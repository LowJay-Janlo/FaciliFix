<?php include 'partials.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaciliFix</title>
    <link href="base.css" rel="stylesheet">
    <link href="landingpage.css" rel="stylesheet">
</head>
<body>

    <?php render_topbar(null, false); ?>

    <main>
        <section class="landing-hero">
            <h1><span>FACILIFIX</span>: Smart School Maintenance, Streamlined.</h1>
            <hr class="page-divider">
        </section>

        <div class="landing-layout">
            <div class="main-column">
                <div class="card">
                    <div class="container">
                        <h2>What is Facilifix?</h2>
                        <p>FaciliFix is a web-based reporting system designed for Araullo University to replace manual, paper-based tracking with a unified digital dashboard. It allows teachers,
                           staff, and administrators to effortlessly report, monitor, and manage the condition of campus equipment (Working, Broken, or Missing) organized by room.</p>
                    </div>
                </div>
                <img class="slogan-image" src="Assets/Images/Slogan.png" alt="FaciliFix: Know every item. Track every status." draggable="false">
            </div>

            <div class="card-portrait">
                <div class="container">
                    <h2>Let's Get Started</h2>
                    <p><b>Need an account?</b> Contact the system <a href="mailto:Guangcojaylo090@gmail.com">Administrator</a> for access.</p>
                    <p><b>Already have an account?</b> Click Log In below and enter your valid credentials to access your dashboard.</p>
                    <a class="login-button" href="login.php">Log In</a>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
