<?php

namespace xjryanse\salary\service\orderBaoBusDriver;

use xjryanse\logic\Arrays;
use xjryanse\prize\logic\DriverSalaryLogic;
use xjryanse\prize\service\PrizeRuleService;
use xjryanse\bus\service\BusTypeService;
use xjryanse\bus\service\BusService;
/**
 * 分页复用列表
 */
trait DoTraits {

    public static function doDeleteAllRam($param) {
        $id = Arrays::value($param, 'id');
        return self::deleteAllRam($id);
    }
    /**
     * 20231211：方便开发调试抽点
     */
    public function doCalGrantMoneyData(){
        $data = $this->get();
        return DriverSalaryLogic::calYing($data);
    }
    
    /**
     * 20231211：方便开发调试抽点
     */
    public function doCalRateData(){
        $data = $this->get();
        // 20231213：增加
        $data['busTypeLevel']       = BusTypeService::getInstance($data['bus_type_id'])->fLevel();
        $data['busLevel']           = BusService::getInstance($data['bus_id'])->calBusTypeLevel();
        // 20231213:判断车型级别和车辆级别是否相同:会影响车辆抽成率计算
        $data['isBusTypeLevelSame'] = $data['busTypeLevel'] == $data['busLevel'] ? 1 : 0;

        $time = Arrays::value($data, 'start_time');
        $groupCate = 'driverSalaryRate';
        //TODO：合理吗？
        return PrizeRuleService::getPerPrize($time, $groupCate, $data);
    }
    
}
