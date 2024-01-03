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
 * Class WptmModelTablesite
 */
class WptmModelTablesite extends Model
{
    /**
     * Get table
     *
     * @param integer $id Id table
     *
     * @return boolean|mixed
     */
    public function getItem($id)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_tables as c WHERE c.id = %s', (int) $id));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep(
            $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_tables as c WHERE c.id = %s', (int) $id), OBJECT)
        );
    }    /**
     * Get different value of column
     *
     * @param integer $id       Id table
     * @param integer $columnId Column id
     *
     * @return boolean|mixed
     */
    public function getDifferentValueColumn($id, $columnId)
    {
        global $wpdb;
        $query = $wpdb->prepare('SELECT DISTINCT(col%d) FROM ' . $wpdb->prefix . 'wptm_tbl_%d', $columnId, (int) $id);
        $result = $wpdb->get_col($query);
        if ($result === false) {
            return false;
        }
        return $result;
    }

    /**
     * Get table
     *
     * @param integer $id_chart Id chartgetTableFromChartId
     *
     * @return boolean|mixed
     */
    public function getTableFromChartId($id_chart)
    {
        global $wpdb;
        $query  = 'SELECT t.* FROM ' . $wpdb->prefix . 'wptm_charts c LEFT JOIN ' . $wpdb->prefix . 'wptm_tables as t ON t.id=c.id_table WHERE c.id=' . (int) $id_chart;

        $result = $wpdb->query($wpdb->prepare('SELECT t.* FROM ' . $wpdb->prefix . 'wptm_charts c LEFT JOIN ' . $wpdb->prefix . 'wptm_tables as t ON t.id=c.id_table WHERE c.id = %s', (int) $id_chart));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep($wpdb->get_row($wpdb->prepare('SELECT t.* FROM ' . $wpdb->prefix . 'wptm_charts c LEFT JOIN ' . $wpdb->prefix . 'wptm_tables as t ON t.id=c.id_table WHERE c.id = %s', (int) $id_chart), OBJECT));
    }
}
