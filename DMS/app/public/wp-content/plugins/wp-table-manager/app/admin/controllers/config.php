<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;
use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Form;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class WptmControllerConfig
 */
class WptmControllerConfig extends Controller
{
    /**
     * Save config
     *
     * @return void
     */
    public function saveconfig()
    {
        $model = $this->getModel();

        $form = new Form();
        if (!$form->load('config')) {
            $this->exitStatus(__('error while saving setting', 'wptm'));
        }
        if (!$form->validate()) {
            $this->exitStatus(__('error while saving setting', 'wptm'));
        }
        if (isset($_POST['option_nonce']) && wp_verify_nonce(sanitize_key($_POST['option_nonce']), 'option_nonce')) {
            $datas = $form->sanitize();
            //get additional var
            if (isset($_POST['enable_hightlight'])) {
                $datas['enable_hightlight'] = $_POST['enable_hightlight'];
            }
            if (isset($_POST['tree_hightlight_color'])) {
                $datas['tree_hightlight_color'] = $_POST['tree_hightlight_color'];
            }
            if (isset($_POST['tree_hightlight_font_color'])) {
                $datas['tree_hightlight_font_color'] = $_POST['tree_hightlight_font_color'];
            }
            if (isset($_POST['hightlight_opacity'])) {
                $datas['hightlight_opacity'] = $_POST['hightlight_opacity'];
            }
            if (isset($_POST['wptm_sync_method'])) {
                $datas['wptm_sync_method'] = $_POST['wptm_sync_method'];
            }
            if (isset($_POST['sync_periodicity'])) {
                $datas['sync_periodicity'] = $_POST['sync_periodicity'];
            }

            $model->save($datas);
            $this->exitStatus(true);
        }
        $this->exitStatus(__('error while saving setting', 'wptm'));
    }

    /**
     * Save role user
     *
     * @return void
     */
    public function save()
    {
        if (!isset($_POST['wptm_role_nonce']) || !check_admin_referer('wptm_role_settings', 'wptm_role_nonce') || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $roles       = $wp_roles->role_objects;

        if (!empty($roles)) {
            $post_type_caps =  array(
                'wptm_create_category',
                'wptm_edit_category',
                'wptm_edit_own_category',
                'wptm_delete_category',
                'wptm_create_tables',
                'wptm_edit_tables',
                'wptm_edit_own_tables',
                'wptm_delete_tables',
                'wptm_access_category',
                'wptm_access_database_table'
            );
            foreach ($roles as $user_role => $role) {
                $user_role_caps = Utilities::getInput($user_role, 'POST', 'none');
                $role_i = get_role($user_role);
                foreach ($post_type_caps as $post_cap) {
                    if (isset($user_role_caps[$post_cap]) && $user_role_caps[$post_cap] === 'on') {
                        $role_i->add_cap($post_cap);
                    } else {
                        $role_i->remove_cap($post_cap);
                    }
                }
            }
        }

        if (!isset($_GET['wptm-tab'])) {
            $this->redirect('admin.php?page=wptm-config');
        }
        $this->redirect('admin.php?page=wptm-config#wptm-user-roles');
        wp_die();
    }

    /**
     * Create new row in db_table
     *
     * @return void
     */
    public function setlocalfont()
    {
        $option = Utilities::getInput('option', 'POST', 'none');
        $action = Utilities::getInput('data_action', 'POST', 'none');
        if ($action === 'none') {
            $this->exitStatus(__('error while changing table', 'wptm'));
        }

        $model = $this->getModel();
        $this_insert = $model->setLocalFont($action, $option);
        if (isset($this_insert) && (int)$this_insert > 0) {
            $this->exitStatus(true, array('id' => $this_insert));
        }
    }

    /**
     * Download new row in db_table
     *
     * @return void
     */
    public function downloadFont()
    {
        $fontsUsed = Utilities::getInput('name', 'POST', 'none');

        if (!empty($fontsUsed)) {
            $googleFonts = WptmTablesHelper::downloadFontGoogle($fontsUsed);
        }
        if (isset($googleFonts) && (int)$googleFonts[0] > 0) {
            $this->exitStatus(true, array('data' => $googleFonts, 'text' =>  __('Font has been downloaded to the server', 'wptm')));
        }
        $this->exitStatus(false, array('error' => __('font not download', 'wptm')));
    }

    /**
     * Get list local font
     *
     * @return void
     */
    public function getlocalfont()
    {
        $localfonts = WptmTablesHelper::getlocalfont();
        if (isset($localfonts) && count($localfonts) > 0) {
            $this->exitStatus(true, $localfonts);
        } else {
            $this->exitStatus(false);
        }
    }
}
