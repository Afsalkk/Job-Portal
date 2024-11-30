<?php
class JobApplications {
    public function __construct() {
        // Nothing to initialize yet
    }

    public static function get_applications($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'job_id' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'job_applications';
        
        $where = '1=1';
        if ($args['job_id']) {
            $where .= $wpdb->prepare(' AND job_id = %d', $args['job_id']);
        }

        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $args['per_page'], $offset));
    }

    public static function get_applications_count($job_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'job_applications';
        
        if ($job_id) {
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE job_id = %d", $job_id));
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    }

    public static function update_application_status($application_id, $status) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'job_applications',
            array('status' => $status),
            array('id' => $application_id),
            array('%s'),
            array('%d')
        );
    }
}