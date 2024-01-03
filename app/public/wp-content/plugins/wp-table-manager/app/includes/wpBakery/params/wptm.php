<?php

/**
 * Wptm table field param.
 *
 * @param string|array|mixed $settings Setting params
 * @param string|array|mixed $value    Field value
 *
 * @return string - html string.
 */
function vc_wptm_table_form_field($settings, $value)
{
    //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
    $value  = htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $result = '<div id="wptm-wpbakery-choose-table-section" class="wptm-wpbakery-choose-table-section">';
    $result .= '<a href="#wptmmodal" class="button wptmlaunch bakery_edit" id="wptmlaunch" data-type="table" title="WP Table Manager">';
    $result .= ' <span class="dashicons" style="line-height: inherit;"></span><span>' . esc_attr__('WP Table Manager', 'wptm') . '</span></a>';
    $result .= '<input name="' . $settings['param_name'] . '" class="wpb_vc_param_value wptm_table-field vc_param-name-' . $settings['param_name'] . ' ' . $settings['type'] . '" type="hidden" value="' . $value . '"/>';
    $result .= '</div>';

    return $result;
}
/**
 * Wptm chart field param.
 *
 * @param string|array|mixed $settings Setting params
 * @param string|array|mixed $value    Field value
 *
 * @return string - html string.
 */
function vc_wptm_chart_form_field($settings, $value)
{
    //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
    $value  = htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $result = '<div id="wptm-wpbakery-choose-table-section" class="wptm-wpbakery-choose-table-section">';
    $result .= '<a href="#wptmmodal" class="button wptmlaunch bakery_edit" id="wptmlaunch" data-type="chart" title="WP Table Manager">';
    $result .= ' <span class="dashicons" style="line-height: inherit;"></span><span>' . esc_attr__('WP Table Manager', 'wptm') . '</span></a>';
    $result .= '<input name="' . $settings['param_name'] . '" class="wpb_vc_param_value wptm_chart-field vc_param-name-' . $settings['param_name'] . ' ' . $settings['type'] . '" type="hidden" value="' . $value . '"/>';
    $result .= '</div>';

    return $result;
}
