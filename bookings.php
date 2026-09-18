<?php
require 'config.php';

requireAdmin();

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
| Read Search / Filter Values
|--------------------------------------------------------------------------
*/

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$allowedStatuses = [
    '',
    'Pending',
    'Approved',
    'Rejected',
    'Cancelled'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}


/*
|--------------------------------------------------------------------------
| Handle Booking Status Changes
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $statusMap = [
        'approve' => 'Approved',
        'reject'  => 'Rejected',
        'cancel'  => 'Cancelled'
    ];

    if (
        $bookingId > 0 &&
        isset($statusMap[$action])
    ) {

        /*
        |--------------------------------------------------------------------------
        | Load Booking Details
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                b.*,
                e.name AS equipment_name
            FROM bookings b
            JOIN equipment e
                ON e.id = b.equipment_id
            WHERE b.id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $bookingId
        ]);

        $booking = $stmt->fetch();

        if ($booking) {

            try {

                $pdo->beginTransaction();

                /*
                |--------------------------------------------------------------------------
                | Update Booking Status
                |--------------------------------------------------------------------------
                */

                $newStatus = $statusMap[$action];

                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newStatus,
                    $bookingId
                ]);


                /*
                |--------------------------------------------------------------------------
                | Create Activity Entry
                |--------------------------------------------------------------------------
                */

                $equipmentName =
                    $booking['equipment_name']
                    ?? 'equipment';

                $requesterName =
                    $booking['requester']
                    ?? 'Unknown requester';

                $actionLabel = match ($action) {

                    'approve' =>
                        'Booking Approved',

                    'reject' =>
                        'Booking Rejected',

                    'cancel' =>
                        'Booking Cancelled',

                    default =>
                        'Booking Updated'
                };

                $description =
                    "{$equipmentName} reservation for "
                    . "{$requesterName} "
                    . "(Booking #{$bookingId}) "
                    . "was {$newStatus}.";

                logActivity(
                    $pdo,
                    $actionLabel,
                    $description
                );


                /*
                |--------------------------------------------------------------------------
                | Commit Changes
                |--------------------------------------------------------------------------
                */

                $pdo->commit();

                flash(
                    'Booking status updated successfully.'
                );

            } catch (Throwable $exception) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                flash(
                    'Booking status could not be updated.',
                    'error'
                );
            }

        } else {

            flash(
                'Booking could not be found.',
                'error'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Preserve Search / Filter After Action
    |--------------------------------------------------------------------------
    */

    $returnSearch =
        trim($_POST['return_q'] ?? '');

    $returnStatus =
        trim($_POST['return_status'] ?? '');

    $query = [];

    if ($returnSearch !== '') {
        $query['q'] = $returnSearch;
    }

    if ($returnStatus !== '') {
        $query['status'] = $returnStatus;
    }

    $redirectUrl = 'bookings.php';

    if ($query) {
        $redirectUrl .=
            '?' . http_build_query($query);
    }

    redirect($redirectUrl);
}


/*
|--------------------------------------------------------------------------
| Load Booking Records
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        b.*,
        e.name AS equipment_name,
        e.category AS equipment_category,
        e.location AS equipment_location
    FROM bookings b
    JOIN equipment e
        ON e.id = b.equipment_id
    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            e.name LIKE ?
            OR e.category LIKE ?
            OR e.location LIKE ?
            OR b.requester LIKE ?
            OR b.requester_email LIKE ?
            OR b.purpose LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($statusFilter !== '') {

    $sql .= "
        AND b.status = ?
    ";

    $params[] = $statusFilter;
}


$sql .= "
    ORDER BY b.id DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$rows = $stmt->fetchAll();

$f = flash();


/*
|--------------------------------------------------------------------------
| Status Counts
|--------------------------------------------------------------------------
*/

$statusCounts = [

    'All' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM bookings
        ")
        ->fetchColumn(),

    'Pending' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM bookings
            WHERE status = 'Pending'
        ")
        ->fetchColumn(),

    'Approved' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM bookings
            WHERE status = 'Approved'
        ")
        ->fetchColumn(),

    'Rejected' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM bookings
            WHERE status = 'Rejected'
        ")
        ->fetchColumn(),

    'Cancelled' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM bookings
            WHERE status = 'Cancelled'
        ")
        ->fetchColumn()
];


/*
|--------------------------------------------------------------------------
| Status Badge Helper
|--------------------------------------------------------------------------
*/

function bookingBadgeClass(string $status): string
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
        Manage Bookings | LabBooker
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


<!-- MAIN -->

<main>

    <div class="container">


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <h2>
                Manage Booking Requests
            </h2>

            <p>
                Search, filter, approve, reject, or cancel equipment reservations.
            </p>

        </div>


        <!-- FLASH MESSAGE -->

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


        <!-- STATUS SUMMARY -->

        <div class="booking-summary">

            <?php foreach ($statusCounts as $label => $count): ?>

                <div class="booking-summary-card">

                    <span>
                        <?= e($label) ?>
                    </span>

                    <strong>
                        <?= $count ?>
                    </strong>

                </div>

            <?php endforeach; ?>

        </div>


        <!-- SEARCH / FILTER -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h3>
                        Find Reservations
                    </h3>

                    <p>
                        Search by researcher, equipment, email, location, or purpose.
                    </p>

                </div>

            </div>


            <form
                method="GET"
                class="filter-form"
            >

                <div class="filter-search">

                    <label for="q">
                        Search
                    </label>

                    <input
                        type="search"
                        id="q"
                        name="q"
                        placeholder="Search bookings..."
                        value="<?= e($search) ?>"
                    >

                </div>


                <div class="filter-status">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="">
                            All statuses
                        </option>

                        <option
                            value="Pending"
                            <?= $statusFilter === 'Pending'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Approved"
                            <?= $statusFilter === 'Approved'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Approved
                        </option>

                        <option
                            value="Rejected"
                            <?= $statusFilter === 'Rejected'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Rejected
                        </option>

                        <option
                            value="Cancelled"
                            <?= $statusFilter === 'Cancelled'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Cancelled
                        </option>

                    </select>

                </div>


                <div class="filter-buttons">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Apply Filters
                    </button>


                    <?php if (
                        $search !== '' ||
                        $statusFilter !== ''
                    ): ?>

                        <a
                            href="bookings.php"
                            class="btn btn-secondary"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>


        <!-- RESERVATIONS -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <h3>
                        Reservations
                    </h3>

                    <p>

                        <?= count($rows) ?>

                        result<?= count($rows) === 1 ? '' : 's' ?>

                        <?php if (
                            $search !== '' ||
                            $statusFilter !== ''
                        ): ?>

                            matching your filters.

                        <?php else: ?>

                            currently recorded.

                        <?php endif; ?>

                    </p>

                </div>


                <a
                    href="booking_form.php"
                    class="btn btn-primary"
                >
                    + New Booking
                </a>

            </div>


            <?php if (!$rows): ?>

                <div class="empty-state">

                    <h4>
                        No matching bookings
                    </h4>

                    <p>

                        <?php if (
                            $search !== '' ||
                            $statusFilter !== ''
                        ): ?>

                            Try changing or clearing your search filters.

                        <?php else: ?>

                            Reservation requests will appear here once submitted.

                        <?php endif; ?>

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
                                    Purpose
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($rows as $booking): ?>

                                <tr>


                                    <!-- EQUIPMENT -->

                                    <td>

                                        <strong>
                                            <?= e($booking['equipment_name']) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e($booking['equipment_category']) ?>
                                        </small>

                                        <br>

                                        <small>
                                            <?= e($booking['equipment_location']) ?>
                                        </small>

                                    </td>


                                    <!-- REQUESTER -->

                                    <td>

                                        <strong>
                                            <?= e($booking['requester']) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e($booking['requester_email']) ?>
                                        </small>

                                    </td>


                                    <!-- SCHEDULE -->

                                    <td>

                                        <strong>
                                            <?= e(
                                                date(
                                                    'M j, Y g:i A',
                                                    strtotime($booking['start_at'])
                                                )
                                            ) ?>
                                        </strong>

                                        <br>

                                        <small>
                                            to
                                            <?= e(
                                                date(
                                                    'M j, Y g:i A',
                                                    strtotime($booking['end_at'])
                                                )
                                            ) ?>
                                        </small>

                                    </td>


                                    <!-- PURPOSE -->

                                    <td>

                                        <div class="purpose-text">

                                            <?= e($booking['purpose']) ?>

                                        </div>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="badge <?= bookingBadgeClass(
                                                $booking['status']
                                            ) ?>"
                                        >

                                            <?= e($booking['status']) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="booking-actions">


                                            <?php if (
                                                $booking['status'] === 'Pending'
                                            ): ?>


                                                <!-- APPROVE -->

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

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="approve"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="return_q"
                                                        value="<?= e($search) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="return_status"
                                                        value="<?= e($statusFilter) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-success btn-small"
                                                    >
                                                        Approve
                                                    </button>

                                                </form>


                                                <!-- REJECT -->

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

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="reject"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="return_q"
                                                        value="<?= e($search) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="return_status"
                                                        value="<?= e($statusFilter) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-small"
                                                        onclick="return confirm('Reject this booking request?');"
                                                    >
                                                        Reject
                                                    </button>

                                                </form>


                                            <?php elseif (
                                                $booking['status'] === 'Approved'
                                            ): ?>


                                                <!-- CANCEL -->

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

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="cancel"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="return_q"
                                                        value="<?= e($search) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="return_status"
                                                        value="<?= e($statusFilter) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-secondary btn-small"
                                                        onclick="return confirm('Cancel this approved booking?');"
                                                    >
                                                        Cancel
                                                    </button>

                                                </form>


                                            <?php else: ?>

                                                <span class="muted-action">
                                                    No action
                                                </span>

                                            <?php endif; ?>


                                        </div>

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