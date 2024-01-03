function wptm_table_bakery_trigger_controles() {
    jQuery('div.vc_active input.vc_param-name-wptm_selected_table_id', window.parent.document).trigger('input');
    jQuery('div.vc_active input.vc_param-name-wptm_table_random', window.parent.document).trigger('input');
    jQuery('div.vc_active input.wptm_table_title', window.parent.document).trigger('input');
    jQuery('div.vc_active input.wptm_table-field[name="content"]', window.parent.document).trigger('input');

    //chart
    jQuery('div.vc_active input.vc_param-name-wptm_selected_chart_id', window.parent.document).trigger('input');
    jQuery('div.vc_active input.vc_param-name-wptm_table_chart_id', window.parent.document).trigger('input');
    jQuery('div.vc_active input.vc_param-name-wptm_chart_random', window.parent.document).trigger('input');
    jQuery('div.vc_active input.wptm_chart_title', window.parent.document).trigger('input');
    jQuery('div.vc_active input.wptm_chart-field[name="content"]', window.parent.document).trigger('input');
}