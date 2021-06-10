<?php
session_start();
// http://zovguran.net/Unalike/logout
unset($_SESSION["unalike-osu-id"]);
unset($_SESSION["unalike-osu-username"]);
unset($_SESSION["unalike-granted"]);
header("Location: ../");