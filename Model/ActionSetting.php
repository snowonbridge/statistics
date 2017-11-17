<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-13
 * Time: 上午10:28
 */

namespace Workerman\Model;


use Workerman\Lib\Model;

class ActionSetting extends Model{

   public $table='action_setting';

    public function getName($no)
    {
        $r = $this->getRow($this->table,'action_no=:action_no',[":action_no"=>$no]);
        return isset($r['name'])?$r['name']:'';
    }
} 