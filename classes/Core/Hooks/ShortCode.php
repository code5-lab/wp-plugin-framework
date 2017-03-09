<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 06/03/2017
 * Time: 11:15
 */

namespace Com\Componto\Framework\Core\Hooks;

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
                $methodReflection = new \ReflectionMethod($class, $method);
                $methodParams = [];

                foreach ($methodReflection->getParameters() as $parameter) {
                    if ($attributes[snake_case($parameter->name)] != null) {
                        $methodParams[] = $attributes[snake_case($parameter->name)];
                    } else {
                        $methodParams[] = $attributes[strtolower($parameter->name)];
                    }
                }

                return (new $class($ctx))->$method(...$methodParams);
            } catch (\Exception $e) {
                var_dump($e);
            }
        });
    }

}