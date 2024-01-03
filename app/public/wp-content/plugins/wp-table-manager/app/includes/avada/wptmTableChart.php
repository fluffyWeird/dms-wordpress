<?php
use Joomunited\WPFramework\v1_0_6\Application;

/**
 * Declare wptm_table
 *
 * @return void
 */
function wptm_table_element()
{
    fusion_builder_map(
        fusion_builder_frontend_data(
            'WptmTable',
            array(
                'name'              => esc_attr__('WP Table Manager Table', 'wptm'),
                'shortcode'         => 'wptm_table',
                'icon'              => 'wptm-table-icon',
                'allow_generator'   => true,
                'inline_editor'     => true,
                'admin_enqueue_css' => plugins_url('app/includes/avada/assets/css/style.css', WPTM_PLUGIN_FILE),
                'preview'           => plugin_dir_path(WPTM_PLUGIN_FILE) . '/app/includes/avada/includes/table-preview.php',
                'preview_id'        => 'wptm-table-block-module-preview-template',
                'params'            => array(
                    array(
                        'type'        => 'wptm_table',
                        'heading'     => esc_attr__('Choose Table', 'wptm'),
                        'description' => 'Select the WP Table Manager Table that will be displayed on this page.',
                        'param_name'  => 'wptm_choose_table',
                    ),
                    array(
                        'type'        => 'textfield',
                        'param_name'  => 'type_table',
                        'value'       => ''
                    ),
                    array(
                        'type'        => 'textfield',
                        'param_name'  => 'wptm_selected_table_random',
                        'value'       => '',
                    ),
                    array(
                        'type'        => 'textfield',
                        'param_name'  => 'wptm_table_id',
                        'value'       => '',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('Table Title', 'wptm'),
                        'description' => esc_attr__('The title of the selected table.', 'wptm'),
                        'param_name'  => 'wptm_selected_table_title',
                        'value'       => '',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('CSS Class', 'wptm'),
                        'description' => esc_attr__('Add a class to the wrapping HTML element.', 'wptm'),
                        'param_name'  => 'class',
                        'value'       => '',
                        'group'       => esc_attr__('Extras', 'wptm')
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('CSS ID', 'wptm'),
                        'description' => esc_attr__('Add an ID to the wrapping HTML element.', 'wptm'),
                        'param_name'  => 'id_builder',
                        'value'       => '',
                        'group'       => esc_attr__('Extras', 'wptm'),
                    ),
                )
            )
        )
    );
}

wptm_table_element();

add_action('fusion_builder_before_init', 'wptm_table_element');

/**
 * Declare wptm_chart
 *
 * @return void
 */
function wptm_chart_element()
{
    fusion_builder_map(
        fusion_builder_frontend_data(
            'WptmChart',
            array(
                'name'              => esc_attr__('WP Table Manager Chart', 'wptm'),
                'shortcode'         => 'wptm_chart',
                'icon'              => 'wptm-chart-icon',
                'allow_generator'   => true,
                'inline_editor'     => true,
                'admin_enqueue_css' => plugins_url('app/includes/avada/assets/css/style.css', WPTM_PLUGIN_FILE),
                'preview'           => plugin_dir_path(WPTM_PLUGIN_FILE) . '/app/includes/avada/includes/chart-preview.php',
                'preview_id'        => 'wptm-chart-block-module-preview-template',
                'params'            => array(
                    array(
                        'type'        => 'wptm_chart',
                        'heading'     => esc_attr__('Choose Chart', 'wptm'),
                        'description' => 'Select the WP Table Manager Chart that will be displayed on this page.',
                        'param_name'  => 'wptm_choose_chart',
                    ),
                    array(
                        'type'        => 'textfield',
                        'param_name'  => 'wptm_chart_id',
                        'value'       => ''
                    ),
                    array(
                        'type'        => 'textfield',
                        'param_name'  => 'wptm_selected_chart_random',
                        'value'       => '',
                    ),
                    array(
                        'type'        => 'textfield',
                        'param_name'  => 'wptm_table_id',
                        'value'       => '',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('Chart Title', 'wptm'),
                        'description' => esc_attr__('The title of the selected chart.', 'wptm'),
                        'param_name'  => 'wptm_selected_chart_title',
                        'value'       => '',
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('CSS Class', 'wptm'),
                        'description' => esc_attr__('Add a class to the wrapping HTML element.', 'wptm'),
                        'param_name'  => 'class',
                        'value'       => '',
                        'group'       => esc_attr__('Extras', 'wptm')
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_attr__('CSS ID', 'wptm'),
                        'description' => esc_attr__('Add an ID to the wrapping HTML element.', 'wptm'),
                        'param_name'  => 'id_builder',
                        'value'       => '',
                        'group'       => esc_attr__('Extras', 'wptm'),
                    ),
                )
            )
        )
    );
}

wptm_chart_element();

add_action('fusion_builder_before_init', 'wptm_chart_element');

if (function_exists('fusion_is_element_enabled')
    && (fusion_is_element_enabled('wptm_table') || fusion_is_element_enabled('wptm_chart'))
    && class_exists('Fusion_Element')
    && !class_exists('WptmTableChart')) {
    /**
     * Class WptmTableChart
     */
    class WptmTableChart extends Fusion_Element
    {
        /**
         * An array of the shortcode arguments.
         *
         * @var array
         */
        protected $args;

        /**
         * WptmTable construction
         */
        public function __construct()
        {
            parent::__construct();

            if (fusion_is_element_enabled('wptm_table')) {
                add_shortcode('wptm_table', array($this, 'render'));
            }

            if (fusion_is_element_enabled('wptm_chart')) {
                add_shortcode('wptm_chart', array($this, 'render'));
            }
        }

        /**
         * Render Table
         *
         * @param string|mixed $tableId Table id
         *
         * @return string|mixed
         */
        public function wptmTableShortCode($tableId)
        {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $contentArray = $WptmFilter->replaceTable($tableId, true);
            $content = $contentArray['content'];

            $content .= '<script>
wptm_ajaxurl = "' . esc_url_raw(admin_url("admin-ajax.php?juwpfisadmin=false&action=Wptm&")) . '";
if (document.getElementsByClassName("wptm-avada").length > 0 && typeof window.wptm_render_tables !== "undefined") {
    window.wptm_render_tables.call();
}
</script>';

            wp_enqueue_style('wptm-front', plugins_url('app/site/assets/css/front.css', WPTM_PLUGIN_FILE), array(), time());
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }

            wp_enqueue_style('wptm_daterangepicker', plugins_url('app/site/assets/css/daterangepicker.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            wp_enqueue_style('wptm_datatables', plugins_url('app/site/assets/DataTables/datatables' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            wp_enqueue_style('wptm_tipso', plugins_url('app/site/assets/tipso/tipso' . $min . '.css', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            return $content;
        }

        /**
         * Render chart
         *
         * @param string|mixed $chartId Chart id
         *
         * @return string|mixed
         */
        public function wptmChartShortCode($chartId)
        {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $content = $WptmFilter->replaceChart($chartId);
            $content .= '<script>if (document.getElementsByClassName("wptm-avada").length > 0 && typeof wptm_drawChart !== "undefined") {wptm_drawChart(); }</script>';
            return $content;
        }

        /**
         * Render
         *
         * @param string|mixed $args Param contents
         *
         * @return string|mixed
         */
        public function render($args)
        {
            $atts = (shortcode_atts(array(
                'wptm_table_id' => '',
                'class' => '',
                'id_builder' => '',
                'wptm_chart_id' => '',
                'wptm_selected_table_title' => '',
                'wptm_selected_chart_title' => '',
            ), $args));

            $tableId    = $atts['wptm_table_id'];
            $chartId    = $atts['wptm_chart_id'];
            $class      = $atts['class'];
            $id_builder = $atts['id_builder'];
            $html       = '';

            if ((int)$chartId > 0) {//chart short code
                $result = $this->wptmChartShortCode($chartId);
            } elseif ((int)$tableId > 0) {//table short code
                $result = $this->wptmTableShortCode($tableId);
            } elseif (isset($args['wptm_chart_id'])) {//chart short code
                $result = '<div id="wptm-chart-placeholder" class="wptm-chart-placeholder" style="background: #fafafa;">';
                $result .= '<img style="background: url(' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/wpBakery/assets/images/wptm_placeholder_chart.svg') . ') no-repeat scroll center center #fafafa;background-size: contain;margin-bottom:10px;background-position-y: 0; height: 200px; border-radius: 2px; width: 99%;" src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '"';
                $result .= 'data-mce-src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '"';
                $result .= 'data-mce-style="background: url(' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/wpBakery/assets/images/wptm_placeholder_chart.svg') . ')';
                $result .= 'no-repeat scroll center center #fafafa; height: 200px; border-radius: 2px; width: 99%;background-size: contain;margin-bottom:10px;background-position-y: 0;">';
                $result .= '<span style="display: block;width: 100%;font-size: 13px; text-align: center;">' . esc_attr__('Please select a WP Table Manager content to activate the preview', 'wptm') . '</span>';
                $result .= '</div>';
            } else {//table short code
                $result = '<div id="wptm-table-placeholder" class="wptm-table-placeholder" style="background: #fafafa;">';
                $result .= '<img style="background: url(' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/wpBakery/assets/images/wptm_placeholder_table.svg') . ') no-repeat scroll center center #fafafa;background-size: contain;margin-bottom:10px;background-position-y: 0; height: 200px; border-radius: 2px; width: 99%;" src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '"';
                $result .= 'data-mce-src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '"';
                $result .= 'data-mce-style="background: url(' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/wpBakery/assets/images/wptm_placeholder_table.svg') . ')';
                $result .= 'no-repeat scroll center center #fafafa; height: 200px; border-radius: 2px; width: 99%;background-size: contain;margin-bottom:10px;background-position-y: 0;">';
                $result .= '<span style="display: block;width: 100%;font-size: 13px; text-align: center;">' . esc_attr__('Please select a WP Table Manager content to activate the preview', 'wptm') . '</span>';
                $result .= '</div>';
            }

            $html .= '<div class="wptm-avada '. $class .'" id="' . $id_builder . '">';
            $html .= $result;
            $html .= '</div>';

            return $html;
        }

        /**
         * Sets the necessary scripts.
         *
         * @access public
         * @since  1.1
         * @return void
         */
        public function add_scripts()
        {
            Application::getInstance('Wptm');
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }

            //table
            Fusion_Dynamic_JS::enqueue_script(
                'jquery-wptm-moment',
                plugins_url('app/site/assets/js/moment.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/js/moment.js',
                array(),
                WPTM_VERSION,
                true
            );
            Fusion_Dynamic_JS::enqueue_script(
                'jquery-wptm-daterangepicker',
                plugins_url('app/site/assets/js/daterangepicker.min.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/js/daterangepicker.min.js',
                array(),
                WPTM_VERSION,
                true
            );
            Fusion_Dynamic_JS::enqueue_script('jquery-wptm-jdateformatparser', plugins_url('app/site/assets/js/moment-jdateformatparser.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/js/moment-jdateformatparser.js',
                array(),
                WPTM_VERSION,
                true
            );
            Fusion_Dynamic_JS::enqueue_script(
                'wptm_datatablesJs',
                plugins_url('app/site/assets/DataTables/datatables' . $min . '.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/DataTables/datatables' . $min . '.js',
                array('jquery'),
                WPTM_VERSION,
                true
            );
            Fusion_Dynamic_JS::enqueue_script(
                'wptm_accounting',
                plugins_url('app/admin/assets/plugins/accounting.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/admin/assets/plugins/accounting.js',
                array('jquery'),
                WPTM_VERSION,
                true
            );

            Fusion_Dynamic_JS::enqueue_script(
                'wptm_datatables_fixedColumns_js',
                plugins_url('app/site/assets/DataTables/dataTables.fixedColumns.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/DataTables/dataTables.fixedColumns.js',
                array(),
                WPTM_VERSION,
                true
            );

            Fusion_Dynamic_JS::enqueue_script(
                'wptm_datatables_responsive_js',
                plugins_url('app/site/assets/DataTables/dataTables.responsive.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/DataTables/dataTables.responsive.js',
                array(),
                WPTM_VERSION,
                true
            );
            Fusion_Dynamic_JS::enqueue_script(
                'wptm_tipso',
                plugins_url('app/site/assets/tipso/tipso' . $min . '.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/tipso/tipso' . $min . '.js',
                array('jquery'),
                WPTM_VERSION,
                true
            );
            Fusion_Dynamic_JS::enqueue_script(
                'wptm_table',
                plugins_url('app/site/assets/js/wptm_front.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/js/wptm_front.js',
                array('jquery', 'wptm_datatablesJs'),
                WPTM_VERSION,
                true
            );

            //chart
            Fusion_Dynamic_JS::enqueue_script(
                'wptm_chart',
                plugins_url('app/admin/assets/js/Chart.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/admin/assets/js/Chart.js',
                array('jquery'),
                WPTM_VERSION
            );
            Fusion_Dynamic_JS::enqueue_script(
                'wptm_dropchart',
                plugins_url('app/site/assets/js/dropchart.js', WPTM_PLUGIN_FILE),
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/js/dropchart.js',
                array('jquery', 'wptm_chart'),
                WPTM_VERSION
            );
        }

        /**
         * Load base CSS.
         *
         * @return void
         */
        public function add_css_files()
        {
            Application::getInstance('Wptm');
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }
            Fusion_Dynamic_CSS::enqueue_style(
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/css/front.css',
                plugins_url('app/site/assets/css/front.css', WPTM_PLUGIN_FILE)
            );
            Fusion_Dynamic_CSS::enqueue_style(
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/DataTables/datatables' . $min . '.css',
                plugins_url('app/site/assets/DataTables/datatables' . $min . '.css', WPTM_PLUGIN_FILE)
            );
            Fusion_Dynamic_CSS::enqueue_style(
                plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/assets/tipso/tipso' . $min . '.css',
                plugins_url('app/site/assets/tipso/tipso' . $min . '.css', WPTM_PLUGIN_FILE)
            );
        }
    }

    new WptmTableChart();
}

/**
 * Add custom fields
 *
 * @param string|mixed $field_types Field types
 *
 * @return string|mixed
 */
function wptm_custom_fields($field_types)
{
    $field_types['wptm_table'] = array(
        'wptm_table',
        plugin_dir_path(WPTM_PLUGIN_FILE) . '/app/includes/avada/includes/wptm_custom_fields.php'
    );

    $field_types['wptm_chart'] = array(
        'wptm_chart',
        plugin_dir_path(WPTM_PLUGIN_FILE) . '/app/includes/avada/includes/wptm_custom_fields_chart.php'
    );

    return $field_types;
}

add_filter('fusion_builder_fields', 'wptm_custom_fields', 10, 1);
