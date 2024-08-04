<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 薪资项目
 */
class SalaryItemService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    use \xjryanse\traits\StaticModelTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryItem';

    use \xjryanse\salary\service\item\TriggerTraits;
    use \xjryanse\salary\service\item\FieldTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    /**
     * 
     */
    public static function listsByTypeId($typeId, $con = []){

        $typeItemTable = SalaryTypeItemService::getTable();
        $con[] = ['b.type_id','=',$typeId];
        $lists = self::mainModel()->where($con)->alias('a')
                ->join($typeItemTable.' b','a.id=b.item_id')
                ->field('a.*')
                ->cache(1)
                ->order('b.sort')
                ->select();

        return $lists ? $lists->toArray() : [];
    }

    /**
     * 键值对对象
     * 20231001
     */
    public static function keyIdObj(){
        $lists = self::staticConList();
        return array_column($lists,'id','item_key');
    }
    /**
     * 20231212：发薪项目id
     */
    public static function addItemIds(){
        $con[] = ['type','=',1];
        return self::staticConIds($con);
    }
    
    /**
     * 20231212：扣款项目id
     */
    public static function cutItemIds(){
        $con[] = ['type','=',2];
        return self::staticConIds($con);
    }
    /**
     * 20231228:按薪资类型，提取可编辑的项目id
     */
    public static function salaryTypeEditableIds($salaryTypeId){
        $cone       = [];
        $cone[]     = ['editable','=',1];
        $itemsArr   = self::listsByTypeId($salaryTypeId, $cone);

        $itemIds    = array_column($itemsArr, 'id');
        return $itemIds;
    }
    
}
