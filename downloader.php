<?php

/**
 * downloader class
 */
class Downloader {
    
    private $connection;
    
    public function __construct() {
        $this->connection = curl_init();
    }
    
    public function __destruct() {
        curl_close($this->connection);
        $this->connection = null;
    }
    
    public function download($link) {
        curl_setopt($this->connection, CURLOPT_URL, $link);
        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($this->connection);
        if (curl_error($this->connection))
            die(curl_error($this->connection));
        return $html;
    }
    
    public function getStatusCode() {
        return curl_getinfo($this->connection, CURLINFO_HTTP_CODE);
    }
}
