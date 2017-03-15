<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 07/03/2017
 * Time: 11:48
 */

namespace Com\CodeFive\Framework\Core\Hooks;


interface HookContract
{
    public static function create(array $hook, $context);
}