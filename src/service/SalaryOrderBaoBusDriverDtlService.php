<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\DbOperate;
use think\Db;

/**
 * 员工薪资总表
 */
class SalaryOrderBaoBusDriverDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    // 20231004:批量处理的逻辑函数
    use \xjryanse\traits\MainModelBatchTrait;
    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryOrderBaoBusDriverDtl';
    
    /**
     * 用于删除SalaryOrderBaoBusDriver记录时，同步删除明细
     * @param type $salaryOrderBaoBusDriverId
     */
    public static function clearBySalaryOrderBaoBusDriverId($salaryOrderBaoBusDriverId){
        $con[] = ['salary_order_bao_bus_driver_id','in',$salaryOrderBaoBusDriverId];
        // 20240126
        // TODO:替换 使用listSetUUData替代
        $lists = self::listWithGlobal($con);
        foreach($lists as $v){
            self::getInstance($v['id'])->deleteRam();
        }
    }
    
    /**
     * 创建sql
     * 20240510弃用
     */
    public static function buildSql($con = []){
        self::stopUse(__METHOD__);
        $fields = self::where($con)->column('distinct field');
        $field      = [];
        $field[]    = 'salary_order_bao_bus_driver_id';
        foreach ($fields as &$v) {
            $field[] = "MIN(CASE field when '" . $v . "' then field_money else 0 end) '" . $v . "'";
        }
        $sql = self::where($con)->field($field)->group('salary_order_bao_bus_driver_id')->buildSql();
        return $sql;
    }

    /**
     * 20240510弃用
     * @param type $orderBaoBusDriverIds
     * @return type
     */
    public static function dataArr($orderBaoBusDriverIds){
        self::stopUse(__METHOD__);
        $con[] = ['salary_order_bao_bus_driver_id','in',$orderBaoBusDriverIds];
        $sql = self::buildSql($con);
        // dump($sql);
        $arr = Db::query($sql);
        return $arr;

    }
    
}
