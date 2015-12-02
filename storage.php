<?php

require_once 'config.php';

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
      $db_name = '_s0544759__dmb';
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
    
    public function getMonument($obj_nr) {
        $statement = $this->connection->prepare('SELECT * FROM monument WHERE obj_nr = :obj_nr');
        $statement->bindParam(':obj_nr', $obj_nr, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    /**
     * 
     * @param type $data
     * @return type
     */
    
    public function insertMonument($monument){
        $placeholders = implode(',', array_fill(0, count($monument), '?'));
        $sql = "INSERT INTO monument (name, obj_nr, descr, type_id, super_monument_id, link_id)" .
               " VALUES ($placeholders) RETURNING id";
        $statement = $this->connection->prepare($sql);
        $statement->execute($monument);
        return $statement->fetch()['id'];
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
        return $result['id'];
    }
    
    /**
     * The function saves the given type in data to the db and returns the
     * id of the inserted type.
     * 
     * @param array $data   the data
     */
    
    public function insertType($type){
        $statement = $this->connection->prepare('INSERT INTO type (name) VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $type, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch()['id'];
    }
    
    public function getDistrictId($district){
        $statement = $this->connection->prepare('Select id from district where name = :district');
        $statement->bindParam(':district', $district, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertDistrict($district, $monumentId){
        $st_table = $this->connection->prepare('INSERT INTO district (name) VALUES (:name) RETURNING id');
        $st_table->bindParam(':name', $district, PDO::PARAM_STR);
        $st_table->execute();
        $districtId = $st_table->fetch()['id'];
        $st_rel = $this->connection->prepare('INSERT INTO district_rel (district_id, monument_id) ' .
                'VALUES (:district_id, :monument_id)');
        $st_rel->bindParam(':district_id', $districtId, PDO::PARAM_STR);
        $st_rel->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $st_rel->execute();
    }
    
    public function getSubDistrictId($subDistrict){
        $statement = $this->connection->prepare('Select id from sub_district where name = :sub_district');
        $statement->bindParam(':sub_district', $subDistrict, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id= $statement->fetch()['id'];
        return $id;
    }
    
    public function insertSubDistrict($subDistrict, $monumentId){
        $st_table = $this->connection->prepare('INSERT INTO sub_district (name) VALUES (:name) RETURNING id');
        $st_table->bindParam(':name', $subDistrict, PDO::PARAM_STR);
        $st_table->execute();
        $subDistrictId = $st_table->fetch()['id'];
        $st_rel = $this->connection->prepare('INSERT INTO sub_district_rel (sub_district_id, monument_id) ' .
                'VALUES (:sub_district_id, :monument_id)');
        $st_rel->bindParam(':sub_district_id', $subDistrictId, PDO::PARAM_STR);
        $st_rel->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $st_rel->execute();
    }
    
    public function getAddressId($address){
        $statement = $this->connection->prepare('Select id from address where street = :street AND nr = :nr');
        $statement->bindParam(':street', $address['street'], PDO::PARAM_STR);
        $statement->bindParam(':nr', $address['nr'][0], PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id= $statement->fetch()['id'];
        return $id;
    }
    
    public function insertAddress($address){
        $placeholders = implode(',', array_fill(0, count($address), '?'));
        $sql = "INSERT INTO address (lat, long, street, nr, monument_id)" .
               " VALUES ($placeholders)";
        $statement = $this->connection->prepare($sql);
        return $statement->execute($address);
    }
    
    public function getMonumentNotionId($monumentNotion){
        $statement = $this->connection->prepare('Select id from monument_notion where name = :monument_notion');
        $statement->bindParam(':monument_notion', $monumentNotion, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertMonumentNotion($monumentNotion, $monumentId){
        $st_table = $this->connection->prepare('INSERT INTO monument_notion (name) VALUES (:name) RETURNING id');
        $st_table->bindParam(':name', $monumentNotion, PDO::PARAM_STR);
        $st_table->execute();
        $monumentNotionId = $st_table->fetch()['id'];
        $st_rel = $this->connection->prepare('INSERT INTO monument_notion_rel (monument_notion_id, monument_id) ' .
                'VALUES (:monument_notion_id, :monument_id)');
        $st_rel->bindParam(':monument_notion_id', $monumentNotionId, PDO::PARAM_STR);
        $st_rel->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $st_rel->execute();
    }
    
    public function getPictureUrlId($picture){
        $statement = $this->connection->prepare('Select id from picture where url = :url');
        $statement->bindParam(':url', $picture, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;    
    }
    
    public function insertPictureUrl($pictureUrl, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO picture (url, monument_id) VALUES (:url, :monument_id)');
        $statement->bindParam(':url', $pictureUrl, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $statement->execute();
    }
    
    public function getDatingId($date){
        $statement = $this->connection->prepare('Select id from dating where beginning = :beginning AND ending = :ending');
        $statement->bindParam(':beginning', $date['beginning'], PDO::PARAM_STR);
        $statement->bindParam(':ending', $date['ending'], PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertDating($date, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO dating (beginning, ending, monument_id) '
                . 'VALUES (:beginning, :ending, :monument_id)');
        $statement->bindParam(':beginning', $date['beginning'], PDO::PARAM_STR);
        $statement->bindParam(':ending', $date['ending'], PDO::PARAM_STR);
        if(!isset($date['beginning']))
            $date['beginning'] = NULL;
        if(!isset($date['ending']))
             $date['ending'] = NULL;
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $statement->execute();
    }
    
    public function getParticipantId($participant){
        $statement = $this->connection->prepare('Select id from participant where name = :name');
        $statement->bindParam(':name', $participant, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function getParticipantTypeId($type){
        $statement = $this->connection->prepare('Select id from participant_type where name = :name');
        $statement->bindParam(':name', $type, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function getParticipantInRel($participantId, $typeId, $monumentId){
        $statement = $this->connection->prepare('Select id from participant_rel where '
                . 'monument_id = :monument_id AND participant_id = :participant_id AND '
                . 'participant_type_id = :participant_type_id');
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        $statement->bindParam(':participant_id', $participantId, PDO::PARAM_STR);
        $statement->bindParam(':participant_type_id', $typeId, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertParticipant($participant){
        $statement = $this->connection->prepare('INSERT INTO participant (name) '
                . 'VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $participant, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch()['id'];
    }
    
    public function insertParticipantInRel($participantId, $typeId, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO participant_rel (monument_id, participant_id, participant_type_id) ' .
                'VALUES (:monument_id, :participant_id, :participant_type_id)');
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        $statement->bindParam(':participant_id', $participantId, PDO::PARAM_STR);
        $statement->bindParam(':participant_type_id', $typeId, PDO::PARAM_STR);
        return $statement->execute();
    }
    
    public function insertParticipantType($type){
        $statement = $this->connection->prepare('INSERT INTO participant_type (name) VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $type, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch()['id'];
    }
}