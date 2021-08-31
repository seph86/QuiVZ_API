<?php 

// ====================== DEVELOPMENT PURPOSES ========================== TODO: remove this in production
session_save_path(getcwd()."/temp/");

// Typical PHP stuff
session_start();

// Include and start the logging system
include "logger.php";

// Quick global definitions
define("OK",200);
define("BAD", 400);
define("UNAUTHORIZED", 401);
define("FORBIDDEN",403);
define("NOTFOUND", 404);
define("TOOMANY", 429);

// Extract URI elements into an array (empty elements excluded, then reindex)
$input = array_values(array_filter(explode("/",$_SERVER["REQUEST_URI"]), 'strlen'));

// Connect to the quiz db
$quizDB = new PDO("sqlite:./quiz.db");

/**
 * Send json encoded data back to connected client, then exit process
 */
function send_data(int $code, string $message, $data = null) {
  
  http_response_code($code);
  $temp = ["message"=>$message];

  appendResponse($code);

  if ($data != null) {
    array_push($temp,["data" => $data]);
  }
  echo json_encode($temp) . "\n";
  die();
}


// Load list of functions
include "api_functions.php";

// Set the header to JSON content type
header("Content-Type: text/json");

// Check that there has been a API request
if (!isset($input[0])) {
  send_data(BAD, "Invalid request");
}

// Process input logic
while(1) {

  // Check the item we are processing exists first
  if (!isset($functions[$input[0]]))
    send_data(BAD, "Invalid request"); // The API has been used incorrectly

  // Does the item point to an array?
  if ( is_array($functions[$input[0]]) ) {
    
    // Yes, step further into the array
    $functions = $functions[$input[0]];
    // Pop off the first element of the URI array
    array_shift($input);

    // Loop
    continue;

  // otherwise it must be a function.  There should never be a case where a $function element is neither a array or a function
  } else {

    // Call the function.
    $functions[$input[0]]($quizDB);

    // This should never get called because each function must have a send_data() which includes die(), but as a fallback...
    die(); //Never infinite loop

  }
}