<?php

namespace Com\Componto\Framework\Core\Render;


class Render
{

    protected $rnd;

    public function __construct($context, $name, array $data = [], $asString = false)
    {
        $this->context = $context;
        $this->rnd = $this->render($name, $data, $asString);
    }

    public static function template($context, $name, array $data = [], $asString = false)
    {
        $render = new Render($context, $name, $data, $asString);
        return $render->rnd;
    }

    public static function json($value, $code = 200, $setHeader = true)
    {
        http_response_code($code);
        if ($setHeader) {
            header('Content-Type:application/json');
        }
        die(json_encode($value));
    }

    public function render($name, array $data = [], $asString = false)
    {
        extract($_REQUEST, EXTR_PREFIX_SAME, 'request_');
        extract($data);
        if ($asString) ob_start();

        include WP_PLUGIN_DIR . '/' . $this->context . '/app/resources/views/' . $name . '.php';

        if ($asString) return ob_get_clean();
    }

    private function renderJson($key, $value)
    {
        $encoded = json_encode($value);
        echo "<script>window.$key=$encoded;</script>";

    }
}