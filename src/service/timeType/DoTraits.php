<?php

namespace xjryanse\salary\service\timeType;

use xjryanse\logic\Arrays;
use Exception;
/**
 * 
 */
trait DoTraits{
    /**
     * 锁定
     * @return type
     */
    public function doSetLock(){
        $data['time_lock'] = 1;
        return $this->updateRam($data);
    }
    /**
     * 解锁
     * @return type
     */
    public function doSetUnLock(){
        $data['time_lock'] = 0;
        return $this->updateRam($data);
    }
}
