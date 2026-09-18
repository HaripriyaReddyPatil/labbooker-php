<?php
declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Database Setup
|--------------------------------------------------------------------------
*/

$dataDir = __DIR__ . '/data';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$pdo = new PDO(
    'sqlite:' . $dataDir . '/labbooker.sqlite'
);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);

$pdo->exec("PRAGMA foreign_keys = ON");


/*
|--------------------------------------------------------------------------
| Users Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");


/*
|--------------------------------------------------------------------------
| Equipment Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS equipment (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        location TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'Available'
    )
");


/*
|--------------------------------------------------------------------------
| Bookings Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        equipment_id INTEGER NOT NULL,
        requester TEXT NOT NULL,
        requester_email TEXT NOT NULL,
        start_at TEXT NOT NULL,
        end_at TEXT NOT NULL,
        purpose TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'Pending',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (equipment_id)
            REFERENCES equipment(id)
    )
");


/*
|--------------------------------------------------------------------------
| Activity Log Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        actor_name TEXT NOT NULL,
        action TEXT NOT NULL,
        description TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    )
");


/*
|--------------------------------------------------------------------------
| Seed Sample Equipment
|--------------------------------------------------------------------------
*/

$equipmentCount = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM equipment
    ")
    ->fetchColumn();

if ($equipmentCount === 0) {

    $seed = [
        [
            '3D Printer',
            'Fabrication',
            'Engineering Lab 204'
        ],
        [
            'Oscilloscope',
            'Electronics',
            'ECE Lab 118'
        ],
        [
            'GPU Workstation',
            'Computing',
            'AI Lab 310'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO equipment (
            name,
            category,
            location
        )
        VALUES (?, ?, ?)
    ");

    foreach ($seed as $row) {
        $stmt->execute($row);
    }
}


/*
|--------------------------------------------------------------------------
| Output Escaping
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Redirect Helper
|--------------------------------------------------------------------------
*/

function redirect(string $path): never
{
    header("Location: $path");
    exit;
}


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

function flash(
    ?string $message = null,
    string $type = 'ok'
): ?array {

    if ($message !== null) {

        $_SESSION['flash'] = [
            $message,
            $type
        ];

        return null;
    }

    if (!empty($_SESSION['flash'])) {

        $flash = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $flash;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Authentication Helpers
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}


function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}


function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}


function logoutUser(): void
{
    unset($_SESSION['user']);

    session_regenerate_id(true);
}


/*
|--------------------------------------------------------------------------
| Role Helpers
|--------------------------------------------------------------------------
*/

function isAdmin(): bool
{
    return isset($_SESSION['user'])
        && $_SESSION['user']['role'] === 'admin';
}


function requireLogin(): void
{
    if (!isLoggedIn()) {

        flash(
            'Please sign in to continue.',
            'error'
        );

        redirect('login.php');
    }
}


function requireAdmin(): void
{
    if (!isLoggedIn()) {

        flash(
            'Please sign in to continue.',
            'error'
        );

        redirect('login.php');
    }

    if (!isAdmin()) {

        http_response_code(403);

        exit('Access denied.');
    }
}


/*
|--------------------------------------------------------------------------
| Activity Logging Helper
|--------------------------------------------------------------------------
*/

function logActivity(
    PDO $pdo,
    string $action,
    string $description
): void {

    $user = currentUser();

    $userId = isset($user['id'])
        ? (int) $user['id']
        : null;

    $actorName = $user['name'] ?? 'System';

    $stmt = $pdo->prepare("
        INSERT INTO activity_log (
            user_id,
            actor_name,
            action,
            description
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $actorName,
        $action,
        $description
    ]);
}
?>
