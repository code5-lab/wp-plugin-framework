<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 06/03/2017
 * Time: 12:07
 */

namespace Com\CodeFive\Framework\Core\Hooks;


class Enqueue
{
    protected static $filters = [
        'hook',
        //'panel',
        'page',
        'post',
        'category',
        'archive',
        'search',
        'postType'
    ];

    protected static $enqueuePlaceHook = [
        'admin' => 'admin_enqueue_scripts',
        'login' => 'login_enqueue_scripts',
        'site' => 'wp_enqueue_scripts',
    ];

    public static function create($file, $context)
    {
        $places = explode('|', $file['place']); // admin|site|login

        if (empty($places)) throw new \InvalidArgumentException('Enqueue: Invalid place');
        if (empty($file['src'])) throw new \InvalidArgumentException('Enqueue: \'src\' not defined');
        if (empty($file['as'])) throw new \InvalidArgumentException('Enqueue: \'as\' not defined');

        foreach ($places as $place) {
            add_action(Enqueue::$enqueuePlaceHook[$place], function ($hook = null) use ($file, $context) {
                if ($hook) $file['hook'] = $hook;
                Enqueue::buildInclude($file, $context);
            });
        }

    }

    private static function buildInclude($file, $context)
    {
        if (isset($file['filter']) && !empty($file['filter'])) {
            $filterBy = key($file['filter']);
            $filterWith = reset($file['filter']);

            if (!is_array($filterWith)) {
                $filterWith = [$filterWith];
            }

            if (!Enqueue::filterBy($filterBy, $file, $filterWith)) {
                return;
            }
        }


        if (!$file['external']) {
            $file['full'] = plugins_url($context . '/app/resources/assets/');
            if (pathinfo($file['src'], PATHINFO_EXTENSION) === 'css') {
                $file['full'] .= 'css/' . $file['src'];
            } else {
                $file['full'] .= 'js/' . $file['src'];
            }
        } else {
            $file['full'] = $file['src'];
        }

        if (pathinfo($file['src'], PATHINFO_EXTENSION) === 'css') {
            wp_enqueue_style($file['as'], $file['full']);
        } else {
            wp_enqueue_script($file['as'], $file['full'], [], false, $file['position'] == 'footer');

            if (isset($file['localize'])) {
                wp_localize_script($file['as'], $file['as'], $file['localize']);
            }
        }

    }

    private static function filterBy($by, $file, $with)
    {
        $method = 'filter' . ucfirst($by);

        try {
            $func = (new \ReflectionMethod(Enqueue::class, $method))->getClosure();
            return $func($file, $with);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function filterHook($file, $filterWith)
    {
        $hook = $file['hook'];

        if ($filterWith[0] === '*') {
            return true;
        }

        return array_search($hook, $filterWith) !== null;
    }

    /*   public function filterPanel($file, $filterWith)
       {
           $panels = $this->app['panel']->getPanels();

           $page = empty($_GET['page']) ? false : $_GET['page'];

           if (!$page && function_exists('get_current_screen')) {
               $page = object_get(get_current_screen(), 'id', $page);
           }

           foreach ($filterWith as $filter) {
               $filtered = array_filter($panels, function ($panel) use ($page, $filter) {
                   return $page === $panel['slug'] && str_is($filter, $panel['slug']);
               });

               if (count($filtered) > 0) {
                   return true;
               }
           }

           return false;
       }*/

    public function filterPage($file, $filterWith)
    {
        if ($filterWith[0] === '*' && is_page()) {
            return true;
        }

        foreach ($filterWith as $filter) {
            if (is_page($filter)) {
                return true;
            }
        }

        return false;
    }

    public function filterPost($file, $filterWith)
    {
        if ($filterWith[0] === '*' && is_single()) {
            return true;
        }

        foreach ($filterWith as $filter) {
            if (is_single($filter)) {
                return true;
            }
        }

        return false;
    }

    public function filterCategory($file, $filterWith)
    {
        if ($filterWith[0] === '*' && is_category()) {
            return true;
        }

        foreach ($filterWith as $filter) {
            if (is_category($filter)) {
                return true;
            }
        }

        return false;
    }

    public function filterArchive($file, $filterWith)
    {
        return is_archive();
    }

    public function filterSearch($file, $filterWith)
    {
        return is_search();
    }

    public function filterPostType($file, $filterWith)
    {
        return array_search(get_post_type(), $filterWith) !== FALSE;
    }
}