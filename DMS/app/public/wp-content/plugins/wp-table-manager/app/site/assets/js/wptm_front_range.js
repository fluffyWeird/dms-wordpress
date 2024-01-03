jQuery.fn.dataTableExt.oApi.fnDisplayRow = function ( oSettings, nRow )
{
    // Account for the "display" all case - row is already displayed
    if ( oSettings._iDisplayLength == -1 )
    {
        return;
    }

    // Find the node in the table
    var iPos = -1;
    for( var i=0, iLen=oSettings.aiDisplay.length ; i<iLen ; i++ )
    {
        if( oSettings.aoData[ oSettings.aiDisplay[i] ].nTr == nRow )
        {
            iPos = i;
            break;
        }
    }

    // Alter the start point of the paging display
    if( iPos >= 0 )
    {
        oSettings._iDisplayStart = ( Math.floor(i / oSettings._iDisplayLength) ) * oSettings._iDisplayLength;
        if ( this.oApi._fnCalculateEnd ) {
            this.oApi._fnCalculateEnd( oSettings );
        }
    }

    this.oApi._fnDraw( oSettings );
};

(function ($) {
    // UMD
    (function( factory ) {
        "use strict";

        if ( typeof define === 'function' && define.amd ) {
            // AMD
            define( ['jquery'], function ( $ ) {
                return factory( $, window, document );
            } );
        }
        else if ( typeof exports === 'object' ) {
            // CommonJS
            module.exports = function (root, $) {
                if ( ! root ) {
                    root = window;
                }

                if ( ! $ ) {
                    $ = typeof window !== 'undefined' ?
                        require('jquery') :
                        require('jquery')( root );
                }

                return factory( $, root, root.document );
            };
        }
        else {
            // Browser
            factory( jQuery, window, document );
        }
    }
    (function( $, window, document ) {
        $.fn.dataTable.render.moment = function ( from, to, locale ) {
            // Argument shifting
            if ( arguments.length === 1 ) {
                locale = 'en';
                to = from;
                from = 'YYYY-MM-DD';
            }
            else if ( arguments.length === 2 ) {
                locale = 'en';
            }

            return function ( d, type, row ) {
                if (! d) {
                    return type === 'sort' || type === 'type' ? 0 : d;
                }

                var m = window.moment( d, from, locale, true );

                // Order and type get a number value from Moment, everything else
                // sees the rendered value
                return m.format( type === 'sort' || type === 'type' ? 'x' : to );
            };
        };
    }));

    var getScrollbarWidth = function (selector) {
        var scrollWidth;
        if (typeof selector.get(0) === 'undefined') {
            scrollWidth = 0;
        } else {
            scrollWidth = selector.get(0).offsetWidth - selector.get(0).clientWidth + 5;//fix when width .5px
            if (scrollWidth === 0) {
                scrollWidth = 20;
            }
        }
        return scrollWidth;
    };

    var filterDelay = null;
    var filterDelayInterval = 250;

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

    window.wptm_render_cellsRanges = function (id_table) {
        function isHidden(el) {
            var style = window.getComputedStyle(el);
            return (style.display === 'none')
        }
        var $parent_table = this;

        function change_width_table_window_resize_or_visible (table_id) {
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

                change_width_table_window_resize_or_visible (table_id);
            });
        });

        var keyupDelay = function (callback, ms) {
            var timer = 0;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    callback.apply(context, args);
                }, ms || 0);
            };
        };
        $(document).ready(function () {
            function wptm_render_cellsRange(index, obj) {
                var wptmtable = $(obj);
                var table_id = wptmtable.data("tableid");
                var rangeCell = wptmtable.find('table').data("range");

                //check in elementor editor
                if (typeof id_table !== 'undefined' && parseInt(id_table) > 0 && table_id !== 'wptmTbl' + id_table) {
                    return false;
                }

                if (wptmtable.find('.dataTable').length > 0) {
                    return false;
                }

                var tableOptions = {};
                var tableDom = wptmtable.find("#" + table_id);

                if (!tableDom.length) {
                    return;
                }

                if (typeof tableDom.DataTable === 'undefined') {
                    return false;
                }

                var colWidths = tableDom.data('colwidths');
                tableOptions.totalWidth = 0;
                if (typeof colWidths !== "undefined") {
                    for (var i = 0; i < colWidths.length; i++) {
                        tableOptions.totalWidth += colWidths[i];
                    }
                }
                function change_width (tableWrapper, tableDom, totalWidth) {
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
                                document.getElementById(table_id + '_wrapper').style.width = '100%';
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
                    var i = 0;
                    for (var j in tableOptions.juHideColumn) {
                        if (tableOptions.juHideColumn[j] > 0) {//hide
                            i++;
                        } else {
                            tableOptions.juHideColumnClass.push(i);
                            tableOptions.juHideColumns[i] = i - tableOptions.juHideColumnClass.length + 1;
                            i++;
                        }
                    }
                }

                tableOptions.date_format = tableDom.data("format");
                tableLanguage.first_load = true;
                tableLanguage.left_header = -1;

                wptmtable.find('thead tr').each(function (key, value) {
                    $(value).addClass('row_index' + (key - 1)).data('row-index', (key - 1));
                    tableLanguage.left_header++;
                });

                var number_load = 0;

                tableOptions.fnDrawCallback = function( oSettings ) {
                    wptm_tooltip();

                    console.log(oSettings)
                    setTimeout(() => {
                        var this_oApi = this.oApi;

                        $('.dataTables_wrapper .dataTables_scrollBody thead').hide();
                        $('.DTFC_LeftBodyWrapper thead').hide();

                        var x2 = $.extend([], oSettings.aiDisplay);
                        if (!tableDom.data("paging")) {
                            // console.log(number_load, this.api().order(), oSettings.aiDisplay);
                            number_load++;
                            var sort_time = [];
                            var order = this.api().order();
                            if (typeof order[0] === 'object') {
                                order = order[0];
                            }

                            oSettings.aiDisplay.forEach(function (value, key) {
                                var i, data, $row = wptmtable.find("tbody .rowShortCode" + (value + tableLanguage.left_header)), rowSpan = 0;
                                if (tableLanguage.first_load) {
                                    $row.addClass('row_index' + (value + tableLanguage.left_header)).data('row-index', (value + tableLanguage.left_header));
                                } else {
                                    data = wptmtable.find("tbody .rowShortCode" + (value + tableLanguage.left_header)).data('row-index');
                                    $row.removeClass('row_index' + data).addClass('row_index' + (key + tableLanguage.left_header)).data('row-index', (key + tableLanguage.left_header));
                                }
                                if (oSettings.aiDisplay.length === key + 1) {
                                    tableLanguage.first_load = false;
                                }

                                if ($row.find('td').filter(function() {
                                    if (this.rowSpan > 1) {
                                        rowSpan = (this.rowSpan - 1) >= rowSpan ? (this.rowSpan - 1) : rowSpan;
                                        return true;
                                    } else {
                                        if ($(this).hasClass('dtc' + order[0]) && $(this).data('timestamp') > 0) {
                                            sort_time.push([value + tableLanguage.left_header - 1, $(this).data('timestamp')]);
                                            return true;
                                        }
                                    }
                                    return false;
                                }).length > 0) {
                                    if (sort_time.length < 1) {
                                        for (i = 0; i < rowSpan; i ++) {
                                            x2 = x2.filter(data => data !== (i + value + 1));
                                            var x3 = x2.indexOf(value);
                                            x2.splice((x3 + i + 1), 0, (i + value + 1));
                                        }
                                    }
                                }

                                if (key + 1 === oSettings.aiDisplay.length) {
                                    if (number_load === 1) {
                                        if (sort_time.length > 0) {
                                            if (order[1] === 'asc') {
                                                sort_time.sort(function(a, b){return a[1] - b[1]});
                                            } else {
                                                sort_time.sort(function(a, b){return b[1] - a[1]});
                                            }
                                            var sort_time2 = sort_time.map(x => x[0]);
                                            oSettings.aiDisplay = $.extend([], sort_time2);
                                            this_oApi._fnDraw( oSettings );
                                        } else {
                                            oSettings.aiDisplay = $.extend([], x2);
                                            this_oApi._fnDraw( oSettings );
                                        }
                                    } else {
                                        number_load = 0;
                                    }
                                }
                            });
                        }
                    }, 300);
                    if (tableDom.data("paging")) {
                        change_width(tableWrapper, tableDom, tableOptions.totalWidth);
                    }
                };

                tableOptions.createdRow = function( row, data, dataIndex ) {
                    var keys = Object.keys(data);
                    var $cRow;
                    if (typeof data.DT_RowId !== 'undefined') {
                        $(row).addClass('row' + data.DT_RowId + ' row_index' + ( dataIndex + tableLanguage.left_header)).data('row-index', ( dataIndex + tableLanguage.left_header));
                    } else {
                        $(row).addClass(' row_index' + ( dataIndex + tableLanguage.left_header)).data('row-index', ( dataIndex + tableLanguage.left_header));
                    }

                    // function not exist in shortCode
                    //
                    // if (typeof data.merges !== 'undefined') {
                    //     // console.group();
                    //     $.each(data.merges, function (key, value) {
                    //         if (typeof tableOptions.juHideColumns !== 'undefined'
                    //             && typeof tableOptions.juHideColumns[value[2]] !== 'undefined'
                    //             && tableOptions.juHideColumns[value[2]] > 0) {
                    //             value[2] = parseInt(value[2]) - tableOptions.juHideColumns[value[2]];
                    //         } else {
                    //         }
                    //         // console.log($(row));
                    //         $(row).find('td:nth-child(' + (1 + parseInt(value[2])) + ')').attr('colspan', value[3]).attr('rowspan', value[1]);
                    //         //merger rows
                    //         if (typeof pagination_merge_cells[value[0]] == 'undefined') {
                    //             pagination_merge_cells[value[0]] = [];
                    //         }
                    //         pagination_merge_cells[value[0]][value[2]] = value;
                    //         var rowspanI = 0;
                    //         for (rowspanI = 0; rowspanI < parseInt(value[1]); rowspanI++) {
                    //             if (typeof pagination_merge_cells[parseInt(value[0]) + rowspanI] == 'undefined') {
                    //                 pagination_merge_cells[parseInt(value[0]) + rowspanI] = [];
                    //             }
                    //             pagination_merge_cells[parseInt(value[0]) + rowspanI][value[2]] = value;
                    //         }
                    //     });
                    //     // console.groupEnd();
                    // }

                    var ii = 0;

                    console.log(row, data, dataIndex)
                    keys.forEach(function (key, index) {
                        if (key !== 'merges' && key !== 'DT_RowId' && key !== 'format_date_cell') {
                            $cRow = $(row).find('td:nth-child(' + (parseInt(key) + 1).toString() + ')');
                            // function not exist in shortCode
                            //
                            // if (typeof tableOptions.pagingType !== 'undefined' && typeof tableOptions.juHideColumn !== 'undefined') {
                            //     if (key !== 'DT_RowId') {
                            //         if ($cRow.length) {
                            //             if (typeof data.DT_RowId !== 'undefined') {
                            //                 $cRow.addClass('dtr' + data.DT_RowId).addClass('dtc' + tableOptions.juHideColumnClass[key]);
                            //             } else {
                            //                 $cRow.addClass('dtc' + tableOptions.juHideColumnClass[key]);
                            //             }
                            //         }
                            //     }
                            // } else {
                            //     if (key !== 'DT_RowId') {
                            //         if ($cRow.length) {
                            //             if (typeof data.DT_RowId !== 'undefined') {
                            //                 $cRow.addClass('dtr' + data.DT_RowId).addClass('dtc' + key);
                            //             } else {
                            //                 $cRow.addClass('dtc' + key);
                            //             }
                            //         }
                            //     }
                            // }
                            //
                            // if (key !== 'DT_RowId' && typeof data.DT_RowId !== 'undefined' && typeof pagination_merge_cells[data.DT_RowId] !== 'undefined') {//has merger
                            //     pagination_merge_cells[data.DT_RowId].forEach(function (value, key) {
                            //         var colspanI = 0;
                            //         for (colspanI = 0; colspanI < parseInt(value[3]); colspanI++) {
                            //             if (!(parseInt(value[0]) == data.DT_RowId && colspanI === 0)) {
                            //                 $(row).find('td:nth-child(' + (1 + parseInt(value[2]) + colspanI) + ')').css('display', 'none');
                            //             }
                            //         }
                            //     });
                            // }

                            var value_format = '';
                            var value_timestamp = '';
                            if (typeof $cRow.data('format') !== 'undefined' && $cRow.text() !== '') {
                                if ($cRow.data('format') == '1') {
                                    value_format = moment($cRow.text()).format(tableOptions.date_format);
                                    value_timestamp = moment($cRow.text()).format('X');
                                    $cRow.data('timestamp', value_timestamp).text(value_format).change();
                                } else if ($cRow.data('format') !== '0') {
                                    if (moment($cRow.text(), tableOptions.date_format).format($cRow.data('format')) === 'Invalid date') {
                                        value_format = moment($cRow.text(), $cRow.data('format')).format($cRow.data('format'));
                                        value_timestamp = moment($cRow.text(), $cRow.data('format')).format('X');
                                    } else {
                                        value_format = moment($cRow.text(), tableOptions.date_format).format($cRow.data('format'));
                                        value_timestamp = moment($cRow.text(), tableOptions.date_format).format('X');
                                    }
                                    $cRow.data('timestamp', value_timestamp).text(value_format).change();
                                }
                            }
                            // function not exist in shortCode
                            //
                            // if (typeof data.format_date_cell !== 'undefined' && typeof data.format_date_cell[key] !== 'undefined' && $cRow.text() !== '') {
                            //     if (data.format_date_cell[key] === '1') {
                            //         value_format = moment($cRow.text()).format(tableOptions.date_format);
                            //         value_timestamp = moment($cRow.text()).format('X');
                            //         $cRow.data('timestamp', value_timestamp).text(value_format).change();
                            //     } else if (data.format_date_cell[key] !== '0') {
                            //         if (moment($cRow.text(), tableOptions.date_format).format(data.format_date_cell[key]) === 'Invalid date') {
                            //             value_format = moment($cRow.text(), data.format_date_cell[key]).format(data.format_date_cell[key]);
                            //             value_timestamp = moment($cRow.text(), data.format_date_cell[key]).format('X');
                            //         } else {
                            //             value_format = moment($cRow.text(), tableOptions.date_format).format(data.format_date_cell[key]);
                            //             value_timestamp = moment($cRow.text(), tableOptions.date_format).format('X');
                            //         }
                            //         $cRow.data('timestamp', value_timestamp).text(value_format).change();
                            //     }
                            // }
                        }
                        ii++;
                    });
                };

                tableOptions.language = tableLanguage;

                var initFilterRow = function(tableDom) {
                    // Apply the search
                    if (tableDom.hasClass("filterable")) {
                        addFilterRowToTable(tableDom);
                    }
                };
                var addFilterRowToTable = function(tbl) {
                    // Add an input to latest th in header
                    tbl.find("thead tr:not(.wptm-header-cells-index):last-child th").each(function(i) {
                        var thContent = $(this).html();
                        var inputHtml = '<br><input onClick="var event = arguments[0] || window.event;event.stopPropagation();" type="text" name="wtmp_col_filter" class="wptm-d-block wptm-filter-input stop-propagation" data-index="' + i + '" value="" />';
                        $(this).html(thContent + inputHtml);
                    });
                };

                if (tableDom.data("ordering")) {
                    tableOptions.ordering = true;
                    var dataOrder = [];
                    dataOrder.push(tableDom.data("ordertarget"));
                    dataOrder.push(tableDom.data("ordervalue"));
                    tableOptions.order = dataOrder;
                }

                initFilterRow($(tableDom));

                //fix dataTable auto pare cell content and return error alert (date data)
                tableOptions.columnDefs = [{
                    "targets": typeof tableOptions.juHideColumnClass !== 'undefined' ? [...tableOptions.juHideColumnClass.keys()] : [...tableDom.data('hidecolumn').keys()],
                    "render": function ( data, type, row, meta ) {
                        if (typeof data === 'undefined' && typeof row[meta.col] !== 'undefined') {
                            return row[meta.col];
                        }
                        return data;
                    }
                }];

                var table;
                tableOptions.fnInitComplete = function( settings, json ) {
                    setTimeout(function() {
                        $('.dataTables_wrapper .dataTables_scrollBody thead').hide();
                        $('.DTFC_LeftBodyWrapper thead').hide();
                    }, 500);
                };

                table = tableDom.DataTable(tableOptions);
                // console.clear();

                $(table.table().container()).on('keyup change', 'input.wptm-filter-input', function (e)
                {
                    e.stopPropagation();
                    columnFilter(table, $(this).data('index'), $(this).val());
                });
                var columnFilter = function (table, columnIndex, val) {
                    if (table.column(columnIndex).search() !== val) {
                        window.clearTimeout(filterDelay);
                        filterDelay = window.setTimeout(function() {
                            table.column(columnIndex).search(val).draw();
                        }, filterDelayInterval);
                    }
                };

                // function not exist in shortCode
                //
                // if (tableDom.data("responsive") === true) {
                //     if ($(".wptm-filter-row").length > 0) {
                //         hideFilterOnResponsive(table, tableDom);
                //     }
                //     //calculateHeaderColspanResponsive(table, tableDom, colWidths);
                //     table.on("responsive-resize", function () {
                //         if ($(".wptm-filter-row").length > 0) {
                //             hideFilterOnResponsive(table, tableDom);
                //         }
                //     });
                // }

                // Change div table wrapper width
                var tableWrapper = tableDom.closest(".dataTables_wrapper");
                var tableAlign = tableDom.data("align");
                var margin = "";
                if (tableAlign === "center") {
                    margin = "0 auto";
                } else if (tableAlign === "left") {
                    margin = "0 auto 0 0";
                } else if (tableAlign === "right") {
                    margin = "0 0 0 auto";
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
                respondToVisibility = function(element, callback) {
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
                respondToVisibility(document.getElementById(table_id), visible => {
                    if(visible) {
                        change_width_table_window_resize_or_visible(table_id);
                    } else {
                        console.log("Not Visible");
                    }
                });

            }
            if (typeof id_table !== 'undefined') {
                wptm_render_cellsRange(1, $parent_table.find("#wptmtable" + id_table + ".wptmtable"));
            } else {
                $(".wptm_cellsRange").each(function (index, obj) {
                    setTimeout(function() {
                        if ($(".wptm_cellsRange").length > 0) {
                            wptm_render_cellsRange(index, obj);
                        }
                    }, 500);
                });

                $('.elementor-widget-button').find('a.elementor-button-link').unbind('click').on('click', function () {
                    setTimeout(function() {
                        $('.elementor-widget-wptm_table .wptmtable').each(function (index, obj) {
                            wptm_render_cellsRange(index, obj);
                        });
                    }, 500);
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
            $(".wptmtable.wptm_cellsRange").siblings('.download_wptm')
                .unbind("click")
                .click(function () {
                    var id_table = $(this).parents(".wptm_table").data("id");
                    var ranger = $(this).parents(".wptm_table").find('table').data('range');
                    if (ranger.length > 0) {
                        ranger = '&ranger=' + ranger;
                    }
                    var url =
                        $(this).data("href") +
                        "task=sitecontrol.export&id=" +
                        id_table + ranger +
                        "&format_excel=xlsx&onlydata=1";
                    $.fileDownload(url, {
                        failCallback: function (html, url) {
                        },
                    });
                });
        });
    }
    window.wptm_render_cellsRanges.call();
})(jQuery);
