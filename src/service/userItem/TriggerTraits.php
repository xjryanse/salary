<?php

namespace xjryanse\salary\service\userItem;

use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Debug;
use xjryanse\logic\DataCheck;
use xjryanse\logic\DataDeal;
use xjryanse\salary\service\SalaryUserService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\salary\service\SalaryTypeItemService;
use xjryanse\prize\service\PrizeRuleService;
/**
 * 
 */
trait TriggerTraits{
    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }
    
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }
    
    /**
     * 钩子-保存前
     */
    public static function ramPreSave(&$data, $uuid) {
        $data = self::redunFields($data, $uuid);
        // 20231128:校验必填
        $keys = ['salary_user_id'];
        DataCheck::must($data, $keys);
    }

    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {
        // 20240604
        self::getInstance($uuid)->doCalMustItems();
        
        $info           = self::getInstance($uuid)->get();
        $salaryUserId   = Arrays::value($info,'salary_user_id');
        // 父级数据更新
        SalaryUserService::getInstance($salaryUserId)->dataSync();
    }

    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        $data = self::redunFields($data, $uuid);
        // 20240618:解决报错
        // SQLSTATE[HY000]: General error: 1366 Incorrect decimal value: '' for column 'salary' at row 1
        $defaultData['salary']     = 0;
        DataDeal::issetDefault($data, $defaultData);
    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        // 20240604
        self::getInstance($uuid)->doCalMustItems();
        
        $info           = self::getInstance($uuid)->get();
        $salaryUserId   = Arrays::value($info,'salary_user_id');
        // 父级数据更新
        SalaryUserService::getInstance($salaryUserId)->dataSync();
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {

    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete($rawData) {

        $this->doCalMustItems();

        $salaryUserId   = Arrays::value($rawData, 'salary_user_id');
        // 父级数据更新
        SalaryUserService::getInstance($salaryUserId)->dataSync();
    }
    
    
    /**
     * 冗余字段
     * @param type $data
     */
    protected static function redunFields(&$data, $uuid){
        //根据recordId,提取studentId
        if(isset($data['salary_user_id'])){
            $salaryUserId           = $data['salary_user_id'];
            $data['time_id']        = SalaryUserService::getInstance($salaryUserId)->fTimeId();
            $data['user_id']        = SalaryUserService::getInstance($salaryUserId)->fUserId();
            $data['salary_type_id'] = SalaryUserService::getInstance($salaryUserId)->fSalaryTypeId();
        }
        if(isset($data['item_id'])){
            $itemId                 = $data['item_id'];
            $data['need_dtl']       = SalaryItemService::getInstance($itemId)->fNeedDtl();
        }
        // 20240602
        $oLists = self::getInstance($uuid)->objAttrsList('salaryUserItemDtl');
        if($oLists){
            $data['salary'] = Arrays2d::sum($oLists, 'salary');
        }
        // Debug::dump($data);
        return $data;
    }
    
    /**
     * 20240604:当有新项目时，更新一下总的必填
     */
    public function doCalMustItems(){
        $info           = $this->get();
        // 20240604:项目id
        $itemId         = Arrays::value($info, 'item_id');
        $salaryUserId   = Arrays::value($info, 'salary_user_id');
        $busId          = Arrays::value($info, 'bus_id');

        $userId         = SalaryUserService::getInstance($salaryUserId)->fUserId();
        $timeId         = SalaryUserService::getInstance($salaryUserId)->fTimeId();
        $salaryTypeId   = SalaryUserService::getInstance($salaryUserId)->fSalaryTypeId();
        $startTime      = SalaryTimeService::getInstance($timeId)->fStartTime();

        $con        = [];
        $con[]      = ['type_id','=',$salaryTypeId];
        $con[]      = ['is_calc','=',1];
        // Debug::dump(SalaryTypeItemService::staticConList());
        $calItems      = SalaryTypeItemService::staticConList($con,'','sort');
        $calItemIds    = Arrays2d::uniqueColumn($calItems, 'item_id');
        // 如果是必有项目，则不处理，
        if(in_array($itemId, $calItemIds)){
            return true;
        }
        $iList          = SalaryUserService::getInstance($salaryUserId)->objAttrsList('salaryUserItem');
        
        $preFix = 'sum_';
        $sumData = self::calSummaryByItem($iList, $preFix);
        // Debug::dump($sumData);
        // $sumArray = Arrays2d::co($iList, Arrays2d::uniqueColumn($iList, 'item_id'));
        // Debug::dump($iList);
        // Debug::dump($sumArray);
        if($busId){
            $conBus     = [['bus_id','=',$busId]];
            $iList      = Arrays2d::listFilter($iList, $conBus);
        }
        
        
        foreach($iList as &$v){
            $v['itemKey'] = SalaryItemService::getInstance($v['item_id'])->fItemKey();
        }

        // 20240604
        // 项目转成键值对：工资项目id=>本项工资
        $iObjR          = array_column($iList, 'salary','itemKey');
        $iObj           = array_merge($iObjR, $sumData);
        foreach($iObjR as $k=>$v){
            $sum = Arrays::value($sumData, 'sum_'.$k) ? : 0;
            // 计算占比
            $iObj['rate_'.$k] = $sum ? Arrays::value($iObjR, $k) / $sum : 1;
        }
// Debug::dump($iObj);
        // 根据
        foreach($calItems as $mItem){
            
            $userItemId         = self::matchIdWithTimeId($userId, $mItem['item_id'], $info['bus_id'], $timeId, $salaryTypeId);
            $prizeGroupKey      = SalaryItemService::getInstance($mItem['item_id'])->fPrizeGroupKey();
            $itemKey            = SalaryItemService::getInstance($mItem['item_id'])->fItemKey();

            $pRes               = self::salaryWithPrizeGroupKey($startTime, $iObj, $prizeGroupKey);

//            if($mItem['remark'] == '个税'){
//                Debug::dump($prizeGroupKey);
//                Debug::dump($iObj);
//                Debug::dump($pRes);
//            }

            $sData                  = [];
            $sData['salary']        = Arrays::value($pRes, 'prize') ? : 0;
            $sData['formula_str']   = Arrays::value($pRes, 'formula');
            self::getInstance($userItemId)->updateRam($sData);
            // 用于后续的计算
            $iObj[$itemKey]     = $sData['salary'];
            // $userItemId     = SalaryUserItemService::matchIdWithTimeId($data['driver_id'], $itemId,$busId, $data['time_id'], $typeId);
        }

    }
    
    
    
    /**
     * 
     */
    protected static function salaryWithPrizeGroupKey($startTime, $data, $prizeGroupKey){
        $groupCate = 'salary';
        $con = [['group_key','=',$prizeGroupKey]];
        $res = PrizeRuleService::getPrizeWithFormula($startTime, $groupCate, $data, $con);
        
        return $res;
    }
    
}
