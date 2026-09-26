<?php
// Include config file and required libraries
require_once "config.php";
include '../lib/format_date.php';

// Initialize the session
session_start();

// Check if the admin is logged in, if not then redirect him to admin login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin_login.php");
    exit;
} elseif (isset($_SESSION["admin_email"])) {
    // Get id of admin
    $sql = 'SELECT id FROM users WHERE email = ? AND type = "supervisor"';
    if ($stmt = mysqli_prepare($link, $sql)) {

        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $_SESSION["admin_email"]);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {

            /* Store result */
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 0) {
                mysqli_stmt_close($stmt);
                header("location: ../index.php");
                exit;
            }

            // Close statement
            mysqli_stmt_close($stmt);

            //Update application status
            if ($_GET["accept"] == "true") {
                $status = 'accepted';
            } elseif ($_GET["accept"] == "false") {
                $status = 'rejected';
            } else {
                $status = 'pending';
            }
            // Update application status
            $sql = 'UPDATE applications SET status=? WHERE id=?';
            if ($stmt = mysqli_prepare($link, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "si", $status, $_GET["app_id"]);

                // Attempt to execute the prepared statement
                if (mysqli_stmt_execute($stmt)) {

                    /* Store result */
                    mysqli_stmt_close($stmt);

                    // Get application submission date and employee email
                    $sql2 = 'SELECT date_submitted, email FROM applications JOIN users WHERE applications.id=? AND employee_id=users.id';
                    if ($stmt2 = mysqli_prepare($link, $sql2)) {

                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt2, "s", $_GET["app_id"]);

                        // Attempt to execute the prepared statement
                        if (mysqli_stmt_execute($stmt2)) {

                            /* Store result */
                            mysqli_stmt_bind_result($stmt2, $submission_date, $email);

                            while (mysqli_stmt_fetch($stmt2)) {
                                $date_submitted = $submission_date;
                                $email_to = $email;
                            }

                            // Close statement
                            mysqli_stmt_close($stmt2);

                            //Send e-mail back to applicant
                            $to = $email_to;
                            $subject = 'e-Leave Response';

                            $message = file_get_contents('../templates/info.html', true);
                            // Fill out template
                            $message = str_replace("{accepted/rejected}", $status, $message);
                            $message = str_replace("{submission_date}", format_date($date_submitted), $message);

                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
                            $headers .= 'From: e-leave@company.com' . "\r\n";

                            // Send mail to employee
                            mail($email_to, $subject, $message, $headers);
                        }
                    }
                }
            }
        }
    }
} else {
    header("location: admin_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>E-Leave - Request</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="http://localhost/css/style.css">
</head>
<body style="background-color: #f4f4f4; margin: 0 !important; padding: 0 !important;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td bgcolor="#246cb4" align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td align="center" valign="top" style="padding: 40px 10px 40px 10px;"> </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#246cb4" align="center" style="padding: 0px 10px 0px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="#ffffff" align="center" valign="top" style="padding: 40px 20px 20px 20px; border-radius: 4px 4px 0px 0px; color: #111111; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 48px; font-weight: 400; letter-spacing: 4px; line-height: 48px;">
                            <h1 style="font-size: 48px; font-weight: 400; margin: 2;">E-Leave Request</h1> <img src=" http://localhost/img/company_logo.png" width="220" style="display: block; border: 0px; margin-top:30px" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f4f4f4" align="center" style="padding: 0px 10px 0px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 20px 30px 40px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400; line-height: 25px;">
                            <p style="margin: 0;">Dear supervisor, you
                            <?php
                            if ($_GET["accept"] == "true") {
                                echo 'accepted';
                            } else {
                                echo 'rejected';
                            }
                            ?> <b>
                            <?php
                              echo $_GET["f_name"] . ' ' . $_GET["l_name"];
                            ?></b>'s leave request.
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 20px 30px 40px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 400; line-height: 25px;">
                            <p style="margin: 0;">Click the link below to return to the home page:</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" align="left">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td bgcolor="#ffffff" align="center" style="padding: 20px 10px 20px 30px;">
                                        <table border="0" cellspacing="1" cellpadding="1">
                                            <tr>
                                                <td align="center" style="border-radius: 3px;" bgcolor="#ef883b"><a href="http://localhost/admin.php" target="_blank" style="font-size: 20px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; padding: 15px 25px; border-radius: 2px; border: 1px solid #ef883b; display: inline-block;">Return</a></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
