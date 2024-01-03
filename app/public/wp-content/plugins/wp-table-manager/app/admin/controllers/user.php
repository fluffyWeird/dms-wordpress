<?php
/**
 * WP table manager
 *
 * @package WP table manager
 * @author  Joomunited
 * @version 2.3
 */

use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WptmControllerUser
 */
class WptmControllerUser extends Controller
{
    /**
     * Get list user
     *
     * @return array
     */
    public function getListUser()
    {
        $args = array(
            'fields' => 'all'
        );

        $wp_user_search = new WP_User_Query($args);
        $items = $wp_user_search->get_results();
        $data = array();
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            $data[] = array('id' => $items[$i]->data->ID, 'name' => $items[$i]->data->user_nicename, 'roles' => $items[$i]->roles);
        }

        return $data;
    }
    /**
     * Function save category params role
     *
     * @return void
     */
    public function save()
    {
        $id   = Utilities::getInt('id', 'POST');
        $type = Utilities::getInt('type', 'POST') === 1 ? 'table' : 'category';
        $data = Utilities::getInput('data', 'POST', 'none');
        $data = str_replace('\\', '', $data);
        $data = json_decode($data);

        if (!current_user_can('administrator')) {
            $this->exitStatus(__('error while saving role', 'wptm'));
        }

        $model = $this->getModel();
        if ($type === 'table') {
            $author = (int) $data->{0};

            if ($model->save($id, $author, 1)) {
                $this->exitStatus(true);
            } else {
                $this->exitStatus(__('error while saving role', 'wptm'));
            }
        }

        //category
        $params = $model->getItem($id);
        if (isset($params[0]->params)) {
            $params = $params[0]->params;
            $param  = json_decode($params);
            if (isset($param->role)) {
                $params = $param;
            } else {
                $params       = new stdClass();
                $params->role = new stdClass();
            }
            $id_user = get_current_user_id();
            $id_user = $id_user !== 0 ? $id_user : - 1;
            $wptm_edit_category = current_user_can('wptm_edit_category');
            if (!empty($wptm_edit_category)
                || (current_user_can('wptm_edit_own_category') && isset($params->role->{0}) && (int) $params->role->{0} === $id_user)
            ) {
                $params->role = $data;
            } else {
                $this->exitStatus(__('You have no right to change the data category', 'wptm'));
            }
            $data = json_encode($params);
        } else {
            $this->exitStatus(__('error while saving role', 'wptm'));
        }

        if ($model->save($id, $data, 0)) {
            $this->exitStatus(true, $data);
        } else {
            $this->exitStatus(__('error while saving role', 'wptm'));
        }
    }

    /**
     * Check role user for table
     *
     * @param string $id    Id of table|category
     * @param string $check Var check function get checkRoleTable
     *
     * @return integer
     */
    public function checkRoleTable($id, $check)
    {
        global $wpdb;

        $wptm_edit_category = current_user_can('wptm_edit_category');
        $wptm_edit_own_category = current_user_can('wptm_edit_own_category');
        $wptm_edit_tables = current_user_can('wptm_edit_tables');
        $wptm_edit_own_tables = current_user_can('wptm_edit_own_tables');
        $idUser = (string) get_current_user_id();

        if ($check === 'getListTable') {
            if ($wptm_edit_category) {
                return 1;
            }

            if (!$wptm_edit_own_category) {
                return 0;
            }

            $modelCategories = $this->getModel('categories');
            $category = $modelCategories->getCategories($id);
            $category_role = json_decode($category[0]->params);

            if (in_array($idUser, (array)$category_role->role)) {
                return 1;
            }
        } elseif ($check === 'edit' && !empty($wptm_edit_tables)) {
            return 1;
        } elseif ($check === 'edit' && !empty($wptm_edit_own_tables)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wptm_tables';
            $params = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT t.author FROM ' . $table_name . ' AS t WHERE t.id = %d',
                    $id
                )
            );

            if ($params === false) {
                return 0;
            }
            $data = (int)$params === (int) $idUser ? 1 : 0;
            return $data;
        } else {
            return 0;
        }
    }
}
