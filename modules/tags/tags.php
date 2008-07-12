<?php
	class Tags extends Module {
		public function __construct() {
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
			Route::current()->add("tag/(name)/");
		}

		static function __uninstall($confirm) {
			if ($confirm)
				SQL::current()->query("DROP TABLE `__tags`");

			Route::current()->remove("tag/(name)/");
		}

		public function new_post_options() {
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?> <span class="sub"><?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="tags" value="" id="tags" />
					</p>
<?php
		}

		public function edit_post_options($post) {
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?> <span class="sub"><?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="tags" value="<?php echo implode(", ", self::unlinked_tags($post->unclean_tags)) ?>" id="tags" />
					</p>
<?php
		}

		public function bookmarklet_submit_values($values) {
			$tags = array();
			foreach ($values as &$value) {
				if (preg_match_all("/([^\\\\]|^)#([a-zA-Z0-9 ]+)(?!\\\\)#/", $value, $double)) {
					$tags = array_merge($double[2], $tags);
					$value = preg_replace("/([^\\\\]|^)#([a-zA-Z0-9 ]+)(?!\\\\)#/", "\\1", $value);
				}
				if (preg_match_all("/[^\\\\]#([a-zA-Z0-9]+)(?!\\\\#)/", $value, $single)) {
					$tags = array_merge($single[1], $tags);
					$value = preg_replace("/([^\\\\]|^)#([a-zA-Z0-9]+)(?!\\\\#)/", "\\1\\2", $value);
				}
				$_POST['tags'] = implode(", ", $tags);
				$value = str_replace("\\#", "#", $value);
			}
		}

		public function add_post($post) {
			if (empty($_POST['tags'])) return;

			$tags = explode(",", $_POST['tags']); // Split at the comma
			$tags = array_map("trim", $tags); // Remove whitespace
			$tags = array_map("strip_tags", $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			$tags_cleaned = array_map("sanitize", $tags);

			$tags_string = "{{".implode("}},{{", $tags)."}}";
			$tags_cleaned_string = "{{".implode("}},{{", $tags_cleaned)."}}";

			$sql = SQL::current();
			$sql->insert("tags", array("tags" => ":tags", "clean" => ":clean", "post_id" => ":post_id"), array(
			                 ":tags"    => $tags_string,
			                 ":clean"   => $tags_cleaned_string,
			                 ":post_id" => $post->id
			             ));
		}

		public function update_post($post) {
			if (!isset($_POST['tags'])) return;

			$sql = SQL::current();
			$sql->delete("tags", "`post_id` = :post_id", array(":post_id" => $post->id));

			$tags = explode(",", $_POST['tags']); // Split at the comma
			$tags = array_map('trim', $tags); // Remove whitespace
			$tags = array_map('strip_tags', $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			$tags_cleaned = array_map("sanitize", $tags);

			$tags_string = (!empty($tags)) ? "{{".implode("}},{{", $tags)."}}" : "" ;
			$tags_cleaned_string = (!empty($tags_cleaned)) ? "{{".implode("}},{{", $tags_cleaned)."}}" : "" ;

			if (empty($tags_string) and empty($tags_cleaned_string))
				$sql->delete("tags", "`__tags`.`post_id` = :post_id", array(":post_id" => $post->id));
			else
				$sql->insert("tags", array("tags" => ":tags", "clean" => ":clean", "post_id" => ":post_id"), array(
				                 ":tags"    => $tags_string,
				                 ":clean"   => $tags_cleaned_string,
				                 ":post_id" => $post->id
				             ));
		}

		public function delete_post($post) {
			SQL::current()->delete("tags", "`post_id` = :post_id", array(":post_id" => $post->id));
		}

		public function parse_urls($urls) {
			$urls["/\/tag\/(.*?)\//"] = "/?action=tag&amp;name=$1";
			return $urls;
		}

		public function manage_posts_column_header() {
			echo "<th>".__("Tags", "tags")."</th>";
		}

		public function manage_posts_column($post) {
			echo "<td>".implode(", ", $post->tags["linked"])."</td>";
		}

		public function route_tag() {
			global $posts;

			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => "__tags.clean LIKE :tag",
			                                        "params" => array(":tag" => "%{{".$_GET['name']."}}%"))),
			                       Config::current()->posts_per_page);
		}

		public function import_chyrp_post($entry, $post) {
			$chyrp = $entry->children("http://chyrp.net/export/1.0/");
			if (!isset($chyrp->tags)) return;

			$tags = $cleaned = "";
			foreach (explode(", ", $chyrp->tags) as $tag)
				if (!empty($tag)) {
					$tags.=    "{{".strip_tags(trim($tag))."}},";
					$cleaned.= "{{".sanitize(strip_tags(trim($tag)))."}},";
				}

			if (!empty($tags) and !empty($cleaned))
				SQL::current()->insert("tags",
				                       array("tags"     => ":tags",
				                             "clean"    => ":clean",
				                             "post_id"  => ":post_id"),
				                       array(":tags"    => rtrim($tags, ","),
				                             ":clean"   => rtrim($cleaned, ","),
				                             ":post_id" => $post->id));
		}

		public function import_wordpress_post($item, $post) {
			if (!isset($item->category)) return;

			$tags = $cleaned = "";
			foreach ($item->category as $tag)
				if (isset($tag->attributes()->domain) and $tag->attributes()->domain == "tag" and !empty($tag) and isset($tag->attributes()->nicename)) {
					$tags.=    "{{".strip_tags(trim($tag))."}},";
					$cleaned.= "{{".sanitize(strip_tags(trim($tag)))."}},";
				}

			if (!empty($tags) and !empty($cleaned))
				SQL::current()->insert("tags",
				                       array("tags"     => ":tags",
				                             "clean"    => ":clean",
				                             "post_id"  => ":post_id"),
				                       array(":tags"    => rtrim($tags, ","),
				                             ":clean"   => rtrim($cleaned, ","),
				                             ":post_id" => $post->id));
		}

		public function import_movabletype_post($array, $post, $link) {
			$get_pointers = mysql_query("SELECT * FROM `mt_objecttag` WHERE `objecttag_object_id` = {$array["entry_id"]} ORDER BY `objecttag_object_id` ASC", $link) or error(__("Database Error"), mysql_error());
			if (!mysql_num_rows($get_pointers))
				return;

			$tags = array();
			while ($pointer = mysql_fetch_array($get_pointers)) {
				$get_dirty_tag = mysql_query("SELECT `tag_name`, `tag_n8d_id` FROM `mt_tag` WHERE `tag_id` = {$pointer["objecttag_tag_id"]}", $link) or error(__("Database Error"), mysql_error());
				if (!mysql_num_rows($get_dirty_tag)) continue;

				$dirty_tag = mysql_fetch_array($get_dirty_tag);
				$dirty = $dirty_tag["tag_name"];

				$clean_tag = mysql_query("SELECT `tag_name` FROM `mt_tag` WHERE `tag_id` = {$dirty_tag["tag_n8d_id"]}", $link) or error(__("Database Error"), mysql_error());
				if (mysql_num_rows($clean_tag))
					$clean = mysql_result($clean_tag, 0);
				else
					$clean = $dirty;

				$tags[$dirty] = $clean;
			}

			if (empty($tags))
				return;

			$dirty_string = "{{".implode("}},{{", array_keys($tags))."}}";
			$clean_string = "{{".implode("}},{{", array_values($tags))."}}";

			$sql = SQL::current();
			$sql->insert("tags", array("tags" => ":tags", "clean" => ":clean", "post_id" => ":post_id"), array(
			                 ":tags"    => $dirty_string,
			                 ":clean"   => $clean_string,
			                 ":post_id" => $post->id
			             ));
		}

		public function metaWeblog_getPost($struct, $post) {
			if (!isset($post->unclean_tags))
				$struct['mt_tags'] = "";
			else
				$struct['mt_tags'] = implode(", ", self::unlinked_tags($post->unclean_tags));

			return $struct;
		}

		public function metaWeblog_editPost_preQuery($struct, $post = null) {
			if (isset($struct['mt_tags']))
				$_POST['tags'] = $struct['mt_tags'];
			else if (isset($post->tags))
				$_POST['tags'] = $post->tags["unlinked"];
			else
				$_POST['tags'] = '';
		}

		public function twig_global_context($context) {
			$context["tags"] = self::list_tags();
			return $context;
		}

		public function posts_get($options) {
			$options["select"][] = "__tags.tags AS `unclean_tags`";
			$options["select"][] = "__tags.clean AS `clean_tags`";

			$options["left_join"][] = array("table" => "tags",
			                                "where" => "`__tags`.`post_id` = `__posts`.`id`");

			$options["group"][] = "`__posts`.`id`";

			return $options;
		}

		static function linked_tags($tags, $cleaned_tags) {
			if (empty($tags) or empty($cleaned_tags))
				return array();

			$tags = explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $tags));
			$cleaned_tags = explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $cleaned_tags));

			$tags = array_combine($cleaned_tags, $tags);

			$linked = array();
			foreach ($tags as $clean => $tag)
				$linked[] = '<a href="'.url("tag/".$clean."/").'" rel="tag">'.$tag.'</a>';

			return $linked;
		}

		static function unlinked_tags($tags) {
			if (empty($tags))
				return array();

			return explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $tags));
		}

		public function filter_post($post) {
			if (!isset($post->unclean_tags))
				$post->tags = array("unlinked" => array(), "linked" => array());
			else
				$post->tags = array("unlinked" => self::unlinked_tags($post->unclean_tags),
				                    "linked"   => self::linked_tags($post->unclean_tags, $post->clean_tags));
		}

		public function sort_tags_name_asc($a, $b) {
			return strcmp($a["name"], $b["name"]);
		}
		public function sort_tags_name_desc($a, $b) {
			return strcmp($b["name"], $a["name"]);
		}
		public function sort_tags_popularity_asc($a, $b) {
			return $a["popularity"] > $b["popularity"];
		}
		public function sort_tags_popularity_desc($a, $b) {
			return $a["popularity"] < $b["popularity"];
		}

		public function list_tags($limit = 10, $order_by = "popularity", $order = "desc") {
			$sql = SQL::current();

			$tags = $sql->select("posts",
			                      "__tags.*",
			                      array(Post::$private, Post::$enabled_feathers),
			                      null,
			                      array(),
			                      null, null, null,
			                      array(array("table" => "tags",
			                                  "where" => "`__tags`.`post_id` = `__posts`.`id`")));

			$unclean = array();
			$clean = array();
			while ($tag = $tags->fetchObject()) {
				if (!isset($tag->id)) continue;
				$unclean[] = $tag->tags;
				$clean[] = $tag->clean;
			}

			if (!count($unclean))
				return array();

			# array("{{foo}},{{bar}}", "{{foo}}") to "{{foo}},{{bar}},{{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
			$unclean = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $unclean))));
			$clean = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $clean))));
			$tag2clean = array_combine(array_keys($unclean), array_keys($clean));

			foreach ($unclean as $name => $popularity)
				$unclean[$name] = array("name" => $name, "popularity" => $popularity, "url" => $tag2clean[$name]);

			usort($unclean, array($this, "sort_tags_".$order_by."_".$order));

			$count = 0;
			$return = array();
			foreach ($unclean as $tag)
				if ($count++ < $limit)
					$return[] = $tag;

			return $return;
		}

		static function clean2tag($clean_tag) {
			$tags = array();
			$clean = array();
			foreach(SQL::current()->select("tags")->fetchAll() as $tag) {
				$tags[] = $tag["tags"];
				$clean[] = $tag["clean"];
			}

			# array("{{foo}},{{bar}}", "{{foo}}") to "{{foo}},{{bar}},{{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
			$tags = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $tags))));
			$clean = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $clean))));
			$clean2tag = array_combine(array_keys($clean), array_keys($tags));

			return $clean2tag[$clean_tag];
		}

		static function tag2clean($unclean_tag) {
			$tags = array();
			$clean = array();
			foreach(SQL::current()->select("tags")->fetchAll() as $tag) {
				$tags[] = $tag["tags"];
				$clean[] = $tag["clean"];
			}

			# array("{{foo}},{{bar}}", "{{foo}}") to "{{foo}},{{bar}},{{foo}}" to array("foo", "bar", "foo") to array("foo" => 2, "bar" => 1)
			$tags = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $tags))));
			$clean = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $clean))));
			$tag2clean = array_combine(array_keys($tags), array_keys($clean));

			return $tag2clean[$unclean_tag];
		}

		public function posts_export($atom, $post) {
			$tags = SQL::current()->select("tags", "tags", "__tags.post_id = :post_id", "__tags.id DESC", array(":post_id" => $post->id))->fetchColumn();
			if (empty($tags)) return;

			$atom.= "		<chyrp:tags>".safe(implode(", ", self::unlinked_tags($tags)))."</chyrp:tags>\r";
			return $atom;
		}
	}
