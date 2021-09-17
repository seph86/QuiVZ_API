<?php

// ============ Admin functions =============
$functions["admin"] = [];

// NOTE: Admin functions to always return BAD if incorrectly used.  So that skimmers cannot 
// determin any admin functions.

// /admin/users/
$functions["admin"]["users"] = [];

// /admin/users/makeadmin
$functions["admin"]["users"]["makeadmin"] = function(&$db) {

  setAdmin($db, true);

};

// /admin/users/removeadmin
$functions["admin"]["users"]["removeadmin"] = function(&$db) {

  setAdmin($db, false);

};

function setAdmin(&$db, $admin) {

  // Check user is authorized
  if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != true) send_data(BAD);

  // Check UUID post is set
  if (!isset($_POST["uuid"])) send_data(BAD, "UUID Missing");

  // Validate input is a valid uuid code
  if (!preg_match("/^[A-z\d]+$/", $_POST["uuid"])) send_data(BAD, "Malformed UUID");

  // Check user exists and is not already admin
  $query = $db->prepare("select * from users where uuid = :uuid");
  $query->bindParam(":uuid", $_POST["uuid"]);
  $query->execute();
  $result = $query->fetchAll();
  if (count($result) == 0) {
    send_data(BAD, "UUID does not exist");
  } else { 
    if ($result[0]["admin"] == $admin) {
      send_data(BAD, "UUID is already ". ($admin ? "admin" : "not admin"));
    }
  }

  // TODO: Figure out how to make current active session with this uuid admin.
  $query = $db->prepare("update users set admin = " . intval($admin) . " where uuid = :uuid");
  $query->bindParam(":uuid", $_POST["uuid"]);
  $query->execute();

  send_data(OK, "User privilages set");

}