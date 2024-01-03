// External Dependencies
import React, { Component, Fragment} from 'react';
import $ from 'jquery';

// Internal Dependencies
import './style.css';


class WptmChart extends Component {

  static slug = 'wptm_chart';

    constructor(props) {
        super(props);
        this.state = {
            selectedChartId: 'root',
            selectedTitle: '',
            isLoaded: true,
            chartShortcode: '',
            chartContent: ''
        };
    }

    componentDidMount() {
        let selectedChartId = this.props.wptm_chart_params;
        if (selectedChartId !== 'root') {
            selectedChartId = JSON.parse(selectedChartId).selected_chart_id;
            this.fetDataTable(selectedChartId);
        }
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        let selectedChartId = this.props.wptm_chart_params;

        if (selectedChartId !== 'root') {
            selectedChartId = JSON.parse(selectedChartId).selected_chart_id;

            if (parseInt(prevState.selectedChartId) !== parseInt(selectedChartId)) {
                this.fetDataTable(selectedChartId);
            } else {
                if (typeof window.DropCharts === "undefined") {
                    window.DropCharts = [];
                }

                if (this.state.chartContent !== '' && !$(this.refs.wptm_chart.firstElementChild).hasClass('chartActive')) {
                    if (typeof this.state.contentJs.config === 'string') {
                        this.state.contentJs.config = JSON.parse(this.state.contentJs.config);
                        this.state.contentJs.data = JSON.parse(this.state.contentJs.data);
                    }

                    window.DropCharts[selectedChartId] = $.extend({}, this.state.contentJs);

                    setTimeout(() => {
                        window.wptm_drawChart();
                    }, 500);
                }
            }
        }
    }

    createShortCode() {
        const {selectedTitle, selectedChartId} = this.state;
        let shortCode = `[`;
        shortCode += `wptm id-chart="${selectedChartId}"`;
        shortCode += ` title="` + selectedTitle + `"`;
        shortCode += `]`;

        return shortCode;
    }

    fetDataTable(id) {
        if (parseInt(id) > 0) {
            fetch(window.et_fb_options.ajaxurl + '?juwpfisadmin=false&action=Wptm&task=table.loadContentChart&id=' + id)
                .then(res => res.json())
                .then(
                    (result) => {
                        if (result.success && result.data.content !== '') {
                            this.setState({
                                selectedChartId: id,
                                chartShortcode: this.createShortCode(),
                                selectedTitle: result.data.title,
                                chartContent: result.data.content,
                                contentJs: result.data.contentJs,
                                isLoaded: false
                            });
                        }
                    },

                    (error) => {
                        this.setState({
                            isLoaded: true,
                            error
                        });
                    }
                )
        }
    }

    render() {
        const {selectedChartId, chartContent, isLoaded} = this.state;
        const chartParams = this.props.wptm_chart_params;
        const content = 'Please select a WP Table Manager content to activate the preview';

        const loadingIcon = (
            <svg className={'wptm-loading'} width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" fill="#29c4a9">
                <g transform="translate(25 50)">
                    <circle cx="0" cy="0" r="10" transform="scale(0.590851 0.590851)">
                        <animateTransform attributeName="transform" type="scale" begin="-0.8666666666666667s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1" dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
                <g transform="translate(50 50)">
                    <circle cx="0" cy="0" r="10" transform="scale(0.145187 0.145187)">
                        <animateTransform attributeName="transform" type="scale" begin="-0.43333333333333335s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1" dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
                <g transform="translate(75 50)">
                    <circle cx="0" cy="0" r="10" transform="scale(0.0339143 0.0339143)">
                        <animateTransform attributeName="transform" type="scale" begin="0s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1" dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
            </svg>
        );

        const placeholder = {
            display: 'block',
            position: 'absolute',
            bottom: '5px',
            width: '100%',
            left: '0',
            'font-size': '13px',
            'text-align': 'center'
        };

        // this.inputEl = useRef(null);

        if (chartParams === 'root') {
            return (
                <Fragment>
                    <div id="wptm-chart-placeholder" className="wptm-chart-placeholder">
                        <p style={placeholder}>
                            {content}
                        </p>
                    </div>
                </Fragment>
            );
        } else {
            return (
                <div className={`wptm-divi-container wptm-chart-${selectedChartId}`}>

                    {isLoaded &&
                    <div className={'wptm-loading-wrapper'}>
                        <i className={'wptm-loading'}>{loadingIcon}</i>
                    </div>
                    }

                    {!isLoaded &&
                    <div dangerouslySetInnerHTML={{__html: chartContent}} ref={`wptm_chart`}/>
                    }
                </div>
            );
        }
    }
}

export default WptmChart;
