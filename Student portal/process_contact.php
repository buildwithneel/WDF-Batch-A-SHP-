<?php


function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contact.html");
    exit();
}

$errors = [];


$fullname = sanitize_input($_POST['fullname'] ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone    = sanitize_input($_POST['phone'] ?? '');
$subject  = sanitize_input($_POST['subject'] ?? '');
$message  = sanitize_input($_POST['message'] ?? '');


if (empty($fullname)) {
    $errors[] = "Full Name is required.";
} elseif (strlen($fullname) < 2 || strlen($fullname) > 60) {
    $errors[] = "Full Name must be between 2 and 60 characters.";
}

if (empty($email)) {
    $errors[] = "Email Address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid email address.";
}

if (!empty($phone) && !preg_match("/^[0-9+\- ]{10,15}$/", $phone)) {
    $errors[] = "Please provide a valid phone number (10-15 digits).";
}

if (empty($subject)) {
    $errors[] = "Subject is required.";
} elseif (strlen($subject) < 3) {
    $errors[] = "Subject must be at least 3 characters.";
}

if (empty($message)) {
    $errors[] = "Message is required.";
} elseif (strlen($message) < 10) {
    $errors[] = "Message must be at least 10 characters long.";
}

$success = false;
$ticketId = "";
$timestamp = date("Y-m-d H:i:s");


if (empty($errors)) {
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    $jsonFile = $dataDir . '/contacts.json';
    $csvFile  = $dataDir . '/contacts.csv';

    $ticketId = "TCK-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -4));

    $contactData = [
        'ticket_id' => $ticketId,
        'fullname'  => $fullname,
        'email'     => $email,
        'phone'     => $phone,
        'subject'   => $subject,
        'message'   => $message,
        'submitted_at' => $timestamp
    ];

    
    $existingContacts = [];
    if (file_exists($jsonFile)) {
        $decoded = json_decode(file_get_contents($jsonFile), true);
        if (is_array($decoded)) {
            $existingContacts = $decoded;
        }
    }
    $existingContacts[] = $contactData;
    file_put_contents($jsonFile, json_encode($existingContacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    
    $csvFileExists = file_exists($csvFile);
    $fp = fopen($csvFile, 'a');
    if ($fp !== false) {
        if (!$csvFileExists || filesize($csvFile) === 0) {
            fputcsv($fp, ['Ticket ID', 'Full Name', 'Email', 'Phone', 'Subject', 'Message', 'Submitted At']);
        }
        fputcsv($fp, [$ticketId, $fullname, $email, $phone, $subject, $message, $timestamp]);
        fclose($fp);
    }

    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? "Message Sent" : "Submission Error"; ?> - StudentHub</title>
    <link rel="stylesheet" href="contact.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .result-container {
            max-width: 650px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 35px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .success-badge {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        .error-badge {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .error-badge h3 {
            margin: 0 0 10px 0;
            color: #c62828 !important;
            font-size: 18px;
        }
        .error-badge ul {
            margin: 0;
            padding-left: 20px;
            background: transparent;
            box-shadow: none;
        }
        .error-badge li {
            margin: 5px 0;
            color: #b71c1c !important;
        }
        .record-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .record-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .record-table td.label-col {
            font-weight: 600;
            color: #0d47a1;
            width: 35%;
            background: #f8fbff;
        }
        .storage-status {
            background: #f0f7ff;
            border-left: 4px solid #1565c0;
            padding: 12px 16px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 13px;
            color: #1565c0;
        }
        .storage-status code {
            background: #e1effe;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: #1565c0;
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: #0d47a1;
        }
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-secondary:hover {
            background: #ccc;
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
                    <a href="about.html">About</a> |
                    <a href="events.html">Events</a> |
                    <a href="faq.html">FAQ</a> |
                    <a href="contact.html">Contact</a>
                </td>
                <td align="right">
                    <a href="login.html">Login</a> |
                    <a href="register.html">Register</a>
                </td>
            </tr>
        </table>
    </nav>
</header>

<hr>

<main>
    <div class="result-container">
        <?php if ($success): ?>
            <div class="success-badge">
                <span>✔</span>
                <span>Your Message Has Been Submitted Successfully!</span>
            </div>

            <h2>Inquiry Confirmation</h2>
            <p>Our support and academic department have received your request.</p>

            <table class="record-table">
                <tr>
                    <td class="label-col">Ticket Reference</td>
                    <td><strong><?php echo htmlspecialchars($ticketId); ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">Name</td>
                    <td><?php echo htmlspecialchars($fullname); ?></td>
                </tr>
                <tr>
                    <td class="label-col">Email</td>
                    <td><?php echo htmlspecialchars($email); ?></td>
                </tr>
                <tr>
                    <td class="label-col">Phone</td>
                    <td><?php echo htmlspecialchars($phone ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label-col">Subject</td>
                    <td><?php echo htmlspecialchars($subject); ?></td>
                </tr>
                <tr>
                    <td class="label-col">Message</td>
                    <td><?php echo nl2br(htmlspecialchars($message)); ?></td>
                </tr>
                <tr>
                    <td class="label-col">Submission Time</td>
                    <td><?php echo htmlspecialchars($timestamp); ?></td>
                </tr>
            </table>

            <div class="storage-status">
                <strong>Data Storage Confirmed:</strong>
                Record appended to <code>data/contacts.json</code> and <code>data/contacts.csv</code>.
            </div>

            <div class="action-buttons">
                <a href="contact.html" class="btn-primary">Submit Another Query</a>
                <a href="view_records.php" class="btn-secondary">View Stored Records</a>
                <a href="home.html" class="btn-secondary">Back to Home</a>
            </div>

        <?php else: ?>
            <div class="error-badge">
                <h3>⚠ Submission Errors</h3>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <p>Please return to the contact form and fix the issues listed above.</p>

            <div class="action-buttons">
                <button onclick="window.history.back();" class="btn-primary">Go Back & Edit</button>
                <a href="contact.html" class="btn-secondary">Back to Contact Form</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<hr>

<footer>
    <table width="100%">
        <tr>
            <td><p>2026 StudentHub. All Rights Reserved.</p></td>
            <td align="right">
                <a href="home.html">Home</a> |
                <a href="about.html">About</a> |
                <a href="contact.html">Contact</a>
            </td>
        </tr>
    </table>
</footer>

<script src="studenthub.js"></script>
</body>
</html>
