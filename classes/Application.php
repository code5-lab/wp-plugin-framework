<?php
namespace Com\Componto\Framework;
use Illuminate\Database\Capsule\Manager as Capsule;



class Application
{
    protected static $instance;

    public function __construct()
    {
        global $wpdb;
        static::$instance = $this;

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            //'collation' => DB_COLLATE ?: $wpdb->collate,
            'prefix' => $wpdb->prefix
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }


    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

}