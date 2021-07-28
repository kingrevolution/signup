<?php
// $Revision: 18407 $; $Date: 2021-06-21 11:53:11 +0000 (Mon, 21 Jun 2021) $
defined('SIGNUP') or die('Restricted access');

Abstract class SignupSubmit
{
	public static function subscriptionSubmit()
	{
		global $config, $mail;

		if(!empty($GLOBALS['paypal_attempts']) && isset($GLOBALS['paypal_attempts'][$_REQUEST['subscription_token']]))
		{
			$_SESSION['subscription_token'] = $_REQUEST['subscription_token'];
			self::_paypalSubmit();
			return;
		}
		else if (!empty($GLOBALS['stripe_customers_info']) && isset($GLOBALS['stripe_customers_info'][$_REQUEST['subscription_token']])) 
		{
			$_SESSION['subscription_token'] = $_REQUEST['subscription_token'];
			self::_stripeSubmit();
			return;
		}
		else if(!empty($GLOBALS['emails_to_confirm']) && isset($GLOBALS['emails_to_confirm'][$_REQUEST['subscription_token']]))
		{
			$email_to_confirm = $GLOBALS['emails_to_confirm'][$_REQUEST['subscription_token']];
			$_SESSION['subscription_token'] = $_REQUEST['subscription_token'];
			if(!empty($config['captcha']))
			{
				$_SESSION['solt'] = $email_to_confirm['solt'];
				unset($email_to_confirm['solt']);
			}
			if(!empty($config['sms']))
			{
				$_SESSION['sms'] = array();
				$_SESSION['sms']['code'] = $email_to_confirm['session_sms_code'];
				unset($email_to_confirm['session_sms_code']);
			}
			$_POST = $email_to_confirm;
		}
		else if(!empty($config['email_confirm']) && !empty($_POST['email']))
		{
			global $mail;
			$email_to_confirm = $_POST;
			$email_to_confirm['timestamp'] = time();
			if(!empty($config['sms']))
			{
				$email_to_confirm['session_sms_code'] = $_SESSION['sms']['code'];
			}
			if(!empty($config['captcha']))
			{
				$email_to_confirm['solt'] = $_SESSION['solt'];
			}
			$result = array('email_to_confirm'=>$email_to_confirm);
			self::_WriteToLog($result);
			$url = SignupHelper::GetUrl().'&subscription_token='.$_SESSION['subscription_token'];
			$from = !empty($mail['cust_from']) ? $mail['cust_from'] : 'no-reply@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
			$to = $_POST['email'];
			$subject = empty($mail['email_confirm_subject'][$GLOBALS['lang']]) ? (empty($mail['email_confirm_subject']["en"]) ? ( is_array($mail['email_confirm_subject']) ? reset($mail['email_confirm_subject']) : '' ) : $mail['email_confirm_subject']["en"]) : $mail['email_confirm_subject'][$GLOBALS['lang']];
			$message = empty($mail['email_confirm_message'][$GLOBALS['lang']]) ? (empty($mail['email_confirm_message']["en"]) ? ( is_array($mail['email_confirm_message']) ? reset($mail['email_confirm_message']) : '' ) : $mail['email_confirm_message']["en"]) : $mail['email_confirm_message'][$GLOBALS['lang']];
			$message = str_replace('$link',$url,$message);
			SignupHelper::SendMail($to, $from, $subject, $message);
			session_unset();
			SignupHelper::Redirect(array('type'=>'result','content'=>array('email_confirm'=>TRUE)),'result',$GLOBALS['vars']);
		}

		global $error,$payment_method,$set_package;

		SignupHelper::WriteCompletePercentage('1%');
		$subscriber = $set_package['subscriber'];
		if (!empty($config['captcha']))
		{
			$solt = $_SESSION['solt'];
			unset($_SESSION['solt']);
			if($solt != md5($_POST['g-recaptcha-response'] . $config['captcha']['private_key']))
			{
				SignupHelper::Redirect(array('type'=>'error','content'=>array("error_mes" => $GLOBALS['text']['HACKING_ATTEMPT'])),'subscription');
			}
		}
		if(!empty($config['sms']))
		{
			$sms_code = !empty($_SESSION['sms']['code']) ? $_SESSION['sms']['code'] : NULL;
			unset($_SESSION['sms']);
			if(empty($_POST['sms_code']) || $_POST['sms_code'] != $sms_code)
			{
				SignupHelper::Redirect(array('type'=>'error','content'=> array("error_mes" => $GLOBALS['text']['SMS_ERROR'])),'subscription');
			}
		}
		//email domain validation
		if (!empty($config['valid_email_domains']))
		{
			$email = $_REQUEST['email'];
			unset($_REQUEST['email']);
			$pos = strripos($email, "@");
			$domain = substr($email, $pos+1);
			$valid_domains = explode(", ",$config["valid_email_domains"]);
			for ($key = 0, $size = count($valid_domains); $key < $size; $key++)
			{
				if($domain == $valid_domains[$key])
				{
					break;
				}
				elseif(($key+1)==$size)
				{
					SignupHelper::Redirect(array('type'=>'error','content'=>array("error_mes" => $GLOBALS['text']['WRONG_EMAIL_DOMAIN'])),'subscription');
				}
			}
		}
		$form_fields = array(
			'companyname'=>'not_mandatory',
			'midinit' => 'not_mandatory',
			'salutation'=>'not_mandatory',
			'note'=>'not_mandatory',
			'cont1'=>'not_mandatory',
			'fax'=>'not_mandatory',
			'phone2'=>'not_mandatory',
			'cont2'=>'not_mandatory',
			'i_time_zone'=>'not_mandatory',
			'number'=>'not_mandatory',
			'alias'=>'not_mandatory',
			'phone1'=>'not_mandatory',
			'promo_code'=>'not_mandatory',
			'firstname'=>'mandatory',
			'lastname'=>'mandatory',
			'address'=>'mandatory',
			'city'=>'mandatory',
			'state'=>'mandatory',
			'zip'=>'mandatory',
			'country'=>'mandatory',
			'email'=>'mandatory',
			'did_api'=>'not_mandatory',
			'payment_info' => array(
					'payment_method_select'=>'mandatory',
					'cc_address'=>'mandatory',
					'cc_zip'=>'mandatory',
					'cc_number'=>'mandatory',
					'cc_cvv'=>'mandatory',
					'cc_city'=>'mandatory',
					'iso_3166_1_a2'=>'mandatory',
					'iso_3166_a2'=>'mandatory',
					'cc_year'=>'mandatory',
					'cc_month'=>'mandatory',
					'cc_name'=>'mandatory')
		);

		$submitted_data = array("package" => intval($_POST["pack"]));
		foreach($_POST as $key => $value)
		{
			if (isset($form_fields[$key]) || isset($form_fields['payment_info'][$key]))
			{
				if (gettype($value) == 'string')
				{
					$submitted_data[$key] = $GLOBALS[$key] = SignupHelper::PrepareString($value);
				}
				else if(gettype($value) == 'array')
				{
					$submitted_data[$key] = $GLOBALS[$key] = array();
					foreach($value as $k=>$v)
					{
						if(gettype($v) == 'array')
						{
							$submitted_data[$key][$k] = $GLOBALS[$key][$k] = array();
							foreach($v as $i=>$val)
							{
								$submitted_data[$key][$k][$i] = $GLOBALS[$key][$k][$i] = SignupHelper::PrepareString($val);
							}
						}
						else
						{
							$submitted_data[$key][$k] = $GLOBALS[$key][$k] = SignupHelper::PrepareString($v);
						}
					}
				}
				if(isset($value) && $value !== "")
				{
					unset($form_fields[$key]);
					unset($form_fields['payment_info'][$key]);
				}
			}
		}
		$submitted_data["ref"] = $GLOBALS['ref'] = (!empty($_GET['ref']) && !empty($set_package['referral']) && strpos($set_package['referral'], $_GET['ref']) !== FALSE) ? $_GET['ref'] : NULL;
		$submitted_data["paypal_payment"] = $GLOBALS['paypal_payment'] = ((!empty($payment_method) && isset($GLOBALS['payment_method_select']) && $payment_method[$GLOBALS['payment_method_select']] == 'PayPal'
				|| count($payment_method) == 1 && implode('',$payment_method) == 'PayPal')
				&& !empty($set_package['amount'])) ? TRUE : FALSE;
		$_REQUEST = $_POST = $_GET = array();

		if(count($payment_method) == 1 && reset($payment_method) == 'PayPal')
		{
			$submitted_data["payment_method_select"] = $GLOBALS['payment_method_select'] = reset(array_keys($payment_method));
		}

		if (in_array('mandatory',$form_fields)
				|| !empty($payment_method) && $payment_method[$GLOBALS['payment_method_select']] != 'PayPal' && !empty($form_fields['payment_info'])
				|| ($subscriber['id_source'] != 'rand' && $subscriber['id_source'] != 'none_field') && isset($form_fields['number'])
				|| $subscriber['id_source'] == 'DID_API' && isset($form_fields['did_api']))
		{
			SignupHelper::Redirect(array('type'=>'error','content'=>array("error_mes" => $GLOBALS['text']['MISSING_MAND_FIELDS'])),'subscription',(empty($submitted_data["package"]) ? "" : "&package=".$submitted_data["package"]));
		}

		$data = SignupHelper::GetStates();
		$GLOBALS["state"] = $data[$GLOBALS["country"]]["states"][$GLOBALS["state"]];

		$address = str_split($GLOBALS["address"], 41);
		$GLOBALS["address"] = array(
			"baddr1" => NULL,
			"baddr2" => NULL,
			"baddr3" => NULL,
			"baddr4" => NULL,
			"baddr5" => NULL,
		);
		foreach($address as $i => $string)
			$GLOBALS["address"]["baddr".($i + 1)] = $string;

		$result = NULL;
		$GLOBALS['account_info'] = $GLOBALS['customer_info'] = array();
		SignupHelper::WriteCompletePercentage('6%');

		/**
		 * Creation of a customer
		 */
		if (empty($subscriber['i_customer']))
		{
			$i_customer = self::_CreateCustomer();
			if ($i_customer)
			{
				$i_customer = self::_UpdateCustomer($i_customer);
			}
		}
		else
		{
			$i_customer = $subscriber['i_customer'];
		}

		/**
		 * Creation of an account
		 */
		if (($i_customer) && !(($subscriber['id_source'] == 'none_field') && ($set_package['virtoffice'] == 'true')))
		{
			$i_account = self::_CreateAccount($i_customer);
			if($i_account)
			{
				$i_account = self::_UpdateAccount($i_account);
			}

			if (!$i_account && empty($subscriber['i_customer']))
			{
				self::_DeleteCustomer($i_customer);
			}
			elseif ($i_account)
			{
				if(!empty($set_package['qrcode']))
				{
					$env = $config['env'];
					while(strlen($env) < 3)
					{
						$env = '0'.$env;
					}
					$qrcode = 'csc:'.rawurlencode($GLOBALS['account_info']['id']).':'.rawurlencode($GLOBALS['account_info']['h323_password']).'@TG';
				}
				$package_desc = empty($set_package[$GLOBALS['lang']]['description']) ? ( $pack = reset($set_package) and !empty($pack['description']) ? $pack['description'] : '' ) : $set_package[$GLOBALS['lang']]['description'];
				if(isset($GLOBALS["virtoffice"])) $subscriber["virtoffice"] = TRUE;
				$result = array(
						'email' => $GLOBALS['email'],
						'number' => $GLOBALS['account_info']['id'],
						'softphone_link' => (!empty($mail['phone_link'])) ? $mail['phone_link'] : NULL,
						'subscriber' => $subscriber,
						'references' => $config['references'],
						'qrcode_data' => (empty($qrcode) ? '' : $qrcode),
						'package'=> $package_desc.' ('.(!empty($set_package['amount']) ? $set_package['amount'] : '0').' '.$set_package['template_account']['currency'].')'
				);
			}

		}

		/**
		 * Processing result
		 */
		SignupHelper::WriteCompletePercentage('95%');
		if ($result)
		{
			if ($subscriber['billing_model'] == 0)
			{
				$account_type = 'Recharge Voucher';
			}
			elseif (!empty($subscriber['prefix']) && in_array($subscriber['prefix'],array('cc','cb','a')))
			{
				if($subscriber['prefix'] == 'cc')
				{
					$account_type = 'Calling Card';
				}
				else if($subscriber['prefix'] == 'a')
				{
					$account_type = 'Pinless Account';
				}
				else if($subscriber['prefix'] == 'cb')
				{
					$account_type = 'Callback Account';
				}
			}
			else
			{
				$account_type = 'Prepaid Account';
			}
			$result['account_type'] = $account_type;
			$result['pin'] = (!empty($GLOBALS['pin'])) ? $GLOBALS['pin'] : '';
			$result['voip_pass'] = $GLOBALS['account_info']['h323_password'];
			$result['alias'] = (!empty($GLOBALS['alias_id'])) ? $GLOBALS['alias_id'] : '';
			$result['account_password'] = $GLOBALS['account_info']['password'];
			$result['account_login'] = $GLOBALS['account_info']['login'];
			$result['customer_password'] = (!empty($GLOBALS['customer_info'])) ? $GLOBALS['customer_info']['password'] : '';
			$result['customer_login'] = (!empty($GLOBALS['customer_info'])) ? $GLOBALS['customer_info']['login'] : '';
			$result['customer_name'] = (!empty($GLOBALS['customer_info'])) ? $GLOBALS['customer_info']['name'] : '';
			if (!empty($GLOBALS['stripe_3ds_url']) || !empty($GLOBALS['stripe_payment_intent'])) {
				$result['stripe_3ds_url'] = (!empty($GLOBALS['stripe_3ds_url'])) ? $GLOBALS['stripe_3ds_url'] : '';
				$result['stripe_payment_intent'] = (!empty($GLOBALS['stripe_payment_intent'])) ? $GLOBALS['stripe_payment_intent'] : '';
			}
			self::_WriteToLog($result);
		}
		if (($i_customer)&&(($subscriber['id_source'] == 'none_field') && ($set_package['virtoffice'] == 'true')))
			{
			$subscriber["virtoffice"] = TRUE;
			$result['subscriber'] = $subscriber;
			$result['customer_password'] = (!empty($GLOBALS['customer_info'])) ? $GLOBALS['customer_info']['password'] : '';
			$result['customer_login'] = (!empty($GLOBALS['customer_info'])) ? $GLOBALS['customer_info']['login'] : '';
			$result['customer_name'] = (!empty($GLOBALS['customer_info'])) ? $GLOBALS['customer_info']['name'] : '';
			$result['references'] = $config['references'];
			}

		self::_SendEmailNotifications($result);
		SignupHelper::WriteCompletePercentage('100%');

		/**
		 * End SOAP session & show result
		 */
		SignupHelper::MakeSoapCall('end_session');
		SignupHelper::WriteCompletePercentage(NULL,TRUE);
		if ($result)
		{
			$result['notice'] = empty($config['submit_note']) ? '' : $config['submit_note'];
			SignupHelper::Redirect(array('type'=>'result','content'=>$result),'result',$GLOBALS['vars']);
		}
		else
		{
			$error = $error = $GLOBALS['text']['ERROR_MES'];
			SignupHelper::Redirect(array('type'=>'error','content' => array("error_mes" => $error, "submitted_data" => $submitted_data)),'subscription',(empty($submitted_data["package"]) ? "" : "&package=".$submitted_data["package"]));
		}
	}

	public static function wizardSubmit()
	{
		global $error,$config;

		if(isset($config['valid_email_domains'])){
			 $_SESSION['valid_email_domains'] = $config['valid_email_domains'];
		}

		if ($_POST['act'] == 'logout')
		{
			SignupHelper::WriteCompletePercentage(NULL,TRUE);
			session_unset();
			session_destroy();
			SignupHelper::Redirect(array('type'=>'result','content'=>''),'wizard');
		}
		else if ($_POST['act'] == 'login')
		{
			SignupHelper::WriteCompletePercentage(NULL,TRUE);
			$login = SignupHelper::PrepareString($_POST['login']);
			$password = SignupHelper::PrepareString($_POST['password']);
			if (!file_exists('auto_config.php') && !empty($login) && !empty($password) || $login == $config['login'] && $password == $config['password'])
			{
				if (empty($GLOBALS['config']))
				{
					$GLOBALS['config'] = array(
						'login' => $login,
						'password' => $password,
						'service' => array('Admin','Reseller'),
						'server_url' => 'mybilling.telinta.com'
					);
				}
				$login_response = SignupHelper::MakeSoapCall('get_i_env','none',array(),'Internal');
				SignupHelper::MakeSoapCall('end_session');
				if($error)
				{
					$error_string = implode("<br>", $error); 
					SignupHelper::Redirect(array('type'=>'error','content'=>$error_string),'wizard');
				}
				else
				{
					$GLOBALS['config']['env'] = (string)$login_response->i_env;
					$_SESSION['config'] = $GLOBALS['config'];
					SignupHelper::Redirect(array('type'=>'result','content'=>''),'wizard');
				}
			}
			else
			{
				SignupHelper::Redirect(array('type'=>'error','content'=>$GLOBALS['text']['SAME_CREDENTIALS']),'wizard');
			}
		}
		else if ($_POST['act'] == 'save')
		{
			SignupHelper::WriteCompletePercentage('1%');
			$form_fields = $validate = array(
				'mail'=>'mandatory',
				'payment_method'=>'not_mandatory',
				'config'=>'mandatory',
				'packages'=>'mandatory'
			);
			if(!empty($_POST['config']['sms']) && (preg_match('/^[a-f0-9]{32}$/i', $_POST['config']['sms']['password']) == 0)){
                                $_POST['config']['sms']['password'] = md5($_POST['config']['sms']['password']);
                        }

			$GLOBALS['conf'] = $config;
			foreach($_POST as $key => $value)
			{
				if (isset($form_fields[$key]))
				{
					if (gettype($value) == 'string')
					{
						${$key} = SignupHelper::PrepareString($value);
					}
					else if(gettype($value) == 'array')
					{
						${$key} = array();
						foreach($value as $k=>$v)
						{
							if(gettype($v) == 'array' && 'mail' != $key)
							{
								${$key}[$k] = array();
								foreach($v as $i=>$val)
								{
									if(gettype($val) == 'array')
									{
										${$key}[$k][$i] = $val;
									}
									else
									{
										${$key}[$k][$i] = SignupHelper::PrepareString($val);
									}
								}
							}
							else
							{
								$is_html = 0;
								if('mail' == $key)
								{
									$is_mail = 1;
								}
								${$key}[$k] = empty($is_mail) ? SignupHelper::PrepareString($v) : $v;
							}
						}
					}
					if($value)
					{
						unset($validate[$key]);
					}
				}
			}

			$_REQUEST = $_POST = $_GET = array();

			if (in_array('mandatory',$validate))
			{
				SignupHelper::Redirect(array('type'=>'error','content'=>$GLOBALS['text']['MISSING_MAND_FIELDS']),'wizard');
			}
			else
			{
				unset($form_fields['packages']);
				unset($form_fields['mail']);
			}

			SignupHelper::WriteCompletePercentage('10%');

			if(empty($GLOBALS['conf']))
			{
				$GLOBALS['conf'] = $_SESSION['config'];
			}
			$config['login'] = $GLOBALS['conf']['login'];
			$config['password'] = $GLOBALS['conf']['password'];
			$config['service'] = $GLOBALS['conf']['service'];
			$config['env'] = $GLOBALS['conf']['env'];
			$config['server_url'] = empty($config['server_url']) ? $GLOBALS['conf']['server_url'] : $config['server_url'];
			$config['valid_email_domains'] = "";

			$SOAP_cache = array();
			$coef = floor(80/(count($packages)*5));
			$progress = 10;
			foreach($packages as $key => $package)
			{
				$GetAccountInfoResponse = array();
				$GetAccountInfoResponse = isset($SOAP_cache[$package['template_account']['id']]['GetAccountInfoResponse']) ? $SOAP_cache[$package['template_account']['id']]['GetAccountInfoResponse'] : SignupHelper::MakeSoapCall('get_account_info','Account',array('id'=>$package['template_account']['id']));
				$SOAP_cache[$package['template_account']['id']]['GetAccountInfoResponse'] = $GetAccountInfoResponse;
				$progress = $progress+$coef;
				SignupHelper::WriteCompletePercentage((string)$progress.'%');
				if ($GetAccountInfoResponse)
				{
					$packages[$key]['subscriber']['billing_model'] = $GetAccountInfoResponse->account_info->billing_model;
					$packages[$key]['subscriber']['i_account_balance_control_type'] = (isset($GetAccountInfoResponse->account_info->i_account_balance_control_type)) ? $GetAccountInfoResponse->account_info->i_account_balance_control_type : null;
					$packages[$key]['subscriber']['opening_balance_a'] = $GetAccountInfoResponse->account_info->opening_balance;
					$packages[$key]['subscriber']['credit_limit_a'] = (isset($GetAccountInfoResponse->account_info->credit_limit)) ? $GetAccountInfoResponse->account_info->credit_limit : null;
					$packages[$key]['subscriber']['acl_a'] = $GetAccountInfoResponse->account_info->i_acl;
					$packages[$key]['subscriber']['batch_name'] = !empty($GetAccountInfoResponse->account_info->batch_name) ? $GetAccountInfoResponse->account_info->batch_name : '';
					$packages[$key]['subscriber']['ecommerce_enabled'] = ($packages[$key]['subscriber']['i_customer']) ? 'Y': $GetAccountInfoResponse->account_info->ecommerce_enabled;
					$packages[$key]['subscriber']['follow_me_enabled'] = $GetAccountInfoResponse->account_info->follow_me_enabled;
					$packages[$key]['subscriber']['currency'] = $GetAccountInfoResponse->account_info->iso_4217;
					$packages[$key]['subscriber']['i_customer'] = (!empty($packages[$key]['owner'])) ? '' : $GetAccountInfoResponse->account_info->i_customer;
					$packages[$key]['subscriber']['i_parent'] = $GetAccountInfoResponse->account_info->i_customer;
					$packages[$key]['subscriber']['i_lang'] = $GetAccountInfoResponse->account_info->i_lang;
					$packages[$key]['subscriber']['iso_639_1'] = $GetAccountInfoResponse->account_info->iso_639_1;
					$packages[$key]['subscriber']['blocked_a'] = $GetAccountInfoResponse->account_info->blocked;
					$packages[$key]['subscriber']['expiration_date'] = $GetAccountInfoResponse->account_info->expiration_date;
					$packages[$key]['subscriber']['life_time'] = $GetAccountInfoResponse->account_info->life_time;
					$packages[$key]['subscriber']['i_time_zone_a'] = $GetAccountInfoResponse->account_info->i_time_zone;

					$payment_method_for_export = array();
					if(!empty($payment_method))
					{
						foreach ($payment_method as $name => $v)
						{
							$payment_method_for_export[] = $name;
						}
					}
					if ($packages[$key]['subscriber']['id_source'] == 'DID')
					{
						$packages[$key]['subscriber']['prefix'] = '';
					}
					if ($packages[$key]['subscriber']['id_source'] != 'rand')
					{
						unset($packages[$key]['subscriber']['id_length']);
					}
					if(!$error)
					{
						$GetCustomerResponse = array();
						$GetCustomerResponse = isset($SOAP_cache[$package['template_account']['id']]['GetCustomerResponse']) ? $SOAP_cache[$package['template_account']['id']]['GetCustomerResponse'] : SignupHelper::MakeSoapCall('get_customer_info','Customer',array('i_customer' => $GetAccountInfoResponse->account_info->i_customer));
						$SOAP_cache[$package['template_account']['id']]['GetCustomerResponse'] = $GetCustomerResponse;
						$progress = $progress+$coef;
						SignupHelper::WriteCompletePercentage((string)$progress.'%');
						if($GetCustomerResponse)
						{
							$packages[$key]['subscriber']['i_customer_r'] = !empty($GetCustomerResponse->customer_info->i_parent) ? $GetCustomerResponse->customer_info->i_parent : '';
							if (!empty($packages[$key]['owner']))
							{
								if(isset($GetCustomerResponse->customer_info->perm_credit_limit))
								{
									$packages[$key]['subscriber']['credit_limit_c'] = $GetCustomerResponse->customer_info->perm_credit_limit;
								}
								$packages[$key]['subscriber']['customer_class'] = $GetCustomerResponse->customer_info->i_customer_class;
								$packages[$key]['subscriber']['i_balance_control_type'] = $GetCustomerResponse->customer_info->i_balance_control_type;
								$packages[$key]['subscriber']['opening_balance_c'] = $GetCustomerResponse->customer_info->opening_balance;
								$packages[$key]['subscriber']['acl_c'] = $GetCustomerResponse->customer_info->i_acl;
								$packages[$key]['subscriber']['blocked_c'] = $GetCustomerResponse->customer_info->blocked;
								if (!empty($GetCustomerResponse->customer_info->cld_translation_rule))
								{
									$packages[$key]['subscriber']['cld_translation_rule'] = $GetCustomerResponse->customer_info->cld_translation_rule;
								}
								$packages[$key]['subscriber']['i_billing_period'] = $GetCustomerResponse->customer_info->i_billing_period;
								$packages[$key]['subscriber']['i_time_zone_c'] = $GetCustomerResponse->customer_info->i_time_zone;
								// Handle Representative for customer
								if (empty($packages[$key]['subscriber']['i_rep']))
								{
									$packages[$key]['subscriber']['i_rep'] = NULL;
								}
								elseif ($packages[$key]['subscriber']['i_rep'] == '1')
								{
									$packages[$key]['subscriber']['i_rep'] = !empty($GetCustomerResponse->customer_info->i_rep) ? $GetCustomerResponse->customer_info->i_rep : NULL;
								}
							}
						}
					}

					if (!empty($packages[$key]['referral']) && !$error)
					{
						$GetAccountCustomFieldsResponse = array();
						$GetAccountCustomFieldsResponse = isset($SOAP_cache[$package['template_account']['id']]['GetAccountCustomFieldsResponse']) ? $SOAP_cache[$package['template_account']['id']]['GetAccountCustomFieldsResponse'] : SignupHelper::MakeSoapCall('get_custom_fields_values','Account',array('i_account' => $GetAccountInfoResponse->account_info->i_account));
						$SOAP_cache[$package['template_account']['id']]['GetAccountCustomFieldsResponse'] = $GetAccountCustomFieldsResponse;
						$progress = $progress+$coef;
						SignupHelper::WriteCompletePercentage((string)$progress.'%');
						if ($GetAccountCustomFieldsResponse)
						{
							$custom_field_trig = FALSE;
							foreach ($GetAccountCustomFieldsResponse->custom_fields_values as $field)
							{
								if ($field->name == 'Referral link')
								{
									$custom_field_trig = TRUE;
									break;
								}
								else
								{
									continue;
								}
							}
							if (!$custom_field_trig)
							{
								$error = $GLOBALS['text']['REF_DISABLED'];
							}
						}
					}
					if(
						!(
							empty($packages[$key]['subscriber']['i_customer'])
								&& in_array($packages[$key]["subscriber"]["id_source"],array("DID","DID_API","man","none_field"))
						)
					)
					{
						unset($packages[$key]['virtoffice']);
						unset($packages[$key]['virtoffice_desc']);
					}
					unset($packages[$key]['owner']);
				}
				else if(!$error)
				{
					$error = $GLOBALS['text']['NO_TEMPLATE'];
					break;
				}
				else
				{
					break;
				}
			}
			SignupHelper::WriteCompletePercentage('100%');
			SignupHelper::MakeSoapCall('end_session');
			SignupHelper::WriteCompletePercentage(NULL,TRUE);
			if ($error)
			{
				$error_string = implode("<br>", $error);
				SignupHelper::Redirect(array('type'=>'error','content'=>$error_string),'wizard');
			}
			else
			{
				$fp = fopen('auto_config.php','w');
				fwrite($fp,"<?php defined('SIGNUP') or die('Restricted access'); \n");

				if(isset($_SESSION['valid_email_domains'])){
					$config['valid_email_domains'] = $_SESSION['valid_email_domains'];
				}

				foreach ($form_fields as $key => $value)
				{
					if (isset(${$key}))
					{
						if(gettype(${$key}) == 'array')
						{
							foreach(${$key} as $k => $val)
							{
								if(gettype(${$key}[$k]) == 'array')
								{
									foreach(${$key}[$k] as $j => $v)
									{
										if(gettype(${$key}[$k][$j]) != 'array')
										{
											if(${$key}[$k][$j] == 'on')
											{
												${$key}[$k][$j] = TRUE;
											}
										}
									}
								}
								else
								{
									if(${$key}[$k] == 'on')
									{
										${$key}[$k] = TRUE;
									}
								}
							}
						}
						else
						{
							if(${$key} == 'on')
							{
								${$key} = TRUE;
							}
						}
						fwrite($fp, '$'.$key.'='.var_export(${$key},TRUE).";\n");
					}
				}

				fwrite($fp, '$packages = array('."\n");
				reset($packages);
				$first_key = key($packages);
				foreach ($packages as $key => $package)
				{
					if (isset($packages[$key]))
					{
						foreach ($packages[$key] as $k => $v)
						{
							if(gettype($v) == 'array')
							{
								foreach($packages[$key][$k] as $j => $val)
								{
									if ($val === '' || $val === NULL)
									{
										unset($packages[$key][$k][$j]);
									}
									else if ($val === 'on')
									{
										$packages[$key][$k][$j] = TRUE;
									}
								}
							}
							else
							{
								if ($v === '' || $v === NULL)
								{
									unset($packages[$key][$k]);
								}
								else if ($v === 'on')
								{
									$packages[$key][$k] = TRUE;
								}
							}
						}
						fwrite($fp,(($first_key == $key)?'':",\n").(var_export($packages[$key],TRUE)));
					}
				}
				fwrite($fp, "\n".');'."\n");
				fwrite($fp,"?>");
				fclose($fp);

				$file = 'mail.php';
				unlink($file);
				$fh=fopen($file, 'w');
				fwrite($fh,"<?php defined('SIGNUP') or die('Restricted access'); \n");
				fwrite($fh,'$mail = '.var_export($mail,TRUE).';');
				fwrite($fh,"\n?>");
				fclose($fh);

				SignupHelper::Redirect(array('type'=>'success','content'=>$GLOBALS['text']['SAVED_CONF']),'wizard',$GLOBALS['vars']);
			}
		}
		else
		{
			SignupHelper::Redirect(array('type'=>'error','content'=>$GLOBALS['text']['MISSING_ARG']),'wizard');
		}
	}


	/**
	 * SUBSCRIPTION
	 */
	/**
	 * Functions related to customer creation processing
	 */
	private static function _CreateCustomer()
	{
		global $error,$config,$customer_info,$set_package,$companyname,$firstname,$salutation,$lastname,$address,
		$city,$state,$zip,$country,$note,$cont1,$phone1,$fax,$phone2,$cont2,$email,$i_time_zone,$midinit;

		$subscriber = $set_package['subscriber'];
		$cus_login_source = !empty($set_package['cus_login_source']) ? $set_package['cus_login_source'] : 'email';
		if ($cus_login_source == 'email') {
			$cust_login = preg_replace("%[^a-z\d\-_@\.]%i", '',$email);
		} elseif ($cus_login_source == 'firstname_lastname') {
			$cust_login = $firstname . '_' . $lastname;
			$cust_login = strtolower(str_replace(' ', '_', $cust_login));
		} elseif ($cus_login_source == 'email_rand_digits') {
			$cust_login = preg_replace("%[^a-z\d\-_@\.]%i", '',$email) . '_' . rand(1000,9999);
		}
		if (strlen($cust_login) > 40) {
			$cust_login = 'Customer-'.time();
		}
		$balance = empty($subscriber['opening_balance_c']) ? 0 : $subscriber['opening_balance_c'];
		$customer_type = !empty($subscriber['i_customer_r']) ? $subscriber['i_customer_r'] : NULL;

		$cust_pass = self::generatePassword(8);
		$customer_info = array(
			'name' => 'signup-'.time(),
			'iso_4217' => $subscriber['currency'],
			'i_parent' => $customer_type,
			'i_balance_control_type' => empty($subscriber['i_balance_control_type']) ? 1 : $subscriber['i_balance_control_type'],
			'opening_balance' => $balance,
			'i_billing_period'=>$subscriber['i_billing_period'],
			'login'	=> $cust_login,
			'password' => $cust_pass,
			'i_acl' => $subscriber['acl_c'],
			'companyname' => $companyname,
			'firstname' => $firstname,
			'salutation' => $salutation,
			'lastname' => $lastname,
			'baddr1' => $address["baddr1"],
			'baddr2' => $address["baddr2"],
			'baddr3' => $address["baddr3"],
			'baddr4' => $address["baddr4"],
			'baddr5' => $address["baddr5"],
			'city' => $city,
			'state' => $state ? substr($state, 0, 21) : '',
			'midinit' => $midinit,
			'zip' => $zip,
			'country' => $country,
			'note' => $note,
			'cont1' => $cont1,
			'phone1' => $phone1,
			'faxnum' => $fax,
			'phone2' => $phone2,
			'cont2' => $cont2,
			'email' => $email,
			'i_time_zone' =>empty($i_time_zone)?$subscriber['i_time_zone_c'] : $i_time_zone,
			'blocked' => $subscriber['blocked_c'],
			'service_features' => array()
		);
		if(isset($subscriber['credit_limit_c']))
		{
			$customer_info['perm_credit_limit'] = $subscriber['credit_limit_c'];
		}
		if(isset($subscriber['i_rep']))
		{
			$customer_info['i_rep'] = $subscriber['i_rep'];
		}
		if ($config['service'] == 'Admin')
		{
			$customer_info['i_customer_class'] = $subscriber['customer_class'];
		}
		SignupHelper::WriteCompletePercentage('10%');
		$AddUpdateCustomerResponse = SignupHelper::MakeSoapCall('add_customer','Customer',array('customer_info' => $customer_info));
		SignupHelper::WriteCompletePercentage('20%');
		if ($AddUpdateCustomerResponse)
		{
			$i_customer = $AddUpdateCustomerResponse->i_customer;
		}
		$customer_info['i_customer'] = $i_customer;
		return ($error) ? FALSE : $i_customer;
	}

	private static function _UpdateCustomer($i_customer,$number=NULL,$i_lang=NULL)
	{
		global $error,$set_package,$customer_info,$payment_method;

		$GetAccountInfoResponse = array();
		$GetAccountInfoResponse = SignupHelper::MakeSoapCall('get_account_info','Account',array('id'=>$set_package['template_account']['id']));
		if (!empty($set_package['ccf_copy']))
		{
			$GetTemplateCustomerCustomFieldsResponse = SignupHelper::MakeSoapCall('get_custom_fields_values','Customer',array('i_customer' => $GetAccountInfoResponse->account_info->i_customer));
			if ($GetTemplateCustomerCustomFieldsResponse)
			{
				$UpdateAccountCutomFieldsValuesResponse = SignupHelper::MakeSoapCall('update_custom_fields_values', 'Customer', array('i_customer' => $i_customer,'custom_fields_values' => $GetTemplateCustomerCustomFieldsResponse->custom_fields_values));
			}
		}
		$GetCustomerServiceFeaturesResponse = SignupHelper::MakeSoapCall('get_service_features','Customer',array('i_customer' => $GetAccountInfoResponse->account_info->i_customer));

		foreach ($GetCustomerServiceFeaturesResponse->service_features as $value)
		{
                    if (in_array($value->name, array("rtpp_level", "voice_dialing"))) {
                    $ArrayCustomerServiceFeatures[$value->name] = $value;
                    }
		}

		$CustomerServiceFeaturesRequest = array(
					'i_customer' => $i_customer,
					'service_features' => $ArrayCustomerServiceFeatures
					);
		$UpdateCustomerServiceFeaturesResponse = SignupHelper::MakeSoapCall('update_service_features','Customer',$CustomerServiceFeaturesRequest);
		SignupHelper::WriteCompletePercentage('25%');
		if (!$i_lang && !$number)
		{
			$subscriber = $set_package['subscriber'];
			if(empty($error) && !empty($set_package['virtoffice']))
			{
				$GetCustomerCutomFieldsValuesResponse = SignupHelper::MakeSoapCall('get_custom_fields_values','Customer',array('i_customer' => $i_customer));
				if ($GetCustomerCutomFieldsValuesResponse)
				{
					$custom_fields = $GetCustomerCutomFieldsValuesResponse->custom_fields_values;
					$field_exists = FALSE;
					foreach ($custom_fields as $key => $custom_field)
					{
						if ("virtoffice" == $custom_field->name)
						{
							$custom_fields[$key]->text_value = $custom_fields[$key]->db_value = "Y";
							$field_exists = TRUE;
						}
						if ("ls_plan" == $custom_field->name and isset($set_package['virtoffice_desc']) and preg_match('/^-?\d+$/', $set_package['virtoffice_desc']))
						{
							$custom_fields[$key]->text_value = $custom_fields[$key]->db_value = -1*abs($set_package['virtoffice_desc']);
						}
					}
					if($field_exists)
					{
						$UpdateAccountCutomFieldsValuesResponse = SignupHelper::MakeSoapCall(
							'update_custom_fields_values','Customer',
							array('i_customer'=> $i_customer,'custom_fields_values'=>$custom_fields)
						);
						$GLOBALS["virtoffice"] = TRUE;
					}
				}
			}
			if (empty($error) && count($payment_method) > 0)
			{
				$make_payment_response = self::_MakePayment('Customer',$i_customer);
				SignupHelper::WriteCompletePercentage('30%');
				if (!$make_payment_response)
				{
					return FALSE;
				}
			}
		}
		if (($i_lang && $number) || (($subscriber['id_source'] == 'none_field') && ($set_package['virtoffice'] == 'true')))
		{
			global $customer_info;

			$lang_list = SignupHelper::GetInterfaceLangs();
			$customer_name = $customer_info["firstname"] . " " . $customer_info["lastname"];
			if (strlen($customer_name) > 31 && strlen($customer_name) < 40) {
				$customer_name = $customer_info["lastname"];
			}
			if (strlen($customer_name) > 40) {
				$customer_name = 'Customer-'.time();
			}
			if (!empty($set_package['cus_login_source']) && $set_package['cus_login_source'] == 'email_rand_digits') {
				$customer_name = $customer_info['login'];
			}

			$CustomerInfo = array (
					'name' => $customer_name,
					'i_customer' => $i_customer,
					'i_lang' => (isset($lang_list[$GLOBALS['lang']]) ? $GLOBALS['lang'] : $i_lang),
			);
			$UpdateCustomerResponse = SignupHelper::MakeSoapCall('update_customer','Customer',array('customer_info' => $CustomerInfo));
			$customer_info['name'] = $CustomerInfo['name'];
			SignupHelper::WriteCompletePercentage('35%');
		}

		return ($error) ? FALSE : $i_customer;
	}
	private static function _DeleteCustomer($i_customer)
	{
		global $error,$set_package;
		$customer_transaction_id = !empty($GLOBALS['customer_transaction_id']) ? $GLOBALS['customer_transaction_id'] : NULL;

		if (!empty($set_package['amount']) && $customer_transaction_id)
		{
			$MakeCustomerTransactionRequest = array(
					'i_customer' => $i_customer,
					'action' => 'E-Commerce Refund',
					'amount' => $set_package['amount'],
					'visible_comment' => 'refund due to failed signup',
					'internal_comment' => 'refund due to failed signup',
					'suppress_notification' => 1,
					'transaction_id' => $customer_transaction_id
			);
			$MakeCustomerTransactionResponse= SignupHelper::MakeSoapCall('make_transaction','Customer',$MakeCustomerTransactionRequest);
		}
		if (!$error)
		{
			$DeleteCustomerResponse= SignupHelper::MakeSoapCall('terminate_customer','Customer',array('i_customer' => $i_customer));
		}

		return ($error) ? FALSE : TRUE;
	}




	/**
	 * Functions related to account creation processing
	 */
	private static function _CreateAccount($i_customer)
	{
		global $error,$config,$set_package,$account_info,$number,$companyname,$firstname,$salutation,$lastname,$address,
		$city,$state,$zip,$country,$note,$cont1,$phone1,$fax,$phone2,$cont2,$email,$i_time_zone,$midinit;

		$subscriber = $set_package['subscriber'];
		$did_mask = !empty($set_package['did_mask']) ? $set_package['did_mask'] : NULL;
		if ($subscriber['id_source'] != "DID")
		{
			$prefix = !empty($subscriber['prefix']) ? $subscriber['prefix'] : '';
			if ($subscriber['id_source'] == 'man')
			{
				$number = $prefix.$number;
//				$hot_numbers = preg_replace("/$prefix(.*)$/", "cld$1", $number);
			}
			else
			{
				if($subscriber['id_source'] == 'DID_API')
				{
					$GLOBALS['did_api']['number'] = $number;
					$GLOBALS['did_api']['package'] = 'DIDAPI::'.$GLOBALS['did_api']['package'];
					$subscriber['id_length'] = 10;
				}
				$number = '';
				for ($i = 0; $i < $subscriber['id_length']; $i++)
				{
					$r = (string)mt_rand(0,9);
					$number .= $r;
				}
				$number = $prefix.$number;
			}
		}
		else if (!empty($did_mask['patern']['back']))
		{
			$number = preg_replace($did_mask['patern']['back'], (string)$did_mask['replace']['back'], $number);
		}

		if (empty($subscriber['i_customer']))
		{
			$batch = mt_rand(10000, 99999);
		}
		else if(!empty($subscriber['batch_name']))
		{
			$batch = $subscriber['batch_name'];
		}

		if (!empty($subscriber['prefix']) && $subscriber['prefix'] == "cc")
		{
			$voip_pass = mt_rand(1000, 9999);
			$pin = $GLOBALS['pin'] = str_replace('cc','',$number).$voip_pass;
		}
		else
		{
			$voip_pass = self::generatePassword(8);
		}
		$lang_list = SignupHelper::GetInterfaceLangs();
		$tz = SignupHelper::GetTimezones(empty($i_time_zone)? $subscriber['i_time_zone_a']:$i_time_zone);
		$tz_name= $tz[empty($i_time_zone)? $subscriber['i_time_zone_a']:$i_time_zone];
		$dt = new DateTime("now", new DateTimeZone($tz_name));
		$dt->setTimestamp(time()); //adjust the object to correct timestamp
		$account_info = array(
			'billing_model' => $subscriber['billing_model'],
			'i_customer' => $i_customer,
			'id' => $number,
			'activation_date' => $dt->format('Y-m-d'),
			'i_product' => $set_package['i_product'],
			'iso_4217' => $subscriber['currency'],
			'i_account_balance_control_type' => empty($subscriber['i_account_balance_control_type']) ? 3 : $subscriber['i_account_balance_control_type'],
			'opening_balance' => empty($subscriber['opening_balance_a']) ? 0 : $subscriber['opening_balance_a'],
			'credit_limit' => isset($subscriber['credit_limit_a']) ? $subscriber['credit_limit_a'] : '',
			'login' => $number,
			'password' => self::generatePassword(8),
			'h323_password' => $voip_pass,
			'i_acl' => $subscriber['acl_a'],
			'companyname' => $companyname,
			'firstname' => $firstname,
			'salutation' => $salutation,
			'lastname' => $lastname,
			'baddr1' => $address["baddr1"],
			'baddr2' => $address["baddr2"],
			'baddr3' => $address["baddr3"],
			'baddr4' => $address["baddr4"],
			'baddr5' => $address["baddr5"],
			'city' => $city,
			'state' => $state ? substr($state, 0, 21) : '',
			'zip' => $zip,
			'country' => $country,
			'midinit' => $midinit,
			'note' => $note,
			'cont1' => $cont1,
			'phone1' => $phone1,
			'faxnum' => $fax,
			'phone2' => $phone2,
			'cont2' => $cont2,
			'subscriber_email' => $email,
			'email' => $email,
			'follow_me_enabled' => $subscriber['follow_me_enabled'],
			'ecommerce_enabled' => $subscriber['ecommerce_enabled'],
			'i_lang' => (isset($lang_list[$GLOBALS['lang']]) ? $GLOBALS['lang'] : $subscriber['i_lang']),
			'iso_639_1' => $subscriber['iso_639_1'],
			'i_time_zone' => empty($i_time_zone)? $subscriber['i_time_zone_a']:$i_time_zone,
			'blocked' => $subscriber['blocked_a'],
			'out_date_format' => 'MM-DD-YYYY',
			'out_time_format' => 'HH24:MI:SS',
			'out_date_time_format' => 'MM-DD-YYYY HH24:MI:SS',
			'in_date_format' => 'MM-DD-YYYY',
			'in_time_format' => 'HH24:MI:SS'
		);
		if(isset($GLOBALS["virtoffice"]))
		{
			$account_info['login'] = time() . substr(md5(mt_rand()*time()), 0, 4);
			$account_info['batch_name'] = $i_customer."-vo-did";
		}

		if (!empty($subscriber['i_customer_r']))
		{
			$method = ($config['service'] == 'Reseller') ? 'get_my_info' : 'get_customer_info';
			$GetCustomerInfoResponse = SignupHelper::MakeSoapCall($method,'Customer',array('i_customer'=>$subscriber['i_customer_r']));
			SignupHelper::WriteCompletePercentage('38%');
			if($GetCustomerInfoResponse)
			{
				$i_lang = $GetCustomerInfoResponse->customer_info->i_lang;
			}
			if ($error)
			{
				return FALSE;
			}
		}
		else
		{
			$i_lang = $GLOBALS['lang'];
		}
		if(empty($subscriber['i_customer']))
		{
			$update_customer_response = self::_UpdateCustomer($i_customer,$number,$i_lang);
		}

		SignupHelper::WriteCompletePercentage('46%');
		$AddAccountResponse = SignupHelper::MakeSoapCall('add_account','Account',array('account_info' => $account_info));
		SignupHelper::WriteCompletePercentage('51%');
		if ($AddAccountResponse)
		{
			$i_account = $AddAccountResponse->i_account;
		}

		$account_info['i_account'] = $i_account;

		return ($error) ? FALSE : $i_account;
	}

	private static function _UpdateAccount($i_account)
	{
		global $error, $ref, $payment_method, $alias, $set_package, $promo_code, $paypal_payment;

		$subscriber = $set_package['subscriber'];
		$terms_text = empty($set_package['terms_text']) ? NULL : $set_package['terms_text'];
		$alias_prefix_on = empty($set_package['alias_prefix_on']) ? NULL : $set_package['alias_prefix_on'];
		$promo_code = empty($set_package['cupon_on']) ? NULL : $promo_code;
		// processing account aliases
		$GetAccountInfoResponse = SignupHelper::MakeSoapCall('get_account_info','Account',array('id'=>$set_package['template_account']['id']));
		if (!empty($set_package['acf_copy']))
		{
			$GetTemplateAccountCustomFieldsResponse = SignupHelper::MakeSoapCall('get_custom_fields_values','Account',array('i_account' => $GetAccountInfoResponse->account_info->i_account));
			if ($GetTemplateAccountCustomFieldsResponse)
			{
				$UpdateAccountCutomFieldsValuesResponse = SignupHelper::MakeSoapCall('update_custom_fields_values', 'Account', array('i_account' => $i_account,'custom_fields_values' => $GetTemplateAccountCustomFieldsResponse->custom_fields_values));
			}
		}
		$GetAccountServiceFeaturesResponse = SignupHelper::MakeSoapCall('get_service_features','Account',array('i_account' => $GetAccountInfoResponse->account_info->i_account));

		foreach ($GetAccountServiceFeaturesResponse->service_features as $value)
		{
                    if (in_array($value->name, array("rtpp_level", "voice_dialing", "routing_plan", "unified_messaging", "auto_attendant", "music_on_hold"))) {
                        $ArrayAccountServiceFeatures[$value->name] = $value;
                        }
                }
		$AccountServiceFeaturesRequest = array(
					'i_account' => $i_account,
					'service_features' => $ArrayAccountServiceFeatures
					);
		$UpdateAccountServiceFeaturesResponse = SignupHelper::MakeSoapCall('update_service_features','Account',$AccountServiceFeaturesRequest);
		if (!empty($subscriber['alias']))
		{
			$alias_id = array();
			SignupHelper::WriteCompletePercentage('64%');
			for ($i = 1; $i <= $subscriber['alias']; $i++)
			{
				if (!empty($alias[$i]))
				{
					if($error)
					{
						return FALSE;
					}

					if ($alias_prefix_on)
					{
						$alias_id[$i] = $subscriber['prefix'].$alias[$i];
					}
					else
					{
						$alias_id[$i] = $alias[$i];
					}
					$AliasInfo = array('alias_info' => array(
							'id' => $alias_id[$i],
							'i_master_account' => $i_account,
							'blocked' => 'N'
					));
					$AddAccountAliasResponse = SignupHelper::MakeSoapCall('add_alias','Account',$AliasInfo);
				}
			}
			$GLOBALS['alias_id'] = $alias_id;
			SignupHelper::WriteCompletePercentage('69%');
		}
		// processing promo
		if (!$error && $promo_code)
		{
			$GetVoucherInfoResponse = SignupHelper::MakeSoapCall('get_account_info', 'Account', array('id'=>$promo_code));
			if ($GetVoucherInfoResponse)
			{
				$voucher_info = $GetVoucherInfoResponse->account_info;
				if (self::_CheckExpirationDate($voucher_info->activation_date, $voucher_info->expiration_date)
						&& $voucher_info->bill_status != 'C'
						&& $voucher_info->blocked == 'Y'
						&& $voucher_info->billing_model == "0"
						&& $voucher_info->balance >= 0)
				{
					if($voucher_info->balance > 0)
					{
						$MakePromoTransactionRequest = array(
								'i_account' => $i_account,
								'action' => 'Manual payment',
								'amount' => $voucher_info->balance,
								'visible_comment' => 'Promo code '.$voucher_info->id,
								'internal_comment' => 'Promo code '.$voucher_info->id,
								'suppress_notification' => 0,
								'transaction_id' => substr(md5(mt_rand()), 0, 20)
						);
						$MakePromoTransactionResponse = SignupHelper::MakeSoapCall('make_transaction','Account',$MakePromoTransactionRequest);
					}

					if(!empty($voucher_info->i_vd_plan))
					{
						$UpdateAccountRequest = array('account_info' => array('i_account' => $i_account, 'i_vd_plan' => $voucher_info->i_vd_plan));
						$UpdateAccountResponse = SignupHelper::MakeSoapCall('update_account','Account',$UpdateAccountRequest);
					}

					$TerminateVoucherResponse = SignupHelper::MakeSoapCall('terminate_account','Account',array('i_account' => $voucher_info->i_account));
				}
			}
			SignupHelper::WriteCompletePercentage('73%');
		}

		// processing subscription
		if (!$error && !empty($set_package['i_subscription']))
		{
			$AddAccountSubscriptionRequest = array(
					'i_account' => $i_account,
					'subscription_info' => array('i_subscription' => $set_package['i_subscription'])
			);
			$AddAccountSubscriptionResponse = SignupHelper::MakeSoapCall('add_subscription','Account',$AddAccountSubscriptionRequest);
			if ($AddAccountSubscriptionResponse)
			{
				$ChargeAccountSubscriptionFeesResponse = SignupHelper::MakeSoapCall('charge_subscription_fees','Account',array('i_account' => $i_account));
			}
			SignupHelper::WriteCompletePercentage('78%');
		}
		//procesing referral & terms
		if (!$error && ($ref || $terms_text))
		{
			$GetAccountCutomFieldsValuesResponse = SignupHelper::MakeSoapCall('get_custom_fields_values','Account',array('i_account' => $i_account));
			if ($GetAccountCutomFieldsValuesResponse)
			{
				$custom_fields = $GetAccountCutomFieldsValuesResponse->custom_fields_values;
				foreach ($custom_fields as $key => $custom_field)
				{
					if ($ref && $custom_field->name == 'Referral link' || $terms_text && $custom_field->name == 'conditions_confirm_date')
					{
						$custom_fields[$key]->text_value = ($custom_field->name == 'Referral link') ? $ref : date('M d Y H:i');
						$custom_fields[$key]->db_value = ($custom_field->name == 'Referral link') ? $ref : date('M d Y H:i');
					}
				}
				$UpdateAccountCutomFieldsValuesResponse = SignupHelper::MakeSoapCall('update_custom_fields_values','Account',array('i_account'=> $i_account,'custom_fields_values'=>$custom_fields));
			}
			SignupHelper::WriteCompletePercentage('86%');
		}

		// processing account payment
		if (!$error && count($payment_method) > 0 && !empty($subscriber['i_customer']))
		{
			$make_payment_response = self::_MakePayment('Account',$i_account);
		}
		SignupHelper::WriteCompletePercentage('93%');

		// using DID API
		if(!$error && !empty($GLOBALS['did_api']) && !$paypal_payment)
		{
			$OrderDIDResponse = self::_OrderDID($i_account);
		}

		return ($error) ? FALSE : $i_account;
	}

	private static function _OrderDID($i_account)
	{
		global $did_api,$account_info,$customer_info;

		$OrderDIDResponse = SignupHelper::MakeSoapCall('order_number', '', $did_api,'DIDAPI');
		if($OrderDIDResponse)
		{
			$number = preg_replace('/[^\d]/','',(string)$OrderDIDResponse->result->number);
			$UpdateAccountRequest = array('account_info' => array('i_account' => $i_account, 'id' => $number));
			$UpdateAccountResponse = SignupHelper::MakeSoapCall('update_account','Account',$UpdateAccountRequest);
			if($UpdateAccountResponse && $account_info)
			{
				$account_info['id'] = $number;
			}
			if($UpdateAccountResponse && $customer_info)
			{
				$UpdateCustomerRequest = array('customer_info' => array('i_customer'=>$customer_info['i_customer'],'name'=>'Customer-'.$number));
				$UpdateCustomerResponse = SignupHelper::MakeSoapCall('update_customer','Customer',$UpdateCustomerRequest);
			}
			return $number;
		}

		return FALSE;
	}

	private static function _CheckExpirationDate($activation_date, $expiration_date)
	{
		if(strtotime($activation_date) < time())
		{
			if (!empty($expiration_date))
			{
				if(strtotime($expiration_date) > time())
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				return TRUE;
			}
		}
		return FALSE;
	}



	/**
	 * Functions shared between customer and account creation processing
	 */
	private static function _SendEmailNotifications($result)
	{
		global $error,$customer_info,$mail,$lang,$account_info,$set_package;

		if (!empty($result['paypal_button']))
		{
			$from = !empty($mail['cust_from']) ? $mail['cust_from'] : 'no-reply@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
			$to = $result['email'];

			$subject = empty($mail['paypal_pending_subject'][$lang]) ? (empty($mail['paypal_pending_subject']["en"]) ? ( is_array($mail['paypal_pending_subject']) ? reset($mail['paypal_pending_subject']) : '' ) : $mail['paypal_pending_subject']["en"]) : $mail['paypal_pending_subject'][$lang];
			$message = empty($mail['paypal_pending_message'][$lang]) ? (empty($mail['paypal_pending_message']["en"]) ? ( is_array($mail['paypal_pending_message']) ? reset($mail['paypal_pending_message']) : '' ) : $mail['paypal_pending_message']["en"]) : $mail['paypal_pending_message'][$lang];
			SignupHelper::SendMail($to, $from, $subject, str_replace('$link',$result["return_page"],$message));
		}
		else if (!empty($result['stripe_3ds_url'])) 
		{
			$from = !empty($mail['cust_from']) ? $mail['cust_from'] : 'no-reply@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
			$to = $result['email'];

			$subject = empty($mail['stripe_pending_subject'][$lang]) ? (empty($mail['stripe_pending_subject']["en"]) ? ( is_array($mail['stripe_pending_subject']) ? reset($mail['stripe_pending_subject']) : '' ) : $mail['stripe_pending_subject']["en"]) : $mail['stripe_pending_subject'][$lang];
			$message = empty($mail['stripe_pending_message'][$lang]) ? (empty($mail['stripe_pending_message']["en"]) ? ( is_array($mail['stripe_pending_message']) ? reset($mail['stripe_pending_message']) : '' ) : $mail['stripe_pending_message']["en"]) : $mail['stripe_pending_message'][$lang];
			$message = str_replace('$stripe_3ds_url',$result["stripe_3ds_url"],$message);
			$url = SignupHelper::GetUrl() . '&subscription_token='.$_SESSION['subscription_token'] . '&act=capture_payment';
			$message = str_replace('$link',$url,$message);
			SignupHelper::SendMail($to, $from, $subject, $message);
		}
		else if ($result)
		{
			if (!(($set_package['subscriber']['id_source'] == 'none_field') && ($set_package['virtoffice'] == 'true'))){
				$number = $result['number'];
				$pin = $result['pin'];
				$acc_login = $result['account_login'];
				$acc_password = $result['account_password'];
			}
			$cust_login = $result['customer_login'];
			$cust_password = $result['customer_password'];
			$cust_name = !empty($customer_info['name']) ? $customer_info['name'] : '';
			$acc_interface = !empty($result['references']['account_sci']) ? $result['references']['account_sci'] : '';
			$cust_interface = !empty($result['references']['customer_sci']) ? $result['references']['customer_sci'] : '';
			if (!empty($result['email']))
			{
				$mime_boundary = FALSE;
				$filename = FALSE;
				$clean_ob_data = FALSE;
				$acc_login = $result['account_login'];
				$acc_password = $result['account_password'];
				$from = !empty($mail['cust_from']) ? $mail['cust_from'] : 'no-reply@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
				$to = $result['email'];
				$subject = empty($mail['cust_subj'][$lang]) ? (empty($mail['cust_subj']["en"]) ? ( is_array($mail['cust_subj']) ? reset($mail['cust_subj']) : '' ) : $mail['cust_subj']["en"]) : $mail['cust_subj'][$lang];
				$message = empty($mail['cust_message'][$lang]) ? (empty($mail['cust_message']["en"]) ? ( is_array($mail['cust_message']) ? reset($mail['cust_message']) : '' ) : $mail['cust_message']["en"]) : $mail['cust_message'][$lang];
				$message = str_replace('$number',$number,$message);
				$message = str_replace('$pin',$pin,$message);
				$message = str_replace('$voip_pass',htmlentities($account_info['h323_password']),$message);
				$message = str_replace('$email',$result['email'],$message);
				$message = str_replace('$cust_login',$cust_login,$message);
				$message = str_replace('$cust_password',htmlentities($cust_password),$message);
				$message = str_replace('$cust_interface',$cust_interface,$message);
				$message = str_replace('$acc_login',$acc_login,$message);
				$message = str_replace('$cust_name',$cust_name,$message);
				$message = str_replace('$acc_password',htmlentities($acc_password),$message);
				$message = str_replace('$acc_interface',$acc_interface,$message);
				$message = str_replace('$price',((empty($set_package["amount"]) ? "0" :$set_package["amount"])." ".$set_package["template_account"]["currency"]),$message);
				$message = str_replace('$package',(empty($set_package["description"][$lang]) ? ( is_array($set_package["description"]) ? reset($set_package["description"]) : '' ) : $set_package["description"][$lang]),$message);
				$source_message = $message;
				if(!empty($result['qrcode_data']) && (empty($result['subscriber']['prefix']) || !in_array($result['subscriber']['prefix'],array('a','cc','cb'))))
				{
					require_once 'libs/phpqrcode/qrlib.php';
					$mime_boundary = md5(time());
					$message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n"
							."Content-Type: text/html; charset=\"UTF-8\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
					$message .= "--{$mime_boundary}\n";
					ob_start();
					QRcode::png($result['qrcode_data'],FALSE,3,3,1,TRUE);
					$data = chunk_split(base64_encode(ob_get_contents()));
					$clean_ob_data = ob_get_contents();
					ob_end_clean();
					$filename = 'Qrcode.png';
					$message .= "Content-Type: image/png; name=\"".$filename."\"\r\n".
					"Content-Transfer-Encoding: base64\r\n".
					"Content-Disposition: attachment\r\n\r\n".$data;
					$message .= "--{$mime_boundary}--";
				}
				SignupHelper::SendMail($to, $from, $subject, $message, $mime_boundary, $filename, $clean_ob_data, $source_message);
			}
			# Service provider notification
			if (!empty($mail['signup_notification_to']))
			{
				$to = $mail['signup_notification_to'];
				$from = !empty($mail['signup_notification_from']) ? $mail['signup_notification_from'] : 'no-reply@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
				$subject = $mail['signup_notification_subj'];
				$message = $mail['signup_notification_message'];
				$message = str_replace('$number',$number,$message);
				$message = str_replace('$cust_name',$cust_name,$message);
				SignupHelper::SendMail($to, $from, $subject, $message);
			}
		}
		else
		{
			if (!empty($mail['error_notification_to']))
			{
				$to = $mail['error_notification_to'];
				$from = !empty($mail['error_notification_from']) ? $mail['error_notification_from'] : 'error@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
				$subject = $mail['error_notification_subj'];
				$message = $mail['error_notification_message'];
				$error_string = implode("<br>", $error); 
				$message = str_replace('$e',$error_string,$message);
				SignupHelper::SendMail($to, $from, $subject, $message);
			}
		}
	}

	private static function _WriteToLog(&$result)
	{
		global $payment_method,$payment_method_select,$set_package,$paypal_payment,$stripe_3ds_url;

		$logsize = 300;//strings
		$logtime = 365*24*3600;//days

		if(!empty($result['paypal_attempts']))
		{
			$paypal_edited = $result['paypal_attempts'];
			unset($result['paypal_attempts']);
		}
		else if(!empty($result['email_to_confirm']))
		{
			$email_to_confirm = $result['email_to_confirm'];
			unset($result['email_to_confirm']);
		}
		else if(!empty($result['stripe_customers_info']))
		{
			$stripe_customers_info_edited = $result['stripe_customers_info'];
			unset($result['stripe_customers_info']);
		}
		$file='log.php';
		$paypal = empty($GLOBALS['paypal_attempts']) ? array() : $GLOBALS['paypal_attempts'];
		$stripe_customers_info = empty($GLOBALS['stripe_customers_info']) ? array() : $GLOBALS['stripe_customers_info'];
		$log = empty($GLOBALS['signup_attempts']) ? array() : $GLOBALS['signup_attempts'];
		$emails_to_confirm = empty($GLOBALS['emails_to_confirm']) ? array() : $GLOBALS['emails_to_confirm'];
		$string = '';

		if(!empty($paypal))
		{
			$paypal = empty($paypal_edited) ? $paypal : $paypal_edited;
			foreach($paypal as $key => $value)
			{
				if(time() - intval($value['timestamp']) > 60*60*24*7)
				{
					$type = empty($value['subscriber']['customer_login']) ? 'Account' : 'Customer';
					$method = ($type == 'Account') ? 'terminate_account' : 'terminate_customer';
					$DeleteCustomerRequest = ($type == 'Account') ? array('i_account'=>$value['i_account']) : array('i_customer'=>$value['i_customer']);
					$DeleteCustomerResponse = SignupHelper::MakeSoapCall($method,$type,$DeleteCustomerRequest);
					unset($paypal[$key]);
				}
				else
				{
					$string .= SignupHelper::VarToString($value,'paypal',$key);
				}
			}
		}

		if(!empty($stripe_customers_info)) {
			$stripe_customers_info = empty($stripe_customers_info_edited) ? $stripe_customers_info : $stripe_customers_info_edited;
			foreach($stripe_customers_info as $key => $value)
			{
				if(time() - intval($value['timestamp']) > 60*60*24*7)
				{
					$type = empty($value['subscriber']['customer_login']) ? 'Account' : 'Customer';
					$method = ($type == 'Account') ? 'terminate_account' : 'terminate_customer';
					$DeleteCustomerRequest = ($type == 'Account') ? array('i_account'=>$value['i_account']) : array('i_customer'=>$value['i_customer']);
					$DeleteCustomerResponse = SignupHelper::MakeSoapCall($method,$type,$DeleteCustomerRequest);
					unset($stripe_customers_info[$key]);
				}
				else
				{
					$string .= SignupHelper::VarToString($value,'stripe_customers_info',$key);
				}
			}
		}


		if(!empty($emails_to_confirm))
		{
			foreach($emails_to_confirm as $key => $value)
			{
				if(empty($email_to_confirm) && $key == $_SESSION['subscription_token'])
				{
					continue;
				}
				if(time() - intval($value['timestamp']) < 60*60*24)
				{
					$string .= SignupHelper::VarToString($value,'emails_to_confirm',$key);
				}
			}
		}

		if(!empty($log))
		{
			$i = (($logsize - count($log)) > 1) ? 0 : 10;
			foreach($log as $j => $value)
			{
				if($j >= $i && (time()-strtotime($value[0])) < $logtime)
				{
					$string .= "\n".'$log[] = array(\''.implode("','",$value).'\');';
				}
			}
		}

		if($paypal_payment)
		{
			$string .= self::_ProcessPayPal($result);
		}
		else if(!empty($stripe_3ds_url)) {
			$string .= self::_ProcessStripe($result);
		}
		else if(!empty($email_to_confirm))
		{
			$string .= SignupHelper::VarToString($email_to_confirm,'emails_to_confirm',$_SESSION['subscription_token']);
		}
		else if(count($result) > 1)
		{
			if (!empty($_SERVER['HTTP_CLIENT_IP']))
			{
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else
			{
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			$vals = array(
				'time' => date('Y-m-d H:i:s',time()),
				'ip' => $ip,
				'package' => $result['package'],
				'type' => $result['account_type'],
				'number' => $result['number'],
				'name' => $result['customer_name'],
				'email' => $result['email']
			);

			$string .= "\n".'$log[] = array(\''.implode("','",$vals).'\');';
		}

		$fh=fopen($file,"w");
		fwrite($fh,"<?php defined('SIGNUP') or die('Restricted access'); \n".'$log = array();'."\n".'$paypal = array();'."\n".'$emails_to_confirm = array();'."\n".'$stripe_customers_info = array();');
		fwrite($fh,$string);
		fclose($fh);
	}

	private static function _ProcessPayPal(&$result)
	{
		global $account_info,$customer_info,$set_package,$did_api;

		$token = $_SESSION['subscription_token'];
		$url = SignupHelper::GetUrl()
			.(strpos(SignupHelper::GetUrl(),"&subscription_token=") === FALSE ? '&subscription_token='.$token : "");
		$GetPayPalButtonRequest = array(
			'type' => (empty($customer_info) ? 'account' : 'customer'),
			'i_value' => (empty($customer_info) ? $account_info['i_account'] : $customer_info['i_customer']),
			'amount' => $set_package['amount'],
			'return_page' => $url.'&act=return',
			'cancel_page' => $url.'&act=cancel'
		);
		$GetPayPalButtonResponse = SignupHelper::MakeSoapCall('get_paypal_button','none',$GetPayPalButtonRequest,'Internal');
		$paypal_log = array(
			'i_account' => $account_info['i_account'],
			'i_customer' => (!empty($customer_info['i_customer']) ? $customer_info['i_customer'] : ''),
			'amount' => $set_package['amount'],
			'timestamp' => time()
		);
		if($did_api)
		{
			$paypal_log['did_api'] = $did_api;
		}
		$paypal_log = array_merge($result,$paypal_log);
		$result = array('paypal_button'=>$GetPayPalButtonResponse->paypal_button,
				"return_page" => $GetPayPalButtonRequest["return_page"],
				"email" => $result["email"]);
		$string = SignupHelper::VarToString($paypal_log,'paypal',$token);

		return $string;
	}

	private static function _paypalSubmit()
	{
		global $vars,$paypal_attempts,$error;

		$result = array();
		$layout = 'subscription';
		$submit_info = $paypal_attempts[$_SESSION['subscription_token']];
		$type = empty($submit_info['i_customer']) ? 'Account' : 'Customer';
		$arr = explode('&',str_replace('?','&',$vars));
		$url_vars = array();
		foreach($arr as $value)
		{
			$matches = NULL;
			preg_match('/(\w+)=(\w+)/',$value,$matches);
			if($matches)
			{
				$url_vars[$matches[1]] = $matches[2];
			}
		}
		$act = isset($url_vars['act']) ? $url_vars['act'] : NULL;

		if(!$act)
		{
			$error = $GLOBALS['text']['MISSING_ARG'];
		}
		else
		{
			if($act == 'return')
			{
				$Request = array_merge((($type == 'Account') ? array('i_account'=>$submit_info['i_account']) : array('i_customer'=>$submit_info['i_customer'])),
					array(
						'i_service' => 2,
						'from_date' => date('Y-m-d H:i:s',strtotime('-1 day')),
						'to_date'=>date('Y-m-d H:i:s')
					)
				);
				$method = ($type == 'Account') ? 'get_xdr_list' : 'get_customer_xdrs';
				$GetXDRList = SignupHelper::MakeSoapCall($method,$type,$Request);
				$paypal_payment = FALSE;
				if(!empty($GetXDRList->xdr_list))
				{
					$xdr_list = $GetXDRList->xdr_list;
					foreach($xdr_list as $xdr)
					{
						if(strpos($xdr->CLD,'PayPal') !== FALSE && abs($xdr->charged_amount) >= floatval($submit_info['amount']))
						{
							if(!empty($submit_info['did_api']))
							{
								$GLOBALS['did_api'] = $submit_info['did_api'];
								unset($submit_info['did_api']);
								if(!empty($submit_info['i_customer']))
								{
									$GLOBALS['customer_info'] = array();
									$GLOBALS['customer_info']['i_customer'] = $submit_info['i_customer'];
								}
								$number = self::_OrderDID($submit_info['i_account']);
								if($number)
								{
									$submit_info['number'] = $number;
								}
							}
							$result = $submit_info;
							$redirect_params = array('type'=>'result','content'=>$result);
							$layout = 'result';
							self::_SendEmailNotifications($result);
							$paypal_payment = TRUE;
							unset($paypal_attempts[$_SESSION['subscription_token']]);
							break;
						}
					}
				}
				if(!$paypal_payment)
				{
					if(time() - $paypal_attempts[$_SESSION['subscription_token']]["timestamp"] < 60*60*24*7)
					{
						global $mail;
						$redirect_params = array('type'=>'warning','content'=>$GLOBALS['text']['PAYPAL_POSTPONE']);
						$from = !empty($mail['cust_from']) ? $mail['cust_from'] : 'no-reply@'.(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
						$to = $submit_info['email'];
						$subject = empty($mail['paypal_pending_subject'][$GLOBALS['lang']]) ? (empty($mail['paypal_pending_subject']["en"]) ? ( is_array($mail['paypal_pending_subject']) ? reset($mail['paypal_pending_subject']) : '' ) : $mail['paypal_pending_subject']["en"]) : $mail['paypal_pending_subject'][$GLOBALS['lang']];
						$message = empty($mail['paypal_pending_message'][$GLOBALS['lang']]) ? (empty($mail['paypal_pending_message']["en"]) ? ( is_array($mail['paypal_pending_message']) ? reset($mail['paypal_pending_message']) : '' ) : $mail['paypal_pending_message']["en"]) : $mail['paypal_pending_message'][$GLOBALS['lang']];
						$message = str_replace('$link',SignupHelper::GetUrl(),$message);
						SignupHelper::SendMail($to, $from, $subject, $message);
					}
					else
					{
						$act = "cancel";
					}
				}
			}
			if($act == 'cancel')
			{
				$method = ($type == 'Account') ? 'terminate_account' : 'terminate_customer';
				$Request = ($type == 'Account') ? array('i_account'=>$submit_info['i_account']) : array('i_customer'=>$submit_info['i_customer']);
				$DeleteCustomerResponse = SignupHelper::MakeSoapCall($method,$type,$Request);
				if($DeleteCustomerResponse)
				{
					$redirect_params = array('type'=>'warning','content'=>$GLOBALS['text']['PAYPAL_CANCEL']);
					unset($paypal_attempts[$_SESSION['subscription_token']]);
				}
			}
		}

		if($error)
		{
			$error_string = implode("<br>", $error); 
			$redirect_params = array('type'=>'error','content'=>array("error_mes" => $error_string));
		}
		else
		{
			$result['paypal_attempts'] = $paypal_attempts;
			self::_WriteToLog($result);
		}

		SignupHelper::Redirect($redirect_params,$layout);
	}

	private static function _ProcessStripe(&$result)
	{
		global $account_info,$customer_info,$set_package,$did_api;

		$token = $_SESSION['subscription_token'];
		$stripe_log = array(
			'i_account' => $account_info['i_account'],
			'i_customer' => (!empty($customer_info['i_customer']) ? $customer_info['i_customer'] : ''),
			'amount' => $set_package['amount'],
			'timestamp' => time()
		);
		if($did_api)
		{
			$stripe_log['did_api'] = $did_api;
		}
		$stripe_log = array_merge($result,$stripe_log);
		$string = SignupHelper::VarToString($stripe_log,'stripe_customers_info',$token);

		return $string; // what you then see as $stripe_customers_info array item in php.log
	}

	private static function _stripeSubmit() {
		global $vars,$stripe_customers_info,$error;

		$result = array();
		$layout = 'subscription';
		$submit_info = $stripe_customers_info[$_SESSION['subscription_token']];
		$type = empty($submit_info['i_customer']) ? 'Account' : 'Customer';
		$arr = explode('&',str_replace('?','&',$vars));
		$url_vars = array();

		foreach($arr as $value)
		{
			$matches = NULL;
			preg_match('/(\w+)=(\w+)/',$value,$matches);
			if($matches)
			{
				$url_vars[$matches[1]] = $matches[2];
			}
		}
		$act = isset($url_vars['act']) ? $url_vars['act'] : NULL;

		if(!$act)
		{
			$error = $GLOBALS['text']['MISSING_ARG'];
		}
		else
		{
			$stripe_xdr = FALSE;
			if($act == 'capture_payment')
			{
				// capture the previously auth'ed payment
				$MakeTransactionRequest = array(
					'i_'.strtolower($type) => $submit_info['i_customer'],
					'action' => 'Capture payment',
					'amount' => $submit_info['amount'],
					'visible_comment' => 'finish Stripe capture payment',
					'internal_comment' => 'finish Stripe payment_intents with manual capture',
					'suppress_notification' => 0,
					'transaction_id' => $submit_info['stripe_payment_intent']
				);
				$MakeTransactionResponse = SignupHelper::MakeSoapCall('make_transaction', $type, $MakeTransactionRequest);
				if ($MakeTransactionResponse && $submit_info['amount'] > 0) {
					$GLOBALS[strtolower($type).'_transaction_id'] = $MakeTransactionResponse->transaction_id;
				}

				// check if Stripe finish capture CDR is in the db - it will be there only if status is COMPLETED
				// auth and failed transactions are not in Customer_CDRs
				$Request = array_merge((($type == 'Account') ? array('i_account'=>$submit_info['i_account']) : array('i_customer'=>$submit_info['i_customer'])),
					array(
						'i_service' => 2,
						'from_date' => date('Y-m-d H:i:s',strtotime('-1 day')),
						'to_date'=>date('Y-m-d H:i:s',strtotime('+10 seconds'))
					)
				);
				$method = ($type == 'Account') ? 'get_xdr_list' : 'get_customer_xdrs';
				$GetXDRList = SignupHelper::MakeSoapCall($method,$type,$Request);
				if(!empty($GetXDRList->xdr_list))
				{
					$xdr_list = $GetXDRList->xdr_list;
					foreach($xdr_list as $xdr)
					{
						if(strpos($xdr->CLD,'Stripe') !== FALSE && abs($xdr->charged_amount) >= floatval($submit_info['amount']))
						{
							if(!empty($submit_info['did_api']))
							{
								$GLOBALS['did_api'] = $submit_info['did_api'];
								unset($submit_info['did_api']);
								if(!empty($submit_info['i_customer']))
								{
									$GLOBALS['customer_info'] = array();
									$GLOBALS['customer_info']['i_customer'] = $submit_info['i_customer'];
								}
								$number = self::_OrderDID($submit_info['i_account']);
								if($number)
								{
									$submit_info['number'] = $number;
								}
							}
							unset($submit_info['stripe_3ds_url']);
							$result = $submit_info;
							$redirect_params = array('type'=>'result','content'=>$result);
							$layout = 'result';
							self::_SendEmailNotifications($result);
							$stripe_xdr = TRUE;
							unset($stripe_customers_info[$_SESSION['subscription_token']]);
							break;
						}
					}
				}
			}
			// if there is stripe xdr - it means the payment failed or no action was taken by the card holder to complete authentication
			if(!$stripe_xdr)
			{
				$method = ($type == 'Account') ? 'terminate_account' : 'terminate_customer';
				$Request = ($type == 'Account') ? array('i_account'=>$submit_info['i_account']) : array('i_customer'=>$submit_info['i_customer']);
				$DeleteCustomerResponse = SignupHelper::MakeSoapCall($method,$type,$Request);
				if($DeleteCustomerResponse)
				{
					$redirect_params = array('type'=>'warning','content'=>$GLOBALS['text']['STRIPE_AUTH_FAILED']);
					unset($stripe_customers_info[$_SESSION['subscription_token']]);
				}
			}
		}

		if($error)
		{
			$error_string = implode("<br>", $error); 
			$redirect_params = array('type'=>'error','content'=>array("error_mes" => $error_string));
		}
		else
		{
			$result['stripe_customers_info'] = $stripe_customers_info;
			self::_WriteToLog($result);
		}

		SignupHelper::Redirect($redirect_params,$layout);
	}



	private static function _MakePayment($type,$id)
	{
		global $error,$set_package,$payment_method,$payment_method_select,$cc_name,$cc_address,$cc_zip,$cc_number,
		$cc_cvv,$cc_city,$iso_3166_1_a2,$iso_3166_a2,$cc_year,$cc_month, $stripe_3ds_url, $stripe_payment_intent;

		if (!empty($payment_method) && !empty($payment_method_select) && $payment_method[$payment_method_select] == 'PayPal'
		|| count($payment_method) == 1 && implode('',$payment_method) == 'PayPal')
		{
			return TRUE;
		}

		$set_package['amount'] = empty($set_package['amount']) ? 0 : floatval($set_package['amount']);
		$PaymentMethodInfo = array(
			'payment_method' => $payment_method[$payment_method_select],
			'name' => $cc_name,
			'address' => $cc_address,
			'zip' => $cc_zip,
			'number' => $cc_number,
			'cvv' => $cc_cvv,
			'city' => $cc_city,
			'iso_3166_1_a2' => $iso_3166_1_a2,
			'i_country_subdivision' => $iso_3166_a2,
			'exp_date' => "$cc_year-$cc_month-01");
		$UpdatePaymentMethodRequest = array(
			'i_'.strtolower($type) => $id,
			'payment_method_info' => $PaymentMethodInfo);

			$GetPaymentMethodsForOwnerRequest = array(
				'owner_type' => $type,
				'i_owner' => $id
			);

			$GetPaymentMethodsForOwnerResponse = SignupHelper::MakeSoapCall('get_payment_methods_for_owner','Payment',$GetPaymentMethodsForOwnerRequest);

			foreach ($GetPaymentMethodsForOwnerResponse->payment_methods as $index => $payment_method_obj) {
			// i_online_payment_processor == '1012' is Stripe Payment Intents
			// so if we found that this Stripe is used as the payment processor for the env
				if ($payment_method_obj->i_online_payment_processor == '1012' && $payment_method_obj->payment_method == $PaymentMethodInfo['payment_method']) {
					// we have Stripe Payment Intents create a payment_intent with capture_method: manual
					// = we make auth only transaction which adds payment method for the customer
					// and tells Stripe that this customer wants to pay some amount
					// and we will capture this amount from the card at once
					// the details are described in: https://stripe.com/docs/payments/capture-later
					$MakeTransactionRequest = array (
						'i_'.strtolower($type) => $id,
						'action' => 'Authorization only',
						'amount' => (!empty($set_package['amount']) ? floatval($set_package['amount']) : 1),
						'visible_comment' => 'start Stripe capture payment',
						'internal_comment' => 'invoke Stripe payment_intents with manual capture',
						'suppress_notification' => 0,
						'card_info' => $PaymentMethodInfo,
						'transaction_id' => substr(md5(mt_rand()), 0, 12),
						'save_card' => 'Y' // this is mandatory
					);

					$MakeTransactionResponse = SignupHelper::MakeSoapCall('make_transaction', $type, $MakeTransactionRequest);
					if (empty($MakeTransactionResponse)) return FALSE;

					// get the transaction details from PortaBilling to obtain a 3ds_url if it is there
					$GetPaymentTransactionByIdRequest = array (
						'i_payment_transaction' => $MakeTransactionResponse->i_payment_transaction
					);
					$GetPaymentTransactionByIdResponse = SignupHelper::MakeSoapCall('get_payment_transaction_by_id', 'Payment', $GetPaymentTransactionByIdRequest);
					if (empty($GetPaymentTransactionByIdResponse)) return FALSE;

					// details can look like: [details] => {"payment_method_id":"","payment_intent":""} or [details] => {"payment_method_id":"","payment_intent":"","3ds_url":""}
					$details = json_decode($GetPaymentTransactionByIdResponse->{'transaction'}->{'details'});
					if(!empty($details->{'3ds_url'})) {
						 $stripe_3ds_url = $details->{'3ds_url'};
						 $stripe_payment_intent = $details->{'payment_intent'};
						return TRUE; // after this we should finish the customer creation etc and send them an email with instructions
						// the screen should be similar to the one with the link confirmation
					} else { // if no, we should process the Stripe transaction as usual: try to make a payment by finishing the capture
						$MakeTransactionRequest = array (
							'i_'.strtolower($type) => $id,
							'action' => 'Capture payment',
							'amount' => $GetPaymentTransactionByIdResponse->{'transaction'}->{'amount'},
							'visible_comment' => 'finish Stripe capture payment',
							'internal_comment' => 'finish Stripe payment_intents with manual capture',
							'suppress_notification' => 0,
							'transaction_id' => $details->{'payment_intent'}
						);
						$MakeTransactionResponse = SignupHelper::MakeSoapCall('make_transaction', $type, $MakeTransactionRequest);
						if ($MakeTransactionResponse && $set_package['amount'] > 0) {
							$GLOBALS[strtolower($type).'_transaction_id'] = $MakeTransactionResponse->transaction_id;
						}
						return TRUE;
					}
					break;
				}
			} 

			$UpdatePaymentMethodResponse = SignupHelper::MakeSoapCall('update_payment_method',$type,$UpdatePaymentMethodRequest);
			if ($UpdatePaymentMethodResponse) {
				$MakeTransactionRequest = array (
					'i_'.strtolower($type) => $id,
					'action' => (empty($set_package['amount']) ? 'Authorization only' : 'E-Commerce Payment'),
					'amount' => (!empty($set_package['amount']) ? floatval($set_package['amount']) : 1),
					'visible_comment' => "refund",
					'internal_comment' => "refund",
					'suppress_notification' => 0,
					'card_info' => $PaymentMethodInfo,
					'transaction_id' => substr(md5(mt_rand()), 0, 12)
					);
				$MakeTransactionResponse = SignupHelper::MakeSoapCall('make_transaction', $type, $MakeTransactionRequest);
				if ($MakeTransactionResponse && $set_package['amount'] > 0) {
					$GLOBALS[strtolower($type).'_transaction_id'] = $MakeTransactionResponse->transaction_id;
				}
			}
		if($error)
		{
			$DeleteCustomerResponse = SignupHelper::MakeSoapCall((($type == 'Customer')?'terminate_customer':'terminate_account'),$type,array('i_'.strtolower($type) => $id));
			return FALSE;
		}
		return TRUE;
	}

	private static function generatePassword($length) {
		$lowercase = "qwertyuiopasdfghjklzxcvbnm";
		$uppercase = "ASDFGHJKLZXCVBNMQWERTYUIOP";
		$numbers = "1234567890";
		$specialcharacters = "{}[];:,./<>?_+~!@#";
		$randomCode = "";
		mt_srand(crc32(microtime()));
		$max = strlen($lowercase) - 1;
		for ($x = 0; $x < abs($length/4); $x++) {
		$randomCode .= $lowercase{mt_rand(0, $max)};
		}
		$max = strlen($uppercase) - 1;
		for ($x = 0; $x < abs($length/4); $x++) {
		$randomCode .= $uppercase{mt_rand(0, $max)};
		}
		$max = strlen($specialcharacters) - 1;
		for ($x = 0; $x < abs($length/4); $x++) {
		$randomCode .= $specialcharacters{mt_rand(0, $max)};
		}
		$max = strlen($numbers) - 1;
		for ($x = 0; $x < abs($length/4); $x++) {
		$randomCode .= $numbers{mt_rand(0, $max)};
		}
		return str_shuffle($randomCode);
	}
}
?>
