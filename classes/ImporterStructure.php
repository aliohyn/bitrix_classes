<?php

/**
 * Import users from 1C array to bitrix
 * Class ImporterUsers
 */
class ImporterStructure implements IImportManager
{
    private $logger;
    private $iblock_id;
    private $root_section_id;
    private $hasError;

    /**
     * ImporterStructure constructor.
     */
    function __construct()
    {
        CModule::IncludeModule('iblock');
        $this -> iblock_id = 5;
        $this -> root_section_id = 376;
        $this -> hasError = false;
    }

    /**
     * Get array with users ID and emails
     * @param $companyStruct array
     * @return array
     */

    private function getUsersFromStruct($companyStruct){
        // Get users ID by emails
        $users = array();
        foreach($companyStruct as $arStruct){
            if($arStruct['Руководитель'])
                $emails[] = $arStruct['Руководитель'];

            foreach($arStruct['Кураторы']['Куратор'] as $email) {
                $emails[] = $email;
            }
        }

        $emails = array_unique($emails);
        if($emails[0]){
            $res = CUser::GetList(($by="id"), ($order="desc"), ["EMAIL" => implode(" | ", $emails)], ['FIELDS' => ['ID', 'EMAIL']]);
            while($ar_res = $res -> GetNext(true, false)){
                $users[$ar_res['EMAIL']] = $ar_res['ID'];
            }
        }

        return $users;
    }

    /**
     * Prepare array for import to bitrix
     * @param $companyStruct array from XML
     * @param $email_to_user_id array users id
     * @param $xml_to_id array sections id
     * @return array for CIBlockSection::Add/Update
     */

    private function prepareFields($companyStruct, $email_to_user_id, $xml_to_id, $company_xml_id){
        $items = [];
        foreach ($companyStruct as $k => $arStruct){
            $arStruct['ИД'] = $company_xml_id . trim($arStruct['ИД']);
            $arStruct['Родитель'] = $company_xml_id . trim($arStruct['Родитель']);
            if(!$arStruct["Название"] || !$arStruct['ИД']) {
                continue;
            }

            $arItem = [
                "NAME" => $arStruct["Название"],
                "XML_ID" => $arStruct['ИД']
            ];

            if($xml_to_id[$arStruct['Родитель']]){
                $arItem['IBLOCK_SECTION_ID'] = $xml_to_id[$arStruct['Родитель']];
            } else {
                $arItem['PARENT_SECTION_XML_ID'] = $arStruct['Родитель'];
            }

            if($arStruct["Руководитель"]){
                if($email_to_user_id[$arStruct["Руководитель"]]) {
                    $arItem['UF_HEAD'] = $email_to_user_id[$arStruct["Руководитель"]];
                } else{
                    $this -> hasError = true;
                    $this -> logger -> log("ERROR", "Not found user by email " . $arStruct["Руководитель"]);
                    continue;
                }
            }

            if($arStruct['Кураторы']['Куратор']){
                if(is_array($arStruct['Кураторы']['Куратор'])){
                    foreach ($arStruct['Кураторы']['Куратор'] as $email){
                        if($email_to_user_id[$email]){
                            $arItem['UF_CURATOR'][] = $email_to_user_id[$email];
                        }
                    }
                } elseif($email_to_user_id[$arStruct['Кураторы']['Куратор']]) {
                    $arItem['UF_CURATOR'][] = $email_to_user_id[$arStruct['Кураторы']['Куратор']];
                }
                if($arItem['UF_CURATOR'][0]){
                    $arItem['UF_CURATOR'] = array_unique($arItem['UF_CURATOR']);
                    sort($arItem['UF_CURATOR']);
                }
            }
            $items[$arItem['XML_ID']] = $arItem;
        }
        return $items;
    }

    /**
     * Recursive function to compare two array
     * @param $arr1 array
     * @param $arr2 array
     * @return bool
     */
    private function isArrayEqual($arr1, $arr2){
        if(is_array($arr1) && is_array($arr2)){
            foreach($arr1 as $k1 => $v1) {
                if($k1 == "ID") {
                    continue;
                }
                if(!$this -> isArrayEqual($arr1[$k1], $arr2[$k1])) {
                    return false;
                }
            }
            return true;
        } elseif($arr1 != $arr2) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Compare structure elements
     * @param $a
     * @param $b
     * @return int
     */
    private function cmp_structure($a, $b){
        if($a['УровеньИерархии'] == $b['УровеньИерархии']){
            return 1;
        }
        return ($a['УровеньИерархии'] > $b['УровеньИерархии']) ? 1 : -1;
    }

    /**
     * Start import from array
     * @param $data array
     * @param $logger Logger
     */
    public function start($data, $logger)
    {
        if($data['ИдКомпании'] && $data['Компания']) {
            $this -> logger = $logger;
            $companyStruct = $data['Подразделения']['Подразделение'];

            $email_to_user_id = $this -> getUsersFromStruct($companyStruct);

            // Getting current struct from bitrix

            $res = CIBlockSection::GetList([], ["IBLOCK_ID" => $this -> iblock_id], false, ['ID', 'XML_ID', 'IBLOCK_SECTION_ID', 'UF_HEAD', 'UF_CURATOR', 'NAME']);
            while ($ar_res = $res->GetNext(true, false)) {
                if($ar_res['UF_CURATOR'][0]){
                    sort($ar_res['UF_CURATOR']);
                } else {
                    unset($ar_res['UF_CURATOR']);
                }

                foreach ($ar_res as $k => $v) {
                    if(!$v) unset($ar_res[$k]);
                }

                if($ar_res['XML_ID']){
                    $xml_to_id[$ar_res['XML_ID']] = $ar_res['ID'];
                    $curSections[$ar_res['XML_ID']] = $ar_res;
                }
            }

            $bs = new CIBlockSection;
            // create/update company root
            if($curSections[$data['ИдКомпании']]){
                // update only if not equal
                if($curSections[$data['ИдКомпании']]['NAME'] != $data['Компания']){
                    $res = $bs->Update($curSections[$data['ИдКомпании']]['ID'], ["NAME" => $data['Компания']]);
                    if($res) {
                        $root_id = $curSections[$data['ИдКомпании']]['ID'];
                        $this->logger->log("DEBUG", "Updated company with ID = " . $curSections[$data['ИдКомпании']]['ID']);
                    } else {
                        $this -> hasError = true;
                        $this->logger->log("ERROR", "Updated company with ID = " . $curSections[$data['ИдКомпании']]['ID']);
                    }
                }
            } else {
                $arFields = [
                    "IBLOCK_ID" => $this -> iblock_id,
                    "NAME" => $data['Компания'],
                    "XML_ID" => $data['ИдКомпании'],
                    "IBLOCK_SECTION_ID" => $this -> root_section_id
                ];
                $root_id = $bs -> Add($arFields);
                $this -> logger -> log("DEBUG", "Created company with ID = " . $root_id);
            }


            // create/update companies
            if($root_id){

                // sorting for correct creating (first level before second)
                usort($companyStruct, array("ImporterStructure", "cmp_structure"));

                $importItems = $this -> prepareFields($companyStruct, $email_to_user_id, $xml_to_id, $data['ИдКомпании']);
                foreach($importItems as $xml_id => $arItem) {
                    if($arItem['PARENT_SECTION_XML_ID'] && $xml_to_id[$arItem['PARENT_SECTION_XML_ID']]){
                        $arItem['IBLOCK_SECTION_ID'] = $xml_to_id[$arItem['PARENT_SECTION_XML_ID']];
                    } elseif(!$arItem['IBLOCK_SECTION_ID']) {
                        $arItem['IBLOCK_SECTION_ID'] = $root_id;
                    }

                    unset($arItem['PARENT_SECTION_XML_ID']);

                    if($xml_to_id[$xml_id]){
                        if(!$this -> isArrayEqual($curSections[$xml_id], $arItem)) {
                            pr($arItem);
                            if(!$bs -> Update($xml_to_id[$xml_id], $arItem)){
                                $this -> hasError = true;
                                $this -> logger -> log("ERROR", $bs -> LAST_ERROR . "\n" . print_r($arItem, 1));
                            } else {
                                $this -> logger -> log("DEBUG", "Updated section with ID = " . $xml_to_id[$xml_id]);
                            }
                        }
                    } else {
                        $arItem['IBLOCK_ID'] = $this -> iblock_id;
                        $sect_id = $bs -> Add($arItem);
                        if($sect_id) {
                            $xml_to_id[$xml_id] = $sect_id;
                            $this -> logger -> log("DEBUG", "Created section with ID = " . $sect_id);
                        } else {
                            $this -> hasError = true;
                            $this -> logger -> log("ERROR", "Not created section " . print_r($arItem, 1));
                        }
                    }
                }
            }
            if(!$this -> hasError){
                echo "success";
            }
        }
    }
}