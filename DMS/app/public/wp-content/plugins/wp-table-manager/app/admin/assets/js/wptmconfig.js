/**
 * Created by USER on 12/03/2018.
 */
jQuery(document).ready(function($) {
    var $controlFormatStyle = $('#control_format_style');
    var $list_format_style  = $controlFormatStyle.find('#list_format_style');
    var $new_format_style   = $controlFormatStyle.find('#new_format_style');
    var $set_color          = $controlFormatStyle.find('#set_color');
    var $save_format_style  = $controlFormatStyle.find('#save_format_style');
    var active_format_color = '';
    var $wptm_popup = $('#wptm_popup');
    var $over_popup = $('#over_popup');
    var $content_popup_hide = $('#content_popup_hide');

    //local font
    var $my_fonts = $('.ju-settings-option.my_fonts');
    var list_my_font = [];
    var list_my_font_loaded = [];

    $my_fonts.find('#add_new_font').unbind('click').on('click', function (e) {
        $my_fonts.find('.font_options').find('.add_new_font').remove();
        $my_fonts.find('.list_font_local').find('.font_google.active').removeClass('active');
        $my_fonts.find('.font_options .save_preview').before($content_popup_hide.find('.add_new_font').clone());
        $my_fonts.find('.font_options').find('.ju-button.save').data('value', 'add');
        edit_font();
    });

    function active_button_my_font($control_value, $my_fonts) {
        var number_item_group = $control_value.find('.font-item-group').length;
        if (number_item_group < 2) {
            $my_fonts.find('.font_options').find('.ju-button.remove').addClass('no_active');
        } else {
            $my_fonts.find('.font_options').find('.ju-button.remove').removeClass('no_active');
        }
        if (number_item_group < 1) {
            $my_fonts.find('.font_options').find('.ju-button.variation').addClass('no_active');
            $my_fonts.find('.font_options').find('.ju-button.variation').addClass('no_active');
            $my_fonts.find('.font_options').find('.ju-button.save').addClass('no_active');
        } else {
            $my_fonts.find('.font_options').find('.ju-button.variation').removeClass('no_active');
            $my_fonts.find('.font_options').find('.ju-button.preview').removeClass('no_active');
            $my_fonts.find('.font_options').find('.ju-button.save').removeClass('no_active');
        }
    }

    // bootbox.alert('textStatus + " : " + error');
    function edit_font() {
        $my_fonts.find('.font_options').show();
        var $control_value = $my_fonts.find('.font_options .control_value');

        $my_fonts.find('.ju-button.update_file').unbind('click').on('click', function (e) {
            $content_popup_hide.find('#wptm_upload_file .upload_file-btn').trigger('click', [$(this)]);
        });

        $my_fonts.find('.font_options').find('.wptm_input').unbind('change').on('change', function (e) {
            if ($(this).val() == '') {
                return false;
            }
            var end_value = $(this).val().split('.').pop();

            if ($(this).hasClass('woff')) {
                if (end_value !== 'woff') {
                    $(this).val('').change();
                    alert(wptmText.warning_add_font_file + 'woff');
                }
            } else if ($(this).hasClass('woff2')) {
                if (end_value !== 'woff2') {
                    $(this).val('').change();
                    alert(wptmText.warning_add_font_file + 'woff2');
                }
            } else if ($(this).hasClass('ttf')) {
                if (end_value !== 'ttf') {
                    $(this).val('').change();
                    alert(wptmText.warning_add_font_file + 'ttf');
                }
            } else if ($(this).hasClass('eot')) {
                if (end_value !== 'eot') {
                    $(this).val('').change();
                    alert(wptmText.warning_add_font_file + 'eot');
                }
            } else if ($(this).hasClass('svg')) {
                if (end_value !== 'svg') {
                    $(this).val('').change();
                    alert(wptmText.warning_add_font_file + 'svg');
                }
            } else if ($(this).hasClass('otf')) {
                if (end_value !== 'otf') {
                    $(this).val('').change();
                    alert(wptmText.warning_add_font_file + 'otf');
                }
            }
        });

        active_button_my_font($control_value, $my_fonts);

        //button events
        $my_fonts.find('.font_options').find('.ju-button.remove:not(.no_active)').unbind('click').on('click', function (e) {
            if ($control_value.find('.font-item-group').length > 1) {
                $control_value.find('.font-item-group').slice(-1).remove();
                active_button_my_font($control_value, $my_fonts);
            }
        });

        $my_fonts.find('.font_options').find('.ju-button.variation:not(.no_active)').unbind('click').on('click', function (e) {
            $my_fonts.find('.variation').before($content_popup_hide.find('.font-item-group').clone());
            edit_font();
        });

        $my_fonts.find(".preview_font").show();
        $my_fonts.find('.font_options').find('.ju-button.preview:not(.no_active)').unbind('click').on('click', function (e) {
            preview(false);
        });
        $my_fonts.find('.font_options').find('.ju-button.save:not(.no_active)').unbind('click').on('click', function (e) {
            e.preventDefault();
            var name_font = $control_value.find('#name_font').val();
            var name_fallback_font = $control_value.find('#name_fallback_font').val();
            var font_option = read_font_option(name_font, name_fallback_font);
            if (font_option[1] !== '') {
                if ($(this).data('value') === 'add') {
                    save_local_font('add', font_option);
                } else {
                    save_local_font('edit', font_option, $my_fonts.find('.list_font_local').find('.font_google.active').data('id'));
                }
            } else {
                alert(wptmText.please_add_font_file);
            }
        });
    }

    function preview (select_font_added) {
        var name_font = '', name_fallback_font, font_option;
        if (select_font_added || $my_fonts.find('.font_options').is(":visible") != true && $my_fonts.find('.list_font_local').find('.font_google.active').length > 0) {
            var id = $my_fonts.find('.list_font_local').find('.font_google.active').data('id'), data;
            $.each(list_my_font, function (i, v) {
                if (typeof v !== 'undefined' && v.id == id) {
                    data = v;
                }
            });
            name_font = data.data[0].name_font;
            font_option = data.urc.replace(new RegExp("\\\\", "g"), "");
        } else {
            var $control_value = $my_fonts.find('.font_options .control_value');
            name_font = $control_value.find('#name_font').val();
            name_fallback_font = $control_value.find('#name_fallback_font').val();
            font_option = read_font_option(name_font, name_fallback_font);
            font_option = font_option[1];
        }

        if (name_font !== '') {
            let links = document.getElementById('wptm-local-fonts-css');
            links.innerHTML = font_option;
            links.cssText = font_option;
            $my_fonts.find(".preview_font .controls").css({'font-family': name_font});
        }
    }

    function edit_local_font() {
        $my_fonts.find('.list_font_local').find('.font_google').unbind('click').on('click', function (e) {
            if ($(e.target).hasClass('material-icons')) {
                return false;
            }
            $(this).siblings('.active').removeClass('active');
            $(this).addClass('active');
            $my_fonts.find(".preview_font").show();
            preview(true);
            return true;
        });
        $my_fonts.find('.list_font_local').find('.font_google .edit_font').unbind('click').on('click', function (e) {
            $my_fonts.find('.font_options').find('.add_new_font').remove();
            $my_fonts.find('.font_options').hide();

            var that = $(this).parents('.font_google');
            that.siblings('.active').removeClass('active');
            that.addClass('active');

            var id = that.data('id');
            var data, $add_new_font;
            $.each(list_my_font, function (i, v) {
                if (typeof v !== 'undefined' && v.id == id) {
                    data = v;
                }
            });

            $add_new_font = $content_popup_hide.find('.add_new_font').clone();
            $add_new_font.find('.font-item-group').remove();
            $add_new_font.find('#name_font').val(data.data[0].name_font).text(data.data[0].name_font);
            $add_new_font.find('#name_fallback_font').val(data.data[0].fallback).text(data.data[0].fallback);
            $my_fonts.find('.font_options .save_preview').before($add_new_font);

            $.each(data.data, function (i2, v2) {
                $add_new_font = $content_popup_hide.find('.font-item-group').clone();
                $add_new_font.find('.font_weight').val(data.data[0].font_weight).change();
                $add_new_font.find('.font_style').val(data.data[0].font_style).change();
                if (typeof data.data[0].woff !== 'undefined' && data.data[0].woff !== '') {
                    $add_new_font.find('.wptm_input.woff').val(data.data[0].woff).change();
                }
                if (typeof data.data[0].woff2 !== 'undefined' && data.data[0].woff2 !== '') {
                    $add_new_font.find('.wptm_input.woff2').val(data.data[0].woff2).change();
                }
                if (typeof data.data[0].ttf !== 'undefined' && data.data[0].ttf !== '') {
                    $add_new_font.find('.wptm_input.ttf').val(data.data[0].ttf).change();
                }
                if (typeof data.data[0].eot !== 'undefined' && data.data[0].eot !== '') {
                    $add_new_font.find('.wptm_input.eot').val(data.data[0].eot).change();
                }
                if (typeof data.data[0].svg !== 'undefined' && data.data[0].svg !== '') {
                    $add_new_font.find('.wptm_input.svg').val(data.data[0].svg).change();
                }
                if (typeof data.data[0].otf !== 'undefined' && data.data[0].otf !== '') {
                    $add_new_font.find('.wptm_input.otf').val(data.data[0].otf).change();
                }

                $my_fonts.find('.variation').before($add_new_font);
            });

            $my_fonts.find('.font_options').find('.ju-button.save').data('value', 'edit');
            edit_font();
        });
    }
    edit_local_font();

    function save_local_font(action, font_option, id) {
        $my_fonts.find(".preview_font").hide();
        var jsonVar;
        if (action === 'add') {
            jsonVar = {
                option: {'data': font_option[0], 'urc': font_option[1]},
                data_action: 'add'
            };
        } else if (action === 'delete') {
            jsonVar = {
                option: {'fontid': id},
                data_action: 'delete'
            };
        } else {
            jsonVar = {
                option: {'fontid': id, 'data': font_option[0], 'urc': font_option[1]},
                data_action: 'update'
            };
        }
        var url = wptm_ajaxurl + "task=config.setlocalfont";

        $.ajax({
            url: url,
            dataType: "json",
            type: "POST",
            data: jsonVar,
            success: function (datas) {
                if (datas.response === true) {
                    if (action === 'add') {
                        list_my_font.push({'id': datas.datas.id, 'data': font_option[0], 'urc': font_option[1]});
                        var $new_font_google = $font_google.find('.new_font_google').clone();
                        $my_fonts.find('.list_font_local .preview_font').before($new_font_google);
                        $new_font_google.find('.label_text').text(font_option[0][0].name_font);
                        $new_font_google.attr('data-name', font_option[0][0].name_font).attr('data-id', datas.datas.id).removeClass('new_font_google').show();

                        $my_fonts.find('.font_options').find('.add_new_font').remove();
                        $my_fonts.find('.font_options').hide();
                        delete_font();
                        edit_local_font();
                    } else if (action === 'delete') {
                        $my_fonts.find('.list_font_local').find('.font_google[data-id="' + id + '"]').remove();

                        $.each(list_my_font, function (i, v) {
                            if (typeof v !== 'undefined' && v.id == id) {
                                delete list_my_font[i];
                            }
                        });
                    } else {//edit
                        $my_fonts.find('.font_options').find('.ju-button.save').data('value', 'add');
                        $.each(list_my_font, function (i, v) {
                            if (typeof v !== 'undefined' && v.id == id) {
                                list_my_font[i].data = font_option[0];
                                list_my_font[i].urc = font_option[1];
                                $my_fonts.find('.list_font_local').find('.font_google[data-id="' + id + '"]')
                                    .attr('data-name', font_option[0][0].name_font)
                                    .find('.label_text').text(font_option[0][0].name_font);
                            }
                        });

                        $my_fonts.find('.font_options').find('.add_new_font').remove();
                        $my_fonts.find('.font_options').hide();
                        $my_fonts.find('.list_font_local').find('.font_google.active').removeClass('active');
                    }
                } else {
                    bootbox.alert(datas.response);
                }
            },
            error: function (jqxhr, textStatus, error) {
                bootbox.alert(textStatus + " : " + error);
            }
        });
    }
    
    function read_font_option(name_font, name_fallback_font) {
        var font_item = $my_fonts.find('.font_options .font-item-group');
        var option = [], i2 = 0;
        var font_face_css = '', font_face_css_string = '';
        if (!(typeof name_font !== 'undefined' && name_font !== '')) {
            name_font = 'font create ' + new Date();
        }
        if (!(typeof name_fallback_font !== 'undefined' && name_fallback_font !== '')) {
            name_fallback_font = '';
        }

        $.each(font_item, function (i, v) {
            i2 = 0;
            var option1 = {
                'name_font': name_font,
                'fallback': name_fallback_font,
                'font_weight': $(v).find('.font_weight').val(),
                'font_style': $(v).find('.font_style').val()
            }
            font_face_css = '@font-face {font-family: "' + name_font + '";font-fallback: "' + name_fallback_font
                + '";font_weight: ' + option1.font_weight + ';font-style: ' + option1.font_style + '; src:';
            if (typeof $(v).find('.woff').val() !== 'undefined' && $(v).find('.woff').val() !== '') {
                option1.woff = $(v).find('.woff').val();
                i2++;
                if (font_face_css.slice(-1) !== ':') {
                    font_face_css += ',';
                }
                font_face_css += 'url(' + option1.woff + ") format('woff')";
            }
            if (typeof $(v).find('.woff2').val() !== 'undefined' && $(v).find('.woff2').val() !== '') {
                option1.woff2 = $(v).find('.woff2').val();
                i2++;
                if (font_face_css.slice(-1) !== ':') {
                    font_face_css += ',';
                }
                font_face_css += 'url(' + option1.woff2 + ") format('woff2')";
            }
            if (typeof $(v).find('.ttf').val() !== 'undefined' && $(v).find('.ttf').val() !== '') {
                option1.ttf = $(v).find('.ttf').val();
                i2++;
                if (font_face_css.slice(-1) !== ':') {
                    font_face_css += ',';
                }
                font_face_css += 'url(' + option1.ttf + ") format('TrueType')";
            }
            if (typeof $(v).find('.eot').val() !== 'undefined' && $(v).find('.eot').val() !== '') {
                option1.eot = $(v).find('.eot').val();
                i2++;
                if (font_face_css.slice(-1) !== ':') {
                    font_face_css += ',';
                }
                font_face_css += 'url(' + option1.eot + ") format('eot')";
            }
            if (typeof $(v).find('.svg').val() !== 'undefined' && $(v).find('.svg').val() !== '') {
                option1.svg = $(v).find('.svg').val();
                i2++;
                if (font_face_css.slice(-1) !== ':') {
                    font_face_css += ',';
                }
                font_face_css += 'url(' + option1.svg + ") format('svg')";
            }
            if (typeof $(v).find('.otf').val() !== 'undefined' && $(v).find('.otf').val() !== '') {
                option1.otf = $(v).find('.otf').val();
                i2++;
                if (font_face_css.slice(-1) !== ':') {
                    font_face_css += ',';
                }
                font_face_css += 'url(' + option1.otf + ") format('OpenType')";
            }
            font_face_css += ';}';
            if (i2 > 0) {
                font_face_css_string += font_face_css;
                option.push(option1);
            }
        });
        return [option, font_face_css_string];
    }

    var $font_google = $('.ju-settings-option.fonts_google');
    var list_font = [];
    var list_font_loaded = [];

    function get_list_font() {
        $.getJSON('https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyCfEec9gz7dOnWJ87ZhWx8hnWbtPgJA3vY', function(response) {
            if (typeof response.items !== 'undefined') {
                $font_google.find('.select_font .wptm_select_box li').remove();
                $.each(response.items, function (i, u) {
                    list_font.push({family: u.family, files: u.files});
                    $font_google.find('.select_font .wptm_select_box').append('<li data-value="' + i + '">' + u.family + '</li>');
                });
            }
        });

        $.ajax({
            url: wptm_ajaxurl + "task=config.getlocalfont",
            dataType: "json",
            type: "POST",
            success: function (datas) {
                if (datas.response === true) {
                    $.each(datas.datas, function (i, v) {
                        list_my_font.push({'id': i, 'data': v.data, 'urc': v.urc});
                    });
                }
            },
            error: function (jqxhr, textStatus, error) {
                bootbox.alert(textStatus + " : " + error);
            }
        });
    }
    if (list_font.length < 1) {
        get_list_font();
    }

    function custom_select_box (click_open, select_function) {
        var $select, $that;
        if (click_open) {
            $that = $(this);
            var position = $(this).position();
            $select = $(this).next().css({top: position.top + 40, left: position.left, 'min-width': $that.outerWidth()});
        } else {
            $select = $(this);
            $that = $(this).siblings($(this).data('destination'));
        }

        $that.on('click', function (e) {
            if (click_open) {
                $('#mybootstrap').find('.wptm_select_box').each(function () {
                    $(this).hide();
                    $(this).siblings('.show').removeClass('show');
                });

                if ($(this).hasClass('show')) {
                    $(this).next().hide();
                    $(this).removeClass('show');
                    $(document).unbind('click.wptm_select_box');
                    return;
                }

                $that.addClass('show');
                $select.show();
            }

            $select.find('li').unbind('click').on('click', function (e) {
                if (typeof select_function !== 'undefined') {
                    select_function.call($(this), $select, $that);
                }
                $select.addClass('wptm_hiden');
            });

            $(document).unbind('click.wptm_select_box').bind('click.wptm_select_box', (e) => {
                if (!$(e.target).is($that)) {
                    $select.addClass('wptm_hiden');
                    $that.removeClass('show');
                    $(document).unbind('click.wptm_select_box');
                }
            });
        });
    }
    
    function downloadFontGoogle(that, font) {
        var url = wptm_ajaxurl + "task=config.downloadFont";
        var jsonVar = {
            name: font
        };

        $.ajax({
            url: url,
            dataType: "json",
            type: "POST",
            data: jsonVar,
            success: function (datas) {
                if (datas.response === true) {
                    that.hide();
                    that.siblings('.allow').show();
                    // that.siblings('.tooltip-label').text(datas.datas.text);
                } else {
                    bootbox.alert(datas.response);
                }
            },
            error: function (jqxhr, textStatus, error) {
                bootbox.alert(textStatus + " : " + error);
            }
        });
    }

    function delete_font() {
        $font_google.find('#list_font_google .material-icons').unbind('click').on('click', function (e) {
            var name = $(this).parents('.font_google').data('name');
            var value = $font_google.find('#fonts_google').val();
            if (!$(this).hasClass('download')) {
                value = value.replace('|' + name + '|', '');
                $font_google.find('#fonts_google').val(value).change();
                $(this).parents('.font_google').remove();
            } else {
                if (!$(this).hasClass('allow')) {
                    downloadFontGoogle($(this), name);
                }
            }
            return true;
        })

        $my_fonts.find('.list_font_local').find('.font_google .material-icons.delete_font').unbind('click').on('click', function (e) {
            save_local_font('delete', null, $(this).parents('.font_google').data('id'));
            return true;
        })
    };
    delete_font();

    function wptm_popup (popup, clone) {
        $wptm_popup.contents().remove();
        var that;
        if (!clone) {
            that = $wptm_popup.append(popup.html);
        } else {
            that = $wptm_popup.append(popup.html.clone());
        }
        $wptm_popup.animate({'opacity': '1'}, 10);

        $wptm_popup.show();
        $over_popup.show();

        if (typeof popup.showAction !== 'undefined') {
            popup.showAction.call(that);
        }

        //action colose
        $wptm_popup.find('.colose_popup').unbind('click').on('click', function (e) {
            e.preventDefault();
            if (typeof popup.cancelAction !== 'undefined') {
                $wptm_popup.animate({'opacity': '0'}, 10);
                if (typeof popup.cancelAction !== 'undefined') {
                    popup.cancelAction.call(that);
                }
            }

            setTimeout(function () {
                $wptm_popup.hide();
                $over_popup.hide();
            }, 200);
            return false;
        });
        $over_popup.unbind('click').on('click', function (e) {
            e.preventDefault();
            $wptm_popup.find('.colose_popup').trigger('click');
            return false;
        });
        return false;
    }

    console.log($font_google.find('#select_font'));
    $font_google.find('#select_font').unbind('click').on('click', function (e) {
        console.log(e);
        wptm_popup({
            'html': $content_popup_hide.find('.google_select_font'),
            'showAction': function () {
                // list_font
                var $body_list = this.find('.popup-body');
                render_list_font('', $body_list.find('.list'), $content_popup_hide.find('.font-item'));

                var checkTimeOut = true, getSearch;
                $body_list.find('#select_font').on('keyup', function (e) {
                    if (!checkTimeOut) {
                        clearTimeout(getSearch);
                    }
                    checkTimeOut = false;
                    var value = $(this).val();
                    getSearch = setTimeout(function () {
                        render_list_font(value, $body_list.find('.list'), $content_popup_hide.find('.font-item'));
                        checkTimeOut = true;
                    }, 300);
                    return true;
                });

                $body_list.find('.refresh').unbind('click').on('click', function (e) {
                    render_list_font('', $body_list.find('.list'), $content_popup_hide.find('.font-item'));
                });
                return true;
            }
        }, true);
    });
    function render_list_font(search, parent, content) {
        var list = [], list_font_added = [], content1, count = 0;
        parent.contents().remove();
        var value = $font_google.find('#fonts_google').val();
        value.split('|').map(function (v, i) {
            if (v !== '') {
                list_font_added.push(v);
            }
        });
        if (search === '') {
            parent.prev().find('#select_font').val('');
            $.each(list_font, function (i, v) {
                content1 = content.clone();
                content1.data('value', v.family).find('.fontname span').text(v.family);
                content1.find('> span').css({'font-family': v.family});
                if (list_font_added.includes(v.family)) {
                    content1.find('.ggfonts_add').removeClass('fontAdd').addClass('fontRemove').text(wptmText.ADDED);
                }
                parent.append(content1);
                count++;
            });
        } else {
            $.each(list_font, function (i, v) {
                if (v.family.toLowerCase().search(search.toLowerCase()) !== -1) {
                    content1 = content.clone();
                    content1.data('value', v.family).find('.fontname span').text(v.family);
                    content1.find('> span').css({'font-family': v.family});
                    if (list_font_added.includes(v.family)) {
                        content1.find('.ggfonts_add').removeClass('fontAdd').addClass('fontRemove').text(wptmText.ADDED);
                    }
                    parent.append(content1);
                    count++;
                }
            });
        }

        var current_page = 1;
        var records_per_page = 10;
        var skip_previous = parent.next().find(".skip_previous");
        var skip_next = parent.next().find(".skip_next");
        var btn_next = parent.next().find(".navigate_next");
        var btn_prev = parent.next().find(".arrow_back_ios");
        var page_span = parent.next().find("#page");

        skip_previous.unbind('click').on('click', function (e) {
            current_page = 0;
            changePage(current_page);
        });
        btn_prev.unbind('click').on('click', function (e) {
            if (current_page > 1) {
                current_page--;
                changePage(current_page);
            }
        });
        btn_next.unbind('click').on('click', function (e) {
            if (current_page < numPages()) {
                current_page++;
                changePage(current_page);
            }
        });
        skip_next.unbind('click').on('click', function (e) {
            current_page = numPages();
            changePage(current_page);
        });
        function changePage(page)
        {
            parent.find('.font-item').hide();
            // Validate page
            if (page < 1) page = 1;
            if (page > numPages()) page = numPages();
            var value = '';

            for (var i = (page-1) * records_per_page; i < (page * records_per_page) && i < count; i++) {
                parent.find('.font-item:eq(' + i + ')').show();
                value = parent.find('.font-item:eq(' + i + ')').data('value');
                if (!list_font_loaded.includes(value)) {
                    list.push(value);
                    list_font_loaded.push(value);
                }
            }
            page_span.text(page + "/" + numPages());

            if (page == 1) {
                btn_prev.hide();
            } else {
                btn_prev.show();
            }

            if (page == numPages()) {
                btn_next.hide();
            } else {
                btn_next.show();
            }
            if (list.length > 0) {
                WebFont.load({
                    google: {
                        families: list
                    }
                });
            }
        }
        function numPages()
        {
            return Math.ceil(count / records_per_page);
        }
        changePage(1);

        // ggfonts_add fontadd
        parent.find('.ggfonts_add').unbind('click').on('click', function (e) {
            var value = $(this).parents('.font-item').data('value'), value1 = $font_google.find('#fonts_google').val();
            if ($(this).hasClass('fontAdd')) {
                $(this).removeClass('fontAdd').addClass('fontRemove');
                $font_google.find('#fonts_google').val(value1 + '|' + value + '|').change();
                var $new_font_google = $font_google.find('.new_font_google').clone();
                $font_google.find('#list_font_google').append($new_font_google);
                $new_font_google.find('.label_text').text(value);
                $new_font_google.attr('data-name', value).removeClass('new_font_google').show();
            } else {
                $(this).removeClass('fontRemove').addClass('fontAdd');
                $font_google.find('#list_font_google .font_google[data-name="' + value + '"]').find('.material-icons').trigger('click');
            }
            delete_font();
        });
        return false;
    }

    // $font_google.find('input.wptm_input').on('keyup', function (e) {
    //     if (e.keyCode === 13) {
    //         search_items(list_font, 'enter', [1]);
    //     } else {
    //         search_items(list_font, $(this).val(), [1]);
    //     }
    //     return true;
    // });
    // function search_items(data, key, position_col) {
    //     var text = key !== '' ? key : '';
    //     // $font_google.find('input.wptm_input').removeClass('show');
    //     if (text !== '') {
    //         $.each(data, function (i, v) {
    //             if (v.family.toLowerCase().search(text.toLowerCase()) !== -1) {
    //                 $font_google.find('.select_font .wptm_select_box').find('li[data-value="' + i + '"]').removeClass('wptm_hiden');
    //             } else {
    //                 $font_google.find('.select_font .wptm_select_box').find('li[data-value="' + i + '"]').addClass('wptm_hiden');
    //             }
    //         });
    //         $font_google.find('.select_font .wptm_select_box').removeClass('wptm_hiden');
    //         // $font_google.find('input.wptm_input').addClass('show');
    //     } else {
    //         $font_google.find('.select_font .wptm_select_box').addClass('wptm_hiden');
    //     }
    // }

    //create new .pane-color-tile
    $new_format_style.find('.create_format_style').on('click', function () {
        var number_format = $list_format_style.find('.pane-color-tile').length;
        var $html = $('<div class="pane-color-tile td_' + number_format + '">' +
            '<div class="pane-color-tile-header pane-color-tile-band" style="background-color:#ffffff" data-value="#ffffff"></div>' +
            '<div class="pane-color-tile-1 pane-color-tile-band" style="background-color:#ffffff" data-value="#ffffff"></div>' +
            '<div class="pane-color-tile-2 pane-color-tile-band" style="background-color:#ffffff" data-value="#ffffff"></div>' +
            '<div class="pane-color-tile-footer pane-color-tile-band" style="background-color:#ffffff" data-value="#ffffff"></div>' +
            '</div>');
        $html.appendTo($list_format_style);

        /*active new .pane-color-tile*/
        set_active_format_style();
        $list_format_style.find('.td_' + number_format).trigger('click');
    });

    //remove .pane-color-tile
    $new_format_style.find('.remove_format_style').on('click', function () {
        if ($new_format_style.find('.hide_set_format_style').hasClass('show')) {
            $new_format_style.find('.hide_set_format_style').trigger('click');
        }
        $controlFormatStyle.find('.pane-color-tile.active').remove();
        save_format_style();
    });

    //click active .pane-color-tile
    var set_active_format_style = function () {
        $controlFormatStyle.find('.pane-color-tile').on('click', function () {
            $(this).siblings('.active').removeClass('active');
            if (!$new_format_style.find('.hide_set_format_style').hasClass('show')) {
                $new_format_style.find('.hide_set_format_style').trigger('click');
            }
            $(this).addClass('active');
            reset_color_picket($(this));
        });
    };

    set_active_format_style();

    //set color picket when select format style
    var reset_color_picket = function (e) {
        active_format_color = '';
        e.find('.pane-color-tile-band').each(function (i) {
            active_format_color += i === 0 ? '' : '|';
            active_format_color += $(this).data('value');
            $set_color.find('.wp-picker-container:eq(' + i + ') input.wp-color-field').val($(this).data('value')).change();
        });
    };

    var get_color_picket = function (e, v, reset) {
        if (reset === '0' || e.hasClass('pane-set-color-header')) {
            $list_format_style.find('.active').find('.pane-color-tile-header').css('background-color', v).data('value', v);
        }
        if (reset === '1' || e.hasClass('pane-set-color-1')) {
            $list_format_style.find('.active').find('.pane-color-tile-1').css('background-color', v).data('value', v);
        }
        if (reset === '2' || e.hasClass('pane-set-color-2')) {
            $list_format_style.find('.active').find('.pane-color-tile-2').css('background-color', v).data('value', v);
        }
        if (reset === '3' || e.hasClass('pane-set-color-footer')) {
            $list_format_style.find('.active').find('.pane-color-tile-footer').css('background-color', v).data('value', v);
        }
    };

    //select color
    $('.wp-color-field').wpColorPicker({
        width: 180,
        change: function(e, i){
            get_color_picket($(this), i.color.toString(), '');
        },
        clear: function (e) {
            get_color_picket($(this).siblings('label').find('input'), '#ffffff', '');
        }
    });

    $('.wp-picker-container').find('button.button span.wp-color-result-text').text('');/*fix wptm-color-picker text in wp version 5.5*/

    /*save the change format style*/
    var save_format_style = function () {
        var dataFormatStyle = '';
        $list_format_style.find('.pane-color-tile').find('.pane-color-tile-band').each(function (i) {
            dataFormatStyle += (i !== 0 ? '|' : '') + $(this).data('value');
        }),
            $('#alternate_color').val(dataFormatStyle);

        active_format_color = '';
    };
    $save_format_style.find('input:eq(0)').on('click', function () {
        $new_format_style.find('.hide_set_format_style').trigger('click');
        var html = '<span style="color: #ff8726; float: right;">' + wptmText.save_alternate + '</span>';
        $controlFormatStyle.find('.label_text').append(html);
        setTimeout(function () {
            $controlFormatStyle.find('.label_text span').remove();
        }, 2000);
        save_format_style();
    });

    /*remove the change format style*/
    var cancel_format_style = function () {
        active_format_color.split('|').map(function (color, number) {
            get_color_picket($controlFormatStyle, color, number.toString());
        }),
            reset_color_picket($list_format_style.find('.active')),
            $controlFormatStyle.find('.pane-color-tile.active').removeClass('active');
    };
    $save_format_style.find('input:eq(1)').on('click', function () {
        if (active_format_color !== '') {
            $new_format_style.find('.hide_set_format_style').trigger('click');
            cancel_format_style();
        }
    });

    $new_format_style.find('.hide_set_format_style').toggle(
        function () {
            $(this).val('Hide').removeClass('show');
            $set_color.hide();
            $save_format_style.hide();
        },
        function() {
            $(this).val('Show').addClass('show');
            $set_color.css('display', 'grid');
            $save_format_style.show();
        }
    );
    $('.hide_set_format_style').trigger('click');

    $('.ju-settings-option.decimal_sym').find('.ju-select').on('change', function () {
        var decimal_sym = $(this).val();

        if ($('.ju-settings-option.thousand_sym').find('.ju-select').val() === decimal_sym) {
            if (decimal_sym === '.') {
                $('.ju-settings-option.thousand_sym').find('.ju-select').val(',').change();
            } else {
                $('.ju-settings-option.thousand_sym').find('.ju-select').val('.').change();
            }
        }
    });

    $('.ju-settings-option.thousand_sym').find('.ju-select').on('change', function () {
        var thousand_sym = $(this).val();

        if ($('.ju-settings-option.decimal_sym').find('.ju-select').val() === thousand_sym) {
            if (thousand_sym === ',') {
                $('.ju-settings-option.decimal_sym').find('.ju-select').val('.').change();
            } else {
                $('.ju-settings-option.decimal_sym').find('.ju-select').val(',').change();
            }
        }
    });

    //separate the main settings
    var $wptm_settings = $('#wptm_settings');
    function show_hiden_option(that) {
        var container_show = that.attr('href');
        $('.ju-right-panel').find('.wptm_show_hiden_option').each(function () {
            var list_option = $(this).data('option').split('|');
            var i;
            if ($(this).is(container_show)) {
                for (i in list_option) {
                    $wptm_settings.find('.' + list_option[i]).show();
                }
            } else {
                for (i in list_option) {
                    $wptm_settings.find('.' + list_option[i]).hide();
                }
            }
        });
    }
    show_hiden_option($wptm_settings.find('.ju-top-tabs').find('a.active'));

    $wptm_settings.find('.ju-top-tabs').find('a.link-tab').click(function (e) {
        show_hiden_option($(this));
    });
    $('.ju-menu-tabs').find('a.link-tab').click(function (e) {
        if ($(this).attr('href') === '#wptm_settings' && !$(this).hasClass('active')) {
            show_hiden_option($($(this).attr('href')).find('.ju-top-tabs a.active'));
        }
    });

    /*resize window*/
    var $main = $('.ju-main-wrapper');
    var $ju_left_panel = $('.ju-main-wrapper > .ju-left-panel');
    function display_left_menu () {
        if (parseInt($( window ).width()) < 960 && $main.find('.wptm_show_cat').length < 1) {
            $wptm_show_cat = $('<div class="wptm_show_cat"><i class="dashicons dashicons-leftright"></i></div>').prependTo($main);
            $ju_left_panel.width(0).show();
            $wptm_show_cat.toggle(
                function () {
                    $(this).val('Hide').addClass('show').animate({left: '280px'}, "slow");
                    $ju_left_panel.show().animate({width: '300px', opacity: '1'}, "slow");
                },
                function () {
                    $(this).val('Show').removeClass('show').animate({left: '-25px'}, "slow");
                    $ju_left_panel.animate({
                        width: '0px',
                        opacity: '0'
                    }, "slow").delay(10).animate({'opacity': '1'}, 10);
                }
            );
        } else if (parseInt($( window ).width()) > 960 && $main.find('.wptm_show_cat').length > 0) {
            $main.find('.wptm_show_cat').remove();
            $ju_left_panel.width(300).show();
        }
    }
    display_left_menu();
    $( window ).resize(function() {
        display_left_menu();
    });
});
