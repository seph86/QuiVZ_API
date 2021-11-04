<?php 

// Quick global definitions

// Response codes
define("OK",200);
define("BAD", 400);
define("UNAUTHORIZED", 401);
define("FORBIDDEN",403);
define("NOTFOUND", 404);
define("TIMEOUT", 408);
define("TOOMANY", 429);

// Misc
define("ADM_SECRET", "b14f6c79f8ac"); // Secret key to hash with the UUID if the user is admin, result is stored client side

// Allowed origins
$origin_whitelist = Array(
  "http://localhost:".$_SERVER["SERVER_PORT"] => true,
  "http://127.0.0.1:".$_SERVER["SERVER_PORT"] => true,
  "https://localhost:".$_SERVER["SERVER_PORT"] => true,
  "https://127.0.0.1:".$_SERVER["SERVER_PORT"] => true,
  //Testing
  "http://localhost:8000"=>true,
  "http://127.0.0.1:8000"=>true,
  "http://localhost:3000"=>true,
  "https://localhost:8000"=>true,
  "https://localhost:3000"=>true,
  "https://192.168.2.2:8000"=>true,
  "http://172.30.240.89:3000"=>true
);

// Restrict and privilages to those only on the following IPs
$admin_ip_whitelist = Array(
  "127.0.0.1"=>true
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
  logger::getInstance()->appendResponse($code);

  // If data is set append it to the JSON array
  // if ($data != null) {
  //   array_push($temp,["data" => $data]);
  // }
  $temp = $temp + ["data"=>$data];
  
  // Add debug information if enabled.  Only checking aginst False because
  // we only care if the environment variable is set, not what it's set too.
  if ( getenv("API_DEBUG", true) == true ) {
    $temp += ["backtrace"=>debug_backtrace()];
  }

  // Encode JSON data and send it
  echo json_encode($temp) . "\n";
  
  // Kill php, no further instruction to execute after this point
  die();
}
