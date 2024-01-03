import alternating from "./_alternating"
import wptmPreview from "./_wptm";
import {initHandsontable} from "./_initHandsontable";
import selectOption from "./_toolbarOptions"

var F_name = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];
var M_name = ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sept", "oct", "nov", "dec"];
var l_name = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
var D_name = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
var regex = new RegExp('^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$');

/*var x2 = (xx) => {return new Promise ((resolve) => {
    setTimeout(() => {
        dd = dd + 1000
        xx = xx + 10
        console.log(dd + ' x2: ' + xx)
        resolve(xx)
    }, 1000)
})}*/
async function eventToCellsSelected(selection, selectionSize, fCallBackCells, fCallBackRow, fCallBackCol, afterLoopCallBack) {
    if (selection === null) {
        selection = selectOption.getSelector()
    }

    let length = selectionSize !== null ? selectionSize : _.size(selection)
    let returnVal = true;
    // console.log(selectionSize, fCallBackCells, fCallBackRow, fCallBackCol, afterLoopCallBack)
    for (let i = 0; i < length; i++) {
        if (selection[i] !== null) {
            if (typeof fCallBackRow !== 'undefined' && fCallBackRow !== null) {
                let fCallBackRowCall = (ranger) => {
                    return new Promise((resolve) => {
                        for (let i2 = ranger[0]; i2 <= ranger[2]; i2++) {
                            returnVal = fCallBackRow(ranger, i2)
                            if (i2 === ranger[2] && returnVal) {
                                resolve(true)
                            }
                        }
                    })
                }
                try {
                    returnVal = await fCallBackRowCall(selection[i]);
                } catch (err) {
                    console.log(err);
                }
            }

            if (typeof fCallBackCol !== 'undefined' && fCallBackCol !== null) {
                let fCallBackColCall = (ranger) => {
                    return new Promise((resolve) => {
                        for (let i2 = ranger[1]; i2 <= ranger[3]; i2++) {
                            returnVal = fCallBackCol(ranger, i2)
                            if (i2 === ranger[3] && returnVal) {
                                resolve(true)
                            }
                        }
                    })
                }
                try {
                    returnVal = await fCallBackColCall(selection[i]);
                } catch (err) {
                    console.log(err);
                }
            }

            if (typeof fCallBackCells !== 'undefined' && fCallBackCells !== null) {
                try {
                    returnVal = await fCallBackCells(selection[i], i)
                } catch (err) {
                    console.log(err);
                }
            }
        }
    }
    if (typeof afterLoopCallBack !== 'undefined' && afterLoopCallBack !== null) {
        try {
            returnVal = await afterLoopCallBack(selection)
        } catch (e) {
            console.log(e)
        }
    }

    return returnVal
}

// set value for an html switch-button element from Style object
function updateSwitchButtonFromStyleObject(styleObj, prop, that, defaultValue, callback, changeCallBack) {
    if (prop === null) {
        defaultValue = styleObj;
    } else {
        if (checkObjPropertyNested(styleObj, prop)) {
            defaultValue = styleObj[prop];
        }
    }

    that.val(defaultValue == 1 ? 'yes' : 'no').prop("checked", defaultValue == 1);

    if (typeof callback !== 'undefined' && callback !== null) {
        that.on('click', () => {changeCallBack.call(that)})
    }
    if (typeof callback !== 'undefined' && callback !== null) {
        callback.call(that, defaultValue);
    }
}

// set value for an html input element from cellStyle object
function updateParamFromStyleObject(styleObj, prop, that, defaultValue, callback) {
    if (checkObjPropertyNested(styleObj, prop)) {
        if (styleObj[prop] === false) {
            that.val('');
        } else {
            that.val(styleObj[prop]);
        }
    } else {
        that.val(defaultValue);
    }
    if (typeof callback !== 'undefined' && callback !== null) {
        callback.call(that);
    }
}

// set value for an html input element from cellStyle object
function updateParamFromStyleObjectSelectBox(styleObj, prop, that, defaultValue, callback, multiple_select) {
    if (styleObj !== '') {
        if (checkObjPropertyNested(styleObj, prop)) {
            defaultValue = styleObj[prop];
        }
    }
    that.data('value', defaultValue);

    var text = '';
    if (multiple_select) {
        jquery.each(defaultValue.split('|'), function (i, v) {
            if (text !== '') {
                text += ',' + that.find('li[data-value="' + v + '"]').text();
            } else {
                text += that.find('li[data-value="' + v + '"]').text();
            }
        })
    } else {
        if (that.find('li[data-value="' + defaultValue + '"]').length > 0) {
            text += that.find('li[data-value="' + defaultValue + '"]').text();
        }
    }

    that.prev('.wptm_select_box_before').text(text === '' ? (defaultValue === false ? '' : defaultValue) : text).data('value', defaultValue);

    if (typeof callback !== 'undefined' && callback !== null) {
        callback.call(that);
    }
}

function combineChangedCellIntoRow(extendData) {
    if (changedData.length < 1) {
        changedData = [{row: extendData.row, cell: [{col: extendData.col, content: extendData.content}]}];
    } else {
        var newRow = true;
        for (var i = 0; i < changedData.length; i++) {
            var rowData = changedData[i];
            if (rowData.row === extendData.row) {
                var newCell = true;
                for (var j = 0; j < rowData.cell.length; j++) {
                    var cellData = rowData.cell[j];
                    if (cellData.col === extendData.col) {
                        cellData.content = extendData.content;
                        newCell = false;
                        break;
                    }
                }
                if (newCell) {
                    rowData.cell.push({col: extendData.col, content: extendData.content});
                }
                newRow = false;
                break;
            }
        }
        if (newRow) {
            changedData.push({row: extendData.row, cell: [{col: extendData.col, content: extendData.content}]});
        }
    }
}

// check for existence of nested object key
// example: var test = {level1:{level2:{level3:'level3'}} };
// checkObjPropertyNested(test, 'level1', 'level2', 'level3'); // true
function checkObjPropertyNested(obj /*, level1, level2, ... levelN*/) {
    var args = Array.prototype.slice.call(arguments, 1);
    for (var i = 0; i < args.length; i++) {
        if (!obj || !obj.hasOwnProperty(args[i])) {
            return false;
        }
        obj = obj[args[i]];
    }
    return true;
}

// check for existence of nested object key and check value not empty
// example: var test = {level1:{level2:{level3:'level3'}} };
// checkObjPropertyNestedNotEmpty(test, 'level1', 'level2', 'level3'); // true
function checkObjPropertyNestedNotEmpty(obj /*, level1, level2, ... levelN*/) {
    var args = Array.prototype.slice.call(arguments, 1);

    for (var i = 0; i < args.length; i++) {
        if (!obj || !obj.hasOwnProperty(args[i]) || !obj[args[i]]) {
            return false;
        }
        obj = obj[args[i]];
    }
    return true;
}

/**
 * calculater height for table when change height row
 *
 * @param topElement
 * @returns {number}
 */
function calculateTableHeight(topElement) {
    var top = topElement.outerHeight() + 33, paddingBottom;
    if (canInsert == 1) {
        paddingBottom = 30; //px
    } else {
        paddingBottom = 10; //px
    }

    var windownHeight = jquery(window).height();
    var height = windownHeight - top - paddingBottom;
    return height;
}

//convert aphabel to number when get col number
function convertStringToNumber(val) {
    var base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', i, j, result = 0;

    for (i = 0, j = val.length - 1; i < val.length; i += 1, j -= 1) {
        result += Math.pow(base.length, j) * (base.indexOf(val[i]) + 1);
    }

    return result;
}

/*print getSelect cell to element*/
function getSelectedVal(dataSelect, selectRange) {
    var valueRange = '';

    if (dataSelect[1] > 25) {
        valueRange += String.fromCharCode(97) + String.fromCharCode(97 + dataSelect[1] - 26);
    } else {
        if (dataSelect[1] !== false) {
            valueRange += String.fromCharCode(97 + dataSelect[1]);
        }
    }
    if (dataSelect[0] !== false && dataSelect[0] !== null) {
        valueRange += dataSelect[0] + 1;
    }

    valueRange += ':';


    if (dataSelect[3] > 25) {
        valueRange += String.fromCharCode(97) + String.fromCharCode(97 + dataSelect[3] - 26);
    } else {
        if (dataSelect[3] !== false) {
            valueRange += String.fromCharCode(97 + dataSelect[3]);
        }
    }
    if (dataSelect[2] !== false) {
        valueRange += dataSelect[2] + 1;
    }
    if (typeof selectRange !== 'undefined' && dataSelect[0] !== null) {
        selectRange.val(valueRange);
    } else {
        return valueRange;
    }
}

function warning_edit_db_table(field, value) {
    var edit = wptmText.warning_edit_db_table.replace('FIELD_JOOMUNITED', field).replace('FIELD_JOOMUNITED_VALUE', value);
    wptmBootbox('', edit, true, true, () => {
        saveData = saveData.concat(Wptm.saveDataDbTable);
        wptm_element.mainTabContent.addClass('loading_ajax');
        saveChanges(true);
    }, () => {
        Wptm.saveDataDbTable = [];
    });
}

/*time out auto save*/
var autosaveNotification;
var getSaveAjax;

function cleanHandsontable() {
    wptm_element.primary_toolbars.find('#undo_cell').removeClass('active').addClass('no_active');
    wptm_element.primary_toolbars.find('#redo_cell').removeClass('active').addClass('no_active');
    setTimeout(function () {
        jquery(Wptm.container).handsontable('getInstance').undoRedo.clear();
    }, 500);
}

/*saving table*/
function saveChanges(autosave, ajaxCallback) {
    var $ = window.jquery;
    Wptm.saveDataDbTable = [];
    if (!(window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author))) {
        return;
    }
    //check save calculate date
    if (window.table_function_data.check_value_data === false) {
        setTimeout(function () {
            $('#saveErrorTable').animate({'top': '+=75px', 'opacity': '1'}, 500).delay(2000).animate({
                'top': '-=75px',
                'opacity': '0'
            }, 1000);
        }, 1000);
        window.table_function_data.check_value_data = true;
        return;
    }

    if ((typeof autosave == 'undefined' && !enable_autosave) || autosave === false) {
        checkTimeOut = false;
        return;
    }
    if (!checkTimeOut) {
        clearTimeout(getSaveAjax);
    }

    checkTimeOut = false;

    var delay_time = enable_autosave ? 5000 : 0;

    if (autosave === true) {
        delay_time = 700;//fix async when copy, paste, create row/col
    }

    getSaveAjax = setTimeout(function () {
        /*check have the change Alternate*/
        if (_.size(window.table_function_data.checkChangeAlternate) > 0) {
            window.Wptm.style.cells = $.extend({}, alternating.reAlternateColor());
        }

        //fix style.cols length
        var cols_length = droptablesCheckSize(window.Wptm.style.cols)
        if (cols_length > window.Wptm.max_Col) {
            var newCols = {}
            for (var ii = 0; ii < window.Wptm.max_Col; ii++) {
                newCols[ii] = window.Wptm.style.cols[ii];

                if (window.Wptm.max_Col === ii + 1) {
                    window.Wptm.style.cols = $.extend({}, newCols);
                }
            }
        }
        var save_data;
        if (window.Wptm.mergeCellsSetting.length > 0) {//update header option by merger cells
            var i;
            var maxHeader = window.Wptm.headerOption;
            for (i = 0; i < window.Wptm.mergeCellsSetting.length; i++) {
                var mergeSetting = window.Wptm.mergeCellsSetting[i];
                maxHeader = (mergeSetting.row < maxHeader && mergeSetting.row + mergeSetting.rowspan > maxHeader) ? mergeSetting.row + mergeSetting.rowspan : maxHeader;
            }

            if (maxHeader > window.Wptm.headerOption) {
                saveData.push({
                    action: 'set_header_option',
                    value: maxHeader
                });
                window.Wptm.headerOption = maxHeader;

                if (Wptm.style.table.freeze_row > 0) {
                    $(Wptm.container).handsontable('updateSettings', {fixedRowsTop: maxHeader});
                }
            }
        }

        if (typeof table_function_data.colsSetType !== 'undefined') {
            saveData.push({
                action: 'set_columns_types',
                value: table_function_data.colsSetType
            });
            delete table_function_data.colsSetType;
        }

        if (table_function_data.save_table_params.length > 0) {
            save_data = JSON.stringify(table_function_data.save_table_params);
        } else {
            if (typeof table_function_data.create_col_after_paste !== 'undefined' && table_function_data.create_col_after_paste.length > 0) {
                saveData = table_function_data.create_col_after_paste.concat(saveData)
                table_function_data.create_col_after_paste = []
            }
            if (saveData.length < 1) {//create empty data to update other table params
                saveData.push({
                    action: 'empty_data',
                    value: ''
                });
            }
            save_data = JSON.stringify(saveData);
        }
        var Wptm_hyperlink = {};
        if (typeof Wptm.hyperlink !== 'undefined') {
            Wptm_hyperlink = $.extend({}, {}, Wptm.hyperlink);
            $.each(Wptm.hyperlink, function (i, v) {
                Wptm_hyperlink[i] = {};
                Wptm_hyperlink[i].hyperlink = v.hyperlink;
                Wptm_hyperlink[i].text = typeof v.text !== 'undefined' ? v.text : '';
                if (typeof v.text !== 'undefined' && v.text.indexOf('\"') > -1) {
                    Wptm_hyperlink[i].text = v.text.replaceAll('\"', '\\"');
                }
            })
        } else {
            Wptm_hyperlink = {};
        }
        var reverseString = (v) => {
            return v.split('').reverse().join('')
        }

        //avoid Firewall error (column type name)
        var styleTableSaving = $.extend({}, {}, window.Wptm.style.table)
        if (typeof styleTableSaving.col_types !== 'undefined' && styleTableSaving.col_types) {
            $.each(styleTableSaving.col_types, (i, v) => {
                styleTableSaving.col_types[i] = typeof v === 'string' ? reverseString(v) : v
            })
        }

        var jsonVar = {
            option_nonce: $('#option_nonce').val(),
            css: window.Wptm.css,
            mergeSetting: JSON.stringify(window.Wptm.mergeCellsSetting),
            count: JSON.stringify({
                'countRows': $(window.Wptm.container).handsontable('countRows'),
                countCols: $(window.Wptm.container).handsontable('countCols')
            }),
            style: JSON.stringify({
                'table': styleTableSaving,
                'cols': window.Wptm.style.cols,
                'rows': window.Wptm.style.rows
            }),
            reverseValue: JSON.stringify({'table': 'col_types'}),
            save_data: save_data,
            id: window.Wptm.id
        };

        if (window.Wptm.type !== 'html')
            jsonVar.headerOption = 1;
        else
            jsonVar.hyperlink = JSON.stringify(Wptm_hyperlink);
        jsonVar.headerOption = window.Wptm.headerOption;

        if (typeof window.Wptm.syn_hash !== 'undefined' && window.Wptm.syn_hash !== '') {
            jsonVar.syn_hash = window.Wptm.syn_hash;
        }

        window.Wptm.style = cleanStyle(window.Wptm.style, $(window.Wptm.container).handsontable('countRows'), $(window.Wptm.container).handsontable('countCols'));
        var old_slection = window.table_function_data.selection;

        $.ajax({
            url: wptm_ajaxurl + "task=table.save",
            dataType: "json",
            traditional: true,
            type: "POST",
            data: $.param(jsonVar),
            beforeSend: function () {
                wptm_element.settingTable.find('.wptm_save_error').addClass('wptm_hiden');
                wptm_element.settingTable.find('.wptm_saving').html('<span>' + wptmText.SAVING + '</span>');
                wptm_element.settingTable.find('.wptm_saving').removeClass('wptm_hiden');
                window.table_function_data.firstRender = false;
            },
            success: function (datas) {
                if (datas.response === true) {
                    wptm_element.settingTable.find('.wptm_save_error').addClass('wptm_hiden');
                    wptm_element.settingTable.find('.wptm_saving').html('<span>' + wptmText.ALL_CHANGES_SAVED + '</span>');

                    //empty data in saveData
                    if (table_function_data.save_table_params.length > 0) {
                        table_function_data.save_table_params = [];
                    } else {
                        saveData = [];
                    }

                    update_after_save(datas.datas, old_slection);
                    if (typeof ajaxCallback !== 'undefined' && ajaxCallback === 'render') {
                        setTimeout(function () {
                            window.jquery(wptm_element.tableContainer).handsontable('render');
                        }, 500);
                    }

                    setTimeout(function () {
                        wptm_element.settingTable.find('.wptm_saving').addClass('wptm_hiden');
                    }, 1500);
                } else {
                    wptmBootbox('', datas.response, true, false);
                }
                if (typeof ajaxCallback == 'function') {
                    ajaxCallback(window.Wptm.id)
                }
                window.table_function_data.firstRender = true;
                $('.prevent_event_popup').hide();
            },
            error: function (jqxhr, textStatus, error) {
                $('.prevent_event_popup').hide();
                wptmBootbox('', textStatus + " : " + error, true, false);
            }
        });
        checkTimeOut = true;
    }, delay_time);
}

/**
 * Update some value in Wptm after saving ajax
 * @param datas
 * @param old_slection
 */
function update_after_save(datas, old_slection) {
    if (typeof datas.type !== 'undefined') {
        jquery.each(datas.type, function (i, value) {
            switch (i) {
                case "reload_table":
                    updateDataAndParamsTable();
                    break;
                case "set_columns_types":
                    var selected = [];

                    jquery.each(old_slection, function (i, v) {
                        selected[i] = [v[0], v[1], v[2], v[3]];
                        if (typeof Wptm.headerOption !== 'undefined') {
                            if (v[0] < parseInt(Wptm.headerOption)) {
                                selected[i][0] = parseInt(Wptm.headerOption);
                            }
                        }
                    });

                    change_value_cells(selected, value, 'col%key');
                    break;
                case "update_params":
                    var params = jquery.parseJSON(value);
                    if (typeof params.headerOption !== 'undefined') {
                        Wptm.headerOption = parseInt(params.headerOption);
                    } else {
                        Wptm.headerOption = 1;
                    }
                    try {
                        Wptm.mergeCellsSetting = jquery.parseJSON(params.mergeSetting);
                        if (Wptm.mergeCellsSetting == null) {
                            Wptm.mergeCellsSetting = [];
                        }
                    } catch (e) {
                        Wptm.mergeCellsSetting = [];
                    }

                    //not update header_data, it was updated
                    if (typeof params.header_data !== 'undefined') {
                        var old_header_data = Wptm.style.table.header_data;
                        delete params.header_data;
                    }
                    params.hyperlink = params.hyperlink.replaceAll('\\\\\\"', '\\"');
                    Wptm.hyperlink = jquery.parseJSON(params.hyperlink);
                    window.Wptm.style.table = jquery.extend({}, params);
                    window.Wptm.style.table.header_data = old_header_data;
                    break;
            }
        });

        if (typeof table_function_data.fetch_data !== 'undefined') {
            wptmPreview.fetchSpreadsheet(table_function_data.fetch_data);
        }
    }
}

/**
 * convert cell data by column type
 *
 * @param position cell position
 * @param value    New value
 * @returns {string|boolean}
 */
function cell_type_to_column(position, value) {
    if ((Wptm.type !== 'mysql' && typeof Wptm.style.table.col_types[position[1]] !== 'undefined')
        || (typeof Wptm.query_option !== 'undefined' && typeof Wptm.query_option.column_options !== 'undefined' && typeof Wptm.query_option.column_options[position[1]] !== 'undefined')) {
        if (value === null) {
            value = '';
        }
        var type_column;
        if (Wptm.type === 'mysql' && typeof Wptm.query_option.column_options !== 'undefined' && typeof Wptm.query_option.column_options[position[1]] !== 'undefined') {
            type_column = Wptm.query_option.column_options[position[1]].Type;
        } else {
            type_column = Wptm.style.table.col_types[position[1]];
        }
        var data = validate_type_cell(type_column, value);
        return data;
    }
    return true;
}

function deleteRowDbTable(table, field, id_row) {
    var url = wptm_ajaxurl + "task=table.deleteRowDbTable";
    wptm_element.mainTabContent.addClass('loading_ajax');

    jquery.ajax({
        url: url,
        data: {
            id_table: Wptm.id,
            value: id_row,
            field: field,
            table: table
        },
        type: 'POST',
        success: function (datas) {
            updateDataAndParamsTable();
        },
        error: function (jqxhr, textStatus, error) {
            wptmBootbox('', textStatus + " : " + error, true, false);
        }
    })
}

function createRowDbTable(dbtable, list_value) {
    var url = wptm_ajaxurl + "task=table.createRowDbTable";
    wptm_element.mainTabContent.addClass('loading_ajax');
    jquery.ajax({
        url: url,
        data: {
            id: Wptm.id,
            data: list_value,
            dbtable: dbtable
        },
        type: 'POST',
        success: function (datas) {
            updateDataAndParamsTable();
        },
        error: function (jqxhr, textStatus, error) {
            wptmBootbox('', textStatus + " : " + error, true, false);
        }
    })
}

function validate_type_cell(type_column, value) {
    var date_string;
    var length = value.length;

    var result = regex.exec(value);

    switch (type_column.toUpperCase()) {
        case 'INT':
            if (value === '') {
                value = '0';
            }

            if (result === null) {
                if (value.replace(/[ |0-9]+/g, '') !== '') {
                    return false;
                }
                if (parseInt(value) < -2147483648 && parseInt(value) > 2147483647) {
                    return false;
                }
            }
            break;
        case 'SMALLINT':
            if (value === '') {
                value = '0';
            }

            if (result === null) {
                if (value.replace(/[ |0-9]+/g, '') !== '') {
                    return false;
                }
                if (parseInt(value) < -32768 && parseInt(value) > 32767) {
                    return false;
                }
            }
            break;
        case 'TINYINT':
            if (value === '') {
                value = '0';
            }

            if (result === null) {
                if (value.replace(/[ |0-9]+/g, '') !== '') {
                    return false;
                }
                if (parseInt(value) < -128 && parseInt(value) > 127) {
                    return false;
                }
            }
            break;
        case 'MEDIUMINT':
            if (value === '') {
                value = '0';
            }
            if (result === null) {
                if (value.replace(/[ |0-9]+/g, '') !== '') {
                    return false;
                }
                if (parseInt(value) < -8368608 && parseInt(value) > 8368607) {
                    return false;
                }
            }
            break;
        case 'bigint unsigned':
            if (value === '') {
                value = '0';
            }
            if (value.replace(/[ |0-9]+/g, '') !== '') {
                return false;
            }
            break;
        case 'DECIMAL(16,4)':
            if (value === '') {
                value = '0';
            }
            if (value.replace(/[ |0-9|.]+/g, '') !== '') {
                return false;
            }
            break;
        case 'DATE':
            if (value !== '') {
                date_string = convertDate(["Y", "m", "d"], value.match(/[a-zA-Z0-9|+|-|\\]+/g), true);
                if (date_string === false) {
                    return false;
                }
                if (date_string === '00-00-0000') {
                    return '0000-00-00';
                }
                var date_string1 = new Date(date_string);
                if (isNaN(date_string1.getTime())) {
                    return false;
                }
                return date_string1.getFullYear() + '-' + ((date_string1.getMonth() < 10) ? '0' + (date_string1.getMonth() + 1) : date_string1.getMonth() + 1) + '-' + date_string1.getDate();
            } else {
                return '0000-00-00';
            }
            break;
        case 'DATETIME':
            if (value !== '') {
                date_string = convertDate(["Y", "m", "d", "h", "i", "s"], value.match(/[a-zA-Z0-9|+|-|\\]+/g), true);
                if (date_string === false) {
                    return false;
                }
                if (date_string === '00/00/0000 0:00:00 ') {
                    return '0000-00-00 00:00:00';
                }
                date_string = new Date(date_string);
                if (isNaN(date_string.getTime())) {
                    return false;
                }
                return date_string.getFullYear() + '-' + ((date_string.getMonth() < 10) ? '0' + (date_string.getMonth() + 1) : date_string.getMonth() + 1) + '-' + date_string.getDate() + ' ' + date_string.getHours() + ':' + date_string.getMinutes() + ':' + date_string.getSeconds();
            } else {
                return '0000-00-00 00:00:00';
            }
            break;
        case 'TIME':
            if (value !== '') {
                date_string = convertDate(["H", "m", "S"], value.match(/[a-zA-Z0-9|+|-|\\]+/g), true);
                if (date_string === false) {
                    return false;
                }
                if (date_string === '00-00-00 ') {
                    return '00:00:00';
                }
                date_string = new Date(date_string);
                if (isNaN(date_string.getTime())) {
                    return false;
                }
                return date_string.getHours() + ':' + date_string.getMinutes() + ':' + date_string.getSeconds();
            } else {
                return '00:00:00';
            }
            break;
        case 'TIMESTAMP':
            if (value !== '') {
                if (isNaN(value) || parseInt(value) < 0) {
                    return false;
                }
                date_string = (new Date(value)).getTime() > 0;

                if (date_string === false) {
                    return false;
                }

                return value;
            }
            break;

        case 'VARCHAR':
            if (length > 255) {
                return false;
            }
            break;
        case 'TEXT':
            if (length > 65500) {
                return false;
            }
            break;
        case 'LONGTEXT':
            if (length > 4294967294) {
                return false;
            }
            break;
        case 'MEDIUMTEXT':
            if (length > 16777214) {
                return false;
            }
            break;
        case 'CHAR':
            if (length > 255) {
                return false;
            }
            break;
        case 'FLOAT':
            if (value === '') {
                value = '0';
            }
            if (result === null) {
                if (isNaN(parseFloat(value))) {
                    return false;
                }
            }
            break;
        default:
            return value;
            break;
    }
    return value;
}

function updateDataAndParamsTable() {
    var url = wptm_ajaxurl + "view=table&format=json&id=" + Wptm.id;
    var $ = jquery;
    $.ajax({
        url: url,
        type: "POST",
        data: {},
        dataType: "json",
    }).done(function (data) {
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
            if (Wptm.headerOption > 0 && (typeof Wptm.style.table.header_data === 'undefined' || Wptm.style.table.header_data.length < Wptm.headerOption)) {
                Wptm.style.table.header_data = [];
                for (var j = 0; j < Wptm.headerOption; j++) {
                    Wptm.style.table.header_data[j] = Wptm.datas[j];
                }
            }
            window.jquery(window.Wptm.container).data('handsontable').loadData(Wptm.datas);
        }
        wptm_element.mainTabContent.removeClass('loading_ajax');
    })
    return false;
}

/**
 * change cells value when add function in toolbar
 *
 * @param selection
 * @param values
 * @param keyColum
 * @param afterLoopCallBack
 */
function change_value_cells(selection, values, keyColum, afterLoopCallBack) {
    table_function_data.data_argument = [];
    if (selection === null) {
        selection = window.jquery.extend([], [], table_function_data.selection);
    }

    var cellProperties;

    eventToCellsSelected(
        selection,
        table_function_data.selectionSize,
        null,
        (ranger, i) => {
            for (let j = ranger[1]; j <= ranger[3]; j++) {
                cellProperties = check_cell_readOnly(i, j, true)
                if (typeof cellProperties.readOnly == "undefined" || cellProperties.readOnly !== true) {
                    let value
                    if (Array.isArray(values)) {
                        value = !keyColum ? values[i]['col0'] : values[i][keyColum.replace('%key', j)];//default col 0
                        table_function_data.data_argument.push([i, j, value, 'wptm_change_value_after_set_columns_types']);
                    } else {
                        value = values;
                        table_function_data.data_argument.push([i, j, value]);
                    }
                    Wptm.datas[i][j] = value;
                }
                if (j === ranger[3]) {
                    return true
                }
            }
        },
        null,
        (ranger) => {
            const promise1 = new Promise((resolve) => {
                if (table_function_data.changeTheme) {
                    wptmPreview.updatepreview(Wptm.id);
                }

                if (table_function_data.data_argument.length < 5) {//if cells number < 5 then setDataAtCell() is more performance
                    window.jquery(window.Wptm.container).data('handsontable').setDataAtCell(table_function_data.data_argument, 'setDataAtCell');
                } else {
                    window.jquery(window.Wptm.container).data('handsontable').loadData(Wptm.datas);
                }

                resolve(true)
            })
            promise1
            if (typeof afterLoopCallBack !== 'undefined' && afterLoopCallBack !== null) {
                afterLoopCallBack.call()
            }
        }
    )
}

//function in table DB
function loadTableContructor() {
    var $ = window.jquery;
    var id_table = $('li.wptmtable.active').data('id-table');
    var table_type = $('li.wptmtable.active').data('table-type');
    var $mainTable = $("#mainTable");
    $mainTable.find(".tabDataSource").hide();
    $mainTable.find(".groupTable" + id_table).show();
    if (table_type == 'mysql') {
        if ($("#tabDataSource_" + id_table).length == 0) {
            var firstTab = $mainTable.find('li').get(0);
            $(firstTab).after('<li><a data-toggle="tab" id="tabDataSource_' + id_table + '" class="tabDataSource groupTable' + id_table + '" href="#dataSource_' + id_table + '">Data Source</a></li>');
            $('#mainTabContent.tab-content').append('<div class="db_table tab-pane" id="dataSource_' + id_table + '">' +
                '<div class="dataSourceContainer" style="padding-top:10px" ></div></div>');

            $.ajax({
                url: wptm_ajaxurl + "view=dbtable&id_table=" + id_table,
                type: "GET"
            }).done(function (data) {
                $("#dataSource_" + id_table).html(data);
            });
        }
    }
    //do nothing
}

// Build column selection for default sort parameter
function default_sortable(tableData) {
    var $ = window.jquery;
    if (tableData && typeof tableData[0] !== 'undefined') {
        var $jform_default_sortable = $('#content_popup_hide').find('.select_columns');
        var $jform_reponsive_table = $('#content_popup_hide').find('#responsive_table table tbody');
        var $jform_column_type_table = $('#content_popup_hide').find('#column_type_table table tbody');
        $jform_default_sortable.contents('li').remove();
        $jform_reponsive_table.html('');
        $jform_column_type_table.html('');
        var html = '';
        var html2 = '';
        var column_type_html = '';
        var ii = 0;

        var responsive_priority = '';
        for (var number = 0; number < (window.Wptm.max_Col > 12 ? window.Wptm.max_Col : 12); number++) {
            responsive_priority += '<li data-value="' + number + '">' + number + '</li>\n';
        }

        $.each(tableData[0], function (i, e) {
            var table_headers = jQuery(Wptm.container).handsontable('getColHeader');
            var header = table_headers[i];
            html += '<li data-value="' + ii + '">' + header + '</li>';

            html2 += '<tr data-col="' + ii + '">';
            html2 += '<td><label>' + header + '</label></td>';
            html2 += '<td><span class="responsive_priority popup_select mb-0 wptm_select_box_before"></span><ul class="wptm_select_box">\n'
                + responsive_priority +
                '</ul></td>';
            html2 += '</tr>';

            column_type_html += '<tr data-col="' + ii + '">';
            column_type_html += '<td><label>' + header + '</label></td>';
            column_type_html += '<td><span class="column_type popup_select mb-0 wptm_select_box_before"></span><ul class="wptm_select_box">\n' +
                '                        <li data-value="varchar">' + wptmContext.column_type_varchar + '</li>\n' +
                '                        <li data-value="int">' + wptmContext.column_type_int + '</li>\n' +
                '                        <li data-value="float">' + wptmContext.column_type_float + '</li>\n' +
                '                        <li data-value="date">' + wptmContext.column_type_date + '</li>\n' +
                '                        <li data-value="datetime">' + wptmContext.column_type_datetime + '</li>\n' +
                '                        <li data-value="text">' + wptmContext.column_type_text + '</li>\n' +
                '                    </ul></td>';
            column_type_html += '</tr>';
            ii++;
        });
        $(html).appendTo($jform_default_sortable);
        $jform_default_sortable.trigger("liszt:updated");
        $(html2).appendTo($jform_reponsive_table);
        $jform_reponsive_table.trigger("liszt:updated");
        $(column_type_html).appendTo($jform_column_type_table);
        $jform_column_type_table.trigger("liszt:updated");
    }
}

/*render data table, cells, rows, cols*/
function cleanStyle(style, nbRows, nbCols) {
    for (var col in style.cols) {
        if (!style.cols[col] || style.cols[col][0] >= nbCols) {
            delete style.cols[col];
        }
    }
    for (var row in style.rows) {
        if (!style.rows[row] || style.rows[row][0] >= nbRows) {
            delete style.rows[row];
        }
    }
    for (var cell in style.cells) {
        if (style.cells[cell][0] >= nbRows || style.cells[cell][1] >= nbCols) {
            delete style.cells[cell];
        }
    }
    var propertiesPos, cells;
    for (var obj in style) {
        if (obj == 'table') {
            continue;
        }
        for (cells in style[obj]) {
            propertiesPos = style[obj][cells].length - 1;
            for (var property in style[obj][cells][propertiesPos]) {
                if (style[obj][cells][propertiesPos][property] === null) {
                    delete style[obj][cells][propertiesPos][property];
                }
            }
        }
    }
    return style;
}

/*change text table name, chart name*/
function setText($name_edit, press_enter_selector, obj) {
    unbindall();
    /*select rename table*/
    var oldTitle = $name_edit.text();
    var wptm_name_edit = document.querySelector('.rename.wptm_name_edit');
    $name_edit.attr('contentEditable', true).focus();

    $name_edit.not('.editable').bind('click.mm', hstop);  //let's click on the editable object
    jquery(press_enter_selector).bind('keydown.mm', enterKey); //let's press enter to validate new title'
    jquery('*').not('.wptm_name_edit').bind('click.mm', houtside);

    // $name_edit.addClass('editable');
    if (obj.selected) {
        $name_edit.trigger('click.mm');
    }

    function unbindall(reselectCell) {
        $name_edit.not('.editable').unbind('click.mm', hstop);  //let's click on the editable object
        jquery(press_enter_selector).unbind('keydown.mm', enterKey); //let's press enter to validate new title'
        jquery('*').not('.wptm_name_edit').unbind('click.mm', houtside);
        $name_edit.attr('contentEditable', false);

        if (reselectCell && typeof table_function_data.selection[0][1] !== 'undefined') {//reselect cell after enter/esc name table
            var selection = window.jquery(window.Wptm.container).handsontable('getSelected');
            window.jquery(Wptm.container).handsontable("selectCell", selection[0][0], selection[0][1], selection[0][0], selection[0][1]);
        }
    }

    //Validation
    function hstop(e) {
        event.stopPropagation();
        $name_edit.addClass('editable');
        if (wptm_name_edit !== null) {
            wptm_name_edit.focus();
        }
        return false;
    }

    function enterKey(e) {
        e.stopImmediatePropagation();
        if (e.which == 13) {
            e.preventDefault();
            $name_edit.removeClass('editable');
            updateTitle($name_edit.text(), jquery);
            unbindall(true);
        }
        if (e.which == 27) {
            e.preventDefault();
            $name_edit.text(oldTitle);
            $name_edit.removeClass('editable');
            unbindall(true);
        }
        $name_edit.removeClass('rename');
    }

    function houtside(e) {
        if ($name_edit.hasClass('editable') && !$name_edit.hasClass('rename')) {
            $name_edit.removeClass('editable');
            updateTitle($name_edit.text(), jquery);
            unbindall(true);
            $name_edit.removeClass('rename');
        }
        return false;
    }

    function updateTitle(title, $) {
        if (!(Wptm.can.edit || (Wptm.can.editown && data.author === Wptm.author))) {
            return false;
        }

        if (oldTitle === title) {
            return false;
        }

        if (title.trim() !== '' && typeof obj.url !== 'undefined') {
            obj.url += title;
            $.ajax({
                url: obj.url,
                type: "POST",
                data: {},
                dataType: "json",
                beforeSend: function () {
                    wptm_element.settingTable.find('.wptm_saving div').removeClass('wptm_hiden');
                },
                success: function (datas) {
                    if (datas.response === true) {
                        $name_edit.text(title);
                        if ($('#pwrapper').hasClass('wptm_hiden')) {
                            $('#list_chart').find('.chart-menu.active a').text(title);
                        } else {
                            $('#list_chart').find('.current_table a').text(title);
                        }
                        if (typeof obj.action !== 'undefined') {
                            obj.action(title);
                        }
                        wptm_element.settingTable.find('.wptm_saving').removeClass('wptm_hiden');
                        setTimeout(function () {
                            wptm_element.settingTable.find('.wptm_saving').addClass('wptm_hiden');

                            if (typeof obj.chart_id !== 'undefined') {
                                localStorage.setItem('wptm_change_chart_name', JSON.stringify({
                                    'id': Wptm.id,
                                    'chart_id': obj.chart_id,
                                    'title': title,
                                    'modified_time': datas.datas.modified_time
                                }));
                            } else {
                                localStorage.setItem('wptm_change_table_name', JSON.stringify({
                                    'id': Wptm.id,
                                    'title': title,
                                    'modified_time': datas.datas.modified_time
                                }));
                            }

                        }, 1500);
                    } else {
                        $name_edit.text(oldTitle);
                        wptmBootbox('', datas.response, true, false);
                    }
                },
                error: function (jqxhr, textStatus, error) {
                    $name_edit.text(oldTitle);
                    wptmBootbox('', textStatus, true, false);
                }
            });
        } else {
            $name_edit.text(oldTitle);
            return false;
        }
        $name_edit.css('white-space', 'normal');
        setTimeout(function () {
            $name_edit.css('white-space', '');
        }, 200);
    }
};

//https://gist.github.com/ncr/399624
jQuery.fn.single_double_click = function (single_click_callback, double_click_callback, timeout) {
    return this.each(function () {
        var clicks = 0, self = this;
        jQuery(this).click(function (event) {
            clicks++;
            if (clicks == 1) {
                setTimeout(function () {
                    if (clicks == 1) {
                        single_click_callback.call(self, event);
                    } else {
                        double_click_callback.call(self, event);
                    }
                    clicks = 0;
                }, timeout || 300);
            }
        });
    });
}

//add, change style for column, rows, cells
function getFillArray(selection, Wptm, value) {
    var action = Array.prototype.slice.call(arguments, 3);
    var save_data = false;
    var actionInSaveData = null;
    if (action.length === 0) {
        actionInSaveData = 'style';
    } else {
        if (action.length > 1) {
            save_data = action[1];
        }
        // if (action.length > 2) {
        //     actionCall = action[2]
        // }
        actionInSaveData = action[0];
    }

    var checkRangeStyleExists = false;
    if (saveData.length > 0) {
        var old_saveData = saveData[saveData.length - 1];
    }

    eventToCellsSelected(
        selection,
        table_function_data.selectionSize,
        (ranger, i) => {
            return new Promise((resolve) => {
                if (typeof old_saveData !== 'undefined') {
                    if (actionInSaveData === old_saveData.action) {
                        if (old_saveData.selection[i][0] == selection[i][0] &&
                            old_saveData.selection[i][1] == selection[i][1] &&
                            old_saveData.selection[i][2] == selection[i][2] &&
                            old_saveData.selection[i][3] == selection[i][3]) {
                            checkRangeStyleExists = true;
                        }
                    }
                }

                for (let i2 = ranger[1]; i2 <= ranger[3]; i2++) {
                    if (table_function_data.option_selected_mysql !== '' && typeof table_function_data.option_selected_mysql !== 'undefined') {
                        Wptm.style.cols = fillArray(Wptm.style.cols, value, i2);

                        if (i2 === ranger[3]) {
                            resolve(true)
                        }
                    } else {
                        for (let i3 = ranger[0]; i3 <= ranger[2]; i3++) {
                            Wptm.style.cells = fillArray(Wptm.style.cells, value, i3, i2);

                            if (i3 === ranger[2]) {
                                resolve(true)
                            }
                        }
                    }
                }

            })
        },
        null,
        null,
        () => {
            return new Promise((resolve) => {
                if (typeof old_saveData !== 'undefined') {
                    if (checkRangeStyleExists) {
                        old_saveData.style = window.jquery.extend(old_saveData.style, value);
                    } else {
                        if (droptablesCheckSize(value) > 0) {
                            saveData.push({action: actionInSaveData, selection: selection, style: value});
                            if (save_data) {
                                saveChanges(true);
                            }
                        }
                    }
                } else {
                    if (droptablesCheckSize(value) > 0) {
                        saveData.push({action: actionInSaveData, selection: selection, style: value});
                        if (save_data) {
                            saveChanges(true);
                        }
                    }
                }
                if (typeof action[2] !== 'string' && typeof action[2] !== 'undefined') {
                    action[2].call()
                }
                resolve(true)
            })
        }
    )
}

function droptablesCheckSize(obj) {
    var size = 0,
        key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

function removeValueInArray(array, parameters, val1, val2) {
    if (typeof (val2) === 'undefined') {
        if (typeof (val1) !== 'undefined') {
            if (typeof (array[val1]) !== 'undefined') {
                if (typeof array[val1][1][parameters] !== 'undefined') {
                    delete array[val1][1][parameters];
                }
            } else {
                array[val1] = [val1, {}];
            }
        } else {
            delete array[parameters];
        }
    } else {
        if (typeof (array[val1 + "!" + val2]) !== 'undefined') {
            if (typeof array[val1 + "!" + val2][2][parameters] !== 'undefined') {
                delete array[val1 + "!" + val2][2][parameters];
            }
        } else {
            array[val1 + "!" + val2] = [val1, val2, {}];
        }
    }
    return array;
}

//update new value for array
function fillArray(array, val, val1, val2) {
    if (typeof (val2) === 'undefined') {
        if (typeof (val1) !== 'undefined') {
            if (typeof (array[val1]) !== 'undefined') {
                array[val1][1] = window.jquery.extend(array[val1][1], val);
            } else {
                array[val1] = [val1, {}];
                array[val1][1] = val;
            }
        } else {
            array = window.jquery.extend(array, val);
        }
    } else {
        if (typeof (array[val1 + "!" + val2]) !== 'undefined') {
            array[val1 + "!" + val2][2] = window.jquery.extend(array[val1 + "!" + val2][2], val);
        } else {
            array[val1 + "!" + val2] = [val1, val2, {}];
            array[val1 + "!" + val2][2] = val;
        }
    }
    return array;
}

function toggleArray(array, val, val1, val2) {
    if (typeof (val2) === 'undefined') {
        if (typeof (array[val1]) !== 'undefined') {
            if (typeof (val) === 'object') {
                for (var key in val) {
                    if (typeof (array[val1][1][key] !== 'undefined')) {
                        array[val1][1][key] = !array[val1][1][key];
                    } else {
                        array[val1][1][key] = val[key];
                    }
                }
            } else {
                array[val1][1] = jQuery.extend(array[val1][1], val);
            }
        } else {
            array[val1] = [val1, {}];
            array[val1][1] = val;
        }
    } else {
        if (typeof (array[val1 + "!" + val2]) !== 'undefined') {
            if (typeof (val) === 'object') {
                for (var key in val) {
                    if (typeof (array[val1 + "!" + val2][2][key] !== 'undefined')) {
                        array[val1 + "!" + val2][2][key] = !array[val1 + "!" + val2][2][key];
                    } else {
                        array[val1 + "!" + val2][2][key] = val[key];
                    }
                }
            } else {
                array[val1 + "!" + val2][2] = jQuery.extend(array[val1 + "!" + val2][2], val);
            }
        } else {
            array[val1 + "!" + val2] = [val1, val2, {}];
            array[val1 + "!" + val2][2] = val;
        }
    }

    return array;
}

//Code from http://stackoverflow.com/questions/9905533/convert-excel-column-alphabet-e-g-aa-to-number-e-g-25
var convertAlpha = function (val) {
    var base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', i, j, result = 0;

    for (i = 0, j = val.length - 1; i < val.length; i += 1, j -= 1) {
        result += Math.pow(base.length, j) * (base.indexOf(val[i]) + 1);
    }

    return result;
};

function wptm_clone(v) {
    return JSON.parse(JSON.stringify(v));
}

//convert value of calculation by format selected
function formatSymbols(resultCalc, decimal_count, thousand_symbols, decimal_symbols, symbol_position, value_unit) {
    decimal_count = parseInt(decimal_count);
    if (typeof resultCalc === 'undefined') {
        return;
    }
    var negative = resultCalc < 0 ? "-" : "",
        i = parseInt(resultCalc = Math.abs(+resultCalc || 0).toFixed(decimal_count), 10) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;

    resultCalc = (j ? i.substr(0, j) + thousand_symbols : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand_symbols) + (decimal_count ? decimal_symbols + Math.abs(resultCalc - i).toFixed(decimal_count).slice(2) : "");

    resultCalc = Number(symbol_position) === 0
        ? ((negative === "-") ? negative + value_unit : value_unit) + resultCalc
        : negative + resultCalc + ' ' + value_unit;

    return resultCalc;
};

// Convert date in date_format to the format: m/d/Y /a/t g:i A
// example: number -> array(2016,03,26)
function convertDate(date_format, number, timezone) {
    var date_array = [];
    number = (!!number) ? number : [];
    if (date_format.length !== number.length) {
        return false;
    }
    if (typeof timezone === 'undefined') {
        timezone = true;
    }
    for (var n = 0; n < date_format.length; n++) {
        number[n] = (!!number[n]) ? number[n] : '';
        if (date_format[n] === 'd' || date_format[n] === 'j') {
            date_array[2] = (number[n] !== '') ? number[n] : '';
        } else if (date_format[n] === 'S' || date_format[n] === 'jS' || date_format[n] === 'dS') {
            date_array[2] = number[n].match(/[0-9]+/g);
        } else if (date_format[n] === 'm' || date_format[n] === 'n') {
            date_array[1] = number[n];
        } else if (date_format[n] === 'F') {
            date_array[1] = F_name.indexOf(number[n].toLowerCase()) + 1;
        } else if (date_format[n] === 'M') {
            date_array[1] = M_name.indexOf(number[n].toLowerCase()) + 1;
        } else if (date_format[n].toLowerCase() === 'y') {
            date_array[3] = number[n];
        } else if (date_format[n].toLowerCase() === 'g' || date_format[n].toLowerCase() === 'h') {
            date_array[4] = Number(number[n]);
        } else if (date_format[n].toLowerCase() === 'ga' || date_format[n].toLowerCase() === 'ha') {
            date_array[4] = number[n].match(/[0-9]+/g);
            date_array[4] = (number[n].toLowerCase().match(/[a-z]+/g) === 'am') ? date_array[4] : date_array[4] + 12;
        } else if (date_format[n].toLowerCase() === 'a') {
            date_array[7] = number[n];
        } else if (date_format[n].toLowerCase() === 'i' || date_format[n].toLowerCase() === 'ia') {
            date_array[5] = number[n].match(/[0-9]+/g);
        } else if (date_format[n].toLowerCase() === 's' || date_format[n].toLowerCase() === 'sa') {
            date_array[6] = number[n].match(/[0-9]+/g);
        } else if (date_format[n] === 'T') {
            date_array[8] = number[n];
        } else if (date_format[n] === 'r') {
            if (M_name.indexOf(number[2].toLowerCase()) + 1 > 0) {
                date_array[1] = M_name.indexOf(number[2].toLowerCase()) + 1;
            } else {
                date_array[1] = F_name.indexOf(number[2].toLowerCase()) + 1;
            }
            return date_array[1] + '/' + number[1] + '/' + number[3] + ' ' + number[4] + ':' + number[5] + ':' + number[6] + ' ' + number[7];
        }
    }
    date_array[4] = (!!date_array[4]) ? date_array[4] : 0;
    date_array[5] = (!!date_array[5]) ? date_array[5] : '00';
    date_array[6] = (!!date_array[6]) ? date_array[6] : '00';
    date_array[7] = (!!date_array[7]) ? ' ' + date_array[7] : '';
    date_array[8] = (!!date_array[8]) ? ' ' + date_array[8] : '';
    date_array[8] = (timezone === true) ? ' ' + date_array[8] : '';

    if (date_array[7] !== 'undefined' && date_array[7] !== '' && date_array[4] > 12) {
        date_array[4] = date_array[4] - 12;
    }
    if (date_array[1] === 0 || date_array[2] > 31 || date_array[1] > 12) {
        return false;
    }
    return date_array[1] + '/' + date_array[2] + '/' + date_array[3] + ' ' + date_array[4] + ':' + date_array[5] + ':' + date_array[6] + date_array[7] + date_array[8];
}

//create string_currency_symbols, replace_unit, text_replace_unit, date_format RegExp string
function createRegExpFormat(table_function_data, currency_symbol, date_formats) {
    if (currency_symbol !== false) {
        table_function_data.string_currency_symbols = currency_symbol.replace(/ /g, "");
        // create string reg currency symbols
        table_function_data.replace_unit = new RegExp('[' + table_function_data.string_currency_symbols.replace(/,/g, "|") + ']', "g");

        // create string reg have not currency symbols
        table_function_data.text_replace_unit = '[^a-zA-Z|' + table_function_data.string_currency_symbols.replace(/,/g, "|^") + ']';
        table_function_data.text_replace_unit = new RegExp(table_function_data.text_replace_unit, "g");
        table_function_data.text_replace_unit_function = new RegExp('[^ |' + table_function_data.string_currency_symbols.replace(/,/g, "|^") + ']', "g");
    }
    if (date_formats !== false) {
        table_function_data.date_format = date_formats.match(/[a-zA-Z|\\]+/g);
    }
    return table_function_data;
}

// check column is int
function replaceCell($, cellsData, currency_symbol) {
    var data1 = [];
    var i = 0;
    var data2 = -1;
    var v1 = '';
    currency_symbol = new RegExp('[0-9|\.|\,|\\-|' + currency_symbol + ']', "g");

    $.each(cellsData, function (k, v) {
        v = v.toString();
        v1 = v.replace(currency_symbol, '');
        if (v1 === '') {
            data1[i] = k;
            i++;
        } else if (v !== '') {
            data2 = k;
        }
    });
    var data = [];
    data[1] = '';
    data[0] = data1;
    if (data2 !== -1) {
        data[1] = data2;
    }
    return data;
}

//get size of cell selected when open change height, row popup
function getSizeCells($, Wptm, cells) {
    if (Wptm.type === 'mysql') {
        updateParamFromStyleObject(Wptm.style.rows[0][1], 'height', $('#cell_row_height'), '30');
    } else {
        if (checkObjPropertyNested(Wptm.style.rows, cells[0], 1, 'height')) {
            $('#cell_row_height').val(Wptm.style.rows[cells[0]][1].height);
        }
    }

    if (checkObjPropertyNested(Wptm.style.cols, cells[1], 1, 'width')) {
        $('#cell_col_width').val(Wptm.style.cols[cells[1]][1].width);
    }
    if (checkObjPropertyNested(Wptm.style.table, 'allRowHeight')) {
        $('#all_cell_row_height').val(Wptm.style.table.allRowHeight);
    }
}

function changeValue(oldValue, index, amount, lengths, LeftRight, copyHeightRow) {
    var i;
    var new_data = {};
    if (LeftRight) {//left
        for (i = index; i < lengths; i++) {
            if (typeof new_data[i + amount] === 'undefined') {
                new_data[i + amount] = [i + amount, {}];
            }

            new_data[i + amount][1] = oldValue[i][1];
            if (i + 1 === lengths) {
                var return_data = jquery.extend({}, oldValue, new_data);
                return return_data;
            }
        }
    } else {//right
        for (i = index; i < lengths + amount; i++) {
            if (typeof new_data[i] === 'undefined') {
                new_data[i] = [i, {}];
            }

            if (!copyHeightRow) {
                new_data[i][1] = jquery.extend({}, oldValue[i - amount][1]);
            }

            if ((i + 1) === (lengths + amount)) {
                return jquery.extend({}, oldValue, new_data);
            }
        }
    }
}

function pullDims(Wptm, $) {
    var args = Array.prototype.slice.call(arguments, 1);
    var cols = [];
    var rows = [];
    var row, lengthRows, lengthCols, col;

    // get count of Wptm.style.rows
    if (typeof Wptm.style.rows.length !== 'undefined') {
        lengthRows = Wptm.style.rows.length;
    } else {
        lengthRows = Object.keys(Wptm.style.rows).length;
    }

    for (row = 0; row < lengthRows; row++) {
        if (checkObjPropertyNested(Wptm.style.rows[row], 1, 'height')) {
            rows[row] = Wptm.style.rows[row][1].height;
        } else if (Wptm.type === 'mysql' && typeof Wptm.style.table.allRowHeight !== 'undefined' && Wptm.style.table.allRowHeight !== '') {
            rows[row] = Wptm.style.table.allRowHeight;
        } else {
            rows[row] = null;
        }
    }
    // get count of Wptm.style.cols

    if (typeof Wptm.style.cols.length !== 'undefined') {
        lengthCols = Wptm.style.cols.length;
    } else {
        lengthCols = Object.keys(Wptm.style.cols).length;
    }

    for (col = 0; col < lengthCols; col++) {
        if (checkObjPropertyNested(Wptm.style.cols[col], 1, 'width')) {
            cols[col] = Wptm.style.cols[col][1].width;
        } else {
            cols[col] = null;
        }
    }
    Wptm.updateSettings.manualColumnResize = $.extend([], cols);
    Wptm.updateSettings.rowHeights = $.extend([], rows);

    if (typeof args[1] === 'undefined' || args[1] === true) {
        delete Wptm.updateSettings.manualColumnResize;
        delete Wptm.updateSettings.manualRowResize;

        Wptm.container.handsontable('updateSettings', {'manualColumnResize': cols});
        Wptm.container.handsontable('updateSettings', {'manualRowResize': rows});
    }
}

//add row height, col width for Wptm.style
function pushDims($, Wptm) {
    //get value height rows
    var rows = $(Wptm.container).handsontable('countRows');
    var i = 0;
    var value = 0;
    for (i = 0; i < rows; i++) {
        value = $(Wptm.container).handsontable('getRowHeight', i);
        if (Wptm.type === 'html') {
            if (!value || value === 0) {
                if (typeof (Wptm.style.rows[i]) !== 'undefined' && typeof (Wptm.style.rows[i][1].height) !== 'undefined') {
                    value = parseInt(Wptm.style.rows[i][1].height);
                }
                if (value === null) {
                    value = 22;
                }
            }
        } else { //type mysql
            if (checkObjPropertyNestedNotEmpty(Wptm.style.rows[i], 1, 'height')) { //Row height is set
                value = parseInt(Wptm.style.rows[i][1].height);
            } else {
                value = null; //if aviation height is set then height === null
            }
        }

        Wptm.style.rows = fillArray(Wptm.style.rows, {height: parseInt(value)}, i);
        value = 0;
    }
    //get value width columns
    var cols = $(Wptm.container).handsontable('countCols');
    Wptm.max_Col = cols;
    Wptm.max_row = rows;

    Wptm.style.table.width = 0;
    var valueCols = 0;
    for (i = 0; i < cols; i++) {
        valueCols = $('#tableContainer').handsontable('getColWidth', i);
        if (!valueCols || valueCols === 0) {
            if (typeof (Wptm.style.cols[i]) !== 'undefined' && Wptm.style.cols[i] !== null && (typeof (Wptm.style.cols[i][1].width) !== 'undefined')) {
                valueCols = parseInt(Wptm.style.cols[i][1].width);
            } else {
                valueCols = null;
            }
        }
        if (!valueCols || valueCols === 0) {
            valueCols = 100;
        }

        Wptm.style.cols = fillArray(Wptm.style.cols, {width: parseInt(valueCols)}, i);

        Wptm.style.table.width += parseInt(valueCols);
        valueCols = 0;
    }
}

//add res priority of columns to set resposive priority popup
function responsive_col(Wptm) {
    var col = this.val();
    if (typeof (Wptm.style.cols) !== 'undefined' && typeof (Wptm.style.cols[col]) !== 'undefined' && typeof (Wptm.style.cols[col][1]) !== 'undefined' && typeof (Wptm.style.cols[col][1].res_priority) !== 'undefined') {
        var res_priority = Wptm.style.cols[col][1].res_priority;

        this.siblings('#responsive_priority').val(res_priority);
    } else {

        this.siblings('#responsive_priority').val(0);
    }
    this.siblings('#responsive_priority').trigger('liszt:updated');
}

function loading(e) {
    e.addClass('dploadingcontainer');
    e.append('<div class="dploading"></div>');
}

function rloading(e) {
    e.removeClass('dploadingcontainer');
    e.find('div.dploading').remove();
}

//add custom css for element
function parseCss($) {
    var parser = new (less.Parser);
    var content = '#mainTabContent .handsontable .ht_master .wtHider .wtSpreader .htCore tbody {' + $('#jform_css').val() + '}';
    content += '#mainTabContent .handsontable .ht_clone_top .wtHider .wtSpreader .htCore tbody {' + $('#jform_css').val() + '}';
    content += '#mainTabContent .handsontable .ht_clone_bottom .wtHider .wtSpreader .htCore tbody {' + $('#jform_css').val() + '}';
    content += '#mainTabContent .handsontable .ht_clone_left .wtHider .wtSpreader .htCore tbody {' + $('#jform_css').val() + '}';
    content += '#mainTabContent .handsontable .ht_clone_top_left_corner .wtHider .wtSpreader .htCore tbody {' + $('#jform_css').val() + '}';
    content += '.reset {background-color: rgb(238, 238, 238);border-bottom-color: rgb(204, 204, 204);border-bottom-style: solid;border-bottom-width: 1px;border-collapse: collapse;border-left-color: rgb(204, 204, 204);border-left-style: solid;border-left-width: 1px;border-right-color: rgb(204, 204, 204);border-right-style: solid;border-right-width: 1px;border-top-color: rgb(204, 204, 204);border-top-style: solid;border-top-width: 1px;box-sizing: content-box;color: rgb(34, 34, 34);display: table-cell;empty-cells: show;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;font-size: 13px;font-weight: normal;line-height: 21px;outline-width: 0px;overflow-x: hidden;overflow-y: hidden;padding-bottom: 0px;padding-left: 4px;padding-right: 4px;padding-top: 0px;text-align: center;vertical-align: top;white-space: nowrap;position: relative;}';
    content += '#mainTabContent .handsontable .ht_master .wtHider .wtSpreader .htCore tbody tr th {.reset() !important;}'
    parser.parse(content, function (err, tree) {
        if (err) {
            //Here we can throw the erro to the user
            return false;
        } else {
            Wptm.css = $('#jform_css').val();
            if ($('#headCss').length === 0) {
                $('head').append('<style id="headCss"></style>');
            }
            $('#headCss').text(tree.toCSS());
            return true;
        }
    });
}

function getCellData(cellPos) {
    var pos = cellPos.split(":");
    var value = Wptm.container.handsontable('getDataAtCell', parseInt(pos[0]), parseInt(pos[1]));

    return value;
}

//replace string by decimal_symbol value and thousand_symbol value in calculation cell
function stringReplace(arr, unit) {
    var thousand_symbol = (!Wptm.style.table.thousand_symbol) ? default_value.thousand_symbol : Wptm.style.table.thousand_symbol;
    var decimal_symbol = (!Wptm.style.table.decimal_symbol) ? default_value.decimal_symbol : Wptm.style.table.decimal_symbol;
    if (typeof arr === 'number') {
        return arr;
    }
    var thousand_re = new RegExp('[' + thousand_symbol + ']', "g");
    // var thousand_re = new RegExp(thousand_symbol,"g");
    if (typeof arr !== 'undefined' && arr !== '' && arr !== null) {
        if (unit === true) {
            if (typeof arr !== 'string') {
                arr = arr.toString();
            }
            arr = arr.replace(table_function_data.text_replace_unit, "");
        } else {
            arr = arr.replace(table_function_data.replace_unit, "");
            arr = arr.replace(thousand_re, "");
            // arr = (thousand_symbol === ',') ? arr.replace(/,/g, "") : (thousand_symbol === '.' ? arr.replace(/\./g, "") : arr);
            arr = (decimal_symbol === ',') ? arr.replace(/,/g, ".") : arr;
        }
    } else {
        arr = '';
    }
    return arr;
}

//Toggle between the charts and the table
function showChartOrTable(showChart, $chart) {
    if (showChart) {//show chart
        jquery('#wptm_chart').removeClass('wptm_hiden');
        jquery('#pwrapper').addClass('wptm_hiden');
        $chart.trigger('click');
        jquery('#list_chart').find('.current_table').unbind('click').on('click', showChartOrTable.bind(this, false));
    } else {//show table
        if (jquery('#inserttable').length > 0) {
            Wptm.chart_active = 0;
            if (!jquery('#inserttable').hasClass('not_change_type')) {
                jquery('#inserttable').data('type', 'table').attr('data-type', 'table').text(insert_table);
            }
            jquery('#inserttable').removeClass("no_click");
        }
        jquery('#wptm_chart').addClass('wptm_hiden');
        jquery('#pwrapper').removeClass('wptm_hiden');
        Wptm.container.handsontable('render');
    }
    jquery('#over_loadding_open_chart').hide();
    wptm_element.primary_toolbars.find('.menu_loading').closest('li').removeClass('menu_loading');
    wptm_element.settingTable.find('.ajax_loading').removeClass('loadding').addClass('wptm_hiden');
}

function ripple_button(that) {
    that.find(".wptm_ripple").click(function (e) {
        // Remove any old one
        jquery(".ripple").remove();

        // Setup
        var posX = jquery(this).offset().left,
            posY = jquery(this).offset().top,
            buttonWidth = jquery(this).width(),
            buttonHeight = jquery(this).height();

        // Add the element
        jquery(this).prepend("<span class='ripple'></span>");

        // Make it round!
        if (buttonWidth >= buttonHeight) {
            buttonHeight = buttonWidth;
        } else {
            buttonWidth = buttonHeight;
        }

        // Get the center of the element
        var x = e.pageX - posX - buttonWidth / 2;
        var y = e.pageY - posY - buttonHeight / 2;

        // Add the ripples CSS and start the animation
        jquery(".ripple").css({
            width: buttonWidth,
            height: buttonHeight,
            top: y + 'px',
            left: x + 'px'
        }).addClass("rippleEffect");
    });
}

function isSameArray(array1, array2) {
    return array1.length === array2.length && array1.every(function (element, index) {
        return element === array2[index];
    });
}

//mer rows, columns style to cells style
function mergeCollsRowsstyleToCells() {
    var style = jquery.extend({}, window.Wptm.style);
    jquery.each(style.cols, function (col, cValue) {
        jquery.each(style.rows, function (row, rValue) {
            var styleCell = {};
            if (typeof (window.Wptm.style.cells[row + '!' + col]) === 'undefined') {
                window.Wptm.style.cells[row + '!' + col] = [row, col, {}];
            }
            if (typeof (style.rows[row]) !== 'undefined' && style.rows[row] !== null && Object.keys(style.rows[row][1]).length !== 0) {
                styleCell = jquery.extend({}, styleCell, rValue[1]);
            }
            if (typeof (style.cols[col]) !== 'undefined' && style.cols[col] !== null && Object.keys(style.cols[col][1]).length !== 0) {
                styleCell = jquery.extend({}, styleCell, cValue[1]);
            }
            window.Wptm.style.cells[row + '!' + col][2] = jquery.extend({}, styleCell, window.Wptm.style.cells[row + '!' + col][2]);
        });
    });
}

function setFormat_accounting() {
    var data = {};
    data.thousand_symbol = Wptm.style.table.thousand_symbol;
    data.symbol_position = Wptm.style.table.symbol_position;
    data.decimal_symbol = Wptm.style.table.decimal_symbol;
    data.decimal_count = Wptm.style.table.decimal_count;
    data.currency_symbol = Wptm.style.table.currency_symbol;

    accounting.settings = {
        currency: {
            symbol: data.currency_symbol,   // default currency symbol is '$'
            format: data.symbol_position == 1 ? "%v %s" : "%s %v", // controls output: %s = symbol, %v = value/number (can be object: see below)
            decimal: data.decimal_symbol,  // decimal point separator
            thousand: data.thousand_symbol,  // thousands separator
            precision: data.decimal_count   // decimal places
        },
        number: {
            decimal: data.decimal_symbol,  // decimal point separator
            thousand: data.thousand_symbol,  // thousands separator
            precision: data.decimal_count   // decimal places
        }
    };
}

function updateStyleAutofill(start_rangess, new_range, direction) {
    var respectively = [], data_respectively;
    var maxRange = [start_rangess[2] - start_rangess[0] + 1, start_rangess[3] - start_rangess[1] + 1];

    for (var i = new_range[0]; i <= new_range[2]; i++) {//rows
        for (var j = new_range[1]; j <= new_range[3]; j++) {//cols
            respectively[0] = i - new_range[0] > maxRange[0]
                ? ((i - new_range[0]) % maxRange[0]) + start_rangess[0]
                : i - new_range[0] + start_rangess[0];
            respectively[1] = j - new_range[1] > maxRange[1]
                ? ((j - new_range[1]) % maxRange[1]) + start_rangess[1]
                : j - new_range[1] + start_rangess[1];
            //update cell hyperlink
            if (typeof Wptm.hyperlink[i + '!' + j] !== 'undefined') {
                delete Wptm.hyperlink[i + '!' + j];
            }
            if (typeof Wptm.hyperlink[respectively[0] + '!' + respectively[1]] !== 'undefined') {
                data_respectively = Wptm.hyperlink[respectively[0] + '!' + respectively[1]];
                Wptm.hyperlink[i + '!' + j] = {};
                Wptm.hyperlink[i + '!' + j].hyperlink = data_respectively.hyperlink;
                Wptm.hyperlink[i + '!' + j].text = data_respectively.text;
            }

            //update cell type and col type
            var selection = {'0': [i, j, i, j]};
            if (typeof Wptm.style.cells[i + '!' + j] !== 'undefined'
                && Wptm.style.cells[i + '!' + j][2].cell_type === 'html') {
                Wptm.style.cells[i + '!' + j][2].cell_type = null;
                saveData.push({action: 'set_cells_type', selection: selection, style: {cell_type: null}});
            } else if (typeof Wptm.style.cells[i + '!' + j] === 'undefined' || typeof Wptm.style.cells[i + '!' + j][2] === 'undefined') {
                Wptm.style.cells[i + '!' + j] = [i, j, {}];
            }

            if (typeof Wptm.style.cells[respectively[0] + '!' + respectively[1]] !== 'undefined'
                && Wptm.style.cells[respectively[0] + '!' + respectively[1]][2].cell_type === 'html') {
                Wptm.style.cells[i + '!' + j][2].cell_type = 'html';
                saveData.push({action: 'set_cells_type', selection: selection, style: {cell_type: 'html'}});

                if (typeof Wptm.style.table.col_types !== 'undefined'
                    && Wptm.style.table.col_types[j] !== 'text') {
                    Wptm.style.table.col_types[j] = 'text';
                    if (typeof table_function_data.colsSetType === 'undefined') {
                        table_function_data.colsSetType = {};
                    }
                    table_function_data.colsSetType[j] = 'text';
                }
            }
        }
    }
}

function setPositionForHtmlCellEditor() {
    var tdOffset = jquery(window.Wptm.container).find('.isHtmlCell.dtr' + table_function_data.selection[0][0] + '.dtc' + table_function_data.selection[0][1]).offset();
    var offsetTable = wptm_element.tableContainer.offset();
    var heightTable = wptm_element.tableContainer.outerHeight() + offsetTable.top;
    var widthTable = wptm_element.tableContainer.outerWidth() + offsetTable.left;

    var wptm_edit_html_cell_width = wptm_element.wptm_edit_html_cell.outerWidth();
    var wptm_edit_html_cell_height = wptm_element.wptm_edit_html_cell.outerHeight();

    if (wptm_edit_html_cell_width + tdOffset.left > widthTable) {
        tdOffset.left = widthTable - wptm_edit_html_cell_width - 30;
    }
    if (350 + tdOffset.top > heightTable) {
        tdOffset.top = heightTable - 350;
    }

    return tdOffset;
}

function hashFnv32a() {
    return Math.random().toString(36).substr(2) + Math.random().toString(36).substr(2);
}

function copy_text(that, default_value) {
    var copyText = jquery(that).parent().find('.wptm_copy_text_content');
    var textArea = document.createElement("textarea");

    if (copyText[0].nodeName === 'INPUT') {
        if (copyText.val() === '') {
            textArea.value = default_value;
        } else {
            textArea.value = copyText.val();
        }
    } else {
        if (copyText.text() === '') {
            textArea.value = default_value;
        } else {
            textArea.value = copyText.text();
        }
    }

    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand("Copy");
    textArea.remove();
}

function getLinkSync(link, type, warningDisplay) {
    var data = {};
    switch (type) {
        case 'excel':
            if (link.indexOf("docs.google.com/spreadsheet") !== -1 && warningDisplay) {
                wptmBootbox('', wptmText.error_link_import_sync, wptmText.GOT_IT, false);
                Wptm.style.table.excel_url = '';
                return false;
            }
            data.auto_sync = Wptm.style.table.excel_auto_sync != '1' ? 0 : 1;
            data.spreadsheet_style = Wptm.style.table.excel_spreadsheet_style != '1' ? 0 : 1;
            data.url = encodeURI(link);

            break;
        case 'csv':
            data.auto_sync = Wptm.style.table.auto_sync_csv != '1' ? 0 : 1;
            data.url = encodeURI(link);

            break;
        case 'onedrive':
            if (link.indexOf("sharepoint") === -1 && link.indexOf("onedrive.live.com") === -1 && warningDisplay) {
                wptmBootbox('', wptmText.error_link_import_sync, wptmText.GOT_IT, false);
                Wptm.style.table.onedrive_url = '';
                return false;
            }
            data.auto_sync = Wptm.style.table.auto_sync_onedrive != '1' ? 0 : 1;
            data.spreadsheet_style = Wptm.style.table.onedrive_style != '1' ? 0 : 1;

            if (link.indexOf("onedrive.live.com") !== -1 && link.indexOf("download?") === -1) {
                if (link.indexOf("<iframe") !== -1) {
                    link = link.match(/src="(.*?)"/)[1];
                }
                link = link.replace("embed?", "download?");
            } else if (link.indexOf("sharepoint") !== -1 && link.indexOf("download=") === -1) {
                link += '&download=1';
            }
            data.url = link;

            break;
        case 'spreadsheet':
            var end_link = link.split('?');
            var end_link0 = end_link[0].slice(-7);
            if (link.indexOf("docs.google.com/spreadsheet") === -1 && end_link0.match(/html|csv|pdf|xlsx/gi) === null && (typeof end_link[1] !== "undefined" && end_link[1].match(/sharing|html|csv|pdf|xlsx/gi) === null) && warningDisplay) {
                wptmBootbox('', wptmText.error_link_google_sync, wptmText.GOT_IT, false);
                Wptm.style.table.spreadsheet_url = '';
                return false;
            }
            data.auto_sync = Wptm.style.table.auto_sync != '1' ? 0 : 1;
            data.spreadsheet_style = Wptm.style.table.spreadsheet_style != '1' ? 0 : 1;
            data.url = encodeURI(link);
            break;
    }

    return data;
}

//convert droptables's dateformat to momentjs's dateformat
function momentjsFormat(value) {
    value = value.replaceAll("M", "MMM");
    value = value.replaceAll("n", "M");
    value = value.replaceAll("F", "MMMM");
    value = value.replaceAll("m", "MM");

    value = value.replaceAll("D", "ddd");
    let date = new RegExp('(\\b)d(?!d)', "g");
    value = value.replaceAll(date, "DD");

    value = value.replaceAll("j", "D");
    value = value.replaceAll("z", "DDDD");
    value = value.replaceAll("l", "dddd");
    value = value.replaceAll("S", "Do");
    value = value.replaceAll("N", "e");

    value = value.replaceAll("w", "do");
    value = value.replaceAll("W", "w");

    value = value.replaceAll("Y", "YYYY");
    value = value.replaceAll("o", "Y");
    value = value.replaceAll("y", "YY");


    value = value.replaceAll("h", "hh");
    value = value.replaceAll("g", "h");
    value = value.replaceAll("H", "HH");
    value = value.replaceAll("G", "H");

    value = value.replaceAll("i", "mm");
    value = value.replaceAll("s", "ss");
    value = value.replaceAll("T", "z");
    return value;
}

function checkCellsOptionsValidate(selection, parametor, value) {
    let returnVal = false;
    if (typeof arguments[3] !== 'undefined') {
        let j = arguments[3];
        for (let i = 0; i < j.length; i++) {
            if (typeof j[i][parametor] !== 'undefined' && j[i][parametor] == value) {
                return true;
            }
        }
        return returnVal;
    } else if (selection.length > 0) {
        eventToCellsSelected(
            selection,
            table_function_data.selectionSize,
            (ranger, i) => {
                for (let i2 = ranger[0]; i2 <= ranger[2]; i2++) {
                    for (let j = ranger[1]; j <= ranger[3]; j++) {
                        if (typeof Wptm.style.cells[i2 + '!' + j] !== 'undefined' && typeof Wptm.style.cells[i2 + '!' + j][2] !== 'undefined') {
                            if (typeof Wptm.style.cells[i2 + '!' + j][2][parametor] !== 'undefined' && Wptm.style.cells[i2 + '!' + j][2][parametor] == value) {
                                returnVal = true
                                return true;
                            }
                        }
                    }
                }
            },
            null,
            null,
            null
        )
        return returnVal;
    }
}

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
        },
        table: {
            decimal: Wptm.style.table.decimal_symbol,  // decimal point separator
            thousand: Wptm.style.table.thousand_symbol,  // thousands separator
            precision: Wptm.style.table.decimal_count,   // decimal places
            format: Wptm.style.table.symbol_position,
            symbol: Wptm.style.table.currency_symbol
        }
    };
}

//create string_currency_symbols, replace_unit, text_replace_unit, date_format RegExp string
function createRegExpFormatForCell(currency_symbol, date_formats, cell_function_data) {
    if (currency_symbol !== false) {
        cell_function_data.string_currency_symbols = currency_symbol.replace(/ /g, "");
        // create string reg currency symbols
        cell_function_data.replace_unit = new RegExp('[' + cell_function_data.string_currency_symbols.replace(/,/g, "|") + ']', "g");

        // create string reg have not currency symbols
        cell_function_data.text_replace_unit = '[^a-zA-Z|' + cell_function_data.string_currency_symbols.replace(/,/g, "|^") + ']';
        cell_function_data.text_replace_unit = new RegExp(cell_function_data.text_replace_unit, "g");
        cell_function_data.text_replace_unit_function = new RegExp('[^ |' + cell_function_data.string_currency_symbols.replace(/,/g, "|^") + ']', "g");
    }
    if (date_formats !== false) {
        cell_function_data.date_format = date_formats.match(/[a-zA-Z|\\]+/g);
    }
    return cell_function_data;
}

function status_notic(status, text) {
    var $ = window.jquery;
    var status_e = $('#savedInfoTable');
    if (status === 1) {
        status_e = $('#savedInfoTable');
    } else {
        status_e = $('#saveErrorTable');
    }

    var args = Array.prototype.slice.call(arguments, 1);
    if (typeof args[1] !== "undefined") {
        status_e = args[1];
    }

    status_e.html(text);
    var position = window.jquery('#setting-cells').offset();
    setTimeout(function () {
        status_e.animate({'opacity': '0.8', 'top': position.top - 35}, 500).delay(2000).animate({
            'opacity': '0',
            'top': '-45px'
        }, 1000);
    }, 1000);
}

function check_cell_readOnly_mysql(row, col, cellProperties) {
    if (row < 1) {
        cellProperties.readOnly = false;
        if (typeof Wptm.style.table.lock_columns !== 'undefined' && Wptm.style.table.lock_columns[col] != 0) {
            cellProperties.readOnly = true;
        }
    }
    if (Wptm.table_editing != '1' && row >= 1) {
        cellProperties.readOnly = true;
    }
    return cellProperties;
}

function check_cell_readOnly(row, col, prop) {
    var cellProperties = {};

    if (window.Wptm.type === 'mysql') {
        cellProperties = check_cell_readOnly_mysql(row, col, cellProperties);
    }

    if (!wptm_administrator) {
        var lock_cell_id = [], not_lock_cell = false;
        if (typeof table_function_data.protect_columns !== 'undefined'
            && typeof table_function_data.protect_columns[col] !== 'undefined') {
            //protect columns
            lock_cell_id.push(table_function_data.protect_columns[col]);
        }
        if (typeof table_function_data.protect_rows !== 'undefined'
            && typeof table_function_data.protect_rows[col] !== 'undefined') {
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
                cellProperties.readOnly = true;
            }
        }
    }

    return cellProperties;
}

function check_duplicate_ranger(value) {
    var duplicate = [];
    for (var i = 0; i < value.length; i++) {
        if (i + 1 < value.length) {
            var search = value.indexOf(value[i], i + 1);
            if (search > 0) {
                duplicate.push([i, search]);
            }
        }
    }
    return duplicate;
}

function merger_duplicate_ranger_user(duplicate, current_rangers, current_rangers_user) {
    var current_rangers1 = [];
    var current_rangers_user1 = [];
    var ranger_user_1, ranger_user_2, ranger_user_new;
    for (var i = 0; i < duplicate.length; i++) {
        ranger_user_1 = current_rangers_user[duplicate[i][1]].split('|');
        ranger_user_2 = current_rangers_user[duplicate[i][0]].split('|');
        ranger_user_new = ranger_user_1.concat(ranger_user_2.filter((item) => ranger_user_1.indexOf(item) < 0));
        current_rangers_user[duplicate[i][1]] = ranger_user_new.join('|');

        current_rangers.splice(duplicate[i][0] - i, 1);
        current_rangers1.push(duplicate[i][0]);
    }
    current_rangers_user1 = current_rangers_user.filter((value, index) => current_rangers1.indexOf(index) < 0);
    return [current_rangers, current_rangers_user1];
}

function create_ranger_cells_lock(Wptm, table_function_data, update_rangers) {
    var lock_ranger_cells_user;
    // table_function_data.ranger_cells_lock_top = '';
    // table_function_data.ranger_cells_lock_left = '';
    // table_function_data.ranger_cells_lock_right = '';
    // table_function_data.ranger_cells_lock_bottom = '';
    table_function_data.lock_ranger_cells_user = [];
    table_function_data.protect_columns = [];
    table_function_data.protect_rows = [];
    // table_function_data.render_first_cell_protect_has_icon = [];

    if (typeof Wptm.style.table.lock_ranger_cells_user == 'undefined') {
        Wptm.style.table.lock_ranger_cells_user = [];
    }

    var ij, enable_lock, value;
    if (typeof Wptm.style.table.lock_ranger_cells === 'undefined' || Wptm.style.table.lock_ranger_cells.length < 1) {
        return false;
    }

    if (update_rangers !== null) {
        var current_rangers = [];
        var current_ranger_cells_user = [];
    }

    var i;

    for (i = 0; i < Wptm.style.table.lock_ranger_cells.length; i++) {
        // table_function_data.render_first_cell_protect_has_icon[i] = true;
        enable_lock = false;
        table_function_data.lock_ranger_cells_user[i] = false;
        if (typeof Wptm.style.table.lock_ranger_cells_user[i] !== 'undefined') {
            lock_ranger_cells_user = Wptm.style.table.lock_ranger_cells_user[i].split('|');
            if (lock_ranger_cells_user.indexOf(wptm_userRoles) !== -1) {//ok edit
                table_function_data.lock_ranger_cells_user[i] = true;
            } else if (!wptm_administrator) {
                enable_lock = true;
            }
        }

        value = Wptm.style.table.lock_ranger_cells[i].replace(/[ ]+/g, "").toUpperCase();
        var arrayRange = value.split(":");

        var check_value = 0;
        if (arrayRange[0].replace(/[ |A-Za-z]+/g, "") === '') {
            check_value = 1;//value A:B
        } else if (arrayRange[0].replace(/[ |1-9]+/g, "") === '') {
            check_value = 2;//value 8:9
        }

        var selection = [];
        if (check_value === 0) {
            if (typeof arrayRange[1] == 'undefined') {
                arrayRange[1] = arrayRange[0];
            }
            selection.push(parseInt(arrayRange[0].split(/[ |A-Za-z]+/g)[1]) - 1);
            selection.push(convertStringToNumber(arrayRange[0].split(/[ |1-9]+/g)[0]) - 1);
            selection.push(parseInt(arrayRange[1].split(/[ |A-Za-z]+/g)[1]) - 1);
            selection.push(convertStringToNumber(arrayRange[1].split(/[ |1-9]+/g)[0]) - 1);

            if (update_rangers !== null) {
                if (update_rangers.type === 'create_row') {
                    if (selection[0] >= update_rangers.index) {//rows >= index
                        if (selection[2] >= update_rangers.amount + update_rangers.index) {//index + amount <= after_col
                            selection[2] = selection[2] + update_rangers.amount;
                            selection[0] = update_rangers.amount + update_rangers.index >= selection[0] ? selection[0] : (selection[0] + update_rangers.amount);
                        } else {//index + amount > after_col
                            selection[2] = selection[2] + update_rangers.amount;
                        }
                    } else if (selection[0] < update_rangers.index && selection[2] >= update_rangers.index) {//row < index < after_row
                        selection[2] = selection[2] + update_rangers.amount;
                    }
                }
                if (update_rangers.type === 'delete_row') {
                    if (selection[0] >= update_rangers.index) {//rows >= index
                        if (update_rangers.amount + update_rangers.index >= selection[2]) {//remove all index + amount >= after_rows
                            continue;
                        }
                        if ((update_rangers.amount + update_rangers.index) >= selection[0]
                            && (update_rangers.amount + update_rangers.index) < selection[2]) {//index <= rows <= index + amount < after_rows
                            selection[0] = update_rangers.index;
                            selection[2] = selection[2] - update_rangers.amount;
                        } else {//index + amount < rows
                            selection[0] = selection[0] - update_rangers.amount;
                            selection[2] = selection[2] - update_rangers.amount;
                        }
                    } else if (selection[0] < update_rangers.index && selection[2] >= update_rangers.index) {//rows < index < after_rows
                        selection[2] = update_rangers.amount + update_rangers.index >= selection[2] ? (update_rangers.index - 1) : (selection[2] - update_rangers.amount);
                    }
                }
                if (update_rangers.type === 'add_col') {
                    if (selection[1] > update_rangers.index) {//col >= index
                        if (selection[3] >= update_rangers.amount + update_rangers.index) {//index + amount <= after_col
                            selection[3] = selection[3] + update_rangers.amount;
                            selection[1] = update_rangers.amount + update_rangers.index >= selection[1] ? selection[1] : (selection[1] + update_rangers.amount);
                        } else {//index + amount > after_col
                            selection[3] = selection[3] + update_rangers.amount;
                        }
                    } else if (selection[1] < update_rangers.index && selection[3] >= update_rangers.index) {//row < index < after_row
                        selection[3] = selection[3] + update_rangers.amount;
                    }
                }
                if (update_rangers.type === 'delete_col') {
                    if (selection[1] >= update_rangers.index) {//col >= index
                        if (update_rangers.amount + update_rangers.index >= selection[3]) {//remove all index + amount >= after_col
                            continue;
                        }
                        if ((update_rangers.amount + update_rangers.index) >= selection[1]
                            && (update_rangers.amount + update_rangers.index) < selection[3]) {//index <= col <= index + amount < after_col
                            selection[1] = update_rangers.index;
                            selection[3] = selection[3] - update_rangers.amount;
                        } else {//index + amount < col
                            selection[1] = selection[1] - update_rangers.amount;
                            selection[3] = selection[3] - update_rangers.amount;
                        }
                    } else if (selection[1] < update_rangers.index && selection[3] >= update_rangers.index) {//col < index < after_col
                        selection[3] = update_rangers.amount + update_rangers.index >= selection[3] ? (update_rangers.index - 1) : (selection[3] - update_rangers.amount);
                    }
                }

                current_rangers.push(getSelectedVal(selection));
                if (typeof Wptm.style.table.lock_ranger_cells_user[i] !== 'undefined') {
                    current_ranger_cells_user.push(Wptm.style.table.lock_ranger_cells_user[i]);
                }
            }

            for (ij = selection[0]; ij <= selection[2]; ij++) {//row
                for (var ij2 = selection[1]; ij2 <= selection[3]; ij2++) {
                    if (typeof Wptm.style.cells[ij + '!' + ij2] !== 'undefined') {
                        Wptm.style.cells[ij + '!' + ij2][2] = window.jquery.extend(Wptm.style.cells[ij + '!' + ij2][2], {lock_cell: i});
                    }
                }
            }
        } else if (check_value === 1) {//columns
            selection.push(convertStringToNumber(arrayRange[0].split(/[ |1-9]+/g)[0]) - 1);
            selection.push(convertStringToNumber(arrayRange[1].split(/[ |1-9]+/g)[0]) - 1);

            if (update_rangers !== null) {
                if (update_rangers.type === 'add_col') {
                    if (selection[0] >= update_rangers.index) {//col >= index
                        selection[1] = selection[1] + update_rangers.amount;
                        selection[0] = update_rangers.amount + update_rangers.index >= selection[0] ? selection[0] : (selection[0] + update_rangers.amount);
                    }
                    if (selection[0] < update_rangers.index && selection[1] >= update_rangers.index) {//row < index < after_row
                        selection[1] = selection[1] + update_rangers.amount;
                    }
                }
                if (update_rangers.type === 'delete_col') {
                    if (selection[0] >= update_rangers.index) {//col >= index
                        if (update_rangers.amount + update_rangers.index >= selection[1]) {//remove all
                            continue;
                        }
                        if (update_rangers.amount + update_rangers.index < selection[0]) {
                            selection[0] = selection[0] - update_rangers.amount;
                            selection[1] = selection[1] - update_rangers.amount;
                        } else {
                            selection[0] = update_rangers.index;
                            selection[1] = selection[1] - update_rangers.amount - update_rangers.index;
                        }
                    }
                    if (selection[0] < update_rangers.index && selection[1] >= update_rangers.index) {//col < index < after_col
                        selection[1] = update_rangers.amount + update_rangers.index >= selection[1] ? (update_rangers.index - 1) : (selection[1] - update_rangers.amount);
                    }
                }

                current_rangers.push(getSelectedVal([false, selection[0], false, selection[1]]));
                if (typeof Wptm.style.table.lock_ranger_cells_user[i] !== 'undefined') {
                    current_ranger_cells_user.push(Wptm.style.table.lock_ranger_cells_user[i]);
                }
            }

            for (ij = selection[0]; ij <= selection[1]; ij++) {
                table_function_data.protect_columns[ij] = i;
            }
        } else {//rows
            selection.push(parseInt(arrayRange[0].split(/[ |A-Za-z]+/g)[0]) - 1);
            selection.push(parseInt(arrayRange[1].split(/[ |A-Za-z]+/g)[0]) - 1);

            if (update_rangers !== null) {
                if (update_rangers.type === 'create_row') {
                    if (selection[0] >= update_rangers.index) {//col >= index
                        selection[1] = selection[1] + update_rangers.amount;
                        selection[0] = update_rangers.amount + update_rangers.index >= selection[0] ? selection[0] : (selection[0] + update_rangers.amount);
                    }
                    if (selection[0] < update_rangers.index && selection[1] >= update_rangers.index) {//row < index < after_row
                        selection[1] = selection[1] + update_rangers.amount;
                    }
                }
                if (update_rangers.type === 'delete_row') {
                    if (selection[0] >= update_rangers.index) {//col >= index
                        if (update_rangers.amount + update_rangers.index >= selection[1]) {//remove all
                            continue;
                        }
                        if (update_rangers.amount + update_rangers.index < selection[0]) {
                            selection[0] = selection[0] - update_rangers.amount;
                            selection[1] = selection[1] - update_rangers.amount;
                        } else {
                            selection[0] = update_rangers.index;
                            selection[1] = selection[1] - update_rangers.amount - update_rangers.index;
                        }
                    }
                    if (selection[0] < update_rangers.index && selection[1] >= update_rangers.index) {//col < index < after_col
                        selection[1] = update_rangers.amount + update_rangers.index >= selection[1] ? (update_rangers.index - 1) : (selection[1] - update_rangers.amount);
                    }
                }

                current_rangers.push(getSelectedVal([selection[0], false, selection[1], false]));
                if (typeof Wptm.style.table.lock_ranger_cells_user[i] !== 'undefined') {
                    current_ranger_cells_user.push(Wptm.style.table.lock_ranger_cells_user[i]);
                }
            }

            for (ij = selection[0]; ij <= selection[1]; ij++) {
                table_function_data.protect_rows[ij] = i;
            }
        }
    }
    ;

    if (update_rangers !== null) {
        Wptm.style.table.lock_ranger_cells = jquery.extend([], current_rangers);
        Wptm.style.table.lock_ranger_cells_user = jquery.extend([], current_ranger_cells_user);
        // render_css_lock(Wptm, table_function_data);
    }
}

function render_css_lock(Wptm, table_function_data) {
    var column_i;
    var css_lock_columns = '';
    var $wptm_add_css_lock_columns = document.getElementById('wptm_add_css_lock_columns');

    // if (typeof table_function_data.ranger_cells_lock_top !== 'undefined' && table_function_data.ranger_cells_lock_top !== '') {
    //     css_lock_columns += table_function_data.ranger_cells_lock_top + '{border-top: 1px solid #4b89ff !important;}'
    // }
    // if (typeof table_function_data.ranger_cells_lock_bottom !== 'undefined' && table_function_data.ranger_cells_lock_bottom !== '') {
    //     css_lock_columns += table_function_data.ranger_cells_lock_bottom + '{border-bottom: 1px solid #4b89ff !important;}'
    // }
    // if (typeof table_function_data.ranger_cells_lock_left !== 'undefined' && table_function_data.ranger_cells_lock_left !== '') {
    //     css_lock_columns += table_function_data.ranger_cells_lock_left + '{border-left: 1px solid #4b89ff !important;}'
    // }
    // if (typeof table_function_data.ranger_cells_lock_right !== 'undefined' && table_function_data.ranger_cells_lock_right !== '') {
    //     css_lock_columns += table_function_data.ranger_cells_lock_right + '{border-right: 1px solid #4b89ff !important;}'
    // }

    if (typeof Wptm.style.table.lock_columns !== 'undefined') {
        for (column_i in Wptm.style.table.lock_columns) {
            if (Wptm.style.table.lock_columns[column_i] != 0) {
                column_i = parseInt(column_i);
                css_lock_columns += ' .handsontable table thead th:nth-child(' + (column_i + 2) + ') {position: relative;} ';
                css_lock_columns += ' .handsontable table thead th:nth-child(' + (column_i + 2) + '):after {content: "lock";} ';
            }
        }
    }


    setTimeout(function () {
        if (css_lock_columns !== '') {
            $wptm_add_css_lock_columns.innerHTML = css_lock_columns;
            $wptm_add_css_lock_columns.cssText = css_lock_columns;
        }
    }, 100);
}

function check_collide_two_range(selection1, selection2) {
    if ((Math.abs(selection1[0] - selection2[0]) < Math.abs(selection1[2] - selection1[0] + selection2[2] - selection2[0]) / 2)
        && (Math.abs(selection1[1] - selection2[1]) < Math.abs(selection1[3] - selection1[1] + selection2[3] - selection2[1]) / 2)
    ) {
        return true;
    }
    return false;
}

function input_not_negative(value, name, min, max) {
    var input_not_negative = ['cell_font_size', 'col_width', 'row_height', 'table_height'];
    if (input_not_negative.includes(name)) {
        if ((min !== null && value < min) || (max !== null && value > max)) {
            return true;
        }
    }
    return false;
}

function wptmBootbox(headrText, bodyText, doneButton, cancelButton, submitCallFunction, cancelCallFunction, type) {
    var popup = {
        'html': wptm_element.content_popup_hide.find('#wptm_bootbox'),
        'showAction': function () {
            if (headrText !== '') {
                this.find('.popup_top span').text(headrText);
            } else {
                this.find('.popup_top').hide();
            }
            if (bodyText !== '') {
                this.find('.control-group span').html(bodyText);
            } else {
                this.find('.control-group').hide();
            }
            if (doneButton === false) {
                this.find('#popup_done').hide();
            } else if (typeof doneButton === 'string') {
                this.find('#popup_done').val(doneButton);
            }
            if (cancelButton === false) {
                this.find('#popup_cancel').hide();
            } else if (typeof cancelButton === 'string') {
                this.find('#popup_cancel').val(cancelButton);
            }
            if (typeof type !== 'undefined') {
                switch (type) {
                    case 'warning':
                        this.find('.control-group').css({'line-height': 1})
                        this.find('.control-group span').css({'font-weight': 300})
                        break
                    default:
                        break
                }
            }
            return true;
        },
        'submitAction': function () {
            if (typeof submitCallFunction !== 'undefined' && submitCallFunction !== null) {
                submitCallFunction.call();
            }
            wptm_element.wptm_popup.animate({'opacity': '0'}, 10);
            setTimeout(function () {
                wptm_element.wptm_popup.hide();
                wptm_element.wptm_popup.siblings('#over_popup').hide();
                return true;
            }, 200);
        },
        'cancelAction': function () {
            if (typeof cancelCallFunction !== 'undefined' && cancelCallFunction !== null) {
                cancelCallFunction.call();
            }
            return true;
        }
    };
    selectOption.wptm_popup(wptm_element.wptm_popup, popup, true, false, false);
}

function WptmLoading(that, stop, start, timeOut, text = null, status = '') {
    that.addClass('show').attr('data-status', status);
    var c = start;
    var $counter = that.find(".counter h5");
    var $hr = that.find(".counter hr");
    if (text) {
        $counter.text(text);
    }

    var loadingInterval = setInterval(() => {
        if (!text && status === '') {
            $counter.text(c + "%");
        }
        $hr.css("width", c + "%");
        start++;
        c++;

        if (start >= stop) {
            clearInterval(loadingInterval);
        }
    }, timeOut);
}

function wptmSetZoom(zoom, reset) {
    if (zoom) {
        zoom = parseFloat(zoom)
    } else {
        return false
    }

    const elements = [
        ['.handsontableInputHolder', '', '', 1],
        ['.ht_master', '', '', '.wtHider'],
        ['.ht_clone_top', 'ht_clone_top', '', '.wtHider'],
        ['.manualColumnResizer', '', '', 1],
        ['.ht_clone_left', '', 'ht_clone_left', '.wtHider'],
        ['.manualRowResizer', '', '', 1],
        ['.ht_clone_top_left_corner', 'ht_clone_top_left_corner', 'ht_clone_top_left_corner', '.wtHider']
    ]

    var p = ["webkit", "moz", "ms", "o"],
        content = '';
    const zoomsize = zoom / (table_function_data.zoom || 1)

    // console.group()
    if (zoomsize !== 1) {
        jquery.each(elements, (i, v) => {
            let $parent_element = wptm_element.tableContainer.find(v[0])

            if ($parent_element.length > 0) {
                // console.log(v, $parent_element.outerHeight(), $parent_element.outerWidth())

                if (v[1] !== '') {
                    content += '#tableContainer .' + v[1] + '{height: ' + (zoomsize * $parent_element.outerHeight()) + 'px !important;}'
                    if ($parent_element.find('.wtHolder'))
                        content += '#tableContainer .' + v[1] + ' .wtHolder {height: ' + (zoomsize * $parent_element.outerHeight()) + 'px !important;}'
                }
                if (v[2] !== '') {
                    content += '#tableContainer .' + v[2] + '{width: ' + (zoomsize * $parent_element.outerWidth()) + 'px !important;}'
                    if ($parent_element.find('.wtHolder'))
                        content += '#tableContainer .' + v[2] + ' .wtHolder {width: ' + (zoomsize * $parent_element.outerWidth()) + 'px !important;}'
                }
            }

            if (v[3] !== '') {
                let $wtHider = v[3] !== 1 ? $parent_element.find(v[3]) : $parent_element
                content += '#tableContainer ' + v[0] + (v[3] !== 1 ? (' ' + v[3]) : '') + ' {transform-origin: 0% 0%;}'
                content += '#tableContainer ' + v[0] + (v[3] !== 1 ? (' ' + v[3]) : '') + ' {transform: scale(' + zoom + ');}'

                for (var i1 = 0; i1 < p.length; i1++) {
                    if ($wtHider.length > 0) {
                        $wtHider[0].style[p[i1] + "Transform"] = "scale(" + zoom + ")";
                        $wtHider[0].style[p[i1] + "TransformOrigin"] = '0% 0%';
                    }
                }
            }
        })
    }

    // console.log(zoom, zoomsize)
    // console.groupEnd()
    if (content !== '') {
        var parser = new (less.Parser)
        parser.parse(content, function (err, tree) {
            if (err) {
                //Here we can throw the erro to the user
                return false;
            } else {
                if (jquery('head').find('.wptmHeadCssZoom').length < 1)
                    jquery('head').append('<style class="wptmHeadCssZoom"></style>');

                if (reset === true) {
                    jquery('.wptmHeadCssZoom').text('')
                } else {
                    jquery('.wptmHeadCssZoom').text(tree.toCSS())
                }
            }
        });
    }

    if (zoom)
        table_function_data.zoom = zoom
}

function unsafeRedactName(text, name) {
    return text.replace(new RegExp(name, "g"), ",");
}

function wptmCollapsible() {
    jquery('.wptmCollapsible').unbind('click').on('click', function() {
        this.classList.toggle("active");
        var content = this.nextElementSibling;
        if (jquery(content).hasClass('wptm_show')) {
            jquery(content).animate({'opacity': '0', height: "hide"}, 1000, function() {
                jquery(this).removeClass('wptm_show')
            })
        } else {
            jquery(content).animate({'opacity': '1', height: "show"}, 1000, function() {
                jquery(this).addClass('wptm_show')
            })
        }
    });
}

function wptm_tooltip(that) {
    jquery(that).find('.wptm_tooltip').each(function (i, e) {
        let content = jquery(e).data("tooltip_text")
            ? jquery(e).data("tooltip_text")
            : (jquery(e).data("tooltip_img") ? '<img src="' + jquery(e).data("tooltip_img") + '"/>' : '')
        jquery(e).tipso({
            useTitle: false,
            tooltipHover: true,
            background: "#000000",
            color: "#ffffff",
            offsetY: 0,
            width: 150,
            content: '<span class="wptm_tooltipcontent">' + content + '</span>',
            onShow: function (ele, tipso, obj) {
                //calculate top tipso_bubble when set width
                var size = realHeight(obj.tooltip());
                jquery(obj.tipso_bubble[0]).css(
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
        jquery("body").append(clone);
        var height = clone.outerHeight();
        var width = clone.outerWidth();
        clone.remove();
        return {
            width: width,
            height: height,
        };
    }
}

/*col = [start, end], row = [start, end], ranger = [seelctions[0]]*/
function getRangercells(col, row, ranger = null) {
    let min, max
    if (ranger !== null) {

    } else {

    }


    return [min, max]
}

export default {
    eventToCellsSelected,
    unsafeRedactName,
    setText,
    updateSwitchButtonFromStyleObject,
    updateParamFromStyleObject,
    updateParamFromStyleObjectSelectBox,
    checkObjPropertyNested,
    calculateTableHeight,
    convertStringToNumber,
    getSelectedVal,
    checkObjPropertyNestedNotEmpty,
    autosaveNotification,
    cleanHandsontable,
    warning_edit_db_table,
    saveChanges,
    cell_type_to_column,
    deleteRowDbTable,
    createRowDbTable,
    validate_type_cell,
    change_value_cells,
    default_sortable,
    convertAlpha,
    convertDate,
    formatSymbols,
    getSizeCells,
    changeValue,
    pullDims,
    pushDims,
    getFillArray,
    fillArray,
    removeValueInArray,
    responsive_col,
    createRegExpFormat,
    replaceCell,
    loading,
    rloading,
    wptm_clone,
    parseCss,
    getCellData,
    stringReplace,
    showChartOrTable,
    ripple_button,
    combineChangedCellIntoRow,
    isSameArray,
    mergeCollsRowsstyleToCells,
    setFormat_accounting,
    updateStyleAutofill,
    setPositionForHtmlCellEditor,
    hashFnv32a,
    copy_text,
    getLinkSync,
    momentjsFormat,
    setFormat_accounting_for_cells,
    createRegExpFormatForCell,
    checkCellsOptionsValidate,
    status_notic,
    check_cell_readOnly,
    check_duplicate_ranger,
    merger_duplicate_ranger_user,
    create_ranger_cells_lock,
    check_collide_two_range,
    render_css_lock,
    input_not_negative,
    droptablesCheckSize,
    wptmBootbox,
    WptmLoading,
    wptmSetZoom,
    wptmCollapsible,
    wptm_tooltip
}