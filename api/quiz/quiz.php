<?php

use Fuz\Component\SharedMemory\SharedMemory;
use Fuz\Component\SharedMemory\Storage\StorageFile;

// // ============ Quiz functions =============
$functions["quiz"] = [];

// start a solo game
$functions["quiz"]["start_solo"] = function(&$db) {

  // Request quiz from Open Trivia DB's API
  $json_quiz = file_get_contents("https://opentdb.com/api.php?amount=10&category=15&difficulty=medium&type=multiple");

  if ($json_quiz === false) {
    error_log("ERROR: There was an issue connecting to Quiz Server");
    send_data(BROKEN, "There was an issue connecting to Quiz Server");
  }

  $data = json_decode($json_quiz, true)["results"];

  send_data(OK, "Game started", $data);

};

// Challenge a friend
$functions["quiz"]["challenge"] = function(&$db, &$input) {

  // Check uuid is supplied
  if (!isset($input[1])) send_data(BAD, "UUID missing");

  // Be sure you're not trying to challenge yourself (use start_solo dummy)
  if ($input[1] == $_SESSION["uuid"]) send_data(BAD, "You cannot challenge yourself");

  $recipient_token = get_uuid_session($db, $input[1]);

  if ($recipient_token == false) send_data(BAD); //Invalid token

  // Set player to active game
  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);
  if ($shared->pendingGame != null) send_data(BAD, "Cannot challenge more than one person");

  // Notify recipient
  $storage = new StorageFile(sys_get_temp_dir()."/".$recipient_token);
  $shared = new SharedMemory($storage);
  $shared->challenge = ["expires" => time() + 5, "uuid" => $_SESSION["uuid"]]; //Send challenge that expires in 5 seconds

  send_data(OK, "Challenge sent");

};

// Accept challenge
$functions["quiz"]["accept"] = function(&$db, &$input) {

  if (!isset($input[1])) send_data(BAD, "UUID not set");

  //send notification to recipient
  $recipient_token = get_uuid_session($db, $input[1]);

  if ($recipient_token == false) send_data(BAD); //Invalid token

  // Load quiz data
  $json_quiz = file_get_contents("https://opentdb.com/api.php?amount=10&category=15&difficulty=medium&type=multiple");

  // Set challenger data
  $storage = new StorageFile(sys_get_temp_dir()."/".$recipient_token);
  $shared = new SharedMemory($storage);
  $shared->response = "true"; // Yes we want to have a game
  $shared->activeGame = true;
  $shared->opponent = $_POST["token"];
  $shared->ready = false;
  $shared->pendingGame = null;
  $shared->trigger = false;
  $shared->gameData = $json_quiz;


  // Set player data
  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);
  $shared->activeGame = true;
  $shared->opponent = $recipient_token;
  $shared->ready = false;
  $shared->gameData = $json_quiz;
  $shared->trigger = false;

  send_data(OK, "Response sent", $json_quiz);

};

// Reject Challenge
$functions["quiz"]["reject"] = function(&$db, &$input) {

  if (!isset($input[1])) send_data(BAD, "UUID not set");

  //send notification to recipient
  $recipient_token = get_uuid_session($db, $input[1]);

  if ($recipient_token == false) send_data(BAD); //Invalid token

  $storage = new StorageFile(sys_get_temp_dir()."/".$recipient_token);
  $shared = new SharedMemory($storage);
  $shared->response = "false";
  $shared->activeGame = null;

  send_data(OK, "Response sent");

};

// Get quiz
$functions["quiz"]["get"] = function(&$db) {

  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);
  if (!$shared->activeGame) send_data(BAD); // There is no active game

  send_data(OK, "Quiz", $shared->gameData);

};

// Player ready
$functions["quiz"]["ready"] = function(&$db, &$input) {

  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);
  if (!$shared->activeGame) send_data(BAD); // There is no active game

  $shared->ready = true;

  send_data(OK);

};

// Answer points
$functions["quiz"]["answer_points"] = function(&$db, &$input) {

  if (!isset($input[1])) send_data(BAD);
  if (!isset($input[2])) send_data(BAD);

  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);
  if (!$shared->activeGame) send_data(BAD); // There is no active game
  $shared->ready = true;

  $storage = new StorageFile(sys_get_temp_dir()."/".$shared->opponent);
  $shared = new SharedMemory($storage);
  $shared->opponentAnswered = $input[2];
  $shared->opponentPoints = $input[1];

  send_data(OK);

};


$functions["quiz"]["end_game"] = function(&$db, &$input) {

  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);
  if (!$shared->activeGame) send_data(BAD); // There is no active game
  $shared->activeGame = null;

  send_data(OK);

};
