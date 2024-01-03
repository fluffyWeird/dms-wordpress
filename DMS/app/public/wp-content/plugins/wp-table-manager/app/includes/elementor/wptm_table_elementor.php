<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 2.7.4
 */

namespace WptmTableElementorWidget;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WptmTableElementorWidget
 */
class WptmTableElementorWidget extends \Elementor\Widget_Base
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
        return 'wptm_table';
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
        return __('WP Table Manager Table', 'wptm');
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
        return 'wp-table-manager-table';
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
            'wptm_table_section',
            array(
                'label' => __('Option', 'wptm'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'wptm_table',
            array(
                'label' => __('Choose Table', 'wptm'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<a href="#wptmmodal" class="button wptmlaunch elementor_edit" id="wptmlaunch" data-type="table" title="WP Table Manager">'
                    . ' <span class="dashicons" style="line-height: inherit;"></span><span>' . esc_attr__('WP Table Manager', 'wptm') . '</span></a>',
                'content_classes' => 'wptm-table-controls'
            )
        );

        $this->add_control(
            'wptm_table_old',
            array(
                'label' => __('Edit Table', 'wptm'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<a class="button wptmReLaunch elementor_edit" id="wptmReLaunch" data-type="table" title="WP Table Manager">'
                    . ' <i class="eicon-edit" aria-hidden="true"></i></a>',
                'content_classes' => 'wptm-table-controls-old'
            )
        );

        $this->add_control(
            'wptm_table_id',
            array(
                'label' => __('Table Id', 'wptm'),
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'input_type' => 'text',
                'classes' => 'wptm-table-id-controls'
            )
        );

        $this->add_control(
            'wptm_table_name',
            array(
                'label' => __('Table Title', 'wptm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'text',
                'classes' => 'wptm-table-name-controls'
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

        if (!empty($settings['wptm_table_id']) && $settings['wptm_table_id'] !== 0) {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $content = $WptmFilter->replaceTable($settings['wptm_table_id']);

            if (isset($content) && Factory::getApplication()->getType() === 'admin') {
                echo '<div id="wptm-elementor-table' . esc_attr($settings['wptm_table_id']) . '" class="wptm-elementor-table loadding">';
                echo '<img style="background: url(\'' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/spreadsheet.svg')
                    . '\') no-repeat scroll center center #D6D6D6; background-size: contain; border: 2px dashed #888888; height: 150px; border-radius: 10px; width: 99%;" src="'
                    . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '" data-wptmtable="' . esc_attr($settings['wptm_table_id']) . '" />';
                echo '</div>';
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content escape above
                echo $content;
            }
        } else {
            ?>
            <div id="wptm-table-placeholder" class="wptm-table-placeholder">
                <img class="single-file-icon" style="background: url(<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/elementor/assets/images/wptm_placeholder_table.svg'); ?>) no-repeat scroll center center #fafafa;background-clip: content-box;background-position-y: 0; padding-bottom: 30px; height: 230px; border-radius: 2px; width: 99%;" src="<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif'); ?>"
                     data-mce-src="<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif'); ?>"
                     data-mce-style="background: url(<?php echo esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/elementor/assets/images/wptm_placeholder_table.svg'); ?>)
                      no-repeat scroll center center #fafafa; height: 230px; border-radius: 2px; width: 99%;background-position-y: 0; padding-bottom: 30px;background-clip: content-box;">
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
    public function __construct($data = [], $args = null)
    {
        Application::getInstance('Wptm');
        parent::__construct($data, $args);
        $min = '.min';
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
            $min = '';
        }

        wp_register_style('wptm-style-frontend', plugins_url('app/site/assets/css/front.css', WPTM_PLUGIN_FILE));
        wp_enqueue_style('wptm-style-frontend');
        wp_register_style('wptm_datatables', plugins_url('app/site/assets/DataTables/datatables' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
        wp_enqueue_style('wptm_datatables');
        wp_register_style('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
        wp_enqueue_style('wptm_tipso');

        wp_register_script('jquery-wptm-moment', plugins_url('app/site/assets/js/moment.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
        wp_enqueue_script('jquery-wptm-moment');
        wp_register_script('jquery-wptm-jdateformatparser', plugins_url('app/site/assets/js/moment-jdateformatparser.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
        wp_enqueue_script('jquery-wptm-jdateformatparser');
        wp_register_script('wptm-accounting', plugins_url('app/admin/assets/plugins/accounting.js', WPTM_PLUGIN_FILE), array(), false, 'all');
        wp_enqueue_script('wptm-accounting');
        wp_register_script('wptm_datatables', plugins_url('app/site/assets/DataTables/datatables' . $min . '.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
        wp_enqueue_script('wptm_datatables');
        /* add tipso lib when tooltip cell exists*/
        wp_register_script('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.js', WPTM_PLUGIN_FILE), array('jquery'), WPTM_VERSION);
        wp_register_script('wptm_table', plugins_url('app/site/assets/js/wptm_front.js', WPTM_PLUGIN_FILE),
            array('jquery', 'jquery-wptm-moment', 'jquery-wptm-jdateformatparser', 'wptm-accounting', 'wptm_datatables'), WPTM_VERSION);
//        wp_register_script('wptm_table', plugins_url('app/site/assets/js/wptm_front.js', WPTM_PLUGIN_FILE),
//            array('jquery', 'wptm_datatables'), WPTM_VERSION);
//        wp_enqueue_script('wptm_table');
        wp_register_script('wptm_datatables_fixedColumns_js', plugins_url('app/site/assets/DataTables/dataTables.fixedColumns.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION, true);
        wp_enqueue_script('wptm_datatables_fixedColumns_js');
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
        return array('wptm_datatables', 'wptm_tipso', 'wptm_table', 'wptm_elementor');
    }
}
