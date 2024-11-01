<?php
/*
Plugin Name: WP SEO Website Protection (by SiteGuarding.com)
Plugin URI: http://www.siteguarding.com/en/website-extensions
Description: Detects fakes links, hidden texts, iframes, JavaScripts codes and redirections.
Version: 1.1
Author: SiteGuarding.com (SafetyBis Ltd.)
Author URI: http://www.siteguarding.com
License: GPLv2
TextDomain: plgsgseo
*/ 
// rev.20200601

define('SEO_PLUGIN_VERSION', '1.1');

if (!defined('DIRSEP'))
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') define('DIRSEP', '\\');
    else define('DIRSEP', '/');
}
//error_reporting(E_ERROR | E_WARNING);
//error_reporting(E_ERROR);


if( is_admin() ) {
	
	error_reporting(0);
    
	add_action( 'admin_init', 'plgsgseo_admin_init' );
	function plgsgseo_admin_init()
	{
		wp_register_style( 'plgsgseo_LoadStyle', plugins_url('css/wp-seo-website-protection.css', __FILE__) );	
	}
    

    
    
	function register_plgsgseo_page() 
	{
		add_menu_page('plgsgseo_protection', 'SEO Protection', 'activate_plugins', 'plgsgseo_protection', 'register_plgsgseo_page_callback', plugins_url('images/', __FILE__).'seo-protection-logo.png');
	}
    add_action('admin_menu', 'register_plgsgseo_page');
    

	function register_plgsgseo_page_callback() 
	{
	    $action = '';
        if (isset($_REQUEST['action'])) $action = sanitize_text_field(trim($_REQUEST['action']));
        
        // Actions
        if ($action != '')
        {
            $action_message = '';
            switch ($action)
            {
                case 'StartScan':
                    if (check_admin_referer( 'name_10EFDDE97A00' ))
                    {
                        SEO_SG_Protection::Set_Params(array('progress_status' => 1 ));
                    }
                    break;
                    
                case 'StartScan_iframe':
                    SEO_SG_Protection::MakeAnalyze();
                    break;
            }
        }
        
        
        wp_enqueue_style( 'plgsgseo_LoadStyle' );
        
        SEO_SG_Protection_HTML::PluginPage();
    }
	
    
	function plgsgseo_activation()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgseo_config';
		if( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name .'"' ) != $table_name ) {
			$sql = 'CREATE TABLE IF NOT EXISTS '. $table_name . ' (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `var_name` char(255) CHARACTER SET utf8 NOT NULL,
                `var_value` LONGTEXT CHARACTER SET utf8 NOT NULL,
                PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql ); // Creation of the new TABLE
            
            SEO_SG_Protection::Set_Params( array('installation_date' => date("Y-m-d")) );
		}
	}
	register_activation_hook( __FILE__, 'plgsgseo_activation' );
    
    
	function plgsgseo_uninstall()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgseo_config';
		$wpdb->query( 'DROP TABLE ' . $table_name );
	}
	register_uninstall_hook( __FILE__, 'plgsgseo_uninstall' );
	
}


/**
 * Functions
 */


class SEO_SG_Protection_HTML
{
    public static function PluginPage()
    {
        $params = SEO_SG_Protection::Get_Params();
        
        $tab_id = intval($_GET['tab']);
        $tab_array = array(0 => '', 1 => '', 2 => '' );
        $tab_array[$tab_id] = 'active ';
           ?>
    
    <h3 class="ui header title_product">SEO Website Protection (<a href="https://www.siteguarding.com/en/wordpress-seo-website-protection" target="_blank">ver. <?php echo SEO_PLUGIN_VERSION; ?></a>)</h3>
    
    <div class="ui grid max-box">
    <div class="row">
    
    
    <div class="ui top attached tabular menu" style="margin-top:0;">
            <a href="admin.php?page=plgsgseo_protection&tab=0" class="<?php echo $tab_array[0]; ?> item"><i class="crosshairs icon"></i> SEO Scanner & Results</a>
            <a href="admin.php?page=plgsgseo_protection&tab=1" class="<?php echo $tab_array[1]; ?> item"><i class="desktop icon"></i> Security Extensions</a>
            <a href="admin.php?page=plgsgseo_protection&tab=2" class="<?php echo $tab_array[2]; ?> item"><i class="comments outline icon"></i> Help</a>
    </div>
    <div class="ui bottom attached segment">
    <?php
    if ($tab_id == 0)
    {
        $message_data = array(
            'type' => 'info',
            'header' => '',
            'message' => 'This free tool scans your posts and detects all links, iframes, JavaScripts and hidden text and codes on your pages. If you feel that website is infected, please try our Antivirus scanner or get analyze and cleaning services.',
            'button_text' => 'Clean Website',
            'button_url' => 'https://www.siteguarding.com/en/services/malware-removal-service',
            'help_text' => ''
        );
        echo '<div style="max-width:800px;margin-top: 10px;">';
        SEO_SG_Protection_HTML::PrintIconMessage($message_data);
        echo '</div>';
        


        if (intval($params['progress_status']) == 0)
        {
            ?>
            <form method="post" action="admin.php?page=plgsgseo_protection&tab=0">
    
            	<p class="submit startscanner">
            	  <input type="submit" name="submit" id="submit" class="button button-primary" value="Start Scanner">
            	</p>
    
    		<?php
    		wp_nonce_field( 'name_10EFDDE97A00' );
    		?>
    		<input type="hidden" name="page" value="plgsgseo_protection"/>
    		<input type="hidden" name="action" value="StartScan"/>
    		</form>
            
            <?php
        }
        else {
            ?>
            <script type="text/javascript">
            window.setTimeout(function(){ document.location.reload(true); }, 10000);
            </script>
            <p style="text-align: center; width: 100%;">
                <img width="120" height="120" src="<?php echo plugins_url('images/ajax_loader.svg', __FILE__); ?>" />
                <br /><br />
                The scanner is in progress.<br>
                Please wait, it will take 30-60 seconds.
            </p>
            <iframe src="admin.php?page=plgsgseo_protection&action=StartScan_iframe" style="height:1px;width:1px;"></iframe>
            <?php
        }
        

        if (intval($params['progress_status']) == 0)
        {
            ?>
            <h4 class="ui header">Results</h4>
            <?php
            if (isset($params['latest_scan_date']))
            {
                // Show report
                echo '<p>Latest scan was '.$params['latest_scan_date'].'</p>';
                
                $params['results'] = (array)json_decode($params['results'], true);

    
                    if (!SEO_SG_Protection::CheckAntivirusInstallation()) 
                    {
                        $action = 'install-plugin';
                        $slug = 'wp-antivirus-site-protection';
                        $install_url = wp_nonce_url(
                            add_query_arg(
                                array(
                                    'action' => $action,
                                    'plugin' => $slug
                                ),
                                admin_url( 'update.php' )
                            ),
                            $action.'_'.$slug
                        );
                        
                        $message_data = array(
                            'type' => 'alert',
                            'header' => '',
                            'message' => 'Antivirus is not installed. We advice to scan all the files and folders of your website. If your website got hacked - don\'t wait until it gets blacklisted. You can loose your customers and search engine positions.',
                            'button_text' => 'Install',
                            'button_url' => $install_url,
                            'help_text' => ''
                        );
                        echo '<div style="max-width:800px;margin-top: 10px;">';
                        SEO_SG_Protection_HTML::PrintIconMessage($message_data);
                        echo '</div>';
                    }

                
                if (intval($_GET['showdetailed']) == 0)
                {
                    /**
                     * Show simple
                     */
                    $results = SEO_SG_Protection::PrepareResults($params['results']);

                    echo '<h3>Bad words (<a href="admin.php?page=plgsgseo_protection&showdetailed=1">show details</a>)</h3>';
                    if (count($results['WORDS']))
                    {
                        echo '<table class="ui selectable celled table small">';
                        echo '<thead><tr><th>Words</th></thead>';
                        foreach ($results['WORDS'] as $word)
                        {
                            echo '<tr>';
                            echo '<td>'.$word.'</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    else echo '<p>No bad words detected.</p>';
                    
                    echo "<hr>";
                    
                    echo '<h3>Detected links (<a href="admin.php?page=plgsgseo_protection&showdetailed=1">show details</a>)</h3>';
                    if (count($results['A']))
                    {
                        echo '<table class="ui selectable celled table small">';
                        echo '<thead><tr><th>Links</th><th>Text in links</th></tr></thead>';
                        foreach ($results['A'] as $link => $txt)
                        {
                            echo '<tr>';
                            echo '<td>'.$link.'</td><td>'.$txt.'</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    else echo '<p>No strange links detected.</p>';
                    
                    echo "<hr>";
                    
                    echo '<h3>Detected iframes (<a href="admin.php?page=plgsgseo_protection&showdetailed=1">show details</a>)</h3>';
                    if (count($results['IFRAME']))
                    {
                        echo '<table class="ui selectable celled table small">';
                        echo '<thead><tr><th>Links</th></thead>';
                        foreach ($results['IFRAME'] as $link)
                        {
                            echo '<tr>';
                            echo '<td>'.$link.'</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    else echo '<p>No iframes detected.</p>';
                    
                    echo "<hr>";
                    
                    echo '<h3>Detected JavaScripts (<a href="admin.php?page=plgsgseo_protection&showdetailed=1">show details</a>)</h3>';
                    if (count($results['SCRIPT']))
                    {
                        echo '<table class="ui selectable celled table small">';
                        echo '<thead><tr><th>JavaScripts Link or codes</th></thead>';
                        foreach ($results['SCRIPT'] as $link)
                        {
                            echo '<tr>';
                            echo '<td>'.$link.'</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    else echo '<p>No iframes detected.</p>';
                }
                else {
                    /**
                     * Show detailed
                     */
                    $post_ids = array();
                    $post_titles = array();
                    if (count($params['results']['posts']['WORDS']))
                    {
                        foreach ($params['results']['posts']['WORDS'] as $post_id => $post_arr)
                        {
                            $post_ids[ $post_id ] = $post_id;
                        }
                    }
                    if (count($params['results']['posts']['A']))
                    {
                        foreach ($params['results']['posts']['A'] as $post_id => $post_arr)
                        {
                            $post_ids[ $post_id ] = $post_id;
                        }
                    }
                    if (count($params['results']['posts']['IFRAME']))
                    {
                        foreach ($params['results']['posts']['IFRAME'] as $post_id => $post_arr)
                        {
                            $post_ids[ $post_id ] = $post_id;
                        }
                    }
                    if (count($params['results']['posts']['SCRIPT']))
                    {
                        foreach ($params['results']['posts']['SCRIPT'] as $post_id => $post_arr)
                        {
                            $post_ids[ $post_id ] = $post_id;
                        }
                    }
                    $post_titles = SEO_SG_Protection::GetPostTitles_by_IDs($post_ids);
                    
                    echo '<h3>Detailed by post (<a href="admin.php?page=plgsgseo_protection&showdetailed=0">show simple</a>)</h3>'; 
                    if (count($params['results']['posts']['WORDS']))
                    {
                        foreach ($params['results']['posts']['WORDS'] as $post_id => $post_arr)
                        {
                            if (count($post_arr))
                            {
                                $edit_link = 'post.php?post='.$post_id.'&action=edit';
                                echo '<table class="ui selectable celled table small">';
                                echo '<thead><tr><th><b>Bad words in post ID: '.$post_id.'</b> ('.$post_titles[$post_id]/*SEO_SG_Protection::GetPostTitle_by_ID($post_id)*/.') <a href="'.$edit_link.'" target="_blank" class="edit_post"><i class="write icon"></i> edit</a></th></tr></thead>';
                                foreach ($post_arr as $word)
                                {
                                    echo '<tr>';
                                    echo '<td>'.$word.'</td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                            }
                        }
                    }
                    if (count($params['results']['posts']['A']))
                    {
                        foreach ($params['results']['posts']['A'] as $post_id => $post_arr)
                        {
                            if (count($post_arr))
                            {
                                $edit_link = 'post.php?post='.$post_id.'&action=edit';
                                echo '<table class="ui selectable celled table small">';
                                echo '<thead><tr><th class="ten wide"><b>Links in post ID: '.$post_id.'</b> ('.$post_titles[$post_id]/*SEO_SG_Protection::GetPostTitle_by_ID($post_id)*/.') <a href="'.$edit_link.'" target="_blank" class="edit_post"><i class="write icon"></i> edit</a></th><th class="six wide">Text in links</th></tr></thead>';
                                foreach ($post_arr as $link_data)
                                {
                                    foreach ($link_data as $link => $txt)
                                    {
                                        echo '<tr>';
                                        echo '<td>'.$link.'</td><td>'.$txt.'</td>';
                                        echo '</tr>';
                                    }
                                }
                                echo '</table>';
                            }
                        }
                    }
                    //else echo '<p>No strange links detected.</p>';
//print_r($params['results']['posts']['IFRAME']);
                    if (count($params['results']['posts']['IFRAME']))
                    {
                        foreach ($params['results']['posts']['IFRAME'] as $post_id => $post_arr)
                        {
                            if (count($post_arr))
                            {
                                $edit_link = 'post.php?post='.$post_id.'&action=edit';
                                echo '<table class="ui selectable celled table small">';
                                echo '<thead><tr><th><b>Iframes in post ID: '.$post_id.'</b> ('.$post_titles[$post_id]/*SEO_SG_Protection::GetPostTitle_by_ID($post_id)*/.') <a href="'.$edit_link.'" target="_blank" class="edit_post"><i class="write icon"></i> edit</a></th></tr></thead>';
                                foreach ($post_arr as $link)
                                {
                                    echo '<tr>';
                                    echo '<td>'.$link.'</td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                            }
                        }
                    }
                    //else echo '<p>No strange links detected.</p>';
//print_r($params['results']['posts']['SCRIPT']);exit;
                    if (count($params['results']['posts']['SCRIPT']))
                    {
                        foreach ($params['results']['posts']['SCRIPT'] as $post_id => $post_arr)
                        {
                            if (count($post_arr))
                            {
                                $edit_link = 'post.php?post='.$post_id.'&action=edit';
                                echo '<table class="ui selectable celled table small">';
                                echo '<thead><tr><th><b>JavaScript in post ID: '.$post_id.'</b> ('.$post_titles[$post_id]/*SEO_SG_Protection::GetPostTitle_by_ID($post_id)*/.') <a href="'.$edit_link.'" target="_blank" class="edit_post"><i class="write icon"></i> edit</a></th></tr></thead>';
                                foreach ($post_arr as $js_link => $js_code)
                                {
                                    if ($js_code == '') $js_code = $js_link;
                                    echo '<tr>';
                                    echo '<td>'.$js_code.'</td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                            }
                        }
                    }
                    //else echo '<p>No strange links detected.</p>';
                }
                
                
            }
            else echo '<p class="msg_alert">No results. Please click <b>Start Scanner</b> button.</p>';
        } 
   

    }
    
    
    
    
    if ($tab_id == 1)
    {
        ?>
        <h4 class="ui header">Security Extensions</h4>
            <style>
            .exten_box{width: 46%;}
            </style>
            
    		<div class="grid-box width25 grid-h exten_box">
    		  <div class="module mod-box widget_black_studio_tinymce">
    		    <div class="deepest">
    		      <h3 class="module-title">WP Antivirus Site Protection</h3>
    		      <div class="textwidget">
    		        <table class="table-val" style="height: 180px;">
    		          <tbody>
    		            <tr>
    		              <td class="table-vat">
    		                <ul style="list-style-type: circle;">
    		                  <li>
    		                    Deep scan of every file on your website
    		                  </li>
    		                  <li>
                        		Advanced Heuristic Logic to find more viruses
    		                  </li>
    		                  <li>
    		                    Daily update of the virus database
    		                  </li>
    		                  <li>
    		                    Daily cron for automatical scanning
    		                  </li>
    		                </ul>
    		              </td>
    		            </tr>
    		            <tr>
    		              <td class="table-vab">
    		                <a class="button button-primary extbttn" href="https://www.siteguarding.com/en/antivirus-site-protection">
    		                  Learn More
    		                </a>
    		              </td>
    		            </tr>
    		          </tbody>
    		        </table>
    		        <p>
    		          <img class="imgpos_ext" alt="WordPress Antivirus Site Protection" src="<?php echo plugins_url('images/wpAntivirusWebsiteProtection-logo.png', __FILE__); ?>">
    		        </p>
    		      </div>
    		    </div>
    		  </div>
    		</div>
            
        
    		<div class="grid-box width25 grid-h exten_box">
    		  <div class="module mod-box widget_black_studio_tinymce">
    		    <div class="deepest">
    		      <h3 class="module-title">WordPress GEO Protection</h3>
    		      <div class="textwidget">
    		        <table class="table-val" style="height: 180px;">
    		          <tbody>
    		            <tr>
    		              <td class="table-vat">
    		                <ul style="list-style-type: circle;">
    		                  <li>
    		                    Ban the visitors from unwanted countries
    		                  </li>
    		                  <li>
                        		Ban the visitors to your backend login page
    		                  </li>
    		                  <li>
    		                    Ban IP addresses which are bruteforcing your passwords
    		                  </li>
    		                  <li>
    		                    It's easy to setup and free to use
    		                  </li>
    		                </ul>
    		              </td>
    		            </tr>
    		            <tr>
    		              <td class="table-vab">
    		                <a class="button button-primary extbttn" href="https://www.siteguarding.com/en/wordpress-geo-website-protection">
    		                  Learn More
    		                </a>
    		              </td>
    		            </tr>
    		          </tbody>
    		        </table>
    		        <p>
    		          <img class="imgpos_ext" alt="WordPress Admin Protection" src="<?php echo plugins_url('images/wp-geo-website-protection.png', __FILE__); ?>">
    		        </p>
    		      </div>
    		    </div>
    		  </div>
    		</div>
            
    		<div class="grid-box width25 grid-h exten_box">
    		  <div class="module mod-box widget_black_studio_tinymce">
    		    <div class="deepest">
    		      <h3 class="module-title">WordPress Admin Protection</h3>
    		      <div class="textwidget">
    		        <table class="table-val" style="height: 180px;">
    		          <tbody>
    		            <tr>
    		              <td class="table-vat">
    		                <ul style="list-style-type: circle;">
    		                  <li>
    		                    Prevents password brute force attack with strong 'secret key'
    		                  </li>
    		                  <li>
                        		White & Black IP list access
    		                  </li>
    		                  <li>
    		                    Notifications by email about all not authorized actions
    		                  </li>
    		                  <li>
    		                    Protection for login page with captcha code
    		                  </li>
    		                </ul>
    		              </td>
    		            </tr>
    		            <tr>
    		              <td class="table-vab">
    		                <a class="button button-primary extbttn" href="https://www.siteguarding.com/en/wordpress-admin-protection">
    		                  Learn More
    		                </a>
    		              </td>
    		            </tr>
    		          </tbody>
    		        </table>
    		        <p>
    		          <img class="imgpos_ext" alt="WordPress Admin Protection" src="<?php echo plugins_url('images/wpAdminProtection-logo.png', __FILE__); ?>">
    		        </p>
    		      </div>
    		    </div>
    		  </div>
    		</div>
    		
    		
    		<div class="grid-box width25 grid-h exten_box">
    		  <div class="module mod-box widget_black_studio_tinymce">
    		    <div class="deepest">
    		      <h3 class="module-title">Graphic Captcha Protection</h3>
    		      <div class="textwidget">
    		        <table class="table-val" style="height: 180px;">
    		          <tbody>
    		            <tr>
    		              <td class="table-vat">
    		                <ul style="list-style-type: circle;">
    		                  <li>
    		                    Strong captcha protection
    		                  </li>
    		                  <li>
                        		Easy for human, complicated for robots
    		                  </li>
    		                  <li>
    		                    Prevents password brute force attack on login page
    		                  </li>
    		                  <li>
    		                    Blocks spam software
    		                  </li>
    		                  <li>
    		                    Different levels of the security
    		                  </li>
    		                </ul>
    		              </td>
    		            </tr>
    		            <tr>
    		              <td class="table-vab">
    		                <a class="button button-primary extbttn" href="https://www.siteguarding.com/en/wordpress-graphic-captcha-protection">
    		                  Learn More
    		                </a>
    		              </td>
    		            </tr>
    		          </tbody>
    		        </table>
    		        <p>
    		          <img class="imgpos_ext" alt="WordPress Graphic Captcha Protection" src="<?php echo plugins_url('images/wpGraphicCaptchaProtection-logo.png', __FILE__); ?>">
    		        </p>
    		      </div>
    		    </div>
    		  </div>
    		</div>
    		
    		
    		<div class="grid-box width25 grid-h exten_box">
    		  <div class="module mod-box widget_black_studio_tinymce">
    		    <div class="deepest">
    		      <h3 class="module-title">Admin Graphic Protection</h3>
    		      <div class="textwidget">
    		        <table class="table-val" style="height: 180px;">
    		          <tbody>
    		            <tr>
    		              <td class="table-vat">
    		                <ul style="list-style-type: circle;">
    		                  <li>
    		                    Good solution if you access to your website from public places or infected computers
    		                  </li>
    		                  <li>
    		                    Prevent password brute force attack with strong "graphic password"
    		                  </li>
    		                  <li>
    		                    Notifications by email about all not authorized actions
    		                  </li>
    		                </ul>
    		              </td>
    		            </tr>
    		            <tr>
    		              <td class="table-vab">
    		                <a class="button button-primary extbttn" href="https://www.siteguarding.com/en/wordpress-admin-graphic-password">
    		                  Learn More
    		                </a>
    		              </td>
    		            </tr>
    		          </tbody>
    		        </table>
    		        <p>
    		          <img class="imgpos_ext" alt="WordPress Admin Graphic Protection" src="<?php echo plugins_url('images/wpAdminGraphicPassword-logo.png', __FILE__); ?>">
    		        </p>
    		      </div>
    		    </div>
    		  </div>
    		</div>
    		
    		
    		<div class="grid-box width25 grid-h exten_box">
    		  <div class="module mod-box widget_black_studio_tinymce">
    		    <div class="deepest" >
    		      <h3 class="module-title">User Access Notification</h3>
    		      <div class="textwidget">
    		        <table class="table-val" style="height: 180px;">
    		          <tbody>
    		            <tr>
    		              <td class="table-vat">
    		                <ul style="list-style-type: circle;">
    		                  <li>
    		                    Catchs successful and failed login actions
    		                  </li>
    		                  <li>
    		                    Sends notifications to the user and to the administrator by email
    		                  </li>
    		                  <li>
    		                    Shows Date/Time of access action, Browser, IP address, Location (City, Country)
    		                  </li>
    		                </ul>
    		              </td>
    		            </tr>
    		            <tr>
    		              <td class="table-vab">
    		                <a class="button button-primary extbttn" href="https://www.siteguarding.com/en/wordpress-user-access-notification">
    		                  Learn More
    		                </a>
    		              </td>
    		            </tr>
    		          </tbody>
    		        </table>
    		        <p>
    		          <img class="imgpos_ext" alt="WordPress User Access Notification" src="<?php echo plugins_url('images/wpUserAccessNotification-logo.jpeg', __FILE__); ?>">
    		        </p>
    		      </div>
    		    </div>
    		  </div>
    		</div>
    		
    		

        <?php
    }
    
    
    

    if ($tab_id == 2)
    {
        ?>
        <h4 class="ui header">Support</h4>
        
		<p>
		For more information and details about SEO Website Protection please <a target="_blank" href="https://www.siteguarding.com/en/wordpress-seo-website-protection">click here</a>.<br />
		<a href="http://www.siteguarding.com/livechat/index.html" target="_blank">
			<img src="<?php echo plugins_url('images/livechat.png', __FILE__); ?>"/>
		</a><br />
		For any questions and support please use LiveChat or this <a href="https://www.siteguarding.com/en/contacts" rel="nofollow" target="_blank" title="SiteGuarding.com - Website Security. Professional security services against hacker activity. Daily website file scanning and file changes monitoring. Malware detecting and removal.">contact form</a>.<br>
		<br>
        
        <h3 class="apv_header">Extra Options</h3>
        <div class="ui message">
            <div class="header">Website Cleaning Services</div>
            <p>Your website got hacked and blacklisted by Google? This is really bad, you are going to lose your visitors. We will help you to clean your website and remove from all blacklists.</p>
            <p>
                <a href="https://www.siteguarding.com/en/services/malware-removal-service" target="_blank" class="ui green button">Clean My Website</a>&nbsp;&nbsp;or&nbsp;&nbsp;
                <a href="https://www.siteguarding.com/en/protect-your-website" target="_blank" class="ui green button">Get Security package</a>
            </p>
        </div>
		<a href="https://www.siteguarding.com/" target="_blank">SiteGuarding.com</a> - Website Security. Professional security services against hacker activity.<br />
		</p>
            <?php
    }
    


    ?>
    
    </div>
           
        
    </div>
    </div>	
    
    		<?php

    }
    
    
    
    
    
    
    public static function PrintIconMessage($data)
    {
        $rand_id = "id_".rand(1,10000).'_'.rand(1,10000);
        if ($data['type'] == '' || $data['type'] == 'alert') {$type_message = 'negative'; $icon = 'warning sign';}
        if ($data['type'] == 'ok') {$type_message = 'green'; $icon = 'checkmark box';}
        if ($data['type'] == 'info') {$type_message = 'yellow'; $icon = 'info';}
        ?>
        <div class="ui icon <?php echo $type_message; ?> message">
            <i class="<?php echo $icon; ?> icon"></i>
            <div class="msg_block_row">
                <?php
                if ($data['button_text'] != '' || $data['help_text'] != '') {
                ?>
                <div class="msg_block_txt">
                    <?php
                    if ($data['header'] != '') {
                    ?>
                    <div class="header"><?php echo $data['header']; ?></div>
                    <?php
                    }
                    ?>
                    <?php
                    if ($data['message'] != '') {
                    ?>
                    <p><?php echo $data['message']; ?></p>
                    <?php
                    }
                    ?>
                </div>
                <div class="msg_block_btn">
                    <?php
                    if ($data['help_text'] != '') {
                    ?>
                    <a class="link_info" href="javascript:;" onclick="InfoBlock('<?php echo $rand_id; ?>');"><i class="help circle icon"></i></a>
                    <?php
                    }
                    ?>
                    <?php
                    if ($data['button_text'] != '') {
                        if (!isset($data['button_url_target']) || $data['button_url_target'] == true) $new_window = 'target="_blank"';
                        else $new_window = '';
                    ?>
                    <a class="mini ui green button" <?php echo $new_window; ?> href="<?php echo $data['button_url']; ?>"><?php echo $data['button_text']; ?></a>
                    <?php
                    }
                    ?>
                </div>
                    <?php
                    if ($data['help_text'] != '') {
                    ?>
                        <div style="clear: both;"></div>
                        <div id="<?php echo $rand_id; ?>" style="display: none;">
                            <div class="ui divider"></div>
                            <p><?php echo $data['help_text']; ?></p>
                        </div>
                    <?php
                    }
                    ?>
                <?php
                } else {
                ?>
                    <?php
                    if ($data['header'] != '') {
                    ?>
                    <div class="header"><?php echo $data['header']; ?></div>
                    <?php
                    }
                    ?>
                    <?php
                    if ($data['message'] != '') {
                    ?>
                    <p><?php echo $data['message']; ?></p>
                    <?php
                    }
                    ?>
                <?php
                }
                ?>
            </div> 
        </div>
        <?php
    }
    
    
    
}


class SEO_SG_Protection
{
    public static $search_words = array(
        0 => 'document.write(',
    	6 => 'document.createElement(',
    	20 => 'display:none',
    	21 => 'poker',
    	22 => 'casino',
    	48=> 'hacked',
    	49=> 'cialis ',
    	52=> 'viagra '
    );
    
    
    public static function PrepareResults($results)
    {
        $a = array(
            'WORDS' => array(),
            'A' => array(),
            'IFRAME' => array(),
            'SCRIPT' => array()
        );
        
        //return $results;
        
        if (count($results['posts']['WORDS']))
        {
            foreach ($results['posts']['WORDS'] as $post_id => $post_arr)
            {
                foreach ($post_arr as $word)
                {
                    $a['WORDS'][$word] = $word;
                }
            }
        }
        
        if (count($results['posts']['A']))
        {
            foreach ($results['posts']['A'] as $posts)
            {
                if (count($posts))
                {
                    foreach ($posts as $post_id => $post_arr)
                    {
                        if (count($post_arr))
                        {
                            foreach ($post_arr as $post_link => $post_txt)
                            {
                                $a['A'][$post_link] = $post_txt;
                            }
                        }
                    }
                }
            }
        }
        
        
        if (count($results['posts']['IFRAME']))
        {
            foreach ($results['posts']['IFRAME'] as $posts)
            {
                if (count($posts))
                {
                    foreach ($posts as $post_id => $post_link)
                    {
                        $a['IFRAME'][$post_link] = $post_link;
                    }
                }
            }
        }
        
        //print_r($results['posts']['IFRAME']);exit;
        if (count($results['posts']['SCRIPT']))
        {
            foreach ($results['posts']['SCRIPT'] as $post_id => $post_arr)
            {
                foreach ($post_arr as $js_link => $js_code)
                {
                    if (strpos($js_link, "javascript code") !== false) $a['SCRIPT'][md5($js_code)] = $js_code;
                    else $a['SCRIPT'][md5($js_link)] = $js_link;
                }
            }
        }
        
        //echo '0000'.$post_link;exit;
        //print_r($a); exit;
        
        ksort($a['A']);
        ksort($a['SCRIPT']);
        sort($a['IFRAME']);
        return $a;
        
    }


    public static function GetPostTitle_by_ID($post_id)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'posts';
        
        $rows = $wpdb->get_results( 
        	"
        	SELECT post_title
        	FROM ".$table_name."
            WHERE ID = ".$post_id."
            LIMIT 1;
        	"
        );
        
        if (count($rows)) return $rows[0]->post_title;
        else return false;
    }
    
    public static function GetPostTitles_by_IDs($post_ids = array())
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'posts';
        
        $rows = $wpdb->get_results( 
        	"
        	SELECT ID, post_title
        	FROM ".$table_name."
            WHERE ID IN (".implode(",", $post_ids).")
        	"
        );
        
        if (count($rows)) 
        {
            $a = array();
            foreach ($rows as $row)
            {
                $a[$row->ID] = $row->post_title;
            }
            return $a;
        }
        else return false;
    }
    
    public static function MakeAnalyze()
    {
        error_reporting(0);
        ignore_user_abort(true);
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'posts';
        
        $rows = $wpdb->get_results( 
        	"
        	SELECT ID, post_content AS val_data
        	FROM ".$table_name."
        	"
        );
        
        $a = array();
        if (count($rows))
        {
            include_once(dirname(__FILE__).DIRSEP.'simple_html_dom.php');
            
            $domain = self::PrepareDomain(get_site_url());
            
            $a['total_scanned'] = count($rows);
            
            foreach ($rows as $row)
            {
                //$post_content = $row->val_data;
				$post_content = "<html><body>".$row->val_data."</body></html>";
                
                foreach (self::$search_words as $find_block)
                {
                    if (stripos($post_content, $find_block) !== false)
                    {
                        $a['posts']['WORDS'][$row->ID][] = $find_block;
                    }
                }
                
                $html = str_get_html($post_content);
                
                if ($html !== false)
                {
                    $tmp_a = array();
                    
                    // Tag A
                    foreach($html->find('a') as $e) 
                    {
                        $link = strtolower(trim($e->href));
                        if (strpos($link, $domain) !== false) continue;     // Skip own links
                        if (strpos($link, "mailto:") !== false) continue;
                        if (strpos($link, "callto:") !== false) continue;
                        if ( $link[0] == '?' || $link[0] == '/' ) continue;
                        if ( $link[0] != 'h' && $link[1] != 't' && $link[2] != 't' && $link[3] != 'p' ) continue;
                        
                        //$tmp_s = $link.' <span class="color_light_grey">[Txt: '.strip_tags($e->outertext).']</span>';

                        /*$tmp_data = array(
                            'l' => $link,
                            't' => strip_tags($e->outertext)
                        );
                        $tmp_a[$link] = $tmp_data;*/
                        $tmp_a[$link] = strip_tags($e->outertext);
                        
                        $a['posts']['A'][$row->ID][] = $tmp_a;
                    }
                    
                    
                    
                    // Tag IFRAME
                    foreach($html->find('iframe') as $e) 
                    {
                        $link = strtolower(trim($e->src));
                        if (strpos($link, $domain) !== false) continue;     // Skip own links
                        if ( $link[0] == '?' || $link[0] == '/' ) continue;
                        if ( $link[0] != 'h' && $link[1] != 't' && $link[2] != 't' && $link[3] != 'p' ) continue;
                        
                        /*$tmp_data = array(
                            'l' => $link,
                            't' => 'iframe'
                        );
                        $tmp_a[$link] = $tmp_data;*/
                        
                        $a['posts']['IFRAME'][$row->ID][] = $link;
                    }
                    
                    
                    
                	// Tag SCRIPT
                	foreach($html->find('script') as $e)
                	{
                	    if (isset($e->src)) 
                        {
                            $link = strtolower(trim($e->src));
                        
                            if (strpos($link, $domain) !== false) continue;     // Skip own links
                            if ( $link[0] == '?' || $link[0] == '/' ) continue;
                            if ( $link[0] != 'h' && $link[1] != 't' && $link[2] != 't' && $link[3] != 'p' ) continue;
                            
                            $t = '';
                        }
                        else  {
                            $link = 'javascript code '.rand(1, 1000);
                            $t = $e->innertext;
                        }
                        
                        /*$tmp_data = array(
                            'l' => $link,
                            't' => $t
                        );*/
                        $tmp_a[$link] = $t;
                        
                        $a['posts']['SCRIPT'][$row->ID] = $tmp_a;
                    }
                    
                }
                
                unset($html);
            }
            
        }
        
        // save results
        $data = array(
            'progress_status' => 0,
            'results' => json_encode($a),
            'latest_scan_date' => date("Y-m-d H:i:s")
        );
        SEO_SG_Protection::Set_Params($data);
    }
    
    
    
    public static function Get_Params($vars = array())
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'plgsgseo_config';
        
        $ppbv_table = $wpdb->get_results("SHOW TABLES LIKE '".$table_name."'" , ARRAY_N);
        if(!isset($ppbv_table[0])) return false;
        
        if (count($vars) == 0)
        {
            $rows = $wpdb->get_results( 
            	"
            	SELECT *
            	FROM ".$table_name."
            	"
            );
        }
        else {
            foreach ($vars as $k => $v) $vars[$k] = "'".$v."'";
            
            $rows = $wpdb->get_results( 
            	"
            	SELECT * 
            	FROM ".$table_name."
                WHERE var_name IN (".implode(',',$vars).")
            	"
            );
        }
        
        $a = array();
        if (count($rows))
        {
            foreach ( $rows as $row ) 
            {
            	$a[trim($row->var_name)] = trim($row->var_value);
            }
        }
    
        return $a;
    }
    
    
    public static function Set_Params($data = array())
    {
		global $wpdb;
		$table_name = $wpdb->prefix . 'plgsgseo_config';
    
        if (count($data) == 0) return;   
        
        foreach ($data as $k => $v)
        {
            $tmp = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE var_name = %s LIMIT 1;', $k ) );
            
            if ($tmp == 0)
            {
                // Insert    
                $wpdb->insert( $table_name, array( 'var_name' => $k, 'var_value' => $v ) ); 
            }
            else {
                // Update
                $data = array('var_value'=>$v);
                $where = array('var_name' => $k);
                $wpdb->update( $table_name, $data, $where );
            }
        } 
    }


	public static function PrepareDomain($domain)
	{
	    $host_info = parse_url($domain);
	    if ($host_info == NULL) return false;
	    $domain = $host_info['host'];
	    if ($domain[0] == "w" && $domain[1] == "w" && $domain[2] == "w" && $domain[3] == ".") $domain = str_replace("www.", "", $domain);
	    //$domain = str_replace("www.", "", $domain);
	    
	    return $domain;
	}

    
    public static function CheckAntivirusInstallation()
    {
        $avp_path = dirname(__FILE__);
		$avp_path = str_replace('wp-seo-website-protection', 'wp-antivirus-site-protection', $avp_path);
        return file_exists($avp_path);
    }
    

}



/* Dont remove this code: SiteGuarding_Block_74B383D88281 */
