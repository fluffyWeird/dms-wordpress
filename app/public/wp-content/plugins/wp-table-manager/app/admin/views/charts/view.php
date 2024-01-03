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

defined('ABSPATH') || die();

/**
 * Class wptmViewCharts
 */
class WptmViewCharts extends View
{
    /**
     * Function render
     *
     * @param null $tpl Tpl
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $id_table = Utilities::getInt('id_table');
        $model    = $this->getModel('charts');
        $items    = $model->getCharts($id_table);
        echo json_encode($items);
        die();
    }
}
