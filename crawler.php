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

    public function fetchObjectIds($filename, $pattern) {
    
        $file = fopen($filename, "r") or die("Unable to open file!");
        $data = array();
        $pattern = '/090[0-9]{5}/';
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
    
    public function getDetailLink($id) {
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

    public function getObjectData($link) {
        $html = $this->downloader->download($link);
        echo $this->downloader->getStatusCode();
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        $header = getHeaderData($dom);
        $body = getBodydata($dom); 
        $data = array_merge($data, array_merge($header, $body)); //issues ?
        $data['name'] = $this->getName($dom);
        $data['picture'] = $this->getPictureUrls($dom);
        $data['descr'] = $this->getDescr($dom); 
        $dataFinal = $this->simplifyDataKeys($data); // issues ?
        return $dataFinal;
    }
    
    /**
     * Crawls the name of the monument.
     * 
     * @param   DOMDocument $dom    the given Dom-Document
     * 
     * @return  String      $name   the name of the monument
     */
    
    private function getName($dom){
        $name = $dom->getElementsByTagName('h2')->item(0)->nodeValue;
        return $name;
    }
    
    /**
     * Crawls for the informations about the monument in the header(-table)
     * 
     * @param   DOMDocument $dom    the given Dom-Document
     * 
     * @return  array       $data   the crawled header-data
     */
    
    private function getHeaderData($dom){
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
        return $data;
    }
    
    /**
     * Crawls for the informations about the monument in the body(-table)
     * 
     * @param   DOMDocument $dom    the given Dom-Document
     * 
     * @return  array       $data   the crawled header-data
     */
    
    private function getBodyData($dom){
        $body = $dom->getElementsByTagName('table');
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
     * Crawls the description from a monument.
     * 
     * @param   DOMDocument $dom    the given DOM-Document
     * 
     * @return  String      $descr  String containing the full description of the object  
     */
    
    private function getDescr($dom){
        $denkmal_detail_text = $this->getElementsByClass($dom, 'div', 'denkmal_detail_text');
        if ($denkmal_detail_text != NULL) {
            $descr = '';
            $pattern = '-{5,}';
            $body_text = $denkmal_detail_text->getElementsByTagName('p');
            $stop = new DOMElement('p', 'stop');
            $hr = $denkmal_detail_text->getElementsByTagName('hr');
        if ($hr != NULL) {
            $hr->parentNode->replaceChild($stop, $hr);
        }
            for ($i = 0; $i < $body_text->length; $i++) {
                preg_match($pattern, $body_text[$i]->nodeValue, $matches);
                if ($matches[0] != NULL) {
                    $i = $body_text->length;
                }
                if($body_text[$i]->nodeValue == 'stop'){
                    $i = $body_text->length;
                } else {
                    $descr.append($body_text[$i]->nodeValue);
                }
            }
        } else {
            $descr = NULL;
        }
        return $descr;
    }
    
    /**
     * Crawls the urls of the pictures from the monument.
     * 
     * @param   DOMDocument $dom        the given DOM-Document
     * 
     * @return  array       $picture    array containing the urls
     */
    
    private function getPictureUrls($dom){
        $denkmal_detail_img = $this->getElementsByClass($dom, 'div', 'denkmal_detail_img');
        if ($denkmal_detail_img != NULL) {
            $picture;
            $body_imgs = $denkmal_detail_img->getElementsByTagName('a');
            for ($i = 0; $i < $body_imgs->length; $i++) {
                $picture[$i] = $body_imgs[$i]->getAttribute('href');
            }
        }
        return $picture;
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

    private function getElementsByClass(&$parentNode, $tagName, $className) {
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
    
    /**
     * The function simply renames the array-keys in proper style.
     * 
     * @param   array   $data       the given data
     * @return  array   $newData    given data returned with renamed keys       
     */
    
    private function simplifyDataKeys($data){
        $newData['name'] = $data["name"];
        $newData['obj_nr'] = $data["Obj.-Dok.-Nr."];
        $newData['district'] = $data["Bezirk"];
        $newData['sub_district'] = $data["Ortsteil"];
        $newData['street'] = $data["Strasse"];
        $newData['nr'] = $data["Hausnummer"];
        $newData['type'] = $data["Denkmalart"];
        $newData['monument_notion'] = $data["Sachbegriff"];
        $newData['date'] = $data["Datierung"];
        $newData['p_concept'] = $data["Entwurf"];
        $newData['p_exec'] = $data["Ausführung"];
        $newData['p_builder'] = $data["Bauherr"];
        $newData['picture'] = $data['picture'];
        $newData['descr'] = $data['descr'];
        return $newData;
    }
    
}
