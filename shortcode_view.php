<?php 

function simple_payson_payment_transaction () {
    
    require_once 'lib/paysonapi.php';
    // Your agent ID and md5 key
    $agentID = get_option('payson_agentID');
    $md5Key = get_option('payson_md5Key');;

    // // Account details of the receiver of money //wp_pp_payment_email
    $receiverEmail = get_option('payson_receiverEmail');
    
    // // URLs used by Payson for redirection after a completed/canceled purchase.
    // //$returnURL = get_option('payson_returnURL');  // "http://www.rikstacket.se/tack-for-din-inbetalning/"; //wp_pp_return_url
    $returnURL = get_option('payson_returnURL');
    $cancelURL = get_option('payson_cancelURL');

    // // Please note that only IP/URLS accessible from the internet will work
    //$ipnURL = "http://www.rikstacket.se/";
    $ipnURL = '';

    // // Amount to send to receiver
    // $value1 = get_option('payson_payment_value1');
    // get_option('payson_payment_item1')
    if(isset($_POST['cmd']) && $_POST['cmd'] == 'payson_transaction'){
      $amountToReceiveID = 'payson_payment_value' . trim($_POST['amount']);
	    $amountToReceive = get_option($amountToReceiveID); // "125";
	    $order_item_description_id = 'payson_payment_item' . trim($_POST['amount']);
	    $order_item_description = get_option($order_item_description_id); 
  	}
  	elseif(isset($_POST['cmd']) && $_POST['cmd'] == '_xclick'){
  		$amountToReceive = $_POST['amount'];
  		//$order_item_description = $_POST['wp_payson_button_options']['data-product_name'];
  		//echo 'Amount to receive: ' . $amountToReceive . '<br />';
  		//exit; //echo $order_item_description;
  		$order_item_description = $_POST['item_name']; 
  		//echo 'Order item description: ' . $order_item_description . '<br />';
  	}

    //echo $amountToReceive;

    // Information about the sender of money
    $senderEmail = $_POST['senderEmail']; // "test-shopper@payson.se";
    $senderFirstname = '';
    $senderFirstname = $_POST['senderFirstname']; //"Test";
    $senderLastname = '';
    $senderLastname = $_POST['senderLastname']; //"Person";

    // Output for test purposes
    $output = '';
    $output .= '<div class="panel"><div class="panel-title"></div><div class="panel-body">';
    $output .= 'agentID: ' . $agentID . '<br />';
    $output .= 'md5Key: ' . $md5Key . '<br />';
    $output .= 'receiverEmail: ' . $receiverEmail . '<br /><br />';

    $output .= 'returnURL: ' . $returnURL . '<br />';
    $output .= 'order_item_description: ' . $order_item_description . '<br />';
    $output .= 'amountToReceive: ' . $amountToReceive . '<br /><br />';

    $output .= 'senderEmail: ' . $senderEmail . '<br />';
    $output .= 'senderFirstname: ' . $senderFirstname . '<br />';
    $output .= 'senderLastname: ' . $senderLastname . '<br />';

    $output .= '</div></div>';
    
    // echo $output;

    /* Every interaction with Payson goes through the PaysonApi object which you set up as follows.  
     * For the use of our test or live environment use one following parameters:
     * TRUE: Use test environment, FALSE: use live environment */
    $credentials = new PaysonCredentials($agentID, $md5Key);
    $api = new PaysonApi($credentials, FALSE);


    // Details about the receiver
    $receiver = new Receiver(
            $receiverEmail, // The email of the account to receive the money
            $amountToReceive); // The amount you want to charge the user, here in SEK (the default currency)
    $receivers = array($receiver);

    // Details about the user that is the sender of the money
    $sender = new Sender($senderEmail, $senderFirstname, $senderLastname);
    $payData = new PayData($returnURL, $cancelURL, $ipnURL, $order_item_description, $sender, $receivers);

    //Set the list of products. For direct payment this is optional
    // For each orderItem, you must specify all or none of the parameters sku, quantity, unitPrice & taxPercentage.
    // parameters: description, sku, quantity, unit price, tax percentage
    // 
    $orderItems = array();

    //$orderItems[] = new OrderItem($order_item_description, $amountToReceive, 1, 0.00, " - ");

    $payData->setOrderItems($orderItems);

    //Set the payment method
    //$constraints = array(FundingConstraint::BANK, FundingConstraint::CREDITCARD); // bank and card
    //$constraints = array(FundingConstraint::INVOICE); // only invoice
    //$constraints = array(FundingConstraint::BANK, FundingConstraint::CREDITCARD, FundingConstraint::INVOICE); // bank, card and invoice
    $constraints = array(FundingConstraint::BANK, FundingConstraint::CREDITCARD); // only bank
    $payData->setFundingConstraints($constraints);

    //Set the payer of Payson fees
    //Must be PRIMARYRECEIVER if using FundingConstraint::INVOICE
    $payData->setFeesPayer(FeesPayer::PRIMARYRECEIVER);

    // Set currency code
    $payData->setCurrencyCode(CurrencyCode::SEK);

    // Set locale code
    $payData->setLocaleCode(LocaleCode::SWEDISH);

    // Set guarantee options
    $payData->setGuaranteeOffered(GuaranteeOffered::OPTIONAL);

    /*
     * Step 2 initiate payment
     */
    $payResponse = $api->pay($payData);

    /*
     * Step 3: verify that it suceeded
     */
    if ($payResponse->getResponseEnvelope()->wasSuccessful()) {
        /*
         * Step 4: forward user
         */
        //header("Location: " . $api->getForwardPayUrl($payResponse));
        wp_redirect($api->getForwardPayUrl($payResponse), 301);
        exit;
    }
    else {
      echo '<div class="container"><div class="panel"><div class="panel-body"><div class="alert alert-danger" role="alert">' . __('Payson transaction failed due to a configuration error. Please notify the site\'s owner.', 'simple-payson-payment') . '</div></div></div></div>';
    }

  /**
   * Perform transaction – send data to Payson's server
   */
  // wp_redirect('http://www.google.com', 301);
  // exit;
        
}

function spp_render_payson_button_with_other_amt($args)
{
	extract( shortcode_atts( array(
		'email' => '',
		'description' => '',	
		'currency' => 'SEK',
		'reference' => '',	
		'return' => site_url(),
		'country_code' => '',
		'button_image' => '',
		'cancel_url' => '',
                'new_window' => '',
                'tax' => '',
	), $args));	
	
  	$email = apply_filters('wppp_widget_any_amt_email', $email);

	$output = "";
	$payment_button_img_src = get_option('payment_button_type');
	if(!empty($button_image)){
		$payment_button_img_src = $button_image;
	}

	if(empty($email)){
		$output = '<p style="color: red;">' . __('Error! Please enter your Payson email address for the payment using the "email" parameter in the shortcode', 'simple-payson-payment') . '</p>';
		return $output;
	}
		
	if(empty($description)){
		$output = '<p style="color: red;">' . __('Error! Please enter a description for the payment using the "description" parameter in the shortcode', 'simple-payson-payment') . '</p>';
		return $output;
	}

        $window_target = '';
        if(!empty($new_window)){
            $window_target = 'target="_blank"';
        }
	$output .= '<div class="wp_payson_button_widget_any_amt">';
	$output .= '<form name="_xclick" class="wp_accept_pp_button_form_any_amount" action="' . '$_SERVER["REQUEST_URI"]' . ' method="post" '.$window_target.'>';

	$output .= 'Amount: <input type="text" name="amount" value="" size="5">';

	if(!empty($reference)){
		$output .= '<div class="wp_pp_button_reference_section">';
		$output .= '<label for="wp_pp_button_reference">'.$reference.'</label>';
		$output .= '<br />';
		$output .= '<input type="hidden" name="" value="Reference" />';
		$output .= '<input type="text" name="senderReference" value="" class="wp_pp_button_reference" />';
		$output .= '</div>';
	}
			
	$output .= '<input type="hidden" name="cmd" value="_xclick">';
	$output .= '<input type="hidden" name="business" value="'.$email.'">';
	$output .= '<input type="hidden" name="currency_code" value="'.$currency.'">';
	$output .= '<input type="hidden" name="item_name" value="'.stripslashes($description).'">';
	$output .= '<input type="hidden" name="return" value="'.$return.'" />';
        if(is_numeric($tax)){
            $output .= '<input type="hidden" name="tax" value="'.$tax.'" />';
        }
	if(!empty($cancel_url)){
		$output .= '<input type="hidden" name="cancel_return" value="'.$cancel_url.'" />';
	}
	if(!empty($country_code)){
		$output .= '<input type="hidden" name="lc" value="'.$country_code.'" />';
	}

	$output .= '<div class="wp_pp_button_submit_btn">';
	$output .= '<input type="image" id="buy_now_button" src="'.$payment_button_img_src.'" border="0" name="submit" alt="Make payments with payson">';
	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';
	return $output;
}

function spp_render_payson_button_form($args)
{	
	extract( shortcode_atts( array(
		'email' => 'your@payson-email.com',
		'currency' => 'SEK',
		'options' => 'Payment for Service 1:15,50|Payment for Service 2:30,00|Payment for Service 3:47,00',
		'return' => site_url(),
		'reference' => __('Reference', 'simple-payson-payment'),
		'other_amount' => '',
		'country_code' => '',
		'payment_subject' => '',
		'button_image' => '',
		'cancel_url' => '',
    'new_window' => '',
    'tax' => '',
	), $args));
	
  $email = apply_filters('wppp_widget_email', $email);
                
	$options = explode( '|' , $options);
	$html_options = '';
	foreach( $options as $option ) {
		$option = explode( ':' , $option );
		$name = esc_attr( $option[0] );
		$price = esc_attr( $option[1] );
		$html_options .= "<option data-product_name='{$name}' value='{$price}'>{$name} - {$price}</option>";
	}
	
	$payment_button_img_src = get_option('payment_button_type');
	if(!empty($button_image)){
		$payment_button_img_src = $button_image;
	}
        
        $window_target = '';
        if(!empty($new_window)){
            $window_target = 'target="_blank"';
        }
	
?>
<div class="wp_payson_button_widget">
	<form name="_xclick" class="wp_accept_pp_button_form" role="form" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post" <?php echo $window_target; ?> >	
		<input type="hidden" name="cmd" value="_xclick">
		<div class="wp_pp_button_selection_section form-group">
			<select class="wp_payson_button_options form-control">
				<?php echo $html_options; ?>
			</select>
		</div>
		
		<?php 
		if(!empty($other_amount)){
			echo '<div class="wp_pp_button_other_amt_section form-group">';
				echo '<label for="other_amount">' . __('Other Amount:', 'simple-payson-payment') . '</label><input type="text" name="other_amount" value="" size="4" class="form-control">';
			echo '</div>';
		}
    // if(!empty($reference)){
    //     echo '<div class="form-group">';
    //     	echo '<label for="wp_pp_button_reference">' . $reference . ':</label>';
    //     	echo '<input type="hidden" name="on0" value="Reference" />';
    //     	echo '<input type="text" name="os0" value="" class="wp_pp_button_reference form-control" />';
    //     echo '</div>';  
    // }
    echo '<div class="form-group">';
    	echo '<label for="wp_pp_button_reference">'. __('Your e-mail', 'simple-payson-payment') . ':</label>';
    	echo '<input type="text" name="senderEmail" value="" class="form-control" /></div>';
    echo '</div>';

		if(!empty($payment_subject)){
		?>
		<input type="hidden" name="on1" value="Payment Subject" />
		<input type="hidden" name="os1" value="<?php echo $payment_subject; ?>" />
		<?php } ?>
		<input type="hidden" id="item_name" name="item_name" value="">
		<input type="hidden" id="amount" name="amount" value="" >
		<!-- <input type="hidden" name="senderEmail" value="" /> -->
		<?php
    if(is_numeric($tax)){
      echo '<input type="hidden" name="tax" value="'.$tax.'" />';
    }                
		if(!empty($cancel_url)){
			echo '<input type="hidden" name="cancel_return" value="'.$cancel_url.'" />';
		}
		if(!empty($country_code)){
			echo '<input type="hidden" name="lc" value="'.$country_code.'" />';
		}
		?>
		<div class="wp_pp_button_submit_btn">
			<input type="image" id="buy_now_button" class="btn pull-left" src="<?php echo $payment_button_img_src; ?>" border="0" name="submit" alt="<?php _e('Make payments with payson - its fast, free and secure!', 'simple-payson-payment'); ?>"><br />
		</div>
	</form>	
	<div class="clearfix"></div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.wp_accept_pp_button_form').submit(function(e){	
		//alert('script is active');
		var form_obj = $(this);
		var options_name = form_obj.find('.wp_payson_button_options :selected').attr('data-product_name');
		form_obj.find('input[name=item_name]').val(options_name);
		$('input#item_name').val(options_name);
		
		var options_val = form_obj.find('.wp_payson_button_options').val();
		var other_amt = form_obj.find('input[name=other_amount]').val();
		if (!isNaN(other_amt) && other_amt.length > 0){
			options_val = other_amt;
		}
		$('input#amount').val(options_val);
		return;
	});
});
</script>

<?php 
}
