/**
 * FrontOffice Modal Functions
 * Handles report and share modal interactions
 */

// Report Modal Functions
function initializeReportModal() {
    const reportModal = document.getElementById('reportModal');
    const reportModalBtn = document.getElementById('reportModalBtn');
    const closeReportModal = document.getElementById('closeReportModal');
    const cancelReportBtn = document.getElementById('cancelReportBtn');
    const reportForm = document.getElementById('reportForm');
    const backdrop = document.querySelector('.report-modal-backdrop');
    
    let currentReportType = 'article';
    let currentReportEntityId = null;
    
    // Set initial article ID if available
    if (reportModalBtn) {
        const articleId = reportModalBtn.getAttribute('data-articleId') || reportModalBtn.dataset.articleId;
        if (articleId) {
            currentReportEntityId = parseInt(articleId);
        }
    }
    
    // Open modal for article
    if (reportModalBtn) {
        reportModalBtn.addEventListener('click', function() {
            currentReportType = 'article';
            const articleId = this.getAttribute('data-articleId') || this.dataset.articleId;
            if (articleId) {
                currentReportEntityId = parseInt(articleId);
            }
            reportModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Open modal for comment
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('comment-report-btn')) {
            e.preventDefault();
            currentReportType = 'comment';
            currentReportEntityId = parseInt(e.target.dataset.commentId);
            reportModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });
    
    // Close modal
    function closeModal() {
        reportModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        reportForm.reset();
    }
    
    if (closeReportModal) {
        closeReportModal.addEventListener('click', closeModal);
    }
    if (cancelReportBtn) {
        cancelReportBtn.addEventListener('click', closeModal);
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }
    
    // Escape key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reportModal.classList.contains('active')) {
            closeModal();
        }
    });
    
    // Handle form submission
    reportForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const reasonType = document.getElementById('reportReason').value;
        const details = document.getElementById('reportDetails').value;
        const email = document.getElementById('reportEmail').value;
        
        if (!reasonType) {
            showError('Veuillez sélectionner une raison');
            return;
        }
        
        let reason = reasonType;
        if (details) {
            reason = `${reasonType}: ${details}`;
        }
        
        // Debug logging
        console.log('Report submission:', {
            report_type: currentReportType,
            entity_id: currentReportEntityId,
            reason: reason,
            email: email || null
        });
        
        try {
            const response = await fetch('api_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    report_type: currentReportType,
                    entity_id: currentReportEntityId,
                    reason: reason,
                    email: email || null
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                showSuccess(data.message);
                closeModal();
            } else {
                showError(data.error || 'Erreur lors de l\'envoi du signalement');
            }
        } catch (error) {
            showError('Erreur: ' + error.message);
        }
    });
}

// Share Modal Functions
function initializeShareModal() {
    const shareModal = document.getElementById('shareModal');
    const shareModalBtn = document.getElementById('shareModalBtn');
    const closeShareModal = document.getElementById('closeShareModal');
    const backdrop = document.querySelector('.share-modal-backdrop');
    
    // Open modal
    if (shareModalBtn) {
        shareModalBtn.addEventListener('click', function() {
            shareModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close modal
    function closeModal() {
        shareModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    
    if (closeShareModal) {
        closeShareModal.addEventListener('click', closeModal);
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }
    
    // Escape key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && shareModal.classList.contains('active')) {
            closeModal();
        }
    });

    // Share actions
    const shareOptions = document.querySelectorAll('.share-option');
    shareOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            const action = this.dataset.action;
            
            if (action === 'copy') {
                e.preventDefault();
                copyShareLink();
            } else if (action === 'facebook') {
                e.preventDefault();
                const url = encodeURIComponent(window.location.href);
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, 'facebook-share', 'width=600,height=400');
            } else if (action === 'twitter') {
                e.preventDefault();
                const url = encodeURIComponent(window.location.href);
                const text = encodeURIComponent(document.querySelector('h1').textContent);
                window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, 'twitter-share', 'width=600,height=400');
            } else if (action === 'email') {
                e.preventDefault();
                const subject = encodeURIComponent(document.querySelector('h1').textContent);
                const body = encodeURIComponent(`J'ai trouvé cet article intéressant:\n\n${window.location.href}`);
                window.location.href = `mailto:?subject=${subject}&body=${body}`;
            } else if (action === 'whatsapp') {
                e.preventDefault();
                const text = encodeURIComponent(`${document.querySelector('h1').textContent}\n${window.location.href}`);
                window.open(`https://wa.me/?text=${text}`, '_blank');
            }
        });
    });
}

// Copy share link to clipboard
function copyShareLink() {
    const shareLink = document.getElementById('shareLink');
    if (!shareLink) return;
    
    shareLink.select();
    document.execCommand('copy');
    showSuccess('Lien copié dans le presse-papiers! 📋');
    
    // Visual feedback
    const copyBtn = document.querySelector('.copy-btn');
    if (copyBtn) {
        copyBtn.textContent = '✓';
        setTimeout(() => {
            copyBtn.textContent = '📋';
        }, 2000);
    }
}

// Initialize modals on page load
document.addEventListener('DOMContentLoaded', function() {
    const reportModal = document.getElementById('reportModal');
    const shareModal = document.getElementById('shareModal');
    
    console.log('Initializing modals...');
    
    if (reportModal) {
        console.log('Report modal found');
        const reportBtn = document.getElementById('reportModalBtn');
        if (reportBtn) {
            console.log('Report button found with data-articleId:', reportBtn.getAttribute('data-articleId'));
        }
        initializeReportModal();
    }
    if (shareModal) {
        console.log('Share modal found');
        initializeShareModal();
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeReportModal,
        initializeShareModal,
        copyShareLink
    };
}
