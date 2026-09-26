<?php
// Initialize the session
session_start();

// Check if the admin is logged in, otherwise redirect to admin login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin_login.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$first_name = $last_name = $email = $password = $confirm_password = $type = "";
$first_name_err = $last_name_err = $email_err = $password_err = $confirm_password_err = $type_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate first name
    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Please enter a first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // Validate last name
    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Please enter a last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // Validate e-mail
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email address.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid e-mail address.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have atleast 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Validate type
    if (empty(trim($_POST["type"]))) {
        $type_err = "Please enter a type.";
    } else {
        $type = trim($_POST["type"]);
    }

    // Check input errors before updating the database
    if (empty($first_name_err) && empty($last_name_err) && empty($email_err) &&
    empty($password_err) && empty($confirm_password_err) && empty($type_err)) {

        // Prepare an INSERT statement
        $sql = "INSERT INTO users (first_name, last_name, email, type, password, supervisor_id) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssssi", $first_name, $last_name, $email, $type, $param_password, $_SESSION['admin_id']);

            // Set parameters
            $param_password = password_hash($password, PASSWORD_DEFAULT);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {

                // User created successfully, redirect to admin page
                header("location: ../admin.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>E-Leave - Create User</title>
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
                            <h1 style="font-size: 48px; font-weight: 400; margin: 2;">E-Leave Create User</h1> <img src="http://localhost/img/company_logo.png" width="220" height="120" style="margin-top:30px ;display: block; border: 0px;" />
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
                          <p>Please fill out this form to create a new user.</p>
                          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group <?php echo (!empty($first_name_err)) ? 'has-error' : ''; ?>">
                                <label>First Name</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo $first_name; ?>">
                                <span class="help-block"><?php echo $first_name_err; ?></span>
                            </div>
                            <div class="form-group <?php echo (!empty($last_name_err)) ? 'has-error' : ''; ?>">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo $last_name; ?>">
                                <span class="help-block"><?php echo $last_name_err; ?></span>
                            </div>
                            <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                                <label>E-Mail</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                                <span class="help-block"><?php echo $email_err; ?></span>
                            </div>
                              <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                                  <label>Password</label>
                                  <input type="password" name="password" class="form-control" value="<?php echo $password; ?>">
                                  <span class="help-block"><?php echo $password_err; ?></span>
                              </div>
                              <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                                  <label>Confirm Password</label>
                                  <input type="password" name="confirm_password" class="form-control">
                                  <span class="help-block"><?php echo $confirm_password_err; ?></span>
                              </div>
                              <div class="form-group <?php echo (!empty($type_err)) ? 'has-error' : ''; ?>">
                                  <label>User Type</label>
                                  <select name="type" class="form-control">
                                    <option disabled selected value> -- select an option -- </option>
                                    <option value="employee">Employee</option>
                                    <option value="supervisor">Admin</option>
                                  </select>
                                  <span class="help-block"><?php echo $type_err; ?></span>
                              </div>
                              <div class="form-group">
                                  <input type="submit" class="btn btn-primary" value="Submit">
                                  <a class="btn btn-link" href="http://localhost/admin.php">Cancel</a>
                              </div>
                          </form>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
