<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Filesystem;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

// Prohibit direct script loading
defined('ABSPATH') || die('No direct script access allowed!');

register_activation_hook(WPTM_PLUGIN_FILE, 'wptm_install');

register_uninstall_hook(WPTM_PLUGIN_FILE, 'wptm_uninstall');

if (!function_exists('wptm_install')) {
    /**
     * Function install
     *
     * @return void
     */
    function wptm_install()
    {
        if (is_multisite()) {
            $blogs = get_sites();
            foreach ($blogs as $blog) {
                switch_to_blog($blog->blog_id);
                _wptm_install();
                restore_current_blog();
            }
        } else {
            _wptm_install();
        }
    }
}

if (!function_exists('_wptm_install')) {
    /**
     * Function create database table and data of table
     *
     * @return void
     */
    function _wptm_install()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->show_errors();

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_categories (
        id int(11) NOT NULL AUTO_INCREMENT,
        parent_id int(10) unsigned NOT NULL DEFAULT \'0\',
        lft int(11) NOT NULL DEFAULT \'0\',
        rgt int(11) NOT NULL DEFAULT \'0\',
        level int(10) unsigned NOT NULL DEFAULT \'0\',
        title varchar(255) NOT NULL,
        published tinyint(1) NOT NULL DEFAULT \'0\',
        access int(1) NOT NULL DEFAULT \'1\',
        params text NOT NULL,
        themeparams text NOT NULL,
        created_time datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
        modified_time datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
        hits int(10) unsigned NOT NULL DEFAULT \'0\',
        PRIMARY KEY  (id),
        KEY idx_left_right (lft,rgt)
      ) ENGINE=InnoDB ;';

        dbDelta($sql);

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_tables (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        id_category int(11) NOT NULL,
                        title varchar(100) NOT NULL,
                        mysql_table_name varchar(50) DEFAULT NULL,
                        style mediumtext NOT NULL,
                        mysql_query text DEFAULT NULL,
                        css text NOT NULL,
                        hash VARCHAR( 32 ) NOT NULL,
                        params text NOT NULL,
                        created_time datetime NOT NULL,
                        modified_time datetime NOT NULL,
                        author int(11) NOT NULL,
                        position int(11) NOT NULL,
                        type char(50) DEFAULT \'html\',
                        PRIMARY KEY  (id), 
                        KEY idx_category (id_category)
      ) ' . $charset_collate . ';';
        dbDelta($sql);

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_range_styles (
			id int(11) NOT NULL AUTO_INCREMENT,
		    id_table int(11) NOT NULL,
		    row_start int(11) NOT NULL,
		    row_end int(11) NOT NULL,
		    col_start int(11) NOT NULL,
		    col_end int(11) NOT NULL,
		    style text NOT NULL,
		    PRIMARY KEY  (id)
      ) ' . $charset_collate . ';';
        dbDelta($sql);

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_charts (
			id int(11) NOT NULL AUTO_INCREMENT,
		      id_table int(11) NOT NULL,
		      title varchar(100) NOT NULL,
		      datas mediumtext NOT NULL,
		      type mediumtext NOT NULL,
		      config text NOT NULL,
		      hash varchar(32) NOT NULL,
		      params text NOT NULL,
		      created_time datetime NOT NULL,
		      modified_time datetime NOT NULL,
		      author int(11) NOT NULL,
		      PRIMARY KEY  (id) ,
                      KEY idx_table (id_table)
      ) ' . $charset_collate . ';';
        dbDelta($sql);

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_charttypes (
			id int(11) NOT NULL AUTO_INCREMENT,
		      name varchar(50) NOT NULL,
		      config mediumtext NOT NULL,
		      extra text NOT NULL,
		      image text NOT NULL,
		      ordering int(11) NOT NULL,
		      PRIMARY KEY  (id) ,
                      KEY idx_id (id)
      ) ' . $charset_collate . ';';
        dbDelta($sql);

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_table_options  (
                id int(11) NOT NULL AUTO_INCREMENT,
                id_table int(11) NOT NULL,
                option_name varchar(255) NOT NULL,
                option_value text NOT NULL,
                PRIMARY KEY  (id)
              ) ENGINE=InnoDB ;';
        dbDelta($sql);

        wptm_update_styles($wpdb);

        if (!$wpdb->get_var('SELECT COUNT(*) FROM `' . $wpdb->prefix . 'wptm_tables`')) {
            $id_user = get_current_user_id();

            if (!$wpdb->get_var('SELECT COUNT(*) FROM `' . $wpdb->prefix . 'wptm_categories`')) {
                $wpdb->query('INSERT INTO `' . $wpdb->prefix . "wptm_categories` (`id`, `parent_id`, `lft`, `rgt`, `level`, `title`, `published`, `access`, `params`, `created_time`, `modified_time`, `hits`) VALUES(1, 0, 1, 4, 0, 'ROOT', 0, '1', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0);");
                $wpdb->query($wpdb->prepare('INSERT INTO `' . $wpdb->prefix . "wptm_categories` (`id`, `parent_id`, `lft`, `rgt`, `level`, `title`, `published`, `access`, `params`, `created_time`, `modified_time`, `hits`) VALUES (2, 1, 2, 3, 1, 'Category', 0, '1', '{\"role\":{\"0\":\" %d \"}}', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0);", $id_user));
                $new_cat = '2';
            } else {
                $new_cat = $wpdb->get_var('SELECT id FROM `' . $wpdb->prefix . 'wptm_categories` WHERE level >0');
            }

            Application::getInstance('wptm', WPTM_PLUGIN_FILE, 'admin');
            $model = Model::getInstance('style');
            $data_theme  = $model->getItem(1);

            $model = Model::getInstance('table');
            $item  = $model->add($new_cat, false, 9);

            $model->addThemeToTable($item[0], $data_theme);
        }

        if (!$wpdb->get_var('SELECT COUNT(*) FROM `' . $wpdb->prefix . 'wptm_charttypes`')) {
            $wpdb->query('INSERT INTO `' . $wpdb->prefix . 'wptm_charttypes` (`id`, `name`, `config`, `extra`, `image`, `ordering`) VALUES
				(1, \'Line\', \'{   \r\n    "scaleShowGridLines" : false,\r\n  "scaleGridLineColor" : "rgba(0,0,0,.05)"\r\n   \r\n}\', \'\', \'line.png\', 0),
				(2, \'Bar\', \'{"scaleBeginAtZero": true,\r\n "scaleShowGridLines" : true,\r\n "scaleGridLineColor" : "rgba(0,0,0,.05)",\r\n "scaleGridLineWidth":1,\r\n "barShowStroke" : true,\r\n "barStrokeWidth": 2,\r\n "barValueSpacing": 5,\r\n "barDatasetSpacing":10\r\n}\', \'\', \'bar.png\', 0),
				(3, \'Radar\', \'\', \'\', \'radar.png\', 0),
				(4, \'PolarArea\', \'\', \'\', \'polar_area.png\', 0),
				(5, \'Pie\', \'\', \'\', \'pie.png\', 0),
				(6, \'Doughnut\', \'\', \'\', \'doughnut.png\', 0)');
        }

        add_option('wptm_version', '3.9.4');

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
                if ($role->name === 'administrator') {
                    $role->add_cap('wptm_access_database_table');
                }
            }
        }
    }
}

if (!function_exists('wptm_update_styles')) {
    /**
     * Function update wptm styles db
     *
     * @param object $wpdb  Object wordPress database abstraction object.
     * @param object $theme List theme will updated
     *
     * @return boolean|void
     */
    function wptm_update_styles($wpdb, $theme = array())
    {
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $query = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wptm_styles';
        if ($wpdb->query($query) === false) {
            return false;
        }

        $sql = 'CREATE TABLE ' . $wpdb->prefix . 'wptm_styles (id int(11) NOT NULL AUTO_INCREMENT, image text NOT NULL, data mediumtext, cols_data text, rows_data text, styles mediumtext, params mediumtext, PRIMARY KEY (id)) ' . $charset_collate . ';';
        maybe_create_table($wpdb->prefix . 'wptm_styles', $sql);
        $app = Application::getInstance('Wptm', __FILE__);

//        if (!$wpdb->get_var('SELECT COUNT(*) FROM `' . $wpdb->prefix . 'wptm_styles`')) {
        $json = file_get_contents($app->getPath() . DIRECTORY_SEPARATOR . 'theme.json');
        $json_data = json_decode($json);

        $image_url = $app->getPath() . DIRECTORY_SEPARATOR . 'admin'
            . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images'
            . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;
        $option_update_theme = get_option('wptm_update_theme', null);
        if ($option_update_theme !== null) {
            $option_update_theme = json_decode($option_update_theme, true);
        } else {
            $option_update_theme = array();
        }

        foreach ($json_data as $key => $data) {
            $data_cell = json_encode($data->data);
            $data_params = json_encode($data->params);
            $update = false;

            if (count($theme) > 0) {
                if (in_array($key, $theme)) {
                    $update = true;
                }
            } else {
                $update = true;
            }

            if ($update) {
                if (isset($data->images)) {
                    foreach ($data->images as $key1 => $name) {
                        if (isset($option_update_theme[$name]) && file_exists($option_update_theme[$name][0])) {
                            $newData = $option_update_theme[$name];
                        } else {
                            $newData = WptmTablesHelper::uploadImage($name, $image_url . $name);
                            $option_update_theme[$name] = $newData;
                        }
                        $list_img[$name] = $newData;

                        $data_cell = str_replace('wptm_image_' . $key1, $newData[0], $data_cell);
                        $data_params = str_replace('wptm_image_' . $key1, $newData[0], $data_params);
                        $data_cell = str_replace('wptm_image_id_' . $key1, 'wp-image-' . $newData[1], $data_cell);
                    }
                }

                if ($wpdb->get_var('SELECT COUNT(*) FROM `' . $wpdb->prefix . 'wptm_styles` WHERE id = ' . ((int)$key + 1))) {
                    $wpdb->query(
                        $wpdb->prepare(
                            'UPDATE ' . $wpdb->prefix . 'wptm_styles SET data = %s, cols_data = %s, rows_data = %s, styles = %s, params = %s, image = %s WHERE id = %d',
                            $data_cell,
                            json_encode($data->cols),
                            json_encode($data->rows),
                            json_encode($data->range_styles),
                            $data_params,
                            WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/styles/theme' . $key . '.png',
                            (int)$key + 1
                        )
                    );
                } else {
                    $wpdb->query(
                        $wpdb->prepare(
                            'INSERT INTO ' . $wpdb->prefix . 'wptm_styles (data, cols_data, rows_data, styles, params, image) VALUES ( %s,%s,%s,%s,%s,%s)',
                            $data_cell,
                            json_encode($data->cols),
                            json_encode($data->rows),
                            json_encode($data->range_styles),
                            $data_params,
                            WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/styles/theme' . $key . '.png'
                        )
                    );
                }
            }
        }
        update_option('wptm_update_theme', json_encode($option_update_theme));
//        }
    }
}

if (!function_exists('wptm_get_list_html_table')) {
    /**
     * Function get list html_table in database
     *
     * @return array|boolean
     */
    function wptm_get_list_html_table()
    {
        global $wpdb;

        $query = 'SELECT t.id, t.mysql_table_name FROM ' . $wpdb->prefix . 'wptm_tables as t WHERE t.mysql_table_name IS NOT NULL ORDER BY t.id ASC';

        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        $result = $wpdb->query($query);

        if ($result === false) {
            return false;
        }
        //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- No variable from user in the query
        return stripslashes_deep($wpdb->get_results($query, ARRAY_A));
    }
}

if (!function_exists('wptm_uninstall')) {
    /**
     * Function uninstall wptm
     *
     * @return boolean
     */
    function wptm_uninstall()
    {
        global $wpdb;
        $app = Application::getInstance('Wptm', __FILE__);
        $app->init();
        require_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'wptmBase.php';
        $modelConfig = Model::getInstance('config');
        $params      = $modelConfig->getConfig();

        if (WptmBase::loadValue($params, 'uninstall_delete_files', 0)) {
            $queries   = array();

            //delete tables list
            $listTable = wptm_get_list_html_table();
            if ($listTable !== false) {
                $count = count($listTable);
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($listTable[$i]['mysql_table_name'])) {
                        $queries[] = 'DROP TABLE IF EXISTS ' . $listTable[$i]['mysql_table_name'];
                    }
                }
            }

            //delete wptm table database
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_categories';
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_tables';
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_range_styles';
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_styles';
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_charts';
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_charttypes';
            $queries[] = 'DROP TABLE ' . $wpdb->prefix . 'wptm_table_options';

            foreach ($queries as $query) {
                //phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared -- sql already escaped
                if ($wpdb->query($query) === false) {
                    return false;
                }
            }

            delete_option('wptm_version');
            delete_option('wptm_tables_convert');
            delete_option('wptm_list_syn_google');
            delete_option('wptm_updated_tables');

            Filesystem::rmdir(WptmBase::getFilesPath());
        }

        $timestamp = wp_next_scheduled('wptmSchedules');
        wp_unschedule_event($timestamp, 'wptmSchedules');
    }
}

// Deactivate light version
if (!function_exists('wptm_deactivate_light_ver')) {
    /**
     * Funtion wptm_deactivate_light_ver
     *
     * @return void
     */
    function wptm_deactivate_light_ver()
    {
        // Check if light version is installed or not
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $all_plugins = get_plugins();
        if (array_key_exists('wp-table-manager-light/wp-table-manager-light.php', $all_plugins)) {
            // If installed and activated, deactivate it
            if (is_plugin_active('wp-table-manager-light/wp-table-manager-light.php')) {
                deactivate_plugins('wp-table-manager-light/wp-table-manager-light.php');
            }
        }
    }
}
