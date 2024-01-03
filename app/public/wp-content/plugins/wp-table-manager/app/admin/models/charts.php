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
 * Class WptmModelCharts
 */
class WptmModelCharts extends Model
{
    /**
     * Get list charts
     *
     * @param integer $id_table Id table
     *
     * @return boolean|mixed
     */
    public function getCharts($id_table)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charts as c WHERE id_table = %d', (int) $id_table));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep($wpdb->get_results($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charts as c WHERE id_table = %d', (int) $id_table), OBJECT));
    }

    /**
     * Get list all charts
     *
     * @return boolean|mixed
     */
    public function getAllCharts()
    {
        global $wpdb;
        $query = 'SELECT id, title, id_table, modified_time FROM ' . $wpdb->prefix . 'wptm_charts ORDER BY id_table ASC' ;
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        $result = $wpdb->query($query);
        if ($result === false) {
            return false;
        }
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        return stripslashes_deep($wpdb->get_results($query, OBJECT));
    }

    /**
     * Get style chart
     *
     * @return boolean|mixed
     */
    public function getChartTypes()
    {
        global $wpdb;
        $query  = 'SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charttypes as c Order By ordering ASC';
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        $result = $wpdb->query($query);
        if ($result === false) {
            return false;
        }
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        return stripslashes_deep($wpdb->get_results($query, OBJECT));
    }
}
