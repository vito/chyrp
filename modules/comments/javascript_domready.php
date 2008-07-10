<!-- --><script>
	if ($(".comments:not(:header)").size()) {
<?php if ($config->auto_reload_comments and $config->enable_reload_comments): ?>
		var updater = setInterval("Comment.reload()", <?php echo $config->auto_reload_comments * 1000; ?>);
<?php endif; ?>
		$("#add_comment").append($(document.createElement("input")).attr({ type: "hidden", name: "ajax", value: "true", id: "ajax" }));
		$("#add_comment").ajaxForm({ dataType: "json", resetForm: true, beforeSubmit: function(){
			$("#add_comment").loader();
		}, success: function(json){
			$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "show_comment", comment_id: json.comment_id, reason: "added" }, function(data) {
				if ($(".comment_count").size() && $(".comment_plural").size()) {
					var count = parseInt($(".comment_count:first").text())
					count++
					$(".comment_count").text(count)
					var plural = (count == 1) ? "" : "s"
					$(".comment_plural").text(plural)
				}
				$("#last_comment").val(json.comment_id)
				$(data).appendTo(".comments:not(:header)").hide().fadeIn("slow")
				$("#comment_edit_"+json.comment_id).click(function(){
					Comment.edit(json.comment_id)
					return false
				})
				$("#comment_delete_"+json.comment_id).click(function(){
					notice++

					if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) {
						notice--
						return false
					}
					notice--

					Comment.destroy(json.comment_id)
					return false
				})
			})
		}, complete: function(){
			$("#add_comment").loader(true)
		} })
	}
	$(".comment_edit_link").click(function(){
		var id = $(this).attr("id").replace(/comment_edit_/, "")
		Comment.edit(id)
		return false
	})
	$(".comment_delete_link").click(function(){
		notice++
		if (!confirm("<?php echo __("Are you sure you want to delete this comment?\\n\\nIt cannot be restored if you do this.", "comments"); ?>")) return false
		var id = $(this).attr("id").replace(/comment_delete_/, "")
		Comment.destroy(id)
		return false
	})
<!-- --></script>