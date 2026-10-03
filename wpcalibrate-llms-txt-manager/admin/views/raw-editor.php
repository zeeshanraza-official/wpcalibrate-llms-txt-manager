<?php
/**
 * Raw Markdown Editor tab view.
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
 * @var array<string, mixed> $draft
 */
$draft = $draft ?? [];

$raw_markdown = $draft['raw_markdown'] ?? '';
?>

<form method="post" id="wpcllm-raw-form" class="wpcllm-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'raw_editor' ] ) ); ?>">
	<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
	<input type="hidden" name="wpcllm_action" id="wpcllm_raw_action" value="raw_save_draft" />

	<!-- Sticky Action Bar -->
	<div class="wpcllm-action-bar">
		<div class="wpcllm-action-bar-left">
			<h2><?php esc_html_e( 'Raw Markdown Editor', 'wpcalibrate-llms-txt-manager' ); ?></h2>
			<span class="description"><?php esc_html_e( 'Directly author or paste raw Markdown adhering to the llms.txt v2 standard.', 'wpcalibrate-llms-txt-manager' ); ?></span>
		</div>
		<div class="wpcllm-action-bar-right">
			<button type="button" class="button button-secondary" id="wpcllm-validate-raw-btn">
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( 'Validate Syntax', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
			<button type="submit" class="button button-secondary wpcllm-submit-raw" data-action="raw_save_draft">
				<?php esc_html_e( 'Save Draft', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
			<button type="submit" class="button button-primary wpcllm-submit-raw" data-action="raw_publish">
				<span class="dashicons dashicons-cloud-upload"></span>
				<?php esc_html_e( 'Publish Live', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
		</div>
	</div>

	<!-- Live Validation Feedback Card (Hidden until checked or on load) -->
	<div id="wpcllm-validation-feedback" class="wpcllm-card wpcllm-validation-card" style="display:none;">
		<div class="wpcllm-val-header">
			<h4><?php esc_html_e( 'Validation Results', 'wpcalibrate-llms-txt-manager' ); ?></h4>
			<span id="wpcllm-val-badge" class="wpcllm-badge"></span>
		</div>
		<div id="wpcllm-val-messages"></div>
		<div id="wpcllm-val-stats" class="wpcllm-val-stats"></div>
	</div>

	<!-- Editor Area -->
	<div class="wpcllm-card wpcllm-editor-card">
		<label for="wpcllm_raw_markdown" class="screen-reader-text">
			<?php esc_html_e( 'Raw Markdown Content', 'wpcalibrate-llms-txt-manager' ); ?>
		</label>
		<textarea name="raw_markdown" id="wpcllm_raw_markdown" rows="24" class="wpcllm-monospace-textarea" spellcheck="false"><?php echo esc_textarea( $raw_markdown ); ?></textarea>
	</div>

	<!-- Specification Quick Reference -->
	<div class="wpcllm-card wpcllm-guide-card">
		<h3><?php esc_html_e( 'llms.txt v2 Specification Reference', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<div class="wpcllm-guide-grid">
			<div class="wpcllm-guide-col">
				<code># Project Name</code>
				<p><?php esc_html_e( 'Required H1 heading. Exactly one top-level title is permitted.', 'wpcalibrate-llms-txt-manager' ); ?></p>

				<code>> Short Summary</code>
				<p><?php esc_html_e( 'Optional blockquote placed right beneath the title summarizing the project.', 'wpcalibrate-llms-txt-manager' ); ?></p>
			</div>
			<div class="wpcllm-guide-col">
				<code>## Section Title</code>
				<p><?php esc_html_e( 'H2 headings defining categorized resource groupings.', 'wpcalibrate-llms-txt-manager' ); ?></p>

				<code>- [Title](URL): Description</code>
				<p><?php esc_html_e( 'List items with absolute HTTP/HTTPS URLs and optional descriptions.', 'wpcalibrate-llms-txt-manager' ); ?></p>
			</div>
			<div class="wpcllm-guide-col">
				<code>## Optional</code>
				<p><?php esc_html_e( 'Standard section for secondary resources, licenses, or legal terms.', 'wpcalibrate-llms-txt-manager' ); ?></p>

				<code>UTF-8 Plain Text</code>
				<p><?php esc_html_e( 'Output is strictly plain-text Markdown without HTML tags or wrappers.', 'wpcalibrate-llms-txt-manager' ); ?></p>
			</div>
		</div>
	</div>
</form>
