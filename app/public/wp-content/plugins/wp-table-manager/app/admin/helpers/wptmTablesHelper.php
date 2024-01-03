<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

namespace Joomunited\WP_Table_Manager\Admin\Helpers;

/**
 * Class WptmTablesHelper
 */
class WptmTablesHelper
{
    /**
     * Get list local font
     *
     * @return array|null
     */
    public static function getlocalfont()
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
     * Download font from google
     *
     * @param string $font Name font
     *
     * @return array
     */
    public static function downloadFontGoogle($font)
    {
        $folder = wp_upload_dir();
        $folder = $folder['basedir'] . DIRECTORY_SEPARATOR . 'wptm' . DIRECTORY_SEPARATOR;
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        if (!file_exists($folder . 'fonts')) {
            mkdir($folder . 'fonts', 0777, true);
        }

        $regex_url = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
        $url      = str_replace(' ', '+', $font);
        $nameFont = str_replace(' ', '_', $font);
        $upload_url = wp_upload_dir();
        if (is_ssl()) {
            $upload_url['baseurl'] = str_replace('http://', 'https://', $upload_url['baseurl']);
        }
        $upload_url = $upload_url['baseurl'] . '/wptm/fonts/';

        $fontFile = $folder . $nameFont . '_google_fonts.css';
        $google_css = rtrim('http://fonts.googleapis.com/css?family=' . $url, "'");

        $fp = fopen($fontFile, 'w+');
        $ch = curl_init($google_css);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        $css_file_contents = file_get_contents($fontFile);

        if (preg_match_all($regex_url, $css_file_contents, $fonts)) {
            $fonts = $fonts[0];

            foreach ($fonts as $i => $font) {
                $font = rtrim($font, ')');

                $font_file_name = explode('/', $font);
                $font_file_name = array_pop($font_file_name);
                $font_file = $folder . 'fonts' . DIRECTORY_SEPARATOR . $font_file_name;

                // download font
                $ch = curl_init();
                $fp = fopen($font_file, 'w+');
                $ch = curl_init($font);
                curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);

                // replace string
                $css_file_contents = str_replace($font, $upload_url . $font_file_name, $css_file_contents);
            }

            $fh = fopen($fontFile, 'w+');
            fwrite($fh, $css_file_contents);
            fclose($fh);
        }

        $googleFont = self::setLocalFont('insert', $nameFont . '_google_fonts.css', 'google_font' . $nameFont);

        return array($googleFont, $fontFile);
    }

    /**
     * Save local font
     *
     * @param string       $action     Data config
     * @param array|string $option     Data config
     * @param string       $optionName Name option
     *
     * @return boolean
     */
    public static function setLocalFont($action, $option, $optionName = 'local_font')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wptm_table_options';
        if ($action === 'delete') {
            $result = $wpdb->query(
                $wpdb->prepare(
                    'DELETE FROM ' . $table . ' WHERE option_name = %s AND id = %d',
                    $optionName,
                    (int)$option['fontid']
                )
            );
            if ($result === false) {
                self::exitStatus(__('error while changing table', 'wptm'));
            } else {
                self::exitStatus(true);
            }
        } elseif ($action === 'update' && $option['fontid'] !== null) {
            $result = $wpdb->update(
                $table,
                array(
                    'option_name' => $optionName,
                    'option_value' => json_encode($option)
                ),
                array(
                    'id' => $option['fontid']
                )
            );
            if ($result === false) {
                self::exitStatus(__('error while changing table', 'wptm'));
            } else {
                self::exitStatus(true);
            }
        } else {
            $result = $wpdb->insert(
                $table,
                array(
                    'option_name' =>  $optionName,
                    'option_value' => json_encode($option)
                )
            );
        }
        if ($result === false) {
            self::exitStatus(__('error while changing table', 'wptm'));
        }
        $this_insert = $wpdb->insert_id;
        return $this_insert;
    }

    /**
     * Exit a request serving a json result
     *
     * @param string $status Exit status
     * @param array  $datas  Echoed datas
     *
     * @since 1.0.3
     *
     * @return void
     */
    public static function exitStatus($status = '', $datas = array())
    {
        $response = array('response' => $status, 'datas' => $datas);
        echo json_encode($response);
        die();
    }

    /**
     * Convert an object of files to a multidimentional array
     *
     * @param array /object $tables Table array
     *
     * @return array
     */
    public static function categoryObject($tables)
    {
        $ordered = array();
        foreach ($tables as $table) {
            $ordered[$table->id_category][] = $table;
        }
        return $ordered;
    }

    /**
     * Convert a list of charts to a multidimentional array
     *
     * @param array /object $charts Chart array
     *
     * @return array
     */
    public static function categoryCharts($charts)
    {
        $ordered = array();
        foreach ($charts as $chart) {
            $chart->modified_time = self::convertDate($chart->modified_time);
            $ordered[$chart->id_table][] = $chart;
        }
        return $ordered;
    }

    /**
     * Function convert date string to date format
     *
     * @param string $date Date string
     *
     * @return string
     */
    public static function convertDate($date)
    {
        if (get_option('date_format', null) !== null) {
            $date = date_create($date);
            $date = date_format($date, get_option('date_format') . ' ' . get_option('time_format'));
        }
        return $date;
    }
    /**
     * Parse data table
     *
     * @param object $item Data Table
     *
     * @return array
     */
    public static function parseItem($item)
    {
        $newData = array();
        $newData['type'] = isset($item->type) ? $item->type : $item->params->table_type;

        if ($newData['type'] === 'html') {
            //convert data cells
            $newData['datas'] = !empty($item->datas) ? json_decode($item->datas) : array(array());
        } else {
            $newData['datas'] = $item->datas;
        }

        /*convert param*/
        $newData['params'] = new \stdClass();
        if (isset($item->params)) {
            $newData['params'] = $item->params;
        }

        if (isset($item->style->table)) {
            foreach ($item->style->table as $key => $table) {
                if (!isset($newData['params']->{$key})) {
                    $newData['params']->{$key} = $table;
                    if ($key === 'freeze_row' && (int)$table > 0) {
                        $newData['params']->{$key} = 1;
                        $newData['params']->headerOption = (int)$table;
                    }
                }
            }
        }
        /*convert style*/
        $newData['style'] = new \stdClass();
        $newData['style']->rows = isset($item->style->rows) ? $item->style->rows : new \stdClass();
        $newData['style']->cols = isset($item->style->cols) ? $item->style->cols : new \stdClass();
        $newData['style'] = json_encode($newData['style']);
        $newData['numberRow'] = count($newData['datas']);
        $newData['numberCol'] = count($newData['datas'][0]);

        $newData['css'] = $item->css;

        if (isset($item->style->cells)) {
            $styleCells = self::mergeStyleCell($item->style->cells, $newData['numberRow'], $newData['numberCol']);
            $newData['styleCells'] = $styleCells['styleCells'];
            $newData['params']->cell_types = $styleCells['typeCells'];
        }
        return $newData;
    }

    /**
     * Create range style cells
     *
     * @param object  $cells Style cells
     * @param integer $row   Style row
     * @param integer $col   Style col
     *
     * @return array
     */
    public static function mergeStyleCell($cells, $row, $col)
    {
        $data = array();
        $content = array();
        $typeCells = array();

        for ($i = 0; $i < $row; $i++) {
            for ($j = 0; $j < $col; $j++) {
                if (isset($cells->{$i . '!' . $j})) {
                    $cell = $cells->{$i . '!' . $j};

                    $content[$cell[0] . '|' . $cell[1]] = array($cell[0],$cell[0],$cell[1],$cell[1]);
                    if (isset($cell[2]->width)) {
                        unset($cell[2]->width);
                    }
                    if (isset($cell[2]->height)) {
                        unset($cell[2]->height);
                    }
                    if (isset($cell[2]->cell_type)) {
                        if ($cell[2]->cell_type === 'html') {
                            $typeCells[] = array($cell[0], $cell[1], 1, 1, 'html');
                        } elseif ($cell[2]->cell_type === null) {
                            $typeCells[] = array($cell[0], $cell[1], 1, 1, '');
                        }
                        unset($cell[2]->cell_type);
                    }
                    array_push($content[$cell[0] . '|' . $cell[1]], json_encode($cell[2]));
                    if (isset($content[$cell[0] . '|' . ($cell[1] - 1)])) {
                        $cell_before = $content[$cell[0] . '|' . ($cell[1] - 1)];
                        if (!isset($cell_before[4])) {
                            $cell_before = $content[$cell_before[0] . '|' . $cell_before[2]];
                        }
                        if ($cell_before[4] === $content[$cell[0] . '|' . $cell[1]][4]) {
                            $content[$cell_before[0] . '|' . $cell_before[2]][3] = $content[$cell[0] . '|' . $cell[1]][3];
                            $data[$cell_before[0] . '|' . $cell_before[2]][3] = $content[$cell[0] . '|' . $cell[1]][3];
                            $content[$cell[0] . '|' . $cell[1]][2] = $cell_before[2];
                            unset($content[$cell[0] . '|' . $cell[1]][4]);
                        }
                    }
                    if (isset($content[$cell[0] . '|' . $cell[1]][4])) {
                        $data[$cell[0] . '|' . $cell[1]] = $content[$cell[0] . '|' . $cell[1]];
                    }
                }
            }
        }

        $content = array();
        foreach ($data as $cell) {
            if (isset($data[($cell[0] - 1) . '|' . $cell[2]])) {
                $cell_before = $data[($cell[0] - 1) . '|' . $cell[2]];
                if (!isset($cell_before[4])) {
                    $cell_before = $data[$cell_before[0] . '|' . $cell_before[2]];
                }
                if ($cell_before[4] === $cell[4] && $cell_before[3] === $cell[3]) {
                    $data[$cell_before[0] . '|' . $cell_before[2]][1] = $cell[1];
                    $content[$cell_before[0] . '|' . $cell_before[2]][1] = $cell[1];
                    $data[$cell[0] . '|' . $cell[2]][0] = $cell_before[0];
                    unset($data[$cell[0] . '|' . $cell[2]][4]);
                }
            }
            if (isset($data[$cell[0] . '|' . $cell[2]][4])) {
                $content[$cell[0] . '|' . $cell[2]] = $data[$cell[0] . '|' . $cell[2]];
            }
        }

        $style_cells = array();
        foreach ($content as $cell) {
            $cell[0]++;
            $cell[1]++;
            $cell[2]++;
            $cell[3]++;
            $style_cells[($cell[0] + 1). '|' . ($cell[2] + 1)] = $cell;
        }

        return array('styleCells'=>$style_cells, 'typeCells'=> $typeCells);
    }

    /**
     * Change theme to table
     *
     * @param object $item Data theme
     *
     * @return array
     */
    public static function changeThemeToTable($item)
    {
        $item->datas  = $item->data;
        $item->type   = 'html';
        $item->params = new \stdClass();

        if ($item->style === '') {
            $item->style = new \stdClass();
        } else {
            $item->style = json_decode(stripslashes_deep($item->style));
        }

        $newData = self::parseItem($item);

        return $newData;
    }

    /**
     * Upload image to media
     *
     * @param string      $name          Name image
     * @param string      $url           Url image
     * @param string|null $attachment_id Attachment id
     *
     * @return array|boolean|string
     */
    public static function uploadImage($name, $url, $attachment_id = null)
    {
        $update = false;
        if ($attachment_id !== null) {
            $attachment = get_attached_file($attachment_id);
            if ($attachment && file_exists($attachment) && md5_file($url) === md5_file($attachment)) {
                $attachment = wp_get_attachment_image_src($attachment_id, 'full');
                return $attachment;//not update, not insert
            }

            if ($attachment && file_exists($attachment) && md5_file($url) !== md5_file($attachment)) {
                $update = true;//update new file
            }
        }

        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($url);
        $dataName = pathinfo($name);

        $newFilename = $dataName['filename'] . time() . '.' . $dataName['extension'];
        $filename = $update ? $newFilename : $dataName['basename'];
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        $url = wp_get_attachment_image_src($attach_id, 'full');
        $data = array($url[0], $attach_id, $file);
        return $data;
    }

    /**
     * Get extension from mime type
     *
     * @param string $mime Mime type of file
     *
     * @return string|boolean
     */
    public static function mimeExt($mime)
    {
        $mime_map = array(
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'application/vnd.oasis.opendocument.text'                                   => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet'                            => 'ods',
            'application/vnd.oasis.opendocument.presentation'                           => 'odp',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        );

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : false;
    }

    /**
     * Get list attachment file in table
     *
     * @param integer $id Table id
     *
     * @return array|null
     */
    public static function getAttachmentFileInTable($id)
    {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT c.* FROM ' . $wpdb->prefix . 'wptm_table_options as c WHERE c.id_table = %d AND c.option_name = %s', (int)$id, 'attachment_file'));

        if ($result && $result !== null) {
            $attachments = json_decode($result->option_value, true);
            return $attachments;
        } else {
            return array();
        }
    }

    /**
     * Update attachment in table when read excel file
     *
     * @param integer $id     Table id
     * @param array   $value  Value option
     * @param boolean $update Update or insert
     *
     * @return false|integer
     */
    public static function updateAttachmentFileInTable($id, $value, $update)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wptm_table_options';
        if ($update) {
            $result = $wpdb->update(
                $table,
                array(
                    'option_value' => json_encode($value)
                ),
                array(
                    'option_name' => 'attachment_file',
                    'id_table' => $id
                )
            );
            return $result;
        }

        $result = $wpdb->insert(
            $table,
            array(
                'option_name' => 'attachment_file',
                'id_table' => $id,
                'option_value' => json_encode($value)
            )
        );
        return $result;
    }

    /**
     * User Role
     *
     * @param array $userRoles All role of user
     *
     * @return array
     */
    public static function userRole($userRoles)
    {
        $data =array(
            'wptm_access_category' => false,
            'wptm_edit_category' => false,
            'wptm_edit_own_category' => false,
            'wptm_create_category' => false,
            'wptm_delete_category' => false,
            'wptm_access_database_table' => false,
            'wptm_edit_tables' => false,
            'wptm_edit_own_tables' => false,
            'wptm_create_tables' => false,
            'wptm_delete_tables' => false
        );
        return array_merge($data, $userRoles);
    }
}
