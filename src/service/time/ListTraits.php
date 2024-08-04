<?php

namespace xjryanse\salary\service\time;

use xjryanse\salary\service\SalaryTypeService;
use xjryanse\logic\Arrays;
use xjryanse\salary\service\SalaryTimeService;
use Exception;
/**
 * 
 */
trait ListTraits{
    /**
     * 薪资类型的发薪批次统计。
     */
    public static function listForSalaryTypeTimeStatics($param){
        // 20230928：发薪批次
        $timeId     = Arrays::value($param,'time_id');
        $year       = Arrays::value($param,'year');

        $timeIds = self::conTimeIds($year, $timeId);

        $salaryTypeIds       = Arrays::value($param,'salary_type_id');
        if(!$salaryTypeIds){
            $lists      = SalaryTypeService::lists();
            $listsArr   = $lists ? $lists->toArray() : [];
            $salaryTypeIds     = array_column($listsArr, 'id');
        }

        return self::salaryStaticsList($timeIds, $salaryTypeIds);
    }
    
    /**
     * 薪资类型的发薪批次统计（按类型区分）
     */
    public static function listForSalaryTypeRoleKeyTimeStatics($param){
        // 20230928：发薪批次
        $timeId     = Arrays::value($param,'time_id');
        $year       = Arrays::value($param,'year');

        $timeIds    = self::conTimeIds($year, $timeId);
        $roleKey    = Arrays::value($param,'role_key');
        if(!$roleKey){
            throw new Exception('role_key必须');
        }

        $salaryTypeIds       = SalaryTypeService::keyToId($roleKey);

        return self::salaryStaticsList($timeIds, $salaryTypeIds);
    }
    
    // 参数，timeId, salaryType
    private static function conTimeIds($year, $timeId = ''){
        // 20230928：发薪批次
        if(!$year && !$timeId){
            // 20231203:没有就查当年
            $year = date('Y');
        }
        
        if($timeId){
            $timeIds = [$timeId];
        } else if($year){
            $timeIds = SalaryTimeService::dimTimeIdsByYear($year);
        }
        return $timeIds;
    }
    
}
