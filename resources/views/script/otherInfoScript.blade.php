<script>
    $(document).ready(function() {
        let empid = "{{ $empid }}"; 
    
        function updateData() {
            let skillsHob = [];
            let recognition = [];
            let memOrg = [];
    
            $('input[name="skills_hob[]"]').each(function() {
                skillsHob.push($(this).val().replace(/,/g, '')); // Remove commas
            });
    
            $('input[name="recognition[]"]').each(function() {
                recognition.push($(this).val().replace(/,/g, '')); // Remove commas
            });
    
            $('input[name="mem_org[]"]').each(function() {
                memOrg.push($(this).val().replace(/,/g, '')); // Remove commas
            });
    
            if (skillsHob.length !== recognition.length || skillsHob.length !== memOrg.length) {
                console.error('Mismatch between array lengths.');
                return;
            }
            
            $.ajax({
                url: "{{ route('update-child-oi') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    empid: empid,
                    skills_hob: skillsHob,
                    recognition: recognition,
                    mem_org: memOrg
                },
                success: function(response) {
                    if (response.success) {
                       // console.log('Data updated successfully!');
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
                    <div class="col-md-3">
                        <input type="text" name="skills_hob[]" class="form-control form-control-sm update-child update-field-array" placeholder="N/A">
                    </div>
                    
                    <div class="col-md-4">
                        <input type="text" name="recognition[]" class="form-control form-control-sm update-child update-field-array" placeholder="N/A">
                    </div>
                    
                    <div class="col-md-4">
                        <input type="text" name="mem_org[]" class="form-control form-control-sm update-child update-field-array" placeholder="N/A">
                    </div>
                    
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete">
                            <i class="fas fa-trash fa-sm"></i>
                        </button>    
                    </div>
                </div>
            `;
    
            $('#form-container').append(newRow);
            updateData();
        });
    
        $('#form-container').on('input', '.update-child', function() {
            // Remove commas from input fields
            $(this).val($(this).val().replace(/,/g, ''));
            updateData();
        });
    
        $('#form-container').on('click', '.btn-delete', function() {
            $(this).closest('.form-row').remove();
            updateData();
        });
    
        $('.update-field').on('input', function() {
            let columnid = $(this).data('column-id');
            let columnname = $(this).attr('name');
            let value = $(this).val().replace(/,/g, ''); // Remove commas
            
            $.ajax({
                url: '{{ route("otherInfoUpdate") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: columnid,
                    column: columnname,
                    value: value
                },
                success: function(response) {
                    // Handle success
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        console.error('Validation errors:', errors);
                    } else {
                        console.error('Error:', xhr.responseText);
                    }
                }
            });
        });
    
    });
    </script>
    