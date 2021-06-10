<html class="void-color">
<head>
<title>
	<?php
		if ($authenticated)
		{
			echo "" . $username . " |";
		}
	?>
	Unalike
</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $unalike_root_public; ?>img/favicon/32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $unalike_root_public; ?>img/favicon/16.png">
<meta name="viewport" content="width=device-width, initial-scale=0.6">
<style>
<?php include "style.css"; ?>
</style>
</head>
<body>

<?php require "navbar.php"; ?>

<div class="card-container">
<?php require "info-panel.php"; ?>

<div class="card-sub-container">
	<div class="card-sub-container">
		<div class="background-color card-osu">
			<h3 style="padding:0;margin:0;">Lobbies</h3>
		</div>
	</div>
	
	<div id="unalike-lobbies-container" class="card-sub-container"></div>
</div>

<div class="card-sub-container">
	<div class="card-sub-container">
		<div class="background-color card-osu">
			<h3 style="padding:0;margin:0;">Match History</h3>
		</div>
	</div>

	<div class="card-sub-container card-osu-fullrow" id="multiplayer-stats" style="flex-flow:row wrap;"></div>
</div>

</div><!-- End of the container. -->

<script>
<?php include "handlers.js"; ?>
<?php include "updater.js"; ?>
<?php include "templates.js"; ?>
</script>

</body>
</html>