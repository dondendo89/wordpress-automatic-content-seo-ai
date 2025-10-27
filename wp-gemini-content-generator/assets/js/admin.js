/**
 * WP Gemini Content Generator - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    var WGCAdmin = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initTooltips();
            this.initMultiSelect();
        },

        bindEvents: function() {
            // Content generation
            $(document).on('click', '#wgc-generate-content', this.generateContent);
            $(document).on('click', '#wgc-generate-meta', this.generateMetaDescription);
            $(document).on('click', '#wgc-generate-tags', this.generateTags);
            $(document).on('click', '#wgc-generate-excerpt', this.generateExcerpt);
            $(document).on('click', '#wgc-generate-all', this.generateAll);
            
            // Bulk generation
            $(document).on('click', '#wgc-bulk-generate', this.startBulkGeneration);
            
            // Tab switching
            $(document).on('click', '.wgc-tab-link', this.switchTab);
        },

        initTabs: function() {
            // Initialize tabs
            $('.wgc-tab-link').first().addClass('active');
            $('.wgc-tab-content').first().addClass('active');
        },

        initTooltips: function() {
            // Add tooltips to form elements
            $('[data-tooltip]').each(function() {
                $(this).addClass('wgc-tooltip');
            });
        },

        initMultiSelect: function() {
            // Enhance multi-select dropdowns
            $('.wgc-multi-select').each(function() {
                var $select = $(this);
                var $container = $('<div class="wgc-multi-select-container"></div>');
                
                $select.after($container);
                $container.append($select);
                
                // Add styling
                $select.addClass('wgc-enhanced-select');
            });
        },

        switchTab: function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var target = $link.attr('href');
            
            // Update active tab
            $('.wgc-tab-link').removeClass('active');
            $link.addClass('active');
            
            // Update active content
            $('.wgc-tab-content').removeClass('active');
            $(target).addClass('active');
        },

        generateContent: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var nonce = $btn.data('nonce');
            var $preview = $('#wgc-preview');
            var $previewContent = $preview.find('.wgc-preview-content');
            var $previewStats = $preview.find('.wgc-preview-stats');
            
            // Show loading state
            $btn.addClass('wgc-loading').prop('disabled', true);
            $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>' + 
                     (WGC.i18n.generating || 'Generating...'));
            
            // Clear previous preview
            $preview.hide();
            $previewContent.empty();
            $previewStats.empty();
            
            // Make AJAX request
            $.post(WGC.ajax_url, {
                action: 'wgc_generate_for_post',
                post_id: postId,
                nonce: nonce
            })
            .done(function(response) {
                if (response.success) {
                    // Show preview
                    $previewContent.html(response.data.generated_content);
                    $previewStats.html(
                        '<span class="wgc-status-indicator success"></span>' +
                        'Characters: ' + response.data.character_count + ' | ' +
                        'Status: ' + (response.data.message || 'Success')
                    );
                    $preview.show();
                    
                    // Show success message
                    WGCAdmin.showNotice('success', response.data.message || 'Content generated successfully!');
                    
                    // Refresh page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    WGCAdmin.showNotice('error', response.data.message || 'Error generating content');
                }
            })
            .fail(function() {
                WGCAdmin.showNotice('error', 'Network error. Please try again.');
            })
            .always(function() {
                // Reset button state
                $btn.removeClass('wgc-loading').prop('disabled', false);
                $btn.html('Generate Content');
            });
        },

        generateMetaDescription: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var nonce = $btn.data('nonce');
            
            // Show loading state
            $btn.addClass('wgc-loading').prop('disabled', true);
            $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Generating...');
            
            // Make AJAX request
            $.post(WGC.ajax_url, {
                action: 'wgc_generate_meta_description',
                post_id: postId,
                nonce: nonce
            })
            .done(function(response) {
                if (response.success) {
                    WGCAdmin.showNotice('success', response.data.message || 'Meta description generated successfully!');
                    
                    // Update meta description fields if they exist
                    if ($('#yoast_wpseo_metadesc').length) {
                        $('#yoast_wpseo_metadesc').val(response.data.meta_description);
                    }
                    if ($('#rank_math_description').length) {
                        $('#rank_math_description').val(response.data.meta_description);
                    }
                    
                    // Refresh page after a short delay to show updated content
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    WGCAdmin.showNotice('error', response.data.message || 'Error generating meta description');
                }
            })
            .fail(function() {
                WGCAdmin.showNotice('error', 'Network error. Please try again.');
            })
            .always(function() {
                // Reset button state
                $btn.removeClass('wgc-loading').prop('disabled', false);
                $btn.html('Generate Meta Description');
            });
        },

        generateTags: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var nonce = $btn.data('nonce');
            
            // Show loading state
            $btn.addClass('wgc-loading').prop('disabled', true);
            $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Generating...');
            
            // Make AJAX request
            $.post(WGC.ajax_url, {
                action: 'wgc_generate_tags',
                post_id: postId,
                nonce: nonce
            })
            .done(function(response) {
                if (response.success) {
                    WGCAdmin.showNotice('success', response.data.message || 'Tags generated successfully!');
                    
                    // Update tags field if it exists
                    if ($('#new-tag-post_tag').length) {
                        var tagsText = response.data.tags.join(', ');
                        $('#new-tag-post_tag').val(tagsText);
                    }
                    
                    // Refresh page after a short delay to show updated content
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    WGCAdmin.showNotice('error', response.data.message || 'Error generating tags');
                }
            })
            .fail(function() {
                WGCAdmin.showNotice('error', 'Network error. Please try again.');
            })
            .always(function() {
                // Reset button state
                $btn.removeClass('wgc-loading').prop('disabled', false);
                $btn.html('Generate Tags');
            });
        },

        generateExcerpt: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var nonce = $btn.data('nonce');
            
            // Show loading state
            $btn.addClass('wgc-loading').prop('disabled', true);
            $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Generating...');
            
            // Make AJAX request
            $.post(WGC.ajax_url, {
                action: 'wgc_generate_excerpt',
                post_id: postId,
                nonce: nonce
            })
            .done(function(response) {
                if (response.success) {
                    WGCAdmin.showNotice('success', response.data.message || 'Excerpt generated successfully!');
                    
                    // Update excerpt field if it exists
                    if ($('#excerpt').length) {
                        $('#excerpt').val(response.data.excerpt);
                    }
                    
                    // Refresh page after a short delay to show updated content
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    WGCAdmin.showNotice('error', response.data.message || 'Error generating excerpt');
                }
            })
            .fail(function() {
                WGCAdmin.showNotice('error', 'Network error. Please try again.');
            })
            .always(function() {
                // Reset button state
                $btn.removeClass('wgc-loading').prop('disabled', false);
                $btn.html('Generate Excerpt');
            });
        },

        generateAll: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var nonce = $btn.data('nonce');
            
            // Show loading state
            $btn.addClass('wgc-loading').prop('disabled', true);
            $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Generating All...');
            
            // Disable all other buttons
            $('.wgc-individual-controls .button').prop('disabled', true);
            
            // Make AJAX request
            $.post(WGC.ajax_url, {
                action: 'wgc_generate_all',
                post_id: postId,
                nonce: nonce
            })
            .done(function(response) {
                if (response.success) {
                    var message = response.data.message || 'All content generated successfully!';
                    WGCAdmin.showNotice('success', message);
                    
                    // Show detailed results
                    if (response.data.results) {
                        var resultsText = Object.keys(response.data.results).join(', ');
                        WGCAdmin.showNotice('info', 'Generated: ' + resultsText);
                    }
                    
                    // Show errors if any
                    if (response.data.errors && Object.keys(response.data.errors).length > 0) {
                        var errorsText = Object.keys(response.data.errors).join(', ');
                        WGCAdmin.showNotice('warning', 'Errors in: ' + errorsText);
                    }
                    
                    // Refresh page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                } else {
                    WGCAdmin.showNotice('error', response.data.message || 'Error generating content');
                }
            })
            .fail(function() {
                WGCAdmin.showNotice('error', 'Network error. Please try again.');
            })
            .always(function() {
                // Reset button state
                $btn.removeClass('wgc-loading').prop('disabled', false);
                $btn.html('🚀 Generate All');
                
                // Re-enable other buttons
                $('.wgc-individual-controls .button').prop('disabled', false);
            });
        },

        startBulkGeneration: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var nonce = $btn.data('nonce');
            var batchSize = parseInt($('#wgc-batch-size').val(), 10) || 5;
            var postTypes = $('#wgc-bulk-post-types').val() || [];
            var forceRegenerate = $('#wgc-force-regenerate').is(':checked');
            var generateContent = $('#wgc-bulk-content').is(':checked');
            var generateMeta = $('#wgc-bulk-meta').is(':checked');
            var generateTags = $('#wgc-bulk-tags').is(':checked');
            var generateExcerpt = $('#wgc-bulk-excerpt').is(':checked');
            var $status = $('#wgc-bulk-status');
            
            // Debug: Log the values being sent
            console.log('WGC Bulk Debug - Sending values:');
            console.log('generateContent:', generateContent);
            console.log('generateMeta:', generateMeta);
            console.log('generateTags:', generateTags);
            console.log('generateExcerpt:', generateExcerpt);
            
            // Validate inputs
            if (postTypes.length === 0) {
                WGCAdmin.showNotice('error', 'Please select at least one post type.');
                return;
            }
            
            // Clear any existing job monitoring
            if (WGCAdmin.bulkJobInterval) {
                clearInterval(WGCAdmin.bulkJobInterval);
                WGCAdmin.bulkJobInterval = null;
            }
            
            // Show loading state
            $btn.addClass('wgc-loading').prop('disabled', true);
            $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Starting...');
            $status.html('');
            
            // Make AJAX request
            $.post(WGC.ajax_url, {
                action: 'wgc_bulk_generate',
                nonce: nonce,
                batchSize: batchSize,
                postTypes: postTypes,
                forceRegenerate: forceRegenerate,
                generateContent: generateContent,
                generateMeta: generateMeta,
                generateTags: generateTags,
                generateExcerpt: generateExcerpt
            })
            .done(function(response) {
                if (response.success) {
                    if (response.data.job_started) {
                        $status.html(
                            '<div class="wgc-notice info">' +
                            '<strong>Job Started:</strong> ' + (response.data.message || 'Processing in background...') + '<br>' +
                            '<strong>Total Posts:</strong> ' + (response.data.total || 0) +
                            '</div>'
                        );
                        
                        // Start monitoring job status
                        var jobId = response.data.job_id;
                        WGCAdmin.monitorBulkJob(jobId, $btn, $status);
                    } else {
                        $status.html('<div class="wgc-notice warning">' + (response.data.message || 'No posts to process') + '</div>');
                        $btn.removeClass('wgc-loading').prop('disabled', false);
                        $btn.html('Start Bulk Generation');
                    }
                } else {
                    $status.html('<div class="wgc-notice error">Error: ' + (response.data.message || 'Unknown error') + '</div>');
                    $btn.removeClass('wgc-loading').prop('disabled', false);
                    $btn.html('Start Bulk Generation');
                }
            })
            .fail(function() {
                $status.html('<div class="wgc-notice error">Error starting job. Please try again.</div>');
                $btn.removeClass('wgc-loading').prop('disabled', false);
                $btn.html('Start Bulk Generation');
            });
        },

        monitorBulkJob: function(jobId, $btn, $status) {
            var startTime = Date.now();
            var maxDuration = 30 * 60 * 1000; // 30 minutes max
            
            WGCAdmin.bulkJobInterval = setInterval(function() {
                // Check if job has been running too long
                if (Date.now() - startTime > maxDuration) {
                    clearInterval(WGCAdmin.bulkJobInterval);
                    WGCAdmin.bulkJobInterval = null;
                    $btn.removeClass('wgc-loading').prop('disabled', false);
                    $btn.html('Start Bulk Generation');
                    
                    $status.html(
                        '<div class="wgc-notice warning">' +
                        '<strong>⏰ Job Timeout!</strong><br>' +
                        'The job has been running for more than 30 minutes. Please check manually or restart the job.' +
                        '</div>'
                    );
                    return;
                }
                $.post(WGC.ajax_url, {
                    action: 'wgc_bulk_status',
                    jobId: jobId,
                    nonce: $('#wgc-bulk-generate').data('nonce')
                })
                .done(function(response) {
                    if (response.success) {
                        var data = response.data;
                        var progress = Math.round((data.processed / data.total_posts) * 100) || 0;
                        
                        if (data.status === 'completed') {
                            clearInterval(WGCAdmin.bulkJobInterval);
                            WGCAdmin.bulkJobInterval = null;
                            $btn.removeClass('wgc-loading').prop('disabled', false);
                            $btn.html('Start Bulk Generation');
                            
                            var errorCount = data.errors ? data.errors.length : 0;
                            var statusClass = errorCount > 0 ? 'warning' : 'success';
                            
                            $status.html(
                                '<div class="wgc-notice ' + statusClass + '">' +
                                '<strong>✅ Job Completed!</strong><br>' +
                                'Processed: ' + data.processed + '/' + data.total_posts + ' posts<br>' +
                                'Errors: ' + errorCount +
                                (errorCount > 0 ? '<br><small>Check the error log for details.</small>' : '') +
                                '</div>'
                            );
                        } else if (data.status === 'running') {
                            $status.html(
                                '<div class="wgc-notice info">' +
                                '<div class="wgc-progress">' +
                                '<div class="wgc-progress-bar" style="width: ' + progress + '%"></div>' +
                                '</div>' +
                                '<strong>🔄 Processing...</strong> ' + data.processed + '/' + data.total_posts + ' posts (' + progress + '%)<br>' +
                                'Errors: ' + (data.errors ? data.errors.length : 0) +
                                '</div>'
                            );
                        } else if (data.status === 'pending') {
                            // If job is still pending, try to force process it
                            WGCAdmin.forceProcessJob(jobId, $btn, $status);
                        } else if (data.status === 'failed') {
                            clearInterval(WGCAdmin.bulkJobInterval);
                            WGCAdmin.bulkJobInterval = null;
                            $btn.removeClass('wgc-loading').prop('disabled', false);
                            $btn.html('Start Bulk Generation');
                            
                            $status.html(
                                '<div class="wgc-notice error">' +
                                '<strong>❌ Job Failed!</strong><br>' +
                                'Error: ' + (data.error_message || 'Unknown error') +
                                '</div>'
                            );
                        }
                    } else {
                        // Handle API errors
                        clearInterval(WGCAdmin.bulkJobInterval);
                        WGCAdmin.bulkJobInterval = null;
                        $btn.removeClass('wgc-loading').prop('disabled', false);
                        $btn.html('Start Bulk Generation');
                        
                        $status.html(
                            '<div class="wgc-notice error">' +
                            '<strong>❌ Monitoring Error!</strong><br>' +
                            'Error: ' + (response.data.message || 'Unknown error') +
                            '</div>'
                        );
                    }
                })
                .fail(function(xhr, status, error) {
                    clearInterval(WGCAdmin.bulkJobInterval);
                    WGCAdmin.bulkJobInterval = null;
                    $btn.removeClass('wgc-loading').prop('disabled', false);
                    $btn.html('Start Bulk Generation');
                    
                    var errorMessage = 'Error monitoring job status';
                    if (xhr.status === 0) {
                        errorMessage = 'Network error - please check your connection';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied - please refresh the page';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Job not found - it may have been completed or expired';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error - please try again later';
                    }
                    
                    $status.html(
                        '<div class="wgc-notice error">' +
                        '<strong>❌ ' + errorMessage + '</strong><br>' +
                        'Status: ' + xhr.status + ' | Error: ' + error +
                        '</div>'
                    );
                });
            }, 3000); // Check every 3 seconds
        },

        forceProcessJob: function(jobId, $btn, $status) {
            $.post(WGC.ajax_url, {
                action: 'wgc_bulk_force_process',
                jobId: jobId,
                nonce: $('#wgc-bulk-generate').data('nonce')
            })
            .done(function(response) {
                if (response.success) {
                    var data = response.data;
                    if (data.status === 'running' || data.status === 'completed') {
                        // Job is now processing or completed, continue monitoring
                        WGCAdmin.monitorBulkJob(jobId, $btn, $status);
                    } else {
                        // Still pending, show warning
                        $status.html(
                            '<div class="wgc-notice warning">' +
                            '<strong>⚠️ Job Stuck!</strong><br>' +
                            'The job appears to be stuck in pending status. This might be due to WordPress cron issues.<br>' +
                            '<button type="button" class="button button-secondary" onclick="WGCAdmin.forceProcessJob(\'' + jobId + '\', $(\'#wgc-bulk-generate\'), $(\'#wgc-bulk-status\'))">Try Again</button>' +
                            '</div>'
                        );
                    }
                } else {
                    $status.html(
                        '<div class="wgc-notice error">' +
                        '<strong>❌ Force Process Failed!</strong><br>' +
                        'Error: ' + (response.data.message || 'Unknown error') +
                        '</div>'
                    );
                }
            })
            .fail(function() {
                $status.html(
                    '<div class="wgc-notice error">' +
                    '<strong>❌ Network Error!</strong><br>' +
                    'Failed to force process job. Please try again.' +
                    '</div>'
                );
            });
        },

        showNotice: function(type, message) {
            // Remove existing notices
            $('.wgc-notice').remove();
            
            // Create new notice
            var $notice = $('<div class="wgc-notice ' + type + '">' + message + '</div>');
            
            // Insert at the top of the page
            $('.wrap h1').after($notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Utility functions
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WGCAdmin.init();
    });

    // Make WGCAdmin globally available
    window.WGCAdmin = WGCAdmin;

})(jQuery);