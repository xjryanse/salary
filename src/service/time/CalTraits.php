<?php

namespace xjryanse\salary\service\time;

/**
 * 
 */
trait CalTraits{

    /**
     * 计算前一个计薪周期id
     * 用于本月复制上月数据时进行定位
     */
    public function calPreTimeId(){
        $info = $this->get();
        $con    = [];
        $con[]  = ['start_time','<',$info['start_time']];

        return self::where($con)->order('start_time desc')->limit(1)->value('id');
    }

}
