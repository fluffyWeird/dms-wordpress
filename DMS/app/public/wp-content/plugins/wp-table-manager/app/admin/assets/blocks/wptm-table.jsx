(function (wpI18n, wpBlocks, wpElement, wpEditor, wpComponents) {
    const {__} = wpI18n
    const {Component, Fragment} = wpElement
    const {BlockControls} = wpEditor
    const {registerBlockType} = wpBlocks
    const {Icon, Button} = wpComponents
    const $ = jQuery

    let dataTables = []
    let changeTables = []
    let blockStatus = {
        error: false,
        loading: false
    }

    const previewImageData = 'data:image/png;base64,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'

    const tableIcon = (
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="21.5" viewBox="0 0 376 336">
            <g fill="none" stroke="#404852" strokeWidth="35">
                <rect width="175" height="148" rx="10" stroke="none"/>
                <rect x="18" y="18" width="140" height="113" rx="7.5"/>
            </g>
            <g transform="translate(0 175)" fill="none" stroke="#404852" strokeWidth="35">
                <rect width="175" height="161" rx="10" stroke="none"/>
                <rect x="18" y="18" width="140" height="126" rx="7.5"/>
            </g>
            <g transform="translate(202)" fill="none" stroke="#404852" strokeWidth="35">
                <rect width="174" height="148" rx="10" stroke="none"/>
                <rect x="18" y="18" width="139" height="113" rx="7.5"/>
            </g>
            <g transform="translate(202 175)" fill="none" stroke="#404852" strokeWidth="35">
                <rect width="174" height="161" rx="10" stroke="none"/>
                <rect x="18" y="18" width="139" height="126" rx="7.5"/>
            </g>
        </svg>
    )
    const blockIcon = (
        <svg className={"dashicon"} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <path fill="none" d="M0 0h24v24H0V0z"/>
            <path
                d="M20 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 2v3H5V5h15zm-5 14h-5v-9h5v9zM5 10h3v9H5v-9zm12 9v-9h3v9h-3z"/>
        </svg>
    )
    const dataTableIcon = (
        <svg className={"dashicon"} xmlns="http://www.w3.org/2000/svg" width="24" height="32.7" viewBox="0 0 259 354">
            <path d="M130 61C51 61 8 44 8 35S51 8 130 8s121 18 121 27-43 26-121 26z"/>
            <path
                d="M130 16c62 0 100 11 111 19-11 7-49 19-111 19S29 42 18 35c11-8 49-19 112-19m0-16C58 0 0 16 0 35s58 34 130 34 129-15 129-34S201 0 130 0z"/>
            <path d="M4 71c22 7 131 54 251 0a3 3 0 014 2v63a6 6 0 01-2 6s-122 56-255 0a3 3 0 01-2-3V73a3 3 0 014-2z"
                  stroke="#000000" strokeMiterlimit="10" strokeWidth=".3"/>
            <path d="M4 164c22 8 131 55 251 0a3 3 0 014 3v63a6 6 0 01-2 5s-122 56-255 1a3 3 0 01-2-3v-66a3 3 0 014-3z"
                  strokeMiterlimit="10" strokeWidth=".3"/>
            <path d="M4 258c22 8 131 54 251 0a3 3 0 014 2v64a6 6 0 01-2 5s-122 56-255 0a3 3 0 01-2-2v-67a3 3 0 014-2z"
                  strokeMiterlimit="10" strokeWidth=".3"/>
        </svg>
    )
    const folderIcon = (
        <svg className={"dashicon"} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
            <path d="M0 0h24v24H0z" fill="none"/>
        </svg>
    )
    const loadingIcon = (
        <svg className={'wptm-loading'} width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
            <g transform="translate(25 50)">
                <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.590851 0.590851)">
                    <animateTransform attributeName="transform" type="scale" begin="-0.8666666666666667s"
                                      calcMode="spline"
                                      keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1"
                                      dur="2.6s"
                                      repeatCount="indefinite"/>
                </circle>
            </g>
            <g transform="translate(50 50)">
                <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.145187 0.145187)">
                    <animateTransform attributeName="transform" type="scale" begin="-0.43333333333333335s"
                                      calcMode="spline"
                                      keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1"
                                      dur="2.6s"
                                      repeatCount="indefinite"/>
                </circle>
            </g>
            <g transform="translate(75 50)">
                <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.0339143 0.0339143)">
                    <animateTransform attributeName="transform" type="scale" begin="0s" calcMode="spline"
                                      keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1"
                                      dur="2.6s"
                                      repeatCount="indefinite"/>
                </circle>
            </g>
        </svg>
    )

    class WptmTableEdit extends Component {
        constructor() {
            super(...arguments)
            const {attributes} = this.props
            const {shortCode, selectedTableId} = attributes
            //create random id for block
            const d = new Date();
            let idBlock = d.getTime();

            this.state = {
                categoriesList: [],
                tableList: [],
                searchText: this.props.attributes.shortCode || '',
                showCategoryList: false,
                getCategories: false,
                isManagingFocus: false,
                IdBlock: idBlock,
                // loading: true,
                // error: false,
                fetchTableData: false,
                preview: false,
                tableData: false,
                // dataTables: false,
                hasTable: !this.props.attributes.shortCode ? false : true
            }

            dataTables[idBlock] = false
            changeTables[idBlock] = false

            require('datatables.net')
        }

        componentWillMount() {//return before component first render
            document.addEventListener('click', this.handleClickOutside)

            const {isPreview, selectedTableId} = this.props.attributes

            let newState = {}
            if (isPreview) {
                newState.preview = true
            }

            if (selectedTableId > 0) {
                // this.setState({preview: true})
            }
            if (Object.keys(newState).length > 0) {
                this.setState(newState)
            }
        }

        componentDidMount() {//return after component first render
            const {hasTable, preview} = this.state;
            const {selectedTableId} = this.props?.attributes

            document.addEventListener('mousedown', this.handleClickOutside)

            if (!preview) {
                if (hasTable || parseInt(selectedTableId) > 0) {//call ajax get data table when has table
                    const wptm_block_tables = JSON.parse(localStorage.getItem('wptm_block_tables'))

                    if (wptm_block_tables && wptm_block_tables[selectedTableId]?.hash !== '') {
                        this.fetchTableData(selectedTableId, wptm_block_tables[selectedTableId].hash)
                    } else {
                        this.fetchTableData(selectedTableId, null)
                    }
                }
            }
        }

        componentWillUnmount() {//when component unmounting
            document.removeEventListener('click', this.handleClickOutside())
        }

        shouldComponentUpdate(nextProps, nextState) {//
            blockStatus.loading = true
            const {selectedTableId, shortCode} = nextProps.attributes
            const {showCategoryList, getCategories, fetchTableData} = nextState
            let self = this

            // Object.keys(nextState).forEach(function(key, index) {
            //     if (nextState[key] != self.state[key] && (key !== 'tableData' && key !== 'tablePath')) {
            //         console.log(key, nextState[key], self.state[key])
            //     }
            // });

            if (showCategoryList && getCategories) {//show list tables
                return this.fetchCategories()
            } else {
                if (selectedTableId > 0 && fetchTableData) {
                    const wptm_block_tables = JSON.parse(localStorage.getItem('wptm_block_tables'))

                    if (wptm_block_tables && wptm_block_tables[selectedTableId]?.hash !== '') {
                        this.fetchTableData(selectedTableId, wptm_block_tables[selectedTableId].hash)
                    } else {
                        this.fetchTableData(selectedTableId, null)
                    }
                }
            }
            return true
        }
        componentWillUpdate(prevProps, prevState) {//not setState

        }

        componentDidUpdate(prevProps, prevState) {//Component is done rendering
            const {IdBlock} = this.state
            const {shortCode, selectedTableId} = this.props.attributes

                if ($(this.refs.tableRef).find('table').length === 1) {

                    var tableDataTable = this.dataTableRender.call($(this.refs.tableRef).find('table'), dataTables[IdBlock], IdBlock)
                    if (tableDataTable !== false) {
                        dataTables[IdBlock] = tableDataTable

                        if ($(this.refs.tableRef).find('.dataTables_length').length > 0) {
                            $(this.refs.tableRef).find('.dataTables_wrapper').append($(this.refs.tableRef).find('.dataTables_length'))
                        }
                    }
                }
        }

        handleClickOutside = (event) => {
            const domNode = this.refs.wptmBlock
            const {hasTable} = this.state
            const {selectedTableId} = this.props.attributes
            if (!domNode || (!domNode.contains(event?.target) && !$(event?.target).hasClass('wptm_change_button'))) {
                if (!hasTable && selectedTableId > 0) {
                    this.setState({hasTable: true, showCategoryList: false})
                } else {
                    this.setState({showCategoryList: false})
                }
            }
        }

        fetchTableData = async (id, hash) => {
            const self = this

            const wptmGetTableContent = hash !== null ? `${wptm_block.ajax}task=table.preview&id=` + id + `&hash=` + hash : `${wptm_block.ajax}task=table.preview&id=` + id

            let updateData = {}
            await fetch(wptmGetTableContent)
                .then(function (response) {
                    return response.json()
                })
                .then(function (response) {
                    if (!response.success) {
                        blockStatus.error = true
                        updateData = {
                            tableData: false,
                            fetchTableData: false,
                            hasTable: false
                        }
                    } else {
                        blockStatus.error = false
                        updateData = {
                            tableData: response.data[0] || false,
                            fetchTableData: false,
                            hasTable: true
                        }
                    }
                })
                .catch(function (error) {
                    blockStatus.error = true
                    updateData = {
                        tableData: false,
                        fetchTableData: false,
                        hasTable: false
                    }
                })

            this.setState(updateData)
        }

        fetchCategories = async () => {
            // if (fetchingQueue) {
            //     clearTimeout(fetchingQueue)
            // }

            // fetchingQueue = setTimeout(function () {

            let fetchCategoriesData = {}
            await fetch(`${ajaxurl}?action=Wptm&task=categories.listCats`)
                .then(function (response) {
                    return response.json()
                })
                .then(function (response) {
                    if (!response.success) {
                        blockStatus.error = true
                        fetchCategoriesData = {
                            getCategories: false
                        }
                    } else {
                        blockStatus.error = false
                        fetchCategoriesData = {
                            categoriesList: response.data.categories,
                            getCategories: false,
                            tableList: response.data.tables,
                            tablePath: response.data.tablePath
                        }
                    }
                })
                .catch(function (error) {
                    blockStatus.error = true
                    fetchCategoriesData = {
                        getCategories: false
                    }
                })

            this.setState(fetchCategoriesData)

            return !blockStatus.error ? true : false
            // }, 500)
        }

        setSelectedTableId(id) {
            const {tableList, tablePath, IdBlock} = this.state
            const {setAttributes} = this.props
            let shortCode = `[`
            shortCode += `wptm id="${id}"`
            if (typeof tablePath !== 'undefined' && typeof tablePath[id] !== 'undefined' && typeof tableList[tablePath[id]['id_category']] !== 'undefined') {
                tableList[tablePath[id]['id_category']].map((table, index) => {
                    if (table['id'] == id) {
                        shortCode += ` title="` + table['title'] + `"`
                    }
                })
            }
            shortCode += `]`

            setAttributes({selectedTableId: id, shortCode: shortCode})
            this.setState({showCategoryList: false, searchText: shortCode, fetchTableData: true})
        }

        searchCategoryHandle(event) {
            const searchText = event.target.value
            this.setState({searchText: searchText, showCategoryList: true})
        }

        dataTableRender(dataTable, idBlock) {
            const colWidths = this.data('colwidths')
            const tableOptions = {
                responsive: true,
                paging: this.data('paging') || false,
                columns: colWidths.map((v, i) => {
                    return {data: '' + i}
                })
            };

            var tableLanguage = {};
            if (this.data("hidecols")) {
                tableOptions.dom = '<"top">Bfrt<"bottom"pl><"clear">';
                tableOptions.buttons = ["colvis"];
                tableLanguage.buttons = {colvis: this.data("hidecolslanguage")};
            }
            tableLanguage.lengthMenu =
                '<select><option value="10">10</option><option value="20">20</option><option value="40">40</option><option value="-1">All</option></select>';
            var pagination_merge_cells = [];
            tableOptions.juHideColumn = this.data('hidecolumn');
            tableOptions.juHideColumnClass = [];
            tableOptions.juHideColumns = [];
            if (typeof tableOptions.juHideColumn !== 'undefined' && tableOptions.juHideColumn.length > 0) {
                var i = 0, j;
                for (j in tableOptions.juHideColumn) {
                    if (j >= 0)
                        if (tableOptions.juHideColumn[j] > 0) {//hide
                            i++;
                        } else {
                            tableOptions.juHideColumnClass.push(i);
                            tableOptions.juHideColumns[i] = i - tableOptions.juHideColumnClass.length + 1;
                            i++;
                        }
                }
            }
            if (this.data("paging")) {
                tableOptions.processing = true;
                // tableOptions.serverSide = true;
                tableOptions.scrollX = true;

                tableOptions.lengthMenu = [
                    [10, 20, 40, -1],
                    [10, 20, 40, "All"],
                ];
                tableLanguage.paginate = {
                    first:
                        "<i class='icon-step-backward glyphicon glyphicon-step-backward'></i>",
                    previous:
                        "<i class='icon-arrow-left glyphicon glyphicon-backward'></i>",
                    next: "<i class='icon-arrow-right glyphicon glyphicon-forward'></i>",
                    last:
                        "<i class='icon-step-forward glyphicon glyphicon-step-forward'></i>",
                };

                tableOptions.pagingType = 'simple_numbers';
                tableOptions.lengthChange = true;
            }
            tableOptions.date_format = this.data("format");
            tableLanguage.first_load = true;
            tableLanguage.left_header = -1;
            this.find('thead tr').each(function (key, value) {
                $(value).addClass('row_index' + (key - 1)).data('row-index', (key - 1));
                tableLanguage.left_header++;
            });
            tableOptions.language = tableLanguage;

            tableOptions.createdRow = function (row, data, dataIndex) {
                var keys = Object.keys(data);
                var $cRow;
                if (typeof data.DT_RowId !== 'undefined') {
                    $(row).addClass('row' + data.DT_RowId + ' row_index' + (dataIndex + tableLanguage.left_header)).data('row-index', (dataIndex + tableLanguage.left_header));
                } else {
                    $(row).addClass(' row_index' + (dataIndex + tableLanguage.left_header)).data('row-index', (dataIndex + tableLanguage.left_header));
                }

                if (typeof data.merges !== 'undefined') {
                    $.each(data.merges, function (key, value) {
                        if (typeof tableOptions.juHideColumns !== 'undefined'
                            && typeof tableOptions.juHideColumns[value[2]] !== 'undefined'
                            && tableOptions.juHideColumns[value[2]] > 0) {
                            value[2] = parseInt(value[2]) - tableOptions.juHideColumns[value[2]];
                        } else {
                        }

                        $(row).find('td:nth-child(' + (1 + parseInt(value[2])) + ')').attr('colspan', value[3]).attr('rowspan', value[1]);
                        //merger rows
                        if (typeof pagination_merge_cells[value[0]] == 'undefined') {
                            pagination_merge_cells[value[0]] = [];
                        }
                        pagination_merge_cells[value[0]][value[2]] = value;
                        var rowspanI = 0;
                        for (rowspanI = 0; rowspanI < parseInt(value[1]); rowspanI++) {
                            if (typeof pagination_merge_cells[parseInt(value[0]) + rowspanI] == 'undefined') {
                                pagination_merge_cells[parseInt(value[0]) + rowspanI] = [];
                            }
                            pagination_merge_cells[parseInt(value[0]) + rowspanI][value[2]] = value;
                        }
                    });
                }

                var ii = 0;
                let regex2 = new RegExp('(dtc)([0-9]+)');
                keys.forEach(function (key, index) {
                    if (key !== 'merges' && key !== 'DT_RowId' && key !== 'format_date_cell') {
                        $cRow = $(row).find('td:nth-child(' + (parseInt(key) + 1).toString() + ')');
                        let columnClass = 'dtc' + key;
                        if (typeof tableOptions.juHideColumn !== 'undefined') {
                            columnClass = 'dtc' + tableOptions.juHideColumnClass[key];
                        }
                        if (typeof tableOptions.pagingType !== 'undefined' && typeof tableOptions.juHideColumn !== 'undefined') {
                            if (key !== 'DT_RowId') {
                                if ($cRow.length) {
                                    if (typeof data.DT_RowId !== 'undefined') {
                                        $cRow.addClass('dtr' + data.DT_RowId).addClass(columnClass);
                                    } else {
                                        $cRow.addClass(columnClass);
                                    }
                                }
                            }
                        } else {
                            if (key !== 'DT_RowId') {
                                if ($cRow.length) {
                                    let classColumn = $cRow.attr("class").split(' ').filter(classname => regex2.exec(classname) !== null)
                                    if (classColumn[0]) {
                                        $cRow.removeClass(classColumn[0]);
                                    }

                                    if (typeof data.DT_RowId !== 'undefined') {
                                        $cRow.addClass('dtr' + data.DT_RowId).addClass(columnClass);
                                    } else {
                                        $cRow.addClass(columnClass);
                                    }
                                }
                            }
                        }

                        if (key !== 'DT_RowId' && typeof data.DT_RowId !== 'undefined' && typeof pagination_merge_cells[data.DT_RowId] !== 'undefined') {//has merger
                            pagination_merge_cells[data.DT_RowId].forEach(function (value, key) {
                                let colspanI = 0;
                                for (colspanI = 0; colspanI < parseInt(value[3]); colspanI++) {
                                    if (!(parseInt(value[0]) == data.DT_RowId && colspanI === 0)) {
                                        $(row).find('td:nth-child(' + (1 + parseInt(value[2]) + colspanI) + ')').css('display', 'none');
                                    }
                                }
                            });
                        }

                        var value_format = '';
                        var value_timestamp = '';

                        if (typeof $cRow.data('format') !== 'undefined' && $cRow.text() !== '') {
                            if ($cRow.data('format') == '1') {
                                value_format = moment($cRow.text()).format(tableOptions.date_format);
                                value_timestamp = moment($cRow.text()).format('X');
                                $cRow.data('timestamp', value_timestamp).text(value_format).change();
                            } else if ($cRow.data('format') !== '0') {
                                if (moment($cRow.text(), tableOptions.date_format).format($cRow.data('format')) === 'Invalid date') {
                                    value_format = moment($cRow.text(), $cRow.data('format')).format($cRow.data('format'));
                                    value_timestamp = moment($cRow.text(), $cRow.data('format')).format('X');
                                } else {
                                    value_format = moment($cRow.text(), tableOptions.date_format).format($cRow.data('format'));
                                    value_timestamp = moment($cRow.text(), tableOptions.date_format).format('X');
                                }
                                $cRow.data('timestamp', value_timestamp).text(value_format).change();
                            }
                        } else if ($cRow.text() !== '' && $cRow.data('sort') > 0) {
                            $cRow.data('timestamp', $cRow.data('sort')).change();
                        }

                        if (typeof data.format_date_cell !== 'undefined' && typeof data.format_date_cell[key] !== 'undefined' && $cRow.text() !== '') {
                            if (data.format_date_cell[key] === '1') {
                                value_format = moment($cRow.text()).format(tableOptions.date_format);
                                value_timestamp = moment($cRow.text()).format('X');
                                $cRow.data('timestamp', value_timestamp).text(value_format).change();
                            } else if (data.format_date_cell[key] !== '0') {
                                if (moment($cRow.text(), tableOptions.date_format).format(data.format_date_cell[key]) === 'Invalid date') {
                                    value_format = moment($cRow.text(), data.format_date_cell[key]).format(data.format_date_cell[key]);
                                    value_timestamp = moment($cRow.text(), data.format_date_cell[key]).format('X');
                                } else {
                                    value_format = moment($cRow.text(), tableOptions.date_format).format(data.format_date_cell[key]);
                                    value_timestamp = moment($cRow.text(), tableOptions.date_format).format('X');
                                }
                                $cRow.data('timestamp', value_timestamp).text(value_format).change();
                            }
                        }
                    }
                    ii++;
                });
            };

            if (this.data("ordering")) {
                tableOptions.ordering = true;
                var dataOrder = [];
                dataOrder.push(this.data("ordertarget"));
                dataOrder.push(this.data("ordervalue"));
                tableOptions.order = dataOrder;
            }

            var initFilterRow = function (tableDom) {
                // Apply the search
                if (tableDom.hasClass("filterable") && !tableDom.hasClass('hasFilter')) {
                    tableDom.find("thead tr:not(.wptm-header-cells-index):last-child th").each(function (i) {
                        var thContent = $(this).html();
                        var inputHtml = '<br><input onClick="var event = arguments[0] || window.event;event.stopPropagation();" type="text" name="wtmp_col_filter" class="wptm-d-block wptm-filter-input stop-propagation" data-index="' + i + '" value="" />';
                        $(this).html(thContent + inputHtml);
                    });
                    tableDom.addClass('hasFilter')
                }
            };
            initFilterRow($(this));

            tableOptions.columnDefs = [{
                "targets": typeof tableOptions.juHideColumnClass !== 'undefined' ? [...tableOptions.juHideColumnClass.keys()] : [...this.data('hidecolumn').keys()],
                "render": function (data, type, row, meta) {
                    if (typeof data === 'undefined' && typeof row[meta.col] !== 'undefined') {
                        return row[meta.col];
                    }
                    return data;
                }
            }];

            tableOptions.fnInitComplete = function (settings, json) {
                setTimeout(function () {
                    $('.dataTables_wrapper .dataTables_scrollBody thead').hide();
                    $('.DTFC_LeftBodyWrapper thead').hide();
                }, 500);
            };

            if (typeof this.data("freezecol") !== "undefined") {
                tableOptions.fixedColumns = {
                    leftColumns: this.data("freezecol")
                }
            }

            tableOptions.bPaginate = false;

            if (changeTables[idBlock]) {
                dataTable.destroy()
                return $(this).DataTable(tableOptions)
            }
            if (dataTable === false) {
                changeTables[idBlock] = true
                return $(this).DataTable(tableOptions)
            }
            return false
        }

        populateData(data) {
            const {idField, classField} = this.props;
            return data.map((row, i) => {
                if (!row) return false;
                if (idField) row.DT_RowId = row[idField];
                if (classField) row.DT_RowClass = row[classField];
                return row;
            });
        }

        render() {
            const {categoriesList, tableList, searchText, showCategoryList, preview, tablePath, tableData, getCategories, hasTable} = this.state
            const {attributes} = this.props
            let catName = ''
            const {shortCode, selectedTableId} = attributes
            if (typeof selectedTableId !== 'undefined' && typeof tablePath !== 'undefined' && typeof tablePath[selectedTableId] !== 'undefined') {
                catName = tablePath[selectedTableId]['path']
            }

            if ((hasTable && tableData !== false) || (!hasTable && !getCategories)) {
                blockStatus.loading = false
            }

            return (
                <>
                    <BlockControls>
                        <Button style={{height: '100%'}} onClick={() => this.setState({
                            showCategoryList: true,
                            getCategories: true,
                            hasTable: false,
                            searchText: ''
                        })} focus="false" className="wptm_change_button" >{__('Change table', 'wptm')}</Button>
                    </BlockControls>
                    {preview ?
                        <img alt={__('WP Table Manager', 'wptm')} width='100%' src={previewImageData}/>
                        :
                        <Fragment>
                            <div className="wptm-category-block" ref="wptmBlock" style={showCategoryList ? {display: 'inline-block'} : {display: 'block'}}>
                            {!hasTable && !showCategoryList ?
                                <div className="wptm-category-search">
                                    <label htmlFor="wptm-category">
                                        <Icon icon={blockIcon}/>{__('WP Table Manager: ', 'wptm')}
                                    </label>
                                    <textarea
                                        defaultValue={shortCode}

                                        className="editor-plain-text input-control"
                                        id="wptm-table"
                                        placeholder={__('Click here to select a table', 'wptm')}
                                        onFocus={() => this.setState({
                                            showCategoryList: true,
                                            getCategories: true,
                                            hasTable: false,
                                            searchText: ''
                                        })}
                                        onBlur={() => this.setState({
                                            showCategoryList: false,
                                            getCategories: false,
                                            searchText: shortCode
                                        })}
                                    />
                                </div>
                                :
                                <>{showCategoryList ?
                                    <div className="categories-dropdown">
                                        {
                                            blockStatus.loading ?
                                                <div className={'wptm-loading-wrapper'}>
                                                    <Icon className={'wptm-loading'} icon={loadingIcon}/>
                                                </div>
                                                :
                                                <ul>
                                                    {categoriesList.length > 0 ?
                                                        categoriesList.map((category, index) => {
                                                            let haveChild = (typeof (categoriesList[index + 1]) !== 'undefined' && categoriesList[index + 1].level > 0)
                                                            let childTables = false;
                                                            if (typeof (tableList[category.id]) !== 'undefined') {
                                                                haveChild = true;
                                                                childTables = tableList[category.id]
                                                            }
                                                            let paddingLeft = category.level * 12
                                                            if (!haveChild) {
                                                                paddingLeft += 14
                                                            }

                                                            return (
                                                                <li
                                                                    key={index}
                                                                    className={`wptm-category cat-lv-${category.level}`}
                                                                    style={{paddingLeft: paddingLeft + 'px'}}
                                                                    data-id-category={category.term_id}
                                                                    data-id-parent={category.parent}
                                                                    data-cloud-type={category.cloudType}
                                                                    data-level={category.level}
                                                                >
                                                                    <div
                                                                        className={'wptm-table-name-wrap'}>
                                                                        {category.level < 7 && haveChild &&
                                                                        <span
                                                                            className={'wptm-toggle-expand'}
                                                                        />
                                                                        }
                                                                        <Icon icon={folderIcon}/>
                                                                        <span
                                                                            className={'wptm-category-name'}>{category.title}</span>
                                                                    </div>
                                                                    {childTables &&
                                                                    <ul>
                                                                        {childTables.filter(table => table.title.toLowerCase().indexOf(searchText.toLowerCase()) >= 0).map((table, index2) => {
                                                                            let selectedClass = ''
                                                                            let tblIcon = table.type === 'html' ? tableIcon : dataTableIcon;
                                                                            if (parseInt(selectedTableId) === parseInt(table.id)) {
                                                                                selectedClass = 'active'
                                                                            }

                                                                            return (
                                                                                <li key={`table_${index2}`}
                                                                                    className={`wptm-table cat-lv-${category.level + 1} ${selectedClass}`}
                                                                                    style={{paddingLeft: paddingLeft + 'px'}}
                                                                                    data-id-table={table.id}
                                                                                    onClick={() => this.setSelectedTableId(parseInt(table.id))}
                                                                                >
                                                                                    <div
                                                                                        className={'wptm-table-name-wrap'}>
                                                                                        <Icon icon={tblIcon}
                                                                                              className={'wptm-table-icon'}/>
                                                                                        <span
                                                                                            className={'wptm-table-name'}>{table.title}</span>
                                                                                    </div>
                                                                                </li>)
                                                                        })
                                                                        }
                                                                    </ul>
                                                                    }
                                                                </li>
                                                            )
                                                        })
                                                        :
                                                        <p>{__('No table found!', 'wptm')}</p>
                                                    }
                                                </ul>
                                        }
                                    </div>
                                    :
                                    <>{
                                        blockStatus.loading ?
                                            <div className={'wptm-loading-wrapper'}>
                                                <Icon className={'wptm-loading'} icon={loadingIcon}/>
                                            </div>
                                            : <div className="wptm-block-table" ref="tableRef" dangerouslySetInnerHTML={{ __html: tableData || '' }} />
                                    }</>
                                }</>
                            }
                            </div>
                        </Fragment>
                    }
                </>
            )
        }
    }

    class WptmTableSave extends Component {
        render() {
            const {shortCode} = this.props.attributes;
            return (
                <Fragment>
                    {shortCode}
                </Fragment>
            )
        }
    }

    registerBlockType('wptm/wptm-table', {
        title: __('WP Table Manager', 'wptm'),
        description: __('Showing WP Table Manager table.', 'wptm'),
        icon: {
            src: blockIcon,
            foreground: undefined,
        },
        category: 'widgets',
        keywords: [__('wptm', 'wptm'), __('table', 'wptm')],
        example: {
            attributes: {
                isPreview: true
            }
        },
        attributes: {
            selectedTableId: {
                type: 'number',
            },
            shortCode: {
                type: 'string',
            },
            isPreview: {
                type: 'boolean'
            }
        },
        edit: WptmTableEdit,
        save: WptmTableSave,
    })
})(wp.i18n, wp.blocks, wp.element, wp.editor, wp.components)
