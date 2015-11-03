<?php

require 'downloader.php';

/**
 * This class provides the specific functions for crawling data in .txt's and html
 * from http://www.stadtentwicklung.berlin.de/denkmal/liste_karte_datenbank/de/denkmaldatenbank/index.shtml .
 */

class Crawler {
    
    private $downloader;
    
    /**
     *  The constructor of the class.
     */
    
    public function __construct() {
        $this->downloader = new Downloader();
    }
    
    /**
     *  The destructor of the class. 
     */
    
    public function __destruct() {
        $this->downloader = null;
    }
    
    /**
    * The function opens the given file, reads it line by line and checks for the
    * given regEx-pattern. The successfull matches will be saved and returnend in 
    * an array.
    * 
    * @param    $filename   string representation of the filename (including ending)
    * @param    $pattern    the regex pattern 
    * @return               array of objectids
    */

    function fetchObjectIds($filename, $pattern) {
    
        $file = fopen($filename, "r") or die("Unable to open file!");
        $data = array();
        //$pattern = '/090[0-9]{5}/';
        $i = 0;
        while(!feof($file)) {
            $line = fgets($file);
            preg_match($pattern, $line, $matches);
            if ($matches[0] != NULL) {
                $data[$i++] = $matches[0];
            }
        }
        fclose($file);
        return $data;
    }
    
    /**
     *  The function crawls for the link of the detailed page of the given
     *  object-id.
     *  
     *  @param      $id     the object-id
     * 
     *  @return string      the URL from the detail-page of the given object-id
     */
    
    function getDetailLink($id) {
        $html = $this->downloader->download('http://www.stadtentwicklung.berlin.de/denkmal/liste_karte_datenbank/de/denkmaldatenbank/suchresultat.php?objekt=' . $id);
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        $body = $dom->getElementsByTagName('table');
        $denkmalNode = $body->item(0);
        $resultNode = $this->getElementsByClass($denkmalNode, 'a', 'denkmal_detail');
        if ($resultNode == NULL) {
            $link = NULL;
        } else {
            $link = 'http://www.stadtentwicklung.berlin.de/denkmal/liste_karte_datenbank/de/denkmaldatenbank/' . $resultNode[0]->getAttribute('href');
        }    
        return $link;
    }

    /**
    *   The functions crawls for detail data with the given object-id.
    *
    *   @param      $link   link of the detailpage from the object
    * 
    *   @return     array   either an array containing the details of the object or NULL
    *                       (in case of errors)
    */

    function getObjectData($link) {
        $html = $this->downloader->download($link);
        echo $this->downloader->getStatusCode();
        $dom = new DOMDocument;
        $dom->loadHTML($html);
    
        $data = array();
        $data['Name'] = $dom->getElementsByTagName('h2')->item(0)->nodeValue;
    
        $body = $dom->getElementsByTagName('table');
    
        $denkmal_detail_head = $body->item(1);
        $head_trs = $denkmal_detail_head->getElementsByTagName('tr');
 
        for ($j = 0; $j < $head_trs->length; $j++) {
            $td = $head_trs->item($j)->getElementsByTagName('td');
            for ($i = 0; $i < $td->length; $i++)
            {
                $tag = filter_var(trim(str_replace(":", "", $td->item(0)->nodeValue)), FILTER_SANITIZE_STRING);
                $data[$tag] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
            }
        }
    
        $denkmal_detail_body = $body->item(2);
    
        if($denkmal_detail_body == NULL) {
            $data = NULL;
        } else {
            $body_trs = $denkmal_detail_body->getElementsByTagName('tr');
            $j = 0;
            for ($j = 0; $j < $body_trs->length; $j++) {
                $td = $body_trs->item($j)->getElementsByTagName('td');
                for ($i = 0; $i < $td->length; $i++)
                {
                    if($td->item(0)->nodeValue == 'Literatur:')
                    {} else {
                        $tag = filter_var(trim(str_replace(":", "", $td->item(0)->nodeValue)), FILTER_SANITIZE_STRING);
                        $data[$tag] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                    }
                }
            }
        }
        return $data;
    }
    
    /**
    *   Helper-function for crawling an element with a specific class.
    * 
    *   @param $parentNode   given parent-node
    *   @param $tagName      the tagname
    *   @param $className    the classname 
    * 
    *   @return     array    the result-node
    *  
    *   Source: http://stackoverflow.com/a/31616848
    */

    function getElementsByClass(&$parentNode, $tagName, $className) {
        $nodes=array();
        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++) {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false) {
                $nodes[]=$temp;
            }
        }
        return $nodes;
    }
    
}
