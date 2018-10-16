$(function () {
    var p = $('<div></div>').append($('#coupons-dialog'));
    p.appendTo('body');
    function dialog(id) {
        var url = '?plugin=coupons&module=settings&action=dialog';
        if (id !== undefined) {
            url = '?plugin=coupons&module=settings&action=dialog&id=' + id;
        }
        if ($('#coupons-dialog .dialog-content-indent').length) {
            $('#coupons-dialog .dialog-content-indent').html('<i class="icon16 loading"></i>');
        } else {
            $('#coupons-dialog').html('<i class="icon16 loading"></i>');
        }
        var dialog = $('#coupons-dialog').waDialog({
            disableButtonsOnSubmit: false,
            buttons: $('<input type="submit" class="button green" value="Закрыть">').click(function () {
                dialog.trigger('close');
            }),
            onSubmit: function (d) {
                return false;
            }
        });
        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'html',
            success: function (html) {
                if ($(html).find('.dialog-window').length) {
                    $('#coupons-dialog').html(html);
                } else {
                    $('#coupons-dialog .dialog-content-indent').html(html);
                }
            }
        });
    }

    $('#add-but').click(function () {
        dialog();
        return false;
    });
    $(document).on('click', '.edit-but', function () {
        dialog($(this).closest('tr').data('id'));
        return false;
    });
    $(document).on('click', '.delete-but', function () {
        var self = $(this);
        $.ajax({
            url: '?plugin=coupons&module=settings&action=deleteCoupon',
            type: 'POST',
            dataType: 'json',
            data: {
                id: $(this).closest('tr').data('id')
            },
            success: function (data, textStatus) {
                if (data.status == 'ok') {
                    self.closest('tr').remove();
                } else {
                    alert(data.errors.join(' '));
                }
            },
            error: function (jqXHR, errorText) {
                alert(jqXHR.responseText);
            }
        });
        return false;
    });

    $('.ibutton').iButton({
        labelOn: "Вкл", labelOff: "Выкл", className: 'mini'
    })

    $('#ibutton-status').iButton({
        labelOn: "Вкл", labelOff: "Выкл"
    }).change(function () {
        var self = $(this);
        var enabled = self.is(':checked');
        if (enabled) {
            self.closest('.field-group').siblings().show(200);
        } else {
            self.closest('.field-group').siblings().hide(200);
        }
        var f = $("#plugins-settings-form");
        $.post(f.attr('action'), f.serialize());
    });
});
