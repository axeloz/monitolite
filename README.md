# MONITOLITE

MonitoLite is a quite old project I recently found into my archives. I developed this script years ago. 
I figured it could be useful for others so here we are.


## What it does

MonitoLite is a very simple monitoring tool developed in Perl. It supports : 
 * ping monitoring
 * http monitoring
 
It uses a SQL backend for handling the tasks and the status of the tasks. 
Tested on MySQL only. 

It comes with a very straightforward dashboard written in PHP. 

I rewrote a couple of things today to make sure the script still works. 

## Screenshot 

![screenshot](https://github.com/axeloz/monitolite/raw/main/screenshot.png "Logo")


## Requirements

* Perl 
* a MTA 
* PHP (with PDO)
* a Database server (MySQL, other?)
* Access to CRON tasks


## Installation

 * clone this repo
 * install Perl dependencies
 * install PHP composer dependencies: `cd ./web && composer install`
 * create a Database and import the schema from `sql/create.sql`
 * add tasks and contacts into the database (no backend yet)
 * run the script: `perl monitolite.pl` 
 * check the web dashboard for results. 
 * when everything works, you may create a CRON `* * * * * cd <change/this/to/the/correct/path> && /usr/bin/perl monitolite.pl > /dev/null`
 
 
## MORE INFORMATION COMING SOON. 

## TODO

 * Make CRUD possible from the backend
 * Multithreading
 * SMS Notifications
