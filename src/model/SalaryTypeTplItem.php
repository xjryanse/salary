<?php
namespace xjryanse\salary\model;

/**
 * 发薪模板
 */
class SalaryTypeTplItem extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'tpl_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_type_tpl',
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