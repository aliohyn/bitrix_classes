<?php

include "HighloadManager.php";


/**
 * Import users from 1C array to bitrix
 * Class ImporterUsers
 */
class ImporterVacations implements IImportManager
{
    private $logger;

    private function prepareFields($vacations, $email_to_user_id)
    {
        $arItems = [];
        foreach ($vacations as $v){
            if(!$v["ИДДокументаПланирования"]) continue;
            if($email_to_user_id[$v["Email"]]){
                $arItems[] = [
                    "UF_USER_ID" => $email_to_user_id[$v["Email"]],
                    "UF_EMAIL" => $v["Email"],
                    "UF_DATE_BEGIN" => $v["Начало"],
                    "UF_DATE_END" => $v["Окончание"],
                    "UF_STATUS" => $v["Статус"],
                    "UF_WORK_PERIOD_TO" => $v["РабочийПериодС"],
                    "UF_WORK_PERIOD_FROM" => $v["РабочийПериодПо"],
                    "UF_COMPANY_XML_ID" => $v["ИдКомпании"],
                ];
            }
        }
        return $arItems;
    }

    /**
     * @param $data
     * @param $logger
     * @return bool
     */

    public function start($data, $logger)
    {
        $hasErrors = false;
        $this -> logger = $logger;

        if(!$data['НачалоВыборки']){
            $this -> logger -> log("ERROR", "Empty parameter 'НачалоВыборки'");
            echo "Error - empty parameter 'НачалоВыборки'";
            return false;
        }

        if(!$data['Отпуск'][0] && $data['Отпуск']){
            $data['Отпуск'] = array($data['Отпуск']);
        }

        $vacations = $data['Отпуск'];

        if($vacations[0]){
            foreach ($vacations as $v){
                $emails[] = $v["Email"];
                if(!$company_xml_id){
                    $company_xml_id = $v["ИдКомпании"];
                }
                if(!$v["ИдКомпании"] || $v["ИдКомпании"] != $company_xml_id){
                    $this -> logger -> log("ERROR", "All users must have same company");
                    return false;
                }
            }
        }
        if($company_xml_id) {
            if ($emails[0]) {
                $rsUsers = CUser::GetList(($by = "id"), ($order = "desc"), ["EMAIL" => implode(" | ", $emails)],
                    ['FIELDS' => ['ID', 'EMAIL']]);
                while ($arUser = $rsUsers->GetNext(true, false)) {
                    $email_to_user_id[$arUser['EMAIL']] = $arUser['ID'];
                }
            }
            $vacations = $this->prepareFields($vacations, $email_to_user_id);

            $HM = new HighloadManager(['NAME' => 'Vacations']);

            // Deleting old vacations
            $curVacations = $HM->GetList(['UF_COMPANY_XML_ID' => $company_xml_id, '>=UF_DATE_BEGIN' => $data['НачалоВыборки']]);
            foreach ($curVacations as $v) {
                $HM->Delete($v['ID']);
            }


            // Add new vacations
            foreach ($vacations as $v) {
                $HM->Add($v);
            }
        } else {
            $hasErrors = "Y";
            $this -> logger -> log("ERROR", "Не указано 'ИдКомпании'");
        }
        if(!$hasErrors){
            echo "success";
        }
        return true;
    }
}