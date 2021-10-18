<?php

// ============ Category functions =============
$functions["category"] = [];

// /category/getall/
$functions["category"]["getall"] = function(&$db) {

  // Check request came from an authenticated user first
  if (!isset($_SESSION["uuid"])) send_data(UNAUTHORIZED);

  // Search the list of categories
  $query = $db->prepare("select name from categories");
  $query->execute();

  $output = Array();
  foreach ($query->fetchAll() as $value) {
    array_push($output, $value["name"]);
  }

  send_data(OK, "Categoies search result", $output);

};

// /category/search/
$functions["category"]["search"] = function(&$db) {

  // Check request came from an authenticated user first
  if (!isset($_SESSION["uuid"])) send_data(UNAUTHORIZED);

  // Check post data exists
  if (!isset($_POST["query"])) send_data(BAD);

  // Validate query does not contain any special characters except spaces
  if (preg_match("/^[A-z\ \d]+$/", $_POST["query"]) != 1) {
    send_data(BAD);
  }


  // Search the list of categories
  $query = $db->prepare("select name from categories where name like :query;");
  $_POST["query"] = "%{$_POST["query"]}%";
  $query->bindParam(":query", $_POST["query"]);
  $query->execute();

  $output = Array();
  foreach ($query->fetchAll() as $value) {
    array_push($output, $value["name"]);
  }

  send_data(OK, "Categoies search result", $output);

};


// /category/create/
$functions["category"]["create"] = function(&$db) {

  // Check user is logged in first
  if (!isset($_SESSION["uuid"])) send_data(UNAUTHORIZED);

  // Check post data exists
  if (!isset($_POST["name"])) send_data(BAD);

  // Validate category name is not more then 150 characters
  if (strlen($_POST["name"]) > 150) send_data(BAD);

  // Validate category name does not contain any special characters except spaces
  if (preg_match("/^[A-z\ \d]+$/", $_POST["name"]) != 1) send_data(BAD);

  // Verify this category doesn't already exist
  $query = $db->prepare("select * from categories where name = :name");
  $query->bindParam(":name", $_POST["name"]);
  $query->execute();

  if (count($query->fetchAll()) > 0) {
    send_data(BAD, "Category with that name already exsits");
  }

  // Validation complete, try to create the category
  $query = $db->prepare("insert into categories (name, owner) values (:name, :owner);");
  $query->bindParam(":name", $_POST["name"]);
  $query->bindParam(":owner", $_SESSION["uuid"]);
  $query->execute();

  send_data(OK, "Category created");
};


// /category/delete/
$functions["category"]["delete"] = function(&$db) {

  // Check user is admin
  if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != true) send_data(BAD);

  // Check post data exists
  if (!isset($_POST["name"])) send_data(BAD, "Invalid category name");

  // Validate category exists
  $query = $db->prepare("select * from categories where name = :name");
  $query->bindParam(":name", $_POST["name"]);
  $query->execute();
  $rows = $query->fetchAll();
  if (count($rows) == 0) send_data(BAD, "Category does not exist");

  // Run delete
  $query = $db->query("delete from categories where id = {$rows[0]["id"]}");

  send_data(OK, "Category successfully deleted");

};