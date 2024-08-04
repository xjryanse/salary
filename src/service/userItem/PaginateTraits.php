<?php

namespace xjryanse\salary\service\userItem;

use xjryanse\salary\service\SalaryItemService;
use xjryanse\logic\ModelQueryCon;
use xjryanse\salary\service\SalaryTypeItemService;
use xjryanse\salary\service\SalaryOrderBaoBusDriverService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\salary\service\SalaryTimeTypeService;
use xjryanse\bus\service\BusService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Prize;
use xjryanse\logic\Number;

use think\Db;
use think\facade\Request; 

/**
 * 
 */
trait PaginateTraits{
    
    /**
     * 用户+发薪批次+ 车下钻 +横轴各薪资项
     * 下钻到车-2023-11-22
     * @return array
     */
    public static function paginateForSalaryTypeTimeUserBus($con = []){
        $qData = Request::param('table_data') ? : Request::param();
        
        $typeId = Arrays::value($qData, 'type_id') ? : Arrays::value($qData, 'salary_type_id');
        $timeId = Arrays::value($qData, 'time_id');
        
        $userId = Arrays::value($qData, 'user_id');
        $busId = Arrays::value($qData, 'bus_id');

        $conTU = [];
        if($userId){
            $conTU[] = ['user_id','=',$userId];
        }
        if($busId){
            $conTU[] = ['bus_id','=',$busId];
        }
        // dump($timeId);
        // dump($typeId);
        // 20231226:判断是否锁定
        $isLock = SalaryTimeTypeService::calIsLockByTimeIdAndTypeId($timeId, $typeId);
        //
        // $data['data'] = SalaryUserService::salaryTypeTimeUserList($typeId, $timeId);
        $dataArr = self::salaryTypeTimeUserList($typeId, $timeId, $conTU);
        
        $itemsArr = SalaryItemService::listsByTypeId($typeId);
        // 求和
        $sumData = [];
        foreach ($itemsArr as $item) {
            // 20230604:控制前端页面显示的动态站点:字段格式：universal_item_table表
            $isSalary = in_array($item['item_key'],['tangGrant','tangEat','tangOther']);
            $tmp = [
                'id' => self::mainModel()->newId(),
                'name' => 's'.$item['id'],
                'label' => $item['item_name'],
                'type' => 'prize',
                'width'=>70,
                'sortable'=>1,
            ];
            // 编辑项目;20231226:增加 $isLock 判定
            if(!$isLock && $item['editable']){
                $tmp['type'] = 'listedit';
                $tmp['update_url'] = '/admin/salary/ajaxOperateFullP?admKey=userItem&doMethod=doHandleItemUpdateBus&itemId='.$item['id'];
            }
            // 弹窗明细
            if($isSalary){
                $tmp['pop_page']    = $isSalary ? 'p4SalaryOrderBaoBusDriverList' : '';
                $tmp['pop_param']   = ['time_id'=>'time_id','driver_id'=>'user_id','bus_id'=>'bus_id'];
            }
            
            $data['fdynFields'][] = $tmp;
            
            // 求和
            $sumKey = 's'.$item['id'];
            $sumData[$sumKey] = round(Arrays2d::sum($dataArr, $sumKey),2);
        }
        $sumData['salary_item_total']    = round(Arrays2d::sum($dataArr, 'salary_item_total'),2);
        // 发款合计
        $sumData['salary_total']    = round(Arrays2d::sum($dataArr, 'salary_total'),2);
        // 扣款合计
        $sumData['salary_cut']      = round(Arrays2d::sum($dataArr, 'salary_cut'),2);
        // 实发
        $sumData['salary_real']     = round(Arrays2d::sum($dataArr, 'salary_real'),2);
        
        $data['data']       = $dataArr;
        $data['sumData']    = $sumData;
        $data['withSum']    = 1;
        $data['$isLock']    = $isLock;
        
        return $data;
    }
    
    /**
     * 20231128:手动输入的项目
     * @param type $con
     * @return type
     */
    public static function paginateForSalaryTypeTimeUserBusHandle($con){

        $typeId = ModelQueryCon::parseValue($con, 'salary_type_id');
        $timeId = ModelQueryCon::parseValue($con, 'time_id');
        $userId = ModelQueryCon::parseValue($con, 'user_id');
        $busId  = ModelQueryCon::parseValue($con, 'bus_id');
        //
        // $data['data'] = SalaryUserService::salaryTypeTimeUserList($typeId, $timeId);
        $conTU = [];
        if($userId){
            $conTU[] = ['user_id','=',$userId];
        }
        if($busId){
            $conTU[] = ['bus_id','=',$busId];
        }
        
        $data['data'] = self::salaryTypeTimeUserList($typeId, $timeId, $conTU);

        $cone       = [];
        $cone[]     = ['editable','=',1];
        $itemsArr   = SalaryItemService::listsByTypeId($typeId, $cone);
        
        foreach ($itemsArr as $item) {
            // 20230604:控制前端页面显示的动态站点:字段格式：universal_item_table表
            $data['fdynFields'][] = ['id' => self::mainModel()->newId(), 'name' => 's'.$item['id'], 'label' => $item['item_name']
                    , 'type' => 'listedit'
                    , 'sortable'=>1
                    , 'update_url' => '/admin/salary/ajaxOperateFullP?admKey=userItem&doMethod=doHandleItemUpdateBus&itemId='.$item['id']
                ];
        }

        // 20231209纵轴求和
        foreach($data['data'] as &$vee){
            $sum = 0;
            foreach ($itemsArr as $item) {
                $key = 's'.$item['id'];
                $sum += Arrays::value($vee, $key, 0);
            }
            // 20231209:固定项合计数
            $vee['fixSum'] = $sum;
        }
        // 20231209:横轴求和
        $data['sumData'] = [];
        foreach ($itemsArr as $item) {
            $key = 's'.$item['id'];
            $data['sumData'][$key] = Arrays2d::sum($data['data'], $key);
        }
        
        $data['withSum']= 1;
        
        
        return $data;
    }


    public static function salaryTypeTimeUserList($typeId, $timeId, $con = []){
        // 20231127:出车+车辆
        $tangUserIdBusIds   = SalaryOrderBaoBusDriverService::driverIdBusIdsForSalary($timeId, $typeId);
        // 已经发薪的批次
        $userIdBusIds       = self::userIdBusIds($timeId, $typeId);

        $allUserIdBusIds    = array_merge($userIdBusIds, $tangUserIdBusIds);
        // 提取主数据
        $listsArr           = self::timeTypeList($timeId, $typeId);
        // dump($listsArr);
        $listsObj           = Arrays2d::fieldSetKey($listsArr, 'id');

        $UBArr = Arrays2d::fieldSetKey($allUserIdBusIds, 'iKey');
        $arr = [];        
        foreach($UBArr as $k=>$userBusId){
            $lData          = Arrays::value($listsObj, $k);
            
            $tmp            = $lData ? $lData : [];
            // 20231122：方便后台修改
            $tmp['id']      = $k;
            $tmp['salaryBaoBusDriverCount'] = Arrays::value($userBusId, 'itemCount');
            $tmp['user_id'] = Arrays::value($userBusId, 'user_id');
            $tmp['bus_id']  = Arrays::value($userBusId, 'bus_id');
            $tmp['type_id'] = $typeId;
            $tmp['time_id'] = $timeId;
            // 20231128:按座位数排序
            $tmp['busSeats'] = BusService::getInstance($tmp['bus_id'])->fSeats();
            // 出车最多的排前面
            // 20240711
            $tmp['status']   = 1;
            
            $arr[] = $tmp;
        }
        
        // 20231209:增加筛选过滤
        $arr = Arrays2d::listFilter($arr, $con);
        
        Arrays2d::sort($arr, 'user_id');

        return $arr;
    }
    
    
    public static function timeTypeList($timeId, $typeId){
        $con    = [];
        $con[]  = ['time_id','in',$timeId];
        $con[]  = ['salary_type_id','in',$typeId];

        // $keepKeys = ['id','time_id','user_id','salary_type_id','salary_total','salary_cut','salary_real','status'];

        
        // concat(time_id,user_id,bus_id,salary_type_id) as id,
        
        $fieldStr       = 'time_id,user_id,bus_id,salary_type_id,sum(salary) as salary_real'
                . ',sum(if(salary>0,salary,0)) as salary_total'
                . ',sum(if(salary<0,salary,0)) as salary_cut';
        $groupFieldStr  = 'time_id,user_id,bus_id,salary_type_id';

        $lists = self::where($con)->group($groupFieldStr)->field($fieldStr)->select();
        $listsArr = $lists ? $lists->toArray() : [];
        
        foreach($listsArr as &$v){
            $v['id'] = implode('_',[$v['time_id'],$v['user_id'],$v['bus_id'],$typeId]);
        }
        // dump($typeId);
        //20230930：拼接一些项目
        $itemIds = SalaryTypeItemService::dimItemIdsByTypeId($typeId);
        // 20230930:用户，细项列表
        // $userLists = [];
        // dump($listsArr);
        // $salaryUserIds = array_unique(array_column($listsArr, 'id'));
        
        $cone    = [];
        $cone[]  = ['time_id','in', array_column($listsArr, 'time_id')];
        $cone[]  = ['user_id','in', array_column($listsArr, 'user_id')];
        $cone[]  = ['bus_id','in', array_column($listsArr, 'bus_id')];
        $cone[]  = ['salary_type_id','in', array_column($listsArr, 'salary_type_id')];

        // $userItemLists = self::dimListsBySalaryUserId($salaryUserIds);
        $userItemListsObj = self::where($cone)->select();
        $userItemLists = $userItemListsObj ? $userItemListsObj->toArray() : [];
        foreach($userItemLists as &$ve){
            // $ve['uid'] = $ve['salary_user_id'].$ve['bus_id'].$ve['item_id'];
            $ve['uid'] = implode('_',[$ve['time_id'],$ve['user_id'],$ve['bus_id'],$ve['salary_type_id'],$ve['item_id']]);
            // 协议: 0-time_id, 1-user_id, 2-bus_id, 3-type_id
        }
        $userItemListsObj = Arrays2d::fieldSetKey($userItemLists, 'uid');

        // 发工资项
        $addItemIds = SalaryItemService::addItemIds();
        // 扣款项
        $cutItemIds = SalaryItemService::cutItemIds();
        
        foreach($listsArr as &$v){
            // $v['salaryUserId'] = $v['id'];
            // 20230930:拼接工资动态
            $addVal = 0;
            $cutVal = 0;
            foreach ($itemIds as $iId) {
                $key            = $v['id'].'_'.$iId;
                $item           = Arrays::value($userItemListsObj, $key);
                $v['s'.$iId]    = $item ? Prize::clearZero(Arrays::value($item, 'salary')) : 0;
                // 发薪
                $addVal += in_array($iId, $addItemIds) ? $v['s'.$iId] : 0;
                // 扣款
                $cutVal += in_array($iId, $cutItemIds) ? $v['s'.$iId] : 0;
                // dump($v['s'.$iId]);
            }
            // 发薪
            $v['salary_item_total'] = round($addVal,2);
            // 扣款
            $v['salary_item_cut']   = round($cutVal,2);
        }
        return $listsArr;
    }
    
    
    /**
     * 20231211:统计基本工资
     * @param type $con
     * @return type
     */
    public static function paginateForSalaryTypeBaseSalary($con){
        $param      = Request::param('table_data') ? : [];
        $yearmonth  = Arrays::value($param, 'yearmonth');
        // 20231221：月份转time_id
        $timeIds = SalaryTimeService::yearmonthToTimeIds($yearmonth);
        
        $typeId = '5370933273363046400';
        
        $userId = ModelQueryCon::parseValue($con, 'user_id');
        $busId  = ModelQueryCon::parseValue($con, 'bus_id');
        $conTU = [];
        if($userId){
            $conTU[] = ['user_id','=',$userId];
        }
        if($busId){
            $conTU[] = ['bus_id','=',$busId];
        }
        
        //
        // $data['data'] = SalaryUserService::salaryTypeTimeUserList($typeId, $timeId);
        $dataArr = self::salaryTypeTimeUserList($typeId, $timeIds, $conTU);
        
        $data['data'] = $dataArr;
        // 提取基本工资项目
        $itemIds    = SalaryTypeItemService::baseItemIds($typeId);
        $cone       = [];
        $cone[]     = ['a.id','in',$itemIds];
        $itemsArr   = SalaryItemService::listsByTypeId($typeId, $cone);
        
        foreach ($itemsArr as $item) {
            // 20230604:控制前端页面显示的动态站点:字段格式：universal_item_table表
            $data['fdynFields'][] = [
                    'id' => self::mainModel()->newId()
                    , 'name' => 's'.$item['id']
                    , 'label' => $item['item_name']
                    , 'type' => 'prize'
                ];
        }

        // 20231209纵轴求和
        foreach($data['data'] as &$vee){
            $sum = 0;
            foreach ($itemsArr as $item) {
                $key = 's'.$item['id'];
                $sum += Arrays::value($vee, $key, 0);
            }
            // 20231209:固定项合计数
            $vee['fixSum'] = $sum;
            $vee['yearmonth'] = $yearmonth;
        }
        // 20231209:横轴求和
        $data['sumData'] = [];
        foreach ($itemsArr as $item) {
            $key = 's'.$item['id'];
            $data['sumData'][$key] = Arrays2d::sum($data['data'], $key);
        }
        $data['sumData']['fixSum'] = Arrays2d::sum($data['data'], 'fixSum');
        
        $data['withSum']= 1;
        
        
        return $data;
    }
    
    /**
     * 20231211:统计全部工资工资
     * 用于手机端查询列表
     * @param type $con
     * @return type
     */
    public static function paginateForSalaryTypeSalary($con){
        $param      = Request::param('table_data') ? : [];
        $yearmonth  = Arrays::value($param, 'yearmonth');
        // 20231221：月份转time_id
        $timeIds = SalaryTimeService::yearmonthToTimeIds($yearmonth);
        
        $typeId = '5370933273363046400';
        
        $userId = ModelQueryCon::parseValue($con, 'user_id');
        $busId  = ModelQueryCon::parseValue($con, 'bus_id');
        $conTU = [];
        if($userId){
            $conTU[] = ['user_id','=',$userId];
        }
        if($busId){
            $conTU[] = ['bus_id','=',$busId];
        }
        
        //
        // $data['data'] = SalaryUserService::salaryTypeTimeUserList($typeId, $timeId);
        $dataArr = self::salaryTypeTimeUserList($typeId, $timeIds, $conTU);
        
        $data['data'] = $dataArr;
        // 提取全部工资项目
        $itemsArr = SalaryItemService::listsByTypeId($typeId);
        
        foreach ($itemsArr as $item) {
            // 20230604:控制前端页面显示的动态站点:字段格式：universal_item_table表
            $data['fdynFields'][] = [
                    'id' => self::mainModel()->newId()
                    , 'name' => 's'.$item['id']
                    , 'label' => $item['item_name']
                    , 'type' => 'prize'
                ];
        }

        // 20231209纵轴求和
        foreach($data['data'] as &$vee){
            $sum = 0;
            foreach ($itemsArr as $item) {
                $key = 's'.$item['id'];
                $sum += Arrays::value($vee, $key, 0);
            }
            // 20231209:固定项合计数
            $vee['fixSum'] = round($sum,2);
            $vee['yearmonth'] = $yearmonth;
        }
        // 20231209:横轴求和
        $data['sumData'] = [];
        foreach ($itemsArr as $item) {
            $key = 's'.$item['id'];
            $data['sumData'][$key] = round(Arrays2d::sum($data['data'], $key),2);
        }
        $data['sumData']['fixSum'] = round(Arrays2d::sum($data['data'], 'fixSum'),2);
        
        $data['withSum']= 1;
        
        
        return $data;
    }

}
