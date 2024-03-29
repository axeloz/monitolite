# MONITOLITE

**MonitoLite** is an old project I recently dug up from my archives. I developed this script years ago for my personal needs.
I figured it could be useful for others so I **rewrote** and **updated** it from scratch in a modern way.


## What it does

**MonitoLite** is a very simple monitoring tool developed in PHP powered by Lumen (by Laravel). It supports :
 * **PING monitoring**: sends a `ping` command to the specified host. Raises an alert if the host is down
 * **HTTP monitoring**: requests the provided URL and raises an alert if the URL returns an error. Optionally you may specify a string to search on the page using the `param` database field. It raises an alert if the specified text could not be found on the page.
 * **FTP monitoring**: connects to the provided FTP server as anonymous (authentication not supported yet).
 * **DNS monitoring**: runs a DNS lookup on a given DNS server for the hostname specified in the params

 In case of an alert, the script sends an email notifications to the specified contacts (one or many).
 The script also sends a recovery email notification when the alert is over.

It uses a SQL backend for handling the tasks and the status of the tasks.
Tested on MySQL only but should support other SQL-based DBMS.

It comes with a very straightforward dashboard written in PHP. This is **optional**, the monitoring script runs as standalone.
**Caution**: the backend is not password-protected. You should make sure you add your own security layer via IP filtering or basic authentication.

## Demo

[DEMO](https://monitolite.mabox.eu)

## Screenshot

### Tasks list with quick preview

![screenshot](https://github.com/axeloz/monitolite/raw/main/screenshot.png "Logo")

### Task details with graph and history

![screenshot](https://github.com/axeloz/monitolite/raw/main/screenshot2.png "Logo")


## Requirements

* PHP 7+ with cURL, `exec` command allowed, MySQL extension via PDO
* a MTA: Postfix, or an external SMTP ...
* a webserver (optional): Apache, Nginx, ...
* a Database server: MySQL, other? (untested)
* access to CRON tasks

## Installation

 * clone this repo
 * install PHP composer dependencies: `cd ./web && composer install`
 * create a Database and import the initial schema using `php artisan migrate`
 * create your own `.env` file: `cp .env.example .env` and adapt it to your needs
 * create a webserver vhost with document root to the `public` directory
 * add tasks and contacts into the database (no GUI for CRUD yet)
 * run the script: `cd /var/www/<your-path> && php artisan monitolite:run`
 * check the output of the command for results.
 * if everything works, you may create a CRON `* * * * * cd /var/www/<your-path> && php artisan monitolite:run > /dev/null`


## Settings

* APP_NAME=Monitolite
* APP_ENV=production
* APP_KEY=<GENERATE KEY HERE>
* APP_DEBUG=false
* APP_URL=http://localhost
* APP_TIMEZONE=UTC
* DB_TIMEZONE="+1:00"
* DB_CONNECTION=mysql
* DB_HOST=127.0.0.1
* DB_PORT=3306
* DB_DATABASE=homestead
* DB_USERNAME=homestead
* DB_PASSWORD=secret
* MAIL_MAILER=smtp
* MAIL_HOST=localhost
* MAIL_PORT=25
* MAIL_USERNAME=
* MAIL_PASSWORD=
* MAIL_ENCRYPTION=
* MAIL_FROM_ADDRESS=noreply@monitolite.fr
* MAIL_FROM_NAME="Monitolite"
* NB_TRIES=3
* ARCHIVE_DAYS=10


## TODO

 [ ] Make CRUD possible from the backend for adding tasks and contacts
 [ ] Multithreading
 [ ] SMS Notifications
 [ ] Protected backend with authentication
 [ ] Create an installation script
 [ ] Raise alert when tasks are not run at the correct frequency (CRON down or other reason)
 [x] Set a notification capping limit to prevent many notifications to be sent in case of an up-and-down host
 [x] Add a notification history log
 [x] Keep track of tasks response time
 [ ] Daemonize the script (instead of CRONs)
