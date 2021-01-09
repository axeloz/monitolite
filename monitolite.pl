#!/usr/bin/perl

################################
#                              #
#    M O N I T O L I T E       #
#                              #
# Lightweight Monitoring Tool  #
#                              #
# @author: Axel de Vignon      #
# @copyright: www.vidax.net    #
# @license: Mozilla Public 1.1 #
#                              #
################################

use warnings;
use strict;
use DBI;
use Dotenv;
use Net::Ping;

use MIME::Lite;
use LWP::Simple;
use MIME::Base64;
use Authen::SASL;
use LWP::UserAgent;
use IO::Socket::SSL;
use LWP::Protocol::https;

my $query;
my $result;
my $tasks;
my $update_query;
my $emails;
my $email;
my $message;
my $response;
my $html;
my $numtasks;
my $previous_status;
my $subject;
my $datas;


############################
#                          #
#  S  E  T  T  I  N  G  S  #
#                          #
############################

Dotenv->load;

my $dbtype 				= $ENV{'DB_TYPE'};
my $hostname 				= $ENV{'DB_HOST'};
my $database 				= $ENV{'DB_NAME'};
my $login 				= $ENV{'DB_USER'};
my $port 				= $ENV{'DB_PORT'};
my $password 				= $ENV{'DB_PASSWORD'};
my $email_from 				= $ENV{'MAIL_FROM'};
my $number_tries 			= $ENV{'NB_TRIES'};
my $days_history_archive 		= $ENV{'ARCHIVE_DAYS'};
my $smtp_host				= $ENV{'SMTP_HOST'};
my $smtp_user				= $ENV{'SMTP_USER'};
my $smtp_password			= $ENV{'SMTP_PASSWORD'};
my $smtp_port				= $ENV{'SMTP_PORT'};
my $smtp_ssl				= $ENV{'SMTP_SSL'};


############################

######
# Testing database connection 
######
my $dsn = "DBI:$dbtype:database=$database;host=$hostname;port=$port";
my $dbh = DBI->connect($dsn, $login, $password) or  output('cannot connect to database', 'ERROR', 1);


######
# Getting tasks
######
my $execution_time = server_time();
my $query1 = $dbh->prepare('SELECT id, host, type, params FROM tasks WHERE ( DATE_SUB(now(), INTERVAL frequency SECOND) > last_execution OR last_execution IS NULL ) AND active = 1');
$query1->execute() or output('Cannot execute query fetching all pending tasks', 'ERROR', 1);
$numtasks = $query1->rows;

#####
# Processing all tasks
#####
if ($numtasks > 0) {
	while ($tasks = $query1->fetchrow_hashref()) {
		print "\n";
		my $status = -1;
		$previous_status = -1;
		$message = 'Host is back up';
		
		####
		# Getting last history for this host
		####
		my $query2 = $dbh->prepare('SELECT status FROM tasks_history WHERE task_id = ' . $tasks->{'id'} . ' ORDER BY datetime DESC LIMIT 1');
		$query2->execute() or output('Cannot get history for this task', 'ERROR', 0);
		
		if ($query2->rows > 0) {
			my $history = $query2->fetchrow_hashref();
			$previous_status = $history->{'status'};
		}
	
		if ($tasks->{'type'} =~ 'ping') {
		
			# Ping check returned an error
			if (! check_ping($tasks->{'host'})) {
				$status = 0;
				output('Host "'. $tasks->{'host'} .'" [' . $tasks->{'type'} . '] is down', 'ALERT');
				$message = 'Host does not reply to ping. Timed out after 5s. Giving up...';
				
			}
			# Ping check went fine 
			else {
				$status = 1;
				output('Host "'. $tasks->{'host'} .'" [' . $tasks->{'type'} . '] is up', 'SUCCESS');
			}
		}
		elsif ($tasks->{'type'} =~ 'http') {
			$response = check_http($tasks->{'host'}, $tasks->{'params'});
			
			# HTTP check went fine
			if ($response =~ 'OK') {
				$status = 1;
				output('Host "'. $tasks->{'host'} .'" [' . $tasks->{'type'} . '] is up', 'SUCCESS');
			} 
			# HTTP check returned an error
			else {
				$status = 0;
				output('Host "'. $tasks->{'host'} .'" [' . $tasks->{'type'} . '] is down', 'ALERT');
				$message = 'HTTP response was: ' . $response;
			}		
		}
		else {
			output('dunno how to process this task', 'DEBUG');
			next;
		}
		
		# Notify on status changes only
		if ($previous_status != -1 && $status != $previous_status) {
			output('Should send notification', 'DEBUG');
			&send_notifications($tasks->{'id'}, $tasks->{'host'}, $tasks->{'type'}, $message, $status);
		}
	
		# Saving Status into DB
		if ($status >= 0) {
			save_history($tasks->{'id'}, $status, $execution_time);
		}


	}
}
else {
	output('nothing to monitor, sleeping back', 'DEBUG');
}



#####
# Function used for the PING test
#####
sub check_ping {
	my ($host, $round) = @_;
	$round = 1 if (! $round);
	
	my $ping = Net::Ping->new('icmp');
	output('ping check n°' . $round . ' on ' . $host, 'DEBUG');
	
	if (! $ping->ping($host)) {
		$ping->close();
		
		if ($number_tries && $round <= $number_tries) {
			sleep (2);
			return check_ping($host, $round + 1)
		} 
		else {
			return undef;
		}

	} else {
		$ping->close();
		return 'OK';
	}	
}

#####
# Function used to check HTTP service
#####
sub check_http {
	my ($host, $find, $round) = @_;
	$round = 1 if (! $round);

	$host = 'http://'.$host if ($host !~ m/^http/i);
	
	my $check = LWP::UserAgent->new(
		ssl_opts => { verify_hostname => 0 },
		protocols_allowed => ['http', 'https']
	);
	$check->timeout(5);
	$check->env_proxy;

	my $response = $check->get($host, ':content_cb' => \&process_data);
	output('http check n°' . $round . ' on ' . $host, 'DEBUG');

	if ($response->is_success) {

		if ($find && length($find) > 0) {
			output('searching "' . $find . '" into html content on ' . $host, 'DEBUG');

			if ($html =~ m/$find/i) {
				output('html content found, looks fine', 'SUCCESS');
				return 'OK';
			} 
			else {
				output('html content not found', 'ERROR');
				return 'Could not find "' . $find . '" into the page';
			}
		} 
		else {
			return 'OK';		
		}
		
	}
	else {
		output('HTTP response error was: '.$response->status_line, 'DEBUG');
		if ($number_tries && $round < $number_tries) {
			sleep (2);
			return check_http($host, $find, $round + 1);
		}
		else {
			return $response->status_line;	
		} 
	}	
}


#####
# Save the page HTML content
#####
sub process_data {
	my ($content, $handler1, $handler2) = @_;
	$html .= $content;
}

#####
# Function managing DEBUG and OUTPUT
#####
sub output {
	my ($output, $level, $fatal) = @_;
	$output = server_time().' - '.$level.' - '.$output."\n";
	
	if ($fatal && $fatal == 1) {
		die ('FATAL '.$output);
	}
	else {
		print ($output);
	}
	return 1;
}

#####
# Function that keeps an history
#####
sub save_history {
	my ($task_id, $status, $datetime) = @_;
	my $query = $dbh->prepare('INSERT INTO tasks_history (status, datetime, task_id) VALUES(' . $status . ', "'.$datetime.'", ' . $task_id . ')');
	if ($query->execute()) {
		output('saving status to history', 'DEBUG');
	}
	else {
		output('cannot save status to history', 'ERROR');
	}
	
	$update_query = $dbh->prepare('UPDATE tasks SET last_execution = "'.$datetime.'" WHERE id = ' . $task_id);
	if ($update_query->execute()) {
		output('saving last execution time for this task', 'DEBUG');
	}
	else {
		output('cannot save last execution time for this task', 'ERROR');
	}
	return 1;
}

#####
# Function sending notifications
#####
sub send_notifications {
	my ($task_id, $host, $type, $message, $status) = @_;
	
	if ($status == 0) {
		$subject = 'ALERT: host "' . $host . '" [' . $type . '] is down';
		$datas = "------ ALERT DETECTED BY MONITORING SERVICE ------ \n\n\nDATETIME: " . server_time() . "(server time)\nHOST: " . $host . "\nSERVICE: " . $type . "\nMESSAGE: " . $message;
	}
	else {
		$subject = 'RECOVERY: host "' . $host . '" [' . $type . '] is up';
		$datas = "------ RECOVERY DETECTED BY MONITORING SERVICE ------ \n\n\nDATETIME: " . server_time() . "(server time)\nHOST: " . $host . "\nSERVICE: " . $type . "\nMESSAGE: " . $message;	
	}
	
	my $query = $dbh->prepare('SELECT c.email FROM contacts as c JOIN notifications as n ON (n.contact_id = c.id) WHERE c.active = 1 AND n.task_id = '.$task_id);
	if ($query->execute()) {
		while ($emails = $query->fetchrow_hashref()) {

			my $email = new MIME::Lite
			  		From 	=> 'axel@mabox.eu',
					To	=> 'axel@mabox.eu',
					Subject	=> 'Bla',
					Type 	=> 'TEXT',
					Data	=> 'Hello';
			eval {
				$email->send('smtp', $smtp_host, Timeout=>5, Auth=>'LOGIN', AuthUser=>$smtp_user, AuthPass=>$smtp_password, Port=>$smtp_port, SSL=>$smtp_ssl, Debug=>0) or output('failed to send notification to ' . $emails->{'email'} . ' (' . $email->error() . ')', 'ERROR');;
			};
			warn() if $@;
		}
		return 1
	}
	output('failed to send notifications', 'ERROR');
	return undef;
}


#####
# Function getting datetime
#####
sub server_time {
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	my $now = (1900 + $year).'-'.($mon + 1).'-'.$mday.' '.$hour.':'.$min.':00';	
	return $now;
}
