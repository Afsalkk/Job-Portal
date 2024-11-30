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

    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Create applications table
        $table_name = $wpdb->prefix . 'job_applications';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            applicant_name varchar(100) NOT NULL,
            applicant_email varchar(100) NOT NULL,
            applicant_phone varchar(20) NOT NULL,
            resume_url text NOT NULL,
            cover_letter text,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
}

// Initialize the plugin
JobPortalManager::get_instance();


function job_application_form_shortcode($atts) {
    // Enqueue necessary scripts and styles
    wp_enqueue_script('job-application-form', plugin_dir_url(__FILE__) . 'js/job-application-form.js', array('jquery'), '1.0', true);
    wp_enqueue_style('job-application-form', plugin_dir_url(__FILE__) . 'css/job-application-form.css');

    // Get job ID from shortcode attributes
    $atts = shortcode_atts(array(
        'job_id' => 0
    ), $atts);

    // Verify job exists
    $job = get_post($atts['job_id']);
    if (!$job || $job->post_type !== 'job') {
        return '<p>Invalid job posting.</p>';
    }

    // Start output buffering
    ob_start();

    // Include the form template
    include plugin_dir_path(__FILE__) . '\templates\Job_application_form.php';

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('job_application_form', 'job_application_form_shortcode');