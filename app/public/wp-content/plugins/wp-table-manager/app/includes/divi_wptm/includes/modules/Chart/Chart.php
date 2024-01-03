<?php

/**
 * Class WPTM_Chart
 */
class WPTM_Chart extends ET_Builder_Module
{

    public $slug = 'wptm_chart';
    public $vb_support = 'on';

    protected $module_credits = array(
        'module_uri' => 'https://www.joomunited.com/',
        'author' => 'Joomunited',
        'author_uri' => 'https://www.joomunited.com/',
    );

    /**
     * Init function
     *
     * @return void
     */
    public function init()
    {
        $this->name = esc_html__('WPTM Chart', 'wptm');
    }

    /**
     * Advanced Fields Config
     *
     * @return array
     */
    public function get_advanced_fields_config()
    {
        return array(
            'button' => false,
            'link_options' => false
        );
    }

    /**
     * Get Fields
     *
     * @return array
     */
    public function get_fields()
    {
        return array(
            'wptm_chart_params' => array(
                'label' => sprintf(esc_html__('Choose Chart', 'wptm'), '#1'),
                'type' => 'wptm_input',
                'option_category' => 'configuration',
                'default_on_front' => 'root',
                'class' => 'wptm-input-module-chart'
            )
        );
    }

    /**
     * Render Contents
     *
     * @param array|mixed $attrs       Attributes
     * @param array|mixed $content     Contents
     * @param array|mixed $render_slug Slug
     *
     * @return mixed|array
     */
    public function render($attrs, $content, $render_slug)
    {
        $tableParams = $this->props['wptm_chart_params'];

        if ($tableParams !== 'root') {
            $tableParams = json_decode($tableParams);
            $tableId = $tableParams->selected_chart_id;
            if (($tableId) > 0) {
                return do_shortcode('[wptm id-chart="' . $tableId . '"]');
            }
        }
    }
}

new WPTM_Chart;
