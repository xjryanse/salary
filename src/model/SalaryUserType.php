<?php
namespace xjryanse\salary\model;

/**
 * 员工薪资类型（兼职多种薪资）
 */
class SalaryUserType extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'user_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'type_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_type',
            'uni_field' =>'id',
            'del_check' => true,
        ]
    ];

}