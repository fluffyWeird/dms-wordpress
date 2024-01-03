jQuery(document).ready(function ($) {
    $wptm_bottom_toolbar = $('#wptm_bottom_toolbar');
    $wptm_table_list = $('#mybootstrap .wptm_table_list');

    $('#wpfooter').hide();

    if (typeof window.parent.wptm_insert !== 'undefined' && typeof window.parent.wptm_insert.wptm_type_insert !== 'undefined') {
        if (window.parent.wptm_insert.wptm_type_insert === 'table') {
            $wptm_bottom_toolbar.find('#inserttable').addClass('not_change_type').data('type', 'table').attr('data-type', 'table').text(insert_table);
        } else {
            $wptm_bottom_toolbar.find('#inserttable').addClass('not_change_type').data('type', 'chart').attr('data-type', 'chart').text(insert_chart);
        }
    }

    if ($('#mybootstrap').hasClass('wptm-tables')) {//tables page
        $('body').css({overflow: 'hidden'});

        $wptm_bottom_toolbar.appendTo($wptm_table_list);
        if ($wptm_table_list.find('.dd-item.selected').length < 1) {
            $wptm_bottom_toolbar.find('#inserttable').addClass("no_click");
        }

        $wptm_bottom_toolbar.find('#inserttable').hover(function () {
            if (Wptm.table > 0) {
                $wptm_bottom_toolbar.find('#inserttable').removeClass("no_click");
                if ($(this).data('type') === 'chart' && parseInt(chart_active) <= 0) {
                    $wptm_bottom_toolbar.find('#inserttable').addClass("no_click");
                }
            } else {
                $wptm_bottom_toolbar.find('#inserttable').addClass("no_click");
            }
        });

        $wptm_bottom_toolbar.find('.wptm_back_list').hide();
        $wptm_bottom_toolbar.find('#inserttable').on("click", function () {
            inserttable($(this));
        });
    } else if($('#mybootstrap').hasClass('wptm-page')) {//table editor
        $wptm_bottom_toolbar.find('#inserttable').on("click", function () {
            inserttable($(this));
        });
        var Scrollbar = window.Scrollbar;
        Scrollbar.initAll({
            damping: 0.5,
            thumbMinSize: 10,
            alwaysShowTracks: true
        });
    } else if($('#mybootstrap').hasClass('wptm-db')) {//db table config
        $wptm_bottom_toolbar.find('#inserttable').on("click", function () {
            inserttable($(this));
        });
    }

    /**
     * Insert the current table into a content editor
     */
    function inserttable (that) {
        var id;
        var is_elementor = false;
        var is_wptm_bakery_edit = false;
        var is_wptm_avada = false;
        if (typeof window.parent.wptm_elementor_edit !== 'undefined' && window.parent.wptm_elementor_edit) {
            is_elementor = true;
        }
        if (typeof window.parent.wptm_bakery_edit !== 'undefined' && window.parent.wptm_bakery_edit) {
            is_wptm_bakery_edit = true;
        }
        if (typeof window.parent.wptm_vada_edit !== 'undefined' && window.parent.wptm_vada_edit) {
            is_wptm_avada = true;
        }

        if (that.data('type') === 'chart') {
            if (typeof Wptm !== 'undefined' && parseInt(Wptm.chart_active) > 0) {
                chart_active = Wptm.chart_active;
            }
            if (parseInt(chart_active) > 0) {
                var table_id = typeof Wptm.id !== 'undefined' ? Wptm.id : Wptm.table;
                var chart_name;
                if (is_elementor) {
                    chart_name = typeof Wptm.dataChart !== 'undefined' ? Wptm.dataChart[chart_active].title : jQuery('#list_tables').find('.tbody[data-id="' + chart_active + '"]').find('a').text();
                    jQuery(".wptm-table-chart-id-controls", window.parent.document).find('input').val(table_id);
                    jQuery(".wptm-chart-id-controls", window.parent.document).find('input').val(chart_active);
                    jQuery(".wptm-chart-name-controls", window.parent.document).find('input').val(chart_name);

                    window.parent.wptm_table_widget_trigger_controles();
                } else if (is_wptm_bakery_edit) {//bakery editor
                    jQuery('div.vc_active input.vc_param-name-wptm_selected_chart_id', window.parent.document).val(chart_active);
                    jQuery('div.vc_active input.vc_param-name-wptm_table_chart_id', window.parent.document).val(table_id);
                    jQuery('div.vc_active input.vc_param-name-wptm_chart_random', window.parent.document).val(Math.random());
                    var chartName = '<!-- wp:paragraph --><p class="wptm-no-content">' + wptmContextBakery.bakeryChartText + '</p><!-- /wp:paragraph -->';

                    chart_name = typeof Wptm.dataChart !== 'undefined' ? Wptm.dataChart[chart_active].title : jQuery('#list_tables').find('.tbody[data-id="' + chart_active + '"]').find('a').text();
                    if (typeof chart_name !== 'undefined' && chart_name !== '') {
                        jQuery('div.vc_active input.wptm_chart_title', window.parent.document).val(chart_name);
                        chartName = '<div class="selected-chart-control-section wptm_chart"><span style="font-weight: bold">Chart Title: </span>' + chart_name + '</div>';
                    }
                    jQuery('div.vc_active input.wptm_chart-field[name="content"]', window.parent.document).val(chartName);
                    window.parent.wptm_table_bakery_trigger_controles();
                } else if (is_wptm_avada) {//avada editor
                    chart_name = typeof Wptm.dataChart !== 'undefined' ? Wptm.dataChart[chart_active].title : jQuery('#list_tables').find('.tbody[data-id="' + chart_active + '"]').find('a').text();

                    if (jQuery('div.fusion_builder_module_settings input#wptm_selected_chart_title', window.parent.document).length > 0) {//backend
                        jQuery('div.fusion_builder_module_settings input#wptm_chart_id', window.parent.document).val(chart_active);
                        jQuery('div.fusion_builder_module_settings input#wptm_table_id', window.parent.document).val(table_id);
                        jQuery('div.fusion_builder_module_settings input#wptm_selected_chart_random', window.parent.document).val(Math.random());
                        if (typeof chart_name !== 'undefined' && chart_name !== '') {
                            jQuery('div.fusion_builder_module_settings input#wptm_selected_chart_title', window.parent.document).val(chart_name);
                        }
                    } else {
                        jQuery('div.fusion-tab-content.active input#wptm_chart_id', window.parent.document).val(chart_active);
                        jQuery('div.fusion-tab-content.active input#wptm_table_id', window.parent.document).val(table_id);
                        jQuery('div.fusion-tab-content.active input#wptm_selected_chart_random', window.parent.document).val(Math.random());
                        if (typeof chart_name !== 'undefined' && chart_name !== '') {
                            jQuery('div.fusion-tab-content.active input#wptm_selected_chart_title', window.parent.document).val(chart_name);
                        }
                    }
                    window.parent.wptm_table_avada_trigger_controles();
                } else {
                    code = '<img src="' + wptm_dir + '/app/admin/assets/images/t.gif"' +
                        ' data-wptmtable="' + table_id + '"' +
                        ' data-wptm-chart="' + chart_active + '"' +
                        'style="background: url(' + wptm_dir + '/app/admin/assets/images/chart.svg) no-repeat scroll center center #D6D6D6;' +
                        'border: 2px dashed #888888;' +
                        'background-size: contain;' +
                        'height: 150px;' +
                        'border-radius: 10px;' +
                        'width: 99%;" />';
                    window.parent.tinyMCE.execCommand('mceInsertContent', false, code);
                }
            }
        } else {
            var table = $wptm_table_list.find('.dd-item.selected');
            if (table.length < 1 && typeof Wptm !== 'undefined' &&  typeof Wptm.id !== 'undefined') {
                table = {0: Wptm.id};
            }

            if (typeof constructedTableData !== 'undefined' && parseInt(constructedTableData.id_table) > 0) {
                table = {0: constructedTableData.id_table};
            }

            $.each(table, function () {
                if (parseInt(this) > 0) {
                    id = this;
                } else {
                    id = $(this).data('id-table');
                }

                if (is_elementor) {
                    jQuery(".wptm-table-id-controls", window.parent.document).find('input').val(id);
                    if (typeof Wptm.title !== 'undefined') {
                        jQuery(".wptm-table-name-controls", window.parent.document).find('input').val(Wptm.title);
                    } else {
                        jQuery(".wptm-table-name-controls", window.parent.document).find('input').val(Wptm.tables[id].title);
                    }
                    window.parent.wptm_table_widget_trigger_controles();
                } else if (is_wptm_bakery_edit) {//bakery editor
                    jQuery('div.vc_active input.vc_param-name-wptm_selected_table_id', window.parent.document).val(id);
                    jQuery('div.vc_active input.vc_param-name-wptm_table_random', window.parent.document).val(Math.random());
                    var tableName = '<!-- wp:paragraph --><p class="wptm-no-content">' + wptmContextBakery.bakeryTableText + '</p><!-- /wp:paragraph -->';
                    if (typeof Wptm.title !== 'undefined') {
                        jQuery('div.vc_active input.wptm_table_title', window.parent.document).val(Wptm.title);
                        if (Wptm.type === 'mysql') {
                            tableName = '<div class="selected-table-control-section wptm_mysql_table"><span style="font-weight: bold">Table Title: </span>' + Wptm.title + '</div>';
                        } else {
                            tableName = '<div class="selected-table-control-section wptm_table_html"><span style="font-weight: bold">Table Title: </span>' + Wptm.title + '</div>';
                        }
                    } else if (typeof Wptm.tables[id].title !== 'undefined') {
                        jQuery('div.vc_active input.wptm_table_title', window.parent.document).val(Wptm.tables[id].title);
                        if (Wptm.tables[id].type === 'mysql') {
                            tableName = '<div class="selected-table-control-section wptm_mysql_table"><span style="font-weight: bold">Table Title: </span>' + Wptm.tables[id].title + '</div>';
                        } else {
                            tableName = '<div class="selected-table-control-section wptm_table_html"><span style="font-weight: bold">Table Title: </span>' + Wptm.tables[id].title + '</div>';
                        }
                    }
                    jQuery('div.vc_active input.wptm_table-field[name="content"]', window.parent.document).val(tableName);
                    window.parent.wptm_table_bakery_trigger_controles();
                }  else if (is_wptm_avada) {//avada editor
                    if (jQuery('div.fusion_builder_module_settings input#wptm_selected_table_title', window.parent.document).length > 0) {//backend
                        jQuery('div.fusion_builder_module_settings input#wptm_table_id', window.parent.document).val(id);
                        jQuery('div.fusion_builder_module_settings input#wptm_selected_table_random', window.parent.document).val(Math.random());
                        if (typeof Wptm.title !== 'undefined') {
                            jQuery('div.fusion_builder_module_settings input#wptm_selected_table_title', window.parent.document).val(Wptm.title);
                            jQuery('div.fusion_builder_module_settings input#type_table', window.parent.document).val(Wptm.type);
                        } else if (typeof Wptm.tables[id].title !== 'undefined') {
                            jQuery('div.fusion_builder_module_settings input#wptm_selected_table_title', window.parent.document).val(Wptm.tables[id].title);
                            jQuery('div.fusion_builder_module_settings input#type_table', window.parent.document).val(Wptm.tables[id].type);
                        }
                    } else {
                        jQuery('div.fusion-tab-content.active input#wptm_table_id', window.parent.document).val(id);
                        jQuery('div.fusion-tab-content.active input#wptm_selected_table_random', window.parent.document).val(Math.random());
                        if (typeof Wptm.title !== 'undefined') {
                            jQuery('div.fusion-tab-content.active input#wptm_selected_table_title', window.parent.document).val(Wptm.title);
                            jQuery('div.fusion-tab-content.active input#type_table', window.parent.document).val(Wptm.type);
                        } else if (typeof Wptm.tables[id].title !== 'undefined') {
                            jQuery('div.fusion-tab-content.active input#wptm_selected_table_title', window.parent.document).val(Wptm.tables[id].title);
                            jQuery('div.fusion-tab-content.active input#type_table', window.parent.document).val(Wptm.tables[id].type);
                        }
                    }
                    window.parent.wptm_table_avada_trigger_controles();
                } else {
                    var code = '<img src="' + wptm_dir + '/app/admin/assets/images/t.gif"' +
                        'data-wptmtable="' + id + '"' +
                        'style="background: url(' + wptm_dir + '/app/admin/assets/images/spreadsheet.svg) no-repeat scroll center center #D6D6D6; ' +
                        'border: 2px dashed #888888; ' +
                        'background-size: contain; ' +
                        'height: 150px; ' +
                        'border-radius: 10px; ' +
                        'width: 99%;" />';
                    window.parent.tinyMCE.execCommand('mceInsertContent', false, code);
                }
            });

        }
        jQuery("#lean_overlay", window.parent.document).fadeOut(300);
        jQuery('#wptmmodal', window.parent.document).fadeOut(300);
        return false;
    }
});