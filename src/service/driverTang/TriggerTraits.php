<?php

namespace xjryanse\salary\service\driverTang;

use xjryanse\logic\Debug;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Arrays;
use xjryanse\logic\Datetime;
use xjryanse\logic\DataDeal;
use xjryanse\staff\service\StaffLogService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\salary\service\SalaryTypeTplService;
use xjryanse\salary\service\SalaryTimeTypeService;
use app\cert\service\CertService;

// use xjryanse\salary\service\SalaryUserItemDtlService;
/**
 * 
 */
trait TriggerTraits{
    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }
    
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }
    
    /**
     * 钩子-保存前
     */
    public static function ramPreSave(&$data, $uuid) {
        self::redunFields($data, $uuid);
        // 2020630:复制上个月的字段：
        // salary_type_tpl_id;is_yibao;is_shebao;is_shebao_sy;is_shebao_gs;wash_money
        $timeId     = Arrays::value($data, 'time_id');
        $preTimeId  = SalaryTimeService::getInstance($timeId)->calPreTimeId();
        if($preTimeId){
            $lastCon    = [];
            $lastCon[]  = ['time_id','=',$preTimeId];
            $lastCon[]  = ['driver_id','=',Arrays::value($data, 'driver_id')];
            
            $lInfo = self::where($lastCon)->find();
            $data['salary_type_tpl_id'] = Arrays::value($lInfo, 'salary_type_tpl_id');
            $data['is_yibao']           = Arrays::value($lInfo, 'is_yibao');
            $data['is_shebao']          = Arrays::value($lInfo, 'is_shebao');
            $data['is_shebao_sy']       = Arrays::value($lInfo, 'is_shebao_sy');
            $data['is_shebao_gs']       = Arrays::value($lInfo, 'is_shebao_gs');
            $data['wash_money']         = Arrays::value($lInfo, 'wash_money');
        }
    }

    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {
        self::getInstance($uuid)->toSalaryUserItemDtlSync();
    }

    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        // 20240630
        $defaultData['wash_money']      = 0;
        DataDeal::issetDefault($data, $defaultData);
        
        self::getInstance($uuid)->checkLock();
        // SalaryTimeService::checkLock($time);
        self::redunFields($data, $uuid);        
    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        // 同步至薪资明细表
        self::getInstance($uuid)->toSalaryUserItemDtlSync();
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {
        // 20240610
        $this->checkLock();
        self::redunFields($data, $uuid);
    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete() {
        // 同步至薪资明细表
        $this->toSalaryUserItemDtlSync();
    }
    
    /**
     * 
     */
    protected static function salaryTpl(&$data){
        if(Arrays::value($data, 'salary_type_tpl_id')){
            $info  = SalaryTypeTplService::getInstance($data['salary_type_tpl_id'])->get();
            $data['monthly_salary']         = Arrays::value($info, 'base_salary') ? : 0;
            $data['per_day_tang']           = Arrays::value($info, 'per_day_tang') ? : 0;
            $data['per_tang_salary_tpl']    = Arrays::value($info, 'per_tang_salary') ? : 0 ;
            $data['calc_cate']              = Arrays::value($info, 'calc_cate') ;
        }
        // dump($data);
    }
    
    protected static function redunFields(&$data, $uuid){
        // 20240603
        $info = self::getInstance($uuid)->get();
        $data['salary_type_tpl_id'] = Arrays::value($data, 'salary_type_tpl_id') ? : $info['salary_type_tpl_id']; 

        self::salaryTpl($data);

        $oLists = self::getInstance($uuid)->objAttrsList('driverDailyTang');
        foreach($oLists as &$v){
            $pTang = Arrays::value($v,'plan_tang_count') ? : 0;
            $rTang = Arrays::value($v,'real_tang_count') ? : 0;
            // 偏差趟次
            $v['diffTangCount'] = $pTang - $rTang;
        }
        // Debug::dump($oLists);
        // 正常班
        $con = [['tang_cate','=','n']];
        $data['plan_tang_count'] = Arrays2d::sum(Arrays2d::listFilter($oLists, $con), 'plan_tang_count');
        $data['real_tang_count'] = Arrays2d::sum(Arrays2d::listFilter($oLists, $con), 'real_tang_count');

        $cona = [['tang_cate','=','a']];
        $data['add_tang_count']  = Arrays2d::sum(Arrays2d::listFilter($oLists, $cona), 'real_tang_count');

        $conbu = [['tang_cate','=','bu']];
        $data['bu_tang_count']   = Arrays2d::sum(Arrays2d::listFilter($oLists, $conbu), 'real_tang_count');

        $conDz = [['tang_cate','=','dz']];
        $data['dz_bu_money']     = Arrays2d::sum(Arrays2d::listFilter($oLists, $conDz), 'bu_money');
        // 脱班：病假
        $conBing = [['tang_cate','=','n'],['diffTangCount','>',0],['diff_cate','=','病']];
        $data['tb_bing_count']   = Arrays2d::sum(Arrays2d::listFilter($oLists, $conBing), 'diffTangCount');
        // 脱班：事假
        $conShi = [['tang_cate','=','n'],['diffTangCount','>',0],['diff_cate','=','事']];
        $data['tb_shi_count']    = Arrays2d::sum(Arrays2d::listFilter($oLists, $conShi), 'diffTangCount');
        // 脱班：替趟
        $conTi = [['tang_cate','=','n'],['diffTangCount','>',0],['diff_cate','=','替']];
        $data['tb_ti_count']     = Arrays2d::sum(Arrays2d::listFilter($oLists, $conTi), 'diffTangCount');
        // 脱班：旅游
        $conLv = [['tang_cate','=','n'],['diffTangCount','>',0],['diff_cate','=','旅']];
        $data['tb_lv_count']     = Arrays2d::sum(Arrays2d::listFilter($oLists, $conLv), 'diffTangCount');
        // 脱班：违规
        $conWg = [['tang_cate','=','n'],['diffTangCount','>',0],['diff_cate','=','违']];
        $data['tb_wg_count']     = Arrays2d::sum(Arrays2d::listFilter($oLists, $conWg), 'diffTangCount');
        // 脱班：其他
        $conQt = [['tang_cate','=','n'],['diffTangCount','>',0],['diff_cate','=','其']];
        $data['tb_qt_count']     = Arrays2d::sum(Arrays2d::listFilter($oLists, $conQt), 'diffTangCount');

        // 20240802：出班天数
        $conHasPan = [['tang_cate','=','n'],['plan_tang_count','>',0]];
        $data['real_date_count'] = count(Arrays2d::listFilter($oLists, $conHasPan));
// Debug::dump($oLists);
        // 偏差趟数
        $data['diff_tang_count'] = $data['real_tang_count'] + $data['add_tang_count'] + $data['bu_tang_count'] - $data['plan_tang_count'];
        $perDayTang = isset($data['per_day_tang']) ? $data['per_day_tang'] : Arrays::value($info, 'per_day_tang');
        $data['per_day_tang'] = $perDayTang ? : 0;
        
        $data['diff_day_count'] = $perDayTang > 0 && $data['diff_tang_count'] ? round($data['diff_tang_count']/$perDayTang,2) : null;
        // 20240614:因为个人原因造成的脱班（扣算工资） = 请假趟数 - 加班趟数
        // $data['self_diff_tang_count'] = $data['real_tang_count'] + $data['add_tang_count'] + $data['bu_tang_count'] - $data['plan_tang_count'];
        // 满勤扣单
        $data['self_tb_count'] = $data['tb_bing_count'] + $data['tb_shi_count'] + $data['tb_wg_count'];
        // 扣单天数
        $data['self_tb_days'] = $perDayTang > 0 && $data['self_tb_count'] ? round($data['self_tb_count']/$perDayTang,2) : 0;
        // 计算员工的入职离职情况
        $userId = isset($data['driver_id']) ? $data['driver_id'] : Arrays::value($info, 'driver_id');
        $data['join_date']  = StaffLogService::calUserJoinDate($userId);

        $timeId = isset($data['time_id']) ? $data['time_id'] : Arrays::value($info, 'time_id');
        $startTime = SalaryTimeService::getInstance($timeId)->fStartTime();
        $data['monthly_date_count'] = Datetime::monthlyDateCount(date('Y-m',strtotime($startTime)));
        $data['join_years'] = intval(StaffLogService::calUserYears($userId, $startTime));
        // 20240602
        $monthlySalary      = isset($data['monthly_salary']) ? $data['monthly_salary'] : Arrays::value($info, 'monthly_salary');
        $monthlyDateCount   = isset($data['monthly_date_count']) ? $data['monthly_date_count'] : Arrays::value($info, 'monthly_date_count');
        $planTangCount      = (isset($data['plan_tang_count']) ? $data['plan_tang_count'] : Arrays::value($info, 'plan_tang_count')) ? : 0;
        // 计算
        $data['per_day_salary']     = $monthlySalary && $monthlyDateCount ? round($monthlySalary / $monthlyDateCount, 2) : 0;
        $data['per_tang_salary']    = $monthlySalary && $planTangCount ? round($monthlySalary / $planTangCount, 2) : 0;
        // 20240602:测试
        $data['salary'] = $data['diff_day_count'] ? $data['diff_day_count'] * $data['per_day_salary'] : 0;
        // 20240605:准驾车型
        $data['drive_cert_level'] = CertService::driverCertLevel($userId);
        
        return $data;
    }
    
    protected function checkLock(){
        // gj工资
        $salaryTypeId   = '5607491131498344449';
        //【1】判断总
        $timeId         = $this->fTimeId();
        SalaryTimeService::getInstance($timeId)->checkLockByInst();
        //【2】判断本类型
        $salaryTimeTypeId = SalaryTimeTypeService::timeTypeId($timeId, $salaryTypeId);
        SalaryTimeTypeService::getInstance($salaryTimeTypeId)->checkLockByInst();
    }
    
}
