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
| Determine Add / Edit Mode
|--------------------------------------------------------------------------
*/

$equipmentId = (int) ($_GET['id'] ?? $_POST['equipment_id'] ?? 0);

$isEditing = $equipmentId > 0;

$equipment = null;

if ($isEditing) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM equipment
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$equipmentId]);

    $equipment = $stmt->fetch();

    if (!$equipment) {
        http_response_code(404);
        exit('Equipment not found.');
    }
}


/*
|--------------------------------------------------------------------------
| Preserve Original Values For Activity Logging
|--------------------------------------------------------------------------
*/

$originalName = $equipment['name'] ?? '';
$originalCategory = $equipment['category'] ?? '';
$originalLocation = $equipment['location'] ?? '';
$originalStatus = $equipment['status'] ?? '';


/*
|--------------------------------------------------------------------------
| Default Form Values
|--------------------------------------------------------------------------
*/

$name = $equipment['name'] ?? '';
$category = $equipment['category'] ?? '';
$location = $equipment['location'] ?? '';
$status = $equipment['status'] ?? 'Available';

$errors = [];

$allowedStatuses = [
    'Available',
    'Unavailable',
    'Maintenance'
];


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (
        $name === '' ||
        $category === '' ||
        $location === '' ||
        $status === ''
    ) {
        $errors[] = 'All fields are required.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Please select a valid equipment status.';
    }

    if (strlen($name) > 100) {
        $errors[] = 'Equipment name is too long.';
    }

    if (strlen($category) > 100) {
        $errors[] = 'Category is too long.';
    }

    if (strlen($location) > 150) {
        $errors[] = 'Location is too long.';
    }

    if (!$errors) {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Edit Existing Equipment
            |--------------------------------------------------------------------------
            */

            if ($isEditing) {

                $stmt = $pdo->prepare("
                    UPDATE equipment
                    SET
                        name = ?,
                        category = ?,
                        location = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name,
                    $category,
                    $location,
                    $status,
                    $equipmentId
                ]);

                /*
                |--------------------------------------------------------------------------
                | Log Equipment Update
                |--------------------------------------------------------------------------
                */

                $changes = [];

                if ($originalName !== $name) {
                    $changes[] =
                        "name changed from '{$originalName}' to '{$name}'";
                }

                if ($originalCategory !== $category) {
                    $changes[] =
                        "category changed from '{$originalCategory}' to '{$category}'";
                }

                if ($originalLocation !== $location) {
                    $changes[] =
                        "location changed from '{$originalLocation}' to '{$location}'";
                }

                if ($originalStatus !== $status) {
                    $changes[] =
                        "status changed from '{$originalStatus}' to '{$status}'";
                }

                if ($changes) {

                    logActivity(
                        $pdo,
                        'Equipment Updated',
                        "{$name}: " . implode(', ', $changes) . "."
                    );

                } else {

                    logActivity(
                        $pdo,
                        'Equipment Updated',
                        "{$name} was saved with no field changes."
                    );
                }

                $pdo->commit();

                flash('Equipment updated successfully.');

            /*
            |--------------------------------------------------------------------------
            | Add New Equipment
            |--------------------------------------------------------------------------
            */

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO equipment (
                        name,
                        category,
                        location,
                        status
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $category,
                    $location,
                    $status
                ]);

                $newEquipmentId = (int) $pdo->lastInsertId();

                logActivity(
                    $pdo,
                    'Equipment Added',
                    "{$name} was added in {$location} with status {$status} (Equipment #{$newEquipmentId})."
                );

                $pdo->commit();

                flash('Equipment added successfully.');
            }

            redirect('equipment.php');

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'The equipment could not be saved. Please try again.';
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
        <?= $isEditing ? 'Edit Equipment' : 'Add Equipment' ?>
        | LabBooker
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


        <div class="page-heading">

            <h2>
                <?= $isEditing
                    ? 'Edit Equipment'
                    : 'Add Equipment'
                ?>
            </h2>

            <p>

                <?= $isEditing
                    ? 'Update equipment details, location, or availability.'
                    : 'Register a new laboratory resource in LabBooker.'
                ?>

            </p>

        </div>


        <div class="form-card panel">


            <?php if ($errors): ?>

                <div class="alert alert-error">

                    <?= e(implode(' ', $errors)) ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >


                <?php if ($isEditing): ?>

                    <input
                        type="hidden"
                        name="equipment_id"
                        value="<?= $equipmentId ?>"
                    >

                <?php endif; ?>


                <!-- EQUIPMENT NAME -->

                <div class="form-group">

                    <label for="name">
                        Equipment Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        required
                        placeholder="Example: GPU Workstation"
                        value="<?= e($name) ?>"
                    >

                </div>


                <!-- CATEGORY -->

                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <input
                        type="text"
                        id="category"
                        name="category"
                        maxlength="100"
                        required
                        placeholder="Example: Computing"
                        value="<?= e($category) ?>"
                    >

                </div>


                <!-- LOCATION -->

                <div class="form-group">

                    <label for="location">
                        Location
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="150"
                        required
                        placeholder="Example: AI Lab 310"
                        value="<?= e($location) ?>"
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label for="status">
                        Equipment Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >

                        <option
                            value="Available"
                            <?= $status === 'Available'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Available
                        </option>

                        <option
                            value="Unavailable"
                            <?= $status === 'Unavailable'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Unavailable
                        </option>

                        <option
                            value="Maintenance"
                            <?= $status === 'Maintenance'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Maintenance
                        </option>

                    </select>

                </div>


                <!-- ACTIONS -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <?= $isEditing
                            ? 'Save Changes'
                            : 'Add Equipment'
                        ?>

                    </button>


                    <a
                        href="equipment.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>


            </form>

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