<?php

// ============ Admin functions =============
$functions["admin"] = [];

// NOTE: Admin functions to always return BAD if incorrectly used.  So that skimmers cannot 
// determin any admin functions.

// /admin/users/
$functions["admin"]["users"] = [];

/**
 * 
 * Setting admin prilvilages
 */
// /admin/users/makeadmin
$functions["admin"]["users"]["makeadmin"] = function(&$db, &$input) {

  setAdmin($db, true, $input);

};
// /admin/users/removeadmin
$functions["admin"]["users"]["removeadmin"] = function(&$db, &$input) {

  setAdmin($db, false, $input);

};
function setAdmin(&$db, $admin, &$input) {

  // Validate UUID was provided
  if (!isset($input[1])) send_data(BAD, "UUID Missing");

  // Validate input is a valid uuid code
  if (!preg_match("/^[A-z\d]+$/", $input[1])) send_data(BAD, "Malformed UUID");

  // Check user exists and is not already admin
  $query = $db->prepare("select * from users where uuid = :uuid");
  $query->bindParam(":uuid", $input[1]);
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
  $query->bindParam(":uuid", $input[1]);
  $query->execute();

  send_data(OK, "User privilages set");

}

/**
 * 
 * Listing all users
 */
// /admin/users/list
$functions["admin"]["users"]["list"] = function(&$db) {
  
  $query = $db->prepare("select uuid, admin from users");
  $query->execute();

  send_data(OK, "Users", $query->fetchAll(PDO::FETCH_NUM));

};