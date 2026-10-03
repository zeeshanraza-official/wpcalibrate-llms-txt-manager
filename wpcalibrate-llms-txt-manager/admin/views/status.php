<?php
/**
 * Status, Diagnostics, and Support tab view.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin\Views
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin\Views;

use WPCalibrate\LlmsTxtManager\Admin\Status;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_ctrl   = new Status();
$diagnostics   = $status_ctrl->get_diagnostics();
$cached_check  = $status_ctrl->get_cached_self_check();
$system_report = $status_ctrl->generate_support_report();
?>

<div class="wpcllm-status-wrapper">
	<!-- Public File Self-Check Tool -->
	<div class="wpcllm-card wpcllm-section-card">
		<div class="wpcllm-section-header-split">
			<div>
				<h3><?php esc_html_e( 'Public Endpoint Self-Check', 'wpcalibrate-llms-txt-manager' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Performs an internal HTTP loopback request to your public /llms.txt URL to verify what external web clients and LLM scrapers actually receive.', 'wpcalibrate-llms-txt-manager' ); ?>
				</p>
			</div>
			<div>
				<button type="button" class="button button-primary" id="wpcllm-run-selfcheck-btn">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Test Public Endpoint Now', 'wpcalibrate-llms-txt-manager' ); ?>
				</button>
			</div>
		</div>

		<div id="wpcllm-selfcheck-result" class="wpcllm-selfcheck-box" style="<?php echo $cached_check ? '' : 'display:none;'; ?>">
			<?php if ( $cached_check ) : ?>
				<div class="wpcllm-callout <?php echo $cached_check['success'] ? 'wpcllm-callout-success' : 'wpcllm-callout-warning'; ?>">
					<h4>
						<?php echo $cached_check['success'] ? esc_html__( 'Verification Succeeded', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Verification Notice', 'wpcalibrate-llms-txt-manager' ); ?>
					</h4>
					<p><?php echo esc_html( $cached_check['message'] ); ?></p>
					<ul class="wpcllm-inline-list">
						<?php if ( null !== $cached_check['status_code'] ) : ?>
							<li><strong><?php esc_html_e( 'HTTP Status:', 'wpcalibrate-llms-txt-manager' ); ?></strong> <?php echo esc_html( (string) $cached_check['status_code'] ); ?></li>
						<?php endif; ?>
						<?php if ( ! empty( $cached_check['content_type'] ) ) : ?>
							<li><strong><?php esc_html_e( 'Content-Type:', 'wpcalibrate-llms-txt-manager' ); ?></strong> <?php echo esc_html( $cached_check['content_type'] ); ?></li>
						<?php endif; ?>
						<li><strong><?php esc_html_e( 'Matches Published Hash:', 'wpcalibrate-llms-txt-manager' ); ?></strong> <?php echo $cached_check['matches_published'] ? esc_html__( 'Yes', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'No', 'wpcalibrate-llms-txt-manager' ); ?></li>
						<li><strong><?php esc_html_e( 'Last Checked:', 'wpcalibrate-llms-txt-manager' ); ?></strong> <?php echo esc_html( human_time_diff( (int) $cached_check['checked_at'], time() ) . ' ' . __( 'ago', 'wpcalibrate-llms-txt-manager' ) ); ?></li>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- System Diagnostics Table -->
	<div class="wpcllm-card wpcllm-section-card">
		<div class="wpcllm-section-header-split">
			<h3><?php esc_html_e( 'System Environment & Diagnostics', 'wpcalibrate-llms-txt-manager' ); ?></h3>
			<button type="button" class="button button-secondary" id="wpcllm-copy-report-btn" data-report="<?php echo esc_attr( $system_report ); ?>">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy Diagnostic Report', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
		</div>

		<table class="widefat striped wpcllm-table" role="presentation">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Plugin Version', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><?php echo esc_html( $diagnostics['plugin_version'] ); ?> (Schema v<?php echo esc_html( $diagnostics['schema_version'] ); ?>)</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'WordPress Version', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><?php echo esc_html( $diagnostics['wp_version'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'PHP Version', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><?php echo esc_html( $diagnostics['php_version'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Web Server Software', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><?php echo esc_html( $diagnostics['server_software'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Home URL', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><code><?php echo esc_html( $diagnostics['home_url'] ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Public Endpoint URL', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td>
						<a href="<?php echo esc_url( $diagnostics['public_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $diagnostics['public_url'] ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Permalink Structure', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><code><?php echo esc_html( $diagnostics['permalink_structure'] ); ?></code></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Rewrite Rules Loaded', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td><?php echo $diagnostics['rewrite_rules_loaded'] ? esc_html__( 'Yes', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'No', 'wpcalibrate-llms-txt-manager' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Transient Output Caching', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td>
						<?php echo $diagnostics['cache_enabled'] ? esc_html__( 'Enabled', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Disabled', 'wpcalibrate-llms-txt-manager' ); ?>
						<?php if ( $diagnostics['cache_enabled'] ) : ?>
							(<?php echo $diagnostics['is_cached'] ? esc_html__( 'Cache warm', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Cache empty', 'wpcalibrate-llms-txt-manager' ); ?>)
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Physical llms.txt Detected', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td>
						<?php if ( $diagnostics['physical_file_exists'] ) : ?>
							<span style="color:#b32d2e; font-weight:bold;"><?php esc_html_e( 'Yes - File exists at:', 'wpcalibrate-llms-txt-manager' ); ?></span>
							<code><?php echo esc_html( $diagnostics['physical_file_path'] ); ?></code>
						<?php else : ?>
							<?php esc_html_e( 'No physical file present (Virtual serving active)', 'wpcalibrate-llms-txt-manager' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'License Information', 'wpcalibrate-llms-txt-manager' ); ?></th>
					<td>
						<span class="wpcllm-badge wpcllm-badge-success"><?php echo esc_html( $diagnostics['license_type'] ); ?></span>
						<span class="description" style="margin-left:8px;"><?php echo esc_html( $diagnostics['license_activation'] ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- WPCalibrate Support & Contact Info Card -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'WPCalibrate Support & Contact', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Developed by WPCalibrate. Need technical assistance, bug reports, or enterprise customization? Reach out directly to our engineering team.', 'wpcalibrate-llms-txt-manager' ); ?>
		</p>

		<div class="wpcllm-contact-grid">
			<div class="wpcllm-contact-item">
				<span class="dashicons dashicons-email"></span>
				<strong><?php esc_html_e( 'Email Support:', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a>
			</div>
			<div class="wpcllm-contact-item">
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<strong><?php esc_html_e( 'Official Website:', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a>
			</div>
			<div class="wpcllm-contact-item">
				<span class="dashicons dashicons-store"></span>
				<strong><?php esc_html_e( 'Marketplace & Plugins:', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">https://marketplace.wpcalibrate.com/</a>
			</div>
			<div class="wpcllm-contact-item">
				<span class="dashicons dashicons-phone"></span>
				<strong><?php esc_html_e( 'Phone / WhatsApp:', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer">+447474795976</a>
			</div>
		</div>
	</div>
</div>
