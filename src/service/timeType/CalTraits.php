<?php

namespace xjryanse\salary\service\timeType;

use xjryanse\salary\service\SalaryTimeService;
/**
 * 
 */
trait CalTraits{

    /**
     * 计算前一个计薪周期id
     * 用于本月复制上月数据时进行定位
     */
    public static function calIsLockByTimeIdAndTypeId($timeId, $typeId){
        // 如果time锁了，则锁
        if(SalaryTimeService::getInstance($timeId)->fTimeLock()){
            return true;
        }

        // 如果timeType锁了，则锁
        $con[] = ['time_id','=',$timeId];
        $con[] = ['type_id','=',$typeId];

        // 20231226:时间锁定？
        $timeLock = self::where($con)->value('time_lock');
        return $timeLock ? true: false;
    }

}
