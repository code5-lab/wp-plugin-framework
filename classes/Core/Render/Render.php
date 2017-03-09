<?php

namespace Com\Componto\Framework\Core\Render;


class Render
{

    protected $rnd;

    public function __construct($context, $name, array $data, $asString = false)
    {
        $this->context = $context;
        $this->rnd = $this->render($name, $data, $asString);
    }

    public static function template($context, $name, array $data, $asString = false)
    {
        $render = new Render($context, $name, $data, $asString);
        return $render->rnd;
    }

    public function render($name, array $data, $asString = false)
    {
        extract($_REQUEST, EXTR_PREFIX_SAME, 'request_');
        extract($data);
        if ($asString) ob_start();

        include WP_PLUGIN_DIR . '/' . $this->context . '/app/resources/views/' . $name . '.php';

        if ($asString) return ob_get_clean();
    }
}