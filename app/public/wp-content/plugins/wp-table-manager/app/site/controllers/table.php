<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use http\Encoding\Stream\Inflate;
use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WptmControllerExcel
 */
class WptmControllerTable extends Controller
{
    /**
     * Get table html raw
     *
     * @return void
     */
    public function loadContent()
    {
        global $wpdb;
        $id = Utilities::getInt('id', 'GET');
        $content = '';
        $return = array();

        if ((int)$id > 0) {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $content = $WptmFilter->replaceTable($id, true);

            $return = array(
                'content' => $content['content'],
                'title' => $content['name'],
            );
        }

        wp_send_json_success($return);
        die;
    }
    /**
     * Get chart html raw
     *
     * @return void
     */
    public function loadContentChart()
    {
        global $wpdb;
        $id = Utilities::getInt('id', 'GET');
        $content = '';
        $return = array();

        if ((int)$id > 0) {
            Application::getInstance('Wptm');
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
            $WptmFilter = new \WptmFilter();

            $content = $WptmFilter->replaceChart($id, true);

            $return = array(
                'content' => $content['content'],
                'title' => $content['name'],
                'contentJs' => $content['js'],
            );
        }

        wp_send_json_success($return);
        die;
    }

    /**
     * Get filter and cort option
     *
     * @param stdClass $styleTable Table option
     * @param stdClass $styleCols  Cols option
     *
     * @return stdClass
     */
    public function filterAndSort($styleTable, $styleCols)
    {
        $filterOptions = new stdClass();
        if (!isset($styleTable->enable_filters)) {
            $filterOptions->enable_filters = 0;
        } else {
            $filterOptions->enable_filters = (int)$styleTable->enable_filters;
        }
        if (!isset($styleTable->enable_filters_all)) {
            $filterOptions->enable_filters_all = 0;
        } else {
            $filterOptions->enable_filters_all = (int)$styleTable->enable_filters_all;
        }
        if (!isset($styleTable->use_sortable)) {
            $filterOptions->use_sortable = 0;
        } else {
            $filterOptions->use_sortable = (int)$styleTable->use_sortable;
        }
        $filterOptions->default_order_sortable = isset($styleTable->default_order_sortable) ? (int)$styleTable->default_order_sortable : 0;
        $filterOptions->default_sortable = isset($styleTable->default_sortable) ? (int)$styleTable->default_sortable : 0;

        $filterOptions->cols = array();
        foreach ($styleCols as $col => $option) {
            if (is_object($option) && isset($option->{1})) {
                $option1 = $option->{1};
                $index = $option->{0};
            } elseif (is_array($option) && isset($option[1])) {
                $option1 = $option[1];
                $index = $option[0];
            }
            if (isset($option1->searchContentType) && !(isset($option1->hide_column) && (int)$option1->hide_column === 1)) {
                $datasCol = new stdClass();
                $datasCol->searchContentType = $option1->searchContentType;
                $datasCol->sortable = isset($option1->sortable) ? $option1->sortable : 1;
                $datasCol->filter = isset($option1->filter) ? $option1->filter : 1;
                $datasCol->min = isset($option1->min) ? $option1->min : 1;
                $datasCol->max = isset($option1->max) ? $option1->max : 10000;
                $datasCol->searchType = isset($option1->searchType) ? $option1->searchType : 'none';
                $datasCol->enable_filters_label = isset($option1->enable_filters_label) ? $option1->enable_filters_label : '';
                $datasCol->checkBoxContent = array();
                if (isset($option1->checkBoxContent) && $option1->checkBoxContent !== '') {
                    $datasCol->checkBoxContent = explode('|', $option1->checkBoxContent);
                }
                $datasCol->index = $index;
                $filterOptions->cols[] = $datasCol;
            }
        }
        return $filterOptions;
    }

    /**
     * Get data for pagination ajax
     *
     * @param null|integer $id              Table id
     * @param array        $createDataTable Params to create a table
     *
     * @return array|void
     */
    public function loadPage($id = null, $createDataTable = array())
    {
        global $wpdb;
        $method = 'POST'; // You can change to post for more beautiful ajax url
        if (is_null($id)) {
            $id = Utilities::getInt('id', 'GET');
            $start = Utilities::getInt('start', $method);
            $limit = Utilities::getInt('length', $method);
            $columns = Utilities::getInput('columns', $method, 'none');
            $orders = Utilities::getInput('order', $method, 'none');
            $draw = Utilities::getInt('draw', $method);
            $searchAll = Utilities::getInput('searchAll', $method, 'none');
            $searchRange = Utilities::getInput('searchRange', $method, 'none');
            $getHeader = false;
        } else {//get all rows by filter
            $start = isset($createDataTable['start']) ? $createDataTable['start'] : 0;
            $limit = isset($createDataTable['length']) ? $createDataTable['length'] : -1;//-1 is full
            $columns = isset($createDataTable['columns']) ? $createDataTable['columns'] : null;
            $orders = isset($createDataTable['order']) ? $createDataTable['order'] : null;//array(array('column' => '0', 'dir' => 'asc'))
            $draw = isset($createDataTable['draw']) ? $createDataTable['draw'] : null;
            $getHeader = isset($createDataTable['draw']) ? $createDataTable['draw'] : true;
            $searchAll = isset($createDataTable['searchAll']) ? $createDataTable['searchAll'] : false;
            $searchRange = isset($createDataTable['searchRange']) ? $createDataTable['searchRange'] : false;
        }

        Application::getInstance('Wptm');
        /* @var WptmModelTable $tableModel */
        $tableModel = $this->getModel('table');
        /* @var WptmModelDbtable $dbTableModel */
        $dbTableModel = $this->getModel('dbtable');

        /* @var WptmModelConfigsite $modelConfig */
        $modelConfig = $this->getModel('configSite');
        $paramsConfig = $modelConfig->getConfig();

//        $table_name = $wpdb->prefix . 'wptm_tables';

//        $item = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $table_name . ' as c WHERE c.id = %d', (int)$id), OBJECT);
//        $params = json_decode($item->params);

        $table = $tableModel->getItem($id, false, true, null, true);
        $params = $table->style->table;

        $cellsStyle = $table->style->cells;
        $columnsStyle = $table->style->cols;

        $table->sortFilters = $this->filterAndSort($params, $columnsStyle);

        $has_hide_column = false;
        $filters = array();

        if (isset($columnsStyle)) {
            $hide_columns = array();
            $filters['hide_columns'] = array();
            foreach ($columnsStyle as $key => $item) {
                if (!empty($item)) {
                    $firstCol = is_object($item) ? $item->{1} : $item[1];
                    if (!empty($firstCol->hide_column) && (int)$firstCol->hide_column === 1) {
                        $hide_columns[] = (int)$key;
                        $filters['hide_columns'][] = 1;
                        $has_hide_column = true;
                    } else {
                        $filters['hide_columns'][] = 0;
                    }
                }
            }
        }

        $headerOffset = isset($params->headerOption) ? intval($params->headerOption) : 0;
        $header_data = isset($params->header_data) ? $params->header_data : null;

        $filters['headerOffset'] = $headerOffset;
        $filters['getLine'] = true;
        $filters['searchAll'] = !isset($searchAll) ? '' : $searchAll;

        require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
        $wptmHelper = new WptmHelper();

        if ($table->type === 'mysql') {
            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'dbtable.php';
            $modelDbTable = new WptmModelDbtable();

            $query_option = $modelDbTable->getQueryOption($id);
            $query_option = json_decode($query_option->params);//object

            if (is_object($params) && is_string($table->params)) {
                $params = json_decode($table->params, true);
            } elseif (!is_array($params)) {
                $params = json_decode(json_encode($table->params), true);
            }
            // Prepare $filters for filter and sorting in database table
            $query = $table->mysql_query;
            $filters['where'] = '';
            $filters['order'] = '';

            $queries = $this->regenerateQueryForAjax($query, $filters, $query_option);
            $datas = $dbTableModel->getTableData($queries[0]);

            $datas = array_map('array_values', $datas);
            $totalRows = intval($wpdb->get_var($queries[1]));
            $totalFilteredRows = $totalRows;
        } else {
            $totalRows = $tableModel->countRows($id, $table);
            $filters['where'] = '';
            $filters['order'] = '';
            $datas = $tableModel->getTableData($table->mysql_table_name, $filters);
        }

        if ($start === 0) {
            $page = 1;
        } else {
            $page = ($start / $limit) + 1;
        }

        if ($page <= 0) {
            $page = 1;
        }

        $filters['page'] = $page;
        $filters['limit'] = $limit;
        $filters['order'] = $orders;
        $filters['where'] = $columns;
        $filters['searchRange'] = $searchRange;

        $startId = ($page - 1) * $limit;
//        error_log(json_encode([$page, $limit, $startId, $headerOffset]));
        if ($page === 1) {
            $startId += $headerOffset;
        }
        $filters['startId'] = $startId;


        if (is_array($datas) && count($datas) > 0) {
            //merge cells
            $keyMergeSetting = array();
            if (isset($params->mergeSetting)) {
                $mergeSetting = is_string($params->mergeSetting) ? json_decode($params->mergeSetting, true) : $params->mergeSetting;
                $count = count($mergeSetting);
                for ($i = 0; $i < $count; $i ++) {
                    $keyMergeSetting[$mergeSetting[$i]['row'] . '!' . $mergeSetting[$i]['col']] = array($mergeSetting[$i]['row'], $mergeSetting[$i]['rowspan'], $mergeSetting[$i]['col'], $mergeSetting[$i]['colspan']);
                }
            }

            if (!isset($wptmHelper->date_formats) || $wptmHelper->date_formats === '') {
                $wptmHelper->date_formats    = (!empty($paramsConfig['date_formats'])) ? $paramsConfig['date_formats'] : 'Y-m-d';
                $wptmHelper->date_formats    = (!empty($params->date_formats)) ? $params->date_formats : $wptmHelper->date_formats;
            }
            if (!isset($wptmHelper->symbol_position) || $wptmHelper->symbol_position === '') {
                $wptmHelper->symbol_position = (!empty($paramsConfig['symbol_position'])) ? $paramsConfig['symbol_position'] : 0;
                $wptmHelper->symbol_position = (!empty($params->symbol_position)) ? $params->symbol_position : $wptmHelper->symbol_position;
            }
            if (!isset($wptmHelper->currency_symbol) || $wptmHelper->currency_symbol === '') {
                $wptmHelper->currency_symbol = (!empty($paramsConfig['currency_sym'])) ? $paramsConfig['currency_sym'] : '$';
                $wptmHelper->currency_symbol = (!empty($params->currency_symbol)) ? $params->currency_symbol : $wptmHelper->currency_symbol;
            }
            if (!isset($wptmHelper->decimal_symbol) || $wptmHelper->decimal_symbol === '') {
                $wptmHelper->decimal_symbol  = (!empty($paramsConfig['decimal_sym'])) ? $paramsConfig['decimal_sym'] : '.';
                $wptmHelper->decimal_symbol  = (!empty($params->decimal_symbol)) ? $params->decimal_symbol : $wptmHelper->decimal_symbol;
            }
            if (!isset($wptmHelper->decimal_count) || $wptmHelper->decimal_count === '') {
                $wptmHelper->decimal_count   = (!empty($paramsConfig['decimal_count'])) ? $paramsConfig['decimal_count'] : 0;
                $wptmHelper->decimal_count   = (isset($params->decimal_count)) ? $params->decimal_count : $wptmHelper->decimal_count;
            }
            if (!isset($wptmHelper->thousand_symbol) || $wptmHelper->thousand_symbol === '') {
                $wptmHelper->thousand_symbol = (!empty($paramsConfig['thousand_sym'])) ? $paramsConfig['thousand_sym'] : ',';
                $wptmHelper->thousand_symbol = isset($params->thousand_symbol) ? $params->thousand_symbol : $wptmHelper->thousand_symbol;
            }

            require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
                . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $activeSheet = $spreadsheet->createSheet(1);
            $maxRows = count($datas);
            $activeSheet->fromArray($wptmHelper->renderValueCalculateCell($datas, $maxRows), null, 'A1');

            $dataCells = $this->getContentCells($filters, $datas, $activeSheet, $wptmHelper, $table, $params, $maxRows, $keyMergeSetting);
        } else {
            $dataCells = array(array(), 0);
        }

        $return = array(
            'draw' => $draw,
            'data' => $dataCells[0],
            'page' => $page,
            'recordsTotal' => intval($totalRows),
            'recordsFiltered' => intval($dataCells[1])
        );

        if (isset($createDataTable['notAjax'])) {
            return $return;
        }

        wp_send_json_success($return);
        die;
    }

    /**
     * Get default value for filter cols
     *
     * @return void
     */
    public function getDefaultValueForCols()
    {
        global $wpdb;
        $method = 'POST'; // You can change to post for more beautiful ajax url
        $id = Utilities::getInt('id', $method);
        $columns = Utilities::getInput('listCols', $method, 'none');
        $hiddenColumns = Utilities::getInput('hiddenColumns', $method, 'none');
        $dataTbody = $this->loadPage($id, array('notAjax' => true, 'draw' => false, 'start' => 0, 'length' => -1, 'searchAll' => true));

        $dataResult = array();
//        $dataKey = array();
//        foreach ($columns as $key => $columnId) {
////            $columns[$key] = 'filter' . $columnId;
//            $dataKey['filter' . $columnId] = $key;
//            $dataResult->{'filter' . $columnId} = array();
//        }

        if (!empty($hiddenColumns)) {
            $columnsHasHidden = array();
            foreach ($columns as $key => $columnId) {
                $count = array_reduce(array_slice($hiddenColumns, 0, $columnId), function ($count, $i) {
                    if ($i === '1') {
                        $count++;
                    }
                    return $count;
                }, 0);

                if ($count >= 0) {
                    $columnsHasHidden[$key] = (int)$columns[$key] - $count;
                }
            }
        }

        foreach ($dataTbody['data'] as $dataRow) {
            foreach ($columns as $key => $columnId) {
                $id = $columnsHasHidden[$key];
                $dataColumn = $dataRow['filter' . $id];

//                if (strlen($dataColumn) > 50) {
//                    $dataResult[$columnId][$dataColumn] = strip_tags($dataColumn);
//                } else {
                    $dataResult[$columnId][$dataColumn] = $dataColumn;
//                }
            }
////            $dataColumn = array_intersect_key($dataRow, array_flip($columns));
//            $dataColumn = array_intersect_key($dataRow, $dataKey);
//            foreach ($dataColumn as $key => $data) {
//                if (strlen($data) > 50) {
//                    $dataResult->{$key}[$data] = strip_tags($data);
//                } else {
//                    $dataResult->{$key}[$data] = $data;
//                }
//            }
        }

        wp_send_json_success($dataResult);
        die;
    }

    /**
     * Get cells content for table
     *
     * @param array   $filters         Filter
     * @param array   $datas           Value cells
     * @param object  $activeSheet     PhpSpreadsheet
     * @param object  $wptmHelper      Class wptmHelper
     * @param object  $table           Table params
     * @param object  $params          Params
     * @param integer $maxRows         Count row
     * @param array   $keyMergeSetting Merge cell setting
     *
     * @return array
     */
    public function getContentCells($filters, $datas, $activeSheet, $wptmHelper, $table, $params, $maxRows, $keyMergeSetting)
    {
        //filter column option
        $tblStyles = $table->style;
        $searchOptions = $table->sortFilters;
        $order = array();
        if (!empty($filters['order']) && count($filters['order']) > 0) {
            $count = count($filters['order']);
            for ($i = 0; $i < $count; $i++) {
                $order[$filters['order'][$i]['column']] = $filters['order'][$i]['dir'];
            }
        }
        //search column option
        $searchColumns = array();
        if (!empty($filters['where']) && count($filters['where']) > 0) {
            $count = count($filters['where']);
            for ($i = 0; $i < $count; $i++) {
                if ($filters['where'][$i]['search']['value'] !== '') {
                    $filters['where'][$i]['search']['value'] = str_replace('\"', '"', $filters['where'][$i]['search']['value']);
                    $searchColumns[$filters['where'][$i]['data']] = $filters['where'][$i]['search']['value'];
                }
            }
        }
        //searchAll
        if (!empty($filters['searchAll']) && $filters['searchAll'] !== '' && $filters['searchAll'] !== false) {
            $searchAll = $filters['searchAll'];
        } else {
            $searchAll = false;
        }
        //search range
        $searchRanges = $filters['searchRange'];

        $hide_column = $filters['hide_columns'];
        $cellsStyle = $tblStyles->cells;

        if (isset($params->hyperlink) && is_string($params->hyperlink)) {
            $tableHyperlink = json_decode($params->hyperlink);
        } elseif (!isset($params->hyperlink)) {
            $tableHyperlink = new stdClass();
        } else {
            $tableHyperlink = $params->hyperlink;
        }

        $headerCount = isset($filters['headerOffset']) ? $filters['headerOffset'] : 1;

        $newDatas = array();
        $indexRow = 0;
        for ($i = 0; $i < $maxRows; $i++) {
            $rowData = $datas[$i];

            $newRow = array();
            $newRow['DT_RowId'] = isset($rowData['DT_RowId']) ? intval($rowData['DT_RowId']) : $i;
            if (!($headerCount <= $newRow['DT_RowId'])) {
                continue;
            }

            $newRow['format_number'] = array();
            $columnIndex = 0;

            //validate of search all
            $validateSearchAll = false;
            //validate of search
            $valiedate = true;

            foreach ($rowData as $k => $value) {
                $col = preg_replace('/a/', '', $k);
                if ($k !== 'DT_RowId' && !(isset($hide_column[$col]) && $hide_column[$col] === 1)) {
                    $tblStyle = !empty($cellsStyle[$newRow['DT_RowId'] . '!' . $col])
                        ? $cellsStyle[$newRow['DT_RowId'] . '!' . $col][2] : array();

                    $position = array();
                    $position[] = $wptmHelper->getNameFromNumber($col);
                    $position[] = $newRow['DT_RowId'] + 1;
                    $position[] = $col;

                    if (isset($searchOptions->cols[$col]) || isset($order[$columnIndex])) {
                        $newRow['sort' . $columnIndex] = '';
                    }

                    if (isset($tblStyle['date_formats_momentjs']) && $tblStyle['date_formats_momentjs'] !== false) {
                        $has_format_date_cell = $tblStyle['date_formats_momentjs'];
                        $has_format_date_cell_old = $tblStyle['date_formats'];
                    } else {
                        $has_format_date_cell = '0';
                        $has_format_date_cell_old = false;
                    }

                    $dataSortAttr = '';

                    $value = do_shortcode($value);
                    $cellHtml = '';
//                    $newRow['format_date'][$columnIndex] = '';
                    if (isset($tblStyle)
                        && isset($tblStyle['cell_type'])
                        && (string)$tblStyle['cell_type'] === 'html') {//html cell
                        if (isset($tableHyperlink->{$newRow['DT_RowId'] . '!' . $col}) && $value !== ''  && stripos($value, $tableHyperlink->{$newRow['DT_RowId'] . '!' . $col}->hyperlink) >= 0) {
                            if (isset($params['openHyperlink']) && (int)$params['openHyperlink'] === 0) {
                                $cellHtml = '<a href="' . $tableHyperlink->{$newRow['DT_RowId'] . '!' . $col}->hyperlink . '">' . $value . '</a>';
                            } else {
                                $cellHtml = '<a target="_blank" href="' . $tableHyperlink->{$newRow['DT_RowId'] . '!' . $col}->hyperlink . '">' . $value . '</a>';
                            }
                        } else {
                            $cellHtml = $value;
                        }

//                        $cellHtml = esc_textarea($cellHtml);
                    } else {
//                        error_log(json_encode(['$tblStyle', $datas, '$tblStyles', $value, $position, $has_format_date_cell]));
                        $contentCell = $wptmHelper->getContentCell($tblStyle, $datas, $tblStyles, $value, $activeSheet, $position, $has_format_date_cell);
                        $cellHtml = $contentCell[0];
                        $has_format_date_cell = $contentCell[1];
                    }

                    if (isset($newRow['sort' . $columnIndex])) {
                        if ($has_format_date_cell !== '0' && $has_format_date_cell !== ' data-format="1" ') {
                            $newRow['sort' . $columnIndex] = $wptmHelper->convertDateToTimestamp($cellHtml, 'Y/m/d H:i:s', true);
                        } else {
                            $newRow['sort' . $columnIndex] = $cellHtml;
                        }
                    }

                    $newRow['format_number'][$columnIndex] = array();
                    if ($wptmHelper::$currency_symbol_cell !== null) {
                        $newRow['format_number'][$columnIndex]['position'] = $wptmHelper::$symbol_position_cell;
                        $newRow['format_number'][$columnIndex]['currency'] = $wptmHelper::$currency_symbol_cell;
                    }
                    if ($wptmHelper::$decimal_symbol_cell !== null) {
                        $newRow['format_number'][$columnIndex]['count'] = $wptmHelper::$decimal_count_cell;
                        $newRow['format_number'][$columnIndex]['decimal'] = $wptmHelper::$decimal_symbol_cell;
                        $newRow['format_number'][$columnIndex]['thousand'] = $wptmHelper::$thousand_symbol_cell;
                    }

                    if ((!$validateSearchAll && $searchAll) || (!empty($searchColumns) && !empty($searchColumns[$columnIndex]))) {
                        if (!empty($has_format_date_cell_old)) {//date filter
                            $newRow['filter' . $columnIndex] = $wptmHelper->convertDateToTimestamp($cellHtml, 'Y/m/d H:i:s', null, $has_format_date_cell_old);
                        } else {//number format
                            if (!empty($newRow['format_number'][$columnIndex])) {
                                $newRow['filter' . $columnIndex] = $wptmHelper->convertNumberByFormat(
                                    $cellHtml,
                                    $newRow['format_number'][$columnIndex]['decimal'],
                                    array('decimal_symbol' => '.', 'currency_symbol' => '$', 'thousand_symbol' => ','),
                                    $newRow['format_number'][$columnIndex]['count'],
                                    $newRow['format_number'][$columnIndex]['thousand']
                                );
                            } else {
                                $newRow['filter' . $columnIndex] = $cellHtml;
                            }
                        }
                    }

                    //fix wrap text
                    if (isset($tblStyle)
                        && isset($tblStyle['text_wrapping'])
                        && ((string)$tblStyle['text_wrapping'] === 'normal' || (string)$tblStyle['text_wrapping'] === 'hidden')) {
                        if (isset($tblStyle['cell_type']) && (string)$tblStyle['cell_type'] === 'html') {
                            $cellHtml = '<div>' . $cellHtml . '</div>';
                        } else {
                            $cellHtml = '<p>' . $cellHtml . '</p>';
                        }
                    }

                    if (!empty($tblStyle['tooltip_content'])) {
                        if (!empty($tblStyle['tooltip_width']) && (int)$tblStyle['tooltip_width'] > 0) {
                            $newRow[$columnIndex] = '<span class="wptm_tooltip ">' . (!empty($newRow[$columnIndex]) ? $newRow[$columnIndex] : $cellHtml) . '<span class="wptm_tooltipcontent" data-width="' . $tblStyle['tooltip_width'] . '">' . $tblStyle['tooltip_content'] . '</span></span>';
                        } else {
                            $newRow[$columnIndex] = '<span class="wptm_tooltip ">' . (!empty($newRow[$columnIndex]) ? $newRow[$columnIndex] : $cellHtml) . '<span class="wptm_tooltipcontent">' . $tblStyle['tooltip_content'] . '</span></span>';
                        }
                    } else {
                        $newRow[$columnIndex] = isset($newRow[$columnIndex]) ? $newRow[$columnIndex] : $cellHtml;
                    }

                    //merge cells
                    if (isset($keyMergeSetting) && isset($keyMergeSetting[$newRow['DT_RowId'] . '!' . $col])) {
                        if (empty($newRow['merges'])) {
                            $newRow['merges'] = array();
                        }
                        $newRow['merges'][$columnIndex] = $keyMergeSetting[$newRow['DT_RowId'] . '!' . $col];
                    }
                    //format_date_cell cells
                    if (empty($newRow['format_date_cell'])) {
                        $newRow['format_date_cell'] = array();
                    }
                    $newRow['format_date_cell'][$columnIndex] = $has_format_date_cell === ' data-format="1" ' ? '1' : $has_format_date_cell;

                    //searchAll
                    if (!$validateSearchAll && $searchAll && $searchAll !== true) {
                        if (isset($newRow['filter' . $columnIndex])) {
                            $validateSearchAll = stripos($newRow['filter' . $columnIndex], $searchAll) !== false;
                        } else {
                            $validateSearchAll = stripos($newRow[$columnIndex], $searchAll) !== false;
                        }
                    }

                    $columnIndex++;
                }

                if ($searchAll) {
                    $valiedate = $validateSearchAll;
                }
            }

            //searchRange
            if ($searchRanges && !empty($searchRanges)) {
                foreach ($searchRanges as $index => $searchRange) {
                    if (isset($newRow[$index]) && isset($searchRange[2])) {
                        if ($searchRange[2] === 'int') {
                            $valueSearchRange = floatval($newRow[$index]);
                        } else {
                            $valueSearchRange = floatval($newRow['sort' . $index]);
                        }

                        if (!($searchRange[0] <= $valueSearchRange && $valueSearchRange <= $searchRange[1])) {
                            $valiedate = false;
                        }
                    }
                }
            }

//            var_dump($valiedate, $newRow[1], $newRow['sort1']);

            if (!empty($searchColumns)) {
                foreach ($searchColumns as $index => $searchColumn) {
                    if ($searchColumn !== '' && isset($newRow[$index])) {
                        $valiFilter = isset($newRow['filter' . $index]) ? $newRow['filter' . $index] : $newRow[$index];

                        if ($searchOptions->cols[$index]->searchType !== 'checkBox') {
                            if (stripos($valiFilter, $searchColumn) !== false || stripos($newRow[$index], $searchColumn) !== false) {
                                $valiedate = $valiedate && true;
                            } else {
                                $valiedate = false;
                            }
                        } else {
                            if (stripos($searchColumn, $valiFilter) !== false) {
                                $valiedate = $valiedate && true;
                            } else {
                                $valiedate = false;
                            }

//                            $searchColumnArray = explode('|', $searchColumn);
//                            $validateCheckBox = false;
//
//                            foreach ($searchColumnArray as $searchColumnCheckBox) {
//                                if (stripos($valiFilter, $searchColumnCheckBox) !== false) {
//                                    $validateCheckBox = true;
//                                }
//                            }
//                            if (!$validateCheckBox) {
//                                $valiedate = false;
//                            } else {
//                                $valiedate = $valiedate && true;
//                            }
                        }
                    } else {
                        $valiedate = false;
                    }
                }
            }

            if ($valiedate || $searchAll === true) {
                $newDatas[$indexRow] = $newRow;
            }

            $indexRow++;
        }

//        var_dump($searchColumns, $newDatas);
//        die();

        /**
         * Sort column by value cell
         *
         * @param array $order Option
         *
         * @return Closure
         */
        function sortValue($order)
        {
            return function ($a, $b) use ($order) {
                //phpcs:ignore PHPCompatibility.Operators.NewOperators.t_spaceshipFound -- no support php version <= 5.6
                return $order[0] === 'asc' ? $a['sort' . $order[1]]<=>$b['sort' . $order[1]] : 0 - floatval($a['sort' . $order[1]]<=>$b['sort' . $order[1]]);
            };
        }

        if (count($order) > 0) {
            $orderLast = end($order);
            //phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_key_lastFound -- no support php version <= 7.2
            usort($newDatas, sortValue(array($orderLast, array_key_last($order))));
        }
        $totalFilteredRows = count($newDatas);


        if ($filters['limit'] === -1) {
            $filters['limit'] = $totalFilteredRows;

            return array(array_slice($newDatas, $filters['startId'] - $headerCount, $filters['limit']), $totalFilteredRows);
        }

        return array(array_slice($newDatas, $filters['startId'] - $headerCount, $filters['limit'] - $headerCount), $totalFilteredRows);
    }

    /**
     * Get cell format
     *
     * @param object $wptmHelper Class wptmHelper
     * @param array  $cellsStyle Cell style
     * @param string $row        Row index
     * @param string $col        ACol index
     *
     * @return array
     */
    public function getFormatCell($wptmHelper, $cellsStyle, $row, $col)
    {
        $format_date_cell = '0';
        $tblStyle = !empty($cellsStyle[$row . '!' . $col]) ? $cellsStyle[$row . '!' . $col][2] : array();
        if ((isset($tblStyle['decimal_count']) && $tblStyle['decimal_count'] !== false) || (isset($tblStyle['decimal_count_second']) && $tblStyle['decimal_count_second'] !== false)
            || (isset($tblStyle['decimal_symbol']) && $tblStyle['decimal_symbol'] !== false) || (isset($tblStyle['decimal_symbol_second']) && $tblStyle['decimal_symbol_second'] !== false)
            || (isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== false) || (isset($tblStyle['currency_symbol_second']) && $tblStyle['currency_symbol_second'] !== false)
        ) {
            $wptmHelper::$thousand_symbol_cell = (isset($tblStyle['thousand_symbol']) && $tblStyle['thousand_symbol'] !== false) ? $tblStyle['thousand_symbol'] : ((isset($tblStyle['thousand_symbol_second']) && $tblStyle['thousand_symbol_second'] !== false) ? $tblStyle['thousand_symbol_second'] : $wptmHelper->thousand_symbol);
            $wptmHelper::$decimal_count_cell = (isset($tblStyle['decimal_count']) && $tblStyle['decimal_count'] !== false) ? $tblStyle['decimal_count'] : ((isset($tblStyle['decimal_count_second']) && $tblStyle['decimal_count_second'] !== false) ? $tblStyle['decimal_count_second'] : $wptmHelper->decimal_count);
            $wptmHelper::$decimal_symbol_cell = (isset($tblStyle['decimal_symbol']) && $tblStyle['decimal_symbol'] !== false) ? $tblStyle['decimal_symbol'] : ((isset($tblStyle['decimal_symbol_second']) && $tblStyle['decimal_symbol_second'] !== false) ? $tblStyle['decimal_symbol_second'] : $wptmHelper->decimal_symbol);
            $wptmHelper::$currency_symbol_cell = (isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== false) ? $tblStyle['currency_symbol'] : ((isset($tblStyle['currency_symbol_second']) && $tblStyle['currency_symbol_second'] !== false) ? $tblStyle['currency_symbol_second'] : $wptmHelper->currency_symbol);
            $wptmHelper::$symbol_position_cell = (isset($tblStyle['symbol_position']) && $tblStyle['symbol_position'] !== false) ? $tblStyle['symbol_position'] : ((isset($tblStyle['symbol_position_second']) && $tblStyle['symbol_position_second'] !== false) ? $tblStyle['symbol_position_second'] : $wptmHelper->symbol_position);
            $has_format_cell = true;
        } else {
            $wptmHelper::$thousand_symbol_cell = null;
            $wptmHelper::$decimal_count_cell = null;
            $wptmHelper::$decimal_symbol_cell = null;
            $wptmHelper::$currency_symbol_cell = null;
            $wptmHelper::$symbol_position_cell = null;
            $has_format_cell = false;
        }
        if ($col !== 'DT_RowId') {
            if (isset($tblStyle['date_formats_momentjs']) && $tblStyle['date_formats_momentjs'] !== '' && $tblStyle['date_formats_momentjs'] !== false) {
                $format_date_cell = '' . $tblStyle['date_formats_momentjs'];
            } else {
                $format_date_cell = '0';
            }
        }
        return array($wptmHelper, $tblStyle, $has_format_cell, $format_date_cell);
    }

    /**
     * Regenerate query for ajax
     *
     * @param array  $queryDatas   Get data query
     * @param array  $filters      Filter
     * @param object $query_option Query option
     *
     * @return string|array
     */
    public function regenerateQueryForAjax($queryDatas, $filters, $query_option)
    {
        require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
            . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
        $parser = new \PHPSQLParser\PHPSQLParser(false, true);
        $creator = new \PHPSQLParser\PHPSQLCreator();

        $queryDatas = str_replace(array("\n\r", "\r\n", "\n", "\r", '&#10;'), ' ', $queryDatas);
        $queryDatas = str_replace('\\', '', $queryDatas);
        $parserMysql = $parser->parse($queryDatas, true);

        $columnsCount = isset($parserMysql['SELECT'])
            ? count($parserMysql['SELECT'])
            : (isset($parserMysql['select'])
                ? count($parserMysql['select'])
                : (isset($filters['hide_columns']) && count($filters['hide_columns']) > 0
                    ? count($filters['hide_columns'])
                    : count($filters['where'])
                )
            );

        //page and limit
        $offset = 0;
        $limit = isset($filters['limit']) ? $filters['limit'] : -1;
        $page = isset($filters['page']) ? $filters['page'] : 1;
        $headerOffset = isset($filters['headerOffset']) ? $filters['headerOffset'] : 1;

        $table_alias = isset($query_option->table_alias) ? $query_option->table_alias : array();

        //where
        // Build Where query part
        if (is_array($filters['where']) && count($filters['where']) > 0) {
            for ($i = 0; $i < $columnsCount; $i++) {//all columns in table
                if (!(isset($filters['hide_columns'][$i]) && (int)$filters['hide_columns'][$i] === 1)
                    && !(isset($query_option->column_options) && !isset($query_option->column_options[$i]))) {
                    if (isset($filters['hide_columns'])) {
                        $j2 = $i;
                        for ($ii = 0; $ii < $i; $ii++) {
                            if (isset($filters['hide_columns'][$ii]) && $filters['hide_columns'][$ii] === 1) {
                                $j2--;
                            }
                        }
                    }

                    if (isset($filters['where'][$j2]['search']['value']) && ($filters['where'][$j2]['search']['value'] !== '' || $filters['searchAll'] !== '')) {
                        if (empty($parserMysql['WHERE'])) {
                            $parserMysql['WHERE'] = array();
                        }
                        if (!empty($parserMysql['WHERE']) && count($parserMysql['WHERE']) > 0) {
                            if ($filters['searchAll'] !== '') {
                                array_push(
                                    $parserMysql['WHERE'],
                                    array(
                                        'expr_type' => 'operator',
                                        'base_expr' => 'OR',
                                        'sub_tree' => false
                                    )
                                );
                            } else {
                                array_push(
                                    $parserMysql['WHERE'],
                                    array(
                                        'expr_type' => 'operator',
                                        'base_expr' => 'AND',
                                        'sub_tree' => false
                                    )
                                );
                            }
                        }

                        if (isset($query_option->column_options[$i])) {
                            $column_option = $query_option->column_options[$i];
                            $table_column_name = (isset($table_alias[$column_option->table]) ? $table_alias[$column_option->table] : $column_option->table) . '.' . $column_option->Field;
                        } elseif (isset($parserMysql['SELECT'][$i])) {
                            $column_option = new stdClass();
                            $name_column = explode('.', $parserMysql['SELECT'][$i]['base_expr']);
                            $column_option->table =  $name_column[0];
                            $column_option->Field = $name_column[1];
                            $table_column_name = $parserMysql['SELECT'][$i]['base_expr'];
                        }

                        array_push(
                            $parserMysql['WHERE'],
                            array(
                                'expr_type' => 'colref',
                                'base_expr' => $table_column_name,
                                'no_quotes' => array(
                                    'delim' => '.',
                                    'parts' => array(
                                        $column_option->table,
                                        $column_option->Field
                                    )
                                ),
                                'sub_tree' => false
                            ),
                            array(
                                'expr_type' => 'operator',
                                'base_expr' => 'LIKE',
                                'sub_tree' => false
                            ),
                            array(
                                'expr_type' => 'const',
                                'base_expr' => '"%' . ($filters['searchAll'] !== '' ? $filters['searchAll'] : $filters['where'][$j2]['search']['value']) . '%"',
                                'sub_tree' => false
                            )
                        );
                    }
                }
            }
        }

        if ($limit > 0 && wp_doing_ajax()) {
//            if (isset($parserMysql['limit']) || isset($parserMysql['LIMIT'])) {//query has limit
            if (!empty($parserMysql['LIMIT'])) {
                $offset = is_object($parserMysql['LIMIT']) ? $parserMysql['LIMIT']->offset : $parserMysql['LIMIT']['offset'];
                unset($parserMysql['LIMIT']);
            } elseif (!empty($parserMysql['limit'])) {
                $offset = is_object($parserMysql['limit']) ? $parserMysql['limit']->offset : $parserMysql['limit']['offset'];
                unset($parserMysql['limit']);
            }
//            }

            $offset += ($page - 1) * $limit + ($headerOffset - 1);
            if ($headerOffset > 0 && $page === 1) {
                $limit -= $headerOffset;
            }
            $parserMysql['LIMIT'] = array(
                'offset' => $offset,
                'rowcount' => $limit
            );
        }

        unset($parserMysql['ORDER']);
        unset($parserMysql['order']);
        if (is_array($filters['order']) && count($filters['order'])) {
            $parserMysql['ORDER'] = array();
            foreach ($filters['order'] as $index => $ord) {
                $columnIndex = intval($ord['column']);
                for ($i = 0; $i <= $columnIndex; $i++) {
                    if (isset($filters['hide_columns'][$i]) && $filters['hide_columns'][$i] === 1) {
                        $columnIndex++;
                    }
                }

                if (isset($query_option->column_options[$columnIndex])) {
                    $column_option = $query_option->column_options[$columnIndex];
                    $table_column_name = (isset($table_alias[$column_option->table]) ? $table_alias[$column_option->table] : $column_option->table) . '.' . $column_option->Field;
                } elseif (isset($parserMysql['SELECT'][$columnIndex])) {
                    $column_option = new stdClass();
                    $name_column = explode('.', $parserMysql['SELECT'][$columnIndex]['base_expr']);
                    $column_option->table =  $name_column[0];
                    $column_option->Field = $name_column[1];
                    $table_column_name = $parserMysql['SELECT'][$columnIndex]['base_expr'];
                }

                $parserMysql['ORDER'][$index] = array(
                    'expr_type' => 'colref',
                    'base_expr' => $table_column_name,
                    'no_quotes' => array(
                        'delim' => '.',
                        'parts' => array(
                            $column_option->table,
                            $column_option->Field
                        )
                    ),
                    'sub_tree' => false,
                    'direction' => strtoupper($ord['dir'])
                );
            }
        }

        $parserMysql = $creator->create($parserMysql);

        $countTotal = 'SELECT SUM(1) AS number_row FROM (' . $queryDatas . ') AS tmp';

        return array($parserMysql, $countTotal);
    }

    /**
     * Create where wildcard
     *
     * @param string $column Column name
     * @param string $value  Value column
     *
     * @return array
     */
    private function createWhereWildcard($column, $value)
    {
        return $this->createWhere($column, 'plikep', $value);
    }

    /**
     * Create where in query
     *
     * @param string $column   Column name
     * @param string $operator Operator
     * @param string $value    Value column
     *
     * @return array
     */
    private function createWhere($column, $operator, $value)
    {
        $where = array();
        $where['column'] = $column;
        $where['operator'] = $operator;
        $where['value'] = $value;

        return $where;
    }

    /**
     * Function print file in frontend
     *
     * @return void
     */
    public function printFile()
    {
        $app    = Application::getInstance('Wptm', __FILE__, 'admin');
        $id     = Utilities::getInput('id', 'GET', 'string');

        $createDataTable = array();
        $createDataTable['start'] = 0;
        $createDataTable['length'] = -1;
        $createDataTable['columns'] = null;
        $createDataTable['order'] = null;
        $createDataTable['draw'] = null;
        $columns = Utilities::getInput('columns', 'POST', 'none');
        $searchPrint = Utilities::getInput('searchPrint', 'POST', 'none');
        $searchColumnsForm = Utilities::getInput('searchColumnsForm', 'POST', 'none');
        $searchAll = Utilities::getInput('searchAll', 'POST', 'none');
        $searchRange = Utilities::getInput('searchRange', 'POST', 'none');
        $orderPrint = Utilities::getInput('orderPrint', 'POST', 'none');

        if (!empty($columns)) {
            $createDataTable['columns'] = $columns;
        }
//        if (!empty($order)) {
//            $createDataTable['order'] = $order;
//        }
        if (!empty($searchPrint)) {
            foreach ($searchPrint as $index => $value) {
                if (!empty($createDataTable['columns']) && !empty($createDataTable['columns'][$index]) && $value !== '') {
                    $createDataTable['columns'][$index]['search']['value'] = $value;
                }
            }
        }
        if (!empty($searchColumnsForm)) {
            foreach ($searchColumnsForm as $index => $value) {
                if (!empty($createDataTable['columns']) && !empty($createDataTable['columns'][$index]) && $value !== '') {
                    $createDataTable['columns'][$index]['search']['value'] = $value;
                }
            }
        }
        if ($searchAll !== null && $searchAll !== '') {
            $createDataTable['searchAll'] = $searchAll;
        }
        if (!empty($searchRange)) {
            $createDataTable['searchRange'] = $searchRange;
        }
//        if (!empty($orderPrint) && !empty($createDataTable['order'])) {
        if (!empty($orderPrint)) {
            $createDataTable['order'] = array();
            $createDataTable['order'][0] = array('column' => '' . $orderPrint[0][0], 'dir' => '' . $orderPrint[0][1]);
        }

        if (isset($id) && (int)$id > 0) {
            $this->loadPage($id, $createDataTable);
        }
        $this->exitStatus(false, esc_attr__('File not found or You do not have permission to download the file.', 'wptm'));
    }

    /**
     * Render table preview in gutenberg block
     *
     * @return void
     */
    public function preview()
    {
        $app    = Application::getInstance('Wptm', __FILE__, 'admin');
        $id     = Utilities::getInput('id', 'GET', 'string');
        $hash   = Utilities::getInput('hash', 'GET', 'string');
        if (!isset($hash)) {
            $hash = '';
        }

        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
        $WptmFilter = new \WptmFilter();

        $content = $WptmFilter->getTableForBlock($id, $hash);

        wp_send_json_success($content);
        die;
    }

    /**
     * Render chart preview in gutenberg block
     *
     * @return void
     */
    public function chartPreview()
    {
        $app    = Application::getInstance('Wptm', __FILE__, 'admin');
        $id_chart     = Utilities::getInput('id', 'GET', 'string');
        if (!isset($id_chart)) {
            wp_send_json_success(array(false, 'no chart'));
            die;
        }

        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/site/filters.php';
        $WptmFilter = new \WptmFilter();

        $content = $WptmFilter->replaceChart($id_chart, true);

        wp_send_json_success(array(true, $content));
        die;
    }
}
