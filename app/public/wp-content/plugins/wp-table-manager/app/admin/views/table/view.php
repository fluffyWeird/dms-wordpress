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
 * Class wptmViewTable
 */
class WptmViewTable extends View
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
        $id    = Utilities::getInt('id');
        $model = $this->getModel('table');

        $wptm_syn_google_delay = get_option('wptm_syn_google_delay_' . $id, false);
        if ($wptm_syn_google_delay === true) {
            die();
        }
        $item  = $model->getItem($id, true, true, null, false, false);
        $item->style = json_encode($item->style);
        echo json_encode($item);
        die();
    }
}
