<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 2.3
 */

// No direct access.
defined('ABSPATH') || die();

global $wp_roles;

if (!isset($wp_roles)) {
    //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- to assign values to $wp_roles
    $wp_roles = new WP_Roles();
}
$roles = $wp_roles->role_objects;
$roles_names = $wp_roles->role_names;

?>

    <form id="wptm-role-form" method="post" action="admin.php?page=wptm-config&amp;task=config.save&amp;wptm-tab=roles">
        <div class="ju-role-search">
            <i class="material-icons ju-role-search-icon">search</i>
            <input type="text" class="ju-role-search-input" placeholder="Search role name"/>
        </div>
        <?php
        wp_nonce_field('wptm_role_settings', 'wptm_role_nonce');

        renderListUser($roles_names, $roles);
        ?>

        <p class="submit">
            <input type="submit" name="submit" class="ju-button orange-button submit_form"
                   value="<?php esc_attr_e('Save', 'wptm'); ?>"/>
        </p>
    </form>

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            function search_items(groups, text) {
                if (text !== '') {
                    groups.find('.ju-settings-option-group-parent').each(function () {
                        $(this).addClass('wptm_hiden');
                        var value = $(this).find('h3.ju-heading').text().toLowerCase();
                        if (value.search(text.toLowerCase()) !== -1) {
                            $(this).removeClass('wptm_hiden');
                        }
                    });
                } else {
                    groups.find('.ju-settings-option-group-parent').each(function () {
                        $(this).removeClass('wptm_hiden');
                    });
                }
            }
            $('.ju-role-search-input').on('keyup', function (e) {
                search_items($('#wptm-role-form'), $(this).val());
                return true;
            });

            $('.ju-heading').on('click', function (e) {
                if ($(this).hasClass('collapsed')) {
                    $(this).removeClass('collapsed');
                } else {
                    $(this).addClass('collapsed');
                }
                $(this).next().toggle("slow");
            });
        });
    </script>
<?php
/**
 * Render list user
 *
 * @param array $roles_names Role $wp_roles->role_names
 * @param array $roles       List user group
 *
 * @return void
 */
function renderListUser($roles_names, $roles)
{
    foreach ($roles as $key => $data) {
        $name = $roles_names[$key];
        ?>
        <div class="ju-settings-option-group-parent full-width <?php echo esc_attr($key) ?>">
            <h3 class="ju-heading full-width ju-toggle"><?php echo esc_attr($name) ?></h3>
            <div class="ju-settings-option-group">
                <?php renderListRole($key, $data);?>
            </div>
        </div>
        <?php
    }
}

/**
 * Render list access user
 *
 * @param string $roles User
 * @param object $data  User data
 *
 * @return void
 */
function renderListRole($roles, $data)
{
    $post_type_caps = array(
        'wptm_create_category' => array(__('Create category', 'wptm'), __('Allow users from this group to create table categories', 'wptm')),
        'wptm_edit_category' => array(__('Edit category', 'wptm'), __(' Allow users from this group to edit all table categories', 'wptm')),
        'wptm_edit_own_category' => array(__('Edit own category', 'wptm'), __('Allow users from this group to edit only their own table categories', 'wptm')),
        'wptm_delete_category' => array(__('Delete category', 'wptm'), __('Allow users from this group to delete all table categories', 'wptm')),
        'wptm_create_tables' => array(__('Create tables', 'wptm'), __('Allow users from this group to create new tables', 'wptm')),
        'wptm_edit_tables' => array(__('Edit tables', 'wptm'), __('Allow users from this group to edit all tables', 'wptm')),
        'wptm_edit_own_tables' => array(__('Edit own tables', 'wptm'), __('Allow users from this group to edit only their own tables', 'wptm')),
        'wptm_delete_tables' => array(__('Delete tables', 'wptm'), __('Allow users from this group to delete all tables', 'wptm')),
        'wptm_access_category' => array(__('Access WP Table Manager', 'wptm'), __('Allow users from this group to access to WP Table Manager and its content, once connected to their account', 'wptm')),
        'wptm_access_database_table' => array(__('Access Database tables', 'wptm'), __('Allow users from this group to manage database tables. Warning: as database tables have a direct access to website sensitive content, it should be limited to trusted admin only!', 'wptm')),
//        'wptm_edit_data_in_database_table' => array(__('the right to edit data table', 'wptm'), __('Allow users from this group to edit database tables. Warning: as database tables have a direct access to website sensitive content, it should be limited to trusted admin only!', 'wptm'))
    );

    foreach ($post_type_caps as $post_key => $post_cap) {
        ?>
        <div class="ju-settings-option enable_import_excel">
            <label class="ju-setting-label"  for="wptm-<?php echo esc_html($roles); ?>-<?php echo esc_html($post_key); ?>-edit">
                <span data-toggle="tooltip" data-placement="top" data-original-title="
                <?php
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                esc_html_e($post_cap[1], 'wptm');
                ?>">
                <?php
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Dynamic translate
                esc_html_e($post_cap[0], 'wptm');
                ?>
                </span>
            </label>
            <div class="controls">
                <input id="wptm-<?php echo esc_attr($roles); ?>-<?php echo esc_attr($post_key); ?>-edit"
                       name="<?php echo esc_attr($roles . '[' . $post_key . ']'); ?>" type="checkbox" <?php echo checked(isset($data->capabilities[$post_key]), 1) ? 'checked' : ''; ?> class="switch-button wptm_input">
            </div>
        </div>
    <?php }
}
