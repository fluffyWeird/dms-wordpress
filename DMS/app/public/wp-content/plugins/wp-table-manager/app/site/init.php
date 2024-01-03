<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;

defined('ABSPATH') || die();

$app = Application::getInstance('Wptm');

load_plugin_textdomain('wptm', null, dirname(plugin_basename(WPTM_PLUGIN_FILE)) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'languages');

add_action('media_buttons', 'wptm_button');
/**
 * Add button
 *
 * @return string
 */
function wptm_button()
{
    $app         = Application::getInstance('Wptm');
    $modelConfig = Model::getInstance('configSite');
    $config      = $modelConfig->getConfig();
    if ((int)$config['enable_frontend'] === 1) {
        wp_enqueue_style('wptm-modal', plugins_url('app/admin/assets/css/leanmodal.css', WPTM_PLUGIN_FILE));
        wp_enqueue_script('wptm-modal', plugins_url('app/admin/assets/js/jquery.leanModal.min.js', WPTM_PLUGIN_FILE));
        wp_enqueue_script('wptm-modal-init', plugins_url('app/site/assets/js/leanmodal.init.js', WPTM_PLUGIN_FILE));
        wp_localize_script('wptm-modal-init', 'wptmVars', array('adminurl' => admin_url()));

        $context = "<a href='#wptmmodal' class='button wptmlaunch' id='wptmlaunch' title='WP Table Manager'>"
                    . " <span class='dashicons dashicons-screenoptions' style='line-height: inherit;'></span>" . __('WP Table Manager', 'wptm') . '</a>';
        $context .= "
            <script type='text/javascript'>
                jQuery(document).ready(function($){
            
                   jQuery('.wptmlaunch').wptm_leanModal({ top : 20, beforeShow: function(){jQuery('#wptmmodal').css('height','90%');jQuery('#wptmmodalframe').hide();jQuery('#wptmmodalframe').attr('src',jQuery('#wptmmodalframe').attr('src'));jQuery('#wptm_loader').show(); } });
                   return false;
                });
            </script>
        ";
    } else {
        $context = '';
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content escape above
    return $context;
}
add_action('wp_ajax_Wptm', 'wptm_ajax');
add_action('wp_ajax_nopriv_Wptm', 'wptm_ajax');
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
    if (!defined('WPTM_AJAX')) {
        wptm_init();
    }

    $application = Application::getInstance('Wptm');
    $application->execute($default_task);
}
