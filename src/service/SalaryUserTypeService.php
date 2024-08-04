<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 员工薪资类型（兼职多种薪资）
 */
class SalaryUserTypeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryUserType';

    use \xjryanse\salary\service\userType\TriggerTraits;    
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    /**
     * 
     * @param type $typeId  分类id
     * @param type $timeId  时间
     */
    public static function typeUserIds($typeId, $timeId){
        $con    = [];
        $con[]  = ['type_id','in',$typeId];

        $userIds = self::where($con)->column('distinct user_id');
        return $userIds;
    }
    
}
