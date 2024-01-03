<?php
/*
Plugin Name: Divi Wptm
Plugin URI:
Description:
Version:     1.0.0
Author:
Author URI:
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: divi-divi_wptm
Domain Path: /languages

Divi Wptm is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Divi Wptm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Divi Wptm. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Factory;

if (!function_exists('divi_wptm_initialize_extension')) {
    /**
     * Creates the extension's main class instance.
     *
     * @return void
     */
    function divi_wptm_initialize_extension()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/DiviWptm.php';

        $min = '.min';
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
            $min = '';
        }

        wp_enqueue_style(
            'wptm-divi-modules-style-1',
            plugins_url('app/includes/divi_wptm/style/module-option.css', WPTM_PLUGIN_FILE),
            array(),
            WPTM_VERSION
        );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for check only
        if (isset($_GET['et_fb']) && current_user_can('edit_posts')) {//get file css for module view
            wp_register_style('wptm-style-frontend', plugins_url('app/site/assets/css/front.css', WPTM_PLUGIN_FILE));
            wp_enqueue_style('wptm-style-frontend');
            wp_register_style('wptm_daterangepicker', plugins_url('app/site/assets/css/daterangepicker.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            wp_enqueue_style('wptm_daterangepicker');
            wp_register_style('wptm_datatables', plugins_url('app/site/assets/DataTables/datatables' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            wp_enqueue_style('wptm_datatables');
            wp_register_style('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            wp_enqueue_style('wptm_tipso');

            wp_register_script('jquery-wptm-moment', plugins_url('app/site/assets/js/moment.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
            wp_enqueue_script('jquery-wptm-moment');
            wp_register_script('jquery-wptm-daterangepicker', plugins_url('app/site/assets/js/daterangepicker.min.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
            wp_enqueue_script('jquery-wptm-daterangepicker');
            wp_register_script('jquery-wptm-jdateformatparser', plugins_url('app/site/assets/js/moment-jdateformatparser.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
            wp_enqueue_script('jquery-wptm-jdateformatparser');
            wp_register_script('wptm-accounting', plugins_url('app/admin/assets/plugins/accounting.js', WPTM_PLUGIN_FILE), array(), false, 'all');
            wp_enqueue_script('accounting');
            wp_register_script('wptm_datatables', plugins_url('app/site/assets/DataTables/datatables' . $min . '.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
            wp_enqueue_script('wptm_datatables');
            wp_register_script('wptm_datatables_fixedColumns_js', plugins_url('app/site/assets/DataTables/dataTables.fixedColumns.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
            wp_enqueue_script('wptm_datatables_fixedColumns_js');
            wp_register_script('wptm_datatables_fixedColumns_js', plugins_url('app/site/assets/DataTables/dataTables.responsive.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
            wp_enqueue_script('wptm_datatables_responsive_js');
            /* add tipso lib when tooltip cell exists*/
            wp_register_script('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
            wp_enqueue_script('wptm_tipso');
            wp_register_script('wptm_table_front', plugins_url('app/site/assets/js/wptm_front.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
            wp_enqueue_script('wptm_table_front');

            Application::getInstance('Wptm');
            wp_localize_script('wptm_table_front', 'wptm_data', array(
                'wptm_ajaxurl' => esc_url_raw(Factory::getApplication('wptm')->getAjaxUrl())
            ));


            wp_register_script('wptm_chart_divi', plugins_url('app/admin/assets/js/Chart.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
            wp_enqueue_script('wptm_chart_divi');
            wp_register_script('wptm_dropchart', plugins_url('app/site/assets/js/dropchart.js', WPTM_PLUGIN_FILE), array('wptm_chart_divi', 'jquery'), WPTM_VERSION);
            wp_enqueue_script('wptm_dropchart');
        }
    }

    add_action('divi_extensions_init', 'divi_wptm_initialize_extension');
}
