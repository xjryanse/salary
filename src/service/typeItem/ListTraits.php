<?php

namespace xjryanse\salary\service\typeItem;

use xjryanse\logic\ModelQueryCon;
use xjryanse\logic\Arrays;
/**
 * 
 */
trait ListTraits{
    /**
     * 20231002
     * 复现历史数据
     */
    public static function listForHis($param){
        $dqTime = Arrays::value($param, 'dqTime') ? : date('Y-m-d H:i:s');
        $con = [];
        return self::hisList($dqTime, $con);
    }
}
