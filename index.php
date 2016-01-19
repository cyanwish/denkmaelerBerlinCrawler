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
// setup db-config in the storage class
$storage = new Storage();
$objectIds = $crawler->fetchObjectIds("denkmalliste.txt", '/090[0-9]{5}/');
$dbImporter = new dbImporter();
//libxml error handling
libxml_use_internal_errors(true);
/////////finish setup/////////////

//$objectId = '09011386';
//$object = crawlObject($crawler, $objectId);

//crawls all monuments in the .txt file, uses the online-db
//crawlAllObjects($crawler, $dbImporter, $objectIds);
//creates new logfile with missing monuments
//checkForExistingMonuments($objectIds, $storage);
//$missingIds = $crawler->fetchObjectIds("missing_objects.txt", '/090[0-9]{5}/');
//crawlAllObjects($crawler, $dbImporter, $missingIds);
//link monuments with their super-monument
//$ensembleIds = $crawler->fetchEnsembleParts("denkmalliste.txt");
//linkMonuments($ensembleIds, $storage);
//add coordinates with the nominatim-api
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
                //echoNewKeys($object);
                //progress-output
                echo  "||| " . round(($i / $length * 100), 2) . "% |||" . $object['obj_nr'] . " " . $object['name'] . "\n";
            }
        } 
    }
    $log->lclose();   
}

function linkMonuments($data, $storage){
    $length = count($data);
    $i = 0;
    foreach($data as $superEnsemble=>$ensemble){ 
        foreach($ensemble as $ensemble){
            $i++;
            $monumentId = $storage->getMonumentId($ensemble);
            $superMonumentId = $storage->getMonumentId($superEnsemble);
            $storage->updateSuperMonumentFromMonument($monumentId, $superMonumentId);
            echo  "||| " . round(($i / $length * 100), 2) . "% |||" . $superEnsemble . " --- " . $ensemble . "\n";
        }
    }
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
    for ($i = 1700; $i < $length; $i++) {
        $monument = $storage->getMonument($objectIds[$i]);
        $addressId = $storage->getAddressIdsFromMonument($monument['id'])[0]['address_id'];
        if($addressId != NULL){
            $districtId = $storage->getDistrictIdsFromMonument($monument['id'])[0]['district_id'];
            if($districtId != NULL){
                $district = $storage->getDistrict($districtId)['name'];
                $address = $storage->getAddress($addressId);
                if($address['lat'] == NULL && $address['long'] == NULL){
                    $coordinates = $geoloc->getCoordinates(array($address['id'], $address['street'], $address['nr'], $district));
                    if($coordinates != NULL){
                        $storage->updateCoordinatesOfAddress($address['id'], $coordinates['lat'], $coordinates['lon']);
                        echo  "||| " . round(($i / $length * 100), 2) . "% |||" . $monument['id'] . " " . $monument['name'] . "\n";
                    } else {
                        $log->lwrite($objectIds[$i] . ' Geolocator Error. Coordinates invalid.');
                    }
                } else {
                    echo $objectIds[$i] . " Address already updated.\n";
                }
            } else {
                $log->lwrite($objectIds[$i] . ' Monument Error. No District-id.'); 
            }
        } else {
            $log->lwrite($objectIds[$i] . ' Monument Error. No Address-id.');    
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

function echoNewKeys($object){
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
}