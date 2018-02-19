<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$fileName = $_GET['filename'];

$exchangeManager = new ExchangeManagerXML("/1c_exchange/", "import/" . $fileName);
$logger = new Logger("import_users", "/1c_exchange/logs/");

// Create object of import strategy
$importer = new ImporterVacationDays();

$exchangeManager -> SetLogger($logger);
$exchangeManager -> startImportStrategy($importer);
