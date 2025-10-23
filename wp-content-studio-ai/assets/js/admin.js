(function($){
	function postAjax(action, data){
		return $.ajax({
			url: (window.WGC && WGC.ajaxUrl) || ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: Object.assign({ action: action }, data || {})
		});
	}

	function showInlineNotice(type, message){
		var cls = type === 'error' ? 'notice notice-error' : 'notice notice-success';
		var $n = $('<div class="'+cls+' is-dismissible" style="margin:10px 0;"><p></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
		$n.find('p').text(message);
		var $anchor = $('#wgc-preview');
		if ($anchor.length){
			$anchor.before($n);
		} else {
			$('.wgc-generate').first().closest('.inside, .wrap, form, body').prepend($n);
		}
		setTimeout(function(){ $n.fadeOut(200, function(){ $(this).remove(); }); }, 5000);
	}

	$(document).on('click', '.wgc-generate', function(e){
		e.preventDefault();
		var $btn = $(this);
		var postId = $btn.data('post-id');
		var nonce = $btn.data('nonce');
		var original = $btn.text();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>' + ((WGC && WGC.i18n && WGC.i18n.generating) || 'Generating...'));

		postAjax('wgc_generate_for_post', { postId: postId, nonce: nonce })
			.done(function(res){
				if(res && res.success){
					$btn.html('<span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-right: 5px;"></span>' + ((WGC && WGC.i18n && WGC.i18n.done) || 'Done'));
					
					// Show generated content preview
					if(res.data && res.data.generated_content){
						var preview = res.data.generated_content.substring(0, 800) + (res.data.generated_content.length > 800 ? '...' : '');
						$('#wgc-preview-content').html(preview);
						$('#wgc-preview').show();
					}
					
					// Refresh the page to show updated content
					setTimeout(function(){ 
						location.reload();
					}, 2000);
				}else{
					showInlineNotice('error', (res && res.data && res.data.message) || 'Error');
					$btn.html(original).prop('disabled', false);
				}
			})
			.fail(function(xhr){
				showInlineNotice('error', 'Error');
				$btn.html(original).prop('disabled', false);
			});
	});

	var bulkJobInterval = null;

	$(document).on('click', '#wgc-bulk-generate', function(e){
		e.preventDefault();
		var $btn = $(this);
		var nonce = $btn.data('nonce');
		var batchSize = parseInt($('#wgc-batch-size').val(), 10) || 5;
		var postTypes = ($('#wgc-bulk-post-types').val() || []);
		var forceRegenerate = $('#wgc-force-regenerate').is(':checked');
		var $status = $('#wgc-bulk-status');
		
		// Clear any existing job monitoring
		if (bulkJobInterval) {
			clearInterval(bulkJobInterval);
			bulkJobInterval = null;
		}
		
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>' + ((WGC && WGC.i18n && WGC.i18n.generating) || 'Starting...'));
		$status.text('');

		postAjax('wgc_bulk_generate', { nonce: nonce, batchSize: batchSize, postTypes: postTypes, forceRegenerate: forceRegenerate })
			.done(function(res){
				if(res && res.success){
					if (res.data.job_started) {
						$status.html('<strong>Job Started:</strong> ' + (res.data.message || 'Processing in background...') + '<br><strong>Total Posts:</strong> ' + (res.data.total || 0));
						
						// Start monitoring job status
						var jobId = res.data.job_id;
						bulkJobInterval = setInterval(function() {
							postAjax('wgc_bulk_status', { jobId: jobId, nonce: nonce })
								.done(function(statusRes) {
									if (statusRes && statusRes.success) {
										var data = statusRes.data;
										var progress = Math.round((data.processed / data.total_posts) * 100) || 0;
										
										if (data.status === 'completed') {
											clearInterval(bulkJobInterval);
											bulkJobInterval = null;
											$btn.prop('disabled', false).html('Run Bulk Generation');
											$status.html('<strong>✅ Job Completed!</strong><br>Processed: ' + data.processed + '/' + data.total_posts + ' posts<br>Errors: ' + (data.errors ? data.errors.length : 0));
										} else if (data.status === 'running') {
											$status.html('<strong>🔄 Processing...</strong> ' + data.processed + '/' + data.total_posts + ' posts (' + progress + '%)<br>Errors: ' + (data.errors ? data.errors.length : 0));
										}
									}
								})
								.fail(function() {
									clearInterval(bulkJobInterval);
									bulkJobInterval = null;
									$btn.prop('disabled', false).html('Run Bulk Generation');
									$status.text('Error monitoring job status');
								});
						}, 3000); // Check every 3 seconds
					} else {
						$status.text(res.data.message || 'No posts to process');
						$btn.prop('disabled', false).html('Run Bulk Generation');
					}
				} else {
					$status.text('Error: ' + (res.data.message || 'Unknown error'));
					$btn.prop('disabled', false).html('Run Bulk Generation');
				}
			})
			.fail(function(){
				$status.text('Error starting job');
				$btn.prop('disabled', false).html('Run Bulk Generation');
			});
	});
})(jQuery);