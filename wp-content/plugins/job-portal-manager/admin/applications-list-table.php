<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Job_Applications_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'application',
            'plural'   => 'applications',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'applicant_name'=> __('Applicant Name', 'job-portal-manager'),
            'email'         => __('Email', 'job-portal-manager'),
            'job_title'     => __('Job Title', 'job-portal-manager'),
            'status'        => __('Status', 'job-portal-manager'),
            'resume'        => __('Resume', 'job-portal-manager'),
            'applied_date'  => __('Applied Date', 'job-portal-manager')
        ];
    }

    public function get_sortable_columns() {
        return [
            'applicant_name'=> ['applicant_name', true],
            'job_title'     => ['job_title', false],
            'applied_date'  => ['applied_date', false],
            'status'        => ['status', false]
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'email':
                return '<a href="mailto:' . esc_attr($item['email']) . '">' . esc_html($item['email']) . '</a>';
            case 'applied_date':
                return date('F j, Y', strtotime($item['applied_date']));
            case 'resume':
                return $item['resume'] ? '<a href="' . esc_url($item['resume']) . '" target="_blank">' . __('Download', 'job-portal-manager') . '</a>' : '-';
            default:
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="applications[]" value="%s" />', $item['id']
        );
    }

    public function column_applicant_name($item) {
        $actions = [
            'view'    => sprintf('<a href="%s">%s</a>', 
                admin_url('admin.php?page=job-applications&action=view&application=' . $item['id']), 
                __('View', 'job-portal-manager')
            ),
            'delete'  => sprintf('<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=job-applications&action=delete&application=' . $item['id']), 'delete_application_' . $item['id']),
                __('Are you sure you want to delete this application?', 'job-portal-manager'),
                __('Delete', 'job-portal-manager')
            )
        ];

        return sprintf('%1$s %2$s',
            '<strong>' . esc_html($item['applicant_name']) . '</strong>',
            $this->row_actions($actions)
        );
    }

    public function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'job-portal-manager')
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'job_applications';

        // Handle bulk actions
        $this->process_bulk_action();

        // Set up pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // Set pagination arguments
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        // Set columns
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Prepare query
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'applied_date';
        $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        $offset = ($current_page - 1) * $per_page;

        // Get items
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY %s %s LIMIT %d OFFSET %d",
                $orderby,
                $order,
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }

    private function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'job_applications';

        if ('delete' === $this->current_action()) {
            $applications = isset($_REQUEST['applications']) ? array_map('intval', $_REQUEST['applications']) : [];
            
            if (!empty($applications)) {
                check_admin_referer('bulk-' . $this->_args['plural']);
                
                foreach ($applications as $application_id) {
                    $wpdb->delete(
                        $table_name,
                        ['id' => $application_id],
                        ['%d']
                    );
                }
                
                wp_redirect(add_query_arg(
                    ['message' => 'deleted'],
                    admin_url('admin.php?page=job-applications')
                ));
                exit;
            }
        }
    }

    public function no_items() {
        _e('No job applications found.', 'job-portal-manager');
    }

    protected function get_table_classes() {
        return ['widefat', 'fixed', 'striped', $this->_args['plural']];
    }

    public function extra_tablenav($which) {
        if ('top' === $which) {
            ?>
            <div class="alignleft actions">
                <?php
                // Add filter dropdowns if needed
                $statuses = ['pending', 'approved', 'rejected'];
                ?>
                <select name="filter_status">
                    <option value=""><?php _e('All Statuses', 'job-portal-manager'); ?></option>
                    <?php foreach ($statuses as $status) : ?>
                        <option value="<?php echo esc_attr($status); ?>" <?php selected(isset($_REQUEST['filter_status']) ? $_REQUEST['filter_status'] : '', $status); ?>>
                            <?php echo esc_html(ucfirst($status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                submit_button(__('Filter', 'job-portal-manager'), 'button', 'filter_action', false);
                ?>
            </div>
            <?php
        }
    }
}