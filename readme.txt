=== Seovela - AI SEO Plugin ===
Contributors: josedeveloper
Tags: seo, meta tags, sitemap, schema, ai seo
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The only WordPress SEO plugin where everything is free. AI-powered with your own API keys. No credits, no subscriptions, no upsells.

== Description ==

Seovela is a lightweight, fully open-source SEO plugin that gives you everything you need to optimize your WordPress site — without paywalls.

**Core SEO Features (All Free):**
* Meta titles & descriptions with SERP preview
* XML Sitemaps (auto-generated, configurable)
* Schema Markup (Article, Product, FAQ, HowTo, LocalBusiness, and more)
* Internal Link Suggestions with relevance scoring
* Image SEO with WebP conversion and auto alt text
* 301/302/307 Redirects Manager
* 404 Monitor with hit tracking
* Google Search Console integration
* Robots meta controls (per post/page)
* Open Graph & social meta tags
* Import/Export with migration from Yoast and Rank Math
* LLMs.txt generator

**AI-Powered Features (Bring Your Own Key):**
* Generate SEO titles and meta descriptions
* Content optimization suggestions
* Supports OpenAI (GPT-4o, GPT-4o-mini), Google Gemini, and Anthropic Claude
* Your API key, your costs — no markup, no credit system

== Installation ==

1. Upload the `seovela` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to Seovela > Dashboard to configure
4. (Optional) Add your AI API key under SEO Settings > AI Optimization

== Frequently Asked Questions ==

= Is Seovela really 100% free? =
Yes. Every feature is free. No premium tier, no locked features, no upsells.

= How does the AI feature work? =
You provide your own API key from OpenAI, Google, or Anthropic. The plugin calls the API directly from your server. You pay the provider at their standard rates — we add zero markup.

= Can I migrate from Yoast or Rank Math? =
Yes. Go to Seovela > Import/Export > Migration to import your settings and meta data.

== External services ==

This plugin connects to several third-party services under certain conditions. No data is sent to any external service unless you explicitly enable the corresponding feature and provide any required credentials.

= OpenAI API =

When you configure an OpenAI API key and use the AI content features, the plugin sends post content and SEO context to OpenAI's servers to generate SEO titles, meta descriptions, and content optimization suggestions.

* Service URL: https://api.openai.com/v1/chat/completions
* Terms of Use: https://openai.com/policies/terms-of-use
* Privacy Policy: https://openai.com/policies/privacy-policy

= Google Gemini API =

When you configure a Google Gemini API key and use the AI content features, the plugin sends post content and SEO context to Google's servers to generate SEO titles, meta descriptions, and content optimization suggestions.

* Service URL: https://generativelanguage.googleapis.com/v1beta/models/
* Terms of Use: https://ai.google.dev/gemini-api/terms
* Privacy Policy: https://policies.google.com/privacy

= Anthropic Claude API =

When you configure an Anthropic API key and use the AI content features, the plugin sends post content and SEO context to Anthropic's servers to generate SEO titles, meta descriptions, and content optimization suggestions.

* Service URL: https://api.anthropic.com/v1/messages
* Terms of Use: https://www.anthropic.com/terms
* Privacy Policy: https://www.anthropic.com/privacy

= Google Search Console API =

When you connect your Google Search Console account, the plugin fetches search performance data (clicks, impressions, keywords, and pages) from Google's servers to display analytics in your WordPress dashboard.

* Service URL: https://searchconsole.googleapis.com/webmasters/v3
* Terms of Use: https://developers.google.com/terms
* Privacy Policy: https://policies.google.com/privacy

= Google OAuth =

When you connect your Google Search Console account, the plugin uses Google OAuth for authentication. The OAuth flow is handled through a callback server at seovela.com, which facilitates the token exchange without exposing client secrets. Your site URL and OAuth refresh token are sent to seovela.com during token refresh.

* Google OAuth URL: https://oauth2.googleapis.com/token
* Seovela OAuth Proxy: https://seovela.com/oauth/callback/ and https://seovela.com/oauth/refresh/
* Google Terms of Use: https://developers.google.com/terms
* Google Privacy Policy: https://policies.google.com/privacy
* Seovela Privacy Policy: https://seovela.com/privacy

= IndexNow API =

When you enable the IndexNow feature and publish or update content, the plugin sends the page URL and your site's host information to the IndexNow API to notify search engines about content changes for faster indexing.

* Service URL: https://api.indexnow.org/indexnow
* Terms of Use: https://www.indexnow.org/terms
* Privacy Policy: https://www.indexnow.org/privacy

= Google Sitemap Ping =

When you update your sitemap, the plugin sends a ping to Google to notify them of the change. Only the sitemap URL is sent.

* Service URL: https://www.google.com/ping?sitemap=
* Terms of Use: https://policies.google.com/terms
* Privacy Policy: https://policies.google.com/privacy

== Changelog ==

= 2.1.0 =
* Open-sourced all features — removed Pro tier
* Added Claude (Anthropic) as AI provider
* Removed license system
* All features now available for free

= 2.0.0 =
* Major redesign with modern dashboard
* Added AI-powered content optimization
* Added Google Search Console integration
* Added Image SEO module
* Added Internal Links module
* Added LLMs.txt generator
