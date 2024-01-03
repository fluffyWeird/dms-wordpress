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
class Radio extends Field
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
            if (!empty($attributes['label']) && $attributes['label'] !== '') {
                $html .= '<label class="ju-setting-label"';
                if (isset($attributes['tooltip'])) {
                    $html .= '><span data-toggle="tooltip" data-placement="top" title="' . esc_attr($attributes['tooltip'], 'wptm') . '"';
                    $html .= '>' . esc_attr($attributes['label'], 'wptm') . '</span></label>';
                } else {
                    $html .= '>' . esc_attr($attributes['label'], 'wptm') . '</label>';
                }
            }

            $html .= '<div class="controls ' . esc_attr($attributes['class'], 'wptm') . '">';
        }

        $cleanfield = $field;
        unset($cleanfield['@attributes']);
        if (!empty($cleanfield[0])) {
            foreach ($cleanfield[0] as $child) {
                if (!empty($child['option']['@attributes'])) {
                    $html .= '<input type="radio" id="' . $child['option']['@attributes']['for']
                        . '" name="' . $attributes['name'] . '" value="' . $child['option']['@attributes']['value'] . '"';
                    if ($attributes['value'] === $child['option']['@attributes']['value']) {
                        $html .= ' checked="true">';
                    } else {
                        $html .= '>';
                    }
                    $html .= '<label class="ju-setting-label" for="' . $child['option']['@attributes']['for'] . '">';
                    if (isset($child['option']['@attributes']['tooltip'])) {
                        $html .= '<span data-toggle="tooltip" data-placement="top" title="'
                            . esc_attr($child['option']['@attributes']['tooltip'], 'wptm') . '">';
                    } else {
                        $html .= '<span>';
                    }
                    if (isset($child['option'][0]) && is_string($child['option'][0])) {
                        $html .= esc_attr($child['option'][0], 'wptm');
                    }
                    $html .= '</span>';
                    $html .= '</label>';
                }
            }
        }

        if (!empty($attributes['help']) && $attributes['help'] !== '') {
            $html .= '<p class="help-block">' . $attributes['help'] . '</p>';
        }
        if (!empty($attributes['type']) || (!empty($attributes['hidden']) && $attributes['hidden'] !== 'true')) {
            $html .= '</div></div>';
        }
        return $html;
    }
}
