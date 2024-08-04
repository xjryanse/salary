<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays2d;
use xjryanse\bus\service\BusService;

/**
 * 员工薪资明细
 */
class SalaryUserItemTplService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryUserItemTpl';

//    use \xjryanse\salary\service\userItemTpl\TriggerTraits;
//    use \xjryanse\salary\service\userItemTpl\CalTraits;
//    use \xjryanse\salary\service\userItemTpl\DimTraits;
    use \xjryanse\salary\service\userItemTpl\DoTraits;
//    use \xjryanse\salary\service\userItemTpl\FieldTraits;
    use \xjryanse\salary\service\userItemTpl\PaginateTraits;

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }

    /**
     * 20231127:提取已设置项目的时段，类型
     * @param type $timeId
     * @param type $salaryTypeId
     */
    public static function userIdBusIds($salaryTypeId){
        $con   = [];
        $con[] = ['salary_type_id','in',$salaryTypeId];
        
        $arrObj = self::where($con)
                ->group('user_id,bus_id')
                ->field('user_id,bus_id,concat(user_id,bus_id,'.$salaryTypeId.') as iKey')
                ->select();
        
        $arr = $arrObj ? $arrObj->toArray() : [];
        foreach($arr as &$v){
            $v['iKey'] = implode('_',[$v['user_id'],$v['bus_id'],$salaryTypeId]);
        }

        return $arr;
    }
    
    /**
     * 20231127:提取车辆的司机，用于本表
     * @param type $timeId
     * @param type $salaryTypeId    驾驶员薪资项id
     */
    public static function busDriverIdBusIds($salaryTypeId){
        $con   = [];
        $con[] = ['owner_type','=','self'];
        $con[] = ['status','=',1];
        
        $arrObj = BusService::where($con)
                ->field('current_driver as user_id,id as bus_id')
                ->select();
        
        $arr = $arrObj ? $arrObj->toArray() : [];
        foreach($arr as &$v){
            $v['iKey'] = implode('_',[$v['user_id'],$v['bus_id'],$salaryTypeId]);
        }

        return $arr;
    }
    
    
    public static function listForCopy($salaryTypeId, $itemIds){
        $cone   = [];
        $cone[] = ['salary_type_id','=',$salaryTypeId];
        $cone[] = ['item_id','in',$itemIds];

        $lists      = self::where($cone)->select();
        $listsArr   = $lists ? $lists->toArray() : [];

        $keys = ['user_id','bus_id','salary_type_id','item_id','salary'];
        $saveData   = Arrays2d::getByKeys($listsArr, $keys);
        
        return $saveData;
    }
    
}
