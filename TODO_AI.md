# TODO_AI.md - Live Development Checklist

## Current Status: Bug Fixes & Strict Quality Verification

- [x] Check workspace and establish primary project context (`CLAUDE.md`, `PROJECT_MEMORY.md`, `TROUBLESHOOTING.md`, `CHANGELOG_AI.md`, `TODO_AI.md`).
- [x] Fix missing `Admin` import and undefined class reference in `admin/views/dashboard.php`.
- [x] Add explicit PHPDoc variable declarations and fallback defaults in all admin view files (`dashboard.php`, `settings.php`, `builder.php`, `raw-editor.php`, `import.php`, `layout.php`).
- [x] Clean up FQCN in `admin/class-builder.php` by importing `Cache`.
- [x] Fix `WP_Post` type hint in `tests/run-tests.php` to prevent undefined type diagnostics in standalone mode.
- [x] Add defensive i18n string fallbacks in `assets/js/admin.js`.
- [x] Inspect all PHP, JS, and CSS files to confirm zero unresolved syntax errors or missing symbols.
- [x] Package standalone plugin folder `wpcalibrate-llms-txt-manager/` and `.zip` archive for direct WordPress installation.
- [x] Fix oversized WPCalibrate sidebar menu icon across WordPress admin by hooking `admin_head` with scoped 20x20px CSS rules.
- [x] Configure and save remote FTP credentials in `scripts/ftp-config.json`.
- [x] Build automated deployment script `scripts/ftp-deploy.ps1` with retry handling and remote directory creation.
- [x] Execute deployment and verify 100% upload success (38/38 files, 0 failures) to `169.58.213.1`.
- [x] Fix .vscode settings.json invalid Intelephense stubs and PowerShell unapproved function verbs.
- [x] Build automated clean rebuild script `scripts/build-plugin-folder.ps1` that deletes old plugin folder and recreates fresh package + zip.
- [x] Integrate clean rebuild into `scripts/ftp-deploy.ps1`.
- [x] Diagnose and fix multi-plugin menu link collision with sibling WPCalibrate plugins (e.g. Tiered Pricing):
  - [x] Implement canonical `Menu::get_admin_url()` anchoring to `wpcalibrate-llms-txt-manager`.
  - [x] Update all tab links in `layout.php` and quick actions in `dashboard.php`.
  - [x] Add explicit form actions in `builder.php`, `raw-editor.php`, `import.php`, `dashboard.php`, and `settings.php`.
  - [x] Guard asset enqueuing in `class-admin.php::is_plugin_screen()` with `Menu::is_current_page()`.
- [x] Run automated build pipeline to delete old plugin folder and recreate fresh package + zip.
- [x] Deploy updated plugin via automated FTP deployment script to remote server.
- [x] Install Git and GitHub CLI (`gh`) and authenticate with user GitHub account (`zeeshanraza-official`).
- [x] Build and test in-dashboard GitHub updater (`includes/class-github-updater.php`) with 6h caching and zipball fallback.
- [x] Complete GitHub repository documentation with comprehensive `README.md` (About, Features, Architecture, Documentation, and Changelog).
- [x] Initialize Git repository, configure .gitignore, commit all production files, and create public repository on GitHub.
- [x] Push `main` branch to `https://github.com/zeeshanraza-official/wpcalibrate-llms-txt-manager`.
- [x] Publish GitHub Release `v1.0.0` with `wpcalibrate-llms-txt-manager.zip` attached for 1-click in-dashboard updates.
- [x] Package production build in `wpcalibrate-llms-txt-manager/` folder inside project root.
- [x] Exclude all AI instructions, memory, planning, troubleshooting, dev files, tests, scripts, credentials, and repository metadata.
- [x] Generate installable ZIP `wpcalibrate-llms-txt-manager-1.0.0.zip` in parent directory (`D:\2-FDrive\1- Services\4- Plugins Development`).
- [x] Ensure cross-platform forward slash path separators in ZIP archive for seamless WordPress/Linux extraction.
- [x] Inspect and verify ZIP contents programmatically via `scripts/verify-package.ps1` (100% PASS).
- [x] Verify deployment and activation on remote testing server via automated FTP synchronization (38/38 files, 0 failures).
- [x] Update documentation (`CLAUDE.md`, `PROJECT_MEMORY.md`, `TROUBLESHOOTING.md`, `CHANGELOG_AI.md`, `TODO_AI.md`).





