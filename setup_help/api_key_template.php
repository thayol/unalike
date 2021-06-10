<?php
// for api v1
$api_key = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // osu! API v1 key, get one at https://osu.ppy.sh/p/api

// for api v2
$client_id = "1234"; // osu! API v2 client ID, register one at https://osu.ppy.sh/home/account/edit#new-oauth-application
$client_secret = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // v2 client secret, you will get one with registering a client ID
$callback_uri = "https://example.com/path/to/Unalike/OAuth/"; // the location where users will be redirected after logging in
// note that you will have to provide the same path when registering your client on the osu website!

// the comments are not needed for your live api_key.php file. the order of the lines are not important except for the very first line