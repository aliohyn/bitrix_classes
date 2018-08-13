<?php
namespace App;

interface DataManagerInterface
{
    // fields info
    public function getFieldsInfo();

    // helpers for function Get
    public function order($arr);
    public function select($arr);
    public function filter($arr);
    public function limit($arr);
    public function resetGet();

    // CRUD
    public function add($arr, $params = []);
    public function get();
    public function update($id, $arr, $params = []);
    public function delete($id, $params = []);
}
