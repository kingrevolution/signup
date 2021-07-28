	function ajax_call(data,callback,async)
	{	data['subscription_token'] = $('#subscription_token').val();
		data['pack'] = $('#pack').val();
		if(typeof(async) == 'undefined')
		{
			var async = true;
		}
		$.ajax({
			type: "POST",
			url: window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+'?task=ajax&layout=subscription&lang='+lang,
			data: data,
			async: async
		}).complete(function(response){ window[callback](response); });
	}

	function did_api(response)
	{
		if(response && response.responseText == '')
		{
			return;
		}
		response = (typeof(response.responseText) != 'undefined') ? JSON.parse(response.responseText) : response;
		var options = '<option value="">'+JS_NOT_SET+'</option>',
			ratecenter = (typeof(response.ratecenter) != 'undefined') ? response.ratecenter : null,
			city_id = (typeof(response.city_id) != 'undefined') ? response.city_id : null,
			state = (typeof(response.state) != 'undefined') ? response.state : null,
			country = (typeof(response.country) != 'undefined') ? response.country : null;
		if(city_id || ratecenter)
		{
			var step = 'numbers',
				key = (city_id) ? city_id : ratecenter,
				attributes = city_id ? ' data-city_id="'+city_id+'"' : '';
			numbers[key] = response.numbers;
			var list = numbers[key];
			for (var i in list)
			{
				options += '<option value="'+i+'" data-monthly="'+list[i].monthly+'" data-country="'+country+'" data-pack="'+list[i].pack+'"'+attributes+(list[i].city_id ? ' data-city_id="'+list[i].city_id+'"' : '')+'>'+i+'</option>';
			}
		}
		else if(state || state === '')
		{
			var step = 'ratecenters';
			if(state)
			{
				ratecenters[state] = response.ratecenters;
			}
			else
			{
				ratecenters[country] = response.ratecenters;
			}
			var list = response.ratecenters;
			for (var i in list)
			{
				var dataRatecenter = typeof(list[i].ratecenter) != 'undefined' ? list[i].ratecenter : '';
				var dataCityId = typeof(list[i].city_id) != 'undefined' ? list[i].city_id : '';
				options += '<option value="'+i+'"'+(state ? ' data-state="'+state+'"' : '')
					+' data-country="'+country+'"'+' data-ratecenter="'+dataRatecenter+'"'
					+' data-pack="'+list[i].pack+'"'+' data-city_id="'+dataCityId+'">'+list[i].name+'</option>';
			}
		}
		else if(country)
		{
			var step = 'states';
			states[country] = response.states;
			var list = states[country];
			if(list)
			{
				for (var i in list)
				{
					options += '<option value="'+i+'" data-pack="'+list[i].pack+'" data-country="'+country+'">'+list[i].name+'</option>';
				}
			}
			else
			{
				response['state'] = '';
				response['country'] = country;
				disable_didapi_selects('countries',false)
				disable_didapi_selects('states',true)
				return did_api(response);
			}
		}
		else
		{
			var step = 'countries';
			countries = response.countries;
			for (var i in countries)
			{
				options += '<option value="'+i+'" data-pack="'+countries[i].pack+'" data-country="'+i+'">'+countries[i].name+'</option>';
			}
		}
		var select = $('#did-api-'+step),
			loader = select.next('.loader');
		select.html(options);
		loader.addClass('hidden');
		select.prop('disabled',false);
		select.removeClass('hidden');
		disable_did_api_fields(false);
	}

	function get_did_patterns(response)
	{
		response = response.responseText;
		if (response === '0')
		{
			$('#error-field').append(JS_WARNING+': '+JS_NO_NUMBERS);
			$('#error-container').removeClass('hidden');
			disable_form();
		}
		else if (response !== '')
		{
			response = JSON.parse(response);
			var select = $("#numb"),
				translation = ($("#numb").attr('name'))?JS_SELECT_NUM:JS_SELECT_AREA_CODE,
				options = '<option value="">'+translation+'</option>';
			for(var i in response)
			{ 	if (typeof(response[i]) == 'object')
				{
					var numbers = '[';
					for(var j in response[i])
					{
						numbers += ((numbers == '[')?'':',')+'"'+response[i][j]+'"';
					}
					numbers += ']';
					options += '<option data-options=\''+numbers+'\' value="'+i+'">'+i+'</option>';
				}
				else
				{
					options += '<option value="'+response[i]+'">'+response[i]+'</option>';
				}
			}
			select.html(options);
			select.next('.loader').addClass('hidden');
			select.removeClass('hidden');
		}
		else
		{
			return false;
		}
	}

	function disable_form()
	{
		var elements = document.forms['websubscr'];
		for (var i in elements)
		{
			if (elements[i])
			{
				elements[i].disabled = true;
			}
		}
	}

	function submitInformation(captcha_response)
	{

		var submit_form = true;
		if ($("#recaptcha_widget").length > 0)
		{
			if (grecaptcha.getResponse() == "")
			{
				validator_baloon($("#recaptcha_widget"),'captcha');
				submit_form = false;
			}
			else if(captcha_response)
			{
				var status = captcha_response.responseText;
				if (status != "1")
				{
					validator_baloon($("#recaptcha_widget"),'captcha');
					submit_form = false;
				}
			}
			else
			{
				var data = {
					grecaptcha_response:grecaptcha.getResponse(),
					act:'CaptchaValidate'
				};
				ajax_call(data,'submitInformation',false);
				return false;
			}
		}

		if (submit_form)
		{
			$('#preloader').removeClass('hidden');
			$('#main').addClass('hidden');
			document.websubscr.submit();
			switch(progress_bar_type)
			{
				case 'advanced':
					$('#status').removeClass('hidden');
					setTimeout(GetProgress, 1500);
					break;
				case 'simple':
					$('#progress').removeClass('hidden');
					break;
			}
		}
	}

	function GetProgress()
	{
	    var url = window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+$('#subscription_token').val()+'.json',
	    	statusbar = $('#status-bar'),
	    	statusbar_val = $('#status-bar-val');
	    $.ajax({
	    	dataType: "json",
	    	url: url,
	    	success: function(data) {
				    	var status = data.complete;
				    	statusbar.width(status);
				    	statusbar_val.html(status);
				    	if(status != '100%')
				    	{
				    		setTimeout(GetProgress, 1000);
				    	}
		    		},
		    error: function(xhr, ajaxOptions, thrownError) {if(xhr.status==404 || xhr.status==304) {setTimeout(GetProgress, 2000);}}
	    });
	}

	function toggleOptional()
	{
		var elements = [$('#show-optional'),$('#hide-optional'),$('.optional')];

		for(var i in elements)
		{
			if(elements[i].hasClass('hidden'))
			{
				elements[i].removeClass('hidden');
			}
			else
			{
				elements[i].addClass('hidden');
			}
		}
	}

	function parse_data_options(source_select,target_select,empty_value_name)
	{
		var selection = source_select.find('option:selected');
		target_select_options = '<option value="">'+empty_value_name+'</option>';
		if(selection.val() != '')
		{
			var options = JSON.parse(selection.attr('data-options'));
			for (var value in options)
			{
				target_select_options += '<option value="'+value+'">'+options[value]+'</option>';
			}
		}
		target_select.html(target_select_options);
	}

	function getSteps()
	{
		var navbar = $('#navbar'),
			progressbar = $('#progress-bar'),
			targets = [{name:'address-info',translation:JS_ACCOUNT_INFO},
			           {name:'payment-info',translation:JS_PAYMENT_INFO},
			           {name:'account-options',translation:JS_ACCOUNT_OPTIONS},
			           {name:'misc',translation:JS_MISC}];
		for (var i in targets)
		{
			if ($('#'+targets[i].name).length > 0)
			{
				navbar.append('<li data-target="'+targets[i].name+'"><a class="cursor-default" href="#">'+targets[i].translation+'</a></li>');
			}
		}

		navbar.children().first().addClass('first active');
		navbar.children().last().addClass('last');

		progressbar.css('width', navbar.children('.active').width());
	}

	function changeStep(direction)
	{
		var navbar = $('#navbar'),
			progressbar = $('#progress-bar'),
			steps = navbar.children().length,
			active = navbar.children('.active'),
			index = active.index() + 1,
			back = $('#back-button'),
			next = $('#next-button'),
			containers = ['#address-info','#payment-info','#account-options','#misc'],
			finish = $('#finish-button');

		// Set step
		if (index <= steps && direction == 'next') {
			index++;
			active.children('a').addClass('text-muted');
			active.removeClass('active');
			active = navbar.children('li:nth-child(' + (index) + ')').addClass('active');
			progressbar.width(progressbar.width() + active.width());
		}
		else if (index > 0 && direction == 'back') {
			index--;
			progressbar.width(progressbar.width() - active.width());
			active.children('a').removeClass('text-muted');
			active.removeClass('active');
			active = navbar.children('li:nth-child(' + (index) + ')').addClass('active');
		}

		// Set buttons
		var is_first = active.hasClass('first'),
			is_last = active.hasClass('last'),
			to_show = (is_first&is_last)?{f:finish}:((is_first)?{n:next}:((is_last)?{f:finish,b:back}:{n:next,b:back})),
			buttons = {n:next,b:back,f:finish};
		for (var i in buttons)
		{
			if (typeof(to_show[i]) != 'undefined')
			{
				buttons[i].removeClass('hidden');
			}
			else
			{
				buttons[i].addClass('hidden');
			}
		}

		// Change display
		var container_id = '#'+ active.attr('data-target');
		for (var i in containers)
		{
			if (containers[i] == container_id)
			{
				$(containers[i]).removeClass('hidden');
			}
			else
			{
				$(containers[i]).addClass('hidden');
			}
		}
	}

	function disable_didapi_selects(step,selection)
	{
		var targets = {
				numbers:$('#did-api-numbers'),
				ratecenters:$('#did-api-ratecenters'),
				states:$('#did-api-states'),
				countries:$('#did-api-countries'),
			},
			j = null;
		for (var i in targets)
		{
			if(i == step)
			{
				if(j && !(selection))
				{
					targets[j].closest('.form-group').addClass('hidden');
					disable_did_api_fields(false);
				}
				else if(j)
				{
					targets[j].closest('.form-group').removeClass('hidden');
				}
				break;
			}
			else
			{
				if(j)
				{
					targets[j].closest('.form-group').addClass('hidden');
				}
				targets[i].prop('disabled',true);
				targets[i].addClass('hidden');
				targets[i].next('.loader').removeClass('hidden');
				j = i;
			}
		}
	}

	function send_sms_callback(response)
	{
		var text = '',
			style = '';
		response = JSON.parse(response.responseText);
		if(response.error.indexOf("Success") != -1) //success
		{
//dunno why we	need to show this to end user	text			text = JS_SMS_SESSION + response.session + ' ' + JS_SMS_ATTEMPTS + response.att;
			style += "box-shadow:inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6);border-color:#66AFE9;";
			if(parseInt(response.att) > 0)
			{
				$('#send_sms').find('button').prop('disabled',false);
			}
		}
		else
		{
			text = 'error: "' + response.error + '"';
			style += "box-shadow:inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(233, 133, 102, 0.6);border-color:red;";
		}
		$('#sms_code').prop('disabled',false)
			.attr('style',style)
			.attr('placeholder',text)
			.prev('.loader').addClass('hidden');
		setTimeout(function() {
			$('#sms_code').removeAttr("style");
		}, 3500);
	}

	function disable_did_api_fields(disable)
	{
		var targets = [$('#back-button'),
		               $('#next-button'),
		               $('#finish-button'),
		               $('#did-api-numbers'),
		               $('#did-api-ratecenters'),
		               $('#did-api-states'),
		               $('#did-api-countries')];

		for (var i in targets)
		{
			if(targets[i].attr('type') == 'button' || !(targets[i].hasClass('hidden')))
			{
				targets[i].prop('disabled',disable);
			}
		}

	}

	$(document).ready(function(){
		$('#send_sms').find('button').click(function(){
			var phone = $('#phone1').val();
			if(phone.length < 10)
			{
				validator_baloon($('#phone1'),'data');
				return false;
			}
			$(this).prop('disabled',true);
			$('#sms_code').prop('disabled',true);
			$('#sms_code').prev('.loader').removeClass('hidden');
			var data = {phone:phone,act:'SendSMS'};
			ajax_call(data,'send_sms_callback',false);
		});
		$('.did-api-select').change(function(){
			var response = new Object,
				select = $(this),
				step = select.attr('id').replace('did-api-',''),
				option = select.find('option:selected'),
				selection = select.val();
			disable_did_api_fields(true);
			disable_didapi_selects(step,selection);
			if(selection)
			{
				var data = {act:'DidApi',package:option.attr('data-pack'),target:null,pack:$('#pack').val()};
				response['package'] = data['package'];
				switch(step)
				{
					case 'countries':
						response['country'] = data['country'] = option.attr('data-country');
						if(typeof(states[data['country']]) != 'undefined')
						{
							response['states'] = states[data['country']];
						}
						else if(typeof(ratecenters[data['country']]) != 'undefined')
						{
							response['ratecenters'] = ratecenters[data['country']];
						}
						else
						{
							data['target'] = 'states';
						}
						break;
					case 'states':
						response['state'] = data['state'] = selection;
						response['country'] = data['country'] = option.attr('data-country');
						if(typeof(ratecenters[data['state']]) != 'undefined')
						{
							response['ratecenters'] = ratecenters[data['state']];
						}
						else
						{
							data['target'] = 'ratecenters';
						}
						break;
					case 'ratecenters':
						if(option.attr('data-city_id'))
						{
							response['city_id'] = data['city_id'] = option.attr('data-city_id');
						}
						if(option.attr('data-ratecenter'))
						{
							response['ratecenter'] = data['ratecenter'] = option.attr('data-ratecenter');
						}
						response['country'] = data['country'] = option.attr('data-country');
						if(option.attr('data-state'))
						{
							response['state'] = data['state'] = option.attr('data-state');
						}
						if(typeof(numbers[data['ratecenter']]) != 'undefined')
						{
							response['numbers'] = numbers[data['ratecenter']];
						}
						else
						{
							data['target'] = 'numbers';
						}
						break;
					case 'numbers':
						var select = $('#did-api-numbers'),
							option = select.find('option:selected');
						$('#did-api-package').val(option.attr('data-pack'));
						if(option.attr('data-country'))
						{
							$('#did-api-city_id').val(option.attr('data-city_id'));
							$('#did-api-ratecenter').val(option.attr('data-ratecenter'));
							$('#did-api-country').val(option.attr('data-country'));
							$('#did-api-monthly').val(option.attr('data-monthly'));
						}
						$('#did-api-ratecenter').val(($("#did-api-ratecenters option:selected").val()));
						$('#did-api-state').val(($("#did-api-states option:selected").val()));
						disable_did_api_fields(false);
						return;
					default:
						break;
				}
				if(data['target'])
				{
					ajax_call(data,'did_api');
				}
				else
				{
					did_api(response);
				}
			}
			else if(!selection && step == 'numbers')
			{
				disable_did_api_fields(false);
			}
		});
		$('#back-button').click(function(){changeStep('back');});
		$('.optional-fields').click(function(){toggleOptional();});
		getSteps();
		$('.custom-select').change(function(){
			parse_data_options($(this),$($(this).attr('data-target')),JS_NOT_SET);
			var hidden = $('#hidden_'+$(this).attr('id'));
			if(hidden.length > 0)
			{
				hidden.val($(this).find('option:selected').text());
			}
		});
		$('.custom-select2').change(function(){
			$('#hidden_'+$(this).attr('id')).val($(this).find('option:selected').text());
		});
		changeStep('none');
		$('#copy-address').click(function(){
			$('#cc_name').val($('#firstname').val()+' '+$('#lastname').val());
			$('#iso_3166_1_a2').val($('#country').val());
			parse_data_options($('#iso_3166_1_a2'),$('#iso_3166_a2'),JS_NOT_SET)
			$('#iso_3166_a2').val($('#state').val());
			$('#cc_city').val($('#city').val());
			$('#cc_address').val($('#address').val());
			$('#cc_zip').val($('#zip').val());
		});
		$('#package').change(function(){
			var pack_num = parseInt($(this).val()),
				pack = (pack_num) ? '&package='+pack_num : '',
				url = window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+'?lang='+lang+pack;
				window.location.href = url;
		});
		$('#payment_method_select').change(function(){
			var selection = $(this).find('option:selected').html(),
				fields  = $('#payment-info').find('select:not([id=payment_method_select]),textarea,input[type=text],input[type=password]'),
				copy_address = $('#copy-address'),
				disabled = (selection == 'PayPal') ? true : false,
				process_class = (disabled) ? 'addClass' : 'removeClass';
			fields.each(function(){
				$(this).prop('disabled',disabled);
			});
			copy_address[process_class]('hidden');
		});
		$('.number-check').focusout(function(){
			var id = $(this).val();
			if(id == '')
			{
				return;
			}
			else
			{
				id = $(this).prev('.prefix').val()+id;
			}
			var container = $(this).closest('.has-feedback'),
				icons = {error:container.find('.error-sign'),success:container.find('.success-sign')},
				loader = container.find('.loader');
				buttons = [$('#next-button'),$('#back-button'),$('#finish-button')];
			$(this).prop('disabled',true);
			loader.removeClass('hidden');
			container.removeClass('has-error').removeClass('has-success');
			for (var i in icons)
			{
				icons[i].addClass('hidden');
			}
			for (var i in buttons)
			{
				buttons[i].prop('disabled',true);
			}
			if (typeof(checked_accounts[id]) == 'undefined')
			{
				var	data = {account_id:id,
						act:'ExistsAccount',
						subscription_token:$('#subscription_token').val(),
						pack:$('#pack').val()},
					status = $.ajax({
						type: "POST",
						url: window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+'?task=ajax&lang='+lang,
						data: data,
						async: false
					}).responseText;
				status = JSON.parse(status);
				status = status.status;
				checked_accounts[id] = status;
			}
			var status = checked_accounts[id];
			container.addClass('has-'+status);
			icons[status].removeClass('hidden');
			loader.addClass('hidden');
			if(status == 'error')
			{
				$(this).val('');
				$(this).attr('placeholder',JS_EXISTS);
			}
			for (var i in buttons)
			{
				buttons[i].prop('disabled',false);
			}
			$(this).prop('disabled',false);
		});
	});
	var checked_accounts = {}, states = {}, ratecenters = {}, numbers = {};
