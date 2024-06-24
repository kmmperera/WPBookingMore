jQuery(document).ready(function($) {
    $('#sbp-staff').change(function() {
        var staff_id = $(this).val();
        if (staff_id) {
            $.ajax({
                url: sbp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sbp_get_services',
                    nonce: sbp_ajax.nonce,
                    staff_id: staff_id
                },
                success: function(response) {
                    if (response.success) {
                        var services = response.data;
                        var options = '<option value="">Select</option>';
                        $.each(services, function(index, service) {
                            options += '<option value="' + service.id + '">' + service.service_name + '</option>';
                        });
                        $('#sbp-service').html(options);
                    } else {
                        $('#sbp-service').html('<option value="">No services found</option>');
                    }
                }
            });
        } else {
            $('#sbp-service').html('<option value="">Select</option>');
        }
    });

    $('#sbp-service, #sbp-date').change(function() {
        var staff_id = $('#sbp-staff').val();
        var service_id = $('#sbp-service').val();
        var date = $('#sbp-date').val();
        if (staff_id && service_id && date) {
            $.ajax({
                url: sbp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sbp_get_available_slots',
                    nonce: sbp_ajax.nonce,
                    staff_id: staff_id,
                    service_id: service_id,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        var slots = response.data;
                        var options = '<option value="">Select</option>';
                        $.each(slots, function(index, slot) {
                            options += '<option value="' + slot + '">' + slot + '</option>';
                        });
                        $('#sbp-time').html(options);
                    } else {
                        $('#sbp-time').html('<option value="">No slots available</option>');
                    }
                }
            });
        } else {
            $('#sbp-time').html('<option value="">Select</option>');
        }
    });

    $('#sbp-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: sbp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sbp_handle_booking',
                nonce: sbp_ajax.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    $('#sbp-message').html('<p class="success">' + response.data + '</p>');
                    $('#sbp-form')[0].reset();
                } else {
                    $('#sbp-message').html('<p class="error">' + response.data + '</p>');
                }
            }
        });
    });
});
