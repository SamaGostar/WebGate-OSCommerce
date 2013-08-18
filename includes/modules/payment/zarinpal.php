<?php
/***************************************************************************/
/*  @version $Page: zarinpal.php,v 0.1 2010/04/07  Mostafa Amiri Exp $ 			*/
/*  @package ZARINPAL         				                                            		*/             
/*  @author Mostafa Amiri ( m.amiri[at]samansystems[dot]com )						*/
/*  @copyright (C) 2010 Mostafa Amiri                                                    		*/
/*  @website www.samansystems.com                                                    					*/
/*  @license http://www.gnu.org/copyleft/gpl.html GNU/GPL		              		*/
/*                                                                     								*/
/****************************************************************************/


  class zarinpal {
    var $code, $title, $description, $enabled;

// class constructor
    function zarinpal() {
      global $order;

      $this->code = 'zarinpal';
      $this->title = MODULE_PAYMENT_ZARINPAL_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_ZARINPAL_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_ZARINPAL_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_ZARINPAL_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_ZARINPAL_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_ZARINPAL_ORDER_STATUS_ID;
      }
      
      $this->style_enabled = ((MODULE_PAYMENT_ZARINPAL_STYLE_STATUS == 'True') ? true : false);

      if (is_object($order)) $this->update_status();

      $this->form_action_url = 'https://www.zarinpal.com/pg/StartPay/';
    }

// class methods
    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_ZARINPAL_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_ZARINPAL_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      $selection = array('id' => $this->code,
                         'module' => $this->title);
      return $selection;
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return false;
    }

    function process_button() {
      global $order, $currencies, $customer_id;
            
      require(DIR_WS_CLASSES . 'zarinpal.php');
      
      
      $amount = ($order->info['total']) * $currencies->currencies['IRR']['value'];

      $outclass = new zarinpalout($customer_id, $amount);
      $outclass->set_redirect(tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'));
      
      $output = $outclass->create();
      
      if($output == false) {
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(MODULE_PAYMENT_ZARINPAL_ERROR_MESSAGE), 'SSL'));	
      }
      
      return $output;
    }

    function before_process() {
	  global $_POST, $_GET;
      
      $status = $_GET['Status'];
  $Authority = $_GET['Authority'];
 if ($status == 'OK')  
  {
         $soapclient = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding'=>'UTF-8'));

        $indbreser = tep_db_input($Authority);
       @$res = tep_db_query("SELECT orderid, amount FROM " .ZARINPAL_TABLE_RESERVATION. " WHERE res_number='$indbreser'");
      if(@tep_db_num_rows($res) == 1) {
          $row = tep_db_fetch_array($res);
          $amount = $row['amount'];
          $orderid = $row['orderid'];
		  $returnvalue=True;
   
			}
			        } else {
          $amount = 0;
          $orderid = 0;
		  $returnvalue=false;
   		  $output .=  $err . "Error not in database";
		  }
      
    
      $res = $this->$client->PaymentVerification(
						  	array(
									'MerchantID'	 => MODULE_PAYMENT_ZARINPAL_MID,
									'Authority' 	 => $Authority,
									'Amount'	 => $amount/10
								)
		);
	  if ($res->Status == 100)
        @$res = tep_db_query("SELECT id, used FROM " .ZARINPAL_TABLE_ERECEIPT. " WHERE refer_number='" .$Authority. "'");
        @$numrow = tep_db_num_rows($res->Status);
        if ($numrow == 0) {
          @tep_db_query("INSERT INTO " .ZARINPAL_TABLE_ERECEIPT. " VALUES ('','" .$orderid. "','" .$amount. "','" .$Authority. "','1','0','1')");
          $returnvalue = True;
        }
		elseif ($numrow == 1) {
          $row = tep_db_fetch_array($res->Status);
          if ($row['used'] == 0) {
            $rowid = $row['id'];
            @tep_db_query("UPDATE " .ZARINPAL_TABLE_ERECEIPT. " SET used='1' WHERE id='$rowid'");
            $returnvalue = True;
          } else {
		  			$output .=  $err . "Used";
            $returnvalue = False;
          }
        } else {
					$output .=  $err . "Used";
          $returnvalue = False;
        }
      } 
	  else {
	    $output .=  $err . MODULE_PAYMENT_ZARINPAL_MID . "status Error:" . $status;
        $returnvalue = False;
      }

	if($returnvalue== True)
		{
		echo "Payment Ok";
		echo $authurity;
		}
  else {

	   // this is a UNsucccessfull payment
	   // we update our DataBase
	   $output .=  $authority . "***" . $status . "---". "Couldn't Validate Payment with Parsian "  ;
	   tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($output), 'SSL'));

	  }
 
    }

    function after_process() {
		return false;
    }

    function get_error() {
      return false;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_ZARINPAL_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('فعال کردن پرداخت آنلاين زرين پال', 'MODULE_PAYMENT_ZARINPAL_STATUS', 'True', 'آیا شما تمایل به دریافت مبلغ سفارش از طریق زرين پال را دارید؟', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_ZARINPAL_MID', '00000000-0000', 'Merchant ID شما که از طرف زرين پال دریافت کرده‌اید. وارد کردن این مورد برای استفاده از این روش پرداخت الزامی است.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('مرتبه طبقه‌بندی برای نمایش.', 'MODULE_PAYMENT_ZARINPAL_SORT_ORDER', '0', 'مرتبه طبقه‌بندی برای نمایش دادن. مقادیر کمتر بالاتر نمایش داده می‌شوند.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('منطقه پرداخت', 'MODULE_PAYMENT_ZARINPAL_ZONE', '0', 'اگر منطقه‌ای انتخاب شود این روش پرداخت فقط برای آن منطقه فعال خواهد بود.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('تنظیم وضعیت نمایش', 'MODULE_PAYMENT_ZARINPAL_ORDER_STATUS_ID', '0', 'سفارشاتی که با این روش پرداخت می‌شوند روی این مقدار تنظیم شود', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_ZARINPAL_STATUS', 'MODULE_PAYMENT_ZARINPAL_MID', 'MODULE_PAYMENT_ZARINPAL_MPASS', 'MODULE_PAYMENT_ZARINPAL_SORT_ORDER', 'MODULE_PAYMENT_ZARINPAL_ZONE', 'MODULE_PAYMENT_ZARINPAL_ORDER_STATUS_ID');
    }
  }
?>
