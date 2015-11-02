<?php

require 'storage.php'; 
require 'logging.php';
require 'crawler.php';

$log = new Logging();
$log->lfile('denkmaeler_log.txt'); 

$crawler = new Crawler();
$objectIds = $crawler->fetchObjectIds("denkmalliste.txt", '/090[0-9]{5}/');
$length = count($objectIds);

//libxml error handling
libxml_use_internal_errors(true);

//$objectId = "09011394";
//$detailLink = getDetailLink($objectId);
//$object = getObjectData($detailLink);

$storage = new Storage();

// get the detaillink & object details for each objectid & save them to the db
for ($i = 4000; $i < $length; $i++) { // 68,73
    $detailLink = $crawler->getDetailLink($objectIds[$i]);
    if ($detailLink == NULL) {
        $log->lwrite($objectIds[$i] . '- Cant get the detail-link.');
        sleep(1);
    } else {
        $object = $crawler->getObjectData($detailLink);
        if ($object == NULL) {
            $log->lwrite($objectIds[$i] . '- Cant get the Object.');
            sleep(1);
        } else {
            $result = $storage->saveObject($object);
            echo  "||| " . round(($i / $length * 100), 2) . "% |||" . $object["Obj.-Dok.-Nr."] . " " . $object["Name"] . "\n";
            sleep(1);
        }
    }
}
$log->lclose();