<?php
require 'config.php';

// Pfad für die Formulare
$me		= basename(__FILE__);
$path	= $me . '?action=';

// Speichert die per Formular übertragenen Zugangsdaten
$hostname		= isset($_POST['hostname']) ? $_POST['hostname'] : '';
$username		= isset($_POST['username']) ? $_POST['username'] : '';
$password		= isset($_POST['password']) ? $_POST['password'] : '';
$sourceDatabase	= isset($_POST['sourceDatabase']) ? $_POST['sourceDatabase'] : '';
$targetDatabase	= isset($_POST['targetDatabase']) ? $_POST['targetDatabase'] : '';
$newDatabase	= isset($_POST['newDatabase']) ? $_POST['newDatabase'] : '';
$port			= isset($_POST['port']) ? (int)$_POST['port'] : 0;
$action			= isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

	// Zeigt die Wahl der Datenbank an
	case 'selectDBs':
		$mysql = new mysqli($hostname, $username, $password, '', $port);

		// Liefert eine Meldung aus, wenn die Verbindung fehlschlägt
		if ($mysql->connect_error) {
			$template = 'connectionError';

			require 'template.php';
		}

		// Wenn alles geklappt hat, wird das Formular ausgeliefert
		else {
			$result		= $mysql->query('SHOW DATABASES');
			$databases	= array();

			while ($db = $result->fetch_object()) {
				$databases[] = $db->Database;
			}

			require 'template.php';
		}
	break;

	// Zeigt die Auswahl des Triggers an
	case 'selectTrigger':
		$mysql		= new mysqli($hostname, $username, $password, $sourceDatabase, $port);
		$result		= $mysql->query('SHOW TABLES FROM `' . $sourceDatabase . '`');

		require 'template.php';
	break;

	// Erstellt die Trigger
	case 'createTrigger':
		$mysql	= new mysqli($hostname, $username, $password, $sourceDatabase, $port);
		$time	= $_POST['time'];
		$event	= $_POST['event'];
		$table	= $_POST['table'];
		$fields	= $pri = array();

		if ($targetDatabase == '0') {
			$targetDatabase = $newDatabase;

			// Erstellt die Zieldatenbank, falls diese nicht existiert
			$mysql->query("CREATE DATABASE IF NOT EXISTS `" . $targetDatabase . "`");
		}

		// Fragt die Spalten der Tabelle ab
		$result = $mysql->query("SHOW COLUMNS FROM `" . $table . "` IN `" . $sourceDatabase . "`");

		while ($column = $result->fetch_object()) {

			// Sammelt alle PrimaryKey-Spalten
			if ($column->Key == 'PRI') {
				$pri[] = "`" . $column->Field . "`=OLD.`" . $column->Field . "`";
			}

			// Hängt alle Spalten der Tabelle an ein Array
			$fields[] = $column->Field;
		}

		// Wenn mindestens ein PrimaryKey gefunden wurde
		if (!empty($pri)) {

			// Löscht eventuell vorhandene History-Tabellen
			$mysql->query("DROP TABLE IF EXISTS `" . $targetDatabase . "`.`history_" . $table . "`");

			// Erstellt eine leere Kopie der Tabelle
			// WHERE 1=2 wird nicht true, daher wird kein Inhalt übernommen
			$mysql->query("CREATE TABLE `" . $targetDatabase . "`.`history_" . $table . "` AS (SELECT * FROM `" . $table . "` WHERE 1=2)");

			// Fügt bei der history-Tabelle die drei Felder revID, revUser
			// und revProcess hinzu
			$mysql->query("ALTER TABLE `" . $targetDatabase . "`.`history_" . $table . "` " .
				"ADD COLUMN `revID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`revID`), " .
				"ADD COLUMN `revUser` VARCHAR(50) NULL DEFAULT NULL AFTER `revID`, " .
				"ADD COLUMN `revProcess` ENUM('" . implode($eventTypes, "','") . "') NULL DEFAULT NULL AFTER `revUser`");

			// Löscht eventuell vorhandene Trigger
			$mysql->query("DROP TRIGGER IF EXISTS `" . $table . "_" . ucfirst(strtolower($time)) . ucfirst(strtolower($event)) . "Trigger`");

			$triggerName = $table . '_' . ucfirst(strtolower($time)) . ucfirst(strtolower($event)) . 'Trigger';

			// Erstellt den Trigger
			$mysql->query(
				// Setzt den Definer
				//"CREATE DEFINER=`" . $username . "`@`" . $hostname . "` " .
				// Setzt den Triggernamen
				"CREATE TRIGGER `" . $triggerName . "` " .
				// Setzt den Zeitpunkt und den Event
				$time . " " . $event . " ON `" . $table . "` FOR EACH ROW BEGIN \r\n" .
				// Ereignis
				"\tINSERT INTO `" . $targetDatabase . "`.`history_" . $table . "` (\r\n\t\t`" . implode($fields, "`,\r\n\t\t`") . "`,\r\n\t\t`revUser`,\r\n\t\t`revProcess`\r\n\t) " .
					"SELECT\r\n\t\t`" . implode($fields, "`,\r\n\t\t`") . "`,\r\n\t\tUSER(),\r\n\t\t'" . strtolower($event) . "' " .
				"\r\n\tFROM\r\n\t\t`" . $table . "`\r\n\tWHERE\r\n\t\t" . implode(" AND ", $pri) . ";\r\nEND");

			// Wenn die Erstellung des Triggers einen Fehler wirft, wird dieser
			// ausgegeben
			if ($mysql->error != '') {
				echo '<div id="error">Erstellen des Triggers f&uuml;r die Tabelle <b>' . $table . '</b> fehlgeschlagen!<br /><br />' . $mysql->error . '</div>';
			}

			// Ansonsten wird eine Erfolgsnachricht ausgegeben
			else {
				echo '<div id="success">Trigger <b>"' . $triggerName . '"</b> f&uuml;r die Tabelle <b>' . $table . '</b> erfolgreich erstellt!</div>';
			}
		}

		// Wenn KEIN PrimaryKey gefunden wurde
		// TODO: Später Möglichkeit einbauen, selbst ein Bezugsfeld zu bestimmen
		else {
			echo '<div id="error">Trigger f&uuml;r die Tabelle <b>' . $table . '</b> konnte nicht erstellt werden!<br /><br />Kein PrimaryKey vorhanden!</div>';
		}
	break;

	// Fragt die Zugangsdaten des DB-Servers ab
	default:
		require 'template.php';
}
