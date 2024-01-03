function wptm_drawChart() {
	var defaultConfig = {	"dataUsing": "row",
							"switchDataUsing": true,
							"useFirstRowAsGraph": true,
							"useFirstRowAsLabels": false,
							"width": 500, "height": 375,
							"scaleShowGridLines": false
						};

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

	if (typeof DropCharts === "undefined") {
		var DropCharts = typeof window.DropCharts !== "undefined" ? window.DropCharts : [];
	}

	jQuery(".chartContainer.wptm").each(function () {
		var id_chart = jQuery(this).data('id-chart');

        var DropCharts1 = DropCharts.filter(element => typeof element !== 'undefined' && element.id == id_chart);

        if (jQuery(this).find('canvas').length > 0 && typeof DropCharts1 !== 'undefined') {
			if (!jQuery(this).hasClass('chartActive')
				&& (jQuery(".vc_editor").length < 1
					|| (jQuery(".vc_editor").length > 0  && typeof jQuery(this).parents('.vc_wptm_chart_shortcode').data('model-id') !== 'undefined')
				)//fix re_render in divi and elementor(add later)
			) {
				let $canvas = jQuery(this).find('canvas')
				let randomId = $canvas.data('random_id')

				var DropChart
				if (!randomId) {
					DropChart = DropCharts1[DropCharts1.length - 1]
				} else {
					let DropCharts2 = DropCharts1.filter(element => typeof element !== 'undefined' && element.random_id == randomId)
					DropChart = DropCharts2[0]
				}

				if (!DropChart || DropChart.length < 1) {
					return false;
				}

				DropCharts1 = DropCharts1.map(function (chart) {
					if (typeof chart.chart !== 'undefined' && typeof chart.random_id !== 'undefined') {
						if (jQuery('.canvasWraper').find('canvas[data-random_id="' + chart.random_id + '"]').length < 1) {
							chart.chart.destroy();
						} else {
							return chart
						}
					} else {
						return chart
					}
				})

				DropChart.config.plugins = {};
				DropChart.config.plugins.tooltip = {
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

				var chartConfig = jQuery.extend({},defaultConfig, DropChart.config);
				chartConfig.scaleBeginAtZero = false;
				chartConfig.responsive = false;
				//set labels for axes
				DropChart.data.useFirstRowAsLabels = chartConfig.useFirstRowAsLabels;
				if (typeof chartConfig.scales !== 'undefined') {
					delete chartConfig.scales;
				}

				let scales = null
				if (chartConfig.stepSize && chartConfig.stepSize > 0)
					scales = {ticks: {stepSize: chartConfig.stepSize}}

				if (chartConfig.minY && chartConfig.minY >= 0)
					scales = scales !== null ? {...scales, ...{min: parseFloat(chartConfig.minY)}} : {min: parseFloat(chartConfig.minY)}

				if (chartConfig.legend_display === false)
					chartConfig.plugins.legend = {display: false}
				var ctx = $canvas.get(0).getContext("2d");

				switch (DropChart.type) {
					case 'PolarArea':
						DropChart.chart = new wptmChart(ctx, {
							type: 'polarArea',
							data: DropChart.data,
							options: chartConfig
						});
						break;
					case 'Pie':
						DropChart.chart = new wptmChart(ctx, {
							type: 'pie',
							data: DropChart.data,
							options: chartConfig
						});
						break;
					case 'Doughnut':
						DropChart.chart = new wptmChart(ctx, {
							type: 'doughnut',
							data: DropChart.data,
							options: chartConfig
						});
						break;
					case 'Bar':
                        if (scales !== null) {
                            chartConfig.scales = {y: scales}
                            chartConfig.scales.y.afterBuildTicks = function(axis) {
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
							data: DropChart.data,
							options: chartConfig
						});
						break;
					case 'Radar':
						DropChart.chart = new wptmChart(ctx, {
							type: 'radar',
							data: DropChart.data,
							options: chartConfig
						});
						break;
					case 'Line':
					default:

                        if (scales !== null) {
                            chartConfig.scales = {y: scales}
                            chartConfig.scales.y.afterBuildTicks = function(axis) {
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
                        chartConfig.cubicInterpolationMode = 'default';
                        chartConfig.tension = 0.3;
						DropChart.chart = new wptmChart(ctx, {
							type: 'line',
							data: DropChart.data,
							options: chartConfig
						});
						break;
				}
				jQuery(this).addClass('chartActive');
			}
		}
	});
}

jQuery(document).ready(function(){
	if (typeof DropCharts !== 'undefined') {
		wptm_drawChart();
	}
});

function wptm_render_chart (id_table) {
	// if (!jQuery('div#chartContainer' + id_table).hasClass('chartActive')) {
		wptm_drawChart();
	// }
};