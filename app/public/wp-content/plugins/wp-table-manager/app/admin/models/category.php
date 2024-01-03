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
 * Class WptmModelCategory
 */
class WptmModelCategory extends Model
{
    /**
     * Function check condition when get fAddCategory function
     *
     * @param string       $title Query
     * @param integer|null $owner Id owner
     *
     * @return boolean|integer
     */
    public function addCategory($title, $owner = null)
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        $result = $this->fAddCategory($title, $owner);
        if (!$result) {
            $wpdb->query('ROLLBACK');
            return false;
        }
        $wpdb->query('COMMIT');
        return $result;
    }

    /**
     * Create new category
     *
     * @param string       $title Query
     * @param integer|null $owner Id owner
     *
     * @return boolean|integer
     */
    private function fAddCategory($title, $owner = null)
    {
        global $wpdb;
        $title = trim(sanitize_text_field($title));
        if ((string) $title === '') {
            return false;
        }
        if ($wpdb->query('UPDATE ' . $wpdb->prefix . 'wptm_categories SET rgt=rgt+2 WHERE level=0') === false) {
            return false;
        }

        $params            = new stdClass();
        $params->role      = new stdClass();
        $params->role->{0} = $owner === null ? get_current_user_id() : (string) $owner;
        $params            = json_encode($params);

        $result = $wpdb->query('SELECT c.rgt FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE level=0 ORDER BY c.lft ASC LIMIT 0,1');
        if ($result === false) {
            return false;
        }
        $rgt = stripslashes_deep($wpdb->get_results('SELECT c.rgt FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE level=0 ORDER BY c.lft ASC LIMIT 0,1', OBJECT));

        $result = $wpdb->query('SELECT id FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE level=0 ORDER BY c.lft ASC LIMIT 0,1');
        if ($result === false) {
            return false;
        }
        $id     = stripslashes_deep($wpdb->get_results('SELECT id FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE level=0 ORDER BY c.lft ASC LIMIT 0,1', OBJECT));
        $result = $wpdb->insert(
            $wpdb->prefix . 'wptm_categories',
            array(
                'params'    => (string) $params,
                'title'     => (string) $title,
                'level'     => 1,
                'lft'       => (int) $rgt[0]->rgt - 2,
                'rgt'       => (int) $rgt[0]->rgt - 1,
                'parent_id' => $id[0]->id
            )
        );
        if ($result === false) {
            return false;
        }
        return $wpdb->insert_id;
    }

    /**
     * Check exist category
     *
     * @param integer $id_category Id category
     *
     * @return boolean
     */
    public function isCategoryExist($id_category)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.id FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE c.id = %d', (int) $id_category));
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * Set title category
     *
     * @param integer $id_category Id category
     * @param string  $title       Title category
     *
     * @return boolean
     */
    public function setTitle($id_category, $title)
    {
        global $wpdb;
        $query = 'UPDATE ' . $wpdb->prefix . 'wptm_categories SET title = ' . $title . ' WHERE id = ' . (int)$id_category;

        if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET title = %s WHERE id = %d', $title, (int)$id_category)) === false) {
            return false;
        }
        return true;
    }
}
