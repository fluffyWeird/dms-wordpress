<?php
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

if (!class_exists('WptmBakeryTable')) {
    /**
     * Class WptmBakeryTable
     */
    class WptmBakeryTable extends WPBakeryShortCode
    {
        /**
         * Construction function
         */
        public function __construct()
        {
            add_action('init', array($this, 'wptmCreateShortcode'), 999);
            add_shortcode('wptm_table_shortcode', array($this, 'wptmRenderShortcode'));
        }

        /**
         * Create shortCode
         *
         * @return void
         * @throws Exception Fire when errors
         */
        public function wptmCreateShortcode()
        {
            if (!defined('WPB_VC_VERSION')) {
                return;
            }

            $this->wptmBakeryInitCustomField();

            vc_map(
                array(
                    'name' => __('WP Table Manager Table', 'wptm'),
                    'base' => 'wptm_table_shortcode',
                    'class' => 'wptm_table_shortcode',
                    'description' => __('WP Table Manager element', 'wptm'),
                    'category' => __('JoomUnited', 'wptm'),
                    'icon' => 'wptm-table-icon',
                    'params' => array(
                        array(
                            'type' => 'wptm_table',
                            'class' => 'wptm-choose-table-control',
                            'holder' => 'div',
                            'heading' => __('Choose Table', 'wptm'),
                            'param_name' => 'content',
                            'value' => '<!-- wp:paragraph -->
<p class="wptm-no-content">' .  __('Hey there, load and edit the WP Table Manager tables from here.', 'wptm') . '</p><!-- /wp:paragraph -->',
                            'description' => __('Select the WP Table Manager Table that will be displayed on this page.', 'wptm'),
                        ),

                        array(
                            'type' => 'hidden',
                            'param_name' => 'wptm_table_random',
                            // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings -- This is for set init
                            'value' => __('', 'wptm')
                        ),

                        array(
                            'type' => 'hidden',
                            'class' => 'wptm-selected-table-id-control',
                            'param_name' => 'wptm_selected_table_id',
                            // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings -- This is for set init
                            'value' => __('', 'wptm')
                        ),

                        array(
                            'type' => 'textfield',
                            'class' => 'wptm-table-title-control',
                            'heading' => __('Table Title: ', 'wptm'),
                            'param_name' => 'wptm_table_title',
                            // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings -- This is for set init
                            'value' => __('', 'wptm'),
                            'description' => __('The title of the selected table.', 'wptm'),
                        ),

                        array(
                            'type' => 'textfield',
                            'heading' => __('Element ID', 'wptm'),
                            'param_name' => 'wptm_element_id',
                            // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings -- This is for set init
                            'value' => __('', 'wptm'),
                            'description' => __('Enter element ID (Note: make sure it is unique and valid).', 'wptm'),
                            'group' => __('Extra', 'wptm'),
                        ),

                        array(
                            'type' => 'textfield',
                            'heading' => __('Extra class name', 'wptm'),
                            'param_name' => 'wptm_table_class',
                            // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings -- This is for set init
                            'value' => __('', 'wptm'),
                            'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'wptm'),
                            'group' => __('Extra', 'wptm'),
                        ),

                        array(
                            'type' => 'css_editor',
                            'heading' => esc_html__('CSS box', 'wptm'),
                            'param_name' => 'css',
                            'group' => esc_html__('Design Options', 'wptm'),
                        )
                    )
                )
            );
        }

        /**
         * Render shortCode
         *
         * @param array|mixed $atts Attributes
         *
         * @return string|mixed
         * @throws Exception Fire when errors
         */
        public function wptmRenderShortcode($atts)
        {
            $atts = (shortcode_atts(array(
                'wptm_selected_table_id' => '',
                'wptm_table_random' => '',
                'wptm_table_class' => '',
                'wptm_element_id' => '',
                'css' => ''
            ), $atts));

            $table_selected_id = esc_attr($atts['wptm_selected_table_id']);
            $table_class = esc_attr($atts['wptm_table_class']);
            $table_id = esc_attr($atts['wptm_element_id']);
            $css_animation = esc_attr($atts['css']);
            $css_animation_class = vc_shortcode_custom_css_class($css_animation, ' ');
            $result = '';

            if ($table_selected_id === '') {
                $result .= '<div id="wptm-table-placeholder" class="wptm-table-placeholder">';
                $result .= '<img style="background: url(' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/wpBakery/assets/images/wptm_placeholder_table.svg') . ') no-repeat scroll center center #fafafa;background-size: contain;margin-bottom:10px;background-position-y: 0; height: 200px; border-radius: 2px; width: 99%;" src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '"';
                $result .= 'data-mce-src="' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/t.gif') . '"';
                $result .= 'data-mce-style="background: url(' . esc_url(WP_TABLE_MANAGER_PLUGIN_URL . 'app/includes/wpBakery/assets/images/wptm_placeholder_table.svg') . ')';
                $result .= 'no-repeat scroll center center #fafafa; height: 200px; border-radius: 2px; width: 99%;background-position-y: 0;background-size: contain;margin-bottom:10px;">';
                $result .= '<span style="font-size: 13px; text-align: center;">' . esc_attr__('Please select a WP Table Manager content to activate the preview', 'wptm') . '</span>';
                $result .= '</div>';
            } else {
                $result = $this->wptmBakeryTableShortCode($table_selected_id);
            }

            $output = '';
            $output .= '<div class="wptm-wpbakery-container ' . $table_class . ' ' . $css_animation_class . '" id="' . $table_id . '" >';
            $output .= $result;
            $output .= '</div>';

            return $output;
        }

        /**
         * Init custom field
         *
         * @return void
         */
        public function wptmBakeryInitCustomField()
        {
            require_once(WPTM_PLUGIN_DIR . '/app/includes/wpBakery/params/wptm.php');

            $wpbakeryshortcodeparamspath = vc_path_dir('PARAMS_DIR', '/params.php');
            include_once $wpbakeryshortcodeparamspath;
            $shortcodeparams = new WpbakeryShortcodeParams();
            global $vc_params_list;
            if (isset($vc_params_list)) {
                array_push($vc_params_list, 'wptm_table');
            }

            if (isset($shortcodeparams)) {
                $name = 'wptm_table';
                $form_field_callback = 'vc_wptm_table_form_field';
                $shortcodeparams->addField($name, $form_field_callback);
            }
        }

        /**
         * Render table
         *
         * @param string|mixed $tableId Table id
         *
         * @return string|mixed
         * @throws Exception Fire when errors
         */
        public function wptmBakeryTableShortCode($tableId)
        {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $content = $WptmFilter->replaceTable($tableId);
            $content .= '<script>if (document.getElementsByClassName("vc_editor").length > 0) {window.wptm_render_tables.call();}</script>';
            return $content;
        }
    }

    new WptmBakeryTable();
}
