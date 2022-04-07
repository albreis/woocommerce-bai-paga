<?php

class WC_BaiPaga_Gateway extends WC_Payment_Gateway {

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = true;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = true;

	/**
	 * Merchant Key Credentials
	 *
	 * @var string
	 */
	public $merchant_key = '';

	/**
	 * Merchant CNPJ Credentials
	 *
	 * @var string
	 */
	public $merchant_cnpj = '';

	/**
	 * Ambient Environment
	 *
	 * @var string
	 */
	public $environment = '';

	/**
	 * Ambient Deadline
	 *
	 * @var string
	 */
	public $deadline = 0;


	/**
	 * Ambient Initial Status
	 *
	 * @var string
	 */
	public $initial_status = '';

	/**
	 * Ambient Expiry Date
	 *
	 * @var string
	 */
	public $expiry_date = '';

	/**
	 * Ambient Max Installment
	 *
	 * @var string
	 */
	public $max_installment = '';

	/**
	 * Function Plugin constructor
	 */
	public function __construct() {
		$this->id = 'bai_paga';
		$this->has_fields         = true;
		$this->method_title       = __( 'Bai Paga Payment Gateway', 'bai-paga' );
		$this->method_description = __( 'Pagar as compras online apenas com o nº de telemóvel', 'bai-paga' );

		$this->title        = $this->get_option( 'title', 'Bai Paga Payment Gateway' );
		$this->description  = $this->get_option( 'description', 'Pagar as compras online apenas com o nº de telemóvel' );
		$this->instructions = $this->get_option(
			'instructions',
			$this->description
		);

		$this->supports = array(
			'products',
		);

		$this->client_id   = $this->get_option( 'client_id' );
		$this->client_secret   = $this->get_option( 'client_secret' );
		$this->email_vendedor   = $this->get_option( 'email_vendedor' );
		$this->environment    = $this->get_option( 'environment' );
		$this->token_vendedor   = $this->get_option( 'token_vendedor' );
		$this->chave_pix   = $this->get_option( 'chave_pix' );
		$this->cert_pem   = $this->get_option( 'cert_pem' );
    $this->cert_key   = $this->get_option( 'cert_key' );

    if($this->environment == 'sandbox') {
  		$this->client_id   = $this->get_option( 'client_id_sandbox' );
  		$this->client_secret   = $this->get_option( 'client_secret_sandbox' );
  		$this->email_vendedor   = $this->get_option( 'email_vendedor_sandbox' );
  		$this->token_vendedor   = $this->get_option( 'token_vendedor_sandbox' );
  		$this->chave_pix   = $this->get_option( 'chave_pix_sandbox' );
  		$this->cert_pem   = $this->get_option( 'cert_pem_sandbox' );
      $this->cert_key   = $this->get_option( 'cert_key_sandbox' );
    }

		$this->init_settings();
		$this->init_form_fields();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thank_you_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		
	}

	/**
	 * Init init_form_fields form fields
	 */
	public function init_form_fields() {

    $params = array(
      'enabled'                    => array(
        'title'   => __( 'Habilitar/Desabilitar:', 'bai-paga' ),
        'type'    => 'checkbox',
        'label'   => __( 'Habilitar ou desabilitar o Módulo de Pagamento', 'bai-paga' ),
        'default' => 'no',
      ),
      'environment'    => array(
        'title'   => __( 'Ambiente:', 'bai-paga' ),
        'type'    => 'select',
        'options' => array(
          'production' => __( 'Produção', 'bai-paga' ),
          'sandbox'    => __( 'Sandbox', 'bai-paga' ),
        ),
      ),
      'title'                      => array(
        'title'       => __( 'Título:', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( 'Pagamento por PIX', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'description'                => array(
        'title'       => __( 'Descrição:', 'bai-paga' ),
        'type'        => 'textarea',
        'description' => __( 'Tenha seu pagamento aprovado na hora', 'bai-paga' ),
        'default'     => __( 'Tenha seu pagamento aprovado na hora', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'instructions'               => array(
        'title'       => __( 'Instruções Após o Pedido:', 'bai-paga' ),
        'type'        => 'textarea',
        'description' => __( 'As instruções iram aparecer na página de Obrigado & Email após o pedido ser feito.', 'bai-paga' ),
        'default'     => __( 'Continue nessa página, o status do pagamento será atualizado automaticamente.', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      // dados da api
      'client_id'                      => array(
        'title'       => __( 'Cliente ID:', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'client_secret'                      => array(
        'title'       => __( 'Cliente Secret:', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'email_vendedor'                      => array(
        'title'       => __( 'E-mail Vendedor:', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'token_vendedor'                      => array(
        'title'       => __( 'Token Vendedor:', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'chave_pix'                      => array(
        'title'       => __( 'Chave PIX:', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),   
      'cert_pem'                      => array(
        'title'       => __( 'Cert PEM File (production):', 'bai-paga' ),
        'type'        => 'textarea',
        'description' => __( 'Arquivo .pem do certificado', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'cert_key'                      => array(
        'title'       => __( 'Cert KEY File (production):', 'bai-paga' ),
        'type'        => 'textarea',
        'description' => __( 'Arquivo .key do certificado', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ), 
      // dados da api sandbox
      'client_id_sandbox'                      => array(
        'title'       => __( 'Cliente ID (sandbox):', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'client_secret_sandbox'                      => array(
        'title'       => __( 'Cliente Secret (sandbox):', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'email_vendedor_sandbox'                      => array(
        'title'       => __( 'E-mail Vendedor (sandbox):', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'token_vendedor_sandbox'                      => array(
        'title'       => __( 'Token Vendedor (sandbox):', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'chave_pix_sandbox'                      => array(
        'title'       => __( 'Chave PIX (sandbox):', 'bai-paga' ),
        'type'        => 'text',
        'description' => __( '', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),       
      'cert_pem_sandbox'                      => array(
        'title'       => __( 'Cert PEM File (sandbox):', 'bai-paga' ),
        'type'        => 'textarea',
        'description' => __( 'Arquivo .pem do certificado', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),
      'cert_key_sandbox'                      => array(
        'title'       => __( 'Cert KEY File (sandbox):', 'bai-paga' ),
        'type'        => 'textarea',
        'description' => __( 'Arquivo .key do certificado', 'bai-paga' ),
        'default'     => __( '', 'bai-paga' ),
        'desc_tip'    => false,
      ),     
            
    );

    $tokens = get_option('bai_paga');
    
		$this->form_fields = apply_filters(
      'bai_paga_card_pay_fields',
      $params
		);
  }
  
  function admin_options() { ?>
    <h2><?php _e('Bai Paga','woocommerce'); ?></h2>
    <div class="pix-pag" id="ppst_form">
      <div class="field">
        <label>Habilitar</label>
        <input type="hidden" v-if="habilitar == 'yes'" value="yes" name="woocommerce_bai_paga_enabled" />
        <select v-model="habilitar">
          <option value="yes">Sim</option>
          <option value="no">Não</option>
        </select>
      </div>
      <div class="field">
        <label>Título</label>
        <input type="text" v-model="settings.woocommerce_bai_paga_title" name="woocommerce_bai_paga_title">
      </div>
      <div class="field">
        <label>Descrição</label>
        <input type="text" v-model="settings.woocommerce_bai_paga_description" name="woocommerce_bai_paga_description">
      </div>
      <div class="field">
        <label>Instruções Após o Pedido</label>
        <input type="text" v-model="settings.woocommerce_bai_paga_instructions" name="woocommerce_bai_paga_instructions">
      </div>
      <div class="field">
        <label>Ambiente</label>
        <select v-model="settings.woocommerce_bai_paga_environment" name="woocommerce_bai_paga_environment">
          <option value="sandbox">Sandbox</option>
          <option value="production">Produção</option>
        </select>
      </div>
      <div class="env" v-show="settings.woocommerce_bai_paga_environment == 'production'">
        <div class="field">
          <label>Cliente ID</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_client_id" name="woocommerce_bai_paga_client_id">
        </div>
        <div class="field">
          <label>Cliente Secret</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_client_secret" name="woocommerce_bai_paga_client_secret">
        </div>
        <div class="field">
          <label>E-mail Vendedor</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_email_vendedor" name="woocommerce_bai_paga_email_vendedor">
        </div>
        <div class="field">
          <label>Token Vendedor</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_token_vendedor" name="woocommerce_bai_paga_token_vendedor">
        </div>
        <div class="field">
          <label>Chave PIX</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_chave_pix" name="woocommerce_bai_paga_chave_pix">
        </div>
        <div class="field">
          <label>Cert PEM File</label>
          <textarea type="text" v-model="settings.woocommerce_bai_paga_cert_pem" name="woocommerce_bai_paga_cert_pem"></textarea>
        </div>
        <div class="field">
          <label>Cert KEY File</label>
          <textarea type="text" v-model="settings.woocommerce_bai_paga_cert_key" name="woocommerce_bai_paga_cert_key"></textarea>
        </div>
      </div>
      <div class="env" v-show="settings.woocommerce_bai_paga_environment == 'sandbox'">
        <div class="field">
          <label>Cliente ID</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_client_id_sandbox" name="woocommerce_bai_paga_client_id_sandbox">
        </div>
        <div class="field">
          <label>Cliente Secret</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_client_secret_sandbox" name="woocommerce_bai_paga_client_secret_sandbox">
        </div>
        <div class="field">
          <label>E-mail Vendedor</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_email_vendedor_sandbox" name="woocommerce_bai_paga_email_vendedor_sandbox">
        </div>
        <div class="field">
          <label>Token Vendedor</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_token_vendedor_sandbox" name="woocommerce_bai_paga_token_vendedor_sandbox">
        </div>
        <div class="field">
          <label>Chave PIX</label>
          <input type="text" v-model="settings.woocommerce_bai_paga_chave_pix_sandbox" name="woocommerce_bai_paga_chave_pix_sandbox">
        </div>
        <div class="field">
          <label>Cert PEM File</label>
          <textarea type="text" v-model="settings.woocommerce_bai_paga_cert_pem_sandbox" name="woocommerce_bai_paga_cert_pem_sandbox"></textarea>
        </div>
        <div class="field">
          <label>Cert KEY File</label>
          <textarea type="text" v-model="settings.woocommerce_bai_paga_cert_key_sandbox" name="woocommerce_bai_paga_cert_key_sandbox"></textarea>
        </div>
      </div>
      <div class="field" v-if="gerar_logs">
        <label>Logs de homologação</label>
        <a class="button" href="<?php echo site_url('wp-json/bai-paga/v1/download-logs'); ?>">Gerar arquivo de logs para homologação</a>
      </div>
    </div>
    <script>
      window.addEventListener('load', function(){
        new Vue({
          el: '#ppst_form',
          data() {
            return {
              habilitar: 'no',
              settings: <?php echo json_encode(array_combine(array_map(function($key){ return 'woocommerce_bai_paga_'.$key; }, array_keys($this->settings)), $this->settings)); ?>
            }
          },
          mounted() {
            this.habilitar = this.settings.woocommerce_bai_paga_enabled
          },
          computed: {
            gerar_logs() {
              return  this.settings.woocommerce_bai_paga_environment == 'sandbox' &&
                    this.settings.woocommerce_bai_paga_client_id_sandbox &&  
                    this.settings.woocommerce_bai_paga_client_secret_sandbox && 
                    this.settings.woocommerce_bai_paga_email_vendedor_sandbox && 
                    this.settings.woocommerce_bai_paga_token_vendedor_sandbox && 
                    this.settings.woocommerce_bai_paga_chave_pix_sandbox && 
                    this.settings.woocommerce_bai_paga_cert_pem_sandbox && 
                    this.settings.woocommerce_bai_paga_cert_key_sandbox
            }
          }
        })
      })
    </script>
    <?php //$this->generate_settings_html(); ?>
   <?php  }
	

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'bai-paga' ) );
		}
	}

	/**
	 * Process_payment method.
	 *
	 * @param int $order_id Id of order.
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

    $token = '12312';

    $m = "<payment_id>|<nonce>|<externalReference>|<payment_amount>|<payment_currency>|<payment_lastChangeDate>|<payment_merchant.externalId>";

    $signature = base64_encode(hash_hmac('sha256', $m, $token));

    // $order->update_meta_data('_ppst_cobranca', $response);
    // $order->update_meta_data('_ppst_txid', $txid);
    $order->update_status( 'pending', __( 'Aguardando autorização do pagamento', 'bai-paga' ) );  					
    return array(
      'result'   => 'error',
      'message'   => 'error',
      'redirect' => $this->get_return_url( $order ),
    );
	}

  public function payment_fields() {
    woocommerce_form_field( 'phone', array(
      'type'          => 'text',
      'class'         => array('phone form-row-wide'),
      'label'         => __('Telemóvel', 'bai-paga'),
      'required'      => true
    ), '' );
  }

	/**
	 * Thankyou_page method.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
    }
	}
}
