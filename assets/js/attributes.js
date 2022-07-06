(function ($, fieldSelectors) {

    let $form = undefined;

    $(document).on('wc_variation_form', function (e, form) {
        if ($form === undefined) {
            $form = $(e.target);
            $form.on('check_variations', function (event, chosenAttributes) {
                const attributes = form.getChosenAttributes();
                const currentAttributes = attributes.data;

                for(let config of fieldSelectors) {
                    const current = currentAttributes[config[0]];
                    const $element = $(config[1]);
                    $element.val(current).trigger('keyup');
                    $element.trigger('change');
                }

            });
        }
    });
})(jQuery, wc_gforms_cl.fieldSelectors);