<?php
defined('SIGNUP') or die('Restricted access');

Abstract Class SignupHelper
{
	public static function GetRootPath()
	{
		return str_replace('index.php','',$_SERVER['PHP_SELF']);
	}

	public static function MakeSoapCall($method=NULL,$type=NULL,$request=NULL,$service_type='')
	{
		$error = array();
		global $error, $config;

		static $soap_client;
		if (empty($soap_client))
		{
			require_once 'libs/soap_lib.php';

			do
			{
				if (is_array($config['service']))
				{
					if(count($config['service']) > 1)
					{
						reset($config['service']);
						$first_key = key($config['service']);
						$service = $config['service'][$first_key];
					}
					else
					{
						$config['service'] = implode('', $config['service']);
						$service = $config['service'];
					}
				}
				else
				{
					$service = $config['service'];
				}
				try
				{
					$soap_client = new SoapWrapper($config['server_url'], $config['login'], $config['password'], $service);
					$GLOBALS['session_id'] = $soap_client->get_session_id();
				}
				catch (SoapFault $e)
				{
					if (is_array($config['service']))
					{
						unset($config['service'][$first_key]);
					}
					else
					{
						$error[] = $e->getMessage();
						return FALSE;
					}
				}
				if(empty($e) && is_array($config['service']))
				{
					$config['service'] = $config['service'][$first_key];
				}
			} while (is_array($config['service']));
		}
		if ($method == 'end_session')
		{
			$soap_client->end_session($GLOBALS['session_id']);
			return TRUE;
		}
		else if ($method)
		{
			try
			{
				$response = call_user_func_array(array($soap_client, $method), array($type,$service_type,$request));
			}
			catch (SoapFault $e)
			{
				$debug = debug_backtrace();
				$file = $debug[0]['file'];
				$line = $debug[0]['line'];
				$error[] = $e->getMessage() . ' Method "'.$method.'" in "'.$file.'" (line: '.$line.')';

				return FALSE;
			}

			return $response;
		}
	}

	public static function SortProdAndSubsc(&$array)
	{
		reset($array);
		foreach($array as $i => $val)
		{
			${'tmp_'.$i} = array();
			foreach($val as $j => $value)
			{
				${'tmp_'.$i}[$j] = $value;
			}
		}
		foreach($array as $i => $val)
		{
			natsort(${'tmp_'.$i});
			$array[$i] = ${'tmp_'.$i};
		}
	}

	public static function PrepareString($string)
	{
		return (!empty($string)) ? trim(htmlspecialchars (strip_tags($string),ENT_QUOTES)) : (($string == "0") ? "0" : '');
	}

	public static function Redirect($data,$layout,$vars='')
	{
		$layout = ($layout == 'subscription') ? '' : ((strpos($vars,'layout=') === FALSE) ? '&layout='.$layout : '');
		if (!empty($data['content']))
		{
			$_SESSION[$data['type']] = $data['content'];
		}
		$lang = empty($GLOBALS['lang']) ? 'en' : $GLOBALS['lang'];
		$location = $GLOBALS['root_path'].'?lang='.$lang.$layout.$vars;
		header('Location: '.$location);
		exit;
	}

	public static function SendMail($to,$from,$subject,$message,$mime_boundary=FALSE,$filename=FALSE,$data=FALSE,$source_message=FALSE)
	{
		global $config;
		if(isset($config['smtp'])){
			include_once('Mail.php');
			include_once('Mail/mime.php');
			if(class_exists('Mail') === true){
				$headers = array(
					'From' => $from,
					'To' => $to,
					'Subject' => $subject,
				);
				$source_message = utf8_decode($source_message);
				if($mime_boundary){
					$mime = new Mail_mime("\r\n");
					$mime->setTXTBody($source_message);
					$mime->setHTMLBody($source_message);
					$mime->addAttachment($data, 'image/png', $filename, false, 'base64', 'attachment','UTF-8');
					$message = $mime->get();
					$headers = $mime->headers($headers);
				}
				$smtp = Mail::factory('smtp', array(
					'host' => $config['smtp']['host'],
					'port' => $config['smtp']['port'],
					'auth' => true,
					'username' => $config['smtp']['user'],
					'password' => $config['smtp']['pwd']
					)
				);
				$mail = $smtp->send($to, $headers, $message);
				if (PEAR::isError($mail)) {
					error_log("Failed to send an email via Pear: " . $mail->getMessage() . "\n");
				}
			}
		} else {
			$content_type = $mime_boundary ? "Content-Type: multipart/mixed;\n" . " boundary=\"".$mime_boundary."\"" : 'Content-type: text/html; charset=UTF-8';
			$headers = 'From: ' . $from . "\r\n";
			$headers .= 'MIME-Version: 1.0' . "\r\n" . $content_type . "\r\n";
			mail($to,$subject,$message,$headers);
		}
	}

	public static function WriteCompletePercentage($percent,$unlink=FALSE)
	{
		global $config;
		if(!empty($config['email_confirm']) && !$unlink)
		{
			return;
		}
		$file = $_SESSION[$GLOBALS['layout'].'_token'].'.json';
		if($unlink)
		{
			if(file_exists($file))
			{
				unlink($file);
			}
			unset($_SESSION[$GLOBALS['layout'].'_token']);
		}
		else
		{
			$fh=fopen($file, 'w');
			fwrite($fh,json_encode(array('complete'=>$percent)));
			fclose($fh);
		}
	}

	public static function GetUrl()
	{
		$pageURL = ((@$_SERVER["HTTPS"] == "on") ? "https://" : "http://").
			$_SERVER["SERVER_NAME"].(($_SERVER["SERVER_PORT"] != "80") ? ":".$_SERVER["SERVER_PORT"] : '')
			.$_SERVER["REQUEST_URI"];
		return $pageURL;
	}

	public static function VarToString($values,$var_name,$array_key='')
	{
		$string = "\n".'$'.$var_name.'[\''.$array_key.'\'] = array(';
		$j = 0;
		foreach($values as $key => $value)
		{
			$string .= (($j)?',':'');
			if(gettype($value) == 'array')
			{
				$string .= "'".$key."'=>array(";
				$i = 0;
				foreach($value as $k => $v)
				{
					$string .= (($i)?',':'');
					if(gettype($v) == 'array')
					{
						$string .= "'".$key."'=>array(";
						$m = 0;
						foreach($v as $l => $val)
						{
							$string .= (($m)?',':'')."'".$l."'=>'".self::PrepareString($val)."'";
							$m++;
						}
						$string .= ')';
					}
					else
					{
						$string .= "'".$k."'=>'".self::PrepareString($v)."'";
						$i++;
					}
				}
				$string .= ')';
			}
			else
			{
				$string .= "'".$key."'=>'".self::PrepareString($value)."'";
			}
			$j++;
		}
		$string .= ');';

		return $string;
	}

	public static function GetStates()
	{
		return array(
			'AF' => array ('country' => 'AFGHANISTAN', 'states' => array ( '4' => 'Balkh', '5' => 'Bamian', '2' => 'Badghis', '1' => 'Badakhshan', '3' => 'Baghlan', '6' => 'Farah', '7' => 'Faryab', '8' => 'Ghazni', '9' => 'Ghowr', '10' => 'Helmand', '11' => 'Herat', '12' => 'Jowzjan', '13' => 'Kabul', '14' => 'Kandahar', '15' => 'Kapisa', '17' => 'Kondoz', '16' => 'Konar', '18' => 'Laghman', '19' => 'Lowgar', '20' => 'Nangrahar', '21' => 'Nimruz', '22' => 'Oruzgan', '25' => 'Parwan', '23' => 'Paktia', '24' => 'Paktika', '26' => 'Samangan', '27' => 'Sar-e Pol', '28' => 'Takhar', '29' => 'Wardak', '30' => 'Zabol', ), ),
			'AX' => array ('country' => 'ALAND ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AL' => array ('country' => 'ALBANIA', 'states' => array ( '31' => 'Berat', '32' => 'Bulqiz', '35' => 'Dibr', '33' => 'Delvin', '34' => 'Devoll', '37' => 'Elbasan', '43' => 'Kolonj', '38' => 'Fier', '39' => 'Gjirokastr', '40' => 'Gramsh', '41' => 'Has', '42' => 'Kavaj', '47' => 'Kuov', '44' => 'Kor', '45' => 'Kruj', '46' => 'Kuks', '48' => 'La', '50' => 'Librazhd', '49' => 'Lezh', '51' => 'Lushnj', '52' => 'Mallakastr', '53' => 'Malsia e Madhe', '55' => 'Mirdit', '54' => 'Mat', '57' => 'Pogradec', '56' => 'Peqin', '59' => 'Prmet', '58' => 'Puk', '61' => 'Shkodr', '62' => 'Skrapar', '60' => 'Sarand', '63' => 'Tepelen', '65' => 'Tropoj', '64' => 'Tiran', '66' => 'Vlor', ), ),
			'DZ' => array ('country' => 'ALGERIA', 'states' => array ( '67' => 'Adrar', '80' => 'Chlef', '91' => 'Laghouat', '100' => 'Oum el Bouaghi', '72' => 'Batna', '79' => 'Bjaa', '73' => 'Biskra', '78' => 'Bchar', '74' => 'Blida', '76' => 'Bouira', '107' => 'Tamanghasset', '114' => 'Tbessa', '113' => 'Tlemcen', '108' => 'Tiaret', '112' => 'Tizi Ouzou', '69' => 'Alger', '82' => 'Djelfa', '89' => 'Jijel', '106' => 'Stif', '102' => 'Sada', '104' => 'Skikda', '103' => 'Sidi Bel Abbs', '70' => 'Annaba', '87' => 'Guelma', '81' => 'Constantine', '96' => 'Mda', '94' => 'Mostaganem', '95' => 'Msila', '92' => 'Mascara', '99' => 'Ouargla', '98' => 'Oran', '83' => 'El Bayadh', '88' => 'Illizi', '75' => 'Bordj Bou Arrridj', '77' => 'Boumerds', '85' => 'El Tarf', '109' => 'Tindouf', '111' => 'Tissemsilt', '84' => 'El Oued', '90' => 'Khenchela', '105' => 'Souk Ahras', '110' => 'Tipaza', '93' => 'Mila', '68' => 'Ain Defla', '97' => 'Naama', '71' => 'An T6mouchent', '86' => 'Ghardaa', '101' => 'Relizane', ), ),
			'AS' => array ('country' => 'AMERICAN SAMOA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AD' => array ('country' => 'ANDORRA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AO' => array ('country' => 'ANGOLA', 'states' => array ( '115' => 'Bengo', '116' => 'Benguela', '117' => 'Bi', '118' => 'Cabinda', '119' => 'Cuando-Cubango', '122' => 'Cunene', '120' => 'Cuanza Norte', '121' => 'Cuanza Sul', '123' => 'Huambo', '124' => 'Hula', '126' => 'Lunda Norte', '127' => 'Lunda Sul', '125' => 'Luanda', '128' => 'Malange', '129' => 'Moxico', '130' => 'Namibe', '131' => 'Uge', '132' => 'Zare', ), ),
			'AI' => array ('country' => 'ANGUILLA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AQ' => array ( 'country' => 'ANTARCTICA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AG' => array ( 'country' => 'ANTIGUA AND BARBUDA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AR' => array ( 'country' => 'ARGENTINA', 'states' => array ( '148' => 'Salta', '133' => 'Buenos Aires', '134' => 'Capital federal', '150' => 'San Luis', '140' => 'Entre Ros', '153' => 'Santiago del Estero', '136' => 'Chaco', '149' => 'San Juan', '135' => 'Catamarca', '143' => 'La Pampa', '144' => 'Mendoza', '145' => 'Misiones', '141' => 'Formosa', '146' => 'Neuqun', '147' => 'Ro Negro', '152' => 'Santa Fe', '155' => 'Tucumn', '137' => 'Chubut', '154' => 'Tierra del Fuego', '138' => 'Corrientes', '139' => 'Crdoba', '142' => 'Jujuy', '151' => 'Santa Cruz', ), ),
			'AM' => array ( 'country' => 'ARMENIA', 'states' => array ( '156' => 'Aragacotn', '157' => 'Ararat', '158' => 'Armavir', '159' => 'Erevan', '160' => 'Gegark\'unik\'', '161' => 'Kotayk\'', '162' => 'Lory', '163' => 'Sirak', '164' => 'Syunik\'', '165' => 'Tavus', '166' => 'Vayoc Jor', ), ),
			'AW' => array ( 'country' => 'ARUBA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'AU' => array ( 'country' => 'AUSTRALIA', 'states' => array ( '167' => 'Australian Capital Territory', '168' => 'New South Wales', '169' => 'Northern Territory', '170' => 'Queensland', '171' => 'South Australia', '172' => 'Tasmania', '173' => 'Victoria', '174' => 'Western Australia', ), ),
			'AT' => array ( 'country' => 'AUSTRIA', 'states' => array ( '175' => 'Burgenland', '176' => 'Krnten', '177' => 'Niedersterreich', '178' => 'Obersterreich', '179' => 'Salzburg', '180' => 'Steiermark', '181' => 'Tirol', '182' => 'Vorarlberg', '183' => 'Wien', ), ),
			'AZ' => array ( 'country' => 'AZERBAIJAN', 'states' => array ( '256' => 'li Bayramli', '184' => 'Abseron', '188' => 'Agstafa', '185' => 'Agcabdi', '186' => 'Agdam', '187' => 'Agdas', '189' => 'Agsu', '190' => 'Astara', '195' => 'Bki', '191' => 'Babk', '192' => 'Balakn', '196' => 'Brd', '193' => 'Beylgan', '194' => 'Bilsuvar', '198' => 'Cbrayll', '199' => 'Clilabad', '197' => 'Culfa', '200' => 'Dasksn', '201' => 'Dvi', '202' => 'Fuzuli', '205' => 'Gnc', '204' => 'Gdby', '203' => 'Goranboy', '206' => 'Gyay', '207' => 'Haciqabul', '208' => 'Imisli', '209' => 'Ismayilli', '211' => 'Klbcr', '210' => 'Kurdmir', '212' => 'Lain', '214' => 'Lnkran', '213' => 'Lerik', '215' => 'Masalli', '216' => 'Mingevir', '218' => 'Naxivan', '217' => 'Naftalan', '219' => 'Neftcala', '220' => 'Oguz', '221' => 'Ordubad', '228' => 'Qbl', '222' => 'Qax', '223' => 'Qazax', '225' => 'Quba', '226' => 'Qubadli', '224' => 'Qobustan', '227' => 'Qusar', '230' => 'Sabirabad', '231' => 'Sadarak', '232' => 'Sahbuz', '239' => 'Ski', '233' => 'Salyan', '241' => 'Srur', '229' => 'Saatli', '236' => 'Siyzn', '240' => 'Smkir', '237' => 'Sumqayit', '234' => 'Samaxi', '235' => 'Samux', '238' => 'Susa', '242' => 'Tartar', '243' => 'Tovuz', '244' => 'Ucar', '245' => 'Xankandi', '247' => 'Xamaz', '246' => 'Xanlar', '249' => 'Xocali', '248' => 'Xizi', '250' => 'Xocavand', '251' => 'Yardimli', '252' => 'Yevlax', '254' => 'Zngilan', '253' => 'Zaqatala', '255' => 'Zrdab', ), ),
			'BS' => array ( 'country' => 'BAHAMAS', 'states' => array ( '257' => 'Acklins and Crooked Islands', '258' => 'Bimini', '259' => 'Cat Island', '260' => 'Exuma', '262' => 'Fresh Creek', '261' => 'Freeport', '263' => 'Governor\'s Harbour', '264' => 'Green Turtle Cay', '265' => 'Harbour Island', '266' => 'High Rock', '267' => 'Inagua', '268' => 'Kemps Bay', '269' => 'Long Island', '271' => 'Mayaguana', '270' => 'Marsh Harbour', '273' => 'Nicholls Town and Berry Islands', '272' => 'New Providence', '274' => 'Ragged Island', '275' => 'Rock Sound', '277' => 'Sandy Point', '276' => 'San Salvador and Rum Cay', ), ),
			'BH' => array ( 'country' => 'BAHRAIN', 'states' => array ( '278' => 'Al Hadd', '283' => 'Al Muharraq', '279' => 'Al Manamah', '285' => 'Jidd Hafs', '282' => 'Al Mintaqah ash Shamaliyah', '289' => 'Sitrah', '280' => 'Al Mintagah al Wust', '286' => 'Madluat Is', '284' => 'Ar Rifa', '281' => 'Al Mintaqah al Gharbiyah', '288' => 'Mintaqat Juzur tawar', '287' => 'Madluat Jamad', ), ),
			'BD' => array ( 'country' => 'BANGLADESH', 'states' => array ( '291' => 'Bandarban zila', '292' => 'Barguna zila', '295' => 'Bogra zila', '296' => 'Brahmanbaria zila', '290' => 'Bagerhat zila', '293' => 'Barisal zila', '294' => 'Bhola zila', '300' => 'Comilla zila', '297' => 'Chandpur zila', '298' => 'Chittagong zila', '301' => 'Cox\'s Bazar zila', '299' => 'Chuadanga zila', '302' => 'Dhaka zila', '303' => 'Dinajpur zila', '304' => 'Faridpur zila', '305' => 'Feni zila', '308' => 'Gopalganj zila', '307' => 'Gazipur zila', '306' => 'Gaibandha zila', '309' => 'Habiganj zila', '311' => 'Jamalpur zila', '312' => 'Jessore zila', '314' => 'Jhenaidah zila', '310' => 'Jaipurhat zila', '313' => 'Jhalakati zila', '317' => 'Kishorganj zila', '316' => 'Khulna zila', '318' => 'Kurigram zila', '315' => 'Khagrachari zila', '319' => 'Kushtia zila', '320' => 'Lakshmipur zila', '321' => 'Lalmonirhat zila', '324' => 'Manikganj zila', '328' => 'Mymensingh zila', '327' => 'Munshiganj zila', '322' => 'Madaripur zila', '323' => 'Magura zila', '326' => 'Moulvibazar zila', '325' => 'Meherpur zila', '331' => 'Narayanganj zila', '335' => 'Netrakona zila', '332' => 'Narsingdi zila', '330' => 'Narail zila', '333' => 'Natore zila', '334' => 'Nawabganj zila', '336' => 'Nilphamari zila', '337' => 'Noakhali zila', '329' => 'Naogaon zila', '338' => 'Pabna zila', '341' => 'Pirojpur zila', '340' => 'Patuakhali zila', '339' => 'Panchagarh zila', '342' => 'Rajbari zila', '343' => 'Rajshahi zila', '345' => 'Rangpur zila', '344' => 'Rangamati zila', '348' => 'Sherpur zila', '346' => 'Satkhira zila', '349' => 'SirajOanj zila', '351' => 'Sylhet zila', '350' => 'SunamOanj zila', '347' => 'Shariatpur zila', '352' => 'Tangail zila', '353' => 'Thakurgaon zila', ), ),
			'BB' => array ( 'country' => 'BARBADOS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'BY' => array ( 'country' => 'BELARUS', 'states' => array ( '354' => 'Bresckaja voblasts\'', '355' => 'Homyel\'skaya voblasts\'', '356' => 'Hrodzenskaya voblasts\'', '357' => 'Mahilyowskaya voblasts\'', '358' => 'Minskaya voblasts\'', '359' => 'Vitsyebskaja voblasts\'', ), ),
			'BE' => array ( 'country' => 'BELGIUM', 'states' => array ( '360' => 'Antwerpen', '368' => 'Vlaams Brabant', '363' => 'Limburg', '367' => 'Oost-Vlaanderen', '369' => 'West-Vlaanderen', '361' => 'Brabant Wallon', '362' => 'Hainaut', '364' => 'Lige', '365' => 'Luxembourg', '366' => 'Namur', ), ),
			'BZ' => array ( 'country' => 'BELIZE', 'states' => array ( '370' => 'Belize', '371' => 'Cayo', '372' => 'Corozal', '373' => 'Orange Walk', '374' => 'Stann Creek', '375' => 'Toledo', ), ),
			'BJ' => array ( 'country' => 'BENIN', 'states' => array ( '376' => 'Atakora', '377' => 'Atlantique', '378' => 'Borgou', '379' => 'Mono', '380' => 'Oum', '381' => 'Zou', ), ),
			'BM' => array ( 'country' => 'BERMUDA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'BT' => array ( 'country' => 'BHUTAN', 'states' => array ( '389' => 'Paro', '383' => 'Chhukha', '386' => 'Ha', '393' => 'Samtee', '395' => 'Thimphu', '399' => 'Tsirang', '384' => 'Dagana', '391' => 'Punakha', '400' => 'Wangdue Phodrang', '394' => 'Sarpang', '398' => 'Trongsa', '382' => 'Bumthang', '401' => 'Zhemgang', '397' => 'Trashigang', '388' => 'Monggar', '390' => 'Pemagatshel', '387' => 'Lhuentse', '392' => 'Samdrup Jongkha', '385' => 'Gasa', '396' => 'Trashi Yangtse', ), ),
			'BO' => array ( 'country' => 'BOLIVIA', 'states' => array ( '404' => 'El Beni', '403' => 'Cochabamba', '402' => 'Chuquisaca', '405' => 'La Paz', '407' => 'Pando', '406' => 'Oruro', '408' => 'Potos', '409' => 'Santa Cruz', '410' => 'Tarija', ), ),
			'BA' => array ( 'country' => 'BOSNIA AND HERZEGOVINA', 'states' => array ( '411' => 'Federacija Bosna i Hercegovina', '412' => 'Republika Srpska', ), ),
			'BW' => array ( 'country' => 'BOTSWANA', 'states' => array ( '413' => 'Central (Serowe-Palapye)', '415' => 'Ghanzi', '416' => 'Kgalagadi', '417' => 'Kgatleng', '418' => 'Kweneng', '420' => 'North-East', '419' => 'Ngamiland', '3521' => 'North-West', '421' => 'South-East', '422' => 'Southern (Ngwaketse)', ), ),
			'BV' => array ( 'country' => 'BOUVET ISLAND', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'BR' => array ( 'country' => 'BRAZIL', 'states' => array ( '423' => 'Acre', '424' => 'Alagoas', '426' => 'Amazonas', '425' => 'Amap', '427' => 'Baia', '428' => 'Cear', '429' => 'Distrito Federal', '430' => 'Espirito Santo', '431' => 'Fernando de Noronha', '432' => 'Gois', '433' => 'Maranhao', '436' => 'Minas Gerais', '435' => 'Mato Grosso do Sul', '434' => 'Mato Grosso', '439' => 'Par', '438' => 'Paraba', '440' => 'Pernambuco', '441' => 'Piau', '437' => 'Paran', '444' => 'Rio de Janeiro', '442' => 'Rio Grande do Norte', '445' => 'Rondonia', '446' => 'Roraima', '443' => 'Rio Grande do Sul', '447' => 'Santa Catarina', '449' => 'Sergipe', '448' => 'Sao Paulo', '450' => 'Tocatins', ), ),
			'IO' => array ( 'country' => 'BRITISH INDIAN OCEAN TERRITORY', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'BN' => array ( 'country' => 'BRUNEI DARUSSALAM', 'states' => array ( '451' => 'Belait', '452' => 'Brunei-Muara', '453' => 'Temburong', '454' => 'Tutong', ), ),
			'BG' => array ( 'country' => 'BULGARIA', 'states' => array ( '462' => 'Sofija-Grad', '455' => 'Burgas', '463' => 'Varna', '457' => 'Lovec', '458' => 'Montana', '459' => 'Plovdiv', '460' => 'Ruse', '461' => 'Sofija', '456' => 'Haskovo', ), ),
			'BF' => array ( 'country' => 'BURKINA FASO', 'states' => array ( '464' => 'Bal', '465' => 'Bam', '466' => 'Banwa', '467' => 'Bazega', '468' => 'Bougouriba', '469' => 'Boulgou', '470' => 'Boulkiemd', '471' => 'Como', '472' => 'Ganzourgou', '473' => 'Gnagna', '474' => 'Gourma', '475' => 'Houet', '476' => 'Ioba', '477' => 'Kadiogo', '484' => 'Kndougou', '478' => 'Komondjari', '479' => 'Kompienga', '481' => 'Koulplogo', '480' => 'Kossi', '482' => 'Kouritenga', '483' => 'Kourwogo', '486' => 'Lraba', '485' => 'Loroum', '487' => 'Mouhoun', '489' => 'Namentenga', '488' => 'Nahouri', '490' => 'Nayala', '491' => 'Noumbiel', '492' => 'Oubritenga', '493' => 'Oudalan', '494' => 'Passor', '495' => 'Poni', '501' => 'Sno', '498' => 'Siasili', '497' => 'Sanmatenga', '496' => 'Sangui', '499' => 'Soum', '500' => 'Sourou', '502' => 'Tapoa', '503' => 'Tui', '504' => 'Yagha', '505' => 'Yatenga', '506' => 'Ziro', '507' => 'Zondoma', '508' => 'Zoundwogo', ), ),
			'BI' => array ( 'country' => 'BURUNDI', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'KH' => array ( 'country' => 'CAMBODIA', 'states' => array ( '510' => 'Banteay Mean Chey', '517' => 'Krachoh', '519' => 'Mondol Kiri', '521' => 'Phnom Penh', '523' => 'Preah Vihear', '524' => 'Prey Veaeng', '522' => 'Pousaat', '526' => 'Rotanak Kiri', '527' => 'Siem Reab', '531' => 'Xrong Preah Sihanouk', '528' => 'Stueng Traeng', '509' => 'Baat Dambang', '529' => 'Svaay Rieng', '530' => 'Taakaev', '520' => 'Otdar Mean Chey', '518' => 'Krong Kaeb', '525' => 'Rampong Chaam', '512' => 'Kampong Chhnang', '513' => 'Kampong Spueu', '514' => 'Kampong Thum', '515' => 'Kampot', '516' => 'Kandaal', '511' => 'Kach Kong', ), ),
			'CM' => array ( 'country' => 'CAMEROON', 'states' => array ( '532' => 'Adamaoua', '533' => 'Centre', '535' => 'Far North', '534' => 'East', '536' => 'Littoral', '537' => 'North', '538' => 'North-West', '541' => 'West', '539' => 'South', ), ),
			'CA' => array ( 'country' => 'CANADA', 'states' => array ( '542' => 'Alberta', '543' => 'British Columbia', '544' => 'Manitoba', '545' => 'New Brunswick', '546' => 'Newfoundland', '548' => 'Nova Scotia', '547' => 'Northwest Territories', '549' => 'Nunavut', '550' => 'Ontario', '551' => 'Prince Edward Island', '552' => 'Quebec', '553' => 'Saskatchewan', '554' => 'Yukon Territory', ), ),
			'CV' => array ( 'country' => 'CAPE VERDE', 'states' => array ( '556' => 'Brava', '555' => 'Boa Vista', '564' => 'Santa Catarina', '565' => 'Santa Cruz', '557' => 'Fogo', '558' => 'Maio', '559' => 'Paul', '560' => 'Porto Novo', '561' => 'Praia', '562' => 'Ribeira Grande', '563' => 'Sal', '566' => 'Sao Nicolau', '567' => 'Sao Vicente', '568' => 'Tarrafal', ), ),
			'KY' => array ( 'country' => 'CAYMAN ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'CF' => array ( 'country' => 'CENTRAL AFRICAN REPUBLIC', 'states' => array ( '582' => 'Ouham', '570' => 'Bamingui-Bangoran', '571' => 'Bangui', '569' => 'Baase-Kotto', '573' => 'Haute-Kotto', '572' => 'Haut-Mbomou', '576' => 'Mambr-Kad', '578' => 'Nana-Grbizi', '574' => 'Kmo', '575' => 'Lobaye', '577' => 'Mbomou', '580' => 'Ombella-Mpoko', '579' => 'Nana-Mambr', '583' => 'Ouham-Pend', '584' => 'Sangha-Mbar', '581' => 'Ouaka', '585' => 'Vakaga', ), ),
			'TD' => array ( 'country' => 'CHAD', 'states' => array ( '586' => 'Batha', '588' => 'Borkou-Ennedi-Tibesti', '587' => 'Biltine', '589' => 'Chari-Baguirmi', '590' => 'Gura', '591' => 'Kanem', '592' => 'Lac', '593' => 'Logone-Occidental', '594' => 'Logone-Oriental', '596' => 'Moyen-Chari', '595' => 'Mayo-Kbbi', '597' => 'Ouadda', '598' => 'Salamat', '599' => 'Tandjil', ), ),
			'CL' => array ( 'country' => 'CHILE', 'states' => array ( '600' => 'Aisn del General Carlos Ibez del Campo', '601' => 'Antofagasta', '602' => 'Araucana', '603' => 'Atacama', '604' => 'Bo-Bo', '605' => 'Coquimbo', '606' => 'Libertador General Bernardo O\'Higgins', '607' => 'Los Lagos', '608' => 'Magallanes', '609' => 'Maule', '610' => 'Region Metropolitana de Santiago', '611' => 'Tarapac', '612' => 'Valparaso', ), ),
			'CN' => array ( 'country' => 'CHINA', 'states' => array ( '614' => 'Beijing', '641' => 'Tianjin', '622' => 'Hebei', '638' => 'Shanxi', '632' => 'Nei Monggol', '631' => 'Liaoning', '630' => 'Jilin', '623' => 'Heilongjiang', '637' => 'Shanghai', '628' => 'Jiangsu', '645' => 'Zhejiang', '613' => 'Anhui', '616' => 'Fujian', '629' => 'Jiangxi', '636' => 'Shandong', '624' => 'Henan', '626' => 'Hubei', '627' => 'Hunan', '618' => 'Guangdong', '619' => 'Guangxi', '621' => 'Hainan', '615' => 'Chongqing', '639' => 'Sichuan', '620' => 'Gulzhou', '644' => 'Yunnan', '643' => 'Xizang', '635' => 'Shaanxi', '617' => 'Gansu', '634' => 'Qinghai', '633' => 'Ningxia', '642' => 'Xinjiang', '640' => 'Taiwan', '625' => 'Hong Kong', ), ),
			'CX' => array ( 'country' => 'CHRISTMAS ISLAND', 'states' => array ( '646' => 'Jihocesky kraj', '647' => 'Jihomoravsky kraj', '649' => 'Severoceaky kraj', '650' => 'Soveromoravsky kraj', '651' => 'Stredocesky kraj', '652' => 'Vychodocesky kraj', '653' => 'Zpadocesky kraj', '648' => 'Praha', ), ),
			'CC' => array ( 'country' => 'COCOS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'CO' => array ( 'country' => 'COLOMBIA', 'states' => array ( '654' => 'Amazonea', '655' => 'Antioquia', '656' => 'Arauca', '657' => 'Atlntico', '658' => 'Bolvar', '659' => 'Boyac', '660' => 'Caldea', '661' => 'Caquet', '662' => 'Casanare', '663' => 'Cauca', '664' => 'Cesar', '665' => 'Choc', '667' => 'Crdoba', '666' => 'Cundinamarca', '668' => 'Distrito Capltal de Santa Fe de Bogot', '669' => 'Guaina', '670' => 'Guaviare', '671' => 'Huila', '672' => 'La Guajira', '673' => 'Magdalena', '674' => 'Meta', '675' => 'Nario', '676' => 'Norte de Santander', '677' => 'Putumayo', '678' => 'Quindo', '679' => 'Risaralda', '681' => 'Santander', '680' => 'San Andrs, Providencia y Santa Catalina', '682' => 'Sucre', '683' => 'Tolima', '684' => 'Valle del Cauca', '685' => 'Vaups', '686' => 'Vichada', ), ),
			'KM' => array ( 'country' => 'COMOROS', 'states' => array ( '687' => 'Anjouan Ndzouani', '688' => 'Grande Comore Ngazidja', '689' => 'Mohli Moili', ), ),
			'CG' => array ( 'country' => 'CONGO, Republic of', 'states' => array ( '690' => 'Bouenza', '699' => 'Pool', '700' => 'Sangha', '698' => 'Plateaux', '693' => 'Cuvette-Ouest', '696' => 'Lkoumou', '694' => 'Kouilou', '695' => 'Likouala', '692' => 'Cuvette', '697' => 'Niari', '691' => 'Brazzaville', ), ),
			'CD' => array ( 'country' => 'CONGO, Democratic Republic of (was Zaire)', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'CK' => array ( 'country' => 'COOK ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'CR' => array ( 'country' => 'COSTA RICA', 'states' => array ( '701' => 'Alajuela', '702' => 'Cartago', '703' => 'Guanacaste', '704' => 'Heredia', '705' => 'Limn', '706' => 'Puntarenas', '707' => 'San Jos', ), ),
			'CI' => array ( 'country' => 'COTE D\'IVOIRE', 'states' => array ( '714' => 'Lagunes', '712' => 'Haut-Sassandra', '718' => 'Savanes', '721' => 'Valle du Bandama', '716' => 'Moyen-Como', '708' => '18 Montagnes', '713' => 'Lacs', '723' => 'Zanzan', '710' => 'Bas-Sassandra', '711' => 'Dengul', '717' => 'Nzi-Como', '715' => 'Marahou', '720' => 'Sud-Como', '722' => 'Worodouqou', '719' => 'Sud-Bandama', '709' => 'Agnbi', ), ),
			'HR' => array ( 'country' => 'CROATIA', 'states' => array ( '743' => 'Zagrebacka zupanija', '730' => 'Krapinako-zagorska zupanija', '737' => 'Sisasko-moelavacka Iupanija', '728' => 'Karlovacka zupanija', '739' => 'Varaidinska zupanija', '729' => 'Koprivoickco-krizeva6ka zupanija', '724' => 'Bjelovarsko-bilogorska zupanija', '735' => 'Primorsko-goranska zupanija', '731' => 'Licko-senjska zupanija', '740' => 'VirovitiEko-podravska zupanija', '734' => 'Pozesko-slavonska zupanija', '725' => 'Brodsko-posavska zupanija', '742' => 'Zadaraka', '733' => 'Osjecko-baranjska zupanija', '736' => 'Sibenako-kninska zupanija', '741' => 'VuRovarako-srijemska zupanija', '738' => 'Splitako-dalmatinska zupanija', '727' => 'Istarska zupanija', '726' => 'Dubrovacko-neretvanska zupanija', '732' => 'Medjimuraka zupanija', ), ),
			'CU' => array ( 'country' => 'CUBA', 'states' => array ( '755' => 'Pinar del Ro', '752' => 'La Habana', '747' => 'Ciudad de La Habana', '754' => 'Matanzas', '758' => 'Villa Clara', '746' => 'Cienfuegos', '756' => 'Sancti Spiritus', '745' => 'Ciego de vila', '744' => 'Camagey', '753' => 'Las Tunas', '750' => 'Holquin', '748' => 'Granma', '757' => 'Santiago de Cuba', '749' => 'Guantnamo', '751' => 'Isla de la Juventud', ), ),
			'CY' => array ( 'country' => 'CYPRUS', 'states' => array ( '762' => 'Lefkosia', '763' => 'Lemesos', '761' => 'Larnaka', '759' => 'Ammochostos Magusa', '764' => 'Pafos', '760' => 'Keryneia', ), ),
			'CZ' => array ( 'country' => 'CZECH REPUBLIC', 'states' => array ( '3501' => 'Jihocesky kraj', '3502' => 'Jihomoravsky kraj', '3503' => 'Karlovarsky kraj', '3504' => 'Kralovehradecky kraj', '3505' => 'Liberecky kraj', '3506' => 'Olomoucky kraj', '3507' => 'Pardubicky kraj', '3508' => 'Plzensky kraj', '3509' => 'Praha, hlavni mesto', '3510' => 'Stredocesky kraj', '3511' => 'Ustecky kraj', '3512' => 'Vysocina', '3513' => 'Zlinsky kraj', ), ),
			'DK' => array ( 'country' => 'DENMARK', 'states' => array ( '768' => 'Kbenhavns', '766' => 'Frederiksborg', '772' => 'Roskilde', '776' => 'Vestsjllands', '773' => 'Storstrms', '765' => 'Bornholms', '767' => 'Fyns', '774' => 'Snderjyllands', '770' => 'Ribe', '775' => 'Vejle', '771' => 'Ringkbing', '778' => 'rhus', '777' => 'Viborg', '769' => 'Nordjyllands', ), ),
			'DJ' => array ( 'country' => 'DJIBOUTI', 'states' => array ( '779' => 'Ali Sabiah', '780' => 'Dikhil', '781' => 'Djibouti', '782' => 'Obock', '783' => 'Tadjoura', ), ),
			'DM' => array ( 'country' => 'DOMINICA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'DO' => array ( 'country' => 'DOMINICAN REPUBLIC', 'states' => array ( '788' => 'Distrito Nacional (Santo Domingo)', '784' => 'Azua', '785' => 'Bahoruco', '786' => 'Barahona', '787' => 'Dajabn', '789' => 'Duarte', '795' => 'La Estrelleta [Elas Pia]', '790' => 'El Seybo [El Seibo]', '791' => 'Espaillat', '793' => 'Independencia', '794' => 'La Altagracia', '796' => 'La Romana', '797' => 'La Vega', '798' => 'Mara Trinidad Snchez', '800' => 'Monte Cristi', '802' => 'Pedernales', '803' => 'Peravia', '804' => 'Puerto Plata', '805' => 'Salcedo', '806' => 'Saman', '807' => 'San Cristbal', '808' => 'San Juan', '809' => 'San Pedro de Macors', '812' => 'Snchez Ramrez', '810' => 'Santiago', '811' => 'Santiago Rodrguez', '813' => 'Valverde', '799' => 'Monseor Nouel', '801' => 'Monte Plata', '792' => 'Hato Mayor', ), ),
			'EC' => array ( 'country' => 'ECUADOR', 'states' => array ( '814' => 'Azuay', '815' => 'Bolvar', '816' => 'Carchi', '821' => 'Esmeraldas', '817' => 'Caar', '823' => 'Guayas', '818' => 'Chimborazo', '824' => 'Imbabura', '825' => 'Loja', '827' => 'Manabi', '829' => 'Napo', '820' => 'El Oro', '831' => 'Pichincha', '826' => 'Los Rios', '828' => 'Morona-Santiago', '833' => 'Tungurahua', '832' => 'Sucumbos', '822' => 'Galpagos', '819' => 'Cotopaxi', '830' => 'Pastaza', '834' => 'Zamora-Chinchipe', ), ),
			'EG' => array ( 'country' => 'EGYPT', 'states' => array ( '840' => 'Al Iskandarlyah', '850' => 'Aswan', '851' => 'Asyut', '836' => 'Al Bahr al Ahmar', '837' => 'Al Buhayrah', '852' => 'Bani Suwayf', '845' => 'Al Qahirah', '835' => 'Ad Daqahllyah', '854' => 'Dumyt', '838' => 'Al Fayym', '839' => 'Al Gharbyah', '842' => 'Al Jzah', '841' => 'Al Isma llyah', '855' => 'Janb Sna\'', '846' => 'Al Qalyblyah', '856' => 'Kafr ash Shaykh', '858' => 'Qin', '844' => 'Al Minya', '843' => 'Al Minuflyah', '857' => 'Matrh', '853' => 'Br Sa\'d', '860' => 'Suhj', '849' => 'Ash Sharqiyah', '859' => 'Shaml Sin\'', '848' => 'As Suways', '847' => 'Al Wadi al Jadd', ), ),
			'SV' => array ( 'country' => 'EL SALVADOR', 'states' => array ( '861' => 'Ahuachapn', '862' => 'Cabaas', '863' => 'Chalatenango', '864' => 'Cuscatln', '865' => 'La Libertad', '868' => 'Morazn', '866' => 'La Paz', '872' => 'Santa Ana', '869' => 'San Miguel', '873' => 'Sonsonate', '870' => 'San Salvador', '871' => 'San Vicente', '867' => 'La Unin', '874' => 'Usulutn', ), ),
			'ER' => array ( 'country' => 'ERITREA', 'states' => array ( '875' => 'Anseba', '877' => 'Debubawi Keyih Bahri [Debub-Keih-Bahri]', '876' => 'Debub', '878' => 'Gash-Barka', '879' => 'Maakel [Maekel]', '880' => 'Semenawi Keyih Bahri [Semien-Keih-Bahri]', ), ),
			'EE' => array ( 'country' => 'ESTONIA', 'states' => array ( '881' => 'Harjumsa', '882' => 'Hitumea', '883' => 'Ida-Virumsa', '885' => 'Jgevamsa', '884' => 'Jrvamsa', '886' => 'Lsnemsa', '887' => 'Lne-Virumaa', '888' => 'Polvamea', '889' => 'Prnumsa', '890' => 'Raplamsa', '891' => 'Saaremsa', '892' => 'Tartumsa', '893' => 'Valgamaa', '894' => 'Viljandimsa', '895' => 'Vrumaa', ), ),
			'ET' => array ( 'country' => 'ETHIOPIA', 'states' => array ( '896' => 'Addis Ababa', '897' => 'Afar', '898' => 'Amara {Amhara]', '899' => 'Benshangul-Gumaz', '900' => 'Gambela Peoples', '901' => 'Harari People', '902' => 'Oromia', '904' => 'Southern Nations, Nationalitioa and Peoples', '903' => 'Somali', '905' => 'Tigrai', ), ),
			'GQ' => array ( 'country' => 'EQUATORIAL GUINEA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'FO' => array ( 'country' => 'FAROE ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'FK' => array ( 'country' => 'FALKLAND ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'FJ' => array ( 'country' => 'FIJI', 'states' => array ( '906' => 'Central', '907' => 'Eastern', '908' => 'Northern', '909' => 'Rotuma', '910' => 'Western', ), ),
			'FI' => array ( 'country' => 'FINLAND', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'FR' => array ( 'country' => 'FRANCE', 'states' => array ( '911' => 'Ain', '912' => 'Aisne', '913' => 'Allier', '915' => 'Alpes-de-Haute-Provence', '953' => 'Hautes-Alpes', '914' => 'Alpes-Maritimes', '917' => 'Ardche', '916' => 'Ardennes', '918' => 'Ariege', '919' => 'Aube', '920' => 'Aude', '921' => 'Aveyron', '924' => 'Bouches-du-Rhone', '925' => 'Calvados', '926' => 'Cantal', '927' => 'Charente', '928' => 'Charente-Maritime', '929' => 'Cher', '930' => 'Correze', '931' => 'Corse-du-Sud', '932' => 'Cote-d\'Or', '933' => 'Cotes-d\'Armour', '934' => 'Creuse', '936' => 'Dordogne', '937' => 'Doubs', '938' => 'Drome', '940' => 'Eure', '941' => 'Eure-et-Loir', '942' => 'Finistere', '943' => 'Gard', '948' => 'Haute-Garonne', '944' => 'Gers', '945' => 'Gironde', '956' => 'Herault', '958' => 'Indre', '957' => 'Ille-et-Vilaine', '959' => 'Indre-et-Loire', '960' => 'Isere', '961' => 'Jura', '962' => 'Landes', '963' => 'Loir-et-Cher', '964' => 'Loire', '949' => 'Haute-Loire', '965' => 'Loire-Atlantique', '966' => 'Loiret', '967' => 'Lot', '968' => 'Lot-et-Garonne', '969' => 'Lozere', '970' => 'Maine-et-Loire', '971' => 'Manche', '972' => 'Marne', '973' => 'Meurthe-et-Moselle', '974' => 'Meuse', '975' => 'Morbihan', '976' => 'Moselle', '977' => 'Nievre', '978' => 'Nord', '979' => 'Oise', '980' => 'Orne', '982' => 'Pas-de-Calais', '983' => 'Puy-de-Dome', '984' => 'Pyrenees-Atlantiques', '954' => 'Hautes-Pyrenees', '985' => 'Pyrenees-Orientales', '922' => 'Bas-Rhin', '946' => 'Haut-Rhin', '986' => 'Rhne', '950' => 'Haute-Saone', '987' => 'Saone-et-Loire', '988' => 'Sarthe', '989' => 'Savoie', '951' => 'Haute-Savoie', '981' => 'Paris', '990' => 'Seine-Maritime', '992' => 'Seine-et-Marne', '1004' => 'Yvelines', '935' => 'Deux-Sevres', '993' => 'Somme', '994' => 'Tarn', '995' => 'Tarn-et-Garonne', '998' => 'Var', '999' => 'Vaucluse', '1000' => 'Vendee', '1001' => 'Vienne', '952' => 'Haute-Vienne', '1002' => 'Vosges', '1003' => 'Yonne', '923' => 'Belfort, Territoire de', '939' => 'Essonne', '955' => 'Hauts-de-Seine', '991' => 'Seine-Saint-Denis', '997' => 'Val-de-Marne', '996' => 'Val d\'Oise', ), ),
			'GF' => array ( 'country' => 'FRENCH GUIANA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'PF' => array ( 'country' => 'FRENCH POLYNESIA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'TF' => array ( 'country' => 'FRENCH SOUTHERN LANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GA' => array ( 'country' => 'GABON', 'states' => array ( '1005' => 'Estuaire', '1006' => 'Haut-Ogoou', '1007' => 'Moyen-Ogoou', '1008' => 'Ngouni', '1009' => 'Nyanga', '1010' => 'Ogoou-Ivindo', '1011' => 'Ogoou-Lolo', '1012' => 'Ogoou-Maritime', '1013' => 'Woleu-Ntem', ), ),
			'GM' => array ( 'country' => 'GAMBIA', 'states' => array ( '1014' => 'Banjul', '1015' => 'Lower River', '1016' => 'MacCarthy Island', '1017' => 'North Bank', '1018' => 'Upper River', '1019' => 'Western', ), ),
			'GE' => array ( 'country' => 'GEORGIA', 'states' => array ( '1020' => 'Abashin Raioni', '1022' => 'Adigenis Raioni', '1023' => 'Akhalgoria Raioni', '1024' => 'Akhalk\'alak\'is Raioni', '1025' => 'Akhalts\'ikhis Raioni', '1026' => 'Akhmetis Raioni', '1027' => 'Ambrolauris Raioni', '1029' => 'Aspindzis Raioni', '1030' => 'Baghdat\' is Raioni', '1032' => 'Bolniais Raioni', '1033' => 'Borjamie Raioni', '1034' => 'Ch\'khorotsqus Raioni', '1035' => 'Ch\'okhatauris Raioni', '1037' => 'Dedop\'listsqaros Raioni', '1038' => 'Dmaniais Raioni', '1039' => 'Dushet\' is Raioni', '1041' => 'Galis Raioni', '1042' => 'Gardabnis Raioni', '1044' => 'Goris Raioni', '1045' => 'Gudaut\' is Raioni', '1046' => 'Gulrip\'shis Raioni', '1047' => 'Gurjeanis Raioni', '1048' => 'Javis Raioni', '1049' => 'K\'arelis Raioni', '1053' => 'Kaspis Raioni', '1050' => 'K\'edis Raioni', '1054' => 'Kharagaulis Raioni', '1055' => 'Khashuris Raioni', '1056' => 'Khelvach\'auri6 Raioni', '1057' => 'Khobis Raioni', '1092' => 'Xhonis Raioni', '1058' => 'Khulos Raioni', '1051' => 'K\'obuletis Raioni', '1059' => 'Lagodekhis Raioni', '1060' => 'Lanch\'khut\'is Raioni', '1061' => 'Lentekhis Raioni', '1062' => 'Marneulis Raioni', '1063' => 'Martvilis Raioni', '1064' => 'Mestiis Raioni', '1065' => 'Mts\'khet\'is Raioni', '1066' => 'Ninotsmindis Raioni', '1067' => 'Och\'amch\'iris Raioni', '1068' => 'Onis Raioni', '1069' => 'Ozurget\' is Raioni', '1071' => 'Qazbegis Raioni', '1072' => 'Qvarlis Raioni', '1074' => 'Sach\'kheris Raioni', '1075' => 'Sagarejos Raioni', '1076' => 'Samtrediis Raioni', '1077' => 'Senakis Raioni', '1078' => 'Shuakhevis Raioni', '1079' => 'Sighnaghis Raioni', '1081' => 'Sokhumis Raioni', '1083' => 'T\'elavis Raioni', '1084' => 'T\'erjolis Raioni', '1085' => 'T\'et\'ritsqaros Raioni', '1086' => 'T\'ianet\'is Raioni', '1089' => 'Ts\'ageris Raioni', '1090' => 'Tsalenjikhis Raioni', '1093' => 'Zestap\'onis Raioni', '1094' => 'Zugdidis Raioni', '1028' => 'Ap\'khazet\'is Avtonomiuri Respublika', '1021' => 'Acharis Avtonomiuri Respublika', '1031' => 'Bat \'umi', '1036' => 'Chiat\'ura', '1040' => 'Gagra', '1043' => 'Gori', '1052' => 'K\'ut\'aisi', '1070' => 'P\'ot\'i', '1073' => 'Rust\'avi', '1080' => 'Sokhumi', '1082' => 'T\'bilisi', '1087' => 'Tqibuli', '1088' => 'Tqvarch\'eli', '1091' => 'Tsqalmbo', '1095' => 'Zuqdidi', ), ),
			'DE' => array ( 'country' => 'GERMANY', 'states' => array ( '1099' => 'Brandenburg', '1098' => 'Berlin', '1096' => 'Baden-Wrttemberg', '1097' => 'Bayern', '1100' => 'Bremen', '1102' => 'Hessen', '1101' => 'Hamburg', '1103' => 'Mecklenburg-Vorpommern', '1104' => 'Niedersachsen', '1105' => 'Nordrhein-Westfalen', '1106' => 'Rheinland-Pfalz', '1110' => 'Schleswig-Holstein', '1107' => 'Saarland', '1108' => 'Sachsen', '1109' => 'Sachsen-Anhalt', '1111' => 'Thringen', ), ),
			'GH' => array ( 'country' => 'GHANA', 'states' => array ( '1116' => 'Greater Accra', '1112' => 'Ashanti', '1113' => 'Brong-Ahafo', '1114' => 'Central', '1115' => 'Eastern', '1117' => 'Northern', '1120' => 'Volta', '1118' => 'Upper East', '1119' => 'Upper West', '1121' => 'Western', ), ),
			'GI' => array ( 'country' => 'GIBRALTAR', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GR' => array ( 'country' => 'GREECE', 'states' => array ( '1124' => 'Aitolia-Akarnania', '1170' => 'Voiotia', '1136' => 'Evvoia', '1135' => 'Evrytania', '1139' => 'Fthiotis', '1138' => 'Fokis', '1125' => 'Argolis', '1126' => 'Arkadia', '1122' => 'Achaa', '1141' => 'Ileia', '1151' => 'Korinthia', '1154' => 'Lakonia', '1160' => 'Messinia', '1172' => 'Zakynthos', '1149' => 'Kerkyra', '1148' => 'Kefallinia', '1157' => 'Lefkas', '1127' => 'Arta', '1167' => 'Thesprotia', '1143' => 'Ioannina', '1162' => 'Preveza', '1145' => 'Karditsa', '1155' => 'Larisa', '1159' => 'Magnisia', '1169' => 'Trikala', '1140' => 'Grevena', '1133' => 'Drama', '1142' => 'Imathia', '1168' => 'Thessaloniki', '1147' => 'Kavalla', '1146' => 'Kastoria', '1150' => 'Kilkis', '1152' => 'Kozani', '1161' => 'Pella', '1166' => 'Serrai', '1137' => 'Florina', '1129' => 'Chalkidiki', '1123' => 'Agio Oros', '1134' => 'Evros', '1171' => 'Xanthi', '1164' => 'Rodopi', '1132' => 'Dodekanisos', '1153' => 'Kyklades', '1158' => 'Lesvos', '1165' => 'Samos', '1131' => 'Chios', '1144' => 'Irakleion', '1156' => 'Lasithion', '1163' => 'Rethymnon', '1130' => 'Chania', '1128' => 'Attiki', ), ),
			'GL' => array ( 'country' => 'GREENLAND', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GD' => array ( 'country' => 'GRENADA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GP' => array ( 'country' => 'GUADELOUPE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GU' => array ( 'country' => 'GUAM', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GT' => array ( 'country' => 'GUATEMALA', 'states' => array ( '1173' => 'Alta Verapez', '1174' => 'Baja Verapez', '1175' => 'Chimaltenango', '1176' => 'Chiquimula', '1178' => 'Escuintla', '1179' => 'Guatemala', '1180' => 'Huehuetenango', '1181' => 'Izabal', '1182' => 'Jalapa', '1183' => 'Jutapa', '1184' => 'Petn', '1177' => 'El Progreso', '1186' => 'Quich', '1185' => 'Quezaltenango', '1187' => 'Retathuleu', '1188' => 'Sacatepquez', '1189' => 'San Marcos', '1191' => 'Solol6', '1190' => 'Santa Rosa', '1192' => 'Suchitepquez', '1193' => 'Totonicapn', '1194' => 'Zacapa', ), ),
			'GG' => array ( 'country' => 'GUERNSEY', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'GN' => array ( 'country' => 'GUINEA', 'states' => array ( '1195' => 'Beyla', '1196' => 'Boffa', '1197' => 'Bok', '1198' => 'Coyah', '1199' => 'Dabola', '1201' => 'Dinguiraye', '1200' => 'Dalaba', '1202' => 'Dubrka', '1203' => 'Faranah', '1204' => 'Forcariah', '1205' => 'Fria', '1206' => 'Gaoual', '1207' => 'Gukdou', '1208' => 'Kankan', '1211' => 'Koubia', '1209' => 'Kindia', '1214' => 'Krouan', '1213' => 'Kouroussa', '1210' => 'Kissidougou', '1215' => 'Lab', '1217' => 'Llouma', '1216' => 'Lola', '1218' => 'Macenta', '1221' => 'Mandiana', '1219' => 'Mali', '1220' => 'Mamou', '1222' => 'Nzrkor', '1223' => 'Pita', '1224' => 'Siguiri', '1226' => 'Tliml', '1225' => 'Tougu', '1227' => 'Yomou', ), ),
			'GW' => array ( 'country' => 'GUINEA-BISSAU', 'states' => array ( '1228' => 'Bafat', '1231' => 'Bolama', '1229' => 'Biombo', '1230' => 'Bissau', '1232' => 'Cacheu', '1233' => 'Gab', '1234' => 'Oio', '1235' => 'Quloara', '1236' => 'Tombali S', ), ),
			'GY' => array ( 'country' => 'GUYANA', 'states' => array ( '1237' => 'Barima-Waini', '1238' => 'Cuyuni-Mazaruni', '1239' => 'Demerara-Mahaica', '1240' => 'East Berbice-Corentyne', '1241' => 'Essequibo Islands-West Demerara', '1242' => 'Mahaica-Berbice', '1243' => 'Pomeroon-Supenaam', '1244' => 'Potaro-Siparuni', '1245' => 'Upper Demerara-Berbice', '1246' => 'Upper Takutu-Upper Essequibo', ), ),
			'HT' => array ( 'country' => 'HAITI', 'states' => array ( '1247' => 'Centre', '1248' => 'Grande-Anse', '1249' => 'Nord', '1250' => 'Nord-Eat', '1251' => 'Nord-Ouest', '1252' => 'Ouest', '1253' => 'Sud', '1254' => 'Sud-Est', ), ),
			'HM' => array ( 'country' => 'HEARD AND MCDONALD ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'HK' => array ( 'country' => 'HONG KONG', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'HN' => array ( 'country' => 'HONDURAS', 'states' => array ( '1255' => 'Atlntida', '1256' => 'Choluteca', '1257' => 'Coln', '1258' => 'Comayagua', '1259' => 'Copn', '1260' => 'Corts', '1261' => 'El Paraso', '1262' => 'Francisco Morazn', '1263' => 'Gracias a Dios', '1265' => 'Islas de la Baha', '1264' => 'Intibuc', '1267' => 'Lempira', '1266' => 'La Paz', '1268' => 'Ocotepeque', '1269' => 'Olancho', '1270' => 'Santa Brbara', '1271' => 'Valle', '1272' => 'Yoro', ), ),
			'HU' => array ( 'country' => 'HUNGARY', 'states' => array ( '1273' => 'Baranya', '1278' => 'Bkscsaba', '1277' => 'Bks', '1276' => 'Bcs-Kiskun', '1275' => 'Budapest', '1274' => 'Borsod-Abaj-Zempln', '1279' => 'Csongrd', '1280' => 'Debrecen', '1281' => 'Dunajvros', '1282' => 'Eger', '1283' => 'Fejr', '1285' => 'Gyr-Moson-Sopron', '1284' => 'Gyr', '1286' => 'Hajd-Bihar', '1287' => 'Heves', '1288' => 'Hdmezvsrhely', '1289' => 'Jsz-Nagykun-Szolnok', '1292' => 'Komrom-Esztergom', '1291' => 'Keckemt', '1290' => 'Kaposvr', '1293' => 'Miskolc', '1294' => 'Nagykanizaa', '1296' => 'Ngrd', '1295' => 'Nyregyhza', '1297' => 'Pest', '1298' => 'Pcs', '1303' => 'Szaged', '1304' => 'Szakeafahrvr', '1307' => 'Szombathely', '1306' => 'Szolnok', '1301' => 'Sopron', '1300' => 'Somogy', '1305' => 'Szakszrd', '1299' => 'Salgtarjn', '1302' => 'Szabolcs-Szatmr-Bereg', '1308' => 'Tatabinya', '1309' => 'Tolna', '1310' => 'Vas', '1312' => 'Veszprm', '1311' => 'Veezprm', '1313' => 'Zala', '1314' => 'Zalaegerszeg', ), ),
			'IN' => array ( 'country' => 'INDIA', 'states' => array ( '1316' => 'Azarb&yjan-e-Sharq', '1317' => 'Azarbayjn-e-Gharb', '1315' => 'ArdabLl', '1320' => 'Esfahan', '1325' => 'Ilam', '1318' => 'Bahahr', '1338' => 'Tehran', '1319' => 'Chahar Mahall va Bakhtlari', '1328' => 'Khorsn', '1329' => 'Khzestan', '1340' => 'Zanjan', '1337' => 'Semnan', '1336' => 'SIstan va Balchestan', '1321' => 'Fars', '1326' => 'Kerman', '1331' => 'Kordeatan', '1327' => 'Kermanshhan', '1330' => 'Kohkilyeh va Byer Ahmad', '1322' => 'Gilan', '1332' => 'Lorestan', '1334' => 'Mazandaran', '1333' => 'Markaz', '1324' => 'Hormozgn', '1323' => 'Hamadan', '1339' => 'Yazd', '1335' => 'Qom', '3885' => 'Andaman and Nicobar Islands', '3886' => 'Andhra Pradesh', '3887' => 'Arunachal Pradesh', '3888' => 'Assam', '3889' => 'Bihar', '3890' => 'Chandigarh', '3891' => 'Chhattisgarh', '3892' => 'Daman and Diu', '3893' => 'Delhi', '3894' => 'Dadra and Nagar Haveli', '3895' => 'Goa', '3896' => 'Gujarat', '3897' => 'Himachal Pradesh', '3898' => 'Haryana', '3899' => 'Jharkhand', '3900' => 'Jammu and Kashmir', '3901' => 'Karnataka', '3902' => 'Kerala', '3903' => 'Lakshadweep', '3905' => 'Maharashtra', '3904' => 'Meghalaya', '3906' => 'Manipur', '3907' => 'Madhya Pradesh', '3908' => 'Mizoram', '3909' => 'Nagaland', '3910' => 'Orissa', '3911' => 'Punjab', '3912' => 'Pondicherry', '3913' => 'Rajasthan', '3914' => 'Sikkim', '3915' => 'Tamil Nadu', '3916' => 'Tripura', '3917' => 'Uttaranchal', '3918' => 'Uttar Pradesh', '3919' => 'West Bengal', ), ),
			'ID' => array ( 'country' => 'INDONESIA', 'states' => array ( '1341' => 'Aceh', '1342' => 'Bali', '1343' => 'Bengkulu', '1344' => 'Irian Jaya', '1346' => 'Jambi', '1348' => 'Jawa Barat', '1350' => 'Jawa Timur', '1345' => 'Jakarta Raya', '1349' => 'Jawa Tengah', '1347' => 'Jawa', '1351' => 'Kalimantan', '1352' => 'Kalimantan Barat', '1353' => 'Kalimantan Selatan', '1354' => 'Kalimantan Timur', '1355' => 'Lampung', '1356' => 'Maluku', '1358' => 'Nusa Tenggara Barat', '1359' => 'Nusa Tenggara Timur', '1357' => 'Nusa Tenggara', '1360' => 'Riau', '1365' => 'Sulawesi Utara', '1368' => 'Sumatra Barat', '1364' => 'Sulawesi Tenggara', '1361' => 'Sulawesi', '1366' => 'Sumatera', '1362' => 'Sulawesi Selatan', '1369' => 'Sumatra Selatan', '1363' => 'Sulawesi Tengah', '1367' => 'Sumatera Utara', '1370' => 'Timor-Timur', '1371' => 'Yogyakarta', ), ),
			'IR' => array ( 'country' => 'IRAN', 'states' => array ( '3854' => 'East Azerbaijan', '3855' => 'West Azerbaijan', '3856' => 'Ardabil', '3857' => 'Esfahan', '3858' => 'Ilam', '3859' => 'Bushehr', '3860' => 'Tehran', '3861' => 'Chahar Mahaal and Bakhtiari', '3862' => 'Khorasan', '3863' => 'Khuzestan', '3864' => 'Zanjan', '3865' => 'Semnan', '3866' => 'Sistan and Baluchistan', '3867' => 'Fars', '3868' => 'Kerman', '3869' => 'Kurdistan', '3870' => 'Kermanshah', '3871' => 'Kohkiluyeh and Buyer Ahmad', '3872' => 'Gilan', '3873' => 'Lorestan', '3874' => 'Mazandaran', '3875' => 'Markazi', '3876' => 'Hormozgan', '3877' => 'Hamadan', '3878' => 'Yazd', '3879' => 'Qom', '3880' => 'Golestan', '3881' => 'Qazvin', '3882' => 'South Khorasan', '3883' => 'Razavi Khorasan', '3884' => 'North Khorasan', ), ),
			'IQ' => array ( 'country' => 'IRAQ', 'states' => array ( '1372' => 'Al Anbar', '1377' => 'Arbil', '1373' => 'Al Ba,rah', '1380' => 'Babil', '1381' => 'Baghdd', '1382' => 'Dahk', '1384' => 'Diyl', '1383' => 'Dhi Qr', '1385' => 'Karbal\'', '1386' => 'Maysan', '1374' => 'Al Muthann', '1376' => 'An Najef', '1387' => 'Ninaw', '1375' => 'Al Qadisiyah', '1388' => 'Salah ad Din', '1378' => 'As Sulaymaniyah', '1379' => 'At Ta\'mm', '1389' => 'Wasit', ), ),
			'IE' => array ( 'country' => 'IRELAND', 'states' => array ( '1393' => 'Cork', '1392' => 'Clare', '1391' => 'Cavan', '1390' => 'Carlow', '1394' => 'Dublin', '1395' => 'Galway', '1396' => 'Kerry', '1397' => 'Kildare', '1398' => 'Kilkenny', '1402' => 'Longford', '1403' => 'Louth', '1401' => 'Limerick', '1400' => 'Leitrim', '1399' => 'Laois', '1405' => 'Meath', '1406' => 'Monaghan', '1404' => 'Mayo', '1407' => 'Offaly', '1408' => 'Roscommon', '1409' => 'Sligo', '1410' => 'Tipperary', '1411' => 'Waterford', '1412' => 'Westmeath', '1414' => 'Wicklow', '1413' => 'Wexford', ), ),
			'IS' => array ( 'country' => 'ICELAND', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'IL' => array ( 'country' => 'ISRAEL', 'states' => array ( '1415' => 'HaDarom', '1418' => 'Hefa', '1420' => 'Yerushalayim', '1416' => 'HaMerkaz', '1419' => 'Tel-Aviv', '1417' => 'HaZafon', ), ),
			'IT' => array ( 'country' => 'ITALY', 'states' => array ( '1499' => 'Piemonte', '1534' => 'Valle d\'Aosta', '1476' => 'Lombardia', '1528' => 'Trentino-Alto Adige', '1536' => 'Veneto', '1460' => 'Friuli-Venezia Giulia', '1473' => 'Liguria', '1454' => 'Emilia-Romagna', '1526' => 'Toscana', '1533' => 'Umbria', '1480' => 'Marche', '1470' => 'Lazio', '1421' => 'Abruzzo', '1486' => 'Molise', '1443' => 'Campania', '1505' => 'Puglia', '1431' => 'Basilicata', '1441' => 'Calabria', '1518' => 'Sicilia', '1515' => 'Sardegna', '1422' => 'Agrigento', '1423' => 'Alessandria', '1424' => 'Ancona', '1425' => 'Aosta', '1427' => 'Ascoli Piceno', '1467' => 'L\'Aquila', '1426' => 'Arezzo', '1428' => 'Asti', '1429' => 'Avellino', '1430' => 'Bari', '1434' => 'Bergamo', '1435' => 'Biella', '1432' => 'Belluno', '1433' => 'Benevento', '1436' => 'Bologna', '1439' => 'Brindisi', '1438' => 'Brescia', '1437' => 'Bolzano', '1440' => 'Cagliari', '1444' => 'Campobasso', '1445' => 'Caserta', '1448' => 'Chieti', '1442' => 'Caltanissetta', '1453' => 'Cuneo', '1449' => 'Como', '1451' => 'Cremona', '1450' => 'Cosenza', '1446' => 'Catania', '1447' => 'Catanzaro', '1455' => 'Enna', '1456' => 'Ferrara', '1458' => 'Foggia', '1457' => 'Firenze', '1459' => 'Forl', '1461' => 'Frosinone', '1462' => 'Genova', '1463' => 'Gorizia', '1464' => 'Grosseto', '1465' => 'Imperia', '1466' => 'Isernia', '1452' => 'Crotone', '1472' => 'Lecco', '1471' => 'Lecce', '1474' => 'Livorno', '1475' => 'Lodi', '1469' => 'Latina', '1477' => 'Lucca', '1478' => 'Macerata', '1483' => 'Messina', '1484' => 'Milano', '1479' => 'Mantova', '1485' => 'Modena', '1481' => 'Massa-Carrara', '1482' => 'Matera', '1487' => 'Napoli', '1488' => 'Novara', '1489' => 'Nuoro', '1490' => 'Oristano', '1492' => 'Palermo', '1498' => 'Piacenza', '1491' => 'Padova', '1497' => 'Pescara', '1495' => 'Perugia', '1500' => 'Pisa', '1502' => 'Pordenone', '1504' => 'Prato', '1493' => 'Parma', '1496' => 'Pesaro e Urbino', '1501' => 'Pistoia', '1494' => 'Pavia', '1503' => 'Potenza', '1507' => 'Ravenna', '1508' => 'Reggio Calabria', '1509' => 'Reggio Emilia', '1506' => 'Ragusa', '1510' => 'Rieti', '1512' => 'Roma', '1511' => 'Rimini', '1513' => 'Rovigo', '1514' => 'Salerno', '1519' => 'Siena', '1521' => 'Sondrio', '1468' => 'La Spezia', '1520' => 'Siracusa', '1516' => 'Sassari', '1517' => 'Savona', '1522' => 'Taranto', '1523' => 'Teramo', '1529' => 'Trento', '1525' => 'Torino', '1527' => 'Trapani', '1524' => 'Terni', '1531' => 'Trieste', '1530' => 'Treviso', '1532' => 'Udine', '1535' => 'Varese', '1538' => 'Verbano-Cusio-Ossola', '1539' => 'Vercelli', '1537' => 'Venezia', '1542' => 'Vicenza', '1540' => 'Verona', '1543' => 'Viterbo', '1541' => 'Vibo Valentia', ), ),
			'IM' => array ( 'country' => 'ISLE OF MAN', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'JM' => array ( 'country' => 'JAMAICA', 'states' => array ( '1546' => 'Kingston', '1549' => 'Saint Andrew', '1555' => 'Saint Thomea', '1548' => 'Portland', '1554' => 'Saint Mary', '1550' => 'Saint Ann', '1556' => 'Trelawny', '1553' => 'Saint James', '1545' => 'Hanover', '1557' => 'Westmoreland', '1552' => 'Saint Elizabeth', '1547' => 'Manchester', '1544' => 'Clarendon', '1551' => 'Saint Catherine', ), ),
			'JP' => array ( 'country' => 'JAPAN', 'states' => array ( '1569' => 'Hokkaido', '1560' => 'Aomori', '1573' => 'Iwate', '1581' => 'Miyagi', '1559' => 'Akita', '1602' => 'Yamagata', '1565' => 'Fukusima', '1571' => 'Ibaraki', '1596' => 'Tochigi', '1567' => 'Gunma', '1592' => 'Saitama', '1561' => 'Chiba', '1598' => 'Tokyo', '1576' => 'Kanagawa', '1586' => 'Niigata', '1600' => 'Toyama', '1572' => 'Ishikawa', '1563' => 'Fukui', '1604' => 'Yamanashi', '1583' => 'Nagano', '1566' => 'Gifu', '1595' => 'Shizuoka', '1558' => 'Aichi', '1580' => 'Mie', '1593' => 'Shiga', '1579' => 'Kyoto', '1590' => 'Osaka', '1570' => 'Hyogo', '1585' => 'Nara', '1601' => 'Wakayama', '1599' => 'Tottori', '1594' => 'Shimane', '1588' => 'Okayama', '1568' => 'Hiroshima', '1603' => 'Yamaguchi', '1597' => 'Tokushima', '1574' => 'Kagawa', '1562' => 'Ehime', '1577' => 'Kochi', '1564' => 'Fukuoka', '1591' => 'Saga', '1584' => 'Nagasaki', '1578' => 'Kumamoto', '1587' => 'Oita', '1582' => 'Miyazaki', '1575' => 'Kagoshima', '1589' => 'Okinawa', ), ),
			'JO' => array ( 'country' => 'JORDAN', 'states' => array ( '1606' => 'Ajln', '1605' => '\'Ammn', '1607' => 'Al \'Aqaba', '1611' => 'At Taflah', '1612' => 'Az Zarg\'', '1608' => 'Al Balqa\'', '1614' => 'Jarash', '1613' => 'Irbid', '1609' => 'Al Karak', '1610' => 'Al Mafraq', '1616' => 'Madaba', '1615' => 'Ma\'n', ), ),
			'JE' => array ( 'country' => 'JERSEY', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'KZ' => array ( 'country' => 'KAZAKHSTAN', 'states' => array ( '1619' => 'Aqmola oblysy', '1620' => 'Aqtbe oblysy', '1617' => 'Almaty', '1618' => 'Almaty oblysy', '1621' => 'Atyra oblyfiy', '1623' => 'Bayqonyr', '1627' => 'Qaraghandy oblysy', '1628' => 'Qostanay oblysy', '1629' => 'Qyzylorda oblysy', '1624' => 'Mangghysta oblysy', '1626' => 'Pavlodar oblysy', '1631' => 'Soltatik Kazakstan Severo-Kazakhstanskaya Severo-Kazahstanskaja', '1630' => 'Shyghys Kazakstan', '1625' => 'Ongtustik Kazakstan Yuzhno-Kazakhstanskaya Juzno-Kazahetanskaja', '1622' => 'Batys Kazakstan', '1632' => 'Zhambyl oblysy Zhambylskaya oblast\'', ), ),
			'KE' => array ( 'country' => 'KENYA', 'states' => array ( '1636' => 'Nairobi Municipality', '1633' => 'Central', '1634' => 'Coast', '1635' => 'Eastern', '1637' => 'North-Eastern Kaskazini Mashariki', '1638' => 'Rift Valley', '1639' => 'Western Magharibi', ), ),
			'KI' => array ( 'country' => 'KIRIBATI', 'states' => array ( '1640' => 'Gilbert Islands', '1641' => 'Line Islands', '1642' => 'Phoenix Islands', ), ),
			'KP' => array ( 'country' => 'KOREA, NORTH', 'states' => array ( '1643' => 'Chagang-do', '1644' => 'Hamgyongbuk-do', '1645' => 'Hamgyongnam-do', '1646' => 'Hwanghaebuk-do', '1647' => 'Hwanghaenam-do', '1648' => 'Kaesong-si', '1649' => 'Kangwon-do', '1650' => 'Nampo-si', '1651' => 'Pyonganbuk-do', '1652' => 'Pyongannam-do', '1653' => 'Pyongyang-ai', '1654' => 'Yanggang-do', ), ),
			'KR' => array ( 'country' => 'KOREA, SOUTH', 'states' => array ( '1669' => 'Seoul Teugbyeolsi', '1655' => 'Busan Gwang\'yeogsi', '1658' => 'Daegu Gwang\'yeogsi', '1665' => 'Incheon Gwang\'yeogsi', '1661' => 'Gwangju Gwang\'yeogsi', '1659' => 'Daejeon Gwang\'yeogsi', '1670' => 'Ulsan Gwang\'yeogsi', '1662' => 'Gyeonggido', '1660' => 'Gang\'weondo', '1656' => 'Chungcheongbugdo', '1657' => 'Chungcheongnamdo', '1667' => 'Jeonrabugdo', '1668' => 'Jeonranamdo', '1663' => 'Gyeongsangbugdo', '1664' => 'Gyeongsangnamdo', '1666' => 'Jejudo', ), ),
			'KW' => array ( 'country' => 'KUWAIT', 'states' => array ( '1671' => 'Al Ahmad', '1672' => 'Al Farwanlyah', '1675' => 'Hawall', '1673' => 'Al Jahrah', '1674' => 'Al Kuwayt', ), ),
			'KG' => array ( 'country' => 'KYRGYZSTAN', 'states' => array ( '1676' => 'Chu', '1677' => 'Jalal-Abad', '1678' => 'Naryn', '1679' => 'Osh', '1680' => 'Talas', '1681' => 'Ysyk-Kol', ), ),
			'LA' => array ( 'country' => 'LAOS', 'states' => array ( '1682' => 'Attapu', '1683' => 'Boko', '1684' => 'Bolikhamxai', '1685' => 'Champasak', '1686' => 'Houaphan', '1687' => 'Khammouan', '1688' => 'Louang Namtha', '1689' => 'Louangphabang', '1690' => 'Oudmxai', '1691' => 'Phngsali', '1692' => 'Salavan', '1693' => 'Savannakht', '1694' => 'Vientiane', '1695' => 'Xaignabouli', '1697' => 'Xkong', '1696' => 'Xiangkhoang', ), ),
			'LV' => array ( 'country' => 'LATVIA', 'states' => array ( '1698' => 'Aizkraukles Apripkis', '1699' => 'Alkanes Apripkis', '1700' => 'Balvu Apripkis', '1701' => 'Bauskas Apripkis', '1702' => 'Csu Aprikis', '1703' => 'Daugavpile Apripkis', '1704' => 'Daugavpils', '1705' => 'Dobeles Apripkis', '1706' => 'Gulbenes Aprlpkis', '1707' => 'Jelgava', '1710' => 'Jkabpils Apripkis', '1708' => 'Jelgavas Apripkis', '1709' => 'Jurmala', '1711' => 'Krslavas Apripkis', '1712' => 'Kuldlgas Apripkis', '1713' => 'Liepjas Apripkis', '1715' => 'Limbazu Apripkis', '1714' => 'Liepja', '1716' => 'Ludzas Apripkis', '1717' => 'Madonas Apripkis', '1718' => 'Ogres Apripkis', '1719' => 'Preilu Apripkis', '1721' => 'Rzaknes Apripkis', '1722' => 'Rzekne', '1723' => 'Rgas Apripkis', '1720' => 'Rlga', '1724' => 'Saldus Apripkis', '1725' => 'Talsu Apripkis', '1726' => 'Tukuma Apriplcis', '1730' => 'Ventspils Apripkis', '1729' => 'Ventspils', '1727' => 'Valkas Apripkis', '1728' => 'Valmieras Apripkis', ), ),
			'LB' => array ( 'country' => 'LEBANON', 'states' => array ( '1734' => 'Loubnane ech Chemli', '1731' => 'Beirout', '1732' => 'El Bqaa', '1735' => 'Loubnne ej Jnobi', '1733' => 'Jabal Loubnne', '1736' => 'Nabaty', ), ),
			'LS' => array ( 'country' => 'LESOTHO', 'states' => array ( '1741' => 'Maseru', '1738' => 'Butha-Buthe', '1739' => 'Leribe', '1737' => 'Berea', '1740' => 'Mafeteng', '1742' => 'Mohale\'s Hoek', '1745' => 'Quthing', '1744' => 'Qacha\'s Nek', '1743' => 'Mokhotlong', '1746' => 'Thaba-Tseka', ), ),
			'LR' => array ( 'country' => 'LIBERIA', 'states' => array ( '1748' => 'Bong', '1747' => 'Bomi', '1750' => 'Grand Cape Mount', '1749' => 'Grand Basaa', '1751' => 'Grand Gedeh', '1752' => 'Grand Kru', '1753' => 'Lofa', '1754' => 'Margibi', '1756' => 'Montserrado', '1755' => 'Maryland', '1757' => 'Nimba', '1758' => 'Rivercess', '1759' => 'Sinoe', ), ),
			'LY' => array ( 'country' => 'LIBYA', 'states' => array ( '1767' => 'Banghazi', '1760' => 'Al Butnan', '1768' => 'Fazzan', '1761' => 'Al Jabal al Akhdar', '1762' => 'Al Jabal al Gharb', '1763' => 'Al Jufrah', '1769' => 'Misratah', '1770' => 'Naggaza', '1771' => 'Sawfajjin', '1772' => 'Tarbulus', '1765' => 'Al Whah', '1764' => 'Al Wust', '1766' => 'Az Zwiyah', ), ),
			'LI' => array ( 'country' => 'LIECHTENSTEIN', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'LT' => array ( 'country' => 'LITHUANIA', 'states' => array ( '1773' => 'Alytaus Apskritis', '1775' => 'Klaipedos Apskritis', '1774' => 'Kauno Apskritis', '1776' => 'Marijampoles Apskritis', '1777' => 'Panevezio Apskritis', '1778' => 'Sisuliu Apskritis', '1779' => 'Taurages Apskritis', '1780' => 'Telsiu Apskritis', '1781' => 'Utenos Apskritis', '1782' => 'Vilniaus Apskritis', ), ),
			'LU' => array ( 'country' => 'LUXEMBOURG', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MO' => array ( 'country' => 'MACAU', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MK' => array ( 'country' => 'MACEDONIA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MG' => array ( 'country' => 'MADAGASCAR', 'states' => array ( '1787' => 'Toamasina', '1784' => 'Antsiranana', '1785' => 'Fianarantsoa', '1786' => 'Mahajanga', '1783' => 'Antananarivo', '1788' => 'Toliara', ), ),
			'MW' => array ( 'country' => 'MALAWI', 'states' => array ( '1789' => 'Blantyre', '1790' => 'Chikwawa', '1791' => 'Chiradzulu', '1792' => 'Chitipa', '1793' => 'Dedza', '1794' => 'Dowa', '1795' => 'Karonga', '1796' => 'Kasungu', '1797' => 'Lilongwe', '1800' => 'Mchinji', '1799' => 'Mangochi', '1798' => 'Machinga', '1801' => 'Mulanje', '1802' => 'Mwanza', '1803' => 'Mzimba', '1804' => 'Nkhata Bay', '1808' => 'Ntchisi', '1805' => 'Nkhotakota', '1806' => 'Nsanje', '1807' => 'Ntcheu', '1809' => 'Rumphi', '1810' => 'Salima', '1811' => 'Thyolo', '1812' => 'Zomba', ), ),
			'MT' => array ( 'country' => 'MALTA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MY' => array ( 'country' => 'MALAYSIA', 'states' => array ( '3515' => 'Johor', '1814' => 'Kedah', '1815' => 'Kelantan', '1816' => 'Melaka', '1817' => 'Negeri Sembilan', '1818' => 'Pahang', '1821' => 'Pulau Pinang', '1819' => 'Perak', '1820' => 'Perlis', '1824' => 'Selangor', '1825' => 'Terengganu', '1822' => 'Sabah', '1823' => 'Sarawak', '1826' => 'Wilayah Persekutuan Kuala Lumpur', '1827' => 'Wilayah Persekutuan Labuan', '3514' => 'Wilayah Persekutuan Putrajaya', '1813' => 'Johor', ), ),
			'MV' => array ( 'country' => 'MALDIVES', 'states' => array ( '1844' => 'Seenu', '1828' => 'Alif', '1839' => 'Lhaviyani', '1847' => 'Vaavu', '1838' => 'Laamu', '1835' => 'Haa Alif', '1846' => 'Thaa', '1841' => 'Meemu', '1843' => 'Raa', '1831' => 'Faafu', '1830' => 'Dhaalu', '1829' => 'Baa', '1836' => 'Haa Dhaalu', '1845' => 'Shaviyani', '1842' => 'Noonu', '1837' => 'Kaafu', '1832' => 'Gaaf Alif', '1833' => 'Gaefu Dhaalu', '1834' => 'Gnaviyani', '1840' => 'Male', ), ),
			'MQ' => array ( 'country' => 'MARTINIQUE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'ML' => array ( 'country' => 'MALI', 'states' => array ( '1850' => 'Kayes', '1856' => 'Xoulikoro', '1854' => 'Sikasso', '1853' => 'S69ou', '1852' => 'Mopti', '1855' => 'Tombouctou', '1849' => 'Gao', '1851' => 'Kidal', '1848' => 'Bamako', ), ),
			'MH' => array ( 'country' => 'MARSHALL ISLANDS', 'states' => array ( '1858' => 'Ailuk', '1857' => 'Ailinglapalap', '1859' => 'Arno', '1860' => 'Aur', '1861' => 'Ebon', '1862' => 'Eniwetok', '1863' => 'Jaluit', '1864' => 'Kili', '1865' => 'Kwajalein', '1866' => 'Lae', '1867' => 'Lib', '1868' => 'Likiep', '1869' => 'Majuro', '1870' => 'Maloelap', '1871' => 'Mejit', '1872' => 'Mili', '1873' => 'Namorik', '1874' => 'Namu', '1875' => 'Rongelap', '1876' => 'Ujae', '1877' => 'Ujelang', '1878' => 'Utirik', '1880' => 'Wotje', '1879' => 'Wotho', ), ),
			'MR' => array ( 'country' => 'MAURITANIA', 'states' => array ( '1887' => 'Hodh ech Chargui', '1888' => 'Hodh el Charbi', '1882' => 'Assaba', '1885' => 'Gorgol', '1883' => 'Brakna', '1893' => 'Trarza', '1881' => 'Adrar', '1884' => 'Dakhlet Nouadhibou', '1891' => 'Tagant', '1886' => 'Guidimaka', '1892' => 'Tiris Zemmour', '1889' => 'Inchiri', '1890' => 'Nouakchott', ), ),
			'MU' => array ( 'country' => 'MAURITIUS', 'states' => array ( '1894' => 'Agalega Islands', '1896' => 'Black River', '1895' => 'Beau Bassin-Rose Hill', '1897' => 'Cargados Carajos Shoals', '1898' => 'Curepipe', '1899' => 'Flacq', '1900' => 'Grand Port', '1901' => 'Moka', '1902' => 'Pamplemousses', '1904' => 'Port Louis', '1903' => 'Plaines Wilhems', '1905' => 'Quatre Bornes', '1907' => 'Rodrigues Island', '1906' => 'Rivire du Rempart', '1908' => 'Savanne', '1909' => 'Vacosa-Phoenix', ), ),
			'YT' => array ( 'country' => 'MAYOTTE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MX' => array ( 'country' => 'MEXICO', 'states' => array ( '1910' => 'Aguascalientes', '1911' => 'Baja California', '1912' => 'Baja California Sur', '1913' => 'Campeche', '1915' => 'Chihushua', '1914' => 'Chiapas', '1916' => 'Coahu ila', '1917' => 'Col ima', '1918' => 'Distrito Federal', '1919' => 'Durango', '1921' => 'Guerrero', '1920' => 'Guanajuato', '1922' => 'Hidalgo', '1923' => 'Jalisco', '1924' => 'Mexico', '1925' => 'Michoacin', '1926' => 'Moreloa', '1927' => 'Nayarit', '1928' => 'Nuevo Len', '1929' => 'Oaxaca', '1930' => 'Puebla', '1931' => 'Quertaro', '1932' => 'Quintana Roo', '1934' => 'Sinaloa', '1933' => 'San Luis Potos', '1935' => 'Sonora', '1936' => 'Tabasco', '1937' => 'Tamaulipas', '1938' => 'Tlaxcala', '1939' => 'Veracruz', '1940' => 'Yucatn', '1941' => 'Zacatecas', ), ),
			'FM' => array ( 'country' => 'MICRONESIA', 'states' => array ( '1943' => 'Kosrae', '1944' => 'Pohnpei', '1942' => 'Chuuk', '1945' => 'Yap', ), ),
			'MD' => array ( 'country' => 'MOLDOVA', 'states' => array ( '1946' => 'Anenii Noi', '1947' => 'Balti', '1948' => 'Basarabeasca', '1949' => 'Brinceni', '1951' => 'Cainari', '1952' => 'Calarayi', '1953' => 'Camenca', '1954' => 'Cantemir', '1955' => 'Causeni', '1956' => 'Chisinau', '1950' => 'Cahul', '1957' => 'Ciadr-Lunga', '1958' => 'Cimi\'lia', '1959' => 'Comrat', '1960' => 'Criuleni', '1963' => 'Dubasari', '1961' => 'Donduseni', '1962' => 'Drochia', '1964' => 'Edine;', '1966' => 'Flesti', '1965' => 'Floresti', '1967' => 'Glodeni', '1968' => 'Grigoriopol', '1969' => 'Hncesti', '1970' => 'Ialoveni', '1971' => 'Leova', '1972' => 'Nisporeni', '1973' => 'Ocnia', '1974' => 'Orhei', '1975' => 'Rezina', '1976' => 'Ribnita', '1978' => 'Rscani', '1977' => 'Rbnita', '1984' => 'Sngerei', '1979' => 'Slobozia', '1981' => 'Soroca', '1980' => 'Soldanesti', '1982' => 'Stefan Voda', '1983' => 'Straseni', '1985' => 'Taraclia', '1986' => 'Telenesti', '1987' => 'Tighina', '1988' => 'Tiraspol', '1989' => 'Ungheni', '1990' => 'Vulcanesti', ), ),
			'MC' => array ( 'country' => 'MONACO', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MN' => array ( 'country' => 'MONGOLIA', 'states' => array ( '2005' => 'Orhon', '1995' => 'Darhan uul', '2002' => 'Hentiy', '2004' => 'Hvsgl', '2003' => 'Hovd', '2010' => 'Uvs', '2008' => 'Tv', '2006' => 'Selenge', '2007' => 'Shbaatar', '2011' => 'mngovi', '2012' => 'vrhangay', '1999' => 'Dzavhan', '1998' => 'DundgovL', '1996' => 'Dornod', '1997' => 'Dornogov', '2001' => 'Govi-Smber', '2000' => 'Govi-Altay', '1994' => 'Bulgan', '1993' => 'Bayanhongor', '1992' => 'Bayan-lgiy', '1991' => 'Arhangay', '2009' => 'Ulanbaatar', ), ),
			'ME' => array ( 'country' => 'MONTENEGRO', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MS' => array ( 'country' => 'MONTSERRAT', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MA' => array ( 'country' => 'MOROCCO', 'states' => array ( '2013' => 'Agadir', '2016' => 'Assa-Zag', '2017' => 'Azilal', '2018' => 'At Baha', '2021' => 'Beni Mellal', '2022' => 'Berkane', '2020' => 'Ben Sllmane', '2023' => 'Boujdour', '2024' => 'Boulemane', '2025' => 'Casablanca', '2026' => 'Chefchaouene', '2027' => 'Chichaoua', '2030' => 'Errachidia', '2032' => 'Essaouira', '2031' => 'Es Semara', '2034' => 'Fs', '2033' => 'Figuig', '2035' => 'Guelmim', '2028' => 'El Hajeb', '2014' => 'Al Haouz', '2015' => 'Al Hocema', '2036' => 'Ifrane', '2037' => 'Jrada', '2029' => 'El Jadida', '2042' => 'Knitra', '2038' => 'Kelaat Sraghna', '2039' => 'Khemisaet', '2040' => 'Khenifra', '2041' => 'Khouribga', '2043' => 'Laayoune (EH)', '2044' => 'Larache', '2045' => 'Marrakech', '2046' => 'Mekns', '2019' => 'At Melloul', '2047' => 'Nador', '2048' => 'Ouarzazate', '2049' => 'Oued ed Dahab (EH)', '2050' => 'Oujda', '2051' => 'Rabat-Sal', '2052' => 'Safi', '2053' => 'Sefrou', '2054' => 'Settat', '2055' => 'Sidl Kacem', '2058' => 'Taounate', '2059' => 'Taroudannt', '2060' => 'Tata', '2061' => 'Taza', '2063' => 'Ttouan', '2062' => 'Tiznit', '2057' => 'Tanger', '2056' => 'Tan-Tan', ), ),
			'MZ' => array ( 'country' => 'MOZAMBIQUE', 'states' => array ( '2069' => 'Niaaea', '2067' => 'Manica', '2065' => 'Gaza', '2066' => 'Inhambane', '2068' => 'Maputo', '2070' => 'Numpula', '2064' => 'Cabo Delgado', '2073' => 'Zambzia', '2071' => 'Sofala', '2072' => 'Tete', ), ),
			'MM' => array ( 'country' => 'MYANMAR', 'states' => array ( '2084' => 'Sagaing', '2075' => 'Bago', '2080' => 'Magway', '2081' => 'Mandalay', '2086' => 'Tanintharyi', '2087' => 'Yangon', '2074' => 'Ayeyarwady', '2077' => 'Kachin', '2078' => 'Kayah', '2079' => 'Kayin', '2076' => 'Chin', '2082' => 'Mon', '2083' => 'Rakhine', '2085' => 'Shan', ), ),
			'NA' => array ( 'country' => 'NAMIBIA', 'states' => array ( '2088' => 'Caprivi', '2089' => 'Erongo', '2090' => 'Hardap', '2091' => 'Karas', '2092' => 'Khomae', '2093' => 'Kunene', '2100' => 'Otjozondjupa', '2096' => 'Omaheke', '2095' => 'Okavango', '2098' => 'Oshana', '2097' => 'Omusati', '2099' => 'Oshikoto', '2094' => 'Ohangwena', ), ),
			'NR' => array ( 'country' => 'NAURU', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'NP' => array ( 'country' => 'NEPAL', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'NL' => array ( 'country' => 'NETHERLANDS', 'states' => array ( '2101' => 'Drente', '2102' => 'Flevoland', '2103' => 'Friesland', '2104' => 'Gelderland', '2105' => 'Groningen', '2106' => 'Limburg', '2107' => 'Noord Brabant', '2108' => 'Noord Holland', '2109' => 'Overijssel', '2110' => 'Utrecht', '2112' => 'Zuid Holland', '2111' => 'Zeeland', ), ),
			'AN' => array ( 'country' => 'NETHERLANDS ANTILLES', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'NC' => array ( 'country' => 'NEW CALEDONIA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'NZ' => array ( 'country' => 'NEW ZEALAND', 'states' => array ( '2113' => 'Auckland', '2114' => 'Bay of Plenty', '2115' => 'Canterbury', '2116' => 'Gisborne', '2117' => 'Hawkes\'s Bay', '2119' => 'Marlborough', '2118' => 'Manawatu-Wanganui', '2120' => 'Nelson', '2121' => 'Northland', '2122' => 'Otago', '2123' => 'Southland', '2125' => 'Tasman', '2124' => 'Taranaki', '2126' => 'Wellington', '2128' => 'waikato', '2127' => 'West Coast', ), ),
			'NI' => array ( 'country' => 'NICARAGUA', 'states' => array ( '2129' => 'Boaco', '2130' => 'Carazo', '2131' => 'Chinandega', '2132' => 'Chontales', '2133' => 'Estel', '2134' => 'Granada', '2135' => 'Jinotega', '2136' => 'Leon', '2137' => 'Madriz', '2138' => 'Managua', '2139' => 'Masaya', '2140' => 'Matagalpa', '2141' => 'Nueva Segovia', '2142' => 'Rivas', '2143' => 'Ro San Juan', '2144' => 'Zelaya', ), ),
			'NE' => array ( 'country' => 'NIGER', 'states' => array ( '2145' => 'Agadez', '2146' => 'Diffa', '2147' => 'Dosso', '2148' => 'Maradi', '2151' => 'Tillabri', '2152' => 'Zinder', '2149' => 'Niamey', '2150' => 'Tahoua', ), ),
			'NG' => array ( 'country' => 'NIGERIA', 'states' => array ( '2153' => 'Abia', '2155' => 'Adamawa', '2156' => 'Akwa Ibom', '2157' => 'Anambra', '2158' => 'Bauchi', '2160' => 'Benue', '2161' => 'Borno', '2159' => 'Bayelsa', '2162' => 'Cross River', '2163' => 'Delta', '2164' => 'Ebonyi', '2165' => 'Edo', '2166' => 'Ekiti', '2167' => 'Enugu', '2154' => 'Abuja Capital Territory', '2168' => 'Gombe', '2169' => 'Imo', '2170' => 'Jigawa', '2171' => 'Kaduna', '2174' => 'Kebbi', '2172' => 'Kano', '2175' => 'Kogi', '2173' => 'Katsina', '2176' => 'Kwara', '2177' => 'Lagos', '2178' => 'Nassarawa', '2179' => 'Niger', '2180' => 'Ogun', '2181' => 'Ondo', '2182' => 'Osun', '2183' => 'Oyo', '2184' => 'Plateau', '2185' => 'Rivers', '2186' => 'Sokoto', '2187' => 'Taraba', '2188' => 'Yobe', '2189' => 'Zamfara', ), ),
			'NU' => array ( 'country' => 'NIUE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'NF' => array ( 'country' => 'NORFOLK ISLAND', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'NO' => array ( 'country' => 'NORWAY', 'states' => array ( '2210' => 'stfold', '2190' => 'Akershus', '2201' => 'Oslo', '2194' => 'Hedmark', '2200' => 'Oppland', '2192' => 'Buskerud', '2209' => 'Vestfold', '2191' => 'Aust-Agder', '2208' => 'Vest-Agder', '2202' => 'Rogaland', '2195' => 'Hordaland', '2203' => 'Sogn og Fjordane', '2197' => 'Mre og Romsdal', '2205' => 'Sr-Trndelag', '2198' => 'Nord-Trndelag', '2199' => 'Nordland', '2207' => 'Troms', '2193' => 'Finumark', '2204' => 'Svalbard', '2196' => 'Jan Mayen', ), ),
			'MP' => array ( 'country' => 'NORTHERN MARIANA ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'OM' => array ( 'country' => 'OMAN', 'states' => array ( '2212' => 'Al Batinah', '2211' => 'Ad Dakhillyah', '2213' => 'Al Janblyah', '2217' => 'Masqat', '2218' => 'Musandam', '2215' => 'Ash Sharqlyah', '2214' => 'Al Wust', '2216' => 'Az Zahirah', ), ),
			'PK' => array ( 'country' => 'PAKISTAN', 'states' => array ( '2220' => 'Baluchistan (en)', '2222' => 'Islamabad', '2219' => 'Azad Rashmir', '2224' => 'Northern Areas', '2223' => 'North-West Frontier', '2225' => 'Punjab', '2226' => 'Sind (en)', '2221' => 'Federally Administered Tribal Aresa', ), ),
			'PW' => array ( 'country' => 'PALAU', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'PS' => array ( 'country' => 'PALESTINE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'PA' => array ( 'country' => 'PANAMA', 'states' => array ( '2227' => 'Bocas del Toro', '2229' => 'Cocl', '2230' => 'Coln', '2228' => 'Chiriqui', '2232' => 'Darin', '2233' => 'Herrera', '2234' => 'Loa Santoa', '2235' => 'Panam', '2236' => 'Veraguas', '2231' => 'Comarca de San Blas', ), ),
			'PG' => array ( 'country' => 'PAPUA NEW GUINEA', 'states' => array ( '2238' => 'Chimbu', '2237' => 'Central', '2239' => 'East New Britain', '2241' => 'Eastern Highlands', '2242' => 'Enga', '2240' => 'East Sepik', '2243' => 'Gulf', '2246' => 'Milne Bay', '2247' => 'Morobe', '2244' => 'Madang', '2245' => 'Manus', '2248' => 'National Capital District (Port Moresby)', '2249' => 'New Ireland', '2251' => 'Northern', '2250' => 'North Solomons', '2252' => 'Santaun', '2253' => 'Southern Highlands', '2254' => 'West New Britain', '2256' => 'Western Highlands', '2255' => 'Western', ), ),
			'PY' => array ( 'country' => 'PARAGUAY', 'states' => array ( '2266' => 'Concepcin', '2258' => 'Alto Paran', '2265' => 'Central', '2274' => 'eembucu', '2259' => 'Amambay', '2264' => 'Canindey', '2272' => 'Presidente Hayes', '2257' => 'Alto Paraguay', '2261' => 'Boquern', '2273' => 'San Pedro', '2267' => 'Cordillera', '2268' => 'Guair', '2263' => 'Caeguaz', '2262' => 'Caazapl', '2269' => 'Itapua', '2270' => 'Miaiones', '2271' => 'Paraguar', '2260' => 'Asuncin', ), ),
			'PE' => array ( 'country' => 'PERU', 'states' => array ( '2275' => 'Amazonas', '2276' => 'Ancash', '2277' => 'Apurmac', '2278' => 'Arequipa', '2279' => 'Ayacucho', '2280' => 'Cajamarca', '2282' => 'El Callao', '2281' => 'Cuzco', '2284' => 'Hunuco', '2283' => 'Huancavelica', '2285' => 'Ica', '2286' => 'Junn', '2287' => 'La Libertad', '2288' => 'Lambayeque', '2289' => 'Lima', '2290' => 'Loreto', '2291' => 'Madre de Dios', '2292' => 'Moquegua', '2293' => 'Pasco', '2294' => 'Piura', '2295' => 'Puno', '2296' => 'San Martn', '2297' => 'Tacna', '2298' => 'Tumbes', '2299' => 'Ucayali', ), ),
			'PH' => array ( 'country' => 'PHILIPPINES', 'states' => array ( '2300' => 'Abra', '2301' => 'Agusan del Norte', '2302' => 'Agusan del Sur', '2303' => 'Aklan', '2304' => 'Albay', '2305' => 'Antique', '2306' => 'Aurora', '2310' => 'Batasn', '2307' => 'Basilan', '2311' => 'Benguet', '2312' => 'Bohol', '2309' => 'Batangas', '2308' => 'Batanes', '2313' => 'Bukidnon', '2314' => 'Bulacan', '2315' => 'Cagayan', '2318' => 'Camiguin', '2316' => 'Camarines Norte', '2319' => 'Capiz', '2317' => 'Camarines Sur', '2320' => 'Catanduanes', '2321' => 'Cavite', '2322' => 'Cebu', '2324' => 'Davao Oriental', '2325' => 'Davao del Sur', '2323' => 'Davao', '2326' => 'Eastern Samar', '2327' => 'Ifugao', '2330' => 'Iloilo', '2328' => 'Ilocos Norte', '2329' => 'Ilocos Sur', '2331' => 'Isabela', '2332' => 'Kalinga-Apayso', '2334' => 'Laguna', '2335' => 'Lanao del Norte', '2336' => 'Lanao del Sur', '2337' => 'Leyte', '2333' => 'La Union', '2339' => 'Marinduque', '2338' => 'Maguindanao', '2340' => 'Masbate', '2341' => 'Mindoro Occidental', '2342' => 'Mindoro Oriental', '2345' => 'Mountain Province', '2343' => 'Misamis Occidental', '2344' => 'Misamis Oriental', '2348' => 'North Cotabato', '2346' => 'Negroe Occidental', '2347' => 'Negros Oriental', '2349' => 'Northern Samar', '2350' => 'Nueva Ecija', '2351' => 'Nueva Vizcaya', '2353' => 'Pampanga', '2354' => 'Pangasinan', '2352' => 'Palawan', '2355' => 'Quezon', '2356' => 'Quirino', '2357' => 'Rizal', '2358' => 'Romblon', '2361' => 'South Cotabato', '2359' => 'Siquijor', '2362' => 'Southern Leyte', '2364' => 'Sulu', '2360' => 'Sorsogon', '2363' => 'Sultan Kudarat', '2365' => 'Surigao del Norte', '2366' => 'Surigao del Sur', '2367' => 'Tarlac', '2368' => 'Tawi-Tawi', '2369' => 'Western Samar', '2371' => 'Zamboanga del Norte', '2372' => 'Zamboanga del Sur', '2370' => 'Zambales', ), ),
			'PN' => array ( 'country' => 'PITCAIRN', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'PL' => array ( 'country' => 'POLAND', 'states' => array ( '2375' => 'Bielsko', '2374' => 'Bialystok', '2373' => 'Biala Podlaska', '2376' => 'Bydgoszcz', '2377' => 'Chelm', '2378' => 'Ciechanw', '2379' => 'Czestochowa', '2380' => 'Elblag', '2381' => 'Gdask', '2382' => 'Gorzw', '2383' => 'Jelenia Gra', '2385' => 'Katowice', '2386' => 'Kielce', '2384' => 'Kalisz', '2387' => 'Konin', '2388' => 'Koszalin', '2389' => 'Krakw', '2390' => 'Krosno', '2395' => 'Ldz', '2392' => 'Leszno', '2391' => 'Legnica', '2393' => 'Lomza', '2394' => 'Lublin', '2396' => 'Nowy Sacz', '2397' => 'Olsztyn', '2398' => 'Opole', '2399' => 'Ostroleka', '2403' => 'Podlaskie', '2400' => 'Pila', '2402' => 'Plock', '2404' => 'Pozna', '2405' => 'Przemysl', '2401' => 'Piotrkw', '2406' => 'Radom', '2407' => 'Rzeszw', '2408' => 'Siedlce', '2409' => 'Sieradz', '2410' => 'Skierniewice', '2411' => 'Slupsk', '2412' => 'Suwalki', '2413' => 'Szczecin', '2416' => 'Toru', '2415' => 'Tarnw', '2414' => 'Tarnobrzeg', '2417' => 'Warazawa', '2418' => 'Wablzych', '2420' => 'Wloclawek', '2419' => 'Wielkopolskie', '2421' => 'Wroclaw', '2422' => 'Zamosc', '2423' => 'Zielona Gra', ), ),
			'PT' => array ( 'country' => 'PORTUGAL', 'states' => array ( '2424' => 'Aveiro', '2425' => 'Beja', '2426' => 'Braga', '2427' => 'Bragana', '2428' => 'Castelo Branco', '2429' => 'Coimbra', '2443' => 'vora', '2430' => 'Faro', '2431' => 'Guarda', '2432' => 'Leiria', '2433' => 'Lisboa', '2434' => 'Portalegre', '2435' => 'Porto', '2438' => 'Santarm', '2439' => 'Setbal', '2440' => 'Viana do Castelo', '2441' => 'Vila Real', '2442' => 'Viseu', '2437' => 'Regiao Autonoma dos Aores', '2436' => 'Regiao AutOnoma da Madeira', ), ),
			'PR' => array ( 'country' => 'PUERTO RICO', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'QA' => array ( 'country' => 'QATAR', 'states' => array ( '2444' => 'Ad Dawhah', '2445' => 'Al Ghuwayryah', '2450' => 'Jariyan al Btnah', '2446' => 'Al Jumayliyah', '2447' => 'Al Khawr', '2451' => 'Madinat ash Shamal', '2449' => 'Ar Rayyn', '2452' => 'Umm Sall', '2448' => 'Al Wakrah', ), ),
			'RE' => array ( 'country' => 'REUNION', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'RO' => array ( 'country' => 'ROMANIA', 'states' => array ( '2453' => 'Alba', '2455' => 'Arges', '2454' => 'Arad', '2462' => 'Bucuresti', '2456' => 'Bacau', '2457' => 'Bihor', '2458' => 'Bistrita-Nasaud', '2460' => 'Braila', '2459' => 'Botosani', '2461' => 'Brasov', '2463' => 'Buzau', '2466' => 'Cluj', '2464' => 'Calarasi', '2465' => 'Caras-Severin', '2467' => 'Constanta', '2468' => 'Covasna', '2470' => 'Dmbovita', '2469' => 'Dolj', '2473' => 'Gorj', '2471' => 'Galati', '2472' => 'Giurgiu', '2475' => 'Hunedoara', '2474' => 'Harghita', '2476' => 'Ialomita', '2477' => 'Iasi', '2479' => 'Mehedinti', '2478' => 'Maramures', '2480' => 'Mures', '2481' => 'Neamt', '2482' => 'Olt', '2483' => 'Prahova', '2486' => 'Sibiu', '2484' => 'Sa laj', '2485' => 'Satu Mare', '2487' => 'Suceava', '2490' => 'Tulcea', '2489' => 'Timis', '2488' => 'Teleorman', '2493' => 'Vlcea', '2492' => 'Vrancea', '2491' => 'Vaslui', ), ),
			'RU' => array ( 'country' => 'RUSSIAN FEDERATION', 'states' => array ( '2494' => 'Adygeya, Respublika', '2495' => 'Aginskiy Buryatskiy avtonomnyy', '2496' => 'Altay, Respublika', '2497' => 'Altayskiy kray', '2498' => 'Amurskaya oblast\'', '2499' => 'Arkhangel\'skaya oblast\'', '2500' => 'Astrakhanskaya oblast\'', '2501' => 'Bashkortostan, Respublika', '2502' => 'Belgorodskaya oblast\'', '2503' => 'Bryanskaya oblast\'', '2504' => 'Buryatiya, Respublika', '2505' => 'Chechenskaya Respublika', '2506' => 'Chelyabinskaya oblast\'', '2507' => 'Chitinskaya oblast\'', '2508' => 'Chukotskiy avtonomnyy okrug', '2509' => 'Chuvashskaya Respublika', '2510' => 'Dagestan, Respublika', '2511' => 'Evenkiyskiy avtonomnyy okrug', '2512' => 'Ingushskaya Respublika', '2513' => 'Irkutskaya oblast\'', '2514' => 'Ivanovskaya oblast\'', '2519' => 'Kamchatskaya oblast\'', '2515' => 'Kabardino-Balkarskaya', '2520' => 'Karachayevo-Cherkesskaya Respublika', '2531' => 'Krasnodarskiy kray', '2522' => 'Kemerovskaya oblast\'', '2516' => 'Kaliningradskaya oblast\'', '2533' => 'Kurganskaya oblast\'', '2523' => 'Khabarovskiy kray', '2525' => 'Khanty-Mansiyskiy avtonomnyy okrug', '2526' => 'Kirovskaya oblast\'', '2524' => 'Khakasiya, Respublika', '2517' => 'Kalmykiya, Respublika', '2518' => 'Kaluzhskaya oblast\'', '2527' => 'Komi, Respublika', '2528' => 'Komi-Permyatskiy avtonomnyy okrug', '2529' => 'Koryakskiy avtonomnyy okrug', '2530' => 'Kostromskaya oblast\'', '2521' => 'Kareliya, Respublika', '2534' => 'Kurskaya oblast\'', '2532' => 'Krasnoyarskiy kray', '2535' => 'Leningradskaya oblast\'', '2536' => 'Lipetskaya oblast\'', '2537' => 'Magadanskaya oblast\'', '2538' => 'Mariy El, Respublika', '2539' => 'Mordoviya, Respublika', '2540' => 'Moskovskaya oblast\'', '2541' => 'Moskva', '2542' => 'Murmanskaya oblast\'', '2543' => 'Nenetskiy avtonomnyy okrug', '2545' => 'Novgorodskaya oblast\'', '2544' => 'Nizhegorodskaya oblast\'', '2546' => 'Novosibirskaya oblast\'', '2547' => 'Omskaya oblast\'', '2548' => 'Orenburgskaya oblast\'', '2549' => 'Orlovskaya oblast\'', '2551' => 'Permskaya oblast\'', '2550' => 'Penzenskaya oblast\'', '2552' => 'Primorskiy kray', '2553' => 'Pskovskaya oblast\'', '2554' => 'Rostovskaya oblast\'', '2555' => 'Ryazanskaya oblast\'', '2556' => 'Sakha, Respublika [Yakutiya]', '2557' => 'Sakhalinskaya oblast\'', '2558' => 'Samarskaya oblast\'', '2560' => 'Saratovskaya oblast\'', '2561' => 'Severnaya Osetiya, Respublika', '2562' => 'Smolenskaya oblast\'', '2559' => 'Sankt-Peterburg', '2563' => 'Stavropol\'skiy kray', '2564' => 'Sverdlovskaya oblast\'', '2566' => 'Tatarstan, Respublika', '2565' => 'Tambovskaya oblast\'', '2567' => 'Taymyrskiy (Dolgano-Nenetskiy', '2568' => 'Tomskaya oblast\'', '2569' => 'Tul\'skaya oblast\'', '2570' => 'Tverskaya oblast\'', '2572' => 'Tyva, Respublika [Tuva]', '2571' => 'Tyumenskaya oblast\'', '2573' => 'Udmurtskaya Respublika', '2574' => 'Ul\'yanovskaya oblast\'', '2575' => 'Ust\'-Ordynskiy Buryatskiy', '2577' => 'Volgogradskaya oblast\'', '2576' => 'Vladimirskaya oblast\'', '2578' => 'Vologodskaya oblast\'', '2579' => 'Voronezhskaya oblast\'', '2580' => 'Yamalo-Nenetskiy avtonomnyy okrug', '2581' => 'Yaroslavskaya oblast\'', '2582' => 'Yevreyskaya avtonomnaya oblast\'', ), ),
			'RW' => array ( 'country' => 'RWANDA', 'states' => array ( '2588' => 'Gitarama', '2583' => 'Butare', '2586' => 'Gikongoro', '2585' => 'Cyangugu', '2590' => 'Kibuye', '2587' => 'Gisenyi', '2594' => 'Ruhengeri', '2584' => 'Byumba', '2589' => 'Kibungo', '2591' => 'Kigali-Rural Kigali y\' Icyaro', '2592' => 'Kigali-Ville Kigali Ngari', '2593' => 'Mutara', ), ),
			'SH' => array ( 'country' => 'SAINT HELENA', 'states' => array ( '2766' => 'Ascension', '2767' => 'Saint Helena', '2768' => 'Tristan da Cunha', ), ),
			'ST' => array ( 'country' => 'SAO TOME AND PRINCIPE', 'states' => array ( '2595' => 'Prncipe', '2596' => 'Sao Tom', ), ),
			'BL' => array ( 'country' => 'SAINT BARTHELEMY', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'KN' => array ( 'country' => 'SAINT KITTS AND NEVIS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'LC' => array ( 'country' => 'SAINT LUCIA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'MF' => array ( 'country' => 'SAINT MARTIN', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'PM' => array ( 'country' => 'SAINT PIERRE AND MIQUELON', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'VC' => array ( 'country' => 'SAINT VINCENT AND THE GRENADINES', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SM' => array ( 'country' => 'SAN MARINO', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SP' => array ( 'country' => 'SATELLITE', 'states' => array ( '3841' => 'Global Networks Satellite', '3843' => 'Globalstar Satellite', '3846' => 'Inmarsat BGAN', '3844' => 'Inmarsat Atlantic Ocean East', '3845' => 'Inmarsat Atlantic Ocean West', '3847' => 'Inmarsat Indian Ocean', '3848' => 'Inmarsat Pacific Ocean', '3850' => 'Iridium Satellite', '3849' => 'Inmarsat SNAC', '3851' => 'Other Satellite', '3853' => 'Thuraya Satellite', '3852' => 'Telespazio Satellite', '3842' => 'Global Satellite', ), ),
			'SA' => array ( 'country' => 'SAUDI ARABIA', 'states' => array ( '2602' => 'Ar Riyad', '2607' => 'Makkah', '2600' => 'Al Madlnah', '2603' => 'Ash Sharqlyah', '2601' => 'Al Qasim', '2605' => 'H\'il', '2609' => 'Tabk', '2598' => 'Al H,udd ash Shamallyah', '2606' => 'Jlzn', '2608' => 'Najran', '2597' => 'Al Batah', '2599' => 'Al Jawf', '2604' => 'Asr', ), ),
			'WS' => array ( 'country' => 'SAMOA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SN' => array ( 'country' => 'SENEGAL', 'states' => array ( '2611' => 'Diourbel', '2610' => 'Dakar', '2612' => 'Fatick', '2614' => 'Kolda', '2613' => 'Kaolack', '2615' => 'Louga', '3516' => 'Matam', '2616' => 'Saint-Louis', '2617' => 'Tambacounda', '2618' => 'This', '2619' => 'Ziguinchor', ), ),
			'RS' => array ( 'country' => 'SERBIA', 'states' => array ( '3477' => 'Crna Gora', '3478' => 'Kosovo-Metohija', '3479' => 'Srbija', '3480' => 'Vojvodina', ), ),
			'SC' => array ( 'country' => 'SEYCHELLES', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SG' => array ( 'country' => 'SINGAPORE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SL' => array ( 'country' => 'SIERRA LEONE', 'states' => array ( '2620' => 'Eastern', '2621' => 'Northern', '2622' => 'Southern', '2623' => 'western Area (Freetown)', ), ),
			'SK' => array ( 'country' => 'SLOVAKIA', 'states' => array ( '2624' => 'Banskobyatricky kraj', '2625' => 'Bratislavsky kraj', '2626' => 'Kolicky kraj', '2627' => 'Nitrianaky kraj', '2628' => 'Prebovaky kraj', '2630' => 'Trnavaky kraj', '2629' => 'Trenciansky kraj', '2631' => 'Zilinaky kraj', ), ),
			'SI' => array ( 'country' => 'SLOVENIA', 'states' => array ( '2640' => 'Pomurska', '2639' => 'Podravska', '2635' => 'Koroka', '2641' => 'Savinjska', '2643' => 'Zasavska', '2642' => 'Spodnjeposavska', '2632' => 'Dolenjska', '2638' => 'Osrednjeslovenska', '2633' => 'Gorenjska', '2636' => 'Notranjsko-kraka', '2634' => 'Goriska', '2637' => 'Obalno-kraka', ), ),
			'SB' => array ( 'country' => 'SOLOMON ISLANDS', 'states' => array ( '2645' => 'Central', '2644' => 'Capital Territory (Honiara)', '2646' => 'Guadalcanal', '2647' => 'Isabel', '2648' => 'Makira', '2649' => 'Malaita', '2650' => 'Temotu', '2651' => 'Western', ), ),
			'SO' => array ( 'country' => 'SOMALIA', 'states' => array ( '2652' => 'Awdal', '2653' => 'Bakool', '2654' => 'Banaadir', '2655' => 'Bari', '2656' => 'Bay', '2657' => 'Galguduud', '2658' => 'Gedo', '2659' => 'Hiirsan', '2660' => 'Jubbada Dhexe', '2661' => 'Jubbada Hoose', '2662' => 'Mudug', '2663' => 'Nugaal', '2664' => 'Saneag', '2665' => 'Shabeellaha Dhexe', '2666' => 'Shabeellaha Hoose', '2667' => 'Sool', '2668' => 'Togdheer', '2669' => 'Woqooyi Galbeed', ), ),
			'ZA' => array ( 'country' => 'SOUTH AFRICA', 'states' => array ( '2670' => 'Eastern Cape', '2671' => 'Free State', '2672' => 'Gauteng', '2674' => 'Mpumalanga', '2676' => 'Northern Cape', '2673' => 'Kwazulu-Natal', '2677' => 'Northern Province', '2675' => 'North-West', '2678' => 'Western Cape', ), ),
			'GS' => array ( 'country' => 'SOUTH GEORGIA AND SOUTH SANDWICH ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SS' => array ( 'country' => 'SOUTH SUDAN', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'ES' => array ( 'country' => 'SPAIN', 'states' => array ( '2680' => 'Alicante', '2679' => 'Albacete', '2681' => 'Almera', '2682' => 'Andaluca', '2683' => 'Aragn', '2740' => 'vila', '2686' => 'Barcelona', '2685' => 'Badajoz', '2736' => 'Vizcaya', '2687' => 'Burgos', '2710' => 'La Corua', '2698' => 'Cdiz', '2697' => 'Cceres', '2694' => 'Ceuta', '2691' => 'Castilla y Len', '2692' => 'Castilla-La Mancha', '2688' => 'Canarias', '2699' => 'Crdoba', '2695' => 'Ciudad Real', '2690' => 'Castelln', '2693' => 'Catalua', '2696' => 'Cuenca', '2700' => 'Extremadura', '2701' => 'Galicia', '2712' => 'Las Palmas', '2702' => 'Girona [Gerona]', '2703' => 'Granada', '2704' => 'Guadalajara', '2706' => 'Huelva', '2707' => 'Huesca', '2709' => 'Jan', '2714' => 'Lleida [Lrida]', '2713' => 'Len', '2711' => 'La Rioja', '2715' => 'Lugo', '2716' => 'Madrid', '2719' => 'Mlaga', '2717' => 'Melilla', '2718' => 'Murcia', '2720' => 'Navarra', '2684' => 'Asturias', '2721' => 'Orense', '2722' => 'Palencia', '2708' => 'Islas Baleares', '2724' => 'Pontevedra', '2723' => 'Pas Vasco', '2689' => 'Cantabria', '2725' => 'Salamanca', '2728' => 'Sevilla', '2727' => 'Segovia', '2729' => 'Soria', '2705' => 'Guipzcoa', '2730' => 'Tarragona', '2731' => 'Teruel', '2726' => 'Santa Cruz de Tenerife', '2732' => 'Toledo', '2733' => 'Valencia', '2735' => 'Valladolid', '2734' => 'Valenciana, Comunidad', '2739' => 'lava', '2738' => 'Zaragoza', '2737' => 'Zamora', ), ),
			'LK' => array ( 'country' => 'SRI LANKA', 'states' => array ( '2745' => 'Colombo', '2747' => 'Gampaha', '2750' => 'Kalutara', '2751' => 'Kandy', '2756' => 'Matale', '2760' => 'Nuwara Eliya', '2746' => 'Galle', '2757' => 'Matara', '2748' => 'Hambantota', '2749' => 'Jaffna', '2753' => 'Kilinochchi', '2755' => 'Mannar', '2765' => 'VavunLya', '2759' => 'Mullaittivu', '2744' => 'Batticaloa', '2741' => 'Ampara', '2764' => 'Trincomalee', '2754' => 'Kurunegala', '2762' => 'Puttalum', '2742' => 'Anuradhapura', '2761' => 'Polonnaruwa', '2743' => 'Badulla', '2758' => 'Monaragala', '2763' => 'Ratnapura', '2752' => 'Kegalla', ), ),
			'SD' => array ( 'country' => 'SUDAN', 'states' => array ( '2779' => 'Ash Shamallyah', '2790' => 'Shamal Darfr', '2773' => 'Al Khartum', '2776' => 'An Nil', '2788' => 'Kassala', '2774' => 'Al Qadarif', '2772' => 'Al Jazirah', '2777' => 'An Nil al Abyaq', '2791' => 'Shamal Kurdufan', '2783' => 'Gharb Kurdufan', '2785' => 'Janub Darfur', '2782' => 'Gharb Darfur', '2786' => 'Janb Rurdufan', '2781' => 'Gharb Bar al Ghazal', '2789' => 'Shamal Batr al Ghazal', '2784' => 'Gharb al Istiwa\'yah', '2780' => 'Bahr al Jabal', '2771' => 'Al Buhayrat', '2792' => 'Sharq al Istiwa\'iyah', '2787' => 'Jnqall', '2794' => 'Warab', '2775' => 'Al Wahdah', '2769' => 'A\'al an Nl', '2778' => 'An Nil al Azraq', '2793' => 'Sinnar', '2770' => 'Al Bah al Ahmar', ), ),
			'SR' => array ( 'country' => 'SURINAME', 'states' => array ( '2795' => 'Brokopondo', '2796' => 'Commewijne', '2797' => 'Coronie', '2798' => 'Marowijne', '2799' => 'Nickerie', '2801' => 'Paramaribo', '2800' => 'Para', '2802' => 'Saramacca', '2803' => 'Sipaliwini', '2804' => 'Wanica', ), ),
			'SJ' => array ( 'country' => 'SVALBARD AND JAN MAYEN ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'SZ' => array ( 'country' => 'SWAZILAND', 'states' => array ( '2805' => 'Hhohho', '2806' => 'Lubombo', '2807' => 'Manzini', '2808' => 'Shiselweni', ), ),
			'SE' => array ( 'country' => 'SWEDEN', 'states' => array ( '2820' => 'Stockholms ln', '2824' => 'Vsterbottens ln', '2818' => 'Norrbottena ln', '2822' => 'Uppsala ln', '2821' => 'Sdermanlands ln', '2829' => 'stergotlands ln', '2815' => 'Jonkopings ln', '2817' => 'Kronoberge ln', '2816' => 'Kalmar ln', '2811' => 'Gotlands ln', '2809' => 'Blekinge ln', '2819' => 'Skne ln', '2813' => 'Hallands ln', '2827' => 'Vstra Gotalands ln', '2823' => 'Vrmlanda ln', '2828' => 'rebro ln', '2826' => 'Vstmanlanda ln', '2810' => 'Dalarnas ln', '2812' => 'Gvleborge ln', '2825' => 'Vsternorrlands ln', '2814' => 'Jamtlande ln', ), ),
			'CH' => array ( 'country' => 'SWITZERLAND', 'states' => array ( '2830' => 'Aargau', '2832' => 'Appenzell Innerrhoden', '2831' => 'Appenzell Ausserrhoden', '2835' => 'Bern', '2833' => 'Basel-Landschaft', '2834' => 'Basel-Stadt', '2836' => 'Fribourg', '2837' => 'Genve', '2838' => 'Glarus', '2839' => 'Graubnden', '2840' => 'Jura', '2841' => 'Luzern', '2842' => 'Neuchatel', '2843' => 'Nidwalden', '2844' => 'Obwalden', '2845' => 'Sankt Gallen', '2846' => 'Schaffhausen', '2848' => 'Solothurn', '2847' => 'Schwyz', '2849' => 'Thurgau', '2850' => 'Ticino', '2851' => 'Uri', '2853' => 'Vaud', '2852' => 'Valais', '2854' => 'Zug', '2855' => 'Zrich', ), ),
			'SY' => array ( 'country' => 'SYRIA', 'states' => array ( '2863' => 'Dimashq', '2861' => 'Dar\'', '2862' => 'Dayr az Zawr', '2856' => 'Al Hasakah', '2867' => 'Jim\'', '2864' => 'Halab', '2865' => 'Hamah', '2866' => 'Idlib', '2857' => 'Al Ladhiqiyah', '2858' => 'Al Qunaytirah', '2859' => 'Ar Raqqah', '2868' => 'Rif Dimashq', '2860' => 'As Suwayd\'', '2869' => 'Tarts', ), ),
			'TW' => array ( 'country' => 'TAIWAN', 'states' => array ( '2870' => 'Changhua', '2871' => 'Chiayi', '2873' => 'Hsinchu', '2874' => 'Hualien', '2875' => 'Ilan', '2877' => 'Keelung', '2876' => 'Kaohsiung', '2878' => 'Miaoli', '2879' => 'Nantou', '2880' => 'Penghu', '2881' => 'Pingtung', '2886' => 'Taoyuan', '2883' => 'Tainan', '2884' => 'Taipei', '2885' => 'Taitung', '2882' => 'Taichung', '2887' => 'Yunlin', ), ),
			'TJ' => array ( 'country' => 'TAJIKISTAN', 'states' => array ( '2888' => 'Gorno-Badakhshan', '2889' => 'Karategin', '2890' => 'Khatlon', '2891' => 'Leninabad', ), ),
			'TZ' => array ( 'country' => 'TANZANIA', 'states' => array ( '2892' => 'Arusha', '2893' => 'Dar es Salaam', '2894' => 'Dodoma', '2895' => 'Iringa', '2896' => 'Kagera', '2897' => 'Kaskazini Pemba', '2898' => 'Kaskazini Unguja', '2916' => 'Xigoma', '2899' => 'Kilimanjaro', '2910' => 'Rusini Pemba', '2900' => 'Kusini Unguja', '2901' => 'Lindi', '2902' => 'Mara', '2903' => 'Mbeya', '2904' => 'Mjini Magharibi', '2905' => 'Morogoro', '2906' => 'Mtwara', '2907' => 'Mwanza', '2908' => 'Pwani', '2909' => 'Rukwa', '2911' => 'Ruvuma', '2912' => 'Shinyanga', '2913' => 'Singida', '2914' => 'Tabora', '2915' => 'Tanga', '3518' => 'Manyara', ), ),
			'TH' => array ( 'country' => 'THAILAND', 'states' => array ( '2933' => 'Krung Thep Maha Nakhon Bangkok', '2973' => 'Samut Prakan', '2951' => 'Nonthaburi', '2952' => 'Pathum Thani', '2963' => 'Phra Nakhon Si Ayutthaya', '2918' => 'Ang Thong', '2937' => 'Lop Buri', '2979' => 'Sing Buri', '2921' => 'Chai Nat', '2976' => 'Saraburi', '2926' => 'Chon Buri', '2969' => 'Rayong', '2923' => 'Chanthaburi', '2987' => 'Trat', '2920' => 'Chachoengsao', '2965' => 'Prachin Buri', '2941' => 'Nakhon Nayok', '2971' => 'Sa Kaeo', '2944' => 'Nakhon Ratchasima', '2919' => 'Buri Ram', '2984' => 'Surin', '2978' => 'Si Sa Ket', '2988' => 'Ubon Ratchathani', '2993' => 'Yasothon', '2922' => 'Chaiyaphum', '2917' => 'Amnat Charoen', '2949' => 'Nong Bua Lam Phu', '2931' => 'Khon Kaen', '2989' => 'Udon Thani', '2936' => 'Loei', '2950' => 'Nong Khai', '2939' => 'Maha Sarakham', '2970' => 'Roi Et', '2928' => 'Kalasin', '2972' => 'Sakon Nakhon', '2943' => 'Nakhon Phanom', '2940' => 'Mukdahan', '2924' => 'Chiang Mai', '2935' => 'Lamphun', '2934' => 'Lampang', '2991' => 'Uttaradit', '2964' => 'Phrae', '2947' => 'Nan', '2958' => 'Phayao', '2925' => 'Chiang Rai', '2938' => 'Mae Hong Son', '2945' => 'Nakhon Sawan', '2990' => 'Uthai Thani', '2929' => 'Kamphasng Phet', '2985' => 'Tak', '2981' => 'Sukhothai', '2962' => 'Phitsanulok', '2961' => 'Phichit', '2959' => 'Phetchabun', '2968' => 'Ratchaburi', '2930' => 'Kanchanaburi', '2982' => 'Suphan Buri', '2942' => 'Nakhon Pathom', '2974' => 'Samut Sakhon', '2975' => 'Samut Songkhram', '2960' => 'Phetchaburi', '2966' => 'Prachuap Khiri Khan', '2946' => 'Nakhon Si Thammarat', '2932' => 'Krabi', '2955' => 'Phangnga', '2954' => 'Phaket', '2983' => 'Surat Thani', '2967' => 'Ranong', '2927' => 'Chumphon', '2980' => 'Songkhla', '2977' => 'Satun', '2986' => 'Trang', '2956' => 'Phatthalung', '2953' => 'Pattani', '2992' => 'Yala', '2948' => 'Narathiwat', '2957' => 'Phatthaya', ), ),
			'TL' => array ( 'country' => 'TIMOR-LESTE', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'TG' => array ( 'country' => 'TOGO', 'states' => array ( '2994' => 'Centre', '2995' => 'Kara', '2996' => 'Maritime (Rgion)', '2997' => 'Plateaux', '2998' => 'Savannes', ), ),
			'TK' => array ( 'country' => 'TOKELAU', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'TO' => array ( 'country' => 'TONGA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'TT' => array ( 'country' => 'TRINIDAD AND TOBAGO', 'states' => array ( '2999' => 'Arima', '3000' => 'Chaguanas', '3001' => 'Couva-Tabaquite-Talparo', '3002' => 'Diego Martin', '3003' => 'Eastern Tobago', '3004' => 'Penal-Debe', '3006' => 'Port of Spain', '3007' => 'Princes Town', '3005' => 'Point Fortin', '3008' => 'Rio Claro-Mayaro', '3009' => 'San Fernando', '3011' => 'Sangre Grande', '3012' => 'Siparia', '3010' => 'San Juan-Laventille', '3013' => 'Tunapuna-Piarco', '3014' => 'Western Tobago', ), ),
			'TN' => array ( 'country' => 'TUNISIA', 'states' => array ( '3036' => 'Tunis', '3023' => 'L\'Ariana', '3015' => 'Ben Arous', '3517' => 'Manouba', '3028' => 'Naboul', '3037' => 'Zaghouan', '3016' => 'Bizerte', '3017' => 'Bja', '3020' => 'Jendouba', '3024' => 'Le Ref', '3032' => 'Siliana', '3021' => 'Kairouan', '3029' => 'Rasserine', '3031' => 'Sidi Bouxid', '3033' => 'Sousse', '3027' => 'Moneatir', '3025' => 'Mahdia', '3030' => 'Sfax', '3019' => 'Gafsa', '3035' => 'Tozeur', '3022' => 'Kebili', '3018' => 'Gabs', '3026' => 'Medenine', '3034' => 'Tataouine', ), ),
			'TR' => array ( 'country' => 'TURKEY', 'states' => array ( '3039' => 'Adana', '3038' => 'Ad yaman', '3040' => 'Afyon', '3041' => 'Ag r', '3043' => 'Amasya', '3044' => 'Ankara', '3045' => 'Antalya', '3047' => 'Artvin', '3048' => 'Ayd n', '3049' => 'Bal kesir', '3053' => 'Bilecik', '3054' => 'Bingl', '3055' => 'Bitlis', '3056' => 'Bolu', '3057' => 'Burdur', '3058' => 'Bursa', '3115' => 'anakkale', '3116' => 'ank r', '3117' => 'orum', '3059' => 'Denizli', '3060' => 'Diyarbak r', '3061' => 'Edirne', '3062' => 'Elaz g', '3063' => 'Erzincan', '3064' => 'Erzurum', '3065' => 'Eskisehir', '3066' => 'Gaziantep', '3067' => 'Giresun', '3068' => 'Gmshane', '3069' => 'Hakkri', '3070' => 'Hatay', '3072' => 'Isparta', '3075' => 'Iel', '3073' => 'Istanbul', '3074' => 'Izmir', '3079' => 'Kars', '3080' => 'Kastamonu', '3081' => 'Kayseri', '3086' => 'Krklareli', '3087' => 'Krsehir', '3083' => 'Kocaeli', '3084' => 'Konya', '3088' => 'Ktahya', '3089' => 'Malatya', '3090' => 'Manisa', '3076' => 'Kahamanmaras', '3091' => 'Mardin', '3092' => 'Mug la', '3093' => 'Mus', '3094' => 'Nevsehir', '3095' => 'Nig de', '3096' => 'Ordu', '3098' => 'Rize', '3099' => 'Sakarya', '3100' => 'Samsun', '3102' => 'Siirt', '3103' => 'Sinop', '3104' => 'Sivas', '3106' => 'Tekirdag', '3107' => 'Tokat', '3108' => 'Trabzon', '3109' => 'Tunceli', '3101' => 'Sanlurfa', '3110' => 'Usak', '3111' => 'Van', '3113' => 'Yozgat', '3114' => 'Zonguldak', '3042' => 'Aksaray', '3052' => 'Bayburt', '3078' => 'Karaman', '3085' => 'Krkkale', '3051' => 'Batman', '3105' => 'Srnak', '3050' => 'Bart n', '3046' => 'Ardahan', '3071' => 'Igdr', '3112' => 'Yalova', '3077' => 'Karabk', '3082' => 'Kilis', '3097' => 'Osmaniye', ), ),
			'TM' => array ( 'country' => 'TURKMENISTAN', 'states' => array ( '3118' => 'Ahal', '3119' => 'Balkan', '3120' => 'Da\'howuz', '3121' => 'Lebap', '3122' => 'Mary', ), ),
			'TC' => array ( 'country' => 'TURKS AND CAICOS ISLANDS', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'TV' => array ( 'country' => 'TUVALU', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'UG' => array ( 'country' => 'UGANDA', 'states' => array ( '3123' => 'Apac', '3124' => 'Arua', '3125' => 'Bundibugyo', '3126' => 'Bushenyi', '3127' => 'Gulu', '3128' => 'Hoima', '3129' => 'Iganga', '3130' => 'Jinja', '3136' => 'Kapchorwa', '3137' => 'Kasese', '3131' => 'Kabale', '3132' => 'Kabarole', '3139' => 'Kiboga', '3140' => 'Kisoro', '3141' => 'Kitgum', '3134' => 'Kampala', '3138' => 'Kibeale', '3133' => 'Kalangala', '3135' => 'Kamuli', '3142' => 'Kotido', '3143' => 'Kumi', '3144' => 'Lira', '3145' => 'Luwero', '3148' => 'Mbale', '3149' => 'Mbarara', '3150' => 'Moroto', '3151' => 'Moyo', '3152' => 'Mpigi', '3147' => 'Masindi', '3146' => 'Masaka', '3153' => 'Mubende', '3154' => 'Mukono', '3155' => 'Nebbi', '3156' => 'Ntungamo', '3157' => 'Pallisa', '3158' => 'Rakai', '3159' => 'Rukungiri', '3160' => 'Soroti', '3161' => 'Tororo', ), ),
			'UA' => array ( 'country' => 'UKRAINE', 'states' => array ( '3184' => 'Vinnyts\'ka Oblast\'', '3185' => 'Volyns\'ka Oblast\'', '3175' => 'Luhans\'ka Oblast\'', '3165' => 'Dnipropetrovs\'ka Oblast\'', '3166' => 'Donets\'ka Oblast\'', '3188' => 'Zhytomyrs\'ka Oblast\'', '3186' => 'Zakarpats\'ka Oblast\'', '3187' => 'Zaporiz\'ka Oblast\'', '3167' => 'Ivano-Frankivs\'ka Oblast\'', '3173' => 'Kyiv', '3172' => 'Kyivs\'ka Oblast\'', '3171' => 'Kirovohrads\'ka Oblast\'', '3181' => 'Sevastopol\'', '3179' => 'Respublika Krym', '3174' => 'L\'vivs\'ka Oblast\'', '3176' => 'Mykolaivs\'ka Oblast\'', '3177' => 'Odes\'ka Oblast\'', '3178' => 'Poltavs\'ka Oblast\'', '3180' => 'Rivnens\'ka Oblast\'', '3182' => 'Sums\'ka Oblast\'', '3183' => 'Ternopil\'s\'ka Oblast\'', '3168' => 'Kharkivs\'ka Oblast\'', '3169' => 'Khersons\'ka Oblast\'', '3170' => 'Khmel\'nyts\'ka Oblast\'', '3162' => 'Cherkas\'ka Oblast\'', '3163' => 'Chernihivs\'ka Oblast\'', '3164' => 'Chernivets\'ka Oblast\'', ), ),
			'AE' => array ( 'country' => 'UNITED ARAB EMIRATES', 'states' => array ( '3189' => '\'Ajman', '3190' => 'Abu Zaby', '3193' => 'Dubayy', '3191' => 'Al Fujayrah', '3194' => 'R\'as al Khaymah', '3192' => 'Ash Shariqah', '3195' => 'Umm al Qaywayn', ), ),
			'GB' => array ( 'country' => 'UNITED KINGDOM', 'states' => array ( '3660' => 'Aberdeenshire', '3661' => 'Aberdeen City', '3662' => 'Argyll and Bute', '3663' => 'Isle of Anglesey', '3664' => 'Angus', '3206' => 'Antrim', '3665' => 'Ards', '3207' => 'Armagh', '3666' => 'Bath and North East Somerset', '3667' => 'Blackburn with Darwen', '3196' => 'Bedfordshire', '3668' => 'Barking and Dagenham', '3669' => 'Brent', '3670' => 'Bexley', '3671' => 'Belfast', '3672' => 'Bridgend', '3673' => 'Blaenau Gwent', '3674' => 'Birmingham', '3199' => 'Buckinghamshire', '3675' => 'Ballymena', '3676' => 'Ballymoney', '3677' => 'Bournemouth', '3678' => 'Banbridge', '3679' => 'Barnet', '3680' => 'Brighton and Hove', '3681' => 'Barnsley', '3682' => 'Bolton', '3683' => 'Blackpool', '3684' => 'Bracknell Forest', '3685' => 'Bradford', '3686' => 'Bromley', '3687' => 'Bristol, City of', '3688' => 'Bury', '3200' => 'Cambridgeshire', '3689' => 'Caerphilly', '3201' => 'Central', '3690' => 'Ceredigion', '3691' => 'Craigavon', '3203' => 'Cheshire', '3202' => 'Channel Islands', '3692' => 'Carrickfergus', '3693' => 'Cookstown', '3694' => 'Calderdale', '3695' => 'Clackmannanshire', '3696' => 'Coleraine', '3213' => 'Cumbria', '3697' => 'Camden', '3698' => 'Carmarthenshire', '3211' => 'County Londonderry', '3212' => 'County Tyrone', '3205' => 'Cornwall', '3699' => 'Coventry', '3700' => 'Cardiff', '3701' => 'Croydon', '3702' => 'Castlereagh', '3703' => 'Conwy', '3704' => 'Darlington', '3214' => 'Derbyshire', '3705' => 'Denbighshire', '3706' => 'Derby', '3215' => 'Devon', '3707' => 'Dungannon', '3708' => 'Dumfries and Galloway', '3709' => 'Doncaster', '3710' => 'Dundee City', '3216' => 'Dorset', '3208' => 'Down', '3711' => 'Derry', '3712' => 'Dudley', '3217' => 'Dumfries & Galloway', '3209' => 'Durham', '3713' => 'Ealing', '3714' => 'East Ayrshire', '3715' => 'Edinburgh, City of', '3716' => 'East Dunbartonshire', '3653' => 'East Lothian', '3717' => 'Eilean Siar', '3718' => 'Enfield', '3719' => 'East Renfrewshire', '3657' => 'East Riding of Yorkshire', '3720' => 'Essex', '3656' => 'East Sussex', '3721' => 'Falkirk', '3210' => 'Fermanagh', '3722' => 'Fife', '3723' => 'Flintshire', '3724' => 'Gateshead', '3218' => 'Glamorganshire', '3725' => 'Glasgow City', '3219' => 'Gloucestershire', '3220' => 'Grampian', '3726' => 'Greenwich', '3221' => 'Gwynedd', '3727' => 'Halton', '3222' => 'Hampshire', '3728' => 'Havering', '3729' => 'Hackney', '3730' => 'Herefordshire, County of', '3731' => 'Hillingdon', '3225' => 'Highland', '3732' => 'Hammersmith and Fulham', '3733' => 'Hounslow', '3734' => 'Hartlepool', '3224' => 'Hertfordshire', '3735' => 'Harrow', '3736' => 'Haringey', '3226' => 'Humberside', '3227' => 'Huntingdonshire', '3228' => 'Isle of Man', '3737' => 'Isles of Scilly', '3229' => 'Isle of Wight', '3738' => 'Islington', '3739' => 'Inverclyde', '3740' => 'Kensington and Chelsea', '3741' => 'Kent', '3742' => 'Kingston upon Hull, City of', '3743' => 'Kirklees', '3744' => 'Kingston upon Thames', '3745' => 'Knowsley', '3231' => 'Lancashire', '3230' => 'Lanarkshire', '3746' => 'Lambeth', '3747' => 'Leicester', '3748' => 'Leeds', '3232' => 'Leicestershire', '3749' => 'Lewisham', '3233' => 'Lincolnshire', '3750' => 'Liverpool', '3751' => 'Limavady', '3752' => 'London, City of', '3234' => 'Lothian', '3753' => 'Larne', '3754' => 'Lisburn', '3755' => 'Luton', '3756' => 'Manchester', '3757' => 'Middlesbrough', '3758' => 'Medway', '3759' => 'Magherafelt', '3235' => 'Middlesex', '3760' => 'Milton Keynes', '3761' => 'Midlothian', '3762' => 'Monmouthshire', '3763' => 'Merton', '3764' => 'Moray', '3765' => 'Merthyr Tydfil', '3766' => 'Moyle', '3767' => 'North Ayrshire', '3238' => 'Northumberland', '3768' => 'North Down', '3769' => 'North East Lincolnshire', '3770' => 'Newcastle upon Tyne', '3236' => 'Norfolk', '3771' => 'Nottingham', '3652' => 'North Lanarkshire', '3772' => 'North Lincolnshire', '3773' => 'North Somerset', '3774' => 'Newtownabbey', '3237' => 'Northamptonshire', '3775' => 'Neath Port Talbot', '3239' => 'Nottinghamshire', '3776' => 'North Tyneside', '3777' => 'Newham', '3778' => 'Newport', '3658' => 'North Yorkshire', '3779' => 'Newry and Mourne', '3780' => 'Oldham', '3781' => 'Omagh', '3782' => 'Orkney Islands', '3240' => 'Oxfordshire', '3241' => 'Pembrokeshire', '3783' => 'Perth and Kinross', '3784' => 'Plymouth', '3785' => 'Poole', '3786' => 'Portsmouth', '3787' => 'Powys', '3788' => 'Peterborough', '3204' => 'Redcar and Cleveland', '3789' => 'Rochdale', '3790' => 'Rhondda, Cynon, Taff', '3791' => 'Redbridge', '3792' => 'Reading', '3793' => 'Renfrewshire', '3794' => 'Richmond upon Thames', '3795' => 'Rotherham', '3242' => 'Rutland', '3796' => 'Sandwell', '3797' => 'South Ayrshire', '3198' => 'Scottish Borders, The', '3247' => 'Suffolk', '3798' => 'Sefton', '3799' => 'South Gloucestershire', '3800' => 'Sheffield', '3801' => 'St. Helens', '3243' => 'Shropshire', '3802' => 'Stockport', '3803' => 'Salford', '3804' => 'Slough', '3651' => 'South Lanarkshire', '3805' => 'Sunderland', '3806' => 'Solihull', '3244' => 'Somerset', '3807' => 'Southend-on-Sea', '3808' => 'Surrey', '3809' => 'Strabane', '3810' => 'Stoke-on-Trent', '3811' => 'Stirling', '3812' => 'Southampton', '3813' => 'Sutton', '3246' => 'Strathclyde', '3245' => 'Staffordshire', '3814' => 'Stockton-on-Tees', '3815' => 'South Tyneside', '3248' => 'Sussex', '3816' => 'Swansea', '3817' => 'Swindon', '3818' => 'Southwark', '3819' => 'Tameside', '3249' => 'Tayside', '3820' => 'Telford and Wrekin', '3821' => 'Thurrock', '3822' => 'Torbay', '3823' => 'Torfaen', '3824' => 'Trafford', '3825' => 'Tower Hamlets', '3250' => 'Tyne & Wear', '3826' => 'Vale of Glamorgan, The', '3251' => 'Warwickshire', '3197' => 'West Berkshire', '3827' => 'West Dunbartonshire', '3828' => 'Waltham Forest', '3829' => 'Wigan', '3252' => 'Wiltshire', '3830' => 'Wakefield', '3831' => 'Walsall', '3654' => 'West Lothian', '3832' => 'Wolverhampton', '3833' => 'Wandsworth', '3834' => 'Windsor and Maidenhead', '3835' => 'Wokingham', '3223' => 'Worcestershire', '3253' => 'Worcestershire', '3836' => 'Wirral', '3837' => 'Warrington', '3838' => 'Wrexham', '3839' => 'Westminster', '3655' => 'West Sussex', '3659' => 'York', '3254' => 'Yorkshire', '3840' => 'Shetland Islands', ), ),
			'UM' => array ( 'country' => 'UNITED STATES MINOR OUTLYING ISLANDS', 'states' => array ( '3320' => 'Johnston Atoll', '3322' => 'Midway Islands', '3323' => 'Navassa Island', '3325' => 'Wake Island', '3317' => 'Baker Island', '3318' => 'Howland Island', '3319' => 'Jarvis Island', '3321' => 'Kingman Reef', '3324' => 'Palmyra Atoll', ), ),
			'US' => array ( 'country' => 'UNITED STATES OF AMERICA', 'states' => array ( '3260' => 'Armed Forces Americas', '3261' => 'Armed Forces Europe, Middle East, & Canada', '3256' => 'Alaska', '3255' => 'Alabama', '3262' => 'Armed Forces Pacific', '3259' => 'Arkansas', '3257' => 'American Samoa', '3258' => 'Arizona', '3263' => 'California', '3264' => 'Colorado', '3265' => 'Connecticut', '3267' => 'District of Columbia', '3266' => 'Delaware', '3269' => 'Florida', '3268' => 'Federated States of Micronesia', '3270' => 'Georgia', '3271' => 'Guam', '3272' => 'Hawaii', '3276' => 'Iowa', '3273' => 'Idaho', '3274' => 'Illinois', '3275' => 'Indiana', '3277' => 'Kansas', '3278' => 'Kentucky', '3279' => 'Louisiana', '3283' => 'Massachusetts', '3282' => 'Maryland', '3280' => 'Maine', '3281' => 'Marshall Islands', '3284' => 'Michigan', '3285' => 'Minnesota', '3287' => 'Missouri', '3297' => 'Northern Mariana Islands', '3286' => 'Mississippi', '3288' => 'Montana', '3295' => 'North Carolina', '3296' => 'North Dakota', '3289' => 'Nebraska', '3291' => 'New Hampshire', '3292' => 'New Jersey', '3293' => 'New Mexico', '3290' => 'Nevada', '3294' => 'New York', '3298' => 'Ohio', '3299' => 'Oklahoma', '3300' => 'Oregon', '3302' => 'Pennsylvania', '3303' => 'Puerto Rico', '3301' => 'Palau', '3304' => 'Rhode Island', '3305' => 'South Carolina', '3306' => 'South Dakota', '3307' => 'Tennessee', '3308' => 'Texas', '3309' => 'Utah', '3312' => 'Virginia', '3311' => 'Virgin Islands', '3310' => 'Vermont', '3313' => 'Washington', '3315' => 'Wisconsin', '3314' => 'West Virginia', '3316' => 'Wyoming', ), ),
			'UY' => array ( 'country' => 'URUGUAY', 'states' => array ( '3326' => 'Artigsa', '3327' => 'Canelones', '3328' => 'Cerro Largo', '3329' => 'Colonia', '3330' => 'Durazno', '3332' => 'Florida', '3331' => 'Flores', '3333' => 'Lavalleja', '3334' => 'Maldonado', '3335' => 'Montevideo', '3336' => 'Paysandu', '3337' => 'Rio Negro', '3339' => 'Rocha', '3338' => 'Rivera', '3340' => 'Salto', '3341' => 'San Jos', '3342' => 'Soriano', '3343' => 'Tacuarembo', '3344' => 'Treinta y Tres', ), ),
			'UZ' => array ( 'country' => 'UZBEKISTAN', 'states' => array ( '3345' => 'Andijon', '3346' => 'Bukhoro', '3347' => 'Farghona', '3348' => 'Jizzakh', '3349' => 'Khorazm', '3350' => 'Namangan', '3351' => 'Nawoiy', '3352' => 'Qashqadaryo', '3353' => 'Qoraqalpoghiston Respublikasi Karakalpakstan, Respublika', '3354' => 'Samarqand', '3355' => 'Sirdaryo', '3356' => 'Surkhondaryo', '3357' => 'Toshkent', ), ),
			'VU' => array ( 'country' => 'VANUATU', 'states' => array ( '3358' => 'Malampa', '3359' => 'Pnama', '3360' => 'Sanma', '3361' => 'Shfa', '3362' => 'Tafa', '3363' => 'Torba', ), ),
			'VA' => array ( 'country' => 'VATICAN CITY', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'VE' => array ( 'country' => 'VENEZUELA', 'states' => array ( '3374' => 'Distrito Federal', '3365' => 'Anzotegui', '3366' => 'Apure', '3367' => 'Aragua', '3368' => 'Barinas', '3369' => 'Bolvar', '3370' => 'Carabobo', '3371' => 'Cojedes', '3375' => 'Falcn', '3376' => 'Gurico', '3377' => 'Lara', '3380' => 'Mrida', '3378' => 'Miranda', '3379' => 'Monagas', '3381' => 'Nueva Esparta', '3382' => 'Portuguesa', '3383' => 'Sucre', '3385' => 'Tchira', '3384' => 'Trujillo', '3386' => 'Yaracuy', '3387' => 'Zulia', '3373' => 'Dependencias Federales', '3519' => 'Vargas', '3372' => 'Delta Amacuro', '3364' => 'Amazonas', ), ),
			'VN' => array ( 'country' => 'VIETNAM', 'states' => array ( '3420' => 'Lai Chau', '3423' => 'Lao Cai', '3407' => 'Ha Giang', '3401' => 'Cao Bang', '3437' => 'Son La', '3448' => 'Yen Bai', '3445' => 'Tuyen Quang', '3422' => 'Lang Son', '3434' => 'Quang Ninh', '3415' => 'Hoa Binh', '3410' => 'Ha Tay', '3427' => 'Ninh Binh', '3439' => 'Thai Binh', '3441' => 'Thanh Hoa', '3426' => 'Nghe An', '3411' => 'Ha Tinh', '3431' => 'Quang Binh', '3435' => 'Quang Tri', '3442' => 'Thua Thien-Hue', '3432' => 'Quang Nam', '3419' => 'Kon Tum', '3433' => 'Quang Ngai', '3406' => 'Gia Lai', '3395' => 'Binh Dinh', '3430' => 'Phu Yen', '3403' => 'Dac Lac', '3417' => 'Khanh Hoa', '3421' => 'Lam Dong', '3428' => 'Ninh Thuan', '3438' => 'Tay Ninh', '3404' => 'Dong Nai', '3398' => 'Binh Thuan', '3424' => 'Long An', '3389' => 'Ba Ria - Vung Tau', '3388' => 'An Giang', '3405' => 'Dong Thap', '3443' => 'Tien Giang', '3418' => 'Kien Giang', '3400' => 'Can Tho', '3446' => 'Vinh Long', '3394' => 'Ben Tre', '3444' => 'Tra Vinh', '3436' => 'Soc Trang', '3390' => 'Bac Can', '3391' => 'Bac Giang', '3392' => 'Bac Lieu', '3393' => 'Bac Ninh', '3396' => 'Binh Duong', '3397' => 'Binh Phuoc', '3399' => 'Ca Mau', '3402' => 'Da Nang, thanh pho', '3412' => 'Hai Duong', '3413' => 'Hai Phong, thanh pho', '3408' => 'Ha Nam', '3409' => 'Ha Noi, thu do', '3414' => 'Ho Chi Minh, thanh po [Sai Gon]', '3416' => 'Hung Yen', '3425' => 'Nam Dinh', '3429' => 'Phu Tho', '3440' => 'Thai Nguyen', '3447' => 'Vinh Phuc', ), ),
			'VG' => array ( 'country' => 'VIRGIN ISLANDS, BRITISH', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'VI' => array ( 'country' => 'VIRGIN ISLANDS, U.S.', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'WF' => array ( 'country' => 'WALLIS AND FUTUNA ISLANDS', 'states' => array ( '3449' => 'A\'ana', '3450' => 'Aiga-i-le-Tai', '3451' => 'Atua', '3452' => 'Fa\'aaaleleaga', '3453' => 'Gaga\'emauga', '3454' => 'Gagaifomauga', '3455' => 'Palauli', '3456' => 'Satupa\'itea', '3457' => 'Tuamasaga', '3458' => 'Va\'a-o-Fonoti', '3459' => 'Vaisigano', ), ),
			'EH' => array ( 'country' => 'WESTERN SAHARA', 'states' => array ( '3500' => 'Not Applicable', ), ),
			'YE' => array ( 'country' => 'YEMEN', 'states' => array ( '3460' => 'Abyan', '3461' => 'Adan', '3462' => 'Al Bayda\'', '3467' => 'Dhamar', '3468' => 'Hadramawt', '3469' => 'Hajjah', '3470' => 'Ibb', '3464' => 'Al Jawf', '3471' => 'Lahij', '3472' => 'Ma\'rib', '3465' => 'Al Mahrah', '3463' => 'Al Hudaydah', '3466' => 'Al Mahwit', '3473' => 'Sa\'dah', '3475' => 'Shabwah', '3474' => 'San\'a\'', '3476' => 'Ta\'izz', ), ),
			'ZM' => array ( 'country' => 'ZAMBIA', 'states' => array ( '3489' => 'Western', '3481' => 'Central', '3483' => 'Eastern', '3484' => 'Luapula', '3487' => 'Northern', '3486' => 'North-Western', '3488' => 'Southern', '3482' => 'Copperbelt', '3485' => 'Lusaka', ), )
		);
	}

	public static function GetTimezones()
	{
		return array(
				'97' => 'Africa/Abidjan',
				'145' => 'Africa/Accra',
				'129' => 'Africa/Addis_Ababa',
				'119' => 'Africa/Algiers',
				'125' => 'Africa/Asmera',
				'213' => 'Africa/Bamako',
				'94' => 'Africa/Bangui',
				'150' => 'Africa/Banjul',
				'158' => 'Africa/Bissau',
				'226' => 'Africa/Blantyre',
				'95' => 'Africa/Brazzaville',
				'46' => 'Africa/Bujumbura',
				'123' => 'Africa/Cairo',
				'206' => 'Africa/Casablanca',
				'127' => 'Africa/Ceuta',
				'151' => 'Africa/Conakry',
				'300' => 'Africa/Dakar',
				'322' => 'Africa/Dar_es_Salaam',
				'115' => 'Africa/Djibouti',
				'101' => 'Africa/Douala',
				'124' => 'Africa/El_Aaiun',
				'298' => 'Africa/Freetown',
				'66' => 'Africa/Gaborone',
				'369' => 'Africa/Harare',
				'367' => 'Africa/Johannesburg',
				'327' => 'Africa/Kampala',
				'290' => 'Africa/Khartoum',
				'286' => 'Africa/Kigali',
				'92' => 'Africa/Kinshasa',
				'242' => 'Africa/Lagos',
				'139' => 'Africa/Libreville',
				'310' => 'Africa/Lome',
				'9' => 'Africa/Luanda',
				'93' => 'Africa/Lubumbashi',
				'368' => 'Africa/Lusaka',
				'153' => 'Africa/Malabo',
				'237' => 'Africa/Maputo',
				'201' => 'Africa/Maseru',
				'306' => 'Africa/Mbabane',
				'301' => 'Africa/Mogadishu',
				'200' => 'Africa/Monrovia',
				'180' => 'Africa/Nairobi',
				'308' => 'Africa/Ndjamena',
				'240' => 'Africa/Niamey',
				'221' => 'Africa/Nouakchott',
				'43' => 'Africa/Ouagadougou',
				'47' => 'Africa/Porto-Novo',
				'303' => 'Africa/Sao_Tome',
				'214' => 'Africa/Timbuktu',
				'205' => 'Africa/Tripoli',
				'315' => 'Africa/Tunis',
				'238' => 'Africa/Windhoek',
				'350' => 'America/Adak',
				'346' => 'America/Anchorage',
				'5' => 'America/Anguilla',
				'4' => 'America/Antigua',
				'55' => 'America/Araguaina',
				'19' => 'America/Argentina/Buenos_Aires',
				'37' => 'America/Aruba',
				'269' => 'America/Asuncion',
				'40' => 'America/Barbados',
				'52' => 'America/Belem',
				'68' => 'America/Belize',
				'60' => 'America/Boa_Vista',
				'107' => 'America/Bogota',
				'342' => 'America/Boise',
				'81' => 'America/Cambridge_Bay',
				'228' => 'America/Cancun',
				'357' => 'America/Caracas',
				'23' => 'America/Catamarca',
				'144' => 'America/Cayenne',
				'191' => 'America/Cayman',
				'339' => 'America/Chicago',
				'232' => 'America/Chihuahua',
				'21' => 'America/Cordoba',
				'108' => 'America/Costa_Rica',
				'58' => 'America/Cuiaba',
				'8' => 'America/Curacao',
				'90' => 'America/Dawson',
				'87' => 'America/Dawson_Creek',
				'341' => 'America/Denver',
				'332' => 'America/Detroit',
				'117' => 'America/Dominica',
				'84' => 'America/Edmonton',
				'62' => 'America/Eirunepe',
				'304' => 'America/El_Salvador',
				'53' => 'America/Fortaleza',
				'71' => 'America/Glace_Bay',
				'148' => 'America/Godthab',
				'72' => 'America/Goose_Bay',
				'307' => 'America/Grand_Turk',
				'142' => 'America/Grenada',
				'152' => 'America/Guadeloupe',
				'156' => 'America/Guatemala',
				'120' => 'America/Guayaquil',
				'159' => 'America/Guyana',
				'70' => 'America/Halifax',
				'109' => 'America/Havana',
				'233' => 'America/Hermosillo',
				'337' => 'America/Indiana/Knox',
				'336' => 'America/Indiana/Marengo',
				'338' => 'America/Indiana/Vevay',
				'335' => 'America/Indianapolis',
				'86' => 'America/Inuvik',
				'77' => 'America/Iqaluit',
				'177' => 'America/Jamaica',
				'22' => 'America/Jujuy',
				'347' => 'America/Juneau',
				'334' => 'America/Kentucky/Monticello',
				'50' => 'America/La_Paz',
				'253' => 'America/Lima',
				'345' => 'America/Los_Angeles',
				'333' => 'America/Louisville',
				'56' => 'America/Maceio',
				'243' => 'America/Managua',
				'61' => 'America/Manaus',
				'220' => 'America/Martinique',
				'231' => 'America/Mazatlan',
				'24' => 'America/Mendoza',
				'340' => 'America/Menominee',
				'229' => 'America/Merida',
				'227' => 'America/Mexico_City',
				'261' => 'America/Miquelon',
				'230' => 'America/Monterrey',
				'352' => 'America/Montevideo',
				'73' => 'America/Montreal',
				'222' => 'America/Montserrat',
				'64' => 'America/Nassau',
				'331' => 'America/New_York',
				'74' => 'America/Nipigon',
				'349' => 'America/Nome',
				'51' => 'America/Noronha',
				'252' => 'America/Panama',
				'76' => 'America/Pangnirtung',
				'302' => 'America/Paramaribo',
				'344' => 'America/Phoenix',
				'163' => 'America/Port-au-Prince',
				'59' => 'America/Porto_Velho',
				'319' => 'America/Port_of_Spain',
				'263' => 'America/Puerto_Rico',
				'80' => 'America/Rainy_River',
				'78' => 'America/Rankin_Inlet',
				'54' => 'America/Recife',
				'82' => 'America/Regina',
				'63' => 'America/Rio_Branco',
				'20' => 'America/Rosario',
				'99' => 'America/Santiago',
				'118' => 'America/Santo_Domingo',
				'57' => 'America/Sao_Paulo',
				'147' => 'America/Scoresbysund',
				'343' => 'America/Shiprock',
				'69' => 'America/St_Johns',
				'187' => 'America/St_Kitts',
				'197' => 'America/St_Lucia',
				'359' => 'America/St_Thomas',
				'356' => 'America/St_Vincent',
				'83' => 'America/Swift_Current',
				'161' => 'America/Tegucigalpa',
				'149' => 'America/Thule',
				'75' => 'America/Thunder_Bay',
				'234' => 'America/Tijuana',
				'358' => 'America/Tortola',
				'88' => 'America/Vancouver',
				'89' => 'America/Whitehorse',
				'79' => 'America/Winnipeg',
				'348' => 'America/Yakutat',
				'85' => 'America/Yellowknife',
				'15' => 'Antarctica/Casey',
				'14' => 'Antarctica/Davis',
				'17' => 'Antarctica/DumontDUrville',
				'13' => 'Antarctica/Mawson',
				'10' => 'Antarctica/McMurdo',
				'12' => 'Antarctica/Palmer',
				'11' => 'Antarctica/South_Pole',
				'18' => 'Antarctica/Syowa',
				'16' => 'Antarctica/Vostok',
				'295' => 'Arctic/Longyearbyen',
				'364' => 'Asia/Aden',
				'192' => 'Asia/Almaty',
				'178' => 'Asia/Amman',
				'285' => 'Asia/Anadyr',
				'194' => 'Asia/Aqtau',
				'193' => 'Asia/Aqtobe',
				'314' => 'Asia/Ashgabat',
				'173' => 'Asia/Baghdad',
				'45' => 'Asia/Bahrain',
				'38' => 'Asia/Baku',
				'311' => 'Asia/Bangkok',
				'196' => 'Asia/Beirut',
				'181' => 'Asia/Bishkek',
				'49' => 'Asia/Brunei',
				'171' => 'Asia/Calcutta',
				'104' => 'Asia/Chungking',
				'199' => 'Asia/Colombo',
				'305' => 'Asia/Damascus',
				'41' => 'Asia/Dhaka',
				'317' => 'Asia/Dili',
				'2' => 'Asia/Dubai',
				'312' => 'Asia/Dushanbe',
				'264' => 'Asia/Gaza',
				'102' => 'Asia/Harbin',
				'160' => 'Asia/Hong_Kong',
				'217' => 'Asia/Hovd',
				'280' => 'Asia/Irkutsk',
				'165' => 'Asia/Jakarta',
				'168' => 'Asia/Jayapura',
				'170' => 'Asia/Jerusalem',
				'3' => 'Asia/Kabul',
				'284' => 'Asia/Kamchatka',
				'259' => 'Asia/Karachi',
				'106' => 'Asia/Kashgar',
				'246' => 'Asia/Katmandu',
				'279' => 'Asia/Krasnoyarsk',
				'235' => 'Asia/Kuala_Lumpur',
				'236' => 'Asia/Kuching',
				'190' => 'Asia/Kuwait',
				'218' => 'Asia/Macao',
				'283' => 'Asia/Magadan',
				'258' => 'Asia/Manila',
				'251' => 'Asia/Muscat',
				'112' => 'Asia/Nicosia',
				'278' => 'Asia/Novosibirsk',
				'277' => 'Asia/Omsk',
				'182' => 'Asia/Phnom_Penh',
				'166' => 'Asia/Pontianak',
				'188' => 'Asia/Pyongyang',
				'270' => 'Asia/Qatar',
				'215' => 'Asia/Rangoon',
				'287' => 'Asia/Riyadh',
				'360' => 'Asia/Saigon',
				'353' => 'Asia/Samarkand',
				'189' => 'Asia/Seoul',
				'103' => 'Asia/Shanghai',
				'292' => 'Asia/Singapore',
				'321' => 'Asia/Taipei',
				'354' => 'Asia/Tashkent',
				'143' => 'Asia/Tbilisi',
				'174' => 'Asia/Tehran',
				'65' => 'Asia/Thimphu',
				'179' => 'Asia/Tokyo',
				'167' => 'Asia/Ujung_Pandang',
				'216' => 'Asia/Ulaanbaatar',
				'105' => 'Asia/Urumqi',
				'195' => 'Asia/Vientiane',
				'282' => 'Asia/Vladivostok',
				'281' => 'Asia/Yakutsk',
				'276' => 'Asia/Yekaterinburg',
				'7' => 'Asia/Yerevan',
				'267' => 'Atlantic/Azores',
				'48' => 'Atlantic/Bermuda',
				'128' => 'Atlantic/Canary',
				'110' => 'Atlantic/Cape_Verde',
				'137' => 'Atlantic/Faeroe',
				'296' => 'Atlantic/Jan_Mayen',
				'266' => 'Atlantic/Madeira',
				'175' => 'Atlantic/Reykjavik',
				'155' => 'Atlantic/South_Georgia',
				'132' => 'Atlantic/Stanley',
				'293' => 'Atlantic/St_Helena',
				'34' => 'Australia/Adelaide',
				'32' => 'Australia/Brisbane',
				'31' => 'Australia/Broken_Hill',
				'35' => 'Australia/Darwin',
				'28' => 'Australia/Hobart',
				'33' => 'Australia/Lindeman',
				'27' => 'Australia/Lord_Howe',
				'29' => 'Australia/Melbourne',
				'36' => 'Australia/Perth',
				'30' => 'Australia/Sydney',
				'244' => 'Europe/Amsterdam',
				'1' => 'Europe/Andorra',
				'154' => 'Europe/Athens',
				'141' => 'Europe/Belfast',
				'366' => 'Europe/Belgrade',
				'114' => 'Europe/Berlin',
				'297' => 'Europe/Bratislava',
				'42' => 'Europe/Brussels',
				'272' => 'Europe/Bucharest',
				'164' => 'Europe/Budapest',
				'208' => 'Europe/Chisinau',
				'116' => 'Europe/Copenhagen',
				'169' => 'Europe/Dublin',
				'146' => 'Europe/Gibraltar',
				'130' => 'Europe/Helsinki',
				'318' => 'Europe/Istanbul',
				'273' => 'Europe/Kaliningrad',
				'323' => 'Europe/Kiev',
				'265' => 'Europe/Lisbon',
				'294' => 'Europe/Ljubljana',
				'140' => 'Europe/London',
				'203' => 'Europe/Luxembourg',
				'126' => 'Europe/Madrid',
				'223' => 'Europe/Malta',
				'67' => 'Europe/Minsk',
				'207' => 'Europe/Monaco',
				'274' => 'Europe/Moscow',
				'245' => 'Europe/Oslo',
				'138' => 'Europe/Paris',
				'113' => 'Europe/Prague',
				'204' => 'Europe/Riga',
				'176' => 'Europe/Rome',
				'275' => 'Europe/Samara',
				'299' => 'Europe/San_Marino',
				'39' => 'Europe/Sarajevo',
				'326' => 'Europe/Simferopol',
				'212' => 'Europe/Skopje',
				'44' => 'Europe/Sofia',
				'291' => 'Europe/Stockholm',
				'122' => 'Europe/Tallinn',
				'6' => 'Europe/Tirane',
				'324' => 'Europe/Uzhgorod',
				'198' => 'Europe/Vaduz',
				'355' => 'Europe/Vatican',
				'26' => 'Europe/Vienna',
				'202' => 'Europe/Vilnius',
				'260' => 'Europe/Warsaw',
				'162' => 'Europe/Zagreb',
				'325' => 'Europe/Zaporozhye',
				'96' => 'Europe/Zurich',
				'209' => 'Indian/Antananarivo',
				'172' => 'Indian/Chagos',
				'111' => 'Indian/Christmas',
				'91' => 'Indian/Cocos',
				'186' => 'Indian/Comoro',
				'309' => 'Indian/Kerguelen',
				'289' => 'Indian/Mahe',
				'225' => 'Indian/Maldives',
				'224' => 'Indian/Mauritius',
				'365' => 'Indian/Mayotte',
				'271' => 'Indian/Reunion',
				'363' => 'Pacific/Apia',
				'249' => 'Pacific/Auckland',
				'250' => 'Pacific/Chatham',
				'100' => 'Pacific/Easter',
				'361' => 'Pacific/Efate',
				'184' => 'Pacific/Enderbury',
				'313' => 'Pacific/Fakaofo',
				'131' => 'Pacific/Fiji',
				'320' => 'Pacific/Funafuti',
				'121' => 'Pacific/Galapagos',
				'256' => 'Pacific/Gambier',
				'288' => 'Pacific/Guadalcanal',
				'157' => 'Pacific/Guam',
				'351' => 'Pacific/Honolulu',
				'328' => 'Pacific/Johnston',
				'185' => 'Pacific/Kiritimati',
				'136' => 'Pacific/Kosrae',
				'211' => 'Pacific/Kwajalein',
				'210' => 'Pacific/Majuro',
				'255' => 'Pacific/Marquesas',
				'329' => 'Pacific/Midway',
				'247' => 'Pacific/Nauru',
				'248' => 'Pacific/Niue',
				'241' => 'Pacific/Norfolk',
				'239' => 'Pacific/Noumea',
				'25' => 'Pacific/Pago_Pago',
				'268' => 'Pacific/Palau',
				'262' => 'Pacific/Pitcairn',
				'135' => 'Pacific/Ponape',
				'257' => 'Pacific/Port_Moresby',
				'98' => 'Pacific/Rarotonga',
				'219' => 'Pacific/Saipan',
				'254' => 'Pacific/Tahiti',
				'183' => 'Pacific/Tarawa',
				'316' => 'Pacific/Tongatapu',
				'134' => 'Pacific/Truk',
				'330' => 'Pacific/Wake',
				'362' => 'Pacific/Wallis',
				'133' => 'Pacific/Yap',
				'370' => 'UTC'
			);
	}

	public static function GetInterfaceLangs()
	{
		return array(
				'ar' => 'Arabic',
				'zh' => 'Chinese Simplified',
				'zh-tw' => 'Chinese Traditional',
				'cs' => 'Czech',
				'en' => 'English',
				'et' => 'Estonian',
				'fr' => 'French',
				'de' => 'German',
				'he' => 'Hebrew',
				'hu' => 'Hungarian',
				'it' => 'Italian',
				'lv' => 'Latvian',
				'lt' => 'Lithuanian',
				'no' => 'Norwegian',
				'pl' => 'Polish',
				'pt' => 'Portuguese',
				'pt-br' => 'Portuguese Brazilian',
				'ru' => 'Russian',
				'sr' => 'Serbian',
				'sl' => 'Slovenian',
				'es' => 'Spanish',
				'sv' => 'Swedish'
		);
	}
}
?>
