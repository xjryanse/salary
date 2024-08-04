<?php

namespace xjryanse\salary\service\driverTang;

use xjryanse\logic\Arrays;
/**
 * 
 */
trait CalTraits{

    /**
     * 20240803:
     * 在指定时段，用户是否有多条记录（换线）
     */
    public static function timeDriverCount($timeId, $driverId){
        $con[] = ['time_id','=',$timeId];
        $con[] = ['driver_id','=',$driverId];
        return self::where($con)->count();
    }
    
    /**
     * 适用于每月多天，计算比比例
     */
    public function calSalaryRate(){
        $info   = $this->get();
        // 20240803:切割比例
        $rate   = Arrays::value($info, 'monthly_date_count') ? $info['real_date_count'] / $info['monthly_date_count'] : 1;
        return $rate;
    }
    
}
