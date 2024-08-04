<?php

namespace xjryanse\salary\service\type;

/**
 * 
 */
trait FieldTraits{
    
    public function fRoleKey() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fTypeName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
