<?php

/**
 * Import users from 1C array to bitrix
 * Class ImporterUsers
 */
class ImporterUsers implements IImportManager
{
    private $logger;
    private $reqFields = array('Email');
    private $structureIblockId;

    /**
     * @param $data array
     * @param $logger Logger
     */
    public function start($data, $logger)
    {
        $this -> structureIblockId = 5;


        $hasErrors = false;
        $this -> logger = $logger;
        $users = $data['Сотрудник'];
        if(!$users[0])
            $users = array($users);

        // getting current company structure

        CModule::IncludeModule('iblock');

        $res = CIBlockSection::GetList([], ['IBLOCK_ID' => $this -> structureIblockId, '!XML_ID' => false], false, ['ID', 'XML_ID']);
        while($ar_res = $res -> GetNext(false, false)){
            $section_xml_to_id[$ar_res['XML_ID']] = $ar_res['ID'];
        }


        foreach ($users as $item) {
            $hasReqEmpty = false;
            foreach ($this -> reqFields as $field){
                if(!$item[$field]){
                    $this -> logger -> log("ERROR", "Required field $field is empty");
                    $hasReqEmpty = true;
                    break;
                }
            }
            if($hasReqEmpty){
                continue;
            }

            if($item['Подразделения']['ИД'] && !is_array($item['Подразделения']['ИД'])){
                $item['Подразделения']['ИД'] = [$item['Подразделения']['ИД']];
            }

            $sections = [];
            foreach ($item['Подразделения']['ИД'] as $k => $v){
                $v = trim($v);
                if($section_xml_to_id[$v]){
                    $sections[] = $section_xml_to_id[$v];
                }
            }

            // prepare array for import
            $arFields = array(
                "EMAIL" => $item['Email'],
                "NAME" => $item['Имя'],
                "LAST_NAME" => $item['Фамилия'],
                "SECOND_NAME" => $item['Отчество'],
                "PERSONAL_BIRTHDAY" => $item['ДатаРождения'],
                "UF_BEGIN_WORK" => $item['ДатаПриёмаНаРаботу'],
                "UF_SHOW" => $item['Отображать'],
                "UF_SHOW_NEW" => $item['ОтображатьКакНовый'],
                "WORK_POSITION" => $item['Должность'],
                "PERSONAL_PHONE" => $item['Телефон'],
                "UF_CABINET" => $item['Кабинет'],
                "UF_STATUS" => $item['Статус'],
                "UF_DIR_NUMBER" => $item['ПрямойНомер'],
                "WORK_COMPANY" => $item['Компания'],
                "UF_DEPARTMENT" => $sections,
                "UF_TYPE_CONTRACT" => $item['ТипДоговора'],
                "UF_CONTRACT_END" => $item['ДатаОкончанияДоговора'],
                "UF_WORK_SCHEDULE" => $item['ГрафикРаботы'],
                "UF_WORK_EXPERIENCE" => $item['СтажРаботыКомпания'],
                "PERSONAL_PHOTO" => false,
            );

            if($item['Фотография']){
                if(file_exists($_SERVER["DOCUMENT_ROOT"]."/1c_exchange/import/images/" . $item['Фотография'])){
                    $arFields['PERSONAL_PHOTO'] = CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/1c_exchange/import/images/" . $item['Фотография']);
                } else{
                    $hasErrors = true;
                    $this -> logger -> log("ERROR", "picture " . $item['Фотография'] . " not found");
                }
            }

            $importItems[$item['Email']] = $arFields;
            $emails[] = $item['Email'];
        }


        if($emails[0]){
            $CUser = new CUser;
            $rsUsers = CUser::GetList(($by="id"), ($order="desc"), array("EMAIL" => implode(" | ", $emails)), array('FIELDS' => array('ID', 'EMAIL')));
            while($arUser = $rsUsers -> GetNext(true, false)){
                if($importItems[$arUser['EMAIL']]){
                    if(!$CUser->Update($arUser['ID'], $importItems[$arUser['EMAIL']])){
                        $hasErrors = true;
                        $this -> logger -> log("ERROR", $CUser -> LAST_ERROR . "\n" . print_r($importItems[$arUser['EMAIL']], 1));
                    }
                    unset($importItems[$arUser['EMAIL']]);
                } else {
                    $hasErrors = true;
                    $this -> logger -> log("CRITICAL", "Users email " . $arUser['EMAIL'] . " duplicated in DataBase");
                }
            }
        } else {
            $this -> logger -> log("ERROR", "Users list empty");
        }

        foreach ($importItems as $email => $item){
            $hasErrors = true;
            $this -> logger -> log("ERROR", "User $email not found in DataBase");
        }
        if(!$hasErrors)echo "success";
    }
}