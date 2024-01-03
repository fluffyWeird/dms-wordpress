<?php
/**
 * Created by PhpStorm.
 * User: dandelion
 * Date: 11/02/2020
 * Time: 09:16
 */

namespace Joomunited\WP_Table_Manager\Admin\Fields;

use Joomunited\WPFramework\v1_0_6\Field;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class Select
 */
class Text extends Field
{

    /**
     * Get the field
     *
     * @param array $field Field attributes
     * @param array $datas Full datas
     *
     * @return string
     */
    public function getfield($field, $datas)
    {
        $attributes = $field['@attributes'];
        $html = '';
        if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '<div class="ju-settings-option ' . $attributes['name'] . '">';
            if (!empty($attributes['link'])) {
                $html .= '<a class="text_link" href="' . $attributes['link'] . '" target="__blank">' . $attributes['label'] . '</a>';
            }
            if (!empty($attributes['label']) && $attributes['label'] !== '' && !empty($attributes['name']) && $attributes['name'] !== '') {
                // phpcs:ignore WordPress.WP.I18n -- Allow non literal arg
                $html .= '<label class="ju-setting-label" for="' . $attributes['name'] . '"';
                if (isset($attributes['tooltip'])) {
                    $html .= '><span data-toggle="tooltip" data-placement="top" title="' . esc_attr($attributes['tooltip'], 'wptm') . '"';
                    $html .= '>'. esc_attr($attributes['label'], 'wptm') . '</span></label>';
                } else {
                    $html .= '>'. esc_attr($attributes['label'], 'wptm') . '</label>';
                }
            }
            $html .= '<div class="controls">';
        }
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '<input';
        } else {
            $html .= '<hidden';
        }

        if (!empty($attributes)) {
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, array('id', 'class', 'placeholder', 'name', 'value', 'min', 'step')) && isset($value)) {
                    $html .= ' ' . $attribute . '="' . $value . '"';
                }
                if ($attribute === 'type_input') {
                    $html .= ' type="number"';
                }
            }
            if (isset($attributes['type_input'])) {
                $html .= ' type="' . $attributes['type_input'] . '"';
            } else {
                $html .= ' type="' . $attributes['type'] . '"';
            }
        }
        $html .= ' />';
        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            $html .= '<p class="help-block">' . $attributes['help'] . '</p>';
        }
        if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '</div></div>';
        }
        return $html;
    }
}
