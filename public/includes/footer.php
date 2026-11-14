<!-- Footer Start -->
<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row">

                <div class="col-lg-3 col-md-6 footer-info">
                    <h3>GreenTechHub</h3>
                    <p>
                        GreenTechHub est la plateforme de référence pour centraliser toutes les innovations écologiques
                        et
                        technologiques visant à protéger notre environnement et construire un avenir durable.
                    </p>
                </div>

                <div class="col-lg-3 col-md-6 footer-links">
                    <h4>Useful Links</h4>
                    <ul>
                        <li><i class="ion-md-arrow-dropright"></i> <a href="<?php echo $basePath; ?>/index.html">Home</a>
                        </li>
                        <li><i class="ion-md-arrow-dropright"></i> <a href="<?php echo $basePath; ?>/about.html">About
                                us</a></li>
                        <li><i class="ion-md-arrow-dropright"></i> <a
                                href="<?php echo $basePath; ?>/service.html">Services</a></li>
                        <li><i class="ion-md-arrow-dropright"></i> <a href="<?php echo $basePath; ?>/terms.html">Terms
                                of service</a></li>
                        <li><i class="ion-md-arrow-dropright"></i> <a
                                href="<?php echo $basePath; ?>/privacy.html">Privacy policy</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 footer-contact">
                    <h4>Contact Us</h4>
                    <p>
                        A108 Adam Street <br>
                        New York, NY 535022<br>
                        United States <br>
                        <strong>Phone:</strong> +1 5589 55488 55<br>
                        <strong>Email:</strong> info@example.com<br>
                    </p>

                    <div class="social-links">
                        <a href="#" class="twitter"><i class="ion-logo-twitter"></i></a>
                        <a href="#" class="facebook"><i class="ion-logo-facebook"></i></a>
                        <a href="#" class="instagram"><i class="ion-logo-instagram"></i></a>
                        <a href="#" class="google-plus"><i class="ion-logo-googleplus"></i></a>
                        <a href="#" class="linkedin"><i class="ion-logo-linkedin"></i></a>
                    </div>

                </div>

                <div class="col-lg-3 col-md-6 footer-newsletter">
                    <h4>Our Newsletter</h4>
                    <p>Rejoignez notre newsletter pour recevoir les dernières nouvelles sur les innovations et les
                        projets durables de GreenTechHub.</p>
                    <form action="" method="post">
                        <input type="email" name="email"><input type="submit" value="Subscribe">
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="container">
        <div class="copyright">
            &copy; Copyright <a href="https://htmlcodex.com">HTML Codex</a>. All Rights Reserved<br>
            Template By <a href="https://htmlcodex.com">HTML Codex</a>
        </div>
    </div>
</footer>
<!-- Footer End -->

<a href="#" class="back-to-top"><i class="ion-md-arrow-dropup"></i></a>

<!-- Vendor JS Files -->
<script src="<?php echo $basePath; ?>/vendor/jquery/jquery.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/jquery/jquery-migrate.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/easing/easing.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/superfish/hoverIntent.js"></script>
<script src="<?php echo $basePath; ?>/vendor/superfish/superfish.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/waypoints/waypoints.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/touchSwipe/jquery.touchSwipe.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/counterup/counterup.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/owlcarousel/owl.carousel.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/isotope/isotope.pkgd.min.js"></script>
<script src="<?php echo $basePath; ?>/vendor/lightbox/js/lightbox.min.js"></script>

<!-- Main Javascript File -->
<script src="<?php echo $basePath; ?>/js/main.js"></script>

<!-- Modals d'authentification -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ion-md-leaf"></i> Connexion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">Accédez à votre tableau de bord GreenTechHub</p>
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-mail"></i></span>
                            <input type="email" class="form-control" id="loginEmail" placeholder="votre@email.com"
                                required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="loginPassword"
                                placeholder="Votre mot de passe" required>
                        </div>
                    </div>
                    <div class="mb-3" id="unlockCodeContainer" style="display: none;">
                        <label for="unlockCode" class="form-label text-danger">Code de sécurité requis</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-shield text-danger"></i></span>
                            <input type="text" class="form-control border-danger" id="unlockCode"
                                placeholder="Entrez le code reçu par email">
                        </div>
                        <div class="form-text text-danger">Veuillez consulter votre boîte Gmail</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="ion-md-log-in"></i> Se connecter
                    </button>
                </form>
                <p class="text-center mb-0">Pas encore de compte ? <a href="#" onclick="switchToSignup()">Créer
                        un
                        compte</a></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="signupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ion-md-leaf"></i> Créer un compte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">Rejoignez la communauté GreenTechHub</p>
                <form id="signupForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ion-md-person"></i></span>
                                <input type="text" class="form-control" id="firstName" placeholder="Votre prénom"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ion-md-person"></i></span>
                                <input type="text" class="form-control" id="lastName" placeholder="Votre nom" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-mail"></i></span>
                            <input type="email" class="form-control" id="signupEmail" placeholder="votre@email.com"
                                required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="signupPassword"
                                placeholder="Créez un mot de passe" required>
                        </div>
                        <small class="form-text text-muted">Le mot de passe doit contenir au moins 8
                            caractères</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="confirmPassword"
                                placeholder="Confirmez votre mot de passe" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="acceptTerms" required>
                        <label class="form-check-label" for="acceptTerms">
                            J'accepte les conditions d'utilisation
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="ion-md-person-add"></i> Créer mon compte
                    </button>
                </form>
                <p class="text-center mb-0">Déjà un compte ? <a href="#" onclick="switchToLogin()">Se
                        connecter</a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Script d'authentification -->
<script src="<?php echo $basePath; ?>/auth-ui.js?v=2"></script>

<?php if (isset($extraScript))
    echo $extraScript; ?>
</body>

</html>