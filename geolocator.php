<?php


class Geolocator {

    private $nomiPlzSearchUrl; 
    
    /**
     *  The constructor of the class.
     */
    
    public function __construct() {
        
    }
    
    /**
     *  The destructor of the class. 
     */
    
    public function __destruct() {
       
    }
    
    /**
     * Function get the coordinates from the given adress; using the nominatim API.
     * 
     * @param   array $data     Array containing [0] street, [1] housenumber, 
     *                          [2] district as strings.
     * @return  array           Returns the coordinates as an array.
     */
    
    function getCoordinates($data) {
        $result = $this->lookUp($data);
        if (!empty($result) && !empty($result[0]['lat']) && !empty($result[0]['lon'])) {
            //writeResults($data, $result[0]['lat'], $result[0]['lon']);
            //TODO: Insert into db.
            $coordinates['lat'] = $result[0]['lat'];
            $coordinates['lon'] = $result[0]['lon'];
        } else {
            // TODO: Error handling
            //writeResults($data,'n.a.', 'n.a.');
            $coordinates = NULL;
        }
        return $coordinates;
    }
 
    private function lookUp($data) {
        $streetName = $this->parseQuery($data[1]);
        $houseNumber = $this->parseQuery($data[2]);
        $district = $this->parseQuery($data[3]);
        $this->nomiPlzSearchUrl = 'http://nominatim.openstreetmap.org/search.php?q=' . $streetName . '+' . $houseNumber . '%2C+Berlin%2C+'. $district . '&format=json&adressdetails=1';
        $nomiResult = file_get_contents($this->nomiPlzSearchUrl);
        $nomiResultArray = json_decode($nomiResult, true);
        return $nomiResultArray;
    }

    private function writeResults($data, $lat, $lon) {
        $resultFile = fopen("results.csv", "a");
        $resultArray = array ($data[0], $data[1],$data[2],$data[3], $lat, $lon);
        fputcsv($resultFile, $resultArray);
        fclose($resultFile);
    }

    private function parseQuery($stringToParse) {
        $newString = str_replace(" ", "+", $stringToParse);
        $newString = str_replace("&", "%26", $newString);
        return $newString;

    }
}
