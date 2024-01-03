var DropCharts = [];
function drawChart() {
	var defaultConfig = {	"dataUsing": "row", 
							"switchDataUsing": true, 
							"useFirstRowAsLabels": true,
							"width": 500, "height": 375, 
							"scaleShowGridLines": false
						};
	
	for(var i=0; i < DropCharts.length; i++) {
		var DropChart = DropCharts[i];
		var chartConfig = jQuery.extend({},defaultConfig, DropChart.config);
		
		var ctx = jQuery("#chartContainer"+DropChart.id+ " .canvas").get(0).getContext("2d");		
		switch (DropChart.type) {
			case 'PolarArea':
				DropChart.chart = new wptmChart(ctx).PolarArea(DropChart.data, chartConfig);
				break;

			case 'Pie':
				DropChart.chart = new wptmChart(ctx).Pie(DropChart.data, chartConfig);
				break;

			case 'Doughnut':
				DropChart.chart = new wptmChart(ctx).Doughnut(DropChart.data, chartConfig);
				break;

			case 'Bar':
				DropChart.chart = new wptmChart(ctx).Bar(DropChart.data, chartConfig);
				break;

			case 'Radar':
				DropChart.chart = new wptmChart(ctx).Radar(DropChart.data, chartConfig);
				break;

			case 'Line':
			default:
				DropChart.chart = new wptmChart(ctx).Line(DropChart.data, chartConfig);
				break;
		}
	}
}

jQuery(document).ready(function(){
        Chart.defaults.global.responsive = true;
        //Chart.defaults.global.animation = false;
	Chart.defaults.global.multiTooltipTemplate = "<%= datasetLabel %>: <%= value %>";	
	drawChart();
})