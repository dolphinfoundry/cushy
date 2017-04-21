<?php
/*
Plugin Name: Cushy.com User Content
Plugin URI: https://github.com/dolphinfoundry/cushy
Description: a plugin to get your specific cushy.com user content into your blogs.
Version: 1.0
Author: foogyllis, reachsuman, nandzgowda
Author URI: http://cushy.com
License: GPL2
*/

global $cushy_db_version;
$cushy_db_version = "1.0";
$is_dubug         = false;
$endpoint         = ($is_dubug) ? 'dev.cushy.com' : 'cushy.com';
define('CUSHY_WP_BASE_URL', 'https://' . $endpoint);
define('CUSHY_WP_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!session_id()) session_start();

function cushy_create_db()
{

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name      = $wpdb->prefix . 'my_analysis';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        views smallint(5) NOT NULL,
        clicks smallint(5) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'cushy_create_db');

function cushy_install()
{
    global $wpdb;
    global $cushy_db_version;

    $table_name = $wpdb->prefix . "cushy_settings";

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      user_name tinytext NOT NULL,
      security_key VARCHAR(55) NOT NULL,
      url text NOT NULL,
      UNIQUE KEY id (id)
    ) ENGINE=InnoDB  DEFAULT COLLATE=latin1_general_ci;";

    $wpdb->query($sql);

}

register_activation_hook(__FILE__, 'cushy_install');

/* drop table when plugin is deactivated */

function cushy_remove_settings_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "cushy_settings";
    $sql        = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
    delete_option("cushy_plugin_db_version");
}
register_deactivation_hook(__FILE__, 'cushy_remove_settings_table');

function cushy_include_files()
{
    wp_enqueue_style('cushy', CUSHY_WP_PLUGIN_URL . 'css/cushy.css', false, '1.0' . time());
    wp_enqueue_script('cushy', CUSHY_WP_PLUGIN_URL . 'js/cushy.js', array(), '1.0.' . time(), true);
}

function cushy_settings_menu()
{
    cushy_include_files();
    add_menu_page('Cushy Settings Page', 'Cushy Settings', 'manage_options', 'cushy-settings', 'cushy_settings');
}

/* add cushy settings to menu */
add_action('admin_menu', 'cushy_settings_menu');

function cushy_settings()
{
    cushy_include_files();
    ?>

    <div class="wrap">
        <?php
        if (isset($_SESSION['is_updated']) && $_SESSION['is_updated'] != ""):
            ?>
            <div class="isa_success"><?php
                echo $_SESSION['is_updated'];
                $_SESSION['is_updated'] = "";
                ?></div>
            <?php
        endif;
        ?>

        <h1>Cushy Settings</h1>

        <form action="#" method="post" novalidate="novalidate" id="form">
            <?php
            settings_fields('cushy');
            ?>

            <?php
            global $wpdb;

            $tablename = $wpdb->prefix . 'cushy_settings';

            $results = $wpdb->get_results("SELECT * FROM $tablename");
            if ($results) {
                foreach ($results as $res) {
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="username">Username</label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="username" name="username" value="<?php echo esc_html($res->user_name); ?>" maxlength="40" />
                                <div id="error" class="field-error"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sec_key">Security Key</label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="sec_key" name="sec_key" value="<?php echo esc_html($res->security_key); ?>" maxlength="30" />
                                <div id="error" class="field-error"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="username">Blog URL</label>
                            </th>
                            <td>
                                <input type="text" class="regular-text" id="blog_url" name="blog_url" value="<?php echo esc_url($res->url); ?>" maxlength="80" />
                            </td>
                        </tr>
                    </table>
                    <?php
                }

            } else { ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="username">User Name</label>
                        </th>
                        <td>
                            <input type="text" id="username" name="username" class="regular-text" maxlength="40" />
                            <div id="error" class="field-error"></div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sec_key">Security Key</label>
                        </th>
                        <td>
                            <input type="text" id="sec_key" name="sec_key" class="regular-text" maxlength="30" />
                            <div id="error" class="field-error"></div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="username">Blog URL</label>
                        </th>
                        <td>
                            <input type="text" id="blog_url" name="blog_url" class="regular-text" maxlength="80" />
                        </td>
                    </tr>
                </table>
                <?php
            } ?>

            <?php
             do_settings_sections('cushy');
            ?>

            <input id="settingsSaveBtn" class="button button-primary" value="Login" type="button">
        </form>

        <?php
        if (isset($_POST['username']) && isset($_POST['sec_key']) ) {

            global $wpdb;

            $tablename = $wpdb->prefix . 'cushy_settings';

            $results = $wpdb->get_results("SELECT * FROM $tablename");

            $username = sanitize_text_field( $_POST['username'] );
            $sec_key = sanitize_text_field( $_POST['sec_key'] );
            $blog_url = sanitize_text_field( $_POST['blog_url'] );

            if ( strlen( $username ) > 40 ) {
                $username = substr( $username, 0, 40 );
            }

            if ( strlen( $sec_key ) > 30 ) {
                $sec_key = substr( $sec_key, 0, 30 );
            }

            if ( strlen( $blog_url ) > 80 ) {
                $blog_url = substr( $blog_url, 0, 80 );
            }

            if ($results) {

                $wpdb->update($tablename, array(
                    'user_name' => $username,
                    'security_key' => $sec_key,
                    'url' => $blog_url
                ), array(
                    'id' => 1
                ));
                $_SESSION['is_updated'] = $username . " login details has been updated";
                echo "<meta http-equiv='refresh' content='0'>";

            } else {

                $data = array(
                    'user_name' => $username,
                    'security_key' => $sec_key,
                    'url' => $blog_url
                );

                $wpdb->insert($tablename, $data);
                $_SESSION['is_updated'] = $username . " has been logged in";

                echo "<meta http-equiv='refresh' content='0'>";
            }
        }
        ?>

    </div>
    <?php
}

function cushy_add_button()
{
    ?>

    <a href="<?php
    echo add_query_arg(array(
        'action' => 'cushy_add',
        'width' => '500'
    ), admin_url('admin-ajax.php'));
    ?>"
       id="add-cushy-button" class="button add_media thickbox" title="Add cushys to your story">Add cushy</a>
    <input type="hidden" id="pluginPath" value="<?php echo esc_url(CUSHY_WP_PLUGIN_URL); ?>">
    <?php
    $get_cushy_access = cushy_get_wp_data();
    $user_name        = (isset($get_cushy_access['user_name'])) ? $get_cushy_access['user_name'] : "";
    $security_key     = (isset($get_cushy_access['security_key'])) ? $get_cushy_access['security_key'] : "";
    echo '<input type="hidden" id="user_name" value="' . esc_html($user_name) . '">';
    echo '<input type="hidden" id="sec_key" value="' . esc_html($security_key) . '">';
    ?>
    <?php
}

add_action('media_buttons', 'cushy_add_button', 11);

function cushy_add_plugin()
{
    $template_string = '
    <button type="button" class="button-link media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close media panel</span></span></button>
    <div class="media-modal-content cushy-media-modal">
    <div class="media-frame mode-select wp-core-ui" id="__wp-uploader-id-0">
    <div class="media-frame-title">
       <h1>Add cushys to your story</h1>
    </div>
    <div class="media-frame-contents">
    <div class="media-frame-content-items">
       <div class="widefat">
          <div id="listContainer" class="list-container">
             <div class="media-frame-content" data-columns="4">
                <div class="attachments-browser">
                   <div class="media-toolbar">
                      <button type="button" class="button media-button button-large media-button-backToLibrary return-btn" style="display: none;margin-top: 11px;">← Return to Cushy list</button>
                      <div class="media-toolbar-primary search-form">
                      <label for="media-search-input" class="screen-reader-text">Search Cushy</label>
                      <input type="text" placeholder="Search cushys..." id="media-search-input" class="search cushy-search-input">
                      <span class="search-fld-btn"></span>
                      </div>
                   </div>
                   
                   <div class="media-dynamic-content">
                     <ul tabindex="-1" class="attachments ui-sortable ui-sortable-disabled render-cushy-list" id="__attachments-view-250">
                     <li class="pre-loader-content">
                         <div class="pre-loader" style="display: block">
                          <img src="' . esc_url(CUSHY_WP_PLUGIN_URL . '/assets/rolling-lg.gif'). '" alt="cushy loader">
                       </div>
                     </li>
                     </ul>
                    </div>
                   
                   <div class="media-sidebar visible">
                      <div class="media-uploader-status" style="display: none;">
                         <h2>Uploading</h2>
                         <button type="button" class="button-link upload-dismiss-errors"><span class="screen-reader-text">Dismiss Errors</span></button>
                         <div class="media-progress-bar">
                            <div></div>
                         </div>
                         <div class="upload-details">
                            <span class="upload-count">
                            <span class="upload-index"></span> / <span class="upload-total"></span>
                            </span>
                            <span class="upload-detail-separator">–</span>
                            <span class="upload-filename"></span>
                         </div>
                         <div class="upload-errors"></div>
                      </div>
                      <div tabindex="0" data-id="1491" class="attachment-details save-ready cushy-overview" style="display: none">
                         <h2>
                            Cushy details           <span class="settings-save-status">
                            <span class="spinner"></span>
                            <span class="saved">Saved.</span>
                            </span>
                         </h2>
                         <div class="attachment-info">
                            <div class="thumbnail thumbnail-image">
                               <img src="' . esc_url(CUSHY_WP_PLUGIN_URL . '/assets/cushy-logo.png'). '" draggable="false" alt="Cushy Preview">
                            </div>
                         </div>
                         <label class="setting" data-setting="caption">
                         <span class="name">Caption</span><br>
                         <textarea class="cushy-caption" readonly></textarea>
                         </label>
                         <label class="setting" data-setting="alt">
                         <span class="name">Place</span><br>
                         <input type="text" class="cushy-loc" readonly>
                         </label>
                         <label class="setting" data-setting="alt">
                         <span class="name">Date</span><br>
                         <input type="text" class="cushy-date" readonly>
                         </label>
                         <label class="setting tags-block" data-setting="alt">
                         <span class="name">Tags</span><br>
                         <span class="cushy-tags"></span>
                         </label>
                      </div>
                   </div>
                </div>
             </div>
          </div>
       </div>
    </div>
    <div class="media-frame-toolbar">
       <div class="media-toolbar">
          <div class="media-toolbar-secondary">
             
             <div class="media-selection" style="display: none">
                <div class="selection-info">
                    <span class="count"></span>
                    <button type="button" class="button-link edit-selection">Edit Selection</button>
                    <button type="button" class="button-link clear-selection">Clear</button>
                </div>
              </div>
             
          </div>
          <div class="media-toolbar-primary search-form">
             <button type="button" class="button media-button button-primary button-large media-button-insert" id="insert" disabled>Insert into post</button>
          </div>
       </div>
    </div>';

    echo $template_string;
    exit();
}

add_action('wp_ajax_cushy_add', 'cushy_add_plugin');

function cushy_view_card($atts)
{
    if (count($atts) > 0 && isset($atts['id'])) {
        $cushy_id   = $atts['id'];
        $img_data   = (isset($atts['img_data'])) ? explode("x", $atts['img_data']) : array();
        $img_width  = (isset($img_data[0])) ? $img_data[0] : "100";
        $img_height = (isset($img_data[1])) ? $img_data[1] : "100";

        $cushy_card = '<div id="iframe-content-' . esc_attr($atts['id']) . '" class="iframe-content" style="border: 1px solid rgb(219, 219, 219); position: relative; left: 0px; width: 100%; height: auto; z-index: 99;">
                        <div class="iframe-pre-loader" style="display: block; height: 100%; width: 100%; background: #D8D8D8 url(' . esc_url(CUSHY_WP_PLUGIN_URL . 'assets/loader.png') . ') no-repeat center center; background-size: initial; position: absolute; left: 0; top: 0; z-index: 100;"></div>
                        <iframe id="' . esc_attr($atts['id']) . '" class="cushy-iframe embed-responsive-item" src="' . esc_url(CUSHY_WP_BASE_URL . '/sections/view/' . $atts['id']) . '" frameborder="0" allowfullscreen style="background-color: #F8F8F8; height: 100%; width: calc(100%);"></iframe>
                        </div>';
        $cushy_card .= '<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>';
        $cushy_card .= '<script src="' . esc_js(CUSHY_WP_PLUGIN_URL . 'js/cushy.js?v=' . time()) . '"></script>';
        $cushy_card .= '<script>
                           /*** Set Iframe aspect ratio on initiazlise ***/
                           $.fn.initIframeContent = function(imgWidth, imgHeight, sectionWidth) {
                             $(".cushy-card").css("display", "block");
                             var cushyId = \'' . $cushy_id . '\';
                             var sectionHeight = $(document).find(".entry-content").innerHeight();
                             var iframeHeight = (imgHeight/imgWidth * sectionWidth);
                             iframeHeight = Math.round(iframeHeight);
                             
                             $("#iframe-content-" + cushyId).css({"border": "1px solid #ddd", "width": sectionWidth + "px", "height": iframeHeight + "px", "max-width": sectionWidth + "px"});
                             
                             document.getElementById(\'' . $cushy_id . '\').onload= function() {
                                $("#iframe-content-" + \'' . $cushy_id . '\').find(".iframe-pre-loader").fadeOut();
                                $(document).find(".cushy-preview").remove();
                             };
                           }
                           
                            var imgWidth = ' . $img_width . ';
                            var imgHeight = ' . $img_height . ';
                            var sectionWidth = Math.round($(document).find(".entry-content").innerWidth());
                            
                            $.fn.initIframeContent(imgWidth, imgHeight, sectionWidth);
                            
                            $(window).resize(function () {
                                $.fn.initIframeContent(imgWidth, imgHeight, sectionWidth);
                            })
                      </script>';
    } else
        $cushy_card = "";

    return $cushy_card;
}

add_shortcode('cushyview', 'cushy_view_card');

function cushy_get_wp_data()
{
    global $wpdb;
    $user_credentials = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "cushy_settings");
    $user_name        = (isset($user_credentials->user_name)) ? $user_credentials->user_name : "";
    $sec_key          = (isset($user_credentials->security_key)) ? $user_credentials->security_key : "";
    return array(
        'user_name' => $user_name,
        'security_key' => $sec_key
    );
}

cushy_get_wp_data();
?>