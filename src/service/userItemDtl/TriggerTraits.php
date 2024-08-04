<?php

namespace xjryanse\salary\service\userItemDtl;

use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
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
        $keys = ['user_item_id','from_table','from_table_id'];
        DataCheck::must($data, $keys);
        // 更新一些冗余
        self::redunFields($data, $uuid);

    }

    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {
        $info       = self::getInstance($uuid)->get();
        $userItemId = Arrays::value($info,'user_item_id');
        // 父级数据更新
        SalaryUserItemService::getInstance($userItemId)->dataSyncRam();
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
        $info       = self::getInstance($uuid)->get();
        $userItemId = Arrays::value($info,'user_item_id');
        // 父级数据更新
        // dump($info);
        SalaryUserItemService::getInstance($userItemId)->dataSyncRam();
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
        $userItemId = Arrays::value($rawData, 'user_item_id');
        // 父级数据更新
        // Debug::dump('删除'.$this->uuid);
        SalaryUserItemService::getInstance($userItemId)->dataSyncRam();
    }

    protected static function redunFields(&$data, $uuid){
        if(Arrays::value($data, 'user_item_id')){
            $data['time_id'] = SalaryUserItemService::getInstance()->fTimeId();
        }
        $dataRaw = self::getInstance($uuid)->get();
        // 20231121
        self::getInstance($uuid)->setUuData(array_merge($dataRaw,$data), true);
        
        return $data;
    }
}
