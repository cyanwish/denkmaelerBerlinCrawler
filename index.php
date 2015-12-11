<?php

require_once 'storage.php'; 
require_once 'logging.php';
require_once 'crawler.php';
require_once 'dbImporter.php';
require_once 'config.php';
require_once 'geolocator.php';

/**
 *  /// The main file /// 
 */

/////////start setup/////////////
$crawler = new Crawler();
$storage = new Storage();
//$objectIds = $crawler->fetchObjectIds("missing_objects.txt", '/090[0-9]{5}/');
$dbImporter = new dbImporter();
//libxml error handling
libxml_use_internal_errors(true);
/////////finish setup/////////////

//$objectId = '09011386';
//$object = crawlObject($crawler, $objectId);
//var_dump($object);
//crawlAllObjects($crawler, $dbImporter, $objectIds);
//var_dump(count($objectIds));
//checkForExistingMonuments($objectIds, $storage);
geolocateAddresses($storage);


///////functions///////
function crawlObject($crawler, $objectId){
    $detailLink = $crawler->getDetailLink($objectId);
    $object = $crawler->getObjectData($detailLink);
    return $object;
}

function crawlAllObjects($crawler, $importer, $objectIds){
    
    $log = new Logging();
    $log->lfile('denkmaeler_log.txt'); 
    
    $length = count($objectIds);

    // get the detaillink & object details for each objectid & save them to the db
    for ($i = 0; $i < $length; $i++){
        $detailLink = $crawler->getDetailLink($objectIds[$i]);
        if ($detailLink == NULL) {
            echo "LINK DEAD\n";
            $log->lwrite($objectIds[$i] . ' Crawler Error. Unable to get the detail-link.');
        } else {
            $object = $crawler->getObjectData($detailLink);
            if ($object == NULL) {
                 echo "OBJECT DEAD\n";
                $log->lwrite($objectIds[$i] . ' Crawler Error. Unable to get the object.');
            } else {
                $object['obj_nr'] = $objectIds[$i];
                $importer->setData($object);
                $importer->writeData();
                $keys = array_keys($object);
                for ($x = 0; $x < count($keys); $x++){
                    if( $keys[$x] != 'obj_nr' &&
                        $keys[$x] != 'name' &&
                        $keys[$x] != 'descr' &&
                        $keys[$x] != 'picture' &&
                        $keys[$x] != 'district' &&
                        $keys[$x] != 'sub_district' &&
                        $keys[$x] != 'p_concept' &&     
                        $keys[$x] != 'p_builder' &&
                        $keys[$x] != 'p_exec' &&
                        $keys[$x] != 'completion' &&
                        $keys[$x] != 'build_start' &&
                        $keys[$x] != 'nr' &&
                        $keys[$x] != 'type' &&
                        $keys[$x] != 'monument_notion' &&
                        $keys[$x] != 'date' &&
                        $keys[$x] != 'street') {
                        echo "\n neuer Key:" . $keys[$x] . "\n"; //Just to find new structures
                    }
                }
                //var_dump($object);
                //progress-output
                echo  "||| " . round(($i / $length * 100), 2) . "% |||" . $object['obj_nr'] . " " . $object['name'] . "\n";
            }
        } 
    }
    $log->lclose();   
}

function geolocateAddresses($storage){
    $log = new Logging();
    $log->lfile('geolocator_log.txt'); 
    $geoloc = new Geolocator();
    $result = $storage->getAllMonumentIds();
    $length = count($result);
    for ($i = 0; $i < $length; $i++){
        $objectIds[] = $result[$i]['id'];
    }
    for ($i = 0; $i < $length; $i++) {
        $monument = $storage->getMonument($objectIds[$i]);
        $addressId = $storage->getAddressIdsFromMonument($monument['id'])[0]['address_id'];
        if($addressId != NULL){
            $districtId = $storage->getDistrictIdsFromMonument($monument['id'])[0]['district_id'];
            if($districtId != NULL){
                $district = $storage->getDistrict($districtId)['name'];
                $address = $storage->getAddress($addressId);
                $coordinates = $geoloc->getCoordinates(array($address['id'], $address['street'], $address['nr'], $district));
                if($coordinates != NULL && $address['lat'] == NULL && $address['long'] == NULL){
                    $storage->updateCoordinatesOfAddress($address['id'], $coordinates['lat'], $coordinates['lon']);
                } else {
                    $log->lwrite($objectIds[$i] . ' Geolocator Error. Coordinates invalid.');
                }
            } else {
                $log->lwrite($objectIds[$i] . ' Monument Error. No District-id.'); 
            }
        } else {
            $log->lwrite($objectIds[$i] . ' Monument Error. No Dating-id.');    
        }
    }
}

function checkForExistingMonuments($objectIds, $storage){
    $log = new Logging();
    $log->lfile('missing_objects.txt'); 
    $missing = array();
    foreach($objectIds as $object){
        $result = $storage->getMonumentId($object);
        if($result == NULL){
            $missing[] = $object;
            $log->lwrite($object);
        }
        
    }
    
}