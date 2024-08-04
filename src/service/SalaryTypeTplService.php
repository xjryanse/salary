<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 薪资类型项目
 */
class SalaryTypeTplService extends Base implements MainModelInterface {

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
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryTypeTpl';

    use \xjryanse\salary\service\typeTpl\TriggerTraits;
//    use \xjryanse\salary\service\typeItem\DimTraits;
//    use \xjryanse\salary\service\typeItem\ListTraits;
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
}
