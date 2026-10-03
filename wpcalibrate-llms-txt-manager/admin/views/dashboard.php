<?php
/**
 * Dashboard tab view.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin\Views
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin\Views;

use WPCalibrate\LlmsTxtManager\Admin\Admin;
use WPCalibrate\LlmsTxtManager\Admin\Menu;
use WPCalibrate\LlmsTxtManager\Includes\Validator;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array<string, mixed> $settings
 * @var array<string, mixed>|null $published
 * @var array<string, mixed> $draft
 * @var array{exists: bool, path: string, size?: int, readable?: bool, writable?: bool, modified_at?: int} $phys_info
 * @var array{type: string, message: string, errors?: list<string>, warnings?: list<string>}|null $notice
 * @var string $active_tab
 */
$settings  = $settings ?? [];
$published = $published ?? null;
$draft     = $draft ?? [];
$phys_info = $phys_info ?? [ 'exists' => false, 'path' => '' ];

$is_enabled   = ! empty( $settings['enabled'] );
$is_published = ! empty( $published ) && ! empty( $published['raw_markdown'] );
$draft_md     = $draft['raw_markdown'] ?? '';
$validator    = new Validator();
$val_res      = $validator->validate( $draft_md );

$has_unsaved_changes = false;
if ( $is_published ) {
	$has_unsaved_changes = ( $draft['hash'] ?? '' ) !== ( $published['hash'] ?? '' );
}

$base_url = Menu::get_admin_url();
?>

<div class="wpcllm-dashboard">
	<!-- Physical File Warning Banner if detected -->
	<?php if ( ! empty( $phys_info['exists'] ) ) : ?>
		<div class="wpcllm-card wpcllm-alert-card wpcllm-alert-warning">
			<div class="wpcllm-alert-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="wpcllm-alert-content">
				<h3><?php esc_html_e( 'Physical llms.txt File Detected in Web Root', 'wpcalibrate-llms-txt-manager' ); ?></h3>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: file path */
							__( 'A physical file was detected at %s. Web servers (Nginx, Apache, LiteSpeed, Cloudflare/CDN) typically serve static files directly from disk before WordPress is executed. This means your physical file may take precedence over this plugin\'s virtual endpoint.', 'wpcalibrate-llms-txt-manager' )
							,
							$phys_info['path']
						)
					);
					?>
				</p>
				<div class="wpcllm-alert-actions">
					<form method="post" class="wpcllm-inline-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'dashboard' ] ) ); ?>">
						<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
						<input type="hidden" name="wpcllm_action" value="import_physical" />
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Import Existing Physical File into Draft', 'wpcalibrate-llms-txt-manager' ); ?>
						</button>
					</form>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'import', $base_url ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Review Import & Conflict Options', 'wpcalibrate-llms-txt-manager' ); ?>
					</a>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Metrics & Status Cards Grid -->
	<div class="wpcllm-grid wpcllm-grid-3">
		<!-- Endpoint Serving Status -->
		<div class="wpcllm-card wpcllm-stat-card">
			<div class="wpcllm-stat-header">
				<span class="wpcllm-stat-title"><?php esc_html_e( 'Virtual Endpoint', 'wpcalibrate-llms-txt-manager' ); ?></span>
				<span class="wpcllm-badge <?php echo $is_enabled ? 'wpcllm-badge-success' : 'wpcllm-badge-neutral'; ?>">
					<?php echo $is_enabled ? esc_html__( 'Active', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Disabled', 'wpcalibrate-llms-txt-manager' ); ?>
				</span>
			</div>
			<div class="wpcllm-stat-value">
				<code>/llms.txt</code>
			</div>
			<p class="wpcllm-stat-desc">
				<?php if ( $is_enabled ) : ?>
					<?php esc_html_e( 'Virtually serving published Markdown natively through WordPress rewrite routing.', 'wpcalibrate-llms-txt-manager' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Endpoint is disabled in Settings. Requests will fall through to standard 404.', 'wpcalibrate-llms-txt-manager' ); ?>
				<?php endif; ?>
			</p>
			<div class="wpcllm-stat-footer">
				<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener noreferrer" class="wpcllm-link-external">
					<?php esc_html_e( 'Open Endpoint', 'wpcalibrate-llms-txt-manager' ); ?> &rarr;
				</a>
			</div>
		</div>

		<!-- Publication Lifecycle Status -->
		<div class="wpcllm-card wpcllm-stat-card">
			<div class="wpcllm-stat-header">
				<span class="wpcllm-stat-title"><?php esc_html_e( 'Publication State', 'wpcalibrate-llms-txt-manager' ); ?></span>
				<?php if ( ! $is_published ) : ?>
					<span class="wpcllm-badge wpcllm-badge-warning"><?php esc_html_e( 'Not Published', 'wpcalibrate-llms-txt-manager' ); ?></span>
				<?php elseif ( $has_unsaved_changes ) : ?>
					<span class="wpcllm-badge wpcllm-badge-info"><?php esc_html_e( 'Draft Modified', 'wpcalibrate-llms-txt-manager' ); ?></span>
				<?php else : ?>
					<span class="wpcllm-badge wpcllm-badge-success"><?php esc_html_e( 'Live / Up to date', 'wpcalibrate-llms-txt-manager' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="wpcllm-stat-value">
				<?php if ( $is_published ) : ?>
					<?php
					$pub_time = (int) ( $published['published_at'] ?? 0 );
					echo esc_html( human_time_diff( $pub_time, time() ) . ' ' . __( 'ago', 'wpcalibrate-llms-txt-manager' ) );
					?>
				<?php else : ?>
					<?php esc_html_e( 'Pending Publication', 'wpcalibrate-llms-txt-manager' ); ?>
				<?php endif; ?>
			</div>
			<p class="wpcllm-stat-desc">
				<?php if ( $has_unsaved_changes ) : ?>
					<?php esc_html_e( 'You have unpublished changes in your draft that are not yet visible at /llms.txt.', 'wpcalibrate-llms-txt-manager' ); ?>
				<?php elseif ( $is_published ) : ?>
					<?php esc_html_e( 'Public endpoint is serving the currently approved published revision.', 'wpcalibrate-llms-txt-manager' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Draft has not been published yet. Save and publish to activate public serving.', 'wpcalibrate-llms-txt-manager' ); ?>
				<?php endif; ?>
			</p>
			<div class="wpcllm-stat-footer">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'builder', $base_url ) ); ?>">
					<?php esc_html_e( 'Edit in Builder', 'wpcalibrate-llms-txt-manager' ); ?> &rarr;
				</a>
			</div>
		</div>

		<!-- Validation & Health Status -->
		<div class="wpcllm-card wpcllm-stat-card">
			<div class="wpcllm-stat-header">
				<span class="wpcllm-stat-title"><?php esc_html_e( 'Draft Validation', 'wpcalibrate-llms-txt-manager' ); ?></span>
				<span class="wpcllm-badge <?php echo $val_res['valid'] ? 'wpcllm-badge-success' : 'wpcllm-badge-danger'; ?>">
					<?php echo $val_res['valid'] ? esc_html__( 'Compliant', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Validation Errors', 'wpcalibrate-llms-txt-manager' ); ?>
				</span>
			</div>
			<div class="wpcllm-stat-value">
				<?php
				printf(
					/* translators: 1: section count, 2: resource count */
					esc_html__( '%1$d Sections, %2$d Links', 'wpcalibrate-llms-txt-manager' ),
					(int) $val_res['stats']['h2_count'],
					(int) $val_res['stats']['resource_count']
				);
				?>
			</div>
			<p class="wpcllm-stat-desc">
				<?php
				printf(
					/* translators: 1: byte size, 2: char count */
					esc_html__( 'Size: %1$s KB (%2$d characters). Adheres to llms.txt v2 specification.', 'wpcalibrate-llms-txt-manager' ),
					number_format( $val_res['stats']['bytes'] / 1024, 2 ),
					(int) $val_res['stats']['chars']
				);
				?>
			</p>
			<div class="wpcllm-stat-footer">
				<button type="button" class="button-link wpcllm-preview-trigger" data-source="draft">
					<?php esc_html_e( 'Inspect Full Preview', 'wpcalibrate-llms-txt-manager' ); ?> &rarr;
				</button>
			</div>
		</div>
	</div>

	<!-- Quick Actions Row -->
	<div class="wpcllm-card wpcllm-section-card">
		<h2><?php esc_html_e( 'Quick Actions', 'wpcalibrate-llms-txt-manager' ); ?></h2>
		<div class="wpcllm-quick-actions">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'builder', $base_url ) ); ?>" class="wpcllm-action-tile">
				<span class="dashicons dashicons-layout"></span>
				<strong><?php esc_html_e( 'Structured Builder', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<span><?php esc_html_e( 'Manage sections, pages, and resource links visually.', 'wpcalibrate-llms-txt-manager' ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'raw_editor', $base_url ) ); ?>" class="wpcllm-action-tile">
				<span class="dashicons dashicons-editor-code"></span>
				<strong><?php esc_html_e( 'Raw Markdown Editor', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<span><?php esc_html_e( 'Direct plain-text editing with syntax validation.', 'wpcalibrate-llms-txt-manager' ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'import', $base_url ) ); ?>" class="wpcllm-action-tile">
				<span class="dashicons dashicons-upload"></span>
				<strong><?php esc_html_e( 'Import or Upload', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<span><?php esc_html_e( 'Upload an existing .txt file or import from disk.', 'wpcalibrate-llms-txt-manager' ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'status', $base_url ) ); ?>" class="wpcllm-action-tile">
				<span class="dashicons dashicons-admin-generic"></span>
				<strong><?php esc_html_e( 'Self-Check & Diagnostics', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<span><?php esc_html_e( 'Verify HTTP loopback delivery and view system stats.', 'wpcalibrate-llms-txt-manager' ); ?></span>
			</a>
		</div>
	</div>
</div>
