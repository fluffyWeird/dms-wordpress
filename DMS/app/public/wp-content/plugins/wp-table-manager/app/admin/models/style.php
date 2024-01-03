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
 * Class WptmModelStyle
 */
class WptmModelStyle extends Model
{
    /**
     * Get item
     *
     * @param integer $id Id
     *
     * @return boolean|mixed
     */
    public function getItem($id)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_styles as c WHERE c.id = %d', (int) $id));
        if ($result === false) {
            return false;
        }
        return $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_styles as c WHERE c.id = %d', (int) $id), OBJECT);
    }
}
