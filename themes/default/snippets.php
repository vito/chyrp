<?php
	class Snippet {
		function no_posts($action) {
			switch($action) {
				default:
?>
		<h2><?php echo __("No Posts"); ?></h2>
		<div class="post body"><?php echo __("There aren't any posts here yet.", "theme"); ?></div>
<?php
					break;
				case "feather":
?>
		<h2><?php echo __("No Posts"); ?></h2>
		<div class="post body"><?php echo __("There aren't any posts of that Feather.", "theme"); ?></div>
<?php
					break;
				case "search":
?>
		<h2><?php echo __("No Results", "theme"); ?></h2>
		<div class="post body"><?php echo __("Your search did not match any posts.", "theme"); ?></div>
<?php
					break;
				case "drafts":
?>
		<h2><?php echo __("No Drafts", "theme"); ?></h2>
		<div class="post body"><?php echo __("There aren't any drafts yet.", "theme"); ?></div>
<?php
					break;
			}
		}
		function tags_top() {
			echo '<h2>'.__("Tags", "theme").'</h2><br />';
		}
		function above_post_added() {
			echo "<div class=\"success\" onClick=\"$(this).fadeOut('fast')\">".__("Post added.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
		function above_post_edited() {
			echo "<div class=\"success\" onClick=\"$(this).fadeOut('fast')\">".__("Post updated.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
	}