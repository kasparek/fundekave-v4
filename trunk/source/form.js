/* formToArray */
;
(function($){
	$.fn.formToArray = function(semantic){
		var a = [];
		if(this.length == 0)
			return a;
		var form = this[0];
		var els = semantic ? form.getElementsByTagName('*') : form.elements;
		if(!els)
			return a;
		for( var i = 0, max = els.length; i < max; i++){
			var el = els[i];
			var n = el.name;
			if(!n)
				continue;
			if(semantic && form.clk && el.type == "image"){
				if(!el.disabled && form.clk == el){
					a.push({
						name : n,
						value : $(el).val()
					});
					a.push({
						name : n + '.x',
						value : form.clk_x
					}, {
						name : n + '.y',
						value : form.clk_y
					})
				}
				continue
			}
			var v = $.fieldValue(el, true);
			if(v && v.constructor == Array){
				for( var j = 0, jmax = v.length; j < jmax; j++)
					a.push({
						name : n,
						value : v[j]
					})
			}else if(v !== null && typeof v != 'undefined')
				a.push({
					name : n,
					value : v
				})
		}
		if(!semantic && form.clk){
			var$input = $(form.clk), input = $input[0], n = input.name;
			if(n && !input.disabled && input.type == 'image'){
				a.push({
					name : n,
					value : $input.val()
				});
				a.push({
					name : n + '.x',
					value : form.clk_x
				}, {
					name : n + '.y',
					value : form.clk_y
				})
			}
		}
		return a
	};
	$.fn.fieldValue = function(successful){
		for( var val = [], i = 0, max = this.length; i < max; i++){
			var el = this[i];
			var v = $.fieldValue(el, successful);
			if(v === null || typeof v == 'undefined' || (v.constructor == Array && !v.length))
				continue;
			v.constructor == Array ? $.merge(val, v) : val.push(v)
		}
		return val
	};
	$.fieldValue = function(el, successful){
		var n = el.name, t = el.type, tag = el.tagName.toLowerCase();
		if(typeof successful == 'undefined')
			successful = true;
		if(successful && (!n || el.disabled || t == 'reset' || t == 'button' || (t == 'checkbox' || t == 'radio') && !el.checked || (t == 'submit' || t == 'image') && el.form && el.form.clk != el || tag == 'select' && el.selectedIndex == -1))
			return null;
		if(tag == 'select'){
			var index = el.selectedIndex;
			if(index < 0)
				return null;
			var a = [], ops = el.options;
			var one = (t == 'select-one');
			var max = (one ? index + 1 : ops.length);
			for( var i = (one ? index : 0); i < max; i++){
				var op = ops[i];
				if(op.selected){
					var v = op.value;
					if(!v)
						v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
					if(one)
						return v;
					a.push(v)
				}
			}
			return a
		}
		return el.value
	};
	$.fn.clearForm = function(){
		return this.each(function(){
			$('input,select,textarea', this).clearFields()
		})
	};
	$.fn.clearFields = $.fn.clearInputs = function(){
		return this.each(function(){
			var t = this.type, tag = this.tagName.toLowerCase();
			if(t == 'text' || t == 'password' || tag == 'textarea')
				this.value = '';
			else if(t == 'checkbox' || t == 'radio')
				this.checked = false;
			else if(tag == 'select')
				this.selectedIndex = -1
		})
	};
	$.fn.resetForm = function(){
		return this.each(function(){
			if(typeof this.reset == 'function' || (typeof this.reset == 'object' && !this.reset.nodeType))
				this.reset()
		})
	};
	$.fn.enable = function(b){
		if(b == undefined)
			b = true;
		return this.each(function(){
			this.disabled = !b
		})
	};
	$.fn.selected = function(select){
		if(select == undefined)
			select = true;
		return this.each(function(){
			var t = this.type;
			if(t == 'checkbox' || t == 'radio')
				this.checked = select;
			else if(this.tagName.toLowerCase() == 'option'){
				var$sel = $(this).parent('select');
				if(select && $sel[0] && $sel[0].type == 'select-one'){
					$sel.find('option').selected(false)
				}
				this.selected = select
			}
		})
	}
})(jQuery);