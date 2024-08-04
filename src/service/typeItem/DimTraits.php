<?php

namespace xjryanse\salary\service\typeItem;

/**
 * 
 */
trait DimTraits{
    /*
     * 提取用户的岗位列表
     */
    public static function dimItemIdsByTypeId($typeId){
        $con    = [];
        $con[]  = ['type_id','in',$typeId];
        return self::column('item_id',$con);
    }
    
    /*
     * 提取类型的列表
     */
    public static function dimListByTypeId($typeId){
        $con    = [];
        $con[]  = ['type_id','in',$typeId];
        return self::lists($con);
    }
}
