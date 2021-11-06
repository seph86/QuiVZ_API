<?php 

require("vendor/autoload.php");

// Load env
if (file_exists("./.settings")) {

  $lines = file("./.settings");
  foreach( $lines as $num => $line) {
    if (!preg_match("/^#/", $line) && preg_match("/^\w+=\w+/", $line)) // Add anything this is valid
      putenv(preg_replace("/\n|\r|\ /", "", "$line")); // Remove newlines and spaces, then add to env
  }

} else {
  error_log("Settings file missing");
  die(1);
}

// Load logger
include "logger.php";

// Reject if no token is used
if (!isset($_GET["token"])) {
  http_response_code(400);
  logger::getInstance()->appendResponse(400);
  exit();
}

// If token is not valid exit
if (!file_exists(sys_get_temp_dir()."/".$_GET["token"])) {
  http_response_code(400);
  logger::getInstance()->appendResponse(400);
  exit();
}

// Use Shared memory library
use Fuz\Component\SharedMemory\SharedMemory;
use Fuz\Component\SharedMemory\Storage\StorageFile;

// Load sync file
$storage = new StorageFile(sys_get_temp_dir()."\"".$_GET["token"]);
$shared = new SharedMemory($storage);

header("Cache-Control: no-cache");
header("Content-Type: text/event-stream");

// Loop here
// while(true) {

//}

http_response_code(200);
exit();