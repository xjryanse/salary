<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Arrays;
use Exception;
/**
 * 薪资时间
 * 理解为发薪批次
 */
class SalaryTimeTypeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryTimeType';
    
    use \xjryanse\salary\service\timeType\TriggerTraits;    
    // use \xjryanse\salary\service\time\ListTraits;
    // use \xjryanse\salary\service\time\DimTraits;
    use \xjryanse\salary\service\timeType\DoTraits;
    use \xjryanse\salary\service\timeType\FieldTraits;
    use \xjryanse\salary\service\timeType\CalTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                },true);
    }
    
    /**
     * 20231226：提取
     * @param type $timeId
     * @param type $typeId
     */
    public static function timeTypeId($timeId, $typeId){
        $con[] = ['time_id','=',$timeId];
        $con[] = ['type_id','=',$typeId];
        $id = self::where($con)->value('id');
        if(!$id){
            $data['time_id'] = $timeId;
            $data['type_id'] = $typeId;
            $id = self::saveGetIdRam($data);
            DbOperate::dealGlobal();
        }
        return $id;
    }
    
    public static function findForSalary($param){
        $id     = Arrays::value($param, 'id');
        
        $info   = self::getInstance($id)->get();
        
        $res    = Arrays::getByKeys($info,['id','time_id','time_lock','status']);
        $res['salary_type_id']  = $info['type_id'];
        
        return $res;
    }
    
    
    /**
     * 20240610
     * @throws Exception
     */
    public function checkLockByInst(){
        $info       = $this->get();
        if(Arrays::value($info, 'time_lock')){
            // $userInfo   = UserService::getInstance($lockUserId)->get();
            // $namePhone  = Arrays::value($userInfo, 'namePhone');
            throw new Exception('工资月已锁定');
            // throw new Exception('工资月' . $info['name'] . '已被"' . $namePhone . '"锁定');
        }
    }
}
