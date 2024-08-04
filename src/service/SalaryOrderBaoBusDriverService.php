<?php

namespace xjryanse\salary\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\salary\service\SalaryUserItemDtlService;
use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\salary\service\SalaryTypeService;
use xjryanse\salary\service\SalaryTimeService;
use app\order\service\OrderBaoBusDriverService;
use xjryanse\salary\service\SalaryTimeTypeService;
use xjryanse\salary\service\SalaryOrderBaoBusDriverDtlService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Number;
use xjryanse\logic\Debug;
use think\Db;
use Exception;

/**
 * 员工薪资总表
 */
class SalaryOrderBaoBusDriverService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    // 20231004:批量处理的逻辑函数
    use \xjryanse\traits\MainModelBatchTrait;
    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\salary\\model\\SalaryOrderBaoBusDriver';

    use \xjryanse\salary\service\orderBaoBusDriver\TriggerTraits;
    use \xjryanse\salary\service\orderBaoBusDriver\FieldTraits;
    use \xjryanse\salary\service\orderBaoBusDriver\CalTraits;
    use \xjryanse\salary\service\orderBaoBusDriver\DoTraits;

    /**
     * 
     * @createTime 2023-08-20 11:01:00
     * @param type $ids
     * @return type
     */
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    // 20240510:弃用了，用自定义sql实现
                    // $arr = SalaryOrderBaoBusDriverDtlService::dataArr($ids);
                    // $obj = Arrays2d::fieldSetKey($arr, 'salary_order_bao_bus_driver_id');
                    foreach($lists as &$v){
                        // $v = isset($obj[$v['id']]) ? array_merge($v, $obj[$v['id']]) : $v;
                        $v['grantWithFinMoney'] = round(Number::sum($v['grant_money'],$v['finance_money']),2);
                    }
                    
                    return $lists;
                },true);
    }
    /**
     * 批
     */
    public static function moveBatch($baoBusDriverIds = []) {
        throw new Exception('请联系开发：改用OrderBaoBusDriverService中的方法替代');
    }



    /**
     * 按年月统计
     * @param type $con
     * @return type
     */
    public static function yearmonthStaticsArr($con = []) {
        $sql = "(SELECT
                    company_id,
                    yearmonth ,
                    count(1) as salaryTangCounts,
                    sum(if(yearmonth = date_format( `start_time`, '%Y-%m' ),1,0)) as currentSalaryTangCounts,
                    sum(if(yearmonth = date_format( `start_time`, '%Y-%m' ),0,1)) as supplySalaryTangCounts,
                    sum(distribute_prize) as distributePrize,
                    sum(calculate_prize) as calculatePrize,
                    sum(grant_money) as grantMoney,
                    sum(eat_money) as eatMoney,
                    sum(other_money) as otherMoney,
                    sum(moneyAll) as moneyAll
            FROM
                    w_salary_order_bao_bus_driver 
            GROUP BY
                    company_id,
                    yearmonth) as eee";
        $con[] = ['company_id', '=', session(SESSION_COMPANY_ID)];
        $res = Db::table($sql)->where($con)->select();
        return $res;
    }
    
    /**
     * 新增和更新时，同步薪资明细表
     * @throws Exception
     */
    public function toSalaryUserItemDtlSync(){
        $info = $this->get();

        // 20231001:先提取一下项目
        $obj = SalaryItemService::keyIdObj();

        // 【1】出车抽成：grant_money
        // 【2】餐费：eat_money
        // 【3】其他补贴：other_money
        // 金额写入
        $busId = Arrays::value($info, 'bus_id');
        $reflects = [
            'tangEat'       =>'eat_money',
            'tangGrant'     =>'grant_money',
            'tangOther'     =>'other_money',
            'tangFinance'   =>'finance_money',
        ];
        
        foreach($obj as $k=>$v){
            if($k && Arrays::value($reflects, $k)){
                // 餐
                self::toSalaryUserItemDtl($this->uuid, $v, $busId, $info[$reflects[$k]], $info);
            }
        }

        return true;
    }
    
    protected static function toSalaryUserItemDtl($id, $itemId, $busId,$salary, $data = []){
        
        $fromTable      = self::getTable();
        $fromTableId    = $id;

        $sData          = [];
        $sData['salary']        = $salary;
        // 提取userItemId
        $userItemId     = SalaryUserItemService::matchIdWithTimeId($data['driver_id'], $itemId,$busId, $data['time_id']);
        
        $res            = SalaryUserItemDtlService::dataSyncRam($userItemId, $fromTable, $fromTableId, $sData);
        
        return $res;
    }
    /**
     * 20231121
     */
    public static function dataSyncRamByBaoBusDriverId($baoBusDriverId){
        $baoBusDriverInfo = OrderBaoBusDriverService::getInstance($baoBusDriverId)->get();
        // 20231124：
        $startTime = OrderBaoBusDriverService::getInstance($baoBusDriverId)->calStartTime();
        $timeId = SalaryTimeService::getTimeIdIfLockNextMonth($startTime);
        // 20231124:清理非当前时段的未锁数据
        self::deleteByBaoBusDriverIdExceptSomeTimeId($baoBusDriverId, $timeId);
        // 没有司机，不用写，
        // 司机是外调的，不用写（ && Arrays::value($baoBusDriverInfo,'driver_type') == 1）(20231202改要写)
        $needWrite = $baoBusDriverInfo 
                && Arrays::value($baoBusDriverInfo,'driver_id');
        
        $con    = [];
        $con[]  = ['bao_bus_driver_id','=',$baoBusDriverId];
        // 20231124:增加timeId
        $con[]  = ['time_id','=',$timeId];
        $id     = self::where($con)->value('id');
        if(!$needWrite){
            if(!$id){
                // 不用写，且原来没有
                return false;
            } else {
                // 原来有，删
                return self::getInstance($id)->deleteRam();
            }
        }
        // 需要写，原来没有新增，原来有的更新，
        $tmp = [];
        $tmp['bao_bus_driver_id']   = $baoBusDriverId;
        $tmp['time_id']             = $timeId;
        if(!$id){
            // [新增]
            $newId          = self::mainModel()->newId();
            $tmp['id']      = $newId;
            self::saveRam($tmp);
            // dump($resp);
            // exit;
            //更新本表
            $data                   = [];
            $data['salary_item_id'] = $newId;
            return OrderBaoBusDriverService::getInstance($baoBusDriverId)->doUpdateRam($data);
        } else {
            // 20231124：多退少补
            return self::getInstance($id)->updateRam($tmp);
        }
    }

    /**
     * 趟司机维度，判断是否被锁
     * @param type $baoBusDriverId
     * @throws Exception
     */
    public static function isBaoBusDriverIdLocked($baoBusDriverId){
        $startTime  = OrderBaoBusDriverService::getInstance($baoBusDriverId)->calStartTime();
        $timeId     = SalaryTimeService::getTimeId($startTime);
        // 费用类型：
        $typeId     = SalaryTypeService::keyToId('driver');
        $isLock = SalaryTimeTypeService::calIsLockByTimeIdAndTypeId($timeId, $typeId);

        return $isLock;
    }
    /**
     * 20231123:包车趟次删除
     * @return type
     */
    public static function deleteByBaoBusDriverId($baoBusDriverId){
        $con    = [['bao_bus_driver_id','=',$baoBusDriverId]];
        $ids     = self::where($con)->column('id');
        foreach($ids as $id){
            // 原来有，删
            self::getInstance($id)->deleteRam();
        }
        return true;
    }
    
    /**
     * 20231123:包车趟次删除(仅删未锁)
     * @return type
     */
    public static function deleteByBaoBusDriverIdExceptSomeTimeId($baoBusDriverId, $exceptTimeIds){
        $timeTable = SalaryTimeService::getTable();

        $con    = [];
        $con[]  = ['a.bao_bus_driver_id','=',$baoBusDriverId];
        // 仅提取未锁班次进行删除
        $con[]  = ['b.time_lock','=',0];
        $con[]  = ['a.time_id','not in', $exceptTimeIds];

        $ids   = self::mainModel()->alias('a')
                ->join($timeTable. ' b','a.time_id=b.id')
                ->where($con)->column('a.id');        
        foreach($ids as $id){
            // 原来有，删
            self::getInstance($id)->deleteRam();
        }
        return true;
    }
    
    /**
     * 提取哪些司机
     * @param type $timeId
     * @param type $typeId
     * @return type
     */
    public static function driverIdBusIdsForSalary($timeId, $typeId){
        $con = [];
        $con[] = ['time_id','in',$timeId];
        
        $arrObj = self::where($con)
                ->group('time_id,driver_id,bus_id')
                ->field('time_id,driver_id,bus_id,count(1) as itemCount')
                ->select();
        $arr = $arrObj ? $arrObj->toArray() : [];
        foreach($arr as &$v){
            $v['iKey'] = implode('_',[$v['time_id'],$v['driver_id'],$v['bus_id'],$typeId]);
        }

        return Arrays2d::keyReplace($arr, ['driver_id'=>'user_id'],true);
    }
    /**
     * 发薪时段提取用户id
     */
    public static function timeIdToUserIds($timeId){
        $con[] = ['time_id','=',$timeId];
        $driverIds = self::where($con)->cache(1)->column('distinct driver_id');
        return $driverIds;
    }

    /**
     * 20240110：列表写入明细
     * prize_cate、prize_key、thisPrize
     */
    public function listToDtl($lists){
        //先删，再写
        SalaryOrderBaoBusDriverDtlService::clearBySalaryOrderBaoBusDriverId($this->uuid);
        if(!$lists){
            return [];
        }
        $arr = [];
        foreach($lists as $v){
            // Debug::dump($v);
            if(!intval($v['thisPrize'])){
                continue;
            }
            $arr[] = [
                'salary_order_bao_bus_driver_id' => $this->uuid,
                'field'         => $v['prize_key'],
                'field_money'   => $v['thisPrize'],
            ];
        }
        if($arr){
            SalaryOrderBaoBusDriverDtlService::saveAllRam($arr);
        }
    }
    
}
