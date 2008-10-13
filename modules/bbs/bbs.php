<?php
    foreach (glob(MODULES_DIR."/bbs/models/*.php") as $model)
        require $model;

    require "controller.BBS.php";

    /**
     * BBS
     */
    class BBS extends Modules {
        
    }