<?php

namespace xjryanse\salary\service\typeTplItem;

use xjryanse\logic\DataCheck;
use xjryanse\logic\DataDeal;
use xjryanse\logic\Arrays;

/**
 * 
 */
trait DoTraits{
    /**
     * 20240531
     * @param type $data
     * @return type
     */
    public static function doTypeUpdateRam($data){
        $keys   = ['tpl_id','item_id'];
        DataCheck::must($data, $keys);

        // $keysD  = array_merge($keys,['dept_id']);
        $sData  = Arrays::getByKeys($data, $keys);
        $id     = self::commGetIdEG($sData);
                
        // $upData['plan_tang_count'] = Arrays::value($data, 'plan_tang_count');
        // 20240512:可多维更新
        $updKeys    = ['default_salary'];
        $upData     = Arrays::getByKeys($data, $updKeys);

        return self::getInstance($id)->updateRam($upData);
    }
}
