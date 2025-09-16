/**
 * Easy Google Reviews - Admin JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initAdminHandlers();
        initFormValidation();
        updateSyncStatus();
    });

    function initAdminHandlers() {
        // Clear cache button
        $('#egr-clear-cache').on('click', function(e) {
            e.preventDefault();

            if (!confirm(egrAdmin.strings.confirm_clear_cache)) {
                return;
            }

            const $btn = $(this);
            const originalText = $btn.text();

            setButtonLoading($btn, true);

            $.ajax({
                url: egrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'egr_clear_cache',
                    nonce: egrAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Cache cleared successfully', 'success');
                    } else {
                        showNotice(response.data || 'Failed to clear cache', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred while clearing cache', 'error');
                },
                complete: function() {
                    setButtonLoading($btn, false, originalText);
                }
            });
        });

        // Test connection button
        $('#egr-test-connection').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const originalText = $btn.text();

            setButtonLoading($btn, true);

            $.ajax({
                url: egrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'egr_test_connection',
                    nonce: egrAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Connection test successful', 'success');
                        console.log('Connection data:', response.data);
                    } else {
                        showNotice(response.data || 'Connection test failed', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred during connection test', 'error');
                },
                complete: function() {
                    setButtonLoading($btn, false, originalText);
                }
            });
        });

        // Manual sync button
        $('#egr-manual-sync').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const originalText = $btn.text();

            setButtonLoading($btn, true);

            $.ajax({
                url: egrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'egr_manual_sync',
                    nonce: egrAdmin.nonce
                },
                timeout: 120000, // 2 minutes
                success: function(response) {
                    if (response.success) {
                        showNotice('Reviews synced successfully', 'success');
                        updateSyncStatusDisplay(response.data);
                    } else {
                        showNotice(response.data || 'Sync failed', 'error');
                    }
                },
                error: function(xhr, status) {
                    if (status === 'timeout') {
                        showNotice('Sync is taking longer than expected. Please check the sync status page.', 'warning');
                    } else {
                        showNotice('An error occurred during sync', 'error');
                    }
                },
                complete: function() {
                    setButtonLoading($btn, false, originalText);
                }
            });
        });

        // Disconnect Google button
        $('#egr-disconnect-google').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to disconnect your Google account? This will clear all authentication data.')) {
                return;
            }

            const $btn = $(this);
            const originalText = $btn.text();

            setButtonLoading($btn, true);

            $.ajax({
                url: egrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'egr_disconnect_google',
                    nonce: egrAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Google account disconnected successfully. Please reload the page.', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotice(response.data || 'Failed to disconnect Google account', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred while disconnecting Google account', 'error');
                },
                complete: function() {
                    setButtonLoading($btn, false, originalText);
                }
            });
        });

        // Refresh token button (if exists)
        $('#egr-refresh-token').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const originalText = $btn.text();

            setButtonLoading($btn, true);

            $.ajax({
                url: egrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'egr_refresh_token',
                    nonce: egrAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Token refreshed successfully', 'success');
                    } else {
                        showNotice(response.data || 'Failed to refresh token', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred while refreshing token', 'error');
                },
                complete: function() {
                    setButtonLoading($btn, false, originalText);
                }
            });
        });
    }

    function initFormValidation() {
        // Validate client ID and secret fields
        const $clientId = $('input[name="egr_google_client_id"]');
        const $clientSecret = $('input[name="egr_google_client_secret"]');

        function validateGoogleCredentials() {
            const hasClientId = $clientId.val().length > 0;
            const hasClientSecret = $clientSecret.val().length > 0;

            // Enable/disable auth button based on credentials
            const $authButton = $('.egr-auth-button');
            if ($authButton.length) {
                $authButton.prop('disabled', !(hasClientId && hasClientSecret));
            }
        }

        $clientId.on('input', validateGoogleCredentials);
        $clientSecret.on('input', validateGoogleCredentials);

        // Initial validation
        validateGoogleCredentials();

        // Validate cache timeout
        $('input[name="egr_cache_timeout"]').on('input', function() {
            const value = parseInt($(this).val());
            if (value < 1 || value > 72) {
                $(this).addClass('invalid');
                showFieldError($(this), 'Cache timeout must be between 1 and 72 hours');
            } else {
                $(this).removeClass('invalid');
                hideFieldError($(this));
            }
        });

        // Validate rows per page
        $('input[name="egr_default_rows_per_page"]').on('input', function() {
            const value = parseInt($(this).val());
            if (value < 1 || value > 100) {
                $(this).addClass('invalid');
                showFieldError($(this), 'Rows per page must be between 1 and 100');
            } else {
                $(this).removeClass('invalid');
                hideFieldError($(this));
            }
        });
    }

    function updateSyncStatus() {
        // Auto-refresh sync status every 30 seconds if we're on the sync page
        if ($('.egr-sync-status').length > 0) {
            setInterval(function() {
                $.ajax({
                    url: egrAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'egr_get_sync_status',
                        nonce: egrAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            updateSyncStatusDisplay(response.data);
                        }
                    }
                });
            }, 30000);
        }
    }

    function updateSyncStatusDisplay(data) {
        if (!data) return;

        // Update last sync
        if (data.last_sync) {
            $('.egr-sync-status tr:first-child td').text(data.last_sync);
        }

        // Update total reviews
        if (data.total_reviews !== undefined) {
            $('.egr-sync-status tr:nth-child(3) td').text(data.total_reviews);
        }

        // Update five star count
        if (data.five_star_count !== undefined) {
            $('.egr-sync-status tr:nth-child(4) td').text(data.five_star_count);
        }

        // Update sync status
        const $statusCell = $('.egr-sync-status tr:last-child td');
        if (data.in_progress) {
            $statusCell.html('<span class="egr-status egr-status-progress">In Progress</span>');
            $('#egr-manual-sync').prop('disabled', true);
        } else {
            $statusCell.html('<span class="egr-status egr-status-idle">Idle</span>');
            $('#egr-manual-sync').prop('disabled', false);
        }
    }

    function setButtonLoading($button, loading, originalText) {
        if (loading) {
            $button.addClass('egr-button-loading').prop('disabled', true);
            if (originalText) {
                $button.data('original-text', originalText);
            }
            $button.text('Loading...');
        } else {
            $button.removeClass('egr-button-loading').prop('disabled', false);
            const text = originalText || $button.data('original-text') || 'Button';
            $button.text(text);
        }
    }

    function showNotice(message, type) {
        type = type || 'info';

        const $notice = $('<div class="notice notice-' + type + ' is-dismissible egr-notice">')
            .append('<p>' + message + '</p>')
            .append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');

        // Remove existing notices
        $('.egr-notice').remove();

        // Add new notice
        if ($('.wrap h1').length > 0) {
            $('.wrap h1').after($notice);
        } else {
            $('.wrap').prepend($notice);
        }

        // Handle dismiss
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(function() {
                $notice.remove();
            });
        });

        // Auto-dismiss success notices
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        }

        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 100
        }, 500);
    }

    function showFieldError($field, message) {
        hideFieldError($field);

        const $error = $('<span class="egr-field-error" style="color: #dc3232; font-size: 12px; display: block; margin-top: 5px;">')
            .text(message);

        $field.after($error);
    }

    function hideFieldError($field) {
        $field.siblings('.egr-field-error').remove();
    }

    // Handle settings form submission
    $('form').on('submit', function(e) {
        const $form = $(this);
        const $invalidFields = $form.find('.invalid');

        if ($invalidFields.length > 0) {
            e.preventDefault();
            showNotice('Please fix the errors in the form before submitting', 'error');
            $invalidFields.first().focus();
            return false;
        }
    });

    // Add visual feedback for AJAX operations
    $(document).ajaxStart(function() {
        $('body').addClass('egr-ajax-loading');
    }).ajaxStop(function() {
        $('body').removeClass('egr-ajax-loading');
    });

})(jQuery);