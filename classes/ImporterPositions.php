<?php

include "HighloadManager.php";


/**
 * Import users from 1C array to bitrix
 * Class ImporterUsers
 */
class ImporterPositions implements IImportManager
{
    private $logger;

    public function start($data, $logger)
    {
        $hasError = false;
        $HM = new HighloadManager(['NAME' => 'Positions']);
        foreach ($data['Должность'] as $arPosition) {
            if($arPosition['Наименование']){
                $arFields = [
                    'UF_NAME' => $arPosition['Наименование'],
                    'UF_SORT' => $arPosition['Порядок'],
                ];
                $HM -> add($arFields, 'UF_NAME');
            } else {
                $hasError = true;
                echo "Error - empty value Должность";
            }

        }
        if(!$hasError){
            echo 'success';
        }
    }
}