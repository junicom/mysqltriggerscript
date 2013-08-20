<?php
require 'config.php';

// Path for the forms action
$me		= basename(__FILE__);
$path	= $me . '?action=';

// Stores the posted data
$hostname		= isset($_POST['hostname']) ? $_POST['hostname'] : '';
$username		= isset($_POST['username']) ? $_POST['username'] : '';
$password		= isset($_POST['password']) ? $_POST['password'] : '';
$sourceDatabase	= isset($_POST['sourceDatabase']) ? $_POST['sourceDatabase'] : '';
$targetDatabase	= isset($_POST['targetDatabase']) ? $_POST['targetDatabase'] : '';
$newDatabase	= isset($_POST['newDatabase']) ? $_POST['newDatabase'] : '';
$port			= isset($_POST['port']) ? (int)$_POST['port'] : 0;
$action			= isset($_GET['action']) ? $_GET['action'] : 'index';

switch ($action) {

	// Select the database to trigger
	case 'selectDBs':
		$mysql = new mysqli($hostname, $username, $password, '', $port);

		// Shows the error message when connecting fails
		if ($mysql->connect_error) {
			$template = 'connectionError';

			require 'template.php';
		}

		// When everything works fine, the next form is displayed
		else {
			$result		= $mysql->query('SHOW DATABASES');
			$databases	= array();

			while ($db = $result->fetch_object()) {
				$databases[] = $db->Database;
			}

			require 'tpl/tpl.' . $action . '.php';
		}
	break;

	// Select the triggers to create
	case 'selectTrigger':
		$mysql		= new mysqli($hostname, $username, $password, $sourceDatabase, $port);
		$result		= $mysql->query('SHOW TABLES FROM `' . $sourceDatabase . '`');

		require 'tpl/tpl.' . $action . '.php';
	break;

	// Checks for conflicts with existing triggers
	case 'checkForConflicts':
		$mysql		= new mysqli($hostname, $username, $password, $sourceDatabase, $port);
		$timing		= $_POST['timing'];
		$event		= $_POST['event'];
		$tables		= explode(',', $_POST['tables']);
		$conflicts	= false;

		$result = $mysql->query("SHOW TRIGGERS FROM `" . $sourceDatabase . "`");

		while ($db = $result->fetch_object()) {

			// Conflict
			if (
				$timing == $db->Timing &&
				$event == $db->Event &&
				in_array($db->Table, $tables)
			) {
				$conflicts	= true;
				$statement	= trim(substr($db->Statement, 5, -3));

				echo '<div class="info">
					Conflict with existing trigger in table <b>' . $db->Table . '</b>!<br />
					<input name="include[' . $db->Table . ']" type="checkbox" value="1" /> Include existing statement in new trigger?<br />
					<a href="javascript:;">Show statement</a>
					<input name="statement[' . $db->Table . ']" type="hidden" value="' . base64_encode($statement) . '" />
				</div>
				<div class="statement"><textarea cols="1" rows="20">' . $statement . '</textarea></div>';
			}
		}

		if (!$conflicts) {
			echo '<div class="success">No conflicts found!</div>';
		}
	break;

	// Creates the triggers
	case 'createTriggers':
		$mysql		= new mysqli($hostname, $username, $password, $sourceDatabase, $port);
		$timing		= $_POST['timing'];
		$event		= $_POST['event'];
		$table		= $_POST['table'];
		$include	= $_POST['include'] == '1' ? true : false;
		$statement	= ($include && isset($_POST['statement'])) ? $_POST['statement'] : '';
		$fields		= $pri = array();

		if ($targetDatabase == '0') {
			$targetDatabase = $newDatabase;

			// Creates the target database, if it not exists
			$mysql->query("CREATE DATABASE IF NOT EXISTS `" . $targetDatabase . "`");
		}

		// Get columns
		$result = $mysql->query("SHOW COLUMNS FROM `" . $table . "` IN `" . $sourceDatabase . "`");

		while ($column = $result->fetch_object()) {

			// Collect all primary_key columns
			if ($column->Key == 'PRI') {
				$pri[] = "`" . $column->Field . "`=OLD.`" . $column->Field . "`";
			}

			// Add all columns to an array
			$fields[] = $column->Field;
		}

		// If at least one primary_key was found
		if (!empty($pri)) {

			// Drops existing tables
			$mysql->query("DROP TABLE IF EXISTS `" . $targetDatabase . "`.`history_" . $table . "`");

			// Creates an empty copy of the table
			// WHERE 1=2 will never be true, therefore it has no content
			$mysql->query("CREATE TABLE `" . $targetDatabase . "`.`history_" . $table . "` AS (SELECT * FROM `" . $table . "` WHERE 1=2)");

			// Adds required fields to the history table
			$mysql->query("ALTER TABLE `" . $targetDatabase . "`.`history_" . $table . "` " .
				"ADD COLUMN `revID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`revID`), " .
				"ADD COLUMN `revUser` VARCHAR(50) NULL DEFAULT NULL AFTER `revID`, " .
				"ADD COLUMN `revProcess` ENUM('" . implode($eventTypes, "','") . "') NULL DEFAULT NULL AFTER `revUser`");

			// Deletes existing triggers with the same name
			$mysql->query("DROP TRIGGER IF EXISTS `" . $table . "_" . ucfirst(strtolower($timing)) . ucfirst(strtolower($event)) . "Trigger`");

			$triggerName = $table . '_' . ucfirst(strtolower($timing)) . ucfirst(strtolower($event)) . 'Trigger';

			// Creates the trigger
			$mysql->query(
				// Sets trigger name				//
				"CREATE TRIGGER `" . $triggerName . "` " .
				// Sets timing and event
				$timing . " " . $event . " ON `" . $table . "` FOR EACH ROW BEGIN \r\n" .
				// Statement
				"\t" . base64_decode($statement) . "\r\n\r\n" .
				"\tINSERT INTO `" . $targetDatabase . "`.`history_" . $table . "` (\r\n\t\t`" . implode($fields, "`,\r\n\t\t`") . "`,\r\n\t\t`revUser`,\r\n\t\t`revProcess`\r\n\t) " .
					"SELECT\r\n\t\t`" . implode($fields, "`,\r\n\t\t`") . "`,\r\n\t\tUSER(),\r\n\t\t'" . strtolower($event) . "' " .
				"\r\n\tFROM\r\n\t\t`" . $table . "`\r\n\tWHERE\r\n\t\t" . implode(" AND ", $pri) . ";\r\nEND");

			// If creating fails, error will be displayed
			if ($mysql->error != '') {
				echo '<div class="error">Unable to create trigger for table <b>' . $table . '</b>!<br />' . $mysql->error . '</div>';
			}

			// Alternatively success message will be displayed
			else {
				echo '<div class="success">Trigger <b>' . $triggerName . '</b> successfully created!</div>';
			}
		}

		// If no primary_key exists
		else {
			echo '<div class="error">Unable to create trigger for table <b>' . $table . '</b> because of missing primary keys!</div>';
		}
	break;

	// Insert connection data
	default:
		require 'tpl/tpl.' . $action . '.php';
}