<?php
namespace xjryanse\salary\model;

/**
 * 员工薪资总表
 */
class SalaryUser extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            //【冗】
            'field'     =>'time_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_time',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'user_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'salary_type_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_type',
            'uni_field' =>'id',
            'del_check' => true,
        ]
    ];

}