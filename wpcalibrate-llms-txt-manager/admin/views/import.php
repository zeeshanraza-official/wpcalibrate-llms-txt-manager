<?php
/**
 * Import & Export tab view.
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
 * @var array<string, mixed> $settings
 * @var array{exists: bool, path: string, size?: int, readable?: bool, writable?: bool, modified_at?: int} $phys_info
 */
$settings  = $settings ?? [];
$phys_info = $phys_info ?? [ 'exists' => false, 'path' => '' ];

$max_kb = (int) ( $settings['max_import_size_kb'] ?? 256 );
?>

<div class="wpcllm-import-wrapper">
	<!-- Upload Text File Section -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'Upload Existing llms.txt File', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<p class="description">
			<?php
			printf(
				/* translators: %d: maximum file size in KB */
				esc_html__( 'Upload a plain-text .txt or .md file from your computer (maximum %d KB). The contents will be validated, parsed, and loaded into your draft editor.', 'wpcalibrate-llms-txt-manager' ),
				$max_kb
			);
			?>
		</p>

		<form method="post" enctype="multipart/form-data" class="wpcllm-form" id="wpcllm-upload-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'import' ] ) ); ?>">
			<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
			<input type="hidden" name="wpcllm_action" value="import_file" />

			<div class="wpcllm-upload-dropzone">
				<input type="file" name="llms_file" id="wpcllm_llms_file" accept=".txt,.md,text/plain" required />
				<label for="wpcllm_llms_file" class="wpcllm-dropzone-label">
					<span class="dashicons dashicons-upload wpcllm-dropzone-icon"></span>
					<span class="wpcllm-dropzone-text"><?php esc_html_e( 'Choose a .txt or .md file, or drag and drop it here', 'wpcalibrate-llms-txt-manager' ); ?></span>
					<span class="wpcllm-dropzone-hint"><?php esc_html_e( 'Strict UTF-8 text only. Any BOM headers will be cleanly stripped.', 'wpcalibrate-llms-txt-manager' ); ?></span>
				</label>
			</div>

			<div class="wpcllm-form-actions">
				<button type="submit" class="button button-primary" id="wpcllm-upload-submit">
					<span class="dashicons dashicons-cloud-upload"></span>
					<?php esc_html_e( 'Validate & Import into Draft', 'wpcalibrate-llms-txt-manager' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Physical File Management Card -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'Physical llms.txt File Detection', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Physical files residing on disk in your document root take priority over WordPress rewrite endpoints on most web servers.', 'wpcalibrate-llms-txt-manager' ); ?>
		</p>

		<?php if ( ! empty( $phys_info['exists'] ) ) : ?>
			<div class="wpcllm-callout wpcllm-callout-warning">
				<h4><?php esc_html_e( 'Physical File Present on Server', 'wpcalibrate-llms-txt-manager' ); ?></h4>
				<table class="wpcllm-diag-table">
					<tr>
						<th><?php esc_html_e( 'Location:', 'wpcalibrate-llms-txt-manager' ); ?></th>
						<td><code><?php echo esc_html( $phys_info['path'] ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'File Size:', 'wpcalibrate-llms-txt-manager' ); ?></th>
						<td><?php echo esc_html( number_format( $phys_info['size'] / 1024, 2 ) . ' KB' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Permissions:', 'wpcalibrate-llms-txt-manager' ); ?></th>
						<td>
							<?php echo $phys_info['readable'] ? esc_html__( 'Readable', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Unreadable', 'wpcalibrate-llms-txt-manager' ); ?> /
							<?php echo $phys_info['writable'] ? esc_html__( 'Writable', 'wpcalibrate-llms-txt-manager' ) : esc_html__( 'Read-only', 'wpcalibrate-llms-txt-manager' ); ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Last Modified:', 'wpcalibrate-llms-txt-manager' ); ?></th>
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', (int) $phys_info['modified_at'] ) . ' UTC' ); ?></td>
					</tr>
				</table>

				<div class="wpcllm-alert-actions" style="margin-top: 15px;">
					<!-- Action 1: Import -->
					<form method="post" class="wpcllm-inline-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'import' ] ) ); ?>">
						<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
						<input type="hidden" name="wpcllm_action" value="import_physical" />
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Import File Contents into Draft', 'wpcalibrate-llms-txt-manager' ); ?>
						</button>
					</form>

					<!-- Action 2: Leave Unchanged -->
					<span class="button button-secondary disabled" title="<?php esc_attr_e( 'No action needed to leave existing file intact.', 'wpcalibrate-llms-txt-manager' ); ?>">
						<?php esc_html_e( 'Leave Existing File Unchanged', 'wpcalibrate-llms-txt-manager' ); ?>
					</span>

					<!-- Action 3: Remove with Confirmation -->
					<?php if ( $phys_info['writable'] ) : ?>
						<form method="post" class="wpcllm-inline-form wpcllm-confirm-remove-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'import' ] ) ); ?>">
							<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
							<input type="hidden" name="wpcllm_action" value="remove_physical" />
							<button type="submit" class="button button-link-delete" id="wpcllm-remove-physical-btn">
								<span class="dashicons dashicons-trash"></span>
								<?php esc_html_e( 'Remove Physical File (Advanced)', 'wpcalibrate-llms-txt-manager' ); ?>
							</button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		<?php else : ?>
			<div class="wpcllm-callout wpcllm-callout-success">
				<p>
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'No conflicting physical llms.txt file was found in your site root. The WordPress virtual endpoint is ready to handle /llms.txt directly.', 'wpcalibrate-llms-txt-manager' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Export Section -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'Export Configurations', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Download current draft or published llms.txt files directly to your computer as plain text.', 'wpcalibrate-llms-txt-manager' ); ?>
		</p>
		<div class="wpcllm-export-buttons">
			<?php
			$export_draft_url = wp_nonce_url(
				Menu::get_admin_url( [ 'tab' => 'import', 'wpcllm_export' => 'draft' ] ),
				Admin::NONCE_ACTION
			);
			$export_pub_url = wp_nonce_url(
				Menu::get_admin_url( [ 'tab' => 'import', 'wpcllm_export' => 'published' ] ),
				Admin::NONCE_ACTION
			);
			?>
			<a href="<?php echo esc_url( $export_draft_url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Download Draft (.txt)', 'wpcalibrate-llms-txt-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( $export_pub_url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Download Published (.txt)', 'wpcalibrate-llms-txt-manager' ); ?>
			</a>
		</div>
	</div>
</div>
