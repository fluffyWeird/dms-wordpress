<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

defined('ABSPATH') || die();

/**
 * Class wptmViewStyle
 */
class WptmViewStyle extends View
{
    /**
     * Render style
     *
     * @param null $tpl Tpl
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $id       = Utilities::getInt('id');
        $id_table = Utilities::getInt('id-table');

        $model = $this->getModel('style');
        $item  = $model->getItem($id);

        $model = $this->getModel('table');
        global $wpdb;
        $model->deleteTblInDb($wpdb->prefix . 'wptm_tbl_' . (int)$id_table);
        $model->deleteOldStyle($id_table);

        $result = $model->addThemeToTable($id_table, $item);

        header('Content-Type: application/json; charset=utf-8', true);
        $response = array('response' => $result, 'datas' => array());
        echo json_encode($response);
        die();
    }
}
