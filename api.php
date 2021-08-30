<?php 

define("OK",200);
define("BAD", 400);
define("NOTFOUND", 404);
define("FORBIDDEN",403);

session_start();

$conn = new PDO("sqlite:./db.db");

/**
 * Send json encoded data back to connected client
 */
function send_data(int $code, string $message, $data = null) {
  http_response_code($code);
  $temp = ["message"=>$message];
  if ($data != null) {
    array_push($temp,["data" => $data]);
  }
  echo json_encode($temp);
}

include "api_functions.php";

header("Content-Type: text/json");

if (!isset($action)) {
  send_data(400, "Invalid request");
  die;
}

if ($functions[$action] != null) {
  $functions[$action]($conn);
} else {
  send_data(400, "Invalid request");
}