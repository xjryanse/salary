<?php

namespace xjryanse\salary\service\user;

use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\logic\DataCheck;
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
        $keys = ['time_id','user_id','salary_type_id'];
        DataCheck::must($data, $keys);
        
        self::redunFields($data, $uuid);
    }

    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {
        
    }

    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        self::redunFields($data, $uuid);
    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {

    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete() {
        
    }
    

    protected static function redunFields(&$data, $uuid){
        // 工资已发，或时间已锁，不改
        $data['salary_item_total']  = SalaryUserItemService::calSalaryItemTotalBySalaryUserId($uuid);
        $data['salary_item_cut']    = SalaryUserItemService::calSalaryItemCutBySalaryUserId($uuid);
        
        $data['salary_total']       = SalaryUserItemService::calSalaryTotalBySalaryUserId($uuid);
        $data['salary_cut']         = SalaryUserItemService::calSalaryCutBySalaryUserId($uuid);
        $data['salary_real']        = SalaryUserItemService::calSalaryRealBySalaryUserId($uuid);

        return $data;
    }
}
