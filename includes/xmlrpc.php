<?php
	require_once "common.php";
	require_once INCLUDES_DIR."/lib/ixr.php";
	
	/**
	 * Class: XMLRPC
	 */
	class XMLRPC extends IXR_Server {
		/**
		 * Function: XMLRPC
		 * Registers the various XMLRPC methods.
		 */
		function XMLRPC() {
			$this->IXR_Server(array(
				'pingback.ping' => 'this:pingback_ping'
			));
		}
		
		/**
		 * Function: pingback_ping
		 * Receive and register pingbacks. Calls the "pingback" trigger.
		 */
		function pingback_ping($args) {
			$config = Config::current();
			$linked_from = str_replace('&amp;', '&', $args[0]);
			$linked_to	 = str_replace('&amp;', '&', $args[1]);
			$post_url	 = str_replace($config->url, "", $linked_to);
			$url         = extract(parse_url($linked_to));
			
			$cleaned_url = str_replace(array("http://www.", "http://"), "", $config->url);
			
			if ($linked_to == $linked_from)
				return new IXR_ERROR(0, __("The from and to URLs cannot be the same."));
			
			if(!strpos($linked_to, $cleaned_url))
				return new IXR_Error(0, __("There doesn't seem to be a valid link in your request."));
			
			if (!$config->clean_urls) {
				# Figure out what they're grabbing by
				$count = 0;
				$get = array();
				$queries = explode("&", $query);
				foreach ($queries as $query) {
					list($key, $value) = explode("=", $query);
					$get[$count]["key"] = $key;
					$get[$count]["value"] = $value;
					$count++;
				}
				$where = "`".$get[1]["key"]."` = :val";
				$params = array(":val" => $get[1]["value"]);
			} else {
				$route = Route::current();
				$where = substr($route->parse_url_to_sql($linked_to, $post_url), 0, -4);
				$params = array();
			}
			
			$sql = SQL::current();
			$check = $sql->query("select `id` from `".$sql->prefix."posts`
			                      where ".$where."
			                      limit 1",
			                     $params);
			$id = $check->fetchColumn();
			
			
			if (!Post::exists($id))
				return new IXR_Error(33, __("I can't find a post from that URL."));
				
			# Wait for the "from" server to publish
			sleep(1);
			
			extract(parse_url($linked_from), EXTR_SKIP);
			if (!isset($host)) return false;
			
			if (!isset($scheme) or !in_array($scheme, array("http")))
				$linked_from = "http://".$linked_from;

			# Connect
			$content = get_remote($linked_from);
			
			$content = str_replace("<!DOC", "<DOC", $content);
			preg_match("/<title>([^<]+)<\/title>/i", $content, $title);
			$title = $title[1];
			
			if (empty($title))
				return new IXR_Error(32, __("There isn't a title on that page."));
			
			$content = strip_tags($content, "<a>");
			
			preg_match("|<a[^>]+?".preg_quote($linked_to)."[^>]*>([^>]+?)</a>|", $content, $context);
			$context[1] = truncate($context[1], 100, false);
			$excerpt = str_replace($context[0], $context[1], $content);
			$excerpt = strip_tags($excerpt);
			$excerpt = preg_replace("|.*?\s(.{0,100}".preg_quote($context[1]).".{0,100})\s.*|s", '$1', $excerpt);
			$excerpt = preg_replace("/[\s\n\r\t]+/", " ", $excerpt);
			$excerpt = "[...] ".trim($excerpt)." [...]";
			
			$linked_to = str_replace('&', '&amp;', $linked_to);
			$trigger = Trigger::current();
			$trigger->call("pingback", array($id, $linked_to, $linked_from, $title, $excerpt));
			
			return sprintf(__("Pingback from %s to %s registered!"), $linked_from, $linked_to);
		}
	}
	$server = new XMLRPC();
