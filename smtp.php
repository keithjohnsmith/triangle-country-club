<?php
/**
 * Triangle Country Club — shared, self-contained SMTP sender (no external library).
 * Used by contact.php and membership-apply.php. Settings come from mail-config.php.
 * Supports SSL (465) and STARTTLS (587) with AUTH LOGIN.
 * Returns [bool success, string error].
 */
if (!function_exists('tcc_smtp_send')) {
    function tcc_smtp_send($cfg, $toEmail, $toName, $subject, $bodyText, $replyToEmail = null, $replyToName = null) {
        $host   = $cfg['smtp_host'];
        $port   = (int)$cfg['smtp_port'];
        $secure = strtolower($cfg['smtp_secure']);

        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) return [false, "connect failed: $errstr ($errno)"];
        stream_set_timeout($fp, 20);

        $read = function () use ($fp) {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $data;
        };
        $cmd = function ($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };
        $ok  = fn($resp, $codes) => in_array(substr($resp, 0, 3), (array)$codes, true);
        $bye = function ($fp, $err) { @fwrite($fp, "QUIT\r\n"); @fclose($fp); return [false, $err]; };

        if (!$ok($read(), '220'))                 return $bye($fp, 'no greeting');
        if (!$ok($cmd('EHLO trianglecc'), '250')) return $bye($fp, 'EHLO rejected');

        if ($secure === 'tls') {
            if (!$ok($cmd('STARTTLS'), '220'))    return $bye($fp, 'STARTTLS rejected');
            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT;
            }
            if (!stream_socket_enable_crypto($fp, true, $crypto)) return $bye($fp, 'TLS negotiation failed');
            if (!$ok($cmd('EHLO trianglecc'), '250')) return $bye($fp, 'EHLO(tls) rejected');
        }

        if (!$ok($cmd('AUTH LOGIN'), '334'))                      return $bye($fp, 'AUTH not accepted');
        if (!$ok($cmd(base64_encode($cfg['smtp_user'])), '334'))  return $bye($fp, 'username rejected');
        if (!$ok($cmd(base64_encode($cfg['smtp_pass'])), '235'))  return $bye($fp, 'authentication failed');

        if (!$ok($cmd('MAIL FROM:<' . $cfg['from_email'] . '>'), '250')) return $bye($fp, 'MAIL FROM rejected');
        if (!$ok($cmd('RCPT TO:<' . $toEmail . '>'), ['250', '251']))    return $bye($fp, 'RCPT TO rejected');
        if (!$ok($cmd('DATA'), '354'))                                    return $bye($fp, 'DATA rejected');

        $enc = fn($s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $enc($cfg['from_name']) . ' <' . $cfg['from_email'] . '>';
        $headers[] = 'To: ' . ($toName !== '' ? $enc($toName) . ' ' : '') . '<' . $toEmail . '>';
        if ($replyToEmail) $headers[] = 'Reply-To: ' . ($replyToName ? $enc($replyToName) . ' ' : '') . '<' . $replyToEmail . '>';
        $headers[] = 'Subject: ' . $enc($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $headers[] = 'X-Mailer: TriangleCC-Web';
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . $host . '>';

        $data = implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($bodyText))) . "\r\n.";
        fwrite($fp, $data . "\r\n");
        if (!$ok($read(), '250')) return $bye($fp, 'message not accepted');

        @fwrite($fp, "QUIT\r\n");
        @fclose($fp);
        return [true, ''];
    }
}
