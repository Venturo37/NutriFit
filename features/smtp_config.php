<?php
// =================== EDITABLE CONFIG ===================
$newSMTP = "smtp.gmail.com";
$newPort = "587";
$newEmail = "ivanshak2005@gmail.com"; // I RECOMMEND USE YOUR OWN EMAIL FOR NOW
$newPassword = "zyauvujkdvvyejlf"; // INSERT YOUR OWN APP PASSWORD LIKE IN JOGET
$newSSL = "tls";

// =================== PATHS ===================
$phpIniPath = "C:/xampp/php/php.ini";
$sendmailIniPath = "C:/xampp/sendmail/sendmail.ini";

// =================== UPDATE php.ini ===================
$phpIni = file_get_contents($phpIniPath);

// Remove any leading ";" (comment) and update the values
$phpIni = preg_replace('/^[;]*\s*SMTP\s*=.*$/mi', "SMTP=$newSMTP", $phpIni);
$phpIni = preg_replace('/^[;]*\s*smtp_port\s*=.*$/mi', "smtp_port=$newPort", $phpIni);
$phpIni = preg_replace('/^[;]*\s*sendmail_from\s*=.*$/mi', "sendmail_from = $newEmail", $phpIni);
$phpIni = preg_replace('/^[;]*\s*sendmail_path\s*=.*$/mi', 'sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"', $phpIni);

file_put_contents($phpIniPath, $phpIni);

// =================== UPDATE sendmail.ini ===================
$sendmailIni = file_get_contents($sendmailIniPath);

// Remove any leading ";" (comment) and update the values
$sendmailIni = preg_replace('/^[;]*\s*smtp_server\s*=.*$/mi', "smtp_server=$newSMTP", $sendmailIni);
$sendmailIni = preg_replace('/^[;]*\s*smtp_port\s*=.*$/mi', "smtp_port=$newPort", $sendmailIni);
$sendmailIni = preg_replace('/^[;]*\s*smtp_ssl\s*=.*$/mi', "smtp_ssl=$newSSL", $sendmailIni);
$sendmailIni = preg_replace('/^[;]*\s*auth_username\s*=.*$/mi', "auth_username=$newEmail", $sendmailIni);
$sendmailIni = preg_replace('/^[;]*\s*auth_password\s*=.*$/mi', "auth_password=$newPassword", $sendmailIni);
$sendmailIni = preg_replace('/^[;]*\s*force_sender\s*=.*$/mi', "force_sender=$newEmail", $sendmailIni);

file_put_contents($sendmailIniPath, $sendmailIni);

// =================== DONE ===================
echo "✅ Configuration updated successfully.<br>";
echo "🔁 Please restart Apache via XAMPP Control Panel to apply changes.";
?>
