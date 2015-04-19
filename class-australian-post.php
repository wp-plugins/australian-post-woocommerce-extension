<?php 

class WC_Australian_Post_Shipping_Method extends WC_Shipping_Method{



	public $postageTypesURL = 'http://auspost.com.au/api/postage/parcel/domestic/calculate.json';


	public function __construct(){
		$this->id = 'auspost';
		$this->method_title = __('Australian Post','australian-post');
		

		$this->init_form_fields();
		$this->init_settings();


		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->api_key = $this->get_option('api_key');
		$this->shop_post_code = $this->get_option('shop_post_code');

		add_action('woocommerce_update_options_shipping_'.$this->id, array($this, 'process_admin_options'));


	}


	public function init_form_fields(){

		$this->form_fields = array(

				'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable Australian Post', 'woocommerce' ),
				'default' 		=> 'no'
				),
				'title' => array(
					'title' 		=> __( 'Method Title', 'woocommerce' ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the title', 'woocommerce' ),
					'default'		=> __( 'Australian Post Shipping', 'woocommerce' ),
					'desc_tip'		=> true,
				),
				'api_key' => array(
						'title'             => __( 'API Key', 'australian-post' ),
						'type'              => 'text',
						'description'       => __( 'Get your key from https://developers.auspost.com.au/apis/pacpcs-registration', 'australian-post' ),
						'default'           => ''
				),
				'shop_post_code' => array(
						'title'             => __( 'Shop Post Code', 'australian-post' ),
						'type'              => 'text',
						'description'       => __( 'Enter your Shop postcode.', 'australian-post' ),
						'default'           => ''
				),


		 );

	}


	public function admin_options(){
		?>
		<h3><?php _e('Australian Post Shipping Method Settings','australian-post'); ?></h3>
		<div class="update-nag">For support, contact the developer: Waseem Senjer, waseem.senjer@gmail.com</div>
		<table class="form-table">
		<?php $this->generate_settings_html(); ?>

		</table> 	
		
		<?php

	}

	public function is_available( $package ){

		if($package['destination']['country'] == 'AU'){
			return true;
		}else{
			return false;

		}
		

	}

	public function calculate_shipping( $package ){
		
		$weight = 0;
		$length = 0;
		$width = 0;
		$height = 0;
		foreach($package['contents'] as  $item_id => $values){
			$_product =  $values['data'];
			$weight = $weight + $_product->get_weight();
			$height = $height + $_product->height;
			$width = $width + $_product->width;
			$length = $length + $_product->length;

		}

		$query_params['from_postcode'] = $this->shop_post_code;
		$query_params['to_postcode'] = $package['destination']['postcode'];
		$query_params['length'] = $length;
		$query_params['width'] = $width;
		$query_params['height'] = $height;
		$query_params['weight'] = $weight;
		$query_params['service_code'] = 'AUS_PARCEL_REGULAR';

		$response = wp_remote_get( $this->postageTypesURL.'?'.http_build_query($query_params),
			array('headers' => array('AUTH-KEY'=> $this->api_key))

		 );
		
		if(is_wp_error( $response )){
			wc_add_notice('Unknown Problem. Please Contact the admin','error');
			return;
		}

		$aus_response = json_decode($response['body']);
		
		if($aus_response->postage_result != ''){
			
			$this->add_rate(array(
					'id' => $this->id,
					'label' => $this->title.' ( '.$aus_response->postage_result->delivery_time.' )',
					'cost' => $aus_response->postage_result->total_cost,
					'taxes' => false
				));



		}

		if($aus_response->error){
			wc_add_notice($aus_response->error->errorMessage,'error');
			return;
		}


	}





}