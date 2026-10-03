# TROUBLESHOOTING.md - WPCalibrate LLMs.txt Manager

## Common Issues & Diagnoses

### 1. `/llms.txt` Returns 404
- **Root Cause A**: The virtual endpoint is disabled in **Settings**.
  - *Fix*: Go to **WPCalibrate > LLMs.txt Manager > Settings** and ensure "Enable public /llms.txt virtual endpoint" is checked.
- **Root Cause B**: Draft has not been published yet.
  - *Fix*: The plugin serves only published content. Click **Publish Live** in the Builder or Raw Editor.
- **Root Cause C**: WordPress rewrite rules have not flushed.
  - *Fix*: Deactivate and reactivate the plugin, or visit **Settings > Permalinks** in WordPress and click **Save Changes**.

### 2. `/llms.txt` Serves Outdated Content or Bypasses WordPress
- **Root Cause**: A physical `llms.txt` file exists in the server document root. Web servers (Apache/Nginx/LiteSpeed/CDNs) serve static files directly from disk without invoking PHP/WordPress.
  - *Fix*: Go to **Dashboard** or **Import & Export**, review the detected physical file, import its contents, and remove the physical file using the built-in file manager action or via FTP.
- **Root Cause 2**: Caching (either plugin transient cache or an upstream CDN/Varnish cache).
  - *Fix*: The plugin automatically flushes its transient cache on publish. For external CDNs (Cloudflare), purge the `/llms.txt` URL cache.

### 3. "Class Admin not found" in `dashboard.php`
- **Root Cause**: In `admin/views/dashboard.php`, namespace `WPCalibrate\LlmsTxtManager\Admin\Views` was missing `use WPCalibrate\LlmsTxtManager\Admin\Admin;`.
  - *Fix*: Add `use WPCalibrate\LlmsTxtManager\Admin\Admin;` at the top of `admin/views/dashboard.php`.

### 4. "Undefined variable" warnings in Admin Views
- **Root Cause**: View files included by `class-admin.php` reference variables passed in scope (`$settings`, `$draft`, `$published`, `$phys_info`, `$notice`, `$active_tab`) which static analyzers and IDEs flag if not type-hinted or initialized in file scope.
  - *Fix*: Add `@var` type annotations and null-coalescing default fallbacks at the top of each view template.

### 5. Loopback Self-Check Fails
- **Root Cause**: Some hosting providers disable internal loopback HTTP requests (server requesting itself).
  - *Note*: A loopback error does not mean external users cannot access `/llms.txt`. Test by opening the public URL directly in a browser or via `curl`.

### 6. WPCalibrate Sidebar Menu Icon Appears Oversized Across Admin
- **Root Cause**: `branding/icon-white.png` is an uncompressed high-resolution master asset (400x400). WordPress core outputs `<div class="wp-menu-image"><img src="..." alt="" /></div>` without `width` and `height` CSS attributes, allowing the browser to render the full 400x400 natural image size across the admin screen.
  - *Fix*: In `admin/class-menu.php`, hooked `admin_head` to output scoped CSS rules (`#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img`) enforcing `width: 20px !important`, `height: 20px !important`, `padding: 7px 0 0 !important`, and `object-fit: contain !important` across all WordPress admin screens. Also mirrored these rules in `assets/css/admin.css`.

### 7. Multiple WPCalibrate Plugins Installed: Clicking Tabs Opens Sibling Plugin Settings
- **Root Cause**: Multiple plugins (such as `wpcalibrate-tiered-pricing-for-woocommerce` and `wpcalibrate-llms-txt-manager`) share the top-level `wpcalibrate` admin menu. When a sibling plugin registers `wpcalibrate` first, WordPress assigns `admin.php?page=wpcalibrate` to that sibling plugin. If tabs or quick links within LLMs.txt Manager link to `admin.php?page=wpcalibrate&tab=...`, WordPress routes those requests to the sibling plugin's settings page. Furthermore, asset enqueuing could erroneously trigger on sibling screens.
  - *Fix*:
    1. Implemented `Menu::get_admin_url( array $query_args = [] )` which always anchors links and form actions to the canonical plugin submenu slug `wpcalibrate-llms-txt-manager` (`admin.php?page=wpcalibrate-llms-txt-manager&tab=...`).
    2. Updated all navigation tabs, quick action links, export URLs, and form actions across `layout.php`, `dashboard.php`, `builder.php`, `raw-editor.php`, `import.php`, and `settings.php` to use `Menu::get_admin_url()`.
    3. Added `Menu::is_current_page()` and `Menu::owns_parent()` checks to `class-admin.php::is_plugin_screen()`, preventing script and style collision on sibling WPCalibrate admin screens.

### 8. Plugin Zip Extraction Failing or Creating Flat Files on Linux/WordPress
- **Root Cause**: PowerShell's default `Compress-Archive` cmdlet on Windows uses backward slashes (`\`) for internal ZIP entry paths. When uploaded to a Linux server or extracted by standard unzippers, paths like `wpcalibrate-llms-txt-manager\admin\class-admin.php` are treated as literal filenames instead of directory hierarchies, breaking plugin activation.
- **Fix**: The build script (`scripts/build-plugin-folder.ps1`) uses POSIX-standard `tar -a -c -f` which stores entries with standard forward slashes (`/`), ensuring seamless extraction on both Windows, macOS, and Linux WordPress hosting environments.

