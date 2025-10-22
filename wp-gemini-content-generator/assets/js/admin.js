(function($){
	function postAjax(action, data){
		return $.ajax({
			url: (window.WGC && WGC.ajaxUrl) || ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: Object.assign({ action: action }, data || {})
		});
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
					alert((res && res.data && res.data.message) || 'Error');
					$btn.html(original).prop('disabled', false);
				}
			})
			.fail(function(xhr){
				alert('Error');
				$btn.html(original).prop('disabled', false);
			});
	});

	$(document).on('click', '#wgc-bulk-generate', function(e){
		e.preventDefault();
		var $btn = $(this);
		var nonce = $btn.data('nonce');
		var batchSize = parseInt($('#wgc-batch-size').val(), 10) || 5;
		var $status = $('#wgc-bulk-status');
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>' + ((WGC && WGC.i18n && WGC.i18n.generating) || 'Generating...'));
		$status.text('');

		postAjax('wgc_bulk_generate', { nonce: nonce, batchSize: batchSize })
			.done(function(res){
				if(res && res.success){
					var processed = (res.data && res.data.processed) || 0;
					var errors = (res.data && res.data.errors) || [];
					$status.text('Processed: ' + processed + (errors.length ? (' | Errors: ' + errors.length) : ''));
				}else{
					$status.text('Error');
				}
			})
			.fail(function(){
				$status.text('Error');
			})
			.always(function(){
				$btn.prop('disabled', false).html('Run Bulk Generation');
			});
	});
})(jQuery);