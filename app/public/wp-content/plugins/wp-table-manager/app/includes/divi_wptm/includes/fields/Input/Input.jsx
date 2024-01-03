// External Dependencies
import React, {Component, Fragment} from 'react';
import $ from 'jquery';

class Input extends Component {

  static slug = 'wptm_input';

  constructor(props) {
    super(props);
    this.state = {
      tableList: [],
      categoriesList: [],
      tablePath: [],
      chartList: [],
      chartPath: [],
      nameItem: '',
      isOpenModal: false,
      wptmLoading: false,
      loading: true,
      search: false,
      error: false,
    };

    this.searchText   = this.searchText.bind(this);
    this.updateInput = this.updateInput.bind(this);
    this.fetchTables = this.fetchTables.bind(this);
    this.fetchCharts = this.fetchCharts.bind(this);
    this.openModal = this.openModal.bind(this);
    this.handleClickOutside = this.handleClickOutside.bind(this);
  }

  componentDidMount() {
    if (this.props.name === 'wptm_chart_params') {
      this.fetchCharts();
    } else if (this.props.name === 'table_params') {
      this.fetchTables();
    }
    document.addEventListener('mousedown', this.handleClickOutside);
  }

  componentWillUnmount() {
    document.removeEventListener('mousedown', this.handleClickOutside)
  }

  updateInput(event) {
    const nameItem = event.target.value;

    this.setState({nameItem: nameItem, isOpenModal: false});
  }

  searchText(event) {
    const searchText = event.target.value;
    this.setState({nameItem: searchText, search: searchText});
  }

  openModal() {
    this.setState({isOpenModal: true});
    let selectedId = 0;

    if (this.props.name === 'wptm_chart_params') {
      const {chartList} = this.state;
      selectedId = this.props.value !== 'root'
          ? JSON.parse(this.props.value).selected_chart_id : 0;

      if (chartList.length === 0) {
        this.fetchCharts();
      } else {
        if (selectedId) {
          this.setState({selectedChartId: selectedId, wptmLoading: false});
        }
      }
    } else {
      const {tableList} = this.state;
      selectedId = this.props.value !== 'root'
          ? JSON.parse(this.props.value).selected_table_id : 0;

      if (tableList.length === 0) {
        this.fetchTables();
      } else {
        if (selectedId) {
          this.setState({selectedTableId: selectedId, wptmLoading: false});
        }
      }
    }
  }

  handleClickOutside(event) {
    const domNode = this.checkMenuDropdown;

    if (!domNode || !domNode.contains(event.target)) {
      this.setState({isOpenModal: false})
    }
  }

  fetchTables() {
    const self = this;
    const url = window.et_fb_options.ajaxurl + `?action=Wptm&task=categories.listCats`;

    if (this.state.error) {
      this.setState({error: false})
    }

    if (!self.state.wptmLoading) {
      self.setState({wptmLoading: true})
    }

    if (!this.state.selectedTableId
        && this.props.value !== 'root'
        && JSON.parse(this.props.value).selected_table_id > 0) {
      self.setState({selectedTableId: JSON.parse(this.props.value).selected_table_id})
    }
    fetch(url)
        .then(function (response) {
          return response.json()
        })
        .then(function (response) {
          if (false === response.success) {
            self.setState({
              wptmLoading: false,
              error: true,
            })
          } else {
            self.setState({
              categoriesList: response.data.categories,
              adminUrl: typeof response.data.adminUrl !== 'undefined' ? response.data.adminUrl : '',
              tableList: response.data.tables,
              tablePath: response.data.tablePath,
              wptmLoading: false,
            });
          }
        })
        .catch(function (error) {
          self.setState({
            wptmLoading: false,
            error: true,
          })
        })
  }

  fetchCharts() {
    const self = this;
    const url = window.et_fb_options.ajaxurl + `?action=Wptm&task=chart.listCharts`;

    if (this.state.error) {
      this.setState({error: false})
    }

    if (!self.state.wptmLoading) {
      self.setState({wptmLoading: true})
    }

    if (!this.state.selectedChartId
        && this.props.value !== 'root'
        && JSON.parse(this.props.value).selected_chart_id > 0) {
      self.setState({selectedChartId: JSON.parse(this.props.value).selected_chart_id})
    }
    fetch(url)
        .then(function (response) {
          return response.json()
        })
        .then(function (response) {
          if (false === response.success) {
            self.setState({
              wptmLoading: false,
              error: true,
            })
          } else {
            console.log(response);
            self.setState({
              categoriesList: response.data.categories,
              adminUrl: typeof response.data.adminUrl !== 'undefined' ? response.data.adminUrl : '',
              tableList: response.data.tables,
              chartList: response.data.charts,
              chartPath: response.data.chartPath,
              wptmLoading: false,
            });
          }
        })
        .catch(function (error) {
          self.setState({
            wptmLoading: false,
            error: true,
          })
        })
  }

  setSelectedTable(id) {
    if (parseInt(id) > 0) {
      this.setState({selectedTableId: id, nameItem: this.createnameItem(id), isOpenModal: false, search: false});

      this._onChange(id);
    }
  }

  createnameItem(id) {
    const {tableList, tablePath, chartList, chartPath} = this.state;
    let nameItem = ``;
    if (this.props.name === 'wptm_chart_params') {
      if (typeof chartPath[id] !== 'undefined' && typeof chartList[parseInt(chartPath[id]['id_table'])] !== 'undefined') {
        chartList[parseInt(chartPath[id]['id_table'])].map((v, i) => {
          if (parseInt(v.id) === id) {
            nameItem += `${v.title}`;
          }
        });
      }
    } else {
      if (typeof tablePath[id] !== 'undefined' && typeof tableList[tablePath[id]['id_category']] !== 'undefined') {
        tableList[parseInt(tablePath[id]['id_category'])].map((v, i) => {
          if (parseInt(v.id) === id) {
            nameItem += `${v.title}`;
          }
        });
      }
    }

    return nameItem;
  }

  /**
   * Handle input value change.
   *
   * @param {string} id
   */
  _onChange = (id) => {
    let result;
    if (this.props.name === 'wptm_chart_params') {
      result = JSON.stringify({
        selected_chart_id: id,
      });
    } else {
      result = JSON.stringify({
        selected_table_id: id,
      });
    }

    this.props._onChange(this.props.name, result);
  };


  showbranchCat(returnCat, listCat, contentCatId) {
    for (let i = 0; i < listCat.length; i++) {
      if (parseInt(listCat[i].id) === parseInt(contentCatId)) {
        returnCat[contentCatId] = contentCatId;
        if (typeof listCat[i].parent_id !== 'undefined' && listCat[i].parent_id !== '1') {
          returnCat = this.showbranchCat(returnCat, listCat, listCat[i].parent_id);
        }
      }
    }
    return returnCat
  }

  render() {
    const {categoriesList, adminUrl, selectedTableId, nameItem, isOpenModal, wptmLoading, tableList, tablePath, selectedChartId, chartList, chartPath, search} = this.state;

    const folderIcon = (
        <svg className={"dashicon"} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
          <path fill="currentColor" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
          <path d="M0 0h24v24H0z" fill="none"/>
        </svg>
    );
    const tableIcon = (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
          <defs/>
          <rect width="100%" height="100%" fill="none"/>
          <path fill="currentColor" d="M16 30.1H2.6c-1.2 0-1.9-.8-2-1.5 0-.1-.1-.3-.1-.5V3.5c0-.3 0-.6.1-.8.2-1 1-1.6 2-1.6h26.8c.3 0 .5 0 .8.1.7.2 1.1.7 1.3 1.4.1.3.1.5.1.8v24.7c0 1-.6 1.7-1.6 1.9h-.7c-4.5.1-8.9.1-13.3.1zm-5.8-15.4H2.5v5.9h7.7v-5.9zm1.9 5.9h7.5c.1 0 .2 0 .2-.2v-5.8h-7.5c-.2 0-.2.1-.2.2v5.8zm17.4 0v-5.9h-7.7v5.9h7.7zm-27-7.9h7.7V6.9H2.5v5.8zm9.6 0h7.5c.2 0 .2 0 .2-.2V7.1c0-.2 0-.2-.2-.2h-7.3c-.2 0-.2 0-.2.2v5.6zm17.4-5.8h-7.7v5.8h7.7V6.9zm-27 15.5v5.8h7.7v-5.8H2.5zm17.3 0h-7.5c-.2 0-.2 0-.2.2v5.6h7.5c.2 0 .2 0 .2-.2V22.4zm9.7 5.8v-5.8h-7.7v5.8h7.7z"/>
        </svg>
    );
    const dbTableIcon = (
        <svg className="db-table" xmlns="http://www.w3.org/2000/svg" width="259.31" height="354.262" viewBox="0 0 259.31 354.262" fill="currentColor">
          <path id="Path_160" data-name="Path 160" d="M129.475,57.375c-78.169,0-121.665-17.7-121.665-26.76S51.306,3.75,129.475,3.75,251.14,21.451,251.14,30.615,208.163,57.375,129.475,57.375Z" transform="translate(0.149 4.059)"/>
          <path id="Path_161" data-name="Path 161" d="M136.534,18.619c62.3,0,100.61,11.35,111.72,19.055-11.11,7.6-49.526,18.951-111.72,18.951S35.924,45.275,24.815,37.674c11.11-7.705,49.526-19.055,111.72-19.055m0-15.619C65,3,7.06,18.515,7.06,37.674S65,72.244,136.534,72.244s129.474-15.515,129.474-34.57S208.072,3,136.534,3Z" transform="translate(-6.91 -3)"/>
          <path id="Path_162" data-name="Path 162" d="M11.005,10.02c22.323,7.809,130.409,54.25,250.85.1a3.009,3.009,0,0,1,4.153,2.083V75.724a6.049,6.049,0,0,1-1.869,5.206s-122.726,56.228-255.107.417a2.914,2.914,0,0,1-1.973-2.6V12.207a2.8,2.8,0,0,1,3.945-2.187Z" transform="translate(-6.91 60.639)" stroke="#0073ab" stroke-miterlimit="10" stroke-width="0.3"/>
          <path id="Path_163" data-name="Path 163" d="M11.005,19.02c22.323,7.809,130.409,54.25,250.85.1a3.009,3.009,0,0,1,4.153,2.083V84.724a6.049,6.049,0,0,1-1.869,5.206s-122.726,56.228-255.107.416a2.914,2.914,0,0,1-1.973-2.6V21.207a2.8,2.8,0,0,1,3.945-2.187Z" transform="translate(-6.91 145.297)" stroke="#0073ab" stroke-miterlimit="10" stroke-width="0.3"/>
          <path id="Path_164" data-name="Path 164" d="M11.005,28.02c22.323,7.809,130.409,54.25,250.85.1a3.009,3.009,0,0,1,4.153,2.083V93.724a6.049,6.049,0,0,1-1.869,5.206s-122.726,56.228-255.107.417a2.914,2.914,0,0,1-1.973-2.6V30.207a2.8,2.8,0,0,1,3.945-2.187Z" transform="translate(-6.91 229.955)" stroke="#0073ab" stroke-miterlimit="10" stroke-width="0.3"/>
        </svg>
    );
    const chartIcon = (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
          <defs/>
          <rect width="100%" height="100%" fill="none"/>
          <path fill="currentColor"
                d="M31.4 4.5c-.1.2-.2.5-.3.7-.6 1.8-1.3 3.5-1.9 5.3-.4-.5-.8-.9-1.2-1.4-.3.3-.7.5-1 .8-1.3 1-2.5 2.1-3.8 3.1-1.4 1.1-2.8 2.3-4.1 3.4l-1.8 1.5h-.2c-1.4-.7-2.8-1.5-4.2-2.2-.1-.1-.2-.1-.4 0-2 1.2-4.1 2.3-6.1 3.5-1.5.9-3.1 1.8-4.6 2.6 0 0-.1 0-.1.1-.2-.3-.4-.7-.6-1-.3-.4-.5-.9-.8-1.3.5-.3 1.1-.6 1.6-.9 1.7-.9 3.3-1.9 5-2.8 1.6-.9 3.2-1.8 4.8-2.8.3-.2.6-.3.8-.5h.2c1.3.7 2.6 1.4 4 2.1h.2C19 13 21 11.3 23.1 9.6l3-2.4.1-.1c-.4-.5-.9-1-1.3-1.5.4-.1.8-.2 1.2-.2 1-.2 2.1-.4 3.1-.6.7-.1 1.4-.3 2.1-.4 0 0 .1 0 .1.1 0-.1 0-.1 0 0zM27.9 12c.3.4.6.7.9 1 .4.4.7.8 1.1 1.2 0 .1.1.1.1.2v12.4c0 .5-.3.8-.8.8h-3.7c-.5 0-.9-.4-.9-.9v-4.5-7.5c0-.1 0-.2.1-.3 1.2-.7 2.2-1.6 3.2-2.4zM22.6 16.4V26.8c0 .6-.4.9-.8.9h-3.7c-.4 0-.7-.4-.7-.8v-6.2c1.6-1.4 3.4-2.8 5.2-4.3z"/>
          <path fill="currentColor"
                d="M15.1 23.3v3.6c0 .5-.3.9-.8.9h-3.7c-.4 0-.8-.4-.8-.8v-3.8-3.1c0-.1 0-.2.2-.2.9-.5 1.7-1 2.6-1.5.1-.1.2-.1.3 0 .7.4 1.4.7 2.1 1.1.1.1.1.1.1.2v3.6zM7.7 21.1c0 .1 0 .1 0 0v5.7c0 .4-.2.7-.6.9H3.4c-.5 0-.8-.2-.9-.6v-.2-2.7c0-.1 0-.2.1-.2 1.6-.9 3.3-1.9 5.1-2.9-.1.1-.1.1 0 0z"/>
        </svg>
    );
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
    );
    let categoryPath = '';
    let editCategoryLink = '' + adminUrl;
    let tableName = '';
    let canOpenEdit = false;

    if (this.props.name === 'wptm_chart_params') {
      let chartId = this.props.value !== 'root'
          ? JSON.parse(this.props.value).selected_chart_id
          : (typeof selectedChartId !== 'undefined' ? selectedChartId : 0);
      let table_id = 0;
      if (parseInt(chartId) > 0 && typeof chartPath[chartId] !== 'undefined') {
        categoryPath = chartPath[chartId]['path'];
        tableName = this.createnameItem(chartId);
        table_id = chartPath[chartId]['id_table'];
        for (let ii = 0; ii < tableList[chartPath[chartId]['id_cat']].length; ii++) {
          if (tableList[chartPath[chartId]['id_cat']][ii].id == table_id && parseInt(tableList[chartPath[chartId]['id_cat']][ii].role) > 0) {
            canOpenEdit = true;
          }
        }
      }

      let listCatHasTable = [];

      if (search !== false && search !== '') {
        Object.entries(chartPath).map((chart1, i1) => {
          if (chart1[1].title.search(search) >= 0) {
            listCatHasTable = $.extend([], listCatHasTable, this.showbranchCat([], categoriesList, chart1[1].id_cat));
          }
        })
        if (listCatHasTable.length < 1) {
          listCatHasTable = false;
        }
      }

      return (
          <Fragment>
            <div className="wptm-input-module-chart wptm-table-block">
              <div className="wptm-file-search">
                {search === false
                    ? <Fragment>
                      <input
                          type={'text'}
                          value={nameItem !== '' ? nameItem : tableName}
                          className="editor-plain-text input-control"
                          placeholder={'Please select a chart'}
                          readOnly={true}
                          onChange={this.updateInput}
                          onFocus={() => {
                            this.setState({isOpenModal: true, search: ''});
                            this.openModal();
                          }}
                          onBlur={() => {
                            this.setState({isOpenModal: true, search: ''});
                            this.openModal();
                          }}
                      />
                    </Fragment>
                    : <Fragment>
                      <input
                          type={'text'}
                          value={search}
                          className="editor-plain-text input-control"
                          placeholder={'Please select a chart'}
                          onFocus={() => {
                            this.openModal();
                          }}
                          onChange={this.searchText}
                      />
                    </Fragment>
                }
              </div>
              {this.props.value !== 'root' &&
              <div className="wptm-selected-category-name">{'CHART CATEGORY: '}
                <span>{categoryPath}</span>
              </div>
              }
              <div className={`wptm-edit-category`}>
                {parseInt(chartId) > 0 && canOpenEdit
                    ? <a href={editCategoryLink + `&id_table=${table_id}&chart=${chartId}`} target="_blank">{`edit chart`}</a>
                    : <a href={editCategoryLink} target="_blank">{`manage chart`}</a>
                }
              </div>
            </div>
            {!isOpenModal ?
                '' :
                <div className="wptm-modal-container" ref={(elm) => this.checkMenuDropdown = elm}>
                  {wptmLoading ?
                      <div className={'wptm-loading-wrapper'}>
                        <i className={'wptm-loading'}>{loadingIcon}</i>
                      </div>
                      :
                      <ul>
                        {categoriesList.length > 0 && listCatHasTable !== false ?
                            categoriesList.map((category, index) => {
                              let haveChild = (typeof (categoriesList[index + 1]) !== 'undefined' && categoriesList[index + 1].level > 0)
                              let childTables = false;
                              if (typeof (tableList[category.id]) !== 'undefined') {
                                haveChild = true;
                                childTables = tableList[category.id]
                              }
                              if (listCatHasTable.length < 1 || typeof listCatHasTable[category.id] !== "undefined") {
                                return (
                                    <li
                                        key={index}
                                        className={`wptm-category cat-lv-${category.level}`}
                                        data-id-category={category.term_id}
                                        data-id-parent={category.parent}
                                        data-cloud-type={category.cloudType}
                                        data-level={category.level}
                                    >
                                      <div
                                          className={'wptm-name-wrap wptm-category-name-wrap'}>
                                        {category.level < 7 && haveChild &&
                                        <span
                                            className={'wptm-toggle-expand'}
                                        />
                                        }
                                        <i>{folderIcon}</i>
                                        <span
                                            className={'wptm-category-name'}>{category.title}</span>
                                      </div>
                                      {childTables &&
                                      childTables.map((table, index2) => {
                                        let charts = false;
                                        let checkHasCharts = true;
                                        if (typeof (chartList[table.id]) !== 'undefined') {
                                          charts = chartList[table.id]
                                          if (search !== false && search !== '') {
                                            checkHasCharts = false;
                                            charts.map((chart, index3) => {
                                              if (chart.title.search(search) >= 0) {
                                                checkHasCharts = true;
                                              }
                                            })
                                          }
                                        }

                                        if (checkHasCharts) {
                                          return (
                                              <ul>
                                                <li key={`table_${index2}`}
                                                    className={`wptm-table`}
                                                    data-id-table={table.id}
                                                >
                                                  <div
                                                      className={'wptm-name-wrap wptm-table-name-wrap'}>
                                                    <i>{tableIcon}</i>
                                                    <span
                                                        className={'wptm-table-name'}>{table.title}</span>
                                                  </div>
                                                  <ul>
                                                    {charts.map((chart, index3) => {
                                                      let selectedClass = '';
                                                      if (parseInt(chartId) === parseInt(chart.id)) {
                                                        selectedClass = 'active'
                                                      }

                                                      if (search !== false && search !== '' && chart.title.search(search) < 0) {
                                                        return ('')
                                                      }

                                                      return (
                                                          <li key={`chart_${index3}`}
                                                              className={`wptm-chart ${selectedClass}`}
                                                              data-id-chart={chart.id}
                                                              onClick={() => this.setSelectedTable(parseInt(chart.id))}
                                                          >
                                                            <div
                                                                className={'wptm-name-wrap wptm-table-name-wrap'}>
                                                              <i>{chartIcon}</i>
                                                              <span
                                                                  className={'wptm-chart-name'}>{chart.title}</span>
                                                            </div>
                                                          </li>)
                                                    })}
                                                  </ul>
                                                </li>
                                              </ul>
                                          )
                                        } else {
                                          return ''
                                        }
                                      })
                                      }
                                    </li>
                                )
                              } else {
                                return ''
                              }
                            })
                            :
                            <p>{'No table found!'}</p>
                        }
                      </ul>
                  }
                </div>
            }
          </Fragment>
      );
    } else {
      let tableId = typeof selectedTableId !== 'undefined'
          ? selectedTableId
          : (this.props.value !== 'root' ? JSON.parse(this.props.value).selected_table_id : 0);

      if (parseInt(tableId) > 0 && typeof tablePath[tableId] !== 'undefined') {
        categoryPath = tablePath[tableId]['path'];
        tableName = this.createnameItem(tableId);

        for (let i = 0; i < tableList[tablePath[tableId].id_category].length; i++) {
          if (tableList[tablePath[tableId].id_category][i].id == tableId && parseInt(tableList[tablePath[tableId].id_category][i].role) > 0) {
            canOpenEdit = true;
          }
        }
      }
      let listCatHasTable = [];

      if (search !== false && search !== '') {
        Object.entries(tablePath).map((table1, i1) => {
          if (table1[1].title.search(search) >= 0) {
            listCatHasTable = $.extend([], listCatHasTable, this.showbranchCat([], categoriesList, table1[1].id_category));
          }
        })
        if (listCatHasTable.length < 1) {
          listCatHasTable = false;
        }
      }

      return (
          <Fragment>
            <div className="wptm-input-module wptm-table-block">
              <div className="wptm-file-search">
                {search === false
                    ? <Fragment>
                      <input
                          type={'text'}
                          value={nameItem !== '' ? nameItem : tableName}
                          className="editor-plain-text input-control"
                          placeholder={'Please select a table'}
                          readOnly={true}
                          onChange={this.updateInput}
                          onFocus={() => {
                            this.setState({isOpenModal: true, search: ''});
                            this.openModal();
                          }}
                          onBlur={() => {
                            this.setState({isOpenModal: true, search: ''});
                            this.openModal();
                          }}
                      />
                    </Fragment>
                    : <Fragment>
                      <input
                          type={'text'}
                          value={search}
                          className="editor-plain-text input-control"
                          placeholder={'Please select a table'}
                          onFocus={() => {
                            this.openModal();
                          }}
                          onChange={this.searchText}
                      />
                    </Fragment>
                }
              </div>
              {this.props.value !== 'root' &&
              <div className="wptm-selected-category-name">{'TABLE CATEGORY: '}
                <span>{categoryPath}</span>
              </div>
              }
              <div className={`wptm-edit-category`}>
                {parseInt(tableId) > 0 && canOpenEdit
                    ? <a href={editCategoryLink + `&id_table=${tableId}`} target="_blank">{`edit table`}</a>
                    : <a href={editCategoryLink} target="_blank">{`manage table`}</a>
                }
              </div>
            </div>
            {!isOpenModal ?
                '' :
                <div className="wptm-modal-container" ref={(elm) => this.checkMenuDropdown = elm}>
                  {wptmLoading ?
                      <div className={'wptm-loading-wrapper'}>
                        <i className={'wptm-loading'}>{loadingIcon}</i>
                      </div>
                      :
                      <ul>
                        {categoriesList.length > 0 && listCatHasTable !== false
                            ? categoriesList.map((category, index) => {
                              let haveChild = (typeof (categoriesList[index + 1]) !== 'undefined' && categoriesList[index + 1].level > 0)
                              let childTables = false;
                              if (typeof (tableList[category.id]) !== 'undefined') {
                                haveChild = true;
                                childTables = tableList[category.id]
                              }

                              if (listCatHasTable.length < 1 || typeof listCatHasTable[category.id] !== "undefined") {
                                return (
                                    <li
                                        key={index}
                                        className={`wptm-category cat-lv-${category.level}`}
                                        data-id-category={category.term_id}
                                        data-id-parent={category.parent}
                                        data-cloud-type={category.cloudType}
                                        data-level={category.level}
                                    >
                                      <div
                                          className={'wptm-name-wrap wptm-category-name-wrap'}>
                                        {category.level < 7 && haveChild &&
                                        <span
                                            className={'wptm-toggle-expand'}
                                        />
                                        }
                                        <i>{folderIcon}</i>
                                        <span
                                            className={'wptm-category-name'}>{category.title}</span>
                                      </div>
                                      {childTables &&
                                      <ul>
                                        {childTables.map((table, index2) => {
                                          let selectedClass = ''

                                          if (parseInt(tableId) === parseInt(table.id)) {
                                            selectedClass = 'active'
                                          }

                                          if (search !== false && search !== '' && table.title.search(search) < 0) {
                                            return ('')
                                          }

                                          return (
                                              <li key={`table_${index2}`}
                                                  className={`wptm-table ${selectedClass}`}
                                                  data-id-table={table.id}
                                                  onClick={() => this.setSelectedTable(parseInt(table.id))}
                                              >
                                                <div
                                                    className={'wptm-name-wrap wptm-table-name-wrap'}>
                                                  {table.type === 'html'
                                                      ? <i>{tableIcon}</i>
                                                      : <i>{dbTableIcon}</i>
                                                  }
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
                              } else {
                                return ''
                              }
                            })
                            :
                            <p>{'No table found!'}</p>
                        }
                      </ul>
                  }
                </div>
            }
          </Fragment>
      );
    }
  }
}

export default Input;