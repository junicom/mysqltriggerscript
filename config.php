<?php
// Fehlerausgabe
error_reporting(-1);
ini_set('display_errors', true);

// Mögliche Event-Typen
// Nicht unterstütze Typen können einfach auskommentiert werden
$eventTypes = array(
//	'insert', // Derzeit nicht anwendbar
	'update',
	'delete'
);

// Standard-Verbindungsdaten
$stdConnection = array(
	'hostname'	=> 'localhost',
	'username'	=> 'root',
	'password'	=> '',
	'port'		=> 3306
);
