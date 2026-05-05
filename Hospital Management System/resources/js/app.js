// Import Bootstrap
import * as bootstrap from 'bootstrap';

// Sidebar functionality
const sidebar = document.getElementById('sidebar');
const topbar = document.getElementById('topbar');
const toggleBtn = document.getElementById('toggleBtn');
const mobileBtn = document.getElementById('mobileBtn');
const overlay = document.getElementById('overlay');
const content = document.getElementById('content');

// Desktop toggle
if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
        topbar.classList.toggle('expanded');
    });
}

// Mobile toggle
if (mobileBtn) {
    mobileBtn.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    });
}

// Close sidebar when clicking overlay
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
}

// Close sidebar when clicking content on mobile
if (content) {
    content.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
}

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
});

// Initialize all dropdowns
const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
dropdownElementList.forEach(dropdownToggle => {
    new bootstrap.Dropdown(dropdownToggle);
});

// Initialize all tooltips
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
tooltipTriggerList.forEach(tooltipTrigger => {
    new bootstrap.Tooltip(tooltipTrigger);
});

// Initialize all popovers
const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
popoverTriggerList.forEach(popoverTrigger => {
    new bootstrap.Popover(popoverTrigger);
});

// Export bootstrap for use in other modules
window.bootstrap = bootstrap;
