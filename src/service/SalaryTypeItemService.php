<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\system\service\SystemChangeHistoryService;
use xjryanse\logic\Arrays2d;

/**
 * 薪资类型项目
 */
class SalaryTypeItemService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\StaticModelTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryTypeItem';

    use \xjryanse\salary\service\typeItem\TriggerTraits;
    use \xjryanse\salary\service\typeItem\DimTraits;
    use \xjryanse\salary\service\typeItem\ListTraits;
    
    /**
     * 基本工资的工资项目
     * @param type $typeId
     */
    public static function baseItemIds($typeId){
        $con[] = ['type_id','in',$typeId];
        $con[] = ['is_base','=',1];
        $arr = self::staticConList($con);
        return Arrays2d::uniqueColumn($arr, 'item_id');
    }
    /**
     * 20240803
     * @param type $typeId
     * @param type $itemId
     * @return type
     */
    public static function getByTypeItem($typeId, $itemId){
        $con[] = ['type_id','in',$typeId];
        $con[] = ['item_id','=',$itemId];
        return self::staticConFind($con);
    }
    
}
