<?php
class JobRestAPI {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('job-portal/v1', '/jobs', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_jobs'),
                'permission_callback' => '__return_true',
            )
        ));

        register_rest_route('job-portal/v1', '/jobs/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_job'),
                'permission_callback' => '__return_true',
            )
        ));

        register_rest_route('job-portal/v1', '/jobs/(?P<id>\d+)/apply', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'apply_for_job'),
                'permission_callback' => '__return_true',
            )
        ));
    }

    public function get_jobs($request) {
        $args = array(
            'post_type' => 'job',
            'posts_per_page' => 10,
            'paged' => $request->get_param('page') ?? 1,
        );

        $query = new WP_Query($args);
        $jobs = array();

        foreach ($query->posts as $post) {
            $jobs[] = $this->prepare_job_response($post);
        }

        return new WP_REST_Response($jobs, 200);
    }

    public function get_job($request) {
        $job_id = $request->get_param('id');
        $post = get_post($job_id);

        if (!$post || $post->post_type !== 'job') {
            return new WP_Error('not_found', 'Job not found', array('status' => 404));
        }

        return new WP_REST_Response($this->prepare_job_response($post), 200);
    }

    public function apply_for_job($request) {
        $job_id = $request->get_param('id');
        
        // Validate job exists
        if (!get_post($job_id)) {
            return new WP_Error('not_found', 'Job not found', array('status' => 404));
        }

        // Validate required fields
        $required_fields = array('name', 'email', 'phone', 'resume_url');
        foreach ($required_fields as $field) {
            if (empty($request->get_param($field))) {
                return new WP_Error('missing_field', "Missing required field: {$field}", array('status' => 400));
            }
        }

        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'job_applications',
            array(
                'job_id' => $job_id,
                'applicant_name' => sanitize_text_field($request->get_param('name')),
                'applicant_email' => sanitize_email($request->get_param('email')),
                'applicant_phone' => sanitize_text_field($request->get_param('phone')),
                'resume_url' => esc_url_raw($request->get_param('resume_url')),
                'cover_letter' => sanitize_textarea_field($request->get_param('cover_letter')),
            )
        );

        if (!$result) {
            return new WP_Error('db_error', 'Failed to submit application', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'message' => 'Application submitted successfully',
            'application_id' => $wpdb->insert_id
        ), 201);
    }

    private function prepare_job_response($post) {
        $categories = wp_get_post_terms($post->ID, 'job_category');
        $types = wp_get_post_terms($post->ID, 'job_type');

        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'categories' => array_map(function($term) {
                return array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }, $categories),
            'types' => array_map(function($term) {
                return array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }, $types),
            'meta' => array(
                'salary' => get_post_meta($post->ID, 'salary', true),
                'location' => get_post_meta($post->ID, 'location', true),
                'company' => get_post_meta($post->ID, 'company', true),
            ),
        );
    }
    
    
//    Job Application Form
    public function handle_application($request) {
        $job_id = $request->get_param('id');

        // Verify job exists
        $job = get_post($job_id);
        if (!$job || $job->post_type !== 'job') {
            return new WP_Error('invalid_job', 'Invalid job ID', array('status' => 404));
        }

        // Validate and sanitize form data
        $full_name = sanitize_text_field($request->get_param('fullName'));
        $email = sanitize_email($request->get_param('email'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $current_position = sanitize_text_field($request->get_param('currentPosition'));
        $linkedin = esc_url_raw($request->get_param('linkedin'));
        $portfolio = esc_url_raw($request->get_param('portfolio'));
        $cover_letter = sanitize_textarea_field($request->get_param('coverLetter'));

        // Validate required fields
        if (empty($full_name) || empty($email) || empty($phone)) {
            return new WP_Error('missing_fields', 'Required fields are missing', array('status' => 400));
        }

        // Handle file upload
        $resume = $request->get_file_params()['resume'];
        $upload_dir = wp_upload_dir();
        $resume_path = '';

        if ($resume && $resume['error'] === UPLOAD_ERR_OK) {
            $file_type = wp_check_filetype(basename($resume['name']), array(
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ));

            if (!$file_type['type']) {
                return new WP_Error('invalid_file', 'Invalid file type', array('status' => 400));
            }

            // Create unique filename
            $filename = wp_unique_filename($upload_dir['path'], $resume['name']);
            $resume_path = $upload_dir['path'] . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($resume['tmp_name'], $resume_path)) {
                return new WP_Error('upload_failed', 'Failed to upload file', array('status' => 500));
            }
        }

        // Create application post
        $application_data = array(
            'post_title'    => sprintf('Application from %s for %s', $full_name, $job->post_title),
            'post_type'     => 'job_application',
            'post_status'   => 'publish',
            'meta_input'    => array(
                'job_id'           => $job_id,
                'applicant_name'   => $full_name,
                'applicant_email'  => $email,
                'applicant_phone'  => $phone,
                'current_position' => $current_position,
                'linkedin_profile' => $linkedin,
                'portfolio_url'    => $portfolio,
                'resume_path'      => $resume_path,
                'cover_letter'     => $cover_letter,
                'application_date' => current_time('mysql')
            )
        );

        $application_id = wp_insert_post($application_data);

        if (is_wp_error($application_id)) {
            return new WP_Error('insert_failed', 'Failed to submit application', array('status' => 500));
        }

        // Send notification emails
        $this->send_application_notifications($application_id, $job_id, $full_name, $email);

        return new WP_REST_Response(array(
            'message' => 'Application submitted successfully',
            'application_id' => $application_id
        ), 200);
    }

    private function send_application_notifications($application_id, $job_id, $applicant_name, $applicant_email) {
        // Send confirmation email to applicant
        $to_applicant = $applicant_email;
        $subject_applicant = 'Application Received - ' . get_the_title($job_id);
        $message_applicant = sprintf(
            "Dear %s,\n\nThank you for applying for the position of %s. We have received your application and will review it shortly.\n\nBest regards,\n%s",
            $applicant_name,
            get_the_title($job_id),
            get_bloginfo('name')
        );
        wp_mail($to_applicant, $subject_applicant, $message_applicant);

        // Send notification to admin
        $admin_email = get_option('admin_email');
        $subject_admin = 'New Job Application - ' . get_the_title($job_id);
        $message_admin = sprintf(
            "New application received for %s\n\nApplicant: %s\nEmail: %s\n\nView application: %s",
            get_the_title($job_id),
            $applicant_name,
            $applicant_email,
            admin_url('post.php?post=' . $application_id . '&action=edit')
        );
        wp_mail($admin_email, $subject_admin, $message_admin);
    }
    
    
    
}