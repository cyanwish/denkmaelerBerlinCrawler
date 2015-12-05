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
        $sql = "INSERT INTO monument (name, obj_nr, descr, type_id, super_monument_id, link_id, dating_id)" .
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
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1) {
            $id = NULL;
        } else {
            $id = $statement->fetch()['id'];
        }
        return $id;
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
    
    public function getDistrictInRel($districtId, $monumentId){
        $statement = $this->connection->prepare('Select id from district_rel '
                . 'where district_id = :district_id AND monument_id = :monument_id ');
        $statement->bindParam(':district_id', $districtId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id= $statement->fetch()['id'];
        return $id;
    }
    
    public function insertDistrict($district){
        $statement = $this->connection->prepare('INSERT INTO district (name) VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $district, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch()['id'];
    }
    
    public function insertDistrictInRel($districtId, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO district_rel (district_id, monument_id) ' .
                'VALUES (:district_id, :monument_id)');
        $statement->bindParam(':district_id', $districtId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $statement->execute();   
    }
    
    public function getSubdistrictId($subDistrict){
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
    
    public function getSubdistrictInRel($subdistrictId, $monumentId){
        $statement = $this->connection->prepare('Select id from sub_district_rel '
                . 'where sub_district_id = :sub_district_id AND monument_id = :monument_id ');
        $statement->bindParam(':sub_district_id', $subdistrictId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id= $statement->fetch()['id'];
        return $id;
    }
    
    public function insertSubdistrict($subdistrict){
        $statement = $this->connection->prepare('INSERT INTO sub_district (name) VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $subdistrict, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch()['id'];
        
    }
    
    public function insertSubdistrictInRel($subdistrictId, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO sub_district_rel (sub_district_id, monument_id) ' .
                'VALUES (:sub_district_id, :monument_id)');
        $statement->bindParam(':sub_district_id', $subdistrictId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $statement->execute();
    }
    
    public function getAddressId($address){
        $statement = $this->connection->prepare('Select id from address where street = :street AND nr = :nr');
        $statement->bindParam(':street', $address['street'], PDO::PARAM_STR);
        $statement->bindParam(':nr', $address['nr'], PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function getAddressInRel($addressId, $monumentId){
        $statement = $this->connection->prepare('Select id from address_rel where monument_id = :monument_id AND address_id = :address_id');
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        $statement->bindParam(':address_id', $addressId, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertAddress($address){
        $placeholders = implode(',', array_fill(0, count($address), '?'));
        $sql = "INSERT INTO address (lat, long, street, nr)" .
               " VALUES ($placeholders) RETURNING id";
        $statement = $this->connection->prepare($sql);
        $statement->execute($address);
        return $statement->fetch()['id'];
    }
    
    public function insertAddressInRel($addressId, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO address_rel (monument_id, address_id) ' .
                'VALUES (:monument_id, :address_id)');
        $statement->bindParam(':address_id', $addressId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $statement->execute();
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
    
    public function getMonumentNotionInRel($monumentNotionId, $monumentId){
        $statement = $this->connection->prepare('Select id from monument_notion_rel where '
                . 'monument_notion_id = :monument_notion_id AND monument_id = :monument_id');
        $statement->bindParam(':monument_notion_id', $monumentNotionId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertMonumentNotion($monumentNotion){
        $statement = $this->connection->prepare('INSERT INTO monument_notion (name) VALUES (:name) RETURNING id');
        $statement->bindParam(':name', $monumentNotion, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetch()['id'];
    }
    
    public function insertMonumentNotionInRel($monumentNotionId, $monumentId){
        $statement = $this->connection->prepare('INSERT INTO monument_notion_rel (monument_notion_id, monument_id) ' .
                'VALUES (:monument_notion_id, :monument_id)');
        $statement->bindParam(':monument_notion_id', $monumentNotionId, PDO::PARAM_STR);
        $statement->bindParam(':monument_id', $monumentId, PDO::PARAM_STR);
        return $statement->execute();
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
    
    public function getDatingId($beginning, $ending){
        $statement = $this->connection->prepare('Select id from dating where beginning = :beginning AND ending = :ending');
        $statement->bindParam(':beginning', $beginning, PDO::PARAM_STR);
        $statement->bindParam(':ending', $ending, PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->rowCount();
        if ($rows < 1)
            $id = NULL;
        else
            $id = $statement->fetch()['id'];
        return $id;
    }
    
    public function insertDating($beginning, $ending){
        $statement = $this->connection->prepare('INSERT INTO dating (beginning, ending) '
                . 'VALUES (:beginning, :ending) RETURNING id');
        $statement->bindParam(':beginning', $beginning);
        $statement->bindParam(':ending', $ending);
        $statement->execute();
        return $statement->fetch()['id'];
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