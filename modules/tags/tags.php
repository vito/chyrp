<?php
	class Tags extends Modules {
		public function __init() {
			$this->addAlias("metaWeblog_newPost_preQuery", "metaWeblog_editPost_preQuery");
			$this->addAlias("post_grab", "posts_get");
			$this->addAlias("javascript", "cloudSelectorJS");
		}

		static function __install() {
			$sql = SQL::current();
			$sql->query("CREATE TABLE IF NOT EXISTS __tags (
			              id INTEGER PRIMARY KEY AUTO_INCREMENT,
			              tags VARCHAR(250) DEFAULT '',
			              clean VARCHAR(250) DEFAULT '',
			              post_id INTEGER DEFAULT '0'
			             ) DEFAULT CHARSET=utf8");
			Route::current()->add("tag/(name)/", "tag");
		}

		static function __uninstall($confirm) {
			if ($confirm)
				SQL::current()->query("DROP TABLE __tags");

			Route::current()->remove("tag/(name)/");
		}

		public function admin_head() {
			$config = Config::current();
?>
		<script type="text/javascript">
<?php $this->cloudSelectorJS(); ?>
		</script>
		<link rel="stylesheet" href="<?php echo $config->chyrp_url; ?>/modules/tags/admin.css" type="text/css" media="screen" title="no title" charset="utf-8" />
<?php
		}

		public function new_post_options() {
			$tags = self::list_tags();
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?> <span class="sub"><?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="tags" value="" id="tags" />
						<span class="tags_select">
<?php foreach ($tags as $tag): ?>
							<a href="javascript:add_tag('<?php echo addslashes($tag["name"]); ?>', '.tag_<?php echo addslashes($tag["url"]); ?>')" class="tag_<?php echo $tag["url"]; ?>"><?php echo $tag["name"]; ?></a>
<?php endforeach; ?>
						</span>
					</p>
<?php
		}

		public function edit_post_options($post) {
			$tags = self::list_tags();
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?> <span class="sub"><?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="tags" value="<?php echo implode(", ", self::unlinked_tags($post->unclean_tags)) ?>" id="tags" />
						<span class="tags_select">
<?php foreach ($tags as $tag): ?>
							<a href="javascript:add_tag('<?php echo addslashes($tag["name"]); ?>', '.tag_<?php echo addslashes($tag["url"]); ?>')" class="tag_<?php echo $tag["url"]; ?>"><?php echo $tag["name"]; ?></a>
<?php endforeach; ?>
						</span>
					</p>
<?php
		}

		public function bookmarklet_submit_values($values) {
			$tags = array();
			foreach ($values as &$value) {
				if (preg_match_all("/(\s|^)#([a-zA-Z0-9 ]+)(?!\\\\)#/", $value, $double)) {
					$tags = array_merge($double[2], $tags);
					$value = preg_replace("/(\s|^)#([a-zA-Z0-9 ]+)(?!\\\\)#/", "\\1", $value);
				}
				if (preg_match_all("/(\s|^)#([a-zA-Z0-9]+)(?!#)/", $value, $single)) {
					$tags = array_merge($single[1], $tags);
					$value = preg_replace("/(\s|^)#([a-zA-Z0-9]+)(?!#)/", "\\1\\2", $value);
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
			$sql->insert("tags", array("tags" => $tags_string, "clean" => $tags_cleaned_string, "post_id" => $post->id));
		}

		public function update_post($post) {
			if (!isset($_POST['tags'])) return;

			$sql = SQL::current();
			$sql->delete("tags", array("post_id" => $post->id));

			$tags = explode(",", $_POST['tags']); // Split at the comma
			$tags = array_map('trim', $tags); // Remove whitespace
			$tags = array_map('strip_tags', $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			$tags_cleaned = array_map("sanitize", $tags);

			$tags_string = (!empty($tags)) ? "{{".implode("}},{{", $tags)."}}" : "" ;
			$tags_cleaned_string = (!empty($tags_cleaned)) ? "{{".implode("}},{{", $tags_cleaned)."}}" : "" ;

			if (empty($tags_string) and empty($tags_cleaned_string))
				$sql->delete("tags", array("post_id" => $post->id));
			else
				$sql->insert("tags", array("tags" => $tags_string, "clean" => $tags_cleaned_string, "post_id" => $post->id));
		}

		public function delete_post($post) {
			SQL::current()->delete("tags", array("post_id" => $post->id));
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

		static function manage_nav($navs) {
			if (!Post::any_editable())
				return $navs;

			$navs["manage_tags"] = array("title" => __("Tags", "tags"),
			                             "selected" => array("rename_tag", "delete_tag", "edit_tags"));

			return $navs;
		}

		static function manage_nav_pages($pages) {
			array_push($pages, "manage_tags", "rename_tag", "delete_tag", "edit_tags");
			return $pages;
		}

		public function admin_manage_tags($admin) {
			$sql = SQL::current();

			$tags = array();
			$clean = array();
			foreach($sql->select("posts",
				                 "tags.*",
				                 array(Post::$private, Post::$enabled_feathers),
				                 null,
				                 array(),
				                 null, null, null,
				                 array(array("table" => "tags",
				                             "where" => "tags.post_id = posts.id")))->fetchAll() as $tag) {
				if ($tag["id"] == null)
					continue;

				$tags[] = $tag["tags"];
				$clean[] = $tag["clean"];
			}

			list($tags, $clean, $tag2clean,) = self::parseTags($tags, $clean);

			$max_qty = max(array_values($tags));
			$min_qty = min(array_values($tags));

			$spread = $max_qty - $min_qty;
			if ($spread == 0)
				$spread = 1;

			$step = 75 / $spread;

			$context = array();
			foreach ($tags as $tag => $count)
				$context[] = array("size" => (100 + (($count - $min_qty) * $step)),
				                   "popularity" => $count,
				                   "name" => $tag,
				                   "title" => sprintf(_p("%s post tagged with &quot;%s&quot;", "%s posts tagged with &quot;%s&quot;", $count, "tags"), $count, $tag),
				                   "clean" => $tag2clean[$tag],
				                   "url" => url("tag/".$tag2clean[$tag]."/"));

			$admin->context["tag_cloud"] = $context;

			if (!Post::any_editable() and !Post::any_deletable())
				return;

			fallback($_GET['query'], "");
			list($where, $params) = keywords(urldecode($_GET['query']), "xml LIKE :query");

			$visitor = Visitor::current();
			if (!$visitor->group()->can("view_draft", "edit_draft", "edit_post", "delete_draft", "delete_post"))
				$where["user_id"] = $visitor->id;

			$admin->context["posts"] = new Paginator(Post::find(array("placeholders" => true,
			                                                          "drafts" => true,
			                                                          "where" => $where,
			                                                          "params" => $params)), 25);
		}

		public function admin_rename_tag($admin) {
			$sql = SQL::current();

			$tags = array();
			$clean = array();
			foreach($sql->select("posts",
				                 "tags.*",
				                 array(Post::$private, Post::$enabled_feathers),
				                 null,
				                 array(),
				                 null,
				                 null,
				                 null,
				                 array(array("table" => "tags",
				                             "where" => array("post_id = posts.id", array("clean like" => "%{{".$_GET['name']."}}%"))))->fetchAll() as $tag) {
				if ($tag["id"] == null)
					continue;

				$tags[] = $tag["tags"];
				$clean[] = $tag["clean"];
			}

			list($tags, $clean, $tag2clean,) = self::parseTags($tags, $clean);

			foreach ($tags as $tag => $count)
				if ($tag2clean[$tag] == $_GET['name'])
					return $admin->context["tag"] = array("name" => $tag, "clean" => $tag2clean[$tag]);
		}

		public function admin_edit_tags($admin) {
			$sql = SQL::current();

			if (!isset($_GET['id']))
				error(__("No ID Specified"), __("Please specify the ID of the post whose tags you would like to edit.", "tags"));

			$admin->context["post"] = new Post($_GET['id']);
		}

		public function admin_update_tags($admin) {
			$sql = SQL::current();

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			if (!isset($_POST['id']))
				error(__("No ID Specified"), __("Please specify the ID of the post whose tags you would like to edit.", "tags"));

			$this->update_post(new Post($_POST['id']));

			Flash::notice(__("Tags updated.", "tags"), "/admin/?action=manage_tags");
		}

		public function admin_update_tag($admin) {
			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$sql = SQL::current();

			$tags = array();
			$clean = array();
			foreach($sql->select("tags",
				                 "*",
				                 array("clean like" => "%{{".$_POST['clean']."}}%"))->fetchAll() as $tag) {
				$names = str_replace("{{".$_POST['clean']."}}", "{{".$_POST['name']."}}", $tag["tags"]);
				$clean = str_replace("{{".$_POST['clean']."}}", "{{".sanitize($_POST['name'])."}}", $tag["clean"]);
				$sql->update("tags",
				             array("id" => $tag["id"]),
				             array("id" => $tag["id"],
				                   "tags" => $names));
			}

			Flash::notice(__("Tag renamed.", "tags"), "/admin/?action=manage_tags");
		}

		public function admin_delete_tag($admin) {
			$sql = SQL::current();

			foreach($sql->select("tags",
				                 "*",
				                 array("clean like" => "%{{".urldecode($_GET['clean'])."}}%"))->fetchAll() as $tag)  {
				$names = array();
				foreach (explode("}},{{", substr(substr($tag["tags"], 0, -2), 2)) as $name)
					if ($name != $_GET['name'])
						$names[] = "{{".$name."}}";

				$cleans = array();
				foreach (explode("}},{{", substr(substr($tag["clean"], 0, -2), 2)) as $clean)
					if ($clean != $_GET['clean'])
						$cleans[] = "{{".$clean."}}";

				if (empty($names) or empty($cleans))
					$sql->delete("tags", array("id" => $tag["id"]));
				else
					$sql->update("tags",
					             array("id" => $tag["id"]),
					             array("tags" => join(",", $names),
					                   "clean" => join(",", $cleans)));
			}

			Flash::notice(__("Tag deleted.", "tags"), "/admin/?action=manage_tags");
		}

		public function admin_bulk_tag($admin) {
			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			if (empty($_POST['name']) or empty($_POST['post']))
				redirect("/admin/?action=manage_tags");

			$sql = SQL::current();

			foreach ($_POST['post'] as $post_id) {
				$post = new Post($post_id);
				$_POST['tags'] = join(", ", $post->tags["unlinked"]).", ".$_POST['name'];
				$this->update_post($post);
			}

			Flash::notice(__("Posts tagged.", "tags"), "/admin/?action=manage_tags");
		}

		public function check_route_tag() {
			global $posts;

			$posts = new Paginator(Post::find(array("placeholders" => true,
			                                        "where" => array("tags.clean like" => "%{{".$_GET['name']."}}%"))),
			                       Config::current()->posts_per_page);

			return !empty($posts->paginated);
		}

		public function route_tag() {
			global $posts;

			if (isset($posts))
				return;
			else
				return $this->check_route_tag();
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
				                       array("tags" => rtrim($tags, ","),
				                             "clean" => rtrim($cleaned, ","),
				                             "post_id" => $post->id));
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
				                       array("tags" => rtrim($tags, ","),
				                             "clean" => rtrim($cleaned, ","),
				                             "post_id" => $post->id));
		}

		public function import_movabletype_post($array, $post, $link) {
			$get_pointers = mysql_query("SELECT * FROM mt_objecttag WHERE objecttag_object_id = {$array["entry_id"]} ORDER BY objecttag_object_id ASC", $link) or error(__("Database Error"), mysql_error());
			if (!mysql_num_rows($get_pointers))
				return;

			$tags = array();
			while ($pointer = mysql_fetch_array($get_pointers)) {
				$get_dirty_tag = mysql_query("SELECT tag_name, tag_n8d_id FROM mt_tag WHERE tag_id = {$pointer["objecttag_tag_id"]}", $link) or error(__("Database Error"), mysql_error());
				if (!mysql_num_rows($get_dirty_tag)) continue;

				$dirty_tag = mysql_fetch_array($get_dirty_tag);
				$dirty = $dirty_tag["tag_name"];

				$clean_tag = mysql_query("SELECT tag_name FROM mt_tag WHERE tag_id = {$dirty_tag["tag_n8d_id"]}", $link) or error(__("Database Error"), mysql_error());
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
			$sql->insert("tags", array("tags" => $dirty_string, "clean" => $clean_string, "post_id" => $post->id));
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
			$options["select"][] = "tags.tags AS unclean_tags";
			$options["select"][] = "tags.clean AS clean_tags";

			$options["left_join"][] = array("table" => "tags",
			                                "where" => "post_id = posts.id");

			$options["group"][] = "id";

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
			if (empty($post->unclean_tags)) {
				$post->tags = array("info" => array(), "unlinked" => array(), "linked" => array());
				return;
			}

			list($tags, $clean, $tag2clean,) = self::parseTags(array($post->unclean_tags), array($post->clean_tags));

			$post->tags = array();

			foreach ($tags as $tag => $count)
				$post->tags["info"][] = array("name" => $tag,
			                                  "clean" => $tag2clean[$tag],
			                                  "url" => url("tag/".$tag2clean[$tag]."/"));

			$post->tags["unlinked"] = self::unlinked_tags($post->unclean_tags);
			$post->tags["linked"]   = self::linked_tags($post->unclean_tags, $post->clean_tags);
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
			                      "tags.*",
			                      array(Post::$private, Post::$enabled_feathers),
			                      null,
			                      array(),
			                      null, null, null,
			                      array(array("table" => "tags",
			                                  "where" => "post_id = posts.id")));

			$unclean = array();
			$clean = array();
			while ($tag = $tags->fetchObject()) {
				if (!isset($tag->id)) continue;
				$unclean[] = $tag->tags;
				$clean[] = $tag->clean;
			}

			if (!count($unclean))
				return array();

			list($unclean, $clean, $tag2clean,) = self::parseTags($unclean, $clean);

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

			list($tags, $clean, $tag2clean, $clean2tag) = self::parseTags($tags, $clean);

			return $clean2tag[$clean_tag];
		}

		static function tag2clean($unclean_tag) {
			$tags = array();
			$clean = array();
			foreach(SQL::current()->select("tags")->fetchAll() as $tag) {
				$tags[] = $tag["tags"];
				$clean[] = $tag["clean"];
			}

			list($tags, $clean, $tag2clean) = self::parseTags($tags, $clean);

			return $tag2clean[$unclean_tag];
		}

		public function posts_export($atom, $post) {
			$tags = SQL::current()->select("tags", "tags", array("post_id" => $post->id), "id DESC")->fetchColumn();
			if (empty($tags)) return;

			$atom.= "		<chyrp:tags>".fix(implode(", ", self::unlinked_tags($tags)))."</chyrp:tags>\r";
			return $atom;
		}

		public function cloudSelectorJS() {
?>
			$(function(){
				function scanTags(){
					$(".tags_select a").each(function(){
						regexp = new RegExp("(, ?|^)"+ $(this).text() +"(, ?|$)", "g")
						if ($("#tags").val().match(regexp))
							$(this).addClass("tag_added")
						else
							$(this).removeClass("tag_added")
					})
				}
				scanTags()
				$("#tags").livequery("keyup", scanTags)
				$(".tag_cloud > span").livequery("mouseover", function(){
					$(this).find(".controls").css("opacity", 1)
				}).livequery("mouseout", function(){
					$(this).find(".controls").css("opacity", 0)
				})
			})

			function add_tag(name, link) {
				if ($("#tags").val().match("(, |^)"+ name +"(, |$)")) {
					regexp = new RegExp("(, |^)"+ name +"(, |$)", "g")
					$("#tags").val($("#tags").val().replace(regexp, function(match, before, after){
						if (before == ", " && after == ", ")
							return ", "
						else
							return ""
					}))

					$(link).removeClass("tag_added")
				} else {
					if ($("#tags").val() == "")
						$("#tags").val(name)
					else
						$("#tags").val($("#tags").val() + ", "+ name)

					$(link).addClass("tag_added")
				}
			}
<?php
		}

		# array("{{foo}},{{bar}}", "{{foo}}")
		# to
		# "{{foo}},{{bar}},{{foo}}"
		# to
		# array("foo", "bar", "foo")
		# to
		# array("foo" => 2, "bar" => 1)
		static function parseTags($tags, $clean) {
			$tags = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $tags))));
			$clean = array_count_values(explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $clean))));
			$tag2clean = array_combine(array_keys($tags), array_keys($clean));
			$clean2tag = array_combine(array_keys($clean), array_keys($tags));
			return array($tags, $clean, $tag2clean, $clean2tag);
		}
	}
