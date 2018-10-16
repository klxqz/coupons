$(document).ready(function () {
    $('.feature-block b i').click(function () {
        $(this).toggleClass('darr').toggleClass('rarr');
        $(this).closest('.feature-block').find('.values').slideToggle('low');
    });

    $('select[name="coupon[type]"]').change(function () {
        var select = $(this);
        var type = select.val();
        var wr = $('#value-input-wrapper').show();
        $('#value-input-wrapper-gft').hide();
        if (type == '%') {
            wr.children('span').text('%');
        } else {
            var t = $.trim(select.children('[value="' + type + '"]').text());
            wr.children('span').text(t.substr(0, t.length - 3));
        }
    }).change()
    // Datepicker
    var datetime_input = $('input[name="coupon[expire_datetime]"]');
    datetime_input.datepicker({
        'dateFormat': 'yy-mm-dd'
    });
    datetime_input.next('a').click(function () {
        $('input[name="coupon[expire_datetime]"]').datepicker('show');
    });

    $("#coupon-form").submit(function () {
        var form = $(this);
        var loading = $('<i class="icon16 loading"></i>')
        form.find('input[type=submit]').after(loading);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function (data, textStatus) {
                loading.remove();
                console.log(data);
                if (data.status == 'ok') {
                    if ($('#coupons-table tbody tr[data-id=' + data.data.id + ']').length) {
                        $('#coupons-table tbody tr[data-id=' + data.data.id + ']').replaceWith(data.data.html);
                    } else {
                        $(data.data.html).appendTo('#coupons-table tbody');
                    }
                    form.closest('.dialog').trigger('close');
                } else {
                    alert(data.errors.join(', '));
                }
            }, error: function (jqXHR, textStatus, errorThrown) {
                loading.remove();
                alert(jqXHR.responseText);
            }
        });
        return false;
    });
    $('#coupon-form .cancel').click(function () {
        $(this).closest('.dialog').trigger('close');
        return false;
    });


    $('input[name=hash]').change(function () {
        var hash = $(this).val();
        $('.js-hash-values').hide();
        $('.js-hash-' + hash).show();
    });
    $('input[name=hash]:first').attr('checked', 'checked').change();

    var products_search = $('#products-search');
    products_search.autocomplete("destroy");
    products_search.autocomplete({
        source: '?action=autocomplete',
        minLength: 3,
        delay: 300,
        select: function (event, ui) {
            var button = $('.add-coupons-products-button[data-type="product"]');
            button.data('id', ui.item.id);
            button.data('name', ui.item.value);
            products_search.val(ui.item.value);
            return false;
        }
    });

    $('.add-coupons-products-button').click(function () {
        var type_names = {"product": "Товар", "set": "Список", "category": "Категория", "feature": "Характеристика"};
        var icons = {"product": "folders", "set": "ss set", "category": "folder", "feature": "ss features-bw"};
        var urls = {"product": "?action=products#/product/", "set": "?action=products#/products/set_id=", "category": "?action=products#/products/category_id=", "feature": "?action=settings#/features/"};
        var type = $(this).data('type');
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!id) {
            alert('Укажите товары участвующие в акции');
            return false;
        }

        if ($('#coupons-products-table tr[data-type="' + type + '"][data-value="' + id + '"]').length) {
            alert(type_names[type] + ' "' + name + '" уже присутствует среди товаров акции');
        } else {
            var data = {
                type: type,
                type_name: type_names[type],
                icon: icons[type],
                url: (type != 'feature' ? urls[type] + id : urls[type]),
                name: name,
                value: id
            };
            $('#coupons-product-tmpl').tmpl(data).appendTo('#coupons-products-table tbody');
        }
        if ($(this).data('type') == 'product') {
            $('#products-search').val('');
            $(this).data('id', '');
            $(this).data('name', '');
        }
        return false;
    });

    $(document).on('click', '.delete-coupons-products-button', function () {
        var tr = $(this).closest('tr');
        var id = tr.find('input[name="coupons_products[id][]"]').val();
        if (id) {
            $.ajax({
                type: 'POST',
                url: '?plugin=coupons&module=settings&action=deleteCouponsProducts',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function (data, textStatus, jqXHR) {
                    if (data.status == 'ok') {
                        tr.remove();
                    } else {
                        alert(data.errors.join(', '));
                    }
                },
                error: function (jqXHR, errorText) {
                    $('#coupons-form-status').html('');
                    alert(jqXHR.responseText);
                }
            });
        } else {
            tr.remove();
        }


        return false;
    });
});