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
 * Class wptmViewCharttype
 */
class WptmViewCharttype extends View
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
        $id    = Utilities::getInt('id');
        $model = $this->getModel('chart');
        $item  = $model->getChartType($id);
        header('Content-Type: application/json; charset=utf-8', true);
        echo json_encode($item);
        die();
    }
}
