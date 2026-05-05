@auth
<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const topbar = document.getElementById('topbar');
    const toggleBtn = document.getElementById('toggleBtn');
    const mobileBtn = document.getElementById('mobileBtn');
    const overlay = document.getElementById('overlay');

    console.log('Sidebar script loaded', { sidebar, toggleBtn, mobileBtn });

    // Desktop sidebar toggle
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Toggle button clicked');
            sidebar.classList.toggle('collapsed');
            if (content) content.classList.toggle('full');
            if (topbar) topbar.classList.toggle('full');

            // Store preference
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    } else {
        console.warn('Toggle button or sidebar not found', { toggleBtn, sidebar });
    }

    // Mobile sidebar toggle
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.add('mobile-show');
            if (overlay) overlay.classList.add('show');
        });
    }

    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('mobile-show');
            overlay.classList.remove('show');
        });
    }

    // Restore sidebar state on page load
    if (sidebar && window.innerWidth >= 992) {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            if (content) content.classList.add('full');
            if (topbar) topbar.classList.add('full');
        }
    }

    // Active nav link highlighting based on current route
    const currentRoute = '{{ Route::currentRouteName() }}';
    const navLinks = document.querySelectorAll('.sidebar .nav-link');

    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href !== '#') {
            // Check if this link matches current route
            const linkRoute = link.getAttribute('data-route');
            if (linkRoute && currentRoute && currentRoute.startsWith(linkRoute)) {
                link.classList.add('active');
            }
        }
    });
});
</script>
@endauth
