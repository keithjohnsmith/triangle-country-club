<?php
/**
 * Triangle Country Club — Membership application handler.
 * Emails the completed application to Administration (mail-config.php →
 * membership_email, currently Monica.Peters@tongaat.com) over authenticated SMTP,
 * sends the applicant an acknowledgement, and returns JSON.
 * This form's submissions go ONLY to the membership/administration address.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$configPath = __DIR__ . '/mail-config.php';
if (!is_file($configPath)) { respond(false, 'Mail is not configured on the server.', 500); }
$cfg = require $configPath;
require __DIR__ . '/smtp.php';

// This form logs in as / sends from the dedicated membership mailbox
// (delivery destination is $cfg['membership_email'], set at send time below).
$cfg['smtp_user']  = $cfg['membership_user'];
$cfg['smtp_pass']  = $cfg['membership_pass'];
$cfg['from_email'] = $cfg['membership_user'];
$cfg['from_name']  = $cfg['membership_name'];

function respond($ok, $msg, $code = 200) {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true, 'message' => $msg] : ['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') respond(false, 'Method not allowed.', 405);

// Honeypot
if (!empty($_POST['company'])) respond(true, 'Thank you — your application has been submitted.');

$strip = fn($s) => trim(str_replace(["\r", "\n", "\0"], ' ', (string)$s));
$val   = fn($k) => $strip($_POST[$k] ?? '');

$surname   = $val('surname');
$forenames = $val('forenames');
$email     = $val('home_email');

if ($surname === '' || $forenames === '') respond(false, 'Please provide your surname and forename(s).');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Please enter a valid email address.');
if (empty($_POST['agree'])) respond(false, 'Please accept the declaration to submit your application.');

if (strncmp((string)$cfg['smtp_pass'], 'PUT-', 4) === 0) {
    error_log('TCC membership form: mail-config.php password not set.');
    respond(false, 'Applications are not configured yet. Please email the club.', 500);
}

// Membership type
$typeMap = [
    'triangle_family'   => 'Triangle Ltd — Family',
    'triangle_single'   => 'Triangle Ltd — Single',
    'triangle_student'  => 'Triangle Ltd — Student',
    'country_married'   => 'Country — Married',
    'country_single'    => 'Country — Single',
    'country_temporary' => 'Country — Temporary',
];
$mtype = $typeMap[$_POST['membership_type'] ?? ''] ?? '(not specified)';

// Sections of interest (checkboxes)
$sections = isset($_POST['sections']) && is_array($_POST['sections'])
    ? array_map($strip, $_POST['sections']) : [];
$sections = array_slice(array_filter($sections), 0, 20);

// Children (parallel arrays)
$cn = $_POST['child_name'] ?? []; $cd = $_POST['child_dob'] ?? []; $cs = $_POST['child_sex'] ?? [];
$children = [];
if (is_array($cn)) {
    for ($i = 0; $i < count($cn) && $i < 8; $i++) {
        $nm = $strip($cn[$i] ?? '');
        if ($nm === '') continue;
        $children[] = $nm . ' — DOB: ' . $strip($cd[$i] ?? '?') . ' — Sex: ' . $strip($cs[$i] ?? '?');
    }
}

$payMap = ['cash_cheque' => 'Non-company / Country — cash or cheque', 'stop_order' => 'Company employee — signed stop order'];
$payment = $payMap[$_POST['payment_method'] ?? ''] ?? '(not specified)';

$L = fn($label, $v) => str_pad($label, 22) . ($v !== '' ? $v : '—') . "\n";

$body  = "TRIANGLE COUNTRY CLUB — APPLICATION FOR MEMBERSHIP\n";
$body .= "(submitted via the website)\n";
$body .= str_repeat('=', 60) . "\n\n";

$body .= "APPLICANT\n" . str_repeat('-', 60) . "\n";
$body .= $L('Surname:', $surname);
$body .= $L('Forename(s):', $forenames);
$body .= $L('Home address:', $val('home_address'));
$body .= $L('Home tel:', $val('home_tel'));
$body .= $L('Email:', $email);
$body .= $L('Occupation:', $val('occupation'));
$body .= $L('Work address:', $val('work_address'));
$body .= $L('Work tel:', $val('work_tel'));
$body .= $L('Work email:', $val('work_email'));

$body .= "\nMEMBERSHIP\n" . str_repeat('-', 60) . "\n";
$body .= $L('Type applied for:', $mtype);
$body .= $L('Spouse forename:', $val('spouse'));
$body .= "Children:\n";
$body .= $children ? ('  - ' . implode("\n  - ", $children) . "\n") : "  —\n";

$body .= "\nSECTIONS OF INTEREST\n" . str_repeat('-', 60) . "\n";
$body .= ($sections ? implode(', ', $sections) : '—') . "\n";

$body .= "\nPROPOSER & SECONDERS (3 current members required)\n" . str_repeat('-', 60) . "\n";
$body .= $L('Proposer:', $val('proposer_name') . '  (card: ' . $val('proposer_card') . ')');
$body .= $L('Seconder 1:', $val('seconder1_name') . '  (card: ' . $val('seconder1_card') . ')');
$body .= $L('Seconder 2:', $val('seconder2_name') . '  (card: ' . $val('seconder2_card') . ')');

$body .= "\nPAYMENT\n" . str_repeat('-', 60) . "\n";
$body .= $L('Method:', $payment);

$body .= "\nDECLARATION\n" . str_repeat('-', 60) . "\n";
$body .= "Applicant accepted the declaration and the Club Constitution & indemnity terms.\n";
$body .= $L('Signed (typed name):', $val('signature_name'));
$body .= $L('Date:', $val('sign_date') !== '' ? $val('sign_date') : date('Y-m-d'));
$body .= "\n" . str_repeat('=', 60) . "\n";
$body .= "Submitted: " . date('r') . "\n";

$subject = 'Membership application — ' . $forenames . ' ' . $surname;

list($ok, $err) = tcc_smtp_send($cfg, $cfg['membership_email'], $cfg['membership_name'] ?? '', $subject, $body, $email, $forenames . ' ' . $surname);
if (!$ok) {
    error_log('TCC membership form SMTP error: ' . $err);
    respond(false, 'Sorry — we could not submit your application right now. Please try again shortly.', 500);
}

// Acknowledgement to the applicant
if (!empty($cfg['send_autoreply'])) {
    $ack =
        "Dear $forenames,\n\n" .
        "Thank you for applying for membership of Triangle Country Club. Your application has been " .
        "received by our Administration office and will be reviewed by the Club Management Committee.\n\n" .
        "Please note: your application must be proposed and seconded by three current members, and you " .
        "may be asked to complete separate forms for any sporting sections you wish to join.\n\n" .
        "We will be in touch regarding entrance fees, subscriptions and the next steps.\n\n" .
        "Warm regards,\n" .
        "Triangle Country Club — Administration\n" .
        "Ross Armstrong Way, Triangle, Zimbabwe";
    tcc_smtp_send($cfg, $email, $forenames . ' ' . $surname, 'We\'ve received your membership application — Triangle Country Club', $ack);
}

respond(true, "Thank you — your membership application has been submitted to our Administration office. You'll receive a confirmation email shortly.");
