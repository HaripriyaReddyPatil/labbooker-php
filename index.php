<?php
require 'config.php';

/*
|--------------------------------------------------------------------------
| Dashboard Data
|--------------------------------------------------------------------------
*/

$equipment = $pdo
    ->query("SELECT * FROM equipment ORDER BY id DESC")
    ->fetchAll();

$upcoming = $pdo
    ->query("
        SELECT 
            b.*, 
            e.name AS equipment_name
        FROM bookings b
        JOIN equipment e ON e.id = b.equipment_id
        ORDER BY b.start_at ASC
        LIMIT 8
    ")
    ->fetchAll();

/*
|--------------------------------------------------------------------------
| Dashboard Metrics
|--------------------------------------------------------------------------
*/

$totalEquipment = (int) $pdo
    ->query("SELECT COUNT(*) FROM equipment")
    ->fetchColumn();

$availableEquipment = (int) $pdo
    ->query("SELECT COUNT(*) FROM equipment WHERE status = 'Available'")
    ->fetchColumn();

$pendingBookings = (int) $pdo
    ->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")
    ->fetchColumn();

$approvedBookings = (int) $pdo
    ->query("SELECT COUNT(*) FROM bookings WHERE status = 'Approved'")
    ->fetchColumn();

$f = flash();

/*
|--------------------------------------------------------------------------
| Helper for status badge classes
|--------------------------------------------------------------------------
*/

function statusBadgeClass($status)
{
    $status = strtolower($status);

    switch ($status) {
        case 'approved':
            return 'badge-approved';

        case 'pending':
            return 'badge-pending';

        case 'rejected':
            return 'badge-rejected';

        case 'available':
            return 'badge-available';

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

    <title>LabBooker | Research Equipment Management</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >
</head>

<body>

<!-- ================================================================
     HEADER
================================================================ -->

<header class="site-header">

    <div class="container navbar">

        <a href="index.php" class="brand">

            <div class="brand-logo">
                LB
            </div>

            <div class="brand-text">
                <h1>LabBooker</h1>
                <p>Research Equipment Management</p>
            </div>

        </a>


        <nav class="nav-links">

            <a href="index.php">
                Dashboard
            </a>

            <a href="booking_form.php">
                Book Equipment
            </a>

            <a href="bookings.php">
                Manage Bookings
            </a>

            <a href="equipment_form.php">
                Add Equipment
            </a>

        </nav>

    </div>

</header>


<!-- ================================================================
     MAIN CONTENT
================================================================ -->

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


        <!-- FLASH MESSAGE -->

        <?php if ($f): ?>

            <div class="alert <?= $f[1] === 'error'
                ? 'alert-error'
                : 'alert-success'
            ?>">

                <?= e($f[0]) ?>

            </div>

        <?php endif; ?>


        <!-- ========================================================
             STATISTICS
        ========================================================= -->

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


        <!-- ========================================================
             EQUIPMENT INVENTORY
        ========================================================= -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <h3>
                        Equipment Inventory
                    </h3>

                    <p>
                        Current laboratory equipment and availability.
                    </p>

                </div>


                <a
                    href="equipment_form.php"
                    class="btn btn-primary"
                >
                    + Add Equipment
                </a>

            </div>


            <?php if (!$equipment): ?>


                <div class="empty-state">

                    <h4>
                        No equipment added yet
                    </h4>

                    <p>
                        Add your first laboratory resource to begin accepting
                        reservations.
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
                                    Availability
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

                                        <span class="badge <?= statusBadgeClass($item['status']) ?>">

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


        <!-- ========================================================
             BOOKING REQUESTS
        ========================================================= -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <h3>
                        Recent Booking Requests
                    </h3>

                    <p>
                        Latest equipment reservation activity.
                    </p>

                </div>


                <div>

                    <a
                        href="booking_form.php"
                        class="btn btn-primary"
                    >
                        + New Booking
                    </a>


                    <a
                        href="bookings.php"
                        class="btn btn-secondary"
                    >
                        Manage All
                    </a>

                </div>

            </div>


            <?php if (!$upcoming): ?>


                <div class="empty-state">

                    <h4>
                        No bookings yet
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
                                    Start
                                </th>

                                <th>
                                    End
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
                                            <?= e($booking['equipment_name']) ?>
                                        </strong>

                                    </td>


                                    <td>
                                        <?= e($booking['requester']) ?>
                                    </td>


                                    <td>
                                        <?= e($booking['start_at']) ?>
                                    </td>


                                    <td>
                                        <?= e($booking['end_at']) ?>
                                    </td>


                                    <td>

                                        <span class="badge <?= statusBadgeClass($booking['status']) ?>">

                                            <?= e($booking['status']) ?>

                                        </span>

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


<!-- ================================================================
     FOOTER
================================================================ -->

<footer class="site-footer">

    <div class="container">

        LabBooker · Research Equipment Reservation System

    </div>

</footer>


</body>

</html>