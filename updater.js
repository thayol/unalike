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
		
		if (unalikeJson.shutdownTimer) {
			newStatus += "</p><p>If nothing happens, Unalike will shut down in " + unalikeJson.shutdownTimer + " seconds.";
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
