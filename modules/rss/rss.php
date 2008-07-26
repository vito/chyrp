<?php
	# This module is outdated.
	class RSS extends Modules {
		static function __install() {
			$config = Config::current();
			$config->set("rss_last_update", 0);
			$config->set("rss_update_every", 30);
			$config->set("rss_feeds", array());
		}

		public function runtime() {
			if (Route::current()->action != "index" or JAVASCRIPT or ADMIN) return;

			$config = Config::current();
			if (time() - $config->rss_last_update >= ($config->rss_update_every * 60)) {
				$rss_feeds = $config->rss_feeds;
				foreach ($config->rss_feeds as $name => $feed) {
					$get_xml_contents = get_remote(trim($feed["url"]));
					$xml_contents = preg_replace("/<(\/?)dc:date>/", "<\\1date>", $get_xml_contents);
					$xml = simplexml_load_string($xml_contents, "SimpleXMLElement", LIBXML_NOCDATA);

					if ($xml == false)
						continue;

					$this->flatten($xml);

					$items = array();

					if (isset($xml->item))
						foreach ($xml->item as $item)
							array_unshift($items, $item);
					else
						foreach ($xml->channel->item as $item)
							array_unshift($items, $item);

					foreach ($items as $item) {
						$date = (isset($item->pubDate)) ? $item->pubDate : ((isset($item->date)) ? $item->date : 0) ;

						if (strtotime($date) > $feed["last_updated"]) {
							$data = array();
							foreach ($feed["data"] as $attr => $field)
								if (!empty($field)) {
									if (preg_match("/^([a-z0-9:]+)$/", $field))
										$value = html_entity_decode($item->$field, ENT_QUOTES, "utf-8");
									elseif (preg_match("/feed\[([^\]]+)\]\.attr\[([^\]]+)\]/", $field, $matches))
										$value = html_entity_decode($item->$matches[1]->attributes()->$matches[2], ENT_QUOTES, "utf-8");
									elseif (preg_match("/feed\[([^\]]+)\]/", $field, $matches))
										$value = html_entity_decode($item->$matches[1], ENT_QUOTES, "utf-8");

									if (preg_match("/call:([^\(]+)\(/", $field, $matches))
										$value = call_user_func($matches[1], $value);

									$data[$attr] = $value;
								} else
									$data[$attr] = "";

							$_POST['feather'] = $feed["feather"];
							Post::add($data);

							$rss_feeds[$name]["last_updated"] = strtotime($date);
						}
					}
				}
				$config->set("rss_feeds", $rss_feeds);
				$config->set("rss_last_update", time());
			}
		}

		public function settings_nav($navs) {
			if (Visitor::current()->group()->can("change_settings"))
				$navs["aggregation_settings"] = array("title" => __("Aggregation", "rss"));

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
	}
