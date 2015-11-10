<?php

require 'storage.php';
require 'logging.php';

/**
 * Description of dbnormalizer
 *
 * @author Wonder
 */

class dbnormalizer {
    
    private $storage;
    private $data;
    private $log;
    
    /**
     *  The constructor of the class.
     */
    
    public function __construct() {
        $this->log = new Logging();
        $this->log->lfile('dbnormalizerError.txt'); 
        $this->storage = new Storage();
    }
    
    /**
     *  The destructor of the class. 
     */
    
    public function __destruct() {
        $this->log->lclose();
        $this->storage = null;
    }
    
    /**
     *  Setter-function
     *  
     * @param array $data   the object-data u want to normalize & write to the db.
     */
    
    public function setData($data) {
        $this->data = $data;
        normalizeKeys($this->$data);
    }
    
    /**
     * Wrapper for all writing-functions.
     */
    
    public function writeData(){
        $this->writeMonument();
        $this->writeAddress();
    }
    
    /**
     * Writing the data to the monument table.
     */
    
    private function writeMonument(){
        if($this->data != NULL && $this->data["objektnr"] != NULL &&
                $this->data["name"] != NULL && $this->data["bezirk"] != NULL &&
                $this->data["ortsteil"] != NULL && $this->data["denkmalart"] != NULL &&
                $this->data["beschreibung"] != NULL && $this->data["bildurl"] != NULL) {
             if($this->storage->getMonument($this->data["objektnr"]) != NULL) {
                 // UPDATE MONUMENT
             } else {
                 // INSERT INTO MONUMENTS
             }
        } 
        else {
            $this->log->lwrite($this->data["objektnr"] . ' --- Missing monument-data.');
        }
    }
    
    private function writeAddress(){
        
    }
    
    /**
     * Just simplifies the Keys of the Object-Array ($this->data).
     */
    
    private function normalizeKeys(){
        foreach (key($this->data) as $key) {
            $key = strtolower($key);
            if ($key == "Obj.-Dok.-Nr.")
                $key = "objektnr";
            if ($key == "Ausführung")
                $key = "ausführung";        
        }
    }
    
}   