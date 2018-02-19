<?php

include "HighloadManager.php";

/**
 * Import users from 1C array to bitrix
 * Class ImporterUsers
 */
class ImporterVacationDays implements IImportManager
{
    private $logger;

    private function prepareFields($data, $email_to_user_id)
    {
        $arItems = [];
        foreach ($data['ОстатокОтпуска'] as $v){
            if($email_to_user_id[$v["Email"]]){
                $arItems[] = [
                    "UF_USER_ID" => $email_to_user_id[$v["Email"]],
                    "UF_DATE_UPDATE" => $data['ДатаОстатков'],
                    "UF_ALL_DAYS" => $v["Положено"],
                    "UF_CUR_DAYS" => $v["Дней"],
                    "UF_WORK_PERIOD_TO" => $v["РабочийПериодС"],
                    "UF_WORK_PERIOD_FROM" => $v["РабочийПериодПо"],
                    "UF_COMPANY_XML_ID" => $data["ИДКомпании"],
                ];
            } else {
                $this -> logger -> log("ERROR", "User " . $v["Email"] . " not found in DataBase");
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

        if($data['ОстатокОтпуска'] && !is_array($data['ОстатокОтпуска'])) {
            $data['ОстатокОтпуска'] = [$data['ОстатокОтпуска']];
        }

        foreach ($data['ОстатокОтпуска'] as $ostatok) {
            $emails[] = $ostatok['Email'];
        }

        if ($emails[0]) {
            $rsUsers = CUser::GetList(($by = "id"), ($order = "desc"), ["EMAIL" => implode(" | ", $emails)],
                ['FIELDS' => ['ID', 'EMAIL']]);
            while ($arUser = $rsUsers->GetNext(true, false)) {
                $email_to_user_id[$arUser['EMAIL']] = $arUser['ID'];
            }
            $vacationDays = $this -> prepareFields($data, $email_to_user_id);

            $HM = new HighloadManager(['NAME' => 'VacationDays']);
            foreach ($vacationDays as $arFields) {
                $HM -> add($arFields, 'UF_USER_ID');
            }
        }

        if(!$hasErrors){
            echo "success";
        }
        return true;
    }
}