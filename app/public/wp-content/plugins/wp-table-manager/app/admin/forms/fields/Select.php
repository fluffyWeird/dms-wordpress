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
class Select extends Field
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
        if (empty($attributes['hidden']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '<div class="ju-settings-option ' . $attributes['name'] . '">';
            if (!empty($attributes['label']) && $attributes['label'] !== '' && !empty($attributes['name']) && $attributes['name'] !== '') {
                $html .= '<label class="ju-setting-label" for="' . esc_attr($attributes['name']) . '"';
                if (isset($attributes['tooltip'])) {
                    $html .= '><span data-toggle="tooltip" data-placement="top" title="' . esc_attr($attributes['tooltip'], 'wptm') . '"';
                    $html .= '>' . esc_attr($attributes['label'], 'wptm') . '</span></label>';
                } else {
                    $html .= '>' . esc_attr($attributes['label'], 'wptm') . '</label>';
                }
            }

            $html .= '<div class="controls">';
        }
        $html .= '<select';
        if (!empty($attributes)) {
            foreach ($attributes as $attribute => $value) {
                if (in_array($attribute, array('id', 'class', 'onchange', 'name')) && isset($value)) {
                    $html .= ' ' . $attribute . '="' . $value . '"';
                }
            }
        }
        $html .= ' >';

        $cleanfield = $field;
        unset($cleanfield['@attributes']);
        if (!empty($cleanfield[0])) {
            foreach ($cleanfield[0] as $child) {
                if (!empty($child['option']['@attributes'])) {
                    $html .= '<option ';
                    foreach ($child['option']['@attributes'] as $childAttribute => $childValue) {
                        if (in_array($childAttribute, array('type', 'id', 'class', 'name', 'onchange', 'value')) && isset($childValue)) {
                            $html .= ' ' . $childAttribute . '="' . $childValue . '"';
                            if ($childAttribute === 'value' && isset($attributes['value']) && $attributes['value'] === $childValue) {
                                $html .= ' selected="selected"';
                            }
                        }
                    }
                    $html .= '>';
                    if (isset($child['option'][0]) && is_string($child['option'][0])) {
                        $html .= esc_attr($child['option'][0], 'wptm');
                    }
                    $html .= '</option>';
                }
            }
        }

        $html .= '</select>';
        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            $html .= '<p class="help-block">' . $attributes['help'] . '</p>';
        }
        if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '</div></div>';
        }
        return $html;
    }
}
