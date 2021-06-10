<?php
// http://zovguran.net/Unalike/API
include "../api_key.php";

if (!empty($_GET["username"]) || !empty($_GET["userid"]))
{
	$id = -1;
	$online = false;
	$token = "";
	
	if (!empty($_GET["username"]))
	{
		$username = strtolower($_GET["username"]);
		$users = json_decode(file_get_contents(getcwd()."/../users.json"), true);
		if (!empty($users[$username]))
		{
			$id = $users[$username];
		}
		else
		{
			$id = $username;
		}
	}
	else if (!empty($_GET["userid"]))
	{
		$id = $_GET["userid"];
	}
	
	if ($id == "~Unalike")
	{
	}
	else if ($id < 0)
	{
		echo "Unauthorized";
		exit(0);
	}
	
	$base_url_v2 = "https://osu.ppy.sh/api/v2";
	$base_url_v1 = "https://osu.ppy.sh/api";
	
	$diffs_redirect = false;
	$cross_mode = false; // single-output for Python
	// d = all [d]ifficulties
	// x = Python [x]ross mode (non-json output, single beatmapset)
	if (!empty($_GET["b"]) || !empty($_GET["d"]) || !empty($_GET["x"]))
	{
		if (!empty($_GET["x"]))
		{
			$beatmap = $_GET["x"];
			$cross_mode = true;
		}
		else if (!empty($_GET["d"]))
		{
			$beatmap = $_GET["d"];
			$diffs_redirect = true;
		}
		else
		{
			$beatmap = $_GET["b"];
		}
		$url = $base_url_v2 . "/beatmaps/{$beatmap}";
		
		$cache_as = array(
			"type" => "beatmap-v2",
			"id" => $beatmap,
		);
	}
	
	if (!empty($_GET["u"]))
	{
		if (!empty($_GET["u"]))
		{
			$user = $_GET["u"];
		}
		
		$url = $base_url_v2 . "/users/{$user}/osu";
		
		$cache_as = array(
			"type" => "user",
			"id" => $user,
		);
	}
	
	$legacy_mode = false;
	// y = [y] u do dis
	if (!empty($_GET["y"]))
	{
		$beatmap = $_GET["y"];
		$cross_mode = true;
		$legacy_mode = true;
		$url = $base_url_v1 . "/get_beatmaps?k={$api_key}&b={$beatmap}";
		
		$cache_as = array(
			"type" => "beatmap",
			"id" => $beatmap,
		);
	}
	
	$filter_diffs = false;
	// f = difficulty id only [f]ilter
	if (!empty($_GET["s"]) || !empty($_GET["f"]))
	{
		if (!empty($_GET["f"]))
		{
			$beatmapset = $_GET["f"];
			$filter_diffs = true;
		}
		else
		{
			$beatmapset = $_GET["s"];
		}
		$url = $base_url_v1 . "/get_beatmaps?k={$api_key}&s={$beatmapset}";
		
		$cache_as = array(
			"type" => "beatmapset",
			"id" => $beatmapset,
		);
	}
	
	// m = [m]ultiplayer match
	if (!empty($_GET["m"]))
	{
		$lobby = $_GET["m"];
		$url = $base_url_v1 . "/get_match?k={$api_key}&mp={$lobby}";
	}
	
	if (strtolower($id) == "~unalike")
	{
		$token = "not-v2-so-no-key-needed-sry-peppy-for-including-this-field";
		$online = true;
	}
	else
	{
		$token_file = getcwd() . "/../tokens/{$id}.json";
		if (file_exists($token_file))
		{
			$token_json = json_decode(file_get_contents($token_file), true);
			if (!empty($token_json["access_token"]))
			{
				$token = $token_json["access_token"];
				$online = true;
			}
		}
	}
	
	
	// t = [t]est api access
	if (!empty($_GET["t"]))
	{
		if (!empty($token_json["expires_at"]) && $token_json["expires_at"] > (time()+3600))
		{
			echo "YES";
		}
		else if (!empty($token_json["expires_at"]) && $token_json["expires_at"] > time())
		{
			echo "YES+EXPIRING";
		}
		else
		{
			echo "NO";
		}
		exit(0);
	}
	
	if (!empty($cache_as["type"]) && !empty($cache_as["id"]))
	{
		$cache_type = $cache_as["type"];
		$cache_id = $cache_as["id"];
		$cache_location = "./cache/{$cache_type}/{$cache_id}.json";
	}
	
	if (!empty($cache_location) && file_exists($cache_location))
	{
		$data = file_get_contents($cache_location);
	}
	else if ($online && isset($url) && !empty($token))
	{
		// echo '<html style="background:black;color:white;font-family:sans-serif;"><body><pre style="font-family:sans-serif;">';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ "Authorization: Bearer {$token}", "Content-type: application/json" ]);
		$data = curl_exec($ch);
		curl_close($ch);
		
		if (!empty($cache_location) && ($data != "{\"authentication\":\"basic\"}"))
		{
			if (!file_exists(dirname($cache_location)))
			{
				mkdir(dirname($cache_location), 0777, true);
			}
			
			file_put_contents($cache_location, $data);
		}
	}
	
	if (!empty($data))
	{
		if ($diffs_redirect || $cross_mode)
		{
			$json = json_decode($data, true);
			if ($legacy_mode)
			{
				if (!empty($json[0]["beatmapset_id"]))
				{
					$json["beatmapset"] = array("id" => $json[0]["beatmapset_id"]);
				}
			}
			if (!empty($json["beatmapset"]["id"]))
			{
				$beatmapset = $json["beatmapset"]["id"];
				if ($cross_mode)
				{
					echo $beatmapset;
					exit(0);
				}
				else if ($diffs_redirect)
				{
					header("Location: " . "../f/" . strval($beatmapset));
				}
			}
		}
		
		header("Content-type: application/json");
		if ($filter_diffs)
		{
			$output = array(
				"id" => $beatmapset,
				"artist" => "",
				"title" => "",
				"creator" => "",
				"beatmaps" => [],
			);
			
			$modes = array(
				"0" => "osu",
				"1" => "taiko",
				"2" => "fruits",
				"3" => "mania",
			);
			
			$temp_beatmaps = array();
			$json = json_decode($data, true);
			foreach ($json as $map)
			{
				if (!empty($map["beatmap_id"]) && 
					!empty($map["approved"]) && 
					!empty($map["difficultyrating"]) && 
					!empty($map["artist"]) && 
					!empty($map["title"]) &&
					!empty($map["version"]) &&
					!empty($map["max_combo"]))
				{
					$output["artist"] = $map["artist"];
					$output["title"] = $map["title"];
					$output["creator"] = $map["creator"];
					
					$map_output = array(
						"id" => intval($map["beatmap_id"]),
						"ranked" => intval($map["approved"]),
						"difficulty_rating" => floatval($map["difficultyrating"]),
						"version" => $map["version"],
						"artist" => $map["artist"],
						"title" => $map["title"],
						"creator" => $map["creator"],
						"max_combo" => $map["max_combo"],
					);
					
					if (in_array($map["mode"], array_keys($modes))) // v2 compliance
					{
						$map_output["mode"] = $modes[$map["mode"]];
					}
					
					$temp_beatmaps[] = $map_output;
				}
			}
			
			usort($temp_beatmaps, function($a, $b) {
				return $a["difficulty_rating"] > $b["difficulty_rating"] ? 1 : ($a["difficulty_rating"] < $b["difficulty_rating"] ? -1 : 0);
			});
			$output["beatmaps"] = $temp_beatmaps;
			
			echo json_encode($output);
		}
		else
		{
			echo $data;
		}
	}
	else
	{
		echo json_encode([]);
	}
	
	
}
else
{
	echo "No authentication method was defined.";
}