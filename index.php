<?php

require 'storage.php'; 
require 'logging.php';
require 'crawler.php';

/**
 *  /// The main file /// 
 */

/////////start setup/////////////
$crawler = new Crawler();
$objectIds = $crawler->fetchObjectIds("denkmalliste.txt", '/090[0-9]{5}/');
$storage = new Storage();
//libxml error handling
libxml_use_internal_errors(true);
/////////finish setup/////////////

$objectId = "09075277";
$object = crawlObject($crawler, $storage, $objectId);
//crawlAllObjects($crawler, $storage, $objectIds);

var_dump($object);

///////functions///////
function crawlObject($crawler, $storage, $objectId){
    $detailLink = $crawler->getDetailLink($objectId);
    $object = $crawler->getObjectData($detailLink);
    return $object;
}

function crawlAllObjects($crawler, $storage, $objectIds){
    
    $log = new Logging();
    $log->lfile('denkmaeler_log.txt'); 
    
    $length = count($objectIds);

    // get the detaillink & object details for each objectid & save them to the db
    for ($i = 5000; $i < $length; $i++) { // 68,73
        $detailLink = $crawler->getDetailLink($objectIds[$i]);
        if ($detailLink == NULL) {
            $log->lwrite($objectIds[$i] . 'Crawler Error. Unable to get the detail-link.');
            sleep(1);
        } else {
            $object = $crawler->getObjectData($detailLink);
            if ($object == NULL) {
                $log->lwrite($objectIds[$i] . 'Crawler Error. Unable to get the object.');
                sleep(1);
            } else {
                //$result = $storage->saveObject($object);
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
                        $keys[$x] != 'nr' &&
                        $keys[$x] != 'type' &&
                        $keys[$x] != 'monument_notion' &&
                        $keys[$x] != 'date' &&
                        $keys[$x] != 'street') {
                        echo 'neuer Key:' . $keys[$x] . "\n"; //Just to find new structures
                    }
                }
                //progress-output
                //echo  "||| " . round(($i / $length * 100), 2) . "% |||" . $object['obj_nr'] . " " . $object['name'] . "\n";
                echo '|' . $object['date'];
                sleep(1);
            }
        } 
    }
    $log->lclose();   
}