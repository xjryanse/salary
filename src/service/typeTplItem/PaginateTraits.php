<?php

namespace xjryanse\salary\service\typeTplItem;

use think\facade\Request;
use xjryanse\logic\DataCheck;
use xjryanse\logic\DataList;
use xjryanse\salary\service\SalaryTypeTplService;
use xjryanse\salary\service\SalaryTypeItemService;
use xjryanse\salary\service\SalaryItemService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Debug;

/**
 * 
 */
trait PaginateTraits{
    
    public static function paginateForTplHandle($con){
        $qData  = Request::param('table_data');
        DataCheck::must($qData, ['type_id']);
        $typeId  = Arrays::value($qData, 'type_id');
        $editable = Arrays::value($qData, 'editable');

        $conn   = [['type_id','=',$typeId]];
        $tpls   = SalaryTypeTplService::staticConList($conn);
        $obj    = Arrays2d::fieldSetKey($tpls, 'id');

        $tplLists = SalaryTypeTplService::staticConList($conn,'','sort');
        // Debug::dump($tplLists);
        $colAll = [];
        foreach($tplLists as $it){
            $colAll[] = ['id'=>$it['id'],'tpl_id'=>$it['id'],'sort'=>$it['sort']];
        }
        $tplIds = SalaryTypeTplService::staticConIds($conn);

        
        $cone   = [['tpl_id','in',$tplIds]];
        $lists  = self::where($cone)->select();
        $listsArr = $lists ? $lists->toArray() : [];
        // 列数据拼接成行表格
        $colField   = 'tpl_id';
        $rowField   = 'item_id';
        $valField   = 'default_salary';
        $isSum      = true;
        $res        = DataList::toLinelyData($listsArr, $colField, $rowField, $valField, $colAll,$isSum);
        foreach($res['data'] as &$v){
            $oItem              = Arrays::value($obj, $v['tpl_id']);
            $v['base_salary']       = floatval(Arrays::value($oItem, 'base_salary'));
            $v['per_day_tang']      = floatval(Arrays::value($oItem, 'per_day_tang'));
            $v['per_tang_salary']   = floatval(Arrays::value($oItem, 'per_tang_salary'));
            // 请假扣法
            $v['calc_cate']         = Arrays::value($oItem, 'calc_cate');
        }
        
        $res['fdynFields']      = self::fDynFieldsForTypeHandle($typeId, $editable);

        return $res;
    }
    
    public static function fDynFieldsForTypeHandle($typeId, $editable){
        $editable = 1;
        $con    = [];
        $con[]  = ['type_id','=',$typeId];
        $lists = SalaryTypeItemService::staticConList($con);

        $itemList   = SalaryItemService::staticConList();
        $itemObj    = array_column($itemList, 'item_name', 'id');

        $arr    = [];
        $arr[] = ['id'=>self::mainModel()->newId(),'name'=>'base_salary'
            ,'label'=>'固定工资(算脱班扣款)'
            ,'type'=> $editable ? 'listedit' : 'text'
            ,'option' =>[]
            ,'update_url'=>'/admin/salary/ajaxOperateInst?admKey=typeTpl&doMethod=updateRam'
            ,'update_param'=>[
                'id'                =>  'tpl_id'
                ,'base_salary'     =>  'base_salary'
            ]
            ,'width'=>80
        ];
        $arr[] = ['id'=>self::mainModel()->newId(),'name'=>'per_day_tang'
            ,'label'=>'日均单数'
            ,'type'=> $editable ? 'listedit' : 'text'
            ,'option' =>[]
            ,'update_url'=>'/admin/salary/ajaxOperateInst?admKey=typeTpl&doMethod=updateRam'
            ,'update_param'=>[
                'id'                =>  'tpl_id'
                ,'per_day_tang'     =>  'per_day_tang'
            ]
            ,'width'=>80
        ];
        
        foreach($lists as $v){
            $arr[] = ['id'=>self::mainModel()->newId(),'name'=>'l'.$v['item_id']
                ,'label'=>Arrays::value($itemObj, $v['item_id'])
                ,'type'=> $editable && $v['is_base'] ? 'listedit' : 'text'
                ,'option' =>[]
                ,'update_url'=>'/admin/salary/ajaxOperateFullP?admKey=typeTplItem&doMethod=doTypeUpdateRam'
                ,'update_param'=>[
                    'tpl_id'            =>  'tpl_id'
                    ,'item_id'          =>  $v['item_id']
                    ,'default_salary'   =>  'l'.$v['item_id']
                ]
                ,'width'=>80
            ];
        }
        return $arr;
    }
}
