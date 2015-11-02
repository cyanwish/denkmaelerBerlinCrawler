<?php

/**
 * db class 
 */

class Storage {
    
    private $connection;
    
    public function __construct() {
      $db_host = 'localhost';
      $db_name = 'denkmaelerBerlin';
      $db_user = 'root';
      $db_pass = '123';
      try {
         $this->connection = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
         $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      }
      catch (PDOException $e) {
         die($e->getMessage());
      }
    }
    
    public function __destruct() {
        $this->connection = null;
    }
    
    public function saveObject($data) {
        $sql = 'INSERT INTO objects (name, objektNr, bezirk, ortsteil, strasse, '
                . 'hausnummer, denkmalart, sachbegriff, datierung, entwurf, ausfuehrung, bauherr) '
                . 'VALUES (:name, :objektNr, :bezirk, :ortsteil, :strasse, :hausnummer, '
                . ':denkmalart, :sachbegriff, :datierung, :entwurf, :ausfuehrung, :bauherr)';
        $statement = $this->connection->prepare($sql);
        $statement->bindParam(':name', $data["Name"], PDO::PARAM_STR);
        $statement->bindParam(':objektNr', $data["Obj.-Dok.-Nr."], PDO::PARAM_STR);
        $statement->bindParam(':bezirk', $data["Bezirk"], PDO::PARAM_STR);
        $statement->bindParam(':ortsteil', $data["Ortsteil"], PDO::PARAM_STR);
        $statement->bindParam(':strasse', $data["Strasse"], PDO::PARAM_STR);
        $statement->bindParam(':hausnummer', $data["Hausnummer"], PDO::PARAM_STR);
        $statement->bindParam(':denkmalart', $data["Denkmalart"], PDO::PARAM_STR);
        $statement->bindParam(':sachbegriff', $data["Sachbegriff"], PDO::PARAM_STR);
        $statement->bindParam(':datierung', $data["Datierung"], PDO::PARAM_STR);
        $statement->bindParam(':entwurf', $data["Entwurf"], PDO::PARAM_STR);
        $statement->bindParam(':ausfuehrung', $data["Ausführung"], PDO::PARAM_STR);
        $statement->bindParam(':bauherr', $data["Bauherr"], PDO::PARAM_STR);
        return $statement->execute();
    }
    
}