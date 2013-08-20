<form action="<?php echo $path; ?>createTrigger" id="ajaxForm" method="post">
	<fieldset>
		<legend>Configure trigger</legend>
		<ul>
			<li class="left">Timing:</li>
			<li>
				<select id="timing" name="timing">
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
			<li class="left">Tables:<br />(Multiple select is possible)</li>
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
			<li>Triggers created: <span id="currentCount">0</span>/<span id="totalCount">0</span></li>
		</ul>
		<div id="progress">
			<div id="progressBorder"><div id="bar"></div></div>
		</div>
		<div id="submit">
			<input onclick="document.location.href='<?php echo $me; ?>'" type="button" value="Reset" />
			<input id="continue" type="hidden" value="1" />
			<input id="cancel" type="button" value="Cancel" />
			<input id="check" type="submit" value="Check for conflicts" />
			<input id="create" type="submit" value="Create triggers" />
		</div>
		<div id="checkResult"></div>
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
	var submitType = '';

	function submitTable(i, total, tables) {

		// Breaks loop when the cancel button is clicked
		if ($('#continue').val() == '0') {
			$('#cancel').hide();
			$('#check').show();

			return false;
		}

		$.post('<?php echo $path; ?>createTriggers', {
			'timing': $('#timing').val(),
			'event': $('#event').val(),
			'hostname': '<?php echo $hostname; ?>',
			'username': '<?php echo $username; ?>',
			'password': '<?php echo $password; ?>',
			'sourceDatabase': '<?php echo $sourceDatabase; ?>',
			'targetDatabase': '<?php echo $targetDatabase; ?>',
			'newDatabase': '<?php echo $newDatabase; ?>',
			'table': tables[i],
			'include': $('input[name="include[' + tables[i] + ']"]').prop('checked') ? '1' : '0',
			'statement': $('input[name="statement[' + tables[i] + ']"]').length > 0 ? $('input[name="statement[' + tables[i] + ']"]').val() : ''
		}).done(function(data) {
			$('#result').prepend(data);
			$('#currentCount').text(i + 1);
			$('#bar').animate({'width': 100 / (total + 1) * 3 * (i + 1)}, 150);

			if (i < total) {
				submitTable(i + 1, total, tables);
			}
			else {
				$('#cancel').hide();
				$('#check').show();
			}
		});
	}

	// Counts the number of selected tables
	$('#tables').change(function() {
		$('#totalCount').text($('#tables :selected').length);
		$('#bar').css('width', '0px');
		$('#currentCount').text('0');
	});

	// Hides submit button when essential fields were changed
	$('#timing, #event, #tables').change(function() {
		$('#create').hide();
	});

	// Initiates the loop break
	$('#cancel').click(function() {
		$('#continue').val('0');
	});

	// Hides the cancel button by default
	$('#cancel').hide();

	// Sets the submit type
	$('#check, #create').click(function() {
		submitType = $(this).attr('id');
	});

	// Handles the ajax submit
	$('#ajaxForm').submit(function() {
		var tables = new Array();

		$('#tables :selected').each(function(i, selected) {
			tables.push($(selected).text());
		});

		// Checks for conflicts
		if (submitType == 'check') {
			$.post('<?php echo $path; ?>checkForConflicts', {
				'timing': $('#timing').val(),
				'event': $('#event').val(),
				'hostname': '<?php echo $hostname; ?>',
				'username': '<?php echo $username; ?>',
				'password': '<?php echo $password; ?>',
				'port': <?php echo $port; ?>,
				'sourceDatabase': '<?php echo $sourceDatabase; ?>',
				'tables': tables.join(',')
			}).done(function(data) {
				$('#create').show();
				$('#checkResult')
					.html(data)
					.find('a')
					.unbind('click')
					.click(function() {
						$(this).closest('div').next().slideToggle(200);
					});
			});
		}

		// Creates the triggers
		else {
			$('#cancel').show();
			//$('#create').hide();

			submitTable(0, tables.length - 1, tables);
		}

		return false;
	});
</script>