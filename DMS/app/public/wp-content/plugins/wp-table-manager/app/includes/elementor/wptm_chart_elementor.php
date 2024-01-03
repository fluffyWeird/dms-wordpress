<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 2.7.4
 */

namespace WptmChartElementorWidget;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WptmTableElementorWidget
 */
class WptmChartElementorWidget extends \Elementor\Widget_Base
{
    /**
     * Get widget name.
     *
     * Retrieve Single File widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'wptm_chart';
    }

    /**
     * Get widget title.
     *
     * Retrieve Single File widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('WP Table Manager Chart', 'wptm');
    }

    /**
     * Get widget icon.
     *
     * Retrieve Single File widget icon.
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'wp-table-manager-chart';
    }

    /**
     * Get widget categories.
     *
     * Retrieve the list of categories the Single File widget belongs to.
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return array('general', 'joom-category');
    }

    /**
     * Register Single File widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @access protected
     *
     * @return void
     */
    protected function register_controls()
    {
        $this->start_controls_section(
            'wptm_chart_section',
            array(
                'label' => __('Option', 'wptm'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'wptm_chart',
            array(
                'label' => __('Choose Chart', 'wptm'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<a href="#wptmmodal" class="button wptmlaunch elementor_edit" id="wptmlaunch" data-type="chart" title="WP Table Manager">'
                    . ' <span class="dashicons" style="line-height: inherit;"></span><span>' . esc_attr__('WP Table Manager', 'wptm') . '</span></a>',
                'content_classes' => 'wptm-chart-controls'
            )
        );

        $this->add_control(
            'wptm_table_old',
            array(
                'label' => __('Edit Chart', 'wptm'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<a class="button wptmReLaunch elementor_edit" id="wptmReLaunch" data-type="chart" title="WP Table Manager">'
                    . ' <i class="eicon-edit" aria-hidden="true"></i></a>',
                'content_classes' => 'wptm-table-controls-old'
            )
        );

        $this->add_control(
            'wptm_table_chart_id',
            array(
                'label' => __('Table Id', 'wptm'),
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'input_type' => 'text',
                'classes' => 'wptm-table-chart-id-controls'
            )
        );

        $this->add_control(
            'wptm_chart_id',
            array(
                'label' => __('Chart Id', 'wptm'),
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'input_type' => 'text',
                'classes' => 'wptm-chart-id-controls'
            )
        );

        $this->add_control(
            'wptm_chart_name',
            array(
                'label' => __('Chart Title', 'wptm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
                'classes' => 'wptm-chart-name-controls'
            )
        );

        $this->end_controls_section();
    }

    /**
     * Generates the final HTML on the frontend
     *
     * @return void
     */
    public function render()
    {
        $settings = $this->get_settings_for_display();

        if (!empty($settings['wptm_table_chart_id']) && $settings['wptm_table_chart_id'] !== 0 && !empty($settings['wptm_chart_id']) && $settings['wptm_chart_id'] !== 0) {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $content = $WptmFilter->replaceChart($settings['wptm_chart_id']);
            if (isset($content) && Factory::getApplication()->getType() === 'admin') {
                echo '<div id="wptm-elementor-chart' . esc_attr($settings['wptm_chart_id']) . '" class="wptm-elementor-chart loadding">';
                echo '<img style="background: url(\'' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/chart.svg') . '\') no-repeat scroll center center #D6D6D6; background-size: contain; border: 2px dashed #888888; height: 150px; border-radius: 10px; width: 99%;" src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '" data-wptmtable="' . esc_attr($settings['wptm_table_chart_id']) . '" data-wptm-chart="' . esc_attr($settings['wptm_chart_id']) . '" />';
                echo '</div>';
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content escape above
                echo $content;
            }
        } else {
            ?>
            <div id="wptm-table-placeholder" class="wptm-table-placeholder">
                <img class="single-file-icon" style="background: url(<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/elementor/assets/images/wptm_placeholder_chart.svg'); ?>) no-repeat scroll center center #fafafa;background-position-y: 0; padding-bottom: 30px; height: 230px; border-radius: 2px; width: 99%;background-clip: content-box;" src="<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif'); ?>" data-mce-src="<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif'); ?>" data-mce-style="background: url(<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/elementor/assets/images/wptm_placeholder_chart.svg'); ?>) no-repeat scroll center center #fafafa; height: 200px; border-radius: 2px; width: 99%;">
                <span style="font-size: 13px; text-align: center;"><?php echo esc_html_e('Please select a WP Table Manager content to activate the preview', 'wptm'); ?></span>
            </div>
            <?php
        }
    }

    /**
     * Element base constructor
     *
     * @param array      $data Element data. Default is an empty array
     * @param null|array $args Element default arguments. Default is null
     *
     * @throws |Exception
     *
     * @return void
     */
    public function __construct($data = array(), $args = null)
    {
        Application::getInstance('Wptm');
        parent::__construct($data, $args);

        wp_register_script('wptm_chart', plugins_url('app/admin/assets/js/Chart.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
        wp_register_script('wptm_dropchart', plugins_url('app/site/assets/js/dropchart.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);

        wp_register_script('wptm_elementor', plugins_url('app/includes/elementor/assets/js/wptm-elementor.js', WPTM_PLUGIN_FILE), array('jquery', 'elementor-frontend'), WPTM_VERSION, true);
        wp_localize_script('wptm_elementor', 'wptm_elementor_var', array(
            'wptm_ajaxurl' => esc_url_raw(Factory::getApplication()->getAjaxUrl()),
        ));
    }

    /**
     * Get script dependencies
     *
     * @return array
     */
    public function get_script_depends()
    {
        return array('wptm_chart', 'wptm_dropchart', 'wptm_elementor');
    }
}
