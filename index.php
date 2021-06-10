<?php
session_start();
// http://zovguran.net/Unalike/
$authenticated = false;
include "api_key.php";

$username = "CACHED";
$username_irc = "";
$userid = "";
$key_working = false;
$authenticated = false;

if (!empty($_SESSION["unalike-osu-id"]) && !empty($_SESSION["unalike-osu-username"]) && isset($_SESSION["unalike-granted"]))
{
	$authenticated = true;
	$username = $_SESSION["unalike-osu-username"];
	$username_irc = str_replace(" ", "_", $_SESSION["unalike-osu-username"]);
	$userid = $_SESSION["unalike-osu-id"];
	
	$token_file = "tokens/{$userid}.json";
	if (file_exists($token_file))
	{
		$token_json = json_decode(file_get_contents($token_file), true);
		if (!empty($token_json["expires_at"]) && $token_json["expires_at"] < (time()+3600))
		{
			$refresh_token = $token_json["refresh_token"];
			
			$postdata = array(
				"client_id" => $client_id,
				"client_secret" => $client_secret,
				"refresh_token" => $refresh_token,
				"grant_type" => "refresh_token",
				"redirect_uri" => $callback_uri,
			);
			
			$poststrings = array();
			foreach ($postdata as $key => $value) $poststrings[] = "{$key}={$value}";
			$poststring = implode("&", $poststrings);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://osu.ppy.sh/oauth/token");
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $poststring);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$auth_data = curl_exec($ch);
			curl_close($ch);
			$auth_json = json_decode($auth_data, true);

			if (!empty($auth_data) && !empty($auth_json))
			{
				$now = time();
				$expiry_date = $now + intval($auth_json["expires_in"]);
				$auth_json["expires_at"] = $expiry_date;
				$token_json = $auth_json;
			}
			else
			{
				$authenticated = false;
			}
		}
	}
	
	if (!empty($token_json["access_token"]))
	{
		$token = $token_json["access_token"];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://osu.ppy.sh/api/v2/me/osu");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ "Authorization: Bearer {$token}", "Content-type: application/json" ]);
		$profile_data = curl_exec($ch);
		curl_close($ch);
		$profile_json = json_decode($profile_data, true);
		
		if (!empty($profile_data) && !empty($profile_json) && isset($profile_json["id"]) && $profile_json["id"] == $_SESSION["unalike-osu-id"])
		{
			$key_working = true;
			$_SESSION["unalike-granted"] = true;
		}
		else
		{
			$_SESSION["unalike-granted"] = false;
		}
	}
}

$avatar_url = "";
$cover_url = "/Unalike/img/covers/c4.jpg"; // some default pls
if ($authenticated)
{
	$userfile = "users/" . $userid . ".json";
	if (file_exists($userfile))
	{
		$userJson = json_decode(file_get_contents($userfile), true);
		if (!empty($userJson["cover_url"]))
		{
			$cover_url = $userJson["cover_url"];
			$avatar_url = $userJson["avatar_url"];
		}
	}
}

$json_display = false;
?>
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
	<?php
		// echo " ・ Home";
	?>
</title>
<link rel="icon" type="image/png" sizes="32x32" href="http://zovguran.net/Unalike/img/favicon/32.png">
<link rel="icon" type="image/png" sizes="16x16" href="http://zovguran.net/Unalike/img/favicon/16.png">
<meta name="viewport" content="width=device-width, initial-scale=0.6">
<style>
* {
	box-sizing: border-box;
}
:root {
  --void: #1c1719;
  --background-darker: #2a2225;
  --background: #382e32;
  
  --void-alt: #171a1c;
  --background-alt: #2e3438;
  
  --text: #ffffff;
  
  --text-disabled: #dddddd;
  --disabled: #5c5c5c;
  
  --positive: #3c9cc8;
  --positive-dim: #4fb7e7;
  
  --negative: #c82838;
  --negative-dim: #e73e4b;
  
  --link: #d7a5bc;
  
  --shadow: #000000;
  --glow: #ffffff;
}
html {
	font-family: "Lucida Grande", sans-serif;
	color: var(--text);
	font-size: 1.2em;
	text-align: center;
}
body {
	text-align: left;
}
html, body {
	margin: 0;
	padding: 0;
	overflow-x: hidden;
}
.void-color {
	background-color: var(--void);
}
.background-color {
	background-color: var(--background);
}
.positive-color {
	color: var(--positive);
}
.negative-color {
	color: var(--negative);
}
a:link, a:visited, a:active, a:hover {
	text-decoration: none;
	color: var(--link);
}
.button-positive {
	background-color: var(--positive);
}
.button-positive:hover {
	background-color: var(--positive-dim);
}
.button-negative {
	background-color: var(--negative);
}
.button-negative:hover {
	background-color: var(--negative-dim);
}
h3 {
	margin: 0;
	padding: 30px 0;
	font-weight: normal;
	font-size: 1.5em;
}
p {
	margin-top: 0;
	text-align: justify;
}
.cover-image {
	background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo $cover_url ?>');
	background-size: cover;
	font-weight: normal;
	margin: 0 -50px;
	border-top-left-radius: 10px;
	border-top-right-radius: 10px;
}
a:hover, a:active {
	text-decoration: underline;
}
.link-osu {
	transition-duration: 0.3s;
	text-shadow: 0px 0px 0px var(--shadow);
}
.link-osu:hover {
	text-shadow:3px 3px 4px var(--shadow);
}
.card-container {
	padding: 10px 20px;
	display: flex;
	flex-flow: row wrap;
	align-content: center;
	align-items: flex-start;
	/* align-items: stretch; */ /* enable for disgusting-but-even containers */
	justify-content: flex-start;
}
.card-sub-container {
	flex: 1 1 0;
	display: flex;
	flex-flow: column nowrap;
	align-content: center;
	align-items: stretch;
	justify-content: flex-start;
}
.hr-osu {
	flex: 2 0 100%;
	padding: 0;
	// margin: 0 -50px;
	border: none;
	background-color: var(--text);
	height: 2px;
	border-radius: 10px;
}
.card-osu {
	flex: 1 0 auto;
	min-width: 600px;
	padding: 20px 50px;
	margin: 0;
	border-radius: 10px;
	margin-bottom: 10px;
	margin-right: 10px;
}
.card-osu-fullrow {
	flex: 1 0 100%;
}
.card-osu-topless {
	padding-top: 0px;
}
.button-osu {
	font-family: inherit;
	border: none;
	color: var(--text);
	min-width: 170px;
	height: 44px;
	line-height: 22px;
	padding: 13px 20px;
	border-radius:50px;
	font-size: 0.8em;
	font-weight: bold;
}
.button-osu-round {
	height: 44px;
	min-width: 44px;
	padding: 12px;
	width: 44px;
	line-height: 22px;
	border-radius:50%;
}
.button-osu:active {
	box-shadow: 0 0 3px var(--glow);
	transition-duration: 0.2s;
}
.button-osu:disabled {
	transition-duration: 0.2s;
	color: var(--text-disabled);
	background-color: var(--disabled);
}
.button-osu-management {
	display: flex;
	flex-flow: row wrap;
	flex: 1 0 0;
	margin: 10px;
	padding-right: 30px;
	min-width: 200px;
	text-align: center;
}
.button-osu-management-container {
	display: flex;
	flex-flow: row wrap;
	align-items: stretch;
	align-content: flex-start;
	justify-content: space-evenly;
}
.button-symbol {
	flex: 0 0 0;
}
.button-label {
	flex: 2 0 auto;
	text-align: center;
	width: auto;
	margin: 0;
}
.card-title {
	text-align: center;
}
.card-avatar {
	width: 120px;
	height: 120px;
	border-radius: 50%;
	margin-bottom: 30px;
}
.card-right {
	text-align: right;
}
.navbar {
	margin-bottom: 5px;
	margin: 10px 30px 0 20px;
	padding: 20px 50px;
	border-radius: 10px;
	min-width: 600px;
	height: 80px;
	white-space: nowrap;
	overflow: hidden;
}
.navbar-title {
	float: left;
	margin: -10px 0 0 -10px;
	padding-top: 6px;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
}
.navbar-logo {
	display: inline-block;
	height: 50px;
	width: 50px;
	background: url('http://zovguran.net/Unalike/img/favicon/128.png');
	background-size: contain;
}
.navbar h2 {
	display: inline-block;
	margin: 10px 10px;
	vertical-align: top;
}
.navbar-button {
	line-height: 15px;
	height: 40px;
}
.navbar-button-container {
	position: absolute;
	top: 0;
	right: 0;
	margin: 30px 80px 0 40px;
}
.link-spacer {
	width: 20px;
	display: inline-block;
}
.song-info {
	text-align: left;
	font-size: 1em;
}
.song-info, .song-info * {
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.song-title {
	font-weight: normal;
	font-size: 1.2em;
}
.song-artist {
	font-weight: normal;
	font-size: 1em;
}
.song-version {
	font-weight: bold;
	font-size: 1em;
}
.song-star-difficulty {
	font-weight: normal;
	font-size: 1em;
}
.scores {
	font-size: 0.6em;
	width: 100%;
	border-radius: 10px;
	overflow: hidden;
}
.scores, .scores tr, .scores td {
	border: none;
	border-collapse: collapse;
}
.scores .rowspan {
	font-size: 1.5em;
	padding: 30px 15px;
}
.scores .row-upper {
	vertical-align: bottom;
}
.scores .row-lower {
	vertical-align: top;
}
.scores .row-upper, .scores .row-lower {
	max-width: 100px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.scores .score-accuracy {
	text-align: right;
}
.scores .score-place {
	padding-right: 0;
	width: 40px;
}
.scores .score-name {
	padding-left: 0;
}
.scores .score-status {
	font-weight: bold;
	margin-right: 10px;
}
.scores tr:nth-child(4n), .scores tr:nth-child(4n-1) {
	background-color: var(--background-darker);
}
#multiplayer-stats {
	align-items: flex-start;
}
</style>
</head>
<body>

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

<div class="card-container">
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
		<!--
		<h3>
			Quickstart
		</h3>
		-->
		<p style="margin-top:30px;">
		<?php if ($authenticated): ?>
			<?php if ($key_working): ?>
				<!-- <div style="text-align:right;">(To-do: auto-shutdown.)</div>
				Create a <a href="action/?new">new lobby</a>, then <a href="action/?invite">invite</a> yourself. <br>
				Don't forget to perform a <a href="action/?shutdown">shutdown</a> after you're done. <br> -->
			<?php else: ?>
				<a href="login/">Authorize this app</a> again to get an access key.
				</p><p>
				Either your refresh token has expired, you have revoked access manually, or the application has been unregistered from the osu! api clients.
				</p><p>
				You might need to re-authorize this application from your <a href="https://osu.ppy.sh/home/account/edit#new-oauth-application">account settings</a> page.
				</p><p>&nbsp;</p><p>
			<?php endif; ?>
			<!--
			BUG: Once all players are ready in a lobby, they remain ready until the map selector is opened or someone joins/leaves.
			</p><p>
			-->
			
			Please don't change the password because it breaks the invitation system. You are free to invite players, add any mods, change the team mode or win condition, and quit the match at any time, but the scores will only be evaluated after everyone has finished. <!-- Also, feel free to open slots and invite other friends to your difficulty, but the game will only start if everyone is ready. -->
			
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


<?php if ($authenticated && $json_display): ?>
<div class="card-sub-container">
	<div class="background-color card-osu">
		<h3>Unalike JSON</h3>
		<div id="dynamic-json"></div>
		<h3>Player JSON</h3>
		<?php
			$userfile = "users/" . $userid . ".json";
			if (file_exists($userfile))
			{
				$userJson = json_decode(file_get_contents($userfile), true);
				echo "<pre>";
				print_r($userJson);
				echo "</pre>";
			}
			$cover_url = ""; // some default pls
		?>
	</div>
</div>
<?php endif; ?>

</div><!-- End of the container. -->

<script>
function isEmpty(obj) {
	for (var prop in obj) {
		if (obj.hasOwnProperty(prop)) return false;
	}
	return true;
}
</script>

<script>
var localStorage = window.localStorage;
var localStoragePrefix = "unalike-";

var refreshFrequency = 5000;
var maxUpdateDelayTolerance = 30;
var defaultLobbyContent = '<div class="card-osu" style="text-align:center;"><h3>No lobbies are open.</h3></div>';
var defaultMultiplayerContent = '<div class="card-osu" style="text-align:center;"><h3>History is empty.</h3></div>';
var lobbyTemplate = '<div class="background-color card-osu"><h3>Template loading...</h3></div>';
var lobbyTemplate = '';
var refreshRequest = new XMLHttpRequest();
var templateRequest = new XMLHttpRequest();
var multiplayerRequest = new XMLHttpRequest();
// var refreshIntervalId = window.setInterval(refreshData, refreshFrequency);
// var multiplayerIntervalId = window.setInterval(refreshMultiplayer, refreshFrequency);
var currentLobbiesContent = "initial";
var currentMultiplayerContent = "initial";
var onlineButtonSet = "initial";
var opMode = false;

var session_username = "Guest";
var session_username_irc = "Guest";
var session_user_id = -3;
<?php
	if ($authenticated)
	{
		echo 'session_username = "' . $username . '"; ';
		echo 'session_username_irc = "' . $username_irc . '"; ';
		echo 'session_user_id = ' . $userid . '; ';
	}
?>

var randomCover = (Math.floor(Math.random() * 8)+1);
<?php
	if (!$authenticated)
	{
		echo 'randomCover = 3;';
	}
?>
if (localStorage.getItem(localStoragePrefix + "lobbyCover")) {
	randomCover = localStorage.getItem(localStoragePrefix + "lobbyCover");
}
refreshTemplate();
// refreshData();
// refreshMultiplayer();
// setTimeout(refreshData, 500);
// setTimeout(refreshMultiplayer, 500);

refreshRequest.onreadystatechange = function() {
	if (this.readyState == 4 && this.status == 200) {
		var result = JSON.parse(this.responseText);
		updateUnalikeDisplay(result);
	}
};

multiplayerRequest.onreadystatechange = function() {
	if (this.readyState == 4 && this.status == 200) {
		var result = JSON.parse(this.responseText);
		updateMultiplayer(result);
	}
};

function updateButtonSet(onlineState, force=false, full=false, empty=false) {
	if (document.getElementById("management-buttons")) {
		element = document.getElementById("management-buttons");
		if (onlineButtonSet !== onlineState || force) {
			onlineButtonSet = onlineState;
			if (onlineButtonSet) {
				var temp = buttonsIfOnline;
				if (opMode) {
					temp = opButtonsIfOnline;
				}
				
				if (full) {
					temp = temp.replaceAll("putDisabledHere", "disabled");
				}
				
				if (empty) {
					temp = temp.replaceAll("putDisabled2Here", "disabled");
				}
				
				element.innerHTML = temp;
			}
			else {
				element.innerHTML = buttonsIfOffline;
			}
		}
	}
}

function updateUnalikeDisplay(unalikeJson) {
	var thisUpdateInterval = 10; // in seconds
	var thisUpdateIntervalIfLobby = 1;
	var thisUpdateIntervalIfPlaying = 6;
	
	var currentTimestamp = Math.floor(Date.now() / 1000);
	if (unalikeJson.timestamp + maxUpdateDelayTolerance < currentTimestamp) {
		unalikeJson = {}
	}
	
	statusDisplay = document.getElementById("unalike-status");
	lobbiesContainer = document.getElementById("unalike-lobbies-container");
	
	var oldLobbiesContent = currentLobbiesContent;
	
	var newStatus = "";
	if (isEmpty(unalikeJson)) {
		newStatus = '<h3>Unalike: <span class="negative-colora">Offline</span></h3><p>Unalike is not running.</p>';
		updateButtonSet(false);
		
		if (lobbiesContainer.innerHTML.trim().replace(/\s/g, "") != defaultLobbyContent.trim().replace(/\s/g, "")) {
			lobbiesContainer.innerHTML = defaultLobbyContent;
		}
		
		if (currentLobbiesContent != JSON.stringify(unalikeJson)) {
			currentLobbiesContent = JSON.stringify(unalikeJson);
		}
	}
	else {
		newStatus = '<h3>Unalike: <span class="positive-colora">Online</span></h3><p>Unalike is up.</p>';
		
		var full = false
		var empty = false
		if (unalikeJson.maxLobbies && !isEmpty(unalikeJson)) {
			full = !(Object.keys(unalikeJson.lobbies).length < unalikeJson.maxLobbies)
		}
		if (!isEmpty(unalikeJson) && isEmpty(unalikeJson.lobbies)) {
			updateButtonSet(true, false, full, true);
		}
		
		if (unalikeJson.roundsPlayed) {
			var plural = "s have";
			if (unalikeJson.roundsPlayed == 1) {
				plural = " gas";
			}
			newStatus += "</p><p>" + unalikeJson.roundsPlayed + " game" + plural + " been played since boot.";
		}
		
		if (unalikeJson.delay) {
			newStatus += "</p><p>Current delay: " + unalikeJson.delay + " seconds between commands. (~" + unalikeJson.delay*4 + " seconds to create a lobby.)";
		}
		// dynamicJsonElement = document.getElementById("dynamic-json");
		// dynamicJsonElement.innerHTML = JSON.stringify(unalikeJson);
		
		if (!isEmpty(unalikeJson.lobbies)) {
			if (unalikeJson.playing) {
				thisUpdateInterval = thisUpdateIntervalIfPlaying;
			}
			else {
				thisUpdateInterval = thisUpdateIntervalIfLobby;
			}
			
			var newLobbiesContent = "";
			var songsInContent = [];
			
			var counter = 2;
			for (var lobby_id in unalikeJson.lobbies) {
				var lobby = unalikeJson.lobbies[lobby_id];
				var playerList = [];
				
				if (!isEmpty(lobby.players)) {
					for (var player of lobby.players) {
						// playerList += "<li>" + player + "</li>";
						if (session_username == player)
						{
							playerList.push('<span class="player positive-color">' + player + '</span>');
						}
						else
						{
							playerList.push('<span class="player">' + player + '</span>');
						}
					}
					// playerList = '<p style="margin-bottom:0;">Players:</p>' +
								 // '<ul style="margin:10px 0;">' + playerList + '</ul>';
					playerList = '<p style="margin-bottom:0;">Players: ' + playerList.join(", ") + '</p>';
				}
				else
				{
					// playerList += "<p>No one is in the lobby.</p>";
				}
				
				var title = '<span class="song-title">Loading...</span><br>' + 
							'<span class="song-artist"> </span><br>' + 
							'<span class="song-version"> </span><br>' + 
							'<span class="song-star-rating" style="opacity:0;">★</span>';
				if (lobby.beatmap && lobby.beatmap > 0) {
					var requestId = "songTitle" + counter++;
					var title = '<div id="' + requestId + '">' + title + '</div>';
					songsInContent.push({
						"requestId":requestId,
						"beatmapId":lobby.beatmap
					});
				}
				else if (lobby.beatmap && lobby.beatmap == -2) {
					var title = '<span class="song-title">Changing beatmap...</span><br>' + 
								'<span class="song-artist"> </span><br>' + 
								'<span class="song-version"> </span><br>' + 
								'<span class="song-star-rating" style="opacity:0;">★</span>';
				}
				else {
					var title = '<span class="song-title">No beatmap set.</span><br>' + 
								'<span class="song-artist"> </span><br>' + 
								'<span class="song-version"> </span><br>' + 
								'<span class="song-star-rating" style="opacity:0;">★</span>';
				}
				
				var stateText = "Loading...";
				var stateSymbol = "🔁";
				var lobbyReady = true;
				
				if (lobby.playing) {
					stateSymbol = "▶️";
					stateText = "Match in progress.";
				}
				else if (lobby.finished) {
					stateSymbol = "⏸";
					stateText = "Finished. Waiting others...";
				}
				else if (lobby.ready) {
					if (unalikeJson.playing) {
						stateSymbol = "🔁";
						stateText = "Starting...";
					}
					else {
						stateSymbol = "✅";
						stateText = "Ready!";
					}
				}
				else if (!isEmpty(lobby.players)) {
					stateSymbol = "❎";
					stateText = "Not ready.";
				}
				else if (lobby.password) {
					stateSymbol = "🆙";
					stateText = "Waiting for participants.";
				}
				else {
					stateSymbol = "🆕";
					stateText = "Setting up lobby...";
					lobbyReady = false;
				}
				
				if (lobbyReady)
				{
					if (lobby.desynced) {
						stateSymbol = "🔀 (Desynced)";
					}
					else if (lobby.beatmapset > 0 && lobby.beatmapset != unalikeJson.current_mapset)
					{
						stateSymbol = "🆘 (Desynced)";
					}
				}
				var state = stateSymbol + " " + stateText;
				
				var cover = "/Unalike/img/covers/c" + randomCover + ".jpg";
				if (lobbyTemplate != '') {
					var tempLobbyText = lobbyTemplate
						.replaceAll("[[ SONG_INFO ]]", title)
						.replaceAll("[[ PLAYERS ]]", playerList)
						.replaceAll("[[ COVER_IMAGE ]]", cover)
						.replaceAll("[[ CHANNEL ]]", lobby.match)
						.replaceAll("[[ STATE ]]", state);
					
					if (!lobbyReady) {
						tempLobbyText = tempLobbyText.replaceAll("putDisabledHere", "disabled");
					}
					
					newLobbiesContent += tempLobbyText;
				}
			}
				
			unalikeJson.timestamp = 0;
			if (currentLobbiesContent != JSON.stringify(unalikeJson) || 
				lobbiesContainer.innerHTML.trim().replace(/\s/g, "") == "" || 
				lobbiesContainer.innerHTML.trim().replace(/\s/g, "") == defaultLobbyContent.trim().replace(/\s/g, "") ) {
					
				lobbiesContainer.innerHTML = newLobbiesContent;
				currentLobbiesContent = JSON.stringify(unalikeJson);
				if (unalikeJson.maxLobbies) {
					full = !(Object.keys(unalikeJson.lobbies).length < unalikeJson.maxLobbies)
				}
				updateButtonSet(true, true, full);
				
				if (newLobbiesContent != "") {
					for (var songInContent of songsInContent) {
						refreshSongInfo(songInContent.requestId, songInContent.beatmapId);
					}
				}
			}
		}
		else
		{
			unalikeJson.timestamp = 0;
			if (currentLobbiesContent != JSON.stringify(unalikeJson)) {
				currentLobbiesContent = JSON.stringify(unalikeJson);
				updateButtonSet(true, true, false, true);
			}
			
			if (lobbiesContainer.innerHTML != defaultLobbyContent) {
				lobbiesContainer.innerHTML = defaultLobbyContent;
			}
		}
	}
	
	if (statusDisplay.innerHTML != newStatus) {
		statusDisplay.innerHTML = newStatus
	}
	
	if (currentLobbiesContent != oldLobbiesContent || currentLobbiesContent != oldLobbiesContent) {
		refreshMultiplayer();
	}
	
	window.setTimeout(refreshData, thisUpdateInterval*1000)
}

function updateTemplate(templateReply) {
	<?php
		if (!$key_working)
		{
			echo 'templateReply = templateReply.replaceAll("putDisabledHere", "disabled");';
		}
	?>
	lobbyTemplate = templateReply;
	refreshData();
}

function updateSongInfo(elementId, beatmapId, beatmapJson) {
	if (document.getElementById(elementId)) { 
		var content = '';
		if (beatmapJson.url) {
			content += '<a href="' + beatmapJson.url + '" class="link-osu" style="text-decoration:inherit;color:inherit;"><div>';
		}
		if (beatmapJson.beatmapset.title) {
			content += '<span class="song-title">' + beatmapJson.beatmapset.title + '</span><br>';
		}
		else {
			content += '<span class="song-title"> </span><br>';
		}
		if (beatmapJson.beatmapset.artist) {
			content += '<span class="song-artist">' + beatmapJson.beatmapset.artist + " // " + beatmapJson.beatmapset.creator + '</span><br>';
		}
		else {
			content += '<span class="song-artist"> </span><br>';
		}
		if (beatmapJson.version) {
			content += '<span class="song-version">' + beatmapJson.version + '</span><br>';
		}
		else {
			content += '<span class="song-version"> </span><br>';
		}
		if (beatmapJson.difficulty_rating) {
			content += '<span class="song-star-rating">' + beatmapJson.difficulty_rating.toFixed(2) + '★</span>';
		}
		else {
			content += '<span class="song-star-rating" style="opacity:0;">★</span>';
		}
		
		if (beatmapJson.url) {
			content += '</div></a>';
		}
		
		var cover = beatmapJson.beatmapset.covers.cover;
		if (beatmapJson.beatmapset.covers["cover@2x"]) {
			cover = beatmapJson.beatmapset.covers["cover@2x"];
		}
			
		element = document.getElementById(elementId);
		element.innerHTML = content
		if (beatmapJson.beatmapset.covers.cover) {
			element.parentElement.style.backgroundImage = "linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('" + cover + "')";
			element.parentElement.style.backgroundSize = "cover";
		}
	}
}

function updateMultiplayer(matchesJson) {
	if (currentMultiplayerContent != JSON.stringify(matchesJson))
	{
		currentMultiplayerContent = JSON.stringify(matchesJson)
		var newContent = "";
		if (isEmpty(matchesJson)) {
			newContent = defaultMultiplayerContent
		}
		else {
			var elementPrefix = "multiplayerMatch";
			var counter = 1;
			for (var match of matchesJson) {
				var eId = elementPrefix + counter++;
				newContent += '<div id="' + eId + '" class="background-color card-osu card-osu-topless" style="flex: 1 0 0;"></div>';
			}
		}
		
		if (document.getElementById("multiplayer-stats")) {
			document.getElementById("multiplayer-stats").innerHTML = newContent;
			
			var counter = 1;
			for (var match of matchesJson) {
				var eId = elementPrefix + counter++;
				refreshMatchInfo(eId, match);
			}
		}
	}
}

function updateMatchInfoContent(elementId, matchContent) {
	document.getElementById(elementId).innerHTML = matchContent;
}

function updateMatchInfo(elementId, matchJson) {
	for (var game of matchJson.games) {
		var newContent = "<h3>Game ID: " + game.game_id + "</h3>";
		for (var score of game.scores) {
			newContent += "<p>[#" + score.place + "] " + score.username + ": " + score.accuracy.toFixed(2) + "%</p>";
		}
		document.getElementById(elementId).innerHTML = newContent;
		break;
	}
}

function refreshData() {
	refreshRequest.open("GET", "./async/?", true);
	refreshRequest.send();
	// console.log("Async request: ./async/?");
}

function refreshMultiplayer() {
	multiplayerRequest.open("GET", "./async/match/?", true);
	multiplayerRequest.send();
}

function refreshTemplate() {
	var localItemName = localStoragePrefix + "lobby-template";

	templateRequest.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			localStorage.setItem(localItemName, this.responseText);
			updateTemplate(this.responseText);
		}
	};
	
	if (localStorage.getItem(localItemName)) {
		updateTemplate(localStorage.getItem(localItemName));
	}
	else {
		templateRequest.open("GET", "./async/lobby-template.html", true);
		templateRequest.send();
		console.log("Async request: ./async/lobby-template.html");
	}
}

function refreshSongInfo(elementId, beatmapId) {
	var songInfoRequest = new XMLHttpRequest();
	var localItemName = localStoragePrefix + "beatmap-" + session_username + "-" + beatmapId;

	songInfoRequest.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var eId = elementId;
			var bId = beatmapId;
			var result = JSON.parse(this.responseText);
			localStorage.setItem(localItemName, this.responseText);
			updateSongInfo(eId, bId, result);
		}
	};
	
	if (localStorage.getItem(localItemName)) {
		var localJson = JSON.parse(localStorage.getItem(localItemName));
		updateSongInfo(elementId, beatmapId, localJson);
	}
	else {
		var playerName = session_username;
		songInfoRequest.open("GET", "./API/" + playerName + "/b/" + beatmapId, true);
		songInfoRequest.send();
		console.log("Async request: ./API/" + playerName + "/b/" + beatmapId);
	}
}

function refreshMatchInfo(elementId, ualMatchId) {
	var matchInfoRequest = new XMLHttpRequest();
	var localItemName = localStoragePrefix + "multiplayer-" + session_username + "-" + ualMatchId;

	matchInfoRequest.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var eId = elementId;
			// var result = JSON.parse(this.responseText);
			localStorage.setItem(localItemName, this.responseText);
			updateMatchInfoContent(eId, this.responseText);
		}
	};
	
	if (localStorage.getItem(localItemName)) {
		updateMatchInfoContent(elementId, localStorage.getItem(localItemName));
		
	}
	else {
		matchInfoRequest.open("GET", "./async/match/?render&select=" + ualMatchId, true);
		matchInfoRequest.send();
		console.log("Async request: ./async/match/?render&select=" + ualMatchId);
	}
}

</script>

<script>
function clearLocalStorage() {	
	Object.keys(localStorage).forEach(function(key) {
		if (key.indexOf(localStoragePrefix) === 0) {
			localStorage.removeItem(key);
		}
		console.log("Removed from local storage: " + key);
	});
}
function hiddenFunctionClick(element, url, destroyMode = 0) // 0 = nothing, 1 = disable, 2 = delete, 3 = disable siblings too
{
	if (!this.disabled) {
		var clickRequest = new XMLHttpRequest();
		clickRequest.onreadystatechange = function() { };
		clickRequest.open("GET", url, true);
		clickRequest.send();
		
		if (destroyMode == 3) {
			for (var sibling of element.parentElement.children) {
				sibling.disabled = true;
			}
		}
		else if (destroyMode == 2) {
			element.outerHTML = "";
		}
		else if (destroyMode == 1) {
			element.disabled = true;
		}
	}
}
</script>
<script>
function keyDownTextField(e) {
	var keyCode = e.code;
	var forceRefresh = false;
	
	if (keyCode == "KeyU") {
		if (opMode) {
			opMode = false;
		}
		else {
			opMode = true;
		}
		forceRefresh = true;
	}
	else if (keyCode == "Digit1") {
		randomCover = 1;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit2") {
		randomCover = 2;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit3") {
		randomCover = 3;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit4") {
		randomCover = 4;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit5") {
		randomCover = 5;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit6") {
		randomCover = 6;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit7") {
		randomCover = 7;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit8") {
		randomCover = 8;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	else if (keyCode == "Digit9") {
		randomCover = 9;
		localStorage.setItem(localStoragePrefix + "lobbyCover", randomCover);
		forceRefresh = true;
	}
	
	if (forceRefresh) {
		currentLobbiesContent = "";
	}
}
document.addEventListener("keydown", keyDownTextField, false);
</script>
<script>
var fix = `</fix>`; // try to guess what this is for lol (IDE is shit)

var multiplayerMatchTemplate = `
	
	`;
</script>
<script>
var fix = `</fix>`; // same reason as above

var buttonsTitle = `
	<!-- <h3 style="text-align:left;">Management Buttons</h3> -->
	<div class="button-osu-management-container">
	`;

var buttonsEnd = `
	</div>
	`;

var buttonsIfOffline = `
	<button onclick="hiddenFunctionClick(this, 'action/?start', 1)" class="button-osu button-osu-management button-positive">
		<div class="button-symbol">🚩</div>
		<div class="button-label">Start</div>
	</button>
	`;

var buttonsIfOnline = `
	<button onclick="hiddenFunctionClick(this, 'action/?shutdown', 3)" class="button-osu button-osu-management button-negative">
		<div class="button-symbol">🔌</div>
		<div class="button-label">Shut down</div>
	</button>
	<hr class="hr-osu">
	<button onclick="hiddenFunctionClick(this, 'action/?invite', 1)" class="button-osu button-osu-management button-positive" putDisabled2Here>
		<div class="button-symbol">📤</div>
		<div class="button-label">Send invites</div>
	</button>
	<button onclick="hiddenFunctionClick(this, 'action/?sync', 1)" class="button-osu button-osu-management button-positive" putDisabled2Here>
		<div class="button-symbol">♻️</div>
		<div class="button-label">Force sync</div>
	</button>
	<button onclick="hiddenFunctionClick(this, 'action/?new', 3)" class="button-osu button-osu-management button-positive" putDisabledHere>
		<div class="button-symbol">➕</div>
		<div class="button-label">New lobby</div>
	</button>
	`;

var opButtons = `
	<hr class="hr-osu">
	<button onclick="hiddenFunctionClick(this, 'action/?delay&target=6', 3)" class="button-osu button-osu-management button-positive">
		<div class="button-symbol">🐌</div>
		<div class="button-label">Slow mode</div>
	</button>
	<button onclick="hiddenFunctionClick(this, 'action/?delay&target=4', 3)" class="button-osu button-osu-management button-positive">
		<div class="button-symbol">🍪</div>
		<div class="button-label">Normal mode</div>
	</button>
	<button onclick="hiddenFunctionClick(this, 'action/?delay&target=2', 3)" class="button-osu button-osu-management button-negative">
		<div class="button-symbol">💣</div>
		<div class="button-label">Kill Bancho</div>
	</button>
	<hr class="hr-osu">
	<button onclick="location.href='./action/?overtake';" class="button-osu button-osu-management button-negative">
		<div class="button-symbol">⚠️</div>
		<div class="button-label">Manual Lobby</div>
	</button>
	<button onclick="location.href='./async/lobby/?';" class="button-osu button-osu-management button-negative">
		<div class="button-symbol">⏪</div>
		<div class="button-label">Lobby History</div>
	</button>
	`;

var opButtonsEnd = `
	<div style="text-align:right;"><p style="font-style:italic;">(Power mode is enabled!)</p></div>
	`;

buttonsIfOffline = buttonsTitle + buttonsIfOffline + buttonsEnd;
opButtonsIfOnline = buttonsTitle + buttonsIfOnline + opButtons + buttonsEnd + opButtonsEnd;
buttonsIfOnline = buttonsTitle + buttonsIfOnline + buttonsEnd;
</script>

</body>
</html>