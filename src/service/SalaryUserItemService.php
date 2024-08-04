<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\salary\service\SalaryUserService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;

/**
 * 员工薪资明细
 */
class SalaryUserItemService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryUserItem';

    use \xjryanse\salary\service\userItem\TriggerTraits;
    use \xjryanse\salary\service\userItem\CalTraits;
    use \xjryanse\salary\service\userItem\DimTraits;
    use \xjryanse\salary\service\userItem\DoTraits;
    use \xjryanse\salary\service\userItem\FieldTraits;
    use \xjryanse\salary\service\userItem\PaginateTraits;

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    // 20240613:明细求和
                    $salarys = SalaryUserItemDtlService::groupBatchSum('user_item_id', $ids, 'salary');
                    
                    foreach($lists as &$v){
                        $v['dtlSalarySum'] = Arrays::value($salarys, $v['id']) ? : 0;
                        // 差值：用于开发核查数据准确性
                        $v['devDiffSalary'] = $v['salary'] - $v['dtlSalarySum'];
                    }
            
                    return $lists;
                },true);
    }
    
    /**
     * 根据薪资模板，把用户薪资写入
     */
    public static function userTimeItemsInit($salaryUserId){
        $info = SalaryUserService::getInstance($salaryUserId)->get();
        $typeId = Arrays::value($info, 'salary_type_id');
        
        // 提取薪资模板
        $lists = SalaryTypeItemService::dimListByTypeId($typeId);

        $arr = [];
        foreach($lists as $v){
            $tmp = [];
            $tmp['salary_user_id']  = $salaryUserId;
            $tmp['item_id']         = $v['item_id'];
            $tmp['salary']          = $v['default_salary'];
            $arr[] = $tmp;
        }
        return self::saveAllRam($arr);
    }
    
    /**
     * 用户id
     * @param type $typeId
     */
    public static function matchId($userId, $itemId, $busId, $time){
        // time 提取 time_id
        $timeId         = SalaryTimeService::getTimeId($time);
        $typeId         = '5370933273363046400';
        $salaryUserId   = SalaryUserService::matchId($userId, $typeId, $timeId);

        return self::doMatchId($salaryUserId, $itemId, $busId);
    }
    
    /**
     * 用户id
     * @param type $typeId
     */
    public static function matchIdWithTimeId($userId, $itemId, $busId, $timeId, $typeId = '5370933273363046400'){
        // time 提取 time_id
        // $typeId         = '5370933273363046400';
        $salaryUserId   = SalaryUserService::matchId($userId, $typeId, $timeId);

        return self::doMatchId($salaryUserId, $itemId, $busId);
    }
    
    
    /**
     * 用户id，薪资id，发薪批次id，匹配一个记录id，没有时新增
     * @param type $salaryUserId
     * @param type $itemId
     * @param type $busId
     * @return type
     */
    public static function doMatchId($salaryUserId, $itemId , $busId){
        $sData      = [];
        $sData['salary_user_id']    = $salaryUserId;
        $sData['item_id']    = $itemId;
        $sData['bus_id']    = $busId;
        return self::commGetIdEG($sData);
    }
    
    /**
     * 从子项表中同步数据
     * 废弃，使用下述方法替代
     * @return bool
     */
    public function dataSync(){
        $info       = $this->get();
        // $itemId     = Arrays::value($info, 'item_id');
        //【1】判断是否需要明细，不需要明细不处理
        $needDtl    = Arrays::value($info, 'need_dtl');
        // 不需要明细时，不处理
        if(!$needDtl){
            return false;
        }
        //【2】判断是否有明细，无明细删
        $key    = 'salaryUserItemDtl';
        $lists  = $this->objAttrsList($key);
        if(!$lists){
            // 没有明细了，删
            return $this->deleteRam();
        }
        //【3】根据明细更新金额
        $data['salary']    = SalaryUserItemDtlService::calSalaryByUserItemId($this->uuid);
        return $this->updateRam($data);
    }
    
    /**
     * 20240602：
     */
    public function dataSyncRam(){
        $this->updateRam(['status'=>1]);
        return true;
    }
    
    /**
     * 20231127:提取已设置项目的时段，类型
     * @param type $timeId
     * @param type $salaryTypeId
     */
    public static function userIdBusIds($timeId, $salaryTypeId){
        $con = [];
        $con[] = ['time_id','in',$timeId];
        $con[] = ['salary_type_id','in',$salaryTypeId];
        
        $arrObj = self::where($con)
                ->group('time_id,user_id,bus_id')
                ->field('time_id,user_id,bus_id,concat(time_id,user_id,bus_id,'.$salaryTypeId.') as iKey')
                ->select();
        
        $arr = $arrObj ? $arrObj->toArray() : [];
        foreach($arr as &$v){
            $v['iKey'] = implode('_',[$v['time_id'],$v['user_id'],$v['bus_id'],$salaryTypeId]);
        }

        return $arr;
    }

    /**
     * 20231228:按时间，用户，车辆，类型，项目更新
     * @param type $timeId
     * @param type $userId
     * @param type $busId
     * @param type $typeId
     * @param type $itemId
     * @param type $updData
     * @return type
     */
    protected static function updateByTimeUserBusTypeItem($timeId, $userId, $busId, $typeId, $itemId, $updData = []){
        // 20231226
        $salaryUserId   = SalaryUserService::matchId($userId, $typeId, $timeId);
        $id             = self::doMatchId($salaryUserId, $itemId, $busId);
        // 20231124:key加前缀s
        $info           = self::getInstance($id)->get();
        if(!$info['need_dtl'] && !floatval($updData['salary'])){
            // 0，删了
            return self::getInstance($id)->deleteRam();
        } else {
            // 更新
            // return self::getInstance($id)->updateRam(['salary'=>$value]);
            return self::getInstance($id)->updateRam($updData);
        }
    }
    
}
