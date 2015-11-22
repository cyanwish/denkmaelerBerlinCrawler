<?php

require 'config.php';

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
      $db_host = 'db.f4.htw-berlin.de';
      $db_name = '_s0544768__dmb';
      $db_user = getUser();
      $db_pass = getPw();
      try {
         $this->connection = new PDO("pgsql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
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
     * 
     */
    
    public function getMonument($id) {
        $statement = $this->connection->prepare('SELECT * FROM monuments WHERE city = :city');
        $statement->bindParam(':city', $city, PDO::PARAM_STR);
        $result = $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1) {
            $result = NULL;
        } else {
            $result = $statement->fetch();
        }
        return $result;
    }
    
    /**
     * 
     * @param type $data
     * @return type
     */
    
    public function saveMonument($data) {
        $sql = 'INSERT INTO monuments (name, obj_nr, descr, type, strasse, '
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
    
    /**
     * 
     */
    
    public function getUnusedId($table) {
        $statement = $this->connection->prepare('SELECT MAX(id) from :table');
        $statement->bindParam(':table', $table, PDO::PARAM_STR);
        $result = $statement->execute();
        $result = $statement->fetch();
        return $result + 1;
    }
    
    /**
     * Returns the type-id for the given type as string.
     * 
     * @param string $type name of the type
     */
    
    public function getTypeId($type){
        $statement = $this->connection->prepare('Select id from type where name = :type');
        $statement->bindParam(':type', $type, PDO::PARAM_STR);
        $result = $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1) {
            $result = NULL;
        } else {
            $result = $statement->fetch();
        }
        return $result;
    }
    
    /**
     * The function saves the given type in data to the db and returns the
     * id of the inserted type.
     * 
     * @param array $data   the data
     */
    
    public function saveType($data){
        $statement = $this->connection->prepare('INSERT INTO type (name) VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $data['denkmalart'], PDO::PARAM_STR);
        $result = $statement->execute();
        return $result;
    }
    
    /**
     *  The function inserts the given data into the db.
     * 
     *  @param       $data   the data
     *  @return      bool    true (success) or false (error)
     */
    
    public function saveObject($data){
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