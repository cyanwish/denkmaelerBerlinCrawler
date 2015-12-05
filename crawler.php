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
        //$pattern = '/090[0-9]{5}/';
        $i = 0;
        while(!feof($file)) {
            $line = fgets($file);
            preg_match($pattern, $line, $matches);
            if (@$matches[0] != NULL) {
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
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        $header = $this->getHeaderData($dom);
        $body = $this->getBodyData($dom); 
        $data = array();
        if ($header != NULL && $body != NULL) {
            $data = array_merge($data, array_merge($header, $body));
            $data['name'] = $this->getName($dom);
            $data['picture'] = $this->getPictureUrls($dom);
            $data['descr'] = $this->getDescr($dom);
            $data = $this->checkParticipants($data);
            $data = $this->simplifyDataKeys($data);
            $data['district'] = $this->splitStringIntoTokens($data['district'], '&');
            $data['sub_district'] = $this->splitStringIntoTokens($data['sub_district'], '&');
            $data['monument_notion'] = $this->splitStringIntoTokens($data['monument_notion'], '&'); 
            if(isset($data['nr']))
                $data['nr'] = $this->splitStringIntoTokens($data['nr'], '&');
            $data = $this->normalizeDate($data);
        } else {
            $data = NULL;
        }
        return $data;
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
        $firstAdr = TRUE;
        $firstNr = TRUE;
        for ($j = 0; $j < $head_trs->length; $j++){
            $td = $head_trs->item($j)->getElementsByTagName('td');  
            if($td->item(0)->nodeValue == 'Adresse'){
                if($firstAdr == TRUE){
                    $firstAdr = FALSE;
                    $tag = filter_var(trim(str_replace(":", "", $td->item(0)->nodeValue)), FILTER_SANITIZE_STRING);
                    $data[$tag] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                } // else nothing because we only take the first adress
            }
            if($td->item(0)->nodeValue == 'Hausnummer'){
                if($firstNr == TRUE){
                    $firstNr = FALSE;
                    $tag = filter_var(trim(str_replace(":", "", $td->item(0)->nodeValue)), FILTER_SANITIZE_STRING);
                    $data[$tag] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                } // else nothing because we only take the first housenr
            } else { // every other data besides "address"
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
        $data = NULL;
        $body = $this->getElementsByClass($dom, 'table', 'denkmal_detail_body');
        if(isset($body[0])){
            $denkmal_detail_body = $body[0];
        } else {
            $denkmal_detail_body = NULL;
        }
       // if($denkmal_detail_body == NULL){
         //   $data = NULL;
        if($denkmal_detail_body !== NULL) {
            $body_trs = $denkmal_detail_body->getElementsByTagName('tr');
            if($body_trs == NULL)
                $data = NULL;
            else {
                $j = 0;
                for ($j = 0; $j < $body_trs->length; $j++) {
                    $td = $body_trs->item($j)->getElementsByTagName('td');
                    if($td->item(0)->nodeValue == 'Literatur:') //ignoring the literature
                    {} else { 
                        $tag = filter_var(trim(str_replace(":", "", $td->item(0)->nodeValue)), FILTER_SANITIZE_STRING);
                        // informations besides the three participant types
                        if($td->item(0)->nodeValue != 'Entwurf:' && $td->item(0)->nodeValue != 'Ausführung:' &&
                            $td->item(0)->nodeValue != 'Bauherr:'){
                            $data[$tag] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                        }          
                        // the three participant types with possible several entries
                        if($td->item(0)->nodeValue == 'Entwurf:'){
                            if(is_numeric($td->item(1)->nodeValue == FALSE)){
                                $data['p_concept'][] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                            } else {} // ignore it if its a number
                        }
                        if($td->item(0)->nodeValue == 'Ausführung:'){
                            $data['p_exec'][] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                        }
                        if($td->item(0)->nodeValue == 'Bauherr:'){
                            $data['p_builder'][] = filter_var(trim($td->item(1)->nodeValue), FILTER_SANITIZE_STRING);
                        }
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
            $pattern = '/-{5,}/';
            $body_text = $denkmal_detail_text[0]->getElementsByTagName('p');
            $stop = new DOMElement('p', 'stop');
            $hr = $denkmal_detail_text[0]->getElementsByTagName('hr');
            // checking for <hr>s as sign for the textend.
            if ($hr->length != 0) {
                $denkmal_detail_text[0]->replaceChild($stop, $hr->item(0));
            }
            for ($i = 0; $i < $body_text->length; $i++) {
                // checking for ugly '---' strings
                preg_match($pattern, $body_text->item($i)->nodeValue, $matches);
                if (isset($matches[0])) {
                    $i = $body_text->length;
                }
                if($body_text->item($i)->nodeValue == 'stop'){
                    $i = $body_text->length;
                } else {
                    $descr .= $body_text->item($i)->nodeValue;
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
        $picture = array();
        if (isset($denkmal_detail_img) && !empty($denkmal_detail_img)) {
            $body_imgs = $denkmal_detail_img[0]->getElementsByTagName('a');
            if($body_imgs != NULL) {
                for ($i = 0; $i < $body_imgs->length; $i++) {
                    $picture[$i] = 'http://www.stadtentwicklung.berlin.de/denkmal/liste_karte_datenbank/de/denkmaldatenbank/' . 
                    $body_imgs->item($i)->getAttribute('href');
                }
            } else {
                $picture = NULL;
            }
        } else {
            $picture = NULL;
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
     * This function returns a given string into an array, separated by the token.
     * Care: Also deletes all whitespaces.
     * 
     * @param   String  $string     the string you want to split into parts
     * @param   String  $token      the token like '\n', '&', ' ', ...
     * 
     * @return  Array   $result     the parts of the given string in an array
     */
    
    private function splitStringIntoTokens($string, $token){
        $result = explode($token, str_replace(' ', '', $string));
        return $result;
    }
    
    /**
     * The function simply renames the array-keys in proper style.
     * 
     * @param   array   $data       the given data
     * 
     * @return  array   $newData    given data returned with renamed keys       
     */
    
    private function simplifyDataKeys($data){
        $newData['name'] = $data["name"];
        $newData['obj_nr'] = $data["Obj.-Dok.-Nr."];
        $newData['district'] = $data["Bezirk"];
        $newData['sub_district'] = $data["Ortsteil"];
        $newData['type'] = $data["Denkmalart"];
        $newData['monument_notion'] = $data["Sachbegriff"];
        if(isset($data['Strasse']))
            $newData['street'] = $data['Strasse'];
        if(isset($data['Hausnummer']))
            $newData['nr'] = $data['Hausnummer'];
        if (isset($data['Datierung']))
            $newData['date'] = $data['Datierung'];
        if (isset($data['Fertigstellung']))
            $newData['completion'] = $data['Fertigstellung'];
        if(isset($data['Baubeginn']))
            $newData['build_start'] = $data['Baubeginn'];
        if (isset($data['p_concept']))
            $newData['p_concept'] = $data['p_concept'];
        if (isset($data['p_exec']))
            $newData['p_exec'] = $data['p_exec'];
        if (isset($data['p_builder']))
            $newData['p_builder'] = $data['p_builder'];
        if (isset($data["picture"]))
            $newData['picture'] = $data['picture'];
        if (isset($data["descr"]))
            $newData['descr'] = $data['descr'];
        return $newData;
    }
    
    /**
     * The function checks for participants with multiple appearings and normalize them
     * 
     * @param   array $data the given object-data with possible participants information
     * 
     * @return  array $data returning object-data with normalized participants information   
     */
    
    private function checkParticipants($data){
        $keys = array_keys($data);
        foreach ($keys as $key){
            if (strpos($key,'Entwurf') !== false) {
                $data['p_concept'][] = $data[$key];
            }
            if (strpos($key,'Ausführung') !== false) {
                 $data['p_exec'][] = $data[$key];
            }
            if (strpos($key,'Bauherr') !== false) {
                 $data['p_builder'][] = $data[$key];
            }
        }
        return $data;
    }
    
    /**
     * The function normalizes several different date descriptions.
     * 
     * @param   array $data the given object-data with possible date information
     * 
     * @return  array $data returning object-data with normalized date information
     */
    
    private function normalizeDate($data){
        if(!isset($data['build_start']) && !isset($data['completion']) && isset($data['date'])){
            $pattern1 = '/([0-9]{4}-[0-9]{4})|([0-9]{4}\/[0-9]{4})/'; // 1900-1910 / 1900/1910
            $pattern2 = '/(um [0-9]{4})|([0-9]{4})/'; // um 1900 / 1900
            $pattern3 = '/[0-9]{4}/'; // only the year
            $date = $data['date'];
            unset($data['date']);
            $success = preg_match($pattern1, $date , $matches);
            if($success){
                preg_match_all($pattern3, $date, $matches);
                $data['date']['beginning'] = $matches[0][0];
                $data['date']['ending'] = $matches[0][1];
            } else {
                $success = preg_match($pattern2, $date, $matches);
                if($success){
                    preg_match($pattern3, $date, $matches);
                    $data['date']['beginning'] = $matches[0];
                }   
            }
        }
        if(isset($data['build_start'])){
            $data['date']['beginning'] = $data['build_start'];
            unset($data['build_start']);
        }
        if(isset($data['completion'])){
            $data['date']['ending'] = $data['completion'];
            unset($data['completion']);
        }
        return $data;
    }
    
}
