<?php
require 'config.php';

requireLogin();

$user = currentUser();

if (isAdmin()) {
    redirect('bookings.php');
}

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
| Handle User Cancellation
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($bookingId > 0) {

        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'Cancelled'
            WHERE id = ?
            AND requester_email = ?
            AND status IN ('Pending', 'Approved')
        ");

        $stmt->execute([
            $bookingId,
            $user['email']
        ]);

        if ($stmt->rowCount() > 0) {
            flash('Booking cancelled successfully.');
        } else {
            flash(
                'Unable to cancel this booking.',
                'error'
            );
        }
    }

    redirect('my_bookings.php');
}


/*
|--------------------------------------------------------------------------
| Load Current User's Bookings
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        b.*,
        e.name AS equipment_name,
        e.category AS equipment_category,
        e.location AS equipment_location
    FROM bookings b
    JOIN equipment e
        ON e.id = b.equipment_id
    WHERE b.requester_email = ?
    ORDER BY b.created_at DESC
");

$stmt->execute([
    $user['email']
]);

$bookings = $stmt->fetchAll();

$f = flash();


/*
|--------------------------------------------------------------------------
| Status Badge Helper
|--------------------------------------------------------------------------
*/

function myBookingBadgeClass(string $status): string
{
    switch (strtolower($status)) {

        case 'approved':
            return 'badge-approved';

        case 'pending':
            return 'badge-pending';

        case 'rejected':
            return 'badge-rejected';

        case 'cancelled':
            return 'badge-unavailable';

        default:
            return 'badge-unavailable';
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
        My Bookings | LabBooker
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

            <a href="my_bookings.php">
                My Bookings
            </a>

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
                My Bookings
            </h2>

            <p>
                View and manage your equipment reservation requests.
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


        <section class="panel">


            <div class="panel-header">

                <div>

                    <h3>
                        Reservation History
                    </h3>

                    <p>
                        <?= count($bookings) ?>
                        booking<?= count($bookings) === 1 ? '' : 's' ?>
                        associated with your account.
                    </p>

                </div>


                <a
                    href="booking_form.php"
                    class="btn btn-primary"
                >
                    + New Booking
                </a>

            </div>


            <?php if (!$bookings): ?>


                <div class="empty-state">

                    <h4>
                        No bookings yet
                    </h4>

                    <p>
                        Your reservation requests will appear here.
                    </p>

                </div>


            <?php else: ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Equipment
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Schedule
                                </th>

                                <th>
                                    Purpose
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($bookings as $booking): ?>

                                <tr>


                                    <td>

                                        <strong>
                                            <?= e($booking['equipment_name']) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e($booking['equipment_category']) ?>
                                        </small>

                                    </td>


                                    <td>
                                        <?= e($booking['equipment_location']) ?>
                                    </td>


                                    <td>

                                        <strong>
                                            <?= e($booking['start_at']) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            to <?= e($booking['end_at']) ?>
                                        </small>

                                    </td>


                                    <td>
                                        <?= e($booking['purpose']) ?>
                                    </td>


                                    <td>

                                        <span
                                            class="badge <?= myBookingBadgeClass($booking['status']) ?>"
                                        >
                                            <?= e($booking['status']) ?>
                                        </span>

                                    </td>


                                    <td>


                                        <?php if (
                                            $booking['status'] === 'Pending' ||
                                            $booking['status'] === 'Approved'
                                        ): ?>


                                            <form
                                                method="POST"
                                                class="inline-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($csrfToken) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?= (int) $booking['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-small"
                                                    onclick="return confirm('Cancel this booking?');"
                                                >
                                                    Cancel
                                                </button>

                                            </form>


                                        <?php else: ?>


                                            <span class="muted-action">
                                                No action
                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </section>


    </div>

</main>



<footer class="site-footer">

    <div class="container">

        LabBooker · Research Equipment Reservation System

    </div>

</footer>


</body>

</html>