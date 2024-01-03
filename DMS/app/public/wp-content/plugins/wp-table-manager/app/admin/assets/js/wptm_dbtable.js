/**
 * Wptm
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 * @package Wptm
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

jQuery(document).ready(function ($) {
    if (table_id === '')
        jQuery('title').text(wptmText.TABLE_CREATION_WIZARD_TITLE_TAG);
    else
        jQuery('title').text(wptmText.TABLE_EDIT_WIZARD_TITLE_TAG);

    var $mainTabContent = $('#mainTabContent');
    var $wptm_mysql_tables = $('#wptm_mysql_tables');
    var $wptm_mysql_tables_columns = $('#wptm_mysql_tables_columns');
    var $wptm_mysql_tables_columns_title = $('#wptm_mysql_tables_columns_title');
    var $wptm_mysql_tables_columns_title_list = $('#wptm_mysql_tables_columns_title table tbody');
    var $wptm_mysql_default_ordering = $('#wptm_mysql_default_ordering');
    var $wptm_mysql_table_pagination = $('#wptm_mysql_table_pagination');
    var $wptm_mysql_number_of_rows = $('#wptm_mysql_number_of_rows');
    var $wptm_define_mysql_relations = $('.wptm_define_mysql_relations');
    var $wptm_define_mysql_conditions = $('.wptm_define_mysql_conditions');
    var $wptm_define_mysql_grouping = $('.wptm_define_mysql_grouping');
    var $wptm_previewTable = $('.wptm_previewTable');
    var $wptm_mysqlRelationBlock = $('#wptm-template-mysqlRelationBlock');
    var $wptm_lock_columns = $mainTabContent.find('.wptm_lock_columns');

    var Wptm = {};

    var Scrollbar = window.Scrollbar;
    var smooth_scrollbar;
    smooth_scrollbar = Scrollbar.initAll({
        damping: 0.5,
        thumbMinSize: 10,
        alwaysShowTracks: true
    });
    // smooth_scrollbar[0].track.xAxis.element.remove();
    // smooth_scrollbar[0].track.yAxis.element.remove();

    if ($('#adminmenuwrap').length > 0) {
        $('#adminmenuwrap').addClass('smooth-scrollbar');
        Scrollbar.init(document.querySelector('#adminmenuwrap'), {
            damping: 0.5,
            thumbMinSize: 10,
            alwaysShowTracks: false
        });
    }

    // Add smooth scrolling to all links
    $("a.btn-next-step").on('click', function (event) {
        // Make sure this.hash has a value before overriding default behavior
        if (this.hash !== "") {
            // Prevent default anchor click behavior
            event.preventDefault();

            // Store hash
            var hash = this.hash;

            // Using jQuery's animate() method to add smooth page scroll
            // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
            $('html, body').animate({
                scrollTop: $(hash).offset().top
            }, 500, function () {

                // Add hash (#) to URL when done scrolling (default click behavior)
                window.location.hash = hash;
            });
        } // End if
    });

    if ($wptm_mysql_tables_columns.find('.uploader').outerHeight() > $wptm_mysql_tables_columns.outerHeight()) {
        $wptm_mysql_tables_columns.addClass('margin_left');
    }

    /**
    Select mysql table
    */
    $wptm_mysql_tables.find('.mysql_table').on('click', function () {
        var that = $(this);
        if (that.hasClass('active')) {
            that.removeClass('active');
        } else {
            that.addClass('active');
        }

        //delete customquery
        delete constructedTableData.query;
        delete constructedTableData.customquery;

        var selected_tables = {};
        $wptm_mysql_tables.find('.mysql_table.active').each(function (i) {
            selected_tables[i] = $(this).data('value');
        });
        constructedTableData.tables = selected_tables;
        jsonVar = {
            'tables': selected_tables
        };

        $.ajax({
            url: wptm_ajaxurl + "task=dbtable.changeTables",
            type: "POST",
            data: jsonVar
        }).done(function (data) {
            var result = jQuery.parseJSON(data);
            if (result.response === true) {
                var columns;
                columns = result.datas.columns;

                $wptm_mysql_tables_columns.find('.uploader').html('');
                var col;
                for (i = 0; i < columns.all_columns.length; i++) {
                    col = columns.all_columns[i];
                    $wptm_mysql_tables_columns.find('.uploader').append('<div data-value="' + col + '" class="mysql_option mysql_column">' +
                        '<span>' + col + '</span></div>');
                }
                $.each(constructedTableData.mysql_columns, function () {
                    $wptm_mysql_tables_columns.find('.uploader .mysql_option[data-value="' + this + '"]').addClass('active');
                });
                var curr_columns = [];
                $wptm_mysql_tables_columns.find('.mysql_column.active').each(function (idx, obj) {
                    curr_columns.push($(obj).data('value'));
                });
                constructedTableData.mysql_columns = curr_columns;
                render_custom_title_column();

                $wptm_define_mysql_relations.find('div.mysqlRelationsContainer > div').remove();
                $wptm_define_mysql_relations.hide();

                if ($wptm_mysql_tables_columns.find('.uploader').outerHeight() > $wptm_mysql_tables_columns.outerHeight()) {
                    $wptm_mysql_tables_columns.addClass('margin_left');
                } else {
                    $wptm_mysql_tables_columns.removeClass('margin_left');
                }

                if (_.size(selected_tables) > 1) {
                    var conditions_columns = {post_type_columns: []};
                    // Generate HTML block for relations constructor
                    for (var i in columns.sorted_columns) {
                        var mysql_table_block = {table: i, columns: [], other_table_columns: []};
                        for (var col_index in columns.sorted_columns[i]) {
                            conditions_columns.post_type_columns.push(columns.sorted_columns[i][col_index]);
                        }
                        for (var j in columns.sorted_columns) {
                            if (i === j) {
                                for (var k in columns.sorted_columns[i]) {
                                    mysql_table_block.columns.push(columns.sorted_columns[i][k].replace(i + '.', ''));
                                }
                                continue;
                            }
                            for (var k in columns.sorted_columns[j]) {
                                mysql_table_block.other_table_columns.push(columns.sorted_columns[j][k]);
                            }
                        }

                        var mysqlRelationBlockTemplate = $wptm_mysqlRelationBlock.html();
                        var template = Handlebars.compile(mysqlRelationBlockTemplate);
                        var relationBlockHtml = template(mysql_table_block);
                        $wptm_define_mysql_relations.find('div.mysqlRelationsContainer').append(relationBlockHtml);

                    }
                    $wptm_define_mysql_relations.show();
                    $wptm_define_mysql_conditions.find('.whereConditionColumn').each(function() {
                        var old_val = $(this).val();
                        $(this).empty();
                        for(var i in conditions_columns.post_type_columns) {
                            $(this).append('<option value="' + conditions_columns.post_type_columns[i] + '" >' + conditions_columns.post_type_columns[i] + '</option>');
                        }
                        $(this).val(old_val);
                    });
                }
                if (_.size(selected_tables) > 0) {
                    $('.please_select_table').hide();
                } else {
                    $('.please_select_table').show();
                    $wptm_define_mysql_conditions.find('.whereConditionColumn').each(function() {
                        $(this).empty();
                    });
                }
            } else {
                alert(result.response);
            }
        });
    });
    $(document).on('keyup', '.search_table', function (e) {
        var key_search = $(this).val();
        search_items($('#' + $(this).data('search')).find('.mysql_option'), key_search, 'wptm_hiden', false);
    });

    /**
     * Add the selected MySQL columns to the constructed table data
     */
    $wptm_mysql_tables_columns.on('click', '.mysql_column', function () {
        //delete customquery
        delete constructedTableData.query;
        delete constructedTableData.customquery;

        var that = $(this);
        if (that.hasClass('active')) {
            that.removeClass('active');
            var index = constructedTableData.mysql_columns.indexOf($(this).data('value'));
            if (index > -1) {
                constructedTableData.mysql_columns.splice(index, 1);
            }
        } else {
            that.addClass('active');
            if (typeof constructedTableData.mysql_columns === 'undefined') {
                constructedTableData.mysql_columns = []
            }
            constructedTableData.mysql_columns.push($(this).data('value'));
        }
        render_custom_title_column();
    });
    $('.wptm_mysql_tables_columns').find('ul li:not(.border_active)').on('click', function () {
        if (!$(this).hasClass('active')) {
            var left = $(this).position().left;
            $(this).siblings('.border_active').animate({'left': left + 'px'});
        }
    })

    /**
     * Render content in set column titles and add value for that
     */
    function render_custom_title_column() {
        constructedTableData.columnCount = _.size(constructedTableData.mysql_columns);
        $wptm_mysql_tables_columns_title.find('.column_title').appendTo($('.custom_title_column'));
        old_default_ordering = $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_column').val();
        $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_column').empty();
        $('.wptm_define_mysql_grouping').find('.groupingRuleColumn').empty();
        if (constructedTableData.columnCount > 0) {
            for (var i = 0; i < constructedTableData.columnCount; i++) {
                column_id = constructedTableData.mysql_columns[i].replace(".", "_");
                $('.custom_title_column').find('#wptm_row_' + column_id).appendTo($wptm_mysql_tables_columns_title_list);
                if ($wptm_mysql_tables_columns_title_list.find('#wptm_column_' + column_id).length === 0) {
                    $wptm_mysql_tables_columns_title_list.append('<tr class="wptm_row column_title" id="wptm_row_' + column_id + '"><td><label>' + constructedTableData.mysql_columns[i] + ' </label></td><td><input type="text" name="" id="wptm_column_' + column_id + '" class="" value=""  /></td></tr>');
                }

                $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_column').append('<option value="' + constructedTableData.mysql_columns[i] + '" >' + constructedTableData.mysql_columns[i] + '</option>');
                $('.wptm_define_mysql_grouping').find('.groupingRuleColumn').each(function() {
                    $(this).append('<option value="' + constructedTableData.mysql_columns[i] + '" >' + constructedTableData.mysql_columns[i] + '</option>');
                });
            }

            $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_column').val(old_default_ordering);
        }
    }

    /**
     * Add a "WHERE" condition to the WP POSTS based table
     */
    $wptm_define_mysql_conditions.on('click', '#wptm_mysql_add_where_condition', function (e) {
        e.preventDefault();
        var whereBlockTemplate = $('#whereConditionTemplate').html();
        var template = Handlebars.compile(whereBlockTemplate);

        var where_block = {
            post_type_columns: $wptm_mysql_tables_columns.find('div.mysql_column').map(function () {
                return $(this).data('value');
            }).toArray()
        };
        var whereBlockHtml = template(where_block);
        $wptm_define_mysql_conditions.find('div.mysqlConditionsContainer').append(whereBlockHtml);

    });
    /**
     * Delete a "WHERE" condition
     */
    $(document).on('click', 'button.deleteConditionPosts', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        $(this).closest('div.post_where_blocks').remove();
    });

    /**
     * Add a grouping rule for MySQL based tables
     */
    $wptm_define_mysql_grouping.on('click', '#wptm_mysql_add_grouping_rule', function (e) {
        e.preventDefault();

        // Generate HTML block for the grouping rule constructor
        var grouping_rule_block = {post_type_columns: []};

        grouping_rule_block.post_type_columns = $wptm_mysql_tables_columns.find('div.mysql_column.active').map(function () {
            return $(this).data('value');
        }).toArray();

        var groupingRuleBlockTemplate = $('#groupingRuleTemplate').html();
        var template = Handlebars.compile(groupingRuleBlockTemplate);
        var groupingRuleHtml = template(grouping_rule_block);

        $wptm_define_mysql_grouping.find('div.mysqlGroupingContainer').append(groupingRuleHtml);
    });
    /**
     * Delete a grouping rule
     */
    $(document).on('click', 'button.deleteGroupingRulePosts', function (e) {
        e.preventDefault();
        $(this).closest('div.post_grouping_rule_blocks').remove();
    });

    /**
     * check column and table selected
     */
    function check_selected_table_and_column() {
        var flag = true;
        var number_table = $('#wptm_mysql_tables').find('.mysql_table.active');
        var number_column = $('#wptm_mysql_tables_columns').find('.mysql_column.active');
        if (number_table.length < 1) {
            flag = false;
        }
        if (number_column.length < 1) {
            flag = false;
        }
        return flag;
    }

    function check_duplicate_column_name() {
        var names = [];
        var obj = {};
        var message = wptmText.THE_TITLES_OF_THE_COLUMNS_MAY_NOT_BE_SAME;
        var column_wrapper = $('#wptm_mysql_tables_columns_title');
        $('.columns_title-error').remove();
        column_wrapper.find('table tr').each(function() {
            var curr = $(this);
            if(curr.find('input[type="text"]').val() !== '') {
                names.push(curr.find('input[type="text"]').val());
            }
        });
        names.forEach(function (item) {
            if(!obj[item])
                obj[item] = 0;
            obj[item] += 1;
        });
        for (var prop in obj) {
            if(obj[prop] >= 2) {
                column_wrapper.parent().prepend('<div class="columns_title-error"><span style="background: #D54E21;color: #fff;font-size: 0.7em;padding: 0 5px;">'+message+'</span></div>');
                return true;
            }
        }

        return false;
    }

    function set_constructed_table_data() {
        constructedTableData.join_rules = [];
        constructedTableData.where_conditions = [];
        constructedTableData.grouping_rules = [];
        /*custom title */
        constructedTableData.custom_titles = [];
        var i;
        for (i = 0; i < _.size(constructedTableData.mysql_columns); i++) {
            column_id = constructedTableData.mysql_columns[i].replace(".", "_");
            if ($wptm_mysql_tables_columns_title.find('#wptm_column_' + column_id).val()) {
                constructedTableData.custom_titles.push($wptm_mysql_tables_columns_title.find('#wptm_column_' + column_id).val());
            } else {
                constructedTableData.custom_titles.push(constructedTableData.mysql_columns[i]);
            }
        }

        constructedTableData.default_ordering = $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_column').val();
        constructedTableData.default_ordering_dir = $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_dir input:checked').val();
        constructedTableData.enable_pagination = $wptm_mysql_table_pagination.is(":checked") ? 1 : 0;

        constructedTableData.limit_rows = $wptm_mysql_number_of_rows.find('input:checked').val();
        /**
         * Join rules
         */
        $wptm_define_mysql_relations.find('.mysqlRelationsContainer').find('.mysql_table_blocks').each(function () {
            var join_rule = {};
            join_rule.initiator_table = $(this).find('select.relationInitiatorColumn').data('table');
            join_rule.initiator_column = $(this).find('select.relationInitiatorColumn').val();
            join_rule.connected_column = $(this).find('select.relationConnectedColumn').val();
            join_rule.type = $(this).find('select.innerjoin').val();
            constructedTableData.join_rules.push(join_rule);
        });

        /**
         * Where block
         */
        $wptm_define_mysql_conditions.find('.mysqlConditionsContainer').find('.post_where_blocks').each(function () {
            var where_condition = {};
            where_condition.column = $(this).find('select.whereConditionColumn').val();
            where_condition.operator = $(this).find('select.whereOperator').val();
            where_condition.value = $(this).find('input[type="text"]').val();
            constructedTableData.where_conditions.push(where_condition);
        });

        /**
         * Grouping rules
         */
        $wptm_define_mysql_grouping.find('.mysqlGroupingContainer').find('.post_grouping_rule_blocks select').each(function () {
            constructedTableData.grouping_rules.push($(this).val());
        });
    }

    function search_items($option, text, classText, compare) {
        $option.each(function () {
            $(this).removeClass(classText);
            if (text !== '') {
                var value = false;
                if ($(this).data('value').search(text) !== -1) {
                    value = compare ? true : false;
                } else {
                    value = compare ? false : true;
                }
                if (value) {
                    $(this).addClass(classText);
                }
            }
        });
    }

    //mysql query
    var textArea = document.getElementById("jform_custom_mysql");
    var myCssEditor;

    $mainTabContent.find('.step_animation').css({'display': 'grid'});
    $('#btn_previous').addClass('wptm_hiden');
    $('#btn_preview').addClass('wptm_hiden');
    $('#btn_tableCreate').addClass('wptm_hiden');
    $('#btn_tableUpdate').addClass('wptm_hiden');

    function setTableIndexKey() {
        $mainTabContent.find('#column_key').data('value', '');
        var $wptm_lock_columns_content;
        $wptm_lock_columns.find('.wptm_container_content').contents().remove();
        $.ajax({
            url: wptm_ajaxurl + "task=dbtable.getTableColumnsOptions",
            data: {
                tableList: typeof constructedTableData.mysql_columns !== 'undefined' && constructedTableData.mysql_columns.length > 0 ? constructedTableData.tables : {},
                query: constructedTableData.query
            },
            type: 'post',
            dataType: 'json',
            success: function (result) {
                if (result.response && result.datas !== false) {
                    $mainTabContent.find('#column_key').siblings('ul').contents('li').remove();
                    $mainTabContent.find('#column_key').siblings('ul').data('value', '');
                    $mainTabContent.find('#column_key').parent().addClass('wptm_hiden');
                    constructedTableData.column_options = $.extend({}, {});
                    constructedTableData.tables_list = $.extend({}, result.datas['table']);

                    if (typeof constructedTableData.lock_columns !== 'undefined') {
                        var lock_columns = $.extend([], constructedTableData.lock_columns);
                    }
                    constructedTableData.lock_columns = [];

                    // var prikey_in_tables = false;
                    var i = 0, j = 0;
                    var html, column;
                    for (column in result.datas['result']) {
                        html = '';
                        var key = result.datas['result'][column].table + '.' + result.datas['result'][column].Field;
                        var index = result.datas['column'].indexOf(key);
                        if (typeof constructedTableData.mysql_columns !== 'undefined' && !constructedTableData.mysql_columns.includes(key)) {
                            continue;
                        }

                        constructedTableData.column_options[index] = {};
                        constructedTableData.column_options[index].Extra = result.datas['result'][column].Extra;
                        constructedTableData.column_options[index].Default = result.datas['result'][column].Default;
                        constructedTableData.column_options[index].priKey = result.datas['result'][column].priKey;
                        constructedTableData.column_options[index].notNull = result.datas['result'][column].notNull;
                        constructedTableData.column_options[index].canEdit = result.datas['result'][column].canEdit;
                        constructedTableData.column_options[index].Type = result.datas['result'][column].Type;
                        constructedTableData.column_options[index].Field = result.datas['result'][column].Field;
                        constructedTableData.column_options[index].table = result.datas['result'][column].table;

                        $wptm_lock_columns_content = $wptm_lock_columns.find('.wptm_hiden').clone();
                        $wptm_lock_columns_content.attr('data-value', index);
                        $wptm_lock_columns_content.find('span').text(key);

                        if (typeof lock_columns !== 'undefined' && lock_columns[index] == 1) {
                            $wptm_lock_columns_content.find('.switch-button').prop("checked", true);
                            constructedTableData.lock_columns[index] = 1;
                        } else {
                            constructedTableData.lock_columns[index] = 0;
                        }

                        for (j = index; j >= 0; j--) {
                            if ($mainTabContent.find('.wptm_lock_columns .wptm_container_content .wptm_lock_column[data-value="' + j + '"]').length > 0) {
                                $mainTabContent.find('.wptm_lock_columns .wptm_container_content .wptm_lock_column[data-value="' + j + '"]').after($wptm_lock_columns_content.removeClass('wptm_hiden'));
                                break;
                            } else {
                                $mainTabContent.find('.wptm_lock_columns .wptm_container_content').append($wptm_lock_columns_content.removeClass('wptm_hiden'));
                            }
                        }


                        html += '<li data-value="' + key + '">' + key + '</li>';
                        if (typeof constructedTableData.priKey !== 'undefined' && key === constructedTableData.priKey) {
                            constructedTableData.priKey = key;
                            $mainTabContent.find('#column_key').data('value', constructedTableData.priKey).text(key);
                            // $mainTabContent.find('.wptm_mysql_table_editing').removeClass('wptm_hiden');
                            // prikey_in_tables = true;
                        } else if (typeof constructedTableData.priKey === 'undefined' || !constructedTableData.priKey) {
                            constructedTableData.priKey = key;
                            $mainTabContent.find('#column_key').data('value', constructedTableData.priKey).text(key);
                        }

                        i++;
                        if (result.datas['result'][column].Extra === 'auto_increment' || result.datas['result'][column].priKey === true) {
                            $(html).prependTo($mainTabContent.find('#column_key').siblings('ul'));
                        } else {
                            $(html).appendTo($mainTabContent.find('#column_key').siblings('ul'));
                        }
                    }
                    // if (!prikey_in_tables) {
                    //     delete constructedTableData.priKey;
                    // }
                    console.log(constructedTableData);

                    custom_select_box.call($mainTabContent.find('#column_key'), $, function () {
                    //     var value_column_key = this.data('value');
                    //     if (typeof value_column_key !== 'undefined' && value_column_key !== '') {
                    //         $mainTabContent.find('.wptm_mysql_table_editing').removeClass('wptm_hiden');
                    //     }
                    });

                    if (constructedTableData.table_editing == 1) {
                        $mainTabContent.find('#column_key').parent().removeClass('wptm_hiden');
                        $mainTabContent.find('.wptm_lock_columns').removeClass('wptm_hiden');
                    }

                    return result.datas;
                } else {
                    return false;
                }
            },
            error: function (jqxhr, textStatus, error) {
                alert(textStatus);
            }
        })
    }

    function show_button_submit (action, params) {
        if (typeof constructedTableData.lock_columns == 'undefined') {
            constructedTableData.lock_columns = [];
        }

        $wptm_lock_columns.find('.wptm_container_content .wptm_lock_column').each(function (i, e) {
            var data = $(this).data('value');
            constructedTableData.lock_columns[data] = $(this).find('.switch-button').is(":checked") ? 1 : 0;
        });

        switch (action) {
            case 'next':
                if (!$mainTabContent.find('.step_animation .step_one').hasClass('wptm_hiden')) {
                    $mainTabContent.find('.step_animation .step_one').animate({'left': '-=100%', 'opacity': '0'}, 1000, function () {
                        $mainTabContent.find('.step_animation .step_two').removeClass('wptm_hiden').animate({'opacity': '1','left': '-=100%'}, 700);
                        $mainTabContent.find('.step_animation .step_one').addClass('wptm_hiden');
                    }).delay(200).animate({'opacity': '0'}, 500, function () {
                        $mainTabContent.find('.sql_table_button #btn_previous').removeClass('wptm_hiden').animate({'opacity': '1'}, 200);
                        $mainTabContent.find('.sql_table_button #btn_next').addClass('wptm_hiden');
                        $mainTabContent.find('.sql_table_button #btn_preview').removeClass('wptm_hiden').animate({'opacity': '1'}, 200);
                        $mainTabContent.css({overflow: 'visible'});
                    });
                }
                break;
            case 'btn_previous':
                $mainTabContent.css({'overflow-x': 'hidden'});
                if (typeof constructedTableData.priKey !== 'undefined') {
                    delete constructedTableData.priKey;
                }

                $mainTabContent.find('#wptm_mysql_review .wptm_previewTable').contents().remove();

                $mainTabContent.find('.step_animation .step_one').removeClass('wptm_hiden').animate({'left': '+=100%', 'opacity': '1'}, 1000, function () {
                    $mainTabContent.find('.step_animation .step_two').animate({'opacity': '0','left': '+=100%'}, 700, function () {
                        $mainTabContent.find('.step_animation .step_two').addClass('wptm_hiden');
                    });
                    $mainTabContent.find('.sql_table_button #btn_previous').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                    $mainTabContent.find('.sql_table_button #btn_preview').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                    $mainTabContent.find('.sql_table_button #btn_next').removeClass('wptm_hiden');
                    $mainTabContent.find('.sql_table_button #btn_tableUpdate').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                    $mainTabContent.find('.sql_table_button #btn_tableCreate').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                });
                break;
            case 'btn_preview':
                if (params !== null && params.response && params.datas !== false) {
                    Wptm.preview = params.datas.preview;
                    Wptm.hasRow = params.datas.hasRow;

                    constructedTableData.query = params.datas.query;
                    $wptm_previewTable.html(params.datas.preview);
                }
                if (Wptm.hasRow) {
                    $mainTabContent.find('.sql_table_button #btn_preview').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                    $mainTabContent.find('.sql_table_button #btn_tableUpdate').removeClass('wptm_hiden').animate({'opacity': '1'}, 200);
                    $mainTabContent.find('.sql_table_button #btn_tableCreate').removeClass('wptm_hiden').animate({'opacity': '1'}, 200);
                }
                break;
            case 'btn_tableCreate':
                if (params.response && params.datas !== false) {
                    constructedTableData.query = params.datas.query;
                    constructedTableData.priKey = $mainTabContent.find('#column_key').data('value');
                    constructedTableData.table_editing = $mainTabContent.find('#wptm_mysql_table_editing').is(":checked") ? 1 : 0;

                    if (typeof constructedTableData.priKey == 'undefined'
                        || constructedTableData.priKey === '') {
                        constructedTableData.table_editing = 0;
                    }

                    $.ajax({
                        url: wptm_ajaxurl + "task=dbtable.createTable",
                        data: {
                            table_data: constructedTableData,
                            id_cat: id_cat
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (result) {
                            if (result.response) {
                                localStorage.setItem('new_db_table', JSON.stringify(result.datas));

                                window.location.href = 'admin.php?page=wptm&id_table=' + result.datas.id;
                            }
                        }
                    });
                }
                break;
            case 'btn_tableUpdate':
                if (params.response && params.datas !== false) {
                    constructedTableData.query = params.datas.query;
                    constructedTableData.priKey = $mainTabContent.find('#column_key').data('value');
                    constructedTableData.table_editing = $mainTabContent.find('#wptm_mysql_table_editing').is(":checked") ? 1 : 0;

                    if (typeof constructedTableData.priKey == 'undefined'
                        || constructedTableData.priKey === '') {
                        constructedTableData.table_editing = 0;
                    }

                    $.ajax({
                        url: wptm_ajaxurl + "task=dbtable.updateTable",
                        data: {
                            table_data: constructedTableData
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function (result) {
                            if (result.response) {
                                if (typeof caninsert !== 'undefined' && caninsert) {
                                    window.location.href = 'admin.php?page=wptm&id_table=' + constructedTableData.id_table + '&noheader=1&caninsert=1';
                                } else {
                                    window.location.href = 'admin.php?page=wptm&id_table=' + constructedTableData.id_table;
                                }
                            }
                        }
                    })
                }
                break;
        }
    }

    function generateQueryAndPreviewdata (constructedTableData, action, callFunction) {
        $.ajax({
            url: wptm_ajaxurl + "task=dbtable.generateQueryAndPreviewdata",
            data: {
                table_data: constructedTableData
            },
            type: 'post',
            dataType: 'json',
            success: function (result) {
                if (callFunction !== null) {
                    callFunction.call(constructedTableData, action, result);
                } else {
                    $mainTabContent.find('#over_loadding_open_chart').hide();
                    if (result.response && result.datas !== false) {
                        constructedTableData.query = result.datas.query;
                        if (typeof Wptm == 'undefined') {
                            Wptm = {};
                        }
                        Wptm.preview = result.datas.preview;
                        Wptm.hasRow = result.datas.hasRow;
                        myCssEditor.setValue(constructedTableData.query);
                        $(textArea).change();
                        setTableIndexKey();
                        return result.datas;
                    } else {
                        return false;
                    }
                }
            },
            error: function (jqxhr, textStatus, error) {
                $mainTabContent.find('#over_loadding_open_chart').hide();
                alert(textStatus);
            }
        });
    }

    function applyCustomQuery (action) {
        var custom_mysql = myCssEditor.getValue();
        $mainTabContent.find('#over_loadding_open_chart').show();

        if (action === 'click') {
            $wptm_lock_columns.find('.wptm_container_content .wptm_lock_column').each(function (i, e) {
                var data = $(this).data('value');
                constructedTableData.lock_columns[data] = $(this).find('.switch-button').is(":checked") ? 1 : 0;
            });
        }

        $.ajax({
            url: wptm_ajaxurl + "task=dbtable.applyCustomQuery",
            data: {
                custom_mysql: custom_mysql
            },
            type: 'post',
            dataType: 'json',
            success: function (result) {
                if (result.response && result.datas !== false) {
                    delete Wptm.preview;
                    delete Wptm.hasRow;
                    if (result.datas.hasRow) {//Custom query pass
                        delete constructedTableData.columnCount;
                        delete constructedTableData.default_ordering;
                        delete constructedTableData.default_ordering_dir;
                        delete constructedTableData.grouping_rules;
                        delete constructedTableData.join_rules;
                        delete constructedTableData.mysql_columns;
                        delete constructedTableData.tables;
                        delete constructedTableData.where_conditions;
                        delete constructedTableData.custom_titles;

                        // delete constructedTableData.priKey;
                        constructedTableData.query = custom_mysql;
                        constructedTableData.customquery = true;

                        $wptm_mysql_tables.find('.mysql_table.active').each(function () {
                            $(this).removeClass('active');
                        })

                        $wptm_mysql_tables_columns.find('.uploader').contents().remove();
                        $wptm_mysql_default_ordering.find('#wptm_mysql_default_ordering_column').contents().remove();
                        $wptm_define_mysql_relations.find('div.mysqlRelationsContainer .mysql_table_blocks').remove();
                        $wptm_define_mysql_conditions.find('div.mysqlConditionsContainer .post_where_blocks').remove();
                        $wptm_define_mysql_grouping.find('div.mysqlGroupingContainer .post_grouping_rule_blocks').remove();

                        setTableIndexKey();
                    }

                    Wptm.preview = result.datas.preview;
                    Wptm.hasRow = result.datas.hasRow;
                    $mainTabContent.find('#over_loadding_open_chart').hide();

                    if (action === 'click') {
                        setTimeout(function () {
                            $mainTabContent.find('.sql_table_button #btn_preview').trigger('click');
                        }, 300);
                    } else {
                        $mainTabContent.find('.sql_table_button #btn_preview').removeClass('wptm_hiden').animate({'opacity': '1'}, 200);
                        $mainTabContent.find('.sql_table_button #btn_tableUpdate').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                        $mainTabContent.find('.sql_table_button #btn_tableCreate').addClass('wptm_hiden').animate({'opacity': '0'}, 200);
                    }
                    return true;
                } else {
                    $mainTabContent.find('#over_loadding_open_chart').hide();
                    alert(result.datas.error)
                    return false;
                }
            },
            error: function (jqxhr, textStatus, error) {
                $mainTabContent.find('#over_loadding_open_chart').hide();
                alert(jqxhr.responseText);
            }
        })
        return false;
    }

    var wptmCreateMyCssEditor = false;
    $(document).on('click', '#btn_next', function (e) {
        if (wptmCreateMyCssEditor === false) {
            wptmCreateMyCssEditor = true;

            myCssEditor = CodeMirror.fromTextArea(textArea, {
                mode: 'text/x-mariadb',
                indentWithTabs: true,
                smartIndent: true,
                lineNumbers: true,
                matchBrackets : true,
                autofocus: true,
                extraKeys: {"Ctrl-Space": "autocomplete"},
                hintOptions: {tables: {
                        users: ["name", "score", "birthDate"],
                        countries: ["name", "population", "size"]
                    }}
            });

            myCssEditor.setSize("100%", 350);
        }

        e.preventDefault();
        $.ajax({
            url: wptm_ajaxurl + "task=dbtable.getQuery",
            data: {
                id: constructedTableData.id_table
            },
            type: 'post',
            dataType: 'json',
            success: function (result) {
                if (result.response === true) {
                    if (result.datas !== null && typeof result.datas.mysql_query !== 'undefined') {
                        constructedTableData.query = result.datas.mysql_query.replace(/[\\]/g, '');
                    }
                    console.log(constructedTableData);

                    if (typeof constructedTableData.customquery !== 'undefined' && constructedTableData.customquery == 'true') {//has custom
                        myCssEditor.setValue(constructedTableData.query);
                        $(textArea).change();
                        applyCustomQuery('getQuery');
                    } else {//no custom
                        if (!check_selected_table_and_column()) {//not selected columns
                            return false;
                        }
                        set_constructed_table_data();

                        $mainTabContent.find('#over_loadding_open_chart').show();
                        generateQueryAndPreviewdata (constructedTableData, '', null);
                    }
                    show_button_submit.call(constructedTableData, 'next', null);
                    $mainTabContent.css({overflow: 'visible'});
                }
            },
            error: function (jqxhr, textStatus, error) {
                $mainTabContent.find('#over_loadding_open_chart').hide();
                alert(jqxhr.responseText);
            }
        });
    });

    $(document).on('click', '#btn_preview', function (e) {
        e.preventDefault();

        if (typeof Wptm.preview !== 'undefined') {//has table html
            $wptm_previewTable.html(Wptm.preview);
            show_button_submit.call(constructedTableData, 'btn_preview', null);
        } else {//has not table html
            var selected_table = check_selected_table_and_column();
            if (!selected_table) {
                return false;
            }
            set_constructed_table_data();
            generateQueryAndPreviewdata(constructedTableData, 'btn_preview', show_button_submit);
        }

        return false;
    });

    $mainTabContent.find('.wptm_custom_mysql #btn_apply_mysql').click(function (e) {
        e.preventDefault();
        applyCustomQuery('click');
        return false;
    });

    $mainTabContent.find('#btn_previous').click(function (e) {
        e.preventDefault();

        show_button_submit.call(constructedTableData, 'btn_previous', null);
        return false;
    });

    $(document).on('click', '#btn_tableCreate', function (e) {
        e.preventDefault();
        if (constructedTableData.customquery === true) {
            // $wptm_previewTable.html(Wptm.preview);
            var result = {
                response: Wptm.hasRow,
                datas: {
                    query: constructedTableData.query,
                }
            };

            show_button_submit.call(constructedTableData, 'btn_tableCreate', result);
        } else {
            var selected_table = check_selected_table_and_column();
            if (!selected_table) {
                return false;
            }
            set_constructed_table_data();

            generateQueryAndPreviewdata(constructedTableData, 'btn_tableCreate', show_button_submit);
        }

        return false;
    });

    $(document).on('click', '#btn_tableUpdate', function (e) {
        e.preventDefault();

        if (constructedTableData.customquery === true) {
            // $wptm_previewTable.html(Wptm.preview);
            var result = {
                response: Wptm.hasRow,
                datas: {
                    query: constructedTableData.query,
                }
            };

            show_button_submit.call(constructedTableData, 'btn_tableUpdate', result);
        } else {
            var selected_table = check_selected_table_and_column();
            if (!selected_table) {
                return false;
            }

            set_constructed_table_data();

            generateQueryAndPreviewdata(constructedTableData, 'btn_tableUpdate', show_button_submit);
        }
        return false;
    });

    $mainTabContent.find('.wptm_mysql_table_editing').on('change', function (e) {
        if (e.target.checked) {
            constructedTableData.table_editing = 1;
            $mainTabContent.find('#column_key').parent().removeClass('wptm_hiden');
            $mainTabContent.find('.wptm_lock_columns').removeClass('wptm_hiden');
        } else {
            constructedTableData.table_editing = 0;
            $mainTabContent.find('#column_key').parent().addClass('wptm_hiden');
            $mainTabContent.find('.wptm_lock_columns').addClass('wptm_hiden');
        }
    });

    /**
     * Create custom select box
     *
     * @param $
     * @param select_function cell function when click select
     */
    function custom_select_box ($, select_function) {
        $(this).on('click', function (e) {
            $('#mybootstrap').find('.wptm_select_box').each(function () {
                $(this).hide();
                $(this).siblings('.show').removeClass('show');
            });
            var position = $(this).position();

            if ($(this).hasClass('show')) {
                $(this).next().hide();
                $(this).removeClass('show');
                $(document).unbind('click.wptm_select_box');
                return;
            }

            var $that = $(this).addClass('show');
            var $select = $(this).next().css({top: position.top + 40, left: position.left, 'min-width': $that.outerWidth()}).show();

            $select.find('li').unbind('click').on('click', function (e) {
                $select.data('value', $(this).data('value')).change();
                $that.val($(this).data('value')).text($(this).text()).data('value', $(this).data('value')).change();
                if (typeof select_function !== 'undefined') {
                    select_function.call($(this));
                }
                $('#mybootstrap').find('.wptm_select_box').hide();
            });

            $(document).bind('click.wptm_select_box', (e) => {
                if (!$(e.target).is($(this))) {
                    $select.hide();
                    $that.removeClass('show');
                    $(document).unbind('click.wptm_select_box');
                }
            });
        });
    }

    if (typeof constructedTableData.id_table !== 'undefined') {//table existed
        $mainTabContent.find('.sql_table_button #btn_previous').removeClass('wptm_hiden').css({'opacity': '1'});
        $mainTabContent.find('.sql_table_button #btn_next').addClass('wptm_hiden');
        $mainTabContent.find('.sql_table_button #btn_preview').removeClass('wptm_hiden').css({'opacity': '1'});
        $(document).find('#btn_next').trigger('click');
    } else {

    }
})
