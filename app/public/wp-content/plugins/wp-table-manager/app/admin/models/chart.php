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
 * Class WptmModelChart
 */
class WptmModelChart extends Model
{
    /**
     * Function save chart
     *
     * @param integer $id    Id chart
     * @param array   $datas Data of chart
     *
     * @return false|integer
     */
    public function save($id, $datas)
    {
        global $wpdb;
        if (!empty($datas['datas']) && $datas['datas'] !== '') {
            $result = $wpdb->update(
                $wpdb->prefix . 'wptm_charts',
                array('type' => $datas['type'], 'config' => $datas['config'], 'datas' => $datas['datas']),
                array('id' => (int) $id)
            );
        } else {
            $result = $wpdb->update(
                $wpdb->prefix . 'wptm_charts',
                array('type' => $datas['type'], 'config' => $datas['config']),
                array('id' => (int) $id)
            );
        }


        if ($result === false) {
            return false;
        }
        if ((int)$result === 0) {
            $result = $id;
        }

        return $result;
    }

    /**
     * Create new chart
     *
     * @param integer $id_table Id chart
     * @param string  $datas    Json string data
     *
     * @return integer
     */
    public function add($id_table, $datas)
    {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            'INSERT INTO ' . $wpdb->prefix . 'wptm_charts (id_table, title, datas, type, created_time, modified_time, author) VALUES ( %d,%s,%s,%s,%s,%s,%d)',
            $id_table,
            __('New chart', 'wptm'),
            $datas,
            'Line',
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            get_current_user_id()
        ));
        return $wpdb->insert_id;
    }

    /**
     * Delete chart
     *
     * @param integer $id Id chart
     *
     * @return false|integer
     */
    public function delete($id)
    {
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'wptm_charts',
            array('id' => (int) $id)
        );

        return $result;
    }

    /**
     * Set title of chart
     *
     * @param integer $id    Id chart
     * @param string  $title Title chart
     *
     * @return false|integer
     */
    public function setTitle($id, $title)
    {
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'wptm_charts',
            array('title' => $title),
            array('id' => (int) $id)
        );

        return $result;
    }

    /**
     * Function get item chart
     *
     * @param integer $id Id chart
     *
     * @return boolean|mixed
     */
    public function getItem($id)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charts as c WHERE c.id = %d', (int) $id));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep($wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charts as c WHERE c.id = %d', (int) $id), OBJECT));
    }

    /**
     * Change type chart
     *
     * @param integer $id Id chart
     *
     * @return boolean|mixed
     */
    public function getChartType($id)
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charttypes as c WHERE c.id = %d', (int) $id));
        if ($result === false) {
            return false;
        }
        return stripslashes_deep($wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charttypes as c WHERE c.id = %d', (int) $id), OBJECT));
    }

    /**
     * Copy chart
     *
     * @param integer $id_chart Id chart
     *
     * @return boolean|integer
     */
    public function copy($id_chart)
    {
        global $wpdb;

        $result = $wpdb->query($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charts as c WHERE c.id = %d', (int)$id_chart));
        if ($result === false) {
            return false;
        }
        $table = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_charts as c WHERE c.id = %d', (int)$id_chart), OBJECT);
        $wpdb->query(
            $wpdb->prepare(
                'INSERT INTO ' . $wpdb->prefix . 'wptm_charts (id_table, title, datas, type, config, hash, params, created_time, modified_time, author) VALUES ( %d,%s,%s,%s,%s,%s,%s,%s,%s,%d)',
                $table->id_table,
                $table->title . __(' (copy)', 'wptm'),
                $table->datas,
                $table->type,
                $table->config,
                $table->hash,
                $table->params,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                get_current_user_id()
            )
        );
        return $wpdb->insert_id;
    }
}
