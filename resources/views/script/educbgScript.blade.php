<script>
    $(document).ready(function() {
        let empid = "{{ $empid }}"; 
        
        // Function to update educational background data
        function updateData() {
            let schools = [];
            let degrees = [];
            let periods = [];
            let levels = [];
            let years = [];
            let honors = [];
    
            // Extract values from input fields in #college-container
            $('input[name="coll_school[]"]').each(function() {
                schools.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="coll_course[]"]').each(function() {
                degrees.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="coll_period[]"]').each(function() {
                periods.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="coll_level[]"]').each(function() {
                levels.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="coll_grad[]"]').each(function() {
                years.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="coll_honor[]"]').each(function() {
                honors.push($(this).val().replace(/,/g, ''));
            });

            // Validate array lengths
            if (schools.length !== degrees.length || schools.length !== periods.length ||
                schools.length !== levels.length || schools.length !== years.length ||
                schools.length !== honors.length) {
                console.error('Mismatch between array lengths.');
                return;
            }
    
            // Send data via AJAX
            $.ajax({
                url: "{{ route('educBgUpdateArray') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    empid: empid,
                    schools: schools,
                    degrees: degrees,
                    periods: periods,
                    levels: levels,
                    years: years,
                    honors: honors
                },
                success: function(response) {
                    if (response.success) {
                        // console.log('Data updated successfully!');
                    } else {
                        console.error('Failed to update data:', response.message);
                    }
                }
            });
        }
    
        // Add new row for educational background
        $('#add-row-college').click(function() {
            var newRowIndex = $('#college-container .form-row').length;
            var newRow = `
                <div class="form-row mt-3 lbel" data-index="${newRowIndex}">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete" style="float: right;">
                            <i class="fas fa-times fa-sm"></i>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Name of School</label>
                        <input type="text" name="coll_school[]" class="form-control form-control-sm update-child" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Degree/Course</label>
                        <input type="text" name="coll_course[]" class="form-control form-control-sm update-child" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Period of Attendance</label>
                        <input type="text" name="coll_period[]" class="form-control form-control-sm update-child" placeholder="ex: 2021 - 2024">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Highest Level/Units Earned</label>
                        <input type="text" name="coll_level[]" class="form-control form-control-sm update-child" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                        <input type="number" name="coll_grad[]" class="form-control form-control-sm update-child" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Honors Received</label>
                        <input type="text" name="coll_honor[]" class="form-control form-control-sm update-child" placeholder="N/A">
                    </div>
                </div>
            `;
            $('#college-container').append(newRow);
            updateData();
        });

        function updateGraduateData() {
            let gradSchools = [];
            let gradCourses = [];
            let gradPeriods = [];
            let gradLevels = [];
            let gradYears = [];
            let gradHonors = [];

            // Extract values from input fields in #graduate-container
            $('input[name="grad_school[]"]').each(function() {
                gradSchools.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="grad_course[]"]').each(function() {
                gradCourses.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="grad_period[]"]').each(function() {
                gradPeriods.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="grad_level[]"]').each(function() {
                gradLevels.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="grad_grad[]"]').each(function() {
                gradYears.push($(this).val().replace(/,/g, ''));
            });
            $('input[name="grad_honor[]"]').each(function() {
                gradHonors.push($(this).val().replace(/,/g, ''));
            });

            // Validate array lengths
            if (gradSchools.length !== gradCourses.length || gradSchools.length !== gradPeriods.length ||
                gradSchools.length !== gradLevels.length || gradSchools.length !== gradYears.length ||
                gradSchools.length !== gradHonors.length) {
                console.error('Mismatch between array lengths.');
                return;
            }

            // Send data via AJAX
            $.ajax({
                url: "{{ route('educBgUpdateGraduateArray') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    empid: empid,
                    grad_schools: gradSchools,
                    grad_courses: gradCourses,
                    grad_periods: gradPeriods,
                    grad_levels: gradLevels,
                    grad_years: gradYears,
                    grad_honors: gradHonors
                },
                success: function(response) {
                    if (response.success) {
                        // console.log('Graduate data updated successfully!');
                    } else {
                        console.error('Failed to update graduate data:', response.message);
                    }
                }
            });
        }

        $('#add-row-graduate').click(function() {
            var newRowIndex = $('#graduate-container .form-row').length;
            var newRow = `
                <div class="form-row mt-3 lbel" data-index="${newRowIndex}">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete-grad" style="float: right;">
                            <i class="fas fa-times fa-sm"></i>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Name of School</label>
                        <input type="text" name="grad_school[]" class="form-control form-control-sm update-grad" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Degree/Course</label>
                        <input type="text" name="grad_course[]" class="form-control form-control-sm update-grad" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Period of Attendance</label>
                        <input type="text" name="grad_period[]" class="form-control form-control-sm update-grad" placeholder="ex: 2021 - 2024">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Highest Level/Units Earned</label>
                        <input type="text" name="grad_level[]" class="form-control form-control-sm update-grad" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                        <input type="number" name="grad_grad[]" class="form-control form-control-sm update-grad" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Honors Received</label>
                        <input type="text" name="grad_honor[]" class="form-control form-control-sm update-grad" placeholder="N/A">
                    </div>
                </div>
            `;
            $('#graduate-container').append(newRow);
            updateGraduateData();
        });

        // Detect changes in input fields and update data
        $('#college-container').on('input', '.update-child', function() {
            updateData();
        });

        // Handle row deletion
        $('#college-container').on('click', '.btn-delete', function() {
            $(this).closest('.form-row').remove();
            updateData();
        });

        $('#graduate-container').on('input', '.update-grad', function() {
            updateGraduateData();
        });

        $('#graduate-container').on('click', '.btn-delete-grad', function() {
            $(this).closest('.form-row').remove();
            updateGraduateData();
        });

        
    });

    $('.update-field').on('input', function() {
        columnid = $(this).data('column-id');
        columnname = $(this).attr('name');

        var value = $(this).val();
        
        $.ajax({
            url: '{{ route("educBgUpdate") }}',
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

</script>