# E-Leave Tool

E-Leave is a small leave management web app written in PHP in April 2020. Employees log in and submit leave requests. The request is e-mailed to their supervisor, who accepts or rejects it with one click. The employee then gets an e-mail with the outcome. Administrators (supervisors) have their own dashboard where they can create and edit users.

## Contents

* [Features](#features)
* [Tech stack](#tech-stack)
* [Repository layout](#repository-layout)
* [Run with Docker](#run-with-docker)
* [Run without Docker](#run-without-docker)
* [Demo accounts](#demo-accounts)
* [Example navigation](#example-navigation)
* [Database schema](#database-schema)
* [Notes and known limitations](#notes-and-known-limitations)
* [Author](#author)

## Features

* Employee login and a dashboard with all own requests and their status.
* Submit a leave request. The number of working days skips weekends, a fixed list of public holidays and Easter Monday.
* The supervisor gets an HTML e-mail with Accept and Reject links.
* The employee gets an HTML e-mail with the result.
* Admin login, list of users, create user and edit user.

## Tech stack

* PHP 7 with the `mysqli` and `calendar` extensions
* MySQL 8.0
* Bootstrap 3 (loaded from a CDN)
* Docker Compose with four services:
  * `php`: Apache with PHP, built from `Dockerfile` on `php:7.1.33-apache`, port 80
  * `db`: `mysql:8.0`, port 3306
  * `mailhog`: `mailhog/mailhog:v1.0.0`, catches all outgoing mail, web UI on port 8025
  * `phpmyadmin`: `phpmyadmin/phpmyadmin`, port 8080

## Repository layout

```
admin.php              Admin dashboard (list of users)
index.php              Employee dashboard (list of own requests)
pages/                 Other pages (login, logout, submit request, handle request, create and edit user)
pages/config.php       Database connection settings
lib/                   Shared helper functions (date format, status colors, working days)
templates/             HTML e-mail templates for requests and results
css/, img/             Stylesheet and logo
database/eleave.sql    Database dump with the schema and demo data
database/database.mwb  MySQL Workbench model (database.png is the EER diagram)
sample nagivation/     Screenshots of the three use cases below
Dockerfile             PHP and Apache image with mhsendmail for MailHog
docker-compose.yaml    The four services
```

## Run with Docker

Prerequisites: Docker Engine and Docker Compose.

```
git clone https://github.com/sleousis/e-leave.git
cd e-leave
docker compose up -d
```

On older Docker installs the command is `docker-compose up -d`. The database container imports `database/eleave.sql` on its first start.

Then open:

* http://localhost for the employee pages
* http://localhost/admin.php for the admin pages
* http://localhost:8025 for MailHog, where all sent e-mails show up
* http://localhost:8080 for phpMyAdmin (user `root`, password `root`)

Stop everything with `docker compose down`. Other useful commands are `docker compose pause`, `docker compose unpause` and `docker compose restart`.

The Docker setup was not run during the 2026 maintenance because Docker was not available. Its two broken steps were fixed and checked on their own. See the notes below.

## Run without Docker

This is how the app was verified in 2026 on Ubuntu 20.04 (WSL) with PHP 7.4.3 and MySQL 8.0.42. On Windows, use WSL.

1. Install the packages:

   ```
   sudo apt-get install php-cli php-mysql mysql-server
   ```

2. Create the database and import the dump:

   ```
   sudo mysql -e "CREATE DATABASE eleave"
   sudo mysql eleave < database/eleave.sql
   ```

3. The app logs in as `root` with password `root` over TCP, like in the Docker setup. Create or change the MySQL user to match, or edit `pages/config.php`.

4. `pages/config.php` connects to the host `db`, which is the Docker service name. Either add `127.0.0.1 db` to `/etc/hosts` or change `DB_SERVER` to `127.0.0.1`.

5. Start the PHP built-in server from the repository root:

   ```
   php -S 127.0.0.1:8095
   ```

   The pages load CSS, the logo and some links from `http://localhost`. For normal browser use serve the app on port 80 of `localhost` (for example `sudo php -S localhost:80`). This was not tried. The check below used port 8095 with curl only.

Outgoing e-mail uses PHP `mail()`. Without MailHog you need a working `sendmail_path`. For the test run it was set to a command that appends the mails to a file.

### What was verified

With the dump imported and the server running, curl went through every page:

```
GET  /                                  302 to pages/user_login.php
POST pages/user_login.php               302 to index.php (employee@company.com / password)
GET  /                                  200, "Welcome to e-Leave, John Doe" and the request table
POST pages/user_submit_request.php      302, new row with 5 requested days, status pending, e-mail sent
POST pages/admin_login.php              302 to admin.php (admin@company.com / password)
GET  admin.php                          200, list of users
GET  pages/admin_handle_request.php     200, request status changed to accepted
POST pages/admin_create_user.php        302, new user row created
POST pages/admin_edit_user.php          302
GET  pages/user_logout.php              302 to the login page
GET  pages/admin_logout.php             302 to the login page
```

`php -l` reports no syntax errors in any file.

## Demo accounts

The dump contains two users. Both have the password `password`.

| Role     | E-mail               |
|----------|----------------------|
| Employee | employee@company.com |
| Admin    | admin@company.com    |

## Example navigation

### Use Case 1 (Employee): Submit a request

The employee logs in at http://localhost (e-mail `employee@company.com`, password `password`).

![1](sample%20nagivation/Use%20Case%201/1%20-%20E-Leave%20-%20Login.png)

The employee sees the main dashboard.

![2](sample%20nagivation/Use%20Case%201/2%20-%20E-Leave%20-%20Dashboard%20-%20Empty.png)

The employee clicks `Submit Request` and fills in all fields.

![3](sample%20nagivation/Use%20Case%201/3%20-%20E-Leave%20-%20Submit%20Request.png)

The new request appears on the dashboard with the status `Pending`.

![4](sample%20nagivation/Use%20Case%201/4%20-%20E-Leave%20-%20Dashboard.png)

The supervisor receives an e-mail about the request.

![5](sample%20nagivation/Use%20Case%201/5%20-%20MailHog.png)

The supervisor clicks `Accept` or `Reject`.

![6](sample%20nagivation/Use%20Case%201/6%20-%20Request%20accepted.png)

The employee receives an e-mail with the outcome.

![7](sample%20nagivation/Use%20Case%201/7%20-%20MailHog%20-%20Accepted.png)

Back on the dashboard, the status of the request is updated.

![8](sample%20nagivation/Use%20Case%201/8%20-%20E-Leave%20-%20Dashboard%20-%20Accepted.png)

### Use Case 2 (Admin): Create a user

The administrator logs in at http://localhost/admin.php (e-mail `admin@company.com`, password `password`).

![1](sample%20nagivation/Use%20Case%202/1-%20E-Leave%20-%20Admin%20Login.png)

The administrator sees the admin dashboard.

![2](sample%20nagivation/Use%20Case%202/2%20-%20E-Leave%20-%20Admin%20Dashboard.png)

The administrator clicks `Create User` and fills in all fields.

![3](sample%20nagivation/Use%20Case%202/3%20-%20E-Leave%20-%20Create%20User.png)

The new user appears on the admin dashboard.

![4](sample%20nagivation/Use%20Case%202/4%20-%20E-Leave%20-%20Admin%20Dashboard%202.png)

### Use Case 3 (Admin): Edit a user

The administrator logs in at http://localhost/admin.php.

![1](sample%20nagivation/Use%20Case%203/1-%20E-Leave%20-%20Admin%20Login.png)

The administrator sees the admin dashboard.

![2](sample%20nagivation/Use%20Case%203/2%20-%20E-Leave%20-%20Admin%20Dashboard%202.png)

The administrator clicks the row of the user to edit and fills in all fields.

![3](sample%20nagivation/Use%20Case%203/3%20-%20E-Leave%20-%20Edit%20User.png)

The edited user appears on the admin dashboard.

![4](sample%20nagivation/Use%20Case%203/4%20-%20E-Leave%20-%20Admin%20Dashboard%203.png)

## Database schema

The `eleave` database has two tables, `users` and `applications`. The EER diagram:

![database](database/database.png)

## Notes and known limitations

* The base image `php:7.1.33-apache` is Debian 10 (buster). Its package mirrors moved to archive.debian.org, so the `Dockerfile` now points apt there. The old Go download URL on storage.googleapis.com returns 403, so it now uses dl.google.com. Both steps were checked outside Docker. `apt-get update` and the package install succeed in the image's own base filesystem, and Go 1.8.3 still builds `mhsendmail`. A full `docker compose build` was not run.
* The database login (`root` / `root`) and the demo passwords are development defaults committed to the repository. Do not use this setup as is on a public server.
* URLs to `http://localhost` are hard-coded in the pages and e-mails. The app only looks right when served at http://localhost on port 80.
* With all PHP notices shown, PHP 7.4 prints three notices. The two login pages call `session_start()` twice and the request form reads an undefined variable. They do not stop anything from working. The Docker image uses PHP defaults, which hide notices.
* The public holiday list in `lib/get_workdays.php` is fixed and follows the Italian calendar.

## Author

Savvas Leousis
