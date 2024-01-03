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
 * Class WptmControllerChart
 */
class WptmControllerChart extends Controller
{
    /**
     * Function save chart
     *
     * @return void
     */
    public function save()
    {
        $id_chart = Utilities::getInt('id', 'POST');
        $datas    = Utilities::getInput('jform', 'POST', 'none');
        $model    = $this->getModel();
        if ($model->save($id_chart, $datas)) {
            $this->exitStatus(true);
        } else {
            $this->exitStatus(__('error while saving table', 'wptm'));
        }
    }

    /**
     * Function create new chart
     *
     * @return void
     */
    public function add()
    {
        $id_table = Utilities::getInt('id_table');
        $datas    = Utilities::getInput('datas', 'POST', 'string');
        $model    = $this->getModel();
        $id       = $model->add($id_table, $datas);
        if ($id) {
            $chart = $model->getItem($id);
            $this->exitStatus(true, array(
                'id'    => $id,
                'datas' => $chart->datas,
                'type'  => 'Line',
                'title' => __('New chart', 'wptm')
            ));
        }
        $this->exitStatus(__('error while adding chart', 'wptm'));
    }

    /**
     * Copy chart
     *
     * @return void
     */
    public function copy()
    {
        $id      = Utilities::getInt('id');
        $model   = $this->getModel();
        $newItem = $model->copy($id);
        if ($newItem) {
            $table = $model->getItem($newItem);
            $table->author_name = get_userdata((int)$table->author)->user_nicename;
            $this->exitStatus(true, array('id' => $table->id, 'author_name' => $table->author_name,
                'title' => $table->title, 'author' => $table->author, 'modified_time' => $this->convertDate($table->modified_time)));
        }
        $this->exitStatus(__('error while copy chart', 'wptm'));
    }

    /**
     * Function delete chart
     *
     * @return void
     */
    public function delete()
    {
        $id     = Utilities::getInt('id');
        $model  = $this->getModel();
        $result = $model->delete($id);
        if ($result) {
            $this->exitStatus(true);
        }
        $this->exitStatus(__('An error occurred!', 'wptm'));
    }

    /**
     * Function set title chart
     *
     * @return void
     */
    public function setTitle()
    {
        $id        = Utilities::getInt('id');
        $new_title = Utilities::getInput('title', 'GET', 'string');
        $model     = $this->getModel();
        $id        = $model->setTitle($id, $new_title);
        if ($id) {
            $this->exitStatus(true);
        }
        $this->exitStatus(__('An error occurred!', 'wptm'));
    }

    /**
     * Get list all charts for gutenberg block
     *
     * @return void
     */
    public function listCharts()
    {
        $modelCharts = $this->getModel('charts');
        $chartsList = $modelCharts->getAllCharts();

        $charts = WptmTablesHelper::categoryCharts($chartsList);
        $tableIds = array_keys($charts);

        $categoriesModel = $this->getModel('categories');
        $categories = $categoriesModel->getCategories();
        $categoryPath = array();
        foreach ($categories as $category) {
            $categoryPath[$category->id] = isset($categoryPath[$category->parent_id])
                ? $categoryPath[$category->parent_id] . ' > ' . $category->title
                : ' ' . $category->title;
        }

        $modelTables  = $this->getModel('tables');
        $tables = $modelTables->getTablesbyIds($tableIds);
        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'user.php';
        $WptmControllerUser = new WptmControllerUser();

        $tablePath = array();
        $listCat =  array();
        foreach ($tables as $table) {
            $tablePath[$table->id] = $categoryPath[$table->id_category] . ' > ' . $table->title;
            $listCat[$table->id] = $table->id_category;
            $table->role = $WptmControllerUser->checkRoleTable($table->id, 'edit');
        }
        $chartPath = array();
        foreach ($chartsList as $chart) {
            if (isset($tablePath[$chart->id_table])) {
                $chartPath[$chart->id] = $tablePath[$chart->id_table];
                $chartPath[$chart->id] = array();
                $chartPath[$chart->id]['path'] = $tablePath[$chart->id_table];
                $chartPath[$chart->id]['id_table'] = $chart->id_table;
                $chartPath[$chart->id]['title'] = $chart->title;
                $chartPath[$chart->id]['id_cat'] = $listCat[$chart->id_table];
            }
        }

        $tables = WptmTablesHelper::categoryObject($tables);

        wp_send_json(array(
            'success' => true,
            'data'    => array(
                'categories' => $categories,
                'tables' => $tables,
                'charts' => $charts,
                'adminUrl' => esc_url(admin_url('admin.php?page=wptm')),
                'chartPath' => $chartPath
            )
        ));
        die();
    }

    /**
     * Function convert date string to date format
     *
     * @param string $date Date string
     *
     * @return string
     */
    public function convertDate($date)
    {
        if (get_option('date_format', null) !== null) {
            $date = date_create($date);
            $date = date_format($date, get_option('date_format') . ' ' . get_option('time_format'));
        }
        return $date;
    }
}
