<?php
	define('JAVASCRIPT', true);
	require_once "../../includes/common.php";
	error_reporting(0);
	header("Content-Type: application/x-javascript");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 03 Jun 1991 05:30:00 GMT");

	$more_options_string = (empty($_COOKIE['show_more_options'])) ? __("More Options &raquo;") : __("&laquo; Less Options") ;
?>
//<script>
$(function(){
	$('<a id="more_options_link" class="more_options_link" href="javascript:void(0)"><?php echo $more_options_string; ?></a>').insertBefore("#after_options")
	$("#more_options").clone().insertAfter("#more_options_link").removeClass("js_disabled")<?php if (empty($_COOKIE['show_more_options'])): ?>.css("display", "none")<?php endif; ?>

	$("#more_options_link").click(function(){
		if ($("#more_options").css("display") == "none") {
			$(this).html("<?php echo __("&laquo; Less Options"); ?>")
			Cookie.set("show_more_options", "true", 30)
		} else {
			$(this).html("<?php echo __("More Options &raquo;"); ?>")
			Cookie.destroy("show_more_options")
		}
		$("#more_options").slideToggle()
	})

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

	$(".disabled").css("opacity", .75)
	$(".box.disabled .right a, .box.enabled .right a").click(function(){
		var link = $(this)
		var box = link.parent().parent().parent()
		var confirmed = false;
		var real_name = box.attr("id").replace(/module_/, "").replace(/feather_/, "")
		var type = box.attr("id").replace(/_(.+)/, "")

		$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "check_confirm", check: real_name, type: type }, function(data){
			if (data != "" && !/disabled/.test(box.attr("class")))
				var confirmed = (confirm(data)) ? 1 : 0

			$.ajax({ type: "post", dataType: "json", url: link.attr("href"), data: { confirm: confirmed, ajax: "true" }, beforeSend: function(){
				box.loader()
			}, success: function(json){
				box.loader(true)
				var box_class = box.attr("class")
				if (box_class.match("disabled")) {
					link.html('<img src="<?php echo $config->url."/admin/icons/success.png"; ?>" alt="<?php echo __("enabled"); ?>" /> <?php echo __("enabled"); ?>')
					box.css("opacity", 1)
					box.removeClass("disabled").addClass("enabled")
				} else {
					link.html('<img src="<?php echo $config->url."/admin/icons/deny.png"; ?>" alt="<?php echo __("disabled"); ?>" /> <?php echo __("disabled"); ?>')
					box.removeClass("enabled").addClass("disabled")
					box.css("opacity", .75)
				}
				$(json.notifications).each(function(){
					if (this == "") return
					alert(this)
				})
			} })
		})

		return false
	})
	$("img[@src$=.png]").ifixpng()
	$(".js_disabled").remove()

	$(".post_delete_link").click(function(){
		if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
		var id = $(this).attr("id").replace(/post_delete_/, "")
		Post.destroy(id)
		return false
	})

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

	$(document.createElement("li")).addClass("bookmarklet right").html("<?php echo sprintf(__("Bookmarklet: %s"), '<a href=\"javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f=\''.$config->url.'/includes/bookmarklet.php\',l=d.location,e=encodeURIComponent,p=\'?url=\'+e(l.href)+\'&title=\'+e(d.title)+\'&selection=\'+e(s),u=f+p;a=function(){if(!w.open(u,\'t\',\'toolbar=0,resizable=0,status=1,width=450,height=430\'))l.href=u;};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();void(0)\">Chyrp!</a>'); ?>").prependTo(".write-nav")

<?php if ($_GET['action'] == "manage" and $_GET['sub'] == "page"): ?>
	var parent_hash = ""
	$(".sort_pages li").css({
		cursor: "move", 
		background: "#f9f9f9", 
		padding: ".15em .5em", 
		marginBottom: ".5em", 
		border: "1px solid #ddd"
	})

	function get_parent_hash() {
		var parent_hash = ""
		$(".sort_pages li").each(function(){
			var id = $(this).attr("id").replace(/page_list_/, "")
			var parent = $(this).parent().parent() // this > #sort_pages > page_list_(id)
			var parent_id = (/page_list_/.test(parent.attr("id"))) ? parent.attr("id").replace(/page_list_/, "") : 0
			$(this).attr("parent", parent_id)
			parent_hash += "&parent["+id+"]="+parent_id
		})
		return parent_hash
	}

	$(".sort_pages:first").attr("id", "sort_pages").NestedSortable({
		accept: "page-item", 
		opacity: 0.8, 
		fit: true, 
		nestingPxSpace: 1, 
		onStop: function(){
			var serialize = $.SortSerialize("sort_pages")
			var parent_hash = get_parent_hash()

			$(".sort_pages").loader()
			$.post("<?php echo $config->url; ?>/includes/ajax.php", "action=organize_pages&"+serialize.hash+parent_hash, function(){
				$(".sort_pages").loader(true)
			})
		}
	})
	$(".sort_pages input").remove()
<?php endif; ?>

<?php if ($_GET['action'] == "extend" and ($_GET['sub'] == "modules" or empty($_GET['sub']))): ?>
	function remove_from_array(value, array) {
		for (i = 0; i < array.length; i++)
			if (array[i] == value)
				array.splice(i, 1)
		return array
	}
	function draw() {
		$(".box, .header, .header .view, .main-nav, .sub-nav, .footer, h1, h3, .legend").css({
			position: "relative", 
			zIndex: 2
		})
		$(".header .view").css({
			zIndex: 3
		})

		$(document.createElement("canvas")).attr("id", "canvas").prependTo("body")
		$("#canvas").css({
			position: "absolute", 
			top: 0, 
			bottom: 0, 
			zIndex: 1, 
			margin: "0 auto 0 -75px"
		}).attr({ width: ($(".content").width() + 150), height: $(document).height() })

		var canvas = document.getElementById("canvas").getContext("2d")
		var displayed = []

		$(".box.conflict").each(function(){
			var classes = $(this).attr("class").split(" ");

			// Remove any classes we don't want
			$(["box", "enabled", "disabled", "conflict", "depends"]).each(function(){
				remove_from_array(this, classes);
			})

			for (i = 0; i < classes.length; i++) {
				var element = classes[i].replace("conflict_", "module_")

				if (displayed[$(this).attr("id")+" :: "+element])
					continue;

				var offset_75_1p = 76 + parseInt($(this).parent().css("paddingLeft"))

				var offset1 = $(this).offset()
				var offset2 = $("#"+element).offset()
				var left_offset = (i % 2) ? offset_75_1p + $(this).width() : offset_75_1p
				var top_offset_start = offset1.top + $(this).height() / 2
				var top_offset_stop = offset2.top + $("#"+element).height() / 2
				var median = top_offset_start + ((top_offset_stop - top_offset_start) / 2)
				var curve = (i % 2) ? left_offset + 75 : left_offset - 75

				canvas.strokeStyle = "#d12f19"
				canvas.fillStyle = "#fbe3e4"
				canvas.lineWidth = 3

				canvas.beginPath();
				canvas.moveTo(left_offset, top_offset_start)
				canvas.quadraticCurveTo(curve, median, left_offset, top_offset_stop);
				canvas.stroke();

				var start = (i % 2) ? -1.75 : 1.35
				var end = (i % 2) ? 1.75 : -1.35

				// Beginning circle
				canvas.beginPath()
				canvas.arc(left_offset, top_offset_start, 5, start, end, false)
				canvas.fill()
				canvas.stroke()

				// Ending circle
				canvas.beginPath()
				canvas.arc(left_offset, top_offset_stop, 5, start, end, false)
				canvas.fill()
				canvas.stroke()

				displayed[element+" :: "+$(this).attr("id")] = true
			}
		})
	}
	if ($(".box.conflict").size() && ($.browser.safari || $.browser.opera || ($.browser.mozilla && $.browser.version >= 1.9)))
		draw()
<?php endif; ?>
})

var Post = {
	destroy: function(id) {
		$("#post_"+id).fadeOut("fast")
		$.post("<?php echo $config->url; ?>/includes/ajax.php", { action: "delete_post", id: id })
<?php if ($_GET['action'] == "view"): ?>
		window.location = "<?php echo $config->url; ?>"
<?php endif; ?>
	}
}

$.fn.loader = function(remove) {
	var new_id = $(this).attr("id").replace(/[^a-zA-Z0-9_]/, "")
	if (remove) {
		$("#load_overlay_"+new_id).remove()
	} else {
		var offset = $(this).offset()
		var width = $(this).width()
		var loading_top = ($(this).height() / 2) - 11
		var loading_left = ($(this).width() / 2) - 63
		$("body").append("<div id=\"load_overlay_"+new_id+"\"><img src=\"<?php echo $config->url; ?>/includes/close.png\" style=\"display: none\" class=\"close\" /><img src=\"<?php echo $config->url; ?>/includes/loading.gif\" style=\"display: none\" class=\"loading\" /></div>")
		$("#load_overlay_"+new_id+" .loading").css({
			position: "absolute", 
			top: loading_top+"px", 
			left: loading_left+"px", 
			display: "inline"
		})
		$("#load_overlay_"+new_id+" .close").css({
			position: "absolute", 
			top: "3px", 
			right: "3px", 
			color: "#fff", 
			cursor: "pointer", 
			display: "inline"
		}).click(function(){ $(this).parent().remove() })
		$("#load_overlay_"+new_id).css({
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
	}
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

<?php $trigger->call("admin_javascript"); ?>
//</script>
