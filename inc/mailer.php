<?php
require_once __DIR__ . '/helpers.php';

function smtp_cmd($fp, $cmd, $expect = null) {
    if ($cmd !== null) {
        fwrite($fp, $cmd . "\r\n");
    }
    $data = '';
    while ($line = fgets($fp, 515)) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    if ($expect !== null) {
        $code = (int)substr($data, 0, 3);
        if (!in_array($code, (array)$expect, true)) {
            return [false, $data];
        }
    }
    return [true, $data];
}

function smtp_send($acc, $to, $subject, $body) {
    $remote = ($acc['secure'] === 'ssl' ? 'ssl://' : '') . $acc['host'] . ':' . (int)$acc['port'];
    $fp = @stream_socket_client($remote, $errno, $errstr, 10);
    if (!$fp) {
        return [false, $errstr];
    }
    stream_set_timeout($fp, 10);
    smtp_cmd($fp, null, 220);
    $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
    smtp_cmd($fp, $ehlo, 250);
    if ($acc['secure'] === 'tls') {
        smtp_cmd($fp, 'STARTTLS', 220);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return [false, 'tls failed'];
        }
        smtp_cmd($fp, $ehlo, 250);
    }
    smtp_cmd($fp, 'AUTH LOGIN', 334);
    smtp_cmd($fp, base64_encode($acc['username']), 334);
    smtp_cmd($fp, base64_encode($acc['password']), 235);
    smtp_cmd($fp, 'MAIL FROM:<' . $acc['username'] . '>', 250);
    smtp_cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
    smtp_cmd($fp, 'DATA', 354);
    $headers = 'From: ' . mb_encode_mimeheader($acc['from_name'] ?: 'mxyA') . ' <' . $acc['username'] . ">\r\n";
    $headers .= 'To: <' . $to . ">\r\n";
    $headers .= 'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
    $data = $headers . "\r\n" . chunk_split(base64_encode($body));
    $data = str_replace("\n.", "\n..", $data);
    fwrite($fp, $data . "\r\n.\r\n");
    smtp_cmd($fp, null, 250);
    smtp_cmd($fp, 'QUIT', 221);
    fclose($fp);
    return [true, ''];
}

function send_mail($to, $subject, $body) {
    $accounts = db()->query("SELECT * FROM mail_accounts WHERE status=1 ORDER BY sort ASC, id ASC")->fetchAll();
    if (!$accounts) {
        return [false, 'no account'];
    }
    $mode = setting('mail_mode', 'backup');
    $ordered = $accounts;
    if ($mode === 'rotate') {
        $row = db()->query("SELECT v FROM settings WHERE k='mail_rotate_index'")->fetch();
        $idx = $row ? (int)$row['v'] : 0;
        $n = count($accounts);
        $ordered = [];
        for ($i = 0; $i < $n; $i++) {
            $ordered[] = $accounts[($idx + $i) % $n];
        }
    }
    $last = 'send failed';
    foreach ($ordered as $acc) {
        [$ok, $err] = smtp_send($acc, $to, $subject, $body);
        if ($ok) {
            if ($mode === 'rotate') {
                $row = db()->query("SELECT v FROM settings WHERE k='mail_rotate_index'")->fetch();
                set_setting('mail_rotate_index', (string)(($row ? (int)$row['v'] : 0) + 1));
            }
            return [true, ''];
        }
        $last = $err;
    }
    return [false, $last];
}