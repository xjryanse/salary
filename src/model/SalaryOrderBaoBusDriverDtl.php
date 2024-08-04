<?php
namespace xjryanse\salary\model;

/**
 * 包车司机薪资表
 */
class SalaryOrderBaoBusDriverDtl extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    
    public static $uniFields = [
        [
            'field'         =>'salary_order_bao_bus_driver_id',
            'uni_name'      =>'salary_order_bao_bus_driver',
            'uni_field'     =>'id',
            'in_statics'    => true,
            'in_list'       => false,
            // 'del_check'     => true
        ],
    ];

    public static $uniRevFields = [];

    // 20231204:默认的时间字段，每表最多一个
    public static $timeField = 'start_time';
    /**
     * 以bao_bus_driver_id聚合的sql
     */
    public static function salaryBaoBusDriverIdGroupSql($con = []){
        $fields     = [];
        $fields[]   = 'bao_bus_driver_id';
        $fields[]   = 'count(1) as salaryCount';
        $fields[]   = 'sum(distribute_prize) as salaryDistributePrize';
        $fields[]   = 'sum(calculate_prize) as salaryCalculatePrize';
        $fields[]   = 'sum(grant_money) as salaryGrantMoney';
        $fields[]   = 'sum(eat_money) as salaryEatMoney';
        $fields[]   = 'sum(other_money) as salaryOtherMoney';
        $fields[]   = 'sum(moneyAll) as salaryMoneyAll';

        $sql = self::where($con)->field(implode(',',$fields))->group('bao_bus_driver_id')->buildSql();

        return $sql;
    }
}