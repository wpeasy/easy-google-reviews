/**
 * Easy Google Reviews - Frontend JavaScript
 */
(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initPagination();
    }

    function initPagination() {
        const paginatedContainers = document.querySelectorAll('.egr__reviews-paginated');

        paginatedContainers.forEach(container => {
            const prevBtns = container.querySelectorAll('.egr__pagination-btn--prev');
            const nextBtns = container.querySelectorAll('.egr__pagination-btn--next');
            const pages = container.querySelectorAll('.egr__reviews-page');
            const currentSpans = container.querySelectorAll('.egr__pagination-current');

            let currentPage = 1;
            const totalPages = pages.length;

            // Initialize pagination state
            updatePaginationState();

            // Add event listeners to prev buttons
            prevBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        showPage(currentPage);
                        updatePaginationState();
                    }
                });
            });

            // Add event listeners to next buttons
            nextBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage++;
                        showPage(currentPage);
                        updatePaginationState();
                    }
                });
            });

            function showPage(pageNum) {
                // Hide all pages
                pages.forEach(page => {
                    page.classList.remove('egr__reviews-page--active');
                });

                // Show target page
                const targetPage = container.querySelector(`[data-page="${pageNum}"]`);
                if (targetPage) {
                    targetPage.classList.add('egr__reviews-page--active');
                }

                // Scroll to top of reviews
                container.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            function updatePaginationState() {
                // Update current page display
                currentSpans.forEach(span => {
                    span.textContent = currentPage;
                });

                // Update prev button state
                prevBtns.forEach(btn => {
                    btn.disabled = currentPage === 1;
                });

                // Update next button state
                nextBtns.forEach(btn => {
                    btn.disabled = currentPage === totalPages;
                });
            }
        });
    }

    // Keyboard navigation for pagination
    document.addEventListener('keydown', function(e) {
        const focusedElement = document.activeElement;

        if (focusedElement && focusedElement.matches('.egr__pagination-btn')) {
            const container = focusedElement.closest('.egr__reviews-paginated');

            if (!container) return;

            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                const prevBtn = container.querySelector('.egr__pagination-btn--prev');
                if (prevBtn && !prevBtn.disabled) {
                    prevBtn.click();
                    prevBtn.focus();
                }
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                const nextBtn = container.querySelector('.egr__pagination-btn--next');
                if (nextBtn && !nextBtn.disabled) {
                    nextBtn.click();
                    nextBtn.focus();
                }
            }
        }
    });

    // Add support for Alpine.js if available
    if (window.Alpine) {
        document.addEventListener('alpine:init', () => {
            Alpine.data('egrReviews', () => ({
                currentPage: 1,
                totalPages: 1,
                rowsPerPage: 10,
                reviews: [],

                init() {
                    this.totalPages = Math.ceil(this.reviews.length / this.rowsPerPage);
                },

                get paginatedReviews() {
                    const start = (this.currentPage - 1) * this.rowsPerPage;
                    const end = start + this.rowsPerPage;
                    return this.reviews.slice(start, end);
                },

                nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                    }
                },

                prevPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                    }
                },

                get canGoNext() {
                    return this.currentPage < this.totalPages;
                },

                get canGoPrev() {
                    return this.currentPage > 1;
                }
            }));
        });
    }

    // Utility function to format dates
    window.egrFormatDate = function(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (e) {
            return dateString;
        }
    };

    // Utility function to generate star rating HTML
    window.egrStarRating = function(rating) {
        const ratingMap = {
            'ONE': 1,
            'TWO': 2,
            'THREE': 3,
            'FOUR': 4,
            'FIVE': 5
        };

        const stars = ratingMap[rating] || 0;
        let html = '<div class="egr__stars">';

        for (let i = 1; i <= 5; i++) {
            const filled = i <= stars ? ' egr__star--filled' : '';
            html += `<span class="egr__star${filled}">★</span>`;
        }

        html += '</div>';
        return html;
    };

})();