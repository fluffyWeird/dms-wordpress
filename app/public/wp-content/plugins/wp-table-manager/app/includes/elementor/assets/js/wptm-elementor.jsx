class WidgetTableClass extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        return {
            selectors: {
                firstSelector: '.wptm-elementor-table',
            },
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings('selectors');
        return {
            $firstSelector: this.$element.find(selectors.firstSelector),
        };
    }

    bindEvents() {
        var table = jQuery(this.elements.$firstSelector);
        jQuery.each(table.find('img'), (i, v) => {
            jQuery(v).css({opacity: 0});
            var id_table = jQuery(v).data('wptmtable');
            if (typeof wptm_elementor_var.wptm_ajaxurl !== 'undefined' && parseInt(id_table) > 0) {
                jQuery.ajax({
                    url: wptm_elementor_var.wptm_ajaxurl + 'task=table.loadContent&id=' + id_table,
                    type: "GET",
                    beforeSend: function () {
                    },
                    success: function (datas) {
                        jQuery(v).hide();
                        jQuery(v).parent().removeClass('loadding');
                        if (datas.success && datas.data.content !== '') {
                            jQuery(v).before(datas.data.content);
                            window.wptm_render_tables.call(table, id_table);
                        }
                    },
                    error: function (jqxhr, textStatus, error) {
                    }
                });
            }
        });

        table.click((e) => {
            window.parent.wptm_table_widget_display_control.call(this.$element, e);
        });
    }
}

jQuery(window).on('elementor/frontend/init', () => {
    const addWptmTable = ($element) => {
        elementorFrontend.elementsHandler.addHandler(WidgetTableClass, {
            $element,
        });
    };
    elementorFrontend.hooks.addAction('frontend/element_ready/wptm_table.default', addWptmTable);
});