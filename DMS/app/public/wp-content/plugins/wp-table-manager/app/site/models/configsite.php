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

defined('ABSPATH') || die();

/**
 * Class WptmModelConfigsite
 */
class WptmModelConfigsite extends Model
{
    /**
     * Get config
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
            'open_table'                => 1,
            'openHyperlink'                => 1,
            'sync_periodicity'          => 0,
            'wptm_sync_method'          => 'ajax',
            'enable_frontend'           => 0
        );
        $config        = (array) get_option('_wptm_global_config', $defaultConfig);
        $config        = array_merge($defaultConfig, $config);

        return $config;
    }

    /**
     * Function save
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

        $result = update_option('_wptm_global_config', $config, false);
        return $result;
    }
}
