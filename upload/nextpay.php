<?php
	include_once dirname(__FILE__).'/includes/paymentapi/include/nextpay_payment.php';
	
	$parameters = array
		    (
		    	"api_key"=>$_POST['nx_api_key'],
			"order_id"=> '0',
			"amount"=> $_POST['nx_amount'],
			"callback_uri"=> $_POST['nx_callback_url']
			);

	$nextpay = new Nextpay_Payment($parameters);
	//$nextpay->setDefaultVerify(Type_Verify::Http);
	$result = $nextpay->token();
	if(intval($result->code) == -1){
		Header('Location: http://api.nextpay.org/gateway/payment/' . $result->trans_id);
	} else {
        echo 'خطا در دریافت توکن تراکنش : ' . $result->code ;
		echo '<br>'.$nextpay->code_error(intval($result->code));
	}
	
?>
