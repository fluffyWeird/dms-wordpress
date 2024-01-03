<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

namespace Joomunited\WP_Table_Manager\Admin\Fields;

use Joomunited\WPFramework\v1_0_6\Field;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

defined('ABSPATH') || die();

/**
 * Class Category
 */
class Config extends Field
{
    /**
     * Display all categories
     *
     * @param array $field Data field
     * @param array $datas Full datas
     *
     * @return string
     */
    public function getfield($field, $datas)
    {
        $attributes = $field['@attributes'];
        $html = '';
        $tooltip = isset($attributes['tooltip']) ? $attributes['tooltip'] : '';
        $html .= '<div class="ju-settings-option ' . $attributes['name'] . '">';
        if (!empty($attributes['label']) && (string)$attributes['label'] !== '' &&
            !empty($attributes['name']) && (string)$attributes['name'] !== ''
        ) {
            $label = (string)$attributes['label'];
            $html .= '<label class="ju-setting-label" for="' . $attributes['name'] . '"';
            if (isset($attributes['tooltip'])) {
                $html .= '><span data-toggle="tooltip" data-placement="top" title="' . esc_attr($attributes['tooltip'], 'wptm') . '"';
                $html .= '>' . esc_attr($label, 'wptm') . '</span></label>';
            } else {
                $html .= '>' . esc_attr($label, 'wptm') . '</label>';
            }
        }
        $html .= '<div class="controls ' . $attributes['name'] . '">';
        if ($attributes['name'] === 'alternate_color') {
            $html .= $this->renderContentAlternate($attributes);
        } elseif ($attributes['name'] === 'hightlight') {
            $html .= $this->renderContentHightlight($field, $datas);
        } elseif ($attributes['name'] === 'fonts_google') {
            $html .= $this->renderContentFontGoogle($attributes);
        } elseif ($attributes['name'] === 'my_fonts') {
            $html .= $this->renderContentLocalFont($attributes);
        } elseif ($attributes['name'] === 'preview_font') {
            $html .= $this->renderContentPreviewFont($attributes);
        }
        if (!empty($attributes['help']) && (string)$attributes['help'] !== '') {
            $html .= '<p class="help-block">' . $attributes['help'] . '</p>';
        }
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Render content
     *
     * @param array $att Data field
     *
     * @return string
     */
    public function renderContentPreviewFont($att)
    {
        $values = isset($att['value']) && (string)$att['value'] !== '' ? $att['value'] : '';

        $html = '';
        $html .= '<span style="line-height: 30px">';
        $html .= __('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed pharetra metus suscipit nisl feugiat, non vehicula arcu elementum. Nulla mi metus, pulvinar at aliquet ac, pharetra at magna. Sed imperdiet molestie lacus non ornare. Sed efficitur ante suscipit tincidunt mollis. Nam ut elit ultricies, tincidunt mauris ut, vulputate tortor. Integer convallis, augue eu venenatis dignissim, tortor diam consequat lacus, quis condimentum erat sem a diam. Vestibulum pulvinar, nisl ut faucibus hendrerit, augue arcu placerat sapien, pulvinar blandit mauris nulla eget odio. Etiam ornare posuere mauris eu condimentum. Curabitur maximus metus sit amet nisl hendrerit, in dictum augue imperdiet. Aliquam eleifend, nisl a ultricies ullamcorper, urna tortor varius felis, nec aliquam tortor turpis eget augue. Etiam id diam id orci ultrices gravida sit amet eget libero. In hac habitasse platea dictumst. Fusce tempus quis ipsum nec fermentum. Sed ultrices maximus placerat.', 'wptm');
        $html .= '</span>';
        return $html;
    }

    /**
     * Render content
     *
     * @param array $att Data field
     *
     * @return string
     */
    public function renderContentLocalFont($att)
    {
        $html = '';
        $html .= '<div class="controls controls_add_new_font_local">';
        $html .= '<div class="controls add_new_font_local">';
        $html .= '<label class="label_text">';
        $html .= __('Add new font', 'wptm');
        $html .= '</label>';
        $html .= '<div class="controls" style="position: relative;margin-right: 0;">
<button id="add_new_font" class="ju-button orange-button" value="" type="button">' . __('Add new font', 'wptm') . '</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="controls font_options" style="display: none">';
        $html .= '<div class="controls save_preview">';
        $html .= '<button class="ju-button orange-button save save_font" style="min-width: auto;" data-value="add" value="" type="button">'. __('Save', 'wptm') . '</button>';
        $html .= '<button class="ju-button preview_font_button preview" value="" type="button">'. __('Preview', 'wptm') . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="controls list_font_local">';
        $html .= '<label class="label_text" style="padding-left: 20px; margin: 5px 0;">';
        $html .= __('Local font stored', 'wptm');
        $html .= '</label>';
        $localfonts = WptmTablesHelper::getlocalfont();

        if (isset($localfonts) && count($localfonts) > 0) {
            foreach ($localfonts as $key => $localfont) {
                if (isset($localfont->urc)) {
                    $name_font = $localfont->data[0]->name_font;
                    $html .= '<div class="font_google" data-id="' . $key . '" data-name="' . $name_font . '">';
                    $html .= '<label class="label_text font-name" for="font_name">';
                    $html .= $name_font;
                    $html .= '</label>';
                    $html .= '<div style="position: relative">';
                    $html .= '<i class="material-icons wptm-has-tooltip edit_font">edit</i>';
                    $html .= '<span class="tooltip-label wptm-tooltip-label" style="right: 40px;">' . __('Edit', 'wptm') . '</span>';
                    $html .= '<i class="material-icons wptm-has-tooltip delete_font">delete</i>';
                    $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Removing this font may affect the tables that are using it!', 'wptm') . '</span>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
        }

        $html .= '<div class="controls preview_font">';
        $html .= '<label class="ju-setting-label" style="margin-top: 10px;"><span data-toggle="tooltip" data-placement="top" data-original-title="Font preview">'. __('Font preview', 'wptm') . '</span></label>';
        $html .= '<div class="controls"> <span style="line-height: 30px;">'. __('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed pharetra metus suscipit nisl feugiat, non vehicula arcu elementum. Nulla mi metus, pulvinar at aliquet ac, pharetra at magna. Sed imperdiet molestie lacus non ornare. Sed efficitur ante suscipit tincidunt mollis. Nam ut elit ultricies, tincidunt mauris ut, vulputate tortor. Integer convallis, augue eu venenatis dignissim, tortor diam consequat lacus, quis condimentum erat sem a diam. Vestibulum pulvinar, nisl ut faucibus hendrerit, augue arcu placerat sapien, pulvinar blandit mauris nulla eget odio. Etiam ornare posuere mauris eu condimentum. Curabitur maximus metus sit amet nisl hendrerit, in dictum augue imperdiet. Aliquam eleifend, nisl a ultricies ullamcorper, urna tortor varius felis, nec aliquam tortor turpis eget augue. Etiam id diam id orci ultrices gravida sit amet eget libero. In hac habitasse platea dictumst. Fusce tempus quis ipsum nec fermentum. Sed ultrices maximus placerat.', 'wptm') . '</span></div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render content
     *
     * @param array $att Data field
     *
     * @return string
     */
    public function renderContentFontGoogle($att)
    {
        $values = isset($att['value']) && (string)$att['value'] !== '' ? $att['value'] : '|Noto Sans|';

        $html = '';
        $html .= '<div class="controls select_font">';
        $html .= '<label class="label_text">';
        $html .= __('Select Google font', 'wptm');
        $html .= '</label>';
        $html .= '<div class="controls" style="position: relative;margin-right: 0;"><button id="select_font" class="ju-button orange-button" value="" type="button">' . __('View google font', 'wptm') . '</button>';
        $html .= '<ul class="wptm_select_box select_columns wptm_hiden" data-destination="#select_font"></ul></div>';
        $html .= '</div>';

        $html .= '<div class="control_value" style="display: none">';
        $html .= '<input name = "' . $att['name'] . '" id = "' . $att['name'] . '" class="' . $att['class'] . '" value="' . $values . '">';
        $html .= '</div>';

        $html .= '<div id="list_font_google">';
        $html .= '<label class="label_text" style="padding-left: 10px">';
        $html .= __('List added Google font', 'wptm');
        $html .= '</label>';

        $arrayValues = explode('|', $values);
        if ($values !== '') {
            $upload_url = wp_upload_dir();
            if (is_ssl()) {
                $upload_url['baseurl'] = str_replace('http://', 'https://', $upload_url['baseurl']);
            }
            $upload_url = $upload_url['baseurl'] . '/wptm/';

            foreach ($arrayValues as $arrayValue) {
                if ($arrayValue !== '') {
                    global $wpdb;
                    $nameFont = str_replace(' ', '_', $arrayValue);
                    $result = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.option_name = %s', 'google_font' . $nameFont));
                    $html .= '<div class="font_google" data-name="' . $arrayValue . '">';
                    $html .= '<label class="label_text font-name" for="font_name">';
                    $html .= $arrayValue;
                    $html .= '</label>';
                    $html .= '<div style="position: relative">';
                    $html .= '<i class="material-icons wptm-has-tooltip">delete</i>';
                    $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Removing this font may affect the tables that are using it!', 'wptm') . '</span>';
                    $html .= '</div>';
                    $html .= '<div style="position: relative">';

                    if (!empty($result)) {
                        $html .= '<i class="download material-icons wptm-has-tooltip allow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">';
                        $html .= '<path d="M0 0h24v24H0V0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg></i>';
                        $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Font has been downloaded to the server', 'wptm') . '</span>';
                    } else {
                        $html .= '<i class="download material-icons wptm-has-tooltip"><svg viewBox="0 0 24 24">';
                        $html .= '<path ng-attr-d="{{icon.data}}" d="M13,5V11H14.17L12,13.17L9.83,11H11V5H13M15,3H9V9H5L12,16L19,9H15V3M19,18H5V20H19V18Z"></path></svg></i>';
                        $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Font has not been downloaded to the server', 'wptm') . '</span>';
                        $html .= '<i class="download material-icons wptm-has-tooltip allow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">';
                        $html .= '<path d="M0 0h24v24H0V0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg></i>';
                        $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Font has been downloaded to the server', 'wptm') . '</span>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
        }

        $html .= '<div class="font_google new_font_google" data-name="" style="display: none">';
        $html .= '<label class="label_text font-name" for="font_name">';
        $html .= '</label>';
        $html .= '<div style="position: relative">';
        $html .= '<i class="material-icons wptm-has-tooltip edit_font">edit</i>';
        $html .= '<span class="tooltip-label wptm-tooltip-label" style="right: 40px;">' . __('Edit', 'wptm') . '</span>';
        $html .= '</div>';

        $html .= '<div style="position: relative">';
        $html .= '<i class="material-icons wptm-has-tooltip delete_font">delete</i>';
        $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Removing this font may affect the tables that are using it!', 'wptm') . '</span>';
        $html .= '</div>';
        $html .= '<div style="position: relative">';
        $html .= '<i class="download material-icons wptm-has-tooltip"><svg viewBox="0 0 24 24">';
        $html .= '<path ng-attr-d="{{icon.data}}" d="M13,5V11H14.17L12,13.17L9.83,11H11V5H13M15,3H9V9H5L12,16L19,9H15V3M19,18H5V20H19V18Z"></path></svg></i>';
        $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Font has not been downloaded to the server', 'wptm') . '</span>';
        $html .= '<i class="download material-icons wptm-has-tooltip allow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">';
        $html .= '<path d="M0 0h24v24H0V0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg></i>';
        $html .= '<span class="tooltip-label wptm-tooltip-label" style="width: 250px">' . __('Font has been downloaded to the server', 'wptm') . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render content
     *
     * @param array $fields Data field
     * @param array $datas  Full datas
     *
     * @return string
     */
    public function renderContentHightlight($fields, $datas)
    {
        $html = '';
        $cleanfield = $fields;
        unset($cleanfield['@attributes']);
        if (!empty($cleanfield[0])) {
            foreach ($cleanfield[0] as $child) {
                $field = array_keys($child);
                if (!empty($child[${'field'}[0]]['@attributes']['namespace'])) {
                    if (!empty($child[${'field'}[0]]['@attributes']['type'])) {
                        $class = ucfirst($child[${'field'}[0]]['@attributes']['type']);
                    } else {
                        $class = ucfirst($field[0]);
                    }

                    if (!empty($child[${'field'}[0]]['@attributes']['name'])) {
                        if (isset($datas) && !empty($datas[$child[${'field'}[0]]['@attributes']['name']])) {
                            $child[${'field'}[0]]['@attributes']['value'] = $datas[$child[${'field'}[0]]['@attributes']['name']];
                        }
                        if (!empty($child[${'field'}[0]]['@attributes']['namespace'])) {
                            $class = $child[${'field'}[0]]['@attributes']['namespace'] . $class;
                        } else {
                            $class = '\Joomunited\WPFramework\v1_0_6\Fields\\' . $class;
                        }
                        if (class_exists($class, true)) {
                            $c = new $class;
                            $html .= $c->getfield($child[$field[0]], isset($this->datas) ? $this->datas : null);
                        }
                    }
                }
            }
        }
        return $html;
    }

    /**
     * Render content
     *
     * @param array $att Data format color
     *
     * @return string
     */
    public function renderContentAlternate($att)
    {
        $default = '#bdbdbd|#ffffff|#f3f3f3|#ffffff';
        $default .= '|#4dd0e1|#ffffff|#e0f7fa|#a2e8f1';
        $default .= '|#63d297|#ffffff|#e7f9ef|#afe9ca';
        $default .= '|#f7cb4d|#ffffff|#fef8e3|#fce8b2';
        $default .= '|#f46524|#ffffff|#ffe6dd|#ffccbc';
        $default .= '|#5b95f9|#ffffff|#e8f0fe|#acc9fe';
        $default .= '|#26a69a|#ffffff|#ddf2f0|#8cd3cd';
        $default .= '|#78909c|#ffffff|#ebeff1|#bbc8ce';

        $values = isset($att['value']) && (string)$att['value'] !== '' ? $att['value'] : $default;

        $html = '';
        $html .= '<div id="control_format_style">';
        $html .= '<div class="label_text">' . __('Automatic styling', 'wptm') . ':</div>';
        $html .= '<div class="control_value" style="display: none">';
        $html .= '<input name = "' . $att['name'] . '" id = "' . $att['name'] . '" class="' . $att['class'] . '" value="' . $values . '">';
        $html .= '</div>';
        $html .= '<div id="list_format_style">';
        $arrayValue = explode('|', $values);
        $count = count($arrayValue);
        for ($i = 0; $i < $count / 4; $i++) {
            $i16 = $i * 4;
            $value = array($arrayValue[$i16], $arrayValue[$i16 + 1], $arrayValue[$i16 + 2], $arrayValue[$i16 + 3]);
            $html .= $this->renderListStyle($value, $i);
        }
        $html .= '</div>';
        $html .= '<div id="new_format_style">';
        $html .= '<input type="button" class="active create_format_style" value="New">';
        $html .= '<input type="button" class="wptm_no_active remove_format_style" value="Remove">';
        $html .= '<span class="hide_set_format_style show">' . __('Show detail color', 'wptm') . '
                <i class="material-icons">keyboard_arrow_down</i>
                <i class="material-icons">keyboard_arrow_up</i></span>';
        $html .= '</div>';
        $value = array('#ffffff', '#ffffff', '#ffffff', '#ffffff');
        $html .= $this->renderListStyle($value, 'create');
        $html .= '<div id="save_format_style">';
        $html .= '<input class="ju-button orange-button" type="button" value="' . __('Done', 'wptm') . '">';
        $html .= '<input class="ju-button wptm_no_active" type="button" value="' . __('Cancel', 'wptm') . '">';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render list style color
     *
     * @param array   $value Style value
     * @param integer $order Order number
     *
     * @return string
     */
    public function renderListStyle($value, $order)
    {
        $html = '';
        if ($order !== 'create') {
            $html .= '<div class="pane-color-tile td_' . $order . '">';
            $html .= '<div class="pane-color-tile-header pane-color-tile-band" data-value="' . $value[0] . '" style="background-color:' . $value[0] . ';"></div>';
            $html .= '<div class="pane-color-tile-1 pane-color-tile-band" data-value="' . $value[1] . '" style="background-color:' . $value[1] . ';"></div>';
            $html .= '<div class="pane-color-tile-2 pane-color-tile-band" data-value="' . $value[2] . '" style="background-color:' . $value[2] . ';"></div>';
            $html .= '<div class="pane-color-tile-footer pane-color-tile-band" data-value="' . $value[3] . '" style="background-color:' . $value[3] . ';"></div>';
            $html .= '</div>';
        } else {
            $html .= '<div id="set_color" class="input-pane-set-color">';
            $html .= '<div class="control_value">';
            $html .= '<span>' . __('Header color', 'wptm') . '</span>';
            $html .= '<input title="" value="#ffffff" class="pane-set-color-header inputbox input-block-level wp-color-field" type="text">';
            $html .= '</div>';
            $html .= '<div class="control_value">';
            $html .= '<span>' . __('Alternate color 1', 'wptm') . '</span>';
            $html .= '<input title="" value="#ffffff" class="pane-set-color-1 inputbox input-block-level wp-color-field" type="text">';
            $html .= '</div>';
            $html .= '<div class="control_value">';
            $html .= '<span>' . __('Alternate color 2', 'wptm') . '</span>';
            $html .= '<input title="" value="#ffffff" class="pane-set-color-2 inputbox input-block-level wp-color-field" type="text">';
            $html .= '</div>';
            $html .= '<div class="control_value">';
            $html .= '<span>' . __('Footer color', 'wptm') . '</span>';
            $html .= '<input title="" value="#ffffff" class="pane-set-color-footer inputbox input-block-level wp-color-field" type="text">';
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }
}
