<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;

use xjryanse\logic\Debug;
/**
 * 员工薪资明细详情
 */
class SalaryUserItemDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryUserItemDtl';
    //直接执行后续触发动作
    protected static $directAfter = true;

    use \xjryanse\salary\service\userItemDtl\TriggerTraits;
    use \xjryanse\salary\service\userItemDtl\CalTraits;

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    $cone = [];
                    $cone[] = ['id','in',Arrays2d::uniqueColumn($lists, 'user_item_id')];
                    $itemIdReflect = SalaryUserItemService::where($cone)->column('item_id','id');
                    foreach($lists as &$v){
                        $v['itemId'] = Arrays::value($itemIdReflect, $v['user_item_id']);
                    }
                    return $lists;
                },true);
    }
    
    public static function deleteByFromTableAndFromTableId($fromTable,$fromTableId){
        $con    = [];
        $con[]  = ['from_table','=',$fromTable];
        $con[]  = ['from_table_id','in',$fromTableId];
        // TODO:替换，使用listSetUUData替代
        $lists = self::listWithGlobal($con);
        // [1]提取项目
        $userItemIds = Arrays2d::uniqueColumn($lists, 'user_item_id');
        // $userItemIds = self::where($con)->column('user_item_id');
        // [2]删除
        foreach($lists as $v){
            self::getInstance($v['id'])->deleteRam();
        }
        // $res = self::where($con)->delete();
        // [3]删父表
        $delUserItemIds = self::calDelUserItemIds($userItemIds);
        foreach($delUserItemIds as $dId){
            SalaryUserItemService::getInstance($dId)->deleteRam();
        }

        return $res;
    }
    /**
     * 20231121
     * @param type $userItemId
     * @param type $fromTable
     * @param type $fromTableId
     * @param type $sData
     */
    public static function dataSyncRam($userItemId, $fromTable, $fromTableId, $sData=[]){
        $sDtlData      = [];
        $sDtlData['user_item_id']      = $userItemId;
        $sDtlData['from_table']        = $fromTable;
        $sDtlData['from_table_id']     = $fromTableId;
        $id = self::commGetIdEG($sDtlData);
        
        $salary = floatval(Arrays::value($sData, 'salary'));
        if(!$salary){
            // 20240614:发现医社保设置后不更新，才加了这个update步骤
            $sData['salary'] = 0;
            $res = self::getInstance($id)->updateRam($sData);
            // 没数据，删
            self::getInstance($id)->deleteRam();
        } else {
            // dump($sData);
            // 更新
            $res = self::getInstance($id)->updateRam($sData);
        }
        return $res;
    }
    

}
