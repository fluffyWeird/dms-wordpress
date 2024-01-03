<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');

if (!function_exists('wptmAutoload')) {
    /**
     * Function Autoload
     *
     * @param string $className ClassName
     *
     * @return void
     */
    function wptmAutoload($className)
    {
        $className = ltrim($className, '\\');

        //Return if it's not a Joomunited's class
        if (strpos($className, 'Joomunited\WP_Table_Manager\Admin\Fields') === 0) {
            $fileName  = '';
            $namespace = '';
            $lastNsPos = strripos($className, '\\');
            if ($lastNsPos) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            $folder   = 'admin' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR;
            $fileName = '' . DIRECTORY_SEPARATOR . substr($fileName, 41);

            if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $folder . $fileName)) {
                require dirname(__FILE__) . DIRECTORY_SEPARATOR . $folder . $fileName;
            }

            return;
        }

        //auto load PHPSQLParser lib
        if (strpos($className, 'PHPSQLParser\\') === 0) {
            require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
                . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

//            $dirnamePHPSQLParser = dirname(WPTM_PLUGIN_FILE) . '/app/admin/classes/php-sql-parser/src/PHPSQLParser/';
//            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
//            $className = str_replace('PHPSQLParser/', $dirnamePHPSQLParser, $className);
//            if (file_exists($className . '.php')) {
//                require $className . '.php';
//            }
            return;
        }

        //don't load any namespace class
        if (strpos($className, '\\') === false) {
            return;
        }
        $fileName1 = explode('\\', $className);
        $fileName = end($fileName1);
        $fileName = lcfirst($fileName) . '.php';
        $app      = Application::getInstance('Wptm');

        if ($app->isAdmin() || (strpos($className, 'WP_Table_Manager') !== 0 && strpos($className, 'Admin') !== 0)) {
            $file = $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $fileName;
        } else {
            $file = $app->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $fileName;
        }
        if (file_exists($file)) {
            require_once($file);
        }
    }
}
spl_autoload_register('wptmAutoload');

/**
 * Add a wptmSchedules of wptm_cronjob minute
 *
 * @param array $schedules Schedules list
 *
 * @return array
 */
function wptm_cron_schedules($schedules)
{
    require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/admin/models/config.php';
    $WptmModelConfig = new WptmModelConfig();
    $config = $WptmModelConfig->getConfig();
    /**
     * Filters hook changer sync_periodicity option
     *
     * @param object $config option config table
     */
    $config = apply_filters('wptm_rewrite_sync_periodicity', $config);
    if (!empty($config['wptm_sync_method']) && $config['wptm_sync_method'] === 'cron' && $config['sync_periodicity'] > 0) {
        $schedules['wptmSchedules'] = array(
            'interval' => $config['sync_periodicity'] * 3600,
            'display'  => __('Wptm run cronjob', 'wptm')
        );
        //when select 5 minute
        if ((string) $config['sync_periodicity'] === '0.083') {
            $schedules['wptmSchedules']['interval'] = 300;
        }
    } else {
        $timestamp = wp_next_scheduled('wptmSchedules');
        wp_unschedule_event($timestamp, 'wptmSchedules');
    }

    return $schedules;
}
add_filter('cron_schedules', 'wptm_cron_schedules');

// Schedule an action if it's not already scheduled
if (!wp_next_scheduled('wptm_run_cronjob')) {
    wp_schedule_event(time(), 'wptmSchedules', 'wptm_run_cronjob');
}

// Hook into that action that'll fire every three minutes
add_action('wptm_run_cronjob', 'wptm_event_func');

/**
 * Wptm event func
 *
 * @return void
 */
function wptm_event_func()
{
    require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/admin/controllers/excel.php';
    $WptmControllerExcel = new WptmControllerExcel();
    $WptmControllerExcel->syncSpreadsheet();
}

require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/includes/includes.php';
