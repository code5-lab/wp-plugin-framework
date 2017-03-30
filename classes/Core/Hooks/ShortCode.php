<?php


namespace Com\CodeFive\Framework\Core\Hooks;

class ShortCode implements HookContract
{
    public static function create(array $handler, $ctx)
    {
        $class = $handler[0];
        $method = $handler[1];
        add_shortcode(snake_case($method), function ($attributes = [], $content = null) use ($class, $method, $ctx) {
            try {
                if (!is_array($attributes)) {
                    $attributes = [];
                }
                if ($content != null) {
                    $attributes['content'] = $content;
                }
                return (new $class($ctx))->$method($attributes);
            } catch (\Exception $e) {
                var_dump($e);
            }
        });
    }

}