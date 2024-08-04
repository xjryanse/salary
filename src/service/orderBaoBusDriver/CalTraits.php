<?php

namespace xjryanse\salary\service\orderBaoBusDriver;

use xjryanse\prize\logic\DriverSalaryLogic;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\bus\service\BusTypeService;
use xjryanse\bus\service\BusService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
use xjryanse\logic\Number;
/**
 * 分页复用列表
 */
trait CalTraits{
    /**
     * 计算计佣营运额
     * 
     *  ["prize"] =&gt; float(585)
        ["formula"] =&gt; string(53) "(下单营业额:622+票据扣点:-37+客户返点:0)"
        ["group_id"] =&gt; string(19) "5381687139153440768"
        ["dataForm"] =&gt; array(4) {
     * @return type
     */
    protected static function calCalculatePrize($data, $uuid = ''){
        $arr = DriverSalaryLogic::calYing($data);
        // 20240110:更新写入明细
        if($uuid){
            self::getInstance($uuid)->listToDtl($arr['dataForm']['lists']);
        }
        return Arrays::value($arr, 'prize') ? : null;
    }
    // 营运额计算描述
    protected static function calCalculatePrizeDesc($data){
        $arr = DriverSalaryLogic::calYing($data);
        return Arrays::value($arr, 'formula');
    }
    /**
     * 计算抽成率
     * @return type
     */
    protected static function calRate($data){
        // 20231213：增加
        $data['busTypeLevel']       = BusTypeService::getInstance($data['bus_type_id'])->fLevel();
        $data['busLevel']           = BusService::getInstance($data['bus_id'])->calBusTypeLevel();
        // 20231213:判断车型级别和车辆级别是否相同:会影响车辆抽成率计算:
        // 当车辆>车型:1；当车辆=车型:0；当车辆<车型:-1
        $data['isBusTypeLevelSame'] = Number::signum(Number::minus($data['busLevel'], $data['busTypeLevel']));

        return DriverSalaryLogic::calRate($data);
    }
    /**
     * 计算已锁定的费用数据
     * 20231124:处理成多退少补
     */
    protected static function calLockedPrizeByBaoBusDriverId($baoBusDriverId){
        
        $timeTable = SalaryTimeService::getTable();
        $fields = [];
        $fields[] = 'sum(distribute_prize) as distributePrize';
        $fields[] = 'sum(eat_money) as eatMoney';
        $fields[] = 'sum(other_money) as otherMoney';
        $fields[] = 'count(1) as number';

        $con = [];
        $con[] = ['a.bao_bus_driver_id','=',$baoBusDriverId];
        $con[] = ['b.time_lock','=',1];
        $info = self::mainModel()->alias('a')
                ->join($timeTable. ' b','a.time_id=b.id')
                ->where($con)
                ->field(implode(',',$fields))
                ->find();
        
        return $info;
    }
}
