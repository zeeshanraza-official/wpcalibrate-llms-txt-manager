/**
 * WPCalibrate LLMs.txt Manager Admin Interactions
 */

(function ($) {
	'use strict';

	var config = window.wpcllmConfig || {};
	config.i18n = $.extend({
		confirmReset: 'Are you sure you want to reset your draft? Any unsaved edits will be replaced with starting defaults.',
		confirmRemovePhysical: 'Are you sure you want to remove the physical llms.txt file from your server? A backup will be saved.',
		confirmImportOverwrite: 'Importing this file will replace your current draft content. Proceed?',
		confirmSwitchRaw: 'Switching to Raw Markdown mode will generate Markdown from your structured sections. Any custom raw formatting will take priority. Continue?',
		confirmSwitchBuilder: 'Switching to Builder mode will parse your Raw Markdown. Any non-standard markdown may be normalized. Continue?',
		copied: 'Copied to clipboard!',
		searching: 'Searching WordPress content...',
		noResults: 'No public content matching your query was found.',
		runningCheck: 'Testing public endpoint...',
		addSection: 'New Section',
		addResource: 'New Resource'
	}, config.i18n || {});

	var activePickerTargetRow = null;
	var searchTimer = null;

	$(document).ready(function () {
		initBuilderActions();
		initRawEditorActions();
		initContentPicker();
		initPreviewModal();
		initSelfCheck();
		initConfirmations();
		initCopyButtons();
	});

	/**
	 * Structured Builder Dynamic Controls
	 */
	function initBuilderActions() {
		var $sectionsList = $('#wpcllm-sections-list');

		// Handle Save Draft vs Publish button clicks
		$(document).on('click', '.wpcllm-submit-action', function (e) {
			var action = $(this).data('action');
			$('#wpcllm_builder_action').val(action);
		});

		// Add new Section
		$('#wpcllm-add-section-btn').on('click', function (e) {
			e.preventDefault();
			var sectionIndex = new Date().getTime();
			var sectionHtml =
				'<div class="wpcllm-card wpcllm-section-item" data-section-index="' + sectionIndex + '">' +
					'<div class="wpcllm-section-item-header">' +
						'<div class="wpcllm-section-title-wrap">' +
							'<span class="dashicons dashicons-menu wpcllm-drag-handle"></span>' +
							'<span class="wpcllm-h2-prefix">##</span>' +
							'<input type="text" name="sections[' + sectionIndex + '][title]" value="" class="wpcllm-section-title-input" placeholder="' + config.i18n.addSection + '" required />' +
						'</div>' +
						'<div class="wpcllm-section-item-actions">' +
							'<button type="button" class="button button-link-delete wpcllm-remove-section-btn">' +
								'<span class="dashicons dashicons-trash"></span> ' + config.i18n.addSection +
							'</button>' +
						'</div>' +
					'</div>' +
					'<div class="wpcllm-resources-list"></div>' +
					'<div class="wpcllm-section-footer">' +
						'<button type="button" class="button button-secondary wpcllm-add-resource-btn">' +
							'<span class="dashicons dashicons-plus-alt2"></span> ' + config.i18n.addResource +
						'</button>' +
					'</div>' +
				'</div>';

			$sectionsList.append(sectionHtml);
			$sectionsList.find('.wpcllm-section-item:last .wpcllm-section-title-input').focus();
		});

		// Remove Section
		$(document).on('click', '.wpcllm-remove-section-btn', function (e) {
			e.preventDefault();
			var $item = $(this).closest('.wpcllm-section-item');
			var resCount = $item.find('.wpcllm-resource-row').length;
			if (resCount > 0 && !confirm(config.i18n.confirmReset)) {
				return;
			}
			$item.fadeOut(200, function () {
				$(this).remove();
			});
		});

		// Add Resource within a Section
		$(document).on('click', '.wpcllm-add-resource-btn', function (e) {
			e.preventDefault();
			var $section = $(this).closest('.wpcllm-section-item');
			var secIndex = $section.data('section-index');
			var resIndex = new Date().getTime();
			var $resourcesList = $section.find('.wpcllm-resources-list');

			var resHtml =
				'<div class="wpcllm-resource-row" data-resource-index="' + resIndex + '">' +
					'<span class="dashicons dashicons-arrow-right-alt wpcllm-resource-bullet"></span>' +
					'<div class="wpcllm-resource-inputs">' +
						'<div class="wpcllm-resource-row-top">' +
							'<input type="text" name="sections[' + secIndex + '][resources][' + resIndex + '][title]" value="" class="wpcllm-res-title" placeholder="Link Title" required />' +
							'<input type="url" name="sections[' + secIndex + '][resources][' + resIndex + '][url]" value="" class="wpcllm-res-url" placeholder="https://..." required />' +
							'<input type="hidden" name="sections[' + secIndex + '][resources][' + resIndex + '][post_id]" value="0" class="wpcllm-res-post-id" />' +
							'<button type="button" class="button wpcllm-btn-pick-post">' +
								'<span class="dashicons dashicons-search"></span> Search Site' +
							'</button>' +
							'<button type="button" class="button-link-delete wpcllm-remove-resource-btn" aria-label="Remove resource">' +
								'<span class="dashicons dashicons-no-alt"></span>' +
							'</button>' +
						'</div>' +
						'<div class="wpcllm-resource-row-bottom">' +
							'<input type="text" name="sections[' + secIndex + '][resources][' + resIndex + '][description]" value="" class="wpcllm-res-desc" placeholder="Optional description for LLMs..." />' +
						'</div>' +
					'</div>' +
				'</div>';

			$resourcesList.append(resHtml);
			$resourcesList.find('.wpcllm-resource-row:last .wpcllm-res-title').focus();
		});

		// Remove Resource
		$(document).on('click', '.wpcllm-remove-resource-btn', function (e) {
			e.preventDefault();
			var $row = $(this).closest('.wpcllm-resource-row');
			$row.fadeOut(150, function () {
				$(this).remove();
			});
		});

		// Generate Suggestions Button
		$('#wpcllm-generate-suggestions-btn').on('click', function (e) {
			e.preventDefault();
			if (!confirm('Generate intelligent suggestions from your site content? This will populate recommended pages and posts into your sections.')) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true).addClass('updating-message');

			$.post(config.ajaxUrl, {
				action: 'wpcllm_generate_suggestions',
				nonce: config.nonce
			}).done(function (response) {
				$btn.prop('disabled', false).removeClass('updating-message');
				if (response.success && response.data.suggestions) {
					var s = response.data.suggestions;
					if (s.title) $('#wpcllm_site_title').val(s.title);
					if (s.summary) $('#wpcllm_site_summary').val(s.summary);

					// Render sections
					$sectionsList.empty();
					if (s.sections && s.sections.length) {
						$.each(s.sections, function (sIdx, sec) {
							var secHtml =
								'<div class="wpcllm-card wpcllm-section-item" data-section-index="' + sIdx + '">' +
									'<div class="wpcllm-section-item-header">' +
										'<div class="wpcllm-section-title-wrap">' +
											'<span class="dashicons dashicons-menu wpcllm-drag-handle"></span>' +
											'<span class="wpcllm-h2-prefix">##</span>' +
											'<input type="text" name="sections[' + sIdx + '][title]" value="' + escapeAttr(sec.title) + '" class="wpcllm-section-title-input" required />' +
										'</div>' +
										'<div class="wpcllm-section-item-actions">' +
											'<button type="button" class="button button-link-delete wpcllm-remove-section-btn">' +
												'<span class="dashicons dashicons-trash"></span> Delete Section' +
											'</button>' +
										'</div>' +
									'</div>' +
									'<div class="wpcllm-resources-list">';

							if (sec.resources && sec.resources.length) {
								$.each(sec.resources, function (rIdx, res) {
									secHtml +=
										'<div class="wpcllm-resource-row" data-resource-index="' + rIdx + '">' +
											'<span class="dashicons dashicons-arrow-right-alt wpcllm-resource-bullet"></span>' +
											'<div class="wpcllm-resource-inputs">' +
												'<div class="wpcllm-resource-row-top">' +
													'<input type="text" name="sections[' + sIdx + '][resources][' + rIdx + '][title]" value="' + escapeAttr(res.title) + '" class="wpcllm-res-title" required />' +
													'<input type="url" name="sections[' + sIdx + '][resources][' + rIdx + '][url]" value="' + escapeAttr(res.url) + '" class="wpcllm-res-url" required />' +
													'<input type="hidden" name="sections[' + sIdx + '][resources][' + rIdx + '][post_id]" value="' + (res.post_id || 0) + '" class="wpcllm-res-post-id" />' +
													'<button type="button" class="button wpcllm-btn-pick-post">' +
														'<span class="dashicons dashicons-search"></span> Search Site' +
													'</button>' +
													'<button type="button" class="button-link-delete wpcllm-remove-resource-btn" aria-label="Remove resource">' +
														'<span class="dashicons dashicons-no-alt"></span>' +
													'</button>' +
												'</div>' +
												'<div class="wpcllm-resource-row-bottom">' +
													'<input type="text" name="sections[' + sIdx + '][resources][' + rIdx + '][description]" value="' + escapeAttr(res.description || '') + '" class="wpcllm-res-desc" placeholder="Optional description for LLMs..." />' +
												'</div>' +
											'</div>' +
										'</div>';
								});
							}

							secHtml +=
									'</div>' +
									'<div class="wpcllm-section-footer">' +
										'<button type="button" class="button button-secondary wpcllm-add-resource-btn">' +
											'<span class="dashicons dashicons-plus-alt2"></span> Add Resource Link' +
										'</button>' +
									'</div>' +
								'</div>';

							$sectionsList.append(secHtml);
						});
					}
				}
			}).fail(function () {
				$btn.prop('disabled', false).removeClass('updating-message');
				alert('Failed to generate suggestions. Please check connection.');
			});
		});
	}

	/**
	 * Raw Markdown Editor Actions & AJAX Validation
	 */
	function initRawEditorActions() {
		$(document).on('click', '.wpcllm-submit-raw', function () {
			var action = $(this).data('action');
			$('#wpcllm_raw_action').val(action);
		});

		$('#wpcllm-validate-raw-btn').on('click', function (e) {
			e.preventDefault();
			var markdown = $('#wpcllm_raw_markdown').val();
			var $btn = $(this);
			$btn.prop('disabled', true);

			$.post(config.ajaxUrl, {
				action: 'wpcllm_validate_content',
				nonce: config.nonce,
				markdown: markdown
			}).done(function (res) {
				$btn.prop('disabled', false);
				if (res.success && res.data) {
					renderValidationFeedback(res.data);
				}
			}).fail(function () {
				$btn.prop('disabled', false);
				alert('Validation check failed to complete.');
			});
		});
	}

	/**
	 * Render Validation Box
	 */
	function renderValidationFeedback(data) {
		var $box = $('#wpcllm-validation-feedback');
		var $badge = $('#wpcllm-val-badge');
		var $msgs = $('#wpcllm-val-messages');
		var $stats = $('#wpcllm-val-stats');

		$msgs.empty();

		if (data.valid) {
			$badge.attr('class', 'wpcllm-badge wpcllm-badge-success').text('Valid llms.txt');
		} else {
			$badge.attr('class', 'wpcllm-badge wpcllm-badge-danger').text('Invalid llms.txt');
		}

		if (data.errors && data.errors.length) {
			var $errList = $('<ul class="wpcllm-notice-list wpcllm-notice-errors" style="margin-bottom:10px;"></ul>');
			$.each(data.errors, function (i, msg) {
				$errList.append('<li><strong>Error:</strong> ' + escapeHtml(msg) + '</li>');
			});
			$msgs.append($errList);
		}

		if (data.warnings && data.warnings.length) {
			var $warnList = $('<ul class="wpcllm-notice-list wpcllm-notice-warnings" style="margin-bottom:10px;"></ul>');
			$.each(data.warnings, function (i, msg) {
				$warnList.append('<li><strong>Warning:</strong> ' + escapeHtml(msg) + '</li>');
			});
			$msgs.append($warnList);
		}

		if (data.valid && (!data.warnings || !data.warnings.length)) {
			$msgs.append('<p style="color:#008a20; font-weight:500;">All formatting and structural checks passed successfully!</p>');
		}

		if (data.stats) {
			var s = data.stats;
			$stats.html(
				'<span><strong>Lines:</strong> ' + s.lines + '</span> &bull; ' +
				'<span><strong>Characters:</strong> ' + s.chars + '</span> &bull; ' +
				'<span><strong>Size:</strong> ' + (s.bytes / 1024).toFixed(2) + ' KB</span> &bull; ' +
				'<span><strong>H1:</strong> ' + s.h1_count + '</span> &bull; ' +
				'<span><strong>Sections:</strong> ' + s.h2_count + '</span> &bull; ' +
				'<span><strong>Links:</strong> ' + s.resource_count + '</span>'
			);
		}

		$box.slideDown(200);
	}

	/**
	 * WordPress Content Search Picker Modal
	 */
	function initContentPicker() {
		var $modal = $('#wpcllm-content-picker-modal');
		var $queryInput = $('#wpcllm-picker-query');
		var $postTypeSelect = $('#wpcllm-picker-post-type');
		var $loading = $('#wpcllm-picker-loading');
		var $results = $('#wpcllm-picker-results');

		// Open Modal
		$(document).on('click', '.wpcllm-btn-pick-post', function (e) {
			e.preventDefault();
			activePickerTargetRow = $(this).closest('.wpcllm-resource-row');
			$modal.fadeIn(150);
			$queryInput.val('').focus();
			executeSearch();
		});

		// Close Modal
		$modal.on('click', '.wpcllm-modal-close, .wpcllm-modal-backdrop', function () {
			$modal.fadeOut(150);
			activePickerTargetRow = null;
		});

		// Debounced Search Input
		$queryInput.on('input', function () {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(executeSearch, 250);
		});

		$postTypeSelect.on('change', function () {
			executeSearch();
		});

		function executeSearch() {
			var q = $queryInput.val();
			var pt = $postTypeSelect.val();
			var pts = pt ? [pt] : [];

			$loading.show();
			$results.empty();

			$.post(config.ajaxUrl, {
				action: 'wpcllm_search_posts',
				nonce: config.nonce,
				query: q,
				post_types: pts
			}).done(function (res) {
				$loading.hide();
				if (res.success && res.data.items && res.data.items.length) {
					$.each(res.data.items, function (i, item) {
						var $row = $(
							'<div class="wpcllm-picker-item" tabindex="0">' +
								'<div class="wpcllm-picker-item-title">' +
									'<span>' + escapeHtml(item.title) + '</span>' +
									'<span class="wpcllm-badge wpcllm-badge-neutral">' + escapeHtml(item.post_type_label) + '</span>' +
								'</div>' +
								'<div class="wpcllm-picker-item-url">' + escapeHtml(item.url) + '</div>' +
								(item.description ? '<div class="wpcllm-picker-item-desc">' + escapeHtml(item.description) + '</div>' : '') +
							'</div>'
						);

						$row.on('click keypress', function (e) {
							if (e.type === 'click' || e.which === 13) {
								if (activePickerTargetRow) {
									activePickerTargetRow.find('.wpcllm-res-title').val(item.title);
									activePickerTargetRow.find('.wpcllm-res-url').val(item.url);
									activePickerTargetRow.find('.wpcllm-res-desc').val(item.description || '');
									activePickerTargetRow.find('.wpcllm-res-post-id').val(item.id);
								}
								$modal.fadeOut(150);
								activePickerTargetRow = null;
							}
						});

						$results.append($row);
					});
				} else {
					$results.html('<p class="description" style="padding:12px; text-align:center;">' + config.i18n.noResults + '</p>');
				}
			}).fail(function () {
				$loading.hide();
				$results.html('<p class="description" style="padding:12px; text-align:center; color:red;">Search error occurred.</p>');
			});
		}
	}

	/**
	 * Preview Modal
	 */
	function initPreviewModal() {
		var $modal = $('#wpcllm-preview-modal');
		var $code = $('#wpcllm-preview-code');
		var $stats = $('#wpcllm-preview-stats');

		$(document).on('click', '.wpcllm-preview-trigger', function (e) {
			e.preventDefault();
			var source = $(this).data('source') || 'draft';

			$code.text('Loading preview...');
			$stats.empty();
			$modal.fadeIn(150);

			$.post(config.ajaxUrl, {
				action: 'wpcllm_preview_output',
				nonce: config.nonce,
				source: source
			}).done(function (res) {
				if (res.success && res.data) {
					$code.text(res.data.markdown);
					if (res.data.validation && res.data.validation.stats) {
						var s = res.data.validation.stats;
						$stats.html(
							'<span class="wpcllm-badge ' + (res.data.validation.valid ? 'wpcllm-badge-success' : 'wpcllm-badge-danger') + '">' +
								(res.data.validation.valid ? 'Valid Specification' : 'Has Validation Issues') +
							'</span> &nbsp; ' +
							'<span>' + s.lines + ' Lines &bull; ' + s.chars + ' Characters &bull; ' + (s.bytes / 1024).toFixed(2) + ' KB</span>'
						);
					}
				}
			}).fail(function () {
				$code.text('Failed to load preview.');
			});
		});

		$modal.on('click', '.wpcllm-modal-close, .wpcllm-modal-backdrop', function () {
			$modal.fadeOut(150);
		});

		$('.wpcllm-copy-preview').on('click', function () {
			var text = $code.text();
			navigator.clipboard.writeText(text).then(function () {
				alert(config.i18n.copied);
			});
		});
	}

	/**
	 * HTTP Loopback Self-Check
	 */
	function initSelfCheck() {
		$('#wpcllm-run-selfcheck-btn').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $resultBox = $('#wpcllm-selfcheck-result');

			$btn.prop('disabled', true).addClass('updating-message');
			$resultBox.html('<div class="wpcllm-callout wpcllm-callout-warning"><p><span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span> ' + config.i18n.runningCheck + '</p></div>').slideDown(150);

			$.post(config.ajaxUrl, {
				action: 'wpcllm_self_check',
				nonce: config.nonce
			}).done(function (res) {
				$btn.prop('disabled', false).removeClass('updating-message');
				if (res.success && res.data) {
					var d = res.data;
					var html =
						'<div class="wpcllm-callout ' + (d.success ? 'wpcllm-callout-success' : 'wpcllm-callout-warning') + '">' +
							'<h4>' + (d.success ? 'Verification Succeeded' : 'Verification Notice') + '</h4>' +
							'<p>' + escapeHtml(d.message) + '</p>' +
							'<ul class="wpcllm-inline-list">';

					if (d.status_code !== null) {
						html += '<li><strong>HTTP Status:</strong> ' + d.status_code + '</li>';
					}
					if (d.content_type) {
						html += '<li><strong>Content-Type:</strong> ' + escapeHtml(d.content_type) + '</li>';
					}
					html += '<li><strong>Matches Published Hash:</strong> ' + (d.matches_published ? 'Yes' : 'No') + '</li>' +
							'<li><strong>Last Checked:</strong> Just now</li>' +
						'</ul>' +
					'</div>';

					$resultBox.html(html);
				}
			}).fail(function () {
				$btn.prop('disabled', false).removeClass('updating-message');
				$resultBox.html('<div class="wpcllm-callout wpcllm-callout-warning"><p>Loopback check could not be executed.</p></div>');
			});
		});
	}

	/**
	 * Confirmation Interceptions
	 */
	function initConfirmations() {
		$('#wpcllm-reset-draft-btn').on('click', function (e) {
			e.preventDefault();
			if (confirm(config.i18n.confirmReset)) {
				var $form = $('#wpcllm-builder-form');
				$form.append('<input type="hidden" name="wpcllm_action" value="reset_draft" />');
				$form.submit();
			}
		});

		$('.wpcllm-confirm-remove-form').on('submit', function (e) {
			if (!confirm(config.i18n.confirmRemovePhysical)) {
				e.preventDefault();
			}
		});

		$('#wpcllm-upload-form').on('submit', function (e) {
			if (!confirm(config.i18n.confirmImportOverwrite)) {
				e.preventDefault();
			}
		});
	}

	/**
	 * Copy Buttons
	 */
	function initCopyButtons() {
		$('#wpcllm-copy-report-btn').on('click', function (e) {
			e.preventDefault();
			var report = $(this).data('report');
			if (report && navigator.clipboard) {
				navigator.clipboard.writeText(report).then(function () {
					alert(config.i18n.copied);
				});
			}
		});
	}

	/**
	 * Utility escaping functions
	 */
	function escapeHtml(str) {
		return $('<div>').text(str || '').html();
	}

	function escapeAttr(str) {
		return $('<div>').text(str || '').html().replace(/"/g, '&quot;');
	}

})(jQuery);
