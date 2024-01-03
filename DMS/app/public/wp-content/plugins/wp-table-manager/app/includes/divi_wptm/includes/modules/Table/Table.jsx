// External Dependencies
import React, { Component, Fragment} from 'react';
import $ from 'jquery';

// Internal Dependencies
import './style.css';


class WptmTable extends Component {

  static slug = 'wptm_table';

    constructor(props) {
        super(props);
        this.state = {
            selectedTableId: 'root',
            selectedTitle: '',
            isLoaded: true,
            tableShortcode: '',
            tableContent: ''
        };
    }

    componentDidMount() {
        let selectedTableId = this.props.table_params;
        if (selectedTableId !== 'root') {
            selectedTableId = JSON.parse(selectedTableId).selected_table_id;
            this.fetDataTable(selectedTableId);
        }
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        let selectedTableId = this.props.table_params;

        if (selectedTableId !== 'root') {
            selectedTableId = JSON.parse(selectedTableId).selected_table_id;
            if (parseInt(prevState.selectedTableId) !== parseInt(selectedTableId)) {
                this.fetDataTable(selectedTableId);
            } else {
                if (this.state.tableContent !== '' && $(`.wptm-table-${selectedTableId}`).find('.dataTable').length < 1) {
                    setTimeout(() => {
                        window.wptm_render_tables.call()
                    }, 500);
                }
            }
        }
    }

    createShortCode() {
        const {selectedTitle, selectedTableId} = this.state;
        let shortCode = `[`;
        shortCode += `wptm id="${selectedTableId}"`;
        shortCode += ` title="` + selectedTitle + `"`;
        shortCode += `]`;

        return shortCode;
    }

    fetDataTable(id) {
        if (parseInt(id) > 0) {
            fetch(window.et_fb_options.ajaxurl + '?juwpfisadmin=false&action=Wptm&task=table.loadContent&id=' + id)
                .then(res => res.json())
                .then(
                    (result) => {
                        if (result.success && result.data.content !== '') {
                            this.setState({
                                selectedTableId: id,
                                tableShortcode: this.createShortCode(),
                                selectedTitle: result.data.title,
                                tableContent: result.data.content,
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
        const {selectedTableId, tableContent, isLoaded} = this.state;
        const categoryParams = this.props.table_params;
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

        if (categoryParams === 'root') {
            return (
                <Fragment>
                    <div id="wptm-table-placeholder" className="wptm-table-placeholder">
                        <p style={placeholder}>
                            {content}
                        </p>
                    </div>
                </Fragment>
            );
        } else {
            return (
                <div className={`wptm-divi-container wptm-table-${selectedTableId}`}>

                    {isLoaded &&
                    <div className={'wptm-loading-wrapper'}>
                        <i className={'wptm-loading'}>{loadingIcon}</i>
                    </div>
                    }

                    {!isLoaded &&
                    <div dangerouslySetInnerHTML={{__html: tableContent}}/>
                    }
                </div>
            );
        }
    }
}

export default WptmTable;
