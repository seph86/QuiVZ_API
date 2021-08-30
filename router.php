<?php 

// Extract URI elements into an array (empty elements excluded)
$input = array_values(array_filter(explode("/",$_SERVER["REQUEST_URI"]), 'strlen'));

// Set first item as "action"
if ($input[0] != null) {
  $action = $input[0];
}

// Set second item as the "command"
if ($input[1] != null) {
  $command = $input[1];
}

// echo $action . "\n";
// echo $command . "\n";

include "api.php";