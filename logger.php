<?php 

// connect to logging database
$logger = new PDO("sqlite:./log.db");

//TODO: Change these from superglobals to...what?
$query = $logger->prepare("insert into log (timestamp, IP, action, post) values (:now, :IP, :action, :post);");

$now = time();
$query->bindParam(":now", $now);
$query->bindParam(":IP", $_SERVER["REMOTE_ADDR"]);
$query->bindParam(":action", $_SERVER["REQUEST_URI"]);
$postData = json_encode($_POST);
$query->bindParam(":post", $postData);
//TODO: Session
//TODO: User

$query->execute();


//TODO: Document this function
function appendResponse($code) {
  
  global $logger;

  $ID = $logger->lastInsertId();

  $query = $logger->prepare("update log set responseCode = :code where ID = ".$ID);
  $query->bindParam(":code", $code);
  $query->execute();
}
