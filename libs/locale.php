<?php 
defined('SIGNUP') or die('Restricted access');

Abstract Class SignupLocale
{
	public static function getText($lang,$layout)
	{
		$text = array();
		$langs = array('en');
		if(!in_array($lang,$langs))
		{
			$langs[] = $lang;
		}
		foreach ($langs as $language)
		{
			$tmp1 = array();
			foreach (array('general',$layout) as $value)
			{
				$locale_file_path = 'locale/'.$language.'/'.$language.'_'.$value.'.ini';
				if (file_exists($locale_file_path))
				{
					$locale_file = file_get_contents($locale_file_path);
					$rows = explode("\n", $locale_file);
					$tmp2 = array();
					foreach($rows as $row)
					{
						if(strrpos($row, ";") !== 0) {
							preg_match('/(.*)="(.*)"/',$row,$matches);
							if (!empty($matches[1]) && !empty($matches[2]))
							{
								$tmp2[$matches[1]] = $matches[2];
							}
						}
					}
					$tmp1 = array_merge($tmp1,$tmp2);
				}
			}
			$text = array_merge($text,$tmp1);
		}
	
	
		return $text;
	}
	
	public static function getLanguages($layout,$template)
	{
		$languages = array();
		$languages = scandir('locale');
		if (!empty($languages))
		{
			foreach ($languages as $key => $item)
			{
				if (!is_dir('locale/'.$item))
				{
					unset($languages[$key]);
				}
				else
				{
					$locale_files = array();
					$locale_files = scandir('locale/'.$item);
					$default_locale_files = self::_getTemplates($layout,$template);
					$default_locale_files[] = 'general';
					foreach($locale_files as $locale_file)
					{
						foreach($default_locale_files as $k => $default_locale_file)
						{
							if($locale_file == $item.'_'.$default_locale_file.'.ini' && is_file('locale/'.$item.'/'.$locale_file))
							{
								unset($default_locale_files[$k]);
								break;
							}
						}
						if (empty($default_locale_files))
						{
							break;
						}
					}
					if (!empty($default_locale_files))
					{
						unset($languages[$key]);
					}
				}
			}
		}
		return $languages;
	}
	
	private static function _getTemplates($layout,$template)
	{
		$mand_templates = array('subscription.php','result.php','wizard.php');
		$templates = scandir('templates/'.$template.'/html/');
		foreach($templates as $key => $file_name)
		{
			if (is_file('templates/'.$template.'/html/'.$file_name) && preg_match('/.+\.php$/',$file_name))
			{
				$templates[$key] = str_replace('.php','',$file_name);
				foreach($mand_templates as $k => $mand_template)
				{
					if ($mand_template == $file_name)
					{
						unset($mand_templates[$k]);
						break;
					}
				}
			}
			else
			{
				unset($templates[$key]);
			}
		}
		
		if (empty($templates) || !in_array($layout,$templates))
		{
			die($layout.' template is missing');
		}
		if(!empty($mand_templates))
		{
			$error_mes = '';
			foreach($mand_templates as $mand_template)
			{
				$error_mes .= $mand_template.' template is missing <br/>';
			}
			die($error_mes);
		}
		return $templates;
	}
}
?>
