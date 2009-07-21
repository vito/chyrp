<?php
    function add_signature_updated_at() {
        if (!SQL::current()->query("SELECT signature FROM __comments"))
            echo __("Adding signature column to comments table...", "comments").
                 test(SQL::current()->query("ALTER TABLE __comments ADD  signature VARCHAR(32) DEFAULT '' AFTER status"));

        if (!SQL::current()->query("SELECT updated_at FROM __comments"))
            echo __("Adding updated_at column to comments table...", "comments").
                test(SQL::current()->query("ALTER TABLE __comments ADD  updated_at DATETIME DEFAULT NULL AFTER created_at"));
    }

    Config::fallback("auto_reload_comments", 30);
    Config::fallback("enable_reload_comments", false);

    add_signature_updated_at();
