<?php
/*
  Plugin Name: WP Scheduled Read-Only
  Description: Schedule readonly mode for your WordPress blog
  Version: 1.3.2
  Author: N.O.U.S. Open Useful and Simple
  Author URI:  https://apps.avecnous.eu/?mtm_campaign=wp-plugin&mtm_kwd=wp-scheduled-read-only
  Text Domain: wp-scheduled-read-only
  Domain Path: /languages/
  Network: 1
 */

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}
global $WPScheduledReadOnly;
$WPScheduledReadOnly = new WPScheduledReadOnly();

class WPScheduledReadOnly {

    public $active;
    public $now;
    public $from;
    public $to;
    public $MU;

    function __construct() {
        $this->MU = is_multisite() && is_plugin_active_for_network( 'wp-scheduled-read-only/readonly.php' );

        load_plugin_textdomain('wp-scheduled-read-only', false, 'wp-scheduled-read-only/languages');
        add_action($this->MU ? 'network_admin_menu' : 'admin_menu', array(&$this, 'menu'));
        add_action('admin_init', array(&$this, 'admin_init'));

        add_action('admin_post_wp_scheduled_readonly', array(&$this, 'save_conf'));

        add_filter('comments_template', array(&$this, 'comments_template'), 100, 1);
        add_action('wp_head', array(&$this, 'wp_head'));
        add_action('wp_loaded', array(&$this, 'filters'));

        $eelv_readonly = $this->get_option('eelv_readonly');
        $this->active = (isset($eelv_readonly['active']) && $eelv_readonly['active'] == 1) ? true : false;
        $this->from = $eelv_readonly['from'] != '' ? current_time(strtotime($eelv_readonly['from'])) : '';
        $this->to = $eelv_readonly['to'] != '' ? current_time(strtotime($eelv_readonly['to'])) : '';
        $this->who = $eelv_readonly['who'] ? $eelv_readonly['who'] : array();
        $this->now = current_time( 'timestamp' );
    }

    //php4
    public function WPScheduledReadOnly() {
        $this->__construct();
    }

    // Ajout du menu d'option sur le reseau
    function menu() {
        add_submenu_page(
                $this->MU ? 'settings.php' : 'options-general.php',
                __('Read Only', 'wp-scheduled-read-only'),
                __('Read Only', 'wp-scheduled-read-only'),
                $this->MU ? 'manage_network' : 'manage_options',
                'wp_readonly_configuration',
                array(&$this, 'configuration')
            );
    }

    function get_option($option){
        $function = $this->MU ? 'get_site_option' : 'get_option';
        return $function($option);
    }
    function update_option($option, $value, $old_value=null){
        $function = $this->MU ? 'update_site_option' : 'update_option';
        return $function($option, $value, $old_value);
    }

    function is_administrator(){
        if (($this->MU && is_super_admin()) || (!$this->MU && current_user_can('manage_options'))) {
            return true;
        }
        return false;
    }
    function is_writer(){
        if ($this->is_administrator()) {
            return true;
        }
        global $current_user;
        $current_user_caps = array_keys($current_user->caps);
        $ncaps = count($current_user_caps);
        $role = isset($current_user_caps[$ncaps - 1]) ? $current_user_caps[$ncaps - 1] : '';
        return in_array($role, (array) $this->who);
    }

    function is_readonly() {
        if (!$this->is_writer()) {
            if ($this->active == true &&
                    ($this->from == '' || $this->from < $this->now) &&
                    ($this->to == '' || $this->to > $this->now)
            ) {
                return true;
            }
        }
        return false;
    }

    function admin_init() {
        if ($this->is_readonly()) {
            $format = get_option('date_format') . ', ' . get_option('time_format');
            wp_die(nl2br(sprintf(__("We are %s,\nsites are on read-only mode from %s to %s.", 'wp-scheduled-read-only'), date_i18n($format, $this->now), date_i18n($format, $this->from), date_i18n($format, $this->to))));
        }
    }

    function wp_head() {
        if ($this->is_readonly() && (is_single() || is_page())) {
            global $post;
            $post->comment_status = false;
        }
    }

    function comments_template($val) {
        if ($this->is_readonly()) {
            //return dirname( __FILE__ ) . '/comments-template.php';
        }
        return $val;
    }

    function comment_status($open, $post_id) {
        if ($this->is_readonly()) {
            return false;
        }
        return $open;
    }

    function filters() {
        if (!is_admin()) {
            add_filter('comments_open', array($this, 'comment_status'), 20, 2);
            wp_deregister_script('comment-reply');
        }
    }

    function save_conf() {
        if (!wp_verify_nonce(\filter_input(INPUT_POST,'readonly_nonce_settings',FILTER_SANITIZE_STRING), 'readonly_nonce_settings')) {
	    wp_die(__('Security error', 'wp-scheduled-read-only'));
	}
        $redirect = $this->MU ? 'network/settings.php?page=wp_readonly_configuration' : 'options-general.php?page=wp_readonly_configuration';
        if ($this->is_administrator()) {
            wp_redirect($redirect.'&confirm='.$this->update_option('eelv_readonly', $_REQUEST['eelv_readonly']));
            exit;
        }
        wp_redirect($redirect.'&confirm=no-role');
        exit;
    }

    function configuration() {
        $sql_date = date('Y-m-d H:i:s');
        ?>
        <div class="wrap">
            <div id="icon-edit" class="icon32 icon32-posts-newsletter"><br/></div>
            <h2><?php _e('Scheduled Read only', 'wp-scheduled-read-only'); ?></h2>
            <?php if ('1'===\filter_input(INPUT_GET,'confirm',FILTER_SANITIZE_STRING)) { ?>
                <div class="updated"><p><strong><?php _e('Read only setting has been registered !', 'wp-scheduled-read-only') ?></strong></p></div>
            <?php } ?>
            <?php if ('0'===\filter_input(INPUT_GET,'confirm',FILTER_SANITIZE_STRING)) { ?>
                <div class="error"><p><strong><?php _e('Read only setting was not saved...', 'wp-scheduled-read-only') ?></strong></p></div>
            <?php } ?>
            <?php if ('no-role'===\filter_input(INPUT_GET,'confirm',FILTER_SANITIZE_STRING)) { ?>
                <div class="notice notice-warning"><p><strong><?php _e('You can not edit this setting.', 'wp-scheduled-read-only') ?></strong></p></div>
            <?php } ?>
            <form method="post" action="<?php echo admin_url(); ?>admin-post.php">
                <input type="hidden" name="action" value="wp_scheduled_readonly">
                <?php wp_nonce_field('readonly_nonce_settings','readonly_nonce_settings') ?>
                <table class="widefat" style="margin-top: 1em;">
                    <tbody>
                        <tr>
                            <th width="20%">
                                <label><?php _e('Read-only:', 'wp-scheduled-read-only'); ?></label>
                            </th><td>
                                <input type="checkbox" name="eelv_readonly[active]"  value="1" <?php checked($this->active, '1', true); ?>>
                            </td>
                        </tr>
                        <tr>
                            <th width="20%">
                                <label for="readonly_from"><?php _e('From:', 'wp-scheduled-read-only'); ?></label>
                            </th><td>
                                <input type="datetime" id="readonly_from" name="eelv_readonly[from]" placeholder="<?php echo $sql_date; ?>" size="60"  value="<?php echo (!empty($this->from) ? date('Y-m-d H:i:s', $this->from) : ''); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th width="20%">
                                <label for="readonly_to"><?php _e('To:', 'wp-scheduled-read-only'); ?></label>
                            </th><td>
                                <input type="datetime" id="readonly_to" name="eelv_readonly[to]" placeholder="<?php echo $sql_date; ?>" size="60" value="<?php echo (!empty($this->to) ? date('Y-m-d H:i:s', $this->to) : ''); ?>">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <th width="20%">
                                <?php _e('Who can write ?', 'wp-scheduled-read-only'); ?>
                            </th><td>
        <?php
        global $wp_roles;
        if ( ! isset( $wp_roles ) ){
            $wp_roles = new WP_Roles();
        }
        $roles = $wp_roles->get_names();

        foreach ($roles as $role_value => $role_name): ?>
                                <p>
                                    <label>
                                        <input type="checkbox" name="eelv_readonly[who][<?php echo $role_value; ?>]" value="<?php echo $role_value; ?>" <?php checked(in_array($role_value, $this->who), '1', true); ?>/>
                                        <?php _e($role_name, 'default'); ?>
                                    </label>
                                </p>
        <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <p class="submit">
                                    <input type="submit"  class="button button-primary" value="<?php _e('save', 'wp-scheduled-read-only'); ?>" />
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </form>
        </div>

        <?php
    }

}
