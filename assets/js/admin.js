(function ($) {

    $(document).ready(function () {

        $('#gravityform-id').change(function () {
            getConditionalLogic($(this).val());
        });

        $('#gravityform_conditional_logic_type').on('change', function(e) {
            getConditionalLogicFields($('#gravityform-id').val(), $(this).val());
        });
    });

    let $xhr = null;

    function getConditionalLogicFields($form_id, type) {
        if (type == 'none') {
            $('#gforms_conditional_logic_section').html('');
            return;
        }


        if ($xhr) {
            $xhr.abort();
        }

        const data = {
            action: 'wc_gravityforms_get_conditional_logic_fields',
            wc_gravityforms_security: wc_gf_addons.nonce,
            form_id: $form_id,
            product_id: wc_gf_addons.product_id,
            gravityform_conditional_logic_type: type,
        };

        $('#gforms_conditional_logic_field_group').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });


        $xhr = $.post(ajaxurl, data, function (response) {

            $('#gforms_conditional_logic_field_group').unblock();

            $('#gforms_conditional_logic_section').show();
            $('#gforms_conditional_logic_section').html(response.data.markup);
        });

    }
})(jQuery);

