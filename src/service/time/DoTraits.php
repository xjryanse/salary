<?php

namespace xjryanse\salary\service\time;

use xjryanse\logic\Arrays;
use Exception;
/**
 * 
 */
trait DoTraits{
    /**
     * 计薪周期初始化
     */
    public static function doInit(){
        $last = self::where()->order('start_time desc')->find();
        $yearMonth = Arrays::value($last, 'start_time')
                ? date('Y-m',strtotime($last['start_time']))
                : date('Y-m',strtotime('-120 month'));
        if($yearMonth >= date('Y-m')){
            throw new Exception('已是最新计薪周期'.$yearMonth);
        }
        // 最后一天，再加一天,作为下一时段
        $newYearMonth = date('Y-m-d',strtotime($last['end_time']) + 86400);
        // 初始化
        return self::initTimeGetId($newYearMonth);
    }
}
