<?php
require 'config.php';

requireAdmin();

/*
|--------------------------------------------------------------------------
| Load Equipment
|--------------------------------------------------------------------------
*/

$equipment = $pdo
    ->query("
        SELECT *
        FROM equipment
        ORDER BY id DESC
    ")
    ->fetchAll();

$f = flash();


/*
|--------------------------------------------------------------------------
| Status Badge Helper
|--------------------------------------------------------------------------
*/

function equipmentBadgeClass(string $status): string
{
    switch (strtolower($status)) {

        case 'available':
            return 'badge-available';

        case 'maintenance':
            return 'badge-pending';

        case 'unavailable':
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
        Manage Equipment | LabBooker
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


<header class="site-header">

    <div class="container navbar">

        <a href="index.php" class="brand">

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

            <a href="bookings.php">
                Manage Bookings
            </a>

            <a href="equipment.php">
                Manage Equipment
            </a>

            <span class="nav-user">
                <?= e(currentUser()['name'] ?? 'Administrator') ?>
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
                Manage Equipment
            </h2>

            <p>
                View and update laboratory equipment, locations, and availability.
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
                        Equipment Inventory
                    </h3>

                    <p>
                        <?= count($equipment) ?>
                        equipment item<?= count($equipment) === 1 ? '' : 's' ?>
                        registered.
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
                        No equipment available
                    </h4>

                    <p>
                        Add laboratory equipment to begin managing reservations.
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

                                <th>
                                    Action
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
                                            class="badge <?= equipmentBadgeClass($item['status']) ?>"
                                        >
                                            <?= e($item['status']) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="equipment_form.php?id=<?= (int) $item['id'] ?>"
                                            class="btn btn-secondary btn-small"
                                        >
                                            Edit
                                        </a>

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
