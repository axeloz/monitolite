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


## Installation

 * clone this repo
 * install Perl dependencies
 * install PHP composer dependencies: `cd ./web && composer install`
 * add tasks and contacts into the database (no backend yet)
 * run the script: `perl monitolite.pl` 
 * check the web dashboard for results. 
 
 
 MORE INFORMATIN COMING SOON. 
