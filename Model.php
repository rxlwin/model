<?php
namespace rxlwin\model;//1.声明类的命名空间 2.由composer规范,以实现自动加载,自由调用
/**
 * Class Model 基本模型类,所有应用模型类都要继承此类
 * 本类抽象出了所有应用模型类都会需要的方法,以实现代码公用
 */
class Model{
    private static $config=[];
    /**
     * 拦截器 __call 当外部实例化应用模型类,并调用一个本类和基类都不存在的方法时,触发本方法
     * @param $name 外部调用的没有找到的方法名
     * @param $arguments 调用方法时所带的参数
     * @return mixed 这此我们直接返回了调用Mysqlaction方法所返回的内容
     *
     * 使用本方法的用意:
     * 我们终极目的是为了调用mysql类中的$name方法,但是调用方法比较繁琐,举例如下
     * $pdo = new Mysql();
     * $pdo -> q("select * from table");
     * 在以上代码中,我们实例化了mysql类,并调用了q方法,还写了一段SQL代码,以后代码量大了,出错的可能性会增加,调试也会降低效率.所以我们抽象出固定的代码,将其封装,直接调用即可.这些代码是
     * $pdo = new Mysql();
     * $pdo -> q("select * from XXXX");
     * 我们发现只有XXXX的地方是变化的,这个XXXX实际上就是数据表名,那我们就能通过将类名与数据表名一致,从而将XXXX拿出来.所以我们建立了XXXX类,再通过魔术方法来实现了通过类名获取表名.
     * 我们再在Mysql类中建立get方法,将q("select * from XXXX")封装起来,直接调用.
     * 这样我们将应用中的调用代码简化到了极致.简化后的代码变成了:
     * (new XXXX)->get();
     * 想出这个办法的人,简直就是神一样的存在.
     */
    public function __call($name, $arguments)
    {
        //1.静态调用mysqlaction方法并返回 2.因为这里有代码重用,所以我们又封装了一个方法用来调用
        return self::modelrun($name, $arguments);
    }

    /**
     * 静态方法拦截器 __callStatic 当外部静态调用应用模型类中一个本类和基类都不存在的方法中,触发本方法
     * @param $name 外部调用的没有找到的方法名
     * @param $arguments 调用方法时所带的参数
     * @return mixed 这此我们直接返回了调用Mysqlaction方法所返回的内容
     *
     * 使用本方法的用意:
     * 在应用中除了上面的正常实例化后再调用的办法,我们还可以更简化,同时也实现了方法的两种方式调用
     * 思想与上面常规方法是一致的,只是静态调用代码更简单
     * 例如上面的例子中我们代码可以再简化成如下代码:
     * XXXX::get();
     * 当应用中这个调用get()方法时,就会触发本方法,通过本方法再实例化mysql类,再调用mysql中的get方法
     * 这次是真的到极致了.以我的脑子,再想不出还能怎么简化了.再次感叹神一般的大牛存在.
     */
    public static function __callStatic($name, $arguments)
    {
        //1.静态调用mysqlaction方法并返回 2.因为这里有代码重用,所以我们又封装了一个方法用来调用
        return self::modelrun($name, $arguments);
    }

    /**
     * 封装的用来实例化Mysql类并调用其方法的方法
     * @param $name 外部调用的没有找到的方法名
     * @param $arguments 调用方法时所带的参数
     * @return mixed 调用mysql类下$name方法时的返回值,我们原封不动的全部返回去
     */
    private static function modelrun($name, $arguments){
        //1.得到带命名空间的类名,分割成数组 2.现在的类名是代命名空间的完整类名,所以需要先处理一下.
        $table=explode('\\',get_called_class());
        //1.通过get_called_class获得当前声明及调用$name的类名,即应用类名 2.这里必须使用这个函数来获取类名,因为我们在应用中实例化的是XXXX类,而XXXX类又继承了Model类,是Model的子类,在Model类中使用__CLASS__得到的将是Model类的类名,这个类名我们没用,我们就需要XXXX这个子类名字,与__CLASS__相类似的函数是get_class.因为我们要获得的XXXX就是数据表的名字,这是我们正需要的.所以这里要用get_called_class来获得,这是phpr内置函数,需要PHP>=5.3.0才能支持使用
        //因为数据表的表名我们都是小写的,所以这里给得到的类名转小写
        $table=strtolower($table[2]);
        //1.实例化Mysql并将表名传给构造函数. 2.这里我们只传进去了,表名,但是还有一个重要的条件就是idname,就是表中的主键和自增的那个字段名叫什么,我们没有传进去,在实例化的时候传进去,通过mysql的构造函数,如果该表有自增加ID字段,我们能得到并给相应的属性赋值
        return call_user_func_array([new Base(self::$config,$table),$name],$arguments);
    }

    /**
     * 获取数据库配置项
     * @param $config
     */
    public static function setconfig($config){
        //1.把传进来的配置项赋值给本类的静态属性 2.数据库配置项在加载时会执行本方法,这样我们就获得了数据库的配置项,以后在调用Base类时会把本配置项再传给Base类.
        self::$config=$config;
    }
}