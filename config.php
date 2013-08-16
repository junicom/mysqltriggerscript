<?php
// Error reporting
error_reporting(-1);
ini_set('display_errors', true);

// Available event types
// Just comment out not supported types to disable them
$eventTypes = array(
	'update',
	'delete'
);

// Default connection data
$stdConnection = array(
	'hostname'	=> 'localhost',
	'username'	=> 'root',
	'password'	=> '',
	'port'		=> 3306
);
