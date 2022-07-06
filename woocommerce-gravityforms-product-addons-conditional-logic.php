<?php
/*
 * Plugin Name: WooCommerce Gravity Forms Product Add-Ons Conditional Logic
 * Plugin URI:
 * Description: Provides for hooks for conditional logic for variable products.
 * Version: 1.0.1
 * Author: Element Stark
 * Author URI: https://www.elementstark.com/
 * Developer: Lucas Stark
 * Developer URI: http://www.elementstark.com/
 * Requires at least: 3.1
 * Tested up to: 5.8

 * Copyright: © 2009-2021 Element Stark.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html

 * WC requires at least: 3.0.0
 * WC tested up to: 6.0
 */


class WC_GFPA_CL {
	/**
	 *
	 * @var WC_GFPA_CL
	 */
	private static $instance = null;

	public static function register() {
		if (self::$instance == null){
			self::$instance = new WC_GFPA_CL();
		}
	}

	public static $scripts_version = '1.0.1';

	public function __construct( ) {
		add_action( 'wp_enqueue_scripts', [ $this, 'on_wp_enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', array( $this, 'on_admin_enqueue_scripts' ), 100 );
		add_action( 'wp_ajax_wc_gravityforms_get_conditional_logic_fields', array( $this, 'get_fields' ) );
		add_filter( 'woocommerce_gravityforms_before_save_metadata', [ $this, 'on_before_save_metadata' ] );
		add_action( 'woocommerce_gforms_after_field_groups', [ $this, 'render_field_group' ], 10, 2 );
	}

	public function on_wp_enqueue_scripts() {

		if (is_product() && $gravity_form_data = wc_gfpa()->get_gravity_form_data(get_the_ID())) {
			if ($gravity_form_data['gravityform_conditional_logic_type'] !== 'none') {
				switch ($gravity_form_data['gravityform_conditional_logic_type']) {
					case 'attributes' :
						wp_enqueue_script( 'wcgfpacl_frontend', self::plugin_url() . '/assets/js/attributes.js', [ 'jquery' ], self::$scripts_version, true );

						$config = $gravity_form_data['gravityform_conditional_logic_fields'];
						$selectors = [];

						foreach ($config as $attribute => $field_id) {
							if (!empty($field_id)) {
								$selectors[] = [
									'attribute_' . $attribute,
									'#input_' . $gravity_form_data['id'] . '_' . $field_id
								];
							}
						}

						$data = [
							'fieldSelectors' => $selectors,
						];

						wp_localize_script( 'wcgfpacl_frontend', 'wc_gforms_cl', $data );
						break;
					case 'variation':
						wp_enqueue_script( 'wcgfpacl_frontend', self::plugin_url() . '/assets/js/variations.js', [ 'jquery' ], self::$scripts_version, true );

						$data = [
							'fieldSelector' => '#input_' . $gravity_form_data['id'] . '_' . $gravity_form_data['gravityform_conditional_logic_field'],
							'variationAttribute' => 'variation_id'
						];

						wp_localize_script( 'wcgfpacl_frontend', 'wc_gforms_cl', $data );
						break;
					default:
						break;
				}
			}
		}


	}

	public function on_admin_enqueue_scripts() {
		wp_enqueue_script( 'wcgfpacl_admin', self::plugin_url() . '/assets/js/admin.js', [ 'jquery' ], self::$scripts_version, true );
	}

	/** Ajax Handling */
	public function get_fields() {
		check_ajax_referer( 'wc_gravityforms_get_products', 'wc_gravityforms_security' );

		$form_id = $_POST['form_id'] ?? 0;
		if ( empty( $form_id ) ) {
			wp_send_json_error( array(
				'status'  => 'error',
				'message' => __( 'No Form ID', 'wc_gf_addons' ),
			) );
			die();
		}

		$product_id        = $_POST['product_id'] ?? 0;
		$gravity_form_data = wc_gfpa()->get_gravity_form_data( $product_id );
		if ( $_POST['gravityform_conditional_logic_type'] == 'attributes' ) {
			$markup = self::get_attribute_fields_markup( $product_id, $form_id, $gravity_form_data['gravityform_conditional_logic_fields'] ?? [] );
		} elseif ( $_POST['gravityform_conditional_logic_type'] == 'variation' ) {
			$markup = self::get_variation_field_markup( $product_id, $form_id, $gravity_form_data['gravityform_conditional_logic_field'] );
		}

		$response = array(
			'status'  => 'success',
			'message' => '',
			'markup'  => $markup
		);

		wp_send_json_success( $response );
		die();
	}

	/**
	 * @param $product_id
	 * @param $form_id
	 *
	 * @return false|string
	 */
	public static function get_attribute_fields_markup( $product_id, $form_id, $current = [] ) {
		$product = wc_get_product( $product_id );


		$form   = GFAPI::get_form( $form_id );
		$fields = GFAPI::get_fields_by_type( $form, array( 'hidden', 'text' ), false );

		if ( $fields ) {
			$options = [
				'' => 'None'
			];
			foreach ( $fields as $field ) {
				$options[ $field['id'] ] = $field['label'];
			}

			$attributes = $product->get_attributes();
			foreach ( $attributes as $attribute ) {
				if ( $attribute->is_taxonomy() ) {
					$key     = $attribute->get_name();
					$tax_obj = $attribute->get_taxonomy_object();
					$label   = $tax_obj->attribute_label;
				} else {
					$key   = sanitize_title( $attribute->get_name() );
					$label = $attribute->get_name();
				}

				$selected_value = '';
				if ( isset( $current[ $key ] ) ) {
					$selected_value = $current[ $key ];
				}

				woocommerce_wp_select(
					array(
						'id'          => 'gravityform_conditional_logic_fields[' . $key . ']',
						'label'       => $label,
						'value'       => $selected_value,
						'options'     => $options,
						'description' => __( 'A field to use to control conditional logic.', 'wc_gf_addons' )
					)
				);
			}

			$markup = ob_get_clean();
		} else {
			$markup = '<p class="form-field">' . __( 'No suitable fields found.', 'wc_gf_addons' ) . '</p>';
		}

		return $markup;
	}


	public static function get_variation_field_markup( $product_id, $form_id, $selected_value = '' ) {
		$product = wc_get_product( $product_id );

		$form   = GFAPI::get_form( $form_id );
		$fields = GFAPI::get_fields_by_type( $form, array( 'hidden', 'text' ), false );

		if ( $fields ) {
			$options = [
				'' => 'None'
			];
			foreach ( $fields as $field ) {
				$options[ $field['id'] ] = $field['label'];
			}

			woocommerce_wp_select(
				array(
					'id'          => 'gravityform_conditional_logic_field',
					'label'       => __( 'Variation ID Field', 'wc_gf_addons' ),
					'value'       => $selected_value,
					'options'     => $options,
					'description' => __( 'The field will be set to the value of the selected variation ID.', 'wc_gf_addons' )
				)
			);

			$markup = ob_get_clean();
		} else {
			$markup = '<p class="form-field">' . __( 'No suitable fields found.', 'wc_gf_addons' ) . '</p>';
		}

		return $markup;
	}


	public function on_before_save_metadata( $gravity_form_data ) {

		if ( isset( $_POST['gravityform_conditional_logic_type'] ) ) {
			$gravity_form_data['gravityform_conditional_logic_type'] = $_POST['gravityform_conditional_logic_type'];
		}

		if ( isset( $_POST['gravityform_conditional_logic_fields'] ) ) {
			$gravity_form_data['gravityform_conditional_logic_fields'] = $_POST['gravityform_conditional_logic_fields'];
		}

		if ( isset( $_POST['gravityform_conditional_logic_field'] ) ) {
			$gravity_form_data['gravityform_conditional_logic_field'] = $_POST['gravityform_conditional_logic_field'];
		}

		return $gravity_form_data;
	}


	public function render_field_group( $gravity_form_data, $product_id ) {
		$gravity_form_data = $gravity_form_data; // make it available for the view.
		$product           = wc_get_product( $product_id );
		include 'conditional-logic-meta-box.php';
	}


	/** Helper functions ***************************************************** */

	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) );
	}
}


WC_GFPA_CL::register();