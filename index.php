<?php
require 'config.php';

/*
|--------------------------------------------------------------------------
| Dashboard Data
|--------------------------------------------------------------------------
*/

$equipment = $pdo
    ->query("
        SELECT *
        FROM equipment
        ORDER BY id DESC
    ")
    ->fetchAll();


$upcoming = $pdo
    ->query("
        SELECT
            b.*,
            e.name AS equipment_name,
            e.location AS equipment_location
        FROM bookings b
        JOIN equipment e
            ON e.id = b.equipment_id
        ORDER BY b.start_at ASC
        LIMIT 8
    ")
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| Recent Activity
|--------------------------------------------------------------------------
*/

$recentActivity = $pdo
    ->query("
        SELECT *
        FROM activity_log
        ORDER BY id DESC
        LIMIT 8
    ")
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| Dashboard Metrics
|--------------------------------------------------------------------------
*/

$totalEquipment = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM equipment
    ")
    ->fetchColumn();


$availableEquipment = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM equipment
        WHERE status = 'Available'
    ")
    ->fetchColumn();


$pendingBookings = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE status = 'Pending'
    ")
    ->fetchColumn();


$approvedBookings = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE status = 'Approved'
    ")
    ->fetchColumn();


$f = flash();


/*
|--------------------------------------------------------------------------
| Badge Helper
|--------------------------------------------------------------------------
*/

function statusBadgeClass(string $status): string
{
    switch (strtolower($status)) {

        case 'approved':
            return 'badge-approved';

        case 'pending':
            return 'badge-pending';

        case 'rejected':
            return 'badge-rejected';

        case 'available':
            return 'badge-available';

        case 'maintenance':
            return 'badge-pending';

        case 'cancelled':
        case 'unavailable':
            return 'badge-unavailable';

        default:
            return 'badge-unavailable';
    }
}


/*
|--------------------------------------------------------------------------
| Activity Badge Helper
|--------------------------------------------------------------------------
*/

function activityBadgeClass(string $action): string
{
    $action = strtolower($action);

    if (str_contains($action, 'approved')) {
        return 'activity-success';
    }

    if (
        str_contains($action, 'rejected') ||
        str_contains($action, 'cancelled')
    ) {
        return 'activity-danger';
    }

    if (str_contains($action, 'booking')) {
        return 'activity-primary';
    }

    return 'activity-neutral';
}


/*
|--------------------------------------------------------------------------
| Format Activity Time
|--------------------------------------------------------------------------
*/

function activityTime(string $date): string
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    return date(
        'M j, Y g:i A',
        $timestamp
    );
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
        LabBooker | Research Equipment Management
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


<!-- HEADER -->

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

            <?php if (isLoggedIn()): ?>

                <a href="booking_form.php">
                    Book Equipment
                </a>

                <?php if (isAdmin()): ?>

                    <a href="bookings.php">
                        Manage Bookings
                    </a>

                    <a href="equipment.php">
                        Manage Equipment
                    </a>

                <?php else: ?>

                    <a href="my_bookings.php">
                        My Bookings
                    </a>

                <?php endif; ?>


                <span class="nav-user">
                    <?= e(
                        currentUser()['name']
                        ?? 'User'
                    ) ?>
                </span>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Sign In
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>


<!-- MAIN -->

<main>

    <div class="container">


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <h2>
                Research Equipment Dashboard
            </h2>

            <p>
                View equipment availability, monitor reservation requests,
                and manage laboratory resources.
            </p>

        </div>


        <!-- FLASH -->

        <?php if ($f): ?>

            <div
                class="alert <?= $f[1] === 'error'
                    ? 'alert-error'
                    : 'alert-success'
                ?>"
            >

                <?= e($f[0]) ?>

            </div>

        <?php endif; ?>


        <!-- DASHBOARD STATS -->

        <div class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    Total Equipment
                </div>

                <div class="stat-value">
                    <?= $totalEquipment ?>
                </div>

                <div class="stat-detail">
                    Registered research assets
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Available Equipment
                </div>

                <div class="stat-value">
                    <?= $availableEquipment ?>
                </div>

                <div class="stat-detail">
                    Currently available for booking
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Pending Requests
                </div>

                <div class="stat-value">
                    <?= $pendingBookings ?>
                </div>

                <div class="stat-detail">
                    Waiting for review
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Approved Bookings
                </div>

                <div class="stat-value">
                    <?= $approvedBookings ?>
                </div>

                <div class="stat-detail">
                    Confirmed reservations
                </div>

            </div>


        </div>


        <!-- EQUIPMENT INVENTORY -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h3>
                        Equipment Inventory
                    </h3>

                    <p>
                        Current laboratory resources and availability.
                    </p>

                </div>


                <?php if (isAdmin()): ?>

                    <a
                        href="equipment.php"
                        class="btn btn-secondary"
                    >
                        Manage Equipment
                    </a>

                <?php elseif (isLoggedIn()): ?>

                    <a
                        href="booking_form.php"
                        class="btn btn-primary"
                    >
                        Book Equipment
                    </a>

                <?php endif; ?>

            </div>


            <?php if (!$equipment): ?>

                <div class="empty-state">

                    <h4>
                        No equipment registered
                    </h4>

                    <p>
                        Equipment will appear here once added.
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
                                    Category
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($equipment as $item): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= e($item['name']) ?>
                                        </strong>

                                    </td>

                                    <td>
                                        <?= e($item['category']) ?>
                                    </td>

                                    <td>
                                        <?= e($item['location']) ?>
                                    </td>

                                    <td>

                                        <span
                                            class="badge <?= statusBadgeClass(
                                                $item['status']
                                            ) ?>"
                                        >

                                            <?= e($item['status']) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <!-- BOOKINGS -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h3>
                        Reservation Activity
                    </h3>

                    <p>
                        Recent equipment reservation requests.
                    </p>

                </div>


                <?php if (isAdmin()): ?>

                    <a
                        href="bookings.php"
                        class="btn btn-secondary"
                    >
                        Manage Bookings
                    </a>

                <?php elseif (isLoggedIn()): ?>

                    <a
                        href="my_bookings.php"
                        class="btn btn-secondary"
                    >
                        My Bookings
                    </a>

                <?php endif; ?>

            </div>


            <?php if (!$upcoming): ?>

                <div class="empty-state">

                    <h4>
                        No reservations yet
                    </h4>

                    <p>
                        Reservation requests will appear here.
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
                                    Requester
                                </th>

                                <th>
                                    Schedule
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($upcoming as $booking): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= e(
                                                $booking['equipment_name']
                                            ) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e(
                                                $booking['equipment_location']
                                            ) ?>
                                        </small>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= e(
                                                $booking['requester']
                                            ) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e(
                                                $booking['requester_email']
                                            ) ?>
                                        </small>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= e(
                                                date(
                                                    'M j, Y g:i A',
                                                    strtotime(
                                                        $booking['start_at']
                                                    )
                                                )
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small>

                                            to

                                            <?= e(
                                                date(
                                                    'M j, Y g:i A',
                                                    strtotime(
                                                        $booking['end_at']
                                                    )
                                                )
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <span
                                            class="badge <?= statusBadgeClass(
                                                $booking['status']
                                            ) ?>"
                                        >

                                            <?= e(
                                                $booking['status']
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <!-- RECENT ACTIVITY -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h3>
                        Recent Activity
                    </h3>

                    <p>
                        Latest booking and administrative actions in LabBooker.
                    </p>

                </div>

            </div>


            <?php if (!$recentActivity): ?>

                <div class="empty-state">

                    <h4>
                        No activity recorded yet
                    </h4>

                    <p>
                        Booking and administrative activity will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="activity-list">

                    <?php foreach ($recentActivity as $activity): ?>

                        <div class="activity-item">

                            <div
                                class="activity-icon <?= activityBadgeClass(
                                    $activity['action']
                                ) ?>"
                            >

                                <?= strtoupper(
                                    substr(
                                        $activity['action'],
                                        0,
                                        1
                                    )
                                ) ?>

                            </div>


                            <div class="activity-content">

                                <div class="activity-heading">

                                    <strong>
                                        <?= e(
                                            $activity['action']
                                        ) ?>
                                    </strong>

                                    <span class="activity-time">

                                        <?= e(
                                            activityTime(
                                                $activity['created_at']
                                            )
                                        ) ?>

                                    </span>

                                </div>


                                <p>
                                    <?= e(
                                        $activity['description']
                                    ) ?>
                                </p>


                                <small>

                                    By
                                    <?= e(
                                        $activity['actor_name']
                                    ) ?>

                                </small>

                            </div>

                        </div>

                    <?php endforeach; ?>

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