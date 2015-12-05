<?php

require_once 'storage.php'; 
require_once 'logging.php';
require_once 'crawler.php';
require_once 'dbImporter.php';
require_once 'config.php';

/**
 *  /// The main file /// 
 */

/////////start setup/////////////
$crawler = new Crawler();
$objectIds = $crawler->fetchObjectIds("denkmalliste.txt", '/090[0-9]{5}/');
$dbImporter = new dbImporter();
//libxml error handling
libxml_use_internal_errors(true);
/////////finish setup/////////////

//$objectId = '09011386';
//$object = crawlObject($crawler, $objectId);
//var_dump($object);
crawlAllObjects($crawler, $dbImporter, $objectIds);


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
    for ($i = 0; $i < $length; $i++) { // 68,73
        $detailLink = $crawler->getDetailLink($objectIds[$i]);
        if ($detailLink == NULL) {
            $log->lwrite($objectIds[$i] . ' Crawler Error. Unable to get the detail-link.');
        } else {
            $object = $crawler->getObjectData($detailLink);
            if ($object == NULL) {
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