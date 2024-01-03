function wptm_table_avada_trigger_controles() {
    jQuery('div.fusion-tab-content.active input#wptm_table_id', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion-tab-content.active input#wptm_selected_table_random', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion-tab-content.active input#wptm_selected_table_title', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion-tab-content.active input#type_table', window.parent.document).trigger('input').trigger('change');

    jQuery('div.fusion_builder_module_settings input#wptm_table_id', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion_builder_module_settings input#wptm_selected_table_random', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion_builder_module_settings input#wptm_selected_table_title', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion_builder_module_settings input#type_table', window.parent.document).trigger('input').trigger('change');

    //chart
    jQuery('div.fusion-tab-content.active input#wptm_chart_id', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion-tab-content.active input#wptm_table_id', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion-tab-content.active input#wptm_selected_chart_random', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion-tab-content.active input#wptm_selected_chart_title', window.parent.document).trigger('input').trigger('change');

    jQuery('div.fusion_builder_module_settings input#wptm_table_id', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion_builder_module_settings input#wptm_chart_id', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion_builder_module_settings input#wptm_selected_chart_random', window.parent.document).trigger('input').trigger('change');
    jQuery('div.fusion_builder_module_settings input#wptm_selected_chart_title', window.parent.document).trigger('input').trigger('change');
}