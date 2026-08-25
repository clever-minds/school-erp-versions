(function ($) {
    'use strict';

    // Modern Sidebar Manager
    var SidebarManager = {
        isAnimating: false,

        // Smooth expand/collapse with height animation
        toggleCollapse: function ($toggle, $collapse) {
            if (this.isAnimating) return;

            var isExpanding = !$collapse.hasClass('show');

            if (isExpanding) {
                // Collapse ALL nested collapses first (optional, based on preference)
                // $collapse.find('.collapse.show').each(function() {
                //     SidebarManager.collapseItem($(this));
                // });

                // Expand this collapse
                this.expandItem($collapse);
                $toggle.attr('aria-expanded', 'true');
            } else {
                // Collapse this and all nested
                this.collapseItem($collapse);
                $toggle.attr('aria-expanded', 'false');
            }
        },

        expandItem: function ($collapse) {
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
            setTimeout(function () {
                $collapse.css('height', 'auto');
                self.isAnimating = false;
            }, 200);
        },

        collapseItem: function ($collapse) {
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
            setTimeout(function () {
                $collapse.removeClass('show');
                $collapse.css({
                    'display': '',
                    'height': '',
                    'opacity': ''
                });
                self.isAnimating = false;
            }, 200);

            // Collapse all nested items
            $collapse.find('.collapse.show').each(function () {
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
        setActiveState: function () {
            var currentUrl = window.location.href;
            var currentPath = window.location.pathname;
            var $activeLink = null;

            // Normalize current URL and path
            var normalizedCurrent = currentUrl.replace(/\/$/, '').split('?')[0];
            var normalizedPath = currentPath.replace(/\/$/, '');

            // Helper function to extract path from URL
            function getPathFromUrl(url) {
                try {
                    if (url.indexOf('http') === 0 || url.indexOf('//') === 0) {
                        var urlObj = new URL(url);
                        return urlObj.pathname.replace(/\/$/, '');
                    } else {
                        return url.split('?')[0].split('#')[0].replace(/\/$/, '');
                    }
                } catch (e) {
                    return url.split('?')[0].split('#')[0].replace(/\/$/, '');
                }
            }

            // Helper function to check if URL matches
            function urlMatches(href, currentUrl, currentPath) {
                if (!href || href === '#' || href === 'javascript:void(0)') return { match: false, priority: 0, length: 0 };

                var hrefPath = getPathFromUrl(href);
                var currentPathClean = normalizedPath;

                hrefPath = hrefPath || '';
                currentPathClean = currentPathClean || '';

                // Exact match (highest priority)
                if (currentPathClean === hrefPath || currentUrl.replace(/\/$/, '') === href.replace(/\/$/, '')) {
                    return { match: true, priority: 1, length: hrefPath.length };
                }

                // Path segment matching
                if (hrefPath && hrefPath !== '/' && currentPathClean.indexOf(hrefPath) === 0) {
                    var nextChar = currentPathClean.charAt(hrefPath.length);
                    if (nextChar === '' || nextChar === '/') {
                        return { match: true, priority: 2, length: hrefPath.length };
                    }
                }

                return { match: false, priority: 0, length: 0 };
            }


            var matches = [];
            $('#sidebar .nav-link').each(function () {
                var $link = $(this);
                var href = $link.attr('href');

                if (href && !$link.attr('data-toggle')) {
                    var match = urlMatches(href, normalizedCurrent, normalizedPath);
                    if (match.match) {
                        matches.push({
                            $link: $link,
                            priority: match.priority,
                            length: match.length
                        });
                    }
                }
            });

            matches.sort(function (a, b) {
                if (a.priority !== b.priority) {
                    return a.priority - b.priority;
                }
                return b.length - a.length;
            });

            if (matches.length > 0) {
                $activeLink = matches[0].$link;
            }

            // Clear all states
            $('#sidebar .nav-link').removeClass('active');
            $('#sidebar .nav-item').removeClass('active parent-active');

            if ($activeLink) {
                $activeLink.addClass('active');
                $activeLink.closest('.nav-item').addClass('active');

                var $current = $activeLink.closest('.nav-item');
                var pathToExpand = [];

                while ($current.length) {
                    pathToExpand.unshift($current);
                    $current = $current.parent().closest('.nav-item');
                }

                pathToExpand.forEach(function ($item) {
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

                // Scroll to the active item after expansion
                setTimeout(function () {
                    SidebarManager.scrollToActive();
                }, 500); // Increased delay for smoother transition after multiple expansions
            }
        },

        scrollToActive: function () {
            const sidebar = document.querySelector('#sidebar');
            if (!sidebar) return;

            // Try to find the active link first as it's the most specific
            const activeLink = sidebar.querySelector('.nav-link.active');
            const activeItem = activeLink ? activeLink.closest('.nav-item') : sidebar.querySelector('.nav-item.active');

            if (!activeItem) return;

            // Determine the scrollable container. Usually it's the #sidebar itself 
            // if it has overflow:auto/scroll, or a parent .sidebar-offcanvas
            let scrollContainer = sidebar;

            // Check if #sidebar is the one scrolling
            const style = window.getComputedStyle(sidebar);
            if (style.overflowY !== 'auto' && style.overflowY !== 'scroll') {
                // If not, check the parent
                const parent = sidebar.closest('.sidebar');
                if (parent) {
                    const parentStyle = window.getComputedStyle(parent);
                    if (parentStyle.overflowY === 'auto' || parentStyle.overflowY === 'scroll') {
                        scrollContainer = parent;
                    }
                }
            }

            // Function to get cumulative offsetTop relative to the scroll container
            function getCumulativeOffset(element, container) {
                let top = 0;
                let current = element;
                while (current && current !== container && container.contains(current)) {
                    top += current.offsetTop || 0;
                    current = current.offsetParent;
                }
                return top;
            }


            // Ensure the item is visible before calculating
            if (activeItem.offsetWidth === 0 && activeItem.offsetHeight === 0) {
                setTimeout(() => SidebarManager.scrollToActive(), 100);
                return;
            }

            const containerHeight = scrollContainer.clientHeight;
            const scrollTop = scrollContainer.scrollTop;
            const itemTop = getCumulativeOffset(activeItem, scrollContainer);
            const itemHeight = activeItem.offsetHeight;

            // Check if the item is already fully visible
            const isVisible = (itemTop >= scrollTop) && ((itemTop + itemHeight) <= (scrollTop + containerHeight));

            if (!isVisible) {
                // Target position: active item at 20% from the top of the sidebar
                const targetScrollTop = itemTop - (containerHeight * 0.5);

                // Smooth scroll with a slightly longer duration for better UX
                $(scrollContainer).stop().animate({
                    scrollTop: Math.max(0, targetScrollTop)
                }, 800);
            }
        }

    };

    $(document).ready(function () {
        // Prevent default Bootstrap collapse behavior on sidebar to use our custom animation
        $(document).on('show.bs.collapse', '#sidebar .collapse', function (e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        });

        // Handle collapse toggle clicks
        $('#sidebar').on('click', '.nav-link[data-toggle="collapse"]', function (e) {
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
        $('#sidebar').on('click', '.nav-link:not([data-toggle="collapse"])', function () {
            var $link = $(this);
            $('#sidebar .nav-link').removeClass('active');
            $('#sidebar .nav-item').removeClass('active parent-active');

            $link.addClass('active');
            $link.closest('.nav-item').addClass('active');

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

        // Initial setup
        SidebarManager.setActiveState();

        // Re-check after a short delay to ensure dynamic content is loaded
        setTimeout(function () {
            SidebarManager.setActiveState();
        }, 100);
    });

})(jQuery);
