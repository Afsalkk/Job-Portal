<?php
/**
 * Plugin Name: Job Portal Manager
 * Description: Manage jobs and provide REST API access
 * Version: 1.0.0
 * Author: Afsal Xpert
 * Text Domain: job-portal-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('JPM_VERSION', '1.0.0');
define('JPM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JPM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once JPM_PLUGIN_DIR . 'includes/class-job-post-type.php';
require_once JPM_PLUGIN_DIR . 'includes/class-job-rest-api.php';
require_once JPM_PLUGIN_DIR . 'includes/class-job-admin.php';
require_once JPM_PLUGIN_DIR . 'includes/class-job-applications.php';

//require_once JPM_PLUGIN_DIR . 'application_form/Job_application_form.php';


// Initialize the plugin
class JobPortalManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // Initialize Custom Post Type
        new JobPostType();

        // Initialize REST API
        new JobRestAPI();

        // Initialize Admin Panel
        new JobAdmin();

        // Initialize Applications
        new JobApplications();

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Register deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
}
