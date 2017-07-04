<?php
/**
 * Created by PhpStorm.
 * User: rxlwin
 * Date: 2017-6-29
 * Time: 22:21
 */

namespace myself\model;
use PDOException;
use PDO;

class Base
{
    private static $pdo=null;
    private $table;
    private $where = "";

    public function __construct($config,$table)
    {
        $this->connect($config);
        $this->table=$table;//设置表格
    }

    private function connect($config){
        if(!is_null(self::$pdo)) return;
        try{
            $dsn="mysql:host=".$config["dbhost"].";dbname=".$config["dbname"];
            $user=$config["dbuser"];
            $pass=$config["dbpass"];
            $pdo=new PDO($dsn,$user,$pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES ".$config['dbcharset']);
            self::$pdo=$pdo;
        }catch (PDOException $e){
            exit($e->getMessage());
        }
    }

    public function q($sql){
        try{
            $res=self::$pdo->query($sql);
            $data=$res->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        }catch (PDOException $e){
            exit($e->getMessage());
        }
    }

    public function e($sql){
        try{
            return self::$pdo->exec($sql);
        }catch (PDOException $e){
            exit($e->getMessage());
        }
    }

    public function __call($name, $arguments)
    {
        p("这里是数库库类,貌似您没有找到正确的路");exit;
    }

    private function getPrikey(){
        $sql="DESC {$this->table}";
        $data=$this->q($sql);
        $prikey="";
        foreach ($data as $v){
            if($v['Key']="PRI"){
                $prikey=$v['Field'];
                break;
            }
        }
        return $prikey;
    }

    /**
     * @param null $where
     * 这个where 方法 传参不能用数组,或者说到目前为止,我还没有找到很方便的用数组能解决问题的方法.
     * 因为在条件里,涉及逻辑操作太多,有and,有or 有 = ,也有like ,关键字一多起来,设置就会很复杂.
     * 所以直接传参可能更方便一些.
     */
    public function where($where=null){
        if(is_null($where)) return;
        $this->where=" WHERE ".$where;
        return $this;
    }


//==================================================
//==================================================
//==================================================

    /**
     * 检索数据
     * @param string $feild
     * @return mixed
     */
    public function get($feild="*"){
        $sql="SELECT {$feild} FROM {$this->table} {$this->where}";
        return $this->q($sql);
    }

    /**
     * 获取特定的一条数据
     * @param $pri
     * @return mixed
     */
    public function find($pri){
        $prikey=$this->getPrikey();
        $sql="SELECT * FROM {$this->table} WHERE {$prikey}=".intval($pri);
        $data=$this->q($sql);
        return current($data);
    }

    /**
     * 统计数量
     * @return mixed
     */
    public function count(){
        $sql="SELECT count(*) as c FROM {$this->table} {$this->where}";
        $data=$this->q($sql);
        return $data[0]["c"];
    }

    /**
     * 保存数据 可以修改 也可以添加
     * @param $data
     * @return mixed|void
     */
    public function save($data){
        if (!is_array($data)) return;
        $prikey=$this->getPrikey();
        //组合保存的内容
        $str="";
        foreach ($data as $k=>$v){
            //如果有主键就保存到where中去
            if($k==$prikey){
                $this->where("{$k}={$v}");
            }else{
                $str.="{$k} = '{$v}',";
            }
        }
        $str=rtrim($str,",");

        //如果参数中有主键传来,或者where中没有数据,就是添加
        if(empty($this->where)){
            $sql="INSERT INTO {$this->table} SET {$str}";
        }else{
            //否则就是修改
            $sql="UPDATE {$this->table} SET {$str} {$this->where}";
        }
        return $this->e($sql);
    }

    /**
     * 删除数据
     * @return bool|mixed
     */
    public function delete(){
        if(empty($this->where)){
            //如果不带条件就不能删除
            return false;
        }
        $sql="DELETE FROM {$this->table} {$this->where}";
        return $this->e($sql);
    }
}