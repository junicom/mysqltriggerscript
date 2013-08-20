<div id="error">
	<b>An error occured while connection to the database!</b><br /><br />
	Error no: <?php echo $mysql->connect_errno; ?><br />
	<?php echo $mysql->connect_error; ?>
</div>