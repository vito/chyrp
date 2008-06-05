<?php
	class Tags extends Module {
		function __construct() {
			$this->addAlias('metaWeblog_newPost_preQuery', 'metaWeblog_editPost_preQuery');
			$this->addAlias("post_grab", "posts_get");
		}

		static function __install() {
			$sql = SQL::current();
			$sql->query("CREATE TABLE IF NOT EXISTS `__tags` (
			              `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
			              `tags` VARCHAR(250) DEFAULT '',
			              `clean` VARCHAR(250) DEFAULT '',
			              `post_id` INTEGER DEFAULT '0'
			             ) DEFAULT CHARSET=utf8");
			$route = Route::current();
			$route->add("tag/(name)/");
		}

		static function __uninstall($confirm) {
			if ($confirm)
				SQL::current()->query("drop table `__tags`");

			$route = Route::current();
			$route->remove("tag/(name)/");
		}

		static function new_post_options() {
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?> <span class="sub"><?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="tags" value="" id="tags" />
					</p>
<?php
		}

		static function edit_post_options($post) {
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?> <span class="sub"><?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="tags" value="<?php echo implode(", ", self::unlinked_tags($post->tags)) ?>" id="tags" />
					</p>
<?php
		}

		static function add_post($post) {
			if (empty($_POST['tags'])) return;

			$tags = explode(",", $_POST['tags']); // Split at the comma
			$tags = array_map("trim", $tags); // Remove whitespace
			$tags = array_map("strip_tags", $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			$tags_cleaned = array_map("sanitize", $tags);

			$tags_string = "{{".implode("}} {{", $tags)."}}";
			$tags_cleaned_string = "{{".implode("}} {{", $tags_cleaned)."}}";

			$sql = SQL::current();
			$sql->insert("tags", array("tags" => ":tags", "clean" => ":clean", "post_id" => ":post_id"), array(
			                 ":tags"    => $tags_string,
			                 ":clean"   => $tags_cleaned_string,
			                 ":post_id" => $post->id
			             ));
		}

		static function update_post($post) {
			$sql = SQL::current();
			$sql->delete("tags", "`post_id` = :post_id", array(":post_id" => $post->id));

			$tags = explode(",", $_POST['tags']); // Split at the comma
			$tags = array_map('trim', $tags); // Remove whitespace
			$tags = array_map('strip_tags', $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			$tags_cleaned = array_map("sanitize", $tags);

			$tags_string = (!empty($tags)) ? "{{".implode("}} {{", $tags)."}}" : "" ;
			$tags_cleaned_string = (!empty($tags_cleaned)) ? "{{".implode("}} {{", $tags_cleaned)."}}" : "" ;

			if (empty($tags_string) and empty($tags_cleaned_string))
				$sql->delete("tags", "`__tags`.`post_id` = :post_id", array(":post_id" => $post->id));
			else
				$sql->insert("tags", array("tags" => ":tags", "clean" => ":clean", "post_id" => ":post_id"), array(
				                 ":tags"    => $tags_string,
				                 ":clean"   => $tags_cleaned_string,
				                 ":post_id" => $post->id
				             ));
		}

		static function delete_post($post) {
			SQL::current()->delete("tags", "`post_id` = :post_id", array(":post_id" => $post->id));
		}

		static function parse_urls($urls) {
			$urls["/\/tag\/(.*?)\//"] = "?action=tag&amp;name=$1";
			return $urls;
		}

		static function manage_posts_column_header() {
			echo "<th>".__("Tags", "tags")."</th>";
		}

		static function manage_posts_column($post) {
			echo "<td>";
			echo implode(", ", $post->tags["linked"]);
			echo "</td>";
		}

		static function route_tag() {
			global $private, $posts;

			$posts = array();
			foreach (SQL::current()->select("tags", "*", "`clean` LIKE :tag", "`__tags`.`id`", array(":tag" => "%{{".$_GET['name']."}}%"))->fetchAll() as $tag)
				$posts[] = new Post($tag["post_id"]);
		}

		static function import_wordpress_post($data, $id) {
			if (isset($data["CATEGORY"])) {
				$tags    = "";
				$cleaned = "";
				foreach ($data["CATEGORY"] as $tag)
					if (isset($tag["attr"]["DOMAIN"]) and $tag["attr"]["DOMAIN"] == "tag" and !empty($tag["data"])) {
						$tags.=    "{{".strip_tags(trim($tag["data"]))."}} ";
						$cleaned.= "{{".sanitize(strip_tags(trim($tag["data"])))."}} ";
					}

				$sql = SQL::current();
				$sql->insert("tags", array("tags" => ":tags", "clean" => ":clean", "post_id" => ":post_id"), array(
				                 ":tags"    => trim($tags),
				                 ":clean"   => trim($cleaned),
				                 ":post_id" => $id
				             ));
			}
		}

		static function metaWeblog_getPost($post, $struct) {
			$struct['mt_tags'] = self::unlinked_tags($post->tags);
			return array($post, $struct);
		}

		static function metaWeblog_editPost_preQuery($struct, $post = null) {
			if (isset($struct['mt_tags']))
				$_POST['tags'] = $struct['mt_tags'];
			else if (isset($post->tags))
				$_POST['tags'] = $post->tags;
			else
				$_POST['tags'] = '';
		}

		static function twig_global_context($context) {
			$context["tags"] = list_tags();
			return $context;
		}

		static function posts_get($options) {
			$sql = SQL::current();

			$options["select"][] = "__tags.tags AS `tags`";
			$options["select"][] = "__tags.clean AS `clean_tags`";

			$options["left_join"][] = array("table" => "tags",
			                                "where" => "`__tags`.`post_id` = `__posts`.`id`");

			$options["params"][":current_ip"] = ip2long($_SERVER['REMOTE_ADDR']);
			$options["params"][":user_id"]    = Visitor::current()->id;

			$options["group"][] = "`__posts`.`id`";

			return $options;
		}

		static function linked_tags($tags, $cleaned_tags) {
			if (empty($tags) or empty($cleaned_tags))
				return array();

			$tags = explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $tags));
			$cleaned_tags = explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $cleaned_tags));

			$tags = array_combine($cleaned_tags, $tags);

			$linked = array();
			foreach ($tags as $clean => $tag)
				$linked[] = '<a href="'.Route::current()->url("tag/".$clean."/").'" rel="tag">'.$tag.'</a>';

			return $linked;
		}

		static function unlinked_tags($tags) {
			if (empty($tags))
				return array();

			return explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $tags));
		}

		static function filter_post($post) {
			if (!isset($post->tags))
				return $post->tags = array("unlinked" => array(), "linked" => array());

			$post->tags = array("unlinked" => self::unlinked_tags($post->tags),
			                    "linked"   => self::linked_tags($post->tags, $post->clean_tags));
		}
	}

	function sort_tags_name_asc($a, $b) {
		return strcmp($a["name"], $b["name"]);
	}
	function sort_tags_name_desc($a, $b) {
		return strcmp($b["name"], $a["name"]);
	}
	function sort_tags_popularity_asc($a, $b) {
		return $a["popularity"] > $b["popularity"];
	}
	function sort_tags_popularity_desc($a, $b) {
		return $a["popularity"] < $b["popularity"];
	}

	function list_tags($limit = 10, $order_by = "popularity", $order = "desc") {
		$sql = SQL::current();
		$tags = array();
		$clean = array();
		foreach($sql->query("select * from `__tags`")->fetchAll() as $tag) {
			$tags[] = $tag["tags"];
			$clean[] = $tag["clean"];
		}

		if (!count($tags))
			return array();

		# array("{{foo}} {{bar}}", "{{foo}}") to "{{foo}} {{bar}} {{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
		$tags = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $tags))));
		$clean = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $clean))));
		$tag2clean = array_combine(array_keys($tags), array_keys($clean));

		foreach ($tags as $name => $popularity)
			$tags[$name] = array("name" => $name, "popularity" => $popularity, "url" => $tag2clean[$name]);

		usort($tags, "sort_tags_".$order_by."_".$order);

		$count = 0;
		$return = array();
		foreach ($tags as $tag)
			if ($count++ < $limit)
				$return[] = $tag;

		return $return;
	}

	function clean2tag($clean_tag) {
		$tags = array();
		$clean = array();
		foreach(SQL::current()->query("select * from `__tags`")->fetchAll() as $tag) {
			$tags[] = $tag["tags"];
			$clean[] = $tag["clean"];
		}

		# array("{{foo}} {{bar}}", "{{foo}}") to "{{foo}} {{bar}} {{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
		$tags = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $tags))));
		$clean = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $clean))));
		$clean2tag = array_combine(array_keys($clean), array_keys($tags));

		return $clean2tag[$clean_tag];
	}

	function tag2clean($unclean_tag) {
		$tags = array();
		$clean = array();
		foreach(SQL::current()->query("select * from `__tags`")->fetchAll() as $tag) {
			$tags[] = $tag["tags"];
			$clean[] = $tag["clean"];
		}

		# array("{{foo}} {{bar}}", "{{foo}}") to "{{foo}} {{bar}} {{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
		$tags = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $tags))));
		$clean = array_count_values(explode(" ", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(" ", $clean))));
		$tag2clean = array_combine(array_keys($tags), array_keys($clean));

		return $tag2clean[$unclean_tag];
	}
