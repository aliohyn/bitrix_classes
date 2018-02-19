<?php
error_reporting(E_ERROR);
if($_GET['filename']) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

    $fileName = $_GET['filename'];
    $exchangeManager = new ExchangeManagerXML("/1c_exchange/", "import/" . $fileName);

    $logger = new Logger("import_vacation", "/1c_exchange/logs/");

    // Create object of import strategy
    $importer = new ImporterVacations();

    $exchangeManager->SetLogger($logger);
    $exchangeManager->startImportStrategy($importer);

} else {
    echo "Error parameters";
}