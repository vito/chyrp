<?php
    function update_tags_structure() {
        if (SQL::current()->query("SELECT tags FROM __tags")) return;

        $tags = array();
        $get_tags = SQL::current()->query("SELECT * FROM __tags");
        echo __("Backing up tags...", "tags").test($get_tags);
        if (!$get_tags) return;

        while ($tag = $get_tags->fetchObject()) {
            if (!isset($tags[$tag->post_id]))
                $tags[$tag->post_id] = array("normal" => array(), "clean" => array());

            $tags[$tag->post_id]["normal"][] = "{{".$tag->name."}}";
            $tags[$tag->post_id]["clean"][] = "{{".$tag->clean."}}";
        }

        # Drop the old table.
        $delete_tags = SQL::current()->query("DROP TABLE __tags");
        echo __("Dropping old tags table...", "tags").test($delete_tags);
        if (!$delete_tags) return;

        # Create the new table.
        $tags_table = SQL::current()->query("CREATE TABLE IF NOT EXISTS __tags (
                                                 id INTEGER PRIMARY KEY AUTO_INCREMENT,
                                                 tags VARCHAR(250) DEFAULT '',
                                                 clean VARCHAR(250) DEFAULT '',
                                                 post_id INTEGER DEFAULT '0'
                                             ) DEFAULT CHARSET=utf8");
        echo __("Creating new tags table...", "tags").test($tags_table);
        if (!$tags_table) return;

        foreach ($tags as $post => $tag)
            echo _f("Inserting tags for post #%s...", array($post), "tags").
                 test(SQL::current()->insert("tags",
                                             array("tags" => implode(",", $tag["normal"]),
                                                   "clean" => implode(",", $tag["clean"]),
                                                   "post_id" => $post)));
    }

    update_tags_structure();
