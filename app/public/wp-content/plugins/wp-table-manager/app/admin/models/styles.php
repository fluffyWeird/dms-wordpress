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
 * Class WptmModelStyles
 */
class WptmModelStyles extends Model
{
    /**
     * Get styles
     *
     * @return boolean|mixed
     */
    public function getStyles()
    {
        global $wpdb;
        $query  = 'SELECT c.id, c.image FROM ' . $wpdb->prefix . 'wptm_styles as c ORDER BY c.id ASC';
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        $result = $wpdb->query($query);
        if ($result === false) {
            return false;
        }
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        return stripslashes_deep($wpdb->get_results($query, OBJECT));
    }
}
