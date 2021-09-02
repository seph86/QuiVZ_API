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

    // Validate password, at least 15 characters, one digit and at least 1 uppercase and lowercase character
    if (preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[A-Za-z\d]{15,}$/", $_POST["password"]) !== 1) {
      send_data(BAD,"Password needs to be 15 characters and one digit, uppercase character, and lowercase character");
    }

    // Hash a the ugly as sin php UUID. Salting is absolutely not necessary but I want too.
    $uuid = hash("sha256",uniqid("",true)."some interesting salt");

    // should never need to check if user exists as uuid should never be a duplicate... "should"
    // there is a chance though.  1 / 1.1579x10⁷⁷ .... yeeeah.
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
  if (isset($_SESSION["uuid"]))
    send_data(FORBIDDEN, "You are already logged in");

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

  if (password_verify($_POST["password"], $result[0]["password"])) {
    
    $_SESSION["uuid"] = $_POST["uuid"];

    send_data(OK, "Logged in successfully");

  } else {

    send_data(BAD, "Invalid credentials");

  }

};



// TODO: remove these
// ===================================================
// ========== Testing functions onwards ==============
// ===================================================

$functions["success"] = function () {
  send_data(OK, "Successful");
};

$functions["restricted"] = function() {
  send_data(FORBIDDEN, "You are not allowed to do this");
};

$functions["logged_in"] = function() {
  if (isset($_SESSION["user"]))
    send_data(OK, "You are logged in");
  else 
    send_data(NOTFOUND, "You are not");
};

$functions["login"] = function() {
  // send_data(200, var_dump($_POST)); die;
  if (empty($_POST["username"]) || empty($_POST["password"])) {
    send_data(BAD, "username or password not filled");
  } else {
    if ($_POST["username"] == "asdf" && $_POST["password"] == "asdf") {
      $_SESSION["user"] = "asdf";
      send_data(OK, "Logged in!");
    } else {
      send_data(406, "User credentials are incorrect");
    }
  }
};

$functions["logout"] = function() {
  session_destroy();
  send_data(OK, "You successfully logged out");
};

$functions["teapot"] = function() {
  send_data(418, "I am a teapot");
};

$functions["delay"] = function() {
  sleep(rand(0,3));
  send_data(OK,"Delay");
};

$functions["event"] = function(PDO &$conn) {
  send_data(OK, "Event Sent");
  $conn->exec("update users set fire = 1 where id = 0");
};