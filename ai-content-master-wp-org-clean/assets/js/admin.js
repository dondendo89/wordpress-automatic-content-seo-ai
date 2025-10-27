/**
 * WordPress.org Compliant Admin JavaScript
 *
 * @package AI_Content_Master
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initContentGenerator();
        initBulkGenerator();
    });

    /**
     * Initialize content generator
     */
    function initContentGenerator() {
        // Generate content
        $(document).on('click', '#acm-generate-content', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            
            generateContent(postId, nonce, $button);
        });

        // Generate meta description
        $(document).on('click', '#acm-generate-meta', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            
            generateMeta(postId, nonce, $button);
        });

        // Generate tags
        $(document).on('click', '#acm-generate-tags', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            
            generateTags(postId, nonce, $button);
        });

        // Generate excerpt
        $(document).on('click', '#acm-generate-excerpt', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            
            generateExcerpt(postId, nonce, $button);
        });

        // Generate all
        $(document).on('click', '#acm-generate-all', function() {
            var $button = $(this);
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            
            generateAll(postId, nonce, $button);
        });
    }

    /**
     * Initialize bulk generator
     */
    function initBulkGenerator() {
        $(document).on('click', '#acm-bulk-generate', function() {
            var $button = $(this);
            var nonce = $button.data('nonce');
            
            startBulkGeneration(nonce, $button);
        });
    }

    /**
     * Generate content
     */
    function generateContent(postId, nonce, $button) {
        setButtonLoading($button, true);
        showStatus('loading', acm.strings.generating_content || 'Generating content...');

        $.ajax({
            url: acm.ajax_url,
            type: 'POST',
            data: {
                action: 'acm_generate_content',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showStatus('success', response.data.message);
                    updateRemainingGenerations(response.data.remaining);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (response.data.upgrade_url) {
                        showStatus('error', response.data.message + ' Redirecting to upgrade page...');
                        setTimeout(function() {
                            window.open(response.data.upgrade_url, '_blank');
                        }, 2000);
                    } else {
                        showStatus('error', response.data.message || 'Content generation failed');
                    }
                }
            },
            error: function() {
                showStatus('error', 'Network error occurred');
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    /**
     * Generate meta description
     */
    function generateMeta(postId, nonce, $button) {
        setButtonLoading($button, true);
        showStatus('loading', acm.strings.generating_meta || 'Generating meta description...');

        $.ajax({
            url: acm.ajax_url,
            type: 'POST',
            data: {
                action: 'acm_generate_meta',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showStatus('success', response.data.message);
                    updateRemainingGenerations(response.data.remaining);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (response.data.upgrade_url) {
                        showStatus('error', response.data.message + ' Redirecting to upgrade page...');
                        setTimeout(function() {
                            window.open(response.data.upgrade_url, '_blank');
                        }, 2000);
                    } else {
                        showStatus('error', response.data.message || 'Meta description generation failed');
                    }
                }
            },
            error: function() {
                showStatus('error', 'Network error occurred');
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    /**
     * Generate tags
     */
    function generateTags(postId, nonce, $button) {
        setButtonLoading($button, true);
        showStatus('loading', acm.strings.generating_tags || 'Generating tags...');

        $.ajax({
            url: acm.ajax_url,
            type: 'POST',
            data: {
                action: 'acm_generate_tags',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showStatus('success', response.data.message);
                    updateRemainingGenerations(response.data.remaining);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (response.data.upgrade_url) {
                        showStatus('error', response.data.message + ' Redirecting to upgrade page...');
                        setTimeout(function() {
                            window.open(response.data.upgrade_url, '_blank');
                        }, 2000);
                    } else {
                        showStatus('error', response.data.message || 'Tags generation failed');
                    }
                }
            },
            error: function() {
                showStatus('error', 'Network error occurred');
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    /**
     * Generate excerpt
     */
    function generateExcerpt(postId, nonce, $button) {
        setButtonLoading($button, true);
        showStatus('loading', acm.strings.generating_excerpt || 'Generating excerpt...');

        $.ajax({
            url: acm.ajax_url,
            type: 'POST',
            data: {
                action: 'acm_generate_excerpt',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showStatus('success', response.data.message);
                    updateRemainingGenerations(response.data.remaining);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (response.data.upgrade_url) {
                        showStatus('error', response.data.message + ' Redirecting to upgrade page...');
                        setTimeout(function() {
                            window.open(response.data.upgrade_url, '_blank');
                        }, 2000);
                    } else {
                        showStatus('error', response.data.message || 'Excerpt generation failed');
                    }
                }
            },
            error: function() {
                showStatus('error', 'Network error occurred');
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    /**
     * Generate all
     */
    function generateAll(postId, nonce, $button) {
        setButtonLoading($button, true);
        showStatus('loading', acm.strings.generating_all || 'Generating all content...');

        $.ajax({
            url: acm.ajax_url,
            type: 'POST',
            data: {
                action: 'acm_generate_all',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showStatus('success', response.data.message);
                    if (response.data.remaining !== undefined) {
                        updateRemainingGenerations(response.data.remaining);
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (response.data && response.data.upgrade_url) {
                        showStatus('error', response.data.message + ' Redirecting to upgrade page...');
                        setTimeout(function() {
                            window.open(response.data.upgrade_url, '_blank');
                        }, 2000);
                    } else {
                        showStatus('error', response.data ? response.data.message : 'Content generation failed');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showStatus('error', 'Network error occurred. Please try again.');
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    /**
     * Start bulk generation
     */
    function startBulkGeneration(nonce, $button) {
        var formData = {
            action: 'acm_bulk_generate',
            nonce: nonce,
            postTypes: [],
            generateContent: 0,
            generateMeta: 0,
            generateTags: 0,
            generateExcerpt: 0,
            batchSize: $('#acm-batch-size').val() || 5,
            forceRegenerate: $('#acm-force-regenerate').is(':checked') ? 1 : 0
        };

        // Collect post types
        $('input[name="postTypes[]"]:checked').each(function() {
            formData.postTypes.push($(this).val());
        });

        // Collect generation options
        if ($('input[name="generateContent"]').is(':checked')) {
            formData.generateContent = 1;
        }
        if ($('input[name="generateMeta"]').is(':checked')) {
            formData.generateMeta = 1;
        }
        if ($('input[name="generateTags"]').is(':checked')) {
            formData.generateTags = 1;
        }
        if ($('input[name="generateExcerpt"]').is(':checked')) {
            formData.generateExcerpt = 1;
        }

        if (formData.postTypes.length === 0) {
            showBulkStatus('error', 'Please select at least one post type');
            return;
        }

        setButtonLoading($button, true);
        showBulkStatus('loading', 'Starting bulk generation...');

        $.ajax({
            url: acm.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showBulkStatus('success', response.data.message);
                    monitorBulkJob(response.data.job_id, nonce);
                } else {
                    if (response.data.upgrade_url) {
                        showBulkStatus('error', response.data.message + ' Redirecting to upgrade page...');
                        setTimeout(function() {
                            window.open(response.data.upgrade_url, '_blank');
                        }, 2000);
                    } else {
                        showBulkStatus('error', response.data.message || 'Bulk generation failed');
                    }
                }
            },
            error: function() {
                showBulkStatus('error', 'Network error occurred');
            },
            complete: function() {
                setButtonLoading($button, false);
            }
        });
    }

    /**
     * Monitor bulk job
     */
    function monitorBulkJob(jobId, nonce) {
        var startTime = Date.now();
        var maxDuration = 30 * 60 * 1000; // 30 minutes

        function checkStatus() {
            if (Date.now() - startTime > maxDuration) {
                showBulkStatus('error', 'Job monitoring timeout');
                return;
            }

            $.ajax({
                url: acm.ajax_url,
                type: 'POST',
                data: {
                    action: 'acm_bulk_status',
                    jobId: jobId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        var jobData = response.data;
                        updateBulkProgress(jobData);

                        if (jobData.status === 'completed') {
                            showBulkStatus('success', 'Bulk generation completed successfully!');
                        } else if (jobData.status === 'processing' || jobData.status === 'pending') {
                            setTimeout(checkStatus, 2000);
                        } else {
                            showBulkStatus('error', 'Job failed');
                        }
                    } else {
                        showBulkStatus('error', response.data.message || 'Failed to get job status');
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Network error occurred';
                    
                    if (xhr.status === 0) {
                        errorMessage = 'Network connection error';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Job not found';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error';
                    }
                    
                    showBulkStatus('error', errorMessage);
                }
            });
        }

        checkStatus();
    }

    /**
     * Update bulk progress
     */
    function updateBulkProgress(jobData) {
        var percentage = Math.round((jobData.processed / jobData.total) * 100);
        
        var progressHtml = '<div class="progress-bar">' +
            '<div class="progress-fill" style="width: ' + percentage + '%"></div>' +
            '</div>' +
            '<div class="progress-text">' +
            jobData.processed + ' / ' + jobData.total + ' posts processed (' + percentage + '%)' +
            '</div>';

        $('#acm-bulk-progress').html(progressHtml);
    }

    /**
     * Update remaining generations display
     */
    function updateRemainingGenerations(remaining) {
        // Update free generations banner if present
        if ($('.acm-free-value').length > 0) {
            $('.acm-free-value').eq(0).text(remaining + ' / 10');
        }

        // Update meta box credits info if present
        if ($('.acm-credits-info-small').length > 0) {
            if (remaining > 0) {
                $('.acm-credits-info-small').html('<p>' + remaining + ' free generations remaining</p>');
            } else {
                $('.acm-credits-info-small').html('<p>No free generations remaining. <a href="#" target="_blank">Upgrade now</a></p>');
            }
        }
    }

    /**
     * Set button loading state
     */
    function setButtonLoading($button, loading) {
        if (loading) {
            $button.prop('disabled', true).addClass('loading');
        } else {
            $button.prop('disabled', false).removeClass('loading');
        }
    }

    /**
     * Show status message
     */
    function showStatus(type, message) {
        var $status = $('#acm-status');
        $status.removeClass('success error loading').addClass(type).text(message);
    }

    /**
     * Show bulk status message
     */
    function showBulkStatus(type, message) {
        var $status = $('#acm-bulk-status');
        $status.removeClass('success error loading').addClass(type).text(message);
    }

})(jQuery);
