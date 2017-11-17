<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-11
 * Time: 下午3:08
 */

namespace Workerman\Controller;



use Workerman\Lib\Controller;
use Workerman\Lib\Model;
use Workerman\Lib\Redis;

use Workerman\Protocols\Http;

class Index extends Controller{

    public function main()
    {
       $users = require CFG_PATH."/user.php";
        if(false !== Redis::getIns()->get($_COOKIE['username']))
        {
            $this->post['username'] =$_COOKIE['username'];
            $this->post['password'] = Redis::getIns()->get($_COOKIE['username']);
        }
        foreach($users as $user)
        {
            if($this->post['username']== $user['user'] && $user['password'] == $this->post['password'])
            {
                Redis::getIns()->set($user['user'],$user['password']);
            }
        }
        if(false === Redis::getIns()->get($this->post['username']))
        {
            Http::header("Location:/index/index");
        }
        Http::setcookie('username',$this->post['username']);
        return $this->render("Index/index.html");
    }
    public function index()
    {

        if(false !== Redis::getIns()->get($_COOKIE['username']))
        {
            Http::header("Location:/index/main");
//            return $this->render("Index/index.html");
        }
        return $this->render("Index/login.html");
    }
    public function login()
    {
        $tables =  Model::instance()->getTables();
        return $tables;
    }



} 