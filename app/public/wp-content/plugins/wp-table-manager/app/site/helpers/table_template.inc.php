<?php
require_once dirname(WPTM_PLUGIN_FILE) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'wptmHelper.php';
$wptmHelper = new WptmHelper();
$r = 0;
$c = 0;
$rowNb = 0;

$date_formats = (!empty($configParams['date_formats'])) ? $configParams['date_formats'] : 'Y-m-d';
$date_formats = (!empty($style->table->date_formats)) ? $style->table->date_formats : $date_formats;
?>
<thead>
<tr>
    <?php
    foreach ($headers as $header) {
        ?>
        <th class="<?php echo esc_attr('dtr' . $r . ' dtc' . $c); ?>"><?php echo esc_attr($header); ?></th>
        <?php $c++;
    }
    ?>
</tr>
</thead>
<tbody>
<?php foreach ($result as $row) {
    $r++;
    $c = 0;
    $rowNb++;
    if ($rowNb <= (int)$style->table->freeze_row) { ?>
        <tr class=" row<?php echo esc_attr($rowNb); ?>">
        <?php
    } else { ?>
        <tr class="droptable_none row<?php echo esc_attr($rowNb); ?>">
        <?php
    } ?>
    <?php
    foreach ($row as $cell) {
        $dataSortAttr = '';
        $newDate = DateTime::createFromFormat($date_formats, $cell);
        if ($newDate !== false) {
            $dataSortAttr = 'data-sort="' . $newDate->getTimestamp() . '"';
        }
        ?>
        <td data-dtr="<?php echo esc_attr($r); ?>" <?php echo esc_attr($dataSortAttr); ?> data-dtc="<?php echo esc_attr($c); ?>"
            class="<?php echo esc_attr('dtr' . $r . ' dtc' . $c); ?>"><?php echo esc_attr($cell); ?></td>
        <?php $c++;
    }
    ?>
    </tr>
<?php } ?>
</tbody>
