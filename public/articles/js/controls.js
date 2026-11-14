/**
 * GreenTech FrontOffice JavaScript Controls
 * Handles form validation, AJAX submissions, and UI interactions
 */

// Initialize controls when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeCreateForm();
    initializeCommentForm();
    initializeLikeButton();
    initializeSaveButton();
    initializeArticleCards();
    initializeArticleMenu();
    initializeCommentActions();
    initializeReplyActions();
    initializeReportButtons();
    initializeSearch();
    initializeBackToTop();
    initializeScrollAnimations();
});

/**
 * Initialize Create Article Form Validation
 */
function initializeCreateForm() {
    const forms = document.querySelectorAll('form');
    if (forms.length === 0) return;

    forms.forEach(form => {
        // Check if this is a create form by looking for title, content, and image fields
        const title = form.querySelector('input[name="title"]');
        const content = form.querySelector('textarea[name="content"]');
        const image = form.querySelector('input[name="image"]');

        // Only attach listener if this looks like a create form
        if (!title || !content || !image) return;

        // Disable browser native validation to use our custom validation
        form.noValidate = true;

        form.addEventListener('submit', function(e) {
            // Validate title
            if (title.value.trim() === '') {
                e.preventDefault();
                showError('Le titre est requis');
                return false;
            }

            if (title.value.trim().length < 3) {
                e.preventDefault();
                showError('Le titre doit contenir au moins 3 caractères');
                return false;
            }

            // Validate content
            if (content.value.trim() === '') {
                e.preventDefault();
                showError('Le contenu est requis');
                return false;
            }

            if (content.value.trim().length < 10) {
                e.preventDefault();
                showError('Le contenu doit contenir au moins 10 caractères');
                return false;
            }

            // Validate image
            if (image.files.length === 0) {
                e.preventDefault();
                showError('Veuillez sélectionner une image');
                return false;
            }

            // Validate image type
            const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validImageTypes.includes(image.files[0].type)) {
                e.preventDefault();
                showError('Veuillez sélectionner un fichier image valide (JPEG, PNG, GIF ou WebP)');
                return false;
            }

            // Validate image size (max 5MB)
            if (image.files[0].size > 5 * 1024 * 1024) {
                e.preventDefault();
                showError('La taille de l\'image ne doit pas dépasser 5MB');
                return false;
            }

            showSuccess('Création de l\'article...');
        });
    });
}

/**
 * Initialize Comment Form Validation
 */
function initializeCommentForm() {
    const commentForm = document.getElementById('comment-form');
    if (!commentForm) return;

    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const commentTextarea = this.querySelector('textarea[name="comment"]');
        const commentText = commentTextarea.value.trim();

        if (commentText === '') {
            showError('Veuillez écrire un commentaire');
            return;
        }

        if (commentText.length < 2) {
            showError('Le commentaire doit contenir au moins 2 caractères');
            return;
        }

        // Allow form to submit normally (PHP will handle it)
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        showSuccess('Publication du commentaire...');
        
        // Let the form submit normally
        setTimeout(() => {
            this.submit();
        }, 300);
    });
}

/**
 * Initialize Like Button with AJAX
 */
function initializeLikeButton() {
    const likeButton = document.getElementById('likeBtn');
    
    if (!likeButton) return;

    const articleId = likeButton.dataset.articleId;
    let isLiked = likeButton.dataset.liked === 'true';

    // Update button appearance based on like status
    updateLikeButtonStyle(likeButton, isLiked);

    likeButton.addEventListener('click', function(e) {
        e.preventDefault();

        // Prevent double clicks
        if (this.disabled) return;

        // Disable button during request
        this.disabled = true;

        // Send AJAX request
        fetch('api_like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'articleId=' + encodeURIComponent(articleId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update like count
                document.querySelector('.like-count').textContent = data.likesCount;

                // Update button state - use the returned hasLiked value
                isLiked = data.hasLiked;
                likeButton.dataset.liked = data.hasLiked ? 'true' : 'false';
                updateLikeButtonStyle(likeButton, data.hasLiked);

                // Add heart animation
                addHeartAnimation();

                // No message - just animation
            } else {
                showError(data.message || 'Erreur lors de la mise à jour du like');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Échec de la mise à jour du like');
        })
        .finally(() => {
            // Re-enable button
            this.disabled = false;
        });

        // Add visual feedback - scale animation
        this.style.transform = 'scale(0.9)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });

    // Add CSS transition for smooth animation
    likeButton.style.transition = 'transform 0.15s ease, background-color 0.3s ease, color 0.3s ease';
}

/**
 * Update like button style based on like status
 */
function updateLikeButtonStyle(button, isLiked) {
    const icon = button.querySelector('.heart-icon');
    
    if (isLiked) {
        button.style.backgroundColor = '#ff1744';
        button.style.color = 'white';
        icon.textContent = '❤️';
        button.classList.add('liked');
    } else {
        button.style.backgroundColor = '';
        button.style.color = '';
        icon.textContent = '🤍';
        button.classList.remove('liked');
    }
}

/**
 * Initialize Save/Bookmark Button
 */
function initializeSaveButton() {
    const saveButton = document.getElementById('saveBtn');
    if (!saveButton) return;

    const articleId = saveButton.dataset.articleId;
    let isSaved = saveButton.dataset.saved === 'true';

    // Update button appearance based on save status
    updateSaveButtonStyle(saveButton, isSaved);

    saveButton.addEventListener('click', function(e) {
        e.preventDefault();

        // Prevent double clicks
        if (this.disabled) return;

        // Disable button during request
        this.disabled = true;

        // Send AJAX request
        fetch('api_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'articleId=' + encodeURIComponent(articleId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update save status
                isSaved = data.action === 'saved';
                saveButton.dataset.saved = isSaved ? 'true' : 'false';
                updateSaveButtonStyle(saveButton, isSaved);

                // Show feedback
                showSuccess(data.message);
            } else {
                showError(data.message || 'Erreur lors de la sauvegarde');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Échec de la sauvegarde');
        })
        .finally(() => {
            // Re-enable button
            this.disabled = false;
        });

        // Add visual feedback - scale animation
        this.style.transform = 'scale(0.9)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });

    // Add CSS transition for smooth animation
    saveButton.style.transition = 'transform 0.15s ease, background-color 0.3s ease, color 0.3s ease';
}

/**
 * Update save button style based on save status
 */
function updateSaveButtonStyle(button, isSaved) {
    const icon = button.querySelector('.save-icon');
    
    if (isSaved) {
        button.style.backgroundColor = '#ffc107';
        button.style.color = 'white';
        icon.textContent = '🔖';
    } else {
        button.style.backgroundColor = '';
        button.style.color = '';
        icon.textContent = '📌';
    }
}

/**
 * Add heart animation particles
 */
function addHeartAnimation() {
    const btn = document.getElementById('likeBtn');
    if (!btn) return;

    // Create multiple floating hearts
    for (let i = 0; i < 5; i++) {
        const heart = document.createElement('div');
        heart.textContent = '❤️';
        heart.style.cssText = `
            position: fixed;
            left: ${btn.getBoundingClientRect().left + btn.offsetWidth / 2}px;
            top: ${btn.getBoundingClientRect().top}px;
            font-size: ${20 + Math.random() * 10}px;
            opacity: 1;
            pointer-events: none;
            z-index: 9999;
        `;

        document.body.appendChild(heart);

        // Animate the heart floating up and fading
        let duration = 0;
        const interval = setInterval(() => {
            duration += 20;
            const progress = duration / 800;
            
            heart.style.opacity = 1 - progress;
            heart.style.transform = `translateY(${-duration / 4}px) translateX(${Math.sin(progress * Math.PI * 2) * 30}px)`;

            if (progress >= 1) {
                clearInterval(interval);
                heart.remove();
            }
        }, 20);
    }
}

/**
 * Initialize Article Cards with Click Interactions
 */
function initializeArticleCards() {
    const articleCards = document.querySelectorAll('.article-card');
    
    articleCards.forEach(card => {
        card.style.cursor = 'pointer';
        
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.2)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });

        // Add transition CSS if not already applied
        card.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
    });
}

/**
 * Initialize Article Menu (Edit/Delete for owners)
 */
function initializeArticleMenu() {
    // Handle menu button clicks
    document.addEventListener('click', function(e) {
        const menuBtn = e.target.closest('.article-menu-btn');
        if (menuBtn) {
            e.stopPropagation();
            const articleId = menuBtn.dataset.articleId;
            const dropdown = document.getElementById(`menu-${articleId}`);
            
            if (dropdown) {
                // Close all other menus
                document.querySelectorAll('.article-menu-dropdown').forEach(menu => {
                    if (menu.id !== `menu-${articleId}`) {
                        menu.style.display = 'none';
                    }
                });
                
                // Toggle current menu
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            }
        }
    });
    
    // Handle delete button clicks
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-item');
        if (deleteBtn) {
            e.preventDefault();
            const articleId = deleteBtn.dataset.articleId;
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                deleteBtn.disabled = true;
                
                fetch('api_article.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&articleId=${articleId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Article supprimé avec succès');
                        // Reload page after success
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showError(data.error || 'Erreur lors de la suppression de l\'article');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Échec de la suppression de l\'article');
                })
                .finally(() => {
                    deleteBtn.disabled = false;
                });
            }
        }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.article-menu')) {
            document.querySelectorAll('.article-menu-dropdown').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
}

/**
 * Show error message as fixed modal
 * @param {string} message - Error message to display
 */
function showError(message) {
    removeExistingMessages();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'message-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f8d7da;
        border: 2px solid #721c24;
        color: #721c24;
        padding: 16px 24px;
        border-radius: 8px;
        animation: slideDown 0.3s ease;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        max-width: 90%;
        font-weight: 500;
    `;

    document.body.appendChild(errorDiv);

    // Keep message visible and auto-dismiss
    setTimeout(removeExistingMessages, 5000);
    
    // Also log to console for debugging
    console.warn('Form Error:', message);
}

/**
 * Show success message as fixed modal
 * @param {string} message - Success message to display
 */
function showSuccess(message) {
    removeExistingMessages();
    
    const successDiv = document.createElement('div');
    successDiv.className = 'message-success';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #d4edda;
        border: 2px solid #155724;
        color: #155724;
        padding: 16px 24px;
        border-radius: 8px;
        animation: slideDown 0.3s ease;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        max-width: 90%;
        font-weight: 500;
    `;

    document.body.appendChild(successDiv);

    // Auto-dismiss after 4 seconds
    setTimeout(removeExistingMessages, 4000);
}

/**
 * Initialize Comment Actions (edit, delete, like)
 */
function initializeCommentActions() {
    console.log('initializeCommentActions called');
    console.log('Comment like buttons found:', document.querySelectorAll('.comment-like-btn').length);
    console.log('Reply like buttons found:', document.querySelectorAll('.reply-like-btn').length);
    
    // Edit buttons
    document.querySelectorAll('.comment-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const editForm = document.getElementById(`edit-form-${commentId}`);
            const commentBody = document.getElementById(`body-${commentId}`);
            
            commentBody.style.display = 'none';
            editForm.style.display = 'block';
        });
    });

    // Cancel edit buttons
    document.querySelectorAll('.comment-cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const editForm = document.getElementById(`edit-form-${commentId}`);
            const commentBody = document.getElementById(`body-${commentId}`);
            
            editForm.style.display = 'none';
            commentBody.style.display = 'block';
        });
    });

    // Save edit buttons
    document.querySelectorAll('.comment-save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const editText = document.getElementById(`edit-text-${commentId}`).value.trim();

            if (!editText) {
                showError('Le commentaire ne peut pas être vide');
                return;
            }

            this.disabled = true;

            fetch('api_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=edit&commentId=${commentId}&commentText=${encodeURIComponent(editText)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`text-${commentId}`).innerHTML = editText.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                    document.getElementById(`edit-form-${commentId}`).style.display = 'none';
                    document.getElementById(`body-${commentId}`).style.display = 'block';
                    showSuccess('Commentaire mis à jour');
                } else {
                    showError(data.message || 'Erreur lors de la mise à jour du commentaire');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Échec de la mise à jour du commentaire');
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });

    // Delete buttons
    document.querySelectorAll('.comment-delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')) {
                const commentId = this.dataset.commentId;
                this.disabled = true;

                fetch('api_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&commentId=${commentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`[data-comment-id="${commentId}"]`).remove();
                        showSuccess('Commentaire supprimé');
                    } else {
                        showError(data.message || 'Erreur lors de la suppression du commentaire');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Échec de la suppression du commentaire');
                    this.disabled = false;
                });
            }
        });
    });

    // Comment like buttons
    document.querySelectorAll('.comment-like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('COMMENT LIKE BUTTON CLICKED!', e);
            e.preventDefault();
            e.stopPropagation();
            
            const commentId = this.dataset.commentId;
            const button = this;
            const countSpan = button.querySelector('.comment-likes-count');
            const oldCount = countSpan.textContent;
            console.log('Comment like button clicked, ID:', commentId);
            console.log('Current display count:', oldCount);
            console.log('Button element:', button);
            button.disabled = true;

            fetch('api_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=like&commentId=${commentId}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                console.log('New likes count from server:', data.likesCount);
                if (data.success) {
                    // Update the likes count inside the button
                    console.log('Updating count from', countSpan.textContent, 'to', data.likesCount);
                    countSpan.textContent = data.likesCount;
                    console.log('Count span now shows:', countSpan.textContent);
                    button.dataset.liked = data.hasLiked ? 'true' : 'false';
                    updateCommentLikeButtonStyle(button, data.hasLiked);
                } else {
                    showError(data.message || 'Erreur lors du like du commentaire');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Échec du like du commentaire');
            })
            .finally(() => {
                button.disabled = false;
            });
        });
    });

    // Initialize comment like button styles
    document.querySelectorAll('.comment-like-btn').forEach(btn => {
        const isLiked = btn.dataset.liked === 'true';
        updateCommentLikeButtonStyle(btn, isLiked);
    });
}

/**
 * Initialize Reply Actions (show form, submit reply, edit reply, delete reply)
 */
function initializeReplyActions() {
    // Reply buttons - show reply form
    document.querySelectorAll('.comment-reply-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            
            if (replyForm) {
                const isVisible = replyForm.style.display !== 'none';
                replyForm.style.display = isVisible ? 'none' : 'block';
                
                // Focus textarea if showing
                if (!isVisible) {
                    const textarea = replyForm.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            }
        });
    });

    // Nested reply buttons (reply to replies) - use event delegation for dynamic elements
    document.addEventListener('click', function(e) {
        if (e.target.closest('.reply-reply-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.reply-reply-btn');
            const commentId = btn.dataset.commentId;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            
            if (replyForm) {
                const isVisible = replyForm.style.display !== 'none';
                replyForm.style.display = isVisible ? 'none' : 'block';
                
                // Focus textarea if showing
                if (!isVisible) {
                    const textarea = replyForm.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            }
        }
    });

    // Cancel reply buttons
    document.querySelectorAll('.btn-cancel-reply').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            if (replyForm) {
                replyForm.style.display = 'none';
                // Clear textarea
                const textarea = replyForm.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
        });
    });

    // Submit reply buttons
    document.querySelectorAll('.btn-submit-reply').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('.reply-form-input');
            const textarea = form.querySelector('textarea[name="comment"]');
            const replyText = textarea.value.trim();
            const parentCommentId = form.querySelector('input[name="parent_comment_id"]').value;
            const articleId = form.querySelector('input[name="article_id"]').value;
            const userName = form.querySelector('input[name="user_name"]').value;

            if (!replyText) {
                showError('Veuillez écrire une réponse');
                return;
            }

            if (replyText.length < 2) {
                showError('La réponse doit contenir au moins 2 caractères');
                return;
            }

            this.disabled = true;

            // Send reply to server
            fetch('api_reply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_reply&article_id=${articleId}&parent_comment_id=${parentCommentId}&comment=${encodeURIComponent(replyText)}&user_name=${encodeURIComponent(userName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear textarea and hide form
                    textarea.value = '';
                    const replyForm = form.closest('.comment-reply-form');
                    replyForm.style.display = 'none';
                    showSuccess('Réponse publiée');
                    
                    // Reload page to show new reply
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    showError(data.error || 'Erreur lors de la publication de la réponse');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Échec de la publication de la réponse');
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });

    // Reply edit buttons
    document.querySelectorAll('.reply-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const editForm = document.getElementById(`edit-form-${commentId}`);
            const replyCard = this.closest('.reply-card');
            
            if (editForm) {
                editForm.style.display = editForm.style.display === 'none' ? 'block' : 'none';
            }
        });
    });

    // Reply cancel edit buttons
    document.querySelectorAll('.reply-cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const editForm = document.getElementById(`edit-form-${commentId}`);
            
            if (editForm) {
                editForm.style.display = 'none';
            }
        });
    });

    // Reply save edit buttons
    document.querySelectorAll('.reply-save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const editText = document.getElementById(`edit-text-${commentId}`).value.trim();

            if (!editText) {
                showError('La réponse ne peut pas être vide');
                return;
            }

            this.disabled = true;

            fetch('api_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=edit&commentId=${commentId}&commentText=${encodeURIComponent(editText)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`reply-text-${commentId}`).innerHTML = editText.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                    document.getElementById(`edit-form-${commentId}`).style.display = 'none';
                    showSuccess('Réponse mise à jour');
                } else {
                    showError(data.message || 'Erreur lors de la mise à jour de la réponse');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Échec de la mise à jour de la réponse');
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });

    // Reply delete buttons
    document.querySelectorAll('.reply-delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?')) {
                const commentId = this.dataset.commentId;
                this.disabled = true;

                fetch('api_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&commentId=${commentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`[data-reply-id="${commentId}"]`).remove();
                        showSuccess('Réponse supprimée');
                    } else {
                        showError(data.message || 'Erreur lors de la suppression de la réponse');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Échec de la suppression de la réponse');
                    this.disabled = false;
                });
            }
        });
    });

    // Reply like buttons
    document.querySelectorAll('.reply-like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('REPLY LIKE BUTTON CLICKED!', e);
            e.preventDefault();
            e.stopPropagation();
            const replyId = this.dataset.replyId;
            const button = this;
            console.log('Reply like button clicked, ID:', replyId);
            console.log('Button element:', button);
            button.disabled = true;

            fetch('api_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=like&commentId=${replyId}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Update the likes count inside the button
                    button.querySelector('.reply-likes-count').textContent = data.likesCount;
                    button.dataset.liked = data.hasLiked ? 'true' : 'false';
                    updateReplyLikeButtonStyle(button, data.hasLiked);
                } else {
                    showError(data.message || 'Erreur lors du like de la réponse');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Échec du like de la réponse');
            })
            .finally(() => {
                button.disabled = false;
            });
        });

        // Initialize reply like button styles
        const isLiked = btn.dataset.liked === 'true';
        updateReplyLikeButtonStyle(btn, isLiked);
    });
}

/**
 * Update reply like button style
 */
function updateReplyLikeButtonStyle(btn, isLiked) {
    if (isLiked) {
        btn.style.backgroundColor = '#ef4444';
        btn.style.color = 'white';
    } else {
        btn.style.backgroundColor = '';
        btn.style.color = '';
    }
}

/**
 * Update comment like button style
 */
function updateCommentLikeButtonStyle(btn, isLiked) {
    if (isLiked) {
        btn.style.backgroundColor = '#ff1744';
        btn.style.color = 'white';
    } else {
        btn.style.backgroundColor = '';
        btn.style.color = '';
    }
}

/**
 * Initialize Comment and Reply Report Buttons
 */
function initializeReportButtons() {
    // Handle comment and reply report buttons with event delegation
    document.addEventListener('click', function(e) {
        const reportBtn = e.target.closest('.comment-report-btn, .reply-report-btn');
        if (reportBtn) {
            e.preventDefault();
            const commentId = reportBtn.dataset.commentId;
            
            // Store the comment ID in the form for submission
            const reportForm = document.getElementById('reportForm');
            if (reportForm) {
                // Store comment ID temporarily in a data attribute
                reportForm.dataset.reportCommentId = commentId;
                
                // Open the report modal
                const reportModal = document.getElementById('reportModal');
                if (reportModal) {
                    reportModal.classList.add('active');
                }
            }
        }
    });
    
    // Handle report form submission for comments/replies
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        // Store the original submit handler if it exists
        const originalHandler = reportForm.onsubmit;
        
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const commentId = this.dataset.reportCommentId;
            
            // If there's a comment ID, this is a comment/reply report
            if (commentId) {
                const reason = document.getElementById('reportReason').value;
                const details = document.getElementById('reportDetails').value;
                const email = document.getElementById('reportEmail').value;
                
                if (!reason) {
                    showError('Veuillez sélectionner une raison');
                    return;
                }
                
                // Send comment/reply report
                fetch('api_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        comment_id: commentId,
                        reason_type: reason,
                        details: details,
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Merci! Votre signalement a été envoyé.');
                        document.getElementById('closeReportModal')?.click();
                        reportForm.reset();
                        delete reportForm.dataset.reportCommentId;
                    } else {
                        showError(data.error || 'Erreur lors de l\'envoi du signalement');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Échec du signalement');
                });
            }
        });
    }
}



/**
 * Remove existing message divs
 */
function removeExistingMessages() {
    const messages = document.querySelectorAll('.message-error, .message-success');
    messages.forEach(msg => {
        msg.classList.add('fade-out');
        setTimeout(() => msg.remove(), 300);
    });
}

/**
 * Initialize Search Functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    // Get all article cards for searching
    const articleCards = Array.from(document.querySelectorAll('.article-card'));
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            // Reset - show all articles
            articleCards.forEach(card => {
                card.style.display = '';
                card.style.opacity = '1';
            });
            return;
        }

        // Filter articles
        let hasResults = false;
        articleCards.forEach(card => {
            const title = card.querySelector('.article-card-link h2')?.textContent.toLowerCase() || '';
            const content = card.querySelector('.article-card-link p')?.textContent.toLowerCase() || '';
            
            const matches = title.includes(searchTerm) || content.includes(searchTerm);
            
            if (matches) {
                card.style.display = '';
                card.style.opacity = '1';
                card.style.animation = 'fadeIn 0.3s ease';
                hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Show no results message
        const container = document.querySelector('.articles-container');
        if (!hasResults && container) {
            let noResults = container.querySelector('.no-results');
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.innerHTML = `
                    <p>😔 Aucun article trouvé pour "<strong>${searchTerm}</strong>"</p>
                    <p style="font-size: 14px; color: #999;">Essayez une autre recherche</p>
                `;
                noResults.style.cssText = `
                    text-align: center;
                    padding: 60px 20px;
                    grid-column: 1 / -1;
                    animation: fadeIn 0.3s ease;
                `;
                container.appendChild(noResults);
            }
        } else if (container) {
            const noResults = container.querySelector('.no-results');
            if (noResults) noResults.remove();
        }
    });
}

/**
 * Initialize Back to Top Button
 */
function initializeBackToTop() {
    // Create back to top button
    const backToTopBtn = document.createElement('button');
    backToTopBtn.innerHTML = '⬆️';
    backToTopBtn.id = 'backToTopBtn';
    backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: #10b981;
        color: white;
        border: none;
        font-size: 24px;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 999;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    `;
    
    document.body.appendChild(backToTopBtn);

    // Show/hide button on scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.opacity = '1';
            backToTopBtn.style.visibility = 'visible';
        } else {
            backToTopBtn.style.opacity = '0';
            backToTopBtn.style.visibility = 'hidden';
        }
    });

    // Smooth scroll to top
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Hover effect
    backToTopBtn.addEventListener('mouseover', function() {
        this.style.backgroundColor = '#059669';
        this.style.transform = 'scale(1.1)';
    });

    backToTopBtn.addEventListener('mouseout', function() {
        this.style.backgroundColor = '#10b981';
        this.style.transform = 'scale(1)';
    });
}

/**
 * Initialize Scroll Animations
 */
function initializeScrollAnimations() {
    // Fade in elements on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // Observe article cards
    document.querySelectorAll('.article-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });
}



/**
 * Add slideDown animation
 */
if (!document.querySelector('style[data-controls]')) {
    const style = document.createElement('style');
    style.setAttribute('data-controls', 'true');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .message-error.fade-out,
        .message-success.fade-out {
            animation: slideOut 0.3s ease forwards;
        }
    `;
    document.head.appendChild(style);
}
