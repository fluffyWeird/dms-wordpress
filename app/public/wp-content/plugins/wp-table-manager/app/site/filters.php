<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WP_Table_Manager\Admin\Helpers\WptmTablesHelper;
use Joomunited\WPFramework\v1_0_6\Filter;
use Joomunited\WPFramework\v1_0_6\Model;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Application;

defined('ABSPATH') || die();

/**
 * Class wptmFilter
 */
class WptmFilter extends Filter
{
    /**
     * Var style highlight cell
     *
     * @var string
     */
    private $hightLightCss = '';
    /**
     * Var admin/site
     *
     * @var string
     */
    private $getType = '';

    /**
     * Var check use first row/column as graph
     *
     * @var string
     */
    private $useFirstRowAsGraph = '';

    /**
     * Var check load file .css and .js by wp_enqueue
     *
     * @var boolean
     */
    private $useEnqueueFile = true;

    /**
     * Var check load file .css and .js in gutenberg block
     *
     * @var boolean
     */
    private $gutenbergBlock = false;

    /**
     * Var table hash
     *
     * @var string
     */
    private $hash = '';

    /**
     * Function load shortcode
     *
     * @return void
     */
    public function load()
    {
        add_filter('the_content', array($this, 'wptmReplaceContent'), 9);
        add_filter('themify_builder_module_content', array($this, 'themifyModuleContent'));

        // acf_pro filter for every value load
        add_filter('acf/load_value', array($this, 'wptmAcfLoadValue'), 10, 3);
        // Register our shortcode
        add_shortcode('wptm', array($this, 'applyShortcode'));
    }

    /**
     * Return content of our shortcode
     *
     * @param array $args Data of chart/table
     *
     * @return string
     */
    public function applyShortcode($args = array())
    {
        $html = '';
        if (isset($args['id']) && !empty($args['id'])) {
            $this->useEnqueueFile = isset($args['useEnqueueFile']) ? ($args['useEnqueueFile'] === 1 ? true : false) : true;
            if (isset($args['range'])) {
                $html = $this->replaceShortCode(false, $args);
            } else {
                $id_table = $args['id'];
                $html = $this->replaceTable($id_table);
            }
        } elseif (isset($args['id-chart']) && !empty($args['id-chart'])) {
            $id_chart = $args['id-chart'];
            $html = $this->replaceChart($id_chart);
        }

        return $html;
    }

    /**
     * Function acf filter to replace table holder-place
     *
     * @param mixed   $value   Content of table
     * @param integer $post_id Id of post
     * @param string  $field   Field
     *
     * @return mixed
     */
    public function wptmAcfLoadValue($value, $post_id, $field)
    {
        if (is_string($value)) {
            $value = $this->wptmReplaceContent($value);
        }
        return $value;
    }

    /**
     * Get function wptmReplaceContent
     *
     * @param string $content Strings to search and replace
     *
     * @return mixed
     */
    public function themifyModuleContent($content)
    {
        $content = $this->wptmReplaceContent($content);
        return $content;
    }

    /**
     * Function replace
     *
     * @param string $content Strings to search and replace
     *
     * @return mixed
     */
    public function wptmReplaceContent($content)
    {
        $content = preg_replace_callback('@<img[^>]*?data\-wptmtable="([0-9]+)".*?/?>@', array(
            $this,
            'replace'
        ), $content);

        return $content;
    }

    /**
     * Get table Html Content
     *
     * @param object     $table         Table object
     * @param boolean    $getData       Get table datas
     * @param array      $data_style    Style value
     * @param array      $rangeColsNull List min, max value of columns
     * @param array|null $argsShortCode Range cell render
     * @param string     $shortCodeHash Hash of short code
     * @param boolean    $readHtmlFile  Write table content to html file
     *
     * @return string|boolean
     */
    public function getTableContent($table, $getData, $data_style, &$rangeColsNull, $argsShortCode = null, $shortCodeHash = '', $readHtmlFile = true)
    {
        $params = $table->params;

        require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
        $wptmHelper = new WptmHelper();

        Application::getInstance('Wptm');
        /* @var WptmModelConfigsite $configModel */
        $configModel = $this->getModel('configsite');
        $configParms = $configModel->getConfig();
        if (!($this->hash !== '' && ($this->hash === $table->id . '_' . $table->hash)
            && ((isset($params->table_type) && $params->table_type === 'html') || $table->type === 'html'))) {
            if (!empty($data_style['data'])) {
                $valueTable = $wptmHelper->htmlRender($table, $configParms, $data_style['data'], $table->hash, $getData, $argsShortCode, $this->gutenbergBlock, $readHtmlFile);
            } else {
                $valueTable = $wptmHelper->htmlRender($table, $configParms, array(), $table->hash, $getData, $argsShortCode, $this->gutenbergBlock, $readHtmlFile);
            }
        }

        $rangeColsNull = $wptmHelper->rangeColsNull;

        if ($readHtmlFile && ((isset($params->table_type) && $params->table_type === 'html') || $table->type === 'html')) {
            $folder = wp_upload_dir();
            $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
            $file = $folder . ($shortCodeHash === '' ? '' : ($shortCodeHash . '_'))  . $table->id . '_' . $table->hash . '.html';

            return file_get_contents($file);
        } elseif (isset($valueTable) && is_string($valueTable) && $valueTable !== '') {
            return $valueTable;
        } else {
            return __('table is empty', 'wptm');
        }

        return false;
    }

    /**
     * Get function replaceChart/replaceTable
     *
     * @param array $match Data table
     *
     * @return string
     */
    private function replace($match)
    {
        $id_table = $match[1];
        $exp = '@<img.*data\-wptm\-chart="([0-9]+)".*?>@';
        preg_match($exp, $match[0], $matches);
        if (count($matches) > 0) { //is a chart
            $id_chart = $matches[1];
            $content = $this->replaceChart($id_chart);
        } else {  //is a table
            $content = $this->replaceTable($id_table);
        }

        return $content;
    }

    /**
     * Render html from short-code
     *
     * @param boolean    $checkElementor_preview Check elementor preview
     * @param array|null $argsShortCode          Range cell render
     *
     * @return string
     */
    public function replaceShortCode($checkElementor_preview = false, $argsShortCode = null)
    {
        Application::getInstance('Wptm');
        if ($checkElementor_preview) {
            $this->getType = 'site';
        }
        $upload_url = wp_upload_dir();
        if (is_ssl()) {
            $upload_url['baseurl'] = str_replace('http://', 'https://', $upload_url['baseurl']);
        }
        $upload_url = $upload_url['baseurl'] . '/wptm/';

        /* @var WptmModelConfigsite $modelConfig */
        $modelConfig = $this->getModel('configSite');

        $configParams = $modelConfig->getConfig();
        /* @var WptmModelTable $model */
        $model = $this->getModel('table');

        if (!($argsShortCode !== null && !empty($argsShortCode['id']) && !empty($argsShortCode['range']))) {
            return '';
        }

        $table = $model->getItem($argsShortCode['id'], true, true, null, false);

        if (!$table) {
            return '';
        }

        $style = $table->style;

        $hightLight = !isset($configParams['enable_hightlight']) ? 0 : (int)$configParams['enable_hightlight'];
        $table->hightlight_color = !isset($configParams['tree_hightlight_color']) ? '#ffffaa' : $configParams['tree_hightlight_color'];
        $table->hightlight_font_color = !isset($configParams['tree_hightlight_font_color']) ? '#ffffff' : $configParams['tree_hightlight_font_color'];
        $table->hightlight_opacity = !isset($configParams['hightlight_opacity']) ? '0.9' : $configParams['hightlight_opacity'];
        if (isset($style->table->fonts_used) && count($style->table->fonts_used) > 0) {
            foreach ($style->table->fonts_used as $fontsUsed) {
                if ($fontsUsed !== '') {
                    global $wpdb;
                    $nameFont = str_replace(' ', '_', $fontsUsed);
                    $result = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.option_name = %s', 'google_font' . $nameFont));
                    $url = '';
                    if (empty($result)) {
                        $urlGoogle = 'https://fonts.googleapis.com/css?family=' . $fontsUsed;
                        wp_enqueue_style('wptm-google-fonts-fe' . $nameFont, $urlGoogle);
//                        $googleFonts = WptmTablesHelper::downloadFontGoogle($fontsUsed);
//                        $url = $nameFont . '_google_fonts.css';
                    } else {
                        $url = $result->option_value;
                        wp_enqueue_style('wptm-google-fonts-fe' . $nameFont, $upload_url . $url);
                    }
                }
            }
        }
        if (isset($style->table->fonts_local_used) && count($style->table->fonts_local_used) > 0) {
            //local font

            require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
            $wptmHelper = new WptmHelper();
            $localFonts = $wptmHelper->getlocalfont();
            $localFontString = '';
            if (isset($localFonts) && count($localFonts) > 0) {
                foreach ($localFonts as $key => $localFont) {
                    if (isset($localFont->urc) && in_array($localFont->data[0]->name_font, $style->table->fonts_local_used)) {
                        $localFontString .= $localFont->urc;
                    }
                }
            }
        }
        $responsive_type = 'scroll';

        /*button download table*/
        if (isset($argsShortCode['download']) && (int)$argsShortCode['download'] === 1) {
            $style->table->download_button = 1;
        }

        $style->table->align = 'left';
        if (isset($argsShortCode['align'])) {
            $style->table->align = $argsShortCode['align'];
        }


        /*sort and filter*/
        $filterOptions = new stdClass();
        if (isset($argsShortCode['filter']) && (int)$argsShortCode['filter'] === 1) {
            $filterOptions->enable_filters = 1;
        } else {
            $filterOptions->enable_filters = 0;
        }
        $filterOptions->enable_filters_all = 0;

        if (isset($argsShortCode['sort']) && $argsShortCode['sort'] > 0) {
            $filterOptions->use_sortable = 1;
            $filterOptions->default_order_sortable = isset($argsShortCode['sortorder']) ? (int)$argsShortCode['sortorder'] : 0;
            $filterOptions->default_sortable = isset($argsShortCode['sortcolumn']) ? (int)$argsShortCode['sortcolumn'] : 0;
        } else {
            $filterOptions->use_sortable = 0;
            $filterOptions->default_order_sortable = 0;
            $filterOptions->default_sortable = 0;
        }
        $table->sortFilters = $filterOptions;


        //create hash
        $shortCodeHash = hash('md5', json_encode($argsShortCode));
        $data_style = $this->styleRender($table, $this->getType, $argsShortCode, $shortCodeHash);

        if (isset($table->datas) && $table->datas !== null && !empty($table->datas)) {
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }
            wp_enqueue_script('jquery');

            // hightlight
            if ($hightLight !== 1) {
                $table->hightlight_color = 'not hightlight';
            }

            $content = '';
            if ($this->getType === '' && $this->useEnqueueFile) {
                wp_enqueue_script('jquery-wptm-moment', plugins_url('assets/js/moment.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_script('jquery-wptm-jdateformatparser', plugins_url('assets/js/moment-jdateformatparser.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_script('wptm-accounting', plugins_url('app/admin/assets/plugins/accounting.js', WPTM_PLUGIN_FILE), array(), false, 'all');

                wp_enqueue_script('wptm_datatables_js', plugins_url('assets/DataTables/datatables' . $min . '.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_script('wptm_datatables_fixedColumns_js', plugins_url('assets/DataTables/dataTables.fixedColumns.js', __FILE__), array(), WPTM_VERSION, true);
                //wp_enqueue_script('wptm_button_js', plugins_url('assets/DataTables/buttons.print.js', __FILE__), array(), WPTM_VERSION, true);
                //wp_enqueue_script('wptm_datatables_button_js', plugins_url('assets/DataTables/dataTables.buttons.js', __FILE__), array(), WPTM_VERSION, true);

                wp_enqueue_script('jquery-fileDownload', plugins_url('assets/js/jquery.fileDownload.js', __FILE__), array(), WPTM_VERSION);

                /* add tipso lib when tooltip cell exists*/
                wp_enqueue_script('wptm_tipso', plugins_url('assets/tipso/tipso' . $min . '.js', __FILE__), array(), WPTM_VERSION, true);

                wp_enqueue_script('wptm_table_range', plugins_url('assets/js/wptm_front_range.js', __FILE__), array(), WPTM_VERSION, true);
            }

            $content .= '<div class="wptm_table tablesorter-bootstrap wptm_range_' . $shortCodeHash . '" data-id="' . ($argsShortCode['range'] === '' ? '' : ($shortCodeHash . '_')) . (int)$table->id . '" data-hightlight="' . $hightLight . '">';

            /*button download table*/
            if (isset($style->table->download_button) && $style->table->download_button) {
                $app = Application::getInstance('Wptm');
                $content .= '<input type="button" data-href="' . $app->getAjaxUrl() . '" href="javascript:void(0);" class="download_wptm" value="' . esc_attr__('Download Table', 'wptm') . '"/>';
            }

            $tableContent = $this->getTableContent($table, true, $data_style, $rangeColsNull, $argsShortCode, $shortCodeHash);

            if ($tableContent) {
                $content .= $tableContent;
            }
            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $content = html_entity_decode($content, ENT_COMPAT | ENT_HTML401, 'UTF-8');

            $content .= '<script>wptm_ajaxurl = \'' . esc_url_raw(Factory::getApplication('wptm')->getAjaxUrl()) . '\';</script>';

            if (isset($localFontString)) {
                $content .= '</div><style>' . $this->hightLightCss . ' ' . stripslashes_deep($localFontString) . '</style>';
            } else {
                $content .= '</div><style>' . $this->hightLightCss . '</style>';
            }
        }

        if ($this->getType === 'site' || !$this->useEnqueueFile) {
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm-table-' . ($argsShortCode['range'] === '' ? '' : ($shortCodeHash . '_')) . $table->id . '" href="' . $upload_url . ($argsShortCode['range'] === '' ? '' : ($shortCodeHash . '_')) . $table->id . '_' . $table->hash . '.css" media="all">';
        }

        if (!$this->useEnqueueFile) {
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm-front" href="' . plugins_url('assets/css/front.css', __FILE__) . '?ver=' . WPTM_VERSION . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm_datatables" href="' . plugins_url('assets/DataTables/datatables' . $min . '.css', __FILE__) . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm_tipso" href="' . plugins_url('assets/tipso/tipso' . $min . '.css', __FILE__) . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/moment.js', __FILE__) . '" id="jquery-wptm-moment"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/moment-jdateformatparser.js', __FILE__) . '" id="jquery-wptm-jdateformatparser"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/DataTables/datatables' . $min . '.js', __FILE__) . '" id="wptm_datatables_js"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/DataTables/dataTables.fixedColumns.js', __FILE__) . '" id="wptm_datatables_jdateformatparser_js"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/jquery.fileDownload.js', __FILE__) . '" id="jquery-fileDownload"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/tipso/tipso' . $min . '.js', __FILE__) . '" id="wptm_tipso"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/wptm_front_range.js', __FILE__) . '?ver=' . WPTM_VERSION . '" id="wptm_table"></script>';
        }

        $content = do_shortcode($content);

        if ($checkElementor_preview) {
            return array('content' => $content, 'name' => $table->title);
        }
        return $content;
    }

    /**
     * Create table(front_end)
     *
     * @param integer $id_table               Id of table
     * @param boolean $checkElementor_preview Check elementor preview
     *
     * @return string|boolean|array
     */
    public function replaceTable($id_table, $checkElementor_preview = false)
    {
        Application::getInstance('Wptm');
        if ($checkElementor_preview) {
            $this->getType = 'site';
        }
        $upload_url = wp_upload_dir();
        if (is_ssl()) {
            $upload_url['baseurl'] = str_replace('http://', 'https://', $upload_url['baseurl']);
        }
        $upload_url = $upload_url['baseurl'] . '/wptm/';

        /* @var WptmModelConfigsite $modelConfig */
        $modelConfig = $this->getModel('configSite');

        $configParams = $modelConfig->getConfig();
        /* @var WptmModelTable $model */
        $model = $this->getModel('table');

        $getData = true;

        $usingAjaxLoading = $model->needAjaxLoad($id_table);
        if ($usingAjaxLoading) {
            $getData = false;
        }

        $table = $model->getItem($id_table, $getData, true, null, $usingAjaxLoading);

        if (!$table) {
            return '';
        }

        $style = $table->style;

        $hightLight = !isset($configParams['enable_hightlight']) ? 0 : (int)$configParams['enable_hightlight'];
        $table->hightlight_color = !isset($configParams['tree_hightlight_color']) ? '#ffffaa' : $configParams['tree_hightlight_color'];
        $table->hightlight_font_color = !isset($configParams['tree_hightlight_font_color']) ? '#ffffff' : $configParams['tree_hightlight_font_color'];
        $table->hightlight_opacity = !isset($configParams['hightlight_opacity']) ? '0.9' : $configParams['hightlight_opacity'];
        $enable_pagination = isset($style->table->enable_pagination) ? (int)$style->table->enable_pagination : 0;

        if (!isset($style->table) || count((array)$style->table) < 1) {
            $style->table = new stdClass();
        }

        if (!isset($style->table->freeze_col)) {
            $style->table->freeze_col = 0;
        }
        if (!isset($style->table->freeze_row)) {
            $style->table->freeze_row = 0;
        }

        /*sort and filter*/
        $filterOptions = new stdClass();
        if (!isset($style->table->enable_filters)) {
            $filterOptions->enable_filters = 0;
        } else {
            $filterOptions->enable_filters = (int)$style->table->enable_filters;
        }
        if (!isset($style->table->enable_filters_all)) {
            $filterOptions->enable_filters_all = 0;
        } else {
            $filterOptions->enable_filters_all = (int)$style->table->enable_filters_all;
        }
        if (!isset($style->table->use_sortable)) {
            $filterOptions->use_sortable = 0;
        } else {
            $filterOptions->use_sortable = (int)$style->table->use_sortable;
        }
        $filterOptions->default_order_sortable = isset($style->table->default_order_sortable) ? (int)$style->table->default_order_sortable : 0;
        $filterOptions->default_sortable = isset($style->table->default_sortable) ? (int)$style->table->default_sortable : 0;

        $filterOptions->cols = array();
        foreach ($style->cols as $col => $option) {
            if (is_object($option) && isset($option->{1})) {
                $option1 = $option->{1};
                $index = $option->{0};
            } elseif (is_array($option) && isset($option[1])) {
                $option1 = $option[1];
                $index = $option[0];
            }
            if (isset($option1->searchContentType) && !(isset($option1->hide_column) && (int)$option1->hide_column === 1)) {
                $datasCol = new stdClass();
                $datasCol->searchContentType = $option1->searchContentType;
                $datasCol->sortable = isset($option1->sortable) ? $option1->sortable : 1;
                $datasCol->filter = isset($option1->filter) ? $option1->filter : 1;
                $datasCol->min = isset($option1->min) ? $option1->min : 1;
                $datasCol->max = isset($option1->max) ? $option1->max : 10000;
                $datasCol->searchType = isset($option1->searchType) ? $option1->searchType : 'none';
                $datasCol->enable_filters_label = isset($option1->enable_filters_label) ? $option1->enable_filters_label : '';
                $datasCol->checkBoxContent = array();
                if (isset($option1->checkBoxContent) && $option1->checkBoxContent !== '') {
                    $datasCol->checkBoxContent = explode('|', $option1->checkBoxContent);
                }
                $datasCol->index = $index;
                $filterOptions->cols[] = $datasCol;
            }
        }
        $filterOptions->submit_form = isset($style->table->submit_form) ? (int)$style->table->submit_form : 0;
        $filterOptions->clear_form = isset($style->table->clear_form) ? (int)$style->table->clear_form : 0;

        $table->sortFilters = $filterOptions;

//
//        $sortable = false;
//        if (isset($style->table->use_sortable) && (int)$style->table->use_sortable === 1) {
//            $sortable = true;
//        }
//
//        /*add style for table*/
//        $table->sortable = $sortable;
//        $table->enable_filters = $style->table->enable_filters;

        $content = '';
        if (isset($style->table->fonts_used) && count($style->table->fonts_used) > 0) {
            foreach ($style->table->fonts_used as $fontsUsed) {
                if ($fontsUsed !== '') {
                    if ($fontsUsed !== '') {
                        global $wpdb;
                        $nameFont = str_replace(' ', '_', $fontsUsed);
                        $result = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.option_name = %s', 'google_font' . $nameFont));
                        $url = '';
                        if (empty($result)) {
                            $urlGoogle = 'https://fonts.googleapis.com/css?family=' . $fontsUsed;
                            wp_enqueue_style('wptm-google-fonts-fe' . $nameFont, $urlGoogle);
                        } else {
                            $url = $result->option_value;
                            wp_enqueue_style('wptm-google-fonts-fe' . $nameFont, $upload_url . $url);
                        }
                    }
                }
            }
        }

        if (isset($style->table->fonts_local_used) && count($style->table->fonts_local_used) > 0) {
            //local font
            require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
            $wptmHelper = new WptmHelper();
            $localFonts = $wptmHelper->getlocalfont();
            $localFontString = '';
            if (isset($localFonts) && count($localFonts) > 0) {
                foreach ($localFonts as $key => $localFont) {
                    if (isset($localFont->urc) && in_array($localFont->data[0]->name_font, $style->table->fonts_local_used)) {
                        $localFontString .= $localFont->urc;
                    }
                }
            }
        }

        $responsive_type = 'scroll';
        if (isset($style->table->responsive_type) && (string)$style->table->responsive_type === 'hideCols') {
            $responsive_type = 'hideCols';
        }

        //not render table content to html file when on pagination in block preview
        $readHtmlFile = $enable_pagination === 1 && $this->gutenbergBlock ? false : true;

        if (!($this->hash !== '' && ($this->hash === $table->id . '_' . $table->hash))) {
            $data_style = $this->styleRender($table, $this->getType);
        } else {
            $data_style = true;
        }

        if (isset($table->datas) && $table->datas !== null && !empty($table->datas) || $enable_pagination) {
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }

            wp_enqueue_script('jquery');

            // hightlight
            if ($hightLight !== 1) {
                $table->hightlight_color = 'not hightlight';
            }

            if ($this->getType === '' && $this->useEnqueueFile) {
                wp_enqueue_script('jquery-wptm-moment', plugins_url('assets/js/moment.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_style('wptm_daterangepicker', plugins_url('assets/css/daterangepicker.css', __FILE__), array(), WPTM_VERSION);
                wp_enqueue_script('jquery-wptm-daterangepicker', plugins_url('assets/js/daterangepicker.min.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_script('jquery-wptm-jdateformatparser', plugins_url('assets/js/moment-jdateformatparser.js', __FILE__), array(), WPTM_VERSION, true);

                wp_enqueue_script('wptm_datatables_js', plugins_url('assets/DataTables/datatables' . $min . '.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_script('wptm-accounting', plugins_url('app/admin/assets/plugins/accounting.js', WPTM_PLUGIN_FILE), array(), false, 'all');

                wp_enqueue_script('wptm_datatables_fixedColumns_js', plugins_url('assets/DataTables/dataTables.fixedColumns.js', __FILE__), array(), WPTM_VERSION, true);
                wp_enqueue_script('wptm_datatables_responsive_js', plugins_url('assets/DataTables/dataTables.responsive.js', __FILE__), array(), WPTM_VERSION, true);

                wp_enqueue_script('jquery-fileDownload', plugins_url('assets/js/jquery.fileDownload.js', __FILE__), array(), WPTM_VERSION);

                /* add tipso lib when tooltip cell exists */
                wp_enqueue_script('wptm_tipso', plugins_url('assets/tipso/tipso' . $min . '.js', __FILE__), array(), WPTM_VERSION, true);

                wp_enqueue_script('wptm_table', plugins_url('assets/js/wptm_front.js', __FILE__), array(), WPTM_VERSION, true);
//                wp_enqueue_script('wptm_table2', plugins_url('assets/js/wptm_front2.js', __FILE__), array(), WPTM_VERSION, true);
            }

            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }

            $app = Application::getInstance('Wptm');

            $buttonContent = '';

            $buttonContent .= '<div class="wptm_buttons buttons_wptmTbl' . (int) $table->id . '">';
            if (isset($style->table->print_button) && $style->table->print_button) {
                $app = Application::getInstance('Wptm');
                //$check_sortable = $sortable ? 'use_sortable' : '';
                $check_sortable = '';
                $title = $table->title ? $table->title : esc_attr('WP Table Managers Print Table', 'wptm');
                $content .= '<div class="wptm_table tablesorter-bootstrap ' . $check_sortable . '" data-id="' . (int)$table->id . '" data-print-table="1" data-hightlight="' . $hightLight . '">';

                $buttonContent .= '<button data-title="' . $title . '" data-table-id="wptmTbl' . (int) $table->id . '"  class="wptm_button wptm_print_table" ><i class="glyphicon glyphicon-print"></i>'
                    . esc_attr('Print', 'wptm') . '</button>';
            } else {
                //$check_sortable = $sortable ? 'use_sortable' : '';
                $check_sortable = '';
                $content .= '<div class="wptm_table tablesorter-bootstrap ' . $check_sortable . '" data-id="' . (int)$table->id . '" data-hightlight="' . $hightLight . '">';
            }

            /*button download table*/
            if (isset($style->table->download_button) && $style->table->download_button) {
                $buttonContent .= '<button href="javascript:void(0);" class="wptm_button download_wptm"/>' . esc_attr__('Export to Excel', 'wptm') . '</button>';
            }

            if (!empty($table->sortFilters->enable_filters_all) && $table->sortFilters->enable_filters_all === 1) {
//                if (!empty($table->sortFilters->enable_filters_all_label) && $table->sortFilters->enable_filters_all_label !== '') {
//                    $buttonContent .= '<label for="wptm_buttons_search_all" style="display: inline-block; float: left">' . esc_attr($table->sortFilters->enable_filters_all_label) . ': </label>';
//                } else {
//                    $buttonContent .= '<label for="wptm_buttons_search_all" style="display: inline-block; float: left">' . esc_attr__('Search data', 'wptm') . ': </label>';
//                }
                $buttonContent .= '<input name="wptm_buttons_search_all" class="wptm_buttons_search_all buttons_search_wptmTbl' . (int) $table->id . '" type="text" placeholder="' . esc_attr__('Search data', 'wptm') . '" />';
            }
            $buttonContent .= '</div>';

            $limit = isset($style->table->limit_rows) ? (int)$style->table->limit_rows : 0;

            $tableContent = $this->getTableContent($table, $getData, $data_style, $rangeColsNull, null, '', $readHtmlFile);
            if (!$tableContent) {
                $tableContent = '';
            }

            $formContent = '';
            //{searchContentType: 'number, text, date', searchType: 'select, input, checkBox', checkBoxContent: [value, value...]}
            if (!empty($table->sortFilters->enable_filters) && $table->sortFilters->enable_filters === 2) {
                $formContent .= $this->renderFormSearch($table->id, $table->sortFilters);
            }

            $buttonContent = $formContent . $buttonContent;

            //hook to position download and print buttons relative to table position
            $contentTableAndButton = apply_filters('wptm_set_button_position_on_frontend', '', $tableContent, $buttonContent, $table->id);

            if ($contentTableAndButton !== '') {
                $content .= $contentTableAndButton;
            } else {
                $content .= $buttonContent . $tableContent;
            }

            //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
            $content = html_entity_decode($content, ENT_COMPAT | ENT_HTML401, 'UTF-8');

            $content .= '<script>window.wptm_ajaxurl = \'' . esc_url_raw($app->getAjaxUrl()) . '\'; ';
            $content .= 'window.wptm_front = \'' . esc_url_raw(plugins_url('assets/', __FILE__)) . '\'; ';
            $content .= 'window.wptm_style = \'' . esc_url_raw($upload_url) . '\';</script>';

            if (isset($localFontString)) {
                $content .= '</div><style>' . $this->hightLightCss . ' ' . stripslashes_deep($localFontString) . '</style>';
            } else {
                $content .= '</div><style>' . $this->hightLightCss . '</style>';
            }
        }

        if ($this->getType === 'site' || !$this->useEnqueueFile) {
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm-table-' . $table->id . '" href="' . $upload_url . $table->id . '_' . $table->hash . '.css" media="all">';
            $content .= '<style>.dataTables_info {display: none}</style>';
        }

        if (!$this->useEnqueueFile) {
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm-front" href="' . plugins_url('assets/css/front.css', __FILE__) . '?ver=' . WPTM_VERSION . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm_datatables" href="' . plugins_url('assets/DataTables/datatables' . $min . '.css', __FILE__) . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm_tipso" href="' . plugins_url('assets/tipso/tipso' . $min . '.css', __FILE__) . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- add style in elementor review
            $content .= '<link rel="stylesheet" id="wptm_daterangepicker" href="' . plugins_url('assets/css/daterangepicker.css', __FILE__) . '" media="all">';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/moment.js', __FILE__) . '" id="jquery-wptm-moment"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/daterangepicker.min.js', __FILE__) . '" id="jquery-wptm-daterangepicker"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/moment-jdateformatparser.js', __FILE__) . '" id="jquery-wptm-jdateformatparser"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/DataTables/datatables' . $min . '.js', __FILE__) . '" id="wptm_datatables_js"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/DataTables/dataTables.fixedColumns.js', __FILE__) . '" id="wptm_datatables_jdateformatparser_js"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/jquery.fileDownload.js', __FILE__) . '" id="jquery-fileDownload"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/tipso/tipso' . $min . '.js', __FILE__) . '" id="wptm_tipso"></script>';
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- add style in elementor review
            $content .= '<script src="' . plugins_url('assets/js/wptm_front.js', __FILE__) . '?ver=' . WPTM_VERSION . '" id="wptm_table123"></script>';
        }

        $content = do_shortcode($content);
        if ($checkElementor_preview) {
            return $this->gutenbergBlock ? array($content, $table->id . '_' . $table->hash) : array('content' => $content, 'name' => $table->title);
        }

        return $this->gutenbergBlock ? array($content, $data_style) : $content;
    }

    /**
     * Render search form
     *
     * @param integer $id_table      Table id
     * @param object  $filterOptions Filter params
     *
     * @return string
     */
    public function renderFormSearch($id_table, $filterOptions)
    {
        //phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.random_intFound -- no support php5.6
        $randomKey = $id_table . random_int(100, 999);
        $form = '<div class="wptm_form_search wptm_form_' . (int)$id_table . '" data-random="' . $randomKey . '" data-id="' . $id_table . '">';

        //$filterOptions->searchOptions = array({enable_filters_label: 'filter label', searchContentType: 'number, text, date', searchType: 'select, input, checkBox, dateTime', checkBoxContent: [value, value...]} / null, ....)
        if (!empty($filterOptions->cols) && count($filterOptions->cols) > 0) {
            $countCols = count($filterOptions->cols);
            $jsTab = '<script>';
            $jsTab .= ' if (typeof wptmFormSearch === "undefined") {var wptmFormSearch = {};}';
            $jsTab .= 'wptmFormSearch.table' . $randomKey . ' = [];';
            $jsTab .= 'wptmFormSearch.table' . $randomKey . '.columnsInForm = [];';

            for ($columnIndex = 0; $columnIndex < $countCols; $columnIndex++) {
                $datasCol = $filterOptions->cols[$columnIndex];
                if ($datasCol !== null && $datasCol->searchType !== 'none') {
                    $jsTab .= ' wptmFormSearch.table' . $randomKey . '.columnsInForm[' . $datasCol->index . '] = true;';

                    $html = '<div class="searchDiv" data-column="' . $datasCol->index . '" data-searchType="' . $datasCol->searchType . '" data-type="' . $datasCol->searchContentType . '">';
                    if (!empty($datasCol->enable_filters_label) && $datasCol->enable_filters_label !== '') {
                        $html .= '<label for="wptm_search_column' . $datasCol->index . '" >' . esc_attr($datasCol->enable_filters_label) . ' </label>';
                    } else {
                        $html .= '<label for="wptm_search_column' . $datasCol->index . '" >' . esc_attr__('Search data: ', 'wptm') . ' </label>';
                    }
                    switch ($datasCol->searchType) {
                        case 'select':
//                            $dataSearch = $this->getDifferentValueColumn($id_table, $datasCol->index + 1, true);
                            $html .= '<select class="wptm_search_column" name="wptm_search_column' . $datasCol->index . '"><option value="">' . esc_attr__('Select', 'wptm') . '</option>';
//                            $count = count($dataSearch);
//                            for ($i = 0; $i < $count; $i++) {
//                                $html .= '<option value="' . strip_tags($dataSearch[$i]) . '">' . strip_tags($dataSearch[$i]) . '</option>';
//                            }
                            $html .= '</select>';
                            $jsTab .= ' if (typeof wptmFormSearch.table' . $randomKey . '.select === "undefined") {wptmFormSearch.table' . $randomKey . '.select = [];}';
                            $jsTab .= ' wptmFormSearch.table' . $randomKey . '.select[' . $datasCol->index . '] = [];';
                            break;
                        case 'range':
                            if ($datasCol->searchContentType === 'date') {
                                if (empty($datasCol->min) || empty($datasCol->max)) {
                                    $html .= '<input class="wptm_search_column wptm_range" type="text" name="wptm_search_column' . $datasCol->index . '" placeholder="' . esc_attr__('Date To Date', 'wptm') . '"/>';
                                } else {
                                    $defaultValue= '' . $datasCol->min . ' - ' .$datasCol->max;
                                    $html .= '<input class="wptm_search_column wptm_range" type="text" value="' . $defaultValue . '" name="wptm_search_column' . $datasCol->index . '" placeholder="' . esc_attr__('Date To Date', 'wptm') . '"/>';
                                }
                            } else {
                                $html .= '<section class="wptm_slider"><input class="wptm_hiden" value="' . $datasCol->min . ' - ' . $datasCol->max . '"/><input class="rangeValues" type="text" value="' . $datasCol->min . ' - ' . $datasCol->max . '"/>';
                                $html .= '<div class="container"><div class="slider-track"></div>';
                                $html .= '<input class="wptm_slider_min wptm_slider" type="range" min="' . $datasCol->min . '" max="' . $datasCol->max . '" value="' . $datasCol->min . '" id="slider-1" oninput="slideOne()"/>';
                                $html .= '<input class="wptm_slider_max wptm_slider" type="range" min="' . $datasCol->min . '" max="' . $datasCol->max . '" value="' . $datasCol->max . '" id="slider-2" oninput="slideTwo()"/>';
                                $html .= '</div></section>';
                            }
                            break;
                        case 'input':
                            if ($datasCol->searchContentType === 'date') {
                                $html .= '<input class="wptm_search_column" type="text" name="wptm_search_column' . $datasCol->index . '">';
                            } else {
                                $html .= '<input name="wptm_search_column' . $datasCol->index . '" class="wptm_search_column" type="text" placeholder="' . ((!empty($datasCol->enable_filters_label) && $datasCol->enable_filters_label !== '') ? esc_attr($datasCol->enable_filters_label) : esc_attr__('Search data', 'wptm')) . '" />';
                            }
                            break;
                        case 'checkBox':
                            $dataSearch = null;
                            if (!((!empty($datasCol->checkBoxContent) && $datasCol->checkBoxContent[0] === 'all_value') || empty($datasCol->checkBoxContent))) {
//                                $dataSearch = $this->getDifferentValueColumn($id_table, $datasCol->index + 1, true);
//                            } else {
                                $dataSearch = $datasCol->checkBoxContent;
                            }
                            $html .= '<input name="wptm_search_column' . $datasCol->index . '" class="material-symbols-outlined wptm_search_column wptm_button" type="button" value="' . esc_attr__('Select', 'wptm') . '"/>';
                            /*<!-- The Modal -->*/
                            $html .= '<div class="wptm_modal"><div class="wptm_modal-content">';
                            $html .= '<div class="wptm_modal-header">';
                            $html .= '<h2>' . (!empty($datasCol->enable_filters_label) && $datasCol->enable_filters_label !== '') ? esc_attr($datasCol->enable_filters_label) : esc_attr__('Search data ', 'wptm') . ' </h2>';
                            $html .= '<i class="material-icons wptm_close">close</i>';
                            $html .= '</div>';

                            $html .= '<div class="wptm_modal-body">';
//                            $count = count($dataSearch);
//                            if ($count > 0) {
//                                for ($i = 0; $i < $count; $i++) {
//                                    $html .= '<div class="wptm_checkbox_option">';
//                                    $html .= '<input type="checkbox" class="wptm_filter_checkbox" name="wptm_filter_checkbox" value="' . strip_tags($dataSearch[$i]) . '"><label class="wptm_filter_checkbox_label" for="wptm_filter_checkbox">' . $dataSearch[$i] . '</label>';
//                                    $html .= '</div>';
//                                }
//                            }
                            $html .= '</div>';

                            $html .= '<div class="wptm_modal-footer">';
                            $html .= '<input class="wptm_filter_checkbox_clear wptm_button" type="button" value="' . esc_attr__('Clear', 'wptm') . '" />';
                            $html .= '<input class="wptm_filter_checkbox_submit wptm_button" type="button" value="' . esc_attr__('Submit', 'wptm') . '" />';
                            $html .= '</div>';

                            $html .= '</div></div>';

                            $jsTab .= ' if (typeof wptmFormSearch.table' . $randomKey . '.checkBox === "undefined") {wptmFormSearch.table' . $randomKey . '.checkBox = [];}';
                            $jsTab .= ' wptmFormSearch.table' . $randomKey . '.checkBox[' . $datasCol->index . '] = [];';

                            if (!empty($dataSearch)) {
                                $count = count($dataSearch);
                                $content = array();
                                for ($i = 0; $i < $count; $i++) {
                                    $content[] = strip_tags($dataSearch[$i]);
                                }
                                //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- no support php5.3
                                $jsTab .= 'wptmFormSearch.table' . $randomKey . '.checkBox[' . $datasCol->index . '] = ' . htmlspecialchars(json_encode($content), ENT_COMPAT | ENT_HTML401, 'UTF-8') . ';';
                            }

                            break;
                    }
                    $html .= '</div>';
                    $form .= $html;
                }
            }

            $jsTab .= ' wptmFormSearch.table' . $randomKey . '.submit_form = ' . (!empty($filterOptions->submit_form) && $filterOptions->submit_form === 1 ? '1' : '0') . ';';
            $jsTab .= '</script>';

            $form .= $jsTab;

            $form .= '<div class="submit_form">';
            if (!empty($filterOptions->submit_form) && $filterOptions->submit_form === 1) {
                $form .= '<button href="javascript:void(0);" class="wptm_button wptm_submit_form" data-id="' . $id_table . '"/>' . esc_attr__('Submit', 'wptm') . '</button>';
            }
            if (!empty($filterOptions->clear_form) && $filterOptions->clear_form === 1) {
                $form .= '<button href="javascript:void(0);" class="wptm_button wptm_clear_form" data-id="' . $id_table . '"/>' . esc_attr__('Clear filters', 'wptm') . '</button>';
            }
            $form .= '</div>';
            $form .= '</div>';
        }

        return $form;
    }

    /**
     * Get different value of column
     *
     * @param integer $id           Table id
     * @param integer $column       Column index
     * @param boolean $getFromDb    Get value direct from DB
     * @param array   $defaultDdata Default value
     *
     * @return array
     */
    public function getDifferentValueColumn($id, $column, $getFromDb, $defaultDdata = array())
    {
        if ($getFromDb) {//unresolved problem, not get value of calculation
            /* @var WptmModelTablesite $modelChart */
            $modelTable = $this->getModel('tablesite');
            $searchData = $modelTable->getDifferentValueColumn($id, $column);
            return $searchData && array_shift($searchData) ? $searchData : array();
        } else {//unresolved problem, not get value of calculation
            $searchData = array();
            return $searchData;
        }
    }

    /**
     * Get table content for gutenberg block
     *
     * @param integer $id   Id table
     * @param string  $hash Table hash
     *
     * @return array|boolean|string
     */
    public function getTableForBlock($id, $hash)
    {
        $this->useEnqueueFile = true;
        $this->gutenbergBlock = true;
        $this->hash = $hash;
        $content = $this->replaceTable($id, true);

        return $content;
    }

    /**
     * Get table content for gutenberg block
     *
     * @param array  $selections      Chart selection
     * @param string $direction       Switch Row/Column
     * @param array  $chartDataRanger Cells range to be used creating chart
     *
     * @return array|boolean|string
     */
    public function validateChartDataPosition($selections = null, $direction = null, &$chartDataRanger = array())
    {
        $chartData = array();
        $chartdataRaw = array();
        $chartDataPosition = array();
        $chartDataRanger = array();//[min row, min col, max row, max col]

        if (is_string($selections[0][0]) && !is_numeric($selections[0][0])) {//old data
            $startCell = explode(':', $selections[0][0]);
            $count = count($selections[count($selections) - 1]);
            $endCell = explode(':', $selections[count($selections) - 1][$count - 1]);

            $selections = array(array((int)$startCell[0], (int)$startCell[1], (int)$endCell[0], (int)$endCell[1]));
        }


        $max_value = 0;
        $count = count($selections);
        $newSelections = array();

        for ($i = 0; $i < $count; $i++) {
            $selection = $selections[$i];

            if ($direction !== 'row') {//direction === col
                $selectCell = array((int)$selection[0], (int)$selection[1], (int)$selection[2], (int)$selection[3]);
            } else {//default row
                $selectCell = array((int)$selection[1], (int)$selection[0], (int)$selection[3], (int)$selection[2]);
            }
            $max_value = ($selectCell[2] - $selectCell[0] + 1) > $max_value ? ($selectCell[2] - $selectCell[0] + 1) : $max_value;
            $newSelections[] = $selectCell;

            $chartDataRanger[0] = !isset($chartDataRanger[0]) || $selection[0] <= $chartDataRanger[0] ? $selection[0] : $chartDataRanger[0];
            $chartDataRanger[1] = !isset($chartDataRanger[1]) || $selection[1] <= $chartDataRanger[1] ? $selection[1] : $chartDataRanger[1];
            $chartDataRanger[2] = !isset($chartDataRanger[2]) || $selection[2] >= $chartDataRanger[2] ? $selection[2] : $chartDataRanger[2];
            $chartDataRanger[3] = !isset($chartDataRanger[3]) || $selection[3] >= $chartDataRanger[3] ? $selection[3] : $chartDataRanger[3];

            for ($i2 = $selectCell[1]; $i2 <= $selectCell[3]; $i2++) {
                for ($i3 = $selectCell[0]; $i3 <= $selectCell[2]; $i3++) {
                    if (empty($chartData[$i2])) {
                        $chartData[$i2] = array();
                    }
                    if (empty($chartdataRaw[$i2])) {
                        $chartdataRaw[$i2] = array();
                    }
                    if (empty($chartDataPosition[$i2 . '!' . $i3])) {
                        $chartData[$i2][] = $i3;
                        $chartDataPosition[$i2 . '!' . $i3] = 1;
                    }
                }
            }
        }

        if ($max_value >= 1 && count($chartData) > 1) {
            foreach ($chartData as $key => $value) {
                if (count($value) < $max_value) {
                    $chartData[$key] = array_merge($value, array_fill(0, $max_value - count($value), 0));
                }
            }
        }
        return $chartData;
    }

    /**
     * Create chart(front_end)
     *
     * @param integer $id_chart               Id of chart
     * @param boolean $checkElementor_preview Check elementor preview
     *
     * @return string|array
     */
    public function replaceChart($id_chart, $checkElementor_preview = false)
    {
        Application::getInstance('Wptm');
        if ($checkElementor_preview) {
            $this->getType = 'site';
        }

        /* @var WptmModelChartsite $modelChart */
        $modelChart = $this->getModel('chartSite');
        $chart = $modelChart->getChart($id_chart);

        $content = '';

        if ($chart) {
            $chartConfig = json_decode($chart->config);
            $chartDatas = json_decode($chart->datas);
            $chartData = $this->validateChartDataPosition($chartDatas, $chartConfig->dataUsing, $chartDataRanger);
            //$chartDataRanger is selection [rowStart, colStart, rowEnd, colEnd]
            if (isset($chartConfig->useFirstRowAsGraph)) {
                $this->useFirstRowAsGraph = $chartConfig->useFirstRowAsGraph;
            }
            $modelConfig = $this->getModel('configSite');
            $configParams = $modelConfig->getConfig();

            $modelTable = $this->getModel('table');
            $tableData = $modelTable->getItem($chart->id_table, true, true, null, false);

            if (empty($tableData)) {
                return $content;
            }

//            $chartData = $this->getChartData($chart->datas, $tableData);
            $readDataChartBySpreadsheet = $this->readDataChartBySpreadsheet($chartDataRanger, $chartData, $tableData, $chartConfig);

            $symbol_position = (!empty($configParams['symbol_position'])) ? $configParams['symbol_position'] : 0;
            $symbol_position = (!empty($tableData->style->table->symbol_position)) ? $tableData->style->table->symbol_position : $symbol_position;
            $currency_symbol = (!empty($configParams['currency_sym'])) ? $configParams['currency_sym'] : '$';
            $currency_symbol = (!empty($tableData->style->table->currency_symbol)) ? $tableData->style->table->currency_symbol : $currency_symbol;
            $decimal_symbol = (!empty($configParams['decimal_sym'])) ? $configParams['decimal_sym'] : '.';
            $decimal_symbol = (!empty($tableData->style->table->decimal_symbol)) ? $tableData->style->table->decimal_symbol : $decimal_symbol;
            $decimal_count = (!empty($configParams['decimal_count'])) ? $configParams['decimal_count'] : 0;
            $decimal_count = (!empty($tableData->style->table->decimal_count)) ? $tableData->style->table->decimal_count : $decimal_count;
            $thousand_symbol = (!empty($configParams['thousand_sym'])) ? $configParams['thousand_sym'] : ',';
            $thousand_symbol = (!empty($tableData->style->table->thousand_symbol)) ? $tableData->style->table->thousand_symbol : $thousand_symbol;

            if (!empty($chartConfig->customLegend)) {
                $readDataChartBySpreadsheet[2] = $chartConfig->customLegend;
            }
            $jsChartData = $this->buildJsChartData(
                $readDataChartBySpreadsheet,
                $chart->type,
                $chartConfig,
                $currency_symbol,
                $decimal_symbol,
                $thousand_symbol
            );

            if (!$chartConfig) {
                $chartConfig = new stdClass();
            }

            $chartConfig->width = isset($chartConfig->width) ? $chartConfig->width : 500;
            $chartConfig->height = isset($chartConfig->height) ? $chartConfig->height : 375;
            $chartConfig->chart_align = isset($chartConfig->chart_align) ? $chartConfig->chart_align : 'center';
            $symbol = '';

            $js = 'var DropChart = {};' . "\n";
            $reactVar = new stdClass();
            $js .= 'DropChart.id = "' . $id_chart . '" ; ' . "\n";
            $randomId = uniqid();
            $js .= 'DropChart.random_id = "' . $randomId . '" ; ' . "\n";
            $reactVar->id = $id_chart;
            $js .= 'DropChart.type = "' . $chart->type . '" ; ' . "\n";
            $reactVar->type = $chart->type;
            $js .= 'DropChart.data = ' . $jsChartData . '; ' . "\n";
            $reactVar->data = $jsChartData;
            $js .= 'DropChart.currency_symbols = "' . $symbol . '"; ' . "\n";
            $reactVar->currency_symbols = $symbol;
            $js .= 'DropChart.places = ' . $decimal_count . '; ' . "\n";
            $reactVar->places = $decimal_count;
            $js .= 'DropChart.unit_symbols = ' . $symbol_position . '; ' . "\n";
            $reactVar->unit_symbols = $symbol_position;
            $js .= 'DropChart.decimal_symbols = "' . $decimal_symbol . '"; ' . "\n";
            $reactVar->decimal_symbols = $decimal_symbol;
            $js .= 'DropChart.thousand_symbols = "' . $thousand_symbol . '"; ' . "\n";
            $reactVar->thousand_symbols = $thousand_symbol;

            if (isset($chartConfig->useFirstRowAsGraph)) {
                $reactVar->useFirstRowAsGraph = $chartConfig->useFirstRowAsGraph;
                $js .= 'DropChart.useFirstRowAsGraph = "' . $reactVar->useFirstRowAsGraph . '"; ' . "\n";
            }

            if ($chart->config) {
                $js .= 'DropChart.config = ' . $chart->config . '; ' . "\n";
                $reactVar->config = $chart->config;
            } else {
                $js .= 'DropChart.config = {} ; ' . "\n";
                $reactVar->config = new stdClass();
            }
            $js .= ' if(typeof DropCharts === "undefined") { var DropCharts = []; } ; ' . "\n";

            $js .= ' DropCharts.push(DropChart) ; ' . "\n";

            if ($this->getType === '') {
                wp_enqueue_script('jquery');
                wp_enqueue_script('wptm_chart', plugins_url('app/admin/assets/js/Chart.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
                wp_enqueue_script('wptm_dropchart', plugins_url('app/site/assets/js/dropchart.js', WPTM_PLUGIN_FILE), array(), WPTM_VERSION);
            }
            $content = '<div class="chartContainer wptm" id="chartContainer' . $id_chart . '" data-id-chart="' . $id_chart . '">';

            $align = '';
            switch ($chartConfig->chart_align) {
                case 'left':
                    $align = ' margin : 0 auto 0 0; ';
                    break;
                case 'right':
                    $align = ' margin : 0 0 0 auto ';
                    break;
                case 'none':
                    break;
                case 'center':
                default:
                    $align = ' margin : 0 auto 0 auto ';
                    break;
            }

            $content .= '<div class="canvasWraper" style="max-height:' . $chartConfig->height
                . 'px; max-width:' . $chartConfig->width . 'px;' . $align . '" >';
            $content .= '<canvas class="canvas" data-random_id="' . $randomId . '"  height="' . $chartConfig->height . '" width="' . $chartConfig->width . '"></canvas>';
            $content .= '</div></div>';
            $content .= '<script>' . $js . '</script>';
        }

        if ($checkElementor_preview) {
            return array('content'=> $content, 'name' => $chart->title, 'js' => $reactVar);
        }

        return $content;
    }

    /**
     * Render style
     *
     * @param object     $table                  Data table
     * @param string     $checkElementor_preview Check elementor preview
     * @param array|null $argsShortCode          Arguments in short code
     * @param string     $shortCodeHash          Hash of short code
     *
     * @return void|array|boolean
     */
    private function styleRender($table, $checkElementor_preview = 'admin', $argsShortCode = null, $shortCodeHash = '')
    {
        $hightlight_color = $table->hightlight_color;
        $hightlight_font_color = $table->hightlight_font_color;
        $hightlight_opacity = $table->hightlight_opacity;
        if ($hightlight_color !== 'not hightlight') {
            require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'chartStyleSet.php';
            $chartStyleObj = new ChartStyleSet($hightlight_color);
            $highlighting_rgbcolor = $chartStyleObj->hex2rgba($hightlight_color, $hightlight_opacity);
            $table->hightlight_css = '.droptables-highlight-horizontal, .droptables-highlight-vertical  {  color: ' . $hightlight_font_color . ' !important; background: ' . $highlighting_rgbcolor . ' !important; }';
        } else {
            $table->hightlight_css = '';
        }

        $this->hightLightCss = $table->hightlight_css;
        require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
        $wptmHelper = new WptmHelper();
        $data_style = $wptmHelper->styleRender($table, $argsShortCode);

        $upload_url = wp_upload_dir();
        if (is_ssl()) {
            $upload_url['baseurl'] = str_replace('http://', 'https://', $upload_url['baseurl']);
        }
        $upload_url = $upload_url['baseurl'] . '/wptm/';
        if ($checkElementor_preview !== 'site') {
            if ($argsShortCode !== null) {
                wp_enqueue_style('wptm-table-' . ($argsShortCode['range'] === '' ? '' : ($shortCodeHash . '_')) . $table->id, $upload_url . ($shortCodeHash === '' ? '' : ($shortCodeHash . '_')) . $table->id . '_' . $table->hash . '.css', array(), WPTM_VERSION);
            } else {
                wp_enqueue_style('wptm-table-' . $table->id, $upload_url . $table->id . '_' . $table->hash . '.css', array(), WPTM_VERSION);
            }
            wp_enqueue_style('wptm-front', plugins_url('assets/css/front.css', __FILE__), array(), WPTM_VERSION);
            $min = '.min';
            if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
                $min = '';
            }
            wp_enqueue_style('wptm_datatables', plugins_url('assets/DataTables/datatables' . $min . '.css', __FILE__), array(), WPTM_VERSION);
            wp_enqueue_style('wptm_tipso', plugins_url('assets/tipso/tipso' . $min . '.css', __FILE__), array(), WPTM_VERSION);
        }

        return $data_style;
    }

    /**
     * Build js chart
     *
     * @param array  $data            Data cell in chart
     * @param string $type            Type chart
     * @param object $config          Data config chart
     * @param string $currency_symbol Currency symbol
     * @param string $decimal_symbol  Decimal symbol
     * @param string $thousand_symbol Thousand symbol
     *
     * @return mixed|string|void
     */
    private function buildJsChartData($data, $type, $config, $currency_symbol, $decimal_symbol, $thousand_symbol)
    {
        $result = '';

        if (!$config || !is_object($config)) {
            $config = new stdClass();
            $config->pieColors = '';
            $config->colors = '';
        }
        $config->dataUsing = isset($config->dataUsing) ? $config->dataUsing : 'row';
        $config->useFirstRowAsLabels = isset($config->useFirstRowAsLabels) ? $config->useFirstRowAsLabels : false;
        switch ($type) {
            case 'PolarArea':
            case 'Pie':
            case 'Doughnut':
//                $chartData = $this->convertForPie($dataSets->data[0], $dataSets->axisLabels, $config->pieColors);
                $chartData = $this->convertForLine($data, $config->useFirstRowAsLabels, $config->useFirstRowAsGraph, $config->pieColors, $type);
                break;

            case 'Bar':
            case 'Radar':
            case 'Line':
            default:
                $chartData = $this->convertForLine($data, $config->useFirstRowAsLabels, $config->useFirstRowAsGraph, $config->colors, $type);
                break;
        }
        $result = json_encode($chartData);
        return $result;
    }

    /**
     * Check column is int
     *
     * @param array  $cellsData       Data cell
     * @param string $currency_symbol Currrency symbol
     *
     * @return array data[0] is list satisfied cell, if data[1] exist then have cells isn't number + currency_symbol
     */
    private function replaceCell($cellsData, $currency_symbol)
    {
        $data1 = array();
        $i = 0;
        $data2 = -1;
        foreach ($cellsData as $k => $v) {
            $v1 = preg_replace('/[0-9\.\,\-| ]/', '', $v);
            $v1 = str_replace($currency_symbol, '', $v1);
            if ($v1 === '') {
                $data1[$i] = $k;
                $i++;
            } elseif ($v1 !== '') {
                $data2 = $k;
            }
        }

        $data = array();
        $data[0] = $data1;
        if ($data2 !== -1) {
            $data[1] = $data2;
        }
        return $data;
    }

    /**
     * Convert to number
     *
     * @param array|string $arr Data cell
     *
     * @return array|mixed
     */
    public function convertToNumber($arr)
    {
        $dataReturn = array();
        if (is_array($arr)) {
            $countArr = count($arr);
            for ($i = 0; $i < $countArr; $i++) {
                if (is_array($arr[$i])) {
                    $count = count($arr[$i]);
                    $dataReturn[$i] = array();
                    for ($j = 0; $j < $count; $j++) {
                        $dataReturn[$i][] = str_replace(',', '', $arr[$i][$j]);
                    }
                } else {
                    $dataReturn[$i] = str_replace(',', '', $arr[$i]);
                }
            }
        } else {
            return str_replace(',', '', $arr);
        }
        return $dataReturn;
    }
    /**
     * Get data cell to chart
     *
     * @param string $cell_value      Data cells
     * @param string $cell_value_raw  Data Switch
     * @param string $currency_symbol Currency symbol
     * @param string $decimal_symbol  Decimal symbol
     * @param string $thousand_symbol Thousand symbol
     *
     * @return array
     */
    public function getStrangeCharacters2($cell_value, $cell_value_raw, $currency_symbol, $decimal_symbol, $thousand_symbol)
    {
        $value = array();
        $value[0] = $cell_value;
        $value0 = $cell_value_raw === null ?  str_replace(' ', '', $cell_value) : str_replace(' ', '', $cell_value_raw);
        $value1 = str_replace($currency_symbol, '', $value0);
        if ($cell_value_raw === null) {
            $value1 = str_replace($thousand_symbol, '', $value1);
            $value1 = str_replace($decimal_symbol, '.', $value1);
        }
        $value[1] = preg_replace('/[^0-9|\.|-]/', '', $value1);

        $value[2] = 0;
        $value[3] = 0;//currency_symbol

        $value1 = preg_replace('/[0-9\.\,\-| ]/', '', $value1);
        if ($value1 !== '' || $cell_value === '' || $cell_value === null) {//have strange characters or is null
            $value[2] = 1;
        }
        if ($cell_value !== '' && (strrpos($cell_value, $currency_symbol) !== false && $cell_value_raw !== null)) {
            $value[3] = 1;
        }
        return $value;
    }

    /**
     * Get data cell to chart
     *
     * @param array  $cellsData       Data cells
     * @param string $dataUsing       Data Switch
     * @param string $currency_symbol Currency symbol
     * @param string $decimal_symbol  Decimal symbol
     * @param string $thousand_symbol Thousand symbol
     *
     * @return stdClass
     */
    private function getDataSets($cellsData, $dataUsing, $currency_symbol, $decimal_symbol, $thousand_symbol)
    {
        $result = new stdClass();
        $result->data = array();
        $result->data1 = array();
        $result->data_raw = array();
        $result->data_raw1 = array();
        $result->graphLabel = array();//text in line
        $result->axisLabels = array();//text in x-axis
        $result->currency_symbol = array();//text in x-axis
        $axisLabels = array();
        $deleteLine = array();

        if ($dataUsing !== 'row') {//convert to column type
            $cellsData[0] = $this->transposeArr($cellsData[0]);
            $cellsData[1] = $this->transposeArr($cellsData[1]);
        }

        $result->deleteData = array();

        foreach ($cellsData[0] as $k => $value) {
            $checkCellsHaveNaN = 0;
            $countCellInLine = count($value);
            $deleteData1 = array();
            $check_currency_symbol = 0;
            $data = array();
            $data1 = array();
            $result->data_raw[$k] = array();
            $result->data_raw1[$k] = array();

            for ($i = 0; $i < $countCellInLine; $i++) {
                $result->data_raw[$k][] = $value[$i];
                $result->data_raw1[$k][] = $cellsData[1][$k][$i];

                $cell_value = $this->getStrangeCharacters2($value[$i], $cellsData[1][$k][$i], $currency_symbol, $decimal_symbol, $thousand_symbol);
                $checkCellsHaveNaN += $cell_value[2];
                $data[] = $cell_value[0];
                $data1[] = $cell_value[1];

                if ($cell_value[2] === 1) {//have strange characters or is null
                    $deleteData1[$i] = 1;
                }
                if ($cell_value[3] === 1) {
                    $check_currency_symbol++;
                }
            }

            if ($checkCellsHaveNaN === $countCellInLine || ($countCellInLine > 2 && $checkCellsHaveNaN + 2 > $countCellInLine)) {//line Have NaN
                $axisLabels[] = $data;
                $deleteLine[] = $k;
            } else {//get this line, that have cell value
                foreach ($deleteData1 as $ii => $deleteData) {
                    if (!isset($result->deleteData[$ii])) {
                        $result->deleteData[$ii] = 0;
                    }
                    $result->deleteData[$ii] += 1;
                }

                $result->graphLabel[] = $data[0];//array key 1, 2, 3,...||$value first value
                $result->data[] = $data;
                $result->data1[] = $data1;

                if ($check_currency_symbol > 1) {
                    $result->currency_symbol[] = 1;
                } else {
                    $result->currency_symbol[] = -1;
                }
            }
        }

        $numberLine = count($result->data);
        $useFirstRowAsGraph = isset($this->useFirstRowAsGraph) ? $this->useFirstRowAsGraph : true;
        //if line number > 1 then not get cell is graphLabel else < 1 then get it
        if ($numberLine > 1 && count($result->data_raw) > 1 && !(count($result->data_raw) === 2 && $useFirstRowAsGraph !== true)) {//have > 1 line in chart
            for ($i = 0; $i < $numberLine; $i++) {
                array_shift($result->data[$i]);
                array_shift($result->data1[$i]);
            }
            $result->arrayShiftData = true;

            if (isset($result->deleteData[0])) {
                unset($result->deleteData[0]);
            }
        }

        if (count($axisLabels) > 0) {//useFirstRowAsGraph become useless
            $result->axisLabels = $axisLabels[0];
        } elseif ($numberLine > 0) {//axisLabels from $cellsData[0] || all line be passed validated
            $result->axisLabels = $result->data_raw[0];
            if ($useFirstRowAsGraph !== true) {
                array_shift($result->data);
                array_shift($result->data1);
                array_shift($result->currency_symbol);
                array_shift($result->graphLabel);
            }
        }

        if (!empty($result->arrayShiftData)) {
            array_shift($result->axisLabels);
        }

        foreach ($result->deleteData as $ii => $deleteData) {//not deleted yet cells not pass
            if ($numberLine !== $deleteData) {
                unset($result->deleteData[$ii]);
            }
        }
        $result->data1 = $this->convertToNumber($result->data1);
        return $result;
    }

    /**
     * Convert for line table
     *
     * @param object  $dataCellForChart    Data chart after change
     * @param boolean $useFirstRowAsLabels Data chart after change
     * @param boolean $useFirstRowAsGraph  Data chart after change
     * @param string  $colors              Color lines in chart
     * @param string  $checkTyleColor      Color input in chart
     *
     * @return stdClass
     */
    private function convertForLine($dataCellForChart, $useFirstRowAsLabels, $useFirstRowAsGraph, $colors, $checkTyleColor)
    {
        $result = new stdClass();
        $result->datasets = array();
        if (!is_array($dataCellForChart[1]) || (count($dataCellForChart[1]) === 0)) {
            return $result;
        }

        $result->labels = $dataCellForChart[3];//da check useFirstRowAsGraph in readDataChartBySpreadsheet()
//        if (isset($useFirstRowAsGraph) && !$useFirstRowAsGraph) {
//            array_shift($result->labels);
//        }
        $i = 0;

        foreach ($dataCellForChart[1] as $line => $dataCellForLine) {
            $dataSet = new stdClass();
            if (isset($useFirstRowAsGraph) && !$useFirstRowAsGraph) {
                array_shift($dataCellForLine);
                array_shift($dataCellForChart[0][$line]);
            }
            $dataSet->label = $dataCellForChart[2][$i];

            $dataSet->dataForTooltip = $dataCellForChart[0][$line];

            if ($checkTyleColor === 'PolarArea'
                || $checkTyleColor === 'Pie'
                || $checkTyleColor === 'Doughnut') {
                $dataSet->highlight = array();
                $dataSet->backgroundColor = array();
                $dataSet->borderColor = array();
                $dataSet->pointBackgroundColor = array();
                $dataSet->pointColor = array();
                $dataSet->pointBorderColor = array();
                $dataSet->pointHighlightFill = array();
            }
            $countDatasets = count($dataCellForLine);
            for ($j = 0; $j < $countDatasets; $j++) {
                if (!isset($dataSet->data)) {
                    $dataSet->data = array();
                }
                $dataSet->data[] = $dataCellForLine[$j];

                if ($checkTyleColor === 'PolarArea'
                    || $checkTyleColor === 'Pie'
                    || $checkTyleColor === 'Doughnut') {
                    if ($checkTyleColor === 'PolarArea') {
                        $pieColors = $this->getStyleSet($j, $colors, 0.5);
                    } else {
                        $pieColors = $this->getStyleSet($j, $colors);
                    }
                    $dataSet->highlight[] = $pieColors->highlight;
                    $dataSet->backgroundColor[] = $pieColors->backgroundColor;
                    $dataSet->borderColor[] = $pieColors->borderColor;
                    $dataSet->pointBackgroundColor[] = $pieColors->pointBackgroundColor;
                    $dataSet->pointColor[] = $pieColors->pointColor;
                    $dataSet->pointBorderColor[] = $pieColors->pointBorderColor;
                    $dataSet->pointHighlightFill[] = $pieColors->pointHighlightFill;
                }
                if (isset($useFirstRowAsLabels) && !$useFirstRowAsLabels) {
                    $result->labels[$j] = '';
                }
            }
            if ($checkTyleColor === 'Bar'
                || $checkTyleColor === 'Radar'
                || $checkTyleColor === 'Line') {
                if ($checkTyleColor === 'Radar' || $checkTyleColor === 'Line') {
                    $styleSet = $this->getStyleSet($i, $colors, 0.5);
                } else {
                    $styleSet = $this->getStyleSet($i, $colors);
                }
                $dataSet = (object)array_merge((array)$dataSet, (array)$styleSet);
                $dataSet->fill = true;
            }
            $result->datasets[$i] = $dataSet;

            $i++;
        }

        return $result;
    }

    /**
     * Convert from datas chart var(pie)
     *
     * @param array  $data       Data cells of pie chart
     * @param array  $axisLabels Data axis Labels
     * @param string $pieColors  Color pie
     *
     * @return array
     */
    private function convertForPie($data, $axisLabels, $pieColors)
    {
        $datas = array();
        $defaultColors = array('#F7464A', '#46BFBD', '#FDB45C', '#949FB1', '#4D5360');
        $highlights = array('#FF5A5E', '#5AD3D1', '#FFC870', '#A8B3C5', '#616774');

        if (!$pieColors) {
            $colors = $defaultColors;
        } else {
            $colors = explode(',', $pieColors);
        }
        $countData = count($data);
        for ($i = 0; $i < $countData; $i++) {
            $dataSet = new stdClass();
            $dataSet->value = (float)$data[$i];
            $dataSet->label = (string)$axisLabels[$i];
            if (isset($colors[$i])) {
                $dataSet->color = $colors[$i];
                $dataSet->highlight = $this->alterBrightness($colors[$i], 0.3);
            } else {
                $dataSet->color = $defaultColors[$i % 5];
                $dataSet->highlight = $highlights[$i % 5];
            }

            $datas[$i] = $dataSet;
        }

        return $datas;
    }

    /**
     * Convert string color
     *
     * @param string  $colourstr Color str
     * @param integer $steps     Steps
     *
     * @return string
     */
    public function alterBrightness($colourstr, $steps)
    {
        $colourstr = str_replace('#', '', $colourstr);
        $rhex = substr($colourstr, 0, 2);
        $ghex = substr($colourstr, 2, 2);
        $bhex = substr($colourstr, 4, 2);

        $r = hexdec($rhex);
        $g = hexdec($ghex);
        $b = hexdec($bhex);

        $r = max(0, min(255, $r + $r * $steps));
        $g = max(0, min(255, $g + $g * $steps));
        $b = max(0, min(255, $b + $b * $steps));

        return '#' . dechex($r) . dechex($g) . dechex($b);
    }

    /**
     * Get style
     *
     * @param integer    $i       Order line
     * @param string     $colors  Color line
     * @param float|null $opacity Opacity color
     *
     * @return ChartStyleSet|null
     */
    private function getStyleSet($i, $colors, $opacity = null)
    {
        $result = null;
        $defaultColors = array('#4285f4', '#ea4335', '#fbbc04', '#34a853', '#46bdc6', '#7baaf7', '#f07b72');

        if (!$colors) {
            $arrColors = $defaultColors;
        } else {
            $arrColors = explode(',', $colors);
        }

        if (count($arrColors) && isset($arrColors[$i])) {
            $color = $arrColors[$i];
        } else {
            $color = $defaultColors[$i % 7];
        }

        require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'chartStyleSet.php';
        $result = new ChartStyleSet($color, $opacity);

        return $result;
    }

    /**
     * Check exist number in array
     *
     * @param array   $arr             Array needs to check
     * @param string  $currency_symbol Currency symbol
     * @param boolean $arg             Check numberic for value array
     *
     * @return boolean|array
     */
    private function isNumbericArray($arr, $currency_symbol, $arg = false)
    {
        $countArr = count($arr);
        $valid = $countArr;
        $check = array();

        for ($c = 0; $c < $countArr; $c++) {
            if ($arr[$c] !== '') {
                $arr[$c] = str_replace($currency_symbol, '', (string)$arr[$c]);
                $arr[$c] = preg_replace('/[\.\,\-]/', '', $arr[$c]);
                if (!is_numeric($arr[$c])) {
                    $valid--;
                    $check[$c] = 1;
                }
            }
        }

        if ($arg) {
            return $check;
        } else {
            return $valid > 0 ? true : false;
        }
    }

    /**
     * Check number in row
     *
     * @param array  $cells           Data cells
     * @param string $currency_symbol Currency symbol
     *
     * @return boolean if has a row/column includes all numbers then return true else return false
     */
    private function hasNumbericRow($cells, $currency_symbol)
    {
        $rValid = true;
        $rNaN = 0;
        $countCells = count($cells);

        for ($r = 0; $r < $countCells; $r++) {
            $valid = true;
            $count = count($cells[$r]);
            for ($c = 0; $c < $count; $c++) {
                $cells[$r][$c] = str_replace($currency_symbol, '', (string)$cells[$r][$c]);
                if (!is_numeric(preg_replace('/[\.\,\-]/', '', $cells[$r][$c]))) {
                    $valid = false;
                }
            }

            if (!$valid) {//has cell is not number
                $rNaN++;
            }
        }

        if ($rNaN === count($cells)) {
            $rValid = false;
        }

        return $rValid;
    }

    /**
     * Check second dimension
     *
     * @param array $array Data cells
     *
     * @return array
     */
    private function transposeArr($array)
    {
        $transposed_array = array();
        if ($array) {
            foreach ($array as $row_key => $row) {
                if (is_array($row) && !empty($row)) { //check to see if there is a second dimension
                    foreach ($row as $column_key => $element) {
                        $transposed_array[$column_key][$row_key] = $element;
                    }
                } else {
                    $transposed_array[0][$row_key] = $row;
                }
            }
            return $transposed_array;
        }
    }

    /**
     * Get chart data
     *
     * @param array  $chartDataRanger Cells range
     * @param array  $chartData       Cell lines to create chart
     * @param array  $datas           Data table(data cell, format and style)
     * @param object $chartConfig     Chart config
     *
     * @return array [1] is value has currency..., [2] is raw
     */
    public function readDataChartBySpreadsheet($chartDataRanger, $chartData, $datas, $chartConfig)
    {
        $cellValue = array();
        $cellValueRaw = array();
        $tblStyles = $datas->style;
        $dataUsing = isset($chartConfig->dataUsing) ? $chartConfig->dataUsing : 'row';
        if (!isset($tblStyles->table)) {
            $tblStyles->table = new stdClass();
        }

        $thousand_symbol = '';
        $decimal_symbol = '';
        $currency_symbol = '';
        if (!empty($tblStyles->table->thousand_symbol)) {
            $thousand_symbol = $tblStyles->table->thousand_symbol;
        }
        if (!empty($tblStyles->table->decimal_symbol)) {
            $decimal_symbol = $tblStyles->table->decimal_symbol;
        }
        if (!empty($tblStyles->table->currency_symbol)) {
            $currency_symbol = $tblStyles->table->currency_symbol;
        }

        $data_value = $datas->datas;

        require_once(dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR
            . 'classes' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $activeSheet = $spreadsheet->createSheet(1);
        $maxRows = count($data_value);
        require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
        $wptmHelper = new WptmHelper();
        $activeSheet->fromArray($wptmHelper->renderValueCalculateCell($data_value, $maxRows), null, 'A1');

        for ($r = $chartDataRanger[0]; $r <= $chartDataRanger[2]; $r++) {
            for ($c = $chartDataRanger[1]; $c <= $chartDataRanger[3]; $c++) {
                if (isset($tblStyles->cells[$r . '!' . $c])) {
                    $tblStyle = $tblStyles->cells[$r . '!' . $c][2];
                } else {
                    $tblStyle = array();
                }
                if (isset($tblStyle['decimal_count']) && $tblStyle['decimal_count'] !== false
                    || isset($tblStyle['decimal_symbol']) && $tblStyle['decimal_symbol'] !== false
                    || isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== false) {
                    $wptmHelper::$thousand_symbol_cell = (isset($tblStyle['thousand_symbol']) && $tblStyle['thousand_symbol'] !== false)
                        ? $tblStyle['thousand_symbol'] : ((isset($tblStyle['thousand_symbol_second']) && $tblStyle['thousand_symbol_second'] !== false) ? $tblStyle['thousand_symbol_second'] : $wptmHelper->thousand_symbol);
                    $wptmHelper::$decimal_count_cell = (isset($tblStyle['decimal_count']) && $tblStyle['decimal_count'] !== false)
                        ? $tblStyle['decimal_count'] : ((isset($tblStyle['decimal_count_second']) && $tblStyle['decimal_count_second'] !== false) ? $tblStyle['decimal_count_second'] : $wptmHelper->decimal_count);
                    $wptmHelper::$decimal_symbol_cell = (isset($tblStyle['decimal_symbol']) && $tblStyle['decimal_symbol'] !== false)
                        ? $tblStyle['decimal_symbol'] : ((isset($tblStyle['decimal_symbol_second']) && $tblStyle['decimal_symbol_second'] !== false) ? $tblStyle['decimal_symbol_second'] : $wptmHelper->decimal_symbol);
                    $wptmHelper::$currency_symbol_cell = (isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== false)
                        ? $tblStyle['currency_symbol'] : ((isset($tblStyle['currency_symbol_second']) && $tblStyle['currency_symbol_second'] !== false) ? $tblStyle['currency_symbol_second'] : $wptmHelper->currency_symbol);
                    $wptmHelper::$symbol_position_cell = (isset($tblStyle['symbol_position']) && $tblStyle['symbol_position'] !== false)
                        ? $tblStyle['symbol_position'] : ((isset($tblStyle['symbol_position_second']) && $tblStyle['symbol_position_second'] !== false) ? $tblStyle['symbol_position_second'] : $wptmHelper->symbol_position);
                    $has_format_cell = true;
                } else {
                    $wptmHelper::$thousand_symbol_cell = null;
                    $wptmHelper::$decimal_count_cell = null;
                    $wptmHelper::$decimal_symbol_cell = null;
                    $wptmHelper::$currency_symbol_cell = null;
                    $wptmHelper::$symbol_position_cell = null;
                    $has_format_cell = false;
                }
                $cell_value = $data_value[$r][$c];
                $position = array();
                $position[] = $wptmHelper->getNameFromNumber($c);
                $position[] = $r + 1;

                if (preg_match('@^=(DATE|DAY|DAYS|DAYS360|AND|OR|XOR|SUM|MULTIPLY|DIVIDE|COUNT|MIN|MAX|AVG|CONCAT|date|day|days|days360|and|or|xor|sum|multiply|divide|count|min|max|avg|concat)\\((.*?)\\)$@', $cell_value, $matches)) {
                    $formula = strtoupper($matches[1]);
                    //check formula function to replace input value
                    if (in_array($formula, $wptmHelper->math_formula)) {
                        $wptmHelper::$decimal_count_cell = (isset($tblStyle['decimal_count_second']) && $tblStyle['decimal_count_second'] !== false) ? $tblStyle['decimal_count_second'] : $wptmHelper::$decimal_count_cell;
                        $wptmHelper::$decimal_symbol_cell = (isset($tblStyle['decimal_symbol_second']) && $tblStyle['decimal_symbol_second'] !== false) ? $tblStyle['decimal_symbol_second'] : $wptmHelper::$decimal_symbol_cell;
                        $wptmHelper::$thousand_symbol_cell = (isset($tblStyle['thousand_symbol_second']) && $tblStyle['thousand_symbol_second'] !== false) ? $tblStyle['thousand_symbol_second'] : $wptmHelper::$thousand_symbol_cell;
                        $wptmHelper::$currency_symbol_cell = (isset($tblStyle['currency_symbol_second']) && $tblStyle['currency_symbol_second'] !== false) ? $tblStyle['currency_symbol_second'] : $wptmHelper::$currency_symbol_cell;
                        $wptmHelper::$symbol_position_cell = (isset($tblStyle['symbol_position_second']) && $tblStyle['symbol_position_second'] !== false) ? $tblStyle['symbol_position_second'] : $wptmHelper::$symbol_position_cell;
                    }
                    $calculaterCell2 = $wptmHelper->calculaterCell2($data_value, $matches, $activeSheet, $position, true, $tblStyles->cells);
                    if (!is_array($calculaterCell2)) {
                        $returnCellValue = $calculaterCell2;
                        $returnCellValueRaw = null;
                    } else {
                        if (isset($calculaterCell2['row'])) {
                            $returnCellValue = $calculaterCell2[0];
                            $returnCellValueRaw = $calculaterCell2[0];
                        } else {
                            $returnCellValue = $calculaterCell2[0];
                            $returnCellValueRaw = $calculaterCell2[1];
                        }
                    }
                } elseif ($has_format_cell) {
                    $col1 = preg_replace('/[-|0-9|,|\.|' . $wptmHelper::$currency_symbol_cell . '| ]/', '', $cell_value);

                    $cell_value = preg_replace('/[' . $wptmHelper::$thousand_symbol_cell . '| ]/', '', $cell_value);
                    $cell_value = preg_replace('/[' . $wptmHelper::$decimal_symbol_cell . '| ]/', '.', $cell_value);
                    $returnCellValueRaw = preg_replace('/[' . $wptmHelper::$currency_symbol_cell . '| ]/', '', $cell_value);
                    if ($col1 === '') {
                        $cell_value = preg_replace('/[' . $wptmHelper::$currency_symbol_cell . '| ]/', '', $cell_value);
                        $cell_value = number_format(floatval($cell_value), $wptmHelper::$decimal_count_cell !== null ? $wptmHelper::$decimal_count_cell : 0, $wptmHelper::$decimal_symbol_cell, $wptmHelper::$thousand_symbol_cell);
                    }

                    if (isset($tblStyle['currency_symbol']) && $tblStyle['currency_symbol'] !== '' && $col1 === '') {
                        $cell_value = ((int) $wptmHelper::$symbol_position_cell === 0) ? $wptmHelper::$currency_symbol_cell . ' ' . $cell_value : $cell_value . ' ' . $wptmHelper::$currency_symbol_cell;
                    }
                    $returnCellValue = $cell_value;
                } else {
                    if (!empty($cell_value) && $cell_value[0] === '=') {
                        $returnCellValueRaw = $activeSheet->getCell($position[0] . $position[1])->getCalculatedValue();
                        $returnCellValue = $returnCellValueRaw;
                    } else {
                        if (!empty($cell_value) && $cell_value !== '' && $cell_value !== null) {
                            $cell_value1 = preg_replace('/[-|0-9|' . $currency_symbol . '|' . $decimal_symbol . '|' . $thousand_symbol . '| ]/', '', $cell_value);
                            if ($cell_value1 === '' || $cell_value1 === '%') {
                                $returnCellValue = $cell_value;
                                $cell_value = preg_replace('/[' . $currency_symbol . ']/', '', $cell_value);
                                $cell_value = preg_replace('/[' . $thousand_symbol . ']/', '', $cell_value);
                                $cell_value = preg_replace('/[' . $currency_symbol . ']/', '.', $cell_value);
                                if ($cell_value1 === '%') {
                                    $cell_value = preg_replace('/[%]/', '.', $cell_value);
                                }
                                $returnCellValueRaw = $cell_value;
                            } else {
                                $returnCellValue = $cell_value;
                                $returnCellValueRaw = null;
                            }
                        } else {
                            $returnCellValue = null;
                            $returnCellValueRaw = null;
                        }
                    }
                }

                $cellValue[$r . '!' . $c] = $returnCellValue;
                $cellValueRaw[$r . '!' . $c] = $returnCellValueRaw;
//                $cellValueRaw[$r . '!' . $c] = $wptmHelper->calculationCells($data_value, $tblStyle, $cell_value, $activeSheet, array($r, $c));
//                $cellValue[$r . '!' . $c] = $cell_value;
            }
        }

        $cellValues = array();
        $cellValueRaws = array();
        $xAxisLabels = null;
        $graphLabel = array();
        //only line in linesData
        if (!isset($chartConfig->linesdataAvailable) || count($chartConfig->linesdataAvailable) < 1) {
            $linesData = $chartData;
        } else {
            $linesData = $chartConfig->linesdataAvailable;
        }

        $lineExisChart = true;

        foreach ($linesData as $key => $value) {
            if (!isset($chartConfig->linesData) || count($chartConfig->linesData) < 1) {
                $lineData = $value;
                $line = $key;
            } else {//new, exis linesData
                $lineData = $chartData[$value];
                $line = $value;
                $lineExisChart = in_array($value, $chartConfig->linesData);
            }
            $count = count($lineData);
            $cellValue1 = array();
            $cellValueRaw1 = array();
            for ($r2 = 0; $r2 < $count; $r2++) {
                if ($dataUsing === 'column') {
                    $cellValue1[] = $cellValue[$lineData[$r2] . '!' . $line];
                    $cellValueRaw1[] = empty($cellValueRaw[$lineData[$r2] . '!' . $line]) || $cellValueRaw[$lineData[$r2] . '!' . $line] === ''
                        ? 0 : $cellValueRaw[$lineData[$r2] . '!' . $line];
                } else {
                    $cellValue1[] = $cellValue[$line . '!' . $lineData[$r2]];
                    $cellValueRaw1[] = empty($cellValueRaw[$line . '!' . $lineData[$r2]]) || $cellValueRaw[$line . '!' . $lineData[$r2]] === ''
                        ? 0 : $cellValueRaw[$line . '!' . $lineData[$r2]];
                }
            }

            if ($xAxisLabels === null) {
                $xAxisLabels = $cellValue1;
            }

            if ($lineExisChart) {
                $graphLabel[] = $cellValue1[0];

                $cellValues[$line] = $cellValue1;
                $cellValueRaws[$line] = $cellValueRaw1;
            }
            if (isset($chartConfig->axisColumn) &&  (int)$chartConfig->axisColumn === (int)$line) {
                $xAxisLabels = $cellValue1;
            }
        }

        if (isset($chartConfig->customText)) {
            $xAxisLabels = $chartConfig->customText;
        }

        return array($cellValues, $cellValueRaws, $graphLabel, $xAxisLabels);
    }

    /**
     * Get Cell Data
     *
     * @param string $cellPos   Cell Pos
     * @param array  $tableData Table Data
     *
     * @return string
     */
    private function getCellData($cellPos, $tableData)
    {
        $result = '';
        list($r, $c) = explode(':', $cellPos);
        $result = $tableData[$r][$c];
        return $result;
    }

    /**
     * Get a model
     *
     * @param string $modelname Model name
     *
     * @return boolean|object
     */
    public function getModel($modelname)
    {
        $modelname = preg_replace('/[^A-Z0-9_-]/i', '', $modelname);
        $filepath = Factory::getApplication()->getPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . strtolower($modelname) . '.php';
        if (!file_exists($filepath)) {
            return false;
        }
        include_once $filepath;
        $class = Factory::getApplication()->getName() . 'Model' . $modelname;
        $model = new $class();
        return $model;
    }
}
