<?php

namespace xjryanse\salary\service\userItem;

use xjryanse\logic\Arrays;
use xjryanse\salary\service\SalaryUserService;
/**
 * 
 */
trait DimTraits{
    /**
     * 
     * @param type $salaryUserId
     */
    public static function dimListsBySalaryUserId($salaryUserId, $con = []){
        $con[]  = ['salary_user_id','in',$salaryUserId];
        $lists  = self::where($con)->select();
        return $lists ? $lists->toArray() : [];
    }
    
    /**
     * 时段取列表
     */
    public static function dimListByTimeId($timeId, $con = []){
        $con[] = ['time_id','=',$timeId];
        $lists = self::where($con)->select();
        return $lists ? $lists->toArray() : [];
    }
}
