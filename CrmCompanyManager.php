<?php
namespace App;


class CrmCompanyManager implements DataManagerInterface
{
    private $order;
    private $select;
    private $filter;
    private $limit;

    function __construct()
    {
        \CModule::IncludeModule('crm');
        $this -> resetGet();
    }

    public function getFieldsInfo()
    {
        $result = [];
        $res = \CUserTypeEntity::GetList(['SORT' => 'ASC'], ['ENTITY_ID' => 'CRM_COMPANY', 'LANG' => 'ru']);
        while ($ar_res = $res->GetNext(false, false)) {
            if ($ar_res['USER_TYPE_ID'] == 'enumeration') {
                $userFieldId[] = $ar_res['ID'];
            }
            $CompanyFields[$ar_res['FIELD_NAME']] = [
                'ID' => $ar_res['ID'],
                'NAME' => $ar_res['EDIT_FORM_LABEL'],
                'CODE' => $ar_res['FIELD_NAME'],
                'TYPE' => $ar_res['USER_TYPE_ID'],
                'IS_REQUIRED' => $ar_res['MANDATORY'],
                'MULTIPLE' => $ar_res['MULTIPLE'],
                'SORT' => $ar_res['SORT'],
            ];
            $CompanyFieldsCodeById[$ar_res['ID']] = $ar_res['FIELD_NAME'];
        }

        $result['FIELDS'] = $CompanyFields;

        if ($userFieldId[0]) {
            $res = \CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $userFieldId]);
            while ($ar_res = $res->GetNext(false, false)) {
                $result['LISTS'][$CompanyFieldsCodeById[$ar_res['USER_FIELD_ID']]][$ar_res['ID']] = $ar_res['VALUE'];
            }
        }

        return $result;
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
        $this -> order = ['DATE_CREATE' => 'DESC'];
        $this -> select = [];
        $this -> filter = [];
        $this -> limit = false;
    }

    public function add($arr, $params = [])
    {
        if(!$params['bUpdateSearch']) {
            $params['bUpdateSearch'] = true;
        }
        $obj = new \CCrmCompany();
        $res = $obj -> Add($arr, $params['bUpdateSearch']);
        if(!$res) {
            return ['ERROR' => $obj -> LAST_ERROR];
        } else {
            return ['SUCCESS' => $res];
        }
    }

    public function get()
    {
        $res = \CCrmCompany::GetList($this -> order, $this -> filter, $this -> select, $this -> limit);
        while ($ar_res = $res -> GetNext(false, false)) {
            $companyId[] = $ar_res['ID'];
            $result[] = $ar_res;
        }

        if($companyId) {
            $res = \CCrmFieldMulti::GetList([], ['ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyId]);
            while($ar_res = $res -> Fetch()) {
                $multiple[$ar_res['ELEMENT_ID']][] = $ar_res;
            }
            foreach ($result as $k => $v) {
                if($multiple[$v['ID']]) {
                    foreach ($multiple[$v['ID']] as $v2) {
                        $result[$k][$v2['TYPE_ID']][] = $v2;
                    }
                }
            }
        }

        $this -> resetGet();
        return $result;
    }

    public function update($id, $arr, $params = [])
    {
        if($id < 1) {
            return false;
        }

        if(!isset($params['bUpdateSearch'])) {
            $params['bUpdateSearch'] = true;
        }

        if(!isset($params['bCompare'])) {
            $params['bCompare'] = true;
        }

        $obj = new \CCrmCompany();
        $res = $obj -> Update($id, $arr, $params['bCompare'], $params['bUpdateSearch']);
        if(!$res) {
            return ['ERROR' => $obj -> LAST_ERROR];
        } else {
            return ['SUCCESS' => $id];
        }
    }

    public function delete($id, $params = [])
    {
        if($id < 1) {
            return false;
        }

        $obj = new \CCrmCompany();
        return $obj -> Delete($id, $params);
    }
}
