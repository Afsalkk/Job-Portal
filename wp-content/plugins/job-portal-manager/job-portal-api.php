<?php
// job-portal-api.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Job_Portal_REST_API {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route(
            'job-portal/v1',  // Namespace
            '/jobs/(?P<id>\d+)/apply', // Route
            array(
                'methods' => WP_REST_Server::CREATABLE, // POST method
                'callback' => array($this, 'handle_application'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            )
        );
    }

    public function handle_application($request) {
        $job_id = $request->get_param('id');
        
        // Basic response for testing
        return new WP_REST_Response(array(
            'message' => 'Application endpoint reached',
            'job_id' => $job_id
        ), 200);
    }
}

// Initialize the class
new Job_Portal_REST_API();