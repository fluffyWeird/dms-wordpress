function wptm_table_widget_trigger_controles() {
    jQuery('.wptm-table-id-controls input[data-setting="wptm_table_id"]', window.parent.document).trigger('input');
    jQuery('.wptm-table-name-controls input[data-setting="wptm_table_name"]', window.parent.document).trigger('input');

    jQuery('.wptm-table-chart-id-controls input[data-setting="wptm_table_chart_id"]', window.parent.document).trigger('input');
    jQuery('.wptm-chart-id-controls input[data-setting="wptm_chart_id"]', window.parent.document).trigger('input');
    jQuery('.wptm-chart-name-controls input[data-setting="wptm_chart_name"]', window.parent.document).trigger('input');
}

function wptm_table_widget_display_control() {
    var $container = this.find('.elementor-widget-container');

    if (typeof window.wptm_insert === 'undefined') {
        window.wptm_insert = {};
    }
    if (typeof window.wptm_insert.opend_table !== 'undefined') {
        delete window.wptm_insert.opend_table;
    }

    setTimeout(function() {
        if ($container.find('.wptm_table').length > 0 || $container.find('.chartContainer').length > 0) {//show
            jQuery('#elementor-panel-content-wrapper .elementor-control-wptm_table_old', window.parent.document).addClass('show');
        } else {//hidden
            jQuery('#elementor-panel-content-wrapper .elementor-control-wptm_table_old', window.parent.document).removeClass('show');
        }

        jQuery('#elementor-panel-content-wrapper .elementor-control-wptm_table_old #wptmReLaunch', window.parent.document).click(function () {
            window.wptm_insert.opend_table = true;
            if (jQuery(this).data('type') === 'chart') {
                jQuery('.elementor-control-wptm_chart #wptmlaunch', window.parent.document).trigger('click');
            } else {//open table
                jQuery('.elementor-control-wptm_table #wptmlaunch', window.parent.document).trigger('click');
            }
        });
    }, 200);
}
