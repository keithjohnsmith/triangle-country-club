<?php
/**
 * Triangle Country Club — mail settings for contact.php and membership-apply.php
 * -----------------------------------------------------------------------------
 * EDIT THIS FILE ON THE SERVER after uploading. Set the two mailbox passwords
 * below. Keep it private — because it is a .php file the server executes it (it
 * is never served as readable text), and .htaccess also blocks direct access.
 *
 * NOTE: when you re-upload a new build, do NOT overwrite this file on the server,
 * or you'll wipe the passwords. (Keep a copy of your filled-in version.)
 * -----------------------------------------------------------------------------
 */
return [
  // --- SMTP server (same host for both mailboxes) ---
  'smtp_host'   => 'mail.trianglecountryclub.com',
  'smtp_port'   => 465,               // 465 = SSL  |  587 = STARTTLS
  'smtp_secure' => 'ssl',             // 'ssl' for port 465, 'tls' for port 587

  // === CONTACT FORM — logs in as / sends from contact@, delivered to bookings ===
  'smtp_user'   => 'contact@trianglecountryclub.com',    // SMTP login (sender)
  'smtp_pass'   => 'PUT-CONTACT-MAILBOX-PASSWORD-HERE',   // <-- set on the server
  'from_email'  => 'contact@trianglecountryclub.com',     // matches smtp_user (SPF/DKIM)
  'from_name'   => 'Triangle Country Club',
  'to_email'    => 'contact@trianglecountryclub.com',     // lands here; cPanel forwards to book@t-cc.net
  'to_name'     => 'Triangle Country Club',

  // === MEMBERSHIP FORM — logs in as / sends from membership@, delivered to Admin ===
  'membership_user'  => 'membership@trianglecountryclub.com', // SMTP login (sender)
  'membership_pass'  => 'PUT-MEMBERSHIP-MAILBOX-PASSWORD-HERE', // <-- set on the server
  'membership_email' => 'membership@trianglecountryclub.com',  // lands here; cPanel forwards to Monica.Peters@tongaat.com
  'membership_name'  => 'Triangle Country Club — Membership',

  // --- Send the visitor an automatic "we received it" reply? ---
  'send_autoreply' => true,
];
