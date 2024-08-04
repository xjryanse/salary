<?php

namespace xjryanse\salary\service\userItem;

use xjryanse\salary\service\SalaryUserService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Arrays;
/**
 * 
 */
trait CalTraits{
    /**
     * 20230929: 计算薪资总额(应发合计)
     * TODO
     */
    public static function calSalaryTotalBySalaryUserId($salaryUserId){
        $lists  = SalaryUserService::getInstance($salaryUserId)->objAttrsList('salaryUserItem');

        $con    = [];
        $con[]  = ['salary','>',0];
        return Arrays::sum(array_column(Arrays2d::listFilter($lists, $con),'salary'));
    }

    /**
     * 20230929:计算扣款
     */
    public static function calSalaryCutBySalaryUserId($salaryUserId){
        $lists  = SalaryUserService::getInstance($salaryUserId)->objAttrsList('salaryUserItem');

        $con    = [];
        $con[]  = ['salary','<',0];
        return Arrays::sum(array_column(Arrays2d::listFilter($lists, $con),'salary'));
    }
    
    /**
     * 20231212: 计算薪资总额(应发合计:按项)
     * TODO
     */
    public static function calSalaryItemTotalBySalaryUserId($salaryUserId){
        $lists  = SalaryUserService::getInstance($salaryUserId)->objAttrsList('salaryUserItem');

        $addItemIds = SalaryItemService::addItemIds();
        $con    = [];
        $con[]  = ['item_id','in',$addItemIds];
        
        return Arrays::sum(array_column(Arrays2d::listFilter($lists, $con),'salary'));
    }

    /**
     * 20231212:计算扣款
     */
    public static function calSalaryItemCutBySalaryUserId($salaryUserId){
        $lists  = SalaryUserService::getInstance($salaryUserId)->objAttrsList('salaryUserItem');

        $cutItemIds = SalaryItemService::cutItemIds();
        $con    = [];
        $con[]  = ['item_id','in',$cutItemIds];
        return Arrays::sum(array_column(Arrays2d::listFilter($lists, $con),'salary'));
    }
    
    /**
     * 20230929:计算实发
     */
    public static function calSalaryRealBySalaryUserId($salaryUserId){
        $lists  = SalaryUserService::getInstance($salaryUserId)->objAttrsList('salaryUserItem');
        return Arrays::sum(array_column($lists,'salary'));
    }
    /**
     * 
     * @param type $userId  用户
     * @param type $typeId  类型
     * @param type $timeId  时间
     * @param type $con
     * @return type
     */
    public static function calUserTypeTimeHasLog($userId, $typeId, $timeId,$con = []){
        // 20240510弃用
        self::stopUse(__METHOD__);
        $con[]  = ['user_id','in',$userId];
        $con[]  = ['salary_type_id','in',$typeId];
        $con[]  = ['time_id','in',$timeId];

        $count  = self::where($con)->count();
        return $count ? true: false;
    }
    /**
     * 
     * 20240803:列表按项求和，适用于计算个税等需要多项合计的情况
     * @param type $list
     */
    public static function calSummaryByItem($list, $preFix=''){
        $items = SalaryItemService::staticConList();
        $obj = array_column($items, 'item_key','id');
        
        $sumData = [];
        foreach($obj as $k=>$v){
            $cone = [['item_id','=',$k]];
            $sumData[$preFix.$v] = round(Arrays2d::sum(Arrays2d::listFilter($list, $cone), 'salary'),2);
        }
        
        return $sumData;
    }
    
}
