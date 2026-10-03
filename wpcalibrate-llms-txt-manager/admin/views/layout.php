<?php
/**
 * Main admin layout template.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin\Views
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin\Views;

use WPCalibrate\LlmsTxtManager\Admin\Admin;
use WPCalibrate\LlmsTxtManager\Admin\Menu;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var string $active_tab
 * @var array{type: string, message: string, errors?: list<string>, warnings?: list<string>}|null $notice
 */
$active_tab = $active_tab ?? 'dashboard';
$notice     = $notice ?? null;
$public_url = home_url( '/llms.txt' );
$icon_dark  = WPCLLM_PLUGIN_URL . 'branding/icon-dark.png';
$base_url   = Menu::get_admin_url();
?>

<div class="wrap wpcllm-wrap">
	<!-- Plugin Header with Branding -->
	<header class="wpcllm-header">
		<div class="wpcllm-branding">
			<img src="<?php echo esc_url( $icon_dark ); ?>" alt="<?php esc_attr_e( 'WPCalibrate Logo', 'wpcalibrate-llms-txt-manager' ); ?>" class="wpcllm-brand-icon" width="36" height="36" />
			<div class="wpcllm-title-group">
				<h1 class="wpcllm-title"><?php esc_html_e( 'WPCalibrate LLMs.txt Manager', 'wpcalibrate-llms-txt-manager' ); ?></h1>
				<span class="wpcllm-version-tag">v<?php echo esc_html( WPCLLM_VERSION ); ?></span>
			</div>
		</div>
		<div class="wpcllm-header-actions">
			<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer" class="button wpcllm-btn-outline">
				<span class="dashicons dashicons-external"></span>
				<?php esc_html_e( 'View Public /llms.txt', 'wpcalibrate-llms-txt-manager' ); ?>
			</a>
			<button type="button" class="button wpcllm-preview-trigger" data-source="draft">
				<span class="dashicons dashicons-visibility"></span>
				<?php esc_html_e( 'Preview Draft', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
		</div>
	</header>

	<!-- Notices Renderer -->
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['success'] ? 'success' : 'error' ); ?> is-dismissible wpcllm-notice">
			<p><strong><?php echo esc_html( $notice['message'] ); ?></strong></p>
			<?php if ( ! empty( $notice['errors'] ) ) : ?>
				<ul class="wpcllm-notice-list wpcllm-notice-errors">
					<?php foreach ( $notice['errors'] as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $notice['warnings'] ) ) : ?>
				<ul class="wpcllm-notice-list wpcllm-notice-warnings">
					<?php foreach ( $notice['warnings'] as $warn ) : ?>
						<li><?php echo esc_html( $warn ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper wpcllm-nav-tabs" aria-label="<?php esc_attr_e( 'LLMs.txt Manager Navigation', 'wpcalibrate-llms-txt-manager' ); ?>">
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'dashboard', $base_url ) ); ?>" class="nav-tab <?php echo 'dashboard' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Dashboard', 'wpcalibrate-llms-txt-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'builder', $base_url ) ); ?>" class="nav-tab <?php echo 'builder' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-layout"></span>
			<?php esc_html_e( 'Structured Builder', 'wpcalibrate-llms-txt-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'raw_editor', $base_url ) ); ?>" class="nav-tab <?php echo 'raw_editor' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Raw Markdown', 'wpcalibrate-llms-txt-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'import', $base_url ) ); ?>" class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-upload"></span>
			<?php esc_html_e( 'Import & Export', 'wpcalibrate-llms-txt-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings', $base_url ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Settings', 'wpcalibrate-llms-txt-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'status', $base_url ) ); ?>" class="nav-tab <?php echo 'status' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-info"></span>
			<?php esc_html_e( 'Status & Diagnostics', 'wpcalibrate-llms-txt-manager' ); ?>
		</a>
	</nav>

	<!-- Main Tab Content Area -->
	<main class="wpcllm-tab-content" id="wpcllm-tab-content">
		<?php
		switch ( $active_tab ) {
			case 'builder':
				include WPCLLM_PLUGIN_DIR . 'admin/views/builder.php';
				break;
			case 'raw_editor':
				include WPCLLM_PLUGIN_DIR . 'admin/views/raw-editor.php';
				break;
			case 'import':
				include WPCLLM_PLUGIN_DIR . 'admin/views/import.php';
				break;
			case 'settings':
				include WPCLLM_PLUGIN_DIR . 'admin/views/settings.php';
				break;
			case 'status':
				include WPCLLM_PLUGIN_DIR . 'admin/views/status.php';
				break;
			case 'dashboard':
			default:
				include WPCLLM_PLUGIN_DIR . 'admin/views/dashboard.php';
				break;
		}
		?>
	</main>

	<!-- Content Picker Modal -->
	<?php include WPCLLM_PLUGIN_DIR . 'admin/views/modal-content-picker.php'; ?>

	<!-- Preview Modal -->
	<div id="wpcllm-preview-modal" class="wpcllm-modal" style="display:none;" aria-hidden="true" role="dialog" aria-labelledby="wpcllm-preview-modal-title">
		<div class="wpcllm-modal-backdrop"></div>
		<div class="wpcllm-modal-dialog">
			<div class="wpcllm-modal-header">
				<h2 id="wpcllm-preview-modal-title"><?php esc_html_e( 'Public /llms.txt Plain-Text Preview', 'wpcalibrate-llms-txt-manager' ); ?></h2>
				<button type="button" class="wpcllm-modal-close" aria-label="<?php esc_attr_e( 'Close preview', 'wpcalibrate-llms-txt-manager' ); ?>">&times;</button>
			</div>
			<div class="wpcllm-modal-body">
				<div class="wpcllm-preview-stats" id="wpcllm-preview-stats"></div>
				<pre class="wpcllm-preview-code" id="wpcllm-preview-code"></pre>
			</div>
			<div class="wpcllm-modal-footer">
				<button type="button" class="button button-secondary wpcllm-modal-close"><?php esc_html_e( 'Close', 'wpcalibrate-llms-txt-manager' ); ?></button>
				<button type="button" class="button button-primary wpcllm-copy-preview"><?php esc_html_e( 'Copy to Clipboard', 'wpcalibrate-llms-txt-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>
