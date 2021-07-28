var ajax_call = function(data,callback,arguments) {
		var arg = arguments || {};
		data['wizard_token'] = $('#wizard_token').val();
		$.ajax({
			type: "POST",
			url: window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+'?task=ajax&layout=wizard&lang='+lang,
			data: data,
			async: true
		}).complete(function(result){
			if(result.responseText == '')
			{
				return false;
			}
			arg['result'] = JSON.parse(result.responseText);
			arg['act'] = data['act'];
			window[callback](arg);
		});
	},

	process_response = function(arguments){
		var packs = arguments.packs || null,
			act = arguments.act,
			result = (typeof(arguments.result) == 'object') ? arguments.result : JSON.parse(arguments.result);
		if(act == 'GetOwnerBatchList')
		{
			ownerbatch = result;
			var target = $('.batch-list')
		}
		else if(act == 'GetRepresentatives')
		{
			representatives = result;
			var target = $('.representative-list')
		}
		else if(act == 'CheckVirtoffice')
		{
			virtoffice = Boolean(result);
			$(".create-customer").each(function(){
				var _this = $(this),
					packId = _this.closest(".package-container").attr("id").replace("packDiv","");
				_this.attr("data-virtoffice",(virtoffice ? "true" : "false"));
				if(virtoffice)
				{
					var targets = JSON.parse(_this.attr("data-target")),
						attr = "[";
					$.each(targets, function( index, value ) { attr += ("[" == attr ? "" : ", ") + "\""+value+"\""; });
					attr += ",\"packages-"+packId+"-virtoffice\",\"packages-"+packId+"-virtoffice_desc\"]";
					_this.attr("data-target",attr);
				}
				id_source_additional("packages-"+packId+"-");
			});
			return;
		}
		else
		{
			if(act == 'GetSubscriptions')
			{
				subscriptions = result;
			}
			else if(act == 'GetProducts')
			{
				products = result;
			}
			if(!packs)
			{
				return;
			}
			var target = [];
			for (var i in packs)
			{
				if(typeof(packs[i]) == 'object')
				{
					var pack = $(packs[i]['pack']).find(((act=='GetProducts')?'.product':'.subscription')+' select');
					pack.data('currency',packs[i]['currency']);
					target.push(pack);
				}
			}
			target = $(target);
		}
		target.each(function(){
			var res = (typeof($(this).data('currency')) != 'undefined') ? result[$(this).data('currency')] : result;
			add_options($(this),res);
		});
	},

	add_options = function(target,result){
		var	options = '<option value="">'+JS_NOT_SET+'</option>',
			parent = target.parent(),
			selected = target.prev('.selected-option').val();
		for(var i in result)
		{
			options += '<option value="'+i+'"'+((i==selected)?' selected':'')+'>'+result[i]+'</option>';
		}
		target.html(options);
		parent.removeClass('hidden');
		target.next('.loader').addClass('hidden');
		target.removeClass('hidden');
		parent.next('.note').addClass('hidden');
	},

	process_account = function(arguments){
		var	result = (typeof(arguments['result']) == 'object') ? arguments['result'] : JSON.parse(arguments['result']),
			id = result.id,
			_this = arguments._this,
			status = result.status,
			currency = result.currency,
			container = _this.closest('.has-feedback'),
			pack = _this.closest('.package-container'),
			prod_container = pack.find('.product'),
			subscr_container = pack.find('.subscription'),
			rep_container = pack.find('.representative'),
			buttons = [$('#submit-button'),$('#logout'),$('#add_package_button button')],
			containers = [prod_container,subscr_container,rep_container];
			icons = {error:container.find('.error-sign'),success:container.find('.success-sign')};
		container.addClass('has-'+status);
		for (var i in icons)
		{
			if(i == status)
			{
				icons[i].removeClass('hidden');
			}
			else
			{
				icons[i].addClass('hidden');
			}
		}
		container.find('.loader').addClass('hidden');
		if(status == 'error')
		{
			_this.val('');
			_this.attr('placeholder',JS_NO_TEMPLATE);
		}
		else
		{
			var args = [
				{ act:'GetProducts', result:products },
				{ act:'GetSubscriptions', result:subscriptions },
				{ act:'GetRepresentatives', result:representatives }
			];
			for(var i in args)
			{
				process_response({
					packs:[
						{ pack:pack, currency:currency }
					],
					act:args[i]['act'],
					result:args[i]['result']
				});
			}
		}
		_this.prop('disabled',false);
		checked_accounts[id] = {status:status,currency:currency};
		_this.prev('.template-currency').val(currency);
		for (var i in buttons)
		{
			buttons[i].prop('disabled',false);
		}
	},

	initialize_packages = function(){
		$('.remove-button').each(function(){
			$(this).unbind();
			$(this).click(function(){
				var pack = $('#'+$(this).attr('data-package')),
					pack_id = $(this).attr('data-package'),
					pack_total = $('.package-container').length;
				if(pack_total > 1)
				{
					pack.remove();
					$('#add_package_button a[data-target='+pack_id+']').parent('li').remove();
					pack_total = pack_total - 1;
				}
				if (pack_total == 1)
				{
					$('.remove-button').addClass('hidden');
				}
			});
		});
		$('.id_source').each(function(){
			$(this).unbind();
			$(this).change(function(){id_source_additional($(this).attr('data-package'));});
			id_source_additional($(this).attr('data-package'));
		});
		$('.add-package').each(function(){
			$(this).unbind('click');
			$(this).click(function(){
				add_package($('#'+$(this).attr('data-target')));
			});
		});
		$('.accountid-field').focusout(function(){
			var id = $(this).val(),
				container = $(this).closest('.has-feedback'),
				pack = $(this).closest('.package-container'),
				prod_container = pack.find('.product'),
				subscr_container = pack.find('.subscription'),
				buttons = [$('#submit-button'),$('#logout'),$('#add_package_button button')],
				containers = [prod_container,subscr_container];
			container.removeClass('has-error').removeClass('has-success');
			container.find('.error-sign,.success-sign').addClass('hidden');
			for(var i in containers)
			{
				containers[i].find('.select-container').addClass('hidden');
				containers[i].find('.note').removeClass('hidden');
			}
			for (var i in buttons)
			{
				buttons[i].prop('disabled',true);
			}
			$(this).attr('placeholder','');
			if(id == '')
			{
				return;
			}
			container.find('.loader').removeClass('hidden');
			$(this).prop('disabled',true);
			if(typeof(checked_accounts[id]) == 'undefined')
			{
				ajax_call({account_id:id,act:'CheckAccount'},'process_account',{_this:$(this)});
				return;
			}
			var arguments = {
					_this:$(this),
					result:{id:id,
							status:checked_accounts[id]['status'],
							currency:checked_accounts[id]['currency']}
				};
			process_account(arguments);
		});
		$('span.params').click(function(){
			var advanced_container = $(this).parent().find('.optional-params');
			if($(this).hasClass('show-optional'))
			{
				advanced_container.removeClass('hidden');
				$(this).addClass('hidden');
				$(this).next('.hide-optional').removeClass('hidden');
			}
			else if($(this).hasClass('hide-optional'))
			{
				advanced_container.addClass('hidden');
				$(this).addClass('hidden');
				$(this).prev('.show-optional').removeClass('hidden');
			}
		});
	};

function id_source_additional(pack)
{
	var id_source = $('#'+pack+'id_source').val();
	switch (id_source)
	{
		case 'DID':
			$('#'+pack+'did_api_countries_tr').addClass('hidden');
			$('#'+pack+'did_api_countries').prop('disabled',true);
			$('#'+pack+'did_api_countries_tr').find('button.ms-choice').addClass('disabled');
			$('#'+pack+'batch_list').prop('disabled',false);
			$('#'+pack+'batch_list').addClass('mand');
			$('#'+pack+'id_length').prop('disabled',false);
			$('#'+pack+'subscriber-prefix').prop('disabled',true);
			$('#'+pack+'val-alias_prefix_on').prop('disabled',true);
			$('#'+pack+'val-did_split_on').prop('disabled',false);
			$('#'+pack+'dids-by-area-code').removeClass('hidden');
			$('#'+pack+'batch_list').addClass('mand');
			$('#'+pack+'id_length').removeClass('mand');
			$('#'+pack+'did-masking').removeClass('hidden');
			$('#'+pack+'alias_prefix').addClass('hidden');
			if (adv_view)
			{
				$('#'+pack+'limit').removeClass('hidden');
				$('#'+pack+'offset').removeClass('hidden');
			}
			if(!($('#'+pack+"val-owner").prop("disabled")) && $('#'+pack+"owner").attr("data-virtoffice") === "true")
			{
				$('#'+pack+'virtoffice').removeClass('hidden');
				if(!($('#'+pack+"virtoffice-val").prop("disabled"))) { $('#'+pack+'virtoffice_desc').removeClass('hidden'); }
				else { $('#'+pack+'virtoffice_desc').addClass('hidden'); }
			}
			$('#'+pack+'did_batch').removeClass('hidden');
			$('#'+pack+'pref_tr').addClass('hidden');
			$('#'+pack+'id_length_tr').addClass('hidden');
			break;
		case 'DID_API':
			$('#'+pack+'batch_list').prop('disabled',true);
			$('#'+pack+'batch_list').removeClass('mand');
			$('#'+pack+'subscriber-prefix').prop('disabled',true);
			$('#'+pack+'val-alias_prefix_on').prop('disabled',true);
			$('#'+pack+'val-did_split_on').prop('disabled',true);
			$('#'+pack+'dids-by-area-code').addClass('hidden');
			$('#'+pack+'batch_list').removeClass('mand');
			$('#'+pack+'did_batch').addClass('hidden');
			$('#'+pack+'pref_tr').addClass('hidden');
			$('#'+pack+'alias_prefix').addClass('hidden');
			$('#'+pack+'alias_prefix_on').prop('disabled',true);
			$('#'+pack+'did-masking').addClass('hidden');
			if (adv_view)
			{
				$('#'+pack+'limit').addClass('hidden');
				$('#'+pack+'limit').find('input').val(50);
				$('#'+pack+'offset').addClass('hidden');
				$('#'+pack+'offset').find('input').val(0);
			}
			if(!($('#'+pack+"val-owner").prop("disabled")) && $('#'+pack+"owner").attr("data-virtoffice") === "true")
			{
				$('#'+pack+'virtoffice').removeClass('hidden');
				if(!($('#'+pack+"virtoffice-val").prop("disabled"))) { $('#'+pack+'virtoffice_desc').removeClass('hidden'); }
				else { $('#'+pack+'virtoffice_desc').addClass('hidden'); }
			}
			$('#'+pack+'id_length').prop('disabled',true);
			$('#'+pack+'id_length').removeClass('mand');
			$('#'+pack+'id_length_tr').addClass('hidden');
			$('#'+pack+'did_api_countries_tr').removeClass('hidden');
			$('#'+pack+'did_api_countries').prop('disabled',false);

			create_multiple_select($('#'+pack+'did_api_countries'));

			$('#'+pack+'did_api_countries_tr').find('button.ms-choice').removeClass('disabled');
			break;
		case 'rand':
			hide_dids(adv_view,pack);
			$('#'+pack+'virtoffice').addClass('hidden');
			$('#'+pack+'virtoffice_desc').addClass('hidden');
			$('#'+pack+'did_api_countries_tr').addClass('hidden');
			$('#'+pack+'did_api_countries').prop('disabled',true);
			$('#'+pack+'did_api_countries_tr').find('button.ms-choice').addClass('disabled');
			$('#'+pack+'id_length').prop('disabled',false);
			$('#'+pack+'id_length').addClass('mand');
			$('#'+pack+'id_length_tr').removeClass('hidden');
			break;
		case 'man':
			hide_dids(adv_view,pack);
			if(!($('#'+pack+"val-owner").prop("disabled")) && $('#'+pack+"owner").attr("data-virtoffice") === "true")
			{
				$('#'+pack+'virtoffice').removeClass('hidden');
				if(!($('#'+pack+"virtoffice-val").prop("disabled"))) { $('#'+pack+'virtoffice_desc').removeClass('hidden'); }
				else { $('#'+pack+'virtoffice_desc').addClass('hidden'); }
			}
			$('#'+pack+'did_api_countries_tr').addClass('hidden');
			$('#'+pack+'did_api_countries').prop('disabled',true);
			$('#'+pack+'did_api_countries_tr').find('button.ms-choice').addClass('disabled');
			$('#'+pack+'id_length').prop('disabled',true);
			$('#'+pack+'id_length_tr').addClass('hidden');
			$('#'+pack+'id_length').removeClass('mand');
			break;
		case 'none_field':
			hide_dids(adv_view,pack);
			$('#'+pack+'did_api_countries_tr').addClass('hidden');
			$('#'+pack+'did_api_countries').prop('disabled',true);
			$('#'+pack+'did_api_countries_tr').find('button.ms-choice').addClass('disabled');
			$('#'+pack+'id_length').prop('disabled',false);
			$('#'+pack+'id_length').removeClass('mand');
			$('#'+pack+'id_length_tr').addClass('hidden');
			$('#'+pack+'alias_prefix').addClass('hidden');
			$('#'+pack+'val-alias_prefix').prop('disabled',false);
			$('#'+pack+'alias_prefix').prop('disabled',false);
			$('#'+pack+'pref_tr').addClass('hidden');
			$('#'+pack+'subscriber-alias').addClass('hidden');
			$('#'+pack+'subscriber-alias_tr').addClass('hidden');
			break;

			break;
	}

	// Check representatives
	var action = (!($('#'+pack+"val-owner").prop("disabled"))) ? 'removeClass' : 'addClass';
	$('#'+pack+'i_rep')[action]('hidden');
}

function hide_dids(adv_view,pack)
{
	$('#'+pack+'batch_list').prop('disabled',true);
	$('#'+pack+'batch_list').removeClass('mand');
	$('#'+pack+'subscriber-prefix').prop('disabled',false);
	$('#'+pack+'val-alias_prefix_on').prop('disabled',false);
	$('#'+pack+'val-did_split_on').prop('disabled',true);
	$('#'+pack+'dids-by-area-code').addClass('hidden');
	$('#'+pack+'batch_list').removeClass('mand');
	$('#'+pack+'did_batch').addClass('hidden');
	$('#'+pack+'pref_tr').removeClass('hidden');
	$('#'+pack+'alias_prefix').removeClass('hidden');
	$('#'+pack+'alias_prefix_on').prop('disabled',false);
	$('#'+pack+'did-masking').addClass('hidden');
	if (adv_view)
	{
		$('#'+pack+'limit').addClass('hidden');
		$('#'+pack+'limit').find('input').val(50);
		$('#'+pack+'offset').addClass('hidden');
		$('#'+pack+'offset').find('input').val(0);
	}
}

// source refers to the 'Add package' source: 
// either 'Create from scratch' (data-target="0")
// or 'Clone package 1', etc. (data-target="packDiv0", data-target="packDiv1")
function add_package(source)
{
	var pack_ids = [],
		from_scratch = (source.length > 0) ? false : true,
		last_pack_id = 0;
		
	$('.package-container').each(function(){
		pack_ids.push(parseInt($(this).attr('id').replace('packDiv','')));
	});
	for (var j in pack_ids)
	{
	    if (pack_ids[j] > last_pack_id)
	    {
	    	last_pack_id = pack_ids[j];
	    }
	}
	new_pack = (from_scratch) ? $('#packDiv'+last_pack_id).clone() : source.clone();
	var id = last_pack_id + 1,
		id_old = (!from_scratch) ? parseInt(source.attr('id').replace('packDiv','')) : last_pack_id;
	new_pack.attr('id', 'packDiv' + id);
	new_pack.find('h4').html(JS_PACKAGE+' '+(id + 1)+' <span class="btn btn-default btn-sm glyphicon glyphicon-trash remove-button" data-package="packDiv'+id+'"></span>');
	new_pack.find('.optional-params').removeClass('hidden');
	new_pack.find('.show-optional').addClass('hidden');
	new_pack.find('.hide-optional').removeClass('hidden');
	new_pack.find('select.didapi_countries').next('.ms-parent').remove(); // remove excessive multiple select
	var feedback = new_pack.find('.has-feedback');
	feedback.removeClass('has-success').removeClass('has-error');
	feedback.find('.error-sign, .success-sign, .loader').addClass('hidden');
	if(from_scratch)
	{
		new_pack.find('.select-container').each(function(){
			$(this).addClass('hidden');
			$(this).find('.selected-option').val('');
			$(this).find('select').addClass('hidden');
			$(this).find('.loader').removeClass('hidden');
			$(this).next('.note').removeClass('hidden');
		});
	}
	new_pack.find('input, select, label, div, textarea').each(function(){
		var found_element = this;
		if(from_scratch)
		{
			if($(found_element).hasClass('to-clear'))
			{
				if($(found_element).hasClass('id_source'))
				{
					$(found_element).val('DID');
				}
				else
				{
					$(found_element).val('');
				}
			}
			if ($(found_element).hasClass('custom_switcher'))
			{
				var parent = $(found_element).parent();
				if(parent.hasClass('switch-on'))
				{
					parent.removeClass('switch-on')
						  .addClass('switch-off');
					$(found_element).prop('checked', false);
				}
			}
		}
		else if($(found_element).attr('name') !== undefined)
		{
			$(found_element).val($('#'+($(found_element).attr('id'))).val());
		}
		var attributes = ['id','name','for','data-package','data-search','data-callback','data-target'];
		for (var j in attributes)
		{
			if ($(found_element).attr(attributes[j]) !== undefined)
			{
				var regexp = new RegExp('\\-'+id_old+'\\-', "gi");
				$(found_element).attr(attributes[j], $(found_element).attr(attributes[j]).replace(regexp,'-'+id+'-').replace('['+id_old+']','['+id+']'));
			}
		}
	});
	new_pack.insertBefore('#add_package_button');
	$('#add_package_button').prev().find('.make-switch').each(function(){
		activate_checkbox($(this).find('.custom_switcher'));
		$(this).click(function(){
			new_pack_switch($(this).find('.custom_switcher'));
		})
	});
	$(".remove-button").each(function(){$(this).removeClass('hidden')});
	var a = $('<a/>').attr('href','javascript:void(0);').addClass('add-package').attr('data-target','packDiv'+id).text(JS_CLONE_PACK+' '+(id+1)),
		li = $('<li/>').append(a);
	li.insertBefore('#add_package_button li.divider');
	initialize_packages();
}

function new_pack_switch(el)
{
	var parent = el.parent();
	if(parent.hasClass('switch-on'))
	{
		parent.removeClass('switch-on')
			  .addClass('switch-off');
		el.prop('checked', false);
	}
	else
	{
		parent.removeClass('switch-off')
		      .addClass('switch-on');
		el.prop('checked', true);
	}
	activate_checkbox(el);
}

function activate_checkbox(el)
{
	var targets = JSON.parse(el.attr('data-target')),
		callback = el.attr('data-callback') ? el.attr('data-callback') : false;

	for (var i in targets)
	{
		var target = $('#'+targets[i]),
			show = (target.attr('data-show')) ? true : false,
			search = $('#'+target.attr('data-search'));
		if(el.prop('checked'))
		{
			if(show)
			{
				target.removeClass('hidden');
			}
			search.prop('disabled', false);
			search.addClass('mand');
		}
		else
		{
			target.addClass('hidden');
			search.prop('disabled', true);
			search.removeClass('mand');
		}
	}
	if(callback) eval(callback);
}

function submit_form(act)
{
	if (act)
	{
		$('#act').val(act);
	}
	$('#main').addClass('hidden');
	$('#preloader').removeClass('hidden');
	$('#wizard_form').submit();
	if($('#act').val() == 'save')
	{
		$('#status').removeClass('hidden');
		setTimeout(GetProgress,1500);
	}
	else
	{
		$('#progress').removeClass('hidden');
	}
}

function GetProgress()
{
    var url = window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+$('#wizard_token').val()+'.json',
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

function create_multiple_select(select)
{
	select.multipleSelect({
			filter:true,
			width:'350',
			selectAllText:JS_SELECT_ALL,
			allSelected:JS_ALL_SELECTED,
			countSelected:JS_SELECTED,
			noMatchesFound:JS_NO_MATCHES
	});
}

$(document).ready(function() {
	if($('.editor').length > 0)
	{
		$('.editor').wysihtml5();
	}
	if($('select.didapi_countries').length > 0)
	{
		create_multiple_select($('select.didapi_countries'));
	}
	$('#mylist').children('li').children('a').bind('click', function(event) {
		event.preventDefault();
		if ($(this).attr('href') != $('#mylist').children('.active').children('a').attr('href')) {
			$($('#mylist').children('.active').children('a').attr('href')).addClass('hidden');
			$('#mylist').children('.active').removeClass('active');
			$(this).parent('li').addClass('active');
			$($(this).attr('href')).removeClass('hidden');
		};
	});
        $('.login_form input').keypress(function(e) {
                if(e.which == 13) {
                  jQuery(this).blur();
                  jQuery('#submit-button').focus().click();
          }
        });
	$('#logout').click(function(){
		if(confirm(JS_SURE))
		{
			submit_form('logout');
		}
	});
	$('.custom_switcher').each(function(){
		if(this.checked) {
			activate_checkbox($(this));
		}
		$(this).change(function(){activate_checkbox($(this));});
	});
	$('#switch-view').change(function(){
		var el = $(this),
			url = window.location.protocol + '//' + window.location.hostname+(window.location.port ? ':'+window.location.port : '')+root_path+'?lang='+lang+'&layout=wizard';
		setTimeout(function(){
			if(el.prop('checked'))
			{
				window.location.href = url+'&advanced=true';
			}
			else
			{
				window.location.href = url;
			}
		},400);
	});
	initialize_packages();
});
