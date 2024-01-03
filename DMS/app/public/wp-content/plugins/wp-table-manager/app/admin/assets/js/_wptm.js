/**
 * Wptm
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 * @package Wptm
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */
import tableFunction from "./_functions";
import {initHandsontable, getMergeCells, calHeightTable} from "./_initHandsontable";
import alternating from "./_alternating";
import selectOption from "./_toolbarOptions";
import DropChart from "./_chart";
import customRenderer from "./_customRenderer";

jQuery('title').text(wptmText.EDIT_TABLE_TITLE_TAG);

jQuery(window).bind('beforeunload', function(){
    if(!checkTimeOut)
        return true;
});

/*document
variable: Wptm.newSelect = 'row' or 'col' -> when select by rows or column. undefined when other cases
*/

jQuery(document).ready(function ($) {
    window.jquery = $;

    var popup = $("#wptm_popup").draggable({
        containment: "#pwrapper",
        drag: function( event, ui ) {
            $(this).css('transform', 'none');
        },
    });

    var popup_edit_html_cell = $("#wptm_edit_html_cell").draggable({
        containment: "#pwrapper",
        drag: function( event, ui ) {
            $(this).css('transform', 'none');
        },
    });

    $('.tip').tooltipster({
        theme: 'tooltipster-borderless',
        delay: 100,
        distance: 5,
    });
    $(document).on('click', '.search-open-btn', function (e) {
        var btn = $(this);
        var search_menu = btn.closest('.search-menu');
        if (!search_menu.hasClass("open")) {
            search_menu.addClass("open");
        }
    });
    $(document).on('mouseup', function (e) {
        var container = $(".search-menu");
        // if the target of the click isn't the container nor a descendant of the container
        if (!container.is(e.target) && container.has(e.target).length === 0 && container.hasClass("open")) {
            container.removeClass("open");
        }
    });
    $('.wp-color-result-text').each(function () {
        var tip = $(this).closest('.cells_option').attr('title');
        $(this).attr('title', tip).tooltipster({
            theme: 'tooltipster-borderless',
            delay: 0,
            distance: 5,
        });
    });
    if (typeof (Wptm) == 'undefined') {
        Wptm = {};
        Wptm.can = {};
        Wptm.can.create = true;
        Wptm.can.edit = true;
        Wptm.can.delete = true;
        Wptm.selection = {};
        Wptm.value_unit_chart = [];
        Wptm.hyperlink = {};
    } else {
        Wptm.value_unit_chart = [];
    }
    Wptm.updateSettings = {};
    Wptm.saveDataDbTable = [];

    if (typeof (Wptm.can) == 'undefined') {
        Wptm.can = {};
        Wptm.can.create = true;
        Wptm.can.edit = true;
        Wptm.can.delete = true;
        Wptm.hyperlink = {};
    }

    Wptm.checkTimeOutSelection = false;

    if (typeof (wptm_isAdmin) == 'undefined') {
        wptm_isAdmin = false;
    }
    Wptm.clearHandsontable = true;
    if (typeof chart_active !== 'undefined' && parseInt(chart_active) > 0) {
        Wptm.chart_active = chart_active;
    } else {
        $('#over_loadding_open_chart').hide();
    }

    if (typeof window.parent.wptm_insert !== 'undefined' && typeof window.parent.wptm_insert.opend_table !== 'undefined') {
        delete window.parent.wptm_insert.opend_table;
    }

    window.wptm_element = {
        wpreview: $('#wptm-toolbars'),
        primary_toolbars: $('#primary_toolbars'),
        mainTabContent: $('#mainTabContent'),
        tableContainer: $('#tableContainer'),
        wptm_popup: $('#wptm_popup'),
        content_popup_hide: $('#content_popup_hide'),
        cellValue: $('#CellValue'),
        settingCells: $('#setting-cells'),
        settingTable: $('#table-setting'),
        nameTable: $('#name-table'),
        alternating_color: $('#alternating_color'),
        editToolTip: $('#editToolTip'),
        edit_html_cell: $('#edit_html_cell'),
        wptm_edit_html_cell: $('#wptm_edit_html_cell'),
        saveToolTipbtn: $('#saveToolTipbtn'),
        chartTabContent: $('#chartTabContent'),
        wptmContentChart: $('#wptm_chart .wptm_content_chart')
    };

    //saving all function data
    window.table_function_data = {
        replace_unit: {},
        text_replace_unit: '',
        text_replace_unit_function: '',
        date_format: [],
        check_value_data: true,
        string_currency_symbols: '',
        selectFetch: {},
        allAlternate: {},
        oldAlternate: {},
        changeAlternate: [],
        checkChangeAlternate: [],
        selection: [],
        styleToRender: '',
        content: '',
        needSaveAfterRender: false,
        alternateIndex: '',
        alternateSelection: [],
        save_table_params: [],
        firstRender: false,
        accountingNumber : {}
    };

    // is writing
    if (typeof idUser !== 'undefined' && idUser !== null) {
        var listUserEdit = {};
    }

    /* init menu actions */

    window.default_value = $.extend({}, {
        'use_sortable': '0',
        'fonts_used': [],
        'fonts_local_used': [],
        'default_sortable': '0',
        'default_order_sortable': '1',
        'table_align': 'center',
        'responsive_type': 'scroll',
        'table_breakpoint': '980',
        'freeze_col': 0,
        'table_height': 500,
        'style_repeated': 0,
        'freeze_row': 0,
        'enable_filters': 0,
        'enable_filters_all': 0,
        'enable_pagination': 0,
        'allRowHeight': '',
        'spreadsheet_url': '',
        'spreadsheet_style': 0,
        'auto_sync': 0,
        'auto_push': 0,
        'download_button': 0,
        'print_button': 0,
        'col_types': ["varchar","varchar","varchar","varchar","varchar","varchar","varchar","varchar","varchar","varchar"]
    }, default_value);

    if (typeof idTable !== 'undefined' && idTable !== '') {
        updatepreview(idTable);

        if ($('.wptm-page').length > 0 && $('#adminmenuwrap').length > 0) {
            Scrollbar.init(document.querySelector('#adminmenuwrap'), {
                damping: 0.5,
                thumbMinSize: 10,
                alwaysShowTracks: false
            });
        }
    }

    //create new table
    wptm_element.primary_toolbars.find('.new_table_menu').on('click', function (e) {
        e.preventDefault();

        if (!(Wptm.can.create)) {
            return;
        }

        if (!wptm_permissions.can_create_tables) {
            tableFunction.wptmBootbox('', wptm_permissions.translate.wptm_create_tables, true, false);
            return false;
        }

        var id_category = Wptm.category;
        var curr_page = window.location.href;
        var cells = curr_page.split("?");

        $.ajax({
            url: wptm_ajaxurl + "task=table.add&id_category=" + id_category,
            type: "POST",
            data: {},
            dataType: "json",
            success: function (datas) {
                if (datas.response === true) {
                    var new_url = cells[0] + '?page=wptm&id_table=' + datas.datas.id;
                    if (typeof wptm_open_table !== 'undefined' && wptm_open_table != 0) {
                        window.open(new_url);
                    } else {
                        window.location = new_url;
                    }
                } else {
                    tableFunction.wptmBootbox('', datas.response, true, false);
                }
            },
            error: function (jqxhr, textStatus, error) {
                tableFunction.wptmBootbox(textStatus + " : " + error, true, false);
            }
        });
        return false;
    });

    tableFunction.wptmCollapsible()
});

function importExcel($, that) {
    //Import Excel
    //Init call back when file is uploaded successful

    that.find(".procExcelCsv").dropzone({
        url: "admin-ajax.php?action=Wptm&task=excel.import",
        maxFiles: 1,
        init: function () {
            //Update form action
            this.on("addedfile", (file) => {
                var dotPos = file.name.lastIndexOf('.') + 1;
                var ext = file.name.substr(dotPos, file.name.length - dotPos);

                if ($(this.element).hasClass('procCsv')) {
                    if (ext !== 'csv') {
                        tableFunction.wptmBootbox('', wptmText.CHOOSE_CSV_FIE_TYPE, true, false);
                        this.options.autoProcessQueue = false;
                        this.removeFile(file);
                    } else {
                        if (this.options.autoProcessQueue === false) {
                            this.options.autoProcessQueue = true;
                        }
                    }
                } else {
                    if (ext !== 'xls' && ext !== 'xlsx') {
                        tableFunction.wptmBootbox('', wptmText.CHOOSE_EXCEL_FIE_TYPE, true, false);
                        this.options.autoProcessQueue = false;
                        this.removeFile(file);
                    } else {
                        if (this.options.autoProcessQueue === false) {
                            this.options.autoProcessQueue = true;
                        }
                    }
                }
            });

            this.on("sending", function (file, xhr, formData) {
                tableFunction.WptmLoading(that.find(".loading-page"), 50, 0, 10);
                $('.prevent_event_popup').show();
                file.previewElement.innerHTML = "";//remove Layout dropzone
                //Add table id to formData
                $("#jform_id_table").val(idTable);
                formData.append('id_table', idTable);
                if ($(this.element).hasClass('procCsv')) {
                    formData.append('onlydata', 0);
                } else {
                    formData.append('onlydata', that.find("#import_style").val());
                    formData.append('getListSheet', that.find('#select_sheet_import').val() || 1);
                }

                // Show the total progress bar when upload starts
                that.find(".progress").show();
                that.find(".progress-bar-success").css('width', 30 + '%');
                that.find(".progress-bar-success").css('opacity', 1);
                // And disable the start button
            });

            this.on("success", function (file, responseText) {
                var responseObj = JSON.parse(responseText);
                file.previewElement.innerHTML = "";//remove Layout dropzone

                if (responseObj.response === true) {
                    if (typeof responseObj.datas.too_large !== 'undefined') {
                        tableFunction.wptmBootbox('', responseObj.datas.msg, true, true, () => {
                            var jsonVar = {
                                id_table: responseObj.datas.id,
                                onlydata: responseObj.datas.onlydata,
                                file: encodeURI(responseObj.datas.file),
                                ignoreCheck: 1
                            };
                            $.ajax({
                                url: wptm_ajaxurl + "task=excel.import",
                                type: 'POST',
                                data: jsonVar,
                                success: function (datas) {
                                    tableFunction.WptmLoading(that.find(".loading-page"), 101, 50, 5);
                                    location.reload();
                                }
                            })
                        });
                    } else {
                        tableFunction.WptmLoading(that.find(".loading-page"), 101, 50, 5);
                        setTimeout(() => {
                            location.reload();
                            $('.prevent_event_popup').hide();
                        }, 700);
                    }

                    setTimeout(function () {
                        that.find(".progress").fadeOut(1000);
                        that.find('.colose_popup').trigger('click');
                        $('.prevent_event_popup').hide();
                    }, 500);
                } else {
                    setTimeout(() => {
                        tableFunction.WptmLoading(that.find(".loading-page"), 101, 50, 5, responseObj.datas, 'hasError');
                        $('.prevent_event_popup').hide();
                    }, 500);
                    // tableFunction.wptmBootbox('', typeof responseObj.datas !== 'undefined' ? responseObj.datas : 'Has error when import file!', true, false);
                }
            });

            this.on('complete', function (file) {
                this.removeFile(file);
                setTimeout(function () {
                    that.find(".progress-bar-success").css('width', 0);
                }, 6000);
            });
            // Update the total progress bar
            this.on("uploadprogress", function (file, progress) {
                that.find(".progress-bar-success").css('width', progress + "%");
            });
        }
    });
}

//Call ajax to get all data of table, add value to Wptm, table_function_data
function updatepreview(id, ajaxCallBack) {
    /*remove after change theme*/
    delete table_function_data.changeTheme;
    var url = wptm_ajaxurl + "view=table&format=json&id=" + id;
    var $ = jquery;

    $.ajax({
        url: url,
        type: "POST",
        data: {},
        dataType: "json",
    }).done(function (data) {
        //TODO: check user role table
        Wptm.id = id;
        Wptm.title = data.title;
        /*not change data cell(in db table)*/
        Wptm.type = 'html';
        Wptm.category = data.id_category;

        /*set height rows variable*/
        Wptm.rowsHeight = {};
        Wptm.table_height = 0;

        Wptm.hyperlink = {};
        if (typeof data.type !== 'undefined') {
            Wptm.type = data.type;
        } else {
            Wptm.type = (typeof data.params.table_type !== 'undefined') ? data.params.table_type : Wptm.type;
        }

        if (data.datas === '' || data.datas === false) {
            delete Wptm.datas;
            Wptm.datas = [
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""],
                ["", "", "", "", "", "", "", "", "", ""]
            ];
        } else {
            data.datas = JSON.stringify(data.datas);
            try {
                Wptm.datas = JSON.parse(data.datas);
            } catch (err) {
                Wptm.datas = [
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""],
                    ["", "", "", "", "", "", "", "", "", ""]
                ];
            }
        }

        Wptm.style = $.parseJSON(data.style);

        Wptm.css = data.css.replace(/\\n/g, "\n");

        if (data.params === "" || data.params === null || data.params.length == 0) {
            Wptm.mergeCellsSetting = [];
            Wptm.headerOption = 1;
        } else {
            if (typeof (data.params) == 'string') {
                data.params = $.parseJSON(data.params);
            }

            if (typeof data.params.headerOption !== 'undefined') {
                Wptm.headerOption = parseInt(data.params.headerOption);
            } else {
                Wptm.headerOption = 1;
            }

            if (typeof data.params.hyperlink !== 'undefined') {
                if (typeof data.params.hyperlink === 'string') {
                    data.params.hyperlink = data.params.hyperlink.replaceAll('\\\\"', '\\"');
                    data.params.hyperlink = $.parseJSON(data.params.hyperlink);
                }
                $.each(data.params.hyperlink, function (index, value) {
                    var rowCol = index.split("!");
                    if(typeof Wptm.datas[rowCol[0]] !== 'undefined') {
                        if (typeof value.text == 'undefined' || typeof value.text == 'object') {
                            value.text =  Wptm.datas[rowCol[0]][rowCol[1]];
                        }
                        if (wptmOpenHyperlink == 1) {
                            Wptm.datas[rowCol[0]][rowCol[1]] = '<a target="_blank" href="' + value.hyperlink + '">' + value.text + '</a>';
                        } else {
                            Wptm.datas[rowCol[0]][rowCol[1]] = '<a href="' + value.hyperlink + '">' + value.text + '</a>';
                        }
                    }
                });
                Wptm.hyperlink = data.params.hyperlink;
            }

            try {
                if (typeof data.params.mergeSetting === 'string') {
                    Wptm.mergeCellsSetting = $.parseJSON(data.params.mergeSetting);
                } else if (typeof data.params.mergeSetting === 'object') {
                    Wptm.mergeCellsSetting = data.params.mergeSetting;
                }
                if (Wptm.mergeCellsSetting == null) {
                    Wptm.mergeCellsSetting = [];
                }
            } catch (e) {
                Wptm.mergeCellsSetting = [];
            }
        }

        /*set default table data*/
        if (typeof (Wptm.style) === 'undefined' || Wptm.style === null) {
            $.extend(Wptm, {
                style: {
                    table: {},
                    rows: {},
                    cols: {},
                    cells: {}
                },
                css: ''
            });
        }

        if (typeof (Wptm.style.rows) === 'undefined' || Wptm.style.rows === null) {
            Wptm.style.rows = {};
        }
        if (typeof (Wptm.style.cols) === 'undefined' || Wptm.style.cols === null) {
            Wptm.style.cols = {};
            for (var number_col in Wptm.datas[0]) {
                Wptm.style.cols[number_col] = [parseInt(number_col), {res_priority: parseInt(number_col)}];
            }
        } else {
            for (var number_col in Wptm.datas[0]) {
                if (typeof window.Wptm.style.cols[number_col] === 'undefined' || window.Wptm.style.cols[number_col] === null) {
                    Wptm.style.cols[number_col] = [parseInt(number_col), {res_priority: parseInt(number_col)}];
                } else  if (typeof window.Wptm.style.cols[number_col][1].res_priority === 'undefined') {
                    Wptm.style.cols[number_col][1].res_priority =  parseInt(number_col);
                }
            }
        }
        if (typeof (Wptm.style.cells) === 'undefined' || Wptm.style.cells === null) {
            Wptm.style.cells = {};
        }

        tableFunction.mergeCollsRowsstyleToCells();

        Wptm.style.table = $.extend({}, window.default_value, Wptm.style.table);

        if (typeof Wptm.style.table.syn_hash !== 'undefined' && Wptm.style.table.syn_hash !== '') {
            window.Wptm.syn_hash = Wptm.style.table.syn_hash;
        }

        if (typeof table_function_data.auto_sync !== 'undefined') {
            Wptm.style.table.auto_sync = table_function_data.auto_sync;
            Wptm.style.table.spreadsheet_style = table_function_data.spreadsheet_style;
            Wptm.style.table.spreadsheet_url = table_function_data.spreadsheet_url;
            delete table_function_data.auto_sync;
        }

        if (Wptm.style.table.spreadsheet_url !== '' && Wptm.style.table.spreadsheet_url.indexOf("docs.google.com/spreadsheet") === -1) {//when first auto syn after update 2.7.0
            if (table_function_data.auto_sync == 1) {
                Wptm.style.table.excel_auto_sync = 1;
                Wptm.style.table.auto_sync = 0;
                Wptm.style.table.excel_spreadsheet_style = Wptm.style.table.spreadsheet_style;
            }
            Wptm.style.table.excel_url = table_function_data.spreadsheet_url;
            table_function_data.spreadsheet_url = '';
            Wptm.style.table.spreadsheet_url = '';
        }

        if (Wptm.headerOption > 0 && (typeof Wptm.style.table.header_data === 'undefined' || Wptm.style.table.header_data.length < Wptm.headerOption)) {
            Wptm.style.table.header_data = [];
            for (var j = 0; j < Wptm.headerOption; j++) {
                Wptm.style.table.header_data[j] = Wptm.datas[j];
            }
        }


        window.table_function_data = tableFunction.createRegExpFormat(table_function_data, Wptm.style.table.currency_symbol, Wptm.style.table.date_formats);

        if (Wptm.style.table.date_formats !== '') {
            window.table_function_data.date_formats_momentjs = tableFunction.momentjsFormat(Wptm.style.table.date_formats);
        }

        window.Wptm = $.extend({}, window.Wptm, Wptm);

        $('#jform_css').val(Wptm.css);
        $('#jform_css').change();
        tableFunction.parseCss($);

        tableFunction.setFormat_accounting();

        var handRisize, clearHandRisize;
        window.onresize = function () {
            if (!clearHandRisize) {
                clearTimeout(handRisize);
            }

            clearHandRisize = false;
            handRisize = setTimeout(function () {
                var height = tableFunction.calculateTableHeight(window.jquery('#wptm-toolbars'));
                jquery(Wptm.container).handsontable('updateSettings', {height: height});
                clearHandRisize = true;
            }, 500);
        };

        if (typeof (Wptm.style.table.alternateColorValue) === 'undefined' || typeof Wptm.style.table.alternateColorValue[0] === 'undefined') {
            var styleRows = null;
            alternating.setAlternateColor(styleRows, window.Wptm, window.wptm_element);
        }

        if (_.size(window.table_function_data.oldAlternate) < 1) {
            window.table_function_data.oldAlternate = $.extend({}, Wptm.style.table.alternateColorValue);
        }

        if (_.size(window.table_function_data.allAlternate) < 1) {
            window.table_function_data.allAlternate = $.extend({}, Wptm.style.table.allAlternate);
        }

        //update option table
        selectOption.updateOptionValTable($, window.Wptm, [0, 0, 0, 0]);

        codemirror_tooltip($);

        $('#list_chart').find('.current_table a').text(Wptm.title);
        if (Wptm.type !== 'html') {
            // wptm_element.primary_toolbars.find('.wptm_name_edit').after('<div class="wptm_warning"><p>' + wptmText.notice_msg_table_database + '</p></div>');
            if (wptm_element.settingTable.find('.table-menu a.source_menu').length > 0) {
                wptm_element.settingTable.find('.table-menu a.source_menu').parent().show();
            }
            if (Wptm.style.table.allRowHeight < 10 || isNaN(Wptm.style.table.allRowHeight)) {
                Wptm.style.table.allRowHeight = 30;
            }
        } else {
            wptm_element.settingTable.find('.table-menu a.source_menu').parent().hide();
        }

        /*convert */

        // window.Wptm.type = 'html';

        //check edit columns, get column key
        if (window.Wptm.type === 'mysql') {
            Wptm.query_option = data.query_option;
            if (Wptm.query_option.column_options !== null && Wptm.query_option.column_options.length > 0) {
                table_function_data.columns = [];
                for (var i in Wptm.query_option.column_options) {
                    if (parseInt(Wptm.query_option.column_options[i].canEdit) !== 1 || (Wptm.query_option.column_options[i].table + '.' + Wptm.query_option.column_options[i].Field === Wptm.style.table.priKey)) {
                        table_function_data.columns[i] = {readOnly: true};
                    } else {
                        table_function_data.columns[i] = {readOnly: false}
                    }
                    if (typeof Wptm.style.table.lock_columns !== 'undefined'
                        && Wptm.style.table.lock_columns[i] != 0) {
                        table_function_data.columns[i] = {readOnly: true};
                    }
                    if (Wptm.style.table.priKey === Wptm.query_option.column_options[i].table + '.' + Wptm.query_option.column_options[i].Field) {
                        table_function_data.keyPosition = i;
                    }
                }
            }
        // } else {
        //     if (wptm_administrator !== 1 && typeof Wptm.style.table.protect_columns !== 'undefined') {
        //         table_function_data.columns = [];
        //         for (var ij2 in Wptm.style.table.protect_columns) {
        //             if (Wptm.style.table.protect_columns[ij2] != 0) {
        //                 table_function_data.columns[ij2] = {readOnly: true};
        //             } else {
        //                 table_function_data.columns[ij2] = {readOnly: false}
        //             }
        //         }
        //     }
        }

        if (typeof Wptm.style.table.lock_ranger_cells !== 'undefined' && Wptm.style.table.lock_ranger_cells.length > 0) {
            tableFunction.create_ranger_cells_lock(Wptm, table_function_data, null);
        }

        table_function_data.mysqlEdit = window.Wptm.type === 'mysql' && parseInt(Wptm.table_editing) === 1;

        table_function_data.FormulaParser = new FormulaParser({
            onCell: ({sheet, row, col}) => {
                // using 1-based index
                // return the cell value, see possible types in next section.
                let data = Wptm.datas[row - 1][col - 1]

                if (!isNaN(parseFloat(data)) && typeof data !== 'boolean')
                    data = parseFloat(data);
                // if (data[0] === '=') {
                //     console.log(row - 1, col - 1, data)
                    // data = data.replaceAll(';', ',')
                    // let datas = table_function_data.FormulaParser.getCell({row: row, col: col})
                    // let datas = customRenderer.render(false, false, row - 1, col - 1, false, data, 'cellProperties', true)
                    // data = datas
                    // data = 16
                // }

                return data;
            },
                // retrieve range values
            onRange: (ref) => {
                // using 1-based index
                // Be careful when ref.to.col is MAX_COLUMN or ref.to.row is MAX_ROW, this will result in
                // unnecessary loops in this approach.
                const arr = [];

                for (let row = ref.from.row; row <= ref.to.row; row++) {
                    const innerArr = [];
                    if (Wptm.datas[row - 1]) {
                        for (let col = ref.from.col; col <= ref.to.col; col++) {
                            let data = Wptm.datas[row - 1][col - 1]
                            if (!isNaN(parseFloat(data)) && typeof data !== 'boolean')
                                data = parseFloat(data);
                            innerArr.push(data);
                        }
                    }
                    arr.push(innerArr);
                }
                return arr;
            }
        })
        /*render table */
        initHandsontable(Wptm.datas);
        wptm_element.primary_toolbars.find('.wptm_name_edit').click(function () {
            if (!$(this).hasClass('editable')) {
                tableFunction.setText.call(
                    $(this),
                    wptm_element.primary_toolbars.find('.wptm_name_edit'),
                    '#primary_toolbars .wptm_name_edit',
                    {'url': wptm_ajaxurl + "task=table.setTitle&id=" + Wptm.id + '&title=', 'selected': true}
                );
            }
        });

        $(window).bind('keydown', function(event) {//CTRL + S
            if (!(event.which == 83 && (event.ctrlKey || event.metaKey)) && !(event.which == 19)) return true;
            tableFunction.saveChanges(true);
            event.preventDefault();
            return false;
        });

        $('#cell_zoom_size').on('change', (e) => {
            // var p = ["webkit", "moz", "ms", "o"],
            //     content = '';
            // for (var i1 = 0; i1 < p.length; i1++) {
            //     wptm_element.tableContainer[0].style[p[i1] + "Transform"] = "scale(" + parseFloat(e.target.value) + ")";
            //     wptm_element.tableContainer[0].style[p[i1] + "TransformOrigin"] = '0% 0%';
            // }

            // setTimeout(() => {
            //     Wptm.container.handsontable("render");
                // jquery(Wptm.container).handsontable('render');
            // }, 200)

            tableFunction.wptmSetZoom(e.target.value, false)
        })

        if (typeof Wptm.wptm_error_message_read_file !== 'undefined') {
            tableFunction.wptmBootbox(wptmText.import_is_finished, wptmText.error_message_read_file + Wptm.title + wptmText.error_message_read_file_cells_concerned +
                Wptm.wptm_error_message_read_file, true, false, null, null, 'warning');
        }
    });
}

function checkLightOrDark(color) {
    var r, g, b, hsp;

    if (color.match(/^rgb/)) {
        color = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);
        r = color[1];
        g = color[2];
        b = color[3];
    }
    else {
        color = +("0x" + color.slice(1).replace(color.length < 5 && /./g, '$&$&'));
        r = color >> 16;
        g = color >> 8 & 255;
        b = color & 255;
    }

    hsp = Math.sqrt(
        0.299 * (r * r) +
        0.587 * (g * g) +
        0.114 * (b * b)
    );

    if (hsp>127.5) {
        return 1;
    }
    else {
        return 0;
    }
}

//custom cell editor
function codemirror_tooltip($) {
    window.CustomEditor = Handsontable.editors.TextEditor.prototype.extend();

    window.CustomEditor.prototype.open = function () {
        // console.log($(this.TEXTAREA).parent())
        // $(this.TEXTAREA).parent().removeClass('wptm_hiden')
        $(this.TEXTAREA).attr('id', 'editor1');
        var lightOrDark = checkLightOrDark($(this.TEXTAREA).css( "backgroundColor" ));
        if (lightOrDark === 0) {
            $(this.TEXTAREA).css({'color':'#ffffff'});
        } else {
            $(this.TEXTAREA).css({'color':'#000000'});
        }
        var cell_html = false;
        if (typeof (Wptm.style.cells[this.row + '!' + this.col]) !== 'undefined' && typeof (Wptm.style.cells[this.row + '!' + this.col][2].cell_type) !== 'undefined' && Wptm.style.cells[this.row + '!' + this.col][2].cell_type === 'html') {
            cell_html = true;
            Handsontable.editors.TextEditor.prototype.close.apply(this, arguments);
            tinyMCE.EditorManager.execCommand('mceRemoveEditor', true, 'html_cell_content');

            var initTT = tinymce.extend({}, tinyMCEPreInit.mceInit['html_cell_content']);
            try {
                tinymce.init(initTT);
            } catch (e) {
            }

            //add tinymce to this
            var content = '';
            if (typeof Wptm.datas[table_function_data.selection[0][0]][table_function_data.selection[0][1]] !== 'undefined'
            && Wptm.datas[table_function_data.selection[0][0]][table_function_data.selection[0][1]] !== null) {
                content = Wptm.datas[table_function_data.selection[0][0]][table_function_data.selection[0][1]];
            }
            tinyMCE.EditorManager.execCommand('mceAddEditor', true, 'html_cell_content');
            if (tinyMCE.EditorManager.get('html_cell_content') != null) {
                var ttEditor = tinyMCE.EditorManager.get('html_cell_content');
                if (ttEditor && ttEditor.getContainer()) {
                    ttEditor.setContent(content);
                }
            }

            wptm_element.edit_html_cell.trigger('click');
        } else {
            tinyMCE.EditorManager.execCommand('mceRemoveEditor', true, 'html_cell_content');
            Handsontable.editors.TextEditor.prototype.open.apply(this, arguments);
        }

        // change width of popup when click html cell
        if (!cell_html) {
            wptm_element.tableContainer.find('.handsontableInputHolder').css('position', 'absolute').css('visibility', 'visible');
        }
    };

    window.CustomEditor.prototype.getValue = function () {
        if (typeof (tinyMCE) === 'undefined' || !tinyMCE.EditorManager.get('html_cell_content')) {

            return Handsontable.editors.TextEditor.prototype.getValue.apply(this, arguments);
        }
    };

    window.CustomEditor.prototype.setValue = function (newValue) {
        var cell_html = false;
        if (typeof (Wptm.style.cells[this.row + '!' + this.col]) !== 'undefined' && typeof (Wptm.style.cells[this.row + '!' + this.col][2].cell_type) !== 'undefined' && Wptm.style.cells[this.row + '!' + this.col][2].cell_type === 'html') {
            cell_html = true;
        }
        if (!cell_html) {
            return Handsontable.editors.TextEditor.prototype.setValue.apply(this, arguments);
        }
    };

    wptm_element.edit_html_cell.wptm_leanModal({
        top: 100, background: '#ffffff', closeButton: '#cancel_html_cell_btn',
        beforeInit: function () {
            var position = tableFunction.setPositionForHtmlCellEditor();
            this.top = position.top;
            this.left = position.left + 'px';
            this.marginLeft = '0px';
        },
        before_colose_modal: function () {
            var content = tinyMCE.EditorManager.get('html_cell_content').getContent();
            window.jquery(window.Wptm.container).data('handsontable').setDataAtCell(table_function_data.selection[0][0], table_function_data.selection[0][1], content);
        }
    });

    //codemirror
    var myTextArea = document.getElementById("jform_css");
    var myCssEditor = CodeMirror.fromTextArea(myTextArea, {mode: "css", lineNumbers: true});
    var ww = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    var wh = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
    if (window.parent) {
        ww = window.parent.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        wh = window.parent.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
    }

    myCssEditor.setSize(ww * 50 / 100, wh - 500);

    wptm_element.primary_toolbars.find('.custom-menu').wptm_leanModal({
        top: 200,
        background: '#ffffff',
        closeButton: '#cancelCssbtn',
        beforeShow: function () {
            var selection = table_function_data.selection;
            var ht = jQuery(Wptm.container).handsontable('getInstance');
            ht.updateSettings({
                cells: function (row, col, prop) {
                    if (selection[0][0] === row && selection[0][1] === col) {
                        var cellProperties = {};

                        cellProperties.readOnly = true;
                        return cellProperties;
                    }
                }
            });
            window.jquery(Wptm.container).handsontable("selectCell", selection[0][0], selection[0][1], selection[0][0], selection[0][1]);
        },
        before_colose_modal: function () {
            var selection = table_function_data.selection;

            var ht = jQuery(Wptm.container).handsontable('getInstance');
            ht.updateSettings({
                cells: function (row, col, prop) {
                    if (selection[0][0] === row && selection[0][1] === col) {
                        var cellProperties = {};

                        cellProperties.readOnly = false;
                        return cellProperties;
                    }
                }
            });
            window.jquery(Wptm.container).handsontable("selectCell", selection[0][0], selection[0][1], selection[0][0], selection[0][1]);
        },
        modalShow: function () {
            myCssEditor.refresh();
            myCssEditor.focus();
        }
    });

    $(myTextArea).on('change', function () {
        myCssEditor.setValue($(myTextArea).val().replace(/\\n/g, "\n"));
    });

    // myCssEditor.on("blur", function() {
    $("#saveCssbtn").click(function () {
        myCssEditor.save();
        tableFunction.parseCss($);
        $(myTextArea).trigger("propertychange");
        //close leanModal
        $("#lean_overlay").fadeOut(200);
        $("#wptm_customCSS").css({"display": "none"})
    });

    wptm_element.editToolTip.wptm_leanModal({
        top: 100, background: '#ffffff', closeButton: [$('#cancelToolTipbtn'), $('#wptm_editToolTip .colose_popup')], modalShow: function () {
        }
    });

    wptm_element.saveToolTipbtn.click(function () {
        var ttEditor = tinyMCE.EditorManager.get('tooltip_content');
        ttEditor.save();
        $("#tooltip_content").trigger("change");
        //close leanModal
        $("#lean_overlay").fadeOut(200);

        $("#wptm_editToolTip").css({"display": "none"});
        $('#edit_toolTip').trigger('change');
    })

    tinyMCEPreInit.mceInit['html_cell_content'] = tinyMCEPreInit.mceInit['wptmditor'];
    tinyMCEPreInit.mceInit['tooltip_content'] = tinyMCEPreInit.mceInit['wptm_tooltip'];
    tinyMCE.EditorManager.execCommand('mceRemoveEditor', true, 'wptmditor');
    $('#wp-wptmditor-wrap').hide();
}

//fetch google sheet, import file excel
function fetchSpreadsheet(obj) {
    tableFunction.loading(wptm_element.wpreview);

    var auto_sync, spreadsheet_style, url;
    var $close_popup = wptm_element.wptm_popup.find('.colose_popup');
    var loader = wptm_element.wptm_popup.find('.lds-ring');
    var popup_notification = wptm_element.wptm_popup.find('.popup_notification');
    var data;
    let select_sheet = 0

    if (obj.type === 'spreadsheet') {
        data = tableFunction.getLinkSync(Wptm.style.table.spreadsheet_url, 'spreadsheet', true);
        if (!data) {
            return false;
        }
        select_sheet = Wptm.style.table.select_sheet_google || 0;
        // url = encodeURI(Wptm.style.table.spreadsheet_url);
        // auto_sync = Wptm.style.table.auto_sync != '1' ? 0 : 1;
        // spreadsheet_style = Wptm.style.table.spreadsheet_style != '1' ? 0 : 1;
        // if (url.indexOf("docs.google.com/spreadsheet") === -1) {
        //     bootbox.alert(wptmText.error_link_google_sync, wptmText.GOT_IT);
        //     Wptm.style.table.spreadsheet_url = '';
        //     return;
        // }
        //
        // //check publish link
        // var end_link = url.split('?');
        // var end_link0 = end_link[0].slice(-7);
        // if (end_link0.match(/html|csv|pdf|xlsx/gi) === null && (typeof end_link[1] !== "undefined" && end_link[1].match(/sharing|html|csv|pdf|xlsx/gi) === null)) {
        //     bootbox.alert(wptmText.error_link_google_sync, wptmText.GOT_IT);
        //     Wptm.style.table.spreadsheet_url = '';
        //     return;
        // }
    } else if (obj.type === 'excel') {
        data = tableFunction.getLinkSync(Wptm.style.table.excel_url, 'excel', true);
        if (!data) {
            return false;
        }
        select_sheet = Wptm.style.table.select_sheet || 0;
        // url = encodeURI(Wptm.style.table.excel_url);
        // auto_sync = Wptm.style.table.excel_auto_sync != '1' ? 0 : 1;
        // spreadsheet_style = Wptm.style.table.excel_spreadsheet_style != '1' ? 0 : 1;
        // if (url.indexOf("docs.google.com/spreadsheet") !== -1) {
        //     bootbox.alert(wptmText.error_link_import_sync, wptmText.GOT_IT);
        //     Wptm.style.table.excel_url = '';
        //     return;
        // }
    } else if (obj.type === 'onedrive') {
        data = tableFunction.getLinkSync(Wptm.style.table.onedrive_url, 'onedrive', true);
        if (!data) {
            return false;
        }
        select_sheet = Wptm.style.table.select_sheet_one || 0;
    } else if (obj.type === 'csv') {
        data = tableFunction.getLinkSync(Wptm.style.table.csv_url, 'csv', true);
        if (!data) {
            return false;
        }

    }

    var jsonVar = {
        spreadsheet_url: data.url,
        id: Wptm.id,
        sync: data.auto_sync,
        syncType: obj.type,
        getListSheet: select_sheet,
        style: data.spreadsheet_style || 0
    };
    delete table_function_data.fetch_data;

    jquery.ajax({
        url: wptm_ajaxurl + "task=excel.fetchSpreadsheet&id=" + Wptm.id,
        type: "POST",
        data: jsonVar,
        beforeSend: function() {
            loader.removeClass('wptm_hiden');
            popup_notification.addClass('wptm_hiden');
        },
        success: function (datas) {
            loader.addClass('wptm_hiden');
            var result = jQuery.parseJSON(datas);
            if (result.response === true) {
                if (obj.type === 'spreadsheet') {
                    // table_function_data.auto_sync = result.datas.sync;
                    table_function_data.spreadsheet_style = result.datas.style;
                    table_function_data.spreadsheet_url = Wptm.style.table.spreadsheet_url;
                } else if(obj.type === 'excel') {
                    // table_function_data.excel_auto_sync = result.datas.sync;
                    table_function_data.excel_spreadsheet_style = result.datas.style;
                    table_function_data.excel_url = Wptm.style.table.excel_url;
                } else if(obj.type === 'onedrive') {
                    // table_function_data.excel_auto_sync = result.datas.sync;
                    table_function_data.onedrive_style = result.datas.style;
                    table_function_data.onedrive_url = Wptm.style.table.onedrive_url;
                } else if(obj.type === 'onedrive') {
                    table_function_data.csv_url = Wptm.style.table.csv_url;
                }
                updatepreview(Wptm.id);
                popup_notification.html('<span class="noti_success">'+wptmText.DATA_HAS_BEEN_FETCHED+'</span>');
                if (typeof result.datas.error !== 'undefined' && result.datas.error !== '') {
                    popup_notification.html('<span class="noti_false">'+result.datas.error+'</span>');
                    popup_notification.removeClass('wptm_hiden');
                } else {
                    if (typeof result.datas.error_read_file !== 'undefined' && result.datas.error_read_file !== '') {
                        setTimeout(function () {
                            tableFunction.wptmBootbox(wptmText.import_is_finished, wptmText.error_message_read_file_cells_concerned +
                                result.datas.error_read_file, true, false, null, null, 'warning');
                        }, 500);
                    }
                    popup_notification.removeClass('wptm_hiden');
                    $close_popup.trigger('click');
                }
            } else {
                if (result.datas && result.datas[0] === 'list sheet') {

                } else {
                    popup_notification.html('<span class="noti_false">'+result.response+'</span>');
                    popup_notification.removeClass('wptm_hiden');
                }
            }
            tableFunction.rloading(wptm_element.wpreview);
        },
        error: function (jqxhr, textStatus, error) {
            popup_notification.html('<span class="noti_false">'+wptmText.have_error+'</span>');
            popup_notification.removeClass('wptm_hiden');
            tableFunction.rloading(wptm_element.wpreview);
        }
    });
}

function updateDimession() {
    var rows = [];
    var i = 0;
    for (var row in Wptm.style.rows) {
        var h = jQuery('#tableContainer .ht_master .htCore tr').eq(i + 1).height();
        rows[row] = h;
        i++;
    }

    jQuery(Wptm.container).handsontable('updateSettings', {rowHeights: rows});

    var ht = jQuery(Wptm.container).handsontable('getInstance');
    ht.runHooks('afterRowResize');
}

export default {
    updatepreview,
    importExcel,
    fetchSpreadsheet
};
