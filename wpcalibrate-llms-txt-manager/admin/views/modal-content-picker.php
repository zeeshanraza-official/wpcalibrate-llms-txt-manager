<?php
/**
 * Content Picker modal template.
 *
 * @package WPCalibrate\LlmsTxtManager\Admin\Views
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Admin\Views;

use WPCalibrate\LlmsTxtManager\Includes\Content_Repository;

// Prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$repo           = new Content_Repository();
$eligible_types = $repo->get_eligible_post_types();
?>

<div id="wpcllm-content-picker-modal" class="wpcllm-modal" style="display:none;" aria-hidden="true" role="dialog" aria-labelledby="wpcllm-picker-title">
	<div class="wpcllm-modal-backdrop"></div>
	<div class="wpcllm-modal-dialog">
		<div class="wpcllm-modal-header">
			<h2 id="wpcllm-picker-title"><?php esc_html_e( 'Select Content from WordPress', 'wpcalibrate-llms-txt-manager' ); ?></h2>
			<button type="button" class="wpcllm-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'wpcalibrate-llms-txt-manager' ); ?>">&times;</button>
		</div>
		<div class="wpcllm-modal-body">
			<div class="wpcllm-picker-search-bar">
				<label for="wpcllm-picker-query" class="screen-reader-text"><?php esc_html_e( 'Search content', 'wpcalibrate-llms-txt-manager' ); ?></label>
				<input type="search" id="wpcllm-picker-query" class="regular-text wpcllm-input-wide" placeholder="<?php esc_attr_e( 'Search by title or keyword...', 'wpcalibrate-llms-txt-manager' ); ?>" autofocus />
				<select id="wpcllm-picker-post-type">
					<option value=""><?php esc_html_e( 'All Post Types', 'wpcalibrate-llms-txt-manager' ); ?></option>
					<?php foreach ( $eligible_types as $pt_name => $pt_label ) : ?>
						<option value="<?php echo esc_attr( $pt_name ); ?>"><?php echo esc_html( $pt_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div id="wpcllm-picker-loading" class="wpcllm-picker-loading" style="display:none;">
				<span class="spinner is-active"></span>
				<span><?php esc_html_e( 'Searching WordPress content...', 'wpcalibrate-llms-txt-manager' ); ?></span>
			</div>

			<div id="wpcllm-picker-results" class="wpcllm-picker-results-list" role="listbox">
				<!-- Dynamically populated via AJAX -->
			</div>
		</div>
		<div class="wpcllm-modal-footer">
			<button type="button" class="button button-secondary wpcllm-modal-close"><?php esc_html_e( 'Cancel', 'wpcalibrate-llms-txt-manager' ); ?></button>
		</div>
	</div>
</div>
