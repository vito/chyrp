<form action="<?php echo url("submit"); ?>" enctype="multipart/form-data" accept-charset="utf-8" method="post" class="regular_post" onsubmit="if (! document.getElementById('confirm_submit_tos').checked) { alert('You must accept the Terms of Submission.'); return false; } else return true;">
        
    <span id="post_type_title" style="position: relative; font-size: 20px;">
    <?php __("Submit a Text Post", "submission"); ?>
    </span>
    
    <div id="form_container">
        <h4 class="first"><?php echo __("Title", "submission"); ?> <span class="optional">(optional)</span></h4>
        <input class="text_field big wide" id="title" name="title" type="text" value="">

        <h4><?php echo __("Body", "submission"); ?></h4>
        <textarea class="big wide" id="body" name="body" rows="8"></textarea>
    </div>

    <label for="confirm_submit_tos">
        <div id="confirm_tos">
            <input type="checkbox" id="confirm_submit_tos" name="confirm_tos">
            <?php echo _f("I accept the <a href=\"%s\" target=\"_blank\">Terms of Submission</a>", array(url("terms_of_submission")), "submission"); ?>
        </div>
    </label>
    <br>

    <input type="text" value="Name (required)" maxlength="25" name="name" id="name"
                    onfocus="if (this.value == 'Name (required)') {
                                this.value = '';
                             }" onblur="if (this.value == '') {
                                          this.value = 'Name (required)';
                                        }">
    <input type="text" value="Email (required)" maxlength="100" name="email" id="email"
                    onfocus="if (this.value == 'Email (required)') {
                                this.value = '';
                             }" onblur="if (this.value == '') {
                                         this.value = 'Email (required)';
                                       }">
    <br>
    <p><button name="submit" type="submit" id="submit"><?php __("Submit", "submission"); ?></button></p>
    <input type="hidden" name="status" value="draft" id="draft" />
    <input type="hidden" name="feather" value="text" id="feather" />
    <input type="hidden" name="hash" value="$site.secure_hashkey" id="hash" />
</form>
