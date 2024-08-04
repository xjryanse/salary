<?php

namespace xjryanse\salary\service\orderBaoBusDriver;

/**
 * 分页复用列表
 */
trait FieldTraits{
    /**
     *
     */
    public function fBelongTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 公司
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建时间
     */
    public function fCreateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建者，user表
     */
    public function fCreater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 有使用(0否,1是)
     */
    public function fHasUsed() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未删，1：已删）
     */
    public function fIsDelete() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未锁，1：已锁）
     */
    public function fIsLock() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 备注
     */
    public function fRemark() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 扣款合计
     */
    public function fSalaryCut() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 实发合计
     */
    public function fSalaryReal() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 应发合计
     */
    public function fSalaryTotal() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 薪资类型
     */
    public function fSalaryType() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 排序
     */
    public function fSort() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 状态(0禁用,1启用)
     */
    public function fStatus() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新时间
     */
    public function fUpdateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新者，user表
     */
    public function fUpdater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }
    
}