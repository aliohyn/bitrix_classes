<?php
if($_GET['filename']){
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/1c_exchange/import/" . $_GET['filename'];
    if(file_exists($filePath)) {
        if($content = file_get_contents($filePath)) {
            require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

            // Prepare data
            $content = str_replace(array("\n", "\r", " "), "", $content);
            $content = explode(";", $content);
            if($content[0]){
                // Getting current data
                $curItems = [];
                $HM = new HighloadManager(['NAME' => 'Holidays']);
                $res = $HM -> GetList();

                // Delete dates not included in csv file
                foreach ($res as $ar_res){
                    if(!in_array($ar_res['UF_DATE'], $content)){
                        $HM -> Delete($ar_res['ID']);
                    }
                    $curItems[] = $ar_res['UF_DATE'];
                }
                // Add dates not added in highload
                foreach ($content as $v){
                    if($v){
                        if(!in_array($v, $curItems)){
                            $HM -> Add(["UF_DATE" => $v]);
                        }
                    }
                }
                echo "success";
            } else {
                echo "Error - Data not found";
            }
        }
    } else {
        echo "Error - File not exist";
    }
} else {
    echo "Error - Empty parameter filename";
}