<?php
/*
Plugin Name: Marionet
Description: Social share buttons with counters likes.
Version: 1.0
Author: Arkadiy
Author URI: http://joomline.ru
*/
?>
<div class="wrap">
    <div class="icon32" id="icon-options-general"></div>
    <h2><?php echo __("Marionet Settings", 'marionet'); ?></h2>

    <div id="message"
         class="updated fade" <?php if (!isset($_REQUEST['marionet_plgn_form_submit']) || $message == "") echo "style=\"display:none\""; ?>>
        <p><?php echo $message; ?></p>
    </div>

    <div class="error" <?php if ("" == $error) echo "style=\"display:none\""; ?>>
        <p>
            <strong><?php echo $error; ?></strong>
        </p>
    </div>

    <div>
        <form name="form1" method="post" action="admin.php?page=marionet" enctype="multipart/form-data">

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __("API Key", 'marionet'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='app_key'
                            type='text'
                            value='<?php echo $marionet_plgn_options['app_key']; ?>'
                            />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __("Currency to rouble exchange rate", 'marionet'); ?></th>
                    <td>
                        <input
                            class="regular-text code"
                            name='currency_excange_rate'
                            type='text'
                            value='<?php echo $marionet_plgn_options['currency_excange_rate']; ?>'
                            />
                    </td>
                </tr>
            </table>

            <input type="hidden" name="marionet_plgn_form_submit" value="submit"/>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>

            <?php wp_nonce_field(plugin_basename(dirname(__DIR__)), 'marionet_plgn_nonce_name'); ?>
        </form>
    </div>
    <br/>
    <div class="link">
        <a class="button-secondary" href="https://app.marionet.io" target="_blank"><?php echo __("Go to Marionet account", 'marionet'); ?></a>
    </div>
</div>
