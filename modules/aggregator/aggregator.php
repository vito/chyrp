<?php
    class Aggregator extends Modules {
        static function __install() {
            $config = Config::current();
            $config->set("last_aggregation", 0);
            $config->set("aggregate_every", 30);
            $config->set("disable_aggregation", false);
            $config->set("aggregates", array());

            Group::add_permission("add_aggregate", "Add Aggregate");
            Group::add_permission("edit_aggregate", "Edit Aggregate");
            Group::add_permission("delete_aggregate", "Delete Aggregate");
        }

        static function __uninstall() {
            $config = Config::current();
            $config->remove("last_aggregation");
            $config->remove("aggregate_every");
            $config->remove("disable_aggregation");
            $config->remove("aggregates");

            Group::remove_permission("add_aggregate");
            Group::remove_permission("edit_aggregate");
            Group::remove_permission("delete_aggregate");
        }
        
        function get_xml_remote($url) {
            extract(parse_url($url), EXTR_SKIP);

            if (ini_get("allow_url_fopen")) {
                $context = stream_context_create(array('http'=>array('user_agent'=>"Chyrp/".CHYRP_VERSION)));
                $content = @file_get_contents($url,false,$context);
                if (!((strpos($http_response_header[0], " 200 ")) || (strpos($http_response_header[0], " 301 ")) || (strpos($http_response_header[0], " 302 ")) || (strpos($http_response_header[0], " 303 ")) || (strpos($http_response_header[0], " 307 "))))
                    $content = "Server returned a message: $http_response_header[0]";
            } elseif (function_exists("curl_init")) {
                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $url);
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 1);
                curl_setopt($handle, CURLOPT_FOLLOWLOCATION, True);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($handle, CURLOPT_TIMEOUT, 60);
                curl_setopt($handle, CURLOPT_USERAGENT, 'Chyrp/'.CHYRP_VERSION);
                $content = curl_exec($handle);
                $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                curl_close($handle);
                if (!(($status == 200) || ($status == 301) || ($status == 302) || ($status == 303) || ($status == 307)))
                    $content = "Server returned a message: $status";
            } else {
                $path = (!isset($path)) ? '/' : $path ;
                if (isset($query)) $path.= '?'.$query;
                $port = (isset($port)) ? $port : 80 ;

                $connect = @fsockopen($host, $port, $errno, $errstr, 2);
                if (!$connect) return false;

                # Send the GET headers
                fwrite($connect, "GET ".$path." HTTP/1.1\r\n");
                fwrite($connect, "Host: ".$host."\r\n");
                fwrite($connect, "User-Agent: Chyrp/".CHYRP_VERSION."\r\n\r\n");

                $content = "";
                while (!feof($connect)) {
                    $line = fgets($connect, 128);
                    if (preg_match("/\r\n/", $line)) continue;

                    $content.= $line;
                }

                fclose($connect);
            }

            return $content;
        }

        public function main_index($main) {
            $config = Config::current();
            if ($config->disable_aggregation or time() - $config->last_aggregation < ($config->aggregate_every * 60))
                exit;

            $aggregates = (array) $config->aggregates;

            if (empty($aggregates))
                exit;

            foreach ($aggregates as $name => $feed) {
                $xml_contents = preg_replace(array("/<(\/?)dc:date>/", "/xmlns=/"),
                                             array("<\\1date>", "a="),
                                             $this->get_xml_remote($feed["url"]));
                $xml = simplexml_load_string($xml_contents, "SimpleXMLElement", LIBXML_NOCDATA);

                if ($xml === false)
                    continue;

                # Flatten namespaces recursively
                $this->flatten($xml);

                $items = array();

                if (isset($xml->entry))
                    foreach ($xml->entry as $entry)
                        array_unshift($items, $entry);
                elseif (isset($xml->item))
                    foreach ($xml->item as $item)
                        array_unshift($items, $item);
                else
                    foreach ($xml->channel->item as $item)
                        array_unshift($items, $item);

                foreach ($items as $item) {
                    $date = oneof(@$item->pubDate, @$item->date, @$item->updated, 0);
                    $updated = strtotime($date);

                    if ($updated > $feed["last_updated"]) {
                        # Get creation date ('created' in Atom)
                        $created = @$item->created ? strtotime($item->created) : 0;
                        if ($created <= 0)
                            $created = $updated;

                        # Construct the post data from the user-defined XPath mapping:
                        $data = array("aggregate" => $name);
                        foreach ($feed["data"] as $attr => $field) {
                            $field = (!empty($field) ? $this->parse_field($field, $item) : "");
                            $data[$attr] = (is_string($field) ? $field : YAML::dump($field));
                        }

                        $clean = sanitize(oneof(@$data["title"], @$data["name"], ""));

                        Post::add($data, $clean, null, $feed["feather"], $feed["author"],
                                  false,
                                  $feed['status'],
                                  datetime($created),
                                  datetime($updated));

                        $aggregates[$name]["last_updated"] = $updated;
                    }
                }
            }

            $config->set("aggregates", $aggregates);
            $config->set("last_aggregation", time());
            return true;
        }

        public function admin_manage_aggregates($admin) {
            $aggregates = array();

            foreach ((array) Config::current()->aggregates as $name => $aggregate)
                $aggregates[] = array_merge(array("name" => $name), array("user" => new User($aggregate["author"])), $aggregate);

            $admin->display("manage_aggregates", array("aggregates" => new Paginator($aggregates, 25),
                                                       "groups" => Group::find(array("order" => "id ASC"))));
        }

        public function manage_nav($navs) {
            if (!Visitor::current()->group->can("edit_aggregate", "delete_aggregate"))
                return $navs;

            $navs["manage_aggregates"] = array("title" => __("Aggregates", "aggregator"),
                                               "selected" => array("edit_aggregate", "delete_aggregate", "new_aggregate"));

            return $navs;
        }

        public function manage_nav_pages($pages) {
            array_push($pages, "manage_aggregates", "edit_aggregate", "delete_aggregate", "new_aggregate");
            return $pages;
        }

        public function manage_nav_show($possibilities) {
            $possibilities[] = Visitor::current()->group->can("edit_aggregate", "delete_aggregate");
            return $possibilities;
        }

        public function determine_action($action) {
            if ($action != "manage") return;

            if (Visitor::current()->group->can("edit_aggregate", "delete_aggregate"))
                return "manage_aggregates";
        }

        public function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["aggregation_settings"] = array("title" => __("Aggregation", "aggregator"));

            return $navs;
        }

        private function flatten(&$start) {
            foreach ($start as $key => $val) {
                if (count($val) and !is_string($val)) {
                    foreach ($val->getNamespaces(true) as $namespace => $url) {
                        if (empty($namespace))
                            continue;

                        foreach ($val->children($url) as $attr => $child) {
                            $name = $namespace.":".$attr;
                            $val->$name = $child;
                            foreach ($child->attributes() as $attr => $value)
                                $val->$name->addAttribute($attr, $value);
                        }
                    }

                    $this->flatten($val);
                }
            }
        }

        static function image_from_content($html) {
            preg_match("/img src=('|\")([^ \\1]+)\\1/", $html, $image);
            return $image[2];
        }

        static function upload_image_from_content($html) {
            return upload_from_url(self::image_from_content($html));
        }

        public function parse_field($value, $item, $basic = true) {
            if (is_array($value)) {
                $parsed = array();
                foreach ($value as $key => $val)
                    $parsed[$this->parse_field($key, $item, false)] = $this->parse_field($val, $item, false);
                
                return $parsed;
            } elseif (!is_string($value))
                return $value;
            
            if ($basic and preg_match("/^([a-z0-9:\/]+)$/", $value)) {
                $xpath = $item->xpath($value);
                return html_entity_decode($xpath[0], ENT_QUOTES, "utf-8");
            }

            if (preg_match("/feed\[(.+)\]\.attr\[([^\]]+)\]/", $value, $matches)) {
                $xpath = $item->xpath($matches[1]);
                $value = str_replace($matches[0],
                                     html_entity_decode($xpath[0]->attributes()->$matches[2],
                                                        ENT_QUOTES,
                                                        "utf-8"),
                                     $value);
            }

            if (preg_match("/feed\[(.+)\]/", $value, $matches)) {
                $xpath = $item->xpath($matches[1]);
                $value = str_replace($matches[0],
                                     html_entity_decode($xpath[0], ENT_QUOTES, "utf-8"),
                                     $value);
            }

            if (preg_match_all("/call:([^\(]+)\((.+)\)/", $value, $calls))
                foreach ($calls[0] as $index => $full) {
                    $function = $calls[1][$index];
                    $arguments = explode(" || ", $calls[2][$index]);

                    $value = str_replace($full,
                                         call_user_func_array($function, $arguments),
                                         $value);
                }

            return $value;
        }

        public function admin_aggregation_settings($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("aggregation_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $config = Config::current();
            $set = array($config->set("aggregate_every", $_POST['aggregate_every']),
                         $config->set("disable_aggregation", !empty($_POST['disable_aggregation'])));

            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=aggregation_settings");
        }

        public function admin_new_aggregate($admin) {
            $admin->context["users"] = User::find();

            if (!Visitor::current()->group->can("add_aggregate"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to add aggregates.", "aggregator"));

            if (empty($_POST))
                return $admin->display("new_aggregate");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            if (empty($_POST['data']))
                return Flash::warning(__("Please enter the attributes for the Feather."));

            if (empty($_POST['name']))
                return Flash::warning(__("Please enter a name for the aggregate."));

            if (empty($_POST['url']))
                return Flash::warning(__("What are you, crazy?! I can't create an aggregate without a source!"));

            $config = Config::current();

            $aggregate = array("url" => $_POST['url'],
                               "last_updated" => 0,
                               "feather" => $_POST['feather'],
                               "author" => $_POST['author'],
                               "status" => $_POST['status'],
                               "data" => YAML::load($_POST['data']));

            $config->aggregates[$_POST['name']] = $aggregate;
            $config->set("aggregates", $config->aggregates);
            $config->set("last_aggregation", 0); # to force a refresh

            Flash::notice(__("Aggregate created.", "aggregator"), "/admin/?action=manage_aggregates");
        }

        public function admin_edit_aggregate($admin) {
            if (empty($_GET['id']))
                error(__("No ID Specified"), __("An ID is required to delete an aggregate.", "aggregator"));

            if (!Visitor::current()->group->can("edit_aggregate"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this aggregate.", "aggregator"));

            $admin->context["users"] = User::find();

            $config = Config::current();

            $aggregate = $config->aggregates[$_GET['id']];

            if (empty($_POST))
                return $admin->display("edit_aggregate",
                                       array("users" => User::find(),
                                             "groups" => Group::find(array("order" => "id ASC")),
                                             "aggregate" => array("name" => $_GET['id'],
                                                                  "url" => $aggregate["url"],
                                                                  "feather" => $aggregate["feather"],
                                                                  "author" => $aggregate["author"],
                                                                  "status" => $aggregate['status'],
                                                                  "data" => preg_replace("/---\n/",
                                                                                         "",
                                                                                         YAML::dump($aggregate["data"])))));

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $aggregate = array("url" => $_POST['url'],
                               "last_updated" => $aggregate['last_updated'],
                               "feather" => $_POST['feather'],
                               "author" => $_POST['author'],
                               "status" => $_POST['status'],
                               "data" => YAML::load($_POST['data']));

            unset($config->aggregates[$_GET['id']]);
            $config->aggregates[$_POST['name']] = $aggregate;

            $config->set("aggregates", $config->aggregates);
            $config->set("last_aggregation", 0); # to force a refresh

            Flash::notice(__("Aggregate updated.", "aggregator"), "/admin/?action=manage_aggregates");
        }

        public function admin_delete_aggregate($admin) {
            if (empty($_GET['id']))
                error(__("No ID Specified"), __("An ID is required to delete an aggregate.", "aggregator"));

            if (!Visitor::current()->group->can("delete_aggregate"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this aggregate.", "aggregator"));

            $config = Config::current();

            $aggregate = $config->aggregates[$_GET['id']];

            $admin->context["aggregate"] = array("name" => $_GET['id'],
                                                 "url" => $aggregate["url"]);
            $admin->display("delete_aggregate", array("aggregate" => array("name" => $_GET['id'],
                                                                           "url" => $aggregate["url"])));
        }

        public function admin_destroy_aggregate($admin) {
            $config = Config::current();

            if (empty($_POST['id']))
                error(__("No ID Specified"), __("An ID is required to delete an aggregate.", "aggregator"));

            if ($_POST['destroy'] == "bollocks")
                redirect("/admin/?action=manage_aggregates");

            if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            if (!Visitor::current()->group->can("delete_aggregate"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to delete this aggregate.", "aggregator"));

            $name = $_POST['id'];
            if ($_POST["delete_posts"]) {
                $this->delete_posts($name);
                $notice = __("Aggregate and its posts deleted.", "aggregator");
            } else
                $notice = __("Aggregate deleted.", "aggregator");

            unset($config->aggregates[$name]);
            $config->set("aggregates", $config->aggregates);
            Flash::notice($notice, "/admin/?action=manage_aggregates");
        }
        
        function delete_posts($aggregate_name) {
            $sql = SQL::current();
            $attrs = $sql->select("post_attributes",
                                  "post_id",
                                  array("name" => "aggregate", "value" => $aggregate_name))->fetchAll();

            foreach ($attrs as $attr)
                Post::delete($attr["post_id"]);
        }

        public function help_aggregation_syntax() {
            $title = __("Post Values", "aggregator");

            $body = "<p>".__("Use <a href=\"http://yaml.org/\">YAML</a> to specify what post attribute holds what value of the feed entry.", "aggregator")."</p>";

            $body.= "<h2>".__("XPath", "aggregator")."</h2>";
            $body.= "<cite><strong>".__("Usage")."</strong>: <code>feed[xp/ath]</code></cite>\n";
            $body.= "<p>".__("You can use XPath to navigate the feed and find the correct attribute.", "aggregator")."</p>";

            $body.= "<h2>".__("Attributes", "aggregator")."</h2>";
            $body.= "<cite><strong>".__("Usage")."</strong>: <code>feed[xp/ath].attr[foo]</code></cite>\n";
            $body.= "<p>".__("To get the attribute of an element, use XPath to find it and the <code>.attr[]</code> syntax to grab an attribute.", "aggregator")."</p>";

            $body.= "<h2>".__("Functions", "aggregator")."</h2>";
            $body.= "<cite><strong>".__("Usage")."</strong>: <code>call:foo_function(feed[foo] || feed[arg2])</code></cite>\n";
            $body.= "<p>".__("To call a function and use its return value for the post's value, use <code>call:</code>. Separate arguments with <code> || </code>.", "aggregator")."</p>";
            $body.= "<p>".__("The Aggregator module provides a couple helper functions:", "aggregator")."</p>";
            $body.= "<cite><strong>".__("To upload an image from the content", "aggregator")."</strong>: <code>call:Aggregator::upload_image_from_content(feed[content])</code></cite>";
            $body.= "<cite><strong>".__("To get the URL of an image in the content", "aggregator")."</strong>: <code>call:Aggregator::image_from_content(feed[content])</code></cite>";

            $body.= "<h2>".__("Example", "aggregator")."</h2>";
            $body.= "<p>".__("From the Photo feather:", "aggregator")."</pre>";
            $body.= "<pre><code>filename: call:upload_from_url(feed[link].attr[href])\ncaption: feed[description] # or just \"description\"</code></pre>";

            return array($title, $body);
        }
    }
