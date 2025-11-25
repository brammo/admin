/**
 * Main JavaScript functionality for the admin panel
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {

    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    const themeIconLight = document.getElementById('theme-icon-light');
    const themeIconDark = document.getElementById('theme-icon-dark');
    
    // Get stored theme preference or null if not set
    function getStoredTheme() {
        return localStorage.getItem('theme');
    }
    
    // Get current theme (use system default if not stored)
    function getCurrentTheme() {
        const stored = getStoredTheme();
        if (stored) {
            return stored;
        }
        // Use system preference as default
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    // Update theme icons visibility
    function updateThemeIcon(theme) {
        themeIconLight.classList.add('d-none');
        themeIconDark.classList.add('d-none');
        if (theme === 'light') {
            themeIconLight.classList.remove('d-none');
        } else {
            themeIconDark.classList.remove('d-none');
        }
    }
    
    // Apply theme to HTML element
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        updateThemeIcon(theme);
    }
    
    // Initialize theme on page load
    const currentTheme = getCurrentTheme();
    applyTheme(currentTheme);
    
    // Theme toggle click handler
    if (themeToggle) {
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const current = getCurrentTheme();
            const newTheme = current === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    }
    
    // Listen for system theme changes when no preference is stored
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
        if (!getStoredTheme()) {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            applyTheme(systemTheme);
        }
    });

    // Sidebar toggle button click handler
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const body = document.body;
            if (window.innerWidth >= 992) {
                body.classList.toggle('sidebar-collapse');
                if (body.classList.contains('sidebar-collapse')) {
                    localStorage.setItem('sidebar-collapsed', 'true');
                } else {
                    localStorage.removeItem('sidebar-collapsed');
                }
            } else {
                body.classList.toggle('sidebar-open');
            }
        });
    }

    // Restore sidebar state on page load (desktop only)
    if (window.innerWidth >= 992) {
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-collapse');
        }
    }

    // Create sidebar backdrop if it doesn't exist
    var backdrop = document.getElementById('sidebar-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
    }

    // Close sidebar when clicking backdrop (mobile)
    backdrop.addEventListener('click', function() {
        document.body.classList.remove('sidebar-open');
    });

    // Submenu toggle functionality
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parentLi = toggle.closest('.has-submenu');
            const isOpen = parentLi.classList.contains('submenu-open');
            // Toggle the submenu with slide animation
            if (isOpen) {
                parentLi.classList.remove('submenu-open');
            } else {
                parentLi.classList.add('submenu-open');
            }
        });
    });

});
