/**
 * jQuery UI 1.6rc5
 *
 * Copyright (c) 2008 Paul Bakaus (ui.jquery.com)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI
 *
 * Enabled Components:
 *   Draggable, Droppable, Sortable
 */
;(function($){var _remove=$.fn.remove,isFF2=$.browser.mozilla&&(parseFloat($.browser.version)<1.9);$.ui={version:"1.6rc5",plugin:{add:function(module,option,set){var proto=$.ui[module].prototype;for(var i in set){proto.plugins[i]=proto.plugins[i]||[];proto.plugins[i].push([option,set[i]]);}},call:function(instance,name,args){var set=instance.plugins[name];if(!set){return;}
for(var i=0;i<set.length;i++){if(instance.options[set[i][0]]){set[i][1].apply(instance.element,args);}}}},contains:function(a,b){return document.compareDocumentPosition?a.compareDocumentPosition(b)&16:a!==b&&a.contains(b);},cssCache:{},css:function(name){if($.ui.cssCache[name]){return $.ui.cssCache[name];}
var tmp=$('<div class="ui-gen"></div>').addClass(name).css({position:'absolute',top:'-5000px',left:'-5000px',display:'block'}).appendTo('body');$.ui.cssCache[name]=!!((!(/auto|default/).test(tmp.css('cursor'))||(/^[1-9]/).test(tmp.css('height'))||(/^[1-9]/).test(tmp.css('width'))||!(/none/).test(tmp.css('backgroundImage'))||!(/transparent|rgba\(0, 0, 0, 0\)/).test(tmp.css('backgroundColor'))));try{$('body').get(0).removeChild(tmp.get(0));}catch(e){}
return $.ui.cssCache[name];},hasScroll:function(el,a){if($(el).css('overflow')=='hidden'){return false;}
var scroll=(a&&a=='left')?'scrollLeft':'scrollTop',has=false;if(el[scroll]>0){return true;}
el[scroll]=1;has=(el[scroll]>0);el[scroll]=0;return has;},isOverAxis:function(x,reference,size){return(x>reference)&&(x<(reference+size));},isOver:function(y,x,top,left,height,width){return $.ui.isOverAxis(y,top,height)&&$.ui.isOverAxis(x,left,width);},keyCode:{BACKSPACE:8,CAPS_LOCK:20,COMMA:188,CONTROL:17,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,INSERT:45,LEFT:37,NUMPAD_ADD:107,NUMPAD_DECIMAL:110,NUMPAD_DIVIDE:111,NUMPAD_ENTER:108,NUMPAD_MULTIPLY:106,NUMPAD_SUBTRACT:109,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SHIFT:16,SPACE:32,TAB:9,UP:38}};if(isFF2){var attr=$.attr,removeAttr=$.fn.removeAttr,ariaNS="http://www.w3.org/2005/07/aaa",ariaState=/^aria-/,ariaRole=/^wairole:/;$.attr=function(elem,name,value){var set=value!==undefined;return(name=='role'?(set?attr.call(this,elem,name,"wairole:"+value):(attr.apply(this,arguments)||"").replace(ariaRole,"")):(ariaState.test(name)?(set?elem.setAttributeNS(ariaNS,name.replace(ariaState,"aaa:"),value):attr.call(this,elem,name.replace(ariaState,"aaa:"))):attr.apply(this,arguments)));};$.fn.removeAttr=function(name){return(ariaState.test(name)?this.each(function(){this.removeAttributeNS(ariaNS,name.replace(ariaState,""));}):removeAttr.call(this,name));};}
$.fn.extend({remove:function(){$("*",this).add(this).each(function(){$(this).triggerHandler("remove");});return _remove.apply(this,arguments);},enableSelection:function(){return this.attr('unselectable','off').css('MozUserSelect','').unbind('selectstart.ui');},disableSelection:function(){return this.attr('unselectable','on').css('MozUserSelect','none').bind('selectstart.ui',function(){return false;});},scrollParent:function(){var scrollParent;if(($.browser.msie&&(/(static|relative)/).test(this.css('position')))||(/absolute/).test(this.css('position'))){scrollParent=this.parents().filter(function(){return(/(relative|absolute|fixed)/).test($.curCSS(this,'position',1))&&(/(auto|scroll)/).test($.curCSS(this,'overflow',1)+$.curCSS(this,'overflow-y',1)+$.curCSS(this,'overflow-x',1));}).eq(0);}else{scrollParent=this.parents().filter(function(){return(/(auto|scroll)/).test($.curCSS(this,'overflow',1)+$.curCSS(this,'overflow-y',1)+$.curCSS(this,'overflow-x',1));}).eq(0);}
return(/fixed/).test(this.css('position'))||!scrollParent.length?$(document):scrollParent;}});$.extend($.expr[':'],{data:function(elem,i,match){return!!$.data(elem,match[3]);},tabbable:function(elem){var nodeName=elem.nodeName.toLowerCase();function isVisible(element){return!($(element).is(':hidden')||$(element).parents(':hidden').length);}
return(elem.tabIndex>=0&&(('a'==nodeName&&elem.href)||(/input|select|textarea|button/.test(nodeName)&&'hidden'!=elem.type&&!elem.disabled))&&isVisible(elem));}});function getter(namespace,plugin,method,args){function getMethods(type){var methods=$[namespace][plugin][type]||[];return(typeof methods=='string'?methods.split(/,?\s+/):methods);}
var methods=getMethods('getter');if(args.length==1&&typeof args[0]=='string'){methods=methods.concat(getMethods('getterSetter'));}
return($.inArray(method,methods)!=-1);}
$.widget=function(name,prototype){var namespace=name.split(".")[0];name=name.split(".")[1];$.fn[name]=function(options){var isMethodCall=(typeof options=='string'),args=Array.prototype.slice.call(arguments,1);if(isMethodCall&&options.substring(0,1)=='_'){return this;}
if(isMethodCall&&getter(namespace,name,options,args)){var instance=$.data(this[0],name);return(instance?instance[options].apply(instance,args):undefined);}
return this.each(function(){var instance=$.data(this,name);(!instance&&!isMethodCall&&$.data(this,name,new $[namespace][name](this,options)));(instance&&isMethodCall&&$.isFunction(instance[options])&&instance[options].apply(instance,args));});};$[namespace]=$[namespace]||{};$[namespace][name]=function(element,options){var self=this;this.namespace=namespace;this.widgetName=name;this.widgetEventPrefix=$[namespace][name].eventPrefix||name;this.widgetBaseClass=namespace+'-'+name;this.options=$.extend({},$.widget.defaults,$[namespace][name].defaults,$.metadata&&$.metadata.get(element)[name],options);this.element=$(element).bind('setData.'+name,function(event,key,value){if(event.target==element){return self._setData(key,value);}}).bind('getData.'+name,function(event,key){if(event.target==element){return self._getData(key);}}).bind('remove',function(){return self.destroy();});this._init();};$[namespace][name].prototype=$.extend({},$.widget.prototype,prototype);$[namespace][name].getterSetter='option';};$.widget.prototype={_init:function(){},destroy:function(){this.element.removeData(this.widgetName).removeClass(this.widgetBaseClass+'-disabled'+' '+this.namespace+'-state-disabled').removeAttr('aria-disabled');},option:function(key,value){var options=key,self=this;if(typeof key=="string"){if(value===undefined){return this._getData(key);}
options={};options[key]=value;}
$.each(options,function(key,value){self._setData(key,value);});},_getData:function(key){return this.options[key];},_setData:function(key,value){this.options[key]=value;if(key=='disabled'){this.element
[value?'addClass':'removeClass'](this.widgetBaseClass+'-disabled'+' '+
this.namespace+'-state-disabled').attr("aria-disabled",value);}},enable:function(){this._setData('disabled',false);},disable:function(){this._setData('disabled',true);},_trigger:function(type,event,data){var callback=this.options[type],eventName=(type==this.widgetEventPrefix?type:this.widgetEventPrefix+type);event=$.Event(event);event.type=eventName;this.element.trigger(event,data);return!($.isFunction(callback)&&callback.call(this.element[0],event,data)===false||event.isDefaultPrevented());}};$.widget.defaults={disabled:false};$.ui.mouse={_mouseInit:function(){var self=this;this.element.bind('mousedown.'+this.widgetName,function(event){return self._mouseDown(event);}).bind('click.'+this.widgetName,function(event){if(self._preventClickEvent){self._preventClickEvent=false;return false;}});if($.browser.msie){this._mouseUnselectable=this.element.attr('unselectable');this.element.attr('unselectable','on');}
this.started=false;},_mouseDestroy:function(){this.element.unbind('.'+this.widgetName);($.browser.msie&&this.element.attr('unselectable',this._mouseUnselectable));},_mouseDown:function(event){(this._mouseStarted&&this._mouseUp(event));this._mouseDownEvent=event;var self=this,btnIsLeft=(event.which==1),elIsCancel=(typeof this.options.cancel=="string"?$(event.target).parents().add(event.target).filter(this.options.cancel).length:false);if(!btnIsLeft||elIsCancel||!this._mouseCapture(event)){return true;}
this.mouseDelayMet=!this.options.delay;if(!this.mouseDelayMet){this._mouseDelayTimer=setTimeout(function(){self.mouseDelayMet=true;},this.options.delay);}
if(this._mouseDistanceMet(event)&&this._mouseDelayMet(event)){this._mouseStarted=(this._mouseStart(event)!==false);if(!this._mouseStarted){event.preventDefault();return true;}}
this._mouseMoveDelegate=function(event){return self._mouseMove(event);};this._mouseUpDelegate=function(event){return self._mouseUp(event);};$(document).bind('mousemove.'+this.widgetName,this._mouseMoveDelegate).bind('mouseup.'+this.widgetName,this._mouseUpDelegate);($.browser.safari||event.preventDefault());return true;},_mouseMove:function(event){if($.browser.msie&&!event.button){return this._mouseUp(event);}
if(this._mouseStarted){this._mouseDrag(event);return event.preventDefault();}
if(this._mouseDistanceMet(event)&&this._mouseDelayMet(event)){this._mouseStarted=(this._mouseStart(this._mouseDownEvent,event)!==false);(this._mouseStarted?this._mouseDrag(event):this._mouseUp(event));}
return!this._mouseStarted;},_mouseUp:function(event){$(document).unbind('mousemove.'+this.widgetName,this._mouseMoveDelegate).unbind('mouseup.'+this.widgetName,this._mouseUpDelegate);if(this._mouseStarted){this._mouseStarted=false;this._preventClickEvent=true;this._mouseStop(event);}
return false;},_mouseDistanceMet:function(event){return(Math.max(Math.abs(this._mouseDownEvent.pageX-event.pageX),Math.abs(this._mouseDownEvent.pageY-event.pageY))>=this.options.distance);},_mouseDelayMet:function(event){return this.mouseDelayMet;},_mouseStart:function(event){},_mouseDrag:function(event){},_mouseStop:function(event){},_mouseCapture:function(event){return true;}};$.ui.mouse.defaults={cancel:null,distance:1,delay:0};})(jQuery);(function($){$.widget("ui.draggable",$.extend({},$.ui.mouse,{_init:function(){if(this.options.helper=='original'&&!(/^(?:r|a|f)/).test(this.element.css("position")))
this.element[0].style.position='relative';(this.options.cssNamespace&&this.element.addClass(this.options.cssNamespace+"-draggable"));(this.options.disabled&&this.element.addClass(this.options.cssNamespace+'-draggable-disabled'));this._mouseInit();},destroy:function(){if(!this.element.data('draggable'))return;this.element.removeData("draggable").unbind(".draggable").removeClass(this.options.cssNamespace+'-draggable '+this.options.cssNamespace+'-draggable-dragging '+this.options.cssNamespace+'-draggable-disabled');this._mouseDestroy();},_mouseCapture:function(event){var o=this.options;if(this.helper||o.disabled||$(event.target).is('.'+this.options.cssNamespace+'-resizable-handle'))
return false;this.handle=this._getHandle(event);if(!this.handle)
return false;return true;},_mouseStart:function(event){var o=this.options;this.helper=this._createHelper(event);this._cacheHelperProportions();if($.ui.ddmanager)
$.ui.ddmanager.current=this;this._cacheMargins();this.cssPosition=this.helper.css("position");this.scrollParent=this.helper.scrollParent();this.offset=this.element.offset();this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left};$.extend(this.offset,{click:{left:event.pageX-this.offset.left,top:event.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()});this.originalPosition=this._generatePosition(event);this.originalPageX=event.pageX;this.originalPageY=event.pageY;if(o.cursorAt)
this._adjustOffsetFromHelper(o.cursorAt);if(o.containment)
this._setContainment();this._trigger("start",event);this._cacheHelperProportions();if($.ui.ddmanager&&!o.dropBehaviour)
$.ui.ddmanager.prepareOffsets(this,event);this.helper.addClass(o.cssNamespace+"-draggable-dragging");this._mouseDrag(event,true);return true;},_mouseDrag:function(event,noPropagation){this.position=this._generatePosition(event);this.positionAbs=this._convertPositionTo("absolute");if(!noPropagation){var ui=this._uiHash();this._trigger('drag',event,ui);this.position=ui.position;}
if(!this.options.axis||this.options.axis!="y")this.helper[0].style.left=this.position.left+'px';if(!this.options.axis||this.options.axis!="x")this.helper[0].style.top=this.position.top+'px';if($.ui.ddmanager)$.ui.ddmanager.drag(this,event);return false;},_mouseStop:function(event){var dropped=false;if($.ui.ddmanager&&!this.options.dropBehaviour)
dropped=$.ui.ddmanager.drop(this,event);if(this.dropped){dropped=this.dropped;this.dropped=false;}
if((this.options.revert=="invalid"&&!dropped)||(this.options.revert=="valid"&&dropped)||this.options.revert===true||($.isFunction(this.options.revert)&&this.options.revert.call(this.element,dropped))){var self=this;$(this.helper).animate(this.originalPosition,parseInt(this.options.revertDuration,10),function(){self._trigger("stop",event);self._clear();});}else{this._trigger("stop",event);this._clear();}
return false;},_getHandle:function(event){var handle=!this.options.handle||!$(this.options.handle,this.element).length?true:false;$(this.options.handle,this.element).find("*").andSelf().each(function(){if(this==event.target)handle=true;});return handle;},_createHelper:function(event){var o=this.options;var helper=$.isFunction(o.helper)?$(o.helper.apply(this.element[0],[event])):(o.helper=='clone'?this.element.clone():this.element);if(!helper.parents('body').length)
helper.appendTo((o.appendTo=='parent'?this.element[0].parentNode:o.appendTo));if(helper[0]!=this.element[0]&&!(/(fixed|absolute)/).test(helper.css("position")))
helper.css("position","absolute");return helper;},_adjustOffsetFromHelper:function(obj){if(obj.left!=undefined)this.offset.click.left=obj.left+this.margins.left;if(obj.right!=undefined)this.offset.click.left=this.helperProportions.width-obj.right+this.margins.left;if(obj.top!=undefined)this.offset.click.top=obj.top+this.margins.top;if(obj.bottom!=undefined)this.offset.click.top=this.helperProportions.height-obj.bottom+this.margins.top;},_getParentOffset:function(){this.offsetParent=this.helper.offsetParent();var po=this.offsetParent.offset();if(this.cssPosition=='absolute'&&this.scrollParent[0]!=document&&$.ui.contains(this.scrollParent[0],this.offsetParent[0])){po.left+=this.scrollParent.scrollLeft();po.top+=this.scrollParent.scrollTop();}
if((this.offsetParent[0]==document.body&&$.browser.mozilla)||(this.offsetParent[0].tagName&&this.offsetParent[0].tagName.toLowerCase()=='html'&&$.browser.msie))
po={top:0,left:0};return{top:po.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:po.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)};},_getRelativeOffset:function(){if(this.cssPosition=="relative"){var p=this.element.position();return{top:p.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:p.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()};}else{return{top:0,left:0};}},_cacheMargins:function(){this.margins={left:(parseInt(this.element.css("marginLeft"),10)||0),top:(parseInt(this.element.css("marginTop"),10)||0)};},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()};},_setContainment:function(){var o=this.options;if(o.containment=='parent')o.containment=this.helper[0].parentNode;if(o.containment=='document'||o.containment=='window')this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,$(o.containment=='document'?document:window).width()-this.helperProportions.width-this.margins.left,($(o.containment=='document'?document:window).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top];if(!(/^(document|window|parent)$/).test(o.containment)){var ce=$(o.containment)[0];var co=$(o.containment).offset();var over=($(ce).css("overflow")!='hidden');this.containment=[co.left+(parseInt($(ce).css("borderLeftWidth"),10)||0)-this.margins.left,co.top+(parseInt($(ce).css("borderTopWidth"),10)||0)-this.margins.top,co.left+(over?Math.max(ce.scrollWidth,ce.offsetWidth):ce.offsetWidth)-(parseInt($(ce).css("borderLeftWidth"),10)||0)-this.helperProportions.width-this.margins.left,co.top+(over?Math.max(ce.scrollHeight,ce.offsetHeight):ce.offsetHeight)-(parseInt($(ce).css("borderTopWidth"),10)||0)-this.helperProportions.height-this.margins.top];}},_convertPositionTo:function(d,pos){if(!pos)pos=this.position;var mod=d=="absolute"?1:-1;var o=this.options,scroll=this.cssPosition=='absolute'&&!(this.scrollParent[0]!=document&&$.ui.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,scrollIsRootNode=(/(html|body)/i).test(scroll[0].tagName);return{top:(pos.top
+this.offset.relative.top*mod
+this.offset.parent.top*mod
-(this.cssPosition=='fixed'?-this.scrollParent.scrollTop():(scrollIsRootNode?0:scroll.scrollTop()))*mod),left:(pos.left
+this.offset.relative.left*mod
+this.offset.parent.left*mod
-(this.cssPosition=='fixed'?-this.scrollParent.scrollLeft():scrollIsRootNode?0:scroll.scrollLeft())*mod)};},_generatePosition:function(event){var o=this.options,scroll=this.cssPosition=='absolute'&&!(this.scrollParent[0]!=document&&$.ui.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,scrollIsRootNode=(/(html|body)/i).test(scroll[0].tagName);if(this.cssPosition=='relative'&&!(this.scrollParent[0]!=document&&this.scrollParent[0]!=this.offsetParent[0])){this.offset.relative=this._getRelativeOffset();}
var pageX=event.pageX;var pageY=event.pageY;if(this.originalPosition){if(this.containment){if(event.pageX-this.offset.click.left<this.containment[0])pageX=this.containment[0]+this.offset.click.left;if(event.pageY-this.offset.click.top<this.containment[1])pageY=this.containment[1]+this.offset.click.top;if(event.pageX-this.offset.click.left>this.containment[2])pageX=this.containment[2]+this.offset.click.left;if(event.pageY-this.offset.click.top>this.containment[3])pageY=this.containment[3]+this.offset.click.top;}
if(o.grid){var top=this.originalPageY+Math.round((pageY-this.originalPageY)/o.grid[1])*o.grid[1];pageY=this.containment?(!(top-this.offset.click.top<this.containment[1]||top-this.offset.click.top>this.containment[3])?top:(!(top-this.offset.click.top<this.containment[1])?top-o.grid[1]:top+o.grid[1])):top;var left=this.originalPageX+Math.round((pageX-this.originalPageX)/o.grid[0])*o.grid[0];pageX=this.containment?(!(left-this.offset.click.left<this.containment[0]||left-this.offset.click.left>this.containment[2])?left:(!(left-this.offset.click.left<this.containment[0])?left-o.grid[0]:left+o.grid[0])):left;}}
return{top:(pageY
-this.offset.click.top
-this.offset.relative.top
-this.offset.parent.top
+(this.cssPosition=='fixed'?-this.scrollParent.scrollTop():(scrollIsRootNode?0:scroll.scrollTop()))),left:(pageX
-this.offset.click.left
-this.offset.relative.left
-this.offset.parent.left
+(this.cssPosition=='fixed'?-this.scrollParent.scrollLeft():scrollIsRootNode?0:scroll.scrollLeft()))};},_clear:function(){this.helper.removeClass(this.options.cssNamespace+"-draggable-dragging");if(this.helper[0]!=this.element[0]&&!this.cancelHelperRemoval)this.helper.remove();this.helper=null;this.cancelHelperRemoval=false;},_trigger:function(type,event,ui){ui=ui||this._uiHash();$.ui.plugin.call(this,type,[event,ui]);if(type=="drag")this.positionAbs=this._convertPositionTo("absolute");return $.widget.prototype._trigger.call(this,type,event,ui);},plugins:{},_uiHash:function(event){return{helper:this.helper,position:this.position,absolutePosition:this.positionAbs,options:this.options};}}));$.extend($.ui.draggable,{version:"1.6rc5",eventPrefix:"drag",defaults:{appendTo:"parent",axis:false,cancel:":input,option",connectToSortable:false,containment:false,cssNamespace:"ui",cursor:"default",cursorAt:null,delay:0,distance:1,grid:false,handle:false,helper:"original",iframeFix:false,opacity:null,refreshPositions:false,revert:false,revertDuration:500,scope:"default",scroll:true,scrollSensitivity:20,scrollSpeed:20,snap:false,snapMode:"both",snapTolerance:20,stack:false,zIndex:null}});$.ui.plugin.add("draggable","connectToSortable",{start:function(event,ui){var inst=$(this).data("draggable");inst.sortables=[];$(ui.options.connectToSortable).each(function(){$(this+'').each(function(){if($.data(this,'sortable')){var sortable=$.data(this,'sortable');inst.sortables.push({instance:sortable,shouldRevert:sortable.options.revert});sortable._refreshItems();sortable._trigger("activate",event,inst);}});});},stop:function(event,ui){var inst=$(this).data("draggable");$.each(inst.sortables,function(){if(this.instance.isOver){this.instance.isOver=0;inst.cancelHelperRemoval=true;this.instance.cancelHelperRemoval=false;if(this.shouldRevert)this.instance.options.revert=true;this.instance._mouseStop(event);this.instance.element.triggerHandler("sortreceive",[event,$.extend(this.instance._uiHash(),{sender:inst.element})],this.instance.options["receive"]);this.instance.options.helper=this.instance.options._helper;if(inst.options.helper=='original')
this.instance.currentItem.css({top:'auto',left:'auto'});}else{this.instance.cancelHelperRemoval=false;this.instance._trigger("deactivate",event,inst);}});},drag:function(event,ui){var inst=$(this).data("draggable"),self=this;var checkPos=function(o){var dyClick=this.offset.click.top,dxClick=this.offset.click.left;var helperTop=this.positionAbs.top,helperLeft=this.positionAbs.left;var itemHeight=o.height,itemWidth=o.width;var itemTop=o.top,itemLeft=o.left;return $.ui.isOver(helperTop+dyClick,helperLeft+dxClick,itemTop,itemLeft,itemHeight,itemWidth);};$.each(inst.sortables,function(i){if(checkPos.call(inst,this.instance.containerCache)){if(!this.instance.isOver){this.instance.isOver=1;this.instance.currentItem=$(self).clone().appendTo(this.instance.element).data("sortable-item",true);this.instance.options._helper=this.instance.options.helper;this.instance.options.helper=function(){return ui.helper[0];};event.target=this.instance.currentItem[0];this.instance._mouseCapture(event,true);this.instance._mouseStart(event,true,true);this.instance.offset.click.top=inst.offset.click.top;this.instance.offset.click.left=inst.offset.click.left;this.instance.offset.parent.left-=inst.offset.parent.left-this.instance.offset.parent.left;this.instance.offset.parent.top-=inst.offset.parent.top-this.instance.offset.parent.top;inst._trigger("toSortable",event);inst.dropped=this.instance.element;this.instance.fromOutside=true;}
if(this.instance.currentItem)this.instance._mouseDrag(event);}else{if(this.instance.isOver){this.instance.isOver=0;this.instance.cancelHelperRemoval=true;this.instance.options.revert=false;this.instance._mouseStop(event,true);this.instance.options.helper=this.instance.options._helper;this.instance.currentItem.remove();if(this.instance.placeholder)this.instance.placeholder.remove();inst._trigger("fromSortable",event);inst.dropped=false;}};});}});$.ui.plugin.add("draggable","cursor",{start:function(event,ui){var t=$('body');if(t.css("cursor"))ui.options._cursor=t.css("cursor");t.css("cursor",ui.options.cursor);},stop:function(event,ui){if(ui.options._cursor)$('body').css("cursor",ui.options._cursor);}});$.ui.plugin.add("draggable","iframeFix",{start:function(event,ui){$(ui.options.iframeFix===true?"iframe":ui.options.iframeFix).each(function(){$('<div class="ui-draggable-iframeFix" style="background: #fff;"></div>').css({width:this.offsetWidth+"px",height:this.offsetHeight+"px",position:"absolute",opacity:"0.001",zIndex:1000}).css($(this).offset()).appendTo("body");});},stop:function(event,ui){$("div.ui-draggable-iframeFix").each(function(){this.parentNode.removeChild(this);});}});$.ui.plugin.add("draggable","opacity",{start:function(event,ui){var t=$(ui.helper);if(t.css("opacity"))ui.options._opacity=t.css("opacity");t.css('opacity',ui.options.opacity);},stop:function(event,ui){if(ui.options._opacity)$(ui.helper).css('opacity',ui.options._opacity);}});$.ui.plugin.add("draggable","scroll",{start:function(event,ui){var o=ui.options;var i=$(this).data("draggable");if(i.scrollParent[0]!=document&&i.scrollParent[0].tagName!='HTML')i.overflowOffset=i.scrollParent.offset();},drag:function(event,ui){var o=ui.options,scrolled=false;var i=$(this).data("draggable");if(i.scrollParent[0]!=document&&i.scrollParent[0].tagName!='HTML'){if((i.overflowOffset.top+i.scrollParent[0].offsetHeight)-event.pageY<o.scrollSensitivity)
i.scrollParent[0].scrollTop=scrolled=i.scrollParent[0].scrollTop+o.scrollSpeed;else if(event.pageY-i.overflowOffset.top<o.scrollSensitivity)
i.scrollParent[0].scrollTop=scrolled=i.scrollParent[0].scrollTop-o.scrollSpeed;if((i.overflowOffset.left+i.scrollParent[0].offsetWidth)-event.pageX<o.scrollSensitivity)
i.scrollParent[0].scrollLeft=scrolled=i.scrollParent[0].scrollLeft+o.scrollSpeed;else if(event.pageX-i.overflowOffset.left<o.scrollSensitivity)
i.scrollParent[0].scrollLeft=scrolled=i.scrollParent[0].scrollLeft-o.scrollSpeed;}else{if(event.pageY-$(document).scrollTop()<o.scrollSensitivity)
scrolled=$(document).scrollTop($(document).scrollTop()-o.scrollSpeed);else if($(window).height()-(event.pageY-$(document).scrollTop())<o.scrollSensitivity)
scrolled=$(document).scrollTop($(document).scrollTop()+o.scrollSpeed);if(event.pageX-$(document).scrollLeft()<o.scrollSensitivity)
scrolled=$(document).scrollLeft($(document).scrollLeft()-o.scrollSpeed);else if($(window).width()-(event.pageX-$(document).scrollLeft())<o.scrollSensitivity)
scrolled=$(document).scrollLeft($(document).scrollLeft()+o.scrollSpeed);}
if(scrolled!==false&&$.ui.ddmanager&&!o.dropBehaviour)
$.ui.ddmanager.prepareOffsets(i,event);}});$.ui.plugin.add("draggable","snap",{start:function(event,ui){var inst=$(this).data("draggable");inst.snapElements=[];$(ui.options.snap.constructor!=String?(ui.options.snap.items||':data(draggable)'):ui.options.snap).each(function(){var $t=$(this);var $o=$t.offset();if(this!=inst.element[0])inst.snapElements.push({item:this,width:$t.outerWidth(),height:$t.outerHeight(),top:$o.top,left:$o.left});});},drag:function(event,ui){var inst=$(this).data("draggable");var d=ui.options.snapTolerance;var x1=ui.absolutePosition.left,x2=x1+inst.helperProportions.width,y1=ui.absolutePosition.top,y2=y1+inst.helperProportions.height;for(var i=inst.snapElements.length-1;i>=0;i--){var l=inst.snapElements[i].left,r=l+inst.snapElements[i].width,t=inst.snapElements[i].top,b=t+inst.snapElements[i].height;if(!((l-d<x1&&x1<r+d&&t-d<y1&&y1<b+d)||(l-d<x1&&x1<r+d&&t-d<y2&&y2<b+d)||(l-d<x2&&x2<r+d&&t-d<y1&&y1<b+d)||(l-d<x2&&x2<r+d&&t-d<y2&&y2<b+d))){if(inst.snapElements[i].snapping)(inst.options.snap.release&&inst.options.snap.release.call(inst.element,event,$.extend(inst._uiHash(),{snapItem:inst.snapElements[i].item})));inst.snapElements[i].snapping=false;continue;}
if(ui.options.snapMode!='inner'){var ts=Math.abs(t-y2)<=d;var bs=Math.abs(b-y1)<=d;var ls=Math.abs(l-x2)<=d;var rs=Math.abs(r-x1)<=d;if(ts)ui.position.top=inst._convertPositionTo("relative",{top:t-inst.helperProportions.height,left:0}).top;if(bs)ui.position.top=inst._convertPositionTo("relative",{top:b,left:0}).top;if(ls)ui.position.left=inst._convertPositionTo("relative",{top:0,left:l-inst.helperProportions.width}).left;if(rs)ui.position.left=inst._convertPositionTo("relative",{top:0,left:r}).left;}
var first=(ts||bs||ls||rs);if(ui.options.snapMode!='outer'){var ts=Math.abs(t-y1)<=d;var bs=Math.abs(b-y2)<=d;var ls=Math.abs(l-x1)<=d;var rs=Math.abs(r-x2)<=d;if(ts)ui.position.top=inst._convertPositionTo("relative",{top:t,left:0}).top;if(bs)ui.position.top=inst._convertPositionTo("relative",{top:b-inst.helperProportions.height,left:0}).top;if(ls)ui.position.left=inst._convertPositionTo("relative",{top:0,left:l}).left;if(rs)ui.position.left=inst._convertPositionTo("relative",{top:0,left:r-inst.helperProportions.width}).left;}
if(!inst.snapElements[i].snapping&&(ts||bs||ls||rs||first))
(inst.options.snap.snap&&inst.options.snap.snap.call(inst.element,event,$.extend(inst._uiHash(),{snapItem:inst.snapElements[i].item})));inst.snapElements[i].snapping=(ts||bs||ls||rs||first);};}});$.ui.plugin.add("draggable","stack",{start:function(event,ui){var group=$.makeArray($(ui.options.stack.group)).sort(function(a,b){return(parseInt($(a).css("zIndex"),10)||ui.options.stack.min)-(parseInt($(b).css("zIndex"),10)||ui.options.stack.min);});$(group).each(function(i){this.style.zIndex=ui.options.stack.min+i;});this[0].style.zIndex=ui.options.stack.min+group.length;}});$.ui.plugin.add("draggable","zIndex",{start:function(event,ui){var t=$(ui.helper);if(t.css("zIndex"))ui.options._zIndex=t.css("zIndex");t.css('zIndex',ui.options.zIndex);},stop:function(event,ui){if(ui.options._zIndex)$(ui.helper).css('zIndex',ui.options._zIndex);}});})(jQuery);(function($){$.widget("ui.droppable",{_init:function(){var o=this.options,accept=o.accept;this.isover=0;this.isout=1;this.options.accept=this.options.accept&&$.isFunction(this.options.accept)?this.options.accept:function(d){return d.is(accept);};this.proportions={width:this.element[0].offsetWidth,height:this.element[0].offsetHeight};$.ui.ddmanager.droppables[this.options.scope]=$.ui.ddmanager.droppables[this.options.scope]||[];$.ui.ddmanager.droppables[this.options.scope].push(this);(this.options.cssNamespace&&this.element.addClass(this.options.cssNamespace+"-droppable"));},destroy:function(){var drop=$.ui.ddmanager.droppables[this.options.scope];for(var i=0;i<drop.length;i++)
if(drop[i]==this)
drop.splice(i,1);this.element.removeClass(this.options.cssNamespace+"-droppable "+this.options.cssNamespace+"-droppable-disabled").removeData("droppable").unbind(".droppable");},_setData:function(key,value){if(key=='accept'){this.options.accept=value&&$.isFunction(value)?value:function(d){return d.is(accept);};}else{$.widget.prototype._setData.apply(this,arguments);}},_activate:function(event){var draggable=$.ui.ddmanager.current;$.ui.plugin.call(this,'activate',[event,this.ui(draggable)]);(draggable&&this._trigger('activate',event,this.ui(draggable)));},_deactivate:function(event){var draggable=$.ui.ddmanager.current;$.ui.plugin.call(this,'deactivate',[event,this.ui(draggable)]);(draggable&&this._trigger('deactivate',event,this.ui(draggable)));},_over:function(event){var draggable=$.ui.ddmanager.current;if(!draggable||(draggable.currentItem||draggable.element)[0]==this.element[0])return;if(this.options.accept.call(this.element,(draggable.currentItem||draggable.element))){$.ui.plugin.call(this,'over',[event,this.ui(draggable)]);this._trigger('over',event,this.ui(draggable));}},_out:function(event){var draggable=$.ui.ddmanager.current;if(!draggable||(draggable.currentItem||draggable.element)[0]==this.element[0])return;if(this.options.accept.call(this.element,(draggable.currentItem||draggable.element))){$.ui.plugin.call(this,'out',[event,this.ui(draggable)]);this._trigger('out',event,this.ui(draggable));}},_drop:function(event,custom){var draggable=custom||$.ui.ddmanager.current;if(!draggable||(draggable.currentItem||draggable.element)[0]==this.element[0])return false;var childrenIntersection=false;this.element.find(":data(droppable)").not("."+draggable.options.cssNamespace+"-draggable-dragging").each(function(){var inst=$.data(this,'droppable');if(inst.options.greedy&&$.ui.intersect(draggable,$.extend(inst,{offset:inst.element.offset()}),inst.options.tolerance)){childrenIntersection=true;return false;}});if(childrenIntersection)return false;if(this.options.accept.call(this.element,(draggable.currentItem||draggable.element))){$.ui.plugin.call(this,'drop',[event,this.ui(draggable)]);this._trigger('drop',event,this.ui(draggable));return this.element;}
return false;},plugins:{},ui:function(c){return{draggable:(c.currentItem||c.element),helper:c.helper,position:c.position,absolutePosition:c.positionAbs,options:this.options,element:this.element};}});$.extend($.ui.droppable,{version:"1.6rc5",eventPrefix:'drop',defaults:{accept:'*',activeClass:null,cssNamespace:'ui',greedy:false,hoverClass:null,scope:'default',tolerance:'intersect'}});$.ui.intersect=function(draggable,droppable,toleranceMode){if(!droppable.offset)return false;var x1=(draggable.positionAbs||draggable.position.absolute).left,x2=x1+draggable.helperProportions.width,y1=(draggable.positionAbs||draggable.position.absolute).top,y2=y1+draggable.helperProportions.height;var l=droppable.offset.left,r=l+droppable.proportions.width,t=droppable.offset.top,b=t+droppable.proportions.height;switch(toleranceMode){case'fit':return(l<x1&&x2<r&&t<y1&&y2<b);break;case'intersect':return(l<x1+(draggable.helperProportions.width/2)&&x2-(draggable.helperProportions.width/2)<r&&t<y1+(draggable.helperProportions.height/2)&&y2-(draggable.helperProportions.height/2)<b);break;case'pointer':var draggableLeft=((draggable.positionAbs||draggable.position.absolute).left+(draggable.clickOffset||draggable.offset.click).left),draggableTop=((draggable.positionAbs||draggable.position.absolute).top+(draggable.clickOffset||draggable.offset.click).top),isOver=$.ui.isOver(draggableTop,draggableLeft,t,l,droppable.proportions.height,droppable.proportions.width);return isOver;break;case'touch':return((y1>=t&&y1<=b)||(y2>=t&&y2<=b)||(y1<t&&y2>b))&&((x1>=l&&x1<=r)||(x2>=l&&x2<=r)||(x1<l&&x2>r));break;default:return false;break;}};$.ui.ddmanager={current:null,droppables:{'default':[]},prepareOffsets:function(t,event){var m=$.ui.ddmanager.droppables[t.options.scope];var type=event?event.type:null;var list=(t.currentItem||t.element).find(":data(droppable)").andSelf();droppablesLoop:for(var i=0;i<m.length;i++){if(m[i].options.disabled||(t&&!m[i].options.accept.call(m[i].element,(t.currentItem||t.element))))continue;for(var j=0;j<list.length;j++){if(list[j]==m[i].element[0]){m[i].proportions.height=0;continue droppablesLoop;}};m[i].visible=m[i].element.css("display")!="none";if(!m[i].visible)continue;m[i].offset=m[i].element.offset();m[i].proportions={width:m[i].element[0].offsetWidth,height:m[i].element[0].offsetHeight};if(type=="dragstart"||type=="sortactivate")m[i]._activate.call(m[i],event);}},drop:function(draggable,event){var dropped=false;$.each($.ui.ddmanager.droppables[draggable.options.scope],function(){if(!this.options)return;if(!this.options.disabled&&this.visible&&$.ui.intersect(draggable,this,this.options.tolerance))
dropped=this._drop.call(this,event);if(!this.options.disabled&&this.visible&&this.options.accept.call(this.element,(draggable.currentItem||draggable.element))){this.isout=1;this.isover=0;this._deactivate.call(this,event);}});return dropped;},drag:function(draggable,event){if(draggable.options.refreshPositions)$.ui.ddmanager.prepareOffsets(draggable,event);$.each($.ui.ddmanager.droppables[draggable.options.scope],function(){if(this.options.disabled||this.greedyChild||!this.visible)return;var intersects=$.ui.intersect(draggable,this,this.options.tolerance);var c=!intersects&&this.isover==1?'isout':(intersects&&this.isover==0?'isover':null);if(!c)return;var parentInstance;if(this.options.greedy){var parent=this.element.parents(':data(droppable):eq(0)');if(parent.length){parentInstance=$.data(parent[0],'droppable');parentInstance.greedyChild=(c=='isover'?1:0);}}
if(parentInstance&&c=='isover'){parentInstance['isover']=0;parentInstance['isout']=1;parentInstance._out.call(parentInstance,event);}
this[c]=1;this[c=='isout'?'isover':'isout']=0;this[c=="isover"?"_over":"_out"].call(this,event);if(parentInstance&&c=='isout'){parentInstance['isout']=0;parentInstance['isover']=1;parentInstance._over.call(parentInstance,event);}});}};$.ui.plugin.add("droppable","activeClass",{activate:function(event,ui){$(this).addClass(ui.options.activeClass);},deactivate:function(event,ui){$(this).removeClass(ui.options.activeClass);},drop:function(event,ui){$(this).removeClass(ui.options.activeClass);}});$.ui.plugin.add("droppable","hoverClass",{over:function(event,ui){$(this).addClass(ui.options.hoverClass);},out:function(event,ui){$(this).removeClass(ui.options.hoverClass);},drop:function(event,ui){$(this).removeClass(ui.options.hoverClass);}});})(jQuery);(function($){$.widget("ui.sortable",$.extend({},$.ui.mouse,{_init:function(){var o=this.options;this.containerCache={};(this.options.cssNamespace&&this.element.addClass(this.options.cssNamespace+"-sortable"));this.refresh();this.floating=this.items.length?(/left|right/).test(this.items[0].item.css('float')):false;this.offset=this.element.offset();this._mouseInit();},destroy:function(){this.element.removeClass(this.options.cssNamespace+"-sortable "+this.options.cssNamespace+"-sortable-disabled").removeData("sortable").unbind(".sortable");this._mouseDestroy();for(var i=this.items.length-1;i>=0;i--)
this.items[i].item.removeData("sortable-item");},_mouseCapture:function(event,overrideHandle){if(this.reverting){return false;}
if(this.options.disabled||this.options.type=='static')return false;this._refreshItems(event);var currentItem=null,self=this,nodes=$(event.target).parents().each(function(){if($.data(this,'sortable-item')==self){currentItem=$(this);return false;}});if($.data(event.target,'sortable-item')==self)currentItem=$(event.target);if(!currentItem)return false;if(this.options.handle&&!overrideHandle){var validHandle=false;$(this.options.handle,currentItem).find("*").andSelf().each(function(){if(this==event.target)validHandle=true;});if(!validHandle)return false;}
this.currentItem=currentItem;this._removeCurrentsFromItems();return true;},_mouseStart:function(event,overrideHandle,noActivation){var o=this.options;this.currentContainer=this;this.refreshPositions();this.helper=this._createHelper(event);this._cacheHelperProportions();this._cacheMargins();this.scrollParent=this.helper.scrollParent();this.offset=this.currentItem.offset();this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left};this.helper.css("position","absolute");this.cssPosition=this.helper.css("position");$.extend(this.offset,{click:{left:event.pageX-this.offset.left,top:event.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()});this.originalPosition=this._generatePosition(event);this.originalPageX=event.pageX;this.originalPageY=event.pageY;if(o.cursorAt)
this._adjustOffsetFromHelper(o.cursorAt);this.domPosition={prev:this.currentItem.prev()[0],parent:this.currentItem.parent()[0]};if(this.helper[0]!=this.currentItem[0]){this.currentItem.hide();}
this._createPlaceholder();if(o.containment)
this._setContainment();this._trigger("start",event);if(!this._preserveHelperProportions)
this._cacheHelperProportions();if(!noActivation){for(var i=this.containers.length-1;i>=0;i--){this.containers[i]._trigger("activate",event,this);}}
if($.ui.ddmanager)
$.ui.ddmanager.current=this;if($.ui.ddmanager&&!o.dropBehaviour)
$.ui.ddmanager.prepareOffsets(this,event);this.dragging=true;this.helper.addClass(o.cssNamespace+'-sortable-helper');this._mouseDrag(event);return true;},_mouseDrag:function(event){this.position=this._generatePosition(event);this.positionAbs=this._convertPositionTo("absolute");if(!this.lastPositionAbs){this.lastPositionAbs=this.positionAbs;}
$.ui.plugin.call(this,"sort",[event,this._uiHash()]);this.positionAbs=this._convertPositionTo("absolute");if(!this.options.axis||this.options.axis!="y")this.helper[0].style.left=this.position.left+'px';if(!this.options.axis||this.options.axis!="x")this.helper[0].style.top=this.position.top+'px';for(var i=this.items.length-1;i>=0;i--){var item=this.items[i],itemElement=item.item[0],intersection=this._intersectsWithPointer(item);if(!intersection)continue;if(itemElement!=this.currentItem[0]&&this.placeholder[intersection==1?"next":"prev"]()[0]!=itemElement&&!$.ui.contains(this.placeholder[0],itemElement)&&(this.options.type=='semi-dynamic'?!$.ui.contains(this.element[0],itemElement):true)){this.direction=intersection==1?"down":"up";if(this.options.tolerance=="pointer"||this._intersectsWithSides(item)){this.options.sortIndicator.call(this,event,item);}else{break;}
this._trigger("change",event);break;}}
this._contactContainers(event);if($.ui.ddmanager)$.ui.ddmanager.drag(this,event);this._trigger('sort',event);this.lastPositionAbs=this.positionAbs;return false;},_mouseStop:function(event,noPropagation){if(!event)return;if($.ui.ddmanager&&!this.options.dropBehaviour)
$.ui.ddmanager.drop(this,event);if(this.options.revert){var self=this;var cur=self.placeholder.offset();self.reverting=true;$(this.helper).animate({left:cur.left-this.offset.parent.left-self.margins.left+(this.offsetParent[0]==document.body?0:this.offsetParent[0].scrollLeft),top:cur.top-this.offset.parent.top-self.margins.top+(this.offsetParent[0]==document.body?0:this.offsetParent[0].scrollTop)},parseInt(this.options.revert,10)||500,function(){self._clear(event);});}else{this._clear(event,noPropagation);}
return false;},cancel:function(){if(this.dragging){this._mouseUp();if(this.options.helper=="original")
this.currentItem.css(this._storedCSS).removeClass(this.options.cssNamespace+"-sortable-helper");else
this.currentItem.show();for(var i=this.containers.length-1;i>=0;i--){this.containers[i]._trigger("deactivate",null,this);if(this.containers[i].containerCache.over){this.containers[i]._trigger("out",null,this);this.containers[i].containerCache.over=0;}}}
if(this.placeholder[0].parentNode)this.placeholder[0].parentNode.removeChild(this.placeholder[0]);if(this.options.helper!="original"&&this.helper&&this.helper[0].parentNode)this.helper.remove();$.extend(this,{helper:null,dragging:false,reverting:false,_noFinalSort:null});if(this.domPosition.prev){$(this.domPosition.prev).after(this.currentItem);}else{$(this.domPosition.parent).prepend(this.currentItem);}
return true;},serialize:function(o){var items=this._getItemsAsjQuery(o&&o.connected);var str=[];o=o||{};$(items).each(function(){var res=($(o.item||this).attr(o.attribute||'id')||'').match(o.expression||(/(.+)[-=_](.+)/));if(res)str.push((o.key||res[1]+'[]')+'='+(o.key&&o.expression?res[1]:res[2]));});return str.join('&');},toArray:function(o){var items=this._getItemsAsjQuery(o&&o.connected);var ret=[];o=o||{};items.each(function(){ret.push($(o.item||this).attr(o.attribute||'id')||'');});return ret;},_intersectsWith:function(item){var x1=this.positionAbs.left,x2=x1+this.helperProportions.width,y1=this.positionAbs.top,y2=y1+this.helperProportions.height;var l=item.left,r=l+item.width,t=item.top,b=t+item.height;var dyClick=this.offset.click.top,dxClick=this.offset.click.left;var isOverElement=(y1+dyClick)>t&&(y1+dyClick)<b&&(x1+dxClick)>l&&(x1+dxClick)<r;if(this.options.tolerance=="pointer"||this.options.forcePointerForContainers||(this.options.tolerance!="pointer"&&this.helperProportions[this.floating?'width':'height']>item[this.floating?'width':'height'])){return isOverElement;}else{return(l<x1+(this.helperProportions.width/2)&&x2-(this.helperProportions.width/2)<r&&t<y1+(this.helperProportions.height/2)&&y2-(this.helperProportions.height/2)<b);}},_intersectsWithPointer:function(item){var isOverElementHeight=$.ui.isOverAxis(this.positionAbs.top+this.offset.click.top,item.top,item.height),isOverElementWidth=$.ui.isOverAxis(this.positionAbs.left+this.offset.click.left,item.left,item.width),isOverElement=isOverElementHeight&&isOverElementWidth,verticalDirection=this._getDragVerticalDirection(),horizontalDirection=this._getDragHorizontalDirection();if(!isOverElement)
return false;return this.floating?(((horizontalDirection&&horizontalDirection=="right")||verticalDirection=="down")?2:1):(verticalDirection&&(verticalDirection=="down"?2:1));},_intersectsWithSides:function(item){var isOverBottomHalf=$.ui.isOverAxis(this.positionAbs.top+this.offset.click.top,item.top+(item.height/2),item.height),isOverRightHalf=$.ui.isOverAxis(this.positionAbs.left+this.offset.click.left,item.left+(item.width/2),item.width),verticalDirection=this._getDragVerticalDirection(),horizontalDirection=this._getDragHorizontalDirection();if(this.floating&&horizontalDirection){return((horizontalDirection=="right"&&isOverRightHalf)||(horizontalDirection=="left"&&!isOverRightHalf));}else{return verticalDirection&&((verticalDirection=="down"&&isOverBottomHalf)||(verticalDirection=="up"&&!isOverBottomHalf));}},_getDragVerticalDirection:function(){var delta=this.positionAbs.top-this.lastPositionAbs.top;return delta!=0&&(delta>0?"down":"up");},_getDragHorizontalDirection:function(){var delta=this.positionAbs.left-this.lastPositionAbs.left;return delta!=0&&(delta>0?"right":"left");},refresh:function(event){this._refreshItems(event);this.refreshPositions();},_getItemsAsjQuery:function(connected){var self=this;var items=[];var queries=[];if(this.options.connectWith&&connected){for(var i=this.options.connectWith.length-1;i>=0;i--){var cur=$(this.options.connectWith[i]);for(var j=cur.length-1;j>=0;j--){var inst=$.data(cur[j],'sortable');if(inst&&inst!=this&&!inst.options.disabled){queries.push([$.isFunction(inst.options.items)?inst.options.items.call(inst.element):$(inst.options.items,inst.element).not("."+inst.options.cssNamespace+"-sortable-helper"),inst]);}};};}
queries.push([$.isFunction(this.options.items)?this.options.items.call(this.element,null,{options:this.options,item:this.currentItem}):$(this.options.items,this.element).not("."+this.options.cssNamespace+"-sortable-helper"),this]);for(var i=queries.length-1;i>=0;i--){queries[i][0].each(function(){items.push(this);});};return $(items);},_removeCurrentsFromItems:function(){var list=this.currentItem.find(":data(sortable-item)");for(var i=0;i<this.items.length;i++){for(var j=0;j<list.length;j++){if(list[j]==this.items[i].item[0])
this.items.splice(i,1);};};},_refreshItems:function(event){this.items=[];this.containers=[this];var items=this.items;var self=this;var queries=[[$.isFunction(this.options.items)?this.options.items.call(this.element[0],event,{item:this.currentItem}):$(this.options.items,this.element),this]];if(this.options.connectWith){for(var i=this.options.connectWith.length-1;i>=0;i--){var cur=$(this.options.connectWith[i]);for(var j=cur.length-1;j>=0;j--){var inst=$.data(cur[j],'sortable');if(inst&&inst!=this&&!inst.options.disabled){queries.push([$.isFunction(inst.options.items)?inst.options.items.call(inst.element[0],event,{item:this.currentItem}):$(inst.options.items,inst.element),inst]);this.containers.push(inst);}};};}
for(var i=queries.length-1;i>=0;i--){var targetData=queries[i][1];var _queries=queries[i][0];for(var j=0,queriesLength=_queries.length;j<queriesLength;j++){var item=$(_queries[j]);item.data('sortable-item',targetData);items.push({item:item,instance:targetData,width:0,height:0,left:0,top:0});};};},refreshPositions:function(fast){if(this.offsetParent&&this.helper){this.offset.parent=this._getParentOffset();}
for(var i=this.items.length-1;i>=0;i--){var item=this.items[i];if(item.instance!=this.currentContainer&&this.currentContainer&&item.item[0]!=this.currentItem[0])
continue;var t=this.options.toleranceElement?$(this.options.toleranceElement,item.item):item.item;if(!fast){if(this.options.accurateIntersection){item.width=t.outerWidth();item.height=t.outerHeight();}
else{item.width=t[0].offsetWidth;item.height=t[0].offsetHeight;}}
var p=t.offset();item.left=p.left;item.top=p.top;};if(this.options.custom&&this.options.custom.refreshContainers){this.options.custom.refreshContainers.call(this);}else{for(var i=this.containers.length-1;i>=0;i--){var p=this.containers[i].element.offset();this.containers[i].containerCache.left=p.left;this.containers[i].containerCache.top=p.top;this.containers[i].containerCache.width=this.containers[i].element.outerWidth();this.containers[i].containerCache.height=this.containers[i].element.outerHeight();};}},_createPlaceholder:function(that){var self=that||this,o=self.options;if(!o.placeholder||o.placeholder.constructor==String){var className=o.placeholder;o.placeholder={element:function(){var el=$(document.createElement(self.currentItem[0].nodeName)).addClass(className||self.currentItem[0].className+" "+self.options.cssNamespace+"-sortable-placeholder").removeClass(self.options.cssNamespace+'-sortable-helper')[0];if(!className)
el.style.visibility="hidden";return el;},update:function(container,p){if(className&&!o.forcePlaceholderSize)return;if(!p.height()){p.height(self.currentItem.innerHeight()-parseInt(self.currentItem.css('paddingTop')||0,10)-parseInt(self.currentItem.css('paddingBottom')||0,10));};if(!p.width()){p.width(self.currentItem.innerWidth()-parseInt(self.currentItem.css('paddingLeft')||0,10)-parseInt(self.currentItem.css('paddingRight')||0,10));};}};}
self.placeholder=$(o.placeholder.element.call(self.element,self.currentItem));self.currentItem.after(self.placeholder);o.placeholder.update(self,self.placeholder);},_contactContainers:function(event){for(var i=this.containers.length-1;i>=0;i--){if(this._intersectsWith(this.containers[i].containerCache)){if(!this.containers[i].containerCache.over){if(this.currentContainer!=this.containers[i]){var dist=10000;var itemWithLeastDistance=null;var base=this.positionAbs[this.containers[i].floating?'left':'top'];for(var j=this.items.length-1;j>=0;j--){if(!$.ui.contains(this.containers[i].element[0],this.items[j].item[0]))continue;var cur=this.items[j][this.containers[i].floating?'left':'top'];if(Math.abs(cur-base)<dist){dist=Math.abs(cur-base);itemWithLeastDistance=this.items[j];}}
if(!itemWithLeastDistance&&!this.options.dropOnEmpty)
continue;this.currentContainer=this.containers[i];itemWithLeastDistance?this.options.sortIndicator.call(this,event,itemWithLeastDistance,null,true):this.options.sortIndicator.call(this,event,null,this.containers[i].element,true);this._trigger("change",event);this.containers[i]._trigger("change",event,this);this.options.placeholder.update(this.currentContainer,this.placeholder);}
this.containers[i]._trigger("over",event,this);this.containers[i].containerCache.over=1;}}else{if(this.containers[i].containerCache.over){this.containers[i]._trigger("out",event,this);this.containers[i].containerCache.over=0;}}};},_createHelper:function(event){var o=this.options;var helper=$.isFunction(o.helper)?$(o.helper.apply(this.element[0],[event,this.currentItem])):(o.helper=='clone'?this.currentItem.clone():this.currentItem);if(!helper.parents('body').length)
$(o.appendTo!='parent'?o.appendTo:this.currentItem[0].parentNode)[0].appendChild(helper[0]);if(helper[0]==this.currentItem[0])
this._storedCSS={width:this.currentItem[0].style.width,height:this.currentItem[0].style.height,position:this.currentItem.css("position"),top:this.currentItem.css("top"),left:this.currentItem.css("left")};if(helper[0].style.width==''||o.forceHelperSize)helper.width(this.currentItem.width());if(helper[0].style.height==''||o.forceHelperSize)helper.height(this.currentItem.height());return helper;},_adjustOffsetFromHelper:function(obj){if(obj.left!=undefined)this.offset.click.left=obj.left+this.margins.left;if(obj.right!=undefined)this.offset.click.left=this.helperProportions.width-obj.right+this.margins.left;if(obj.top!=undefined)this.offset.click.top=obj.top+this.margins.top;if(obj.bottom!=undefined)this.offset.click.top=this.helperProportions.height-obj.bottom+this.margins.top;},_getParentOffset:function(){this.offsetParent=this.helper.offsetParent();var po=this.offsetParent.offset();if(this.cssPosition=='absolute'&&this.scrollParent[0]!=document&&$.ui.contains(this.scrollParent[0],this.offsetParent[0])){po.left+=this.scrollParent.scrollLeft();po.top+=this.scrollParent.scrollTop();}
if((this.offsetParent[0]==document.body&&$.browser.mozilla)||(this.offsetParent[0].tagName&&this.offsetParent[0].tagName.toLowerCase()=='html'&&$.browser.msie))
po={top:0,left:0};return{top:po.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:po.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)};},_getRelativeOffset:function(){if(this.cssPosition=="relative"){var p=this.currentItem.position();return{top:p.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:p.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()};}else{return{top:0,left:0};}},_cacheMargins:function(){this.margins={left:(parseInt(this.currentItem.css("marginLeft"),10)||0),top:(parseInt(this.currentItem.css("marginTop"),10)||0)};},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()};},_setContainment:function(){var o=this.options;if(o.containment=='parent')o.containment=this.helper[0].parentNode;if(o.containment=='document'||o.containment=='window')this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,$(o.containment=='document'?document:window).width()-this.helperProportions.width-this.margins.left,($(o.containment=='document'?document:window).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top];if(!(/^(document|window|parent)$/).test(o.containment)){var ce=$(o.containment)[0];var co=$(o.containment).offset();var over=($(ce).css("overflow")!='hidden');this.containment=[co.left+(parseInt($(ce).css("borderLeftWidth"),10)||0)-this.margins.left,co.top+(parseInt($(ce).css("borderTopWidth"),10)||0)-this.margins.top,co.left+(over?Math.max(ce.scrollWidth,ce.offsetWidth):ce.offsetWidth)-(parseInt($(ce).css("borderLeftWidth"),10)||0)-this.helperProportions.width-this.margins.left,co.top+(over?Math.max(ce.scrollHeight,ce.offsetHeight):ce.offsetHeight)-(parseInt($(ce).css("borderTopWidth"),10)||0)-this.helperProportions.height-this.margins.top];}},_convertPositionTo:function(d,pos){if(!pos)pos=this.position;var mod=d=="absolute"?1:-1;var o=this.options,scroll=this.cssPosition=='absolute'&&!(this.scrollParent[0]!=document&&$.ui.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,scrollIsRootNode=(/(html|body)/i).test(scroll[0].tagName);return{top:(pos.top
+this.offset.relative.top*mod
+this.offset.parent.top*mod
-(this.cssPosition=='fixed'?-this.scrollParent.scrollTop():(scrollIsRootNode?0:scroll.scrollTop()))*mod),left:(pos.left
+this.offset.relative.left*mod
+this.offset.parent.left*mod
-(this.cssPosition=='fixed'?-this.scrollParent.scrollLeft():scrollIsRootNode?0:scroll.scrollLeft())*mod)};},_generatePosition:function(event){var o=this.options,scroll=this.cssPosition=='absolute'&&!(this.scrollParent[0]!=document&&$.ui.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,scrollIsRootNode=(/(html|body)/i).test(scroll[0].tagName);if(this.cssPosition=='relative'&&!(this.scrollParent[0]!=document&&this.scrollParent[0]!=this.offsetParent[0])){this.offset.relative=this._getRelativeOffset();}
var pageX=event.pageX;var pageY=event.pageY;if(this.originalPosition){if(this.containment){if(event.pageX-this.offset.click.left<this.containment[0])pageX=this.containment[0]+this.offset.click.left;if(event.pageY-this.offset.click.top<this.containment[1])pageY=this.containment[1]+this.offset.click.top;if(event.pageX-this.offset.click.left>this.containment[2])pageX=this.containment[2]+this.offset.click.left;if(event.pageY-this.offset.click.top>this.containment[3])pageY=this.containment[3]+this.offset.click.top;}
if(o.grid){var top=this.originalPageY+Math.round((pageY-this.originalPageY)/o.grid[1])*o.grid[1];pageY=this.containment?(!(top-this.offset.click.top<this.containment[1]||top-this.offset.click.top>this.containment[3])?top:(!(top-this.offset.click.top<this.containment[1])?top-o.grid[1]:top+o.grid[1])):top;var left=this.originalPageX+Math.round((pageX-this.originalPageX)/o.grid[0])*o.grid[0];pageX=this.containment?(!(left-this.offset.click.left<this.containment[0]||left-this.offset.click.left>this.containment[2])?left:(!(left-this.offset.click.left<this.containment[0])?left-o.grid[0]:left+o.grid[0])):left;}}
return{top:(pageY
-this.offset.click.top
-this.offset.relative.top
-this.offset.parent.top
+(this.cssPosition=='fixed'?-this.scrollParent.scrollTop():(scrollIsRootNode?0:scroll.scrollTop()))),left:(pageX
-this.offset.click.left
-this.offset.relative.left
-this.offset.parent.left
+(this.cssPosition=='fixed'?-this.scrollParent.scrollLeft():scrollIsRootNode?0:scroll.scrollLeft()))};},_rearrange:function(event,i,a,hardRefresh){a?a[0].appendChild(this.placeholder[0]):i.item[0].parentNode.insertBefore(this.placeholder[0],(this.direction=='down'?i.item[0]:i.item[0].nextSibling));this.counter=this.counter?++this.counter:1;var self=this,counter=this.counter;window.setTimeout(function(){if(counter==self.counter)self.refreshPositions(!hardRefresh);},0);},_clear:function(event,noPropagation){this.reverting=false;if(!this._noFinalSort)this.placeholder.before(this.currentItem);this._noFinalSort=null;if(this.helper[0]==this.currentItem[0]){for(var i in this._storedCSS){if(this._storedCSS[i]=='auto'||this._storedCSS[i]=='static')this._storedCSS[i]='';}
this.currentItem.css(this._storedCSS).removeClass(this.options.cssNamespace+"-sortable-helper");}else{this.currentItem.show();}
if(this.fromOutside)this._trigger("receive",event,this,noPropagation);if(this.fromOutside||this.domPosition.prev!=this.currentItem.prev().not("."+this.options.cssNamespace+"-sortable-helper")[0]||this.domPosition.parent!=this.currentItem.parent()[0])this._trigger("update",event,null,noPropagation);if(!$.ui.contains(this.element[0],this.currentItem[0])){this._trigger("remove",event,null,noPropagation);for(var i=this.containers.length-1;i>=0;i--){if($.ui.contains(this.containers[i].element[0],this.currentItem[0])){this.containers[i]._trigger("receive",event,this,noPropagation);this.containers[i]._trigger("update",event,this,noPropagation);}};};for(var i=this.containers.length-1;i>=0;i--){this.containers[i]._trigger("deactivate",event,this,noPropagation);if(this.containers[i].containerCache.over){this.containers[i]._trigger("out",event,this);this.containers[i].containerCache.over=0;}}
this.dragging=false;if(this.cancelHelperRemoval){this._trigger("beforeStop",event,null,noPropagation);this._trigger("stop",event,null,noPropagation);return false;}
this._trigger("beforeStop",event,null,noPropagation);this.placeholder[0].parentNode.removeChild(this.placeholder[0]);if(this.helper[0]!=this.currentItem[0])this.helper.remove();this.helper=null;this._trigger("stop",event,null,noPropagation);this.fromOutside=false;return true;},_trigger:function(type,event,inst,noPropagation){$.ui.plugin.call(this,type,[event,this._uiHash(inst)]);if(!noPropagation){if($.widget.prototype._trigger.call(this,type,event,this._uiHash(inst))===false){this.cancel();}}},plugins:{},_uiHash:function(inst){var self=inst||this;return{helper:self.helper,placeholder:self.placeholder||$([]),position:self.position,absolutePosition:self.positionAbs,item:self.currentItem,sender:inst?inst.element:null};}}));$.extend($.ui.sortable,{getter:"serialize toArray",version:"1.6rc5",defaults:{accurateIntersection:true,appendTo:"parent",cancel:":input,option",cssNamespace:'ui',delay:0,distance:1,dropOnEmpty:true,forcePlaceholderSize:false,forceHelperSize:false,helper:"original",items:'> *',scope:"default",scroll:true,scrollSensitivity:20,scrollSpeed:20,sortIndicator:$.ui.sortable.prototype._rearrange,tolerance:"default",zIndex:1000}});$.ui.plugin.add("sortable","cursor",{start:function(event,ui){var t=$('body'),i=$(this).data('sortable');if(t.css("cursor"))i.options._cursor=t.css("cursor");t.css("cursor",i.options.cursor);},beforeStop:function(event,ui){var i=$(this).data('sortable');if(i.options._cursor)$('body').css("cursor",i.options._cursor);}});$.ui.plugin.add("sortable","opacity",{start:function(event,ui){var t=ui.helper,i=$(this).data('sortable');if(t.css("opacity"))i.options._opacity=t.css("opacity");t.css('opacity',i.options.opacity);},beforeStop:function(event,ui){var i=$(this).data('sortable');if(i.options._opacity)$(ui.helper).css('opacity',i.options._opacity);}});$.ui.plugin.add("sortable","scroll",{start:function(event,ui){var i=$(this).data("sortable"),o=i.options;if(i.scrollParent[0]!=document&&i.scrollParent[0].tagName!='HTML')i.overflowOffset=i.scrollParent.offset();},sort:function(event,ui){var i=$(this).data("sortable"),o=i.options,scrolled=false;if(i.scrollParent[0]!=document&&i.scrollParent[0].tagName!='HTML'){if((i.overflowOffset.top+i.scrollParent[0].offsetHeight)-event.pageY<o.scrollSensitivity)
i.scrollParent[0].scrollTop=scrolled=i.scrollParent[0].scrollTop+o.scrollSpeed;else if(event.pageY-i.overflowOffset.top<o.scrollSensitivity)
i.scrollParent[0].scrollTop=scrolled=i.scrollParent[0].scrollTop-o.scrollSpeed;if((i.overflowOffset.left+i.scrollParent[0].offsetWidth)-event.pageX<o.scrollSensitivity)
i.scrollParent[0].scrollLeft=scrolled=i.scrollParent[0].scrollLeft+o.scrollSpeed;else if(event.pageX-i.overflowOffset.left<o.scrollSensitivity)
i.scrollParent[0].scrollLeft=scrolled=i.scrollParent[0].scrollLeft-o.scrollSpeed;}else{if(event.pageY-$(document).scrollTop()<o.scrollSensitivity)
scrolled=$(document).scrollTop($(document).scrollTop()-o.scrollSpeed);else if($(window).height()-(event.pageY-$(document).scrollTop())<o.scrollSensitivity)
scrolled=$(document).scrollTop($(document).scrollTop()+o.scrollSpeed);if(event.pageX-$(document).scrollLeft()<o.scrollSensitivity)
scrolled=$(document).scrollLeft($(document).scrollLeft()-o.scrollSpeed);else if($(window).width()-(event.pageX-$(document).scrollLeft())<o.scrollSensitivity)
scrolled=$(document).scrollLeft($(document).scrollLeft()+o.scrollSpeed);}
if(scrolled!==false&&$.ui.ddmanager&&!o.dropBehaviour)
$.ui.ddmanager.prepareOffsets(i,event);}});$.ui.plugin.add("sortable","zIndex",{start:function(event,ui){var t=ui.helper,i=$(this).data('sortable');if(t.css("zIndex"))i.options._zIndex=t.css("zIndex");t.css('zIndex',i.options.zIndex);},beforeStop:function(event,ui){var i=$(this).data('sortable');if(i.options._zIndex)$(ui.helper).css('zIndex',i.options._zIndex=='auto'?'':i.options._zIndex);}});})(jQuery);


/**
 * jQuery ifixpng plugin
 * (previously known as pngfix)
 * Version 2.1	(23/04/2008)
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


/**
 * jQuery Form Plugin
 * version: 2.12 (06/07/2008)
 * @requires jQuery v1.2.2 or later
 *
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Dual licensed under the MIT and GPL licenses:
 *	 http://www.opensource.org/licenses/mit-license.php
 *	 http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id$
 */
(function($){$.fn.ajaxSubmit=function(options){if(!this.length){log('ajaxSubmit: skipping submit process - no element selected');return this;}
if(typeof options=='function')
options={success:options};options=$.extend({url:this.attr('action')||window.location.toString(),type:this.attr('method')||'GET'},options||{});var veto={};this.trigger('form-pre-serialize',[this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');return this;}
var a=this.formToArray(options.semantic);if(options.data){options.extraData=options.data;for(var n in options.data)
a.push({name:n,value:options.data[n]});}
if(options.beforeSubmit&&options.beforeSubmit(a,this,options)===false){log('ajaxSubmit: submit aborted via beforeSubmit callback');return this;}
this.trigger('form-submit-validate',[a,this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-submit-validate trigger');return this;}
var q=$.param(a);if(options.type.toUpperCase()=='GET'){options.url+=(options.url.indexOf('?')>=0?'&':'?')+q;options.data=null;}
else
options.data=q;var $form=this,callbacks=[];if(options.resetForm)callbacks.push(function(){$form.resetForm();});if(options.clearForm)callbacks.push(function(){$form.clearForm();});if(!options.dataType&&options.target){var oldSuccess=options.success||function(){};callbacks.push(function(data){$(options.target).html(data).each(oldSuccess,arguments);});}
else if(options.success)
callbacks.push(options.success);options.success=function(data,status){for(var i=0,max=callbacks.length;i<max;i++)
callbacks[i](data,status,$form);};var files=$('input:file',this).fieldValue();var found=false;for(var j=0;j<files.length;j++)
if(files[j])
found=true;if(options.iframe||found){if($.browser.safari&&options.closeKeepAlive)
$.get(options.closeKeepAlive,fileUpload);else
fileUpload();}
else
$.ajax(options);this.trigger('form-submit-notify',[this,options]);return this;function fileUpload(){var form=$form[0];if($(':input[@name=submit]',form).length){alert('Error: Form elements must not be named "submit".');return;}
var opts=$.extend({},$.ajaxSettings,options);var id='jqFormIO'+(new Date().getTime());var $io=$('<iframe id="'+id+'" name="'+id+'" />');var io=$io[0];if($.browser.msie||$.browser.opera)
io.src='javascript:false;document.write("");';$io.css({position:'absolute',top:'-1000px',left:'-1000px'});var xhr={responseText:null,responseXML:null,status:0,statusText:'n/a',getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){}};var g=opts.global;if(g&&!$.active++)$.event.trigger("ajaxStart");if(g)$.event.trigger("ajaxSend",[xhr,opts]);var cbInvoked=0;var timedOut=0;var sub=form.clk;if(sub){var n=sub.name;if(n&&!sub.disabled){options.extraData=options.extraData||{};options.extraData[n]=sub.value;if(sub.type=="image"){options.extraData[name+'.x']=form.clk_x;options.extraData[name+'.y']=form.clk_y;}}}
setTimeout(function(){var t=$form.attr('target'),a=$form.attr('action');$form.attr({target:id,encoding:'multipart/form-data',enctype:'multipart/form-data',method:'POST',action:opts.url});if(opts.timeout)
setTimeout(function(){timedOut=true;cb();},opts.timeout);var extraInputs=[];try{if(options.extraData)
for(var n in options.extraData)
extraInputs.push($('<input type="hidden" name="'+n+'" value="'+options.extraData[n]+'" />').appendTo(form)[0]);$io.appendTo('body');io.attachEvent?io.attachEvent('onload',cb):io.addEventListener('load',cb,false);form.submit();}
finally{$form.attr('action',a);t?$form.attr('target',t):$form.removeAttr('target');$(extraInputs).remove();}},10);function cb(){if(cbInvoked++)return;io.detachEvent?io.detachEvent('onload',cb):io.removeEventListener('load',cb,false);var operaHack=0;var ok=true;try{if(timedOut)throw'timeout';var data,doc;doc=io.contentWindow?io.contentWindow.document:io.contentDocument?io.contentDocument:io.document;if(doc.body==null&&!operaHack&&$.browser.opera){operaHack=1;cbInvoked--;setTimeout(cb,100);return;}
xhr.responseText=doc.body?doc.body.innerHTML:null;xhr.responseXML=doc.XMLDocument?doc.XMLDocument:doc;xhr.getResponseHeader=function(header){var headers={'content-type':opts.dataType};return headers[header];};if(opts.dataType=='json'||opts.dataType=='script'){var ta=doc.getElementsByTagName('textarea')[0];xhr.responseText=ta?ta.value:xhr.responseText;}
else if(opts.dataType=='xml'&&!xhr.responseXML&&xhr.responseText!=null){xhr.responseXML=toXml(xhr.responseText);}
data=$.httpData(xhr,opts.dataType);}
catch(e){ok=false;$.handleError(opts,xhr,'error',e);}
if(ok){opts.success(data,'success');if(g)$.event.trigger("ajaxSuccess",[xhr,opts]);}
if(g)$.event.trigger("ajaxComplete",[xhr,opts]);if(g&&!--$.active)$.event.trigger("ajaxStop");if(opts.complete)opts.complete(xhr,ok?'success':'error');setTimeout(function(){$io.remove();xhr.responseXML=null;},100);};function toXml(s,doc){if(window.ActiveXObject){doc=new ActiveXObject('Microsoft.XMLDOM');doc.async='false';doc.loadXML(s);}
else
doc=(new DOMParser()).parseFromString(s,'text/xml');return(doc&&doc.documentElement&&doc.documentElement.tagName!='parsererror')?doc:null;};};};$.fn.ajaxForm=function(options){return this.ajaxFormUnbind().bind('submit.form-plugin',function(){$(this).ajaxSubmit(options);return false;}).each(function(){$(":submit,input:image",this).bind('click.form-plugin',function(e){var $form=this.form;$form.clk=this;if(this.type=='image'){if(e.offsetX!=undefined){$form.clk_x=e.offsetX;$form.clk_y=e.offsetY;}else if(typeof $.fn.offset=='function'){var offset=$(this).offset();$form.clk_x=e.pageX-offset.left;$form.clk_y=e.pageY-offset.top;}else{$form.clk_x=e.pageX-this.offsetLeft;$form.clk_y=e.pageY-this.offsetTop;}}
setTimeout(function(){$form.clk=$form.clk_x=$form.clk_y=null;},10);});});};$.fn.ajaxFormUnbind=function(){this.unbind('submit.form-plugin');return this.each(function(){$(":submit,input:image",this).unbind('click.form-plugin');});};$.fn.formToArray=function(semantic){var a=[];if(this.length==0)return a;var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els)return a;for(var i=0,max=els.length;i<max;i++){var el=els[i];var n=el.name;if(!n)continue;if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el)
a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});continue;}
var v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(var j=0,jmax=v.length;j<jmax;j++)
a.push({name:n,value:v[j]});}
else if(v!==null&&typeof v!='undefined')
a.push({name:n,value:v});}
if(!semantic&&form.clk){var inputs=form.getElementsByTagName("input");for(var i=0,max=inputs.length;i<max;i++){var input=inputs[i];var n=input.name;if(n&&!input.disabled&&input.type=="image"&&form.clk==input)
a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});}}
return a;};$.fn.formSerialize=function(semantic){return $.param(this.formToArray(semantic));};$.fn.fieldSerialize=function(successful){var a=[];this.each(function(){var n=this.name;if(!n)return;var v=$.fieldValue(this,successful);if(v&&v.constructor==Array){for(var i=0,max=v.length;i<max;i++)
a.push({name:n,value:v[i]});}
else if(v!==null&&typeof v!='undefined')
a.push({name:this.name,value:v});});return $.param(a);};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length))
continue;v.constructor==Array?$.merge(val,v):val.push(v);}
return val;};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(typeof successful=='undefined')successful=true;if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1))
return null;if(tag=='select'){var index=el.selectedIndex;if(index<0)return null;var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=$.browser.msie&&!(op.attributes['value'].specified)?op.text:op.value;if(one)return v;a.push(v);}}
return a;}
return el.value;};$.fn.clearForm=function(){return this.each(function(){$('input,select,textarea',this).clearFields();});};$.fn.clearFields=$.fn.clearInputs=function(){return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(t=='text'||t=='password'||tag=='textarea')
this.value='';else if(t=='checkbox'||t=='radio')
this.checked=false;else if(tag=='select')
this.selectedIndex=-1;});};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType))
this.reset();});};$.fn.enable=function(b){if(b==undefined)b=true;return this.each(function(){this.disabled=!b});};$.fn.select=function(select){if(select==undefined)select=true;return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio')
this.checked=select;else if(this.tagName.toLowerCase()=='option'){var $sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').select(false);}
this.selected=select;}});};function log(){if($.fn.ajaxSubmit.debug&&window.console&&window.console.log)
window.console.log('[jquery.form] '+Array.prototype.join.call(arguments,''));};})(jQuery);

/**
 * Auto Expanding Text Area (1.2.2)
 * by Chrys Bader (www.chrysbader.com), modified by Alex Suraci
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
(function(jQuery) {
	var self = null;
	jQuery.fn.autogrow = function(o) {
		return this.each(function() {
			new jQuery.autogrow(this, o);
		});
	};
	jQuery.autogrow = function(e, o) {
		if (o == "disable")
			return $(".autogrow-dummy").addClass("disabled")

		this.options = o || {};
		this.dummy = null;
		this.interval = null;
		this.line_height = this.options.lineHeight || parseInt(jQuery(e).css('line-height'));
		this.min_height = this.options.minHeight || parseInt(jQuery(e).css('min-height'));
		this.max_height = this.options.maxHeight || parseInt(jQuery(e).css('max-height'));;
		this.textarea = jQuery(e);
		if (this.line_height == NaN) this.line_height = 0;
		this.init();
	};
	jQuery.autogrow.fn = jQuery.autogrow.prototype = {
		autogrow: '1.2.2'
	};
	jQuery.autogrow.fn.extend = jQuery.autogrow.extend = jQuery.extend;
	jQuery.autogrow.fn.extend({
		init: function() {
			var self = this;
			this.textarea.css({
				overflow: 'hidden',
				display: 'block'
			});
			this.textarea.bind('focus', function() { self.startExpand() }).bind('blur', function() { self.stopExpand() });
			this.checkExpand();
		},
		startExpand: function() {
			var self = this;
			this.interval = window.setInterval(function() { self.checkExpand() }, 400);
			// this.textarea.keydown(function() {
			//     self.checkExpand()
			// })
		},
		stopExpand: function() {
			clearInterval(this.interval);
		},
		checkExpand: function() {
			if (this.dummy == null) {
				this.dummy = jQuery('<div />');
				this.dummy.css({
					fontSize: this.textarea.css('font-size'),
					fontFamily: this.textarea.css('font-family'),
					width: this.textarea.css('width'),
					padding: this.textarea.css('padding'),
					lineHeight: this.line_height + 'px',
					overflowX: 'hidden',
					display: "none"
				}).addClass("autogrow-dummy").appendTo('body');
			}

			if (this.dummy.hasClass("disabled"))
                return clearInterval(this.interval)

			var html = this.textarea.val().replace(/(<|>|&)/g, '&#160;');
			html = ($.browser.msie) ? html.replace(/\n/g, '<BR>new') : html.replace(/\n/g, '<br>new');

			var fixed = html.replace(/&#160;/g, '&nbsp;');

			if (this.dummy.html() != fixed) {
				this.dummy.html(html);
				if (this.max_height > 0 && (this.dummy.height() + this.line_height > this.max_height)) {
					this.textarea.css('overflow-y', 'auto');
				} else {
					this.textarea.css('overflow-y', 'hidden');
					if (this.textarea.height() < this.dummy.height() + this.line_height || (this.dummy.height() < this.textarea.height()))
						this.textarea.height(this.dummy.height() + this.line_height)
				}
			}
		}
	});
})(jQuery);

// I didn't write most of this. Unfortunately I can't find the original author.
// I've updated it to work with jQuery 1.3.
// If anyone knows the original author I'll gladly add attribution.
$.fn.tree = function(options){
	var self = this;
	
	$(this).sortable({
		items: options.sortOn,
		placeholder: "sort-placeholder",
		sortable: null,
		start: function(e, ui){
			$(this).data("sortable").placeholder.hide();
			$(this).data("sortable").refreshPositions(true);
			$(ui.item).addClass("sort-dragging")
		},
		stop: function(e, ui){
			options.done();
			var self = $(self).data("sortable");
			$(ui.item).removeClass("sort-dragging");
		},
		sortIndicator: function(e, item, append, hardRefresh){
			if (append)
				append[0].appendChild(this.placeholder[0]);
			else
				item.item[0].parentNode.insertBefore(this.placeholder[0], (this.direction == 'down' ? item.item[0] : item.item[0].nextSibling));
		}
	});
	
	$(options.dropOn, $(this)).droppable({
		accept: options.sortOn,
		hoverClass: options.hoverClass,
		drop: function(e,ui){
			if ($(this).parent().hasClass("sort-dragging"))
				return;
			
			if (!$(this).parent().find("ul").size())
				$(this).parent().append(document.createElement("ul"));
			
			$(this).parent().find("ul:first").append(ui.draggable);
			$(self).data("sortable")._noFinalSort = true;
		}
	});
};


// Our custom field expander
$.fn.expand = function(){
	$(this).each(function(){
		if ($(this).parent().parent().attr("class") == "more_options" || $.browser.msie) return

		var id = $(this).attr("id")
		var dummy = ".dummy_"+ id

		$(this).css("min-width", $(this).width())

		$(this).css("max-width", $(this).parent().width() - 8)

		$(document.createElement("span")).prependTo("body").addClass("dummy_"+ id).css({
			fontSize: $(this).css("font-size"),
			fontFamily: $(this).css("font-family"),
			padding: $(this).css("padding"),
			position: "absolute",
			top: -9999
		}).text($(this).val())

		$(this).width($(dummy).width() + 20)

		$(this).keypress(function(e){
			$(dummy).text($(this).val() + String.fromCharCode(e.which))

			if (e.which == 8)
				$(dummy).text($(this).val().substring(0, $(this).val().length - 1))

			$(this).width($(dummy).width() + 20)
		})

		if ($.browser.safari)
			$(this).keydown(function(e){
				$(dummy).text($(this).val() + String.fromCharCode(e.which))

				if (e.which == 8)
					$(dummy).text($(this).val().substring(0, $(this).val().length - 1))

				$(this).width($(dummy).width() + 20)
			})
	})
}

// "Loading..." overlay.
$.fn.loader = function(remove) {
	if (remove) {
		$(this).next().remove()
		return this
	}

	var offset = $(this).offset()
	var loading_top = ($(this).outerHeight() / 2) - 11
	var loading_left = ($(this).outerWidth() / 2) - 63

	$(this).after("<div class=\"load_overlay\"><img src=\""+site_url+"/includes/close.png\" style=\"display: none\" class=\"close\" /><img src=\""+site_url+"/includes/loading.gif\" style=\"display: none\" class=\"loading\" /></div>")

	$(".load_overlay .loading").css({
		position: "absolute",
		top: loading_top+"px",
		left: loading_left+"px",
		display: "inline"
	})

	$(".load_overlay .close").css({
		position: "absolute",
		top: "3px",
		right: "3px",
		color: "#fff",
		cursor: "pointer",
		display: "inline"
	}).click(function(){ $(this).parent().remove() })

	$(".load_overlay").css({
		position: "absolute",
		top: offset.top,
		left: offset.left,
		zIndex: 100,
		width: $(this).outerWidth(),
		height: $(this).outerHeight(),
		background: ($.browser.msie) ? "transparent" : "transparent url('"+site_url+"/includes/trans.png')",
		textAlign: "center",
		filter: ($.browser.msie) ? "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=scale, src='"+site_url+"/includes/trans.png');" : ""
	})

	return this
}

// Originally from http://livepipe.net/extra/cookie
var Cookie = {
	set: function (name, value, days) {
		if (days) {
			var d = new Date();
			d.setTime(d.getTime() + (days * 1000 * 60 * 60 * 24));
			var expiry = "; expires=" + d.toGMTString();
		} else
			var expiry = "";

		document.cookie = name + "=" + value + expiry + "; path=/";
	},
	get: function(name){
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];

			while(c.charAt(0) == " ")
				c = c.substring(1,c.length);

			if(c.indexOf(nameEQ) == 0)
				return c.substring(nameEQ.length,c.length);
		}
		return null;
	},
	destroy: function(name){
		Cookie.set(name, "", -1);
	}
}

// Used to check if AJAX responses are errors.
function isError(text) {
	return /HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW$/m.test(text);
}

Array.prototype.indicesOf = function(value) {
	var results = []

	for (var j = 0; j < this.length; j++)
		if (typeof value != "string") {
			if (value.test(this[j]))
				results.push(j)
		} else if (this[j] == value)
			results.push(j)

	return results
}

Array.prototype.find = function(match) {
	var matches = []

	for (var f = 0; f < this.length; f++)
		if (match.test(this[f]))
			matches.push(this[f])

	return matches
}

Array.prototype.remove = function(value) {
	if (value instanceof Array) {
		for (var r = 0; r < value.length; r++)
			this.remove(value[r])

		return
	}

	var indices = this.indicesOf(value)

	if (indices.length == 0)
		return

	for (var h = 0; h < indices.length; h++)
		this.splice(indices[h] - h, 1)

	return this
}
