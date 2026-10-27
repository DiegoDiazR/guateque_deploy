$(document).ready(function() {

    applyOnlyNumbers();

    if ($('#btn_generar_password')) {
        var generatePassword = (
                length = 8,
                wishlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~!@-#$"
            ) => Array(length)
            .fill('')
            .map(() => wishlist[Math.floor(crypto.getRandomValues(new Uint32Array(1))[0] / (0xffffffff + 1) * wishlist.length)])
            .join('');

        $('#btn_generar_password').off('click').on('click', function() {
            var pass = generatePassword();
            $('#password').val(pass);
            $('#password_confirmation').val(pass);
            $('#div_random_text').text(pass);
        });
    }

    // (El menú ahora es horizontal y lo maneja paipa.js: se retiró el armado del menú lateral que usaba .padre/.opcion.)

    if ($('#search_control').length > 0) {
        $('#search_control').off('keyup').on('keyup', function() {
            var action = $('#link_search').attr('url');
            $('#form_search').attr('action', action + $(this).val());
        });
    }

});

function applyOnlyNumbers() {
    $('.onlyNumbers').bind("cut copy paste", function(evt) {
        evt.preventDefault();
    });

    $('.onlyNumbers').off('keypress').on('keypress', function(evt) {
        if (evt.which !== 8 && evt.which !== 0 && (evt.which < 48 || evt.which > 57)) {
            evt.preventDefault();
            return false;
        }
    });

    $('.onlyNumbers').off('keyup').on('keyup', function(evt) {
        if ($.trim($(this).val()).length > 0) {
            $(this).trigger('change');
        }
    });

    $('.onlyNumbers').off('change').on('change', function(evt) {
        if ($(this).val().length > 0) {
            $(this).val($(this).val().replace(/\D/g, ''));
        }
    });
}
