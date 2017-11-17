<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-11
 * Time: 上午11:19
 */

namespace Workerman\Lib;


use Workerman\Protocols\Http;

class Controller {

    public $twig;
    public $post;
    public $get;
    public static $instance;
    public function __construct()
    {
        foreach($_POST as $k=>$v)
        {
            $this->post[$k] = ($v);
        }
        foreach($_GET as $k=>$v)
        {
            $this->get[$k] = ($v);
        }
        $loader = new \Twig_Loader_Filesystem(VIEW_PATH);
        $this->twig = new \Twig_Environment($loader, array(
            /* 'cache' => './compilation_cache', */
        ));

    }
    public function render($tpl,$args=[])
    {
        return $this->twig->render($tpl,$args);
    }
    /**
     * Get simple instance
     *@return static
     */
    public static function instance()
    {
        if (!isset(self::$instance[get_called_class()])) {
            self::$instance[get_called_class()] = new static();
        }

        return self::$instance[get_called_class()];
    }
}