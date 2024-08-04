<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use Exception;
use xjryanse\logic\Datetime;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\salary\service\SalaryUserService;
use xjryanse\salary\service\SalaryOrderBaoBusDriverService;
use xjryanse\user\service\UserAuthUserRoleService;


/**
 * 薪资时间
 * 理解为发薪批次
 */
class SalaryTimeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryTime';
    
    use \xjryanse\salary\service\time\TriggerTraits;    
    use \xjryanse\salary\service\time\ListTraits;
    use \xjryanse\salary\service\time\DimTraits;
    use \xjryanse\salary\service\time\DoTraits;
    use \xjryanse\salary\service\time\FieldTraits;
    use \xjryanse\salary\service\time\CalTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    foreach($lists as &$v){
                        // 应计薪人数：
                        $v['needSalaryCount'] = 999;
                        // 已计薪人数
                        $v['hasSalaryCount']  = 888;
                        // 待计薪人数
                        $v['noSalaryCount']   = 777;
                        // 薪资金额
                        $v['salaryMoney']     = 666;
                        // 已发薪人数
                        $v['outUserCount']    = 89;
                        // 已计待发人数
                        $v['noOutUserCount']  = 90;
                        // 应发已发资金额
                        $v['outMoney']        = 895;
                        // 应发待发资金额
                        $v['noOutMoney']      = 455;
                    }

                    return $lists;
                },true);
    }
    
    /**
     * 20231002:时间取id
     * @param type $time
     */
    public static function getTimeId($time){
        if(!$time){
            return '';
        }
        $con = [];
        $con[] = ['start_time','<=',$time];
        $con[] = ['end_time','>=',$time];

        $id = self::where($con)->value('id');
        if(!$id){
            // 20231123:直接处理了，不报错。
            $id = self::initTimeGetId($time);
            // throw new Exception('没有匹配的计薪时段，请先初始化'.$time);
        }
        return $id;
    }
    /**
     * 20231124：提取计薪时段id，如果已锁定，取下一个月份
     */
    public static function getTimeIdIfLockNextMonth($time){
        $id         = self::getTimeId($time);
        $isLocked   = self::getInstance($id)->fTimeLock();
        if(!$isLocked){
            return $id;
        }
        // 如果锁定，取下一个月份
        $nextMonthDay   = date('Y-m-01', strtotime($time . ' +1 month'));
        $nId             = self::getTimeId($nextMonthDay);
        if(self::getInstance($nId)->fTimeLock()){
            // 如果下一个月份也锁定了
            $lastName = self::getInstance($id)->fName();
            $timeName = self::getInstance($nId)->fName();
            throw new Exception('计薪时段“'.$lastName.'”和“'.$timeName.'”均已锁定，不可操作');
        }

        return $nId;
    }
    /**
     * 20231221：月份提取time_id
     */
    public static function yearmonthToTimeIds($yearmonth){
        // 20231221:只看开始时间
        $con = Datetime::yearMonthTimeCon('start_time',$yearmonth);
        return self::where($con)->column('id');
    }
    
    
    protected static function initTimeGetId($time){
        $data               = [];
        $data['name']       = date('Y年m月',strtotime($time));
        $data['start_time'] = Datetime::monthStartTime($time);
        $data['end_time']   = Datetime::monthEndTime($time);
        return self::saveGetIdRam($data);
    }
    
    
    /**
     * 校验时间是否被锁
     * @param type $time
     * @throws Exception
     */
    public static function checkLock($time) {
        $lockTimeId = self::isTimeLock($time);
        if ($lockTimeId) {
            $info = FinanceTimeService::getInstance($lockTimeId)->get();
            $lockUserId = $info['lock_user_id'];
            $userInfo = UserService::getInstance($lockUserId)->get();
            $namePhone = Arrays::value($userInfo, 'namePhone');
            throw new Exception('账期' . $info['belong_time'] . '已被"' . $namePhone . '"锁定，请联系财务');
        }
    }
    /**
     * 20240610
     * @throws Exception
     */
    public function checkLockByInst(){
        $info       = $this->get();
        $timeLock   = $this->fTimeLock();
        $lockUserId = $this->fLockUserId();
        if($timeLock){
            $userInfo   = UserService::getInstance($lockUserId)->get();
            $namePhone  = Arrays::value($userInfo, 'namePhone');
            throw new Exception('工资月' . $info['name'] . '已被"' . $namePhone . '"锁定');
        }
    }
    
    /**
     * 获取锁定时间段
     * @param type $cacheUpdate     
     * @return type
     */
    protected static function getLockTimesArrCache() {
        $cacheKey = __CLASS__ . 'getLockTimesArr';
        return Cachex::funcGet($cacheKey, function() {
                    return self::lockTimeArrDb();
                }, true);
    }
    
    /**
     * 20220617;从数据库获取
     * @return type
     */
    protected static function lockTimeArrDb() {
        $con[] = ['time_lock', '=', 1];
        $lists = self::lists($con);
        return $lists ? $lists->toArray() : [];
    }
    
    
    /**
     * 传入一个时间，获取被锁定的账期名
     */
    public static function isTimeLock($time) {
        $lockArr = self::getLockTimesArrCache();
        foreach ($lockArr as &$v) {
            if ($v['from_time'] <= $time && $v['to_time'] >= $time) {
                return $v['id'];
            }
        }
        return false;
    }
    /**
     * 20231202:统计数据列表
     */
    protected static function salaryStaticsList($timeIds, $salaryTypeIds){
        if(!$salaryTypeIds || !$timeIds){
            return [];
        }
        if(is_string($timeIds)){
            $timeIds = [$timeIds];
        }
        if(is_string($salaryTypeIds)){
            $salaryTypeIds = [$salaryTypeIds];
        }
        
        // 20231201
        $arrList = SalaryUserService::salaryTypeTimeGroupList();
        // time_id + salary_type_id做key
        $salaryTimeTypeObj = Arrays2d::fieldSetKey($arrList, 'KEY');
        
        
        $arr = [];
        foreach($timeIds as $timeId){
            foreach($salaryTypeIds as $salaryTypeId){
                $timeTypeId = SalaryTimeTypeService::timeTypeId($timeId, $salaryTypeId);

                $tmp = [];
                $tmp['id']      = $timeTypeId;
                $tmp['time_id'] = $timeId;
                $tmp['type_id'] = $salaryTypeId;
                $tmp['time_lock'] = SalaryTimeTypeService::getInstance($timeTypeId)->fTimeLock();
                
                // 20231202
                $key = $timeId.'_'.$salaryTypeId;

                // 已计薪笔数
                $tmp['hasSalaryCount']      = isset($salaryTimeTypeObj[$key]) ? Arrays::value($salaryTimeTypeObj[$key], 'salaryCount') : 0;
                // 应计薪人数
                $tmp['needSalaryUserCount'] = 999;
                // 已计薪人数
                $tmp['hasSalaryUserCount']  = isset($salaryTimeTypeObj[$key]) ? Arrays::value($salaryTimeTypeObj[$key], 'userCount') : 0;
                // 待计薪人数
                $tmp['noSalaryUserCount']   = 777;
                // 薪资金额应发合计
                $tmp['salaryTotalMoney']         = isset($salaryTimeTypeObj[$key]) ? Arrays::value($salaryTimeTypeObj[$key], 'salary_total') : 0;
                // 实发合计
                $tmp['salaryMoney']         = isset($salaryTimeTypeObj[$key]) ? Arrays::value($salaryTimeTypeObj[$key], 'salary_real') : 0;

                // 已发薪人数
                $tmp['outUserCount']    = 89;
                // 已计待发人数
                $tmp['noOutUserCount']  = 90;
                // 应发已发资金额
                $tmp['outMoney']        = 895;
                // 应发待发资金额
                $tmp['noOutMoney']      = 455;
                
                $arr[] = $tmp;
            }
        }
        return $arr;
    }
    /**
     * 20231228:提取本月可能需要发工资的用户id数组
     * 逻辑是全部后台用户 + 本月有出车的外调司机
     * 一般用于导入时进行姓名匹配
     */
    public static function perhapsUserIds($timeId){
        $staffUserIds   = UserAuthUserRoleService::staffUserIds();
        $driverIds      = SalaryOrderBaoBusDriverService::timeIdToUserIds($timeId);
        return array_unique(array_merge($staffUserIds, $driverIds));
    }
    
    
}
