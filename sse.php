<?php 

ignore_user_abort(true);

require("vendor/autoload.php");

// Load env
if (file_exists("./.settings")) {

  $lines = file("./.settings");
  foreach( $lines as $num => $line) {
    if (!preg_match("/^#/", $line) && preg_match("/^\w+=\w+/", $line)){ // Add anything this is valid
      $line = preg_replace("/\n|\r|\ /", "", "$line"); // Remove newlines and spaces, then add to env
      $temp = explode("=",$line);
      $_ENV[$temp[0]] = $temp[1];
    }
  }
  $temp = null;$line = null; // Clear temp data

} else {
  error_log("Settings file missing");
  die(1);
}

// Load logger
include "logger.php";

if (isset($_ENV["API_DEBUG"]) && $_ENV["API_DEBUG"] != false) {
  // DEVELOPMENT
  session_save_path(getcwd()."/temp/");
  header("Access-Control-Allow-Origin: *");
} else {
  session_save_path(getcwd()."/sess/");
  if (!file_exists(getcwd()."/sess/")) mkdir(getcwd()."/sess/");
  header("Access-Control-Allow-Origin: https://app.drawfunction.com");
}

// Reject if no token is used
if (!isset($_GET["token"])) {
  http_response_code(403);
  logger::getInstance()->appendResponse(403);
  exit();
}

// If token is not valid exit
if (!file_exists(session_save_path()."sess_".$_GET["token"])) {
  http_response_code(403);
  logger::getInstance()->appendResponse(403);
  logger::getInstance()->appendData("invalid token");
  exit();
}

// Use Shared memory library
use Fuz\Component\SharedMemory\SharedMemory;
use Fuz\Component\SharedMemory\Storage\StorageFile;

// Load sync file
$storage = new StorageFile(sys_get_temp_dir()."/".$_GET["token"]);
$shared = new SharedMemory($storage);

header("Cache-Control: no-cache");
header("Content-Type: text/event-stream");

// Loop here
while(true) {

  echo "\n";

  if ($shared->friendRequest != null) {
    echo "event: friendRequest\n";
    echo "data: ".json_encode($shared->friendRequest)."\n\n";
    $shared->friendRequest = null;
  }

  if ($shared->challenge) {

    // Check the challenge has expired
    if (time() > $shared->challenge["expires"]){
      $shared->challenge = null; 
    } else {
      echo "event: challenge\n";
      echo "data: ".$shared->challenge["uuid"]."\n\n";
      $shared->challenge = null; 
    }
  }

  if ($shared->response != null) {
    echo "event: response\n";
    echo "data: ".$shared->response."\n\n";
    $shared->response = null;
  }

  if ($shared->pendingGame != null) {
    if (time() > $shared->pendingGame["expires"]) {
      $shared->pendingGame = null;
      echo "event: response\ndata: false\n\n";
    }
  }

  if ($shared->activeGame != null) {

    // Is player ready?
    if ($shared->ready === true) {
      $tempStorage = new StorageFile(sys_get_temp_dir()."/".$shared->opponent);
      $tempShared = new SharedMemory($tempStorage);
      
      // Is opponent ready?
      if ($tempShared->ready === true) {

        // Trigger events for both (whoever gets here first sets the trigger)
        $tempShared->trigger = true;
        $shared->trigger = true;
        $tempShared->ready = false;
        $shared->ready = false;

      }

      $tempStorage = null;
      $tempShared = null;
    
    }

  }

  if ($shared->opponentDisconnect) {
    $shared->opponentDisconnect = null;
    $shared->opponent = null;
    $shared->activeGame = null;
    echo "event: opponentDisconnected\ndata: \n\n";
  }

  if ($shared->opponentAnswered != null) {
    echo "event: ".($shared->opponentAnswered == "true" ? "opponentRight":"opponentWrong")."\ndata: ".$shared->opponentPoints."\n\n";
    $shared->opponentAnswered = null;
  }

  if($shared->test) {
    // echo "event: update\n";
    // echo "data: test\n";
    $shared->ready = true;
    // Consume data
    $shared->test = null;
  } else {
    //
  }

  if ($shared->trigger === true) {
    // Force delay
    sleep(1);
    echo "event: trigger\ndata: \n\n";
    $shared->trigger = false;
  }

  // flush data
  if (ob_get_level() > 0) ob_end_flush();
  flush();

  // if connection closed then we quit
  if ( connection_aborted() || connection_status() != 0) {

    // error_log("connection ended===============");

    // Clean connection data
    $shared->activeGame = null;

    if ($shared->opponent != null) {
      $tempStorage = new StorageFile(sys_get_temp_dir()."/".$shared->opponent);
      $tempShared = new SharedMemory($tempStorage);
      $tempShared->opponentDisconnect = true;
    }

    exit();
  }

  usleep(100000);
}

exit();