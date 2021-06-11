<?php
session_start();
unset($_SESSION["unalike-osu-id"]);
unset($_SESSION["unalike-osu-username"]);
unset($_SESSION["unalike-granted"]);
header("Location: ../");