<?php
$template = isset($template) ? $template : $action;

switch ($template) {

	// Verbindungsfehler
	case 'connectionError': ?>
<div id="error">
	<b>Fehler bei der Verbindung!</b><br /><br />
	Fehlernummer: <?php echo $mysql->connect_errno; ?><br />
	<?php echo $mysql->connect_error; ?>
</div>
<?php
	break;

	// Formular für die Datenbank-Zugangsdaten
	case 'selectDBs': ?>
<form action="<?php echo $path; ?>selectTrigger" id="form" method="post">
	<fieldset>
		<legend>Datenbank ausw&auml;hlen</legend>
		<ul>
			<li class="left">Quelldatenbank:</li>
			<li>
				<select name="sourceDatabase">
					<?php foreach ($databases as $database): ?>
					<option value="<?php echo $database; ?>"><?php echo $database; ?></option>
					<?php endforeach; ?>
				</select>
			</li>
		</ul>
		<ul>
			<li class="left">History-Zieldatenbank:</li>
			<li>
				<select name="targetDatabase">
					<option value="0" selected="selected">&lt;&lt; Neue Datenbank anlegen &gt;&gt;</option>
					<option disabled="disabled" value="" style="background:#aaa"></option>
					<?php foreach ($databases as $database): ?>
					<option value="<?php echo $database; ?>"><?php echo $database; ?></option>
					<?php endforeach; ?>
				</select>
			</li>
		</ul>
		<ul id="newDatabase">
			<li class="left">Neuer Datenbank-Name:</li>
			<li><input name="newDatabase" placeholder="Datenbank-Name" type="text" /></li>
		</ul>
		<div id="submit">
			<input onclick="document.location.href='<?php echo $me; ?>'" type="button" value="Zur&uuml;cksetzen" />
			<input type="submit" value="Weiter" />
		</div>
	</fieldset>
	<input name="hostname" type="hidden" value="<?php echo $hostname; ?>" />
	<input name="username" type="hidden" value="<?php echo $username; ?>" />
	<input name="password" type="hidden" value="<?php echo $password; ?>" />
	<input name="port" type="hidden" value="<?php echo (string)$port; ?>" />
</form>
<script type="text/javascript">
	$('select[name="targetDatabase"]').change(function() {
		if ($(this).val() == '0') {
			$('#newDatabase').slideDown(150);
			$('input[name="newDatabase"]').focus();
		}
		else {
			$('#newDatabase').slideUp(150);
		}
	});

	$('#form').submit(function() {
		if ($('select[name="targetDatabase"]').val() == '0' && $('input[name="newDatabase"]').val() == '') {
			alert('Es muss ein Name für die Datenbank eingegeben werden!');

			$('input[name="newDatabase"]').focus();

			return false;
		}
	});
</script>
<?php
	break;

	// Formular für die Erstellung des Triggers
	case 'selectTrigger': ?>
<form action="<?php echo $path; ?>createTrigger" id="ajaxForm" method="post">
	<fieldset>
		<legend>Trigger konfigurieren</legend>
		<ul>
			<li class="left">Zeitpunkt:</li>
			<li>
				<select id="time" name="time">
					<option value="BEFORE">BEFORE</option>
					<option value="AFTER">AFTER</option>
				</select>
			</li>
			<li class="left">Event:</li>
			<li>
				<select id="event" name="event">
					<?php foreach ($eventTypes as $type): ?>
					<option value="<?php echo strtoupper($type); ?>"><?php echo strtoupper($type); ?></option>
					<?php endforeach; ?>
				</select>
			</li>
			<li class="left">Tabellen:<br />(Multiple Auswahl m&ouml;glich)</li>
			<li>
				<select id="tables" multiple="multiple" name="tables[]" size="10">
					<?php while ($db = $result->fetch_object()): ?>
					<option value="<?php echo $db->{'Tables_in_' . $sourceDatabase}; ?>"><?php echo $db->{'Tables_in_' . $sourceDatabase}; ?></option>
					<?php endwhile; ?>
				</select>
			</li>
		</ul>
		<ul>
			<li class="left">&nbsp;</li>
			<li>Trigger erstellt: <span id="currentCount">0</span>/<span id="totalCount">0</span></li>
		</ul>
		<div id="progress">
			<div id="progressBorder"><div id="bar"></div></div>
		</div>
		<div id="submit">
			<input onclick="document.location.href='<?php echo $me; ?>'" type="button" value="Zur&uuml;cksetzen" />
			<input id="continue" type="hidden" value="1" />
			<input id="cancel" type="button" value="Abbrechen" />
			<input id="create" type="submit" value="Trigger erstellen" />
		</div>
		<div id="result"></div>
	</fieldset>
	<input name="hostname" type="hidden" value="<?php echo $hostname; ?>" />
	<input name="username" type="hidden" value="<?php echo $username; ?>" />
	<input name="password" type="hidden" value="<?php echo $password; ?>" />
	<input name="sourceDatabase" type="hidden" value="<?php echo $sourceDatabase; ?>" />
	<input name="targetDatabase" type="hidden" value="<?php echo $targetDatabase; ?>" />
	<input name="newDatabase" type="hidden" value="<?php echo $newDatabase; ?>" />
	<input name="port" type="hidden" value="<?php echo (string)$port; ?>" />
</form>
<script type="text/javascript">
	function submitTable(i, total, tables) {

		// Bricht die Schleife ab, wenn der Abbrechen-Button gedrückt wurde
		if ($('#continue').val() == '0') {
			$('#cancel').hide();
			$('#create').show();

			return false;
		}

		$.post('<?php echo $path; ?>createTrigger', {
			'time': $('#time').val(),
			'event': $('#event').val(),
			'hostname': '<?php echo $hostname; ?>',
			'username': '<?php echo $username; ?>',
			'password': '<?php echo $password; ?>',
			'sourceDatabase': '<?php echo $sourceDatabase; ?>',
			'targetDatabase': '<?php echo $targetDatabase; ?>',
			'newDatabase': '<?php echo $newDatabase; ?>',
			'table': tables[i]
		}).done(function(data) {
			$('#result').prepend(data);
			$('#currentCount').text(i + 1);
			$('#bar').animate({'width': 100 / (total + 1) * 3 * (i + 1)}, 150);

			if (i < total) {
				submitTable(i + 1, total, tables);
			}
			else {
				$('#cancel').hide();
				$('#create').show();
			}
		});
	}

	// Zählt die Anzahl der ausgewählten Tabellen
	$('#tables').change(function() {
		$('#totalCount').text($('#tables :selected').length);
		$('#bar').css('width', '0px');
		$('#currentCount').text('0');
	});

	// Leitet den Abbruch der Schleife ein
	$('#cancel').click(function() {
		$('#continue').val('0');
	});

	// Versteckt den Abbrechen-Button
	$('#cancel').hide();

	// Feuert für jede Tabelle hintereinander ein Ajax-Query ab
	$('#ajaxForm').submit(function() {
		var tables = new Array();

		$('#cancel').show();
		$('#create').hide();
		$('#tables :selected').each(function(i, selected) {
			tables.push($(selected).text());
		});

		submitTable(0, tables.length - 1, tables);

		return false;
	});
</script>
<?php
	break;

	// Hauptgerüst
	default: ?>
<!DOCTYPE html>
<html>
<head>
	<title>TriggerMaker</title>
	<style type="text/css">
		@import url('style.css');
	</style>
	<script src="jquery-1.10.2.min.js" type="text/javascript"></script>
	<script src="jquery.form.js" type="text/javascript"></script>
</head>
<body>
	<div id="wrapper">
		<form action="<?php echo $path; ?>selectDBs" id="form" method="post">
			<fieldset>
				<legend>Zugangsdaten</legend>
				<div id="info">Bitte stellen Sie vorher sicher, dass Sie &uuml;ber die ben&ouml;tigten Rechte zur Erstellung von Datenbanken, Tabellen und Triggern verf&uuml;gen!</div>
				<ul>
					<li class="left">Hostname:</li>
					<li><input id="focus" name="hostname" type="text" value="<?php echo $stdConnection['hostname']; ?>" /></li>
				</ul>
				<ul>
					<li class="left">Username:</li>
					<li><input name="username" type="text" value="<?php echo $stdConnection['username']; ?>" /></li>
				</ul>
				<ul>
					<li class="left">Password:</li>
					<li><input name="password" type="text" value="<?php echo $stdConnection['password']; ?>" /></li>
				</ul>
				<ul>
					<li class="left">Port:</li>
					<li><input name="port" type="text" value="<?php echo (string)$stdConnection['port']; ?>" /></li>
				</ul>
				<div id="submit">
					<input type="submit" value="Weiter" />
				</div>
			</fieldset>
			<script type="text/javascript">document.getElementById('focus').focus();</script>
		</form>
	</div>
	<script type="text/javascript">
		function createSlider() {
			$('#form').ajaxForm(function(data) {
				$('#wrapper').slideUp(0, function() {
					$('#wrapper').html(data).slideDown(200);
					createSlider();
				});

			});
		}

		$(document).ready(function() {
			createSlider();
		});
	</script>
</body>
</html>
<?php
}
