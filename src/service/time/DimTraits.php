<?php

namespace xjryanse\salary\service\time;

use xjryanse\logic\Arrays;
use xjryanse\logic\Datetime;
use Exception;

/**
 * 
 */
trait DimTraits{
    /**
     * 年份提取id
     */
    public static function dimTimeIdsByYear($year){
        $con = Datetime::yearTimeCon('start_time',$year);
        $ids = self::where($con)->column('id');
        return $ids;
    }
}
