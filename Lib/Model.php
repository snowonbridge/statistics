<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-9
 * Time: 下午2:54
 */

namespace Workerman\Lib;

use \PDOException;
class Model {

    /**
     * 配置数据库
     * @var string
     */
    protected $db = 'point_statistics';
    protected $table='';
    public static  $instance;

    protected static $connections=array();
    protected $prefix;
    private $transactions = 0;
    public $db_configs;
    public function __construct($db='')
    {
        if($db)
        {
            $this->db= $db;
        }
        if(DEBUG)
        {
//            Logger::write('当前数据库配置文件为db_local','dbconfig');
            $this->db_configs  = require(CFG_PATH.'db_local.php');
        }else{
//            Logger::write('当前数据库配置文件为db_test','dbconfig');
            $this->db_configs  = require(CFG_PATH.'db_test.php');
        }


    }
    protected function connect($name='activity')
    {

        if(isset(self::$connections[$name]))
        {
            return self::$connections[$name];
        }


        $db_config = $this->db_configs[$name];

        $host = $db_config['host'];
        $port = $db_config['port'];
        $dbname = $db_config['dbname'];
        $user = $db_config['user'];
        $password = $db_config['password'];
        $charset = $db_config['charset'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
        $option = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_PERSISTENT => true,
        ];

        // 5.3.6 and before use option, other use dsn
        if (version_compare(PHP_VERSION, '5.3.6', '<')) {
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $charset;
            }
        } else {
            $dsn .= ';charset=' . $charset;
        }

        $db = new \PDO($dsn, $user, $password, $option);

        // fix for (version < 5.3.6) and PDO::MYSQL_ATTR_INIT_COMMAND is undefined
        if (version_compare(PHP_VERSION, '5.3.6', '<') && !defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $db->exec('SET NAMES ' . $charset);
        }
        self::$connections[$name] = $db;
        return $db;

    }
    public function rules()
    {
        return [];
    }

    public function validate($data,$rules=array())
    {

        if(!$rules)
            return true;
        foreach($rules as $k=>$item)
        {
            if((isset($item[0]) && $item[1] =='required') && !isset($data[$item[0]]))
            {

                return false;
            }
        }
        return true;
    }

    /**
     * @DESC 配置使用数据库名称
     * @param $name
     * @return \PDO
     */
    public function db($name)
    {
        if(!isset(self::$connections[$name]))
        {
            self::$connections[$name] = $this->connect($name);
        }

        return self::$connections[$name];
    }
    public function __destruct()
    {
        self::$connections = array();

    }
    /**
     * Get simple instance
     *
     * @return static
     */
    static function instance($db='')
    {
        if (!isset(self::$instance[$db.get_called_class()])) {
            self::$instance[$db.get_called_class()] = new static($db);
        }

        return self::$instance[$db.get_called_class()];
    }
    /**
     * @desc 插入一条数据
     * @param string $table
     * @param array $data
     * @return bool|string
     */
    private function insert($table,$data=array())
    {
        if(empty($table) || empty($data))
        {
            Logger::write('insert数据库 参数非法','model_insert','error');
            return false;
        }
        $cols = $vals='';
        $params=[];
        foreach($data as $key =>$val)
        {
            $cols .= ",`{$key}`";

            $vals .= ",:{$key}";
            $params[":{$key}"] = $val;
        }
        $cols = trim($cols,',');
        $vals = trim($vals,',');
        $sql = "insert into `{$table}`($cols) value($vals)";
        try{

            $pdo =  $this->db($this->db);
            $pdo_stat = $pdo->prepare($sql)->execute($params);

            return $pdo->lastInsertId();
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()."sql:$sql",'model_insert','error');
            return false;
        }
    }
    public  function getTables()
    {
        $sql    =  'SHOW TABLES ';
        try{
            $pdo =  $this->db($this->db);
            $pdo_stat = $pdo->prepare($sql);
             $pdo_stat->execute();
            $list = $pdo_stat->fetchAll();
            $result=[];
            foreach($list as $table)
            {
                $result[]=array_values($table)[0];
            }
            return $result;
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()." sql:$sql",'getTables','error');
            return false;
        }
    }
    private function counts($table='',$where='',$w_params=array())
    {
        if(empty($table))
        {
            Logger::write('counts 参数非法','counts','error');
            return false;
        }
        $where = !$where?1:$where;
        $sql = "select count(*) as counts from `{$table}` where {$where}";
        try{

            $pdo =  $this->db($this->db);
            $pdo_stat = $pdo->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;

           $res = $pdo_stat->fetch(\PDO::FETCH_ASSOC);
            return $res['counts'];
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()." sql:$sql",'getTables','error');
            return false;
        }
    }
    public  function createLikeTable($from_tb,$dst_tb)
    {
        if(empty($from_tb) || empty($dst_tb))
        {
            Logger::write('createTable 参数非法','createTable','error');
            return false;
        }
        $sql    =  "CREATE TABLE `{$dst_tb}` LIKE `{$from_tb}`";
        try{
            $pdo =  $this->db($this->db);
            $pdo_stat = $pdo->prepare($sql);
            $pdo_stat->execute();

            return true;
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()." 创建表失败 sql:$sql",'createLikeTable','error');
            return false;
        }
    }
    public function tableExist($table='')
    {
        $sql="show TABLES like '{$table}'";
        try{
             $pdo =  $this->db($this->db);
             $pdo_stat = $pdo->prepare($sql);
             $pdo_stat->execute();
             $flag = $pdo_stat->fetchColumn();
            return empty($flag)?false:true;
        }catch(PDOException $e)
        {
            Logger::write($e->getMessage()." 表查询失败 sql:$sql",'tableExist','error');
            return false;
        }


    }
    private function insertBatch($table='',$data=array())
    {
        if(empty($table) || empty($data))
        {
            Logger::write('insertBatch 参数非法','insertBatch','error');
            return false;
        }
        $cols=$val_str='';$params=[];
        foreach($data as $key1=>$item)
        {
            foreach($item as $k =>$v)
            {
                $cols .= ",`{$k}`";
            }
            $cols = trim($cols,',');
            break;
        }
        foreach($data as $key1=>$item)
        {
            $val_str .= ",(";
            $str='';
            foreach($item as $k =>$v)
            {
                $str .= ",:{$k}{$key1}";
                $params[":{$k}{$key1}"] = $v;
            }
            $val_str .= trim($str,',');
            $val_str .= ")";
        }
        $val_str = trim($val_str,',');
        $sql = "insert into `{$table}`($cols) values {$val_str}";
        try{
            $pdo =  $this->db($this->db);
            $pdo->prepare($sql)->execute($params);

            return $pdo->lastInsertId();
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()." sql:$sql",'model_insertBatch','error');
            return false;
        }
    }

    /**
     * @desc  获取一行 ok
     * @param string $table
     * @param string $where
     * @param array $w_params
     * @param string $columns
     * @return mixed
     */
    private  function getRow($table,$where='',$w_params=array(),$columns='*')
    {
        $sels = '';
        if(is_array($columns))
        {
            foreach($columns as $item)
            {
                $sels .= ",`{$item}`";
            }
            $sels = trim($sels,',');
        }
        if(!$sels)
        {
            $sels = "*";
        }
        $where = !$where?1:$where;
        $sql = "select {$sels} from `{$table}` where {$where}  limit 1";
        try{
            $pdo_stat = $this->db($this->db)->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;
            return $pdo_stat->fetch(\PDO::FETCH_ASSOC);
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage().'sql:'.$sql,'getRow','error');
            return false;
        }
    }
    public function execRawSql($sql)
    {
        try{
            $pdo = $this->db($this->db);
            $pdo->exec($sql);
            return true;
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage().'sql:'.$sql,'execRawSql','error');
            return false;
        }

    }

    /**
     * @desc 获取所有数据
     * @param string $table
     * @param string $where
     * @param array $w_params
     * @param string $columns
     * @return bool
     */
    private function getRows($table,$where='',$w_params=array(),$columns='*')
    {
        if(!$table)
        {
            Logger::write('getRows 参数非法','getRows','error');
            return false;
        }
        $sels = '';
        if(is_array($columns))
        {
            foreach($columns as $item)
            {
                $sels .= ",`{$item}`";
            }
            $sels = trim($sels,',');
        }else{
            $sels = $columns;
        }
        $sels = !$sels?"*":$sels;
        $where = !$where?"1=1":$where;
        $sql = "select {$sels} from `{$table}` where {$where} ";
        try{

            $pdo_stat = $this->db($this->db)->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;

            return $pdo_stat->fetchAll(\PDO::FETCH_ASSOC);
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()."sql:$sql",'getRows','error');
            return false;
        }
    }

    /**
     * @desc 更新数据
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $w_params
     * @return bool
     */
    private   function update($table,$data,$where='',$w_params=array())
    {
        if(!$table || !$data || !$where)
        {
            Logger::write('update 参数非法','update','error');
            return false;
        }
        $vals='';
        foreach($data as  $key=>$val)
        {
            if(0 !== strpos($val,':'))
            {
                $val = "'{$val}'";
            }
            $vals .= ",`{$key}`={$val}";
        }
        $vals = trim($vals,',');
        $sql = "update `{$table}` set $vals where {$where}";
        try{

            $pdo_stat = $this->db($this->db)->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;
            return $pdo_stat->rowCount();
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()."sql:$sql",'update','error');
            return false;
        }
    }

    /**
     * @DESC 删除操作
     * @param string $table
     * @param string $where
     * @return bool
     */
    private function delete($table,$where='',$w_params=array())
    {
        if(!$table || !$where)
        {
            Logger::write('delete 参数非法','delete','error');
            return false;
        }

        $sql = "delete from `{$table}` where {$where}";
        try{
            $pdo_stat = $this->db($this->db)->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;
            return $pdo_stat->rowCount();
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()."sql:$sql",'delete','error');
            return false;
        }
    }
    private  function count($table,$where='',$w_params=array())
    {
        if(!$table)
        {
            Logger::write('count 参数非法','count','error');
            return false;
        }
        if(!$where)
        {
            $where=1;
        }
        $sql = "select count(*) as c from `{$table}` where {$where}";
        try{
            $pdo_stat = $this->db($this->db)->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;
            $fr = $pdo_stat->fetch();

            return $fr['c'];
        }catch (PDOException $e)
        {
            Logger::write($e->getMessage()."sql:$sql",'count','error');
            return false;
        }
    }
    private function incr($table,$where,$w_params,$field,$num=1)
    {
        $sql="update `{$table}` set `{$field}` = `{$field}` +{$num} where {$where}";
        try{
            $pdo_stat = $this->db($this->db)->prepare($sql);
            empty($w_params) &&  $pdo_stat->execute() ||$pdo_stat->execute($w_params)  ;
            return $pdo_stat->rowCount();
        }catch (\PDOException $e)
        {
            Logger::write($e->getMessage()."sql:$sql",'incr','error');
            return false;
        }
    }

    /**
     * 事物开始
     */
    public function start()
    {
        ++$this->transactions;

        if ($this->transactions == 1)

        {

            return $this->db($this->db)->beginTransaction();

        }

    }
    /**
     * 事物提交
     */
    private  function commit()
    {

        if ($this->transactions == 1)
        {
            $this->transactions = 0;
            return  $this->db($this->db)->commit();
        }else{

            --$this->transactions;
        }

    }
    /**
     * 事物回滚
     */
    private function rollBack()
    {
        if ($this->transactions == 1)
        {
            $this->transactions = 0;
            return $this->db($this->db)->rollBack();
        }else{
            --$this->transactions;
        }

    }
    public function __call($name, array $arguments)
    {

        $result = false;
        $i=3;

        while($i--)
        {
            $result = call_user_func_array([$this,'invoke'], [$name,$arguments]);

            if($result !== false)
                break;
            self::$connections[$this->db] = null;

        }


        return $result;
    }

    public function getAll()
    {
        $r = $this->getRows($this->table);
        return $r;
    }
    public function invoke($name,$arguments)
    {
        if(!isset( self::$instance[__CLASS__]))
        {
            self::$instance[__CLASS__] = new self();

        }

        $mod = self::$instance[__CLASS__];
        $class = get_class($mod);

        $refC = new \ReflectionClass($class);


        $db = $refC->getProperty('db') ;
        if($db  && ($db->isPrivate() || $db->isProtected()))
        {
            $db->setAccessible(true);
            $db->setValue($mod,$this->db);
        }else{
            $refC->db = $this->db;
        }
        $table = ($refC->getProperty('table') );
        if($db  && ($db->isPrivate() || $db->isProtected()))
        {
            $table->setAccessible(true);
            $table->setValue($mod,$this->table);
        }else{
            $refC->table = $this->table;
        }
        $c_name = $refC->getMethod($name);
        $c_name->setAccessible(true);

        $result = $c_name->invokeArgs($mod,$arguments);
        return $result;
    }
    public static $selfIns;


} 