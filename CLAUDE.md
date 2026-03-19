# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Seovela is a WordPress SEO plugin (v2.1.0, GPL-3.0). All features are free — no premium tier. Requires WordPress 6.0+ and PHP 7.4+. AI features use a BYOK (bring your own key) model supporting OpenAI, Google Gemini, and Anthropic Claude.

## Development Environment

This is a standard WordPress plugin — no build tools, bundler, or package manager. To develop:

1. Symlink or copy this directory into a WordPress install at `wp-content/plugins/seovela/`
2. Activate via WP Admin > Plugins
3. Access settings under the **Seovela** admin menu

There are no tests, linters, or CI pipelines configured.

## Architecture

### Entry Point & Bootstrapping

`seovela.php` — Plugin entry point. Defines constants (`SEOVELA_VERSION`, `SEOVELA_PLUGIN_DIR`, etc.), runs DB migrations on `admin_init`, and calls `Seovela_Core::instance()`.

### Core Classes (`includes/`)

- **`Seovela_Core`** — Singleton orchestrator. Conditionally loads files based on context (`is_admin()`, `REST_REQUEST`). Initializes `Seovela_Module_Loader`, `Seovela_Admin`, `Seovela_Ajax`, `Seovela_Frontend`, and `Seovela_Conflict_Detector`.
- **`Seovela_Module_Loader`** — Loads modules from `modules/` by convention: file at `modules/{name}/class-seovela-{name}.php`, class name derived by converting hyphens to underscores and ucfirst (e.g., `404-monitor` → `Seovela_404_Monitor`). Modules are enabled/disabled via `seovela_{name}_enabled` wp_options. Uses `get_instance()` singleton if available.
- **`Seovela_Cache`** — Two-tier caching (runtime array + WP transients). All plugin options are batch-loaded via `get_all_plugin_options()`. Auto-invalidates on option update hooks. Use `Seovela_Cache::get_option()` instead of raw `get_option()` for plugin settings.
- **`Seovela_Helpers`** — Static utilities: `encrypt()`/`decrypt()` for API key storage (AES-256-CBC using WP salts), `mask_api_key()`, post type/taxonomy sanitizers.
- **`Seovela_Frontend`** — Outputs meta tags, robots, canonical, OpenGraph, and Twitter cards on `wp_head`. Uses template variables (`%title%`, `%sep%`, `%sitename%`, etc.) resolved by `replace_vars()`.

### Modules (`modules/`)

Each module is self-contained in its own directory. Modules with frontend output (meta, sitemap, schema, llms-txt) load on every request; admin-only modules (optimizer, internal-links, image-seo, gsc-integration, ai) only load in admin context.

Key modules:
- **`ai/`** — Multi-provider AI integration. REST endpoint `seovela/v1/ai-stream` for SSE streaming. AJAX handlers for title/description generation, keyword suggestions, content improvement. Includes 429 retry logic and per-provider token usage tracking.
- **`schema/`** — JSON-LD structured data. `Seovela_Schema` orchestrates, `Seovela_Schema_Builder` generates previews, individual types in `schema/types/` (Article, FAQ, HowTo, LocalBusiness, Person, Product).
- **`content-analysis/`** — `Seovela_SEO_Scorer`, `Seovela_Keyword_Analyzer`, `Seovela_Readability_Analyzer`. Called via AJAX `seovela_analyze_content`.
- **`redirects/` and `404-monitor/`** — Use custom DB tables (`{prefix}_seovela_redirects`, `{prefix}_seovela_404_logs`). Loaded outside the module loader for frontend redirect interception.

### Admin (`admin/`)

- **`Seovela_Admin`** — Registers all WP admin menu pages. Settings page uses tabs (meta, sitemap, schema, indexing, ai). Tools page is a hub linking to Internal Links, Image SEO, Import/Export, and LLMS Txt.
- **`Seovela_Settings`** — Handles `register_setting()` and sanitization callbacks.
- **`Seovela_Metabox`** — Post editor metabox for per-post SEO fields.
- **`Seovela_AI_Editor`** — AI writing assistant integration for the block/classic editor.
- Views in `admin/views/` are PHP templates included by admin class methods.

### Assets

- `assets/css/` and `assets/js/` — Admin-only CSS/JS. No frontend styles. jQuery-dependent.
- Some modules have their own `assets/` directories (gsc-integration, image-seo, internal-links, llms-txt).
- `chart.min.js` is vendored for dashboard charts.

## Conventions

- **Singleton pattern**: Most module classes and admin classes use `get_instance()`. Some use a static `$hooks_added` flag to prevent duplicate hook registration.
- **Post meta keys**: Prefixed with `_seovela_` (e.g., `_seovela_meta_title`, `_seovela_focus_keyword`, `_seovela_noindex`).
- **Options**: Prefixed with `seovela_` (e.g., `seovela_meta_enabled`, `seovela_openai_api_key`).
- **AJAX actions**: Prefixed with `seovela_` (e.g., `seovela_analyze_content`, `seovela_generate_ai_content`). All use nonce verification and capability checks.
- **Text domain**: `seovela` — all user-facing strings use `__()` / `esc_html_e()`.
- **API keys**: Always stored encrypted via `Seovela_Helpers::encrypt()`, decrypted on use.
- **Coding style**: WordPress PHP Coding Standards — spaces for indentation, Yoda conditions, `wp_unslash()` + sanitize on all `$_POST`/`$_GET` access.

## Database Tables

Created on activation (and verified on admin_init):
- `{prefix}_seovela_redirects`
- `{prefix}_seovela_404_logs`
- `{prefix}_seovela_internal_links`
- `{prefix}_seovela_image_seo`
- `{prefix}_seovela_gsc_data` (Google Search Console)

## Adding a New Module

1. Create `modules/{name}/class-seovela-{name}.php` with a class following the naming convention
2. Add the module to `Seovela_Module_Loader::get_available_modules()` and `load_modules()`
3. Add `seovela_{name}_enabled` option default in `seovela_activate()` in `seovela.php`
4. If it needs a DB table, add a static `create_table()` method and call it from `seovela_activate()`
