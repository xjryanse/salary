<?php

namespace xjryanse\salary\service\userItem;

use xjryanse\salary\service\SalaryUserItemService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\DataCheck;
use xjryanse\salary\service\SalaryUserService;
use xjryanse\salary\service\SalaryUserItemTplService;
use xjryanse\salary\service\SalaryTimeTypeService;
use xjryanse\salary\service\SalaryTimeService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\user\service\UserAuthUserRoleService;
use xjryanse\salary\service\SalaryTypeService;
use xjryanse\user\service\UserService;
use xjryanse\bus\service\BusService;
use Exception;

/**
 * 
 */
trait DoTraits{
    
    /**
     * 20231122：手工输入项目更新(下钻车辆)
     * @param type $userId
     * @param type $timeId
     */
    public static function doHandleItemUpdateBus($param){
        $paramId    = Arrays::value($param, 'id');
        // 协议: 0-time_id, 1-user_id, 2-bus_id, 3-type_id
        // $arr = mb_str_split($paramId, 19);
        $arr        = explode('_',$paramId);
        // 会话读取
        $timeId     = $arr[0];
        $userId     = $arr[1];
        $busId      = $arr[2];
        $typeId     = $arr[3];
        $itemId     = Arrays::value($param, 'itemId');
        // 20231226
        $isLock     = SalaryTimeTypeService::calIsLockByTimeIdAndTypeId($timeId, $typeId);
        if($isLock){
            throw new Exception('当月工资已锁，修改不生效');
        }
        // 20231124:key加前缀s
        $key                = 's'.$itemId;
        $updData['salary']  = Arrays::value($param, $key, 0);
        return self::updateByTimeUserBusTypeItem($timeId, $userId, $busId, $typeId, $itemId, $updData);
    }
    /**
     * 从上一个计薪周期复制固定项目
     * @param type $param
     * @return type
     * @throws Exception
     */
    public static function doCopyLastTimeData($param){
        $keys = ['time_id','salary_type_id'];
        DataCheck::must($param, $keys);
        // 类型
        $timeId         = Arrays::value($param, 'time_id');
        $salaryTypeId   = Arrays::value($param, 'salary_type_id');
        // 
        $lastTimeId = SalaryTimeService::getInstance($timeId)->calPreTimeId();
        if(!$lastTimeId){
            throw new Exception('没有上个计薪时段数据');
        }
        
        $itemIds    = SalaryItemService::salaryTypeEditableIds($salaryTypeId);

        return self::doCopy($timeId, $lastTimeId, $salaryTypeId, $itemIds);
    }
    
    /**
     * 
     * @param type $timeId          当前
     * @param type $sourceTimeId    目标，一般是上月
     */
    protected static function doCopy($timeId, $sourceTimeId, $salaryTypeId, $itemIds){

        self::checkTimeHasData($timeId, $salaryTypeId, $itemIds);

        $cone   = [];
        $cone[] = ['time_id','=',$sourceTimeId];
        $cone[] = ['salary_type_id','=',$salaryTypeId];
        $cone[] = ['item_id','in',$itemIds];
        $hasDtlInfo = self::where($cone)->where('need_dtl',1)->find();
        if($hasDtlInfo){
            $itemName = SalaryItemService::getInstance($timeId)->fItemName();
            throw new Exception($itemName.'有明细项，不可操作');
        }

        $lists      = self::where($cone)->select();
        $listsArr   = $lists ? $lists->toArray() : [];

        $keys = ['user_id','bus_id','salary_type_id','item_id','need_dtl','salary'];
        $saveData   = Arrays2d::getByKeys($listsArr, $keys);
        foreach($saveData as &$v){
            $v['salary_user_id']    = SalaryUserService::matchId($v['user_id'], $v['salary_type_id'], $timeId);
            // $v['time_id'] = $timeId;
        }
        
        return self::saveAllRam($saveData);
    }

    
        
    /**
     * 从薪资模板复制固定项目
     * @param type $param
     * @return type
     * @throws Exception
     */
    public static function doCopyTplData($param){
        $keys = ['salary_type_id'];
        DataCheck::must($param, $keys);
        
        $timeId         = Arrays::value($param, 'time_id');
        // 类型
        $salaryTypeId   = Arrays::value($param, 'salary_type_id');
        // 
        $itemIds    = SalaryItemService::salaryTypeEditableIds($salaryTypeId);
        
        return self::doCopyTpl($timeId, $salaryTypeId, $itemIds);
    }
    /**
     * 20231228：清理固定项数据
     * 危险操作：需要校验权限
     */
    public static function doClearFixedData($param){
        $userId     = session(SESSION_USER_ID);
        $roleKey    = 'salary';
        if(!UserAuthUserRoleService::userHasRoleKey($userId, $roleKey)){
            throw new Exception('您没有权限，请联系管理员开通:'.$roleKey);
        }
        if(!UserAuthUserRoleService::isStaff($userId)){
            throw new Exception('您的员工权限异常，请联系管理员处理');
        }

        $keys = ['salary_type_id'];
        DataCheck::must($param, $keys);

        $timeId         = Arrays::value($param, 'time_id');
        // 类型
        $salaryTypeId   = Arrays::value($param, 'salary_type_id');
        // 提取itemIds
        $itemIds        = SalaryItemService::salaryTypeEditableIds($salaryTypeId);
        
        $con    = [];
        $con[]  = ['salary_type_id', '=', $salaryTypeId];
        $con[]  = ['time_id','=',$timeId];
        $con[]  = ['item_id','in',$itemIds];
        $con[]  = ['need_dtl','=',0];
        $lists  = self::where($con)->select();
        foreach($lists as $v){
            self::getInstance($v['id'])->deleteRam();
        }
        return true;
    }
    

    /**
     * 
     * 20231210:从薪资模板复制数据
     * @param type $timeId          当前
     * @param type $sourceTimeId    目标，一般是上月
     */
    protected static function doCopyTpl($timeId, $salaryTypeId, $itemIds){

        self::checkTimeHasData($timeId, $salaryTypeId, $itemIds);
        
        $saveData = SalaryUserItemTplService::listForCopy($salaryTypeId, $itemIds);

        foreach($saveData as &$v){
            $v['salary_user_id']    = SalaryUserService::matchId($v['user_id'], $v['salary_type_id'], $timeId);
        }
        
        return self::saveAllRam($saveData);
    }

    private static function checkTimeHasData($timeId, $salaryTypeId, $itemIds){
        $con    = [];
        $con[]  = ['time_id','=',$timeId];
        $con[]  = ['salary_type_id','=',$salaryTypeId];
        $con[]  = ['item_id','in',$itemIds];
        $hasId = self::where($con)->value('id');
        if($hasId){
            $timeName = SalaryTimeService::getInstance($timeId)->fName();
            throw new Exception('时段'.$timeName.'已存在薪资项目，请先清理才能操作'.$hasId);
        }
    }

    /**
     * 20231228:导入固定项部分工资数据
     */
    public static function doImportFix($param){
        $keys           = ['time_id','salary_type_id'];
        DataCheck::must($param, $keys);
        $timeId         = Arrays::value($param, 'time_id');
        // 类型
        $salaryTypeId   = Arrays::value($param, 'salary_type_id');
        //验证有数据不可操作；
        $itemIds        = SalaryItemService::salaryTypeEditableIds($salaryTypeId);
        
        self::checkTimeHasData($timeId, $salaryTypeId, $itemIds);

        $tableData = Arrays::value($param, 'table_data') ? : [];
        $salaryTypeRoleKey = SalaryTypeService::getInstance($salaryTypeId)->fRoleKey();
        // 20231228:提取可能需要发薪的用户id，用于限定范围匹配姓名
        $perhapsUserIds = SalaryTimeService::perhapsUserIds($timeId);

        foreach($tableData as $dItem){
            $userId = UserService::realnameToId($dItem['realname'], $perhapsUserIds);
            if(!$userId){
                throw new Exception($dItem['realname'].'无法匹配');
            }
            $busId  = BusService::licencePlateToId($dItem['licence_plate']);
            if(!$busId && $salaryTypeRoleKey == 'driver'){
                throw new Exception($dItem['licence_plate'].'无法匹配');
            }
            
            foreach($itemIds as $itemId){
                $key                = 's'.$itemId;
                $updData            = [];
                $updData['salary']  = trim(Arrays::value($dItem, $key, 0));
                self::updateByTimeUserBusTypeItem($timeId, $userId, $busId, $salaryTypeId, $itemId, $updData);                
            }
        }

        return true;
    }

    /**
     * 20240603
     * @param type $param
     */
    public static function doUpdateParam($param){
        $userItemId     = self::matchIdWithTimeId($param['user_id'], $param['item_id'],$param['bus_id'], $param['time_id'], $param['salary_type_id']);
        self::getInstance($userItemId)->updateRam(['salary'=>$param['salary']]);
        return true;
    }
   
    /**
     * HX系统调整
     */
    public static function doUpdateHandleParam($param){
        $keys = ['time_id','salary_type_id','user_id','item_id'];
        DataCheck::must($param, $keys);
        // 步骤1：
        $suD            = Arrays::getByKeys($param, ['time_id','salary_type_id','user_id']);
        $salaryUserId   = SalaryUserService::commGetIdEG($suD);
        // 步骤2：
        $sData['salary_user_id']    = $salaryUserId;
        $sData['bus_id']            = Arrays::value($param, 'bus_id');
        $sData['item_id']           = Arrays::value($param, 'item_id');
        $id = self::commGetIdEg($sData);
        // 步骤3：
        self::getInstance($id)->updateRam(['salary'=>$param['salary']]);

        return $id;
    }
    
}
