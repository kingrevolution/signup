	function process_items(target_class, func, callback, argument) {
		if (func == 'is_empty_field') {
			var validate = [];
			if (signupwizard) { var tabs_ids = []; }
		}
		$('div.to-validate').each(function() {
			if (signupwizard || !($(this).attr('class').match(/hidden/g))) {
				var targets = "input."+target_class+", textarea."+target_class+", select."+target_class;
				if (func == 'is_empty_field' && signupwizard) {
					var div_id = $(this).attr('id');
					targets += ", input.email";
				}
				var fn = window[func];
				$(this).find(targets).each(function() {
					if (func == 'is_empty_field') {
						if (signupwizard && $(this).attr('class').match(/email/g)) {
							if ($(this).val() != '') {
								var output = fn($(this))
								validate.push(output);
							}
						}
						else {
							var output = fn($(this))
							validate.push(output);
						}
						if (output == false && signupwizard) {
							var match = div_id.match(/\d+/g);
							var li_id = '#li'+match;
							if (!in_array(li_id,tabs_ids)) {
								tabs_ids.push(li_id);
							}
						}
					}
					else { fn($(this)); }
				});
			}
		});
		if (func == 'is_empty_field') {
			if (!in_array(false,validate)) {
				fn = window[callback];
				fn(argument);
				if (!signupwizard) { process_items('check', 'is_correct_field'); }
			}
			else if (signupwizard) {
				for (var i in tabs_ids) {
					if (!($(tabs_ids[i]).attr('class'))) {
						$(tabs_ids[i]).attr('style', "background-color: #F2DEDE; border: 0.5px solid #c0c0c0; border-top-left-radius:5px; border-top-right-radius:5px; border-bottom:none; color: #B94A48;");
					}
				}
				if(tabs_ids.length > 0)
					$(tabs_ids[0]).find("a").focus();
				setTimeout(function() {
					for (var i in tabs_ids) {
						$(tabs_ids[i]).removeAttr("style");
					}
				}, 2500);
			}
		}
	}

	function is_empty_field(el) {
		var validate = true;
		if(!el.prop('disabled'))
		{
			if (el.prop("tagName") == 'SELECT')
			{
				 if (el.find( "option:selected" ).val() == '') {
					validate = false;
					validator_baloon(el,'select');
				 } else if (el.attr("id") == 'cc_month' || el.attr("id") == 'cc_year') {
					 if (el.attr("id") == 'cc_month') return;
					 var year = $(cc_year).val();
					 var month = $(cc_month).val();
					 var cc_date = validate_cc_date(year,month);
					 if (cc_date === false) {
						 validate = false;
						 validator_baloon(el,'cc_date');
					 }
				 }
			}
			else if('checkbox' == el.attr('type')) {
				if (!(el.prop('checked'))) {
					validate = false;
					validator_baloon(el,'checkbox');
				}
			}
			else if (el.attr("id") == 'email' || el.attr("class").match(/email/g)) {
				var email = validate_email(el);
				if (email === false) {
					validate = false;
					validator_baloon(el,'email');
				}
			}
			else {
				if (el.val() == '') {
					validate = false;
					validator_baloon(el);
				}
			}
		}
		return validate;
	}

	function is_correct_field(el) {
		var el = el || null;
		if (el == null) return;
		var el_class = el.attr('class');
		var match = el_class.match(/email/g);
		if (!(el_class.match(/email/g))) {
			el.bind('input propertychange', function() {
				var type = 'other';
				var pattern = '[^';
				if (el_class.match(/letters/g)) {
					pattern += "a-zA-zА-Яа-яàâçéèêëîïôûùüÿñæœ";
				}
				if (el_class.match(/digits/g)) {
					pattern += "\\d";
				}
				if (el_class.match(/float/g)) {
					pattern += "\\d\\.";
				}
				if (el_class.match(/dashes/g)) {
					pattern += "\\-";
				}
				if (el_class.match(/spaces/g)) {
					pattern += '\\s';
				}
				if (el_class.match(/punctuation/g)) {
					pattern += "\\,\\.\\'";
				}
				pattern += ']';
				var regexp = new RegExp(pattern, "g");
				var validate = regexp.test(el.val());
				if (validate !== false) {
					el.val(el.val().replace(regexp,""));
					validator_baloon(el,type);
				}
			});
		}
		else {
			el.focusout(function() {
				var el = $(this);
				if (el.val() != '') {
					var validate = validate_email(el);
					if (validate === false) { validator_baloon(el,'email'); }
				}
			})
		}
	}

	function validate_email(el) {
		var regexp = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		var validate = regexp.test(el.val());
		if (signupwizard) return validate;
		else return validate && validate_email_domain(el.val());
	}
	
	function validate_cc_date(year, month) {
		var currentDate = new Date();
		var year = year;
		var month = month;
		var dateToCheck = new Date(year, month, 0, 0, 0, 0, 0);
		if (dateToCheck.getTime() < currentDate.getTime()) {
			return false;
		} else {
			return true;
		}
	}

	//email_domain_validation
	function validate_email_domain(email){
		if (valid_email_domains == "") return true;
		var domains = valid_email_domains.split(', ');
		var a_poz = email.indexOf("@");
		if(a_poz == -1) return false;
		var email_domain = email.substring(a_poz+1);
		for (var i = 0; i < domains.length; i++) {
			if(domains[i] == email_domain) return true;
		}
		return false;
	}

	function validator_baloon(el,type) {console.log(el.attr('id'));
		var show_popover = (window.innerWidth >= 1000) ? true : false;
		if (el.is(":visible")) {
			type = type || null;
			var placement = 'right';
			var custom_style = '';
			switch (type) {
				case 'cc_date':
					var text = JS_ENTER_VALID_CC_DATE;
					var width = 250;
					break;
				case 'email':
					var text = JS_ENTER_VALID_EMAIL;
					var width = 240;
					break;
				case 'checkbox':
					var text = JS_MAND_CHECKBOX;
					var width = 230;
					placement = 'left';
					custom_style += ' margin-left:-30px;';
					break;
				case 'select':
					var text = JS_SELECT_ITEM;
					var width = 170;
					break;
				case 'captcha':
					var text = JS_INCORRECT_CAPTCHA;
					var width = 250;
					break;
				case 'data':
					var text = JS_INVALID_DATA;
					var width = 250;
					break;
				case 'other':
					var text = JS_INVALID_CHARACTER;
					var width = 205;
					break;
				default:
					var label = (el.data('label')) ? el.data('label') : 'This';
					var text = "<b>"+label+"</b> "+JS_MAND_FIELD;
					var width = text.length*6.5;
			}
			var showPopover = function () {
				if (el.attr('id') === 'cc_year') {
					var month = $(cc_month);
					month.popover('show');
					month.attr("style","border-color:red; box-shadow:inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(233, 133, 102, 0.6)")
				}
			    el.popover('show');
			    el.attr("style","border-color:red; box-shadow:inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(233, 133, 102, 0.6)")
			}
			, hidePopover = function () {
				if (el.attr('id') === 'cc_year') {
					var month = $(cc_month);
					month.popover('destroy');
					month.removeAttr("style");
				}
			    el.popover('destroy');
			    el.removeAttr("style");
			};
			if (show_popover)
			{
				el.popover({ content: text, trigger: 'manual', html: true, placement: placement });
			}
			showPopover();

			if(show_popover)
			{
				var popover = el.parent().find("div.popover");
				var style = popover.attr('style');
				custom_style += ' text-align:center; width:'+width+'px;';
				popover.attr('style', style + custom_style);
				popover.find("div.arrow").attr('style','top:50%');
			}
			setTimeout(hidePopover, 2500);
		}
	}

	function in_array(needle, haystack, strict) {
		var found = false,
		key,
		strict = !!strict;

		for (key in haystack) {
			if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
				found = true;
				break;
			}
		 }

		return found;
	}

	function step_validator() {
		if (typeof(trigger) == 'object') {
			for (var i in trigger) {
				$('#'+i).click(function() {
					var id = $(this).attr('id');
					process_items('mand', 'is_empty_field', trigger[id]['callback'], trigger[id]['argument']);
				});
			}
		}
	}

	function fields_validator() {
		process_items('check', 'is_correct_field');
	}

	$(document).ready(function(){
		fields_validator();
		step_validator();
	})

	var trigger = new Object,
		url = window.location.href;
	if (url.indexOf('layout=wizard') == -1)
	{
		var mand_matches = new Object,
			signupwizard = false;
		trigger['next-button'] = new Object;
		trigger['finish-button'] = new Object;
		trigger['next-button']['callback'] = 'changeStep';
		trigger['next-button']['argument'] = 'next';
		trigger['finish-button']['callback'] = 'submitInformation';
		trigger['finish-button']['argument'] = '';
	}
	else
	{
		var signupwizard = ($('#wizard_form').attr('name') == 'main') ? true : false;
		trigger['submit-button'] = new Object;
		trigger['submit-button']['callback'] = 'submit_form';
		trigger['submit-button']['argument'] = '';
	}
