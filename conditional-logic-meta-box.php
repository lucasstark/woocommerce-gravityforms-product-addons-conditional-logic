<div class="wc-product-data-metabox-group-field">
    <div class="wc-product-data-metabox-group-field-title">
        <a href="javascript:;"><?php _e( 'Conditional Logic', 'wc_gf_addons' ); ?></a>
    </div>


    <div id="gforms_conditional_logic_field_group" class="wc-product-data-metabox-group-field-content"
         style="display:none;">

        <div class="gforms-panel options_group" <?php echo empty( $gravity_form_data['id'] ) ? "style='display:none;'" : ''; ?>>
            <div class="wc-product-data-metabox-option-group-label">
				<?php _e( 'Conditional Logic Options', 'wc_gf_addons' ); ?>
                <p style="font-weight: normal;">
					<?php _e( 'Options for linking form field values to variation or individual attribute selection', 'wc_gf_addons' ); ?>
                </p>

            </div>

			<?php
			woocommerce_wp_select( array(
				'id'          => 'gravityform_conditional_logic_type',
				'label'       => __( 'Conditional logic link type', 'wc_gf_addons' ),
				'value'       => isset( $gravity_form_data['gravityform_conditional_logic_type'] ) ? $gravity_form_data['gravityform_conditional_logic_type'] : 'none',
				'options'     => array(
					'none'       => __( 'None', 'wc_gf_addons' ),
					'variation'  => __( 'Variation - Link a field to the selected variation\'s ID', 'wc_gf_addons' ),
					'attributes' => __( 'Attributes - Link fields to selected attribute values' )
				),
				'description' => false
			) );
			?>

            <div id="gforms_conditional_logic_section">
				<?php if ( isset( $gravity_form_data['gravityform_conditional_logic_type'] ) ) : ?>
					<?php if ( $gravity_form_data['gravityform_conditional_logic_type'] == 'attributes' ) :
						echo WC_GFPA_CL::get_attribute_fields_markup( $product->get_id(), $gravity_form_data['id'], $gravity_form_data['gravityform_conditional_logic_fields'] ?? [] );
                    elseif ( $gravity_form_data['gravityform_conditional_logic_type'] == 'variation' ) :
	                    echo WC_GFPA_CL::get_variation_field_markup( $product->get_id(), $gravity_form_data['id'], $gravity_form_data['gravityform_conditional_logic_field'] ?? '' );
					endif;
					?>
				<?php endif; ?>
            </div>

        </div>
    </div>
</div>
