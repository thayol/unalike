<?php
// http://zovguran.net/Unalike/login/
include "../api_key.php";
$scope = implode("+", [ "identify", "public" ]);

$params = array(
	"client_id={$client_id}",
	"redirect_uri={$callback_uri}",
	"response_type=code",
	"scope={$scope}",
);

$url = "https://osu.ppy.sh/oauth/authorize?" . implode("&", $params);
header("Location: {$url}");