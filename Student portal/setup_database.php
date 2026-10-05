<?php


require_once __DIR__ . '/db_connect.php';

$isCli = (php_sapi_name() === 'cli');


$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, null, DB_PORT);

$outputMessages = [];
$status = "info";

if ($conn->connect_error) {
    $outputMessages[] = "❌ Connection Failed: " . $conn->connect_error;
    $outputMessages[] = "Please ensure the MySQL module is started in your XAMPP Control Panel.";
    $status = "error";
} else {
    $outputMessages[] = "✔ Successfully connected to MySQL Server (" . $conn->server_info . ") on " . DB_HOST . ":" . DB_PORT;

    
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        $outputMessages[] = "❌ schema.sql file not found in " . __DIR__;
        $status = "error";
    } else {
        $sqlScript = file_get_contents($schemaFile);

        
        if ($conn->multi_query($sqlScript)) {
            do {
                
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());

            if ($conn->errno) {
                $outputMessages[] = "⚠ Warning during execution: " . $conn->error;
            } else {
                $outputMessages[] = "✔ Database 'studenthub_db' created / confirmed.";
                $outputMessages[] = "✔ Table 'students' initialized.";
                $outputMessages[] = "✔ Table 'events' initialized with seed events.";
                $outputMessages[] = "✔ Table 'event_registrations' initialized with foreign key constraints.";
                $outputMessages[] = "✔ Table 'contacts' initialized.";
                $outputMessages[] = "✔ Sample records and demo data successfully inserted.";
                $status = "success";
            }
        } else {
            $outputMessages[] = "❌ Failed to execute schema.sql: " . $conn->error;
            $status = "error";
        }
    }
    $conn->close();
}


if ($isCli) {
    echo "\n=== StudentHub MySQL Database Setup ===\n";
    foreach ($outputMessages as $msg) {
        echo $msg . "\n";
    }
    echo "=======================================\n\n";
    exit($status === "success" ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - StudentHub</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .setup-card {
            max-width: 700px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 35px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .console-box {
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Consolas', monospace;
            padding: 18px 22px;
            border-radius: 8px;
            margin: 20px 0;
            line-height: 1.8;
            font-size: 14px;
        }
        .console-box .success-line {
            color: #4ade80;
        }
        .console-box .error-line {
            color: #f87171;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        .btn-main {
            background: #1565c0;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .btn-main:hover {
            background: #0d47a1;
        }
    </style>
</head>
<body>

<header>
    <nav>
        <table width="100%">
            <tr>
                <td><h2>StudentHub</h2></td>
                <td align="center">
                    <a href="home.html">Home</a> |
                    <a href="index.php">Events</a> |
                    <a href="register.html">Register</a> |
                    <a href="view_records.php">Records</a>
                </td>
                <td align="right">
                    <a href="login.html">Login</a>
                </td>
            </tr>
        </table>
    </nav>
</header>

<hr>

<main class="setup-card">
    <h2>MySQL Database Setup & Migration</h2>
    <p>This script executes <code>schema.sql</code> to create the relational database tables (<strong>students</strong>, <strong>events</strong>, <strong>event_registrations</strong>) in MySQL with foreign key relationships.</p>

    <div class="console-box">
        <?php foreach ($outputMessages as $msg): ?>
            <?php
                $class = strpos($msg, '✔') !== false ? 'success-line' : (strpos($msg, '❌') !== false ? 'error-line' : '');
            ?>
            <div class="<?php echo $class; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
    </div>

    <?php if ($status === "success"): ?>
        <p style="color: #2e7d32; font-weight: 600;">
            ✔ Database 'studenthub_db' is now fully ready and operational in MySQL!
        </p>
        <div class="btn-group">
            <a href="view_records.php" class="btn-main">View Database Records</a>
            <a href="register.html" class="btn-main" style="background:#0d47a1;">Test Registration Form</a>
            <a href="home.html" class="btn-main" style="background:#546e7a;">Return to Home</a>
        </div>
    <?php else: ?>
        <p style="color: #c62828;">
            Please ensure MySQL is running in XAMPP and click "Retry Setup".
        </p>
        <div class="btn-group">
            <a href="setup_database.php" class="btn-main">🔄 Retry Setup</a>
        </div>
    <?php endif; ?>
</main>

<hr>

<footer>
    <table width="100%">
        <tr>
            <td><p>2026 StudentHub. All Rights Reserved.</p></td>
            <td align="right">
                <a href="home.html">Home</a> |
                <a href="contact.html">Contact</a>
            </td>
        </tr>
    </table>
</footer>

<script src="studenthub.js"></script>
</body>
</html>
