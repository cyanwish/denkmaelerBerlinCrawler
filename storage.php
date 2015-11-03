<?php

/**
 *  This class provides functions for connecting with a database by using PDO.
 */

class Storage {
    
    private $connection;
    
    /**
     *  The constructor of the class.
     */
    
    public function __construct() {
      // insert your own credentials here
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
    
    /**
     *  The destructor of the class. 
     */
    
    public function __destruct() {
        $this->connection = null;
    }
    
    /**
     *  The function inserts the given data into the db.
     * 
     *  @param       $data   the data
     *  @return      bool    true (success) or false (error)
     */
    
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