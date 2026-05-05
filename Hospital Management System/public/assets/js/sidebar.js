// Sidebar Toggle Functionality
(function() {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const topbar = document.getElementById('topbar');
    const toggleBtn = document.getElementById('toggleBtn');
    const mobileBtn = document.getElementById('mobileBtn');
    const overlay = document.getElementById('overlay');

    console.log('Sidebar.js loaded', { sidebar, toggleBtn, mobileBtn });

    // Desktop sidebar collapse toggle
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Toggle button clicked from sidebar.js');

            sidebar.classList.toggle('collapsed');
            if (content) content.classList.toggle('full');
            if (topbar) topbar.classList.toggle('full');

            // Save preference to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    } else {
        console.warn('Toggle button or sidebar not found in sidebar.js', { toggleBtn, sidebar });
    }

    // Mobile sidebar open
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            sidebar.classList.add('mobile-show');
            if (overlay) overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('mobile-show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    // Restore sidebar state on page load (desktop only)
    if (sidebar && window.innerWidth >= 992) {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            if (content) content.classList.add('full');
            if (topbar) topbar.classList.add('full');
        }
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            // Desktop: remove mobile classes
            if (sidebar) sidebar.classList.remove('mobile-show');
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
        } else {
            // Mobile: remove collapsed state
            if (sidebar) sidebar.classList.remove('collapsed');
            if (content) content.classList.remove('full');
            if (topbar) topbar.classList.remove('full');
        }
    });

    // Active nav link highlighting based on current URL
    function setActiveNavLink() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.sidebar .nav-link');

        navLinks.forEach(function(link) {
            const href = link.getAttribute('href');
            if (href && href !== '#') {
                // Check if current path starts with link href (for nested routes)
                if (currentPath === href || currentPath.startsWith(href + '/')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            }
        });
    }

    // Set active link on page load
    setActiveNavLink();
})();