/*
 * Auto Expanding Text Area (1.2.2)
 * by Chrys Bader (www.chrysbader.com)
 * chrysb@gmail.com
 *
 * Special thanks to:
 * Jake Chapa - jake@hybridstudio.com
 * John Resig - jeresig@gmail.com
 *
 * Copyright (c) 2008 Chrys Bader (www.chrysbader.com)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 *
 * NOTE: This script requires jQuery to work.  Download jQuery at www.jquery.com
 *
 */

(function(jQuery){var self=null;jQuery.fn.autogrow=function(o)
{return this.each(function(){new jQuery.autogrow(this,o);});};jQuery.autogrow=function(e,o)
{this.options=o||{};this.dummy=null;this.interval=null;this.line_height=this.options.lineHeight||parseInt(jQuery(e).css('line-height'));this.min_height=this.options.minHeight||parseInt(jQuery(e).css('min-height'));this.max_height=this.options.maxHeight||parseInt(jQuery(e).css('max-height'));;this.textarea=jQuery(e);if(this.line_height==NaN)
this.line_height=0;this.init();};jQuery.autogrow.fn=jQuery.autogrow.prototype={autogrow:'1.2.2'};jQuery.autogrow.fn.extend=jQuery.autogrow.extend=jQuery.extend;jQuery.autogrow.fn.extend({init:function(){var self=this;this.textarea.css({overflow:'hidden',display:'block'});this.textarea.bind('focus',function(){self.startExpand()}).bind('blur',function(){self.stopExpand()});this.checkExpand();},startExpand:function(){var self=this;this.interval=window.setInterval(function(){self.checkExpand()},400);},stopExpand:function(){clearInterval(this.interval);},checkExpand:function(){if(this.dummy==null)
{this.dummy=jQuery('<div></div>');this.dummy.css({'font-size':this.textarea.css('font-size'),'font-family':this.textarea.css('font-family'),'width':this.textarea.css('width'),'padding':this.textarea.css('padding'),'line-height':this.line_height+'px','overflow-x':'hidden','position':'absolute','top':0,'left':-9999}).appendTo('body');}
var html=this.textarea.val().replace(/(<|>)/g,'');if($.browser.msie)
{html=html.replace(/\n/g,'<BR>new');}
else
{html=html.replace(/\n/g,'<br>new');}
if(this.dummy.html()!=html)
{this.dummy.html(html);if(this.max_height>0&&(this.dummy.height()+this.line_height>this.max_height))
{this.textarea.css('overflow-y','auto');}
else
{this.textarea.css('overflow-y','hidden');if(this.textarea.height()<this.dummy.height()+this.line_height||(this.dummy.height()<this.textarea.height()))
{this.textarea.animate({height:(this.dummy.height()+this.line_height)+'px'},100);}}}}});})(jQuery);


/**
 * Expands text and textarea elements while new characters are typed to the a miximum width
 *
 * @name Expander
 * @description Expands text and textarea elements while new characters are typed to the a miximum width
 * @param Mixed limit integer if only expands in width, array if expands in width and height
 * @type jQuery
 * @cat Plugins/Interface
 * @author Stefan Petre
 */
jQuery.iExpander={helper:null,limit:null,expand:function()
{text=this.value;if(!text)
return;style={fontFamily:jQuery(this).css('fontFamily')||'',fontSize:jQuery(this).css('fontSize')||'',fontWeight:jQuery(this).css('fontWeight')||'',fontStyle:jQuery(this).css('fontStyle')||'',fontStretch:jQuery(this).css('fontStretch')||'',fontVariant:jQuery(this).css('fontVariant')||'',letterSpacing:jQuery(this).css('letterSpacing')||'',wordSpacing:jQuery(this).css('wordSpacing')||''};jQuery.iExpander.helper.css(style);html=jQuery.iExpander.htmlEntities(text);html=html.replace(new RegExp("\\n","g"),"<br />");jQuery.iExpander.helper.html('pW');spacer=jQuery.iExpander.helper.get(0).offsetWidth;jQuery.iExpander.helper.html(html);width=jQuery.iExpander.helper.get(0).offsetWidth+spacer;if(jQuery.iExpander.limit&&width>jQuery.iExpander.limit[0]){width=jQuery.iExpander.limit[0];}
this.style.width=width+'px';if(this.tagName=='TEXTAREA'){height=jQuery.iExpander.helper.get(0).offsetHeight+spacer;if(jQuery.iExpander.limit&&height>jQuery.iExpander.limit[1]){height=jQuery.iExpander.limit[1];}
this.style.height=height+'px';}},htmlEntities:function(text)
{entities={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'};for(i in entities){text=text.replace(new RegExp(i,'g'),entities[i]);}
return text;},build:function(limit)
{if(jQuery.iExpander.helper==null){jQuery('body',document).append('<div id="expanderHelper" style="position: absolute; top: 0; left: 0; visibility: hidden;"></div>');jQuery.iExpander.helper=jQuery('#expanderHelper');}
return this.each(function()
{if(/TEXTAREA|INPUT/.test(this.tagName)){if(this.tagName=='INPUT'){elType=this.getAttribute('type');if(!/text|password/.test(elType)){return;}}
if(limit&&(limit.constructor==Number||(limit.constructor==Array&&limit.length==2))){if(limit.constructor==Number)
limit=[limit,limit];else{limit[0]=parseInt(limit[0])||400;limit[1]=parseInt(limit[1])||400;}
jQuery.iExpander.limit=limit;}
jQuery(this).blur(jQuery.iExpander.expand).keyup(jQuery.iExpander.expand).keypress(jQuery.iExpander.expand);jQuery.iExpander.expand.apply(this);}});}};jQuery.fn.Autoexpand=jQuery.iExpander.build;


/*
 * jQuery ifixpng plugin
 * (previously known as pngfix)
 * Version 2.1  (23/04/2008)
 * @requires jQuery v1.1.3 or above
 *
 * Examples at: http://jquery.khurshid.com
 * Copyright (c) 2007 Kush M.
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */
(function($){$.ifixpng=function(customPixel){$.ifixpng.pixel=customPixel;};$.ifixpng.getPixel=function(){return $.ifixpng.pixel||'images/pixel.gif';};var hack={ltie7:$.browser.msie&&$.browser.version<7,filter:function(src){return"progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,sizingMethod=crop,src='"+src+"')";}};$.fn.ifixpng=hack.ltie7?function(){return this.each(function(){var $$=$(this);var base=$('base').attr('href');if(base){base=base.replace(/\/[^\/]+$/,'/');}if($$.is('img')||$$.is('input')){if($$.attr('src')){if($$.attr('src').match(/.*\.png([?].*)?$/i)){var source=(base&&$$.attr('src').search(/^(\/|http:)/i))?base+$$.attr('src'):$$.attr('src');$$.css({filter:hack.filter(source),width:$$.width(),height:$$.height()}).attr({src:$.ifixpng.getPixel()}).positionFix();}}}else{var image=$$.css('backgroundImage');if(image.match(/^url\(["']?(.*\.png([?].*)?)["']?\)$/i)){image=RegExp.$1;image=(base&&image.substring(0,1)!='/')?base+image:image;$$.css({backgroundImage:'none',filter:hack.filter(image)}).children().children().positionFix();}}});}:function(){return this;};$.fn.iunfixpng=hack.ltie7?function(){return this.each(function(){var $$=$(this);var src=$$.css('filter');if(src.match(/src=["']?(.*\.png([?].*)?)["']?/i)){src=RegExp.$1;if($$.is('img')||$$.is('input')){$$.attr({src:src}).css({filter:''});}else{$$.css({filter:'',background:'url('+src+')'});}}});}:function(){return this;};$.fn.positionFix=function(){return this.each(function(){var $$=$(this);var position=$$.css('position');if(position!='absolute'&&position!='relative'){$$.css({position:'relative'});}});};})(jQuery);(function($){$.fn.ajaxSubmit=function(options){if(!this.length){log('ajaxSubmit: skipping submit process - no element selected');return this;}if(typeof options=='function')options={success:options};options=$.extend({url:this.attr('action')||window.location.toString(),type:this.attr('method')||'GET'},options||{});var veto={};this.trigger('form-pre-serialize',[this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');return this;}var a=this.formToArray(options.semantic);if(options.data){options.extraData=options.data;for(var n in options.data)a.push({name:n,value:options.data[n]});}if(options.beforeSubmit&&options.beforeSubmit(a,this,options)===false){log('ajaxSubmit: submit aborted via beforeSubmit callback');return this;}this.trigger('form-submit-validate',[a,this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-submit-validate trigger');return this;}var q=$.param(a);if(options.type.toUpperCase()=='GET'){options.url+=(options.url.indexOf('?')>=0?'&':'?')+q;options.data=null;}else
options.data=q;var $form=this,callbacks=[];if(options.resetForm)callbacks.push(function(){$form.resetForm();});if(options.clearForm)callbacks.push(function(){$form.clearForm();});if(!options.dataType&&options.target){var oldSuccess=options.success||function(){};callbacks.push(function(data){$(options.target).html(data).each(oldSuccess,arguments);});}else if(options.success)callbacks.push(options.success);options.success=function(data,status){for(var i=0,max=callbacks.length;i<max;i++)callbacks[i](data,status,$form);};var files=$('input:file',this).fieldValue();var found=false;for(var j=0;j<files.length;j++)if(files[j])found=true;if(options.iframe||found){if($.browser.safari&&options.closeKeepAlive)$.get(options.closeKeepAlive,fileUpload);else
fileUpload();}else
$.ajax(options);this.trigger('form-submit-notify',[this,options]);return this;function fileUpload(){var form=$form[0];var opts=$.extend({},$.ajaxSettings,options);var id='jqFormIO'+(new Date().getTime());var $io=$('<iframe id="'+id+'" name="'+id+'" />');var io=$io[0];if($.browser.msie||$.browser.opera)io.src='javascript:false;document.write("");';$io.css({position:'absolute',top:'-1000px',left:'-1000px'});var xhr={responseText:null,responseXML:null,status:0,statusText:'n/a',getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){}};var g=opts.global;if(g&&!$.active++)$.event.trigger("ajaxStart");if(g)$.event.trigger("ajaxSend",[xhr,opts]);var cbInvoked=0;var timedOut=0;setTimeout(function(){var t=$form.attr('target'),a=$form.attr('action');$form.attr({target:id,encoding:'multipart/form-data',enctype:'multipart/form-data',method:'POST',action:opts.url});if(opts.timeout)setTimeout(function(){timedOut=true;cb();},opts.timeout);var extraInputs=[];try{if(options.extraData)for(var n in options.extraData)extraInputs.push($('<input type="hidden" name="'+n+'" value="'+options.extraData[n]+'" />').appendTo(form)[0]);$io.appendTo('body');io.attachEvent?io.attachEvent('onload',cb):io.addEventListener('load',cb,false);form.submit();}finally{$form.attr('action',a);t?$form.attr('target',t):$form.removeAttr('target');$(extraInputs).remove();}},10);function cb(){if(cbInvoked++)return;io.detachEvent?io.detachEvent('onload',cb):io.removeEventListener('load',cb,false);var operaHack=0;var ok=true;try{if(timedOut)throw'timeout';var data,doc;doc=io.contentWindow?io.contentWindow.document:io.contentDocument?io.contentDocument:io.document;if(doc.body==null&&!operaHack&&$.browser.opera){operaHack=1;cbInvoked--;setTimeout(cb,100);return;}xhr.responseText=doc.body?doc.body.innerHTML:null;xhr.responseXML=doc.XMLDocument?doc.XMLDocument:doc;xhr.getResponseHeader=function(header){var headers={'content-type':opts.dataType};return headers[header];};if(opts.dataType=='json'||opts.dataType=='script'){var ta=doc.getElementsByTagName('textarea')[0];xhr.responseText=ta?ta.value:xhr.responseText;}else if(opts.dataType=='xml'&&!xhr.responseXML&&xhr.responseText!=null){xhr.responseXML=toXml(xhr.responseText);}data=$.httpData(xhr,opts.dataType);}catch(e){ok=false;$.handleError(opts,xhr,'error',e);}if(ok){opts.success(data,'success');if(g)$.event.trigger("ajaxSuccess",[xhr,opts]);}if(g)$.event.trigger("ajaxComplete",[xhr,opts]);if(g&&!--$.active)$.event.trigger("ajaxStop");if(opts.complete)opts.complete(xhr,ok?'success':'error');setTimeout(function(){$io.remove();xhr.responseXML=null;},100);};function toXml(s,doc){if(window.ActiveXObject){doc=new ActiveXObject('Microsoft.XMLDOM');doc.async='false';doc.loadXML(s);}else
doc=(new DOMParser()).parseFromString(s,'text/xml');return(doc&&doc.documentElement&&doc.documentElement.tagName!='parsererror')?doc:null;};};};$.fn.ajaxForm=function(options){return this.ajaxFormUnbind().bind('submit.form-plugin',function(){$(this).ajaxSubmit(options);return false;}).each(function(){$(":submit,input:image",this).bind('click.form-plugin',function(e){var $form=this.form;$form.clk=this;if(this.type=='image'){if(e.offsetX!=undefined){$form.clk_x=e.offsetX;$form.clk_y=e.offsetY;}else if(typeof $.fn.offset=='function'){var offset=$(this).offset();$form.clk_x=e.pageX-offset.left;$form.clk_y=e.pageY-offset.top;}else{$form.clk_x=e.pageX-this.offsetLeft;$form.clk_y=e.pageY-this.offsetTop;}}setTimeout(function(){$form.clk=$form.clk_x=$form.clk_y=null;},10);});});};$.fn.ajaxFormUnbind=function(){this.unbind('submit.form-plugin');return this.each(function(){$(":submit,input:image",this).unbind('click.form-plugin');});};$.fn.formToArray=function(semantic){var a=[];if(this.length==0)return a;var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els)return a;for(var i=0,max=els.length;i<max;i++){var el=els[i];var n=el.name;if(!n)continue;if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el)a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});continue;}var v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(var j=0,jmax=v.length;j<jmax;j++)a.push({name:n,value:v[j]});}else if(v!==null&&typeof v!='undefined')a.push({name:n,value:v});}if(!semantic&&form.clk){var inputs=form.getElementsByTagName("input");for(var i=0,max=inputs.length;i<max;i++){var input=inputs[i];var n=input.name;if(n&&!input.disabled&&input.type=="image"&&form.clk==input)a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});}}return a;};$.fn.formSerialize=function(semantic){return $.param(this.formToArray(semantic));};$.fn.fieldSerialize=function(successful){var a=[];this.each(function(){var n=this.name;if(!n)return;var v=$.fieldValue(this,successful);if(v&&v.constructor==Array){for(var i=0,max=v.length;i<max;i++)a.push({name:n,value:v[i]});}else if(v!==null&&typeof v!='undefined')a.push({name:this.name,value:v});});return $.param(a);};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length))continue;v.constructor==Array?$.merge(val,v):val.push(v);}return val;};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(typeof successful=='undefined')successful=true;if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1))return null;if(tag=='select'){var index=el.selectedIndex;if(index<0)return null;var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=$.browser.msie&&!(op.attributes['value'].specified)?op.text:op.value;if(one)return v;a.push(v);}}return a;}return el.value;};$.fn.clearForm=function(){return this.each(function(){$('input,select,textarea',this).clearFields();});};$.fn.clearFields=$.fn.clearInputs=function(){return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(t=='text'||t=='password'||tag=='textarea')this.value='';else if(t=='checkbox'||t=='radio')this.checked=false;else if(tag=='select')this.selectedIndex=-1;});};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType))this.reset();});};$.fn.enable=function(b){if(b==undefined)b=true;return this.each(function(){this.disabled=!b});};$.fn.select=function(select){if(select==undefined)select=true;return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio')this.checked=select;else if(this.tagName.toLowerCase()=='option'){var $sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').select(false);}this.selected=select;}});};function log(){if($.fn.ajaxSubmit.debug&&window.console&&window.console.log)window.console.log('[jquery.form] '+Array.prototype.join.call(arguments,''));};})(jQuery);


/*
 * jQuery Form Plugin
 * version: 2.10 (05/08/2008)
 * @requires jQuery v1.2.2 or later
 *
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id$
 */
(function($){$.fn.ajaxSubmit=function(options){if(!this.length){log('ajaxSubmit: skipping submit process - no element selected');return this;}if(typeof options=='function')options={success:options};options=$.extend({url:this.attr('action')||window.location.toString(),type:this.attr('method')||'GET'},options||{});var veto={};this.trigger('form-pre-serialize',[this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');return this;}var a=this.formToArray(options.semantic);if(options.data){options.extraData=options.data;for(var n in options.data)a.push({name:n,value:options.data[n]});}if(options.beforeSubmit&&options.beforeSubmit(a,this,options)===false){log('ajaxSubmit: submit aborted via beforeSubmit callback');return this;}this.trigger('form-submit-validate',[a,this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-submit-validate trigger');return this;}var q=$.param(a);if(options.type.toUpperCase()=='GET'){options.url+=(options.url.indexOf('?')>=0?'&':'?')+q;options.data=null;}else
options.data=q;var $form=this,callbacks=[];if(options.resetForm)callbacks.push(function(){$form.resetForm();});if(options.clearForm)callbacks.push(function(){$form.clearForm();});if(!options.dataType&&options.target){var oldSuccess=options.success||function(){};callbacks.push(function(data){$(options.target).html(data).each(oldSuccess,arguments);});}else if(options.success)callbacks.push(options.success);options.success=function(data,status){for(var i=0,max=callbacks.length;i<max;i++)callbacks[i](data,status,$form);};var files=$('input:file',this).fieldValue();var found=false;for(var j=0;j<files.length;j++)if(files[j])found=true;if(options.iframe||found){if($.browser.safari&&options.closeKeepAlive)$.get(options.closeKeepAlive,fileUpload);else
fileUpload();}else
$.ajax(options);this.trigger('form-submit-notify',[this,options]);return this;function fileUpload(){var form=$form[0];var opts=$.extend({},$.ajaxSettings,options);var id='jqFormIO'+(new Date().getTime());var $io=$('<iframe id="'+id+'" name="'+id+'" />');var io=$io[0];if($.browser.msie||$.browser.opera)io.src='javascript:false;document.write("");';$io.css({position:'absolute',top:'-1000px',left:'-1000px'});var xhr={responseText:null,responseXML:null,status:0,statusText:'n/a',getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){}};var g=opts.global;if(g&&!$.active++)$.event.trigger("ajaxStart");if(g)$.event.trigger("ajaxSend",[xhr,opts]);var cbInvoked=0;var timedOut=0;setTimeout(function(){var t=$form.attr('target'),a=$form.attr('action');$form.attr({target:id,encoding:'multipart/form-data',enctype:'multipart/form-data',method:'POST',action:opts.url});if(opts.timeout)setTimeout(function(){timedOut=true;cb();},opts.timeout);var extraInputs=[];try{if(options.extraData)for(var n in options.extraData)extraInputs.push($('<input type="hidden" name="'+n+'" value="'+options.extraData[n]+'" />').appendTo(form)[0]);$io.appendTo('body');io.attachEvent?io.attachEvent('onload',cb):io.addEventListener('load',cb,false);form.submit();}finally{$form.attr('action',a);t?$form.attr('target',t):$form.removeAttr('target');$(extraInputs).remove();}},10);function cb(){if(cbInvoked++)return;io.detachEvent?io.detachEvent('onload',cb):io.removeEventListener('load',cb,false);var operaHack=0;var ok=true;try{if(timedOut)throw'timeout';var data,doc;doc=io.contentWindow?io.contentWindow.document:io.contentDocument?io.contentDocument:io.document;if(doc.body==null&&!operaHack&&$.browser.opera){operaHack=1;cbInvoked--;setTimeout(cb,100);return;}xhr.responseText=doc.body?doc.body.innerHTML:null;xhr.responseXML=doc.XMLDocument?doc.XMLDocument:doc;xhr.getResponseHeader=function(header){var headers={'content-type':opts.dataType};return headers[header];};if(opts.dataType=='json'||opts.dataType=='script'){var ta=doc.getElementsByTagName('textarea')[0];xhr.responseText=ta?ta.value:xhr.responseText;}else if(opts.dataType=='xml'&&!xhr.responseXML&&xhr.responseText!=null){xhr.responseXML=toXml(xhr.responseText);}data=$.httpData(xhr,opts.dataType);}catch(e){ok=false;$.handleError(opts,xhr,'error',e);}if(ok){opts.success(data,'success');if(g)$.event.trigger("ajaxSuccess",[xhr,opts]);}if(g)$.event.trigger("ajaxComplete",[xhr,opts]);if(g&&!--$.active)$.event.trigger("ajaxStop");if(opts.complete)opts.complete(xhr,ok?'success':'error');setTimeout(function(){$io.remove();xhr.responseXML=null;},100);};function toXml(s,doc){if(window.ActiveXObject){doc=new ActiveXObject('Microsoft.XMLDOM');doc.async='false';doc.loadXML(s);}else
doc=(new DOMParser()).parseFromString(s,'text/xml');return(doc&&doc.documentElement&&doc.documentElement.tagName!='parsererror')?doc:null;};};};$.fn.ajaxForm=function(options){return this.ajaxFormUnbind().bind('submit.form-plugin',function(){$(this).ajaxSubmit(options);return false;}).each(function(){$(":submit,input:image",this).bind('click.form-plugin',function(e){var $form=this.form;$form.clk=this;if(this.type=='image'){if(e.offsetX!=undefined){$form.clk_x=e.offsetX;$form.clk_y=e.offsetY;}else if(typeof $.fn.offset=='function'){var offset=$(this).offset();$form.clk_x=e.pageX-offset.left;$form.clk_y=e.pageY-offset.top;}else{$form.clk_x=e.pageX-this.offsetLeft;$form.clk_y=e.pageY-this.offsetTop;}}setTimeout(function(){$form.clk=$form.clk_x=$form.clk_y=null;},10);});});};$.fn.ajaxFormUnbind=function(){this.unbind('submit.form-plugin');return this.each(function(){$(":submit,input:image",this).unbind('click.form-plugin');});};$.fn.formToArray=function(semantic){var a=[];if(this.length==0)return a;var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els)return a;for(var i=0,max=els.length;i<max;i++){var el=els[i];var n=el.name;if(!n)continue;if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el)a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});continue;}var v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(var j=0,jmax=v.length;j<jmax;j++)a.push({name:n,value:v[j]});}else if(v!==null&&typeof v!='undefined')a.push({name:n,value:v});}if(!semantic&&form.clk){var inputs=form.getElementsByTagName("input");for(var i=0,max=inputs.length;i<max;i++){var input=inputs[i];var n=input.name;if(n&&!input.disabled&&input.type=="image"&&form.clk==input)a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});}}return a;};$.fn.formSerialize=function(semantic){return $.param(this.formToArray(semantic));};$.fn.fieldSerialize=function(successful){var a=[];this.each(function(){var n=this.name;if(!n)return;var v=$.fieldValue(this,successful);if(v&&v.constructor==Array){for(var i=0,max=v.length;i<max;i++)a.push({name:n,value:v[i]});}else if(v!==null&&typeof v!='undefined')a.push({name:this.name,value:v});});return $.param(a);};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length))continue;v.constructor==Array?$.merge(val,v):val.push(v);}return val;};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(typeof successful=='undefined')successful=true;if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1))return null;if(tag=='select'){var index=el.selectedIndex;if(index<0)return null;var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=$.browser.msie&&!(op.attributes['value'].specified)?op.text:op.value;if(one)return v;a.push(v);}}return a;}return el.value;};$.fn.clearForm=function(){return this.each(function(){$('input,select,textarea',this).clearFields();});};$.fn.clearFields=$.fn.clearInputs=function(){return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(t=='text'||t=='password'||tag=='textarea')this.value='';else if(t=='checkbox'||t=='radio')this.checked=false;else if(tag=='select')this.selectedIndex=-1;});};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType))this.reset();});};$.fn.enable=function(b){if(b==undefined)b=true;return this.each(function(){this.disabled=!b});};$.fn.select=function(select){if(select==undefined)select=true;return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio')this.checked=select;else if(this.tagName.toLowerCase()=='option'){var $sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').select(false);}this.selected=select;}});};function log(){if($.fn.ajaxSubmit.debug&&window.console&&window.console.log)window.console.log('[jquery.form] '+Array.prototype.join.call(arguments,''));};})(jQuery);


/*
 * Interface elements for jQuery - http://interface.eyecon.ro
 *
 * Copyright (c) 2006 Stefan Petre
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
*/
jQuery.iUtil={getPosition:function(e)
{var x=0;var y=0;var es=e.style;var restoreStyles=false;if(jQuery(e).css('display')=='none'){var oldVisibility=es.visibility;var oldPosition=es.position;restoreStyles=true;es.visibility='hidden';es.display='block';es.position='absolute';}
var el=e;while(el){x+=el.offsetLeft+(el.currentStyle&&!jQuery.browser.opera?parseInt(el.currentStyle.borderLeftWidth)||0:0);y+=el.offsetTop+(el.currentStyle&&!jQuery.browser.opera?parseInt(el.currentStyle.borderTopWidth)||0:0);el=el.offsetParent;}
el=e;while(el&&el.tagName&&el.tagName.toLowerCase()!='body')
{x-=el.scrollLeft||0;y-=el.scrollTop||0;el=el.parentNode;}
if(restoreStyles==true){es.display='none';es.position=oldPosition;es.visibility=oldVisibility;}
return{x:x,y:y};},getPositionLite:function(el)
{var x=0,y=0;while(el){x+=el.offsetLeft||0;y+=el.offsetTop||0;el=el.offsetParent;}
return{x:x,y:y};},getSize:function(e)
{var w=jQuery.css(e,'width');var h=jQuery.css(e,'height');var wb=0;var hb=0;var es=e.style;if(jQuery(e).css('display')!='none'){wb=e.offsetWidth;hb=e.offsetHeight;}else{var oldVisibility=es.visibility;var oldPosition=es.position;es.visibility='hidden';es.display='block';es.position='absolute';wb=e.offsetWidth;hb=e.offsetHeight;es.display='none';es.position=oldPosition;es.visibility=oldVisibility;}
return{w:w,h:h,wb:wb,hb:hb};},getSizeLite:function(el)
{return{wb:el.offsetWidth||0,hb:el.offsetHeight||0};},getClient:function(e)
{var h,w,de;if(e){w=e.clientWidth;h=e.clientHeight;}else{de=document.documentElement;w=window.innerWidth||self.innerWidth||(de&&de.clientWidth)||document.body.clientWidth;h=window.innerHeight||self.innerHeight||(de&&de.clientHeight)||document.body.clientHeight;}
return{w:w,h:h};},getScroll:function(e)
{var t=0,l=0,w=0,h=0,iw=0,ih=0;if(e&&e.nodeName.toLowerCase()!='body'){t=e.scrollTop;l=e.scrollLeft;w=e.scrollWidth;h=e.scrollHeight;iw=0;ih=0;}else{if(document.documentElement){t=document.documentElement.scrollTop;l=document.documentElement.scrollLeft;w=document.documentElement.scrollWidth;h=document.documentElement.scrollHeight;}else if(document.body){t=document.body.scrollTop;l=document.body.scrollLeft;w=document.body.scrollWidth;h=document.body.scrollHeight;}
iw=self.innerWidth||document.documentElement.clientWidth||document.body.clientWidth||0;ih=self.innerHeight||document.documentElement.clientHeight||document.body.clientHeight||0;}
return{t:t,l:l,w:w,h:h,iw:iw,ih:ih};},getMargins:function(e,toInteger)
{var el=jQuery(e);var t=el.css('marginTop')||'';var r=el.css('marginRight')||'';var b=el.css('marginBottom')||'';var l=el.css('marginLeft')||'';if(toInteger)
return{t:parseInt(t)||0,r:parseInt(r)||0,b:parseInt(b)||0,l:parseInt(l)};else
return{t:t,r:r,b:b,l:l};},getPadding:function(e,toInteger)
{var el=jQuery(e);var t=el.css('paddingTop')||'';var r=el.css('paddingRight')||'';var b=el.css('paddingBottom')||'';var l=el.css('paddingLeft')||'';if(toInteger)
return{t:parseInt(t)||0,r:parseInt(r)||0,b:parseInt(b)||0,l:parseInt(l)};else
return{t:t,r:r,b:b,l:l};},getBorder:function(e,toInteger)
{var el=jQuery(e);var t=el.css('borderTopWidth')||'';var r=el.css('borderRightWidth')||'';var b=el.css('borderBottomWidth')||'';var l=el.css('borderLeftWidth')||'';if(toInteger)
return{t:parseInt(t)||0,r:parseInt(r)||0,b:parseInt(b)||0,l:parseInt(l)||0};else
return{t:t,r:r,b:b,l:l};},getPointer:function(event)
{var x=event.pageX||(event.clientX+(document.documentElement.scrollLeft||document.body.scrollLeft))||0;var y=event.pageY||(event.clientY+(document.documentElement.scrollTop||document.body.scrollTop))||0;return{x:x,y:y};},traverseDOM:function(nodeEl,func)
{func(nodeEl);nodeEl=nodeEl.firstChild;while(nodeEl){jQuery.iUtil.traverseDOM(nodeEl,func);nodeEl=nodeEl.nextSibling;}},purgeEvents:function(nodeEl)
{jQuery.iUtil.traverseDOM(nodeEl,function(el)
{for(var attr in el){if(typeof el[attr]==='function'){el[attr]=null;}}});},centerEl:function(el,axis)
{var clientScroll=jQuery.iUtil.getScroll();var windowSize=jQuery.iUtil.getSize(el);if(!axis||axis=='vertically')
jQuery(el).css({top:clientScroll.t+((Math.max(clientScroll.h,clientScroll.ih)-clientScroll.t-windowSize.hb)/2)+'px'});if(!axis||axis=='horizontally')
jQuery(el).css({left:clientScroll.l+((Math.max(clientScroll.w,clientScroll.iw)-clientScroll.l-windowSize.wb)/2)+'px'});},fixPNG:function(el,emptyGIF){var images=jQuery('img[@src*="png"]',el||document),png;images.each(function(){png=this.src;this.src=emptyGIF;this.style.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+png+"')";});}};[].indexOf||(Array.prototype.indexOf=function(v,n){n=(n==null)?0:n;var m=this.length;for(var i=n;i<m;i++)
if(this[i]==v)
return i;return-1;});
jQuery.iDrag={helper:null,dragged:null,destroy:function()
{return this.each(function()
{if(this.isDraggable){this.dragCfg.dhe.unbind('mousedown',jQuery.iDrag.draginit);this.dragCfg=null;this.isDraggable=false;if(jQuery.browser.msie){this.unselectable="off";}else{this.style.MozUserSelect='';this.style.KhtmlUserSelect='';this.style.userSelect='';}}});},draginit:function(e)
{if(jQuery.iDrag.dragged!=null){jQuery.iDrag.dragstop(e);return false;}
var elm=this.dragElem;jQuery(document).bind('mousemove',jQuery.iDrag.dragmove).bind('mouseup',jQuery.iDrag.dragstop);elm.dragCfg.pointer=jQuery.iUtil.getPointer(e);elm.dragCfg.currentPointer=elm.dragCfg.pointer;elm.dragCfg.init=false;elm.dragCfg.fromHandler=this!=this.dragElem;jQuery.iDrag.dragged=elm;if(elm.dragCfg.si&&this!=this.dragElem){parentPos=jQuery.iUtil.getPosition(elm.parentNode);sliderSize=jQuery.iUtil.getSize(elm);sliderPos={x:parseInt(jQuery.css(elm,'left'))||0,y:parseInt(jQuery.css(elm,'top'))||0};dx=elm.dragCfg.currentPointer.x-parentPos.x-sliderSize.wb/2-sliderPos.x;dy=elm.dragCfg.currentPointer.y-parentPos.y-sliderSize.hb/2-sliderPos.y;jQuery.iSlider.dragmoveBy(elm,[dx,dy]);}
return jQuery.selectKeyHelper||false;},dragstart:function(e)
{var elm=jQuery.iDrag.dragged;elm.dragCfg.init=true;var dEs=elm.style;elm.dragCfg.oD=jQuery.css(elm,'display');elm.dragCfg.oP=jQuery.css(elm,'position');if(!elm.dragCfg.initialPosition)
elm.dragCfg.initialPosition=elm.dragCfg.oP;elm.dragCfg.oR={x:parseInt(jQuery.css(elm,'left'))||0,y:parseInt(jQuery.css(elm,'top'))||0};elm.dragCfg.diffX=0;elm.dragCfg.diffY=0;if(jQuery.browser.msie){var oldBorder=jQuery.iUtil.getBorder(elm,true);elm.dragCfg.diffX=oldBorder.l||0;elm.dragCfg.diffY=oldBorder.t||0;}
elm.dragCfg.oC=jQuery.extend(jQuery.iUtil.getPosition(elm),jQuery.iUtil.getSize(elm));if(elm.dragCfg.oP!='relative'&&elm.dragCfg.oP!='absolute'){dEs.position='relative';}
jQuery.iDrag.helper.empty();var clonedEl=elm.cloneNode(true);jQuery(clonedEl).css({display:'block',left:'0px',top:'0px'});clonedEl.style.marginTop='0';clonedEl.style.marginRight='0';clonedEl.style.marginBottom='0';clonedEl.style.marginLeft='0';jQuery.iDrag.helper.append(clonedEl);var dhs=jQuery.iDrag.helper.get(0).style;if(elm.dragCfg.autoSize){dhs.width='auto';dhs.height='auto';}else{dhs.height=elm.dragCfg.oC.hb+'px';dhs.width=elm.dragCfg.oC.wb+'px';}
dhs.display='block';dhs.marginTop='0px';dhs.marginRight='0px';dhs.marginBottom='0px';dhs.marginLeft='0px';jQuery.extend(elm.dragCfg.oC,jQuery.iUtil.getSize(clonedEl));if(elm.dragCfg.cursorAt){if(elm.dragCfg.cursorAt.left){elm.dragCfg.oR.x+=elm.dragCfg.pointer.x-elm.dragCfg.oC.x-elm.dragCfg.cursorAt.left;elm.dragCfg.oC.x=elm.dragCfg.pointer.x-elm.dragCfg.cursorAt.left;}
if(elm.dragCfg.cursorAt.top){elm.dragCfg.oR.y+=elm.dragCfg.pointer.y-elm.dragCfg.oC.y-elm.dragCfg.cursorAt.top;elm.dragCfg.oC.y=elm.dragCfg.pointer.y-elm.dragCfg.cursorAt.top;}
if(elm.dragCfg.cursorAt.right){elm.dragCfg.oR.x+=elm.dragCfg.pointer.x-elm.dragCfg.oC.x-elm.dragCfg.oC.hb+elm.dragCfg.cursorAt.right;elm.dragCfg.oC.x=elm.dragCfg.pointer.x-elm.dragCfg.oC.wb+elm.dragCfg.cursorAt.right;}
if(elm.dragCfg.cursorAt.bottom){elm.dragCfg.oR.y+=elm.dragCfg.pointer.y-elm.dragCfg.oC.y-elm.dragCfg.oC.hb+elm.dragCfg.cursorAt.bottom;elm.dragCfg.oC.y=elm.dragCfg.pointer.y-elm.dragCfg.oC.hb+elm.dragCfg.cursorAt.bottom;}}
elm.dragCfg.nx=elm.dragCfg.oR.x;elm.dragCfg.ny=elm.dragCfg.oR.y;if(elm.dragCfg.insideParent||elm.dragCfg.containment=='parent'){parentBorders=jQuery.iUtil.getBorder(elm.parentNode,true);elm.dragCfg.oC.x=elm.offsetLeft+(jQuery.browser.msie?0:jQuery.browser.opera?-parentBorders.l:parentBorders.l);elm.dragCfg.oC.y=elm.offsetTop+(jQuery.browser.msie?0:jQuery.browser.opera?-parentBorders.t:parentBorders.t);jQuery(elm.parentNode).append(jQuery.iDrag.helper.get(0));}
if(elm.dragCfg.containment){jQuery.iDrag.getContainment(elm);elm.dragCfg.onDragModifier.containment=jQuery.iDrag.fitToContainer;}
if(elm.dragCfg.si){jQuery.iSlider.modifyContainer(elm);}
dhs.left=elm.dragCfg.oC.x-elm.dragCfg.diffX+'px';dhs.top=elm.dragCfg.oC.y-elm.dragCfg.diffY+'px';dhs.width=elm.dragCfg.oC.wb+'px';dhs.height=elm.dragCfg.oC.hb+'px';jQuery.iDrag.dragged.dragCfg.prot=false;if(elm.dragCfg.gx){elm.dragCfg.onDragModifier.grid=jQuery.iDrag.snapToGrid;}
if(elm.dragCfg.zIndex!=false){jQuery.iDrag.helper.css('zIndex',elm.dragCfg.zIndex);}
if(elm.dragCfg.opacity){jQuery.iDrag.helper.css('opacity',elm.dragCfg.opacity);if(window.ActiveXObject){jQuery.iDrag.helper.css('filter','alpha(opacity='+elm.dragCfg.opacity*100+')');}}
if(elm.dragCfg.frameClass){jQuery.iDrag.helper.addClass(elm.dragCfg.frameClass);jQuery.iDrag.helper.get(0).firstChild.style.display='none';}
if(elm.dragCfg.onStart)
elm.dragCfg.onStart.apply(elm,[clonedEl,elm.dragCfg.oR.x,elm.dragCfg.oR.y]);if(jQuery.iDrop&&jQuery.iDrop.count>0){jQuery.iDrop.highlight(elm);}
if(elm.dragCfg.ghosting==false){dEs.display='none';}
return false;},getContainment:function(elm)
{if(elm.dragCfg.containment.constructor==String){if(elm.dragCfg.containment=='parent'){elm.dragCfg.cont=jQuery.extend({x:0,y:0},jQuery.iUtil.getSize(elm.parentNode));var contBorders=jQuery.iUtil.getBorder(elm.parentNode,true);elm.dragCfg.cont.w=elm.dragCfg.cont.wb-contBorders.l-contBorders.r;elm.dragCfg.cont.h=elm.dragCfg.cont.hb-contBorders.t-contBorders.b;}else if(elm.dragCfg.containment=='document'){var clnt=jQuery.iUtil.getClient();elm.dragCfg.cont={x:0,y:0,w:clnt.w,h:clnt.h};}}else if(elm.dragCfg.containment.constructor==Array){elm.dragCfg.cont={x:parseInt(elm.dragCfg.containment[0])||0,y:parseInt(elm.dragCfg.containment[1])||0,w:parseInt(elm.dragCfg.containment[2])||0,h:parseInt(elm.dragCfg.containment[3])||0};}
elm.dragCfg.cont.dx=elm.dragCfg.cont.x-elm.dragCfg.oC.x;elm.dragCfg.cont.dy=elm.dragCfg.cont.y-elm.dragCfg.oC.y;},hidehelper:function(dragged)
{if(dragged.dragCfg.insideParent||dragged.dragCfg.containment=='parent'){jQuery('body',document).append(jQuery.iDrag.helper.get(0));}
jQuery.iDrag.helper.empty().hide().css('opacity',1);if(window.ActiveXObject){jQuery.iDrag.helper.css('filter','alpha(opacity=100)');}},dragstop:function(e)
{jQuery(document).unbind('mousemove',jQuery.iDrag.dragmove).unbind('mouseup',jQuery.iDrag.dragstop);if(jQuery.iDrag.dragged==null){return;}
var dragged=jQuery.iDrag.dragged;jQuery.iDrag.dragged=null;if(dragged.dragCfg.init==false){return false;}
if(dragged.dragCfg.so==true){jQuery(dragged).css('position',dragged.dragCfg.oP);}
var dEs=dragged.style;if(dragged.si){jQuery.iDrag.helper.css('cursor','move');}
if(dragged.dragCfg.frameClass){jQuery.iDrag.helper.removeClass(dragged.dragCfg.frameClass);}
if(dragged.dragCfg.revert==false){if(dragged.dragCfg.fx>0){if(!dragged.dragCfg.axis||dragged.dragCfg.axis=='horizontally'){var x=new jQuery.fx(dragged,{duration:dragged.dragCfg.fx},'left');x.custom(dragged.dragCfg.oR.x,dragged.dragCfg.nRx);}
if(!dragged.dragCfg.axis||dragged.dragCfg.axis=='vertically'){var y=new jQuery.fx(dragged,{duration:dragged.dragCfg.fx},'top');y.custom(dragged.dragCfg.oR.y,dragged.dragCfg.nRy);}}else{if(!dragged.dragCfg.axis||dragged.dragCfg.axis=='horizontally')
dragged.style.left=dragged.dragCfg.nRx+'px';if(!dragged.dragCfg.axis||dragged.dragCfg.axis=='vertically')
dragged.style.top=dragged.dragCfg.nRy+'px';}
jQuery.iDrag.hidehelper(dragged);if(dragged.dragCfg.ghosting==false){jQuery(dragged).css('display',dragged.dragCfg.oD);}}else if(dragged.dragCfg.fx>0){dragged.dragCfg.prot=true;var dh=false;if(jQuery.iDrop&&jQuery.iSort&&dragged.dragCfg.so){dh=jQuery.iUtil.getPosition(jQuery.iSort.helper.get(0));}
jQuery.iDrag.helper.animate({left:dh?dh.x:dragged.dragCfg.oC.x,top:dh?dh.y:dragged.dragCfg.oC.y},dragged.dragCfg.fx,function()
{dragged.dragCfg.prot=false;if(dragged.dragCfg.ghosting==false){dragged.style.display=dragged.dragCfg.oD;}
jQuery.iDrag.hidehelper(dragged);});}else{jQuery.iDrag.hidehelper(dragged);if(dragged.dragCfg.ghosting==false){jQuery(dragged).css('display',dragged.dragCfg.oD);}}
if(jQuery.iDrop&&jQuery.iDrop.count>0){jQuery.iDrop.checkdrop(dragged);}
if(jQuery.iSort&&dragged.dragCfg.so){jQuery.iSort.check(dragged);}
if(dragged.dragCfg.onChange&&(dragged.dragCfg.nRx!=dragged.dragCfg.oR.x||dragged.dragCfg.nRy!=dragged.dragCfg.oR.y)){dragged.dragCfg.onChange.apply(dragged,dragged.dragCfg.lastSi||[0,0,dragged.dragCfg.nRx,dragged.dragCfg.nRy]);}
if(dragged.dragCfg.onStop)
dragged.dragCfg.onStop.apply(dragged);return false;},snapToGrid:function(x,y,dx,dy)
{if(dx!=0)
dx=parseInt((dx+(this.dragCfg.gx*dx/Math.abs(dx))/2)/this.dragCfg.gx)*this.dragCfg.gx;if(dy!=0)
dy=parseInt((dy+(this.dragCfg.gy*dy/Math.abs(dy))/2)/this.dragCfg.gy)*this.dragCfg.gy;return{dx:dx,dy:dy,x:0,y:0};},fitToContainer:function(x,y,dx,dy)
{dx=Math.min(Math.max(dx,this.dragCfg.cont.dx),this.dragCfg.cont.w+this.dragCfg.cont.dx-this.dragCfg.oC.wb);dy=Math.min(Math.max(dy,this.dragCfg.cont.dy),this.dragCfg.cont.h+this.dragCfg.cont.dy-this.dragCfg.oC.hb);return{dx:dx,dy:dy,x:0,y:0}},dragmove:function(e)
{if(jQuery.iDrag.dragged==null||jQuery.iDrag.dragged.dragCfg.prot==true){return;}
var dragged=jQuery.iDrag.dragged;dragged.dragCfg.currentPointer=jQuery.iUtil.getPointer(e);if(dragged.dragCfg.init==false){distance=Math.sqrt(Math.pow(dragged.dragCfg.pointer.x-dragged.dragCfg.currentPointer.x,2)+Math.pow(dragged.dragCfg.pointer.y-dragged.dragCfg.currentPointer.y,2));if(distance<dragged.dragCfg.snapDistance){return;}else{jQuery.iDrag.dragstart(e);}}
var dx=dragged.dragCfg.currentPointer.x-dragged.dragCfg.pointer.x;var dy=dragged.dragCfg.currentPointer.y-dragged.dragCfg.pointer.y;for(var i in dragged.dragCfg.onDragModifier){var newCoords=dragged.dragCfg.onDragModifier[i].apply(dragged,[dragged.dragCfg.oR.x+dx,dragged.dragCfg.oR.y+dy,dx,dy]);if(newCoords&&newCoords.constructor==Object){dx=i!='user'?newCoords.dx:(newCoords.x-dragged.dragCfg.oR.x);dy=i!='user'?newCoords.dy:(newCoords.y-dragged.dragCfg.oR.y);}}
dragged.dragCfg.nx=dragged.dragCfg.oC.x+dx-dragged.dragCfg.diffX;dragged.dragCfg.ny=dragged.dragCfg.oC.y+dy-dragged.dragCfg.diffY;if(dragged.dragCfg.si&&(dragged.dragCfg.onSlide||dragged.dragCfg.onChange)){jQuery.iSlider.onSlide(dragged,dragged.dragCfg.nx,dragged.dragCfg.ny);}
if(dragged.dragCfg.onDrag)
dragged.dragCfg.onDrag.apply(dragged,[dragged.dragCfg.oR.x+dx,dragged.dragCfg.oR.y+dy]);if(!dragged.dragCfg.axis||dragged.dragCfg.axis=='horizontally'){dragged.dragCfg.nRx=dragged.dragCfg.oR.x+dx;jQuery.iDrag.helper.get(0).style.left=dragged.dragCfg.nx+'px';}
if(!dragged.dragCfg.axis||dragged.dragCfg.axis=='vertically'){dragged.dragCfg.nRy=dragged.dragCfg.oR.y+dy;jQuery.iDrag.helper.get(0).style.top=dragged.dragCfg.ny+'px';}
if(jQuery.iDrop&&jQuery.iDrop.count>0){jQuery.iDrop.checkhover(dragged);}
return false;},build:function(o)
{if(!jQuery.iDrag.helper){jQuery('body',document).append('<div id="dragHelper"></div>');jQuery.iDrag.helper=jQuery('#dragHelper');var el=jQuery.iDrag.helper.get(0);var els=el.style;els.position='absolute';els.display='none';els.cursor='move';els.listStyle='none';els.overflow='hidden';if(window.ActiveXObject){el.unselectable="on";}else{els.mozUserSelect='none';els.userSelect='none';els.KhtmlUserSelect='none';}}
if(!o){o={};}
return this.each(function()
{if(this.isDraggable||!jQuery.iUtil)
return;if(window.ActiveXObject){this.onselectstart=function(){return false;};this.ondragstart=function(){return false;};}
var el=this;var dhe=o.handle?jQuery(this).find(o.handle):jQuery(this);if(jQuery.browser.msie){dhe.each(function()
{this.unselectable="on";});}else{dhe.css('-moz-user-select','none');dhe.css('user-select','none');dhe.css('-khtml-user-select','none');}
this.dragCfg={dhe:dhe,revert:o.revert?true:false,ghosting:o.ghosting?true:false,so:o.so?o.so:false,si:o.si?o.si:false,insideParent:o.insideParent?o.insideParent:false,zIndex:o.zIndex?parseInt(o.zIndex)||0:false,opacity:o.opacity?parseFloat(o.opacity):false,fx:parseInt(o.fx)||null,hpc:o.hpc?o.hpc:false,onDragModifier:{},pointer:{},onStart:o.onStart&&o.onStart.constructor==Function?o.onStart:false,onStop:o.onStop&&o.onStop.constructor==Function?o.onStop:false,onChange:o.onChange&&o.onChange.constructor==Function?o.onChange:false,axis:/vertically|horizontally/.test(o.axis)?o.axis:false,snapDistance:o.snapDistance?parseInt(o.snapDistance)||0:0,cursorAt:o.cursorAt?o.cursorAt:false,autoSize:o.autoSize?true:false,frameClass:o.frameClass||false};if(o.onDragModifier&&o.onDragModifier.constructor==Function)
this.dragCfg.onDragModifier.user=o.onDragModifier;if(o.onDrag&&o.onDrag.constructor==Function)
this.dragCfg.onDrag=o.onDrag;if(o.containment&&((o.containment.constructor==String&&(o.containment=='parent'||o.containment=='document'))||(o.containment.constructor==Array&&o.containment.length==4))){this.dragCfg.containment=o.containment;}
if(o.fractions){this.dragCfg.fractions=o.fractions;}
if(o.grid){if(typeof o.grid=='number'){this.dragCfg.gx=parseInt(o.grid)||1;this.dragCfg.gy=parseInt(o.grid)||1;}else if(o.grid.length==2){this.dragCfg.gx=parseInt(o.grid[0])||1;this.dragCfg.gy=parseInt(o.grid[1])||1;}}
if(o.onSlide&&o.onSlide.constructor==Function){this.dragCfg.onSlide=o.onSlide;}
this.isDraggable=true;dhe.each(function(){this.dragElem=el;});dhe.bind('mousedown',jQuery.iDrag.draginit);})}};jQuery.fn.extend({DraggableDestroy:jQuery.iDrag.destroy,Draggable:jQuery.iDrag.build});


jQuery.iDrop={fit:function(zonex,zoney,zonew,zoneh)
{return zonex<=jQuery.iDrag.dragged.dragCfg.nx&&(zonex+zonew)>=(jQuery.iDrag.dragged.dragCfg.nx+jQuery.iDrag.dragged.dragCfg.oC.w)&&zoney<=jQuery.iDrag.dragged.dragCfg.ny&&(zoney+zoneh)>=(jQuery.iDrag.dragged.dragCfg.ny+jQuery.iDrag.dragged.dragCfg.oC.h)?true:false;},intersect:function(zonex,zoney,zonew,zoneh)
{return!(zonex>(jQuery.iDrag.dragged.dragCfg.nx+jQuery.iDrag.dragged.dragCfg.oC.w)||(zonex+zonew)<jQuery.iDrag.dragged.dragCfg.nx||zoney>(jQuery.iDrag.dragged.dragCfg.ny+jQuery.iDrag.dragged.dragCfg.oC.h)||(zoney+zoneh)<jQuery.iDrag.dragged.dragCfg.ny)?true:false;},pointer:function(zonex,zoney,zonew,zoneh)
{return zonex<jQuery.iDrag.dragged.dragCfg.currentPointer.x&&(zonex+zonew)>jQuery.iDrag.dragged.dragCfg.currentPointer.x&&zoney<jQuery.iDrag.dragged.dragCfg.currentPointer.y&&(zoney+zoneh)>jQuery.iDrag.dragged.dragCfg.currentPointer.y?true:false;},overzone:false,highlighted:{},count:0,zones:{},highlight:function(elm)
{if(jQuery.iDrag.dragged==null){return;}
var i;jQuery.iDrop.highlighted={};var oneIsSortable=false;for(i in jQuery.iDrop.zones){if(jQuery.iDrop.zones[i]!=null){var iEL=jQuery.iDrop.zones[i].get(0);if(jQuery(jQuery.iDrag.dragged).is('.'+iEL.dropCfg.a)){if(iEL.dropCfg.m==false){iEL.dropCfg.p=jQuery.extend(jQuery.iUtil.getPositionLite(iEL),jQuery.iUtil.getSizeLite(iEL));iEL.dropCfg.m=true;}
if(iEL.dropCfg.ac){jQuery.iDrop.zones[i].addClass(iEL.dropCfg.ac);}
jQuery.iDrop.highlighted[i]=jQuery.iDrop.zones[i];if(jQuery.iSort&&iEL.dropCfg.s&&jQuery.iDrag.dragged.dragCfg.so){iEL.dropCfg.el=jQuery('.'+iEL.dropCfg.a,iEL);elm.style.display='none';jQuery.iSort.measure(iEL);iEL.dropCfg.os=jQuery.iSort.serialize(jQuery.attr(iEL,'id')).hash;elm.style.display=elm.dragCfg.oD;oneIsSortable=true;}
if(iEL.dropCfg.onActivate){iEL.dropCfg.onActivate.apply(jQuery.iDrop.zones[i].get(0),[jQuery.iDrag.dragged]);}}}}
if(oneIsSortable){jQuery.iSort.start();}},remeasure:function()
{jQuery.iDrop.highlighted={};for(i in jQuery.iDrop.zones){if(jQuery.iDrop.zones[i]!=null){var iEL=jQuery.iDrop.zones[i].get(0);if(jQuery(jQuery.iDrag.dragged).is('.'+iEL.dropCfg.a)){iEL.dropCfg.p=jQuery.extend(jQuery.iUtil.getPositionLite(iEL),jQuery.iUtil.getSizeLite(iEL));if(iEL.dropCfg.ac){jQuery.iDrop.zones[i].addClass(iEL.dropCfg.ac);}
jQuery.iDrop.highlighted[i]=jQuery.iDrop.zones[i];if(jQuery.iSort&&iEL.dropCfg.s&&jQuery.iDrag.dragged.dragCfg.so){iEL.dropCfg.el=jQuery('.'+iEL.dropCfg.a,iEL);elm.style.display='none';jQuery.iSort.measure(iEL);elm.style.display=elm.dragCfg.oD;}}}}},checkhover:function(e)
{if(jQuery.iDrag.dragged==null){return;}
jQuery.iDrop.overzone=false;var i;var applyOnHover=false;var hlt=0;for(i in jQuery.iDrop.highlighted)
{var iEL=jQuery.iDrop.highlighted[i].get(0);if(jQuery.iDrop.overzone==false&&jQuery.iDrop[iEL.dropCfg.t](iEL.dropCfg.p.x,iEL.dropCfg.p.y,iEL.dropCfg.p.wb,iEL.dropCfg.p.hb)){if(iEL.dropCfg.hc&&iEL.dropCfg.h==false){jQuery.iDrop.highlighted[i].addClass(iEL.dropCfg.hc);}
if(iEL.dropCfg.h==false&&iEL.dropCfg.onHover){applyOnHover=true;}
iEL.dropCfg.h=true;jQuery.iDrop.overzone=iEL;if(jQuery.iSort&&iEL.dropCfg.s&&jQuery.iDrag.dragged.dragCfg.so){jQuery.iSort.helper.get(0).className=iEL.dropCfg.shc;jQuery.iSort.checkhover(iEL);}
hlt++;}else if(iEL.dropCfg.h==true){if(iEL.dropCfg.onOut){iEL.dropCfg.onOut.apply(iEL,[e,jQuery.iDrag.helper.get(0).firstChild,iEL.dropCfg.fx]);}
if(iEL.dropCfg.hc){jQuery.iDrop.highlighted[i].removeClass(iEL.dropCfg.hc);}
iEL.dropCfg.h=false;}}
if(jQuery.iSort&&!jQuery.iDrop.overzone&&jQuery.iDrag.dragged.so){jQuery.iSort.helper.get(0).style.display='none';}
if(applyOnHover){jQuery.iDrop.overzone.dropCfg.onHover.apply(jQuery.iDrop.overzone,[e,jQuery.iDrag.helper.get(0).firstChild]);}},checkdrop:function(e)
{var i;for(i in jQuery.iDrop.highlighted){var iEL=jQuery.iDrop.highlighted[i].get(0);if(iEL.dropCfg.ac){jQuery.iDrop.highlighted[i].removeClass(iEL.dropCfg.ac);}
if(iEL.dropCfg.hc){jQuery.iDrop.highlighted[i].removeClass(iEL.dropCfg.hc);}
if(iEL.dropCfg.s){jQuery.iSort.changed[jQuery.iSort.changed.length]=i;}
if(iEL.dropCfg.onDrop&&iEL.dropCfg.h==true){iEL.dropCfg.h=false;iEL.dropCfg.onDrop.apply(iEL,[e,iEL.dropCfg.fx]);}
iEL.dropCfg.m=false;iEL.dropCfg.h=false;}
jQuery.iDrop.highlighted={};},destroy:function()
{return this.each(function()
{if(this.isDroppable){if(this.dropCfg.s){id=jQuery.attr(this,'id');jQuery.iSort.collected[id]=null;jQuery('.'+this.dropCfg.a,this).DraggableDestroy();}
jQuery.iDrop.zones['d'+this.idsa]=null;this.isDroppable=false;this.f=null;}});},build:function(o)
{return this.each(function()
{if(this.isDroppable==true||!o.accept||!jQuery.iUtil||!jQuery.iDrag){return;}
this.dropCfg={a:o.accept,ac:o.activeclass||false,hc:o.hoverclass||false,shc:o.helperclass||false,onDrop:o.ondrop||o.onDrop||false,onHover:o.onHover||o.onhover||false,onOut:o.onOut||o.onout||false,onActivate:o.onActivate||false,t:o.tolerance&&(o.tolerance=='fit'||o.tolerance=='intersect')?o.tolerance:'pointer',fx:o.fx?o.fx:false,m:false,h:false};if(o.sortable==true&&jQuery.iSort){id=jQuery.attr(this,'id');jQuery.iSort.collected[id]=this.dropCfg.a;this.dropCfg.s=true;if(o.onChange){this.dropCfg.onChange=o.onChange;this.dropCfg.os=jQuery.iSort.serialize(id).hash;}}
this.isDroppable=true;this.idsa=parseInt(Math.random()*10000);jQuery.iDrop.zones['d'+this.idsa]=jQuery(this);jQuery.iDrop.count++;});}};jQuery.fn.extend({DroppableDestroy:jQuery.iDrop.destroy,Droppable:jQuery.iDrop.build});jQuery.recallDroppables=jQuery.iDrop.remeasure;


jQuery.iSort={changed:[],collected:{},helper:false,inFrontOf:null,start:function()
{if(jQuery.iDrag.dragged==null){return;}
var shs,margins,c,cs;jQuery.iSort.helper.get(0).className=jQuery.iDrag.dragged.dragCfg.hpc;shs=jQuery.iSort.helper.get(0).style;shs.display='block';jQuery.iSort.helper.oC=jQuery.extend(jQuery.iUtil.getPosition(jQuery.iSort.helper.get(0)),jQuery.iUtil.getSize(jQuery.iSort.helper.get(0)));shs.width=jQuery.iDrag.dragged.dragCfg.oC.wb+'px';shs.height=jQuery.iDrag.dragged.dragCfg.oC.hb+'px';margins=jQuery.iUtil.getMargins(jQuery.iDrag.dragged);shs.marginTop=margins.t;shs.marginRight=margins.r;shs.marginBottom=margins.b;shs.marginLeft=margins.l;if(jQuery.iDrag.dragged.dragCfg.ghosting==true){c=jQuery.iDrag.dragged.cloneNode(true);cs=c.style;cs.marginTop='0px';cs.marginRight='0px';cs.marginBottom='0px';cs.marginLeft='0px';cs.display='block';jQuery.iSort.helper.empty().append(c);}
jQuery(jQuery.iDrag.dragged).after(jQuery.iSort.helper.get(0));jQuery.iDrag.dragged.style.display='none';},check:function(e)
{if(!e.dragCfg.so&&jQuery.iDrop.overzone.sortable){if(e.dragCfg.onStop)
e.dragCfg.onStop.apply(dragged);jQuery(e).css('position',e.dragCfg.initialPosition||e.dragCfg.oP);jQuery(e).DraggableDestroy();jQuery(jQuery.iDrop.overzone).SortableAddItem(e);}
jQuery.iSort.helper.removeClass(e.dragCfg.hpc).html('&nbsp;');jQuery.iSort.inFrontOf=null;var shs=jQuery.iSort.helper.get(0).style;shs.display='none';jQuery.iSort.helper.after(e);if(e.dragCfg.fx>0){jQuery(e).fadeIn(e.dragCfg.fx);}
jQuery('body').append(jQuery.iSort.helper.get(0));var ts=[];var fnc=false;for(var i=0;i<jQuery.iSort.changed.length;i++){var iEL=jQuery.iDrop.zones[jQuery.iSort.changed[i]].get(0);var id=jQuery.attr(iEL,'id');var ser=jQuery.iSort.serialize(id);if(iEL.dropCfg.os!=ser.hash){iEL.dropCfg.os=ser.hash;if(fnc==false&&iEL.dropCfg.onChange){fnc=iEL.dropCfg.onChange;}
ser.id=id;ts[ts.length]=ser;}}
jQuery.iSort.changed=[];if(fnc!=false&&ts.length>0){fnc(ts);}},checkhover:function(e,o)
{if(!jQuery.iDrag.dragged)
return;var cur=false;var i=0;if(e.dropCfg.el.size()>0){for(i=e.dropCfg.el.size();i>0;i--){if(e.dropCfg.el.get(i-1)!=jQuery.iDrag.dragged){if(!e.sortCfg.floats){if((e.dropCfg.el.get(i-1).pos.y+e.dropCfg.el.get(i-1).pos.hb/2)>jQuery.iDrag.dragged.dragCfg.ny){cur=e.dropCfg.el.get(i-1);}else{break;}}else{if((e.dropCfg.el.get(i-1).pos.x+e.dropCfg.el.get(i-1).pos.wb/2)>jQuery.iDrag.dragged.dragCfg.nx&&(e.dropCfg.el.get(i-1).pos.y+e.dropCfg.el.get(i-1).pos.hb/2)>jQuery.iDrag.dragged.dragCfg.ny){cur=e.dropCfg.el.get(i-1);}}}}}
if(cur&&jQuery.iSort.inFrontOf!=cur){jQuery.iSort.inFrontOf=cur;jQuery(cur).before(jQuery.iSort.helper.get(0));}else if(!cur&&(jQuery.iSort.inFrontOf!=null||jQuery.iSort.helper.get(0).parentNode!=e)){jQuery.iSort.inFrontOf=null;jQuery(e).append(jQuery.iSort.helper.get(0));}
jQuery.iSort.helper.get(0).style.display='block';},measure:function(e)
{if(jQuery.iDrag.dragged==null){return;}
e.dropCfg.el.each(function()
{this.pos=jQuery.extend(jQuery.iUtil.getSizeLite(this),jQuery.iUtil.getPositionLite(this));});},serialize:function(s)
{var i;var h='';var o={};if(s){if(jQuery.iSort.collected[s]){o[s]=[];jQuery('#'+s+' .'+jQuery.iSort.collected[s]).each(function()
{if(h.length>0){h+='&';}
h+=s+'[]='+jQuery.attr(this,'id');o[s][o[s].length]=jQuery.attr(this,'id');});}else{for(a in s){if(jQuery.iSort.collected[s[a]]){o[s[a]]=[];jQuery('#'+s[a]+' .'+jQuery.iSort.collected[s[a]]).each(function()
{if(h.length>0){h+='&';}
h+=s[a]+'[]='+jQuery.attr(this,'id');o[s[a]][o[s[a]].length]=jQuery.attr(this,'id');});}}}}else{for(i in jQuery.iSort.collected){o[i]=[];jQuery('#'+i+' .'+jQuery.iSort.collected[i]).each(function()
{if(h.length>0){h+='&';}
h+=i+'[]='+jQuery.attr(this,'id');o[i][o[i].length]=jQuery.attr(this,'id');});}}
return{hash:h,o:o};},addItem:function(e)
{if(!e.childNodes){return;}
return this.each(function()
{if(!this.sortCfg||!jQuery(e).is('.'+this.sortCfg.accept))
jQuery(e).addClass(this.sortCfg.accept);jQuery(e).Draggable(this.sortCfg.dragCfg);});},destroy:function()
{return this.each(function()
{jQuery('.'+this.sortCfg.accept).DraggableDestroy();jQuery(this).DroppableDestroy();this.sortCfg=null;this.isSortable=null;});},build:function(o)
{if(o.accept&&jQuery.iUtil&&jQuery.iDrag&&jQuery.iDrop){if(!jQuery.iSort.helper){jQuery('body',document).append('<div id="sortHelper">&nbsp;</div>');jQuery.iSort.helper=jQuery('#sortHelper');jQuery.iSort.helper.get(0).style.display='none';}
this.Droppable({accept:o.accept,activeclass:o.activeclass?o.activeclass:false,hoverclass:o.hoverclass?o.hoverclass:false,helperclass:o.helperclass?o.helperclass:false,onHover:o.onHover||o.onhover,onOut:o.onOut||o.onout,sortable:true,onChange:o.onChange||o.onchange,fx:o.fx?o.fx:false,ghosting:o.ghosting?true:false,tolerance:o.tolerance?o.tolerance:'intersect'});return this.each(function()
{var dragCfg={revert:o.revert?true:false,zindex:3000,opacity:o.opacity?parseFloat(o.opacity):false,hpc:o.helperclass?o.helperclass:false,fx:o.fx?o.fx:false,so:true,ghosting:o.ghosting?true:false,handle:o.handle?o.handle:null,containment:o.containment?o.containment:null,onStart:o.onStart&&o.onStart.constructor==Function?o.onStart:false,onDrag:o.onDrag&&o.onDrag.constructor==Function?o.onDrag:false,onStop:o.onStop&&o.onStop.constructor==Function?o.onStop:false,axis:/vertically|horizontally/.test(o.axis)?o.axis:false,snapDistance:o.snapDistance?parseInt(o.snapDistance)||0:false,cursorAt:o.cursorAt?o.cursorAt:false};jQuery('.'+o.accept,this).Draggable(dragCfg);this.isSortable=true;this.sortCfg={accept:o.accept,revert:o.revert?true:false,zindex:3000,opacity:o.opacity?parseFloat(o.opacity):false,hpc:o.helperclass?o.helperclass:false,fx:o.fx?o.fx:false,so:true,ghosting:o.ghosting?true:false,handle:o.handle?o.handle:null,containment:o.containment?o.containment:null,floats:o.floats?true:false,dragCfg:dragCfg}});}}};jQuery.fn.extend({Sortable:jQuery.iSort.build,SortableAddItem:jQuery.iSort.addItem,SortableDestroy:jQuery.iSort.destroy});jQuery.SortSerialize=jQuery.iSort.serialize;


/**
 *
 * Nested Sortable Plugin for jQuery/Interface.
 *
 * Version 1.0.1
 *
 *Change Log:
 * 1.0
 *       Initial Release
 * 1.0.1
 *       Added noNestingClass option to prevent nesting in some elements.
 *
 * Copyright (c) 2007 Bernardo de Padua dos Santos
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://code.google.com/p/nestedsortables/
 *
 * Compressed using Dean Edwards' Packer (http://dean.edwards.name/packer/)
 *
 */
jQuery.iNestedSortable={checkHover:function(e,o){if(e.isNestedSortable){jQuery.iNestedSortable.scroll(e);return jQuery.iNestedSortable.newCheckHover(e);}else{return jQuery.iNestedSortable.oldCheckHover(e,o);}},oldCheckHover:jQuery.iSort.checkhover,newCheckHover:function(e){if(!jQuery.iDrag.dragged){return;}
if(!(e.dropCfg.el.size()>0)){return;}
if(!e.nestedSortCfg.remeasured){jQuery.iSort.measure(e);e.nestedSortCfg.remeasured=true;}
var precItem=jQuery.iNestedSortable.findPrecedingItem(e);var shouldNest=jQuery.iNestedSortable.shouldNestItem(e,precItem);var touchingFirst=(!precItem)?jQuery.iNestedSortable.isTouchingFirstItem(e):false;var quit=false;if(precItem){if(e.nestedSortCfg.lastPrecedingItem===precItem&&e.nestedSortCfg.lastShouldNest===shouldNest){quit=true;}}
else if(e.nestedSortCfg.lastPrecedingItem===precItem&&e.nestedSortCfg.lastTouchingFirst===touchingFirst){quit=true;}
e.nestedSortCfg.lastPrecedingItem=precItem;e.nestedSortCfg.lastShouldNest=shouldNest;e.nestedSortCfg.lastTouchingFirst=touchingFirst;if(quit){return;}
if(precItem!==null){if(shouldNest){jQuery.iNestedSortable.nestItem(e,precItem);}else{jQuery.iNestedSortable.appendItem(e,precItem);}}else if(touchingFirst){jQuery.iNestedSortable.insertOnTop(e);}},scroll:function(e){if(!e.nestedSortCfg.autoScroll){return false;}
var sensitivity=e.nestedSortCfg.scrollSensitivity;var speed=e.nestedSortCfg.scrollSpeed;var pointer=jQuery.iDrag.dragged.dragCfg.currentPointer;var docDim=jQuery.iUtil.getScroll();if((pointer.y-docDim.ih)-docDim.t>-sensitivity){window.scrollBy(0,speed);}
if(pointer.y-docDim.t<sensitivity){window.scrollBy(0,-speed);}},check:function(dragged){jQuery.iNestedSortable.newCheck(dragged);return jQuery.iNestedSortable.oldCheck(dragged);},oldCheck:jQuery.iSort.check,newCheck:function(dragged){if(jQuery.iNestedSortable.latestNestingClass&&jQuery.iNestedSortable.currentNesting)
{jQuery.iNestedSortable.currentNesting.removeClass(jQuery.iNestedSortable.latestNestingClass);jQuery.iNestedSortable.currentNesting=null;jQuery.iNestedSortable.latestNestingClass="";}
if(jQuery.iDrop.overzone.isNestedSortable){jQuery.iDrop.overzone.nestedSortCfg.remeasured=false;}},serialize:function(s){if(jQuery('#'+s).get(0).isNestedSortable){return jQuery.iNestedSortable.newSerialize(s);}else{return jQuery.iNestedSortable.oldSerialize(s);}},oldSerialize:jQuery.iSort.serialize,newSerialize:function(s){var i;var h='';var currentPath='';var o={};var e;var buildHierarchySer=function(context){var retVal=[];thisChildren=jQuery(context).children('.'+jQuery.iSort.collected[s]);thisChildren.each(function(i){var serId=jQuery.attr(this,'id');if(serId&&serId.match){serId=serId.match(e.nestedSortCfg.serializeRegExp)[0];}
if(h.length>0){h+='&';}
h+=s+currentPath+'['+i+'][id]='+serId;retVal[i]={id:serId};var newContext=jQuery(this).children(e.nestedSortCfg.nestingTag+"."+e.nestedSortCfg.nestingTagClass.split(" ").join(".")).get(0);var oldPath=currentPath;currentPath+='['+i+'][children]';var thisChildren=buildHierarchySer(newContext);if(thisChildren.length>0){retVal[i].children=thisChildren;}
currentPath=oldPath;});return retVal;};if(s){if(jQuery.iSort.collected[s]){e=jQuery('#'+s).get(0);o[s]=buildHierarchySer(e);}else{for(a in s){if(jQuery.iSort.collected[s[a]]){e=jQuery('#'+s[a]).get(0);o[s[a]]=buildHierarchySer(e);}}}}else{for(i in jQuery.iSort.collected){e=jQuery('#'+i).get(0);o[i]=buildHierarchySer(e);}}
return{hash:h,o:o};},findPrecedingItem:function(e){var largestY=0;var preceding=jQuery.grep(e.dropCfg.el,function(i){var isOnTop=(i.pos.y<jQuery.iDrag.dragged.dragCfg.ny)&&(i.pos.y>largestY);if(!isOnTop){return false;}
var isSameLevel;if(e.nestedSortCfg.rightToLeft){isSameLevel=(i.pos.x+i.pos.wb+e.nestedSortCfg.snapTolerance>jQuery.iDrag.dragged.dragCfg.nx+jQuery.iDrag.dragged.dragCfg.oC.wb);}else{isSameLevel=(i.pos.x-e.nestedSortCfg.snapTolerance<jQuery.iDrag.dragged.dragCfg.nx);}
if(!isSameLevel){return false;}
var isBeingDragged=jQuery.iNestedSortable.isBeingDragged(e,i);if(isBeingDragged){return false;}
largestY=i.pos.y;return true;});if(preceding.length>0){return preceding[(preceding.length-1)];}else{return null;}},isTouchingFirstItem:function(e){var lowestY;var firstItem=jQuery.grep(e.dropCfg.el,function(i){var isBefore=(lowestY===undefined||i.pos.y<lowestY);if(!isBefore){return false;}
var isBeingDragged=jQuery.iNestedSortable.isBeingDragged(e,i);if(isBeingDragged){return false;}
lowestY=i.pos.y;return true;});if(firstItem.length>0){firstItem=firstItem[(firstItem.length-1)];return firstItem.pos.y<jQuery.iDrag.dragged.dragCfg.ny+jQuery.iDrag.dragged.dragCfg.oC.hb&&firstItem.pos.y>jQuery.iDrag.dragged.dragCfg.ny;}else{return false;}},isBeingDragged:function(e,elem){var dragged=jQuery.iDrag.dragged;if(!dragged){return false;}
if(elem==dragged){return true;}
if(jQuery(elem).parents("."+e.sortCfg.accept.split(" ").join(".")).filter(function(){return this==dragged;}).length!==0){return true;}else{return false;}},shouldNestItem:function(e,precedingItem){if(!precedingItem){return false;}
if(e.nestedSortCfg.noNestingClass&&jQuery(precedingItem).filter("."+e.nestedSortCfg.noNestingClass).get(0)===precedingItem)
{return false;}
if(e.nestedSortCfg.rightToLeft){return precedingItem.pos.x+precedingItem.pos.wb-(e.nestedSortCfg.nestingPxSpace-e.nestedSortCfg.snapTolerance)>jQuery.iDrag.dragged.dragCfg.nx+jQuery.iDrag.dragged.dragCfg.oC.wb;}else{return precedingItem.pos.x+(e.nestedSortCfg.nestingPxSpace-e.nestedSortCfg.snapTolerance)<jQuery.iDrag.dragged.dragCfg.nx;}},nestItem:function(e,parent){var parentNesting=jQuery(parent).children(e.nestedSortCfg.nestingTag+"."+e.nestedSortCfg.nestingTagClass.split(" ").join("."));var helper=jQuery.iSort.helper;styleHelper=helper.get(0).style;styleHelper.width='auto';if(!parentNesting.size()){var newUl="<"+e.nestedSortCfg.nestingTag+" class='"+e.nestedSortCfg.nestingTagClass+"'></"+e.nestedSortCfg.nestingTag+">";parentNesting=jQuery(parent).append(newUl).children(e.nestedSortCfg.nestingTag).css(e.nestedSortCfg.styleToAttach);}
jQuery.iNestedSortable.updateCurrentNestingClass(e,parentNesting);jQuery.iNestedSortable.beforeHelperRemove(e);parentNesting.prepend(helper.get(0));jQuery.iNestedSortable.afterHelperInsert(e);},appendItem:function(e,itemBefore){jQuery.iNestedSortable.updateCurrentNestingClass(e,jQuery(itemBefore).parent());jQuery.iNestedSortable.beforeHelperRemove(e);jQuery(itemBefore).after(jQuery.iSort.helper.get(0));jQuery.iNestedSortable.afterHelperInsert(e);},insertOnTop:function(e){jQuery.iNestedSortable.updateCurrentNestingClass(e,e);jQuery.iNestedSortable.beforeHelperRemove(e);jQuery(e).prepend(jQuery.iSort.helper.get(0));jQuery.iNestedSortable.afterHelperInsert(e);},beforeHelperRemove:function(e){var parent=jQuery.iSort.helper.parent(e.nestedSortCfg.nestingTag+"."+e.nestedSortCfg.nestingTagClass.split(" ").join("."));var numSiblings=parent.children("."+e.sortCfg.accept.split(" ").join(".")+":visible").size();if(numSiblings===0&&parent.get(0)!==e){parent.hide();}},afterHelperInsert:function(e){var parent=jQuery.iSort.helper.parent();if(parent.get(0)!==e){parent.show();}
e.nestedSortCfg.remeasured=false;},updateCurrentNestingClass:function(e,nestingElem){var nesting=jQuery(nestingElem);if((e.nestedSortCfg.currentNestingClass)&&(!jQuery.iNestedSortable.currentNesting||nesting.get(0)!=jQuery.iNestedSortable.currentNesting.get(0))){if(jQuery.iNestedSortable.currentNesting){jQuery.iNestedSortable.currentNesting.removeClass(e.nestedSortCfg.currentNestingClass);}
if(nesting.get(0)!=e){jQuery.iNestedSortable.currentNesting=nesting;nesting.addClass(e.nestedSortCfg.currentNestingClass);jQuery.iNestedSortable.latestNestingClass=e.nestedSortCfg.currentNestingClass;}else{jQuery.iNestedSortable.currentNesting=null;jQuery.iNestedSortable.latestNestingClass="";}}},destroy:function(){return this.each(function(){if(this.isNestedSortable){this.nestedSortCfg=null;this.isNestedSortable=null;jQuery(this).SortableDestroy();}});},build:function(conf){if(conf.accept&&jQuery.iUtil&&jQuery.iDrag&&jQuery.iDrop&&jQuery.iSort)
{this.each(function(){this.isNestedSortable=true;this.nestedSortCfg={noNestingClass:conf.noNestingClass?conf.noNestingClass:false,rightToLeft:conf.rightToLeft?true:false,nestingPxSpace:parseInt(conf.nestingPxSpace,10)||30,currentNestingClass:conf.currentNestingClass?conf.currentNestingClass:"",nestingLimit:conf.nestingLimit?conf.nestingLimit:false,autoScroll:conf.autoScroll!==undefined?conf.autoScroll==true:true,scrollSensitivity:conf.scrollSensitivity?conf.scrollSensitivity:20,scrollSpeed:conf.scrollSpeed?conf.scrollSpeed:20,serializeRegExp:conf.serializeRegExp?conf.serializeRegExp:/[^\-]*$/};this.nestedSortCfg.snapTolerance=parseInt(this.nestedSortCfg.nestingPxSpace*0.4,10);this.nestedSortCfg.nestingTag=this.tagName;this.nestedSortCfg.nestingTagClass=this.className;this.nestedSortCfg.styleToAttach=(this.nestedSortCfg.rightToLeft)?{"padding-left":0,"padding-right":this.nestedSortCfg.nestingPxSpace+'px'}:{"padding-left":this.nestedSortCfg.nestingPxSpace+'px',"padding-right":0};jQuery(this.nestedSortCfg.nestingTag,this).css(this.nestedSortCfg.styleToAttach);});jQuery.iSort.checkhover=jQuery.iNestedSortable.checkHover;jQuery.iSort.check=jQuery.iNestedSortable.check;jQuery.iSort.serialize=jQuery.iNestedSortable.serialize;}
return this.Sortable(conf);}};jQuery.fn.extend({NestedSortable:jQuery.iNestedSortable.build,NestedSortableDestroy:jQuery.iNestedSortable.destroy});jQuery.iUtil.getScroll=function(e)
{var t,l,w,h,iw,ih;if(e&&e.nodeName.toLowerCase()!='body'){t=e.scrollTop;l=e.scrollLeft;w=e.scrollWidth;h=e.scrollHeight;iw=0;ih=0;}else{if(document.documentElement&&document.documentElement.scrollTop){t=document.documentElement.scrollTop;l=document.documentElement.scrollLeft;w=document.documentElement.scrollWidth;h=document.documentElement.scrollHeight;}else if(document.body){t=document.body.scrollTop;l=document.body.scrollLeft;w=document.body.scrollWidth;h=document.body.scrollHeight;}
iw=self.innerWidth||document.documentElement.clientWidth||document.body.clientWidth||0;ih=self.innerHeight||document.documentElement.clientHeight||document.body.clientHeight||0;}
return{t:t,l:l,w:w,h:h,iw:iw,ih:ih};};
