<?php
namespace xjryanse\salary\model;

/**
 * 包车司机薪资表
 */
class SalaryOrderBaoBusDriver extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    
    public static $uniFields = [
        [
            'field'         =>'bao_bus_driver_id',
            'uni_name'      =>'order_bao_bus_driver',
            'uni_field'     =>'id',
            'in_statics'    => true,
            'del_check'     => true
        ],
        [
            'field'     =>'customer_id',
            'uni_name'  =>'customer',
            'uni_field' =>'id',
            'del_check' => true
        ],
        [
            'field'     =>'user_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
            'del_msg'   => '该用户有包车薪资项，不可删'
        ],
        [
            'field'     =>'driver_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
            'del_msg'   => '该司机有包车薪资项，不可删'
        ],
        [
            'field'     =>'bus_id',
            // 去除prefix的表名
            'uni_name'  =>'bus',
            'uni_field' =>'id',
            'in_list'   => false,            
            'del_check' => true,
            'del_msg'   => '该车辆有包车薪资项，不可删'
        ],
        [
            'field'     =>'time_id',
            // 去除prefix的表名
            'uni_name'  =>'salary_time',
            'uni_field' =>'id',
            'del_check' => true,
            'del_msg'   => '该时段有包车薪资项，不可删',
            'reflect_field' => [
                // timeLock 映射到表 salary_time time_lock
                'timeLock'  => ['key'=>'time_lock','nullVal'=>0],
            ],
        ]
    ];

    public static $uniRevFields = [
        [
            'table'         =>'salary_user_item_dtl',
            'field'         =>'from_table_id',
            'uni_field'     =>'id',
            'exist_field'   =>'isSalaryOrderBaoBusDriverExist',
            'condition'     =>[
                // 关联表，即本表
                'from_table'=>'{$uniTable}'
            ]
        ]
    ];
    
    // 20231204:默认的时间字段，每表最多一个
    public static $timeField = 'start_time';
    /**
     * 以bao_bus_driver_id聚合的sql
     */
    public static function salaryBaoBusDriverIdGroupSql($con = []){
        $fields = [];
        $fields[] = 'bao_bus_driver_id';
        $fields[] = 'count(1) as salaryCount';
        $fields[] = 'sum(distribute_prize) as salaryDistributePrize';
        $fields[] = 'sum(calculate_prize) as salaryCalculatePrize';
        $fields[] = 'sum(grant_money) as salaryGrantMoney';
        $fields[] = 'sum(eat_money) as salaryEatMoney';
        $fields[] = 'sum(other_money) as salaryOtherMoney';
        $fields[] = 'sum(moneyAll) as salaryMoneyAll';
        
        $sql = self::where($con)->field(implode(',',$fields))->group('bao_bus_driver_id')->buildSql();

        return $sql;
    }
}