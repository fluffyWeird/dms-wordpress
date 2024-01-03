(function ($) {
    var wptmPrintTableOptions = [];
    var wptm_number_reg = new RegExp('[0-9| |\.|\,|\\-]', "g");

    var getScrollbarWidth = function (selector) {
        var scrollWidth;
        if (typeof selector.get(0) === 'undefined') {
            scrollWidth = 0;
        } else {
            scrollWidth = selector.get(0).offsetWidth - selector.get(0).clientWidth + 1;//fix when width .5px
            if (scrollWidth <= 1) {
                scrollWidth = 20;
            }
        }
        return scrollWidth;
    };
    var addMarginTopToFixedColumn = function (currentTable, numberRows) {
        var scrollWrapper = currentTable.closest(".DTFC_ScrollWrapper");
        if (scrollWrapper.length < 1) {
            scrollWrapper = currentTable.closest(".dataTables_wrapper");
        }

        var totalRowHeight = scrollWrapper
            .find(".DTFC_TopBodyWrapper")
            .outerHeight();

        var leftScroll = scrollWrapper.find(".DTFC_LeftBodyLiner table");
        leftScroll.css({
            marginTop: totalRowHeight + "px",
        });
    };
    var filterDelay = null;
    var filterAllDelay = null;
    var filterDelayInterval = 250;


    var cloneFixedTopWrapper = function (currentTable, numberRows, numCols) {
        var scrollWrapper = currentTable.closest(".DTFC_ScrollWrapper");
        if (scrollWrapper.length < 1) {
            scrollWrapper = currentTable.closest(".dataTables_wrapper");
        }

        var scrollBody = scrollWrapper
            .find(".dataTables_scroll")
            .find(".dataTables_scrollBody");
        var topWrapper = scrollWrapper.find(".dataTables_scroll").clone();
        var tableScrollHeaderHeight = scrollWrapper
            .find(".dataTables_scrollHead")
            .outerHeight();

        topWrapper.removeClass("dataTables_scroll").addClass("DTFC_TopWrapper");
        topWrapper.css({
            position: "absolute",
            top: tableScrollHeaderHeight + "px",
            left: "0px",
            width: "100%",
            paddingRight: getScrollbarWidth(scrollBody) + "px",
            height: "1px",
        });
        //change scroll head class
        topWrapper
            .find(".dataTables_scrollHead")
            .addClass("DTFC_TopHeadWrapper")
            .removeClass("dataTables_scrollHead")
            .attr("style", "")
            .css({
                position: "relative",
                top: "0",
                left: "0",
                height: "0",
                overflow: "hidden",
            });
        topWrapper
            .find(".DTFC_TopHeadWrapper table")
            .addClass("DTFC_Cloned")
            .unwrap()
            .wrap('<div class="DTFC_TopHeadLiner"></div>');
        topWrapper.find(".DTFC_TopHeadWrapper table thead tr").css({
            height: 0,
        });

        var totalRowHeight = 0;

        var datatableScroll = scrollWrapper.find(".dataTables_scroll");
        var datatableScrollBody = datatableScroll.find(".dataTables_scrollBody");
        var datatableScrollBodyTable = datatableScrollBody.find("table");

        for (var i = 0; i < numberRows; i++) {
            var dataScrollRow = scrollWrapper
                .find(".dataTables_scrollBody table tbody tr")
                .eq(i);
            totalRowHeight += dataScrollRow.outerHeight();
            datatableScrollBodyTable.find("tbody tr").eq(i).addClass("hidden_row");
        }

        topWrapper
            .find(".dataTables_scrollBody")
            .addClass("DTFC_TopBodyWrapper")
            .removeClass("dataTables_scrollBody")
            .attr("style", "")
            .css({
                position: "relative",
                top: "0",
                left: "0",
                height: totalRowHeight + "px",
                overflow: "hidden",
            });
        topWrapper
            .find(".DTFC_TopBodyWrapper table")
            .removeAttr("id")
            .addClass("DTFC_Cloned")
            .wrap('<div class="DTFC_TopBodyLiner"></div>');
        topWrapper.find(".DTFC_TopBodyLiner").css({
            overflowX: "scroll",
        });
        topWrapper.find(".DTFC_TopBodyLiner table thead tr").addClass('hidden_row');
        topWrapper.find(".DTFC_TopBodyLiner table tbody tr.droptable_none").remove();

        topWrapper.appendTo(scrollWrapper);

        //set margin top for original table
        datatableScrollBodyTable.css({
            marginTop: totalRowHeight + "px",
        });

        if (numCols > 0) {
            var topLeftWrapper = scrollWrapper.find(".DTFC_TopWrapper").clone();
            topLeftWrapper
                .addClass("DTFC_TopLeftWrapper")
                .removeClass("DTFC_TopWrapper");

            topLeftWrapper.css({
                padding: 0,
                width: scrollWrapper.find(".DTFC_LeftWrapper").width() + "px",
            });
            topLeftWrapper
                .find(".DTFC_TopBodyLiner")
                .addClass("DTFC_TopLeftBodyLiner")
                .removeClass("DTFC_TopBodyLiner");

            topLeftWrapper.appendTo(scrollWrapper);
        }

        var mainScroll = scrollWrapper.find(".dataTables_scrollBody");
        var topBodyScroll = scrollWrapper.find(".DTFC_TopBodyLiner");
        mainScroll.scroll(function () {
            topBodyScroll.scrollLeft($(this).scrollLeft());
        });
        // initFilterRow(tableDom);
    };

    var calculateHeaderColspanResponsive = function (table, tableDom, colWidths) {
        var header = tableDom.find('thead .row0').eq(0);
        var colspans = [];
        table.columns().every(function (index) {
            var currFirstColWidth = 0;
            var thCol = header.find('th[data-dtc="' + index + '"]');
            var nextColIndexes = [];
            if (thCol.attr('colspan') > 1) {
                var currColIndex = thCol.data('dtc');
                var numberColspan = thCol.attr('colspan');
                colspans.push([currColIndex]);
                nextColIndexes.push(currColIndex);
                for (var i = 0; i < numberColspan; i++) {
                    i++;
                    var nextColIndex = currColIndex + i;
                    nextColIndexes.push(nextColIndex);
                }
            }
        });
    };

    var hideFilterOnResponsive = function (table, tableDom) {
        var filterRow = tableDom.find(".wptm-filter-row");
        table.columns().every(function (index) {
            var thCol = filterRow.find('th[data-dtc="' + index + '"]');
            if (thCol.length < 1) {
                thCol = filterRow.find('th.dtc' + index);
            }
            if (this.responsiveHidden()) {
                thCol.css({display: ""});
            } else {
                thCol.css({display: "none"});
            }
        });
    };

    function setFormat_accounting_for_cells(setFormat_accounting_for_cells_raw) {
        return {
            currency: {
                symbol: setFormat_accounting_for_cells_raw.currency_symbol,   // default currency symbol is '$'
                format: setFormat_accounting_for_cells_raw.symbol_position == 1 ? "%v%s" : "%s%v", // controls output: %s = symbol, %v = value/number (can be object: see below)
                decimal: setFormat_accounting_for_cells_raw.decimal_symbol,  // decimal point separator
                thousand: setFormat_accounting_for_cells_raw.thousand_symbol,  // thousands separator
                precision: typeof setFormat_accounting_for_cells_raw.decimal_count !== 'undefined' ? setFormat_accounting_for_cells_raw.decimal_count : 0   // decimal places
            },
            number: {
                decimal: setFormat_accounting_for_cells_raw.decimal_symbol,  // decimal point separator
                thousand: setFormat_accounting_for_cells_raw.thousand_symbol,  // thousands separator
                precision: typeof setFormat_accounting_for_cells_raw.decimal_count !== 'undefined' ? setFormat_accounting_for_cells_raw.decimal_count : 0   // decimal places
            }
        };
    }

    // UMD
    (function (factory) {
        "use strict";

        if (typeof define === 'function' && define.amd) {
            // AMD
            define(['jquery'], function ($) {
                return factory($, window, document);
            });
        } else if (typeof exports === 'object') {
            // CommonJS
            module.exports = function (root, $) {
                if (!root) {
                    root = window;
                }

                if (!$) {
                    $ = typeof window !== 'undefined' ?
                        require('jquery') :
                        require('jquery')(root);
                }

                return factory($, root, root.document);
            };
        } else {
            // Browser
            factory(jQuery, window, document);
        }
    }
    (function ($, window, document) {

    }));

    window.wptm_render_tables = function (id_table) {
        var $parent_table = this;

        function change_width_table_window_resize_or_visible(table_id) {
            var currTable = $("#" + table_id);
            var tableWrapper = currTable.closest(".dataTables_wrapper");
            var wptmtable = tableWrapper.parent();

            var colWidths = currTable.data('colwidths');
            var totalWidth = 0;

            if (typeof colWidths !== "undefined") {
                for (var i = 0; i < colWidths.length; i++) {
                    totalWidth += colWidths[i];
                }
            }

            if (currTable.data("responsive")) {
                if (totalWidth > 0) {
                    if (wptmtable.width() >= totalWidth) {
                        tableWrapper.css('width', currTable.data('tablewidth'));
                    } else {
                        tableWrapper.css("width", "100%");
                        currTable.css("width", "100%");
                    }
                }
            } else {
                var parent_tableWrapper_width = tableWrapper.parent().width();

                var scrollBarWidth = 1;
                if (parent_tableWrapper_width > 0) {
                    if (tableWrapper.width() >= parent_tableWrapper_width) {
                        tableWrapper.css("width", "100%");
                        if (tableWrapper.width() >= currTable.width()) {
                            tableWrapper.css("width", currTable.width());
                        }
                    } else {
                        scrollBarWidth = getScrollbarWidth(
                            // currTable.closest(".dataTables_scroll").find(".dataTables_scrollBody")
                            typeof currTable.data('freezerow') !== 'undefined' ? currTable.closest(".dataTables_scrollBody") : currTable.closest(".dataTables_scroll")
                        );
                        // console.log(currTable.outerWidth() + scrollBarWidth)
                        tableWrapper.css("width", currTable.outerWidth() + scrollBarWidth);
                    }
                } else {//when hidden table container when first load(fix for slide in BEAVER BUILDER)
                    scrollBarWidth = getScrollbarWidth(
                        // currTable.closest(".dataTables_scroll").find(".dataTables_scrollBody")
                        typeof currTable.data('freezerow') !== 'undefined' ? currTable.closest(".dataTables_scrollBody") : currTable.closest(".dataTables_scroll")
                    );

                    tableWrapper.css('width', currTable.data('tablewidth') + scrollBarWidth);
                }

                if (tableWrapper.find('.repeatedHeader').length > 0) {
                    var table_breakpoint = tableWrapper.find('.repeatedHeader').data('table-breakpoint');

                    table_breakpoint = parseInt(table_breakpoint) > 0 ? table_breakpoint : 980;

                    if (window.innerWidth <= table_breakpoint) {
                        tableWrapper.find('.repeatedHeader').addClass('repeatedHeaderTrue');
                    } else {
                        tableWrapper.find('.repeatedHeader').removeClass('repeatedHeaderTrue');
                    }
                }
            }
        }

        $(window).resize(function () {
            $(".wptmtable").each(function (index, obj) {
                var wptmtable = $(obj);
                var table_id = wptmtable.data("tableid");

                //check in elementor editor
                if (typeof id_table !== 'undefined' && parseInt(id_table) > 0 && table_id !== 'wptmTbl' + id_table) {
                    return false;
                }

                change_width_table_window_resize_or_visible(table_id);
            });
        });

        var keyupDelay = function (callback, ms) {
            var timer = 0;
            return function () {
                var context = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    callback.apply(context, args);
                }, ms || 0);
            };
        };
        $(document).ready(function () {
            const createDefaultForForm = async function (id, columnsInForm, $dom, tableOptions) {
                return new Promise((resolve) => {
                    // console.clear()
                    $.each(columnsInForm, (index, columnForm) => {
                        if (columnForm && columnForm !== true) {
                            let $searchDiv = $dom.find('.searchDiv[data-column="' + index + '"]')
                            let type = $searchDiv.data('searchtype')
                            let typeContent = $searchDiv.data('type')
                            let ii = 0;
                            switch(type) {
                                case 'select':
                                    let $select = $searchDiv.find('select')
                                    tableOptions.columnsInFormData[index] = []

                                    $.each(columnForm, (i, v) => {
                                        if (/<\/?[a-z][\s\S]*>/i.test(v)) {
                                            let $p = document.createElement('p')
                                            $p.innerHTML = v

                                            tableOptions.columnsInFormData[index][ii] = $p.childNodes[0].innerHTML

                                            $select.append('<option value="' + ii + '">' + i + '</option>')
                                        } else {
                                            $select.append('<option value="' + v + '">' + i + '</option>')
                                        }
                                        ii++
                                    })

                                    break;
                                case 'checkBox':
                                    let $modalBody = $searchDiv.find('.wptm_modal-body')
                                    tableOptions.columnsInFormData[index] = []
                                    $.each(columnForm, (i, v) => {
                                        if (/<\/?[a-z][\s\S]*>/i.test(v)) {
                                            let $p = document.createElement('p')
                                            $p.innerHTML = v

                                            tableOptions.columnsInFormData[index][ii] = $p.childNodes[0].innerHTML

                                            $modalBody.append('<div class="wptm_checkbox_option"><input type="checkbox" class="wptm_filter_checkbox" id="' + index + 'wptm_filter_checkbox' + ii + '" value="'
                                                + ii + '"><label class="wptm_filter_checkbox_label" for="' + index + 'wptm_filter_checkbox' + ii + '">' + i + '</label></div>')
                                        } else {
                                            $modalBody.append('<div class="wptm_checkbox_option"><input type="checkbox" class="wptm_filter_checkbox" id="' + index + 'wptm_filter_checkbox' + ii + '" value="'
                                                + v + '"><label class="wptm_filter_checkbox_label" for="' + index + 'wptm_filter_checkbox' + ii + '">' + i + '</label></div>')
                                        }
                                        ii++
                                    })
                                    break;
                            }
                        }
                    })

                    resolve(true)
                })
            }

            const getDefaultValueForCols = async function (id, columnsInForm, tableOptions, select, checkBox) {
                let listColGetAjax = []
                let hiddenColumns = tableOptions.hidecolumn || null

                for (let j in columnsInForm) {
                    if (columnsInForm[j]) {
                        if (typeof checkBox[j] !== 'undefined' && checkBox[j].length > 0) {
                            // checkBox[j] = Array.from(checkBox[j], (x, i) => {
                            //     console.log(x, i)
                            //     return x
                            // })
                            let checkBoxContent = checkBox[j].reduce((acc, cur) => ({...acc, [cur]: cur}), {})
                            columnsInForm[j] = checkBoxContent;
                        } else if (typeof checkBox[j] !== 'undefined' || typeof select[j] !== 'undefined') {
                            listColGetAjax.push(j)
                        }
                    }
                }

                if (typeof window.wptm_ajaxurl === "undefined") {
                    window.wptm_ajaxurl = wptm_data.wptm_ajaxurl;
                }
                //hidecolumn
                return new Promise((resolve) => {
                    if (listColGetAjax.length > 0) {
                        $.ajax({
                            url: window.wptm_ajaxurl + "task=table.getDefaultValueForCols",
                            dataType: "json",
                            traditional: true,
                            type: "POST",
                            data: $.param({listCols: listColGetAjax, id: id, hiddenColumns: hiddenColumns}),
                            beforeSend: function () {
                            },
                            success: function (datas) {
                                if (datas.success === true) {
                                    $.extend(columnsInForm, datas.data)
                                }
                                resolve(columnsInForm)
                            },
                            error: function (jqxhr, textStatus, error) {
                                resolve(columnsInForm)
                            }
                        });
                    } else {
                        resolve(columnsInForm)
                    }
                });
            }

            function wptm_render_table(index, obj) {
                var wptmtable = $(obj);
                var table_id = wptmtable.data("tableid");

                //check in elementor editor
                if (typeof id_table !== 'undefined' && parseInt(id_table) > 0 && table_id !== 'wptmTbl' + id_table) {
                    return false;
                }

                if (wptmtable.find('.dataTable').length > 0) {
                    return false;
                }

                var tableOptions = {};
                var tableDom = wptmtable.find("#" + table_id);

                //$hideScrollHeader

                if (!tableDom.length) {
                    return;
                }

                if (typeof tableDom.DataTable === 'undefined') {
                    return false;
                }
                tableOptions.tableFormat = {}
                tableOptions.tableFormat.decimal_count = tableDom.data('format-decimal_count')
                tableOptions.tableFormat.decimal_symbol = tableDom.data('format-decimal_symbol')
                tableOptions.tableFormat.symbol_position = tableDom.data('format-symbol_position')
                tableOptions.tableFormat.thousand_symbol = tableDom.data('format-thousand_symbol')
                tableOptions.tableFormat.currency_symbol = tableDom.data('format-currency_symbol')

                var table_id_num = tableDom.data('id');
                var colWidths = tableDom.data('colwidths');
                tableOptions.totalWidth = 0;
                if (typeof colWidths !== "undefined") {
                    for (var i = 0; i < colWidths.length; i++) {
                        tableOptions.totalWidth += colWidths[i];
                    }
                }

                //dataTables_wrapper
                //tableWrapper, tableDom, tableOptions.totalWidth
                function change_width(tableWrapper, tableDom, totalWidth) {
                    if (!tableDom.data("change_width")) {
                        var parent_tableWrapper_width = tableWrapper.parent().width();

                        if (tableDom.data("responsive")) {
                            if (totalWidth > 0) {
                                if (parent_tableWrapper_width >= totalWidth) {
                                    tableWrapper.css('width', totalWidth + 'px');
                                    tableDom.css('width', '100%');
                                } else {
                                    tableWrapper.css("width", "100%");
                                    tableDom.css("width", "100%");
                                }
                            }
                        } else {
                            var scrollBarWidth = getScrollbarWidth(
                                typeof tableDom.data('freezerow') !== 'undefined' ? tableDom.closest(".dataTables_scrollBody") : tableDom.closest(".dataTables_scroll")
                            );
                            var width_content = 0;
                            if (totalWidth > tableDom.outerWidth()) {
                                width_content = totalWidth;
                            } else {
                                width_content = tableDom.outerWidth();
                            }

                            if (tableWrapper.width() >= parent_tableWrapper_width || (tableWrapper.width() == 0 && isHidden(tableWrapper[0]))) {
                                tableWrapper[0].style.width = '100%';
                                setTimeout(() => {
                                    if (tableWrapper.width() >= width_content) {
                                        width_content = width_content + scrollBarWidth;
                                    } else {
                                        if (tableWrapper.width() > 0) width_content = tableWrapper.width() + 10;
                                    }

                                    tableWrapper.css("width", width_content);
                                }, 100);
                            } else {
                                tableWrapper.css("width", width_content + scrollBarWidth);
                            }

                            //repeated header responsive
                            setTimeout(() => {
                                if (tableWrapper.find('.repeatedHeader').length > 0) {
                                    var table_breakpoint = tableWrapper.find('.repeatedHeader').data('table-breakpoint');
                                    table_breakpoint = parseInt(table_breakpoint) > 0 ? table_breakpoint : 980;
                                    if (window.innerWidth <= table_breakpoint) {
                                        tableWrapper.find('.repeatedHeader').addClass('repeatedHeaderTrue');
                                    }
                                }
                            }, 100);
                        }
                        tableDom.data("change_width", true);
                    }
                }

                tableDom.attr("data-tablewidth", tableDom.width());

                tableOptions.orderCellsTop = false;
                tableOptions.dom = '<"top">rt<"bottom"pl><"clear">';
                var tableLanguage = {};
                if (tableDom.data("hidecols")) {
                    tableOptions.dom = '<"top">Bfrt<"bottom"pl><"clear">';
                    tableOptions.buttons = ["colvis"];
                    tableLanguage.buttons = {colvis: tableDom.data("hidecolslanguage")};
                }
                tableLanguage.lengthMenu =
                    '<select><option value="10">10</option><option value="20">20</option><option value="40">40</option><option value="-1">All</option></select>';

                var pagination_merge_cells = [];
                tableOptions.juHideColumn = tableDom.data('hidecolumn');
                tableOptions.juHideColumnClass = [];
                tableOptions.juHideColumns = [];
                if (typeof tableOptions.juHideColumn !== 'undefined' && tableOptions.juHideColumn.length > 0) {
                    var i = 0, j;
                    for (j in tableOptions.juHideColumn) {
                        if (j >= 0)
                            if (tableOptions.juHideColumn[j] > 0) {//hide
                                i++;
                            } else {
                                tableOptions.juHideColumnClass.push(i);
                                tableOptions.juHideColumns[i] = i - tableOptions.juHideColumnClass.length + 1;
                                i++;
                            }
                    }
                }

                if (tableDom.data("paging")) {
                    //if (tableDom.data("type") === 'html') {
                    tableOptions.processing = true;
                    tableOptions.serverSide = true;
                    tableOptions.paging = true;
                    tableOptions.scrollX = true;

                    if (typeof window.wptm_ajaxurl === "undefined") {
                        window.wptm_ajaxurl = wptm_data.wptm_ajaxurl;
                    }

                    tableOptions.ajax = {
                        'url': window.wptm_ajaxurl + 'task=table.loadPage&id=' + table_id_num,
                        'type': 'POST',
                        'dataSrc': function (json) {
                            console.log(json)
                            if (!json.success) {
                                return false
                            }

                            return json.data;
                        },
                        'dataFilter': function (json) {
                            var json = jQuery.parseJSON(json);
                            var data = {};

                            data.recordsTotal = json.data.recordsTotal;
                            data.recordsFiltered = json.data.recordsFiltered;
                            data.data = json.data.data;
                            data.page = json.data.page;
                            data.draw = json.data.draw;
                            data.success = json.success;
                            console.log(data)

                            return JSON.stringify(data); // return JSON string
                        }
                    };

                    tableOptions.lengthMenu = [
                        [10, 20, 40, -1],
                        [10, 20, 40, "All"],
                    ];
                    tableLanguage.paginate = {
                        first:
                            "<i class='icon-step-backward glyphicon glyphicon-step-backward'></i>",
                        previous:
                            "<i class='icon-arrow-left glyphicon glyphicon-backward'></i>",
                        next: "<i class='icon-arrow-right glyphicon glyphicon-forward'></i>",
                        last:
                            "<i class='icon-step-forward glyphicon glyphicon-step-forward'></i>",
                    };

                    // tableOptions.pagingType = "full_numbers";
                    tableOptions.pagingType = 'simple_numbers';
                    tableOptions.lengthChange = true;
                }

                tableOptions.date_format = tableDom.data("format");
                tableLanguage.first_load = true;
                tableLanguage.left_header = -1;

                wptmtable.find('thead tr').each(function (key, value) {
                    $(value).addClass('row_index' + (key - 1)).data('row-index', (key - 1));
                    tableLanguage.left_header++;
                });

                var number_load = 0;

                tableOptions.fnDrawCallback = function (oSettings) {
                    wptm_tooltip();
                    setTimeout(() => {
                        var this_oApi = this.oApi;

                        $('.dataTables_wrapper .dataTables_scrollBody thead').hide();
                        $('.DTFC_LeftBodyWrapper thead').hide();
                        var x2 = $.extend([], oSettings.aiDisplay);
                        if (!tableDom.data("paging")) {
                            number_load++;
                            var sort_time = [];
                            var complement_sort_time = [];
                            if (tableDom.data('ordering')) {
                                var order = this.api().order();
                                if (typeof order !== 'undefined' && typeof order[0] === 'object') {
                                    order = order[0];
                                } else {
                                    order = [0, 'asc']
                                }
                            }
                            // console.log(order, oSettings)
                            oSettings.aiDisplay.forEach(function (value, key) {
                                var i, data, $row = wptmtable.find("tbody .row" + (value + tableLanguage.left_header)),
                                    rowSpan = 0;

                                if (tableLanguage.first_load) {
                                    $row.addClass('row_index' + (value + tableLanguage.left_header)).data('row-index', (value + tableLanguage.left_header));
                                } else {
                                    data = wptmtable.find("tbody .row" + (value + tableLanguage.left_header)).data('row-index');
                                    $row.removeClass('row_index' + data).addClass('row_index' + (key + tableLanguage.left_header)).data('row-index', (key + tableLanguage.left_header));
                                }
                                if (oSettings.aiDisplay.length === key + 1) {
                                    tableLanguage.first_load = false;
                                }

                                if (tableDom.data('ordering')) {
                                    if ($row.find('td').filter(function () {
                                        // console.log(this.rowSpan)
                                        if (this.rowSpan > 1) {
                                            rowSpan = (this.rowSpan - 1) >= rowSpan ? (this.rowSpan - 1) : rowSpan;
                                            return true;
                                        } else {
                                            if ($(this).hasClass('dtc' + order[0])) {
                                                // console.log($(this).data('timestamp'), value + tableLanguage.left_header - 1)
                                                if ($(this).data('timestamp') > 0) {
                                                    sort_time.push([value + tableLanguage.left_header - 1, $(this).data('timestamp')]);
                                                } else {
                                                    complement_sort_time.push([value + tableLanguage.left_header - 1, 0])
                                                }
                                                return true;
                                            }
                                        }
                                        return false;
                                    }).length > 0) {
                                        // console.log(sort_time)
                                        if (sort_time.length < 1) {
                                            // console.log(rowSpan)
                                            for (i = 0; i < rowSpan; i++) {
                                                x2 = x2.filter(data => data !== (i + value + 1));
                                                var x3 = x2.indexOf(value);
                                                x2.splice((x3 + i + 1), 0, (i + value + 1));
                                            }
                                        }
                                    }

                                    if (key + 1 === oSettings.aiDisplay.length) {
                                        if (number_load === 1) {
                                            if (sort_time.length > 0) {
                                                if (oSettings.aiDisplay.length > sort_time.length) {
                                                    sort_time = sort_time.concat(complement_sort_time);
                                                }
                                                if (order[1] === 'asc') {
                                                    sort_time.sort(function (a, b) {
                                                        return a[1] - b[1]
                                                    });
                                                } else {
                                                    sort_time.sort(function (a, b) {
                                                        return b[1] - a[1]
                                                    });
                                                }
                                                var sort_time2 = sort_time.map(x => x[0]);
                                                oSettings.aiDisplay = $.extend([], sort_time2);
                                                // console.log(sort_time2)
                                                this_oApi._fnDraw(oSettings);
                                            } else {
                                                oSettings.aiDisplay = $.extend([], x2);
                                                // console.log(x2)
                                                this_oApi._fnDraw(oSettings);
                                            }
                                        } else {
                                            number_load = 0;
                                        }
                                    }
                                }
                            });
                        }
                    }, 300);

                    if (!tableWrapper) {
                        var tableWrapper = tableDom.closest(".dataTables_wrapper");
                    }

                    if (tableDom.data("paging")) {
                        change_width(tableWrapper, tableDom, tableOptions.totalWidth);
                    }
                };

                tableOptions.formatCellContent = function ($td, value, [rowIndex, colIndex], tableFormat, dataFormat, check = false) {
                    let format= []
                    if (dataFormat === null) {
                        format.decimal_count = $td !== null && $td.data('format-decimal_count') ? $td.data('format-decimal_count') : (tableFormat !== null ? tableFormat.decimal_count : tableOptions.tableFormat.decimal_count)
                        format.decimal_symbol = $td !== null && $td.data('format-decimal_symbol') ? $td.data('format-decimal_symbol') : (tableFormat !== null ? tableFormat.decimal_symbol : tableOptions.tableFormat.decimal_symbol)
                        format.symbol_position = $td !== null && $td.data('format-symbol_position') ? $td.data('format-symbol_position') : (tableFormat !== null ? tableFormat.symbol_position : tableOptions.tableFormat.symbol_position)
                        format.thousand_symbol = $td !== null && $td.data('format-thousand_symbol') ? $td.data('format-thousand_symbol') : (tableFormat !== null ? tableFormat.thousand_symbol : tableOptions.tableFormat.thousand_symbol)
                        format.currency_symbol = $td !== null && $td.data('format-currency_symbol') ? $td.data('format-currency_symbol') : null
                    } else {
                        format.decimal_count = dataFormat.count !== null ? dataFormat.count : (tableFormat !== null ? tableFormat.decimal_count : tableOptions.tableFormat.decimal_count)
                        format.decimal_symbol = dataFormat.decimal !== null ? dataFormat.decimal : (tableFormat !== null ? tableFormat.decimal_symbol : tableOptions.tableFormat.decimal_symbol)
                        format.symbol_position = dataFormat.position !== null ? dataFormat.position : (tableFormat !== null ? tableFormat.symbol_position : tableOptions.tableFormat.symbol_position)
                        format.thousand_symbol = dataFormat.thousand !== null ? dataFormat.thousand : (tableFormat !== null ? tableFormat.thousand_symbol : tableOptions.tableFormat.thousand_symbol)
                        format.currency_symbol = typeof dataFormat.currency !== 'undefined' && dataFormat.currency !== null ? dataFormat.currency : null

                    }
                    let accounting_for_cells = setFormat_accounting_for_cells(format)
                    if (format.currency_symbol !== null) {
                        value = accounting.formatMoney(value, accounting_for_cells.currency);
                    } else if($td !== null && format.decimal_symbol !== null) {
                        value = accounting.formatNumber(value, accounting_for_cells.number);
                    } else if($td === null) {
                        value = accounting.formatNumber(value, accounting_for_cells.number);
                    }

                    return value;
                }

                if (!tableOptions.paging) {
                    tableOptions.dataCells = [];
                }

                tableOptions.createdRow = function (row, data, dataIndex) {
                    var keys = Object.keys(data);
                    let $row = $(row);
                    var $cRow;
                    let dataCellsInRow = []

                    // console.log(dataIndex + tableLanguage.left_header)
                    if (typeof data.DT_RowId !== 'undefined') {
                        $row.addClass('row' + data.DT_RowId + ' row_index' + (dataIndex + tableLanguage.left_header)).data('row-index', (dataIndex + tableLanguage.left_header));
                    } else {
                        $row.addClass(' row_index' + (dataIndex + tableLanguage.left_header)).data('row-index', (dataIndex + tableLanguage.left_header));
                    }

                    if (typeof data.merges !== 'undefined') {
                        $.each(data.merges, function (key, value) {
                            if (typeof tableOptions.juHideColumns !== 'undefined'
                                && typeof tableOptions.juHideColumns[value[2]] !== 'undefined'
                                && tableOptions.juHideColumns[value[2]] > 0) {
                                value[2] = parseInt(value[2]) - tableOptions.juHideColumns[value[2]];
                            } else {
                            }

                            $row.find('td:nth-child(' + (1 + parseInt(value[2])) + ')').attr('colspan', value[3]).attr('rowspan', value[1]);
                            //merger rows
                            if (typeof pagination_merge_cells[value[0]] == 'undefined') {
                                pagination_merge_cells[value[0]] = [];
                            }
                            pagination_merge_cells[value[0]][value[2]] = value;
                            var rowspanI = 0;
                            for (rowspanI = 0; rowspanI < parseInt(value[1]); rowspanI++) {
                                if (typeof pagination_merge_cells[parseInt(value[0]) + rowspanI] == 'undefined') {
                                    pagination_merge_cells[parseInt(value[0]) + rowspanI] = [];
                                }
                                pagination_merge_cells[parseInt(value[0]) + rowspanI][value[2]] = value;
                            }
                        });
                    }

                    var ii = 0;
                    let regex2 = new RegExp('(dtc)([0-9]+)');
                    let regex3 = new RegExp('(sort)([0-9]+)');
                    let regex4 = new RegExp('(filter)([0-9]+)');
                    keys.forEach(function (key, index) {
                        if (key !== 'merges' && key !== 'DT_RowId' && key !== 'format_date_cell' && key !== 'format_number' && !(regex3.exec(key) !== null) && !(regex4.exec(key) !== null)) {
                            $cRow = $row.find('td:nth-child(' + (parseInt(key) + 1).toString() + ')');
                            let columnIndex = key;
                            if (typeof tableOptions.juHideColumn !== 'undefined') {
                                columnIndex = tableOptions.juHideColumnClass[key];
                            }
                            let columnClass = 'dtc' + columnIndex;
                            if (typeof tableOptions.pagingType !== 'undefined' && typeof tableOptions.juHideColumn !== 'undefined') {
                                if (key !== 'DT_RowId') {
                                    if ($cRow.length) {
                                        if (typeof data.DT_RowId !== 'undefined') {
                                            $cRow.addClass('dtr' + data.DT_RowId).addClass(columnClass);
                                        } else {
                                            $cRow.addClass(columnClass);
                                        }
                                    }
                                }
                            } else {
                                if (key !== 'DT_RowId') {
                                    if ($cRow.length) {
                                        let classColumn = $cRow.attr("class").split(' ').filter(classname => regex2.exec(classname) !== null)
                                        if (classColumn[0]) {
                                            $cRow.removeClass(classColumn[0]);
                                        }

                                        if (typeof data.DT_RowId !== 'undefined') {
                                            $cRow.addClass('dtr' + data.DT_RowId).addClass(columnClass);
                                        } else {
                                            $cRow.addClass(columnClass);
                                        }
                                    }
                                }
                            }

                            if (key !== 'DT_RowId' && typeof data.DT_RowId !== 'undefined' && typeof pagination_merge_cells[data.DT_RowId] !== 'undefined') {//has merger
                                pagination_merge_cells[data.DT_RowId].forEach(function (value, key) {
                                    var colspanI = 0;
                                    for (colspanI = 0; colspanI < parseInt(value[3]); colspanI++) {
                                        if (!(parseInt(value[0]) == data.DT_RowId && colspanI === 0)) {
                                            $row.find('td:nth-child(' + (1 + parseInt(value[2]) + colspanI) + ')').css('display', 'none');
                                        }
                                    }
                                });
                            }

                            var value_format = '';
                            var value_timestamp = '';
                            let valueCell = data[key]

                            //pagination???
                            if (tableOptions.paging) {
                                if (typeof data.format_date_cell !== 'undefined' && typeof data.format_date_cell[key] !== 'undefined' && $cRow.text() !== '') {
                                    let time = moment(valueCell, 'YYYY/MM/DD HH:mm:ss')
                                    if (data.format_date_cell[key] == '1') {
                                        value_format = time.format(tableOptions.date_format);
                                        value_timestamp = time.format('X');
                                        $cRow.data('timestamp', value_timestamp).text(value_format).change();
                                    } else if (data.format_date_cell[key] !== '0') {
                                        if (time.format(data.format_date_cell[key]) === 'Invalid date') {
                                            time = moment(valueCell, data.format_date_cell[key])
                                        }
                                        value_format = time.format(data.format_date_cell[key]);
                                        value_timestamp = time.format('X');
                                        $cRow.data('timestamp', valueCell).text(value_format).change();
                                    }
                                }
                                if (typeof data.format_number !== 'undefined') {
                                    if (String(valueCell).replace(wptm_number_reg, "") === '' && (typeof data.format_number[key].decimal !== 'undefined' || typeof data.format_number[key].currency !== 'undefined')) {
                                        valueCell = tableOptions.formatCellContent($cRow, data[key], [dataIndex, key], null, data.format_number[key], false)
                                        $cRow.text(valueCell).change();
                                    }
                                }
                            } else {
                                if (typeof $cRow.data('format') !== 'undefined' && valueCell !== '') {
                                    let time = moment(valueCell, 'YYYY/MM/DD HH:mm:ss')
                                    if ($cRow.data('format') == '1') {
                                        value_format = time.format(tableOptions.date_format);
                                        value_timestamp = time.format('X');
                                        $cRow.data('timestamp', value_timestamp).text(value_format).change();
                                    } else if ($cRow.data('format') !== '0') {
                                        if (time.format($cRow.data('format')) === 'Invalid date') {
                                            time = moment(valueCell, $cRow.data('format'))
                                        }
                                        value_format = time.format($cRow.data('format'));
                                        value_timestamp = time.format('X');
                                        $cRow.data('timestamp', valueCell).text(value_format).change();
                                    }
                                } else if (String(valueCell).replace(wptm_number_reg, "") === '' && (typeof $cRow.data('format-decimal_symbol') !== 'undefined' || typeof $cRow.data('format-currency_symbol') !== 'undefined')) {
                                    valueCell = tableOptions.formatCellContent($cRow, data[key], [dataIndex, key], null, null, false)
                                    $cRow.text(valueCell).change();
                                }
                            }

                            dataCellsInRow[columnIndex] = value_format !== '' ? value_format : valueCell
                        }
                        ii++;
                    });

                    if (!tableOptions.paging)
                        tableOptions.dataCells.push(dataCellsInRow)
                };

                //sort by number format
                tableLanguage.decimal = ','
                tableLanguage.thousands = '.'
                tableLanguage.search = "Search in table:"
                tableOptions.language = tableLanguage;

                if ($(tableDom).data('columnssort')) {
                    tableOptions.columnsSort = $(tableDom).data('columnssort');
                    if (tableOptions.columnsSort.length > 0) {
                        tableOptions.columns = Array.from(Array(tableOptions.columnsSort.length), (i, v) => {
                            if (tableOptions.columnsSort[v] === 0) {
                                tableDom.find("thead tr:not(.wptm-header-cells-index):last-child").find('th.dtc' + v).addClass('wptm_no_sort')
                                return {orderable: false};
                            }
                            return null;
                        })
                    }
                }

                if ($(tableDom).data('columnsfilter')) {
                    tableOptions.columnsFilter = $(tableDom).data('columnsfilter');
                    let initFilterRow = function (tableDom) {
                        // Apply the search
                        if (tableDom.hasClass("filterable")) {
                            addFilterRowToTable(tableDom);
                        }
                    };
                    let addFilterRowToTable = function (tbl) {
                        // Add an input to latest th in header
                        tbl.find("thead tr:not(.wptm-header-cells-index):last-child th").each(function (i) {
                            if (tableOptions.columnsFilter.length < 1 || tableOptions.columnsFilter[i] === 1) {
                                var thContent = $(this).html();
                                var inputHtml = '<br><input onClick="var event = arguments[0] || window.event;event.stopPropagation();" type="text" name="wtmp_col_filter" class="wptm-d-block wptm-filter-input stop-propagation" data-index="' + i + '" value="" />';
                                $(this).html(thContent + inputHtml);
                            }
                        });
                    };

                    initFilterRow($(tableDom));
                }

                if (tableDom.data("ordering")) {
                    tableOptions.ordering = true;
                    let dataOrder = [];
                    dataOrder.push(tableDom.data("ordertarget"));
                    dataOrder.push(tableDom.data("ordervalue"));
                    tableOptions.order = dataOrder;
                }

                //fix dataTable auto pare cell content and return error alert (date data)
                tableOptions.columnDefs = [{
                    "targets": typeof tableOptions.juHideColumnClass !== 'undefined' ? [...tableOptions.juHideColumnClass.keys()] : [...tableDom.data('hidecolumn').keys()],
                    "render": function (data, type, row, meta) {
                        // console.log(data, type, row, meta)
                        if (typeof data === 'undefined' && typeof row[meta.col] !== 'undefined') {
                            return row[meta.col];
                        }
                        return data;
                    }
                }];//https://jsfiddle.net/jhp9u237/

                jQuery.fn.dataTableExt.oApi.fnDisplayRow = function (oSettings, nRow) {
                    console.log(oSettings, nRow)
                    // Account for the "display" all case - row is already displayed
                    if (oSettings._iDisplayLength == -1) {
                        return;
                    }

                    // Find the node in the table
                    var iPos = -1;
                    for (var i = 0, iLen = oSettings.aiDisplay.length; i < iLen; i++) {
                        if (oSettings.aoData[oSettings.aiDisplay[i]].nTr == nRow) {
                            iPos = i;
                            break;
                        }
                    }

                    // Alter the start point of the paging display
                    if (iPos >= 0) {
                        oSettings._iDisplayStart = (Math.floor(i / oSettings._iDisplayLength)) * oSettings._iDisplayLength;
                        if (this.oApi._fnCalculateEnd) {
                            this.oApi._fnCalculateEnd(oSettings);
                        }
                    }

                    this.oApi._fnDraw(oSettings);
                };

                $.fn.dataTable.render.moment = function (from, to, locale) {
                    // Argument shifting
                    if (arguments.length === 1) {
                        locale = 'en';
                        to = from;
                        from = 'YYYY-MM-DD';
                    } else if (arguments.length === 2) {
                        locale = 'en';
                    }

                    return function (d, type, row) {
                        if (!d) {
                            return type === 'sort' || type === 'type' ? 0 : d;
                        }

                        var m = window.moment(d, from, locale, true);

                        // Order and type get a number value from Moment, everything else
                        // sees the rendered value
                        return m.format(type === 'sort' || type === 'type' ? 'x' : to);
                    };
                };

                var table;
                tableOptions.fnInitComplete = function (settings, json) {
                    setTimeout(function () {
                        $('.dataTables_wrapper .dataTables_scrollBody thead').hide();
                        $('.DTFC_LeftBodyWrapper thead').hide();
                    }, 500);
                };

                if (typeof tableDom.data("freezecol") !== "undefined") {
                    tableOptions.fixedColumns = {
                        leftColumns: tableDom.data("freezecol")
                    }

                    // new $.fn.dataTable.FixedColumns(table, {
                    //     leftColumns: tableDom.data("freezecol"),
                    // });
                }

                // tableOptions.dom = 'Bfrtip'
                // tableOptions.buttons = ['print']
                tableOptions.headerCount = tableDom.data('count-header') || 1
                table = tableDom.on( 'init.dt', function(data) {
                    wptmPrintTableOptions.push(data)

                    wptmtable.siblings('.wptm_buttons').find('.wptm_print_table').data('optionNumber', wptmPrintTableOptions.length - 1)
                    $('.wptm_print_table').unbind('click').on('click', function (e) {
                        let $printButton = $(e.target).hasClass('wptm_print_table') ? $(e.target) : $(e.target).parents('.wptm_print_table')
                        wptmPrintTable($printButton, wptmPrintTableOptions[$printButton.data('optionNumber')])
                    })
                }(tableOptions)).DataTable(tableOptions);

                //valueRow is list columns in front-end, not by id column, key of valueRow is [0, 1, 2, 3...]
                //so, tableOptions.searchColumnsForm must get value by number of column be is show
                let dataTableSearchTimeAction
                $.fn.dataTable.ext.search.push(function (settings, valueRow, dataIndex) {
                    let data = wptmCustomSearch(settings, valueRow, dataIndex)

                    return data
                });

                const wptmCustomSearch = function (settings, valueRow, dataIndex) {
                    let validate = true
                    let searchAll = false

                    let tableOptionsTable = settings.oInit

                    if (tableOptionsTable.searchAll !== '') {
                        searchAll = true
                    }

                    if (searchAll) {
                        validate = false;
                        for (let i in valueRow) {
                            if (typeof tableOptionsTable.searchAll !== 'undefined' && tableOptionsTable.searchAll !== '') {
                                let text = tableOptionsTable.dataCells[dataIndex][i] || ''

                                if (text.toUpperCase().includes(tableOptionsTable.searchAll.toUpperCase())) {
                                    validate = true
                                }
                            }
                        }
                    }

                    if (typeof tableOptionsTable.searchColumnsForm !== 'undefined' && tableOptionsTable.searchColumnsForm.length > 0) {
                        for (let i in tableOptionsTable.searchColumnsForm) {
                            let value = tableOptionsTable.searchColumnsForm[i]
                            if (value && value !== '') {
                                let text = tableOptionsTable.dataCells[dataIndex][i] || ''
                                //checkBox use default filter of dataTable
                                if (tableOptionsTable.columnsSearchType[i] !== 'checkBox') {
                                    if (text.toUpperCase().includes(value.toUpperCase())) {
                                        validate = validate && true
                                    } else {
                                        validate = false
                                    }
                                } else {
                                    let values = value.split('|')
                                    let checkBoxSearch = false
                                    for (let i2 in values) {
                                        let textSearch = values[i2]
                                        if (text.toUpperCase().includes(textSearch.toUpperCase())) {
                                            checkBoxSearch = true
                                        }
                                    }
                                    if (checkBoxSearch) {
                                        validate = validate && true
                                    } else {
                                        validate = false
                                    }
                                }
                            }
                        }
                    }

                    //searchRange[] = [min, max, typeData] or null
                    if (typeof tableOptionsTable.searchRange !== 'undefined' && tableOptionsTable.searchRange.length > 0) {
                        let hasSearchValue = true
                        tableOptionsTable.searchRange.forEach((value, i) => {
                            if (value) {
                                // console.log(value, i)
                                let min = value[0]
                                let max = value[1]
                                hasSearchValue = false
                                if (value[2] === 'date') {
                                    let valueCell = valueRow[i]
                                    let time = moment(valueCell, 'YYYY-MM-DD')
                                    valueCell = time.format('X')
                                    if (
                                        (min === null && max === null) ||
                                        (min === null && valueCell <= max) ||
                                        (min <= valueCell && max === null) ||
                                        (min <= valueCell && valueCell <= max)
                                    ) {
                                        hasSearchValue = true;
                                    }
                                }
                                if (value[2] === 'int') {
                                    let valueCell = parseFloat(valueRow[i])
                                    if (
                                        (min === null && max === null) ||
                                        (min === null && valueCell <= max) ||
                                        (min <= valueCell && max === null) ||
                                        (min <= valueCell && valueCell <= max)
                                    ) {
                                        hasSearchValue = true;
                                    }
                                }
                            }
                            validate = validate && hasSearchValue
                        })
                    }
                    return validate
                }

                tableOptions.searchAll = ''

                $('.buttons_search_' + table_id).on('keyup clear', function (e) {
                    // tableOptions.searchAll = $(this).val().toUpperCase()
                    tableOptions.searchAll = $(this).val()

                    window.clearTimeout(filterAllDelay);
                    filterAllDelay = window.setTimeout(function () {
                        table.draw()
                    }, filterDelayInterval);
                })
                //use to search data when pagination
                table.on('preXhr.dt', function (e, settings, data) {
                    // console.clear()
                    let tableOptionsTable = settings.oInit
                    // data.searchForm =
                    data.searchAll = tableOptionsTable.searchAll
                    data.searchRange = tableOptionsTable.searchRange
                })
                table.on('draw', function () {
                    if (tableDom.hasClass('hideScrollHeader') && !tableDom.hasClass('fxdHdrCol')) {
                        let $wptmScrollHead = wptmtable.find('.dataTables_scrollHead')
                        const $wptmScrollTbody = tableDom.find('tbody')
                        $($wptmScrollHead.find('thead tr:not(.wptm-header-cells-index)').get().reverse()).each((i, v) => {
                            $(v).addClass('moveFromHeader')
                            $(v).prependTo($wptmScrollTbody)
                        })
                    }
                });

                wptmFormAction(wptmtable, $('.wptm_form_' + table_id_num), tableOptions, table);

                //hide colgroup in hiding cols option (on mobile)
                if (tableDom.data("hidecols")) {
                    var wptmCheckColumnVisibility = (tableDataTable) => {
                        var responsiveHidden = tableDataTable.columns().responsiveHidden()
                        let columns = typeof tableOptions.juHideColumnClass !== 'undefined' ? [...tableOptions.juHideColumnClass.keys()] : [...tableDom.data('hidecolumn').keys()]

                        for (let i = 0; i < columns.length; i++) {
                            responsiveHidden[i] ? tableDom.find('colgroup col:eq(' + i + ')').show() : tableDom.find('colgroup col:eq(' + i + ')').hide()
                        }
                    }

                    tableDom.on("click.dtr", function (e, settings, column, state) {
                        if ($(e.target).hasClass('dtr-control')) {
                            wptmCheckColumnVisibility(table)
                        }
                    });
                    wptmCheckColumnVisibility(table)
                }

                $(table.table().container()).on('keyup change', 'input.wptm-filter-input', function (e) {
                    e.stopPropagation();
                    columnFilter(table, $(this).data('index'), $(this).val());
                });

                var columnFilter = function (table, columnIndex, val) {
                    window.clearTimeout(filterDelay)
                    filterDelay = window.setTimeout(function () {
                        tableOptions.searchColumnsForm[columnIndex] = val
                        if (tableOptions.paging) {
                            table.column(columnIndex).search(val, !0, !1).draw();
                        } else {
                            table.draw();
                        }
                    }, filterDelayInterval);
                };

                if (tableDom.data("responsive") === true) {
                    if ($(".wptm-filter-row").length > 0) {
                        hideFilterOnResponsive(table, tableDom);
                    }
                    //calculateHeaderColspanResponsive(table, tableDom, colWidths);
                    table.on("responsive-resize", function () {
                        if ($(".wptm-filter-row").length > 0) {
                            hideFilterOnResponsive(table, tableDom);
                        }
                    });
                }

                // Change div table wrapper width
                var tableWrapper = tableDom.closest(".dataTables_wrapper");
                var tableAlign = tableDom.data("align");
                var margin = "0 0 0 auto";
                if (tableAlign === "center") {
                    margin = "0 auto";
                } else if (tableAlign === "left") {
                    margin = "0 auto 0 0";
                }
                tableWrapper.css("margin", margin);
                tableWrapper.closest(".wptm_table").css("margin", margin);

                if (!tableDom.data("paging")) {
                    change_width(tableWrapper, tableDom, tableOptions.totalWidth);
                }

                //fix height for before td in repeatedHeader
                if (tableWrapper.find('.repeatedHeader').length > 0) {

                }

                table.rows(".hidden_row").remove().draw();

                if (!tableDom.data("paging")) {
                    tableDom
                        .closest(".wptmtable")
                        .find(".dataTables_info")
                        .css("display", "none");
                }

                var hightLight = tableDom.closest(".wptm_table").data("hightlight");
                if (typeof hightLight === "undefined") {
                    hightLight = tableDom.closest(".wptm_dbtable").data("highlight");
                }
                var classHightLight = "droptables-highlight-vertical";
                if (hightLight === 1) {
                    table.on("mouseenter", "td", function () {
                        if (typeof table.cell(this).index() !== "undefined") {
                            var colIdx = table.cell(this).index().column;
                            var rowIdx = table.cell(this).index().row;
                            var affectedRow = 0;
                            var affectedCol = table.cell(this).index().column;

                            $(table.cells().nodes()).removeClass(
                                "droptables-highlight-vertical"
                            );
                            $(table.column(colIdx).nodes()).addClass(
                                "droptables-highlight-vertical"
                            );

                            table.row(rowIdx).every(function () {
                                var row = $(this.node());
                                row.find("td").addClass("droptables-highlight-vertical");
                                affectedRow = row.find("td").data("dtr");
                            });
                            var leftWrapperTable = tableDom
                                .closest(".dataTables_wrapper")
                                .find(".DTFC_LeftBodyLiner table");
                            var topWrapperTable = tableDom
                                .closest(".dataTables_wrapper")
                                .find(".DTFC_TopBodyLiner table");
                            if (leftWrapperTable.length > 0) {
                                leftWrapperTable.find("td").removeClass(classHightLight);
                                leftWrapperTable
                                    .find(".dtr" + affectedRow)
                                    .addClass(classHightLight);
                            }
                            if (topWrapperTable.length > 0) {
                                topWrapperTable.find("td").removeClass(classHightLight);
                                topWrapperTable
                                    .find(".dtc" + affectedCol)
                                    .addClass(classHightLight);
                            }
                        }
                    });
                    table.on("mouseleave", "td", function () {
                        if (typeof table.cell(this).index() !== "undefined") {
                            $(table.cells().nodes()).removeClass(
                                "droptables-highlight-vertical"
                            );

                            var leftWrapperTable = tableDom
                                .closest(".dataTables_wrapper")
                                .find(".DTFC_LeftBodyLiner table");
                            var topWrapperTable = tableDom
                                .closest(".dataTables_wrapper")
                                .find(".DTFC_TopBodyLiner table");
                            if (leftWrapperTable.length > 0) {
                                leftWrapperTable.find("td").removeClass(classHightLight);
                            }
                            if (topWrapperTable.length > 0) {
                                topWrapperTable.find("td").removeClass(classHightLight);
                            }
                        }
                    });
                }

                /*if table not  content*/
                if (tableDom.find('td.dataTables_empty').length > 0) {
                    tableDom.css({width: '95%'});
                }

                //change width table when table visible in toggle
                respondToVisibility = function (element, callback) {
                    var options = {
                        root: document.documentElement
                    }

                    var observer = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            callback(entry.intersectionRatio > 0);
                        });
                    }, options);

                    observer.observe(element);
                }
                if (document.getElementById(table_id)) {
                    respondToVisibility(document.getElementById(table_id), visible => {
                        if (visible) {
                            change_width_table_window_resize_or_visible(table_id);
                        } else {
                            console.log("Not Visible");
                        }
                    });
                } else {
                    respondToVisibility(tableWrapper[0], visible => {
                        if (visible) {
                            change_width_table_window_resize_or_visible(table_id);
                        } else {
                            console.log("Not Visible");
                        }
                    });
                }

                //download and print button
                const $buttons = $('.buttons_' + table_id)
                $buttons.prependTo($buttons.next().find('.dataTables_wrapper'))

                let wptmPrintTable = async (that, CurrentTableOptions) => {
                    var $table = $('#' + that.data('table-id'))
                    var tableTitle = that.data('title') || 'Table Print'
                    var id = $table.data('id')
                    var ajaxUrl = window.wptm_ajaxurl
                    var styleUrl = window.wptm_style
                    var frontUrl = window.wptm_front
                    var colWidths = $table.data('colwidths')

                    //loadding
                    that.addClass('printing');

                    const getBlobURL = (code, type) => {
                        const blob = new Blob([code], {type})
                        return URL.createObjectURL(blob)
                    }

                    const getGeneratedPageURL = ({html, css, js}) => {
                        if (html) {
                            let id = $table.data('id')
                            window.wptmprint[id].Columns = colWidths.map((v, i) => {
                                return {data: '' + i}
                            })
                            window.wptmprint[id].order = $table.data("ordering") ? table.order() : null
                            window.wptmprint[id].$button = that
                            window.wptmprint[id].$table = $table
                            window.wptmprint[id].ajaxUrl = ajaxUrl
                            window.wptmprint[id].tableOptions = CurrentTableOptions
                            window.wptmprint[id].table = table
                            window.wptmprint[id].tableTitle = tableTitle
                            console.log(tableOptions, CurrentTableOptions)

                            const cssURL = getBlobURL(css, 'text/css')
                            // const jsURL = getBlobURL(js, 'text/javascript')

                            // const jsURL = 'http://localhost/wordpress58/wp-content/plugins/wp-table-manager/app/site/assets/DataTables/datatables.js?ver=123'
                            const wptm_print_moment = frontUrl + 'js/moment.js'
                            const wptm_print_jdateformatparser = frontUrl + 'js/moment-jdateformatparser.js'
                            const wptm_print_table = frontUrl + 'js/wptm_print_table.js'
                            const jsURL = frontUrl + 'DataTables/datatables.js'
                            // const jsURL2 = 'http://localhost/wordpress58/wp-content/plugins/wp-table-manager/app/site/assets/js/wptm_front.js?ver=123'
                            const jsURL2 = ''
                            const source = `
                <html>
                    <head>
                    <title>` + tableTitle + `</title>
                        <script>
                            var jQuery = window.parent.jQuery
                            var wptm_id = ` + id + `
                        </script>
                        ${jsURL && `<script src="${jsURL}"></script>`}
                        ${wptm_print_moment && `<script src="${wptm_print_moment}"></script>`}
                        ${wptm_print_jdateformatparser && `<script src="${wptm_print_jdateformatparser}"></script>`}
                        ${wptm_print_table && `<script src="${wptm_print_table}"></script>`}
                        
                        ${frontUrl && `<link rel="stylesheet" media = "print" type="text/css" href="${frontUrl + 'css/front.css?ver=123'}" />`}
                        ${frontUrl && `<link rel="stylesheet" media = "print" type="text/css" href="${frontUrl + 'DataTables/datatables.css?ver=123'}" />`}
                        ${styleUrl && `<link rel="stylesheet" media = "print" type="text/css" href="${styleUrl + $table.data('name-style') + '?ver=123123'}" />`}
                        ${css && `<link rel="stylesheet" media = "print" type="text/css" href="${cssURL}" />`}
                    </head>
                    <body>
                        <div class="wptm_table tablesorter-bootstrap " data-id="` + $table.data('id') + `" data-hightlight="0" style="margin: 0px auto;">
                            <div class="wptmresponsive dataTables-wptmtable wptmtable" id="wptmtable` + $table.data('id') + `" data-tableid="wptmTbl` + $table.data('id') + `">
                                <style type = "text/css">
                                      @page{margin: 1cm;}
                                      @media screen {
                                        /**{-webkit-box-sizing: border-box;box-sizing: border-box;}*/
                                        /*td, th, tr {border-color: #1b9eff !important;}*/
                                        /*table{font-family: Arial;border-spacing: 0pt !important;background: #ff0004;border: none!important;border-collapse: collapse !important;}*/
                                      }
                                      @media print {
                                        /**{-webkit-box-sizing: border-box;box-sizing: border-box;}*/
                                        /*td, th, tr {border-color: #1b9eff !important;}*/
                                        /*table{font-family: Arial;border-spacing: 0pt !important;background: #f63a22;border: none!important;border-collapse: collapse !important;}*/
                                      }
                                </style>
                               ${html && `${html}`}
                            </div>
                        </div>
                    </body>
                </html>`

                            return getBlobURL(source, 'text/html')
                        } else {
                            return getBlobURL(`<html>
                    <head>
                    </head>
                    <body>
                               ${html && `${html}`}
                    </body>
                </html>`, 'text/html')
                        }
                    }
                    let $thead = $('#wptmtable' + id).find('.dataTables_scrollHead thead').clone()
                    if ($table.hasClass('hideScrollHeader')) {
                        $('#wptmtable' + id).find('.dataTables_scrollBody tbody tr.moveFromHeader').each((i, v) => {
                            $thead.append($(v).clone())
                        })
                    }

                    const url = await getGeneratedPageURL({
                        html: '<table style="" class="wptmPrintTable' + $table.data('id') + '" id="wptmTbl' + $table.data('id') + '" cellspacing="0" cellpadding="0">' + $thead.prop("outerHTML") + '</table>',
                        css: 'table{border-spacing: 0pt !important;border-collapse: collapse;text-align: left;} table input.wptm-filter-input, .sorting_asc:after, .sorting_desc:after, .sorting:after {display: none; content: none !important} .dataTables_length, .dataTables_info, .dataTables_paginate {display: none;}',
                        // css: 'table{border-spacing: 0pt !important;border-collapse: collapse;text-align: left;} *{-webkit-box-sizing: border-box;box-sizing: border-box;} table input.wptm-filter-input, .sorting_asc:after, .sorting_desc:after, .sorting:after {display: none; content: none !important} .dataTables_length, .dataTables_info, .dataTables_paginate {display: none;}',
                        // css: '.dataTables_wrapper table.dataTable {border-spacing: 0;background: red !important;border: none !important;border-collapse: separate !important;} table input.wptm-filter-input, .sorting_asc:after, .sorting_desc:after, .sorting:after {display: none; content: none !important} .dataTables_length, .dataTables_info, .dataTables_paginate {display: none;}',
                        js: ''
                    })

                    const iframe = document.createElement("iframe");
                    document.head.appendChild(iframe);
                    // document.getElementsByClassName('tablesorter-bootstrap')[0].appendChild(iframe);
                    iframe.name = 'wptm-print-table'
                    iframe.src = url
                    iframe.width = '100%'
                    iframe.loading = 'lazy'
                    setTimeout(() => {
                        iframe.contentWindow.focus();
                    }, 500)
                }

                if (tableDom.closest(".wptm_table").data("print-table")) {
                    //get search value, sort value from parent table
                    if (typeof window.wptmprint === 'undefined') {
                        window.wptmprint = {}
                    }
                    let id = tableDom.data('id')
                    window.wptmprint[id] = {}
                    if (tableDom.data("searching")) {
                        window.wptmprint[id].searchValue = {}
                        wptmtable.find("thead th input").keyup(function () {
                            window.wptmprint[id].searchValue[wptmtable.find("thead th input").index(this)] = this.value
                        });
                    }
                }

                $(".wptmtable:not(.wptm_cellsRange)").find('.download_wptm')
                    .unbind("click")
                    .click(function () {
                        var id_table = $(this).parents(".wptm_table").data("id");
                        var url =
                            window.wptm_ajaxurl +
                            "task=sitecontrol.export&id=" +
                            id_table +
                            "&format_excel=xlsx&onlydata=0";
                        $.fileDownload(url, {
                            failCallback: function (html, url) {
                            },
                        });
                    });
            }

            if (typeof id_table !== 'undefined') {
                wptm_render_table(1, $parent_table.find("#wptmtable" + id_table + ".wptmtable"));
            } else {
                $(".wptmtable:not(.wptm_cellsRange)").each(function (index, obj) {
                    setTimeout(function () {
                        if ($(".wptmtable").length > 0) {
                            wptm_render_table(index, obj);
                        }
                    }, 500);
                });

                console.log($('.elementor-widget-button').find('a.elementor-button-link'))
                $('.elementor-widget-button').find('a.elementor-button-link').unbind('click').on('click', function () {
                    setTimeout(function () {
                        $('.elementor-widget-wptm_table .wptmtable:not(.wptm_cellsRange)').each(function (index, obj) {
                            wptm_render_table(index, obj);
                        });
                    }, 500);
                })
            }

            /*
            * $dom: .dataTables_wrapper
            * tableOptions: option of datatable
            * */
            async function wptmFormAction($wptmtable, $dom, tableOptions, table) {
                let $searchDiv = $($dom).find('.searchDiv')
                let id = $dom.data('id')
                let idRandom = $dom.data('random')
                tableOptions.searchRange = [];
                tableOptions.searchColumnsForm = []
                tableOptions.columnsInFormData = []
                tableOptions.columnsSearchType = []

                let searchGroup = {};
                let wptmSubmitButton = false;
                if (typeof wptmFormSearch !== 'undefined') {
                    let wptmFormSearchTable = wptmFormSearch['table' + idRandom];
                    console.log(wptmFormSearchTable)

                    /*submit form button*/
                    wptmSubmitButton = typeof wptmFormSearchTable !== 'undefined'
                        && typeof wptmFormSearchTable.submit_form !== 'undefined'
                        && wptmFormSearchTable.submit_form == 1;

                    /*get list value for selector and checkBox*/
                    if (wptmFormSearchTable) {
                        if (typeof wptmFormSearchTable.select !== 'undefined' || typeof wptmFormSearchTable.checkBox !== 'undefined') {
                            wptmFormSearchTable.columnsInForm = await getDefaultValueForCols(id, wptmFormSearchTable.columnsInForm, tableOptions, wptmFormSearchTable.select || [], wptmFormSearchTable.checkBox || [])
                            wptmFormSearchTable.columnsInForm = await createDefaultForForm(id, wptmFormSearchTable.columnsInForm, $($dom), tableOptions)
                        }
                    }
                }

                let width = $wptmtable.find('.dataTables_wrapper').width()
                if (width > 600) {
                    if (width >= 1200) {
                        $($dom).addClass('big_size')
                    } else {
                        if (width >= 800) {
                            $($dom).addClass('medium_size')
                        } else {
                            $($dom).addClass('small_size')
                        }
                    }
                    $($dom).width(width)

                    let margin = "0 0 0 auto";
                    if (tableOptions.align === 'center') {
                        margin = "0 auto";
                    } else if (tableOptions.align === 'left') {
                        margin = "0 auto 0 0";
                    }
                    $($dom).css("margin", margin);
                }

                $($dom).find('.wptm_submit_form').unbind('click').on('click', function (e) {
                    table.draw();
                })
                $($dom).find('.wptm_clear_form').unbind('click').on('click', function (e) {
                    tableOptions.searchRange = []
                    tableOptions.searchColumnsForm = []
                    $searchDiv.each((i, v) => {
                        let $that = $(v).find('.wptm_search_column')
                        let $thatSlider = $(v).find('.wptm_slider')

                        if ($that.length > 0 && !$that.hasClass('wptm_button'))
                            $that.val('').change()

                        if ($thatSlider.length > 0) {
                            let sliderMax = $thatSlider[2]

                            $($thatSlider[0].querySelector(".rangeValues")).val(sliderMax.min + ' - ' + sliderMax.max).change()
                        }

                        if ($that.hasClass('wptm_button'))
                            $($that[0].parentElement).find('input.wptm_filter_checkbox:checkbox:checked').prop("checked", false).data('value-checked', false)
                    })
                    table.columns().search('').draw()
                })

                $searchDiv.each((i, v) => {
                    let $that = $(v).find('.wptm_search_column')
                    let $thatSlider = $(v).find('.wptm_slider')
                    console.log(tableOptions.juHideColumnClass)
                    let columnId = tableOptions.juHideColumnClass ? tableOptions.juHideColumnClass.indexOf($(v).data('column')) : $(v).data('column')
                    let columnType = $(v).data('type')
                    let searchType = $(v).data('searchtype')
                    let selectDateTimeAction

                    tableOptions.columnsSearchType[columnId] = searchType

                    if (columnType === 'date' && (searchType === 'input' || searchType === 'range')) {
                        let daterangepickerData = []
                        $that.daterangepicker({
                            singleDatePicker: !$that.hasClass('wptm_range') || false,
                            // showDropdowns: true,
                            autoUpdateInput: false,
                            minYear: 1001,
                            maxYear: parseInt(moment().format('YYYY'),20),
                            locale: {
                                format: 'YYYY-MM-DD',
                                cancelLabel: 'Clear'
                            }
                        }, function(start, end, label) {
                            if (start._d) {
                                // val1.split('-')
                                daterangepickerData[0] = start.format('YYYY-MM-DD')
                            }
                            if (end._d) {
                                daterangepickerData[1] = end.format('YYYY-MM-DD')
                            }
                        });
                        $that.on('cancel.daterangepicker', function(ev, picker) {
                            $(this).val('').change();
                        });
                        $that.on('apply.daterangepicker', function(ev, picker) {
                            if (picker) {
                                let startDate = picker.startDate.format('YYYY-MM-DD')
                                let endDate = picker.endDate.format('YYYY-MM-DD')

                                if (!$that.hasClass('wptm_range')) {
                                    $(this).val(startDate).change()
                                } else {
                                    $(this).val(startDate + ' - ' + endDate).data('startDate', startDate).data('endDate', endDate).change()
                                }
                            }
                        });
                        $that.on('change', function () {
                            let val = $(this).val()
                            if (val !== '') {
                                if (!$that.hasClass('wptm_range')) {
                                    try {
                                        let time = moment(val + ' 00:00:00', 'YYYY-MM-DD HH:mm:ss')
                                        // tableOptions.searchColumnsForm[columnId] = time.format('YYYY/MM/DD HH:mm:ss')
                                        if (val !== time.format('YYYY-MM-DD')) {
                                            // $(this).val('').change();
                                            alert('Please enter in YYYY-MM-DD format');
                                            return false;
                                        }
                                        if (time.format('YYYY/MM/DD HH:mm:ss') === 'Invalid date') {
                                            // $(this).val('').change();
                                            alert('Please enter in YYYY-MM-DD format');
                                            return false;
                                        } else {
                                            table.column(columnId).search(time.format('YYYY/MM/DD HH:mm:ss'), !0, !1)
                                        }
                                    } catch (e) {
                                        // $(this).val('').change();
                                        alert('Please enter in YYYY-MM-DD format');
                                        return false;
                                    }
                                } else {
                                    try {
                                        // let val1 = val.replaceAll(' ', '')
                                        // let vals = val1.split('-')
                                        let time1, time2
                                        let vals = daterangepickerData

                                        if (vals[0] !== '') {
                                            time1 = moment(vals[0] + ' 00:00:00', 'YYYY-MM-DD HH:mm:ss')
                                            if (time1.format('X') === 'Invalid date') {
                                                // $(this).val('').change();
                                                alert('Please enter in YYYY-MM-DD format');
                                                return false;
                                            }
                                            vals[0] = time1.format('X')
                                        } else {
                                            vals[0] = null
                                        }
                                        if (vals[1] !== '') {
                                            time2 = moment(vals[1] + ' 23:59:59', 'YYYY-MM-DD HH:mm:ss')
                                            if (time2.format('X') === 'Invalid date') {
                                                // $(this).val('').change();
                                                alert('Please enter in YYYY-MM-DD format');
                                                return false;
                                            }
                                            vals[1] = time2.format('X')
                                        } else {
                                            vals[1] = [null, null]
                                        }

                                        if (val !== (time1.format('YYYY-MM-DD') + ' - ' +  time2.format('YYYY-MM-DD'))
                                            && val !== (time1.format('YYYY-MM-DD') + '-' +  time2.format('YYYY-MM-DD'))
                                            && val !== (time1.format('YYYY-MM-DD') + '- ' +  time2.format('YYYY-MM-DD'))
                                            && val !== (time1.format('YYYY-MM-DD') + ' -' +  time2.format('YYYY-MM-DD'))
                                        ) {
                                            // $(this).val('').change();
                                            alert('Please enter in YYYY-MM-DD format');
                                            return false;
                                        }

                                        tableOptions.searchRange[columnId] = [...vals, 'date']
                                    } catch (e) {
                                        // $(this).val('').change();
                                        alert('Please enter in YYYY-MM-DD format');
                                        return false;
                                    }
                                }
                            } else {
                                if (!$that.hasClass('wptm_range')) {
                                    // tableOptions.searchColumnsForm[columnId] = ''
                                    table.column(columnId).search('')
                                } else {
                                    tableOptions.searchRange[columnId] = null
                                }
                            }

                            if (!wptmSubmitButton) {
                                table.draw();
                            }
                        })
                    } else {
                        if ($thatSlider.length > 0) {
                            let wptmSlideSearch = {}
                            if (typeof wptmFormSearchRange !== "undefined") {
                                let wptmSlideSearch1 = wptmFormSearchRange['table' + id] || {}

                                $.each(wptmSlideSearch1, (i, v) => {
                                    wptmSlideSearch[v.indexFrontEnd] = v
                                })
                            }

                            wptmTwoPointSlide.call($thatSlider, id, columnId, wptmSlideSearch, false)
                            let min, max
                            // let valueText = $thatSlider.find(".wptm_hiden").val()
                            // if (valueText !== '' && valueText !== ' - ' && valueText !== '-') {
                            //     let value = [...valueText.match(/([-|.|0-9|,]+)/g)]
                            //     [min, max] = [value[0], value[2]]
                            // }

                            // tableOptions.searchRange[columnId] = [min, max, 'int']
                            $thatSlider.find(".rangeValues").on('change', function (e) {
                                let value = null, val
                                if ($(this).val() !== '' && $(this).val() !== '-' && $(this).val() !== ' - ') {
                                    val = [...$(this).val().match(/([-|.|0-9|,]+)/g)]
                                    value = [val[0], val[2]]
                                }

                                clearTimeout(selectDateTimeAction);
                                selectDateTimeAction = setTimeout(() => {
                                    if (value !== null){
                                        [min, max] = value
                                        min = parseFloat(min)
                                        max = parseFloat(max)
                                        if (!isNaN(min) && !isNaN(max) ) {
                                            $thatSlider.find(".wptm_hiden").val(min + ' - ' + max).change()
                                            tableOptions.searchRange[columnId] = [min, max, 'int']

                                            if (!wptmSubmitButton) {
                                                table.draw();
                                            }
                                        }
                                    }
                                }, 500);
                            })
                        }

                        // loai bo dau thap phan trong input
                        switch(searchType) {
                            case 'select':
                                $that.unbind('change').on('change', function (e) {
                                    clearTimeout(selectDateTimeAction);
                                    selectDateTimeAction = setTimeout(() => {
                                        let val = $(this).val()
                                        if (typeof tableOptions.columnsInFormData[columnId] !== 'undefined' && typeof tableOptions.columnsInFormData[columnId][val] !== 'undefined') {
                                            val = tableOptions.columnsInFormData[columnId][val]
                                        }

                                        tableOptions.searchColumnsForm[columnId] = val
                                        if (tableOptions.paging) {
                                            table.column(columnId).search(val, !0, !1)
                                        }
                                        if (!wptmSubmitButton) {
                                            table.draw();
                                        }
                                    }, 300)
                                })
                                break;
                            case 'input':
                                $that.unbind('change keyup').on('change keyup', function (e) {
                                    clearTimeout(selectDateTimeAction);
                                    selectDateTimeAction = setTimeout(() => {
                                        let val = $(this).val()

                                        tableOptions.searchColumnsForm[columnId] = val

                                        if (tableOptions.paging) {
                                            table.column(columnId).search(val, !0, !1)
                                        }

                                        if (!wptmSubmitButton) {
                                            table.draw();
                                        }
                                    }, 300)
                                })
                                break;
                            case 'checkBox'://checkBox use default filter of dataTable
                                $that.unbind('click').on('click', function (e) {
                                    clearTimeout(selectDateTimeAction);
                                    selectDateTimeAction = setTimeout(() => {
                                        let val = $(this).val()

                                        let $modal = $(this).siblings('.wptm_modal')
                                        let $modalNode = $modal[0]

                                        let $span = $modal.find(".wptm_close")[0];
                                        let $buttonClear = $modal.find(".wptm_filter_checkbox_clear")[0];
                                        let $buttonSubmit = $modal.find(".wptm_filter_checkbox_submit")[0];

                                        let checkedInputs = $modal.find('input:checkbox')
                                        $.each(checkedInputs, function() {
                                            if ($(this).data('value-checked')) {
                                                $(this).prop("checked", true)
                                            } else {
                                                $(this).prop("checked", false)
                                            }
                                        });

                                        $modalNode.style.display = "block";

                                        $span.onclick = function() {
                                            $modalNode.style.display = "none";
                                        }
                                        $buttonSubmit.onclick = function() {
                                            $modalNode.style.display = "none";
                                        }
                                        // $buttonClear.onclick = function() {
                                            // $modalNode.style.display = "none";
                                        // }

                                        $modalNode.onclick = function(event) {
                                            if (event.target == $modalNode) {
                                                $modalNode.style.display = "none";
                                            }
                                        }

                                        wptmCheckboxSearch(table, columnId, $modal, wptmSubmitButton, tableOptions)
                                    }, 300)
                                })

                                break;
                        }
                    }
                })
            }

            setTimeout(function () {
                wptm_tooltip();
            }, 100);

            function wptm_tooltip() {
                $(".wptm_tooltip ").each(function () {
                    var that = $(this);
                    $(that).tipso({
                        useTitle: false,
                        tooltipHover: true,
                        background: "#000000",
                        color: "#ffffff",
                        offsetY: 0,
                        width: $(that).find(".wptm_tooltipcontent").data("width"),
                        content: $(that).find(".wptm_tooltipcontent").html(),
                        onShow: function (ele, tipso, obj) {
                            //calculate top tipso_bubble when set width
                            var size = realHeight(obj.tooltip());
                            $(obj.tipso_bubble[0]).css(
                                "top",
                                obj.tipso_bubble[0].offsetTop +
                                (size.height - obj.tipso_bubble.outerHeight())
                            );
                        },
                    });
                });

                function realHeight(obj) {
                    var clone = obj.clone();
                    clone.css("visibility", "hidden");
                    $("body").append(clone);
                    var height = clone.outerHeight();
                    var width = clone.outerWidth();
                    clone.remove();
                    return {
                        width: width,
                        height: height,
                    };
                }
            }
        });

        function isHidden(el) {
            var style = window.getComputedStyle(el);
            return (style.display === 'none')
        }

        function wptmCheckboxSearch(table, columnId, $dom, wptmSubmitButton, tableOptions) {
            $dom.find('input.wptm_filter_checkbox_submit').off('click').on('click', function() {
                let search = ''
                let checkedInputs = $dom.find('input:checkbox')
                $.each(checkedInputs, function() {
                    $(this).data('value-checked', false)
                    if ($(this).is(":checked")) {
                        $(this).data('value-checked', true)
                        let value = $(this).val()
                        if (typeof tableOptions.columnsInFormData[columnId] !== 'undefined' && typeof tableOptions.columnsInFormData[columnId][value] !== 'undefined') {
                            value = tableOptions.columnsInFormData[columnId][value]
                        }
                        search += value + '|';
                    }
                });

                tableOptions.searchColumnsForm[columnId] = search.substring(0, search.length - 1)
                if (tableOptions.paging) {
                    table.column(columnId).search(search.substring(0, search.length - 1), !0, !1)
                }

                // tableOptions.searchColumnsForm[columnId] = search.substring(0, search.length - 1)
                // table.column(columnId).search(search.substring(0, search.length - 1), !0, !1).draw()

                if (!wptmSubmitButton) {
                    table.draw();
                }
            });
            $dom.find('input.wptm_filter_checkbox_clear').off('click').on('click', function() {
                let checkedInputs = $dom.find('input:checkbox')
                $.each(checkedInputs, function() {
                    $(this).prop("checked", false).data('value-checked', false)
                });
                tableOptions.searchColumnsForm[columnId] = ''

                // if (tableOptions.paging) {
                    table.column(columnId).search('', !0, !1)
                // }

                if (!wptmSubmitButton) {
                    table.draw();
                }
            });
        }

        function wptmTwoPointSlide(id, columnId, wptmSlideSearch, reset) {
            let that = this[0]
            let sliderOne = that.querySelector(".wptm_slider_min");
            let sliderTwo = that.querySelector(".wptm_slider_max");

            if (typeof wptmSlideSearch[columnId] !== "undefined" && wptmSlideSearch[columnId].initialize) {
                let min = Number.parseFloat(wptmSlideSearch[columnId].min - 1).toFixed();
                let max = Number.parseFloat(wptmSlideSearch[columnId].max + 1).toFixed();
                sliderOne.min = min
                sliderOne.max = max
                sliderTwo.min = min
                sliderTwo.max = max
                sliderOne.value = min
                sliderTwo.value = max
            }

            if (reset)
                return false;

            let minGap = 0;
            let sliderTrack = that.querySelector(".slider-track");
            let sliderMaxValue = sliderTwo.max - sliderTwo.min;
            let oldValue = [parseFloat(sliderOne.value), parseFloat(sliderTwo.value)]
            let setTimeoutAction

            function slideOne() {
                let value2 = parseFloat(sliderTwo.value)
                let value1 = parseFloat(sliderOne.value)

                if (value2 - value1 <= minGap) {
                    sliderOne.value = value1 = value2 - minGap;
                }
                // that.querySelector(".rangeValues").textContent = sliderOne.value;
                if (oldValue[0] !== value1) {
                    fillColor([value1, value2, true]);
                }
            }
            sliderOne.oninput = slideOne;
            function slideTwo() {
                let value2 = parseFloat(sliderTwo.value)
                let value1 = parseFloat(sliderOne.value)
                if (value2 - value1 <= minGap) {
                    sliderTwo.value = value2 = value1 + minGap;
                }
                // that.querySelector(".rangeValues").textContent = sliderTwo.value;
                if (oldValue[1] !== value2) {
                    fillColor([value1, value2, true]);
                }
            }
            sliderTwo.oninput = slideTwo;
            function fillColor([value1, value2, setNewVal]) {
                clearTimeout(setTimeoutAction);
                setTimeoutAction = setTimeout(() => {
                    let percent1 = ((value1 - sliderTwo.min) / sliderMaxValue) * 100;
                    let percent2 = ((value2 - sliderTwo.min) / sliderMaxValue) * 100;
                    sliderTrack.style.background = `linear-gradient(to right, #e5e5e5 ${percent1}% , #3264fe ${percent1}% , #3264fe ${percent2}%, #e5e5e5 ${percent2}%)`;

                    oldValue = [value1, value2]
                    if (setNewVal) {
                        $(that.querySelector(".rangeValues")).val(value1 + ' - ' + value2).change()
                    }
                }, 50);
            }
            fillColor([...oldValue, true])
            this.find(".wptm_hiden").on('change', function (e) {
                let val = $(this).val()
                if (val !== oldValue[0] + ' - ' + oldValue[1]) {
                    let value, value1 = '', value2 = ''
                    if (val !== '' && val !== '-' && val !== ' - ') {
                        value = [...val.match(/([-|.|0-9|,]+)/g)]
                        value1 = value[0]
                        value2 = value[2]
                    }
                    if (value1 !== oldValue[0]) {
                        sliderOne.value = value1
                    }
                    if (value2 !== oldValue[1]) {
                        sliderTwo.value = value2
                    }
                    fillColor([value1, value2, false])
                }
            })
        }
    }
    window.wptm_render_tables.call();
})(jQuery);