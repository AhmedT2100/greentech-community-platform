<?php
if (!isset($basePath)) {
    $basePath = '.';
}
if (!isset($pageTitle)) {
    $pageTitle = 'GreenTechHub | Plateforme des Innovations Durables';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="innovations durables, écologie, technologie verte, énergie solaire, recyclage, mobilité verte"
        name="keywords">
    <meta
        content="GreenTechHub - La plateforme de référence pour découvrir, proposer et voter pour les meilleures innovations écologiques et technologiques."
        name="description">

    <!-- Favicons -->
    <link href="<?php echo $basePath; ?>/img/favicon.ico" rel="icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300|Nunito:400,700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Bootstrap CSS File -->
    <link href="<?php echo $basePath; ?>/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="<?php echo $basePath; ?>/vendor/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/vendor/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/vendor/lightbox/css/lightbox.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link href="<?php echo $basePath; ?>/css/style.css" rel="stylesheet">

    <!-- Dark Mode Stylesheet -->
    <link href="<?php echo $basePath; ?>/css/dark-mode.css" rel="stylesheet">

    <?php if (isset($extraCss))
        echo $extraCss; ?>
</head>

<body>
    <!-- Header Section Start -->
    <header class="header">
        <div class="container-fluid">
            <div class="logo pull-left">
                <h1><a href="<?php echo $basePath; ?>/index.html">GreenTechHub</a></h1>
                <!-- <a href="#"><img src="img/logo.png" alt="" title="" /></a>-->
            </div>

            <nav id="nav-menu-container">
                <ul class="nav-menu">
                    <li class="<?php echo ($activePage ?? '') == 'home' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/index.html">Home</a></li>
                    <li class="<?php echo ($activePage ?? '') == 'about' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/about.html">About</a></li>
                    <li class="<?php echo ($activePage ?? '') == 'services' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/service.html">Services</a></li>


                    <!-- Events Module Link -->
                    <li class="<?php echo ($activePage ?? '') == 'events' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/events.html">Events</a></li>

                    <!-- Articles Module Link -->
                    <li class="<?php echo ($activePage ?? '') == 'articles' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/articles/index.php">Articles</a></li>

                    <!-- Innovations Module Link -->
                    <li class="<?php echo ($activePage ?? '') == 'innovations' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/innovation/index.php">Innovations</a></li>

                    <!-- Reclamations Module Link -->
                    <li class="<?php echo ($activePage ?? '') == 'reclamations' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/reclamations.html">Réclamations</a></li>

                    <li class="<?php echo ($activePage ?? '') == 'contact' ? 'menu-active' : ''; ?>"><a
                            href="<?php echo $basePath; ?>/contact.html">Contact</a></li>

                    <!-- Authentication Links -->
                    <li id="loginLink"><a href="#" onclick="openLoginModal(); return false;">Connexion</a></li>
                    <li id="signupLink"><a href="#" onclick="openSignupModal(); return false;">Inscription</a></li>

                    <!-- Admin Link (Hidden by default, shown via JS if admin) -->
                    <li id="adminLink" style="display:none;"><a href="<?php echo $basePath; ?>/admin.html">Admin</a>
                    </li>

                    <!-- User Info Display -->
                    <li id="userInfo" style="display:none;"></li>
                </ul>
            </nav>
        </div>
    </header>
    <!-- Header Section End -->