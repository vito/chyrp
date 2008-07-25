<?php
	define('JAVASCRIPT', true);
	require_once "common.php";
?>
<!-- --><script>
$(function(){
	// Scan AJAX responses for errors.
	$(document).ajaxComplete(function(event, request){
		var response = request.responseText
		if (isError(response))
			alert(response.replace(/(HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW|<([^>]+)>\n?)/gm, ""))
	})<?php echo "\n\n\n\n"; # Balance out the line numbers in this script and in the output to help debugging. ?>

	$(".toggle_admin").click(function(){
		if (!$("#admin_bar:visible, #controls:visible").size())
			Cookie.destroy("hide_admin")
		else
			Cookie.set("hide_admin", "true", 30)

		$("#admin_bar, #controls").slideToggle()
		return false
	})

	Post.prepare_links()
})

var Route = {
	action: "<?php echo $_GET['action']; ?>"
}

var Post = {
	delete_animations: { height: "hide", opacity: "hide" },
	delete_wrap: "<div></div>",
	id: 0,
	edit: function(id) {
		Post.id = id
		$("#post_"+id).loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "edit_post", id: id }, function(data) {
			$("#post_"+id).loader(true).fadeOut("fast", function(){
				$(this).replaceWith(data)
				$("#post_edit_form_"+id).css("opacity", 0).animate({ opacity: 1 }, function(){
<?php $trigger->call("ajax_post_edit_form_javascript"); ?>
					$("#more_options_link_"+id).click(function(){
						if ($("#more_options_"+id).css("display") == "none") {
							$(this).empty().append("<?php echo __("&#171; Fewer Options"); ?>")
							$("#more_options_"+id).slideDown("slow");
						} else {
							$(this).empty().append("<?php echo __("More Options &#187;"); ?>")
							$("#more_options_"+id).slideUp("slow");
						}
						return false;
					})
					$("#post_edit_form_"+id).ajaxForm({ beforeSubmit: function(){
						$("#post_edit_form_"+id).loader()
					}, success: Post.updated })
					$("#post_cancel_edit_"+id).click(function(){
						$("#post_edit_form_"+id).loader()
						$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "view_post", context: "all", id: id, reason: "cancelled" }, function(data) {
							$("#post_edit_form_"+id).loader(true).fadeOut("fast", function(){
								$(this).replaceWith(data)
								$(this).hide().fadeIn("fast", function(){
									Post.prepare_links(id)
								})
							})
						})
						return false
					})
				})
			})
		})
	},
	updated: function(response){
		id = Post.id
		if (isError(response))
			return $("#post_edit_form_"+id).loader(true)

		if (Route.action != "drafts" && Route.action != "view" && $("#post_edit_form_"+id+" select#status").val() == "draft") {
			$("#post_edit_form_"+id).loader(true).fadeOut("fast", function(){
				alert("<?php echo __("Post has been saved as a draft."); ?>")
			})
		} else if (Route.action == "drafts" && $("#post_edit_form_"+id+" select#status").val() != "draft") {
			$("#post_edit_form_"+id).loader(true).fadeOut("fast", function(){
				alert("<?php echo __("Post has been published."); ?>")
			})
		} else {
			$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "view_post", context: "all", id: id, reason: "edited" }, function(data) {
				$("#post_edit_form_"+id).loader(true).fadeOut("fast", function(){
					$(this).replaceWith(data)
					$("#post_"+id).hide().fadeIn("fast", function(){
						Post.prepare_links(id)
					})
				})
			})
		}
	},
	destroy: function(id) {
		$("#post_"+id).loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "delete_post", id: id }, function(response){
			$("#post_"+id).loader(true)
			if (isError(response)) return

			if (Post.delete_wrap != "")
				$("#post_"+id).wrap(Post.delete_wrap).parent().animate(Post.delete_animations, function(){
					$(this).remove()

					if (Route.action == "view")
						window.location = "<?php echo $config->url; ?>"
				})
			else
				$("#post_"+id).animate(Post.delete_animations, function(){
					$(this).remove()

					if (Route.action == "view")
						window.location = "<?php echo $config->url; ?>"
				})
		})
	},
	prepare_links: function(id) {
		if (id != null) {
			$("#post_edit_"+id).click(function(){
				Post.edit(id)
				return false
			})
			$("#post_delete_"+id).click(function(){
				if (!confirm("<?php echo __("Are you sure you want to delete this post?\\n\\nIt cannot be restored if you do this. If you wish to hide it, save it as a draft."); ?>")) return false
				Post.destroy(id)
				return false
			})
		} else {
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
		}
	}
}

$.fn.loader = function(remove) {
	if (remove) {
		$(this).next().remove()
		return this
	}

	var offset = $(this).offset()
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
		width: $(this).outerWidth(),
		height: $(this).outerHeight(),
		background: ($.browser.msie) ? "transparent" : "transparent url('<?php echo $config->chyrp_url; ?>/includes/trans.png')",
		textAlign: "center",
		filter: ($.browser.msie) ? "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=scale, src='<?php echo $config->chyrp_url; ?>/includes/trans.png');" : ""
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

function isError(text) {
	return /HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW/m.test(text);
}

<?php echo "\n"; $trigger->call("javascript"); ?>
<!-- --></script>