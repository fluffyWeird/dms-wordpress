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

// elementor init
if (!function_exists('wptm_elementor_init')) {
    /**
     * WP Table Manager elementor init
     *
     * @return void
     */
    function wptm_elementor_init()
    {
        // Check if Elementor installed and activated
        if (! did_action('elementor/loaded')) {
            return;
        }

        // Add Plugin actions
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
            add_action('elementor/widgets/widgets_registered', 'wptm_elementor_widgets');
        } else {
            add_action('elementor/widgets/register', 'wptm_elementor_widgets');
        }
    }
}
add_action('plugins_loaded', 'wptm_elementor_init');

if (!function_exists('wptm_elementor_widgets')) {
    /**
     * WP Table Manager elementor init
     *
     * @return void
     */
    function wptm_elementor_widgets()
    {
        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . '/app/includes/elementor/wptm_table_elementor.php';
        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . '/app/includes/elementor/wptm_chart_elementor.php';

        wp_register_script(
            'jquery-wptm-elementor',
            plugins_url('app/includes/elementor/assets/js/wptm.elementor.js', WPTM_PLUGIN_FILE),
            array('jquery')
        );
        wp_enqueue_script('jquery-wptm-elementor');

        Application::getInstance('Wptm');
        if (Factory::getApplication()->getType() === 'admin') {
            wp_enqueue_style('wptm-modal', plugins_url('../admin/assets/css/leanmodal.css', __FILE__));
            wp_enqueue_script('wptm-modal', plugins_url('../admin/assets/js/jquery.leanModal.min.js', __FILE__), array('jquery'));
            wp_enqueue_script('wptm-modal-init', plugins_url('../admin/assets/js/leanmodal.init.js', __FILE__), array('jquery', 'wptm-modal'));
        }

        wp_enqueue_style(
            'wptm-elementor-widget-style',
            plugins_url('app/includes/elementor/assets/css/wptm.elementor.widgets.css', WPTM_PLUGIN_FILE),
            array(),
            WPTM_VERSION
        );

        // Load widget icon style by theme mod
        $ui_theme = \Elementor\Core\Settings\Manager::get_settings_managers('editorPreferences')->get_model()->get_settings('ui_theme');

        if ('auto' === $ui_theme) {
            wp_enqueue_style(
                'wptm-elementor-widget-dark-style',
                plugins_url('app/includes/elementor/assets/css/wptm.elementor.dark.css', WPTM_PLUGIN_FILE),
                array(
                    'wptm-elementor-widget-style'
                ),
                WPTM_VERSION,
                '(prefers-color-scheme: dark)'
            );
            wp_enqueue_style(
                'wptm-elementor-widget-light-style',
                plugins_url('app/includes/elementor/assets/css/wptm.elementor.light.css', WPTM_PLUGIN_FILE),
                array(
                    'wptm-elementor-widget-style'
                ),
                WPTM_VERSION,
                '(prefers-color-scheme: light)'
            );
        } elseif ('dark' === $ui_theme) {
            wp_enqueue_style(
                'wptm-elementor-widget-dark-style',
                plugins_url('app/includes/elementor/assets/css/wptm.elementor.dark.css', WPTM_PLUGIN_FILE),
                array(
                    'wptm-elementor-widget-style'
                ),
                WPTM_VERSION,
                'all'
            );
        } else { // Light mode
            wp_enqueue_style(
                'wptm-elementor-widget-light-style',
                plugins_url('app/includes/elementor/assets/css/wptm.elementor.light.css', WPTM_PLUGIN_FILE),
                array(
                    'wptm-elementor-widget-style'
                ),
                WPTM_VERSION,
                'all'
            );
        }

        // Let Elementor know about our widget
        if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, '3.5', '<')) {
            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new WptmTableElementorWidget\WptmTableElementorWidget());
            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new WptmChartElementorWidget\WptmChartElementorWidget());
        } else {
            Elementor\Plugin::instance()->widgets_manager->register(new WptmTableElementorWidget\WptmTableElementorWidget());
            Elementor\Plugin::instance()->widgets_manager->register(new WptmChartElementorWidget\WptmChartElementorWidget());
        }
    }
}

if (!function_exists('joom_create_category_widget_elementor')) {
    /**
     * Joom create elementor widget category
     *
     * @param object $elements_manager Elements manager
     *
     * @return void
     */
    function joom_create_category_widget_elementor($elements_manager)
    {
        $elements_manager->add_category(
            'joom-category',
            array(
                'title' => __('WP Table Manager', 'wptm'),
                'icon' => 'fa fa-plug'
            )
        );
    }

    add_action('elementor/elements/categories_registered', 'joom_create_category_widget_elementor');
}

require_once WPTM_PLUGIN_DIR . 'app/includes/divi_wptm/divi_wptm.php';

require_once WPTM_PLUGIN_DIR . 'app/includes/wpBakery/wptmBakery.php';

if (!function_exists('wptm_avada_inits')) {

    /**
     * Wptm_avada_inits
     *
     * @return void
     */
    function wptm_avada_inits()
    {
        if (!defined('AVADA_VERSION') || !defined('FUSION_BUILDER_VERSION')) {
            return;
        }

        require_once(WPTM_PLUGIN_DIR . '/app/includes/avada/wptmTableChart.php');

        if (fusion_is_builder_frame()) {
            add_action('wp_enqueue_scripts', 'wptm_avada_enqueue_assets', 999);
        }

        if (class_exists('Fusion_App')) {
            $fusion_app_instance = Fusion_App::get_instance();
            if ($fusion_app_instance->is_builder) {
                add_action('wp_enqueue_scripts', 'wptm_avada_scripts', 997);
            }
        }

        if (is_admin()) {
            add_action('fusion_builder_admin_scripts_hook', 'wptm_avada_scripts');
        }
    }
    add_action('init', 'wptm_avada_inits');
}

/**
 * Wptm_avada_enqueue_assets
 *
 * @return void
 */
function wptm_avada_enqueue_assets()
{
    wp_enqueue_style(
        'wptm-avada-style-front-end',
        plugins_url('app/includes/avada/assets/css/style.css', WPTM_PLUGIN_FILE),
        array(),
        WPTM_VERSION,
        'all'
    );//in front end preview
}

/**
 * Add avada scripts
 *
 * @return void
 */
function wptm_avada_scripts()
{
    Application::getInstance('Wptm');
    $min = '.min';
    if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
        $min = '';
    }

    wp_enqueue_style('wptm-modal', plugins_url('app/admin/assets/css/leanmodal.css', WPTM_PLUGIN_FILE));

    //table
    wp_enqueue_script('jquery');

    wp_enqueue_script('wptm-avada-modal', plugins_url('app/admin/assets/js/jquery.leanModal.min.js', WPTM_PLUGIN_FILE), array('jquery'));
    wp_enqueue_script('wptm-avada-modal-init', plugins_url('app/admin/assets/js/leanmodal.init.js', WPTM_PLUGIN_FILE), array('jquery', 'wptm-avada-modal'));
    wp_localize_script('wptm-avada-modal-init', 'wptm_avada', array(
        'wptm_view' => esc_url_raw(admin_url())
    ));
    wp_enqueue_script(
        'wptm-avada',
        plugins_url('app/includes/avada/assets/js/wptm.js', WPTM_PLUGIN_FILE),
        array( 'jquery', 'wptm-avada-modal-init' )
    );
}
