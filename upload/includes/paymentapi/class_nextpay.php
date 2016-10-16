<?php

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @version	$Revision: 20000 $
* @date		$Date: 2012-03-25 01:24:45 +0350 (Sun, 25 March 2012) $
*/
class vB_PaidSubscriptionMethod_nextpay extends vB_PaidSubscriptionMethod
{
	var $supports_recurring = false;	 
	var $display_feedback = true;

	function verify_payment()
	{		
		$this->registry->input->clean_array_gpc('r', array(
			'item'		=> TYPE_STR,			
			'trans_id'	=> TYPE_STR,
			'order_id'	=> TYPE_STR
		));  
		
		if (!class_exists('SoapClient'))
		{
			$this->error = 'SOAP is not installed';
			return false;
		}
		
		if (!$this->test())
		{
			$this->error = 'Payment processor not configured';
			return false;
		}
		
		$this->transaction_id = $this->registry->GPC['trans_id'];
		if(!empty($this->registry->GPC['item']) AND !empty($this->registry->GPC['trans_id']))
		{
			$this->paymentinfo = $this->registry->db->query_first("
				SELECT paymentinfo.*, user.username
				FROM " . TABLE_PREFIX . "paymentinfo AS paymentinfo
				INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				WHERE hash = '" . $this->registry->db->escape_string($this->registry->GPC['item']) . "'
			");
			if (!empty($this->paymentinfo))
			{
			    include_once dirname(__FILE__).'/include/nextpay_payment.php';

				$sub = $this->registry->db->query_first("SELECT * FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $this->paymentinfo['subscriptionid']);
				$cost = unserialize($sub['cost']);				
				$amount = floor($cost[0][cost][usd]*$this->settings['d2t']);
				
				$parameters = array
				(
				    'api_key'	=> $this->settings['nxapi_key'],
				    'order_id'	=> '0',
				    'trans_id' 	=> $this->registry->GPC['trans_id'],
    				'amount'	=> $amount,
				);

				$nextpay = new Nextpay_Payment();
    
				$result = $nextpay->verify_request($parameters);

    				if ($result == 0) {

				  $this->paymentinfo['currency'] = 'usd';
				  $this->paymentinfo['amount'] = $cost[0][cost][usd];				
				  $this->type = 1;								
				  return true;

			        }else{
                                  $this->error = 'Unsuccessful Payment';
				  return false;
			        }


			} else {
				$this->error = 'Invalid trasaction';
				return false;
			}
		}else{
			$this->error = 'Duplicate transaction.';
			return false;
		}
    }

	function test()
	{
		if (class_exists('SoapClient')){
			if(!empty($this->settings['nxapi_key']) AND !empty($this->settings['d2t'])){
				return true;
			}
		}
		return false;
	}

	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo)
	{
		global $vbphrase, $vbulletin, $show;

		$item = $hash;
		$cost = floor($cost*$this->settings['d2t']);
		$apiKey = $this->settings['nxapi_key'];

		$form['action'] = 'nextpay.php';
		$form['method'] = 'POST';

		$settings =& $this->settings;

		$templater = vB_Template::create('subscription_payment_nextpay');
        $templater->register('Api_Key',$apiKey);
		$templater->register('cost', $cost);
		$templater->register('item', $item);					
		$templater->register('subinfo', $subinfo);
		$templater->register('settings', $settings);
		$templater->register('userinfo', $userinfo);
		$form['hiddenfields'] .= $templater->render();
		return $form;
	}
}
