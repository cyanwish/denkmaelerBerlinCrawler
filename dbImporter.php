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
        $this->log = null;
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
        if(isset($this->data)){
            $this->writeType();
            if(isset($this->data['type_id'])){
                $this->writeDating();
                $this->writeMonument();
                if(isset($this->data['id'])){
                    $this->writeDistrict();
                    $this->writeSubDistrict();
                    $this->writeAddress();
                    $this->writePictureUrl();
                    $this->writeMonumentNotion();
                    $this->writeParticipant();
                } else
                    echo '||| ' .$this->data['obj_nr'] . ' already written. ';
            } 
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing monument-data.\n");
        }
    }
    
    /**
     * Writing the data to the monument table.
     */
    // Missing super-monument id & link id
    private function writeMonument(){
        if($this->storage->getMonument($this->data['obj_nr']) == NULL) {
            if(!isset($this->data['descr']))
                $this->data['descr'] = NULL;
            if(!isset($this->data['dating_id']))
                $this->data['dating_id'] = NULL;
            $this->data['id'] = $this->storage->insertMonument(array($this->data['name'], $this->data['obj_nr'], $this->data['descr'], $this->data['type_id'], NULL, NULL, $this->data['dating_id']));
        } else {}   //update 
    }
    
    /**
     * The function checks for an existing type, else a new type will be written
     * to the db.
     */
    
    private function writeType(){
        if(isset($this->data['type'])){
            $this->data['type_id'] = $this->storage->getTypeId($this->data['type']);
            if($this->data['type_id'] == NULL)
                $this->data['type_id'] = $this->storage->insertType($this->data['type']);
        } else
            $this->data['type_id'] = NULL;
    }
    
    /**
     * 
     */
    // Missing lat/long
    private function writeAddress(){
        if(!isset($this->data['nr'][0]))
            $this->data['nr'][0] = NULL;
        if(isset($this->data['street'])){
            $addressId = $this->storage->getAddressId(array('street' => $this->data['street'], 'nr' => $this->data['nr'][0]));
            if($addressId == NULL){
                if(isset($this->data['nr'][0])){
                    $addressId = $this->storage->insertAddress(array(NULL, NULL, $this->data['street'], $this->data['nr'][0]));
                    $this->storage->insertAddressInRel($addressId, $this->data['id']);
                } else {
                    $addressId = $this->storage->insertAddress(array(NULL, NULL, $this->data['street'], NULL));
                    $this->storage->insertAddressInRel($addressId, $this->data['id']);
                }
            }
            if($this->storage->getAddressInRel($addressId, $this->data['id']) == NULL){
                $this->storage->insertAddressInRel($addressId, $this->data['id']);
            } else {} // update
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing address\n");
        }
    }
    
    private function writeDistrict(){
        if(isset($this->data['district'])){
            foreach($this->data['district'] as $district){
                $districtId = $this->storage->getDistrictId($district);
                if($districtId == NULL){
                    $districtId = $this->storage->insertDistrict($district);
                    $this->storage->insertDistrictInRel($districtId, $this->data['id']);
                }
                if($this->storage->getDistrictInRel($districtId, $this->data['id']) == NULL){
                    $this->storage->insertDistrictInRel($districtId, $this->data['id']);
                } else {} // Update 
            }
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing district(s)\n");
        }
        
    }
    
    private function writeSubdistrict(){
        if(isset($this->data['sub_district'])){
            foreach($this->data['sub_district'] as $subdistrict){
                $subdistrictId = $this->storage->getSubdistrictId($subdistrict); 
                if($subdistrictId == NULL){
                    $subdistrictId = $this->storage->insertSubdistrict($subdistrict);
                    $this->storage->insertSubDistrictInRel($subdistrictId, $this->data['id']);
                }
                if($this->storage->getSubdistrictInRel($subdistrictId, $this->data['id']) == NULL){
                    $this->storage->insertSubdistrictInRel($subdistrictId, $this->data['id']);
                } else {} // Update 
            }
        } else {
            $this->log->lwrite($this->data['obj_nr'] . " --- missing district(s)\n");
        }
        
    }
    
    private function writeMonumentNotion(){
        if(isset($this->data['monument_notion'])){
            foreach($this->data['monument_notion'] as $monumentNotion){
                $monumentNotionId = $this->storage->getMonumentNotionId($monumentNotion);
                if($monumentNotionId == NULL){
                    $monumentNotionId = $this->storage->insertMonumentNotion($monumentNotion);
                    $this->storage->insertMonumentNotionInRel($monumentNotionId, $this->data['id']);
                }
                if($this->storage->getMonumentNotionInRel($monumentNotionId, $this->data['id']) == NULL){
                    $this->storage->insertMonumentNotionInRel($monumentNotionId, $this->data['id']);
                } else {} // Update
            }
        } else {
             $this->log->lwrite($this->data['obj_nr'] . " --- missing monument_notion\n");
        }
    }
    
    private function writePictureUrl(){
        if(isset($this->data['picture'])){
            foreach($this->data['picture'] as $picture){
               $this->storage->insertPictureUrl($picture, $this->data['id']);
            }
        } // nothing, not every monument has a picture
    }
    
    private function writeDating(){
        if(isset($this->data['date'])){
            if(!isset($this->data['date']['beginning']))
                $this->data['date']['beginning'] = NULL;
            else
                $this->data['date']['beginning'] .= '-01-01';
            if(!isset($this->data['date']['ending']))
                $this->data['date']['ending'] = NULL;
            else
                $this->data['date']['ending'] .= '-01-01';
            $this->data['dating_id'] = $this->storage->getDatingId($this->data['date']['beginning'], $this->data['date']['ending']);
            if($this->data['dating_id'] == NULL){
                $this->data['dating_id'] = $this->storage->insertDating($this->data['date']['beginning'], $this->data['date']['ending']);
            }   
        } else {
            $this->data['dating_id'] = NULL;
            $this->log->lwrite($this->data['obj_nr'] . " --- missing monument dating\n");
        }
    }
    
    private function writeParticipant(){
        if(isset($this->data['p_exec'])){
            foreach($this->data['p_exec'] as $exec){
                $typeId = $this->storage->getParticipantTypeId ('Ausführung');
                if($typeId == null){
                    $typeId = $this->storage->insertParticipantType('Ausführung');
                }
                $execId = $this->storage->getParticipantId($exec);
                if($execId == NULL){
                    $execId = $this->storage->insertParticipant($exec);
                    $this->storage->insertParticipantInRel($execId, $typeId, $this->data['id']);   
                } else {
                    if($this->storage->getParticipantInRel($execId, $typeId, $this->data['id']) == NULL){
                        $this->storage->insertParticipantInRel($execId, $typeId, $this->data['id']);
                    } else {}//Update
                }
            }
        }
        if(isset($this->data['p_builder'])){
            foreach($this->data['p_builder'] as $builder){
                $typeId = $this->storage->getParticipantTypeId ('Bauherr');
                if($typeId == null){
                    $typeId = $this->storage->insertParticipantType('Bauherr');
                }
                $builderId = $this->storage->getParticipantId($builder);
                if($builderId == NULL){
                    $builderId = $this->storage->insertParticipant($builder);
                    $this->storage->insertParticipantInRel($builderId, $typeId, $this->data['id']);   
                } else {
                    if($this->storage->getParticipantInRel($builderId, $typeId, $this->data['id']) == NULL){
                        $this->storage->insertParticipantInRel($builderId, $typeId, $this->data['id']);
                    } else {}//Update
                }
            }
        }
        if(isset($this->data['p_concept'])){
            foreach($this->data['p_concept'] as $concept){
                $typeId = $this->storage->getParticipantTypeId ('Entwurf');
                if($typeId == null){
                    $typeId = $this->storage->insertParticipantType('Entwurf');
                }
                $conceptId = $this->storage->getParticipantId($concept);
                if($conceptId == NULL){
                    $conceptId = $this->storage->insertParticipant($concept);
                    $this->storage->insertParticipantInRel($conceptId, $typeId, $this->data['id']);   
                } else {
                    if($this->storage->getParticipantInRel($conceptId, $typeId, $this->data['id']) == NULL){
                        $this->storage->insertParticipantInRel($conceptId, $typeId, $this->data['id']);
                    } else {}//Update
                }
            }
        }
    }
}   