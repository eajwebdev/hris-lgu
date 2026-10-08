@if(request()->is('employees/add'))
<script>
    $(document).ready(function() {
        var $empIdInput = $('#emp_ID');
        var $feedback = $('#emp_ID_feedback');
        var $submitBtn = $('.add-form button[name="btn-submit"]');
        var checkTimer = null;
        var idIsTaken = false;

        function setFeedback(state, message) {
            $empIdInput.removeClass('is-valid is-invalid');
            $feedback.removeClass('text-success text-danger');

            if (state === 'valid') {
                $empIdInput.addClass('is-valid');
                $feedback.addClass('text-success').text(message);
            } else if (state === 'invalid') {
                $empIdInput.addClass('is-invalid');
                $feedback.addClass('text-danger').text(message);
            } else {
                $feedback.text(message || '');
            }
        }

        $empIdInput.on('input', function() {
            var empId = $(this).val().trim();
            clearTimeout(checkTimer);

            if (!empId) {
                idIsTaken = false;
                setFeedback(null, '');
                return;
            }

            setFeedback(null, 'Checking availability...');

            checkTimer = setTimeout(function() {
                $.ajax({
                    url: '{{ route("checkEmpId", ["empID" => ":empID"]) }}'.replace(':empID', encodeURIComponent(empId)),
                    method: 'GET',
                    success: function(response) {
                        idIsTaken = !!response.exists;
                        if (idIsTaken) {
                            setFeedback('invalid', 'This Employee ID already exists.');
                        } else {
                            setFeedback('valid', 'Employee ID is available.');
                        }
                    },
                    error: function() {
                        idIsTaken = false;
                        setFeedback(null, '');
                    }
                });
            }, 400);
        });

        $('.add-form').on('submit', function(e) {
            if (idIsTaken) {
                e.preventDefault();
                setFeedback('invalid', 'This Employee ID already exists. Please use a different one.');
                $empIdInput.focus();
            }
        });
    });
</script>
@endif
<script>
    $(document).ready(function() {
        $('select[name="country"]').prop('disabled', true);
        $('.c-radio').prop('disabled', true);
    
        $('select[name="citizenship"]').change(function() {
            var selectedValue = $(this).val();
    
            if (selectedValue == "2") {
                $('select[name="country"]').prop('disabled', false);
                $('.c-radio').prop('disabled', false);
            } else {
                $('select[name="country"]').prop('disabled', true);
                $('.c-radio').prop('disabled', true);
                $('.c-radio').prop('checked', false);
                $('select[name="country"]').prop('selectedIndex', 0);
            }
        });
    });
</script>