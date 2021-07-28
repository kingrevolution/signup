<?php
defined('SIGNUP') or die('Restricted access');

Abstract Class SignupAjaxRequest
{
	/**
	 * subscription
	 */
	public static function AjaxSendSMS($params)
	{
		global $config,$mail;

		$att = empty($_SESSION['sms']['att']) ? 0 : $_SESSION['sms']['att'];

		if($att < $config['sms']['att'])
		{
			$server_url = 'https://mybilling.telinta.com/smsd/smsd.pl?';
			$session_id = substr(md5(mt_rand()*time()),0,6);
			$code = mt_rand(100000,999999);
			$i_customer = empty($_SESSION['sms']['i_customer']) ? NULL : $_SESSION['sms']['i_customer'];
			$_SESSION['sms'] = array('code'=>$code, 'session_id'=>$session_id);
			$text = str_replace('$session',$session_id,str_replace('$code',$code,$mail['sms_validation_message']));
			$options = array(
					'env' => $config['env'],
					'provider' => $config['sms']['provider'],
					'pass_hash' => $config['sms']['password'],
					'to' => $params['phone'],
					'text' => $text
			);

                        if(preg_match('/^[a-f0-9]{32}$/i', $config['sms']['password']) == 0) {
                                $options['pass_hash'] == md5($config['sms']['password']);
                        }
			if($config['service'] == 'Reseller')
			{
				if($i_customer)
				{
					$_SESSION['sms']['i_customer'] = $i_customer;
				}
				else
				{
					$GetMyInfoResponse = SignupHelper::MakeSoapCall('get_my_info','Customer', array());
					$i_customer = $_SESSION['sms']['i_customer'] = $GetMyInfoResponse->customer_info->i_customer;
				}
				$options['i_customer'] = $i_customer;
			}

			$response_code = preg_replace('/.+Result\:\s(\d+)\s.+/',"$1",file_get_contents($server_url.http_build_query($options)));

			if($response_code == '1' or $response_code == 'Success')
			{
				$_SESSION['sms']['att'] = $att+1;
				$att_left = $config['sms']['att'] - $_SESSION['sms']['att'];
				return json_encode(array('session'=>$session_id,'att'=>$att_left,'error'=>'0'));
			}
		}
		else
		{
			$response_code = 'attempts limit exceeded';
		}
		return json_encode(array('error'=>$response_code));

	}

	public static function AjaxDidApi($params)
	{
		global $config,$packages,$mail,$packages;
		$didapi_countries = $packages[$params['pack']]['config']['didapi_countries'];
		$output = array();

		if($params['target'] == 'numbers')
		{
			$GetNumbersListRequest = array('country'=>$params['country']);
			if(!empty($params['city_id']))
			{
				$GetNumbersListRequest['city_id'] = $params['city_id'];
				$output['city_id'] = $params['city_id'];
			}
			elseif(!empty($params['state']))
			{
				$GetNumbersListRequest['state'] = $params['state'];
				$output['state'] = $params['state'];
			}
			$GetNumbersListRequest['ratecenter'] = $params['ratecenter'];
			$output['ratecenter'] = $params['ratecenter'];
			$GetNumbersListResponse = SignupHelper::MakeSoapCall('get_numbers', '', $GetNumbersListRequest,'DIDAPI');
			$output['country'] = $params['country'];
			$output['numbers'] = array();
			if($GetNumbersListResponse)
			{
				foreach($GetNumbersListResponse->numbers as $number)
				{
					$output['numbers'][(string)$number->number] = array(
						'pack'=> str_replace('DIDAPI::','',$number->package),
						"monthly" => (empty($number->monthly) ? NULL : floatval($number->monthly))
					);
					if(!empty($number->city_id))
					{
						$output['numbers'][(string)$number->number]['city_id'] = $number->city_id;
					}
				}
			}
		}
		else if($params['target'] == 'ratecenters')
		{
			$GetRatecentersListResponse = SignupHelper::MakeSoapCall('get_ratecenters','', array('country'=>$params['country'],'state'=>$params['state']),'DIDAPI');
			$output['country'] = $params['country'];
			$output['ratecenters'] = array();
			if(!empty($params['state']))
			{
				$output['state'] = $params['state'];
			}
			if(!empty($params['state']) && $params['state'] === "TF" ) {
				$output['ratecenters']['TF'] = array(
					'name' => SignupHelper::PrepareString("Toll-free"),
					'ratecenter' => "Toll-free"
				);
			}
			if($GetRatecentersListResponse)
			{
				foreach($GetRatecentersListResponse->ratecenters as $ratecenter)
				{
					if(strpos($ratecenter->name,'Toll-free') === FALSE)
					{
						$key = $ratecenter->value;
						$output['ratecenters'][$key] = array(
								'name'=>SignupHelper::PrepareString($ratecenter->name),
								'pack'=>str_replace('DIDAPI::','',$ratecenter->package)
						);
						if(!empty($ratecenter->value))
						{
							$output['ratecenters'][$key]['ratecenter'] = $ratecenter->value;
						}
						if(!empty($ratecenter->city_id))
						{
							$output['ratecenters'][$key]['city_id'] = $ratecenter->city_id;
						}
					}
				}
			}
		}
		else if($params['target'] == 'states')
		{
			$GetStatesListResponse = SignupHelper::MakeSoapCall('get_states', '', array('country'=>$params['country']),'DIDAPI');
			if($GetStatesListResponse && $GetStatesListResponse->states && ($GetStatesListResponse->states[0] && !$GetStatesListResponse->states[0]->error))
			{
				$output['country'] = $params['country'];
				foreach($GetStatesListResponse->states as $state)
				{
					$output['states'][$state->value] = array('name'=>SignupHelper::PrepareString($state->name),'pack'=>str_replace('DIDAPI::','',$state->package));
				}
			}
			else
			{
				$params['state'] = '';
				$params['target'] = 'ratecenters';
				return self::AjaxDidApi($params);
			}
		}
		else if($params['target'] == 'countries')
		{
			$GetCountriesListResponse = SignupHelper::MakeSoapCall('get_countries', '', array(),'DIDAPI');
			$output['countries'] = array();
			if($GetCountriesListResponse)
			{
				foreach($GetCountriesListResponse->countries as $country)
				{
					if(in_array($country->value,$didapi_countries))
					{
						$output['countries'][$country->value] = array('name'=>SignupHelper::PrepareString($country->name),'pack'=>str_replace('DIDAPI::','',$country->package));
					}
				}
			}
		}
		return json_encode($output);
	}

	public static function AjaxGetDidPatterns($params)
	{
		global $config,$packages,$mail;

		$package = $packages[$params['pack']];
		$did_mask = !empty($package['did_mask']) ? $package['did_mask'] : NULL;
		$did_split_on = !empty($package['did_split_on']) ? $package['did_split_on'] : NULL;

		$GetDIDNumberListRequest  =  array(
				'usage' => 'F',
				'limit' => $package['config']['limit'],
				'offset' => $package['config']['offset']);

		if ($package['config']['owner_batch'] == '')
		{
			$GetDIDNumberListRequest['i_customer'] = ($package['subscriber']['i_customer_r']) ? $package['subscriber']['i_customer_r'] : null;
		}
		else
		{
			$GetDIDNumberListRequest['owner_batch'] = $package['config']['owner_batch'];
		}

		$GetDIDNumberListResponse = SignupHelper::MakeSoapCall('get_number_list', '', $GetDIDNumberListRequest,'DID');
		SignupHelper::MakeSoapCall('end_session');
		if (count($GetDIDNumberListResponse->number_list) > 0)
		{
			$did_list = &$GetDIDNumberListResponse->number_list;
			$numbers = $area = array();
			for ($i = 0; $i <= count($GetDIDNumberListResponse->number_list)-1; $i++)
			{
				if (!empty($did_mask['patern']['direct']))
				{
					$did_list[$i]->number = preg_replace($did_mask['patern']['direct'], (string)$did_mask['replace']['direct'], $did_list[$i]->number);
				}
				if ($GetDIDNumberListResponse->number_list[$i]->number != '')
				{
					if (!empty($did_split_on))
					{
						$value = substr($did_list[$i]->number, 0, 3);
						$numbers[$value][] = $did_list[$i]->number;
					}
					else
					{
						$numbers[] = $did_list[$i]->number;
					}
				}
			}
			return json_encode($numbers);

		}
		else
		{
			if (!empty($mail['error_notification_to']))
			{
				$to = $mail['error_notification_to'];
				$from = !empty($mail['error_notification_from']) ? $mail['error_notification_from'] : 'info@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
				$subject = $GLOBALS['text']['NO_DIDS'];
				$message = $GLOBALS['text']['NO_DIDS_MES'];
				SignupHelper::SendMail($to, $from, $subject, $message);
			}
			return '0';
		}
	}

	public static function AjaxCaptchaValidate()
	{
		global $config;
		$captcha = $config['captcha'];
		$privatekey = $captcha['private_key'];
		$remoteIP = $_SERVER['REMOTE_ADDR'];
		$recaptchaErrors = '';

		$gRecaptchaResponse = $_POST['grecaptcha_response'];

		$data = array(
			"secret" => $privatekey,
			"response" => $gRecaptchaResponse,
			"remoteip" => $remoteIP
		);

		$opts = [
				"http" => [
				"method" => "POST",
				"header" => "Content-Type: application/x-www-form-urlencoded",
				"content" => http_build_query($data)
			]
		];

		$context = stream_context_create($opts);

		$request = file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, $context);
		$response = json_decode($request);

		if ($response->success)
		{
			$_SESSION['solt'] = md5($gRecaptchaResponse . $privatekey);
			return "1";
		}
		else
		{
			$recaptchaErrors = $response->{'error-codes'};
			return "0";
		}
	}

	public static function AjaxExistsAccount($params)
	{
		global $error,$packages;
		$set_package = $packages[$params['pack']];
		$subscriber = $set_package['subscriber'];
		$number = SignupHelper::PrepareString($params['account_id']);
		$account_info = array(
				'i_customer' => $subscriber['i_parent'],
				'id' => $number,
				'activation_date' => date('Y-m-d'),
				'i_product' => $set_package['i_product'],
				'billing_model' => $set_package['subscriber']['billing_model'],
				'h323_password' => substr(md5(mt_rand()), 0, 6)
		);
		$AddAccountResponse = SignupHelper::MakeSoapCall('validate_account_info','Account',array('account_info'=>$account_info));

		return json_encode(array('id'=>$number,'status'=>(($error)?'error':'success')));
	}



	/**
	 * wizard
	 */
	public static function AjaxCheckVirtoffice()
	{
		global $config;

		$virtoffice = "false";
		$GetCustomerListResponse = SignupHelper::MakeSoapCall('get_customer_list','Customer',array('limit' => 1, "offset" => 0));
		if (!empty($GetCustomerListResponse->customer_list))
		{
			$cust_info = reset($GetCustomerListResponse->customer_list);
			$GetCustomerCutomFieldsValuesResponse = SignupHelper::MakeSoapCall('get_custom_fields_values','Customer',array('i_customer' => $cust_info->i_customer));
			if (!empty($GetCustomerCutomFieldsValuesResponse->custom_fields_values))
			{
				$custom_fields = $GetCustomerCutomFieldsValuesResponse->custom_fields_values;
				foreach ($custom_fields as $key => $custom_field)
				{
					if ("virtoffice" == $custom_field->name)
					{
						$virtoffice = "true";
						break;
					}
				}
			}
		}
		$_SESSION["voffice"] = $virtoffice;

		return $virtoffice;
	}
	public static function AjaxCheckAccount($params)
	{
		$account_id = SignupHelper::PrepareString($params['account_id']);
		$GetAccountInfoResponse = SignupHelper::MakeSoapCall('get_account_info', 'Account', array('id'=>$account_id));
		SignupHelper::MakeSoapCall('end_session');
		if($GetAccountInfoResponse)
		{
			return json_encode(array('id'=>$GetAccountInfoResponse->account_info->id,'currency'=>$GetAccountInfoResponse->account_info->iso_4217,'status'=>'success'));
		}
		else
		{
			return json_encode(array('id'=>$account_id,'currency'=>'','status'=>'error'));
		}
	}

	public static function AjaxGetOwnerBatchList()
	{
		$service = $GLOBALS['config']['service'];
		$GetOwnerBatchListResponse = SignupHelper::MakeSoapCall('get_owner_batch_list','none',array(),'Internal');
		SignupHelper::MakeSoapCall('end_session');
		if ($GetOwnerBatchListResponse)
		{
			$owner_batch_list = array();
			$response_list = $GetOwnerBatchListResponse->owner_batch_list;
			for ($i = 0; $i <= sizeof($response_list)-1; $i++)
			{
				if ($service == 'Admin')
				{
					if (empty($response_list[$i]->i_customer))
					{
						$owner_batch_list[$response_list[$i]->i_do_batch] = SignupHelper::PrepareString($response_list[$i]->name);
					}
				}
				else
				{
					$owner_batch_list[$response_list[$i]->i_do_batch] = SignupHelper::PrepareString($response_list[$i]->name);
				}
			}
			natsort($owner_batch_list);
			$owner_batch_list = $_SESSION['ownerbatch'] = json_encode($owner_batch_list);
			return $owner_batch_list;
		}
		else
		{
			return '0';
		}
	}

	public static function AjaxGetProducts()
	{
		$service = $GLOBALS['config']['service'];
		$GetProductListRequest = array('offset' => 0,'limit' => '');
		$GetProductListResponse = SignupHelper::MakeSoapCall('get_product_list','none',$GetProductListRequest,'Product');
		SignupHelper::MakeSoapCall('end_session');
		if ($GetProductListResponse && count($GetProductListResponse->product_list) > 0)
		{
			$products_a = $products_r = array();
			foreach ($GetProductListResponse->product_list as $product)
			{
				if ($service == 'Admin')
				{
					if (empty($product->i_customer))
					{
						$products[$product->iso_4217][$product->i_product] = SignupHelper::PrepareString($product->name);
					}
				}
				else
				{
					$products[$product->iso_4217][$product->i_product] = SignupHelper::PrepareString($product->name);
				}
			}
			SignupHelper::SortProdAndSubsc($products);
			$products = $_SESSION['products'] = json_encode($products);
			return $products;
		}
		else
		{
			return '0';
		}
	}

	public static function AjaxGetSubscriptions()
	{
		$service = $GLOBALS['config']['service'];
		$GetSubscriptionListResponse = SignupHelper::MakeSoapCall('get_subscription_list','none',array(),'Subscription');
		SignupHelper::MakeSoapCall('end_session');
		if ($GetSubscriptionListResponse && isset($GetSubscriptionListResponse->subscriptions) && count($GetSubscriptionListResponse->subscriptions) > 0)
		{
			foreach ($GetSubscriptionListResponse->subscriptions as $subscription)
			{

				if ($service == 'Admin')
				{
					if (empty($subscription->i_customer))
					{
						$subscriptions[$subscription->iso_4217][$subscription->i_subscription] = SignupHelper::PrepareString($subscription->name);
					}
				}
				else
				{
					$subscriptions[$subscription->iso_4217][$subscription->i_subscription] = SignupHelper::PrepareString($subscription->name);
				}

			}
			SignupHelper::SortProdAndSubsc($subscriptions);
			$subscriptions = $_SESSION['subscriptions'] = json_encode($subscriptions);
			return $subscriptions;
		}
		else
		{
			return '0';
		}
	}

	/**
	 * The method that returns the list of representatives
	 */
	public static function AjaxGetRepresentatives()
	{
		$text = $GLOBALS['text'];
		$service = $GLOBALS['config']['service'];
		$GetRepresentativeListRequest = array('offset' => 0,'limit' => NULL);
		$GetRepresentativeListResponse = SignupHelper::MakeSoapCall('get_representative_list','none',$GetRepresentativeListRequest,'Representative');
		SignupHelper::MakeSoapCall('end_session');
		if ($GetRepresentativeListResponse && isset($GetRepresentativeListResponse->representative_list) && count($GetRepresentativeListResponse->representative_list) > 0)
		{
			foreach ($GetRepresentativeListResponse->representative_list as $representative)
			{

				if ($service == 'Admin' && !empty($representative->i_customer))
				{
					continue;
				}
				$representatives[$representative->iso_4217][$representative->i_rep] = SignupHelper::PrepareString($representative->name);

			}
			SignupHelper::SortProdAndSubsc($representatives);
			foreach (array_keys($representatives) as $iso_4217) {
				$representatives[$iso_4217] = ['1' => $text['DEFAULT']] + $representatives[$iso_4217];
			}
			$representatives = $_SESSION['representatives'] = json_encode($representatives);
			return $representatives;
		}
		return '0';
	}

	/**
	 * result
	 */
	public static function AjaxGetQrcode($params)
	{
		require_once 'libs/phpqrcode/qrlib.php';
		unset($_SESSION['result_token']);
		return QRcode::png($params['data'],FALSE,3,3,1,TRUE);
	}
}
?>
