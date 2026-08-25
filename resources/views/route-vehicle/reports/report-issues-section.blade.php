<div class="reported-issues-section px-2 pt-2">
    {{-- Header Area with Title, Subtitle, and Search --}}
    <div class="issues-header-area">
        <div>
            <h4 class="trip-performance-title">{{ __('reported_issues') }}/{{ __('feedback') }}</h4>
            <p class="trip-performance-subtitle">{{ __('feedback_and_alerts_from_parents_and_drivers') }}</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="issues-search-container">
                <i class="fa fa-search"></i>
                <input type="text" id="issues-search" class="issues-search-input" placeholder="{{ __('search_issues') }}...">
            </div>
        </div>
    </div>

    {{-- List Container --}}
    <div id="reported-issues-list" class="reported-issues-list">
        {{-- Cards will be rendered here via AJAX --}}
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem; border-width: 0.2em;">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>

    {{-- Pagination / Empty State --}}
    <div id="issues-empty-state" class="text-center py-5 d-none">
        <div class="mb-3">
            <i class="fa fa-info-circle text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
        </div>
        <h5 class="text-muted">{{ __('no_issues_found') }}</h5>
        <p class="small text-muted">{{ __('try_adjusting_your_search_to_find_what_you_are_looking_for') }}</p>
    </div>
</div>

<script>
    $(function() {
        let searchTimer;
        
        /**
         * Fetches issues from the server
         */
        function fetchIssues(search = '') {
            const $list = $('#reported-issues-list');
            const $empty = $('#issues-empty-state');
            
            // Show loading spinner
            $list.removeClass('d-none').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem; border-width: 0.2em;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);
            $empty.addClass('d-none');

            const url = "{{ route('route-vehicle.trip-reports', $id) }}";
            $.ajax({
                url: url,
                type: 'GET',
                data: { search: search, limit: 100 },
                success: function(response) {
                    renderIssues(response.rows);
                },
                error: function() {
                    $list.html('<div class="alert alert-danger mx-3">Error loading issues. Please try again later.</div>');
                }
            });
        }

        /**
         * Renders the list of cards
         */
        function renderIssues(issues) {
            const $list = $('#reported-issues-list');
            const $empty = $('#issues-empty-state');
            $list.empty();

            if (!issues || issues.length === 0) {
                $list.addClass('d-none');
                $empty.removeClass('d-none');
                return;
            }

            issues.forEach(issue => {
                const iconClass = getIconClass(issue.title || issue.description);
                
                const cardHtml = `
                    <div class="issue-card">
                        <div class="issue-icon-container ${iconClass.bg}">
                            <i class="${iconClass.icon}"></i>
                        </div>
                        <div class="issue-content">
                            <div class="issue-header">
                                <h5 class="issue-title">${issue.title || 'Reported Issue'}</h5>
                                
                            </div>
                            <p class="issue-description">${issue.description}</p>
                            <div class="issue-meta">
                                <div class="meta-item">
                                    <i class="fa fa-user-circle"></i>
                                    <span><strong>${issue.created_by ? issue.created_by.full_name : 'Unknown'}</strong> (${issue.created_by ? issue.created_by.role : 'System'})</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa fa-calendar"></i>
                                    <span>${issue.date}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa fa-clock"></i>
                                    <span>${issue.time}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $list.append(cardHtml);
            });
        }

        /**
         * Determines icon and color based on content
         */
        function getIconClass(text) {
            text = text.toLowerCase();
            if (text.includes('tyre') || text.includes('puncture') || text.includes('breakdown') || text.includes('engine')) {
                return { bg: 'icon-warning', icon: 'fa fa-warning' };
            }
            if (text.includes('late') || text.includes('delay') || text.includes('time')) {
                return { bg: 'icon-info', icon: 'fa fa-clock' };
            }
            if (text.includes('unsafe') || text.includes('speed') || text.includes('rash') || text.includes('driving')) {
                return { bg: 'icon-danger', icon: 'fa fa-shield' };
            }
            return { bg: 'icon-info', icon: 'fa fa-info-circle' };
        }

        /**
         * Search Input Handler
         */
        $('#issues-search').on('input', function() {
            clearTimeout(searchTimer);
            const search = $(this).val();
            searchTimer = setTimeout(() => fetchIssues(search), 400);
        });

        // Initialize
        fetchIssues();
    });
</script>