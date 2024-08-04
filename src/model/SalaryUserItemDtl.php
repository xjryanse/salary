<?php
namespace xjryanse\salary\model;

/**
 * 员工薪资明细详情
 */
class SalaryUserItemDtl extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'time_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_time',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'user_item_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_user_item',
            'uni_field' =>'id',
            'del_check' => true,
        ]
    ];

}