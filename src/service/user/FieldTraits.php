<?php

namespace xjryanse\salary\service\user;

use xjryanse\salary\service\SalaryUserItemService;
/**
 * 
 */
trait FieldTraits{
    
    public function fTimeId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fSalaryTypeId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
