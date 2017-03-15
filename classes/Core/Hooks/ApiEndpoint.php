<?php

namespace Com\CodeFive\Framework\Core\Hooks;


class ApiEndpoint implements HookContract
{

    public static function create(array $hook, $context)
    {

        $closure = function () use ($hook, $context) {
            try {
                $class = $hook['uses'][0];
                $method = $hook['uses'][1];
                return (new $class($context))->$method();
            } catch (\Exception $e) {
                var_dump($e);
                return $e->getMessage();
            }
        };
        add_action("wp_ajax_{$hook['name']}", $closure);
        if (!empty($hook['public']) && $hook['public']) {
            add_action("wp_ajax_nopriv_{$hook['name']}", $closure);
        }
        if (!empty($hook['rewrite'])) {
            add_action('init', function () use ($hook, $context) {
                $rule = '';

                if ($hook['rewrite']['prefix'] !== false) {
                    $rule = '^' . $context . '/api/' . $hook['name'];
                }

                $rule .= $hook['rewrite']['rule'];

                $matches = '';
                if (!empty($hook['rewrite']['match_names'])) {
                    $i = 1;
                    foreach ($hook['rewrite']['match_names'] as $match_name) {
                        $matches .= '&' . $match_name . "=\$matches[{$i}]";
                        $i++;
                    }
                }

                add_rewrite_rule($rule,
                    '/wp-admin/admin-ajax.php?action=' . $hook['name'] . $matches,
                    $hook['rewrite']['order'] ?: 'top');
            }, 10, 0);
        }
    }
}