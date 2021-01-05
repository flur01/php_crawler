<?php

namespace Core;

use Core\Database\Connection;
use Core\Database\QueryBilder;
use Core\App;
use DomXPath;
use DOMDocument;

class Crawler
{
    protected $data = [];

    protected $dataTable;
    
    protected $queueTable;
    
    protected $xpath;

    protected $baseURI;
    
    public function __construct($queueData, $baseURI=''){
        $this->baseURI = $baseURI;
        $this->queueData = $queueData;
        $this->dataTable = App::get('config')['data-table'];
        $this->queueTable = App::get('config')['queue-table'];
        $this->errorTable = App::get('config')['error-table'];
    }

    public function getDomXPath()
    {
        libxml_use_internal_errors(true);
        
        $proxies = App::get('config')['proxies'];

        $proxy = $proxies[array_rand($proxies)];
        $url = $this->queueData['url'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTPS');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
	    curl_setopt($ch, CURLOPT_SSLVERSION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	    curl_setopt($ch, CURLOPT_CAINFO, getcwd() . '/CAcert.pem',);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 35);
        curl_setopt($ch, CURLOPT_TIMEOUT, 35);
	    curl_setopt($ch, CURLOPT_MAXREDIRS,10);
        $response = curl_exec($ch);
        if (!$response) {
            $errorMessage = curl_error($ch);
            $logMessage = "$errorMessage\n  in $url \n";
            if (strpos($errorMessage, '404')) {
                echo "\n**404**\n$url\n*******\n";
            } elseif (strpos($errorMessage, '500')) {
                echo "\n**500**\n$url\n*******\n";
            } elseif (strpos($errorMessage, '502') || strpos($errorMessage, 'SSL_ERROR_SYSCALL')) {
                $this->data[] = $this->queueData;
                $this->addToQueue();
                $logMessage = $logMessage . "return to QUEUE \n\n";
            } else {
                $this->data[] = $this->queueData;
                $this->addToErrorTable();
                $logMessage = $logMessage . "add to ERROR table \n\n";
            }
            echo $logMessage;
            fwrite($GLOBALS['logFile'], $logMessage);
            die();
        }
        curl_close($ch);

        $dom = new DOMDocument;
        $dom->loadHTML($response);
        $this->xpath = new DomXPath($dom);
    }

    public function insert($table)
    {
        foreach ($this->data as $row) {
            $queryBilder = new QueryBilder();
            $queryBilder->insert($table, $row);
        }
    }

    public function insertData()
    {
        $this->insert($this->dataTable);
    }

    public function addToQueue()
    {
        $this->insert($this->queueTable);
    }

    public function addToErrorTable()
    {
        $this->insert($this->errorTable);
    }
    
    public function selectOneWhere($attributes, $where = [])
    {
        $queryBilder = new QueryBilder();
        return $queryBilder->selectOneWhere($this->queueTable, $attributes, $where);
    }
}
