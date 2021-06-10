<?php
include "../api_key.php";
include "../local_secret.php";

$trusted = false;

if (!empty($_GET["secret"]) && $_GET["secret"] == $local_secret)
{
	$trusted = true;
}

if ($trusted)
{
	$url_base = "http://localhost:80/Unalike/API/~Unalike/m/";
	
	$source = array();
	if (!empty($_GET["source"]))
	{
		foreach (explode(";", $_GET["source"]) as $list)
		{
			$entry = explode(",", $list);
			$lobby = array();
			
			if (isset($entry[0]) && isset($entry[1]))
			{
				$lobby["match"] = $entry[0];
				$lobby["round"] = $entry[1];
				$source[] = $lobby;
			}
		}
	}
	
	$beatmapset = -4;
	if (isset($_GET["beatmapset"]))
	{
		$beatmapset = $_GET["beatmapset"];
	}
	
	if (!empty($source) && isset($beatmapset))
	{
		$beatmap_url_v1 = "https://osu.ppy.sh/api/get_beatmaps?k={$api_key}&";
		
		$match_id = "ual" . strval(floor(time()));
		$merged = array(
			"match" => array(
				"match_id" => $match_id,
			),
			"games" => array(),
		);
		
		$merged_game = array(
			"game_id" => $match_id,
			"beatmapset_id" => $beatmapset,
			// "beatmapset" => [ "id" => $beatmapset ],
		);
		
		$modes = array(
			"0" => "osu",
			"1" => "taiko",
			"2" => "fruits",
			"3" => "mania",
		);
		
		$scores = array();
		foreach ($source as $lobby)
		{
			$url = $url_base . $lobby["match"];
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$data = curl_exec($ch);
			curl_close($ch);
			$json = json_decode($data, true);
			
			if (!empty($json) && 
				!empty($json["match"]) && 
				isset($json["match"]["name"]) && 
				!empty($json["games"]))
			{
				$real_round = array_key_first($json["games"]);
				$lobby["round"] = intval($lobby["round"]);
				if ($lobby["round"] > 0)
				{
					$real_round = intval($lobby["round"]) - 1;
				}
				else if ($lobby["round"] == -1)
				{
					$real_round = array_key_last($json["games"]);
				}
				
				
				if (!empty($json["games"][$real_round]) && 
					isset($json["games"][$real_round]["scores"]) )
				{
					$lobby_name = $json["match"]["name"];
					
					$bancho_game = $json["games"][$real_round];
					
					$lobby_beatmap = $bancho_game["beatmap_id"];
					
					$api_url = $beatmap_url_v1 . "b=" . $lobby_beatmap;
					
					
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $api_url);
					curl_setopt($ch, CURLOPT_HEADER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$api_response = curl_exec($ch);
					curl_close($ch);
					$api_json = json_decode($api_response, true);
					
					$mode = "osu";
					if (!empty($api_response) && !empty($api_json) && isset($api_json[0]["mode"]))
					{
						$mode = "osu";
						if ($api_json[0]["mode"] != "0")
						{
							$mode = $modes[$api_json[0]["mode"]];
						}
						else if (isset($bancho_game["play_mode"]) && $bancho_game["play_mode"] != "0")
						{
							$mode = $modes[$bancho_game["play_mode"]];
						}
					}
					
					
					foreach ($bancho_game["scores"] as $souce_score)
					{
						$score = array(
							"user_id" => $souce_score["user_id"],
							"place" => 0,
							"accuracy" => 0,
							"maxcombo" => 0,
							"beatmap_id" => "0",
							"score" => 0,
							"game" => [],
						);
						
						// $score["source"] = $souce_score;
						$score["source_id"] = $lobby["match"];
						$score["source_round"] = $real_round + 1;
						$score["lobby_name"] = $lobby_name;
						
						$score["beatmap_id"] = $lobby_beatmap;
						// $score["beatmap"] = array("id" => $lobby_beatmap);
						
						$score["game"]["play_mode"] = $bancho_game["play_mode"];
						$score["game"]["mode"] = $mode;
						$score["game"]["match_type"] = $bancho_game["match_type"];
						$score["game"]["scoring_type"] = $bancho_game["scoring_type"];
						$score["game"]["team_type"] = $bancho_game["team_type"];
						$score["game"]["mods"] = $bancho_game["mods"];
						
						$hit0 = intval($souce_score["countmiss"]);
						$hit50 = intval($souce_score["count50"]);
						$hit100 = intval($souce_score["count100"]);
						$hit300 = intval($souce_score["count300"]);
						$hitGeki = intval($souce_score["countgeki"]);
						$hitKatsu = intval($souce_score["countkatu"]);
						
						$score["countmiss"] = $hit0;
						$score["count50"] = $hit50;
						$score["count100"] = $hit100;
						$score["count300"] = $hit300;
						$score["countgeki"] = $hitGeki;
						$score["countkatu"] = $hitKatsu;
						
						if ($mode == "mania")
						{
							$all_notes = $hit0 + $hit50 + $hit100 + $hitKatsu + $hit300 + $hitGeki;
							$flat_score = ($hit50 * 50) + ($hit100 * 100) + ($hitKatsu * 200) + (($hit300 + $hitGeki) * 300);
							$max_score = $all_notes * 300;
						}
						else if ($mode == "fruits")
						{
							$all_notes = $hit0 + $hitKatsu + $hit50 + $hit100 + $hit300;
							$flat_score = $hit50 + $hit100 + $hit300;
							$max_score = $all_notes;
						}
						else if ($mode == "taiko")
						{
							$all_notes = $hit0 + $hit100 + $hit300;
							$flat_score = $hit100 + ($hit300 * 2);
							$max_score = $all_notes * 2;
						}
						else
						{
							$all_notes = $hit0 + $hit50 + $hit100 + $hit300;
							$flat_score = ($hit50 * 50) + ($hit100 * 100) + ($hit300 * 300);
							$max_score = $all_notes * 300;
						}
						
						$accuracy = round(($flat_score * 100) / $max_score, 2);
						
						$score["all_notes"] = $all_notes;
						$score["accuracy"] = $accuracy;
						$score["maxcombo"] = intval($souce_score["maxcombo"]);
						$score["score"] = intval($souce_score["score"]);
						$score["pass"] = intval($souce_score["pass"]);
						
						$scores[] = $score;
					}
				}
			}
		}
		
		usort($scores, function($a, $b) {
			if ($a["pass"] == $b["pass"])
			{
				if ($a["accuracy"] == $b["accuracy"])
				{
					if ($a["maxcombo"] == $b["maxcombo"])
					{
						return 0;
					}
					else if ($a["maxcombo"] < $b["maxcombo"])
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
				else if ($a["accuracy"] < $b["accuracy"])
				{
					return 1;
				}
				else
				{
					return -1;
				}
			}
			else if ($a["pass"] < $b["pass"])
			{
				return 1;
			}
			else
			{
				return -1;
			}
		});
		
		$counter = 1;
		foreach ($scores as $key => $score)
		{
			$scores[$key]["place"] = $counter++;
		}
		
		$merged_game["scores"] = $scores;
		
		$merged["games"][] = $merged_game;
		
		if (!file_exists("../matches"))
		{
			mkdir("../matches", 0777, true);
		}
		
		file_put_contents("../matches/{$match_id}.json", json_encode($merged));
		
		header("Content-Type: application/json");
		echo json_encode(["status" => "success"]);
		// echo json_encode($merged);
	}
	else
	{
		echo json_encode(["status" => "error", "error" => "missing_argument"]);
	}
}
else
{
	echo json_encode(["status" => "error", "error" => "unauthenticated"]);
}