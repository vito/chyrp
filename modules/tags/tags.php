<?php
    class Tags extends Modules {
        public function __init() {
            $this->addAlias("metaWeblog_newPost_preQuery", "metaWeblog_editPost_preQuery");
            $this->addAlias("javascript", "cloudSelectorJS");
        }

        static function __install() {
            Route::current()->add("tag/(name)/", "tag");
        }

        static function __uninstall($confirm) {
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

        public function post_options($fields, $post = null) {
            $tags = self::list_tags(false);

            $selector = '<span class="tags_select">'."\n";

            foreach (array_reverse($tags) as $tag) {
                $selected = ($post and substr_count($post->unclean_tags, "{{".$tag["name"]."}}")) ?
                                ' tag_added' :
                                "" ;
                $selector.= "\t\t\t\t\t\t\t\t".'<a href="javascript:add_tag(\''.addslashes($tag["name"]).'\', \'.tag_'.addslashes($tag["url"]).'\')" class="tag_'.$tag["url"].$selected.'">'.$tag["name"].'</a>'."\n";
            }

            $selector.= "\t\t\t\t\t\t\t</span>";

            $fields[] = array("attr" => "tags",
                              "label" => __("Tags", "tags"),
                              "note" => __("(comma separated)", "tags"),
                              "type" => "text",
                              "value" => ($post ? implode(", ", self::unlinked_tags($post->unclean_tags)) : ""),
                              "extra" => $selector);

            return $fields;
        }

        public function bookmarklet_submit_values(&$values) {
            $tags = array();
            foreach ($values as &$value) {
                if (preg_match_all("/(\s|^)#([a-zA-Z0-9 ]+)(?!\\\\)#/", $value, $double)) {
                    $tags = array_merge($double[2], $tags);
                    $value = preg_replace("/(\s|^)#([a-zA-Z0-9 ]+)(?!\\\\)#/", "\\1", $value);
                }
                if (preg_match_all("/(\s|^)#([a-zA-Z0-9]+)(?!#)/", $value, $single)) {
                    $tags = array_merge($single[2], $tags);
                    $value = preg_replace("/(\s|^)#([a-zA-Z0-9]+)(?!#)/", "\\1\\2", $value);
                }
                $_POST['tags'] = implode(", ", $tags);
                $value = str_replace("\\#", "#", $value);
            }
        }

        public function add_post($post) {
            if (empty($_POST['tags'])) return;

            $tags = explode(",", $_POST['tags']); # Split at the comma
            $tags = array_map("trim", $tags); # Remove whitespace
            $tags = array_map("strip_tags", $tags); # Remove HTML
            $tags = array_unique($tags); # Remove duplicates
            $tags = array_diff($tags, array("")); # Remove empties
            $tags_cleaned = array_map("sanitize", $tags);

            $tags_string = "{{".implode("}},{{", $tags)."}}";
            $tags_cleaned_string = "{{".implode("}},{{", $tags_cleaned)."}}";

            $sql = SQL::current();
            $sql->insert("post_attributes",
                         array("name" => "unclean_tags",
                               "value" => $tags_string,
                               "post_id" => $post->id));

            $sql->insert("post_attributes",
                         array("name" => "clean_tags",
                               "value" => $tags_cleaned_string,
                               "post_id" => $post->id));
        }

        public function update_post($post) {
            if (!isset($_POST['tags'])) return;

            $sql = SQL::current();
            $sql->delete("post_attributes",
                         array("name" => array("unclean_tags", "clean_tags"),
                               "post_id" => $post->id));

            $tags = explode(",", $_POST['tags']); # Split at the comma
            $tags = array_map('trim', $tags); # Remove whitespace
            $tags = array_map('strip_tags', $tags); # Remove HTML
            $tags = array_unique($tags); # Remove duplicates
            $tags = array_diff($tags, array("")); # Remove empties
            $tags_cleaned = array_map("sanitize", $tags);

            $tags_string = (!empty($tags)) ? "{{".implode("}},{{", $tags)."}}" : "" ;
            $tags_cleaned_string = (!empty($tags_cleaned)) ? "{{".implode("}},{{", $tags_cleaned)."}}" : "" ;

            $sql->insert("post_attributes",
                         array("name" => "unclean_tags",
                               "value" => $tags_string,
                               "post_id" => $post->id));

            $sql->insert("post_attributes",
                         array("name" => "clean_tags",
                               "value" => $tags_cleaned_string,
                               "post_id" => $post->id));
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
                                 array(Post::statuses(), Post::feathers()),
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

            $cloud = array();
            foreach ($tags as $tag => $count)
                $cloud[] = array("size" => (100 + (($count - $min_qty) * $step)),
                                 "popularity" => $count,
                                 "name" => $tag,
                                 "title" => sprintf(_p("%s post tagged with &quot;%s&quot;", "%s posts tagged with &quot;%s&quot;", $count, "tags"), $count, $tag),
                                 "clean" => $tag2clean[$tag],
                                 "url" => url("tag/".$tag2clean[$tag]));

            if (!Post::any_editable() and !Post::any_deletable())
                return $admin->display("manage_tags", array("tag_cloud" => $cloud));

            fallback($_GET['query'], "");
            list($where, $params) = keywords($_GET['query'], "post_attributes.value LIKE :query OR url LIKE :query");

            $visitor = Visitor::current();
            if (!$visitor->group->can("view_draft", "edit_draft", "edit_post", "delete_draft", "delete_post"))
                $where["user_id"] = $visitor->id;

            $results = Post::find(array("placeholders" => true,
                                        "where" => $where,
                                        "params" => $params));

            $ids = array();
            foreach ($results[0] as $result)
                $ids[] = $result["id"];

            if (!empty($ids))
                new Paginator(Post::find(array("placeholders" => true,
                                               "drafts" => true,
                                               "where" => array("id" => $ids))),
                              25);
            else
                $posts = new Paginator(array());

            $admin->display("manage_tags", array("tag_cloud" => $cloud,
                                                 "posts" => $posts));
        }

        public function admin_rename_tag($admin) {
            $sql = SQL::current();

            $tags = array();
            $clean = array();
            foreach($sql->select("posts",
                                 "tags.*",
                                 array(Post::statuses(), Post::feathers()),
                                 null,
                                 array(),
                                 null,
                                 null,
                                 null,
                                 array(array("table" => "tags",
                                             "where" => array("post_id = posts.id", "clean like" => "%{{".$_GET['name']."}}%"))))->fetchAll() as $tag) {
                if ($tag["id"] == null)
                    continue;

                $tags[] = $tag["tags"];
                $clean[] = $tag["clean"];
            }

            list($tags, $clean, $tag2clean,) = self::parseTags($tags, $clean);

            foreach ($tags as $tag => $count)
                if ($tag2clean[$tag] == $_GET['name']) {
                    $tag = array("name" => $tag, "clean" => $tag2clean[$tag]);
                    continue;
                }

            $admin->display("rename_tag", array("tag" => $tag));
        }

        public function admin_edit_tags($admin) {
            if (!isset($_GET['id']))
                error(__("No ID Specified"), __("Please specify the ID of the post whose tags you would like to edit.", "tags"));

            $admin->display("edit_tags", array("post" => new Post($_GET['id'])));
        }

        public function admin_update_tags($admin) {
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
            foreach($sql->select("post_attributes",
                                 "*",
                                 array("name" => "clean_tags",
                                       "value like" => "%{{".$_POST['clean']."}}%"))->fetchAll() as $tag) {
                $names = str_replace("{{".$_POST['clean']."}}", "{{".$_POST['name']."}}", $tag["tags"]);
                $clean = str_replace("{{".$_POST['clean']."}}", "{{".sanitize($_POST['name'])."}}", $tag["clean"]);

                $sql->update("post_attributes",
                             array("name" => "unclean_tags",
                                   "post_id" => $tag["post_id"]),
                             array("value" => $names));

                $sql->update("post_attributes",
                             array("name" => "clean_tags",
                                   "post_id" => $tag["post_id"]),
                             array("value" => $clean));
            }

            Flash::notice(__("Tag renamed.", "tags"), "/admin/?action=manage_tags");
        }

        public function admin_delete_tag($admin) {
            $sql = SQL::current();

            foreach($sql->select("post_attributes",
                                 "*",
                                 array("name" => "clean_tags",
                                       "value like" => "%{{".$_GET['clean']."}}%"))->fetchAll() as $tag)  {
                $names = array();
                foreach (explode("}},{{", substr(substr($tag["tags"], 0, -2), 2)) as $name)
                    if ($name != $_GET['name'])
                        $names[] = "{{".$name."}}";

                $cleans = array();
                foreach (explode("}},{{", substr(substr($tag["clean"], 0, -2), 2)) as $clean)
                    if ($clean != $_GET['clean'])
                        $cleans[] = "{{".$clean."}}";

                if (empty($names) or empty($cleans))
                    $sql->delete("post_attributes", array("name" => $tag["name"], "post_id" => $tag["post-id"]));
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

        public function main_tag($main) {
            if (!isset($_GET['name']))
                return $main->resort(array("pages/tag", "pages/index"),
                                     array("reason" => "no_tag_specified"),
                                     __("No Tag", "tags"));

            if (!SQL::current()->count("tags", array("clean like" => "%{{".$_GET['name']."}}%")))
                return $main->resort(array("pages/tag", "pages/index"),
                                     array("reason" => "tag_not_found"),
                                     __("Invalid Tag", "tags"));

            $posts = new Paginator(Post::find(array("placeholders" => true,
                                                    "where" => array("tags.clean like" => "%{{".$_GET['name']."}}%"))),
                                   Config::current()->posts_per_page);

            if (empty($posts))
                return false;

            $tag = Tags::clean2tag($_GET['name']);

            $main->display(array("pages/tag", "pages/index"),
                           array("posts" => $posts, "tag" => $tag),
                           _f("Posts tagged with \"%s\"", array($tag), "tags"));
        }

        public function main_tags($main) {
            $sql = SQL::current();

            if ($sql->count("tags") > 0) {
                $tags = array();
                $clean = array();
                foreach($sql->select("posts",
                                     "tags.*",
                                     array(Post::statuses(), Post::feathers()),
                                     null,
                                     array(),
                                     null, null, null,
                                     array(array("table" => "tags",
                                                 "where" => "tags.post_id = posts.id")))->fetchAll() as $tag) {
                    $tags[] = $tag["tags"];
                    $clean[] = $tag["clean"];
                }

                list($tags, $clean, $tag2clean,) = self::parseTags($tags, $clean);

                $max_qty = max(array_values($tags));
                $min_qty = min(array_values($tags));

                $spread = $max_qty - $min_qty;
                if ($spread == 0)
                    $spread = 1;

                $step = 250 / $spread; # Increase for bigger difference.

                $context = array();
                foreach ($tags as $tag => $count)
                    $context[] = array("size" => (100 + (($count - $min_qty) * $step)),
                                       "popularity" => $count,
                                       "name" => $tag,
                                       "title" => sprintf(_p("%s post tagged with &quot;%s&quot;", "%s posts tagged with &quot;%s&quot;", $count, "tags"), $count, $tag),
                                       "clean" => $tag2clean[$tag],
                                       "url" => url("tag/".$tag2clean[$tag]));

                $main->display("pages/tags", array("tag_cloud" => $context), __("Tags", "tags"));
            }
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

        public function main_context($context) {
            $context["tags"] = self::list_tags();
            return $context;
        }

        static function linked_tags($tags, $cleaned_tags) {
            if (empty($tags) or empty($cleaned_tags))
                return array();

            $tags = explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $tags));
            $cleaned_tags = explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", $cleaned_tags));

            $tags = array_combine($cleaned_tags, $tags);

            $linked = array();
            foreach ($tags as $clean => $tag)
                $linked[] = '<a href="'.url("tag/".urlencode($clean)).'" rel="tag">'.$tag.'</a>';

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
                                              "url" => url("tag/".urlencode($tag2clean[$tag])));

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

            $attrs = $sql->select("post_attributes",
                                  array("name", "value"),
                                  array("name" => array("unclean_tags", "clean_tags"), Post::statuses(), Post::feathers()),
                                  null,
                                  array(),
                                  null, null, null,
                                  array(array("table" => "posts",
                                              "where" => "post_attributes.post_id = id")));

            $unclean = array();
            $clean = array();
            while ($attr = $attrs->fetchObject())
                if ($attr->name == "tags")
                    $unclean[] = $attr->value;
                else
                    $clean[] = $attr->value;

            if (!count($unclean))
                return array();

            list($unclean, $clean, $tag2clean,) = self::parseTags($unclean, $clean);

            foreach ($unclean as $name => $popularity)
                $unclean[$name] = array("name" => $name,
                                        "popularity" => $popularity,
                                        "url" => urlencode($tag2clean[$name]),
                                        "clean" => $tag2clean[$name]);

            usort($unclean, array($this, "sort_tags_".$order_by."_".$order));

            return ($limit) ? array_slice($unclean, 0, $limit) : $unclean ;
        }

        static function clean2tag($clean_tag) {
            $tags = array();
            $clean = array();
            foreach(SQL::current()->select("post_attributes",
                                           "*",
                                           array("name" => array("unclean_tags",
                                                                 "clean_tags")))->fetchAll() as $attr) {
                if ($attr["name"] == "unclean_tags")
                    $tags[] = $attr["value"];
                else
                    $clean[] = $attr["value"];
            }

            list($tags, $clean, $tag2clean, $clean2tag) = self::parseTags($tags, $clean);

            return $clean2tag[$clean_tag];
        }

        static function tag2clean($unclean_tag) {
            $tags = array();
            $clean = array();
            foreach(SQL::current()->select("post_attributes",
                                           "*",
                                           array("name" => array("unclean_tags",
                                                                 "clean_tags")))->fetchAll() as $attr) {
                if ($attr["name"] == "unclean_tags")
                    $tags[] = $attr["value"];
                else
                    $clean[] = $attr["value"];
            }

            list($tags, $clean, $tag2clean) = self::parseTags($tags, $clean);

            return $tag2clean[$unclean_tag];
        }

        public function posts_export($atom, $post) {
            $tags = SQL::current()->select("post_attributes",
                                           "value",
                                           array("name" => "unclean_tags",
                                                 "post_id" => $post->id),
                                           "id DESC")->fetchColumn();
            if (empty($tags)) return;

            $atom.= "       <chyrp:tags>".fix(implode(", ", self::unlinked_tags($tags)))."</chyrp:tags>\r";
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
            $tags = explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $tags)));
            $clean = explode(",", preg_replace("/\{\{([^\}]+)\}\}/", "\\1", implode(",", $clean)));
            $tag2clean = array_combine($tags, $clean);
            $clean2tag = array_combine($clean, $tags);
            return array(array_count_values($tags), array_count_values($clean), $tag2clean, $clean2tag);
        }
    }
