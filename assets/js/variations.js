(function ($, fieldSelector, variationAttribute = 'variation_id') {

    const $element = $(fieldSelector);
    let $form = undefined;
    $(document).on('wc_variation_form', function (e) {
        if ($form === undefined) {
            $form = $(e.target);
            $form.on('found_variation', function (e, variation) {
                $element.val(variation.variation_id).trigger('keyup');
                $element.trigger('change');
            });
        }
    });
})(jQuery, wc_gforms_cl.fieldSelector, wc_gforms_cl.variationAttribute);