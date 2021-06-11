import sys
import time
import socket
import requests
import string
import random
import json
import math
import msvcrt
import os.path
from os import path

# CONFIGURATION

# location of the account files
account_path = "account.php" # take a look at "setup_help/account.php"
local_secret_path = "local_secret.php" # take a look at "setup_help/local_secret.php"

# how many seconds should be between commands
global_irc_send_timeout = 4 # in seconds (3 didn't work sometimes, 7 should always work)

# how many seconds should
global_composer_delay = 1 # in seconds (wait for osu API to catch up)

# reason of introduction: when two players selected a map at the same time, it created an endless loop
global_sync_init_timeout = 10 # in seconds (to prevent looping due to Bancho lag)

# how many lobbies should the script be able to manage (there is a limit by peppy!)
global_max_lobbies = 4 # this is the default for normal accounts by peppy

# when a lobby is created, how many people should be able to join
lobby_default_size = 1

# checks per second (Hz) [a range between 0.5 and 4 is recommended, can be any arbitrary number]
polling_rate = 2 # 2 = report to the web api every 0.5 seconds (also affects IRC ping)

# this will be used in chat messages (can be anything, does not have to point to your Unalike)
unalike_url = "http://zovguran.net/Unalike/"

# change it to match your location of the Unalike API (does not have to be local)
unalike_api = "http://localhost:80/Unalike/API"

# change it to match your location of the Unalike Composer (does not have to be local)
unalike_composer = "http://localhost:80/Unalike/compose/?"

# where the script will listen for requests from the web
requests_file = "requests.json"

# where the script will tell stuff to the web
report_file = "unalike.json"

# where the script will log the created lobbies
lobbies_dir = "lobbies/"

# the encoding used for IRC
encoding = "utf-8"

# the size of the socket buffer
buffer_size = 16384 # you might need to lower this. the bot should work with as low as 1024

# set to True if you want to use the console to send custom commands
console_input_allowed = False

# set to True if you want to see what the IRC bot is sending to Bancho
send_command_feedback = False








# initializing some variables
server = "irc.ppy.sh"
port = 6667
bot_name = ""
bot_pass = ""

lobby_default_pass = "unset"
local_secret = "unset"

# folder structure setup
os.makedirs(lobbies_dir, exist_ok=True)

lines = []
with open(account_path, "r") as file:
	lines = file.readlines()

for line in lines:
	if line.lower().find("server:") != -1:
		server = line.split(":")[1].strip()
	if line.lower().find("port:") != -1:
		port = int(line.split(":")[1].strip())
	if line.lower().find("name:") != -1:
		bot_name = line.split(":")[1].strip()
	if line.lower().find("password:") != -1:
		bot_pass = line.split(":")[1].strip()

lines = []
with open(local_secret_path, "r") as file:
	lines = file.readlines()

for line in lines:
	if line.lower().find("$local_secret") != -1:
		local_secret = line.split("\"")[1].strip()

if bot_name == "" or bot_pass == "":
	print(account_path + " does not contain login details!")
	exit()

irc = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
irc.connect((server, port))
irc.setblocking(False)


# actually irc_queue but for compatibility...
def irc_send(command, argument1="", argument2="", argument3="", argument4="", argument5="", argument6="", custom_delay="global"):
	global global_irc_send_queue, global_irc_send_timeout
	
	line = command.upper()
	
	if argument1 != "":
		line = line + " " + argument1
	if argument2 != "":
		line = line + " " + argument2
	if argument3 != "":
		line = line + " " + argument3
	if argument4 != "":
		line = line + " " + argument4
	if argument5 != "":
		line = line + " " + argument5
	if argument6 != "":
		line = line + " " + argument6
	
	line = line + "\r\n"
	
	delay = global_irc_send_timeout
	if custom_delay != "global":
		delay = custom_delay
	
	global_irc_send_queue.append({ "line": line, "delay": delay })
	
def irc_send_real(line, delay):
	global irc, encoding, current_irc_send_timeout
	
	irc.send(line.encode(encoding))
	
	current_irc_send_timeout = delay
	if send_command_feedback:
		print("SEND: " + line)

def irc_send_pm(recipent, message):
	irc_send("PRIVMSG", recipent, ":"+message)

def irc_send_links(recipient, channel_id=False):
	global managed_lobbies
	
	temp_lobbies = []
	
	counter = 1
	if channel_id != False and channel_id in managed_lobbies:
		temp_lobbies.append("[" + "osump://" + str(managed_lobbies[channel_id]["lobby"]) + "/" + str(managed_lobbies[channel_id]["password"]) + " " + str(channel_id) + "]")
		counter += 1
	else:
		for lobby in managed_lobbies:
			if "password" in managed_lobbies[lobby]:
				temp_lobbies.append("[" + "osump://" + str(managed_lobbies[lobby]["lobby"]) + "/" + str(managed_lobbies[lobby]["password"]) + " Lobby #" + str(counter) + "]")
				counter += 1
	
	msg = "No Unalike lobbies are ready!"
	if counter > 1:
		msg = "[" + unalike_url + " Unalike > ] invite: " + "  ".join(temp_lobbies)
	
	print("Sending invite to: " + recipient)
	print("Invite content: " + msg)
	irc_send_pm(recipient, msg)

def irc_sync_lobbies_to(originating_channel, beatmapset_id, beatmap_id):
	global managed_lobbies, global_sync_init_timeout
	
	timestamp_now = math.floor(time.time())
	if global_last_mapset_played == beatmapset_id:
		print(originating_channel + " tried to sync back the last played map!")
	else:
		syncAllowed = False
		if originating_channel in managed_lobbies and "lastSyncInit" in managed_lobbies[originating_channel]:
			if abs(timestamp_now - managed_lobbies[originating_channel]["lastSyncInit"]) > global_sync_init_timeout:
				managed_lobbies[originating_channel]["lastSyncInit"] = timestamp_now
				syncAllowed = True
			else:
				print(originating_channel + " attempted syncing too frequently!")
		elif originating_channel == "WebUI":
			syncAllowed = True
		
		if syncAllowed:
			for lobby in managed_lobbies:
				if lobby != originating_channel and "beatmapset" in managed_lobbies[lobby] and managed_lobbies[lobby]["beatmapset"] != beatmapset_id:
					skipSync = False
					if "desynced" in managed_lobbies[lobby]:
						if managed_lobbies[lobby]["desynced"] == True:
							skipSync = True
					
					if not skipSync:
						if "channel" in managed_lobbies[lobby]:
							print("Bringing " + managed_lobbies[lobby]["channel"] + " to compliance...")
							irc_send_pm(managed_lobbies[lobby]["channel"], "!mp map " + str(beatmap_id))
							managed_lobbies[lobby]["beatmapset"] = beatmapset_id
							managed_lobbies[lobby]["beatmap"] = beatmap_id
						else:
							print("Attempted to set a non-existent lobby's map...")
					else:
						print("Skipping " + managed_lobbies[lobby]["channel"] + "... (Reason: desynced)")
		else:
			print(originating_channel + " does not have a sync property!")
	

def fix_mp_prefix(channel_id):
	if channel_id.find("#") == -1:
		channel_id = "#mp_" + channel_id
	
	return channel_id

def irc_close_lobby(channel_id, abandon=False):
	global managed_lobbies, lobbies_changed
	
	channel_id = fix_mp_prefix(channel_id)
	
	if not abandon:
		irc_send_pm(channel_id, "!mp close")
	
	managed_lobbies.pop(channel_id, None)
	
	if abandon:
		print(channel_id + " abandoned!")
	else:
		print(channel_id + " closed!")
	
	lobbies_changed = True

def irc_start_managing(lobby_channel, lobby_name=False, join=False):
	global managed_lobbies, lobbies_changed
	
	lobby_channel = fix_mp_prefix(lobby_channel) 
	print("Lobby registered: " + lobby_channel)
	
	if join:
		print("Joining channel: " + lobby_channel)
		irc_send("JOIN", lobby_channel)
	
	
	if not (lobby_channel in managed_lobbies):
		managed_lobbies[lobby_channel] = {}
	
	managed_lobbies[lobby_channel]["channel"] = lobby_channel
	if lobby_name != False:
		managed_lobbies[lobby_channel]["name"] = lobby_name
	if join:
		managed_lobbies[lobby_channel]["skipModeSetup"] = True
	
	lobbies_changed = True

def irc_auth(the_name, the_pass=""):
	if the_pass != "":
		irc_send("PASS", the_pass, custom_delay=0)
	irc_send("NICK", the_name, custom_delay=0)
	irc_send("USER", the_name, the_name, the_name, the_name, custom_delay=0)

default_sender = "SERVER"
class Command():
	def __init__(self):
		global default_sender
		self.sender = default_sender
		self.raw = ""
		self.cmd = ""
		self.args = []
		self.message = ""
		self.channel = ""

def irc_to_Command(lines):
	cmds = []
	for line in lines:
		args_start = 1
		has_sender = False
		if line.find(":") == 0:
			args_start = 2
			has_sender = True
		
		expl = line.split(" ")
		
		cmd = Command()
		cmd.raw = line
		cmd.cmd = expl[args_start-1].upper()
		cmd.args = expl[args_start:]
		if has_sender:
			temp_sender = expl[0][1:]
			if temp_sender.find("!") != -1:
				temp_sender = temp_sender.split("!")[0]
			cmd.sender = temp_sender
		if cmd.cmd in [ "PRIVMSG", "001", "332", "372", "375", "376", "401", "403" ]:
			cmd.channel = expl[args_start]
			if (cmd.cmd in ["401", "403"]):
				cmd.channel = expl[args_start+1]
			temp_msg = " ".join(expl[(args_start+1):])
			if len(temp_msg) > 1:
				cmd.message = temp_msg[1:].replace(b'\x01ACTION'.decode(encoding), "[STATUS]").replace(b'\x01'.decode(encoding), "");
		
		cmds.append(cmd)
	
	return cmds

def irc_read():
	global irc, encoding, buffer_size
	text = ""
	
	
	reading = True
	while reading:
		remaining = ""
		
		try:
			remaining = irc.recv(buffer_size).decode(encoding, "replace")
		except Exception:
			pass
		
		if remaining == "":
			if (len(text) >= 2 and text[-1] == "\n") or text == "":
				reading = False
		else:
			remaining = remaining.replace("\r\n", "\n") # unstandard bancho fix
			text = text + remaining
	
	if text == "":
		return []
	else:
		return [x for x in text.split("\n") if len(x.strip()) > 0]

def reset_lobby_states():
	global global_playing, managed_lobbies, lobbies_changed
	
	global_playing = False
	for lobby in managed_lobbies:
		managed_lobbies[lobby]["ready"] = False
		managed_lobbies[lobby]["playing"] = False
	lobbies_changed = True
	
	print("Lobby states reset.")

def generate_new_password():
	global lobby_default_pass
	lobby_default_pass = ''.join(random.choice(string.ascii_letters) for i in range(10))
	print("A new default password has been generated.")

if console_input_allowed:
		print("--- HELP ---")
		print("")
		print("ESC: Quit")
		print("  c: Command")
		print("  t: Message")
		print("  r: Reply (last channel)")
		print("  b: Message BanchoBot")
		print("  p: Responsivity test")
		print("  n: Create lobby (+shift: auto-name)")
		print("  s: Send links")
		print("  x: Close lobby")
		print("  Q: RESET STATES")
		print("")

print("")
print("--- START ---")
print("")

with open(requests_file, "w") as json_file:
	json.dump({}, json_file)
print("Requests JSON reset.")

with open(report_file, "w") as outfile:
	json.dump({}, outfile)
print("Report JSON reset.")

managed_lobbies = {}
lobbies_changed = False
current_mapset = 0
current_mapset_default_beatmap = 0
api_player = ""
global_playing = False
global_rounds_played = 0
global_polling_rate = float(1)/polling_rate
global_last_mapset_played = -5
generate_new_password()
global_authenticated = False
boot_timestamp = math.floor(time.time())
current_irc_send_timeout = 0
global_irc_send_queue = []
print("First password: " + lobby_default_pass)

tick = True

print("Beginning authentication...")
irc_auth(bot_name, bot_pass)
last_channel = "BanchoBot"

running = True
while running:
	time.sleep(global_polling_rate)
	lines = irc_to_Command(irc_read())
	
	request_json = {}
	if path.exists(requests_file):
		with open(requests_file, "r") as json_file:
			request_json = json.load(json_file)
		if len(request_json) > 0:
			print("NEW REQUEST:", request_json)
			with open(requests_file, "w") as json_file:
				json.dump({}, json_file)

	for request in request_json:
		if "type" in request:
			if request["type"] == "invite" and "target" in request:
				filter = False;
				if "filter" in request:
					filter = fix_mp_prefix(request["filter"])
				
				irc_send_links(request["target"].replace(" ", "_"), filter)
			elif request["type"] == "new_lobby":
				if len(managed_lobbies) < global_max_lobbies:
					lobby_name = "Unalike"
					if "name" in request:
						lobby_name = request["name"]
					irc_send_pm("BanchoBot", "!mp make "+lobby_name)
			elif request["type"] == "close" and "target" in request:
				irc_close_lobby(request["target"])
			elif request["type"] == "set_delay" and "target" in request:
				if str(request["target"]).isnumeric() and request["target"] >= 1 and request["target"] <= 7:
					global_irc_send_timeout = float(request["target"])
					print("Global delay has been updated: " + str(global_irc_send_timeout))
			elif request["type"] == "results_fetched" and "target" in request:
				if request["target"] in managed_lobbies and "finished" in managed_lobbies[request["target"]]:
					managed_lobbies[request["target"]]["finished"] = False
					lobbies_changed = True
			elif request["type"] == "ping" and "target" in request:
				channel_id = fix_mp_prefix(request["target"])
				if channel_id in managed_lobbies:
					print("Pinging " + str(channel_id) + " by request.")
					irc_send_pm(channel_id, "[" + unalike_url + " Unalike > ] Someone pinged this lobby.")
			elif request["type"] == "sync_all":
				if current_mapset != 0 and current_mapset_default_beatmap != 0:
					print("Syncing all lobbies...")
					irc_sync_lobbies_to("WebUI", current_mapset, current_mapset_default_beatmap)
			elif request["type"] == "register" and "target" in request:
				print("Registering lobby: " + str(request["target"]))
				irc_start_managing(request["target"], join=True)
			elif request["type"] == "shutdown":
				running = False
				break
				
	for line in lines:
		if line.cmd in [ "001" ]:
			print(line.message)
			global_authenticated = True
		elif line.cmd == "PRIVMSG" and line.sender == "BanchoBot":
			if line.message.find("Created the tournament match ") != -1:
				new_lobby_info = line.message.replace("Created the tournament match https://osu.ppy.sh/mp/", "").split(" ")
				
				lobby_channel = new_lobby_info[0]
				lobby_name = " ".join(new_lobby_info[1:])
				last_channel = lobby_channel
				
				irc_start_managing(lobby_channel, lobby_name)
				
			elif line.message.find("The match has finished!") != -1 or line.message.find("Aborted the match") != -1:
				lobby_channel = line.channel
				print(line.channel + " ended.")
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["ready"] = False
				managed_lobbies[lobby_channel]["playing"] = False
				managed_lobbies[lobby_channel]["finished"] = True
				
				lobbies_changed = True
			elif line.message.find("Match starts in ") != -1 or line.message.find("Good luck, have fun!") != -1:
				pass
			elif line.message.find(" finished playing (") != -1:
				player = line.message.split(" finished playing (")[0]
				
			elif line.message.find("The match has started!") != -1:
				lobby_channel = line.channel
				print(line.channel + " started.")
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["playing"] = True
				
				lobbies_changed = True
			elif line.message.find("All players are ready") != -1:
				lobby_channel = line.channel
				print(line.channel + " is ready.")
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["ready"] = True
				
				lobbies_changed = True
			elif line.message.find(" left the game.") != -1:
				lobby_channel = line.channel
				player = line.message.split(" left the game.")[0]
				print(player + " left." + "(" + lobby_channel + ")")
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["ready"] = False
				if not ("players" in managed_lobbies[lobby_channel]):
					managed_lobbies[lobby_channel]["players"] = []
				managed_lobbies[lobby_channel]["players"].remove(player)
			elif line.message.find(" joined in slot ") != -1:
				lobby_channel = line.channel
				player = line.message.split(" joined in slot ")[0]
				print(player + " joined." + "(" + lobby_channel + ")")
				
				temp_api_player = player.replace(" ", "_")
				httpRequest = requests.get(unalike_api + "/" + temp_api_player + "/t/test")
				if httpRequest.text.find("YES") != -1:
					print("New API token provider elected: " + api_player + " -> " + temp_api_player)
					api_player = temp_api_player
				elif httpRequest.text.find("EXPIRING") != -1:
					irc_send_pm(temp_api_player, "Your API key is expiring soon. Please click [" + unalike_url + " here] to refresh it.")
				elif httpRequest.text.find("NO") != -1:
					irc_send_pm(temp_api_player, "Your API key has expired. Please click [" + unalike_url + " here] to refresh it.")
					irc_send_pm(lobby_channel, "!mp kick " + player)
				else:
					irc_send_pm(temp_api_player, "Unalike is having connection issues...")
					print("API connection issues for player (unalike-side!): " + player)
				
				
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["ready"] = False
				
				if not ("players" in managed_lobbies[lobby_channel]):
					managed_lobbies[lobby_channel]["players"] = []
				
				if len(managed_lobbies[lobby_channel]["players"]) < 1:
					irc_send_pm(line.channel, "!mp host " + player) # auto host
				
				managed_lobbies[lobby_channel]["players"].append(player)
			elif line.message.find("Changed the match password") != -1:
				lobby_channel = line.channel
				print("Password set in " + line.channel)
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["password"] = lobby_default_pass
				generate_new_password() # let's hope there won't be any random changes
				
				lobbies_changed = True
			elif line.message.find("Changed match settings to ") != -1:
				lobby_channel = line.channel
				lobby_settings = line.message.replace("Changed match settings to ", "").replace(" slots", "").split(", ") #Changed match settings to 1 slots, HeadToHead, Accuracy
				lobby_size = int(lobby_settings[0])
				lobby_mode = lobby_settings[1]
				lobby_win_condition = lobby_settings[2]
				
				print("New settings in " + line.channel + ":")
				print("    Win Condition: " + lobby_win_condition)
				print("    Mode: " + lobby_mode)
				print("    Size: " + str(lobby_size))
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["winCondition"] = lobby_win_condition
				managed_lobbies[lobby_channel]["mode"] = lobby_mode
				managed_lobbies[lobby_channel]["size"] = lobby_size
				
				lobbies_changed = True
			elif line.message.find("Changed match host to ") != -1:
				pass # already announced, this is a duplicate
			elif line.message.find(" became the host.") != -1:
				player = line.message.split(" became the host.")[0]
				print("Host changed: " + player)
			elif (line.message.find("Beatmap changed to:") != -1 or line.message.find("Changed beatmap to ") != -1) and line.message.find("osu.ppy.sh/b/"):
				lobby_channel = line.channel
				try:
					temp = line.message.split("osu.ppy.sh/b/")[1].replace(")", "")
					if temp.find(" ") != -1:
						temp = temp.split(" ")[0]
					beatmap_id = int(temp)
				except Exception:
					beatmap_id = -1
				
				http_request = requests.get(unalike_api + "/" + api_player + "/y/" + str(beatmap_id))
				if (http_request.text.isnumeric()):
					beatmapset_id = int(http_request.text)
				else:
					beatmapset_id = -1
				
				print("Beatmap changed to: " + str(beatmap_id) + " (" + str(beatmapset_id) + ")")
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["beatmap"] = beatmap_id
				managed_lobbies[lobby_channel]["ready"] = False
				
				if beatmapset_id > 0:
					managed_lobbies[lobby_channel]["beatmapset"] = beatmapset_id
				
				skipSync = False
				if "desynced" in managed_lobbies[lobby_channel]:
					if managed_lobbies[lobby_channel]["desynced"] == True:
						skipSync = True
				
				if skipSync:
					print("Skipping sync of " + str(lobby_channel) + "... (Reason: desynced)")
				else:
					if current_mapset != beatmapset_id:
						print("Syncing other lobbies to the new beatmapset... (" + str(current_mapset) + " previously)")
						current_mapset = beatmapset_id
						current_mapset_default_beatmap = beatmap_id
						irc_sync_lobbies_to(lobby_channel, current_mapset, current_mapset_default_beatmap)
					else:
						print("The beatmap is a part of the current set.")
				
				lobbies_changed = True
			elif line.message.find("Host is changing map...") != -1:
				lobby_channel = line.channel
				
				beatmap_id = -2
				print("Selecting beatmap...")
				
				if not (lobby_channel in managed_lobbies):
					managed_lobbies[lobby_channel] = {}
				
				managed_lobbies[lobby_channel]["beatmap"] = beatmap_id
				managed_lobbies[lobby_channel]["ready"] = False
				
				lobbies_changed = True
			else:
				print("[" + line.channel + "] " + line.sender + ": " + line.message)
		
		elif line.cmd == "PRIVMSG" and line.channel in managed_lobbies:
			if len(line.message) >= 1 and line.message[0] == ".":
				if line.message == ".close":
					irc_close_lobby(line.channel)
				elif line.message == ".desync":
					managed_lobbies[line.channel]["desynced"] = True
					print("Setting " + line.channel + " to desynced mode.")
					irc_send_pm(channel_id, "[" + unalike_url + " Unalike > ] The mapset will not sync while desynced.")
					lobbies_changed = True
				elif line.message == ".sync":
					managed_lobbies[line.channel]["desynced"] = False
					print("Setting " + line.channel + " to synced mode.")
					irc_send_pm(channel_id, "!mp map " + str(current_mapset_default_beatmap))
					lobbies_changed = True
		elif line.cmd == "PRIVMSG" and line.sender != "BanchoBot":
			print("[" + line.channel + "] " + line.sender + ": " + line.message)
		elif line.cmd in [ "401", "403" ]: # 401 = No such nick (bancho bug), 403 = No such channel
			irc_close_lobby(line.channel, True) # abandon = don't announce close (don't loop endlessly)
		elif line.cmd == "332": # Topic (has game number)
			if line.message.find("multiplayer game #") != -1:
				temp_topic = line.message.replace(":multiplayer game #", "").split(" ")
				lobby_id = int(temp_topic[1])
				channel_id = temp_topic[0]
				print("Received lobby ID (" + str(lobby_id) + ") for channel " + channel_id + ".")
				
				last_channel = channel_id
				
				if channel_id.find("#") == -1:
					channel_id = "#" + channel_id
				
				if not (channel_id in managed_lobbies):
					managed_lobbies[channel_id] = {}
				
				managed_lobbies[channel_id]["channel"] = channel_id
				managed_lobbies[channel_id]["lobby"] = lobby_id
					
				lobbies_changed = True
		elif line.cmd in [ "372", "375", "376", "333", "366", "353" ]:
			pass
		elif line.cmd == "QUIT":
			pass
		elif line.cmd in [ "JOIN", "PART" ]:
			pass # other bancho messages are more useful
		elif not (line.cmd in [ "PING", "MODE" ]):
			print("")
			print(line.raw)
			print(line.sender)
			print(line.cmd)
			print(" ".join(line.args))
			print("")
	
		if line.cmd == "PING":
			if len(line.args) >= 1:
				irc_send("PONG", line.args[0])
	
	if current_irc_send_timeout > 0:
		current_irc_send_timeout -= global_polling_rate
		if current_irc_send_timeout < 0:
			current_irc_send_timeout = 0
	elif len(global_irc_send_queue) > 0:
		irc_enqueued_obj = global_irc_send_queue.pop(0)
		if "line" in irc_enqueued_obj and "delay" in irc_enqueued_obj:
			irc_send_real(irc_enqueued_obj["line"], irc_enqueued_obj["delay"])
	
	
	if global_authenticated:
		temp_output = {}
		temp_output["lobbies"] = managed_lobbies
		temp_output["playing"] = global_playing
		temp_output["apiPlayer"] = api_player
		temp_output["roundsPlayed"] = global_rounds_played
		temp_output["timestamp"] = math.floor(time.time());
		temp_output["boot"] = boot_timestamp;
		temp_output["delay"] = global_irc_send_timeout;
		temp_output["maxLobbies"] = global_max_lobbies;
		temp_output["current_mapset"] = current_mapset;
		
		# uncomment to expose commands to the public
		# temp_output["command_queue"] = global_irc_send_queue;
		
		with open(report_file, "w") as outfile:
			json.dump(temp_output, outfile)
	
	if lobbies_changed:
		lobbies_changed = False
		all_ready = True
		all_finished = True
		if len(managed_lobbies) > 0:
			print("Managed lobbies (" + str(len(managed_lobbies)) + "):")
			for channel_id in managed_lobbies:
				print("    ", managed_lobbies[channel_id])
				if not ("configured" in managed_lobbies[channel_id]):
					managed_lobbies[channel_id]["configured"] = True
					managed_lobbies[channel_id]["ready"] = False
					managed_lobbies[channel_id]["playing"] = False
					managed_lobbies[channel_id]["channel"] = channel_id
					managed_lobbies[channel_id]["match"] = channel_id.replace("#mp_", "")
					managed_lobbies[channel_id]["roundsPlayed"] = 0
					managed_lobbies[channel_id]["players"] = []
					managed_lobbies[channel_id]["finished"] = False
					managed_lobbies[channel_id]["desynced"] = False
					managed_lobbies[channel_id]["lastSyncInit"] = 0
					if not ("skipModeSetup" in managed_lobbies[channel_id]):
						managed_lobbies[channel_id]["skipModeSetup"] = False
					
					print("Performing basic setup on " + channel_id + "...")
					irc_send_pm(channel_id, "!mp password " + str(lobby_default_pass))
					if not managed_lobbies[channel_id]["skipModeSetup"]:
						irc_send_pm(channel_id, "!mp set 0 1 " + str(lobby_default_size))
					
					lobbies_changed = True
				if managed_lobbies[channel_id]["ready"] == False:
					all_ready = False
				if managed_lobbies[channel_id]["playing"] == True:
					all_finished = False
				with open(lobbies_dir + managed_lobbies[channel_id]["match"] + ".json", "w") as json_file:
					json.dump(managed_lobbies[channel_id], json_file)
			if global_playing:
				if all_finished:
					print("All lobbies finished playing!")
					global_playing = False
					for channel_id in managed_lobbies:
						managed_lobbies[channel_id]["finished"] = False
						# irc_send_pm(channel_id, "No results system yet, but the message works. xD")
					global_rounds_played += 1
					
					request_matches = []
					for channel_id in managed_lobbies:
						if "roundsPlayed" in managed_lobbies[channel_id] and "match" in managed_lobbies[channel_id]:
							request_matches.append(str(managed_lobbies[channel_id]["match"]) + ",-1")
					
					composer_url = unalike_composer + "secret=" + local_secret + "&source=" + ";".join(request_matches) + "&beatmapset=" + str(current_mapset);
					
					print("Finished. Request forwarded to: Unalike Composer")
					
					time.sleep(global_composer_delay)
					requests.get(composer_url)
					
					lobbies_changed = True
			else:
				if all_ready:
					print("All lobbies are ready!")
					global_playing = True
					global_last_mapset_played = current_mapset
					temp_timer = len(managed_lobbies) * global_irc_send_timeout
					for channel_id in managed_lobbies:
						managed_lobbies[channel_id]["roundsPlayed"] += 1
						irc_send_pm(channel_id, "!mp start " + str(temp_timer))
						print("Started " + channel_id + " with a " + str(temp_timer) + " second timer.")
						temp_timer = temp_timer - global_irc_send_timeout
		else:
			print("No lobbies are managed by Unalike.")
	
	if console_input_allowed:
		if msvcrt.kbhit():
			pressed_button = msvcrt.getch()
			
			if pressed_button == b'\x1b': # ESC
				print("EXITING APPLICATION")
				break
			
			elif pressed_button == b'p': # p
				print("Pong!")
			
			elif pressed_button == b'c': # c
				print("COMMAND MODE")
				temp_input = input("Command: ")
				if temp_input.find(" "):
					temp_args = temp_input.split()
				else:
					temp_args = [temp_input, ""]
				irc_send(temp_args[0], " ".join(temp_args[1:]))
			
			elif pressed_button == b't': # t
				print("MESSAGE MODE")
				temp_channel = input("Channel/User: ")
				temp_message = input("Message: ")
				irc_send_pm(temp_channel, temp_message)

print("")
print("--- END ---")
print("")

print("Starting shutdown process...")

with open(requests_file, "w") as json_file:
	json.dump({}, json_file)
print("Requests JSON reset.")

to_close_list = []
for channel_id in managed_lobbies:
	to_close_list.append(channel_id)

if global_irc_send_timeout < 5:
	global_irc_send_timeout = 5

for channel_id in to_close_list:
	irc_close_lobby(channel_id)

irc_send("QUIT", custom_delay=0)

time.sleep(global_irc_send_timeout)
for irc_enqueued_obj in global_irc_send_queue:
	if "line" in irc_enqueued_obj and "delay" in irc_enqueued_obj:
		irc_send_real(irc_enqueued_obj["line"], irc_enqueued_obj["delay"])
		# print("SENT: " + str(irc_enqueued_obj["line"]).replace("\r\n", ""))
		time.sleep(irc_enqueued_obj["delay"])

with open(report_file, "w") as outfile:
	json.dump({}, outfile)
print("Report JSON reset.")

print("Quit request sent!")