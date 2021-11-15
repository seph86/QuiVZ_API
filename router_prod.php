<?php 

// Route to API
if (preg_match("/^\/api/", $_SERVER["REQUEST_URI"])) {
  $_SERVER["REQUEST_URI"] = preg_replace("/^\/api/","",$_SERVER["REQUEST_URI"]);
  include "api.php";
  exit();
}

// Route to SSE
if (preg_match("/^\/sse/", $_SERVER["REQUEST_URI"])) {
  $_SERVER["REQUEST_URI"] = preg_replace("/^\/sse/","",$_SERVER["REQUEST_URI"]);
  include "sse.php";
  exit();
}

http_response_code(403);
//include "./QuiVZ_APP/build".$_SERVER["REQUEST_URI"];