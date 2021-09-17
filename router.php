<?php 

// Testing code block
header("Access-Control-Allow-Origin: http://127.0.0.1:8080");

//if (isset($_SERVER["HTTP_ORIGIN"])) error_log($_SERVER["HTTP_ORIGIN"]); 
// die();
// Testing code block end

// The router "redirects" all traffic to the API (api.php)
include "api.php";