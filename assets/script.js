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
                        $('#sbp-service').html('<option value="">Select</option>');
                        $.each(services, function(index, service) {
                            $('#sbp-service').append('<option value="' + service.id + '">' + service.service_name + '</option>');
                        });
                    }
                }
            });
        } else {
            $('#sbp-service').html('<option value="">Select</option>');
        }
    });

    $('#sbp-service').change(function() {
        $('#sbp-date').val('');
        $('#sbp-time').html('<option value="">Select</option>');
    });

    $('#sbp-date').change(function() {
        var staff_id = $('#sbp-staff').val();
        var service_id = $('#sbp-service').val();
        var date = $(this).val();
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
                        $('#sbp-time').html('<option value="">Select</option>');
                        $.each(slots, function(index, slot) {
                            $('#sbp-time').append('<option value="' + slot + '">' + slot + '</option>');
                        });
                    }
                }
            });
        } else {
            $('#sbp-time').html('<option value="">Select</option>');
        }
    });

    $('#sbp-form').submit(function(e) {
        e.preventDefault();
        var form_data = $(this).serialize();
        $.ajax({
            url: sbp_ajax.ajax_url,
            type: 'POST',
            data: form_data + '&action=sbp_handle_booking&nonce=' + sbp_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    $('#sbp-message').html('<p>' + response.data + '</p>').addClass('success');
                    $('#sbp-form')[0].reset();
                } else {
                    $('#sbp-message').html('<p>' + response.data + '</p>').addClass('error');
                }
            }
        });
    });
});
