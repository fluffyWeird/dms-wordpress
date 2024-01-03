function wptm_frameload() {
    // jQuery("#wptm_loader").hide();
    jQuery("#wptmmodalframe").css('visibility', "visible");
    jQuery("#wptmmodalframe").show();
    jQuery('#wptmmodal').removeClass('loadding');
}

jQuery(document).ready(function ($) {
    $('.wptmlaunch').wptm_leanModal({
        top: 20, beforeShow: function () {
            $("#wptmmodal").css("height", "90%");
            $("#wptmmodalframe").css('visibility', 'hidden');
            $("#wptmmodalframe").attr('src', $("#wptmmodalframe").attr('src'));
            // $("#wptm_loader").show();
        }
    });

    var src = 'admin.php?page=wptm&noheader=1&caninsert=1';
    if (typeof wptm_avada !== 'undefined') {
        src = wptm_avada.wptm_view + 'admin.php?page=wptm&noheader=1&caninsert=1';
    }

    $('body').append('<div id="wptmmodal" class="loadding">' +
        // '<img src="images/spinner-2x.gif" width="32" id="wptm_loader" />' +
        '<iframe id="wptmmodalframe" onload="wptm_frameload()"  width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" src="' + src + '">' +
        '</iframe><button id="wptm-close-modal" onclick="jQuery(\'#lean_overlay\',window.parent.document).fadeOut(300);jQuery(\'#wptmmodal\',window.parent.document).fadeOut(300);" style="position: absolute; right: -23px;">' +
        'x</button>' +
        '</div>');

    $('body').on("click", ".wptmlaunch", function (e) {
        $("#wptmmodal").css("height", "90%");
        $("#wptmmodalframe").css('visibility', 'hidden');
        $("#wptmmodalframe").attr('src', $("#wptmmodalframe").attr('src'));
        jQuery('#wptmmodal').addClass('loadding');

        var modal_id = $(this).attr("href");
        var type_insert = $(this).data("type");

        if (typeof window.wptm_insert === 'undefined') {
            window.wptm_insert = {};
        } else {
            delete window.wptm_insert.table;
            delete window.wptm_insert.chart;
            delete window.wptm_insert.wptm_type_insert;
        }
        //elementor
        if (typeof type_insert !== 'undefined') {
            if (typeof window.wptmmodalframe_src === 'undefined') {
                window.wptmmodalframe_src = document.getElementById('wptmmodalframe').src;
            }

            var url_wptmmodalframe_src = window.wptmmodalframe_src;
            if (type_insert === 'table') {
                if (jQuery('.wptm-table-id-controls input[data-setting="wptm_table_id"]').length > 0) {
                    window.wptm_insert.table = jQuery('.wptm-table-id-controls input[data-setting="wptm_table_id"]').val();
                } else if (jQuery('div.vc_active input.vc_param-name-wptm_selected_table_id').length > 0) {//bakery editor
                    window.wptm_insert.table = jQuery('div.vc_active input.vc_param-name-wptm_selected_table_id').val();
                } else if (jQuery('.fusion-builder-option input#wptm_table_id').length > 0) {//avada editor
                    window.wptm_insert.table = jQuery('.fusion-builder-option input#wptm_table_id').val();
                }
                if (typeof window.wptm_insert.opend_table !== 'undefined') {
                    document.getElementById('wptmmodalframe').src = url_wptmmodalframe_src + '&id_table=' + window.wptm_insert.table;
                } else {
                    document.getElementById('wptmmodalframe').src = url_wptmmodalframe_src;
                }
            } else if (type_insert === 'chart') {
                if (jQuery('.wptm-table-chart-id-controls input[data-setting="wptm_table_chart_id"]').length > 0) {
                    window.wptm_insert.table = jQuery('.wptm-table-chart-id-controls input[data-setting="wptm_table_chart_id"]').val();
                    window.wptm_insert.chart = jQuery('.wptm-chart-id-controls input[data-setting="wptm_chart_id"]').val();
                } else if (jQuery('div.vc_active input.vc_param-name-wptm_selected_chart_id').length > 0) {//bakery editor
                    window.wptm_insert.table = jQuery('div.vc_active input.vc_param-name-wptm_table_chart_id').val();
                    window.wptm_insert.chart = jQuery('div.vc_active input.vc_param-name-wptm_selected_chart_id').val();
                } else if (jQuery('.fusion-builder-option input#wptm_chart_id').length > 0) {//avada editor
                    window.wptm_insert.table = jQuery('.fusion-builder-option input#wptm_table_id').val();
                    window.wptm_insert.chart = jQuery('.fusion-builder-option input#wptm_chart_id').val();
                }

                if (typeof window.wptm_insert.opend_table !== 'undefined') {
                    document.getElementById('wptmmodalframe').src = url_wptmmodalframe_src + '&id_table=' + window.wptm_insert.table + '&chart=' + window.wptm_insert.chart;
                } else {
                    document.getElementById('wptmmodalframe').src = url_wptmmodalframe_src;
                }
            }

            window.wptm_insert.wptm_type_insert = type_insert;
        }

        //var modal_height=$(modal_id).outerHeight();
        var modal_width = $(modal_id).outerWidth();
        $("#lean_overlay").css({"display": "block", opacity: 0});
        $("#lean_overlay").fadeTo(200, 0.5);
        $(modal_id).css({
            "visibility": "visible",
            "display": "block",
            "text-align": "center",
            "position": "fixed",
            "opacity": 0,
            "z-index": 100102,
            "left": 50 + "%",
            "margin-left": -(modal_width / 2) + "px",
            "top": "20px",
            "padding": '5px'
        });
        $(modal_id).fadeTo(200, 1);

        /*check Elementor editor*/
        if ($(this).hasClass('elementor_edit')) {
            window.wptm_elementor_edit = true;
        }
        /*check bakery editor*/
        if ($(this).hasClass('bakery_edit')) {
            window.wptm_bakery_edit = true;
        }
        /*check avada editor*/
        if ($(this).hasClass('avada_edit')) {
            window.wptm_vada_edit = true;
        }
    });

    return false;
});
