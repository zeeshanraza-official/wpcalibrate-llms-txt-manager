# WPCalibrate LLMs.txt Manager

[![WordPress 6.2+](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg?logo=php&logoColor=white)](https://php.net)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Standard: llms.txt v2](https://img.shields.io/badge/Standard-llms.txt%20v2-orange.svg)](https://llmstxt.org)

> **Create, generate, edit, import, validate, publish, and serve site `/llms.txt` natively and virtually through WordPress.**

**WPCalibrate LLMs.txt Manager** empowers WordPress administrators and site owners to take full control over how Large Language Models (LLMs), AI crawlers, and search agents understand their website—without editing server configuration, manually uploading files over FTP, or placing physical files in the document root.

---

## Table of Contents

- [About](#about)
- [Key Features](#key-features)
- [Architecture & Standards](#architecture--standards)
- [Documentation & Usage Guide](#documentation--usage-guide)
  - [Installation](#installation)
  - [Structured Builder](#structured-builder)
  - [Raw Markdown Editor](#raw-markdown-editor)
  - [Import & Physical File Management](#import--physical-file-management)
  - [Settings & Performance](#settings--performance)
  - [Status & Diagnostics Self-Check](#status--diagnostics-self-check)
- [Automatic In-Dashboard Updates](#automatic-in-dashboard-updates)
- [Changelog](#changelog)
- [Security & Contributing](#security--contributing)
- [License](#license)

---

## About

The `/llms.txt` file format (introduced by Jeremy Howard and the Answer.AI initiative) is an emerging web standard designed to provide Large Language Models and AI agents with structured, high-signal, token-efficient summaries and links to authoritative resources across your website.

Traditional solutions require developers to write static text files and upload them to the server web root via FTP, SSH, or cPanel file managers. This approach is prone to stale content, breaks multi-environment workflows, and requires manual server access for every edit.

**WPCalibrate LLMs.txt Manager solves this natively:**
1. **Virtual Serving**: `/llms.txt` is served directly by WordPress through custom rewrite rules and template redirection.
2. **Draft & Published Lifecycle**: Safely draft, edit, and validate changes before publishing them live to the world.
3. **No External Dependencies**: 100% self-contained PHP. Zero paid APIs, zero tracking, zero telemetry, and zero third-party cloud lock-in.

---

## Key Features

### 🚀 Native Virtual Endpoint
- Serves `/llms.txt` at the root of your WordPress domain using WordPress rewrite rules (`^llms\.txt$` &rarr; `index.php?wpcllm_endpoint=1`).
- Transparent fallback interception for sites using plain permalinks (`?p=123`).
- Sends correct HTTP headers: `Content-Type: text/plain; charset=utf-8`, `X-Robots-Tag: index, follow`, `Cache-Control`, and `ETag`.
- Built-in conditional request handling (`304 Not Modified`) for bandwidth efficiency and web crawler performance.

### 📐 llms.txt v2 Standard Compliance
- **Single H1 Title**: Strictly validates and formats the primary site/project heading (`# Project Name`).
- **Summary Blockquote**: Formats the optional short summary (`> Short summary`).
- **Narrative Markdown**: Supports optional contextual description text before sections.
- **H2 Grouping**: Divides links into clean topical sections (`## Section Name`).
- **Resource Item Syntax**: Formats links deterministically as `- [Title](URL): Description` or `- [Title](URL)`.
- **Optional Resources**: Supports the canonical `## Optional` section for secondary documentation and legal disclosures.
- **Strict Sanitization**: Strips HTML tags, ensures absolute HTTP/HTTPS URLs, and removes UTF-8 Byte Order Marks (BOM).

### 🎨 Visual Structured Builder
- Visual editor with repeatable sections and resource items.
- Live search modal to quickly find and attach any public WordPress Post, Page, or WooCommerce Product.
- Intelligent automatic suggestion generator that heuristics-scans high-value site pages to bootstrap your `/llms.txt` in seconds.
- Interactive live draft preview with syntax validation feedback.

### ✍️ Raw Markdown Editor
- Direct plain-text editing environment for developers and advanced users.
- Real-time syntax validation distinguishing fatal errors (missing H1, invalid URLs) from advisory warnings (missing descriptions, long text).
- Seamless two-way switching between Structured Builder and Raw Markdown modes.

### 🛡️ Physical File Precedence & Conflict Management
- Detects whether a physical `llms.txt` file exists in the web server root that might override WordPress rewrite rules.
- Inspects file permissions, size, and last modified timestamps.
- One-click import tool to transfer physical file contents into your WordPress draft.
- Safe, confirmed deletion of physical files using WordPress's native `WP_Filesystem` API with automated backup creation.

### ⚡ High Performance & Transient Caching
- Optional WordPress transient caching for live `/llms.txt` output.
- Configurable Time-To-Live (TTL).
- Instant cache invalidation whenever content is saved, published, imported, or toggled.

### 🔍 Automated HTTP Loopback Self-Check
- Comprehensive diagnostics screen testing HTTP loopback requests against your public `/llms.txt` URL.
- Verifies HTTP status code (200 OK), `Content-Type`, content hash matching, and rewrite engine health.
- One-click "Copy Diagnostic Report" button formatted for support and troubleshooting.

---

## Architecture & Standards

```
wpcalibrate-llms-txt-manager/
├── wpcalibrate-llms-txt-manager.php   # Bootstrap, constants, autoloader, activation
├── uninstall.php                      # Safe uninstaller (respects data retention setting)
├── readme.txt                         # WordPress.org standard metadata
├── README.md                          # GitHub documentation
├── admin/                             # Admin screens & controllers
│   ├── class-admin.php                # Screen coordinator & asset dispatcher
│   ├── class-menu.php                 # Isolated WPCalibrate admin menu handler
│   ├── class-builder.php              # Structured Builder controller
│   ├── class-raw-editor.php           # Raw Markdown controller
│   ├── class-import.php               # File import/export & physical file controller
│   ├── class-settings.php             # Settings controller
│   ├── class-status.php               # System diagnostics & loopback self-check
│   └── views/                         # Clean, isolated PHP view templates
├── includes/                          # Core business logic
│   ├── class-plugin.php               # Coordinator singleton
│   ├── class-activator.php            # Activation & rewrite initialization
│   ├── class-deactivator.php          # Deactivation & rewrite cleanup
│   ├── class-router.php               # Virtual /llms.txt rewrite router & ETag handler
│   ├── class-generator.php            # Deterministic llms.txt v2 Markdown generator
│   ├── class-validator.php            # Specification validator (fatal vs advisory)
│   ├── class-importer.php             # Defensive file upload parser & BOM stripper
│   ├── class-content-repository.php   # WP_Query content discovery wrapper
│   ├── class-physical-file-detector.php # Static file inspection & safe removal
│   ├── class-cache.php                # Transient caching manager
│   ├── class-github-updater.php       # In-dashboard GitHub release updater
│   └── class-options.php              # Type-safe options manager (autoload=false)
├── assets/                            # Scoped CSS and JavaScript
│   ├── css/admin.css                  # Responsive, accessible, RTL-ready styles
│   └── js/admin.js                    # Repeaters, modals, search debounce, self-check
├── branding/                          # Official brand icon assets
└── languages/                         # Translation template (.pot)
```

- **PHP Strict Typing**: `declare(strict_types=1);` enforced across all PHP classes.
- **WordPress Standards**: Nonce verification, strict capability checks (`manage_options`), input sanitization, contextual output escaping.
- **Isolated Multi-Plugin Coexistence**: Fully interoperable with other WPCalibrate plugins sharing the top-level `wpcalibrate` admin menu without route or asset collisions.

---

## Documentation & Usage Guide

### Installation

1. Download the latest `wpcalibrate-llms-txt-manager.zip` from [Releases](https://github.com/zeeshanraza-official/wpcalibrate-llms-txt-manager/releases).
2. In your WordPress dashboard, navigate to **Plugins > Add New Plugin > Upload Plugin**.
3. Select the `.zip` file and click **Install Now**, then **Activate Plugin**.
4. Access the management console from the WordPress sidebar under **WPCalibrate > LLMs.txt Manager**.

### Structured Builder

1. Navigate to **LLMs.txt Manager > Structured Builder**.
2. Customize the **Project Title (H1)** and **Short Summary**.
3. Click **Add Section** to create a new category (e.g., `Core Documentation`, `API Reference`).
4. Click **Select from Site** to open the Content Picker modal and automatically populate title and URL from any published post, page, or product.
5. Click **Generate Suggestions** to allow the plugin to automatically recommend top pages based on your site hierarchy.
6. Click **Save Draft** to record your work, or **Publish Live** to serve it immediately at `/llms.txt`.

### Raw Markdown Editor

1. Navigate to **LLMs.txt Manager > Raw Markdown**.
2. Directly edit the Markdown content in the code editor.
3. Observe live validation feedback below the editor for any syntax or URL issues.
4. Click **Save Draft** or **Publish Live**.

### Import & Physical File Management

1. Navigate to **LLMs.txt Manager > Import & Export**.
2. Drag and drop any `.txt` or `.md` file to validate and import it into your draft.
3. If an existing static `llms.txt` file exists in your server document root, the plugin flags it with file permissions and provides one-click options to:
   - **Import File Contents into Draft**
   - **Remove Physical File** (creates a timestamped `.bak` copy and removes the physical file so the virtual endpoint takes over).

### Settings & Performance

1. Navigate to **LLMs.txt Manager > Settings**.
2. **Virtual Serving**: Toggle `/llms.txt` on or off without losing your draft or published data.
3. **Response Caching**: Enable transient caching and set custom TTL in seconds.
4. **Eligible Post Types**: Select which post types (e.g. Posts, Pages, WooCommerce Products) appear in search and suggestions.
5. **Uninstall Cleanup**: Choose whether to preserve or permanently delete configurations upon plugin uninstallation.

### Status & Diagnostics Self-Check

1. Navigate to **LLMs.txt Manager > Status & Diagnostics**.
2. Click **Test Public Endpoint Now** to perform an internal HTTP loopback test verifying that external LLMs receive HTTP 200 with matching content.
3. Click **Copy Diagnostic Report** to copy system details for support requests.

---

## Automatic In-Dashboard Updates

This plugin includes an integrated **GitHub Release Updater**.

Whenever a new version tag (e.g. `v1.0.1`) is released on GitHub with an attached `.zip` asset:
1. WordPress automatically detects the update during standard plugin update checks.
2. An update notification appears in **Plugins > Installed Plugins**:
   > *"There is a new version of WPCalibrate LLMs.txt Manager available. View version details or update now."*
3. Clicking **Update Now** automatically downloads and updates the plugin in place through the WordPress dashboard.

---

## Changelog

### [1.0.0] - 2026-10-03
#### Initial Release
- **Core Architecture**: Native virtual `/llms.txt` rewrite routing with fallback plain permalinks handling.
- **Specification**: Complete adherence to the llms.txt v2 proposal.
- **Visual Builder**: Dynamic section and link builder with drag-and-drop support.
- **Content Picker**: Fast AJAX search and heuristic suggestion engine for WordPress Posts, Pages, and Custom Post Types.
- **Raw Editor**: Syntax-checked direct Markdown authoring interface.
- **Defensive Importer**: Upload handler with file size limit, MIME validation, and UTF-8 BOM stripping.
- **Physical File Manager**: Server root inspection, automated backup creation, and safe WP_Filesystem removal.
- **Performance**: Transient caching with automatic invalidation on publication.
- **Diagnostics**: HTTP loopback self-check with ETag and hash verification.
- **Branding & Menu**: Responsive admin styling, retina-scaled sidebar branding, and multi-plugin shared menu isolation.
- **GitHub Updater**: Native in-dashboard update checker for GitHub releases.

---

## Security & Contributing

If you discover a security vulnerability, please submit a report privately via GitHub Security Advisories or contact the development team at [support@wpcalibrate.com](mailto:support@wpcalibrate.com).

Pull requests, feature requests, and issue reports are welcome on [GitHub](https://github.com/zeeshanraza-official/wpcalibrate-llms-txt-manager).

---

## License

WPCalibrate LLMs.txt Manager is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
