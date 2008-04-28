<?php
	class Snippet {
		function above_post_added() {
			echo "<div class=\"success\" onClick=\"$(this).fadeOut('fast')\">".__("Post added.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
		function above_post_edited() {
			echo "<div class=\"success\" onClick=\"$(this).fadeOut('fast')\">".__("Post updated.", "theme")."<span class=\"sub\">".__("(click to hide)", "theme")."</span></div>";
		}
	}