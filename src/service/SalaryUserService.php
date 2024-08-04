<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Debug;
use xjryanse\logic\Arrays;
use think\Db;
/**
 * 员工薪资总表
 */
class SalaryUserService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryUser';

    use \xjryanse\salary\service\user\TriggerTraits;
    use \xjryanse\salary\service\user\PaginateTraits;
    use \xjryanse\salary\service\user\FieldTraits;
    use \xjryanse\salary\service\user\DoTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {

                    $salarys = SalaryUserItemService::groupBatchSum('salary_user_id', $ids, 'salary');

                    foreach($lists as &$v){
                        // 按明细项的统计，方便比对程序异常
                        $v['salaryUserItemSum'] = Arrays::value($salarys, $v['id']);
                        // 开发核对是否准确
                        $sReal  = Arrays::value($v, 'salary_real') ? : 0;
                        $sum    = Arrays::value($v, 'salaryUserItemSum') ? : 0;
                        $v['devSalaryDiff'] = $sReal - $sum;
                    }
                    return $lists;
                },true);
    }
    /**
     * 20230929:明细数据同步逻辑
     */
    public function dataSync(){
        // 判断明细，无明细的时候，删了
        $key = 'salaryUserItem';
        $lists = $this->objAttrsList($key);
        if(!$lists){
            return $this->deleteRam();
        } else {
            $data['status'] = 1;
            return $this->updateRam($data);
        }
    }


    /**
     * 用户id，薪资id，发薪批次id，匹配一个记录id，没有时新增
     * @param type $userId
     * @param type $typeId
     * @param type $timeId
     * @return type
     */
    public static function matchId($userId, $typeId, $timeId){
        $sData                      = [];
        $sData['user_id']           = $userId;
        $sData['salary_type_id']    = $typeId;
        $sData['time_id']           = $timeId;
        return self::commGetIdEG($sData);
    }
    /**
     * 类型+时段聚合查询
     * 2023-12-01
     */
    public static function salaryTypeTimeGroupList($con = []){

        // $lists = self::mainModel()->where($con)->group('');
        $fields     = [];
        $fields[]   = 'count(1) as salaryCount';
        $fields[]   = 'count(distinct user_id) as userCount';
        $fields[]   = 'sum(salary_total) as salary_total';
        $fields[]   = 'sum(salary_cut) as salary_cut';
        $fields[]   = 'sum(salary_real) as salary_real';
        
        $groups     = [];
        $groups[]   = 'time_id';
        $groups[]   = 'salary_type_id';
        
        $sql = self::mainModel()->sqlGroupDown($con,$fields,$groups);
        // Debug::dump($sql);
        $lists = Db::query($sql);
        foreach($lists as &$v){
            // 可作唯一识别
            $v['KEY'] = $v['time_id'].'_'.$v['salary_type_id'];
        }

        return $lists;
    }
    

}
