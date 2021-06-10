<div class="navbar background-color">
	<div class="navbar-title">
		<a href="./"><div class="navbar-logo"></div></a>
		<h2><span style="font-family:'Cambria',sans-serif;">Unalike</span> <span style="font-size:0.6em;font-style:italic;">(3rd-party osu! lobby manager)</span></h2>
	</div>
	
	<div class="navbar-button-container">
		<?php if ($authenticated && $key_working): ?>
			<a href="logout/"><button class="button-osu button-negative navbar-button">Log out</button></a>
		<?php elseif ($authenticated): ?>
			<a href="login/"><button class="button-osu button-positive navbar-button">Retry</button></a>
		<?php else: ?>
			<a href="login/"><button class="button-osu button-positive navbar-button">Log in</button></a>
		<?php endif; ?>
	</div>
</div>