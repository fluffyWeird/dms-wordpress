<?php
/**
 * Plugin Name: WP Table Manager
 * Plugin URI: http://www.joomunited.com/wordpress-products/wp-table-manager
 * Description: WP Table Manager, a new way to manage tables in WordPress
 * Author: Joomunited
 * Version: 3.9.4
 * Update URI: https://www.joomunited.com/juupdater_files/wp-table-manager.json
 * Tested up to: 6.3.2
 * Text Domain: wptm
 * Domain Path: /app/languages
 * Author URI: http://www.joomunited.com
 */

//Check plugin requirements
if (version_compare(PHP_VERSION, '5.6', '<')) {
    if (!function_exists('wptm_disable_plugin')) {
        /**
         * Function disable plugin
         *
         * @return void
         */
        function wptm_disable_plugin()
        {
            if (current_user_can('activate_plugins') && is_plugin_active(plugin_basename(__FILE__))) {
                deactivate_plugins(__FILE__);
                //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- unset active status plugin
                unset($_GET['activate']);
            }
        }
    }

    if (!function_exists('wptm_show_error')) {
        /**
         * Function show error
         *
         * @return void
         */
        function wptm_show_error()
        {
            echo '<div class="error"><p><strong>WP Table Manager</strong> need at least PHP 5.6 version, please update php before installing the plugin.</p></div>';
        }
    }

    //Add actions
    add_action('admin_init', 'wptm_disable_plugin');
    add_action('admin_notices', 'wptm_show_error');

    //Do not load anything more
    return;
}

//Include the jutranslation helpers
include_once('jutranslation' . DIRECTORY_SEPARATOR . 'jutranslation.php');
\Joomunited\WPTableManager\Jutranslation\Jutranslation::init(__FILE__, 'wptm', 'WP Table Manager', 'wptm', 'app' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'wptm-en_US.mo');

include_once('framework' . DIRECTORY_SEPARATOR . 'ju-libraries.php');

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');
if (!defined('WPTM_PLUGIN_FILE')) {
    define('WPTM_PLUGIN_FILE', __FILE__);
}
if (!defined('WPTM_PLUGIN_DIR')) {
    define('WPTM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
define('WP_TABLE_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('WPTM_VERSION')) {
    define('WPTM_VERSION', '3.9.4');
}

include_once('app' . DIRECTORY_SEPARATOR . 'install.php');
include_once('app' . DIRECTORY_SEPARATOR . 'autoload.php');

use Joomunited\WPFramework\v1_0_6\Application;

//Initialise the application
$app = Application::getInstance('Wptm', __FILE__);
$app->init();

if (is_admin()) {
    add_filter('mce_external_plugins', 'wptm_mce_external_plugins2');
    if (!function_exists('wptm_mce_external_plugins2')) {
        /**
         * Function wptm_mce_external_plugins2
         *
         * @param array $plugins Plugins
         *
         * @return mixed
         */
        function wptm_mce_external_plugins2($plugins)
        {
            $plugins['wpedittable'] = WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/plugins/wpedittable/plugin.js';
            return $plugins;
        }
    }

    if (!defined('JU_BASE')) {
        define('JU_BASE', 'https://www.joomunited.com/');
    }

    $remote_updateinfo = JU_BASE . 'juupdater_files/wp-table-manager.json';
    //end config

    require 'juupdater/juupdater.php';
    $UpdateChecker = Jufactory::buildUpdateChecker(
        $remote_updateinfo,
        __FILE__
    );
}

if (!function_exists('wptm_remove_xss_code')) {
    /**
     * Callback for `wp_kses_split()`.
     *
     * @param array $match Regexp matches
     *
     * @return string
     */
    function wptm_kses_split_callback($match)
    {
        global $pass_allowed_html, $pass_allowed_protocols;
        $attributes = array(
            'id' => array(), 'class' => array(),
            'height' => array(), 'width' => array(),
            'value' => array(), 'style' => array(),
            'readonly' => array(), 'type' => array(),
            'muted' => array(), 'preload' => array(),
            'controls' => array(), 'loop' => array(),
            'data-*' => array(), 'title' => array(),
            'translate' => array(), 'href' => array(),
            'target' => array(), 'disabled' => array(),
            'autofocus' => array(), 'name' => array(),
            'rel' => array(), 'method' => array(),
            'novalidate' => array(), 'action' => array(),
            'autocomplete' => array(), 'allow' => array(),
            'form*' => array(), 'hidden' => array(),
            'loading' => array(), 'src' => array(),
            'label' => array(), 'selected' => array(),
            'start' => array(), 'sizes' => array(),
            'content' => array(), 'charset' => array(),
            'param' => array(), 'size' => array(),
            'required' => array(), 'multiple' => array(),
        );
        $pass_allowed_html_key = array(
            'img', 'label', 'input','audio','article', 'aside', 'base', 'body', 'button', 'canvas', 'caption','data', 'dt', 'figure', 'form', 'footer', 'h1', 'h2',
            'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html','i', 'em', 'iframe', 'map', 'meta', 'ol', 'option', 'param', 'picture', 'section', 'li',
            'select', 'style', 'svg', 'table', 'tbody', 'td', 'textarea', 'tfoot','th', 'thead', 'title', 'tr', 'ul', 'video', 'p', 'span', 'a', 'div', 'source',
            'strong', 'dir', 'abbr', 'acronym', 'address', 'pre', 'br', 'area','small', 'col', 'colgroup', 'b', 'embed', 'frame', 'font', 'link', 'menu', 'meter',
            'nav', 'template', 'time', 'tt', 'sub', 'sup', 'summary', 'details'
        );

        if (is_array($pass_allowed_html)) {
            $wptm_pass_allowed_html = $pass_allowed_html;
        } else {
            $wptm_pass_allowed_html = array();
        }
        foreach ($pass_allowed_html_key as $key) {
            $wptm_pass_allowed_html[$key] = $attributes;
        }
        add_filter('safe_style_css', 'wptm_fix_safe_style_css', 10, 3);
        $data = wp_kses_split2($match[0], $wptm_pass_allowed_html, $pass_allowed_protocols);
        return $data;
    }

    /**
     * Fix wp safe_style_css
     *
     * @param array $safe_style_css Safe style css array
     *
     * @return array
     */
    function wptm_fix_safe_style_css($safe_style_css)
    {
        $safe_style_css[] = 'display';
        $safe_style_css[] = 'position';
        $safe_style_css[] = 'background-repeat';
        $safe_style_css[] = 'background-position-x';
        $safe_style_css[] = 'background-position-y';
        $safe_style_css[] = 'right';
        $safe_style_css[] = 'left';
        $safe_style_css[] = 'top';
        $safe_style_css[] = 'bottom';
        return $safe_style_css;
    }

    /**
     * Remove some html tag may be related to xss
     *
     * @param string $content Cell content
     *
     * @return string|string[]|null
     */
    function wptm_remove_xss_code($content)
    {
        $content = preg_replace_callback('%(<!--.*?(-->|$))|(<[^>]*(>|$)|>)%', 'wptm_kses_split_callback', $content);

        return $content;
    }
}

if (!function_exists('wptmPluginCheckForUpdates')) {
    /**
     * Plugin check for updates
     *
     * @param object $update      Update
     * @param array  $plugin_data Plugin data
     * @param string $plugin_file Plugin file
     *
     * @return array|boolean|object
     */
    function wptmPluginCheckForUpdates($update, $plugin_data, $plugin_file)
    {
        if ($plugin_file !== 'wp-table-manager/wp-table-manager.php') {
            return $update;
        }

        if (empty($plugin_data['UpdateURI']) || !empty($update)) {
            return $update;
        }

        $response = wp_remote_get($plugin_data['UpdateURI']);

        if (is_wp_error($response) || empty($response['body'])) {
            return $update;
        }

        $custom_plugins_data = json_decode($response['body'], true);

        $package = null;
        $token = get_option('ju_user_token');
        if (!empty($token)) {
            $package = $custom_plugins_data['download_url'] . '&token=' . $token . '&siteurl=' . get_option('siteurl');
        }

        return array(
            'version' => $custom_plugins_data['version'],
            'package' => $package
        );
    }
    add_filter('update_plugins_www.joomunited.com', 'wptmPluginCheckForUpdates', 10, 3);
}
