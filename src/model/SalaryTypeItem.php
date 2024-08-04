<?php
namespace xjryanse\salary\model;

/**
 * 薪资类型项目
 */
class SalaryTypeItem extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'item_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_item',
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