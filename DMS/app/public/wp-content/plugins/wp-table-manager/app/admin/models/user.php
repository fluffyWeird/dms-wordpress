<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 26/03/2018
 * Time: 3:02 CH
 */

use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

/**
 * Class WptmModelUser
 * save user role a category
 */
class WptmModelUser extends Model
{
    /**
     * Get params a category
     *
     * @param integer $id Id category
     *
     * @return boolean|mixed
     */
    public function getItem($id)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.params FROM ' . $wpdb->prefix . 'wptm_categories AS c WHERE c.id = %d', (int)$id));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep(
            $wpdb->get_results(
                $wpdb->prepare('SELECT c.params FROM ' . $wpdb->prefix . 'wptm_categories AS c WHERE c.id = %d', (int)$id),
                OBJECT
            )
        );
    }

    /**
     * Update params role a category/table
     *
     * @param integer $id   Id table
     * @param string  $data Data user
     * @param integer $type Type === 0 -> category, Type === 1 -> table
     *
     * @return boolean
     */
    public function save($id, $data, $type)
    {
        global $wpdb;
        if ((int)$type === 1) {
            $result = $wpdb->update(
                $wpdb->prefix . 'wptm_tables',
                array('author' => (string)$data),
                array('id' => (int)$id)
            );
        } elseif ((int)$type === 0) {
            $result = $wpdb->update(
                $wpdb->prefix . 'wptm_categories',
                array('params' => (string)$data),
                array('id' => (int)$id)
            );
        } else {
            $result = false;
        }

        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }
}
