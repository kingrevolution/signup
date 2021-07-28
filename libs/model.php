<?php
defined('SIGNUP') or die('Restricted access');

require_once 'libs/helper.php';
require_once 'libs/locale.php';

class SignupModel
{
	public $languages;
	public $template;
	public $task;
	private $pack;

	public function __construct()
	{
		$GLOBALS['root_path'] = SignupHelper::GetRootPath();
		$vars = '';
		foreach ($_GET as $var => $value)
		{
			if (!in_array($var,array('lang','task','layout')))
			{
				$vars .= '&'.$var.'='.$value;
			}
			else if (property_exists($this, $var))
			{
				$this->$var = $value;
			}
			else
			{
				$GLOBALS[$var] = $value;
			}
		}
		$this->task = !empty($this->task) ? $this->task : 'load';
		$GLOBALS['layout'] = (empty($GLOBALS['layout'])) ? 'subscription' : $GLOBALS['layout'];
		$GLOBALS['vars'] = (($GLOBALS['layout'] == 'subscription') ? '' : '&layout='.$GLOBALS['layout']).$vars;

		if(file_exists('log.php'))
		{
			require_once 'log.php';
			$GLOBALS['paypal_attempts'] = $paypal;
			$GLOBALS['signup_attempts'] = $log;
			$GLOBALS['emails_to_confirm'] = $emails_to_confirm;
			$GLOBALS['stripe_customers_info'] = $stripe_customers_info;
		}

		if (file_exists('auto_config.php'))
		{
			if($GLOBALS['layout'] != 'result')
			{
				require_once 'auto_config.php';
				$GLOBALS['payment_method'] = !empty($payment_method) ? $payment_method : NULL;
				$GLOBALS['packages'] = $packages;
				$GLOBALS['config'] = $config;
				if($GLOBALS['layout'] == 'subscription')
				{
					$this->pack = !empty($_GET['package']) ? (isset($packages[intval($_GET['package'])])?intval($_GET['package']):0) : 0;
					$GLOBALS['set_package'] = $packages[$this->pack];
				}
			}
		}
		else if($this->task == 'ajax' && !empty($_SESSION['config']))
		{
			$GLOBALS['config'] = $_SESSION['config'];
		}
		else if ($GLOBALS['layout'] != 'wizard')
		{
			SignupHelper::Redirect(NULL,'wizard');
		}

		$this->template = empty($config['template']) ? 'default' : $config['template'];
		$this->languages = SignupLocale::getLanguages($GLOBALS['layout'],$this->template);

		if(empty($GLOBALS['lang']))
		{
			$preffered_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			$GLOBALS['lang'] = (in_array($preffered_lang,$this->languages)) ? $preffered_lang : 'en';
			SignupHelper::Redirect(NULL,$GLOBALS['layout'],$GLOBALS['vars']);
		}

		if($GLOBALS['layout'] == 'wizard' || $this->task == 'submit' || $this->task == 'ajax')
		{
			require_once 'mail.php';
			$GLOBALS['mail'] = $mail;
		}

		$GLOBALS['text'] = SignupLocale::getText($GLOBALS['lang'],$GLOBALS['layout']);
		$GLOBALS['error'] = FALSE;
	}

	public function getOutput()
	{
		return call_user_func(array($this,'_'.$GLOBALS['layout'].'Load'));
	}

	public function submitData()
	{
		if (empty($_REQUEST))
		{
			SignupHelper::Redirect(array('type'=>'error','content'=>$GLOBALS['text']['MISSING_ARG']),$GLOBALS['layout']);
		}
		elseif (empty($_SESSION[$GLOBALS['layout'].'_token']) || $_REQUEST[$GLOBALS['layout'].'_token'] != $_SESSION[$GLOBALS['layout'].'_token'])
		{
			if(empty($GLOBALS['paypal_attempts'][$_REQUEST['subscription_token']]))
			{
				if(empty($GLOBALS['emails_to_confirm'][$_REQUEST['subscription_token']]))
				{
					if (empty($GLOBALS['stripe_customers_info'][$_REQUEST['subscription_token']]))
					{
						SignupHelper::Redirect(array('type'=>'error','content'=>$GLOBALS['text']['INVALID_TOKEN']),$GLOBALS['layout']);
					}
				}
			}
		}

		require_once 'libs/submit.php';
		return call_user_func(array('SignupSubmit',$GLOBALS['layout'].'Submit'));
	}

	public function processAjax()
	{
		if(!empty($_REQUEST['act']) && (!empty($_REQUEST[$GLOBALS['layout'].'_token']) && $_REQUEST[$GLOBALS['layout'].'_token'] == $_SESSION[$GLOBALS['layout'].'_token']))
		{
			require_once 'libs/ajax.php';
			return call_user_func_array('SignupAjaxRequest::Ajax'.$_REQUEST['act'],array($_REQUEST));
		}
		else
		{
			return '0';
		}
	}

	private function _subscriptionLoad()
	{
		$error = $notice = $warning = '';
		$log = empty($GLOBALS['signup_attempts']) ? NULL : $GLOBALS['signup_attempts'];
		if ($log)
		{
			$delimiter = !empty($GLOBALS['config']['delimiter']) ? $GLOBALS['config']['delimiter'] : FALSE;
			if ($delimiter)
			{
				$att = $delimiter['att'];
				$period = $delimiter['period'];//hours

				if (!empty($_SERVER['HTTP_CLIENT_IP']))
				{
					$val['ip'] = $_SERVER['HTTP_CLIENT_IP'];
				}
				elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
				{
					$val['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
				}
				else
				{
					$val['ip'] = $_SERVER['REMOTE_ADDR'];
				}

				$val['time'] = time();
				$n = $ipc = 0;
				foreach($log as $value)
				{
					$n++;
					if($delimiter && strtotime($value[0])>($val['time']-$period*3600))
					{
						if ($value[1]==$val['ip'])
						{
							$ipc++;
						}
					}
					else
					{
						continue;
					}
				}

				if ($ipc >= $att)
				{
					$error = $GLOBALS['text']['LIMIT_EXCEEDED'];
				}
			}
		}

		if(isset($_SESSION['sms']))
		{
			unset($_SESSION['sms']);
		}

		$output = array(
			'payment_method' => $GLOBALS['payment_method'],
			'set_package' => $GLOBALS['set_package'],
			'packages' => $GLOBALS['packages'],
			'config' => $GLOBALS['config'],
			'countries_states_list' => SignupHelper::GetStates(),
			'timezones' => SignupHelper::GetTimezones(),
			'progress_bar'=> ((empty($GLOBALS['config']['email_confirm'])) ? 'advanced' : 'simple'),
			'pack' => $this->pack,
			"data" => empty($_SESSION['error']["submitted_data"]) ? array() : $_SESSION['error']["submitted_data"]
		);
		$token = $_SESSION['subscription_token'] = substr(md5(mt_rand()*time()), 0, 20);
		$error = empty($error) ? (empty($_SESSION['error']["error_mes"]) ? '' : $_SESSION['error']["error_mes"]) : $error;
		$warning = empty($_SESSION['warning']) ? '' : $_SESSION['warning'];
		$notice = empty($_SESSION['notice']) ? '' : $_SESSION['notice'];
		return array('output'=>$output,'error'=>$error,'notice'=>$notice,'warning'=>$warning,'token'=>$token);
	}

	private function _resultLoad()
	{
		$notice = $warning = $success =  '';
		if (empty($_SESSION['result']))
		{
			SignupHelper::Redirect(array('type'=>'result','content'=>''),'subscription');
		}
		$output = $_SESSION['result'];
		unset($_SESSION['result']);
		if(isset($output['notice']))
		{
			$notice = $output['notice'];
			unset($output['notice']);
		}
		if(isset($output['warning']))
		{
			$warning = $output['warning'];
			unset($output['warning']);
		}
		$success = $GLOBALS['text']['SUCCESS'];

		return array('output'=>$output,'notice'=>$notice,'warning'=>$warning,'success'=>$success);
	}

	private function _wizardLoad()
	{
		$error = $notice = $warning = $success =  '';
		$output = array();
		$output['authorized'] = $output['config_exists'] = $output['log_exists'] = FALSE;

		if(isset($_SESSION['config']))
		{
			if (file_exists("log.php"))
			{
				$download_log = (!empty($_GET['download']) && $_GET['download'] == 'log') ? TRUE : FALSE;
				if($download_log)
				{
					$log = $GLOBALS['signup_attempts'];
					$filename = 'signup_attempts('.gmdate('Y-m-d',time()).').csv';
					header('Content-Type: application/csv');
					header('Content-Disposition: attachement; filename="'.$filename.'";');
					$f = fopen('php://output','w');
					$n = 0;
					fputcsv($f,explode(',',$GLOBALS['text']['LOG_HEADER']),',');
					foreach($log as $value)
					{
						fputcsv($f,$value,',');
					}
					fclose($f);
					exit;
				}
				$output['log_exists'] = TRUE;
			}

			if (file_exists("auto_config.php"))
			{
				$remove_config = (!empty($_GET['remove']) && $_GET['remove'] == 'auto_config') ? TRUE : FALSE;
				if($remove_config)
				{
					unlink('auto_config.php');
					session_unset();
					session_destroy();
					SignupHelper::Redirect(array('type'=>'result','content'=>''),'wizard');
					exit;
				}
				$output['config_exists'] = TRUE;
				$success = !empty($_SESSION['success']) ? $_SESSION['success'] : '';
				if($success)
				{
					unset($_SESSION['success']);
				}
				else
				{
					$notice = $GLOBALS['text']['CONFIG_EXISTS'];
				}
			}

			// defining values
			$output['authorized'] = TRUE;
			$output['adv_view'] = (!empty($_GET['advanced']) && $_GET['advanced'] == 'true') ? TRUE : FALSE;
			$notice .= ($output['adv_view']) ? (($notice) ? '<br/><strong>'.$GLOBALS['text']['NOTE'].':</strong> ' : '').$GLOBALS['text']['ADV_ON'] : '';
			$warning = ($_SESSION['config']['service'] == 'Admin') ? $GLOBALS['text']['ADMIN_CREDENTIALS'] : $warning;
			$output['server_url'] = (empty($GLOBALS['server_url'])) ? 'mybilling.telinta.com' : $GLOBALS['server_url'];
			$output['owner'] = empty($GLOBALS['subscriber']['i_customer']) ? FALSE : TRUE;
			$output['config'] = array();
			$output['config']['debug'] = empty($GLOBALS['config']['debug']) ? 0 : 1;
			$output['config']['email_confirm'] = empty($GLOBALS['config']['email_confirm']) ? 0 : 1;
			$output['config']['smtp'] =  empty($GLOBALS['config']['smtp']) ? array() : $GLOBALS['config']['smtp'];
			$output['config']['qrcode'] = empty($GLOBALS['config']['qrcode']) ? 0 : 1;
			$output['config']['delimiter'] = empty($GLOBALS['config']['delimiter']) ? array() : $GLOBALS['config']['delimiter'];
			$output['config']['sms'] = empty($GLOBALS['config']['sms']) ? array() : $GLOBALS['config']['sms'];
			$output['config']['captcha'] = empty($GLOBALS['config']['captcha']) ? array() : $GLOBALS['config']['captcha'];
			$output['config']['server_url'] = empty($GLOBALS['config']['server_url']) ? 'mybilling.telinta.com' : $GLOBALS['config']['server_url'];
			$output['config']['references'] = empty($GLOBALS['config']['references']) ? array('account_sci'=>'https://mybilling.telinta.com:8445','customer_sci'=>'https://mybilling.telinta.com:8444') : $GLOBALS['config']['references'];
			$output['config']['submit_note'] = empty($GLOBALS['config']['submit_note']) ? '' : $GLOBALS['config']['submit_note'];
			$output['subscriber'] = array();
			$output['subscriber']['id_source'] = empty($GLOBALS['subscriber']['id_source']) ? 'DID' : $GLOBALS['subscriber']['id_source'];
			$output['subscriber']['id_length'] = empty($GLOBALS['subscriber']['id_length']) ? 10 : $GLOBALS['subscriber']['id_length'];
			$output['subscriber']['prefix'] = empty($GLOBALS['subscriber']['prefix']) ? '' : $GLOBALS['subscriber']['prefix'];
			$output['subscriber']['alias'] = empty($GLOBALS['subscriber']['alias']) ? 0 : $GLOBALS['subscriber']['alias'];
			$output['payment_method'] = empty($GLOBALS['payment_method']) ? array() : $GLOBALS['payment_method'];
			$output['packages'] = empty($GLOBALS['packages']) ? array(array()) : $GLOBALS['packages'];
			$output['products'] = isset($_SESSION['products']) ? $_SESSION['products'] : '{}';
			$output['subscriptions'] = isset($_SESSION['subscriptions']) ? $_SESSION['subscriptions'] : '{}';
			$output['voffice'] = isset($_SESSION['voffice']) ? $_SESSION['voffice'] : 'null';
			$output['payment_method_names'] = array('MasterCard', 'VISA', 'American Express', 'Discover', 'Maestro', 'PayPal');
			$output['template'] = empty($GLOBALS['template']) ? 'default' : $GLOBALS['template'];
			$output['ownerbatch'] = isset($_SESSION['ownerbatch']) ? $_SESSION['ownerbatch'] : '{}';
			$output['sms_providers'] = array('CSoft','Twilio','MessageMedia','Nexmo','Infobip','Macrotechnology','Clubtexting');
			$output['cus_login_sources'] = array(
				'email' => $GLOBALS['text']['CUS_LOGIN_SOURCE_EMAIL'],
				'firstname_lastname' => $GLOBALS['text']['CUS_LOGIN_SOURCE_FIRST_LAST_NAME'],
				'email_rand_digits' => $GLOBALS['text']['CUS_LOGIN_SOURCE_EMAIL_RAND'],
			);
			$output['countries'] = SignupHelper::GetStates();

			$templates = scandir('templates/');
			foreach($templates as $key => $dir_name)
			{
				if (is_dir('templates/'.$dir_name.'/')
						&& file_exists('templates/'.$dir_name.'/index.php')
						&& !in_array($dir_name,array('.','..')))
				{
					continue;
				}
				else
				{
					unset($templates[$key]);
				}
			}
			$output['templates'] = $templates;
			$output['mail'] = $GLOBALS['mail'];
		}

		$error = (!empty($_SESSION['error'])) ? $_SESSION['error'] : $error;

		$token = $_SESSION['wizard_token'] = substr(md5(mt_rand()*time()), 0, 10);
		return array('output'=>$output,'error'=>$error,'notice'=>$notice,'warning'=>$warning,'success'=>$success,'token'=>$token);
	}
}
?>
