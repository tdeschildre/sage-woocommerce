<?php
namespace App;

if (defined('WC_ABSPATH')) {
    add_action('after_setup_theme', function () {
        add_theme_support('woocommerce');
    });

    /**
     * @param string $template
     * @return string
     */
    function wc_template_loader(String $template)
    {
        if (strpos($template, get_template_directory()) !== false) {
            return locate_template(WC()->template_path() . str_replace(get_template_directory() . '/woocommerce/', '', $template)) ? : $template;
        }

        return strpos($template, WC_ABSPATH) === -1
            ? $template
            : (locate_template(WC()->template_path() . str_replace(WC_ABSPATH . 'templates/', '', $template)) ? : $template);
    }
    add_filter('template_include', __NAMESPACE__ . '\\wc_template_loader', 100, 1);
    add_filter('comments_template', __NAMESPACE__ . '\\wc_template_loader', 100, 1);

    add_filter('wc_get_template_part', function ($template) {
        $theme_template = locate_template(WC()->template_path() . str_replace(WC_ABSPATH . 'templates/', '', $template));

        if ($theme_template) {
            $data = collect(get_body_class())->reduce(function ($data, $class) {
                return apply_filters("sage/template/{$class}/data", $data);
            }, []);

            echo template($theme_template, $data);
            return get_stylesheet_directory() . '/index.php';
        }

        return $template;
    }, PHP_INT_MAX, 1);

    add_action('woocommerce_before_template_part', function ($template_name, $template_path, $located, $args) {
        $theme_template = locate_template(WC()->template_path() . $template_name);

        if ($theme_template) {
            $data = collect(get_body_class())->reduce(function ($data, $class) {
                return apply_filters("sage/template/{$class}/data", $data);
            }, []);

            // Get back to blade view syntax
            $paths = apply_filters('sage/filter_templates/paths', [
                'views',
                'resources/views',
            ]);
            $paths_pattern = "#^(" . implode('|', $paths) . ")/#";

            $blade_template = WC()->template_path() . str_replace('.php', '', $template_name);

            /** Remove .blade.php/.blade/.php from template names */
            $blade_template = preg_replace('#\.(blade\.?)?(php)?$#', '', ltrim($blade_template));

            /** Remove partial $paths from the beginning of template names */
            if (strpos($blade_template, '/')) {
                $blade_template = preg_replace($paths_pattern, '', $blade_template);
            }

            echo template($blade_template, array_merge(
                compact(explode(' ', 'template_name template_path located args')),
                $data,
                $args
            ));
        }
    }, PHP_INT_MAX, 4);

    add_filter('wc_get_template', function ($template, $template_name, $args) {
        $theme_template = locate_template(WC()->template_path() . $template_name);

        // return theme filename for status screen
        if (is_admin() && ! wp_doing_ajax() && function_exists('get_current_screen') && get_current_screen() && get_current_screen()->id === 'woocommerce_page_wc-status') {
            return $theme_template ? : $template;
        }

        // return empty file, output already rendered by 'woocommerce_before_template_part' hook
        return $theme_template ? get_stylesheet_directory() . '/index.php' : $template;
    }, 100, 3);
}
