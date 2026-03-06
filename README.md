# Seovela - AI SEO Plugin for WordPress

A lightweight, fully free and open-source SEO plugin for WordPress. Every feature is included — no premium tier, no upsells, no locked features.

## Features

### Core SEO
- **Meta Tags** — Custom meta titles & descriptions with live SERP preview
- **XML Sitemaps** — Auto-generated, configurable per post type and taxonomy
- **Schema Markup** — JSON-LD structured data (Article, Product, FAQ, HowTo, LocalBusiness, Person)
- **Robots Meta** — Per-post/page noindex, nofollow, canonical URL controls
- **Open Graph** — Social meta tags for Facebook, Twitter, and more

### Content & Links
- **Internal Link Suggestions** — AI-scored relevance-based linking recommendations
- **Image SEO** — Scan and optimize images (alt text, file size, filenames)
- **Content Analysis** — Keyword density, readability scoring, SEO score

### Technical SEO
- **Redirects Manager** — 301, 302, 307 redirects with hit tracking
- **404 Monitor** — Track and fix broken links with automatic logging

### AI-Powered (BYOK)
- **AI Meta Generation** — Generate SEO titles and descriptions with one click
- **AI Content Assistant** — Write, improve, expand, simplify, and SEO-optimize content
- **Multiple Providers** — OpenAI (GPT-4o), Google Gemini, and Anthropic Claude
- **Streaming** — Real-time AI content generation via Server-Sent Events

### Integrations
- **Google Search Console** — Connect for real performance data
- **Import/Export** — Migrate from Yoast and Rank Math
- **LLMs.txt** — Serve a custom llms.txt file for AI crawlers

## Installation

### From WordPress.org
1. Go to Plugins > Add New
2. Search for "Seovela"
3. Click Install Now, then Activate

### Manual
1. Download or clone this repo into `wp-content/plugins/seovela/`
2. Activate through WordPress admin

## Architecture

- **PHP** — WordPress plugin standards, no framework dependencies
- **JavaScript** — Vanilla JS + jQuery (WordPress bundled)
- **CSS** — Custom stylesheets, no CSS framework
- **Module System** — Each feature is a toggleable module in `modules/`
- **AI Providers** — Abstracted provider pattern supporting OpenAI, Gemini, and Claude

```
seovela/
├── seovela.php              # Main plugin file
├── admin/                   # Admin UI classes and views
├── assets/                  # CSS and JS assets
├── includes/                # Core classes (loader, cache, helpers)
├── modules/                 # Feature modules (ai, meta, schema, etc.)
├── languages/               # Translation files
└── uninstall.php            # Cleanup on delete
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE) for the full text.
