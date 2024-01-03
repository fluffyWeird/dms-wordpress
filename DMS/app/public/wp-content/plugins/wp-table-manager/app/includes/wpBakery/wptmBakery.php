<?php

use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

if (!function_exists('wptm_initializeWPBakery')) {
    /**
     * Creates the extension's main class instance.
     *
     * @since 1.0.0
     *
     * @return void
     */
    function wptm_initializeWPBakery()
    {

        if (!defined('WPB_VC_VERSION')) {
            return;
        }

        require_once(WPTM_PLUGIN_DIR . '/app/includes/wpBakery/table.php');
        require_once(WPTM_PLUGIN_DIR . '/app/includes/wpBakery/chart.php');

        if (is_admin()) {
            //backend enqueue
            add_action('vc_backend_editor_enqueue_js_css', 'wptm_wpbakery_enqueue_assets');

            //frontend enqueue
            add_action('vc_frontend_editor_enqueue_js_css', 'wptm_wpbakery_enqueue_assets');
        }

        wp_enqueue_style(
            'wptm-wpbakery-style',
            plugins_url('app/includes/wpBakery/assets/css/wpbakery.css', WPTM_PLUGIN_FILE),
            array(),
            WPTM_VERSION,
            'all'
        );
    }
    add_action('init', 'wptm_initializeWPBakery');
}

/**
 * WPBakery enqueue assets
 *
 * @return void
 */
function wptm_wpbakery_enqueue_assets()
{
    Application::getInstance('Wptm');
    $min = '.min';
    if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
        $min = '';
    }

    wp_enqueue_style(
        'wptm-wpbakery-style',
        plugins_url('app/includes/wpBakery/assets/css/wpbakery.css', WPTM_PLUGIN_FILE),
        array(),
        '123',
        'all'
    );
    wp_enqueue_style('wptm-modal', plugins_url('app/admin/assets/css/leanmodal.css', WPTM_PLUGIN_FILE));

    //table
    wp_register_style('wptm-style-frontend', plugins_url('app/site/assets/css/front.css', WPTM_PLUGIN_FILE));
    wp_enqueue_style('wptm-style-frontend');
    wp_register_style('wptm_datatables', plugins_url('app/site/assets/DataTables/datatables' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
    wp_enqueue_style('wptm_datatables');
    wp_register_style('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
    wp_enqueue_style('wptm_tipso');

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-wptm-moment', plugins_url('app/site/assets/js/moment.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
    wp_enqueue_script('jquery-wptm-jdateformatparser', plugins_url('app/site/assets/js/moment-jdateformatparser.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
    wp_enqueue_script('wptm-accounting', plugins_url('app/admin/assets/plugins/accounting.js', WPTM_PLUGIN_FILE), array(), false, 'all');
    wp_enqueue_script('wptm_datatablesJs', plugins_url('app/site/assets/DataTables/datatables' . $min . '.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
    wp_enqueue_script('wptm_datatables_fixedColumns_js', plugins_url('app/site/assets/DataTables/dataTables.fixedColumns.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);

    /* add tipso lib when tooltip cell exists*/
    wp_enqueue_script('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);

    wp_enqueue_script('wptm_table', plugins_url('app/site/assets/js/wptm_front.js', WPTM_PLUGIN_FILE), array('jquery', 'wptm_datatablesJs'), WPTM_VERSION);
    wp_localize_script('wptm_table', 'wptm_data', array(
        'wptm_ajaxurl' => esc_url_raw(Factory::getApplication('wptm')->getAjaxUrl())
    ));

    //chart
//    wp_enqueue_script('wptm_chart', plugins_url('app/admin/assets/js/Chart.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
//    wp_enqueue_script('wptm_dropchart', plugins_url('app/site/assets/js/dropchart.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);

    //js bakery
    wp_enqueue_script('wptm-modal', plugins_url('app/admin/assets/js/jquery.leanModal.min.js', WPTM_PLUGIN_FILE), array('jquery'));
    wp_enqueue_script('wptm-modal-init', plugins_url('app/admin/assets/js/leanmodal.init.js', WPTM_PLUGIN_FILE), array('jquery', 'wptm-modal'));

    wp_enqueue_script(
        'wptm-wpbakery-modal',
        plugins_url('app/includes/wpBakery/assets/js/wptm.js', WPTM_PLUGIN_FILE),
        array( 'jquery', 'wptm-modal-init' )
    );
}
