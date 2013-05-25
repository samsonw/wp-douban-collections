<div class="wrap">
  <h2>Douban Collections Settings</h2>

  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="douban_collections">
    <?php wp_nonce_field('douban-collections-nonce'); ?>

    <h3>Usage</h3>
    <p>Create a page, then insert the code "[douban_collections category="CATEGORY" with_user_info="true"]" in the content, CATEGORY now supports "book"/"music"/"movie", "with_user_info" supports "true" and "false"</p>

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
    <h3>"Books" Display Style</h3>
    <p>Tweak the display style of the book collections page.</p>

    <table class="form-table">
      <tbody>
      <tr>
      <th><label for="book_status_reading_text">"Reading" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['book']['reading']; ?>" id="book_status_reading_text" name="book_status_reading_text"></td>
      </tr>
      <tr>
      <th><label for="book_status_read_text">"Read" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['book']['read']; ?>" id="book_status_read_text" name="book_status_read_text"></td>
      </tr>
      <tr>
      <th><label for="book_status_wish_text">"Wish" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['book']['wish']; ?>" id="book_status_wish_text" name="book_status_wish_text"></td>
      </tr>
    </tbody></table>
    
    <br />

    <h3>"Music" Display Style</h3>
    <p>Tweak the display style of the music collections page.</p>

    <table class="form-table">
      <tbody>
      <tr>
        <th><label for="music_status_wish_text">"Wish" status display name</label></th>
        <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['music']['wish']; ?>" id="music_status_wish_text" name="music_status_wish_text"></td>
      </tr>
      <tr>
        <th><label for="music_status_listening_text">"Listening" status display name</label></th>
        <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['music']['listening']; ?>" id="music_status_listening_text" name="music_status_listening_text"></td>
      </tr>
      <tr>
        <th><label for="music_status_listened_text">"Listened" status display name</label></th>
        <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['music']['listened']; ?>" id="music_status_listened_text" name="music_status_listened_text"></td>
      </tr>
    </tbody></table>
    
    <br />

    <h3>"Movies" Display Style</h3>
    <p>Tweak the display style of the movie collections page.</p>

    <table class="form-table">
      <tbody>
      <tr>
      <th><label for="movie_status_wish_text">"Wish" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['movie']['wish']; ?>" id="movie_status_wish_text" name="movie_status_wish_text"></td>
      </tr>
      <tr>
      <th><label for="movie_status_watching_text">"Watching" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['movie']['watching']; ?>" id="movie_status_watching_text" name="movie_status_watching_text"></td>
      </tr>
      <tr>
      <th><label for="movie_status_watched_text">"Watched" status display name</label></th>
      <td><input type="text" class="regular-text" value="<?php echo $options['status_text']['movie']['watched']; ?>" id="movie_status_watched_text" name="movie_status_watched_text"></td>
      </tr>
    </tbody></table>
    
    <br />
    <p>Tweak and customize the CSS styles</p>
    <table class="form-table">
      <tbody>
        <tr>
          <th><label for="custom_css_styles">Custom CSS Styles</label></th>
          <td><textarea id="custom_css_styles" name="custom_css_styles" cols="60" rows="5" style="min-width:40em;"><?php echo $options['custom_css_styles']; ?></textarea><br/>
            <span class="description">These custom CSS styles will overwrite the Douban Collections default CSS styles.</span><br/>
            <span class="description">For example, to custom the individual book box size and its background color, you can use custom css like:</span><br/>
            <span class="description">#douban_collections ul li div.dc_entry {background-color: #0D0D0D; height: 125px; width: 195px;}</span><br/>
            <span class="description">[You can also copy all the Douban Collections default css from "Plugin Editor" here and make any modifications you like, these modifications won't be lost when you update the plugin.]</span>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="load_resources_only_in_douban_collections_page">Load Douban Collections resources only in specific pages:</label></th>
          <td><input type="checkbox" value="1" id="load_resources_only_in_douban_collections_page" name="load_resources_only_in_douban_collections_page" <?php if($options['load_resources_only_in_douban_collections_page']) echo 'checked="checked"';?> > Enable&nbsp;&nbsp;<input type="text" id="douban_collections_page_names" style="width:38em;" value="<?php echo $options['douban_collections_page_names']; ?>" name="douban_collections_page_names" <?php if(!$options['load_resources_only_in_douban_collections_page']) echo 'disabled="disabled"';?> > <span class="description">(comma separated)</span><br/>
            <span class="description">If enabled, Douban Collections plugin resources (css, js etc) will only be loaded in these specfic pages,</span><br/>
            <span class="description">thus improve the load speed of other pages and posts.</span>
          </td>
        </tr>
      </tbody>
    </table>
    <br />
    <p class="submit"><input type="submit" value="Save Changes" class="button-primary" name="submit"></p>
    <h3>Refresh Collections Page</h3>
    <p>It usually takes 30 mins for the collections page to be refreshed to sync with your latest douban collections, you can refresh yourself manually as well by clicking the "Refresh" button below.</p>
    <p class="submit"><input type="submit" value="Refresh" class="button-highlighted" name="refresh"></p>
  </form>
</div>

