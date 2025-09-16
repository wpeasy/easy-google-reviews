/**
 * Easy Google Reviews - Instructions Page JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initTabs();
        initCopyButtons();
        initUrlHash();
    });

    function initTabs() {
        const $tabButtons = $('.egr-tabs__button');
        const $tabPanels = $('.egr-tabs__panel');

        $tabButtons.on('click', function(e) {
            e.preventDefault();

            const $button = $(this);
            const targetTab = $button.data('tab');

            // Update button states
            $tabButtons.removeClass('egr-tabs__button--active');
            $button.addClass('egr-tabs__button--active');

            // Update panel states
            $tabPanels.removeClass('egr-tabs__panel--active');
            $(`[data-panel="${targetTab}"]`).addClass('egr-tabs__panel--active');

            // Update URL hash
            history.replaceState(null, null, '#' + targetTab);

            // Scroll to top of tabs
            $('.egr-tabs').get(0).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });

        // Keyboard navigation
        $tabButtons.on('keydown', function(e) {
            const $current = $(this);
            let $target = null;

            switch (e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    $target = $current.prev('.egr-tabs__button');
                    if ($target.length === 0) {
                        $target = $tabButtons.last();
                    }
                    break;

                case 'ArrowRight':
                    e.preventDefault();
                    $target = $current.next('.egr-tabs__button');
                    if ($target.length === 0) {
                        $target = $tabButtons.first();
                    }
                    break;

                case 'Home':
                    e.preventDefault();
                    $target = $tabButtons.first();
                    break;

                case 'End':
                    e.preventDefault();
                    $target = $tabButtons.last();
                    break;

                case 'Enter':
                case ' ':
                    e.preventDefault();
                    $current.click();
                    return;
            }

            if ($target && $target.length > 0) {
                $target.focus().click();
            }
        });
    }

    function initCopyButtons() {
        $('.egr-copy-btn').on('click', function(e) {
            e.preventDefault();

            const $button = $(this);
            const textToCopy = $button.data('copy') || $button.siblings('code').text();

            if (!textToCopy) {
                return;
            }

            // Create temporary textarea for copying
            const $temp = $('<textarea>');
            $temp.val(textToCopy);
            $('body').append($temp);
            $temp.select();

            try {
                // Try modern clipboard API first
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        showCopySuccess($button);
                    }).catch(function() {
                        // Fallback to old method
                        fallbackCopy($button, $temp);
                    });
                } else {
                    // Fallback for older browsers
                    fallbackCopy($button, $temp);
                }
            } catch (err) {
                console.warn('Copy to clipboard failed:', err);
                showCopyError($button);
            }

            $temp.remove();
        });
    }

    function fallbackCopy($button, $temp) {
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess($button);
            } else {
                showCopyError($button);
            }
        } catch (err) {
            console.warn('Fallback copy failed:', err);
            showCopyError($button);
        }
    }

    function showCopySuccess($button) {
        const originalText = $button.text();

        $button
            .addClass('copied')
            .text('Copied!')
            .prop('disabled', true);

        setTimeout(function() {
            $button
                .removeClass('copied')
                .text(originalText)
                .prop('disabled', false);
        }, 2000);
    }

    function showCopyError($button) {
        const originalText = $button.text();

        $button
            .css('background-color', '#dc3545')
            .text('Failed')
            .prop('disabled', true);

        setTimeout(function() {
            $button
                .css('background-color', '')
                .text(originalText)
                .prop('disabled', false);
        }, 2000);
    }

    function initUrlHash() {
        // Check if there's a hash in the URL on page load
        const hash = window.location.hash.replace('#', '');

        if (hash) {
            const $targetButton = $(`.egr-tabs__button[data-tab="${hash}"]`);

            if ($targetButton.length > 0) {
                $targetButton.click();
            }
        }

        // Handle browser back/forward
        $(window).on('hashchange', function() {
            const newHash = window.location.hash.replace('#', '');

            if (newHash) {
                const $targetButton = $(`.egr-tabs__button[data-tab="${newHash}"]`);

                if ($targetButton.length > 0) {
                    $targetButton.click();
                }
            } else {
                // No hash, go to first tab
                $('.egr-tabs__button').first().click();
            }
        });
    }

    // Helper function to highlight search terms
    function highlightSearchTerms(searchTerm) {
        if (!searchTerm || searchTerm.length < 2) {
            // Remove existing highlights
            $('.egr-highlight').contents().unwrap();
            return;
        }

        const $content = $('.egr-instructions__content');

        // Remove existing highlights
        $('.egr-highlight').contents().unwrap();

        // Find and highlight new terms
        $content.find('*').contents().filter(function() {
            return this.nodeType === 3; // Text nodes only
        }).each(function() {
            const text = this.textContent;
            const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');

            if (regex.test(text)) {
                const highlighted = text.replace(regex, '<span class="egr-highlight">$1</span>');
                $(this).replaceWith(highlighted);
            }
        });
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Add search functionality (if search box exists)
    const $searchBox = $('#egr-search');
    if ($searchBox.length > 0) {
        let searchTimeout;

        $searchBox.on('input', function() {
            const searchTerm = $(this).val();

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                highlightSearchTerms(searchTerm);
            }, 300);
        });

        // Clear search
        $('.egr-clear-search').on('click', function() {
            $searchBox.val('').trigger('input');
        });
    }

    // Add smooth scrolling for anchor links
    $('.egr-instructions__content').on('click', 'a[href^="#"]', function(e) {
        const href = $(this).attr('href');
        const $target = $(href);

        if ($target.length > 0) {
            e.preventDefault();

            $target.get(0).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            // Update URL
            history.replaceState(null, null, href);
        }
    });

    // Add step completion tracking (visual enhancement)
    $('.egr-step').each(function(index) {
        const $step = $(this);
        const stepNumber = index + 1;

        // Add step number
        $step.attr('data-step', stepNumber);

        // Add completion checkbox (optional enhancement)
        const $checkbox = $('<input type="checkbox" class="egr-step-checkbox">');
        $checkbox.on('change', function() {
            if (this.checked) {
                $step.addClass('egr-step--completed');
            } else {
                $step.removeClass('egr-step--completed');
            }

            // Save state to localStorage
            const stepId = `egr-step-${stepNumber}`;
            localStorage.setItem(stepId, this.checked);
        });

        // Restore state from localStorage
        const stepId = `egr-step-${stepNumber}`;
        const isCompleted = localStorage.getItem(stepId) === 'true';

        if (isCompleted) {
            $checkbox.prop('checked', true);
            $step.addClass('egr-step--completed');
        }

        // Add checkbox to step header
        $step.find('h3').append($checkbox);
    });

    // Add progress indicator
    function updateProgress() {
        const $steps = $('.egr-step');
        const $completedSteps = $('.egr-step--completed');
        const progress = ($completedSteps.length / $steps.length) * 100;

        $('.egr-progress-bar').css('width', progress + '%');
        $('.egr-progress-text').text(`${$completedSteps.length} of ${$steps.length} steps completed`);
    }

    // Update progress when steps are completed
    $('.egr-step-checkbox').on('change', updateProgress);

    // Initial progress update
    updateProgress();

    // Add print functionality
    $('.egr-print-instructions').on('click', function(e) {
        e.preventDefault();
        window.print();
    });

    // Add expand/collapse for long code blocks
    $('.egr-code-block').each(function() {
        const $codeBlock = $(this);
        const $code = $codeBlock.find('code');

        if ($code.text().length > 100) {
            $codeBlock.addClass('egr-code-block--collapsible');

            const $toggleBtn = $('<button type="button" class="egr-code-toggle">Show More</button>');

            $toggleBtn.on('click', function() {
                if ($codeBlock.hasClass('egr-code-block--expanded')) {
                    $codeBlock.removeClass('egr-code-block--expanded');
                    $toggleBtn.text('Show More');
                } else {
                    $codeBlock.addClass('egr-code-block--expanded');
                    $toggleBtn.text('Show Less');
                }
            });

            $codeBlock.append($toggleBtn);
        }
    });

})(jQuery);

// Add CSS for additional enhancements
jQuery(document).ready(function($) {
    const additionalCSS = `
        <style>
        .egr-step--completed {
            background-color: #d4edda !important;
            border-color: #c3e6cb !important;
        }

        .egr-step--completed h3 {
            color: #155724 !important;
        }

        .egr-step-checkbox {
            margin-left: auto;
            transform: scale(1.2);
        }

        .egr-highlight {
            background-color: yellow;
            padding: 1px 2px;
            border-radius: 2px;
        }

        .egr-code-block--collapsible code {
            max-height: 60px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .egr-code-block--expanded code {
            max-height: none;
        }

        .egr-code-toggle {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .egr-code-toggle:hover {
            background: #5a6268;
        }
        </style>
    `;

    $('head').append(additionalCSS);
});