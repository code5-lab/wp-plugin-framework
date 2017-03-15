<?php
namespace Com\CodeFive\Framework;

use Com\CodeFive\Framework\Core\Hooks\ApiEndpoint;
use Com\CodeFive\Framework\Core\Hooks\Enqueue;
use Com\CodeFive\Framework\Core\Hooks\Panel;
use Com\CodeFive\Framework\Core\Hooks\ShortCode;
use Illuminate\Database\Capsule\Manager as Capsule;


class Application
{
    protected static $instance;
    protected $hooks;

    public function __construct()
    {
        $this->hooks = [
            'short_code' => new ShortCode(),
            'enqueue' => new Enqueue(),
            'panel' => new Panel(),
            'api_endpoint' => new ApiEndpoint()
        ];
        static::$instance = $this;
        $this->bootEloquent();
    }

    private function bootEloquent()
    {
        global $wpdb;

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'collation' => 'utf8_general_ci',//DB_COLLATE ?: $wpdb->collate,
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

    public function bootContext($context)
    {
        register_activation_hook($context . '/' . $context . '.php', function () use ($context) {
            @require WP_PLUGIN_DIR . '/' . $context . '/app/lifecycle/activate.php';
        });

        register_deactivation_hook($context . '/' . $context . '.php', function () use ($context) {
            @require WP_PLUGIN_DIR . '/' . $context . '/app/lifecycle/deactivate.php';
        });

        foreach ($this->hooks as $hook => $handler) {
            $hooks = @include WP_PLUGIN_DIR . '/' . $context . '/app/hooks/' . $hook . '.php';
            if (!empty($hooks)) {
                foreach ($hooks as $h) {
                    $handler::create($h, $context);
                }
            }
        }
    }
}