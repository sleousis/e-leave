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
* [Upgrade notes](#upgrade-notes)
* [Notes and known limitations](#notes-and-known-limitations)
* [Author](#author)

## Features

* Employee login and a dashboard with all own requests and their status.
* Submit a leave request. The number of working days skips weekends, a fixed list of public holidays and Easter Monday.
* The supervisor gets an HTML e-mail with Accept and Reject links.
* The employee gets an HTML e-mail with the result.
* Admin login, list of users, create user and edit user.

## Tech stack

* PHP 8.5.11 with the `mysqli` and `calendar` extensions
* MySQL 26.7.0
* Bootstrap 3.4.1 (loaded from a CDN)
* Docker Compose with four services:
  * `php`: Apache with PHP, built from `Dockerfile` on `php:8.5.11-apache`, port 80
  * `db`: `mysql:26.7.0`, port 3306
  * `mailpit`: `axllent/mailpit:v1.31.2`, catches all outgoing mail, web UI on port 8025
  * `phpmyadmin`: `phpmyadmin:5.2.3-apache`, port 8080

The app was written in 2020 for PHP 7.1, MySQL 8.0 and MailHog. In 2026 it was moved to the versions above. See [Upgrade notes](#upgrade-notes).

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
Dockerfile             PHP and Apache image with the Mailpit sendmail command
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
* http://localhost:8025 for Mailpit, where all sent e-mails show up
* http://localhost:8080 for phpMyAdmin (user `root`, password `root`)

Stop everything with `docker compose down`. Other useful commands are `docker compose pause`, `docker compose unpause` and `docker compose restart`.

The Docker setup was not run during the 2026 maintenance because Docker was not available. The same versions of PHP, MySQL and Mailpit were tested without Docker as described below.

## Run without Docker

This is how the app was verified in 2026 on Ubuntu 20.04 (WSL). On Windows, use WSL. You need:

* PHP 8.5 with the `mysqli`, `calendar`, `session`, `filter` and `openssl` extensions. The test used PHP 8.5.11 built from the php.net source tarball with `./configure --disable-all --enable-cli --enable-session --enable-filter --enable-calendar --enable-mysqlnd --with-mysqli=mysqlnd --with-openssl --enable-ctype --enable-tokenizer`.
* MySQL 26.7. The test used the official `mysql-26.7.0-linux-glibc2.28-x86_64-minimal.tar.xz` from dev.mysql.com.
* Mailpit 1.31 for e-mail (optional). The test used `mailpit-linux-amd64.tar.gz` v1.31.2 from its GitHub releases.

Steps:

1. Create the database and import the dump:

   ```
   mysql -uroot -p -e "CREATE DATABASE eleave"
   mysql -uroot -p eleave < database/eleave.sql
   ```

2. The app logs in as `root` with password `root` over TCP, like in the Docker setup. Create or change the MySQL user to match, or edit `pages/config.php`.

3. `pages/config.php` connects to the host `db`, which is the Docker service name. Either add `127.0.0.1 db` to `/etc/hosts` or change `DB_SERVER` to `127.0.0.1`.

4. Start Mailpit, then start the PHP built-in server from the repository root with Mailpit as the sendmail command:

   ```
   mailpit --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 &
   php -d "sendmail_path=mailpit sendmail -S 127.0.0.1:1025" -S 127.0.0.1:8095
   ```

   The pages load CSS, the logo and some links from `http://localhost`. For normal browser use serve the app on port 80 of `localhost` (for example `sudo php -S localhost:80`). This was not tried. The check below used other ports and curl only.

### What was verified

The test used PHP 8.5.11 with `error_reporting=-1`, MySQL 26.7.0 with its default `caching_sha2_password` login and Mailpit 1.31.2. With the dump imported and the server running, curl went through every page:

```
GET  /                                  302 to pages/user_login.php
POST pages/user_login.php               302 to index.php (employee@company.com / password)
GET  /                                  200, "Welcome to e-Leave, John Doe" and the request table
POST pages/user_submit_request.php      302, new row with 5 requested days, status pending, e-mail in Mailpit
POST pages/admin_login.php              302 to admin.php (admin@company.com / password)
GET  admin.php                          200, list of users
GET  pages/admin_handle_request.php     200, request status changed to accepted, e-mail in Mailpit
POST pages/admin_create_user.php        302, new user row created
POST pages/admin_edit_user.php          302
GET  pages/user_logout.php              302 to the login page
GET  pages/admin_logout.php             302 to the login page
```

`php -l` reports no syntax errors in any file. The PHP error log stayed empty, so there were no errors, warnings, notices or deprecations.

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

## Upgrade notes

In 2026 the project was moved to the latest stable versions. Old and new versions:

| Part         | 2020                     | 2026                        |
|--------------|--------------------------|-----------------------------|
| PHP          | 7.1.33 (`php:7.1.33-apache`) | 8.5.11 (`php:8.5.11-apache`) |
| MySQL        | 8.0 (`mysql:8.0`)        | 26.7.0 (`mysql:26.7.0`)     |
| Mail catcher | MailHog 1.0.0            | Mailpit 1.31.2              |
| sendmail     | mhsendmail built with Go 1.8.3 | `mailpit sendmail` copied from the Mailpit image |
| phpMyAdmin   | `phpmyadmin/phpmyadmin` (untagged) | `phpmyadmin:5.2.3-apache` |
| Bootstrap    | 3.3.7                    | 3.4.1                       |

Changes that were needed:

* PHP 8.1 and later make mysqli throw exceptions. `pages/config.php` now calls `mysqli_report(MYSQLI_REPORT_OFF)` so errors are handled by the existing checks as before.
* The two login pages called `session_start()` a second time after a correct password. The extra call is removed.
* The date field on the request form printed an undefined variable (`$new_password`). It now prints `$date_from`, which is empty on a new form.
* MySQL 9 and later removed `mysql_native_password`, so the `--default-authentication-plugin` option is gone from `docker-compose.yaml`. PHP 8 supports the default `caching_sha2_password` login.
* The MySQL image refuses `MYSQL_USER: root`, so that line is removed. The root password is still set by `MYSQL_ROOT_PASSWORD`.
* MailHog is no longer maintained. Mailpit is its drop-in successor with the same ports 1025 and 8025. Its binary also works as a sendmail command, so the Go build of mhsendmail is gone.
* The obsolete `version` key is removed from `docker-compose.yaml`.

Bootstrap stays on 3.x. Version 3.4.1 is the last 3.x release and a drop-in update. Bootstrap 4 and 5 removed classes the forms use, such as `has-error` and `help-block`. Moving to them would mean restyling every page.

## Notes and known limitations

* A full `docker compose build` was not run because Docker was not available. The image tags were checked on Docker Hub. PHP, MySQL and Mailpit were tested at the same versions outside Docker.
* The database login (`root` / `root`) and the demo passwords are development defaults committed to the repository. Do not use this setup as is on a public server.
* URLs to `http://localhost` are hard-coded in the pages and e-mails. The app only looks right when served at http://localhost on port 80.
* The screenshots are from 2020 and show MailHog. Mailpit looks different but shows the same e-mails.
* The public holiday list in `lib/get_workdays.php` is fixed and follows the Italian calendar.

## Author

Savvas Leousis
