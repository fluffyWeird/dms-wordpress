<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Factory;

$icon = array(
    'ok'    => '<div class="controls"><i class="material-icons system-checkbox material-icons-success">check_circle</i></div>',
    'alert' => '<div class="controls"><i class="material-icons system-checkbox material-icons-alert">info</i></div>',
    'info'  => '<div class="controls"><img class="system-checkbox material-icons-info bell" src="' . plugins_url('../../assets/images/icon-notification.png', __DIR__) . '" /></div>'
);

$extension_array = array(
    array(
        'id'      => 'curl',
        'title'   => 'Php_Curl',
        'tooltip' => __('PHP extension Curl is NOT detected. The cache preloading feature will not work (preload a page cache automatically after a cache purge)', 'wptm')
    ),
    array(
        'id'      => 'ctype',
        'title'   => 'ext-ctype',
        'tooltip' => __('PHP extension ext-ctype is NOT detected. The function render table on site will not work', 'wptm')
    ),
    array(
        'id'      => 'zlib',
        'title'   => 'ext-zlib',
        'tooltip' => __('PHP extension ext-zlib is NOT detected. The function to sync with google sheet, or import, export excel file will not work', 'wptm')
    ),
    array(
        'id'      => 'xml',
        'title'   => 'ext-xml',
        'tooltip' => __('PHP extension ext-xml is NOT detected. The function to sync with google sheet, or import, export excel file will not work', 'wptm')
    ),
    array(
        'id'      => 'zip',
        'title'   => 'ext-zip',
        'tooltip' => __('PHP extension ext-zip is NOT detected. The function to sync with google sheet, or import, export excel file will not work', 'wptm')
    ),
    array(
        'id'      => 'mbstring',
        'title'   => 'ext-mbstring',
        'tooltip' => __('PHP extension ext-mbstring is NOT detected. The function to sync with google sheet, or import, export excel file will not work', 'wptm')
    )
);

/**
 * Parse module info.
 * Based on https://gist.github.com/sbmzhcn/6255314
 *
 * @return array
 */
function parsePhpinfo()
{
    ob_start();
    //phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_phpinfo -- Get info modules of phpinfo
    phpinfo(INFO_MODULES);
    $s = ob_get_contents();
    ob_end_clean();
    $s     = strip_tags($s, '<h2><th><td>');
    $s     = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
    $s     = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
    $t     = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, - 1, PREG_SPLIT_DELIM_CAPTURE);
    $r     = array();
    $count = count($t);
    $p1    = '<info>([^<]+)<\/info>';
    $p2    = '/' . $p1 . '\s*' . $p1 . '\s*' . $p1 . '/';
    $p3    = '/' . $p1 . '\s*' . $p1 . '/';
    for ($i = 1; $i < $count; $i ++) {
        if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
            $name = trim($matchs[1]);
            $vals = explode("\n", $t[$i + 1]);
            foreach ($vals as $val) {
                if (preg_match($p2, $val, $matchs)) { // 3cols
                    $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                } elseif (preg_match($p3, $val, $matchs)) { // 2cols
                    $r[$name][trim($matchs[1])] = trim($matchs[2]);
                }
            }
        }
    }

    return $r;
}

$listDisableFunc = ini_get('disable_functions');
$listDisableFuncs = explode(',', $listDisableFunc);
if (!in_array('phpinfo', $listDisableFuncs)) {
    $phpInfo = parsePhpinfo();
}
?>
<style id="wptm-local-fonts-css"></style>
<div class="wrap wptm-config">
    <div class="ju-main-wrapper">
        <div class="ju-left-panel">
            <div class="ju-logo">
                <a href="http://linktoyourwebsite.com" target="_blank" title="Visit my site">
                    <img src="<?php echo esc_url_raw(plugins_url('../../assets/cssJU/images/logo-joomUnited-white.png', __DIR__)); ?>"
                         alt="Your Logo"/>
                </a>
            </div>
            <div class="ju-menu-search">
                <i class="mi mi-search ju-menu-search-icon"></i>
                <input type="text" class="ju-menu-search-input"
                       placeholder="<?php esc_attr_e('Search settings', 'wptm'); ?>"/>
            </div>
            <ul class="tabs ju-menu-tabs">
                <li class="tab">
                    <a href="#wptm_settings" class="link-tab waves-effect waves-light">
                        <i class="material-icons">home</i>
                        <?php esc_attr_e('Main settings', 'wptm'); ?></a></li>
                <li class="tab">
                    <a href="#wptm-user-roles" class="link-tab waves-effect waves-light user-roles">
                        <img src="<?php echo esc_url_raw(plugins_url('../../assets/images/icon-user-roles.svg', __DIR__)); ?>" class="mCS_img_loaded"><?php esc_attr_e('User roles', 'wptm'); ?></a></li>
                <li class="tab">
                    <a href="#wptm_translation" class="link-tab waves-effect waves-light">
                        <i class="material-icons">text_format</i>
                        <?php esc_attr_e('Translation', 'wptm'); ?></a></li>
                <li class="tab">
                    <a href="#wptm_check" class="link-tab waves-effect waves-light">
                        <i class="material-icons">check_circle_outline</i>
                        <?php esc_attr_e('System check', 'wptm'); ?></a></li>
            </ul>
        </div>

        <div class="ju-right-panel">
            <div class="ju-content-wrapper" id="wptm_settings">
                <div class="ju-top-tabs-wrapper">
                    <ul class="tabs ju-top-tabs">
                        <li class="tab active">
                            <a href="#wptm_main_settings"
                               class="link-tab"><?php esc_attr_e('Main settings', 'wptm'); ?></a>
                        </li>
                        <li class="tab">
                            <a href="#wptm_format" class="link-tab"><?php esc_attr_e('Format', 'wptm'); ?></a>
                        </li>
                        <li class="tab">
                            <a href="#wptm_fonts" class="link-tab"><?php esc_attr_e('Fonts', 'wptm'); ?></a>
                        </li>
                    </ul>
                </div>
                <div class="ju-notice-msg ju-notice-success">
                    <p></p>
                    <i class="dashicons dashicons-dismiss ju-notice-close"></i>
                </div>
                <div class="ju-notice-msg ju-notice-error">
                    <p></p>
                    <i class="dashicons dashicons-dismiss ju-notice-close"></i>
                </div>
                <div id="wptm_main_settings" class="tab-pane wptm_show_hiden_option"
                     data-option="enable_import_excel|export_excel_format|alternate_color|hightlight|enable_autosave|open_table|wptm_sync_method|sync_periodicity|enable_frontend|uninstall_delete_files|automatically_calculation|openHyperlink">
                    <h2><?php esc_attr_e('Main settings', 'wptm'); ?></h2>
                </div>
                <div id="wptm_format" class="tab-pane wptm_show_hiden_option"
                     data-option="date_formats|symbol_position|decimal_sym|decimal_count|thousand_sym|currency_sym">
                    <h2><?php esc_attr_e('Format settings', 'wptm'); ?></h2>
                </div>
                <div id="wptm_fonts" class="tab-pane wptm_show_hiden_option"
                     data-option="fonts_google|my_fonts">
                    <h2><?php esc_attr_e('Font settings', 'wptm'); ?></h2>
                </div>
                <?php
                //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- print output from render() of framework
                echo $this->configform;
                ?>
            </div>

            <div class="ju-content-wrapper" id="wptm_translation">
                <h2><?php esc_attr_e('Translation', 'wptm'); ?></h2>
                <div id="wptm-jutranslation-config" class="ju-settings-option full-width">
                    <?php
                    //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- print output from render() of framework
                    echo \Joomunited\WPTableManager\Jutranslation\Jutranslation::getInput();
                    ?>
                </div>
            </div>

            <div class="ju-content-wrapper" id="wptm-user-roles">
                <h2><?php esc_attr_e('User Roles', 'wptm'); ?></h2>

                <div class="ju-content-wrapper full-width">
                    <?php
                    require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin/views/config/tpl/default_role.php';
                    ?>
                </div>
            </div>

            <div class="ju-content-wrapper" id="wptm_check">
                <h2><?php esc_attr_e('System Check Settings', 'wptm'); ?></h2>
                <div class="text-intro">
                    <blockquote>
                        <?php esc_html_e('We have checked your server environment. 
    If you see some warning below it means that some plugin features may not work properly.
    Reload the page to refresh the results', 'wptm') ?>
                    </blockquote>
                </div>
                <div class="php_version">
                    <h3><?php esc_attr_e('PHP Version', 'wptm'); ?></h3>
                    <div class="ju-settings-option full-width">
                        <label class="ju-setting-label system-check-label">
                            <?php esc_html_e('PHP ', 'wptm'); ?>
                            <?php echo esc_html(PHP_VERSION) ?>
                            <?php esc_html_e('version', 'wptm'); ?>
                        </label>
                        <?php
                        if (version_compare(PHP_VERSION, '7.4', '>=')) {
                            //phpcs:ignore WordPress.Security.EscapeOutput -- Echo icon html
                            echo $icon['ok'];
                        } else {
                            //phpcs:ignore WordPress.Security.EscapeOutput -- Echo icon html
                            echo $icon['alert'];
                        }
                        ?>
                    </div>
                    <?php if (version_compare(PHP_VERSION, '7.4.0', '<')) : ?>
                        <p>
                            <?php esc_html_e('Your PHP version is ', 'wptm') ?>
                            <?php echo esc_html(PHP_VERSION) ?>
                            <?php esc_html_e('. to use the function to sync with google sheet, or import, export excel file, to use the function to sync with google sheet, or import, export excel file.
                             Please upgrade to php version 7.4+', 'wptm'); ?>
                        </p>
                    <?php else : ?>
                        <p style="height: auto">
                            <?php esc_html_e('Great ! Your PHP version is ', 'wptm'); ?>
                            <?php echo esc_html(PHP_VERSION) ?>
                            <?php if (version_compare(PHP_VERSION, '7.4.0', '<')) : ?>
                                <?php esc_html_e(', upgrade to PHP 7.4+ version to get an even better performance', 'wptm') ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php
                if (!isset($phpInfo) && !function_exists('get_loaded_extensions')) : ?>
                    <p><?php esc_attr_e('Some server PHP information can’t be checked', 'wptm'); ?></p>
                <?php else :
                    $i = 0;
                    foreach ($extension_array as $v) :
                        ?>
                        <div class="<?php echo esc_attr(ucfirst($v['id'])); ?>">
                            <?php if ($i === 0) : ?>
                                <h3><?php esc_attr_e('Other Check', 'wptm');
                                    $i = 1; ?></h3>
                            <?php endif; ?>
                            <div class="ju-settings-option full-width">
                                <label for="<?php echo esc_attr($v['id']) ?>" class="ju-setting-label system-check-label">
                                    <?php echo esc_attr(ucfirst($v['title'])); ?>
                                </label>
                                <?php
                                $checkother = false;
                                if (function_exists('get_loaded_extensions')) {
                                    $phpModules = get_loaded_extensions();
                                    if (in_array($v['id'], $phpModules)) {
                                        $checkother = true;
                                    }
                                } else {
                                    if (isset($phpInfo[$v['id']])) {
                                        $checkother = true;
                                    }
                                }
                                if ($v['id'] === 'curl') {
                                    //phpcs:ignore WordPress.Security.EscapeOutput -- Echo icon html
                                    echo ($checkother) ? $icon['ok'] : $icon['alert'];
                                } else {
                                    //phpcs:ignore WordPress.Security.EscapeOutput -- Echo icon html
                                    echo ($checkother) ? $icon['ok'] : $icon['info'];
                                }
                                ?>
                            </div>
                            <?php if (!$checkother) : ?>
                                <p><?php echo esc_html($v['tooltip']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>

            <div id="wptm_popup"></div><div id="over_popup"></div>
            <div id="content_popup_hide">
                <div class="google_select_font">
                    <div class="header">
                        <h3><?php esc_attr_e('View google font', 'wptm'); ?></h3>
                        <i class="material-icons colose_popup">close</i>
                    </div>
                    <div class="content">
                        <div class="popup_top">
                            <div class="logo_google">
                                <a>
                                    <img alt="Google" src="<?php echo esc_url_raw(plugins_url('../../assets/images/googlelogo_color.png', __DIR__));?>">Fonts</a>
                            </div>
                        </div>
                        <div class="popup-body">
                            <div>
                                <input id="select_font" class="ju-input wptm_input" value="" type="text"/>
                                <i class="material-icons refresh" style="font-size:24px;color:#5a5a5a;vertical-align: middle"></i>
                            </div>
                            <div class="list">

                            </div>
                            <div style="line-height: 40px">
                                <a style="vertical-align: middle;cursor: pointer" class="material-icons skip_previous"></a>
                                <a style="vertical-align: middle;cursor: pointer" class="material-icons arrow_back_ios"></a>
                                <a style="vertical-align: middle;cursor: pointer" class="material-icons navigate_next"></a>
                                <a style="vertical-align: middle;cursor: pointer" class="material-icons skip_next"></a>
                                <span style="vertical-align: middle">page:</span><span style="vertical-align: middle" id="page"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="font-item collapsed">
                    <span><?php esc_attr_e('Grumpy wizards make toxic brew for the evil Queen and Jack.', 'wptm'); ?></span>
                    <div class="fontname">
                        <span></span>
                        <button class="ggfonts_add fontAdd" tabindex="0" role="button" type="button" data-profiles_id="1">
                            <?php esc_attr_e('Add to Collection', 'wptm'); ?>
                        </button>
                    </div>
                </div>
                <div class="add_new_font control_value">
                    <div class="controls" style="font-size: 16px;font-weight: bold;letter-spacing: 0.53px;color: #404852;">
                        <label class="label_text"><?php esc_attr_e('Font options', 'wptm'); ?></label>
                    </div>
                    <div class="controls">
                        <label class="label_text" for="name_font"><?php esc_attr_e('Name', 'wptm'); ?></label>
                        <div class="controls" style="margin-right: 0;">
                            <input class="ju-input wptm_input" id="name_font" value="" placeholder="<?php esc_attr_e('The name of the font', 'wptm'); ?>">
                        </div>
                    </div>
                    <div class="controls">
                        <label class="label_text" for="name_fallback_font"><?php esc_attr_e('Font Fallback', 'wptm'); ?></label>
                        <div class="controls" style="margin-right: 0;">
                            <input class="ju-input wptm_input" id="name_fallback_font" value="" placeholder="<?php esc_attr_e('The font\'s fallback names', 'wptm'); ?>">
                        </div>
                    </div>
                    <div class="controls font-item-group">
                        <div class="controls">
                            <label class="label_text" for="font_weight"><?php esc_attr_e('Font weight', 'wptm'); ?></label>
                            <div class="controls" style="margin-right: 0;">
                                <select class="font_weight ju-select wptm_input" style="width: 12em;">
                                    <option value="normal" selected="selected">normal</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="300">300</option>
                                    <option value="400">400</option>
                                    <option value="500">500</option>
                                    <option value="600">600</option>
                                    <option value="700">700</option>
                                    <option value="bold">bold</option>
                                    <option value="bolder">bolder</option>
                                    <option value="lighter">lighter</option>
                                </select></div>
                        </div>
                        <div class="controls">
                            <label class="label_text" for="font_style"><?php esc_attr_e('Font style', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <select class="font_style ju-select wptm_input" style="width: 12em;">
                                    <option value="normal" selected="selected">normal</option>
                                    <option value="italic">italic</option>
                                    <option value="oblique">oblique</option>
                                </select></div>
                        </div>

                        <div class="controls"><label class="label_text"><?php esc_attr_e('Font .woff', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <input class="ju-input wptm_input woff" value=""
                                       placeholder="<?php esc_attr_e('Link font file', 'wptm'); ?>">
                                <button class="ju-button orange-button update_file material-icons" value=""
                                        type="button">upload_file</button></div>
                        </div>
                        <div class="controls"><label class="label_text"><?php esc_attr_e('Font .woff2', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <input class="ju-input wptm_input woff2" value=""
                                       placeholder="<?php esc_attr_e('Link font file', 'wptm'); ?>">
                                <button class="ju-button orange-button update_file material-icons" value=""
                                        type="button">upload_file</button></div>
                        </div>
                        <div class="controls"><label class="label_text"><?php esc_attr_e('Font .ttf', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <input class="ju-input wptm_input ttf" value=""
                                       placeholder="<?php esc_attr_e('Link font file', 'wptm'); ?>">
                                <button class="ju-button orange-button update_file material-icons" value=""
                                        type="button">upload_file</button></div>
                        </div>
                        <div class="controls"><label class="label_text"><?php esc_attr_e('Font .eot', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <input class="ju-input wptm_input eot" value=""
                                       placeholder="<?php esc_attr_e('Link font file', 'wptm'); ?>">
                                <button class="ju-button orange-button update_file material-icons" value=""
                                        type="button">upload_file</button></div>
                        </div>
                        <div class="controls"><label class="label_text"><?php esc_attr_e('Font .svg', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <input class="ju-input wptm_input svg" value=""
                                       placeholder="<?php esc_attr_e('Link font file', 'wptm'); ?>">
                                <button class="ju-button orange-button update_file material-icons" value=""
                                        type="button">upload_file</button></div>
                        </div>
                        <div class="controls"><label class="label_text"><?php esc_attr_e('Font .otf', 'wptm'); ?>
                                </label>
                            <div class="controls" style="margin-right: 0;">
                                <input class="ju-input wptm_input otf" value=""
                                       placeholder="<?php esc_attr_e('Link font file', 'wptm'); ?>">
                                <button class="ju-button orange-button update_file material-icons" value=""
                                        type="button">upload_file</button></div>
                        </div>
                    </div>
                    <button class="ju-button orange-button variation" value=""
                            type="button"><?php esc_attr_e('Add font variation', 'wptm'); ?></button>
                    <button class="ju-button wptm_no_active remove" value="" style="min-width: unset"
                            type="button"><?php esc_attr_e('Remove', 'wptm'); ?></button>
                </div>
                <div id="wptm_upload_file">
                    <input type="text" class="upload_file regular-text">
                    <input type="button" class="upload_file-btn button-secondary" value="">
                </div>
                <script type="text/javascript">
                    jQuery(document).ready(function($){
                        $('#wptm_upload_file .upload_file-btn').on('click', function(e, $my_font_button) {
                            e.preventDefault();
                            var image = wp.media({
                                title: 'Upload Image',
                                // mutiple: true if you want to upload multiple files at once
                                multiple: false
                            }).open()
                                .on('select', function(e){
                                    // This will return the selected image from the Media Uploader, the result is an object
                                    var uploaded_image = image.state().get('selection').first();
                                    // We convert uploaded_image to a JSON object to make accessing it easier
                                    // Output to the console uploaded_image
                                    var link_url = uploaded_image.toJSON().url;
                                    // Let's assign the url value to the input field
                                    $my_font_button.parent().find('input').val(link_url).change();
                                });
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>
<?php wp_nonce_field('option_nonce', 'option_nonce'); ?>
<div id="wptm-crontab-url-help">
    <span class="description"><?php echo esc_attr('The Cloud synchronization method. Default is AJAX, advanced user only.', 'wptm');?></span>
    <p style="word-break: break-word; white-space: normal; width: 100%;"><?php echo esc_attr('Cron task command to add to your hoster crontab manager', 'wptm');?></p>
    <p class="input_text">/usr/bin/php <?php echo esc_html(ABSPATH . 'wp-cron.php') ?> >/dev/null 2>&1</p>
</div>
<script type="text/javascript">
    ajaxurl = "<?php echo 'admin-ajax.php?action=Wptm'; ?>";
    var wptm_ajaxurl = "<?php echo  esc_url_raw(admin_url('admin-ajax.php?action=Wptm&')); ?>";
    jQuery(document).ready(function ($) {
        $('[data-toggle="tooltip"]').tooltip();

        var $wptm_main_config = $('#wptm_settings');

        $wptm_main_config.find('.date_formats').closest('.control-group').css('margin-top', '25px');

        if ($("input[name=dropboxKey]").val() != '' && $("input[name=dropboxSecret]").val() != '') {
            $('#dropboxAuthor + .help-block').html('');
        } else {
            $("#dropboxAuthor").attr('type', 'hidden');
        }
        $('.wptm_sync_method .wptm_input').append($('#wptm-crontab-url-help'));
        function showWptmCrontabUrlHelp() {
            if ($('.wptm_sync_method #cron').is(":checked")) {
                $('#wptm-crontab-url-help p').show().fadeIn(200);
            } else {
                $('#wptm-crontab-url-help p').fadeOut(200).hide();
            }
        }
        showWptmCrontabUrlHelp();
        $('#wptm_settings .wptm_sync_method input').on('change', function () {
            showWptmCrontabUrlHelp();
        });

        $('#wptm_settings form.wptmparams').submit(function (event) {
            event.preventDefault();
            var url = wptm_ajaxurl + "task=config.saveconfig";
            var jsonVar = {};
            $('#wptm_settings').find('.ju-settings-option .wptm_input').each(function (i, e) {
                if ($(this).hasClass('switch-button')) {
                    jsonVar[$(this).attr('name')] = $(this).is(":checked") ? 1 : 0;
                } else if ($(this).hasClass('radio')) {
                    jsonVar[$(this).find(':checked').attr('name')] = $(this).find(':checked').val();
                } else {
                    jsonVar[$(this).attr('name')] = $(this).val();
                }
            });
            jsonVar['joomunited_nonce_field'] = $('#joomunited_nonce_field').val();
            jsonVar['option_nonce'] = $('#option_nonce').val();

            $.ajax({
                url: url,
                dataType: "json",
                type: "POST",
                data: jsonVar,
                success: function (datas) {
                    if (datas.response === true) {
                        $('.ju-right-panel .ju-notice-success p').html('<?php esc_html_e('Setting have been saved!', 'wptm'); ?>');
                        $('.ju-right-panel .ju-notice-success').show().fadeIn(200);
                    } else {
                        $('.ju-right-panel .ju-notice-error p').html(datas.response);
                        $('.ju-right-panel .ju-notice-error').show().fadeIn(200);
                    }

                    $('.ju-right-panel .ju-notice-close').click(function () {
                        $(this).closest('.ju-notice-msg').fadeOut(500).hide();
                    });
                },
                error: function (jqxhr, textStatus, error) {
                    bootbox.alert(textStatus + " : " + error);
                },
            });
        });
    });
</script>
