<?php
	# This module is outdated.
	class RSS {
		static function __install() {
			$config = Config::current();
			$config->set("rss_last_update", 0);
			$config->set("rss_feeds", array());
		}
		static function runtime() {
			global $action;
			if ($action != "index" or JAVASCRIPT or ADMIN) return;
			
			$config = Config::current();
			if (@time() - $config->rss_last_update >= 5) {
				$rss_feeds = $config->rss_feeds;
				foreach ($config->rss_feeds as $name => $feed) {
					$get_xml_contents = get_remote($feed["url"]);
					$xml_contents = preg_replace("/<(\/?)dc:date>/", "<\\1date>", $get_xml_contents);
					$xml = simplexml_load_string($xml_contents);
					
					$to_array = (array) $xml;
					
					if (isset($to_array["item"]))
						$target = (array) $to_array["item"];
					else {
						$channel_array = (array) $to_array["channel"];
						$target = (array) $channel_array["item"];
					}
					
					foreach (array_reverse($target) as $item) {
						$date = (isset($item->pubDate)) ? $item->pubDate : ((isset($item->date)) ? $item->date : 0) ;
						
						if (@strtotime($date) > $feed["last_updated"]) {
							$data = array();
							foreach ($feed["data"] as $attr => $field)
								if (!empty($field))
									$data[$field] = $item->$attr;
							
							$yaml = Spyc::YAMLDump($data);
							
							$_POST['feather'] = $feed["feather"];
							Post::add($yaml);
							
							$rss_feeds[$name]["last_updated"] = @strtotime($date);
						}
					}
				}
				$config->set("rss_feeds", $rss_feeds);
				$config->set("rss_last_update", @time());
			}
		}
		static function admin_settings_nav() {
?>
					<li<?php selected("settings", "aggregation"); ?>><a href="<?php url("settings", "aggregation"); ?>"><?php echo __("Aggregation", "rss"); ?></a></li>
<?php
		}
	}
