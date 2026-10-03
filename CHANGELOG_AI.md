# CHANGELOG_AI.md - AI Development Record

## [1.0.0] - Initial Production Architecture & Implementation
### Added
- Complete plugin scaffolding with `wpcalibrate-llms-txt-manager.php`, `uninstall.php`, `readme.txt`.
- Class autoloader resolving `WPCalibrate\LlmsTxtManager\Includes\*` and `WPCalibrate\LlmsTxtManager\Admin\*` directly to `includes/` and `admin/` kebab-case files.
- Versioned options manager with `wpcllm_settings`, `wpcllm_draft`, `wpcllm_published`, `wpcllm_schema_version`.
- Deterministic Generator (`Includes\Generator`) conforming strictly to the llms.txt v2 standard.
- Validator (`Includes\Validator`) verifying UTF-8, single H1, valid absolute HTTP/HTTPS URLs, no HTML tags, and distinguishing fatal errors from advisory warnings.
- Defensive Importer (`Includes\Importer`) enforcing file size ceilings, MIME validation, UTF-8 checks, and BOM stripping.
- Content Repository (`Includes\Content_Repository`) querying public WordPress content and generating transparent heuristic suggestions.
- Virtual Router (`Includes\Router`) intercepting `/llms.txt`, setting plain-text headers, ETag, and 304 conditional request handling.
- Physical File Detector (`Includes\Physical_File_Detector`) inspecting server root files and managing safe removal with backups.
- Response Cache (`Includes\Cache`) providing transient response caching with instant invalidation.
- Admin screen (`Admin\Admin`, `Admin\Menu`, views) with Structured Builder, Raw Editor, Import/Export, Settings, and Diagnostics.
- Diagnostic tools with HTTP loopback self-check.
- Unit test suites (`tests/test-generator.php`, `tests/test-validator.php`, `tests/test-importer-parser.php`, `tests/run-tests.php`).

### Fixed / Improved
- **Resolved Fatal Error / Undefined Class in `admin/views/dashboard.php`**: Added `use WPCalibrate\LlmsTxtManager\Admin\Admin;` so `Admin::NONCE_ACTION` and `Admin::NONCE_NAME` resolve reliably within namespace `WPCalibrate\LlmsTxtManager\Admin\Views`.
- **Eliminated IDE/Intelephense "Undefined variable" Warnings in Admin Views**:
  - `admin/views/dashboard.php`: Added `@var` annotations and null-coalescing fallbacks for `$settings`, `$published`, `$draft`, `$phys_info`.
  - `admin/views/settings.php`: Added `@var` annotations and fallback for `$settings`.
  - `admin/views/builder.php`: Added `@var` annotations and fallback for `$draft`.
  - `admin/views/raw-editor.php`: Added `@var` annotations and fallback for `$draft`.
  - `admin/views/import.php`: Added `Menu` import, `@var` annotations, and fallbacks for `$settings` and `$phys_info`.
  - `admin/views/layout.php`: Added `@var` annotations and fallbacks for `$active_tab` and `$notice`.
- **Refined FQCN in `admin/class-builder.php`**: Added `use WPCalibrate\LlmsTxtManager\Includes\Cache;` and replaced inline FQCN.
- **Fixed Standalone Test Mock Type Hint in `tests/run-tests.php`**: Changed `WP_Post` to `object|null` to ensure test runner can run without error in standalone non-WordPress environments.
- **Added Defensive i18n Fallbacks in `assets/js/admin.js`**: Provided default fallback strings via `$.extend` so missing localized script configs never throw JavaScript `TypeError` exceptions.
- **Packaged Standalone Plugin Folder & Zip**: Created `wpcalibrate-llms-txt-manager/` distribution directory and `wpcalibrate-llms-txt-manager.zip` archive containing all production plugin files ready for immediate WordPress installation.
- **Fixed Oversized WPCalibrate Sidebar Menu Icon**: `branding/icon-white.png` is 400x400; WordPress core's `#adminmenu .wp-menu-image` does not specify width/height on `<img>` tags, causing the image to render at natural dimensions (400x400) and overflow across the admin screen. Added `admin_head` hook in `admin/class-menu.php` and scoped CSS in `assets/css/admin.css` strictly constraining the menu icon to 20x20px with `object-fit: contain` and standard WordPress 7px top padding.
- **Established AI Project Memory & Guidelines**: Created and populated `CLAUDE.md`, `PROJECT_MEMORY.md`, `TROUBLESHOOTING.md`, `CHANGELOG_AI.md`, and `TODO_AI.md`.
- **Automated Remote FTP Deployment**:
  - Saved credentials securely into `scripts/ftp-config.json` targeting `169.58.213.1` (port 21, user `ftp-testing`).
  - Created `scripts/ftp-deploy.ps1` with recursive directory synchronization, multi-attempt retry logic with backoff, HTTP/FTP connection pool limits, and progress reporting.
  - Successfully deployed all 38 plugin files to the remote server at `/wp-content/plugins/wpcalibrate-llms-txt-manager/` (0 failures).
- **Automated Clean Rebuild Pipeline (`scripts/build-plugin-folder.ps1`)**:
  - Automatically deletes any existing `wpcalibrate-llms-txt-manager/` folder and `.zip` archive on trigger.
  - Recreates a pristine, production-only plugin folder structure containing all plugin assets while excluding developer configs/tests/scripts.
  - Integrated into `scripts/ftp-deploy.ps1` to ensure every deployment always builds freshly from source before upload.
- **Fixed Multi-Plugin WPCalibrate Menu & Link Collisions**:
  - Identified conflict when `wpcalibrate-tiered-pricing-for-woocommerce` and `wpcalibrate-llms-txt-manager` coexist under the shared `wpcalibrate` parent menu: sibling plugin registered `wpcalibrate` at priority 9, causing all tab links pointing to `admin.php?page=wpcalibrate` to unexpectedly route to Tiered Pricing.
  - Added `Menu::get_admin_url()` method ensuring all internal links, tabs, Quick Actions, and export URLs strictly anchor to `admin.php?page=wpcalibrate-llms-txt-manager`.
  - Added explicit form `action` targets in `builder.php`, `raw-editor.php`, `import.php`, `dashboard.php`, and `settings.php` to prevent form submissions from crossing plugin boundaries.
- **Production Distribution Packaging & Verification (`scripts/build-plugin-folder.ps1`, `scripts/verify-package.ps1`)**:
  - Rebuilt production packaging to strictly isolate production code into a local `wpcalibrate-llms-txt-manager/` folder inside the project root.
  - Enforced complete exclusion of all AI instructions, memory, planning, troubleshooting, and dev artifacts (`.agents`, `.claude`, `AGENTS.md`, `CLAUDE.md`, `PROJECT_MEMORY.md`, `TROUBLESHOOTING.md`, `CHANGELOG_AI.md`, `TODO_AI.md`, `tests/`, `scripts/`, `.git`, `.vscode`, `ftp-config.json`, secrets).
  - Configured installable archive generation using POSIX standard archive creation (`tar -a -c -f`) ensuring all ZIP entries use canonical forward slashes (`/`), resolving Windows `Compress-Archive` backslash extraction issues on Linux WordPress hosts.
  - Generated `wpcalibrate-llms-txt-manager-1.0.0.zip` in the parent directory (`d:\2-FDrive\1- Services\4- Plugins Development\`) and `wpcalibrate-llms-txt-manager.zip` in the project root.
  - Developed automated verification suite (`scripts/verify-package.ps1`) validating that the archive contains exactly one top-level `wpcalibrate-llms-txt-manager/` container, 0 disallowed files, and 100% of required runtime code, templates, assets, branding, and metadata files.




