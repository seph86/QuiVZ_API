<?php 

include_once "global_functions.php";

class logger {

  private $pdo;
  private $LastID;

  static private $incrementType = [
    "mysql"=>"AUTO_INCREMENT",
    "sqlite"=>"autoincrement",
  ];

  public static $instance = null;

  /** 
   * Construct log class and connect to logger.  Immediately log connection data
  */
  function __construct() {

    // Check settings file has log_db set
    if (!isset($_ENV["log_db"])) {
      error_log("ERROR: log_db not set in .settings file. Exiting");
      die(1);
    }

    $username = isset($_ENV["log_db_username"]) ? $_ENV["log_db_username"] : null;
    $password = isset($_ENV["log_db_password"]) ? $_ENV["log_db_password"] : null;

    try {

      $this->pdo = new PDO($_ENV["log_db"], $username, $password);

      // Construct schema if it does not exist
      if (!table_exists($this->pdo, "log")) {
        // Check we can use this sql datastore
        if (!isset(Logger::$incrementType[ $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ])) {
          error_log("This Log SQL datastore is not supported. - ".$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
          exit(1);
        }
        $incrementor = Logger::$incrementType[ $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ];
        $this->pdo->query("CREATE TABLE log (ID integer primary key ".$incrementor.", timestamp integer not null, IP text not null, action text, post text, session text, userID text, responseCode integer, responseData text, responseLine text);");
      }

      $query = $this->pdo->prepare("insert into log (timestamp, IP, action, post, session, userID) values (:now, :IP, :action, :post, :session, :userID);");

      $now = time();
      $query->bindParam(":now", $now);
      $query->bindParam(":IP", $_SERVER["REMOTE_ADDR"]);
      $query->bindParam(":action", $_SERVER["REQUEST_URI"]);
      $postData = $_POST;
      // remove passwords from post data before logging
      if (isset($postData["password"])) $postData["password"] = "[redacted]";
      if (isset($postData["oldpassword"])) $postData["oldpassword"] = "[redacted]";
      if (isset($postData["newpassword"])) $postData["newpassword"] = "[redacted]";
      if (isset($postData["password_confirm"])) $postData["password_confirm"] = "[redacted]";
      $postData = json_encode($postData);
      $query->bindParam(":post", $postData);
      $sessionid = session_id();
      $query->bindParam(":session", $sessionid);
      $userID = isset($_SESSION["uuid"]) ? $_SESSION["uuid"] : "";
      $query->bindParam(":userID", $userID);

      $query->execute();

      $this->LastID = $this->pdo->lastInsertId();


    } catch(PDOException $e) {
      error_log("ERROR: Cannot connect to log database file -");
      error_log($e);
      die(1);
    }

  }


  /**
   * Append response code to log for current action.
   * This is done at the end of send_data() function
   */
  public function appendResponse($code, $from):void {
    $query = $this->pdo->prepare("update log set responseCode = :code, responseLine = :line where ID = ".$this->LastID);
    $query->bindParam(":code", $code, pdo::PARAM_INT);
    $query->bindParam(":line", $from);
    $query->execute();
  }

  /**
   * Get number of requests from a single ip in the last second
   */
  public function getRequestCount():int {

    $query = $this->pdo->query("select count(timestamp) from log where IP = \"" . $_SERVER["REMOTE_ADDR"] . "\" and  timestamp = \"" . time() . "\"");
    return intval($query->fetch()[0]);

  }

  public function appendData($data) {
    $query = $this->pdo->prepare("update log set responseCode = :post where ID = ".$this->LastID);
    $query->bindParam(":post", $data);
    $query->execute();
  }

  public static function getInstance():logger {
    
    if (self::$instance == null) self::$instance = new logger();

    return self::$instance;

  }

}
