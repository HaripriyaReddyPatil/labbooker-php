<?php
require 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter both email and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (
            $user &&
            password_verify(
                $password,
                $user['password_hash']
            )
        ) {

            loginUser($user);

            flash(
                'Welcome back, ' . $user['name'] . '.'
            );

            redirect('index.php');

        } else {

            $error = 'Invalid email or password.';
        }
    }
}

$f = flash();
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Sign In | LabBooker
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>


<body>


<header class="site-header">

    <div class="container navbar">

        <a
            href="index.php"
            class="brand"
        >

            <div class="brand-logo">
                LB
            </div>


            <div class="brand-text">

                <h1>
                    LabBooker
                </h1>

                <p>
                    Research Equipment Management
                </p>

            </div>

        </a>

    </div>

</header>



<main>

    <div class="container">

        <div class="form-card panel">


            <div class="page-heading">

                <h2>
                    Sign In
                </h2>

                <p>
                    Access your LabBooker account.
                </p>

            </div>



            <?php if ($f): ?>

                <div class="alert <?= $f[1] === 'error'
                    ? 'alert-error'
                    : 'alert-success'
                ?>">

                    <?= e($f[0]) ?>

                </div>

            <?php endif; ?>



            <?php if ($error): ?>

                <div class="alert alert-error">

                    <?= e($error) ?>

                </div>

            <?php endif; ?>



            <form method="POST">


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                    >

                </div>



                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >

                </div>



                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Sign In
                </button>


            </form>



            <p
                style="
                    margin-top: 20px;
                    color: #6b7280;
                "
            >

                Don't have an account?

                <a
                    href="register.php"
                    style="
                        color: #1e40af;
                        font-weight: 600;
                    "
                >
                    Create one
                </a>

            </p>


        </div>

    </div>

</main>



<footer class="site-footer">

    <div class="container">

        LabBooker · Research Equipment Reservation System

    </div>

</footer>


</body>

</html>