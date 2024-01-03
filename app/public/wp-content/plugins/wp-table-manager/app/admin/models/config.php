<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

defined('ABSPATH') || die();

/**
 * Class WptmModelConfig
 */
class WptmModelConfig extends Model
{
    /**
     * Get config wptm
     *
     * @return array
     */
    public function getConfig()
    {
        $defaultConfig = array(
            'enable_import_excel'       => 1,
            'export_excel_format'       => 'xlsx',
            'enable_autosave'           => 1,
            'automatically_calculation' => 1,
            'openHyperlink' => 1,
            'open_table'                => 1,
            'sync_periodicity'          => 0,
            'wptm_sync_method'          => 'ajax',
            'enable_frontend'           => 0
        );
        $config        = (array) get_option('_wptm_global_config', $defaultConfig);

        $config        = array_merge($defaultConfig, $config);
        return $config;
    }

    /**
     * Save local font
     *
     * @param string $action     Data config
     * @param array  $option     Data config
     * @param string $optionName Name option
     *
     * @return boolean
     */
    public function setLocalFont($action, $option, $optionName = 'local_font')
    {
        $googleFont = WptmTablesHelper::setLocalFont($action, $option, $optionName);

        return $googleFont;
    }

    /**
     * Function save config wptm
     *
     * @param array $datas Data config
     *
     * @return boolean
     */
    public function save($datas)
    {
        $config = get_option('_wptm_global_config');

        foreach ($datas as $key => $value) {
            $config[$key] = $value;
        }

        update_option('_wptm_global_config', $config, false);

        return true;
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
}
