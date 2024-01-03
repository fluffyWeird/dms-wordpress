<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Form;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class wptmViewDbtable
 */
class WptmViewDbtable extends View
{
    /**
     * Render
     *
     * @param null $tpl Tpl
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $app = Application::getInstance('Wptm');
        $id_cat                     = Utilities::getInt('id_cat');
        $model                      = $this->getModel('category');
        $this->id_cat               = $id_cat === null ? $model->addCategory('Category') : $id_cat;

        $model                      = $this->getModel('dbtable');
        $id_table                   = Utilities::getInt('id_table');
        $new_table                  = Utilities::getInput('action');
        if (Utilities::getInput('caninsert', 'GET', 'bool')) {
            $this->caninsert = true;
        } else {
            $this->caninsert = false;
        }
        $this->selected_tables      = array();
        $this->availableColumns     = array();
        $this->selected_columns     = array();
        $this->join_rules           = array();
        $this->params               = new stdClass();
        $this->id_table             = $id_table;
        $this->default_ordering_dir = 'asc';
        if ($id_table) {//id db_table exist
            $modelTable            = $this->getModel('table');
            $item                  = $modelTable->getItem($id_table);
//            var_dump($item->params);
//            die();
            $params                = $item->params;
            $this->params          = $params;
            if (isset($params->tables)) {
                $this->selected_tables = $params->tables;
                $columns               = $model->listMySQLColumns($this->selected_tables);

                $this->availableColumns = $columns['all_columns'];
                $this->selected_columns = $params->mysql_columns;
                $this->sorted_columns   = $columns['sorted_columns'];
            }

            if (isset($params->join_rules)) {
                $this->join_rules = $params->join_rules;
            }

            if (isset($params->default_ordering_column)) {
                $this->default_ordering_column = $params->default_ordering;
            } else {
                $this->default_ordering_column = '';
            }

            if (isset($params->default_ordering_dir)) {
                $this->default_ordering_dir = $params->default_ordering_dir;
            }

            if (isset($params->custom_titles)) {
                $this->custom_titles = $params->custom_titles;
            } else {
                $this->custom_titles = array();
            }
        }

        $this->mysql_tables = $model->listMySQLTables();
        if ($new_table !== 'create' && $id_table < 1) {//get list db table or create new table when not db table exist 24
            $modelTable  = $this->getModel('tables');
            $item        = $modelTable->getDbItems();
            $this->count = count($item);
            if ($this->count > 0) {//list
                $this->item = $item;
            }
        }

        parent::render($tpl);
    }
}
