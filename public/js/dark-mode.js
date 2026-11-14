// dark-mode.js - Dark mode toggle functionality

(function () {
    'use strict';

    // Check for saved theme preference or default to light mode
    const currentTheme = localStorage.getItem('theme') || 'light';

    // Apply theme on page load
    document.documentElement.setAttribute('data-theme', currentTheme);

    // Create and add toggle button
    function createToggleButton() {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'theme-toggle';
        toggleBtn.setAttribute('aria-label', 'Toggle dark mode');
        toggleBtn.setAttribute('title', 'Toggle dark/light mode');

        updateToggleIcon(toggleBtn, currentTheme);

        toggleBtn.addEventListener('click', toggleTheme);

        document.body.appendChild(toggleBtn);
    }

    // Update toggle button icon
    function updateToggleIcon(button, theme) {
        if (theme === 'dark') {
            button.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            button.innerHTML = '<i class="fas fa-moon"></i>';
        }
    }

    // Toggle theme function
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        const toggleBtn = document.querySelector('.theme-toggle');
        if (toggleBtn) {
            updateToggleIcon(toggleBtn, newTheme);
        }

        // Optional: Add smooth transition effect
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createToggleButton);
    } else {
        createToggleButton();
    }

    // Expose toggle function globally for potential manual triggers
    window.toggleTheme = toggleTheme;
})();
