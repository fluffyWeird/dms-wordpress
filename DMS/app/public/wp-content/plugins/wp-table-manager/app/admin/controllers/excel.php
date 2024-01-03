<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\Controller;
use Joomunited\WPFramework\v1_0_6\Utilities;
use Joomunited\WPFramework\v1_0_6\Application;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;

defined('ABSPATH') || die();

require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
    . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

/**
 * Class WptmControllerExcel
 */
class WptmControllerExcel extends Controller
{
    /**
     * Error message
     *
     * @var string $error_message error_message
     */
    private $error_message = '';

    /**
     * Error message when read file .xlsx
     *
     * @var array $error_message_read_file error_message
     */
    private $error_message_read_file = array();

    /**
     * Allowed ext
     *
     * @var array $allowed_ext allowed_ext
     */
    private $allowed_ext = array('xls', 'xlsx', 'csv');

    /**
     * Max rows columns
     *
     * @var array $maxCell allowed_ext
     */
    private $maxCell = array();

    /**
     * Fetch file from heartbeat
     *
     * @var boolean $heartbeat Fetch file from heartbeat
     */
    private $heartbeat = false;

    /**
     * Fetch style from excel
     *
     * @var boolean $fetchStyle Fetch style from excel
     */
    private $fetchStyle = false;

    /**
     * Select sheet to sync
     *
     * @var boolean|integer $getSheetFromAdmin Fetch style from excel
     */
    private $getSheetFromAdmin = false;

    /**
     * Function import excell
     *
     * @return void
     */
    public function import()
    {
        $json = array();
        $file = Utilities::getInput('file', 'POST', 'string');
        $upload_dir = wp_upload_dir();
        $targetPath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;

        if ($file) {
            $file = $targetPath . $file;
        } else {
            $file = $this->uploadFileExcel();
        }

        if ($file) {
            $id_table = Utilities::getInt('id_table', 'POST');
            $onlydata = Utilities::getInt('onlydata', 'POST');
            $onlydata = $onlydata === 0 ? false : true;

            $getListSheet = Utilities::getInt('getListSheet', 'POST');
            if ($getListSheet) {
                $this->getSheetFromAdmin = (int)$getListSheet;
            }

            $ignoreCheck = Utilities::getInt('ignoreCheck', 'POST');

            $modelTable = $this->getModel('table');
            $tableContent = (array)$modelTable->getItem($id_table, false, true);

            if ($onlydata) {//render new style
                $tableContent['style'] = new stdClass();
                $tableContent['style']->rows = new stdClass();
                $tableContent['style']->cols = new stdClass();
            } else {
                if (!isset($tableContent['style'])) {
                    $tableContent['style'] = new stdClass();
                }

                if (!isset($tableContent['style']->rows)) {
                    $tableContent['style']->rows = new stdClass();
                }
                if (!isset($tableContent['style']->cols)) {
                    $tableContent['style']->cols = new stdClass();
                }
            }

            $doUpdate = false;

            if (!isset($tableContent['params'])) {
                $tableContent['params'] = new stdClass();
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if ($ext === 'csv') {
                $readFileExcel = $this->readCsv($file);
                $onlydata = false;
            } else {
                $readFileExcel = $this->readFileExcel($file, true, false, $tableContent['params'], $id_table, $onlydata);
            }

            if (isset($readFileExcel['status']) && $readFileExcel['status']) {
                $doUpdate = true;
            } elseif (isset($readFileExcel['text'])) {
                $this->exitStatus(false, esc_attr($readFileExcel['text']));
            } elseif (is_string($readFileExcel)) {
                $this->exitStatus(false, esc_attr($readFileExcel));
            } else {
                $this->exitStatus(false, $this->error_message !== '' ? $this->error_message : __('There was an error importing the file', 'wptm'));
            }

            if ($onlydata) {//render new style
                $modelTable->deleteOldStyle($id_table);
            }
            $tableContent['datas'] = $readFileExcel['data']['data'];
            $tableContent['params']->mergeSetting = $readFileExcel['data']['mergeSetting'];

            $tableContent['numberRow'] = count($tableContent['datas']);
            //fix error csv missing column
            $tableContent['numberCol'] = !empty($readFileExcel['data']['max_cols']) ? $readFileExcel['data']['max_cols'] : count($tableContent['datas'][0]);

            if ($onlydata) {
                $tableContent['params']->cell_types = $readFileExcel['data']['typeCells'];
                $tableContent['style']->rows = $readFileExcel['data']['style']['rows'];
                $tableContent['style']->cols = $readFileExcel['data']['style']['cols'];
                $tableContent['style'] = json_encode($tableContent['style']);

                $tableContent['styleCells'] = $readFileExcel['data']['style']['cell'];
            } else {
                $newStyleRowsCols = $this->updateStyleColRowWhenImport(
                    $tableContent['style']->rows,
                    $tableContent['style']->cols,
                    $tableContent['numberRow'],
                    $tableContent['numberCol']
                );

                $tableContent['style']->rows = $newStyleRowsCols[0];
                $tableContent['style']->cols = $newStyleRowsCols[1];
            }

            $count = count($readFileExcel['data']['hyperlink']);
            if ($readFileExcel['data']['hyperlink'] !== false) {
                if ($count > 0 && isset($tableContent['params']->cell_types) && $tableContent['params']->cell_types !== false && is_array($tableContent['params']->cell_types)) {
                    $tableContent['params']->cell_types = $this->changeHyperlinksTable($tableContent['params']->cell_types, $readFileExcel['data']['hyperlink']);
                }
            }

            $tableContent['params']->hyperlink = $readFileExcel['data']['hyperlink'];

            $tableContent['action'] = 'insert';

            if (file_exists($file)) {
                unlink($file);
            }

            if ($doUpdate) {
                //add table header = 1
                $header = 1;
                $tableContent['params']->headerOption = $header;
                $tableContent['params']->header_data = array();
                for ($i = 0; $i < $header; $i++) {
                    $tableContent['params']->header_data[] = $tableContent['datas'][$i];
                }

                //update header option by merger cells
                $updateMergeCells = $modelTable->updateMergeCells($id_table, false, $tableContent['params'], array_slice($tableContent['datas'], 0, 9));

                if (is_array($updateMergeCells)) {
                    $tableContent['params'] = $updateMergeCells;
                }

                if ($modelTable->saveTableSynfile($id_table, $tableContent)) {
                    $updated = true;
                    /**
                     * Action hook after update table when table import
                     *
                     * @param integer $id_table Table id
                     */
                    do_action('wptm_after_update_table_when_import', $id_table);
                } else {
                    $error = array(
                        'error' => $this->error_message,
                        'text' => __('error while saving table', 'wptm')
                    );
                    $this->exitStatus($error);
                }
            }

            if (count($this->error_message_read_file) > 0) {
                $this->exitStatus($updated, $this->error_message_read_file[0]);
            }
            $this->exitStatus($updated);
        } else {
            $this->exitStatus($this->error_message);
        }
    }

    /**
     * Read data from file csv
     *
     * @param array $file File dir
     *
     * @return mixed
     */
    public function readCsv($file)
    {
        if ($file !== '') {
            $handle = fopen($file, 'r');

            if ($handle !== false) {
                $datas = array();
                $i = 0;
                $max_cols = 0;
                $pattern = '@^=(HYPERLINK)\\((.*?)\\)$@';
                $pattern2 = '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#';
                $styletable = array();

                while (($data = fgetcsv($handle, null, ',')) !== false) {
                    $max_col = count($data);
                    if ($max_cols < $max_col) {
                        $max_cols = $max_col;
                    }

                    for ($ii = 0; $ii < $max_col; $ii ++) {
                        $data[$ii] = $data[$ii] ? $data[$ii] : '';

                        if ($data[$ii] !== '' && preg_match($pattern, $data[$ii], $matches)) {
                            $cells = explode(',', $matches[2]);
//                            preg_match_all($pattern2, $cells[0], $val0);
                            preg_match_all(
                                "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
                                $cells[0],
                                $val
                            );
                            if (!empty($val[0])) {
                                $styletable[($i) . '!' . ($ii)] = array();
                                $styletable[($i) . '!' . ($ii)]['hyperlink'] = $val[0][0];
                                $styletable[($i) . '!' . ($ii)]['text'] = preg_replace('/"/', '', $cells[1]);
                            }
                        }
                    }

                    $i++;
                    $datas[] = $data;
                }
                fclose($handle);

                if (empty($datas)) {
                    return array(
                        'text' => __('File empty!', 'wptm'),
                        'file' => basename($file),
                        'status' => false
                    );
                }

                $tableContent = array(
                    'data' => $datas,
                    'style' => null,
                    'mergeSetting' => null,
                    'typeCells' => null,
                    'max_cols' => $max_cols,
                    'hyperlink' => $styletable
                );

                return array('data' => $tableContent, 'status' => true);
            }
        }
        return array(
            'text' => __('Can\'t read file!', 'wptm'),
            'file' => basename($file),
            'status' => false
        );
    }

    /**
     * Function change type cell value to string and replace ',' to ';' in calculate cell
     *
     * @param array $datas      Data cell
     * @param array $valueDatas Data cell value
     * @param array $maxCell    Max number row and col
     *
     * @return mixed
     */
    public function changeValueCalculateCell($datas, $valueDatas, $maxCell)
    {
        for ($i = 0; $i < $maxCell['row']; $i++) {
            $datas[$i] = array_map(
                function ($data, $valueData) {
                    $data = (string)$data;
                    $key = substr($data, 0, 1);
                    $pattern = '@^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|count|min|max|avg|concat)\\((.*?)\\)$@';
                    if ($key === '=' && preg_match($pattern, $data, $matches)) {
                        $data = str_replace(',', ';', $data);
                    } else {
                        $data = $valueData;
                    }

                    return (string)$data;
                },
                $datas[$i],
                $valueDatas[$i]
            );
        }

        return $datas;
    }

    /**
     * Function replace ';' to ',' of cell value in calculate cell
     *
     * @param array $datas   Data cell
     * @param array $maxCell Max number row and col
     *
     * @return mixed
     */
    public function renderValueCalculateCell($datas, $maxCell)
    {
        for ($i = 0; $i < $maxCell['row']; $i++) {
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
     * Function creator array list data merge cell
     *
     * @param array $mergeRanges Value return $sheetActive->getMergeCells
     *
     * @return array
     */
    public function getMergeCell($mergeRanges)
    {
        $mergeSettings = array();

        foreach ($mergeRanges as $mergeRange) {
            list($tlCell, $rbCell) = explode(':', $mergeRange);

            list($tl_cNb, $tl_rNb) = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($tlCell);
            list($br_cNb, $br_rNb) = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($rbCell);
            $tl_cNb = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($tl_cNb);
            $br_cNb = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($br_cNb);

            $mergeSetting = new stdClass();
            $mergeSetting->row = $tl_rNb - 1;
            $mergeSetting->col = $tl_cNb - 1;
            $mergeSetting->rowspan = $br_rNb - $tl_rNb + 1;
            $mergeSetting->colspan = $br_cNb - $tl_cNb + 1;
            $mergeSettings[] = $mergeSetting;
        }

        return $mergeSettings;
    }

    /**
     * Convert utf8
     *
     * @param array $array Array
     *
     * @return mixed
     */
    public function utf8Converter($array)
    {
        ini_set('mbstring.substitute_character', 'none');
        array_walk_recursive($array, function (&$item, $key) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            }
        });

        return $array;
    }

    /**
     * Create CSS style
     *
     * @param PhpOffice\PhpSpreadsheet\Style\Style $pStyle PhpOffice\PhpSpreadsheet\Style\Style
     *
     * @return string
     */
    private function createCSSStyleExel(PhpOffice\PhpSpreadsheet\Style\Style $pStyle)
    {
        // Construct CSS
        $css = '{';
        $css .= $this->createCSSStyleAlignmentExcel($pStyle->getAlignment());
        $css .= $this->createCSSStyleBordersExcel($pStyle->getBorders());
        $css .= $this->createCSSStyleFontExcel($pStyle->getFont());
        $css .= $this->createCSSStyleFillExcel($pStyle->getFill());
        $css .= '}';

        // Return
        return $css;
    }

    /**
     * Create CSS style (PhpSpreadsheet style alignment)
     *
     * @param PhpOffice\PhpSpreadsheet\Style\Alignment $getAlignment PhpOffice\PhpSpreadsheet\Style\Alignment
     *
     * @return string
     */
    private function createCSSStyleAlignmentExcel(PhpOffice\PhpSpreadsheet\Style\Alignment $getAlignment)
    {
        // Construct CSS
        $css = '';
        $css = '"cell_vertical_align":"' . $this->mapVAlignExcel($getAlignment->getVertical()) . '",';

        // Create CSS
        $textAlign = $this->mapHAlignExcel($getAlignment->getHorizontal());
        if ($textAlign) {
            $css .= '"cell_text_align":"' . $textAlign . '",';
            if (in_array($textAlign, array('left', 'right'))) {
                $css .= '"cell_padding_' . $textAlign . '":"' . (string)((int)$getAlignment->getIndent() * 9) . 'px",';
            }
        }

        // Return
        return $css;
    }

    /**
     * Create CSS style (PhpSpreadsheet style font)
     *
     * @param PhpOffice\PhpSpreadsheet\Style\Font $pStyle PhpOffice\PhpSpreadsheet\Style\Font
     *
     * @return string
     */
    private function createCSSStyleFontExcel(PhpOffice\PhpSpreadsheet\Style\Font $pStyle)
    {
        // Construct CSS
        $css = '';

        // Create CSS
        if ($pStyle->getBold()) {
            $css .= '"cell_font_bold":true,';
        }
        if ((string)$pStyle->getUnderline() !== PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE && $pStyle->getStrikethrough()) {
            $css .= '"cell_font_underline":true,';
        } elseif ((string)$pStyle->getUnderline() !== PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE) {
            $css .= '"cell_font_underline":true,';
        } elseif ($pStyle->getStrikethrough()) {
            $css .= '"cell_font_underline":false,';
        }
        if ($pStyle->getItalic()) {
            $css .= '"cell_font_italic":true,';
        }

        $css .= '"cell_font_color":"#' . $pStyle->getColor()->getRGB() . '",';
        $css .= '"cell_font_family":"' . $pStyle->getName() . '",';
        $css .= '"cell_font_size":"' . floor($pStyle->getSize() * 96 / 72) . '",';

        // Return
        return $css;
    }

    /**
     * Create CSS style (PhpSpreadsheet style borders)
     *
     * @param PhpOffice\PhpSpreadsheet\Style\Borders $pStyle PhpOffice\PhpSpreadsheet\Style\Borders
     *
     * @return string
     */
    private function createCSSStyleBordersExcel(PhpOffice\PhpSpreadsheet\Style\Borders $pStyle)
    {
        // Construct CSS
        $css = '';

        // Create CSS
        $css .= '"cell_border_bottom":"' . $this->createCSSStyleBorderExcel($pStyle->getBottom()) . '",';
        $css .= '"cell_border_top":"' . $this->createCSSStyleBorderExcel($pStyle->getTop()) . '",';
        $css .= '"cell_border_left":"' . $this->createCSSStyleBorderExcel($pStyle->getLeft()) . '",';
        $css .= '"cell_border_right":"' . $this->createCSSStyleBorderExcel($pStyle->getRight()) . '",';

        // Return
        return $css;
    }

    /**
     * Create CSS style (PhpSpreadsheet style border)
     *
     * @param PhpOffice\PhpSpreadsheet\Style\Border $pStyle PhpOffice\PhpSpreadsheet\Style\Border
     *
     * @return string
     */
    private function createCSSStyleBorderExcel(PhpOffice\PhpSpreadsheet\Style\Border $pStyle)
    {
        // Create CSS
        $borderStyle = $this->mapBorderStyleExcel($pStyle->getBorderStyle());
        $css = $borderStyle . ' #' . $pStyle->getColor()->getRGB() . (($borderStyle === 'none') ? '' : ' !important');

        // Return
        return $css;
    }

    /**
     * Create CSS style (PhpSpreadsheet style fill)
     *
     * @param PhpOffice\PhpSpreadsheet\Style\Fill $pStyle PhpOffice\PhpSpreadsheet\Style\Fill
     *
     * @return string
     */
    private function createCSSStyleFillExcel(PhpOffice\PhpSpreadsheet\Style\Fill $pStyle)
    {
        // Construct HTML
        $css = '';

        // Create CSS
        $value = $pStyle->getFillType() === PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE ?
            '' : '#' . $pStyle->getStartColor()->getRGB();
        $css .= '"cell_background_color":"' . $value . '"';

        // Return
        return $css;
    }

    /**
     * Map VAlign
     *
     * @param string $vAlign Vertical alignment
     *
     * @return string
     */
    private function mapVAlignExcel($vAlign)
    {
        switch ($vAlign) {
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM:
                return 'bottom';
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP:
                return 'top';
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER:
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_JUSTIFY:
                return 'middle';
            default:
                return 'baseline';
        }
    }

    /**
     * Map HAlign
     *
     * @param string $hAlign Horizontal alignment
     *
     * @return string|false
     */
    private function mapHAlignExcel($hAlign)
    {
        switch ($hAlign) {
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_GENERAL:
                return false;
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT:
                return 'left';
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT:
                return 'right';
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER:
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER_CONTINUOUS:
                return 'center';
            case \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_JUSTIFY:
                return 'justify';
            default:
                return false;
        }
    }

    /**
     * Map border style
     *
     * @param integer $borderStyle Sheet index
     *
     * @return string
     */
    private function mapBorderStyleExcel($borderStyle)
    {
        switch ($borderStyle) {
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE:
                return 'none';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHDOT:
                return '1px dashed';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHDOTDOT:
                return '1px dotted';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHED:
                return '1px dashed';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED:
                return '1px dotted';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE:
                return '3px double';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR:
                return '1px solid';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM:
                return '2px solid';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUMDASHDOT:
                return '2px dashed';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUMDASHDOTDOT:
                return '2px dotted';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUMDASHED:
                return '2px dashed';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_SLANTDASHDOT:
                return '2px dashed';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK:
                return '3px solid';
            case PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN:
                return '1px solid';
            default:
                return '1px solid'; // map others to thin
        }
    }

    /**
     * Upload file excel
     *
     * @return boolean|string
     */
    private function uploadFileExcel()
    {
        if (!empty($_FILES)) {
            $tempFile = $_FILES['file'];
            //check file extension
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $tempFile['name'] = html_entity_decode($tempFile['name'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $ext = strtolower(pathinfo($tempFile['name'], PATHINFO_EXTENSION));
            $newname = uniqid() . '.' . $ext;
            if (!in_array($ext, $this->allowed_ext)) {
                $this->error_message = __('Wrong file extension', 'wptm');

                return false;
            }

            $upload_dir = wp_upload_dir();
            $targetPath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
                $data = '<html><body bgcolor="#FFFFFF"></body></html>';
                $file = fopen($targetPath . 'index.html', 'w');
                fwrite($file, $data);
                fclose($file);
            }

            $targetFile = $targetPath . $newname;
            if (!move_uploaded_file($tempFile['tmp_name'], $targetFile)) {
                $this->error_message = __('Error orcured when retrieving file to temporary folder', 'wptm');

                return false;
            } else {
                return $targetFile;
            }
        } else {
            $this->error_message = __('Please choose a file before submit!', 'wptm');

            return false;
        }
    }

    /**
     * Function export
     *
     * @param null|array $dataTable Data table export
     * @param null|array $ranger    Range cells
     *
     * @return void
     */
    public function export($dataTable = null, $ranger = null)
    {
        $file = $this->makeFileExcel($dataTable, $ranger);
        if ($file && is_readable($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);

            if (file_exists($file)) {
                unlink($file);
            }
        } else {
            $this->exitStatus(sprintf('%s', $this->error_message));
        }
        die();
    }

    /**
     * Function make file
     *
     * @param null|array $dataTable Data table export
     * @param null|array $ranger    Range cells
     *
     * @return boolean|resource|string
     */
    private function makeFileExcel($dataTable, $ranger = null)
    {
        $format_excel = Utilities::getInput('format_excel');
        $id = Utilities::getInt('id', 'GET');
        $onlydata = Utilities::getInt('onlydata', 'GET');
        if ($dataTable === null) {
            $modelTable = $this->getModel('table');
            $tableContent = $modelTable->getItem($id, true, true, null, false);
        } else {
            $tableContent = $dataTable;
        }

        if (gettype($tableContent->params) === 'string') {
            $tableParams = json_decode($tableContent->params, true);
        } else {
            $tableParams = json_encode($tableContent->params);
            $tableParams = json_decode($tableParams, true);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $activeSheet = $spreadsheet->getActiveSheet();

        $activeSheet->fromArray($tableContent->datas);
        $this->maxCell = $activeSheet->getHighestRowAndColumn();
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($this->maxCell['column']);

        $datas = $this->renderValueCalculateCell($tableContent->datas, $this->maxCell);

        $activeSheet->fromArray($datas);

        if ($format_excel === 'xlsx') {
            $activeSheet->rangeToArray('A1:' . $this->maxCell['column'] . $this->maxCell['row'], '', false);
        } else {
            $data = $activeSheet->rangeToArray('A1:' . $this->maxCell['column'] . $this->maxCell['row'], '', true);
            $activeSheet->fromArray($data);
        }

        if ($onlydata && $onlydata === 1) {
            $style_row = array();
            $style_column = array();
            /*set style table*/
            if (isset($tableContent->style->table)) {
                $tableStyles = (array)$tableContent->style->table;
                if (!empty($tableStyles)) {
                    $activeSheet = $this->setStyleTable($activeSheet, $tableStyles);
                }
            } else {
                $tableStyles = array();
            }

            /*set width columns*/
            if (isset($tableContent->style->cols) && !empty($tableContent->style->cols)) {
                $cI = 0;
                foreach ($tableContent->style->cols as $col) {
                    if (is_array($col) && isset($col[1]) && isset($col[1]->width)) {
                        $activeSheet->getColumnDimensionByColumn($cI + 1)->setWidth(floor($col[1]->width / 10)); //Excel unit: number of characters that can be displayed with the standard font
                        $style_column[$col[0]] = (array)$col[1];
                        $activeSheet = $this->setCellStyle($activeSheet, $cI + 1, array(1, $this->maxCell['row']), (array)$col[1], $tableStyles);
                    } elseif (!is_array($col) && isset($col->{1}) && isset($col->{1}->width)) {
                        $activeSheet->getColumnDimensionByColumn($cI + 1)->setWidth(floor($col->{1}->width / 10)); //Excel unit: number of characters that can be displayed with the standard font
                        $style_column[$col->{0}] = (array)$col->{1};
                        $activeSheet = $this->setCellStyle($activeSheet, $cI + 1, array(1, $this->maxCell['row']), (array)$col->{1}, $tableStyles);
                    }
                    $cI++;
                }
            }

            /*set height rows*/
            if ($tableContent->type !== 'mysql' && isset($tableContent->style->rows) && !empty($tableContent->style->rows)) {
                $rI = 0;
                foreach ($tableContent->style->rows as $row) {
                    $rI++;
                    if (is_array($row) && isset($row[1]) && isset($row[1]->height)) {
                        $activeSheet->getRowDimension($rI)->setRowHeight(floor($row[1]->height / 1.333333)); //px 2 pt
                        $activeSheet = $this->setCellStyle($activeSheet, array(1, $columnIndex), $rI, (array)$row[1], $tableStyles);
                        $style_row[$row[0]] = (array)$row[1];
                    } elseif (!is_array($row) && isset($row->{1}) && isset($row->{1}->height)) {
                        $activeSheet->getRowDimension($rI)->setRowHeight(floor($row->{1}->height / 1.333333)); //px 2 pt
                        $activeSheet = $this->setCellStyle($activeSheet, array(1, $columnIndex), $rI, (array)$row->{1}, $tableStyles);
                        $style_row[$row->{0}] = (array)$row->{1};
                    }
                }
            } elseif ($tableContent->type === 'mysql') {
                if (!empty($tableContent->style->table->allRowHeight) && $tableContent->style->table->allRowHeight > 0) {
                    $rowHeight = $tableContent->style->table->allRowHeight / 1.333333;
                }

                for ($rI = 0; $rI < $this->maxCell['row']; $rI++) {
                    if (isset($tableContent->style->rows) && isset($tableContent->style->rows->{$rI})) {
                        $row = $tableContent->style->rows->{$rI};
                        if (!empty($row[1]) && !empty($row[1]->height)) {
                            $activeSheet->getRowDimension($rI + 1)->setRowHeight(floor($row[1]->height / 1.333333)); //px 2 pt
                            $style_row[$row[0]] = (array)$row[1];
                            $activeSheet = $this->setCellStyle($activeSheet, array(1, $columnIndex), $rI + 1, (array)$row[1], $tableStyles);
                        } elseif (!empty($row->{1}) && !empty($row->{1}->height)) {
                            $activeSheet->getRowDimension($rI + 1)->setRowHeight(floor($row->{1}->height / 1.333333)); //px 2 pt
                            $style_row[$row->{0}] = (array)$row->{1};
                            $activeSheet = $this->setCellStyle($activeSheet, array(1, $columnIndex), $rI + 1, (array)$row->{1}, $tableStyles);
                        } else {
                            $activeSheet->getRowDimension($rI + 1)->setRowHeight(floor($rowHeight)); //px 2 pt
                            $activeSheet = $this->setCellStyle($activeSheet, array(1, $columnIndex), $rI + 1, array(), $tableStyles);
                        }
                    } else {
                        $activeSheet = $this->setCellStyle($activeSheet, array(1, $columnIndex), $rI + 1, array(), $tableStyles);
                    }
                }
            }

            /*set style cells*/
            if (isset($tableContent->style->cells)) {
                $cellStyles = $tableContent->style->cells;
                if (!empty($cellStyles)) {
                    foreach ($cellStyles as $key => $cellCss) {
                        $check_valid_cells = explode('!', $key);
                        if (isset($check_valid_cells[0]) && (int)$check_valid_cells[0] >= 0 && isset($cellCss[2])) {
                            $cellCss[2] = array_merge(
                                isset($style_row[$check_valid_cells[0]]) ? $style_row[$check_valid_cells[0]] : array(),
                                isset($style_column[$check_valid_cells[1]]) ? $style_column[$check_valid_cells[1]] : array(),
                                $cellCss[2]
                            );
                            $activeSheet = $this->setCellStyle($activeSheet, $check_valid_cells[1] + 1, $check_valid_cells[0] + 1, $cellCss[2], $tableStyles);
                        }
                    }
                }
            }

            if (isset($tableParams['mergeSetting'])) {
                $mergeSettings = is_string($tableParams['mergeSetting']) ? json_decode($tableParams['mergeSetting'], true) : $tableParams['mergeSetting'];
            } else {
                $mergeSettings = array();
            }

            if (!empty($mergeSettings)) {
                foreach ($mergeSettings as $mergeSetting) {
                    $activeSheet->mergeCellsByColumnAndRow($mergeSetting['col'] + 1, $mergeSetting['row'] + 1, $mergeSetting['col'] + $mergeSetting['colspan'], $mergeSetting['row'] + $mergeSetting['rowspan']);
                }
            }
        }

        if (!empty($ranger)) {
            if ($ranger[2] + 1 < $this->maxCell['row']) {
                $activeSheet->removeRow($ranger[2] + 2, $this->maxCell['row'] - $ranger[2] - 1);
            }
            if ($ranger[0] >= 1) {
                $activeSheet->removeRow(1, $ranger[0]);
            }
            if ($ranger[3] + 1 < $columnIndex) {
                $activeSheet->removeColumnByIndex($ranger[3] + 2, $columnIndex - $ranger[3] - 1);
            }

            if ($ranger[1] >= 1) {
                $activeSheet->removeColumnByIndex(1, $ranger[1]);
            }
        }

        if ($format_excel === 'xlsx') {
            $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        } elseif ($format_excel === 'xls') {
            $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        }

        $upload_dir = wp_upload_dir();
        $targetPath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
            $data = '<html><body bgcolor="#FFFFFF"></body></html>';
            $file = fopen($targetPath . 'index.html', 'w');
            fwrite($file, $data);
            fclose($file);
        }

        $tableContent->title = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $tableContent->title));
        $file = $targetPath . DIRECTORY_SEPARATOR . $tableContent->title . '_' . $id . '.' . $format_excel;

        try {
            $objWriter->save($file);
        } catch (Exception $e) {
            $this->error_message = __('Error occurred when creating file to export! <br/>Please try again.', 'wptm');
        }

        if (!file_exists($file) || !is_readable($file)) {
            $this->error_message = __('Error occurred when creating file to export! <br/>Please try again.', 'wptm');

            return false;
        }

        return $file;
    }

    /**
     * Function set style of table(freeze_row, col, symbol...)
     *
     * @param object $activeSheet ActiveSheet
     * @param array  $tableStyle  Data style table
     *
     * @return mixed
     */
    public function setStyleTable($activeSheet, $tableStyle)
    {
        /*freeze col and arow*/
        if (isset($tableStyle['freeze_col']) && $tableStyle['freeze_col']) {
            $freeze_col = (int)$tableStyle['freeze_col'] + 1;
        } else {
            $freeze_col = 1;
        }

        if (isset($tableStyle['freeze_row']) && $tableStyle['freeze_row']) {
            $freeze_row = (int)$tableStyle['freeze_row'] + 1;
        } else {
            $freeze_row = 1;
        }

        if ($freeze_row * $freeze_col !== 1) {
            $activeSheet->freezePaneByColumnAndRow($freeze_col, $freeze_row);
        }

        return $activeSheet;
    }

    /**
     * Function set style for cell
     *
     * @param object        $activeSheet ActiveSheet
     * @param integer|array $col         Col
     * @param integer|array $row         Row
     * @param array         $css         Css
     * @param array         $tableStyles Data option table
     *
     * @return mixed
     */
    public function setCellStyle($activeSheet, $col, $row, $css, $tableStyles)
    {
        $selection = array();
        if (is_array($row)) {
            $selection[1] = $row[0];//row start
            $selection[3] = $row[1];//row end
        } else {
            $selection[1] = $row;
            $selection[3] = $row;
        }
        if (is_array($col)) {
            $selection[0] = $col[0];//column start
            $selection[2] = $col[1];//column end
        } else {
            $selection[0] = $col;
            $selection[2] = $col;
        }
        $getStyle = $activeSheet->getStyleByColumnAndRow($selection[0], $selection[1], $selection[2], $selection[3]);

        //font
        if (isset($css['cell_font_bold']) && $css['cell_font_bold']) {
            $getStyle->getFont()->setBold(true);
        }
        if (isset($css['cell_font_underline']) && $css['cell_font_underline']) {
            $getStyle->getFont()->setUnderline(true);
        }
        if (isset($css['cell_font_italic']) && $css['cell_font_italic']) {
            $getStyle->getFont()->setItalic(true);
        }
        if (isset($css['cell_font_color']) && $css['cell_font_color']) {
            $getStyle->getFont()->getColor()->setRGB(str_replace('#', '', $css['cell_font_color']));
        }
        if (isset($css['cell_font_family']) && $css['cell_font_family']) {
            $getStyle->getFont()->setName($css['cell_font_family']);
        }
        if (isset($css['cell_font_size']) && $css['cell_font_size']) {
            $getStyle->getFont()->setSize($css['cell_font_size'] * 72 / 96); //points = pixels * 72 / 96
        }

        //Alignment
        if (isset($css['cell_vertical_align']) && $css['cell_vertical_align']) {
            if ($css['cell_vertical_align'] === 'middle') {
                $vertical = 'center';
            } else {
                $vertical = $css['cell_vertical_align'];
            }
            $getStyle->getAlignment()->setVertical($vertical);
        }
        if (isset($css['cell_text_align']) && $css['cell_text_align']) {
            $horizontal = $css['cell_text_align'];
            $getStyle->getAlignment()->setHorizontal($horizontal);
        }

        //Fill
        if (!isset($css['cell_background_color']) || isset($css['cell_background_color']) && $css['cell_background_color'] === '') {
            if (isset($tableStyles['allAlternate']) && isset($tableStyles['allAlternate']->old)) {
                if (is_array($col)) {
                    if ($selection[1] % 2 === (($tableStyles['allAlternate']->header !== '') ? 0 : 1)) {
                        $css['cell_background_color'] = $tableStyles['allAlternate']->even;
                    } else {
                        $css['cell_background_color'] = $tableStyles['allAlternate']->old;
                    }
                    if ($selection[1] === 1 && $tableStyles['allAlternate']->header !== '') {
                        $css['cell_background_color'] = $tableStyles['allAlternate']->header;
                    } elseif ($this->maxCell['row'] === $selection[1] && $tableStyles['allAlternate']->footer !== '') {
                        $css['cell_background_color'] = $tableStyles['allAlternate']->footer;
                    }
                }
            } else {
                if (isset($css['AlternateColor'])) {
                    $css['cell_background_color'] = $this->getAlternateColor($selection[1] - 1, $css, $tableStyles);
                }
            }
        }

        if (isset($css['cell_background_color'])) {
            $fill_color = str_replace('#', '', $css['cell_background_color']);
            if ($fill_color === null || $fill_color === '') {
                $fill_color = 'ffffff';
            }
            $getStyle->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $getStyle->getFill()->getStartColor()->setRGB($fill_color);
        }

        //Border
        if (isset($css['cell_border_bottom']) && $css['cell_border_bottom']) {
            list($bWidth, $bStyle, $bColor) = array_merge(explode(' ', $css['cell_border_bottom']), array('', ''));
            if ($bColor !== '') {
                $borderStyle = $this->getBorderStyle($bWidth, $bStyle);
                $getStyle->getBorders()->getBottom()->setBorderStyle($borderStyle);
                $bColor = str_replace('#', '', trim($bColor));
                $getStyle->getBorders()->getBottom()->getColor()->setRGB($bColor);
            }
        }

        if (isset($css['cell_border_top']) && $css['cell_border_top']) {
            list($bWidth, $bStyle, $bColor) = array_merge(explode(' ', $css['cell_border_top']), array('', ''));
            if ($bColor !== '') {
                $borderStyle = $this->getBorderStyle($bWidth, $bStyle);
                $getStyle->getBorders()->getTop()->setBorderStyle($borderStyle);
                $bColor = str_replace('#', '', trim($bColor));
                $getStyle->getBorders()->getTop()->getColor()->setRGB($bColor);
            }
        }

        if (isset($css['cell_border_left']) && $css['cell_border_left']) {
            list($bWidth, $bStyle, $bColor) = array_merge(explode(' ', $css['cell_border_left']), array('', ''));
            if ($bColor !== '') {
                $borderStyle = $this->getBorderStyle($bWidth, $bStyle);
                $getStyle->getBorders()->getLeft()->setBorderStyle($borderStyle);
                $bColor = str_replace('#', '', trim($bColor));
                $getStyle->getBorders()->getLeft()->getColor()->setRGB($bColor);
            }
        }

        if (isset($css['cell_border_right']) && $css['cell_border_right']) {
            list($bWidth, $bStyle, $bColor) = array_merge(explode(' ', $css['cell_border_right']), array('', ''));
            if ($bColor !== '') {
                $borderStyle = $this->getBorderStyle($bWidth, $bStyle);
                $getStyle->getBorders()->getRight()->setBorderStyle($borderStyle);
                $bColor = str_replace('#', '', trim($bColor));
                $getStyle->getBorders()->getRight()->getColor()->setRGB($bColor);
            }
        }

        return $activeSheet;
    }

    /**
     * Function set background color cell by alternate color
     *
     * @param integer $row         Row number
     * @param array   $css         Data css cell
     * @param array   $tableStyles Table styles
     *
     * @return string
     */
    public function getAlternateColor($row, $css, $tableStyles)
    {
        $fill_color = '';
        if (!isset($tableStyles['alternateColorValue'])) {
            return '';
        } else {
            if (is_array($tableStyles['alternateColorValue'])) {
                $alternateColorValue = (array)$tableStyles['alternateColorValue'][$css['AlternateColor']];
            } else {
                $alternateColorValue = (array)$tableStyles['alternateColorValue']->{$css['AlternateColor']};
            }
            if (empty($alternateColorValue)) {
                return '';
            }
        }

        $numberRow = 0;
        if ($alternateColorValue['header'] === '') {
            $numberRow = -1;
        }
        switch ($row) {
            case $alternateColorValue['selection'][0]:
                if ($numberRow === -1) {
                    $fill_color .= $alternateColorValue['even'];
                } else {
                    $fill_color .= $alternateColorValue['header'];
                }
                break;
            case $alternateColorValue['selection'][2]:
                if ($alternateColorValue['footer'] === '') {
                    if (($row - (int)($alternateColorValue['selection'][0] + $numberRow)) % 2) {
                        $fill_color .= $alternateColorValue['even'];
                    } else {
                        $fill_color .= $alternateColorValue['old'];
                    }
                } else {
                    $fill_color .= $alternateColorValue['footer'];
                }
                break;
            default:
                if (($row - (int)($alternateColorValue['selection'][0] + $numberRow)) % 2) {
                    $fill_color .= $alternateColorValue['even'];
                } else {
                    $fill_color .= $alternateColorValue['old'];
                }
                break;
        }

        return $fill_color;
    }

    /**
     * Function get Border Style cell
     *
     * @param integer $bWidth Width
     * @param string  $bStyle Style
     *
     * @return string
     */
    public function getBorderStyle($bWidth, $bStyle)
    {
        $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE;
        $bStyle = trim($bStyle);
        $bWidth = (int)$bWidth;
        if ($bWidth > 1) {
            switch ($bStyle) {
                case 'solid':
                    $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM;
                    break;
                case 'dashed':
                    $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUMDASHED;
                    break;
                case 'dotted':
                    $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUMDASHDOT;
                    break;
            }
        } elseif ((int)$bWidth === 1) {
            switch ($bStyle) {
                case 'solid':
                    $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
                    break;
                case 'dashed':
                    $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHED;
                    break;
                case 'dotted':
                    $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED;
                    break;
            }
        } else {
            $borderStyle = PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE;
        }

        return $borderStyle;
    }

    /**
     * Function syncSpreadsheet
     *
     * @return boolean|integer
     */
    public function syncSpreadsheet()
    {
        $modelTable = $this->getModel('tables');
        $tables = $modelTable->getItems(0, null, false);

        $count = 0;
        foreach ($tables as $table) {
            $wptm_syn_google_delay = get_option('wptm_syn_google_delay_' . $table->id, false);
            if ($table->type === 'html' && $wptm_syn_google_delay === false) {
                update_option('wptm_syn_google_delay_' . $table->id, true, false);
                $count = $this->synControl($table, $count);
                delete_option('wptm_syn_google_delay_' . $table->id);
            } else {
                continue;
            }
        }

        return $count; //number of table synced
    }

    /**
     * Function syncSpreadsheet in google script
     *
     * @param integer $id Id table
     *
     * @return boolean|integer
     */
    public function synControlGoogleScript($id)
    {
        Application::getInstance('Wptm');
        /* @var WptmModelTable $modelTable */
        $modelTable = $this->getModel('table');
        if (!$modelTable) {
            // call this function from site
            require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'table.php';
            $modelTable = new WptmModelTable();
        }

        $table = $modelTable->getItem($id, false, true);
        $count = $this->synControl($table, 0, 'push');

        return $count; //number of table synced
    }

    /**
     * SyncSpreadsheet control function (googleSpreadsheet / import excel)
     *
     * @param object  $table   Table Data
     * @param integer $count   Table number
     * @param string  $typeSyn Syn type
     *
     * @return integer
     */
    public function synControl($table, $count, $typeSyn = '')
    {
        if (is_string($table->style)) {
            $tblStyles = new stdClass();
            $tblStyles->table = json_decode($table->params);
        } else {
            $tblStyles = $table->style;
        }

        if (isset($tblStyles->table)) {
            if ((isset($tblStyles->table->spreadsheet_url) && $tblStyles->table->spreadsheet_url)) {
                if (($typeSyn === 'push' && isset($tblStyles->table->auto_push) && (int)$tblStyles->table->auto_push === 1)
                    || ($typeSyn !== 'push' && isset($tblStyles->table->auto_sync) && (int)$tblStyles->table->auto_sync === 1)) {
                    $spreadsheet_url = $tblStyles->table->spreadsheet_url;
                    $spreadsheet_style = isset($tblStyles->table->spreadsheet_style) ? (int)$tblStyles->table->spreadsheet_style : 0;
                    if (strpos($spreadsheet_url, 'docs.google.com/spreadsheet') !== false) {
                        $syncType = 'spreadsheet';
                        if (isset($tblStyles->table->select_sheet_google) && $tblStyles->table->select_sheet_google) {
                            $this->getSheetFromAdmin = (int)$tblStyles->table->select_sheet_google;
                        }
                    } else {
                        $syncType = 'excel';
                        if (isset($tblStyles->table->select_sheet) && $tblStyles->table->select_sheet) {
                            $this->getSheetFromAdmin = (int)$tblStyles->table->select_sheet;
                        }
                    }

                    if ($this->updateTableFromSpreadsheet($table->id, $spreadsheet_url, $spreadsheet_style, $syncType)) {
                        $count++;
                    }
                }
            }

            if (isset($tblStyles->table->excel_auto_sync)
                && (int)$tblStyles->table->excel_auto_sync === 1
                && isset($tblStyles->table->excel_url)
                && $tblStyles->table->excel_url) {
                $spreadsheet_url = $tblStyles->table->excel_url;
                $spreadsheet_style = isset($tblStyles->table->excel_spreadsheet_style) ? (int)$tblStyles->table->excel_spreadsheet_style : 0;
                if (strpos($spreadsheet_url, 'docs.google.com/spreadsheet') !== false) {
                    $syncType = 'spreadsheet';
                    if (isset($tblStyles->table->select_sheet_google) && $tblStyles->table->select_sheet_google) {
                        $this->getSheetFromAdmin = (int)$tblStyles->table->select_sheet_google;
                    }
                } else {
                    $syncType = 'excel';
                    if (isset($tblStyles->table->select_sheet) && $tblStyles->table->select_sheet) {
                        $this->getSheetFromAdmin = (int)$tblStyles->table->select_sheet;
                    }
                }

                if ($this->updateTableFromSpreadsheet($table->id, $spreadsheet_url, $spreadsheet_style, $syncType)) {
                    $count++;
                }
            }

            if (isset($tblStyles->table->auto_sync_onedrive)
                && (int)$tblStyles->table->auto_sync_onedrive === 1
                && isset($tblStyles->table->onedrive_url)
                && $tblStyles->table->onedrive_url) {
                $spreadsheet_url = $tblStyles->table->onedrive_url;
                $spreadsheet_style = isset($tblStyles->table->onedrive_style) ? (int)$tblStyles->table->onedrive_style : 0;

                if (isset($tblStyles->table->select_sheet_one) && $tblStyles->table->select_sheet_one) {
                    $this->getSheetFromAdmin = (int)$tblStyles->table->select_sheet_one;
                }

                if ($this->updateTableFromSpreadsheet($table->id, $spreadsheet_url, $spreadsheet_style, 'onedrive')) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Function fetch Spread sheet from table edit page|sync from heartbeat
     *
     * @return void
     */
    public function fetchSpreadsheet()
    {
        $id_table = Utilities::getInt('id', 'POST');
        $autoSync = Utilities::getInt('sync', 'POST');
        $fetchStyle = Utilities::getInt('style', 'POST');
        $getListSheet = Utilities::getInt('getListSheet', 'POST');

        if (isset($autoSync) && (int)$autoSync === 1) {
            $this->heartbeat = true;
        }

        if ($getListSheet) {
            $this->getSheetFromAdmin = (int)$getListSheet > 0 ? (int)$getListSheet : false;
        }

        $syncType = Utilities::getInput('syncType', 'POST');//fetch excel/google
        $spreadsheet_url = Utilities::getInput('spreadsheet_url', 'POST', 'none');

        if ($id_table && $spreadsheet_url) {
            $GLOBALS['wp_object_cache']->delete('wptm_syn_google_delay_' . $id_table, 'options');
            $wptm_syn_google_delay = get_option('wptm_syn_google_delay_' . $id_table, false);
            if ($wptm_syn_google_delay === false) {
                update_option('wptm_syn_google_delay_' . $id_table, true, false);
                $update = $this->updateTableFromSpreadsheet($id_table, $spreadsheet_url, $fetchStyle, $syncType);
            } else {
                $update = true;
            }

            delete_option('wptm_syn_google_delay_' . $id_table);

            if ($this->heartbeat) {
                $this->exitStatus(true);
            }

            if (!$update) {
                $this->exitStatus(__('error while saving table', 'wptm'));
            } else {
                if (count($this->error_message_read_file) > 0) {
                    $this->exitStatus(true, array('sync' => $autoSync, 'style' => $fetchStyle, 'spreadsheet_url' => $spreadsheet_url, 'error_read_file' => $this->error_message_read_file[0]));
                }
                $this->exitStatus(true, array('sync' => $autoSync, 'style' => $fetchStyle, 'spreadsheet_url' => $spreadsheet_url));
            }
        }

        if (count($this->error_message_read_file) > 0) {
            $this->exitStatus(true, array('autoSync' => $autoSync, 'style' => $fetchStyle, 'spreadsheet_url' => $spreadsheet_url, 'error_read_file' => $this->error_message_read_file[0]));
        }

        $this->exitStatus(true, array(
            'sync' => $autoSync,
            'style' => $fetchStyle,
            'spreadsheet_url' => $spreadsheet_url
        ));
    }

    /**
     * Function update Table From Spread sheet
     *
     * @param integer $id_table        Id of table
     * @param string  $spreadsheet_url Url
     * @param integer $fetchStyle      Fetch style table
     * @param string  $syncType        Fetch from google or excel
     *
     * @return boolean
     */
    public function updateTableFromSpreadsheet($id_table, $spreadsheet_url, $fetchStyle, $syncType)
    {
        Application::getInstance('Wptm');
        /* @var WptmModelTable $modelTable */
        $modelTable = $this->getModel('table');
        if (!$modelTable) {
            // call this function from site
            require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'table.php';
            $modelTable = new WptmModelTable();
        }

        if ($fetchStyle === 1) {//render new style
            $this->fetchStyle = true;
            $tableContent = (array)$modelTable->getItem($id_table, false, true);
            $tableContent['style'] = new stdClass();
            $tableContent['style']->rows = new stdClass();
            $tableContent['style']->cols = new stdClass();
        } else {
            $tableContent = (array)$modelTable->getItem($id_table, false, false);
        }

        if ($syncType === 'spreadsheet' && $this->getSheetFromAdmin === 99 && isset($tableContent['params']->select_sheet_google) && $tableContent['params']->select_sheet_google > 0) {
            $this->getSheetFromAdmin = (int)$tableContent['params']->select_sheet_google;
        }

        if ($syncType === 'excel' && $this->getSheetFromAdmin === 99 && isset($tableContent['params']->select_sheet) && $tableContent['params']->select_sheet > 0) {
            $this->getSheetFromAdmin = (int)$tableContent['params']->select_sheet;
        }

        if ($syncType === 'onedrive' && $this->getSheetFromAdmin === 99 && isset($tableContent['params']->select_sheet_one) && $tableContent['params']->select_sheet_one > 0) {
            $this->getSheetFromAdmin = (int)$tableContent['params']->select_sheet_one;
        }

        $doUpdate = false;

        if (strpos($spreadsheet_url, 'docs.google.com/spreadsheet') !== false) {
            $syncType = 'spreadsheet';
            $url_arr = explode('/', $spreadsheet_url);
            array_pop($url_arr);

            if (preg_match('/sharing/i', substr($spreadsheet_url, -8, 8)) === 1) {//link sharing
                $csv_url = implode('/', $url_arr) . '/export?format=xlsx';
            } else {
                //convert to url export csv
                $csv_url = implode('/', $url_arr) . '/pub?hl=en_US&single=true&output=csv';

                $url_query = parse_url($spreadsheet_url, PHP_URL_QUERY);
                if (!empty($url_query)) {
                    parse_str($url_query, $url_query_params);
                    if (isset($url_query_params['gid'])) {
                        $csv_url .= '&gid=' . $url_query_params['gid'];
                    }
                }
            }

            /*download file .csv*/
            $files = $this->getCsvDataFromUrl($csv_url, $spreadsheet_url);

            if ($files !== false) {
                //read file xlsx
                if (isset($files['xlsx'])) {
                    $readFileExcel = $this->readFileExcel($files['xlsx'], true, false, $tableContent['params'], $id_table, $fetchStyle === 1);//return text, file, status
                    $file = $files['xlsx'];
                }

                if (isset($files['csv'])) {
                    if (!(isset($readFileExcel['status']) && $readFileExcel['status'])) {//not get style from csv
                        $fetchStyle = 0;
                        $readFileExcel = $this->readFileExcel($files['csv'], true, false, $tableContent['params'], $id_table, false);
                    }
                    unlink($files['csv']);
                }

                if (isset($readFileExcel['status']) && $readFileExcel['status']) {//ok file
                    $doUpdate = true;
                } elseif (isset($readFileExcel['text'])) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel['text']));
                } elseif (is_string($readFileExcel)) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel));
                } else {
                    return false;
                }
            } else {
                $this->exitStatus($this->error_message !== '' ? $this->error_message : __('There was an error fetch the file', 'wptm'));
                return false;
            }
        } elseif ($syncType === 'onedrive') {
            /*download file .csv*/
            $file = $this->downloadFileOneDrive($spreadsheet_url, $id_table);

            if ($file !== false) {
                $readFileExcel = $this->readFileExcel($file, true, false, $tableContent['params'], $id_table, $fetchStyle === 1);
                if (isset($readFileExcel['status']) && $readFileExcel['status']) {
                    $doUpdate = true;
                } elseif (isset($readFileExcel['text'])) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel['text']));
                } elseif (is_string($readFileExcel)) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel));
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } elseif ($syncType === 'csv') {
            /*download file .csv*/
            $file = $this->downloadFileExcel($spreadsheet_url, 'csv');

            if ($file !== false) {
                $this->getSheetFromAdmin = false;//csv have only one sheet
                $readFileExcel = $this->readCsv($file);
                $this->fetchStyle = false;
                $fetchStyle = 0;
//                $readFileExcel = $this->readFileExcel($file, true, false, $tableContent['params'], $id_table, $fetchStyle === 1);
                if (isset($readFileExcel['status']) && $readFileExcel['status']) {
                    $doUpdate = true;
                } elseif (isset($readFileExcel['text'])) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel['text']));
                } elseif (is_string($readFileExcel)) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel));
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $syncType = 'excel';
            //download file
            $file = $this->downloadFileExcel($spreadsheet_url);

            if ($file) {
                $readFileExcel = $this->readFileExcel($file, true, false, $tableContent['params'], $id_table, $fetchStyle === 1);

                if (isset($readFileExcel['status']) && $readFileExcel['status']) {
                    $doUpdate = true;
                } elseif (isset($readFileExcel['text'])) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel['text']));
                } elseif (is_string($readFileExcel)) {
                    if ($this->heartbeat) {
                        return false;
                    }
                    $this->exitStatus(esc_attr($readFileExcel));
                } else {
                    return false;
                }
            } else {
                if (!$this->heartbeat) {
                    $this->exitStatus($this->error_message !== '' ? $this->error_message : __('There is an error when download the file', 'wptm'));
                }
                return false;
            }
        }

        //remove old style
        if ($this->fetchStyle) {
            $modelTable->deleteOldStyle($id_table);
        }
        $updated = false;
        if (isset($readFileExcel)) {
            $tableContent['datas'] = $readFileExcel['data']['data'];
            $tableContent['params']->mergeSetting = $readFileExcel['data']['mergeSetting'];

            if ($syncType === 'spreadsheet') {
                $tableContent['params']->spreadsheet_style = $fetchStyle;
                $spreadsheet_url = str_replace('%20', ' ', $spreadsheet_url);
                $tableContent['params']->spreadsheet_url = $spreadsheet_url;
            } elseif ($syncType === 'onedrive') {
                $tableContent['params']->onedrive_style = $fetchStyle;
                $spreadsheet_url = str_replace('%20', ' ', $spreadsheet_url);
                $tableContent['params']->onedrive_url = $spreadsheet_url;
            } elseif ($syncType === 'csv') {
                $tableContent['params']->csv_url = $spreadsheet_url;
            } else {
                $tableContent['params']->excel_spreadsheet_style = $fetchStyle;
                $spreadsheet_url = str_replace('%20', ' ', $spreadsheet_url);
                $tableContent['params']->excel_url = $spreadsheet_url;
            }

            $tableContent['numberRow'] = count($tableContent['datas']);
            $tableContent['numberCol'] = count($tableContent['datas'][0]);

            if ($fetchStyle === 1) {
                $tableContent['params']->cell_types = $readFileExcel['data']['typeCells'];
                $tableContent['style']->rows = $readFileExcel['data']['style']['rows'];
                $tableContent['style']->cols = $readFileExcel['data']['style']['cols'];
                $tableContent['style'] = json_encode($tableContent['style']);

                $tableContent['styleCells'] = $readFileExcel['data']['style']['cell'];
            } else {
                $newStyleRowsCols = $this->updateStyleColRowWhenImport(
                    $tableContent['style']->rows,
                    $tableContent['style']->cols,
                    $tableContent['numberRow'],
                    $tableContent['numberCol']
                );
                $tableContent['style']->rows = $newStyleRowsCols[0];
                $tableContent['style']->cols = $newStyleRowsCols[1];
            }

            $count = count($readFileExcel['data']['hyperlink']);
            if ($readFileExcel['data']['hyperlink'] !== false) {
                if ($count > 0) {
                    $tableContent['params']->cell_types = $this->changeHyperlinksTable($tableContent['params']->cell_types, $readFileExcel['data']['hyperlink']);
                }
            }
            $tableContent['params']->hyperlink = $readFileExcel['data']['hyperlink'];

            $tableContent['action'] = 'insert';

            if ($doUpdate) {
                //add table header = 1
                $header = 1;
                $tableContent['params']->headerOption = $header;
                $tableContent['params']->header_data = array();
                for ($i = 0; $i < $header; $i++) {
                    $tableContent['params']->header_data[] = $tableContent['datas'][$i];
                }
                //update header option by merger cells
                $updateMergeCells = $modelTable->updateMergeCells($id_table, false, $tableContent['params'], array_slice($tableContent['datas'], 0, 9));
                if (is_array($updateMergeCells)) {
                    $tableContent['params'] = $updateMergeCells;
                }

                if ($modelTable->saveTableSynfile($id_table, $tableContent)) {
                    $updated = true;
                    /**
                     * Action hook after update table when sync table
                     *
                     * @param integer $id_table        Table id
                     * @param string  $spreadsheet_url Sync url/link
                     * @param string  $syncType        Sync type
                     */
                    do_action('wptm_after_update_table_when_sync', $id_table, $spreadsheet_url, $syncType);
                } else {
                    $this->exitStatus(__('error while saving table', 'wptm'));
                }
            }
        }

        if (isset($file) && file_exists($file)) {
            unlink($file);
        }

        return $updated;
    }

    /**
     * Update style for rows and cols when readonly excel file
     *
     * @param object  $rows          Old rows style
     * @param object  $cols          Old cols style
     * @param integer $numberNewRows New rows number
     * @param integer $numberNewCols New cols number
     *
     * @return array
     */
    public function updateStyleColRowWhenImport($rows, $cols, $numberNewRows, $numberNewCols)
    {
        $lengthOldRow = count((array)$rows);
        $lengthOldRow = !$lengthOldRow ? 0 : $lengthOldRow;

        if ($lengthOldRow < $numberNewRows) {
            for ($i = $lengthOldRow; $i < $numberNewRows; $i++) {
                if (is_array($rows)) {
                    if (!isset($rows[$i])) {
                        $rows[$i] = array();
                    }
                    $rows[$i][0] = $i;
                    $rows[$i][1]['height'] = 23;
                } else {
                    if (!isset($rows->{$i})) {
                        $rows->{$i} = array();
                    }
                    $rows->{$i}[0] = $i;
                    $rows->{$i}[1]['height'] = 23;
                }
            }
        } elseif ($lengthOldRow > $numberNewRows) {
            for ($i = $numberNewRows; $i < $lengthOldRow; $i++) {
                if (is_array($rows)) {
                    unset($rows[$i]);
                } else {
                    unset($rows->{$i});
                }
//                is_object($rows) ? unset($rows->{$i}) : unset($rows[$i]);
            }
        }

        $lengthOldCols = count((array)$cols);
        $lengthOldCols = !$lengthOldCols ? 0 : $lengthOldCols;
        if ($lengthOldCols < $numberNewCols) {
            for ($i = $lengthOldCols; $i < $numberNewCols; $i++) {
                if (is_array($cols)) {
                    if (!isset($cols[$i])) {
                        $cols[$i] = array();
                    }
                    $cols[$i][0] = $i;
                    $cols[$i][1]['res_priority'] = $i;
                    $cols[$i][1]['width'] = 100;
                } else {
                    if (!isset($cols->{$i})) {
                        $cols->{$i} = array();
                    }
                    $cols->{$i}[0] = $i;
                    $cols->{$i}[1]['res_priority'] = $i;
                    $cols->{$i}[1]['width'] = 100;
                }
            }
        } elseif ($lengthOldCols > $numberNewCols) {
            for ($i = $numberNewCols; $i < $lengthOldCols; $i++) {
                if (is_array($rows)) {
                    unset($cols[$i]);
                } else {
                    unset($cols->{$i});
                }
            }
        }

        return array($rows, $cols);
    }

    /**
     * Convert number to alphabet
     *
     * @param integer|string $number Number
     *
     * @return string
     */
    public function numberToAlphabet($number)
    {
        $number = intval($number);
        if ($number <= 0) {
            return '';
        }
        $alphabet = '';
        while ($number !== 0) {
            $p = ($number - 1) % 26;
            $number = intval(($number - $p) / 26);
            $alphabet = chr(65 + $p) . $alphabet;
        }
        return $alphabet;
    }

    /**
     * List error code when use PhpSpreadsheet
     *
     * @var array
     */
    private $errorCodes = array(
        '#NULL!' => 0,
        '#DIV/0!' => 1,
        '#VALUE!' => 2,
        '#REF!' => 3,
        '#NAME?' => 4,
        '#NUM!' => 5,
        '#N/A' => 6,
    );

    /**
     * Function read data file excel(import, fetch)
     *
     * @param string  $file             Url file excel
     * @param boolean $data             Check get data cell
     * @param boolean $ignoreCheck      Ignore check
     * @param object  $tableOptionStyle Table style data option
     * @param integer $id               Table id
     * @param boolean $renderStyle      Check get style table
     *
     * @return boolean|array
     */
    public function readFileExcel($file, $data, $ignoreCheck, $tableOptionStyle, $id, $renderStyle = false)
    {
        $tableContent = array();
        $tblStyles = array();
        $hyperlinks = array();
        $cell_type = array();

        $folder_admin = dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin';

        if (!is_readable($file)) {
            return array(
                'text' => __('This file is not readable', 'wptm'),
                'file' => basename($file),
                'status' => false
            );
        }

        //note: cannot convert to csv file to read when overflowing, because the transfer process is equally heavy.
        //can't select to read 1 sheet to avoid overflow, same reason above
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            //Load $inputFileName to a Spreadsheet Object
            if (!$spreadsheet->getSheetCount()) {
                return false;
            }
            $max_sheet = $spreadsheet->getSheetCount();
            if ($this->getSheetFromAdmin && $this->getSheetFromAdmin > 0) {
                if ($this->getSheetFromAdmin - 1 >= $max_sheet) {
                    //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Log error messages for users
                    error_log(__('Data update failed, the sheet not exist, keep the same table with id ', 'wptm') . $id);
                    return array(
                        'text' => __('Selected sheet does not exist !', 'wptm'),
                        'file' => basename($file),
                        'status' => false
                    );
                }
                $sheet = $spreadsheet->getSheet($this->getSheetFromAdmin - 1 >= $max_sheet ? 0 : $this->getSheetFromAdmin - 1);
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $maxCell = $sheet->getHighestRowAndColumn();
            $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxCell['column']);

            // If there are more than 100 rows we need to re-check number of rows actually have data to avoid a sheet with many empty rows
            if ($maxCell['row'] > 100) {
                $valueDatas = $sheet->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row'], null, false, true, false);
                //we need to first know how many rows actually have data
                $row_count = $maxCell['row'];
                // read through the data and see how many rows actually have data
                //the idea is that for every row, the first or second cell should be mandatory...
                //if we find one that is not, we stop there...

                $minRow = (int)($maxCell['row'] / 100);

                for ($i = $minRow; $i >= 0; $i--) {
                    $colRandom = rand(0, $maxColIndex);
                    if (!((!$valueDatas[$i * 100][0] || $valueDatas[$i * 100][0] === 'NULL') &&
                        (!$valueDatas[$i * 100][$colRandom] || $valueDatas[$i * 100][$colRandom] === 'NULL'))) {
                        $minRow = $i;
                        break;
                    }
                }
                $row_count = ($minRow + 1) * 100;
                $minColRandom = $maxColIndex > 1 ? 1 : 0;

                do {
                    if ($maxColIndex - 2 > $minColRandom) {
                        $colRandom = rand($minColRandom, $maxColIndex - 2);
                    } else {
                        $colRandom = $minColRandom;
                    }
                    $row_count--;
                } while (empty($valueDatas[$row_count]) ||
                ((!$valueDatas[$row_count][0] || $valueDatas[$row_count][0] === 'NULL') &&
                (!$valueDatas[$row_count][$colRandom] || $valueDatas[$row_count][$colRandom] === 'NULL') &&
                (!$valueDatas[$row_count][$colRandom + 1] || $valueDatas[$row_count][$colRandom + 1] === 'NULL') &&
                (!$valueDatas[$row_count][$colRandom - 1] || $valueDatas[$row_count][$colRandom - 1] === 'NULL')
                ));

                $maxCell['row'] = $row_count + 1;
            }

            $modelConfig  = $this->getModel('config');
            $paramsConfig = $modelConfig->getConfig();

            //get style cells and FormatCode cells
            $formatCode = array();

            $maxCells = (int)$maxCell['row'] * $maxColIndex;
            /*convert Separator for cells*/
            if (isset($tableOptionStyle->decimal_symbol)) {
                $decimal_symbol = $tableOptionStyle->decimal_symbol;
            } else {
                $decimal_symbol = !isset($paramsConfig['decimal_sym']) ? '.' : $paramsConfig['decimal_sym'];
            }
            if (isset($tableOptionStyle->thousand_symbol)) {
                $thousand_symbol = $tableOptionStyle->thousand_symbol;
            } else {
                $thousand_symbol = !isset($paramsConfig['thousand_sym']) ? ',' : $paramsConfig['thousand_sym'];
            }
            if (isset($tableOptionStyle->decimal_count)) {
                $decimal_count = $tableOptionStyle->decimal_count;
            } else {
                $decimal_count = !isset($paramsConfig['decimal_count']) ? 0 : $paramsConfig['decimal_count'];
            }
            if (isset($tableOptionStyle->currency_symbol)) {
                $currency_symbol = $tableOptionStyle->currency_symbol;
            } else {
                $currency_symbol = !isset($paramsConfig['currency_sym']) ? '$' : $paramsConfig['currency_sym'];
            }
            if (isset($tableOptionStyle->symbol_position)) {
                $symbol_position = $tableOptionStyle->symbol_position;
            } else {
                $symbol_position = !isset($paramsConfig['symbol_position']) ? '0' : $paramsConfig['symbol_position'];
            }

            \PhpOffice\PhpSpreadsheet\Shared\StringHelper::setThousandsSeparator($thousand_symbol);
            \PhpOffice\PhpSpreadsheet\Shared\StringHelper::setDecimalSeparator($decimal_symbol);

            if ($data) {
                \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->disableCalculationCache();
                \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance()->setCalculationCacheEnabled(false);
                $datas = $sheet->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row'], '', false, true, false);
                $datas = $this->utf8Converter($datas);
                \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->clearCalculationCache();
                try {
                    $valueDatas = $sheet->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row'], '', true, true, false);
                    \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->clearCalculationCache();

                    $valueDatas = $this->utf8Converter($valueDatas);
                } catch (\Exception $e) {
                    $this->error_message = $e->getMessage();
                    $this->error_message_read_file[] = $e->getMessage();
                }

                if (isset($valueDatas)) {
                    /*change type cell value to string and replace ',' to ';' in calculate*/
                    $pattern = '@^=(date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$@i';
                    $pattern1 = '@^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$@';

                    require_once $folder_admin . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'worksheetDrawingExt.php';
                    $wsExt = new WorksheetDrawingExt($sheet);

                    $attachments = WptmTablesHelper::getAttachmentFileInTable($id);

                    $updateAttachments = false;
                    if (count($attachments) > 0) {
                        $updateAttachments = true;
                    }
                    for ($i = 0; $i < $maxCell['row']; $i++) {
                        $count = count($datas[$i]);
                        $c = 0;
                        foreach ($sheet->getRowIterator($i + 1, $i + 1) as $row) {
                            $cellIterator = $row->getCellIterator();
                            $cellIterator->setIterateOnlyExistingCells(false);
                            foreach ($cellIterator as $cell) {
                                if (!isset($datas[$i][$c])) {
                                    break;
                                }

                                //Report the coord:
                                $coord = $cell->getCoordinate();

                                //Report images - our new feature in action
                                $ds = $wsExt->drawingsForCoordinate($coord);
                                if (!empty($ds)) {
                                    //get column index
//                                    $col = preg_replace('/[^A-Z]/', '', $coord);
//                                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col);

                                    foreach ($ds as $d) {
                                        $imageContents = file_get_contents($d->getPath());
//                                        $extension = WptmTablesHelper::mimeExt(mime_content_type($d->getPath()));
                                        $fileName = $id . '_' . basename($d->getPath());

                                        if (isset($attachments[$fileName])) {
                                            $newData = WptmTablesHelper::uploadImage($fileName, $d->getPath(), $attachments[$fileName]);
                                        } else {
                                            $newData = WptmTablesHelper::uploadImage($fileName, $d->getPath(), null);
                                        }

                                        if (is_array($newData)) {
                                            $height = $sheet->getRowDimension($i + 1)->getRowHeight();
                                            if ($height === -1) {
                                                $height = 18;
                                            }

                                            if (isset($newData[3])) {
                                                $datas[$i][$c] = '<img class="alignnone size-full wp-image-' . $attachments[$fileName] . '" src="' . $newData[0] . '" alt="" style="width: auto; max-height: ' . floor($height * 1.333333) . 'px"/>';
                                            } else {
                                                $attachments[$fileName] = $newData[1];
                                                $datas[$i][$c] = '<img class="alignnone size-full wp-image-' . $newData[1] . '" src="' . $newData[0] . '" alt="" style="width: auto; max-height: ' . floor($height * 1.333333) . 'px"/>';
                                            }
                                            $valueDatas[$i][$c] = $datas[$i][$c];
                                        }

                                        if ($renderStyle) {
                                            $cell_type[] = array($i, $c, 1, 1, 'html');
                                        }
                                    };
                                }

                                $data_cell = (string)$datas[$i][$c];
                                $key = substr($data_cell, 0, 1);
                                $cell_position = $this->numberToAlphabet($c + 1) . '' . ($i + 1);

                                if (isset($valueDatas)) {
                                    $datas[$i][$c] = $valueDatas[$i][$c];
                                    if ($key === '=' && preg_match($pattern, $data_cell, $matches)) {
                                        if (isset($matches[1]) && is_string($matches[1]) && !preg_match($pattern1, $data_cell, $matches1)) {
                                            $data_cell = str_replace($matches[1], strtoupper($matches[1]), $data_cell);
                                        }
                                        $datas[$i][$c] = str_replace(',', ';', $data_cell);
                                    } elseif (isset($valueDatas[$i][$c]) && $valueDatas[$i][$c] !== '#NUM!') {
                                        if (!isset($paramsConfig['automatically_calculation']) || (int)$paramsConfig['automatically_calculation'] === 1) {
                                            if (is_bool($valueDatas[$i][$c])) {//fix Calculation result is boolean
                                                $datas[$i][$c] = $valueDatas[$i][$c] ? 'true' : 'false';
                                            }
                                            if ($key === '=' && $valueDatas[$i][$c] === $datas[$i][$c]) {//not render value
                                                $this->error_message_read_file[] = '' . $cell_position;
                                            }
                                        } else {
                                            if ($key === '=') {
                                                if ($valueDatas[$i][$c] === $data_cell) {//not render value
                                                    $this->error_message_read_file[] = '' . $cell_position;
                                                } else {
                                                    $datas[$i][$c] = $data_cell;
                                                }
                                            } else {
                                                if (is_bool($valueDatas[$i][$c])) {//fix Calculation result is boolean
                                                    $datas[$i][$c] = $valueDatas[$i][$c] ? 'true' : 'false';
                                                }
                                            }
                                        }
                                    }
                                } else {//has formula cell be not read, $valueDatas is understand
                                    try {
                                        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->clearCalculationCache();
                                        $datas[$i][$c] = $sheet->getCell($cell_position)->getCalculatedValue();
                                        if (isset($this->errorCodes[$datas[$i][$c]])) {
                                            $this->error_message_read_file[] = '' . $cell_position;
                                        }
                                        $datas[$i][$c] = $datas[$i][$c] === null ? '' : $datas[$i][$c];
                                    } catch (\Exception $e) {
                                        $this->error_message_read_file[] = '' . $cell_position;
                                    }
//                                $datas[$i][$c] = str_replace(',', ';', $data_cell);
                                }

                                if ($renderStyle && preg_match_all('@<(\w+)\b.*?>.*?</\1>@si', $data_cell, $matches2) > 0) {
                                    $cell_type[] = array($i, $c, 1, 1, 'html');
                                }
                                $c++;
                            }
                        }
                    }

                    WptmTablesHelper::updateAttachmentFileInTable($id, $attachments, $updateAttachments);

//                    $datas = $this->changeValueCalculateCell($datas, $valueDatas, $maxCell);
                }
            }

            if (count($this->error_message_read_file) > 0) {
                $erros1 = '' . implode(', ', $this->error_message_read_file);
                //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Log error messages for users
                error_log('WARNING Wptm:' . $erros1);

                $wptm_error_message_read_file = get_option('wptm_error_message_read_file', '');
                if ($wptm_error_message_read_file !== '') {
                    $wptm_error_message = json_decode($wptm_error_message_read_file, true);
                }
                if (!isset($wptm_error_message)) {
                    $wptm_error_message = array();
                }

                if (count($this->error_message_read_file) > 10) {
                    $erros = '' . implode(', ', array(
                            $this->error_message_read_file[0],
                            $this->error_message_read_file[1],
                            $this->error_message_read_file[2],
                            $this->error_message_read_file[3],
                            $this->error_message_read_file[4],
                            $this->error_message_read_file[5],
                            '...',
                            $this->error_message_read_file[count($this->error_message_read_file) - 2],
                            $this->error_message_read_file[count($this->error_message_read_file) - 1]
                        ));
                } else {
                    $erros = $erros1;
                }
                $wptm_error_message[$id] = $erros;

                update_option('wptm_error_message_read_file', json_encode($wptm_error_message));
                $this->error_message_read_file[0] = $erros;
            }

            if (empty($datas)) {
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        return array('text' => ' - No errors', 'status' => false);
                    case JSON_ERROR_DEPTH:
                        return array('text' => ' - Maximum stack depth exceeded', 'status' => false);
                    case JSON_ERROR_STATE_MISMATCH:
                        return array('text' => ' - Underflow or the modes mismatch', 'status' => false);
                    case JSON_ERROR_CTRL_CHAR:
                        return array('text' => ' - Unexpected control character found', 'status' => false);
                    case JSON_ERROR_SYNTAX:
                        return array('text' => ' - Syntax error, malformed JSON', 'status' => false);
                    // phpcs:ignore PHPCompatibility.Constants.NewConstants.json_error_utf8Found -- the use of JSON_ERROR_UTF8 has check
                    case JSON_ERROR_UTF8:
                        return array(
                            'text' => ' - Malformed UTF-8 characters, possibly incorrectly encoded',
                            'status' => false
                        );
                    default:
                        return array('text' => ' - Unknown error', 'status' => false);
                }
            }

            if ($renderStyle) {
                $tblStyles['cols'] = new stdClass();
                $tblStyles['rows'] = new stdClass();
                $tblStyles['cell'] = array();
                for ($ri = 1; $ri <= $maxCell['row']; $ri++) {
                    if ($file !== '' && $maxCells > 100000 && (($maxColIndex <= 30 && $ri % 3000 === 0) || ($maxColIndex > 30 && $ri % 2000 === 0))) {//large table :(
                        $spreadsheet->disconnectWorksheets();
                        unset($spreadsheet);
                        unset($sheet);
                        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

                        if ($this->getSheetFromAdmin && $this->getSheetFromAdmin > 0) {
                            $sheet = $spreadsheet->getSheet($this->getSheetFromAdmin - 1 >= $max_sheet ? 0 : $this->getSheetFromAdmin - 1);
                        } else {
                            $sheet = $spreadsheet->getActiveSheet();
                        }
                    }

                    $tblStyles = $this->getStyleColumns($sheet, $maxColIndex, $ri, $tblStyles);

                    $height = $sheet->getRowDimension($ri)->getRowHeight();
                    $tblStyles['rows']->{$ri - 1}[0] = $ri - 1;
                    if ($height === -1) {
                        $height = 18;
                    }

                    $tblStyles['rows']->{$ri - 1}[1]['height'] = floor($height * 1.333333); ////1 point = 1.333333 px
                }
//                foreach ($sheet->getColumnDimensions() as $cd) {
////                    error_log(json_encode(array('123' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cd->getColumnIndex()) - 1, $cd->getWidth())));
//                    if ($maxColIndex > \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cd->getColumnIndex()) - 1) {
//                        $tblStyles['cols']->{\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cd->getColumnIndex()) - 1}[1]['width'] = $cd->getWidth() * 10; //Excel unit: number of characters that can be displayed with the standard font
//                    }
//                }
            }

            if (isset($tblStyles['cell'])) {
                $count = count($tblStyles['cell']);
                $new_cell_style = array();
                for ($i = 0; $i < $count; $i++) {
                    $r = (int)($i / $maxColIndex);
                    $c = $i % $maxColIndex;
                    $key = ($r + 1) . '|' . ($c + 1);
                    if (isset($tblStyles['cell'][$key][4])) {
                        if (isset($tblStyles['cell'][($tblStyles['cell'][$key][0] - 1) . '|' . $tblStyles['cell'][$key][2]])) {
                            $row_before = $tblStyles['cell'][($tblStyles['cell'][$key][0] - 1) . '|' . $tblStyles['cell'][$key][2]];
                            if ($row_before[3] === $tblStyles['cell'][$key][3]) {
                                if (!isset($row_before[4])) {
                                    $row_before = $tblStyles['cell'][$row_before[0] . '|' . $row_before[2]];
                                }

                                if (!empty($row_before[4]) && $tblStyles['cell'][$key][4] === $row_before[4]) {
                                    $new_cell_style[$row_before[0] . '|' . $row_before[2]][1] = $tblStyles['cell'][$key][1];
                                    $tblStyles['cell'][$row_before[0] . '|' . $row_before[2]][1] = $tblStyles['cell'][$key][1];
                                    $tblStyles['cell'][$key][0] = $row_before[0];
                                    $tblStyles['cell'][$key][1] = $row_before[2];
                                    $tblStyles['cell'][$key][2] = $row_before[2];
                                    $tblStyles['cell'][$key][3] = $row_before[3];
                                    unset($tblStyles['cell'][$key][4]);
                                }
                            }
                        }
                        if (isset($tblStyles['cell'][$key][4])) {
                            $new_cell_style[$key] = $tblStyles['cell'][$key];
                        }
                    }
                }
                $tblStyles['cell'] = $new_cell_style;
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $this->error_message = $e->getMessage();

            return $e->getMessage();
        }

        //Read Merged Cells info
        $mergeSettings = array();

        if (!isset($sheet)) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

            if ($this->getSheetFromAdmin && $this->getSheetFromAdmin > 0) {
                $max_sheet = $spreadsheet->getSheetCount();
                $sheet = $spreadsheet->getSheet($this->getSheetFromAdmin - 1 >= $max_sheet ? 0 : $this->getSheetFromAdmin - 1);
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }
        }

        $mergeRanges = $sheet->getMergeCells();

        if (count($mergeRanges)) {
            $mergeSettings = $this->getMergeCell($mergeRanges);
        }

        //disconnect worksheets
        $hyperlinks = $this->getHyperlinkFromgg($file, 0, $sheet);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unset($sheet);

//        var_dump($sheet->getActiveSheet()->getHyperlinkCollection());

        $tableContent = array(
            'data' => $datas,
            'style' => $tblStyles,
            'mergeSetting' => $mergeSettings,
            'typeCells' => $cell_type,
            'hyperlink' => $hyperlinks
        );

        return array('data' => $tableContent, 'status' => true);
    }

    /**
     * Function get style by columns
     *
     * @param object  $sheet       Spreadsheet->getActiveSheet();
     * @param integer $maxColIndex Number column
     * @param integer $ri          Row id
     * @param array   $tblStyles   Table style
     *
     * @return array
     */
    public function getStyleColumns($sheet, $maxColIndex, $ri, $tblStyles)
    {
        for ($ci = 1; $ci <= $maxColIndex; $ci++) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
            $cellStyle = $sheet->getStyle($column . ($ri));

//                        $formatCode[$ci] = array();
//                        $formatCode[$ci][$ri] = $cellStyle->getNumberFormat()->getFormatCode();
            /*get style for cell*/
            $tblStyles['cell'][$ri . '|' . $ci] = array();
            array_push($tblStyles['cell'][$ri . '|' . $ci], $ri, $ri, $ci, $ci);
            $styleFromGetStyle = $this->createCSSStyleExel($cellStyle);

            array_push($tblStyles['cell'][$ri . '|' . $ci], $styleFromGetStyle);

            if ($ci + $ci > 2) {
                $start_row1 = $ri;
                $start_col1 = isset($tblStyles['cell'][$ri . '|' . ($ci - 1)]) ? $ci - 1 : -1;

                if ($start_col1 !== -1 && isset($tblStyles['cell'][$start_row1 . '|' . $start_col1])) {
                    if (!isset($tblStyles['cell'][$start_row1 . '|' . $start_col1][4])) {
                        $start_row1 = $tblStyles['cell'][$start_row1 . '|' . $start_col1][0];
                        $start_col1 = $tblStyles['cell'][$start_row1 . '|' . $start_col1][2];
                    }
                    if ($tblStyles['cell'][$start_row1 . '|' . $start_col1][4] === $tblStyles['cell'][$ri . '|' . $ci][4]) {
                        $tblStyles['cell'][$start_row1 . '|' . $start_col1][1] = $tblStyles['cell'][$start_row1 . '|' . $start_col1][1] < $tblStyles['cell'][$ri . '|' . $ci][1] ? $tblStyles['cell'][$ri . '|' . $ci][1] : $tblStyles['cell'][$start_row1 . '|' . $start_col1][1];
                        $tblStyles['cell'][$start_row1 . '|' . $start_col1][3] = $tblStyles['cell'][$start_row1 . '|' . $start_col1][3] < $tblStyles['cell'][$ri . '|' . $ci][3] ? $tblStyles['cell'][$ri . '|' . $ci][3] : $tblStyles['cell'][$start_row1 . '|' . $start_col1][3];
                        $tblStyles['cell'][$ri . '|' . $ci][0] = $tblStyles['cell'][$start_row1 . '|' . $start_col1][0];
                        $tblStyles['cell'][$ri . '|' . $ci][1] = $tblStyles['cell'][$start_row1 . '|' . $start_col1][1];
                        $tblStyles['cell'][$ri . '|' . $ci][2] = $tblStyles['cell'][$start_row1 . '|' . $start_col1][2];
                        $tblStyles['cell'][$ri . '|' . $ci][3] = $tblStyles['cell'][$start_row1 . '|' . $start_col1][3];
                        unset($tblStyles['cell'][$ri . '|' . $ci][4]);
                    }
                }
            }

            if (!isset($tblStyles['cols']->{$ci - 1}[1]['width'])) {
                $tblStyles['cols']->{$ci - 1}[0] = $ci - 1;
                $width = $sheet->getColumnDimension($column)->getWidth();
                $tblStyles['cols']->{$ci - 1}[1]['width'] = $width * 10;

                if ($width < 0) {//error when reading the column width
                    $width = \PhpOffice\PhpSpreadsheet\Shared\Xls::sizeCol($sheet, $column);
                    $tblStyles['cols']->{$ci - 1}[1]['width'] = $width;
                }

                if ($width < 0) {
                    $tblStyles['cols']->{$ci - 1}[1]['width'] = 100;
                }
            }
        }
        return $tblStyles;
    }

    /**
     * Add hyperlinks to table
     *
     * @param array $tableContent Data of table
     * @param array $hyperlinks   Hyperlinks data of table
     *
     * @return mixed
     */
    public function changeHyperlinksTable($tableContent, $hyperlinks)
    {
        if (!$tableContent) {
            $tableContent = array();
        }
        foreach ($hyperlinks as $key => $hyperlink) {
            $keyArray = explode('!', $key);
            if (!in_array(array($keyArray[0], $keyArray[1], 1, 1, 'html'), $tableContent)) {
                $tableContent[] = array($keyArray[0], $keyArray[1], 1, 1, 'html');
            }
        }

        return $tableContent;
    }

    /**
     * Function get Data file .csv From Url
     *
     * @param string $spreadsheet_url Url
     * @param string $original_url    Original url
     *
     * @return string|boolean|array
     */
    public function getCsvDataFromUrl($spreadsheet_url, $original_url)
    {
        $listFileDownload = array();

        //download csv is first
        $csvFile = $this->downloadFileExcel($spreadsheet_url);
        if ($csvFile) {
            $handle = fopen($csvFile, 'r');
            if ($handle !== false) {
                $listFileDownload['csv'] = $csvFile;
            }
            fclose($handle);
        }

        if (preg_match('/sharing/i', substr($original_url, -8, 8)) === 1) {//link sharing
            $sharing = true;
            $xlsxFile = $this->downloadFileExcel($spreadsheet_url, '.xlsx');
            if ($xlsxFile) {
                $handle = fopen($xlsxFile, 'r');
                if ($handle !== false) {
                    $listFileDownload['xlsx'] = $xlsxFile;
                }
                fclose($handle);

                return $listFileDownload;
            }
        }

        if (!$csvFile && (!isset($xlsxFile) || !$xlsxFile)) {
            $this->error_message = __('Wrong file extension', 'wptm');
            return false;
        }

        if (isset($listFileDownload['csv'])) {
            $spreadsheet_url = str_replace('output=csv', 'output=xlsx', $spreadsheet_url);
            $file = $this->downloadFileExcel($spreadsheet_url);

            /*if cann't get file from gg (first time)*/
            if ($file === false) {
                $url_query = parse_url($original_url, PHP_URL_QUERY);
                $url_arr = explode('/', $original_url);
                array_pop($url_arr);
                $csv_url = implode('/', $url_arr);

                if (!empty($url_query)) {
                    parse_str($url_query, $url_query_params);
                    if (isset($url_query_params['gid'])) {
                        $csv_url .= '/export?format=xlsx&id=' . $url_query_params['gid'];
                    }
                }

                $file = $this->downloadFileExcel($csv_url);
            }

            if ($file !== false) {
                $listFileDownload['xlsx'] = $file;
            }

            return $listFileDownload;
        } else {
            $this->error_message = __('Wrong file extension', 'wptm');
            return false;
        }
    }

    /**
     * Convert cell data by format
     *
     * @param string|integer|float $value               Cell value
     * @param string               $decimalSeparator    Decimal separator
     * @param string               $thousandsSeparator  Thousands separator
     * @param string               $decimalNumber       Decimal number
     * @param string               $currencies          Currencies
     * @param string               $currencies_position Currencies position
     *
     * @return boolean|mixed|string
     */
    public function convertDataCellByFormat($value, $decimalSeparator, $thousandsSeparator, $decimalNumber, $currencies, $currencies_position)
    {
        $oldValue = $value;
        $currencies = !$currencies ? '' : $currencies;
        if (!is_numeric($value)) {
            $value = str_replace($currencies, '', $value);
            if (!is_numeric($value)) {
                return $oldValue;
            }
        }

        if (is_string($value)) {
            $value = str_replace($thousandsSeparator, '', $value);
            if ($value === '') {
                return $oldValue;
            }
        }
        $value = number_format($value, $decimalNumber, $decimalSeparator, $thousandsSeparator);

        if ($currencies !== '') {
            $value = $currencies_position === '0' ? ($currencies . $value) : ($value . $currencies);
        }
        return $value;
    }

    /**
     * Function get file xls from spreadsheet
     *
     * @param string $url_arr Url
     *
     * @return boolean|string
     */
    public function getFilexlsFromgg($url_arr)
    {
        $datafile = $this->getDataFromUrl($url_arr);

        if ($datafile === null) {
            return false;
        }
        $upload_dir = wp_upload_dir();
        $targetPath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (file_exists($targetPath)) {
            $targetFile = $targetPath . uniqid() . '.xlsx';
        } else {
            return false;
        }
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        global $wp_filesystem;
        WP_Filesystem(); // Initial WP file system
        $wp_filesystem->put_contents(
            $targetFile,
            $datafile,
            FS_CHMOD_FILE // predefined mode settings for WP files
        );

        return $targetFile;
    }

    /**
     * Funciton get Hyperlink from file xls getSheet(0) will get a sheet first
     *
     * @param string  $targetFile Url file
     * @param integer $check      Check delete file xls
     * @param object  $sheet      Sheet object
     *
     * @return array|boolean
     */
    public function getHyperlinkFromgg($targetFile, $check, $sheet)
    {
        if (!is_readable($targetFile)) {
            return array(
                'text' => __('This file is not readable', 'wptm'),
                'file' => basename($targetFile),
                'status' => false
            );
        }

        $maxCell = $sheet->getHighestRowAndColumn();
        $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxCell['column']);

        $styletable = array();
        $pattern = '@^=(HYPERLINK)\\((.*?)\\)$@';
        $pattern2 = '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#';
        $maxCells = (int)$maxCell['row'] * $maxColIndex;
        for ($ri = 1; $ri <= $maxCell['row']; $ri++) {
            for ($ci = 1; $ci <= $maxColIndex; $ci++) {
                $indexCell = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci) . $ri);
                $cell = $indexCell->getValue();
                $cell = $cell ? $cell : '';
                if ($cell !== '' && preg_match($pattern, $cell, $matches)) {
                    $cells = explode(',', $matches[2]);
                    preg_match_all(
                        "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
                        $cells[0],
                        $val
                    );
                    if (!empty($val[0])) {
                        $styletable[($ri - 1) . '!' . ($ci - 1)] = array();
                        $styletable[($ri - 1) . '!' . ($ci - 1)]['hyperlink'] = $val[0][0];
                        $styletable[($ri - 1) . '!' . ($ci - 1)]['text'] = preg_replace('/"/', '', $cells[1]);
                    }
                }

                if ($indexCell->hasHyperlink()) {
                    $getHyperlink = $indexCell->getHyperlink()->getUrl();
                    if ($getHyperlink !== '' && !isset($styletable[($ri - 1) . '!' . ($ci - 1)])) {
                        $styletable[($ri - 1) . '!' . ($ci - 1)] = array();
                        $styletable[($ri - 1) . '!' . ($ci - 1)]['hyperlink'] = $getHyperlink;
                        $styletable[($ri - 1) . '!' . ($ci - 1)]['text'] = $cell;
                    }
                }
            }
        }

        if ($check === 1) {
            unlink($targetFile);
        }

        return $styletable;
    }

    /**
     * Function download file from oneDrive
     *
     * @param string $url      Url
     * @param string $id_table Table id
     *
     * @return boolean|string
     */
    public function downloadFileOneDrive($url, $id_table)
    {
        $upload_dir = wp_upload_dir();

        $targetPath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
            $data = '<html><body bgcolor="#FFFFFF"></body></html>';
            $file = fopen($targetPath . 'index.html', 'w');
            fwrite($file, $data);
            fclose($file);
        }
        $extension = '.xlsx';

        $targetFile = $targetPath . $id_table . '_' . uniqid() . $extension;

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (!function_exists('wp_generate_password')) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
        }

        $tmp_file = download_url($url);

        if (is_wp_error($tmp_file)) {
            return false;
        }

        if (!file_exists($tmp_file)) {
            $this->error_message = __('Cannot download file', 'wptm');
            return false;
        } else {
            copy($tmp_file, $targetFile);
            unlink($tmp_file);
        }
        return $targetFile;
    }

    /**
     * Function download file
     *
     * @param string $url       Url
     * @param string $extension File extension
     *
     * @return boolean|string
     */
    public function downloadFileExcel($url, $extension = '')
    {
        //check file extension
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $newname = uniqid() . '.' . $ext;
        if (strpos($url, 'docs.google.com/spreadsheet') !== false) {
            if ($extension === '') {
                $newname = uniqid() . '.csv';
            } else {
                $newname = uniqid();
            }
        } elseif (!in_array($ext, $this->allowed_ext)) {
            $this->error_message = __('Wrong file extension', 'wptm');
            return false;
        }

        $upload_dir = wp_upload_dir();

        $targetPath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
            $data = '<html><body bgcolor="#FFFFFF"></body></html>';
            $file = fopen($targetPath . 'index.html', 'w');
            fwrite($file, $data);
            fclose($file);
        }
        $targetFile = $targetPath . $newname . $extension;

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (!function_exists('wp_generate_password')) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
        }

        $tmp_file = download_url($url);

        if (is_wp_error($tmp_file)) {
            $this->error_message = __('There is an error when download the file', 'wptm') ;
            return false;
        }

        if (!file_exists($tmp_file)) {
            $this->error_message = __('Cannot download file', 'wptm');
            return false;
        } else {
            copy($tmp_file, $targetFile);
            unlink($tmp_file);
        }

        return $targetFile;
    }

    /**
     * Function get data from url
     *
     * @param string $url Url
     *
     * @return mixed|null
     * @throws Exception Error when get data
     */
    public function getDataFromUrl($url)
    {
        $ch = curl_init();
        $timeout = 10;
        $agent = 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_REFERER, site_url());
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $data = curl_exec($ch);
        if (curl_error($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new Exception($error);
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        $statusCode = (string)$info['http_code'];

        if ($statusCode[0] === '2') {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * Function csvToArray
     *
     * @param string $csv Cvs
     *
     * @return array
     */
    public function csvToArray($csv)
    {
        $arr = array();
        $lines = explode("\n", $csv);
        foreach ($lines as $row) {
            $row = str_replace('""', '\\"', $row);
            $arr[] = str_getcsv($row, ',');
        }

        return $arr;
    }

    /**
     * Get a model
     *
     * @param string|null $modelname Model name
     *
     * @return boolean|object
     */
    public function getModel($modelname = null)
    {
        $modelname = preg_replace('/[^A-Z0-9_-]/i', '', $modelname);
        $filepath = Factory::getApplication()->getPath() . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . strtolower($modelname) . '.php';
        if (!file_exists($filepath)) {
            return false;
        }
        include_once $filepath;
        $class = Factory::getApplication()->getName() . 'Model' . $modelname;
        $model = new $class();
        return $model;
    }
}
