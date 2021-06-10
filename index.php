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

require "displayed.php";
?>