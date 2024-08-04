<?php

namespace xjryanse\salary\service\time;

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
    
    public function fName() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fStartTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fLockUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }
}
