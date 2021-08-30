<?php 

/**
 * @var Array Array of available annonmyous functions
 */
$functions = [];

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