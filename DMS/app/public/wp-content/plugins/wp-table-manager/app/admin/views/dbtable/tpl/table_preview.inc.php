<p><?php esc_html_e('Preview the 50 first result rows', 'wptm'); ?></p>
<table>

    <thead>
    <tr>
        <?php foreach ($headers as $header) { ?>
            <th><?php echo esc_html($header); ?></th>
        <?php } ?>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($result as $i => $row) { ?>
        <?php
        if ($i >= 50) {
            break;
        }?>
        <tr class="<?php
        if ((int)$i % 2 === 0) {
            echo 'odd';
        } ?>">
            <?php foreach ($row as $cell) { ?>
                <td><?php echo esc_html($cell); ?></td>
            <?php } ?>
        </tr>
    <?php } ?>
    </tbody>

</table>