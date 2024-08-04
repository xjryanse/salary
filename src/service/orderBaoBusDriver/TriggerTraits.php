<?php
namespace xjryanse\salary\service\orderBaoBusDriver;

use app\order\service\OrderBaoBusDriverService;
use app\order\service\OrderBaoBusService;
use xjryanse\order\service\OrderService;
use xjryanse\bus\service\BusTypeService;
use xjryanse\salary\service\SalaryUserItemDtlService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\logic\DataCheck;
use xjryanse\salary\service\SalaryOrderBaoBusDriverDtlService;
// use xjryanse\logic\DbOperate;
use xjryanse\logic\Arrays;
use xjryanse\logic\Debug;
use xjryanse\logic\Number;

use Exception;
/**
 * 分页复用列表
 */
trait TriggerTraits{
    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-更新前
     */
    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-删除前
     */
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }

    
    
    /**
     * 钩子-保存前
     */
    public static function ramPreSave(&$data, $uuid) {

        
        // 更新一些冗余
        self::redunFields($data, $uuid);

        $keys = ['driver_id'];
        $notice['driver_id'] = '订单没有安排司机';
        // $keys = [];
        DataCheck::must($data, $keys, $notice);
        
        if(SalaryTimeService::getInstance($data['time_id'])->fTimeLock()){
            throw new Exception('当月工资已锁定，不可增加');
        }
    }

    /**
     * 钩子-保存前
     */
    public static function ramPreUpdate(&$data, $uuid) {
        // 20240503
        self::queryCountCheck(__METHOD__);
//        $info = self::getInstance($uuid)->get();
//        $data['bao_bus_driver_id'] = $info['bao_bus_driver_id'];
        // 更新一些冗余
        self::redunFields($data, $uuid);

        if(SalaryTimeService::getInstance($data['time_id'])->fTimeLock()){
            throw new Exception('当月工资已锁定不可修改');
        }
    }

    /**
     * 钩子-删除前
     * 
     */
    public function ramPreDelete() {
        // 20231004:使用批量方法替代
        // self::stopUse(__METHOD__);
        $info = $this->get();
        if(SalaryTimeService::getInstance($info['time_id'])->fTimeLock()){
            throw new Exception('当月工资已锁定不可操作');
        }
    }

    public static function ramAfterSave(&$data, $uuid) {
        // 同步至薪资明细表
        self::getInstance($uuid)->toSalaryUserItemDtlSync();

        // self::getInstance($uuid)->updateDataRam();
    }

    public static function ramAfterUpdate(&$data, $uuid) {
        // 同步至薪资明细表
        self::getInstance($uuid)->toSalaryUserItemDtlSync();
        
        // self::getInstance($uuid)->updateDataRam();
    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete($data) {
        // 批量的处理？？
        self::baoBusDriverDelDeal($data['bao_bus_driver_id']);
        // 删除明细
        self::salaryUserItemDelDeal($this->uuid);
        // 20240110
        SalaryOrderBaoBusDriverDtlService::clearBySalaryOrderBaoBusDriverId($this->uuid);
    }
    
    public static function ramAfterDeleteBatch($dataArr){
        // 批量的处理？？
        $baoBusDriverIds = array_column($dataArr, 'bao_bus_driver_id');
        self::baoBusDriverDelDeal($baoBusDriverIds);
        // 删除明细
        $ids    = array_column($dataArr, 'id');
        self::salaryUserItemDelDeal($ids);
        // 20240110
        SalaryOrderBaoBusDriverDtlService::clearBySalaryOrderBaoBusDriverId($ids);

    }
    
    protected static function baoBusDriverDelDeal($baoBusDriverIds){
        $con1                       = [];
        $con1[]                     = ['id','in',$baoBusDriverIds];
        $updData                    = [];
        $updData['salary_item_id']  = '';
        OrderBaoBusDriverService::where($con1)->update($updData);
    }
    
    protected static function salaryUserItemDelDeal($ids){
        $fromTable      = self::getRawTable();
        SalaryUserItemDtlService::deleteByFromTableAndFromTableId($fromTable, $ids);
    }
        
    protected static function redunFields(&$data, $uuid){
        // 工资已发，或时间已锁，不改
        
        if(Arrays::value($data, 'bao_bus_driver_id')){
            $baoBusDriverId = Arrays::value($data, 'bao_bus_driver_id');
            // 司机信息
            $info = OrderBaoBusDriverService::getInstance($baoBusDriverId)->get();
            $data['bao_bus_id'] = $info['bao_bus_id'];
            // 出车信息
            $baoBusInfo = OrderBaoBusService::getInstance($info['bao_bus_id'])->get();
            $data['order_id'] = $baoBusInfo['order_id'];
            //订单信息
            $orderInfo = OrderService::getInstance($baoBusInfo['order_id'])->get();

            $data['start_time']          = Arrays::value($baoBusInfo, 'start_time', '');
            // 20231123
            // $data['time_id']             = SalaryTimeService::getTimeId($data['start_time']);
            // 工资没锁才有改
            if(!SalaryTimeService::getInstance($data['time_id'])->fTimeLock()){
                $data['customer_id']         = Arrays::value($orderInfo, 'customer_id', '');
                $data['user_id']             = Arrays::value($orderInfo, 'user_id', '');
                $data['need_invoice']        = Arrays::value($orderInfo, 'need_invoice', 0);
                $data['driver_id']           = $info['driver_id'];
                $data['bus_type_id']         = $baoBusInfo['bus_type_id'];
                $data['busTypeSeats']        = BusTypeService::getInstance($data['bus_type_id'])->calSeats();
                $data['bus_id']              = Arrays::value($baoBusInfo, 'bus_id', '');
                $data['route_start']         = Arrays::value($baoBusInfo, 'route_start', '');
                $data['route_end']           = Arrays::value($baoBusInfo, 'route_end', '');
                // 20231124:已锁定的
                $lockedPrizeData = self::calLockedPrizeByBaoBusDriverId($baoBusDriverId);

                $distributePrize = $info['distribute_prize'] ? : 0;
                $data['distribute_prize']    = $distributePrize - Arrays::value($lockedPrizeData, 'distributePrize',0);
                // 没必要存在原始订单了，慢慢过渡
                // $data['calculate_prize']     = $info['calculate_prize'];
                // $data['rate']                = $info['rate'];
                // $data['grant_money']         = $info['grant_money'];
                $eatMoney   = Arrays::value($info, 'eat_money', 0 );
                $otherMoney = Arrays::value($info, 'other_money', 0 );
                
                $lockedEatMoney             = Arrays::value($lockedPrizeData, 'eatMoney') ? : 0;
                $lockedOtherMoney           = Arrays::value($lockedPrizeData, 'otherMoney') ? : 0;
                $data['eat_money']          = $lockedEatMoney ? $eatMoney - $lockedEatMoney : $eatMoney;
                $data['other_money']        = $lockedOtherMoney ? $otherMoney - $lockedOtherMoney : $otherMoney;

                //20231122
                // 20240503:发现有的价格是空字符串
                $data['calculate_prize']        = self::calCalculatePrize($data, $uuid) ? : 0;
                $data['calculate_prize_desc']   = self::calCalculatePrizeDesc($data);
                $data['rate']                   = self::calRate($data);

                $grantMoney = $data['calculate_prize'] && ['rate'] 
                    ? $data['calculate_prize'] * $data['rate']
                    : 0;
                $data['grant_money']            = $grantMoney ? Number::round($grantMoney, 2) : 0;
            }
        }
        
        if(Arrays::value($data, 'start_time')){
            // 20231123
            self::getInstance($uuid)->calUpdateDiffs($data);
            // 20231121
            self::getInstance($uuid)->setUuData($data, true);
        }
        
        return $data;
    }
}
