import tableFunction from "./_functions";
import alternating from "./_alternating";
import customRenderer from "./_customRenderer";

/* Chart functions */
var DropChart = {};
DropChart.default = {
    "dataUsing": "column",
    "switchDataUsing": true,
    "useFirstRowAsLabels": true,
    "legend_display": true,
    "useFirstRowAsGraph": false,
    "width": 500,
    "height": 375,
    "chart_align": "center",
    "scaleShowGridLines": false,
    "scaleBeginAtZero": false,
};
DropChart.default.colors = "#4285f4,#ea4335,#fbbc04,#34a853,#46bdc6,#7baaf7,#f07b72";
DropChart.default.pieColors = "#4285f4,#ea4335,#fbbc04,#34a853,#46bdc6,#7baaf7,#f07b72";
DropChart.datas = {};

DropChart.currency_symbol = window.default_value.currency_symbol;
DropChart.thousand_symbol = window.default_value.thousand_symbol;
DropChart.decimal_symbol = window.default_value.decimal_symbol;

DropChart.functions = {};

//get list chart of table
DropChart.functions.loadCharts = function () {
    var $ = jquery;

    if (typeof idTable !== 'undefined' && idTable !== '') {
        var url = wptm_ajaxurl + "view=charts&format=json&id_table=" + idTable;
        $.ajax({
            url: url,
            type: "POST",
            data: {},
            dataType: "json",
        }).done(function (data) {
            var i = 0;
            //console.clear()

            for (i = 0; i < data.length; i++) {
                var cells = $.parseJSON(data[i].datas);
                if ($.isArray(cells) !== false || cells.length !== 0) {
                    var config;
                    try {
                        config = $.parseJSON(data[i].config);
                    } catch (e) {
                        config = $.extend({}, DropChart.default);
                    }

                    var dataCell = DropChart.functions.validateChartData(cells, (config.dataUsing || 'row'));
                    if (dataCell !== false) {
                        var [chartData, selections, chartDataRaw] = dataCell

                        DropChart.datas[data[i].id] = {
                            author: data[i].author,
                            config: config,
                            data: chartData,
                            selections: selections,
                            chartDataRaw: chartDataRaw,
                            title: data[i].title,
                            type: data[i].type,
                        };
                        $('#list_chart').append('<li class="chart-menu" data-id="' + data[i].id + '"><a>' + data[i].title + '</a></li>');
                    }
                }
            }
            wptm_chart();
        });
    }
}

function wptm_chart(first_load) {
    var $ = jquery;
    var $wptm_top_chart = $('#wptm_chart').find('.wptm_left_content .wptm_top_chart');


    $('#list_chart').find('.chart-menu').unbind('click').on('click', function (e) {
        var chart_name = '';
        if (!$(this).data('id')) {
            $(this).closest('.chart-menu').siblings('.chart-menu').removeClass('active');
            var chart_id = $(this).closest('.chart-menu').data('id');
            $(this).closest('.chart-menu').addClass('active');
            chart_name = $(this).text();
        } else {
            $(this).siblings('.chart-menu').removeClass('active');
            var chart_id = $(this).data('id');
            $(this).addClass('active');
            chart_name = $(this).find('a').text();
        }
        $wptm_top_chart.find('.wptm_name_edit').text(chart_name);

        if ($('#inserttable').length > 0) {
            Wptm.chart_active = chart_id;
            if (!$('#inserttable').hasClass('not_change_type')) {
                jquery('#inserttable').data('type', 'chart').attr('data-type', 'chart').text(insert_chart);
            }
            $('#inserttable').removeClass("no_click");
        }

        if(first_load) {
            DropChart.functions.render(chart_id, false, first_load);
        } else {
            DropChart.functions.render(chart_id);
        }
        return false;
    });

    if (typeof Wptm.chart_active !== 'undefined' && parseInt(Wptm.chart_active) > 0) {
        if ($('.over_popup').length > 0) {
            $('.over_popup').hide();
        }
        tableFunction.showChartOrTable(true, $('#list_chart').find('.chart-menu[data-id="' + Wptm.chart_active + '"]'));
    }

    $wptm_top_chart.find('.edit').unbind('click').on('click', function (e) {
        $wptm_top_chart.find('.wptm_name_edit').addClass('rename');
        $wptm_top_chart.find('.wptm_name_edit').trigger('click');
    });

    $wptm_top_chart.find('.wptm_name_edit').click(function () {
        if (!$(this).hasClass('editable')) {
            tableFunction.setText.call(
                $(this),
                $wptm_top_chart.find('.wptm_name_edit'),
                '#wptm_chart .wptm_name_edit',
                {
                    'url': wptm_ajaxurl + "task=chart.setTitle&id=" + DropChart.id_chart + '&title=',
                    'selected': true,
                    'chart_id': DropChart.id_chart,
                    'action': function (obj) {
                        if (arguments[0] != '') {
                            $('#list_chart').find('.chart-menu.active a').text(arguments[0]);
                        }
                    }
                }
            );
        }
    });

    $wptm_top_chart.find('.trash').unbind('click').on('click', function (e) {
        var that = this;
        var list_chart = $('#list_chart');
        tableFunction.wptmBootbox(
            '',
            wptmText.JS_WANT_DELETE + "\"" + $(this).siblings('.wptm_name_edit').text().trim() + '"?',
            true,
            true,
            () => {
                $.ajax({
                    url: wptm_ajaxurl + "task=chart.delete&id=" + DropChart.id_chart,
                    type: "POST",
                    data: {},
                    dataType: "json",
                    success: function (datas) {
                        if (datas.response === true) {
                            list_chart.find('li.chart-menu[data-id="' + DropChart.id_chart + '"]').remove();
                            wptm_element.wptmContentChart.find('canvas.wptm_chart_' + DropChart.id_chart).remove();
                            if (list_chart.find('li.chart-menu').length > 0) {
                                list_chart.find('.chart-menu').eq(0).trigger('click');
                            } else {
                                list_chart.find('.current_table').trigger('click');
                            }
                        } else {
                            alert(datas.response);
                        }
                    },
                    error: function (jqxhr, textStatus, error) {
                        alert(textStatus);
                    }
                });
                return false;
        });
    });
    wptm_element.chartTabContent.find('.copy_shortcode').unbind('click').on('click', function (e) {
        wptm_element.chartTabContent.find('.controls[name="shortcode"] input').select();
        document.execCommand('copy');
    });
    Wptm.dataChart = $.extend([], DropChart.datas);
}

function updateOption(currentChart) {
    var $ = jquery;
    //get cell range label to input selected range and add range to handsontable

    if (currentChart.selections?.length > 0) {
        let cellRangeLabel = ''
        for (let i = 0; i < currentChart.selections?.length; i++) {
            let selection = currentChart.selections[i]
            cellRangeLabel += cellRangeLabel !== '' ? ';' : ''
            cellRangeLabel += Handsontable.helper.spreadsheetColumnLabel(parseInt(selection[1])) + (parseInt(selection[0]) + 1)
            cellRangeLabel += ':' + Handsontable.helper.spreadsheetColumnLabel(parseInt(selection[3])) + (parseInt(selection[2]) + 1)
        }
        wptm_element.chartTabContent.find('.cellRangeLabelAlternate').val(cellRangeLabel);
        // wptm_element.chartTabContent.find('.cellRangeLabelAlternate').parents('.controls').hide()
    }

    let $x_axis_line = wptm_element.chartTabContent.find('.controls[name="x_axis_line"] select')
    $x_axis_line.contents().remove()

    if (typeof currentChart.type !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="type"]').find('img').each(function () {
            $(this).removeClass('active')
            if ($(this).attr('alt') === currentChart.type) {
                $(this).addClass('active')
            }
            wptm_element.chartTabContent.find('.y_axis_line').show()
            if (currentChart.type !== 'Line' && currentChart.type !== 'Bar') {
                wptm_element.chartTabContent.find('.y_axis_line').hide()
            }
        });

        if (typeof currentChart.config.stepSize !== 'undefined') {
            wptm_element.chartTabContent.find('.stepSize').val(currentChart.config.stepSize)
        }
        if (typeof currentChart.config.minY !== 'undefined') {
            wptm_element.chartTabContent.find('.minY').val(currentChart.config.minY)
        }

        wptm_element.chartTabContent.find('#listLine').contents().remove()
        let $dataset_select = wptm_element.chartTabContent.find('.controls[name="dataset_select"] select')
        let $dataset_color = wptm_element.chartTabContent.find('.controls[name="dataset_color"] input.minicolors')
        $dataset_select.contents().remove()

        let $customLabel = wptm_element.chartTabContent.find('.customLabelXaxis')
        $customLabel.find('input:not(.wptm_default)').remove()

        if (currentChart.config?.linesdataAvailable?.length >= 1) {
            let i2 = 0
            let linesdataAvailable = currentChart.config?.linesdataAvailable

            for (let i = 0; i < linesdataAvailable.length; i++) {
                let line = linesdataAvailable[i]
                let html = wptm_element.content_popup_hide.find('.chart_line').clone()

                let line_name = currentChart.config?.dataUsing === 'row'
                    ? ('row ' + (parseInt(line) + 1))
                    : 'column ' + (Handsontable.helper.spreadsheetColumnLabel(parseInt(line)))

                if (!currentChart.config?.linesData.includes(line)) {
                    html.find('input').prop("checked", false)
                } else {
                    html.find('input').prop("checked", true)
                }

                html.find('input').addClass('line_' + line)
                html.find('.lineSelectTion').html(line_name)

                $('#listLine').append(html)

                //append select color for lines
                if (['Bar', 'Radar', 'Line'].includes(currentChart.type) && currentChart.config?.linesData.includes(line)) {
                    $dataset_select.append('<option value="' + i2 + '">' + currentChart.config?.graphLabel[i2] + '</option>');
                    i2++
                }

                $x_axis_line.append('<option value="' + linesdataAvailable[i] + '">' + line_name + '</option>');
                $x_axis_line.trigger('liszt:updated');
            }

            //
            if (currentChart.type == 'Line' || currentChart.type == 'Bar' || currentChart.type == 'Radar') {
                $dataset_color.wpColorPicker('color', currentChart.config?.colors.split(",")[0]);
            } else {
                $dataset_color.wpColorPicker('color', currentChart.config?.pieColors.split(",")[0]);
            }

            for (let i = 0; i < currentChart.config?.customText.length; i++) {
                let text = currentChart.config?.customText[i]

                if (['PolarArea', 'Pie', 'Doughnut'].includes(currentChart.type)) {
                    $dataset_select.append('<option value="' + i + '">' + text + '</option>');
                }
                //customLabelInput customText
                let $customLabelInput = $customLabel.find('input.wptm_default').clone()
                $customLabelInput.attr('placeholder', text).val(text).removeClass('wptm_default').addClass('customLabelInput').data('value', text)
                $customLabel.append($customLabelInput)
            }
        }

        $dataset_select.trigger('liszt:updated');
    }

    if (typeof currentChart.config?.axisColumn !== 'undefined') {
        $x_axis_line.val(parseInt(currentChart.config?.axisColumn)).change()
    }

    if (typeof DropChart.id_chart !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="shortcode"] input').val('[wptm id-chart=' + DropChart.id_chart + ']');
    }
    if (typeof currentChart.config?.dataUsing !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="dataUsing"] select').val(currentChart.config?.dataUsing).change();
    }
    if (typeof currentChart.config?.useFirstRowAsLabels !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="useFirstRowAsLabels"] input')
            .val(currentChart.config?.useFirstRowAsLabels === true ? 'yes' : 'no').prop("checked", currentChart.config?.useFirstRowAsLabels);
    }

    let $customLegendText = wptm_element.chartTabContent.find('.customLegendText')
    $customLegendText.find('input:not(.wptm_default)').remove()

    if (typeof currentChart.config?.customLegend !== 'undefined') {
        let $default_input = $customLegendText.find('input.wptm_default')
        for (let i = 0; i < currentChart.config?.customLegend.length; i++) {
            let $html = $default_input.clone()
            let text = currentChart.config?.customLegend[i]
            let textDefault = currentChart.config?.customLegendDefault[i]
            $html.attr('placeholder', textDefault).val(text).removeClass('wptm_default').addClass('customLegend').data('value', text)
            $customLegendText.append($html)
        }
    }

    if (typeof currentChart.config?.legend_display !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="legend_display"] input')
            .val(currentChart.config?.legend_display === true ? 'yes' : 'no').prop("checked", currentChart.config?.legend_display);

        currentChart.config?.legend_display === true ? $customLegendText.show() : $customLegendText.hide()
    } else {
        wptm_element.chartTabContent.find('.controls[name="legend_display"] input').val('yes').prop("checked", true);

        $customLegendText.show()
    }


    if (typeof currentChart.config?.useFirstRowAsGraph !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="useFirstRowAsGraph"] input')
            .val(currentChart.config?.useFirstRowAsGraph === true ? 'yes' : 'no').prop("checked", currentChart.config?.useFirstRowAsGraph);

        // if (!currentChart.config?.useFirstRowAsGraph) {
        //     $('#wptm_chart').find('.customLabelInput:eq(0)').hide()
        // }
    }
    if (typeof currentChart.config?.width !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="width"] input').val(currentChart.config?.width).change();
    }
    if (typeof currentChart.config?.height !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="height"] input').val(currentChart.config?.height).change();
    }
    if (typeof currentChart.config?.chart_align !== 'undefined') {
        wptm_element.chartTabContent.find('.controls[name="chart_align"] select').val(currentChart.config?.chart_align).change();
        wptm_element.wptmContentChart.css('text-align', currentChart.config?.chart_align);
    }
}

//action option changing
function initChartObserver() {
    if (!(Wptm.can.edit || (Wptm.can.editown && data.author === Wptm.author))) {
        return false;
    }
    var $ = jquery;
    let listLine = false;
    var currentChart = DropChart.datas[DropChart.id_chart];

    $('#wptm_chart #listLine').find('input').unbind('change').bind('change', function (e) {
        e.preventDefault()

        if (DropChart.optionsChanged !== true) {
            return;
        }

        let linesdataAvailable = currentChart.config?.linesdataAvailable
        currentChart.config.linesData = []
        let linesData = []

        for (let i =0; i < linesdataAvailable.length; i++) {
            if ($('#wptm_chart #listLine').find('input.line_' + linesdataAvailable[i]).is(":checked")) {
                linesData.push(linesdataAvailable[i])
            }
        }

        if (linesData.length < 1) {
            $(e.target).prop("checked", true)
            return false;
        }
        currentChart.config.linesData = $.extend([], [], linesData)
        delete DropChart.datas[DropChart.id_chart].config.customLegend

        clearTimeout(listLine);
        listLine = setTimeout(function () {
            DropChart.functions.render(DropChart.id_chart, false, true, 'listLine');
        }, 100);
    })

    $('#wptm_chart .option_chart').unbind('change').bind('change', function (e) {
        if (DropChart.optionsChanged !== true) {
            return false;
        }
        // e.preventDefault()

        switch ($(this).parents('.controls').attr('name')) {
            case 'Shortcode':
                break;
            case 'x_axis_line':
                let column = parseInt($(this).val())
                currentChart.config.axisColumn = '' + column
                delete DropChart.datas[DropChart.id_chart].config.customText

                clearTimeout(listLine);
                listLine = setTimeout(function () {
                    DropChart.functions.render(DropChart.id_chart, false, true, 'x_axis_line');
                }, 100);
                break;
            case 'dataUsing':
                let dataUsing = $(this).val()
                let dataCell = DropChart.functions.validateChartData(currentChart.selections, (dataUsing || 'row'))
                if (dataCell !== false) {
                    var [chartData, selections, chartDataRaw] = dataCell
                    let newConfig = $.extend({}, DropChart.default, {
                        chart_align: DropChart.datas[DropChart.id_chart].config.chart_align,
                        height: DropChart.datas[DropChart.id_chart].config.height,
                        width: DropChart.datas[DropChart.id_chart].config.width
                    })
                    DropChart.datas[DropChart.id_chart] = {
                        config: newConfig,
                        data: chartData,
                        selections: selections,
                        chartDataRaw: chartDataRaw,
                        title: currentChart.title,
                        type: currentChart.type,
                    };
                    DropChart.datas[DropChart.id_chart].config.dataUsing = dataUsing
                    delete DropChart.datas[DropChart.id_chart].config.linesdataAvailable
                    delete DropChart.datas[DropChart.id_chart].config.axisColumn
                    delete DropChart.datas[DropChart.id_chart].config.linesData
                    delete DropChart.datas[DropChart.id_chart].config.customLegend

                    setTimeout(function () {
                        DropChart.functions.render(DropChart.id_chart, false, true, 'dataUsing');
                    }, 500);
                }
                break;
            case 'useFirstRowAsLabels':
                if ($(this).is(":checked")) {
                    currentChart.config.useFirstRowAsLabels = true;
                } else {
                    currentChart.config.useFirstRowAsLabels = false;
                }
                DropChart.functions.render(DropChart.id_chart, false, true, 'firstRowAsLabels');
                break;
            case 'legend_display':
                if ($(this).is(":checked")) {
                    currentChart.config.legend_display = true;
                } else {
                    currentChart.config.legend_display = false;
                }
                DropChart.functions.render(DropChart.id_chart, false, true, 'legend_display');
                break;
            case 'useFirstRowAsGraph':
                if ($(this).is(":checked")) {
                    currentChart.config.useFirstRowAsGraph = true;
                    let axisColumn = currentChart.config?.axisColumn || null
                    let text = ''
                    if (axisColumn !== null && typeof currentChart.data[axisColumn] !== 'undefined') {
                        text = currentChart.data[axisColumn][0] || ''
                    }
                    currentChart.config.customText.unshift(text)
                } else {
                    currentChart.config.useFirstRowAsGraph = false;
                    currentChart.config.customText.shift()
                }
                DropChart.functions.render(DropChart.id_chart, false, true, 'firstRowAsGraph');
                break;
            case 'width':
                currentChart.config.width = parseInt($(this).val());
                DropChart.functions.render(DropChart.id_chart, true, true, 'width');
                break;
            case 'height':
                currentChart.config.height = parseInt($(this).val());
                DropChart.functions.render(DropChart.id_chart, true, true, 'height');
                break;
            case 'chart_align':
                currentChart.config.chart_align = $(this).val();
                if (currentChart.config.chart_align !== 'none') {
                    wptm_element.wptmContentChart.css('text-align', currentChart.config.chart_align);
                } else {
                    wptm_element.wptmContentChart.css('text-align', 'left');
                }

                DropChart.functions.save();
                break;
            case 'dataset_select':
                let line_name = parseInt($(this).val());
                var $dataset_color = wptm_element.chartTabContent.find('.controls[name="dataset_color"] input.minicolors');
                currentChart.change_dataset_select = true;
                if (currentChart.type == "Line" || currentChart.type == "Bar" || currentChart.type == "Radar") {
                    let colors = currentChart.config.colors.split(",")
                    let numberColor = line_name % colors.length
                    $dataset_color.wpColorPicker('color', colors[numberColor]);
                    // if (currentChart.config.colors.split(",").length > line_name) {
                    //     $dataset_color.wpColorPicker('color', currentChart.config.colors.split(",")[line_name]);
                    // } else {
                    //     $dataset_color.wpColorPicker('color', "");
                    // }
                } else {
                    let colors = currentChart.config.pieColors.split(",")
                    let numberColor = line_name % colors.length
                    $dataset_color.wpColorPicker('color', colors[numberColor]);
                    // if (currentChart.config.pieColors.split(",").length > line_name) {
                    //     $dataset_color.wpColorPicker('color', currentChart.config.pieColors.split(",")[line_name]);
                    // } else {
                    //     $dataset_color.wpColorPicker('color', "");
                    // }
                }
                break;
            case 'dataset_color':
                let index = parseInt(wptm_element.chartTabContent.find('.controls[name="dataset_select"] select').val());
                if (currentChart.type == "Line" || currentChart.type == "Bar" || currentChart.type == "Radar") {
                    var colors = currentChart.config.colors.split(",");
                    colors[index] = $(this).val();
                    currentChart.config.colors = colors.join(",");
                } else {
                    var pieColors = currentChart.config.pieColors.split(",");
                    if (pieColors.length <= index) {
                        var maxLabels = DropChart.labels.length;
                        var maxPieColors = pieColors.length;
                        var i;
                        for (i = 0; i < maxLabels; i++) {
                            pieColors[i] = pieColors[i % maxPieColors];
                        }
                    }
                    pieColors[index] = $(this).val();
                    currentChart.config.pieColors = pieColors.join(",");
                }

                if (currentChart.change_dataset_select === true) {
                    currentChart.change_dataset_select = false;
                } else {
                    DropChart.functions.render(DropChart.id_chart, true, true, 'color');
                }
                break;
            default:
                break;
        }
        return false
    });

    $('#wptm_chart .chart_type').unbind('click').bind('click', function (e) {
        e.preventDefault()

        if (DropChart.optionsChanged !== true) {
            return;
        }
        changeStyleChart($(this).data('id'));
        return false
    });

    $('#wptm_chart .change_ranger').unbind('click').bind('click', function (e) {
        if (DropChart.optionsChanged !== true) {
            return;
        }
        e.preventDefault()

        let valueRange = wptm_element.chartTabContent.find('.cellRangeLabelAlternate').val()
        wptm_element.chartTabContent.find('.cellRangeLabelAlternate').removeClass('wptm_error')
        valueRange = valueRange.replace(/[ ]+/g, "").toUpperCase();
        let arrayRanges = valueRange.split(";");
        let selections = [];

        if (arrayRanges.length > 0) {
            try{
                for (let i = 0; i < arrayRanges.length; i++) {
                    let arrayRange = arrayRanges[i].split(":")
                    if (arrayRange[0].replace(/[ |A-Za-z]+/g, "") === '') {//value A:B
                        selections.push([
                            0,
                            tableFunction.convertStringToNumber(arrayRange[0].split(/[ |1-9]+/g)[0]) - 1,
                            Wptm.max_row - 1,
                            tableFunction.convertStringToNumber(arrayRange[1].split(/[ |1-9]+/g)[0]) - 1
                        ]);
                    } else if (arrayRange[0].replace(/[ |1-9]+/g, "") === '') {//value 8:9
                        selections.push([arrayRange[0] - 1, 0, arrayRange[1] - 1, Wptm.max_Col - 1]);
                    } else {
                        let selection = []
                        selection.push(parseInt(arrayRange[0].split(/[ |A-Za-z]+/g)[1]) - 1);
                        selection.push(tableFunction.convertStringToNumber(arrayRange[0].split(/[ |1-9]+/g)[0]) - 1);
                        selection.push(parseInt(arrayRange[1].split(/[ |A-Za-z]+/g)[1]) - 1);
                        selection.push(tableFunction.convertStringToNumber(arrayRange[1].split(/[ |1-9]+/g)[0]) - 1);
                        selections.push(selection);
                    }
                }
            } catch (e) {
                wptm_element.chartTabContent.find('.cellRangeLabelAlternate').addClass('wptm_error')
            }

        }

        if (selections.length > 0) {
            DropChart.datas[DropChart.id_chart].selections = $.extend([], [], selections)
            changerRangeChart();
        }
        return false
    });

    $('#wptm_chart .customLabelXaxis').find('input').unbind('change').bind('change', function(event) {//CTRL + S
        // if (event.keyCode === 13 || event.key === "Enter") {
            let customText = []
            $('#wptm_chart .customLabelXaxis').find('input:not(.wptm_default)').each(function (i, v) {
                customText.push($(v).val())
            })
            DropChart.datas[DropChart.id_chart].config.customText = customText

            DropChart.functions.render(DropChart.id_chart, true, true, 'customLabel')
        // }
    })

    wptm_element.chartTabContent.find('.customLegendText').find('input.customLegend').unbind('change').bind('change', function(event) {//CTRL + S
        // if (event.keyCode === 13 || event.key === "Enter") {
            let customText = []
        wptm_element.chartTabContent.find('.customLegendText').find('input.customLegend').each(function (i, v) {
            customText.push($(v).val())
        })
        DropChart.datas[DropChart.id_chart].config.customLegend = customText

        DropChart.functions.render(DropChart.id_chart, true, true, 'customLegend')
        // }
    })

    $('#wptm_chart .y_axis_line').find('input').unbind('keydown').bind('keydown', function(event) {//CTRL + S
        clearTimeout(listLine)
        let that = $(event.target)
        listLine = setTimeout(function () {
            if (that.hasClass('stepSize'))
                DropChart.datas[DropChart.id_chart].config.stepSize = that.val() || null

            if (that.hasClass('minY'))
                DropChart.datas[DropChart.id_chart].config.minY = that.val() || null

            DropChart.functions.render(DropChart.id_chart, true, true, 'y_axis_line')
        }, 500);
    })

    setTimeout(function () {
        DropChart.optionsChanged = true;
    }, 100)
}

function changeStyleChart(charttype_id) {
    var $ = jquery;
    var id_chart = DropChart.id_chart;
    $.ajax({
        url: wptm_ajaxurl + "view=charttype&format=json&id=" + charttype_id,
        type: 'POST',
        data: {}
    }).done(function (data) {
        if (typeof (data) === 'object') {
            //local save
            if (data.config !== '') {
                $.extend(DropChart.datas[id_chart].config, $.parseJSON(data.config));
            }
            DropChart.datas[id_chart].type = data.name;

            setTimeout(function () {
                DropChart.functions.render(id_chart, false, true, 'changeStyleCHart');
            }, 200)
        }
    });
}

function changerRangeChart() {
    var $ = jquery;
    var id_chart = DropChart.id_chart;
    var dataChart = DropChart.datas[id_chart];

    var dataCell = DropChart.functions.validateChartData(dataChart?.selections || null, dataChart?.config?.dataUsing);
    if (dataCell === false) {
        tableFunction.wptmBootbox('', wptmText.CHART_INVALID_DATA, true, false);
    } else {
        dataChart.data = dataCell[0];
        dataChart.selections = dataCell[1];
        dataChart.chartDataRaw = dataCell[2];
        delete dataChart.config.axisColumn
        delete dataChart.config.linesData
        delete dataChart.config.axisColumn
        delete dataChart.config.customLegend

        DropChart.changer = true;
        DropChart.functions.render(id_chart, false, true, 'rangeChart');
    }
}

//dataSets = [dataCreateChart, dataForTooltip, graphLabel, xAxisLabels]
function convertForPie(dataSets, customText, useFirstRowAsLabels, useFirstRowAsGraph, colors, dataUsing, ctx) {
    var result = {};
    let colorList = [];
    result.datasets = [];

    let numberLine = dataSets[2].length, countDatasets = dataSets[3].length, dataSet, pieColors;

    let i2 = 0
    // if (customText !== null && 0 < customText.length && customText.length < countDatasets) {
    //     customText.unshift(dataSets[3][0])
    // }
    jQuery.each(dataSets[0], function (i, dataCell) {
    // for (let i = 0; i < numberLine; i++) {
        dataSet = jquery.extend({}, {})
        dataSet.label = dataSets[2][i2]

        if (dataSets[0][i] === 0) {
            dataSet.dataForTooltip = []
        } else {
            dataSet.dataForTooltip = dataSets[1][i]
        }
        // dataSet.currency_symbol = dataSets.currency_symbol[i];
        // if (typeof dataSets.data2 !== 'undefined' && typeof dataSets.data2[i] !== 'undefined') {
        //     dataSet.data_format = dataSets.data2[i];
        // } else {
        //     dataSet.data_format = dataSets.data[i];
        // }
        result.labels = jquery.extend([], []);
        pieColors = jquery.extend({}, {});

        if (dataUsing === 'PolarArea' || dataUsing === 'Pie' || dataUsing === 'Doughnut') {
            dataSet.highlight = [];
            dataSet.backgroundColor = [];
            dataSet.borderColor = [];
            dataSet.pointBackgroundColor = [];
            dataSet.pointColor = [];
            dataSet.pointBorderColor = [];
            dataSet.pointHighlightFill = [];
        }

        for (let j = 0; j < countDatasets; j++) {
            if (typeof dataSet.data === 'undefined') {
                dataSet.data = [];
            }
            if (dataSets[0][i] === 0) {
                dataSet.data.push(0)
                dataSet.dataForTooltip.push(0)
            } else {
                dataSet.data.push(dataSets[0][i][j]);//data da duoc remove tu truoc
            }

            var text = ''

            if (useFirstRowAsLabels) {
                text = dataSets[3][j]

                // console.log(customText, useFirstRowAsGraph, useFirstRowAsGraph ? j : (j + 1))
                if (customText !== null && typeof customText[j] !== 'undefined') {
                    text = customText[j]
                }
            }
            result.labels.push(text)

            if (dataUsing === 'PolarArea' || dataUsing === 'Pie' || dataUsing === 'Doughnut') {
                if (dataUsing === 'PolarArea') {
                    [pieColors, colorList[j]] = getStyleSet(j, colors, 0.5);
                } else {
                    [pieColors, colorList[j]] = getStyleSet(j, colors);
                }

                dataSet.highlight.push(pieColors.highlight);
                dataSet.backgroundColor.push(pieColors.backgroundColor);
                dataSet.borderColor.push(pieColors.borderColor);
                dataSet.pointBackgroundColor.push(pieColors.pointBackgroundColor);
                dataSet.pointColor.push(pieColors.pointColor);
                dataSet.pointBorderColor.push(pieColors.pointBorderColor);
                dataSet.pointHighlightFill.push(pieColors.pointHighlightFill);
            }
        }
        if (dataUsing === 'Bar' || dataUsing === 'Radar' || dataUsing === 'Line') {
            if (dataUsing === 'Radar' || dataUsing === 'Line') {
                [pieColors, colorList[i2]] = getStyleSet(i2, colors, 0.5);
            } else {
                [pieColors, colorList[i2]] = getStyleSet(i2, colors);
            }
            dataSet = jquery.extend({}, dataSet, pieColors);
            dataSet.fill = true;

            //color by line value
            // const skipped = (ctx, value) => ctx.p0.skip || ctx.p1.skip ? value : undefined;
            // const down = (ctx, value) => ctx.p0.parsed.y > ctx.p1.parsed.y ? value : undefined;
            // dataSet.segment = {
            //     borderColor: ctx => skipped(ctx, 'rgb(0,0,0,0.2)') || down(ctx, 'rgb(192,75,75)'),
            //     borderDash: ctx => skipped(ctx, [6, 6]),
            // }
        }
        result.datasets[i2] = dataSet;
        result.datasets[i2].lineNumber = i
        i2++
    })

    return [result, colorList.join(",")];
}

function getStyleSet(i, colors, opacity = null) {
    var styleSet = {};

    var color = getColor(i, colors) || '#935a5a';
    if (color != "") {
        // styleSet.lineTension = 0.2;
        styleSet.highlight = DropChart.helper.ColorLuminance(color, 100);
        styleSet.backgroundColor = DropChart.helper.convertHex(color, 90);
        styleSet.borderColor = DropChart.helper.convertHex(color, 100);
        styleSet.pointBackgroundColor = DropChart.helper.convertHex(color, 100);
        if (opacity !== null) {
            styleSet.highlight = DropChart.helper.ColorLuminance(color, 100 * opacity);
            styleSet.backgroundColor = DropChart.helper.convertHex(color, 90 * opacity);
            styleSet.borderColor = DropChart.helper.convertHex(color, 100 * opacity);
            styleSet.pointBackgroundColor = DropChart.helper.convertHex(color, 100 * opacity);
        }
        styleSet.pointColor = "#fff";
        styleSet.pointHighlightFill = "#fff";
        styleSet.pointBorderColor = DropChart.helper.convertHex(color, 100);
    }

    return [styleSet, color];
}

function getColor(i, colors) {
    var result = "";
    var arrColors = colors.split(",");
    var len = arrColors.length;
    if (len > 0) {
        result = arrColors[i % len];
        if (!result) {
            arrColors = DropChart.default.colors.split(",")

            result = arrColors[i % arrColors.length];
        }
    }

    return result;
}

function addChartStyles(dataSets, colors) {
    var result = [];
    var dataset, styleSet;

    for (var i = 0; i < dataSets.length; i++) {
        dataset = dataSets[i];
        styleSet = getStyleSet(i, colors);
        jquery.extend(dataset, styleSet);
        result.push(dataset);
    }

    return result;
}

var wptmCreateNewChart = false

DropChart.functions.addChart = function () {
    if ((typeof idTable !== 'undefined' && idTable !== '') || table_function_data.selectionSize >= 1) {
        var $ = jquery;

        let dataCell = DropChart.functions.validateChartData(null, 'column');

        if (dataCell === false) {
            tableFunction.wptmBootbox('', wptmText.CHART_INVALID_DATA, true, false);
        } else {
            let [chartData, selections, chartDataRaw] = dataCell
            let list_columns = Object.keys(chartData)
            let config = {linesData: list_columns, useFirstRowAsGraph: true, axisColumn: list_columns[0]}
            let [dataCreateChart, dataForTooltip, countDataValidate, graphLabel, xAxisLabels] = DropChart.functions.getDataSets2(chartData, config, selections, chartDataRaw);
            if (countDataValidate < 1) {//data not validate
                tableFunction.wptmBootbox('', wptmText.CHART_INVALID_DATA, true, false);
            } else {
                $.ajax({
                    url: wptm_ajaxurl + "task=chart.add&id_table=" + idTable,
                    type: "POST",
                    dataType: "json",
                    data: {datas: JSON.stringify(selections)},
                    beforeSend: function () {
                        wptm_element.settingTable.find('.ajax_loading').addClass('loadding').removeClass('wptm_hiden');
                        wptm_element.primary_toolbars.find('.new_chart_menu').closest('li').addClass('menu_loading');
                    },
                    success: function (datas) {
                        wptm_element.settingTable.find('.ajax_loading').removeClass('loadding').addClass('wptm_hiden');
                        if (datas.response === true) {
                            var count = $('#list_chart').find('li.chart-menu').length;
                            var data_chart = datas.datas;
                            $('#list_chart').append('<li class="chart-menu" data-id="' + data_chart.id + '"><a>' + data_chart.title + '</a></li>');
                            DropChart.datas[data_chart.id] = {
                                config: $.extend({}, {}, DropChart.default),
                                data: chartData,
                                selections: selections,
                                chartDataRaw: chartDataRaw,
                                title: data_chart.title,
                                type: "Bar",
                            };
                            wptmCreateNewChart = true
                            wptm_chart(true);
                            tableFunction.showChartOrTable(true, $('#list_chart').find('.chart-menu').eq(count));
                        } else {
                            alert(datas.response);
                        }
                    },
                    error: function (jqxhr, textStatus, error) {
                        wptm_element.settingTable.find('.ajax_loading').removeClass('loadding').addClass('wptm_hiden');
                        tableFunction.wptmBootbox('', textStatus + " : " + error, true, false);
                    }
                });
            }
        }
    } else {
        tableFunction.wptmBootbox('', wptmText.CHART_INVALID_DATA, wptmText.GOT_IT, false);
    }
}

DropChart.functions.render = function (chart_id, re_render, save_chart, action) {
    var $ = jquery;
    DropChart.id_chart = chart_id;

    if (typeof DropChart.datas[chart_id] === 'undefined') {//data not validate
        $('<span class="">' + wptmText.warning_creat_chart + '<span>').appendTo(wptm_element.wptmContentChart);
        return false
    }
    let currentChart = DropChart.datas[chart_id];

    wptm_element.wptmContentChart.find(':not(.wptm_chart_' + chart_id + ')').remove()
    let $canvas = wptm_element.wptmContentChart.find('canvas.wptm_chart_' + chart_id);

    if (currentChart.config === null) {
        currentChart.config = $.extend({}, DropChart.default);
    }

    let list_columns = Object.keys(currentChart.data)
    currentChart.config.linesdataAvailable = Object.keys(currentChart.data)

    if (typeof currentChart.config.axisColumn === 'undefined'
        || typeof currentChart.data[currentChart.config.axisColumn] === 'undefined') {
        currentChart.config.axisColumn = list_columns[0]
    }

    if (typeof currentChart.config.linesData === 'undefined'
        || currentChart.config.linesData.length > currentChart.config.linesdataAvailable.length) {
        currentChart.config.linesData = $.extend([], [], list_columns);
        if (!currentChart.config?.useFirstRowAsGraph) {//old chart
            currentChart.config.linesData.shift()
        }
    }

    try {
        currentChart.config = $.extend({}, DropChart.default, currentChart.config);
    } catch (e) {
        currentChart.config = $.extend({}, DropChart.default, $.parseJSON(currentChart.config));
    }

    let [dataCreateChart, dataForTooltip, countDataValidate, graphLabel, xAxisLabels] = DropChart.functions.getDataSets2(currentChart.data, currentChart.config, currentChart.selections, currentChart.chartDataRaw);

    let createNewChart = wptmCreateNewChart
    wptmCreateNewChart = false
    if (countDataValidate < 1) {//data not validate
        if ($canvas.length < 1) {
            $('<span class="">' + wptmText.warning_creat_chart + '<span>').appendTo(wptm_element.wptmContentChart);
        }
    }

    if (countDataValidate < 1 && currentChart.config.linesdataAvailable?.length >= 1 && countDataValidate < currentChart.config.linesdataAvailable?.length) {
        // let linesData = []
        currentChart.config.linesData = $.extend([], [], currentChart.config.linesdataAvailable);
        currentChart.config.useFirstRowAsGraph = true;

        // for (let i = 0; i < currentChart.config.linesData?.length; i++) {
        //     if (dataCreateChart[currentChart.config.linesData[i]] !== 0)
        //         linesData.push(currentChart.config.linesData[i])
        // }
        // currentChart.config.linesData = $.extend([], [], linesData);

        let dataGetDataSets2 = DropChart.functions.getDataSets2(currentChart.data, currentChart.config, currentChart.selections, currentChart.chartDataRaw);

        [dataCreateChart, dataForTooltip, countDataValidate, graphLabel, xAxisLabels] = dataGetDataSets2
    }


    if (countDataValidate < 1) {//data not validate
        return false
    }

    if (graphLabel !== null && graphLabel.length > 0) {
        currentChart.config.customLegendDefault = $.extend([], [], graphLabel)
        if (typeof currentChart.config.customLegend !== 'undefined') {
            graphLabel = $.extend([], graphLabel, currentChart.config.customLegend);
        }
        currentChart.config.customLegend = $.extend([], [], graphLabel)

        //save line name
        currentChart.config.graphLabel = graphLabel
    }

    wptm_element.wptmContentChart.find('span').remove()

    //destroy old chart version
    if (DropChart.chart) {
        DropChart.chart.clear();
        DropChart.chart.destroy();
    }

    //hiden all canvas except chart_id
    wptm_element.wptmContentChart.find('.canvas').addClass('wptm_hiden');
    // if ($canvas.length > 1)
    //     $canvas.remove();
    if ($canvas.length < 1 || re_render) {
        $canvas.remove();
        $canvas = $('<canvas class="canvas wptm_chart_' + chart_id + '" width="' + currentChart.config.width + '" height="' + currentChart.config.height + '"   ><canvas>')
            .appendTo(wptm_element.wptmContentChart);
    } else {
        $canvas.width(currentChart.config.width);
        $canvas.height(currentChart.config.height);
    }
    $canvas.removeClass('wptm_hiden');
    var ctx = $canvas.get(0).getContext("2d");

    

    let chartData, colorList;
    switch (currentChart.type) {
        case 'PolarArea':
        case 'Pie':
        case 'Doughnut':
            [chartData, colorList] = convertForPie([dataCreateChart, dataForTooltip, graphLabel, xAxisLabels], currentChart.config.customText || null, currentChart.config.useFirstRowAsLabels, currentChart.config.useFirstRowAsGraph, currentChart.config.pieColors, currentChart.type, ctx);
            currentChart.config.pieColors = colorList
            break;
        case 'Bar':
        case 'Radar':
        case 'Line':
        default:
            [chartData, colorList] = convertForPie([dataCreateChart, dataForTooltip, graphLabel, xAxisLabels], currentChart.config.customText || null, currentChart.config.useFirstRowAsLabels,  currentChart.config.useFirstRowAsGraph, currentChart.config.colors, currentChart.type, ctx);
            currentChart.config.colors = colorList
            break;
    }

    if (typeof chartData.labels !== 'undefined' && currentChart.config.useFirstRowAsLabels) {
        currentChart.config.customText = $.extend([], [], chartData.labels)
    }

    DropChart.labels = chartData.labels;
    DropChart.datasets = chartData.datasets;
    if (DropChart.datasets.length > 0) {
        currentChart.config.plugins = {};
        currentChart.config.plugins.tooltip = {
        // DropChart.config.tooltips = {
            enabled: true, callbacks: {
                label: function (tooltipItems) {
                    var labelTitle = '';
                    var label = '';
                    var data = tooltipItems.chart.config._config.data;
                    var type = tooltipItems.chart.config._config.type;
                    var useFirstRowAsGraph = tooltipItems.chart.config._config?.options?.useFirstRowAsGraph || false;

                    if (data.useFirstRowAsLabels) {
                        switch (type) {
                            case 'polarArea':
                                labelTitle += data.labels[tooltipItems.dataIndex];
                                if (!useFirstRowAsGraph) {
                                    label += data.datasets[tooltipItems.datasetIndex].label
                                }
                                if (label) {
                                    label += ': ';
                                }
                                break;
                            case 'pie':
                            case 'doughnut':
                                if (tooltipItems.label !== '') {
                                    labelTitle += tooltipItems.label;
                                } else {
                                    labelTitle += data.labels[tooltipItems.dataIndex];
                                }
                                if (!useFirstRowAsGraph) {
                                    label += data.datasets[tooltipItems.datasetIndex].label
                                }
                                if (label) {
                                    label += ': ';
                                }
                                break;
                            case 'bar':
                            case 'radar':
                            case 'line':
                            default:
                                if (!useFirstRowAsGraph) {
                                    if (data.datasets.length > 1) {
                                        label = data.datasets[tooltipItems.datasetIndex].label || '';
                                    }
                                    if (label) {
                                        label += ': ';
                                    }
                                }
                            break;
                        }
                    }
                    if (tooltipItems.raw !== 0) {
                        if (typeof tooltipItems.dataset.dataForTooltip !== 'undefined' && typeof tooltipItems.dataset.dataForTooltip[tooltipItems.dataIndex] !== 'undefined') {
                            label += tooltipItems.dataset.dataForTooltip[tooltipItems.dataIndex];
                        } else {
                            label += tooltipItems.dataset.data[tooltipItems.dataIndex];
                        }
                    } else {
                        label += '0'
                    }
                    return labelTitle !== '' ? [labelTitle, label] : label;
                }
            }
        };
    } else {
        tableFunction.wptmBootbox('', wptmText.CHART_INVALID_DATA, wptmText.GOT_IT, false);
    }

    try {
        chartData.useFirstRowAsLabels = currentChart.config.useFirstRowAsLabels;
        currentChart.config.scaleBeginAtZero = false;//fix the original value of the shaft
        currentChart.config.responsive = false;//fix the original value of the shaft
        if (typeof currentChart.config.scales !== 'undefined') {
            delete currentChart.config.scales;//fix the chart shows the wrong axes
            // delete DropChart.config.scales;//fix the chart shows the wrong axes
            delete currentChart.config.cubicInterpolationMode;
            delete currentChart.config.tension;
        }

        let scales = null
        if (currentChart.config.stepSize && currentChart.config.stepSize > 0)
            scales = {ticks: {stepSize: currentChart.config.stepSize}}

        if (currentChart.config.minY && currentChart.config.minY >= 0)
            scales = scales !== null ? {...scales, ...{min: parseFloat(currentChart.config.minY)}} : {min: parseFloat(currentChart.config.minY)}

        // if (scales !== null)
        //     scales = {...scales, ...{beginAtZero: false}}
        //https://www.chartjs.org/docs/3.1.1/axes/cartesian/linear.html

        let wptmChartConfig = $.extend({}, {}, currentChart.config)
        if (currentChart.config.legend_display === false) {
            wptmChartConfig.plugins.legend = {display: false}
        } else {
            wptmChartConfig.plugins.legend = {display: true}
        }
        delete wptmChartConfig.width
        delete wptmChartConfig.height
        switch (currentChart.type) {
            case 'PolarArea':
                DropChart.chart = new wptmChart(ctx, {
                    type: 'polarArea',
                    data: chartData,
                    options: wptmChartConfig
                });
                break;

            case 'Pie':
                DropChart.chart = new wptmChart(ctx, {
                    type: 'pie',
                    data: chartData,
                    options: wptmChartConfig
                });
                break;

            case 'Doughnut':
                DropChart.chart = new wptmChart(ctx, {
                    type: 'doughnut',
                    data: chartData,
                    options: wptmChartConfig
                });
                break;

            case 'Bar':
                if (scales !== null) {
                    wptmChartConfig.scales = {y: scales}
                    wptmChartConfig.scales.y.afterBuildTicks = function(axis) {
                        if (scales.ticks?.stepSize > 0 && axis.ticks[1].value - axis.ticks[0].value < axis.ticks[2].value - axis.ticks[1].value) {
                            axis.ticks = axis.ticks.map(function (tick) {
                                let newStep = tick.value
                                if (axis.start < tick.value) {
                                    let newStep1 = Math.floor((tick.value - axis.start)/ scales.ticks.stepSize)
                                    newStep = axis.start + (newStep1 + 1) * scales.ticks.stepSize
                                }
                                tick.value = newStep
                                return tick
                            });
                        }
                    }
                }
                DropChart.chart = new wptmChart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: wptmChartConfig
                });
                break;

            case 'Radar':
                DropChart.chart = new wptmChart(ctx, {
                    type: 'radar',
                    data: chartData,
                    options: wptmChartConfig
                });
                break;

            case 'Line':
            default:
                if (scales !== null) {
                    wptmChartConfig.scales = {y: scales}
                    wptmChartConfig.scales.y.afterBuildTicks = function(axis) {
                        if (scales.ticks?.stepSize > 0 && axis.ticks[1].value - axis.ticks[0].value < axis.ticks[2].value - axis.ticks[1].value) {
                            axis.ticks = axis.ticks.map(function (tick) {
                                let newStep = tick.value
                                if (axis.start < tick.value) {
                                    let newStep1 = Math.floor((tick.value - axis.start)/ scales.ticks.stepSize)
                                    newStep = axis.start + (newStep1 + 1) * scales.ticks.stepSize
                                }
                                tick.value = newStep
                                return tick
                            });
                        }
                    }
                }
                wptmChartConfig.cubicInterpolationMode = 'default';
                wptmChartConfig.tension = 0.3;

                DropChart.chart = new wptmChart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: wptmChartConfig
                });
                break;
        }

        DropChart.optionsChanged = false;
        //update val of selector to chart option

        if (!re_render) {
            updateOption(currentChart);

            setTimeout(function () {
                initChartObserver();
            }, 200)
        } else {
            DropChart.optionsChanged = true;
        }

        if (save_chart) {
            DropChart.functions.save();
        }
    } catch (e) {}
}

DropChart.functions.save = function () {
    if (!(Wptm.can.edit || (Wptm.can.editown && data.author === Wptm.author))) {
        return;
    }

    var $ = jQuery;
    let config = DropChart.datas[DropChart.id_chart].config
    var jsonVar = {
        jform: {
            type: DropChart.datas[DropChart.id_chart].type,
            config: JSON.stringify(config)
        },
        id: DropChart.id_chart
    };

    if (DropChart.changer === true) {
        jsonVar.jform.datas = JSON.stringify(DropChart.datas[DropChart.id_chart].selections);
        DropChart.changer = false;
    }
    var $saving = $('.wptm_top_chart .saving');
    $saving.html(wptmText.SAVING);
    $saving.animate({'opacity': '1'}, 200);
    $.ajax({
        url: wptm_ajaxurl + "task=chart.save",
        dataType: "json",
        type: "POST",
        data: jsonVar,
        success: function (datas) {
            if (datas.response === true) {
                $saving.html(wptmText.ALL_CHANGES_SAVED).delay(500).animate({'opacity': '0'}, 200);
            } else {
                $saving.animate({'opacity': '0'}, 200);
                tableFunction.wptmBootbox('', datas.response, true, false);
            }
        },
        error: function (jqxhr, textStatus, error) {
            $saving.animate({'opacity': '0'}, 200);
            tableFunction.wptmBootbox('', textStatus + " : " + error, true, false);
        }
    });
}

DropChart.functions.getDataSets2 = function (dataCells, config, selections, chartDataRaw) {
    DropChart.currency_symbol = typeof Wptm.style.table.currency_symbol === 'undefined'
        ? default_value.currency_symbol
        : Wptm.style.table.currency_symbol;
    DropChart.thousand_symbol = typeof Wptm.style.table.thousand_symbol === 'undefined'
        ? default_value.thousand_symbol
        : Wptm.style.table.thousand_symbol;
    DropChart.decimal_symbol = typeof Wptm.style.table.decimal_symbol === 'undefined'
        ? default_value.decimal_symbol
        : Wptm.style.table.decimal_symbol;

    let datasReturn = {}
    let countDataValidate = 0
    let datasraw = {}
    let xAxisLabels = jQuery.extend([], [], dataCells[config.axisColumn])

    if (!config.useFirstRowAsGraph) {
        xAxisLabels.shift()
    }

    let graphLabel = []

    jQuery.each(dataCells, function (id, dataCell) {
        // if (!config.useFirstRowAsGraph && id === config.axisColumn) {
        //     return;
        // }
        if (!(config.linesData.indexOf(id) != -1)) {
            return;
        }

        let floatValues = [], cellDatas = []
        let count_cell_in_line = 0
        let isNull = 0
        let percentValues = []
        let percentCount = 0
        jQuery.each(dataCell, (i, v) => {
            count_cell_in_line++
            let check = v, floatValue = ''

            let check1 = check.replaceAll(' ', '');
            check = check1.replace(DropChart.currency_symbol, '');
            check = check.replace(DropChart.thousand_symbol, '');
            check = check.replace(DropChart.decimal_symbol, '.');

            floatValue = check.replace(/[^0-9|^%|\\.|-]/g, '');//value

            check = check.replace(/[0-9|\\.|,|-| |%]/g, '');

            if (check !== '' || check1 === '') {//have strange characters or is null
                isNull++
            }

            if (floatValue.includes('%')) {
                percentCount++
                floatValues.push(floatValue.replace(/[%]/g, ''))
            } else {
                floatValue = floatValue !== '' ? floatValue : 0
                floatValues.push(floatValue)
            }

            cellDatas.push(chartDataRaw[id][i])
        })

        graphLabel.push(cellDatas[0])

        //remove first RowAsLabels
        if (!config.useFirstRowAsGraph) {
            floatValues.shift()
            cellDatas.shift()
        }

        if (percentCount + 1 >= count_cell_in_line) {//percent line

        }

        if (isNull === count_cell_in_line) {
            datasReturn[id] = 0
        } else {
            datasReturn[id] = floatValues
            countDataValidate++
        }
        datasraw[id] = cellDatas
    })

    return [datasReturn, datasraw, countDataValidate, graphLabel, xAxisLabels];
}

//get valid chart data area
// return: valid data , col indexes, row indexes
DropChart.functions.getValidChartData = function (cellsData) {
    var i, tempIndexes;
    var results = [];
    var resultIndexes = [];
    var rowIndexes = [];
    for (i = 0; i < cellsData[0].length; i++) {
        resultIndexes.push(i);
    }

    for (i = 0; i < cellsData.length; i++) {
        if (DropChart.helper.isValidRow(cellsData[i])) {
            results.push(cellsData[i]);
            rowIndexes.push(i);
            tempIndexes = DropChart.helper.getValidIndexes(cellsData[i]);
            resultIndexes = DropChart.helper.intersection(tempIndexes, resultIndexes);
        }
    }
    var tempArr = [];

    for (i = 0; i < results.length; i++) {
        tempArr = [];
        for (var j = 0; j < tempIndexes.length; j++) {
            tempArr.push(results[i][tempIndexes[j]]);
        }
        results[i] = tempArr;
    }
    return [results, resultIndexes, rowIndexes];
}

DropChart.functions.checkValidRowData = function (array) {
    return !array.some(function (value, index, array) {
        return value !== array[0];
    });
}

DropChart.functions.sizeSelection = function (selection, direction = null) {
    if (selection.length == 0) {
        return false;
    }

    if (selection[0] === selection[2] && selection[1] === selection[3]) {
        return false;
    }

    if (direction !== null && ((direction === 'col' && selection[0] + 1 >= selection[2]) || (direction === 'row' && selection[1] + 1 >= selection[3]))) {
        return false;
    }

    return true
}

DropChart.functions.validateChartData = function (selections = null, direction = null) {
    selections = selections === null ? table_function_data.selection : selections
    let chartData = {}, chartDataRaw = {}, chartDataPosition = []

    if (typeof selections[0][0] === 'string') {//old data
        let cell = selections[0][0].split(':')
        let lastSelections = selections[selections.length - 1]
        let cell2 = lastSelections[lastSelections.length - 1].split(':')
        selections = [[parseInt(cell[0]), parseInt(cell[1]), parseInt(cell2[0]), parseInt(cell2[1])]]
    }

    let max_value = 0
    for (let i = 0; i < _.size(selections); i++) {
        let selectCell = []
        let selection = selections[i]

        if (direction !== 'row') {//direction === col
            selectCell = [parseInt(selection[0]), parseInt(selection[1]), parseInt(selection[2]), parseInt(selection[3])]
        } else {//default row
            selectCell = [parseInt(selection[1]), parseInt(selection[0]), parseInt(selection[3]), parseInt(selection[2])]
        }

        max_value = (selectCell[2] - selectCell[0]) > max_value ? (selectCell[2] - selectCell[0]) : max_value
    }

    if (_.size(selections) == 1 && selections[0][1] === selections[0][3] && selections[0][0] === selections[0][2]) {
        return false
    }

    //select cells not validate
    for (let i = 0; i < _.size(selections); i++) {
        let selectCell = []
        let selection = selections[i]

        if (direction !== 'row') {//direction === col
            selectCell = [parseInt(selection[0]), parseInt(selection[1]), parseInt(selection[2]), parseInt(selection[3])]
        } else {//default row
            selectCell = [parseInt(selection[1]), parseInt(selection[0]), parseInt(selection[3]), parseInt(selection[2])]
        }

        for (let i2 = selectCell[1]; i2 <= selectCell[3]; i2++) {
            for (let i3 = selectCell[0]; i3 <= selectCell[2]; i3++) {
                chartData[i2] = typeof chartData[i2] === 'undefined' ? [] : chartData[i2]
                chartDataRaw[i2] = typeof chartDataRaw[i2] === 'undefined' ? [] : chartDataRaw[i2]

                if (direction !== 'row') {//direction === col
                    if (typeof Wptm.datas[i3] === 'undefined' || typeof Wptm.datas[i3][i2] === 'undefined') {
                        return false
                    }
                    if (typeof chartDataPosition[i3 + '!' + i2] === 'undefined') {
                        let customRendererData = customRenderer.render(
                            false,
                            false,
                            i3,
                            i2,
                            false,
                            (typeof Wptm.datas[i3][i2] !== 'undefined' && Wptm.datas[i3][i2] !== null) ? Wptm.datas[i3][i2] : '',
                            'cellProperties',
                            true
                        )

                        let [cell_value, raw_value] = customRendererData
                        cell_value = typeof cell_value === 'boolean' ? (cell_value ? 'true' : 'false') : cell_value

                        chartData[i2].push(raw_value || cell_value)
                        chartDataRaw[i2].push(cell_value)
                        chartDataPosition[i3 + ':' + i2] = cell_value
                    }
                } else {//default row
                    if (typeof Wptm.datas[i2] === 'undefined' || typeof Wptm.datas[i2][i3] === 'undefined') {
                        return false
                    }
                    if (typeof chartDataPosition[i2 + '!' + i3] === 'undefined') {
                        let customRendererData = customRenderer.render(
                            false,
                            false,
                            i2,
                            i3,
                            false,
                            (typeof Wptm.datas[i2][i3] !== 'undefined' && Wptm.datas[i2][i3] !== null) ? Wptm.datas[i2][i3] : '',
                            'cellProperties',
                            true
                        )

                        let [cell_value, raw_value] = customRendererData
                        cell_value = typeof cell_value === 'boolean' ? (cell_value ? 'true' : 'false') : cell_value

                        chartData[i2].push(raw_value || cell_value)
                        chartDataRaw[i2].push(cell_value)
                        chartDataPosition[i2 + ':' + i3] = cell_value
                    }
                }
            }
        }
    }

    //add missing value for line
    if (max_value >= 1 && Object.keys(chartDataRaw)?.length > 1) {
        jQuery.each(chartDataRaw, function (id, dataCell) {
            if (dataCell.length < max_value) {
                chartDataRaw[id] =  dataCell.concat(Array(max_value - dataCell.length).fill(0))
                chartData[id] =  chartData[id].concat(Array(max_value - dataCell.length).fill(0))
            }
        })
    }

    return [chartData, selections, chartDataRaw]
}

//check val of cells to chart of table
DropChart.functions.validateCharts = function (change) {
    var result = true;
    var $ = jQuery;
    var editCell = change[0] + ":" + change[1];

    $.each(DropChart.datas, function (chart_id, chart) {
        if (chart_id) {
            var cells = chart.data;
            if (DropChart.helper.inArrays(editCell, cells)) {
                var cellsData = [];
                for (var i = 0; i < cells.length; i++) {
                    var rowData = [];
                    for (var j = 0; j < cells[i].length; j++) {
                        if (cells[i][j] != editCell) {
                            rowData[j] = tableFunction.getCellData(cells[i][j]);
                        } else {
                            rowData[j] = change[3];//new value
                        }
                    }
                    cellsData[i] = rowData;
                }

                if (!validateDataForChart(cellsData)) {
                    result = false;
                }
            }
        }
    });

    return result;
}

function validateDataForChart(Cells) {
    var rValid, rCells, cValid, subCells, rsubCells;
    //Check row
    rValid = DropChart.helper.hasNumbericRow(Cells);
    if (!rValid) {
        //check column
        rCells = DropChart.helper.transposeArr(Cells);
        cValid = DropChart.helper.hasNumbericRow(rCells);
        if (!cValid) { //ignore first row and column

            subCells = DropChart.helper.removeFirstRowColumn(rCells);
            if (subCells.length <= 0) return false;

            rValid = DropChart.helper.hasNumbericRow(subCells);
            if (!rValid) {
                rsubCells = DropChart.helper.transposeArr(subCells);
                cValid = DropChart.helper.hasNumbericRow(rsubCells);
            }
        }
    }

    return (rValid || cValid);
}

DropChart.helper = {}

DropChart.helper.getStrangeCharacters2 = function (value) {
    var value1, value0;
    value0 = typeof value[1] !== 'undefined' ? value[1].toString().replaceAll(' ', '') : value[0].replaceAll(' ', '');
    value1 = value0.replace(DropChart.currency_symbol, '');
    value1 = value1.replace(DropChart.thousand_symbol, '');
    value1 = value1.replace(DropChart.decimal_symbol, '.');
    value[1] = value1.replace(/[^0-9|\\.|-]/g, '');//value

    value[2] = 0;//nan
    value[3] = 0;//currency_symbol
    value1 = value1.replace(/[0-9|\\.|,|-| ]/g, '');

    if (value1 !== '' || value[0] === '') {//have strange characters or is null
        value[2] = 1;
    }
    if (value[0] !== '' && (value[0].includes(DropChart.currency_symbol) || arguments[1] === true)) {
        value[3] = 1;
    }

    return value;
}

DropChart.helper.getStrangeCharacters = function (value) {
    var data = [], value1;
    value1 = value.replace(DropChart.currency_symbol, '');
    value1 = value1.replace(DropChart.thousand_symbol, '.');
    value1 = value1.replace(DropChart.decimal_symbol, '');
    data['value'] = value1.replace(/[^0-9|\\.|-]/g, '');
    data['NaN'] = 0;
    data['delete'] = 0;
    value1 = value1.replace(/[0-9|\\.|,|-| ]/g, '');
    if (value1 !== '' || value === '') {//have strange characters or is null
        data['NaN'] = 1;
        data['delete'] = 1;
    }
    if (data['value'] !== '' && value !== '' && value.includes(DropChart.currency_symbol)) {
        data['currency_symbol'] = 1;
    }

    return data;
}
//get index of valid number in the array
DropChart.helper.getValidIndexes = function (arr) {
    var currency_symbol = typeof Wptm.style.table.currency_symbol === 'undefined'
        ? default_value.currency_symbol
        : Wptm.style.table.currency_symbol;
    var thousand_symbol = typeof Wptm.style.table.thousand_symbol === 'undefined'
        ? default_value.thousand_symbol
        : Wptm.style.table.thousand_symbol;

    // var thousand_re = new RegExp(thousand_symbol,"g");
    var thousand_re = new RegExp('[' + thousand_symbol + ']', "g");
    var i, v, x1;
    var result = [];

    for (i = 0; i < arr.length; i++) {

        v = arr[i] ? arr[i].toString() : "";
        x1 = v.replace(currency_symbol, '');
        x1 = x1.replace(thousand_re, '');
        x1 = x1.replace(/[\\.|+|,| ]/g, '');
        x1 = x1.replace(/-/g, '');
        x1 = x1.replace(/[0-9]/g, '');
        if (x1 === '') {
            result.push(i);
        }
    }
    return result;
}
//get intersection values of two array
DropChart.helper.intersection = function (a, b) {
    var rs = [];
    for (var i = 0; i < a.length; i++) {
        if (b.indexOf(a[i]) != -1) {
            rs.push(a[i]);
        }
    }
    return rs;
};

DropChart.helper.isNumbericArray = function (arr) {
    var valid = true;
    for (var c = 0; c < arr.length; c++) {
        if (isNaN(arr[c])) {
            valid = false;
        }
    }

    return valid;
};

DropChart.helper.convertToNumber = function (arr) {
    var result = [];
    for (var c = 0; c < arr.length; c++) {
        // if (!isNaN(arr[c])) {
        if (typeof arr[c] === 'string') {
            arr[c] = tableFunction.stringReplace(arr[c], false);
        }
        result.push(arr[c]);
        // }
    }
    return result;
};

DropChart.helper.transposeArr = function (arr) {
    if (typeof arr === "undefined" || arr.length === 0) {
        return [];
    }
    return Object.keys(arr[0]).map(function (c) {
        return arr.map(function (r) {
            return r[c];
        });
    });
}

DropChart.helper.inArrays = function (c, cells) {
    var result = false;
    for (var r = 0; r < cells.length; r++) {
        if (cells[r].indexOf(c) > -1) {
            result = true;
        }
    }

    return result;
}
// there is at least 2 number
DropChart.helper.isValidRow = function (arr) {
    var currency_symbol = typeof Wptm.style.table.currency_symbol === 'undefined'
        ? default_value.currency_symbol
        : Wptm.style.table.currency_symbol;
    var thousand_symbol = typeof Wptm.style.table.thousand_symbol === 'undefined'
        ? default_value.thousand_symbol
        : Wptm.style.table.thousand_symbol;

    var thousand_re = new RegExp('[' + thousand_symbol + ']', "g");
    var i, v, x1, count = 0;

    for (i = 0; i < arr.length; i++) {
        v = arr[i] ? arr[i].toString() : "";
        if (v !== '') {
            x1 = v.replace(currency_symbol, '');
            x1 = x1.replace(thousand_re, '');
            x1 = x1.replace(/[\\.|+|,| ]/g, '');
            x1 = x1.replace(/-/g, '');
            x1 = x1.replace(/[0-9]/g, '');
            if (x1 === '') {
                count++;
            }
        }
    }
    return (count > 1);
}

DropChart.helper.hasNumbericRow = function (Cells) {
    var rValid = false;
    if (typeof Cells === "undefined") {
        return false;
    }

    for (var r = 0; r < Cells.length; r++) {
        if (DropChart.helper.isValidRow(Cells[r])) {
            rValid = true;
            break;
        }
    }
    return rValid;
}

// check val int cel in row
DropChart.helper.hasNumbericRowCol = function (Cells) {
    var rValid = true;
    var rNaN = 0;
    if (typeof Cells === "undefined") {
        return false;
    }
    for (var r = 0; r < Cells.length; r++) {
        var valid = true;
        if (typeof (Cells[r]) === 'string' && isNaN(parseInt(tableFunction.stringReplace(Cells[r], false)))) {
            valid = false;
        }

        if (!valid) {
            rNaN++;
        }
    }

    if (rNaN === Cells.length) {
        rValid = false;
    }
    return rValid;
}

DropChart.helper.getRowData = function (row) {
    var data = [];
    for (var j = 0; j < row.length; j++) {
        data[j] = tableFunction.getCellData(row[j]);
    }

    return data;
}

DropChart.helper.getRangeData = function (cells) {
    var datas = [];
    for (var i = 0; i < cells.length; i++) {
        datas[i] = DropChart.helper.getRowData(cells[i]);
    }

    return datas;
}

DropChart.helper.getCellRangeLabel = function (cells) {
    var data = [];
    var firstCell = cells[0][0];
    var lastRow = cells[cells.length - 1];
    var lastCell = lastRow[lastRow.length - 1];

    var pos = firstCell.split(":");
    data[0] = parseInt(pos[0]);
    data[1] = parseInt(pos[1]);

    pos = lastCell.split(":");
    data[2] = parseInt(pos[0]);
    data[3] = parseInt(pos[1]);
    return data;
}

DropChart.helper.canSwitchRowCol = function (cellsData) {
    var result = -1;
    var rValid = false;
    var cValid = false;
    if (DropChart.helper.hasNumbericRow(cellsData)) {
        rValid = true;
    }
    var rCellsData = DropChart.helper.transposeArr(cellsData);
    if (DropChart.helper.hasNumbericRow(rCellsData)) {
        cValid = true;
    }

    if (rValid && cValid) {
        result = 3;
    } else if (rValid) {
        result = 2;
    } else if (cValid) {
        result = 1;
    } else {
        // invalid data
        result = -1;
    }

    return result;
}

DropChart.helper.removeFirstRowColumn = function (cells) {
    cells.shift();
    if (cells.length > 0) {
        cells = DropChart.helper.transposeArr(cells);
        cells.shift();
    }

    return cells;
}

DropChart.helper.getEmptyArray = function (len) {
    var result = [];
    for (var i = 0; i < len; i++) {
        result[i] = "    ";
    }
    return result;
}

DropChart.helper.convertHex = function (hex, opacity) {
    hex = hex.replace('#', '');
    var r = parseInt(hex.substring(0, 2), 16);
    var g = parseInt(hex.substring(2, 4), 16);
    var b = parseInt(hex.substring(4, 6), 16);

    return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity / 100 + ')';
}

DropChart.helper.ColorLuminance = function (hex, lum) {

    // validate hex string
    hex = String(hex).replace(/[^0-9a-f]/gi, '');
    if (hex.length < 6) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    lum = lum || 0;

    // convert to decimal and change luminosity
    var rgb = "#", c, i;
    for (i = 0; i < 3; i++) {
        c = parseInt(hex.substr(i * 2, 2), 16);
        c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
        rgb += ("00" + c).substr(c.length);
    }

    return rgb;
}

export default DropChart
