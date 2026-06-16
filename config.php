<?php

$DB_HOST = 'localhost';
$DB_USER = 'student';
$DB_PASS = 'password';
$DB_NAME = 'stroymaterialy';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$dbConnection = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

$dbConnection->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
