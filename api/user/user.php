<?php

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

	$_SESSION["uuid"] = $uuid;

    send_data(OK, "Successfully created user", ["UUID" => $uuid, "token" => session_id()]);

  } else {

    send_data(BAD, "Password required");

  }
};


// /user/login
$functions["user"]["login"] = function(&$db) {

  // Check user is not already logged in, if so return a response failing the login request
  if (isset($_SESSION["uuid"])) send_data(FORBIDDEN, "You are already logged in");

  // Check post data exists
  if (!isset($_POST["password"])) send_data(BAD, "Password required");
  if (!isset($_POST["uuid"])) send_data(BAD, "UUID required");

  // Get user from the database
  $query = $db->prepare("select password,admin from users where uuid = :uuid"); 
  $query->bindParam(":uuid", $_POST["uuid"]);
  $query->execute();

  $result = $query->fetchAll();

  // Duplicate found
  if (isset($result[1])) {
    // Panic 
    // TODO: add functionality to alert system admin
    error_log("FATAL ERROR: Duplicate uuid found. Exiting. UUID: ".$_POST["uuid"]);
    die();
  }

  // If user doesnt exist
  if (!isset($result[0])) {
    send_data(BAD, "Invalid credentials"); 
  }

  // Check password is correct
  if (password_verify($_POST["password"], $result[0]["password"])) {
    
    // Set user to be logged in and admin status if they are an admin
    $_SESSION["uuid"] = $_POST["uuid"];
    if ($result[0]["admin"] == "1") $_SESSION["admin"] = true;

    send_data(OK, "Logged in successfully", ["token" => session_id()]);

  } else {

    send_data(BAD, "Invalid credentials");

  }

};


// /user/newpassword
$functions["user"]["newpassword"] = function(&$db) {

  // Check user is logged in first
  if (!isset($_SESSION["uuid"])) send_data(BAD, "You are not currently logged in");

  // Check all for post data exists
  if (!isset($_POST["oldpassword"]) || !isset($_POST["newpassword"])) send_data(BAD, "You must fill in all fields");

  // Check new password is not the same as old
  if ($_POST["newpassword"] === $_POST["oldpassword"]) send_data(BAD, "New password cannot be the same as old");

  // Validate new password, same as /user/register requirements
  if (preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[A-Za-z\d]{15,}$/", $_POST["newpassword"]) !== 1) send_data(BAD,"Password needs to be 15 characters and one digit, uppercase character, and lowercase character");

  // Validate user by checking old password is correct before updating
  $query = $db->prepare("select * from users where uuid = :uuid");
  $query->bindParam(":uuid", $_SESSION["uuid"]);
  $query->execute();
  $rows = $query->fetchAll();

  // sanity checker
  if (count($rows) != 1) {
    // Something VERY wrong has happened. Panic
    error_log("FATAL ERROR: uuid:[{$_SESSION["uuid"]}] was able to access password reset even though they do not exist or there is more than one in the database.");
    die();
  }

  if (!password_verify($_POST["oldpassword"], $rows[0]["password"])) send_data(BAD, "Check old password");

  $query = $db->prepare("update users set password = :password where ID = :id");
  $newPass = password_hash($_POST["newpassword"],PASSWORD_DEFAULT);
  $query->bindParam(":password", $newPass);
  $query->bindParam(":id", $rows[0]["ID"]);
  $query->execute();

  send_data(OK, "Password changed");

};

// /user/loggedin
$functions["user"]["loggedin"] = function() {

  if (isset($_SESSION["uuid"]))
    send_data(OK, "You are logged in", ["token" => session_id()]);
  else 
    send_data(BAD, "You are not currently logged in", ["token" => session_id()]);

};

// /user/logout
$functions["user"]["logout"] = function() {

  // If there is no user logged in
  if (!isset($_SESSION["uuid"])) send_data(BAD, "You are not currently logged in");

  // Log out but keep session data
  session_destroy();
  send_data(OK, "Successfully logged out");

};