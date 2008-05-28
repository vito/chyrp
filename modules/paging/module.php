<?php
	class Paging extends Module {
		public function __construct() {
			$this->addAlias("markup_post_text", "makesafe", 8);
			$this->addAlias("markup_post_text", "split");
		}
		static function makesafe($text) {
			global $post;
			if (!isset($post->id)) return;
			return str_replace("<!--page-->", "(((page)))", $text);
		}
		static function split($text) {
			global $post, $action;
			if (!isset($post->id) or !strpos($text, "(((page)))")) return $text;

			$text = preg_replace("/(<p>)?(\(\(\(page\)\)\))(<\/p>|<br \/>)?/", "\\2", $text);
			$split_pages = explode("(((page)))", $text);

			if ($action == "view")
				$post->page = (isset($_GET['page'])) ? $_GET['page'] : 1 ;

			$offset = ($action == "view") ? $post->page - 1 : 0 ;
			$post->total_pages = ($action == "view") ? count($split_pages) : 0 ;
			$post->next_page = ($action == "view") ? ($post->page != $post->total_pages and $post->total_pages != 1 and $post->total_pages != 0) : false ;
			$post->prev_page = ($action == "view") ? ($post->page > 1) : false ;

			if (!isset($split_pages[$offset]))
				return $split_pages[count($split_pages) - 1];

			if ($action == "view") {
				$post->next_page_url = next_page_url();
				$post->prev_page_url = prev_page_url();
			}

			return $split_pages[$offset];
		}
		static function filter_post($post) {
			$post->next_page = false;
			$post->prev_page = false;

		}
	}

	function prev_page_url() {
		global $post, $action;
		$request = rtrim($_SERVER['REQUEST_URI'], "/");

		$config = Config::current();
		if (!$config->clean_urls)
			$mark = (strpos($request, "?")) ? "&" : "?" ;

		return ($config->clean_urls) ?
		       preg_replace("/(\/page\/([0-9]+)|$)/", "/page/".($post->page - 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
		       preg_replace("/([\?&]page=([0-9]+)|$)/", $mark."page=".($post->page - 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
	}
	function next_page_url() {
		global $post, $action;
		$request = rtrim($_SERVER['REQUEST_URI'], "/");
		$config = Config::current();
		$slash = (substr($config->post_url, -1) == "/") ? "/" : "" ;

		if (!$config->clean_urls)
			$mark = (strpos($request, "?")) ? "&" : "?" ;

		return ($config->clean_urls) ?
		       preg_replace("/(\/page\/([0-9]+)|$)/", "/page/".($post->page + 1), "http://".$_SERVER['HTTP_HOST'].$request, 1) :
		       preg_replace("/([\?&]page=([0-9]+)|$)/", $mark."page=".($post->page + 1), "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1) ;
	}
