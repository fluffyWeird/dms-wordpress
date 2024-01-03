<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

defined('ABSPATH') || die();

/**
 * Class WptmControllerCategories
 * Functions delete, set order category
 * ? where delete function
 */
class WptmControllerCategories extends Controller
{

    /**
     * Function delete category
     *
     * @return void
     */
    public function delete()
    {
        $wptm_delete_category = current_user_can('wptm_delete_category');
        if (!empty($wptm_delete_category)) {
            $category = Utilities::getInt('id_category');
            $model    = $this->getModel();
            if ($model->delete($category)) {
                $this->exitStatus(true);
            }
        }
        $this->exitStatus(__('An error occurred!', 'wptm'));
    }

    /**
     * Function set order category
     *
     * @return void
     */
    public function order()
    {
        if (Utilities::getInput('position') === 'after') {
            $position = 'after';
        } else {
            $position = 'first-child';
        }
        $pk  = Utilities::getInt('pk');
        $ref = Utilities::getInt('ref');
        if ($ref === 0) {
            $ref = 1;
        }
        $model = $this->getModel();
        if ($model->move($pk, $ref, $position)) {
            $this->exitStatus(true, $pk . ' ' . $position . ' ' . $ref);
        }
        $this->exitStatus(__('An error occurred!', 'wptm'));
    }

    /**
     * Function get categories list
     *
     * @return void
     */
    public function listCats()
    {
        $categoriesModel = $this->getModel();
        $categories = $categoriesModel->getCategories();

        $categoryPath = array();
        foreach ($categories as $category) {
            $categoryPath[$category->id] = isset($categoryPath[$category->parent_id])
                ? $categoryPath[$category->parent_id] . ' > ' . $category->title
                : ' ' . $category->title;
        }

        $modelTables  = $this->getModel('tables');
        $tables = $modelTables->getTables();

        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'user.php';
        $WptmControllerUser = new WptmControllerUser();
        $tablePath = array();

        foreach ($tables as $table) {
            if (isset($categoryPath[$table->id_category])) {
                $table->role = $WptmControllerUser->checkRoleTable($table->id, 'edit');
                $tablePath[$table->id] = array();
                $tablePath[$table->id]['path'] = $categoryPath[$table->id_category];
                $tablePath[$table->id]['id_category'] = $table->id_category;
                $tablePath[$table->id]['title'] = $table->title;
            }
        }

        $tables = WptmTablesHelper::categoryObject($tables);

        wp_send_json(array(
            'success' => true,
            'data'    => array(
                'categories' => $categories,
                'tables' => $tables,
                'adminUrl' => esc_url(admin_url('admin.php?page=wptm')),
                'tablePath' => $tablePath
            )
        ));
        die();
    }
}
