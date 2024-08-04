<?php
namespace xjryanse\salary\model;

/**
 * 作为财务工资核算的基础表
 */
class SalaryDriverTang extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    
    public static $uniFields = [

    ];

    /**
     * 20230807：反置属性
     * @var type
     */
    public static $uniRevFields = [
        [
            'table'     =>'salary_user_item_dtl',
            'field'     =>'from_table_id',
            'uni_field' =>'id',
            'exist_field'   =>'isSalaryDriverTangExist',
            'condition'     =>[
                // 关联表，即本表
                'from_table'=>'{$uniTable}'
            ]
        ]
    ];
}