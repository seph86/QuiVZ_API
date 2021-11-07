<?php


// // ============ Quiz functions =============
$functions["quiz"] = [];


$functions["quiz"]["start_solo"] = function(&$db) {

  // Check user is logged in first
  if (!isset($_SESSION["uuid"])) send_data(BAD, "You are not currently logged in");


  $json_quiz = file_get_contents("https://opentdb.com/api.php?amount=10&category=15&difficulty=medium&type=multiple");

  if ($json_quiz === false) {
    error_log("ERROR: There was an issue connecting to Quiz Server");
    send_data(BROKEN, "There was an issue connecting to Quiz Server");
  }

  $data = json_decode($json_quiz, true)["results"];

  send_data(OK, "Game started", $data);

};