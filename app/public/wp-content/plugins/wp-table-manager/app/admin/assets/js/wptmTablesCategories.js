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
    var $cat_list = $('.cat_list');
    var $categories = $('#categorieslist');
    var $wptm_categories_list = $('.wptm_categories');

    var $table_list = $('.wptm_table_list');
    var $wptm_top_toolbar = $table_list.find('.wptm_top_toolbar');
    var $folder_path = $table_list.find('.folder_path');

    var $list_tables = $('#list_tables');
    var $tbody = $list_tables.find('tbody');

    var $content_popup_hide = $('#content_popup_hide');

    var $right_mouse_menu = $('#right_mouse_menu');

    if (typeof (Wptm) == 'undefined') {
        Wptm = {};
    }
    Wptm.disableTable = false;
    // Wptm.showDbTables = false;
    var Scrollbar = window.Scrollbar;

    if ($('.sticky-menu').length > 0) {
        Scrollbar.init(document.querySelector('#adminmenuwrap'), {
            damping: 0.5,
            thumbMinSize: 10,
            alwaysShowTracks: false
        });
    }

    Scrollbar.init(document.querySelector('#categorieslist'), {
        damping: 0.5,
        thumbMinSize: 10,
        alwaysShowTracks: false
    });

    if (typeof wptm_convert_data !== 'undefined') {
        if (wptm_convert_data.id >= 0) {
            $.ajax({
                url: wptm_ajaxurl + "task=table.convertTable",
                type: 'POST',
                dataType: "json",
                data: {
                    'option_nonce': $('#option_nonce').val(),
                },
                success: function (datas) {
                    if (datas.response === true) {
                        convert_data_table(datas.datas.id);
                    }
                },
                error: function (jqxhr, textStatus, error) {
                    alert(textStatus + " : " + error);
                }
            });
        }
    }

    function convert_data_table (id) {
        if (parseInt(id) >= 0) {
            $.ajax({
                url: wptm_ajaxurl + "task=table.moveTable",
                type: 'POST',
                dataType: "json",
                data: {
                    'id': id,
                    'option_nonce': $('#option_nonce').val(),
                },
                success: function (datas) {
                    if (datas.response === true) {
                        if (parseInt(datas.datas) < 0) {
                            setTimeout(function () {
                                $('.convert_tables').remove();
                            }, 3000);
                        } else {
                            convert_data_table(datas.datas);
                        }
                    } else {
                        status_noti(0, datas.response);
                        alert(wptmText.Error_convert_old_data + " : " + datas.datas);
                    }
                },
                error: function (jqxhr, textStatus, error) {
                    alert(textStatus + " : " + error);
                }
            });
        }
    }

    $.fn.wptmSingleDoubleClick = function (single_click_callback, double_click_callback, timeout) {
        return this.each(function () {
            var clicks = 0, self = this;
            $(this).unbind('click').on('click', function (event) {
                clicks++;
                if (clicks === 1) {
                    setTimeout(function () {
                        if (clicks === 1) {
                            single_click_callback.call(self, event);
                        } else {
                            double_click_callback.call(self, event);
                        }
                        clicks = 0;
                    }, timeout || 300);
                }
            });
        });
    };

    $list_tables.prev().width($list_tables.width()).show();

    function show_cat(that, content) {//that is li.dd-item
        if ($categories.find('.active').length < 1) {//first get function
            // Wptm.cat_active = 0;
        } else {
            $categories.find('.active').removeClass('active');
        }
        if (that.hasClass('hasRole') || that.hasClass('caninsert')) {
            that.addClass('active');
            $categories.find('li.dd-item.show').removeClass('show');
            $folder_path.contents().remove();

            var $parent = that.parents('li.dd-item');
            var id_cat = that.data('id-category');
            var html = '<div data-id="0"><span>TABLES</span></div>', i;

            for (i = $parent.length - 1; i >= 0; i--) {
                html += '<div data-id="' + $($parent[i]).data('id-category') + '">' +
                    '<span>' + Wptm.dategory[$($parent[i]).data('id-category')].title + '</span></div>';
            }
            html += '<div data-id="' + id_cat + '" class="wptm_hove_right_mouse_menu"><span>' + Wptm.dategory[id_cat].title + '</span></div>';

            //print folder_path
            $folder_path.append(html);

            //render list table
            if (Wptm.cat_active != id_cat) {
                get_list_table(id_cat, content);
            } else {
                update_changer(content);
            }
            setHeightToolbar();
            wptm_setCookie("wptm_category_id", id_cat, 30);
        } else if (!that.hasClass('caninsert') && getCookie('wptm_category_id') != Wptm.cat_active && $categories.find('.hasRole').length > 0) {//user not has wptm_category_id_cookie category
            show_cat($categories.find('li[data-id-category="' + Wptm.cat_active + '"]'));
        } else {//user not has category but has wptm_edit_own_category
            delete Wptm.cat_active;
            $tbody.contents().remove();
            Wptm.tables = $.extend({}, {});

            if ($list_tables.find('.no_table').length < 1) {
                $tbody.append('<span class="no_table no_sortable">' + wptmText.no_table_found + '</span>');
            }
            update_changer(content);
        }
    }

    $('#create_new').unbind('click').on('click', function () {
        var offsets = $(this).position();
        var html = '<div id="create_new_menu" style="top: ' + offsets.top + 'px;left: ' + offsets.left + 'px">' +
            '<div class="create_category"><span>' + wptmText.create_category + '</span></div>' +
            '<div class="create_table"><span>' + wptmText.create_table + '</span></div>';

        if (Wptm.roles.wptm_access_database_table) {
            html += '<div class="create_dbtable"><span>' + wptmText.create_dbtable + '</span></div></div>';
        } else {
            html += '</div>';
        }

        $('#mybootstrap.wptm-tables').append($(html));
        $('#over_popup').css({'opacity': 0}).show();

        $('#over_popup').click(function () {
            $('#over_popup').css({'opacity': 0.4}).removeClass('loadding').hide();
            $('#mybootstrap.wptm-tables').find('#create_new_menu').remove();
        });

        // if (Wptm.showDbTables) {//when show db tables list, not create new table/category
        //     $('#mybootstrap.wptm-tables').find('.create_category').addClass('no_click');
        //     $('#mybootstrap.wptm-tables').find('.create_table').addClass('no_click');
        // }

        $('#create_new_menu').find('.create_table:not(.no_click)').unbind().on('click', function () {
            if (typeof Wptm.cat_active !== 'undefined' && Wptm.cat_active !== 0) {
                if (!wptm_permissions.can_create_tables) {
                    alert(wptm_permissions.translate.wptm_create_tables);
                    return false;
                }
                var curr_page = window.location.href;
                var cells = curr_page.split("?");

                $('#over_popup').css({'opacity': 0.4}).addClass('loadding');
                $('#mybootstrap.wptm-tables').find('#create_new_menu').remove();

                $.ajax({
                    url: wptm_ajaxurl + "task=table.add&id_category=" + Wptm.cat_active,
                    type: "POST",
                    data: {},
                    dataType: "json",
                    success: function (datas) {
                        $('#over_popup').trigger('click');
                        if (datas.response === true) {
                            var data = {};
                            data[datas.datas.id] = {
                                author: Wptm.idUser,
                                author_name: Wptm.author_name,
                                id: datas.datas.id,
                                modified_time: datas.datas.modified_time,
                                position: datas.datas.position,
                                title: datas.datas.title,
                                type: datas.datas.type
                            };
                            add_new_tr(data[datas.datas.id]);

                            $list_tables.trigger("update");
                            Wptm.tables = $.extend({}, Wptm.tables, data);
                            update_changer();

                            var new_url = cells[0] + '?page=wptm&id_table=' + datas.datas.id;
                            if (wptm_open_table != 0) {
                                window.open(new_url);
                            } else {
                                window.location = new_url;
                            }
                        } else {
                            status_noti(0, datas.response);
                        }
                    },
                    error: function (jqxhr, textStatus, error) {
                        $('#over_popup').trigger('click');
                        alert(textStatus + " : " + error);
                    }
                });
            }
        });

        $('#create_new_menu').find('.create_category:not(.no_click)').unbind().on('click', function () {
            $('#mybootstrap.wptm-tables').find('#create_new_menu').remove();
            $('#over_popup').css({'opacity': 0.4});
            if (!check_role('createCat', [])) {
                alert(wptm_permissions.translate.wptm_create_category);
                return false;
            }

            var popup = {
                'html': $content_popup_hide.find('#edit_category'),
                'inputEnter': true,
                'showAction': function () {
                    this.siblings('.colose_popup').show();
                    var list_cat = [];
                    var id;
                    for (id in Wptm.dategory) {
                        list_cat[id] = [Wptm.dategory[id].title, id];
                    }

                    list_cat.sort(function (a,b) {
                        return a[0].localeCompare(b[0]);
                    });

                    for (id in list_cat) {
                        if (typeof list_cat[id] !== 'undefined' && list_cat[id][1] == Wptm.cat_active) {
                            this.find('#jform_parent_cat').prepend('<option value="' + list_cat[id][1] + '">' + list_cat[id][0] + '</option>');
                            this.find('#jform_parent_cat').val(list_cat[id][1]).change();
                        }
                    }

                    this.find('input[name="re_name"]').focus();

                    this.find('#submit_button').addClass('wptm_center');
                    this.find('.wptm_done').addClass('wptm_blu');
                    this.find('.wptm_cancel').addClass('wptm_grey');
                    return true;
                },
                'submitAction': function () {
                    var name = this.find('input[name="re_name"]').val();

                    var parent = this.find('#jform_parent_cat').val();
                    parent = parent == 0 ? 1 : parent;

                    add_new_cat(name, parent);
                    this.siblings('.colose_popup').trigger('click');

                    return true;
                }
            };
            wptm_popup($('#wptm_popup'), popup, true, true);
        });
        if (Wptm.roles.wptm_access_database_table) {
            $('#create_new_menu').find('.create_dbtable:not(.no_click)').unbind().on('click', function () {
                if (typeof Wptm.cat_active !== 'undefined' && Wptm.cat_active !== 0) {
                    if (!wptm_permissions.can_create_tables) {
                        alert(wptm_permissions.translate.wptm_create_tables);
                        return false;
                    }

                    $('#mybootstrap.wptm-tables').find('#create_new_menu').remove();

                    var curr_page = window.location.href;
                    var cells = curr_page.split("?");
                    var new_url = cells[0] + '?page=wptm&type=dbtable&action=create&id_cat=' + Wptm.cat_active;
                    // window.location = new_url;
                    window.open(new_url);
                }
            });
        }
    });

    localStorage.removeItem('new_db_table');
    localStorage.removeItem('wptm_change_table_name');
    localStorage.removeItem('wptm_change_chart_name');

    $(window).on('storage', function (e) {
        var storageEvent = e.originalEvent;
        if (storageEvent.key == 'new_db_table') {
            try {
                var datas = jQuery.parseJSON(storageEvent.newValue);

                if (typeof datas.id_category !== 'undefined' && datas.id_category == Wptm.cat_active) {
                    var data = {};
                    data[datas.id] = {
                        author: Wptm.idUser,
                        author_name: Wptm.author_name,
                        id: datas.id,
                        modified_time: datas.modified_time,
                        position: datas.position,
                        title: datas.title,
                        type: datas.type
                    };
                    add_new_tr(data[datas.id]);

                    $list_tables.trigger("update");
                    Wptm.tables = $.extend({}, Wptm.tables, data);
                    update_changer();
                }
            } catch (err) {}
        }
        localStorage.removeItem('new_db_table');

        if (storageEvent.key == 'wptm_change_table_name') {
            try {
                var datas = jQuery.parseJSON(storageEvent.newValue);

                console.log(datas)

                if (typeof datas.id !== 'undefined' && typeof datas.title !== 'undefined') {
                    if (typeof Wptm.tables[datas.id] !== 'undefined' && $tbody.find('tr[data-id-table="' + datas.id + '"]').length > 0) {
                        $tbody.find('tr[data-id-table="' + datas.id + '"]').find('.title').text(datas.title);
                        $tbody.find('tr[data-id-table="' + datas.id + '"]').find('td').eq(2).text(datas.modified_time);
                        Wptm.tables[datas.id].title = datas.title;
                        $list_tables.trigger("update");
                    }
                }
            } catch (err) {}
        }
        localStorage.removeItem('wptm_change_table_name');

        if (storageEvent.key == 'wptm_change_chart_name') {
            try {
                let datas = jQuery.parseJSON(storageEvent.newValue);

                if (typeof datas.chart_id !== 'undefined' && typeof datas.title !== 'undefined') {
                    if (typeof list_chart[datas.id] !== 'undefined') {
                        if ($tbody.find('.list_chart').find('.tbody[data-id="' + datas.chart_id + '"]').length > 0) {
                            $tbody.find('.list_chart').find('.tbody[data-id="' + datas.chart_id + '"]').find('.a').text(datas.title);
                        }
                        let listCharts = list_chart[datas.id]
                        list_chart[datas.id] = listCharts.map(function (chart) {
                            if (chart.id == datas.chart_id) {
                                chart.title = datas.title;
                            }
                            return chart
                        })
                        $list_tables.trigger("update");
                    }
                }
            } catch (err) {}
        }
        localStorage.removeItem('wptm_change_chart_name');
    });

    function add_new_cat (name, parent) {
        name = name !== '' ? name : wptmText.new_name_category;
        $.ajax({
            url: wptm_ajaxurl + "task=category.addCategory",
            type: 'POST',
            data: {
                'name': name,
                'parent': parent,
                'owner': Wptm.idUser,
            }
        }).done(function (data) {
            var result;
            try {
                result = jQuery.parseJSON(data);
            } catch (err) {
                alert('<div>' + data + '</div>');
            }
            if (result.response === true) {
                var data = {};
                data[result.datas.id] = {
                    parent_id: parent,
                    id: result.datas.id,
                    role: {0:Wptm.idUser},
                    title: name
                };
                Wptm.dategory = $.extend({}, Wptm.dategory, data);

                var html = '<li class="dd-item hasRole dd3-item" data-id-category="' + result.datas.id + '">' +
                    '<div class="dd-handle dd3-handle ui-droppable ui-sortable-handle">' +
                    '<span class="title folder_name">' + name + '</span>' +
                    '</div>' +
                    '</li>';

                var cat_parent = $categories.find('.dd-item[data-id-category="' + parent + '"]');

                if (parent != 1) {
                    if (cat_parent.find('ol').length < 1) {
                        cat_parent.append('<ol class="dd-list"></ol>');
                        cat_parent.prepend('<button class="cat_expand wptm_nestable show" data-action="expand"></button>');
                    }
                    $(cat_parent.find('ol')[0]).prepend(html);
                } else {
                    $categories.find('.scroll-content').prepend(html);
                }

                update_changer();
                show_cat($categories.find('.dd-item[data-id-category="' + result.datas.id + '"]'));
                status_noti(1, wptmText.created_cat_success);
            } else {
                status_noti(0, result.response);
            }
        });
    }

    $wptm_top_toolbar.find('.wptm_select_type_table').on('click', function () {
        var position = $(this).position();
        var left = $(this).parent().width() - position.left - $(this).outerWidth();

        if ($(this).hasClass('show')) {
            $(this).next().hide();
            $(this).removeClass('show');
            $(document).unbind('click.select_type_table');
            return;
        }
        var $that = $(this).addClass('show');
        var $select = $(this).next().css({top: position.top + 40, left: 'auto', right: left, 'min-width': $that.outerWidth()}).show();

        $select.find('li').unbind('click').on('click', function () {
            $select.data('value', $(this).data('type'));
            $that.text($(this).text());
            search_items($list_tables, 'enter', [1]);
            wptm_setCookie("wptm_select_type_table", $(this).data('type'), 30);
        });

        $(document).bind('click.select_type_table', function (e) {
            if (!$(e.target).is('.wptm_select_type_table')) {
                $select.hide();
                $that.removeClass('show');
                $(document).unbind('click.select_type_table');
            }
        });
    });

    $wptm_top_toolbar.find('.re_name').on('click', function () {
        if (parseInt(chart_active) > 0) {
            var that = $list_tables.find('.tbody[data-id="' + chart_active + '"]');
        }
        if (typeof Wptm.table !== 'undefined' && !Wptm.disableTable) {
            var popup = {
                'html': $content_popup_hide.find('#re_name'),
                'inputEnter': true,
                'showAction': function () {
                    this.find('.wptm_done').addClass('wptm_blu');
                    this.find('.wptm_cancel').addClass('wptm_grey');
                    if (parseInt(chart_active) > 0) {
                        this.find('input[name="re_name"]').val(list_chart[Wptm.table][that.data('position')].title).focus();
                        this.find('#jform_re_name-lbl').text('Rename Chart');
                    } else {
                        this.find('input[name="re_name"]').val(Wptm.tables[Wptm.table].title).focus();
                    }

                    return true;
                },
                'submitAction': function () {
                    var name = this.find('input[name="re_name"]').val();
                    if (name.trim() !== '') {
                        var url;
                        if (parseInt(chart_active) > 0) {
                            url = wptm_ajaxurl + "task=chart.setTitle&id=" + chart_active + '&title=' + name;
                        } else {
                            url = wptm_ajaxurl + "task=table.setTitle&id=" + Wptm.table + '&title=' + name;
                        }

                        $.ajax({
                            url: url,
                            type: "POST",
                            data: {},
                            dataType: "json",
                            success: function (datas) {
                                if (datas.response === true) {
                                    if (parseInt(chart_active) > 0) {
                                        list_chart[Wptm.table][that.data('position')].title = name;
                                        that.find('.title_chart a').text(name);
                                        status_noti(1, wptmText.noti_chart_renamed);
                                    } else {
                                        $tbody.find('tr[data-id-table="' + Wptm.table + '"]').find('.title').text(name);
                                        $tbody.find('tr[data-id-table="' + Wptm.table + '"]').find('td').eq(2).text(datas.datas.modified_time);
                                        Wptm.tables[Wptm.table].title = name;
                                        $list_tables.trigger("update");
                                        status_noti(1, wptmText.noti_table_renamed);
                                    }
                                } else {
                                    status_noti(0, datas.response);
                                }
                            },
                            error: function (jqxhr, textStatus, error) {
                                status_noti(0, textStatus);
                            }
                        });
                    }
                    this.siblings('.colose_popup').trigger('click');
                    return true;
                }
            };
            wptm_popup($('#wptm_popup'), popup, true, true);
        }
    });

    $wptm_top_toolbar.find('.delete').unbind('click').on('click', function () {
        if (parseInt(chart_active) > 0) {
            var that = $list_tables.find('.tbody[data-id="' + chart_active + '"]');

            var html = '';
            html += '<div>';
            html += '<div  class="control-group">';
            html += '<span>' + wptmText.JS_WANT_DELETE + "\"" + that.find('.title_chart a').text() + '"?' + '</span>';
            html += '</div>';
            html += '</div>';
            popup = {
                'html': $(html),
                'showAction': function () {
                    this.find('.wptm_done').addClass('wptm_red');
                    this.find('.wptm_cancel').addClass('wptm_grey');
                },
                'submitAction': function () {
                    $.ajax({
                        url: wptm_ajaxurl + "task=chart.delete&id=" + chart_active,
                        type: "POST",
                        data: {},
                        dataType: "json",
                        success: function (datas) {
                            if (datas.response === true) {
                                var id = that.data('position');
                                that.remove();
                                delete list_chart[Wptm.table][id];
                                $list_tables.find('.dd-item[data-id-table="' + Wptm.table + '"]').trigger('click');
                                status_noti(1, wptmText.delete_chart_success);
                            } else {
                                alert(datas.response);
                            }
                        },
                        error: function (jqxhr, textStatus, error) {
                            alert(textStatus);
                        }
                    });
                },
                'cancelAction': function () {
                    if (typeof cancelCallFunction !== 'undefined') {
                        cancelCallFunction.call();
                    }
                    return true;
                }
            };
            wptm_popup($('#wptm_popup'), popup, false, true);
            return true;
        }
        if (typeof Wptm.table !== 'undefined') {
            if (!wptm_permissions.can_delete_tables) {
                alert(wptm_permissions.translate.wptm_delete_tables);
                return false;
            }

            var popup = {
                'html': $content_popup_hide.find('#delete_tables'),
                'showAction': function () {
                    this.find('.wptm_done').addClass('wptm_red');
                    this.find('.wptm_cancel').addClass('wptm_grey');
                    this.find('#popup_done').text(wptmText.Delete);
                    this.siblings('.colose_popup').hide();

                    if ($tbody.find('tr.selected').length > 1) {
                        this.find('.delete_table').text(wptmText.delete_table);
                        this.find('.delete_table_question').text(wptmText.delete_table_question);
                    }
                    return true;
                },
                'submitAction': function () {
                    var id_table = [];
                    var list_id = {};
                    $tbody.find('tr.selected').each(function () {
                        id_table.push($(this).data('id-table'));
                        list_id[$(this).data('id-table')] = $.extend({}, Wptm.tables[$(this).data('id-table')]);

                        delete Wptm.tables[$(this).data('id-table')];
                    });

                    $.ajax({
                        url: wptm_ajaxurl + "task=table.delete",
                        type: "POST",
                        data: {
                            'id': JSON.stringify(id_table),
                            'option_nonce': $('#option_nonce').val(),
                        },
                        success: function (datas) {
                            var result;
                            result = $.parseJSON(datas);

                            if (result.response === true) {
                                $tbody.find('tr.selected').remove();
                                $list_tables.trigger("update");
                                delete Wptm.table;

                                fix_style_top_toolbar();

                                status_noti(1, wptmText.delete_success);
                            } else {
                                Wptm.tables = $.extend({}, Wptm.tables, list_id);
                                status_noti(0, result.response);
                            }
                            set_background_table();
                        },
                        error: function (jqxhr, textStatus, error) {
                            Wptm.tables = $.extend({}, Wptm.tables, list_id);
                            alert(textStatus);
                        }
                    });
                    this.siblings('.colose_popup').trigger('click');
                    return true;
                }
            };
            wptm_popup($('#wptm_popup'), popup, true, true);
        }
    });

    $wptm_top_toolbar.find('span.button:not(.delete)').hover(function () {
        if (Wptm.disableTable) {
            $(this).css({'cursor': 'not-allowed'});
        } else {
            $(this).css({'cursor': 'pointer'});
        }
    });

    $wptm_top_toolbar.find('.edit_table').unbind('click').on('click', function () {
        if (parseInt(Wptm.table) > 0 && !Wptm.disableTable && (!!wptm_permissions.can_edit_tables || ($list_tables.find('.dd-item[data-id-table="' + Wptm.table + '"]').hasClass('hasRole') && wptm_permissions.can_edit_own_tables))) {
            var curr_page = window.location.href;
            var cells = curr_page.split("?");
            var new_url = cells[0] + '?page=wptm&id_table=' + Wptm.table;

            if (typeof wptm_open_table !== 'undefined' && wptm_open_table === '0') {
                window.location = new_url;
            } else {
                window.open(new_url);
            }
        }
    });

    $wptm_top_toolbar.find('.copy').unbind('click').on('click', function () {
        var url = wptm_ajaxurl + "task=table.copy&id=" + Wptm.table;
        if (parseInt(chart_active) > 0) {
            var that = $list_tables.find('.tbody[data-id="' + chart_active + '"]');
            url = wptm_ajaxurl + "task=chart.copy&id=" + chart_active;
        } else {
            if (!wptm_permissions.can_create_tables) {
                alert(wptm_permissions.translate.wptm_create_tables);
                return false;
            }

            if (Wptm.disableTable) {
                return false;
            }
        }
        if (typeof Wptm.table !== 'undefined') {
            $('#over_popup').css({'opacity': 0.4}).addClass('loadding').show();

            $.ajax({
                url: url,
                type: "POST",
                data: {},
                dataType: "json",
                success: function (datas) {
                    $('#over_popup').css({'opacity': 0.4}).removeClass('loadding').hide();
                    if (datas.response === true) {
                        if (parseInt(chart_active) > 0) {//copy chart
                            list_chart[Wptm.table].push({id: datas.datas.id, title: datas.datas.title, id_table: Wptm.table, modified_time: datas.datas.modified_time});
                            var html = '<div class="tbody" data-position="' + (list_chart[Wptm.table].length - 1) + '" data-id="' + datas.datas.id + '"><div class="title_chart"><a href="' + wptm_table_url + 'id_table=' + Wptm.table + '&chart=' + datas.datas.id + '&noheader=1&caninsert=1">' + datas.datas.title + '</a></div><div class="time">' + datas.datas.modified_time + '</div><div class="shortcode"><span>[wptm id-chart=' + datas.datas.id + ']</span><span class="button wptm_icon_tables copy_text tooltip"></span></div></div>';
                            that.after(html);
                            update_chart();
                            status_noti(1, wptmText.copy_chart_success);
                        } else {
                            var data = {};
                            data[datas.datas.id] = {
                                author: Wptm.idUser,
                                author_name: Wptm.author_name,
                                id: datas.datas.id,
                                modified_time: datas.datas.created_time,
                                position: datas.datas.position,
                                title: datas.datas.title,
                                type: datas.datas.type
                            };
                            add_new_tr(data[datas.datas.id]);

                            $list_tables.trigger("update");
                            Wptm.tables = $.extend({}, Wptm.tables, data);
                            update_changer();
                            status_noti(1, wptmText.copy_success);
                        }
                    } else {
                        status_noti(0, datas.response);
                    }
                },
                error: function (jqxhr, textStatus, error) {
                    alert(textStatus);
                }
            });
        }
    });

    /*search tables*/
    $('#wptm-form-search').on('keyup', function (e) {
        if (e.keyCode === 13) {
            search_items($list_tables, 'enter', [1]);
        } else if ($(this).val() === '') {
            search_items($list_tables, '', [1]);
        }
        return true;
    });
    $('i.search_table').unbind('click').on('click', function () {
        search_items($list_tables, 'enter', [1]);
    });
    function search_items(table, key, position_col) {
        var i;
        var text = key !== '' ? $('#wptm-form-search').val() : '';

        table.find('tbody tr').each(function () {
            if (text !== '') {
                $(this).addClass('wptm_hiden');
                for (i = 0; i < position_col.length; i++) {
                    var value = $(this).find('td:eq(' + position_col[i] + ') span').text();

                    if (value.toLowerCase().search(text.toLowerCase()) !== -1) {
                        $(this).removeClass('wptm_hiden');
                    }
                }
            } else {
                $(this).removeClass('wptm_hiden');
            }

            switch ($wptm_top_toolbar.find('#wptm_select_type_table').data('value')) {
                case 'mysql':
                    if ($(this).data('type') !== 'mysql') {
                        $(this).addClass('wptm_hiden');
                    }
                    break;
                case 'html':
                    if ($(this).data('type') !== 'html') {
                        $(this).addClass('wptm_hiden');
                    }
                    break;
            }
        });

        $tbody.find('.no_table').remove();

        if (table.find('tbody tr:not(.wptm_hiden)').length < 1) {
            $tbody.append('<span class="no_table no_sortable">' + wptmText.no_table_found + '</span>');
        } else {
            $list_tables.trigger("update");
        }
        set_background_table();
    }
    /*end search*/

    var category_cookie = getCookie('wptm_category_id');
    if (typeof category_cookie !== 'undefined' && parseInt(category_cookie) > 0 && $categories.find('li[data-id-category="' + category_cookie + '"]').length > 0) {
        show_cat($categories.find('li[data-id-category="' + category_cookie + '"]'));
    } else {
        show_cat($categories.find('li[data-id-category="' + Wptm.cat_active + '"]'));
    }

    function update_changer(content) {
        var wptm_categories_resizable = getCookie('wptm_select_type_table');
        if (typeof wptm_categories_resizable !== 'undefined' && wptm_categories_resizable !== null) {
            var $wptm_select_type_table = $('#wptm_select_type_table').data('value', wptm_categories_resizable);
            $wptm_select_type_table.prev().text($wptm_select_type_table.find('li[data-type="' + wptm_categories_resizable + '"]').text());
            search_items($list_tables, 'enter', [1]);
        } else {
            $wptm_top_toolbar.find('.wptm_select_type_table').text(wptmText.table_type);
            $wptm_top_toolbar.find('#wptm_select_type_table').data('value', '');
        }

        set_background_table();

        if (typeof content !== 'undefined') {
            active_chart(content);
        }

        if ($list_tables.find('.dd-item.selected').length > 0) {
            Wptm.table = $($list_tables.find('.dd-item.selected')[0]).data('id-table');
        } else {
            delete Wptm.table;
        }
        if ($('#inserttable').length > 0) {//change insert button status
            $('#inserttable').addClass("no_click");
            if (!$('#inserttable').hasClass('not_change_type')) {
                $('#inserttable').data('type', 'table').attr('data-type', 'table').text(insert_table);
            }

            if (typeof Wptm.table !== 'undefined') {
                $('#inserttable').removeClass("no_click");
            }
        }

        if ($list_tables.find('tbody tr').length < 1 && $list_tables.find('.no_table').length < 1) {
            $tbody.append('<span class="no_table no_sortable">' + wptmText.no_table_found + '</span>');
        }

        $categories.find('.ui-droppable').wptmSingleDoubleClick(function () {//open category
            show_cat($(this).parent());
        }, function () { // double click
            change_cat(this);
        });

        $categories.find('.ui-droppable').contextmenu(function (e) {
            e.preventDefault();

            right_click.call(e, function () {
                $right_mouse_menu.find('.copy_table_menu').addClass('wptm_hiden');
                $right_mouse_menu.find('.rename_table_menu').addClass('wptm_hiden');
                $right_mouse_menu.find('.edit_table_menu').addClass('wptm_right_menu_top');
                // $right_mouse_menu.find('.rename_table_menu').addClass('wptm_right_menu_top');

                if (!check_role('editCat', Wptm.dategory[$(e.currentTarget).parent().data('id-category')].role)) {
                    $right_mouse_menu.find('.edit_table_menu').addClass('wptm_hiden');
                } else {
                    $right_mouse_menu.find('.edit_table_menu').removeClass('wptm_hiden');
                }

                if (!check_role('deleteCat', Wptm.dategory[$(e.currentTarget).parent().data('id-category')].role)) {
                    $right_mouse_menu.find('.delete_table_menu').addClass('wptm_hiden');
                } else {
                    $right_mouse_menu.find('.delete_table_menu').removeClass('wptm_hiden');
                }

                $right_mouse_menu.removeClass('wptm_hiden show');
            }, function() {
                $right_mouse_menu.find('.edit_table_menu').unbind('click').on('click', () => {
                    change_cat(this);
                });

                $right_mouse_menu.find('.delete_table_menu').unbind('click').on('click', () => {
                    delete_cat(this);
                });
            }, null);
        });

        //set height for toolbar when change category
        setHeightToolbar();

        $folder_path.find('div').not(':first').unbind('click').on('click', function (e) {
            e.preventDefault();
            if ($(this).data('id') == 0) {
                return false;
            }
            if (Wptm.cat_active == $(this).data('id')) {
                var that = $(this).find('span').addClass('active');
                var position = that.offset();
                position = {'clientY': parseInt(position.top) + that.outerHeight() + 2,'clientX': position.left, 'width': that.outerWidth()};

                if ($(this).hasClass('wptm_hove_right_mouse_menu')) {

                }
            } else {
                show_cat($categories.find('.dd-item[data-id-category="' + $(this).data('id') + '"]'));
            }
        });

        // $categories.find('.cat_expand').toggle(
        //     function () {
        //         $(this).val('Hide').removeClass('show');
        //         $(this).siblings('.dd-list').hide();
        //     },
        //     function () {
        //         $(this).val('Show').addClass('show');
        //         $(this).siblings('.dd-list').show();
        //     }
        // );
        $categories.find('.cat_expand').removeClass('show').hide();
        $categories.find('.cat_expand').val('Hide');

        $cat_list.sortable({
            placeholder: 'highlight file',
            distance: 5,
            revert: true,
            tolerance: "intersect",
            appendTo: "body",
            items: "li.hasRole > .dd-handle",
            cursorAt: {top: 25, left: -5},
            change: function (event, ui) {
                $(ui.placeholder).hide();
                // $cat_list.find('li > .dd-handle').not($(Wptm.wptm_sortable).find(' > .dd-handle')).unbind('mousemove.mm').on('mousemove.mm', function(e) {
                $cat_list.find('li > .dd-handle').unbind('mousemove.mm').on('mousemove.mm', function(e) {
                    if (Wptm.sortcat) {
                        var highlight_file;
                        if (typeof Wptm.hover_cat !== 'undefined') {
                            Wptm.hover_cat.removeClass('hover_sort selector_sortable');
                        }
                        if (e.offsetY < 4) {
                            highlight_file = $('<div class="highlight_file before"><div></div></div>').insertBefore($(e.currentTarget));
                            $(e.currentTarget).addClass('hover_sort');
                        }
                        if (36 >= e.offsetY && e.offsetY >= 4) {
                            $(e.currentTarget).siblings('.highlight_file').remove();
                            $(e.currentTarget).removeClass('hover_sort').addClass('selector_sortable');
                        }
                        if (e.offsetY > 36) {
                            highlight_file = $('<div class="highlight_file after"><div></div></div>').insertAfter($(e.currentTarget));
                            $(e.currentTarget).addClass('hover_sort');
                        }
                        if (typeof Wptm.hover_cat !== 'undefined') {
                            $(Wptm.old_highlight_file).remove();
                        }
                        Wptm.hover_cat = $(e.currentTarget);
                        Wptm.old_highlight_file = highlight_file;

                        if (typeof Wptm.old_highlight_file !== 'undefined') {
                            Wptm.old_highlight_file.on('mouseup.mm', function(e) {
                                sortable_animation($(ui.helper));

                                Wptm.cat_target = $(e.currentTarget).parent();
                                if (Wptm.old_highlight_file.hasClass('after')) {
                                    Wptm.sortCatPosition = 'after';
                                } else {
                                    Wptm.sortCatPosition = 'before';
                                }
                                Wptm.sortcat = false;
                            });
                        }
                    }
                });

                $cat_list.find('li.dd-item > .ui-droppable').unbind('mouseup.mm').on('mouseup.mm', function(e) {
                    Wptm.cat_target = $(e.currentTarget).parent();
                    sortable_animation($(ui.helper));

                    if (typeof Wptm.old_highlight_file !== 'undefined') {
                        if (Wptm.old_highlight_file.hasClass('after')) {
                            Wptm.sortCatPosition = 'after';
                        } else {
                            Wptm.sortCatPosition = 'before';
                        }
                    }
                });
            },
            helper: function (e, item) {
                $cat_list.find('li > .cat_expand').siblings('.dd-handle').find(' > span.title').addClass('show_expand');
                $cat_list.find('li > button').hide();
                var filetext = $(item).find('.title').text();
                Wptm.sortcat = true;
                Wptm.wptm_sortable = $(item).parent();
                Wptm.wptm_sortable.find(' > ol').addClass('wptm_hiden');

                return $("<div id='wptm_folder_handle' class='wptm_draged_file ui-widget-header' ><div class='ext '><span class='folder_name'>" + filetext + "</span></div></div>");
            },
            /** Prevent firefox bug positionnement **/
            start: function (event, ui) {
                Wptm.wptm_sortable.addClass('wptm_sortable');
                $(ui.helper).css('width', 'auto');

                var userAgent = navigator.userAgent.toLowerCase();
                if (ui.helper !== "undefined" && userAgent.match(/firefox/)) {
                    ui.helper.css('position', 'absolute');
                }
            },
            stop: function (event, ui) {
                $cat_list.find('li > button').show();
                $cat_list.find('.highlight_file').remove();
                Wptm.wptm_sortable.find(' > ol').removeClass('wptm_hiden');
                $cat_list.find('.hover_sort').removeClass('hover_sort selector_sortable');
                $cat_list.find('li > .dd-handle').unbind('mousemove.mm');

                if (Wptm.wptm_sortable.find('ol').length > 0) {
                    Wptm.wptm_sortable.find('> button').after($(ui.item));
                } else {
                    $(ui.item).appendTo(Wptm.wptm_sortable);
                }
                //Wptm.wptm_sortable is <li> sortable

                var $cat_parent, cat_target;
                Wptm.notMoved = 0;

                if (typeof $(Wptm.cat_target).data("id-category") !== 'undefined') {//move cat
                    if (!$(Wptm.hover_cat).parent().hasClass('hasRole') && !$(Wptm.hover_cat).parent().hasClass('wptm_id_0')) {
                        Wptm.notMoved = 1;
                    }
                    var pk = $(Wptm.wptm_sortable).data('id-category');

                    $cat_parent = Wptm.cat_target;
                    cat_target = $cat_parent.data("id-category");

                    $cat_parent.addClass("ui-state-highlight");

                    var url = '';
                    url = wptm_ajaxurl + "task=categories.order&pk=" + pk + "&position=first-child&ref=" + cat_target;

                    if (Wptm.notMoved === 1 || pk == cat_target) {
                        Wptm.notMoved = 1;
                    } else {
                        if (cat_target != 0 && typeof Wptm.sortCatPosition !== 'undefined' && Wptm.sortCatPosition !== '') {//not TABLES
                            if (Wptm.sortCatPosition === 'after') {
                                url = wptm_ajaxurl + "task=categories.order&pk=" + pk + "&position=after&ref=" + cat_target;
                                if (!check_role('editCat', Wptm.dategory[cat_target].role)) {
                                    Wptm.notMoved = 1;
                                }
                            } else if (Wptm.sortCatPosition === 'before') {//before not cat nth(0)
                                if ($cat_parent.prev().length > 0) {//after
                                    url = wptm_ajaxurl + "task=categories.order&pk=" + pk + "&position=after&ref=" + $cat_parent.prev().data("id-category");
                                    if (!check_role('editCat', Wptm.dategory[$cat_parent.prev().data("id-category")].role)) {
                                        Wptm.notMoved = 1;
                                    }
                                } else if ($cat_parent.parents('li.dd-item').length > 0) {//first child of parent cat
                                    url = wptm_ajaxurl + "task=categories.order&pk=" + pk + "&position=first-child&ref=" + $($cat_parent.parents('li.dd-item')[0]).data("id-category");
                                    if (!check_role('editCat', Wptm.dategory[$($cat_parent.parents('li.dd-item')[0]).data("id-category")].role)) {
                                        Wptm.notMoved = 1;
                                    }
                                } else {//first cat
                                    url = wptm_ajaxurl + "task=categories.order&pk=" + pk + "&position=first-child&ref=0";
                                }
                            } else {
                                if (!check_role('editCat', Wptm.dategory[cat_target].role)) {
                                    Wptm.notMoved = 1;
                                }
                            }
                        } else {
                            if (!check_role('editCat', Wptm.dategory[cat_target].role)) {
                                Wptm.notMoved = 1;
                            }
                        }

                        if (Wptm.notMoved !== 1) {
                            $.ajax({
                                url: url,
                                type: "POST"
                            }).done(function (data) {
                                var result = jQuery.parseJSON(data);
                                if (result.response === true) {
                                    status_noti(1, wptmText.move_category);
                                } else {
                                    status_noti(0, wptmText.error_move_category + ', <a onClick="window.location.reload();">' + wptmText.please_reload + '</a>!');
                                }
                            });

                            if ($cat_parent.find('ol').length < 1 && cat_target != 0 && typeof Wptm.sortCatPosition !== 'string') {
                                $cat_parent.append('<ol class="dd-list"></ol>');
                                if ($cat_parent.data('id-category') != 0) {
                                    $cat_parent.prepend('<button class="cat_expand wptm_nestable show" data-action="expand"></button>');
                                }
                            }

                            var oll_ol = Wptm.wptm_sortable.parent();

                            if ($cat_list.find('.ui-state-highlight').data("id-category") == 'undefined' || cat_target == 0) {
                                Wptm.wptm_sortable.prependTo($categories.find('.scroll-content'));
                            } else {
                                if (typeof Wptm.sortCatPosition !== 'undefined') {
                                    if (Wptm.sortCatPosition == 'after') {
                                        Wptm.cat_target.after(Wptm.wptm_sortable);
                                    }
                                    if (Wptm.sortCatPosition == 'before') {
                                        Wptm.cat_target.before(Wptm.wptm_sortable);
                                    }
                                } else {
                                    Wptm.wptm_sortable.prependTo(Wptm.cat_target.find(' > ol'));
                                }
                            }

                            if (oll_ol.find('li').length < 1) {//remove old <ol>, <button>
                                oll_ol.siblings('.cat_expand').remove();
                                oll_ol.remove();
                            }

                            delete Wptm.sortCatPosition;
                            delete Wptm.cat_target;

                            show_cat(Wptm.wptm_sortable);
                            Wptm.wptm_sortable.removeClass('wptm_sortable');
                        }
                    }
                } else {
                    Wptm.notMoved = 1;
                }

                if (Wptm.notMoved == 1) {
                    delete Wptm.sortCatPosition;
                    delete Wptm.cat_target;
                    Wptm.wptm_sortable.removeClass('wptm_sortable');

                    update_changer();
                }

                $cat_list.find('.show_expand').removeClass('show_expand');
                $cat_list.find('.ui-state-highlight').removeClass('ui-state-highlight');
            }
        });

        $(document).unbind('click.window').bind('click.window', function (e) {
            if ($(e.target).is('#create_new')
                || $(e.target).parents('.cat_list').length > 0
                || $(e.target).parents('.folder_path').length > 0
                || $(e.target).parents('.wptm_top_toolbar').length > 0
                || $(e.target).parents('#create_new_menu').length > 0
                || $(e.target).parents('#wptm_popup').length > 0
                || $(e.target).parents('#right_mouse_menu').length > 0
                || $(e.target).parents('#wptm_bottom_toolbar').length > 0
                || $(e.target).parents('#header_list_tables').length > 0
                || $(e.target).parents('#list_tables').length > 0
            ) {
                return;
            }
            $list_tables.find('.dd-item.selected').removeClass('selected');
            delete Wptm.table;

            fix_style_top_toolbar();
        });

        $list_tables.find('.dd-item').unbind('click').click(function (e) {
            if (!(e.ctrlKey || e.metaKey)) {
                $(this).siblings('.dd-item.selected').removeClass('selected');
            }

            $(this).addClass('selected');
            Wptm.iselected = $list_tables.find('.dd-item.selected').length;
            Wptm.table = $(this).data('id-table');

            if (Wptm.iselected > 1) {
                Wptm.disableTable = true;
            } else {
                Wptm.disableTable = false;
            }

            $('#wptm_select_type_table').hide();
            $('.wptm_select_type_table').removeClass('show');
            $(document).unbind('click.select_type_table');

            if ($('#inserttable').length > 0 && !$(e.target).hasClass('hasChart')) {
                $list_tables.find('.tbody.active').trigger('click');
                if (!$('#inserttable').hasClass('not_change_type')) {
                    $('#inserttable').data('type', 'table').attr('data-type', 'table').text(insert_table);
                }
                $('#inserttable').removeClass("no_click");
            }

            $right_mouse_menu.hide();
            $right_mouse_menu.addClass('wptm_hiden');
            $('body').unbind('click.mm');
            $folder_path.find('div:last-child').addClass('wptm_hove_right_mouse_menu').find('.active').removeClass('active');

            fix_style_top_toolbar();

            e.stopPropagation();
        });

        //data source
        $list_tables.find('.table_name .data_source').unbind('click').on('click', function (e) {
            if (!wptm_permissions.can_edit_tables) {
                alert(wptm_permissions.translate.can_edit_tables);
                return false;
            }

            if (canInsert === 1) {
                window.location = wptm_db_table + 'id_table=' + $(this).closest('tr').data('id-table') + '&noheader=1&caninsert=1';
            } else {
                window.location = wptm_db_table + 'id_table=' + $(this).closest('tr').data('id-table');
            }
        });

        if (typeof Wptm.table_selected !== 'undefined') {//active table in class editor
            if (typeof window.parent.wptm_insert !== 'undefined' && typeof window.parent.wptm_insert.opend_table !== 'undefined'
                && $list_tables.find('.dd-item[data-id-table="' + Wptm.table_selected + '"]').length > 0) {
                delete window.parent.wptm_insert.opend_table;
                if (typeof window.parent.wptm_insert.chart !== 'undefined') {//open the chart inserted
                    // window.location = document.querySelector('.dd-item[data-id-table="' + Wptm.table_selected + '"] a.t').href + '&chart=' + window.parent.wptm_insert.chart;
                } else {
                    // window.location = document.querySelector('.dd-item[data-id-table="' + Wptm.table_selected + '"] a.t').href;
                }
            } else {
                if (parseInt(chart_active)< 1) {//no chart active
                    $list_tables.find('.dd-item[data-id-table="' + Wptm.table_selected + '"]').trigger('click');
                }
            }
        }

        //table sort
        $list_tables.find("tbody").sortable({
            placeholder: 'highlight file',
            distance: 5,
            revert: true,
            tolerance: "pointer",
            appendTo: "body",
            cursorAt: {top: 35, left: -5},
            items: '.dd-item.hasRole:not(.no_sortable)',
            helper: function (e, item) {
                var fileext = $(item).find('.table_name .title').text();
                if (!$(item).hasClass('selected')) {
                    $(item).siblings('.selected').removeClass('selected');
                }
                $(item).addClass('selected');
                $(item).addClass('wptm_sortable');
                Wptm.table = $(item).data('id-table');

                var type = $(item).data('type');
                Wptm.iselected = 1;
                // if (Wptm.iselected > 1) {
                //     return $("<span id='wptm_file_handle' class='wptm_draged_file ui-widget-header' ><div class='ext '><i class='wptm_icon_tables'></i>"+fileext+"</div><span class='fCount'>" + Wptm.iselected + "</span></div>");
                // } else {
                return $("<div id='wptm_file_handle' class='wptm_draged_file ui-widget-header " + type + "' ><div class='ext '><i class='wptm_icon_tables'></i>" + fileext + "</div></div>");
                // }
            },
            /** Prevent firefox bug positionnement **/
            start: function (event, ui) {
                $(ui.helper).css('width', 'auto');

                var userAgent = navigator.userAgent.toLowerCase();
                if (ui.helper !== "undefined" && userAgent.match(/firefox/)) {
                    ui.helper.css('position', 'absolute');
                }
                ui.placeholder.html("<td colspan='8'></td>");
            },
            stop: function (event, ui) {
                $cat_list.find('.ui-state-highlight').removeClass('ui-state-highlight');
                ui.item.removeClass('wptm_sortable');

                $list_tables.find('tr.list_chart').each(function () {
                    $(this).prev().find('.hasChart').removeClass('open');
                    $(this).remove();
                });

                if (Wptm.notMoved == 0 && Wptm.id_cat_target > 0 && Wptm.id_cat_target != Wptm.cat_active && check_role('editCat', Wptm.dategory[Wptm.id_cat_target].role)) {
                    $(ui.item).hide();

                    $(ui.placeholder).hide();
                    //can droppable tables(do after)
                    $.ajax({
                        url: wptm_ajaxurl + "task=table.changeCategory&id=" + $(ui.item).data('id-table') + "&category=" + Wptm.id_cat_target
                    }).done(function (data) {
                        result = jQuery.parseJSON(data);

                        if (result.response === true) {
                            $(ui.item).remove();
                            $list_tables.trigger("update");
                            delete Wptm.id_cat_target;
                            update_changer();
                            status_noti(1, wptmText.more_table);
                        } else {
                            status_noti(0, result.response);
                        }
                    });
                }
            },
            update: function (event, ui) {
                if (typeof Wptm.id_cat_target == 'undefined' || Wptm.id_cat_target == Wptm.cat_active) {
                    var sortedIDs = $(this).sortable("toArray", {attribute: "data-id-table"});
                    var before = 0;

                    if (sortedIDs[0] != Wptm.table) {
                        before = $(ui.item).prev().data('id-table');
                    }

                    $.ajax({
                        url: wptm_ajaxurl + "task=table.order&table=" + Wptm.table + "&before=" + before + "&cat=" + Wptm.cat_active,
                        type: "POST"
                    }).done(function (data) {
                        result = jQuery.parseJSON(data);

                        $(ui.item).show();

                        if (result.response === true) {
                            update_changer();
                            status_noti(1, wptmText.order_table);
                        } else {
                            status_noti(0, result.response);
                        }
                    });
                } else {
                    $(ui.item).hide();
                }
            }
        });

        $list_tables.find('.copy_text').unbind('click').on('click', function (e) {
            copy_text($(this));
        });

        $list_tables.find('.dd-item').contextmenu(function (e) {
            $(this).trigger('click');
            e.preventDefault();
            right_click.call(e, function () {
                if (!$(e.currentTarget).hasClass('hasRole')) {
                    return false;
                }

                if (!wptm_permissions.can_delete_tables) {
                    $right_mouse_menu.find('.delete_table_menu').addClass('wptm_hiden');
                } else {
                    $right_mouse_menu.find('.delete_table_menu').removeClass('wptm_hiden');
                }

                if (!check_role('hasRole', Wptm.tables[$(e.currentTarget).data('id-table')])) {
                    $right_mouse_menu.find('.edit_table_menu').addClass('wptm_hiden');
                    $right_mouse_menu.find('.rename_table_menu').addClass('wptm_hiden');
                } else {
                    $right_mouse_menu.find('.edit_table_menu').removeClass('wptm_hiden');
                    $right_mouse_menu.find('.rename_table_menu').removeClass('wptm_hiden');
                    $right_mouse_menu.find('.rename_table_menu').removeClass('wptm_right_menu_top');
                }

                if (!wptm_permissions.can_create_tables) {
                    $right_mouse_menu.find('.copy_table_menu').addClass('wptm_hiden');
                } else {
                    $right_mouse_menu.find('.copy_table_menu').removeClass('wptm_hiden');
                }

                $right_mouse_menu.removeClass('wptm_hiden show');

            }, function() {
                $right_mouse_menu.find('.edit_table_menu').unbind('click').on('click', function () {
                    $wptm_top_toolbar.find(' .edit_table').trigger('click');
                });
                $right_mouse_menu.find('.rename_table_menu').unbind('click').on('click', function () {
                    $wptm_top_toolbar.find('.re_name').trigger('click');
                });
                $right_mouse_menu.find('.delete_table_menu').unbind('click').on('click', function () {
                    $wptm_top_toolbar.find('.delete').trigger('click');
                });
                $right_mouse_menu.find('.copy_table_menu').unbind('click').on('click', function () {
                    $wptm_top_toolbar.find('.copy').trigger('click');
                });
            }, null);
        });

        $cat_list.find("li.dd-item > .ui-droppable").droppable({
            hoverClass: "dd-content-hover",
            containment: $cat_list.find('.scroll-content'),
            tolerance: "pointer",
            drop: function (event, ui) {
                var $cat_parent;
                var type = 0;
                Wptm.notMoved = 0;

                sortable_animation($('#wptm_file_handle'));

                if (typeof $(ui.draggable).data("id-table") !== 'undefined') {
                    $cat_parent = $(event.target).parent();
                    Wptm.id_cat_target = $cat_parent.data("id-category");
                    $(this).addClass("ui-state-highlight");
                    Wptm.notMoved = !$(ui.draggable).hasClass('hasRole') ? 1 : 0;

                    if ((!$cat_parent.hasClass('hasRole') && Wptm.id_cat_target != 0)) {
                        Wptm.notMoved = 1;
                    }

                    if (Wptm.id_cat_target == Wptm.cat_active) {
                        Wptm.notMoved = 1;
                    }

                    if (Wptm.id_cat_target == 0) {//not move table cat id = 0
                        Wptm.notMoved = 1;
                    }

                    if (Wptm.notMoved === 1) {
                        return false;
                    }

                    if (Wptm.id_cat_target > 0 && Wptm.id_cat_target != Wptm.cat_active) {
                        return false;
                    }
                }
            }
        });

        $list_tables.find('.hasChart').unbind('click').on('click', function (e) {
            e.preventDefault();
            $list_tables.find('tr.list_chart').remove();
            if ($(this).hasClass('open')) {
                /*close charts list*/
                $(this).removeClass('open');
            } else {
                $list_tables.find('.hasChart.open').removeClass('open');
                var id_table = $(this).parents('tr').data('id-table');
                /*open charts list*/
                var html = '';
                html += '<tr class="list_chart"><td colspan="4"><div>';
                if (typeof list_chart[id_table] !== 'undefined', list_chart[id_table].length > 0) {
                    html += '<div class="thead"><div class="title_chart">' + wptmText.chart_title + '</div><div class="time">' + wptmText.last_edit + '</div><div class="shortcode">Shortcode</div></div><div class="tbodys" data-scrollbar>';
                    $.each(list_chart[id_table], function (i, v) {
                        if (canInsert === 0) {
                            html += '<div class="tbody" data-position="' + i + '" data-id="' + v.id + '"><div class="title_chart"><a href="' + wptm_table_url + 'id_table=' + id_table + '&chart=' + v.id + '">'
                                + v.title + '</a></div><div class="time">'
                                + v.modified_time + '</div><div class="shortcode"><span>[wptm id-chart=' + v.id + ']</span><span class="button wptm_icon_tables copy_text tooltip"></span></div></div>';
                        } else {
                            html += '<div class="tbody" data-position="' + i + '" data-id="' + v.id + '"><div class="title_chart"><a href="' + wptm_table_url + 'id_table=' + id_table + '&chart=' + v.id + '&noheader=1&caninsert=1">' + v.title + '</a></div><div class="time">' + v.modified_time + '</div><div class="shortcode"><span>[wptm id-chart=' + v.id + ']</span><span class="button wptm_icon_tables copy_text tooltip"></span></div></div>';
                        }
                    });
                    html += '</div></div>';
                }
                html += '</div></td></tr>';
                $(this).addClass('open');
                $(this).parents('tr').after(html);
                update_chart();
            }
        });

        $('#over_loadding_open_chart').hide();
    }

    function delete_cat (that) {
        var id_cat = $(that.currentTarget).parent().data('id-category');

        if (!check_role('deleteCat', Wptm.dategory[id_cat].role)) {
            alert(wptm_permissions.translate.wptm_delete_category);
            return false;
        }

        if (parseInt(id_cat) > 0) {
            var popup = {
                'html': $content_popup_hide.find('#delete_tables'),
                'showAction': function () {
                    this.find('.wptm_done').addClass('wptm_red');
                    this.find('.wptm_cancel').addClass('wptm_grey');
                    this.find('#popup_done').text(wptmText.Delete);
                    this.siblings('.colose_popup').hide();

                    this.find('.delete_table').text(wptmText.delete_category);
                    this.find('.delete_table_question').text(wptmText.delete_category_question);
                    return true;
                },
                'submitAction': function () {
                    $.ajax({
                        url: wptm_ajaxurl + "task=categories.delete&id_category=" + id_cat,
                        type: 'POST',
                        data: {},
                        success: function (datas) {
                            var resultdata;
                            resultdata = jQuery.parseJSON(datas);
                            if (resultdata.response === true) {
                                var cat = $categories.find('.dd-item[data-id-category="' + id_cat + '"]');
                                var parent_cat = cat.parents('li.dd-item');

                                if (cat.siblings('li').length < 1 && parent_cat.length > 0) {
                                    $(parent_cat[0]).find('ol').remove();
                                    $(parent_cat[0]).find('button').remove();
                                } else {
                                    cat.remove();
                                }

                                if (Wptm.cat_active == id_cat) {
                                    if (parent_cat.length > 0) {
                                        parent_cat = $(parent_cat[0]);
                                    } else {
                                        parent_cat = cat.next();
                                    }

                                    if (parent_cat.length < 1) {
                                        add_new_cat(wptmText.new_name_category, 1);
                                    } else {
                                        show_cat(parent_cat);
                                    }
                                }

                                status_noti(1, wptmText.noti_delete_category);
                            } else {
                                status_noti(0, resultdata.response);
                            }
                        },
                        error: function (jqxhr, textStatus, error) {
                            alert(textStatus + " : " + error);
                        }
                    });
                    this.siblings('.colose_popup').trigger('click');
                    return true;
                }
            };
            wptm_popup($('#wptm_popup'), popup, true, true);
        }
    }

    function change_cat (that) {
        var id_cat;
        if (typeof that.currentTarget !== 'undefined') {
            id_cat = $(that.currentTarget).parent().data('id-category');
        } else {
            id_cat = $(that).parent().data('id-category');
        }

        if (!check_role('editCat', Wptm.dategory[id_cat].role)) {
            alert(wptm_permissions.translate.wptm_edit_category);
            return false;
        }

        var popup = {
            'html': $content_popup_hide.find('#change_cat'),
            'inputEnter': true,
            'showAction': function () {
                this.find('.wptm_done').addClass('wptm_blu');
                this.find('.wptm_cancel').addClass('wptm_grey');

                if (typeof Wptm.dategory[id_cat] !== 'undefined') {
                    this.find('input[name="re_name"]').val(Wptm.dategory[id_cat].title).focus();

                    var category_own_id = parseInt(Wptm.dategory[id_cat].role[0]);
                    this.find('#jform_role_cat').val(category_own_id).change();
                }

                return true;
            },
            'submitAction': function () {
                var name = this.find('input[name="re_name"]').val();
                if (name.trim() !== '' && name !== Wptm.dategory[id_cat].title) {//change name category
                    $.ajax({
                        url: wptm_ajaxurl + "task=category.setTitle&id_category=" + id_cat + '&title=' + name,
                        type: "POST",
                        data: {},
                        dataType: "json",
                        success: function (datas) {
                            if (datas.response === true) {
                                $categories.find('.dd-item[data-id-category="' + id_cat + '"]').find('> .dd-handle .title').text(name);
                                $table_list.find('.folder_path').find('div[data-id="' + id_cat + '"]').text(name);
                                Wptm.dategory[id_cat].title = name;
                                status_noti(1, wptmText.noti_category_renamed);
                            } else {
                                status_noti(0, datas.response);
                            }
                        },
                        error: function (jqxhr, textStatus, error) {
                            alert(textStatus);
                        }
                    });
                }

                var category_own_id = this.find('#jform_role_cat').val();
                if (parseInt(category_own_id) !== 0 && parseInt(Wptm.dategory[id_cat].role[0]) !== parseInt(category_own_id)) {//change own category
                    var jsonVar = {
                        data: JSON.stringify({0: category_own_id}),
                        id: id_cat,
                        type: 0
                    };
                    $.ajax({
                        url: wptm_ajaxurl + "task=user.save",
                        dataType: "json",
                        type: "POST",
                        data: jsonVar,
                        success: function (datas) {
                            if (datas.response === true) {
                                Wptm.dategory[id_cat].role = jQuery.parseJSON(datas.datas).role;
                                status_noti(1, wptmText.CHANGE_ROLE_OWN_CATEGORY);
                            } else {
                                status_noti(0, datas.response);
                            }
                        },
                        error: function (jqxhr, textStatus, error) {
                            alert(textStatus + " : " + error);
                        }
                    });
                }
                this.siblings('.colose_popup').trigger('click');
                return true;
            }
        };
        wptm_popup($('#wptm_popup'), popup, true, true);
    }

    function sortable_animation (that) {
        that.animate({
            width: '0',
            height: '0',
            opacity: .6
        }, 150, "linear", function () {
            $( this ).hide();
        });
    }

    function update_chart () {
        Scrollbar.init(document.querySelector('.tbodys'), {
            damping: 0.5,
            thumbMinSize: 10,
            alwaysShowTracks: false
        });
        var list_chart = $list_tables.find('tr.list_chart');

        list_chart.find('.tbody').unbind('click').on('click', function () {
            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
                chart_active = 0;
            } else {
                list_chart.find('.tbody.active').removeClass('active');
                $(this).addClass('active');
                if (!$('#inserttable').hasClass('not_change_type') && typeof insert_chart !== 'undefined') {
                    $('#inserttable').text(insert_chart).data('type', 'chart').attr('data-type', 'chart');
                }
                chart_active = $(this).data('id');

                /*active parent table*/
                list_chart.siblings('.dd-item.selected').removeClass('selected');
                Wptm.iselected = 1;
                Wptm.table = list_chart.prev().addClass('selected').data('id-table');
            }
        });

        if (parseInt(chart_active) > 0) {//active chart selected
            $list_tables.find('.tbody[data-id="' + chart_active + '"]').trigger('click');
        }

        list_chart.find('.tbody').find('.copy_text').unbind('click').on('click', function (e) {
            copy_text($(this));
        });
    }

    function right_click (before_show, action, position) {
        $right_mouse_menu.removeClass('wptm_box_shadow');

        // right_mouse_menu
        let x, y;
        if (position === null) {
            x = this.clientX;     // Get the horizontal coordinate
            y = this.clientY;
        } else {
            x = position.clientX;     // Get the horizontal coordinate
            y = position.clientY;
        }
        before_show.call(this);

        var show = {};

        if (x + $right_mouse_menu.width() > $(window).width()) {
            if (position !== null) {
                show.right = $(window).width() - x - position.width + 'px';
            } else {
                show.right = $(window).width() - x + 'px';
            }
            show.left = 'auto';
            show.bottom = 'auto';
            show.top = y + 'px';
            // show.opacity = 1;
        } else {
            show.left = x + 'px';
            show.right = 'auto';
            show.bottom = 'auto';
            show.top = y + 'px';
            // show.opacity = 1;
        }

        if (y + $right_mouse_menu.outerHeight() > $(window).height()) {
            show.top = 'auto';
            show.bottom = $(window).height() - y + 'px';
        }

        $right_mouse_menu.slideDown(200, function () {
            $(this).addClass('wptm_box_shadow');
        }).css(show);

        action.call(this);
        $('body').bind('click.mm', function (e) {
            $right_mouse_menu.removeClass('wptm_box_shadow');

            if (!$(e.target).hasClass('wptm_hove_right_mouse_menu') && !$(e.target).parent().hasClass('wptm_hove_right_mouse_menu')) {
                $right_mouse_menu.hide();
                $('body').unbind('click.mm');
                $folder_path.find('div:last-child').addClass('wptm_hove_right_mouse_menu').find('.active').removeClass('active');
            } else {
                $folder_path.find('div:last-child').removeClass('wptm_hove_right_mouse_menu');
            }
        });
    }

    //list table sorter
    $list_tables.tablesorter({
        theme: "bootstrap",
        widthFixed: true,
        headerTemplate: '{content} {icon}',
        widgets: ["uitheme", "zebra"],
        dateFormat: 'ddmmyyyy',
        cssIcon: '',
        imgAttr: 'src',
        headers: {
            '.disable_sort': {
                sorter: false
            }
        },
        sortList: [[0, 0]]
    });
    var column_sort = {};
    $list_tables.bind("sortEnd",function(e, t) {
        if (canInsert === 1) {
            $list_tables.find('tr.list_chart').remove();
            $list_tables.find('.hasChart.open').removeClass('open');
        }
        if (typeof column_sort[1] !== 'undefined') {
            $list_tables.prev().find('td').removeClass('tablesorter-headerDesc tablesorter-headerAsc');
            if (column_sort[1].hasClass('tablesorter-headerDesc')) {
                column_sort[0].addClass('tablesorter-headerDesc');
            } else {
                column_sort[0].addClass('tablesorter-headerAsc');
            }
        }
    });

    $list_tables.prev().find('td').unbind('click').on('click', function () {
        column_sort[0] = $(this);
        if ($(this).hasClass('name')) {
            $list_tables.find('thead td.name').trigger('click');
            column_sort[1] = $list_tables.find('thead td.name');
        }
        if ($(this).hasClass('last_edit')) {
            $list_tables.find('thead td.last_edit').trigger('click');
            column_sort[1] = $list_tables.find('thead td.last_edit');
        }
    });

    function get_list_table(value, content) {
        if (value !== 'none') {
            $.ajax({
                url: wptm_ajaxurl + "task=table.getListTables",
                type: 'POST',
                data: {
                    'id': value,
                    'canInsert': canInsert,
                    'option_nonce': $('#option_nonce').val(),
                }
            }).done(function (data) {
                var result;
                result = jQuery.parseJSON(data);
                if (result.response === true) {
                    $tbody.contents().remove();
                    var tables = result.datas.tables;
                    Wptm.tables = $.extend({}, {});
                    // Wptm.showDbTables = false;

                    $.each(tables, function (i, v) {
                        Wptm.tables[v.id] = v;

                        add_new_tr(v);
                    });

                    if (typeof Wptm.table !== 'undefined') {
                        delete Wptm.table;
                    }
                    Wptm.cat_active = result.datas.id;

                    if (tables.length == 0 && $list_tables.find('.no_table').length < 1) {
                        $tbody.append('<span class="no_table no_sortable">' + wptmText.no_table_found + '</span>');
                    }

                    $list_tables.trigger("update");
                    update_changer(content);
                } else {
                    alert(result.response);
                }
            });
        }
    }

    /*create new <tr/> table*/
    function add_new_tr(value) {
        $tbody.find('.no_table').remove();
        var html = '';
        var class_name = '';

        if (check_role('hasRole', value)) {
            class_name += ' hasRole ' + value.type;
        }

        if (!check_role('showDbTable', value)) {
            class_name += ' wptm_not_access ';
        }

        html += '<tr class="dd-item ' + class_name + '" data-type="' + value.type + '" data-id-table="' + value.id + '" data-role="' + value.author + '" data-position="' + value.position + '">';

        html += '<td class="indicator wptm_hiden">' + value.position + '</td>';

        html += '<td class="dd-content table_name"><i class="wptm_icon_tables"></i><div>';
        if (canInsert === 1) {
            html += '<a class="t" href="' + wptm_table_url + 'id_table=' + value.id + '&noheader=1&caninsert=1"><span class="title dd-handle">' + value.title + '</span>';
            html += '</a>';
            if ((typeof list_chart !== 'undefined' && typeof list_chart[value.id] !== 'undefined')
                && !($('#inserttable').data('type') === 'table' && (window.parent.wptm_bakery_edit || window.parent.wptm_elementor_edit || window.parent.wptm_vada_edit))) {//don't show chart when insert table
                html += '<i class="hasChart"></i>';
            }
        } else {
            if (typeof wptm_open_table !== 'undefined' && wptm_open_table === '0') {
                html += '<a class="t" href="' + wptm_table_url + 'id_table=' + value.id + '"><span class="title dd-handle">' + value.title + '</span></a>';
            } else {
                html += '<a class="t" href="' + wptm_table_url + 'id_table=' + value.id + '" target="_blank"><span class="title dd-handle">' + value.title + '</span></a>';
            }
            if ((typeof list_chart !== 'undefined' && typeof list_chart[value.id] !== 'undefined')
                && !($('#inserttable').data('type') === 'table' && (window.parent.wptm_bakery_edit || window.parent.wptm_elementor_edit || window.parent.wptm_vada_edit))) {//don't show chart when insert table
                html += '<i class="hasChart"></i>';
            }
        }
        if (typeof value.type !== 'undefined' && value.type == 'mysql') {
            html += '<a class="data_source tooltip"></a>';
        }
        html += '</div></td>';

        html += '<td>' + value.modified_time + '</td>';

        html += '<td class="dd-content shortcode"><div><div><span>[wptm id=' + value.id + ']</span><span class="button wptm_icon_tables copy_text tooltip"></span></div>' +
            '</div></td>';

        html += '</tr>';
        $tbody.append(html);
    }

    /*
    popup and action for this
    wptm_popup     #wptm_popup
    popup          object data popup
    clone          check clone content in popup
    submit_button  get submit button to popup window
    */
    function wptm_popup(wptm_popup, popup, clone, submit_button) {
        wptm_popup.find('.content').contents().remove();
        var over_popup = wptm_popup.siblings('#over_popup');
        var that;
        if (!clone) {
            that = wptm_popup.find('.content').append(popup.html);
        } else {
            that = wptm_popup.find('.content').append(popup.html.clone());
        }

        if (submit_button === true) {
            that.find('>div').append($('#submit_button').clone());
        }

        wptm_popup.css({'top': $folder_path.position().top});

        wptm_popup.show();
        over_popup.show();

        //set top for popup
        // wptm_popup.css('top', (over_popup.outerHeight() - wptm_popup.outerHeight()) / 2);
        // wptm_popup.css('left', (over_popup.outerWidth() - wptm_popup.outerWidth()) / 2);

        /*action when show popup*/
        if (typeof popup.showAction !== 'undefined') {
            popup.showAction.call(that);
        }

        /*action selector*/
        if (typeof popup.selector !== 'undefined') {
            popup.selector.call(that);
        }

        /*action enter input*/
        if (popup.inputEnter) {
            that.find('input').on('keyup', function (e) {
                if (e.keyCode === 13) {
                    that.find('#popup_done').trigger('click');
                }
                return true;
            });
        }

        /*click done button*/
        that.find('#popup_done').unbind('click').on('click', function (e) {
            e.preventDefault();
            if (typeof popup.submitAction !== 'undefined') {
                popup.submitAction.call(that);
            }
            return false;
        });

        /*click cancel button*/
        that.find('#popup_cancel').unbind('click').on('click', function (e) {
            e.preventDefault();
            if (typeof popup.cancelAction !== 'undefined') {
                popup.cancelAction.call(that);
            }
            wptm_popup.hide();
            over_popup.hide();
            return false;
        });

        //action colose
        wptm_popup.find('.colose_popup').unbind('click').on('click', function (e) {
            e.preventDefault();
            if (typeof popup.cancelAction !== 'undefined') {
                popup.cancelAction.call(that);
            }
            wptm_popup.hide();
            over_popup.hide();
            return false;
        });
        over_popup.unbind('click').on('click', function (e) {
            e.preventDefault();
            wptm_popup.find('.colose_popup').trigger('click');
            return false;
        });
        return false;
    }

    /*in elementor*/
    if (typeof window.parent.wptm_insert !== 'undefined' && typeof window.parent.wptm_insert.table !== 'undefined') {
        Wptm.selection = new Array();

        Wptm.table_selected = window.parent.wptm_insert.table;
        if (typeof window.parent.wptm_insert.chart !== 'undefined') {//insert chart
            setCategory(Wptm.table_selected, window.parent.wptm_insert.chart);
        } else {
            setCategory(Wptm.table_selected, '');
        }
    }

    /*open table when class editor*/
    if (canInsert === 1) {
        if (typeof (window.parent.tinyMCE) !== 'undefined') {
            if (window.parent.tinyMCE.activeEditor == null) {
                return;
            }
            var content = window.parent.tinyMCE.activeEditor.selection.getContent();
            var exp = '<img.*data\-wptmtable="([0-9]+)".*?>';
            var table = content.match(exp);
            Wptm.selection = new Array();
            Wptm.selection.content = content;
            if (table !== null) {
                Wptm.table_selected = table[1];
                setCategory(table[1], content);
            }
        }
    }

    function active_chart (content) {
        if (parseInt(content) > 0) {
            chart_active = parseInt(content);
        } else {
            var exp2 = '<img.*data\-wptm\-chart="([0-9]+)".*?>';
            var table2 = content.match(exp2);
            if (table2 !== null) {
                chart_active = table2[1];
            }
        }

        if (parseInt(chart_active) > 0 && $list_tables.find('.dd-item[data-id-table="' + Wptm.table_selected + '"]').length > 0) {
            $list_tables.find('.dd-item[data-id-table="' + Wptm.table_selected + '"]').find('.hasChart').trigger('click');
        }
    }

    function setCategory ($idTable, content) {
        if (parseInt($idTable) > 0) {
            $.ajax({
                url: wptm_ajaxurl + "task=table.getCategoryById",
                type: 'POST',
                data: {
                    'id': $idTable,
                    'option_nonce': $('#option_nonce').val(),
                }
            }).done(function (data) {
                var result;
                result = jQuery.parseJSON(data);
                if (!result.response) {
                    return false;
                }
                var id_cat = result.datas[0];

                if ($categories.find('.dd-item[data-id-category="' + id_cat + '"]').hasClass('hasRole')) {
                    show_cat($categories.find('.dd-item[data-id-category="' + id_cat + '"]'), content);
                }
            });
        }
    }

    function status_noti (status, text) {
        var status_e = $('#savedInfoTable');
        if (status === 1) {
            status_e = $('#savedInfoTable');
        } else {
            status_e = $('#saveErrorTable');
        }
        status_e.html(text);
        if (!$('body').hasClass('wp-admin')) {
            status_e.css({'bottom': '60px'});
        }
        setTimeout(function () {
            status_e.animate({'opacity': '1'}, 500).delay(2000).animate({'opacity': '0'}, 1000);
        }, 1000);
    }

    /*resize categories list*/
    var leftwidth = parseInt($wptm_categories_list.width());
    var leftHeight = parseInt($wptm_categories_list.height());
    var rightWidth = $table_list.width();
    var wptm_categories_resizable = getCookie('wptm_categories_resizable');

    if (parseInt($('#mybootstrap.wptm-tables').width()) > 680 && typeof wptm_categories_resizable !== 'undefined' && parseInt(wptm_categories_resizable) > 0) {
        $wptm_categories_list.width(parseInt(wptm_categories_resizable));
        set_width_table_list(rightWidth - wptm_categories_resizable + leftwidth, true);
    }

    function smallDisplay () {
        if (parseInt($('#mybootstrap.wptm-tables').width()) < 680 && $wptm_categories_list.find('.wptm_show_cat').length < 1) {
            $wptm_show_cat = $('<div class="wptm_show_cat"><i class="dashicons dashicons-leftright"></i></div>').appendTo($wptm_categories_list);
            $wptm_categories_list.width(0);
            set_width_table_list(rightWidth - 0 + leftwidth, true);
            $wptm_categories_list.css({'position': 'absolute'});

            $wptm_show_cat.click(function () {
                if (!$wptm_show_cat.hasClass('show')) {
                    $wptm_show_cat.val('Hide').addClass('show');
                    $wptm_categories_list.animate({width: '100%', opacity: '1'}, "slow");
                } else {
                    $wptm_show_cat.val('Show').removeClass('show');
                    $wptm_categories_list.animate({
                        width: '0%',
                        opacity: '0'
                    }, "slow").delay(10).animate({'opacity': '1'}, 10);
                }
            });
        } else if (parseInt($('#mybootstrap.wptm-tables').width()) > 680 && $wptm_categories_list.find('.wptm_show_cat').length > 0) {
            $wptm_categories_list.find('.wptm_show_cat').remove();
            $wptm_categories_list.width(parseInt(wptm_categories_resizable));
            set_width_table_list(rightWidth - wptm_categories_resizable + leftwidth, true);
            $wptm_categories_list.css({'position': 'fixed'});
        }
    }

    $( window ).resize(function() {
        smallDisplay();
        set_width_table_list(parseInt($('#mybootstrap.wptm-tables').width()) - parseInt($wptm_categories_list.width()), true);
    });

    //small display
    smallDisplay();

    if ($('#collapse-button').length > 0) {
        document.getElementById("collapse-button").addEventListener("click", function() {
            var full_width = parseInt($('#mybootstrap.wptm-tables').width());
            set_width_table_list(full_width - parseInt($wptm_categories_list.width()), true);
        });
    }

    $wptm_categories_list.resizable({handles: "e", maxWidth: 600}).resize(function (event, ui) {
        var width = parseInt(this.style.width);
        wptm_setCookie("wptm_categories_resizable", width, 30);
        set_width_table_list(rightWidth - width + leftwidth, false);
    });

    function set_width_table_list(width, set_height_toolbar) {
        if ($table_list.height() > leftHeight) {
            $table_list.width(width - 25);
        } else {
            $table_list.width(width);
        }
        if (width < 820) {
            $table_list.removeClass('mobile_small_size').addClass('small_size');
        } else  {
            $table_list.removeClass('small_size');
        }
        if (width < 600) {
            $table_list.removeClass('small_size').addClass('mobile_small_size');
        } else {
            $table_list.removeClass('mobile_small_size');
        }
        $list_tables.prev().width($list_tables.width());

        if (set_height_toolbar) {
            setHeightToolbar();
        }
    }
    $wptm_categories_list.on("resizestop", function( event, ui) {
        setHeightToolbar();
    });
    function setHeightToolbar () {
        /*change folder path*/
        var folder_path = $folder_path.find('div');
        if (folder_path.length > 4) {
            var i = 2;
            for (i = 2; i < folder_path.length - 2; i++) {
                $(folder_path[i]).hide();
            }
            $(folder_path[folder_path.length - 2]).addClass('hide_prev_folder');
        }

        fix_style_top_toolbar();
    }

    function fix_style_top_toolbar () {
        var category_name = $wptm_top_toolbar.find('.category_name');

        if (parseInt(Wptm.table) > 0) {
            /*show icon*/
            category_name.next().addClass('show_button').find(' span').show();
        } else {
            category_name.next().removeClass('show_button').find(' span').hide();
        }

        var button = category_name.next().find('div');
        var min_width = $wptm_top_toolbar.outerWidth() - $(button[0]).outerWidth() - $(button[1]).outerWidth();

        /*change text "table list"*/
        $wptm_top_toolbar.removeClass('wptm_2_row');
        if (!$table_list.hasClass('small_size') && !$table_list.hasClass('mobile_small_size')) {
            if (0 < min_width && min_width < 500) {
                if (min_width < 310) {
                    category_name.find('h2').text(wptmText.TABLE_LIST_FULL);
                    $wptm_top_toolbar.addClass('wptm_2_row');
                } else {
                    category_name.find('h2').text(wptmText.TABLE_LIST);
                }
            } else {
                category_name.find('h2').text(wptmText.TABLE_LIST_FULL);
            }
        } else {
            category_name.find('h2').text(wptmText.TABLE_LIST_FULL);
        }

        var height = parseInt($table_list.find('.folder_path').outerHeight());
        var top_toolbar_height = parseInt($wptm_top_toolbar.outerHeight()) + height;
        $table_list.css( "grid-template-rows", top_toolbar_height + "px auto" );
    }
    /*end categories list*/

    function wptm_setCookie (cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function getCookie (name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length == 2) return parts.pop().split(";").shift();
    }

    function copy_text (that) {
        var copyText = $(that).parent().find('span:not(.copy_text)');
        var textArea = document.createElement("textarea");
        textArea.value = copyText.text();
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("Copy");
        textArea.remove();

        status_noti(1, wptmText.copy_shortCode);
    }

    function check_role (action, data) {
        var role = Wptm.roles;
        switch (action) {
            case 'editCat':
                var check = false;
                $.each(data, (i, v) => {
                    if (parseInt(v) == Wptm.idUser) {check = true;}
                });
                if (role.wptm_edit_category || (check && role.wptm_edit_own_category)) {
                    return true;
                }
                return false;
                break;
            case 'deleteCat':
                if (check_role('editCat', data) && role.wptm_delete_category) {
                    return true;
                }
                return false;
                break;
            case 'createCat':
                if (role.wptm_create_category) {
                    return true;
                }
                return false;
                break;
                //start table
            case 'editTable':
                if ((data.author == Wptm.idUser && role.wptm_edit_own_tables) || role.wptm_edit_tables) {
                    return true;
                }
                return false;
                break;
            case 'deleteTable':
                if (check_role('editTable', data) && role.wptm_delete_tables) {
                    return true;
                }
                return false;
                break;
            case 'createTable':
                if (role.wptm_create_tables) {
                    return true;
                }
                return false;
                break;
            case 'showDbTable':
                if (data.type === 'html' || role.wptm_access_database_table) {
                    return true;
                }
                return false;
                break;
            case 'hasRole':
                if ((data.author == Wptm.idUser && role.wptm_edit_own_tables) || role.wptm_edit_tables) {
                    return true;
                }
                return false;
                break;
        }
        return false;
    }

    function set_background_table () {
        $list_tables.find('tr:not(.ui-sortable-placeholder):visible:odd td').css({'background-color': '#F4F6FD'});
        $list_tables.find('tr:not(.ui-sortable-placeholder):visible:even td').css({'background-color': '#ffffff'});
    }
});
