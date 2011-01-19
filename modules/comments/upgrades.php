<?php
    function remove_signature_add_updated_at() {
        if (SQL::current()->query("SELECT signature FROM __comments"))
            echo __("Removing signature column from comments table...", "comments").
                 test(SQL::current()->query("ALTER TABLE __comments DROP COLUMN  signature"));

        if (!SQL::current()->query("SELECT updated_at FROM __comments"))
            echo __("Adding updated_at column to comments table...", "comments").
                test(SQL::current()->query("ALTER TABLE __comments ADD  updated_at DATETIME DEFAULT NULL AFTER created_at"));
    }

    function remove_defensio_set_akismet() {
        if (!Config::check("defensio_api_key")) {
            Config::fallback("defensio_api_key", " ", "Adding a temporary defensio_api_key...");
            Config::set("akismet_api_key", " ", "Creating akismet_api_key setting...");
            Config::remove("defensio_api_key");
        } else {
            Config::remove("defensio_api_key");
            Config::set("akismet_api_key", " ", "Creating akismet_api_key setting...");
        }
    }

    Config::fallback("auto_reload_comments", 30);
    Config::fallback("enable_reload_comments", false);

    remove_signature_add_updated_at();
    remove_defensio_set_akismet();
