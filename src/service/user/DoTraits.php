<?php

namespace xjryanse\salary\service\user;

use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\salary\service\SalaryTypeItemService;
use xjryanse\salary\service\SalaryTypeService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Strings;
use xjryanse\generate\service\GenerateTemplateService;
use xjryanse\generate\service\GenerateTemplateLogService;
use xjryanse\user\service\UserService;
use xjryanse\bus\service\BusService;
/**
 * 
 */
trait DoTraits{

    public static function monthlyExport($param){
        $typeId = Arrays::value($param, 'salary_type_id');
        $timeId = Arrays::value($param, 'time_id');
        // 开发项目
        $itemIds = SalaryTypeItemService::dimItemIdsByTypeId($typeId);
        
        $dataArr    = SalaryUserItemService::salaryTypeTimeUserList($typeId, $timeId);
        $userObj    = UserService::arrDynenum($dataArr, 'user_id', 'realname');
        $busObj     = BusService::arrDynenum($dataArr, 'bus_id', 'licence_plate');
        // 按出车排序
        Arrays2d::sort($dataArr, 'salaryBaoBusDriverCount','desc');
        // 20231213:司机最常开几座车
        $driverBusSeatsArr = [];
        foreach($dataArr as &$v){
            $v['realname'] = Arrays::value($userObj, $v['user_id']);
            $v['licence_plate'] = Arrays::value($busObj, $v['bus_id']);
            if(!isset($driverBusSeatsArr[$v['user_id']])){
                $driverBusSeatsArr[$v['user_id']] = $v['busSeats'];
            }
        }
        // 主车辆从大到小
        // TODO：自有司机排前面，外调司机排后面
        foreach($dataArr as &$ve){
            // 主座位数降序
            $driverSeat = Strings::preKeepLength(Arrays::value($driverBusSeatsArr, $ve['user_id']),2);
            // 出车数降序
            $baoBusCount = Strings::preKeepLength($ve['salaryBaoBusDriverCount'],3);

            $ve['sort'] = $driverSeat.'_'.$baoBusCount.'_'.$ve['user_id'];
        }
        Arrays2d::sort($dataArr, 'sort', 'desc');
        // 列表，列表字段，关联字段（id）,column字段
        // 20231213：计算小计合计
        $resArr = self::calXiaojiHeji($dataArr, $itemIds);

        $templateId = GenerateTemplateService::keyToId('driverSalary');
        
        //
        $timeName = SalaryTimeService::getInstance($timeId)->fName();
        $typeName = SalaryTypeService::getInstance($typeId)->fTypeName();
        
        $replace    = [];
        $replace[]  = [1, 2, $timeName.$typeName];
        $res = GenerateTemplateLogService::export($templateId, $resArr, $replace);

        $resp['fileName'] = time() . '.xlsx';
        $resp['url'] = $res['file_path'];
        
        return $resp;
    }
    
    private static function calXiaojiHeji($dataArr, $itemIds){
        $sumKeys = ['salary_item_total','salary_item_cut','salary_real'];
        foreach ($itemIds as $iId) {
            $sumKeys[]  = 's'.$iId;
        }
        
        $resArr = [];
        // 小计求和
        $tSumData = [];
        // 合计求和
        $allSumData = [];
        foreach($dataArr as $k=>$vf){
            $resArr[] = $vf;
            $nextDriver = $k <count($dataArr) - 1 ? $dataArr[$k+1]['user_id'] : '';
            
            foreach ($sumKeys as $key) {
                // 各项求和：小计
                $tSumData[$key] = Arrays::value($tSumData, $key, 0) +  $vf[$key];
                // 合计求和
                $allSumData[$key] = Arrays::value($allSumData, $key, 0) +  $vf[$key];
            }
            // 如果当前司机不是上一个司机，则增加一行小计
            if(!$nextDriver || $nextDriver != $vf['user_id']){
                $newRow = $tSumData;
                $newRow['licence_plate'] = '小计';
                $newRow['realname'] = '';
                $resArr[] = $newRow;
                //清空小计求和
                $tSumData = [];
            }
        }
        // 合计
        $newRow = $allSumData;
        $newRow['licence_plate'] = '合计';
        $newRow['realname'] = '';
        $resArr[] = $newRow;
        return $resArr;
    }
}
