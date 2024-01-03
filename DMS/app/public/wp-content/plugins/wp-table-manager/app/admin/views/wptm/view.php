<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;
use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class wptmViewWptm
 */
class WptmViewWptm extends View
{
    /**
     * Render
     *
     * @param null $tpl Tpl
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $app = Application::getInstance('Wptm');
        $id_table = Utilities::getInt('id_table', 'GET');
        if (Utilities::getInput('caninsert', 'GET', 'bool')) {
            $this->caninsert = true;
        } else {
            $this->caninsert = false;
        }
        $this->charts = array();
        $this->id_charts = Utilities::getInt('chart', 'GET');

        $this->idUser = get_current_user_id();
        require_once plugin_dir_path(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'user.php';
        $WptmControllerUser = new WptmControllerUser();
        $this->list_user = $WptmControllerUser->getListUser();

        if ($id_table !== 0) {
            $modelStyles  = $this->getModel('styles');
            $this->styles = $modelStyles->getStyles();

            $modelTable = $this->getModel('table');
            $this->table = $modelTable->getItem($id_table, true, true, null, false, false);

            $modelCharts      = $this->getModel('charts');
            $this->chartTypes = $modelCharts->getChartTypes();

            $this->id_table = $id_table;
        } else {
            $cid = Utilities::getInt('cid', 'GET');
            $modelCat         = $this->getModel('categories');
            $this->categories = $modelCat->getCategories();

            if (isset($cid) && $cid !== 0) {
                $this->cid = $cid;
                setcookie('wptm_category_id', $this->cid, time() + (86400 * 30), '/');
            } else {
                if (empty($this->categories) || empty($this->categories[0])) {
                    $modelCat = $this->getModel('category');
                    $id    = $modelCat->addCategory(esc_attr__('first category', 'wptm'), $this->idUser);
                    if ($id) {
                        $model = $this->getModel('categories');
                        if ($model->move($id, 1, 'first-child')) {
                            $this->categories = $model->getCategories();
                        }
                    }
                }
                if (!isset($_COOKIE['wptm_category_id']) || (int)$_COOKIE['wptm_category_id'] < 1) {
                    $this->cid = (int)$this->categories[0]->id;//when has not cid to $cid = first category
                } else {
                    $this->cid = (int)$_COOKIE['wptm_category_id'];
                }
            }

//            if ($this->caninsert) {
                $modelCharts = $this->getModel('charts');
                $charts = $modelCharts->getAllCharts();
                $this->charts = WptmTablesHelper::categoryCharts($charts);
//                var_dump($this->charts[104]);
//            }
        }

        $this->convert = get_option('wptm_tables_convert', null);

        //google font
        $modelConfig  = $this->getModel('config');
        $this->params = $modelConfig->getConfig();
        $this->listsFont = array();

        if (!empty($this->params['fonts_google']) && $this->params['fonts_google']) {
            $arrayValues = explode('|', $this->params['fonts_google']);
            $upload_url = wp_upload_dir();
            if (is_ssl()) {
                $upload_url['baseurl'] = str_replace('http://', 'https://', $upload_url['baseurl']);
            }
            $upload_url = $upload_url['baseurl'] . '/wptm/';

            foreach ($arrayValues as $arrayValue) {
                if ($arrayValue !== '') {
                    global $wpdb;
                    $nameFont = str_replace(' ', '_', $arrayValue);
                    $result = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.option_name = %s', 'google_font' . $nameFont));
                    $url = '';
                    if (empty($result)) {
                        $urlGoogle = 'https://fonts.googleapis.com/css?family=' . $arrayValue;
                        wp_enqueue_style('wptm-google-fonts' . $nameFont, $urlGoogle);
                    } else {
                        $url = $result->option_value;
                        wp_enqueue_style('wptm-google-fonts' . $nameFont, $upload_url . $url);
                    }

                    $this->listsFont[] = $arrayValue;
                }
            }
        }

        //local font
        $localFonts = WptmTablesHelper::getlocalfont();
        $this->listsLocalFont = array();
        $this->localFont = '';
        if (isset($localFonts) && count($localFonts) > 0) {
            foreach ($localFonts as $key => $localFont) {
                if (isset($localFont->urc)) {
                    $this->listsLocalFont[] = $localFont->data[0]->name_font;
                    $this->localFont .= $localFont->urc;
                }
            }
        }

        parent::render($tpl);
    }
}
