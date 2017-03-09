<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 07/03/2017
 * Time: 11:59
 */

namespace Com\Componto\Framework\Core\Controllers;


class BaseController
{
    protected $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

}