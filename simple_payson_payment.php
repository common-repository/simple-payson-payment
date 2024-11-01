<?php
/*
Plugin Name: Simple Payson Payment
Version: v1.1
Text Domain: simple-payson-payment
Plugin URI: http://www.popolo.se
Author: Leander Lindahl, Popolo
Author URI: http://www.popolo.se/
Description: Easy to use Wordpress plugin to accept Payson payment for a service, product or donation in one click. Can be used in the sidebar, posts and pages.
License: GPL2
*/

define('SIMPLE_PAYSON_PAYMENT_PLUGIN_VERSION', '1.0');
define('SIMPLE_PAYSON_PAYMENT_PLUGIN_URL', plugins_url('',__FILE__));

include_once('shortcode_view.php');

function simple_payson_payment_plugin_install ()
{
	// Some default options
  add_option('payson_receiverEmail', get_bloginfo('admin_email'));

  add_option('payson_payment_currency', 'SEK');

  add_option('payson_payment_subject', 'Plugin Service Payment');

  add_option('payson_payment_item1', 'Basic Service - SEK 100');
  add_option('payson_payment_value1', '10');
  add_option('payson_payment_item2', __('Gold Service - SEK 200', 'simple_payson_payment'));
  add_option('payson_payment_value2', '20');
  add_option('payson_payment_item3', __('Platinum Service - SEK 300', 'simple_payson_payment'));
  add_option('payson_payment_value3', '30');

  add_option('simple_payson_payment_widget_title_name', __('Simple Payson Payment', 'simple_payson_payment'));

  add_option('payment_button_type', 'https://www.payson.com/en_US/i/btn/btn_paynowCC_LG.gif');

  add_option('simple_payson_payment_show_other_amount', '-1');

  add_option('simple_payson_payment_show_ref_box', '1');      
  add_option('simple_payson_payment_ref_title', __('Payment reference', 'simple_payson_payment'));

  add_option('payson_returnURL', home_url());
  add_option('payson_cancelURL', home_url());
}
register_activation_hook(__FILE__,'simple_payson_payment_plugin_install');


add_shortcode('simple_payson_payment_box_for_any_amount', 'spp_buy_now_any_amt_handler');
function spp_buy_now_any_amt_handler($args)
{
	$output = spp_render_payson_button_with_other_amt($args);
	return $output;
}

add_shortcode('simple_payson_payment_box', 'spp_buy_now_button_shortcode' );
function spp_buy_now_button_shortcode($args) 
{
	ob_start();
    spp_render_payson_button_form($args);
	$output = ob_get_contents();
    ob_end_clean();
    return $output;
} 

add_action( 'init', 'spp_shortcode_plugin_enqueue_jquery' );
function spp_shortcode_plugin_enqueue_jquery() {
	wp_enqueue_script('jquery');
}

/**
 * This is the form that is presented to the user 
 */
function payson_payment_accept()
{
  $payson_email = get_option('payson_receiverEmail');
  $payment_currency = get_option('payson_payment_currency');
  $payson_subject = get_option('payson_payment_subject');

  $itemName1 = get_option('payson_payment_item1');
  $value1 = get_option('payson_payment_value1');
  $itemName2 = get_option('payson_payment_item2');
  $value2 = get_option('payson_payment_value2');
  $itemName3 = get_option('payson_payment_item3');
  $value3 = get_option('payson_payment_value3');
  $itemName4 = get_option('payson_payment_item4');
  $value4 = get_option('payson_payment_value4');
  $itemName5 = get_option('payson_payment_item5');
  $value5 = get_option('payson_payment_value5');
  $itemName6 = get_option('payson_payment_item6');
  $value6 = get_option('payson_payment_value6');

  $payment_button = get_option('payment_button_type');
  $simple_payson_payment_show_other_amount = get_option('simple_payson_payment_show_other_amount');
  $simple_payson_payment_show_ref_box = get_option('simple_payson_payment_show_ref_box');
  $simple_payson_payment_ref_title = get_option('simple_payson_payment_ref_title');
  $payson_returnURL = get_option('payson_returnURL');

    /* === Payson form === */
    $output = '';
    $output .= '<div id="accept_payson_payment_form">';
    $output .= '
        <form action="' . $_SERVER["REQUEST_URI"] . '" method="post">
        <input type="hidden" name="cmd" value="payson_transaction" />
    ';
    // Payson receiver $payson_email
    $output .= "<input type=\"hidden\" name=\"business\" value=\"$payson_email\" />";

    $output .= "<input type=\"hidden\" name=\"item_name\" value=\"$payson_subject\" />";
    $output .= "<input type=\"hidden\" name=\"currency_code\" value=\"$payment_currency\" />";

    $output .= "<span><strong>$payson_subject</strong></span><br /><br />";
    $output .= '<div class="form-group">';
    $output .= '<select id="amount" name="amount" class="form-control">';
    $output .= "<option value=\"1\">$itemName1</option>";
    if($value2 != 0)
    {
        $output .= "<option value=\"2\">$itemName2</option>";
    }
    if($value3 != 0)
    {
        $output .= "<option value=\"3\">$itemName3</option>";
    }
    if($value4 != 0)
    {
        $output .= "<option value=\"4\">$itemName4</option>";
    }
    if($value5 != 0)
    {
        $output .= "<option value=\"5\">$itemName5</option>";
    }
    if($value6 != 0)
    {
        $output .= "<option value=\"6\">$itemName6</option>";
    }

    $output .= '</select></div>';

	// Show other amount text box
	if ($simple_payson_payment_show_other_amount == '1')
	{
    	$output .= '<br /><br /><strong>' . __('Other Amount:', 'simple-payson-payment') . '</strong>';
    	$output .= '<br /><br /><input type="text" name="amount" size="10" title="Other donate" value="" />';
	}
	
	// Show the reference text box
	if ($simple_payson_payment_show_ref_box == '1')
	{
		$output .= "";
    $output .= '<div class="form-group"><input type="hidden" name="on0" value="Reference" />';
    $output .= '<strong>' . $simple_payson_payment_ref_title . '</strong><br />';
    $output .= '<br /><label for="senderEmail">' . __('E-mail', 'simple-payson-payment') . '</label><input type="text" class="form-control" name="senderEmail" maxlength="60" />';
    $output .= '<br /><label for="senderFirstname">' . __('First name', 'simple-payson-payment') . '</label><input type="text" class="form-control" name="senderFirstname" maxlength="60" />';
    $output .= '<br /><label for="senderLastname">' . __('Last name', 'simple-payson-payment') . '</label><input type="text" class="form-control" name="senderLastname" maxlength="60" />';  
    $output .= '</div>';
	}
    
    $output .= '
        <br /><br />
        <input type="hidden" name="no_shipping" value="0" />
        <input type="hidden" name="no_note" value="1" />
        <input type="hidden" name="mrb" value="3FWGC6LFTMTUG" />
        <input type="hidden" name="bn" value="IC_Sample" />
    ';
    if (!empty($payson_returnURL)) 
    {
		$output .= '<input type="hidden" name="return" value="'.$payson_returnURL.'" />';
	} 
	else 
	{
		$output .='<input type="hidden" name="return" value="'. home_url() .'" />';
	}
		
    $output .= "<input type=\"image\" src=\"$payment_button\" name=\"submit\" alt=\"Make payments with Payson\" />";
    $output .= '</form>';
    $output .= '</div>';
    /* = end of payson form = */
    return $output;
}

function spp_process($content)
{
    if (strpos($content, "<!-- simple_payson_payment -->") !== FALSE)
    {
        $content = preg_replace('/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content);
        $content = str_replace('<!-- simple_payson_payment -->', payson_payment_accept(), $content);
    }
    return $content;
}

// Displays payson Payment Accept Options menu
function simple_payson_payment_add_options_page() {
  if (function_exists('add_options_page')) {
      add_options_page('Simple Payson Payment', 'Simple Payson Payment', 'manage_options', __FILE__, 'payson_payment_options_page');
  }
}

function payson_payment_options_page() {
  if (isset($_POST['info_update']))
  {
    echo '<div id="message" class="updated fade"><p><strong>';
    update_option('simple_payson_payment_widget_title_name', (string)$_POST["simple_payson_payment_widget_title_name"]);

    // Account details of the receiver of money
    update_option('payson_receiverEmail', (string)$_POST["payson_receiverEmail"]);

    // Payson agent ID and md5 key
    update_option('payson_agentID', (string)$_POST["payson_agentID"]);
    update_option('payson_md5Key', (string)$_POST["payson_md5Key"]);

    //update_option('payson_payment_currency', (string)$_POST["payson_payment_currency"]);
    update_option('payson_payment_currency', (string)"SEK");
    
    update_option('payson_payment_subject', (string)$_POST["payson_payment_subject"]);
    
    update_option('payson_payment_item1', (string)$_POST["payson_payment_item1"]);
    update_option('payson_payment_value1', (double)$_POST["payson_payment_value1"]);
    update_option('payson_payment_item2', (string)$_POST["payson_payment_item2"]);
    update_option('payson_payment_value2', (double)$_POST["payson_payment_value2"]);
    update_option('payson_payment_item3', (string)$_POST["payson_payment_item3"]);
    update_option('payson_payment_value3', (double)$_POST["payson_payment_value3"]);
    update_option('payson_payment_item4', (string)$_POST["payson_payment_item4"]);
    update_option('payson_payment_value4', (double)$_POST["payson_payment_value4"]);
    update_option('payson_payment_item5', (string)$_POST["payson_payment_item5"]);
    update_option('payson_payment_value5', (double)$_POST["payson_payment_value5"]);
    update_option('payson_payment_item6', (string)$_POST["payson_payment_item6"]);
    update_option('payson_payment_value6', (double)$_POST["payson_payment_value6"]);
    
    update_option('payment_button_type', (string)$_POST["payment_button_type"]);
    
    update_option('simple_payson_payment_show_other_amount', ($_POST['simple_payson_payment_show_other_amount']=='1') ? '1':'-1' );
    
    update_option('simple_payson_payment_show_ref_box', ($_POST['simple_payson_payment_show_ref_box']=='1') ? '1':'-1' );        
    update_option('simple_payson_payment_ref_title', (string)$_POST["simple_payson_payment_ref_title"]); 
    
    update_option('payson_returnURL', (string)$_POST["payson_returnURL"]);
    update_option('payson_cancelURL', (string)$_POST["payson_cancelURL"]);       
            
    echo 'Options Updated!';
    echo '</strong></p></div>';
  }

  $payson_payment_currency = stripslashes(get_option('payson_payment_currency'));
  $payment_button_type = stripslashes(get_option('payment_button_type'));

  ?>

<div class="wrap">
<h2><?php _e('Simple Payson Payment Settings v', 'simple-payson-payment'); ?> <?php echo SIMPLE_PAYSON_PAYMENT_PLUGIN_VERSION; ?></h2>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
  <input type="hidden" name="info_update" id="info_update" value="true" />
  <fieldset class="options">
  <h3><?php _e('How to use the plugin', 'simple-payson-payment'); ?></h3><?php /* <p><?php _e('There are a few ways you can use this plugin:', 'simple-payson-payment'); ?></p> */ ?>
    <ul>
      <li><?php _e('Add the shortcode', 'simple-payson-payment'); ?> <strong>[simple_payson_payment]</strong> <?php _e('to a post or page', 'simple-payson-payment'); ?></li><?php /* <li><?php _e('Call the function from a template file:', 'simple-payson-payment'); ?> <strong>&lt;?php echo payson_payment_accept(); ?&gt;</strong></li> */ ?>
    <li><?php _e('Use the <strong>Simple Payson Payment</strong> Widget from the Widgets menu', 'simple-payson-payment'); ?></li>
    <?php /* <li><?php _e('Use the shortcode with custom parameter option to add multiple different payment widgets in different areas of the site.', 'simple-payson-payment'); ?>
    <a href="http://www.popolo.se/simple-payson-payment#shortcode_with_custom_parameters" target="_blank"><?php _e('View documentation', 'simple-payson-payment'); ?></a></li> */ ?>
    </ul>
  </fieldset>
  <fieldset class="options second">
    <h3><?php _e('Plugin Options', 'simple-payson-payment'); ?></h3><strong><?php _e('Widget Title', 'simple-payson-payment'); ?> :</strong>
        <input name="simple_payson_payment_widget_title_name" type="text" size="30" value="<?php echo get_option('simple_payson_payment_widget_title_name'); ?>"/>
        <br /><i><?php _e('This will be the title of the Widget on the Sidebar if you use it.', 'simple-payson-payment'); ?></i>
     <table class="widefat">
     <tbody>
      <tr>
        <td width="25%" align="right">
          <strong><?php _e('Payson Email address:', 'simple-payson-payment'); ?></strong>
        </td>
        <td align="left">
          <input name="payson_receiverEmail" type="text" size="35" value="<?php echo get_option('payson_receiverEmail'); ?>"/><br />
          <i><?php _e('This is the payson Email address where the payments will go', 'simple-payson-payment'); ?></i><br /><br />
        </td>
      </tr>

      <tr valign="top">
        <td width="25%" align="right">
          <strong><?php _e('Payson agentID:', 'simple-payson-payment'); ?></strong>
        </td>
        <td align="left">
          <input name="payson_agentID" type="text" size="35" value="<?php echo get_option('payson_agentID'); ?>"/><br />
          <i><?php _e('Provide your Payson agentID', 'simple-payson-payment'); ?></i><br /><br />
        </td>
      </tr>

      <tr valign="top">
        <td width="25%" align="right">
          <strong><?php _e('Payson md5Key:', 'simple-payson-payment'); ?></strong>
        </td>
        <td align="left">
          <input name="payson_md5Key" type="text" size="35" value="<?php echo get_option('payson_md5Key'); ?>"/><br />
          <i><?php _e('Provide your Payson md5key', 'simple-payson-payment'); ?></i><br /><br />
        </td>
      </tr>
      <?php /* 
      <tr valign="top">
        <td width="25%" align="right">
          <strong><?php _e('Choose Payment Currency', 'simple-payson-payment'); ?> : </strong>
        </td>
        <td align="left">
          <select id="payson_payment_currency" name="payson_payment_currency">
          <?php echo '<option value="USD"'; ?><?php if ($payson_payment_currency == "USD") echo " selected " ?><?php echo '>' . __('US Dollar', 'simple-payson-payment') . '</option>'; ?>
          <?php echo'<option value="GBP"'; ?><?php if ($payson_payment_currency == "GBP") echo " selected " ?><?php echo '>' . __('Pound Sterling', 'simple-payson-payment') . '</option>'; ?>
          <?php echo '<option value="EUR"';  ?><?php if ($payson_payment_currency == "EUR") echo " selected " ?><?php echo '>' . __('Euro', 'simple-payson-payment') . '</option>'; ?>
          <?php echo '<option value="SEK"'; ?><?php if ($payson_payment_currency == "SEK") echo " selected " ?><?php echo '>' . __('Swedish krona', 'simple-payson-payment') . '</option>'; ?>
          </select><br />
          <i><?php _e('This is the currency for your visitors to make Payments or Donations in.', 'simple-payson-payment'); ?></i><br /><br />
        </td>
      </tr>
      */ ?>
      <tr valign="top">
        <td width="25%" align="right">
          <strong><?php _e('Payment Subject', 'simple-payson-payment'); ?> :</strong>
        </td>
        <td align="left">
          <input name="payson_payment_subject" type="text" size="35" value="<?php echo get_option('payson_payment_subject'); ?>"/>
          <br /><i><?php _e('Enter the Product or service name or the reason for the payment here. The visitors will see this text', 'simple-payson-payment'); ?></i><br /><br />
        </td>
      </tr>

      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Payment Option', 'simple-payson-payment'); ?> 1 :</strong>
      </td><td align="left">
      <input name="payson_payment_item1" type="text" size="25" value="<?php echo get_option('payson_payment_item1'); ?>"/>
      <strong><?php _e('Price', 'simple-payson-payment'); ?> :</strong>
      <input name="payson_payment_value1" type="text" size="10" value="<?php echo get_option('payson_payment_value1'); ?>"/>
      <br />
      </td></tr>

      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Payment Option', 'simple-payson-payment'); ?> 2 :</strong>
      </td><td align="left">
      <input name="payson_payment_item2" type="text" size="25" value="<?php echo get_option('payson_payment_item2'); ?>"/>
      <strong><?php _e('Price', 'simple-payson-payment'); ?> :</strong>
      <input name="payson_payment_value2" type="text" size="10" value="<?php echo get_option('payson_payment_value2'); ?>"/>
      <br />
      </td></tr>

      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Payment Option', 'simple-payson-payment'); ?> 3 :</strong>
      </td><td align="left">
      <input name="payson_payment_item3" type="text" size="25" value="<?php echo get_option('payson_payment_item3'); ?>"/>
      <strong><?php _e('Price', 'simple-payson-payment'); ?> :</strong>
      <input name="payson_payment_value3" type="text" size="10" value="<?php echo get_option('payson_payment_value3'); ?>"/>
      <br />
      </td></tr>

      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Payment Option', 'simple-payson-payment'); ?> 4 :</strong>
      </td><td align="left">
      <input name="payson_payment_item4" type="text" size="25" value="<?php echo get_option('payson_payment_item4'); ?>"/>
      <strong><?php _e('Price', 'simple-payson-payment'); ?> :</strong>
      <input name="payson_payment_value4" type="text" size="10" value="<?php echo get_option('payson_payment_value4'); ?>"/>
      <br />
      </td></tr>

      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Payment Option', 'simple-payson-payment'); ?> 5 :</strong>
      </td><td align="left">
      <input name="payson_payment_item5" type="text" size="25" value="<?php echo get_option('payson_payment_item5'); ?>"/>
      <strong><?php _e('Price', 'simple-payson-payment'); ?> :</strong>
      <input name="payson_payment_value5" type="text" size="10" value="<?php echo get_option('payson_payment_value5'); ?>"/>
      <br />
      </td></tr>

      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Payment Option', 'simple-payson-payment'); ?> 6 :</strong>
      </td><td align="left">
      <input name="payson_payment_item6" type="text" size="25" value="<?php echo get_option('payson_payment_item6'); ?>"/>
      <strong><?php _e('Price', 'simple-payson-payment'); ?> :</strong>
      <input name="payson_payment_value6" type="text" size="10" value="<?php echo get_option('payson_payment_value6'); ?>"/>
      <br /><i><?php _e('Enter the name of the service or product and the price. eg. Enter "Basic service - $10" in the Payment Option text box and "10.00" in the price text box to accept a payment of $10 for "Basic service". Leave the Payment Option and Price fields empty if u don\'t want to use that option. For example, if you have 3 price options then fill in the top 3 and leave the rest empty.', 'simple-payson-payment'); ?></i>
      </td></tr>
      
      <br /><br />
      <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Show Other Amount', 'simple-payson-payment'); ?> :</strong>
      </td><td align="left">
      <input name="simple_payson_payment_show_other_amount" type="checkbox" <?php if(get_option('simple_payson_payment_show_other_amount')!='-1') echo ' checked="checked"'; ?> value="1"/>
    <i> <?php _e('Tick this checkbox if you want to show other amount text box to your visitors so they can enter custom amount.', 'simple-payson-payment'); ?></i>
    </td></tr>

    <br />
    <tr valign="top">
      <td width="25%" align="right">
        <strong><?php _e('Show Reference Text Box', 'simple-payson-payment'); ?>:</strong>
      </td>
      <td align="left">
        <input name="simple_payson_payment_show_ref_box" type="checkbox"<?php if(get_option('simple_payson_payment_show_ref_box')!='-1') echo ' checked="checked"'; ?> value="1"/><i><?php _e('Tick this checkbox if you want your visitors to be able to enter a reference text like email or web address.', 'simple-payson-payment'); ?></i>
      </td>
    </tr>

    <tr valign="top"><td width="25%" align="right">
      <strong><?php _e('Reference Text Box Title', 'simple-payson-payment'); ?> :</strong>
      </td><td align="left">
      <input name="simple_payson_payment_ref_title" type="text" size="35" value="<?php echo get_option('simple_payson_payment_ref_title'); ?>"/>
      <br /><i><?php _e('Enter a title for the Reference text box (ie. Your Information). The visitors will see this text', 'simple-payson-payment'); ?></i><br />
      </td>
    </tr>
  
  <tr>
    <td colspan="3">
  <br /><br />
  <strong><?php _e('Choose a Submit Button Type', 'simple-payson-payment'); ?> :</strong>
  <br /><i><?php _e('This is the button the visitors will click on to make Payments or Donations.', 'simple-payson-payment'); ?></i><br />
  <table style="border-spacing:0; padding:0; text-align:center;" class="">
    <tr>
      <td>
        <input type="radio" name="payment_button_type" value="<?php echo SIMPLE_PAYSON_PAYMENT_PLUGIN_URL; ?>/assets/images/payson180x78.png" <?php 
          if ($payment_button_type == SIMPLE_PAYSON_PAYMENT_PLUGIN_URL . '/assets/images/payson180x78.png') echo " checked "; ?> />
      </td>
      <td>
        <input type="radio" name="payment_button_type" value="<?php echo SIMPLE_PAYSON_PAYMENT_PLUGIN_URL; ?>/assets/images/payson145x42.png" <?php 
          if ($payment_button_type == SIMPLE_PAYSON_PAYMENT_PLUGIN_URL . '/assets/images/payson145x42.png') echo " checked "; ?> />
      </td>
    </tr>
    <tr>
      <td><img border="0" src="<?php echo SIMPLE_PAYSON_PAYMENT_PLUGIN_URL; ?>/assets/images/payson180x78.png" alt="Payson Button" /></td>
      <td><img border="0" src="<?php echo SIMPLE_PAYSON_PAYMENT_PLUGIN_URL; ?>/assets/images/payson145x42.png" alt="Payson Button" /></td>
    </tr>
  </table>

  <br />
  <strong><?php _e('Return URL from payson', 'simple-payson-payment'); ?>:</strong>
  <input name="payson_returnURL" type="text" size="60" value="<?php echo get_option('payson_returnURL'); ?>"/>
  <br /><i><?php _e('Enter a return URL (could be a Thank You page). Payson will redirect visitors to this page after Payment', 'simple_payson_payment'); ?></i><br />
  <strong><?php _e('Cancel URL from payson', 'simple-payson-payment'); ?>:</strong>
  <input name="payson_cancelURL" type="text" size="60" value="<?php echo get_option('payson_cancelURL'); ?>"/>
  <br /><i><?php _e('Enter a cancel URL. Payson will redirect visitors to this page if the cancel the payment process', 'simple_payson_payment'); ?></i><br />

  </fieldset>
</td></tr>
  </tbody>
  </table>
  <div class="submit">
      <input type="submit" class="button-primary" name="info_update" value="<?php _e('Update options', 'simple-payson-payment'); ?> &raquo;" />
  </div>
  </form>

  <div style="background: none repeat scroll 0 0 #ECECEC;border: 1px solid #CFCFCF;color: #363636;margin: 10px 0 15px;padding:15px;text-shadow: 1px 1px #FFFFFF;">
    <?php _e('For usage documentation and updates, please visit the plugin page at the following URL:', 'simple-payson-payment'); ?><br />
    <a href="http://www.popolo.se/simple-payson-payment" target="_blank">http://www.popolo.se/simple-payson-payment</a>
  </div>
  
  </div><!-- end of .wrap -->
  <?php
}

function show_simple_payson_payment_widget($args)
{
	extract($args);
	
    $simple_payson_payment_widget_title_name_value = get_option('simple_payson_payment_widget_title_name');
    echo $before_widget;
    echo $before_title . $simple_payson_payment_widget_title_name_value . $after_title;
    echo payson_payment_accept();
    echo $after_widget;
}

function simple_payson_payment_widget_control()
{
?>
<p><? _e('Set the Plugin Settings from the Settings menu', 'simple-payson-payment'); ?></p>
<?php
}

function simple_payson_payment_init()
{
  /**
   * Function to process transaction when payment form was posted
   */
  if(isset($_POST['cmd']) && $_POST['cmd'] == 'payson_transaction'){
      simple_payson_payment_transaction();  
  }
  if(isset($_POST['cmd']) && $_POST['cmd'] == '_xclick'){
      simple_payson_payment_transaction();  
  }

  load_plugin_textdomain( 'simple-payson-payment', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

  wp_register_style('wpapp-styles', SIMPLE_PAYSON_PAYMENT_PLUGIN_URL.'/css/spp-styles.css');
  wp_enqueue_style('wpapp-styles');
      	
  //Widget code
  $widget_options = array('classname' => 'widget_simple_payson_payment', 'description' => __( "Simple Payson Payment.") );
  wp_register_sidebar_widget('simple_payson_payment_widgets', __('Simple Payson Payment'), 'show_simple_payson_payment_widget', $widget_options);
  wp_register_widget_control('simple_payson_payment_widgets', __('Simple Payson Payment'), 'simple_payson_payment_widget_control' );
}

add_filter('the_content', 'spp_process');

add_shortcode('simple_payson_payment', 'payson_payment_accept');
if (!is_admin()) {
  add_filter('widget_text', 'do_shortcode');
}

add_action('init', 'simple_payson_payment_init');

// Insert the simple_payson_payment_add_options_page in the 'admin_menu'
add_action('admin_menu', 'simple_payson_payment_add_options_page');
