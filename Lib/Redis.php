<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-9
 * Time: 下午2:54
 */

namespace Workerman\Lib;
class Redis{

    public  $oRedis = null; //连接对象
    public $aServer = array(); //地址配置
    public $persist = false; //是否长连接(当前不支持长连接)
    private  $connect = false; //是否连接上
    private $connected = false; //是否已经连接过
    public static  $connections = array();//连接实例
    private  function __construct($name) {
        if (!class_exists('Redis')) { //强制使用
            throw new \RuntimeException('This Lib Requires The Redis Extention!');
            return false;
        }

        self::$currentName = $name;

    }


    /**
     * 设置
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    private function set($key, $value, $serialize = false,$zip = false) {

        ($serialize == true) && ($value = serialize($value));
        ($zip && function_exists('gzcompress')) && ($value = gzcompress($value));
        return $this->oRedis->set($key, $value); //Bool TRUE if the command is successful.
    }

    /**
     * 设置带过期时间的值(暂不支持)
     * @param String $key
     * @param Mixed $value
     * @param int $expire 过期时间.默认24小时
     * @return Boolean
     */
    private function setex($key, $value, $expire = 86400, $zip = false) {


        $value = ($zip && function_exists('gzcompress')) ? gzcompress(serialize($value)) : serialize($value);
        return $this->oRedis->setex($key, $expire, $value); //Bool TRUE if the command is successful.
    }

    /**
     * 设置带过期时间的值
     * 没有序列化的 setex
     * @param String $key
     * @param Mixed $value
     * @param int $expire 过期时间.默认24小时
     * @return Boolean
     */
    private function nsetex($key, $value, $expire = 86400, $zip = false, $serialize = false) {


        ($serialize === true) && $value = serialize($value);
        (($zip === true) && function_exists('gzcompress')) && $value = gzcompress($value);
        return $this->oRedis->setex($key, $expire, $value); //Bool TRUE if the command is successful.
    }

    /**
     * 添加.存在该Key则返回false.
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    private function setnx($key, $value, $zip = false) {


        $value = ($zip && function_exists('gzcompress')) ? gzcompress(serialize($value)) : serialize($value);
        return $this->oRedis->setnx($key, $value);
    }


    /**
     * 原子递加.不存在该key则基数为0,注意$value为 max(1, $value)
     * @param String $key
     * @param int $value
     * @return false/int 返回最新的值
     */
    private function incr($key, $value = 1) {

        return $this->oRedis->incr($key, $value);
    }

    /**
     * use
     * 原子递加指定的整数.不存在该key则基数为0,注意$value可以为负数.返回的结果也可能是负数
     * !!!如果超过42亿,请用incrByFloat
     * @param String $key
     * @param int $value 可以为0
     * @return false/int 返回最新的值
     */
    private function incrBy($key, $value){

        return $this->oRedis->incrBy($key, (int)$value);
    }

    /**
     * use
     * 原子递减.不存在该key则基数为0,注意$value为 max(1, $value).可以减成负数
     * @param String $key
     * @param int $value
     * @return false/int 返回最新的值
     */
    private function decr($key, $value = 1) {

        return $this->oRedis->decr($key, $value);
    }

    /**
     * use
     * 获取
     * @param String $key
     * @param Boolean $zip 存入时是否采取了压缩
     * @param Boolean $serial 存入时是否序列化了
     * @return false/Mixed
     */
    private function get($key, $serial = false,$zip = false) {

        $result = $this->oRedis->get($key); //String or Bool: If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned.

        return $result === false ? $result : (($zip && function_exists('gzuncompress')) ? unserialize(gzuncompress($result)) : ($serial ? unserialize($result) : $result));

    }


    /**
     * use
     * 设置某个key过期时间.只能设置一次
     * @param String $key
     * @param int $expire 过期秒数
     * @return Boolean
     */
    private function setTimeout($key, $expire) {

        return $this->oRedis->setTimeout($key, $expire);
    }




    /**
     * use
     * 从hash中获取指定key的值 use
     * @param string $hash hash的名
     * @param string $hkey 要获取的hkey
     * @return 成功为value失败则false
     */
    private function hGet($hash, $hkey) {

        $hval = $this->oRedis->hGet($hash, $hkey);
        return $hval;
    }


    /**
     * use
     * 获得一个hash的所有键值对 use
     * @param string $hash hash的名
     * @return array
     */
    private function hGetAll($hash) {

        $flag = $this->oRedis->hGetAll($hash);
        return $flag;
    }
    private  function blPop($key)
    {

        $r = $this->oRedis->blPop($key,15);
        if(!$r)
            return false;
        if(is_string($r[1]))
        {
            $r =  unserialize($r[1]);
            return $r;
        }

      return  false;
    }

    /**
     * use
     * 把元素加入到队列右边(尾部) use
     * @param String $key
     * @param Mixed $value
     * @return Boolean
     */
    private function rPush($key, $value, $zip = false, $serial = true) {

        $value = $serial ? serialize($value) : $value;
        $value = ($zip && function_exists('gzcompress')) ? gzcompress($value) : $value;
        return $this->oRedis->rPush($key, $value);
    }
    // use
    private function hExists($hash,$key)
    {
        return $this->oRedis->hExists($hash,$key);
    }

    /**
     * use
     * hash中的hkey的自增或自减,如果hash中的hkey不存在,则默认起始值为0
     * @param string $hash hash的名
     * @param string $hkey 要获取的hkey
     * @param int $hincr 要自增长的幅度,默认是加1,负数则是自减
     * @return int 自增或自减后的新值
     */
    private function hIncrBy($hash, $hkey, $hincr = 1) {

        $flag = $this->oRedis->hIncrBy($hash, $hkey, $hincr);
        return $flag;
    }





    /**
     * use
     * 设置数据到一个hash中去,一次设置单个hash中的多个key的值,注意可以一次操作一个hash的多个健值,但不能一次操作多个hash的健值
     * @param string $hash hash的名
     * @param array $aMset 要设置到hash中去的键值对
     * @return bool
     */
    private function hMSet($hash, $aMset) {


        $flag = $this->oRedis->hMSet($hash, $aMset);
        return $flag;
    }

    /**
     * use
     * 删除某key/某些key,其实往redis里面删除也可以传多个字符串参数,
     * 这里简化处理,多个删除统一使用数组来传进来.
     * @param String /Array $keys
     * @return int 被删的个数
     */
    private function delete($keys) {
        return $this->oRedis->delete($keys);
    }



    /**
     * use
     * 密码验证.密码明文传输
     * @param String $password
     * @return Boolean
     */
    private function auth($password) {
        return $this->oRedis->auth($password);
    }



    /**
     * use
     * 关闭连接
     */
    private   function close() {
        $this->oRedis->close();
        self::$connections[self::$currentName] = null;


    }
    private function lGet($key,$index=0)
    {
        $r = $this->oRedis->lGet($key,$index);
        if(!$r)
            return false;
        if(is_string($r))
        {
            $r =  unserialize($r);
            return $r;
        }

        return  false;
    }

    public function __call($name,  $arguments)
    {

        $result = false;
        $i=3;
        while($i--)
        {
            $this->connectR();
            if(!$this->oRedis)
            {
                $this->close();
                continue;
            }

            try{

                if(method_exists($this,$name))
                {

                    $result = call_user_func_array([$this,$name], $arguments);
                }else{
                    $result = call_user_func_array([$this->oRedis,$name], $arguments);
                }


                return $result;
            }catch (\Exception $e)
            {

                Logger::write($e->getMessage(),__METHOD__);

            }
            $this->close();

        }
        return $result;
    }

    /**
     * 连接.每个实例仅连接一次
     * @return Boolean
     */
    public  function connectR()
    {

        $configs = require(CFG_PATH.'redis.php');
//
        if( isset( self::$connections[self::$currentName]))
            return  self::$connections[self::$currentName];
//

        $host = $configs[self::$currentName]['host'];
        $port = $configs[self::$currentName]['port'];
        $auth = isset($configs[self::$currentName]['auth'])?$configs[self::$currentName]['auth']:'';

        try{
            $redis = new \Redis();
            $ret = $redis->connect($host,$port);
            if (!empty($auth)) {
                $redis->auth($auth);
            }

            $this->oRedis = $redis;

            self::$connections[self::$currentName] = $redis;
            return $redis;
        }catch (\RedisException $e)
        {
            Logger::write($e->getMessage(),'Redis Connect');
            return false;
        }


    }

    /**
     * @desc 获取redis实例
     * @param string $name
     * @return Redis
     * @throws \BadMethodCallException
     */
    public static function getIns($name='user')
    {


        if(isset(self::$ins))
            return self::$ins;
        self::$ins = new Redis($name);
        return  self::$ins;
    }


    public static $currentName;
    public static $ins=null;

}