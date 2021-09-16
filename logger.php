<?php 

// connect to logging database
$logger = new PDO("sqlite:./log.db");


$query = $logger->prepare("insert into log (timestamp, IP, action, post, session, userID) values (:now, :IP, :action, :post, :session, :userID);");

$now = time();
$query->bindParam(":now", $now);
$query->bindParam(":IP", $_SERVER["REMOTE_ADDR"]);
$query->bindParam(":action", $_SERVER["REQUEST_URI"]);
$postData = $_POST;
// remove password from post before logging the data
if (isset($postData["password"])) $postData["password"] = "[redacted]";
$postData = json_encode($postData);
$query->bindParam(":post", $postData);
$sessionid = session_id();
$query->bindParam(":session", $sessionid);
$userID = isset($_SESSION["userID"]) ? $_SESSION["userID"] : "";
$query->bindParam(":userID", $userID);

$query->execute();


/**
 * Append response code to log for current action.
 * This is done at the end of send_data() function
 */
function appendResponse($code) {
  
  global $logger;

  $ID = $logger->lastInsertId();

  $query = $logger->prepare("update log set responseCode = :code where ID = ".$ID);
  $query->bindParam(":code", $code);
  $query->execute();
}
