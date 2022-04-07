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
		$this->id = 'pix_pagseguro_albreis';
		$this->has_fields         = true;
		$this->method_title       = __( 'Bai Paga Payment Gateway', 'bai-paga' );
		$this->method_description = __( 'Pagamento por pix no PagSeguro', 'bai-paga' );

		$this->title        = $this->get_option( 'title', 'Pagamento por PIX' );
		$this->description  = $this->get_option( 'description', 'Pague com pix' );
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

    $this->cert_pem_file = __DIR__ . '/' . get_option('p_p_a_certfile') . '.' . $this->environment . '.pem';
    if(!file_exists($this->cert_pem_file)) {
      file_put_contents($this->cert_pem_file, $this->cert_pem);
    }

    $this->cert_key_file = __DIR__ . '/' . get_option('p_p_a_certfile') . '.' . $this->environment . '.key';
    if(!file_exists($this->cert_key_file) && $this->cert_key) {
      file_put_contents($this->cert_key_file, $this->cert_key);
    }

    $tokens = get_option('pix_pagseguro_albreis');
    
    if(!get_option('p_p_a_webhook_' . $this->environment) && $tokens && isset($tokens->access_token) && $tokens->access_token) {
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->environment == 'sandbox' ? 'https://secure.sandbox.api.pagseguro.com/instant-payments/webhook/' . $this->chave_pix : 'https://secure.api.pagseguro.com/instant-payments/webhook/' . $this->chave_pix,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_SSLKEY => $this->cert_key_file,
        CURLOPT_SSLCERT => $this->cert_pem_file,
        CURLOPT_POSTFIELDS =>'{
          "webhookUrl": "' . get_site_url() . "/wc-api/" . get_option('p_p_a_webhook_key') . '"
        }',
        CURLOPT_HTTPHEADER => array(
          "Authorization: Bearer {$tokens->access_token}",
          'Content-Type: application/json',
        ),
      ));      
      $response = curl_exec($curl); 
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->environment == 'sandbox' ? 'https://secure.sandbox.api.pagseguro.com/instant-payments/webhook/' . $this->chave_pix : 'https://secure.api.pagseguro.com/instant-payments/webhook/' . $this->chave_pix,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_SSLKEY => $this->cert_key_file,
        CURLOPT_SSLCERT => $this->cert_pem_file,
        CURLOPT_POSTFIELDS =>'',
        CURLOPT_HTTPHEADER => array(
          "Authorization: Bearer {$tokens->access_token}",
          'Content-Type: application/json',
        ),
      ));      
      $response = curl_exec($curl);  
      update_option('p_p_a_webhook_' . $this->environment, $response);
    }

		$this->form_fields = apply_filters(
      'pix_pagseguro_albreis_card_pay_fields',
      $params
		);
  }

  function process_admin_options() {
    parent::process_admin_options();
    if(!isset($_POST['woocommerce_pix_pagseguro_albreis_environment'])) return;
    if($_POST['woocommerce_pix_pagseguro_albreis_environment'] == 'production') {
      $token = base64_encode("{$_POST['woocommerce_pix_pagseguro_albreis_client_id']}:{$_POST['woocommerce_pix_pagseguro_albreis_client_secret']}");
      $curl = curl_init();
      $body = json_encode(array(
        "grant_type" => "client_credentials",
        "scope" => "pix.read pix.write cob.read cob.write webhook.write webhook.read payloadlocation.write payloadlocation.read"
      ));
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://secure.api.pagseguro.com/pix/oauth2',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_SSLKEY => $this->cert_key_file,
        CURLOPT_SSLCERT => $this->cert_pem_file,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_VERBOSE => true,
        // CURLOPT_STDERR => fopen('./curl.log', 'w+'),
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => array(
          "Authorization: Basic {$token}",
          'Content-Type: application/json',
        ),
      ));
      $response = curl_exec($curl);
    }
    else {
      $token = base64_encode("{$_POST['woocommerce_pix_pagseguro_albreis_client_id_sandbox']}:{$_POST['woocommerce_pix_pagseguro_albreis_client_secret_sandbox']}");
      $curl = curl_init();
      $body = json_encode(array(
        "grant_type" => "client_credentials",
        "scope" => "pix.read pix.write cob.read cob.write webhook.write webhook.read payloadlocation.write payloadlocation.read"
      ));
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://secure.sandbox.api.pagseguro.com/pix/oauth2',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_SSLKEY => $this->cert_key_file,
        CURLOPT_SSLCERT => $this->cert_pem_file,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_VERBOSE => true,
        // CURLOPT_STDERR => fopen('./curl.log', 'w+'),
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => array(
          "Authorization: Basic {$token}",
          'Content-Type: application/json',
        ),
      ));
      $response = curl_exec($curl);
    }
    update_option('pix_pagseguro_albreis', json_decode($response));
  }
  
  function admin_options() { ?>
    <h2><?php _e('Pix PagSeguro','woocommerce'); ?></h2>
    <div class="pix-pag" id="ppst_form">
      <div class="field">
        <label>Habilitar</label>
        <input type="hidden" v-if="habilitar == 'yes'" value="yes" name="woocommerce_pix_pagseguro_albreis_enabled" />
        <select v-model="habilitar">
          <option value="yes">Sim</option>
          <option value="no">Não</option>
        </select>
      </div>
      <div class="field">
        <label>Título</label>
        <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_title" name="woocommerce_pix_pagseguro_albreis_title">
      </div>
      <div class="field">
        <label>Descrição</label>
        <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_description" name="woocommerce_pix_pagseguro_albreis_description">
      </div>
      <div class="field">
        <label>Instruções Após o Pedido</label>
        <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_instructions" name="woocommerce_pix_pagseguro_albreis_instructions">
      </div>
      <div class="field">
        <label>Ambiente</label>
        <select v-model="settings.woocommerce_pix_pagseguro_albreis_environment" name="woocommerce_pix_pagseguro_albreis_environment">
          <option value="sandbox">Sandbox</option>
          <option value="production">Produção</option>
        </select>
      </div>
      <div class="env" v-show="settings.woocommerce_pix_pagseguro_albreis_environment == 'production'">
        <div class="field">
          <label>Cliente ID</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_client_id" name="woocommerce_pix_pagseguro_albreis_client_id">
        </div>
        <div class="field">
          <label>Cliente Secret</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_client_secret" name="woocommerce_pix_pagseguro_albreis_client_secret">
        </div>
        <div class="field">
          <label>E-mail Vendedor</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_email_vendedor" name="woocommerce_pix_pagseguro_albreis_email_vendedor">
        </div>
        <div class="field">
          <label>Token Vendedor</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_token_vendedor" name="woocommerce_pix_pagseguro_albreis_token_vendedor">
        </div>
        <div class="field">
          <label>Chave PIX</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_chave_pix" name="woocommerce_pix_pagseguro_albreis_chave_pix">
        </div>
        <div class="field">
          <label>Cert PEM File</label>
          <textarea type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_cert_pem" name="woocommerce_pix_pagseguro_albreis_cert_pem"></textarea>
        </div>
        <div class="field">
          <label>Cert KEY File</label>
          <textarea type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_cert_key" name="woocommerce_pix_pagseguro_albreis_cert_key"></textarea>
        </div>
      </div>
      <div class="env" v-show="settings.woocommerce_pix_pagseguro_albreis_environment == 'sandbox'">
        <div class="field">
          <label>Cliente ID</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_client_id_sandbox" name="woocommerce_pix_pagseguro_albreis_client_id_sandbox">
        </div>
        <div class="field">
          <label>Cliente Secret</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_client_secret_sandbox" name="woocommerce_pix_pagseguro_albreis_client_secret_sandbox">
        </div>
        <div class="field">
          <label>E-mail Vendedor</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_email_vendedor_sandbox" name="woocommerce_pix_pagseguro_albreis_email_vendedor_sandbox">
        </div>
        <div class="field">
          <label>Token Vendedor</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_token_vendedor_sandbox" name="woocommerce_pix_pagseguro_albreis_token_vendedor_sandbox">
        </div>
        <div class="field">
          <label>Chave PIX</label>
          <input type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_chave_pix_sandbox" name="woocommerce_pix_pagseguro_albreis_chave_pix_sandbox">
        </div>
        <div class="field">
          <label>Cert PEM File</label>
          <textarea type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_cert_pem_sandbox" name="woocommerce_pix_pagseguro_albreis_cert_pem_sandbox"></textarea>
        </div>
        <div class="field">
          <label>Cert KEY File</label>
          <textarea type="text" v-model="settings.woocommerce_pix_pagseguro_albreis_cert_key_sandbox" name="woocommerce_pix_pagseguro_albreis_cert_key_sandbox"></textarea>
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
              settings: <?php echo json_encode(array_combine(array_map(function($key){ return 'woocommerce_pix_pagseguro_albreis_'.$key; }, array_keys($this->settings)), $this->settings)); ?>
            }
          },
          mounted() {
            this.habilitar = this.settings.woocommerce_pix_pagseguro_albreis_enabled
          },
          computed: {
            gerar_logs() {
              return  this.settings.woocommerce_pix_pagseguro_albreis_environment == 'sandbox' &&
                    this.settings.woocommerce_pix_pagseguro_albreis_client_id_sandbox &&  
                    this.settings.woocommerce_pix_pagseguro_albreis_client_secret_sandbox && 
                    this.settings.woocommerce_pix_pagseguro_albreis_email_vendedor_sandbox && 
                    this.settings.woocommerce_pix_pagseguro_albreis_token_vendedor_sandbox && 
                    this.settings.woocommerce_pix_pagseguro_albreis_chave_pix_sandbox && 
                    this.settings.woocommerce_pix_pagseguro_albreis_cert_pem_sandbox && 
                    this.settings.woocommerce_pix_pagseguro_albreis_cert_key_sandbox
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
    $tokens = get_option('pix_pagseguro_albreis');
    $curl = curl_init();
    $txid = substr(preg_replace('/[^\d\w]+/', '', base64_encode(openssl_random_pseudo_bytes(60))), 0, 35);
    $body = json_encode(array(
      "calendario" => [
        "expiracao" => "3600"
      ],
      "devedor" => [
        "cpf" => preg_replace('/[^\d]+/', '', $order->get_meta('_billing_cpf')),
        "nome" => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
      ],
      "valor" => [
        "original" => $order->get_total()
      ],
      "solicitacaoPagador" => "Pagamento do pedido #" . $order->get_id(),
      "chave" => $this->chave_pix,
      "infoAdicionais" => [
          [
              "nome" => "ID do Pedido",
              "valor" => $order->get_id()
          ],
          [
              "nome" => "Vendedor",
              "valor" => get_site_url()
          ],
          [
              "nome" => "Data da Compra",
              "valor" => date('d/m/Y H:i:s')
          ],
          [
              "nome" => "IP do Comprador",
              "valor" => $_SERVER['REMOTE_ADDR']
          ]
      ]
    ));
    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->environment == 'sandbox' ? "https://secure.sandbox.api.pagseguro.com/instant-payments/cob/{$txid}" : "https://secure.api.pagseguro.com/instant-payments/cob/{$txid}",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HEADER => false,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'PUT',
      CURLOPT_SSLKEY => $this->cert_key_file,
      CURLOPT_SSLCERT => $this->cert_pem_file,
      CURLINFO_HEADER_OUT => true,
      CURLOPT_VERBOSE => true,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer {$tokens->access_token}",
        'Content-Type: application/json',
      ),
    ));
    $response = curl_exec($curl);
    $cobranca = json_decode($response);
    $order->update_meta_data('_ppst_cobranca', $response);
    $order->update_meta_data('_ppst_txid', $txid);
    $order->update_status( 'pending', __( 'Aguardando autorização do pagamento', 'bai-paga' ) );  					
    return array(
      'result'   => 'success',
      'redirect' => $this->get_return_url( $order ),
    );
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
