<?php

namespace xjryanse\salary\service\item;

/**
 * 
 */
trait FieldTraits{
    public function fNeedDtl() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    public function fItemKey() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fItemName() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
    public function fPrizeGroupKey() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
}
