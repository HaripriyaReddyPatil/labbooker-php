<?php
require 'config.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| Load Available Equipment
|--------------------------------------------------------------------------
*/

$equipment = $pdo
    ->query("
        SELECT *
        FROM equipment
        WHERE status = 'Available'
        ORDER BY name
    ")
    ->fetchAll();

$errors = [];

$user = currentUser();

$requester = $user['name'] ?? '';
$email = $user['email'] ?? '';


/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| Handle Booking Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Verify CSRF Token
    |--------------------------------------------------------------------------
    */

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }


    /*
    |--------------------------------------------------------------------------
    | Read Form Values
    |--------------------------------------------------------------------------
    */

    $equipmentId = (int) ($_POST['equipment_id'] ?? 0);

    $start = trim($_POST['start_at'] ?? '');

    $end = trim($_POST['end_at'] ?? '');

    $purpose = trim($_POST['purpose'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if (
        !$equipmentId ||
        $requester === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $start === '' ||
        $end === '' ||
        $purpose === ''
    ) {
        $errors[] = 'Please complete all required fields.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Equipment Exists And Is Available
    |--------------------------------------------------------------------------
    */

    $selectedEquipment = null;

    if ($equipmentId) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM equipment
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $equipmentId
        ]);

        $selectedEquipment = $stmt->fetch();

        if (!$selectedEquipment) {

            $errors[] =
                'The selected equipment could not be found.';

        } elseif (
            $selectedEquipment['status'] !== 'Available'
        ) {

            $errors[] =
                'The selected equipment is currently unavailable.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Times
    |--------------------------------------------------------------------------
    */

    if (
        $start !== '' &&
        $end !== '' &&
        strtotime($end) <= strtotime($start)
    ) {
        $errors[] =
            'End time must be after start time.';
    }


    if (
        $start !== '' &&
        strtotime($start) < time()
    ) {
        $errors[] =
            'Start time cannot be in the past.';
    }


    /*
    |--------------------------------------------------------------------------
    | Check Booking Conflict
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE equipment_id = ?
            AND status IN ('Pending', 'Approved')
            AND NOT (
                end_at <= ?
                OR start_at >= ?
            )
        ");

        $stmt->execute([
            $equipmentId,
            $start,
            $end
        ]);

        if ((int) $stmt->fetchColumn() > 0) {

            $errors[] =
                'That equipment is already booked during the selected time.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Booking
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Insert Booking
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO bookings (
                    equipment_id,
                    requester,
                    requester_email,
                    start_at,
                    end_at,
                    purpose,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");

            $stmt->execute([
                $equipmentId,
                $requester,
                $email,
                $start,
                $end,
                $purpose
            ]);

            $bookingId = (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Create Activity Log Entry
            |--------------------------------------------------------------------------
            */

            $equipmentName =
                $selectedEquipment['name'] ?? 'equipment';

            $startDisplay = date(
                'M j, Y g:i A',
                strtotime($start)
            );

            $endDisplay = date(
                'M j, Y g:i A',
                strtotime($end)
            );

            logActivity(
                $pdo,
                'Booking Created',
                "Requested {$equipmentName} from {$startDisplay} to {$endDisplay} (Booking #{$bookingId})."
            );


            /*
            |--------------------------------------------------------------------------
            | Finish Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            flash(
                'Booking request submitted successfully.'
            );

            redirect('index.php');

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'The booking could not be submitted. Please try again.';
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
        Book Equipment | LabBooker
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


        <nav class="nav-links">

            <a href="index.php">
                Dashboard
            </a>

            <a href="booking_form.php">
                Book Equipment
            </a>


            <?php if (!isAdmin()): ?>

                <a href="my_bookings.php">
                    My Bookings
                </a>

            <?php endif; ?>


            <?php if (isAdmin()): ?>

                <a href="bookings.php">
                    Manage Bookings
                </a>

                <a href="equipment.php">
                    Manage Equipment
                </a>

            <?php endif; ?>


            <span class="nav-user">

                <?= e($user['name'] ?? 'User') ?>

            </span>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </div>

</header>


<main>

    <div class="container">


        <div class="page-heading">

            <h2>
                Reserve Equipment
            </h2>

            <p>
                Submit a reservation request for available laboratory equipment.
            </p>

        </div>


        <div class="form-card panel">


            <?php if ($errors): ?>

                <div class="alert alert-error">

                    <?= e(implode(' ', $errors)) ?>

                </div>

            <?php endif; ?>


            <?php if (!$equipment): ?>

                <div class="empty-state">

                    <h4>
                        No equipment currently available
                    </h4>

                    <p>
                        Please check again later or contact the lab administrator.
                    </p>

                </div>

            <?php else: ?>


                <form method="POST">


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                    >


                    <!-- EQUIPMENT -->

                    <div class="form-group">

                        <label for="equipment_id">
                            Equipment
                        </label>

                        <select
                            id="equipment_id"
                            name="equipment_id"
                            required
                        >

                            <option value="">
                                Choose equipment...
                            </option>


                            <?php foreach ($equipment as $item): ?>

                                <option
                                    value="<?= (int) $item['id'] ?>"
                                    <?= (
                                        isset($_POST['equipment_id']) &&
                                        (int) $_POST['equipment_id']
                                            ===
                                        (int) $item['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e($item['name']) ?>

                                    —

                                    <?= e($item['location']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- REQUESTER -->

                    <div class="form-group">

                        <label>
                            Requester
                        </label>

                        <input
                            type="text"
                            value="<?= e($requester) ?>"
                            disabled
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            value="<?= e($email) ?>"
                            disabled
                        >

                    </div>


                    <!-- START -->

                    <div class="form-group">

                        <label for="start_at">
                            Start Time
                        </label>

                        <input
                            type="datetime-local"
                            id="start_at"
                            name="start_at"
                            required
                            value="<?= e($_POST['start_at'] ?? '') ?>"
                        >

                    </div>


                    <!-- END -->

                    <div class="form-group">

                        <label for="end_at">
                            End Time
                        </label>

                        <input
                            type="datetime-local"
                            id="end_at"
                            name="end_at"
                            required
                            value="<?= e($_POST['end_at'] ?? '') ?>"
                        >

                    </div>


                    <!-- PURPOSE -->

                    <div class="form-group">

                        <label for="purpose">
                            Purpose
                        </label>

                        <textarea
                            id="purpose"
                            name="purpose"
                            maxlength="1000"
                            required
                            placeholder="Briefly describe how you plan to use this equipment."
                        ><?= e($_POST['purpose'] ?? '') ?></textarea>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Submit Booking Request
                    </button>


                </form>


            <?php endif; ?>


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