<?php
defined('SIGNUP') or die('Restricted access');
include_once('Mail.php');
?>
<script src="<?php echo $path; ?>js/wizard.js"></script>
<?php if(!$authorized): ?>
<div class="col-md-6 col-md-offset-3 to-validate">
	<span class="spacer10"></span>
	<form id="wizard_form" name="login-form" action="<?php echo $root_path.'?task=submit&lang='.$lang.$vars; ?>" method="POST" class="form-horizontal login_form " autocomplete="off" role="form">
		<div class="form-group">
			<label class="col-lg-4 control-label" for="login"><?php echo $text['LOGIN']; ?></label>
			<div class="col-lg-8">
				<input type="text" id="login" data-label="<?php echo $text['LOGIN']; ?>" class="form-control mand" name="login" placeholder="<?php echo $text['LOGIN']; ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="password"><?php echo $text['PASSWORD']; ?></label>
			<div class="col-lg-8">
			<input type="password" data-label="<?php echo $text['PASSWORD']; ?>" id="password" class="form-control mand" name="password" placeholder="<?php echo $text['PASSWORD']; ?>">
			</div>
		</div>
		<input type="hidden" id="act" name="act" value="login" />
		<input type="hidden" name="wizard_token" value="<?php echo $token;?>" />
		<input type="button" class="btn btn-primary" id="submit-button" value="<?php echo $text['LOGIN']; ?>"/>
	</form>
</div>
<?php else: ?>
<script src="<?php echo $path; ?>js/wysihtml5-0.3.0.js"></script>
<script src="<?php echo $path; ?>js/prettify.js"></script>
<script src="<?php echo $path; ?>js/bootstrap-wysihtml5.js"></script>
<link href="<?php echo $path; ?>css/multiple-select.css" rel="stylesheet">
<script>
	var products = '<?php echo $products; ?>',
		subscriptions = '<?php echo $subscriptions; ?>',
		ownerbatch = '<?php echo $ownerbatch; ?>',
		representatives = '{}',
		virtoffice = <?php echo $voffice; ?>,
		adv_view = '<?php echo $adv_view;?>',
		JS_SURE = '<?php echo $text['SURE'];?>',
		JS_PACKAGE = '<?php echo $text['PACKAGE'];?>',
		JS_NO_TEMPLATE = "<?php echo $text['NO_TEMPLATE'];?>",
		JS_CLONE_PACK = '<?php echo $text['CLONE_PACK'];?>',
		JS_SELECT_ALL = '<?php echo $text['JS_SELECT_ALL'];?>',
		JS_ALL_SELECTED = '<?php echo $text['JS_ALL_SELECTED'];?>',
		JS_SELECTED = '<?php echo $text['JS_SELECTED'];?>',
		JS_NO_MATCHES = '<?php echo $text['JS_NO_MATCHES'];?>',
		valid_email_domains = '<?php echo empty($config['valid_email_domains']) ? "" : $config['valid_email_domains']; ?>',
		checked_accounts = {};
		<?php foreach($packages as $package):
			if(!empty($package)):?>
		checked_accounts['<?php echo $package['template_account']['id'];?>'] = {status:'success',currency:'<?php echo $package['template_account']['currency'] ?>'};
		<?php
			endif;
		endforeach;?>
		$(document).ready(function() {
			<?php if ($ownerbatch == '{}'):?>
				ajax_call({act:'GetOwnerBatchList'},'process_response');
			<?php else:?>
				process_response({result:ownerbatch,act:'GetOwnerBatchList'});
			<?php endif;?>

			<?php if ($voffice === "null"):?>
				ajax_call({act:'CheckVirtoffice'},'process_response');
			<?php else:?>
				process_response({"result":virtoffice,act:'CheckVirtoffice'});
			<?php endif;?>


			<?php if ($products == '{}'):?>
				var packs = [];
				<?php if(count($packages) > 1 || !empty($packages[0])):
					foreach($packages as $key => $package):
							?>packs.push({pack:$('#packDiv'+<?php echo $key;?>),currency:'<?php echo $package['template_account']['currency']; ?>'});<?php
					endforeach;
				endif; ?>
				ajax_call({act:'GetProducts'},'process_response',{packs:packs});
			<?php elseif(count($packages) > 1 || !empty($packages[0])): ?>
				var packs = [];
				<?php foreach($packages as $key => $package): ?>packs.push({pack:$('#packDiv'+<?php echo $key;?>),currency:'<?php echo $package['template_account']['currency']; ?>'});<?php endforeach;?>
				process_response({result:products,act:'GetProducts',packs:packs});
			<?php endif;?>

			<?php if ($subscriptions == '{}'):?>
				var packs2 = [];
				<?php if(count($packages) > 1 || !empty($packages[0])):
					foreach($packages as $key => $package):
							?>packs2.push({pack:$('#packDiv'+<?php echo $key;?>),currency:'<?php echo $package['template_account']['currency']; ?>'});<?php
					endforeach;
				endif; ?>
				ajax_call({act:'GetSubscriptions'},'process_response',{packs:packs2});
			<?php elseif(count($packages) > 1 || !empty($packages[0])):?>
				var packs2 = [];
				<?php foreach($packages as $key => $package): ?>packs2.push({pack:$('#packDiv'+<?php echo $key;?>),currency:'<?php echo $package['template_account']['currency']; ?>'});<?php endforeach;?>
				process_response({result:subscriptions,act:'GetSubscriptions',packs:packs2});
			<?php endif;?>

			// Get list of representatives
			ajax_call({act:'GetRepresentatives'},'process_response');
		});
</script>
<div style="margin:25px;" class="make-switch" data-off-label="basic" data-on-label="adv.">
	<input type="checkbox" id="switch-view"<?php if (!empty($adv_view)): echo " checked"; endif; ?> />
</div>
<form id="wizard_form" name="main" action="<?php echo $root_path.'?task=submit&lang='.$lang.$vars; ?>" method="POST" class="form-horizontal" role="form" autocomplete="off">
	<div id="tabs" class="row">
		<ul id = "mylist" class="nav nav-tabs nav-justified">
			<li id="li1" class="active"><a href="#fragment-1"><span><?php echo $text['SERVICE_INFO']; ?></span></a></li>
			<li id="li2"><a href="#fragment-2"><span><?php echo $text['PACKAGES']; ?></span></a></li>
			<li id="li3"><a href="#fragment-3"><span><?php echo $text['RESULT']; ?></span></a>  </li>
			<li id="li4"><a href="#fragment-4"><span><?php echo $text['CUR_CONF']; ?></span></a></li>
			<li id="li5"><a href="#fragment-5"><span><?php echo $text['HELP']; ?></span></a></li>
		</ul>
	</div>
	<span class="spacer10"></span>
	<div id="fragment-1" class="col-md-6 col-md-offset-3 to-validate">
		<div class="form-group" title="<?php echo $text['SERV_URL_DESC']; ?>">
			<label class="col-lg-4 control-label" for="server_url"><?php echo $text['SERVER_URL']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="server_url" class="form-control check letters-digits-dashes-punctuation mand" data-label="<?php echo $text['SERVER_URL']; ?>" name="config[server_url]" value="<?php echo $config['server_url'];?>">
			</div>
		</div>
		<?php if(count($templates) > 1):?>
		<div class="form-group" title="<?php echo $text['TEMPLATE_DESC']; ?>">
			<label class="col-lg-4 control-label" for="server_url"><?php echo $text['TEMPLATE']; ?></label>
			<div class="col-lg-8">
				<select id="template" name="config[template]" class="form-control">
				<?php foreach($templates as $tmpl):?>
					<option value="<?php echo $tmpl;?>"<?php echo ($tmpl === $GLOBALS['config']['template']) ? ' selected' : ''; ?>><?php echo $tmpl;?></option>
				<?php endforeach;?>
				</select>
			</div>
		</div>
		<?php endif; ?>
		<div class="form-group" title="<?php echo $text['DEBUG_MODE_DESC']; ?>">
			<label class="col-lg-4 control-label" for="debug" ><?php echo $text['DEBUG_MODE']; ?></label>
			<div class="col-lg-8 controls text-left">
				<div class="make-switch">
					<input type="checkbox" class="custom_switcher" id="debug"<?php if(!empty($config['debug'])) echo " checked";?> data-target='["debug-hidden"]' />
				</div>
			</div>
		</div>
		<div id="debug-hidden" data-search="debug-val">
			<input type="hidden"<?php if(empty($config['debug'])): echo " disabled"; endif;?> id="debug-val" name="config[debug]" value="on">
		</div>
		<div class="form-group" title="<?php echo $text['CAPTCHA_DESC']; ?>">
			<label class="col-lg-4 control-label" for="captcha" ><?php echo $text['CAPTCHA']?></label>
			<div class="col-lg-8 controls text-left">
				<div class="make-switch">
					<input type="checkbox" class="custom_switcher" id="captcha" <?php if(!empty($config['captcha'])): echo "checked"; endif;?> data-target='["captcha_keys_public","captcha_keys_private"]' />
				</div>
			</div>
		</div>
		<div id="captcha_keys_public" data-show="true" class="form-group<?php if(empty($config['captcha'])): echo " hidden"; endif;?>" title="<?php echo $text['CAPTCHA_PUBLIC_KEY_DESC']; ?>" data-search="captcha_public_key">
			<label class="col-lg-4 control-label" for="captcha_public_key"><?php echo $text['CAPTCHA_PUBLIC_KEY']; ?></label>
			<div class="col-lg-8">
				<input type="text" id="captcha_public_key" <?php if(empty($config['captcha'])): echo " disabled"; endif;?> name="config[captcha][public_key]" class="form-control" value="<?php if(!empty($config['captcha'])): echo $config['captcha']['public_key']; else: echo '6LeuYFMUAAAAAI7su3fHUSbSu_BzURwPO03GuOMZ'; endif; ?>">
			</div>
		</div>
		<div id="captcha_keys_private" data-show="true" class="form-group<?php if(empty($config['captcha'])): echo " hidden"; endif;?>" title="<?php echo $text['CAPTCHA_PRIVATE_KEY_DESC']; ?>" data-search="captcha_private_key">
			<label class="col-lg-4 control-label" for="captcha_public_key"><?php echo $text['CAPTCHA_PRIVATE_KEY']; ?></label>
			<div class="col-lg-8">
				<input type="text" id="captcha_private_key" <?php if(empty($config['captcha'])): echo " disabled"; endif;?> name="config[captcha][private_key]" class="form-control" value="<?php if(!empty($config['captcha'])): echo $config['captcha']['private_key']; else: echo '6LeuYFMUAAAAAIgdkp44rThCQJCT2yohnR9tp69L'; endif; ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="delimiter"><?php echo $text['DELIMITER']; ?></label>
			<div class="col-lg-8 controls text-left">
				<div class="make-switch">
					<input type="checkbox" class="custom_switcher" id="delimiter" <?php if(!empty($config['delimiter'])) echo "checked";?> data-target='["attempts_delimiter","period_delimiter"]'>
				</div>
			</div>
		</div>
		<div id="attempts_delimiter" data-show="true" class="form-group<?php if(empty($config['delimiter'])): echo " hidden"; endif;?>" title="<?php echo $text['DELIMITER_ATT_DESC']; ?>" data-search="delimiter_attempts_val">
			<label class="col-lg-4 control-label" for="delimiter_attempts_val"><?php echo $text['NUM_ATT']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="delimiter_attempts_val"<?php if(empty($config['delimiter'])): echo " disabled"; endif;?> name="config[delimiter][att]" class="form-control" data-label="<?php echo $text['NUM_ATT']; ?>" value="<?php if(!empty($config['delimiter'])): echo $config['delimiter']['att']; else: echo '2'; endif; ?>">
			</div>
		</div>
		<div id="period_delimiter" data-show="true" class="form-group<?php if(empty($config['delimiter'])): echo " hidden"; endif;?>" title="<?php echo $text['DELIMITER_PERIOD_DESC']; ?>" data-search="delimiter_period_val">
			<label class="col-lg-4 control-label" for="delimiter_period_val"><?php echo $text['DELIMITER_PERIOD'];?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="delimiter_period_val"<?php if(empty($config['delimiter'])): echo " disabled"; endif;?> name="config[delimiter][period]" data-label="<?php echo $text['DELIMITER_PERIOD'];?>" class="form-control" value="<?php if(!empty($config['delimiter'])): echo $config['delimiter']['period']; else: echo '24'; endif;?>">
			</div>
		</div>
		<div class="form-group" title="<?php echo $text['EMAIL_CONFIRM_DESC']; ?>">
			<label class="col-lg-4 control-label" for="email_confirm" ><?php echo $text['EMAIL_CONFIRM']; ?></label>
			<div class="col-lg-8 controls text-left">
				<div class="make-switch">
					<input type="checkbox" class="custom_switcher" id="email_confirm"<?php if(!empty($config['email_confirm'])) echo " checked";?> data-target='["email_confirm-hidden"]' />
				</div>
			</div>
		</div>
		<div id="email_confirm-hidden" data-search="email_confirm-val">
			<input type="hidden"<?php if(empty($config['email_confirm'])): echo " disabled"; endif;?> id="email_confirm-val" name="config[email_confirm]" value="on">
		</div>
		<div class="form-group">
			<label title="<?php echo class_exists('Mail')===true ? $text['SMTP_DESC'] : $text['SMTP_DISABLED']?>" class="col-lg-4 control-label" for="smtp"><?php echo $text['SMTP'];?></label>
			<div class="col-lg-8 controls text-left">
				<div class="make-switch">
					<input type="checkbox" class="custom_switcher" id="smtp" <?php if(!empty($config['smtp'])) echo "checked"; if(class_exists('Mail')===false) echo " disabled"?> data-target='["smtp_host", "smtp_port", "smtp_user", "smtp_pwd"]'>
				</div>
			</div>
		</div>
		<div id="smtp_host" data-show="true" class="form-group<?php if(empty($config['smtp'])): echo " hidden"; endif;?>" title="<?php echo $text['SMTP_HOST_DESC']; ?>" data-search="smtp_host_val">
			<label class="col-lg-4 control-label" for="smtp_host_val"><?php echo $text['SMTP_HOST']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="smtp_host_val"<?php if(empty($config['smtp'])): echo " disabled"; endif;?> name="config[smtp][host]" class="form-control mand" data-label="<?php echo $text['SMTP_HOST']; ?>" value="<?php if(!empty($config['smtp'])): echo $config['smtp']['host']; else: echo ''; endif; ?>">
			</div>
		</div>
		<div id="smtp_port" data-show="true" class="form-group<?php if(empty($config['smtp'])): echo " hidden"; endif;?>" title="<?php echo $text['SMTP_PORT_DESC']; ?>" data-search="smtp_port_val">
			<label class="col-lg-4 control-label" for="smtp_port_val"><?php echo $text['SMTP_PORT']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="smtp_port_val"<?php if(empty($config['smtp'])): echo " disabled"; endif;?> name="config[smtp][port]" class="form-control mand" data-label="<?php echo $text['SMTP_PORT']; ?>" value="<?php if(!empty($config['smtp'])): echo $config['smtp']['port']; else: echo ''; endif; ?>">
			</div>
		</div>
		<div id="smtp_user" data-show="true" class="form-group<?php if(empty($config['smtp'])): echo " hidden"; endif;?>" title="<?php echo $text['SMTP_USER_DESC']; ?>" data-search="smtp_user_val">
			<label class="col-lg-4 control-label" for="smtp_user_val"><?php echo $text['SMTP_USER']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="smtp_user_val"<?php if(empty($config['smtp'])): echo " disabled"; endif;?> name="config[smtp][user]" class="form-control mand" data-label="<?php echo $text['SMTP_USER']; ?>" value="<?php if(!empty($config['smtp'])): echo $config['smtp']['user']; else: echo ''; endif; ?>">
			</div>
		</div>
		<div id="smtp_pwd" data-show="true" class="form-group<?php if(empty($config['smtp'])): echo " hidden"; endif;?>" title="<?php echo $text['SMTP_PWD_DESC']; ?>" data-search="smtp_pwd_val">
			<label class="col-lg-4 control-label" for="smtp_pwd_val"><?php echo $text['SMTP_PWD']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="smtp_pwd_val"<?php if(empty($config['smtp'])): echo " disabled"; endif;?> name="config[smtp][pwd]" class="form-control mand" data-label="<?php echo $text['SMTP_PWD']; ?>" value="<?php if(!empty($config['smtp'])): echo $config['smtp']['pwd']; else: echo ''; endif; ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="sms"><?php echo $text['SMS']; ?></label>
			<div class="col-lg-8 controls text-left">
				<div class="make-switch">
					<input type="checkbox" class="custom_switcher" id="sms" <?php if(!empty($config['sms'])) echo "checked";?> data-target='["attempts_sms","provider_sms","password_sms"]'>
				</div>
			</div>
		</div>
		<div id="attempts_sms" data-show="true" class="form-group<?php if(empty($config['sms'])): echo " hidden"; endif;?>" title="<?php echo $text['SMS_ATT_DESC']; ?>" data-search="sms_attempts_val">
			<label class="col-lg-4 control-label" for="sms_attempts_val"><?php echo $text['NUM_ATT']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="sms_attempts_val"<?php if(empty($config['sms'])): echo " disabled"; endif;?> name="config[sms][att]" class="form-control" data-label="<?php echo $text['NUM_ATT']; ?>" value="<?php if(!empty($config['sms'])): echo $config['sms']['att']; else: echo '2'; endif; ?>">
			</div>
		</div>
		<div id="provider_sms" data-show="true" class="form-group<?php if(empty($config['sms'])): echo " hidden"; endif;?>" title="<?php echo $text['SMS_PROVIDER_DESC']; ?>" data-search="sms_provider_val">
			<label class="col-lg-4 control-label" for="sms_provider_val"><?php echo $text['SMS_PROVIDER'];?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select type="text" id="sms_provider_val"<?php if(empty($config['sms'])): echo " disabled"; endif;?> name="config[sms][provider]" data-label="<?php echo $text['SMS_PROVIDER'];?>" class="form-control">
				<option value=""<?php if(empty($config['sms']['provider'])): echo ' selected'; endif;?>><?php echo $text['NOT_SET']?></option>
				<?php foreach($sms_providers as $provider):?>
					<?php echo '<option value="'.$provider.'"'.((!empty($config['sms']['provider']) && $config['sms']['provider'] == $provider)?' selected':'').'>'.$provider.'</option>'; ?>
				<?php endforeach;?>
				</select>
			</div>
		</div>
		<div id="password_sms" data-show="true" class="form-group<?php if(empty($config['sms'])): echo " hidden"; endif;?>" title="<?php echo $text['SMS_PASSWORD_DESC']; ?>" data-search="sms_password_val">
			<label class="col-lg-4 control-label" for="sms_password_val"><?php echo $text['PASSWORD']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="password" id="sms_password_val"<?php if(empty($config['sms'])): echo " disabled"; endif;?> name="config[sms][password]" class="form-control" data-label="<?php echo $text['PASSWORD']; ?>" value="<?php if(!empty($config['sms'])): echo $config['sms']['password']; else: echo ''; endif; ?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label"><?php echo $text['PAYMENT_METHODS'];?></label>
			<div class="col-lg-8 controls text-left">
				<?php foreach ($payment_method_names as $key=>$pm): ?>
				<div class="make-switch">
					<input type="checkbox" name="payment_method[<?php echo $key?>]" value="<?php echo $pm?>"<?php if (in_array($pm,$payment_method)): echo "checked"; endif;?>>
				</div>
				<span><?php echo "$pm"; if ($pm == 'PayPal') echo " **"; ?></span>
				<br>
				<span class="spacer2"></span>
				<?php endforeach; ?>
			</div>
		</div>
		<dl class="dl-horizontal">
			<dt><span class="text-danger">*</span></dt>
			<dd><?php echo $text['MAND_FIELDS'];?></dd>
			<dt>**</dt>
			<dd><?php echo $text['VALID_ONLY']; ?></dd>
		</dl>
		<?php if($log_exists):?>
		<a target="_blank" href="<?php echo $root_path.'?lang='.$lang.$vars.'&download=log'; ?>" class="btn btn-default btn-sm"><?php echo $text['DOWNLOAD_LOG'];?></a>
		<span class="spacer5"></span>
		<span class="spacer5"></span>
		<span class="spacer5"></span>
		<?php endif;?>
		<?php if($config_exists):?>
		<a href="<?php echo $root_path.'?lang='.$lang.$vars.'&remove=auto_config'; ?>" class="btn btn-default btn-sm"><?php echo $text['REMOVE_AUTOCONFIG'];?></a>
		<span class="spacer5"></span>
		<span class="spacer5"></span>
		<span class="spacer5"></span>
		<?php endif;?>
	</div>

	<div id="fragment-2" class="hidden col-md-6 col-md-offset-3 to-validate">
		<?php
		$pack_total = (count($packages) == 1 && empty($packages[0])) ? 0 : count($packages);
		foreach ($packages as $i => $package): ?>
		<div id="packDiv<?php echo $i; ?>" class="package-container">
			<h4><?php echo $text['PACKAGE'];?> <?php echo $i+1; ?>  <span data-package="packDiv<?php echo $i; ?>" class="btn btn-default btn-sm glyphicon glyphicon-trash remove-button<?php if(!$pack_total || $pack_total == 1): echo ' hidden'; endif;?>"></span></h4>
			<span style="margin-bottom:20px" class="show-optional params btn btn-link<?php if(empty($package)): echo ' hidden'; endif;?>"><?php echo $text['SHOW_FIELDS'];?> <span class="glyphicon glyphicon-arrow-down"></span></span>
			<span style="margin-bottom:20px" class="hide-optional params btn btn-link hidden"><?php echo $text['HIDE_FIELDS'];?> <span class="glyphicon glyphicon-arrow-up"></span></span>
			<div class="optional-params<?php if(!empty($package)): echo ' hidden'; endif;?>">
				<div class="form-group" title="<?php echo $text['CREATE_CUS_DESC']; ?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-owner"><?php echo $text['CREATE_CUS']; ?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" data-callback="id_source_additional('packages-<?php echo $i; ?>-')" class="custom_switcher create-customer" id="packages-<?php echo $i; ?>-owner"<?php if (empty($package['subscriber']['i_customer'])): echo " checked"; endif; ?> data-target='["packages-<?php echo $i; ?>-hidden-owner","packages-<?php echo $i; ?>-cus-login-source"]'>
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-hidden-owner" data-search="packages-<?php echo $i; ?>-val-owner">
						<input type="hidden"<?php if(!empty($package['subscriber']['i_customer'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-owner" name="packages[<?php echo $i; ?>][owner]" value="on" />
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-cus-login-source" data-show="true" class="form-group<?php if(empty($package['cus_login_source'])): echo " hidden"; endif;?>" title="<?php echo $text['CUS_LOGIN_SOURCE_DESC'];?>" data-search="packages-<?php echo $i; ?>-cus-login-source-val">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-cus-login-source-val"><?php echo $text['CUS_LOGIN_SOURCE'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<select type="text" id="packages-<?php echo $i; ?>-cus-login-source-val" name="packages[<?php echo $i; ?>][cus_login_source]" class="form-control to-clear" <?php if(isset($package['subscriber']['i_customer'])): echo ' disabled';endif;?>>
							<?php foreach($cus_login_sources as $cus_login_source_val => $cus_login_source_text):?>
								<?php echo '<option value="'.$cus_login_source_val.'"'.((!empty($package['cus_login_source']) && $package['cus_login_source'] == $cus_login_source_val)?' selected':'').'>'.$cus_login_source_text.'</option>'; ?>
							<?php endforeach;?>
						</select>
					</div>
				</div>
				<div class="form-group" title="<?php echo $text['CCF_COPY_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-ccf_copy" ><?php echo $text['CCF_COPY'];?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-ccf_copy" <?php if(isset($package['ccf_copy'])) echo "checked";?> data-target='["packages-<?php echo $i; ?>-hidden-ccf_copy"]'>
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-hidden-ccf_copy" data-search="packages-<?php echo $i; ?>-val-ccf_copy">
						<input type="hidden"<?php if(!isset($package['ccf_copy'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-ccf_copy" name="packages[<?php echo $i; ?>][ccf_copy]" class="form-control" value="on" />
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-virtoffice" class="form-group hidden" data-show="true" title="<?php echo $text['VIRTOFFICE_DESC']; ?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-virtoffice" ><?php echo $text['VIRTOFFICE']; ?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-virtoffice"<?php if(!empty($package['virtoffice'])) echo " checked";?> data-target='["packages-<?php echo $i; ?>-virtoffice-hidden","packages-<?php echo $i; ?>-virtoffice_desc"]' />
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-virtoffice-hidden" data-search="packages-<?php echo $i; ?>-virtoffice-val">
						<input type="hidden"<?php if(!empty($package['subscriber']['i_customer']) || empty($package['virtoffice'])): echo " disabled"; endif;?> id="packages-<?php echo $i; ?>-virtoffice-val" name="packages[<?php echo $i; ?>][virtoffice]" value="on">
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-virtoffice_desc" class="form-group hidden" data-show="true" title="<?php echo $text['VIRTOFFICEDESC_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-virtoffice_desc"><?php echo $text['VIRTOFFICEDESC'];?></label>
					<div class="col-lg-8">
						<input type="text" id="packages-<?php echo $i; ?>-virtoffice_desc" class="form-control check digits dashes to-clear" name="packages[<?php echo $i; ?>][virtoffice_desc]" value="<?php echo (isset($package['virtoffice_desc']))?$package['virtoffice_desc']:""; ?>">
					</div>
				</div>
				<div class="form-group" title="<?php echo $text['PROVISION_SOFTPHONE_DESC']; ?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-qrcode" ><?php echo $text['PROVISION_SOFTPHONE']; ?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-qrcode"<?php if(!empty($package['qrcode'])) echo " checked";?> data-target='["packages-<?php echo $i; ?>-qrcode-hidden"]' />
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-qrcode-hidden" data-search="packages-<?php echo $i; ?>-qrcode-val">
						<input type="hidden"<?php if(empty($package['qrcode'])): echo " disabled"; endif;?> id="packages-<?php echo $i; ?>-qrcode-val" name="packages[<?php echo $i; ?>][qrcode]" value="on">
					</div>
				</div>
				<div class="form-group" title="<?php echo $text['ACCOUNT_ID_SOURCE_SHORT_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-id_source"><?php echo $text['ACCOUNT_ID_SOURCE'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<?php if(empty($package['subscriber']['id_source'])): $package['subscriber']['id_source'] = 'DID'; endif;?>
						<select id="packages-<?php echo $i; ?>-id_source" name="packages[<?php echo $i; ?>][subscriber][id_source]" class="form-control id_source mand to-clear" data-package="packages-<?php echo $i; ?>-">
							<option value="DID"<?php if ($package['subscriber']['id_source'] == 'DID'): echo " selected"; endif;?>><?php echo $text['DID_INV'];?></option>
							<option value="DID_API"<?php if ($package['subscriber']['id_source'] == 'DID_API'): echo " selected"; endif;?>><?php echo 'DID API';?></option>
							<option value="rand"<?php if ($package['subscriber']['id_source'] == 'rand'): echo " selected"; endif;?>><?php echo $text['ID_GEN'];?></option>
							<option value="man"<?php if ($package['subscriber']['id_source'] == 'man'): echo " selected"; endif;?>><?php echo $text['INPUT_FIELD'];?></option>
							<option value="none_field"<?php if ($package['subscriber']['id_source'] == 'none_field'): echo " selected"; endif;?>><?php echo $text['NONE_FIELD'];?></option>
						</select>
					</div>
				</div>
				<div class="form-group<?php if($package['subscriber']['id_source'] != 'DID'): echo ' hidden'; endif;?>" title="<?php echo $text['DIDS_BY_AREA_CODE_DESC'];?>" id="packages-<?php echo $i; ?>-dids-by-area-code">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-did_split_on"><?php echo $text['DIDS_BY_AREA_CODE_DESC'];?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-did_split_on" <?php if(isset($package['did_split_on'])) echo "checked";?> data-target='["packages-<?php echo $i; ?>-hidden-did_split_on"]'>
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-hidden-did_split_on" data-search="packages-<?php echo $i; ?>-val-did_split_on">
						<input type="hidden"<?php if(!isset($package['did_split_on'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-did_split_on" name="packages[<?php echo $i; ?>][did_split_on]" value="on" />
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-did_batch" class="form-group<?php if($package['subscriber']['id_source'] != 'DID'): echo ' hidden'; endif;?>" title="<?php echo $text['DID_OWNER_BATCH_SHORT_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-batch_list"><?php echo $text['DID_OWNER_BATCH'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8 param-container">
						<input type="hidden" class="selected-option" value="<?php echo empty($package['config']['owner_batch'])?'':$package['config']['owner_batch']; ?>" />
						<select id="packages-<?php echo $i; ?>-batch_list"<?php if($package['subscriber']['id_source'] != 'DID'): echo ' disabled'; endif;?> name="packages[<?php echo $i; ?>][config][owner_batch]" data-label="<?php echo $text['DID_OWNER_BATCH'];?>" class="form-control batch-list hidden to-clear">
							<option value=""><?php echo $text['NOT_SET'];?></option>
						</select>
						<span class="loader"><img style="width:15px;" src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-did_api_countries_tr" class="form-group<?php if($package['subscriber']['id_source'] != 'DID_API'): echo ' hidden'; endif;?>" title="<?php echo $text['DID_API_COUNTRIES_SHORT_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-did_api_countries"><?php echo $text['DID_API_COUNTRIES'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<select multiple="multiple" id="packages-<?php echo $i; ?>-did_api_countries"<?php if($package['subscriber']['id_source'] != 'DID_API'): echo ' disabled'; endif;?> name="packages[<?php echo $i; ?>][config][didapi_countries][]" data-label="<?php echo $text['DID_API_COUNTRIES'];?>" class="didapi_countries form-control to-clear">
							<?php
								$package['config']['didapi_countries'] = !empty($package['config']['didapi_countries']) ? $package['config']['didapi_countries'] : array();
								foreach($countries as $id => $value):
							?>
							<?php echo '<option value="'.$id.'"'.(in_array($id,$package['config']['didapi_countries'])?' selected':'').'>'.$value['country'].'</option>'; ?>
							<?php endforeach;?>
						</select>
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-offset" class="form-group<?php if(!$adv_view): echo ' hidden'; endif;?>" title="<?php echo $text['SKIPED_ITEMS_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-config-offset"><?php echo $text['SKIPED_ITEMS'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<input type="text" id="packages-<?php echo $i; ?>-config-offset" class="form-control mand" data-label="<?php echo $text['SKIPED_ITEMS'];?>" name="packages[<?php echo $i; ?>][config][offset]" value="<?php echo !empty($package['config']['offset'])?$package['config']['offset']:'0';?>">
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-limit" class="form-group<?php if(!$adv_view): echo ' hidden'; endif;?>" title="<?php echo $text['MAX_LIST_SIZE_DESC']; ?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-config-limit"><?php echo $text['MAX_LIST_SIZE'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<input type="text" id="packages-<?php echo $i; ?>-config-limit" class="form-control mand" data-label="<?php echo $text['MAX_LIST_SIZE'];?>" name="packages[<?php echo $i; ?>][config][limit]" value="<?php echo !empty($package['config']['limit']) ? $package['config']['limit'] : '50';?>">
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-id_length_tr" class="form-group<?php if($package['subscriber']['id_source'] != 'rand'): echo ' hidden'; endif;?>" title="<?php echo $text['ID_LENGTH_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-id_length"><?php echo $text['ID_LENGTH'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<input type="text"<?php if($package['subscriber']['id_source'] != 'rand'): echo ' disabled'; endif;?> id="packages-<?php echo $i; ?>-id_length" class="form-control to-clear" name="packages[<?php echo $i; ?>][subscriber][id_length]" value="<?php echo !empty($package['subscriber']['id_length']) ? $package['subscriber']['id_length'] : ''; ?>">
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-pref_tr" class="form-group<?php if($package['subscriber']['id_source'] == 'DID'): echo ' hidden'; endif;?>" title="<?php echo $text['PREFIX_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-subscriber-prefix"><?php echo $text['PREFIX'];?></label>
					<div class="col-lg-8">
						<input type="text"<?php if($package['subscriber']['id_source'] == 'DID'): echo ' disabled'; endif;?> id="packages-<?php echo $i; ?>-subscriber-prefix" class="form-control check letters-digits to-clear" name="packages[<?php echo $i; ?>][subscriber][prefix]" value="<?php echo isset($package['subscriber']['prefix']) ? $package['subscriber']['prefix'] : '';?>">
					</div>
				</div>
				<div class="form-group<?php if($package['subscriber']['id_source'] == 'DID'): echo ' hidden'; endif;?>" title="<?php echo $text['SWITCH'];?>" id="packages-<?php echo $i; ?>-alias_prefix">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-alias_prefix_on"><?php echo $text['SAME_PREFIX_FOR_ALIASES'];?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-alias_prefix_on" <?php if(isset($package['alias_prefix_on'])) echo "checked";?> data-target='["packages-<?php echo $i; ?>-hidden-alias_prefix_on"]'>
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-hidden-alias_prefix_on" data-search="packages-<?php echo $i; ?>-val-alias_prefix_on">
						<input type="hidden"<?php if(!isset($package['alias_prefix_on'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-alias_prefix_on" name="packages[<?php echo $i; ?>][alias_prefix_on]" value="on" />
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-subscriber-alias_tr" class="form-group" title="<?php echo $text['ALLOW_ALIASES_DESC']; ?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-subscriber-alias"><?php echo $text['ALLOW_ALIASES']; ?></label>
					<div class="col-lg-8">
						<select id="packages-<?php echo $i; ?>-subscriber-alias" name="packages[<?php echo $i; ?>][subscriber][alias]" class="form-control to-clear">
							<?php $selected = !empty($package['subscriber']['alias']) ? $package['subscriber']['alias'] : 0;
							for($j=0; $j<=4; $j++):
							$val = ($j == 0) ? $text['NOT_SET'] : $j;
							echo '<option value="'.(($j==0)?'':$j).'"'.(($selected == $j)?' selected':'').'>'.$val.'</option>'."/n";
							endfor; ?>
						</select>
					</div>
				</div>
				<?php if(0): ?>
				<div class="form-group" title="<?php echo $text['ENABLE_HOT_NUMS_DESC']; ?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-hot_numbers_on"><?php echo $text['ENABLE_HOT_NUMS']; ?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-hot_numbers_on" <?php if(isset($package['hot_numbers_on'])) echo "checked";?> data-target='["packages-<?php echo $i; ?>-hidden-hot_numbers_on"]'>
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-hidden-hot_numbers_on" data-search="packages-<?php echo $i; ?>-val-hot_numbers_on">
						<input type="hidden"<?php if(!isset($package['hot_numbers_on'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-hot_numbers_on" name="packages[<?php echo $i; ?>][hot_numbers_on]" value="on" />
					</div>
				</div>
				<?php endif; ?>
				<div class="form-group">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-referral"><?php echo $text['REF_LINKS'];?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-referral"<?php if(!empty($package['referral'])): echo " checked"; endif;?> data-target='["packages-<?php echo $i; ?>-ref-key"]' />
						</div>
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-ref-key" class="form-group<?php if(empty($package['referral'])): echo " hidden"; endif;?>" data-show="true" title="<?php echo $text['REF_KEYS_DESC'];?>" data-search="packages-<?php echo $i; ?>-ref-key-val">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-ref-key-val"><?php echo $text['REF_KEYS'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<input type="text"<?php if(empty($package['referral'])): echo " disabled"; endif;?> id="packages-<?php echo $i; ?>-ref-key-val" name="packages[<?php echo $i; ?>][referral]" class="form-control to-clear" data-label="<?php echo $text['REF_KEYS_DESC'];?>" value="<?php if(!empty($package['referral'])): echo $package['referral']; endif;?>">
					</div>
				</div>
				<?php if ($adv_view): ?>
				<div id="packages-<?php echo $i; ?>-did-masking">
					<h4><?php echo $text['DID_INV_MASK'];?></h4>
					<div class="form-group" title="<?php echo $text['PATERN_DIRECT_DESC'];?>">
						<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-did_mask-patern-direct"><?php echo $text['PATERN_DIRECT'];?></label>
						<div class="col-lg-8">
							<input type="text" id="packages-<?php echo $i; ?>-did_mask-patern-direct" class="form-control" name="packages[<?php echo $i; ?>][did_mask][patern][direct]" value="<?php if (isset($package['did_mask']['patern']['direct'])): echo $package['did_mask']['patern']['direct']; endif;?>" placeholder="/^1(.*)/">
						</div>
					</div>
					<div class="form-group" title="<?php echo $text['REPLACE_DIRECT_DESC'];?>">
						<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-did_mask-replace-direct"><?php echo $text['REPLACE_DIRECT'];?></label>
						<div class="col-lg-8">
							<input type="text" id="packages-<?php echo $i; ?>-did_mask-replace-direct" class="form-control" name="packages[<?php echo $i; ?>][did_mask][replace][direct]" value="<?php if (isset($package['did_mask']['replace']['direct'])): echo $package['did_mask']['replace']['direct']; endif; ?>" placeholder="$1">
						</div>
					</div>
					<div class="form-group" title="<?php echo $text['PATERN_BACK_DESC'];?>">
						<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-did_mask-patern-back"><?php echo $text['PATERN_BACK'];?></label>
						<div class="col-lg-8">
							<input type="text" id="packages-<?php echo $i; ?>-did_mask-patern-back" class="form-control" name="packages[<?php echo $i; ?>][did_mask][patern][back]" value="<?php if (isset($package['did_mask']['patern']['back'])): echo $package['did_mask']['patern']['back']; endif;?>" placeholder="/(.*)/">
						</div>
					</div>
					<div class="form-group" title="<?php echo $text['REPLACE_BACK_DESC'];?>">
						<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-did_mask-replace-back"><?php echo $text['REPLACE_BACK_DESC'];?></label>
						<div class="col-lg-8">
							<input type="text" id="packages-<?php echo $i; ?>-did_mask-replace-back" class="form-control" name="packages[<?php echo $i; ?>][did_mask][replace][back]" value="<?php if (isset($package['did_mask']['replace']['back'])): echo $package['did_mask']['replace']['back']; endif;?>" placeholder="1$1">
						</div>
					</div>
				</div>
				<?php endif; ?>
				<div class="form-group">
					<label class="col-lg-4 control-label" for="terms" ><?php echo $text['TERMS_AND_CONDS'];?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-terms"<?php if(!empty($package['terms_text'])): echo " checked"; endif;?> data-target='["packages-<?php echo $i; ?>-terms_tab_2"]'>
						</div>
					</div>
				</div>
				<div class="form-group<?php if(empty($package['terms_text'])): echo " hidden"; endif;?>" id="packages-<?php echo $i; ?>-terms_tab_2" data-show="true" data-search="packages-<?php echo $i; ?>-terms_text">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-terms_text"><?php echo $text['TERMS_AND_CONDS_TEXT'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<textarea id="packages-<?php echo $i; ?>-terms_text"<?php if(empty($package['terms_text'])): echo " disabled"; endif;?> class="form-control to-clear" data-label="<?php echo $text['TERMS_AND_CONDS_TEXT'];?>" name="packages[<?php echo $i; ?>][terms_text]" rows="3"><?php if(!empty($package['terms_text'])): echo $package['terms_text']; endif;?></textarea>
					</div>
				</div>
				<div class="form-group has-feedback" title="<?php echo $text['ACCOUNT_TEMPLATE_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-template_account_id"><?php echo $text['ACCOUNT_TEMPLATE'];?> <span class="text-danger">*</span></label>
					<div class="col-lg-8">
						<input type="hidden" id="packages-<?php echo $i; ?>-template_account_currency" name="packages[<?php echo $i; ?>][template_account][currency]" class="template-currency to-clear" value="<?php echo !empty($package['template_account']['currency'])?$package['template_account']['currency']:'';?>" />
						<input type="text" maxlength="32" id="packages-<?php echo $i; ?>-template_account_id" class="form-control mand accountid-field to-clear" data-label="<?php echo $text['ACCOUNT_TEMPLATE'];?>" name="packages[<?php echo $i; ?>][template_account][id]" value="<?php echo !empty($package['template_account']['id'])?$package['template_account']['id']:'';?>">
						<span style="position:absolute;right:25px;top:6px;color:#3C763D;" class="loader hidden"><img style="width:15px;" src="<?php echo $path; ?>img/loader.gif" /></span>
						<span style="position:absolute;right:25px;top:8px;color:#3C763D;" class="glyphicon glyphicon-ok success-sign form-control-feedback hidden"></span>
						<span style="position:absolute;right:25px;top:8px;color:#A94442;" class="glyphicon glyphicon-remove error-sign form-control-feedback hidden"></span>
					</div>
				</div>
				<div class="form-group" title="<?php echo $text['ACF_COPY_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-acf_copy" ><?php echo $text['ACF_COPY'];?></label>
					<div class="col-lg-8 controls text-left">
						<div class="make-switch">
							<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-acf_copy" <?php if(isset($package['acf_copy'])) echo "checked";?> data-target='["packages-<?php echo $i; ?>-hidden-acf_copy"]'>
						</div>
					</div>
					<div id="packages-<?php echo $i; ?>-hidden-acf_copy" data-search="packages-<?php echo $i; ?>-val-acf_copy">
						<input type="hidden"<?php if(!isset($package['acf_copy'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-acf_copy" name="packages[<?php echo $i; ?>][acf_copy]" class="form-control" value="on" />
					</div>
				</div>
			</div>
			<div class="form-group" title="<?php echo $text['SHORT_DESC_OF_PACK']; ?>">
				<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-description"><?php echo $text['DESC'];?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" id="packages-<?php echo $i; ?>-description" class="form-control mand to-clear" data-label="<?php echo $text['DESC'];?>" name="packages[<?php echo $i; ?>][description][<?php echo $lang; ?>]" value="<?php echo isset($package['description'][$lang]) ? $package['description'][$lang] : ''; ?>">
					<?php if(!empty($package['description'])): ?>
					<?php foreach($package['description'] as $_lang => $_desc): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="packages[<?php echo $i; ?>][description][<?php echo $_lang; ?>]" value="<?php echo $package['description'][$_lang]; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="form-group" title="<?php echo $text['AMOUNT_DESC'];?>">
				<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-amount"><?php echo $text['AMOUNT'];?></label>
				<div class="col-lg-8">
					<input type="text" id="packages-<?php echo $i; ?>-amount" class="form-control check float to-clear" name="packages[<?php echo $i; ?>][amount]" value="<?php echo empty($package['amount'])?'':$package['amount']; ?>">
				</div>
			</div>
			<div class="form-group" title="<?php echo $text['PRODUCT_DESC'];?>">
				<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-i_product"><?php echo $text['PRODUCT'];?> <span class="text-danger">*</span></label>
				<div class="col-lg-8 product param-container">
					<div class="select-container<?php if($pack_total == 0):?> hidden<?php endif;?>">
						<input type="hidden" class="selected-option" value="<?php echo empty($package['i_product'])?'':$package['i_product']; ?>" />
						<select id="packages-<?php echo $i; ?>-i_product" name="packages[<?php echo $i; ?>][i_product]" data-label="<?php echo $text['PRODUCT'];?>" class="form-control mand hidden to-clear">
							<option value="" selected><?php echo $text['NOT_SET']; ?></option>
						</select>
						<span class="loader"><img style="width:15px;" src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
					</div>
					<span class="note<?php if($pack_total > 0):?> hidden<?php endif;?>"><?php echo $text['ENTER_ACCOUNT'];?></span>
				</div>
			</div>
			<div class="form-group" title="<?php echo $text['SUBSCRIPTION_DESC'];?>">
				<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-i_subscription"><?php echo $text['SUBSCRIPTION'];?></label>
				<div class="col-lg-8 subscription param-container">
					<div class="select-container<?php if($pack_total == 0):?> hidden<?php endif;?>">
						<input type="hidden" class="selected-option" value="<?php echo empty($package['i_subscription'])?'':$package['i_subscription']; ?>" />
						<select id="packages-<?php echo $i; ?>-i_subscription" name="packages[<?php echo $i; ?>][i_subscription]" class="form-control hidden to-clear">
							<option value="" selected><?php echo $text['NOT_SET']; ?></option>
						</select>
						<span class="loader"><img style="width:15px;" src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
					</div>
					<span class="note<?php if($pack_total > 0):?> hidden<?php endif;?>"><?php echo $text['ENTER_ACCOUNT'];?></span>
				</div>
			</div>
			<div class="optional-params<?php if(!empty($package)): echo ' hidden'; endif;?>">
				<div id="packages-<?php echo $i; ?>-i_rep" class="form-group hidden" data-show="true" title="<?php echo $text['REPRESENTATIVE_DESC'];?>">
					<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-i_rep-select"><?php echo $text['REPRESENTATIVE'];?></label>
					<div class="col-lg-8 representative param-container">
						<div class="select-container<?php if($pack_total == 0):?> hidden<?php endif;?>">
							<input type="hidden" class="selected-option" value="<?php echo empty($package['subscriber']['i_rep'])?'':$package['subscriber']['i_rep']; ?>" />
							<select id="packages-<?php echo $i; ?>-i_rep-select" name="packages[<?php echo $i; ?>][subscriber][i_rep]" class="form-control hidden to-clear representative-list" data-currency="<?php echo !empty($package['template_account']['currency'])?$package['template_account']['currency']:'';?>">
								<option value="" selected><?php echo $text['NOT_SET']; ?></option>
							</select>
							<span class="loader"><img style="width:15px;" src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
						</div>
						<span class="note<?php if($pack_total > 0):?> hidden<?php endif;?>"><?php echo $text['ENTER_ACCOUNT'];?></span>
					</div>
				</div>
			</div>
			<div class="form-group" title="<?php echo $text['COUPONS_DESC'];?>">
				<label class="col-lg-4 control-label" for="packages-<?php echo $i; ?>-cupon_on" ><?php echo $text['COUPONS'];?></label>
				<div class="col-lg-8 controls text-left">
					<div class="make-switch">
						<input type="checkbox" class="custom_switcher" id="packages-<?php echo $i; ?>-cupon_on" <?php if(isset($package['cupon_on'])) echo "checked";?> data-target='["packages-<?php echo $i; ?>-hidden-cupon_on"]'>
					</div>
				</div>
				<div id="packages-<?php echo $i; ?>-hidden-cupon_on" data-search="packages-<?php echo $i; ?>-val-cupon_on">
					<input type="hidden"<?php if(!isset($package['cupon_on'])): echo ' disabled';endif;?> id="packages-<?php echo $i; ?>-val-cupon_on" name="packages[<?php echo $i; ?>][cupon_on]" class="form-control" value="on" />
				</div>
			</div>
			<span class="spacer5"></span>
		</div>
		<?php endforeach; ?>
		<div class="btn-group" id="add_package_button" style="margin-bottom:20px;">
		  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		    <?php echo $text['ADD_PACK'];?> <span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu" role="menu">
		  <?php foreach ($packages as $i => $package): ?>
		    <li><a href="javascript:void(0);" class="add-package" data-target="packDiv<?php echo $i; ?>"><?php echo $text['CLONE_PACK'].' '.($i+1);?></a></li>
		  <?php endforeach; ?>
		    <li class="divider"></li>
		    <li><a href="javascript:void(0);" class="add-package" data-target="0"><?php echo $text['FROM_SCRATCH'];?></a></li>
		  </ul>
		</div>
		<span class="spacer5"></span>
	</div>

	<div id="fragment-3" class="hidden col-md-6 col-md-offset-3 to-validate">
		<h4><?php echo $text['RESULT']; ?></h4>
		<div class="form-group" title="<?php echo $text['ACCOUNT_SC_LINK_DESC']; ?>">
			<label class="col-lg-4 control-label" for="account_sci"><?php echo $text['ACCOUNT_SC_LINK']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="account_sci" class="form-control mand" data-label="<?php echo $text['ACCOUNT_SC_LINK']; ?>" name="config[references][account_sci]" value="<?php echo $config['references']['account_sci'];?>">
			</div>
		</div>
		<div class="form-group" title="<?php echo $text['CUSTOMER_SC_LINK_DESC']; ?>">
			<label class="col-lg-4 control-label" for="customer_sci"><?php echo $text['CUSTOMER_SC_LINK']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="customer_sci" class="form-control mand" data-label="<?php echo $text['CUSTOMER_SC_LINK']; ?>" name="config[references][customer_sci]" value="<?php echo $config['references']['customer_sci'];?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="mail-phone_link"><?php echo $text['SOFTPHONE_LINK'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-phone_link" class="form-control" name="mail[phone_link]" value="<?php echo !empty($mail['phone_link']) ? $mail['phone_link'] : '';?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="submit-notes"><?php echo $text['AD_NOTES'];?></label>
			<div class="col-lg-8">
				<textarea id="submit-notes" class="form-control" name="config[submit_note]" rows="2"><?php echo $config['submit_note'];?></textarea>
			</div>
		</div>

		<h4><?php echo $text['INFO_EMAIL_CUS']; ?></h4>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="mail-cust_from"><?php echo $text['FROM'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-cust_from" class="form-control check email" name="mail[cust_from]" value="<?php echo !empty($mail['cust_from']) ? $mail['cust_from'] : '';?>">
			</div>
		</div>
		<div class="form-group<?php if (!$adv_view): echo " hidden"; endif;?>">
			<label class="col-lg-4 control-label" for="mail-cust_subj"><?php echo $text['SUBJECT'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-cust_subj" class="form-control mand" name="mail[cust_subj][<?php echo $lang; ?>]" value="<?php echo !empty($mail['cust_subj'][$lang]) ? $mail['cust_subj'][$lang] : 'Subscriber info';?>">
				<?php if(!empty($mail['cust_subj'])): ?>
				<?php foreach($mail['cust_subj'] as $_lang => $_cust_subj): ?>
				<?php if($_lang != $lang): ?>
				<input type="hidden" name="mail[cust_subj][<?php echo $_lang; ?>]" value="<?php echo $_cust_subj; ?>" />
				<?php endif; ?>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<div class="form-group<?php if (!$adv_view): echo " hidden"; endif;
		$cust_message = 'Your account number is: $number;<br/>Account login: $acc_login<br/>Account password:$acc_password<br/>Access page: $acc_interface';
		?>">
			<label class="col-lg-4 control-label" for="mail-cust_message"><?php echo $text['MESSAGE'];?></label>
			<div class="col-lg-8">
				<textarea id="mail-cust_message" class="form-control mand editor" name="mail[cust_message][<?php echo $lang; ?>]" rows="2"><?php echo !empty($mail['cust_message'][$lang]) ? $mail['cust_message'][$lang] : $cust_message;?></textarea>
				<?php if(!empty($mail['cust_message'])): ?>
				<?php foreach($mail['cust_message'] as $_lang => $_cust_mes): ?>
				<?php if($_lang != $lang): ?>
				<input type="hidden" name="mail[cust_message][<?php echo $_lang; ?>]" value="<?php echo $_cust_mes; ?>" />
				<?php endif; ?>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<h4><?php echo $text['NOTIF_NEW_CUS'];?></h4>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="mail-signup_notification_to"><?php echo $text['TO'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-signup_notification_to" class="form-control check email" name="mail[signup_notification_to]" value="<?php echo !empty($mail['signup_notification_to']) ? $mail['signup_notification_to'] : '';?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="mail-signup_notification_from"><?php echo $text['FROM'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-signup_notification_from" class="form-control check email" name="mail[signup_notification_from]" value="<?php echo !empty($mail['signup_notification_from']) ? $mail['signup_notification_from'] : '';?>">
			</div>
		</div>
		<div class="form-group<?php if (!$adv_view): echo " hidden"; endif;?>">
			<label class="col-lg-4 control-label" for="mail-signup_notification_subj"><?php echo $text['SUBJECT']; ?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-signup_notification_subj" class="form-control mand" name="mail[signup_notification_subj]" value="<?php echo !empty($mail['signup_notification_subj']) ? $mail['signup_notification_subj'] : 'New customer subscribed';?>">
			</div>
		</div>
		<div class="form-group<?php if (!$adv_view): echo " hidden"; endif;
		$signup_notification_message = 'New customer subscribed. Customer name: $cust_name Account ID: $number';
		?>">
			<label class="col-lg-4 control-label" for="mail-signup_notification_message"><?php echo $text['MESSAGE']; ?></label>
			<div class="col-lg-8">
				<textarea id="mail-signup_notification_message" class="form-control mand editor" name="mail[signup_notification_message]" rows="2"><?php echo !empty($mail['signup_notification_message']) ? $mail['signup_notification_message'] : $signup_notification_message;?></textarea>
			</div>
		</div>

		<div<?php if (!$adv_view): echo " class='hidden'"; endif; ?>>
			<h4><?php echo $text['SMS_TEXT'];?></h4>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-sms_validation_message"><?php echo $text['MESSAGE']; ?></label>
				<div class="col-lg-8">
					<textarea id="mail-sms_validation_message" class="form-control mand" name="mail[sms_validation_message]" rows="2"><?php echo !empty($mail['sms_validation_message']) ? $mail['sms_validation_message'] : 'Verification code:$code (session:$session)';?></textarea>
				</div>
			</div>

			<h4><?php echo $text['PAYPAL_PENDING_TEXT'];?></h4>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-paypal_pending_message"><?php echo $text['SUBJECT']; ?></label>
				<div class="col-lg-8">
					<input type="text" id="mail-paypal_pending_message" class="form-control mand" name="mail[paypal_pending_subject][<?php echo $lang; ?>]" value="<?php echo !empty($mail['paypal_pending_subject'][$lang]) ? $mail['paypal_pending_subject'][$lang] : "PayPal payment status check";?>" />
					<?php if(!empty($mail['paypal_pending_subject'])): ?>
					<?php foreach($mail['paypal_pending_subject'] as $_lang => $_confirm_subj): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="mail[paypal_pending_subject][<?php echo $_lang; ?>]" value="<?php echo $_confirm_subj; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-paypal_pending_message"><?php echo $text['MESSAGE']; ?></label>
				<div class="col-lg-8">
					<textarea id="mail-paypal_pending_message" class="form-control mand editor" name="mail[paypal_pending_message][<?php echo $lang; ?>]" rows="2"><?php echo !empty($mail['paypal_pending_message'][$lang]) ? $mail['paypal_pending_message'][$lang] : 'Please, follow the link $link once PayPal transaction is complete (sometimes it takes a while to complete the transaction).';?></textarea>
					<?php if(!empty($mail['paypal_pending_message'])): ?>
					<?php foreach($mail['paypal_pending_message'] as $_lang => $_confirm_mes): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="mail[paypal_pending_message][<?php echo $_lang; ?>]" value="<?php echo $_confirm_mes; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<h4><?php echo $text['STRIPE_PENDING_TEXT'];?></h4>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-stripe_pending_message"><?php echo $text['SUBJECT']; ?></label>
				<div class="col-lg-8">
					<input type="text" id="mail-stripe_pending_message" class="form-control mand" name="mail[stripe_pending_subject][<?php echo $lang; ?>]" value="<?php echo !empty($mail['stripe_pending_subject'][$lang]) ? $mail['stripe_pending_subject'][$lang] : "Complete Authentication";?>" />
					<?php if(!empty($mail['stripe_pending_subject'])): ?>
					<?php foreach($mail['stripe_pending_subject'] as $_lang => $_confirm_subj): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="mail[stripe_pending_subject][<?php echo $_lang; ?>]" value="<?php echo $_confirm_subj; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-stripe_pending_message"><?php echo $text['MESSAGE']; ?></label>
				<div class="col-lg-8">
					<textarea id="mail-stripe_pending_message" class="form-control mand editor" name="mail[stripe_pending_message][<?php echo $lang; ?>]" rows="2"><?php echo !empty($mail['stripe_pending_message'][$lang]) ? $mail['stripe_pending_message'][$lang] : 'Please go to the authentication page $stripe_3ds_url to complete the payment. Once done, please follow the link $link to finish signing up.';?></textarea>
					<?php if(!empty($mail['stripe_pending_message'])): ?>
					<?php foreach($mail['stripe_pending_message'] as $_lang => $_confirm_mes): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="mail[stripe_pending_message][<?php echo $_lang; ?>]" value="<?php echo $_confirm_mes; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<h4><?php echo $text['EMAIL_CONFIRM_TEXT'];?></h4>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-confirm_notification_subject"><?php echo $text['SUBJECT']; ?></label>
				<div class="col-lg-8">
					<input type="text" id="mail-confirm_notification_subject" class="form-control mand" name="mail[email_confirm_subject][<?php echo $lang; ?>]" value="<?php echo !empty($mail['email_confirm_subject'][$lang]) ? $mail['email_confirm_subject'][$lang] : 'Signup confirmation';?>" />
					<?php if(!empty($mail['email_confirm_subject'])): ?>
					<?php foreach($mail['email_confirm_subject'] as $_lang => $_confirm_subj): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="mail[email_confirm_subject][<?php echo $_lang; ?>]" value="<?php echo $_confirm_subj; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="mail-confirm_notification_message"><?php echo $text['MESSAGE']; ?></label>
				<div class="col-lg-8">
					<textarea id="mail-confirm_notification_message" class="form-control mand editor" name="mail[email_confirm_message][<?php echo $lang; ?>]" rows="2"><?php echo !empty($mail['email_confirm_message'][$lang]) ? $mail['email_confirm_message'][$lang] : 'Please, follow the link $link to complete.';?></textarea>
					<?php if(!empty($mail['email_confirm_message'])): ?>
					<?php foreach($mail['email_confirm_message'] as $_lang => $_confirm_mes): ?>
					<?php if($_lang != $lang): ?>
					<input type="hidden" name="mail[email_confirm_message][<?php echo $_lang; ?>]" value="<?php echo $_confirm_mes; ?>" />
					<?php endif; ?>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<h4><?php echo $text['NOTIF_ERRORS'];?></h4>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="mail-error_notification_to"><?php echo $text['TO'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-error_notification_to" class="form-control check email" name="mail[error_notification_to]" value="<?php echo !empty($mail['error_notification_to']) ? $mail['error_notification_to'] : '';?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="mail-error_notification_from"><?php echo $text['FROM'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-error_notification_from" class="form-control check email" name="mail[error_notification_from]" value="<?php echo !empty($mail['error_notification_from']) ? $mail['error_notification_from'] : '';?>">
			</div>
		</div>
		<div class="form-group<?php if (!$adv_view): echo " hidden"; endif;?>">
			<label class="col-lg-4 control-label" for="mail-error_notification_subj"><?php echo $text['SUBJECT'];?></label>
			<div class="col-lg-8">
				<input type="text" id="mail-error_notification_subj" class="form-control mand" name="mail[error_notification_subj]" value="<?php echo !empty($mail['error_notification_subj']) ? $mail['error_notification_subj'] : 'an error occured during signup';?>">
			</div>
		</div>
		<div class="form-group<?php if (!$adv_view): echo " hidden"; endif;?>">
			<label class="col-lg-4 control-label" for="mail-error_notification_message"><?php echo $text['MESSAGE'];?></label>
			<div class="col-lg-8">
				<textarea id="mail-error_notification_message" class="form-control mand editor" name="mail[error_notification_message]" rows="2"><?php echo !empty($mail['error_notification_message']) ? $mail['error_notification_message'] : '$e';?></textarea>
			</div>
		</div>
	</div>

	<div id="fragment-4" class="hidden col-md-8 col-md-offset-2 text-left">
		<pre>
		<?php
		if (file_exists('auto_config.php')):
			$fp = fopen('auto_config.php','r');
			$content = fread($fp, filesize('auto_config.php'));
			fclose($fp);

			$a = strpos( $content, "'login'");
			$log_beg = strpos( $content, "'",$a+7);
			$log_end = strpos( $content, "'",$log_beg+2);
			$content= substr_replace($content,"'************'",$log_beg,$log_end-$log_beg+1);

			$b = strpos( $content, "'password'");
			$pas_beg = strpos( $content, "'",$b+11);
			$pas_end = strpos( $content, "'",$pas_beg+2);
			$content= substr_replace($content,"'************'",$pas_beg,$pas_end-$pas_beg+1);

			$content = str_replace('<', '&lt;', $content);
			$content = str_replace('>', '&gt;', $content);

			echo $content;
		endif;
		?>
		</pre>
	</div>

	<div id="fragment-5" class="hidden col-md-8 col-md-offset-2">
		<p><?php echo $text['WIZARD_DESIGN'];?></p>
		<p><?php echo $text['PERMISSION_FOR_SCRIPT'];?></p>
		<table class="table">
			<thead>
				<tr>
					<th><?php echo $text['PARAMETER'];?></th>
					<th><?php echo $text['DESC'];?></th>
				</tr>
			</thead>
			<tbody class="text-left">
				<tr>
					<td align="center" colspan="2"><i><?php echo $text['SERVICE_INFO'];?></i></td>
				</tr>
				<tr>
					<td><?php echo $text['SERVER_URL'];?></td>
					<td><?php echo $text['PB_NAME'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['DEBUG_MODE'];?></td>
					<td><?php echo $text['DEBUG_MODE_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['CAPTCHA'];?></td>
					<td><?php echo $text['CAPTCHA_FULL_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['DELIMITER'];?></td>
					<td><?php echo $text['DELIMITER_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['PAYMENT_METHODS'];?></td>
					<td>
						<p><?php echo $text['PAY_METHODS_DESC'];?></p>
						<p><?php echo $text['PAY_METHODS_NOTE'];?></p>
					</td>
				</tr>
				<tr>
					<td align="center" colspan="2"><i><?php echo $text['ACCOUNT_OPTIONS'];?></i></td>
				</tr>
				<tr>
					<td><?php echo $text['ACCOUNT_TEMPLATE'];?></td>
					<td><?php echo $text['ACCOUNT_TEMPLATE_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['CREATE_CUS'];?></td>
					<td><?php echo $text['CREATE_CUS_FULL_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['CUS_LOGIN_SOURCE'];?></td>
					<td><?php echo $text['CUS_LOGIN_SOURCE_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['ACCOUNT_ID_SOURCE'];?></td>
					<td><?php echo $text['ACCOUNT_ID_SOURCE_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['DID_OWNER_BATCH'];?></td>
					<td><?php echo $text['DID_OWNER_BATCH_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['SKIPED_ITEMS'];?></td>
					<td><?php echo $text['SKIPED_ITEMS_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['MAX_LIST_SIZE']; ?></td>
					<td><?php echo $text['MAX_LIST_SIZE_FULL_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['ID_LENGTH'];?></td>
					<td><?php echo $text['ID_LENGTH_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['PREFIX'];?></td>
					<td><?php echo $text['PREFIX_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['ALLOW_ALIASES'];?></td>
					<td><?php echo $text['ALLOW_ALIASES_DESC'];?></td>
				</tr>
				<?php if(0):?>
				<tr>
					<td><?php echo $text['ENABLE_HOT_NUMS'];?></td>
					<td><?php echo $text['ENABLE_HOT_NUMS_FULL_DESC'];?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td><?php echo $text['REFERRAL_LINKS_FUNC'];?></td>
					<td><?php echo $text['REFERRAL_LINKS_FUNC_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['PROMO_CODES_FUNC'];?></td>
					<td><?php echo $text['PROMO_CODES_FUNC_DESC'];?></td>
				</tr>
				<tr>
					<td align="center" colspan="2"><i><?php echo $text['PACKAGES'];?></i></td>
				</tr>
				<tr>
					<td><?php echo $text['DESC'];?>:</td>
					<td><?php echo $text['SHORT_DESC_OF_PACK'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['AMOUNT'];?>:</td>
					<td><?php echo $text['AMOUNT_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['PRODUCT'];?>:</td>
					<td><?php echo $text['PRODUCT_FULL_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['SUBSCRIPTION'];?>:</td>
					<td><?php echo $text['SUBSCRIPTION_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['REPRESENTATIVE'];?>:</td>
					<td><?php echo $text['REPRESENTATIVE_DESC'];?></td>
				</tr>

				<tr>
					<td align="center" colspan="2"><i>Result Info</i></td>
				</tr>
				<tr>
					<td><?php echo $text['RESULT'];?></td>
					<td><?php echo $text['RESULT_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['INFO_EMAIL_CUS'];?></td>
					<td><?php echo $text['INFO_EMAIL_CUS_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['NOTIF_NEW_CUS']?></td>
					<td><?php echo $text['NOTIF_NEW_CUS_DESC'];?></td>
				</tr>
				<tr>
					<td><?php echo $text['NOTIF_ERRORS'];?></td>
					<td><?php echo $text['NOTIF_ERRORS_DESC'];?></td>
				</tr>
				<tr>
					<td align="center" colspan="2"><i><?php echo $text['CUR_CONF'];?></i></td>
				</tr>
				<tr>
					<td colspan="2"><?php echo $text['CUR_CONF_DESC'];?></td>
				</tr>
			</tbody>
		</table>
	</div>
		<br/>
		<div class="row col-md-6 col-md-offset-3">
			<input type="hidden" id="wizard_token" name="wizard_token" value="<?php echo $token;?>" />
			<input type="hidden" name="act" id="act" value="save" />
			<input type="button" class="btn btn-primary" id="submit-button" value="Save">
			<input type="button" id="logout" class="btn btn-primary" value="Logout">
		</div>
</form>
<script src="<?php echo $path; ?>js/jquery.multiple.select.js"></script>
<?php endif; ?>
