<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use app\driver\service\DriverDailyTangService;
use xjryanse\salary\service\SalaryTimeService;
use Exception;

/**
 * 员工薪资总表
 */
class SalaryDriverTangService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    // 20231004:批量处理的逻辑函数
    // use \xjryanse\traits\MainModelBatchTrait;
    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryDriverTang';
    //直接执行后续触发动作
    protected static $directAfter = true;
    
    use \xjryanse\salary\service\driverTang\CalTraits;
    use \xjryanse\salary\service\driverTang\TriggerTraits;
    use \xjryanse\salary\service\driverTang\FieldTraits;
    use \xjryanse\salary\service\driverTang\SalaryTraits;
    use \xjryanse\salary\service\driverTang\DoTraits;
    
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
            foreach($lists as &$v){
                
            }

            return $lists;
        }, true);
    }
    
    /**
     * 司机日趟记录id,来处理同步数据
     * 其他渠道的保存尽量不使用。
     * 20240531
     * @param type $driverDailyTangId
     */
    public static function dataSyncRamByDriverDailyTangId($driverDailyTangId){
        $driverDailyTangInfo    = DriverDailyTangService::getInstance($driverDailyTangId)->get();
        $salaryTangId           = Arrays::value($driverDailyTangInfo, 'salary_tang_id');
        // 20240531:确保数据有更新
        // 20240630:加判断
        if(!$salaryTangId){
            return false;
        }
        // 20240701:无明细增加删除动作
        if(self::getInstance($salaryTangId)->objAttrsCount('driverDailyTang')){
            // 有count数据
            self::getInstance($salaryTangId)->updateRam(['status'=>1]);
        } else {
            self::getInstance($salaryTangId)->deleteRam();
        }

        return true;
    }
    /**
     * 获取薪资趟id，无时新增
     */
    public static function getSalaryDriverTangIdEG($driverDailyTangId){
        // 提取日期：belong_date;
        // 提取 salary_tang_id
        // belong_date提取time_id;
        // time_id + bus_id + driver_id + circuit_home_id
        // time_id + bus_id + driver_id + circuit_home_id
        $driverDailyTangInfo    = DriverDailyTangService::getInstance($driverDailyTangId)->get();
        if(!$driverDailyTangInfo['dept_id']){
            throw new Exception('部门参数异常，请联系开发');
        }

        $belongDate             = Arrays::value($driverDailyTangInfo, 'belong_date');
        $timeId                 = SalaryTimeService::getTimeIdIfLockNextMonth($belongDate);
        // Debug::dump($belongDate);
        // Debug::dump($timeId);
        // 旧的趟次id
        $oldSalaryTangId        = Arrays::value($driverDailyTangInfo, 'salary_tang_id');

        $dataForId = [
            'time_id'           =>$timeId
            ,'dept_id'          =>Arrays::value($driverDailyTangInfo, 'dept_id')
            ,'bus_id'           =>Arrays::value($driverDailyTangInfo, 'bus_id')
            ,'driver_id'        =>Arrays::value($driverDailyTangInfo, 'driver_id')
            ,'circuit_home_id'  =>Arrays::value($driverDailyTangInfo, 'circuit_home_id')
        ];
        // Debug::dump($dataForId);

        $salaryTangId   = self::commGetIdEG($dataForId);
        
/*
 * 20240630:发现严重的性能问题，需要优化
 * 
 * */
        if($oldSalaryTangId && $oldSalaryTangId != $salaryTangId){
            $oLists = self::getInstance($oldSalaryTangId)->objAttrsList('driverDailyTang');
            if($oLists){
                // 如果有数据，则更新
                self::getInstance($oldSalaryTangId)->updateRam(['status'=>1]);
            } else {
                // 如果无数据，则删除
                self::getInstance($oldSalaryTangId)->deleteRam();
            }
        }
/**/
        return $salaryTangId;
    }

    
}
