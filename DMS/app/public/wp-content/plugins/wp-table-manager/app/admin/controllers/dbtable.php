<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Form;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Factory;

defined('ABSPATH') || die();

/**
 * Class WptmControllerDbtable
 */
class WptmControllerDbtable extends Controller
{
    /**
     * Check role user for table
     *
     * @param string|integer $id    Id of table
     * @param string         $check Var check function get checkRoleTable
     *
     * @return integer
     */
    private function checkRoleTable($id, $check)
    {
        global $wpdb;
        $wptm_delete_tables = current_user_can('wptm_delete_tables');
        $wptm_create_tables = current_user_can('wptm_create_tables');
        $wptm_edit_tables = current_user_can('wptm_edit_tables');
        $wptm_edit_own_tables = current_user_can('wptm_edit_own_tables');
        if ($check === 'delete' && !empty($wptm_delete_tables)) {
            return 1;
        } elseif ($check === 'new' && !empty($wptm_create_tables)) {
            return 1;
        } elseif ($check === 'save' && !empty($wptm_edit_tables)) {
            return 1;
        } elseif ($check === 'save' && !empty($wptm_edit_own_tables)) {
            $idUser = (string) get_current_user_id();
            $model  = $this->getModel();
            $data   = $model->getTableData(
                'SELECT t.author FROM ' . $wpdb->prefix . 'wptm_tables AS t  WHERE t.id = ' . $id
            );
            if ($data === false) {
                return 0;
            }
            $data = (int) $data[0]['author'] === (int) $idUser ? 1 : 0;
            return $data;
        } else {
            return 0;
        }
    }

    /**
     * Function change Tables
     *
     * @return void
     */
    public function changeTables()
    {
        $tables  = Utilities::getInput('tables', 'POST', 'none');
        $model   = $this->getModel();
        $columns = $model->listMySQLColumns($tables);

        $this->exitStatus(true, array('columns' => $columns));
    }

    /**
     * Function generateQueryAndPreviewdata
     *
     * @return void
     */
    public function generateQueryAndPreviewdata()
    {
        $table_data = Utilities::getInput('table_data', 'POST', 'none');
        $model      = $this->getModel();
        $result     = $model->generateQueryAndPreviewdata($table_data);

        $this->exitStatus(true, $result);
    }

    /**
     * Function applyCustomQuery
     *
     * @return void
     */
    public function applyCustomQuery()
    {
        $custom_mysql = Utilities::getInput('custom_mysql', 'POST', 'none');
        $model        = $this->getModel();
        $custom_mysql = str_replace(array("\n\r", "\r\n", "\n", "\r", '&#10;'), ' ', $custom_mysql);
        $custom_mysql = str_replace('\\', '', $custom_mysql);
        $check_query  = $model->checkCustomQuery($custom_mysql);

        if ($check_query !== false) {
            try {
                require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
                    . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
                $parser = new \PHPSQLParser\PHPSQLParser(false, true);
                $parser = $parser->parse($custom_mysql);

                $count = count($check_query[0]);
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($check_query[$i][0]) && !empty($parser[$check_query[$i][0]])) {
                        $this->exitStatus(false, __('There was an error in the query!', 'wptm'));
                    }
                }
            } catch (Exception $e) {
                $this->exitStatus(false, __('There was an error in the query!', 'wptm'));
            }
        }

        $result = $model->applyCustomQuery($custom_mysql);
        if ($result['hasRow'] === false) {
            $this->exitStatus(false, $result);
        }
        $this->exitStatus(true, $result);
    }

    /**
     * Function getTableColumnsOptions
     *
     * @param string|null $queryTable Query table
     *
     * @return void|array
     */
    public function getTableColumnsOptions($queryTable = null)
    {
        if ($queryTable !== null) {
            $table_data = false;
            $query = $queryTable;
        } else {
            $table_data = Utilities::getInput('tableList', 'POST', 'none');
            $query      = Utilities::getInput('query', 'POST', 'none');
        }
        $query = str_replace(array("\n\r", "\r\n", "\n", "\r", '&#10;'), ' ', $query);
        $query = str_replace('\\', '', $query);
        $model = $this->getModel();

        require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
            . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
        $parser = new \PHPSQLParser\PHPSQLParser(false, true);

        $parserMysql = $parser->parse($query, true);
        $countTable = count($parserMysql['FROM']);
        $columns = $parserMysql['SELECT'];//array
        $count = count($columns);

        //list tables
        $table_in_query = array();
        $tables_list_alias = array();
        for ($i = 0; $i < $countTable; $i++) {
            $table_in_query[] = $parserMysql['FROM'][$i]['table'];
            if (!empty($parserMysql['FROM'][$i]['alias'])) {
                $tables_list_alias[$parserMysql['FROM'][$i]['alias']['no_quotes']['parts'][0]] = $parserMysql['FROM'][$i]['table'];
            }
        }

        //list columns
        $listColumns = array();
        $listColumnss = array();
        for ($i = 0; $i < $count; $i++) {
            $nameColumn = explode('.', $columns[$i]['base_expr']);
            if (!empty($nameColumn[1])) {
                $listColumns[] = $nameColumn[1];
                if (!empty($tables_list_alias[$nameColumn[0]])) {//table is alias
                    $listColumnss[] = $tables_list_alias[$nameColumn[0]] . '.' . $nameColumn[1];
                } else {
                    $listColumnss[] = $columns[$i]['base_expr'];
                }
            } else {
                $listColumns[] = $columns[$i]['base_expr'];
                $listColumnss[] = $columns[$i]['base_expr'];
            }
        }

        if (!empty($table_data) && !empty($table_data[0])) {//has option of db table
            $result = $model->getTableColumnsOptions($table_data);
        } elseif (!empty($query)) {//custom query
            $result = $model->getTableColumnsOptions($table_in_query, $listColumns);
        } else {
            $result = array();
        }

        $dataReturn = array();
        $dataReturn['column'] = $listColumnss;
        $dataReturn['table'] = $table_in_query;
        $dataReturn['result'] = $result;

        if ($queryTable !== null) {
            return $dataReturn;
        } else {
            $this->exitStatus(true, $dataReturn);
        }
    }

    /**
     * Function get query table
     *
     * @return void
     */
    public function getQuery()
    {
        $is = Utilities::getInput('id', 'POST', 'none');

        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT c.mysql_query FROM ' . $wpdb->prefix . 'wptm_tables as c WHERE c.id = %d', (int)$is));

        $this->exitStatus(true, $result);
    }

    /**
     * Function create new table for selected mysql tables
     *
     * @return void
     */
    public function createTable()
    {
        $check_role  = $this->checkRoleTable('', 'new');
        if ($check_role === 0) {
            $this->exitStatus(__('error while adding table', 'wptm'));
        }
        $table_data = Utilities::getInput('table_data', 'POST', 'none');

        $id_cat     = Utilities::getInt('id_cat', 'POST', 'none');

        $model      = $this->getModel();
        $result     = $model->createTable($table_data, $id_cat);

        $this->exitStatus(true, $result);
    }

    /**
     * Function update table with new change
     *
     * @return void
     */
    public function updateTable()
    {
        $table_data          = Utilities::getInput('table_data', 'POST', 'none');
        $id_table            = (int) $table_data['id_table'];
        $check_role  = $this->checkRoleTable($id_table, 'save');
        if ($check_role === 0) {
            $this->exitStatus(__('error while adding table', 'wptm'));
        }
        $model               = $this->getModel();
        $result = $model->updateTable($id_table, $table_data);

        $this->exitStatus(true, $result);
    }
}
