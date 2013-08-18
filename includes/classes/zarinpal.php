     <?php

/********************************************************************************/
/*  @version $Page: zarinpal.php,v 0.1 2010/04/07  Mostafa Amiri Exp $ 			*/
/*  @package ZARINPAL         				                                            		*/             
/*  @author Mostafa Amiri ( m.amiri[at]samansystems[dot]com )						*/
/*  @copyright (C) 2010 Mostafa Amiri                                                    		*/
/*  @website www.samansystems.com                                                    					*/
/*  @license http://www.gnu.org/copyleft/gpl.html GNU/GPL		              		*/
/*                                                                     								*/
/****************************************************************************/


//define required variables
define("_INCLUDE_DIR", "includes/classes/lib/");           //Path of required file for include (with full slash)
define("MERCHANT_ID", MODULE_PAYMENT_ZARINPAL_MID);            //Merchant ID for settlement money.You must get one from Saman Bank Network
define("ZARINPAL_TABLE_RESERVATION", TABLE_ZARINPAL_RESERVATION);  //Name of table in mysql database for store reservation data
define("ZARINPAL_TABLE_ERECEIPT", TABLE_ZARINPAL_ERECEIPT);        //Name of table in mysql database for store electronic receipt data
define("ZARINPAL_TABLE_RETURNLOG", TABLE_ZARINPAL_RETURNLOG);      //Name of table in mysql database for store all money return details


class zarinpalout {

  var $amount = 0;                       //Amount of money for transaction
  var $reservation = '';                 //A security number for access user
  var $merchantID = MERCHANT_ID;         //Your Merchant ID of Saman bank
  var $redirect = '';                    //User redirect to this URL after compietion transaction
  var $orderid;                          //ID of order for wist user and order after user redirect
  var $error_ok = False;
  var $errorstr = array();
  var $error_save_handele = 0;
  
  function zarinpalout($oid, $amo) {
    if ($amo <= 0) {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: Amount cannot be eaqual to or less than zero. ";
      $this->save_error();
    } else {
      $this->amount = $amo;
    }
    if ($oid <= 0) {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: You must enter a valid value for orderid of this payment.";
      $this->save_error();
    } else {
      $this->orderid = $oid;
      $this->orderid = $this->create_reservation();
    }

  }
  
  function set_redirect($url) {
    if (!eregi("^(http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]", $url)) {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: Invalid redirect URL.";
      $this->save_error();
    } else {
      $this->redirect = $url;
    }
  }
  
  function create() {

    if(trim($this->merchantID) == '') {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: you must enter a mechant id for post to transaction server.";
    }
    if(trim($this->redirect) == '') {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: You must enter a redirect URL for redirect user to your site after compietion transaction";
    }
    if(intval($this->amount) <= 0) {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: Amount cannot be eaqual to or less than zero. ";
    }
    if(intval($this->orderid) <= 0) {
      $this->error_ok = True;
      $this->errorstr[] = "Payment error: You must enter a valid value for orderid of this payment.";
    }
    if($this->error_ok == True) {
      $this->save_error();
      return False;
    }

      $soapclient = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding'=>'UTF-8'));
  
  
  if ( $soapclient ) {
    $this->orderid=generatePassword(5,false,false,true,false);

$res = $this->client->PaymentRequest(
						array(
								'MerchantID' 	=> $this->merchantID,
								'Amount' 		=> intval($this->amount)/10,
								'Description' 	=> 'افزايش اعتبار کاربر: '.$data['user'].'پرداخت صورتحساب: '.$this->orderid,
								'Email' 		=> $Email,
								'Mobile' 		=> $Mobile,
								'CallbackURL' 	=> $this->redirect
							)
	);

	
    if ( $res->Status == 100 )  {
		$output .= tep_draw_hidden_field('au', $res->Authority);
		if(!@tep_db_query("INSERT INTO " .ZARINPAL_TABLE_RESERVATION. " VALUES('','" .$this->orderid. "','" .$this->amount. "','" .$res->Authority. "')")) {
		  $this->error_ok = True;
		  $this->errorstr[] = "MySQL error: you can't insert data to database.";
		  $this->save_error();
		  return False;
		}
    } else {
      
    	$output .= "OrderID" . $params[orderId] . "<br>***Status:". $status . "--Au:" . $res->Authority ."--"."Couldn't Validate Payment with Parsian<br> " . $this->errorstr[1] ;

    }
  }
    return $output;
  }
  
  function create_reservation() {
    $this->reservation = generatePassword(10,false,false,true,false);
    if(tep_db_query("SELECT * FROM " .ZARINPAL_TABLE_RESERVATION)) {
      while(True) {
        $randreser = generatePassword(10,false,false,true,false);
        if(!tep_db_num_rows(tep_db_query("SELECT id FROM " .ZARINPAL_TABLE_RESERVATION. " WHERE res_number='$randreser'"))) {
          break;
        }
      }
      $this->reservation = $randreser;
      return True;
    } else {
      $this->error_ok = True;
      $this->errorstr[] = "MySQL error: reservation table not found in database.";
    }
    $this->save_error();
    return False;
  }
  
  function save_error() {
    if ($this->error_ok == True) {
      for ($i=$this->error_save_handele;$i<sizeof($this->errorstr);$i++) {
        error_log($this->errorstr[$i]."\n",3,"includes/error.log");
      }
      $this->error_save_handele = $i;
    } else {
      $this->error_save_handele = 0;
    }
  }
  
}

//Random Password Generator  v1.0
//special tanks from sebflipper (http://www.sebflipper.com)
function generatePassword($plength,$include_letters,$include_capitals,$include_numbers,$include_punctuation) {

  $pwd = '';
  // First we need to validate the argument that was given to this function
  // If need be, we will change it to a more appropriate value.
  if(!is_numeric($plength) || $plength <= 0) {
    $plength = 8;
  }
  if($plength > 32) {
    $plength = 32;
  }

  // This is the array of allowable characters.
  $chars = "";

  if ($include_letters == true) { $chars .= 'abcdefghijklmnopqrstuvwxyz'; }
  if ($include_capitals == true) { $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; }
  if ($include_numbers == true) { $chars .= '0123456789'; }
  if ($include_punctuation == true) { $chars .= '`??$%^&*()-_=+[{]};:@#~,<.>/?'; }

  // If nothing selected just display 0's
  if ($include_letters == false AND $include_capitals == false AND $include_numbers == false AND $include_punctuation == false) {
  $chars .= '0';
  }

  // This is important:  we need to seed the random number generator
  mt_srand(microtime() * 1000000);

  // Now we simply generate a random string based on the length that was
  // requested in the function argument
  for($i = 0; $i < $plength; $i++) {
    $key = mt_rand(0,strlen($chars)-1);
    $pwd = $pwd . $chars{$key};
  }

  // Finally to make it a bit more random, we switch some characters around
  for($i = 0; $i < $plength; $i++) {
    $key1 = mt_rand(0,strlen($pwd)-1);
    $key2 = mt_rand(0,strlen($pwd)-1);

    $tmp = $pwd{$key1};
    $pwd{$key1} = $pwd{$key2};
    $pwd{$key2} = $tmp;
  }

  // Convert into HTML
  $pwd = htmlentities($pwd, ENT_QUOTES);

  return $pwd;
}

//Close return action
function notreturn($refer = '') {
  if(trim($refer) != '' AND strlen($refer) == 20) {
    $refer = tep_db_input($refer);
    @$res = tep_db_query("SELECT id,return FROM " .ZARINPAL_TABLE_ERECEIPT. " WHERE refer_number='$refer'");
    if (@tep_db_num_rows($res) == 1) {
      $row = tep_db_fetch_array($res);
      if ($row['return'] == 0) {
        $rowid = $row['id'];
        tep_db_query("UPDATE " .ZARINPAL_TABLE_ERECEIPT. " SET return='1' WHERE id='$rowid'");
        $returnvalue = True;
      } else {
        $returnvalue = False;
      }
    } else {
      $returnvalue = False;
    }
  } else {
    $returnvalue = False;
  }
  return $returnvalue;
}

?>