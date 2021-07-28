<?php
// $Revision: 12621 $; $Date: 2017-04-19 16:21:27 +0000 (Wed, 19 Apr 2017) $
define('SIGNUP',TRUE);

date_default_timezone_set('UTC');
ini_set('max_execution_time', 0);
session_name("SignupSession");
session_start();

$errors = array();
$extensions = @get_loaded_extensions();

if (!in_array("soap", $extensions))
{
	array_push($errors, "PHP does not have the SOAP extension enabled. Please contact your web hosting provider.");
}
if (!in_array("json", $extensions))
{
	array_push($errors, "PHP does not have the Json extension enabled. Please contact your web hosting provider.");
}
if (!in_array("gd", $extensions))
{
	array_push($errors, "PHP does not have the GD extension enabled. Please contact your web hosting provider.");
}
if (!in_array("session", $extensions))
{
	array_push($errors, "Session support is disabled in PHP. Please contact your web hosting provider.");
}
if (!in_array("openssl", $extensions))
{
	array_push($errors, "PHP does not have the OpenSSL extension enabled. Please contact your web hosting provider.");
}
if (ini_get("safe_mode"))
{
	array_push($errors, "The PHP safe mode feature must be disabled. Please contact your web hosting provider.");
}
$dir = getcwd();
$pattern = (strpos($dir,'\\') === FALSE) ? '/.+\/$/' : '/.+\\$/';
$dir = $dir.((preg_match($pattern,$dir)) ? '' : ((strpos($dir,'\\') === FALSE) ? '/' : '\\'));
$filename = $dir."write_check";
if (!$fileHandler = fopen($filename, "a"))
{
	array_push($errors, "Can't write to the directory " . $dir);
}
else
{
	fclose($fileHandler);
	unlink($filename);
}
if (count($errors) > 0)
{
	foreach ($errors as $error)
	{
		echo $error."<br/>";
	}
	exit;
}

require_once 'libs/controller.php';
$controller = new SignupController();
$controller->invoke();
?>
