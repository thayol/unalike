# Unalike

A lobby manager IRC bot (Python) and web interface (PHP) that enables playing multiplayer with friends on different difficulties.

Live demo at [zovguran.net/Unalike][Unalike-URL]. (It might be broken sometimes because it's what I'm actively modifying.)

I know it's a bit messy but I wasn't planning on releasing the code. Initially, I wanted to make it open-source because of the [Bot Account System][osu-bot-account-forum] proposed by Peppy, but after testing it thoroughly, I have come to the conclusion that it is perfectly fine to run from user accounts.

The plan was to make it Python only, as it has built-in support for poviding a dedicated web server service, but in the end I fell back to my well known PHP knowledge.

By the way, I'm [Thayol][thayol-osu-url], feel free to ask me any questions anywhere.


## Features

- Allow users to log in with their own osu! accounts.
- Communicate with Bancho IRC while respecting the rate limits.
- Communicate with the osu! API v1 and v2 and caching the responses for less abuse.
- Monitor multiplayer results to aggregate the scores.
- Create osu! lobbies using a custom web interface.
- Send invites to users using the web interface.
- Synchronize the mapset across the lobbies.
- (Optinally) Write ".desync" in #multiplayer to turn off syncing temporarily.
- Display the results based on accuracy. (So that everyone has equal chance of winning.)
- Supports all game modes, and the score calculation is fully\* independent of the lobby settings.

*\* Score v2 might break a lobby.*

## Requirements

- A dedicated web service (Such as IIS, Apache, or XAMPP.)
- PHP 7.4.10 or greater (Untested on others, some features might break in PHP 8.)
- Python 3.2 or greater (Tested on Python 3.9.1.)


## Setup

1. Clone the source code to a subfolder of your web server.
2. Copy the files from "setup_help/" to the root of Unalike
3. Fill in the missing keys in the newly copied files.
4. (Optional) Check the configuration section of unalike.py
5. Set up a URL rewrite rule that makes "path/to/unalike/api/USERNAME/KEY/VALUE" point to "path/to/unalike/API/?username=USERNAME&KEY=VALUE" (You can just rewrite these calls manually if you want to, but using API parameters like this is very user friendly.)


## Start

1. Start start.ps1 with powershell.
2. Start your web service if it is stopped.

*(If it's your first time running start.ps1, the service will start automatically.)*


## Usage

1. Open the web page you deployed in a web browser.
2. Log in with you osu! account.
3. Press the "Start" button if Unalike is offline.
4. Press the "New lobby" button.
5. Make sure your osu! client is open and connected to Bancho.
6. Press the "Invite" button next of the newly created lobby.
7. Press F9 in your osu! client to open Bancho, then click the invite link received.
8. Wait for Unalike to give you the Host.
9. Pick a map, then ready up to start playing.
10. After playing, don't forget to press the "Shut down" button to save resources.

*(Optionally, you can replace step six with pressing the "Send invites" button.)*

## Known Bugs (or rather Features)

- When multiple people join the lobby, always the newly joined person gets the Host.
- If the map is changed in two or more lobbies simultaneously, some of the lobbies desync.
- You can play by yourself...
- When the lobbies are created, there is no map selected. When the joined user receives the host, it counts as a map change, so every other lobby will sync to the new mapset.
- If someone presses Escape early but they have played a map in the last round, they will receive a score with plus one point compared to the last run. (This is a bug in Bancho, which was last checked 2021-06-10)

[Unalike-URL]: http://zovguran.net/Unalike/
[osu-bot-account-forum]: https://osu.ppy.sh/wiki/en/Bot_account
[thayol-osu-url]: https://osu.ppy.sh/users/12416594
