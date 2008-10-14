<?php
    /**
     * Class: Page
     * The Page model.
     *
     * See Also:
     *     <Model>
     */
    class Page extends Model {
        public $belongs_to = array("user", "parent" => "page");

        public $has_many = array("children" => array("page", "parent"));

        /**
         * Function: __construct
         * See Also:
         *     <Model::grab>
         */
        public function __construct($page_id, $options = array()) {
            if (!isset($page_id) and empty($options)) return;
            parent::grab($this, $page_id, $options);

            if ($this->no_results)
                return false;

            $this->slug = $this->url;

            $this->filtered = !isset($options["filter"]) or $options["filter"];

            if ($this->filtered) {
                $trigger = Trigger::current();
                $trigger->filter($this->body, "markup_page_text");
                $trigger->filter($this->title, "markup_page_title");
            }
        }

        /**
         * Function: find
         * See Also:
         *     <Model::search>
         */
        static function find($options = array(), $options_for_object = array()) {
            return parent::search(get_class(), $options, $options_for_object);
        }

        /**
         * Function: add
         * Adds a page to the database.
         *
         * Calls the add_page trigger with the inserted page.
         *
         * Parameters:
         *     $title - The Title for the new page.
         *     $body - The Body for the new page.
         *     $parent_id - The ID of the new page's parent page (0 for none).
         *     $show_in_list - Whether or not to show it in the pages list.
         *     $clean - The sanitized URL (or empty to default to "(feather).(new page's id)").
         *     $url - The unique URL (or empty to default to "(feather).(new page's id)").
         *     $created_at - The new page's "created" timestamp.
         *     $updated_at - The new page's "last updated" timestamp.
         *     $user_id - The ID of the user that created the page. Defaults to the visitor's ID.
         *
         * Returns:
         *     $page - The newly created page.
         *
         * See Also:
         *     <update>
         */
        static function add($title, $body, $parent_id, $show_in_list, $list_order = 0, $clean, $url, $created_at = null, $updated_at = null, $user_id = null) {
            $sql = SQL::current();
            $visitor = Visitor::current();
            $sql->insert("pages",
                         array("title" => $title,
                               "body" => $body,
                               "user_id" => fallback($user_id, $visitor->id),
                               "parent_id" => $parent_id,
                               "show_in_list" => (int) $show_in_list,
                               "list_order" => $list_order,
                               "clean" => $clean,
                               "url" => $url,
                               "created_at" => fallback($created_at, datetime()),
                               "updated_at" => fallback($updated_at, "0000-00-00 00:00:00")));

            $page = new self($sql->latest());

            Trigger::current()->call("add_page", $page);

            return $page;
        }

        /**
         * Function: update
         * Updates the page.
         *
         * Parameters:
         *     $title - The new Title.
         *     $body - The new Body.
         *     $parent_id - The new parent ID.
         *     $show_in_list - Whether or not to show it in the pages list.
         *     $url - The new page URL.
         */
        public function update($title, $body, $parent_id, $show_in_list, $list_order, $url, $update_timestamp = true) {
            if ($this->no_results)
                return false;

            $sql = SQL::current();
            $sql->update("pages",
                         array("id" => $this->id),
                         array("title" => $title,
                               "body" => $body,
                               "parent_id" => $parent_id,
                               "show_in_list" => $show_in_list,
                               "list_order" => $list_order,
                               "updated_at" => ($update_timestamp) ? datetime() : $this->updated_at,
                               "url" => $url));

            $trigger = Trigger::current();
            $trigger->call("update_page", $this, $title, $body, $parent_id, $show_in_list, $list_order, $url, $update_timestamp);
        }

        /**
         * Function: delete
         * Deletes the given page. Calls the "delete_page" trigger and passes the <Page> as an argument.
         *
         * Parameters:
         *     $id - The page to delete.
         *     $recursive - Should the sub-pages be deleted? (default: false)
         */
        static function delete($id, $recursive = false) {
            if ($recursive) {
                $page = new self($id);
                foreach ($page->children as $child)
                    self::delete($child->id);
            }

            parent::destroy(get_class(), $id);
        }

        /**
         * Function: exists
         * Checks if a page exists.
         *
         * Parameters:
         *     $page_id - The page ID to check
         *
         * Returns:
         *     true - if a page with that ID is in the database.
         */
        static function exists($page_id) {
            return SQL::current()->count("pages", array("id" => $page_id)) == 1;
        }

        /**
         * Function: check_url
         * Checks if a given clean URL is already being used as another page's URL.
         *
         * Parameters:
         *     $clean - The clean URL to check.
         *
         * Returns:
         *     $url - The unique version of the passed clean URL. If it's not used, it's the same as $clean. If it is, a number is appended.
         */
        static function check_url($clean) {
            $count = SQL::current()->count("pages", array("clean" => $clean));
            return (!$count or empty($clean)) ? $clean : $clean."-".($count + 1) ;
        }

        /**
         * Function: url
         * Returns a page's URL.
         */
        public function url() {
            if ($this->no_results)
                return false;

            $config = Config::current();
            if (!$config->clean_urls)
                return $config->url."/?action=page&amp;url=".urlencode($this->url);

            $url = array("", urlencode($this->url));

            $page = $this;
            while (isset($page->parent_id) and $page->parent_id) {
                $url[] = urlencode($page->parent->url);
                $page = $page->parent;
            }

            return url("page/".implode("/", array_reverse($url)));
        }

        /**
         * Function: parent
         * Returns a page's parent. Example: $page->parent()->parent()->title
         * 
         * !! DEPRECATED AFTER 2.0 !!
         */
        public function parent() {
            if ($this->no_results or !$this->parent_id)
                return false;

            return new self($this->parent_id);
        }

        /**
         * Function: children
         * Returns a page's children.
         * 
         * !! DEPRECATED AFTER 2.0 !!
         */
        public function children() {
            if ($this->no_results)
                return false;

            return self::find(array("where" => array("parent_id" => $this->id)));
        }

        /**
         * Function: user
         * Returns a page's creator. Example: $page->user->full_name
         * 
         * !! DEPRECATED AFTER 2.0 !!
         */
        public function user() {
            if ($this->no_results)
                return false;

            return new User($this->user_id);
        }

        /**
         * Function: edit_link
         * Outputs an edit link for the page, if the <User.can> edit_page.
         *
         * Parameters:
         *     $text - The text to show for the link.
         *     $before - If the link can be shown, show this before it.
         *     $after - If the link can be shown, show this after it.
         */
        public function edit_link($text = null, $before = null, $after = null){
            if ($this->no_results or !Visitor::current()->group->can("edit_page"))
                return false;

            fallback($text, __("Edit"));

            echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=edit_page&amp;id='.$this->id.'" title="Edit" class="page_edit_link edit_link" id="page_edit_'.$this->id.'">'.$text.'</a>'.$after;
        }

        /**
         * Function: delete_link
         * Outputs a delete link for the page, if the <User.can> delete_page.
         *
         * Parameters:
         *     $text - The text to show for the link.
         *     $before - If the link can be shown, show this before it.
         *     $after - If the link can be shown, show this after it.
         */
        public function delete_link($text = null, $before = null, $after = null){
            if ($this->no_results or !Visitor::current()->group->can("delete_page"))
                return false;

            fallback($text, __("Delete"));

            echo $before.'<a href="'.Config::current()->chyrp_url.'/admin/?action=delete_page&amp;id='.$this->id.'" title="Delete" class="page_delete_link delete_link" id="page_delete_'.$this->id.'">'.$text.'</a>'.$after;
        }
    }
