# Seovela

**The only WordPress SEO plugin where everything is free.**

AI-powered with your own API keys. No credits, no subscriptions, no upsells.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue?logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv3-green)](LICENSE)
[![Version](https://img.shields.io/badge/Version-2.1.0-orange)]()

---

## Why Seovela?

Most SEO plugins lock their best features behind a premium tier. Seovela doesn't. Every feature is available to every user, for free. AI features use a bring-your-own-key model — you pay the AI provider directly at their standard rates with zero markup.

## Features

### Core SEO
- **Meta Tags** — Custom titles & descriptions with live SERP preview
- **XML Sitemaps** — Auto-generated, configurable by post type and taxonomy
- **Schema Markup** — JSON-LD structured data (Article, Product, FAQ, HowTo, LocalBusiness, Person)
- **Robots Controls** — Global and per-post/page noindex, nofollow, and advanced directives
- **Open Graph & Twitter Cards** — Social meta tags with fallback image support
- **Canonical URLs** — Automatic with per-post override

### Tools
- **Redirects Manager** — 301, 302, and 307 redirects with hit tracking
- **404 Monitor** — Track broken URLs with referrer logging and resolution workflow
- **Internal Link Suggestions** — Content-based relevance scoring
- **Image SEO** — Alt text optimization, WebP conversion, and file analysis
- **Import/Export** — Migrate from Yoast and Rank Math, or backup your settings
- **LLMs.txt** — Configure how AI models interact with your site content

### Integrations
- **Google Search Console** — Performance data (clicks, impressions, keywords) in your dashboard
- **IndexNow** — Instant indexing notifications on publish/update

### AI-Powered (Bring Your Own Key)
- Generate SEO titles and meta descriptions
- Content writing, improvement, expansion, and simplification
- SEO content analysis with keyword and readability suggestions
- Focus keyword suggestions
- Cost estimation per request

Supported providers:
| Provider | Models |
|----------|--------|
| **OpenAI** | GPT-5.2, GPT-5 Mini, GPT-5 Nano, GPT-4.1, GPT-4.1 Mini, GPT-4o |
| **Anthropic** | Claude Opus 4.6, Claude Sonnet 4.6, Claude Haiku 4.5 |
| **Google** | Gemini 3.1 Pro, Gemini 3 Flash, Gemini 3.1 Flash Lite |

## Installation

### From WordPress Admin
1. Download the latest release zip
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload the zip and activate

### Manual
1. Clone or download this repo
2. Copy the folder to `wp-content/plugins/seovela/`
3. Activate via **Plugins** in WP Admin

### Setup
1. Go to **Seovela > Dashboard** to see your site overview
2. Configure modules under **Seovela > Modules**
3. Adjust settings under **Seovela > Settings** (Meta, Sitemap, Schema, Indexing, AI)
4. *(Optional)* Add your AI API key under **Settings > AI Optimization**

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher

## Project Structure

```
seovela.php              # Plugin entry point, constants, activation/migration hooks
includes/                # Core classes (Core, Cache, Frontend, Helpers, Ajax, Module Loader)
admin/                   # Admin UI (menu pages, settings, metabox, AI editor)
  views/                 # PHP view templates for admin pages
modules/                 # Self-contained feature modules
  ai/                    # Multi-provider AI integration with SSE streaming
  meta/                  # Meta title/description output
  schema/                # JSON-LD structured data with type classes
  sitemap/               # XML sitemap generation
  redirects/             # URL redirect management (custom DB table)
  404-monitor/           # 404 error tracking (custom DB table)
  internal-links/        # Link suggestion engine
  image-seo/             # Image optimization tools
  gsc-integration/       # Google Search Console OAuth + data
  llms-txt/              # LLMs.txt file generator
  optimizer/              # SEO scoring and analytics
  breadcrumbs/           # Breadcrumb navigation
  content-analysis/      # Keyword and readability analyzers
  import-export/         # Settings migration (Yoast, Rank Math)
assets/                  # Admin CSS, JS, and vendored libraries
languages/               # Translation files (.pot)
```

## Security

- All API keys are encrypted at rest using AES-256-CBC derived from WordPress salts
- AJAX handlers verify nonces and check user capabilities
- All user input is sanitized using WordPress sanitization functions

## Contributing

Contributions are welcome. Please open an issue first to discuss what you'd like to change.

1. Fork the repo
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes
4. Push and open a Pull Request

## License

[GPLv3 or later](LICENSE)

## Links

- [Website](https://seovela.com)
- [Author](https://github.com/freddiehdxd)
