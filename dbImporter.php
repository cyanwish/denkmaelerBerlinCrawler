<?php

require_once 'storage.php';
require_once 'logging.php';

/**
 * 
 *
 * @author 
 */

class dbImporter{
    
    private $storage;
    private $data;
    private $log;
    
    
    /**
     *  The constructor of the class.
     */
    
    public function __construct(){
        $this->log = new Logging();
        $this->log->lfile('dbImporterError.txt'); 
        $this->storage = new Storage();
    }
    
    /**
     *  The destructor of the class. 
     */
    
    public function __destruct(){
        $this->log->lclose();
        $this->storage = null;
    }
    
    /**
     *  Setter-function
     *  
     * @param array $data   the object-data u want to normalize & write to the db.
     */
    
    public function setData($data){
        $this->data = $data;
    }
    
    /**
     * Wrapper for all writing-functions.
     */
    
    public function writeData(){
        if(!isset($this->data)){
            $this->writeType();
            $this->writeMonument();
            $this->writeDistrict();
            $this->writeSubDistrict();
            $this->writeAddress();
            $this->writePictureUrl();
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing monument-data.\n");
        }
    }
    
    /**
     * Writing the data to the monument table.
     */
    // Missing super-monument id & link id
    private function writeMonument(){
        if($this->storage->getMonument($this->data["objektnr"]) == NULL) {
            $this->storage->insertMonument(array($this->data['name'], $this->data['obj_nr'], $this->data['descr'], $this->data['type_id'], 'NULL', 'NULL'));
        } else {
            // UPDATE MONUMENT
        } 
    }
    
    /**
     * The function checks for an existing type, else a new type will be written
     * to the db.
     */
    
    private function writeType(){
        if($this->storage->getTypeId($this->data['type']) == NULL){
            $this->data['type_id'] = $this->storage->insertType($this->data);
        } else {
            $this->data['type_id'] = $this->storage->getTypeId($this->data['type']);
        }
    }
    
    /**
     * 
     */
    // Missing lat/long
    private function writeAddress(){
        if(isset($this->data['street']) && isset($this->data['obj_nr'])){
            if(isset($this->data['nr'])){
                $this->storage->insertAddress(array('NULL', 'NULL', $this->data['street'], $this->data['nr'], $this->data['obj_nr']));
            } else {
                $this->storage->insertAddress(array('NULL', 'NULL', $this->data['street'], 'NULL', $this->data['obj_nr']));
            }
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing address\n");
        }
    }
    
    private function writeDistrict(){
        if(isset($this->data['district'])){
            foreach($this->data['district'] as $district){
                if($this->storage->getDistrictId($district == NULL)){
                    $this->storage->insertDistrict($district, $this->data['obj_nr']);
                } else {
                    // UPDATE district
                }
            }
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing district(s)\n");
        }
        
    }
    
    private function writeSubDistrict(){
        if(isset($this->data['sub_district'])){
            foreach($this->data['sub_district'] as $sub_district){
                if($this->storage->getSubDistrictId($sub_district == NULL)){
                    $this->storage->insertSubDistrict($sub_district, $this->data['obj_nr']);
                } else {
                //UPDATE sub_district
                }
            }
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing subdistrict(s)\n");
        }  
    }
    
    private function writeMonumentNotion(){
        if(isset($this->data['monument_notion'])){
            foreach($this->data['monument_notion'] as $monumentNotion){
                if($this->storage->getMonumentNotionId($monumentNotion) == NULL){
                    $this->storage->insertMonumentNotion($monumentNotion, $this->data['obj_nr']);
                } else {
                    // UPDATE monument_notion
                }
            }
        } else {
             $this->log->lwrite($this->data['obj_nr'] . " --- missing monument_notion\n");
        }
    }
    
    private function writePictureUrl(){
        if(isset($this->data['picture'])){
            foreach($this->data['picture'] as $picture){
                $this->storage->insertPictureUrl($picture, $this->data['obj_nr']);
            }
        }
    }
    
    private function writeDating(){
        if(isset($this->data['date'])){
            foreach($this->data['date'] as $date){
                $this->storage->insertDating($date, $this->data['obj_nr']);
            }
        }
        
    }
}   