<?php
/**
 * Structured Builder tab view.
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

$title           = $draft['title'] ?? get_bloginfo( 'name' );
$summary         = $draft['summary'] ?? get_bloginfo( 'description' );
$additional_info = $draft['additional_info'] ?? '';
$sections        = $draft['sections'] ?? [];
?>

<form method="post" id="wpcllm-builder-form" class="wpcllm-form" action="<?php echo esc_url( Menu::get_admin_url( [ 'tab' => 'builder' ] ) ); ?>">
	<?php wp_nonce_field( Admin::NONCE_ACTION, Admin::NONCE_NAME ); ?>
	<input type="hidden" name="wpcllm_action" id="wpcllm_builder_action" value="builder_save_draft" />

	<!-- Sticky Action Bar -->
	<div class="wpcllm-action-bar">
		<div class="wpcllm-action-bar-left">
			<h2><?php esc_html_e( 'Structured llms.txt Builder', 'wpcalibrate-llms-txt-manager' ); ?></h2>
			<span class="description"><?php esc_html_e( 'Assemble your llms.txt v2 document using modular, structured fields.', 'wpcalibrate-llms-txt-manager' ); ?></span>
		</div>
		<div class="wpcllm-action-bar-right">
			<button type="button" class="button wpcllm-btn-outline" id="wpcllm-generate-suggestions-btn">
				<span class="dashicons dashicons-superhero-alt"></span>
				<?php esc_html_e( 'Generate Suggestions', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
			<button type="submit" class="button button-secondary wpcllm-submit-action" data-action="builder_save_draft">
				<?php esc_html_e( 'Save Draft', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
			<button type="submit" class="button button-primary wpcllm-submit-action" data-action="builder_publish">
				<span class="dashicons dashicons-cloud-upload"></span>
				<?php esc_html_e( 'Publish Live', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
			<button type="button" class="button button-link-delete" id="wpcllm-reset-draft-btn">
				<?php esc_html_e( 'Reset Draft', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
		</div>
	</div>

	<!-- Primary Metadata Card -->
	<div class="wpcllm-card wpcllm-meta-card">
		<h3><?php esc_html_e( 'Primary Site Information', 'wpcalibrate-llms-txt-manager' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'The H1 heading is the required primary element of the llms.txt standard.', 'wpcalibrate-llms-txt-manager' ); ?>
		</p>

		<div class="wpcllm-form-group">
			<label for="wpcllm_site_title">
				<strong><?php esc_html_e( 'Site / Project Name (H1 Heading)', 'wpcalibrate-llms-txt-manager' ); ?></strong>
				<span class="wpcllm-required">*</span>
			</label>
			<input type="text" name="title" id="wpcllm_site_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text wpcllm-input-wide" required />
			<p class="description"><?php esc_html_e( 'Prepopulated from your WordPress site title. Appears as "# Project Name" at the very top.', 'wpcalibrate-llms-txt-manager' ); ?></p>
		</div>

		<div class="wpcllm-form-group">
			<label for="wpcllm_site_summary">
				<strong><?php esc_html_e( 'Short Summary (Blockquote)', 'wpcalibrate-llms-txt-manager' ); ?></strong>
			</label>
			<textarea name="summary" id="wpcllm_site_summary" rows="2" class="large-text"><?php echo esc_textarea( $summary ); ?></textarea>
			<p class="description"><?php esc_html_e( 'A concise summary of the project or organization. Rendered as a Markdown blockquote ("> Summary").', 'wpcalibrate-llms-txt-manager' ); ?></p>
		</div>

		<div class="wpcllm-form-group">
			<label for="wpcllm_additional_info">
				<strong><?php esc_html_e( 'Additional Project Notes (Optional Markdown)', 'wpcalibrate-llms-txt-manager' ); ?></strong>
			</label>
			<textarea name="additional_info" id="wpcllm_additional_info" rows="3" class="large-text"><?php echo esc_textarea( $additional_info ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Descriptive context placed between the summary blockquote and the first resource section.', 'wpcalibrate-llms-txt-manager' ); ?></p>
		</div>
	</div>

	<!-- Repeatable Sections Container -->
	<div class="wpcllm-sections-wrapper">
		<div class="wpcllm-sections-header">
			<h3><?php esc_html_e( 'Resource Sections (H2)', 'wpcalibrate-llms-txt-manager' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Curated collections of links categorized into distinct sections (e.g., Core Documentation, Guides, Optional).', 'wpcalibrate-llms-txt-manager' ); ?>
			</p>
		</div>

		<div id="wpcllm-sections-list">
			<?php
			if ( ! empty( $sections ) && is_array( $sections ) ) :
				foreach ( $sections as $s_idx => $sec ) :
					$s_title     = $sec['title'] ?? '';
					$s_resources = $sec['resources'] ?? [];
					?>
					<div class="wpcllm-card wpcllm-section-item" data-section-index="<?php echo esc_attr( (string) $s_idx ); ?>">
						<div class="wpcllm-section-item-header">
							<div class="wpcllm-section-title-wrap">
								<span class="dashicons dashicons-menu wpcllm-drag-handle" title="<?php esc_attr_e( 'Reorder Section', 'wpcalibrate-llms-txt-manager' ); ?>"></span>
								<label for="wpcllm_sec_title_<?php echo esc_attr( (string) $s_idx ); ?>" class="screen-reader-text">
									<?php esc_html_e( 'Section Title', 'wpcalibrate-llms-txt-manager' ); ?>
								</label>
								<span class="wpcllm-h2-prefix">##</span>
								<input type="text" id="wpcllm_sec_title_<?php echo esc_attr( (string) $s_idx ); ?>" name="sections[<?php echo esc_attr( (string) $s_idx ); ?>][title]" value="<?php echo esc_attr( $s_title ); ?>" class="wpcllm-section-title-input" placeholder="<?php esc_attr_e( 'Section Title (e.g. Main Documentation)', 'wpcalibrate-llms-txt-manager' ); ?>" required />
							</div>
							<div class="wpcllm-section-item-actions">
								<button type="button" class="button button-link-delete wpcllm-remove-section-btn" aria-label="<?php esc_attr_e( 'Remove this section', 'wpcalibrate-llms-txt-manager' ); ?>">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Delete Section', 'wpcalibrate-llms-txt-manager' ); ?>
								</button>
							</div>
						</div>

						<div class="wpcllm-resources-list">
							<?php
							if ( ! empty( $s_resources ) && is_array( $s_resources ) ) :
								foreach ( $s_resources as $r_idx => $res ) :
									$r_title   = $res['title'] ?? '';
									$r_url     = $res['url'] ?? '';
									$r_desc    = $res['description'] ?? '';
									$r_post_id = $res['post_id'] ?? 0;
									?>
									<div class="wpcllm-resource-row" data-resource-index="<?php echo esc_attr( (string) $r_idx ); ?>">
										<span class="dashicons dashicons-arrow-right-alt wpcllm-resource-bullet"></span>
										<div class="wpcllm-resource-inputs">
											<div class="wpcllm-resource-row-top">
												<input type="text" name="sections[<?php echo esc_attr( (string) $s_idx ); ?>][resources][<?php echo esc_attr( (string) $r_idx ); ?>][title]" value="<?php echo esc_attr( $r_title ); ?>" class="wpcllm-res-title" placeholder="<?php esc_attr_e( 'Link Title', 'wpcalibrate-llms-txt-manager' ); ?>" required />
												<input type="url" name="sections[<?php echo esc_attr( (string) $s_idx ); ?>][resources][<?php echo esc_attr( (string) $r_idx ); ?>][url]" value="<?php echo esc_url( $r_url ); ?>" class="wpcllm-res-url" placeholder="https://..." required />
												<input type="hidden" name="sections[<?php echo esc_attr( (string) $s_idx ); ?>][resources][<?php echo esc_attr( (string) $r_idx ); ?>][post_id]" value="<?php echo esc_attr( (string) $r_post_id ); ?>" class="wpcllm-res-post-id" />
												<button type="button" class="button wpcllm-btn-pick-post" title="<?php esc_attr_e( 'Insert page or post from WordPress', 'wpcalibrate-llms-txt-manager' ); ?>">
													<span class="dashicons dashicons-search"></span>
													<?php esc_html_e( 'Search Site', 'wpcalibrate-llms-txt-manager' ); ?>
												</button>
												<button type="button" class="button-link-delete wpcllm-remove-resource-btn" title="<?php esc_attr_e( 'Remove resource', 'wpcalibrate-llms-txt-manager' ); ?>" aria-label="<?php esc_attr_e( 'Remove resource', 'wpcalibrate-llms-txt-manager' ); ?>">
													<span class="dashicons dashicons-no-alt"></span>
												</button>
											</div>
											<div class="wpcllm-resource-row-bottom">
												<input type="text" name="sections[<?php echo esc_attr( (string) $s_idx ); ?>][resources][<?php echo esc_attr( (string) $r_idx ); ?>][description]" value="<?php echo esc_attr( $r_desc ); ?>" class="wpcllm-res-desc" placeholder="<?php esc_attr_e( 'Optional description for LLMs...', 'wpcalibrate-llms-txt-manager' ); ?>" />
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<div class="wpcllm-section-footer">
							<button type="button" class="button button-secondary wpcllm-add-resource-btn">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php esc_html_e( 'Add Resource Link', 'wpcalibrate-llms-txt-manager' ); ?>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<div class="wpcllm-add-section-wrap">
			<button type="button" class="button button-secondary button-hero" id="wpcllm-add-section-btn">
				<span class="dashicons dashicons-plus-alt"></span>
				<?php esc_html_e( 'Add New Resource Section', 'wpcalibrate-llms-txt-manager' ); ?>
			</button>
		</div>
	</div>
</form>
