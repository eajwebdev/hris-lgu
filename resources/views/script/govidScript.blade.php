<script>
    $(document).ready(function() {
        $('input[type=radio]').on('change', function() {
            const index = $(this).attr('name').match(/\d+/)[0];

            if ($(this).val() === '1') {
                $(`#details-${index}`).prop('readonly', false).val('');
                $(`#details-${index}`).parent().show();
            } else {
                $(`#details-${index}`).prop('readonly', true).val('');
                $(`#details-${index}`).parent().hide();
            }
        });

        $('input[type=radio]:checked').each(function() {
            const index = $(this).attr('name').match(/\d+/)[0];
            if ($(this).val() === '1') {
                $(`#details-${index}`).prop('readonly', false).parent().show();
            } else {
                $(`#details-${index}`).prop('readonly', true).val('').parent().hide();
            }
        });
    });
</script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).ready(function() {
        var empid = {{ $empid }};
        
        $('.updated-data').on('change', function() {
            var column = $(this).attr('name');
            var index = $(this).data('array');
            var value = $(this).val();

            $.ajax({
                url: '{{ route("update.govids") }}',
                type: 'POST',
                data: {
                    empid: empid,
                    column: column,
                    index: index,
                    value: value
                },
                success: function(response) {
                    if (response.success) {
                        //console.log('Update successful!');
                    } else {
                        console.log('Update failed!');
                    }
                },
                error: function(xhr) {
                    console.log('Error:', xhr.responseText);
                }
            });
        });
    });
</script>
<script>
    document.querySelectorAll('.input-details').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/,/g, '');
        });
    });
</script>