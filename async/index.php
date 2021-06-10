<?php
session_start();
// http://zovguran.net/Unalike/async/

$unalike = json_decode(file_get_contents("../unalike.json"), true);
// if (!empty($unalike))
// {
	// $requests = json_decode(file_get_contents("../requests.json"), true);
	// if (!empty($requests))
	// {
		// $unalike["busy"] = true;
	// }
	// else
	// {
		// $unalike["busy"] = false;
	// }
// }

header("Content-Type: application/json");
echo json_encode($unalike);
