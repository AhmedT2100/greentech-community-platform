<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Inscription - GreenTechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        .form-control {
            padding: 12px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-signup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            width: 100%;
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <div class="text-center mb-4">
            <i class="fas fa-user-plus" style="font-size: 60px; color: #667eea;"></i>
            <h1 class="mt-3">Test d'inscription</h1>
            <p class="text-muted">Créez un compte pour tester l'email de bienvenue</p>
        </div>

        <div id="alertContainer"></div>

        <form id="signupForm">
            <div class="mb-3">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Jean" required>
            </div>

            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" class="form-control" id="nom" name="nom" placeholder="Dupont" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="jean.dupont@example.com"
                    required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="********"
                    required>
            </div>

            <button type="submit" class="btn btn-signup" id="submitBtn">
                <i class="fas fa-user-plus"></i> S'inscrire et recevoir l'email
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> Un email de bienvenue sera envoyé automatiquement
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription en cours...';

            const formData = new FormData();
            formData.append('action', 'signup');
            formData.append('prenom', document.getElementById('prenom').value);
            formData.append('nom', document.getElementById('nom').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('password', document.getElementById('password').value);

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', `
                        <i class="fas fa-check-circle"></i> 
                        <strong>Inscription réussie !</strong><br>
                        Un email de bienvenue a été envoyé à <strong>${document.getElementById('email').value}</strong><br>
                        <small>Vérifiez votre boîte de réception (et les spams)</small>
                    `);
                    document.getElementById('signupForm').reset();
                } else {
                    showAlert('danger', `<i class="fas fa-times-circle"></i> ${result.message}`);
                }
            } catch (error) {
                showAlert('danger', `<i class="fas fa-exclamation-triangle"></i> Erreur : ${error.message}`);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> S\'inscrire et recevoir l\'email';
            }
        });

        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    </script>
</body>

</html>