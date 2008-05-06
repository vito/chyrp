<?php
	define('JAVASCRIPT', true);
	require_once "../../includes/common.php";
	error_reporting(0);
	header("Content-Type: application/x-javascript");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 03 Jun 1991 05:30:00 GMT");
	$action = $_GET['action'];
	$page = fallback($_GET['page'], 1, true);
	$more_options_string = (empty($_COOKIE['show_more_options'])) ? __("More Options &raquo;") : __("&laquo; Fewer Options") ;
?>
//<script>
$(function(){
	// Scan AJAX responses for errors.
	$(document).ajaxComplete(function(imconfused, request){
		var response = request.responseText
		if (isError(response))
			alert(response.replace(/HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW/, ""))
	})

	// Fancify the "More Options" links.
	$('<a id="more_options_link" class="more_options_link" href="javascript:void(0)"><?php echo $more_options_string; ?></a>').insertBefore("#after_options")
	$("#more_options").clone().insertAfter("#more_options_link").removeClass("js_disabled")<?php if (empty($_COOKIE['show_more_options'])): ?>.css("display", "none")<?php endif; ?>

	$("#more_options_link").click(function(){
		if ($("#more_options").css("display") == "none") {
			$(this).html("<?php echo __("&laquo; Fewer Options"); ?>")
			Cookie.set("show_more_options", "true", 30)
		} else {
			$(this).html("<?php echo __("More Options &raquo;"); ?>")
			Cookie.destroy("show_more_options")
		}
		$("#more_options").slideToggle()
	})

	// Remove things that only exist for JS-disabled users.
	$(".js_disabled").remove()
	$(".js_enabled").css("display", "block")

	// Automatic PNG fixing.
	$("img[@src$=.png]").ifixpng()

	$(document.createElement("li")).addClass("bookmarklet right").html("<?php echo sprintf(__("Bookmarklet: %s"), '<a href=\"javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f=\''.$config->url.'/includes/bookmarklet.php\',l=d.location,e=encodeURIComponent,p=\'?url=\'+e(l.href)+\'&title=\'+e(d.title)+\'&selection=\'+e(s),u=f+p;a=function(){if(!w.open(u,\'t\',\'toolbar=0,resizable=0,status=1,width=450,height=430\'))l.href=u;};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();void(0)\">Chyrp!</a>'); ?>").prependTo(".write-nav")

<?php if ($_GET['action'] == "edit" or $_GET['action'] == "write"): ?>
	// Auto-expand text fields.
	$("input.text").keyup(function(){
		if ($(this).val().length > 10 && ($(this).parent().width() - $(this).width()) < 10)
			return;

		$(this).attr("size", $(this).val().length)
	})

<?php endif; ?>
	// "Help" links should open in popup windows.
	$(".help").click(function(){
		window.open($(this).attr("href"), "help", "status=0, height=350, width=300")
		return false;
	})

	// AJAX post deletion.
	$(".post_delete_link").click(function(){
		if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
		var id = $(this).attr("id").replace(/post_delete_/, "")
		Post.destroy(id)
		return false
	})

	// Content previewing.
	if ($(".preview_me").length > 0) {
		var feather = ($("#write_feather").size()) ? $("#write_feather").val() : ""
		var feather = ($("#edit_feather").size()) ? $("#edit_feather").val() : feather
		$(document.createElement("div")).css("display", "none").attr("id", "preview").insertBefore("#write_form, #edit_form")
		$(document.createElement("button")).html("<?php echo __("Preview &rarr;"); ?>").attr({ "type": "submit", "accesskey": "p" }).click(function(){
			$("#preview").load("<?php echo $config->url; ?>/includes/ajax.php", { action: "preview", content: $(".preview_me").val(), feather: feather }, function(){
				$(this).fadeIn("fast")
			})
			return false
		}).insertAfter("#publish, #save")
	}

	// Checkbox toggling.
	var all_checked = true
	$("#toggler").html('<label for="toggle">Toggle All</label><input type="checkbox" name="toggle" value="" id="toggle" />')
	$("#toggle").click(function(){
		$("form#new_group, form#group_edit").find(":checkbox").not("#toggle").each(function(){
			this.checked = document.getElementById("toggle").checked
		})
	})
	$("form#new_group, form#group_edit").find(":checkbox").not("#toggle").each(function(){
		if (!all_checked) return
		all_checked = this.checked
	})
	if ($("#toggler").size())
		document.getElementById("toggle").checked = all_checked
})

var Post = {
	destroy: function(id) {
		$("#post_"+id+" .target, #post_"+id+".target").loader()
		$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "delete_post", id: id }, function(response){
			$("#post_"+id+" .target, #post_"+id+".target").loader(true)
			if (isError(response)) return
			$("#post_"+id).animate({ height: "hide", opacity: "hide" }).remove()
		})
	}
}

// "Loading..." overlay.
$.fn.loader = function(remove) {
	if (remove) {
		$(this).next().remove()
		return this
	}

	var offset = $(this).offset()
	var width = $(this).width()
	var loading_top = ($(this).height() / 2) - 11
	var loading_left = ($(this).width() / 2) - 63

	$(this).after("<div class=\"load_overlay\"><img src=\"<?php echo $config->url; ?>/includes/close.png\" style=\"display: none\" class=\"close\" /><img src=\"<?php echo $config->url; ?>/includes/loading.gif\" style=\"display: none\" class=\"loading\" /></div>")

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
		width: $(this).width(),
		height: $(this).height(),
		background: ($.browser.msie) ? "transparent" : "transparent url('<?php echo $config->url; ?>/includes/trans.png')",
		textAlign: "center",
		filter: ($.browser.msie) ? "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=scale, src='<?php echo $config->url; ?>/includes/trans.png');" : ""
	})

	return this
}

var Cookie = {
	set: function(name, value, expires) {
		var today = new Date()
		today.setTime( today.getTime() )

		if (expires)
			expires = expires * 1000 * 60 * 60 * 24

		var expires_date = new Date(today.getTime() + (expires))

		document.cookie = name+"="+escape(value)+
		                  ((expires) ? ";expires="+expires_date.toGMTString() : "" )+";path=/"
	},
	destroy: function(name) {
		document.cookie = name+"=;path=/;expires=Thu, 01-Jan-1970 00:00:01 GMT"
	}
}

// Used to check if AJAX responses are errors.
function isError(text) {
	return /HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW/.test(text);
}

<?php $trigger->call("admin_javascript"); ?>
//</script>
