<form action="<?php echo $path; ?>selectTrigger" id="form" method="post">
	<fieldset>
		<legend>Select database</legend>
		<ul>
			<li class="left">Source database:</li>
			<li>
				<select name="sourceDatabase">
					<?php foreach ($databases as $database): ?>
					<option value="<?php echo $database; ?>"><?php echo $database; ?></option>
					<?php endforeach; ?>
				</select>
			</li>
		</ul>
		<ul>
			<li class="left">History database:</li>
			<li>
				<select name="targetDatabase">
					<option value="0" selected="selected">&lt;&lt; Create new database &gt;&gt;</option>
					<option disabled="disabled" value="" style="background:#aaa"></option>
					<?php foreach ($databases as $database): ?>
					<option value="<?php echo $database; ?>"><?php echo $database; ?></option>
					<?php endforeach; ?>
				</select>
			</li>
		</ul>
		<ul id="newDatabase">
			<li class="left">New database name:</li>
			<li><input name="newDatabase" placeholder="Database name" type="text" /></li>
		</ul>
		<div id="submit">
			<input onclick="document.location.href='<?php echo $me; ?>'" type="button" value="Reset" />
			<input type="submit" value="Continue" />
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
			alert('You have to enter a name for the new database!');

			$('input[name="newDatabase"]').focus();

			return false;
		}
	});
</script>