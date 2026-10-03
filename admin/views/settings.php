<?php
/**
 * Settings tab view.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin\Views
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin\Views;

use WPCalibrate\LlmsTxtManager\Admin\Admin;
use WPCalibrate\LlmsTxtManager\Admin\Menu;
use WPCalibrate\LlmsTxtManager\Includes\Content_Repository;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array<string, mixed> $settings
 */
$settings = $settings ?? [];

$repo           = new Content_Repository();
$eligible_types = $repo->get_eligible_post_types();
$selected_types = $settings['generation_post_types'] ?? [ 'page', 'post' ];
?>

<form method="post" id="wpcllm-settings-form" class="wpcllm-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'settings' ] ) ); ?>">
	<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
	<input type="hidden" name="wpcllm_action" value="save_settings" />

	<!-- Virtual Endpoint Serving -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'Virtual Endpoint Configuration', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="wpcllm_setting_enabled"><?php esc_html_e( 'Virtual Serving', 'wpcalibrate-llms-txt-manager' ); ?></label>
				</th>
				<td>
					<label class="wpcllm-toggle-label">
						<input type="checkbox" name="settings[enabled]" id="wpcllm_setting_enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
						<strong><?php esc_html_e( 'Enable public /llms.txt virtual endpoint', 'wpcalibrate-llms-txt-manager' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'When disabled, requests to /llms.txt are not served by this plugin and fall back to standard 404. Your saved configuration is completely preserved.', 'wpcalibrate-llms-txt-manager' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpcllm_setting_editor_mode"><?php esc_html_e( 'Default Editor Mode', 'wpcalibrate-llms-txt-manager' ); ?></label>
				</th>
				<td>
					<select name="settings[editor_mode]" id="wpcllm_setting_editor_mode">
						<option value="builder" <?php selected( $settings['editor_mode'] ?? 'builder', 'builder' ); ?>>
							<?php esc_html_e( 'Structured Builder (Recommended)', 'wpcalibrate-llms-txt-manager' ); ?>
						</option>
						<option value="raw" <?php selected( $settings['editor_mode'] ?? '', 'raw' ); ?>>
							<?php esc_html_e( 'Raw Markdown Editor', 'wpcalibrate-llms-txt-manager' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'Choose your preferred editing workspace.', 'wpcalibrate-llms-txt-manager' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<!-- Output Caching -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'Performance & Endpoint Caching', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="wpcllm_setting_cache"><?php esc_html_e( 'Response Caching', 'wpcalibrate-llms-txt-manager' ); ?></label>
				</th>
				<td>
					<label class="wpcllm-toggle-label">
						<input type="checkbox" name="settings[enable_cache]" id="wpcllm_setting_cache" value="1" <?php checked( ! empty( $settings['enable_cache'] ) ); ?> />
						<strong><?php esc_html_e( 'Cache published /llms.txt responses in WordPress transients', 'wpcalibrate-llms-txt-manager' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'Caches published output to minimize database queries. Automatically flushed whenever you publish, save, import, or toggle the endpoint.', 'wpcalibrate-llms-txt-manager' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpcllm_setting_cache_ttl"><?php esc_html_e( 'Cache Duration (Seconds)', 'wpcalibrate-llms-txt-manager' ); ?></label>
				</th>
				<td>
					<input type="number" name="settings[cache_ttl]" id="wpcllm_setting_cache_ttl" value="<?php echo esc_attr( (string) ( $settings['cache_ttl'] ?? 3600 ) ); ?>" min="60" max="604800" step="60" class="small-text" />
					<span class="description"><?php esc_html_e( 'Default: 3600 seconds (1 hour).', 'wpcalibrate-llms-txt-manager' ); ?></span>
				</td>
			</tr>
		</table>
	</div>

	<!-- Import & Auto-Generation Preferences -->
	<div class="wpcllm-card wpcllm-section-card">
		<h3><?php esc_html_e( 'Content Discovery & Import Limits', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="wpcllm_setting_max_size"><?php esc_html_e( 'Maximum Import Size (KB)', 'wpcalibrate-llms-txt-manager' ); ?></label>
				</th>
				<td>
					<input type="number" name="settings[max_import_size_kb]" id="wpcllm_setting_max_size" value="<?php echo esc_attr( (string) ( $settings['max_import_size_kb'] ?? 256 ) ); ?>" min="16" max="2048" class="small-text" />
					<p class="description"><?php esc_html_e( 'Ceiling for uploaded .txt/.md files (16 KB to 2048 KB). Default is 256 KB.', 'wpcalibrate-llms-txt-manager' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Eligible Post Types', 'wpcalibrate-llms-txt-manager' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Eligible Post Types', 'wpcalibrate-llms-txt-manager' ); ?></legend>
						<?php foreach ( $eligible_types as $pt_name => $pt_label ) : ?>
							<label style="display:inline-block; margin-right: 18px; margin-bottom: 6px;">
								<input type="checkbox" name="settings[generation_post_types][]" value="<?php echo esc_attr( $pt_name ); ?>" <?php checked( in_array( $pt_name, $selected_types, true ) ); ?> />
								<?php echo esc_html( $pt_label . ' (' . $pt_name . ')' ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'Post types that appear in the content picker and automatic suggestions generator.', 'wpcalibrate-llms-txt-manager' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<!-- Data Retention on Uninstall -->
	<div class="wpcllm-card wpcllm-section-card wpcllm-card-danger-zone">
		<h3><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Uninstall Cleanup', 'wpcalibrate-llms-txt-manager' ); ?></th>
				<td>
					<label class="wpcllm-toggle-label">
						<input type="checkbox" name="settings[delete_on_uninstall]" id="wpcllm_setting_delete_on_uninstall" value="1" <?php checked( ! empty( $settings['delete_on_uninstall'] ) ); ?> />
						<strong style="color: #b32d2e;"><?php esc_html_e( 'Delete all plugin settings, drafts, and published llms.txt configurations upon plugin deletion', 'wpcalibrate-llms-txt-manager' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'By default, WPCalibrate preserves your configuration so you never lose your curated work if the plugin is temporarily uninstalled or updated. Check this box only if you want to completely erase all plugin data when permanently uninstalling.', 'wpcalibrate-llms-txt-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<div class="wpcllm-form-actions">
		<button type="submit" class="button button-primary button-large">
			<?php esc_html_e( 'Save Settings', 'wpcalibrate-llms-txt-manager' ); ?>
		</button>
	</div>
</form>
