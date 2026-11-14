// Auto-refresh for pending reports or updates
function setupAutoRefresh(interval = 5000) {
    const refreshBtn = document.querySelector('[data-auto-refresh]');
    if (refreshBtn) {
        setInterval(() => {
            location.reload();
        }, interval);
    }
}

// Form submission handler
function setupFormHandlers() {
    const forms = document.querySelectorAll('form[data-async]');
    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            // Add custom async handling if needed
        });
    });
}

// Delete confirmation dialog
function confirmDelete(message = 'Are you sure? This action cannot be undone.') {
    return confirm(message);
}

// Tab switching
function setupTabs() {
    const tabs = document.querySelectorAll('.form-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Remove active class from all tabs
            document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab
            tab.classList.add('active');
            const tabId = tab.getAttribute('data-tab');
            if (tabId) {
                document.querySelector(`#${tabId}`).classList.add('active');
            }
        });
    });
}

// Search and filter setup
function setupSearchFilters() {
    const searchInputs = document.querySelectorAll('.search-input');
    const filterButtons = document.querySelectorAll('[data-filter]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(() => {
            applyFilters();
        }, 300));
    });
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', applyFilters);
    });
}

// Debounce function for search
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
}

// Apply filters to table/grid
function applyFilters() {
    const searchValue = document.querySelector('.search-input')?.value.toLowerCase() || '';
    const rows = document.querySelectorAll('tbody tr, .card-item');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
}

// Initialize all admin functions on page load
document.addEventListener('DOMContentLoaded', () => {
    setupTabs();
    setupSearchFilters();
    setupFormHandlers();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        confirmDelete,
        setupTabs,
        setupSearchFilters,
        applyFilters,
        debounce
    };
}
