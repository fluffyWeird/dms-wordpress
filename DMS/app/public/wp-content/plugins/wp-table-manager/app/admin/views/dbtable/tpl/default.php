<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */


use Joomunited\WPFramework\v1_0_6\Factory;

if (!current_user_can('wptm_access_category')) {
    wp_die(esc_attr__("You don't have permission to view this page", 'wptm'));
}

$wptm_list_url = admin_url('admin.php?page=wptm&');
?>
    <div id="mybootstrap" class="wptm-db">
    <div id="pwrapper">
        <div id="wptmreview">
            <?php if (empty($this->id_table)) : ?>
                <h2><?php esc_html_e('Table Creation Wizard', 'wptm'); ?></h2>
            <?php elseif (!empty($this->id_table)) : ?>
                <h2><?php esc_html_e('Database Table Wizard', 'wptm'); ?></h2>
            <?php endif; ?>
            <h3><?php esc_html_e('Please choose the MySQL data which will be used to create a table', 'wptm'); ?></h3>
        </div>
        <div id="mainTabContent" class="tab-content">
            <div class="step_animation">
                <?php if (empty($this->id_table)) :?>
                    <div class="full_width step_one">
                <?php elseif (!empty($this->id_table)) :?>
                    <div class="full_width step_one wptm_hiden" style="left: -100%; opacity: 0;">
                <?php endif;?>
                    <div class="wptm_container wptm_mysql_tables">
                        <label
                                for="wptm_mysql_tables"><span><?php esc_html_e('Database tables selection', 'wptm'); ?></span></label>
                        <div class="wptm_container_content">
                            <i class="search_table mi mi-search"></i>
                            <input class="observeChanges search_table" type="text" name="page" value=""
                                   placeholder="Search table" data-search="wptm_mysql_tables">
                            <div id="wptm_mysql_tables" data-scrollbar class="uploader" style="margin-top: 15px;">
                                <?php foreach ($this->mysql_tables as $mysql_table) { ?>
                                    <div data-value="<?php echo esc_html($mysql_table); ?>" class="mysql_table mysql_option
                                    <?php
                                    if (in_array($mysql_table, $this->selected_tables)) {
                                        echo ' active';
                                    };
                                    ?>">
                                        <span><?php echo esc_html($mysql_table); ?></span>
                                    </div>
                                <?php } ?>
                                <div style="clear: both"></div>
                            </div>
                            <div class="full_width mb-0 align-right">
                                <a href="#wptm_mysql_tables_columns_wrapper"
                                   class="orange-button text-upper btn-next-step"><?php esc_html_e('Next, select columns', 'wptm'); ?></a>
                            </div>
                        </div>
                    </div>

                    <div class="wptm_container wptm_mysql_tables_columns" id="wptm_mysql_tables_columns_wrapper">
                        <label for="wptm_mysql_tables_columns">
                            <span><?php esc_html_e('Database columns selection', 'wptm'); ?></span>
                        </label>
                        <div class="wptm_container_content">
                            <i class="search_table mi mi-search"></i>
                            <input class="observeChanges search_table" type="text" name="page" value=""
                                   placeholder="Search Columns" data-search="wptm_mysql_tables_columns">
                            <div id="wptm_mysql_tables_columns" class="active" data-scrollbar style="margin-top: 15px">
                                <div class="uploader">
                                    <?php if (count($this->availableColumns)) { ?>
                                        <?php foreach ($this->availableColumns as $column) { ?>
                                            <div data-value="<?php echo esc_html($column) ?>" class="mysql_option mysql_column
                                            <?php echo in_array($column, $this->selected_columns) ? ' active' : ''; ?>">
                                                <span><?php echo esc_html($column); ?></span>
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <div class="please_select_table">
                                            <?php esc_html_e('Please select the tables first', 'wptm'); ?>
                                        </div>
                                    <?php } ?>
                                    <div style="clear: both"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wptm_define_mysql_relations wptm_container" style="<?php
                    if (empty($this->join_rules)) {
                        echo 'display: none';
                    } ?>">
                        <div class="wptm_container_content uploader mysqlRelationsContainer">
                            <label
                                    class="full_width"><span><?php esc_html_e('Define the relations between tables', 'wptm'); ?></span></label>
                            <?php if (!empty($this->join_rules)) :
                                foreach ($this->join_rules as $join_rule) { ?>
                                    <div class="mysql_table_blocks">
                                        <span class="relationInitiatorTable dbtbl-tooltip" title="<?php echo esc_attr($join_rule->initiator_table); ?>">
                                            <?php echo esc_html($join_rule->initiator_table); ?>.
                                        </span>
                                        <select class="relationInitiatorColumn"
                                                data-table="<?php echo esc_html($join_rule->initiator_table); ?>">
                                            <option value=""></option>
                                            <?php foreach ($this->sorted_columns[$join_rule->initiator_table] as $key => $column) {
                                                $col = str_replace($join_rule->initiator_table . '.', '', $column); ?>
                                                <option value="<?php echo esc_html($col); ?>"
                                                    <?php
                                                    if ((string)$join_rule->initiator_column === (string)$col) {
                                                        echo 'selected';
                                                    } ?> >
                                                    <?php echo esc_html($col); ?></option>
                                            <?php } ?>

                                        </select>
                                        <div>=</div>
                                        <select class="relationConnectedColumn"
                                                data-table="<?php echo esc_html($join_rule->initiator_table); ?>">
                                            <option value=""></option>
                                            <?php
                                            foreach ($this->sorted_columns as $tbl => $columns) {
                                                if ((string)$tbl !== (string)$join_rule->initiator_table) {
                                                    foreach ($this->sorted_columns[$tbl] as $column) { ?>
                                                        <option value="<?php echo esc_html($column); ?>"
                                                            <?php
                                                            if ((string)$join_rule->connected_column === (string)$column) {
                                                                echo 'selected';
                                                            } ?> >
                                                            <?php echo esc_html($column); ?></option>
                                                        <?php
                                                    }
                                                }
                                            } ?>
                                        </select>
                                        <span><?php esc_html_e('mySQL join', 'wptm'); ?></span>
                                        <select class="innerjoin">
                                            <option value="left"><?php esc_html_e('left', 'wptm'); ?></option>
                                            <option value="inner"><?php esc_html_e('inner', 'wptm'); ?></option>
                                        </select>
                                    </div>
                                    <?php
                                }
                            endif; ?>
                        </div>
                    </div>

                    <div class="wptm_define_mysql_conditions wptm_container">
                        <div class="wptm_container_content">
                            <div class="d-flex flex-justify-sb flex-align-center">
                                <label for="wptm_mysql_conditions"><span><?php esc_html_e('Data display conditions', 'wptm'); ?></span></label>
                                <div class="d-flex flex-align-center">
                                    <a class="button wptm_active"
                                       id="wptm_mysql_add_where_condition"><?php esc_html_e('Add', 'wptm'); ?></a>
                                </div>
                            </div>

                            <div class="uploader mysqlConditionsContainer full_width"
                                 style="margin: 0;display: block">
                                <?php
                                if (isset($this->params->where_conditions)) :
                                    $count = count($this->params->where_conditions);
                                    for ($i = 0; $i < $count; $i++) :
                                        $where_conditions = $this->params->where_conditions[$i];
                                        ?>
                                        <div class="post_where_blocks">
                                            <div class="full_width">
                                                <select class="whereConditionColumn">
                                                    <option value=""></option>
                                                    <?php foreach ($this->availableColumns as $column) : ?>
                                                        <option value="<?php echo esc_html($column) ?>" <?php echo $where_conditions->column === $column ? ' selected' : ''; ?>><?php echo esc_html($column) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="button-secondary deleteConditionPosts"
                                                        style="line-height: 26px; font-size: 26px"><span
                                                            class="dashicons dashicons-no"></span></button>
                                            </div>
                                            <div class="full_width" style="width: 96%">
                                                <select class="whereOperator"
                                                        value="<?php echo esc_attr($where_conditions->operator); ?>">
                                                    <option value="eq" <?php echo esc_attr($where_conditions->operator === 'eq' ? 'selected' : '') ?>>
                                                        =
                                                    </option>
                                                    <option value="gt" <?php echo esc_attr($where_conditions->operator === 'gt' ? 'selected' : '') ?>>
                                                        &gt;
                                                    </option>
                                                    <option value="gtoreq" <?php echo esc_attr($where_conditions->operator === 'gtoreq' ? 'selected' : '') ?>>
                                                        &gt;=
                                                    </option>
                                                    <option value="lt" <?php echo esc_attr($where_conditions->operator === 'lt' ? 'selected' : '') ?>>
                                                        &lt;
                                                    </option>
                                                    <option value="ltoreq" <?php echo esc_attr($where_conditions->operator === 'ltoreq' ? 'selected' : '') ?>>
                                                        &lt;=
                                                    </option>
                                                    <option value="neq" <?php echo esc_attr($where_conditions->operator === 'neq' ? 'selected' : '') ?>>
                                                        &lt;&gt;
                                                    </option>
                                                    <option value="like" <?php echo esc_attr($where_conditions->operator === 'like' ? 'selected' : '') ?>>
                                                        LIKE
                                                    </option>
                                                    <option value="plikep" <?php echo esc_attr($where_conditions->operator === 'plikep' ? 'selected' : '') ?>>
                                                        %LIKE%
                                                    </option>
                                                    <option value="in" <?php echo esc_attr($where_conditions->operator === 'in' ? 'selected' : '') ?>>
                                                        IN
                                                    </option>
                                                </select>

                                                <input type="text"
                                                       value="<?php echo esc_attr($where_conditions->value); ?>"/>
                                            </div>
                                        </div>
                                    <?php endfor;
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="wptm_define_mysql_grouping wptm_container">
                        <div class="wptm_container_content">
                            <div class="d-flex flex-justify-sb flex-align-center">
                                <label
                                        for="wptm_posts_grouping"><span><?php esc_html_e('Data group rules', 'wptm'); ?></span></label>
                                <div  class="d-flex flex-align-center">
                                    <a class="button wptm_active"
                                       id="wptm_mysql_add_grouping_rule"><?php esc_html_e('Add', 'wptm'); ?></a>
                                </div>
                            </div>

                            <div class="uploader mysqlGroupingContainer full_width"
                                 style="margin: 0;display: block">
                                <?php
                                if (isset($this->params->grouping_rules)) :
                                    $count = count($this->params->grouping_rules);
                                    for ($i = 0; $i < $count; $i++) :
                                        $grouping_rules = $this->params->grouping_rules[$i];
                                        ?>
                                        <div class="post_grouping_rule_blocks">
                                            <span><?php esc_html_e('Group by ', 'wptm'); ?></span>

                                            <select class="groupingRuleColumn">
                                                <option value=""></option>
                                                <?php foreach ($this->selected_columns as $column) : ?>
                                                    <option value="<?php echo esc_html($column) ?>" <?php echo $grouping_rules === $column ? ' selected' : ''; ?>><?php echo esc_html($column) ?></option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="button-secondary deleteGroupingRulePosts"
                                                    style="line-height: 26px; font-size: 26px"><span
                                                        class="dashicons dashicons-no"></span></button>
                                        </div>
                                    <?php endfor;
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="wptm_container" id="wptm_mysql_default_ordering">
                        <div class="wptm_container_content">
                            <label for="wptm_mysql_default_ordering_column"
                                   class="full_width"><span><?php esc_html_e('Data default ordering', 'wptm'); ?></span></label>
                            <select id="wptm_mysql_default_ordering_column" class="full_width" style="margin-top: 10px;">
                                <?php
                                if (count($this->selected_columns) > 0) {
                                    foreach ($this->selected_columns as $column) { ?>
                                        <option value="<?php echo esc_html($column); ?>"
                                            <?php
                                            if ((string)$column === (string)$this->params->default_ordering) {
                                                echo ' selected';
                                            }; ?>>
                                            <?php echo esc_html($column); ?>
                                        </option>
                                    <?php }
                                }
                                ?>
                            </select>
                            <div id="wptm_mysql_default_ordering_dir" class="full_width">
                                <input id="default_ordering_dir_asc" type="radio" name="default_ordering_dir" value="asc"
                                    <?php
                                    if ('asc' === (string)$this->default_ordering_dir) {
                                        echo ' checked';
                                    }; ?>>
                                <label for="default_ordering_dir_asc"> Ascending</label>
                                <input id="default_ordering_dir_desc" type="radio" name="default_ordering_dir" value="desc"
                                    <?php
                                    if ('desc' === (string)$this->default_ordering_dir) {
                                        echo ' checked';
                                    }; ?>>
                                <label for="default_ordering_dir_desc"> Descending</label>
                            </div>
                        </div>
                    </div>

<!--                    --><?php //if (!$this->id_table) { ?>
                        <div class="wptm_container">
                            <div class="wptm_container_content">
                                <label
                                        for="wptm_mysql_table_pagination"><span><?php esc_html_e('Activate pagination', 'wptm'); ?></span></label>
                                <div style="float: right;margin-right: 15px;line-height: inherit;">
                                    <input class="switch-button"
                                           id="wptm_mysql_table_pagination" type="checkbox" value="" <?php echo isset($this->params->enable_pagination) && (int)$this->params->enable_pagination === 1 ? ' checked' : '';?>>
                                </div>

                            </div>
                        </div>

                        <div class="wptm_container" id="wptm_mysql_number_of_rows">
                            <div class="wptm_container_content">
                                <label for="wptm_mysql_number_of_rows"
                                       class="full_width"><span><?php esc_html_e('Number of rows', 'wptm'); ?></span></label>
                                <input id="wptm_mysql_number_of_rows_10" type="radio" name="wptm_mysql_number_of_rows"
                                       value="10" <?php echo isset($this->params->limit_rows) && (int)$this->params->limit_rows === 10 ? ' checked' : '';?>>
                                <label for="wptm_mysql_number_of_rows_10" style="font-weight: 400"> 10 rows</label>
                                <input id="wptm_mysql_number_of_rows_20" type="radio" name="wptm_mysql_number_of_rows"
                                       value="20"  <?php echo (isset($this->params->limit_rows) && (int)$this->params->limit_rows === 20) || !isset($this->params->limit_rows) ? ' checked' : '';?>>
                                <label for="wptm_mysql_number_of_rows_20" style="font-weight: 400"> 20 rows</label>
                                <input id="wptm_mysql_number_of_rows_40" type="radio" name="wptm_mysql_number_of_rows"
                                       value="40" <?php echo isset($this->params->limit_rows) && (int)$this->params->limit_rows === 40 ? ' checked' : '';?>>
                                <label for="wptm_mysql_number_of_rows_40" style="font-weight: 400"> 40 rows</label>
                                <input id="wptm_mysql_number_of_rows_0" type="radio" name="wptm_mysql_number_of_rows"
                                       value="0" <?php echo isset($this->params->limit_rows) && (int)$this->params->limit_rows === 0 ? ' checked' : '';?>>
                                <label for="wptm_mysql_number_of_rows_0" style="font-weight: 400"> All rows</label>
                            </div>
                        </div>
<!--                    --><?php //} ?>
                </div>

                <?php if (empty($this->id_table)) :?>
                    <div class="full_width step_two wptm_hiden">
                <?php elseif (!empty($this->id_table)) :?>
                    <div class="full_width step_two" style="left: 0%; opacity: 1;">
                <?php endif;?>
                    <div id="over_loadding_open_chart" class="loadding" style="display: none;"></div>
                    <div class="wptm_container full_width" style="display: grid;grid-template-columns: 49% 49%;grid-gap: 0;width: 100%">
                        <div class="wptm_container_content wptm_mysql_table_editing" style="display: inline-table;margin: 15px 10px 15px 0">
                            <label
                                    for="wptm_mysql_table_editing"><span><?php esc_html_e('Allow editing', 'wptm'); ?></span></label>
                            <div style="float: right;margin-right: 15px;line-height: inherit;">
                                <input class="switch-button"
                                       id="wptm_mysql_table_editing" type="checkbox" <?php echo isset($this->params->table_editing) && (int)$this->params->table_editing === 1 ? ' checked' : '';?> value="">
                            </div>
                        </div>
                        <div class="wptm_container_content" style="margin: 15px 0 15px 0">
                            <label for="wptm_mysql_conditions" style="margin-bottom: 10px"><span><?php esc_html_e('Base column for row identification', 'wptm'); ?></span>
                            <br/>
                            <span style="display:block;margin-left:5px;font-size: 12px;color: #404852;line-height: 18px;font-weight: 300;font-stretch: normal;font-style: inherit;letter-spacing: 0.6px;"><?php esc_html_e('Choose the column values from which will be used as row identifiers. MUST be a unique auto-increment integer on MySQL side so insert/edit/delete would work correctly! WP Table Manager try to guess the correct column by default', 'wptm'); ?></span>
                            </label>
                            <span id="column_key" class="popup_select wptm_select_box_before select_column_key"></span>
                            <ul class="wptm_select_box">
                            </ul>
                        </div>
                        <div class="wptm_container_content wptm_lock_columns wptm_hiden" style="display: inline-table;margin: 15px 10px 15px 0">
                            <label for="wptm_mysql_conditions" style="margin-bottom: 10px;font-size: 20px;"><span><?php esc_html_e('Select locked columns', 'wptm'); ?></span>
                            </label>
                            <div class="wptm_container_content">
                            </div>
                            <div class="wptm_lock_column wptm_hiden" style="line-height: 40px;border-top: 1px solid #cfcfcf">
                                <label for="wptm_lock_column"><span style="font-weight:400;font-size:1em;"></span></label>
                                <div style="float: right;margin-right: 15px;line-height: inherit;">
                                    <input id="wptm_lock_column" class="switch-button" type="checkbox" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wptm_container wptm_custom_mysql">
                        <div class="wptm_container_content" style="display: inline-block;width: 100%;box-sizing: border-box;">
                            <label for="wptm_custom_mysql"><span><?php esc_html_e('MYSQL Query', 'wptm'); ?></span></label>
                            <div id="wptm_custom_mysql" style="margin-top: 15px;">
                                <textarea id="jform_custom_mysql" cols="50" name="jform[jform_custom_mysql]"></textarea>
                            </div>
                            <input type="button" id="btn_apply_mysql" class="orange-button ju-button-while"
                                   value="<?php esc_html_e('APPLY QUERY', 'wptm'); ?>" style="opacity: 1;float: right;margin-top: 19px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="full_width sql_table_button" style="float: right">
                <input type="button" id="btn_previous" class="orange-button ju-button-while" style="max-width: unset;"
                       value="<?php esc_html_e('<< BACK TO DATABASE SELECTION', 'wptm'); ?>"/>
                <input type="button" id="btn_next" class="orange-button ju-button-while"
                       value="<?php esc_html_e('NEXT STEP >>', 'wptm'); ?>"/>
                <input type="button" id="btn_preview" class="orange-button ju-button-while"
                       value="<?php esc_html_e('PREVIEW', 'wptm'); ?>"/>
                <?php
                if ($this->id_table) { ?>
                    <input type="button" id="btn_tableUpdate" class="orange-button ju-button-while"
                           value="<?php esc_html_e('GO TO TABLE EDITION', 'wptm'); ?>"/>
                    <?php
                } else {
                    ?>
                    <input type="button" id="btn_tableCreate" class="orange-button"
                           value="<?php esc_html_e('CREATE TABLE', 'wptm'); ?>"/>
                <?php } ?>
            </div>

            <div id="wptm_mysql_review" class="full_width" style="float: left;width: 100%">
                <div class="uploader wptm_previewTable full_width" style="max-width: 100%">
                </div>
                <div class="wptm_hiden custom_title_column"></div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        <?php if (empty($this->id_table)) : ?>
            table_id = '';
        <?php elseif (!empty($this->id_table)) : ?>
            table_id = "<?php echo esc_attr($this->id_table); ?>";
        <?php endif; ?>
        id_cat = "<?php echo esc_attr($this->id_cat); ?>";
        ajaxurl = "<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>";
        adminurl = "<?php echo esc_url_raw(admin_url()); ?>";
        wptm_ajaxurl = "<?php echo esc_url_raw(Factory::getApplication('wptm')->getAjaxUrl()); ?>";
        wptm_dir = "<?php echo esc_url_raw(Factory::getApplication('wptm')->getBaseUrl()); ?>";
        constructedTableData = {};
        <?php if (!empty($this->params)) : ?>
        constructedTableData =<?php echo json_encode($this->params);?>;
        <?php endif; ?>
        <?php if (!empty($this->id_table)) : ?>
        constructedTableData.id_table = <?php echo esc_attr($this->id_table);?>;
        <?php endif; ?>
        <?php if (isset($this->params->priKey)) {?>
        constructedTableData.priKey = '<?php echo esc_attr($this->params->priKey);?>';
        <?php }?>
<!--        --><?php //if (isset($this->params->query)) {?>
        //constructedTableData.query = '<?php //echo $this->params->query;?>//';//no esc_attr '>'
<!--        --><?php //}?>
        <?php if (isset($this->params->customquery)) {?>
        constructedTableData.customquery = '<?php echo esc_attr($this->params->customquery);?>';
        <?php }?>
        constructedTableData.table_editing = <?php echo(isset($this->params->priKey) && isset($this->params->table_editing) ? esc_attr($this->params->table_editing) : 0);?>;
        constructedTableData.tables =<?php echo json_encode($this->selected_tables);?>;
        constructedTableData.enable_pagination = <?php echo(isset($this->params->enable_pagination) ? esc_attr($this->params->enable_pagination) : 1);?>;
        constructedTableData.limit_rows = <?php echo(isset($this->params->limit_rows) ? esc_attr($this->params->limit_rows) : 20);?>;
        <?php if (!empty($this->selected_columns)) {?>
        constructedTableData.mysql_columns =<?php echo json_encode($this->selected_columns);?>;
        <?php } ?>
    </script>

    <script type="text/x-handlebars-template" id="wptm-template-mysqlRelationBlock">
        <div class="mysql_table_blocks">
            <span class="relationInitiatorTable dbtbl-tooltip" title="{{table}}">{{table}}.</span>
            <select class="relationInitiatorColumn" data-table="{{table}}">
                <option value=""></option>
                {{#each columns}}
                <option value="{{this}}">{{this}}</option>
                {{/each}}
            </select>
            <div>=</div>
            <select class="relationConnectedColumn" data-table="{{table}}">
                <option value=""></option>
                {{#each other_table_columns}}
                <option value="{{this}}">{{this}}</option>
                {{/each}}
            </select>
            <span><?php esc_html_e('mySQL join', 'wptm'); ?></span>
            <select class="innerjoin">
                <option value="inner"><?php esc_html_e('inner', 'wptm'); ?></option>
                <option value="left"><?php esc_html_e('left', 'wptm'); ?></option>
            </select>
        </div>


    </script>

    <script type="text/x-handlebars-template" id="whereConditionTemplate">
        <div class="post_where_blocks">
            <div class="full_width">
                <select class="whereConditionColumn">
                    <option value=""></option>
                    {{#each post_type_columns}}
                    <option value="{{this}}">{{this}}</option>
                    {{/each}}
                </select>
                <button class="button-secondary deleteConditionPosts" style="line-height: 26px; font-size: 26px"><span
                            class="dashicons dashicons-no"></span></button>
            </div>
            <div class="full_width" style="width: 96%">
                <select class="whereOperator">
                    <option value="eq">=</option>
                    <option value="gt">&gt;</option>
                    <option value="gtoreq">&gt;=</option>
                    <option value="lt">&lt;</option>
                    <option value="ltoreq">&lt;=</option>
                    <option value="neq">&lt;&gt;</option>
                    <option value="like">LIKE</option>
                    <option value="plikep">%LIKE%</option>
                    <option value="in">IN</option>
                </select>

                <input type="text"/>
            </div>
        </div>
    </script>


    <script type="text/x-handlebars-template" id="groupingRuleTemplate">
        <div class="post_grouping_rule_blocks">
            <span><?php esc_html_e('Group by ', 'wptm'); ?></span>

            <select class="groupingRuleColumn">
                <option value=""></option>
                {{#each post_type_columns}}
                <option value="{{this}}">{{this}}</option>
                {{/each}}
            </select>

            <button class="button-secondary deleteGroupingRulePosts" style="line-height: 26px; font-size: 26px"><span
                        class="dashicons dashicons-no"></span></button>
        </div>
    </script>
<?php
if ($this->caninsert) :
    add_filter('show_admin_bar', '__return_false'); ?>
    <div id="wptm_bottom_toolbar">
        <div class="bottom_left_toolbar">
            <a class="wptm_back_list" href="<?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- link opend table when insert
            echo $wptm_list_url . 'noheader=1&caninsert=1';
            ?>">
                <?php esc_attr_e('Back to table list', 'wptm'); ?></a>
        </div>

        <div class="bottom_right_toolbar">
            <a id="inserttable" class="button button-primary button-big" data-type="table" href="javascript:void(0)"><?php esc_attr_e('Insert This Table', 'wptm'); ?></a>
        </div>
    </div>
    <style>
        html.wp-toolbar {
            padding-top: 0 !important;
        }
        #pwrapper {
            height: calc(100vh - 60px) !important;
            padding: 0 40px !important;
        }
        #wptmreview {
            margin-top: 40px;
        }
        #mybootstrap {
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        #wptm_bottom_toolbar {
            bottom: 0 !important;
        }
        .wptm_back_list {
            font-size: 14px;
        }
        #inserttable {
            margin-top: 12px !important;
        }
    </style>
    <script>
        var caninsert = true;
        var insert_chart = '<?php esc_attr_e('Insert This Chart', 'wptm') ?>';
        var insert_table = '<?php esc_attr_e('Insert This Table', 'wptm') ?>';
    </script>
<?php endif; ?>
    </div>
<?php

/**
 * OpenItem
 *
 * @param object $table         Categories
 * @param string $wptm_ajaxurl  Url wptm
 * @param string $wptm_db_table Url db_table
 *
 * @return string
 */
function openItem($table, $wptm_ajaxurl, $wptm_db_table)
{
    $content = '';
    $content .= '<tr class="dd-item" data-id-table="' . $table->id . '"
      data-role="' . (int)$table->author . '" data-position="' . (int)$table->position . '">';

    $content .= '<td class="dd-content table_name"><i class="wptm_icon_tables"></i>';
    $content .= '<div>';
    $content .= '<a class="t" href="' . $wptm_ajaxurl . 'id_table=' . $table->id . '" target="_blank"><span class="title dd-handle">' . $table->title . '</span></a>';
    $content .= '<a class="edit tooltip"></a>';
    $content .= '<a class="copy tooltip"></a>';
    $content .= '<a class="trash tooltip"></a>';
    $content .= '<a class="data_source tooltip"></a>';
    $content .= '</div>';
    $content .= '</td>';

    $content .= '<td>' . convertDate($table->modified_time) . '</td>';

    $content .= '<td class="dd-content author_table">';
    //role category
    $user = get_userdata((int)$table->author);
    if (isset($user->user_nicename)) {
        $content .= '<span>' . $user->user_nicename . '</span>';
    }
    $content .= '</td>';

    $content .= '<td class="dd-content shortcode"><div><span>[wptm id=' . $table->id . ']</span>';
    $content .= '<span class="button wptm_icon_tables copy_text tooltip"></span></div></td>';

    $content .= '</tr>';

    return $content;
}

/**
 * Function convert date string to date format
 *
 * @param string $date Date string
 *
 * @return string
 */
function convertDate($date)
{
    if (get_option('date_format', null) !== null) {
        $date = date_create($date);
        $date = date_format($date, get_option('date_format') . ' ' . get_option('time_format'));
    }
    return $date;
}
