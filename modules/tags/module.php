<?php
	class Tags extends Module {
		function __construct() {
			$this->addAlias('metaWeblog_newPost_preQuery', 'metaWeblog_editPost_preQuery');
		}

		static function __install() {
			$sql = SQL::current();
			$sql->query("create table if not exists `".$sql->prefix."tags` (
			              `id` int(11) not null auto_increment,
			              `name` varchar(250) not null,
			              `post_id` int(11) not null,
			              `clean` varchar(250) not null,
			              primary key (`id`)
			             ) default charset=utf8");
			$route = Route::current();
			$route->add("tag/(name)/");
		}

		static function __uninstall($confirm) {
			if ($confirm) {
				$sql = SQL::current();
				error_log("$confirm is true");
				#$sql->query("drop table `".$sql->prefix."tags`");
			}
			$route = Route::current();
			$route->remove("tag/(name)/");
		}

		static function new_post_options() {
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?><span class="sub"> <?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="option[tags]" value="" id="tags" />
					</p>
<?php
		}

		static function edit_post_options($id) {
			$tags = implode(", ", get_post_tags($id, false));
?>
					<p>
						<label for="tags"><?php echo __("Tags", "tags"); ?><span class="sub"> <?php echo __("(comma separated)", "tags"); ?></span></label>
						<input class="text" type="text" name="option[tags]" value="<?php echo fix($tags, "html"); ?>" id="tags" />
					</p>
<?php
		}

		static function add_post($post, $options) {
			if (!isset($options["tags"])) return;

			$tags = explode(",", $options["tags"]); // Split at the comma
			$tags = array_map('trim', $tags); // Remove whitespace
			$tags = array_map('strip_tags', $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			$sql = SQL::current();
			foreach ($tags as $tag) {
				$sql->query("insert into `".$sql->prefix."tags`
				             (`name`, `post_id`, `clean`)
				             values
				             (:name, :post_id, :clean)",
				            array(
				                ":name" => $tag,
				                ":post_id" => $post->id,
				                ":clean" => sanitize($tag)
				            ));
			}
		}

		static function update_post($post, $options) {
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."tags`
			             where `post_id` = :id",
			            array(
			                ":id" => $post->id
			            ));

			$tags = explode(",", $options["tags"]); // Split at the comma
			$tags = array_map('trim', $tags); // Remove whitespace
			$tags = array_map('strip_tags', $tags); // Remove HTML
			$tags = array_unique($tags); // Remove duplicates
			$tags = array_diff($tags, array("")); // Remove empties
			foreach ($tags as $tag) {
				$sql->query("insert into `".$sql->prefix."tags`
				             (`name`, `post_id`, `clean`)
				             values
				             (:name, :post_id, :clean)",
				            array(
				                ":name" => $tag,
				                ":post_id" => $post->id,
				                ":clean" => sanitize($tag)
				            ));
			}
		}

		static function delete_post($post) {
			$sql = SQL::current();
			$sql->query("delete from `".$sql->prefix."tags`
			             where `post_id` = :id",
			            array(
			                ":id" => $post->id
			            ));
		}

		static function parse_urls($urls) {
			$urls["/\/tag\/(.*?)\//"] = "?action=tag&amp;name=$1";
			return $urls;
		}

		static function admin_manage_posts_column_header() {
			echo "<th>".__("Tags", "tags")."</th>";
		}

		static function admin_manage_posts_column($id) {
			echo "<td>";
			echo implode(", ", get_post_tags($id));
			echo "</td>";
		}

		static function route_tag() {
			global $paginate, $private, $enabled_feathers, $tag, $get_posts;
			$tag = $_GET['name'];

			$config = Config::current();
			$sql = SQL::current();
			$get_posts = $paginate->select(array("posts AS p", "tags AS t"), # from
			                               "p.*", # fields
			                               $private.$enabled_feathers." and
			                               `post_id` = `p`.`id` and
			                               `t`.`clean` = :clean",
			                               "`pinned` desc, `created_at` desc, `p`.`id` desc",
			                               $config->posts_per_page, "page",
			                               array(
			                                   ":clean" => $tag
			                               ));
		}

		static function import_wordpress_post($data, $id) {
			if (isset($data["CATEGORY"])) {
				$sql = SQL::current();
				foreach ($data["CATEGORY"] as $tag) {
					if (!isset($tag["attr"]["DOMAIN"]) or $tag["attr"]["DOMAIN"] != "tag") continue;

					$sql->query("insert into `".$sql->prefix."tags`
					             (`name`, `post_id`, `clean`)
					             values
					             (:name, :post_id, :clean)",
					            array(
					                ":name" => $tag["data"],
					                ":post_id" => $id,
					                ":clean" => sanitize($tag["data"])
					            ));
				}
			}
		}

		static function metaWeblog_getPost($post, $struct) {
			$struct['mt_tags'] = $post->tags;
			return array($post, $struct);
		}

		static function metaWeblog_editPost_preQuery($struct, $post = null) {
			if (isset($struct['mt_tags']))
				$_POST['option']['tags'] = $struct['mt_tags'];
			else if (isset($post->tags))
				$_POST['option']['tags'] = $post->tags;
			else
				$_POST['option']['tags'] = '';
		}

		static function twig_global_context($context) {
			$context["tags"] = list_tags();
			return $context;
		}

		static function filter_post($post) {
			$post->tags = array("linked" => get_post_tags($post->id), "unlinked" => get_post_tags($post->id, false));
		}
	}
	$tags = new Tags();

	$tags_limit_reached = false;
	function list_tags($limit = 10, $order_by = "id", $order = "asc") {
		global $tags_limit_reached;

		$sql = SQL::current();
		$order_by = (("count" != $order_by) ? $sql->prefix."tags`.`" : "").$order_by;

		$get_tags = $sql->query("select
		                             `name`, `".$sql->prefix."tags`.`clean` as `clean`,
		                             `".$sql->prefix."tags`.`post_id` as `target_id`,
		                             `".$sql->prefix."posts`.`id` as `post_id`,
		                             `".$sql->prefix."tags`.`id` as `tag_id`,
		                             count(`".$sql->prefix."tags`.`id`) as `count`
		                         from `".$sql->prefix."tags`, `".$sql->prefix."posts`
		                         where
		                             `".$sql->prefix."tags`.`post_id` = `".$sql->prefix."posts`.`id` and
		                             `status` = 'public'
		                         group by `name`
		                         order by `".$order_by."` ".$order);

		$tags = array();
		$count = 0;
		while ($tag = $get_tags->fetchObject()) {
			if ($count < $limit) {
				$tags[$tag->tag_id]["id"] = $tag->tag_id;
				$tags[$tag->tag_id]["name"] = $tag->name;
				$tags[$tag->tag_id]["count"] = $tag->count;
				$tags[$tag->tag_id]["post_id"] = $tag->target_id;
				$tags[$tag->tag_id]["url"] = $tag->clean;
			}
			$count++;
		}
		if ($count > $limit)
			$tags_limit_reached = true;

		return $tags;
	}

	function tags_limit_reached() {
		global $tags_limit_reached;
		return $tags_limit_reached;
	}

	function get_post_tags($post_id, $links = true, $order_by = "id", $order = "asc"){
		$sql = SQL::current();
		$get_tags = $sql->query("select * from `".$sql->prefix."tags`
		                         where `post_id` = :id
		                         order by `".$order_by."` ".$order,
		                        array(
		                            ":id" => $post_id
		                        ));

		$tags = array();
		$route = Route::current();

		while ($tag = $get_tags->fetchObject())
			$tags[] = ($links ? '<a href="'.$route->url("tag/".$tag->clean."/").'" rel="tag">' : '').$tag->name.($links ? '</a>' : '');

		return $tags;
	}
