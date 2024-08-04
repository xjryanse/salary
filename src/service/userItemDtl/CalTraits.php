<?php

namespace xjryanse\salary\service\userItemDtl;

use xjryanse\logic\Arrays;
use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\logic\Debug;

/**
 * 
 */
trait CalTraits{
    /**
     * 20230929: 计算薪资总额(应发合计)
     * TODO
     */
    public static function calSalaryByUserItemId($userItemId){
        $lists  = SalaryUserItemService::getInstance($userItemId)->objAttrsList('salaryUserItemDtl');
        return Arrays::sum(array_column($lists,'salary'));
    }

    /**
     * 计算哪些项目已删
     * @param type $rawUserItemIds 源项目
     */
    protected static function calDelUserItemIds($rawUserItemIds){
        $cone               = [['user_item_id','in',$rawUserItemIds]];
        // 还存在的项目
        $keepUserItemIds    = self::where($cone)->column('user_item_id');
        // 已删除项目
        $delUserItemIds     = array_diff($rawUserItemIds, $keepUserItemIds);
        return $delUserItemIds;
    }
}
