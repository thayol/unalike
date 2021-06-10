<?php
session_start();
// http://zovguran.net/Unalike/OAuth/

if (!empty($_GET["error"]) && $_GET["error"] == "access_denied")
{
	$_SESSION["unalike-osu-username"] = "Guest";
	$_SESSION["unalike-osu-id"] = -404;
	$_SESSION["unalike-granted"] = false;
	header("Location: ../");
	exit(0);
}
else if (empty($_GET["code"]))
{
	header("Location: ../login/");
	exit(0);
}

$code = $_GET["code"];
include "../api_key.php";
$final_uri = "http://zovguran.net/Unalike/";

$postdata = array(
	"client_id" => $client_id,
	"client_secret" => $client_secret,
	"code" => $code,
	"grant_type" => "authorization_code",
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
$auth_data = curl_exec($ch);
curl_close($ch);
$auth_json = json_decode($auth_data, true);

if (!empty($auth_data) && !empty($auth_json))
{
	$now = time();
	$expiry_date = $now + intval($auth_json["expires_in"]);
	$auth_json["expires_at"] = $expiry_date;
	$token = $auth_json["access_token"];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://osu.ppy.sh/api/v2/me/osu");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [ "Authorization: Bearer {$token}", "Content-type: application/json" ]);
	$profile_data = curl_exec($ch);
	curl_close($ch);
	$profile_json = json_decode($profile_data, true);
	
	if (!empty($profile_data) && !empty($profile_json))
	{
		$id = $profile_json["id"];
		file_put_contents("../users/{$id}.json", $profile_data);
		file_put_contents("../tokens/{$id}.json", json_encode($auth_json));
		
		
		$_SESSION["unalike-osu-username"] = $profile_json["username"];
		$_SESSION["unalike-osu-id"] = $profile_json["id"];
		$_SESSION["unalike-granted"] = true;
		
		$db = [];
		foreach (glob("../users/*.json") as $file)
		{
			unset($temp);
			$temp = json_decode(file_get_contents($file), true);
			$db[strtolower($temp["username"])] = $temp["id"];
			$db[strtolower(str_replace(" ", "_", $temp["username"]))] = $temp["id"];
		}
		
		$db["~Unalike"] = "~Unalike";
		
		file_put_contents("../users.json", json_encode($db));
		
		header("Location: ../");
	}
	else
	{
		echo "Authorization code accepted but user could not be identified.";
	}
}
else
{
	echo "Invalid authorization code.";
}
