<?php
namespace xjryanse\salary\model;

/**
 * 员工薪资明细
 */
class SalaryUserItemTpl extends Base
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
            'field'     =>'bus_id',
            // 去除prefix的表名
            'uni_name'  =>'bus',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'item_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_item',
            'uni_field' =>'id',
            'del_check' => true,
        ]

    ];
}