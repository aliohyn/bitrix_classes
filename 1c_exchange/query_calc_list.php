<?php
include "config.php";
$config = getConfig();

if($config['COMPANY_TOKENS'][$_GET['token']]){
    header ("Content-Type:text/xml");
    $xmlstr = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<Запросы></Запросы>
XML;

    $xml = new SimpleXMLElement($xmlstr);
    $query = $xml -> addChild('Запрос');
    $query -> addChild('Год', "2017");
    $query -> addChild('Месяц', "09");
    $query -> addChild('Email', "test@test.test");
    $query -> addChild('ИмяФайла', "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa");
    $query = $xml -> addChild('Запрос');
    $query -> addChild('Год', "2017");
    $query -> addChild('Месяц', "10");
    $query -> addChild('Email', "test2@test.test");
    $query -> addChild('ИмяФайла', "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1");

    echo $xml -> asXML();
} else {
    echo "Error parameters";
}