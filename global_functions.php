<?php 

// Quick global definitions
define("OK",200);
define("BAD", 400);
define("UNAUTHORIZED", 401);
define("FORBIDDEN",403);
define("NOTFOUND", 404);
define("TIMEOUT", 408);
define("TOOMANY", 429);


// Allowed origins
$origin_whitelist = Array(
  "http://localhost:".$_SERVER["SERVER_PORT"] => true,
  "http://127.0.0.1:".$_SERVER["SERVER_PORT"] => true,
  "https://localhost:".$_SERVER["SERVER_PORT"] => true,
  "https://127.0.0.1:".$_SERVER["SERVER_PORT"] => true,
  //Testing
  "http://127.0.0.1:8000"=>true,
  "http://localhost:8000"=>true
);



/**
 * Send json encoded data back to connected client, then exit process
 */
function send_data(int $code, string $message = "", $data = null) {
  
  // Predefine a list of response messages
  $response_code_messages = Array(
    BAD => "Invalid Request",
    FORBIDDEN => "You are not allowed to do this",
    TOOMANY => "Too many requests",
    UNAUTHORIZED => "You are not allowed to do this"
  );

  // Set response code
  http_response_code($code);

  // Set message if it is not already and exists in the messages array
  if ($message === "" && array_key_exists($code, $response_code_messages))
    $message = $response_code_messages[$code];

  // Create a temp array ready to be encoded into JSON
  $temp = ["message"=>$message];

  // Append response code to logger
  appendResponse($code);

  // If data is set append it to the JSON array
  if ($data != null) {
    array_push($temp,["data" => $data]);
  }

  // Encode JSON data and send it
  echo json_encode($temp) . "\n";
  
  // Kill php, no further instruction to execute after this point
  die();
}