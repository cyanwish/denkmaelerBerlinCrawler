<?php

/**
 *  The downloader class, based on the cURL library.
 */

class Downloader {
    
    private $connection;
    
    /**
     *  The constructor of the class.
     */
    
    public function __construct() {
        $this->connection = curl_init();
    }
    
    /**
     *  The destructor of the class. 
     */
    
    public function __destruct() {
        curl_close($this->connection);
        $this->connection = null;
    }
    
    /**
     *  The function returns the html from the given link.
     * 
     *  @param type $link    the needed URL
     *  @return type         the downloaded html
     */
    
    public function download($link) {
        curl_setopt($this->connection, CURLOPT_URL, $link);
        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($this->connection);
        if (curl_error($this->connection))
            die(curl_error($this->connection));
        return $html;
    }
    
    /*+
     *  The function returns the HTTP-Code from the last executed download().
     *  It only returns a useful code, if download() was executed before.
     * 
     *  @return the HTTP-Code from the last executed download()
     */
    
    public function getHTTPCode() {
        return curl_getinfo($this->connection, CURLINFO_HTTP_CODE);
    }
}
