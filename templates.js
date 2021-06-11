var multiplayerMatchTemplate = `
	
	`;

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
	<button onclick="hiddenFunctionClick(this, 'action/?shutdown', 3)" class="button-osu button-osu-management button-negative" disabled>
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
