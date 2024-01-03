<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */
//namespace Joomunited\WP_Table_Manager\Site\Helpers;

use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Model;

//use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
//use PhpOffice\PhpSpreadsheet\Cell\DataType;

require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
//-- No direct access
defined('ABSPATH') || die();

/**
 * Class WptmHelper
 */
class WptmHelper
{
    /**
     * Result Calculator
     *
     * @var string
     */
    protected static $resultCalc;
    /**
     * Date formats
     *
     * @var string
     */
    public $date_formats;
    /**
     * Symbol position
     *
     * @var string
     */
    public $symbol_position;
    /**
     * Symbol position cell
     *
     * @var string
     */
    public static $symbol_position_cell = null;
    /**
     * Currency symbol
     *
     * @var string
     */
    public $currency_symbol;
    /**
     * Currency symbol cell
     *
     * @var string
     */
    public static $currency_symbol_cell = null;
    /**
     * Decimal symbol
     *
     * @var string
     */
    public $decimal_symbol;
    /**
     * Decimal symbol cell
     *
     * @var string
     */
    public static $decimal_symbol_cell = null;
    /**
     * Decimal count
     *
     * @var string
     */
    public $decimal_count;
    /**
     * Decimal count cell
     *
     * @var string
     */
    public static $decimal_count_cell = null;
    /**
     * Thousand symbol
     *
     * @var string
     */
    public $thousand_symbol;
    /**
     * Thousand symbol cell
     *
     * @var string
     */
    public static $thousand_symbol_cell = null;
    /**
     * Date var
     *
     * @var array
     */
    protected static $date;
    /**
     * Number cells in calculator
     *
     * @var integer
     */
    protected static $n;
    /**
     * Math formula
     *
     * @var array
     */
    public $math_formula = array('SUM','MULTIPLY','DIVIDE','COUNT','MIN', 'MAX','AVG');
    /**
     * Boolean formula
     *
     * @var array
     */
    public $and_formula = array('AND','OR', 'XOR');
    /**
     * Time formula
     *
     * @var array
     */
    public $date_formula = array('DATE','DAY','DAYS','DAYS360');
    /**
     * Font used from google
     *
     * @var array
     */
    public $fonts_google = array();

    /**
     * Position of cell when use PhpSpreadsheet to get cell value
     *
     * @var integer
     */
    public $alternativPosition = 9999;

    /**
     * Hash of custom short-code
     *
     * @var string
     */
    public static $shortCodeHash = '';

    /**
     * List min max of range cols
     *
     * @var string
     */
    public $rangeColsNull = array();

    /**
     * Get list local font
     *
     * @return array|null
     */
    public function getlocalfont()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wptm_table_options';
        $result = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $table . ' WHERE option_name = %s',
                'local_font'
            )
        );

        if ($result && $result !== null) {
            $localfonts = array();
            foreach ($result as $localfont) {
                if (isset($localfont->option_value)) {
                    $localfonts[$localfont->id] = json_decode($localfont->option_value);
                }
            }
            return $localfonts;
        } else {
            return array();
        }
    }

    /**
     * Compile style table/chart
     *
     * @param object     $table         Data table
     * @param array      $styles        Style table
     * @param string     $customCss     Custom css
     * @param array|null $argsShortCode Arguments in short code
     *
     * @return boolean
     */
    public static function compileStyle($table, $styles, $customCss, $argsShortCode = null)
    {
        $folder = wp_upload_dir();
        $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        if ($argsShortCode !== null) {
            $range = self::$shortCodeHash;
        } else {
            $range = '';
        }

        $file = $folder . ($range === '' ? '' : ($range . '_')) . $table->id . '_' . $table->hash . '.css';

        if (!file_exists($file)) {
            $files = glob($folder . ($range === '' ? '' : ($range . '_')) . $table->id . '_*.css');
            foreach ($files as $f) {
                unlink($f);
            }
        } else {
            return true;
        }

        $folder_admin = dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin';
        require_once $folder_admin . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'wikimedia' . DIRECTORY_SEPARATOR . 'less.php' . DIRECTORY_SEPARATOR . 'lessc.inc.php';
        require_once $folder_admin . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'cerdic' . DIRECTORY_SEPARATOR . 'css-tidy' . DIRECTORY_SEPARATOR . 'class.csstidy.php';

        $less  = new lessc;

        $countStyles = count($styles);
        $contents    = array();
        $contents[0] = '';

        for ($i = 0; $i < $countStyles; $i ++) {
            $style = $styles[$i];
            try {
                $contents[$i] = $less->compile('#wptmtable' . ($range === '' ? '' : ($range . '_')) . $table->id . '.wptmtable {' . $style . '}');
            } catch (Exception $exc) {
                return false;
            }

            try {
                $customContent = $less->compile('#wptmtable' . ($range === '' ? '' : ($range . '_')) . $table->id . '.wptmtable table {' . $customCss . '}');
            } catch (Exception $exc) {
                $customContent = '';
            }

            $contents[$i] .= $customContent;
            $csstidy      = new csstidy();
            $csstidy->parse($contents[$i]);

            $less->setFormatter('compressed');

            try {
                //phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped -- The inner variables were esc
                $contents[$i] = $less->compile($csstidy->print->plain());
            } catch (Exception $exc) {
                return false;
            }
        }

        if (isset($contents[1])) {
            $content = implode($contents);
        } else {
            $content = $contents[0];
        }

        $style = $table->style;
        $alignCss = '';
        $table_align = !isset($style->table->table_align) ? 'left' : $style->table->table_align;
        if (isset($argsShortCode['align'])) {
            $table_align = $argsShortCode['align'];
        }
        switch ($table_align) {
            case 'left':
                $alignCss .= 'margin : 0 auto 0 0';
                break;
            case 'right':
                $alignCss .= '';//fix table header is wrong when table_aline option is right
                break;
            case 'none':
                break;
            case 'center':
            default:
                $alignCss .= 'margin : 0';
                break;
        }
        $content .= '#wptmtable' . ($range === '' ? '' : ($range . '_')) . $table->id . '.wptmtable .dataTables_scrollHead .dataTables_scrollHeadInner { '. $alignCss .' }';

        if (!file_put_contents($file, $content)) {
            return false;
        }
        return true;
    }

    /**
     * Check .cc or .html file exist
     *
     * @param string  $id     Table id
     * @param string  $hash   Table hash
     * @param boolean $isHtml Check html file or css file
     * @param string  $range  Range cell render
     *
     * @return boolean
     */
    public function checkFileExists($id, $hash, $isHtml, $range = '')
    {
        $folder = wp_upload_dir();
        $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
            return false;
        }
        if ($isHtml) {
            $file = $folder . ($range === '' ? '' : ($range . '_')) . $id . '_' . $hash . '.html';
        } else {
            $file = $folder . ($range === '' ? '' : ($range . '_')) . $id . '_' . $hash . '.css';
        }

        if (!file_exists($file)) {
            if ($isHtml) {
                $files = glob($folder . ($range === '' ? '' : ($range . '_')) . $id . '_*.html');
            } else {
                $files = glob($folder . ($range === '' ? '' : ($range . '_')) . $id . '_*.css');
            }
            foreach ($files as $f) {
                unlink($f);
            }
        } else {
            return true;
        }
        return false;
    }

    /**
     * Render style table
     *
     * @param object     $table         Data table
     * @param array|null $argsShortCode Arguments in short code
     *
     * @return boolean|array
     */
    public function styleRender($table, $argsShortCode = null)
    {
        //create hash
        if ($argsShortCode !== null) {
            self::$shortCodeHash = hash('md5', json_encode($argsShortCode));
        } else {
            self::$shortCodeHash = '';
        }

        $file = $this->checkFileExists($table->id, $table->hash, false, (self::$shortCodeHash !== '' ? self::$shortCodeHash : ''));
        if ($file) {
            return true;
        }

        $style = $table->style;
        $contents = array();

        $style_tooltip = array();
        if ($style === null) {
            $contents[0] = '';
        } else {
            $contentTable = 'table {';
            //render global table params
            if (isset($style->table->alternate_row_odd_color) && $style->table->alternate_row_odd_color) {
                $contentTable .= 'tr:nth-child(even) td {background-color: ' . $style->table->alternate_row_odd_color . ';}';
            }
            if (isset($style->table->alternate_row_even_color) && $style->table->alternate_row_even_color) {
                $contentTable .= 'tr:nth-child(odd) td {background-color: ' . $style->table->alternate_row_even_color . ';}';
            }
            if (isset($style->table->row_border) && $style->table->row_border) {
                $contentTable .= 'tr td {border-bottom: ' . $style->table->row_border . ';}';
            }

            if (isset($style->table->headerOption) && isset($style->table->header_data[(int)$style->table->headerOption - 1])) {
                $header_content = $style->table->header_data[(int)$style->table->headerOption - 1];
            } else {
                $header_content = !empty($table->datas[0]) ? $table->datas[0] : $style->table->header_data[0];
            }

            if ($table->type === 'mysql') {
                if (isset($style->table->allAlternate)) {
                    if (!empty($style->table->allAlternate->old)) {
                        $contentTable .= 'tbody tr.odd td {background-color: '.$style->table->allAlternate->old.'}';
                    }
                    if (!empty($style->table->allAlternate->even)) {
                        $contentTable .= 'tbody tr.even td {background-color: '.$style->table->allAlternate->even.'}';
                    }
                    if (!empty($style->table->allAlternate->header)) {
                        $contentTable .= 'thead tr:first-child th {background-color: '.$style->table->allAlternate->header.'}';
                    }
                    if (!empty($style->table->allAlternate->footer)) {
                        $contentTable .= 'tbody tr:last-child td {background-color: '.$style->table->allAlternate->footer.'}';
                    }
                }
            }

            //render global rows
            if (!(!isset($style->table->allRowHeight) || $style->table->allRowHeight === '')) {
                $contentTable .= ' td, th {height: ' . (int)$style->table->allRowHeight . 'px;}';
            }

            $content2 = '';
            if ($argsShortCode === null) {
                if (isset($style->table->width) && $style->table->width > 0) {
                    $content2 .= '& {width : ' . $style->table->width . 'px;}';
                }
            }

            if (!isset($style->table->table_align)) {
                $style->table->table_align = 'center';
            }

            if (isset($argsShortCode['align'])) {
                $style->table->table_align = $argsShortCode['align'];
            }

            switch ($style->table->table_align) {
                case 'left':
                    $content2 .= '& {margin : 0 auto 0 0}';
                    break;
                case 'right':
                    $content2 .= '& {margin : 0 0 0 auto}';
                    break;
                case 'none':
                    break;
                case 'center':
                default:
                    $content2 .= '& {margin : 0 auto 0 auto}';
                    break;
            }
            $content2 .= '}';

            $i = 0;
            $content = '';
            $alternateColorValue = !empty($style->table->alternateColorValue) ? $style->table->alternateColorValue : null;

            if (!empty($style->table->mergeSetting)) {
                $mergeSetting = is_string($style->table->mergeSetting) ? json_decode($style->table->mergeSetting, true) : $style->table->mergeSetting;
            }

            foreach ($style->cells as $cell) {
                $rowStyle = null;
                $colStyle = null;
                if ((is_object($style->rows) && isset($style->rows->{(int)($cell[0])}))) {
                    $rowStyle = $style->rows->{(int)($cell[0])};
                    $rowStyle = is_object($rowStyle) ? $rowStyle->{1} : $rowStyle[1];
                } elseif (!is_object($style->rows) && isset($style->rows[(int)($cell[0])])) {
                    $rowStyle = $style->rows[(int)($cell[0])];
                    $rowStyle = is_object($rowStyle) ? $rowStyle->{1} : $rowStyle[1];
                }

                if ((is_object($style->cols) && isset($style->cols->{(int)($cell[1])}))) {
                    $colStyle = $style->cols->{(int)($cell[1])};
                    $colStyle = is_object($colStyle) ? $colStyle->{1} : $colStyle[1];
                } elseif (!is_object($style->cols) && isset($style->cols[(int)($cell[1])])) {
                    $colStyle = $style->cols[(int)($cell[1])];
                    $colStyle = is_object($colStyle) ? $colStyle->{1} : $colStyle[1];
                }

                //render global table params
                $backgroundColor = $this->backgroundColor($alternateColorValue, $cell, $colStyle, $rowStyle, $table);
                $cell_style = $backgroundColor[1];
                $content .= $backgroundColor[0];

                if (!empty($mergeSetting)) {
                    $count_mergeSetting = count($mergeSetting);
                    for ($j = 0; $j < $count_mergeSetting; $j++) {
                        $merge_cells_2 = $mergeSetting[$j];
                        if (is_object($merge_cells_2)) {
                            $merge_cells_2 = (array)$merge_cells_2;
                        }
                        if (isset($merge_cells_2['col'])
                            && (int)($cell[1]) === $merge_cells_2['col']
                            && ($merge_cells_2['rowspan'] + $merge_cells_2['row'] - 1 >= (int)($cell[0]))
                            && (int)($cell[0]) >= $merge_cells_2['row']) {
                            $cell_end = ($cell[0]) . '!' . ($merge_cells_2['col'] + $merge_cells_2['colspan'] - 1);
                            if (isset($style->cells[$cell_end]) && isset($style->cells[$cell_end][2]) && isset($style->cells[$cell_end][2]['cell_border_right'])) {
                                $cell[2]['cell_border_right'] = $style->cells[$cell_end][2]['cell_border_right'];
                            }
                        }
                    }
                }

                if (!empty($cell[2])) {
                    foreach ($cell[2] as $keyStyleCell => $styleCell) {
                        if (in_array($keyStyleCell, array(
                            'cell_border_top',
                            'cell_border_right',
                            'cell_border_bottom',
                            'cell_border_left',
                            'cell_font_family',
                            'cell_font_size',
                            'cell_font_color',
                            'cell_font_bold',
                            'cell_font_italic',
                            'cell_font_underline',
                            'cell_format_strikethrough',
                            'cell_text_align',
                            'cell_padding_left',
                            'cell_vertical_align',
                            'cell_padding_top',
                            'cell_padding_right',
                            'cell_padding_bottom',
                            'cell_background_radius_left_top',
                            'cell_background_radius_right_top',
                            'cell_background_radius_right_bottom',
                            'cell_background_radius_left_bottom',
                            'cell_border_bottom'))) {
                            $cell_style .= $this->addStyleColsRows(
                                isset($colStyle) && isset($colStyle->{$keyStyleCell}) ? $colStyle->{$keyStyleCell} : null,
                                isset($rowStyle) && isset($rowStyle->{$keyStyleCell}) ? $rowStyle->{$keyStyleCell} : null,
                                $cell,
                                $keyStyleCell
                            );
                        }
                    }

                    $text_wrapping = isset($colStyle) && isset($colStyle->text_wrapping) ? $colStyle->text_wrapping : null;
                    $text_wrapping = isset($rowStyle) && isset($rowStyle->text_wrapping) ? $rowStyle->text_wrapping : $text_wrapping;
                    $text_wrapping = (isset($cell[2]['text_wrapping']) && false !== $cell[2]['text_wrapping']) ? $cell[2]['text_wrapping'] : $text_wrapping;
                    if (isset($text_wrapping) && ($text_wrapping === 'normal' || $text_wrapping === 'hidden')) {
                        $cell_text_wrapping = $this->addStyleColsRows(
                            null,
                            null,
                            $cell,
                            'text_wrapping',
                            $text_wrapping
                        );
                        $firstCol = $colStyle ? $colStyle->width : null;
                        if (isset($cell[2]['cell_padding_left']) && (int)$cell[2]['cell_padding_left'] > 0) {
                            $firstCol -= (int)$cell[2]['cell_padding_left'];
                        }
                        if (isset($cell[2]['cell_padding_right']) && (int)$cell[2]['cell_padding_right'] > 0) {
                            $firstCol -= (int)$cell[2]['cell_padding_right'];
                        }

                        $htmlTag = 'p';
                        if (isset($cell[2]['cell_type']) && (string)$cell[2]['cell_type'] === 'html') {
                            $htmlTag = 'div';
                        }
                        if ($firstCol) {
                            $content .= '.dtr'.(int)($cell[0]).'.dtc'.(int)($cell[1]).' {overflow: visible;}';
                            $content .= '.dtr'.(int)($cell[0]).'.dtc'.(int)($cell[1]).' > ' . $htmlTag . ' {'. $cell_text_wrapping . ' max-width: ' . (int)$firstCol . 'px;}';
                        } else {
                            $content .= '.dtr'.(int)($cell[0]).'.dtc'.(int)($cell[1]).' {overflow: visible;}';
                            $content .= '.dtr'.(int)($cell[0]).'.dtc'.(int)($cell[1]).' > ' . $htmlTag . ' {'. $cell_text_wrapping . ' max-width: 100%;}';
                        }
                    }
                }
                $content .= '.dtr'.(int)($cell[0]).'.dtc'.(int)($cell[1]).' {'. $cell_style . '}';

                if (isset($cell[2]['tooltip_width']) && !empty($cell[2]['tooltip_width'])) {
                    $style_tooltip[(int) ($cell[0]) . '_'. (int) ($cell[1])] = $cell[2]['tooltip_width'];
                }
                $i++;
                if ($i > 0 && $i % 1000 === 0) {
                    $contents[$i / 1000 - 1] = 'table {' . $content . '}';
                    $content = '';
                }

                //render global cols
                if (isset($style->cols) && !empty($colStyle) && (int)$cell[0] === 0) {
                    $numberCol = (int)$cell[1];
                    $contentTable .= '.dtc' . (int)($numberCol) . ' {';
                    if (isset($colStyle->width)) {
                        $contentTable .= ' width: ' . (int)$colStyle->width . 'px !important; min-width: ' . (int)$colStyle->width . 'px;';
                    }

                    foreach ($colStyle as $numberStyleCol => $styleCol) {
                        if ($numberStyleCol !== 'width') {
                            $contentTable .= $this->addStyleColsRows(
                                $styleCol,
                                null,
                                null,
                                $numberStyleCol
                            );
                        }
                    }
                    $contentTable .= '}';

                    //responsive_type repeated header
                    if (isset($style->table->responsive_type) && $style->table->responsive_type === 'repeatedHeader') {
                        $contentTable .= ' &.repeatedHeaderTrue { tbody td:nth-of-type(' . ((int)($numberCol) + 1) . '):before {content:';
                        if (!empty($header_content[(int)($numberCol)])) {
                            $contentTable .= '"' . strip_tags($header_content[(int)($numberCol)]) . '"';
                        } else {
                            $contentTable .= '""';
                        }
                        $contentTable .= ';}';
                        $contentTable .= ' &:not(.style_repeated) tbody tr.odd td.dtc' . (int)($numberCol) . ' {width: auto !important;border: none !important; border-bottom: 1px solid #ffffff !important;';
                        $contentTable .= '}';
                        $contentTable .= ' &:not(.style_repeated) tbody tr.even td.dtc' . (int)($numberCol) . ' {width: auto !important;border: none !important; border-bottom: 1px solid #eaeaea !important;';
                        $contentTable .= '}}';
                    }
                }

                if (isset($style->rows) && !empty($rowStyle) && (int)$cell[1] === 0) {
                    $numberRow = (int)$cell[0];
                    $contentTable .= '.dtr' . (int)$numberRow . ' {';

                    if (isset($rowStyle->height)) {
                        $contentTable .= ' height: ' . (int)$rowStyle->height . 'px;';
                    }

                    if ($rowStyle !== null) {
                        foreach ($rowStyle as $numberStyleRow => $styleRow) {
                            if ($numberStyleRow !== 'height') {
                                $contentTable .= $this->addStyleColsRows(
                                    $styleRow,
                                    null,
                                    null,
                                    $numberStyleRow
                                );
                            }
                        }
                    }

                    $contentTable .= '}';

                    //responsive_type repeated header
                    if (isset($style->table->responsive_type) && $style->table->responsive_type === 'repeatedHeader') {
                        $contentTable .= ' &.repeatedHeaderTrue tbody .dtr' . (int)$numberRow . ' {';

                        if (isset($rowStyle->height)) {
                            $contentTable .= ' min-height: ' . (int)$rowStyle->height . 'px;';
                        } else {
                            $contentTable .= ' min-height: 30px;';
                        }

                        $contentTable .= ';}';
                    }
                }
            }

            if (empty($style->cells)) {
                foreach ($style->cols as $colStyles) {
                    $colStyle = is_object($colStyles) ? $colStyles->{1} : $colStyles[1];
                    $colNumber = is_object($colStyles) ? (int)$colStyles->{0} : (int)$colStyles[0];
                    $cell_style = $this->addStyleColsRows(
                        isset($colStyle) && isset($colStyle->cell_background_color) ? $colStyle->cell_background_color : null,
                        isset($rowStyle) && isset($rowStyle->cell_background_color) ? $rowStyle->cell_background_color : null,
                        null,
                        'cell_background_color'
                    );

                    $contentTable .= '.dtc' . (int)($colNumber) . ' {';
                    if (isset($colStyle->width)) {
                        $contentTable .= ' width: ' . (int)$colStyle->width . 'px !important; min-width: ' . (int)$colStyle->width . 'px;';
                    }

                    foreach ($colStyle as $keyStyleCell => $styleCell) {
                        if (in_array($keyStyleCell, array(
                            'cell_border_top',
                            'cell_border_right',
                            'cell_border_bottom',
                            'cell_border_left',
                            'cell_font_family',
                            'cell_font_size',
                            'cell_font_color',
                            'cell_font_bold',
                            'cell_font_italic',
                            'cell_font_underline',
                            'cell_format_strikethrough',
                            'cell_text_align',
                            'cell_padding_left',
                            'cell_vertical_align',
                            'cell_padding_top',
                            'cell_padding_right',
                            'cell_padding_bottom',
                            'cell_background_radius_left_top',
                            'cell_background_radius_right_top',
                            'cell_background_radius_right_bottom',
                            'cell_background_radius_left_bottom',
                            'cell_border_bottom'))) {
                            $cell_style .= $this->addStyleColsRows(
                                isset($colStyle) && isset($colStyle->{$keyStyleCell}) ? $colStyle->{$keyStyleCell} : null,
                                isset($rowStyle) && isset($rowStyle->{$keyStyleCell}) ? $rowStyle->{$keyStyleCell} : null,
                                null,
                                $keyStyleCell
                            );
                        }

                        if ($keyStyleCell !== 'width') {
                            $contentTable .= $this->addStyleColsRows(
                                $styleCell,
                                null,
                                null,
                                $keyStyleCell
                            );
                        }
                    }

                    $text_wrapping = isset($colStyle) && isset($colStyle->text_wrapping) ? $colStyle->text_wrapping : null;
                    $text_wrapping = isset($rowStyle) && isset($rowStyle->text_wrapping) ? $rowStyle->text_wrapping : $text_wrapping;
                    if (isset($text_wrapping) && ($text_wrapping === 'normal' || $text_wrapping === 'hidden')) {
                        $cell_text_wrapping = $this->addStyleColsRows(
                            null,
                            null,
                            null,
                            'text_wrapping',
                            $text_wrapping
                        );
                        $firstCol = $colStyle ? $colStyle->width : null;

                        $htmlTag = 'p';
                        if ($firstCol) {
                            $content .= '.dtc'.(int)($colNumber).' {overflow: visible;}';
                            $content .= '.dtc'.(int)($colNumber).' > ' . $htmlTag . ' {'. $cell_text_wrapping . ' max-width: ' . (int)$firstCol . 'px;}';
                        } else {
                            $content .= '.dtc'.(int)($colNumber).' {overflow: visible;}';
                            $content .= '.dtc'.(int)($colNumber).' > ' . $htmlTag . ' {'. $cell_text_wrapping . ' max-width: 100%;}';
                        }
                    }
                    $content .= '.dtc'.(int)($colNumber).' {'. $cell_style . '}';
                    $i++;
                    if ($i > 0 && $i % 1000 === 0) {
                        $contents[$i / 1000 - 1] = 'table {' . $content . '}';
                        $content = '';
                    }

                    $contentTable .= '}';

                    //responsive_type repeated header
                    if (isset($style->table->responsive_type) && $style->table->responsive_type === 'repeatedHeader') {
                        $contentTable .= ' &.repeatedHeaderTrue { tbody td:nth-of-type(' . ((int)($colNumber) + 1) . '):before {content:';
                        if (!empty($header_content[(int)($colNumber)])) {
                            $contentTable .= '"' . strip_tags($header_content[(int)($colNumber)]) . '"';
                        } else {
                            $contentTable .= '""';
                        }
                        $contentTable .= ';}';
                        $contentTable .= ' &:not(.style_repeated) tbody tr.odd td.dtc' . (int)($colNumber) . ' {width: auto !important;border: none !important; border-bottom: 1px solid #ffffff !important;';
                        $contentTable .= '}';
                        $contentTable .= ' &:not(.style_repeated) tbody tr.even td.dtc' . (int)($colNumber) . ' {width: auto !important;border: none !important; border-bottom: 1px solid #eaeaea !important;';
                        $contentTable .= '}}';
                    }
                }
            }

            $count_content = count($contents);

            $contents[$count_content] = $contentTable . $content . $content2;
        }

        require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
        self::compileStyle($table, $contents, $table->css, $argsShortCode);

        $data_return = array(
            'tooltip' => $style_tooltip
        );

        return array ('data' => $data_return);
    }


    /**
     * Set background for cell/col/row
     *
     * @param object $alternateColorValue Alternate color value
     * @param array  $cell                Cell style
     * @param array  $colStyle            Col style
     * @param array  $rowStyle            Row style
     * @param object $table               Table style
     *
     * @return array
     */
    public function backgroundColor($alternateColorValue, $cell, $colStyle, $rowStyle, $table)
    {
        $content = '';
        $cell_style = '';
        $AlternateColor = null;
        $cell_style_background = '';

        if (!empty($cell)) {
            if (isset($cell[2]['AlternateColor'])) {
                if (is_object($alternateColorValue) && isset($alternateColorValue->{$cell[2]['AlternateColor']})) {
                    $AlternateColor = $alternateColorValue->{$cell[2]['AlternateColor']};
                } elseif (is_array($alternateColorValue) && isset($alternateColorValue[$cell[2]['AlternateColor']])) {
                    $AlternateColor = $alternateColorValue[$cell[2]['AlternateColor']];
                }
            }

            if (isset($AlternateColor) && isset($AlternateColor->selection)) {
                $numberRow = 0;
                if (isset($AlternateColor->header) && $AlternateColor->header === '') {
                    $numberRow = -1;
                }
                switch ($cell[0]) {
                    case $AlternateColor->selection[0]:
                        if ($AlternateColor->header === '') {
                            $cell_style_background .= 'background-color: ' . $AlternateColor->even . '; ';
                        } else {
                            $cell_style_background .= 'background-color: ' . $AlternateColor->header . '; ';
                        }
                        break;
                    case $AlternateColor->selection[2]:
                        if ($AlternateColor->footer === '') {
                            if (($cell[0] - (int)($AlternateColor->selection[0] + $numberRow)) % 2) {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->even . '; ';
                            } else {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->old . '; ';
                            }
                        } else {
                            $cell_style_background .= 'background-color: ' . $AlternateColor->footer . '; ';
                        }
                        break;
                    default:
                        if (($cell[0] - (int)($AlternateColor->selection[0] + $numberRow)) % 2) {
                            $cell_style_background .= 'background-color: ' . $AlternateColor->even . '; ';
                        } else {
                            $cell_style_background .= 'background-color: ' . $AlternateColor->old . '; ';
                        }
                        break;
                }
            }
        }

        $cell_background = $this->addStyleColsRows(
            isset($colStyle) && isset($colStyle->cell_background_color) ? $colStyle->cell_background_color : null,
            isset($rowStyle) && isset($rowStyle->cell_background_color) ? $rowStyle->cell_background_color : null,
            $cell,
            'cell_background_color'
        );

        if ($cell_style_background !== '') {
            if ((isset($table->sortFilters->use_sortable) && (int)$table->sortFilters->use_sortable === 1) || (isset($table->sortFilters->enable_filters) && (int)$table->sortFilters->enable_filters === 1)) {//if user filter|sort then AlternateColor not by row id
                $content = '.row_index' . (int)($cell[0]) . ' .dtc' . (int)($cell[1]) . ' {' . $cell_style_background . '}';
            } else {
                $cell_style = $cell_style_background;
            }
            if ($cell_background !== '') {
                $content = 'tr .dtr'.(int)($cell[0]).'.dtc'.(int)($cell[1]).' {'. $cell_background . '}';
            }
        } else {
            $cell_style = $cell_background;
        }

        return array($content, $cell_style);
    }

    /**
     * Merge style col, row, cell
     *
     * @param string     $cols  Col  style
     * @param string     $rows  Row  style
     * @param array|null $cell  List style
     * @param string     $key   Name style
     * @param string     $value Data cell style
     *
     * @return string
     */
    public function addStyleColsRows($cols, $rows, $cell, $key, $value = '')
    {
        if ($value === '') {
            if (isset($cell) && isset($cell[2][$key]) && false !== $cell[2][$key]) {
                $value = $cell[2][$key];
            } elseif (isset($rows)) {
                $value = $rows;
            } elseif (isset($cols)) {
                $value = $cols;
            }
        }

        $content = '';
        if (isset($value)) {
            switch ($key) {
                case 'text_wrapping':
                    if ($value !== '') {
                        if ($value === 'normal') {
                            $content = ' word-wrap: normal; ';
                            $content .= ' overflow: visible; ';
                            $content .= ' white-space: nowrap; ';
                        } elseif ($value === 'break-word') {
                            $content = ' word-wrap: break-word; ';
                            $content .= ' overflow: visible; ';
                        } else {
                            $content = ' overflow: hidden; ';
                            $content .= ' white-space: nowrap; ';
                        }
                    }
                    break;
                case 'cell_background_color':
                    if ($value !== '') {
                        $content = ' background-color: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_top':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-top: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_right':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-right: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_bottom':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-bottom: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_left':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-left: ' . $value . '; ';
                    }
                    break;
                case 'cell_font_family':
                    if ($value !== '') {
                        $content = ' font-family: ' . $value . '; ';
                        //$fonts_google
                    }
                    break;
                case 'cell_font_size':
                    if ($value !== '') {
                        $content = ' font-size: ' . $value . 'px;';
                    }
                    break;
                case 'cell_font_color':
                    if ($value !== '') {
                        $content = ' color: ' . $value . '; ';
                    }
                    break;
                case 'cell_font_bold':
                    if (!empty($value)) {
                        $content = ' font-weight: bold;';
                    }
                    break;
                case 'cell_font_italic':
                    if (!empty($value)) {
                        $content = ' font-style: italic;';
                    }
                    break;
                case 'cell_font_underline':
                    if (!empty($value)) {
                        $content = ' text-decoration: underline;';
                    }
                    break;
                case 'cell_format_strikethrough':
                    if (!empty($value)) {
                        $content = ' text-decoration: line-through;';
                    }
                    break;
                case 'cell_text_align':
                    if ($value !== '') {
                        $content = ' text-align: ' . $value . '; ';
                    }
                    break;
                case 'cell_vertical_align':
                    if ($value !== '') {
                        $content = ' vertical-align: ' . $value . '; ';
                    }
                    break;
                case 'cell_padding_left':
                    if ($value !== '') {
                        $content = ' padding-left: ' . $value . 'px;';
                    }
                    break;
                case 'cell_padding_top':
                    if ($value !== '') {
                        $content = ' padding-top: ' . $value . 'px;';
                    }
                    break;
                case 'cell_padding_right':
                    if ($value !== '') {
                        $content = ' padding-right: ' . $value . 'px;';
                    }
                    break;
                case 'cell_padding_bottom':
                    if ($value !== '') {
                        $content = ' padding-bottom: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_left_top':
                    if ($value !== '') {
                        $content = ' border-top-left-radius: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_right_top':
                    if ($value !== '') {
                        $content = ' border-top-right-radius: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_right_bottom':
                    if ($value !== '') {
                        $content = ' border-bottom-right-radius: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_left_bottom':
                    if ($value !== '') {
                        $content = ' border-bottom-left-radius: ' . $value . 'px;';
                    }
                    break;
            }
        }

        return $content;
    }

    /**
     * Standardize value for filter and sort params
     *
     * @param object $sortFilters Sort and filter params
     * @param array  $params      Params
     *
     * @return stdClass
     */
    public function renderSortAndFilter($sortFilters, $params)
    {
        $dataReturn = new stdClass();
        $dataReturn->tableClass = '';
        $dataReturn->tableDataAttr = array();

        $dataReturn->filters = array();
        $dataReturn->sortables = array();
        if (!empty($sortFilters->cols)) {
            foreach ($sortFilters->cols as $col) {
                $dataReturn->filters[] = (int)$col->filter;
                $dataReturn->sortables[] = (int)$col->sortable;
            }
        }
        if ($sortFilters->enable_filters === 1) {
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $dataReturn->tableDataAttr[] = 'data-columnsFilter="' . htmlspecialchars(json_encode($dataReturn->filters), ENT_COMPAT | ENT_HTML401, 'UTF-8').'"';
        }
        if ($sortFilters->use_sortable === 1) {
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $dataReturn->tableDataAttr[] = 'data-columnsSort="' . htmlspecialchars(json_encode($dataReturn->sortables), ENT_COMPAT | ENT_HTML401, 'UTF-8').'"';
        }

        $dataReturn->enable_filters = (int)$sortFilters->enable_filters;

        $dataReturn->default_sortable = isset($sortFilters->default_sortable) ? (int)$sortFilters->default_sortable : 0;
        if ($params['column_hiden']) {
            $dataReturn->default_sortable = 0;
        }
        $dataReturn->default_order_sortable = (int)$sortFilters->default_order_sortable;

        $dataReturn->use_sortable = (int)$sortFilters->use_sortable;

        if ($dataReturn->use_sortable === 1) {
            $dataReturn->tableClass .= ' sortable';
            $dataReturn->tableDataAttr[] = ' data-ordering="true"';
            $dataReturn->tableDataAttr[] = 'data-ordertarget="'.$dataReturn->default_sortable.'"';
            $default_order_sortable_str = ($dataReturn->default_order_sortable === 1) ? 'asc' : 'desc';
            $dataReturn->tableDataAttr[] = 'data-ordervalue="' . $default_order_sortable_str . '"';
        } else {
            $dataReturn->tableDataAttr[] = ' data-ordering="false"';
        }

        if ($dataReturn->enable_filters === 1) {
            $dataReturn->tableClass .= ' filterable';
        }

        /*get list cols filter range, this has not min, max*/
        $dataReturn->rangeColsNull = array();
        if (!empty($sortFilters->cols) && count($sortFilters->cols) > 0) {
            //phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.array_filter_flagFound -- no support php5.5
            $dataReturn->rangeColsNull = array_filter($sortFilters->cols, function ($value, $key) {
                return $value->searchType === 'range' && $value->searchContentType === 'number';
                //phpcs:ignore PHPCompatibility.Constants.NewConstants.array_filter_use_bothFound -- no support php5.5
            }, ARRAY_FILTER_USE_BOTH);
        }

//        $dataReturn->tableDataAttr[] = $dataReturn->use_sortable === 1 ? ' data-ordering="true"' : ' data-ordering="false"';

        return $dataReturn;
    }

    /**
     * Render html
     *
     * @param object  $table         Data table
     * @param array   $configParams  Config param data
     * @param array   $dataStyle     Data style(tooltip, ...)
     * @param string  $table_hash    Hash string
     * @param boolean $getData       Get data or build header only. Default true
     * @param array   $argsShortCode Range cell render
     * @param boolean $gutenberBlock Preview table in gutengerg
     * @param boolean $readHtmlFile  Write table content to html file
     *
     * @return boolean
     */
    public function htmlRender($table, $configParams, $dataStyle, $table_hash, $getData = true, $argsShortCode = array(), $gutenberBlock = false, $readHtmlFile = true)
    {
        $folder = wp_upload_dir();
        $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (!empty($dataStyle['tooltip'])) {
            $data_tooltip = $dataStyle['tooltip'];
        }
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        if (isset($argsShortCode['range'])) {
            self::$shortCodeHash = hash('md5', json_encode($argsShortCode));
        } else {
            self::$shortCodeHash = '';
        }

        $file = $folder . (self::$shortCodeHash !== '' ? (self::$shortCodeHash . '_') : '') . $table->id . '_' . $table_hash . '.html';
        $tblStyles = $table->style;
        if (!isset($tblStyles->table)) {
            $tblStyles->table = new stdClass();
        }

        if ($getData &&  (empty($table->datas) || $table->datas === '' || !is_array($table->datas))) {
            return true;
        }

        $datas = null;

        if ($getData && isset($table->datas)) {
            $datas = $table->datas;
        }

        $app = Application::getInstance('Wptm');
        $modelConfig = Model::getInstance('configSite', 'site');
        $params = $modelConfig->getConfig();

        if (!file_exists($file)) {
            $files = glob($folder . (self::$shortCodeHash !== '' ? (self::$shortCodeHash . '_') : '') . $table->id . '_*.html');
            foreach ($files as $f) {
                unlink($f);
            }
        } else {
            // sync google sheet automatically on front-end
            $fetchNewData = false;
            $app = Application::getInstance('Wptm');
            if ($app->isSite()) {
                if (isset($params['sync_periodicity']) && (string) $params['sync_periodicity'] !== '0') {
                    if ((isset($tblStyles->table->spreadsheet_url) && $tblStyles->table->spreadsheet_url !== ''
                            && isset($tblStyles->table->auto_sync) && (int)$tblStyles->table->auto_sync === 1)
                        || (isset($tblStyles->table->excel_url) && $tblStyles->table->excel_url !== ''
                            && isset($tblStyles->table->excel_auto_sync) && (int)$tblStyles->table->excel_auto_sync === 1)
                        || (isset($tblStyles->table->csv_url) && $tblStyles->table->csv_url !== ''
                            && isset($tblStyles->table->auto_sync_csv) && (int)$tblStyles->table->auto_sync_csv === 1)
                        || (isset($tblStyles->table->onedrive_url) && $tblStyles->table->onedrive_url !== ''
                            && isset($tblStyles->table->auto_sync_onedrive) && (int)$tblStyles->table->auto_sync_onedrive === 1)) {
                        $modifed = filemtime($file);
                        $time_now = (int) strtotime(date('Y-m-d H:i:s'));

                        if (($time_now - $modifed) / 3600 >= (float)$params['sync_periodicity']) {
                            $fetchNewData = true;

                            // fetch new data
                            require_once $app->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'excel.php';
                            $excel = new WptmControllerExcel();
                            $wptm_syn_google_delay = get_option('wptm_syn_google_delay_' . $table->id, false);
                            if ($wptm_syn_google_delay === false) {
                                $excel->synControl($table, 0);
                            }

                            // get new data
                            /* @var WptmModelTable $model */
                            $model = Model::getInstance('table');
                            $table = $model->getItem($table->id, $getData, true, null, false);
                            $tblStyles = $table->style;
                            $datas = $table->datas;
                        }
                    }
                }
            }

            if (!$readHtmlFile) {
                $fetchNewData = true;
            }

            if (!$fetchNewData && $table->type === 'html') {
                return true;
            }
        }

        $column_hiden = false;
        if (self::$shortCodeHash === '') {
            $count_Col = 0;
            $colWidths = array();
            $hide_column = array();

            if (isset($tblStyles->cols)) {
                foreach ($tblStyles->cols as $key => $col) {
                    if (isset($col)) {
                        $firstCol = is_object($col) ? $col->{1} : $col[1];
                        $zeroCol = is_object($col) ? (string)$col->{0} : (string)$col[0];
                        $hide_column[] = !empty($firstCol->hide_column) ? (int)$firstCol->hide_column : 0;
                        if (isset($firstCol->width) && $hide_column[$count_Col] !== 1) {
                            $colWidths[] = $firstCol->width;
                        } else {
                            $column_hiden = true;
                        }

                        $count_Col++;
                    }
                }
            }

            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $encodeColWidths = htmlspecialchars(json_encode($colWidths), ENT_COMPAT | ENT_HTML401, 'UTF-8');
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $encode_hide_column = htmlspecialchars(json_encode($hide_column), ENT_COMPAT | ENT_HTML401, 'UTF-8');
            //responsive type
            if (isset($tblStyles->table->responsive_type)) {
                if ((string)$tblStyles->table->responsive_type === 'hideCols') {
                    $responsive_type = 'hideCols';
                } elseif ((string)$tblStyles->table->responsive_type === 'scroll') {
                    $responsive_type = 'scroll';
                } else {
                    $responsive_type = 'repeatedHeader';
                }
            } else {
                $responsive_type = 'scroll';
            }

            if (!isset($tblStyles->table->freeze_col)) {
                $tblStyles->table->freeze_col = 0;
            }
            if (!isset($tblStyles->table->freeze_row)) {
                $tblStyles->table->freeze_row = 0;
            }

            //pagination
            $limit = isset($tblStyles->table->limit_rows) ? (int)$tblStyles->table->limit_rows : 0;
            $enable_pagination = isset($tblStyles->table->enable_pagination) ? (int)$tblStyles->table->enable_pagination : 0;

            $style_repeated = isset($tblStyles->table->style_repeated) ? $tblStyles->table->style_repeated : 0;

            $encodeRangeCells = '[]';

            //header
            if (!isset($tblStyles->table->headerOption) || (int)$tblStyles->table->headerOption < 1) {//header not exist
                $heads = 1;
                if (isset($datas[0])) {
                    $header_data = array($datas[0]);
                } elseif (!empty($tblStyles->table->header_data)) {
                    $header_data = $tblStyles->table->header_data;
                } else {
                    $header_data = null;
                }
            } else {
                $heads = (int)$tblStyles->table->headerOption;
                if (isset($tblStyles->table->header_data[(int)$tblStyles->table->headerOption - 1])) {
                    $header_data = $tblStyles->table->header_data;
                } else {//header_data not exist
                    $header_data = array();
                    for ($header_number = 0; $header_number < $tblStyles->table->headerOption; $header_number++) {
                        $header_data[$header_number] = $datas[$header_number];
                    }
                }
            }
        } else {//short code
            $tblStyles->table->freeze_row = 0;
            $tblStyles->table->freeze_col = 0;
            $style_repeated = 0;
            $enable_pagination = 0;
            $limit = 0;
            $tblStyles->table->headerOption = 1;
            $heads = 1;
            $header_data = array();
            $header_data[] = array();
            $responsive_type = 'scroll';

            if (isset($argsShortCode['align'])) {
                $tblStyles->table->table_align = $argsShortCode['align'];
            }

            $rangeCell = self::converRangeCellToArray($argsShortCode['range']);
            if ($rangeCell[2] === 0) {
                $rangeCell[2] = count($datas);
            } else {
                $rangeCell[2]--;
            }
            if ($rangeCell[3] === 0) {
                $rangeCell[3] = count($datas[0]) - 1;
            } else {
                $rangeCell[3]--;
            }

            $colWidths = array();
            $hide_column = array();
            $reduction_column = 0;

            for ($column_index = $rangeCell[1]; $column_index <= $rangeCell[3]; $column_index++) {
                $cols = is_object($tblStyles->cols) ? $tblStyles->cols->{$column_index} : $tblStyles->cols[$column_index];
                $column = is_object($cols) ? $cols->{1} : $cols[1];
                $column->hide_column = !empty($column->hide_column) ? (int)$column->hide_column : 0;
                $hide_column[] = $column->hide_column;
                if (isset($column->width) && $column->hide_column !== 1) {
                    $colWidths[] = $column->width;
                } else {
                    $column_hiden = true;
                }

                $header_data[0][] = $datas[$rangeCell[0]][$column_index];
            }
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $encodeRangeCells = htmlspecialchars(json_encode($rangeCell), ENT_COMPAT | ENT_HTML401, 'UTF-8');
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $encodeColWidths = htmlspecialchars(json_encode($colWidths), ENT_COMPAT | ENT_HTML401, 'UTF-8');
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $encode_hide_column = htmlspecialchars(json_encode($hide_column), ENT_COMPAT | ENT_HTML401, 'UTF-8');
        }

        $addMissingRows = false;
        if (!$getData && is_null($datas)) {//cells range not run
            $datas = $header_data;
            $addMissingRows = true;
        }

        //in gutenberg block, add missing row for tbody when pagination is enable
        $addMissingRows = $gutenberBlock && $addMissingRows;

        //convert to tableshorter dateformat
        $dateFormat = isset($tblStyles->table->date_formats) ? self::momentjsFormat($tblStyles->table->date_formats) : 'mm/dd/yyyy';

        //merge cells
        if (isset($tblStyles->table->mergeSetting)) {
            $mergeSetting = is_string($tblStyles->table->mergeSetting) ? json_decode($tblStyles->table->mergeSetting, true) : $tblStyles->table->mergeSetting;
        } else {
            $mergeSetting = array();
        }

        //hyperlink cells
        if (isset($tblStyles->table->hyperlink) && is_string($tblStyles->table->hyperlink)) {
            $tableHyperlink = json_decode($tblStyles->table->hyperlink);
        } elseif (!isset($tblStyles->table->hyperlink)) {
            $tableHyperlink = new stdClass();
        } else {
            $tableHyperlink = $tblStyles->table->hyperlink;
        }

        $tblCls = '';
        $tableDataAttr = array();

        $res_prioritys = array();
        if ($responsive_type === 'hideCols') {
            foreach ($tblStyles->cols as $col) {
                if (is_object($col)) {
                    $firstCol = $col->{1};
                    $colZero = $col->{0};
                } else {
                    $firstCol = $col[1];
                    $colZero = $col[0];
                }
                if (isset($firstCol->res_priority)) {
                    $res_prioritys[(string) $colZero] = ((string)$firstCol->res_priority === 'persistent') ? 'persistent' : (int) $firstCol->res_priority;
                }
            }
            $tableDataAttr[] = 'data-responsive="true"';
            $tableDataAttr[] = 'data-hideCols="true"';
            $tableDataAttr[] = 'data-hideColslanguage="'.esc_html__('Columns', 'wptm').'"';
        } else {
            $tableDataAttr[] = 'data-responsive="false"';
            $tableDataAttr[] = 'data-hideCols="false"';

            if (($tblStyles->table->freeze_row) || ($tblStyles->table->freeze_col)) {
                $tblCls .= ' fxdHdrCol';
            }

            if ($responsive_type === 'repeatedHeader') {
                if ($style_repeated > 0) {
                    $tblCls .= ' repeatedHeader style_repeated';
                } else {
                    $tblCls .= ' repeatedHeader';
                }
            }
        }

        //check table no body
        $countData = count($datas);

        if (!$enable_pagination && ($countData < 2 || (isset($rangeCell) && $rangeCell[0] === $rangeCell[2]))) {
            $content = '<div class="wptmOneRow wptmresponsive dataTables-wptmtable wptmtable ' . (isset($tblStyles->table->class_name) ? $tblStyles->table->class_name : '') . '" id="wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . '" data-tableid="wptmTbl' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . '">';
        } else {
            $content = '<div class="wptmresponsive dataTables-wptmtable wptmtable ' . (isset($tblStyles->table->class_name) ? $tblStyles->table->class_name : '') . (self::$shortCodeHash === '' ? '' : (' wptm_cellsRange')) . '" id="wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . '" data-tableid="wptmTbl' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . '">';
        }

        // Table type
        if ($table->type === 'html') {
            $tableDataAttr[] = 'data-type="html"';
        } else {
            $tableDataAttr[] = 'data-type="mysql"';
        }

        //pagination
        if (!$enable_pagination || !$limit) {
            $tblCls .= ' disablePager';
        }
        if ($enable_pagination && $limit) {
//            if (($rows - 1) > $limit) {
            $tableDataAttr[] = 'data-paging="true"';
            $tableDataAttr[] = 'data-page-length="' . $limit . '"';
//            } else {
//                $tableDataAttr[] = 'data-paging="false"';
//            }
        } else {
            $tableDataAttr[] = 'data-paging="false"';
        }

        //pagination
        if ($enable_pagination && $limit) {
            $tblCls .= ' enablePager';
        }

        //searching must = true for search dataTable search function work
//        $tableDataAttr[] = ($enable_filters) ? 'data-searching="true"' : 'data-searching="false"';
        $tableDataAttr[] = 'data-searching="true"';

        //filters and sort
        $renderSortAndFilter = $this->renderSortAndFilter($table->sortFilters, array('column_hiden' => $hide_column));
        $tblCls .= $renderSortAndFilter->tableClass;
        $tableDataAttr = array_merge($tableDataAttr, $renderSortAndFilter->tableDataAttr);
        $sortable = $renderSortAndFilter->use_sortable === 1;

        if (count($renderSortAndFilter->rangeColsNull) > 0) {
            $rangeColsNull = array();
//            $rangeColsNull = array_map(function ($value) {
//                $initialize = !empty($value->min) || !empty($value->max) ? false : true;
//                return array('index' => $value->index, 'initialize' => $initialize, 'min' => $value->min !== '' ? $value->min : null, 'max' => $value->max !== '' ? $value->max : null);
//            }, $renderSortAndFilter->rangeColsNull);
            foreach ($renderSortAndFilter->rangeColsNull as $key => $rangeColsNull1) {
                $initialize = !empty($rangeColsNull1->min) || !empty($rangeColsNull1->max) ? false : true;
                $rangeColsNull[$rangeColsNull1->index] = array(
                    'indexFrontEnd' => $key,
                    'initialize' => $initialize,
                    'min' => $rangeColsNull1->min !== '' ? $rangeColsNull1->min : null,
                    'max' => $rangeColsNull1->max !== '' ? $rangeColsNull1->max : null
                );
            }
        }

        if (!isset($tblStyles->table->table_height)) {
            $tblStyles->table->table_height = 0;
        }

        if (!isset($tblStyles->table->table_breakpoint)) {
            $tblStyles->table->table_breakpoint = 980;
        }

        $tableDataAttr[] = 'data-table-breakpoint="'.$tblStyles->table->table_breakpoint.'"';

        //scrolling
        if ($responsive_type === 'scroll' || $responsive_type === 'repeatedHeader') {
            if ((int)$tblStyles->table->table_height !== 0 && $tblStyles->table->table_height !== '') {
                $table_height = $tblStyles->table->table_height . 'px';
            } else {
                $table_height = 'auto';
            }
            $tableDataAttr[] = 'data-scroll-x="true"';
            $tableDataAttr[] = 'data-scroll-collapse="true"';
            if ($tblStyles->table->freeze_col && $responsive_type !== 'repeatedHeader') {
                //freeze column
                $tableDataAttr[] = 'data-freezecol="'.$tblStyles->table->freeze_col.'"';
            }

            $fix_height = '';
            if ($responsive_type === 'repeatedHeader') {
                $fix_height = '#wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . ' table.repeatedHeaderTrue colgroup col[data-dtr="0"] {width: 0 !important;min-width: unset;}';
            }

            if ($tblStyles->table->freeze_row > 0 && $table_height === 'auto') {//fix freeze_row will work if has height
                $table_height = '500px';
            }

            if ($tblStyles->table->freeze_col > 0 && $table_height === 'auto') {//fix error if table_height = 0, freeze_col > 0
                $table_height = '9999px';
            }

            if ($tblStyles->table->freeze_row > 0) {
                if (isset($table_height)) {
                    $tableDataAttr[] = 'data-scroll-y="' . $table_height . '"';
                }
                //freeze row
                $tableDataAttr[] = 'data-freezerow="true"';
            } elseif ($table_height !== 'auto') {
                //fix height when not freeze row
                $fix_height .= '#wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . ' .dataTables_wrapper > .dataTables_scroll {max-height:' . $table_height .';overflow: auto;}';
                if ($tblStyles->table->freeze_col) {
                    $fix_height .= '#wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . ' .dataTables_wrapper > .DTFC_ScrollWrapper {max-height:' . $table_height .';overflow: auto;}';
                }
            } elseif ($table_height === 'auto') {
                //fix height when not freeze row
                $fix_height .= '#wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . ' .dataTables_wrapper > .dataTables_scroll {max-height: unset;overflow: auto;}';
                if ($tblStyles->table->freeze_col) {
                    $fix_height .= '#wptmtable' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int) $table->id . ' .dataTables_wrapper > .DTFC_ScrollWrapper {max-height: unset;overflow: auto;}';
                }
            }
        }
        $table_align = !isset($tblStyles->table->table_align) ? 'left' : $tblStyles->table->table_align;
        $tableDataAttr[] = 'data-align="'.$table_align.'"';
        $tableDataAttrStr = implode(' ', $tableDataAttr);

        if (self::$shortCodeHash !== '') {
            $tblCls .= ' ' . self::$shortCodeHash;
        }


        /*fix sync header and body when scroll horizontal in mobile, how to solve without filter, sort, paging: move header to body and hide old header*/
        $hideScrollHeader = '';
        if ($responsive_type === 'scroll' && ($renderSortAndFilter->enable_filters === 0) && !$sortable && $enable_pagination === 0) {
//        if ($responsive_type === 'scroll') {
            $hideScrollHeader = ' hideScrollHeader';
        }

        if (!isset($this->date_formats) || $this->date_formats === '') {
            $this->date_formats    = (!empty($configParams['date_formats'])) ? $configParams['date_formats'] : 'Y-m-d';
            $this->date_formats    = (!empty($tblStyles->table->date_formats)) ? $tblStyles->table->date_formats : $this->date_formats;
        }
        if (!isset($this->symbol_position) || $this->symbol_position === '') {
            $this->symbol_position = (!empty($configParams['symbol_position'])) ? $configParams['symbol_position'] : 0;
            $this->symbol_position = (!empty($tblStyles->table->symbol_position)) ? $tblStyles->table->symbol_position : $this->symbol_position;
        }
        if (!isset($this->currency_symbol) || $this->currency_symbol === '') {
            $this->currency_symbol = (!empty($configParams['currency_sym'])) ? $configParams['currency_sym'] : '$';
            $this->currency_symbol = (!empty($tblStyles->table->currency_symbol)) ? $tblStyles->table->currency_symbol : $this->currency_symbol;
        }
        if (!isset($this->decimal_symbol) || $this->decimal_symbol === '') {
            $this->decimal_symbol  = (!empty($configParams['decimal_sym'])) ? $configParams['decimal_sym'] : '.';
            $this->decimal_symbol  = (!empty($tblStyles->table->decimal_symbol)) ? $tblStyles->table->decimal_symbol : $this->decimal_symbol;
        }
        if (!isset($this->decimal_count) || $this->decimal_count === '') {
            $this->decimal_count   = (!empty($configParams['decimal_count'])) ? $configParams['decimal_count'] : 0;
            $this->decimal_count   = (isset($tblStyles->table->decimal_count)) ? $tblStyles->table->decimal_count : $this->decimal_count;
        }
        if (!isset($this->thousand_symbol) || $this->thousand_symbol === '') {
            $this->thousand_symbol = (!empty($configParams['thousand_sym'])) ? $configParams['thousand_sym'] : ',';
            $this->thousand_symbol = isset($tblStyles->table->thousand_symbol) ? $tblStyles->table->thousand_symbol : $this->thousand_symbol;
        }

        $has_format_number_table = ' data-format-decimal_count="' . $this->decimal_count
            . '" data-format-decimal_symbol="' . $this->decimal_symbol
            . '" data-format-symbol_position="' . $this->symbol_position
            . '" data-format-thousand_symbol="' . $this->thousand_symbol
            . '"  data-format-currency_symbol="' . $this->currency_symbol . '" ';

        $content .= '<table data-count-header="' . $heads . '" ' . $has_format_number_table . ' data-name-style="' . $table->id . '_' . $table_hash . '.css" data-format="' . $dateFormat . '" id="wptmTbl'
            . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . (int)$table->id . '" data-id="' . (self::$shortCodeHash === '' ? '' : (self::$shortCodeHash . '_')) . $table->id
            . '" '.$tableDataAttrStr.' data-range="' . $encodeRangeCells . '" data-hideColumn="' . $encode_hide_column . '" data-colwidths="' . $encodeColWidths . '" class="' . $tblCls . $hideScrollHeader . '">';

        $rowNb = 0;
        $rowNbShortCode = 0;
        $limit_rows = 0;

        // Fix Col width issue for Mac
        $firstRow = $datas[0];
        $content .= '<colgroup>';
        $countFirstRow = count($firstRow);
        $createDataTableForBlock = array();

        for ($colNb = 0; $colNb < $countFirstRow; $colNb ++) {
            if (!(isset($rangeCell) && ($colNb < $rangeCell[1] || $colNb > $rangeCell[3]))) {
                if (!(isset($hide_column[$colNb]) && $hide_column[$colNb] === 1)) {
                    $content .= '<col data-dtr="' . $rowNb . '" data-dtc="' . $colNb . '" class="dtc' . $colNb . '">';
                }
            }
            $createDataTableForBlock[$colNb] = array('data' => $colNb, 'searchable' => true);
            $createDataTableForBlock[$colNb]['name'] = '';
            $createDataTableForBlock[$colNb]['orderable'] = false;
            $createDataTableForBlock[$colNb]['search']['value'] = '';
            $createDataTableForBlock[$colNb]['search']['regex'] = false;
        }
        $content .= '</colgroup>';
        $tfoot = '';
        $maxColNb = 0;

        if ($datas !== null) {
            if ($addMissingRows) {
                $folder_admin = dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site';//to calculation
                require_once $folder_admin . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'table.php';
                $spreadsheet = new WptmControllerTable();

                $newdata = $spreadsheet->loadPage($table->id, $createDataTable = array('notAjax' => true, 'start' => 0, 'draw' => 1, 'length' => $limit, 'columns' => $createDataTableForBlock));

                if (!empty($newdata['data'])) {
                    for ($i = 0; $i < $limit; $i++) {
                        if (!empty($newdata['data'][$i])) {
                            $newRow = array();
                            foreach ($newdata['data'][$i] as $key => $row) {
                                if ($key === 0 || (int)$key > 0) {
                                    $newRow[] = $row;
                                }
                            }
                            $datas[] = $newRow;
                        }
                    }
                }
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $activeSheet = $spreadsheet->createSheet(1);
            $maxRows = count($datas);
            $activeSheet->fromArray($this->renderValueCalculateCell($datas, $maxRows), null, 'A1');

            /*convert Separator for cells*/
//            $formatCode = $this->createFormatCode();
//            $activeSheet->getStyle('A1:' . $maxCell['column'] . $maxCell['row'])->getNumberFormat()->setFormatCode('$ #,##0.00_-');
//            $activeSheet->getStyle('A1:' . $maxCell['column'] . $maxCell['row'])->getNumberFormat()>setFormatCode('"$" #,##0.00');

            foreach ($datas as $krow => $row) {
                if (!empty($tblStyles->table->limit_rows)) {
                    $limit_rows ++;
                }

                if (!(isset($rangeCell) && ($rowNb < $rangeCell[0] || $rowNb > $rangeCell[2]))) {
                    if ($rowNb === 0 || (isset($rangeCell) && $rowNb === $rangeCell[0])) {
                        $content .= '<thead>';
                    } elseif ($rowNb !== 0 && $rowNb === $heads) {
                        $content .= '<tbody>';
                    }

//                    if (($enable_pagination && !isset($rangeCell) && $krow > $limit)) {
//                        break;
//                    }


                    if ($rowNb === 0 || (isset($rangeCell) && $rowNb === $rangeCell[0])) {
                        $content .= '<tr class="wptm-header-cells-index dnone">';
                        $headerColNb = 0;
                        foreach ($row as $index_column_header => $col_value) {
                            if (!(isset($rangeCell) && ($index_column_header < $rangeCell[1] || $index_column_header > $rangeCell[3]))) {
                                if (!(isset($hide_column[$headerColNb]) && $hide_column[$headerColNb] === 1)) {
                                    $content .= '<th rowspan="1" colspan="1" data-dtr="' . $rowNb . '" data-dtc="' . $headerColNb . '" class="dtr' . $rowNb . ' dtc' . $headerColNb . '">';
                                    $content .= '';
                                    $content .= '</th>';
                                }
                                $headerColNb++;
                            }
                        }
                        $content .= '</tr>';
                    }

                    // when have pagination---> freeze_row = limit_rows (joomla)
                    if ($limit_rows <= $tblStyles->table->freeze_row) {
                        $content .= '<tr class=" row' . $rowNb . (isset($rangeCell) ? ' rowShortCode' . $rowNbShortCode : '') . '">';
                    } else {
                        $content .= '<tr class="droptable_none row' . $rowNb . (isset($rangeCell) ? ' rowShortCode' . $rowNbShortCode : '') . '">';
                    }
                    $colNb = 0;
                    $columnKeyRange = 0;

                    foreach ($row as $k => $col) {
                        if ($k >= 0) {
                            if (!(isset($rangeCell) && ($colNb < $rangeCell[1] || $colNb > $rangeCell[3]))) {
                                $position = array();
                                $position[] = self::getNameFromNumber($k);
                                $position[] = $krow + 1;
                                $position[] = $k;

                                $dataSortAttr = '';
                                $col = ($col || $col === '0') ? $col : '';
                                if ($heads > $rowNb && $header_data !== null) {//in header
                                    $col = $header_data[$rowNb][$columnKeyRange];
                                }

                                if (isset($tblStyles->cells[$rowNb . '!' . $colNb])) {
                                    $tblStyle = $tblStyles->cells[$rowNb . '!' . $colNb][2];
                                } else {
                                    $tblStyle = array();
                                }

                                if (isset($tblStyle['date_formats_momentjs']) && $tblStyle['date_formats_momentjs'] !== false) {
                                    $has_format_date_cell = 'data-format="' . $tblStyle['date_formats_momentjs'] . '"';
                                } else {
                                    $has_format_date_cell = '0';
                                }

                                $cellHtml = '';
                                if (isset($tblStyle)
                                    && isset($tblStyle['cell_type'])
                                    && (string)$tblStyle['cell_type'] === 'html') {//html cell
                                    if (isset($tableHyperlink->{$rowNb . '!' . $colNb}) && $col !== ''  && strpos($col, $tableHyperlink->{$rowNb . '!' . $colNb}->hyperlink) >= 0) {
                                        if (isset($params['openHyperlink']) && (int)$params['openHyperlink'] === 0) {
                                            $cellHtml = '<a href="' . $tableHyperlink->{$rowNb . '!' . $colNb}->hyperlink . '">' . $col . '</a>';
                                        } else {
                                            $cellHtml = '<a target="_blank" href="' . $tableHyperlink->{$rowNb . '!' . $colNb}->hyperlink . '">' . $col . '</a>';
                                        }
                                    } else {
                                        $cellHtml = $col;
                                    }

                                    $cellHtml = esc_textarea($cellHtml);
                                } else {
                                    $contentCell = $this->getContentCell($tblStyle, $datas, $tblStyles, $col, $activeSheet, $position, $has_format_date_cell);
                                    $cellHtml = $contentCell[0];
                                    $has_format_date_cell = $contentCell[1];

                                    if (isset($rangeColsNull)
                                        && isset($rangeColsNull[$colNb])
                                        && $rangeColsNull[$colNb]['initialize']
                                        && !((isset($rangeCell) && $rowNb === $rangeCell[0]) || $rowNb < $heads)) {
                                        $rangeColsNull[$colNb]['min'] = $rangeColsNull[$colNb]['min'] === null || floatval($cellHtml) <= $rangeColsNull[$colNb]['min'] ? floatval($cellHtml) : $rangeColsNull[$colNb]['min'];
                                        $rangeColsNull[$colNb]['max'] = $rangeColsNull[$colNb]['max'] === null || floatval($cellHtml) >= $rangeColsNull[$colNb]['max'] ? floatval($cellHtml) : $rangeColsNull[$colNb]['max'];
                                    }
                                }

                                $has_format_number_cell = '';
                                if (self::$decimal_symbol_cell !== null) {
                                    $has_format_number_cell = ' data-format-decimal_count="' . self::$decimal_count_cell
                                        . '" data-format-decimal_symbol="' . self::$decimal_symbol_cell
                                        . '" data-format-symbol_position="' . self::$symbol_position_cell
                                        . '" data-format-thousand_symbol="' . self::$thousand_symbol_cell
                                        . '" data-format-currency_symbol="' . self::$currency_symbol_cell . '" ';
                                } elseif ($has_format_number_cell) {
                                    $has_format_number_cell = ' data-format-decimal_count="' . self::$decimal_count_cell
                                        . '" data-format-decimal_symbol="' . self::$decimal_symbol_cell
                                        . '" data-format-thousand_symbol="' . self::$thousand_symbol_cell . '" ';
                                }

                                //fix wrap text
                                if (isset($tblStyle)
                                    && isset($tblStyle['text_wrapping'])
                                    && ((string)$tblStyle['text_wrapping'] === 'normal' || (string)$tblStyle['text_wrapping'] === 'hidden')) {
                                    if (isset($tblStyle['cell_type']) && (string)$tblStyle['cell_type'] === 'html') {
                                        $cellHtml = '<div>' . $cellHtml . '</div>';
                                    } else {
                                        $cellHtml = '<p>' . $cellHtml . '</p>';
                                    }
                                }

                                $isDateFormat = $this->validateDate(($col || $col === '0') ? $col : '', $this->date_formats);
                                if ($isDateFormat && !$enable_pagination) {//no add timestamp sort when paging
                                    $newDate = DateTime::createFromFormat($this->date_formats, $col);
                                    $dataSortAttr = 'data-timestamp="'.$newDate->getTimestamp().'"';
                                }

                                $mergeInfoAndCheckMerge = $rowNb >= $heads ? self::checkMergeInfo($rowNb, $colNb, $mergeSetting, false) : self::checkMergeInfo($rowNb, $colNb, $mergeSetting, true);
                                $mergeInfo              = $mergeInfoAndCheckMerge[0];
                                $mergeSetting           = $mergeInfoAndCheckMerge[1];
                                if (isset($rangeCell)) {
                                    $columnKey = $columnKeyRange;
                                } else {
                                    $columnKey = $colNb;
                                }
                                if ($mergeInfo !== false
                                    && ((isset($hide_column[$columnKey]) && $hide_column[$columnKey] === 1 && $mergeInfo !== '')
                                        || !(isset($hide_column[$columnKey]) && $hide_column[$columnKey] === 1)
                                    )
                                ) {//fix MergerHeader And HidenCol merger[0]
                                    if ((isset($rangeCell) && $rowNb === $rangeCell[0]) || $rowNb < $heads) {
                                        $dataPriority = !empty($res_prioritys) && isset($res_prioritys[$colNb]) ? 'data-priority="'.$res_prioritys[$colNb].'"' : 'data-priority="0"';
                                        $content   .= '<th ' . $has_format_number_cell . ($mergeInfo === '0' ? '' : $mergeInfo) . ' ' . ($has_format_date_cell === '0' ? '' : $has_format_date_cell) . ' '.$dataPriority.' class="dtr' . $rowNb . ' dtc' . $colNb . '">';
                                    } else {
                                        $content .= '<td ' . $has_format_number_cell . ($mergeInfo === '0' ? '' : $mergeInfo) . ' ' . ($has_format_date_cell === '0' ? '' : $has_format_date_cell) . ' ' . $dataSortAttr .' class="dtr' . $rowNb . ' dtc' . $colNb . '">';
                                    }
                                    if (isset($tblStyles->cells[$rowNb . '!' . $colNb]) && isset($tblStyles->cells[$rowNb . '!' . $colNb][2]['tooltip_content']) && (string) $tblStyles->cells[$rowNb . '!' . $colNb][2]['tooltip_content'] !== '') {
                                        if (!empty($data_tooltip[$rowNb . '_' . $colNb])) {
                                            $content .= '<span class="wptm_tooltip ">'
                                                . $cellHtml . '<span class="wptm_tooltipcontent" data-width="' . $data_tooltip[$rowNb . '_' . $colNb] . '">'
                                                . $tblStyles->cells[$rowNb . '!' . $colNb][2]['tooltip_content'] . '</span></span>';
                                        } else {
                                            $content .= '<span class="wptm_tooltip ">'
                                                . $cellHtml . '<span class="wptm_tooltipcontent">'
                                                . $tblStyles->cells[$rowNb . '!' . $colNb][2]['tooltip_content'] . '</span></span>';
                                        }
                                    } else {
                                        $content .= $cellHtml;
                                    }

                                    if ($rowNb < $heads || (isset($rangeCell) && $rowNb === $rangeCell[0])) {
                                        $content .= '</th>';
                                    } else {
                                        $content .= '</td>';
                                    }
                                }
                                $columnKeyRange++;
                            }
                            $colNb ++;
                        }
                    }
                    if ($rowNb === $heads - 1 || (isset($rangeCell) && $rowNb === $rangeCell[0])) {
                        $content .= '</thead>';
                    } else {
                        $content .= '</tr>';
                    }
                    $rowNbShortCode++;
                }

                $maxColNb = $maxColNb > $colNb ? $maxColNb : $colNb;

                $rowNb ++;
            }

//            if (!$enable_pagination) {//fix merger cells in row end
            if (!$enable_pagination || ($enable_pagination && $responsive_type === 'hideCols')) {//fix merger cells in row end and (error paging + hiding columns)
                for ($kTfoot = 0; $kTfoot < $maxColNb; $kTfoot++) {
                    if (!(isset($rangeCell) && ($colNb < $rangeCell[1] || $colNb > $rangeCell[3]))) {
                        if (!(isset($hide_column[$kTfoot]) && $hide_column[$kTfoot] === 1)) {
                            $tfoot .= '<td class="dtr' . ($rowNb - 1) . ' dtc' . $kTfoot . '" style="height: 0;"></td>';
                        }
                    }
                }
            }
        }

        if (isset($rangeColsNull)) {
            $this->rangeColsNull = $rangeColsNull;

            $jsTab = '<script>';
            $jsTab .= ' if (typeof wptmFormSearchRange === "undefined") {var wptmFormSearchRange = {};}';
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $jsTab .= 'wptmFormSearchRange.table' . $table->id . ' = ' . htmlspecialchars(json_encode($rangeColsNull), ENT_COMPAT | ENT_HTML401, 'UTF-8') . ';';
            $jsTab .= '</script>';
        } else {
            $jsTab = '';
        }

        $content .= '</tbody>';

        $content .= '<tfoot><tr>';//fix merger cells in row end
        $content .= $tfoot;
        $content .= '</tr></tfoot>';
        $content .= '</table></div>';
        if (isset($fix_height)) {
            $content .= '<style>' . $fix_height . '</style>';
        }

        $content .= $jsTab;

        if ($table->type === 'html' && $readHtmlFile) {//html table//write table content to html file only when read html file
            if (!file_put_contents($file, esc_html($content))) {
                echo 'error saving file!';
                return false;
            }
        } else {
            return $content;
        }

        return true;
    }

    /**
     * Convert content by a date format
     *
     * @param string       $value           Value format
     * @param null|string  $format          Format
     * @param null|boolean $returnTimestamp Specifies the return value
     * @param string       $resultFormat    Format for result
     *
     * @return boolean|integer|string
     */
    public function convertDateToTimestamp($value, $format = null, $returnTimestamp = null, $resultFormat = 'Y/m/d H:i:s')
    {
        if ($format === null) {
            try {
                //phpcs:ignore PHPCompatibility.Classes.NewClasses.datetimeimmutableFound -- no support php version <= 5.4
                $createFromFormat = new DateTimeImmutable($value);
            } catch (Exception $e) {
                $createFromFormat = false;
            }
        } else {
            try {
                //phpcs:ignore PHPCompatibility.Classes.NewClasses.datetimeimmutableFound -- no support php version <= 5.4
                $createFromFormat = DateTimeImmutable::createFromFormat($format, $value);
            } catch (Exception $e) {
                $createFromFormat = false;
            }
        }
        if ($createFromFormat !== false) {
            return $returnTimestamp === null ? $createFromFormat->format($resultFormat) : $createFromFormat->getTimestamp();
        } else {
            return false;
        }
    }

    /**
     * Get cells content for table
     *
     * @param array  $tblStyleCell         Cell params
     * @param array  $datas                Table tbody data
     * @param object $tblStyles            Table style
     * @param string $col                  Cell value
     * @param object $activeSheet          PhpSpreadsheet
     * @param array  $position             Cell position
     * @param string $has_format_date_cell Check cell has format
     *
     * @return array
     */
    public function getContentCell($tblStyleCell, $datas, $tblStyles, $col, $activeSheet, $position, $has_format_date_cell)
    {
        $calculatorCell = isset($col[0]) && $col[0] === '=';
        $formatCell = $this->getFormatCell($tblStyleCell);
        self::$currency_symbol_cell = $formatCell['currency_symbol'];
        self::$thousand_symbol_cell = $formatCell['thousand_symbol'];
        self::$decimal_count_cell = $formatCell['decimal_count'];
        self::$decimal_symbol_cell = $formatCell['decimal_symbol'];
        self::$symbol_position_cell = $formatCell['symbol_position'];
        $has_format_number_cell = self::$decimal_symbol_cell !== null;

        $krow = $position[1] - 1;
        $k = $position[2];

        if ($calculatorCell) {//calculator cell
            $calculaterCell = $this->calculationCells($datas, $tblStyles->cells, $col, $activeSheet, array($krow, $k));//value by table format

            //CONVERT value to float
            $calculaterCell = $this->convertNumberByFormat($calculaterCell, '.', array(
                'currency_symbol' => $this->currency_symbol,
                'decimal_symbol' => $this->decimal_symbol,
                'thousand_symbol' => $this->thousand_symbol,
            ), self::$decimal_count_cell > $this->decimal_count ? self::$decimal_count_cell : $this->decimal_count, '');

            if (is_array($calculaterCell)) {
                if (strtoupper($calculaterCell[0]) === 'DATE') {
                    if ($has_format_date_cell === '0') {
                        $has_format_date_cell = ' data-format="1" ';
                    }
                    $calculaterCell = $calculaterCell[1];

                    $convertDateToTimestamp = $this->convertDateToTimestamp($calculaterCell, null);
                    if ($convertDateToTimestamp === false) {
                        $convertDateToTimestamp = $this->convertDateToTimestamp($calculaterCell, $this->date_formats);
                    }

                    $calculaterCell = $convertDateToTimestamp !== false ? $convertDateToTimestamp : $calculaterCell;
                } elseif (in_array(strtoupper($calculaterCell[0]), array('DAYS360','DAYS','DAY')) !== false) {
                    $calculaterCell = $calculaterCell[1];
                    $has_format_date_cell = '0';
                } else {
                    $calculaterCell = 'NaN';
                }
            }
            $cellHtml = is_bool($calculaterCell) ? ($calculaterCell ? 'true' : 'false') : $calculaterCell;
        } else {//default cell
            if ($has_format_date_cell !== '0') {//not dateTime cell
                $col = $activeSheet->getCell($position[0] . $position[1])->getValue();

                $convertDateToTimestamp = $this->convertDateToTimestamp($col, null);
                if ($convertDateToTimestamp === false) {
                    $convertDateToTimestamp = $this->convertDateToTimestamp($col, $this->date_formats);
                }

                $col = $convertDateToTimestamp !== false ? $convertDateToTimestamp : $col;
            } elseif ($has_format_number_cell && $col !== '' && $col !== null) {//has format number
                $isNumber = !empty(self::$currency_symbol_cell)
                    ? preg_replace('/[-|0-9|,|\.|' . self::$currency_symbol_cell . '| ]/', '', $col) === ''
                    : preg_replace('/[-|0-9|,|\.| ]/', '', $col) === '';

                if ($isNumber) {
                    $col = $this->convertNumberByFormat($col, '.', array(), $this->decimal_count, '');
                }
            }
            $cellHtml = nl2br(($col || $col === '0') ? $col : '');

            if (!strpos($cellHtml, '<br')) {
                $cellHtml = esc_html($cellHtml);
                $cellHtml = esc_textarea($cellHtml);
            }
        }

        return array($cellHtml, $has_format_date_cell);
    }

    /**
     * Get format cell
     *
     * @param array $tblStyle Cell params
     *
     * @return array
     */
    public function getFormatCell($tblStyle)
    {
        $format = array();

        if ((isset($tblStyle['decimal_count']) && $tblStyle['decimal_count'] !== false)
            || (isset($tblStyle['decimal_symbol']) && $tblStyle['decimal_symbol'] !== false)
        ) {
            $format['thousand_symbol'] = isset($tblStyle['thousand_symbol']) && $tblStyle['thousand_symbol'] !== false
                ? $tblStyle['thousand_symbol'] : $this->thousand_symbol;
            $format['decimal_count'] = (isset($tblStyle['decimal_count']) && $tblStyle['decimal_count'] !== false)
                ? $tblStyle['decimal_count'] : $this->decimal_count;
            $format['decimal_symbol'] = (isset($tblStyle['decimal_symbol']) && $tblStyle['decimal_symbol'] !== false)
                ? $tblStyle['decimal_symbol'] : $this->decimal_symbol;
        } else {
            $format['thousand_symbol'] = null;
            $format['decimal_count'] = null;
            $format['decimal_symbol'] = null;
        }

        if (isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== false) {
            $format['currency_symbol'] = (isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== false)
                ? $tblStyle['currency_symbol'] : $this->currency_symbol;
            $format['symbol_position'] = (isset($tblStyle['symbol_position']) && $tblStyle['symbol_position'] !== false)
                ? $tblStyle['symbol_position'] : $this->symbol_position;

            if ($format['decimal_symbol'] === null) {
                $format['thousand_symbol'] = $this->thousand_symbol;
                $format['decimal_count'] = $this->decimal_count;
                $format['decimal_symbol'] = $this->decimal_symbol;
            }
        } else {
            $format['currency_symbol'] = null;
            $format['symbol_position'] = null;
        }

        return $format;
    }

    /**
     * Calculation value a cell, return by table format
     *
     * @param array  $datas         Cells value
     * @param array  $tblStyleCells Format of cells
     * @param string $value         Cell content
     * @param object $activeSheet   PhpSpreadsheet
     * @param array  $positionCell  Cell position eg: array(row(excel), coll(excel, int))
     *
     * @return array|string
     */
    public function calculationCells($datas, $tblStyleCells, $value, $activeSheet, $positionCell)
    {
        $format = array();

        $position = array();
        $position[] = self::getNameFromNumber($positionCell[1]);
        $position[] = $positionCell[0] + 1;

        if (isset($tblStyleCells[$positionCell[0] . '!' . $positionCell[1]])) {
            $tblStyle = $tblStyleCells[$positionCell[0] . '!' . $positionCell[1]][2];
        } else {
            $tblStyle = array();
        }


        $format = $this->getFormatCell($tblStyle);
        $has_format_number_cell = $format['decimal_symbol'] !== null;

        //support calculator
        if (preg_match('@^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$@', $value, $matches)) {
            $formula = strtoupper($matches[1]);
            preg_match('/(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\((.*?)\)+/', $matches[2], $matches2);
            if (isset($matches2[2])) {
                $contentMatch = $matches2[0];
                $activeSheet->getCell('Z' . $this->alternativPosition)->setValue('=' . $contentMatch);
                $this->alternativPosition++;
                $newContent = $this->calculationCells($datas, $tblStyleCells, '=' . $contentMatch, $activeSheet, array($this->alternativPosition, 26));
                $matches[2] = str_replace($contentMatch, $newContent, $matches[2]);
                $matches[0] = str_replace($contentMatch, $newContent, $matches[0]);

                $activeSheet->getCell($position[0] . $position[1])->setValue(str_replace(';', ',', $matches[0]));
            }

            //check formula function to replace input value
            if (in_array($formula, $this->math_formula)) {
                $format['decimal_count'] = (isset($tblStyle['decimal_count_second']) && $tblStyle['decimal_count_second'] !== false) ? $tblStyle['decimal_count_second'] : $format['decimal_count'];
                $format['decimal_symbol'] = (isset($tblStyle['decimal_symbol_second']) && $tblStyle['decimal_symbol_second'] !== false) ? $tblStyle['decimal_symbol_second'] : $format['decimal_symbol'];
                $format['thousand_symbol'] = (isset($tblStyle['thousand_symbol_second']) && $tblStyle['thousand_symbol_second'] !== false) ? $tblStyle['thousand_symbol_second'] : $format['thousand_symbol'];
                $format['currency_symbol'] = (isset($tblStyle['currency_symbol_second']) && $tblStyle['currency_symbol_second'] !== false) ? $tblStyle['currency_symbol_second'] : $format['currency_symbol'];
                $format['symbol_position'] = (isset($tblStyle['symbol_position_second']) && $tblStyle['symbol_position_second'] !== false) ? $tblStyle['symbol_position_second'] : $format['symbol_position'];
            }

            $calculaterCell2 = $this->calculaterCell2($datas, $matches, $activeSheet, $position, false, $format, $tblStyleCells);

            if (is_array($calculaterCell2)) {
                $calculaterCell = $calculaterCell2[0];
            } else {
                $calculaterCell = $calculaterCell2;
            }
        } else {
            if (preg_match('@^=(RANK|rank)\\((.*?)\\)$@', $value, $matches)) {
                $value = str_replace(';', ',', $value);
            }
            $activeSheet->getCell('Z' . $this->alternativPosition)->setValue($value);//fix unknown error, when calculated cell is in column A
            $calculaterCell = $activeSheet->getCell('Z' . $this->alternativPosition)->getCalculatedValue();
            $this->alternativPosition++;
        }

        //format calculator value from format cell to table format
        $calculaterCell = $this->convertNumberByFormat($calculaterCell, $this->decimal_symbol, $format, 9, $this->thousand_symbol);

        return $calculaterCell;
    }

    /**
     * Convert cell range to a array
     *
     * @param string $value Cell ranger
     *
     * @return array
     */
    public static function converRangeCellToArray($value)
    {
        $data = explode('-', $value);
        $startCol = preg_replace('/[0-9]/', '', $data[0]);
        $startCol = $startCol === '' ? 0 : self::convertAlpha($startCol) - 1;
        $startRow = preg_replace('/[A-Za-z]/', '', $data[0]);
        $startRow = $startRow === '' ? 0 : $startRow - 1;
        $endCol = preg_replace('/[0-9]/', '', $data[1]);
        $endCol = $endCol === '' ? 0 : self::convertAlpha($endCol);
        $endRow = preg_replace('/[A-Za-z]/', '', $data[1]);
        $endRow = $endRow === '' ? 0 : $endRow;

        return array($startRow, $startCol, $endRow, $endCol);
    }

    /**
     * Convert droptables's dateformat to momentjs's dateformat
     *
     * @param string $value PHP date format
     *
     * @return string
     */
    public static function momentjsFormat($value)
    {
        $value = str_replace('M', 'MMM', $value);
        $value = str_replace('F', 'MMMM', $value);
        $value = str_replace('m', 'MM', $value);
        $value = str_replace('n', 'M', $value);

        $value = str_replace('D', 'ddd', $value);
        $regex = '/(?<!d)d(?!d)/i';
        $value = preg_replace($regex, 'DD', $value);

        $value = str_replace('j', 'D', $value);
        $value = str_replace('z', 'DDDD', $value);
        $value = str_replace('S', 'Do', $value);
        $value = str_replace('l', 'dddd', $value);
        $value = str_replace('N', 'e', $value);

        $value = str_replace('w', 'do', $value);
        $value = str_replace('W', 'w', $value);

        $value = str_replace('Y', 'YYYY', $value);
        $value = str_replace('y', 'YY', $value);
        $value = str_replace('o', 'Y', $value);

        $value = str_replace('h', 'hh', $value);
        $value = str_replace('g', 'h', $value);
        $value = str_replace('H', 'HH', $value);
        $value = str_replace('G', 'H', $value);

        $value = str_replace('i', 'mm', $value);
        $value = str_replace('s', 'ss', $value);
        $value = str_replace('T', 'z', $value);
        return $value;
    }

    /**
     * Setup some params for wptm helper
     *
     * @param array $params Table params
     *
     * @return array
     */
    public function setup($params)
    {
        Application::getInstance('Wptm');
        /* @var WptmModelConfigsite $configModel */
        $configModel = Model::getInstance('configsite');
        $configParams = $configModel->getConfig();

        $this->date_formats    = (!empty($configParams['date_formats'])) ? $configParams['date_formats'] : 'Y-m-d';
        $this->date_formats    = (!empty($params->date_formats)) ? $params->date_formats : $this->date_formats;

        $this->symbol_position = (!empty($configParams['symbol_position'])) ? $configParams['symbol_position'] : 0;
        $this->symbol_position = (!empty($params->symbol_position)) ? $params->symbol_position : $this->symbol_position;

        $this->currency_symbol = (!empty($configParams['currency_sym'])) ? $configParams['currency_sym'] : '$';
        $this->currency_symbol = (!empty($params->currency_symbol)) ? $params->currency_symbol : $this->currency_symbol;
        $this->currency_symbol = str_replace(' ', '', $this->currency_symbol);

        $this->decimal_symbol  = (!empty($configParams['decimal_sym'])) ? $configParams['decimal_sym'] : '.';
        $this->decimal_symbol  = (!empty($params->decimal_symbol)) ? $params->decimal_symbol : $this->decimal_symbol;

        $this->decimal_count   = (!empty($configParams['decimal_count'])) ? $configParams['decimal_count'] : 0;
        $this->decimal_count   = (isset($params->decimal_count)) ? $params->decimal_count : $this->decimal_count;

        $this->thousand_symbol = (!empty($configParams['thousand_sym'])) ? $configParams['thousand_sym'] : ',';
        $this->thousand_symbol = isset($params->thousand_symbol) ? $params->thousand_symbol : $this->thousand_symbol;

        return array(
            $this->date_formats,
            $this->symbol_position,
            $this->currency_symbol,
            $this->decimal_symbol,
            $this->decimal_count,
            $this->thousand_symbol
        );
    }

    /**
     * Function replace ';' to ',' of cell value in calculate cell
     *
     * @param array  $datas   Data cell
     * @param string $maxRows Max number row
     *
     * @return mixed
     */
    public function renderValueCalculateCell($datas, $maxRows)
    {
        for ($i = 0; $i < $maxRows; $i++) {
            $datas[$i] = array_map(
                function ($data) {
                    $data = (string)$data;
                    $key = substr($data, 0, 1);
                    $pattern = '@^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|count|min|max|avg|concat)\\((.*?)\\)$@';
                    if ($key === '=' && preg_match($pattern, $data, $matches)) {
                        $data = str_replace(';', ',', $data);
                    }

                    return $data;
                },
                $datas[$i]
            );
        }

        return $datas;
    }

    /**
     * Convert number to string
     *
     * @param number $num Number
     *
     * @return string
     */
    public function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }

    /**
     * Convert number by format of table
     *
     * @param string     $inputData      Input value
     * @param string     $decimalSymbol  Input decimal symbol
     * @param null|array $format         Format of cell
     * @param integer    $decimalCount   Input decimal count
     * @param string     $thousandSymbol Input thousand symbol
     *
     * @return string
     */
    public function convertNumberByFormat($inputData, $decimalSymbol, $format, $decimalCount = 9, $thousandSymbol = '')
    {
        $format = array(
            'currency_symbol' => !empty($format['currency_symbol']) && $format['currency_symbol'] !== null ? $format['currency_symbol'] : $this->currency_symbol,
            'decimal_symbol' => !empty($format['decimal_symbol']) && $format['decimal_symbol'] !== null ? $format['decimal_symbol'] : $this->decimal_symbol,
            'thousand_symbol' => !empty($format['thousand_symbol']) && $format['thousand_symbol'] !== null ? $format['thousand_symbol'] : $this->thousand_symbol,
        );

        $isNumber = preg_replace('/[-|0-9|,|\.|' . $format['currency_symbol'] . '| ]/', '', $inputData) === '';

        if ($isNumber) {//content is number
            $charactersRemove = $format['thousand_symbol'] === ' ' ? ($format['decimal_symbol'] === '.' ? ',' : '\.') : $format['thousand_symbol'];
            if ($charactersRemove !== null && $format['currency_symbol'] !== null) {
                $inputData = preg_replace('/[' . $charactersRemove . '|' . $format['currency_symbol'] . '| ]/', '', $inputData);
            } elseif ($format['currency_symbol'] !== null) {
                $inputData = preg_replace('/[' . $format['currency_symbol'] . '| ]/', '', $inputData);
            }

            $decimal_symbol = $format['decimal_symbol'] === '.' ? '\.' : $format['decimal_symbol'];
            if ($decimal_symbol !== null) {
                $inputData = preg_replace('/' . $decimal_symbol . '/', '.', $inputData);
            }

            if ($inputData === '' || $inputData === null) {//if value null
                return $inputData;
            }

            $inputData = number_format(floatval($inputData), $decimalCount, $decimalSymbol, $thousandSymbol);
            if (is_nan(floatval($inputData))) {
                $inputData = '';
            }
        }
        return $inputData;
    }

    /**
     * Calculation for cell, return by cell format
     *
     * @param array      $datas         Cells data array
     * @param string     $valueCell     Cell value
     * @param object     $activeSheet   PhpSpreadsheet
     * @param array      $position      Cell position
     * @param boolean    $getRawData    Get cell data before convert format
     * @param null|array $format        Format of cell
     * @param array      $tblStyleCells Format of cells
     *
     * @return string|array
     */
    public function calculaterCell2($datas, $valueCell, $activeSheet, $position, $getRawData = false, $format = null, $tblStyleCells = array())
    {
        $value_missing = array();
        $value_missing['col'] = null;
        $value_missing['row'] = null;

        $returnCellValue = '';
        $returnCellValueRaw = '';
        $rangers = explode(';', $valueCell[2]);
        $formula = strtoupper($valueCell[1]);
        $values = array();
        $positionCells = array();
        $checkCurrencySymbolExist = false;

        //check formula function to replace input value
        if (in_array($formula, $this->math_formula) || in_array($formula, $this->and_formula)) {//math calculation ans boolean formula
            $formulaGroup = 1;
        } elseif (in_array($formula, $this->date_formula)) {//date calculation
            $formulaGroup = 2;
        } else {
            $formulaGroup = 3;
        }
//        error_log('$position, $valueCell');
//        error_log(json_encode($datas));
//        error_log(json_encode($valueCell));

        foreach ($rangers as $cells) {
            $cell     = explode(':', $cells);
            if (count($cell) === 1) { //single cell
                preg_match_all('@([a-zA-Z]+)([0-9]+)@', $cells, $val0);
                $data = '';
                if ($val0[0] !== array()) {
                    $count = count($val0[0]);
                    for ($i = 0; $i < $count; $i ++) {
                        if (isset($datas[$val0[2][$i] - 1]) && $datas[$val0[2][$i] - 1][self::convertAlpha(strtoupper($val0[1][$i])) - 1] === null) {
                            $datas[$val0[2][$i] - 1][self::convertAlpha(strtoupper($val0[1][$i])) - 1] = '';
                        }
                        if (!isset($datas[$val0[2][$i] - 1]) || !isset($datas[$val0[2][$i] - 1][self::convertAlpha(strtoupper($val0[1][$i])) - 1])) {//check exist cell
                            $value_missing['col'][] = self::convertAlpha(strtoupper($val0[1][$i])) - 1;
                            $value_missing['row'][] = $val0[2][$i] - 1;
                            continue;
                        }

                        $dataCells = $this->getCell($datas, $val0[2][$i] - 1, strtoupper($val0[1][$i]), $formulaGroup, $checkCurrencySymbolExist, $activeSheet, $format, $tblStyleCells);

                        $data = $dataCells[0];
                        $positionCells[] = array($val0[2][$i] - 1, strtoupper($val0[1][$i]));
                        $checkCurrencySymbolExist = $dataCells[1];
                    }
                } else {//no reference to a cell
                    if ($formulaGroup === 1) {
                        $data = $this->convertNumberByFormat($cells, $this->decimal_symbol, $format, 9, $this->thousand_symbol);
                    } else {
                        $data = $cells;
                    }
                }
                $values[] = $data;
            } else { //range
                if ($formulaGroup === 2 && $formula !== 'DAY') {
                    return $returnCellValue;
                }
                preg_match('@([a-zA-Z]+)([0-9]+)@', $cell[0], $val1);
                preg_match('@([a-zA-Z]+)([0-9]+)@', $cell[1], $val2);
                if ($val1 !== array() && $val2 !== array()) {
                    $rowRange = array($val1[2] - 1, $val2[2] - 1);

                    $convertVal1 = self::convertAlpha($val1[1]) - 1;
                    $convertVal2 = self::convertAlpha($val2[1]) - 1;
                    $ColRange = array($convertVal1, $convertVal2);

                    for ($i = $rowRange[0]; $i <= $rowRange[1]; $i ++) {
                        for ($j = $ColRange[0]; $j <= $ColRange[1]; $j ++) {
                            if (isset($datas[$i]) && (!isset($datas[$i][$j]) || $datas[$i][$j] === null)) {
                                $datas[$i][$j] = '';
                            }
                            if (!isset($datas[$i]) || !isset($datas[$i][$j])) {//check exist cell
                                $value_missing['col'][] = $j;
                                $value_missing['row'][] = $i;
                                continue;
                            }

                            $colCell = strtoupper($this->getNameFromNumber($j));
                            $dataCells = $this->getCell($datas, $i, $colCell, $formulaGroup, $checkCurrencySymbolExist, $activeSheet, $format, $tblStyleCells);
                            $checkCurrencySymbolExist = $dataCells[1];
                            $values[] = $dataCells[0];
                            $positionCells[] = array($i, $colCell);
                            $data = $dataCells[0];
                        }
                    }
                } else {
                    if ($formulaGroup === 1) {
                        $values[] = $this->convertNumberByFormat($cells, $this->decimal_symbol, $format, 9, $this->thousand_symbol);
                    } else {
                        $values[] = $cells;
                    }
                }
            }
        }

        if ($value_missing['row'] !== null) {
            $value_missing[0] = $valueCell;
            return $value_missing;
        }

        if (in_array($formula, $this->math_formula)) {
            /*math formula*/
            if ($formula === 'MULTIPLY') {//phpspreadsheet not support
                $returnCellValue = $this->multiplyCalculation($values);
            } elseif ($formula === 'DIVIDE') {//phpspreadsheet not support
                $returnCellValue = $this->divideCalculation($values);
            } else {
                if ($formula === 'AVG') {
                    $activeSheet->setCellValue($position[0] . $position[1], '=AVERAGE(' . $valueCell[2] . ')');
                }
                $returnCellValue = $activeSheet->getCell($position[0] . $position[1])->getCalculatedValue();
            }

            if ($getRawData) {
                $returnCellValueRaw = floatval($returnCellValue);
            }

            if ($returnCellValue !== '') {
                $returnCellValue = number_format(
                    floatval($returnCellValue),
                    self::$decimal_count_cell !== null ? self::$decimal_count_cell : $this->decimal_count,
                    self::$decimal_symbol_cell !== null ? self::$decimal_symbol_cell : $this->decimal_symbol,
                    self::$thousand_symbol_cell !== null ? self::$thousand_symbol_cell : $this->thousand_symbol
                );
            }

            if (self::$currency_symbol_cell !== null) {
                $returnCellValue = ((int) self::$symbol_position_cell === 0) ? self::$currency_symbol_cell . ' ' . $returnCellValue : $returnCellValue . ' ' . self::$currency_symbol_cell;
            } elseif ($checkCurrencySymbolExist) {
                $returnCellValue = ((int) $this->symbol_position === 0) ? $this->currency_symbol . ' ' . $returnCellValue : $returnCellValue . ' ' . $this->currency_symbol;
            }
        } elseif (in_array($formula, $this->and_formula)) {
            /*boolean formula*/
            $returnCellValue = $activeSheet->getCell($position[0] . $position[1])->getCalculatedValue() ? 'true' : 'false';
        } elseif ($formulaGroup === 2) {
            /*dateTime formula*/
            $activeSheet->getStyle($position[0] . $position[1])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD);
            preg_match_all('@([a-zA-Z]+)([0-9]+)@', $valueCell[2], $x1);
            if ($formula === 'day' || $formula === 'DAY') {
                if (!($x1[0] !== array())) {
                    $activeSheet->getCell('Z9999')->setValue($valueCell[2]);
                    $activeSheet->getCell($position[0] . $position[1])->setValue('=DAY(Z9999)');
                }
            }
            if ($formula === 'days' || $formula === 'DAYS' || $formula === 'days360' || $formula === 'DAYS360') {
                if (!($x1[0] !== array())) {
                    $cell     = explode(';', $valueCell[2]);
                    $activeSheet->getCell('Z9999')->setValue($cell[0]);
                    $activeSheet->getCell('Z9998')->setValue($cell[1]);
                    $activeSheet->getCell($position[0] . $position[1])->setValue('=DAYS(Z9999,Z9998)');
                }
            }

            if ($formula === 'DATE') {//phpspreadsheet get getCalculatedValue other dateCalculation
                if (count($values) > 1) {
                    $returnCellValue = join('/', $values);
                } else {
                    $returnCellValue = $values[0];
                }
                return array('date', $returnCellValue);
            } else {
                $returnCellValue = $activeSheet->getCell($position[0] . $position[1])->getCalculatedValue();
                if ($formula === 'days' || $formula === 'DAYS'
                    || $formula === 'day' || $formula === 'DAY'
                    || $formula === 'days360' || $formula === 'DAYS360') {
                    return array($formula, $returnCellValue);
                }
            }
        } elseif ($formulaGroup === 3) {
            /*CONCAT formula*/
            $returnCellValue = $activeSheet->getCell($position[0] . $position[1])->getCalculatedValue();
        }

        if ($getRawData) {
            return array($returnCellValue, $returnCellValueRaw);
        }
        return $returnCellValue;
    }

    /**
     * Get and replace a cell value by xxxx.xxxxxxxxx format
     *
     * @param array      $datas                    Cells value
     * @param string     $row                      Position row cell
     * @param string     $col                      Position col cell
     * @param integer    $formulaGroup             Formula group
     * @param boolean    $checkCurrencySymbolExist Check currency symbol exist
     * @param object     $activeSheet              PhpSpreadsheet
     * @param array|null $format                   Format of cell
     * @param array      $tblStyleCells            Format of cells
     *
     * @return array
     */
    public function getCell($datas, $row, $col, $formulaGroup, $checkCurrencySymbolExist, $activeSheet, $format, $tblStyleCells)
    {
        $cellData    = $datas[$row][self::convertAlpha($col) - 1];
        $format = array(
            'currency_symbol' => $this->currency_symbol,
            'decimal_symbol' => $this->decimal_symbol,
            'thousand_symbol' => $this->thousand_symbol,
        );
//        error_log('$cellData');
//        error_log($cellData);
//        error_log(json_encode(array($row, self::convertAlpha($col) - 1)));
        if ($cellData !== null) {
            if ($formulaGroup === 1) {//convert value to number
                if ($cellData !== '') {
                    preg_match('/=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\((.*?)\)+/', $cellData, $cellData2);
                    if (isset($cellData2[2])) {
                        $cellData = $this->calculationCells($datas, $tblStyleCells, $cellData, $activeSheet, array($row, self::convertAlpha($col) - 1));
                    }
                }

//                error_log(2409);
//                error_log(json_encode($cellData));
                $checkCurrencySymbolExist = strpos($cellData, $this->currency_symbol) > -1 ? true : $checkCurrencySymbolExist;
                $cellData = $this->convertNumberByFormat($cellData, '.', $format, 9, '');
            }
            $activeSheet->getCell($col . ($row + 1))->setValue($cellData);
            //when parent cell not has format, will get format by content cell (only calculator cell)
            if (self::$decimal_symbol_cell === null) {
                if (isset($tblStyleCells[($row + 1) . '!' . (self::convertAlpha($col) - 1)])) {
                    $formatCell = $this->getFormatCell($tblStyleCells[($row + 1) . '!' . (self::convertAlpha($col) - 1)][2]);
                    if ($formatCell['decimal_symbol'] !== null) {
                        self::$currency_symbol_cell = $formatCell['currency_symbol'];
                        self::$thousand_symbol_cell = $formatCell['thousand_symbol'];
                        self::$decimal_count_cell = $formatCell['decimal_count'];
                        self::$decimal_symbol_cell = $formatCell['decimal_symbol'];
                        self::$symbol_position_cell = $formatCell['symbol_position'];
                    }
                }
            }
            $data = $cellData;
        } else {
            $data = null;
        }
        return array($data, $checkCurrencySymbolExist);
    }

    /**
     * Caculate Functions for cells
     *
     * @param array $datas     Data range
     * @param array $valueCell Value cell
     *
     * @return string
     */
    public function calculaterCell($datas, $valueCell)
    {
        $value_missing = array();
        $value_missing['col'] = null;
        $value_missing['row'] = null;
        $string_currency_symbol = '/[' . str_replace(',', '|', $this->currency_symbol) . ']/';
        $string_unit = str_replace(',', '|^', $this->currency_symbol);
        $string_unit = '/[^a-zA-Z|' . $string_unit . ']/';
        $text_replace_unit_function = '/[^ |' . str_replace(',', '|^', $this->currency_symbol) . ']/';

        $htmlCell = '';
        if (preg_match('@^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$@', $valueCell, $matches)) {
            $cells      = explode(';', $matches[2]);
            $values     = array();
            $checkIsDay = (count(explode('DAY', $matches[1])) === 2) ? true : false;
            $value_unit = '';

            foreach ($cells as $cell) {
                $vals     = explode(':', $cell);
                if (count($vals) === 1) { //single cell
                    preg_match_all('@([a-zA-Z]+)([0-9]+)@', $cell, $val0);
                    $data = '';
                    if ($val0[0] !== array()) {
                        $count = count($val0[0]);
                        for ($i = 0; $i < $count; $i ++) {
                            if (!isset($datas[$val0[2][$i] - 1]) || !isset($datas[$val0[2][$i] - 1][self::convertAlpha(strtoupper($val0[1][$i])) - 1])) {//check exist cell
                                $value_missing['col'][] = self::convertAlpha(strtoupper($val0[1][$i])) - 1;
                                $value_missing['row'][] = $val0[2][$i] - 1;
                                continue;
                            }
                            $d    = $datas[$val0[2][$i] - 1][self::convertAlpha(strtoupper($val0[1][$i])) - 1];
                            $unit = $d;
                            if ($d !== null) {
                                if ($i === 0) {
                                    $data = str_replace($val0[0][$i], $d, $cell);
                                } else {
                                    $data = str_replace($val0[0][$i], $d, $data);
                                }
                            }
                        }
                    } else {
                        $data = $cell;
                        $unit = $cell;
                    }
                    if ($value_missing['row'] !== null) {//check exist cell
                        continue;
                    }

                    if (strtoupper($matches[1]) !== 'CONCAT') {
                        $d = preg_replace($string_currency_symbol, '', $data);
                        if ($checkIsDay === false) {
                            if ($this->thousand_symbol === ',') {
                                $d = preg_replace('/,/', '', $d);
                            } elseif ($this->thousand_symbol === '.') {
                                $d = preg_replace('/\./', '', $d);
                            }

                            $d = ($this->decimal_symbol === ',') ? preg_replace('/,/', '.', $d) : $d;
                        }
                    } else {
                        $d = $data;
                    }
                    preg_match_all('/<=|>=|!=|>|<|=/', $d, $math1);
                    $math2 = $math1[0];
                    if (!empty($math2)) {
                        $d = preg_replace('/[ |A-Za-z]+/', '', $d);
                        switch ($math2[0]) {
                            case '<=':
                                $varNumber = explode('<=', $d);
                                $number    = (int) ($varNumber[0]) <= (int) ($varNumber[1]);
                                $number    = ($number === true) ? 'true' : 'false';
                                break;
                            case '>=':
                                $varNumber = explode('>=', $d);
                                $number    = (int) ($varNumber[0]) >= (int) ($varNumber[1]);
                                $number    = ($number === true) ? 'true' : 'false';
                                break;
                            case '=':
                                $varNumber = explode('=', $d);
                                $number    = (int) ($varNumber[0]) === (int) ($varNumber[1]);
                                $number    = ($number === true) ? 'true' : 'false';
                                break;
                            case '!=':
                                $varNumber = explode('!=', $d);
                                $number    = (int) ($varNumber[0]) !== (int) ($varNumber[1]);
                                $number    = ($number === true) ? 'true' : 'false';
                                break;
                            case '<':
                                $varNumber = explode('<', $d);
                                $number    = (int) ($varNumber[0]) < (int) ($varNumber[1]);
                                $number    = ($number === true) ? 'true' : 'false';

                                break;
                            case '>':
                                $varNumber = explode('>', $d);
                                $number    = (int) ($varNumber[0]) > (int) ($varNumber[1]);
                                $number    = ($number === true) ? 'true' : 'false';

                                break;
                            default:
                                $number = $d;
                                break;
                        }
                    } else {
                        $number = $d;
                    }
                    $values[] = $number;

                    if (strtoupper($matches[1]) === 'DIVIDE' || strtoupper($matches[1]) === 'MULTIPLY') {
                        $value_unit = $value_unit !== '' ? $value_unit : (($unit !== null) ? preg_replace($text_replace_unit_function, '', $unit) : '');
                    } else {
                        $value_unit = ($unit !== null) ? preg_replace($text_replace_unit_function, '', $unit) : '';
                    }
                } else { //range
                    preg_match('@([a-zA-Z]+)([0-9]+)@', $vals[0], $val1);
                    preg_match('@([a-zA-Z]+)([0-9]+)@', $vals[1], $val2);
                    if ($val1 !== array() && $val2 !== array()) {
                        if ($checkIsDay === true && $val1[2] > $val2[2]) {
                            $val3 = $val1;
                            $val1 = $val2;
                            $val2 = $val3;
                        }

                        for ($il = $val1[2] - 1; $il <= $val2[2] - 1; $il ++) {
                            $convertVal1 = self::convertAlpha($val1[1]) - 1;
                            $convertVal2 = self::convertAlpha($val2[1]) - 1;
                            for ($ik = $convertVal1; $ik <= $convertVal2; $ik ++) {
                                if (!isset($datas[$il]) || !isset($datas[$il][$ik])) {//check exist cell
                                    $value_missing['col'][] = $ik;
                                    $value_missing['row'][] = $il;
                                    continue;
                                }
                                if (strtoupper($matches[1]) !== 'CONCAT') {
                                    $number = preg_replace($string_currency_symbol, '', $datas[$il][$ik]);
                                    if ($checkIsDay === false) {
                                        $number = ($this->thousand_symbol === ',') ? preg_replace('/,/', '', $number) : (($this->thousand_symbol === '.') ? preg_replace('/\./', '', $number) : $number);
                                        $number = ($this->decimal_symbol === ',') ? preg_replace('/,/', '.', $number) : $number;
                                    }
                                } else {
                                    $number = $datas[$il][$ik];
                                }
                                $values[] = $number;
                                $unit     = $datas[$il][$ik];

                                if (strtoupper($matches[1]) === 'MULTIPLY') {
                                    $value_unit = $value_unit !== '' ? $value_unit : (($unit !== null) ? preg_replace($text_replace_unit_function, '', $unit) : '');
                                } else {
                                    $value_unit = ($unit !== null) ? preg_replace($text_replace_unit_function, '', $unit) : '';
                                }
                            }
                        }
                        if ($value_missing['row'] !== null) {
                            continue;
                        }
                        if (!empty($val3) && $val3 === $val2) {
                            $values_data = $values[0];
                            $values[0]   = $values[1];
                            $values[1]   = $values_data;
                        }
                    } else {
                        $values[] = $cell;
                    }
                }
            }

            if ($value_missing['row'] !== null) {
                return $value_missing;
            }

            preg_match_all('/[A-Z|\\\|a-z]+/', $this->date_formats, $date_formats);

            switch (strtoupper($matches[1])) {
                case 'DATE':
                    $htmlCell = $this->dateCalculation($values, $date_formats, $number);
                    break;
                case 'DAY':
                    $htmlCell = $this->dayCalculation($values, $date_formats);
                    break;
                case 'DAYS':
                    $htmlCell = $this->daysCalculation($values, $date_formats);
                    break;
                case 'DAYS360':
                    $htmlCell = $this->days360Calculation($values, $date_formats);
                    break;
                case 'AND':
                    $htmlCell = $this->andCalculation($values);
                    break;
                case 'OR':
                    $htmlCell = $this->orCalculation($values);
                    break;
                case 'XOR':
                    $htmlCell = $this->xorCalculation($values);
                    break;
                case 'SUM':
                    $htmlCell = $this->sumCalculation($values, $value_unit);
                    break;
                case 'MULTIPLY':
                    $htmlCell = $this->multiplyCalculation($values, $value_unit);
                    break;
                case 'DIVIDE':
                    $htmlCell = $this->divideCalculation($values, $value_unit);
                    break;
                case 'COUNT':
                    $htmlCell = $this->countCalculation($values);
                    break;
                case 'MIN':
                    $htmlCell = $this->minCalculation($values, $value_unit);
                    break;
                case 'MAX':
                    $htmlCell = $this->maxCalculation($values, $value_unit);
                    break;
                case 'AVG':
                    $htmlCell = $this->avgCalculation($values, $value_unit);
                    break;
                case 'CONCAT':
                    $htmlCell = $this->concatCalculation($values);
                    break;
            }
        }
        return $htmlCell;
    }

    /**
     * Date calculater function
     *
     * @param string $values       Cell value
     * @param array  $date_formats Date format
     * @param string $number       Number value
     *
     * @return string
     */
    public function dateCalculation($values, $date_formats, $number)
    {
        $M_name = array('', 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sept', 'oct', 'nov', 'dec');
        $D_name = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');

        $cellHtml = '';
        if (count($values) === 1) {
            preg_match_all('/[a-zA-Z0-9|+|-|\\\]+/', $number, $values);
        }
        $text_date = $this->convertDay($values, $date_formats[0], false);
        if ($text_date !== false) {
            $date_string = date_create($text_date);
            if ($date_string !== false) {
                preg_match_all('/[^A-Z|^\\\|^a-z]+/', $this->date_formats, $format_resultCalc);
                $date_string = getdate($date_string->getTimestamp());
                $date        = array();
                $date['D']   = $D_name[$date_string['wday']];
                $date['l']   = $date_string['weekday'];
                $date['j']   = $date_string['mday'];
                $date['d']   = ((int) $date_string['mday'] < 10) ? '0' . $date_string['mday'] : $date_string['mday'];
                $date['F']   = $date_string['month'];
                $date['M']   = $M_name[$date_string['mon']];
                $date['n']   = $date_string['mon'];
                $date['m']   = ((int) $date_string['mon'] < 10) ? '0' . $date_string['mon'] : $date_string['mon'];
                $date['Y']   = $date_string['year'];
                $date['y']   = (int) $date_string['year'] % 100;
                foreach ($date_formats[0] as $date_format => $key) {
                    if (strpos($key, '\\') !== false
                        || in_array($key, array('a', 'A', 'g', 'G', 'h', 'H', 'i', 's', 'T')) !== false
                    ) {
                        $date[$key] = $values[$date_format];
                    }
                    $cellHtml .= '' . $date[$key] . (!empty($format_resultCalc[0][$date_format]) ? $format_resultCalc[0][$date_format] : '');
                }
            } else {
                return 'NaN';
            }
        } else {
            return 'NaN';
        }
        return $cellHtml;
    }

    /**
     * Day calculater function
     *
     * @param array $values       Value cell
     * @param array $date_formats Date format
     *
     * @return array|string
     */
    public function dayCalculation($values, $date_formats)
    {
        $date_format = $date_formats[0];
        preg_match_all('/[a-zA-Z0-9|+|-|\\\]+/', $values[0], $number);
        $text_date = $this->convertDay($number[0], $date_format, true);
        if ($text_date !== false) {
            $date1            = date_create($text_date);
            $cellHtml = getdate($date1->getTimestamp());
            $cellHtml = $cellHtml['mday'];
        } else {
            $cellHtml = 'NaN';
        }

        return $cellHtml;
    }

    /**
     * Days calculater function
     *
     * @param array $values       Value cell
     * @param array $date_formats Date format
     *
     * @return float|integer|string
     */
    public function daysCalculation($values, $date_formats)
    {
        try {
            $class = $this;
            $date_format = $date_formats[0];
            $resultCalc = array();
            $resultCalc = array_map(function ($foo) use ($class, $date_format, $resultCalc) {
                preg_match_all('/[a-zA-Z0-9|+|-|\\\]+/', $foo, $number);
                $text_date = $class->convertDay($number[0], $date_format, true);
                $date = new DateTime($text_date);
                $resultCalc[] = $date->getTimestamp();
                return $resultCalc;
            }, $values);
            if (isset($resultCalc[0][0]) && isset($resultCalc[1][0])) {
                return ($resultCalc[0][0] - $resultCalc[1][0]) / (24 * 3600);
            }
            return 'NaN';
        } catch (Exception $exc) {
            return 'NaN';
        }
    }

    /**
     * Days360 calculater function
     *
     * @param array $values       Value cell
     * @param array $date_formats Date format
     *
     * @return float|integer|string
     */
    public function days360Calculation($values, $date_formats)
    {
        self::$resultCalc = 1;
        $countValue       = count($values);
        $result = array();
        for ($i = 0; $i < $countValue; $i ++) {
            preg_match_all('/[a-zA-Z0-9|+|-|\\\]+/', $values[$i], $number);
            $text_date = $this->convertDay($number[0], $date_formats[0], true);
            if ($text_date !== false) {
                $result[$i] = getdate(date_create($text_date)->getTimestamp());
            } else {
                self::$resultCalc = 'NaN';
                break;
            }
        }
        if (self::$resultCalc !== 'NaN') {
            if ($result[0]['year'] > $result[1]['year']) {
                self::$resultCalc = - 1;
            }
            $result[0]['mday'] = ($result[0]['mday'] === 31) ? 30 : $result[0]['mday'];
            $result[1]['mday'] = ($result[1]['mday'] === 31) ? 30 : $result[1]['mday'];
            self::$resultCalc  = (($result[1]['year'] - $result[0]['year'] - 1) * 360) + ((13 - $result[0]['mon']) * 30 - $result[0]['mday']) + (($result[1]['mon'] - 1) * 30 + $result[1]['mday']);
        }
        return (int) self::$resultCalc;
    }

    /**
     * And calculater
     *
     * @param array $values Cell value
     *
     * @return string
     */
    public function andCalculation($values)
    {
        $resultCalc = new stdClass();
        $resultCalc->value = true;
        array_map(function ($foo) use ($resultCalc) {
            $resultCalc->value = $resultCalc->value && (($foo === 'true') ? true : false);
        }, $values);
        return ($resultCalc->value === true) ? 'true' : 'false';
    }

    /**
     * Or calculater
     *
     * @param array $values Cell value
     *
     * @return string
     */
    public function orCalculation($values)
    {
        $resultCalc = new stdClass();
        $resultCalc->value = 0;
        array_map(function ($foo) use ($resultCalc) {
            $resultCalc->value += ($foo === 'true') ? 1 : 0;
        }, $values);
        return ($resultCalc->value > 0) ? 'true' : 'false';
    }

    /**
     * Xor calculater
     *
     * @param array $values Cell value
     *
     * @return string
     */
    public function xorCalculation($values)
    {
        $resultCalc = new stdClass();
        $resultCalc->value = 2;
        array_map(function ($foo) use ($resultCalc) {
            $resultCalc->value += ($foo === 'true') ? 1 : 0;
        }, $values);
        return (($resultCalc->value % 2) === 1) ? 'true' : 'false';
    }

    /**
     * Sum calculater
     *
     * @param array  $values     Cell value
     * @param string $value_unit Value unit
     *
     * @return string
     */
    public function sumCalculation($values, $value_unit)
    {
        $resultCalc = new stdClass();
        $resultCalc->resultCalc = 0;
        array_map(function ($foo) use ($resultCalc) {
            $foo = preg_replace('/[ ]+/', '', $foo);
            if (is_numeric($foo)) {
                $resultCalc->resultCalc = $resultCalc->resultCalc + $foo;
            }
        }, $values);

        return $this->formatSymbols($resultCalc->resultCalc, $value_unit !== '' ? $this->currency_symbol : '');
    }

    /**
     * Multiply calculater
     *
     * @param array  $values     Cell value
     * @param string $value_unit Value unit
     *
     * @return string
     */
    public function multiplyCalculation($values, $value_unit = '')
    {
        $resultCalc = new stdClass();
        $resultCalc->resultCalc = 1;
        array_map(function ($foo) use ($resultCalc) {
            $foo = preg_replace('/[ ]+/', '', $foo);
            if (is_numeric($foo)) {
                $resultCalc->resultCalc = $resultCalc->resultCalc * $foo;
            }
        }, $values);
//        return $this->formatSymbols($resultCalc->resultCalc, $value_unit !== '' ? $this->currency_symbol : '');
        return $resultCalc->resultCalc;
    }

    /**
     * Divide calculater
     *
     * @param array  $values     Cell value
     * @param string $value_unit Value unit
     *
     * @return string
     */
    public function divideCalculation($values, $value_unit = '')
    {
        $resultCalc = new stdClass();
        $resultCalc->resultCalc = 0;

        $count = count($values);
        if ($count !== 2) {
            return 'NaN';
        }
        $values[1] = preg_replace('/[ ]+/', '', $values[1]);

        if (!is_numeric($values[1]) || (int)$values[1] === 0 || $values[1] === '') {
            return 'NaN';
        }

        $values[0] = preg_replace('/[ |A-Za-z]+/', '', $values[0]);

        if (!is_numeric($values[0])) {
            return 'NaN';
        }

        $resultCalc->resultCalc = $values[0] / $values[1];
//        return $this->formatSymbols($resultCalc->resultCalc, $value_unit !== '' ? $this->currency_symbol : '');
        return $resultCalc->resultCalc;
    }

    /**
     * Count calculater
     *
     * @param array $values Cell value
     *
     * @return string
     */
    public function countCalculation($values)
    {
        $resultCalc = new stdClass();
        $resultCalc->value = 0;
        array_map(function ($foo) use ($resultCalc) {
            $foo = preg_replace('/[ |A-Za-z]+/', '', $foo);
            if (is_numeric($foo)) {
                $resultCalc->value ++;
            }
        }, $values);

        return $resultCalc->value;
    }

    /**
     * Min calculater
     *
     * @param array  $values     Cell value
     * @param string $value_unit Value unit
     *
     * @return string
     */
    public function minCalculation($values, $value_unit)
    {
        $resultCalc = new stdClass();
        $resultCalc->value = null;
        array_map(function ($foo) use ($resultCalc) {
            $foo = preg_replace('/[ |A-Za-z]+/', '', $foo);
            if (is_numeric($foo)) {
                if ($resultCalc->value === null || $resultCalc->value > $foo) {
                    $resultCalc->value = $foo;
                }
            }
        }, $values);

        return $this->formatSymbols($resultCalc->value, $value_unit !== '' ? $this->currency_symbol : '');
    }


    /**
     * Max calculater
     *
     * @param array  $values     Cell value
     * @param string $value_unit Value unit
     *
     * @return string
     */
    public function maxCalculation($values, $value_unit)
    {
        $resultCalc = new stdClass();
        $resultCalc->value = null;
        array_map(function ($foo) use ($resultCalc) {
            $foo = preg_replace('/[ |A-Za-z]+/', '', $foo);
            if (is_numeric($foo)) {
                if ($resultCalc->value === null || $resultCalc->value < $foo) {
                    $resultCalc->value = $foo;
                }
            }
        }, $values);

        return $this->formatSymbols($resultCalc->value, $value_unit !== '' ? $this->currency_symbol : '');
    }

    /**
     * Avg calculater
     *
     * @param array  $values     Cell value
     * @param string $value_unit Value unit
     *
     * @return string
     */
    public function avgCalculation($values, $value_unit)
    {
        $resultCalc = new stdClass();
        $resultCalc->resultCalc = 0;
        $resultCalc->n = 0;
        array_map(function ($foo) use ($resultCalc) {
            $foo = preg_replace('/[ ]+/', '', $foo);
            if (is_numeric($foo)) {
                $resultCalc->resultCalc += $foo;
                $resultCalc->n ++;
            }
        }, $values);
        if ($resultCalc->n > 0) {
            $resultCalc->resultCalc = $resultCalc->resultCalc / $resultCalc->n;
        }

        return $this->formatSymbols($resultCalc->resultCalc, $value_unit !== '' ? $this->currency_symbol : '');
    }

    /**
     * Concat calculater
     *
     * @param array $values Cell value
     *
     * @return string
     */
    public function concatCalculation($values)
    {
        $resultCalc = new stdClass();
        $resultCalc->resultCalc = '';

        array_map(function ($foo) use ($resultCalc) {
            if (isset($foo[0]) && (string) $foo[0] !== '=') {
                $resultCalc->resultCalc .= (string) $foo;
            }
        }, $values);

        return $resultCalc->resultCalc;
    }

    /**
     * Check valid date with date format
     *
     * @param string $date   Date
     * @param string $format PHP date format
     *
     * @return boolean
     */
    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    /**
     * Convert php date format to tableSorter date format
     *
     * @param string $dateFormat PHP date format
     *
     * @return string
     */
    public function convertDateFormat($dateFormat)
    {
        $dateFormat = strtolower($dateFormat);
        $d1 = strpos($dateFormat, 'd');
        $m1 = strpos($dateFormat, 'm');
        $y1 = strpos($dateFormat, 'y');

        $tempArr = array();
        if ($d1 !== false) {
            $tempArr[$d1] = 'dd';
        }
        if ($m1 !== false) {
            $tempArr[$m1] = 'mm';
        }
        if ($y1 !== false) {
            $tempArr[$y1] = 'yyyy';
        }

        ksort($tempArr);
        return implode('', array_values($tempArr));
    }

    /**
     * Convert string to m/d/Y
     *
     * @param array   $number      Var of date
     * @param array   $date_format Date format
     * @param boolean $timezone    Check used timezone
     *
     * @return boolean|string
     */
    public function convertDay($number, $date_format, $timezone)
    {
        $F_name     = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
        $M_name     = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sept', 'oct', 'nov', 'dec');
        $date_array = array();
        $date       = array();
        $number     = (!!$number) ? $number : array();
        if (count($date_format) !== count($number)) {
            return false;
        }

        $countFormatDate = count($date_format);
        for ($n = 0; $n < $countFormatDate; $n ++) {
            $number[$n] = (!!$number[$n]) ? $number[$n] : '';
            if ($date_format[$n] === 'd' || $date_format[$n] === 'j') {
                $date_array[2] = ($number[$n] !== '') ? $number[$n] : 0;
            } elseif ($date_format[$n] === 'S' || $date_format[$n] === 'jS' || $date_format[$n] === 'dS') {
                preg_match_all('/[0-9]+/', $number[$n], $date[2]);
                $date_array[2] = $date[2][0][0];
            } elseif ($date_format[$n] === 'm' || $date_format[$n] === 'n') {
                $date_array[1] = $number[$n];
            } elseif ($date_format[$n] === 'F') {
                $date_array[1] = array_search(strtolower($number[$n]), $F_name) + 1;
            } elseif ($date_format[$n] === 'M') {
                $date_array[1] = array_search(strtolower($number[$n]), $M_name) + 1;
            } elseif ($date_format[$n] === 'Y' || $date_format[$n] === 'y') {
                $date_array[3] = $number[$n];
            } elseif (strtolower($date_format[$n]) === 'g' || strtolower($date_format[$n]) === 'h') {
                $date_array[4] = $number[$n];
            } elseif (strtolower($date_format[$n]) === 'ga' || strtolower($date_format[$n]) === 'ha') {
                preg_match_all('/[0-9]+/', $number[$n], $date[4]);
                $number[$n]    = preg_replace('/[0-9]+/', '', $number[$n]);
                $date_array[4] = (strtolower($number[$n]) === 'am') ? (int) $date[4][0][0] : (int) $date[4][0][0] + 12;
            } elseif (strtolower($date_format[$n]) === 'a') {
                $date_array[7] = $number[$n];
            } elseif (strtolower($date_format[$n]) === 'i' || strtolower($date_format[$n]) === 'ia') {
                preg_match_all('/[0-9]+/', $number[$n], $date[5]);
                $date_array[5] = $date[5][0][0];
            } elseif (strtolower($date_format[$n]) === 's' || strtolower($date_format[$n]) === 'sa') {
                preg_match_all('/[0-9]+/', $number[$n], $date[6]);
                $date_array[6] = $date[6][0][0];
            } elseif ($date_format[$n] === 'T') {
                $date_array[8] = $number[$n];
            } elseif ($date_format[$n] === 'r') {
                if (array_search(strtolower($number[$n]), $F_name) + 1 > 0) {
                    $date_array[1] = array_search(strtolower($number[$n]), $F_name) + 1;
                } else {
                    $date_array[1] = array_search(strtolower($number[$n]), $M_name) + 1;
                }
                return $date_array[1] . '/' . $number[1] . '/' . $number[3] . ' ' . $number[4] . ':' . $number[5] . ':' . $number[6] . ' ' . $number[7];
            }
        }
        if ($date_array[3] === '' || $date_array[2] === '' || $date_array[2] > 31 || $date_array[1] > 12) {
            return false;
        }

        $date_array[4] = (!empty($date_array[4])) ? (int) $date_array[4] : '00';
        $date_array[5] = (!empty($date_array[5])) ? (int) $date_array[5] : '00';
        $date_array[6] = (!empty($date_array[6])) ? (int) $date_array[6] : '00';
        $date_array[7] = (!empty($date_array[7])) ? $date_array[7] : '';
        $date_array[8] = (!empty($date_array[8])) ? $date_array[8] : '';
        $date_array[8] = ($timezone === true) ? $date_array[8] : '';
        if (strtolower($date_array[7]) === 'pm') {
            $date_array[4] = $date_array[4] + 12;
        }
        return (int) $date_array[1] . '/' . (int) $date_array[2] . '/' . $date_array[3] . ' ' . $date_array[4] . ':' . $date_array[5] . ':' . $date_array[6] . $date_array[8];
    }

    /**
     * Convert var calculator by format
     *
     * @param integer|null $resultCalc Var calculator
     * @param string       $value_unit Value unit
     *
     * @return string
     */
    public function formatSymbols($resultCalc, $value_unit)
    {
        if ($resultCalc === null) {
            return 'NaN';
        }
        $decimal_count    = $this->decimal_count;
        $array_resultCalc = str_split((string) round($resultCalc, $decimal_count));
        $decimal          = array_search('.', $array_resultCalc);
        $decimal          = ($decimal !== false) ? $decimal : count($array_resultCalc);
        if ($decimal === count($array_resultCalc)) {
            $array_resultCalc[count($array_resultCalc)] = $this->decimal_symbol;
        }

        $data = '';
        $j    = ($decimal > 3) ? $decimal % 3 : - 1;
        if ($array_resultCalc[0] === '-') {
            $j                   = ($decimal - 1 > 3) ? ($decimal - 1) % 3 + 1 : - 1;
            $array_resultCalc[0] = (int) $this->symbol_position === 0 ? $array_resultCalc[0] . $value_unit : $array_resultCalc[0];
        } else {
            $array_resultCalc[0] = (int) $this->symbol_position === 0 ? $value_unit . $array_resultCalc[0] : $array_resultCalc[0];
        }
        $decimal1 = $decimal;
        for ($i = 0; $i < $decimal1 + 1 + $decimal_count; $i ++) {
            if ($i + 1 === $j && $array_resultCalc[$i] !== '-') {
                $data .= $array_resultCalc[$i] ? $array_resultCalc[$i] . $this->thousand_symbol : $array_resultCalc[$i];
            } elseif ($j !== - 1 && $i + 1 - $j !== 0 && ($i + 1 - $j) % 3 === 0 && $i < $decimal - 1) {
                $data .= $array_resultCalc[$i] . $this->thousand_symbol;
            } elseif ($i === $decimal && $decimal_count !== 0) {
                $data .= $this->decimal_symbol;
            } elseif (empty($array_resultCalc[$i])) {
                $data .= '0';
            } elseif ($array_resultCalc[$i] !== $this->decimal_symbol) {
                $data .= $array_resultCalc[$i];
            }
        }
        return ((int) $this->symbol_position === 0) ? $data : $data . ' ' . $value_unit;
    }

    /**
     * Check merge cell
     *
     * @param integer $rowNb         Row
     * @param integer $colNb         Col NB
     * @param array   $mergeSettings Setting
     * @param boolean $removeSpan    Remove tag span
     *
     * @return array|boolean
     */
    private static function checkMergeInfo($rowNb, $colNb, $mergeSettings, $removeSpan = false)
    {
        $result = array('', $mergeSettings);
        $count = count($mergeSettings);
        if (!is_array($mergeSettings) || $count === 0) {
            return $result;
        }

        foreach ($mergeSettings as $key => $ms) {
            if (is_object($ms)) {
                $merge = (array)$ms;
                $mergeSettings[$key] = $merge;
            } else {
                $merge = $ms;
            }

            if ((int) $merge['row'] === (int) $rowNb && (int) $merge['col'] === (int) $colNb) {
                $rowSpan = intval($merge['rowspan']) > 1 ? ' rowspan="' . $merge['rowspan'] . '"' : '';
//                $rowSpan = intval($merge['rowspan']) > 1 ? ' ' : '';
                $colSpan = intval($merge['colspan']) > 1 ? ' colspan="' . $merge['colspan'] . '"' : '';
//                $colSpan = intval($merge['colspan']) > 1 ? '' : '';
                $result[0] = $colSpan . $rowSpan;
            } elseif ($merge['row'] <= $rowNb && $rowNb < $merge['row'] + $merge['rowspan'] && $merge['col'] <= $colNb && $colNb < $merge['col'] + $merge['colspan']) {
                if ($removeSpan === true) {
                    $result[0] = false;
                    return $result;
                } else {
                    $result[0] = ' style="display: none"';
                }
            }
        }

        return $result;
    }

    /**
     * Get val cell
     *
     * @param string $col Position of cell
     *
     * @return integer
     */
    private static function convertAlpha($col)
    {
        $col = str_pad($col, 2, '0', STR_PAD_LEFT);
        $i   = ((string) $col[0] === '0') ? 0 : (ord($col[0]) - 64) * 26;
        $i   += ord($col[1]) - 64;

        return $i;
    }
}
