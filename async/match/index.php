<?php
session_start();

$api_url = "http://localhost:80/Unalike/API";

$users_prefix = "../../users/";

$relative_prefix = "../../matches/";
$original_suffix = ".json";
$users_suffix = $original_suffix;

$absolute_prefix = "/Unalike/matches/";

$call_prefix = "";
$call_suffix = "";

$recent_count = 6;
$max_history_time_sec = 86400;

$selected_match = false;

$output = array();

if (!empty($_GET["select"]) && file_exists($relative_prefix . $_GET["select"] . $original_suffix))
{
	$selected_match = $relative_prefix . $_GET["select"] . $original_suffix;
}

if (!empty($selected_match))
{
	if (file_exists($selected_match))
	{
		$output = json_decode(file_get_contents($selected_match), true);
		if (!empty($output) && !empty($output["games"]) && !empty($output["games"][0]["scores"]))
		{
			foreach ($output["games"][0]["scores"] as $key => $score)
			{
				if (!empty($score["user_id"]))
				{
					$username = "Guest";
					if (file_exists($users_prefix . $score["user_id"] . $users_suffix))
					{
						$user = json_decode(file_get_contents($users_prefix . $score["user_id"] . $users_suffix), true);
						if (!empty($user) && !empty($user["username"]))
						{
							$username = $user["username"];
						}
					}
					else if (!empty($_SESSION["unalike-osu-id"]) && !empty($_SESSION["unalike-osu-username"]) && !empty($_SESSION["unalike-granted"]) && $_SESSION["unalike-granted"] === true)
					{
						$caller = str_replace(" ", "_", $_SESSION["unalike-osu-username"]);
						$url = $api_url . "/" . $caller . "/u/" . $score["user_id"];
						$raw = file_get_contents($url);
						$user = json_decode($raw, true);
						if (!empty($user) && !empty($user["username"]))
						{
							$username = $user["username"];
						}
					}
					$output["games"][0]["scores"][$key]["username"] = $username;
				}
			}
		}
	}
}
else
{
	$all_matches = glob($relative_prefix . "*" . $original_suffix);
	natsort($all_matches);
	$all_matches = array_reverse($all_matches);
	$recent_matches = array_slice($all_matches, 0, $recent_count);
	foreach ($recent_matches as $recent_key => $recent_file)
	{
		if ((time() - filemtime($recent_file)) > $max_history_time_sec)
		{
			unset($recent_matches[$recent_key]);
		}
	}

	$link_to_file = false;

	foreach ($recent_matches as $relative_file)
	{
		if ($link_to_file)
		{
			$output[] = str_replace($relative_prefix, $absolute_prefix, $relative_file);
		}
		else
		{
			$output[] = str_replace($original_suffix, $call_suffix, str_replace($relative_prefix, $call_prefix, $relative_file));
		}
	}
}

if (isset($_GET["render"]))
{
	$needed_beatmaps = array();
	foreach ($output["games"][0]["scores"] as $score)
	{
		$needed_beatmaps[] = $score["beatmap_id"];
	}
	
	$needed_beatmaps = array_unique($needed_beatmaps);
	
	$userid = 0;
	$beatmaps = array();
	if (!empty($_SESSION["unalike-osu-id"]) && !empty($_SESSION["unalike-osu-username"]) && !empty($_SESSION["unalike-granted"]) && $_SESSION["unalike-granted"] === true)
	{
		$caller = str_replace(" ", "_", $_SESSION["unalike-osu-username"]);
		$userid = $_SESSION["unalike-osu-id"];
		
		foreach ($needed_beatmaps as $beatmap_id)
		{
			$url = $api_url . "/" . $caller . "/b/" . $beatmap_id;
			$raw = file_get_contents($url);
			$beatmaps[$beatmap_id] = json_decode($raw, true);
			$beatmaps[$beatmap_id] = json_decode($raw, true);
		}
	}
	else
	{
		foreach ($needed_beatmaps as $beatmap_id)
		{
			$beatmaps[$beatmap_id] = array( // dummy
				"id" => $beatmap_id,
				"url" => "login/",
				"beatmapset" => [
					"title" => "Results",
				],
			);
		}
	}
	
	$sample_beatmap = array_key_first($beatmaps);
	
	$match_template = file_get_contents("match_template.html");
	$score_template = file_get_contents("score_template.html");
	
	$scores = "";
	
	foreach ($output["games"][0]["scores"] as $score)
	{
		$name = $score["username"];
		if ($userid == $score["user_id"])
		{
			$name = '<span class="positive-color">' . $name . '</span>';
		}
		$beatmap_id = $score["beatmap_id"];
		
		$all_objects = 0;
		if (!empty($beatmaps[$beatmap_id]["count_circles"])) $all_objects += $beatmaps[$beatmap_id]["count_circles"];
		if (!empty($beatmaps[$beatmap_id]["count_sliders"]) && $score["game"]["mode"] != "taiko") $all_objects += $beatmaps[$beatmap_id]["count_sliders"];
		if (!empty($beatmaps[$beatmap_id]["count_spinners"]) && $score["game"]["mode"] != "taiko") $all_objects += $beatmaps[$beatmap_id]["count_spinners"];
		
		$maxcombo = $score["maxcombo"];
		if (!empty($beatmaps[$beatmap_id]["max_combo"]))
		{
			if (isset($beatmaps[$beatmap_id]["convert"]) && $beatmaps[$beatmap_id]["convert"] != true)
			{
				$maxcombo .= "/" . $beatmaps[$beatmap_id]["max_combo"];
			}
		}
		
		$stars = 0;
		if (!empty($beatmaps[$beatmap_id]["difficulty_rating"]))
		{
			$stars = number_format($beatmaps[$beatmap_id]["difficulty_rating"], 2, '.', '');
		}
		
		$version = "";
		if (!empty($beatmaps[$beatmap_id]["version"]))
		{
			$version = $beatmaps[$beatmap_id]["version"];
		}
		
		$accuracy = number_format($score["accuracy"], 2, '.', '');
		$status = '';
		if ($all_objects > $score["all_notes"])
		{
			$status = '<span class="negative-color">QUIT</span>';
		}
		else if ($score["pass"] != 1)
		{
			$status = '<span class="negative-color">FAILED</span>';
		}
		else if (!empty($beatmaps[$beatmap_id]["max_combo"]) && $score["maxcombo"] >= $beatmaps[$beatmap_id]["max_combo"])
		{
			$status = '<span class="positive-color">FC</span>';
		}
		// else
		// {
			// $status = '<span class="positive-color">PASS</span>';
		// }
		
		$mode_text = "?";
		if ($score["game"]["mode"] == "osu")
		{
			// $mode_text = "standard";
			$mode_text = "osu";
		}
		else if ($score["game"]["mode"] == "taiko")
		{
			$mode_text = "taiko";
		}
		else if ($score["game"]["mode"] == "mania")
		{
			$mode_text = "mania";
		}
		else if ($score["game"]["mode"] == "fruits")
		{
			$mode_text = "catch";
		}
		// $mode_text = "osu!" . $mode_text;
		
		$score_replaced = str_replace(
			[
				"[[ PLACE ]]",
				"[[ NAME ]]",
				"[[ STATUS ]]",
				"[[ ACCURACY ]]",
				"[[ MODE ]]",
				"[[ MAXCOMBO ]]",
				"[[ STARS ]]",
				"[[ VERSION ]]",
			],
			[
				$score["place"],
				$name,
				$status,
				$accuracy,
				$mode_text,
				$maxcombo,
				$stars,
				$version,
			],
			$score_template);
		
		$scores .= $score_replaced;
	}
	
	$cover = "/Unalike/img/covers/c4.jpg";
	if (!empty($beatmaps[$sample_beatmap]["beatmapset"]["covers"]["cover@2x"]))
	{
		$cover = $beatmaps[$sample_beatmap]["beatmapset"]["covers"]["cover@2x"];
	}
	else if (!empty($beatmaps[$sample_beatmap]["beatmapset"]["covers"]["cover"]))
	{
		$cover = $beatmaps[$sample_beatmap]["beatmapset"]["covers"]["cover"];
	}
	
	$artist_components = array();
	if (!empty($beatmaps[$sample_beatmap]["beatmapset"]["artist"]))
	{
		$artist_components[] = $beatmaps[$sample_beatmap]["beatmapset"]["artist"];
	}
	if (!empty($beatmaps[$sample_beatmap]["beatmapset"]["creator"]))
	{
		$artist_components[] = $beatmaps[$sample_beatmap]["beatmapset"]["creator"];
	}
	
	$artist = "";
	if (!empty($artist_components))
	{
		$artist = implode(" // ", $artist_components);
	}
	
	$response = str_replace(
		[
			"[[ LINK ]]",
			"[[ TITLE ]]",
			"[[ ARTIST ]]",
			"[[ COVER_IMAGE ]]",
			"[[ SCORES ]]",
		],
		[
			$beatmaps[$sample_beatmap]["url"],
			$beatmaps[$sample_beatmap]["beatmapset"]["title"],
			$artist,
			$cover,
			$scores,
		],
		$match_template);
	
	echo $response;
}
else
{
	header("Content-Type: application/json");
	echo json_encode($output);
}