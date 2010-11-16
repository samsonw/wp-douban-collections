<div class="wrap">
  <h2>Douban Collections Settings</h2>

  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="douban_collections">
    <?php wp_nonce_field('douban-collections-nonce'); ?>

    <h3>Usage</h3>
    <p>Create a page, then insert the code "[douban_collections]" in the content.</p>

    <br/>
    <h3>Douban Info</h3>
    <p>Input your douban username or user id.</p>

    <table class="form-table">
      <tbody><tr>
          <th><label for="douban_user_id">Douban User ID or username</label></th>
          <td><input type="text" class="regular-text" value="<?php echo $options['douban_user_id']; ?>" id="douban_user_id" name="douban_user_id"></td>
        </tr>
    </tbody></table>
    
    <br />
    <h3>Display Style</h3>
    <p>Tweak the display style of the collections page.</p>

    <table class="form-table">
      <tbody>
      <tr>
      <th><label for="status_reading_text">"Reading" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['reading']; ?>" id="status_reading_text" name="status_reading_text"></td>
      </tr>
      <tr>
      <th><label for="status_reading_text">"Read" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['read']; ?>" id="status_read_text" name="status_read_text"></td>
      </tr>
      <tr>
      <th><label for="status_reading_text">"Wish" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['wish']; ?>" id="status_wish_text" name="status_wish_text"></td>
      </tr>
    </tbody></table>
    
    <br />
    <h3>Refresh Collections Page</h3>
    <p>It usually takes 30 mins for the collections page to be refreshed, you can refresh yourself as well by clicking the "Refresh" button below.</p>
    <p class="submit"><input type="submit" value="Refresh" class="button-highlighted" name="refresh"></p>
    
  </div>
  <p class="submit"><input type="submit" value="Save Changes" class="button-primary" name="submit"></p>
</form>

