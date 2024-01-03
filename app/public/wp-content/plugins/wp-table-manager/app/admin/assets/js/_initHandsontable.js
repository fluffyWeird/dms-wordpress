//setTimeout change height table by rows height
import tableFunction from "./_functions";
import selectOption from "./_toolbarOptions";
import customRenderer from "./_customRenderer";
import DropChart from "./_chart";

/**
 * Call handsontable lib
 *
 * @param datas Wptm.datas
 */
function initHandsontable(datas) {
    var checkScroll = false;
    var autoScroll;
    window.Wptm.container = window.wptm_element.tableContainer;
    var modifyRow = null;
    var totalRows = datas.length;
    var copyHeightRow = false;

    window.Wptm.container.handsontable({
        data: datas,
        startRows: 5,
        startCols: 5,
        editor: window.CustomEditor,
        search: true,
        renderAllRows: false,
        fillHandle: ((window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author)) && window.Wptm.type === 'html') ? {
            autoInsertRow:false,
        } : false,
        modifyAutofillRange: function (startArea, entireArea) {//entireArea is start ranger
            var directionFill = -1;//-1 is not fill, 0 is top, 1 is bottom, 2 is left, 3 is right
            var new_range = [startArea[0], startArea[1], startArea[2], startArea[3]];

            if (startArea[0] !== entireArea[0]) {
                directionFill = 0;
                new_range[2] = entireArea[0] - 1;
            } else if (startArea[2] !== entireArea[2]) {
                directionFill = 1;
                new_range[0] = entireArea[2] + 1;
            } else if (startArea[1] !== entireArea[1]) {
                directionFill = 2;
                new_range[3] = entireArea[1] - 1;
            } else if (startArea[3] !== entireArea[3]) {
                directionFill = 3;
                new_range[1] = entireArea[3] + 1;
            }

            if (directionFill > -1) {
                //update cell type, hyperlink
                tableFunction.updateStyleAutofill(entireArea, new_range, directionFill);
            }
        },
        copyPaste: true,
        beforePaste: (data, coords) => {
            tableFunction.saveChanges(true)
            //vcl, asynchronous
        },
        beforeCopy: (data, coords) => {
            var selection = table_function_data.selection[0];
            table_function_data.copyData = [];
            table_function_data.copyWidthCols = [];
            table_function_data.copyTypeCols = [];
            table_function_data.copyHeightRows = [];
            table_function_data.copyMergeCells = [];
            table_function_data.copyDataSelection = [selection[2] - selection[0], selection[3] - selection[1]];//[row, col]
            table_function_data.copyHeightRow = [];
            table_function_data.createCellWhenCopy = [];
            jquery.map(window.Wptm.style.rows, function (v) {
                table_function_data.copyHeightRow.push(v[1].height);
            });

            var i2 = 0, j2;
            for (var i = selection[0]; i <= selection[2]; i++) {//row
                j2 = 0;
                for (var j = selection[1]; j <= selection[3]; j++) {
                    if (typeof Wptm.style.cells[i + '!' + j] !== 'undefined') {
                        table_function_data.copyData[i2 + '!' + j2] = window.jquery.extend({}, Wptm.style.cells[i + '!' + j][2]);

                        if (typeof Wptm.newSelect !== 'undefined') {
                            if (Wptm.newSelect === 'row' && typeof Wptm.style.rows[i][1] !== 'undefined') {
                                table_function_data.copyHeightRows[i2 + '!'] = Wptm.style.rows[i][1].height;
                            }
                            if (Wptm.newSelect === 'col' && typeof Wptm.style.cols[j][1] !== 'undefined') {
                                table_function_data.copyWidthCols[j2 + '!'] = Wptm.style.cols[j][1].width;
                                table_function_data.copyTypeCols[j2 + '!'] = Wptm.style.table.col_types[j];
                            }
                        }

                        delete table_function_data.copyData[i2 + '!' + j2].AlternateColor;
                        delete table_function_data.copyData[i2 + '!' + j2].lock_cell;
                        delete table_function_data.copyData[i2 + '!' + j2].width;
                        delete table_function_data.copyData[i2 + '!' + j2].height;
                    }
                    j2++;
                }
                i2++;
            }

            //check merge cells
            jquery.map(Wptm.mergeCellsSetting, function (v) {
                if (v.col >= selection[1] && v.row >= selection[0]) {
                    if ((v.col + v.colspan - 1) <= selection[3] && (v.row + v.rowspan - 1) <= selection[2]) {
                        table_function_data.copyMergeCells[(v.row - selection[0]) + '!' + (v.col - selection[1])] = [v.rowspan - 1, v.colspan - 1];
                    }
                }
            });
        },
        afterCopy: function (data, coords) {
        },
        afterPaste: function (data, coords) {
        },
        colHeaders: true,
        rowHeaders: true,
        autoRowSize: false,
        autoColSize: false,
        renderer: customRenderer.render,
        // beforeOnCellMouseDown: function() {//event mouse down
        // },
        height: tableFunction.calculateTableHeight(window.jquery('#wptm-toolbars')),
        afterInit: function () {
            DropChart.functions.loadCharts();
            Wptm.container.handsontable("selectCell", 0, 0, 0, 0);
            window.table_function_data.firstRender = true;

            tableFunction.render_css_lock(Wptm, table_function_data);
        },
        beforeRender: function (isForced) {
            Wptm.style.table.fonts_used = window.jquery.extend([], []);
            Wptm.style.table.fonts_local_used = window.jquery.extend([], []);
            window.table_function_data.styleToRender = '';
        },
        beforeChange: function (changes, source) {
            if (source === 'auto_convert_data') {
                return;
            }
            var paste_start = [changes[0][0], changes[0][1]];
            var paste_x = 0, paste_y = 0, before_next_row;
            copyHeightRow = false;
            var updateSettings = {};
            var cols_selected = [];
            var number_cols = changes.length / (changes[changes.length - 1][0] - changes[0][0] + 1);
            var mergeCellsSetting = jquery.extend([], window.Wptm.mergeCellsSetting);

            for (var i = 0; i < changes.length; i++) {
                before_next_row = false;
                if ((i + 1) % number_cols === 0) {
                    before_next_row = true;
                }
                var optionsCell = changes[i];

                //paste style options cells
                if (source === 'CopyPaste.paste' && typeof table_function_data.copyDataSelection !== "undefined" && table_function_data.copyDataSelection[0] >= 0) {
                    // optionsCell
                    if (optionsCell[0] - paste_start[0] > table_function_data.copyDataSelection[0]) {//row
                        paste_y = 0;
                    }
                    if (optionsCell[1] - paste_start[1] > table_function_data.copyDataSelection[1]) {//col
                        paste_x = 0;
                    }
                    if (typeof (window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]]) === 'undefined') {
                        window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]] = [optionsCell[0], optionsCell[1], {}];
                        table_function_data.createCellWhenCopy[optionsCell[0] + '!' + optionsCell[1]] = optionsCell[0] + '!' + optionsCell[1];
                    }
                    if (table_function_data.copyData[paste_y + '!' + paste_x].cell_type === 'html') {
                        setTimeout((optionsCell) => {
                            saveData.push({action: 'set_cells_type', selection: [[optionsCell[0], optionsCell[1], optionsCell[0], optionsCell[1]]], style: {cell_type: 'html'}});
                        }, 300, optionsCell);
                    } else {
                        if (window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2].cell_type === 'html') {
                            setTimeout((optionsCell) => {
                                saveData.push({action: 'set_cells_type', selection: [[optionsCell[0], optionsCell[1], optionsCell[0], optionsCell[1]]], style: {cell_type: null}});
                            }, 300, optionsCell);
                        }
                    }
                    // window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2] = window.jquery.extend({}, window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2], table_function_data.copyData[paste_y + '!' + paste_x]);
                    let cellParams = {}
                    if (typeof window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]] !== 'undefined' && typeof window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2].AlternateColor !== "undefined")
                        cellParams.AlternateColor = window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2].AlternateColor;

                    window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2] = window.jquery.extend({}, cellParams, table_function_data.copyData[paste_y + '!' + paste_x]);

                    if (typeof table_function_data.copyHeightRows !== 'undefined' && table_function_data.copyHeightRows[paste_y + '!'] > 0) {
                        if (typeof (window.Wptm.style.rows[optionsCell[0]]) === 'undefined') {
                            window.Wptm.style.rows[optionsCell[0]] = [optionsCell[0], {}];
                        }
                        window.Wptm.style.rows[optionsCell[0]][1].height = table_function_data.copyHeightRows[paste_y + '!'];
                        table_function_data.copyHeightRow[optionsCell[0]] = table_function_data.copyHeightRows[paste_y + '!'];
                        copyHeightRow = true;
                    }
                    if (typeof table_function_data.copyWidthCols !== 'undefined' && table_function_data.copyWidthCols[paste_x + '!'] > 0) {
                        if (typeof (window.Wptm.style.cols[optionsCell[1]]) === 'undefined') {
                            window.Wptm.style.cols[optionsCell[1]] = [optionsCell[1], {}];
                            window.Wptm.style.cols[optionsCell[1]][1].res_priority = 0;
                        }

                        window.Wptm.style.cols[optionsCell[1]][1].width = table_function_data.copyWidthCols[paste_x + '!'];
                    }

                    if (typeof table_function_data.copyTypeCols !== 'undefined' && table_function_data.copyTypeCols[paste_x + '!']) {
                        Wptm.style.table.col_types[optionsCell[1]] = 'text';
                        cols_selected[optionsCell[1]] = 'text';
                    }

                    if (typeof table_function_data.copyMergeCells !== 'undefined' && typeof table_function_data.copyMergeCells[paste_y + '!' + paste_x] !== 'undefined') {
                        mergeCellsSetting.push({
                            col: optionsCell[1],
                            colspan: table_function_data.copyMergeCells[paste_y + '!' + paste_x][1] + 1,
                            row: optionsCell[0],
                            rowspan: table_function_data.copyMergeCells[paste_y + '!' + paste_x][0] + 1
                        });

                        updateSettings.mergeCells = mergeCellsSetting;
                    }

                    setTimeout((optionsCell, changes, i, updateSettings, copyHeightRow) => {
                        saveData.push({action: 'style',
                            selection: [[optionsCell[0], optionsCell[1], optionsCell[0], optionsCell[1]]],
                            style: window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2]
                        });

                        if (i + 1 === changes.length) {
                            if (copyHeightRow) {
                                updateSettings.manualRowResize = table_function_data.copyHeightRow;
                            }

                            if (tableFunction.droptablesCheckSize(updateSettings) > 0) {
                                Wptm.container.handsontable('updateSettings', updateSettings);
                            }
                        }
                    }, 300, optionsCell, changes, i, updateSettings, copyHeightRow);
                    paste_x++;

                    if (i + 1 === changes.length) {
                        if (tableFunction.droptablesCheckSize(cols_selected) > 0) {
                            if (saveData[0] && saveData[0].action === 'create_col_after_paste') {
                                saveData.splice(1, 0, {
                                    action: 'set_columns_types_copy',
                                    value: cols_selected
                                })
                            } else {
                                saveData.unshift({
                                    action: 'set_columns_types_copy',
                                    value: cols_selected
                                });
                            }
                        }
                    }

                    if (before_next_row) {
                        paste_y++;
                        paste_x = 0;
                    }
                }

                //check validate chart
                if (jquery('#list_chart').find('.chart-menu').length > 0) {//check chart exist
                    if (!DropChart.functions.validateCharts(optionsCell)) {
                        tableFunction.wptmBootbox('', wptmText.CHANGE_INVALID_CHART_DATA, true, false);
                        return false;
                    }
                }
            }
        },
        afterRender: function (isForced) {
            var parser = new (less.Parser);
            var Wptm = window.Wptm;
            var $ = window.jquery;
            window.table_function_data.content = '#mainTabContent .handsontable .ht_master .htCore {' + window.table_function_data.styleToRender + '}';
            if (Wptm.style.table.responsive_type == 'scroll' && Wptm.style.table.freeze_row) {
                window.table_function_data.content += ' #mainTabContent .handsontable .ht_clone_top .htCore {' + window.table_function_data.styleToRender + '}';
            }
            if (Wptm.style.table.responsive_type == 'scroll' && Wptm.style.table.freeze_col) {
                window.table_function_data.content += ' #mainTabContent .handsontable .ht_clone_left .htCore {' + window.table_function_data.styleToRender + '}';
            }
            if (Wptm.style.table.responsive_type == 'scroll' && Wptm.style.table.freeze_row && Wptm.style.table.freeze_col) {
                window.table_function_data.content += ' #mainTabContent .handsontable .ht_clone_corner .htCore {' + window.table_function_data.styleToRender + '}';
            }

            // await parser.parse(window.table_function_data.content, function (err, tree) {
            parser.parse(window.table_function_data.content, function (err, tree) {
                if (err) {
                    //Here we can throw the error to the user
                    return false;
                } else {
                    Wptm.css = $('#jform_css').val();
                    if ($('#headMainCss').length === 0) {
                        $('head').append('<style id="headMainCss"></style>');
                    }
                    $('#headMainCss').text(tree.toCSS());
                    return true;
                }
            });

            if ($('#content_popup_hide').find('select.select_columns option').length < 1) {
                // Build column selection for default sort parameter
                tableFunction.default_sortable(window.Wptm.datas);
            }
            //set Wptm.style.rows , Wptm.style.cols value
            tableFunction.pushDims($, Wptm);

            if (table_function_data.needSaveAfterRender === true) {
                tableFunction.saveChanges();
                table_function_data.needSaveAfterRender = false;
            }

            Wptm.style.table.class_name && $('#tableContainer').addClass(Wptm.style.table.class_name);
        },
        afterChange: function (change, source) {//play when change content cell
            table_function_data.copyWidthCols = [];
            if (source === 'CopyPaste.paste') {
                table_function_data.createCellWhenCopy = [];
                table_function_data.copyHeightRows = [];
                table_function_data.copyHeightRow = [];
            }

            if (typeof table_function_data.data_argument !== 'undefined' && source === 'loadData') {
                source = 'edit';
                change = jquery.extend([], table_function_data.data_argument);
            }

            //fix handsontable merge cells remove data of cells
            if ((source === 'MergeCells' || source === 'populateFromArray')&& typeof change.length !== 'undefined') {
                for (i = 0; i < change.length; i++) {
                    if (change[i][2] !== null && change[i][3] === null) {
                        Wptm.datas[change[i][0]][change[i][1]] = change[i][2];
                    }
                }
            }

            if (source === 'populateFromArray' && change.length > 0 && change[0][2] === change[0][3]) {
                tableFunction.cleanHandsontable();
            }

            if (source === 'loadData' || source === 'populateFromArray' || !(window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author))) {
                return; //don't save this change
            }
            var action = ['CopyPaste.paste', 'edit', 'UndoRedo.undo', 'UndoRedo.redo', 'Autofill.fill', 'setDataAtCell'];

            //validate data cells when mergeCells
            if (change) {
                var i;
                var notSaveData;
                var editHeader = false;
                var mysql_column_edit, mysql_column_key;

                for (i = 0; i < change.length; i++) {
                    if (action.includes(source)) {
                        notSaveData = true;
                        if (typeof change[i][3] === 'undefined' || change[i][3] === 'wptm_change_value_after_set_columns_types') {
                            change[i][3] = change[i][2];//loadData when edit cells > 5ommit
                            notSaveData = false;
                        }
                        if (change[i][3] === change[i][4]) {
                            notSaveData = false;
                        }

                        var optionsCell = change[i];

                        if (typeof Wptm.headerOption == 'undefined' || optionsCell[0] >= Wptm.headerOption) {//check cell in table header
                            if (typeof optionsCell[3] !== 'undefined' && optionsCell[3] !== null) {
                                var value = tableFunction.cell_type_to_column(optionsCell, optionsCell[3]);
                                var value_change;
                                if (value === false) {
                                    wptm_element.mainTabContent.find('td.dtr' + optionsCell[0] + '.dtc' + optionsCell[1]).addClass('invalid_data');
                                    tableFunction.wptmBootbox('', wptmText.CHANGE_INVALID_CELL_DATA, true, false);
                                    return;
                                } else {
                                    wptm_element.mainTabContent.find('td.dtr' + optionsCell[0] + '.dtc' + optionsCell[1]).removeClass('invalid_data');
                                    if (value !== true) {
                                        if (notSaveData) {
                                            if (Wptm.type !== 'mysql') {
                                                if (window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]]
                                                    && window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2]
                                                    && window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2].cell_type === 'html')
                                                    saveData.push({action: 'edit_cell_html', row: optionsCell[0], col: optionsCell[1], content: value});
                                                else
                                                    saveData.push({action: 'edit_cell', row: optionsCell[0], col: optionsCell[1], content: value});
                                            } else if ( Wptm.query_option.column_options !== null && typeof Wptm.query_option.column_options[optionsCell[1]] !== "undefined") {
                                                if (optionsCell[2] !== value) {
                                                    mysql_column_edit = Wptm.query_option.column_options[optionsCell[1]].table + '.' + Wptm.query_option.column_options[optionsCell[1]].Field;
                                                    mysql_column_key = Wptm.query_option.column_options[table_function_data.keyPosition].table + '.' + Wptm.query_option.column_options[table_function_data.keyPosition].Field;
                                                    value_change = Wptm.datas[optionsCell[0]][table_function_data.keyPosition];
                                                    Wptm.saveDataDbTable.push({
                                                        action: 'edit_cell_mysql',
                                                        row: optionsCell[0],
                                                        col: optionsCell[1],
                                                        content: value,
                                                        id: Wptm.datas[optionsCell[0]][table_function_data.keyPosition],
                                                        column: mysql_column_edit,
                                                        column_key: mysql_column_key
                                                    });
                                                }
                                            }
                                            if (Wptm.headerOption > 0 && optionsCell[0] < Wptm.headerOption) {
                                                Wptm.style.table.header_data[optionsCell[0]][optionsCell[1]] = value;
                                            }
                                        }
                                        if (value !== optionsCell[3]) {//has convert cell value
                                            window.Wptm.container.handsontable('setDataAtCell', optionsCell[0], optionsCell[1], value, 'auto_convert_data');
                                        }
                                    } else {
                                        if (notSaveData) {
                                            if (Wptm.type !== 'mysql') {
                                                if (window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]]
                                                    && window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2]
                                                    && window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2].cell_type === 'html')
                                                    saveData.push({action: 'edit_cell_html', row: optionsCell[0], col: optionsCell[1], content: optionsCell[3]});
                                                else
                                                    saveData.push({action: 'edit_cell', row: optionsCell[0], col: optionsCell[1], content: optionsCell[3]});
                                            } else if ( Wptm.query_option.column_options !== null && typeof Wptm.query_option.column_options[optionsCell[1]] !== "undefined")  {
                                                if (optionsCell[2] !== optionsCell[3]) {
                                                    mysql_column_edit = Wptm.query_option.column_options[optionsCell[1]].table + '.' + Wptm.query_option.column_options[optionsCell[1]].Field;
                                                    mysql_column_key = Wptm.query_option.column_options[table_function_data.keyPosition].table + '.' + Wptm.query_option.column_options[table_function_data.keyPosition].Field;
                                                    value_change = Wptm.datas[optionsCell[0]][table_function_data.keyPosition];
                                                    Wptm.saveDataDbTable.push({
                                                        action: 'edit_cell_mysql',
                                                        row: optionsCell[0],
                                                        col: optionsCell[1],
                                                        content: optionsCell[3],
                                                        id: Wptm.datas[optionsCell[0]][table_function_data.keyPosition],
                                                        column: mysql_column_edit,
                                                        column_key: mysql_column_key
                                                    });
                                                }
                                            }
                                            if (Wptm.headerOption > 0 && optionsCell[0] < Wptm.headerOption) {
                                                Wptm.style.table.header_data[optionsCell[0]][optionsCell[1]] = optionsCell[3];
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            editHeader = true;
                            if (notSaveData) {
                                if (Wptm.type === 'mysql' && Wptm.query_option.column_options !== null && typeof Wptm.query_option.column_options[optionsCell[1]] !== "undefined") {
                                    if (optionsCell[2] !== optionsCell[3]) {
                                        mysql_column_edit = Wptm.query_option.column_options[optionsCell[1]].table + '.' + Wptm.query_option.column_options[optionsCell[1]].Field;
                                        mysql_column_key = Wptm.query_option.column_options[table_function_data.keyPosition].table + '.' + Wptm.query_option.column_options[table_function_data.keyPosition].Field;
                                        value_change = Wptm.datas[optionsCell[0]][table_function_data.keyPosition];
                                        Wptm.saveDataDbTable.push({
                                            action: 'edit_cell_mysql',
                                            row: optionsCell[0],
                                            col: optionsCell[1],
                                            content: optionsCell[3],
                                            id: Wptm.datas[optionsCell[0]][table_function_data.keyPosition],
                                            column: mysql_column_edit,
                                            column_key: mysql_column_key
                                        });
                                    }
                                } else {
                                    if (window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]]
                                        && window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2]
                                        && window.Wptm.style.cells[optionsCell[0] + '!' + optionsCell[1]][2].cell_type === 'html')
                                        saveData.push({action: 'edit_cell_html', row: optionsCell[0], col: optionsCell[1], content: optionsCell[3]});
                                    else
                                        saveData.push({action: 'edit_cell', row: optionsCell[0], col: optionsCell[1], content: optionsCell[3]});
                                }
                                if (Wptm.headerOption > 0 && optionsCell[0] < Wptm.headerOption) {
                                    Wptm.style.table.header_data[optionsCell[0]][optionsCell[1]] = optionsCell[3];
                                }
                            }
                        }
                    }
                }
            }

            // clearTimeout(tableFunction.autosaveNotification);

            //change[0][2] & change[0][3] === undefined when edit html cell
            if (action.includes(source) && typeof change[0] !== 'undefined' && !(typeof change[0][2] === 'undefined' && typeof change[0][3] === 'undefined')) {
                table_function_data.needSaveAfterRender = (change[0][2] !== change[0][3] || source ==='setDataAtCell') ? true : false;
            }

            if (change && Wptm.type === 'mysql' && notSaveData && Wptm.saveDataDbTable.length > 0) {//mysql table edited
                if (typeof value !== 'undefined' || editHeader) {
                    saveData = saveData.concat(Wptm.saveDataDbTable);
                    wptm_element.mainTabContent.addClass('loading_ajax');
                    Wptm.saveDataDbTable = [];
                    tableFunction.saveChanges(true);
                }
                table_function_data.needSaveAfterRender = false;
            }

            //update merge cells when autofill
            if ('Autofill.fill' === source) {
                tableFunction.cleanHandsontable();
                updateMergeCells(true);
            } else {
                setTimeout(function () {
                    if (jquery(Wptm.container).handsontable('getInstance').undoRedo.isUndoAvailable()) {
                        wptm_element.primary_toolbars.find('#undo_cell').addClass('active').removeClass('no_active');
                    } else {
                        wptm_element.primary_toolbars.find('#undo_cell').removeClass('active').addClass('no_active');
                    }
                    if (jquery(Wptm.container).handsontable('getInstance').undoRedo.isRedoAvailable()) {
                        wptm_element.primary_toolbars.find('#redo_cell').addClass('active').removeClass('no_active');
                    } else {
                        wptm_element.primary_toolbars.find('#redo_cell').removeClass('active').addClass('no_active');
                    }
                }, 100);
            }

            if (table_function_data.needSaveAfterRender === true) {
                tableFunction.saveChanges();
                table_function_data.needSaveAfterRender = false;
            }
        },
        afterCreateRow: async function (index, amount) {
        // afterCreateRow: function (index, amount) {
            var selector = table_function_data.selection[table_function_data.selectionSize - 1];
            var $ = window.jquery;
            var Wptm = window.Wptm;
            var above = selector[0] === index ? true : false;//check above/below
            /*fix create many rows when copy many lines. add create_row action before edit_cell action(in afterChange)*/
            saveData.push({action: 'create_row', index: index, amount: amount, above: above});

            var changing_style_cell = await changeCellAfterCreateRow(index, amount, selector, above)

            //remove tooltip in new cells created
            await tableFunction.getFillArray([[index, 0, index + amount - 1, Wptm.max_Col - 1]], Wptm, {tooltip_content: null});

            if (typeof (Wptm.style.rows) !== 'undefined') {
                Wptm.style.rows = await tableFunction.changeValue(Wptm.style.rows, index, amount, Wptm.max_row, selector[0] === index, copyHeightRow);
                // Wptm.style.rows = tableFunction.changeValue(Wptm.style.rows, index, amount, Wptm.max_row, selector[0] === index, copyHeightRow);
            }

            if (parseInt(Wptm.headerOption) > 0) {
                delete Wptm.style.table.header_data;
                Wptm.style.table.header_data = [];
                for (var j = 0; j < Wptm.headerOption; j++) {
                    Wptm.style.table.header_data[j] = Wptm.datas[j];
                }
            }

            // update merged row index and get tableFunction.saveChanges();
            // setTimeout(function () {
            await updateMergeCells(window.table_function_data.firstRender);
                // updateMergeCells(window.table_function_data.firstRender);
            // }, 100);
            // await tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'create_row', index: index, amount: amount});
            tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'create_row', index: index, amount: amount});
            tableFunction.cleanHandsontable();
        },
        afterRemoveRow: function (index, amount) {
            var selector = table_function_data.selection[table_function_data.selectionSize - 1];
            var $ = jquery;
            var Wptm = window.Wptm;
            if (typeof (Wptm.style.cells) !== 'undefined') {
                var newCells = {};
                var cell, i, cells;

                for (cell in Wptm.style.cells) {
                    cells = Wptm.style.cells[cell];
                    if (cells[0] > index + amount - 1) {
                        newCells[parseInt(cells[0] - amount) + '!' + cells[1]]
                            = [cells[0] - amount, cells[1], $.extend({}, cells[2])];
                    }
                    if (cells[0] < index) {
                        newCells[cell]
                            = [cells[0], cells[1], $.extend({}, cells[2])];
                    }
                }
                Wptm.style.cells = $.extend({}, newCells);

                if (typeof Wptm.style.table.alternateColorValue !== 'undefined') {//update Wptm.style.table.alternateColorValue by index, amount
                    var alternateColorValue = Wptm.style.table.alternateColorValue;
                    for (i in alternateColorValue) {
                        if (alternateColorValue[i] !== false) {
                            if (alternateColorValue[i].selection[2] >= index && alternateColorValue[i].selection[2] <= (index + amount - 1)) {
                                alternateColorValue[i].selection[2] = index - 1;//selection[2] in selector
                            }
                            if (alternateColorValue[i].selection[2] >= index + amount) {
                                alternateColorValue[i].selection[2] = alternateColorValue[i].selection[2] - amount;//selection[2] > selector
                            }
                            if (alternateColorValue[i].selection[0] >= index && alternateColorValue[i].selection[0] < (index + amount)) {
                                alternateColorValue[i].selection[0] = index;//selection[0] in selector
                            }
                            if (alternateColorValue[i].selection[0] >= index + amount) {
                                alternateColorValue[i].selection[0] = alternateColorValue[i].selection[0] - amount;//selection[0] > selector
                            }
                            if (alternateColorValue[i].selection[0] > alternateColorValue[i].selection[2]) {//alternateColor in selector
                                delete alternateColorValue[i];
                            }
                        }
                    }
                }
            }

            if (typeof (Wptm.style.cols) !== 'undefined') {
                var new_data = $.extend({}, Wptm.style.rows);
                var max = _.size(Wptm.style.rows);
                var jj;
                for (jj = index; jj < max; jj++) {
                    if (jj < index + amount) {
                        delete new_data[jj];
                    }
                    if (typeof (new_data[jj + amount]) !== 'undefined' && new_data[jj + amount] !== null) {
                        new_data[jj] = [jj, {}];
                        new_data[jj][1] = $.extend({}, new_data[jj + amount][1]);
                    }
                    if (jj + amount >= max) {
                        delete new_data[jj];
                    }
                }
                Wptm.style.rows = $.extend({}, new_data);
            }

            //remove col in header table
            if (parseInt(Wptm.headerOption) > 0) {
                delete Wptm.style.table.header_data;
                Wptm.style.table.header_data = [];
                for (var j = 0; j < Wptm.headerOption; j++) {
                    Wptm.style.table.header_data[j] = Wptm.datas[j];
                }
            }

            saveData.push({action: 'del_row', index: index, amount: amount, old_rows: $(window.Wptm.container).handsontable('countRows') + amount});
            // update merged row index and get tableFunction.saveChanges();

            setTimeout(function () {
                updateMergeCells(window.table_function_data.firstRender);
            }, 100);
            tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'delete_row', index: index, amount: amount});

            tableFunction.cleanHandsontable();
        },
        afterCreateCol: function (index, amount) {
            var selector = table_function_data.selection[table_function_data.selectionSize - 1];
            var $ = jquery;
            var Wptm = window.Wptm;
            var left = selector[1] === index ? true : false;//check insert left/right
            var createColsWhenPaste = false;

            if (typeof (Wptm.style.cells) !== 'undefined') {
                var newCells = {};
                var cell, i, cells;

                for (cell in Wptm.style.cells) {
                    cells = Wptm.style.cells[cell];
                    if (cells[1] >= index) {//cols >= index
                        if (typeof table_function_data.createCellWhenCopy !== 'undefined' && typeof table_function_data.createCellWhenCopy[cell] !== 'undefined') {
                            newCells[cell] = $.extend([], cells);
                            createColsWhenPaste = true;
                        } else {
                            newCells[cells[0] + '!' + parseInt(cells[1] + amount)]
                                = [cells[0], cells[1] + amount, $.extend({}, cells[2])];
                        }
                    }
                    if (cells[1] < index) {//cols < index
                        newCells[cell]
                            = [cells[0], cells[1], $.extend({}, cells[2])];
                    }
                    if (cells[1] <= selector[3] && selector[1] <= cells[1]) {//new cols copy style of selector
                        if (typeof table_function_data.createCellWhenCopy !== 'undefined' && typeof table_function_data.createCellWhenCopy[cell] !== 'undefined') {
                            newCells[cell] = $.extend([], cells);
                            createColsWhenPaste = true;
                        } else {
                            newCells[cells[0] + '!' + parseInt(cells[1] - selector[1] + index)]
                                = [cells[0], parseInt(cells[1] - selector[1] + index), $.extend({}, cells[2])];
                        }
                    }
                }
                Wptm.style.cells = $.extend({}, newCells);

                if (typeof Wptm.style.table.alternateColorValue !== 'undefined') {//update Wptm.style.table.alternateColorValue by index, amount
                    var alternateColorValue = Wptm.style.table.alternateColorValue;
                    for (i in alternateColorValue) {
                        if (alternateColorValue[i] !== false) {
                            if (left) {//insert left
                                if (alternateColorValue[i].selection[1] >= index) { // alternateColorValue > new rows
                                    alternateColorValue[i].selection[1] = alternateColorValue[i].selection[1] + amount;
                                    alternateColorValue[i].selection[3] = alternateColorValue[i].selection[3] + amount;
                                }
                                if (alternateColorValue[i].selection[1] < index && alternateColorValue[i].selection[3] >= index) {
                                    alternateColorValue[i].selection[3] = alternateColorValue[i].selection[3] + amount;
                                }
                            } else {//insert right
                                if (alternateColorValue[i].selection[1] >= index) { // alternateColorValue > new rows
                                    alternateColorValue[i].selection[1] = alternateColorValue[i].selection[1] + amount;
                                    alternateColorValue[i].selection[3] = alternateColorValue[i].selection[3] + amount;
                                }
                                if (alternateColorValue[i].selection[1] < index && alternateColorValue[i].selection[3] >= index) {
                                    alternateColorValue[i].selection[3] = alternateColorValue[i].selection[3] + amount;
                                }
                            }
                        }
                    }
                }
            }

            if (createColsWhenPaste) {
                if (typeof table_function_data.create_col_after_paste === 'undefined') {
                    table_function_data.create_col_after_paste = []
                }
                table_function_data.create_col_after_paste.push({action: 'create_col_after_paste', index: index, amount: amount, left: left});
            } else {
                saveData.push({action: 'create_col', index: index, amount: amount, left: left});
            }
            //remove tooltip in new cells created
            tableFunction.getFillArray([[0, index, Wptm.max_row - 1, index + amount - 1]], Wptm, {tooltip_content: null});

            if (typeof (Wptm.style.cols) !== 'undefined') {
                // index, amount
                var new_data = $.extend({}, Wptm.style.cols);
                var jj;
                if (selector[1] === index) {//left
                    for (jj = index; jj < tableFunction.droptablesCheckSize(Wptm.style.cols); jj++) {
                        new_data[jj + amount] = [jj + amount, {}];
                        new_data[jj + amount][1] = $.extend({}, Wptm.style.cols[jj][1]);
                    }
                } else {
                    for (jj = index; jj < tableFunction.droptablesCheckSize(Wptm.style.cols) + amount; jj++) {
                        // if (typeof new_data[jj] === 'undefined') {
                            new_data[jj] = [jj, {}];
                            new_data[jj][1] = $.extend({}, Wptm.style.cols[jj - amount][1]);
                        // }
                    }
                }

                Wptm.style.cols = $.extend({}, new_data);
            }

            for (var i = index; i <= (index + amount - 1) ; i++) {
                if (createColsWhenPaste) {
                    Wptm.style.table.col_types.splice(i, 0, 'text');
                } else {
                    Wptm.style.table.col_types.splice(i, 0, 'varchar');
                }
            }

            if (parseInt(Wptm.headerOption) > 0) {
                delete Wptm.style.table.header_data;
                Wptm.style.table.header_data = [];
                for (var j = 0; j < Wptm.headerOption; j++) {
                    Wptm.style.table.header_data[j] = Wptm.datas[j];
                }
            }

            // Build column selection for default sort parameter
            tableFunction.default_sortable(window.Wptm.datas);


            window.jquery(window.Wptm.container).handsontable('render');

            // update merged row index and get tableFunction.saveChanges();
            setTimeout(function () {
                updateMergeCells(window.table_function_data.firstRender);
            }, 100);
            tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'add_col', index: index, amount: amount});

            tableFunction.cleanHandsontable();
        },
        afterRemoveCol: function (index, amount) {
            var selector = table_function_data.selection[table_function_data.selectionSize - 1];
            var $ = jquery;
            var Wptm = window.Wptm;
            if (typeof (Wptm.style.cells) !== 'undefined') {
                var newCells = {};
                var cell, i, cells;

                for (cell in Wptm.style.cells) {
                    cells = Wptm.style.cells[cell];
                    if (cells[1] > index + amount - 1) {
                        newCells[cells[0] + '!' + parseInt(cells[1] - amount)]
                            = [cells[0], cells[1] - amount, $.extend({}, cells[2])];
                    }
                    if (cells[1] < index) {
                        newCells[cell]
                            = [cells[0], cells[1], $.extend({}, cells[2])];
                    }
                }
                Wptm.style.cells = $.extend({}, newCells);

                if (typeof Wptm.style.table.alternateColorValue !== 'undefined') {//update Wptm.style.table.alternateColorValue by index, amount
                    var alternateColorValue = Wptm.style.table.alternateColorValue;
                    for (i in alternateColorValue) {
                        if (alternateColorValue[i] !== false) {
                            if (alternateColorValue[i].selection[3] >= index && alternateColorValue[i].selection[3] <= (index + amount - 1)) {
                                alternateColorValue[i].selection[3] = index - 1;//selection[3] in selector
                            }
                            if (alternateColorValue[i].selection[3] >= index + amount) {
                                alternateColorValue[i].selection[3] = alternateColorValue[i].selection[3] - amount;//selection[3] > selector
                            }
                            if (alternateColorValue[i].selection[1] >= index && alternateColorValue[i].selection[1] < (index + amount)) {
                                alternateColorValue[i].selection[1] = index;//selection[0] in selector
                            }
                            if (alternateColorValue[i].selection[1] >= index + amount) {
                                alternateColorValue[i].selection[1] = alternateColorValue[i].selection[1] - amount;//selection[0] > selector
                            }
                            if (alternateColorValue[i].selection[1] > alternateColorValue[i].selection[3]) {//alternateColor in selector
                                delete alternateColorValue[i];
                            }
                        }
                    }
                }
            }

            for (var i = (index + amount - 1); i >= index ; i--) {
                Wptm.style.table.col_types.splice(i, 1);
            }
            if (typeof (Wptm.style.cols) !== 'undefined') {
                var new_data = $.extend({}, Wptm.style.cols);
                var max = _.size(Wptm.style.cols);
                var jj;
                for (jj = index; jj < max; jj++) {
                    if (jj < index + amount) {
                        delete new_data[jj];
                    }
                    if (typeof (new_data[jj + amount]) !== 'undefined' && new_data[jj + amount] !== null) {
                        new_data[jj] = [jj, {}];
                        new_data[jj][1] = $.extend({}, new_data[jj + amount][1]);
                    }
                    if (jj + amount >= max) {
                        delete new_data[jj];
                    }
                }
                Wptm.style.cols = $.extend({}, new_data);
            }

            //remove col in header table
            if (parseInt(Wptm.headerOption) > 0) {
                delete Wptm.style.table.header_data;
                Wptm.style.table.header_data = [];
                for (var j = 0; j < Wptm.headerOption; j++) {
                    Wptm.style.table.header_data[j] = Wptm.datas[j];
                }
            }

            // Build column selection for default sort parameter
            tableFunction.default_sortable(window.Wptm.datas);
            jquery(Wptm.container).data('handsontable').render();
            saveData.push({action: 'del_col', index: index, amount: amount, old_columns: $(window.Wptm.container).handsontable('countCols') + amount});

            setTimeout(function () {
                updateMergeCells(window.table_function_data.firstRender);
            }, 100);
            tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'delete_col', index: index, amount: amount});

            tableFunction.cleanHandsontable();
        },
        afterColumnResize: function (col, width) {
            tableFunction.saveChanges();
        },
        beforeRowResize: function (currentRow, newSize, isDoubleClick) {
            if (modifyRow !== null) {//currentRow is current size row
                Wptm.style.rows[modifyRow][1].height = newSize;
            }
        },
        modifyRow: function (row) {
            modifyRow = row;
        },
        afterRowResize: function (row1, height) {
            Wptm.style.rows[row1][1].height = height;

            tableFunction.saveChanges();
        },
        // currentRowClassName: 'wptmCurrentRow',
        afterSelectionByProp: (row, column, row2, column2, preventScrolling, selectionLayerLevel) => {
            preventScrolling.value = true;//turn off scroll when selection cell
            // console.log('afterSelectionByProp')
        },
        // afterDrawSelection: (currentRow, currentColumn, cornersOfSelection, layerLevel) => {
            // console.log(currentRow, currentColumn, cornersOfSelection, layerLevel)
            // return 'cell_selected'
        // },
        afterSelection: function (r, c, r2, c2, preventScrolling, selectionLayerLevel) {
            wptm_element.handsontableInputHolder = wptm_element.tableContainer.find('.handsontableInputHolder');
            // console.log('afterSelection')
            preventScrolling.value = true;
            if ((table_function_data.zoom || 1) != 1) {
                tableFunction.wptmSetZoom(table_function_data.zoom || 1, false)

                wptm_element.handsontableInputHolder.addClass('wptm_hiden')
            }

            if (table_function_data.checkCellValueChange !== false && typeof table_function_data.cellValueChange !== 'undefined') {
                tableFunction.change_value_cells(table_function_data.checkCellValueChange, wptm_element.cellValue.val());
            }
            selectOption.loadSelection(window.jquery, window.Wptm, [[r, c, r2, c2]]);
            Wptm.max_Col = Wptm.datas[0].length;
            Wptm.max_row = Wptm.datas.length;
            if (typeof Wptm.datas[Wptm.max_row - 1] !== 'undefined') {
                var max_col_2 = Wptm.datas[Wptm.max_row - 1].length;
                Wptm.max_Col = Wptm.max_Col < max_col_2 ? max_col_2 : Wptm.max_Col;
            }
            if (r * r2 == 0 && (r2 + r) == (Wptm.max_row - 1)) {
                Wptm.newSelect = 'col';
            } else if (c * c2 == 0 && (c2 + c) == Wptm.max_Col - 1) {
                Wptm.newSelect = 'row';
            } else {
                delete Wptm.newSelect;
            }
        },
        afterBeginEditing: (row, column) => {
            if ((table_function_data.zoom || 1) != 1) {
                checkScroll = true;
                clearTimeout(autoScroll);
                autoScroll = setTimeout(function () {
                    checkScroll = afterScrollEditors(checkScroll);
                }, 50);
                // console.log('afterBeginEditing')
            }
        },
        afterMomentumScroll: function () {//Fired after a scroll event, which is identified as a momentum scroll (e.g. on an iPad).
            if ((table_function_data.zoom || 1) != 1) {
                // wptm_element.handsontableInputHolder.addClass('wptm_hiden')
                checkScroll = true;
                clearTimeout(autoScroll);
                autoScroll = setTimeout(function () {
                    checkScroll = afterScrollEditors(checkScroll);
                }, 50);
            }
        },
        afterScrollHorizontally: function () {
            //change position of Editors when ScrollHorizontally
            if ((table_function_data.zoom || 1) != 1) {
                wptm_element.handsontableInputHolder.addClass('wptm_hiden')
                checkScroll = true;
                clearTimeout(autoScroll);
                autoScroll = setTimeout(function () {
                    checkScroll = afterScrollEditors(checkScroll);
                }, 50);
            }
        },
        afterScrollVertically: function () {
            //change position of Editors when ScrollHorizontally
            if ((table_function_data.zoom || 1) != 1) {
                // wptm_element.handsontableInputHolder.addClass('wptm_hiden')
                checkScroll = true;
                clearTimeout(autoScroll);
                autoScroll = setTimeout(function () {
                    checkScroll = afterScrollEditors(checkScroll);
                }, 50);
            }
        },
        afterMergeCells: function (cellRange, mergeParent, auto) {
            updateMergeCells(window.table_function_data.firstRender);
            // if (window.table_function_data.firstRender) {
            //     tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'mergeCells'});
            // }
        },
        afterUnmergeCells: function (cellRange, auto) {
            updateMergeCells(window.table_function_data.firstRender);
            // if (window.table_function_data.firstRender) {
            //     tableFunction.create_ranger_cells_lock(Wptm, table_function_data, {type: 'mergeCells'});
            // }
        },
        colWidths: function (index) {
            if (tableFunction.checkObjPropertyNested(window.Wptm.style, 'cols', index, 1, 'width')) {
                return window.Wptm.style.cols[index][1].width;
            } else if (typeof window.Wptm.style.cols === 'object'
                && (typeof window.Wptm.style.cols[index] === 'undefined' || typeof window.Wptm.style.cols[index][1].width === 'undefined')) {
                return 100;
            }
        },
        rowHeights: function (index) {
            if (tableFunction.checkObjPropertyNestedNotEmpty(window.Wptm.style, 'rows', index, 1, 'height')) {
                return window.Wptm.style.rows[index][1].height;
            } else {
                // Table rows is large than 1000, set default row height to 30
                if (totalRows >= 1000) {
                    return 30;
                }
                var h;
                if (typeof Wptm.style.table.allRowHeight !== 'undefined' && Wptm.style.table.allRowHeight !== '') {
                    h = Wptm.style.table.allRowHeight;
                } else {
                    h = window.Wptm.container.find('.ht_master .htCore tbody tr').eq(index).height();
                }
                return h;
            }
        },
        fixedRowsTop: (window.Wptm.style.table.responsive_type == 'scroll' && parseInt(window.Wptm.style.table.freeze_row) > 0) ? window.Wptm.headerOption : 0,
        fixedColumnsLeft: (window.Wptm.style.table.responsive_type == 'scroll') ? parseInt(window.Wptm.style.table.freeze_col) : 0,
        manualColumnResize: (window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author)),
        manualRowResize: (window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author)),
        outsideClickDeselects: false,
        columnSorting: false,
        // columnSorting: true,
        // filters: true,
        // dropdownMenu: true,
        undo: true,
        mergeCells: window.Wptm.mergeCellsSetting,
        readOnly: ((window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author)) && window.Wptm.type === 'html') ? false : (Wptm.table_editing != "1" ? true : false),
        // readOnly: true,
        // selectionMode: table_function_data.mysqlEdit ? 'single' : 'range',
        selectionMode: 'multiple',
        columns: typeof table_function_data.columns !== 'undefined' ? table_function_data.columns : undefined,
        // columns: undefined,
        beforeKeyDown: function (e) {
            var evtobj = window.event? event : e;

            if (evtobj.keyCode == 90 && (evtobj.ctrlKey || evtobj.metaKey)) {//undo
                if (!evtobj.shiftKey) {
                    if (!window.jquery(Wptm.container).handsontable('getInstance').undoRedo.isUndoAvailable()) {
                        tableFunction.status_notic(1, wptmText.some_action_cant_be_done + '!', jquery('#undoNotic'));
                    }
                }
                if (evtobj.shiftKey) {
                    if (!window.jquery(Wptm.container).handsontable('getInstance').undoRedo.isRedoAvailable()) {
                        tableFunction.status_notic(1, wptmText.some_action_cant_be_done + '!', jquery('#undoNotic'));
                    }
                }
            }
        },
        // readOnlyCellClassName: 'wptm_lock_column', //ncc error conflict header, conflict html cell
        cells: function(row, col, prop) {
            return tableFunction.check_cell_readOnly(row, col, prop);
        },
        contextMenu: (((window.Wptm.can.edit || (window.Wptm.can.editown && data.author === window.Wptm.author)) && window.Wptm.type === 'html')
            ? {
                items: {
                    "cut": {
                        name: wptmContext.cut,
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "copy": {
                        name: wptmContext.copy,
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        },
                    },
                    // "paste": {
                    //     key: 'paste',
                    //     name: 'Paste',
                    //     disabled: function() {
                    //         return clipboardCache.length === 0;
                    //     },
                    //     callback: function() {
                    //         var plugin = this.getPlugin('copyPaste');
                    //
                    //         this.listen();
                    //         plugin.paste(clipboardCache);
                    //     }
                    // },
                    "remove": {
                        name: wptmContext.remove,
                        key: 'remove',
                        submenu: {
                            items: [
                                {
                                    key: "remove:remove_row",
                                    name: wptmContext.remove_rows,
                                    callback: function (key, selection, clickEvent) {
                                        var selection = table_function_data.selection;
                                        var i;
                                        for (i = 0; i < table_function_data.selectionSize; i++) {
                                            if (selection[i][2] != null || selection[i][0] != selection[i][2]) {
                                                window.Wptm.container.handsontable('alter', 'remove_row', selection[i][0], selection[i][2] - selection[i][0] + 1);
                                            } else {
                                                window.Wptm.container.handsontable('alter', 'remove_row', selection[i][0]);
                                            }
                                        }
                                    },
                                },
                                {
                                    key: "remove:remove_col",
                                    name: wptmContext.remove_cols,
                                    callback: function (key, options) {
                                        var selection = table_function_data.selection;
                                        var i;
                                        for (i = 0; i < table_function_data.selectionSize; i++) {
                                            if (selection[i][3] != null || selection[i][1] != selection[i][3]) {
                                                window.Wptm.container.handsontable('alter', 'remove_col', selection[i][1], selection[i][3] - selection[i][1] + 1);
                                            } else {
                                                window.Wptm.container.handsontable('alter', 'remove_col', selection[i][1]);
                                            }
                                        }
                                    },
                                },
                                {
                                    key: "remove:remove_cell_format",
                                    name: wptmContext.remove_cell_format,
                                    callback: function (key, selection, clickEvent) {
                                        tableFunction.getFillArray(table_function_data.selection, Wptm, {
                                            date_formats: false,
                                            date_formats_momentjs: false,
                                            currency_symbol: false,
                                            symbol_position: false,
                                            decimal_symbol: false,
                                            decimal_count: false,
                                            thousand_symbol: false
                                        }, 'style', true, function () {
                                            window.jquery(wptm_element.tableContainer).handsontable('render');
                                        });
                                    },
                                    hidden: function () {//hiden when has html cell
                                        if (table_function_data.selectionSize > 0) {
                                            if (!tableFunction.checkCellsOptionsValidate(table_function_data.selection, 'cell_type', 'html')) {
                                                return false;
                                            }
                                        }
                                        return true;
                                    }
                                },
                                {
                                    key: "remove:remove_alternating_color",
                                    // name: function () {
                                    //     return (table_function_data.selectionSize > 2
                                    //         || (table_function_data.selectionSize < 2 && (table_function_data.selection[0][2] > table_function_data.selection[0][0] + 1) || (table_function_data.selection[0][3] > table_function_data.selection[0][1] + 1)))
                                    //         ? wptmContext.remove_alternating_color_cells
                                    //         : wptmContext.remove_alternating_color;
                                    // },
                                    name: wptmContext.remove_alternating_color,
                                    callback: function (key, selection, clickEvent) {
                                        var i, ij, ik, alternateColorCell, selection_cell = null;
                                        for (i = 0; i < table_function_data.selectionSize; i++) {
                                            if (selection_cell !== null) {
                                                break;
                                            }
                                            for (ij = table_function_data.selection[i][0]; ij <= table_function_data.selection[i][2]; ij++) {
                                                if (selection_cell !== null) {
                                                    break;
                                                }
                                                for (ik = table_function_data.selection[i][1]; ik <= table_function_data.selection[i][3]; ik++) {
                                                    if (typeof Wptm.style.cells[ij + "!" + ik] !== 'undefined' && typeof Wptm.style.cells[ij + "!" + ik][2].AlternateColor !== 'undefined') {
                                                        alternateColorCell = parseInt(Wptm.style.cells[ij + "!" + ik][2].AlternateColor);
                                                        if (typeof Wptm.style.table.alternateColorValue[alternateColorCell] !== 'undefined' && typeof Wptm.style.table.alternateColorValue[alternateColorCell].selection !== 'undefined') {
                                                            selection_cell = Wptm.style.table.alternateColorValue[alternateColorCell].selection;
                                                            Wptm.container.handsontable("selectCell", selection_cell[0], selection_cell[1], selection_cell[2], selection_cell[3]);
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if (selection_cell !== null) {
                                            tableFunction.wptmBootbox('', wptmText.warning_remove_alternating_color, true, true, () => {
                                                table_function_data.oldAlternate[alternateColorCell] = false;
                                                Wptm.style.table.alternateColorValue[alternateColorCell] = false;
                                                setTimeout(function () {
                                                    saveData.push({action: 'deleteStyle', selection: table_function_data.selection, style: 'AlternateColor'});
                                                    tableFunction.saveChanges(true, 'render');
                                                }, 100);
                                            });
                                        }
                                    },
                                    hidden: function () {//hiden when has html cell
                                        if (table_function_data.selectionSize > 0) {
                                            return false;
                                        }
                                        return true;
                                    }
                                }],
                        },
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "---------": {},
                    "rows_size": {
                        name: function () {
                            if (typeof Wptm.newSelect !== 'undefined') {
                                var selectSize = table_function_data.selection[table_function_data.selectionSize - 1];
                                if (Wptm.newSelect === 'row') {
                                    if (selectSize[0] !== selectSize[2]) {
                                        return '<span>' + wptmContext.define + 's ' + (selectSize[0] + 1) + '-' + (selectSize[2] + 1) + '</span>';
                                    }
                                    return '<span>' + wptmContext.define + '</span>';
                                } else {
                                    if (selectSize[1] !== selectSize[3]) {
                                        return '<span>' + wptmContext.define + 's ' + String.fromCharCode(65 + selectSize[1]) + '-' + String.fromCharCode(65 + selectSize[3]) + '</span>';
                                    }
                                    return '<span>' + wptmContext.defineColumn + '</span>';
                                }
                            }
                        },
                        callback: function (key, selection, clickEvent) {
                            if (Wptm.newSelect === 'row') {
                                wptm_element.primary_toolbars.find('.table_option[name="resize_row"]').trigger('click');
                            } else {
                                wptm_element.primary_toolbars.find('.table_option[name="resize_column"]').trigger('click');
                            }
                            return true;
                        },
                        hidden: function () {
                            if (typeof Wptm.newSelect !== 'undefined' && Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "insert": {
                        name: wptmContext.insert,
                        key: 'insert',
                        submenu: {
                            items: [
                                {
                                    key: "insert:row_above",
                                    name: wptmContext.insert_above,
                                    callback: function (key, options) {
                                        var selection = table_function_data.selection[table_function_data.selectionSize - 1];
                                        if (selection[2] != null || selection[0] != selection[2]) {
                                            window.Wptm.container.handsontable('alter', 'insert_row', selection[0], selection[2] - selection[0] + 1);
                                        } else {
                                            window.Wptm.container.handsontable('alter', 'insert_row', selection[0]);
                                        }
                                    },
                                    hidden: function () {
                                        if (Wptm.max_Col > 0) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    key: "insert:row_below",
                                    name: wptmContext.insert_below,
                                    callback: function (key, options) {
                                        var selection = table_function_data.selection[table_function_data.selectionSize - 1];
                                        if (selection[2] != null || selection[0] != selection[2]) {
                                            window.Wptm.container.handsontable('alter', 'insert_row', selection[2] + 1, selection[2] - selection[0] + 1);
                                        } else {
                                            window.Wptm.container.handsontable('alter', 'insert_row', selection[2] + 1);
                                        }
                                    },
                                    hidden: function () {
                                        if (Wptm.max_Col > 0) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    key: "insert:col_left",
                                    name: wptmContext.insert_left,
                                    callback: function (key, options) {
                                        var selection = table_function_data.selection[table_function_data.selectionSize - 1];
                                        if (selection[3] != null || selection[1] != selection[3]) {
                                            window.Wptm.container.handsontable('alter', 'insert_col', selection[1], selection[3] - selection[1] + 1);
                                        } else {
                                            window.Wptm.container.handsontable('alter', 'insert_col', selection[1]);
                                        }
                                    },
                                    hidden: function () {
                                        if (Wptm.max_row > 0) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    key: "insert:col_right",
                                    name: wptmContext.insert_right,
                                    callback: function (key, options) {
                                        var selection = table_function_data.selection[table_function_data.selectionSize - 1];
                                        if (selection[3] != null || selection[1] != selection[3]) {
                                            window.Wptm.container.handsontable('alter', 'insert_col', selection[3] + 1, selection[3] - selection[1] + 1);
                                        } else {
                                            window.Wptm.container.handsontable('alter', 'insert_col', selection[3] + 1);
                                        }
                                    },
                                    hidden: function () {
                                        if (Wptm.max_row > 0) {
                                            return false;
                                        }
                                        return true;
                                    }
                                }],
                        }
                    },
                    "undo": {
                        name: wptmContext.undo,
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "redo": {
                        name: wptmContext.redo,
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "protect_range": {
                        name: function () {
                            // if (typeof Wptm.newSelect !== 'undefined' && Wptm.newSelect === 'col') {
                            //     var selection = table_function_data.selection[table_function_data.selectionSize - 1];
                            //     var ij;
                            //     table_function_data.protect_columns_check = false;
                            //     if (typeof Wptm.style.table.protect_columns !== 'undefined') {
                            //         for (ij = selection[1]; ij <= selection[3]; ij++) {
                            //             if (Wptm.style.table.protect_columns[ij] != 1) {
                            //                 table_function_data.protect_columns_check = false;
                            //                 return '<span>' + wptmContext.protect_columns + '</span>';
                            //             }
                            //         }
                            //         table_function_data.protect_columns_check = true;
                            //         return '<span class="selected">' + wptmContext.protect_columns + '</span>';
                            //     } else {
                            //         Wptm.style.table.protect_columns = [];
                            //         for (ij = 0; ij < Wptm.max_Col; ij++) {
                            //             Wptm.style.table.protect_columns[ij] = '0';
                            //         }
                            //         return '<span>' + wptmContext.protect_columns + '</span>';
                            //     }
                            // }
                            return '<span>' + wptmContext.protect_range + '</span>';
                        },
                        callback: function (key, selections, clickEvent) {
                            // if (typeof Wptm.newSelect !== 'undefined' && Wptm.newSelect === 'col') {
                            //     var ij;
                            //     if (typeof Wptm.style.table.protect_columns == 'undefined') {
                            //         Wptm.style.table.protect_columns = [];
                            //         for (ij = 0; ij < Wptm.max_Col; ij++) {
                            //             Wptm.style.table.protect_columns[ij] = '0';
                            //         }
                            //     }
                            //
                            //     var selection = table_function_data.selection[table_function_data.selectionSize - 1];
                            //     for (ij = selection[1]; ij <= selection[3]; ij++) {
                            //         if (typeof table_function_data.protect_columns_check !== 'undefined'
                            //             && table_function_data.protect_columns_check) {
                            //             Wptm.style.table.protect_columns[ij] = '0';
                            //         } else {
                            //             Wptm.style.table.protect_columns[ij] = '1';
                            //         }
                            //     }
                            // } else {
                                wptm_element.primary_toolbars.find('.table_option[name="lock_ranger_cells"]').trigger('click');
                            // }
                            return true;
                        },
                        hidden: function () {
                            if (wptm_administrator == 1) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "mergeCells": {
                        // name: wptmContext.merge,
                    },
                    "Add tooltip": {
                        name: wptmContext.tooltip,
                        callback: function (key, selection, clickEvent) {
                            wptm_element.editToolTip.trigger('click');
                        },
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row < 1) {
                                return true;
                            }
                            if (table_function_data.selectionSize > 1
                                || (table_function_data.selection[0][2] - table_function_data.selection[0][0] > 0
                                    || table_function_data.selection[0][3] - table_function_data.selection[0][1] > 0)) {
                                return true;
                            }
                            return false;
                        }
                    },
                    "Insert a cell selection": {
                        name: wptmContext.insert_selection,
                        callback: function (key, selection, clickEvent) {
                            wptm_element.primary_toolbars.find('.table_option[name="cell_insertion_shortcode"]').trigger('click');
                        },
                        hidden: function () {
                            return false;
                        }
                    },
                    "Column type": {
                        name: wptmContext.column_type,
                        key: 'column_type',
                        submenu: {
                            items: [
                                {
                                    key: "column_type:varchar",
                                    name: function () {
                                        if (typeof table_function_data.type_column_selected !== 'undefined' && (table_function_data.type_column_selected === 'varchar' || table_function_data.type_column_selected.toLowerCase() === 'varchar(255)')) {
                                            return '<span class="selected">' + wptmContext.column_type_varchar + '</span>';
                                        } else {
                                            return '<span>' + wptmContext.column_type_varchar + '</span>';
                                        }
                                    },
                                    callback: function (key, options) {
                                        tableFunction.wptmBootbox('', wptmText.ALERT_CHANGE_COLUMN_TYPE, true, true, () => {
                                            var cols_selected = [];
                                            var i, jj;
                                            for (jj = 0; jj < table_function_data.selectionSize; jj++) {
                                                for (i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                                    cols_selected[i] = 'varchar';
                                                    Wptm.style.table.col_types[i] = 'varchar';
                                                }
                                            }

                                            // saveData.push({action: 'set_column_type', cols: unique_cols_selected, type: 'varchar'});
                                            saveData.push({
                                                action: 'set_columns_types',
                                                value: cols_selected
                                            });
                                            tableFunction.cleanHandsontable();
                                            tableFunction.saveChanges(true);
                                        });
                                    },
                                },
                                {
                                    key: "column_type:int",
                                    name: function () {
                                        if (typeof table_function_data.type_column_selected !== 'undefined' && table_function_data.type_column_selected === 'int') {
                                            return '<span class="selected">' + wptmContext.column_type_int + '</span>';
                                        } else {
                                            return '<span>' + wptmContext.column_type_int + '</span>';
                                        }
                                    },
                                    callback: function (key, options) {
                                        tableFunction.wptmBootbox('', wptmText.ALERT_CHANGE_COLUMN_TYPE, true, true, () => {
                                            var cols_selected = [];
                                            var i, jj;
                                            for (jj = 0; jj < table_function_data.selectionSize; jj++) {
                                                for (i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                                    cols_selected[i] = 'int';
                                                    Wptm.style.table.col_types[i] = 'int';
                                                }
                                            }

                                            // saveData.push({action: 'set_column_type', cols: unique_cols_selected, type: 'varchar'});
                                            saveData.push({
                                                action: 'set_columns_types',
                                                value: cols_selected
                                            });
                                            tableFunction.cleanHandsontable();
                                            tableFunction.saveChanges(true);
                                        });
                                    },
                                },
                                {
                                    key: "column_type:float",
                                    name: function () {
                                        if (typeof table_function_data.type_column_selected !== 'undefined' && table_function_data.type_column_selected === 'float') {
                                            return '<span class="selected">' + wptmContext.column_type_float + '</span>';
                                        } else {
                                            return '<span>' + wptmContext.column_type_float + '</span>';
                                        }
                                    },
                                    callback: function (key, options) {
                                        tableFunction.wptmBootbox('', wptmText.ALERT_CHANGE_COLUMN_TYPE, true, true, () => {
                                            var cols_selected = [];
                                            var i, jj;
                                            for (jj = 0; jj < table_function_data.selectionSize; jj++) {
                                                for (i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                                    cols_selected[i] = 'float';
                                                    Wptm.style.table.col_types[i] = 'float';
                                                }
                                            }

                                            // saveData.push({action: 'set_column_type', cols: unique_cols_selected, type: 'varchar'});
                                            saveData.push({
                                                action: 'set_columns_types',
                                                value: cols_selected
                                            });
                                            tableFunction.cleanHandsontable();
                                            tableFunction.saveChanges(true);
                                        });
                                    },
                                },
                                {
                                    key: "column_type:date",
                                    name: function () {
                                        if (typeof table_function_data.type_column_selected !== 'undefined' && table_function_data.type_column_selected === 'date') {
                                            return '<span class="selected">' + wptmContext.column_type_date + '</span>';
                                        } else {
                                            return '<span>' + wptmContext.column_type_date + '</span>';
                                        }
                                    },
                                    callback: function (key, options) {
                                        tableFunction.wptmBootbox('', wptmText.ALERT_CHANGE_COLUMN_TYPE, true, true, () => {
                                            var cols_selected = [];
                                            var i, jj;
                                            for (jj = 0; jj < table_function_data.selectionSize; jj++) {
                                                for (i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                                    cols_selected[i] = 'date';
                                                    Wptm.style.table.col_types[i] = 'date';
                                                }
                                            }

                                            // saveData.push({action: 'set_column_type', cols: unique_cols_selected, type: 'varchar'});
                                            saveData.push({
                                                action: 'set_columns_types',
                                                value: cols_selected
                                            });
                                            tableFunction.cleanHandsontable();
                                            tableFunction.saveChanges(true);
                                        });
                                    },
                                },
                                {
                                    key: "column_type:datetime",
                                    name: function () {
                                        if (typeof table_function_data.type_column_selected !== 'undefined' && table_function_data.type_column_selected === 'datetime') {
                                            return '<span class="selected">' + wptmContext.column_type_datetime + '</span>';
                                        } else {
                                            return '<span>' + wptmContext.column_type_datetime + '</span>';
                                        }
                                    },
                                    callback: function (key, options) {
                                        tableFunction.wptmBootbox('', wptmText.ALERT_CHANGE_COLUMN_TYPE, true, true, () => {
                                            var cols_selected = [];
                                            var i, jj;
                                            for (jj = 0; jj < table_function_data.selectionSize; jj++) {
                                                for (i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                                    cols_selected[i] = 'datetime';
                                                    Wptm.style.table.col_types[i] = 'datetime';
                                                }
                                            }

                                            // saveData.push({action: 'set_column_type', cols: unique_cols_selected, type: 'varchar'});
                                            saveData.push({
                                                action: 'set_columns_types',
                                                value: cols_selected
                                            });
                                            tableFunction.cleanHandsontable();
                                            tableFunction.saveChanges(true);
                                        });
                                    },
                                },
                                {
                                    key: "column_type:text",
                                    name: function () {
                                        if (typeof table_function_data.type_column_selected !== 'undefined' && table_function_data.type_column_selected === 'text') {
                                            return '<span class="selected">' + wptmContext.column_type_text + '</span>';
                                        } else {
                                            return '<span>' + wptmContext.column_type_text + '</span>';
                                        }
                                    },
                                    callback: function (key, options) {
                                        tableFunction.wptmBootbox('', wptmText.ALERT_CHANGE_COLUMN_TYPE, true, true, () => {
                                            var cols_selected = [];
                                            var i, jj;
                                            for (jj = 0; jj < table_function_data.selectionSize; jj++) {
                                                for (i = table_function_data.selection[jj][1]; i <= table_function_data.selection[jj][3]; i++) {
                                                    cols_selected[i] = 'text';
                                                    Wptm.style.table.col_types[i] = 'text';
                                                }
                                            }

                                            // saveData.push({action: 'set_column_type', cols: unique_cols_selected, type: 'varchar'});
                                            saveData.push({
                                                action: 'set_columns_types',
                                                value: cols_selected
                                            });
                                            tableFunction.cleanHandsontable();
                                            tableFunction.saveChanges(true);
                                        });
                                    },
                                },
                            ]
                        },
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row < 1) {
                                return true;
                            }
                            if (table_function_data.selectionSize > 1 || table_function_data.selection[0][3] - table_function_data.selection[0][1] > 0) {
                                return true;
                            }
                            return false;
                        }
                    },
                    "hide_column": {
                        name: function () {
                            if (typeof Wptm.newSelect !== 'undefined' && Wptm.newSelect === 'col') {
                                var selection = table_function_data.selection;
                                if (typeof Wptm.style.cols[selection[0][1]] !== 'undefined'
                                    && Wptm.style.cols[selection[0][1]] !== null
                                    && typeof Wptm.style.cols[selection[0][1]][1].hide_column !== 'undefined'
                                    && Wptm.style.cols[selection[0][1]][1].hide_column == 1) {
                                    return '<span class="selected">' + wptmContext.hide_column + '</span>';
                                } else {
                                    return '<span>' + wptmContext.hide_column + '</span>';
                                }
                            }
                        },
                        callback: function (key, selection, clickEvent) {
                            if (Wptm.newSelect === 'col') {
                                var selection = table_function_data.selection;
                                if (typeof Wptm.style.cols[selection[0][1]] !== 'undefined' && Wptm.style.cols[selection[0][1]] !== null) {
                                    if (typeof Wptm.style.cols[selection[0][1]][1].hide_column !== 'undefined' && Wptm.style.cols[selection[0][1]][1].hide_column == 1) {
                                        Wptm.style.cols = tableFunction.fillArray(Wptm.style.cols, {hide_column: 0}, selection[0][1]);
                                    } else {
                                        Wptm.style.cols = tableFunction.fillArray(Wptm.style.cols, {hide_column: 1}, selection[0][1]);
                                    }
                                    tableFunction.saveChanges(true);
                                }
                                return true;
                            }
                            return true;
                        },
                        hidden: function () {
                            var selection = table_function_data.selection;
                            if (typeof Wptm.newSelect === 'undefined' || Wptm.newSelect !== "col" || selection[0][3] - selection[0][1] >= 1) {
                                return true;
                            }
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    }
                }
            }
            : {
                items: {
                    "hide_column": {
                        name: function () {
                            if (typeof Wptm.newSelect !== 'undefined' && Wptm.newSelect === 'col') {
                                var selection = table_function_data.selection;
                                if (typeof Wptm.style.cols[selection[0][1]] !== 'undefined'
                                    && Wptm.style.cols[selection[0][1]] !== null
                                    && typeof Wptm.style.cols[selection[0][1]][1].hide_column !== 'undefined'
                                    && Wptm.style.cols[selection[0][1]][1].hide_column == 1) {
                                    return '<span class="selected">' + wptmContext.hide_column + '</span>';
                                } else {
                                    return '<span>' + wptmContext.hide_column + '</span>';
                                }
                            }
                        },
                        callback: function (key, selection, clickEvent) {
                            if (Wptm.newSelect === 'col') {
                                var selection = table_function_data.selection;
                                if (typeof Wptm.style.cols[selection[0][1]] !== 'undefined' && Wptm.style.cols[selection[0][1]] !== null) {
                                    if (typeof Wptm.style.cols[selection[0][1]][1].hide_column !== 'undefined' && Wptm.style.cols[selection[0][1]][1].hide_column == 1) {
                                        Wptm.style.cols = tableFunction.fillArray(Wptm.style.cols, {hide_column: 0}, selection[0][1]);
                                    } else {
                                        Wptm.style.cols = tableFunction.fillArray(Wptm.style.cols, {hide_column: 1}, selection[0][1]);
                                    }
                                }
                                return true;
                            }
                            return true;
                        },
                        hidden: function () {
                            var selection = table_function_data.selection;
                            if (typeof Wptm.newSelect === 'undefined' || Wptm.newSelect !== "col" || selection[0][3] - selection[0][1] >= 1) {
                                return true;
                            }
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "remove_alternating_color": {
                        name: wptmContext.remove_alternating_color,
                        callback: function (key, selection, clickEvent) {
                            tableFunction.fillArray(table_function_data, {allAlternate: {}});
                            tableFunction.fillArray(Wptm.style.table, {allAlternate: {}});
                            window.jquery(wptm_element.tableContainer).handsontable('render');
                            tableFunction.saveChanges(true);
                        },
                        hidden: function () {
                            if (typeof table_function_data.allAlternate !== 'undefined' && typeof table_function_data.allAlternate.even !== 'undefined') {
                                return false;
                            }
                            return true;
                        }
                    },
                    "delete_row_dbtable": {
                        name: function () {
                            if (typeof Wptm.newSelect !== 'undefined') {
                                if (Wptm.newSelect === 'row') {
                                    return '<span>' + wptmContext.delete_row_db_table + '</span>';
                                }
                            }
                        },
                        callback: function (key, selection, clickEvent) {
                            if (Wptm.newSelect === 'row') {
                                if (typeof Wptm.query_option.columns_list !== "undefined") {
                                    var not_create = false;
                                    var warning_craete = wptmText.warning_delete_row_db_table;

                                    if (Wptm.query_option.tables_list.length > 1) {
                                        not_create = true;
                                        warning_craete = wptmText.warning_delete_row_multiple_db_table;
                                    }

                                    var selection = table_function_data.selection;
                                    if (not_create || typeof Wptm.query_option.column_options[table_function_data.keyPosition] === 'undefined') {
                                        tableFunction.wptmBootbox('', warning_craete, true, false);
                                        return true;
                                    } else if (typeof selection[0] !== 'undefined') {
                                        tableFunction.wptmBootbox('', warning_craete, true, true, () => {
                                            var id_row = Wptm.datas[selection[0][0]][table_function_data.keyPosition];
                                            var field = Wptm.query_option.column_options[table_function_data.keyPosition].Field;
                                            tableFunction.deleteRowDbTable(Wptm.query_option.tables_list[0], field, id_row);
                                        });
                                    }
                                }
                                return true;
                            }
                            return true;
                        },
                        hidden: function () {
                            var selection = table_function_data.selection;
                            if (typeof Wptm.newSelect === 'undefined' || Wptm.newSelect !== "row" || selection[0][2] - selection[0][0] >= 1 || !table_function_data.mysqlEdit) {
                                return true;
                            }
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "create_row_dbtable": {
                        name: function () {
                            if (typeof Wptm.newSelect !== 'undefined') {
                                if (Wptm.newSelect === 'row') {
                                    return '<span>' + wptmContext.create_row_db_table + '</span>';
                                }
                            }
                        },
                        callback: function (key, selection, clickEvent) {
                            if (Wptm.newSelect === 'row') {
                                var popup = {
                                    'html': wptm_element.content_popup_hide.find('#create_row_dbtable'),
                                    'showAction': function () {
                                        return true;
                                    },
                                    'submitAction': function () {
                                        if (typeof Wptm.query_option.columns_list !== "undefined") {
                                            var list_value = {};
                                            var dbtable = '';
                                            var not_create = false;
                                            var warning_craete = '';
                                            window.jquery.each(Wptm.query_option.columns_list, function (i, v) {
                                                if (v.canEdit == 1) {
                                                    var value = wptm_element.wptm_popup.find('input.table_column[data-column-name="' + v.table + '.' + v.Field + '"]').val();
                                                    var validate = tableFunction.validate_type_cell(
                                                        v.Type,
                                                        value
                                                    );
                                                    // if (!(typeof list_value[v.table] !== 'undefined')) {
                                                    //     list_value[v.table] = {};
                                                    // }
                                                    // list_value[v.table][v.Field] = value;
                                                    list_value[v.Field] = value;
                                                    dbtable = v.table;
                                                    if (Wptm.query_option.tables_list.length > 1) {
                                                        not_create = true;
                                                        warning_craete = wptmText.warning_craete_row_multiple_db_table;
                                                    } else {
                                                        if (!validate && v.Null !== 'YES') {
                                                            not_create = true;
                                                            warning_craete = wptmText.warning_craete_row_db_table;
                                                        }
                                                    }
                                                }

                                                if (Wptm.query_option.columns_list.length == i + 1) {
                                                    if (not_create) {
                                                        tableFunction.wptmBootbox('', warning_craete, true, false);
                                                        return true;
                                                    } else {
                                                        tableFunction.createRowDbTable(dbtable, list_value);
                                                        wptm_element.wptm_popup.find('.colose_popup').trigger('click');
                                                        return true;
                                                    }
                                                }
                                            });
                                        }
                                        return true;
                                    },
                                    'cancelAction': function () {
                                        return true;
                                    }
                                };
                                selectOption.wptm_popup(wptm_element.wptm_popup, popup, true, false, true);
                            }
                            return true;
                        },
                        hidden: function () {
                            if (typeof Wptm.newSelect === 'undefined' || Wptm.newSelect !== "row" || !table_function_data.mysqlEdit) {
                                return true;
                            }
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "rows_size": {
                        name: function () {
                            if (typeof Wptm.newSelect !== 'undefined') {
                                var selectSize = table_function_data.selection[table_function_data.selectionSize - 1];
                                if (Wptm.newSelect === 'row') {
                                    if (selectSize[0] !== selectSize[2]) {
                                        return '<span>' + wptmContext.define + 's ' + (selectSize[0] + 1) + '-' + (selectSize[2] + 1) + '</span>';
                                    }
                                    return '<span>' + wptmContext.define + '</span>';
                                } else {
                                    if (selectSize[1] !== selectSize[3]) {
                                        return '<span>' + wptmContext.define + 's ' + String.fromCharCode(65 + selectSize[1]) + '-' + String.fromCharCode(65 + selectSize[3]) + '</span>';
                                    }
                                    return '<span>' + wptmContext.defineColumn + '</span>';
                                }
                            }
                        },
                        callback: function (key, selection, clickEvent) {
                            if (Wptm.newSelect === 'row') {
                                wptm_element.primary_toolbars.find('.table_option[name="resize_row"]').trigger('click');
                            } else {
                                wptm_element.primary_toolbars.find('.table_option[name="resize_column"]').trigger('click');
                            }
                            return true;
                        },
                        hidden: function () {
                            if (typeof Wptm.newSelect === 'undefined' || (Wptm.newSelect !== "row" && Wptm.newSelect !== "col")) {
                                return true;
                            }
                            if (Wptm.max_Col * Wptm.max_row > 0) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "protect_range": {
                        name: function () {
                            return '<span>' + wptmContext.protect_range + '</span>';
                        },
                        callback: function (key, selections, clickEvent) {
                            wptm_element.primary_toolbars.find('.table_option[name="lock_ranger_cells"]').trigger('click');
                            return true;
                        },
                        hidden: function () {
                            if (wptm_administrator == 1) {
                                return false;
                            }
                            return true;
                        }
                    },
                    "Add tooltip": {
                        name: wptmContext.tooltip,
                        callback: function (key, selection, clickEvent) {
                            wptm_element.editToolTip.trigger('click');
                        },
                        hidden: function () {
                            if (Wptm.max_Col * Wptm.max_row < 1) {
                                return true;
                            }
                            if (table_function_data.selectionSize > 1
                                || (table_function_data.selection[0][2] - table_function_data.selection[0][0] > 0
                                    || table_function_data.selection[0][3] - table_function_data.selection[0][1] > 0)) {
                                return true;
                            }
                            return false;
                        }
                    },
                    "Insert a cell selection": {
                        name: wptmContext.insert_selection,
                        callback: function (key, selection, clickEvent) {
                            wptm_element.primary_toolbars.find('.table_option[name="cell_insertion_shortcode"]').trigger('click');
                        },
                        hidden: function () {
                            return false;
                        }
                    }
                }
            })
    });

    wptm_element.tableContainer.find('.ht_clone_top_left_corner').unbind('click').on('click', function () {
        var max_col = parseInt(Wptm.max_Col) > 0 ? Wptm.max_Col : Wptm.datas[0].length;
        var max_row = parseInt(Wptm.max_row) > 0 ? Wptm.max_row : Wptm.datas.length;
        jquery(wptm_element.tableContainer).handsontable("selectCells", [[0, 0, max_row - 1, max_col - 1]]);
    });

    // search key
    wptm_element.primary_toolbars.find('.search-menu').find('#dp-form-search').on('keyup', function (event) {
        if (event.keyCode === 13) {
            wptm_element.primary_toolbars.find('.search-menu').find('.search_table').trigger('click');
        } else if (jquery(this).val() === '') {
            wptm_element.primary_toolbars.find('.search-menu').find('.search_table').trigger('click');
        }
    });

    wptm_element.primary_toolbars.find('.search-menu').find('.search_table').click(function () {
        var textSearch = wptm_element.primary_toolbars.find('.search-menu').find('#dp-form-search');
        var queryResult = jquery(Wptm.container).data('handsontable').getPlugin('search').query(textSearch.val());
        jquery(Wptm.container).data('handsontable').render();
    });

    wptm_element.primary_toolbars.find('.search-menu').find('.reload_search').click(function () {
        wptm_element.primary_toolbars.find('.search-menu').find('#dp-form-search').val('');
        wptm_element.primary_toolbars.find('.search-menu').find('.search_table').trigger('click');
    });

    /*select menu option function*/
    selectOption.selectOption();

    /*more function Eg: rename table*/
    window.wptm_element.primary_toolbars.find('.wptm_name_edit').text(window.Wptm.title);
}

/*
* function change Wptm.mergeCellsSetting when mergecell/unMergeCell action
*/
function getMergeCells(argument, checkUnmerge) {
    if (typeof window.table_function_data.mergeCells === 'undefined') {
        window.table_function_data.mergeCells = [];
        if (window.Wptm.mergeCellsSetting.length > 0) {
            var i;
            for (i = 0; i < window.Wptm.mergeCellsSetting.length; i++) {
                window.table_function_data.mergeCells['d' + window.Wptm.mergeCellsSetting[i].row + window.Wptm.mergeCellsSetting[i].col] = window.Wptm.mergeCellsSetting[i];
            }
        }
    }
    if (checkUnmerge) {
        for (i in table_function_data.mergeCells) {
            if (argument[0].from.row <= table_function_data.mergeCells[i].row
                && table_function_data.mergeCells[i].row <= argument[0].to.row
                && argument[0].from.col <= table_function_data.mergeCells[i].col
                && table_function_data.mergeCells[i].col <= argument[0].to.col) {
                delete table_function_data.mergeCells[i];
            }
        }
    } else {
        var key = 'd' + argument[0].from.row + argument[0].from.col;
        table_function_data.mergeCells[key] = argument[1];
    }
}

/*
* Function update mergecells when change rows and cols
* */
function updateMergeCells(firstRender) {
    window.Wptm.mergeCellsSetting = [];
    var ht = jquery(Wptm.container).handsontable('getInstance');
    var mergeSetting = ht.getPlugin('mergeCells').mergedCellsCollection;
    var i = 0, ij = 0, ij2 = 0;

    if (mergeSetting.mergedCells.length < 1) {//save
        tableFunction.saveChanges();
    }

    table_function_data.start_merge_cell_col = {};

    for (i = 0; i < mergeSetting.mergedCells.length; i++) {
        window.Wptm.mergeCellsSetting[i] = {
            col: mergeSetting.mergedCells[i].col,
            colspan: mergeSetting.mergedCells[i].colspan,
            row: mergeSetting.mergedCells[i].row,
            rowspan: mergeSetting.mergedCells[i].rowspan
        };
        if (typeof table_function_data.start_merge_cell_col[mergeSetting.mergedCells[i].col] == 'undefined') {
            table_function_data.start_merge_cell_col[mergeSetting.mergedCells[i].col] = [];
        }
        table_function_data.start_merge_cell_col[mergeSetting.mergedCells[i].col][mergeSetting.mergedCells[i].row] = i;
    }

    if (firstRender) {
        tableFunction.saveChanges(firstRender);
    }
}

/*
* calculator height table container
* style: style of table,
* getRowsHeight: true(get getRowHeight), false(not get getRowHeight),
* top: true(get height of ht_clone_top), false(not get height of ht_clone_top)
* */
function calHeightTable(style, getRowsHeight, top, rowRender) {
    var rows = style.rows.length;
    if (rows === undefined) {
        rows = Object.keys(style.rows).length;
    }

    var height = 0;
    var htCloneTop = window.wptm_element.tableContainer.find('.ht_clone_top');

    if (getRowsHeight) {
        for (var i = 0; i < rows; i++) {
            window.Wptm.rowsHeight[i] = window.Wptm.container.handsontable('getRowHeight', i);
            height += window.Wptm.rowsHeight[i];
        }
    } else {
        //remove setTimeout when resize rows
        // clearTimeout(window.setHeightTable);

        height = window.Wptm.table_height;
        var newHeight = window.Wptm.container.handsontable('getRowHeight', rowRender);
        if (typeof window.Wptm.rowsHeight[rowRender] === 'undefined') {
            window.Wptm.rowsHeight[rowRender] = 0;
        }
        height += newHeight - window.Wptm.rowsHeight[rowRender];
        window.Wptm.rowsHeight[rowRender] = newHeight;
    }

    if (top) {
        height += htCloneTop.outerHeight();
    }
    return height;
}

const changeCellAfterCreateRow = function (index, amount, selector, above) {
    if (typeof (Wptm.style.cells) !== 'undefined') {
        var $ = window.jquery;
        var newCells = {};
        var cell, i, cells;

        for (cell in Wptm.style.cells) {
            cells = Wptm.style.cells[cell];
            if (cells[0] < index) {//rows < index
                newCells[cell] = $.extend([], cells);
            }

            if (cells[0] >= index) {//rows >= index
                if (typeof table_function_data.createCellWhenCopy !== 'undefined' && typeof table_function_data.createCellWhenCopy[cell] !== 'undefined') {
                    newCells[cell] = $.extend([], cells);
                } else {
                    newCells[parseInt(cells[0] + amount) + '!' + cells[1]]
                        = [cells[0] + amount, cells[1], $.extend({}, cells[2])];
                }
            }
            if (cells[0] <= selector[2] && selector[0] <= cells[0]) {//new rows copy style of selector
                if (typeof table_function_data.createCellWhenCopy !== 'undefined' && typeof table_function_data.createCellWhenCopy[cell] !== 'undefined') {
                    newCells[cell] = $.extend([], cells);
                } else {
                    newCells[parseInt(cells[0] - selector[0] + index) + '!' + cells[1]]
                        = [parseInt(cells[0] - selector[0] + index), cells[1], $.extend({}, cells[2])];
                }
            }
        }
        Wptm.style.cells = $.extend({}, newCells);

        if (typeof Wptm.style.table.alternateColorValue !== 'undefined') {//update Wptm.style.table.alternateColorValue by index, amount
            var alternateColorValue = Wptm.style.table.alternateColorValue;
            for (i in alternateColorValue) {
                if (alternateColorValue[i] !== false) {
                    if (above) {//above
                        if (alternateColorValue[i].selection[0] >= index + amount) { // alternateColorValue > new rows
                            alternateColorValue[i].selection[0] = alternateColorValue[i].selection[0] + amount;
                            alternateColorValue[i].selection[2] = alternateColorValue[i].selection[2] + amount;
                        }
                        if (alternateColorValue[i].selection[0] < index + amount && alternateColorValue[i].selection[2] >= index) {
                            alternateColorValue[i].selection[2] = alternateColorValue[i].selection[2] + amount;
                        }
                    } else {//below
                        if (alternateColorValue[i].selection[0] >= index) { // alternateColorValue > new rows
                            alternateColorValue[i].selection[0] = alternateColorValue[i].selection[0] + amount;
                            alternateColorValue[i].selection[2] = alternateColorValue[i].selection[2] + amount;
                        }
                        if (alternateColorValue[i].selection[0] < index && alternateColorValue[i].selection[2] >= selector[0]) {
                            alternateColorValue[i].selection[2] = alternateColorValue[i].selection[2] + amount;
                        }
                    }
                }
            }
        }

        return true;
    }
    return false;
}

/**
 * Set position for html cell editer popup
 *
 * @param checkScroll Check whether to perform
 * @returns {boolean}
 */
function afterScrollEditors (checkScroll) {
    if (checkScroll === true) {
        const selected = table_function_data.selection
        if (typeof selected[0] !== 'undefined' ) {
            let selectionCell = jquery(Wptm.container).handsontable('getCell', selected[0][0], selected[0][1])
            let positionSelectionCell = jquery(selectionCell).offset()
            let positionTableContainer = jquery('#tableContainer').offset()

            wptm_element.handsontableInputHolder.removeClass('wptm_hiden')

            // console.log('afterScrollEditors', wptm_element.handsontableInputHolder)
            wptm_element.handsontableInputHolder[0].style.top = (positionSelectionCell.top - positionTableContainer.top) + 'px'
            wptm_element.handsontableInputHolder[0].style.left = (positionSelectionCell.left - positionTableContainer.left) + 'px'
        }
    }
    return false;
}

export {initHandsontable, getMergeCells, calHeightTable}