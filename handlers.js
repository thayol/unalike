function isEmpty(obj) {
	for (var prop in obj) {
		if (obj.hasOwnProperty(prop)) return false;
	}
	return true;
}

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