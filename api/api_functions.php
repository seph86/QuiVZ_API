<?php 

/**
 * @var Array Array of available annonmyous API functions
 */
$functions = [];

// Include subcategories
include "./api/user/user.php";
include "./api/categories/categories.php";
include "./api/admin/admin.php";
include "./api/quiz/quiz.php";


// TODO: Maybe remove these superfluous functions
// =======================================================
// ========== Old testing functions onwards ==============
// =======================================================

$functions["teapot"] = function() {

  // Delete session if the token is not logged in
  if (!isset($_SESSION["uuid"]))
    session_destroy();

  send_data(418, "I am a teapot");
};

// $functions["delay"] = function() {
//   sleep(rand(0,3));
//   send_data(BAD,"Delay");
// };
