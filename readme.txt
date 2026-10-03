=== WPCalibrate LLMs.txt Manager ===
Contributors: wpcalibrate
Tags: llms, llms-txt, ai, markdown, seo
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create, generate, edit, validate, import, and serve your site's /llms.txt file natively and virtually through WordPress without file manager access.

== Description ==

**WPCalibrate LLMs.txt Manager** empowers WordPress administrators to manage, curate, and publish an official `/llms.txt` file directly from the WordPress admin dashboard without needing FTP, SSH, cPanel, or server file manager access.

### What is llms.txt?
The `llms.txt` specification is an open standard designed to help Large Language Models (LLMs) and autonomous AI agents discover and ingest curated documentation, key resources, and high-value project content efficiently in clean plain Markdown.

### How Virtual Serving Works
Rather than physically writing files into your WordPress root directory or web server document root, WPCalibrate LLMs.txt Manager utilizes WordPress's native rewrite rules and request handling. When an AI client or web browser requests `/llms.txt`, WordPress dynamically intercepts the request and outputs clean, plain-text UTF-8 Markdown without HTML wrappers, theme templates, or unwanted overhead.

### Key Features
* **Virtual Endpoint Serving**: Serves `/llms.txt` cleanly through WordPress routing. No physical file writing required.
* **Dual Editing Modes**: Switch seamlessly between a **Structured Builder** (drag-and-drop sections, post picker, repeatable fields) and a **Raw Markdown Editor** (plain text with syntax validation).
* **Deterministic llms.txt v2 Standard**: Compliant with the llms.txt v2 format, featuring a required H1 site title, optional blockquote summary, descriptive context, and categorized H2 resource sections with optional secondary links.
* **Intelligent Auto-Generation**: Suggests a starting configuration using transparent heuristics (Front Page, Blog, About, Contact, Documentation, Services, and key published posts). Never fabricates descriptions or contacts external AI services.
* **WordPress Content Search**: Select and insert public pages, posts, or custom post types with automated title, URL, and excerpt population.
* **Safe Text Import**: Import existing `.txt` or `.md` files with strict MIME inspection, configurable file size ceilings (default 256KB), UTF-8 validation, and BOM stripping.
* **Physical File Conflict Detection**: Automatically detects if a static `llms.txt` file exists in your server document root. Provides options to import the file, leave it untouched, or safely remove it with automatic backup.
* **Draft & Published Separation**: Edit freely in draft mode without exposing unapproved changes. Publish only when you are ready.
* **Strict Validation Engine**: Comprehensive checks separating fatal errors (missing H1, invalid URLs, HTML tags, binary content) from advisory warnings (missing summaries, large file size, duplicates).
* **Lightweight Output Caching**: Built-in transient response caching with instant invalidation upon publishing, editing, or disabling the endpoint.
* **Public Self-Check Tool**: On-demand HTTP loopback testing to confirm what external web clients and scrapers actually receive from your server.
* **100% Free & Private**: No API keys, no external AI calls, no telemetry, no tracking, and no paid upsells. Completely self-contained.

== Installation ==

1. Upload the `wpcalibrate-llms-txt-manager` folder to the `/wp-content/plugins/` directory, or install the plugin zip file directly through **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **WPCalibrate > LLMs.txt Manager** in your WordPress admin menu.
4. Review your default site title and summary in the Structured Builder, or click **Generate Suggestions** to curate your key content.
5. Click **Publish Live** to activate your `/llms.txt` file.

== Frequently Asked Questions ==

= Does this plugin physically create a file in my server root? =
No. The plugin serves `/llms.txt` virtually through WordPress rewrite rules. This ensures compatibility with diverse hosting environments, containerized deployments, and read-only filesystems without needing FTP or SSH permissions.

= What happens if I already have a physical llms.txt file in my root directory? =
Web servers like Nginx and Apache normally serve physical static files directly from disk before WordPress is executed. The plugin detects this and displays a conflict notice with options to import the physical file into your WordPress draft or safely remove it using the WordPress Filesystem API.

= Does llms.txt guarantee AI citation or higher rankings? =
No. While `llms.txt` provides structured, machine-readable documentation for AI crawlers and developer agents, search engines and LLM providers operate according to their own proprietary indexing and retrieval algorithms.

= Can I use custom post types or WooCommerce products? =
Yes. Any publicly queryable post type registered on your site can be searched and included in your resource sections.

= Is WooCommerce required? =
No. WooCommerce is completely optional and is not a dependency.

= What happens when I deactivate the plugin? =
Your draft and published configurations are preserved in your database options table. The virtual rewrite route is disabled, and `/llms.txt` will return a standard 404 until reactivated.

= What happens when I uninstall the plugin? =
By default, all user data is safely preserved. If you wish to completely delete all plugin options upon uninstall, check the "Delete plugin data on uninstall" option in **Settings**.

== Developer Hooks & Extensibility ==

The plugin provides several documented filter hooks for advanced developer customization:

* `apply_filters('wpcllm_manage_capability', 'manage_options')` - Customize the capability required to administer the plugin.
* `apply_filters('wpcllm_eligible_post_types', $post_types)` - Filter which post types appear in the content picker and generator.
* `apply_filters('wpcllm_suggested_sections', $suggestions)` - Alter programmatic starting suggestions.
* `apply_filters('wpcllm_generated_markdown', $markdown, $structured_data)` - Modify generated Markdown before validation or saving.
* `apply_filters('wpcllm_serve_llms_txt_content', $content, $published_data)` - Alter the exact plain text served on frontend `/llms.txt` requests.
* `apply_filters('wpcllm_robots_tag', 'index, follow')` - Customize the `X-Robots-Tag` HTTP header sent with `/llms.txt`.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Support for llms.txt v2 specification.
* Structured Builder with WordPress post search and repeatable controls.
* Raw Markdown Editor with live AJAX syntax validation.
* Virtual endpoint routing with ETag and 304 conditional request support.
* Safe text file import with BOM stripping and size limits.
* Physical file detection, conflict handling, and HTTP loopback self-check.
* Lightweight output caching with deterministic invalidation.
