<?php

namespace xjryanse\salary\service\driverTang;

use xjryanse\logic\Arrays;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\user\service\UserService;
use Exception;
/**
 * 
 */
trait DoTraits{

    /**
     * 20240619:复制上月计薪模板
     */
    public function doCopyLastTpl(){
        $keys = ['salary_type_tpl_id'];
        return $this->copyLastOperate($keys);
    }

    /**
     * 20240619:复制上月医社保数据
     */
    public function doCopyLastYsb(){
        $keys = ['is_yibao','is_shebao','is_shebao_sy','is_shebao_gs'];
        return $this->copyLastOperate($keys);
    }
    
    
    private function copyLastOperate($keys){
        $info       = $this->get();
        $timeId     = Arrays::value($info, 'time_id');
        // 计算上一个计薪时段
        $lastTimeId = SalaryTimeService::getInstance($timeId)->calPreTimeId();
        if(!$lastTimeId){
            throw new Exception('没有上个计薪时段数据');
        }
        $lastTimeName = SalaryTimeService::getInstance($lastTimeId)->fName();
        // 提取用户是当前
        $driverId   = Arrays::value($info, 'driver_id');
        $driverName = UserService::getInstance($driverId)->fRealname();

        $con        = [];
        $con[]      = ['driver_id','=',$driverId];
        $con[]      = ['time_id','=',$lastTimeId];
        $lastInfo   = self::where($con)->find();
        if(!$lastInfo){
            throw new Exception($driverName.$lastTimeName.'无数据');
        }
        $lastInfoN = $lastInfo ? $lastInfo->toArray() : [];
        $upData = Arrays::getByKeys($lastInfoN, $keys);
        
        // 上个月的模板写入这个月
        return $this->updateRam($upData);
    }
}
