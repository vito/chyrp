<?php
	define('JAVASCRIPT', true);
	require_once "common.php";
	error_reporting(0);
	header("Content-Type: application/x-javascript");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 03 Jun 1991 05:30:00 GMT");
	$action = $_GET['action'];
	$page = fallback($_GET['page'], 1, true);
?>
//<script type="text/javascript"> (This is so TextMate gives me nice JS highlighting.)
$(function(){
	// Scan AJAX responses for errors.
	$(document).ajaxComplete(function(imconfused, request){
		var response = request.responseText
		if (isError(response))
			alert(response.replace(/(HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW|<([^>]+)>\n?)/gm, ""))
	})

	$(".toggle_admin").click(function(){
		if (!$("#admin_bar:visible").size()) {
			$("#admin_bar").slideDown()
			Cookie.destroy("chyrp_hide_admin")
		} else {
			$("#admin_bar").slideUp()
			Cookie.set("chyrp_hide_admin", "true", 30)
		}
		return false
	})

	$(".post_edit_link").click(function(){
		var id = $(this).attr("id").replace(/post_edit_/, "")
		Post.edit(id)
		return false
	})

	$(".post_delete_link").click(function(){
		if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
		var id = $(this).attr("id").replace(/post_delete_/, "")
		Post.destroy(id)
		return false
	})
<?php $trigger->call("javascript_domready"); ?>
})

var Post = {
  edit: function(id) {
		$("#post_"+id+" .target, #post_"+id+".target").loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "edit_post", id: id }, function(data) {
			$("#post_"+id+" .target, #post_"+id+".target").loader(true).fadeOut("fast", function(){ $(this).html(data).fadeIn("fast", function(){
<?php $trigger->call("ajax_post_edit_form_javascript"); ?>
				$("#more_options_link_"+id).click(function(){
					if ($("#more_options_"+id).css("display") == "none") {
						$(this).html("<?php echo __("&laquo; Fewer Options"); ?>")
						$("#more_options_"+id).slideDown("slow")
					} else {
						$(this).html("<?php echo __("More Options &raquo;"); ?>")
						$("#more_options_"+id).slideUp("slow")
					}
					return false
				})
				$("#post_edit_"+id).ajaxForm({ beforeSubmit: function(){
					$("#post_"+id+" .target, #post_"+id+".target").loader()
				}, success: function(response){
					if (isError(response)) return $("#post_"+id+" .target, #post_"+id+".target").loader(true)
<?php if ($action != "drafts"): ?>
					if ($("#post_edit_"+id+" select#status").val() == "draft") {
						$("#post_"+id+" .target, #post_"+id+".target").loader(true)
						$("#post_"+id).fadeOut("fast")
						appendNextPost(0)
						alert("<?php echo __("Post has been saved as a draft."); ?>")
					} else {
<?php endif; ?>
						$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "view_post", context: "all", id: id, reason: "edited" }, function(data) {
							$("#post_"+id+" .target, #post_"+id+".target").loader(true)
							$("#post_"+id+" .target, #post_"+id+".target").fadeOut("fast", function(){ $(this).html(data).fadeIn("fast", function(){
								$("#post_edit_"+id).click(function(){
									Post.edit(id)
									return false
								})
								$("#post_delete_"+id).click(function(){
									if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
									Post.destroy(id)
									return false
								})
							}) })
						})
<?php if ($action != "drafts"): ?>
					}
<?php endif; ?>
				} })
				$("#post_cancel_edit_"+id).click(function(){
					$("#post_"+id+" .target, #post_"+id+".target").loader()
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "view_post", context: "all", id: id, reason: "cancelled" }, function(data) {
						$("#post_"+id+" .target, #post_"+id+".target").loader(true)
						$("#post_"+id+" .target, #post_"+id+".target").fadeOut("fast", function(){ $(this).html(data).fadeIn("fast", function(){
							$("#post_edit_"+id).click(function(){
								Post.edit(id)
								return false
							})
							$("#post_delete_"+id).click(function(){
								if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
								Post.destroy(id)
								return false
							})
						}) })
					})
					return false
				})
			}) })
		})
	}
	,
	destroy: function(id) {
		$("#post_"+id+" .target, #post_"+id+".target").loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "delete_post", id: id }, function(response){
			$("#post_"+id+" .target, #post_"+id+".target").loader(true)
			if (isError(response)) return
			$("#post_"+id).animate({ height: "hide", opacity: "hide" }, function(){
				$(this).remove()
			})
			appendNextPost(1)
		})
<?php if ($_GET['action'] == "view"): ?>
		window.location = "<?php echo $config->url; ?>"
<?php endif; ?>
	}
}

$.fn.loader = function(remove) {
	if (remove) {
		$(this).next().remove()
		return this
	}

	var offset = $(this).offset()
	var width = $(this).width()
	var loading_top = ($(this).height() / 2) - 11
	var loading_left = ($(this).width() / 2) - 63

	$(this).after("<div class=\"load_overlay\"><img src=\"<?php echo $config->chyrp_url; ?>/includes/close.png\" style=\"display: none\" class=\"close\" /><img src=\"<?php echo $config->chyrp_url; ?>/includes/loading.gif\" style=\"display: none\" class=\"loading\" /></div>")

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
		background: ($.browser.msie) ? "transparent" : "transparent url('<?php echo $config->chyrp_url; ?>/includes/trans.png')",
		textAlign: "center",
		filter: ($.browser.msie) ? "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=scale, src='<?php echo $config->chyrp_url; ?>/includes/trans.png');" : ""
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

function appendNextPost(minus) {
	var minus = (minus == "") ? 1 : minus ;
<?php if ($action == "index" or ($action == "drafts" and $visitor->group()->can("view_draft")) or $action == "archive" or $action == "search"): ?>
	if ($("#posts").length == 0) return;
<?php
	switch($action):
		case "index":
?>
	$.ajax({ type: "post", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: { action: "view_post", offset: <?php echo ($page * $config->posts_per_page); ?> - minus }, success: function(data) {
<?php
			break;
		case "drafts":
?>
	$.ajax({ type: "post", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: { action: "view_post", context: "drafts", offset: <?php echo ($page * $config->posts_per_page); ?> - minus }, success: function(data) {
<?php
			break;
		case "archive":
?>
	$.ajax({ type: "post", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: { action: "view_post", context: "archive", year: "<?php echo $_GET['arg1']; ?>", month: "<?php echo $_GET['arg2']; ?>", offset: <?php echo ($page * $config->posts_per_page); ?> - minus }, success: function(data) {
<?php
			break;
		case "search":
?>
	$.ajax({ type: "post", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: { action: "view_post", context: "search", query: "<?php echo $_GET['arg1']; ?>", offset: <?php echo ($page * $config->posts_per_page); ?> - minus }, success: function(data) {
<?php
			break;
	endswitch;
?>
		$("#posts").append(data)
		$("#posts .post:last").hide().fadeIn("slow")
		var id = $("#posts .post:last").attr("id").replace(/post_/, "")

		$("#post_edit_"+id).click(function(){
			Post.edit(id)
			return false
		})

		$("#post_delete_"+id).click(function(){
			if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
			Post.destroy(id)
			return false
		})
	}, error: function(request){
		if (request.status == 404) {
			if (!$("#posts .post").size())
				$.ajax({ type: "post", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: { action: "snippet", name: "no_posts", argument: "<?php echo $action; ?>" }, success: function(data) {
					$("#posts *").each(function(){
						$(this).addClass("nofade")
					})
					$("#posts").append(data)
					$("#posts *:not(.nofade)").hide().fadeIn("slow")
				} })

			$("#next_page_page").fadeOut("fast");
		}
	} });
<?php endif; ?>
}

function isError(text) {
	return /HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW/m.test(text);
}

<?php $trigger->call("javascript"); ?>
//</script>
