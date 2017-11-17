<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-13
 * Time: 上午10:28
 */

namespace Workerman\Model;


use Workerman\Lib\Model;

class PageSetting extends Model{

   public $table='page_setting';

    public function getName($no)
    {
        $r = $this->getRow($this->table,'page_no=:page_no',[":page_no"=>$no]);
        return isset($r['name'])?$r['name']:'';
    }
} 