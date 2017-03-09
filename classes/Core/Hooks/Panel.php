<?php
/**
 * Created by PhpStorm.
 * User: eduardo
 * Date: 06/03/2017
 * Time: 15:56
 */

namespace Com\Componto\Framework\Core\Hooks;


use InvalidArgumentException;

class Panel implements HookContract
{
    protected static $methods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE'
    ];

    /**
     * @var array
     */
    protected static $wpPanels = [
        'index.php', 'edit.php', 'upload.php',
        'link-manager.php', 'edit.php?post_type=*',
        'edit-comments.php', 'themes.php',
        'plugins.php', 'users.php', 'tools.php',
        'options-general.php', 'settings.php'
    ];

    protected $panel = [];
    protected $context;

    public function __construct()
    {

        if (!is_admin()) {
            return;
        }

        add_action('admin_menu', [$this, 'boot']);


        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            add_action('init', [$this, 'bootEarly']);
        }
    }

    /**
     * Boots the panels.
     *
     * @return void
     */
    public function boot()
    {
        if (empty($this->panel)) return;
        switch ($this->panel['type']) {
            case 'panel':
                $this->addPanel($this->panel);

                break;

            case 'wp-sub-panel':
            case 'sub-panel':
                $this->addSubPanel($this->panel);

                break;
        }
    }

    /**
     * Boots early.
     *
     * @return void
     */
    public function bootEarly()
    {
        if (($slug = $_GET['page'] ?: null) === null) {
            return;
        }

        if (($panel = $this->isPanel($slug, true)) === null) {
            return;
        }

        if (!$this->handler($panel, $this->context, true)) {
            return;
        }

        die;
    }

    /**
     * Adds a panel.
     *
     * @param array $data
     * @param $context
     */
    public static function create(array $data, $context)
    {

        foreach (['type', 'uses', 'title', 'slug'] as $key) {
            if (isset($data[$key])) {
                continue;
            }

            throw new InvalidArgumentException("Missing {$key} definition for panel");
        }

        if (!in_array($data['type'], ['panel', 'sub-panel', 'wp-sub-panel'])) {
            throw new InvalidArgumentException("Unknown panel type '{$data['type']}'");
        }

        if (in_array($data['type'], ['sub-panel', 'wp-sub-panel']) && !isset($data['parent'])) {
            throw new InvalidArgumentException("Missing parent definition for sub-panel");
        }

        if ($data['type'] === 'wp-sub-panel') {
            $arr = array_filter(static::$wpPanels, function ($value) use ($data) {
                return str_is($value, $data['parent']);
            });

            if (count($arr) === 0) {
                throw new InvalidArgumentException("Unknown WP panel '{$data['parent']}'");
            }
        }

        $p = new Panel();
        $p->panel = $data;
        $p->context = $context;
    }

    /**
     * Adds a panel.
     *
     * @param $panel
     * @return void
     */
    protected function addPanel($panel)
    {
        add_menu_page(
            $panel['title'],
            $panel['title'],
            isset($panel['capability']) && $panel['capability'] ? $panel['capability'] : 'manage_options',
            $panel['slug'],
            $this->makeCallable($panel),
            isset($panel['icon']) ? $this->fetchIcon($panel['icon']) : '',
            isset($panel['order']) ? $panel['order'] : null
        );

        if (isset($panel['rename']) && !empty($panel['rename'])) {
            $this->addSubPanel([
                'title' => $panel['rename'],
                'rename' => true,
                'slug' => $panel['slug'],
                'parent' => $panel['slug']
            ]);
        }
    }

    /**
     * Adds a sub panel.
     *
     * @param $panel
     * @return void
     */
    protected function addSubPanel($panel)
    {
        /*foreach ($this->panels as $parent) {
            if (array_get($parent, 'as') !== $panel['parent']) {
                continue;
            }

            $panel['parent'] = $parent['slug'];
        }*/

        add_submenu_page(
            $panel['parent-slug'],
            $panel['title'],
            $panel['title'],
            isset($panel['capability']) && $panel['capability'] ? $panel['capability'] : 'manage_options',
            $panel['slug'],
            isset($panel['rename']) && $panel['rename'] ? null : $this->makeCallable($panel)
        );
    }

    /**
     * Fetches an icon for a panel.
     *
     * @param $icon
     * @return string
     */
    protected function fetchIcon($icon)
    {
        if (empty($icon)) {
            return '';
        }

        if (substr($icon, 0, 9) === 'dashicons' || substr($icon, 0, 5) === 'data:'
            || substr($icon, 0, 2) === '//' || $icon == 'none'
        ) {
            return $icon;
        }

        return $icon;
    }

    /**
     * Makes a callable for the panel hook.
     *
     * @param $panel
     * @return callable
     */
    protected function makeCallable($panel)
    {
        $context = $this->context;
        return function () use ($panel, $context) {
            return $this->handler($panel, $context);
        };
    }

    /**
     * Gets a panel.
     *
     * @param  string $name
     * @param  boolean $slug
     * @return array
     */
    protected function isPanel($name, $slug = false)
    {
        $slug = $slug ? 'slug' : 'as';

        if (array_get($this->panel, $slug) !== $name) {
            return null;
        }

        return $this->panel;

    }

    /**
     * Gets the panels.
     *
     * @return array
     */
    public function getPanels()
    {
        return array_values($this->panels);
    }

    /**
     * Get the URL to a panel.
     *
     * @param  string $name
     * @return string
     */
    public function url($name)
    {
        if (($panel = $this->isPanel($name)) === null) {
            return null;
        }

        $slug = array_get($panel, 'slug');

        if (array_get($panel, 'type') === 'wp-sub-panel') {
            return admin_url(add_query_arg('page', $slug, array_get($panel, 'parent')));
        }

        return admin_url('admin.php?page=' . $slug);
    }


    /**
     * Return the correct callable based on action
     *
     * @param  array $panel
     * @param $context
     * @param  boolean $strict
     * @return bool
     */
    protected function handler($panel, $context, $strict = false)
    {

        $callable = $uses = $panel['uses'];
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $action = strtolower(empty($_GET['action']) ? 'uses' : $_GET['action']);

        $callable = array_get($panel, $method, false) ?: $callable;

        if ($callable === $uses || is_array($callable)) {
            $callable = array_get($panel, $action, false) ?: $callable;
        }
        if ($callable === $uses || is_array($callable)) {
            $callable = array_get($panel, "{$method}.{$action}", false) ?: $callable;
        }

        if ($strict && $uses === $callable) {
            return false;
        }

        try {
            (new $callable[0]($context))->$callable[1]();
        } catch (\Exception $e) {
            var_dump($e);
        }


        return true;
    }

}