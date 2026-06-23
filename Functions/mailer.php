<?php

require_once __DIR__ . "/../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;

function boules_mail_env($name, $default = "")
{
    $value = getenv($name);

    return $value === false || $value === "" ? $default : $value;
}

function boules_mail_config()
{
    $fileConfig = file_exists(__DIR__ . "/mail_config.php")
        ? require __DIR__ . "/mail_config.php"
        : array();

    return array(
        "smtp_host" => boules_mail_env("MAIL_HOST", isset($fileConfig["smtp_host"]) ? $fileConfig["smtp_host"] : "127.0.0.1"),
        "smtp_port" => (int) boules_mail_env("MAIL_PORT", isset($fileConfig["smtp_port"]) ? $fileConfig["smtp_port"] : "1025"),
        "smtp_username" => boules_mail_env("MAIL_USERNAME", isset($fileConfig["smtp_username"]) ? $fileConfig["smtp_username"] : ""),
        "smtp_password" => boules_mail_env("MAIL_PASSWORD", isset($fileConfig["smtp_password"]) ? $fileConfig["smtp_password"] : ""),
        "smtp_encryption" => strtolower(boules_mail_env("MAIL_ENCRYPTION", isset($fileConfig["smtp_encryption"]) ? $fileConfig["smtp_encryption"] : "")),
        "from_email" => boules_mail_env("MAIL_FROM_EMAIL", isset($fileConfig["from_email"]) ? $fileConfig["from_email"] : "noreply@boules.test"),
        "from_name" => boules_mail_env("MAIL_FROM_NAME", isset($fileConfig["from_name"]) ? $fileConfig["from_name"] : "Boules Competities"),
        "reply_to" => boules_mail_env("MAIL_REPLY_TO", isset($fileConfig["reply_to"]) ? $fileConfig["reply_to"] : ""),
    );
}

function boules_create_mailer()
{
    $config = boules_mail_config();
    $mailer = new PHPMailer(true);

    $mailer->isSMTP();
    $mailer->Host = $config["smtp_host"];
    $mailer->Port = $config["smtp_port"];
    $mailer->SMTPAuth = $config["smtp_username"] !== "" || $config["smtp_password"] !== "";
    $mailer->Username = $config["smtp_username"];
    $mailer->Password = $config["smtp_password"];
    $mailer->CharSet = "UTF-8";

    if ($config["smtp_encryption"] !== "" && $config["smtp_encryption"] !== "none" && $config["smtp_encryption"] !== "false") {
        $mailer->SMTPSecure = $config["smtp_encryption"];
        $mailer->SMTPAutoTLS = true;
    } else {
        $mailer->SMTPSecure = "";
        $mailer->SMTPAutoTLS = false;
    }

    $mailer->setFrom($config["from_email"], $config["from_name"]);

    if ($config["reply_to"] !== "") {
        $mailer->addReplyTo($config["reply_to"]);
    }

    return $mailer;
}

function boules_send_html_mail($toEmail, $toName, $subject, $htmlBody, $textBody = "")
{
    $mailer = boules_create_mailer();

    $mailer->addAddress($toEmail, $toName);
    $mailer->Subject = $subject;
    $mailer->isHTML(true);
    $mailer->Body = $htmlBody;
    $mailer->AltBody = $textBody !== "" ? $textBody : trim(strip_tags(str_replace(array("<br>", "<br/>", "<br />"), "\n", $htmlBody)));

    $mailer->send();
}
