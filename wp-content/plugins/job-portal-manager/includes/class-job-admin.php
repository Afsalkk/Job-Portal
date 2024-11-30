<?php
class JobAdmin {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_job', array($this, 'save_meta_boxes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_meta_boxes() {
        add_meta_box(
            'job_details',
            __('Job Details', 'job-portal-manager'),
            array($this, 'render_job_details_meta_box'),
            'job',
            'normal',
            'high'
        );
    }

    public function render_job_details_meta_box($post) {
        wp_nonce_field('job_details_nonce', 'job_details_nonce');
        
        $salary = get_post_meta($post->ID, 'salary', true);
        $location = get_post_meta($post->ID, 'location', true);
        $company = get_post_meta($post->ID, 'company', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="salary"><?php _e('Salary', 'job-portal-manager'); ?></label></th>
                <td><input type="text" id="salary" name="salary" value="<?php echo esc_attr($salary); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="location"><?php _e('Location', 'job-portal-manager'); ?></label></th>
                <td><input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="company"><?php _e('Company', 'job-portal-manager'); ?></label></th>
                <td><input type="text" id="company" name="company" value="<?php echo esc_attr($company); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['job_details_nonce']) || !wp_verify_nonce($_POST['job_details_nonce'], 'job_details_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array('salary', 'location', 'company');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=job',
            __('Applications', 'job-portal-manager'),
            __('Applications', 'job-portal-manager'),
            'manage_options',
            'job-applications',
            array($this, 'render_applications_page')
        );

        add_submenu_page(
            'edit.php?post_type=job',
            __('Settings', 'job-portal-manager'),
            __('Settings', 'job-portal-manager'),
            'manage_options',
            'job-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_applications_page() {
        require_once JPM_PLUGIN_DIR . 'admin/applications-list-table.php';
        $applications_table = new Job_Applications_List_Table();
        $applications_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php _e('Job Applications', 'job-portal-manager'); ?></h1>
            <?php $applications_table->display(); ?>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Job Portal Settings', 'job-portal-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('job_portal_settings');
                do_settings_sections('job_portal_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('job_portal_settings', 'job_portal_settings');

        add_settings_section(
            'job_portal_main_settings',
            __('Main Settings', 'job-portal-manager'),
            array($this, 'settings_section_callback'),
            'job_portal_settings'
        );

        add_settings_field(
            'jobs_per_page',
            __('Jobs Per Page', 'job-portal-manager'),
            array($this, 'jobs_per_page_callback'),
            'job_portal_settings',
            'job_portal_main_settings'
        );
    }

    public function settings_section_callback() {
        echo '<p>' . __('Configure your job portal settings below:', 'job-portal-manager') . '</p>';
    }

    public function jobs_per_page_callback() {
        $options = get_option('job_portal_settings');
        $value = isset($options['jobs_per_page']) ? $options['jobs_per_page'] : 10;
        echo '<input type="number" name="job_portal_settings[jobs_per_page]" value="' . esc_attr($value) . '">';
    }
}// In your main plugin file

