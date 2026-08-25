<!-- partial:../../partials/_sidebar.html -->
<nav class="sidebar sidebar-offcanvas" id="sidebar">

    <div class="sidebar-search pl-3 pr-3">
        <input type="text" id="menu-search" placeholder="{{ __('search_menu') }}"
            class="form-control menu-search border-theme form-control-sm">
    </div>

    <div class="sidebar-search pl-3 pr-3 mt-2">
        <input type="text" id="menu-search-mini" placeholder="{{ __('search_menu') }}"
            class="form-control d-lg-none border-theme">
    </div>

    <style>
        /* ============================================
           MODERN SIDEBAR - CLEAN IMPLEMENTATION
           ============================================ */

        /* Sidebar container - single scrollbar */




        :root {
            --primary-color-sidebar: {{ $systemSettings['theme_color'] ?? '#22577a' }};
        }

        .sidebar {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            height: calc(100vh - 70px) !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 0, 0, 0.3);
        }

        /* Remove all nested scrollbars */
        .sidebar .nav,
        .sidebar .nav.sub-menu,
        .sidebar .nav .nav-item .collapse {
            overflow: visible !important;
            max-height: none !important;
        }

        /* Remove bottom margin that causes empty space */
        .sidebar .nav {
            margin-bottom: 0 !important;
            padding-bottom: 1rem !important;
            /* Small padding for visual breathing room */
        }

        /* Nav item padding */
        .sidebar .nav .nav-item {
            padding: 0 0.175rem !important;
            position: relative;
        }

        /* Nav link styling */
        .sidebar .nav .nav-item .nav-link {
            display: flex !important;
            align-items: center;
            padding: 0.75rem 0.5rem !important;
            color: #3e4b5b;
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
            border-radius: 4px;
            margin: 2px 0;
        }

        .sidebar .nav .nav-item .nav-link:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }

        /* Parent link always visible */
        .sidebar .nav .nav-item>.nav-link[data-toggle="collapse"] {
            position: relative;
            z-index: 1;
        }

        /* Icon styling */
        .sidebar .nav .nav-item .nav-link i.menu-icon {
            margin-right: 0.75rem !important;
            width: 20px !important;
            text-align: center;
            flex-shrink: 0;
            font-size: 1.125rem;
        }

        /* Menu arrow */
        .sidebar .nav .nav-item .nav-link i.menu-arrow {
            margin-left: auto;
            flex-shrink: 0;
            transition: transform 0.25s ease;
            font-size: 1rem;
        }

        .sidebar .nav .nav-item .nav-link[aria-expanded="true"] i.menu-arrow {
            transform: rotate(90deg);
        }

        /* Menu title */
        .sidebar .nav .nav-item .nav-link .menu-title {
            flex: 1;
            white-space: nowrap;
            /* overflow: hidden; */
            text-overflow: ellipsis;
            font-size: 0.875rem;
        }

        /* Collapse container - smooth animation */
        .sidebar .nav .nav-item .collapse {
            overflow: hidden;
            transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
            height: 0;
            opacity: 0;
        }

        .sidebar .nav .nav-item .collapse.show {
            height: auto;
            opacity: 1;
            overflow: visible;
        }

        .sidebar .nav .nav-item .collapse:not(.show) {
            display: block !important;
            height: 0 !important;
            opacity: 0;
            overflow: hidden !important;
        }

        /* CRITICAL: Prevent nested collapses from auto-expanding */
        .sidebar .nav .nav-item .collapse.show .collapse:not(.show) {
            height: 0 !important;
            opacity: 0;
        }

        /* Sub-menu indentation */
        .sidebar .nav.sub-menu {
            padding-left: 0;
        }

        .sidebar .nav.sub-menu .nav-item {
            padding-left: 1.5rem !important;
            padding-right: 0.875rem !important;
        }

        .sidebar .nav.sub-menu .nav.sub-menu .nav-item {
            padding-left: 2.0rem !important;
        }

        /* Active state */
        .sidebar .nav .nav-item.active>.nav-link,
        .sidebar .nav .nav-item .nav-link.active {
            background-color: rgba(0, 123, 255, 0.12) !important;
            /* color: #007bff !important; */
            color: var(--primary-color-sidebar) !important;
            font-weight: 500;
        }

        .sidebar .nav .nav-item.active>.nav-link i.menu-icon,
        .sidebar .nav .nav-item .nav-link.active i.menu-icon {
            /* color: #007bff !important; */
            color: var(--primary-color-sidebar) !important;
        }

        /* Parent active state */
        .sidebar .nav .nav-item.parent-active>.nav-link[data-toggle="collapse"] {
            font-weight: 600;
            /* color: #007bff !important; */
            color: var(--primary-color-sidebar) !important;
        }

        /* Expanded parent link styling */
        .sidebar .nav .nav-item .nav-link[aria-expanded="true"] {
            font-weight: 500;
        }
    </style>

    <script>
        (function($) {
            'use strict';

            // Modern Sidebar Manager - Clean Implementation
            var SidebarManager = {
                isAnimating: false,

                // Smooth expand/collapse with height animation
                toggleCollapse: function($toggle, $collapse) {
                    if (this.isAnimating) return;

                    var isExpanding = !$collapse.hasClass('show');

                    if (isExpanding) {
                        // CRITICAL: Collapse ALL nested collapses first
                        $collapse.find('.collapse.show').each(function() {
                            SidebarManager.collapseItem($(this));
                        });

                        // Expand this collapse
                        this.expandItem($collapse);
                        $toggle.attr('aria-expanded', 'true');
                    } else {
                        // Collapse this and all nested
                        this.collapseItem($collapse);
                        $toggle.attr('aria-expanded', 'false');
                    }
                },

                expandItem: function($collapse) {
                    if ($collapse.hasClass('show')) return;

                    this.isAnimating = true;
                    var $content = $collapse.children().first();
                    var height = $content.outerHeight(true);

                    $collapse.css({
                        'height': '0px',
                        'opacity': '0',
                        'display': 'block'
                    }).addClass('show');

                    // Force reflow
                    $collapse[0].offsetHeight;

                    $collapse.css({
                        'height': height + 'px',
                        'opacity': '1'
                    });

                    var self = this;
                    setTimeout(function() {
                        $collapse.css('height', 'auto');
                        self.isAnimating = false;
                    }, 200);
                },

                collapseItem: function($collapse) {
                    if (!$collapse.hasClass('show')) return;

                    this.isAnimating = true;
                    var height = $collapse[0].scrollHeight;

                    $collapse.css('height', height + 'px');
                    $collapse[0].offsetHeight; // Force reflow

                    $collapse.css({
                        'height': '0px',
                        'opacity': '0'
                    });

                    var self = this;
                    setTimeout(function() {
                        $collapse.removeClass('show');
                        $collapse.css({
                            'display': '',
                            'height': '',
                            'opacity': ''
                        });
                        self.isAnimating = false;
                    }, 200);

                    // Collapse all nested items
                    $collapse.find('.collapse.show').each(function() {
                        var $nested = $(this);
                        var $nestedToggle = $nested.siblings('.nav-link[data-toggle="collapse"]');
                        if ($nestedToggle.length) {
                            $nestedToggle.attr('aria-expanded', 'false');
                        }
                        $nested.removeClass('show').css({
                            'display': '',
                            'height': '',
                            'opacity': ''
                        });
                    });
                },

                // Set active state and expand only direct path
                setActiveState: function() {
                    var currentUrl = window.location.href;
                    var currentPath = window.location.pathname;
                    var $activeLink = null;

                    // Normalize current URL and path
                    var normalizedCurrent = currentUrl.replace(/\/$/, '').split('?')[
                        0]; // Remove trailing slash and query params
                    var normalizedPath = currentPath.replace(/\/$/, '');

                    // Helper function to normalize and compare URLs
                    function normalizeUrl(url) {
                        if (!url) return '';
                        // Convert to absolute URL if relative
                        if (url.indexOf('http') !== 0 && url.indexOf('//') !== 0) {
                            var baseUrl = window.location.origin;
                            url = baseUrl + (url.indexOf('/') === 0 ? url : '/' + url);
                        }
                        // Remove trailing slash, query params, and hash
                        return url.replace(/\/$/, '').split('?')[0].split('#')[0];
                    }

                    // Helper function to extract path from URL
                    function getPathFromUrl(url) {
                        try {
                            if (url.indexOf('http') === 0 || url.indexOf('//') === 0) {
                                var urlObj = new URL(url);
                                return urlObj.pathname.replace(/\/$/, '');
                            } else {
                                // Relative URL
                                return url.split('?')[0].split('#')[0].replace(/\/$/, '');
                            }
                        } catch (e) {
                            // Fallback for relative URLs
                            return url.split('?')[0].split('#')[0].replace(/\/$/, '');
                        }
                    }

                    // Helper function to check if URL matches (using path segments)
                    function urlMatches(href, currentUrl, currentPath) {
                        var hrefPath = getPathFromUrl(href);
                        var currentPathClean = normalizedPath;

                        // Normalize paths
                        hrefPath = hrefPath || '';
                        currentPathClean = currentPathClean || '';

                        // Exact match (highest priority)
                        if (currentPathClean === hrefPath) {
                            return {
                                match: true,
                                priority: 1,
                                length: hrefPath.length
                            };
                        }

                        // Path segment matching - current path must start with href path
                        // and the next character must be / or end of string (to avoid partial matches)
                        // Example: /students/reset-password should match /students/reset-password, not /students
                        if (hrefPath && currentPathClean.indexOf(hrefPath) === 0) {
                            var nextChar = currentPathClean.charAt(hrefPath.length);
                            if (nextChar === '' || nextChar === '/') {
                                // Valid path prefix match - use length as priority (longer = more specific)
                                return {
                                    match: true,
                                    priority: 2,
                                    length: hrefPath.length
                                };
                            }
                        }

                        return {
                            match: false,
                            priority: 0,
                            length: 0
                        };
                    }

                    // Collect all matching links and find the best one
                    var matches = [];
                    $('#sidebar .nav-link').each(function() {
                        var $link = $(this);
                        var href = $link.attr('href');

                        if (href && !$link.attr('data-toggle')) {
                            var match = urlMatches(href, normalizedCurrent, normalizedPath);

                            if (match.match) {
                                matches.push({
                                    $link: $link,
                                    priority: match.priority, // 1 = exact, 2 = prefix
                                    length: match.length
                                });
                            }
                        }
                    });

                    // Sort matches: exact matches first, then by length (longest = most specific)
                    matches.sort(function(a, b) {
                        if (a.priority !== b.priority) {
                            return a.priority - b.priority; // Exact match (1) before prefix match (2)
                        }
                        return b.length - a.length; // Longer paths first
                    });

                    // Select the best match (longest/most specific)
                    if (matches.length > 0) {
                        $activeLink = matches[0].$link;
                    }

                    // Clear all states
                    $('#sidebar .nav-link').removeClass('active');
                    $('#sidebar .nav-item').removeClass('active parent-active');

                    // Don't collapse all - only expand the path to active item
                    // This preserves user's manually expanded sections

                    if ($activeLink) {
                        // Mark active
                        $activeLink.addClass('active');
                        $activeLink.closest('.nav-item').addClass('active');

                        // Expand ONLY direct path to active item (no nested auto-expansion)
                        var $current = $activeLink.closest('.nav-item');
                        var pathToExpand = [];

                        // Build path from active item to root
                        while ($current.length) {
                            pathToExpand.unshift($current);
                            $current = $current.parent().closest('.nav-item');
                        }

                        // Expand only the direct parent collapses
                        pathToExpand.forEach(function($item) {
                            $item.addClass('parent-active');
                            var $collapse = $item.find('> .collapse').first();
                            var $toggle = $item.find('> .nav-link[data-toggle="collapse"]').first();

                            if ($collapse.length && $toggle.length) {
                                if (!$collapse.hasClass('show')) {
                                    SidebarManager.expandItem($collapse);
                                }
                                $toggle.attr('aria-expanded', 'true');
                            }
                        });
                    }
                }
            };

            $(document).ready(function() {
                // CRITICAL: Prevent misc.js from closing other collapses
                // This must run before misc.js handlers
                $(document).on('show.bs.collapse', '#sidebar .collapse', function(e) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                });

                // Wait for DOM to be fully ready, then override misc.js
                setTimeout(function() {
                    // Remove misc.js handler for sidebar
                    $('#sidebar').off('show.bs.collapse', '.collapse');
                }, 50);

                // Handle collapse toggle clicks
                $('#sidebar').on('click', '.nav-link[data-toggle="collapse"]', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $toggle = $(this);
                    var $navItem = $toggle.closest('.nav-item');
                    var $collapse = $navItem.find('> .collapse').first();

                    if ($collapse.length) {
                        SidebarManager.toggleCollapse($toggle, $collapse);
                    }
                });

                // Handle regular link clicks
                $('#sidebar').on('click', '.nav-link:not([data-toggle="collapse"])', function() {
                    var $link = $(this);

                    $('#sidebar .nav-link').removeClass('active');
                    $('#sidebar .nav-item').removeClass('active parent-active');

                    $link.addClass('active');
                    $link.closest('.nav-item').addClass('active');

                    // Expand only direct path
                    var $current = $link.closest('.nav-item');
                    while ($current.length) {
                        $current.addClass('parent-active');
                        var $collapse = $current.find('> .collapse').first();
                        var $toggle = $current.find('> .nav-link[data-toggle="collapse"]').first();

                        if ($collapse.length && $toggle.length) {
                            if (!$collapse.hasClass('show')) {
                                SidebarManager.expandItem($collapse);
                            }
                            $toggle.attr('aria-expanded', 'true');
                        }

                        $current = $current.parent().closest('.nav-item');
                    }
                });

                // Set active state on load
                SidebarManager.setActiveState();
                setTimeout(function() {
                    SidebarManager.setActiveState();
                }, 100);
            });
        })(jQuery);
    </script>

    <ul class="nav">
        {{-- dashboard --}}
        <li class="nav-item">
            <a href="{{ url('/dashboard') }}" class="nav-link">
                <i class="fa fa-home menu-icon"></i>
                <span class="menu-title">{{ __('dashboard') }}</span>
            </a>
        </li>

        {{-- ============================================ --}}
        {{-- SCHOOLS GROUP (Main Menu) --}}
        {{-- ============================================ --}}
        @canany(['schools-list', 'schools-create', 'schools-edit', 'schools-delete'])
            @if (!Auth::user()->school_id)
                <li class="nav-item">
                    <a class="nav-link" data-toggle="collapse" href="#schools-main-menu" aria-expanded="false"
                        aria-controls="schools-main-menu">
                        <i class="fa fa-university menu-icon"></i>
                        <span class="menu-title">{{ __('schools') }}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse" id="schools-main-menu">
                        <ul class="nav flex-column sub-menu">
                            @canany(['schools-list', 'schools-create', 'schools-edit', 'schools-delete'])
                                <li class="nav-item">
                                    <a href="{{ route('schools.index') }}" class="nav-link">
                                        <i class="fa fa-building menu-icon"></i>
                                        <span class="menu-title">{{ __('schools_details') }}</span>
                                    </a>
                                </li>
                            @endcanany
                            {{-- @if (isset($systemSettings['school_inquiry']) && $systemSettings['school_inquiry'] == 1) --}}
                                @canany(['schools-list', 'schools-create', 'schools-edit', 'schools-delete'])
                                    <li class="nav-item">
                                        <a href="{{ route('inquiry.index') }}" class="nav-link">
                                            <i class="fa fa-inbox menu-icon"></i>
                                            <span class="menu-title">{{ __('school_inquires') }}</span>
                                        </a>
                                    </li>
                                @endcanany
                            {{-- @endif --}}
                        </ul>
                    </div>
                </li>
            @endif
        @endcanany

        {{-- ============================================ --}}
        {{-- ACADEMIC MANAGEMENT GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasAcademicManagement = false;
            $academicPermissions = [
                'medium-list',
                'section-list',
                'subject-list',
                'class-list',
                'form-fields-list',
                'form-fields-create',
                'form-fields-edit',
                'form-fields-delete',
                'student-create',
                'student-list',
                'student-reset-password',
                'class-teacher',
                'guardian-create',
                'promote-student-list',
                'transfer-student-list',
                'assign-elective-subject-list',
                'teacher-create',
                'student-diary-list',
                'lesson-list',
                'lesson-create',
                'lesson-edit',
                'lesson-delete',
                'topic-list',
                'topic-create',
                'topic-edit',
                'topic-delete',
                'assignment-create',
                'assignment-submission',
            ];
            foreach ($academicPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasAcademicManagement = true;
                    break;
                }
            }
            if (Auth::user()->hasRole('Teacher')) {
                $hasAcademicManagement = true;
            }
        @endphp

        @if ($hasAcademicManagement)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#academic-management-group" aria-expanded="false"
                    aria-controls="academic-management-group">
                    <i class="fa fa-university menu-icon"></i>
                    <span class="menu-title">{{ __('Academic Management') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="academic-management-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Academics --}}
                        @canany(['medium-list', 'section-list', 'subject-list', 'class-list', 'subject-list'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#academics-menu" aria-expanded="false"
                                    aria-controls="academics-menu">
                                    <i class="fa fa-university menu-icon"></i>
                                    <span class="menu-title">{{ __('academics') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="academics-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('medium-list')
                                            <li class="nav-item"><a href="{{ route('mediums.index') }}" class="nav-link">
                                                    {{ __('medium') }}
                                                </a></li>
                                        @endcan

                                        @can('section-list')
                                            <li class="nav-item"><a href="{{ route('section.index') }}" class="nav-link">
                                                    {{ __('section') }}
                                                </a></li>
                                        @endcan

                                        @can('subject-list')
                                            <li class="nav-item"><a href="{{ route('subjects.index') }}" class="nav-link">
                                                    {{ __('subject') }}
                                                </a></li>
                                        @endcan

                                        @can('semester-list')
                                            <li class="nav-item"><a href="{{ route('semester.index') }}" class="nav-link">
                                                    {{ __('Semester') }} </a></li>
                                        @endcan

                                        @can('stream-list')
                                            <li class="nav-item"><a class="nav-link" href="{{ route('stream.index') }}">
                                                    {{ __('Stream') }}
                                                </a></li>
                                        @endcan

                                        @can('shift-list')
                                            <li class="nav-item"><a class="nav-link" href="{{ route('shift.index') }}">
                                                    {{ __('Shift') }}
                                                </a></li>
                                        @endcan

                                        @can('class-list')
                                            <li class="nav-item"><a href="{{ route('class.index') }}" class="nav-link">
                                                    {{ __('Class') }}
                                                </a></li>
                                            <li class="nav-item"><a href="{{ route('class.subject.index') }}" class="nav-link">
                                                    {{ __('Class Subject') }} </a></li>
                                        @endcan

                                        @can('class-group-list')
                                            <li class="nav-item"><a href="{{ route('class-group.index') }}" class="nav-link">
                                                    {{ __('class_group') }} </a></li>
                                        @endcan

                                        @can('class-section-list')
                                            <li class="nav-item"><a href="{{ route('class-section.index') }}"
                                                    class="nav-link">{{ __('Class Section & Teachers') }} </a></li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcanany


                        {{-- Class Section For Teacher --}}
                        @role('Teacher')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('class-section.index') }}">
                                    <i class="fa fa-university menu-icon"></i>
                                    <span class="menu-title"> {{ __('Class Section') }} </span>
                                </a>
                            </li>
                        @endrole

                        {{-- Students --}}
                        @canany(['student-create', 'student-list', 'student-reset-password', 'class-teacher',
                            'form-fields-list', 'form-fields-create', 'form-fields-edit', 'form-fields-delete',
                            'guardian-create', 'promote-student-list', 'transfer-student-list',
                            'assign-elective-subject-list'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#student-menu" aria-expanded="false"
                                    aria-controls="student-menu">
                                    <i class="fa fa-graduation-cap menu-icon"></i>
                                    <span class="menu-title">{{ __('students') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="student-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('student-create')
                                            <li class="nav-item"><a href="{{ route('students.create') }}"
                                                    class="nav-link">{{ __('student_admission') }}</a></li>
                                        @endcan
                                        @can('student-create')
                                            <li class="nav-item"><a href="{{ route('online-registration.index') }}"
                                                    class="nav-link"
                                                    data-access="@hasFeatureAccess('Website Management')">{{ __('admission_inquiries') }}</a></li>
                                        @endcan
                                        @canany(['student-list', 'class-teacher'])
                                            <li class="nav-item"><a href="{{ route('students.index') }}"
                                                    class="nav-link">{{ __('student_details') }}</a></li>
                                        @endcanany

                                        @can('student-create')
                                            <li class="nav-item"><a href="{{ route('students.create-bulk-data') }}"
                                                    class="nav-link">{{ __('add_bulk_data') }}</a></li>
                                        @endcan

                                        @can('guardian-create')
                                            <li class="nav-item">
                                                <a href="{{ route('guardian.index') }}" class="nav-link">
                                                    {{ __('Guardian') }} </a>
                                            </li>
                                        @endcan

                                        @can('student-list')
                                            <li class="nav-item"><a href="{{ route('students.roll-number.index') }}"
                                                    class="nav-link">{{ __('assign') }} {{ __('roll_no') }}</a></li>
                                        @endcan

                                        @can('student-edit')
                                            <li class="nav-item"><a href="{{ route('students.upload-profile') }}"
                                                    class="nav-link">{{ __('upload_profile_images') }}</a></li>
                                        @endcan

                                        @can('assign-elective-subject-list')
                                            <li class="nav-item"><a href="{{ route('assign.elective.subject.index') }}"
                                                    class="nav-link">{{ __('Assign Elective Subject') }} </a></li>
                                        @endcan

                                        @canany('promote-student-create', 'transfer-student-create')
                                            <li class="nav-item"><a href="{{ route('promote-student.index') }}"
                                                    class="nav-link text-wrap">{{ __('Transfer & Promote Students') }}</a>
                                            </li>
                                        @endcan

                                        @can('student-reset-password')
                                            <li class="nav-item"><a href="{{ route('students.reset-password.index') }}"
                                                    class="nav-link">{{ __('students') . ' ' . __('reset_password') }}</a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcanany

                        {{-- Teacher --}}
                        @can('teacher-create')
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#teacher-menu" aria-expanded="false"
                                    aria-controls="teacher-menu">
                                    <i class="fa fa-user menu-icon"></i>
                                    <span class="menu-title">{{ __('teacher') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="teacher-menu">
                                    <ul class="nav flex-column sub-menu">
                                        <li class="nav-item">
                                            <a href="{{ route('teachers.index') }}" class="nav-link">
                                                <span class="menu-title">{{ __('manage_teacher') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('teachers.create-bulk-upload') }}" class="nav-link">
                                                <span class="menu-title">{{ __('bulk upload') }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endcan

                        {{-- Subject Lesson --}}
                        @canany(['lesson-list', 'lesson-create', 'lesson-edit', 'lesson-delete', 'topic-list',
                            'topic-create', 'topic-edit', 'topic-delete'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#subject-lesson-menu"
                                    aria-expanded="false" aria-controls="subject-lesson-menu"
                                    data-access="@hasFeatureAccess('Lesson Management')">
                                    <i class="fa fa-book menu-icon"></i>
                                    <span class="menu-title">{{ __('subject_lesson') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="subject-lesson-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @canany(['lesson-list', 'lesson-create', 'lesson-edit', 'lesson-delete'])
                                            <li class="nav-item">
                                                <a href="{{ url('lesson') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Lesson Management')">
                                                    {{ __('create_lesson') }}</a>
                                            </li>
                                        @endcanany

                                        @canany(['topic-list', 'topic-create', 'topic-edit', 'topic-delete'])
                                            <li class="nav-item">
                                                <a href="{{ url('lesson-topic') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Lesson Management')">
                                                    {{ __('create_topic') }}</a>
                                            </li>
                                        @endcanany
                                    </ul>
                                </div>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endif

        {{-- ============================================ --}}
        {{-- ACADEMIC CALENDAR GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasAcademicCalendar = false;
            $calendarPermissions = [
                'timetable-create',
                'timetable-list',
                'holiday-create',
                'holiday-list',
                'class-teacher',
                'attendance-list',
                'attendance-create',
                'attendance-edit',
                'attendance-delete',
            ];
            foreach ($calendarPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasAcademicCalendar = true;
                    break;
                }
            }
            if (Auth::user()->hasRole('Teacher')) {
                $hasAcademicCalendar = true;
            }
        @endphp

        @if ($hasAcademicCalendar)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#academic-calendar-group" aria-expanded="false"
                    aria-controls="academic-calendar-group">
                    <i class="fa fa-calendar menu-icon"></i>
                    <span class="menu-title">{{ __('Academic Calendar') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="academic-calendar-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Timetable --}}
                        @if (Auth::user()->hasRole('Teacher'))
                            <li class="nav-item">
                                <a href="{{ route('timetable.teacher.show', Auth::user()->id) }}" class="nav-link"
                                    data-access="@hasFeatureAccess('Timetable Management')">
                                    <i class="fa fa-calendar menu-icon"></i>
                                    <span class="menu-title">{{ __('timetable') }}</span>
                                </a>
                            </li>
                        @else
                            @canany(['timetable-create', 'timetable-list'])
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="collapse" href="#timetable-menu"
                                        aria-expanded="false" aria-controls="timetable-menu"
                                        data-access="@hasFeatureAccess('Timetable Management')">
                                        <i class="fa fa-calendar menu-icon"></i>
                                        <span class="menu-title">{{ __('timetable') }}</span>
                                        <i class="menu-arrow"></i>
                                    </a>
                                    <div class="collapse" id="timetable-menu">
                                        <ul class="nav flex-column sub-menu">
                                            @can('timetable-create')
                                                <li class="nav-item">
                                                    <a href="{{ route('timetable.index') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Timetable Management')">{{ __('create_timetable') }} </a>
                                                </li>
                                            @endcan

                                            @can('timetable-list')
                                                <li class="nav-item">
                                                    <a href="{{ route('timetable.teacher.index') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Timetable Management')">
                                                        {{ __('teacher_timetable') }}
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </li>
                            @endcanany
                        @endif

                        {{-- Attendance --}}
                        @canany(['class-teacher', 'attendance-list', 'attendance-create', 'attendance-edit',
                            'attendance-delete'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#attendance-menu"
                                    data-access="@hasFeatureAccess('Attendance Management')" aria-expanded="false"
                                    aria-controls="attendance-menu">
                                    <i class="fa fa-check menu-icon"></i>
                                    <span class="menu-title">{{ __('attendance') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="attendance-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @canany(['class-teacher', 'attendance-create'])
                                            <li class="nav-item">
                                                <a href="{{ route('attendance.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Attendance Management')">
                                                    {{ __('add_attendance') }}
                                                </a>
                                            </li>
                                        @endcan

                                        @canany(['class-teacher', 'attendance-list'])
                                            <li class="nav-item">
                                                <a href="{{ route('attendance.view') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Attendance Management')">
                                                    {{ __('view_attendance') }}
                                                </a>
                                            </li>

                                            <li class="nav-item">
                                                <a href="{{ route('attendance.month') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Attendance Management')">
                                                    {{ __('month_wise') }}
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcanany

                        {{-- Holiday List --}}
                        @canany(['holiday-create', 'holiday-list'])
                            <li class="nav-item">
                                @can('holiday-list')
                                    <a href="{{ route('holiday.index') }}" class="nav-link"
                                        data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Holiday Management')">
                                        <i class="fa fa-calendar-check-o menu-icon"></i>
                                        <span class="menu-title">{{ __('holiday_list') }}</span>
                                    </a>
                                @endcan
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endif

        {{-- ============================================ --}}
        {{-- EXAM & PERFORMANCE GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasExamPerformance = false;
            $examPermissions = [
                'exam-create',
                'exam-upload-marks',
                'grade-create',
                'exam-result',
                'view-exam-marks',
                'online-exam-create',
                'online-exam-list',
                'online-exam-edit',
                'online-exam-delete',
                'assignment-create',
                'assignment-submission',
            ];
            foreach ($examPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasExamPerformance = true;
                    break;
                }
            }
        @endphp

        @if ($hasExamPerformance)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#exam-performance-group" aria-expanded="false"
                    aria-controls="exam-performance-group">
                    <i class="fa fa-clipboard menu-icon"></i>
                    <span class="menu-title">{{ __('Exam & Performance') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="exam-performance-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Student Assignment --}}
                        @canany(['assignment-create', 'assignment-submission'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#student-assignment-menu"
                                    aria-expanded="false" aria-controls="student-assignment-menu"
                                    data-access="@hasFeatureAccess('Assignment Management')">
                                    <i class="fa fa-tasks menu-icon"></i>
                                    <span class="menu-title">{{ __('assignment') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="student-assignment-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('assignment-create')
                                            <li class="nav-item">
                                                <a href="{{ route('assignment.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Assignment Management')">
                                                    {{ __('create_assignment') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('assignment-submission')
                                            <li class="nav-item">
                                                <a href="{{ route('assignment.submission') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Assignment Management')">
                                                    {{ __('assignment_submission') }}
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcanany

                        {{-- Offline Exam --}}
                        @canany(['exam-create', 'exam-upload-marks', 'grade-create', 'exam-result', 'view-exam-marks'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#exam-menu" aria-expanded="false"
                                    aria-controls="exam-menu" data-access="@hasFeatureAccess('Exam Management')">
                                    <i class="fa fa-book menu-icon"></i>
                                    <span class="menu-title">{{ __('Offline Exam') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="exam-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('exam-create')
                                            <li class="nav-item">
                                                <a href="{{ route('exams.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('manage_exam') }}
                                                </a>
                                            </li>
                                        @endcan

                                        <li class="nav-item">
                                            <a href="{{ route('exams.timetable') }}" class="nav-link"
                                                data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                data-access="@hasFeatureAccess('Exam Management')">
                                                {{ __('timetable') }}
                                            </a>
                                        </li>

                                        @can('view-exam-marks')
                                            <li class="nav-item">
                                                <a href="{{ route('exam.view-marks') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('track_exam_marks') }}
                                                </a>
                                            </li>
                                        @endcan

                                        @can('exam-upload-marks')
                                            <li class="nav-item">
                                                <a href="{{ route('exams.upload-marks') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('upload_exam_marks') }}
                                                </a>
                                            </li>

                                            <li class="nav-item">
                                                <a href="{{ route('exam.bulk-upload-marks') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('bulk_upload_exam_marks') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('exam-result')
                                            <li class="nav-item">
                                                <a href="{{ route('exams.get-result') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('Exam Result') }}
                                                </a>
                                            </li>
                                        @endcan

                                        @can('grade-create')
                                            <li class="nav-item">
                                                <a href="{{ route('exam.grade.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('exam_grade') }}
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcan

                        {{-- Online Exam --}}
                        @canany(['online-exam-create', 'online-exam-list', 'online-exam-edit', 'online-exam-delete'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#online-exam-menu"
                                    aria-expanded="false" aria-controls="online-exam-menu"
                                    data-access="@hasFeatureAccess('Exam Management')">
                                    <i class="fa fa-laptop menu-icon"></i>
                                    <span class="menu-title">{{ __('online_exam') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="online-exam-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('online-exam-list')
                                            <li class="nav-item">
                                                <a href="{{ route('online-exam.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('manage_online_exam') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('online-exam-create')
                                            <li class="nav-item">
                                                <a href="{{ route('online-exam-question.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('manage_questions') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('online-exam-create')
                                            <li class="nav-item">
                                                <a href="{{ route('online-exam-question.add-bulk-questions') }}"
                                                    class="nav-link" data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('add_bulk_questions') }}
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endif

        {{-- ============================================ --}}
        {{-- COMMUNICATION & MEDIA GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasCommunicationMedia = false;
            $communicationPermissions = [
                'student-diary-list',
                'slider-create',
                'notification-create',
                'notification-list',
                'notification-delete',
                'announcement-list',
                'gallery-create',
                'gallery-list',
                'gallery-edit',
                'gallery-delete',
            ];
            foreach ($communicationPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasCommunicationMedia = true;
                    break;
                }
            }
        @endphp

        @if ($hasCommunicationMedia)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#communication-media-group" aria-expanded="false"
                    aria-controls="communication-media-group">
                    <i class="fa fa-comments menu-icon"></i>
                    <span class="menu-title">{{ __('Communication & Media') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="communication-media-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Student Diary --}}
                        @can(['student-diary-list'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#student-diary-menu"
                                    aria-expanded="false" aria-controls="student-diary-menu">
                                    <i class="fa fa-envelope-square menu-icon"></i>
                                    <span class="menu-title">{{ __('student_diary') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="student-diary-menu">
                                    <ul class="nav flex-column sub-menu">
                                        <li class="nav-item">
                                            <a href="{{ route('diary-categories.index') }}" class="nav-link">
                                                <span class="menu-title">{{ __('diary_category') }}</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('diary.index') }}" class="nav-link">
                                                <span class="menu-title">{{ __('manage_diaries') }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endcan

                        {{-- Notification --}}
                        @canany(['notification-create', 'notification-list', 'notification-delete'])
                            <li class="nav-item">
                                <a href="{{ route('notifications.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Announcement Management')">
                                    <i class="fa fa-bell menu-icon"></i>
                                    <span class="menu-title">{{ __('notification') }}</span>
                                </a>
                            </li>
                        @endcanany

                        {{-- Announcement --}}
                        @can('announcement-list')
                            <li class="nav-item">
                                <a href="{{ route('announcement.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Announcement Management')">
                                    <i class="fa fa-bullhorn menu-icon"></i>
                                    <span class="menu-title">{{ __('announcement') }}</span>
                                </a>
                            </li>
                        @endcan

                        {{-- Slider --}}
                        @can('slider-create')
                            <li class="nav-item">
                                <a href="{{ route('sliders.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Slider Management')">
                                    <i class="fa fa-list menu-icon"></i>
                                    <span class="menu-title">{{ __('sliders') }}</span>
                                </a>
                            </li>
                        @endcan

                        {{-- Gallery --}}
                        @canany(['gallery-create', 'gallery-list', 'gallery-edit', 'gallery-delete'])
                            <li class="nav-item">
                                <a href="{{ route('gallery.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('School Gallery Management')">
                                    <i class="fa fa-picture-o menu-icon"></i>
                                    <span class="menu-title">{{ __('gallery') }}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endif

        {{-- ============================================ --}}
        {{-- PERSONNEL MANAGEMENT GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasPersonnelManagement = false;
            $personnelPermissions = [
                'role-list',
                'role-create',
                'role-edit',
                'role-delete',
                'staff-list',
                'staff-create',
                'staff-edit',
                'staff-delete',
                'leave-list',
                'leave-create',
                'leave-edit',
                'leave-delete',
                'approve-leave',
                'staff-attendance-list',
                'staff-attendance-create',
                'staff-attendance-edit',
                'staff-attendance-delete',
            ];
            foreach ($personnelPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasPersonnelManagement = true;
                    break;
                }
            }
            if (
                (!Auth::user()->hasRole('School Admin') && Auth::user()->school_id) ||
                (Auth::user()->school_id && Auth::user()->staff)
            ) {
                $hasPersonnelManagement = true;
            }
        @endphp

        @if ($hasPersonnelManagement)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#personnel-management-group" aria-expanded="false"
                    aria-controls="personnel-management-group">
                    <i class="fa fa-briefcase menu-icon"></i>
                    <span class="menu-title">{{ __('Personnel Management') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="personnel-management-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Staff Management --}}
                        @if (Auth::user()->school_id)
                            @canany(['role-list', 'role-create', 'role-edit', 'role-delete', 'staff-list',
                                'staff-create', 'staff-edit', 'staff-delete'])
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="collapse" href="#staff-management"
                                        aria-expanded="false" aria-controls="staff-management-menu"
                                        data-access="@hasFeatureAccess('Staff Management')">
                                        <i class="fa fa-user-secret menu-icon"></i>
                                        <span class="menu-title">{{ __('Staff Management') }}</span>
                                        <i class="menu-arrow"></i>
                                    </a>
                                    <div class="collapse" id="staff-management">
                                        <ul class="nav flex-column sub-menu">
                                            @canany(['role-list', 'role-create', 'role-edit', 'role-delete'])
                                                <li class="nav-item">
                                                    <a href="{{ route('roles.index') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Staff Management')">{{ __('Role & Permission') }}</a>
                                                </li>
                                            @endcanany
                                            @canany(['staff-list', 'staff-create', 'staff-edit', 'staff-delete'])
                                                <li class="nav-item">
                                                    <a href="{{ route('staff.index') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Staff Management')">{{ __('staff') }}</a>
                                                </li>
                                            @endcanany
                                            @canany(['staff-list', 'staff-create', 'staff-edit', 'staff-delete'])
                                                <li class="nav-item">
                                                    <a href="{{ route('staff.create-bulk-upload') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Staff Management')">{{ __('bulk upload') }}</a>
                                                </li>
                                            @endcanany
                                        </ul>
                                    </div>
                                </li>
                            @endcan

                            {{-- Staff Leave Management --}}
                            @canany(['approve-leave'])
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="collapse" href="#staff-leave-management"
                                        aria-expanded="false" aria-controls="staff-leave-management-menu"
                                        data-access="@hasFeatureAccess('Staff Leave Management')">
                                        <i class="fa fa-plane menu-icon"></i>
                                        <span class="menu-title">{{ __('Staff Leave') }}</span>
                                        <i class="menu-arrow"></i>
                                    </a>
                                    <div class="collapse" id="staff-leave-management">
                                        <ul class="nav flex-column sub-menu">
                                            @can('approve-leave')
                                                <li class="nav-item">
                                                    <a href="{{ route('leave.request') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Staff Leave Management')">{{ __('staff') }}
                                                        {{ __('leave') }}</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="{{ url('leave/report') }}" class="nav-link"
                                                        data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                        data-access="@hasFeatureAccess('Staff Leave Management')">{{ __('leave_report') }}</a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </li>
                            @endcan
                        @else
                            @canany(['role-list', 'role-create', 'role-edit', 'role-delete', 'staff-list',
                                'staff-create', 'staff-edit', 'staff-delete'])
                                @canany(['role-list', 'role-create', 'role-edit', 'role-delete'])
                                    <li class="nav-item">
                                        <a href="{{ route('roles.index') }}" class="nav-link">
                                            <i class="fa fa-user-secret menu-icon"></i>
                                            <span class="menu-title">{{ __('Role & Permission') }}</span>
                                        </a>
                                    </li>
                                @endcanany

                                @canany(['staff-list', 'staff-create', 'staff-edit', 'staff-delete'])
                                    <li class="nav-item">
                                        <a href="{{ route('staff.index') }}" class="nav-link">
                                            <i class="fa fa-users menu-icon"></i>
                                            <span class="menu-title">{{ __('staff') }}</span>
                                        </a>
                                    </li>
                                @endcanany
                            @endcan
                        @endif

                        {{-- Leave (for staff) --}}
                        @canany(['leave-list', 'leave-create', 'leave-edit', 'leave-delete'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#staff-leave-menu"
                                    data-access="@hasFeatureAccess('Staff Leave Management')" aria-expanded="false"
                                    aria-controls="staff-leave-menu">
                                    <i class="fa fa-plane menu-icon"></i>
                                    <span class="menu-title">{{ __('leave') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="staff-leave-menu">
                                    <ul class="nav flex-column sub-menu">
                                        <li class="nav-item">
                                            <a href="{{ route('leave.index') }}" class="nav-link"
                                                data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                data-access="@hasFeatureAccess('Staff Leave Management')">
                                                {{ __('apply_leave') }}
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('leave.report') }}" class="nav-link"
                                                data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                data-access="@hasFeatureAccess('Staff Leave Management')">
                                                {{ __('leave_report') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endcanany

                        {{-- Staff Attendance --}}
                        @if (!Auth::user()->hasRole('School Admin') && Auth::user()->school_id)
                            <li class="nav-item">
                                <a href="{{ route('staff-attendance.your-index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                    data-access="@hasFeatureAccess('Staff Attendance Management')">
                                    <i class="fa fa-calendar-check-o menu-icon"></i>
                                    <span class="menu-title">{{ __('my_attendance') }}</span>
                                </a>
                            </li>
                        @endif

                        @canany(['staff-attendance-list', 'staff-attendance-create', 'staff-attendance-edit',
                            'staff-attendance-delete'])
                            <li class="nav-item">
                                <a href="{{ route('staff-attendance.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Staff Attendance Management')">
                                    <i class="fa fa-users menu-icon"></i>
                                    <span class="menu-title">{{ __('Staff Attendance') }}</span>
                                </a>
                            </li>
                        @endcanany

                        {{-- Payroll Slips --}}
                        @if (Auth::user()->school_id && Auth::user()->staff)
                            <li class="nav-item">
                                <a href="{{ route('payroll.slip.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                    data-access="@hasFeatureAccess('Expense Management')">
                                    <i class="fa fa-money menu-icon"></i>
                                    <span class="menu-title">{{ __('payroll') }} {{ __('slips') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>
        @else
        @endif

        {{-- ============================================ --}}
        {{-- INSTITUTIONAL FINANCE GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasInstitutionalFinance = false;
            $financePermissions = [
                'fees-list',
                'fees-type-list',
                'fees-classes-list',
                'fees-paid',
                'expense-category-create',
                'expense-category-list',
                'expense-category-edit',
                'expense-category-delete',
                'expense-create',
                'expense-list',
                'expense-edit',
                'expense-delete',
                'payroll-create',
                'payroll-list',
                'payroll-edit',
                'payroll-delete',
                'payroll-settings-list',
                'payroll-settings-create',
                'payroll-settings-edit',
                'payroll-settings-delete',
                'route-list',
                'pickup-points-list',
                'reports-student',
                'reports-exam',
                'report-list',
                'reports-teacher',
                'reports-expense',
            ];

            foreach ($financePermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasInstitutionalFinance = true;
                    break;
                }
            }
            if ((Auth::user()->school_id && Auth::user()->staff) || Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher')) {
                $hasInstitutionalFinance = true;
            }
            if (Auth::user()->hasRole('Teacher')) {
                $hasInstitutionalFinance = false;
            }
        @endphp

        @if ($hasInstitutionalFinance)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#institutional-finance-group" aria-expanded="false"
                    aria-controls="institutional-finance-group">
                    <i class="fa fa-bank menu-icon"></i>
                    <span class="menu-title">{{ __('Institutional Finance') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="institutional-finance-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Fees --}}
                        @canany(['fees-list', 'fees-type-list', 'fees-classes-list', 'fees-paid'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#fees-menu" aria-expanded="false"
                                    aria-controls="fees-menu" data-access="@hasFeatureAccess('Fees Management')">
                                    <i class="fa fa-dollar menu-icon"></i>
                                    <span class="menu-title">{{ __('Fees') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="fees-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('fees-type-list')
                                            <li class="nav-item">
                                                <a href="{{ route('fees-type.index') }}" class="nav-link"
                                                    data-access="@hasFeatureAccess('Fees Management')">
                                                    {{ __('Fees Type') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('fees-list')
                                            <li class="nav-item">
                                                <a href="{{ route('fees.index') }}" class="nav-link"
                                                    data-access="@hasFeatureAccess('Fees Management')">
                                                    {{ __('Manage Fee') }}</a>
                                            </li>
                                        @endcan
                                        @can('fees-paid')
                                            <li class="nav-item">
                                                <a href="{{ route('fees.paid.index') }}" class="nav-link"
                                                    data-access="@hasFeatureAccess('Fees Management')">
                                                    {{ __('Student Fees') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('fees-paid')
                                            <li class="nav-item">
                                                <a href="{{ route('fees.optional') }}" class="nav-link"
                                                    data-access="@hasFeatureAccess('Fees Management')">
                                                    {{ __('Optional Fee') }}</a>
                                            </li>
                                        @endcan
                                        @can('fees-paid')
                                            <li class="nav-item">
                                                <a href="{{ route('fees.transactions.log.index') }}" class="nav-link"
                                                    data-access="@hasFeatureAccess('Fees Management')"> {{ __('Fees Transaction Logs') }}
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcan

                        {{-- Expense --}}
                        @canany(['expense-category-create', 'expense-category-list', 'expense-category-edit',
                            'expense-category-delete', 'expense-create', 'expense-list', 'expense-edit', 'expense-delete'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#expense-menu" aria-expanded="false"
                                    aria-controls="expense-menu" data-access="@hasFeatureAccess('Expense Management')">
                                    <i class="fa fa-money menu-icon"></i>
                                    <span class="menu-title">{{ __('expense') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="expense-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @canany(['expense-category-create', 'expense-category-list',
                                            'expense-category-edit', 'expense-category-delete'])
                                            <li class="nav-item">
                                                <a href="{{ route('expense-category.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Expense Management')">{{ __('manage_category') }} </a>
                                            </li>
                                        @endcanany

                                        @canany(['expense-create', 'expense-list', 'expense-edit', 'expense-delete'])
                                            <li class="nav-item">
                                                <a href="{{ route('expense.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Expense Management')">
                                                    {{ __('manage_expense') }}
                                                </a>
                                            </li>
                                        @endcanany
                                    </ul>
                                </div>
                            </li>
                        @endcanany

                        {{-- Payroll --}}
                        @canany(['payroll-create', 'payroll-list', 'payroll-edit', 'payroll-delete',
                            'payroll-settings-list', 'payroll-settings-create', 'payroll-settings-edit',
                            'payroll-settings-delete'])
                            <li class="nav-item">
                                <a href="#payroll-menu" class="nav-link" data-toggle="collapse"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Expense Management')">
                                    <i class="fa fa-credit-card-alt menu-icon"></i>
                                    <span class="menu-title">{{ __('payroll') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="payroll-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @canany(['payroll-create', 'payroll-edit', 'payroll-list'])
                                            <li class="nav-item">
                                                <a href="{{ route('payroll.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Expense Management')">{{ __('manage_payroll') }} </a>
                                            </li>
                                        @endcanany

                                        @canany(['payroll-settings-list', 'payroll-settings-create',
                                            'payroll-settings-edit', 'payroll-settings-delete'])
                                            <li class="nav-item">
                                                <a href="{{ route('payroll-setting.index') }}" class="nav-link"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Expense Management')">
                                                    {{ __('payroll_setting') }}
                                                </a>
                                            </li>
                                        @endcanany
                                    </ul>
                                </div>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endif

        {{-- ============================================ --}}
        {{-- TRANSPORTATION MODULE GROUP --}}
        {{-- ============================================ --}}

        @canany(['route-list', 'pickup-points-list', 'vehicles-list', 'RouteVehicle-list', 'driver-helper-list',
            'transportationRequests-list', 'transportationexpense-list'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#transportation-menu" aria-expanded="false"
                    aria-controls="transportation-menu">
                    <i class="fa fa-bus menu-icon"></i>
                    <span class="menu-title">{{ __('transportation_module') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="transportation-menu">
                    <ul class="nav flex-column sub-menu">


                        @can('vehicles-list')
                            <li class="nav-item">
                                <a href="{{ route('vehicles.index') }}" class="nav-link" data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-car menu-icon"></i>
                                    <span class="menu-title">{{ __('vehicles') }}</span>
                                </a>
                            </li>
                        @endcan
                        @canany(['pickup-points-list'])
                            <li class="nav-item">
                                <a href="{{ route('pickup-points.index') }}" class="nav-link"
                                    data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-map-marker menu-icon"></i>
                                    <span class="menu-title">{{ __('pickup_points') }}</span>
                                </a>
                            </li>
                        @endcanany
                        @can('route-list')
                            <li class="nav-item">
                                <a href="{{ route('routes.index') }}" class="nav-link" data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-road menu-icon"></i>
                                    <span class="menu-title">{{ __('routes') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('RouteVehicle-list')
                            <li class="nav-item">
                                <a href="{{ route('route-vehicle.index') }}" class="nav-link"
                                    data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-map-o menu-icon"></i>
                                    <span class="menu-title">{{ __('route_vehicles') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('driver-helper-list')
                            <li class="nav-item">
                                <a href="{{ route('driver-helper.index') }}" class="nav-link"
                                    data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-user menu-icon"></i>
                                    <span class="menu-title">{{ __('driver_helper') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('transportationRequests-list')
                            <li class="nav-item">
                                <a href="{{ route('transportation-requests.index') }}" class="nav-link"
                                    data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-bus menu-icon"></i>
                                    <span class="menu-title">{{ __('allocation') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('transportationexpense-list')
                            <li class="nav-item">
                                <a href="{{ route('transportation-expense.index') }}" class="nav-link"
                                    data-access="@hasFeatureAccess('Transportation Module')">
                                    <i class="fa fa-money menu-icon"></i>
                                    <span class="menu-title">{{ __('expenses') }}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endrole


        {{-- ============================================ --}}
        {{-- ACCOUNT & SUBSCRIPTION GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasAccountSubscription = false;
            $accountPermissions = [
                'package-list',
                'package-create',
                'package-edit',
                'package-delete',
                'addons-list',
                'addons-create',
                'addons-edit',
                'addons-delete',
                'subscription-view',
            ];
            foreach ($accountPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasAccountSubscription = true;
                    break;
                }
            }
            if (Auth::user()->hasRole('School Admin')) {
                $hasAccountSubscription = true;
            }
        @endphp

        @if ($hasAccountSubscription && !Auth::user()->school_id)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#account-subscription-group" aria-expanded="false"
                    aria-controls="account-subscription-group">
                    <i class="fa fa-credit-card menu-icon"></i>
                    <span class="menu-title">{{ __('packages_subscription') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="account-subscription-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- Package (Super Admin) --}}
                        @canany(['package-list', 'package-create', 'package-edit', 'package-delete'])
                            <li class="nav-item">
                                <a href="{{ route('package.index') }}" class="nav-link">
                                    <i class="fa fa-codepen menu-icon"></i>
                                    <span class="menu-title">{{ __('package') }}</span>
                                </a>
                            </li>
                        @endcan

                        {{-- Addons (Super Admin) --}}
                        @canany(['addons-list', 'addons-create', 'addons-edit', 'addons-delete'])
                            <li class="nav-item">
                                <a href="{{ route('addons.index') }}" class="nav-link">
                                    <i class="fa fa-puzzle-piece menu-icon"></i>
                                    <span class="menu-title">{{ __('addons') }}</span>
                                </a>
                            </li>
                        @endcan

                        {{-- Features --}}
                        @canany(['addons-list', 'addons-create', 'addons-edit', 'addons-delete', 'package-list',
                            'package-create', 'package-edit', 'package-delete'])
                            <li class="nav-item">
                                <a href="{{ url('features') }}" class="nav-link">
                                    <i class="fa fa-list-ul menu-icon"></i>
                                    <span class="menu-title">{{ __('features') }}</span>
                                </a>
                            </li>
                        @endcan

                        {{-- Subscription View (Super Admin) --}}
                        @can('subscription-view')
                            <li class="nav-item">
                                <a href="{{ url('subscriptions/report') }}" class="nav-link">
                                    <i class="fa fa-puzzle-piece menu-icon"></i>
                                    <span class="menu-title">{{ __('subscription') }}</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ url('subscriptions/transactions') }}" class="nav-link">
                                    <i class="fa fa-money menu-icon"></i>
                                    <span class="menu-title">{{ __('subscription_transaction') }}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endif

        {{-- Expense --}}
        {{-- @canany(['expense-category-create', 'expense-category-list', 'expense-category-edit', 'expense-category-delete', 'expense-create', 'expense-list', 'expense-edit', 'expense-delete'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#expense-menu" aria-expanded="false"
                    aria-controls="expense-menu" data-access="@hasFeatureAccess('Expense Management')">
                    <i class="fa fa-money menu-icon"></i>
                    <span class="menu-title">{{ __('expense') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="expense-menu">
                    <ul class="nav flex-column sub-menu">
                        @canany(['expense-category-create', 'expense-category-list', 'expense-category-edit', 'expense-category-delete'])
                            <li class="nav-item">
                                <a href="{{ route('expense-category.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                    data-access="@hasFeatureAccess('Expense Management')">
                                    <i class="fa fa-list-ul menu-icon"></i>
                                    <span class="menu-title">{{ __('manage_category') }}</span>
                                </a>
                            </li>
                        @endcanany

                        @canany(['expense-create', 'expense-list', 'expense-edit', 'expense-delete'])
                            <li class="nav-item">
                                <a href="{{ route('expense.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Expense Management')">
                                    {{ __('manage_expense') }}
                                </a>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endcanany --}}

        {{-- Payroll --}}
        {{-- @canany(['payroll-create', 'payroll-list', 'payroll-edit', 'payroll-delete', 'payroll-settings-list', 'payroll-settings-create', 'payroll-settings-edit', 'payroll-settings-delete'])
            <li class="nav-item">
                <a href="#payroll-menu" class="nav-link" data-toggle="collapse"
                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Expense Management')">
                    <i class="fa fa-credit-card-alt menu-icon"></i>
                    <span class="menu-title">{{ __('payroll') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="payroll-menu">
                    <ul class="nav flex-column sub-menu">
                        @canany(['payroll-create', 'payroll-edit', 'payroll-list'])
                            <li class="nav-item">
                                <a href="{{ route('payroll.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                    data-access="@hasFeatureAccess('Expense Management')">{{ __('manage_payroll') }} </a>
                            </li>
                        @endcanany

                        @canany(['payroll-settings-list', 'payroll-settings-create', 'payroll-settings-edit', 'payroll-settings-delete'])
                            <li class="nav-item">
                                <a href="{{ route('payroll-setting.index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('Expense Management')">
                                    {{ __('payroll_setting') }}
                                </a>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endcanany --}}


        {{-- ============================================ --}}
        {{-- Certificate & ID Card GROUP --}}
        {{-- ============================================ --}}

        {{-- Certificate & ID Card (standalone) --}}
        @canany(['certificate-create', 'certificate-list', 'certificate-edit', 'certificate-delete', 'student-list',
            'class-teacher', 'id-card-settings'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#certificate-menu" aria-expanded="false"
                    aria-controls="certificate-menu">
                    <i class="fa fa-trophy menu-icon"></i>
                    <span class="menu-title">{{ __('certificate_id_card') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="certificate-menu">
                    <ul class="nav flex-column sub-menu">
                        @canany(['certificate-create', 'certificate-list', 'certificate-edit', 'certificate-delete'])
                            <li class="nav-item">
                                <a href="{{ url('certificate-template') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('ID Card - Certificate Generation')">
                                    <i class="fa fa-file-text menu-icon"></i>
                                    <span class="menu-title">{{ __('certificate_template') }}</span>
                                </a>
                            </li>
                        @endcanany

                        @canany(['certificate-list'])
                            <li class="nav-item">
                                <a href="{{ url('certificate') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('ID Card - Certificate Generation')">
                                    <i class="fa fa-user menu-icon"></i>
                                    <span class="menu-title">{{ __('student_certificate') }}</span>
                                </a>
                            </li>
                        @endcanany

                        @canany(['certificate-list'])
                            <li class="nav-item">
                                <a href="{{ url('certificate/staff-certificate') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('ID Card - Certificate Generation')">
                                    <i class="fa fa-user-secret menu-icon"></i>
                                    <span class="menu-title">{{ __('staff_certificate') }}</span>
                                </a>
                            </li>
                        @endcanany

                        @can('id-card-settings')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('id-card-settings') }}"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('ID Card - Certificate Generation')">
                                    <i class="fa fa-cogs menu-icon"></i>
                                    <span class="menu-title">{{ __('id_card_settings') }}</span>
                                </a>
                            </li>
                        @endcan

                        @canany(['student-list', 'class-teacher'])
                            <li class="nav-item"><a href="{{ route('students.generate-id-card-index') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('ID Card - Certificate Generation')">
                                    <i class="fa fa-id-card menu-icon"></i>
                                    <span class="menu-title">{{ __('student_id_card') }}</span>
                                </a>
                            </li>
                        @endcanany

                        @can('staff-list')
                            <li class="nav-item">
                                <a href="{{ route('staff.id-card') }}" class="nav-link"
                                    data-name="{{ Auth::user()->getRoleNames()[0] }}" data-access="@hasFeatureAccess('ID Card - Certificate Generation')">
                                    <i class="fa fa-id-card-o menu-icon"></i>
                                    <span class="menu-title">{{ __('staff_id_card') }}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany


        {{-- ============================================ --}}
        {{-- Reports GROUP --}}
        {{-- ============================================ --}}
        @if ((Auth::user()->school_id && Auth::user()->staff) || Auth::user()->hasRole('School Admin'))
            @canany(['reports-student', 'reports-exam', 'report-list'])
                <li class="nav-item">
                    <a class="nav-link" data-toggle="collapse" href="#reports-menu" aria-expanded="false"
                        aria-controls="reports-menu">
                        <i class="fa fa-file-text menu-icon"></i>
                        <span class="menu-title">{{ __('reports') }}</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse" id="reports-menu">
                        <ul class="nav flex-column sub-menu">
                            @can('reports-student')
                                <li class="nav-item">
                                    <a href="{{ route('reports.student.student-reports') }}" class="nav-link">
                                        <i class="fa fa-user menu-icon"></i>
                                        <span class="menu-title">{{ __('Student Reports') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('reports-teacher')
                                <li class="nav-item">
                                    <a href="{{ route('reports.teacher.teacher-reports') }}" class="nav-link">
                                        <i class="fa fa-user menu-icon"></i>
                                        <span class="menu-title">{{ __('Teacher Reports') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('reports-exam')
                                <li class="nav-item">
                                    <a href="{{ route('reports.exam.exam-reports') }}" class="nav-link">
                                        <i class="fa fa-file-text menu-icon"></i>
                                        <span class="menu-title">{{ __('Exam Reports') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('reports-expense')
                                <li class="nav-item">
                                    <a href="{{ route('reports.expense.list') }}" class="nav-link">
                                        <i class="fa fa-money menu-icon"></i>
                                        <span class="menu-title">{{ __('expense_report') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </li>
            @endcanany
        @endif


        {{-- ============================================ --}}
        {{-- Certificate & ID Card GROUP --}}
        {{-- ============================================ --}}

        @role('School Admin')
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#subscription-menu" aria-expanded="false"
                    aria-controls="subscription-menu">
                    <i class="fa fa-credit-card menu-icon"></i>
                    <span class="menu-title">{{ __('subscription_and_plans') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="subscription-menu">
                    <ul class="nav flex-column sub-menu">

                        <li class="nav-item">
                            <a href="{{ route('subscriptions.history') }}" class="nav-link">
                                <i class="fa fa-credit-card menu-icon"></i>
                                <span class="menu-title">{{ __('subscription') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('subscriptions.index') }}">
                                <i class="fa fa-codepen menu-icon"></i>
                                <span class="menu-title">{{ __('plans') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('addons.plan') }}">
                                <i class="fa fa-puzzle-piece menu-icon"></i>
                                <span class="menu-title">{{ __('addons') }}</span>
                            </a>
                        </li>

                    </ul>
                </div>
            </li>
        @endrole

        {{-- Email Schools (Super Admin) --}}
        @canany(['custom-school-email'])
            <li class="nav-item">
                <a href="{{ route('schools.send.mail') }}" class="nav-link">
                    <i class="fa fa-envelope menu-icon"></i>
                    <span class="menu-title">{{ __('email_schools') }}</span>
                </a>
            </li>
        @endcan

        {{-- ============================================ --}}
        {{-- SETTINGS GROUP --}}
        {{-- ============================================ --}}
        @php
            $hasSettings = false;
            $settingsPermissions = [
                'app-settings',
                'language-list',
                'school-setting-manage',
                'system-setting-manage',
                'fcm-setting-manage',
                'email-setting-create',
                'privacy-policy',
                'contact-us',
                'about-us',
                'guidance-create',
                'guidance-list',
                'guidance-edit',
                'guidance-delete',
                'email-template',
                'web-settings',
                'school-web-settings',
            ];
            foreach ($settingsPermissions as $perm) {
                if (Auth::user()->can($perm)) {
                    $hasSettings = true;
                    break;
                }
            }
            if (
                Auth::user()->hasRole(['Super Admin', 'School Admin']) ||
                Auth::user()->hasPermissionTo('database-backup')
            ) {
                $hasSettings = true;
            }
        @endphp

        @if ($hasSettings)
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#settings-group" aria-expanded="false"
                    aria-controls="settings-group">
                    <i class="fa fa-cog menu-icon"></i>
                    <span class="menu-title">{{ __('Settings') }}</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="settings-group">
                    <ul class="nav flex-column sub-menu">
                        {{-- System Settings --}}
                        @canany(['app-settings', 'language-list', 'school-setting-manage', 'system-setting-manage',
                            'fcm-setting-manage', 'email-setting-create', 'privacy-policy', 'contact-us', 'about-us',
                            'guidance-create', 'guidance-list', 'guidance-edit', 'guidance-delete', 'email-template',
                            'school-custom-field-list', 'school-custom-field-create', 'school-custom-field-edit', 'school-custom-field-delete'])
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#settings-menu" aria-expanded="false"
                                    aria-controls="settings-menu">
                                    <i class="fa fa-wrench menu-icon"></i>
                                    <span class="menu-title">{{ __('system_settings') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="settings-menu">
                                    <ul class="nav flex-column sub-menu">
                                        @can('app-settings')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.app') }}">{{ __('app_settings') }}</a>
                                            </li>
                                        @endcan
                                        @can('school-setting-manage')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.index') }}">{{ __('general_settings') }}</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('session-year.index') }}">{{ __('session_year') }}</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('leave-master.index') }}"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Staff Leave Management')">{{ __('leave') }}
                                                    {{ __('settings') }}</a>
                                            </li>

                                            @canany(['form-fields-list', 'form-fields-create', 'form-fields-edit', 'form-fields-delete'])
                                                <li class="nav-item">
                                                    <a class="nav-link" href="{{ route('form-fields.index') }}">
                                                        {{ __('custom_fields') }}
                                                    </a>
                                                </li>
                                            @endcanany
                                        @endcan

                                        @can('system-setting-manage')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.index') }}">{{ __('general_settings') }}</a>
                                            </li>
                                        @endcan

                                        @can('subscription-settings')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.subscription-settings') }}">{{ __('subscription_settings') }}</a>
                                            </li>
                                        @endcan

                                        @canany(['guidance-create', 'guidance-list', 'guidance-edit', 'guidance-delete'])
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('guidances.index') }}">{{ __('guidance') }}</a>
                                            </li>
                                        @endcanany

                                        @canany(['school-custom-field-list', 'school-custom-field-create', 'school-custom-field-edit',
                                            'school-custom-field-delete'])
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('schools.custom-field.index') }}">{{ __('custom_fields') }}</a>
                                            </li>
                                        @endcanany

                                        @can('language-list')
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ url('language') }}">
                                                    {{ __('language_settings') }}</a>
                                            </li>
                                        @endcan
                                        @can('fcm-setting-manage')
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('system-settings.fcm') }}">
                                                    {{ __('notification_settings') }}</a>
                                            </li>
                                        @endcan

                                        @can('school-setting-manage')
                                            <li class="nav-item">
                                                <a href="{{ route('school-settings.online-exam.index') }}"
                                                    class="nav-link text-wrap"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Exam Management')">
                                                    {{ __('online_exam_terms_condition') }}
                                                </a>
                                            </li>
                                        @endcan

                                        @can('email-setting-create')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.email.index') }}">{{ __('email_configuration') }}</a>
                                            </li>
                                        @endcan

                                        @can('email-setting-create')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.email.template') }}">{{ __('email_template') }}</a>
                                            </li>
                                        @endcan

                                        @can('email-template')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.email.template') }}">{{ __('email_template') }}</a>
                                            </li>
                                        @endcan

                                        @hasanyrole(['Super Admin', 'School Admin'])
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.payment.index') }}">{{ __('Payment Settings') }}</a>
                                            </li>
                                        @endrole

                                        @can('school-setting-manage')
                                            <li class="nav-item">
                                                <a class="nav-link" data-access="@hasFeatureAccess('Website Management')"
                                                    href="{{ route('school-settings.third-party') }}">{{ __('Third-Party APIs') }}</a>
                                            </li>
                                        @endcan

                                        @can('system-setting-manage')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.third-party') }}">{{ __('Third-Party APIs') }}</a>
                                            </li>
                                        @endcan

                                        @can('contact-us')
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('system-settings.contact-us') }}">
                                                    {{ __('contact_us') }}</a>
                                            </li>
                                        @endcan
                                        @can('about-us')
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('system-settings.about-us') }}">
                                                    {{ __('about_us') }}
                                                </a>
                                            </li>
                                        @endcan

                                        @hasrole('School Admin')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.privacy-policy') }}">{{ __('privacy_policy') }}</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.terms-condition') }}">{{ __('terms_condition') }}</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.refund-cancellation') }}">{{ __('refund_cancellation') }}</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.contact-us') }}">{{ __('contact_us') }}</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('school-settings.about-us') }}">{{ __('about_us') }}</a>
                                            </li>
                                        @endrole

                                        @can('privacy-policy')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.privacy-policy') }}">{{ __('privacy_policy') }}</a>
                                            </li>
                                        @endcan

                                        @can('terms-condition')
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('system-settings.terms-condition') }}">{{ __('terms_condition') }}</a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </li>
                        @endcanany

                        {{-- Web Settings --}}
                        @can('web-settings')
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#web_settings" aria-expanded="false"
                                    aria-controls="web_settings-menu">
                                    <i class="fa fa-cogs menu-icon"></i>
                                    <span class="menu-title">{{ __('web_settings') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="web_settings">
                                    <ul class="nav flex-column sub-menu">
                                        <li class="nav-item">
                                            <a class="nav-link"
                                                href="{{ route('web-settings.index') }}">{{ __('general_settings') }}</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link"
                                                href="{{ route('web-settings.feature.sections') }}">{{ __('feature_sections') }}</a>
                                        </li>

                                        @canany(['faqs-create', 'faqs-list', 'faqs-edit', 'faqs-delete'])
                                            <li class="nav-item">
                                                <a class="nav-link"
                                                    href="{{ route('faqs.index') }}">{{ __('faqs') }}</a>
                                            </li>
                                        @endcanany
                                    </ul>
                                </div>
                            </li>
                        @endcan

                        @can('school-web-settings')
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="collapse" href="#school-web_settings"
                                    aria-expanded="false" aria-controls="school-web_settings-menu"
                                    data-access="@hasFeatureAccess('Website Management')">
                                    <i class="fa fa-cogs menu-icon"></i>
                                    <span class="menu-title">{{ __('web_settings') }}</span>
                                    <i class="menu-arrow"></i>
                                </a>
                                <div class="collapse" id="school-web_settings">
                                    <ul class="nav flex-column sub-menu">
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('school.web-settings.index') }}"
                                                data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                data-access="@hasFeatureAccess('Website Management')">{{ __('content') }}</a>
                                        </li>

                                        @canany(['faqs-create', 'faqs-list', 'faqs-edit', 'faqs-delete'])
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('faqs.index') }}"
                                                    data-name="{{ Auth::user()->getRoleNames()[0] }}"
                                                    data-access="@hasFeatureAccess('Website Management')">{{ __('faqs') }}</a>
                                            </li>
                                        @endcanany
                                    </ul>
                                </div>
                            </li>
                        @endcan

                        {{-- Database Backup --}}
                        @if (Auth::user()->hasRole(['Super Admin', 'School Admin']) || Auth::user()->hasPermissionTo('database-backup'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('database-backup.index') }}">
                                    <i class="fa fa-database menu-icon"></i>
                                    <span class="menu-title">{{ __('database_backup') }}</span>
                                </a>
                            </li>
                        @endif

                        {{-- System Update --}}
                        @if (Auth::user()->hasRole('Super Admin'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('system-update.index') }}">
                                    <i class="fa fa-cloud-download menu-icon"></i>
                                    <span class="menu-title">{{ __('system_update') }}</span>
                                </a>
                            </li>
                        @endif

                        {{-- Documentation --}}
                        @if (Auth::user()->hasRole(['Super Admin']))
                            <li class="nav-item">
                                <a class="nav-link" href="https://wrteam-in.github.io/eSchool-SaaS-Doc/"
                                    target="_blank">
                                    <i class="fa fa-book menu-icon"></i>
                                    <span class="menu-title">{{ __('Documentation') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>
        @endif

        {{-- Contact Inquiry --}}
        @canany(['contact-inquiry-list'])
            <li class="nav-item">
                <a href="{{ url('contact-inquiry') }}" class="nav-link">
                    <i class="fa fa-envelope-o menu-icon"></i>
                    <span class="menu-title">{{ __('Contact Inquiry') }}</span>
                </a>
            </li>
        @endcanany

        @role('School Admin')
            {{-- Support --}}
            <li class="nav-item">
                <a href="{{ url('staff/support') }}" class="nav-link">
                    <i class="fa fa-question menu-icon"></i>
                    <span class="menu-title">{{ __('support') }}</span>
                </a>
            </li>
        @endrole
    </ul>
</nav>

</nav>
