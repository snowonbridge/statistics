<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-13
 * Time: 上午10:28
 */

namespace Workerman\Model;


use Workerman\Lib\Model;

class PointSetting extends Model{

   public $table='point_setting';
    public function getName($no)
    {
        $r = $this->getRow($this->table,'point_no=:point_no',[":point_no"=>$no]);
        return isset($r['name'])?$r['name']:'';
    }
} 