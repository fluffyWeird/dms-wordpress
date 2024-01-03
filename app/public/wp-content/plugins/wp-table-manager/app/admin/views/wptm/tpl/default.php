<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0W
 */

use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

// No direct access.
defined('ABSPATH') || die();

if (!current_user_can('wptm_access_category')) {
    wp_die(esc_attr__("You don't have permission to view this page", 'wptm'));
}

$currentUser = new WP_User($this->idUser);
$userRoles = (array)$currentUser->roles;
$userRoless = array_values($userRoles);
$userRoles = $userRoless[0];
if (in_array('administrator', $userRoless)) {
    $userRoles = 'administrator';
}

//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- to assign values to $wp_roles
$wp_roles = new WP_Roles();
// list role users
$roles = $wp_roles->role_objects;
$roles_list = $wp_roles->get_names();
$user_role_wptm = WptmTablesHelper::userRole($roles[$userRoles]->capabilities);
$countUserRoless = count($userRoless);

if ($countUserRoless > 1) {
    $role_wptms = array('wptm_access_category',
        'wptm_access_database_table',
        'wptm_create_category',
        'wptm_create_tables',
        'wptm_delete_category',
        'wptm_delete_tables',
        'wptm_access_category',
        'wptm_edit_own_category',
        'wptm_edit_own_tables',
        'wptm_edit_tables');
    for ($role_length = 1; $role_length < $countUserRoless; $role_length++) {
        if (!empty($roles[$userRoless[$role_length]]->capabilities)) {
            $user_role_wptm1 = WptmTablesHelper::userRole($roles[$userRoless[$role_length]]->capabilities);
            foreach ($role_wptms as $role_wptm) {
                if (isset($user_role_wptm1[$role_wptm]) && $user_role_wptm1[$role_wptm] === true) {
                    $user_role_wptm[$role_wptm] = true;
                    unset($role_wptms[array_search($role_wptm, $role_wptms)]);
                }
            }
        }
    }
}

if (current_user_can('administrator')) {
    $user_role_wptm['change_author'] = true;
} else {
    $user_role_wptm['change_author'] = false;
}

if ($this->caninsert) {
    global $hook_suffix;
    _wp_admin_html_begin();
    do_action('admin_enqueue_scripts', $hook_suffix);
    do_action('admin_print_scripts-$hook_suffix');
    do_action('admin_print_scripts');
}
$auto_save = isset($this->params['enable_autosave']) && (string)$this->params['enable_autosave'] === '0' ? false : true;
$open_table = isset($this->params['open_table']) && (string)$this->params['open_table'] === '0' ? '0' : '1';
$wptm_db_table = admin_url('admin.php?page=wptm&type=dbtable&');
$wptm_list_url = admin_url('admin.php?page=wptm&');

$app = Application::getInstance('Wptm');

//if (false) {
if ($this->convert !== null && $this->convert > -1) {
    ?>
    <div class="over_popup loadding convert_tables">
        <div class="wptm_popup">
            <h3><?php esc_attr_e('WP Table Manager database update required ', 'wptm'); ?></h3>
            <span><?php esc_attr_e('The database update process runs in the background and may take a little while, so please be patient.', 'wptm'); ?></span>
        </div>
    </div>
    <script type="text/javascript">
        wptm_convert_data = {'id': <?php echo esc_attr(isset($this->convert) ? $this->convert : 0);?>};
    </script>
    <?php
}
?>
    <script type="text/javascript">
        ajaxurl = '<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>';
        wptm_db_table = '<?php echo isset($wptm_db_table) ? esc_url_raw($wptm_db_table) : ''; ?>';
        adminurl = '<?php echo esc_url_raw(admin_url()); ?>';
        wptm_ajaxurl = "<?php echo esc_url_raw(admin_url('admin-ajax.php?action=Wptm&')); ?>";
        wptm_ajaxurl_site = "<?php echo esc_url_raw(admin_url('admin-ajax.php?juwpfisadmin=false&action=Wptm&')); ?>";
        wptm_admin_asset = '<?php echo esc_url_raw(WP_TABLE_MANAGER_PLUGIN_URL . '/app/admin/assets'); ?>';
        wptm_dir = "<?php echo esc_url_raw(WP_TABLE_MANAGER_PLUGIN_URL); ?>";
        wptm_user = "<?php echo esc_attr($currentUser->data->user_login); ?>";
        wptm_user_id = "<?php echo esc_attr($currentUser->data->ID); ?>";
        wptm_table_url = '<?php echo isset($wptm_list_url) ? esc_url_raw($wptm_list_url) : ''; ?>';
        wptm_open_table = '<?php echo esc_attr($open_table); ?>';
        canInsert = <?php echo $this->caninsert ? 1 : 0; ?>;
        wptm_administrator = <?php echo current_user_can('administrator') ? 1 : 0; ?>;
        wptm_userRoles = '<?php echo esc_attr(strtoupper($userRoles)); ?>';
        <?php if ((int)$this->id_charts > 0) : ?>
        chart_active = <?php echo esc_attr($this->id_charts); ?>;
        <?php else : ?>
        chart_active = 0;
        <?php endif; ?>
        wptm_listFont = [];
        <?php if (count($this->listsFont) > 0) :
            foreach ($this->listsFont as $listFont) :
                ?>
                wptm_listFont.push('<?php echo esc_attr($listFont); ?>');
            <?php endforeach; ?>
        <?php endif; ?>
        wptm_listsLocalFont = [];
        <?php if (count($this->listsLocalFont) > 0) :
            foreach ($this->listsLocalFont as $listsLocalFont) :
                ?>
                wptm_listsLocalFont.push('<?php echo esc_attr($listsLocalFont); ?>');
            <?php endforeach; ?>
        <?php endif; ?>
    </script>
<?php
if (isset($this->id_table)) :
    if (empty($this->table)) {
        wp_die(esc_attr__('Table not exist', 'wptm'));
    }

    if ($this->table->type === 'mysql' && !current_user_can('wptm_access_database_table')) {
        wp_die(esc_attr__("You don't have permission to view this page", 'wptm'));
    }

    if (empty($user_role_wptm['wptm_edit_tables']) && (empty($this->table->author) || (int)$this->table->author !== (int)$this->idUser)) {
        wp_die(esc_attr__("You don't have permission to view this page", 'wptm'));
    }

    //get syn_hash for table
    $list_syn_google = get_option('wptm_list_syn_google', '');
    if ($list_syn_google === '') {
        $list_syn_google = array();
    } else {
        $list_syn_google = json_decode($list_syn_google, true);
    }

    if (!empty($list_syn_google['table' . $this->id_table]) && $list_syn_google['table' . $this->id_table] !== '') {
        $this->syn_hash = $list_syn_google['table' . $this->id_table];
    } else {
        $this->syn_hash = '';
    }

    $alone = '';
    $editor_id = 'wptmditor';
    $editor_args = array(
        'tabfocus_elements' => 'content-html,save-post',
        'quicktags' => true,
        'media_buttons' => false,
        'editor_height' => 400,
        'tinymce' => array(
            'resize' => true,
            'wp_autoresize_on' => true,
            'add_unload_trigger' => false
        )
    );
    wp_editor('<p></p><p></p>', $editor_id, $editor_args);


    $editor_args1 = $editor_args;
    // $editor_args1['editor_height'] = '300' ;
    $editor_args1['quicktags'] = false;
    $editor_args1['tinymce'] = array(
        'setup' => 'function (ed) {                               
                               ed.on("keyup", function (e) {
                                  // ed.save();
                                   //wptm_tooltipChange();
                                
                                });
                                ed.on("change", function(e) {
                                   // ed.save();
                                    //wptm_tooltipChange();
                                });
                            }',
    );
    wp_editor('', 'wptm_tooltip', $editor_args1);
    $date_formats = !isset($this->params['date_formats']) ? 'Y-m-d' : $this->params['date_formats'];
    $date_formats = str_replace('\\', '\\\\', $date_formats);
    $symbol_position = !isset($this->params['symbol_position']) ? 0 : $this->params['symbol_position'];
    $currency_sym = !isset($this->params['currency_sym']) ? '$' : $this->params['currency_sym'];
    $decimal_sym = !isset($this->params['decimal_sym']) ? '.' : $this->params['decimal_sym'];
    $decimal_count = !isset($this->params['decimal_count']) ? '0' : $this->params['decimal_count'];
    $thousand_sym = !isset($this->params['thousand_sym']) ? ',' : $this->params['thousand_sym'];
    wp_nonce_field('option_nonce', 'option_nonce');
    ?>
    <style>
        <?php
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Render css
        echo stripslashes_deep($this->localFont);
        ?>
        /*@font-face {*/
        /*    font-family: "xxxxx4";*/
        /*    font-fallback: "a4";*/
        /*    font_weight: normal;*/
        /*    font-style: normal;*/
        /*    src: url(http://localhost/wordpress2/wp-content/uploads/2022/02/O4ZSFGfvnxFiCA3i30dhhgcC_QDsqy3yixWdaNq343W7IXtV.54.woff2) format('woff2');*/
        /*}*/
        #wpwrap, body.sticky-menu {
            background-color: #ffffff;
        }

        #wp-wptmditor-wrap, #wp-wptm_tooltip-wrap {
            display: none
        }
        #over_loadding_open_chart {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            min-height: 360px;
            z-index: 998;
            background-color: #ffffff;
            opacity: 1;
        }
        #over_loadding_open_chart:after {
            content: "";
            width: 90px;
            height: 90px;
            top: 50%;
            position: absolute;
            left: 50%;
            background-image: url("<?php echo esc_url_raw(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/'); ?>loadingfile.svg");
            background-size: auto 100%;
            background-repeat: no-repeat;
            transform: translateY(-50%);
            color: #FFF;
        }
        .tooltip.copy_text:hover:after {
            content: '<?php esc_attr_e('Copy', 'wptm')?>';
        }
    </style>
    <script type="text/javascript">
        var Wptm = {};
        Wptm.author = '<?php echo !empty($this->table->author) ? esc_attr($this->table->author) : 0; ?>';
        Wptm.syn_hash = '<?php echo esc_attr($this->syn_hash); ?>';
        if (typeof (addLoadEvent) === 'undefined') {
            addLoadEvent = function (func) {
                if (typeof jQuery != "undefined") jQuery(document).ready(func); else if (typeof wpOnload != 'function') {
                    wpOnload = func;
                } else {
                    var oldonload = wpOnload;
                    wpOnload = function () {
                        oldonload();
                        func();
                    }
                }
            };
        }
        Wptm.table_editing = '<?php echo esc_attr($this->table->table_editing); ?>';
        <?php
        $error_message_read_file = get_option('wptm_error_message_read_file', '');
        if ($error_message_read_file !== '') {
            $wptm_error_message = json_decode($error_message_read_file, true);

            if (!empty($wptm_error_message) && isset($wptm_error_message[$this->id_table])) {
                ?>
                Wptm.wptm_error_message_read_file = '<?php echo esc_attr($wptm_error_message[$this->id_table]); ?>';
                <?php
                $wptm_error_message[$this->id_table] = '';
            }
            update_option('wptm_error_message_read_file', json_encode(!empty($wptm_error_message) ? $wptm_error_message : array()));
        }?>
        Wptm.table_editing = '<?php echo esc_attr($this->table->table_editing); ?>';
    </script>
    <style id="wptm_add_css_lock_columns"></style>
    <div id="mybootstrap" class="wptm-page <?php echo esc_html($alone); ?>">
        <div id="over_loadding_open_chart" class="loadding" style="display: block;"></div>
        <div id="pwrapper">
            <div id="wptm-toolbars">
                <div id="primary_toolbars">
                    <a title="<?php esc_attr_e('Wp Table Manager home', 'wptm') ?>" href="<?php
                    $url_back = admin_url('admin.php?page=wptm');
                    if ($this->caninsert) {
                        $url_back .= '&noheader=1&caninsert=1';
                    }
                    //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The inner variables were esc
                    echo $url_back; ?>">
                        <?php if ($this->table->type === 'mysql') : ?>
                            <i class="type_table mysql_type"></i>
                        <?php else : ?>
                            <i class="type_table"></i>
                        <?php endif; ?>
                    </a>
                    <!-- Table text -->
                    <span class="title wptm_name_edit" contentEditable="true"><?php echo esc_attr($this->table->title); ?></span>
                    <!-- Menu option table -->
                    <ul class="nav " id="table-setting">
                        <li class="table-menu">
                            <a><?php esc_attr_e('Table', 'wptm'); ?></a>
                            <ul>
                                <li>
                                    <a class="new_table_menu"><?php esc_attr_e('New Table', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option rename_menu"
                                       name="rename_menu"><?php esc_attr_e('Rename', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option cell_insertion_shortcode" name="cell_insertion_shortcode">
                                        <?php esc_attr_e('Cell insertion shortcode', 'wptm'); ?></a>
                                </li>
                                <?php if (isset($this->params['enable_autosave']) && (string)$this->params['enable_autosave'] === '0') : ?>
                                    <li>
                                        <a class="table_option save_menu"
                                           name="save_menu"><?php esc_attr_e('Save', 'wptm'); ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if (isset($this->params['enable_autosave']) && (string)$this->params['enable_import_excel'] === '1') : ?>
                                    <!--                                    --><?php //if ($this->table->type === 'mysql') : ?>
                                    <!--                                        <li class="no_active">-->
                                    <!--                                    --><?php //else: ?>
                                    <li>
                                        <!--                                    --><?php //endif; ?>
                                        <a class="table_option export_menu"
                                           name="export_menu"><?php esc_attr_e('Export table', 'wptm'); ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if (!$this->caninsert) :?>
                                <li>
                                    <a class="table_option source_menu"
                                       name="source_menu"><?php esc_attr_e('Data Source', 'wptm'); ?></a>
                                </li>
                                <?php endif; ?>
                                <li class="has_sub_menu">
                                    <a class="table_option formats_menu"><?php esc_attr_e('Format options', 'wptm'); ?></a>
                                    <ul class="sub-menu">
                                        <li>
                                            <a class="table_option date_menu" name="date_menu">
                                                <?php esc_attr_e('Date time', 'wptm'); ?></a>
                                        </li>
                                        <li>
                                            <a class="table_option curency_menu" name="curency_menu">
                                                <?php esc_attr_e('Currencies', 'wptm'); ?></a>
                                        </li>
                                        <li>
                                            <a class="table_option decimal_menu" name="decimal_menu">
                                                <?php esc_attr_e('Decimal and number', 'wptm'); ?></a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="table_option access_menu"
                                       name="access_menu"><?php esc_attr_e('Table access', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option download_button_menu" name="download_button_menu">
                                        <?php esc_attr_e('Export Excel button', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option print_button_menu" name="print_button_menu">
                                        <?php esc_attr_e('Print button', 'wptm'); ?></a>
                                </li>
                            </ul>
                        </li>
                        <li class="theme-menu">
                            <a><?php esc_attr_e('Theme', 'wptm'); ?></a>
                            <ul>
                                <?php if ($this->table->type === 'mysql') : ?>
                                <li class="no_active">
                                <?php else : ?>
                                <li>
                                <?php endif; ?>
                                    <a class="table_option select_theme_menu"
                                       name="select_theme_menu"><?php esc_attr_e('Theme selection', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option alternate_menu"
                                       name="alternate_menu"><?php esc_attr_e('Alternate colors', 'wptm'); ?></a>
                                </li>
                            </ul>
                        </li>
                        <li class="format-menu">
                            <a><?php esc_attr_e('Format', 'wptm'); ?></a>
                            <ul>
                                <li class="has_sub_menu">
                                    <a class="table_option column_size_menu">
                                        <?php esc_attr_e('Apply column/line size', 'wptm'); ?></a>
                                    <ul class="sub-menu">
                                        <li><a class="table_option"
                                               name="resize_column"><?php esc_attr_e('Resize column', 'wptm'); ?></a>
                                        </li>
                                        <li><a class="table_option"
                                               name="resize_row"><?php esc_attr_e('Resize row', 'wptm'); ?></a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="table_option lock_ranger_cells" name="lock_ranger_cells">
                                        <?php esc_attr_e('Protect range', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option form_filter" name="form_filter_table">
                                        <?php esc_attr_e('Sort and filters', 'wptm'); ?></a>
                                </li>
                                <li class="has_sub_menu">
                                    <a class="table_option align_menu" name="align_menu">
                                        <?php esc_attr_e('Table align', 'wptm'); ?></a>
                                    <ul class="sub-menu">
                                        <li name="center"><a><?php esc_attr_e('Center', 'wptm'); ?></a></li>
                                        <li name="left"><a><?php esc_attr_e('Left', 'wptm'); ?></a></li>
                                        <li name="right"><a><?php esc_attr_e('Right', 'wptm'); ?></a></li>
                                        <li name="none"><a><?php esc_attr_e('None', 'wptm'); ?></a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a class="table_option responsive_menu" name="responsive_menu">
                                        <?php esc_attr_e('Responsive options', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option pagination_menu" name="pagination_menu">
                                        <?php esc_attr_e('Pagination', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option date_menu_cell" name="date_menu_cell">
                                        <?php esc_attr_e('Date time', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option curency_menu_cell" name="curency_menu_cell">
                                        <?php esc_attr_e('Currencies', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option decimal_menu_cell" name="decimal_menu_cell">
                                        <?php esc_attr_e('Number', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="custom-menu" name="custom_menu" href="#wptm_customCSS">
                                        <?php esc_attr_e('Custom CSS', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option header_option_menu" name="header_option_menu">
                                        <?php esc_attr_e('Table header', 'wptm'); ?></a>
                                </li>
                                <?php if ($this->table->type === 'mysql') : ?>
                                <li class="no_active">
                                <?php else : ?>
                                <li>
                                <?php endif; ?>
                                    <a class="table_option column_type_menu" name="column_type_menu">
                                        <?php esc_attr_e('Columns types', 'wptm'); ?></a>
                                </li>
                            </ul>
                        </li>
                        <li class="import-menu">
                            <a><?php esc_attr_e('Import & Sync', 'wptm'); ?></a>
                            <ul>
                            <?php if ($this->table->type === 'mysql') : ?>
                            <li class="no_active">
                            <?php else : ?>
                                <li>
                            <?php endif; ?>
                                    <a class="table_option google_sheets_menu" name="google_sheets_menu">
                                        <?php esc_attr_e('Google Sheets', 'wptm'); ?></a>
                                </li>

                                <?php if ($this->table->type === 'mysql') : ?>
                                <li class="no_active">
                                <?php else : ?>
                                <li>
                                <?php endif; ?>
                                    <a class="table_option onedrive_menu" name="onedrive_menu">
                                        <?php esc_attr_e('OneDrive Excel', 'wptm'); ?></a>
                                </li>

                                <?php if ($this->table->type === 'mysql') : ?>
                                <li class="no_active">
                                <?php else : ?>
                                <li>
                                <?php endif; ?>
                                    <a class="table_option synchronization_menu" name="synchronization_menu">
                                        <?php esc_attr_e('Excel file', 'wptm'); ?></a>
                                </li>

                                <?php if ($this->table->type === 'mysql') : ?>
                                <li class="no_active">
                                <?php else : ?>
                                <li>
                                <?php endif; ?>
                                    <a class="table_option csv_menu" name="csv_menu">
                                        <?php esc_attr_e('CSV file', 'wptm'); ?></a>
                                </li>
                            </ul>
                        </li>
                        <li class="chart-menu">
                            <a><?php esc_attr_e('Chart', 'wptm'); ?></a>
                            <ul>
                                <li>
                                    <a class="table_option new_chart_menu" name="new_chart_menu">
                                        <?php esc_attr_e('Create chart from data', 'wptm'); ?></a>
                                </li>
                                <li>
                                    <a class="table_option view_chart_menu"
                                       name="view_chart_menu"><?php esc_attr_e('View existing charts', 'wptm'); ?></a>
                                </li>
                            </ul>
                        </li>
                        <?php if (!$auto_save) { ?>
                            <li class="save-changes-menu">
                                <a class="table_option save_menu tip" name="save_menu"
                                   title="<?php esc_attr_e('Save', 'wptm'); ?>">
                                    <i class="save_icon material-icons">save</i>
                                </a>
                            </li>
                        <?php } ?>
                        <li class="search-menu wptm_highlight">
                            <i class="search-open-btn search_icon material-icons">search</i>
                            <div class="search-box">
                                <i class="search_table search_icon material-icons">search</i>
                                <input name="filter[search_drp]" id="dp-form-search" type="input"
                                       style="border: 1px solid #bfcddc;">
                                <i class="material-icons reload_search">clear</i>
                            </div>
                        </li>
                        <?php if (!empty($this->table->table_editing) && (int)$this->table->table_editing === 1) : ?>
                            <li class="warnning_edit_db_table" style="box-shadow: none !important;"><span><?php esc_attr_e('Be Careful! The change to tables in the database can\'t be reverted', 'wptm');?></span></li>
                        <?php endif; ?>
                        <li class="wptm_saving wptm_highlight wptm_hiden">
                            <span><?php esc_attr_e('Saving...', 'wptm'); ?></span>
                        </li>
                        <li class="wptm_save_error wptm_hiden">
                            <span><?php esc_attr_e('Error! You have an error in the date calculation.', 'wptm'); ?></span>
                        </li>
                        <li class="ajax_loading wptm_hiden">
                        </li>
                    </ul>
                    <!-- cell style -->
                    <ul class="nav " id="setting-cells">
                        <li class="cells_option ml-2 mr-2 tip tooltipstered" data-toggle="tab" title="<?php esc_attr_e('Undo', 'wptm'); ?>">
                            <a id="undo_cell" data-toggle="tab" class="cell_option tip tooltipstered no_active" name="undo_cell">
                                <i class="material-icons undo"></i>
                            </a>
                        </li>
                        <li class="cells_option ml-2 mr-2 tip tooltipstered" data-toggle="tab" title="<?php esc_attr_e('Redo', 'wptm'); ?>">
                            <a id="redo_cell" data-toggle="tab" class="cell_option tip tooltipstered no_active" name="redo_cell">
                                <i class="material-icons redo"></i>
                            </a>
                        </li>
                        <li class="cells_option font_family">
                            <span class="wptm_select_box_before">Inherited</span>
                            <ul class="wptm_select_box chzn-select observeChanges cell_option tip"
                                title="<?php esc_attr_e('Font', 'wptm') ?>" id="cell_font_family"
                                name="cell_font_family">
                                <li data-value="inherit">Inherited</li>
                                <li data-value="Arial">Arial</li>
                                <li data-value="Arial Black">Arial Black</li>
                                <li data-value="Comic Sans MS">Comic Sans MS</li>
                                <li data-value="Courier New">Courier New</li>
                                <li data-value="Georgia">Georgia</li>
                                <li data-value="Impact">Impact</li>
                                <li data-value="Times New Roman">Times New Roman</li>
                                <li data-value="Trebuchet MS">Trebuchet MS</li>
                                <li data-value="Verdana">Verdana</li>
                                <?php if (count($this->listsFont) > 0) :
                                    foreach ($this->listsFont as $listFont) :
                                        ?>
                                        <li data-value="<?php echo esc_attr($listFont); ?>"><?php echo esc_attr($listFont); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (count($this->listsLocalFont) > 0) :
                                    foreach ($this->listsLocalFont as $listsLocalFont) :
                                        ?>
                                        <li class="local-font" data-value="<?php echo esc_attr($listsLocalFont); ?>"><?php echo esc_attr($listsLocalFont); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="cells_option right_border font_size">
                            <input type="number" class="chzn-select observeChanges cell_option tip" min="1"
                                   title="<?php esc_attr_e('Font size', 'wptm') ?>" id="cell_font_size"
                                   name="cell_font_size">
                        </li>
                        <li class="cells_option right_border zoom_size">
                            <select id="cell_zoom_size" class="popup_select cell_option tip" title="<?php esc_attr_e('Zoom size', 'wptm') ?>" style="font-size: 15px;padding-right: 20px;height: 35px">
                                <option value="0.5"><?php esc_attr_e('50%', 'wptm'); ?></option>
                                <option value="0.75"><?php esc_attr_e('75%', 'wptm'); ?></option>
                                <option value="0.9"><?php esc_attr_e('90%', 'wptm'); ?></option>
                                <option value="1" selected><?php esc_attr_e('100%', 'wptm'); ?></option>
                                <option value="1.25"><?php esc_attr_e('125%', 'wptm'); ?></option>
                                <option value="1.5"><?php esc_attr_e('150%', 'wptm'); ?></option>
                                <option value="2"><?php esc_attr_e('200%', 'wptm'); ?></option>
                            </select>
                        </li>
                        <li class="cells_option ml-2 mr-2">
                            <a id="cell_format_bold" data-toggle="tab" class="cell_option cell_format_bold tip"
                               name="cell_format_bold" title="<?php esc_attr_e('Bold', 'wptm') ?>">
                                <i class="material-icons">format_bold</i>
                            </a>
                        </li>
                        <li class="cells_option ml-2 mr-2">
                            <a id="cell_format_underlined" data-toggle="tab"
                               class="cell_option cell_format_underlined tip"
                               title="<?php esc_attr_e('Underline', 'wptm') ?>"
                               name="cell_font_underline">
                                <i class="material-icons">
                                    format_underlined
                                </i>
                            </a>
                        </li>
                        <li class="cells_option ml-2 mr-2">
                            <a id="cell_format_italic" data-toggle="tab" class="cell_option cell_format_italic tip"
                               name="cell_font_italic" title="<?php esc_attr_e('Italic', 'wptm') ?>">
                                <i class="material-icons">
                                    format_italic
                                </i>
                            </a>
                        </li>
                        <li class="cells_option ml-2 mr-2">
                            <a id="cell_format_strikethrough" data-toggle="tab" class="cell_option cell_format_strikethrough tip"
                               name="cell_format_strikethrough" title="<?php esc_attr_e('Strikethrough', 'wptm') ?>">
                                <i class="material-icons"></i>
                            </a>
                        </li>
                        <li class="cells_option background_color position_relative ml-2 mr-2"
                            title="<?php esc_attr_e('Background cell color', 'wptm') ?>">
                            <input id="cell_background_color" type="text" value="#ffffff" data-show-opacity="true"
                                   class="cell_option cell_background_color minicolors" name="cell_background_color">
                            <a data-toggle="tab" class="table_option">
                                <i class="material-icons">
                                    format_color_fill
                                </i>
                            </a>
                        </li>
                        <li class="cells_option font_color position_relative ml-2 mr-2"
                            title="<?php esc_attr_e('Font color', 'wptm') ?>">
                            <input id="cell_font_color" type="text" value="#ffffff" data-show-opacity="true"
                                   data-default-color="#adadad"
                                   class="cell_option cell_font_color minicolors" name="cell_font_color">
                            <a data-toggle="tab" class="table_option">
                                <i class="material-icons">
                                    text_format
                                </i>
                            </a>
                        </li>
                        <div class="toolbar-separator"></div>
                        <input id="cell_text_align" type="button" value="center" class="cell_option cell_text_align"
                               name="cell_text_align" style="display: none">
                        <li class="cells_option sub_menu_option ml-2 mr-2">
                            <div class="toolbar-icon dflex-aligncenter tip"
                                 title="<?php esc_attr_e('Cell content horizontal align', 'wptm') ?>">
                                <i class="material-icons">
                                    format_align_left
                                </i>
                            </div>
                            <div class="sub_menu">
                                <div>
                                    <a data-toggle="tab" class="cell_option "
                                       title="<?php esc_attr_e('Left', 'wptm') ?>"
                                       name="format_align_left">
                                        <i class="material-icons">
                                            format_align_left
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option "
                                       title="<?php esc_attr_e('Center', 'wptm') ?>"
                                       name="format_align_center">
                                        <i class="material-icons">
                                            format_align_center
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option "
                                       title="<?php esc_attr_e('Right', 'wptm') ?>"
                                       name="format_align_right">
                                        <i class="material-icons">
                                            format_align_right
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option "
                                       title="<?php esc_attr_e('Align justify', 'wptm') ?>"
                                       name="format_align_justify">
                                        <i class="material-icons">
                                            format_align_justify
                                        </i>
                                    </a>
                                </div>
                            </div>
                        </li>
                        <input id="cell_vertical_align" type="button" value="1" class="cell_option cell_vertical_align"
                               name="cell_vertical_align" style="display: none">
                        <li class="cells_option sub_menu_option ml-2 mr-2">
                            <div class="toolbar-icon dflex-aligncenter tip"
                                 title="<?php esc_attr_e('Cell content vertical align', 'wptm') ?>">
                                <i class="material-icons">
                                    vertical_align_bottom
                                </i>
                            </div>
                            <div class="sub_menu">
                                <div>
                                    <a data-toggle="tab" class="cell_option "
                                       title="<?php esc_attr_e('Bottom', 'wptm') ?>"
                                       name="vertical_align_bottom">
                                        <i class="material-icons">
                                            vertical_align_bottom
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option "
                                       title="<?php esc_attr_e('Middle', 'wptm') ?>"
                                       name="vertical_align_middle">
                                        <i class="material-icons">
                                            vertical_align_center
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " title="<?php esc_attr_e('Top', 'wptm') ?>"
                                       name="vertical_align_top">
                                        <i class="material-icons">
                                            vertical_align_top
                                        </i>
                                    </a>
                                </div>
                            </div>
                        </li>

                        <li class="cells_option sub_menu_option ml-2 mr-2 text_wrapping">
                            <div class="toolbar-icon dflex-aligncenter tip"
                                 title="<?php esc_attr_e('Cell content text wrapping', 'wptm') ?>">
                                <a data-toggle="tab" class="cell_option tip">
                                    <i class="material-icons"></i>
                                </a>
                            </div>
                            <div class="sub_menu">
                                <div>
                                    <input id="text_wrapping" type="button" value="break-word" class="cell_option text_wrapping"
                                           name="text_wrapping" style="display: none">
<!--                                    <li class="cells_option position_relative text_wrapping">-->
                                        <a data-toggle="tab" class="cell_option tip"
                                           title="<?php esc_attr_e('Overflow', 'wptm') ?>" id="overflow_cell" name="overflow_cell">
                                            <i class="material-icons"></i>
                                        </a>
<!--                                    </li>-->
<!--                                    <li class="cells_option position_relative text_wrapping">-->
                                        <a data-toggle="tab" class="cell_option tip"
                                           title="<?php esc_attr_e('Wrap', 'wptm') ?>" id="wrap_cell" name="wrap_cell">
                                            <i class="material-icons"></i>
                                        </a>
<!--                                    </li>-->
<!--                                    <li class="cells_option position_relative text_wrapping">-->
                                        <a data-toggle="tab" class="cell_option tip"
                                           title="<?php esc_attr_e('Clip', 'wptm') ?>" id="clip_cell" name="clip_cell">
                                            <i class="material-icons"></i>
                                        </a>
<!--                                    </li>-->
                                </div>
                            </div>
                        </li>

                        <li class="cells_option ml-2 mr-2">
                            <a data-toggle="tab" class="cell_option tip"
                               title="<?php esc_attr_e('Cell padding and border radius', 'wptm') ?>"
                               name="padding_border">
                                <i class="material-icons">
                                    crop_free
                                </i>
                            </a>
                        </li>
                        <?php if ($this->table->type === 'mysql') : ?>
                        <li class="cells_option ml-2 mr-2 no_active">
                        <?php else : ?>
                        <li class="cells_option ml-2 mr-2">
                        <?php endif; ?>
                            <a id="cell_type" data-toggle="tab" class="cell_option tip"
                               title="<?php esc_attr_e('HTML / Simple text editor toggle', 'wptm') ?>" name="cell_type">
                                <i class="material-icons">code</i>
                            </a>
                        </li>
                        <li class="cells_option sub_menu_option sub_left ml-2 mr-2">
                            <div class="toolbar-icon dflex-aligncenter tip"
                                 title="<?php esc_attr_e('Apply cell border, border color and border style', 'wptm') ?>">
                                <i class="material-icons">
                                    border_all
                                </i>
                            </div>
                            <div id="cell_border" class="sub_menu">
                                <div>
                                    <input class="cell_option observeChanges input-mini tip tooltipstered" data-toggle="tab" title="<?php esc_attr_e('border width', 'wptm') ?>"
                                           type="number" name="border_width" id="cell_border_width" value="1" style="width: 100%" min="0">
                                </div>
                                <div>
                                    <a data-toggle="tab" class="cell_option " name="border_all"
                                       title="<?php esc_attr_e('All border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_all
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_top"
                                       title="<?php esc_attr_e('Top border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_top
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_bottom"
                                       title="<?php esc_attr_e('Bottom border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_bottom
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_horizontal"
                                       title="<?php esc_attr_e('Horizontal border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_horizontal
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_clear"
                                       title="<?php esc_attr_e('Clear border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_clear
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="table_option border_color">
                                        <i class="material-icons">
                                            format_color_fill
                                        </i>
                                        <input id="border_color" type="text" value="#000000" data-show-opacity="true"
                                               data-default-color="#adadad"
                                               class="cell_option border_color minicolors" name="border_color">
                                    </a>
                                </div>
                                <div>
                                    <a data-toggle="tab" class="cell_option " name="border_left"
                                       title="<?php esc_attr_e('Left border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_left
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_vertical"
                                       title="<?php esc_attr_e('Vertical border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_vertical
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_right"
                                       title="<?php esc_attr_e('Right border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_right
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_inner"
                                       title="<?php esc_attr_e('Inner border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_inner
                                        </i>
                                    </a>
                                    <a data-toggle="tab" class="cell_option " name="border_outer"
                                       title="<?php esc_attr_e('Outer border', 'wptm') ?>">
                                        <i class="material-icons">
                                            border_outer
                                        </i>
                                    </a>
                                    <div class="sub_menu_option">
                                        <a data-toggle="tab" class="cell_option sub_menu_option"
                                           title="<?php esc_attr_e('Border style', 'wptm') ?>">
                                            <i class="material-icons">
                                                power_input
                                            </i>
                                        </a>
                                        <div id="cell_border_style" class="sub_menu"
                                             style="line-height: 20px; padding: 0 0 7px 0;">
                                            <a class="cell_option active border_style" name="border_solid"
                                               style="min-height: unset;padding:5px;">
                                                <hr style="width: 40px;border: 1px solid #546478; margin: 0"/>
                                            </a>
                                            <a class="cell_option border_style" name="border_dashed"
                                               style="min-height: unset;padding: 5px;">
                                                <hr style="width: 40px;border: 1px dashed #546478; margin: 0"/>
                                            </a>
                                            <a class="cell_option border_style" name="border_dotted"
                                               style="min-height: unset;padding: 5px;">
                                                <hr style="width: 40px;border: 1px dotted #546478; margin: 0"/>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php if ($this->table->type === 'mysql') : ?>
                        <li class="cells_option position_relative ml-2 mr-2 no_active">
                        <?php else : ?>
                        <li class="cells_option position_relative ml-2 mr-2">
                        <?php endif; ?>
                            <a data-toggle="tab" class="cell_option tip"
                               title="<?php esc_attr_e('Merge cells', 'wptm') ?>" id="merge_cell" name="merge_cell">
                                <i class="material-icons">

                                </i>
                            </a>
                        </li>
                        <li class="cells_option sub_menu_option ml-2 mr-2">
                            <div class="toolbar-icon dflex-aligncenter tip"
                                 title="<?php esc_attr_e('Functions', 'wptm') ?>">
                                <i class="material-icons">
                                    functions
                                </i>
                            </div>
                            <div class="sub_menu" id="list_function" style="padding: 0;">
                                <div>
                                    <ul>
                                        <?php
                                        $list_function = array(
                                            'DATE' => array('DATE(year; month; day)', 'EXAMPLE', 'DATE(1969; 7; 20)', 'ABOUT', 'Converts a provided year, month, and day into a date.'),
                                            'DAY' => array('DAY(date)', 'EXAMPLE', 'DAY("7/20/1969")', 'ABOUT', 'Returns the day of the month that a specific date falls on, in numeric format.'),
                                            'DAYS' => array('DAYS(end_date; start_date)', 'EXAMPLE', 'DAYS("7/24/1969"; "7/16/1969")', 'ABOUT', 'Returns the number of days between two dates.'),
                                            'DAYS360' => array('DAYS360(start_date; end_date)', 'EXAMPLE', 'DAYS360("7/16/1969"; "7/24/1969")', 'ABOUT', 'Returns the difference between two days based on the 360 day year used in some financial interest calculations.'),
                                            'AND' => array('AND(logical_expression1; [logical_expression2, …])', 'EXAMPLE', 'AND(A1=1; A2=2)', 'ABOUT', 'Returns true if all of the provided arguments are logically true, and false if any of the provided arguments are logically false.'),
                                            'OR' => array('or(logical_expression1; [logical_expression2, …])', 'EXAMPLE', 'or(A1=1; A2=2)', 'ABOUT', 'Returns true if any of the provided arguments are logically true, and false if all of the provided arguments are logically false.'),
                                            'XOR' => array('XOR(logical_expression1; [logical_expression2, …])', 'EXAMPLE', 'XOR(A1=1; A2=2)', 'ABOUT', 'Returns true if an odd number of the provided arguments are logically true, and false if an even number of the arguments are logically true.'),
                                            'SUM' => array('SUM(value1; [value2, …])', 'EXAMPLE', 'SUM(A2; 101) &#10; OR: SUM(A2:A100)', 'ABOUT', 'Returns the sum of a series of numbers and/or cells.'),
                                            'DIVIDE' => array('DIVIDE(value1; value2)', 'EXAMPLE', 'DIVIDE(A2; B2)', 'ABOUT', 'One number divided by another.'),
                                            'MULTIPLY' => array('MULTIPLY(value1; [value2, …])', 'EXAMPLE', 'MULTIPLY(A2; 101)  &#10; OR: MULTIPLY(A2:A100)', 'ABOUT', 'Product of numbers and/or cells'),
                                            'COUNT' => array('COUNT(value1; [value2, …])', 'EXAMPLE', 'COUNT(A2:A100)', 'ABOUT', 'Returns the number of numeric values in a dataset.'),
                                            'MIN' => array('MIN(value1; [value2, …])', 'EXAMPLE', 'MIN(A2:A100)', 'ABOUT', 'Returns the minimum value in a numeric dataset'),
                                            'MAX' => array('MAX(value1; [value2, …])', 'EXAMPLE', 'MAX(A2:A100)', 'ABOUT', 'Returns the maximum value in a numeric dataset.'),
                                            'AVG' => array('AVG(value1; [value2, …])', 'EXAMPLE', 'AVG(A2; 101)', 'ABOUT', 'Returns the rank of a specified value in a dataset. If there is more than one entry of the same value in the dataset, the average rank of the entries will be returned.'),
                                            'CONCAT' => array('CONCAT(value1, value2)', 'EXAMPLE', 'CONCAT(hello, goodbye)', 'ABOUT', 'Returns the concatenation of two values. Equivalent to the & operator.')
                                        );
                                        foreach ($list_function as $calculator => $value_title) {
                                            ?>
                                            <?php if ($this->table->type === 'mysql') : ?>
                                                <li data-toggle="tab" class="calculater_function sub_menu_option no_active"
                                                data-calculater="<?php echo esc_attr($calculator); ?>">
                                            <?php else : ?>
                                                <li data-toggle="tab" class="calculater_function sub_menu_option"
                                                data-calculater="<?php echo esc_attr($calculator); ?>">
                                            <?php endif; ?>
                                            <span><?php echo esc_attr($calculator); ?></span>
                                            <div class="sub_menu" style="left: 100%;width: 200px;padding: 0;">
                                                <?php
                                                $count = count($value_title);
                                                for ($i = 0; $i < $count; $i++) {
                                                    ?>
                                                    <span style="white-space: pre-line;"><?php echo esc_attr($value_title[$i]); ?></span>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <!-- value cell show -->
                    <?php if ($this->table->type === 'mysql') : ?>
                        <input type="text" id="CellValue" readonly name="" value=""/>
                    <?php else : ?>
                        <input type="text" id="CellValue" name="" value=""/>
                    <?php endif; ?>
                    <a id="edit_toolTip" name="editToolTip" class="table_option" style="display: none;"></a>
                </div>
            </div>
            <!-- show table -->
            <div id="mainTabContent" class="tab-content">
                <div id="dataTable" class="tab-pane active">
                    <div id="tableContainer" style="overflow:hidden;"></div>
                </div>
            </div>
            <!--Notification-->
        </div>
        <span id="savedInfoTable"><?php esc_attr_e('All modifications were saved', 'wptm'); ?></span>
        <span id="savedTableName"><?php esc_attr_e('Table name saved', 'wptm'); ?></span>
        <span id="saveErrorTable"><?php esc_attr_e('Error! You have an error in the date calculation.', 'wptm'); ?></span>
        <span id="undoNotic"></span>
        <div id="wptm_chart" class="wptm_hiden" data-scrollbar>
            <div class="wptm_left_content">
                <div class="wptm_top_chart">
                    <div>
                        <!-- Chart text -->
                        <span class="title wptm_name_edit" contentEditable="true">
                            <?php esc_attr_e('name chart', 'wptm'); ?>
                        </span>
                        <a class="trash"><i class="icon-trash"></i></a>
                        <p class="saving"
                           style="opacity: 0;display: inline-block;"><?php esc_attr_e('saving...', 'wptm'); ?></p>
                        <!-- Menu option chart -->
                    </div>
                    <ul class="nav" id="list_chart">
                        <li class="current_table">
                            <a><?php esc_attr_e('Name table', 'wptm'); ?></a>
                        </li>
                    </ul>
                </div>
                <div class="wptm_content_chart">
                </div>
            </div>
            <div class="wptm_rightcol">
                <div class="tab-content" id="chartTabContent">
                    <span class="wptm_rightcol_top_title"><?php esc_attr_e('Chart', 'wptm'); ?></span>
                    <div id="chart" class="tab-pane active">
                        <div class="control-group">
                            <div class="controls" name="type">
                                <ul style="margin-left: 0;">
                                    <?php foreach ($this->chartTypes as $chartType) : ?>
                                        <li><a href="#" title="<?php echo esc_attr($chartType->name); ?>">
                                                <img class="option_chart chart_type"
                                                     width="100"
                                                     data-id="<?php echo esc_attr($chartType->id); ?>"
                                                     alt="<?php echo esc_attr($chartType->name); ?>"
                                                     src="<?php echo esc_url_raw(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/charts/' . $chartType->image); ?>"/></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="control-group">
                            <div class="controls" name="changerChart">
                                <span><?php esc_attr_e('Data range', 'wptm'); ?></span>
                                <label id="jform_dataSelected-lbl">
                                    <input class="observeChanges cellRangeLabelAlternate input-mini"
                                           style="width: 120px;text-transform: uppercase;">
                                    <input type="button" class="wptm_no_active change_ranger" style="width: 80px;padding: 7px 2px;"
                                           value="<?php esc_attr_e('Change', 'wptm'); ?>">
                                </label>
                            </div>

                            <div class="controls" name="dataUsing">
                                <span><?php esc_attr_e('Switch row/column', 'wptm'); ?></span>
                                <select class="chzn-select observeChanges option_chart">
                                    <option value="row"><?php esc_attr_e('Row', 'wptm'); ?></option>
                                    <option value="column"><?php esc_attr_e('Column', 'wptm'); ?></option>
                                </select>
                                <span class="wptm_notice" style="display: none;">
                                    <?php esc_attr_e('Cannot convert data fetch', 'wptm'); ?></span>
                            </div>
                            <div class="wptmCollapsibleParent">
                                <span class="wptmCollapsible">
                                    <?php esc_attr_e('Custom axis data', 'wptm'); ?>
                                </span>
                                <div class="wptmCollapsibleContent">
                                    <div class="controls lineUsing" name="lineUsing">
                                        <span><?php esc_attr_e('Series', 'wptm'); ?></span>
                                        <div>
                                            <div id="listLine"></div>
                                        </div>
                                    </div>

                                    <div class="controls x_axis_line" name="x_axis_line">
                                        <label style="font-size: 19px"><?php esc_attr_e('X-axis', 'wptm'); ?></label>
                                        <div>
                                            <select class="chzn-select observeChanges option_chart">
                                            </select>
                                        </div>
                                    </div>

                                    <div class="controls" name="useFirstRowAsLabels">
                                        <span>
                                            <?php esc_attr_e('Use selected row/column as X-labels', 'wptm'); ?>
                                        </span>
                                        <input class="banding-footer-checkbox switch-button option_chart"
                                               type="checkbox" value="">
                                    </div>

                                    <div class="controls" name="useFirstRowAsGraph">
                                        <span>
                                            <?php esc_attr_e('Use first row/column as chart data', 'wptm'); ?>
                                        </span>
                                        <input class="banding-footer-checkbox switch-button option_chart"
                                               type="checkbox" value="">
                                    </div>

                                    <div class="controls customLabelXaxis" name="customLabelXaxis">
                                        <span>
                                            <?php esc_attr_e('Custom label for X-axis', 'wptm'); ?>
                                        </span>
                                        <input class="observeChanges wptm_default input-mini">
                                    </div>

                                    <div class="controls y_axis_line" name="y_axis_line">
                                        <label style="font-size: 19px"><?php esc_attr_e('Y-axis', 'wptm'); ?></label>
                                        <div name="stepSize">
                                            <span><?php esc_attr_e('Major step', 'wptm'); ?></span>
                                            <input class="stepSize observeChanges wptm_default input-mini" type="number" min="0"
                                                   style="text-transform: uppercase;width: 95% !important;margin: 5px 0">
                                        </div>
                                        <div name="minY">
                                            <span><?php esc_attr_e('Min value of Y-axis', 'wptm'); ?></span>
                                            <input class="minY observeChanges wptm_default input-mini" type="number"
                                                   style="text-transform: uppercase;width: 95% !important;margin: 5px 0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="wptmCollapsibleParent">
                                <span class="wptmCollapsible active">
                                    <?php esc_attr_e('Data series color', 'wptm'); ?>
                                </span>
                                <div class="wptmCollapsibleContent wptm_show" style="display: block; opacity: 1">
                                    <div class="controls" name="dataset_select">
                                        <span>
                                            <?php esc_attr_e('Chart data colors', 'wptm'); ?>
                                        </span>
                                        <select class="chzn-select observeChanges option_chart">
                                        </select>
                                    </div>

                                    <div class="controls" name="dataset_color">
                                    <span>
                                        <?php esc_attr_e('Selected data colors', 'wptm'); ?>
                                    </span>
                                        <input class="minicolors minicolors-input observeChanges option_chart"
                                               data-position="left"
                                               data-control="hue" type="text" value="" size="7"/>
                                    </div>
                                </div>
                            </div>

                            <div class="wptmCollapsibleParent">
                                <span class="wptmCollapsible active">
                                    <?php esc_attr_e('Configuration', 'wptm'); ?>
                                </span>
                                <div class="wptmCollapsibleContent wptm_show" style="display: block; opacity: 1">
                                    <div class="controls" name="legend_display">
                                        <span><?php esc_attr_e('Chart legend display', 'wptm'); ?></span>
                                        <input class="banding-footer-checkbox switch-button option_chart"
                                               type="checkbox" value="">
                                    </div>

                                    <div class="controls customLegendText" name="customLegendText">
                                        <span>
                                            <?php esc_attr_e('Custom legend text', 'wptm'); ?>
                                        </span>
                                        <input class="observeChanges wptm_default input-mini">
                                    </div>

                                    <div class="controls" name="width">
                                        <span>
                                            <?php esc_attr_e('Chart width', 'wptm'); ?>
                                        </span>
                                        <div class="form-inline">
                                            <input class="observeChanges option_chart" type="number"
                                                   value="" size="7"/>
                                        </div>
                                    </div>

                                    <div class="controls" name="height">
                                        <span>
                                            <?php esc_attr_e('Chart height', 'wptm'); ?>
                                        </span>
                                        <div class="form-inline">
                                            <input class="observeChanges option_chart" type="number"
                                                   value="" size="7"/>
                                        </div>
                                    </div>

                                    <div class="controls" name="chart_align">
                                    <span>
                                        <?php esc_attr_e('Align Chart', 'wptm'); ?>
                                    </span>
                                        <select class="chzn-select observeChanges option_chart">
                                            <option value="center"><?php esc_attr_e('Center', 'wptm'); ?></option>
                                            <option value="right"><?php esc_attr_e('Right', 'wptm'); ?></option>
                                            <option value="left"><?php esc_attr_e('Left', 'wptm'); ?></option>
                                            <option value="none"><?php esc_attr_e('None', 'wptm'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="controls" name="shortcode">
                                    <span>
                                        <?php esc_attr_e('Shortcode', 'wptm'); ?>
                                        <span class="copy_shortcode copy_button">
                                            <i class="material-icons">file_copy</i>
                                            <?php esc_attr_e('Copy', 'wptm'); ?>
                                        </span>
                                    </span>
                                <div class="form-inline">
                                    <input class="observeChanges wptm_no_active_click option_chart" readonly="readonly"
                                           type="text" name="shortcode" value="" size="7"/>
                                </div>
                            </div>
                        </div>

                        <br/><br/>
                    </div>
                </div>
            </div>
        </div>

        <div id="wptm_popup">
            <i class="material-icons colose_popup">
                close
            </i>
            <div class="content"></div>
        </div>
        <div id="over_popup">
        </div>
        <div class="prevent_event_popup">
        </div>
        <!--element tooltip-->
        <div id="wptm_edit_html_cell" class="control-group">
            <div class="content">
                <div id="html_cell_editor">
                    <div class="control-group wptm_html_cell_editor_container">
                        <textarea id="html_cell_content" name="html_cell_content" class="observeChanges"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div id="wptm_editToolTip" class="control-group">
            <i class="material-icons colose_popup">
                close
            </i>
            <div class="content">
                <div class="popup_top">
                    <span><?php esc_attr_e('Tooltip', 'wptm') ?></span>
                </div>
                <div id="tooltip_editor">
                    <div class="control-group wptm_tooltip_editor_container">
                        <textarea id="tooltip_content" name="tooltip_content" class="observeChanges"></textarea>
                    </div>
                    <div class="control-group" style="margin-top: 0;">
                        <label id="jform_tooltip_width-lbl" for="jform_tooltip_width">
                            <?php esc_attr_e('Tooltip width', 'wptm'); ?> :
                        </label>
                        <input class="observeChanges input-mini" type="number" name="tooltip_width" min="0"
                               id="tooltip_width" value="" size="7"/>
                    </div>
                    <div>
                        <a id="saveToolTipbtn" class="wptm_button wptm_done"
                           title="<?php esc_attr_e('Save', 'wptm'); ?>"
                           href="javascript:void(0)"><?php esc_attr_e('Save', 'wptm'); ?></a>
                        <a id="cancelToolTipbtn" class="wptm_button wptm_cancel"
                           title="<?php esc_attr_e('Cancel', 'wptm'); ?>"
                           href="javascript:void(0)"><?php esc_attr_e('Cancel', 'wptm'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <!--element in popup, display none-->
        <div id="content_popup_hide">
            <a id="edit_html_cell" href="#wptm_edit_html_cell"></a>
            <a id="editToolTip" href="#wptm_editToolTip"></a>

            <div class="control-label" id="select_cells">
                <label class="wrapper" id="jform_alternate_row_even_color-lbl" for="jform_alternate_row_even_color">
                    <?php esc_attr_e('Data range', 'wptm'); ?> :
                </label>
                <div class="wrapper">
                    <input type="text" class="cellRangeLabelAlternate input-mini observeChanges"
                           style="width: 100px;text-transform: uppercase;">
                    <input type="button" class="wptm_active wptm_ripple" id="get_select_cells"
                           value="<?php esc_attr_e('Apply', 'wptm'); ?>">
                </div>
            </div>
            <div id="submit_button">
                <input type="button" class="wptm_button wptm_done" id="popup_done"
                       value="<?php esc_attr_e('Done', 'wptm'); ?>">
                <input type="button" class="wptm_button wptm_cancel" id="popup_cancel"
                       value="<?php esc_attr_e('Cancel', 'wptm'); ?>">
            </div>

            <div id="access_menu">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Table access', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <label id="jform_role_table-lbl" for="jform_role_table">
                        <?php esc_attr_e('Owner', 'wptm'); ?>
                    </label>
                    <select id="jform_role_table" class="popup_select" <?php echo $user_role_wptm['change_author'] ? '' : 'disabled'?>>
                        <?php
                        $count = count($this->list_user);
                        for ($i = 0; $i < $count; $i++) :?>
                            <option value="<?php echo esc_attr($this->list_user[$i]['id']);?>"><?php echo esc_attr($this->list_user[$i]['name']);?></option>
                        <?php endfor;?>
                    </select>
                </div>
            </div>

            <div class="chart_line">
                <input type="checkbox" for="line_name"/>
                <span class="lineSelectTion" name="line_name"></span>
            </div>

            <div id="table_styles" class="control-group">
                <div class="popup_top border_top">
                    <span><?php echo esc_attr__('Theme selection', 'wptm') ?></span>
                </div>
                <ul>
                    <?php foreach ($this->styles as $style) : ?>
                        <?php
                        $url_image_full = str_replace('theme' . ($style->id - 1) . '.png', 'themefull' . ($style->id - 1) . '.png', $style->image);
                        ?>
                        <li class="theme_<?php echo esc_attr($style->id); ?>" data-image="<?php echo esc_url_raw($url_image_full); ?>"><a href="#" data-id="<?php echo esc_attr($style->id); ?>"><img style="width:200px"
                                        src="<?php echo esc_url_raw($style->image); ?>"/></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div id="alternating_color" class="">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Automatic styling', 'wptm'); ?></span>
                </div>
                <div>
                    <div class="control-group">
                        <div class="control-label banding-header-footer-checkbox-wrapper">
                            <div class="wrapper">
                                <span class="banding-header-checkbox-label">Header styling</span></div>
                            <input class="banding-header-checkbox switch-button" type="checkbox" value="">
                        </div>
                    </div>
                    <div class="control-group">
                        <div class="control-label banding-header-footer-checkbox-wrapper">
                            <div class="wrapper">
                                <span class="banding-footer-checkbox-label">Footer styling</span></div>
                            <input class="banding-footer-checkbox switch-button" type="checkbox" value="">
                        </div>
                    </div>
                    <hr/>
                    <div class="control-group">
                        <div class="control-label">
                            <label id="jform_alternate_color" for="jform_alternate_color">
                                <?php esc_attr_e('Automatic styling', 'wptm'); ?> :
                            </label>
                        </div>
                        <div class="controls formatting_style">
                            <?php
                            if (isset($this->params['alternate_color'])) {
                                $arrayValue = explode('|', $this->params['alternate_color']);
                            } else {
                                $defaultAlternateColor = '#bdbdbd|#ffffff|#f3f3f3|#ffffff';
                                $defaultAlternateColor .= '|#4dd0e1|#ffffff|#e0f7fa|#a2e8f1';
                                $defaultAlternateColor .= '|#63d297|#ffffff|#e7f9ef|#afe9ca';
                                $defaultAlternateColor .= '|#f7cb4d|#ffffff|#fef8e3|#fce8b2';
                                $defaultAlternateColor .= '|#f46524|#ffffff|#ffe6dd|#ffccbc';
                                $defaultAlternateColor .= '|#5b95f9|#ffffff|#e8f0fe|#acc9fe';
                                $defaultAlternateColor .= '|#26a69a|#ffffff|#ddf2f0|#8cd3cd';
                                $defaultAlternateColor .= '|#78909c|#ffffff|#ebeff1|#bbc8ce';
                                $arrayValue = explode('|', $defaultAlternateColor);
                            }

                            $count = count($arrayValue);
                            $html = '';
                            for ($i = 0; $i < $count / 4; $i++) {
                                $i4 = $i % 4;
                                $i16 = $i * 4;
                                $value = array(
                                    $arrayValue[$i16],
                                    $arrayValue[$i16 + 1],
                                    $arrayValue[$i16 + 2],
                                    $arrayValue[$i16 + 3]
                                );

                                $html .= renderListStyle($value, $i4);
                            }
                            ?>

                            <?php
                            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The inner variables were esc
                            echo $html;
                            ?>
                        </div>
                    </div>
                    <input id="alternate_color_value" type="text" style="display: none;" value="">
                    <input type="button" class="wptm_ripple wptm_button wptm_done" id="alternate_color_done"
                           value="<?php esc_attr_e('Done', 'wptm'); ?>">
                    <input type="button" class="wptm_ripple wptm_button wptm_cancel" id="popup_cancel"
                           value="<?php esc_attr_e('Cancel', 'wptm'); ?>">
                </div>
            </div>
            <div id="wptm_bootbox">
                <div class="popup_top border_top">
                    <span></span>
                </div>
                <div class="control-group">
                    <span></span>
                </div>
                <div id="submit_button">
                    <input type="button" class="wptm_button wptm_done" id="popup_done" value="<?php esc_attr_e('Done', 'wptm'); ?>">
                    <input type="button" class="wptm_button wptm_cancel" id="popup_cancel" value="<?php esc_attr_e('Cancel', 'wptm'); ?>">
                </div>
            </div>
            <div id="cell_insertion_shortcode">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Cell insertion shortcode', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <div class="">
                        <label id="jform_cell_insertion_shortcode_filter-lbl" for="jform_cell_insertion_shortcode_filter" style="display: inline-block">
                            <?php esc_attr_e('Frontend filter data', 'wptm'); ?> :
                        </label>
                        <input class="switch-button" style="margin-left: 50px;"
                               id="cell_insertion_shortcode_filter" type="checkbox" value="">
                    </div>
                    <div class="">
                        <label id="jform_cell_insertion_shortcode_sort-lbl" for="jform_cell_insertion_shortcode_sort" style="display: inline-block">
                            <?php esc_attr_e('Frontend sortable data', 'wptm'); ?> :
                        </label>
                        <input class="switch-button" style="margin-left: 27px;"
                               id="cell_insertion_shortcode_sort" type="checkbox" value="">
                        <div class="sortOrder" style="margin-left: 0">
                            <label id="jform_default_sort-lbl" for="jform_default_sort">
                                <?php esc_attr_e('Default column sorting', 'wptm'); ?> :
                            </label>
                            <span id="default_sort" class="popup_select wptm_select_box_before"></span>
                            <ul class="wptm_select_box select_columns">
                            </ul>

                            <label id="jform_default_order_sort-lbl" for="jform_default_order_sort">
                                <?php esc_attr_e('Order sort a column by default', 'wptm'); ?> :
                            </label>
                            <span id="default_order_sort" class="popup_select wptm_select_box_before"></span>
                            <ul class="wptm_select_box">
                                <li data-value="1"><?php esc_attr_e('ASC', 'wptm'); ?></li>
                                <li data-value="0"><?php esc_attr_e('DESC', 'wptm'); ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="" style="margin-top: 10px;">
                        <label id="jform_cell_insertion_shortcode_align-lbl" for="jform_cell_insertion_shortcode_align" style="display: block">
                            <?php esc_attr_e('Table align', 'wptm'); ?> :
                        </label>
                        <input class="wptm_input" type="radio" id="center" name="cell_insertion_shortcode_align" value="center" style="margin: 0 !important;">
                        <label class="ju-setting-label" for="ajax"><span><?php esc_attr_e('Center', 'wptm'); ?></span></label>
                        <input class="wptm_input" type="radio" id="left" name="cell_insertion_shortcode_align" value="left" style="margin: 0 !important;">
                        <label class="ju-setting-label" for="cron"><span><?php esc_attr_e('Left', 'wptm'); ?></span></label>
                        <input class="wptm_input" type="radio" id="right" name="cell_insertion_shortcode_align" value="right" style="margin: 0 !important;">
                        <label class="ju-setting-label" for="cron"><span><?php esc_attr_e('Right', 'wptm'); ?></span></label>
                        <input class="wptm_input" type="radio" id="none" name="cell_insertion_shortcode_align" value="none" checked="true" style="margin: 0 !important;">
                        <label class="ju-setting-label" for="cron"><span><?php esc_attr_e('None', 'wptm'); ?></span></label>
                    </div>
                    <div class="">
                        <label id="jform_cell_insertion_shortcode_download-lbl" for="jform_cell_insertion_shortcode_download" style="display: inline-block">
                            <?php esc_attr_e('Allow download', 'wptm'); ?> :
                        </label>
                        <input class="switch-button"
                               id="cell_insertion_shortcode_download" type="checkbox" value="">

                    </div>
                </div>
                <div class="control-group">
                    <label for="cell_insertion_shortcode" style="display:block;">
                        <?php esc_attr_e('Shortcode', 'wptm'); ?> :
                    </label>
                    <div class="wrapper" style="margin: 0">
                        <input readonly class="shortcode wptm_copy_text_content" type="text" value="[wptm id=1]"><span class="button wptm_icon_tables copy_text tooltip"></span><span style="opacity: 0;color: #ff3e3e;font-size: 11px;"><?php esc_attr_e('Copied', 'wptm'); ?></span>
                    </div>
                </div>
            </div>
            <div id="lock_ranger_cells">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Protected ranges', 'wptm'); ?></span>
                </div>
                <div class="control-group" id="123123">
                    <label for="jform_role_can_edit_lock">
                        <?php esc_attr_e('User roles allowed to edit', 'wptm'); ?> :
                    </label>
                    <span id="jform_role_can_edit_lock" class="popup_select wptm_select_box_before" style="max-width: 390px;overflow: hidden;text-overflow: ellipsis;padding-right: 40px;"></span>
                    <ul class="wptm_select_box">
                        <li class="data-none" data-value="<?php echo esc_attr(strtoupper('Administrator')); ?>"><?php esc_attr_e('None', 'wptm'); ?></li>
                        <?php
                        foreach ($roles_list as $key => $role_user) :
                            if ($role_user !== 'Administrator') {
                                ?>
                            <li data-value="<?php echo esc_attr(strtoupper($key)); ?>"><input type="checkbox"/><?php echo esc_attr($role_user); ?></li>
                                <?php
                            }
                            ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="control-label" style="margin-bottom: 0" id="select_cells">
                    <label class="wrapper" id="jform_alternate_row_even_color-lbl" for="jform_alternate_row_even_color">
                        <?php esc_attr_e('Protected cell range', 'wptm'); ?>:
                    </label>
                    <div class="wrapper">
                        <input type="text" class="cellRangeLabelAlternate input-mini observeChanges" style="width: 100%; margin-right: 0;text-transform: uppercase;">
                    </div>
                </div>
                <input type="button" class="wptm_active wptm_ripple" id="get_select_cells" value="Add" style="margin: 0 0 0 auto;">
                <div class="control-group" id="321321">
                    <label style="margin-bottom: 10px;">
                        <?php esc_attr_e('Edit cell ranges', 'wptm'); ?> :
                    </label>
                    <ul class="select_lock_ranger_cells nfd-pill-container">
                        <li class="nfd-format-pill wptm_hiden" data-value="">
                            <label class="label_text font-name" for="font_name"></label>
                            <div style="position: relative">
                                <i class="material-icons-outlined wptm-has-tooltip edit_font">mode_edit</i>
                                <i class="material-icons-outlined wptm-has-tooltip delete_range">delete</i>
                                <span class="tooltip-label wptm-tooltip-label">
                                    <?php esc_attr_e('Removing this protected range', 'wptm'); ?>
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
                <div id="submit_button">
                    <input type="button" class="wptm_button wptm_done" id="popup_done" style="margin-right: 0;" value="<?php esc_attr_e('SAVE & CLOSE', 'wptm'); ?>">
                </div>
            </div>
            <div id="column_size_menu">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Apply column/line size', 'wptm'); ?></span>
                </div>
                <div class="control-group wptm_range_labe_show">
                    <label id="jform_row_height-lbl" for="jform_row_height">
                        <?php esc_attr_e('Rows height', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" type="number" name="row_height"
                           id="cell_row_height" value="" size="7"/><span>(px)</span>
                </div>
                <div class="control-group wptm_range_labe_hide wptm_hiden">
                    <label id="jform_all_row_height-lbl" for="jform_row_height">
                        <?php esc_attr_e('Rows height (body)', 'wptm'); ?> :
                        <div style="position: relative">
                            <i class="material-icons has-tooltip" style="color: #f93d3d; font-size: 15px; ">announcement</i>
                            <span class="tooltip-label"><?php esc_attr_e('This will change the height of all rows in the body!', 'wptm') ?></span>
                        </div>
                    </label>
                    <input class="observeChanges input-mini" type="number" name="all_row_height"
                           id="all_cell_row_height" value="" size="7"/><span>(px)</span>
                </div>
                <div class="control-group">
                    <label id="jform_col_width-lbl" for="jform_col_width">
                        <?php esc_attr_e('Column width', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" type="number" name="col_width"
                           id="cell_col_width" value="" size="7"/><span>(px)</span>
                </div>
                <div id="submit_button">
                    <input type="button" class="wptm_button wptm_done" id="popup_done"
                           value="<?php esc_attr_e('Done', 'wptm'); ?>">
                    <input type="button" class="wptm_button wptm_cancel" id="popup_cancel"
                           value="<?php esc_attr_e('Cancel', 'wptm'); ?>">
                </div>
            </div>
            <div id="create_row_dbtable">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Add new entry', 'wptm'); ?></span>
                </div>
                <?php if (!empty($this->table->query_option) && !empty($this->table->query_option->columns_list)) :?>
                    <div class="control-group scrolling" style="max-height: 380px; overflow-y: auto; overflow-x: hidden; padding-right: 15px;">
                        <?php foreach ($this->table->query_option->columns_list as $key => $column_option) {
                            if (!empty($column_option->canEdit) && (int)$column_option->canEdit === 1) {
                                ?>
                                <label id="jform_column_<?php echo esc_attr($column_option->Field);?>-lbl" for="jform_table_column">
                                    <?php
                                    //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- column name no EscapeOutput
                                    echo $column_option->table . '.' . $column_option->Field; ?> :
                                </label>
                                <input class="observeChanges input-mini table_column" data-column-name="<?php
                                //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- column name no EscapeOutput
                                echo $column_option->table . '.' . $column_option->Field;?>" type="text" name="table_column"
                                       value="" style="width: 100%"/>
                                <?php
                            }
                        }?>
                    </div>
                <?php endif;?>
            </div>
            <div id="form_filter_table">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Sort and filters', 'wptm'); ?></span>
                </div>
                <div class="control-group" style="max-height: 900px; overflow-y: auto">
                    <!--search all-->
                    <div class="topOption">
                        <!--Sort column-->
                        <div>
                            <label  for="jform_use_sortable" style="display: inline-block">
                                <?php esc_attr_e('Frontend sortable', 'wptm'); ?> :
                                <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Enable to sort columns and filter data on the frontend', 'wptm'); ?>"></i>
                            </label>
                            <input class="switch-button" name="jform_use_sortable"
                                   id="use_sortable" type="checkbox" value="" style="margin: 1em 1rem 1rem 0">
                        </div>
                        <br/>
                        <!--Default sort-->
                        <div class="min-width" style="display: inline-block;">
                            <label  for="jform_default_sortable">
                                <?php esc_attr_e('Default column sorting', 'wptm'); ?> :
                            </label>
                            <span id="default_sortable" name="jform_default_sortable"
                                  class="popup_select wptm_select_box_before" style="margin: 1rem 1rem 1rem 0;max-width: 70px"></span>
                            <ul class="wptm_select_box select_columns">
                            </ul>
                        </div>
                        <!--Order sort-->
                        <div>
                            <label for="default_order_sortable" style="min-width: unset;">
                                <?php esc_attr_e('Order sort a column by default', 'wptm'); ?> :
                            </label>
                            <span id="default_order_sortable" name="default_order_sortable"
                                  class="popup_select wptm_select_box_before" style="margin: 1rem 1rem 1rem 0;max-width: 95px"></span>
                            <ul class="wptm_select_box">
                                <li data-value="1"><?php esc_attr_e('ASC', 'wptm'); ?></li>
                                <li data-value="0"><?php esc_attr_e('DESC', 'wptm'); ?></li>
                            </ul>
                        </div>
                        <br/>
                        <div style="margin-top: 10px;margin-bottom: 10px;display: block;">
                            <label for="jform_enable_filters" style="display: inline-block">
                                <?php esc_attr_e('Enable filter', 'wptm'); ?> :
                                <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Select filter in each column or search in table', 'wptm'); ?>"></i>
                            </label>
                            <span id="enable_filters" name="jform_enable_filters" class="popup_select wptm_select_box_before"
                                  style="margin-right: 1rem; max-width: 200px"></span>
                            <ul class="wptm_select_box">
                                <li data-value="0"><?php esc_attr_e('None', 'wptm'); ?></li>
                                <li data-value="1"><?php esc_attr_e('Inside column', 'wptm'); ?></li>
                                <li data-value="2"><?php esc_attr_e('Advanced filter form', 'wptm'); ?></li>
                            </ul>
                        </div>
                        <!--button submit form-->
                        <div class="min-width" style="display: inline-block;">
                            <label for="jform_submit_form" style="display: inline-block">
                                <?php esc_attr_e('Submit button', 'wptm'); ?> :
                                <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Display a Submit button that needs to be clicked to filter the data in the table', 'wptm'); ?>"></i>
                            </label>
                            <input class="switch-button" name="jform_submit_form"
                                   id="submit_form" type="checkbox" value="" style="margin: 1em 1rem 1rem 0">
                        </div>
                        <!--button clear form-->
                        <div style="display: inline-block;">
                            <label for="jform_clear_form" style="display: inline-block">
                                <?php esc_attr_e('Clear filters button', 'wptm'); ?> :
                                <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Show Clear button which helps to clear data after searching', 'wptm'); ?>"></i>
                            </label>
                            <input class="switch-button" name="jform_clear_form"
                                   id="clear_form" type="checkbox" value="" style="margin: 1rem">
                        </div>
                        <!--search all-->
                        <div style="display: block; margin-top: 10px">
                            <label for="jform_enable_filters_all" style="display: inline-block">
                                <?php esc_attr_e('Main search', 'wptm'); ?> :
                                <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Enable to search all data in the table', 'wptm'); ?>"></i>
                            </label>
                            <input class="switch-button" name="jform_enable_filters_all"
                                   id="enable_filters_all" type="checkbox" value="" style="margin: 1em 1rem 1rem 0">
                        </div>
                    </div>
                    <div class="containerHorizontal listColumnConfig">
                        <div class="container columnConfigHeader">
                            <div class="name" style="text-align: left"><span><?php echo esc_attr('Column'); ?></span></div>

                            <div class="sortFilterType">
                                <span><?php esc_attr_e('Sort & filter type', 'wptm'); ?>
                                    <i class="material-symbols-outlined wptm_tooltip"  data-tooltip_text="<?php esc_attr_e('Select type for each column, default value is Text', 'wptm'); ?>"></i>
                                </span>
                            </div>

                            <div class="sort">
                                <span>
                                    <?php esc_attr_e('Sortable', 'wptm'); ?>
                                    <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Enable this feature to sort at each column', 'wptm'); ?>"></i>
                                </span>
                            </div>

                            <div class="filter">
                                <span>
                                    <?php esc_attr_e('Filterable', 'wptm'); ?>
                                    <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Enable this feature to filter at each column', 'wptm'); ?>"></i>
                                </span>
                            </div>

                            <div class="filterOptions">
                                <div class="filterType" style="text-align: center">
                                    <span style="text-align: center">
                                        <?php esc_attr_e('Filter type', 'wptm'); ?>
                                        <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Select a suitable type to filter corresponding data at each column', 'wptm'); ?>"></i>
                                    </span>
                                </div>

                                <div class="label">
                                    <span><?php esc_attr_e('Custom label', 'wptm'); ?></span>
                                </div>
                                <div class="checkBoxValue" style="text-align: center">
                                    <span>
                                        <?php esc_attr_e('Predefined value(s)', 'wptm'); ?>
                                        <i class="material-symbols-outlined wptm_tooltip" data-tooltip_text="<?php esc_attr_e('Select a specific value to filter. It\'s valid when the Filter type is CheckBox.', 'wptm'); ?>"></i>
                                    </span>
                                </div>
                                <div class="minValue rangerValue">
                                    <span>
                                        <?php esc_attr_e('Min value', 'wptm'); ?>
                                    </span>
                                </div>
                                <div class="maxValue rangerValue">
                                    <span>
                                        <?php esc_attr_e('Max value', 'wptm'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="container columnConfig wptm_hiden">
<!--                        <div class="columnConfig" data-column="">-->
                            <div class="name option" style="padding-right: 20px; box-sizing: border-box;">
                                <span style="text-transform: uppercase;"><?php esc_attr_e('Column Name', 'wptm'); ?></span>
                            </div>
                            <!--searchContentType-->
                            <div class="sortFilterType option">
                                <span class="popup_select wptm_select_box_before searchContentType"></span>
                                <ul class="wptm_select_box">
                                    <li data-value="number"><?php esc_attr_e('Number', 'wptm'); ?></li>
                                    <li data-value="text"><?php esc_attr_e('Text', 'wptm'); ?></li>
                                    <li data-value="date"><?php esc_attr_e('Date', 'wptm'); ?></li>
                                </ul>
                            </div>
                            <!--sort-->
                            <div class="sort option">
                                <input class="switch-button sortable" type="checkbox" value="">
                            </div>
                            <!--filter column-->
                            <div class="filter option">
                                <input class="use_filter_column switch-button" type="checkbox" value="">
                            </div>
                            <!--filterOptions-->
                            <div class="filterOptions">
                                <div class="option filterType">
                                    <span class="popup_select wptm_select_box_before searchType"
                                          title="<?php esc_attr_e('Filter type', 'wptm'); ?>"></span>
                                    <ul class="wptm_select_box">
                                        <li data-value="none"><?php esc_attr_e('None', 'wptm'); ?></li>
                                        <li data-value="select"><?php esc_attr_e('Select', 'wptm'); ?></li>
                                        <li data-value="input"><?php esc_attr_e('Input', 'wptm'); ?></li>
                                        <li data-value="checkBox"><?php esc_attr_e('CheckBox', 'wptm'); ?></li>
                                        <li data-value="range"><?php esc_attr_e('Range', 'wptm'); ?></li>
                                    </ul>
                                </div>
                                <!--custom label-->
                                <div class="option label">
                                    <input class="enable_filters_label" type="text"
                                           placeholder="<?php esc_attr_e('custom Filter label', 'wptm'); ?>" value="">
                                </div>
                                <!--checkBoxContent-->
                                <div class="option checkBoxValue">
                                    <span class="popup_select wptm_select_box_before checkBoxContent" style="max-width: 390px;overflow: hidden;text-overflow: ellipsis;padding-right: 40px;"></span>
                                    <ul class="wptm_select_box">
                                        <li class="data_all" data-value="all_value"><input type="checkbox"/>
                                            <?php esc_attr_e('All Value', 'wptm'); ?></li>
                                    </ul>
                                </div>
                                <div class="option minValue rangerValue">
                                    <input class="" type="number"
                                           placeholder="<?php esc_attr_e('Min', 'wptm'); ?>" value="">
                                </div>
                                <div class="option maxValue rangerValue">
                                    <input class="" type="number"
                                           placeholder="<?php esc_attr_e('Max', 'wptm'); ?>" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="pagination_table">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Pagination', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <div class="control-group"
                         style="display: flex;margin-bottom: 25px;margin-top: 10px;align-items: center;">
                        <label id="jform_enable_pagination-lbl" for="jform_enable_pagination"
                               style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                            <?php esc_attr_e('Enable Pagination', 'wptm'); ?> :
                        </label>
                        <input class="switch-button"
                               id="enable_pagination" type="checkbox" value="">
                    </div>
                    <label id="jform_limit_rows-lbl" for="jform_limit_rows" style="margin-bottom: 10px">
                        <?php esc_attr_e('Number rows per page', 'wptm'); ?> :
                    </label>
                    <span id="limit_rows" class="popup_select wptm_select_box_before"></span>
                    <ul class="wptm_select_box">
                        <li data-value="0"><?php esc_attr_e('Show All', 'wptm'); ?></li>
                        <li data-value="10"><?php esc_attr_e('10', 'wptm'); ?></li>
                        <li data-value="20"><?php esc_attr_e('20', 'wptm'); ?></li>
                        <li data-value="40"><?php esc_attr_e('40', 'wptm'); ?></li>
                    </ul>
                </div>
            </div>
            <!-- Table header menu -->
            <div id="header_option">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Table header', 'wptm'); ?></span>
                </div>
                <div class="control-group wptm_range_labe_show">
                    <label id="jform_number_first_rows-lbl" for="number_first_rows">
                        <?php esc_attr_e('Number of first rows', 'wptm'); ?> :
                    </label>
                    <span id="number_first_rows" class="popup_select wptm_select_box_before"
                          name="number_first_rows"></span>
                    <ul class="wptm_select_box">
                        <li data-value="1"><?php esc_attr_e('1', 'wptm'); ?></li>
                        <li data-value="2"><?php esc_attr_e('2', 'wptm'); ?></li>
                        <li data-value="3"><?php esc_attr_e('3', 'wptm'); ?></li>
                        <li data-value="4"><?php esc_attr_e('4', 'wptm'); ?></li>
                        <li data-value="5"><?php esc_attr_e('5', 'wptm'); ?></li>
                    </ul>
                </div>
                <div class="control-group wptm_range_labe_show">
                    <!--Freeze first rows-->
                    <label id="jform_freeze_row-lbl" for="jform_freeze_row">
                        <?php esc_attr_e('Freeze header', 'wptm'); ?> :
                    </label>
                    <input class="switch-button"
                           id="freeze_row" type="checkbox" value="">
                </div>
                <div class="illustration_img">
                    <img src="<?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link img
                    echo WP_TABLE_MANAGER_PLUGIN_URL . '/app/admin/assets'; ?>/images/Col-fixed.gif"
                         style="margin-top: 20px">
                </div>
            </div>
            <!-- Columns type menu -->
            <div id="column_type_table">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Columns types options', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <p style="font-size: 15px;"><?php esc_html_e('After change the data type of the column, the old data in the current column may be lost. Be careful!', 'wptm'); ?></p>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <td style="width: 30%"><label for=""><?php esc_attr_e('Column', 'wptm'); ?></label></td>
                                <td style="width: 70%"><label for=""><?php esc_attr_e('Type', 'wptm'); ?></label></td>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--responsive_menu-->
            <div id="responsive_table">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Responsive options', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <label id="jform_responsive_type-lbl" for="jform_responsive_type">
                        <?php esc_attr_e('Responsive Type', 'wptm'); ?> :
                    </label>
                    <span id="responsive_type" class="popup_select wptm_select_box_before"></span>
                    <ul class="wptm_select_box">
                        <li data-value="scroll"><?php esc_attr_e('Scrolling', 'wptm'); ?></li>
                        <li data-value="hideCols"><?php esc_attr_e('Hiding Cols', 'wptm'); ?></li>
                        <li data-value="repeatedHeader"><?php esc_attr_e('Repeated header', 'wptm'); ?></li>
                    </ul>
                </div>
                <div class="control-group scrolling">
                    <div class="illustration_img">
                        <img src="<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link img
                        echo WP_TABLE_MANAGER_PLUGIN_URL . '/app/admin/assets'; ?>/images/Responsive-scroll.gif">
                    </div>
                    <!--Table height-->
                    <label id="jform_table_height-lbl" for="jform_table_height">
                        <?php esc_attr_e('Table height (px)', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini table_height" type="text" name="table_height"
                           value="" size="7" style="width: 100%"/>
                    <!--Freeze first cols-->
                    <label id="jform_freeze_col-lbl" for="jform_freeze_col">
                        <?php esc_attr_e('Freeze first cols', 'wptm'); ?> :
                    </label>
                    <span id="freeze_col" class="popup_select wptm_select_box_before"></span>
                    <ul class="wptm_select_box">
                        <?php for ($i = 0; $i < 6; $i++) { ?>
                            <li data-value="<?php echo esc_attr($i); ?>"><?php echo esc_attr($i); ?></li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="control-group hiding" style="display: none;">
                    <div class="illustration_img">
                        <img src="<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link img
                        echo WP_TABLE_MANAGER_PLUGIN_URL . '/app/admin/assets'; ?>/images/Responsive-hide.gif">
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <td><label for=""><?php esc_attr_e('Column', 'wptm'); ?></label></td>
                                <td><label for=""><?php esc_attr_e('Responsive Priority', 'wptm'); ?></label></td>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="control-group repeatedHeader">
                    <div class="illustration_img">
                        <img src="<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link img
                        echo WP_TABLE_MANAGER_PLUGIN_URL . '/app/admin/assets'; ?>/images/repeated-header.gif">
                    </div>
                    <!--Table breakpoint-->
                    <label id="jform_table_breakpoint-lbl" for="jform_table_breakpoint" class="tooltipster" style="flex-basis: 50%;width: max-content;"
                           title="<?php esc_attr_e('Select a breakpoint value for window size to define when the table will toggle to this responsive mode', 'wptm');?>">
                        <?php esc_attr_e('Responsive breakpoint (px)', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini table_breakpoint" type="text" name="table_breakpoint"
                           value="" size="7" style="width: 100%" placeholder="<?php esc_attr_e('ex: 980', 'wptm'); ?>"/>
                    <!--Table height-->
                    <label id="jform_table_height-lbl2" for="jform_table_height2" class="tooltipster" style="flex-basis: 50%;width: max-content;"
                           title="<?php esc_attr_e('When the responsive mode is activated, depending on the breakpoint value,
                            define a max-height to avoid a very-long table', 'wptm');?>">
                        <?php esc_attr_e('Responsive max-height (px)', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini table_height" type="text" name="table_height"
                           value="" size="7" style="width: 100%"/>
                    <label id="jform_style_repeated-lbl" for="jform_style_repeated" class="tooltipster" style="flex-basis: 50%;width: max-content;"
                           title="<?php esc_attr_e('Apply a default styling for this responsive mode or use the table colors', 'wptm') ?>">
                        <?php esc_attr_e('Responsive mode styling', 'wptm'); ?> :
                    </label>
                    <span id="style_repeated" class="popup_select wptm_select_box_before"></span>
                    <ul class="wptm_select_box">
                        <li data-value="0"><?php esc_attr_e('Default', 'wptm'); ?></li>
                        <li data-value="1"><?php esc_attr_e('Style from table', 'wptm'); ?></li>
                    </ul>
                </div>
            </div>

            <div id="date_menu_cell">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Cell(s) custom date & time formats', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <div>
                        <label id="jform_date_format-lbl" for="jform_date_format">
                            <?php esc_attr_e('Date formats', 'wptm'); ?> <a
                                    href="https://www.phptutorial.net/php-tutorial/php-date/" target="__blank">
                                <?php esc_attr_e('Date format', 'wptm'); ?></a> :
                        </label>
                        <input class="observeChanges input-mini date_formats" type="text" name="date_format"
                               value="" size="7"/>
                    </div>
                </div>
                <div class="control-group">
                    <ul class="select_date_format nfd-pill-container">
                        <?php
                        $listDateFormats = array('Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd.m.Y', 'Y/m/d \a\t g:i A', 'M j, Y @ G:i', 'g:i:s a', 'F, Y', 'F j, Y g:i a');
                        if (!in_array($date_formats, $listDateFormats)) {
                            array_unshift($listDateFormats, $date_formats);
                        }
                        foreach ($listDateFormats as $listDateFormat) {
                            ?>
                            <li class="nfd-format-pill" data-value="<?php echo esc_attr($listDateFormat); ?>">
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- date time
                                echo date($listDateFormat); ?>
                                <span>(<?php echo esc_attr($listDateFormat); ?>)</span></li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div id="date_menu">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Custom date and time formats', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <label id="jform_date_format-lbl" for="jform_date_format">
                        <?php esc_attr_e('Date formats ', 'wptm'); ?> <a
                                href="https://codex.wordpress.org/Formatting_Date_and_Time" target="__blank">
                            <?php esc_attr_e('Date format ', 'wptm'); ?></a> :
                    </label>
                    <input class="observeChanges input-mini" type="text" name="date_format"
                           id="date_format" value="" size="7"/>
                </div>
                <div class="control-group">
                    <ul class="select_date_format nfd-pill-container">
                        <?php
                        $listDateFormats = array('Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd.m.Y', 'Y/m/d \a\t g:i A', 'M j, Y @ G:i', 'g:i:s a', 'F, Y', 'F j, Y g:i a');
                        if (!in_array($date_formats, $listDateFormats)) {
                            array_unshift($listDateFormats, $date_formats);
                        }
                        foreach ($listDateFormats as $listDateFormat) {
                            ?>
                            <li class="nfd-format-pill" data-value="<?php echo esc_attr($listDateFormat); ?>">
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- date time
                                echo date($listDateFormat); ?>
                                <span>(<?php echo esc_attr($listDateFormat); ?>)</span></li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div id="curency_menu_cell">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Cell(s) custom currencies symbol', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <div>
                        <label id="jform_symbol_position-lbl" for="jform_symbol_position">
                            <?php esc_attr_e('Symbol position ', 'wptm'); ?>:
                        </label>
                        <span class="popup_select wptm_select_box_before symbol_position"></span>
                        <ul class="wptm_select_box">
                            <li data-value="0">Before</li>
                            <li data-value="1">After</li>
                        </ul>
                    </div>

                    <div>
                        <label id="jform_currency_symbol-lbl" for="jform_currency_symbol">
                            <?php esc_attr_e('Currency symbol(s)', 'wptm'); ?> :
                        </label>
                        <input class="observeChanges input-mini currency_symbol" type="text" name="currency_symbol"
                               value="" size="7"/>
                    </div>

                </div>
                <div class="control-group">
                    <ul class="select_curency_menu nfd-pill-container">
                        <li class="nfd-format-pill" data-currency_sym="$" data-symbol_position="0">
                            United States dollar</li>
                        <li class="nfd-format-pill" data-currency_sym="AUD" data-symbol_position="1">
                            Australian Dollar</li>
                        <li class="nfd-format-pill" data-currency_sym="Af" data-symbol_position="1">
                            Afghan Afghani</li>
                        <li class="nfd-format-pill" data-currency_sym="Lek" data-symbol_position="0">
                            Albanian Lek</li>
                        <li class="nfd-format-pill" data-currency_sym="din" data-symbol_position="0">
                            Algerian Dinar</li>
                        <li class="nfd-format-pill" data-currency_sym="$" data-symbol_position="0">
                            Argentine Peso</li>
                        <li class="nfd-format-pill" data-currency_sym="Afl" data-symbol_position="0">
                            Armenian Dram</li>
                        <li class="nfd-format-pill" data-currency_sym="৳" data-symbol_position="0">
                            Bangladeshi Taka</li>
                        <li class="nfd-format-pill" data-currency_sym="р." data-symbol_position="1">
                            Ruble</li>
                        <li class="nfd-format-pill" data-currency_sym="Nu." data-symbol_position="0">
                            Bhutanese Ngultrum</li>
                        <li class="nfd-format-pill" data-currency_sym="KM" data-symbol_position="0">
                            Bosnia-Herzegov...onvertible Mark</li>
                        <li class="nfd-format-pill" data-currency_sym="R$" data-symbol_position="0">
                            Brazilian Real</li>
                        <li class="nfd-format-pill" data-currency_sym="£" data-symbol_position="0">
                            British Pound Sterling</li>
                        <li class="nfd-format-pill" data-currency_sym="lev" data-symbol_position="1">
                            Bulgarian Lev</li>
                        <li class="nfd-format-pill" data-currency_sym="₡" data-symbol_position="0">
                            Costa Rican Colon</li>
                        <li class="nfd-format-pill" data-currency_sym="€" data-symbol_position="0">
                            Euro</li>
                        <li class="nfd-format-pill" data-currency_sym="vnd" data-symbol_position="1">
                            <?php echo 'Vietnamese dong'; ?></li>
                        <li class="nfd-format-pill" data-currency_sym="₱" data-symbol_position="0">
                            <?php echo 'Philippine Peso'; ?></li>
                        <li class="nfd-format-pill" data-currency_sym="$" data-symbol_position="0">
                            <?php echo 'Singapore Dollar'; ?></li>
                        <li class="nfd-format-pill" data-currency_sym="Rp" data-symbol_position="0">
                            <?php echo 'Indonesian Rupiah'; ?></li>
                        <li class="nfd-format-pill" data-currency_sym="฿" data-symbol_position="0">
                            <?php echo 'Thai Baht'; ?></li>
                        <li class="nfd-format-pill" data-currency_sym="¥" data-symbol_position="0">
                            <?php echo 'Chinese Yuan'; ?></li>
                        <li class="nfd-format-pill" data-currency_sym="¥" data-symbol_position="0">
                            <?php echo 'Japanese Yen'; ?></li>
                    </ul>
                </div>
            </div>

            <div id="curency_menu">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Currency options', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <label id="jform_symbol_position-lbl" for="jform_symbol_position">
                        <?php esc_attr_e('Symbol position ', 'wptm'); ?> :
                    </label>
                    <span id="symbol_position" class="popup_select wptm_select_box_before"></span>
                    <ul class="wptm_select_box">
                        <li data-value="0"><?php esc_attr_e('Before', 'wptm'); ?></li>
                        <li data-value="1"><?php esc_attr_e('After', 'wptm'); ?></li>
                    </ul>

                    <label id="jform_currency_symbol-lbl" for="jform_currency_symbol">
                        <?php esc_attr_e('Currency Symbol(s) ', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" type="text" name="currency_symbol"
                           id="currency_symbol" value="" size="7"/>
                </div>
                <div class="control-group">
                    <ul class="select_curency_menu nfd-pill-container">
                        <li class="nfd-format-pill" data-currency_sym="$" data-symbol_position="0">
                            <?php esc_attr_e('Dollar', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="AUD" data-symbol_position="1">
                            <?php esc_attr_e('Australian Dollar', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="Af" data-symbol_position="1">
                            <?php esc_attr_e('Afghan Afghani', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="Lek" data-symbol_position="0">
                            <?php esc_attr_e('Albanian Lek', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="din" data-symbol_position="0">
                            <?php esc_attr_e('Algerian Dinar', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="$" data-symbol_position="0">
                            <?php esc_attr_e('Argentine Peso', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="Afl" data-symbol_position="0">
                            <?php esc_attr_e('Armenian Dram', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="৳" data-symbol_position="0">
                            <?php esc_attr_e('Bangladeshi Taka', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="р." data-symbol_position="1">
                            <?php esc_attr_e('Ruble', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="Nu." data-symbol_position="0">
                            <?php esc_attr_e('Bhutanese Ngultrum', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="KM" data-symbol_position="0">
                            <?php esc_attr_e('Bosnia-Herzegov...onvertible Mark', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="R$" data-symbol_position="0">
                            <?php esc_attr_e('Brazilian Real', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="£" data-symbol_position="0">
                            <?php esc_attr_e('British Pound Sterling', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="lev" data-symbol_position="1">
                            <?php esc_attr_e('Bulgarian Lev', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="₡" data-symbol_position="0">
                            <?php esc_attr_e('Costa Rican Colon', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="€" data-symbol_position="0">
                            <?php esc_attr_e('Euro', 'wptm'); ?></li>
                        <li class="nfd-format-pill" data-currency_sym="vnd" data-symbol_position="1">
                            <?php esc_attr_e('vnd', 'wptm'); ?></li>
                    </ul>
                </div>
            </div>

            <div id="decimal_menu_cell">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Cell(s) custom number formats', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <div>
                        <label id="jform_decimal_symbol-lbl" for="jform_decimal_symbol">
                            <?php esc_attr_e('Decimal symbol', 'wptm'); ?> :
                        </label>
                        <input class="observeChanges input-mini decimal_symbol" type="text" name="decimal_symbol"
                               readonly value="" size="7"/>
                    </div>

                    <div>
                        <label id="jform_decimal_count-lbl" for="jform_decimal_count">
                            <?php esc_attr_e('Decimal count', 'wptm'); ?> :
                        </label>
                        <input class="observeChanges input-mini decimal_count" name="decimal_count"
                               value="" size="7" type="number" min="0"/>
                    </div>

                    <div>
                        <label id="jform_thousand_symbol-lbl" for="jform_thousand_symbol">
                            <?php esc_attr_e('Thousand symbol', 'wptm'); ?> :
                        </label>
                        <input class="observeChanges input-mini thousand_symbol" type="text" name="thousand_symbol"
                               value="" size="7" readonly/>
                    </div>

                </div>
                <div class="control-group">
                    <ul class="select_decimal_menu nfd-pill-container">
                        <li class="nfd-format-pill" data-thousand_symbol="," data-decimal_symbol="."
                            data-decimal_count="2">
                            <?php echo '#,##0.00'; ?><span>(1,234.56)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="," data-decimal_symbol="."
                            data-decimal_count="1">
                            <?php echo '#,##0.0'; ?><span>(1,234.5)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="," data-decimal_symbol="."
                            data-decimal_count="0">
                            <?php echo '#,##0'; ?><span>(1,234)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="." data-decimal_symbol=","
                            data-decimal_count="2">
                            <?php echo '#.##0,00'; ?><span>(1.234,56)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="." data-decimal_symbol=","
                            data-decimal_count="1">
                            <?php echo '#.##0,0'; ?><span>(1.234,5)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="." data-decimal_symbol=","
                            data-decimal_count="0">
                            <?php echo '#.##0'; ?><span>(1.234)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="" data-decimal_symbol=","
                            data-decimal_count="0">
                            <?php echo '###0'; ?><span>(1234)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol=" " data-decimal_symbol=","
                            data-decimal_count="0">
                            <?php echo '# ##0'; ?><span>(1 234)</span></li>
                    </ul>
                </div>
            </div>

            <div id="decimal_menu">
                <div class="popup_top border_top">
                    <span><?php esc_attr_e('Custom Number Format', 'wptm'); ?></span>
                </div>
                <div class="control-group">
                    <label id="jform_decimal_symbol-lbl" for="jform_decimal_symbol">
                        <?php esc_attr_e('Decimal symbol', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" type="text" name="decimal_symbol"
                           id="decimal_symbol" readonly value="" size="7"/>

                    <label id="jform_decimal_count-lbl" for="jform_decimal_count">
                        <?php esc_attr_e('Decimal count ', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" name="decimal_count"
                           id="decimal_count" value="" size="7" type="number" min="0"/>

                    <label id="jform_thousand_symbol-lbl" for="jform_thousand_symbol">
                        <?php esc_attr_e('Thousand symbol ', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" type="text" name="thousand_symbol"
                           id="thousand_symbol" value="" size="7" readonly/>
                </div>
                <div class="control-group">
                    <ul class="select_decimal_menu nfd-pill-container">
                        <li class="nfd-format-pill" data-thousand_symbol="," data-decimal_symbol="."
                            data-decimal_count="2">
                            <?php esc_attr_e('#,##0.00', 'wptm'); ?><span>(1,234.56)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="," data-decimal_symbol="."
                            data-decimal_count="1">
                            <?php esc_attr_e('#,##0.0', 'wptm'); ?><span>(1,234.5)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="," data-decimal_symbol="."
                            data-decimal_count="0">
                            <?php esc_attr_e('#,##0', 'wptm'); ?><span>(1,234)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="." data-decimal_symbol=","
                            data-decimal_count="2">
                            <?php esc_attr_e('#.##0,00', 'wptm'); ?><span>(1.234,56)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="." data-decimal_symbol=","
                            data-decimal_count="1">
                            <?php esc_attr_e('#.##0,0', 'wptm'); ?><span>(1.234,5)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="." data-decimal_symbol=","
                            data-decimal_count="0">
                            <?php esc_attr_e('#.##0', 'wptm'); ?><span>(1.234)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol="" data-decimal_symbol=","
                            data-decimal_count="0">
                            <?php esc_attr_e('###0', 'wptm'); ?><span>(1234)</span></li>
                        <li class="nfd-format-pill" data-thousand_symbol=" " data-decimal_symbol=","
                            data-decimal_count="0">
                            <?php esc_attr_e('# ##0', 'wptm'); ?><span>(1 234)</span></li>
                    </ul>
                </div>
            </div>
            <div id="google_sheets_menu">
                <div class="control-group">
                    <label id="jform_spreadsheet_url-lbl" for="jform_spreadsheet_url" style="margin-bottom: 10px;">
                        <?php esc_attr_e('Spreadsheet link', 'wptm'); ?>
                    </label>
                    <hr style="margin: 15px 0 20px;"/>
                    <label id="jform_excel_url-lbl" for="jform_excel_url" style="margin-bottom: 10px;font-size: 18px;">
                        <?php esc_attr_e('Remote Excel file link', 'wptm'); ?>
                    </label>
                    <input class="observeChanges input-mini" type="text" name="spreadsheet_url"
                           style="font-size: 0.55em;" placeholder="<?php esc_attr_e('https://www.domain.com/file.xlsx', 'wptm'); ?>"
                           id="spreadsheet_url" value="" size="7"/>
                </div>

                <div class="control-group select_sheet">
                    <label id="jform_select-sheet-lbl" for="jform_select_sheet" style="margin: 5px 0;width: max-content;"  class="tooltipster"
                           title="<?php esc_attr_e('Select the sheet number of the google sheet file to sync', 'wptm');?>">
                        <?php esc_attr_e('Select sheet', 'wptm'); ?>
                    </label>
                    <span id="select_sheet" class="popup_select wptm_select_box_before"
                          value="1"><?php esc_attr_e('First sheet', 'wptm'); ?></span>
                    <ul class="wptm_select_box">
                        <li data-value="1"><?php esc_attr_e('First sheet', 'wptm'); ?></li>
                        <li data-value="2"><?php esc_attr_e('Second sheet', 'wptm'); ?></li>
                        <li data-value="3"><?php esc_attr_e('Third sheet', 'wptm'); ?></li>
                        <?php for ($i = 4; $i <= 40; $i++) :?>
                            <li data-value="<?php echo esc_attr($i); ?>"><?php echo esc_attr($i . 'th sheet'); ?></li>
                        <?php endfor;?>
                    </ul>
                </div>

                <div class="control-group" style="line-height: normal; display: flex; align-items: center">
                    <input type="button" class="wptm_active" id="fetch_google"
                           value="<?php esc_attr_e('Fetch data', 'wptm'); ?>">
                    <a type="button" href="index.php?page=wptm-foldertree&TB_iframe=true&width=500&height=380"
                       class="thickbox button wptm_no_active"
                       id="fetch_browse"> <?php esc_attr_e('Browse server', 'wptm'); ?></a>
                    <div class="lds-ring wptm_hiden">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <div class="popup_notification wptm_hiden"></div>
                </div>
                <div class="control-group"
                     style="display: flex;margin-bottom: 10px;margin-top: 10px;align-items: center;">
                    <label id="jform_spreadsheet_style-lbl" for="jform_spreadsheet_style"
                           style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                        <?php esc_attr_e('Fetch Style', 'wptm'); ?> :
                    </label>
                    <input class="switch-button"
                           id="spreadsheet_style" type="checkbox" value="">
                </div>

                <div class="control-group" style="display: flex;margin-bottom: 10px;align-items: center;">
                    <label id="jform_auto_sync-lbl" for="jform_auto_sync"
                           style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                        <?php esc_attr_e('Auto Sync', 'wptm'); ?> :
                    </label>
                    <input class="switch-button"
                           id="auto_sync" type="checkbox" value="">
                </div>

                <div class="control-group push-notification-group" style="margin-bottom: 5px;align-items: center;">
                    <div class="control-group" style="display: flex;align-items: center;margin-bottom: 5px;">
                        <label id="jform_auto_push-lbl" for="jform_auto_push"
                               style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                            <?php esc_attr_e('Push synchronization', 'wptm'); ?> :
                        </label>
                        <input class="switch-button"
                               id="auto_push" type="checkbox" value="">
                    </div>

                    <div>
                        <label id="jform_google_url-lbl" for="jform_google_url"
                               title="<?php esc_attr_e('Code to include from your Google Sheets using the menu: Tools > Script editor.
                                Push notification is executing a synchronization every time a modification on the Google Sheets is made', 'wptm');?>"
                               style="-webkit-flex-basis: 50%;flex-basis: 50%; display: inline-block;" class="tooltipster">
                            <?php esc_attr_e('Google Sheets push notification', 'wptm'); ?> :
                        </label>
                        <span class="button wptm_icon_tables copy_text tooltip"></span>
                        <span class="wptm_copied"><?php esc_attr_e('Copied', 'wptm'); ?></span>
                        <textarea disabled name="google_url" size="7" style="height: 100px;width: 100%;min-width: 300px;font-size: 0.55em;"
                                  class="inputbox input-block-level observeChanges input-mini wptm_copy_text_content">
                            <?php esc_attr_e('No function ', 'wptm'); ?>
                        </textarea>
                        <input class="observeChanges input-mini" type="text" name="google_url"
                               style="display: none;width: 80%;min-width: 300px;font-size: 0.55em;pointer-events: none;" placeholder="<?php esc_attr_e('Link ', 'wptm'); ?>"
                               id="google_url" value="" size="7"/>
                        <label>
                            <a class="link_document" target="_blank" href="https://www.joomunited.com/wordpress-documentation/wp-table-manager/263-wp-table-manager-excel-google-sheets-sync#toc-5-real-time-synchronization-with-google-sheet-"><?php esc_attr_e('How-to documentation', 'wptm'); ?></a>
                        </label>
                    </div>
                </div>
            </div>

            <div id="csv_menu">
                <div class="control-group">
                    <label id="jform_import_style-lbl" for="jform_import_style" style="margin-bottom: 10px;font-size: 18px;">
                        <?php esc_attr_e('CSV file single import', 'wptm'); ?>
                    </label>
                    <div class="progress progress-striped active" role="progressbar" style="display: none;"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <div class="bar progress-bar-success data-dz-uploadprogress" style="width:0%;"
                             data-dz-uploadprogress></div>
                    </div>

                    <div>
                        <a title="Import Excel sheet, import just the data or sheet data + style" href="javascript:void(0);" id="procCsv"
                           class="pull-left nephritis-flat-button button wptm_active procExcelCsv procCsv"><?php esc_attr_e('Select CSV file', 'wptm') ?></a>
                        <div class="loading-page">
                            <div class="counter">
                                <p class="loading"></p>
                                <h5>0%</h5>
                                <hr/>
                            </div>
                        </div>
                    </div>
                </div>
                <hr style="margin: 5px 0;"/>
                <div class="control-group">
                    <label id="jform_csv_url-lbl" for="jform_csv_url" style="margin-bottom: 10px;font-size: 18px;">
                        <?php esc_attr_e('Remote CSV file link', 'wptm'); ?>
                    </label>
                    <input class="observeChanges input-mini" type="text" name="csv_url"
                           style="font-size: 0.55em;" placeholder="<?php esc_attr_e('https://www.domain.com/file.csv', 'wptm'); ?>"
                           id="csv_url" value="" size="7"/>
                    <i class="material-icons" style="display: none; color: red; font-size: 20px;" title="<?php esc_attr_e('Empty link', 'wptm') ?>">
                        error_outline
                    </i>
                </div>
                <div class="control-group" style="line-height: normal; display: flex; align-items: center">
                    <input type="button" class="wptm_active" id="fetch_csv"
                           value="<?php esc_attr_e('Fetch data', 'wptm'); ?>">
                    <a type="button" href="index.php?page=wptm-foldertree&type=csv&TB_iframe=true&width=500&height=380"
                       class="thickbox button wptm_no_active"
                       id="fetch_browse_csv"> <?php esc_attr_e('Browse server', 'wptm'); ?></a>
                    <div class="lds-ring wptm_hiden">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <div class="popup_notification wptm_hiden"></div>
                </div>

                <div class="control-group" style="display: flex;margin-bottom: 10px;align-items: center;">
                    <label id="jform_auto_sync_csv-lbl" for="jform_auto_sync_csv"
                           style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                        <?php esc_attr_e('Auto Sync', 'wptm'); ?> :
                    </label>
                    <input class="switch-button"
                           id="auto_sync_csv" type="checkbox" value="">
                </div>
            </div>

            <div id="onedrive_menu">
                <div class="control-group">
                    <label id="jform_onedrive_url-lbl" for="jform_onedrive_url" style="margin-bottom: 10px;">
                        <?php esc_attr_e('OneDrive link', 'wptm'); ?> :
                    </label>
                    <input class="observeChanges input-mini" type="text" name="onedrive_url"
                           style="font-size: 0.55em;" placeholder="<?php esc_attr_e('https://www.domain.com/file.xlsx', 'wptm'); ?>"
                           id="onedrive_url" value="" size="7"/>
                </div>
                <div class="control-group select_sheet">
                    <label id="jform_select-sheet-lbl" for="jform_select_sheet" style="margin: 5px 0;width: max-content;"  class="tooltipster"
                           title="<?php esc_attr_e('Select the sheet number of the oneDrive file to sync', 'wptm');?>">
                        <?php esc_attr_e('Select sheet', 'wptm'); ?>
                    </label>
                    <span id="select_sheet" class="popup_select wptm_select_box_before"
                          value="1"><?php esc_attr_e('First sheet', 'wptm'); ?></span>
                    <ul class="wptm_select_box">
                        <li data-value="1"><?php esc_attr_e('First sheet', 'wptm'); ?></li>
                        <li data-value="2"><?php esc_attr_e('Second sheet', 'wptm'); ?></li>
                        <li data-value="3"><?php esc_attr_e('Third sheet', 'wptm'); ?></li>
                        <?php for ($i = 4; $i <= 40; $i++) :?>
                            <li data-value="<?php echo esc_attr($i); ?>"><?php echo esc_attr($i . 'th sheet'); ?></li>
                        <?php endfor;?>
                    </ul>
                </div>
                <div class="control-group" style="line-height: normal; display: flex; align-items: center">
                    <input type="button" class="wptm_active" id="fetch_google"
                           value="<?php esc_attr_e('Fetch data', 'wptm'); ?>">
                    <div class="lds-ring wptm_hiden">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <div class="popup_notification wptm_hiden"></div>
                </div>

                <div class="control-group"
                     style="display: flex;margin-bottom: 10px;margin-top: 10px;align-items: center;">
                    <label id="jform_onedrive_style-lbl" for="jform_onedrive_style"
                           style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                        <?php esc_attr_e('Fetch Style', 'wptm'); ?> :
                    </label>
                    <input class="switch-button"
                           id="onedrive_style" type="checkbox" value="">
                </div>

                <div class="control-group" style="display: flex;margin-bottom: 10px;align-items: center;">
                    <label id="jform_auto_sync_onedrive-lbl" for="jform_auto_sync_onedrive"
                           style="-webkit-flex-basis: 50%;flex-basis: 50%;">
                        <?php esc_attr_e('Auto Sync', 'wptm'); ?> :
                    </label>
                    <input class="switch-button"
                           id="auto_sync_onedrive" type="checkbox" value="">
                </div>
            </div>

            <div class="control-group" id="import_excel">
                <label id="jform_import_style-lbl" for="jform_import_style" style="margin-bottom: 10px;font-size: 18px;">
                    <?php esc_attr_e('Excel file single import', 'wptm'); ?>
                </label>
                <div class="progress progress-striped active" role="progressbar" style="display: none;"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="bar progress-bar-success data-dz-uploadprogress" style="width:0%;"
                         data-dz-uploadprogress></div>
                </div>
                <span id="import_style" class="popup_select wptm_select_box_before"
                      value="0"><?php esc_attr_e('Data only', 'wptm'); ?></span>
                <ul class="wptm_select_box">
                    <li data-value="0"><?php esc_attr_e('Data only', 'wptm'); ?></li>
                    <li data-value="1"><?php esc_attr_e('Data + styles', 'wptm'); ?></li>
                </ul>
                <div class="control-group">
                    <label id="jform_select_sheet_import-lbl" for="jform_select_sheet_import" style="margin: 5px 0;width: max-content;"  class="tooltipster"
                           title="<?php esc_attr_e('Select the sheet number of the excel file to import', 'wptm');?>">
                        <?php esc_attr_e('Select sheet', 'wptm'); ?>
                    </label>
                    <span id="select_sheet_import" class="popup_select wptm_select_box_before"
                          value="1"><?php esc_attr_e('First sheet', 'wptm'); ?></span>
                    <ul class="wptm_select_box">
                        <li data-value="1"><?php esc_attr_e('First sheet', 'wptm'); ?></li>
                        <li data-value="2"><?php esc_attr_e('Second sheet', 'wptm'); ?></li>
                        <li data-value="3"><?php esc_attr_e('Third sheet', 'wptm'); ?></li>
                        <?php for ($i = 4; $i <= 40; $i++) :?>
                            <li data-value="<?php echo esc_attr($i); ?>"><?php echo esc_attr($i . 'th sheet'); ?></li>
                        <?php endfor;?>
                    </ul>
                </div>
                <div>
                    <a title="Import Excel sheet, import just the data or sheet data + style" href="javascript:void(0);" id="procExcel"
                       class="pull-left nephritis-flat-button button wptm_active procExcelCsv"><?php esc_attr_e('Select Excel file', 'wptm') ?></a>
                    <div class="loading-page">
                        <div class="counter">
                            <p class="loading"></p>
                            <h5>0%</h5>
                            <hr/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="control-group" id="padding_border">
                <div class="control-group">
                    <div class="popup_top border_top">
                        <span><?php esc_attr_e('Padding', 'wptm'); ?>:</span>
                    </div>
                    <div style="height: 170px; width: 280px; border: 1px solid #ffffff; margin: 0 auto; position: relative; opacity: 0.8;">
                        <div style="height: 80px; width: 80px; border: 1px dashed #ffffff; margin: 45px auto; text-align: center; line-height: 80px;font-size:12px;">
                            <?php esc_attr_e('Lorem Ipsum', 'wptm'); ?>
                        </div>
                        <div class="padding_border_item" style="top: 70px; left: 3px;">
                            <input name="jform[jform_cell_padding_left]"
                                   id="jform_cell_padding_left" class="observeChanges" value="0" type="number" min="0">px
                        </div>
                        <div class="padding_border_item" style="top: 9px; left: 100px;">
                            <input name="jform[jform_cell_padding_top]"
                                   id="jform_cell_padding_top" class="observeChanges" value="0" type="number" min="0">px
                        </div>
                        <div class="padding_border_item" style="top: 70px; right: 3px;">
                            <input name="jform[jform_cell_padding_right]"
                                   id="jform_cell_padding_right" class="observeChanges" value="0" type="number" min="0">px
                        </div>
                        <div class="padding_border_item" style="bottom: 0px; left: 100px;">
                            <input name="jform[jform_cell_padding_bottom]"
                                   id="jform_cell_padding_bottom" class="observeChanges" value="0" type="number"
                                   min="0">px
                        </div>
                    </div>
                </div>
                <div class="control-group">
                    <div class="popup_top border_top">
                        <span><?php esc_attr_e('Cell background radius', 'wptm'); ?>:</span>
                    </div>
                    <div style="height: 170px; width: 280px; border: 1px solid #FFF; margin: 0 auto; position: relative; opacity: 0.8;">
                        <div style="height: 80px; width: 80px; margin: 45px auto; text-align: center; line-height: 80px; border-radius: 5px; background-color: #ffffff;font-size:12px;">
                            Lorem Ipsum
                        </div>
                        <div class="padding_border_item" style="top: 15px; left: 5px">
                            <input name="jform[jform_cell_background_radius_left_top]"
                                   id="jform_cell_background_radius_left_top" class="observeChanges"
                                   value="0">px
                        </div>
                        <div class="padding_border_item" style="top: 15px; right: 3px">
                            <input name="jform[jform_cell_background_radius_right_top]"
                                   id="jform_cell_background_radius_right_top" class="observeChanges"
                                   value="0">px
                        </div>
                        <div class="padding_border_item" style="bottom: 15px; right: 3px">
                            <input name="jform[jform_cell_background_radius_right_bottom]"
                                   id="jform_cell_background_radius_right_bottom" class="observeChanges"
                                   value="0">px
                        </div>
                        <div class="padding_border_item" style="bottom: 15px; left: 5px">
                            <input name="jform[jform_cell_background_radius_left_bottom]"
                                   id="jform_cell_background_radius_left_bottom" class="observeChanges"
                                   value="0">px
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="wptm_customCSS">
            <textarea rows="10" cols="50" name="jform[jform_css]" id="jform_css"></textarea>
            <a id="saveCssbtn" class="wptm_button wptm_done" title="<?php esc_attr_e('Save', 'wptm'); ?>"
               href="javascript:void(0)"><?php esc_attr_e('Save', 'wptm'); ?></a>
            <a id="cancelCssbtn" class="wptm_button wptm_cancel" title="<?php esc_attr_e('Cancel', 'wptm'); ?>"
               href="javascript:void(0)"><?php esc_attr_e('Cancel', 'wptm'); ?></a>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                var wptm_wpColorPicker_time = null;
                var validate_color = true;
                var palette = false;
                // var wptm_wpColorPicker_changed = function(hexcolor){
                //     $(this).val(hexcolor);
                //     $(this).trigger('change');
                // };

                var myOptions = {
                    width: 225,
                    // a callback to fire whenever the color changes to a valid color
                    change: function (event, ui) {
                        var hexcolor = $(this).wpColorPicker('color');
                        $(event.target).removeClass('wptm_color_validate');

                        if (typeof event.originalEvent.type !== 'undefined' && (event.originalEvent.type === 'square' || event.originalEvent.type === 'strip')) {
                            if (wptm_wpColorPicker_time) clearTimeout(wptm_wpColorPicker_time);
                            wptm_wpColorPicker_time = setTimeout(function () {
                                $(this).val(hexcolor);
                                $(this).trigger('change');
                                $(event.target).data('keyup', 1).change();}, 500);
                            validate_color = true;
                        } else {
                            if (!validate_color) {
                                return false;
                            }
                            setTimeout(function () {
                                if ($(event.target).data('keyup') === 1 || palette === true) {
                                    validate_color = true;
                                    $(event.target).val(hexcolor);
                                    $(event.target).trigger('change');
                                    if (palette === true) {
                                        palette = false;
                                        $(event.target).data('keyup', 1).change();
                                    }
                                }
                            }, 300);
                        }
                    },
                    clear: function (e) {
                        $(event.target).siblings('label').find('.wp-color-picker').val('').trigger('change');
                    },
                    create: function() {
                        var $palette_scontainer = $(this).parents('.wp-picker-container').find('.iris-palette-container');

                        $palette_scontainer.find('.iris-palette').unbind('click').on('click', () => {
                            validate_color = true;
                            palette = true;
                        })
                        $(this).on('keyup', function (event) {
                            event.preventDefault();
                            // $(event.target).data('keyup', 1).change();
                            // $(this).data('keyup', 0);#dd3333
                            validate_color = true;
                            $(event.target).removeClass('wptm_color_validate');
                            var value_color = $(this).val();
                            if (event.keyCode === 17 && value_color.length > 5 && (CSS.supports('color', '#' + value_color) || CSS.supports('color', value_color))) {
                                if (value_color.length === 6) {
                                    $(this).val('#' + value_color).trigger('change');
                                }
                            } else if (!CSS.supports('color', $(this).val()) && !CSS.supports('color', '#' + $(this).val())) {
                                validate_color = false;
                                $(event.target).data('keyup', 0).change();
                                $(this).addClass('wptm_color_validate');
                            } else {
                                if (event.keyCode === 13) {
                                    $(event.target).data('keyup', 1).trigger('change');
                                }
                            }
                        })
                        var button_ok = $('<input type="button" style="line-height: 1" class="wptm_active wptm_ripple" value="<?php esc_attr_e('Ok', 'wptm'); ?>">');
                        button_ok.unbind('click').on('click', () => {
                            if (!validate_color) {
                                return false;
                            }
                            $(this).data('keyup', 1).change();
                            $(this).wpColorPicker('close');
                        })
                        $(this).after(button_ok);

                        var button_clear = $('<i class="material-icons reload_search wptm-picker-clear">clear</i>');
                        button_clear.on('click', () => {
                            $(this).parents('.wp-picker-container').find('.wp-color-picker').val('').trigger('change');
                        })
                        $(this).after(button_clear);
                    }
                }

                $('.minicolors').wpColorPicker(myOptions);
                $('.wp-picker-container').find('button.button span.wp-color-result-text').text('');/*fix wptm-color-picker text in wp version 5.5*/

                // fix can't open color picker on safari
                $('li.background_color .table_option .material-icons').click(function() {
                    $('li.background_color .wp-color-result-text').click();
                });
                $('li.font_color .table_option .material-icons').click(function() {
                    $('li.font_color .wp-color-result-text').click();
                });
                $('#cell_border .border_color i.material-icons').click(function() {
                    $('#cell_border .border_color .wp-color-result-text').click();
                });
            })

            var idUser = <?php echo json_encode($this->idUser); ?>;
            var wptm_isAdmin = <?php echo (int)current_user_can('manage_options'); ?>;

            var enable_autosave = true;
            var checkTimeOut = true;
            var saveData = [];
            var default_value = {};
            default_value.date_formats = '<?php echo esc_attr($date_formats);?>';
            default_value.symbol_position = '<?php echo esc_attr($symbol_position);?>';
            default_value.currency_symbol = '<?php echo esc_attr($currency_sym);?>';
            default_value.decimal_symbol = '<?php echo esc_attr($decimal_sym);?>';
            default_value.decimal_count = '<?php echo esc_attr($decimal_count);?>';
            default_value.thousand_symbol = '<?php echo esc_attr($thousand_sym);?>';
            default_value.enable_tooltip = '1';
            wptmOpenHyperlink = '<?php echo esc_attr(isset($this->params['openHyperlink']) ? $this->params['openHyperlink'] : '1');?>';
            default_value.enable_import_excel = '<?php echo esc_attr($this->params['enable_import_excel']);?>';
            default_value.export_excel_format = '<?php echo esc_attr($this->params['export_excel_format']);?>';
            <?php if (isset($this->params['enable_autosave']) && (string)$this->params['enable_autosave'] === '0') : ?>
            enable_autosave = false;
            <?php endif;?>

            <?php
            if (isset($this->id_table) && is_int($this->id_table)) { ?>
            var idTable = <?php echo esc_attr($this->id_table);
            } ?>
        </script>
<?php else :
    wp_nonce_field('option_nonce', 'option_nonce');
    $content = ''; ?>
        <style>
            #over_loadding_open_chart {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                min-height: 360px;
                z-index: 999;
                background-color: #ffffff;
                opacity: 1;
            }
            #over_loadding_open_chart:after {
                content: "";
                width: 90px;
                height: 90px;
                top: 50%;
                position: absolute;
                left: 50%;
                background-image: url("<?php echo esc_url_raw(WP_TABLE_MANAGER_PLUGIN_URL . 'app/admin/assets/images/'); ?>loadingfile.svg");
                background-size: auto 100%;
                background-repeat: no-repeat;
                transform: translateY(-50%);
                color: #FFF;
            }
        </style>
        <div id="mybootstrap" class="wptm-tables">
            <div id="over_loadding_open_chart" class="loadding" style="display: block;"></div>

            <div class="wptm_categories">
                <div class="create_new"><a id="create_new" class="button wptm_active"
                                           href="#"><?php esc_attr_e('Create New', 'wptm'); ?></a></div>
                <div class="cat_list nested dd">
                    <li class="wptm_id_0 dd-item" data-id-category="0">
                        <div class="dd-handle dd3-handle ui-droppable" style="padding-left: 10px; overflow: hidden">
                            <span><?php esc_attr_e('TABLES', 'wptm'); ?></span>
                        </div>
                    </li>
                    <ol id="categorieslist" class="dd-list">
                        <?php
                        if (!empty($this->categories)) {
                            $user_role = new stdClass();
                            $countCategory = count($this->categories);
                            $active = -1;
                            $categories = array();
                            $cid_exist = false;

                            for ($index = 0; $index < $countCategory; $index++) {
                                $categorys_role = new stdClass();
                                $check = false;
                                $role_category = array();
                                //check role
                                $category_role = json_decode($this->categories[$index]->params);
                                if (!empty($category_role)) {
                                    $category_role = $category_role->role;

                                    if ($user_role_wptm['wptm_edit_category']
                                        || ($user_role_wptm['wptm_edit_own_category'] && in_array($this->idUser, (array)$category_role))) {
                                        $check = true;
                                    }
                                    $categorys_role = $category_role;
                                } else {
                                    $categorys_role->{0} = $this->idUser;
                                    if ($user_role_wptm['wptm_edit_category']) {//when category not own
                                        $check = true;
                                    }
                                }

                                if ($index + 1 !== $countCategory) {
                                    $nextlevel = (int)$this->categories[$index + 1]->level;
                                } else {
                                    $nextlevel = 0;
                                }

                                $content .= openItem($this->categories[$index], $check, $this->caninsert);
                                if ($nextlevel > $this->categories[$index]->level) {//have content cat
                                    $content .= '<button class="cat_expand wptm_nestable show" data-action="expand"></button>';
                                    $content .= '<div class="dd-handle dd3-handle ui-droppable"><span class="title folder_name">' . $this->categories[$index]->title . '</span></div>';
                                    $content .= openlist($this->categories[$index]);
                                } elseif ($nextlevel === (int)$this->categories[$index]->level) {//not has content cat, not end cat
                                    $content .= '<div class="dd-handle dd3-handle ui-droppable"><span class="title folder_name">' . $this->categories[$index]->title . '</span></div>';
                                    $content .= closeItem($this->categories[$index]);
                                } else {
                                    $c = '';
                                    $content .= '<div class="dd-handle dd3-handle ui-droppable"><span class="title folder_name">' . $this->categories[$index]->title . '</span></div>';
                                    $c .= closeItem($this->categories[$index]);
                                    $c .= closeList($this->categories[$index]);
                                    $content .= str_repeat($c, $this->categories[$index]->level - $nextlevel);
                                }

                                if ($active === -1 && $check) {//first category active
                                    $cat_active = $this->categories[$index]->id;
                                    $active = 0;
                                }

                                if (isset($this->cid) && $this->categories[$index]->id === $this->cid && $check) {
                                    $cid_exist = true;
                                }

                                $categories[$this->categories[$index]->id] = $this->categories[$index];
                                $categories[$this->categories[$index]->id]->role = $categorys_role;
                                $previouslevel = $this->categories[$index]->level;
                            }
                        }

                        /*fix cat_id_access vs wptm_category_id cookie*/
                        if (!$cid_exist) {
                            $this->cid = isset($cat_active) ? $cat_active : $this->categories[0]->id;
                        }
                        if (!isset($content)) {
                            $content = '';
                        }
                        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The inner variables were esc
                        echo $content;
                        ?>
                    </ol>
                </div>
            </div>

            <div class="wptm_table_list">
                <div class="wptm_toolbar">
                    <div class="wptm_top_toolbar">
                        <div class="category_name">
                            <h2><?php esc_attr_e('WP Table Manager - Table List', 'wptm'); ?></h2></div>
                        <div>
                            <div>
                                <span class="button edit_table tooltip" style="display: none;"></span>
                                <span class="button re_name tooltip" style="display: none;"></span>
                                <span class="button copy tooltip" style="display: none;"></span>
                                <span class="button delete tooltip" style="display: none;"></span>
                                <?php if (current_user_can('wptm_access_database_table')) : ?>
                                    <li class="wptm_select_type_table"><?php esc_attr_e('Table type', 'wptm'); ?></li>
                                    <ul class="nav " id="wptm_select_type_table">
                                        <li data-type="all"><?php esc_attr_e('All', 'wptm'); ?></li>
                                        <li data-type="mysql"><?php esc_attr_e('Database Table', 'wptm'); ?></li>
                                        <li data-type="html"><?php esc_attr_e('Normal Table', 'wptm'); ?></li>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div>
                                <input name="filter[search_drp]" id="wptm-form-search" type="input"
                                       placeholder="<?php esc_attr_e('Search Table…', 'wptm'); ?>">
                                <i class="search_table mi mi-search"></i>
                            </div>
                        </div>
                    </div>
                    <div class="folder_path">
                        <div data-id="0"><span><?php esc_attr_e('TABLES', 'wptm'); ?></span></div>
                    </div>
                </div>
                <div class="wptm_list">
                    <table id="header_list_tables">
                        <thead>
                        <tr>
                            <td class="wptm_hiden"></td>
                            <td class="name">
                                <div><?php esc_attr_e('Name', 'wptm'); ?></div>
                            </td>
                            <td class="last_edit" style="text-align: left;">
                                <div><?php esc_attr_e('Last edit', 'wptm'); ?></div>
                            </td>
                            <td class="disable_sort">
                                <div><span><?php esc_attr_e('Shortcode', 'wptm'); ?></span></div>
                            </td>
                        </tr>
                        </thead>
                    </table>
                    <table id="list_tables">
                        <thead>
                        <tr>
                            <td class="wptm_hiden"></td>
                            <td class="name"><?php esc_attr_e('Name', 'wptm'); ?></td>
                            <td class="last_edit"
                                style="text-align: left;padding-left: 30px;"><?php esc_attr_e('Last edit', 'wptm'); ?></td>
                            <td class="disable_sort"><?php esc_attr_e('Shortcode', 'wptm'); ?></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $listTable = array();
                        if (!empty($this->cid)) {
                            Application::getInstance('Wptm');
                            $modelTables = $this->getModel('tables');

                            if (!current_user_can('wptm_access_database_table')) {
                                $typeTable = new stdClass();
                                $typeTable->type = 'html';
                                $this->tables = $modelTables->getItems($this->cid, $typeTable);
                            } else {
                                $this->tables = $modelTables->getItems($this->cid);
                            }

                            $count = count($this->tables);
                            $contentTable = '';
                            for ($i = 0; $i < $count; $i++) {
                                $own = false;
                                $class = '';

                                if ($user_role_wptm['wptm_edit_tables']
                                    || ($user_role_wptm['wptm_edit_own_tables'] && (int)$this->tables[$i]->author === (int)$this->idUser)) {
                                    $own = true;
                                }

                                $contentTable .= openItemTable($this->tables[$i], $wptm_list_url, $this->caninsert, $this->charts, $own, $class, $open_table);
                                $user = get_userdata((int)$this->tables[$i]->author);
                                if ($user !== false) {
                                    $this->tables[$i]->author_name = $user->user_nicename;
                                }
                                $listTable[$this->tables[$i]->id] = $this->tables[$i];
                            }
                        } else {
                            $contentTable = '';
                        }

                        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The inner variables were esc
                        echo $contentTable;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="wptm_popup">
                <i class="material-icons colose_popup">
                    close
                </i>
                <div class="content"></div>
            </div>
            <div id="over_popup">
            </div>
            <div id="content_popup_hide">
                <div id="submit_button">
                    <span class="wptm_button wptm_cancel"
                          id="popup_cancel"><?php esc_attr_e('Cancel', 'wptm'); ?></span>
                    <span class="wptm_button wptm_done" id="popup_done"><?php esc_attr_e('Done', 'wptm'); ?></span>
                </div>
                <div id="create_new_popup">

                </div>
                <div id="re_name">
                    <div class="control-group">
                        <label id="jform_re_name-lbl" for="jform_re_name">
                            <?php esc_attr_e('Rename Table', 'wptm'); ?>
                        </label>
                        <hr/>
                        <input class="observeChanges input-mini" type="text" name="re_name"
                               value=""/>
                    </div>
                </div>
                <div id="delete_tables">
                    <div class="control-group">
                        <label class="delete_table" for="jform_delete">
                            <?php esc_attr_e('Delete Table', 'wptm'); ?>
                        </label>
                        <hr/>
                        <label class="delete_table_question">
                            <?php esc_attr_e('Are you sure you want to delete this table ?', 'wptm'); ?>
                        </label>
                    </div>
                </div>
                <div id="edit_category">
                    <div class="popup_top border_top">
                        <span><?php esc_attr_e('Add New Category', 'wptm'); ?></span>
                    </div>
                    <div class="control-group">
                        <label id="jform_re_name-lbl" for="jform_re_name">
                            <?php esc_attr_e('Name', 'wptm'); ?>
                        </label>
                        <input class="observeChanges input-mini" type="text" name="re_name" style="width: 100%"
                               value="" placeholder="<?php esc_attr_e('New category', 'wptm'); ?>"/>
                        <label id="jform_parent_cat-lbl" for="jform_parent_cat">
                            <?php esc_attr_e('Parent Category', 'wptm'); ?>
                        </label>
                        <select id="jform_parent_cat" class="popup_select">
                            <option value="0"><?php esc_attr_e('None', 'wptm'); ?></option>
                        </select>
                    </div>
                </div>

                <div id="change_cat">
                    <div class="control-group">
                        <label id="jform_lbl" for="jform_" style="font-weight: bold">
                            <?php esc_attr_e('Edit Category', 'wptm'); ?>
                        </label>
                        <hr/>
                        <label id="jform_rename-lbl" for="jform_re_name">
                            <?php esc_attr_e('Name', 'wptm'); ?>
                        </label>
                        <input class="observeChanges input-mini" type="text" name="re_name"
                               value=""/>
                        <label id="jform_role_cat-lbl" for="jform_role_cat">
                            <?php esc_attr_e('Owner', 'wptm'); ?>
                        </label>
                        <select id="jform_role_cat" class="popup_select" <?php echo $user_role_wptm['change_author'] ? '' : 'disabled'?>>
                            <?php
                            $count = count($this->list_user);
                            for ($i = 0; $i < $count; $i++) :?>
                                <option value="<?php echo esc_attr($this->list_user[$i]['id']);?>"><?php echo esc_attr($this->list_user[$i]['name']);?></option>
                            <?php endfor;?>
                        </select>
                    </div>
                </div>
            </div>
            <div id="right_mouse_menu">
                <div class="edit_table_menu"><span><?php esc_attr_e('Edit', 'wptm'); ?></span></div>
                <div class="rename_table_menu"><span><?php esc_attr_e('Rename', 'wptm'); ?></span></div>
                <div class="copy_table_menu"><span><?php esc_attr_e('Make a copy', 'wptm'); ?></span></div>
                <div class="delete_table_menu"><span><?php esc_attr_e('Delete', 'wptm'); ?></span></div>
            </div>
            <span id="savedInfoTable"><?php esc_attr_e('All modifications were saved', 'wptm'); ?></span>
            <span id="saveErrorTable"><?php esc_attr_e('Error! You have an error.', 'wptm'); ?></span>
            <script>
                var Wptm = {};

                Wptm.cat_active = <?php echo esc_attr($this->cid); ?>;
                Wptm.dategory = <?php echo json_encode($categories); ?>;
                Wptm.idUser = <?php echo esc_attr($this->idUser); ?>;
                Wptm.author_name = "<?php echo esc_attr($currentUser->data->user_login); ?>";
                Wptm.roles = <?php echo json_encode($user_role_wptm); ?>;

                Wptm.tables = <?php echo json_encode($listTable); ?>;

                var list_chart = <?php echo json_encode($this->charts); ?>;
            </script>
            <style>
                .wptm_table_list span.edit_table:hover:after {
                    content: '<?php esc_attr_e('Edit', 'wptm')?>';
                }

                .wptm_table_list span.re_name:hover:after {
                    content: '<?php esc_attr_e('Rename', 'wptm')?>';
                }

                .wptm_table_list span.copy:hover:after {
                    content: '<?php esc_attr_e('Make a copy', 'wptm')?>';
                }

                .wptm_table_list span.delete:hover:after {
                    content: '<?php esc_attr_e('Delete', 'wptm')?>';
                }

                #list_tables .copy_text:hover:after {
                    content: '<?php esc_attr_e('Copy', 'wptm')?>';
                }

                #list_tables .data_source:hover:after {
                    content: '<?php esc_attr_e('Data Source', 'wptm')?>';
                }

                body {
                    position: fixed;
                    width: 100%;
                }
            </style>
<?php endif;
if ($this->caninsert) :
    add_filter('show_admin_bar', '__return_false'); ?>
    <div id="wptm_bottom_toolbar">
        <div class="bottom_left_toolbar">
            <a class="wptm_back_list" href="<?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link opend table when insert
            echo $wptm_list_url . 'noheader=1&caninsert=1'; ?>">
                <?php esc_attr_e('Back to table list', 'wptm'); ?></a>
        </div>

        <div class="bottom_right_toolbar">
            <?php if ((int)$this->id_charts > 0) : ?>
                <a id="inserttable" class="button button-primary button-big" data-type="chart"
                   href="javascript:void(0)"><?php esc_attr_e('Insert This Chart', 'wptm'); ?></a>
            <?php else : ?>
                <a id="inserttable" class="button button-primary button-big" data-type="table"
                   href="javascript:void(0)"><?php esc_attr_e('Insert This Table', 'wptm'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($this->id_charts > 0 && isset($this->id_table)) : ?>
<!--        <div class="over_popup loadding"></div>-->
    <?php endif;?>
    <style>
        html.wp-toolbar {
            padding-top: 0 !important;
        }

        .wptm_table_list, #wptm_chart {
            height: calc(100vh - 60px) !important;
        }

        @media (min-width: 1199px) {
            #categorieslist {
                height: calc(100% - 200px) !important;
            }
        }

        #mybootstrap.wptm-page {
            height: 100%;
        }

        .wptm-page #wptm_chart .wptm_left_content {
            margin: 15px 15px 10px 15px !important;
            display: inline-block !important;
        }

        .wptm-page #wptm_chart .wptm_rightcol {
            display: inline-block !important;
        }

        #wptm_chart .scroll-content {
            width: 100%;
        }

        #mybootstrap.wptm-tables #savedInfoTable,
        #mybootstrap.wptm-tables #saveErrorTable {
            bottom: 50px !important;
        }
        #query-monitor-main {
            display: none !important;
        }
    </style>
    <script>
        var insert_chart = '<?php esc_attr_e('Insert This Chart', 'wptm') ?>';
        var insert_table = '<?php esc_attr_e('Insert This Table', 'wptm') ?>';
    </script>
<?php endif; ?>
        </div>
<?php
/**
 * Render list style color
 *
 * @param array   $value Style value
 * @param integer $order Order number
 *
 * @return string
 */
function renderListStyle($value, $order)
{
    $html = '';
    $html .= '<td class="td_' . $order . '">';
    $html .= '<div class="pane-color-tile" data-tile-header="' . $value[0] . '" data-tile-1="' . $value[1] . '" data-tile-2="' . $value[2] . '" data-tile-footer="' . $value[3] . '">';
    $html .= '<div class="pane-color-tile-header pane-color-tile-band" style="background-color:' . $value[0] . ';"></div>';
    $html .= '<div class="pane-color-tile-1 pane-color-tile-band" style="background-color:' . $value[1] . ';"></div>';
    $html .= '<div class="pane-color-tile-2 pane-color-tile-band" style="background-color:' . $value[2] . ';"></div>';
    $html .= '<div class="pane-color-tile-footer pane-color-tile-band" style="background-color:' . $value[3] . ';"></div>';
    $html .= '</div>';
    $html .= '</td>';

    return $html;
}

/**
 * OpenItem
 *
 * @param object  $category  Category
 * @param boolean $check2    Check
 * @param boolean $caninsert Check caninsert
 *
 * @return string
 */
function openItem($category, $check2, $caninsert)
{
    return '<li class="dd-item ' . ($check2 ? 'hasRole' : '') . ($caninsert ? ' caninsert' : '') . ' dd3-item" data-id-category="' . $category->id . '">';
}

/**
 * OpenItem
 *
 * @param object  $table        Categories
 * @param string  $wptm_ajaxurl Url wptm
 * @param boolean $caninsert    Insert table/chart page
 * @param array   $chartTables  List chart
 * @param boolean $own          User own
 * @param string  $class        Class to add
 * @param string  $open_new_tab Open table in new tab
 *
 * @return string
 */
function openItemTable($table, $wptm_ajaxurl, $caninsert, $chartTables, $own, $class, $open_new_tab)
{
    $content = '';

    if ($own) {
        $content .= '<tr class="dd-item hasRole ' . $table->type . $class . '" data-id-table="' . $table->id . '" data-type="' . $table->type . '"
      data-role="' . (int)$table->author . '" data-position="' . (int)$table->position . '">';
    } else {
        $content .= '<tr class="dd-item ' . $table->type . $class . '" data-id-table="' . $table->id . '" data-type="' . $table->type . '"
      data-role="' . (int)$table->author . '" data-position="' . (int)$table->position . '">';
    }

    $content .= '<td class="indicator wptm_hiden">' . $table->position . '</td>';

    $content .= '<td class="dd-content table_name"><i class="wptm_icon_tables"></i><div>';
    if ($caninsert) {
        if ($own) {
            $url = $wptm_ajaxurl . 'id_table=' . $table->id . '&noheader=1&caninsert=1';
        } else {
            $url = '#';
        }
        if (isset($chartTables[$table->id])) {
            $content .= '<a class="t" href="' . $url . '"><span class="title dd-handle">' . $table->title . '</span></a><i class="hasChart"></i>';
        } else {
            $content .= '<a class="t" href="' . $url . '"><span class="title dd-handle">' . $table->title . '</span></a>';
        }
    } else {
        if ($own) {
            if ($open_new_tab === '0') {
                $content .= '<a class="t" href="' . $wptm_ajaxurl . 'id_table=' . $table->id . '"><span class="title dd-handle">' . $table->title . '</span></a>';
            } else {
                $content .= '<a class="t" href="' . $wptm_ajaxurl . 'id_table=' . $table->id . '" target="_blank"><span class="title dd-handle">' . $table->title . '</span></a>';
            }
        } else {
            $content .= '<a class="t" href="#"><span class="title dd-handle">' . $table->title . '</span></a>';
        }
        if (isset($chartTables[$table->id])) {
            $content .= '<i class="hasChart"></i>';
        }
    }

    if ($table->type === 'mysql') {
        $content .= '<a class="data_source tooltip"></a>';
    }

    $content .= '</div></td>';

    $content .= '<td>' . convertDate($table->modified_time) . '</td>';

    $content .= '<td class="dd-content shortcode"><div><div><span>[wptm id=' . $table->id . ']</span><span class="button wptm_icon_tables copy_text tooltip"></span></div>';
    $content .= '</div></td>';

    $content .= '</tr>';
    return $content;
}

/**
 * CloseItem
 *
 * @param integer $category Categoer
 *
 * @return string
 */
function closeItem($category)
{
    return '</li>';
}

/**
 * Open list category
 *
 * @param integer $category Category
 *
 * @return string
 */
function openlist($category)
{
    return '<ol class="dd-list">';
}

/**
 * Close list category
 *
 * @param integer $category Category
 *
 * @return string
 */
function closelist($category)
{
    return '</ol>';
}

/**
 * Function convert date string to date format
 *
 * @param string $date Date string
 *
 * @return string
 */
function convertDate($date)
{
    if (get_option('date_format', null) !== null) {
        $date = date_create($date);
        $date = date_format($date, get_option('date_format') . ' ' . get_option('time_format'));
    }
    return $date;
}
/**
 * User Role
 *
 * @param array $userRoles All role of user
 *
 * @return array
 */
function userRole($userRoles)
{
    $data =array(
        'wptm_access_category' => false,
        'wptm_edit_category' => false,
        'wptm_edit_own_category' => false,
        'wptm_create_category' => false,
        'wptm_delete_category' => false,
        'wptm_access_database_table' => false,
        'wptm_edit_tables' => false,
        'wptm_edit_own_tables' => false,
        'wptm_create_tables' => false,
        'wptm_delete_tables' => false
    );

    $data = array_merge($data, $userRoles);

    return $data;
}
