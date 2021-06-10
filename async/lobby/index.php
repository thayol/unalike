<?php
session_start();
// http://zovguran.net/Unalike/async/

$relative_prefix = "../../lobbies/";
$original_suffix = ".json";
$users_suffix = $original_suffix;

$absolute_prefix = "/Unalike/lobbies/";

$call_prefix = "";
$call_suffix = "";

$recent_count = 6;

$selected_match = false;

$output = array();


$all_lobbies = glob($relative_prefix . "*" . $original_suffix);
natsort($all_lobbies);
$all_lobbies = array_reverse($all_lobbies);
$recent_lobbies = array_slice($all_lobbies, 0, $recent_count);


foreach ($recent_lobbies as $relative_file)
{
	$json = json_decode(file_get_contents($relative_file), true);
	if (!empty($json))
	{
		$output[] = $json;
	}
}

header("Content-Type: application/json");
echo json_encode($output);