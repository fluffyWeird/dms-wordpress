(function ($) {
    $(document).ready(function() {
        var wptmprint = window.parent.wptmprint
        let printTableOption = wptmprint[wptm_id].tableOptions
        let order = wptmprint[wptm_id].order
        let countRow = 0

        setTimeout(() => {
            var tableDom = $(document).find(".wptmPrintTable" + wptm_id)

            let ajaxOrder = {}
            ajaxOrder.searchPrint = wptmprint[wptm_id].searchValue
            ajaxOrder.searchColumnsForm = printTableOption.searchColumnsForm
            ajaxOrder.searchAll = printTableOption.searchAll
            ajaxOrder.searchRange = printTableOption.searchRange
            if (order !== null)
                ajaxOrder.orderPrint = order

            var tableOptions = {
                responsive: true,
                processing: true,
                serverSide: true,
                paging: true,
                columns: wptmprint[wptm_id].Columns,
                ajax: {
                    url: wptmprint[wptm_id].ajaxUrl + 'task=table.printFile&id=' + wptm_id,
                    data: ajaxOrder,
                    type: 'POST',
                    dataSrc: function(json){
                        if (!json.success) {
                            return false
                        }
                        return json.data
                    },
                    'dataFilter': function(json){
                        var json = jQuery.parseJSON(json)
                        var data = {}

                        data.recordsTotal = json.data.recordsTotal
                        data.recordsFiltered = json.data.recordsFiltered
                        data.data = json.data.data
                        data.page = json.data.page
                        data.draw = json.data.draw
                        data.success = json.success

                        countRow = data.data.length

                        return JSON.stringify(data) // return JSON string
                    }
                }
            };

            var pagination_merge_cells = [];
            tableOptions.createdRow = function (row, data, dataIndex) {
                var keys = Object.keys(data)
                var $cRow;
                if (typeof data.DT_RowId !== 'undefined') {
                    $(row).addClass('row' + data.DT_RowId + ' row_index'
                        + (dataIndex + printTableOption.language.left_header)).data('row-index', (dataIndex + printTableOption.language.left_header))
                } else {
                    $(row).addClass(' row_index' + (dataIndex + printTableOption.language.left_header)).data('row-index', (dataIndex
                        + printTableOption.language.left_header))
                }

                if (typeof data.merges !== 'undefined') {
                    $.each(data.merges, function (key, value) {
                        if (typeof printTableOption.juHideColumns !== 'undefined'
                            && typeof printTableOption.juHideColumns[value[2]] !== 'undefined'
                            && printTableOption.juHideColumns[value[2]] > 0) {
                            value[2] = parseInt(value[2]) - printTableOption.juHideColumns[value[2]]
                        } else {
                        }

                        $(row).find('td:nth-child(' + (1 + parseInt(value[2])) + ')').attr('colspan', value[3]).attr('rowspan', value[1])
                        //merger rows
                        if (typeof pagination_merge_cells[value[0]] == 'undefined') {
                            pagination_merge_cells[value[0]] = []
                        }
                        pagination_merge_cells[value[0]][value[2]] = value
                        var rowspanI = 0
                        for (rowspanI = 0; rowspanI < parseInt(value[1]); rowspanI++) {
                            if (typeof pagination_merge_cells[parseInt(value[0]) + rowspanI] == 'undefined') {
                                pagination_merge_cells[parseInt(value[0]) + rowspanI] = []
                            }
                            pagination_merge_cells[parseInt(value[0]) + rowspanI][value[2]] = value
                        }
                    });
                }

                var ii = 0;
                let regex2 = new RegExp('(dtc)([0-9]+)')
                let regex3 = new RegExp('(sort)([0-9]+)');
                var wptm_number_reg = new RegExp('[0-9| |\.|\,|\\-]', "g");
                keys.forEach(function (key, index) {
                    if (key !== 'merges' && key !== 'DT_RowId' && key !== 'format_date_cell'
                        && key !== 'format_number' && !(regex3.exec(key) !== null))  {
                        $cRow = $(row).find('td:nth-child(' + (parseInt(key) + 1).toString() + ')')
                        let columnClass = 'dtc' + key
                        if (typeof printTableOption.juHideColumn !== 'undefined') {
                            columnClass = 'dtc' + printTableOption.juHideColumnClass[key]
                        }
                        if (typeof printTableOption.pagingType !== 'undefined' && typeof printTableOption.juHideColumn !== 'undefined') {
                            if (key !== 'DT_RowId') {
                                if ($cRow.length) {
                                    if (typeof data.DT_RowId !== 'undefined') {
                                        $cRow.addClass('dtr' + data.DT_RowId).addClass(columnClass)
                                    } else {
                                        $cRow.addClass(columnClass)
                                    }
                                }
                            }
                        } else {
                            if (key !== 'DT_RowId') {
                                if ($cRow.length) {
                                    let class_list = $cRow.attr("class") !== undefined ? $cRow.attr("class").split(' ') : []
                                    let classColumn = class_list.filter(classname => regex2.exec(classname) !== null)
                                    if (classColumn[0]) {
                                        $cRow.removeClass(classColumn[0])
                                    }

                                    if (typeof data.DT_RowId !== 'undefined') {
                                        $cRow.addClass('dtr' + data.DT_RowId).addClass(columnClass)
                                    } else {
                                        $cRow.addClass(columnClass)
                                    }
                                }
                            }
                        }

                        if (key !== 'DT_RowId' && typeof data.DT_RowId !== 'undefined' && typeof pagination_merge_cells[data.DT_RowId] !== 'undefined') {//has merger
                            pagination_merge_cells[data.DT_RowId].forEach(function (value, key) {
                                var colspanI = 0
                                for (colspanI = 0; colspanI < parseInt(value[3]); colspanI++) {
                                    if (!(parseInt(value[0]) == data.DT_RowId && colspanI === 0)) {
                                        $(row).find('td:nth-child(' + (1 + parseInt(value[2]) + colspanI) + ')').css('display', 'none')
                                    }
                                }
                            });
                        }

                        var value_format = ''
                        var value_timestamp = ''
                        let valueCell = data[key]

                        if (typeof data.format_date_cell !== 'undefined' && typeof data.format_date_cell[key] !== 'undefined' && $cRow.text() !== '') {
                            let time = moment(valueCell, 'YYYY/MM/DD HH:mm:ss')

                            if (data.format_date_cell[key] === '1') {
                                value_format = time.format(printTableOption.date_format);
                                value_timestamp = time.format('X');
                                $cRow.data('timestamp', value_timestamp).text(value_format).change()
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
                                valueCell = printTableOption.formatCellContent($cRow, data[key], [dataIndex, key], null, data.format_number[key], false)
                                $cRow.text(valueCell).change();
                            }
                        }

                    }
                    ii++;
                })

                if (countRow === dataIndex + 1) {
                }
            };

            var table = tableDom.on( 'init.dt', function() {
                setTimeout(() => {
                    console.log(document.getElementById("wptmTbl" + wptm_id), document.getElementById("wptmTbl" + wptm_id).style.borderSpacing)
                    let parentTitle = window.parent.document.title
                    window.parent.document.title = wptmprint[wptm_id].tableTitle

                    window.print()

                    $(wptmprint[wptm_id].$button).removeClass('printing')

                    window.parent.document.title = parentTitle
                    $(window.parent.document.getElementsByName('wptm-print-table')).remove()

                    // var style = "<style>";
                    // style = style + "table, th, td {border-collapse: collapse;";
                    // style = style + "</style>";
                    //
                    // console.log(document.getElementsByClassName('dataTable').outerHTML);
                    // var win = window.open('', '', 'height=700,width=700');
                    // win.document.write(style);          //  add the style.
                    // win.document.write(document.getElementsByClassName('dataTable').outerHTML);
                    // win.document.close();
                    // await win.print();
                }, 1000)
            }).DataTable(tableOptions)
        }, 1000)
    })
})(jQuery);