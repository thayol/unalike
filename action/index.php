<?php
session_start();
// http://zovguran.net/Unalike/action/?(a|b|c)

// header("Content-Type: application/json");
// echo json_encode($_GET);

if (!empty($_SESSION["unalike-osu-id"]) && !empty($_SESSION["unalike-osu-username"]) && !empty($_SESSION["unalike-granted"]) && $_SESSION["unalike-granted"] === true)
{
	$username = $_SESSION["unalike-osu-username"];
	$username_irc = str_replace(" ", "_", $_SESSION["unalike-osu-username"]);
	$id = $_SESSION["unalike-osu-id"];
	
	if (isset($_GET["close"]))
	{
		if (!empty($_GET["target"]))
		{
			$target = $_GET["target"];
			$type = "close";
		}
	}
	else if (isset($_GET["ping"]))
	{
		if (!empty($_GET["target"]))
		{
			$target = $_GET["target"];
			$type = "ping";
		}
	}
	else if (isset($_GET["overtake"]))
	{
		if (!empty($_GET["target"]))
		{
			$target = $_GET["target"];
			$type = "register";
		}
		else
		{
			echo '<html><body><form action="./">';
			echo '
				<input type="hidden" name="overtake" value="form" />
				<input type="text" name="target" value="" placeholder="#mp_xxxxxxxx" />
				<input type="submit" value="Manage" /><br>
				<p>Make sure Unalike\'s user is a referee in the room.</p>
				<p><a href="../async/lobby/?">Lobby history</a> might be useful for regaining lost access.</p>
				';
			echo '</form><form action="../"><input type="submit" value="Back" /></form></body></html>';
			exit(0);
		}
	}
	else if (isset($_GET["delay"]))
	{
		if (!empty($_GET["target"]) && $_GET["target"] >= 1 && $_GET["target"] <= 7)
		{
			$target = floatval($_GET["target"]);
			$type = "set_delay";
		}
	}
	else if (isset($_GET["invite"]))
	{
		if (!empty($_GET["target"])) // TARGET IS THE FILTER HERE!
		{
			$filter = $_GET["target"];
		}
		$target = $username_irc;
		$type = "invite";
	}
	else if (isset($_GET["new"]))
	{
		$type = "new_lobby";
	}
	else if (isset($_GET["sync"]))
	{
		$type = "sync_all";
	}
	else if (isset($_GET["shutdown"]))
	{
		$type = "shutdown";
	}
	else if (isset($_GET["start"]))
	{
		$type = "start";
	}
	
	$action = $_GET;
	
	if (!empty($type))
	{
		
		$action = array(
			"type" => $type,
			"issuer" => [
				"username" => $username,
				"username_irc" => $username_irc,
				"id" => $id,
		]);
		
		if (!empty($target))
		{
			$action["target"] = $target;
		}
		
		if (!empty($filter))
		{
			$action["filter"] = $filter;
		}
		
		$json = json_decode(file_get_contents("../requests.json"), true);
		if (empty($json))
		{
			$json = array($action);
		}
		else
		{
			$json[] = $action;
		}
		file_put_contents("../requests.json", json_encode($json));
		
		header("Location: ../");
	}
	else
	{
		echo "Unknown action.";
	}
}
else
{
	echo "Authentication error.";
}