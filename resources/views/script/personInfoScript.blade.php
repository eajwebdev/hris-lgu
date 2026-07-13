<script>
    function calculateAge() {
        var birthday = document.getElementById('bday').value;
        var today = new Date();
        var birthDate = new Date(birthday);
        var age = today.getFullYear() - birthDate.getFullYear();

        if (today.getMonth() < birthDate.getMonth() || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
            age--;
        }
        document.getElementById('age').value = age;
    }
</script>
<script>
    function handleImageUpload() {
        var fileInput = document.getElementById('profile-image-input');
        var file = fileInput.files[0];

        if (file && file.type.startsWith('image/')) {
            var button = document.getElementById('capture-toggle1');
            button.classList.remove('btn-secondary');
            button.classList.add('btn-success');
            button.innerHTML = '<i class="fas fa-check"></i> Uploaded';
            button.disabled = true; // Disable the button to prevent re-uploading the same image
        } else {
            // Reset the file input if an invalid file is selected
            fileInput.value = '';
            alert('Please select a valid image file.');
        }
    }
</script>
<script>
    $(document).ready(function() {
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    
        // AJAX request to get provinces based on selected region
        $('#region').change(function() {
            var regionId = $(this).val();
            if (regionId) {
                $.ajax({
                    url: "{{ route('getProvinces', ['regionId' => ':regionId']) }}".replace(':regionId', regionId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#province').empty();
                        $('#province').append('<option value="">Select Province</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ $empid }}";
                            $('#province').append(
                                '<option value="' + value.province_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="add_prov">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#province').prop('disabled', false);
                    },
                    error: function() {
                        $('#province').empty();
                        $('#province').append('<option value="">Select Province</option>');
                        $('#province').prop('disabled', true);
                    }
                });
            } else {
                $('#province').empty();
                $('#province').append('<option value="">Select Province</option>');
                $('#province').prop('disabled', true);
            }
        });
    
        // AJAX request to get cities based on selected province
        $('#province').change(function() {
            var provinceId = $(this).val();
            if (provinceId) {
                $.ajax({
                    url: "{{ route('getCities', ['provinceId' => ':provinceId']) }}".replace(':provinceId', provinceId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#city').empty();
                        $('#city').append('<option value="">Select City</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ $empid }}";
                            $('#city').append(
                                '<option value="' + value.city_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="add_city">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#city').prop('disabled', false);
                    },
                    error: function() {
                        $('#city').empty();
                        $('#city').append('<option value="">Select City</option>');
                        $('#city').prop('disabled', true);
                    }
                });
            } else {
                $('#city').empty();
                $('#city').append('<option value="">Select City</option>');
                $('#city').prop('disabled', true);
            }
        });
    
        // AJAX request to get barangays based on selected city
        $('#city').change(function() {
            var cityId = $(this).val();
            if (cityId) {
                $.ajax({
                    url: "{{ route('getBarangays', ['cityId' => ':cityId']) }}".replace(':cityId', cityId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#barangay').empty();
                        $('#barangay').append('<option value="">Select Barangay</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ $empid }}";
                            $('#barangay').append(
                                '<option value="' + value.id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="add_brgy">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#barangay').prop('disabled', false);
                    },
                    error: function() {
                        $('#barangay').empty();
                        $('#barangay').append('<option value="">Select Barangay</option>');
                        $('#barangay').prop('disabled', true);
                    }
                });
            } else {
                $('#barangay').empty();
                $('#barangay').append('<option value="">Select Barangay</option>');
                $('#barangay').prop('disabled', true);
            }
        });
    
    
        // AJAX request to get provinces based on selected region
        $('#region1').change(function() {
            var regionId = $(this).val();
            if (regionId) {
                $.ajax({
                    url: "{{ route('getProvinces', ['regionId' => ':regionId']) }}".replace(':regionId', regionId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#province1').empty();
                        $('#province1').append('<option value="">Select Province</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ $empid }}";
                            $('#province1').append(
                                '<option value="' + value.province_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="padd_prov">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#province1').prop('disabled', false);
                    },
                    error: function() {
                        $('#province1').empty();
                        $('#province1').append('<option value="">Select Province</option>');
                        $('#province1').prop('disabled', true);
                    }
                });
            } else {
                $('#province1').empty();
                $('#province1').append('<option value="">Select Province</option>');
                $('#province1').prop('disabled', true);
            }
        });
    
        // AJAX request to get cities based on selected province
        $('#province1').change(function() {
            var provinceId = $(this).val();
            if (provinceId) {
                $.ajax({
                    url: "{{ route('getCities', ['provinceId' => ':provinceId']) }}".replace(':provinceId', provinceId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#city1').empty();
                        $('#city1').append('<option value="">Select City</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ $empid }}";
                            $('#city1').append(
                                '<option value="' + value.city_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="padd_city">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#city1').prop('disabled', false);
                    },
                    error: function() {
                        $('#city1').empty();
                        $('#city1').append('<option value="">Select City</option>');
                        $('#city1').prop('disabled', true);
                    }
                });
            } else {
                $('#city1').empty();
                $('#city1').append('<option value="">Select City</option>');
                $('#city1').prop('disabled', true);
            }
        });
    
        // AJAX request to get barangays based on selected city
        $('#city1').change(function() {
            var cityId = $(this).val();
            if (cityId) {
                $.ajax({
                    url: "{{ route('getBarangays', ['cityId' => ':cityId']) }}".replace(':cityId', cityId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#barangay1').empty();
                        $('#barangay1').append('<option value="">Select Barangay</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ $empid }}";
                            $('#barangay1').append(
                                '<option value="' + value.id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="padd_brgy">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#barangay1').prop('disabled', false);
                    },
                    error: function() {
                        $('#barangay1').empty();
                        $('#barangay1').append('<option value="">Select Barangay</option>');
                        $('#barangay1').prop('disabled', true);
                    }
                });
            } else {
                $('#barangay1').empty();
                $('#barangay1').append('<option value="">Select Barangay</option>');
                $('#barangay1').prop('disabled', true);
            }
        });
    });
</script>
@if(request()->is('pds/*') || request()->is('pds'))
<script>
    $(document).ready(function(){
        $('#changeProfilePicture').on('click', function(){
            $('#profilePictureInput').click();
        });
    
        $('#profilePictureInput').on('change', function(){
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    $('#changeProfilePicture').attr('src', event.target.result);
                }
                reader.readAsDataURL(file);
    
                const formData = new FormData();
                formData.append('profileImage', file);
    
                $.ajax({
                    url: '{{ route("updateProfilePicture", $empid) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Profile Updated!',
                            text: 'Your profile picture has been updated successfully.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                });
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('.update-field').on('change', function() {
            var elementType = $(this).prop('tagName').toLowerCase();
            if (elementType === 'input' || elementType === 'textarea') {
                columnid = $(this).data('column-id');
                columnname = $(this).data('column-name');
            } else if (elementType === 'select') {
                columnid = $(this).find('option:selected').data('column-id');
                columnname = $(this).find('option:selected').data('column-name');
            }
            
            var value = $(this).val();

            $.ajax({
                url: '{{ route("employeeUpdate") }}',
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
    });
</script>
@endif
<script>
    // Convert height from centimeters to meters
    function convertHeightCmToM() {
        var cm = parseFloat(document.getElementById('height_cm').value);
        if (!isNaN(cm)) {
            var meters = cm / 100; // Convert cm to m
            document.getElementById('height_m').value = meters.toFixed(2); // Round to 2 decimal places
        } else {
            document.getElementById('height_m').value = '';
        }
    }

    // Convert height from meters to centimeters
    function convertHeightMToCm() {
        var meters = parseFloat(document.getElementById('height_m').value);
        if (!isNaN(meters)) {
            var cm = meters * 100; // Convert m to cm
            document.getElementById('height_cm').value = Math.round(cm); // Round to nearest whole number
        } else {
            document.getElementById('height_cm').value = '';
        }
    }

    // Event listener for height in centimeters
    document.getElementById('height_cm').addEventListener('input', function () {
        convertHeightCmToM();
    });

    // Event listener for height in meters
    document.getElementById('height_m').addEventListener('input', function () {
        convertHeightMToCm();
    });

    // Initial conversion on page load
    convertHeightCmToM();

    // Convert weight from kilograms to pounds
    function convertWeightKgToLb() {
        var weightKg = parseFloat(document.getElementById('weight_kg').value);
        if (!isNaN(weightKg)) {
            var weightLb = weightKg * 2.20462;
            document.getElementById('weight_lb').value = Math.round(weightLb); // Round weight in pounds
        } else {
            document.getElementById('weight_lb').value = '';
        }
    }

    // Convert weight from pounds to kilograms
    function convertWeightLbToKg() {
        var weightLb = parseFloat(document.getElementById('weight_lb').value);
        if (!isNaN(weightLb)) {
            var weightKg = weightLb / 2.20462;
            document.getElementById('weight_kg').value = Math.round(weightKg); // Round weight in kilograms
        } else {
            document.getElementById('weight_kg').value = '';
        }
    }

    // Event listener for weight in kilograms
    document.getElementById('weight_kg').addEventListener('input', function() {
        convertWeightKgToLb();
    });

    // Event listener for weight in pounds
    document.getElementById('weight_lb').addEventListener('input', function() {
        convertWeightLbToKg();
    });
</script>
<script>
    document.getElementById('mobile').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '').substring(0, 11);
        let formattedValue = '';

        if (value.length > 0) {
            formattedValue = value.substring(0, 4);
        }
        if (value.length > 4) {
            formattedValue += '-' + value.substring(4, 7);
        }
        if (value.length > 7) {
            formattedValue += '-' + value.substring(7, 11);
        }

        e.target.value = formattedValue;
    });
</script>
<script>
    $(document).ready(function() {

        @if($employee->citizenship == 1 || $employee->citizenship == null)
            $('select[name="country"]').prop('disabled', true);
            $('.c-radio').prop('disabled', true);
        @endif

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