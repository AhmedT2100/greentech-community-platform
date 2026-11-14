/*
  auth-ui.js - cleaned and refactored
  - Dashboard charts and small UI helpers preserved
  - Login/signup handlers use shared validation helpers and inline error UI
*/
// auth-ui.js - cleaned and refactored
// Provides modal helpers, validation, login/signup handlers, password strength

const $ = (s) => document.querySelector(s);

// Detect if we're on the home page or articles page and set auth path accordingly
const getAuthPath = () => {
  const currentPath = window.location.pathname;
  if (currentPath.includes('/articles/')) {
    return '../auth.php'; // From articles page (one level up)
  }
  return 'auth.php'; // From home page
};

document.addEventListener('DOMContentLoaded', () => {
  console.log('auth-ui.js loaded successfully!');
  // Charts init (safe no-op)
  try {
    const ctxAnnual = document.getElementById('annualChart');
    if (ctxAnnual) {
      const annualData = {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
        datasets: [{ label: 'Innovations', data: [45, 60, 75, 80, 90, 100, 110, 105, 95, 85, 70, 65], borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.4 }]
      };
      new Chart(ctxAnnual, { type: 'line', data: annualData });
    }
  } catch (e) { console.warn('Charts init skipped', e); }

  // Vote placeholder
  document.querySelectorAll('.badge').forEach(b => b.addEventListener('click', e => { e.preventDefault(); alert('Fonctionnalité de vote - À implémenter'); }));

  // restore UI if user stored
  try { const s = localStorage.getItem('user'); if (s) updateUIAfterLogin(JSON.parse(s)); } catch (e) { }
});

// Modal helpers
function openLoginModal() { const el = document.getElementById('loginModal'); if (!el) return; new bootstrap.Modal(el).show(); }
function openSignupModal() { const el = document.getElementById('signupModal'); if (!el) return; new bootstrap.Modal(el).show(); }
function switchToSignup() { const m = bootstrap.Modal.getInstance(document.getElementById('loginModal')); if (m) m.hide(); setTimeout(openSignupModal, 250); }
function switchToLogin() { const m = bootstrap.Modal.getInstance(document.getElementById('signupModal')); if (m) m.hide(); setTimeout(openLoginModal, 250); }

// Password strength
(function () { const el = document.getElementById('signupPassword'); if (!el) return; el.addEventListener('input', function () { const p = this.value || ''; const bar = document.getElementById('passwordStrength'); if (!bar) return; let s = 0; if (p.length >= 8) s += 25; if (/[A-Z]/.test(p)) s += 25; if (/[0-9]/.test(p)) s += 25; if (/[^A-Za-z0-9]/.test(p)) s += 25; bar.style.width = s + '%'; bar.style.backgroundColor = s < 50 ? '#ef4444' : (s < 75 ? '#f59e0b' : '#10b981'); }); })();

// Validation et inscription avec auth.php
(function () {
  const form = document.getElementById('signupForm');
  if (!form) return;
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const prenom = document.getElementById('firstName').value.trim();
    const nom = document.getElementById('lastName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const pass = document.getElementById('signupPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    const terms = document.getElementById('acceptTerms').checked;

    // Validation
    if (!prenom) { alert('Prénom requis'); return; }
    if (!nom) { alert('Nom requis'); return; }
    if (!email.match(/^\S+@\S+\.\S+$/)) { alert('Email invalide'); return; }
    if (pass.length < 8) { alert('Mot de passe au moins 8 caractères'); return; }
    if (pass !== confirm) { alert('Les mots de passe ne correspondent pas'); return; }
    if (!terms) { alert('Vous devez accepter les conditions'); return; }

    // Envoi à auth.php
    try {
      const formData = new FormData();
      formData.append('action', 'signup');
      formData.append('prenom', prenom);
      formData.append('nom', nom);
      formData.append('email', email);
      formData.append('password', pass);

      const response = await fetch(getAuthPath(), {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        // Stocker dans localStorage
        localStorage.setItem('user', JSON.stringify(data.user));

        alert('Inscription réussie ! Bienvenue ' + (data.user.name || data.user.nom));

        // Recharger la page pour mettre à jour l'UI
        window.location.reload();
      } else {
        alert('Erreur: ' + data.message);
      }
    } catch (error) {
      console.error('Erreur d\'inscription:', error);
      alert('Erreur de connexion au serveur');
    }
  });
})();

// Validation et connexion avec auth.php
(function () {
  const form = document.getElementById('loginForm');
  if (!form) return;
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const pass = document.getElementById('loginPassword').value;

    // Validation
    if (!email.match(/^\S+@\S+\.\S+$/)) { alert('Email invalide'); return; }
    if (!pass) { alert('Mot de passe requis'); return; }

    // Envoi à auth.php
    try {
      const formData = new FormData();
      formData.append('action', 'login');
      formData.append('email', email);
      formData.append('password', pass);

      const response = await fetch(getAuthPath(), {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        // Stocker dans localStorage
        localStorage.setItem('user', JSON.stringify(data.user));

        // Recharger la page pour mettre à jour l'UI
        window.location.reload();
      } else {
        alert('Erreur: ' + data.message);
      }
    } catch (error) {
      console.error('Erreur de connexion:', error);
      alert('Erreur de connexion au serveur');
    }
  });
})();

// Update UI after login
function updateUIAfterLogin(user) {
  // Masquer les liens de connexion/inscription
  const loginLink = document.getElementById('loginLink');
  const signupLink = document.getElementById('signupLink');
  if (loginLink) loginLink.style.display = 'none';
  if (signupLink) signupLink.style.display = 'none';

  // Afficher le lien Admin si nécessaire
  const adminLink = document.getElementById('adminLink');
  if (adminLink && user && user.role === 'admin') {
    adminLink.style.display = 'block';
  }

  // Afficher les infos utilisateur
  const userInfo = document.getElementById('userInfo');
  if (userInfo) {
    userInfo.style.display = 'block';
    userInfo.innerHTML = `
            <span class="user-greeting me-2" style="color: white; font-weight: 500;">Bonjour, ${user.name || user.nom}</span>
            <a href="#" onclick="logout(); return false;" class="btn btn-sm btn-outline-light">Déconnexion</a>
        `;
  }

  // Support pour l'ancienne structure (au cas où)
  const userActions = document.querySelector('.user-actions');
  if (userActions) {
    let adminButton = '';
    if (user && user.role === 'admin') adminButton = '<a href="admin.html" class="btn btn-warning btn-sm me-2"><i class="fas fa-cog"></i> Admin</a>';
    const photoSrc = (user && user.photo_profil) ? ('uploads/profile_pictures/' + user.photo_profil) : 'https://via.placeholder.com/36?text=U';
    const photoHtml = `<a href="accounts/View/Backoffice/changeProfilePhoto.php" class="me-2" title="Changer ma photo"><img src="${photoSrc}" alt="Photo" style="width:36px;height:36px;object-fit:cover;border-radius:50%;border:2px solid rgba(0,0,0,0.1);"></a>`;
    userActions.innerHTML = `${photoHtml}<span class="badge bg-primary me-2">${user?.name || user?.nom || 'Utilisateur'}</span>${adminButton}<button class="btn btn-outline-secondary" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Déconnexion</button>`;
  }
}

async function logout() {
  try {
    const formData = new FormData();
    formData.append('action', 'logout');
    await fetch(getAuthPath(), { method: 'POST', body: formData });
  } catch (e) {
    console.error('Erreur lors de la déconnexion:', e);
  }
  localStorage.removeItem('user');
  window.location.reload();
}

// expose helpers
window.openLoginModal = openLoginModal; window.openSignupModal = openSignupModal; window.switchToSignup = switchToSignup; window.switchToLogin = switchToLogin; window.logout = logout;
