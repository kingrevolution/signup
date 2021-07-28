<?php
defined('SIGNUP') or die('Restricted access');

require_once 'libs/model.php';

class SignupController {
	private $_model;

	public function __construct()
	{
		$this->_model = new SignupModel();
	}

	public function invoke()
	{
		$data = call_user_func(array($this,'_'.$this->_model->task));
		foreach (array('error','notice','warning','success','token') as $val)
		{
			${$val} = empty($data[$val]) ? '' : $data[$val];
			if(isset($_SESSION[$val]))
			{
				unset($_SESSION[$val]);
			}
		}
		foreach ($data['output'] as $key => $value)
		{
			${$key} = $value;
		}
		$langs =  $this->_model->languages;
		$template = $this->_model->template;
		$path = $GLOBALS['root_path'].'templates/'.$template.'/';
		global $lang,$root_path,$text,$layout,$vars;
		require_once 'templates/'.$template.'/index.php';
	}
	
	private function _load()
	{
		return $this->_model->getOutput();
	}
	
	private function _submit()
	{
		$this->_model->submitData();
		exit;
	}
	
	private function _ajax()
	{
		echo $this->_model->processAjax();		
		exit;
	}
}
?>
