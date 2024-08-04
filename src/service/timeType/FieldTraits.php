<?php

namespace xjryanse\salary\service\timeType;

/**
 * 分页复用列表
 */
trait FieldTraits{

    /**
     * 公司
     */
    public function fTimeLock() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    

}
