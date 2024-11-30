<?php
class JobPostType {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Jobs', 'job-portal-manager'),
            'singular_name'      => __('Job', 'job-portal-manager'),
            'menu_name'          => __('Jobs', 'job-portal-manager'),
            'add_new'            => __('Add New', 'job-portal-manager'),
            'add_new_item'       => __('Add New Job', 'job-portal-manager'),
            'edit_item'          => __('Edit Job', 'job-portal-manager'),
            'new_item'           => __('New Job', 'job-portal-manager'),
            'view_item'          => __('View Job', 'job-portal-manager'),
            'search_items'       => __('Search Jobs', 'job-portal-manager'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'jobs'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-businessperson',
            'supports'           => array('title', 'editor', 'thumbnail'),
            'show_in_rest'       => true,
        );

        register_post_type('job', $args);
    }

    public function register_taxonomies() {
        // Register Job Category taxonomy
        register_taxonomy('job_category', 'job', array(
            'label' => __('Job Categories', 'job-portal-manager'),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));

        // Register Job Type taxonomy
        register_taxonomy('job_type', 'job', array(
            'label' => __('Job Types', 'job-portal-manager'),
            'hierarchical' => false,
            'show_in_rest' => true,
        ));
    }
    
    
}