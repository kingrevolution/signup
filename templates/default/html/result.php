<?php defined('SIGNUP') or die('Restricted access'); ?>
<?php if(!empty($paypal_button)):?>
	<div id="payplal-form-container" class="jumbotron">
		<h1 id="confirm_title"><?php echo $text['CONFIRM_TITLE'];?></h1>
		<p><?php echo $text['PAYPAL_WARNING'];?></p>
		<p>
			<?php echo $paypal_button; ?>
			<a id="paypal-form-submit" class="btn btn-primary btn-lg" role="button" onClick="clearTimeout(timeout);container.remove();form.submit();$(this).removeAttr('onClick').unbind('click');">
				<?php echo $text['PAYPAL_PAY'];?> <span id="time_left" style="background-color:#FFFFFF;color:#428BCA;margin-left:3%;" class="badge">15</span>
			</a>
		</p>
	</div>
	<script>
		var container = $('#time_left'),
			timeout = null,
			time_left = parseInt(container.html())+1,
			form = $('#payplal-form-container').find('form');
		form.addClass('hidden');
		run_timer();
		function run_timer()
		{
			time_left--;
			container.html(time_left);
			if(time_left)
			{
				timeout = setTimeout(run_timer,1000);
				return;
			}
			$('#paypal-form-submit').removeAttr('onClick').unbind('click');
			form.submit();
		}
	</script>
<?php elseif(!empty($email_confirm)):?>
	<div class="jumbotron">
		<h1 id="confirm_title"><?php echo $text['CONFIRM_TITLE'];?></h1>
		<p><?php echo $text['EMAIL_CONFIRM_WARNING'];?></p>
	</div>
<?php elseif(!empty($stripe_3ds_url)):?> 
	<div class="jumbotron">
		<h1 id="confirm_title"><?php echo $text['CONFIRM_TITLE'];?></h1>
		<p><?php echo $text['STRIPE_COMPLETE_3DS_AUTHENTICATON'];?></p>
	</div>
<?php else:
	$prefix_condition = (empty($subscriber['prefix']) || !in_array($subscriber['prefix'],array('a','cc','cb')));
?>
	<h3><a class="back-link" href="<?php echo $root_path.'?lang='.$lang.str_replace('&layout=result','',$vars); ?>"><span class="glyphicon glyphicon-share-alt"></span> <?php echo $text['BACK']; ?></a></h3>
	<?php if(!isset($subscriber["virtoffice"])): ?>
	<div class="panel panel-default">
		<div class="panel-heading"><?php echo $text['ACCOUNT']; ?></div>
		<table class="table">
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['TYPE']; ?></strong></td>
				<td align="left"><?php echo $account_type;?></td>
			</tr>
			<?php if (!empty($subscriber['prefix']) && $subscriber['prefix'] == 'cc'): ?>
			<tr>
				<td width="50%" align="right"><strong>PIN</strong></td>
				<td align="left"><?php echo $pin; ?></td>
			</tr>
			<?php else: ?>
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['PHONE_NUMBER']; ?></strong></td>
				<td align="left"><?php echo $number; ?></td>
			</tr>
				<?php if ($subscriber['billing_model'] != '0' && $prefix_condition): ?>
				<tr>
					<td width="50%" align="right"><strong><?php echo $text['SERVICE_PASSWORD']; ?></strong></td>
					<td align="left"><?php echo htmlspecialchars($voip_pass);?></td>
				</tr>
					<?php if(!empty($qrcode_data)):
						$result_token = $_SESSION['result_token'] = substr(md5(mt_rand()*time()), 0, 10);
						$url = SignupHelper::GetUrl().'&data='.urlencode($qrcode_data).'&task=ajax&result_token='.$result_token.'&act=GetQrcode';
					?>
				<tr>
					<td width="50%" align="right"><strong><?php echo $text['PROVISION_SOFTPHONE']; ?></strong></td>
					<td align="left"><img src="<?php echo $url;?>" alt="<?php echo $text['PROVISION_SOFTPHONE']; ?>" /></td>
				</tr>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
			<?php if (!empty($alias)):
				for ($i=1; $i <= sizeof($alias); $i++):
					if ($alias[$i]):
						?>
						<tr>
							<td width="50%" align="right"><strong><?php echo $text['ALIAS'].' '.$i; ?></strong></td>
							<td align="left"><?php echo $alias[$i];?></td>
						</tr>
						<?php
					endif;
				endfor;
			endif; ?>
		</table>
	</div>
	<?php endif; ?>

	<?php if ($subscriber['billing_model'] != '0' && $prefix_condition && !isset($subscriber["virtoffice"])): ?>
	<div class="panel panel-default">
		<div class="panel-heading"><?php echo $text['ACCOUNT_INTARFACE']; ?></div>
		<table class="table">
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['LOGIN']; ?></strong></td>
				<td align="left"><?php echo $account_login;?></td>
			</tr>
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['PASSWORD']; ?></strong></td>
				<td align="left"><?php echo htmlspecialchars($account_password);?></td>
			</tr>
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['LINK']; ?></strong></td>
				<td align="left">
					<a href="javascript:document.getElementById('account_url').submit();">Account Self-Care</a>
					<form id="account_url" method="POST" action="<?php echo $references['account_sci']; ?>" target="_blank">
						<input type="hidden" value="<?php echo $account_login; ?>" name="pb_auth_user">
						<input type="hidden" value="<?php echo $account_password; ?>" name="pb_auth_password">
					</form>
				</td>
			</tr>
		</table>
	</div>
	<?php endif; ?>

	<?php if (empty($subscriber['i_customer']) && $prefix_condition):?>
	<div class="panel panel-default">
		<div class="panel-heading"><?php echo $text['CUSTOMER_INTERFACE']; ?></div>
		<table class="table">
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['LOGIN']; ?></strong></td>
				<td align="left"><?php echo $customer_login;?></td>
			</tr>
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['PASSWORD']; ?></strong></td>
				<td align="left"><?php echo htmlspecialchars($customer_password);?></td>
			</tr>
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['LINK']; ?></strong></td>
				<td align="left">
					<a href="javascript:document.getElementById('customer_url').submit();">Customer Self-Care</a>
					<form id="customer_url" method="POST" action="<?php echo $references['customer_sci']; ?>" target="_blank">
						<input type="hidden" value="<?php echo $customer_login; ?>" name="pb_auth_user">
						<input type="hidden" value="<?php echo htmlspecialchars_decode($customer_password); ?>" name="pb_auth_password">
					</form>
				</td>
			</tr>
		</table>
	</div>
	<?php endif; ?>

	<?php if (!empty($softphone_link) && $prefix_condition): ?>
	<div class="panel panel-default">
		<div class="panel-heading"><?php echo $text['ADDITIONAL_INFO']; ?></div>
		<table class="table">
			<tr>
				<td width="50%" align="right"><strong><?php echo $text['SOFTPHONE_LINK']; ?></strong></td>
				<td align="left"><a class="form-control-static" target="_new" href="<?php echo $softphone_link?>"><?php echo $text['DOWNLOAD_SOFTPHONE']; ?></a></td>
			</tr>
		</table>
	</div>
	<?php endif; ?>
<?php endif;?>
