<?php 

require("vendor/autoload.php");

// Load env
if (file_exists("./.settings")) {

  $lines = file("./.settings");
  foreach( $lines as $num => $line) {
    if (!preg_match("/^#/", $line) && preg_match("/^\w+=\w+/", $line)){ // Add anything this is valid
      $line = preg_replace("/\n|\r|\ /", "", "$line"); // Remove newlines and spaces, then add to env
      $key = substr($line, 0, strpos($line, "="));
      $value = substr($line, strpos($line, "=") + 1);
      $_ENV[$key] = $value;
    }
  }

} else {
  error_log("Settings file missing");
  die(1);
}

// Extract URI elements into an array (empty elements excluded, then reindex)
$input = array_values(array_filter(explode("/",$_SERVER["REQUEST_URI"]), 'strlen'));

// Get if in production or debug mode
if (isset($_ENV["API_DEBUG"]) && $_ENV["API_DEBUG"] == "true") {
  
  // DEVELOPMENT
  session_save_path(getcwd()."/temp/");
  if (!file_exists(getcwd()."/temp/")) mkdir(getcwd()."/temp/");
  header("Access-Control-Allow-Origin: *");

} else {

  // PRDUCTION
  header("Access-Control-Allow-Origin: https://app.drawfunction.com");

}

// Include some multipurpose functions and definitions
include_once "global_functions.php";

// Include and start the logging system
include "logger.php";

// Rate limit requests from a single ip to 4 requests per second by counting log requests
if (logger::getInstance()->getRequestcount() > 4) send_data(TOOMANY);

// Check that there has been a API request
if (!isset($input[0])) {
  send_data(BAD);
}

// We need to start the session engine early because we need that information asap
ini_set("session.use_cookies", "0"); // We're going to work with tokens in localstorage. Not cookies

// Reject request if token is invalid
if ( isset($_POST["token"]) && !file_exists(session_save_path()."sess_".$_POST["token"]) ) {
  send_data(BAD);
}

// If there is no token check what the user is doing
if (!isset($_POST["token"])) { 
  // If they are trying to login or register, allow
  if ($input[0] === "user" && ($input[1] === "login" || $input[1] === "register"))
    $_POST["token"] = bin2hex(openssl_random_pseudo_bytes(20));
  else // Deny
    send_data(BAD);
}

// Start session
session_id($_POST["token"]);
session_start();
$_SESSION["remote_ip"] = $_SERVER["REMOTE_ADDR"];

// Capture the time the user was last active as a timestamp
$session_inactivty = time() - filemtime(session_save_path()."sess_".session_id());

// Set the response header to JSON content type
header("Content-Type: text/json");

// Check last time the session had any activity, if it's longer then 24 hours. Log out the user.
// Also log out the user if the ip has changed
if ($session_inactivty > 86400 || $_SESSION["remote_ip"] != $_SERVER["REMOTE_ADDR"]) {
  session_destroy();
  send_data(TIMEOUT, "Session expired");
}

// Rate limit requests from a single session to 1000 per 24 hours
if (isset($_SESSION["request_reset_timestamp"])) {

  // Increment counter
  if (isset($_SESSION["request_count"])) 
    $_SESSION["request_count"] = $_SESSION["request_count"] + 1;
  else
    $_SESSION["request_count"] = 1;

  // Is the session still within the time before reset and counter is over 1000
  if ($_SESSION["request_reset_timestamp"] > time() && $_SESSION["request_count"] > 999) 
    send_data(TOOMANY);
  
  // Reset items if 
  if (time() > $_SESSION["request_reset_timestamp"]) {
    $_SESSION["request_reset_timestamp"] = strtotime("+1 day", time());
    $_SESSION["request_count"] = 0;
  }

} else {
  $_SESSION["request_reset_timestamp"] = strtotime("+1 day", time());
}

// Limit requests from only specific origins
if (!isset($_SERVER["HTTP_ORIGIN"]) || !array_key_exists($_SERVER["HTTP_ORIGIN"], $origin_whitelist)){
  if (!isset($_ENV["API_DEBUG"]) || $_ENV["API_DEBUG"] != true )
    send_data(BAD);
} 


// ---------------------------------------
// Begin processing API requests
// ---------------------------------------

// Connect to the quiz db once early verification tests are completed
if (!isset($_ENV["quiz_db"])) {
  error_log("ERROR: quiz_db not set in .settings file. Exiting");
  die(1);
}

$username = isset($_ENV["quiz_db_username"]) ? $_ENV["quiz_db_username"] : null;
$password = isset($_ENV["quiz_db_password"]) ? $_ENV["quiz_db_password"] : null;

$quizDB = new PDO($_ENV["quiz_db"], $username, $password);

// Construct schema if it doesn't already exist
if (!table_exists($quizDB, "users")) {
  $quizDB->query("CREATE TABLE users (uuid varchar(32) primary key, password text, admin tinyint, token text);");error_log(var_export($quizDB->errorInfo(), true));
}

// We only want logged in users to be able to do anything, so unless they are checking logged in, logging in, or registring can they continue
if (!isset($_SESSION["uuid"]) && $input[0] == "user" && ($input[0] == "login" || $input[0] == "register" || $input[0] == "loggedin"))
  send_data(BAD, "You are not currently logged in");

// Set a flag that we will use later to change what error we output
$isAdminFunction = false;
if ($input[0] == "admin") {
  if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != true) send_data(UNAUTHORIZED);
  $isAdminFunction = true; 
}

// Load list of functions
include "./api/api_functions.php";

// Process input logic
while(1) {

  // Check the item we are processing exists first
  if (!isset($functions[$input[0]])) {
    // If we are in admin functions, we're going report unauthroized instead as so admin functions cannot be scraped from the api by fuzzing
    if ($isAdminFunction) 
      send_data(UNAUTHORIZED);
    else
      send_data(BAD); // The API has been used incorrectly
  }

  // Does the item point to an array?
  if ( is_array($functions[$input[0]]) ) {
    
    // Are we seeing admin functions?
    if ($input[0] == "admin") $isAdminFunction = true;

    // Yes, step further into the array
    $functions = $functions[$input[0]];
    // Pop off the first element of the URI array
    array_shift($input);

    // Loop
    continue;

  // otherwise it must be a function.  There should never be a case where a $function element is neither a array or a function
  } else {

    // Call the function.
    $functions[$input[0]]($quizDB, $input);

    // This should never get called because each function must have a send_data() which includes die(), but as a fallback...
    die(); //Never infinite loop

  }
}
