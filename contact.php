<?php
/**
 * Triangle Country Club — contact form handler.
 * Receives the enquiry form (POST), emails it to book@t-cc.net over authenticated
 * SMTP, sends the visitor an auto-reply, and returns JSON. Self-contained: no
 * external libraries. Settings live in mail-config.php.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$configPath = __DIR__ . '/mail-config.php';
if (!is_file($configPath)) { respond(false, 'Mail is not configured on the server.', 500); }
$cfg = require $configPath;
require __DIR__ . '/smtp.php';

function respond($ok, $msg, $code = 200) {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true, 'message' => $msg] : ['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') respond(false, 'Method not allowed.', 405);

// Honeypot — real users never fill this hidden field. Pretend success for bots.
if (!empty($_POST['company'])) respond(true, 'Thank you — your enquiry has been sent.');

// Collect + sanitise
$strip = fn($s) => trim(str_replace(["\r", "\n", "\0"], ' ', (string)$s));
$name  = $strip($_POST['name']  ?? '');
$email = $strip($_POST['email'] ?? '');
$phone = $strip($_POST['phone'] ?? '');
$type  = $strip($_POST['type']  ?? '');
$dates = $strip($_POST['dates'] ?? '');
$msg   = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $email === '') respond(false, 'Please provide your name and email.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Please enter a valid email address.');
if (mb_strlen($name) > 120 || mb_strlen($msg) > 5000) respond(false, 'That message is too long.');

if (strncmp((string)$cfg['smtp_pass'], 'PUT-', 4) === 0) {
    error_log('TCC contact form: mail-config.php password not set.');
    respond(false, 'Mail is not configured yet. Please email book@t-cc.net.', 500);
}

$subject = 'Website enquiry — ' . ($type !== '' ? $type : 'General') . ' — ' . $name;
$body =
    "New enquiry from the Triangle Country Club website\n" .
    "----------------------------------------------------\n" .
    "Name:            $name\n" .
    "Email:           $email\n" .
    "Phone:           " . ($phone !== '' ? $phone : '—') . "\n" .
    "Enquiry type:    " . ($type  !== '' ? $type  : '—') . "\n" .
    "Preferred dates: " . ($dates !== '' ? $dates : '—') . "\n" .
    "----------------------------------------------------\n\n" .
    ($msg !== '' ? $msg : '(no message provided)') . "\n";

// Send to the club (Reply-To = visitor, so a reply goes straight back to them)
list($ok, $err) = tcc_smtp_send($cfg, $cfg['to_email'], $cfg['to_name'], $subject, $body, $email, $name);
if (!$ok) {
    error_log('TCC contact form SMTP error: ' . $err);
    respond(false, 'Sorry — we could not send your enquiry right now. Please email book@t-cc.net or WhatsApp us.', 500);
}

// Best-effort auto-reply to the visitor
if (!empty($cfg['send_autoreply'])) {
    $reply =
        "Dear $name,\n\n" .
        "Thank you for your enquiry to Triangle Country Club. We've received your message and " .
        "a member of our team will reply within one business day.\n\n" .
        "For anything urgent, reach us on WhatsApp at +263 77 404 5150 or call +263 78 294 6200.\n\n" .
        "Warm regards,\n" .
        "Triangle Country Club\n" .
        "Ross Armstrong Way, Triangle, Zimbabwe\n" .
        "Where Business, Leisure & Lifestyle come together.";
    tcc_smtp_send($cfg, $email, $name, "We've received your enquiry — Triangle Country Club", $reply);
}

respond(true, "Thank you — your enquiry has been sent. We'll reply within one business day.");
