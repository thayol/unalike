<div class="card-sub-container">
	<div class="background-color card-osu card-osu-topless">
		<div class="card-title">
			<h3 class="cover-image">
				<?php if ($authenticated): ?>
					<?php if (!empty($avatar_url)): ?>
						<img class="card-avatar" src="<?php echo $avatar_url; ?>" /><br>
					<?php endif; ?>
					<?php echo $username ?>
				<?php else: ?>
					<span style="font-size:1.5em;font-weight:bold;">Unalike</span>
				<?php endif; ?>
				<br>
				<span style="font-size:0.65em;font-weight:bold;">
					<?php if ($authenticated): ?>
						Unalike Access:
						<?php
							if ($key_working)
							{
								echo '<span class="positive-color">Granted</span>';
							}
							else
							{
								echo '<span class="negative-color">Denied!!</span>';
							}
						?>
					<?php else: ?>
						<a href="login/">Log in</a> to continue.
					<?php endif; ?>
				</span>
			</h3>
		</div>
		<p style="margin-top:30px;">
		<?php if ($authenticated): ?>
			<?php if (!$key_working): ?>
				<a href="login/">Authorize this app</a> again to get an access key.
				</p><p>
				Either your refresh token has expired, you have revoked access manually, or the application has been unregistered from the osu! api clients.
				</p><p>
				You might need to re-authorize this application from your <a href="https://osu.ppy.sh/home/account/edit#new-oauth-application">account settings</a> page.
				</p><p>&nbsp;</p><p>
			<?php endif; ?>
			BUG: Once all players are ready in a lobby, they remain ready until the map selector is opened or someone joins/leaves.
			</p><p>
			
			Please don't change the password because it breaks the invitation system. You are free to invite players, add any mods, change the team mode or win condition, and quit the match at any time, but the scores will only be evaluated after everyone has finished. Also, feel free to open slots and invite other friends to your difficulty, but the game will only start if everyone is ready.
			
			<?php if ($key_working): ?>
				<div id="management-buttons" style="text-align:center;"></div>
			<?php endif; ?>
			
		<?php else: ?>
			Unalike is a lobby synchronization system that allows you and your friends to play the same song but on different difficulties. 
			The results are calculated based on your accuracy.</p><p>
			<a href="login/">Log in</a> using your osu! account to get started.
		<?php endif; ?>
		</p>
	</div>

	<div id="unalike-display" class="background-color card-osu card-osu-topless">
		<div id="unalike-status"><h3>Loading...</h3></div>
	</div>
</div>