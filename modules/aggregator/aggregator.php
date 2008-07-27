<?php
	class Aggregator extends Modules {
		static function __install() {
			$config = Config::current();
			$config->set("last_aggregation", 0);
			$config->set("aggregate_every", 30);
			$config->set("disable_aggregation", false);
			$config->set("aggregation_author", Visitor::current()->id);
			$config->set("aggregation_feeds", array());
		}

		static function __uninstall() {
			$config = Config::current();
			$config->remove("last_aggregation");
			$config->remove("aggregate_every");
			$config->remove("disable_aggregation");
			$config->remove("aggregation_author");
			$config->remove("aggregation_feeds");
		}

		public function runtime() {
			if (Route::current()->action != "index" or JAVASCRIPT or ADMIN) return;

			$config = Config::current();
			if ($config->disable_aggregation or time() - $config->last_aggregation < ($config->aggregate_every * 60))
				return;

			$aggregation_feeds = $config->aggregation_feeds;
			foreach ($config->aggregation_feeds as $name => $feed) {
				$xml_contents = preg_replace(array("/<(\/?)dc:date>/", "/xmlns=/"),
				                             array("<\\1date>", "a="),
				                             get_remote(trim($feed["url"])));
				$xml = simplexml_load_string($xml_contents, "SimpleXMLElement", LIBXML_NOCDATA);

				if ($xml == false)
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
					$date = fallback($item->pubDate, fallback($item->date, fallback($item->published, 0, true), true), true);

					if (strtotime($date) > $feed["last_updated"]) {
						$data = array();
						foreach ($feed["data"] as $attr => $field)
							$data[$attr] = (!empty($field)) ? $this->parse_field($field, $item) : "" ;

						$_POST['feather'] = $feed["feather"];
						$_POST['user_id'] = $config->aggregation_author;
						Post::add($data);

						$aggregation_feeds[$name]["last_updated"] = strtotime($date);
					}
				}
			}
			$config->set("aggregation_feeds", $aggregation_feeds);
			$config->set("last_aggregation", time());
		}

		public function settings_nav($navs) {
			if (Visitor::current()->group()->can("change_settings"))
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

		static function image_from_html($html) {
			preg_match("/img src=('|\")([^ \\1]+)\\1/", $html, $image);
			return $image[2];
		}

		static function upload_image_from_html($html) {
			return upload_from_url(self::image_from_html($html));
		}

		public function parse_field($value, $item) {
			if (preg_match("/^([a-z0-9:\/]+)$/", $value)) {
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
			if (!Visitor::current()->group()->can("change_settings"))
				show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

			$admin->context["users"] = User::find();

			if (empty($_POST))
				return;

			if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
				show_403(__("Access Denied"), __("Invalid security key."));

			$config = Config::current();
			$set = array($config->set("aggregate_every", $_POST['aggregate_every']),
			             $config->set("disable_aggregation", !empty($_POST['disable_aggregation'])),
			             $config->set("aggregation_author", $_POST['aggregation_author']));

			if (!in_array(false, $set))
				Flash::notice(__("Settings updated."), "/admin/?action=aggregation_settings");
		}
	}
