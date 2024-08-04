<?php

namespace xjryanse\salary\service\userItemTpl;

use xjryanse\salary\service\SalaryItemService;
use xjryanse\logic\ModelQueryCon;
use xjryanse\salary\service\SalaryTypeItemService;
use xjryanse\salary\service\SalaryTypeService;
use xjryanse\bus\service\BusService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Prize;
use Exception;

/**
 * 
 */
trait PaginateTraits{

    
    /**
     * 20231128:手动输入的项目
     * @param type $con
     * @return type
     */
    public static function paginateForUserBusHandle($con){

        $typeId = ModelQueryCon::parseValue($con, 'salary_type_id');
        if(!$typeId){
            throw new Exception('salary_type_id必须');
        }
        
        $userId = ModelQueryCon::parseValue($con, 'user_id');
        $busId = ModelQueryCon::parseValue($con, 'bus_id');
        //
        // $data['data'] = SalaryUserService::salaryTypeTimeUserList($typeId, $timeId);
        $conTU = [];
        if($userId){
            $conTU[] = ['user_id','=',$userId];
        }
        if($busId){
            $conTU[] = ['bus_id','=',$busId];
        }
        
        $data['data'] = self::salaryTypeTimeUserList($typeId, $conTU);

        $cone       = [];
        $cone[]     = ['editable','=',1];
        $itemsArr   = SalaryItemService::listsByTypeId($typeId, $cone);
        
        foreach ($itemsArr as $item) {
            // 20230604:控制前端页面显示的动态站点:字段格式：universal_item_table表
            $data['fdynFields'][] = ['id' => self::mainModel()->newId(), 'name' => 's'.$item['id'], 'label' => $item['item_name']
                    , 'type' => 'listedit'
                    , 'sortable'=>1
                    , 'update_url' => '/admin/salary/ajaxOperateFullP?admKey=userItemTpl&doMethod=doHandleItemUpdateBus&itemId='.$item['id']
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
    
    public static function salaryTypeTimeUserList($typeId, $con = []){
        // 20231127:出车+车辆
        // 
        // 20231210:提取最新一次
        // $tangUserIdBusIds   = SalaryOrderBaoBusDriverService::driverIdBusIdsForSalary($timeId, $typeId);
        // 已经发薪的批次
        $userIdBusIds       = self::userIdBusIds($typeId);
        if(SalaryTypeService::getInstance($typeId)->fRoleKey() == 'driver'){
            $busDriverBusIds = self::busDriverIdBusIds($typeId);
            $userIdBusIds    = array_merge($userIdBusIds, $busDriverBusIds);
        }

        // $allUserIdBusIds    = array_merge($userIdBusIds, $tangUserIdBusIds);
        // 提取主数据
        $listsArr           = self::typeList( $typeId);
        // dump($listsArr);
        $listsObj           = Arrays2d::fieldSetKey($listsArr, 'id');

        $UBArr = Arrays2d::fieldSetKey($userIdBusIds, 'iKey');
        $arr = [];        
        foreach($UBArr as $k=>$userBusId){
            $lData          = Arrays::value($listsObj, $k);
            
            $tmp            = $lData ? $lData : [];
            // 20231122：方便后台修改
            $tmp['id']      = $k;
            // $tmp['salaryBaoBusDriverCount'] = Arrays::value($userBusId, 'itemCount');
            $tmp['user_id'] = Arrays::value($userBusId, 'user_id');
            $tmp['bus_id']  = Arrays::value($userBusId, 'bus_id');
            $tmp['type_id'] = $typeId;
            // 20231128:按座位数排序
            $tmp['busSeats'] = BusService::getInstance($tmp['bus_id'])->fSeats();
            $arr[] = $tmp;
        }
        
        // 20231209:增加筛选过滤
        $arr = Arrays2d::listFilter($arr, $con);
        
        Arrays2d::sort($arr, 'user_id');

        return $arr;
    }
    
    
    public static function typeList($typeId){
        $con    = [];
        $con[]  = ['salary_type_id','in',$typeId];
        
        $fieldStr       = 'user_id,bus_id,salary_type_id,sum(salary) as salary_total'
                . ',sum(if(salary>0,salary,0)) as salary_real'
                . ',sum(if(salary<0,salary,0)) as salary_cut';
        $groupFieldStr  = 'user_id,bus_id,salary_type_id';

        $lists = self::where($con)->group($groupFieldStr)->field($fieldStr)->select();
        $listsArr = $lists ? $lists->toArray() : [];
        
        foreach($listsArr as &$v){
            $v['id'] = implode('_',[$v['user_id'],$v['bus_id'],$typeId]);
        }
        // dump($typeId);
        //20230930：拼接一些项目
        $itemIds = SalaryTypeItemService::dimItemIdsByTypeId($typeId);
        // 20230930:用户，细项列表
        $cone    = [];
        $cone[]  = ['user_id','in', array_column($listsArr, 'user_id')];
        $cone[]  = ['bus_id','in', array_column($listsArr, 'bus_id')];
        $cone[]  = ['salary_type_id','in', array_column($listsArr, 'salary_type_id')];

        // $userItemLists = self::dimListsBySalaryUserId($salaryUserIds);
        $userItemListsObj = self::where($cone)->select();
        $userItemLists = $userItemListsObj ? $userItemListsObj->toArray() : [];
        foreach($userItemLists as &$ve){
            // $ve['uid'] = $ve['salary_user_id'].$ve['bus_id'].$ve['item_id'];
            $ve['uid'] = implode('_',[$ve['user_id'],$ve['bus_id'],$ve['salary_type_id'],$ve['item_id']]);
            // 协议: 0-time_id, 1-user_id, 2-bus_id, 3-type_id
        }
        $userItemListsObj = Arrays2d::fieldSetKey($userItemLists, 'uid');

        foreach($listsArr as &$v){
            // $v['salaryUserId'] = $v['id'];
            // 20230930:拼接工资动态
            foreach ($itemIds as $iId) {
                $key            = $v['id'].'_'.$iId;
                $item           = Arrays::value($userItemListsObj, $key);
                $v['s'.$iId]    = $item ? Prize::clearZero(Arrays::value($item, 'salary')) : 0;
                // dump($v['s'.$iId]);
            }
        }
        return $listsArr;
    }
    
    

}
