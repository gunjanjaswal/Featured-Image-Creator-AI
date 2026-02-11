/**
 * Admin JavaScript for AI Featured Image Generator
 */
(function ($) {
	'use strict';

	/**
	 * Single post image generation
	 */
	function initSingleGeneration() {
		$('.aifig-generate-btn').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var postId = $btn.data('post-id');
			var $metaBox = $btn.closest('.aifig-meta-box');
			var $loading = $metaBox.find('.aifig-loading');
			var $result = $metaBox.find('.aifig-result');

			// Disable button and show loading
			$btn.prop('disabled', true);
			$loading.show();
			$result.hide().removeClass('success error');

			// Make AJAX request
			$.ajax({
				url: aifigData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aifig_generate_single',
					nonce: aifigData.nonce,
					post_id: postId
				},
				success: function (response) {
					if (response.success) {
						$result
							.addClass('success')
							.html(
								'<p><strong>' + response.data.message + '</strong></p>' +
								'<img src="' + response.data.image_url + '" alt="Generated featured image">'
							)
							.show();

						// Update featured image box
						$('#postimagediv').load(window.location.href + ' #postimagediv > *');

						// Update status
						$metaBox.find('.aifig-status').replaceWith(
							'<p class="aifig-status">' +
							'<span class="dashicons dashicons-yes-alt"></span>' +
							aifigData.strings.success +
							'</p>'
						);
					} else {
						$result
							.addClass('error')
							.html('<p>' + response.data.message + '</p>')
							.show();
					}
				},
				error: function (xhr, status, error) {
					$result
						.addClass('error')
						.html('<p>' + aifigData.strings.error + '</p>')
						.show();
				},
				complete: function () {
					$loading.hide();
					$btn.prop('disabled', false);
				}
			});
		});
	}

	/**
	 * Batch generation
	 */
	function initBatchGeneration() {
		var batchInProgress = false;
		var currentIndex = 0;
		var postIds = [];
		var results = {
			success: [],
			errors: []
		};

		$('.aifig-start-batch').on('click', function (e) {
			e.preventDefault();

			if (batchInProgress) {
				return;
			}

			var $btn = $(this);
			var mode = $btn.data('mode') || 'missing';

			// Confirm action
			var confirmMsg = mode === 'regenerate'
				? 'Are you sure you want to regenerate ALL featured images? This will overwrite existing images.'
				: aifigData.strings.confirmBatch;

			if (!confirm(confirmMsg)) {
				return;
			}

			// Disable button and show loading
			$btn.prop('disabled', true);
			$btn.addClass('updating-message');
			var originalText = $btn.html();
			$btn.text('Preparing...');

			// Fetch IDs via AJAX
			$.ajax({
				url: aifigData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aifig_get_batch_ids',
					nonce: aifigData.nonce,
					mode: mode
				},
				success: function (response) {
					if (response.success && response.data.length > 0) {
						postIds = response.data;
						startBatchProcessing($btn);
					} else {
						alert('No posts found to process.');
						$btn.prop('disabled', false);
						$btn.html(originalText);
					}
				},
				error: function (xhr, status, error) {
					alert('Failed to fetch post IDs. Please try again.');
					$btn.prop('disabled', false);
					$btn.html(originalText);
				}
			});
		});

		function startBatchProcessing($btn) {
			// Reset state
			batchInProgress = true;
			currentIndex = 0;
			results = { success: [], errors: [] };

			// Show progress
			$('.aifig-batch-progress').show();
			$('.aifig-batch-results').hide();

			// Scroll to progress bar
			$('html, body').animate({
				scrollTop: $(".aifig-batch-progress").offset().top - 100
			}, 500);

			// Start processing
			processNextPost();
		}

		function processNextPost() {
			if (currentIndex >= postIds.length) {
				// Batch complete
				completeBatch();
				return;
			}

			var postId = postIds[currentIndex];
			// Show progress for items completed, not current item
			var progress = postIds.length > 0 ? Math.round((currentIndex / postIds.length) * 100) : 0;

			// Update progress (ensure value is finite)
			if (isFinite(progress)) {
				$('.aifig-batch-progress progress').val(progress);
				$('.aifig-progress-text').text(progress + '%');
			}
			$('.aifig-progress-status').text(
				aifigData.strings.batchProgress
					.replace('{current}', currentIndex + 1)
					.replace('{total}', postIds.length)
			);

			// Generate image
			$.ajax({
				url: aifigData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aifig_batch_generate',
					nonce: aifigData.nonce,
					post_id: postId
				},
				success: function (response) {
					if (response.success) {
						results.success.push({
							postId: response.data.post_id,
							title: response.data.post_title
						});
					} else {
						results.errors.push({
							postId: response.data.post_id,
							title: response.data.post_title,
							message: response.data.message
						});
					}
				},
				error: function (xhr, status, error) {
					results.errors.push({
						postId: postId,
						title: 'Post #' + postId,
						message: aifigData.strings.error
					});
				},
				complete: function () {
					currentIndex++;
					// Small delay to avoid rate limiting
					setTimeout(processNextPost, 500);
				}
			});
		}

		function completeBatch() {
			batchInProgress = false;

			// Hide progress
			$('.aifig-batch-progress').hide();

			// Show results
			var resultsHtml = '<div class="aifig-results-summary">';
			resultsHtml += '<p><strong>' + aifigData.strings.batchComplete + '</strong></p>';
			resultsHtml += '<p>Successfully generated: ' + results.success.length + '</p>';
			if (results.errors.length > 0) {
				resultsHtml += '<p>Errors: ' + results.errors.length + '</p>';
			}
			resultsHtml += '</div>';

			// Add success items
			if (results.success.length > 0) {
				resultsHtml += '<h4>Successfully Generated</h4>';
				results.success.forEach(function (item) {
					resultsHtml += '<div class="aifig-result-item">';
					resultsHtml += '<strong>' + item.title + '</strong>';
					resultsHtml += '</div>';
				});
			}

			// Add error items
			if (results.errors.length > 0) {
				resultsHtml += '<h4>Errors</h4>';
				results.errors.forEach(function (item) {
					resultsHtml += '<div class="aifig-result-item error">';
					resultsHtml += '<strong>' + item.title + '</strong>';
					resultsHtml += '<span>' + item.message + '</span>';
					resultsHtml += '</div>';
				});
			}

			$('.aifig-results-content').html(resultsHtml);
			$('.aifig-batch-results').show();

			// Re-enable button
			$('.aifig-start-batch').prop('disabled', false).text(
				$('.aifig-start-batch').data('mode') === 'regenerate' ? 'Regenerate All Images' : 'Generate All Images'
			);
			// Restore icon if possible or just text is fine for now, page reload usually follows.

			// Reload page after a delay to show updated post list
			setTimeout(function () {
				if (confirm('Batch generation complete! Reload the page to see updated results?')) {
					window.location.reload();
				}
			}, 2000);
		}
	}

	/**
	 * Tab switching
	 */
	function initTabSwitching() {
		$('.aifig-tab-btn').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var tab = $btn.data('tab');

			// Update active tab button
			$btn.siblings().removeClass('active');
			$btn.addClass('active');

			// Update active tab content
			$btn.closest('.aifig-meta-box').find('.aifig-tab-content').removeClass('active');
			$('#' + tab + '-tab').addClass('active');
		});
	}

	/**
	 * Manual upload functionality
	 */
	function initManualUpload() {
		var mediaUploader;

		$('.aifig-upload-btn').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var $metaBox = $btn.closest('.aifig-meta-box');
			var $preview = $metaBox.find('.aifig-upload-preview');

			// If the media uploader already exists, reopen it
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			// Create the media uploader
			mediaUploader = wp.media({
				title: 'Select or Upload Featured Image',
				button: {
					text: 'Use this image'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			});

			// When an image is selected
			mediaUploader.on('select', function () {
				var attachment = mediaUploader.state().get('selection').first().toJSON();

				// Set the featured image via AJAX
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'set-post-thumbnail',
						post_id: $btn.closest('.aifig-meta-box').find('.aifig-generate-btn').data('post-id'),
						thumbnail_id: attachment.id,
						_ajax_nonce: $('#_wpnonce').val()
					},
					success: function (response) {
						// Show preview
						$preview.find('img').attr('src', attachment.url);
						$preview.show();

						// Update featured image box
						$('#postimagediv').load(window.location.href + ' #postimagediv > *');

						// Update status
						$metaBox.find('.aifig-status').replaceWith(
							'<p class="aifig-status">' +
							'<span class="dashicons dashicons-yes-alt"></span>' +
							'Featured image set successfully!' +
							'</p>'
						);
					}
				});
			});

			// Open the media uploader
			mediaUploader.open();
		});
	}

	/**
	 * Settings page dynamic logic
	 */
	function initSettingsPage() {
		var $providerSelect = $('#aifig_api_provider');
		var $qualityRow = $('#aifig_image_quality').closest('tr');
		var $formatRow = $('#aifig_output_format').closest('tr');

		if ($providerSelect.length === 0) {
			return;
		}

		function updateVisibility() {
			var provider = $providerSelect.val();
			var isOpenAI = provider === 'openai' || provider.indexOf('gpt-image') !== -1;
			var isStability = provider === 'stability' || provider === 'seedream-4.5';
			var isGemini = provider === 'gemini';

			// Quality: Only for OpenAI
			if (isOpenAI) {
				$qualityRow.show();
			} else {
				$qualityRow.hide();
			}

			// Output Format: Only for Stability
			// (OpenAI returns URL/PNG usually, renaming leads to issues. Stability allows format request)
			if (isStability) {
				$formatRow.show();
			} else {
				$formatRow.hide();
			}
		}

		// Initial state
		updateVisibility();

		// On change
		$providerSelect.on('change', updateVisibility);
	}

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		initSingleGeneration();
		initBatchGeneration();
		initTabSwitching();
		initManualUpload();
		initSettingsPage();
	});

})(jQuery);
