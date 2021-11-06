<?php


// // ============ Quiz functions =============
$functions["quiz"] = [];


$functions["quiz"]["start_solo"] = function(&$db) {

  // Check user is logged in first
  if (!isset($_SESSION["uuid"])) send_data(BAD, "You are not currently logged in");


};