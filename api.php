<?php 

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


// Get if in production or debug mode
if (getenv("API_DEBUG", true) != false) {
  
  // DEVELOPMENT
  session_save_path(getcwd()."/temp/");
  if (!file_exists(getcwd()."/temp/")) mkdir(getcwd()."/temp/");
  header("Access-Control-Allow-Origin: *");

} else {

  // PRDUCTION
  // TODO: Set prod mode here

}

// Include some multipurpose functions and definitions
include "global_functions.php";

// Include and start the logging system
include "logger.php";

// Rate limit requests from a single ip to 4 requests per second by counting log requests
if (logger::getInstance()->getRequestcount() > 4) send_data(TOOMANY);  // Are there more than 4 requests in the last second?

// We need to start the session engine early because we need that information asap
ini_set("session.use_cookies", "0"); // We're going to work with tokens in localstorage. Not cookies
$session_error = false;

// Destroy posted token if it is invalid.  We'll make our own later
if ( isset($_POST["token"]) && !file_exists(session_save_path()."sess_".$_POST["token"]) ) {
  $_POST["token"] = null;
}

// If token is missing build one, otherwise start session with valid token
if (!isset($_POST["token"])) 
  session_id(bin2hex(openssl_random_pseudo_bytes(20)));
else
  session_id($_POST["token"]);

// Start session
session_start();
$_SESSION["remote_ip"] = $_SERVER["REMOTE_ADDR"];

// If an invalid token was used, respond now with with 400 after the logger has initialized.
if ($session_error) send_data(TIMEOUT);

// Capture the time the user was last active as a timestamp
$session_inactivty = time() - filemtime(session_save_path()."sess_".session_id());

// Set the response header to JSON content type
header("Content-Type: text/json");

// Check last time the session had any activity, if it's longer then 2 hours. Log out the user.
// Also log out the user if the ip has changed
if ($session_inactivty > 7200 || $_SESSION["remote_ip"] != $_SERVER["REMOTE_ADDR"]) {
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
  send_data(BAD);
} 


// ---------------------------------------
// Begin processing API requests
// ---------------------------------------

// Extract URI elements into an array (empty elements excluded, then reindex)
$input = array_values(array_filter(explode("/",$_SERVER["REQUEST_URI"]), 'strlen'));

// Connect to the quiz db once early verification tests are completed
$quizDB = new PDO("sqlite:./quiz.db");

// Load list of functions
include "./api/api_functions.php";

// Check that there has been a API request
if (!isset($input[0])) {
  send_data(BAD);
}

// Set a flag that we will use later to change what error we output
$isAdminFunction = false;
if ($input[0] == "admin") {
  if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != true) send_data(UNAUTHORIZED);
  $isAdminFunction = true; 
}

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
