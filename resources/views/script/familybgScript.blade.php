<script>

$(document).ready(function() {
    let empid = "{{ $empid }}"; 

    function updateData() {
        let nameChildren = [];
        let dateBirths = []; 

        $('input[name="name_child[]"]').each(function() {
            nameChildren.push($(this).val());
        });

        $('input[name="date_birth[]"]').each(function() {
            dateBirths.push($(this).val());
        });

        if (nameChildren.length !== dateBirths.length) {
            console.error('Mismatch between name_children and date_birth arrays length.');
            return;
        }
        
        $.ajax({
            url: "{{ route('update-child') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                empid: empid,
                name_child: nameChildren,
                date_birth: dateBirths
            },
            success: function(response) {
                if (response.success) {
                    console.log('Data updated successfully!');
                } else {
                    console.error('Failed to update data:', response.message);
                }
            },
            error: function(xhr) {
                console.error('An error occurred:', xhr.responseText);
            }
        });
        
    }


    // Add row button click event
    $('#add-row-familybg').click(function() {
        var newRowIndex = $('#form-container .form-row').length;
        var newRow = `
            <div class="form-row mt-3 lbel" data-index="${newRowIndex}">
                <div class="col-md-6">
                    <input type="text" name="name_child[]" class="form-control form-control-sm update-child" placeholder="N/A">
                </div>
                
                <div class="col-md-5">
                    <input type="date" name="date_birth[]" class="form-control form-control-sm update-child" placeholder="N/A">
                </div>
                
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete"><i class="fas fa-trash fa-sm"></i> </button>    
                </div>
            </div>
        `;

        $('#form-container').append(newRow);

        updateData();
    });

    $('#form-container').on('input', '.update-child', function() {
        updateData();
    });

    $('#form-container').on('click', '.btn-delete', function() {
        $(this).closest('.form-row').remove();
        updateData();
    });


    $('.update-field').on('input', function() {
        columnid = $(this).data('column-id');
        columnname = $(this).attr('name');

        var value = $(this).val();
        
        $.ajax({
            url: '{{ route("familyBgUpdate") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: columnid,
                column: columnname,
                value: value
            },
            success: function(response) {
                
            },
            error: function(xhr, status, error) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    console.error('Validation errors:', errors);
                } else {
                    console.error('Error:', error);
                }
            }
        });
    });

    $('.update-field-array').on('input', function() {
        $.ajax({
            url: '{{ route("familyBgUpdateArray") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                names: names,
                dates: dates
            },
            success: function(response) {
                if (response.success) {
                    console.log('Data updated successfully!');
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    console.error('Validation errors:', errors);
                } else {
                    console.error('Error:', error);
                }
            }
        });
    });
    
});


</script>