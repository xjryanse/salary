<?php

namespace xjryanse\salary\service\driverTang;


use xjryanse\salary\service\SalaryUserItemDtlService;
use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\salary\service\SalaryTypeTplService;
use xjryanse\salary\service\SalaryTypeItemService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\prize\service\PrizeRuleService;
use xjryanse\salary\service\SalaryDriverTangService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
use Exception;
/**
 * 
 */
trait SalaryTraits{
    
    public function toSalaryUserItemDtlSync(){
        // 20240603：匹配一下模板的工资
        $this->matchTplSalary();
        
        $info = $this->get();
        
        // 20231001:先提取一下项目
        $obj = SalaryItemService::keyIdObj();
//
//        // 【1】出车抽成：grant_money
//        // 【2】餐费：eat_money
//        // 【3】其他补贴：other_money
//        // 金额写入
//        $busId = Arrays::value($info, 'bus_id');
        $reflects = [
            // 值无实际意义了
            // 出勤
            'dailyTang'     =>'id',
            // 工龄
            'workYears'     =>'id',
            // 高温补贴
            'gwbt'          =>'id',
            // 超勤奖
            'chaoQin'       =>'id',
            // 洗车补助
            'xcbz'          =>'id',
            // 其他补贴
            'qtbt'          =>'id',
            'ysb'           =>'id'
            
//            'tangGrant'     =>'grant_money',
//            'tangOther'     =>'other_money',
//            'tangFinance'   =>'finance_money',
        ];
//        
        // Debug::dump($obj);
        foreach($obj as $k=>$v){
            if($k && Arrays::value($reflects, $k)){
                // 餐
                self::toSalaryUserItemDtl($this->uuid, $v, $info);
            }
        }

        return true;
    }

    protected static function toSalaryUserItemDtl($id, $itemId, $data = []){
        $typeId         = '5607491131498344449';
        
        $fromTable      = self::getTable();
        $fromTableId    = $id;
//
        $salaryItem = SalaryItemService::getInstance($itemId)->get();
        $prizeGroupKey = Arrays::value($salaryItem, 'prize_group_key'); 
        if(!$prizeGroupKey){
            throw new Exception('未配置工资计算规则，请联系开发-itemId'.$itemId);
        }
        $info = self::getInstance($id)->get();
        $timeId     = Arrays::value($data, 'time_id');
        $startTime  = SalaryTimeService::getInstance($timeId)->fStartTime();
        // 归属月份，用于计算高温补贴
        $info['month'] = date('m',strtotime($startTime));
        
        $pRes = self::salaryWithPrizeGroupKey($info, $prizeGroupKey);
        
        // 20240803:切割比例
        $rate       = self::getInstance($id)->calSalaryRate();
        $typeItem   = SalaryTypeItemService::getByTypeItem($typeId, $itemId);
        $rateSep    = Arrays::value($typeItem, 'rate_sep');
        $count      = SalaryDriverTangService::timeDriverCount($info['time_id'], $info['driver_id']);
        
        $salaryAll = Arrays::value($pRes, 'prize') ? : 0;
        // Debug::dump($pRes);
        $sData                  = [];
        $sData['salary']        = $count > 1 && $rateSep 
                ? $salaryAll * $rate 
                : $salaryAll;
        $sData['formula_str']   = Arrays::value($pRes, 'formula');

        $busId = Arrays::value($data, 'bus_id');        


        $userItemId     = SalaryUserItemService::matchIdWithTimeId($data['driver_id'], $itemId,$busId, $data['time_id'], $typeId);

        $res            = SalaryUserItemDtlService::dataSyncRam($userItemId, $fromTable, $fromTableId, $sData);

        return $res;
    }
    /**
     * 
     */
    protected static function salaryWithPrizeGroupKey($data, $prizeGroupKey){
        
        $timeId = Arrays::value($data, 'time_id');
        $startTime = SalaryTimeService::getInstance($timeId)->fStartTime();
        
        $groupCate = 'salary';

        $con = [['group_key','=',$prizeGroupKey]];
        $res = PrizeRuleService::getPrizeWithFormula($startTime, $groupCate, $data, $con);
        return $res;
    }
    /**
     * 匹配模板工资
     */
    protected function matchTplSalary(){
        $info   = $this->get();
        // 20240803:切割比例
        $rate   = $this->calSalaryRate();

        $tplId  = Arrays::value($info, 'salary_type_tpl_id');
        
        $typeId         = '5607491131498344449';
        $list = SalaryTypeTplService::getInstance($tplId)->objAttrsList('salaryTypeTplItem');
        foreach($list as $v){
            $userItemId     = SalaryUserItemService::matchIdWithTimeId($info['driver_id'], $v['item_id'],$info['bus_id'], $info['time_id'], $typeId);
            
            $typeItem = SalaryTypeItemService::getByTypeItem($typeId, $v['item_id']);
            $rateSep = Arrays::value($typeItem, 'rate_sep');
            $count = SalaryDriverTangService::timeDriverCount($info['time_id'], $info['driver_id']);
            $salary = $count > 1 && $rateSep 
                    ? $v['default_salary'] * $rate 
                    : $v['default_salary'];
            
            // $salary = $v['default_salary'];
            
            // 20240803：此处应该把工资计算出来
            SalaryUserItemService::getInstance($userItemId)->updateRam(['salary'=>$salary]);
        }
    }
    
}
