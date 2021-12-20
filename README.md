# MONITOLITE

**MonitoLite** is an old project I recently dug up from my archives. I developed this script years ago for my personal needs.
I figured it could be useful for others so I **rewrote** and **updated** it from scratch in a modern way.


## What it does

**MonitoLite** is a very simple monitoring tool developed in Perl. It supports :
 * **ping monitoring**: sends a `ping` command to the specified host. Raises an alert if the host is down
 * **http monitoring**: requests the provided URL and raises an alert if the URL returns an error. Optionally you may specify a string to search on the page using the `param` database field. It raises an alert if the specified text could not be found on the page.

 In case of an alert, the script sends an email notifications to the specified contacts (one or many).
 The script also sends a recovery email notification when the alert is over.

It uses a SQL backend for handling the tasks and the status of the tasks.
Tested on MySQL only but should support other SQL-based DBMS.

It comes with a very straightforward dashboard written in PHP. This is **optional**, the `monitolite.pl` script runs as standalone.
**Caution**: the backend is not password-protected. You should make sure you add your own security layer via IP filtering or basic authentication.


I rewrote a couple of things today to make sure the script still works.

## Screenshot

![screenshot](https://github.com/axeloz/monitolite/raw/main/screenshot.png "Logo")


## Requirements

* Perl : with DBI::MySQL, Dotenv, Net::Ping, Email::MIME, Email::Sender::Simple, Email::Sender::Transport::SMTP, LWP::Simple, LWP::UserAgent, LWP::Protocol::https
* a MTA: Postfix, ...
* PHP 7+ (optional): with PDO
* a webserver (optional): Apache, Nginx, ...
* a Database server: MySQL, other? (untested)
* access to CRON tasks
* possibly `root` access for the `ping` command to run (needs confirmation)


## Installation

 * clone this repo
 * install Perl dependencies
 * install PHP composer dependencies: `cd ./web && composer install`
 * create a Database and import the schema from `sql/create.sql`
 * create your own `.env` file: `cp env.example .env` and adapt it to your needs
 * create a webserver vhost with document root to the `public` directory
 * add tasks and contacts into the database (no backend yet)
 * run the script: `perl monitolite.pl`
 * check the web dashboard for results.
 * when everything works, you may create a CRON `* * * * * cd <change/this/to/the/correct/path> && /usr/bin/perl monitolite.pl > /dev/null`


## Settings

* DB_TYPE=mysql
* DB_HOST=127.0.0.1
* DB_USER=vagrant
* DB_PASSWORD=vagrant
* DB_NAME=monitoring
* DB_PORT=3306
* SMTP_HOST=localhost
* SMTP_USER=
* SMTP_PASSWORD=
* SMTP_PORT=80
* SMTP_SSL=1
* MAIL_FROM=axel@monitolite.fr
* NB_TRIES=3
* ARCHIVE_DAYS=10

## MORE INFORMATION COMING SOON.

## TODO

 * Make CRUD possible from the backend for adding tasks and contacts
 * Multithreading
 * SMS Notifications
 * Better dashboard
 * Protected backend with authentication
 * Create an installation script
 * Raise alert when tasks are not run at the correct frequency (CRON down or other reason)
 * Set a notification capping limit to prevent many notifications to be sent in case of an up-and-down host
 * Add a notification history log
 * Keep track of tasks response time
 * Daemonize the script (instead of CRONs)