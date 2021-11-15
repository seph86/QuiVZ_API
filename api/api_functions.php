<?php 

// Use Shared memory library
use Fuz\Component\SharedMemory\SharedMemory;
use Fuz\Component\SharedMemory\Storage\StorageFile;

/**
 * @var Array Array of available annonmyous API functions
 */
$functions = [];

// Include subcategories
include "./api/user/user.php";
//include "./api/categories/categories.php";
include "./api/admin/admin.php";
include "./api/quiz/quiz.php";


// TODO: Maybe remove these superfluous functions
// =======================================================
// ========== Old testing functions onwards ==============
// =======================================================

$functions["teapot"] = function(&$db) {
  send_data(418, "I am a teapot");
};

$functions["test"] = function(&$db, &$input) {
  // Only allow logged in users
  if (!isset($_SESSION["uuid"])) send_data(BAD);
  
  // Load sync file
  $storage = new StorageFile(sys_get_temp_dir()."/".$_POST["token"]);
  $shared = new SharedMemory($storage);

  //if (!isset($input[1])) send_data(BAD, "Input needed");

  // if ($input[1] === "on") {
  //   $shared->test = true;
  // } else if ($input[1] === "off") {
  //   $shared->test = false;
  // } else {
  //   send_data(BAD, "Invalid input");
  // }

  $shared->test = "Message!";

  send_data(OK, "Sent");

};

// $functions["delay"] = function() {
//   sleep(rand(0,3));
//   send_data(BAD,"Delay");
// };
