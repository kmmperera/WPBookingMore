<?php
/*
Plugin Name: Simple Booking Plugin Two
Description: A simple booking plugin for managing staff and services.
Version: 1.0
Author: Your Name
*/

// Enqueue scripts and styles
function sbp_enqueue_scripts() {
    wp_enqueue_style('sbp-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_script('sbp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), null, true);
    wp_localize_script('sbp-script', 'sbp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sbp_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'sbp_enqueue_scripts');

// Create custom database tables
function sbp_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE {$wpdb->prefix}sbp_staff (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}sbp_services (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        staff_id mediumint(9) NOT NULL,
        service_name tinytext NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}sbp_bookings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        staff_id mediumint(9) NOT NULL,
        service_id mediumint(9) NOT NULL,
        date date NOT NULL,
        time time NOT NULL,
        name tinytext NOT NULL,
        email tinytext NOT NULL,
        telephone tinytext NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'sbp_create_tables');

// Handle AJAX request to get services
function sbp_get_services() {
    check_ajax_referer('sbp_nonce', 'nonce');

    if (!isset($_POST['staff_id'])) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    $staff_id = intval($_POST['staff_id']);
    $services = $wpdb->get_results($wpdb->prepare("SELECT id, service_name FROM {$wpdb->prefix}sbp_services WHERE staff_id = %d", $staff_id));

    if ($services) {
        wp_send_json_success($services);
    } else {
        wp_send_json_error('No services found.');
    }
}
add_action('wp_ajax_sbp_get_services', 'sbp_get_services');
add_action('wp_ajax_nopriv_sbp_get_services', 'sbp_get_services');

// Handle AJAX request to get available slots
function sbp_get_available_slots() {
    check_ajax_referer('sbp_nonce', 'nonce');

    if (!isset($_POST['staff_id'], $_POST['service_id'], $_POST['date'])) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    $staff_id = intval($_POST['staff_id']);
    $service_id = intval($_POST['service_id']);
    $date = sanitize_text_field($_POST['date']);

    // Example: Generate time slots from 9:00 AM to 5:00 PM with a 30-minute interval
    $time_slots = [];
    $start_time = strtotime('09:00');
    $end_time = strtotime('17:00');

    for ($time = $start_time; $time <= $end_time; $time = strtotime('+30 minutes', $time)) {
        $time_slots[] = date('H:i', $time);
    }

    // Fetch already booked slots for the selected date
    $booked_slots = $wpdb->get_col($wpdb->prepare("SELECT time FROM {$wpdb->prefix}sbp_bookings WHERE staff_id = %d AND service_id = %d AND date = %s", $staff_id, $service_id, $date));

    // Filter out booked slots
    $available_slots = array_diff($time_slots, $booked_slots);

    wp_send_json_success($available_slots);
}
add_action('wp_ajax_sbp_get_available_slots', 'sbp_get_available_slots');
add_action('wp_ajax_nopriv_sbp_get_available_slots', 'sbp_get_available_slots');

// Handle AJAX request to handle booking
function sbp_handle_booking() {
    check_ajax_referer('sbp_nonce', 'nonce');

    if (!isset($_POST['staff'], $_POST['service'], $_POST['date'], $_POST['time'], $_POST['name'], $_POST['email'], $_POST['telephone'])) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    $staff_id = intval($_POST['staff']);
    $service_id = intval($_POST['service']);
    $date = sanitize_text_field($_POST['date']);
    $time = sanitize_text_field($_POST['time']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $telephone = sanitize_text_field($_POST['telephone']);

    // Check if the selected slot is already booked
    $is_booked = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sbp_bookings WHERE staff_id = %d AND service_id = %d AND date = %s AND time = %s", $staff_id, $service_id, $date, $time));

    if ($is_booked) {
        wp_send_json_error('This time slot is already booked.');
    }

    $wpdb->insert(
        "{$wpdb->prefix}sbp_bookings",
        [
            'staff_id' => $staff_id,
            'service_id' => $service_id,
            'date' => $date,
            'time' => $time,
            'name' => $name,
            'email' => $email,
            'telephone' => $telephone
        ]
    );

    if ($wpdb->insert_id) {
        // Send a confirmation email to the client
        $subject = "Booking Confirmation";
        $message = "Thank you for your booking.\n\nDetails:\n\nName: $name\nEmail: $email\nTelephone: $telephone\nDate: $date\nTime: $time\n\nWe look forward to serving you.";
        wp_mail($email, $subject, $message);

        wp_send_json_success('Booking successful.');
    } else {
        wp_send_json_error('Failed to save booking.');
    }
}
add_action('wp_ajax_sbp_handle_booking', 'sbp_handle_booking');
add_action('wp_ajax_nopriv_sbp_handle_booking', 'sbp_handle_booking');

function sbp_uninstall() {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbp_bookings");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbp_services");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbp_staff");
}
register_uninstall_hook(__FILE__, 'sbp_uninstall');

function sbp_booking_form_shortcode() {
    ob_start();
    ?>
    <div id="sbp-booking-form">
        <form id="sbp-form">
            <label for="sbp-staff">Select Staff Member:</label>
            <select id="sbp-staff" name="staff" required>
                <option value="">Select</option>
                <?php
                global $wpdb;
                $staff_members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sbp_staff");
                foreach ($staff_members as $staff) {
                    echo "<option value='{$staff->id}'>{$staff->name}</option>";
                }
                ?>
            </select>

            <label for="sbp-service">Select Service:</label>
            <select id="sbp-service" name="service" required>
                <option value="">Select</option>
            </select>

            <label for="sbp-date">Select Date:</label>
            <input type="date" id="sbp-date" name="date" required>

            <label for="sbp-time">Select Time:</label>
            <select id="sbp-time" name="time" required>
                <option value="">Select</option>
            </select>

            <label for="sbp-name">Name:</label>
            <input type="text" id="sbp-name" name="name" required>

            <label for="sbp-email">Email:</label>
            <input type="email" id="sbp-email" name="email" required>

            <label for="sbp-telephone">Telephone:</label>
            <input type="tel" id="sbp-telephone" name="telephone" required>

            <button type="submit">Book Now</button>
        </form>
        <div id="sbp-message"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sbp_booking_form', 'sbp_booking_form_shortcode');
