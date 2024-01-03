import tableFunction from "./_functions";

/*month text and aug calculator cell*/
var F_name = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];
var M_name = ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sept", "oct", "nov", "dec"];
var l_name = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
var D_name = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
var regex2 = new RegExp('([a-zA-Z]+)([0-9]+)');
var regex3 = new RegExp('([a-zA-Z]+)([0-9]+)', 'g');
/* List of supported formulas */
var regex = new RegExp('^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$');
var regex22 = new RegExp('^=([a-zA-Z]+)\\((.*?)\\)$');
var has_replace = ['AND','OR','XOR','SUM','MULTIPLY','DIVIDE','COUNT','MIN','MAX','AVG'];
var Not_range_function = ['DATE','DAY','DAYS','DAYS360'];

var merge_style_val_cell = function (style_cols, style_rows, Wptm, row, col) {
    var cellStyle = [];

    if (tableFunction.checkObjPropertyNestedNotEmpty(style_cols, col, 1)) {
        cellStyle[2] = jquery.extend([], style_cols[col][1]);
    }
    if (tableFunction.checkObjPropertyNestedNotEmpty(style_rows, row, 1)) {
        cellStyle[2] = jquery.extend([], cellStyle[2], style_rows[row][1]);
    }

    if (tableFunction.checkObjPropertyNested(Wptm.style,'cells', row + "!" + col, 2)) {
        if (tableFunction.checkObjPropertyNestedNotEmpty(Wptm.style.cells[row + "!" + col][2], 'tooltip_content')) {
            cellStyle[2].tooltip_content = Wptm.style.cells[row + "!" + col][2].tooltip_content;
            cellStyle[2].tooltip_width = Wptm.style.cells[row + "!" + col][2].tooltip_width;
        }
        if (Wptm.type === 'html' || row == 0) {
            cellStyle[2] = jquery.extend({}, cellStyle[2],  Wptm.style.cells[row + "!" + col][2]);
        }
    }
    return cellStyle;
}

/**
 * function render cell when handsontable render, call in handsontable.renderer
 *
 * @param instance
 * @param td             cell element
 * @param row            now number
 * @param col            col number
 * @param prop
 * @param value          cell value
 * @param cellProperties
 * @returns {*}
 */
var render = function (instance, td, row, col, prop, value, cellProperties) {
    var onl_value_cell = false;
    if (typeof Array.prototype.slice.call(arguments, 6)[1] !== "undefined") {
        // onl_value_cell = true;
        onl_value_cell = Array.prototype.slice.call(arguments, 6)[1];
    }

    if (typeof Array.prototype.slice.call(arguments, 7)[1] !== "undefined") {
        window.Wptm.numberSheetCalculation++
    } else {
        window.Wptm.numberSheetCalculation = 1
    }

    var Wptm = window.Wptm;
    var function_data = window.table_function_data;
    var css = {};
    var celltype = '', tooltip_content = false;
    var first_cell_merged = false;

    if (typeof (Wptm.style.cells) !== 'undefined') {
        //Cells rendering
        var cellStyle = [];

        if (tableFunction.checkObjPropertyNestedNotEmpty( Wptm.style.cols, col, 1)) {
            cellStyle[2] = jquery.extend([], Wptm.style.cols[col][1]);
        }
        if (tableFunction.checkObjPropertyNestedNotEmpty( Wptm.style.rows, row, 1)) {
            cellStyle[2] = jquery.extend([], cellStyle[2], Wptm.style.rows[row][1]);
        }

        if (tableFunction.checkObjPropertyNested(Wptm.style,'cells', row + "!" + col, 2)) {
            if (Wptm.type === 'html' || row == 0) {
                cellStyle[2] = jquery.extend({}, cellStyle[2],  Wptm.style.cells[row + "!" + col][2]);
            }
        }

        if (!onl_value_cell) {
            if (typeof (cellStyle[2]) !== 'undefined') {
                if (typeof cellStyle[2].AlternateColor !== 'undefined' || typeof function_data.allAlternate.default !== 'undefined') {
                    var numberRow = 0;
                    css["background-color"] = '';
                    if (typeof function_data.allAlternate.default !== 'undefined') {
                        if (function_data.allAlternate.header === '') {
                            numberRow = -1;
                        }
                        switch (row) {
                            case 0:
                                if (function_data.allAlternate.header === '') {
                                    css["background-color"] = function_data.allAlternate.even;
                                } else {
                                    css["background-color"] = function_data.allAlternate.header;
                                }
                                break;
                            case _.size(Wptm.style.rows) - 1:
                                if (function_data.allAlternate.footer === '') {
                                    if ((row - parseInt(0 + numberRow)) % 2) {
                                        css["background-color"] = function_data.allAlternate.even;
                                    } else {
                                        css["background-color"] = function_data.allAlternate.old;
                                    }
                                } else {
                                    css["background-color"] = function_data.allAlternate.footer;
                                }
                                break;
                            default:
                                if ((row - parseInt(0 + numberRow)) % 2) {
                                    css["background-color"] = function_data.allAlternate.even;
                                } else {
                                    css["background-color"] = function_data.allAlternate.old;
                                }
                                break;
                        }
                    } else {
                        var value_cell_alternateColor = function_data.oldAlternate[cellStyle[2].AlternateColor];
                        if (typeof value_cell_alternateColor === 'undefined') {
                            if (typeof function_data.changeAlternate[cellStyle[2].AlternateColor] !== 'undefined' || function_data.changeAlternate.length < 1) {
                                cellStyle[2].AlternateColor = function_data.changeAlternate[cellStyle[2].AlternateColor];
                                value_cell_alternateColor = function_data.oldAlternate[cellStyle[2].AlternateColor];
                            }
                        }

                        if (typeof value_cell_alternateColor !== 'undefined' && value_cell_alternateColor !== false && typeof value_cell_alternateColor.selection !== 'undefined') {
                            if (value_cell_alternateColor.header === '') {
                                numberRow = -1;
                            }
                            switch (row) {
                                case parseInt(value_cell_alternateColor.selection[0]):
                                    if (value_cell_alternateColor.header === '') {
                                        css["background-color"] = value_cell_alternateColor.even;
                                    } else {
                                        css["background-color"] = value_cell_alternateColor.header;
                                    }
                                    break;
                                case parseInt(value_cell_alternateColor.selection[2]):
                                    if (value_cell_alternateColor.footer === '') {
                                        if ((row - parseInt(value_cell_alternateColor.selection[0] + numberRow)) % 2) {
                                            css["background-color"] = value_cell_alternateColor.even;
                                        } else {
                                            css["background-color"] = value_cell_alternateColor.old;
                                        }
                                    } else {
                                        css["background-color"] = value_cell_alternateColor.footer;
                                    }
                                    break;
                                default:
                                    if ((row - parseInt(value_cell_alternateColor.selection[0] + numberRow)) % 2) {
                                        css["background-color"] = value_cell_alternateColor.even;
                                    } else {
                                        css["background-color"] = value_cell_alternateColor.old;
                                    }
                                    break;
                            }
                        } else {
                            delete cellStyle[2].AlternateColor;
                            if (typeof Wptm.style.cells[row + "!" + col][2] !== 'undefined' && typeof Wptm.style.cells[row + "!" + col][2].AlternateColor !== 'undefined') {
                                delete Wptm.style.cells[row + "!" + col][2].AlternateColor;
                            }
                        }
                    }
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_type')) {
                    celltype = cellStyle[2].cell_type;
                } else if (typeof cellStyle[2].cell_type !== 'undefined') {
                    delete cellStyle[2].cell_type;
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_background_color')) {
                    css["background-color"] = cellStyle[2].cell_background_color;
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_border_top')) {
                    css["border-top"] = cellStyle[2].cell_border_top;
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_border_top_start')) {
                    if (0 == row) {
                        css["border-top"] = cellStyle[2].cell_border_top_start;
                    }
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_border_right')) {
                    css["border-right"] = cellStyle[2].cell_border_right;
                }

                //check merge cell and border right
                if (typeof table_function_data.start_merge_cell_col !== 'undefined' && typeof table_function_data.start_merge_cell_col[col] !== 'undefined') {
                    window.jquery.each(table_function_data.start_merge_cell_col[col], function (index, value) {
                        if (typeof value !== 'undefined') {
                            var start_merge_cell_col = Wptm.mergeCellsSetting[value];
                            if (start_merge_cell_col.rowspan + start_merge_cell_col.row > row && row >= start_merge_cell_col.row) {
                                var end_style = merge_style_val_cell(Wptm.style.cols, Wptm.style.rows, Wptm, row, start_merge_cell_col.col + start_merge_cell_col.colspan - 1);
                                first_cell_merged = true;
                                if (tableFunction.checkObjPropertyNestedNotEmpty(end_style, 2, 'cell_border_right')) {
                                    css["border-right"] = end_style[2].cell_border_right;
                                }
                            }
                        }
                    });
                }

                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_border_bottom')) {
                    css["border-bottom"] = cellStyle[2].cell_border_bottom;
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_border_bottom_end')) {
                    if (_.size(Wptm.style.rows) - 1 == row) {
                        css["border-bottom"] = cellStyle[2].cell_border_bottom_end;
                    }
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'cell_border_left')) {
                    css["border-left"] = cellStyle[2].cell_border_left;
                }

                delete css["word-wrap"];
                delete css["overflow"];
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'text_wrapping')) {
                    if (cellStyle[2].text_wrapping === 'normal') {
                        css["word-wrap"] = "normal";
                        css["overflow"] = "visible";
                        css["white-space"] = "nowrap";
                    } else if (cellStyle[2].text_wrapping === 'break-word') {
                        css["word-wrap"] = "break-word";
                        css["overflow"] = "visible";
                    } else if (cellStyle[2].text_wrapping === 'hidden') {
                        css["overflow"] = "hidden";
                        css["white-space"] = "nowrap";
                    }
                }

                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_font_bold') && cellStyle[2].cell_font_bold === true) {
                    css["font-weight"] = "bold";
                } else {
                    delete css["font-weight"];
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_font_italic') && cellStyle[2].cell_font_italic === true) {
                    css["font-style"] = "italic";
                } else {
                    delete css["font-style"];
                }

                let cell_format_strikethrough = false;
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_format_strikethrough') && cellStyle[2].cell_format_strikethrough === true) {
                    css["text-decoration"] = "line-through";
                    cell_format_strikethrough = true;
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_font_underline') && cellStyle[2].cell_font_underline === true) {
                    css["text-decoration"] = "underline";
                } else {
                    if (!cell_format_strikethrough) {
                        delete css["text-decoration"];
                    }
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_text_align')) {
                    css["text-align"] = cellStyle[2].cell_text_align;
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_vertical_align')) {
                    css["vertical-align"] = cellStyle[2].cell_vertical_align;
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_font_family')) {
                    css["font-family"] = cellStyle[2].cell_font_family;
                    //get list custom fonts used
                    if (typeof wptm_listFont !== 'undefined' && wptm_listFont.length > 0
                        && wptm_listFont.includes(cellStyle[2].cell_font_family)
                        && !Wptm.style.table.fonts_used.includes(cellStyle[2].cell_font_family)) {
                        Wptm.style.table.fonts_used.push(cellStyle[2].cell_font_family);
                    }
                    if (typeof wptm_listsLocalFont !== 'undefined' && wptm_listsLocalFont.length > 0
                        && wptm_listsLocalFont.includes(cellStyle[2].cell_font_family)
                        && !Wptm.style.table.fonts_local_used.includes(cellStyle[2].cell_font_family)) {
                        Wptm.style.table.fonts_local_used.push(cellStyle[2].cell_font_family);
                    }
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_font_size')) {
                    css["font-size"] = cellStyle[2].cell_font_size + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_font_color')) {
                    css["color"] = cellStyle[2].cell_font_color;
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_padding_left')) {
                    css["padding-left"] = cellStyle[2].cell_padding_left + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_padding_top')) {
                    css["padding-top"] = cellStyle[2].cell_padding_top + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_padding_right')) {
                    css["padding-right"] = cellStyle[2].cell_padding_right + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_padding_bottom')) {
                    css["padding-bottom"] = cellStyle[2].cell_padding_bottom + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_background_radius_left_top')) {
                    css["border-top-left-radius"] = cellStyle[2].cell_background_radius_left_top + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_background_radius_right_top')) {
                    css["border-top-right-radius"] = cellStyle[2].cell_background_radius_right_top + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_background_radius_right_bottom')) {
                    css["border-bottom-right-radius"] = cellStyle[2].cell_background_radius_right_bottom + "px";
                }
                if (tableFunction.checkObjPropertyNested(cellStyle,2,'cell_background_radius_left_bottom')) {
                    css["border-bottom-left-radius"] = cellStyle[2].cell_background_radius_left_bottom + "px";
                }
                if (tableFunction.checkObjPropertyNestedNotEmpty(cellStyle,2,'tooltip_content')) {
                    tooltip_content = cellStyle[2].tooltip_content !== '' ? true : false;
                }
            }

            //render style for table
            window.jquery(td).css(css);

            if (Object.keys(css).length > 0) {
                function_data.styleToRender += '.dtr' + row + '.dtc' + col + '{';
                window.jquery.each(css, function (index, value) {
                    function_data.styleToRender += index + ':' + value + ';';
                });
                function_data.styleToRender += '}';
            }
        }
    }

    if (!onl_value_cell) {
        switch (celltype) {
            case 'html':
                var escaped = Handsontable.helper.stringify(value);
                //escaped = strip_tags(escaped, '<div><span><img><em><b><a>'); //be sure you only allow certain HTML tags to avoid XSS threats (you should also remove unwanted HTML attributes)
                td.innerHTML = escaped;
                window.jquery(td).addClass('isHtmlCell');
                break;
            default:
                window.jquery(td).removeClass('isHtmlCell');
                Handsontable.renderers.TextRenderer.apply(this, arguments);
                break;
        }

        if (tooltip_content) {
            window.jquery(td).addClass('isTooltipContent');
        } else {
            window.jquery(td).removeClass('isTooltipContent');
        }
    }

    /* Calculs rendering */
    if (typeof Wptm.style.table.date_formats === 'undefined') {
        Wptm.style.table.date_formats = window.default_value.date_formats;
    }
    var accounting_for_cells = {};
    var createRegExpFormatForCell = {};
    var currency_symbol = '';
    var setFormat_for_cells = {};
    var cell_has_format = 0;

    //not has time format
    [accounting_for_cells, createRegExpFormatForCell, currency_symbol, setFormat_for_cells, cell_has_format] = get_format_for_cell(cellStyle[2], Wptm.style.table, function_data.string_currency_symbols, cell_has_format);

    accounting_for_cells.date_formats = typeof cellStyle[2].date_formats !== 'undefined' && cellStyle[2].date_formats !== false ? cellStyle[2].date_formats : Wptm.style.table.date_formats;

    if (typeof cellStyle[2].date_formats !== 'undefined' && cellStyle[2].date_formats !== '' && cellStyle[2].date_formats !== false) {
        if (typeof cellStyle[2].date_formats_momentjs !== 'undefined' && cellStyle[2].date_formats_momentjs !== '' && cellStyle[2].date_formats_momentjs !== false) {
            accounting_for_cells.date_formats_momentjs = cellStyle[2].date_formats_momentjs;
        } else {
            accounting_for_cells.date_formats_momentjs = tableFunction.momentjsFormat(accounting_for_cells.date_formats);
        }
    } else if (typeof accounting_for_cells.date_formats !== 'undefined') {
        if (typeof function_data.date_formats_momentjs !== 'undefined' && function_data.date_formats_momentjs !== '') {
            accounting_for_cells.date_formats_momentjs = function_data.date_formats_momentjs;
        } else {
            accounting_for_cells.date_formats_momentjs = tableFunction.momentjsFormat(Wptm.style.table.date_formats);
        }
    } else {
        accounting_for_cells.date_formats_momentjs = '';
    }

    //accounting_for_cells.date_formats_momentjs from cell/table
    if (typeof cellStyle[2].date_formats !== 'undefined' && cellStyle[2].date_formats !== false) {
        createRegExpFormatForCell = tableFunction.createRegExpFormatForCell(false, accounting_for_cells.date_formats, createRegExpFormatForCell);
    }

    var value_null = value === null || value === '' ? true : false;

    if (typeof (value) === 'string') {
        var value_cell = value;
        var valueNotFormat;
        if (value[0] === '=') {
            value_cell = evaluateFormulas2(td, row, col, value, function_data, accounting_for_cells, createRegExpFormatForCell, setFormat_for_cells, cell_has_format, onl_value_cell);
        } else {
            if (value_null) {
                window.jquery(td).text(value);
            }
            if (currency_symbol !== '' && value.replace(currency_symbol, "") === '') {
                value = value.replaceAll(accounting_for_cells.table.thousand, '');
                value = value.replaceAll(accounting_for_cells.table.decimal, '.');
                valueNotFormat = value;

                if (cell_has_format === 1) {
                    value_cell = accounting.formatNumber(value, accounting_for_cells.number);
                }
                if (cell_has_format > 1) {
                    value_cell = accounting.formatMoney(value, accounting_for_cells.currency);
                }

                if (onl_value_cell) {
                    valueNotFormat = accounting.formatMoney(value, "", accounting_for_cells.number.precision, typeof Wptm.style.table.thousand_symbol === 'undefined'
                        ? default_value.thousand_symbol
                        : Wptm.style.table.thousand_symbol, typeof Wptm.style.table.decimal_symbol === 'undefined'
                        ? default_value.decimal_symbol
                        : Wptm.style.table.decimal_symbol);
                }

                if (!onl_value_cell && !value_null) {
                    window.jquery(td).text(value_cell);
                }
            }

            if (typeof createRegExpFormatForCell.date_format !== 'undefined' && accounting_for_cells.date_formats !== '') {
                value_cell = moment(value, function_data.date_formats_momentjs).format(cellStyle[2].date_formats_momentjs);
                valueNotFormat = value;

                if (!onl_value_cell && !value_null) {
                    window.jquery(td).text(value_cell);
                }
            }
        }

        if (celltype == 'html' && (cell_has_format >= 1 || typeof createRegExpFormatForCell.date_format !== 'undefined')) {
            tableFunction.getFillArray([[row, col, row, col]], Wptm, {
                date_formats: false,
                date_formats_momentjs: false,
                currency_symbol: false,
                symbol_position: false,
                decimal_symbol: false,
                decimal_count: false,
                thousand_symbol: false
            }, 'style');
            var text = window.jquery(td).text();
            saveData.push({action: 'edit_cell_html', row: row, col: col, content: text});
        }
        if (onl_value_cell) {
            return typeof value_cell === 'string' ? [value_cell, valueNotFormat] : value_cell;
        }
    }

    if (!onl_value_cell) {
        //add class for lock column of db table
        if (typeof Wptm.style.table.lock_columns !== 'undefined' && Wptm.style.table.lock_columns[col] != 0) {
            window.jquery(td).addClass('wptm_lock_column');
        }

        if (!wptm_administrator) {
            var lock_cell_id = [], not_lock_cell = false;
            if (typeof table_function_data.protect_columns !== 'undefined'
                && typeof table_function_data.protect_columns[col] !== 'undefined') {
                //protect columns
                lock_cell_id.push(table_function_data.protect_columns[col]);
            }
            if (typeof table_function_data.protect_rows !== 'undefined'
                && typeof table_function_data.protect_rows[row] !== 'undefined') {
                //protect rows
                lock_cell_id.push(table_function_data.protect_rows[row]);
            }
            if (typeof Wptm.style.cells[row + "!" + col][2].lock_cell !== 'undefined') {
                lock_cell_id.push(Wptm.style.cells[row + "!" + col][2].lock_cell);
            }
            if (lock_cell_id.length > 0 && typeof lock_cell_id[0] !== 'undefined'
                && typeof table_function_data.lock_ranger_cells_user !== 'undefined') {
                for (var ij in lock_cell_id) {
                    if (table_function_data.lock_ranger_cells_user[lock_cell_id[ij]]) {//allow edit
                        not_lock_cell = true;
                        break;
                    }
                }
                if (!not_lock_cell) {
                    window.jquery(td).addClass('wptm_lock_cells');
                }
            }
        }

        window.jquery(td).addClass('dtr' + row + ' dtc' + col);
    }

    return td;
};

function get_format_for_cell(cellStyle, cellStyle2, string_currency_symbols, cell_has_format) {
    var accounting_for_cells = {};
    var setFormat_for_cells = {};
    var createRegExpFormatForCell = {};
    var currency_symbol = '';

    if ((typeof cellStyle.decimal_count !== 'undefined' && cellStyle.decimal_count !== false)
        || (typeof cellStyle.decimal_symbol !== 'undefined' && cellStyle.decimal_symbol !== false)
        || (typeof cellStyle.currency_symbol !== 'undefined' && cellStyle.currency_symbol !== false)) {
        setFormat_for_cells.thousand_symbol = typeof cellStyle.thousand_symbol !== 'undefined' && cellStyle.thousand_symbol !== false && cell_has_format !== 1 ? cellStyle.thousand_symbol : cellStyle2.thousand_symbol;
        setFormat_for_cells.decimal_count = typeof cellStyle.decimal_count !== 'undefined' && cellStyle.decimal_count !== false && cell_has_format !== 1 ? cellStyle.decimal_count : cellStyle2.decimal_count;
        setFormat_for_cells.decimal_symbol = typeof cellStyle.decimal_symbol !== 'undefined' && cellStyle.decimal_symbol !== false && cell_has_format !== 1 ? cellStyle.decimal_symbol : cellStyle2.decimal_symbol;

        setFormat_for_cells.currency_symbol = typeof cellStyle.currency_symbol !== 'undefined' && cellStyle.currency_symbol !== false && cell_has_format < 2 ? cellStyle.currency_symbol : cellStyle2.currency_symbol;
        setFormat_for_cells.symbol_position = typeof cellStyle.symbol_position !== 'undefined' && cellStyle.symbol_position !== false && cell_has_format < 2 ? cellStyle.symbol_position : cellStyle2.symbol_position;

        accounting_for_cells = tableFunction.setFormat_accounting_for_cells(setFormat_for_cells);
        if (typeof setFormat_for_cells.currency_symbol === "undefined") {
            setFormat_for_cells.currency_symbol = false;
        }
        createRegExpFormatForCell = tableFunction.createRegExpFormatForCell(setFormat_for_cells.currency_symbol, false, {});

        if (typeof cellStyle.currency_symbol !== 'undefined' && cellStyle.currency_symbol !== '' && cellStyle.symbol_position !== false) {
            cell_has_format += 2;
        }
        if (cell_has_format !== 1 && ((typeof cellStyle.decimal_count !== 'undefined' && cellStyle.decimal_count !== false) || (typeof cellStyle.decimal_symbol !== 'undefined' && cellStyle.decimal_symbol !== false))) {
            cell_has_format += 1;
        }

        currency_symbol = new RegExp('[0-9| |\.|\,|\\-|' + (typeof createRegExpFormatForCell.string_currency_symbols !== 'undefined' ? createRegExpFormatForCell.string_currency_symbols : string_currency_symbols) + ']', "g");
    }
    return [accounting_for_cells, createRegExpFormatForCell, currency_symbol, setFormat_for_cells, cell_has_format];
}

// Evaluate formula in cell then set value to td
function evaluateFormulas2 (td, row, col, value, function_data, accounting_for_cells, createRegExpFormatForCell, setFormat_for_cells, cell_has_format, onl_value_cell) {
    var error = false;

    // var result = regex.exec(value);
    var result = regex22.exec(value);
    var valueReturn = 'NaN';
    var valueNotFormat = 'NaN';
    var cellStyle;
    var changeFormatCell = 0;
    var save_change_format = false;

    if (result !== null) {
        var style_cell = window.Wptm.style.cells[row + '!' + col];
        var calculation;
        calculation = result[1];
        var check_value_data = true;
        var update_format_second = cell_has_format;
        var update_date_formats_momentjs = accounting_for_cells.date_formats_momentjs;

        var check_has_currency_symbol = false;
        var cell_decimal_symbol = typeof accounting_for_cells.decimal !== 'undefined' ? accounting_for_cells.currency.decimal : window.Wptm.style.table.decimal_symbol;
        var date_format = typeof createRegExpFormatForCell.date_format !== 'undefined' ? createRegExpFormatForCell.date_format : function_data.date_format;

        var currency_symbol = new RegExp('[0-9| |\.|\,|\\-|' + (typeof createRegExpFormatForCell.string_currency_symbols !== 'undefined' ? createRegExpFormatForCell.string_currency_symbols : function_data.string_currency_symbols) + ']', "g");
        var cells = result[2].split(";");
        var values = [];
        //(A1:A2)(A1:A2;A3)(A1;A3)(A1;123)(A1;A2;A3:A4)(A1:A2;123)(123 > A1; A2) MIN|MAX|AVG|CONCAT
        var ij = 0, i = 0;

        for (ij = 0; ij < cells.length; ij++) {
            var rCells = [];
            var datas = '';
            var data = '';

            var vals = [cells[ij]];
            // if (cells[ij].indexOf('://') <= 0)//is http://, https://
                vals = cells[ij].split(":")

            if (vals.length === 1 ) {//single cell

                values[ij] = '';

                var checkVal = vals[0].match(regex3);
                if (checkVal !== null) {
                    i = 0;
                    for (i = 0; i < checkVal.length; i++) {
                        //ex: B1 -> B, 1, B1
                        rCells = regex2.exec(checkVal[i]);
                        if (rCells !== null) {
                            cellStyle = window.Wptm.style.cells[(rCells[2] - 1) + '!' + (tableFunction.convertAlpha(rCells[1].toUpperCase()) - 1)];

                            if (changeFormatCell < 1 && cell_has_format < 3
                                && typeof cellStyle !== 'undefined' && ((typeof cellStyle[2].decimal_count !== 'undefined' && cellStyle[2].decimal_count !== false)
                                    || (typeof cellStyle[2].decimal_symbol !== 'undefined' && cellStyle[2].decimal_symbol !== false)
                                    || (typeof cellStyle[2].currency_symbol !== 'undefined' && cellStyle[2].currency_symbol !== false))
                            ) {
                                changeFormatCell = 1;
                                [accounting_for_cells, createRegExpFormatForCell, currency_symbol, setFormat_for_cells, cell_has_format] = get_format_for_cell(cellStyle[2], setFormat_for_cells, function_data.string_currency_symbols, cell_has_format);
                                if (typeof style_cell !== "undefined") {
                                    if (update_format_second !== 1) {
                                        if (style_cell[2].thousand_symbol_second !== (typeof setFormat_for_cells.thousand_symbol !== 'undefined' ? setFormat_for_cells.thousand_symbol : false)) {
                                            style_cell[2].thousand_symbol_second = typeof setFormat_for_cells.thousand_symbol !== 'undefined' ? setFormat_for_cells.thousand_symbol : false;
                                            save_change_format = true;
                                        }
                                        if (style_cell[2].decimal_count_second !== (typeof setFormat_for_cells.decimal_count !== 'undefined' ? setFormat_for_cells.decimal_count : false)) {
                                            style_cell[2].decimal_count_second = typeof setFormat_for_cells.decimal_count !== 'undefined' ? setFormat_for_cells.decimal_count : false;
                                            save_change_format = true;
                                        }
                                        if (style_cell[2].decimal_symbol_second !== (typeof setFormat_for_cells.decimal_symbol !== 'undefined' ? setFormat_for_cells.decimal_symbol : false)) {
                                            style_cell[2].decimal_symbol_second = typeof setFormat_for_cells.decimal_symbol !== 'undefined' ? setFormat_for_cells.decimal_symbol : false;
                                            save_change_format = true;
                                        }
                                    }
                                    if (update_format_second !== 2) {
                                        if (style_cell[2].currency_symbol_second !== (typeof setFormat_for_cells.currency_symbol !== 'undefined' ? setFormat_for_cells.currency_symbol : false)) {
                                            style_cell[2].currency_symbol_second = typeof setFormat_for_cells.currency_symbol !== 'undefined' ? setFormat_for_cells.currency_symbol : false;
                                            save_change_format = true;
                                        }
                                        if (style_cell[2].symbol_position_second !== (typeof setFormat_for_cells.symbol_position !== 'undefined' ? setFormat_for_cells.symbol_position : false)) {
                                            style_cell[2].symbol_position_second = typeof setFormat_for_cells.symbol_position !== 'undefined' ? setFormat_for_cells.symbol_position : false;
                                            save_change_format = true;
                                        }
                                 
                                    }
                                }
                                cell_decimal_symbol = typeof accounting_for_cells.decimal !== 'undefined' ? accounting_for_cells.currency.decimal : setFormat_for_cells.decimal_symbol;
                            }

                            data = window.Wptm.container.handsontable('getDataAtCell', rCells[2] - 1, tableFunction.convertAlpha(rCells[1].toUpperCase()) - 1);
                            if (data !== null && typeof data !== 'undefined') {
                                if (data[0] === '=') {
                                    let data2 = render(false, false, rCells[2] - 1, tableFunction.convertAlpha(rCells[1].toUpperCase()) - 1, false, data, 'cellProperties', true)
                                    data = '' + data2[1]
                                }

                                check_has_currency_symbol = ((typeof data === 'string' && data.indexOf(function_data.string_currency_symbols) > -1) || check_has_currency_symbol) ? true : false;
                                if (has_replace.includes(calculation.toUpperCase())) {//replace in some calculation
                                    if (typeof data === 'string' && data.replace(currency_symbol, "") === '') {
                                        data = accounting.unformat(data, window.Wptm.style.table.decimal_symbol);
                                    } else {
                                        data = '';
                                    }
                                }
                                if (i === 0) {
                                    datas = vals[0].replace(checkVal[i], data);
                                } else {
                                    datas = datas.replace(checkVal[i], data);
                                }
                            } else {
                                datas = data = '';
                            }
                        } else {
                            check_value_data = false;
                        }
                    }
                } else {
                    data = datas = vals[0];
                }
                if (datas !== null) {
                    values[ij] = datas;
                }
            } else {
                if (Not_range_function.includes(calculation.toUpperCase())) {//not set range in some calculation
                    check_value_data = false;
                }
                rCells[0] = regex2.exec(vals[0]);
                rCells[1] = regex2.exec(vals[1]);
                if (rCells[0] !== null && rCells[1] !== null) {
                    cellStyle = window.Wptm.style.cells[(rCells[0][2] - 1) + '!' + (tableFunction.convertAlpha(rCells[0][1]) - 1)];
                    if (changeFormatCell < 1 && cell_has_format < 3 && typeof cellStyle !== 'undefined'
                        && ((typeof cellStyle[2].decimal_count !== 'undefined' && cellStyle[2].decimal_count !== false)
                            || (typeof cellStyle[2].decimal_symbol !== 'undefined' && cellStyle[2].decimal_symbol !== false)
                            || (typeof cellStyle[2].currency_symbol !== 'undefined' && cellStyle[2].currency_symbol !== false))
                    ) {
                        changeFormatCell = 1;
                        [accounting_for_cells, createRegExpFormatForCell, currency_symbol, setFormat_for_cells, cell_has_format] = get_format_for_cell(cellStyle[2], setFormat_for_cells, function_data.string_currency_symbols, cell_has_format);
                        if (typeof style_cell !== "undefined") {
                            if (update_format_second !== 1) {
                                if (style_cell[2].thousand_symbol_second !== (typeof setFormat_for_cells.thousand_symbol !== 'undefined' ? setFormat_for_cells.thousand_symbol : false)) {
                                    style_cell[2].thousand_symbol_second = typeof setFormat_for_cells.thousand_symbol !== 'undefined' ? setFormat_for_cells.thousand_symbol : false;
                                    save_change_format = true;
                                }
                                if (style_cell[2].decimal_count_second !== (typeof setFormat_for_cells.decimal_count !== 'undefined' ? setFormat_for_cells.decimal_count : false)) {
                                    style_cell[2].decimal_count_second = typeof setFormat_for_cells.decimal_count !== 'undefined' ? setFormat_for_cells.decimal_count : false;
                                    save_change_format = true;
                                }
                                if (style_cell[2].decimal_symbol_second !== (typeof setFormat_for_cells.decimal_symbol !== 'undefined' ? setFormat_for_cells.decimal_symbol : false)) {
                                    style_cell[2].decimal_symbol_second = typeof setFormat_for_cells.decimal_symbol !== 'undefined' ? setFormat_for_cells.decimal_symbol : false;
                                    save_change_format = true;
                                }
                            }
                            if (update_format_second !== 2) {
                                if (style_cell[2].currency_symbol_second !== (typeof setFormat_for_cells.currency_symbol !== 'undefined' ? setFormat_for_cells.currency_symbol : false)) {
                                    style_cell[2].currency_symbol_second = typeof setFormat_for_cells.currency_symbol !== 'undefined' ? setFormat_for_cells.currency_symbol : false;
                                    save_change_format = true;
                                }
                                if (style_cell[2].symbol_position_second !== (typeof setFormat_for_cells.symbol_position !== 'undefined' ? setFormat_for_cells.symbol_position : false)) {
                                    style_cell[2].symbol_position_second = typeof setFormat_for_cells.symbol_position !== 'undefined' ? setFormat_for_cells.symbol_position : false;
                                    save_change_format = true;
                                }
                            }
                        }
                        cell_decimal_symbol = typeof accounting_for_cells.decimal !== 'undefined' ? accounting_for_cells.currency.decimal : window.Wptm.style.table.decimal_symbol;
                    }

                    values[ij] = [];
                    rCells = window.Wptm.container.handsontable('getData', rCells[0][2] - 1, tableFunction.convertAlpha(rCells[0][1]) - 1, rCells[1][2] - 1, tableFunction.convertAlpha(rCells[1][1]) - 1);
                    i = 0;
                    for (i = 0; i < rCells.length; i++) {
                        if (has_replace.includes(calculation.toUpperCase())) {//replace in some calculation
                            datas = [];

                            for (var j = 0; j < rCells[i].length; j++) {
                                check_has_currency_symbol = ((rCells[i][j] !== null && String(rCells[i][j]).indexOf(window.Wptm.style.table.currency_symbol) > -1) || check_has_currency_symbol) ? true : false;
                                if (rCells[i][j] === null) {
                                    rCells[i][j] = '';
                                }
                                if (String(rCells[i][j]).replace(currency_symbol, "") === '') {
                                    datas[j] = accounting.unformat(String(rCells[i][j]), window.Wptm.style.table.decimal_symbol);
                                } else {
                                    datas[j] = '';
                                }
                                // datas[j] = accounting.unformat(rCells[i][j], window.Wptm.style.table.decimal_symbol);
                            }
                        } else {
                            datas = rCells[i];
                        }
                        if (datas === null) {
                            values[ij][i] = '';
                        } else {
                            values[ij][i] = datas;
                        }
                    }
                } else {
                    if (tableFunction.convertDate(function_data.date_format, cells[ij].match(/[a-zA-Z0-9|+|-|\\]+/g), true) !== false) {
                        values[ij] = cells[ij];
                    } else {
                        check_value_data = false;
                    }
                }
            }
        }

        //remove format..._second
        if (changeFormatCell < 1 && cell_has_format < 3 && typeof style_cell !== "undefined") {
            if (typeof style_cell[2].thousand_symbol_second !== 'undefined' && style_cell[2].thousand_symbol_second !== false) {
                style_cell[2].thousand_symbol_second = false;
                save_change_format = true;
            }
            if (typeof style_cell[2].decimal_count_second !== 'undefined' && style_cell[2].decimal_count_second !== false) {
                style_cell[2].decimal_count_second = false;
                save_change_format = true;
            }
            if (typeof style_cell[2].decimal_symbol_second !== 'undefined' && style_cell[2].decimal_symbol_second !== false) {
                style_cell[2].decimal_symbol_second = false;
                save_change_format = true;
            }
            if (typeof style_cell[2].currency_symbol_second !== 'undefined' && style_cell[2].currency_symbol_second !== false) {
                style_cell[2].currency_symbol_second = false;
                save_change_format = true;
            }
            if (typeof style_cell[2].symbol_position_second !== 'undefined' && style_cell[2].symbol_position_second !== false) {
                style_cell[2].symbol_position_second = false;
                save_change_format = true;
            }
        }
        if (save_change_format) {
            saveData.push({action: 'style', selection: [[row, col, row, col]], style: style_cell[2]});
        }

        if (check_value_data === true) {
            switch (calculation.toUpperCase()) {
                case 'SUM':
                    valueNotFormat = formulajs.SUM(values);
                    if (check_has_currency_symbol || cell_has_format > 1) {
                        valueReturn = accounting.formatMoney(valueNotFormat, accounting_for_cells.currency);
                    } else {
                        valueReturn = accounting.formatNumber(valueNotFormat, accounting_for_cells.number);
                    }
                    break;
                case 'MULTIPLY':
                    valueNotFormat = 1;
                    values = merge_value_from_cells(values);
                    values.map(function (foo) {
                        if (foo !== false) {
                            var number = Number(foo);
                            if (!isNaN(number)) {
                                valueNotFormat = valueNotFormat * number;
                            } else {
                                // check_value_data = false;//comment before =MULTIPLY(SUM(A1;A2;A3);2)
                            }
                        }
                    });
                    if (!check_value_data) {
                        valueReturn = 'NaN';
                        valueNotFormat = 'NaN';
                    } else {
                        if (check_has_currency_symbol || cell_has_format > 1) {
                            valueReturn = accounting.formatMoney(valueNotFormat, accounting_for_cells.currency);
                        } else {
                            valueReturn = accounting.formatNumber(valueNotFormat, accounting_for_cells.number);
                        }
                    }
                    break;
                case 'DIVIDE':
                    valueNotFormat = '';
                    values = merge_value_from_cells(values);

                    if (values.length !== 2) {
                        valueReturn = 'NaN';
                        valueNotFormat = 'NaN';
                        check_value_data = false;
                        break;
                    }

                    values[1] = Number(values[1]);

                    if (isNaN(values[1]) || values[1] == 0 ||  values[1] == '') {
                        valueReturn = 'NaN';
                        valueNotFormat = 'NaN';
                        check_value_data = false;
                        break;
                    }

                    values[0] = Number(values[0]);

                    if (isNaN(values[0])) {
                        valueReturn = 'NaN';
                        valueNotFormat = 'NaN';
                        check_value_data = false;
                        break;
                    }

                    valueNotFormat = values[0] / values[1];

                    if (!check_value_data) {
                        valueReturn = 'NaN';
                        valueNotFormat = 'NaN';
                    } else {
                        if (check_has_currency_symbol || cell_has_format > 1) {
                            valueReturn = accounting.formatMoney(valueNotFormat, accounting_for_cells.currency);
                        } else {
                            valueReturn = accounting.formatNumber(valueNotFormat, accounting_for_cells.number);
                        }
                    }
                    break;
                case 'AND':
                    valueReturn = true;
                    values = merge_value_from_cells(values);
                    values.map(function (foo) {
                        valueReturn = valueReturn && normalizationData(foo, cell_decimal_symbol);
                    });
                    valueNotFormat = 'true'
                    if (!valueReturn) {
                        valueNotFormat = 'false'
                    }
                    break;
                case 'OR':
                    valueReturn = false;
                    values = merge_value_from_cells(values);
                    values.map(function (foo) {
                        valueReturn = valueReturn || normalizationData(foo, cell_decimal_symbol);
                    });
                    valueNotFormat = 'false'
                    if (valueReturn) {
                        valueNotFormat = 'true'
                    }
                    break;
                case 'XOR':
                    valueReturn = 0;
                    values = merge_value_from_cells(values);
                    values.map(function (foo) {
                        valueReturn += Number(normalizationData(foo, cell_decimal_symbol));
                    });
                    valueReturn = ((valueReturn % 2) === 1) ? 'true' : 'false';
                    valueNotFormat = valueReturn;
                    break;
                case 'COUNT':
                    valueReturn = 0;
                    var currency_symbol = new RegExp('[ |\.|\,|\\-|' + window.Wptm.style.table.currency_symbol + ']', "g");
                    values = merge_value_from_cells(values);
                    values.map(function (foo) {
                        if (foo !== false) {
                            if (!isNaN(Number(foo)) && foo !== '') {
                                valueReturn = valueReturn + 1;
                            }
                        }
                    });
                    valueNotFormat = valueReturn;
                    break;
                case 'DATE':
                    var data_value = '';
                    if (values.length > 1) {
                        data_value = values.join('/');
                    } else {
                        data_value = values[0];
                    }

                    if (typeof data_value !== 'undefined') {
                        valueReturn = moment(data_value).formatWithJDF(accounting_for_cells.date_formats_momentjs);
                    } else {
                        valueReturn = 'NaN';
                        check_value_data = false;
                    }
                    valueNotFormat = valueReturn;
                    // if (values.length === 1) {
                    //     values = values[0].match(/[a-zA-Z0-9|+|-|\\]+/g);
                    // }
                    // //convert values --> (string) date pursuant date_format not have timezone
                    // var date_string = tableFunction.convertDate(function_data.date_format, values, false);
                    // date_string = date_string !== false ? new Date(date_string) : check_value_data = false;
                    //
                    // //convert values --> (string) date pursuant date_format have timezone
                    // var date_string_timezone = tableFunction.convertDate(function_data.date_format, values, true);
                    // date_string_timezone = date_string_timezone !== false ? new Date(date_string_timezone) : check_value_data = false;
                    // if (date_string_timezone && date_string_timezone.getDate() > 0 && check_value_data !== false) {
                    //     var format_resultCalc = window.Wptm.style.table.date_formats.split(/[a-zA-Z|\\]+/g);
                    //     var date = [];
                    //
                    //     date['month'] = date_string.getMonth();
                    //     date['date'] = date_string.getDate();
                    //     date['day'] = date_string.getDay();
                    //     date['year'] = date_string.getUTCFullYear();
                    //
                    //     date['D'] = D_name[date['day']];
                    //     date['l'] = l_name[date['day']];
                    //     date['j'] = date['date'];
                    //     date['d'] = (Number(date['date']) < 10) ? '0' + date['date'] : date['date'];
                    //     date['F'] = F_name[date['month']];
                    //     date['M'] = M_name[date['month']];
                    //     date['n'] = Number(date['month']) + 1;
                    //     date['m'] = (Number(date['month']) < 10) ? '0' + (Number(date['month']) + 1) : Number(date['month']) + 1;
                    //     date['Y'] = date['year'];
                    //     date['y'] = Number(date['year']) % 100;
                    //
                    //     valueReturn = format_resultCalc[0];
                    //     window.jquery.each(function_data.date_format, function (i, v) {
                    //         if (v.indexOf("\\") !== -1 || window.jquery.inArray(v, ["a", "A", "g", "G", "h", "H", "i", "s", "T"]) !== -1) {
                    //             date[v] = values[i];
                    //         }
                    //         valueReturn += date[v] + format_resultCalc[i + 1];
                    //     });
                    // } else {
                    //     valueReturn = 'NaN';
                    //     check_value_data = false;
                    // }
                    break;
                case 'DAY':
                    if (typeof values[0] !== 'undefined') {
                        valueNotFormat = values[0];
                        valueReturn = formulajs.DAY(values[0]);
                        valueReturn = moment(values[0], accounting_for_cells.date_formats_momentjs).date();
                    }
                    break;
                case 'DAYS':
                    if (typeof values[0] !== 'undefined' && typeof values[1] !== 'undefined') {
                        valueReturn = formulajs.DAYS(values[0], values[1]);
                    }
                    valueNotFormat = valueReturn;
                    break;
                case 'DAYS360':
                    valueReturn = 0;
                    if (check_value_data !== false) {
                        var month = [];
                        var year = [];
                        var days = [];
                        var date1 = 0;
                        values.map(function (foo) {
                            if (foo !== false) {
                                valueReturn++;
                                var number = foo.match(/[a-zA-Z0-9|+|-|\\]+/g);
                                var string_day = tableFunction.convertDate(function_data.date_format, number, true);
                                if (string_day !== false) {
                                    date1 = new Date(string_day);
                                    if (!isNaN(date1.getTime())) {
                                        days[valueReturn] = date1.getDate();
                                        month[valueReturn] = date1.getMonth();
                                        year[valueReturn] = date1.getFullYear();
                                    }
                                }
                            }
                        });
                        if (year.length > 1) {
                            if (year[2] < year[1]) {
                                year[2] = year[1] + year[2];
                                year[1] = year[2] - year[1];
                                year[2] = year[2] - year[1];
                                month[2] = month[1] + month[2];
                                month[1] = month[2] - month[1];
                                month[2] = month[2] - month[1];
                                days[2] = days[1] + days[2];
                                days[1] = days[2] - days[1];
                                days[2] = days[2] - days[1];
                                year[4] = -1;
                            } else {
                                year[4] = 1;
                            }
                            year[3] = 0;

                            for (i = year[1]; i < year[2]; i++) {
                                year[3] += 1;
                            }
                            days[1] = (days[1] === 31) ? 30 : days[1];
                            days[2] = (days[2] === 31) ? 30 : days[2];
                            valueReturn = year[4] * (((year[3] - 1) * 360) + ((13 - month[1]) * 30 - days[1]) + ((month[2] - 1) * 30 + days[2]));
                            check_value_data = !isNaN(valueReturn);
                        } else {
                            valueReturn = 'NaN';
                            check_value_data = false;
                        }
                    } else {
                        valueReturn = 'NaN';
                    }
                    valueNotFormat = valueReturn;
                    break;
                case 'MIN':
                    valueNotFormat = formulajs.MIN(values);
                    if (check_has_currency_symbol || cell_has_format > 1) {
                        valueReturn = accounting.formatMoney(valueNotFormat, accounting_for_cells.currency);
                    } else {
                        valueReturn = accounting.formatNumber(valueNotFormat, accounting_for_cells.number);
                    }
                    break;
                case 'MAX':
                    valueNotFormat = formulajs.MAX(values);
                    if (check_has_currency_symbol || cell_has_format > 1) {
                        valueReturn = accounting.formatMoney(valueNotFormat, accounting_for_cells.currency);
                    } else {
                        valueReturn = accounting.formatNumber(valueNotFormat, accounting_for_cells.number);
                    }
                    break;
                case 'AVG':
                    values = merge_value_from_cells(values);
                    valueNotFormat = formulajs.SUM(values) / values.length;
                    if (check_has_currency_symbol || cell_has_format > 1) {
                        valueReturn = accounting.formatMoney(valueNotFormat, accounting_for_cells.currency);
                    } else {
                        valueReturn = accounting.formatNumber(valueNotFormat, accounting_for_cells.number);
                    }
                    break;
                case 'CONCAT':
                    valueReturn = '';

                    if (value.search(';') > 0) {
                        value = value.replaceAll(';', ',')
                    }
                    valueReturn = table_function_data.FormulaParser.parse(value.slice(1), {row: 1, col: 1, sheet: 'Sheet1'})
                    valueNotFormat = valueReturn
                    break;
                default:
                    valueReturn = '';

                    if (value.search(';') > 0) {
                        value = value.replaceAll(';', ',')
                    }
                    try {
                        valueReturn = table_function_data.FormulaParser.parse(value.slice(1), {row: 1, col: 1, sheet: 'Sheet1'})

                        valueNotFormat = valueReturn
                    } catch (e) {
                        valueNotFormat = valueReturn = value
                    }
                    break;
            }
        }
        let matchValue = value.match(/(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\(()/g)

        if (matchValue && matchValue.length > 1) {
            if (value.search(';') > 0) {
                // let newRegExp = value.match(/([0-9]+);([0-9]+)/g)
                // let newRegExp2 = value.match(/([0-9]+);([a-zA-Z]+)([0-9]+)/g)
                // let newRegExp3 = value.match(/([a-zA-Z]+);([a-zA-Z]+)/g)

                value = value.replaceAll(';', ',')
            }
            valueReturn = ''

            value = value.replace('MULTIPLY', 'MMULT')

            // let valueReturn2 = table_function_data.FormulaParser.parse(value.slice(1), {row: 1, col: 1, sheet: 'Sheet' + Wptm.numberSheetCalculation}, true)

            valueReturn = table_function_data.FormulaParser.parse(value.slice(1), {row: 1, col: 1, sheet: 'Sheet' + Wptm.numberSheetCalculation})
            valueNotFormat = valueReturn
        }
    } else {
        if (value.search(';') > 0) {
            value = value.replaceAll(';', ',')
        }
        try {
            valueReturn = table_function_data.FormulaParser.parse(value.slice(1), {row: 1, col: 1, sheet: 'Sheet' + Wptm.numberSheetCalculation})
            valueNotFormat = valueReturn
            check_value_data = true
        } catch (e) {
            valueReturn = ''
        }
    }
    if (check_value_data === true) {
        if (!onl_value_cell) {
            window.jquery(td).text(valueReturn);
        }
    } else {
        function_data.check_value_data = check_value_data;
    }
    return [valueReturn, valueNotFormat];
}

function normalizationData(values, cell_decimal_symbol) {
    if (typeof values === 'string') {
        return values === '' ? values : values === 'true'
    }
    if (!values) {
        return ''
    }

    return values.map(function (foo) {
        if (typeof foo === 'object') {
            return normalizationData(foo, cell_decimal_symbol)
        } else {
            foo = parse_boolean_calculation(foo, cell_decimal_symbol)

            if (!isNaN(parseFloat(foo)) && typeof foo !== 'boolean')
                foo = parseFloat(foo);

            return foo
        }
    });
}

function merge_value_from_cells(values) {
    var value = [];

    values.map(function (foo) {
        if (foo === null) {
            foo = '';
        }
        if (foo !== false) {
            if (typeof foo === 'object') {
                value = value.concat(merge_value_from_cells(foo));
            } else {
                value = value.concat([foo]);
            }
        }
    });

    return value;
}

function parse_boolean_calculation(values, decimal_symbol) {
    var math = values.match(/<=|>=|!=|>|<|=/g);
    var value = true, value1, value2;
    if (math !== null) {
        value1 = values.split(math[0])[0];
        value2 = values.split(math[0])[1];
        value1 = accounting.unformat(value1, decimal_symbol);
        value2 = accounting.unformat(value2, decimal_symbol);
        switch (math[0]) {
            case '<=':
                value = Number(value1) <= Number(value2);
                break;
            case '>=':
                value = Number(value1) >= Number(value2);
                break;
            case '=':
                value = Number(value1) === Number(value2);
                break;
            case '!=':
                value = Number(value1) !== Number(value2);
                break;
            case '<':
                value = Number(value1) < Number(value2);
                break;
            case '>':
                value = Number(value1) > Number(value2);
                break;
        }
    } else {
        value = values === 'true' ? true : (values === 'false' ? false : values);
    }
    return value;
}

export default {
    render,
    evaluateFormulas2,
}
