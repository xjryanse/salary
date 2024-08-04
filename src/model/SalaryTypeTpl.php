<?php
namespace xjryanse\salary\model;

/**
 * 发薪模板
 */
class SalaryTypeTpl extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'type_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_type',
            'uni_field' =>'id',
            'del_check' => true,
        ]
    ];

}