# PROJECT_MEMORY.md - WPCalibrate LLMs.txt Manager

## Core Decisions & Invariants
1. **Virtual Serving Over Physical Writing**:
   - `/llms.txt` is served natively via WordPress rewrite rules (`^llms\.txt$` -> `index.php?wpcllm_endpoint=1`).
   - If plain permalinks are active, fallback interception checks `$_SERVER['REQUEST_URI']`.
   - Never write directly to the web document root during normal publishing.
2. **llms.txt v2 Standard**:
   - Required primary element: Exactly one H1 (`# Project Name`).
   - Optional blockquote: `> Short Summary`.
   - Optional descriptive text before the first section.
   - Zero or more H2 sections: `## Section Name`.
   - Resource list items: `- [Title](URL): Description` or `- [Title](URL)`.
   - Optional section: `## Optional` for secondary or legal resources.
   - Clean UTF-8 plain text without BOM or HTML tags.
3. **Data Storage & Options**:
   - `wpcllm_settings`: Autoloaded configuration array (`enabled`, `editor_mode`, `max_import_size_kb`, `enable_cache`, `cache_ttl`, `delete_on_uninstall`, `generation_post_types`, `max_auto_resources`).
   - `wpcllm_draft`: Stored with `autoload = false`. Holds draft structured data and raw Markdown.
   - `wpcllm_published`: Stored with `autoload = false`. Holds live published data and content hash.
   - `wpcllm_schema_version`: Tracks migration state.
4. **Physical File Precedence**:
   - Web servers (Nginx/Apache/LiteSpeed) serve physical files directly before WordPress executes.
   - Plugin detects physical files, warns the user prominently, and provides options to import, leave unchanged, or safely remove via `WP_Filesystem`.
5. **No Dependencies**:
   - Zero WooCommerce dependency (supports public products generically if active).
   - Zero remote AI APIs or paid services.
   - Free GPL-2.0-or-later license.
6. **Automated FTP Deployment**:
   - Remote credentials saved in `scripts/ftp-config.json` targeting `169.58.213.1`.
   - Automated deployment via `scripts/ftp-deploy.ps1` with retry handling and directory structure synchronization.
7. **Clean Plugin Packaging & Rebuilds**:
   - Upon modifying plugin files, always delete the previous `wpcalibrate-llms-txt-manager/` folder and regenerate it cleanly using `scripts/build-plugin-folder.ps1`.
   - Excludes developer artifacts (`.vscode`, `tests/`, `scripts/`, `.agents/`) and generates `wpcalibrate-llms-txt-manager.zip`.
8. **Shared WPCalibrate Menu Isolation**:
   - In environments with multiple WPCalibrate plugins (e.g. `wpcalibrate-tiered-pricing-for-woocommerce`), the top-level `wpcalibrate` slug may be owned by a sibling plugin.
   - All internal tabs, actions, form targets, and export URLs must strictly anchor to `Menu::SUBMENU_SLUG` (`wpcalibrate-llms-txt-manager`) via `Menu::get_admin_url()`.
   - `Menu::is_current_page()` guarantees assets and action handlers never conflict with sibling screens.
9. **Strict Production Distribution & Packaging Standards**:
   - Packaged folder `wpcalibrate-llms-txt-manager/` must reside directly inside the project root, containing only production-required runtime code, assets, branding, and standard distribution files (`README.md`, `readme.txt`, `LICENSE`, `uninstall.php`).
   - Strict exclusions: AI instructions, memory, planning, troubleshooting, and dev artifacts (`.agents`, `.claude`, `AGENTS.md`, `CLAUDE.md`, `PROJECT_MEMORY.md`, `TROUBLESHOOTING.md`, `CHANGELOG_AI.md`, `TODO_AI.md`, `scripts/`, `tests/`, `.git`, `.vscode`, `ftp-config.json`, secrets).
   - Parent directory installable archive `wpcalibrate-llms-txt-manager-1.0.0.zip` must contain exactly one top-level directory `wpcalibrate-llms-txt-manager/` with standard forward slash (`/`) path separators for 100% Linux/WordPress compatibility.




