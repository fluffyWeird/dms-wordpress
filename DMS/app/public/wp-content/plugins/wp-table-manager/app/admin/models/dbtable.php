<?php
/* Based on some work of wp Data Tables plugin */

/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Factory;

defined('ABSPATH') || die();

/**
 * Class WptmModelDbtable
 */
class WptmModelDbtable extends Model
{
    /*     * * For the WP DB type query ** */
    /**
     * Select arr
     *
     * @var array
     */
    private $select_arr = array();

    /**
     * Where arr
     *
     * @var array
     */
    private $where_arr = array();

    /**
     * Group arr
     *
     * @var array
     */
    private $group_arr = array();

    /**
     * From arr
     *
     * @var array
     */
    private $from_arr = array();

    /**
     * Inner join arr
     *
     * @var array
     */
    private $inner_join_arr = array();

    /**
     * Left join
     *
     * @var array
     */
    private $left_join_arr = array();

    /**
     * Check have group
     *
     * @var boolean
     */
    private $has_groups = false;

    /**
     * Query data
     *
     * @var string
     */
    private $query = '';

    /**
     * Get list sql table
     *
     * @return array
     */
    public function listMySQLTables()
    {

        $tables = array();
        global $wpdb;
        $result = $wpdb->get_results('SHOW TABLES', ARRAY_N);

        // Formatting the result to plain array
        foreach ($result as $row) {
            $tables[] = $row[0];
        }

        return $tables;
    }

    /**
     * Return a list of columns for the selected tables
     *
     * @param array $tables Data table
     *
     * @return array
     */
    public static function listMySQLColumns($tables)
    {
        $columns = array('all_columns' => array(), 'sorted_columns' => array());
        if (!empty($tables)) {
            global $wpdb;
            foreach ($tables as $table) {
                $columns['sorted_columns'][$table] = array();

                //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- $table is name table in database, it already escaped
                $table_columns = $wpdb->get_results('SHOW COLUMNS FROM ' . $table, ARRAY_A);

                foreach ($table_columns as $table_column) {
                    $columns['sorted_columns'][$table][] = $table . '.' . $table_column['Field'];
                    $columns['all_columns'][] = $table . '.' . $table_column['Field'];
                }
            }
        }

        return $columns;
    }

    /**
     * Checks that the table and column are valid and exist in the database.
     *
     * @param array  $table_data Data table
     * @param string $value      Value need to check
     *
     * @return boolean
     */
    public function checkValidValue($table_data, $value = '')
    {
        $value_arr = explode('.', $value);
        if ($value === '' || empty($value_arr) || $value_arr === null || count($value_arr) < 2) {
            return false;
        }
        $tables = $table_data['tables'];

        if (!in_array($value_arr[0], $tables)) {
            return false;
        }
        $columns = self::listMySQLColumns(array($value_arr[0]));
        if (!in_array($value, $columns['all_columns'])) {
            return false;
        }
        return true;
    }

    /**
     * Return a build query and preview table
     *
     * @param array $table_data Data table
     *
     * @return array
     */
    public function generateQueryAndPreviewdata($table_data)
    {
        global $wpdb;
        foreach ($table_data as $key => &$value) {
            if (is_array($value)) {
                foreach ($value as &$v) {
                    if (is_array($v)) {
                        foreach ($v as &$v1) {
                            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
                            $v1 = str_replace('`', '&#x60;', htmlentities(htmlspecialchars($v1, ENT_COMPAT | ENT_HTML401, 'UTF-8'), ENT_COMPAT | ENT_HTML401, 'UTF-8'));
                        }
                    } else {
                        //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
                        $v = str_replace('`', '&#x60;', htmlentities(htmlspecialchars($v, ENT_COMPAT | ENT_HTML401, 'UTF-8'), ENT_COMPAT | ENT_HTML401, 'UTF-8'));
                    }
                }
            } else {
                //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
                $value = str_replace('`', '&#x60;', htmlentities(htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8'), ENT_COMPAT | ENT_HTML401, 'UTF-8'));
            }
        }
        $this->table_data = apply_filters('wdt_before_generate_mysql_based_query', $table_data);
        if (!isset($this->table_data['where_conditions'])) {
            $this->table_data['where_conditions'] = array();
        }

        if (isset($this->table_data['grouping_rules'])) {
            $this->has_groups = true;
        }

        if (!isset($table_data['mysql_columns'])) {
            $table_data['mysql_columns'] = array();
        }

        // Initializing structure for the SELECT part of query
        $this->prepareMySQLSelectBlock();

        // Initializing structure for the WHERE part of query
        $this->prepareMySQLWhereBlock();

        // Prepare the GROUP BY block
        $this->prepareMySQLGroupByBlock();

        // Prepare the join rules
        $this->prepareMySQLJoinedQueryStructure();

        // Prepare the query itself
        $this->query = $this->buildMySQLQuery();

        if (isset($this->table_data['default_ordering']) && $this->table_data['default_ordering'] && $this->checkValidValue($this->table_data, $this->table_data['default_ordering'])) {
            $default_ordering_dir = strtolower($this->table_data['default_ordering_dir']) !== 'asc' && strtolower($this->table_data['default_ordering_dir']) !== 'desc' ? 'asc' : $this->table_data['default_ordering_dir'];
            $this->query .= 'Order by ' . esc_sql($this->table_data['default_ordering']) . ' ' . esc_sql($default_ordering_dir);
        }

        if (preg_match('/union/i', $this->query)) {
            $result = false;
        } else {
            $result = array(
                'query' => $this->query,
                'hasRow' => true,
                'preview' => $this->getQueryPreview()
            );
        }

        return $result;
    }

    /**
     * Get columns options
     *
     * @param array $custom_mysql Custom query table
     *
     * @return array
     */
    public function applyCustomQuery($custom_mysql)
    {
        global $wpdb;
        $wpdb->hide_errors();
        $this->query = $custom_mysql;

        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- query already escaped
        $result = $wpdb->get_results($this->query, ARRAY_A);
        if (!empty($result) && !empty($result[0])) {
            $headers = array();
            foreach ($result[0] as $column => $row0) {
                $headers[] = $column;
            }
            ob_start();
            include(WPTM_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'dbtable' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'table_preview.inc.php');
            $ret_val = ob_get_contents();
            ob_end_clean();
            $result = array(
                'hasRow' => true,
                'query' => $this->query,
                'preview' => $ret_val
            );
        } else {
            $result = array(
                'hasRow' => false,
                'query' => $this->query,
                'error' => $wpdb->last_error,
                'preview' => __('No results found', 'wptm')
            );
        }

        return $result;
    }

    /**
     * Removes all dangerous strings from query
     *
     * @param string $query Custom query
     *
     * @return boolean|array
     */
    public function checkCustomQuery($query)
    {
        preg_match_all('/CREATE|DELETE|DROP|INSERT|SET|UPDATE|REPLACE|create|delete|drop|insert|update|set|replace/i', $query, $check, PREG_OFFSET_CAPTURE);
        if (!empty($check[0])) {
            return $check[0];
        } else {
            return false;
        }
    }

    /**
     * Get columns options
     *
     * @param array      $table_data  Data table
     * @param array|null $listColumns Data table
     *
     * @return array
     */
    public function getTableColumnsOptions($table_data, $listColumns = null)
    {
        global $wpdb;
        $tableColumns = array();
        $i = 0;
        foreach ($table_data as $table) {
            $columns = $wpdb->get_results('SHOW COLUMNS FROM ' . $table, ARRAY_A);
            foreach ($columns as $column) {
                $column = $this->getColumnInTable($column);
                if ($listColumns !== null) {
                    if (in_array($column['Field'], $listColumns) || in_array($table . '.' . $column['Field'], $listColumns)) {
                        $tableColumns[$i] = $column;
                        $tableColumns[$i]['table'] = $table;
                        $i++;
                    }
                } else {
                    $tableColumns[$i] = $column;
                    $tableColumns[$i]['table'] = $table;
                    $i++;
                }
            }
        }

        return $tableColumns;
    }

    /**
     * Get list table alias
     *
     * @param array $parserMysql Data query
     *
     * @return array
     */
    public function getTableAliasOptions($parserMysql)
    {
        $countTable = count($parserMysql['FROM']);
        $tables_list_alias = array();
        for ($i = 0; $i < $countTable; $i++) {
            $table_in_query[] = $parserMysql['FROM'][$i]['table'];
            if (!empty($parserMysql['FROM'][$i]['alias'])) {
                $tables_list_alias[$parserMysql['FROM'][$i]['table']] = $parserMysql['FROM'][$i]['alias']['no_quotes']['parts'][0];
            }
        }

        return $tables_list_alias;
    }

    /**
     * Get column option
     *
     * @param array $column Column option raw
     *
     * @return array
     */
    public function getColumnInTable($column)
    {
        $column['priKey'] = false;
        $column['canEdit'] = 0;
        $column['notNull'] = false;
        //check key column
        switch ($column['Key']) {
            case 'MUL':
                $column['priKey'] = false;
                $column['canEdit'] = 1;
                break;
            case 'PRI':
                $column['priKey'] = true;
                $column['canEdit'] = 0;
                break;
            case 'UNI':
                $column['priKey'] = true;
                $column['canEdit'] = 1;
                break;
            default:
                $column['priKey'] = false;
                $column['canEdit'] = 1;
                break;
        }

        switch ($column['Null']) {
            case 'NO':
                $column['notNull'] = true;
                break;
            default:
                $column['notNull'] = false;
                break;
        }

        //not set type column
//
//        if ($column['Extra'] !== '') {
//            $column['canEdit'] = false;
//        }
        return $column;
    }

    /**
     * Generate the sample table with 5 rows from MySQL query
     *
     * @return string|void
     */
    public function getQueryPreview()
    {
        global $wpdb;

        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- query already escaped
        $result = $wpdb->get_results($this->query, ARRAY_A);

        if (!empty($result)) {
            $headers = isset($this->table_data['custom_titles']) ? $this->table_data['custom_titles'] : $this->table_data['mysql_columns'];
            ob_start();
            include(WPTM_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'dbtable' . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'table_preview.inc.php');
            $ret_val = ob_get_contents();
            ob_end_clean();
        } else {
            $ret_val = __('No results found', 'wptm');
        }

        return $ret_val;
    }

    /**
     * Helper function to generate the fields structure from MySQL tables
     *
     * @return void
     */
    private function prepareMySQLSelectBlock()
    {
        foreach ($this->table_data['mysql_columns'] as $key => $mysql_column) {
            $mysql_column_arr = explode('.', esc_sql($mysql_column));
            if (!isset($this->select_arr[$mysql_column_arr[0]])) {
                $this->select_arr[$mysql_column_arr[0]] = array();
            }
            if (isset($this->table_data['custom_titles'][$key])) {
                $this->select_arr[$mysql_column_arr[0]][] = esc_sql($mysql_column) . ' `' . esc_sql($this->table_data['custom_titles'][$key]) . '`';
            } else {
                $this->select_arr[$mysql_column_arr[0]][] = esc_sql($mysql_column);
            }
            // From
            if (!in_array($mysql_column_arr[0], $this->from_arr)) {
                $this->from_arr[] = $mysql_column_arr[0];
            }
        }
    }

    /**
     * Prepare a Where block for MySQL based
     *
     * @return void
     */
    private function prepareMySQLWhereBlock()
    {
        if (empty($this->table_data['where_conditions'])) {
            return;
        }

        foreach ($this->table_data['where_conditions'] as $where_condition) {
            if ($where_condition['column'] === '' || $where_condition['value'] === '') {
                continue;
            }
            $where_column_arr = explode('.', esc_sql($where_condition['column']));
            if (!in_array($where_column_arr[0], $this->from_arr)) {
                $this->from_arr[] = $where_column_arr[0];
            }

            if ($this->checkValidValue($this->table_data, $where_condition['column'])) {
                $this->where_arr[$where_column_arr[0]][] = self::buildWhereCondition(
                    esc_sql($where_condition['column']),
                    esc_sql($where_condition['operator']),
                    esc_sql($where_condition['value'])
                );
            }
        }
    }

    /**
     * Prepare a GROUP BY block for MySQL based
     *
     * @return void
     */
    private function prepareMySQLGroupByBlock()
    {
        if (!$this->has_groups) {
            return;
        }

        foreach ($this->table_data['grouping_rules'] as $grouping_rule) {
            if (empty($grouping_rule) || !$this->checkValidValue($this->table_data, $grouping_rule)) {
                continue;
            }
            $this->group_arr[] = esc_sql($grouping_rule);
        }
    }

    /**
     * Prepares the structure of the JOIN rules for MySQL based tables
     *
     * @return void
     */
    private function prepareMySQLJoinedQueryStructure()
    {
        if (!isset($this->table_data['join_rules'])) {
            return;
        }

        if (count($this->from_arr) > 1) {
            foreach ($this->table_data['join_rules'] as $join_rule) {
                if (empty($join_rule['initiator_column']) || empty($join_rule['connected_column']) || !in_array($join_rule['initiator_table'], $this->from_arr)) {
                    continue;
                }

                $connected_column_arr = explode('.', esc_sql($join_rule['connected_column']));
                if ($this->checkValidValue($this->table_data, esc_sql($join_rule['initiator_table']) . '.' . esc_sql($join_rule['initiator_column']))
                    && $this->checkValidValue($this->table_data, esc_sql($join_rule['connected_column']))) {
                    if (in_array($connected_column_arr[0], $this->from_arr) && count($this->from_arr) > 1) {
                        if ((string)$join_rule['type'] === 'left') {
                            $this->left_join_arr[$connected_column_arr[0]] = $connected_column_arr[0];
                        } else {
                            $this->inner_join_arr[$connected_column_arr[0]] = $connected_column_arr[0];
                        }
                        unset($this->from_arr[array_search($connected_column_arr[0], $this->from_arr)]);
                    } else {
                        if ((string)$join_rule['type'] === 'left') {
                            $this->left_join_arr[$connected_column_arr[0]] = $connected_column_arr[0];
                        } else {
                            $this->inner_join_arr[$connected_column_arr[0]] = $connected_column_arr[0];
                        }
                    }

                    $this->where_arr[$connected_column_arr[0]][] = self::buildWhereCondition(
                        esc_sql($join_rule['initiator_table']) . '.' . esc_sql($join_rule['initiator_column']),
                        'eq',
                        esc_sql($join_rule['connected_column']),
                        false
                    );
                } else {
                    break;
                }
            }
        }
    }

    /**
     * Prepares the query text for MySQL based table
     *
     * @return string
     */
    private function buildMySQLQuery()
    {
        // Build the final output
        $query = 'SELECT ';
        $i = 0;
        foreach ($this->select_arr as $table_alias => $select_block) {
            $query .= implode(",\n       ", $select_block);
            $i++;
            if ($i < count($this->select_arr)) {
                $query .= ",\n       ";
            }
        }
        $query .= " \nFROM ";
        $query .= implode(', ', $this->from_arr) . ' ';

        if (!empty($this->inner_join_arr)) {
            $i = 0;
            foreach ($this->inner_join_arr as $table_alias => $inner_join_block) {
                $query .= "\n INNER JOIN " . $inner_join_block . ' ';
                if (!empty($this->where_arr[$table_alias])) {
                    $query .= "\n  ON " . implode("\n   AND ", $this->where_arr[$table_alias]) . ' ';
                    unset($this->where_arr[$table_alias]);
                }
            }
        }

        if (!empty($this->left_join_arr)) {
            foreach ($this->left_join_arr as $table_alias => $left_join_block) {
                $query .= "\n LEFT JOIN " . $left_join_block . ' ';
                if (!empty($this->where_arr[$table_alias])) {
                    $query .= "\n  ON " . implode("\n   AND ", $this->where_arr[$table_alias]) . ' ';
                    unset($this->where_arr[$table_alias]);
                }
            }
        }
        if (!empty($this->where_arr)) {
            $i = 0;
            foreach ($this->where_arr as $table_alias => $where_block) {
                if ($i === 0) {
                    $query .= "\nWHERE ";
                }
                $query .= implode("\n AND ", $where_block);
                $i++;
                if ($i < count($this->where_arr)) {
                    $query .= "\n AND ";
                }
            }
            $query .= ' ';
        }

        if (!empty($this->group_arr)) {
            $query .= " \nGROUP BY " . implode(', ', $this->group_arr) . ' ';
        }
//        $query .= ' ';
        return $query;
    }


    /**
     * Prepares the structure of the WHERE rules for MySQL based tables
     *
     * @param string  $leftOperand  Left Operand
     * @param string  $operator     Operator
     * @param string  $rightOperand Right Operand
     * @param boolean $isValue      Value
     *
     * @return string
     */
    public static function buildWhereCondition($leftOperand, $operator, $rightOperand, $isValue = true)
    {
        $rightOperand = stripslashes_deep($rightOperand);
        $wrap = $isValue ? "'" : '';
        switch ($operator) {
            case 'eq':
                return $leftOperand . ' = ' . $wrap . $rightOperand . $wrap;
            case 'neq':
                return $leftOperand . ' != ' . $wrap . $rightOperand . $wrap;
            case 'gt':
                return $leftOperand . ' > ' . $wrap . $rightOperand . $wrap;
            case 'gtoreq':
                return $leftOperand . ' >= ' . $wrap . $rightOperand . $wrap;
            case 'lt':
                return $leftOperand . ' < ' . $wrap . $rightOperand . $wrap;
            case 'ltoreq':
                return $leftOperand . ' <= ' . $wrap . $rightOperand . $wrap;
            case 'in':
                return $leftOperand . ' IN (' . $rightOperand . ')';
            case 'like':
                return $leftOperand . ' LIKE ' . $wrap . $rightOperand . $wrap;
            case 'plikep':
                return $leftOperand . ' LIKE ' . $wrap . '%' . $rightOperand . '%' . $wrap;
        }
    }

    /**
     * Update query option by table id
     *
     * @param array   $query_option Query option
     * @param integer $id           Table id
     *
     * @return array|false|integer|object|void|null
     */
    public function updateQueryOption($query_option, $id)
    {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.id_table = %d AND c.option_name = %s', (int)$id, 'query_option'));

        if (empty($result)) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'wptm_table_options',
                array(
                    'id_table' => $id,
                    'option_name' => 'query_option',
                    'option_value' => json_encode($query_option)
                )
            );
        } elseif (!empty($result->id)) {
            $result = $wpdb->update(
                $wpdb->prefix . 'wptm_table_options',
                array('option_value' => json_encode($query_option)),
                array('id' => (int)$result->id, 'option_name' => 'query_option')
            );
        }

        return $result;
    }

    /**
     * Get query option by table id
     *
     * @param integer $id Table id
     *
     * @return boolean|mixed
     */
    public function getQueryOption($id)
    {
        global $wpdb;

        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.id_table = %d AND c.option_name = %s', (int)$id, 'query_option'));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep($wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.id_table = %d AND c.option_name = %s', (int)$id, 'query_option')));
    }

    /**
     * Create new table for selected mysql tables
     *
     * @param array        $table_data Data table
     * @param null|integer $id_cat     Id category
     *
     * @return array
     */
    public function createTable($table_data, $id_cat = null)
    {
        $query_option = array();
        $query_option['tables_list'] = $table_data['tables_list'];
        $query_option['column_options'] = $table_data['column_options'];
        unset($table_data['tables_list']);
        unset($table_data['column_options']);
        $params = $table_data;

        global $wpdb;
        if ($id_cat !== null) {
            $id_category = $id_cat;
        } else {
            $modelCategory = Model::getInstance('category');
            $id_category = $modelCategory->addCategory('Category');
        }
        $lastPos = (int)$wpdb->get_var($wpdb->prepare('SELECT MAX(c.position) AS lastPos FROM ' . $wpdb->prefix . 'wptm_tables as c WHERE c.id_category = %d', (int)$id_category));
        $lastPos++;
        $style = json_decode('{"table":{"use_sortable":"1"},"rows":{"0":[0,{"height":30,"cell_padding_top":"3","cell_padding_right":"3","cell_padding_bottom":"3","cell_padding_left":"3","cell_font_family":"Arial","cell_font_size":"13","cell_font_color":"#333333","cell_border_bottom":"2px solid #707070","cell_background_color":"#ffffff","cell_font_bold":true,"cell_vertical_align":"middle"}]},"cols":{"0":[0,{"width":50,"cell_text_align":"center","cell_font_bold":true}],"1":[1,{"width":122,"cell_text_align":"center"}],"2":[2,{"width":137,"cell_text_align":"center"}],"3":[3,{"width":133,"cell_text_align":"center"}],"4":[4,{"width":150,"cell_text_align":"center"}],"5":[5,{"width":50,"cell_text_align":"center"}]},"cells":{}}');
        $style->table->enable_pagination = $table_data['enable_pagination'];
        $style->table->limit_rows = $table_data['limit_rows'];

        require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
            . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
        $parser = new \PHPSQLParser\PHPSQLParser(false, true);
        $creator = new \PHPSQLParser\PHPSQLCreator();

        $params['query'] = str_replace(array("\n\r", "\r\n", "\n", "\r", '&#10;'), ' ', $params['query']);
        $params['query'] = str_replace('\\', '', $params['query']);
        $parserMysql = $parser->parse($params['query'], true);

        unset($params['query']);
        $params['table_type'] = 'mysql';

        if (empty($params['customquery'])
            && !empty($params['priKey'])
            && !empty($params['table_editing'])
            && !in_array($params['priKey'], $params['mysql_columns'])) {
            //no custom, priKey column not exist in columns be selected then create new query

            array_unshift(
                $parserMysql['SELECT'],
                array('expr_type' => 'colref',
                    'alias' => '',
                    'base_expr' => $params['priKey'],
                    'sub_tree' => false,
                    'delim' => ','
                )
            );

            $params['getPriKeyTableQuery'] = $creator->create($parserMysql);
        } else {
            $params['getPriKeyTableQuery'] = $table_data['query'];
        }
        //phpcs:ignore PHPCompatibility.Constants.NewConstants.json_unescaped_unicodeFound -- the use of JSON_UNESCAPED_UNICODE has check PHP version
        $data_params = json_encode($params, JSON_UNESCAPED_UNICODE);

        $wpdb->query($wpdb->prepare(
            'INSERT INTO ' . $wpdb->prefix . 'wptm_tables (id_category, title, style, mysql_query, params, created_time, modified_time, author, position, type) VALUES ( %d,%s,%s,%s,%s,%s,%s,%d,%d,%s)',
            $id_category,
            __('New table', 'wptm'),
            json_encode($style),
            $table_data['query'],
            $data_params,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            get_current_user_id(),
            $lastPos,
            'mysql'
        ));
        $user = get_userdata(get_current_user_id());

        $data = array('id' => $wpdb->insert_id, 'id_category' => $id_category, 'position' => $lastPos, 'title' => __('New table', 'wptm'), 'modified_time' => date(get_option('date_format') . ' ' . get_option('time_format')), 'author' => get_current_user_id(), 'type' => 'mysql', 'author_name' => $user->user_nicename);

        //create data in wptm_table_options table
        //get list all columns
        if (!empty($query_option['tables_list'])) {
            $query_option['columns_list'] = $this->getTableColumnsOptions($query_option['tables_list'], null);
            $table_alias = $this->getTableAliasOptions($parserMysql);
            if (count($table_alias)) {
                $query_option['table_alias'] = $table_alias;
            }
            $this->updateQueryOption($query_option, $wpdb->insert_id);
        }

        return $data;
    }

    /**
     * Update table with new change
     *
     * @param integer $id_table   Id table
     * @param array   $table_data Data table
     *
     * @return false|integer
     */
    public function updateTable($id_table, $table_data)
    {
        $query_option = array();
        $query_option['tables_list'] = $table_data['tables_list'];
        $query_option['column_options'] = $table_data['column_options'];
        unset($table_data['tables_list']);
        unset($table_data['column_options']);
        $params = $table_data;

        global $wpdb;

        require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
            . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
        $parser = new \PHPSQLParser\PHPSQLParser(false, true);
        $creator = new \PHPSQLParser\PHPSQLCreator();
        $params['query'] = str_replace(array("\n\r", "\r\n", "\n", "\r", '&#10;'), ' ', $params['query']);
        $params['query'] = str_replace('\\', '', $params['query']);
        $parserMysql = $parser->parse($params['query'], true);

        unset($params['query']);
        $params['table_type'] = 'mysql';

        if (empty($params['customquery'])
            && !empty($params['priKey'])
            && !empty($params['table_editing'])
            && !in_array($params['priKey'], $params['mysql_columns'])) {
            //no custom, priKey column not exist in columns be selected then create new query
            array_unshift(
                $parserMysql['SELECT'],
                array('expr_type' => 'colref',
                    'alias' => '',
                    'base_expr' => $params['priKey'],
                    'sub_tree' => false,
                    'delim' => ','
                )
            );

            $params['getPriKeyTableQuery'] = $creator->create($parserMysql);
        } else {
            $params['getPriKeyTableQuery'] = $table_data['query'];
        }
//        error_log($table_data['query']);
        $ret = $wpdb->update(
            $wpdb->prefix . 'wptm_tables',
            array('mysql_query' => $table_data['query'],
                'params' => json_encode($params),
                'modified_time' => date('Y-m-d H:i:s')
            ),
            array('id' => $id_table)
        );

        //update data in wptm_table_options table
        //get list all columns
        if (!empty($query_option['tables_list'])) {
            $query_option['columns_list'] = $this->getTableColumnsOptions($query_option['tables_list'], null);
            $table_alias = $this->getTableAliasOptions($parserMysql);
            if (count($table_alias)) {
                $query_option['table_alias'] = $table_alias;
            }
            $this->updateQueryOption($query_option, $id_table);
        }

        return $ret;
    }

    /**
     * Update table with new change
     *
     * @param integer $id_table Id table
     * @param string  $query    Query table
     *
     * @return false|integer
     */
    public function updateOldTable($id_table, $query)
    {
        $query_option = array();
        $query_option['tables_list'] = array();
        $query_option['column_options'] = array();
        $query_option['columns_list'] = array();

        require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
            . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
        $parser = new \PHPSQLParser\PHPSQLParser(false, true);
        $creator = new \PHPSQLParser\PHPSQLCreator();
        $query = str_replace(array("\n\r", "\r\n", "\n", "\r", '&#10;'), ' ', $query);
        $query = str_replace('\\', '', $query);
        $parserMysql = $parser->parse($query, true);

        $ret = false;

        //list columns
        //$parserMysql['SELECT'];//array
        $count = count($parserMysql['SELECT']);
        $column_options = array();
        for ($i = 0; $i < $count; $i++) {
            $nameColumn = explode('.', $parserMysql['SELECT'][$i]['base_expr']);
            if (!empty($nameColumn[1])) {
                $column_options[] = $nameColumn[1];
            } else {
                $column_options[] = $parserMysql['SELECT'][$i]['base_expr'];
            }
        }

        //update data in wptm_table_options table
        //get list all columns
        if (!empty($parserMysql['FROM'])) {
            global $wpdb;
            $i = 0;
            $table_alias = array();

            foreach ($parserMysql['FROM'] as $table) {
                //list tables
                $query_option['tables_list'][] = $table['table'];
                if (!empty($table['alias'])) {
                    $table_alias[$table['table']] = $table['alias']['no_quotes']['parts'][0];
                }

                $columns = $wpdb->get_results('SHOW COLUMNS FROM ' . $table['table'], ARRAY_A);
                foreach ($columns as $column) {
                    $column = $this->getColumnInTable($column);
                    $query_option['columns_list'][$i] = $column;
                    $query_option['columns_list'][$i]['table'] = $table['table'];
                    $i++;

                    if (in_array($column['Field'], $column_options) || in_array($table['table'] . '.' . $column['Field'], $column_options)) {
                        $column['table'] = $table['table'];
                        $query_option['column_options'][] = $column;
                    }
                }
            }
            if (count($table_alias) > 0) {
                $query_option['table_alias'] = $table_alias;
            }
        }
        $this->updateQueryOption($query_option, $id_table);
        return $ret;
    }

    /**
     * Get result data of build query
     *
     * @param string $query Query
     *
     * @return array|boolean|null|object
     */
    public function getTableData($query)
    {
        global $wpdb;
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- sql already escaped
        $result = $wpdb->query($query);
        if ($result === false) {
            return false;
        }
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- sql already escaped
        return $wpdb->get_results($query, ARRAY_A);
    }
}
