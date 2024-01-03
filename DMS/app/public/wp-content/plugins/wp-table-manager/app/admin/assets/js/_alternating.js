//alternate color handling functions
import tableFunction from "./_functions";

const alternating = {
    /**
     * Check that the selection matches the saved alternate values
     *
     * @param selection
     * @param oldAlternate
     * @returns {*}
     */
    setNumberAlternate : (selection, oldAlternate) => {
        var count = _.size(oldAlternate);

        window.jquery.each(oldAlternate, function (i, v) {
            if (typeof v.selection !== 'undefined') {
                if (v.selection[0] == selection[0] &&
                    v.selection[1] == selection[1] &&
                    v.selection[2] == selection[2] &&
                    v.selection[3] == selection[3]) {
                    count = i;
                }
            } else {
                return count;
            }
        });

        return count;
    },
    /**
     * when select alternating_color
     *
     * @param value
     * @param selection
     * @param count
     */
    selectAlternatingColor : (value, selection, count) => {//when select alternating_color
        //ij is row, ik is coll
        var ij, ik;
        var beforeChange = window.jquery.extend({}, window.Wptm.style.cells);
        var size = _.size(window.table_function_data.checkChangeAlternate);
        window.table_function_data.checkChangeAlternate[size] = {};

        for (ij = selection[0]; ij <= selection[2]; ij++) {
            for (ik = selection[1]; ik <= selection[3]; ik++) {
                if (typeof (beforeChange[ij + "!" + ik]) !== 'undefined') {
                    window.table_function_data.checkChangeAlternate[size][ij + "!" + ik] = beforeChange[ij + "!" + ik][2].AlternateColor;
                    beforeChange[ij + "!" + ik][2] = window.jquery.extend(beforeChange[ij + "!" + ik][2], {AlternateColor: count});
                } else { //cell not have style
                    beforeChange[ij + "!" + ik] = [ij, ik, {}];
                    beforeChange[ij + "!" + ik][2] = {AlternateColor: count};
                }
                if (typeof window.table_function_data.checkChangeAlternate[size][ij + "!" + ik] === 'undefined' || window.table_function_data.checkChangeAlternate[size][ij + "!" + ik] === null) {
                    window.table_function_data.checkChangeAlternate[size][ij + "!" + ik] = -1;
                }
            }
        }

        window.Wptm.style.cells = beforeChange;

        var listChangeAlternate = [];
        var i = count, oldCount = count;
        window.table_function_data.changeAlternate = [];
        for (var ii = count - 1; ii >= 0; ii--) {
            var check = 1;
            if (value[ii] !== false && typeof value[ii].selection !== 'undefined') {
                if(value[ii].selection[0] >= value[i].selection[0]) {
                    check++;
                }

                if(value[ii].selection[1] >= value[i].selection[1]) {
                    check++;
                }

                if(value[ii].selection[2] <= value[i].selection[2]) {
                    check++;
                }

                if(value[ii].selection[3] <= value[i].selection[3]) {
                    check++;
                }
            } else {
                check = 5;
            }

            if (check === 5) {
                value[ii] = value[count];
                listChangeAlternate[ii] = count;
                if (typeof listChangeAlternate[count] !== 'undefined') {
                    listChangeAlternate[ii] = listChangeAlternate[count];
                    delete listChangeAlternate[count];
                }
                delete value[count];
                count--;
                i = ii;
            }
        }
        for (var j = 0; j < oldCount; j ++) {
            if (typeof listChangeAlternate[j] !== 'undefined') {
                window.table_function_data.changeAlternate[listChangeAlternate[j]] = j;
            }
        }
        window.table_function_data.alternateIndex = count;
        window.table_function_data.alternateSelection = selection;
    },
    /**
     * Cancel alternateColor
     */
    reAlternateColor : () => {
        var styleCells = {};
        styleCells = window.jquery.extend({}, Wptm.style.cells);

        for (var i = _.size(window.table_function_data.checkChangeAlternate) - 1; i >= 0; i--) {
            window.jquery.map(window.table_function_data.checkChangeAlternate[i], function (v, ii) {
                if (typeof styleCells[ii] !== 'undefined') {
                    if (v !== -1) {
                        styleCells[ii][2].AlternateColor = v;
                    } else {
                        delete styleCells[ii][2].AlternateColor;
                    }
                }
            });
        }

        window.table_function_data.checkChangeAlternate = [];
        return styleCells;
    },



    /**
     * Set alternate color by alternate_row_odd_color option(in params)
     *
     * @param styleRows style.rows
     * @param Wptm
     * @param $element
     */
    setAlternateColor : (styleRows, Wptm, $element) => {
        Wptm.style.table.alternateColorValue = {};

        if (typeof Wptm.container !== 'undefined') {
            var countCols = Wptm.container.handsontable('countCols');
            var countRows = Wptm.container.handsontable('countRows');
        } else {/*get count cols, rows when handsontable not activated*/
            countCols = Wptm.datas[0].length - 1;
            countRows = Wptm.datas.length - 1;
        }

        var checkExistAlternateOld = 0;
        var checkExistAlternateEven = 0;

        if (tableFunction.checkObjPropertyNested(Wptm.style.table, 'alternate_row_odd_color') && Wptm.style.table.alternate_row_odd_color) {
            checkExistAlternateOld = 1;
            // Wptm.style.table.alternate_row_odd_color;
        }
        if (tableFunction.checkObjPropertyNested(Wptm.style.table, 'alternate_row_even_color') && Wptm.style.table.alternate_row_even_color) {
            checkExistAlternateEven = 1;
            // Wptm.style.table.alternate_row_even_color;
        }

        var header = '';
        if (styleRows !== null && typeof styleRows[0][1].cell_background_color !== 'undefined') {
            header = styleRows[0][1].cell_background_color;
        }

        if (checkExistAlternateEven + checkExistAlternateOld > 0) {
            Wptm.style.table.alternateColorValue = {};
            Wptm.style.table.alternateColorValue[0] = {};
            Wptm.style.table.alternateColorValue[0].selection = [0, 0, countRows - 1, countCols - 1];
            Wptm.style.table.alternateColorValue[0].footer = '';
            Wptm.style.table.alternateColorValue[0].even = checkExistAlternateEven === 1 ? Wptm.style.table.alternate_row_even_color : '#ffffff';
            Wptm.style.table.alternateColorValue[0].header = header;
            Wptm.style.table.alternateColorValue[0].old = checkExistAlternateOld === 1 ? Wptm.style.table.alternate_row_odd_color : '#ffffff';
            Wptm.style.table.alternateColorValue[0].default = '' + header + '|' + Wptm.style.table.alternateColorValue[0].even + '|' + Wptm.style.table.alternateColorValue[0].old + '|' + '';
            saveData.push({action: 'style', selection: [[0, 0, countRows, countCols]], style: {AlternateColor: 0}});
        }

        Wptm.style.table.alternate_row_even_color = null;
        Wptm.style.table.alternate_row_odd_color = null;

        if (typeof Wptm.style.table.alternateColorValue[0] !== 'undefined') {
            var ij, ik;
            for (ij = 0; ij <= countRows; ij++) {
                for (ik = 0; ik <= countCols; ik++) {
                    if (typeof (Wptm.style.cells[ij + "!" + ik]) !== 'undefined') {
                        Wptm.style.cells[ij + "!" + ik][2].AlternateColor = 0;
                    } else {
                        Wptm.style.cells[ij + "!" + ik] = [ij, ik, {}];
                        Wptm.style.cells[ij + "!" + ik][2] = jQuery.extend({}, {AlternateColor: 0});
                    }
                    if (header !== '' && ij === 0) {
                        delete Wptm.style.cells[ij + "!" + ik][2].cell_background_color;
                    }
                }
            }
        }

        window.Wptm = window.jquery.extend({}, Wptm);
    },
    /**
     * add new alternate to saving function and Wptm.style.table
     */
    applyAlternate : function () { //save oldAlternate value to Wptm and save
        window.Wptm.style.table.alternateColorValue = window.jquery.extend({}, window.table_function_data.oldAlternate);
        window.Wptm.style.table.allAlternate = window.jquery.extend({}, window.table_function_data.allAlternate);

        if(window.table_function_data.alternateIndex !== '') {
            var i, k;
            for (i = window.table_function_data.alternateSelection[0]; i <= window.table_function_data.alternateSelection[2]; i++) {
                for (k = window.table_function_data.alternateSelection[1]; k <= window.table_function_data.alternateSelection[3]; k++) {
                    if (typeof (window.Wptm.style.cells[i + "!" + k]) !== 'undefined') {
                        window.Wptm.style.cells[i + "!" + k][2] = window.jquery.extend(window.Wptm.style.cells[i + "!" + k][2], {AlternateColor: window.table_function_data.alternateIndex});
                    } else { //cell not have style
                        window.Wptm.style.cells[i + "!" + k] = [i, k, {}];
                        window.Wptm.style.cells[i + "!" + k][2] = {AlternateColor: window.table_function_data.alternateIndex};
                    }
                }
            }

            window.table_function_data.save_table_params.push({action: 'style', selection: [window.table_function_data.alternateSelection], style: {AlternateColor: window.table_function_data.alternateIndex}});
        }
        window.table_function_data.checkChangeAlternate = [];

        tableFunction.saveChanges(true);
        this.siblings('.colose_popup').trigger('click');
    },
    /**
     * set the change options of altenate when selector cell/change rangeLabe
     * @param Wptm
     * @param $
     */
    affterRangeLabe : function (Wptm, $) {
        try {
            var rangeLabel = this.find('.cellRangeLabelAlternate').val();
            rangeLabel = rangeLabel.replace(/[ ]+/g, "").toUpperCase();
            var arrayRange = rangeLabel.split(":");
            if (arrayRange.length > 1) {
                var check_value = 0;
                if (arrayRange[0].replace(/[ |A-Za-z]+/g, "") === '') {
                    check_value = 1;//value A:B
                } else if (arrayRange[0].replace(/[ |1-9]+/g, "") === '') {
                    check_value = 2;//value 8:9
                }
                var selection = [];
                if (check_value === 0) {
                    selection.push(parseInt(arrayRange[0].split(/[ |A-Za-z]+/g)[1]) - 1);
                    selection.push(tableFunction.convertStringToNumber(arrayRange[0].split(/[ |1-9]+/g)[0]) - 1);
                    selection.push(parseInt(arrayRange[1].split(/[ |A-Za-z]+/g)[1]) - 1);
                    selection.push(tableFunction.convertStringToNumber(arrayRange[1].split(/[ |1-9]+/g)[0]) - 1);
                } else if (check_value === 1) {
                    selection.push(0);
                    selection.push(tableFunction.convertStringToNumber(arrayRange[0].split(/[ |1-9]+/g)[0]) - 1);
                    selection.push(Wptm.max_row - 1);
                    selection.push(tableFunction.convertStringToNumber(arrayRange[1].split(/[ |1-9]+/g)[0]) - 1);
                } else {
                    selection.push(parseInt(arrayRange[0].split(/[ |A-Za-z]+/g)[0]) - 1);
                    selection.push(0);
                    selection.push(parseInt(arrayRange[1].split(/[ |A-Za-z]+/g)[0]) - 1);
                    selection.push(Wptm.max_Col - 1);
                }

                if (Wptm.type === 'mysql') {
                    if (selection[2] + selection[0] === 0) {
                        this.find('.wptm_range_labe_show').removeClass('wptm_hiden');
                    } else {
                        this.find('.wptm_range_labe_show').addClass('wptm_hiden');
                    }
                }
                $(Wptm.container).handsontable("selectCell", selection[0], selection[1], selection[2], selection[3]);

                if (typeof this.find('.cellRangeLabelAlternate').data('text_change') !== 'undefined') {
                    if (this.find('.cellRangeLabelAlternate').hasClass('select_row')) {
                        if (selection[0] === selection[2]) {
                            $(this.find('.cellRangeLabelAlternate').data('text_change')).text(wptmContext.rows_height);
                        } else {
                            $(this.find('.cellRangeLabelAlternate').data('text_change')).text(wptmContext.rows_height_start + (selection[0] + 1) + '-' + (selection[2] + 1));
                        }
                    } else if (this.find('.cellRangeLabelAlternate').hasClass('select_column')) {
                        $(this.find('.cellRangeLabelAlternate').data('text_change')).text(wptmContext.columns_width_start
                            + arrayRange[0].split(/[ |1-9]+/g)[0] + '-' + arrayRange[1].split(/[ |1-9]+/g)[0]);
                    } else {
                        $(this.find('.cellRangeLabelAlternate').data('text_change')).text(rangeLabel);
                    }
                }
            }
        } catch (err) {}
    },
    /**
     * active alternating
     *
     * @param format
     */
    getActiveFormatColor: (format) => {
        $alternating_color.find('.formatting_style .pane-color-tile').each(function () {
            if (window.jquery(this).find('.pane-color-tile-1').data('value') === format.even) {
                if (window.jquery(this).find('.pane-color-tile-2').data('value') === format.old) {
                    var check = 0;
                    if (format.header !== '') {
                        check = format.header === window.jquery(this).find('.pane-color-tile-header').data('value') ? 1 : -1;
                    } else {
                        check = 1;
                    }

                    if (format.footer !== '') {
                        check = format.footer === window.jquery(this).find('.pane-color-tile-footer').data('value') ? check : -1;
                    }

                    switch (check) {
                        case 1:
                            window.jquery(this).addClass('active');
                            break;
                        case -1:
                            // No active
                            break;
                    }
                }
            }
        });
    },
    /**
     * handsontable render after set alternating
     */
    renderCell : () => { //render cells
        window.jquery(window.Wptm.container).handsontable('render');
    }
}

export default alternating
