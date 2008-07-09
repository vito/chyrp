<?php
	define('JAVASCRIPT', true);
	require_once "../../includes/common.php";
	error_reporting(0);
	header("Content-Type: application/x-javascript");
?>
<!-- --><script>
var editing = 0
var notice = 0
var Comment = {
	delete_animations: { height: "hide", opacity: "hide" },
	delete_wrap: "",
	reload: function() {
		if ($(".comments:not(:header)").attr("id") == undefined) return;

		var id = $(".comments:not(:header)").attr("id").replace(/comments_/, "")
		if (editing == 0 && notice == 0 && $(".comments:not(:header)").children().size() < <?php echo $config->comments_per_page; ?>) {
			$.ajax({ type: "post", dataType: "json", url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php", data: "action=reload_comments&post_id="+id+"&last_comment="+$("#last_comment").val(), success: function(json) {
				$.each(json.comment_ids, function(i, id) {
					$("#last_comment").val(id)
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id }, function(data){
						$(data).appendTo(".comments:not(:header)").hide().fadeIn("slow")
					})
				})
			} })
		}
	},
	edit: function(id) {
		editing++
		$("#comment_"+id).loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "edit_comment", comment_id: id }, function(data) {
			if (isError(data)) return $("#comment_"+id).loader(true)
			$("#comment_"+id).loader(true).fadeOut("fast", function(){ $(this).html(data).fadeIn("fast", function(){
				$("#more_options_link_"+id).click(function(){
					if ($("#more_options_"+id).css("display") == "none") {
						$(this).html("<?php echo __("&laquo; Fewer Options"); ?>")
						$("#more_options_"+id).slideDown("slow");
					} else {
						$(this).html("<?php echo __("More Options &raquo;"); ?>")
						$("#more_options_"+id).slideUp("slow");
					}
					return false;
				})
				$("#comment_cancel_edit_"+id).click(function(){
					$("#comment_"+id).loader()
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id }, function(data){
						$("#comment_"+id).replaceWith(data)
						$("#comment_"+id).loader(true)
						$("#comment_edit_"+id).click(function(){
							Comment.edit(id)
							return false
						})
						$("#comment_delete_"+id).click(function(){
							notice++
							if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return notice--
							Comment.destroy(id)
							return false
						})
					})
				})
				$("#comment_edit_"+id).ajaxForm({ beforeSubmit: function(){
					$("#comment_"+id).loader()
				}, success: function(response){
					editing--
					if (isError(response)) return $("#comment_"+id).loader(true)
					$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: id, reason: "edited" }, function(data) {
						if (isError(data)) return $("#comment_"+id).loader(true)
						$("#comment_"+id).loader(true)
						$("#comment_"+id).fadeOut("fast", function(){ $(this).replaceWith(data).fadeIn("fast", function(){
							$("#comment_edit_"+id).click(function(){
								Comment.edit(id)
								return false
							})
							$("#comment_delete_"+id).click(function(){
								notice++
								if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return notice--
								Comment.destroy(id)
								return false
							})
						}) })
					})
				} })
			}) })
		})
	},
	destroy: function(id) {
		notice--
		$("#comment_"+id).loader()
		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "delete_comment", id: id }, function(response){
			$("#comment_"+id).loader(true)
			if (isError(response)) return

			if (Comment.delete_wrap != "")
				$("#comment_"+id).wrap(Comment.delete_wrap).parent().animate(Comment.delete_animations, function(){
					$(this).remove()
				})
			else
				$("#comment_"+id).animate(Comment.delete_animations, function(){
					$(this).remove()
				})

			if ($(".comment_count").size() && $(".comment_plural").size()) {
				var count = parseInt($(".comment_count:first").text())
				count--
				$(".comment_count").text(count)
				var plural = (count == 1) ? "" : "s"
				$(".comment_plural").text(plural)
			}
		})
	}
}
<?php Trigger::current()->call("comments_javascript"); ?>
<!-- --></script>