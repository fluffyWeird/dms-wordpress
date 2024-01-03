import wptmPreview from "./_wptm";
import tableFunction from "./_functions";
import change_theme from "./_changeTheme";
import alternating from "./_alternating";
import DropChart from "./_chart";

//Control functions when select table items
const selectOption = function () {
    var wptm_element = window.wptm_element;
    var popupOption = {};
    var render = false;
    var autoRender;
    var check_saving;
    var new_col_types = [];
    var changed_cols = [];

    if (!(window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author))) {
        return false;
    }

    if (Wptm.max_Col * Wptm.max_row < 1) {
        return false;
    }

    custom_select_box.call(window.jquery('#setting-cells .wptm_select_box_before'), window.jquery);

    // jquery(Wptm.container).data('handsontable').getPlugin('hiddenColumns').query(textSearch.val());

    wptm_element.primary_toolbars.find('.table_option:not(.menu_loading)').unbind('change click').on('change click', function (e) {
        var function_data = window.table_function_data;
        var Wptm = window.Wptm;
        var $ = window.jquery;

        if ($(this).parent('li.no_active').length > 0) {
            return false;
        }

        var popup = $.extend({}, {});
        var html = '';
        //for undo
        // var oldStyle = JSON.parse(JSON.stringify(Wptm.style));
        //for mergecells
        // var ht = Wptm.container.handsontable('getInstance');

        var selection = {};
        selection = table_function_data.selection;

        /*click cell*/
        switch ($(this).attr('name')) {
            case 'rename_menu':
                // Wptm.container.handsontable('updateSettings', {
                //     hiddenColumns: {
                //         columns: [2, 3],
                //         indicators: true
                //     }
                // });
                // var ht = jquery(Wptm.container).handsontable('getInstance');
                // var hiddenColumnsPlugin = ht.getPlugin('hiddenColumns');
                // hiddenColumnsPlugin.hideColumn(1, 4);
                tableFunction.setText.call(
                    $(this),
                    wptm_element.primary_toolbars.find('.wptm_name_edit'),
                    '#primary_toolbars .wptm_name_edit',
                    {'url': wptm_ajaxurl + "task=table.setTitle&id=" + Wptm.id + '&title=', 'selected': true}
                );
                break;
            case 'cell_insertion_shortcode':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#cell_insertion_shortcode'),
                    'showAction': function () {
                        var $filter = this.find('#cell_insertion_shortcode_filter');
                        var $sort = this.find('#cell_insertion_shortcode_sort');
                        var $download = this.find('#cell_insertion_shortcode_download');
                        var $shortcode = this.find('.shortcode');
                        var range = ' range="';
                        var selectSize = table_function_data.selection[table_function_data.selectionSize - 1];
                        if (Wptm.newSelect === 'row') {
                            range += (selectSize[0] + 1) + '-' + (selectSize[2] + 1);
                        } else if (Wptm.newSelect === 'col') {
                            range += String.fromCharCode(65 + selectSize[1]) + '-' + String.fromCharCode(65 + selectSize[3]);
                        } else {
                            range += String.fromCharCode(65 + selectSize[1]) + (selectSize[0] + 1) + '-' + String.fromCharCode(65 + selectSize[3]) + (selectSize[2] + 1);
                        }
                        range += '"';

                        var render_shortcode = () => {
                            var align = this.find('.wptm_input[name="cell_insertion_shortcode_align"]:checked').val();
                            if (typeof align === 'undefined') {
                                this.find('#none').prop("checked", true);
                            }
                            var shortcode = '[wptm id=' + Wptm.id + ($filter.is(":checked") ? ' filter="1"' : '') + ($sort.is(":checked") ? ' sort="1"' : '') + ($download.is(":checked") ? ' download="1"' : '');
                            shortcode += ' align=' + (typeof align === 'undefined' ? '"none"' : '"' + align + '"');
                            if ($sort.is(":checked")) {
                                shortcode += ' sortcolumn="' + this.find('#default_sort').data('value') + '"';
                                shortcode += ' sortorder="' + this.find('#default_order_sort').data('value') + '"';
                            }
                            shortcode += range;
                            shortcode += ']';
                            $shortcode.val(shortcode).change();
                            this.find('.copy_text').unbind('click').on('click', function (e) {
                                tableFunction.copy_text($(e.target), '[wptm id=' + Wptm.id + range + ']');
                                $(this).siblings('span').css({'opacity': 1});
                                setTimeout(() => {
                                    $(this).siblings('span').css({'opacity': 0});
                                }, 1000);
                                return true;
                            });
                            return true;
                        }

                        if (Wptm.newSelect !== 'row') {
                            this.find('#default_sort').next('.wptm_select_box').addClass('hiddingCols');
                            for (var i = selectSize[1]; i <= selectSize[3]; i++) {
                                this.find('#default_sort').next('.wptm_select_box').find('li[data-value="' + i + '"]').addClass('show').data('value', i - selectSize[1]);
                            }
                        }
                        if (Wptm.newSelect !== 'row') {
                            this.find('#default_sort').next('.wptm_select_box').data('value', 0);
                            this.find('#default_sort').text(String.fromCharCode(65 + selectSize[1])).data('value', 0);
                        } else {
                            this.find('#default_sort').next('.wptm_select_box').data('value', 0);
                            this.find('#default_sort').text('A').data('value', 0);
                        }
                        custom_select_box.call(this.find('#default_sort'), $, () => {
                            render_shortcode()
                        });

                        this.find('#default_order_sort').next('.wptm_select_box').data('value', 1);
                        this.find('#default_order_sort').text('ASC').data('value', 1);
                        custom_select_box.call(this.find('#default_order_sort'), $, () => {
                            render_shortcode()
                        });

                        this.find('.sortOrder').hide();

                        this.find('#cell_insertion_shortcode_filter, #cell_insertion_shortcode_sort, .wptm_input[name="cell_insertion_shortcode_align"], #cell_insertion_shortcode_download')
                            .on('change', (e) => {
                                if ($(e.target).is('#cell_insertion_shortcode_sort')) {
                                    if ($(e.target).is(":checked")) {
                                        this.find('.sortOrder').show();
                                    } else {
                                        this.find('.sortOrder').hide();
                                    }
                                }

                                render_shortcode();
                                return true;
                            });

                        render_shortcode();

                        return true;
                    },
                    'submitAction': function () {
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, false);
                break;
            case 'save_menu':
                tableFunction.saveChanges(true);
                break;
            case 'export_menu':
                // if (Wptm.type == 'mysql') {
                //     return false;
                // }
                html = '';
                html += '<div>';
                html += '<div  class="popup_top border_top">';
                html += '<span>' + wptmText.import_export_style + ' :</span>';
                html += '</div>';
                html += '<span class="popup_select style_export wptm_select_box_before" data-value="1">' + wptmText.import_export_data_styles + '</span>';
                html += '<ul class="wptm_select_box">';
                html += '<li data-value="1">' + wptmText.import_export_data_styles + '</li>';
                html += '<li data-value="0">' + wptmText.import_export_data_only + '</li>';
                html += '</ul>';
                html += '<div><input type="button" class="wptm_button wptm_done" value="Export excel" id="export_excel"></div>';
                html += '</div>';
                popup = {
                    'html': $(html),
                    'selector': {'export_excel': '.style_export'},
                    'showAction': function () {
                        custom_select_box.call(this.find('.style_export'), $);
                    },
                    'option': {'export_excel': 1},
                };
                wptm_popup(wptm_element.wptm_popup, popup, false);

                //Export-excel
                $('#export_excel').bind('click', function (e) {
                    var format = default_value.export_excel_format;
                    var url = wptm_ajaxurl + 'task=excel.export&id=' + idTable + '&format_excel=' + format;

                    url = url + '&onlydata=' + popup.option['export_excel'];

                    $.fileDownload(url, {
                        failCallback: function (html, url) {
                            tableFunction.wptmBootbox('', html, true, false);
                        }
                    });
                });
                break;
            case 'source_menu':
                var curr_page = window.location.href;
                var cells = curr_page.split("?");
                var new_url;
                if (canInsert) {
                    new_url = cells[0] + '?page=wptm&type=dbtable&id_table=' + idTable + '&noheader=1&caninsert=1';
                } else {
                    new_url = cells[0] + '?page=wptm&type=dbtable&id_table=' + idTable;
                }
                window.location = new_url;
                break;
            case 'access_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#access_menu'),
                    'inputEnter': true,
                    'showAction': function () {
                        this.find('.wptm_done').addClass('wptm_blu');
                        this.find('.wptm_cancel').addClass('wptm_grey');

                        if (typeof Wptm.author !== 'undefined' && parseInt(Wptm.author) > 0) {
                            this.find('#jform_role_table').val(parseInt(Wptm.author)).change();
                        }

                        return true;
                    },
                    'submitAction': function () {
                        var author = this.find('#jform_role_table').val();
                        if (parseInt(author) > 0 && parseInt(author) !== parseInt(Wptm.author)) {//change own category
                            var jsonVar = {
                                data: JSON.stringify({0: author}),
                                id: Wptm.id,
                                type: 1
                            };
                            $.ajax({
                                url: wptm_ajaxurl + "task=user.save",
                                dataType: "json",
                                type: "POST",
                                data: jsonVar,
                                beforeSend: function () {
                                    wptm_element.settingTable.find('.wptm_saving').removeClass('wptm_hiden');
                                },
                                success: function (datas) {
                                    if (datas.response === true) {
                                        Wptm.author = author;
                                    } else {
                                        tableFunction.wptmBootbox('', datas.response, true, false);
                                    }
                                    wptm_element.settingTable.find('.wptm_saving').addClass('wptm_hiden');
                                },
                                error: function (jqxhr, textStatus, error) {
                                    tableFunction.wptmBootbox('', textStatus + ' : ' + error, true, false);
                                }
                            });
                        }
                        this.siblings('.colose_popup').trigger('click');
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'select_theme_menu':
                if (Wptm.type == 'mysql') {
                    return false;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#table_styles'),
                    'showAction': function () {
                        var image_hover_url = '';
                        var $image_hover = '';
                        var $image_height = 0;
                        var max_size_window = {};
                        max_size_window.height = $('#wpwrap').outerHeight();
                        max_size_window.width = $('#wpwrap').outerWidth();
                        wptm_element.wptm_popup.find('#table_styles li').mousemove(function (e) {
                            var x = e.clientX;
                            var y = e.clientY;
                            var upside = 0;

                            if (image_hover_url !== $(this).data('image')) {
                                $('#wpwrap').find('.full_image').remove();
                                image_hover_url = $(this).data('image');
                                $image_hover = $('<img class="full_image" style="width:500px" src="' + image_hover_url + '">');
                                $('#wpwrap').append($image_hover);
                            }

                            if (max_size_window.width < (x + 500)) {
                                upside += 2;
                            }

                            if ($image_hover.outerHeight() > 400) {
                                $image_height = 400;
                                $image_hover.css({height: 400, width: 'auto'});
                            } else {
                                $image_height = $image_hover.outerHeight();
                            }
                            if (max_size_window.height < (y + $image_height)) {
                                upside++;
                            }

                            if (upside === 3) {
                                $image_hover.css({top: y - $image_height - 20, left: x - 520});
                            } else if (upside === 2) {
                                $image_hover.css({top: y, left: x - 520});
                            } else if (upside === 1) {
                                $image_hover.css({top: y - $image_height - 20, left: x + 20});
                            } else {
                                $image_hover.css({top: y, left: x + 20});
                            }
                        }).mouseout(function (e) {
                            $('#wpwrap').find('.full_image').remove();
                            image_hover_url = '';
                            image_hover_url = '';
                        });
                    },
                    'cancelAction': function () {
                        wptm_element.wptm_popup.find('#table_styles li').unbind('mousemove');
                        $('#wpwrap').find('.full_image').remove();
                        return true;
                    }
                };

                wptm_popup(wptm_element.wptm_popup, popup, true);

                wptm_element.wptm_popup.find('#table_styles a').click(function () {
                    e.preventDefault();
                    var cellsData = Wptm.container.handsontable('getData');
                    var ret = true;
                    var nbCols = 0;
                    var id = $(this).data('id');

                    //check table data exist
                    $.each(cellsData, function (index, value) {
                        nbCols = value.length;
                        $.each(value, function (i, v) {
                            if (v && v.toString().trim() !== '') {
                                ret = false;
                                return false;
                            }
                        });
                    });

                    change_theme(ret, id, cellsData);
                })
                break;
            case 'alternate_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#alternating_color'),
                    'showAction': function () {
                        let selection, reSelection, valueRange;
                        valueRange = ''
                        if (Wptm.type === 'mysql') {
                            Wptm.container.handsontable("selectAll");
                            selection = $.extend([], function_data.selection[0]);
                        } else {
                            if (typeof function_data.selection === 'undefined' || function_data.selection === undefined || function_data.selection === false) { //if unselected cells
                                var i;
                                if (_.size(function_data.oldAlternate) < 1 || typeof Wptm.style.table.alternateColorValue === 'undefined') {
                                    selection = [0, 0, _.size(Wptm.style.cols) - 1, _.size(Wptm.style.rows) - 1];
                                    valueRange = 'a1:';
                                    //remove undefined cols in array
                                    var cols = [];
                                    for (i = 0; i < _.size(Wptm.style.cols); i++) {
                                        if (typeof Wptm.style.cols[i] !== 'undefined' && Wptm.style.cols[i] !== undefined && Wptm.style.cols[i] !== null) {
                                            cols[i] = Wptm.style.cols[i];
                                        }
                                    }
                                    Wptm.style.cols = cols;

                                    valueRange += String.fromCharCode(97 + _.size(Wptm.style.cols) - 1);
                                    valueRange += _.size(Wptm.style.rows);
                                }
                            } else {
                                selection = $.extend([], function_data.selection[0]);
                                // check if selecting more than 3 rows then process
                                var selection_rows = selection[2] - selection[0];
                                if (selection_rows >= 2) {
                                    if (_.size(function_data.oldAlternate) >= 1 || typeof Wptm.style.table.alternateColorValue !== 'undefined') {
                                        for (var i = 0; i < _.size(function_data.oldAlternate); i++) {
                                            var oldAlternateData = function_data.oldAlternate[i];
                                            if (oldAlternateData !== false && typeof oldAlternateData.selection !== 'undefined') {
                                                var isSameSelection = tableFunction.isSameArray(selection, oldAlternateData.selection);
                                                if (isSameSelection) {
                                                    var colorData = oldAlternateData.default.split('|');
                                                    var dftAlternateHeader = colorData[0];
                                                    var dftAlternateTile1 = colorData[1];
                                                    var dftAlternateTile2 = colorData[2];
                                                    var dftAlternateFooter = colorData[3];
                                                    var currTile = this.find('.pane-color-tile[data-tile-header="' + dftAlternateHeader + '"][data-tile-1="' + dftAlternateTile1 + '"][data-tile-2="' + dftAlternateTile2 + '"][data-tile-footer="' + dftAlternateFooter + '"]');
                                                    currTile.addClass('active');
                                                    if (oldAlternateData.header !== '') {
                                                        this.find('.banding-header-checkbox').trigger('click');
                                                    }
                                                    if (oldAlternateData.footer !== '') {
                                                        this.find('.banding-footer-checkbox').trigger('click');
                                                    }
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }

                        if (function_data.selectionSize > 0) {
                            selection = $.extend([], function_data.selection[0]);
                            for (i = 0; i < function_data.selectionSize; i++) {
                                if (function_data.selection[i][0] < selection[0]) {
                                    selection[0] = function_data.selection[i][0];
                                }
                                if (function_data.selection[i][1] < selection[1]) {
                                    selection[1] = function_data.selection[i][1];
                                }
                                if (function_data.selection[i][2] > selection[2]) {
                                    selection[2] = function_data.selection[i][2];
                                }
                                if (function_data.selection[i][3] > selection[3]) {
                                    selection[3] = function_data.selection[i][3];
                                }
                            }
                            valueRange += String.fromCharCode(97 + selection[1]) + (selection[0] + 1)
                                + ':' + String.fromCharCode(97 + selection[3]) + (selection[2] + 1);
                        }

                        if (valueRange !== '') {
                            this.find('.cellRangeLabelAlternate').val(valueRange);
                            this.find('#get_select_cells').trigger('click');
                        }

                        this.find('.formatting_style .pane-color-tile').on('click', (e) => {
                            var that = $(e.currentTarget);
                            //check exist selector and
                            selection = $.extend([], function_data.selection[0]);

                            if (typeof selection[1] !== 'undefined' && Math.abs(selection[0] - selection[2]) > 1) {
                                if (Wptm.type === 'mysql') { //alternate for all cell
                                    function_data.allAlternate = {};
                                    function_data.allAlternate.even = that.data('tile-1');
                                    function_data.allAlternate.old = that.data('tile-2');
                                    function_data.allAlternate.header = that.data('tile-header');
                                    function_data.allAlternate.footer = that.data('tile-footer');
                                    function_data.allAlternate.default = '' + function_data.allAlternate.header + '|' + function_data.allAlternate.even + '|' + function_data.allAlternate.old + '|' + function_data.allAlternate.footer;

                                    if (this.find('.banding-header-checkbox:checked').length < 1) {
                                        function_data.allAlternate.header = '';
                                    }

                                    if (this.find('.banding-footer-checkbox:checked').length < 1) {
                                        function_data.allAlternate.footer = '';
                                    }
                                    alternating.renderCell();
                                } else {
                                    var count = alternating.setNumberAlternate(selection, function_data.oldAlternate);

                                    /*create/reset oldAlternate[count]*/
                                    function_data.oldAlternate[count] = {};
                                    function_data.oldAlternate[count].selection = selection;
                                    function_data.oldAlternate[count].even = that.data('tile-1');
                                    function_data.oldAlternate[count].old = that.data('tile-2');
                                    function_data.oldAlternate[count].header = that.data('tile-header');
                                    function_data.oldAlternate[count].footer = that.data('tile-footer');

                                    function_data.oldAlternate[count].default = '' + function_data.oldAlternate[count].header + '|' + function_data.oldAlternate[count].even + '|' + function_data.oldAlternate[count].old + '|' + function_data.oldAlternate[count].footer;

                                    if (this.find('.banding-header-checkbox:checked').length < 1) {
                                        function_data.oldAlternate[count].header = '';
                                    }

                                    if (this.find('.banding-footer-checkbox:checked').length < 1) {
                                        function_data.oldAlternate[count].footer = '';
                                    }
                                    alternating.selectAlternatingColor(function_data.oldAlternate, selection, count),
                                        alternating.renderCell();
                                }

                                this.find('.pane-color-tile.active').removeClass('active');
                                that.addClass('active');
                            }
                            return false;
                        });

                        this.find('.banding-header-footer-checkbox-wrapper input').on('click', (e) => {
                            selection = $.extend([], function_data.selection[0]);
                            if (typeof selection[1] !== 'undefined' && Math.abs(selection[0] - selection[2]) > 1) {
                                if (Wptm.type === 'mysql') { //alternate for all cell
                                    if (typeof function_data.allAlternate.default !== 'undefined') {
                                        var defaultStyle = function_data.allAlternate.default.split("|");

                                        if (this.find('.banding-header-checkbox:checked').length > 0) {
                                            function_data.allAlternate.header = defaultStyle[0];
                                        } else {
                                            function_data.allAlternate.header = '';
                                        }

                                        if (this.find('.banding-footer-checkbox:checked').length > 0) {
                                            function_data.allAlternate.footer = defaultStyle[3];
                                        } else {
                                            function_data.allAlternate.footer = '';
                                        }
                                        alternating.renderCell();
                                    }
                                } else {
                                    var oldCount = _.size(function_data.oldAlternate);
                                    var count = alternating.setNumberAlternate(selection, function_data.oldAlternate);
                                    if (oldCount !== count) {
                                        var defaultStyle = [];
                                        if (typeof function_data.oldAlternate[count].default !== 'undefined') {
                                            defaultStyle = function_data.oldAlternate[count].default.split("|");
                                        } else {
                                            if (this.find('.pane-color-tile.active').length > 0) {
                                                defaultStyle[0] = this.find('.pane-color-tile.active').find('.pane-color-tile-header').data('value');
                                                defaultStyle[3] = this.find('.pane-color-tile.active').find('.pane-color-tile-footer').data('value');
                                            } else {
                                                defaultStyle[0] = function_data.oldAlternate[count].header;
                                                defaultStyle[3] = function_data.oldAlternate[count].footer;
                                            }
                                        }
                                        if (this.find('.banding-header-checkbox:checked').length > 0) {
                                            function_data.oldAlternate[count].header = defaultStyle[0];
                                        } else {
                                            function_data.oldAlternate[count].header = '';
                                        }

                                        if (this.find('.banding-footer-checkbox:checked').length > 0) {
                                            function_data.oldAlternate[count].footer = defaultStyle[3];
                                        } else {
                                            function_data.oldAlternate[count].footer = '';
                                        }

                                        function_data.oldAlternate[count].default = '' + defaultStyle[0] + '|' + function_data.oldAlternate[count].even + '|' + function_data.oldAlternate[count].old + '|' + defaultStyle[3];

                                        alternating.selectAlternatingColor(function_data.oldAlternate, selection, count),
                                            alternating.renderCell();
                                    }
                                }
                            }
                            return true;
                        });

                        this.find('#alternate_color_done').click((e) => {
                            e.preventDefault();
                            alternating.applyAlternate.call(this);
                        });
                        return true;
                    },
                    'cancelAction': function () {
                        if (_.size(function_data.checkChangeAlternate) > 0) {
                            if (Wptm.type === 'mysql') { //alternate for all cell
                                function_data.allAlternate = {};
                            } else {
                                Wptm.style.cells = $.extend({}, alternating.reAlternateColor());
                                function_data.oldAlternate = $.extend({}, Wptm.style.table.alternateColorValue);
                            }

                            alternating.renderCell();
                        }
                        return true;
                    }
                };
                if (Wptm.type === 'mysql') {
                    wptm_popup(wptm_element.wptm_popup, popup, true, false);
                } else {
                    wptm_popup(wptm_element.wptm_popup, popup, true, true);
                }
                break;
            case 'resize_column':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#column_size_menu'),
                    'inputEnter': true,
                    'showAction': function () {
                        let selection = function_data.selection;

                        this.find('#jform_row_height-lbl').closest('.control-group').addClass('wptm_hiden');
                        this.find('.popup_top span').text(wptmContext.columns_width_start);

                        if (function_data.selectionSize < 2) {
                            let text = ''
                            text = tableFunction.getSelectedVal([null, selection[0][1], null, selection[0][3]])

                            this.find('#jform_col_width-lbl').text(wptmContext.columns_width_start + text)
                        }

                        if (function_data.selectionSize > 1) {
                            this.find('#jform_col_width-lbl').text(wptmContext.columns_width_start);

                            var text = ''
                            tableFunction.eventToCellsSelected(
                                selection,
                                function_data.selectionSize,
                                (ranger, i) => {
                                    return new Promise((resolve) => {
                                        if (text !== '') {
                                            text += ','
                                        }
                                        text += tableFunction.getSelectedVal([ranger[0], ranger[1], ranger[2], ranger[3]])
                                        resolve(true)
                                    })
                                },
                                null,
                                null,
                                () => {
                                    return new Promise((resolve) => {
                                        this.find('.cellRangeLabelAlternate').val(text);
                                    })
                                })
                        }

                        this.find('.cellRangeLabelAlternate').addClass('select_column').data('text_change', '#jform_col_width-lbl');
                        return true;
                    },
                    'submitAction': function () {
                        var col_width_val = this.find('#cell_col_width').val();
                        selection = function_data.selection;
                        if (typeof selection !== 'undefined' && selection !== undefined && function_data.selectionSize > 0) {
                            tableFunction.eventToCellsSelected(selection, function_data.selectionSize, (ranger, ii) => {
                                return new Promise((resolve) => {
                                    for (let i = ranger[1]; i <= ranger[3]; i++) {
                                        if (typeof Wptm.style.cols[i] === 'undefined' || Wptm.style.cols[i] === null) {
                                            Wptm.style.cols[i] = [i, {width: parseInt(col_width_val)}];
                                        }
                                        Wptm.style.cols[i][1].width = parseInt(col_width_val);
                                    }

                                    resolve(true)
                                })
                            }, null, null, () => {
                                return new Promise((resolve) => {
                                    table_function_data.needSaveAfterRender = true;
                                    tableFunction.pullDims(Wptm, $);

                                    this.siblings('.colose_popup').trigger('click');
                                    tableFunction.saveChanges(true);
                                })
                            })
                        } else {
                            this.siblings('.colose_popup').trigger('click');
                        }
                        return true;
                    },
                    'cancelAction': function () {
                        wptm_element.wptm_popup.find('.content').contents().remove();
                        return true;
                    }
                };
                if (Wptm.type === 'mysql') {
                    wptm_popup(wptm_element.wptm_popup, popup, true, false);
                } else {
                    wptm_popup(wptm_element.wptm_popup, popup, true, true);
                }
                break;
            case 'resize_row':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#column_size_menu'),
                    'inputEnter': true,
                    'showAction': function () {
                        var selection = function_data.selection;
                        tableFunction.getSizeCells($, Wptm, [selection[0][2], selection[0][3]]);

                        this.find('#jform_col_width-lbl').closest('.control-group').addClass('wptm_hiden');
                        this.find('.popup_top span').text(wptmContext.rows_height_start);
                        if (Wptm.type === 'mysql') {
                            this.find('#all_cell_row_height').closest('.control-group').removeClass('wptm_hiden');
                            this.find('#jform_row_height-lbl').text(wptmContext.row_height + ' (header):');
                            this.find('#all_cell_row_height').val(Wptm.style.table.allRowHeight);
                        } else {
                            if (function_data.selectionSize > 1) {
                                this.find('#jform_row_height-lbl').text(wptmContext.rows_height);
                            } else {
                                if (selection[0][0] !== selection[0][2]) {//rows
                                    this.find('#jform_row_height-lbl')
                                        .text(wptmContext.rows_height_start + (selection[0][0] + 1) + '-' + (selection[0][2] + 1));
                                }
                            }
                        }

                        this.find('.cellRangeLabelAlternate').addClass('select_row').data('text_change', '#jform_row_height-lbl')

                        var text = '';
                        tableFunction.eventToCellsSelected(
                            selection,
                            function_data.selectionSize,
                            (ranger, i) => {
                                return new Promise((resolve) => {
                                    if (text !== '') {
                                        text += ','
                                    }

                                    text += tableFunction.getSelectedVal([ranger[0], ranger[1], ranger[2], ranger[3]])
                                    resolve(true)
                                })
                            },
                            null,
                            null,
                            () => {
                                return new Promise((resolve) => {
                                    this.find('.cellRangeLabelAlternate').val(text);
                                })
                            })

                        return true;
                    },
                    'submitAction': function () {
                        var row_height_val = this.find('#cell_row_height').val();
                        var all_row_height_val = this.find('#all_cell_row_height').val();
                        selection = function_data.selection;

                        if (typeof selection !== 'undefined' && selection !== undefined && function_data.selectionSize > 0) {
                            tableFunction.eventToCellsSelected(selection, function_data.selectionSize, (ranger, j) => {
                                return new Promise((resolve) => {
                                    for (let i = ranger[0]; i <= ranger[2]; i++) {
                                        if (Wptm.type !== 'mysql') {
                                            if (typeof Wptm.style.rows[i] === 'undefined') {
                                                Wptm.style.rows[i] = [i, {height: parseInt(row_height_val)}];
                                            }
                                            Wptm.style.rows[i][1].height = parseInt(row_height_val);
                                        }
                                    }

                                    if (Wptm.type == 'mysql') {
                                        Wptm.style.rows[0][1].height = r
                                        ow_height_val;
                                        var all_heigt = parseFloat(all_row_height_val);
                                        for (let i = 1; i < Wptm.max_row; i++) {
                                            if (typeof Wptm.style.rows[i] === 'undefined') {
                                                Wptm.style.rows[i] = [i, {height: all_heigt}];
                                            }
                                            Wptm.style.rows[i][1].height = all_heigt;
                                        }
                                        Wptm.style.table = tableFunction.fillArray(Wptm.style.table, {allRowHeight: all_heigt});
                                    }

                                    resolve(true)
                                })
                            }, null, null, () => {
                                return new Promise((resolve) => {
                                    table_function_data.needSaveAfterRender = true;
                                    tableFunction.pullDims(Wptm, $);

                                    this.siblings('.colose_popup').trigger('click');
                                    tableFunction.saveChanges(true);
                                })
                            })
                        } else {
                            this.siblings('.colose_popup').trigger('click');
                            tableFunction.saveChanges(true);
                        }
                        return true;
                    },
                    'cancelAction': function () {
                        wptm_element.wptm_popup.find('.content').contents().remove();
                        return true;
                    }
                };
                if (Wptm.type === 'mysql') {
                    wptm_popup(wptm_element.wptm_popup, popup, true, false);
                } else {
                    wptm_popup(wptm_element.wptm_popup, popup, true, true);
                }
                break;
            case 'header_option_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#header_option'),
                    'showAction': function () {
                        var length = -1;
                        for (var i = 4; i > 0; i--) {
                            if (typeof Wptm.datas[i] !== 'undefined') {
                                for (var ii = 0; ii < Wptm.datas[i].length; ii++) {
                                    if (Wptm.datas[i][ii] !== '') {
                                        length = i;
                                        break;
                                    }
                                }
                            }
                            if (length > -1) {
                                break;
                            }
                        }
                        this.find('#number_first_rows').next().find('li[data-value=' + (length + 1) + ']').nextAll().hide();
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm, 'headerOption', this.find('#number_first_rows').next('.wptm_select_box'), length + 1 > 0 ? 1 : 0);
                        if (Wptm.type == 'mysql') {
                            this.find('#number_first_rows').addClass('no_active');
                        } else {
                            custom_select_box.call(this.find('#number_first_rows'), $);
                        }
                        //freeze_row
                        var freeze_row = parseInt(Wptm.style.table.freeze_row) > 0 ? 1 : 0;
                        tableFunction.updateSwitchButtonFromStyleObject({'freeze_row': freeze_row}, 'freeze_row', this.find('#freeze_row'), '0');
                    },
                    'submitAction': function () {
                        if (Wptm.type !== 'mysql') {
                            if (this.find('#number_first_rows').data('value') !== '') {
                                if (this.find('#number_first_rows').data('value') != Wptm.headerOption) {
                                    saveData.push({
                                        action: 'set_header_option',
                                        value: this.find('#number_first_rows').data('value')
                                    });
                                }
                                Wptm.headerOption = parseInt(this.find('#number_first_rows').data('value'));
                            }
                        }

                        Wptm.style.table.freeze_row = this.find("#freeze_row").is(":checked") ? 1 : 0;
                        if (Wptm.style.table.freeze_row > 0) {
                            $(Wptm.container).handsontable('updateSettings', {fixedRowsTop: Wptm.headerOption});
                        } else {
                            $(Wptm.container).handsontable('updateSettings', {fixedRowsTop: 0});
                        }

                        tableFunction.saveChanges(true);
                        this.siblings('.colose_popup').trigger('click');

                        return true;
                    },
                    'cancelAction': function () {
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'form_filter_table':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#form_filter_table'),
                    'showAction': function () {
                        /*get list checkBox value*/
                        let checkBoxDefault = Array.from({length: Wptm.max_Col}, (_, i) => []);
                        for (let key in Wptm.datas.slice(0, 100)) {
                            if (parseInt(Wptm.style.table.headerOption) <= key) {
                                let row = Wptm.datas[key];
                                for (let i = 0; i < Wptm.max_Col; i++) {
                                    if (checkBoxDefault[i].indexOf(row[i]) === -1) {
                                        checkBoxDefault[i].push(row[i])
                                    }
                                }
                            }
                        }

                        // enable_filters
                        //use_sortable default_order_sortable default_sortable
                        let $listColumnConfig = this.find('.listColumnConfig')
                        for (let key in Wptm.style.cols) {
                            let colStyle = Wptm.style.cols[key]
                            let $columnConfig = this.find('.columnConfig.wptm_hiden').clone().removeClass('wptm_hiden')
                            let $component = $columnConfig.find('div.option')

                            $($component[0]).find('span').text(String.fromCharCode(97 + colStyle[0]))

                            tableFunction.updateParamFromStyleObjectSelectBox(
                                colStyle[1],
                                'searchContentType',
                                $($component[1]).find('span').next('.wptm_select_box'),
                                'text'
                            );
                            const inputType = (type, that) => {
                                switch (type) {
                                    case 'date':
                                        that.type = 'date'
                                        break;
                                    case 'text':
                                    case 'number':
                                    default:
                                        that.type = 'number'
                                        break;
                                }
                                return false;
                            }
                            const disableFilterType = (type, that) => {
                                let $select = that.find('li[data-value="select"]')
                                let $input = that.find('li[data-value="input"]')
                                let $checkBox = that.find('li[data-value="checkBox"]')
                                let $range = that.find('li[data-value="range"]')
                                switch (type) {
                                    case 'date':
                                        $checkBox.addClass('wptm_turn_off')
                                        $range.removeClass('wptm_turn_off')
                                        break;
                                    case 'text':
                                        $range.addClass('wptm_turn_off')
                                        $checkBox.removeClass('wptm_turn_off')
                                        break;
                                    case 'number':
                                    default:
                                        $select.removeClass('wptm_turn_off')
                                        $input.removeClass('wptm_turn_off')
                                        $checkBox.removeClass('wptm_turn_off')
                                        $range.removeClass('wptm_turn_off')
                                        break;
                                }
                                return false;
                            }
                            custom_select_box.call($($component[1]).find('span'), $,
                                (value) => {
                                    inputType(value, $($component[7]).find('input')[0])
                                    inputType(value, $($component[8]).find('input')[0])
                                    disableFilterType(value, $($component[4]))
                                    return false;
                                },
                                false,
                                () => {
                                    inputType(colStyle[1]?.searchContentType, $($component[7]).find('input')[0])
                                    inputType(colStyle[1]?.searchContentType, $($component[8]).find('input')[0])
                                    disableFilterType(colStyle[1]?.searchContentType, $($component[4]))
                                }
                            )

                            tableFunction.updateSwitchButtonFromStyleObject(
                                colStyle[1], 'sortable',  $($component[2]).find('input'), '1');

                            tableFunction.updateSwitchButtonFromStyleObject(
                                colStyle[1], 'filter',  $($component[3]).find('input'), '1');

                            tableFunction.updateParamFromStyleObjectSelectBox(
                                colStyle[1],
                                'searchType',
                                $($component[4]).find('span').next('.wptm_select_box'),
                                'none'
                            );
                            custom_select_box.call($($component[4]).find('span'), $,
                                (value) => {
                                    $($component[7]).addClass('wptm_turn_off')
                                    $($component[8]).addClass('wptm_turn_off')
                                    switch (value) {
                                        case 'none':
                                        default:
                                            $($component[5]).addClass('wptm_turn_off')
                                            $($component[6]).addClass('wptm_turn_off')
                                            break;
                                        case 'select':
                                        case 'input':
                                            $($component[5]).removeClass('wptm_turn_off')
                                            $($component[6]).addClass('wptm_turn_off')
                                            break;
                                        case 'range':
                                            $($component[5]).removeClass('wptm_turn_off')
                                            $($component[6]).addClass('wptm_turn_off')
                                            $($component[7]).removeClass('wptm_turn_off')
                                            $($component[8]).removeClass('wptm_turn_off')

                                            inputType(colStyle[1]?.searchContentType, $($component[7]).find('input')[0])
                                            inputType(colStyle[1]?.searchContentType, $($component[8]).find('input')[0])
                                            break;
                                        case 'checkBox':
                                            $($component[5]).removeClass('wptm_turn_off')
                                            $($component[6]).removeClass('wptm_turn_off')
                                            break;
                                    }
                                    return false;
                                },
                                false,
                                () => {
                                    $($component[7]).addClass('wptm_turn_off')
                                    $($component[8]).addClass('wptm_turn_off')
                                    switch (colStyle[1]?.searchType) {
                                        case 'none':
                                        default:
                                            $($component[5]).addClass('wptm_turn_off')
                                            $($component[6]).addClass('wptm_turn_off')
                                            break;
                                        case 'select':
                                        case 'input':
                                            $($component[5]).removeClass('wptm_turn_off')
                                            $($component[6]).addClass('wptm_turn_off')
                                            break;
                                        case 'range':
                                            $($component[5]).removeClass('wptm_turn_off')
                                            $($component[6]).addClass('wptm_turn_off')
                                            $($component[7]).removeClass('wptm_turn_off')
                                            $($component[8]).removeClass('wptm_turn_off')

                                            break;
                                        case 'checkBox':
                                            $($component[5]).removeClass('wptm_turn_off')
                                            $($component[6]).removeClass('wptm_turn_off')
                                            break;
                                    }
                                }
                            )

                            $($component[5]).find('input').val(colStyle[1]?.enable_filters_label || (Wptm.datas[0][colStyle[0]] || String.fromCharCode(97 + colStyle[0])))

                            for (let i in checkBoxDefault[key]) {
                                let $default = $($component[6]).find('span').next('.wptm_select_box').find('li.data_all').clone()
                                $default.removeClass('data_all').html('<input type="checkbox"/>' + checkBoxDefault[key][i]).attr('data-value', checkBoxDefault[key][i]).data('value', checkBoxDefault[key][i]).change()
                                $($component[6]).find('span').next('.wptm_select_box').append($default);
                            }
                            custom_select_box.call($($component[6]).find('span'), $, null, true);
                            tableFunction.updateParamFromStyleObjectSelectBox(colStyle[1], 'checkBoxContent', $($component[6]).find('span').next('.wptm_select_box'), 'all_value', null, true);

                            $($component[7]).find('input').val(colStyle[1]?.min)
                            $($component[8]).find('input').val(colStyle[1]?.max)

                            $listColumnConfig.append($columnConfig);
                        }

                        let $filterOptions = this.find('.container:not(.wptm_hiden) .filterOptions')
                        let $filter = this.find('.container:not(.wptm_hiden) .filter')
                        let $sort = this.find('.container:not(.wptm_hiden) .sort')
                        let $default_sortable = this.find('#default_sortable')
                        let $default_order_sortable = this.find('#default_order_sortable')
                        let $form_filter_table = this.find('#form_filter_table')
                        let $submit_form = this.find('#submit_form')
                        let $clear_form = this.find('#clear_form')

                        //filter: none, column filter, form filter
                        tableFunction.updateParamFromStyleObjectSelectBox(
                            Wptm.style.table,
                            'enable_filters',
                            this.find('#enable_filters').next('.wptm_select_box'),
                            '0'
                        );
                        custom_select_box.call(this.find('#enable_filters'), $, (value) => {
                            switch (parseInt(value)) {
                                case 2:
                                    $submit_form.parent().removeClass('wptm_hiden')
                                    $clear_form.parent().removeClass('wptm_hiden')
                                    $filter.addClass('wptm_hiden').removeClass('wptm_turn_off')
                                    $filterOptions.removeClass('wptm_hiden')
                                    $form_filter_table.removeClass('fullList')
                                    break;
                                case 1:
                                    $submit_form.parent().addClass('wptm_hiden')
                                    $clear_form.parent().addClass('wptm_hiden')
                                    $filter.removeClass('wptm_hiden wptm_turn_off')
                                    $filterOptions.addClass('wptm_hiden')
                                    $form_filter_table.addClass('fullList')
                                    break;
                                case 0:
                                default:
                                    $submit_form.parent().addClass('wptm_hiden')
                                    $clear_form.parent().addClass('wptm_hiden')
                                    $filter.removeClass('wptm_hiden').addClass('wptm_turn_off')
                                    $filterOptions.addClass('wptm_hiden')
                                    $form_filter_table.addClass('fullList')
                                    break;
                            }
                        }, false, () => {
                            switch (parseInt(Wptm.style.table?.enable_filters)) {
                                case 2:
                                    $submit_form.parent().removeClass('wptm_hiden')
                                    $clear_form.parent().removeClass('wptm_hiden')
                                    $filter.addClass('wptm_hiden').removeClass('wptm_turn_off')
                                    $filterOptions.removeClass('wptm_hiden')
                                    $form_filter_table.removeClass('fullList')
                                    break;
                                case 0:
                                    $submit_form.parent().addClass('wptm_hiden')
                                    $clear_form.parent().addClass('wptm_hiden')
                                    $filter.addClass('wptm_turn_off')
                                    $filterOptions.addClass('wptm_hiden')
                                    $form_filter_table.addClass('fullList')
                                    break;
                                case 1:
                                    $submit_form.parent().addClass('wptm_hiden')
                                    $clear_form.parent().addClass('wptm_hiden')
                                    $filter.removeClass('wptm_hiden wptm_turn_off')
                                    $filterOptions.addClass('wptm_hiden')
                                    $form_filter_table.addClass('fullList')
                                    break;
                                default:
                                    $submit_form.parent().addClass('wptm_hiden')
                                    $clear_form.parent().addClass('wptm_hiden')
                                    $filter.removeClass('wptm_hiden').addClass('wptm_turn_off')
                                    $filterOptions.addClass('wptm_hiden')
                                    $form_filter_table.addClass('fullList')
                                    break;
                            }
                        });

                        /*search all*/
                        tableFunction.updateSwitchButtonFromStyleObject(
                            Wptm.style.table, 'enable_filters_all', this.find('#enable_filters_all'), '0')

                        /*submit_form*/
                        tableFunction.updateSwitchButtonFromStyleObject(
                            Wptm.style.table, 'submit_form', $submit_form, '0')
                        /*clear_form*/
                        tableFunction.updateSwitchButtonFromStyleObject(
                            Wptm.style.table, 'clear_form', $clear_form, '0')

                        /*sort*/
                        tableFunction.updateSwitchButtonFromStyleObject(
                            Wptm.style.table, 'use_sortable', this.find('#use_sortable'), '0', function (value) {
                                switch (parseInt(value)) {
                                    case 1:
                                        $sort.removeClass('wptm_turn_off')
                                        $default_sortable.removeClass('wptm_turn_off')
                                        $default_order_sortable.removeClass('wptm_turn_off')
                                        break;
                                    case 0:
                                    default:
                                        $sort.addClass('wptm_turn_off')
                                        $default_sortable.addClass('wptm_turn_off')
                                        $default_order_sortable.addClass('wptm_turn_off')
                                        break;
                                }
                            }, function () {
                                if ($(this).is(":checked")) {
                                    $sort.removeClass('wptm_turn_off')
                                    $default_sortable.removeClass('wptm_turn_off')
                                    $default_order_sortable.removeClass('wptm_turn_off')
                                }
                                else {
                                    $sort.addClass('wptm_turn_off')
                                    $default_sortable.addClass('wptm_turn_off')
                                    $default_order_sortable.addClass('wptm_turn_off')
                                }
                            });
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'default_sortable', $default_sortable.next('.wptm_select_box'), '0');
                        custom_select_box.call($default_sortable, $);
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'default_order_sortable', $default_order_sortable.next('.wptm_select_box'), '1');
                        custom_select_box.call($default_order_sortable, $);
                        tableFunction.wptm_tooltip(this)
                        return true;
                    },
                    'submitAction': function () {
                        Wptm.style.table.enable_filters = this.find('#enable_filters').data('value')
                        Wptm.style.table.enable_filters_all = this.find('#enable_filters_all').is(":checked") ? 1 : 0
                        Wptm.style.table.submit_form = this.find('#submit_form').is(":checked") ? 1 : 0
                        Wptm.style.table.clear_form = this.find('#clear_form').is(":checked") ? 1 : 0
                        Wptm.style.table.use_sortable = this.find('#use_sortable').is(":checked") ? 1 : 0
                        Wptm.style.table.default_order_sortable = this.find('#default_order_sortable').data('value')
                        Wptm.style.table.default_sortable = this.find('#default_sortable').data('value')
                        this.find('.columnConfig:not(.wptm_hiden)').each(function (i, e) {
                            Wptm.style.cols[i][1].searchContentType = $(this).find('.searchContentType').data('value')
                            Wptm.style.cols[i][1].sortable = $(this).find('.sortable').is(":checked") ? 1 : 0
                            Wptm.style.cols[i][1].filter = $(this).find('.use_filter_column').is(":checked") ? 1 : 0
                            Wptm.style.cols[i][1].searchType = $(this).find('.searchType').data('value')
                            Wptm.style.cols[i][1].enable_filters_label = $(this).find('.enable_filters_label').val()
                            Wptm.style.cols[i][1].checkBoxContent = $(this).find('.checkBoxContent').data('value')
                            Wptm.style.cols[i][1].min = $(this).find('.minValue input').val() || ''
                            Wptm.style.cols[i][1].max = $(this).find('.maxValue input').val() || ''
                        })

                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'download_button_menu':
                if ($(this).parent().hasClass('selected')) {
                    Wptm.style.table.download_button = 0;
                    $(this).parent().removeClass('selected');
                } else {
                    Wptm.style.table.download_button = 1;
                    $(this).parent().addClass('selected');
                }
                tableFunction.saveChanges(true);
                break;
            case 'print_button_menu':
                if ($(this).parent().hasClass('selected')) {
                    Wptm.style.table.print_button = 0;
                    $(this).parent().removeClass('selected');
                } else {
                    Wptm.style.table.print_button = 1;
                    $(this).parent().addClass('selected');
                }
                tableFunction.saveChanges(true);
                break;
            case 'column_type_menu':
                tableFunction.default_sortable(window.Wptm.datas);
                popup = {
                    'html': wptm_element.content_popup_hide.find('#column_type_table'),
                    'showAction': function () {
                        var col_types = Wptm.style.table.col_types;

                        this.find('tbody tr').each(function () {
                            var i = $(this).data('col');
                            col_types[i] = typeof col_types[i] !== 'undefined' ? col_types[i].toLowerCase() === 'varchar(255)' ? 'varchar' : col_types[i].toLowerCase() : 'varchar';
                            if (Wptm.type === 'mysql') {
                                $(this).find('.wptm_select_box_before').addClass('no_active').text(col_types[i]);
                            } else {
                                tableFunction.updateParamFromStyleObjectSelectBox(col_types, i, $(this).find('.wptm_select_box'), 'varchar');
                                custom_select_box.call($(this).find('.column_type'), $);
                            }
                        });
                    },
                    'submitAction': function () {
                        var cols_selected = [];
                        if (Wptm.type !== 'mysql') {
                            this.find('tbody tr').each(function () {
                                var i = $(this).data('col');
                                var that = $(this).find('.wptm_select_box_before');
                                if (that.data('value') !== '') {
                                    if (that.data('value') != Wptm.style.table.col_types[i]) {
                                        cols_selected[i] = that.data('value');
                                    }
                                    Wptm.style.table.col_types[i] = that.data('value');
                                }
                            });

                            if (cols_selected.length > 0) {
                                saveData.push({
                                    action: 'set_columns_types',
                                    value: cols_selected
                                });
                            }
                            tableFunction.cleanHandsontable();
                            tableFunction.saveChanges(true);
                        }
                        this.siblings('.colose_popup').trigger('click');

                        return true;
                    },
                    'cancelAction': function () {
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'responsive_menu':
                tableFunction.default_sortable(window.Wptm.datas);
                popup = {
                    'html': wptm_element.content_popup_hide.find('#responsive_table'),
                    'selector': {
                        'responsive_type': '#responsive_type',
                        'table_breakpoint': '.table_breakpoint',
                        'table_height': '.table_height',
                        'freeze_col': '#freeze_col',
                        'responsive_col': '#responsive_col',
                        'style_repeated': '#style_repeated',
                        'responsive_priority': '#responsive_priority'
                    },
                    'option': {
                        'responsive_type': Wptm.style.table.responsive_type,
                        'freeze_col': Wptm.style.table.freeze_col,
                        'table_height': Wptm.style.table.table_height,
                        'style_repeated': Wptm.style.table.style_repeated,
                        'table_breakpoint': Wptm.style.table.table_breakpoint,
                    },
                    'showAction': function () {
                        tableFunction.updateParamFromStyleObjectSelectBox(popup.option, 'responsive_type', this.find('#responsive_type').next('.wptm_select_box'), 'scroll');
                        custom_select_box.call(this.find('#responsive_type'), $);

                        tableFunction.updateParamFromStyleObjectSelectBox(popup.option, 'freeze_col', this.find('#freeze_col').next('.wptm_select_box'), '0');
                        custom_select_box.call(this.find('#freeze_col'), $);

                        tableFunction.updateParamFromStyleObjectSelectBox(popup.option, 'style_repeated', this.find('#style_repeated').next('.wptm_select_box'), '0');
                        custom_select_box.call(this.find('#style_repeated'), $);

                        tableFunction.updateParamFromStyleObject(popup.option, 'table_height', this.find('.table_height'), '0');
                        tableFunction.updateParamFromStyleObject(popup.option, 'table_breakpoint', this.find('.table_breakpoint'), '980');

                        this.find('table tr .responsive_priority').each(function () {
                            custom_select_box.call($(this), $);
                        });

                        popup.inputAction.call(this, 'responsive_type');
                        check_saving = false;
                    },
                    'submitAction': function () {
                        Wptm.style.table.responsive_type = this.find('#responsive_type').data('value');
                        Wptm.style.table.freeze_col = this.find("#freeze_col").data('value');
                        Wptm.style.table.style_repeated = this.find("#style_repeated").data('value');
                        Wptm.style.table.table_height = popup.option.table_height;
                        Wptm.style.table.table_breakpoint = popup.option.table_breakpoint;
                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);
                        return true;
                    },
                    'inputAction': function (selector) {
                        if (selector === 'responsive_type') {
                            if (popup.option.responsive_type === 'scroll') {
                                this.find('.hiding').hide();
                                this.find('.repeatedHeader').hide();
                                this.find('.scrolling').show();
                            }

                            if (popup.option.responsive_type === 'repeatedHeader') {
                                this.find('.hiding').hide();
                                this.find('.scrolling').hide();
                                this.find('.repeatedHeader').show();
                                $(Wptm.container).handsontable('updateSettings', {
                                    manualColumnFreeze: false,
                                    fixedColumnsLeft: 0
                                });
                            }

                            if (popup.option.responsive_type === 'hideCols') {
                                this.find('.hiding').show();
                                this.find('.scrolling').hide();
                                this.find('.repeatedHeader').hide();

                                tableFunction.responsive_col.call(this.find('#responsive_col'), Wptm);
                            }
                        }

                        //scrolling
                        if (selector === 'freeze_col') {
                            if (parseInt(popup.option.freeze_col) === 0) {
                                $(Wptm.container).handsontable('updateSettings', {
                                    manualColumnFreeze: false,
                                    fixedColumnsLeft: 0
                                });
                            } else {
                                $(Wptm.container).handsontable('updateSettings', {
                                    manualColumnFreeze: true,
                                    fixedColumnsLeft: popup.option.freeze_col
                                });
                            }
                        }

                        for (var i = 0; i < _.size(Wptm.style.cols); i++) {
                            if (typeof Wptm.style.cols[i] !== 'undefined' && Wptm.style.cols[i] !== null) {
                                var col_priority = typeof Wptm.style.cols[i][1].res_priority !== 'undefined' ? Wptm.style.cols[i][1].res_priority : 0;
                                if (typeof col_priority !== 'undefined') {
                                    var curr_col = wptm_element.wptm_popup.find('table tbody tr').eq(i);
                                    curr_col.find('.responsive_priority').text(col_priority).data('value', col_priority);
                                }
                            }
                        }
                        $('.responsive_priority').unbind('change').on('change', (e) => {
                            var col = $(e.target).closest('tr').data('col');

                            if (typeof Wptm.style.cols[col] !== 'undefined' && Wptm.style.cols[col] !== null && $(e.target).val() !== Wptm.style.cols[col].res_priority) {
                                check_saving = true;
                            }
                            Wptm.style.cols = tableFunction.fillArray(Wptm.style.cols, {res_priority: $(e.target).data('value')}, col);
                        });
                    },
                    'cancelAction': function () {
                        for (var key in popup.option) {
                            if (popup.option[key] !== Wptm.style.table[key]) {
                                check_saving = false;
                                break;
                            }
                        }
                        if (check_saving) {
                            Wptm.style.table = $.extend({}, Wptm.style.table, popup.option), tableFunction.saveChanges();
                        }
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'pagination_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#pagination_table'),
                    'showAction': function () {
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'enable_pagination', this.find('#enable_pagination'), '0');

                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'limit_rows', this.find('#limit_rows').next('.wptm_select_box'), 0);
                        custom_select_box.call(this.find('#limit_rows'), $);

                        return true;
                    },
                    'submitAction': function () {
                        Wptm.style.table.limit_rows = this.find('#limit_rows').data('value');
                        Wptm.style.table.enable_pagination = this.find("#enable_pagination").is(":checked") ? 1 : 0;
                        if (Wptm.style.table.enable_pagination == 1) {
                            wptm_element.primary_toolbars.find('.pagination_menu').parent().addClass('selected');
                        } else {
                            wptm_element.primary_toolbars.find('.pagination_menu').parent().removeClass('selected');
                        }
                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'date_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#date_menu'),
                    'inputEnter': true,
                    'render': true,
                    'showAction': function () {
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'date_formats', this.find('#date_format'), '');

                        this.find('.select_date_format li').unbind('click').on('click', (e) => {
                            $(e.currentTarget).siblings('.active').removeClass('active');
                            var date_format = $(e.currentTarget).addClass('active').data('value');
                            if (typeof date_format !== 'undefined' && date_format !== '') {
                                this.find('#date_format').val(date_format);
                            }
                        });
                        return true;
                    },
                    'submitAction': function () {
                        if (this.find('#date_format').val() !== '') {
                            Wptm.style.table.date_formats = this.find('#date_format').val();
                            function_data.date_formats_momentjs = tableFunction.momentjsFormat(Wptm.style.table.date_formats);
                        }

                        function_data = tableFunction.createRegExpFormat(function_data, false, Wptm.style.table.date_formats);
                        table_function_data.needSaveAfterRender = true;
                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'date_menu_cell':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#date_menu_cell'),
                    'inputEnter': true,
                    'render': true,
                    'showAction': function () {
                        this.find('#popup_done').val('Apply');
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'date_formats', this.find('#date_format'), '');

                        this.find('.select_date_format li').unbind('click').on('click', (e) => {
                            $(e.currentTarget).siblings('.active').removeClass('active');
                            var date_format = $(e.currentTarget).addClass('active').data('value');
                            if (typeof date_format !== 'undefined' && date_format !== '') {
                                this.find('.date_formats').val(date_format);
                            }
                        });
                        return true;
                    },
                    'submitAction': function () {
                        if (typeof this.find('.date_formats').val() !== 'undefined') {
                            tableFunction.getFillArray(selection, Wptm, {
                                date_formats: this.find('.date_formats').val(),
                                date_formats_momentjs: tableFunction.momentjsFormat(this.find('.date_formats').val())
                            });
                        }

                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'lock_ranger_cells':
                if (wptm_administrator !== 1) {
                    break;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#lock_ranger_cells'),
                    'inputEnter': true,
                    'render': true,
                    'current_rangers': null,
                    'current_rangers_user': null,
                    'showAction': function () {
                        var current_rangers_user_defalt = this.find('.wptm_select_box').find('li:first').data('value');
                        custom_select_box.call(this.find('#jform_role_can_edit_lock'), $, null, true);
                        tableFunction.updateParamFromStyleObjectSelectBox(
                            '', 0,
                            this.find('#jform_role_can_edit_lock').next('.wptm_select_box'), current_rangers_user_defalt
                            , null, true);

                        // wptm_element.wptm_popup.find('.content .control-group:first').after(window.wptm_element.content_popup_hide.find('#select_cells').clone());
                        var function_data = window.table_function_data;
                        if (typeof function_data.selection !== 'undefined' && function_data.selection[0] !== undefined && _.size(function_data.selection[0]) > 0) {
                            var selection = function_data.selection[0];
                            if (selection[0] == 0 && selection[2] == Wptm.max_row - 1) {
                                tableFunction.getSelectedVal([false, selection[1], false, selection[3]], this.find('.cellRangeLabelAlternate'));
                            } else {
                                if (selection[1] == 0 && selection[3] == Wptm.max_Col - 1) {
                                    tableFunction.getSelectedVal([selection[0], false, selection[2], false], this.find('.cellRangeLabelAlternate'));
                                } else {
                                    tableFunction.getSelectedVal(selection, this.find('.cellRangeLabelAlternate'));
                                }
                            }
                        }

                        this.find('#get_select_cells').val(wptmText.Save_range);

                        var current_ranger = null;
                        if (typeof Wptm.style.table.lock_ranger_cells !== 'undefined') {
                            $.each(Wptm.style.table.lock_ranger_cells, (i, v) => {
                                if (v !== '') {
                                    var $lock_ranger_cell = this.find('.select_lock_ranger_cells').find('.wptm_hiden').clone();
                                    $lock_ranger_cell.removeClass('wptm_hiden');
                                    $lock_ranger_cell.attr('data-value', v).find('.label_text').text(v);
                                    this.find('.select_lock_ranger_cells').append($lock_ranger_cell);
                                }
                            });
                        } else {
                            Wptm.style.table.lock_ranger_cells = [];
                        }
                        if (typeof Wptm.style.table.lock_ranger_cells_user == 'undefined') {
                            Wptm.style.table.lock_ranger_cells_user = [];
                        }
                        popup.current_rangers = jquery.extend([], Wptm.style.table.lock_ranger_cells);
                        popup.current_rangers_user = jquery.extend([], Wptm.style.table.lock_ranger_cells_user);

                        this.find('.cellRangeLabelAlternate').unbind('keyup').on('click', function (e) {
                            e.preventDefault();
                            return false;
                        });
                        var select_ranger = () => {
                            //select
                            this.find('.select_lock_ranger_cells').find('.nfd-format-pill:not(.wptm_hiden)').unbind('click').on('click', (e) => {
                                if (!$(e.target).hasClass('delete_range')) {
                                    $(e.currentTarget).siblings('.nfd-format-pill.active').removeClass('active');
                                    current_ranger = popup.current_rangers.indexOf($(e.currentTarget).find('.label_text').text());
                                    this.find('.cellRangeLabelAlternate').val(popup.current_rangers[current_ranger]);
                                    alternating.affterRangeLabe.call(this, window.Wptm, window.jquery);
                                    $(e.currentTarget).addClass('active');
                                    // jform_role_can_edit_lock
                                    tableFunction.updateParamFromStyleObjectSelectBox(popup.current_rangers_user, current_ranger, this.find('#jform_role_can_edit_lock').next('.wptm_select_box'), current_rangers_user_defalt, null, true);

                                    // this.find('#popup_done').addClass('not_active');
                                }
                            });

                            //delete
                            this.find('.select_lock_ranger_cells').find('.nfd-format-pill:not(.wptm_hiden) .delete_range').unbind('click').on('click', (e) => {
                                e.preventDefault();
                                current_ranger = popup.current_rangers.indexOf(this.find('.cellRangeLabelAlternate').val());
                                var $li = $(e.currentTarget).parents('.nfd-format-pill');

                                if ($li.hasClass('active')) {
                                    $li.removeClass('active');
                                    this.find('.cellRangeLabelAlternate').val('');
                                }
                                popup.current_rangers.splice(current_ranger, 1);
                                popup.current_rangers_user.splice(current_ranger, 1);
                                $li.remove();
                                tableFunction.updateParamFromStyleObjectSelectBox('', current_ranger, this.find('#jform_role_can_edit_lock').next('.wptm_select_box'), current_rangers_user_defalt, null, true);

                                // this.find('#popup_done').removeClass('not_active');
                            });
                        }

                        //apply
                        this.find('#get_select_cells').unbind('click').on('click', () => {
                            alternating.affterRangeLabe.call(this, window.Wptm, window.jquery);
                            var ranger = this.find('.cellRangeLabelAlternate').val();
                            if (ranger !== '') {
                                var value = ranger.replace(/[ ]+/g, "").toUpperCase();
                                var arrayRange = value.split(":");
                                if (arrayRange.length < 2) {
                                    ranger += ':' + ranger;
                                } else if (arrayRange.length > 2) {
                                    return false;
                                }
                                var data_role_can_edit_lock = this.find('#jform_role_can_edit_lock').data('value');
                                if (current_ranger == null) {//new
                                    this.find('.nfd-format-pill.active').removeClass('active');
                                    var $lock_ranger_cell1 = this.find('.select_lock_ranger_cells').find('.wptm_hiden').clone();
                                    $lock_ranger_cell1.removeClass('wptm_hiden').addClass('active');
                                    $lock_ranger_cell1.attr('data-value', ranger).find('.label_text').text(ranger);
                                    this.find('.select_lock_ranger_cells').append($lock_ranger_cell1);
                                    popup.current_rangers.push(ranger.toUpperCase());
                                    popup.current_rangers_user.push(data_role_can_edit_lock === '' ? 'ADMINISTRATOR' : data_role_can_edit_lock);
                                } else {
                                    this.find('.select_lock_ranger_cells').find('.nfd-format-pill.active').attr('data-value', ranger).find('.label_text').text(ranger);
                                    popup.current_rangers[current_ranger] = ranger.toUpperCase();
                                    popup.current_rangers_user[current_ranger] = data_role_can_edit_lock === '' ? 'ADMINISTRATOR' : data_role_can_edit_lock;
                                }
                            }
                            // this.find('#popup_done').removeClass('not_active');

                            this.find('.cellRangeLabelAlternate').val('');
                            this.find('.nfd-format-pill.active').removeClass('active');
                            current_ranger = null;
                            select_ranger();
                        });

                        select_ranger();

                        return true;
                    },
                    'submitAction': function () {
                        this.find('#get_select_cells').trigger('click');
                        setTimeout(() => {
                            var duplicate = tableFunction.check_duplicate_ranger(popup.current_rangers);
                            if (duplicate.length > 0) {
                                [popup.current_rangers, popup.current_rangers_user] = tableFunction.merger_duplicate_ranger_user(duplicate, popup.current_rangers, popup.current_rangers_user);
                            }
                            setTimeout(() => {
                                Wptm.style.table.lock_ranger_cells = jquery.extend([], popup.current_rangers);
                                Wptm.style.table.lock_ranger_cells_user = jquery.extend([], popup.current_rangers_user);
                                this.siblings('.colose_popup').trigger('click');
                                tableFunction.saveChanges(true);
                            }, 200);
                        }, 200);
                        return true;
                    },
                    'cancelAction': function () {
                        var duplicate = tableFunction.check_duplicate_ranger(popup.current_rangers);
                        if (duplicate.length > 0) {
                            [popup.current_rangers, popup.current_rangers_user] = tableFunction.merger_duplicate_ranger_user(duplicate, popup.current_rangers, popup.current_rangers_user);
                        }
                        setTimeout(() => {
                            Wptm.style.table.lock_ranger_cells = jquery.extend([], popup.current_rangers);
                            Wptm.style.table.lock_ranger_cells_user = jquery.extend([], popup.current_rangers_user);
                            tableFunction.saveChanges(true);
                        }, 200);
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, false);
                break;
            case 'curency_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#curency_menu'),
                    'render': true,
                    'inputEnter': true,
                    'selector': {
                        'symbol_position': '#symbol_position'
                    },
                    'option': {
                        'symbol_position': Wptm.style.table.symbol_position
                    },
                    'showAction': function () {
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'currency_symbol', this.find('#currency_symbol'), '');
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'symbol_position', this.find('#symbol_position').next('.wptm_select_box'), '');
                        custom_select_box.call(this.find('#symbol_position'), $);

                        this.find('.select_curency_menu li').unbind('click').on('click', (e) => {
                            $(e.currentTarget).siblings('.active').removeClass('active');
                            var currency_symbol = $(e.currentTarget).addClass('active').data('currency_sym');
                            if (typeof currency_symbol !== 'undefined' && currency_symbol !== '') {
                                this.find('#currency_symbol').val(currency_symbol);
                            }

                            var symbol_position = $(e.currentTarget).data('symbol_position');
                            if (typeof symbol_position !== 'undefined' && symbol_position !== '') {
                                this.find('#symbol_position').data('value', symbol_position).text(symbol_position == 1 ? 'After' : 'Before').change();
                            }
                        });
                        return true;
                    },
                    'submitAction': function () {
                        if (this.find('#currency_symbol').val() !== '') {
                            Wptm.style.table.currency_symbol = this.find('#currency_symbol').val();
                        }
                        function_data = tableFunction.createRegExpFormat(function_data, Wptm.style.table.currency_symbol, false);

                        Wptm.style.table = $.extend({}, Wptm.style.table, popup.option);
                        table_function_data.needSaveAfterRender = true;
                        tableFunction.setFormat_accounting();

                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);

                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'curency_menu_cell':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#curency_menu_cell'),
                    'render': true,
                    'inputEnter': true,
                    'showAction': function () {
                        this.find('#popup_done').val('Apply');
                        custom_select_box.call(this.find('.symbol_position'), $);

                        this.find('.select_curency_menu li').unbind('click').on('click', (e) => {
                            $(e.currentTarget).siblings('.active').removeClass('active');
                            var currency_symbol = $(e.currentTarget).addClass('active').data('currency_sym');
                            if (typeof currency_symbol !== 'undefined' && currency_symbol !== '' && currency_symbol !== false) {
                                this.find('.currency_symbol').val(currency_symbol);
                            }

                            var symbol_position = $(e.currentTarget).data('symbol_position');
                            if (typeof symbol_position !== 'undefined' && symbol_position !== '' && symbol_position !== false) {
                                this.find('.symbol_position').data('value', symbol_position).text(symbol_position == 1 ? 'After' : 'Before').change();
                            }
                        });
                        return true;
                    },
                    'submitAction': function () {
                        if (typeof this.find('.currency_symbol').val() !== 'undefined') {
                            tableFunction.getFillArray(selection, Wptm, {
                                currency_symbol: this.find('.currency_symbol').val(),
                                currency_symbol_second: null
                            });
                        }

                        if (typeof this.find('.symbol_position').data('value') !== 'undefined' && this.find('.symbol_position').data('value') !== '') {
                            tableFunction.getFillArray(selection, Wptm, {
                                symbol_position: this.find('.symbol_position').data('value'),
                                symbol_position_second: null
                            });
                        }

                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);

                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'decimal_menu':
                popup = {
                    'html': wptm_element.content_popup_hide.find('#decimal_menu'),
                    'render': true,
                    'inputEnter': true,
                    'showAction': function () {
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'thousand_symbol', this.find('#thousand_symbol'), '');
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'decimal_symbol', this.find('#decimal_symbol'), '');
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'decimal_count', this.find('#decimal_count'), '');
                        this.find('.select_decimal_menu li').unbind('click').on('click', (e) => {
                            $(e.currentTarget).siblings('.active').removeClass('active');
                            var thousand_symbol = $(e.currentTarget).addClass('active').data('thousand_symbol');
                            if (typeof thousand_symbol !== 'undefined') {
                                this.find('#thousand_symbol').val(thousand_symbol).change();
                            }

                            var decimal_symbol = $(e.currentTarget).data('decimal_symbol');
                            if (typeof decimal_symbol !== 'undefined') {
                                this.find('#decimal_symbol').val(decimal_symbol).change();
                            }

                            var decimal_count = $(e.currentTarget).data('decimal_count');
                            if (typeof decimal_count !== 'undefined' && decimal_count !== '') {
                                this.find('#decimal_count').val(decimal_count).change();
                            } else {
                                this.find('#decimal_count').val(0).change();
                            }
                        });
                        return true;
                    },
                    'submitAction': function () {
                        if (typeof this.find('#decimal_symbol').val() !== 'undefined') {
                            Wptm.style.table.decimal_symbol = this.find('#decimal_symbol').val();
                        }
                        if (typeof this.find('#decimal_count').val() !== 'undefined') {
                            Wptm.style.table.decimal_count = this.find('#decimal_count').val() == '' ? 0 : this.find('#decimal_count').val();
                        }
                        if (typeof this.find('#thousand_symbol').val() !== 'undefined') {
                            Wptm.style.table.thousand_symbol = this.find('#thousand_symbol').val();
                        }
                        table_function_data.needSaveAfterRender = true;
                        tableFunction.setFormat_accounting();

                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);

                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'decimal_menu_cell':
                if (Wptm.type === 'mysql') {
                    return true;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#decimal_menu_cell'),
                    'render': true,
                    'inputEnter': true,
                    'showAction': function () {
                        this.find('#popup_done').val('Apply');
                        this.find('.select_decimal_menu li').unbind('click').on('click', (e) => {
                            $(e.currentTarget).siblings('.active').removeClass('active');
                            var thousand_symbol = $(e.currentTarget).addClass('active').data('thousand_symbol');
                            if (typeof thousand_symbol !== 'undefined' && thousand_symbol !== false) {
                                this.find('.thousand_symbol').val(thousand_symbol).change();
                            }

                            var decimal_symbol = $(e.currentTarget).data('decimal_symbol');
                            if (typeof decimal_symbol !== 'undefined' && decimal_symbol !== false) {
                                this.find('.decimal_symbol').val(decimal_symbol).change();
                            }

                            var decimal_count = $(e.currentTarget).data('decimal_count');
                            if (typeof decimal_count !== 'undefined' && decimal_count !== '' && decimal_count !== false) {
                                this.find('.decimal_count').val(decimal_count).change();
                            } else {
                                this.find('.decimal_count').val(0).change();
                            }
                        });
                        return true;
                    },
                    'submitAction': function () {
                        if (typeof this.find('.decimal_symbol').val() !== 'undefined') {
                            tableFunction.getFillArray(selection, Wptm, {
                                decimal_symbol: this.find('.decimal_symbol').val(),
                                decimal_symbol_second: null
                            });
                        }
                        if (typeof this.find('.decimal_count').val() !== 'undefined') {
                            tableFunction.getFillArray(selection, Wptm, {
                                decimal_count: this.find('.decimal_count').val() == '' ? 0 : this.find('.decimal_count').val(),
                                decimal_count_second: null
                            });
                        }
                        if (typeof this.find('.thousand_symbol').val() !== 'undefined') {
                            tableFunction.getFillArray(selection, Wptm, {
                                thousand_symbol: this.find('.thousand_symbol').val(),
                                thousand_symbol_second: null
                            });
                        }

                        this.siblings('.colose_popup').trigger('click');
                        tableFunction.saveChanges(true);

                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'google_sheets_menu':
                if (Wptm.type == 'mysql') {
                    return false;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#google_sheets_menu'),
                    'show_google_url': function (text_url, $done) {
                        if (text_url !== '') {
                            $done.show();

                            if (this.find('#auto_push').is(":checked")) {
                                this.find('#google_url').parent().show();
                            } else {
                                this.find('#google_url').parent().hide();
                            }
                        } else {
                            $done.hide();
                        }

                        if (typeof Wptm.syn_hash !== 'undefined' && Wptm.syn_hash !== '') {
                            this.find('#google_url').val(Wptm.syn_hash);
                        } else {
                            this.find('#google_url').val('' + Wptm.id + tableFunction.hashFnv32a());
                        }

                        var text = 'function send' + Wptm.id + 'sheetspush() {\n var response = UrlFetchApp.fetch("'
                            + wptm_ajaxurl_site + 'task=sitecontrol.scriptGoogle&id_table=' + Wptm.id + '&wptmhash=' + this.find('#google_url').val() + '");\n}';
                        this.find('.wptm_copy_text_content').text(text);

                        this.find('#google_url').siblings('.copy_text').unbind('click').on('click', (e) => {
                            tableFunction.copy_text($(e.target), this.find('.wptm_copy_text_content').val());
                            this.find('.wptm_copied').show().animate({opacity: '1'}, "slow").delay(1000).animate({'opacity': '0'}, 10);
                        });
                    },
                    'showAction': function () {
                        // this.find('#google_sheets_menu .control-group.select_sheet').hide();
                        this.find('#fetch_browse').hide();
                        this.find('#jform_excel_url-lbl').hide();
                        this.find('#import_style').parent().hide();
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'auto_sync', this.find('#auto_sync'), '0');
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'spreadsheet_style', this.find('#spreadsheet_style'), '0');
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'auto_push', this.find('#auto_push'), '0');

                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'spreadsheet_url', this.find('#spreadsheet_url'), '');
                        check_saving = 0;

                        custom_select_box.call(this.find('#select_sheet'), $);
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'select_sheet_google', this.find('#select_sheet').next('.wptm_select_box'), 1);

                        popup.show_google_url.call(this, Wptm.style.table.spreadsheet_url, this.find('#google_url').closest('.control-group'));
                        this.find('#spreadsheet_url').on('keyup', (e) => {
                            popup.show_google_url.call(this, $(e.currentTarget).val(), this.find('#google_url').closest('.control-group'));
                        });

                        this.find('#auto_push').on('change', (e) => {
                            popup.show_google_url.call(this, this.find('#spreadsheet_url').val(), this.find('#google_url').parent());
                        });

                        this.find('#fetch_google').unbind('click').on('click', (e) => {
                            Wptm.style.table.spreadsheet_url = this.find("#spreadsheet_url").val();
                            if (Wptm.style.table.spreadsheet_url === '' || Wptm.style.table.spreadsheet_url === undefined) {
                                return false;
                            }
                            Wptm.style.table.select_sheet_google = this.find('#select_sheet').data('value') || 1;

                            Wptm.style.table.spreadsheet_style = this.find("#spreadsheet_style").is(":checked") ? 1 : 0;
                            Wptm.style.table.auto_sync = this.find("#auto_sync").is(":checked") ? 1 : 0;
                            if (Wptm.style.table.auto_sync === 1) {
                                Wptm.style.table.excel_auto_sync = 0;
                            }

                            Wptm.style.table.auto_push = this.find("#auto_push").is(":checked") ? 1 : 0;

                            var data = {'type': 'spreadsheet'};
                            table_function_data.fetch_data = data;

                            if (this.find('#google_url').val() !== '') {
                                Wptm.syn_hash = this.find('#google_url').val();
                            } else {
                                Wptm.syn_hash = '' + Wptm.id + tableFunction.hashFnv32a();
                            }

                            tableFunction.saveChanges(true);
                            return true;
                        });
                        return true;
                    },
                    'submitAction': function () {
                        Wptm.style.table.spreadsheet_url = this.find("#spreadsheet_url").val();
                        Wptm.style.table.select_sheet_google = this.find('#select_sheet').data('value') || 1;

                        Wptm.style.table.spreadsheet_style = this.find("#spreadsheet_style").is(":checked") ? 1 : 0;

                        Wptm.style.table.auto_sync = this.find("#auto_sync").is(":checked") ? 1 : 0;
                        if (Wptm.style.table.auto_sync === 1) {
                            Wptm.style.table.excel_auto_sync = 0;
                        }

                        Wptm.style.table.auto_push = this.find("#auto_push").is(":checked") ? 1 : 0;

                        check_saving = 1;

                        if (this.find('#google_url').val() !== '') {
                            Wptm.syn_hash = this.find('#google_url').val();
                        }

                        this.siblings('.colose_popup').trigger('click');
                        return true;
                    },
                    'cancelAction': function () {
                        if (check_saving === 1) {
                            tableFunction.saveChanges(true);
                        }
                        return true;
                    },
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'onedrive_menu':
                if (Wptm.type == 'mysql') {
                    return false;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#onedrive_menu'),
                    'showAction': function () {
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'onedrive_style', this.find('#onedrive_style'), '0');
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'auto_sync_onedrive', this.find('#auto_sync_onedrive'), '0');
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'onedrive_url', this.find('#onedrive_url'), '');
                        check_saving = 0;

                        custom_select_box.call(this.find('#select_sheet'), $);
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'select_sheet_one', this.find('#select_sheet').next('.wptm_select_box'), 1);

                        this.find('#fetch_google').unbind('click').on('click', (e) => {
                            Wptm.style.table.onedrive_url = this.find("#onedrive_url").val();
                            Wptm.style.table.select_sheet_one = this.find('#select_sheet').data('value') || 1;
                            var dataSync = tableFunction.getLinkSync(Wptm.style.table.onedrive_url, 'onedrive', true);
                            Wptm.style.table.onedrive_url = dataSync.url;

                            if (!dataSync) {
                                return false;
                            }

                            Wptm.style.table.onedrive_style = this.find("#onedrive_style").is(":checked") ? 1 : 0;
                            Wptm.style.table.auto_sync_onedrive = this.find("#auto_sync_onedrive").is(":checked") ? 1 : 0;

                            var data = {'type': 'onedrive'};
                            table_function_data.fetch_data = data;

                            tableFunction.saveChanges(true);
                            return true;
                        });
                        return true;
                    },
                    'submitAction': function () {
                        Wptm.style.table.onedrive_url = this.find("#onedrive_url").val();
                        Wptm.style.table.onedrive_style = this.find("#onedrive_style").is(":checked") ? 1 : 0;
                        Wptm.style.table.auto_sync_onedrive = this.find("#auto_sync_onedrive").is(":checked") ? 1 : 0;
                        Wptm.style.table.select_sheet_one = this.find('#select_sheet').data('value') || 1;

                        check_saving = 1;

                        this.siblings('.colose_popup').trigger('click');
                        return true;
                    },
                    'cancelAction': function () {
                        if (check_saving === 1) {
                            var dataSync = tableFunction.getLinkSync(Wptm.style.table.onedrive_url, 'onedrive', false);
                            Wptm.style.table.onedrive_url = dataSync.url;

                            tableFunction.saveChanges(true);
                        }
                        return true;
                    },
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'synchronization_menu':
                if (Wptm.type == 'mysql') {
                    return false;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#google_sheets_menu'),
                    'inputEnter': true,
                    'showAction': function () {
                        this.css({'min-width': 400});
                        this.find('#google_url').parent().hide();
                        this.find('#jform_spreadsheet_url-lbl').hide();
                        this.find("#auto_push").closest('.push-notification-group').hide();
                        if (typeof default_value.enable_import_excel !== 'undefined'
                            && default_value.enable_import_excel == '1') {
                            this.prepend(wptm_element.content_popup_hide.find('#import_excel').clone());
                            this.find('#google_sheets_menu').addClass('excel_syn');
                        }

                        custom_select_box.call(this.find('#select_sheet'), $);
                        tableFunction.updateParamFromStyleObjectSelectBox(Wptm.style.table, 'select_sheet', this.find('#select_sheet').next('.wptm_select_box'), 1);
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'excel_auto_sync', this.find('#auto_sync'), '0');
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'excel_spreadsheet_style', this.find('#spreadsheet_style'), '0');

                        custom_select_box.call(this.find('#select_sheet_import'), $);
                        custom_select_box.call(this.find('#import_style'), $);
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'excel_url', this.find('#spreadsheet_url'), '');
                        check_saving = 0;

                        this.find('#fetch_google').unbind('click').on('click', (e) => {
                            Wptm.style.table.excel_url = this.find("#spreadsheet_url").val();
                            if (Wptm.style.table.excel_url === '' || Wptm.style.table.excel_url === undefined) {
                                return false;
                            }
                            $('.prevent_event_popup').show();

                            Wptm.style.table.select_sheet = this.find('#select_sheet').data('value') || 1;
                            Wptm.style.table.excel_spreadsheet_style = this.find("#spreadsheet_style").is(":checked") ? 1 : 0;
                            Wptm.style.table.excel_auto_sync = this.find("#auto_sync").is(":checked") ? 1 : 0;
                            if (Wptm.style.table.excel_auto_sync === 1) {
                                Wptm.style.table.auto_sync = 0;
                            }

                            var data = {'type': 'excel'};
                            table_function_data.fetch_data = data;

                            tableFunction.saveChanges(true);
                            return true;
                        });

                        wptmPreview.importExcel(jquery, this)
                        return true;
                    },
                    'submitAction': function () {
                        Wptm.style.table.excel_auto_sync = this.find("#auto_sync").is(":checked") ? 1 : 0;
                        if (Wptm.style.table.excel_auto_sync === 1) {
                            Wptm.style.table.auto_sync = 0;
                        }
                        Wptm.style.table.select_sheet = this.find('#select_sheet').data('value');
                        Wptm.style.table.excel_spreadsheet_style = this.find("#spreadsheet_style").is(":checked") ? 1 : 0;

                        Wptm.style.table.excel_url = this.find("#spreadsheet_url").val();

                        check_saving = 1;
                        this.siblings('.colose_popup').trigger('click');
                        return true;
                    },
                    'cancelAction': function () {
                        // this.find('#import_excel').appendTo(wptm_element.content_popup_hide);
                        // jquery(".loading-page").removeClass('show')
                        if (check_saving === 1) {
                            tableFunction.saveChanges(true);
                        }
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'csv_menu':
                if (Wptm.type == 'mysql') {
                    return false;
                }
                popup = {
                    'html': wptm_element.content_popup_hide.find('#csv_menu'),
                    'inputEnter': true,
                    'showAction': function () {
                        this.css({'min-width': 400});
                        check_saving = 0;
                        tableFunction.updateParamFromStyleObject(Wptm.style.table, 'csv_url', this.find('#csv_url'), '', () => {
                            this.find('#csv_url').keyup(() => {
                                this.find('#csv_url').css({'border-color': '#a7b5c3'});
                                this.find('#csv_url').next().hide();
                            })
                        });
                        tableFunction.updateSwitchButtonFromStyleObject(Wptm.style.table, 'auto_sync_csv', this.find('#auto_sync_csv'), '0');
                        //
                        this.find('#fetch_csv').unbind('click').on('click', (e) => {
                            Wptm.style.table.csv_url = this.find("#csv_url").val();
                            if (Wptm.style.table.csv_url === '' || Wptm.style.table.csv_url === undefined) {
                                this.find('#csv_url').css({'border-color': 'red'});
                                this.find('#csv_url').next().show();
                                return false;
                            }
                            $('.prevent_event_popup').show();

                            Wptm.style.table.auto_sync_csv = this.find("#auto_sync_csv").is(":checked") ? 1 : 0;

                            var data = {'type': 'csv'};
                            table_function_data.fetch_data = data;

                            tableFunction.saveChanges(true);
                            return true;
                        });

                        wptmPreview.importExcel(jquery, this)
                        return true;
                    },
                    'submitAction': function () {
                        Wptm.style.table.auto_sync_csv = this.find("#auto_sync_csv").is(":checked") ? 1 : 0;

                        Wptm.style.table.csv_url = this.find("#csv_url").val();

                        check_saving = 1;
                        this.siblings('.colose_popup').trigger('click');
                        return true;
                    },
                    'cancelAction': function () {
                        if (check_saving === 1) {
                            tableFunction.saveChanges(true);
                        }
                        return true;
                    }
                };
                wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                break;
            case 'new_chart_menu':
                DropChart.functions.addChart();
                break;
            case 'view_chart_menu':
                if (Wptm.dataChart.length > 0) {
                    wptm_element.settingTable.find('.ajax_loading').addClass('loadding').removeClass('wptm_hiden');
                    $(this).closest('li').addClass('menu_loading');
                    tableFunction.showChartOrTable(true, jquery('#list_chart').find('.chart-menu').eq(0));
                } else {
                    tableFunction.wptmBootbox('', wptmText.CHART_NOT_EXIST, wptmText.GOT_IT, false);
                }
                break;
            case 'editToolTip':
                var content = $('#tooltip_content').val();

                if (content !== '') {
                    wptm_element.tableContainer.find('tbody td.current').addClass('isTooltipContent');
                } else {
                    wptm_element.tableContainer.find('tbody td.current').removeClass('isTooltipContent');
                }

                if (typeof content !== 'undefined') {
                    var i, j;
                    var selection = table_function_data.selection[0];
                    var width = $('#tooltip_width').val();
                    for (i = selection[0]; i <= selection[2]; i++) {
                        for (j = selection[1]; j <= selection[3]; j++) {
                            if (width !== '') {
                                Wptm.style.cells = tableFunction.fillArray(Wptm.style.cells, {
                                    tooltip_content: content,
                                    tooltip_width: width
                                }, i, j);
                            } else {
                                Wptm.style.cells = tableFunction.fillArray(Wptm.style.cells, {tooltip_content: content}, i, j);
                            }
                            if (i == selection[2] && j == selection[3]) {
                                saveData.push({
                                    action: 'style', selection: table_function_data.selection, style: {
                                        tooltip_content: content,
                                        tooltip_width: width
                                    }
                                });
                                tableFunction.saveChanges();
                            }
                        }
                    }
                }
                break;
        }
        return false;
    });

    //select option in sub-menu(first top menu)
    wptm_element.primary_toolbars.find('.has_sub_menu ul li').on('change click', function () {
        if (!(window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author))) {
            return false;
        }

        var function_data = window.table_function_data;
        var Wptm = window.Wptm;
        var $ = window.jquery;
        var option_name = $(this).parents('.sub-menu').siblings('.table_option');
        switch (option_name.attr('name')) {
            case 'align_menu':
                Wptm.style.table.table_align = $(this).attr('name').toString();
                $(this).siblings('.selected').removeClass('selected');
                $(this).addClass('selected');
                tableFunction.saveChanges(true);
                break;
        }
        return true;
    });

    wptm_element.primary_toolbars.find('a.cell_option').unbind('click').on('click', function (e) {
        e.preventDefault();
        render = second_menu.call(this, render);
        clearTimeout(autoRender);

        autoRender = setTimeout(function () {
            if (render === true) {
                table_function_data.needSaveAfterRender = true;
                window.jquery(window.Wptm.container).data('handsontable').render();
            }

            render = false;
        }, 200);
    });

    wptm_element.primary_toolbars.find('.cell_option').unbind('change').on('change', function (e) {
        e.preventDefault();

        render = second_menu.call(this, render);
        if (render !== 'input_not_negative') {
            clearTimeout(autoRender);

            autoRender = setTimeout(function () {
                if (render === true) {
                    table_function_data.needSaveAfterRender = true;
                    window.jquery(window.Wptm.container).data('handsontable').render();
                }
                render = false;
            }, 200);
        }

    });

    //select calculator function
    wptm_element.primary_toolbars.find('.calculater_function').unbind('click').on('click', function (e) {
        e.preventDefault();
        if (Wptm.type !== 'html') {
            return;
        }
        var $ = window.jquery;

        var selection = $.extend({}, table_function_data.selection);
        var calculater = $(this).data('calculater');

        tableFunction.change_value_cells(selection, '=' + calculater + '()');
        var CellValue = document.getElementById('CellValue');

        wptm_element.cellValue.focus().val('=' + calculater + '()');

        if (CellValue) {
            if (typeof CellValue.setSelectionRange === 'function') {
                let x = calculater.length + 2;
                CellValue.setSelectionRange(x, x);
            }
            wptm_element.cellValue.click();
        }
    });

    //change value cells by #CellValue
    wptm_element.cellValue.on('keyup', function (e) {
        if (typeof Wptm.style.table.lock_ranger_cells !== 'undefined' || typeof Wptm.style.table.lock_columns !== 'undefined') {
            let readOnly = false;
            tableFunction.eventToCellsSelected(table_function_data.selection, 1, (ranger, i) => {
                for (let i2 = ranger[0]; i2 <= ranger[2]; i2++) {
                    for (let j = ranger[1]; j <= ranger[3]; j++) {
                        let cellProperties = tableFunction.check_cell_readOnly(i2, j, {})
                        if (cellProperties.readOnly) {
                            readOnly = true;
                            break;
                        }
                    }
                    if (readOnly)
                        break;
                }
            }, null , null, null)

            if (readOnly)
                return false;
        }

        if (wptm_element.cellValue.val() !== table_function_data.cellValueChange) {
            table_function_data.checkCellValueChange = table_function_data.selection;
        } else {
            table_function_data.checkCellValueChange = false;
        }
        if (e.keyCode === 13 && Wptm.type == 'html') {
            tableFunction.change_value_cells(table_function_data.selection, wptm_element.cellValue.val(), null, () => {
                var $ = window.jquery;
                let selection = table_function_data.selection;
                table_function_data.checkCellValueChange = false;

                if (typeof selection[1] === 'undefined'
                    && typeof selection[0][2] !== 'undefined'
                    && selection[0][0] === selection[0][2]
                    && selection[0][1] === selection[0][3]) {
                    if (typeof Wptm.datas[selection[0][0] + 1] === 'undefined') {
                        $(Wptm.container).handsontable("selectCell", 0, selection[0][1] + 1, 0, selection[0][3] + 1);
                    } else {
                        $(Wptm.container).handsontable("selectCell", selection[0][0] + 1, selection[0][1], selection[0][2] + 1, selection[0][3]);
                    }
                }
            });
        }
        return true;
    });
};

//event change value option in cell_menu
function second_menu(render) {
    if (!(window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author))) {
        return false;
    }

    if (table_function_data.selectOption) {
        return false;
    }
    var $ = window.jquery;

    var $name = $(this).attr('name');
    var min = typeof $(this).attr('min') !== 'undefined' ? $(this).attr('min') : null;
    var max = typeof $(this).attr('max') !== 'undefined' ? $(this).attr('max') : null;

    if (tableFunction.input_not_negative($(this).val(), $name, min, max)) {
        return 'input_not_negative';
    }

    var re_active = typeof arguments[1] !== 'undefined' && arguments[1] === 're_active' ? true : false;

    var Wptm = window.Wptm;
    var value;
    var parentLi = $(this).parents('.cells_option');
    var i;
    table_function_data.option_selected_mysql = '';

    var selection = $.extend({}, table_function_data.selection);

    if (Wptm.type === 'mysql') {
        for (i = 0; i < table_function_data.selectionSize; i++) {
            if ((selection[i][0] + selection[i][2]) !== 0) {//not cell in header
                selection[i][0] = selection[i][0] > 0 ? 1 : selection[i][0];
                selection[i][2] = _.size(Wptm.style.rows) - 1;
                table_function_data.option_selected_mysql = jquery(this).attr('name');
            }
        }
    }

    if (typeof selection[0][1] === 'undefined') {
        return;
    }
    var border_selection = {};
    var border_selection2 = {};
    var border_selection3 = {};
    switch ($name) {
        case 'undo_cell':
            if ($(this).hasClass('active')) {
                $(Wptm.container).handsontable('getInstance').undoRedo.undo()
                render = true;
            } else {
                tableFunction.status_notic(1, wptmText.some_action_cant_be_done + '!', $('#undoNotic'));
            }
            break;
        case 'redo_cell':
            if ($(this).hasClass('active')) {
                $(Wptm.container).handsontable('getInstance').undoRedo.redo()
                render = true;
            } else {
                tableFunction.status_notic(1, wptmText.some_action_cant_be_done + '!', $('#undoNotic'));
            }
            break;
        case 'cell_font_family':
            tableFunction.getFillArray(selection, Wptm, {cell_font_family: $(this).data('value')});
            render = true;
            break;
        case 'cell_font_size':
            tableFunction.getFillArray(selection, Wptm, {cell_font_size: $(this).val()});
            render = true;
            break;
        case 'cell_format_bold':
            if (parentLi.find('.active').length < 1) {
                value = true;
                parentLi.find('.cell_option').addClass('active');
            } else {
                value = false;
                parentLi.find('.active').removeClass('active');
            }

            tableFunction.getFillArray(selection, Wptm, {cell_font_bold: value});
            render = true;
            break;
        case 'cell_font_underline':
            if (parentLi.find('.active').length < 1) {
                value = true;
                parentLi.find('.cell_option').addClass('active');
                parentLi.parent().find('.cell_format_strikethrough').removeClass('active');
                tableFunction.getFillArray(selection, Wptm, {cell_format_strikethrough: false});
            } else {
                value = false;
                parentLi.find('.active').removeClass('active');
            }
            tableFunction.getFillArray(selection, Wptm, {cell_font_underline: value});
            render = true;
            break;
        case 'cell_format_strikethrough':
            if (parentLi.find('.active').length < 1) {
                value = true;
                parentLi.find('.cell_option').addClass('active');
                parentLi.parent().find('.cell_format_underlined').removeClass('active');
                tableFunction.getFillArray(selection, Wptm, {cell_font_underline: false});
            } else {
                value = false;
                parentLi.find('.active').removeClass('active');
            }
            tableFunction.getFillArray(selection, Wptm, {cell_format_strikethrough: value});
            render = true;
            break;
        case 'cell_font_italic':
            if (parentLi.find('.active').length < 1) {
                value = true;
                parentLi.find('.cell_option').addClass('active');
            } else {
                value = false;
                parentLi.find('.active').removeClass('active');
            }
            tableFunction.getFillArray(selection, Wptm, {cell_font_italic: value});
            render = true;
            break;
        case 'cell_background_color':
            if ($(this).data('keyup') === 1) {
                value = $(this).val();
                tableFunction.getFillArray(selection, Wptm, {cell_background_color: value});
                $(this).parents('.wp-picker-container').find('.wp-color-result').css('color', value);
                $(this).data('keyup', 0).change();

                render = true;
            }
            break;
        case 'cell_font_color':
            if ($(this).data('keyup') === 1) {
                value = $(this).val();
                tableFunction.getFillArray(selection, Wptm, {cell_font_color: value});
                $(this).parents('.wp-picker-container').find('.wp-color-result').css('color', value);
                $(this).data('keyup', 0).change();

                render = true;
            }
            break;
        case 'format_align_left':
            wptm_element.primary_toolbars.find('a[name="format_align_left"]').addClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_center"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_right"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_justify"]').removeClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_text_align: 'left'});
            render = true;
            break;
        case 'format_align_center':
            wptm_element.primary_toolbars.find('a[name="format_align_left"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_center"]').addClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_right"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_justify"]').removeClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_text_align: 'center'});
            render = true;
            break;
        case 'format_align_right':
            wptm_element.primary_toolbars.find('a[name="format_align_left"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_center"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_right"]').addClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_justify"]').removeClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_text_align: 'right'});
            render = true;
            break;
        case 'format_align_justify':
            wptm_element.primary_toolbars.find('a[name="format_align_left"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_center"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_right"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="format_align_justify"]').addClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_text_align: 'justify'});
            render = true;
            break;
        case 'vertical_align_bottom':
            wptm_element.primary_toolbars.find('a[name="vertical_align_bottom"]').addClass('active');
            wptm_element.primary_toolbars.find('a[name="vertical_align_middle"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="vertical_align_top"]').removeClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_vertical_align: 'bottom'});
            render = true;
            break;
        case 'vertical_align_middle':
            wptm_element.primary_toolbars.find('a[name="vertical_align_bottom"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="vertical_align_middle"]').addClass('active');
            wptm_element.primary_toolbars.find('a[name="vertical_align_top"]').removeClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_vertical_align: 'middle'});
            render = true;
            break;
        case 'vertical_align_top':
            wptm_element.primary_toolbars.find('a[name="vertical_align_bottom"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="vertical_align_middle"]').removeClass('active');
            wptm_element.primary_toolbars.find('a[name="vertical_align_top"]').addClass('active');
            tableFunction.getFillArray(selection, Wptm, {cell_vertical_align: 'top'});
            render = true;
            break;
        case 'overflow_cell':
            wptm_element.primary_toolbars.find('#wrap_cell').removeClass('active');
            wptm_element.primary_toolbars.find('#clip_cell').removeClass('active');
            if (wptm_element.primary_toolbars.find('#overflow_cell').hasClass('active')) {
                wptm_element.primary_toolbars.find('#overflow_cell').removeClass('active');
                tableFunction.getFillArray(selection, Wptm, {text_wrapping: false});
            } else {
                wptm_element.primary_toolbars.find('#overflow_cell').addClass('active');
                tableFunction.getFillArray(selection, Wptm, {text_wrapping: "normal"});
            }

            render = true;
            break;
        case 'wrap_cell':
            wptm_element.primary_toolbars.find('#overflow_cell').removeClass('active');
            wptm_element.primary_toolbars.find('#clip_cell').removeClass('active');
            if (wptm_element.primary_toolbars.find('#wrap_cell').hasClass('active')) {
                wptm_element.primary_toolbars.find('#wrap_cell').removeClass('active');
                tableFunction.getFillArray(selection, Wptm, {text_wrapping: false});
            } else {
                wptm_element.primary_toolbars.find('#wrap_cell').addClass('active');
                tableFunction.getFillArray(selection, Wptm, {text_wrapping: "break-word"});
            }
            render = true;
            break;
        case 'clip_cell':
            wptm_element.primary_toolbars.find('#overflow_cell').removeClass('active');
            wptm_element.primary_toolbars.find('#wrap_cell').removeClass('active');
            if (wptm_element.primary_toolbars.find('#clip_cell').hasClass('active')) {
                wptm_element.primary_toolbars.find('#clip_cell').removeClass('active');
                tableFunction.getFillArray(selection, Wptm, {text_wrapping: false});
            } else {
                wptm_element.primary_toolbars.find('#clip_cell').addClass('active');
                tableFunction.getFillArray(selection, Wptm, {text_wrapping: "hidden"});
            }
            render = true;
            break;
        case 'padding_border':
            var popup;
            popup = {
                'html': wptm_element.content_popup_hide.find('#padding_border'),
                'showAction': function () {
                    var size_selection = table_function_data.selectionSize - 1;
                    var cellStyle = window.Wptm.style.cells[selection[size_selection][0] + '!' + selection[size_selection][1]][2];

                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_padding_left', this.find('#jform_cell_padding_left'), 0);
                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_padding_top', this.find('#jform_cell_padding_top'), 0);
                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_padding_right', this.find('#jform_cell_padding_right'), 0);
                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_padding_bottom', this.find('#jform_cell_padding_bottom'), 0);

                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_background_radius_left_top', this.find('#jform_cell_background_radius_left_top'), 0);
                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_background_radius_right_top', this.find('#jform_cell_background_radius_right_top'), 0);
                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_background_radius_right_bottom', this.find('#jform_cell_background_radius_right_bottom'), 0);
                    tableFunction.updateParamFromStyleObject(cellStyle, 'cell_background_radius_left_bottom', this.find('#jform_cell_background_radius_left_bottom'), 0);

                    this.find('.observeChanges').unbind('change').on('change', (e) => {
                        var name = $(e.currentTarget).attr('name');
                        var value = $(e.currentTarget).val();
                        switch (name) {
                            case 'jform[jform_cell_padding_left]':
                                tableFunction.getFillArray(selection, Wptm, {cell_padding_left: value});
                                break;
                            case 'jform[jform_cell_padding_top]':
                                tableFunction.getFillArray(selection, Wptm, {cell_padding_top: value});
                                break;
                            case 'jform[jform_cell_padding_right]':
                                tableFunction.getFillArray(selection, Wptm, {cell_padding_right: value});
                                break;
                            case 'jform[jform_cell_padding_bottom]':
                                tableFunction.getFillArray(selection, Wptm, {cell_padding_bottom: value});
                                break;
                            case 'jform[jform_cell_background_radius_left_top]':
                                tableFunction.getFillArray(selection, Wptm, {cell_background_radius_left_top: value});
                                break;
                            case 'jform[jform_cell_background_radius_right_top]':
                                tableFunction.getFillArray(selection, Wptm, {cell_background_radius_right_top: value});
                                break;
                            case 'jform[jform_cell_background_radius_right_bottom]':
                                tableFunction.getFillArray(selection, Wptm, {cell_background_radius_right_bottom: value});
                                break;
                            case 'jform[jform_cell_background_radius_left_bottom]':
                                tableFunction.getFillArray(selection, Wptm, {cell_background_radius_left_bottom: value});
                                break;
                        }
                    });
                    return true;
                },
                'submitAction': function () {
                    this.siblings('.colose_popup').trigger('click');
                    tableFunction.saveChanges();
                    return true;
                },
                'cancelAction': function () {
                    window.jquery(window.Wptm.container).data('handsontable').render();
                    return true;
                }
            };
            wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
            break;
        case 'cell_type':
            if (Wptm.type !== 'mysql') {
                if (parentLi.find('.active').length < 1) {
                    tableFunction.getFillArray(selection, Wptm, {cell_type: 'html'}, "set_cells_type", false, function () {
                        // add cell type == text for column selected
                        var cols_selected = [];

                        for (let jj = 0; jj < table_function_data.selectionSize; jj++) {
                            for (let i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                cols_selected[i] = 'text';
                                Wptm.style.table.col_types[i] = 'text';
                            }
                        }
                        saveData.push({
                            action: 'set_columns_types',
                            value: cols_selected
                        });

                        setTimeout(function () {
                            tableFunction.saveChanges(true);
                        }, 200);
                    });
                    parentLi.find('.cell_option').addClass('active');
                } else {
                    tableFunction.getFillArray(selection, Wptm, {cell_type: null}, "set_cells_type");
                    parentLi.find('.active').removeClass('active');
                }
                tableFunction.cleanHandsontable();
                render = true;
            }
            break;
        case 'border_color':
            if ($(this).data('keyup') === 1) {
                value = $(this).val();
                $(this).parents('.wp-picker-container').find('.wp-color-result').css('color', value);
                border_cell(selection, Wptm, 'color', render);
                $(this).data('keyup', 0).change();
                render = true;
            }
            break;
        case 'border_all':
            if ($(this).hasClass('active') && !re_active) {
                border_selection = {};
                border_selection2 = {};
                border_selection3 = {};
                for (i = 0; i < table_function_data.selectionSize; i++) {
                    border_selection[i] = [selection[i][0], selection[i][1], selection[i][0], selection[i][3]];
                    if (selection[i][0] - 1 >= 0) {
                        border_selection2[i] = [selection[i][0] - 1, selection[i][1], selection[i][0] - 1, selection[i][3]];
                    }
                    if (selection[i][1] - 1 >= 0) {
                        border_selection3[i] = [selection[i][0], selection[i][1] - 1, selection[i][2], selection[i][1] - 1];
                    }
                }
                tableFunction.getFillArray(selection, Wptm, {cell_border_left: '', cell_border_top: '', cell_border_right: '', cell_border_bottom: ''});

                if (tableFunction.droptablesCheckSize(border_selection2) > 0) {
                    tableFunction.getFillArray(border_selection2, Wptm, {cell_border_bottom: ''});
                }
                if (tableFunction.droptablesCheckSize(border_selection3) > 0) {
                    tableFunction.getFillArray(border_selection3, Wptm, {cell_border_right: ''});
                }
                $(this).removeClass('active');
            } else {
                border_cell(selection, Wptm, 'position', 'cell_border_left', 'cell_border_top', 'cell_border_right', 'cell_border_bottom');
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_top':
            border_selection = {};
            border_selection2 = {};
            for (i = 0; i < table_function_data.selectionSize; i++) {
                border_selection[i] = [selection[i][0], selection[i][1], selection[i][0], selection[i][3]];
                if (selection[i][0] - 1 >= 0) {
                    border_selection2[i] = [selection[i][0] - 1, selection[i][1], selection[i][0] - 1, selection[i][3]];
                }
            }
            var parameter;
            if (table_function_data.option_selected_mysql !== '' && typeof table_function_data.option_selected_mysql !== 'undefined') {
                parameter = 'cell_border_top_start';
            } else {
                parameter = 'cell_border_top';
            }
            if ($(this).hasClass('active') && !re_active) {
                var x = {};
                x[parameter] = '';
                tableFunction.getFillArray(border_selection, Wptm, x);
                if (tableFunction.droptablesCheckSize(border_selection2) > 0 && parameter === 'cell_border_top') {
                    tableFunction.getFillArray(border_selection2, Wptm, {cell_border_bottom: ''});
                }
                $(this).removeClass('active');
            } else {
                border_cell(border_selection, Wptm, 'position', parameter);
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_bottom':
            var border_selection = {};
            for (i = 0; i < table_function_data.selectionSize; i++) {
                border_selection[i] = [selection[i][2], selection[i][1], selection[i][2], selection[i][3]];
            }
            var parameter;
            if (table_function_data.option_selected_mysql !== '' && typeof table_function_data.option_selected_mysql !== 'undefined') {
                parameter = 'cell_border_bottom_end';
            } else {
                parameter = 'cell_border_bottom';
            }
            if ($(this).hasClass('active') && !re_active) {
                var x = {};
                x[parameter] = '';
                tableFunction.getFillArray(border_selection, Wptm, x);
                $(this).removeClass('active');
            } else {
                border_cell(border_selection, Wptm, 'position', parameter);
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_left':
            border_selection = {};
            border_selection2 = {};
            for (i = 0; i < table_function_data.selectionSize; i++) {
                border_selection[i] = [selection[i][0], selection[i][1], selection[i][2], selection[i][1]];
                if (selection[i][1] - 1 >= 0) {
                    border_selection2[i] = [selection[i][0], selection[i][1] - 1, selection[i][2], selection[i][1] - 1];
                }
            }
            if ($(this).hasClass('active') && !re_active) {
                tableFunction.getFillArray(border_selection, Wptm, {cell_border_left: ''});
                if (tableFunction.droptablesCheckSize(border_selection2) > 0) {
                    tableFunction.getFillArray(border_selection2, Wptm, {cell_border_right: ''});
                }
                $(this).removeClass('active');
            } else {
                border_cell(border_selection, Wptm, 'position', 'cell_border_left');
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_right':
            border_selection = {};
            for (i = 0; i < table_function_data.selectionSize; i++) {
                border_selection[i] = [selection[i][0], selection[i][3], selection[i][2], selection[i][3]];
            }
            if ($(this).hasClass('active') && !re_active) {
                tableFunction.getFillArray(border_selection, Wptm, {cell_border_right: ''});
                $(this).removeClass('active');
            } else {
                border_cell(border_selection, Wptm, 'position', 'cell_border_right');
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_horizontal':
            border_selection = {};

            for (i = 0; i < table_function_data.selectionSize; i++) {
                if (selection[i][0] === selection[i][2]) {
                    return false;
                } else {
                    border_selection[i] = [selection[i][0], selection[i][1], selection[i][2] - 1, selection[i][3]];
                }
            }

            if ($(this).hasClass('active') && !re_active) {
                tableFunction.getFillArray(border_selection, Wptm, {cell_border_bottom: ''});
                $(this).removeClass('active');
            } else {
                border_cell(border_selection, Wptm, 'position', 'cell_border_bottom');
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_clear':
            wptm_element.primary_toolbars.find('#cell_border > div > a.cell_option.active').each(function () {
                jquery(this).removeClass('active');
            });
            tableFunction.getFillArray(selection, Wptm, {cell_border_left: '', cell_border_top: '', cell_border_right: '', cell_border_bottom: ''});
            if (table_function_data.option_selected_mysql !== '' && typeof table_function_data.option_selected_mysql !== 'undefined') {
                tableFunction.getFillArray(selection, Wptm, {cell_border_top_start: '', cell_border_bottom_end: ''});
            }

            // Get cells range above selection
            var topCellsSelections = {};
            Object.keys(selection).map(function (key, index) {
                var x = selection[key][0] - 1 > 0 ? selection[key][0] - 1 : 0;
                topCellsSelections[key] = [x, selection[key][1], x, selection[key][3]];
            });
            tableFunction.getFillArray(topCellsSelections, Wptm, {cell_border_bottom: ''});

            var leftCellsSelections = {};
            var fillLeftCells = true;
            Object.keys(selection).map(function (key, index) {
                var y = selection[key][1] - 1 > 0 ? selection[key][1] - 1 : 0;
                leftCellsSelections[key] = [selection[key][0], y, selection[key][2], y];
                if (parseInt(selection[key][1]) === 0) {
                    fillLeftCells = false;
                }
            });
            if (fillLeftCells) {
                tableFunction.getFillArray(leftCellsSelections, Wptm, {cell_border_right: ''});
            }

            render = true;
            break;
        case 'border_vertical':
            border_selection = {};
            for (i = 0; i < table_function_data.selectionSize; i++) {
                if (selection[i][1] === selection[i][3]) {
                    return false;
                } else {
                    border_selection[i] = [selection[i][0], selection[i][1], selection[i][2], selection[i][3] - 1];
                }
            }

            if ($(this).hasClass('active') && !re_active) {
                tableFunction.getFillArray(border_selection, Wptm, {cell_border_right: ''});
                $(this).removeClass('active');
            } else {
                border_cell(border_selection, Wptm, 'position', 'cell_border_right');
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_inner':
            var new_selection = [];
            new_selection['bottom'] = [];
            new_selection['right'] = [];
            for (i = 0; i < table_function_data.selectionSize; i++) {
                new_selection['bottom'][i] = $.extend([], selection[i]);
                new_selection['right'][i] = $.extend([], selection[i]);
                if (selection[i][1] === selection[i][3]) {
                    return false;
                } else {
                    new_selection['right'][i][3] = selection[i][3] - 1;
                }
                if (selection[i][0] === selection[i][2]) {
                    return false;
                } else {
                    new_selection['bottom'][i][2] = selection[i][2] - 1;
                }
            }
            if ($(this).hasClass('active') && !re_active) {
                tableFunction.getFillArray(new_selection['right'], Wptm, {cell_border_right: ''});
                tableFunction.getFillArray(new_selection['bottom'], Wptm, {cell_border_bottom: ''});
                $(this).removeClass('active');
            } else {
                border_cell(new_selection['bottom'], Wptm, 'position', 'cell_border_bottom');
                border_cell(new_selection['right'], Wptm, 'position', 'cell_border_right');
                $(this).addClass('active');
            }
            render = true;
            break;
        case 'border_outer':
            var val, ij, ik;
            if ($(this).hasClass('active') && !re_active) {
                $(this).removeClass('active');
                val = 'no_active';
            } else {
                $(this).addClass('active');
                val = 'active';
            }

            $.map(selection, function (v) {
                if (val === 'no_active') {
                    for (ij = v[0]; ij <= v[2]; ij++) {
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_right: ''}, ij, v[1] - 1);//before cell
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_right: ''}, ij, v[3]);
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_left: ''}, ij, v[3] + 1);//after cell
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_left: ''}, ij, v[1]);
                    }
                    for (ik = v[1]; ik <= v[3]; ik++) {
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_top: ''}, v[2] + 1, ik);
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_top: ''}, v[0], ik);
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_bottom: ''}, v[0] - 1, ik);
                        tableFunction.fillArray(Wptm.style.cells, {cell_border_bottom: ''}, v[2], ik);
                    }
                } else {
                    border_cell([[v[0], v[3], v[2], v[3]]], Wptm, 'position', 'cell_border_right');
                    border_cell([[v[0], v[1], v[2], v[1]]], Wptm, 'position', 'cell_border_left');
                    border_cell([[v[2], v[1], v[2], v[3]]], Wptm, 'position', 'cell_border_bottom');
                    border_cell([[v[0], v[1], v[0], v[3]]], Wptm, 'position', 'cell_border_top');
                }
            });

            render = true;
            break;
        case 'border_width':
            border_cell(selection, Wptm, 'border_width', render);
            render = true;
            break;
        case 'border_solid':
            $(this).siblings('.border_style.active').removeClass('active');
            $(this).addClass('active');
            border_cell(selection, Wptm, 'border_style', render);

            render = true;
            break;
        case 'border_dashed':
            $(this).siblings('.border_style.active').removeClass('active');
            $(this).addClass('active');
            border_cell(selection, Wptm, 'border_style', render);

            render = true;
            break;
        case 'border_dotted':
            $(this).siblings('.border_style.active').removeClass('active');
            $(this).addClass('active');
            border_cell(selection, Wptm, 'border_style', render);

            render = true;
            break;
        case 'merge_cell':
            if (Wptm.type !== 'mysql') {
                if ((selection[0][3] - selection[0][1] + selection[0][2] - selection[0][0]) < 1) {
                    return false;
                }
                if (table_function_data.selectionSize > 1) {//if selection > 1 when not merge and select selection[0]
                    $(Wptm.container).handsontable("selectCell", selection[0][0], selection[0][1], selection[0][2], selection[0][3]);
                    return false;
                }
                if ($(this).hasClass('active')) {
                    $(Wptm.container).handsontable('getInstance').getPlugin('mergeCells').unmergeSelection();
                    $(this).removeClass('active');
                } else {
                    $(Wptm.container).handsontable('getInstance').getPlugin('mergeCells').mergeSelection();
                    $(this).addClass('active');
                }
            }
            break;
    }
    return render;
}

/**
 * Convert border value for cell
 *
 * @param selection Selected cells
 * @param Wptm
 */
function border_cell(selection, Wptm) {
    var border_color = wptm_element.primary_toolbars.find('#border_color').val();
    if (typeof border_color === 'undefined' || border_color === '') {
        border_color = 'rgba(0, 0, 0, 0)';
    }
    var cell_border_style = 'solid';
    switch (wptm_element.primary_toolbars.find('#cell_border_style a.border_style.active').attr('name')) {
        case 'border_solid':
            cell_border_style = 'solid';
            break;
        case 'border_dashed':
            cell_border_style = 'dashed';
            break;
        case 'border_dotted':
            cell_border_style = 'dotted';
            break;
    }

    var width = '1';
    var cell_border_width = wptm_element.primary_toolbars.find('#cell_border_width').val();
    width = cell_border_width !== '' ? cell_border_width : '1';

    var position = [];
    if (arguments[2] !== 'position') {
        position = wptm_element.primary_toolbars.find('#cell_border > div > a.cell_option.active').each(function () {
            second_menu.call(jquery(this), arguments[3], 're_active');
        });
        return false;
    } else {
        position = Array.prototype.slice.call(arguments, 3);
    }
    position.map(function (foo) {
        var value = {};
        value[foo] = '' + width + 'px ' + cell_border_style + ' ' + border_color;
        switch (foo) {
            case 'cell_border_top':
                // Get cells range above selection
                var topCellsSelections = {};
                var topCellsSelections2;
                tableFunction.getFillArray(selection, Wptm, value);

                Object.keys(selection).map(function (key, index) {
                    if (selection[key][0] > 0) {
                        var x = selection[key][0] - 1;
                        topCellsSelections[key] = [x, selection[key][1], x, selection[key][3]];
                    }
                    if (selection[key][0] <= Wptm.headerOption && selection[key][2] >= Wptm.headerOption) {
                        topCellsSelections2 = {};
                        topCellsSelections2[key] = [Wptm.headerOption, selection[key][1], Wptm.headerOption, selection[key][3]];
                    }
                });

                if (typeof topCellsSelections2[0] !== 'undefined') {
                    tableFunction.getFillArray(topCellsSelections2, Wptm, {'cell_border_top': ''});
                }

                if (typeof topCellsSelections[0] !== 'undefined') {
                    tableFunction.getFillArray(topCellsSelections, Wptm, {cell_border_bottom: '' + width + 'px ' + cell_border_style + ' ' + border_color});
                }
                break;
            case 'cell_border_left':
                // Get cells range left selection
                var leftCellsSelections = {};
                var fillLeftCells = true;
                Object.keys(selection).map(function (key, index) {
                    var y = selection[key][1] - 1 > 0 ? selection[key][1] - 1 : 0;
                    if (parseInt(selection[key][1]) === 0) {
                        fillLeftCells = false;
                    }
                    leftCellsSelections[key] = [selection[key][0], y, selection[key][2], y];
                });

                if (fillLeftCells) {
                    tableFunction.getFillArray(leftCellsSelections, Wptm, {cell_border_right: '' + width + 'px ' + cell_border_style + ' ' + border_color});
                }
                tableFunction.getFillArray(selection, Wptm, value);
                break;
            default:
                tableFunction.getFillArray(selection, Wptm, value);
                break;
        }
    });

}

/*
popup and action for this
wptm_popup     #wptm_popup
popup          object data popup
clone          check clone content in popup
selector_cells get selector cells to popup window
submit_button  get submit button to popup window
*/
function wptm_popup(wptm_popup, popup, clone, selector_cells, submit_button, update_option_cell) {
    if (update_option_cell === true) {
        var selection = getSelector();
        updateOptionValCell(jquery, Wptm, selection);
    }

    wptm_popup.find('.content').contents().remove();
    var over_popup = wptm_popup.siblings('#over_popup');
    if (!clone) {
        var that = wptm_popup.find('.content').append(popup.html);
    } else {
        var that = wptm_popup.find('.content').append(popup.html.clone());
        // window.jquery(html).appendTo(window.jquerywptm_popup.find('.content'));
    }

    if (selector_cells === true) {
        wptm_popup.find('.content .popup_top').after(window.wptm_element.content_popup_hide.find('#select_cells').clone());
        var function_data = window.table_function_data;
        if (typeof function_data.selection !== 'undefined' && function_data.selection[0] !== undefined && _.size(function_data.selection[0]) > 0) {
            tableFunction.getSelectedVal(function_data.selection[0], that.find('.cellRangeLabelAlternate'));
        }
    }

    if (submit_button === true) {
        that.find('>div').append(window.wptm_element.content_popup_hide.find('#submit_button').clone());
    }
    wptm_popup.animate({'opacity': '1'}, 10);

    wptm_popup.show();
    over_popup.show();

    //selection cells
    that.find('#get_select_cells').unbind('click').on('click', function () {
        alternating.affterRangeLabe.call(that, window.Wptm, window.jquery);
        that.find('.cellRangeLabelAlternate').val();
    });
    that.find('.cellRangeLabelAlternate').on('keyup', function (e) {
        if (e.which == 13) {
            alternating.affterRangeLabe.call(that, window.Wptm, window.jquery);
        }
    });

    /*action when show popup*/
    if (typeof popup.showAction !== 'undefined') {
        popup.showAction.call(that);
    }

    /*action selector*/
    if (typeof popup.selector !== 'undefined') {
        var select = select_input_popup.bind(popup, that);
        select();
    }

    // select_tab
    // that.find('.select_tab').find('.link-tab').on('click', function (e) {
    //     e.preventDefault();
    //     topTab.find('li.tab a[href="'+ tabHref +'"]').click();
    //     currentSubMenu.find('li.tab div.link-tab').removeClass('active');
    //     $(this).addClass('active');
    // });

    /*action enter input*/
    if (popup.inputEnter) {
        that.find('input').on('keyup', function (e, i) {
            if (e.keyCode === 13) {
                if (typeof popup.submitAction !== 'undefined') {
                    setTimeout(function () {
                        popup.submitAction.call(that);
                    }, 200);
                } else {
                    jquery(this).trigger('change');
                }
                wptm_popup.find('.colose_popup').trigger('click');
            }
            return true;
        });
    }

    /*click done button*/
    that.find('#popup_done').unbind('click').on('click', function (e) {
        e.preventDefault();
        if (typeof popup.submitAction !== 'undefined' && !jquery(this).hasClass('not_active')) {
            setTimeout(function () {
                popup.submitAction.call(that);
            }, 200);
        }
        return false;
    });

    /*click cancel button*/
    that.find('#popup_cancel').unbind('click').on('click', function (e) {
        e.preventDefault();

        setTimeout(function () {
            wptm_popup.hide();
            over_popup.hide();
            if (typeof popup.cancelAction !== 'undefined') {
                wptm_popup.animate({'opacity': '0'}, 10);
                popup.cancelAction.call(that);
            }
        }, 200);
        return false;
    });

    //action colose
    wptm_popup.find('.colose_popup').unbind('click').on('click', function (e) {
        e.preventDefault();

        if (typeof popup.render !== 'undefined' && popup.render === true) {
            window.jquery(window.Wptm.container).data('handsontable').render();
        }

        setTimeout(function () {
            wptm_popup.hide();
            over_popup.hide();
            if (typeof popup.cancelAction !== 'undefined') {
                wptm_popup.animate({'opacity': '0'}, 10);
                popup.cancelAction.call(that);
            }
        }, 200);
        return false;
    });
    over_popup.unbind('click').on('click', function (e) {
        e.preventDefault();
        wptm_popup.find('.colose_popup').trigger('click');
        return false;
    });

    that.find('.tooltipster').each(function () {
        jquery(this).tooltipster({
            theme: 'tooltipster-borderless',
            delay: 100,
            maxWidth: 300,
        });
    })
    //set top for popup
    //wptm_popup.css('top', (over_popup.outerHeight() - wptm_popup.outerHeight()) / 2);

    if (typeof popup.afterShowAction !== 'undefined') {
        popup.afterShowAction.call(that);
    }

    return false;
}

/**
 * Selector Control function in popup
 *
 * @param that Wptm_popup.find('.content')
 */
var select_input_popup = function (that) {
    if (typeof this.selector !== 'undefined') {
        window.jquery.each(this.selector, (i, e) => {
            that.find(e).change((e) => {
                if (typeof this.option[i] !== 'undefined') {
                    this.option[i] = window.jquery(e.currentTarget).val();
                }
                if (typeof this.inputAction !== 'undefined') {
                    this.inputAction.call(that, i);
                }
            });
        });
    }
}

/**
 * add value for table_function_data.selection and table_function_data.selectionSize
 *
 * @returns {*}
 */
var getSelector = function () {
    var selection = [];
    // if (typeof selection === 'undefined' || selection === undefined || !selection) {
    selection = window.jquery(window.Wptm.container).handsontable('getSelected');//get all cells selected ex:[[0,0,1,9],[3,0,3,9]]
    // }
    if (selection !== false && typeof selection !== 'undefined' && selection.length > 0) {
        //check when handsontable('getSelected') return array[0] = array;
        window.table_function_data.selectionSize = _.size(selection);
        window.table_function_data.selection = []
        for (var i = 0; i < table_function_data.selectionSize; i++) {
            if (selection[i][0] > selection[i][2]) {
                selection[i] = [selection[i][2], selection[i][3], selection[i][0], selection[i][1]];
            }
            if (selection[i][1] > selection[i][3]) {
                selection[i] = [selection[i][0], selection[i][3], selection[i][2], selection[i][1]];
            }
            window.table_function_data.selection.push(selection[i])
        }
        // window.table_function_data.selection = selection;
    } else {
        window.table_function_data.selection = false;
        window.table_function_data.selectionSize = 0;
    }

    return selection;
}

var loadSelectionTimeout;
/**
 * Update status item in cell_menu and table_menu by cell selected
 *
 * @param $
 * @param Wptm
 * @param selection Cell selected
 *
 * @returns {boolean}
 */
var loadSelection = function ($, Wptm, selection) {
    selection = getSelector();
    if (!selection) {
        return true;
    }
    //show value cell to #CellValue when selector a cell
    let size_selection = table_function_data.selectionSize - 1
    let merege_cell = false;

    if (typeof Wptm.mergeCellsSetting !== 'undefined' && Wptm.mergeCellsSetting.length > 0) {
        Wptm.mergeCellsSetting.map(function (v, i) {
            if (
                selection[size_selection][0] == v.row
                && selection[size_selection][1] == v.col
                && (v.rowspan + v.row - 1) == selection[size_selection][2]
                && (v.colspan + v.col - 1) == selection[size_selection][3]
            ) {
                merege_cell = true;
            }
        });
    }

    if (merege_cell || (selection[size_selection][0] === selection[size_selection][2] && selection[size_selection][1] === selection[size_selection][3])) {
        wptm_element.cellValue.val(Wptm.datas[selection[size_selection][0]][selection[size_selection][1]]);
        table_function_data.cellValueChange = Wptm.datas[selection[size_selection][0]][selection[size_selection][1]];
    } else {
        wptm_element.cellValue.val('');
        table_function_data.cellValueChange = '';
    }
    table_function_data.checkCellValueChange = false;

    //set value option by selection
    if (!Wptm.checkTimeOutSelection) {
        clearTimeout(loadSelectionTimeout);
    }
    loadSelectionTimeout = setTimeout(() => {
        updateOptionValTable($, Wptm, selection);

        updateOptionValCell($, Wptm, selection);
        //Todo: populate jform_responsive_col
        Wptm.checkTimeOutSelection = false;
    }, 200)

    return true;
}

/**
 * Update status item in table_menu by cell seletoed
 *
 * @param $
 * @param Wptm
 * @param selection
 */
function updateOptionValTable($, Wptm, selection) {
    if (tableFunction.checkObjPropertyNested(Wptm.style, 'table')) {
        var styleTable = Wptm.style.table;
        var wptm_element = window.wptm_element;
        var selector;
        $.each(styleTable, function (index, value) {
            switch (index) {
                // case 'enable_filters':
                //     selector = wptm_element.settingTable.find('.filters_menu');
                //     if (value === 1 || value === '1') {
                //         selector.parent().addClass('selected');
                //     } else {
                //         selector.parent().removeClass('selected');
                //     }
                //     break;
                case 'download_button':
                    selector = wptm_element.settingTable.find('.download_button_menu');
                    if (value === 1 || value === '1') {
                        selector.parent().addClass('selected');
                    } else {
                        selector.parent().removeClass('selected');
                    }
                    break;
                case 'print_button':
                    selector = wptm_element.settingTable.find('.print_button_menu');
                    if (value === 1 || value === '1') {
                        selector.parent().addClass('selected');
                    } else {
                        selector.parent().removeClass('selected');
                    }
                    break;
                case 'enable_pagination':
                    selector = wptm_element.settingTable.find('.pagination_menu');
                    if (value === 1 || value === '1') {
                        selector.parent().addClass('selected');
                    } else {
                        selector.parent().removeClass('selected');
                    }
                    break;
                // case 'use_sortable':
                //     selector = wptm_element.settingTable.find('.sort_menu');
                //     if (value === 1 || value === '1') {
                //         selector.parent().addClass('selected');
                //     } else {
                //         selector.parent().removeClass('selected');
                //     }
                //     break;
                case 'table_align':
                    if (value !== '') {
                        selector = wptm_element.settingTable.find('.align_menu').siblings('.sub-menu').find('li[name="' + value + '"]');
                        selector.siblings('li.selected').removeClass('selected');
                        selector.addClass('selected');
                    }
                    break;
                case 'col_types':
                    if (typeof table_function_data.selection[0] !== 'undefined') {
                        var first_column_selected = table_function_data.selection[0][1];
                        if (typeof value[first_column_selected] !== 'undefined') {
                            table_function_data.type_column_selected = value[first_column_selected];
                        }
                    }
                    break;
                default:
                    break;
            }
        });
    } else {

    }
}

/**
 * Update status item in cell_menu by cell seletoed
 *
 * @param $
 * @param Wptm
 * @param selection
 */
function updateOptionValCell($, Wptm, selection) {
    var colsStyle = Wptm.style.cols;
    var cellsStyle = Wptm.style.cells;
    var rowsStyle = Wptm.style.rows;
    var size_selection = table_function_data.selectionSize - 1;
    if (size_selection < 0) {//not cell selected
        return;
    }
    var endCell = [selection[size_selection][2], selection[size_selection][3]];
    var end_cell = selection[size_selection][2] + '!' + selection[size_selection][3];

    if (!tableFunction.checkObjPropertyNested(Wptm.style, 'cells')) {//fix when Wptm.style.cells not exist
        Wptm.style.cells = {};
    }

    window.table_function_data.selectOption = true;//if == true then not click function
    var cell_style = {};

    if (typeof colsStyle[selection[size_selection][3]] !== 'undefined') {
        cell_style = $.extend({}, colsStyle[selection[size_selection][3]][1]);
    }
    if (typeof rowsStyle[selection[size_selection][2]] !== 'undefined') {
        cell_style = $.extend({}, cell_style, rowsStyle[selection[size_selection][2]][1]);
    }

    if (tableFunction.checkObjPropertyNested(Wptm.style, 'cells', end_cell, 2)) {
        if ((Wptm.type === 'html' || selection[size_selection][2] === 0) && typeof cellsStyle[end_cell] !== 'undefined') {
            cell_style = $.extend({}, cell_style, cellsStyle[end_cell][2]);
        }
    } else {//fix when Wptm.style.cells[endCell] not exist
        tableFunction.getFillArray(selection, Wptm, {});
    }

    //tooltip
    var $tooltip_content = $('#tooltip_content');
    if ($tooltip_content.length > 0) {
        tableFunction.updateParamFromStyleObject(cell_style, 'tooltip_width', $('#tooltip_width'), 0);

        tinyMCE.EditorManager.execCommand('mceRemoveEditor', true, 'tooltip_content');
        tableFunction.updateParamFromStyleObject(cell_style, 'tooltip_content', $('#tooltip_content'), "");
        var contenNeedToset = $('#tooltip_content').val();

        var initTT = tinymce.extend({}, tinyMCEPreInit.mceInit['tooltip_content']);
        try {
            tinymce.init(initTT);
        } catch (e) {
        }

        //add tinymce to this
        tinyMCE.EditorManager.execCommand('mceAddEditor', true, 'tooltip_content');
        if (tinyMCE.EditorManager.get('tooltip_content') != null) {
            var ttEditor = tinyMCE.EditorManager.get('tooltip_content');
            if (ttEditor && ttEditor.getContainer()) {
                ttEditor.setContent(contenNeedToset);
            }
        }
    }

    if (tableFunction.checkCellsOptionsValidate(table_function_data.selection, 'cell_type', 'html')) {//hide cell formats
        wptm_element.settingTable.find('a.date_menu_cell').parent('li').addClass('no_active');
        wptm_element.settingTable.find('a.curency_menu_cell').parent('li').addClass('no_active');
        wptm_element.settingTable.find('a.decimal_menu_cell').parent('li').addClass('no_active');
    } else {
        wptm_element.settingTable.find('a.date_menu_cell').parent('li').removeClass('no_active');
        wptm_element.settingTable.find('a.curency_menu_cell').parent('li').removeClass('no_active');
        wptm_element.settingTable.find('a.decimal_menu_cell').parent('li').removeClass('no_active');
    }

    tableFunction.updateParamFromStyleObject(cell_style, 'thousand_symbol', wptm_element.content_popup_hide.find('#decimal_menu_cell').find('.thousand_symbol'), '');
    tableFunction.updateParamFromStyleObject(cell_style, 'decimal_symbol', wptm_element.content_popup_hide.find('#decimal_menu_cell').find('.decimal_symbol'), '');
    tableFunction.updateParamFromStyleObject(cell_style, 'decimal_count', wptm_element.content_popup_hide.find('#decimal_menu_cell').find('.decimal_count'), '');

    tableFunction.updateParamFromStyleObject(cell_style, 'currency_symbol', wptm_element.content_popup_hide.find('#curency_menu_cell').find('.currency_symbol'), '');

    tableFunction.updateParamFromStyleObjectSelectBox(cell_style, 'symbol_position', wptm_element.content_popup_hide.find('#curency_menu_cell').find('.symbol_position').next('.wptm_select_box'), '');

    tableFunction.updateParamFromStyleObject(cell_style, 'date_formats', wptm_element.content_popup_hide.find('#date_menu_cell').find('.date_formats'), Wptm.style.table.date_formats);


    tableFunction.updateParamFromStyleObjectSelectBox(cell_style, 'cell_font_family', wptm_element.primary_toolbars.find('#cell_font_family'), 'inherit');
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_font_size', wptm_element.primary_toolbars.find('#cell_font_size'), 13);
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_font_bold', wptm_element.primary_toolbars.find('#cell_format_bold'), false, function () {
        active_table_option(this);
    });

    tableFunction.updateParamFromStyleObject(cell_style, 'cell_font_underline', wptm_element.primary_toolbars.find('#cell_format_underlined'), false,
        function () {
            active_table_option(this);
        }
    );
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_format_strikethrough', wptm_element.primary_toolbars.find('#cell_format_strikethrough'), false,
        function () {
            active_table_option(this);
        }
    );
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_font_italic', wptm_element.primary_toolbars.find('#cell_format_italic'), false,
        function () {
            active_table_option(this);
        }
    );
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_background_color', wptm_element.primary_toolbars.find('#cell_background_color'), '',
        function () {
            var color = cell_style.cell_background_color;
            this.wpColorPicker('color', color);
            this.parents('.wp-picker-container').find('.wp-color-result').css('color', color);
        }
    );
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_font_color', wptm_element.primary_toolbars.find('#cell_font_color'), '',
        function () {
            var color = cell_style.cell_font_color;
            this.wpColorPicker('color', color);
            this.parents('.wp-picker-container').find('.wp-color-result').css('color', color);
        }
    );
    tableFunction.updateParamFromStyleObject(cell_style, 'text_wrapping', wptm_element.primary_toolbars.find('#text_wrapping'), 'text_wrapping', function () {
        var value = (typeof cell_style.text_wrapping === 'undefined' || cell_style.text_wrapping === 'break-word')
            ? 'wrap_cell' : (cell_style.text_wrapping === 'normal' ? 'overflow_cell' : 'clip_cell');
        wptm_element.primary_toolbars.find('a[name="overflow_cell"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="wrap_cell"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="clip_cell"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="' + value + '"]').addClass('active');
    });

    tableFunction.updateParamFromStyleObject(cell_style, 'cell_text_align', wptm_element.primary_toolbars.find('#cell_text_align'), 'left', function () {
        var value = typeof cell_style.cell_text_align === 'undefined' ? '' : cell_style.cell_text_align;

        wptm_element.primary_toolbars.find('a[name="format_align_left"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="format_align_center"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="format_align_right"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="format_align_justify"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="format_align_' + value + '"]').addClass('active');
    });
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_vertical_align', wptm_element.primary_toolbars.find('#cell_vertical_align'), 'middle', function () {
        var value = typeof cell_style.cell_vertical_align === 'undefined' ? cell_style.cell_vertical_align = 'top' : cell_style.cell_vertical_align;

        wptm_element.primary_toolbars.find('a[name="vertical_align_bottom"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="vertical_align_middle"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="vertical_align_top"]').removeClass('active');
        wptm_element.primary_toolbars.find('a[name="vertical_align_' + value + '"]').addClass('active');
    });
    tableFunction.updateParamFromStyleObject(cell_style, 'cell_type', wptm_element.primary_toolbars.find('#cell_type'), '', function () {
        var value = cell_style.cell_type;
        if (typeof value === 'undefined' || value === '' || value === null) {
            wptm_element.primary_toolbars.find('#cell_type').removeClass('active');
        } else {
            wptm_element.primary_toolbars.find('#cell_type').addClass('active');
        }
    });
    wptm_element.primary_toolbars.find('#cell_border').find('a').each(function () {
        $(this).removeClass('active');
    });

    var mergeCellRanger = {
        'col': selection[size_selection][1],
        'colspan': selection[size_selection][3] - selection[size_selection][1] + 1,
        'row': selection[size_selection][0],
        'rowspan': selection[size_selection][2] - selection[size_selection][0] + 1
    };
    if (Wptm.mergeCellsSetting.some(function (mergeCellsSetting) {
        return mergeCellsSetting.col === mergeCellRanger.col && mergeCellsSetting.row === mergeCellRanger.row;
    })) {
        wptm_element.primary_toolbars.find('#merge_cell').addClass('active');
    } else {
        wptm_element.primary_toolbars.find('#merge_cell').removeClass('active');
    }

    window.table_function_data.selectOption = false;
    //get size(height, width) end cells
    tableFunction.getSizeCells($, Wptm, endCell);
}


function active_table_option(that) {
    if (that.val() === true) {
        that.addClass('active');
    } else {
        that.removeClass('active');
    }
}

/**
 * Create custom select box
 *
 * @param $
 * @param select_function cell function when click select
 * @param multiple multi selector
 * @param afterCreateFunc function when after create element
 */
function custom_select_box($, select_function, multiple, afterCreateFunc) {
    if (multiple) {
        $(this).addClass('multiple');
    }

    $(this).on('click', function (e) {
        let $that = $(this);
        let $wptm_select_box = $that.next();
        let old_value;

        $('#mybootstrap #wptm_popup').find('.wptm_select_box').each(function () {
            if (!$(this).is($wptm_select_box)) {
                $(this).hide();
                $(this).prev().removeClass('show');
            }
        });

        let position = $(this).position();

        if ($that.hasClass('show')) {
            $that.next().hide();
            $that.removeClass('show');
            $(document).unbind('click.wptm_select_box');
            return;
        }

        let $select = $wptm_select_box.css({
            top: position.top + 40,
            left: position.left,
            'width': $that.outerWidth()
        }).show();

        if (multiple) {
            $select.find('li input').prop("checked", false);
            if ($that.data('value')) {
                old_value = $that.data('value').split('|');
                $.each(old_value, function (i, v) {
                    $select.find('li[data-value="' + v + '"]').find('input').prop("checked", true);
                })
            }
        } else {
            old_value = $that.data('value');
        }
        $that.addClass('show');

        $select.find('li:not(.wptm_turn_off)').unbind('click').on('click', function (e) {
            let text = '', data = '';
            let $liTag = $(this);
            if (multiple) {
                if ($(e.target).is('input')) {
                    $liTag = $(e.target).parent();
                } else {
                    $liTag.find('input').prop("checked", !$liTag.find('input').is(":checked"));
                }
            }

            if (!multiple || $liTag.hasClass('data-none')) {
                text = $liTag.text();
                data = $liTag.data('value');
            } else {
                let checkAll = 0
                if ($liTag.siblings('.data_all')) {//check all
                    if($liTag.hasClass('data_all')) {
                        checkAll = 1
                        text = ''
                        data = ''
                    }
                    if ($liTag.parent().find('.data_all').find('input').is(":checked")) {
                        checkAll = 2
                        text = 'All value'
                        data = 'all_value'
                    }
                }
                $select.find('li:not(.data-none):not(.data_all)').each(function (i, e) {
                    if (checkAll >= 1) {
                        $(this).find('input').prop("checked", false)
                    } else {
                        if ($(e).find('input').is(":checked")) {
                            if (data !== '') {
                                text += ',' + $(e).text();
                                data += '|' + $(e).data('value');
                            } else {
                                text += $(e).text();
                                data += $(e).data('value');
                            }
                        }
                    }
                });
            }
            $select.data('value', data).change();
            $that.val(data).text(text).data('value', data).change();

            if (typeof select_function !== 'undefined' && select_function !== null) {
                select_function.call($select, $liTag.data('value'));
            }
            if (!multiple || $liTag.hasClass('data-none')) {
                $('#mybootstrap').find('.wptm_select_box').hide();
            }
        });

        $(document).bind('click.wptm_select_box', (e) => {
            // if (($(e.target).is($(this)) && $that.hasClass('show')) || (!$(e.target).is($(this)) && !(!!multiple && $(e.target).parents('.wptm_select_box').length > 0))) {
            if (!$(e.target).is($that) && !(!!multiple && $(e.target).parents('.wptm_select_box').length > 0)) {
                $select.hide();
                $that.removeClass('show');
                $(document).unbind('click.wptm_select_box');
            }
        });
    });
    if (typeof afterCreateFunc !== 'undefined' && afterCreateFunc !== null) {
        afterCreateFunc.call($(this))
    }
}

export default {getSelector, selectOption, loadSelection, updateOptionValTable, wptm_popup}