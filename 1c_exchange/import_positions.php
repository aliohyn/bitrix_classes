<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$fileName = $_GET['filename'];

$ExchangeManagerXML = new ExchangeManagerXML("/1c_exchange/", "import/" . $fileName);
$logger = new Logger("import_structure", "/1c_exchange/logs/");

// Create object of import strategy
$importer = new ImporterPositions();

$ExchangeManagerXML -> SetLogger($logger);
$ExchangeManagerXML -> startImportStrategy($importer);
