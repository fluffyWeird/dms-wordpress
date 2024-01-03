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
 * Class WptmModelCategories
 * functions: delete, get list category, insert, move category
 */
class WptmModelCategories extends Model
{

    /**
     * Get list categories
     *
     * @param null|integer $id_cat Id category
     *
     * @return boolean|mixed
     */
    public function getCategories($id_cat = null)
    {
        global $wpdb;
        if ((int) $id_cat > 0) {
            $data_query = $wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE c.id = %d ORDER BY c.lft ASC', $id_cat);
            $result = $wpdb->query($data_query);
        } else {
            $data_query = 'SELECT c.* FROM ' . $wpdb->prefix . 'wptm_categories as c WHERE c.level >0 ORDER BY c.lft ASC';
            $result = $wpdb->query($data_query);
        }
        if ($result === false) {
            return false;
        }
        return stripslashes_deep($wpdb->get_results($data_query, OBJECT));
    }

    /**
     * Get function fDelete category
     *
     * @param integer $nodeId Id of category
     *
     * @return boolean
     */
    public function delete($nodeId)
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        if (!$this->fDelete($nodeId)) {
            $wpdb->query('ROLLBACK');
            return false;
        }
        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Delete category
     *
     * @param integer $nodeId Id of category
     *
     * @return boolean
     */
    private function fDelete($nodeId)
    {
        global $wpdb;
        $nodeInfos = $wpdb->get_row('SELECT rgt,lft,level FROM ' . $wpdb->prefix . 'wptm_categories WHERE id=' . (int) $nodeId);
        if ($nodeInfos === false) {
            return false;
        }

        $width = (int)$nodeInfos->rgt - (int)$nodeInfos->lft + 1;

        //Delete node
        if ($wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'wptm_categories WHERE lft >= %d AND rgt <= %d', (int)$nodeInfos->lft, (int)$nodeInfos->rgt)) === false) {
            return false;
        }

        //Update right brothers
        if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET lft = lft - %d, rgt = rgt - %d WHERE lft > %d AND rgt > %d', $width, $width, (int)$nodeInfos->rgt, (int)$nodeInfos->rgt)) === false) {
            return false;
        }

        //Update parents
        if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET rgt = rgt - %d WHERE lft < %d AND rgt > %d', $width, $nodeInfos->lft, $nodeInfos->rgt)) === false) {
            return false;
        }

        return true;
    }

    /**
     * Function insert data
     *
     * @param integer $nodeTo   Id to the reference node
     * @param array   $nodes    Array of nodes to insert ordered by left asc
     * @param string  $where    First-child
     * @param object  $refInfos Id category object
     *
     * @global type   $wpdb
     *
     * @return boolean
     */
    private function fInsert($nodeTo, $nodes, $where = 'first-child', $refInfos = null)
    {
        global $wpdb;
        $nodeToInfos = $wpdb->get_row($wpdb->prepare('SELECT rgt,lft,level,parent_id FROM ' . $wpdb->prefix . 'wptm_categories WHERE id = %d', (int) $nodeTo));
        if ($nodeToInfos === false) {
            return false;
        }

        //Get the node width
        $maxRgt = 0;
        $minLft = $nodes[0]->lft;
        foreach ($nodes as $node) {
            $minLft = min($node->lft, $minLft);
            $maxRgt = max($node->rgt, $maxRgt);
        }
        $width = $maxRgt - $minLft + 1;

        //Update parents
        if ($where === 'first-child') {
            //Update right brothers
            if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET lft = lft + %d, rgt = rgt + %d WHERE lft > %d AND rgt > %d', $width, $width, (int)$nodeToInfos->lft, (int)$nodeToInfos->lft)) === false) {
                return false;
            }

            //insert at first position
            if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET rgt = rgt + %d WHERE lft <= %d AND rgt >= %d', $width, (int)$nodeToInfos->lft, (int)$nodeToInfos->rgt)) === false) {
                return false;
            }

            $leftTo    = $nodeToInfos->lft + 1;//new position lft
            $diffLevel = $nodeToInfos->level + 1 - $nodes[0]->level;//lever range
        } else {//after
            //Update right brothers
            if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET lft = lft + %d, rgt = rgt + %d WHERE lft > %d AND rgt > %d', $width, $width, (int)$nodeToInfos->rgt, (int)$nodeToInfos->rgt)) === false) {
                return false;
            }

            //insert after element
            if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET rgt = rgt + %d WHERE lft <= %d AND rgt > %d', $width, (int)$nodeToInfos->lft, (int)$nodeToInfos->rgt)) === false) {
                return false;
            }
            //new position left
            $leftTo    = $nodeToInfos->rgt + 1;
            $diffLevel = $nodeToInfos->level - $nodes[0]->level;
        }

        $diff = $minLft;
        //prepare and insert old nodes
        foreach ($nodes as $NodeElement) {
            $NodeElement->lft   += $leftTo - $diff;
            $NodeElement->rgt   += $leftTo - $diff;
            $NodeElement->level += $diffLevel;
            $keys               = array();
            $values             = array();
            foreach ($NodeElement as $key => $value) {
                $keys[]   = '`' . esc_attr($key) . '`';
                $values[] = '"' . addslashes($value) . '"';
            }

            $query = 'INSERT INTO ' . $wpdb->prefix . 'wptm_categories (' . implode(',', $keys) . ') VALUES (' . implode(',', $values) . ')';

            //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- Query already escaped
            if ($wpdb->query($query) === false) {
                return false;
            }
        }

        //update parent value
        if ($where === 'first-child') {
            if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET parent_id = %d WHERE id = %d', (int) $refInfos->id, (int) $nodes[0]->id)) === false) {
                return false;
            }
        } else {
            if ($wpdb->query($wpdb->prepare('UPDATE ' . $wpdb->prefix . 'wptm_categories SET parent_id = %d WHERE id = %d', (int) $refInfos->parent_id, (int) $nodes[0]->id)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Function move
     *
     * @param integer $node     Id of category
     * @param integer $ref      Id
     * @param string  $position Position
     *
     * @return boolean
     */
    public function move($node, $ref, $position = 'first-child')
    {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        if (!$this->fMove($node, $ref, $position)) {
            $wpdb->query('ROLLBACK');
            return false;
        }
        $wpdb->query('COMMIT');
        return true;
    }

    /**
     * Move a node $node to the $position of $ref element
     *
     * @param integer $node     Id of category
     * @param integer $ref      Id Parent| id before category
     * @param string  $position Position
     *
     * @return boolean
     */
    private function fMove($node, $ref, $position = 'first-child')
    {
        global $wpdb;

        $nodeInfos = $wpdb->get_row($wpdb->prepare('SELECT rgt,lft,level FROM ' . $wpdb->prefix . 'wptm_categories WHERE id = %d', (int) $node));
        if ($nodeInfos === false) {
            return false;
        }

        if ((int) $ref === 0) {
            //case ROOT
            $refInfos = $wpdb->get_row('SELECT id,rgt,lft,level,parent_id FROM ' . $wpdb->prefix . 'wptm_categories WHERE level=0 LIMIT 0,1');
        } else {
            $refInfos = $wpdb->get_row($wpdb->prepare('SELECT id,rgt,lft,level,parent_id FROM ' . $wpdb->prefix . 'wptm_categories WHERE id = %d', (int) $ref));
        }


        if ($refInfos === false) {
            return false;
        }

        $nodes = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'wptm_categories WHERE lft >= %d AND rgt <= %d ORDER BY lft ASC', (int) $nodeInfos->lft, (int) $nodeInfos->rgt));
        if ($nodes === false) {
            return false;
        }

        if (!$this->fDelete($node)) {
            return false;
        }

        if (!$this->fInsert($ref, $nodes, $position, $refInfos)) {
            return false;
        }

        return true;
    }
}
