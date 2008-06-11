<?php
	class Paging extends Module {
		public function __construct() {
			$this->addAlias("markup_post_text", "makesafe", 8);
			$this->addAlias("markup_post_text", "split");
		}
		public function makesafe($text, $post) {
			return str_replace("<!--page-->", "(((page)))", $text);
		}
		public function split($text, $post) {
			global $action;
			if (!strpos($text, "(((page)))"))
				return $text;

			$text = preg_replace("/(<p>)?(\(\(\(page\)\)\))(<\/p>|<br \/>)?/", "\\2", $text);
			$split_pages = explode("(((page)))", $text);

			$post->paginated = new Paginator($split_pages, 1, "page", false);

			return $post->paginated->result[0];
		}
		static function filter_post($post) {
			$post->next_page = false;
			$post->prev_page = false;
		}
		public function prev_page_url($post) {
			global $action;
			$request = rtrim($_SERVER['REQUEST_URI'], "/");

			$config = Config::current();
			if (!$config->clean_urls)
				$mark = (strpos($request, "?")) ? "&" : "?" ;

			return ($config->clean_urls) ?
			       preg_replace("/(\/page\/([0-9]+)|$)/", "/page/".($post->page - 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]page=([0-9]+)|$)/", $mark."page=".($post->page - 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}
		public function next_page_url($post) {
			global $action;
			$request = rtrim($_SERVER['REQUEST_URI'], "/");
			$config = Config::current();
			$slash = (substr($config->post_url, -1) == "/") ? "/" : "" ;

			if (!$config->clean_urls)
				$mark = (strpos($request, "?")) ? "&" : "?" ;

			return ($config->clean_urls) ?
			       preg_replace("/(\/page\/([0-9]+)|$)/", "/page/".($post->page + 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
			       preg_replace("/([\?&]page=([0-9]+)|$)/", $mark."page=".($post->page + 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
		}
	}
