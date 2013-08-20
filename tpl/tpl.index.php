<!DOCTYPE html>
<html>
<head>
	<title>TriggerMaker</title>
	<style type="text/css">
		@import url('css/style.css');
	</style>
	<script src="js/jquery-1.10.2.min.js" type="text/javascript"></script>
	<script src="js/jquery.form.js" type="text/javascript"></script>
</head>
<body>
	<div id="wrapper">
		<form action="<?php echo $path; ?>selectDBs" id="form" method="post">
			<fieldset>
				<legend>Connection data</legend>
				<div class="info">Please ensure, that you own the required privileges to create databases, tables and triggers before continue!</div>
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
					<input type="submit" value="Continue" />
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