<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with password 'root') */
define('DB_SERVER', 'db');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'eleave');

/* PHP 8.1 and later throw exceptions from mysqli by default.
Turn that off so failures are handled by the checks below, as in PHP 7. */
mysqli_report(MYSQLI_REPORT_OFF);

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
