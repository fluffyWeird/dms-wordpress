<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

/**
 * Class WptmModelChartsite
 */
class WptmModelChartsite extends Model
{
    /**
     * Get chart
     *
     * @param integer $id Id chart
     *
     * @return boolean|mixed
     */
    public function getChart($id)
    {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare('SELECT c.title, c.id_table, c.datas, c.type, c.config FROM ' . $wpdb->prefix . 'wptm_charts as c '
                . ' WHERE c.id = %s', (int) $id)
        );

        if ($result === false) {
            return false;
        }
        return stripslashes_deep(
            $wpdb->get_row(
                $wpdb->prepare('SELECT c.title, c.id_table, c.datas, c.type, c.config FROM ' . $wpdb->prefix . 'wptm_charts as c '
                    . ' WHERE c.id = %s', (int) $id),
                OBJECT
            )
        );
    }
}
