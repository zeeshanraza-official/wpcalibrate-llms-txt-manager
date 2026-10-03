# CLAUDE.md - WPCalibrate LLMs.txt Manager

## Overview
**WPCalibrate LLMs.txt Manager** is a WordPress plugin developed by WPCalibrate that allows administrators to create, edit, import, validate, and serve the site's `/llms.txt` file virtually through WordPress rewrite rules without writing physical files to the site root.

- **Plugin Slug**: `wpcalibrate-llms-txt-manager`
- **PHP Namespace**: `WPCalibrate\LlmsTxtManager`
- **Procedural Prefix**: `wpcllm_`
- **Text Domain**: `wpcalibrate-llms-txt-manager`
- **PHP Requirements**: PHP 8.2 and PHP 8.3 explicitly supported
- **WordPress Requirements**: WordPress 6.2+ (tested up to 6.7)
- **Specification**: llms.txt v2 proposal

## Architecture & Code Organization
- `wpcalibrate-llms-txt-manager.php`: Main bootstrap, autoloader, constants, and activation/deactivation hooks.
- `uninstall.php`: Direct-access guarded uninstaller respecting user data retention setting.
- `includes/`:
  - `class-plugin.php`: Core coordinator singleton.
  - `class-activator.php`: Activation routines, initial options, rewrite rule flush.
  - `class-deactivator.php`: Deactivation cleanup, transient purge, rewrite flush.
  - `class-upgrader.php`: Idempotent schema migrations.
  - `class-options.php`: Centralized, type-safe options manager with `autoload=false` for content.
  - `class-router.php`: Virtual `/llms.txt` routing, template interception, ETag, 304 handling.
  - `class-generator.php`: Deterministic llms.txt v2 Markdown generator.
  - `class-validator.php`: Validator distinguishing blocking errors from advisory warnings.
  - `class-importer.php`: Defensive file uploads, BOM stripping, and markdown-to-structured parser.
  - `class-content-repository.php`: WP_Query wrapper for content search and heuristic suggestions.
  - `class-physical-file-detector.php`: Inspection of physical `llms.txt` files and safe WP_Filesystem removal.
  - `class-cache.php`: Transient response caching with instant invalidation.
- `admin/`:
  - `class-admin.php`: Screen coordinator, asset enqueue, AJAX dispatchers, form submission router.
  - `class-menu.php`: Shared top-level WPCalibrate menu and LLMs.txt Manager submenu.
  - `class-builder.php`: Controller for the Structured Builder.
  - `class-raw-editor.php`: Controller for the Raw Markdown Editor.
  - `class-import.php`: Controller for file upload, physical file actions, and exports.
  - `class-settings.php`: Controller for plugin options and caching preferences.
  - `class-status.php`: Diagnostics compiler and HTTP loopback self-check runner.
  - `views/`: Tabbed templates (`dashboard.php`, `builder.php`, `raw-editor.php`, `import.php`, `settings.php`, `status.php`, `modal-content-picker.php`, `layout.php`).
- `assets/`:
  - `css/admin.css`: Scoped, responsive, accessible, RTL-ready styles.
  - `js/admin.js`: Repeatable fields, debounce search, modals, self-check.
- `branding/`: Contains official `icon-dark.png` and `icon-white.png`.
- `tests/`: Automated test suites for Generator, Validator, and Importer/Parser.

## Development & Code Standards
1. **Strict Types**: Always include `declare(strict_types=1);` at the top of PHP files.
2. **Security**:
   - Check `current_user_can(Menu::get_capability())` for all administrative actions.
   - Verify nonces via `check_admin_referer()` or `check_ajax_referer()`.
   - Sanitize all input (`sanitize_text_field`, `esc_url_raw`, `wp_unslash`).
   - Contextually escape all output (`esc_html`, `esc_attr`, `esc_url`).
3. **No External Calls**: Never introduce telemetry, tracking, or remote AI APIs.
4. **No Direct Writes**: Virtual serving is primary; physical files must never be silently overwritten.

## Deployment & Tooling
- `scripts/build-plugin-folder.ps1`: Automated packaging script that removes the old `wpcalibrate-llms-txt-manager/` folder and `wpcalibrate-llms-txt-manager.zip`, creates a fresh build directory with all production assets, and generates an updated `.zip` distribution archive.
- `scripts/ftp-config.json`: Host, credentials, and remote plugin path (`wp-content/plugins/wpcalibrate-llms-txt-manager`).
- `scripts/ftp-deploy.ps1`: Automated recursive FTP deployment script that triggers `build-plugin-folder.ps1` first, ensures remote directory hierarchy, and uploads with connection retry handling.
- `scripts/github-publish.ps1`: Creates the public GitHub repository via GitHub CLI (`gh`), pushes the `main` branch, and publishes release `v1.0.0` with the distribution `.zip` archive attached.
- Rebuild Plugin Folder:
  ```powershell
  powershell -ExecutionPolicy Bypass -File scripts\build-plugin-folder.ps1
  ```
- Deploy to Remote:
  ```powershell
  powershell -ExecutionPolicy Bypass -File scripts\ftp-deploy.ps1
  ```
- Publish to GitHub:
  ```powershell
  powershell -ExecutionPolicy Bypass -File scripts\github-publish.ps1
  ```



