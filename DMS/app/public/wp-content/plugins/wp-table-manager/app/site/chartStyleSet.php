<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 2.4
 */

/**
 * Class ChartStyleSet
 */
class ChartStyleSet
{
    /**
     * Highlight color
     *
     * @var string
     */
    public $highlight;
    /**
     * BackgroundColor color
     *
     * @var string
     */
    public $backgroundColor;
    /**
     * BorderColor Color
     *
     * @var string
     */
    public $borderColor;
    /**
     * PointBackgroundColor Color
     *
     * @var string
     */
    public $pointBackgroundColor;
    /**
     * Point Color
     *
     * @var string
     */
    public $pointColor;
    /**
     * Point Border Color Color
     *
     * @var string
     */
    public $pointBorderColor;
    /**
     * Point Highlight Fill
     *
     * @var string
     */
    public $pointHighlightFill;

    /**
     * ChartStyleSet constructor.
     *
     * @param string     $color   Color string
     * @param float|null $opacity Var opacity
     *
     * @return void
     */
    public function __construct($color, $opacity = null)
    {
        $this->highlight = $this->hex2rgba($color, $opacity !== null ? $opacity : 1);
        $this->backgroundColor = $this->hex2rgba($color, 0.9 * ($opacity !== null ? $opacity : 1));
        $this->borderColor = $this->hex2rgba($color, $opacity !== null ? $opacity : 1);
        $this->pointBackgroundColor = $this->hex2rgba($color, $opacity !== null ? $opacity : 1);
        $this->pointColor = '#fff';
        $this->pointHighlightFill = '#fff';
        $this->pointBorderColor = $this->hex2rgba($color, 1);
    }

    /**
     * Function combine color + opacity
     *
     * @param array   $color   Color array
     * @param boolean $opacity Var opacity
     *
     * @return string
     */
    public function hex2rgba($color, $opacity = false)
    {
        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color)) {
            return $default;
        }
        //Sanitize $color if "#" is provided
        if ((string)$color[0] === '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) === 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) === 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = 1.0;
            }
            $output = 'rgba(' . implode(',', $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(',', $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
    }
}
