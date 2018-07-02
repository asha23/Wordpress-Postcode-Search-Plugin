<?php

/* 
	Add an admin menu for the api keys
*/

add_action('admin_menu', function() {
    add_options_page( 'Postcode Search Settings', 'Postcode Search Settings', 'manage_options', 'postcode-search-settings', 'postcode_search_settings_page' );
});
 
 
add_action( 'admin_init', function() {
    register_setting( 'postcode-search-settings', 'map_option_1' );
});
 
 
function postcode_search_settings_page() {
  ?>
    <div class="wrap">
	<h1>Postcode Search Settings</h1>
	<p>You will need a working ipStack account for this to correctly return the Location based on the IP address</p>
	
      <form action="options.php" method="post">
 
        <?php
          settings_fields( 'postcode-search-settings' );
          do_settings_sections( 'postcode-search-settings' );
        ?>
        <table>
             
            <tr>
                <th>API KEY</th>
                <td>
					<p>Please enter the API key for <a href="https://ipstack.com/" target="_blank">IpStack</a>: </p>
					<input type="text" placeholder="" name="map_option_1" value="<?php echo esc_attr( get_option('map_option_1') ); ?>" size="50" />

				</td>
            </tr>
 
            <tr>
                <td><?php submit_button(); ?></td>
            </tr>
 
        </table>
 
      </form>
    </div>
  <?php
}



