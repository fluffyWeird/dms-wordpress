<?php
/**
 * WP table manager
 *
 * @package WP table manager
 * @author  Joomunited
 * @version 2.3
 */

use Joomunited\WPFramework\v1_0_6\Factory;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!wp_verify_nonce($_GET['security'], 'wptm_user')) {
        wp_die(esc_html__('You don\'t have permission to perform this action!', 'wptm'));
    }
}

$cataction = isset($_REQUEST['cataction']) ? wp_unslash(trim($_REQUEST['cataction'])) : '';
// Query the user IDs for this page
$args           = array(
    'fields' => 'all'
);
$wp_user_search = new WP_User_Query($args);
$this->items    = $wp_user_search->get_results();
?>
<div id="<?php echo $this->check === 1 ? 'wptm_category_own' : 'wptm_table_own'?>">
    <div class="content">
        <div class="search_user">
            <input id="content_user" value="" type="text" placeholder="Select User/ Email">
            <a class="button button-primary" id="search_user" href=""><?php esc_attr_e('Search User', 'wptm'); ?></a>
            <span></span>
        </div>

        <a id="save_category_role" class="button button-primary button-large" title="Save" href="javascript:void(0)">Save</a>
        <div class="list_user">
            <ul class="subsubsub">
                <?php
                $role_links = wptm_filter_role_links();
                foreach ($role_links as $role_name => $role_link) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape inside wpfd_filter_role_links()
                    echo '<li class="' . esc_attr($role_name) . '">' . $role_link . '</li>';
                }
                ?>
            </ul>
            <table class="widefat fixed">
                <thead>
                <tr>
                    <th scope="col" id="name" class="manage-column" width="8%">
                        <input type="checkbox" id="select_all" />
                    </th>
                    <th scope="col" id="name" class="manage-column"><span>Name</span></th>
                    <th scope="col" id="username" class="manage-column"><span>Username</span></th>
                    <th scope="col" id="email" class="manage-column"><span>Email</span></th>
                    <th scope="col" id="role" class="manage-column">Role</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (count($this->items)) {
                    global $wp_roles;
                    $roles = $wp_roles->role_objects;
                    foreach ($this->items as $userid => $user_object) {
                        $role_list = array();
                        foreach ($user_object->roles as $role_i) {
                            if (isset($wp_roles->role_names[$role_i])) {
                                $role_list[$role_i] = translate_user_role($wp_roles->role_names[$role_i]);
                            }
                        }

                        if (count($role_list) < 1) {
                            $role_list['none'] = esc_attr_x('None', 'no user roles', 'wptm');
                        }
                        $roles_list = implode(', ', $role_list);

                        echo '<tr class="' . esc_attr($roles_list) . '">';
                        echo '<td>';
                        if ($this->check === 0) {
                            echo '<input type="checkbox" name="cb-selected" class="checkbox' . (int)$user_object->ID
                                . ' checkbox" value="' . (int)$user_object->ID . '"/>';
                        } elseif ($this->check === 1) {
                            echo '<input type="checkbox" name="cb-selected" class="checkbox' . (int)$user_object->ID
                                . ' checkbox" value="' . (int)$user_object->ID . '"/>';
                        }

                        echo '</td>';
                        echo '<td class="name column-name">';
                        echo '<a class="pointer button-select-user" href="#">' . esc_attr($user_object->display_name) . ' </a>';
                        echo '</td>';
                        echo '<td class="username column-username searchArea">';
                        echo '<strong>' . esc_attr($user_object->user_login) . '</strong>';
                        echo '</td>';
                        echo '<td class="email column-email searchArea">' . esc_html($user_object->user_email) . '</td>';
                        echo '<td class="role column-role">' . esc_attr($roles_list) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4"> ';
                    esc_attr_e('No users found.', 'wptm');
                    echo '</td></tr>';
                }
                ?>
                </tbody>
            </table>

        </div>
    </div>
</div>
<?php
/**
 * Filter role links
 *
 * @return array
 */
function wptm_filter_role_links()
{
    $wp_roles = wp_roles();
    $users_of_blog = count_users();

    $total_users = $users_of_blog['total_users'];
    $avail_roles =& $users_of_blog['avail_roles'];
    unset($users_of_blog);
    $role_links = array();
    $role_links['all'] = "<a class=\"current active\" data-role='all'>"
        . sprintf(_nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_users, 'users', 'wptm'), number_format_i18n($total_users))
        . '</a>';
    foreach ($wp_roles->get_names() as $this_role => $name) {
        if (!isset($avail_roles[$this_role])) {
            continue;
        }
        $name1 = translate_user_role($name);
        /* translators: User role name with count */
        $name1 = sprintf(
            __('%1$s <span class="count">(%2$s)</span>', 'wptm'),
            $name1,
            number_format_i18n($avail_roles[$this_role])
        );
        $role_links[$this_role] = '<a class="current" data-role="' . esc_attr($name) . '"> ' . $name1 . '</a>';
    }

    if (!empty($avail_roles['none'])) {
        $name = __('No role', 'wptm');
        /* translators: User role name with count */
        $name = sprintf(
            __('%1$s <span class="count">(%2$s)</span>', 'wptm'),
            $name,
            number_format_i18n($avail_roles['none'])
        );
        $role_links['none'] = '<a class="current" data-role="' . _x('None', 'no user roles', 'wptm') . '">' . $name . '</a>';
    }
    return $role_links;
}
?>

<script type="text/javascript">
    adminurl = "<?php echo esc_url_raw(admin_url()); ?>";
    wptm_ajaxurl = "<?php echo esc_url_raw(Factory::getApplication('wptm')->getAjaxUrl()); ?>";
    var idUser = <?php echo json_encode($this->idUser); ?>;
</script>
