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
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class WptmControllerExcel
 */
class WptmControllerSiteControl extends Controller
{
    /**
     * Function export file .xml in frontend
     *
     * @return void
     */
    public function export()
    {
        $app    = Application::getInstance('Wptm', __FILE__, 'admin');
        $id     = Utilities::getInput('id', 'GET', 'string');

        if (strpos($id, '_') > 0) {
            $ids = explode('_', $id);
            $id = $ids[1];
        }
        $ranger = Utilities::getInput('ranger', 'GET', 'string');
        if (!empty($ranger)) {
            $ranger = explode(',', $ranger);
        } else {
            $ranger = null;
        }
        $model  = Model::getInstance('table');
        $table  = $model->getItem((int)$id, true, true, null, false);

        if (isset($table) && isset($table->style) && is_string($table->style)) {
            $style = json_decode($table->style);
        } else {
            $style = $table->style;
        }

        if (isset($style) && ((isset($style->table->download_button) && $style->table->download_button) || $ranger !== null)) {
            require_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'excel.php';
            $excel = new WptmControllerExcel();
            $excel->export($table, $ranger);
        } else {
            $this->exitStatus(false, esc_attr__('File not found or You do not have permission to download the file.', 'wptm'));
        }
    }

    /**
     * Exit a request serving a json result
     *
     * @param string $status Exit status
     * @param array  $datas  Echoed datas
     *
     * @since 1.0.3
     *
     * @return void
     */
    protected function exitStatus($status = '', $datas = array())
    {
        $response = array('response' => $status, 'datas' => $datas);
        echo json_encode($response);
        die();
    }

    /**
     * Processing function when has sheet google push
     *
     * @return void
     */
    public function scriptGoogle()
    {
        $google_hash = Utilities::getInput('wptmhash', 'GET', 'string');
        if ($google_hash !== null && $google_hash !== '') {
            $id_table = Utilities::getInput('id_table', 'GET', 'string');
            $list_syn_google = get_option('wptm_list_syn_google', '');

            require_once plugin_dir_path(WPTM_PLUGIN_FILE) . 'app/admin/models/config.php';
            $WptmModelConfig = new WptmModelConfig();
            $config = $WptmModelConfig->getConfig();

            if ($list_syn_google !== '') {
                $list_syn_google = json_decode($list_syn_google, true);
                if (!empty($list_syn_google['table' . $id_table]) && $list_syn_google['table' . $id_table] === $google_hash) {
                    $wptm_syn_google_time = time();
                    update_option('wptm_syn_google_time_' . $id_table, $wptm_syn_google_time, false);

                    $GLOBALS['wp_object_cache']->delete('wptm_syn_google_delay_' . $id_table, 'options');
                    $wptm_syn_google_delay = get_option('wptm_syn_google_delay_' . $id_table, false);
                    if ($wptm_syn_google_delay === false) {
                        update_option('wptm_syn_google_delay_' . $id_table, true, false);

                        $this->callSynControlGoogleScript($id_table, $wptm_syn_google_time);
                    }
                    $this->exitStatus(__('succes!', 'wptm'));
                } else {
                    $this->exitStatus(__('An error occurred!', 'wptm'));
                }
            } else {
                $this->exitStatus(__('An error occurred!', 'wptm'));
            }
        }
        $this->exitStatus(__('An error occurred!', 'wptm'));
    }
    /**
     * Call to synControlGoogleScript
     *
     * @param integer $id                   Table id
     * @param integer $wptm_syn_google_time Old time update
     *
     * @return void
     */
    public function callSynControlGoogleScript($id, $wptm_syn_google_time)
    {
        $app = Application::getInstance('Wptm');
        require_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'excel.php';
        $excel = new WptmControllerExcel();
        $excel->synControlGoogleScript($id);

        $GLOBALS['wp_object_cache']->delete('wptm_syn_google_time_' . $id, 'options');
        $newTime = get_option('wptm_syn_google_time_' . $id, false);

        if ((int)$newTime !== (int)$wptm_syn_google_time) {
            $this->callSynControlGoogleScript($id, $newTime);
        } else {
            delete_option('wptm_syn_google_delay_' . $id);
        }
    }
}
