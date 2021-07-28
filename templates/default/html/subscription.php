<?php
defined('SIGNUP') or die('Restricted access');
?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="<?php echo $path; ?>js/subscription.js"></script>
<script type="text/javascript">
	var JS_INCORRECT_CAPTCHA = "<?php echo $text['JS_INCORRECT_CAPTCHA']; ?>",
		JS_NO_NUMBERS = "<?php echo $text['JS_NO_NUMBERS']; ?>",
		JS_ACCOUNT_INFO = "<?php echo $text['ACCOUNT_INFO']; ?>",
		JS_PAYMENT_INFO = "<?php echo $text['PAYMENT_INFO']; ?>",
		JS_ACCOUNT_OPTIONS = "<?php echo $text['ACCOUNT_OPTIONS']; ?>",
		JS_MISC = "<?php echo $text['MISC']; ?>",
		JS_SELECT_NUM = "<?php echo $text['SELECT_NUMBER']; ?>",
		JS_SELECT_AREA_CODE = "<?php echo $text['SELECT_AREA_CODE']; ?>",
		JS_EXISTS = "<?php echo $text['JS_EXISTS']; ?>",
		JS_THIS = "<?php echo $text['JS_THIS']; ?>",
		JS_SMS_SESSION = "<?php echo $text['JS_SMS_SESSION']; ?>",
		JS_INVALID_DATA = "<?php echo $text['JS_INVALID_DATA']; ?>",
		JS_SMS_ATTEMPTS = "<?php echo $text['JS_SMS_ATTEMPTS']; ?>",
		JS_WARNING = "<?php echo $text['WARNING']; ?>",
		progress_bar_type = "<?php echo $progress_bar; ?>",
		valid_email_domains = "<?php echo $config['valid_email_domains']; ?>";

	$('document').ready(function() {
		var form = document.forms['websubscr'];
		form.reset();
		<?php if (in_array($set_package['subscriber']['id_source'],array('DID','DID_API'))):
			$id_source = $set_package['subscriber']['id_source'];
		?>ajax_call({act:'<?php echo (($id_source == 'DID')?'GetDidPatterns':'DidApi\',target:\'countries');?>',},'<?php echo (($id_source == 'DID')?'get_did_patterns':'did_api');?>');
		<?php if($set_package['subscriber']['id_source'] == 'DID_API'):?>$('#finish-button').prop('disabled',true);<?php endif;?>
		<?php endif; ?>
	});
</script>
<form name="websubscr" action="<?php echo $root_path.'?task=submit&lang='.$lang.$vars; ?>" method="POST" class="form-horizontal" autocomplete="off" role="form">
	<div class="row col-md-8 col-md-offset-2">
		<span class="spacer5"></span>
		<ul id="navbar" class="nav nav-tabs nav-justified"></ul>
		<div class="hidden-xs progress progress-striped">
			<div id="progress-bar" class="progress-bar"></div>
		</div>
		<span class="spacer10"></span>
	</div>

	<!-- Package info & Address info -->
	<div class="row to-validate" id="address-info">
		<div class="col-md-6 col-md-offset-3">
			<?php if (count($packages) > 1):?>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="package"><?php echo $text['PACKAGE']?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<select id="package" data-label="<?php echo $text['PACKAGE']?>" name="package" class="form-control mand">
						<?php
						foreach ($packages as $key => $package):
							echo '<option value="'.$key.'"'.(($key==$pack)?' selected':'').'>'.(isset($package['description'][$lang]) ? $package['description'][$lang] : (isset($package['description']["en"]) ? $package['description']["en"] : reset($package['description']))).' ('.(!empty($package['amount'])?$package['amount'].' ':'0 ').$package['template_account']['currency'].')</option>';
						endforeach;
						?>
					</select>
				</div>
			</div>
			<?php else: ?>
			<div class="form-group">
				<label class="col-lg-4 control-label"></label>
				<div class="col-lg-8 text-left">
					<p class="form-control-static"><b><?php echo $text['PACKAGE']?></b> <?php echo (isset($set_package['description'][$lang]) ? $set_package['description'][$lang] : (isset($set_package['description']["en"]) ? $set_package['description']["en"] : reset($set_package['description']))); ?> <?php echo '('.(!empty($set_package['amount'])?$set_package['amount']:'0 ').$set_package['template_account']['currency'].')'; ?></p>
					<input type=hidden name="package" value="0" />
				</div>
			</div>
			<?php
			endif;
			if(!empty($set_package['cupon_on'])): ?>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="promo_code"><?php echo $text['PROMO_CODE']?></label>
				<div class="col-lg-8">
					<input type="text" id="promo_code" name="promo_code" value="<?php echo empty($data["promo_code"]) ? "" : $data["promo_code"];?>" class="form-control">
				</div>
			</div>
			<?php endif;?>
			<input type="hidden" id="pack" value="<?php echo $pack;?>" name="pack" />
		</div>
		<div class="col-md-6 col-md-offset-3">
			<h3><?php echo $text['ADDRESS_INFO']?></h3>
		</div>
		<div class="col-md-6 col-md-offset-3" id="mandatory-fields">
			<div class="form-group">
				<label class="col-lg-4 control-label" for="firstname"><?php echo $text['FIRST_NAME']?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["firstname"]) ? "" : $data["firstname"]; ?>" id="firstname" maxlength="25" data-label="<?php echo $text['FIRST_NAME']?>" class="form-control check letters-digits-dashes-spaces mand" name="firstname">
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="lastname"><?php echo $text['LAST_NAME']?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["lastname"]) ? "" : $data["lastname"]; ?>" id="lastname" maxlength="25" data-label="<?php echo $text['LAST_NAME']?>" class="form-control check letters-digits-dashes-spaces mand" name="lastname">
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="email">E-mail <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["email"]) ? "" : $data["email"]; ?>" id="email" maxlength="99" data-label="E-mail" class="form-control check email mand" name="email">
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="country"><?php echo $text['COUNTRY_REGION']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<select id="country" class="form-control mand custom-select" data-label="<?php echo $text['COUNTRY_REGION']; ?>" data-target="#state" name="country">
						<option<?php echo empty($data["country"]) ? " selected=\"selected\"" : ""; ?> value=""><?php echo $text['NOT_SET']; ?></option>
					<?php
					$countries = '';
					$selected_states = NULL;
					foreach ($countries_states_list as $country_short_name => $country_info):
						if(!empty($data["country"]) && !empty($data["state"]) && $country_short_name == $data["country"]) $selected_states = $country_info['states'];
						$states = '';
						foreach ($country_info['states'] as $iso_3166_a2 => $state):
							$states .= (($states == '')?'':',').'"'.$iso_3166_a2.'":"'.htmlspecialchars($state,ENT_QUOTES).'"';
						endforeach;
						$countries .= '<option'.(!empty($data["country"]) && !empty($data["state"]) && $country_short_name == $data["country"]
								? " selected=\"selected\"" : "").' data-options=\'{'.$states.'}\' value="'.$country_short_name.'">'.$country_info['country'].'</option>';
					endforeach;
					echo $countries;
					?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="state"><?php echo $text['PROVINCE_STATE']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<select id="state" class="form-control mand custom-select2" data-label="<?php echo $text['PROVINCE_STATE']; ?>" name="state">
						<option value="" ><?php echo $text['NOT_SET']; ?></option>
						<?php
						if($selected_states):
						foreach ($selected_states as $iso_3166_a2 => $state):
							echo "<option".($data["state"] == $iso_3166_a2 ? " selected=\"selected\"" : "")." value=\"".$iso_3166_a2."\" >".$state."</option>";
						endforeach;
						endif;
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="city"><?php echo $text['CITY']?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["city"]) ? "" : $data["city"]; ?>"  id="city" maxlength="30" class="form-control check letters-digits-dashes-spaces mand" data-label=<?php echo $text['CITY']?> name="city" />
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="address"><?php echo $text['ADDRESS']?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<textarea id="address" maxlength="205" class="form-control check letters-digits-dashes-spaces-punctuation mand" data-label="<?php echo $text['ADDRESS']?>" name="address" cols="20" rows="3"><?php echo empty($data["address"]) ? "" : $data["address"]; ?></textarea>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="zip"><?php echo $text['ZIP']?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["zip"]) ? "" : $data["zip"]; ?>" maxlength="13" id="zip" data-label="<?php echo $text['ZIP']?>" class="form-control check letters-digits mand" name="zip">
				</div>
			</div>
			<div id="show-optional" class="row optional-fields">
				<span class="btn btn-link" ><?php echo $text['SHOW_FIELDS']; ?> <span class="glyphicon glyphicon-chevron-down"></span></span>
			</div>
			<div id="hide-optional" class="row hidden optional-fields">
				<span class="btn btn-link" ><?php echo $text['HIDE_FIELDS']; ?> <span class="glyphicon glyphicon-chevron-up"></span></span>
			</div>
			<div class="spacer5"></div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="companyname"><?php echo $text['COMPANY_NAME']; ?> </label>
				<div class="col-lg-8">
					<input type="text" maxlength="41" value="<?php echo empty($data["companyname"]) ? "" : $data["companyname"]; ?>" name="companyname" id="companyname" class="form-control check letters-digits-dashes-spaces-punctuation">
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="salutation">Mr./Ms./...</label>
				<div class="col-lg-8">
					<input type="text" maxlength="15" value="<?php echo empty($data["salutation"]) ? "" : $data["salutation"]; ?>" id="salutation" class="form-control check letters-digits-dashes-punctuation" name="salutation">
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="midinit"><?php echo $text['MIDINT']; ?> </label>
				<div class="col-lg-8">
					<input type="text" maxlength="25" value="<?php echo empty($data["midinit"]) ? "" : $data["midinit"]; ?>" id="midinit" class="form-control check letters-punctuation" name="midinit">
				</div>
			</div>
			<?php if(empty($config['sms'])):?>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="phone1"><?php echo $text['PHONE']?> </label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["phone1"]) ? "" : $data["phone1"]; ?>" id="phone1" maxlength="21" class="form-control check digits" name="phone1">
				</div>
			</div>
			<?php endif;?>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="cont1"><?php echo $text['CONTACT']; ?> </label>
				<div class="col-lg-8">
					<input value="<?php echo empty($data["cont1"]) ? "" : $data["cont1"]; ?>" type="text" maxlength="41" id="cont1" class="form-control check letters-dashes-spaces-punctuation" name="cont1">
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="fax"><?php echo $text['FAX']; ?> </label>
				<div class="col-lg-8">
					<input value="<?php echo empty($data["fax"]) ? "" : $data["fax"]; ?>" type="text" maxlength="21" id="fax" class="form-control check digits" name="fax" placeholder="">
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="phone2"><?php echo $text['ALT_PHONE']; ?> </label>
				<div class="col-lg-8">
					<input value="<?php echo empty($data["phone2"]) ? "" : $data["phone2"]; ?>" type="text" maxlength="21" id="phone2" class="form-control check digits" name="phone2">
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="cont2"><?php echo $text['ALT_CONTACT']; ?>  </label>
				<div class="col-lg-8">
					<input value="<?php echo empty($data["cont2"]) ? "" : $data["cont2"]; ?>" type="text" maxlength="21" id="cont2" class="form-control check letters-digits-dashes-punctuation" name="cont2">
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="note"><?php echo $text['NOTE']; ?> </label>
				<div class="col-lg-8">
					<textarea maxlength="200" id="note" class="form-control" name="note" cols="20" rows="3"><?php echo empty($data["note"]) ? "" : $data["note"]; ?></textarea>
				</div>
			</div>
			<div class="form-group optional hidden">
				<label class="col-lg-4 control-label" for="i_time_zone"><?php echo $text['TIMEZONE']; ?> </label>
				<div class="col-lg-8">
					<select id="i_time_zone" class="form-control" name="i_time_zone">
					<option value='' selected="selected"><?php echo $text['NOT_SET']; ?></option>
					<?php
					foreach ($timezones as $timezone_id => $timezone_name):
						echo '<option'.(!empty($data["i_time_zone"]) && $timezone_id == $data["i_time_zone"] ? " selected=\"selected\"" : "").' value="'.$timezone_id.'">'.$timezone_name.'</option>';
					endforeach;
					?>
					</select>
				</div>
			</div>
		</div>
	</div>

	<!-- Payment info -->
	<?php if (count($payment_method) > 0 && !(count($payment_method) == 1 && implode('',$payment_method) == 'PayPal')): ?>
	<div class="row to-validate col-md-6 col-md-offset-3 hidden" id="payment-info">
		<h3><?php echo $text['PAYMENT_INFO']; ?></h3>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="payment_method_select"><?php echo $text['PAYMENT_METHOD']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
			<?php if(count($payment_method) > 1):?>
				<select id="payment_method_select" class="form-control" data-label="<?php echo $text['PAYMENT_METHOD']; ?>" name="payment_method_select">
					<?php foreach ($payment_method as $key => $value):
						echo '<option'.(!empty($data["payment_method_select"]) && $key == $data["payment_method_select"] ? " selected=\"selected\"" : "").' class="payment-method-'.$key.'" value="'.$key.'">'.$value.'</option>';
					endforeach;?>
				</select>
			<?php else:?>
				<span class="payment-method-0"><?php echo $payment_method[reset(array_keys($payment_method))];?></span>
				<input type="hidden" name="payment_method_select" value="<?php echo reset(array_keys($payment_method));?>" />
			<?php endif; ?>
			</div>
		</div>
		<div id="card_info">
			<div class="form-group">
				<label class="col-lg-4 control-label" for="cc_number"><?php echo $text['CREDIT_CARD']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["cc_number"]) ? "" : $data["cc_number"]; ?>"  maxlength="16" id="cc_number" class="form-control check digits mand" data-label="<?php echo $text['CREDIT_CARD']; ?>" name="cc_number">
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label"><?php echo $text['EXP_DATE']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-4">
					<select id="cc_month" class="form-control mand" name="cc_month">
					<?php
					$i=1;
					while ($i <= 12):
						$j = ($i < 10) ? '0'.(string)$i : $i;
						echo '<option'.(!empty($data["cc_month"]) && $data["cc_month"] == $j ? " selected=\"selected\"" : "").' value="'.$j.'">'.$j.'</option>';
						$i++;
					endwhile;
					?>
					</select>
				</div>
				<div class="col-lg-4">
					<select id="cc_year" class="form-control mand" name="cc_year">
					<?php
					$cur_year = $new_year = intval(date('Y'));
					while ($new_year <= $cur_year + 5):
						echo '<option'.(!empty($data["cc_year"]) && $data["cc_year"] == $new_year ? " selected=\"selected\"" : "").' value="'.$new_year.'">'.$new_year.'</option>';
						$new_year++;
					endwhile;
					?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="cc_cvv">CVV2 <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="password" value="<?php echo empty($data["cc_cvv"]) ? "" : $data["cc_cvv"]; ?>" id="cc_cvv" maxlength="16" class="form-control check digits mand" data-label="CVV2" name="cc_cvv">
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="cc_name"><?php echo $text['NAME_ON_CARD']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" id="cc_name" value="<?php echo empty($data["cc_name"]) ? "" : $data["cc_name"]; ?>" maxlength="41" class="form-control check letters-digits-dashes-spaces mand" data-label="<?php echo $text['NAME_ON_CARD']; ?>" name="cc_name">
				</div>
			</div>
			<div class="form-group">
			<label class="col-lg-4 control-label" for="iso_3166_1_a2"><?php echo $text['COUNTRY']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<select id="iso_3166_1_a2" class="form-control mand custom-select" data-label="<?php echo $text['COUNTRY']; ?>" data-target="#iso_3166_a2" name="iso_3166_1_a2" >
						<option<?php echo empty($data["iso_3166_1_a2"]) ? " selected=\"selected\"" : ""; ?> value=""><?php echo $text['NOT_SET']; ?></option>
					<?php
					$countries = '';
					$selected_states = NULL;
					foreach ($countries_states_list as $country_short_name => $country_info):
						if(!empty($data["iso_3166_1_a2"]) && !empty($data["iso_3166_a2"]) && $country_short_name == $data["iso_3166_1_a2"]) $selected_states = $country_info['states'];
						$states = '';
						foreach ($country_info['states'] as $iso_3166_a2 => $state):
							$states .= (($states == '')?'':',').'"'.$iso_3166_a2.'":"'.htmlspecialchars($state,ENT_QUOTES).'"';
						endforeach;
						$countries .= '<option'.(!empty($data["iso_3166_1_a2"]) && !empty($data["iso_3166_a2"]) && $country_short_name == $data["iso_3166_1_a2"]
								? " selected=\"selected\"" : "").' data-options=\'{'.$states.'}\' value="'.$country_short_name.'">'.$country_info['country'].'</option>';
					endforeach;
					echo $countries;
					?>
					</select>
				</div>
			</div>
			<div class="form-group">
			<label class="col-lg-4 control-label" for="iso_3166_a2"><?php echo $text['STATE']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<select id="iso_3166_a2" class="form-control mand" data-label="<?php echo $text['STATE']; ?>" name="iso_3166_a2">
						<option value=""><?php echo $text['NOT_SET']; ?></option>
						<?php
						if($selected_states):
						foreach ($selected_states as $iso_3166_a2 => $state):
							echo "<option".($data["iso_3166_a2"] == $iso_3166_a2 ? " selected=\"selected\"" : "")." value=\"".$iso_3166_a2."\" >".$state."</option>";
						endforeach;
						endif;
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="cc_city"><?php echo $text['CITY']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" value="<?php echo empty($data["cc_city"]) ? "" : $data["cc_city"]; ?>" id="cc_city" maxlength="50" class="form-control check letters-digits-dashes-spaces mand" data-label="<?php echo $text['CITY']; ?>" name="cc_city">
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="cc_address"><?php echo $text['ADDRESS']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<textarea id="cc_address" maxlength="41" class="form-control check letters-digits-dashes-spaces-punctuation mand" data-label="<?php echo $text['ADDRESS']?>" name="cc_address" cols="20" rows="3"><?php echo empty($data["cc_address"]) ? "" : $data["cc_address"]; ?></textarea>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label" for="cc_zip"><?php echo $text['ZIP']; ?> <span class="text-danger">*</span></label>
				<div class="col-lg-8">
					<input type="text" maxlength="41" id="cc_zip" value="<?php echo empty($data["cc_zip"]) ? "" : $data["cc_zip"]; ?>" class="form-control check letters-digits mand" data-label="<?php echo $text['ZIP']; ?>" name="cc_zip">
					<span class="spacer5"></span>
					<span class="spacer5"></span>
					<span id="copy-address" class="btn btn-default btn-xs"><?php echo $text['COPY_ADDRESS'];?></span>
				</div>
			</div>
		</div >
	</div>
	<?php endif; ?>

	<!-- Account options -->
	<?php if (!empty($set_package['subscriber']['alias']) || ($set_package['subscriber']['id_source'] != 'rand' && $set_package['subscriber']['id_source'] != 'none_field')): ?>
	<div class="row to-validate col-md-6 col-md-offset-3 hidden" id="account-options">
		<h3><?php echo $text['ACCOUNT_OPTIONS']; ?></h3>
		<?php if ($set_package['subscriber']['id_source'] == 'DID'): ?>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="numb"><?php if (!empty($set_package['did_split_on'])): echo $text['AREA_CODE'];
				else: echo $text['YOUR_PHONE_NUMBER']; endif; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select id="numb" class="form-control hidden mand<?php if (!empty($set_package['did_split_on'])):?> custom-select" data-target="#number<?php else: echo '" name="number';?><?php endif;?>" data-label="<?php if (!empty($set_package['did_split_on'])): echo $text['AREA_CODE'];
				else: echo $text['YOUR_PHONE_NUMBER']; endif; ?>"></select>
				<span class="loader"><img src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
			</div>
		</div>
			<?php if (!empty($set_package['did_split_on'])): ?>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="number"><?php echo $text['YOUR_PHONE_NUMBER']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select id="number" class="form-control mand custom-select2" data-label="<?php echo $text['YOUR_PHONE_NUMBER']; ?>">
					<option value=""><?php echo $text['SELECT_NUMBER']; ?></option>
				</select>
				<input type="hidden" name="number" id="hidden_number" value="" />
			</div>
		</div>
			<?php endif; ?>
		<?php elseif ($set_package['subscriber']['id_source'] == 'man'): ?>
		<div class="form-group has-feedback">
			<label class="col-lg-4 control-label" for="number"><?php echo $text['YOUR_PHONE_NUMBER'];?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="hidden" class="prefix" id="prefix" value="<?php echo !empty($set_package['subscriber']['prefix']) ? $set_package['subscriber']['prefix'] : '';?>" />
				<input type="text" maxlength="32" id="number" class="form-control check digits mand number-check" data-label="<?php echo $text['YOUR_PHONE_NUMBER'];?>" name="number" oninput="$('#phone1').val($(this).val());" />
				<span class="loader account-check hidden"><img src="<?php echo $path; ?>img/loader.gif" /></span>
				<span class="glyphicon account-check glyphicon-ok success-sign form-control-feedback hidden"></span>
				<span class="glyphicon account-check glyphicon-remove error-sign form-control-feedback hidden"></span>
			</div>
		</div>
		<?php elseif ($set_package['subscriber']['id_source'] == 'DID_API'): ?>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="did-api-countries"><?php echo $text['COUNTRY_REGION']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select class="form-control mand did-api-select hidden" id="did-api-countries" data-label="<?php echo $text['COUNTRY_REGION']; ?>"></select>
				<span class="loader"><img src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
			</div>
		</div>
		<div class="form-group hidden">
			<label class="col-lg-4 control-label" for="did-api-states"><?php echo $text['PROVINCE_STATE']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select disabled class="form-control mand did-api-select hidden" id="did-api-states" data-label="<?php echo $text['PROVINCE_STATE']; ?>"></select>
				<span class="loader"><img src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
			</div>
		</div>
		<div class="form-group hidden">
			<label class="col-lg-4 control-label" for="did-api-ratecenters"><?php echo $text['CITY']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select disabled class="form-control mand did-api-select hidden" id="did-api-ratecenters" data-label="<?php echo $text['CITY']; ?>"></select>
				<span class="loader"><img src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
			</div>
		</div>
		<div class="form-group hidden">
			<label class="col-lg-4 control-label" for="did-api-numbers"><?php echo $text['YOUR_PHONE_NUMBER']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<select disabled name="number" class="form-control mand did-api-select hidden" id="did-api-numbers" data-label="<?php echo $text['YOUR_PHONE_NUMBER']; ?>"></select>
				<span class="loader"><img src="<?php echo $path; ?>img/loader.gif" /> <?php echo $text['PROCESSING']; ?></span>
			</div>
			<input type="hidden" name="did_api[package]" id="did-api-package" value="" />
			<input type="hidden" name="did_api[city_id]" id="did-api-city_id" value="" />
			<input type="hidden" name="did_api[ratecenter]" id="did-api-ratecenter" value="" />
			<input type="hidden" name="did_api[country]" id="did-api-country" value="" />
			<input type="hidden" name="did_api[monthly]" id="did-api-monthly" value="" />
			<input type="hidden" name="did_api[state]" id="did-api-state" value="" />
		</div>
		<?php endif; ?>

		<?php if (!empty($set_package['subscriber']['alias'])): ?>
			<?php for ($i = 1; $i <= intval($set_package['subscriber']['alias']); $i++): ?>
		<div class="form-group has-feedback">
			<label class="col-lg-4 control-label" for="alias-<?php echo $i; ?>"><?php echo $text['ALIAS'].' '.$i; ?></label>
			<div class="col-lg-8">
				<input type="hidden" class="prefix" value="<?php echo !empty($set_package['alias_prefix_on']) ? $set_package['subscriber']['prefix'] : '';?>" />
				<input type="text" maxlength="32" id="alias-<?php echo $i; ?>" class="form-control check digits number-check" name="alias[<?php echo $i; ?>]" />
				<span class="loader account-check hidden"><img src="<?php echo $path; ?>img/loader.gif" /></span>
				<span class="glyphicon account-check glyphicon-ok success-sign form-control-feedback hidden"></span>
				<span class="glyphicon account-check glyphicon-remove error-sign form-control-feedback hidden"></span>
			</div>
		</div>
			<?php endfor; ?>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Captcha & terms -->
	<?php if (!empty($config['captcha']['public_key']) || !empty($set_package['terms_text']) || !empty($config['sms'])): ?>
	<div class="row to-validate col-md-6 col-md-offset-3 hidden" id="misc">
		<?php if (isset($config['captcha']) && $config['captcha']['public_key'] != "" || !empty($config['sms'])): ?>
		<h3><?php echo $text['ANTI_BOT_PROTECTION']; ?></h3>

		<?php if (isset($config['captcha']) && $config['captcha']['public_key'] != ""): ?>

		<div class="row center-block">
			<div id="recaptcha_widget">
				<div align="center" class="g-recaptcha" data-theme="light" data-size="normal" data-sitekey="<?php echo $config['captcha']['public_key']; ?>"></div>
				<div class="help-block with-errors"></div>
		</div>
		</div>

		<?php endif;?>

		<?php if(!empty($config['sms'])): ?>
		<div class="form-group optional">
			<label class="col-lg-4 control-label" for="phone1"><?php echo $text['PHONE']?> <span class="text-danger">*</span></label>
			<div class="col-lg-8">
				<input type="text" id="phone1" maxlength="21" class="form-control check digits mand" name="phone1">
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" for="sms_code"><?php echo $text['SMS']; ?> <span class="text-danger">*</span></label>
			<div class="col-lg-8 input-group">
				<span class="loader account-check hidden" style="right:220px;"><img src="<?php echo $path; ?>img/loader.gif" /></span>
				<input type="text" id="sms_code" maxlength="6" class="form-control check digits mand" data-label="<?php echo $text['SMS']; ?>" name="sms_code">
				<span id="send_sms" class="input-group-btn"><button class="btn btn-default" type="button"><?php echo $text['SEND_SMS'];?></button></span>
			</div>
		</div>
		<?php endif?>

		<?php endif; ?>
		<?php if(isset($set_package['terms_text'])): ?>
		<h3><?php echo $text['TERMS_AND_COND']; ?></h3>
		<textarea id="termstext" class="form-control" readonly="readonly" rows="5"><?php echo $set_package['terms_text'];?></textarea>
		<div class="form-group">
			<div class="col-lg-offset-2 col-lg-10">
				<div class="checkbox">
					<label>
						<input type="checkbox" id="terms1" name="terms1" class="mand"><?php echo $text['TERMS_CB1']; ?> <span class="text-danger">*</span>
					</label>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<span class="spacer5"></span>
	<div class="row col-md-6 col-md-offset-3" id="submit-button">
		<input id="back-button" class="btn btn-primary" type="button" value="<?php echo $text['BACK']; ?>">
		<input id="next-button" class="btn btn-primary" type="button" value="<?php echo $text['NEXT']; ?>">
		<input id="finish-button" class="btn btn-primary" type="button" value="<?php echo $text['FINISH']; ?>">
	</div>
	<span class="spacer5"></span>
	<input type="hidden" id="subscription_token" name="subscription_token" value="<?php echo $token; ?>" />
</form>
