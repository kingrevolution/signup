<?php
defined('SIGNUP') or die('Restricted access');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
	<head>
		<title><?php echo $text[strtoupper($layout)]; ?></title>
	
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="utf-8">
	
		<link href="<?php echo $path; ?>css/bootstrap.min.css" rel="stylesheet" media="screen">
		<link href="<?php echo $path; ?>css/bootstrap-switch.css" rel="stylesheet" media="screen">
		<link href="<?php echo $path; ?>css/bootstrap-additions.css" rel="stylesheet">
		<link href="<?php echo $path; ?>css/theme.css" rel="stylesheet" media="screen">
	
		<script src="<?php echo $path; ?>js/jquery-1.11.0.min.js"></script>
		<script src="<?php echo $path; ?>js/bootstrap.min.js"></script>
		<script src="<?php echo $path; ?>js/bootstrap-switch.min.js"></script>
		<script>
			var JS_MAND_CHECKBOX = '<?php echo $text['JS_MAND_CHECKBOX']; ?>',
				JS_ENTER_VALID_EMAIL = '<?php echo $text['JS_ENTER_VALID_EMAIL']; ?>',
				JS_ENTER_VALID_CC_DATE = '<?php echo $text['JS_ENTER_VALID_CC_DATE']; ?>',
				JS_SELECT_ITEM = '<?php echo $text['JS_SELECT_ITEM']; ?>',
				JS_INVALID_CHARACTER = '<?php echo $text['JS_INVALID_CHARACTER']; ?>',
				JS_MAND_FIELD = '<?php echo $text['JS_MAND_FIELD']; ?>',
				JS_NOT_SET = '<?php echo $text['NOT_SET']; ?>',
				lang = '<?php echo $lang; ?>',
				root_path = '<?php echo $root_path; ?>';
		</script>
		<style>
			#confirm_title {
				font-size: 36px;
				padding: 20px;
				max-width: 75%;
				margin: 0 auto;
			}
			
			.container .jumbotron {
				padding: 20px;
				max-width: 75%;
				margin: 0 auto;
			}
		</style>
	</head>
	<body>
		<div id="preloader" class="container text-center hidden">
			<h3><?php echo $text['WAIT']; ?></h3>
			<div id="status" class="progress progress-striped active hidden">
				<div class="progress-bar" role="progressbar" id="status-bar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
					<span id="status-bar-val" ></span>
				</div>
			</div>
			<div id="progress" class="progress progress-striped active hidden">
				<div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
					<span id="status-bar-val" ></span>
				</div>
			</div>
		</div>
		<div id="main" class="container text-center">
			<div id="error-container" class="alert alert-dismissable alert-danger<?php if((!$error)): ?> hidden<?php endif; ?>">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<span id="error-field"><strong><?php echo $text['ERROR']; ?>:</strong> <?php echo $error; ?></span>
			</div>
			<div class="alert alert-dismissable alert-warning<?php if (!$warning):?> hidden<?php endif;?>">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<strong><?php echo $text['WARNING']; ?>:</strong> <?php echo $warning; ?>
			</div>
			<div class="alert alert-dismissable alert-success<?php if (!$success):?> hidden<?php endif;?>">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<strong><?php echo $text['CONGRATULATIONS']; ?>:</strong> <?php echo $success; ?>
			</div>
			<div class="alert alert-dismissable alert-info<?php if (!$notice):?> hidden<?php endif;?>">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<strong><?php echo $text['NOTE']; ?>:</strong> <?php echo $notice; ?>
			</div>
			<span class="spacer10"></span>
			<h1><?php echo $text[strtoupper($layout)]; ?></h1>
			<div class="languages">
				<ul class="language-bar">
				<?php foreach($langs as $language): ?>
					<li<?php if ($language == $lang):?> class="active"<?php endif;?>>
						<a <?php if ($language == $lang):?>href="#" onclick="return false;"<?php else: ?>href="<?php echo $root_path.'?lang='.$language.$vars; ?>"<?php endif; ?>>
							<img src="<?php echo $path.'img/flags/'.$language.'.gif';?>"/>
							<?php echo $language;?>
						</a>
					</li>
				<?php endforeach; ?>
				</ul>
			</div>
			<?php require_once 'templates/'.$template.'/html/'.($layout).'.php';?>
		</div>
		<div class="spacer5"></div>
		<script src="<?php echo $path; ?>js/validator.js"></script>
	</body>
</html>
