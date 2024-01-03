;(function ( $, window, document, undefined ) {
    var pluginName = "wpjuscroll",
        defaults = {
            content: {},
            option : {}
        };

    function Plugin( element, option ) {
        this.element = element;

        // jQuery has an extend method that merges the
        this.option = $.extend({}, defaults, option);

        this._defaults = defaults;
        this._name = pluginName;

        this.enable();
    }

    Plugin.prototype = {
        enable: function () {
            $(this.element).siblings('.wpJuScroll').remove();

            var element = this.element;
            var option = this.option;

            var mousewheelevt = (/Firefox/i.test(navigator.userAgent))? "DOMMouseScroll" : "mousewheel" //FF doesn't recognize mousewheel as of FF3.x

            if ($(this.element).height() < $(this.option.content).height()) {
                createScrollTop(this);

                scrollTop(this);

                renderScrollTop(this);

                if (document.getElementById("tableContainer").attachEvent) //if IE (and Opera depending on user setting)
                    document.getElementById("tableContainer").attachEvent("on"+mousewheelevt, function (e) {
                        var evt = window.event || e //equalize event object
                        var delta = evt.detail ? evt.detail * (-120) : evt.wheelDelta;
                        $(document).on('mousewheel DOMMouseScroll', function () {
                            e.preventDefault();
                        });
                        renderScrollTop({element: element, option: option, wheelDelta: delta / 2});
                    })
                else //WC3 browsers
                    document.getElementById("tableContainer").addEventListener(mousewheelevt, function (e) {
                        var evt = window.event || e //equalize event object
                        var delta = evt.detail ? evt.detail * (-120) : evt.wheelDelta;
                        $(document).on('mousewheel DOMMouseScroll', function () {
                            e.preventDefault();
                        });
                        renderScrollTop({element: element, option: option, wheelDelta: delta / 2});
                    }, true)
            } else {
                if (document.getElementById("tableContainer").attachEvent)
                    document.getElementById("tableContainer").attachEvent("off"+mousewheelevt, function (e) {
                        e.preventDefault();
                    })
                else //WC3 browsers
                    document.getElementById("tableContainer").removeEventListener(mousewheelevt, function (e) {
                        e.preventDefault();
                    }, true)
                $(this.option.content).css('top', 0 + 'px');
                $(this.option.content).siblings('.wpju_scroll_top').css('top', 0 + 'px');
                $(this.element).css('height', $(this.option.content).height() + 'px');
            }

            if ($(this.element).width() < $(this.option.content).width()) {
                createScrollLeft(this);

                scrollLeft(this);

                renderScrollLeft(this);
            } else {
                $(this.option.content).css('left', 0 + 'px');
                $(this.option.content).siblings('.wpju_scroll_left').css('left', 0 + 'px');
            }
        }
    };

    // check condition position when scroll
    function checkPosition(topLeft, contentHeight, height, valChange) {
        if (topLeft <= 0 && topLeft + contentHeight >= height) {
            return true;
        }
        return false;
    }

    // action for scrollbarTop
    function scrollTop(e) {
        var scrollbarTop = $(e.element).siblings('#wpJuScrollTop');
        var xxxxx = 0;
        var top = 0;
        scrollbarTop.find('.thumb').mousedown(function (event) {
            scrollbarTop.on('mousemove', function (event ) {
                if (xxxxx === 0) {
                    xxxxx = event.pageY;
                }
                top = event.pageY - xxxxx;
                var topScroll = scrollbarTop.find('.thumb').position().top + top;
                var topChange = -1 * top * $(e.option.content).height() / scrollbarTop.height();
                var offsetTop = e.option.content[0].offsetTop + topChange;
                if (checkPosition(offsetTop, $(e.option.content).height(), $(e.element).height(), topChange)) {
                    scrollbarTop.find('.thumb').css('top', topScroll + 'px');
                    $(e.option.content).css('top', offsetTop + 'px');
                    $(e.option.content).siblings('.wpju_scroll_top').css('top', offsetTop + 'px');
                } else if (offsetTop <= 0 && offsetTop + $(e.option.content).height() < $(e.element).height()) {
                    offsetTop = $(e.element).height() - $(e.option.content).height();
                    topScroll = -1 * offsetTop * scrollbarTop.height() / $(e.option.content).height();
                    scrollbarTop.find('.thumb').css('top', topScroll + 'px');
                    $(e.option.content).css('top', offsetTop + 'px');
                    $(e.option.content).siblings('.wpju_scroll_top').css('top', offsetTop + 'px');
                } else if (offsetTop > 0) {
                    scrollbarTop.find('.thumb').css('top', 0 + 'px');
                    $(e.option.content).css('top', 0 + 'px');
                    $(e.option.content).siblings('.wpju_scroll_top').css('top', 0 + 'px');
                }
                xxxxx = event.pageY;
            });
        });

        $(document).mouseup(function (event ) {
            xxxxx = 0;
            scrollbarTop.off("mousemove");
        });
    };

    //position e.option.content --> scrollbarTop
    function renderScrollTop(e) {
        var scrollbarTop = $(e.element).siblings('#wpJuScrollTop');
        var marginTop = e.element.offsetTop + ($(e.element).height() - e.option.option.height) / 2;
        var top, wheelDelta;

        var height = $(e.element).height() * e.option.option.height / $(e.option.content).height();

        if (typeof e.wheelDelta !== 'undefined') {
            wheelDelta = e.wheelDelta;
            top = e.option.content[0].offsetTop + e.wheelDelta;
        } else  {
            wheelDelta = 0;
            top = e.option.content[0].offsetTop;
        }

        if (checkPosition(top, $(e.option.content).height(), $(e.element).height(), wheelDelta)) {
            $(e.option.content).css('top', top + 'px');
            $(e.option.content).siblings('.wpju_scroll_top').css('top', top + 'px');
            e.option.content[0].offsetTop = top;
        } else if (top <= 0 && top + $(e.option.content).height() < $(e.element).height()) {
            top = $(e.element).height() - $(e.option.content).height();
            $(e.option.content).css('top', top + 'px');
            $(e.option.content).siblings('.wpju_scroll_top').css('top', top + 'px');
            e.option.content[0].offsetTop = top;
        } else if (top > 0) {
            $(e.option.content).css('top', 0 + 'px');
            $(e.option.content).siblings('.wpju_scroll_top').css('top', 0 + 'px');
            e.option.content[0].offsetTop = 0;
        }

        var topScrollbar = (-1 * e.option.content[0].offsetTop * e.option.option.height) / ($(e.option.content).height());

        scrollbarTop.show();
        scrollbarTop.height(e.option.option.height);
        scrollbarTop.css('top', marginTop + 'px');

        scrollbarTop.find('.thumb').css('height', height + 'px');
        scrollbarTop.find('.thumb').css('top', topScrollbar + 'px');

    };

    // action for scrollbarLeft
    function scrollLeft(e) {
        var scrollbarLeft = $(e.element).siblings('#wpJuScrollLeft');
        var left = 0;
        var xxxxx = 0;
        scrollbarLeft.find('.thumb').mousedown(function (event) {
            scrollbarLeft.on('mousemove', function (event ) {
                if (xxxxx === 0) {
                    xxxxx = event.pageX;
                }
                left = event.pageX - xxxxx;
                var leftScroll = scrollbarLeft.find('.thumb').position().left + left;
                var leftChange = -1 * left * $(e.option.content).width() / scrollbarLeft.width();
                var offsetLeft = e.option.content[0].offsetLeft + leftChange;
                if (checkPosition(offsetLeft, $(e.option.content).width(), $(e.element).width(), leftChange)) {
                    scrollbarLeft.find('.thumb').css('left', leftScroll + 'px');
                    $(e.option.content).css('left', offsetLeft + 'px');
                    $(e.option.content).siblings('.wpju_scroll_left').css('left', offsetLeft + 'px');
                } else if (offsetLeft <= 0 && offsetLeft + $(e.option.content).width() < $(e.element).width()) {
                    offsetLeft = $(e.element).width() - $(e.option.content).width();
                    leftScroll = -1 * offsetLeft * scrollbarLeft.width() / $(e.option.content).width();
                    scrollbarLeft.find('.thumb').css('left', leftScroll + 'px');
                    $(e.option.content).css('left', offsetLeft + 'px');
                    $(e.option.content).siblings('.wpju_scroll_left').css('left', offsetLeft + 'px');
                } else if (offsetLeft > 0) {
                    scrollbarLeft.find('.thumb').css('left', 0 + 'px');
                    $(e.option.content).css('left', 0 + 'px');
                    $(e.option.content).siblings('.wpju_scroll_left').css('left', 0 + 'px');
                }
                xxxxx = event.pageX;
            });
        });
        $(document).mouseup(function (event ) {
            xxxxx = 0;
            scrollbarLeft.off("mousemove");
        });
    };

    //position e.option.content --> scrollbarLeft
    function renderScrollLeft(e) {
        var scrollbarLeft = $(e.element).siblings('#wpJuScrollLeft');
        var marginLeft = e.element.offsetLeft + ($(e.element).width() - e.option.option.width) / 2;
        var left = (-1 * e.option.content[0].offsetLeft * e.option.option.width) / ($(e.option.content).width());
        var width = $(e.element).width() * e.option.option.width / $(e.option.content).width();

        scrollbarLeft.show();
        scrollbarLeft.width(e.option.option.width);
        scrollbarLeft.css('left', marginLeft + 'px');
        scrollbarLeft.find('.thumb').css('width', width + 'px');
        scrollbarLeft.find('.thumb').css('left', left + 'px');
        $(e.option.content).siblings('.wpju_scroll_left').css('left', e.element.offsetLeft + 'px');
    };

    function createScrollTop(e) {
        var scrollbarTop = $('<div id="wpJuScrollTop" class="wpJuScroll"><div class="thumb"></div></div>');
        scrollbarTop.insertAfter($(e.element));
    };

    function createScrollLeft(e) {
        var scrollbarLeft = $('<div id="wpJuScrollLeft" class="wpJuScroll"><div class="thumb"></div></div>');
        scrollbarLeft.insertAfter($(e.element));
    };

    $.fn[pluginName] = function ( method ) {

        return this.each(function () {
            $.data(this, "plugin_" + pluginName,
                new Plugin( this, method ));
        });
    };

})( jQuery, window, document );