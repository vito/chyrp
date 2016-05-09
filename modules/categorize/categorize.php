<?php
    require_once("model.category.php");

    class Categorize extends Modules {
        public function __init() {
            # Overlap all the get category items
            $this->addAlias("mt_getCategoryList", "xmlrpc_getCategoryList");
            $this->addAlias("metaWeblog_getCategories", "xmlrpc_getCategoryList");
        }

        static function __install() {
            Category::installCategorize();
            Group::add_permission("manage_categorize", "Manage Categories");
            Route::current()->add("category/(name)/", "category");
        }

        static function __uninstall($confirm) {
            if ($confirm)
                Category::uninstallCategorize();

            Group::remove_permission('manage_categorize');
            Route::current()->remove("category/(name)/");
        }

        public function feed_item($post) {
            if (!empty($post->category_id) OR $post->category != 0)
               printf("\t<category term=\"%s\" />\n", Category::getCategory($post->category_id)->name);
        }

        /* Begin XML-RPC Support */
        public function metaWeblog_getPost($struct, $post)
        {
            if (empty($post->category_id)) {
                $struct['categories'][] = "";
            } else {
                $category = Category::getCategory($post->category_id);
                $struct['categories'][] = array('categoryId' => $category->id,
                    'categoryName' => $category->name);
            }

            return $struct;
        }

        public function metaWeblog_editPost($args, $post)
        {
            if (empty($args['categories'][0])) return;
            $category = Category::getCategoryIDbyName($args['categories'][0]);

            SQL::current()->replace("post_attributes",
                              array("post_id", "name"),
                              array("name" => "category_id",
                                    "value" => $category->id,
                                    "post_id" => $post->id));
        }

        public function metaWeblog_newPost($args, $post)
        {
            if (empty($args['categories'][0])) return;
            $category = Category::getCategoryIDbyName($args['categories'][0]);

            SQL::current()->insert("post_attributes",
                             array("name" => "category_id",
                                   "value" => $category->id,
                                   "post_id" => $post->id));
        }

        public function mt_getPostCategories($struct, $post)
        {
            if (empty($post->category_id)) return;

            $category = Category::getCategory($post->category_id);
            $struct[] = array('categoryId' => $category->id, 'categoryName' => $category->name);

            return $struct;
        }

        public function mt_setPostCategories($args, $post)
        {
            if (empty($args[0]['categoryId'])) return;
            $category = Category::getCategoryIDbyName($args[0]['categoryName']);

            SQL::current()->replace("post_attributes",
                              array("post_id", "name"),
                              array("name" => "category_id",
                                    "value" => $category->id,
                                    "post_id" => $post->id));
        }

        # return a list of categories to the XMLRPC system.
        public function xmlrpc_getCategoryList()
        {
            $categories_struct = array();

            if ($categories = Category::getCategory()) {
                foreach ($categories as $category) {
                    $struct = array();
                    $struct['categoryId'] = $category['id'];
                    $struct['categoryName'] = $category['name'];
                    $categories_struct[] = $struct;
                }
            }

            return $categories_struct;
        }
        /* End XML-RPC Support */

        public function parse_urls($urls) {
            $urls["/\/category\/(.*?)/"] = "/?action=category&name=$1";
            return $urls;
        }

        public function manage_posts_column_header() {
            echo "<th>".__("Category", "categorize")."</th>";
        }

        public function manage_posts_column($post) {
            echo (isset($post->category->name) && $post->category->id != FALSE)
                ? "<td>" . $post->category->name . "</td>"
                : "<td>&nbsp;</td>";
        }

        public function post_options($fields, $post = null) {
            $categories = Category::getCategory();

            $fields_list[0]["value"] = "0";
            $fields_list[0]["name"] = "- None -";
            if (!isset($post->category_id))
                $fields_list[0]["selected"] = true;

            if (!empty($categories)) # make sure we don't try to process an empty list.
                foreach ($categories as $category) {
                    $fields_list[$category["id"]]["value"] = $category["id"];
                    $fields_list[$category["id"]]["name"] = $category["name"];
                    if (isset($post->category_id))
                        $fields_list[$category["id"]]["selected"] = ($post ? $post->category_id == $category["id"] : true);
                }

            $fields[] = array("attr" => "option[category_id]",
                              "label" => __("Category", "categorize"),
                              "type" => "select",
                              "options" => $fields_list
                        );

            return $fields;
        }

        public function post($post) {
            if (!empty($post->category_id))
                $post->category = Category::getCategory($post->category_id);
        }

        public function main_context($context) {
            $context["categorize"] = Category::getCategoryList();
            return $context;
        }

        public function main_category($main) {
            # make sure we have enough information to continue
            if (!isset($_GET['name']))
                $reason = "no_category_requested";
            elseif (!$category = Category::getCategorybyClean($_GET['name']))
                $reason = "category_not_found";

            if (isset($reason))
                return $main->resort(array("pages/category", "pages/index"),
                                     array("reason" => $reason),
                                        __("Invalid Category", "categorize"));

            $attributes = SQL::current()->select("post_attributes",
                                           array("post_id"),
                                           array("name" => "category_id",
                                                 "value" => $category->id));

            $ids = array();
            foreach ($attributes->fetchAll() as $index => $row)
                $ids[] = $row["post_id"];

            if (empty($ids))
                return $main->resort(array("pages/category", "pages/index"),
                                     array("reason" => "category_not_found"),
                                        __("Invalid Category", "categorize"));

            $posts = new Paginator(Post::find(array("placeholders" => true,
                                                    "where" => array("id" => $ids))),
                                   Config::current()->posts_per_page);

            if (empty($posts))
                return false;

            $main->display(array("pages/category", "pages/index"),
                           array("posts" => $posts, "category" => $category->name),
                              _f("Posts in category %s", $_GET['name'], "categorize"));
        }

        public function main_index($main) {
            $ids = array();

            # this mammoth query allows searching for posts on the main page in 1 query
            $record = SQL::current()->query("SELECT __posts.id FROM __posts
                        LEFT JOIN __post_attributes
                            ON (__posts.id = __post_attributes.post_id
                            AND __post_attributes.name = 'category_id')
                        LEFT JOIN __categorize
                            ON (__post_attributes.value = __categorize.id
                            AND __post_attributes.name = 'category_id')
                        WHERE (__categorize.show_on_home = 1
                            OR __post_attributes.value IS NULL
                            OR __post_attributes.value = 0)
                        GROUP BY __posts.id
                    ");

            foreach ($record->fetchAll() as $entry)
                $ids[] = $entry['id'];

            if (empty($ids))
                return false;

            $posts = new Paginator(Post::find(array("placeholders" => true,
                                            "where" => array("id" => $ids))),
                                            Config::current()->posts_per_page);

            if (empty($posts))
                return false;

            $main->display(array("pages/index"),
                           array("posts" => $posts));
            return true;
        }

        static function manage_nav($navs) {
            if (!Visitor::current()->group->can('manage_categorize'))
                return $navs;

            $navs["manage_category"] = array("title" => __("Categories", "categorize"),
                                             "selected" => array("add_category", "delete_category", "edit_category"));

            return $navs;
        }

        static function manage_nav_pages($pages) {
            array_push($pages, "manage_category", "add_category", "delete_category", "edit_category");
            return $pages;
        }

        public function admin_manage_category($admin) {
            if (!Visitor::current()->group->can('manage_categorize'))
                show_403(__("Access Denied"), __('You do not have sufficient privileges to manage categories.', 'categorize'));

            $admin->display("manage_category", array(
                "categorize" => Category::getCategory()
                ));
        }

        public function admin_add_category($admin) {
            if (!Visitor::current()->group->can('manage_categorize'))
                show_403(__("Access Denied"), __('You do not have sufficient privileges to manage categories.', 'categorize'));

            # deal with a good submission
            if (isset($_POST['add']))
                if (!empty($_POST['name'])) {
                    Category::addCategory($_POST);
                    Flash::notice(__("Category added.", "categorize"), "/admin/?action=manage_category");
                } else
                    $fields['categorize'] = array("name" => $_POST['name']);

            $admin->display("add_category", $fields = array());
            # we land here when we aren't posting
        }

        public function admin_edit_category($admin) {
            if (!Visitor::current()->group->can("manage_categorize"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to manage categories.", "categorize"));

            if (empty($_REQUEST['id']))
                error(__("No ID Specified"), __("An ID is required to edit a category.", "categorize"));

            if (isset($_POST['update']))
                if (!empty($_POST['name'])) {
                    Category::updateCategory($_POST);
                    Flash::notice(__("Category updated.", "categorize"), "/admin/?action=manage_category");
                } else
                    $fields["categorize"] = array("name" => $_POST['name'], "show_on_home" => $_POST['show_on_home']);
            else
                $fields["categorize"] = Category::getCategory($_REQUEST['id']);

            $admin->display("edit_category", $fields, "Edit category");
        }

        public function admin_delete_category($admin) {
            if (!Visitor::current()->group->can("manage_categorize"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to manage categories.", "categorize"));

            Category::deleteCategory($_REQUEST['id']);
            Flash::notice(__("Category deleted.", "categorize"), "/admin/?action=manage_category");
        }

        /* Import Hooks */

        public function import_wordpress_post($item, $post) {
            if (!isset($item->category)) return;

            $categories = array();
            foreach ($item->category as $category)
                if (!empty($category) and isset($category->attributes()->domain) and (substr_count($category->attributes()->domain, "category") > 0) and isset($category->attributes()->nicename))
                    $categories[strip_tags(trim($category))] = (string) $category->attributes()->nicename;

            if (!empty($categories)) {
                foreach($categories as $catname => $catclean) {

                    if ($catname === 'Uncategorized') {
                        // do not use the uncategorized category, just let it empty
                        break;
                    }

                    $category = Category::getCategoryIDbyName($catname);

                    if (!$category || !isset($category->id)) {

                        // add new category to store

                        SQL::current()->insert(
                                            "categorize",
                                            array(
                                                "name" => ":name",
                                                "clean" => ":clean",
                                                "show_on_home" => ":show_on_home"
                                            ),
                                            array(
                                                ":name" => $catname,
                                                ":clean" => $catclean,
                                                ":show_on_home" => 1
                                            )
                                        );

                        $category = Category::getCategoryIDbyName($catname);
                    }

                    if ($category && isset($category->id)) {
                        SQL::current()->insert(
                                            "post_attributes",
                                            array(
                                                "name" => "category_id",
                                                "value" => $category->id,
                                                "post_id" => $post->id
                                            )
                                        );
                        // only use first category, chyrp cat module only allows one
                        break;
                    }
                }
            }
        }
    }
