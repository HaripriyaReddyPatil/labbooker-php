<?php
require 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        $name === '' ||
        $email === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {

        $error = 'Password must be at least 8 characters.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $error = 'An account with this email already exists.';

        } else {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    name,
                    email,
                    password_hash,
                    role
                )
                VALUES (?, ?, ?, 'user')
            ");

            $stmt->execute([
                $name,
                $email,
                $passwordHash
            ]);

            flash(
                'Account created successfully. Please sign in.'
            );

            redirect('login.php');
        }
    }
}
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
        Create Account | LabBooker
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
                    Create Account
                </h2>

                <p>
                    Register to reserve research equipment.
                </p>

            </div>


            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST">


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        autocomplete="name"
                        value="<?= e($_POST['name'] ?? '') ?>"
                    >

                </div>


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
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Account
                </button>


            </form>


            <p style="margin-top: 20px; color: #6b7280;">

                Already have an account?

                <a
                    href="login.php"
                    style="color: #1e40af; font-weight: 600;"
                >
                    Sign in
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