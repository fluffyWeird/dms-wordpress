<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Factory;

defined('ABSPATH') || die();

$app = Application::getInstance('Wptm');
require_once $app->getPath() . DIRECTORY_SEPARATOR . $app->getType() . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'wptmBase.php';

add_action('admin_menu', 'wptm_menu');
add_action('wp_ajax_Wptm', 'wptm_ajax');
add_action('media_buttons', 'wptm_button');
add_action('load-dashboard_page_wptm-foldertree', 'wptm_foldertree_thickbox');
add_action('wp_ajax_wptm_getFolders', 'wptm_getFolders');
add_action('admin_init', 'wptm_update_version');
add_action('plugins_loaded', 'wptm_update_tables');
add_action('admin_enqueue_scripts', 'wptm_admin_enqueue_script');

if (!function_exists('wptm_update_tables')) {
    /**
     * Update tables when update plugin
     *
     * @return void
     */
    function wptm_update_tables()
    {
        $version = get_option('wptm_version');
        $updated_tables = get_option('wptm_updated_tables');
        if (version_compare($version, '2.7.0', '>=') && version_compare($version, '2.7.2', '<') && !$updated_tables) {//update from 2.7.0| 2.7.1
            $folder = wp_upload_dir();
            $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
            if (file_exists($folder)) {
                $files = glob($folder . '*');
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file); // delete file
                    }
                }
            }
            update_option('wptm_updated_tables', true);
        }

        if (version_compare($version, '2.7.0', '<') && $version !== '3.9.4' && strripos($version, '}}') !== false) {//update from 2.6.x
            $tables_convert = get_option('wptm_tables_convert', null);

            if ($tables_convert === null) {
                update_option('wptm_tables_convert', 0);
            }

            if ($tables_convert === null || $tables_convert > -1) {
                add_action('admin_notices', 'wp_admin_notice_convert');

                $page = Utilities::getInput('page', 'GET', 'string');
                if ($page === 'wptm-config') {
                    wp_safe_redirect(admin_url('index.php?page=wptm'));
                    exit;
                }
            }
        }

        $app = Application::getInstance('Wptm');
        $tables_convert = get_option('wptm_tables_convert', null);
        if (version_compare($version, '2.7.0', '>=') && version_compare($version, '2.7.1', '<')
            && $tables_convert !== null && (int)$tables_convert === -1) {//remove data column when upadte from 2.7.0
            $modelTable = Model::getInstance('tables');
            if ($modelTable->removeDataColumn()) {
                update_option('wptm_tables_convert', -2);
            }
        }

        if (version_compare($version, '2.7.2', '>=') && version_compare($version, '2.7.3', '<') && $tables_convert !== null && (int)$tables_convert > -3) {
            $modelTable = Model::getInstance('tables');
            $item = $modelTable->getListTableById();
            $count = count($item);

            for ($i = 0; $i < $count; $i++) {
                $modelTable = Model::getInstance('table');
                $id = $item[$i]->id;
                $pase = $modelTable->updateMergeCells($id, true);

                if (!$pase) {
                    add_action('admin_notices', function () use ($id) {
                        $wptm_list_url = admin_url('admin.php?page=wptm');
                        ?>
                        <div class="notice notice-warning" style="padding: 10px 12px 5px">
                            <span style="font-size: 20px;line-height: 1;">
                                <?php
                                esc_attr_e('Warning', 'wptm');
                                ?>
                            </span>
                            <p>
                                <?php
                                sprintf(esc_attr__('An error occurred during the automatic update WP Table Manager plugin process! Table with id $s appear errors', 'wptm'), $id);
                                ?>
                            </p>
                            <a class="wptm_convert" href="<?php
                            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link list table
                            echo $wptm_list_url; ?>" style="    background: #0073aa;
                                color: #ffffff;
                                padding: 5px 10px;
                                border-radius: 5px;
                                text-decoration: none;
                                display: inline-block;
                                margin: 0 0 10px auto;">Update</a>
                        </div>
                        <?php
                    });
                    break;
                }
            }
            update_option('wptm_tables_convert', -3);
        }

        if (version_compare($version, '3.0.0', '<') && $version !== '3.9.4' && strripos($version, '}}') === false) {//update from < 3.0.0
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            global $wpdb;
            maybe_create_table(
                $wpdb->prefix . 'wptm_table_options',
                'CREATE TABLE ' . $wpdb->prefix . 'wptm_table_options  (
                id int(11) NOT NULL AUTO_INCREMENT,
                id_table int(11) NOT NULL,
                option_name varchar(255) NOT NULL,
                option_value text NOT NULL,
                PRIMARY KEY  (id)
              ) ENGINE=InnoDB ;'
            );
            $modelTable = Model::getInstance('tables');
            $list_db_table = $modelTable->getDbItems();

            if ($list_db_table && isset($list_db_table[0])) {
                $count = count($list_db_table);
                if ($count > 0) {//list
                    require_once plugin_dir_path(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'dbtable.php';
                    $modelDbTable = new WptmModelDbtable();
                    for ($i = 0; $i < $count; $i ++) {
                        if (!empty($list_db_table[$i]->mysql_query) && $list_db_table[$i]->mysql_query !== null) {
                            if (!$modelDbTable->getQueryOption($list_db_table[$i]->id)) {
                                $modelDbTable->updateOldTable($list_db_table[$i]->id, $list_db_table[$i]->mysql_query);
                            }
                        }
                    }
                }
            }
        }
    }
}
/**
 * Wptm admin notice
 *
 * @return void
 */
function wp_admin_notice_convert()
{
    $wptm_list_url = admin_url('admin.php?page=wptm');
    ?>
    <div class="notice notice-success" style="padding: 10px 12px 5px">
        <span style="font-size: 20px;line-height: 1;">
            <?php
            esc_attr_e('WP Table Manager database update required ', 'wptm');
            ?>
        </span>
        <p>
            <?php
            esc_attr_e('WP Table Manager has been updated! To keep things running smoothly, we have to update your database to the newest version.', 'wptm');
            ?>
        </p>
        <a class="wptm_convert" href="<?php
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link list table
        echo $wptm_list_url; ?>" style="    background: #0073aa;
    color: #ffffff;
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    margin: 0 0 10px auto;">Update</a>
    </div>
    <?php
}

if (!function_exists('wptm_update_version')) {
    /**
     * Update data params category when update plugin
     *
     * @return void|boolean
     */
    function wptm_update_version()
    {
        $version = get_option('wptm_version');
        if (version_compare($version, '2.3.0', '<') && $version !== '3.9.4' && strripos($version, '}}') === false) {
            $id_user = get_current_user_id();
            $app = Application::getInstance('Wptm');
            $modelCat = Model::getInstance('categories');
            $categories = $modelCat->getCategories();
            $count_categories = count($categories);
            for ($index = 0; $index < $count_categories; $index++) {
                $id = $categories[$index]->id;
                if ($categories[$index]->params !== '') {
                    $dataCategory = json_decode($categories[$index]->params);
                } else {
                    $dataCategory = new stdClass();
                }
                if (empty($dataCategory->role)) {
                    $dataCategory = new stdClass();
                    $dataCategory->role = new stdClass();
                    $dataCategory->role->{0} = (string)$id_user;
                    $dataCategory = json_encode($dataCategory);
                    $modelUser = Model::getInstance('user');
                    $modelUser->save($id, $dataCategory, 0);
                }
            }
            // Set permissions for editors and admins so they can do stuff with wptm
            $wptm_roles = array('editor', 'administrator');
            foreach ($wptm_roles as $role_name) {
                $role = get_role($role_name);
                if ($role) {
                    $role->add_cap('wptm_create_category');
                    $role->add_cap('wptm_edit_category');
                    $role->add_cap('wptm_edit_own_category');
                    $role->add_cap('wptm_delete_category');
                    $role->add_cap('wptm_create_tables');
                    $role->add_cap('wptm_edit_tables');
                    $role->add_cap('wptm_edit_own_tables');
                    $role->add_cap('wptm_delete_tables');
                    $role->add_cap('wptm_access_category');
                }
            }
        }

        //update default theme when update plugin
        if (version_compare($version, '2.7.0', '<') && $version !== '3.9.4' && strripos($version, '}}') === false) {
            if (function_exists('wptm_update_styles')) {
                global $wpdb;

                change_wptm_table($wpdb);

                $role = get_role('administrator');

                if ($role) {//update cap when update version plugin
                    $role->add_cap('wptm_access_database_table');
                }
            }
            update_option('wptm_version', WPTM_VERSION);
        }

        //update default theme when update plugin
        if (version_compare($version, '3.0.5', '<') && $version !== '3.9.4' && strripos($version, '}}') === false) {
            $folder = wp_upload_dir();
            $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
            if (file_exists($folder)) {
                $files = glob($folder . '*');
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file); // delete file
                    }
                }
            }
        }

        if (version_compare($version, '3.7.0', '<') && $version !== '3.9.4' && strripos($version, '}}') === false) {
            if (function_exists('wptm_update_styles')) {
                global $wpdb;

                wptm_update_styles($wpdb);
            }
        }

        if (version_compare($version, '3.9.4', '<') && $version !== '3.9.4' && strripos($version, '}}') === false) {
            $folder = wp_upload_dir();
            $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
            if (file_exists($folder)) {
                $files = glob($folder . '*');
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file); // delete file
                    }
                }
            }
            update_option('wptm_version', '3.9.4');
        }
    }
}

/**
 * Change structure of wptm_table database
 *
 * @param object $wpdb Object wordPress database abstraction object.
 *
 * @return void
 */
function change_wptm_table($wpdb)
{
    $row = $wpdb->get_results('SHOW COLUMNS FROM ' . $wpdb->prefix . 'wptm_tables LIKE \'type\'');
    if (empty($row)) {
        $wpdb->query('ALTER TABLE ' . $wpdb->prefix . 'wptm_tables ADD type char(50) DEFAULT \'html\'');

        $wpdb->query('UPDATE ' . $wpdb->prefix . 'wptm_tables SET type = \'mysql\' WHERE params LIKE \'%table_type":"mysql%\'');
    }
}

/**
 * Load the heartbeat JS
 *
 * @return void
 */
function wptm_heartbeat_enqueue()
{
    // Make sure the JS part of the Heartbeat API is loaded.
    wp_enqueue_script('heartbeat');
    add_action('admin_print_footer_scripts', 'wptm_heartbeat_footer_js', 20);
}

add_action('admin_enqueue_scripts', 'wptm_heartbeat_enqueue');

/**
 * Inject our JS into the admin footer
 *
 * @return void
 */
function wptm_heartbeat_footer_js()
{
    global $pagenow;
    Application::getInstance('Wptm');
    $wptm_ajaxurl = Factory::getApplication()->getAjaxUrl();
    ?>
    <script>
        (function ($) {
            var wptm_ajaxurl = "<?php echo esc_url_raw($wptm_ajaxurl); ?>";
            // Hook into the heartbeat-send
            $(document).on('heartbeat-send', function (e, data) {
                data['wptm_heartbeat'] = 'rendering';
            });

            // Listen for the custom event "heartbeat-tick" on $(document).
            $(document).on('heartbeat-tick', function (e, data) {
                // Only proceed if our EDD data is present
                if (!data['wptm-result'] && data['wptm-list-syn-table'] === '') {
                    return;
                } else {
                    var listTable = data['wptm-list-syn-table'];
                    heartbeatSyncTable(listTable, 0);
                }
            });

            function heartbeatSyncTable (listTable, count) {
                if (typeof listTable !== "undefined" && typeof listTable[count] !== "undefined" && parseInt(listTable[count].id) > 0) {
                    $.ajax({
                        url: wptm_ajaxurl + "task=excel.fetchSpreadsheet",
                        type: 'POST',
                        dataType: "json",
                        data: {
                            'id': listTable[count].id,
                            'sync': 1,
                            'style': listTable[count].spreadsheet_style,
                            'getListSheet': 99,
                            'syncType': listTable[count].type,
                            'spreadsheet_url': listTable[count].spreadsheet_url,
                        },
                        success: function (datas) {
                            count++;
                            if (typeof listTable[count] !== 'undefined') {
                                heartbeatSyncTable(listTable, count);
                            }
                        },
                        error: function (jqxhr, textStatus, error) {
                            bootbox.alert(textStatus + " : " + error, wptmText.Ok);
                        }
                    });
                }
            }
        }(jQuery));
    </script>
    <?php
}

if (!function_exists('is_countable')) {
    /**
     * Check countable variables from php version 7.3.0
     *
     * @param mixed $var Variable to check
     *
     * @return boolean
     */
    function is_countable($var)
    {
        return is_array($var) || $var instanceof Countable || $var instanceof ResourceBundle || $var instanceof SimpleXmlElement;
    }
}

/**
 * Modify the data that goes back with the heartbeat-tick
 *
 * @param array $response Response
 * @param array $data     Data
 *
 * @return mixed
 */
function wptm_heartbeat_received($response, $data)
{
    // Make sure we only run our query if the edd_heartbeat key is present
    if (!empty($data['wptm_heartbeat']) && (string)$data['wptm_heartbeat'] === 'rendering') {
        $app = Application::getInstance('Wptm');
        $modelConfig = Model::getInstance('config');
        $params = $modelConfig->getConfig();

        if (isset($params['sync_periodicity']) && (string)$params['sync_periodicity'] !== '0' && $params['wptm_sync_method'] === 'ajax') :
            if (isset($params['last_sync']) && (string)$params['last_sync'] !== '0') {
                $last_sync = (int)$params['last_sync'];
            } else {
                $last_sync = 0;
            }
            $time_now = (int)strtotime(date('Y-m-d H:i:s'));
            if (($time_now - $last_sync) / 3600 >= (float)$params['sync_periodicity']) {
                $app = Application::getInstance('Wptm');
                require_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'tables.php';
                $tables = new WptmModelTables();
                $list_table = $tables->getListTableHasSyn();

                if ($list_table !== false) {
                    $response['wptm-list-syn-table'] = $list_table;
                } else {
                    $response['wptm-list-syn-table'] = '';
                }

                $params['last_sync'] = $time_now;
                $modelConfig->save($params);
            }
        endif;
        // Send back the number of complete payments
        $response['wptm-result'] = time();
    }
    return $response;
}

add_filter('heartbeat_received', 'wptm_heartbeat_received', 10, 2);
/**
 * Create menu
 *
 * @return void
 */
function wptm_menu()
{
    $app = Application::getInstance('Wptm');
    add_menu_page(
        'WP Table Manager',
        'WP Table Manager',
        'wptm_access_category',
        'wptm',
        'wptm_call',
        plugin_dir_url(__FILE__) . '/assets/images/listTables/sidebar-icon.svg'
    );
    add_submenu_page('wptm', 'All tables', 'All tables', 'wptm_access_category', 'wptm');

    add_submenu_page('wptm', 'WP Table Manager config', 'Configuration', 'manage_options', 'wptm-config', 'wptm_call_config');
    add_submenu_page('', 'Folder tree', 'Folder tree', 'manage_options', 'wptm-foldertree', 'wptm_folderTree');
}

add_action('admin_head', 'wptmCustomIcon');

/**
 * Function custom menu icon
 *
 * @return void
 */
function wptmCustomIcon()
{
    echo '<style>

//    #toplevel_page_wptm .wp-submenu li:last-child {
//        display: none;
//    }

    #toplevel_page_wptm .dashicons-before img {
        width: 25px;
        height: 20px;
        padding-top: 7px;
    }
  </style>';
}

/**
 * Function ajax
 *
 * @return void
 */
function wptm_ajax()
{
    define('WPTM_AJAX', true);
    wptm_call();
}

/**
 * Function call
 *
 * @param null   $ref          Ref
 * @param string $default_task Default task
 *
 * @return void
 */
function wptm_call($ref = null, $default_task = 'wptm.display')
{
    if (!current_user_can('wptm_access_category')) {
        wp_die(esc_attr__('You do not have sufficient permissions to access this page.', 'wptm'));
    }

    if (!defined('WPTM_AJAX')) {
        wptm_init();
    }

    $application = Application::getInstance('Wptm');
    $dbtable = Utilities::getInput('type', 'GET', 'none');

    if ($dbtable === 'dbtable') {
        $application->execute('dbtable.display');
    } else {
        $application->execute($default_task);
    }
}

/**
 * Call config
 *
 * @return void
 */
function wptm_call_config()
{
    wptm_call(null, 'config.display');
}


/**
 * Enqueue scripts for admin
 *
 * @return void
 */
function wptm_admin_enqueue_script()
{
    $page = Utilities::getInput('page', 'GET', 'string');

    if ($page === 'wptm-config') {
        wp_enqueue_style('wptm-cssJU_style', plugins_url('assets/cssJU/css/style.css', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_style('wptm-waves_style', plugins_url('assets/cssJU/css/waves.min.css', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_style('wptm-bootstrap', plugins_url('assets/css/bootstrap.min.css', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_style('wptm-style', plugins_url('assets/css/config.css', __FILE__), array(), WPTM_VERSION);
    }

    if ($page === 'wptm' || $page === 'wptm-config' || $page === 'wptm-folderTree') {
        wp_enqueue_style('wptm-style', plugins_url('assets/css/style.css', __FILE__), array(), WPTM_VERSION);
    }
}

/**
 * Function init
 *
 * @return void
 */
function wptm_init()
{
    $page = Utilities::getInput('page', 'GET', 'string');
    $dbtable = Utilities::getInput('type', 'GET', 'none');

    $min = '.min';
    if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
        $min = '';
    }

    $application = Application::getInstance('Wptm');
    load_plugin_textdomain('wptm', null, $application->getPath(true) . DIRECTORY_SEPARATOR . 'languages');
    load_plugin_textdomain('wptm', null, dirname(plugin_basename(WPTM_PLUGIN_FILE)) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'languages');

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-migrate');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-resizable');

    wp_enqueue_style('wptm-tooltipster', plugins_url('assets/css/tooltipster.bundle.min.css', __FILE__), array(), false, 'all');
    wp_enqueue_style('wptm-tooltipster-theme', plugins_url('assets/css/tooltipster-sideTip-borderless.min.css', __FILE__), array(), false, 'all');
    wp_enqueue_script('wptm-tooltipster', plugins_url('assets/js/tooltipster.bundle.min.js', __FILE__), array(), false, true);

    wp_enqueue_script('wptm-iris', plugins_url('assets/js/iris.min.js', __FILE__), array('jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch'), false, 1);
    wp_enqueue_script('wptm-color-picker', admin_url('js/color-picker.min.js'), array('wptm-iris'), false, 1);
    wp_localize_script('wptm-color-picker', 'wpColorPickerL10n', array(/*remove wpColorPickerL10n.pick in wp version 5.5*/
        'clear' => __('Clear', 'wptm'),
        'defaultString' => __('Default', 'wptm'),
        'current' => __('Current Color', 'wptm'),
    ));
    wp_enqueue_style('wp-color-picker');

    if ($page === 'wptm-config') {
        wp_enqueue_script('wptm-bootstrap', plugins_url('assets/js/bootstrap.min.js', __FILE__), array(), WPTM_VERSION);
    }

    wp_enqueue_script('wptm-touch-punch', plugins_url('assets/js/jquery.ui.touch-punch.min.js', __FILE__), array(), WPTM_VERSION);

    wp_enqueue_style('buttons');
    wp_enqueue_style('wp-admin');
    wp_enqueue_style('colors-fresh');

    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');

    wp_enqueue_style('wptm-table-sprites', plugins_url('assets/css/table-sprites.css', __FILE__, array(), WPTM_VERSION));

    if ($page === 'wptm-config') {
        wp_enqueue_script('wptm-bootbox', plugins_url('assets/js/bootbox.js', __FILE__), array(), WPTM_VERSION);
        wp_localize_script('wptm-bootbox', 'wptmCmd', array(
            'Delete' => __('Delete', 'wptm'),
            'Edit' => __('Edit', 'wptm'),
            'CANCEL' => __('Cancel', 'wptm'),
            'OK' => __('Ok', 'wptm'),
            'CONFIRM' => __('Confirm', 'wptm'),
            'Save' => __('Save', 'wptm'),
        ));
    }

    if (($page === 'wptm' || $page === 'wptm-folderTree') && $dbtable !== 'dbtable') {
        $tid = Utilities::getInt('id_table', 'GET');
        wp_enqueue_script('wptm-scrollbar', plugins_url('assets/js/smooth-scrollbar.js', __FILE__), array(), WPTM_VERSION);

        if (isset($tid) && $tid !== 0) {
            wp_dequeue_script('handsontable');
            wp_enqueue_style('wptm-handsontable', plugins_url('assets/css/handsontable.full.css', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_style('wptm-codemirror', plugins_url('assets/codemirror/codemirror.css', __FILE__));
            wp_enqueue_style('wptm-codemirror3024-night', plugins_url('assets/codemirror/3024-night.css', __FILE__));
            wp_enqueue_style('wptm-modal', plugins_url('assets/css/leanmodal.css', __FILE__));
//            wp_enqueue_style('wptm-multiselect-css', plugins_url('assets/plugins/multiselect/multiselect.css', __FILE__));

            wp_enqueue_script('wptm-modal', plugins_url('assets/js/jquery.leanModal.min.js', __FILE__));
            wp_enqueue_script('less', plugins_url('assets/js/less.js', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_script('wptm-handsontable', plugins_url('assets/js/handsontable.full.js', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_script('wptm-formula', plugins_url('assets/plugins/formula.min.js', __FILE__), array(), false, 'all');
            wp_enqueue_script('wptm-accounting', plugins_url('assets/plugins/accounting.js', __FILE__), array(), false, 'all');
            wp_enqueue_script('wptm-jdateformatparser', plugins_url('assets/plugins/moment-jdateformatparser.js', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_script('jquery-textselect', plugins_url('assets/js/jquery.textselect.min.js', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_script('wptm-codemirror', plugins_url('assets/codemirror/codemirror.js', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_script('wptm-codemirror-css', plugins_url('assets/codemirror/mode/css/css.js', __FILE__), array(), WPTM_VERSION);
//            wp_enqueue_script('wptm-multiselect', plugins_url('assets/plugins/multiselect/multiselect-dropdown.js', __FILE__), array(), WPTM_VERSION, true);
            wp_enqueue_script('wptm-fast-formula-parser', plugins_url('assets/plugins/fast-formula-parser/build/parser.min.js', __FILE__), array(), WPTM_VERSION, true);
            wp_enqueue_script('wptm_tipso', plugins_url('/site/assets/tipso/tipso' . $min . '.js', __DIR__), array(), WPTM_VERSION, true);
            wp_enqueue_script('wptm-main', plugins_url('assets/js/wptm.js', __FILE__), array(), WPTM_VERSION, true);
            wp_enqueue_script('wptm-chart', plugins_url('assets/js/Chart.js', __FILE__), array(), WPTM_VERSION, true);
        } else {
            wp_enqueue_script('wptm_tablesorter', plugins_url('/site/assets/tablesorter/jquery.tablesorter.js', __DIR__), array(), WPTM_VERSION);
            wp_enqueue_script('wptm-main', plugins_url('assets/js/wptmTablesCategories.js', __FILE__), array(), WPTM_VERSION);
        }
    }
    
    if ($page === 'wptm-config') {
        wp_enqueue_script('wptm-waves_js', plugins_url('assets/cssJU/js/waves.min.js', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_script('wptm-velocity_js', plugins_url('assets/cssJU/js/velocity.min.js', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_script('wptm-script_js', plugins_url('assets/cssJU/js/tabs.js', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_script('wptm-cssJUscript_js', plugins_url('assets/cssJU/js/script.js', __FILE__), array(), WPTM_VERSION);
        // This will enqueue the Media Uploader script
        /**
         * Load media_files
         *
         * @return void
         */
        function load_wp_media_files()
        {
            wp_enqueue_media();
        }
        add_action('admin_enqueue_scripts', 'load_wp_media_files');

        wp_enqueue_script('wptm-webfont', 'https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js', array(), WPTM_VERSION);
        wp_enqueue_script('wptm-main', plugins_url('assets/js/wptmconfig.js', __FILE__), array(), WPTM_VERSION);
    }

    if ($dbtable === 'dbtable') {
        wp_enqueue_style('wptm-codemirror', plugins_url('assets/codemirror/codemirror.css', __FILE__));
//        wp_enqueue_style('wptm-codemirror1', plugins_url('assets/codemirror/mode/sql/show-hint.css', __FILE__));
        wp_enqueue_script('wptm-codemirror', plugins_url('assets/codemirror/codemirror.js', __FILE__), array(), WPTM_VERSION);
//        wp_enqueue_script('wptm-codemirror-sql3', plugins_url('assets/codemirror/mode/sql/matchbrackets.js', __FILE__), array());
        wp_enqueue_script('wptm-codemirror-sql', plugins_url('assets/codemirror/mode/sql/sql.js', __FILE__), array());
//        wp_enqueue_script('wptm-codemirror-sql1', plugins_url('assets/codemirror/mode/sql/show-hint.js', __FILE__), array());
//        wp_enqueue_script('wptm-codemirror-sql2', plugins_url('assets/codemirror/mode/sql/sql-hint.js', __FILE__), array());

        wp_enqueue_script('wptm_tablesorter', plugins_url('/site/assets/tablesorter/jquery.tablesorter.js', __DIR__), array(), WPTM_VERSION);
        wp_enqueue_script('wptm-scrollbar', plugins_url('assets/js/smooth-scrollbar.js', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_script('wptm-handlebars', plugins_url('assets/js/handlebars-1.0.0-rc.3.js', __FILE__), array(), WPTM_VERSION);
        wp_enqueue_script('wptm-main', plugins_url('assets/js/wptm_dbtable.js', __FILE__), array(), WPTM_VERSION);
    }

    wp_enqueue_script('dropzone', plugins_url('assets/js/dropzone' . $min . '.js', __FILE__), array(), WPTM_VERSION);
    wp_enqueue_script('jquery-fileDownload', plugins_url('assets/js/jquery.fileDownload.js', __FILE__), array(), WPTM_VERSION);

    wp_localize_script('wptm-main', 'wptm_permissions', array(
        'can_create_category' => current_user_can('wptm_create_category'),
        'can_edit_category' => current_user_can('wptm_edit_category'),
        'can_edit_own_category' => current_user_can('wptm_edit_own_category'),
        'can_delete_category' => current_user_can('wptm_delete_category'),
        'can_create_tables' => current_user_can('wptm_create_tables'),
        'can_edit_tables' => current_user_can('wptm_edit_tables'),
        'can_edit_own_tables' => current_user_can('wptm_edit_own_tables'),
        'can_delete_tables' => current_user_can('wptm_delete_tables'),
        'can_access_category' => current_user_can('wptm_access_category'),
        'can_access_database_table' => current_user_can('wptm_access_database_table'),
        'translate' => array(
            'wptm_create_category' => __('You don\'t have permission to create new category', 'wptm'),
            'wptm_edit_category' => __('You don\'t have permission to edit category', 'wptm'),
            'wptm_delete_category' => __('You don\'t have permission to delete category', 'wptm'),
            'wptm_create_tables' => __('You don\'t have permission to create new tables', 'wptm'),
            'wptm_edit_tables' => __('You don\'t have permission to edit tables', 'wptm'),
            'wptm_delete_tables' => __('You don\'t have permission to delete tables', 'wptm')
        ),
    ));

    wp_localize_script('wptm-main', 'wptmText', array(
        'ALERT_CHANGE_COLUMN_TYPE' => 'After changing the data type of the column, the old data in the current column may be lost!',
        'THE_TITLES_OF_THE_COLUMNS_MAY_NOT_BE_SAME' => __('The names of the columns may not be the same.', 'wptm'),
        'SAVING' => __('Saving...', 'wptm'),
        'ALL_CHANGES_SAVED' => __('All changes saved', 'wptm'),
        'DATA_HAS_BEEN_FETCHED' => __('Data has been fetched', 'wptm'),
        'TABLE_LIST' => __('WP Table Manager ...', 'wptm'),
        'TABLE_LIST_FULL' => __('WP Table Manager - Table List', 'wptm'),
        'EDIT_TABLE_TITLE_TAG' => __('Edit table', 'wptm'),
        'TABLE_EDIT_WIZARD_TITLE_TAG' => __('Table Edit Wizard', 'wptm'),
        'TABLE_CREATION_WIZARD_TITLE_TAG' => __('Table Creation Wizard', 'wptm'),
        'Delete' => __('Delete', 'wptm'),
        'Edit' => __('Edit', 'wptm'),
        'Add' => __('Add', 'wptm'),
        'Cancel' => __('Cancel', 'wptm'),
        'Ok' => __('Ok', 'wptm'),
        'Confirm' => __('Confirm', 'wptm'),
        'Save' => __('Save', 'wptm'),
        'Save_range' => __('Save range', 'wptm'),
        'ADDED' => __('Added', 'wptm'),
        'GOT_IT' => __('Got it!', 'wptm'),
        'LAYOUT_WPTM_SELECT_ONE' => __('Please select a table a create a new one', 'wptm'),
        'VIEW_WPTM_TABLE_ADD' => __('Add new table', 'wptm'),
        'JS_WANT_DELETE' => __('Do you really want to delete ', 'wptm'),
        'CHANGE_INVALID_CHART_DATA' => __('Invalid chart data', 'wptm'),
        'CHANGE_INVALID_CELL_DATA' => __('The value of the cell does not match the column format. For example, and integer format cannot contain letters.', 'wptm'),
        'CHANGE_ERROR_ROLE_OWN_CATEGORY' => __('Only one user has the right to own', 'wptm'),
        'CHANGE_ROLE_OWN_CATEGORY' => __('Successful ownership change', 'wptm'),
        'CHART_INVALID_DATA' => __('Cannot generate chart from those data selection, please make a new data range selection with at least one row or column and some numeric data, thanks!', 'wptm'),
        'CHART_NOT_EXIST' => __('No charts have been created for this table, sorry.', 'wptm'),
        'error_link_import_sync' => __('Your path is incorrect, please enter the link again, thanks!', 'wptm'),
        'error_link_google_sync' => __('Please double check the Google Sheet access configuration, cannot get the data.', 'wptm'),
        'CHOOSE_EXCEL_FIE_TYPE' => __('Please choose a file with type of xls or xlsx.', 'wptm'),
        'CHOOSE_CSV_FIE_TYPE' => __('Please choose a file with type of csv.', 'wptm'),
        'WARNING_CHANGE_THEME' => __('Warning - all data and styles will be removed & replaced on theme switch', 'wptm'),
        'Your browser does not support HTML5 file uploads' => __('Your browser does not support HTML5 file uploads', 'wptm'),
        'Too many files' => __('Too many files', 'wptm'),
        'is too large' => __('is too large', 'wptm'),
        'Only images are allowed' => __('Only images are allowed', 'wptm'),
        'Do you want to delete &quot;' => __('Do you want to delete &quot;', 'wptm'),
        'Select files' => __('Select files', 'wptm'),
        'Image parameters' => __('Image parameters', 'wptm'),
        'notice_msg_table_syncable' => __('This spreadsheet is currently sync with an external file, you may lose content in case of modification', 'wptm'),
        'notice_msg_table_database' => __('Table data are from database, only the 50 first rows are displayed for performance reason.', 'wptm'),
        'import_export_style' => __('Export excel', 'wptm'),
        'import_export_data_styles' => __('Data + styles', 'wptm'),
        'import_export_data_only' => __('Data only', 'wptm'),
        'save_alternate' => __('Remember to save your alternate color change', 'wptm'),
        'delete_table_question' => __('Are you sure you want to delete tables selected ?', 'wptm'),
        'delete_category_question' => __('Are you sure you want to delete category selected ?', 'wptm'),
        'delete_table' => __('Delete Tables', 'wptm'),
        'rename_category' => __('Rename Category', 'wptm'),
        'noti_category_renamed' => __('Category renamed', 'wptm'),
        'noti_table_renamed' => __('Table renamed', 'wptm'),
        'noti_chart_renamed' => __('Chart renamed', 'wptm'),
        'create_table' => __('Table', 'wptm'),
        'create_dbtable' => __('Database Table', 'wptm'),
        'create_category' => __('Category', 'wptm'),
        'delete_category' => __('Delete Category', 'wptm'),
        'noti_delete_category' => __('Category deleted', 'wptm'),
        'new_name_category' => __('New category', 'wptm'),
        'edit_success' => __('Edit Success', 'wptm'),
        'edited_success' => __('Edited', 'wptm'),
        'delete_success' => __('Table deleted', 'wptm'),
        'delete_chart_success' => __('Chart deleted', 'wptm'),
        'copy_success' => __('Table copied', 'wptm'),
        'copy_chart_success' => __('Chart copied', 'wptm'),
        'created_cat_success' => __('Category added', 'wptm'),
        'have_error' => __('There was an error', 'wptm'),
        'no_table_found' => __('No table found.', 'wptm'),
        'table_from_database' => __('Table from Database', 'wptm'),
        'chart_title' => __('Chart title', 'wptm'),
        'last_edit' => __('Last edit', 'wptm'),
        'order_table' => __('Table order saved with success', 'wptm'),
        'more_table' => __('Table moved with success', 'wptm'),
        'move_category' => __('New category order saved', 'wptm'),
        'error_move_category' => __('Has error ordering the category', 'wptm'),
        'please_reload' => __('please reload the page', 'wptm'),
        'copy_shortCode' => __('Shortcode copied', 'wptm'),
        'please_add_font_file' => __('Please add path to font file', 'wptm'),
        'warning_add_font_file' => __('Please select file with extension ', 'wptm'),
        'table_type' => __('Table type', 'wptm'),
        'Error_convert_old_data' => __('Error during import', 'wptm'),
        'warning_edit_db_table' => __('warning, you are editing the site database, all cells with field FIELD_JOOMUNITED = FIELD_JOOMUNITED_VALUE will be edited', 'wptm'),
        'warning_craete_row_db_table' => __('warning, the column value you entered is not in the correct format stored in the database.', 'wptm'),
        'warning_craete_row_multiple_db_table' => __('warning, cannot create new row for tables created from multiple database tables.', 'wptm'),
        'warning_delete_row_multiple_db_table' => __('Warning! Can not delete a row created from multiple database tables.', 'wptm'),
        'warning_delete_row_db_table' => __('Do you want to delete this row? The data can not be reverted.', 'wptm'),
        'import_is_finished' => __('Import is finished!', 'wptm'),
        'error_message_read_file' => __('Note that the import tool wasn\'t able to read some cell data and therefore have removed some values.<br/> Table connected: ', 'wptm'),
        'error_message_read_file_cells_concerned' => __('<br/> Cells concerned: ', 'wptm'),
        'some_action_cant_be_done' => __('Some actions can\'t be done due to the Technical Problem', 'wptm'),
        'warning_remove_alternating_color' => __('Are you sure you want to remove all the table automatic styling?', 'wptm'),
        'warning_creat_chart' => __('Invalid chart data', 'wptm')
    ));
    wp_localize_script('wptm-main', 'wptmContext', array(
        'cut' => __('Cut', 'wptm'),
        'copy' => __('Copy', 'wptm'),
        'copied' => __('Copied', 'wptm'),
        'paste' => __('Paste', 'wptm'),
        'remove' => __('Remove', 'wptm'),
        'define' => __('Resize row', 'wptm'),
        'defineColumn' => __('Resize column', 'wptm'),
        'create_row_db_table' => __('Create Row', 'wptm'),
        'delete_row_db_table' => __('Delete Row', 'wptm'),
        'hide_column' => __('Hide column on front-end', 'wptm'),
        'insert' => __('Insert', 'wptm'),
        'redo' => __('Redo', 'wptm'),
        'undo' => __('Undo', 'wptm'),
        'merge' => __('Merge', 'wptm'),
        'column_type' => __('Column type', 'wptm'),
        'column_type_varchar' => __('VARCHAR', 'wptm'),
        'column_type_text' => __('TEXT', 'wptm'),
        'column_type_int' => __('INT', 'wptm'),
        'column_type_date' => __('DATE', 'wptm'),
        'column_type_datetime' => __('DATETIME', 'wptm'),
        'column_type_float' => __('FLOAT', 'wptm'),
        'tooltip' => __('Add tooltip', 'wptm'),
        'insert_selection' => __('Cell insertion shortcode', 'wptm'),
        'remove_rows' => __('Remove rows', 'wptm'),
        'remove_cols' => __('Remove columns', 'wptm'),
        'insert_above' => __('Insert rows above', 'wptm'),
        'insert_below' => __('Insert rows below', 'wptm'),
        'insert_left' => __('Insert columns left', 'wptm'),
        'insert_right' => __('Insert columns right', 'wptm'),
        'row_height' => __('Row height:', 'wptm'),
        'rows_height' => __('Selected rows height:', 'wptm'),
        'column_width' => __('Column width:', 'wptm'),
        'columns_width' => __('Selected columns width:', 'wptm'),
        'columns_width_start' => __('Resize columns ', 'wptm'),
        'remove_cell_format' => __('Remove cell format', 'wptm'),
        'remove_alternating_color' => __('Remove automatic styling', 'wptm'),
        'remove_alternating_color_cell' => __('Remove automatic styling for cell', 'wptm'),
        'remove_alternating_color_cells' => __('Remove automatic styling for cells', 'wptm'),
        'rows_height_start' => __('Resize rows ', 'wptm'),
        'protect_range' => __('Protect range', 'wptm'),
        'protect_columns' => __('Protect selected columns', 'wptm')
    ));

    if (Utilities::getInput('noheader', 'GET', 'bool')) {
        if ($page === 'wptm-config') {
            wp_enqueue_style('wptm-bootstrap', plugins_url('assets/css/bootstrap.min.css', __FILE__), array(), WPTM_VERSION);
        }
        wp_enqueue_style('wptm-style', plugins_url('assets/css/style.css', __FILE__), array(), WPTM_VERSION);

        //remove script loaded in bottom of page
        wp_dequeue_script('sitepress-scripts');
        wp_dequeue_script('wpml-tm-scripts');
        wp_enqueue_script('wptm-insert', plugins_url('assets/js/insert_table.js', __FILE__), array(), WPTM_VERSION);
        wp_localize_script('wptm-insert', 'wptmContextBakery', array(
            'bakeryTableText' => __('Hey there, load and edit the WP Table Manager tables from here.', 'wptm'),
            'bakeryChartText' => __('Hey there, load and edit the WP Table Manager charts from here.', 'wptm')
        ));
    }

    wp_enqueue_media();
    add_filter('tiny_mce_before_init', 'wptm_tiny_mce_before_init');  // Before tinymce initialization
    // Build extra plugins array
    add_filter('mce_external_plugins', 'wptm_mce_external_plugins');
    add_editor_style(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/css/wptm-editor-style.css');
}

/**
 * Get button
 *
 * @return void
 */
function wptm_button()
{
    wp_enqueue_style('wptm-modal', plugins_url('assets/css/leanmodal.css', __FILE__));
    wp_enqueue_script('wptm-modal', plugins_url('assets/js/jquery.leanModal.min.js', __FILE__));
    wp_enqueue_script('wptm-modal-init', plugins_url('assets/js/leanmodal.init.js', __FILE__));
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content escape above
    echo "<a href='#wptmmodal' class='button wptmlaunch' id='wptmlaunch' title='WP Table Manager'>"
        . " <span class='dashicons dashicons-screenoptions' style='line-height: inherit;'></span>" . esc_attr__('WP Table Manager', 'wptm') . '</a>';
}

/**
 * Get plugin.min.js
 *
 * @param array $plugins Plugins
 *
 * @return mixed
 */
function wptm_mce_external_plugins($plugins)
{
    $plugins['code'] = WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/plugins/code/plugin.min.js';
    $plugins['wpmedia'] = WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/plugins/wpmedia/plugin.js';
    return $plugins;
}

/**
 * Initialize table ability
 *
 * @param array $init Init
 *
 * @return mixed
 */
function wptm_tiny_mce_before_init($init)
{
    if (isset($init['tools'])) {
        $init['tools'] = $init['tools'] . ',inserttable';
    } else {
        $init['tools'] = 'inserttable';
    }

    if (isset($init['toolbar2'])) {
        $init['toolbar2'] = $init['toolbar2'] . ',code,wpmedia';
    } else {
        $init['toolbar1'] = $init['toolbar1'] . ',code,wpmedia';
    }
    $init['height'] = '500';
    return $init;
}

/**
 * Folder Tree
 *
 * @return void
 */
function wptm_folderTree()
{
    /* Do nothing */
}

/**
 * Get folder tree
 *
 * @return void
 */
function wptm_foldertree_thickbox()
{
    if (!defined('IFRAME_REQUEST')) {
        define('IFRAME_REQUEST', true);
    }

    iframe_header();
    global $wp_scripts, $wp_styles;

    wp_enqueue_script('wptm-jaofiletree', plugins_url('assets/js/jaofiletree.js', __FILE__), array(), WPTM_VERSION);
    wp_enqueue_style('wptm-jaofiletree', plugins_url('assets/css/jaofiletree.css', __FILE__), array(), WPTM_VERSION);

    $file_type = 'xlsx';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check the file type when creating foldertree
    if (!empty($_GET['type'])) {
        $file_type = 'csv';
    }
    ?>
    <div class="popup">
        <div class="pull-top">
            <div id="wptm_foldertree"></div>
        </div>
        <div class="pull-bottom">
            <button class="button button-primary" type="button"
                    onclick="selectFile()"><?php echo esc_attr__('OK', 'wptm') ?></button>
            <button class="button" type="button"
                    onclick="window.parent.tb_remove();"><?php echo esc_attr__('Cancel', 'wptm') ?></button>
        </div>
    </div>
    <style>
        body {
            background: #ffffff;
        }

        .popup {
            padding: 10px;
            display: block;
            height: 100%;
            box-sizing: border-box;
        }

        .pull-top {
            margin: 0 5px;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 4px;
            overflow-y: auto;
            height: calc(100% - 70px);
            display: block;
        }

        .pull-bottom {
            text-align: right;
            margin: 15px 25px 0;
        }

        .wp-core-ui .button {
            transition: box-shadow 0.2s cubic-bezier(0.4, 0, 1, 1), background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            line-height: 36px;
            vertical-align: middle;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0;
            overflow: hidden;
            will-change: box-shadow;
            background: 0 0;
            border: none;
            border-radius: 40px;
            color: #656565;
            position: relative;
            height: 36px;
            margin: 0 10px 0 0;
            min-width: 64px;
            padding: 0 16px;
            display: inline-block;
            float: right;
            width: unset;
        }

        .button.button-primary {
            background-color: rgba(255, 135, 38, 0.77);
            border-color: rgba(255, 135, 38, 0.77);
            color: #ffffff;
        }

        .wp-core-ui .button:hover {
            border: 0;
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
            background: rgba(158, 158, 158, 0.2) radial-gradient(circle, transparent 1%, rgba(158, 158, 158, 0.2) 1%) center/15000%;
        }

        .wp-core-ui .button:active {
            background-color: rgba(158, 158, 158, 0.4);
        }

        .button.button-primary:hover {
            background: rgba(255, 135, 38, 0.77) radial-gradient(circle, transparent 1%, rgba(255, 135, 38, 0.77) 1%) center/15000%;
        }
        #wptm_foldertree .jaofiletree li.drive {
            background-image: none;
            padding-left: 10px !important;
            margin-top: 9px;
        }
        #wptm_foldertree .jaofiletree li.drive > ul {
            padding-left: 10px;
        }
        #wptm_foldertree ul.jaofiletree li:not(.ext_xlsx) {
            list-style: none;
            padding: 0;
            padding-left: 25px;
            margin: 4px 0;
            white-space: nowrap;
        }
        #wptm_foldertree li a {
            width: calc(100% - 35px);
            text-overflow: ellipsis;
            overflow: hidden;
            text-align: left;
            color: #555;
            font-family: 'Roboto', sans-serif;
            font-size: 16px;
            font-style: normal;
            font-stretch: normal;
            line-height: normal;
            letter-spacing: 0.5px;
        }
        #wptm_foldertree li.drive > a {
            font-size: 18px;
            font-weight: bold;
        }
        #wptm_foldertree li.ext_xlsx {
            background-position: 26px 2px;
            padding-left: 0;
            margin-top: 3px;
            background-size: 14px;
        }
        #wptm_foldertree li.ext_xls {
            background-position: 26px 2px;
            padding-left: 0 !important;
            margin-left: 10px !important;
        }

        #wptm_foldertree input[type="checkbox"] {
            margin-right: 30px;
            box-shadow: none;
            border-radius: 2px;
            width: 18px;
            height: 18px;
            border: solid 2px #49bf88;
            background-color: #fff;
            padding: 0;
            position: relative;
            display: inline-block;
            vertical-align: baseline;
        }
        #wptm_foldertree .ext_csv input[type="checkbox"] {
            margin-right: 10px;
        }

        #wptm_foldertree input[type="checkbox"]:checked {
            background-color: #49bf88;
        }

        #wptm_foldertree input[type="checkbox"]:checked::before, #wptm_foldertree input:checked::before {
            position: absolute;
            vertical-align: middle;
            color: transparent;
            font-size: 0;
            content: "";
            background-color: transparent;
            border-left: 2px solid #fff;
            border-bottom: 2px solid #fff;
            left: 50%;
            top: 50%;
            display: block;
            transform: rotate(-45deg);
            transition: all 0.2s linear 0s;
            width: 6px;
            height: 3px;
            margin-left: -4px;
            margin-top: -4px;
            box-sizing: content-box;
        }
    </style>
    <script>
        jQuery(document).ready(function ($) {
            var file_type = "<?php echo esc_attr($file_type) ?>"
            var wptm_site_url = '<?php echo esc_url_raw(get_site_url());?>';
            selectFile = function () {
                var selected_file = "";
                $('#wptm_foldertree').find('input:checked + a').each(function () {
                    selected_file = $(this).attr('data-file');
                })

                if (file_type === 'csv') {
                    window.parent.document.getElementById('csv_url').value = wptm_site_url + selected_file;
                    window.parent.jQuery("#csv_url").change();
                }
                window.parent.document.getElementById('spreadsheet_url').value = wptm_site_url + selected_file;
                window.parent.jQuery("#spreadsheet_url").change();
                window.parent.tb_remove();
            }

            $('#wptm_foldertree').jaofiletreewptm({
                script: ajaxurl,
                file_type: file_type,
                usecheckboxes: 'files',
                showroot: "<?php echo esc_attr__('SERVER FOLDERS', 'wptm') ?>",
                oncheck: function (elem, checked, type, file) {
                }
            });

        })
    </script>
    <?php
    iframe_footer();
    exit; //Die to prevent the page continueing loading and adding the admin menu's etc.
}

/**
 * Get Folders
 *
 * @return void
 */
function wptm_getFolders()
{
    $path = ABSPATH . DIRECTORY_SEPARATOR;
    $dir = Utilities::getInput('dir', 'GET', 'string');
    $file_type = Utilities::getInput('file_type', 'GET', 'string');

    $allowed_ext = array('xls', 'xlsx');
    if ($file_type === 'csv') {
        $allowed_ext = array('csv');
    }
    $return = array();
    $dirs = array();
    $fi = array();
    if (file_exists($path . $dir)) {
        $files = scandir($path . $dir);

        natcasesort($files);
        if (count($files) > 2) { // The 2 counts for . and ..
            // All dirs
            $baseDir = ltrim(rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $dir), '/'), '/');
            if ((string)$baseDir !== '') {
                $baseDir .= '/';
            }
            foreach ($files as $file) {
                if (file_exists($path . $dir . DIRECTORY_SEPARATOR . $file) && $file !== '.' && $file !== '..' && is_dir($path . $dir . DIRECTORY_SEPARATOR . $file)) {
                    $dirs[] = array('type' => 'dir', 'dir' => $dir, 'file' => $file);
                } elseif (file_exists($path . $dir . DIRECTORY_SEPARATOR . $file) && $file !== '.' && $file !== '..' && !is_dir($path . $dir . DIRECTORY_SEPARATOR . $file)) {
                    $dot = strrpos($file, '.') + 1;
                    $file_ext = strtolower(substr($file, $dot));
                    if (in_array($file_ext, $allowed_ext)) {
                        $fi[] = array('type' => 'file', 'dir' => $dir, 'file' => $file, 'ext' => $file_ext);
                    }
                }
            }
            $return = array_merge($dirs, $fi);
        }
    }
    echo json_encode($return);
    die();
}


// Disable all admin notice for pages belong to plugin
add_action('admin_print_scripts', function () {
    global $wp_filter;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
    if ((!empty($_GET['page']) && in_array($_GET['page'], array('wptm', 'wptm-config')))) {
        if (is_user_admin()) {
            if (isset($wp_filter['user_admin_notices'])) {
                unset($wp_filter['user_admin_notices']);
            }
        } elseif (isset($wp_filter['admin_notices'])) {
            unset($wp_filter['admin_notices']);
        }
        if (isset($wp_filter['all_admin_notices'])) {
            unset($wp_filter['all_admin_notices']);
        }
    }
});

// Gutenberg integration
if (!function_exists('wptm_gutenberg_integration')) {
    /**
     * WP Table Manager gutenberg integration
     *
     * @return void
     */
    function wptm_gutenberg_integration()
    {
        wp_enqueue_style(
            'wptm-table-style',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/css/wptm-blocks.css',
            array('wp-edit-blocks')
        );
        wp_enqueue_style(
            'wptm-front-style',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/site/assets/css/front.css',
            array('wp-edit-blocks')
        );
        wp_enqueue_style(
            'wptm-datatables-style',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/site/assets/DataTables/datatables.css',
            array('wp-edit-blocks')
        );
        wp_enqueue_script(
            'wptm-moment',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/site/assets/js/moment.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wptm-blocks')
        );
        wp_enqueue_script(
            'wptm-jdateformatparser',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/site/assets/js/moment-jdateformatparser.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wptm-blocks')
        );
        wp_enqueue_script(
            'wptm-datatables',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/site/assets/DataTables/datatables.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-data')
        );
        wp_enqueue_script(
            'wptm-blocks',
            WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/blocks/wptm-blocks.js',
            array('wp-i18n', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wptm-datatables')
        );

        wp_enqueue_script('wptm-chart', plugins_url('assets/js/Chart.js', __FILE__), array('wp-blocks'), WPTM_VERSION, true);

        wp_enqueue_script('wptm_dropchart', plugins_url('app/site/assets/js/dropchart.js', WPTM_PLUGIN_FILE), array('wp-blocks'), WPTM_VERSION, true);

        wp_localize_script('wptm-blocks', 'wptm_block', array(
            'ajax' => admin_url('admin-ajax.php?juwpfisadmin=false&action=Wptm&')
        ));
//        wp_enqueue_script(
//            'wptm-table-blocks',
//            WP_TABLE_MANAGER_PLUGIN_URL . 'app/site/assets/js/wptm_blocks_front.js',
//            array('wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wptm-blocks')
//        );
    }
}
add_action('enqueue_block_editor_assets', 'wptm_gutenberg_integration');

/**
 * Sets the extension and mime type for .webp files.
 *
 * @param array  $types    Check_filetype_and_ext File data array containing 'ext', 'type', and proper_filename' keys.
 * @param string $file     Full path to the file.
 * @param string $filename The name of the file.
 * @param array  $mimes    Key is the file extension with value as the mime type.
 *
 * @return array
 */
function my_file_and_ext_webp($types, $file, $filename, $mimes)
{
    if (false !== strpos($filename, '.webp')) {
        $types['ext'] = 'webp';
        $types['type'] = 'image/webp';
    }
    if (false !== strpos($filename, '.ogg')) {
        $types['ext'] = 'ogg';
        $types['type'] = 'audio/ogg';
    }
    if (false !== strpos($filename, '.woff')) {
        $types['ext'] = 'woff';
        $types['type'] = 'font/woff|application/font-woff|application/x-font-woff|application/octet-stream';
    }
    if (false !== strpos($filename, '.ttf')) {
        $types['ext'] = 'ttf';
        $types['type'] = 'font/ttf|application/font-ttf|application/x-font-ttf|application/octet-stream';
    }
    if (false !== strpos($filename, '.eot')) {
        $types['ext'] = 'eot';
        $types['type'] = 'font/eot|application/font-eot|application/x-font-eot|application/octet-stream';
    }
    if (false !== strpos($filename, '.otf')) {
        $types['ext'] = 'otf';
        $types['type'] = 'font/otf|application/font-otf|application/x-font-otf|application/octet-stream';
    }
    if (false !== strpos($filename, '.woff2')) {
        $types['ext'] = 'woff2';
        $types['type'] = 'font/woff2|application/octet-stream|font/x-woff2';
    }

    return $types;
}
add_filter('wp_check_filetype_and_ext', 'my_file_and_ext_webp', 10, 4);

/**
 * Custom upload font
 *
 * @param object|array $mimes Mimes
 *
 * @return array|object
 */
function my_mime_types($mimes)
{
    // $mimes['svg']   = 'image/svg+xml'; // insecure; do not use! Try https://wordpress.org/plugins/safe-svg/ if you need to upload SVGs
    $mimes['webp']  = 'image/webp';
    $mimes['ogg']   = 'audio/ogg';
    $mimes['woff']  = 'font/woff|application/font-woff|application/x-font-woff|application/octet-stream';
    $mimes['ttf']  = 'font/ttf|application/font-ttf|application/x-font-ttf|application/octet-stream';
    $mimes['eot']  = 'font/eot|application/font-eot|application/x-font-eot|application/octet-stream';
    $mimes['otf']  = 'font/otf|application/font-otf|application/x-font-otf|application/octet-stream';
    $mimes['woff2'] = 'font/woff2|application/octet-stream|font/x-woff2';
    return $mimes;
}

add_filter('upload_mimes', 'my_mime_types');