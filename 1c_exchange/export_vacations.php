<?php
error_reporting(E_ERROR);
include "config.php";
$config = getConfig();

if($config['COMPANY_TOKENS'][$_GET['token']]) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
    header("Content-Type:text/xml");

    $xmlstr = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<Отпуска></Отпуска>
XML;

    $xml = new SimpleXMLElement($xmlstr);
    $HM = new HighloadManager(['NAME' => 'Vacations']);
    $arFilter = [];
    /*
    if ($_GET['date']) {
        $_GET['date'] = "2017-01-31";
        $_GET['date'] = $GLOBALS['DB']->FormatDate($_GET['date'], "YYYY-MM-DD", "DD.MM.YYYY");
        $arFilter = ['>UF_DATE_BEGIN' => $_GET['date']];
    }
    */

    $vacations = $HM->GetList($arFilter);

    foreach ($vacations as $v) {
        $vacation = $xml->addChild('Отпуск');
        $vacation->addChild('Email', $v['UF_EMAIL']);
        $vacation->addChild('Начало', $v['UF_DATE_BEGIN']->toString());
        $vacation->addChild('Окончание', $v['UF_DATE_END']->toString());
        $vacation->addChild('Статус', $v['UF_STATUS']);
        if($v['UF_DATE_UPDATE']){
            $vacation->addChild('Изменено', $v['UF_DATE_UPDATE']->toString());
        }
        $vacation->addChild('ИДДокументаПланирования', $v['UF_XML_ID']);
        $vacation->addChild('ИдКомпании', "386e613a-9b59-11e5-8c0e-7845c41c13b0");
    }
    echo $xml->asXML();
} else {
    echo "Error parameters";
}