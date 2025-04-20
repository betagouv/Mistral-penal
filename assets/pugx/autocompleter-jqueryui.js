(function ($) {
    'use strict';
    $.fn.autocompleter = function (options) {
        var settings = {
            url_list: '',
            url_get:  '',
            url_init: null,
            min_length: 2,
            on_select_callback: null,
            format_callback: null
        };
        return this.each(function () {
            if (options) {
                $.extend(settings, options);
            }
            var $this = $(this), $fakeInput = $this.clone();
            $fakeInput.attr('id', 'fake_' + $fakeInput.attr('id'));
            $fakeInput.attr('name', 'fake_' + $fakeInput.attr('name'));
            $this.hide().after($fakeInput);
            var timeout = null;
            $fakeInput.autocomplete({
                //source: settings.url_list,
                source: function (request, response) {
                  if (timeout !== null)
                    clearTimeout(timeout);
                  timeout = setTimeout(() => {
                    $.ajax({
                      url: settings.url_list,
                      data: request,
                      dataType: "json",
                      success: function (data) { response(data) },
                      error: function () { response([]); }
                    });
                  }, 2000);
                },
                select: function (event, ui) {
                    event.preventDefault();
                    $this.val(ui.item.value);
                    $(this).val(ui.item.label);
                    if (settings.on_select_callback) {
                        settings.on_select_callback($this, event, ui, settings);
                    }
                },
                minLength: settings.min_length
            });
            /**
             * Initialisation
             *
             */
            if ($this.val() !== '') {
              let url = settings.url_init ? settings.url_init : settings.url_get;
              url = ((url.substring(-1) === '/') ? url : url + '/') + $this.attr('value');
              $.ajax({
                  url: url,
                  success: function (name) {
                      if (settings.format_callback) {
                        $fakeInput.val(settings.format_callback(name));
                      }
                      else {
                        $fakeInput.val(name);
                      }
                  }
              });
            }
        });
    };
})(jQuery);
