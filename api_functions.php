<?php 

/**
 * @var Array Array of available annonmyous functions
 */
$functions = [];

// ============ User functions =============
$functions["user"] = [];

// /user/register
$functions["user"]["register"] = function(&$db) {
  if (isset($_POST["password"])) {

    // Validate password, at least 15 characters, one digit and at least 1 uppercase and lowercase character.
    // No symboles because this app is meant to be used on mobile, entering symboles on a phone
    // keyboard is annoying
    if (preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[A-Za-z\d]{15,}$/", $_POST["password"]) !== 1) {
      send_data(BAD,"Password needs to be 15 characters and one digit, uppercase character, and lowercase character");
    }

    // Hash a the ugly as sin php UUID. Salting is absolutely not necessary but I want too.
    $uuid = hash("sha256",uniqid("",true)."some interesting salt");

    // Should never need to check if user exists as uuid should never be a duplicate... "should".
    // There is a chance though.  1 / 1.1579x10⁷⁷ .... yeeeah.
    $query = $db->prepare("insert into users (uuid, password) values (:uuid, :password)");
    $query->bindParam(":uuid", $uuid);
    $newPass = password_hash($_POST["password"],PASSWORD_DEFAULT);
    $query->bindParam(":password", $newPass);

    $query->execute();

    send_data(OK, "Successfully created user", ["UUID" => $uuid]);

  } else {

    send_data(BAD, "Password required");

  }
};


// /user/login
$functions["user"]["login"] = function(&$db) {

  // Check user is not already logged in
  if (isset($_SESSION["uuid"])) send_data(FORBIDDEN, "You are already logged in");

  // Check post data
  if (!isset($_POST["password"])) send_data(BAD, "Password required");
  if (!isset($_POST["uuid"])) send_data(BAD, "UUID required");

  // Get user from the database
  $query = $db->prepare("select password from users where uuid = :uuid"); 
  $query->bindParam(":uuid", $_POST["uuid"]);
  $query->execute();

  $result = $query->fetchAll();

  // Duplicate found
  if (isset($result[1])) {
    error_log("Duplicate uuid found. Exiting. UUID: ".$_POST["uuid"]);
    die();
  }

  // If user doesnt exist
  if (!isset($result[0])) {
    send_data(BAD, "Invalid credentials"); 
  }

  // Check password is correct
  if (password_verify($_POST["password"], $result[0]["password"])) {
    
    $_SESSION["uuid"] = $_POST["uuid"];

    send_data(OK, "Logged in successfully");

  } else {

    send_data(BAD, "Invalid credentials");

  }

};


// /user/logout
$functions["user"]["logout"] = function(&$db) {

  // If there is no user logged in
  if (!isset($_SESSION["uuid"])) send_data(BAD, "You are not currently logged in");

  // Log out but keep session data
  $_SESSION["uuid"] = null;
  send_data(OK, "Successfully logged out");

};


// =======================================================
// ========== Old testing functions onwards ==============
// =======================================================

$functions["teapot"] = function() {
  send_data(418, "I am a teapot");
};

$functions["delay"] = function() {
  sleep(rand(0,3));
  send_data(BAD,"Delay");
};
