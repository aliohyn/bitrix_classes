<?php

class HighloadManager
{
    private $entity;
    private $entityDataClass;

    private $fields;
    private $lastError;

    private $showErrors;

    function __construct($IBLOCK_FILTER, $showErrors = true) {
        $this -> showErrors = $showErrors;
        if($IBLOCK_FILTER){
            CModule::IncludeModule('highloadblock');
            $rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>$IBLOCK_FILTER));
            if($arData=$rsData->fetch()) {
                $this -> entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arData);
                $this -> entityDataClass = $this -> entity -> getDataClass();

                // Список полей
                $highloadFields = $this -> entity -> GetFields();
                foreach($highloadFields as $k => $v){
                    $this -> fields[] = $k;
                }
            }
            else{
                $this -> SetLastError(__METHOD__ . ' - ' . " Highload is not detected by filter");
            }
        }
        else{
            $this -> SetLastError(__METHOD__ . ' - ' . " Empty IBLOCK_FILTER");
        }
    }

    /**
     * Добавление элемента Highloadblock
     * @param $arFields array
     * @param $sinchroField string
     * @return bool|integer
     */
    public function Add($arFields, $sinchroField) {
        $hasField = false;
        foreach($this -> fields as $code){
            if($arFields[$code]){
                $hasField = true;
            }
        }
        if($hasField){
            $entity_data_class = $this -> entityDataClass;
            if($sinchroField){
                if(!isset($arFields[$sinchroField])){
                    $this -> SetLastError(__METHOD__ . ' - ' . "Empty parameter arFields[sinchroFields]");
                    return false;
                }
                if($res = $this -> GetList(array($sinchroField => $arFields[$sinchroField]))){
                    $result = $entity_data_class::update($res[0]['ID'], $arFields);
                    if(!$result->isSuccess()){
                        $this -> SetLastError(__METHOD__ . ' - ' . $result->getErrorMessages());
                        return false;
                    }
                    return true;
                }
                else{
                    $result = $entity_data_class::add($arFields);
                    if(!$result->isSuccess()){
                        $this -> SetLastError(__METHOD__ . ' - ' . $result->getErrorMessages());
                        return false;
                    }
                    return $result->getId(); //Id нового элемента
                }
            }
            else{
                $result = $entity_data_class::add($arFields);
                if(!$result->isSuccess()){
                    $this -> SetLastError(__METHOD__ . ' - ' . $result->getErrorMessages());
                    return false;
                }
                return $result->getId(); //Id нового элемента
            }
        }
        else{
            $this -> SetLastError(__METHOD__ . ' - ' . "Can't add empty fields");
        }
    }

    public function Update($ID, $arFields) {
        $entity_data_class = $this -> entityDataClass;
        $result = $entity_data_class::update($ID, $arFields);
    }

    // Список элементов Highloadblock
    public function GetList($arFilter = array(), $arSelect = array('*'), $arOrder = array("ID"=>"ASC")) {
        $Query = new \Bitrix\Main\Entity\Query($this -> entity);
        $Query->setSelect($arSelect);
        if(is_array($arFilter)) $Query->setFilter($arFilter);
        if(is_array($arOrder)) $Query->setOrder($arOrder);
        $qr_result = $Query->exec();
        $bd_result = new CDBResult($qr_result);
        while ($row = $bd_result->Fetch()){
            $result[] = $row;
        }
        return $result;
    }

    // Удаление элемента Highloadblock
    public function Delete($ID) {
        if($ID){
            $entity_data_class = $this -> entityDataClass;
            $entity_data_class::delete($ID);
        }
        else{
            $this -> SetLastError(__METHOD__ . ' - ' . " Empty delete ID");
        }
    }


    // Поля Highloadblock
    public function GetFields(){
        return $this -> fields;
    }

    // Получить последнюю ошибку
    public function GetLastError(){
        return $this -> lastError;
    }

    // Сохранить последнюю ошибку
    private function SetLastError($error_text){
        $this -> lastError = $error_text;
        if($this -> showErrors){
            echo "<div style='color:red'>Error in " . $error_text . "</div>";
        }
    }
}