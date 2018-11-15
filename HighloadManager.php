<?php
namespace App;

class HighloadManager implements DataManagerInterface
{
    // variables for get() params
    private $order;
    private $select;
    private $filter;
    private $limit;

    private $entity;
    private $entityDataClass;
    
    private $info;
    private $fields;

    /**
     * need show errors
     * @var bool
     */
    private $showErrors;

    function __construct($highloadFilter, $showErrors = true)
    {
        $this -> showErrors = $showErrors;
        if($highloadFilter){
            \CModule::IncludeModule('highloadblock');
            $rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>$highloadFilter));
            if($arData=$rsData->fetch()) {
                $this -> info = $arData;
                $this -> entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arData);
                $this -> entityDataClass = $this -> entity -> getDataClass();

                // Список полей
                $highloadFields = $this -> entity -> GetFields();

                foreach($highloadFields as $k => $v){
                    $this -> fields[] = $k;
                }
            }
            else{
                $this -> criticalError(__METHOD__ . ' - ' . ' Highload is not detected by filter ' . print_r($highloadFilter, 1));
            }
        }
        else{
            $this -> criticalError(__METHOD__ . ' - ' . ' Empty IBLOCK_FILTER');
        }
        $this -> resetGet();
    }

    public function order($arr)
    {
        $this -> order = $arr;
        return $this;
    }

    public function select($arr)
    {
        $this -> select = $arr;
        return $this;
    }

    public function filter($arr)
    {
        $this -> filter = $arr;
        return $this;
    }

    public function limit($num)
    {
        $this -> limit = $num;
        return $this;
    }

    public function resetGet()
    {
        $this -> order = ['ID' => 'DESC'];
        $this -> select = ['*'];
        $this -> filter = [];
        $this -> limit = false;
    }

    public function get()
    {
        $entity_data_class = $this -> entityDataClass;
        $params = [
            'order' => $this -> order,
            'filter' => $this -> filter,
            'select' => $this -> select,
            'limit' => $this -> limit
        ];

        $res = $entity_data_class::GetList($params);
        while ($ar_res = $res -> fetch()) {
            $result[] = $ar_res;
        }

        $this -> resetGet();
        return $result;
    }

    /**
     * Добавление элемента Highloadblock
     * @param $arFields array
     * @return bool|integer
     */
    public function add($arFields, $params = [])
    {
        $hasField = false;
        foreach($this -> fields as $code){
            if($arFields[$code]){
                $hasField = true;
            }
        }
        if($hasField){
            $entity_data_class = $this -> entityDataClass;
            $result = $entity_data_class::add($arFields);
            if(!$result->isSuccess()){
                $this -> criticalError(__METHOD__ . ' - ' . print_r($result->getErrorMessages(), 1));
                return false;
            }
            return $result->getId(); //Id нового элемента
        }
        else{
            $this -> criticalError(__METHOD__ . ' - ' . 'Can\'t add empty fields');
            return false;
        }
    }

    /**
     * @param $id
     * @param $arFields
     * @return \Bitrix\Main\Entity\UpdateResult
     */
    public function update($id, $arFields, $params = [])
    {
        $arFields = $this -> fileFieldsPrepare($id, $arFields);
        $entity_data_class = $this -> entityDataClass;
        if($arFields) {
            return $entity_data_class::update($id, $arFields);
        } else {
            return false;
        }
    }

    /**
     * Prepare file fields for update
     * @param $id
     * @param $arFields
     * @return array
     */
    private function fileFieldsPrepare($id, $arFields)
    {
        // multiple files update
        $info = $this -> GetFieldsInfo();
        foreach ($info['FIELDS'] as $item) {
            if($item['TYPE'] == 'file' && $item['MULTIPLE'] == 'Y' && $arFields[$item['CODE']]) {
                $multiple_files[] = $item['CODE'];
            }
        }

        if($multiple_files[0]) {

            $cur_elem = $this -> GetList(['ID' => $id]);
            $cur_elem = $cur_elem[0];
            foreach ($multiple_files as $code){
                $arUpdateFiles = [];

                // checking deleted file id
                foreach ($arFields[$code] as $k => $val) {
                    if($val['del']) {
                        if(!in_array($k, $cur_elem[$code])){
                            unset($arFields[$code][$k]);
                        }
                    } elseif($val['name']) {
                        $arUpdateFiles[] = $val;
                    }
                }

                // saving old values
                if($arFields[$code]) {
                    foreach ($cur_elem[$code] as $field_id) {
                        if (isset($arFields[$code][$field_id]['del'])) {
                            continue;
                        }
                        $arFile = [
                            'old_id' => $field_id,
                            'error' => 4
                        ];
                        $arUpdateFiles[] = $arFile;
                    }
                    $arFields[$code] = $arUpdateFiles;
                } else {
                    unset($arFields[$code]);
                }
            }
        }

        return $arFields;
    }

    /**
     * Удаление элемента Highloadblock
     * @param $id
     */
    public function delete($id, $params = [])
    {
        if($id){
            $entity_data_class = $this -> entityDataClass;
            $entity_data_class::delete($id);
        }
        else{
            $this -> criticalError(__METHOD__ . ' - ' . ' Empty delete ID');
        }
    }



    /*
     * Highloadblock information (ID, NAME, TABLE_NAME)
     * @return array
     */
    public function GetInfo(){
        return $this -> info;
    }

    /**
     * Highloadblock fields
     * @return array
     */
    public function GetFields(){
        return $this -> fields;
    }

    /**
     * Highloadblock fields information (NAME, CODE, TYPE, IS_REQUIRED)
     * @return array
     */
    public function GetFieldsInfo()
    {
        $result = [];
        if($this->info['ID']) {
            $res = \CUserTypeEntity::GetList(['SORT' => 'ASC'],
                ['ENTITY_ID' => 'HLBLOCK_' . $this->info['ID'], 'LANG' => 'ru']);
            while ($ar_res = $res->GetNext(false, false)) {
                if ($ar_res['USER_TYPE_ID'] == 'enumeration') {
                    $userFieldId[] = $ar_res['ID'];
                }
                $HLFields[$ar_res['FIELD_NAME']] = [
                    'ID' => $ar_res['ID'],
                    'NAME' => $ar_res['EDIT_FORM_LABEL'],
                    'CODE' => $ar_res['FIELD_NAME'],
                    'TYPE' => $ar_res['USER_TYPE_ID'],
                    'IS_REQUIRED' => $ar_res['MANDATORY'],
                    'MULTIPLE' => $ar_res['MULTIPLE'],
                    'SORT' => $ar_res['SORT'],
                ];
                $HLFieldsCodeById[$ar_res['ID']] = $ar_res['FIELD_NAME'];
            }
            $result['FIELDS'] = $HLFields;

            if ($userFieldId[0]) {
                $res = \CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $userFieldId]);
                while ($ar_res = $res->GetNext(false, false)) {
                    $result['LISTS'][$HLFieldsCodeById[$ar_res['USER_FIELD_ID']]][$ar_res['ID']] = $ar_res['VALUE'];
                }
            }
        } else {
            $this -> criticalError(__METHOD__ . ' - ' . ' Empty highload ID');
        }
        return $result;
    }

    /**
     * Show critical error
     * @param $errorText
     */
    private function criticalError($errorText)
    {
        if($this -> showErrors) {
            echo '<div style="color:red">Error in ' . $errorText . '</div>';
        }
    }
}
