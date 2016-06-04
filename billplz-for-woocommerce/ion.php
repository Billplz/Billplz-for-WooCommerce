<?php
function wcbillplz_woocommerce_fallback_notice() {
    $message = '<div class="error">';
    $message .= '<p>' . __( 'WooCommerce Billplz Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!' , 'wcbillplz' ) . '</p>';
    $message .= '</div>';
    echo $message;
}
//Load the function
add_action( 'plugins_loaded', 'wcbillplz_gateway_load', 0 );
/**
 * Load Billplz gateway plugin function
 * 
 * @return mixed
 */
function DapatkanLinkWoo($api_key, $billplz_data, $host){
	$process = curl_init($host );
	curl_setopt($process, CURLOPT_HEADER, 0);
	curl_setopt($process, CURLOPT_USERPWD, $api_key . ":");
	curl_setopt($process, CURLOPT_TIMEOUT, 30);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($billplz_data) );
	$return = curl_exec($process);
	curl_close($process);
	$arr = json_decode($return,true);
	return $arr;
	}
function DapatkanInfoWoo($api_key, $verification2, $host){
$process = curl_init($host.$verification2 );
curl_setopt($process, CURLOPT_HEADER, 0);
curl_setopt($process, CURLOPT_USERPWD, $api_key . ":");
curl_setopt($process, CURLOPT_TIMEOUT, 30);
curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
$return = curl_exec($process);
curl_close($process);
$arra = json_decode($return,true);
return $arra;
}
function unlinkr($dir, $pattern = "*") {
    // find all files and folders matching pattern
    $files = glob($dir . "/$pattern"); 
	$now   = time();
    //interate thorugh the files and folders
    foreach($files as $file){ 
    //if it is a directory then re-call unlinkr function to delete files inside this directory     
        if (is_dir($file) and !in_array($file, array('..', '.')))  {
            unlinkr($file, $pattern);
            //remove the directory itself
            if ($now - filemtime($file) >= 60 * 60 * 24 * 2) // 2 days
            rmdir($file);
        } else if(is_file($file) and ($file != __FILE__)) {
            // make sure you don't delete the current script
            if ($now - filemtime($file) >= 60 * 60 * 24 * 2) // 2 days
			unlink($file); 
        }
    }
}
function wcbillplz_gateway_load() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'wcbillplz_woocommerce_fallback_notice' );
        return;
    }
    //Load language
    load_plugin_textdomain( 'wcbillplz', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    add_filter( 'woocommerce_payment_gateways', 'wcbillplz_add_gateway' );
    /**
     * Add Billplz gateway to ensure WooCommerce can load it
     * 
     * @param array $methods
     * @return array
     */
    function wcbillplz_add_gateway( $methods ) {
        $methods[] = 'WC_Billplz_Gateway';
        return $methods;
    }
    /**
     * Define the Billplz gateway
     * 
     */
    class WC_Billplz_Gateway extends WC_Payment_Gateway {
        /**
         * Construct the Billplz gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct() {
            global $woocommerce;
            $this->id = 'billplz';
            $this->icon = plugins_url( 'images/billplz.gif', __FILE__ );
            $this->has_fields = false;
			$this->method_title = __( 'Billplz', 'wcbillplz' );
            // Load the form fields.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();
			
			//Billplz Staging
			if ($this->settings['teststaging'] == "no"){
			$this->host = 'https://www.billplz.com/api/v3/bills/';
			}
			else {
			$this->host = 'https://billplz-staging.herokuapp.com/api/v3/bills/';
			}
			
            // Define user setting variables.
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->api_key = $this->settings['api_key'];
            $this->collection_id = $this->settings['collection_id'];
            $this->smsnoti = $this->settings['smsnoti'];
            $this->emailnoti = $this->settings['emailnoti'];
            $this->nopostdata = $this->settings['nopostdata'];
			$this->teststaging = $this->settings['teststaging'];
            add_action( 'woocommerce_receipt_billplz', array( &$this, 'receipt_page' ) );
			
            //save setting configuration
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
						
            // Payment listener/API hook
            add_action( 'woocommerce_api_wc_billplz_gateway', array( $this, 'check_ipn_response' ) );
            // Checking if api_key is not empty.
            $this->api_key == '' ? add_action( 'admin_notices', array( &$this, 'api_key_missing_message' ) ) : '';
            // Checking if collection_id is not empty.
            $this->collection_id == '' ? add_action( 'admin_notices', array( &$this, 'collection_id_missing_message' ) ) : '';
        }	
        /**
         * Checking if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        public function is_valid_for_use() {
            if ( !in_array( get_woocommerce_currency() , array( 'MYR' ) ) ) {
                return false;
            }
            return true;
        }
        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options() {
            ?>
            <h3><?php _e( 'Billplz Online Payment', 'wcbillplz' ); ?></h3>
            <p><?php _e( 'Billplz Online Payment works by sending the user to Billplz to enter their payment information. Please consider a donation to developer.', 'wcbillplz' ); ?><a href="https://www.billplz.com/form/sw2co7ig8" target="_blank"><?php _e( 'Donate Now', 'wcbillplz' ); ?></a></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }
        /**
         * Gateway Settings Form Fields.
         * 
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'wcbillplz' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable Billplz', 'wcbillplz' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'wcbillplz' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'wcbillplz' ),
                    'default' => __( 'Billplz Malaysia Online Payment', 'wcbillplz' )
                ),
                'description' => array(
                    'title' => __( 'Description', 'wcbillplz' ),
                    'type' => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'wcbillplz' ),
                    'default' => __( 'Pay with Maybank2u, CIMB Clicks, Bank Islam, FPX', 'wcbillplz' )
                ),
                'api_key' => array(
                    'title' => __( 'API Secret Key', 'wcbillplz' ),
                    'type' => 'text',
                    'description' => __( 'Please enter your Billplz Api Key.', 'wcbillplz' ) . ' ' . sprintf( __( 'Get Your API Key: %sBillplz%s.', 'wcbillplz' ), '<a href="https://www.billplz.com/enterprise/setting" target="_blank">', '</a>' ),
                    'default' => ''
                ),
                'collection_id' => array(
                    'title' => __( 'Collection ID', 'wcbillplz' ),
                    'type' => 'text',
                    'description' => __( 'Please enter your Billplz Collection ID.', 'wcbillplz' ) . ' ' . sprintf( __( 'Collection ID can be generated here: %sWanzul.Net%s.', 'wcbillplz' ), '<a href="https://www.wanzul.net/billplz/createcc.php" target="_blank">', '</a>' ),
                    'default' => ''
                ),
                    'smsnoti' => array(
                    'title' => __( 'SMS Notification', 'wcbillplz' ),
                    'type' => 'checkbox',
                    'label' => __( 'Get customer notified via SMS for the payment', 'wcbillplz' ),
                    'description' => __( 'RM0.15 charges per SMS.', 'wcbillplz' ),
                    'default' => 'no'
                ),
                    'emailnoti' => array(
                    'title' => __( 'Email Notification', 'wcbillplz' ),
                    'type' => 'checkbox',
                    'label' => __( 'Get customer notified via Email for the payment ', 'wcbillplz' ),
                    'description' => __( 'Free.', 'wcbillplz' ),
                    'default' => 'no'
                ),
					'teststaging' => array(
                    'title' => __( 'Test Mode', 'wcbillplz' ),
                    'type' => 'checkbox',
                    'label' => __( 'Staging Mode.', 'wcbillplz' ),
                    'description' => __( 'To Use Staging Mode, Please Ensure You Are Using The Correct API Key and Collection ID.', 'wcbillplz' ),
                    'default' => 'no'
                ),
				    'nopostdata' => array(
                    'title' => __( 'Use no Callback Method', 'wcbillplz' ),
                    'type' => 'checkbox',
                    'label' => __( 'I am having problem with Payment Response', 'wcbillplz' ),
                    'description' => __( 'Tick this option if you are having problem with payment status.', 'wcbillplz' ),
                    'default' => 'no'
                )
            );
        }
        /**
         * Generate the form.
         *
         * @param mixed $order_id
         * @return string
         */
        public function generate_form( $order_id ) {
            $order = new WC_Order( $order_id );	
			$order->reduce_order_stock();
            $host = $this->host; //ubah disini utk tambah feature
            $api_key = $this->api_key;
            $amount = $order->order_total;
            if ($this->smsnoti == "no"){
            $smsnoti = false;
            }
            else {
            $smsnoti = true;
            }
            if ($this->emailnoti == "no"){
            $emailnoti = false;
            }
            else {
            $emailnoti = true;
            }
			if ($this->nopostdata == "no"){
				$postboo = false;
			}
			else {
				$postboo = true;		
			}
			$nopostdata = "&orderid=".$order_id;
			//number intelligence
			$custTel = $order->billing_phone;
$custTel2 = substr($order->billing_phone, 0, 1);
if ($custTel2 == '+'){
$custTel3 = substr($order->billing_phone, 1, 1);
if ($custTel3 != '6')
$custTel = "+6" . $order->billing_phone;
}
else if ($custTel2 == '6') {
}
else {
	if ($custTel != '')
$custTel = "+6" . $order->billing_phone;
}
//number intelligence
$emailCust = $order->billing_email;

            if ($smsnoti && $emailnoti){
            $deliver = true;
            }
            else if ($smsnoti == false && $emailnoti == false){
            $deliver = false;
            }    
            else if ($smsnoti == true && $emailnoti == false){
            $emailCust = "";
            $deliver = true;
}
            else {
            $deliver = true;
            $custTel = "";
            }
			$custTel = preg_replace("/[^0-9]/","",$custTel);
			
            if ( sizeof( $order->get_items() ) > 0 ) 
                foreach ( $order->get_items() as $item )
                    if ( $item['qty'] )
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];
            $desc = sprintf( __( 'Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode( ', ', $item_names );			
$desc = substr($desc, 0, 200);	
            $billplz_data = array(
                'amount' => $amount * 100,
                'name' => $order->billing_first_name." ".$order->billing_last_name,
                'email' => $emailCust,
                'collection_id' => $this->collection_id,
                'mobile' => $custTel,
				'reference_1_label' => "ID",
				'reference_1' => $order_id,
                'deliver' => $deliver,
				'description' => $desc,
                'redirect_url' => str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Billplz_Gateway', home_url( '/' ) )) . $nopostdata.'&by=wanzul',
                'callback_url' => str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Billplz_Gateway', home_url( '/' ) )) . $nopostdata
            );
$arr = DapatkanLinkWoo($api_key, $billplz_data, $host);
if (isset($arr['error'])) {
            $billplz_data = array(
                'amount' => $amount * 100,
                'name' => $order->billing_first_name." ".$order->billing_last_name,
                'email' => $emailCust,
                'collection_id' => $this->collection_id,
				'reference_1_label' => "ID",
				'reference_1' => $order_id,
                'deliver' => $deliver,
				'description' => $desc,
                'redirect_url' => str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Billplz_Gateway', home_url( '/' ) )). $nopostdata.'&by=wanzul',
                'callback_url' => str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Billplz_Gateway', home_url( '/' ) )). $nopostdata
            );
$arr = DapatkanLinkWoo($api_key, $billplz_data, $host);
if (isset($arr['error'])) {
echo "<pre>".print_r($arr['error'], true)."</pre>";
exit;
}
}
if ($postboo){
$iv = json_encode(array(id => $arr['id']));
$f = fopen(__DIR__."/garbage/".$order_id.".billplz", "w");
fwrite($f, print_r($iv, true));
fclose($f);
}
$url_from_link=$arr['url'];
           return header("Location: ".$url_from_link);
//           return "<script>location.href = '$url_from_link'</script>";        
        }
        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function billplz_order_error( $order ) {
            $html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcbillplz' ) . '</p>';
            $html .='<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'wcbillplz' ) . '</a>';
            return $html;
        }
        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = new WC_Order( $order_id );
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        }
        /**
         * Output for the order received page.
         * 
         */
        public function receipt_page( $order ) {
            echo $this->generate_form( $order );
        }
        /**
         * Check for Billplz Response
         *
         * @access public
         * @return void
         */
        function check_ipn_response() {
            @ob_clean();
			if (isset($_GET['billplz'])){
			$verification2= $_GET['billplz'];
            $tranID = implode($verification2);	
			}
			else {
				if ($this->nopostdata == "no"){
					$tranID = $_POST['id'];
				}
			else {
				$tranID = $_GET['orderid'];
				$str = file_get_contents(__DIR__."/garbage/".$tranID.".billplz");
				$json = json_decode($str, true);
				$tranID = $json['id'];
				
				//buang garbage
				$dir = __DIR__."/garbage/";
				unlinkr($dir, "*.billplz");
				//buang garbage tamat
				
			}
			}
				global $woocommerce;
$arra = DapatkanInfoWoo($this->api_key, $tranID, $this->host);
//echo "<pre>".print_r($return2, true)."</pre>";
$paymentStatus=$arra['paid'];
$orderid = $_GET['orderid'];
if ($orderid != $arra['reference_1'])
	exit("Hacking Attempt!");
$order = new WC_Order( $orderid );
$referer = "<br>Referer: CallbackURL";	
if ($paymentStatus){//kena ubah 
if (!isset($_GET['billplz'])){
	if ($order->status == 'pending'){
                $order->add_order_note('Payment Status: SUCCESSFUL'.'<br>Transaction ID: ' . $tranID . $referer);								
                $order->payment_complete();
	}
	//else
	//	$order->add_order_note('Duplicated Callback by Billplz');
}
wp_redirect($order->get_checkout_order_received_url());
		}
else {
	if (!isset($_GET['billplz']))
	 $order->add_order_note('Payment Status: CANCELLED BY USER'.'<br>Transaction ID: ' . $tranID . $referer);
wp_redirect($order->get_cancel_order_url());
}
            exit;
        }
        /**
         * Adds error message when not configured the app_key.
         * 
         */
        public function api_key_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should inform your Merchant ID in Billplz. %sClick here to configure!%s' , 'wcbillplz' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_billplz_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }
        /**
         * Adds error message when not configured the app_secret.
         * 
         */
        public function collection_id_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should inform your Verify Key in Billplz. %sClick here to configure!%s' , 'wcbillplz' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_billplz_gateway">', '</a>' ) . '</p>';
            $message .= '</div>';
            echo $message;
        }
    }
}
