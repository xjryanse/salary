<?php

namespace xjryanse\salary\service\userItemTpl;

use xjryanse\logic\Arrays;
use xjryanse\logic\DataCheck;
use xjryanse\logic\ModelQueryCon;
/**
 * 
 */
trait DoTraits{
    
    /**
     * 添加用户和关联车
     */
    public static function doUserBusAdd($param){
        DataCheck::must($param, ['user_id','salary_type_id']);
        $keys = ['user_id','bus_id','salary_type_id'];
        $data = Arrays::getByKeys($param, $keys);
        
        return self::saveRam($data);
    }

    /**
     * 删除用户和关联车
     */
    public static function doUserBusDel($param){
        DataCheck::must($param, ['user_id','salary_type_id']);
        
        $fields = [];
        $fields['equal'] = ['user_id','bus_id','salary_type_id'];
        $con = ModelQueryCon::queryCon($param, $fields);
        
        return self::where($con)->delete();
    }
    
    
    /**
     * 20231122：手工输入项目更新(下钻车辆)
     * @param type $userId
     * @param type $timeId
     */
    public static function doHandleItemUpdateBus($param){
        $paramId = Arrays::value($param, 'id');
        // 协议: 0-user_id, 1-bus_id, 2-type_id
        // $arr = mb_str_split($paramId, 19);
        $arr = explode('_',$paramId);
        // 
        
        // 会话读取
        $userId = $arr[0];
        $busId  = $arr[1];
        $typeId = $arr[2];
        $itemId = Arrays::value($param, 'itemId');
        

        $id = self::doMatchId($typeId, $userId, $itemId, $busId);
        // 20231124:key加前缀s
        $key = 's'.$itemId;
        $value = Arrays::value($param, $key, 0);
        if(!floatval($value)){
            // 0，删了
            return self::getInstance($id)->deleteRam();
        } else {
            // 更新
            return self::getInstance($id)->updateRam(['salary'=>$value]);
        }
    }
    
    
    /**
     * 用户id，薪资id，发薪批次id，匹配一个记录id，没有时新增
     * @param type $salaryUserId
     * @param type $itemId
     * @param type $busId
     * @return type
     */
    private static function doMatchId( $typeId, $userId, $itemId, $busId){
        $con    = [];
        $con[]  = ['salary_type_id', '=', $typeId];
        $con[]  = ['user_id', '=', $userId];
        $con[]  = ['item_id', '=', $itemId];
        $con[]  = ['bus_id', '=', $busId];
        $info = self::ramFind($con);
        if($info){
            //步骤1：先从保存数据取
            $id = $info['id'];
        } else {
            //步骤2：从数据库取
            $id = self::where($con)->value('id');
        }

        if(!$id){
            $sData = [];
            $sData['salary_type_id']    = $typeId;
            $sData['user_id']           = $userId;
            $sData['item_id']           = $itemId;
            $sData['bus_id']            = $busId;
            // 步骤3：都没有才保存
            $id = self::saveGetIdRam($sData);
        }

        return $id;
    }

}
