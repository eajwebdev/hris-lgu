<script>
    const equivalences = [
        0.042, 0.083, 0.125, 0.167, 0.208, 0.250, 0.292, 0.333, 0.375,
        0.417, 0.458, 0.500, 0.542, 0.583, 0.625, 0.667, 0.708, 0.750,
        0.792, 0.833, 0.875, 0.917, 0.958, 1.000, 1.042, 1.083, 1.125,
        1.167, 1.208, 1.250
    ];

    function updateEquivalent() {
        let daysInput = parseInt(document.getElementById('days').value, 10);
        const sl = document.getElementById('sl');
        const vl = document.getElementById('vl'); 

        if (isNaN(daysInput) || daysInput < 1) {
            sl.value = '';
            vl.value = '';
            return;
        }

        if (daysInput > 30) {
            daysInput = 30;
            document.getElementById('days').value = 30;
        }

        if (daysInput >= 1 && daysInput <= 30) {
            const equivalentValue = equivalences[daysInput - 1];
            sl.value = equivalentValue.toFixed(3);
            vl.value = equivalentValue.toFixed(3);
        } else {
            sl.value = '';
            vl.value = ''; 
        }
    }
</script>
<script>
    function updateEquivalent1() {
        let daysInput = parseInt(document.getElementById('days1').value, 10);
        const sl = document.getElementById('sl1');
        const vl = document.getElementById('vl1'); 

        if (isNaN(daysInput) || daysInput < 1) {
            sl.value = '';
            vl.value = '';
            return;
        }

        if (daysInput > 30) {
            daysInput = 30;
            document.getElementById('days1').value = 30;
        }

        if (daysInput >= 1 && daysInput <= 30) {
            const equivalentValue = equivalences[daysInput - 1];
            sl.value = equivalentValue.toFixed(3);
            vl.value = equivalentValue.toFixed(3);
        } else {
            sl.value = '';
            vl.value = ''; 
        }
    }
</script>
<script>
    function redirectToLeaveRead(select) {
        var empId = select.value;
        if (empId) {
            window.location.href = '{{ route("leavesRead", ":id") }}'.replace(':id', empId);
        }
    }
</script>
<script>
    $(document).ready(function() {
        function enableVacationLeaveFields() {
            $('.vacation-check').prop('disabled', false);
            $('.vacation-leave').prop('readonly', false);
        }
        
        function disableVacationLeaveFields() {
            $('.vacation-check').prop('disabled', true);
            $('.vacation-leave').prop('readonly', true);
            $('.vacation-check').prop('checked', false);
            $('.vacation-leave').val('');
        }
    
        $('#vacation-leave').on('change', function() {
            if ($(this).is(':checked')) {
                enableVacationLeaveFields();
            }
        });
    
        $('input[name="leave_type"]').on('change', function() {
            if ($(this).val() != '1') {
                disableVacationLeaveFields();
            }
        });

        function enableStudyLeaveFields() {
            $('.leave-check').prop('disabled', false);
            $('.study-leave').prop('readonly', false);
        }
        
        function disableStudyLeaveFields() {
            $('.leave-check').prop('disabled', true);
            $('.study-leave').prop('readonly', true);
            $('.leave-check').prop('checked', false);
            $('.study-leave').val('');
        }
    
        $('#study-leave').on('change', function() {
            if ($(this).is(':checked')) {
                enableStudyLeaveFields();
            }
        });
    
        $('input[name="leave_type"]').on('change', function() {
            if ($(this).val() != '8') {
                disableStudyLeaveFields();
            }
        });

        function enableSickLeaveFields() {
            $('.sick-leave-detail').prop('disabled', false);
            $('.sick-leave').prop('readonly', false);
        }

        function disableSickLeaveFields() {
            $('.sick-leave-detail').prop('disabled', true);
            $('.sick-leave').prop('readonly', true);
            $('.sick-leave-detail').prop('checked', false);
            $('.sick-leave').val('');
        }

        $('#sick-leave').on('change', function() {
            if ($(this).is(':checked')) {
                enableSickLeaveFields();
            } else {
                disableSickLeaveFields();
            }
        });

        $('input[name="leave_type"]').on('change', function() {
            if ($(this).val() != '3') { 
                disableSickLeaveFields();
            } else {
                enableSickLeaveFields();
            }
        });
        
        function enablePurposeFields() {
            $('.purpose-detail').find('input').prop('disabled', false);
            $('.purpose-detail').find('input[type="text"]').prop('readonly', false);
            $('#monetizationdefault').prop('checked', true);
        }

        function disablePurposeFields() {
            $('.purpose-detail').find('input').prop('disabled', true);
            $('.purpose-detail').find('input[type="text"]').prop('readonly', true);
            $('.purpose-detail').find('input[type="radio"]').prop('checked', false);
            $('.purpose-detail').find('input[type="text"]').val('');
            $('#monetizationdefault').prop('checked', false);
        }

        function enableLeaveSpecificFields() {
        }

        $('input[name="leave_type"]').on('change', function() {
            const selectedLeaveType = $(this).val();
            if (selectedLeaveType == '1' || selectedLeaveType == '3' || selectedLeaveType == '8') {
                disablePurposeFields();
            } else {
                enablePurposeFields();
            }
        });

        $('input[name="leave_detail[]"]').on('input', function() {
            var currentInput = $(this);

            $('input[name="leave_detail[]"]').not(currentInput).val('');
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format

        // Initialize the flatpickr instance
        const flatpickrInstance = flatpickr("#date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: {{ request()->is('leave/history/*') ? 'null' : 'today' }}, // Default to today's date
            onChange: function(selectedDates) {
                calculateWeekdays(selectedDates);
            }
        });

        // Event listener for leave type selection
        document.querySelectorAll('input[name="leave_type"]').forEach(function(input) {
            input.addEventListener('change', function() {
                const selectedLeaveType = this.value;

                if (selectedLeaveType == '1' || selectedLeaveType == '8') {
                    // flatpickrInstance.set('minDate', today); // Disable previous dates
                    // flatpickrInstance.clear(); // Clear previous selection
                    flatpickrInstance.set('minDate', null); // Allow previous dates
                    flatpickrInstance.clear(); // Clear previous selection
                } else if (selectedLeaveType == '3') {
                    flatpickrInstance.set('minDate', null); // Allow previous dates
                    flatpickrInstance.clear(); // Clear previous selection
                } else {
                    // flatpickrInstance.set('minDate', today); // Disable previous dates
                    // flatpickrInstance.clear(); // Clear previous selection

                    flatpickrInstance.set('minDate', null); // Allow previous dates
                    flatpickrInstance.clear(); // Clear previous selection
                }
            });
        });
    });

    function calculateWeekdays(selectedDates) {
        const daysField = document.getElementById('day');

        if (selectedDates.length === 2) {
            const startDate = selectedDates[0];
            const endDate = selectedDates[1];

            const weekdayCount = countWeekdays(startDate, endDate);
            
            daysField.value = weekdayCount;
        } else {
            daysField.value = '';
        }
    }

    function countWeekdays(startDate, endDate) {
        let count = 0;
        let currentDate = new Date(startDate);

        while (currentDate <= endDate) {
            const dayOfWeek = currentDate.getDay();
            // Skip weekends (Saturday and Sunday)
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                count++;
            }
            currentDate.setDate(currentDate.getDate() + 1);
        }

        return count;
    }

</script>

<script>   
    $(document).on('click', '.leaves_delete', function(e){
        var id = $(this).val();
        var url = "{{ route('leavesDelete', ['id' => ':id', 'empid' => $employee->id]) }}";
        url = url.replace(':id', id);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });
    
        Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    type: "POST",
                    url: url,
                    success: function (response) {  
                        
                        $('#b-vl').html(response.vl);
                        $('#b-sl').html(response.sl);

                        $("#tr-"+id).fadeOut(2000);
                        Swal.fire({
                        title:'Deleted!',
                        text:'Your file has been deleted.',
                        type:'success',
                        icon: 'warning',
                        showConfirmButton: false,
                        timer: 1000
                        })
                    }
                });
            }
        })
    });  
</script>
<script>   
    $(document).on('click', '.leaves_delete', function(e){
        var id = $(this).val();
        var url = "{{ route('leavesDelete', ['id' => ':id', 'empid' => $employee->id]) }}";
        url = url.replace(':id', id);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });
    
        Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    type: "POST",
                    url: url,
                    success: function (response) {  
                        
                        $('#b-vl').html(response.vl);
                        $('#b-sl').html(response.sl);

                        $("#tr-"+id).fadeOut(2000);
                        Swal.fire({
                        title:'Deleted!',
                        text:'Your file has been deleted.',
                        type:'success',
                        icon: 'warning',
                        showConfirmButton: false,
                        timer: 1000
                        })
                    }
                });
            }
        })
    });  
</script>
<script>   
    $(document).on('click', '.leaves_edit', function(e){
        var id = $(this).data('id');
        var url = "{{ route('leavesEdit', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        $('#lcid').val(id);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });

        $.ajax({
            type: "POST",
            url: url,
            success: function (response) {  
                if(response.data){
                    
                    $('#days1').val(response.data.days);
                    $('#sl1').val(response.data.earn_sl);
                    $('#vl1').val(response.data.earn_vl);
                    $('#remarks1').val(response.data.remarks);
                    $('#date1').val(response.data.date);

                    if(response.data.stat == 0) {
                        $('#days1').prop('readonly', true);
                        $('#sl1').val(response.data.earn_sl).removeAttr('min').removeAttr('max').prop('readonly', false);
                        $('#vl1').val(response.data.earn_vl).removeAttr('min').removeAttr('max').prop('readonly', false);
                    } else {
                        $('#days1').prop('readonly', false); 
                        if(response.data.days == 0){
                            $('#lcid-ded').val(id);
                            $('#days1-ded').val(response.data.days);
                            $('#sl1-ded').val(response.data.earn_sl);
                            $('#vl1-ded').val(response.data.earn_vl);
                            $('#remarks1-ded').val(response.data.remarks);

                            $('#sl1').attr('min', 0).attr('max', 00);
                            $('#vl1').attr('min', 0).attr('max', 30);
                        }else{
                            $('#sl1').attr('min', 0).attr('max', 30).prop('readonly', true);
                            $('#vl1').attr('min', 0).attr('max', 30).prop('readonly', true);
                        }
                    }

                } else {
                    console.log("No leave credit found for this employee.");
                }
            },
        });
    });  
</script>
<script>
    $('.return-leave').on('click', function(){
        var id = $(this).data('id');
        var to = $(this).data('to');
        alert(to);
        var returnUrl = "{{ route('leaveReturn') }}";

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to return leave application?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, return it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: returnUrl,
                    type: 'POST',
                    data: {
                        id: id,
                        to: to,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if(to == 1){
                            $('#action-button' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                        }
                        if(to == 2){
                            $('#action-button1' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                        }
                        if(to == 3){
                            $('#action-button2' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                        }
                        Swal.fire(
                            'Returned!',
                            'Leave has been successfully returned.',
                            'success'
                        );
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while returning the leave.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
<script>
    $('.undo-leave').on('click', function(){
        var id = $(this).data('id');
        var to = $(this).data('to');

        var undoUrl = "{{ route('leaveUndo') }}";

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to undo leave application?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, undo it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: undoUrl,
                    type: 'POST',
                    data: {
                        id: id,
                        to: to,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Undo!',
                            'Leave has been successfully undone.',
                            'success'
                        );
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while undo the leave.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
<script>
    $('.day-wpay').on('click', function() {
        var id = $(this).data('id');
        var max = $(this).data('max');
        var approveUrl = "{{ route('leaveWpay') }}";
    
        Swal.fire({
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Submit',
            html: `
                <input type="number" id="days-without-pay" class="swal2-input" placeholder="Enter days without pay..." 
                    min="0" max="${max}" style="width: calc(85% - 16px);">
                <input type="number" id="holiday" class="swal2-input" placeholder="Enter holiday days..." 
                    min="0" style="width: calc(85% - 16px);">
            `,
            preConfirm: () => {
                var daysWithoutPay = document.getElementById('days-without-pay').value;
                var holiday = document.getElementById('holiday').value;
    
                if (!daysWithoutPay || daysWithoutPay < 0 || daysWithoutPay > max) {
                    Swal.showValidationMessage(`Please enter a valid number of days without pay (0-${max})`);
                    return false;
                }
                if (!holiday || holiday < 0) {
                    Swal.showValidationMessage(`Please enter a valid number of holiday days (0 or more)`);
                    return false;
                }
    
                return { daysWithoutPay, holiday };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('id', id);
                formData.append('day_wpay', result.value.daysWithoutPay);
                formData.append('holiday', result.value.holiday);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                
                $.ajax({
                    type: "POST",
                    url: approveUrl,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        // $('#days-wpay' + id).html(response.withpay);
                        // $('#days-withoutpay' + id).html(response.withoutpay);
                        Swal.fire({
                            title: 'Approved!',
                            text: 'The request has been approved.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                            // $('#action-button0' + id).fadeOut(1000, function() {
                            //     $(this).remove();
                            // });
                        });
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        if (xhr.status === 400 && response && response.error === 'Insufficient leave credits') {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Insufficient leave credits. Please check available credits.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        } else if (xhr.status === 422) {
                            Swal.fire({
                                title: 'Validation Error!',
                                text: 'Please check the entered data for any validation errors.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        } else if (xhr.status === 500) {
                            Swal.fire({
                                title: 'Server Error!',
                                text: 'An internal server error occurred. Please try again later.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while approving the leave.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        }
                    }
                });
            }
        });
    });
</script>
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
<script>
    // $('.approve-leave').on('click', function() {
    //     var id = $(this).data('id');
    //     var by = $(this).data('by');
    //     var approveUrl = "{{ route('leaveApprove') }}";
    //     var btnapp = (by == 0) ? 'Yes, Submit it!' : 'Yes, approve it!';
    //     var errortext = (by == 0) ? 'uploading' : 'approving';

    //     Swal.fire({
    //         title: 'Are you sure?',
    //         text: "You want to approve this request!",
    //         icon: 'warning',
    //         showCancelButton: true,
    //         confirmButtonColor: '#28a745',
    //         cancelButtonColor: '#d33',
    //         confirmButtonText: btnapp,
    //         html: `
    //             <input type="file" id="pdf-file" class="swal2-input" accept=".pdf" style="width: calc(85% - 16px);">
    //         `,
    //         preConfirm: () => {
    //             var file = document.getElementById('pdf-file').files[0];

    //             if (!file) {
    //                 Swal.showValidationMessage('Please attach the signed application form.');
    //                 return false;
    //             }

    //             return { file };
    //         }
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             // Show the loading spinner
    //             $('#loading-spinner').show();

    //             var formData = new FormData();
    //             formData.append('id', id);
    //             formData.append('by', by);
    //             formData.append('file', result.value.file);
    //             formData.append('_token', $('meta[name="csrf-token"]').attr('content'));  // Ensure CSRF token is added

    //             $.ajax({
    //                 type: "POST",
    //                 url: approveUrl,
    //                 data: formData,
    //                 contentType: false,
    //                 processData: false,
    //                 success: function(response) {
    //                     Swal.fire({
    //                         title: 'Approved!',
    //                         text: 'The request has been approved.',
    //                         icon: 'success',
    //                         showConfirmButton: false,
    //                         timer: 1000
    //                     });

    //                     // Handle actions based on 'by' value
    //                     if (by == 0) {
    //                         $('#action-button0' + id).fadeOut(1000, function() {
    //                             $(this).remove();
    //                         });
    //                     }
    //                     if (by == 1) {
    //                         $('#action-button' + id).fadeOut(1000, function() {
    //                             $(this).remove();
    //                         });
    //                         $('#status-icon' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
    //                         $('.time-sup' + id).html(response.datetime);
    //                     } else if (by == 2) {
    //                         $('#action-button1' + id).fadeOut(1000, function() {
    //                             $(this).remove();
    //                         });
    //                         $('#status-icon1' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
    //                         $('.time-hr' + id).html(response.datetime);
    //                     } else if (by == 3) {
    //                         $('#action-button2' + id).fadeOut(1000, function() {
    //                             $(this).remove();
    //                         });
    //                         $('#status-icon2' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
    //                         $('#status-icon3' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
    //                         $('.time-pres' + id).html(response.datetime);
    //                         $('#preview' + id).removeClass('bg-secondary').addClass('bg-danger');
    //                         $('#preview' + id).attr('href', "{{ route('previewLeave', ':id') }}".replace(':id', id));
    //                     }
    //                 },
    //                 error: function(xhr, status, error) {
    //                     var response = xhr.responseJSON;
    //                     if (xhr.status === 400 && response && response.error === 'Insufficient leave credits') {
    //                         Swal.fire({
    //                             title: 'Error!',
    //                             text: 'Insufficient leave credits. Please check available credits.',
    //                             icon: 'error',
    //                             showConfirmButton: true,
    //                         });
    //                     } else {
    //                         Swal.fire({
    //                             title: 'Error!',
    //                             text: 'An error occurred while '+ errortext +' the leave form.',
    //                             icon: 'error',
    //                             showConfirmButton: true,
    //                         });
    //                     }
    //                 },
    //                 complete: function() {
    //                     // Hide the loading spinner once the AJAX call is complete
    //                     $('#loading-spinner').hide();
    //                 }
    //             });
    //         }
    //     });
    // });

    $('.approve-leave-pres').on('click', function() {
        var id = $(this).data('id');
        var by = $(this).data('by');
        var approveUrl = "{{ route('leaveApprovePres') }}";

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you wish to formally sign this leave application?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, sign it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-spinner').show();

                var formData = new FormData();
                formData.append('id', id);
                formData.append('by', by);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                $.ajax({
                    type: "POST",
                    url: approveUrl,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.fire({
                            title: 'Approved!',
                            text: 'The request has been approved.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });

                        // Update UI for case 3
                        $('#action-button2' + id).fadeOut(1000, function() {
                            $(this).remove();
                        });
                        $('#status-icon2' + id).removeClass('fa-times bg-danger fa-times bg-secondary').addClass('fa-check bg-success');
                        $('#status-icon3' + id).removeClass('fa-times bg-danger fa-times bg-secondary').addClass('fa-check bg-success');
                        $('.time-pres' + id).html(response.datetime);
                        $('#preview' + id).removeClass('bg-secondary').addClass('bg-danger');
                        $('#preview' + id).attr('href', "{{ route('previewLeave', ':id') }}".replace(':id', id));
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : 'An error occurred while approving the leave form.',
                            icon: 'error',
                            showConfirmButton: true,
                        });
                    },
                    complete: function() {
                        $('#loading-spinner').hide();
                    }
                });
            }
        });
    });

    $('.bypass-leave').on('click', function() {
        var id = $(this).data('id');
        var by = $(this).data('by');
        var approveUrl = "{{ route('leaveApprove') }}";

        Swal.fire({
           title: 'Are you sure?',
            text: 'Do you want to forward this leave application to the Mayor / Vice Mayor?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm',
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-spinner').show();

                var formData = new FormData();
                formData.append('id', id);
                formData.append('by', by);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                $.ajax({
                    type: "POST",
                    url: approveUrl,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.fire({
                            title: (by == 0 ? 'Signed!' : 'Approved!'),
                            text: 'The request has been ' + (by == 0 ? 'signed' : 'approved') + '.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });

                        // DOM updates based on 'by'
                        if (by == 0) {
                            $('#action-button0' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                        }
                        if (by == 1) {
                            $('#action-button' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('.time-sup' + id).html(response.datetime);
                        } else if (by == 2) {
                            $('#action-button1' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon1' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('.time-hr' + id).html(response.datetime);
                        } else if (by == 3) {
                            $('#action-button2' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon2' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('#status-icon3' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('.time-pres' + id).html(response.datetime);
                            $('#preview' + id).removeClass('bg-secondary').addClass('bg-danger');
                            $('#preview' + id).attr('href', "{{ route('previewLeave', ':id') }}".replace(':id', id));
                        }
                    },
                    error: function(xhr, status, error) {
                        var response = xhr.responseJSON;
                        if (xhr.status === 400 && response && response.error === 'Insufficient leave credits') {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Insufficient leave credits. Please check available credits.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while ' + errortext + ' the leave form.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        }
                    },
                    complete: function() {
                        $('#loading-spinner').hide();
                    }
                });
            }
        });
    });

    $('.approve-leave').on('click', function() {
        var id = $(this).data('id');
        var by = $(this).data('by');
        var approveUrl = "{{ route('leaveApprove') }}";

        // var btnapp = (by == 0) ? 'Yes, sign it!' : 'Yes, approve it!';
        // var errortext = (by == 0) ? 'signing' : 'approving';
        // var actionText = (by == 0) ? 'Do you wish to formally sign this leave application?' : 'You want to approve this leave request';
        
        var btnapp = (by == 0 || by == 2 || by == 3) ? 'Yes, sign it!' : 'Yes, approve it!';
        var errortext = (by == 0 || by == 2 || by == 3) ? 'signing' : 'approving';
        var actionText = (by == 0 || by == 2 || by == 3) ? 'Do you wish to formally sign this leave application?' : 'You want to approve this leave request';

        Swal.fire({
            title: 'Are you sure?',
            text: actionText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: btnapp
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-spinner').show();

                var formData = new FormData();
                formData.append('id', id);
                formData.append('by', by);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                $.ajax({
                    type: "POST",
                    url: approveUrl,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.fire({
                            title: (by == 0 ? 'Signed!' : 'Approved!'),
                            text: 'The request has been ' + (by == 0 ? 'signed' : 'approved') + '.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });

                        // DOM updates based on 'by'
                        if (by == 0) {
                            $('#action-button0' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                        }
                        if (by == 1) {
                            $('#action-button' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('.time-sup' + id).html(response.datetime);
                        } else if (by == 2) {
                            $('#action-button1' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon1' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('.time-hr' + id).html(response.datetime);
                        } else if (by == 3) {
                            $('#action-button2' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon2' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('#status-icon3' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-check bg-success');
                            $('.time-pres' + id).html(response.datetime);
                            $('#preview' + id).removeClass('bg-secondary').addClass('bg-danger');
                            $('#preview' + id).attr('href', "{{ route('previewLeave', ':id') }}".replace(':id', id));
                        }
                    },
                    error: function(xhr, status, error) {
                        var response = xhr.responseJSON;
                        if (xhr.status === 400 && response && response.error === 'Insufficient leave credits') {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Insufficient leave credits. Please check available credits.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while ' + errortext + ' the leave form.',
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        }
                    },
                    complete: function() {
                        $('#loading-spinner').hide();
                    }
                });
            }
        });
    });

    $('.disapprove-leave').on('click', function() {
        var id = $(this).data('id');
        var by = $(this).data('by');
        var title = (by == 4) ? "Cancel" : "Disapprove";
        var text = (by == 4) ? "cancellation:" : "disapproval:";
        var disapproveUrl = "{{ route('leaveDisapprove') }}";

        Swal.fire({
            title: title +' Request',
            text: "Please provide your reason for " + text,
            input: 'textarea',
            inputPlaceholder: 'Enter your reason...',
            showCancelButton: true,
            confirmButtonText: 'Submit',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            inputValidator: (value) => {
                if (!value) {
                    return 'Reason are required!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                let remarks = result.value;

                $.ajax({
                    type: "POST",
                    url: disapproveUrl,
                    data: {
                        id: id,
                        by: by,
                        remarks: remarks,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Disapproved!',
                                text: 'Your disapproval has been submitted.',
                                icon: 'warning',
                                showConfirmButton: false,
                                timer: 1000
                            });

                            var remarksSupervisor = $('#status-remarks-supervisor' + id);
                            var remarksHrmo = $('#status-remarks-hrmo' + id);
                            var remarksPresedent = $('#status-remarks-presedent' + id);
                            switch (by) {
                                case 1:
                                    $('#action-button' + id).fadeOut(1000, function() { $(this).remove(); });
                                    $('#status-icon' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-ban bg-danger');
                                    if (remarksHrmo.length) {
                                        remarksHrmo.html(`
                                            <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                <p>${remarks}</p>
                                            </div>
                                        `);
                                    }
                                    break;
                                case 2:
                                    $('#action-button1' + id).fadeOut(1000, function() { $(this).remove(); });
                                    $('#status-icon1' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-ban bg-danger');
                                    if (remarksSupervisor.length) {
                                        remarksSupervisor.html(`
                                            <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                <p>${remarks}</p>
                                            </div>
                                        `);
                                    }
                                    break;
                                case 3:
                                    $('#action-button2' + id).fadeOut(1000, function() { $(this).remove(); });
                                    $('#status-icon2' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-ban bg-danger');
                                    if (remarksPresedent.length) {
                                        remarksPresedent.html(`
                                            <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                <p>${remarks}</p>
                                            </div>
                                        `);
                                    }
                                    break;
                                case 4:
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1500);
                                    break;
                            }

                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Something went wrong. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'Ok'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing your request.',
                            icon: 'error',
                            confirmButtonText: 'Ok'
                        });
                    }
                });
            }
        });
    });

</script>
@if(request()->is('leave'))
{{-- <script>
$(document).ready(function() {
    setInterval(function() {
        let leaveType = $("input[name='leave_type']:checked").val();
        if (leaveType && (leaveType == 1 || leaveType == 2)) {
            let day = parseFloat($('#day').val()),
                vl = parseFloat($('#b-vl').text());
            if (day > vl) {
                $('#date_range, #day').val('');
                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                    'positionClass': 'toast-bottom-center'
                };
                toastr.error("Insufficient Vacation Leave Credits");
            }
        }
    }, 500);
});
</script> --}}
@endif
@if(request()->is('leaves/') || request()->is('leave/status') || request()->is('leaves/status/*') || request()->is('leave/history*') || request()->is('leave/status/*'))
<script>
    $(document).on('click', '.cancelLeave', function (e) {
        var id = $(this).val();
        var url = "{{ route('cancelLeave', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: url,
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 2000
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Something went wrong. Please try again.',
                            icon: 'error',
                        });
                    }
                });
            }
        });
    });
</script>

<script>
    $(document).ready(function() {
        function updateLeaveInfo() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            });

            var url = "{{ request()->is('leave/status') || request()->is('leave/history') ? route('leaveLive') : (request()->is('leaves/*') || request()->is('leave/status/*') || request()->is('leave/history/*') ? route('leaveLive', $empid) : '') }}";
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response) {
                        $('#b-vl').text(response.vl ?? 0);
                        $('#b-sl').text(response.sl ?? 0);
                        $('#special-pl').text(response.special_pl ?? 0);
                        $('#solo-pl').text(response.solo_pl ?? 0);
                        $('#study-leave').text(response.study_leave ?? 0);
                        $('#vawc-leave').text(response.vawc_leave ?? 0);
                        $('#rehab-leave').text(response.rehab_leave ?? 0);
                        $('#benefits-leave').text(response.benefits_leave ?? 0);
                        $('#calamity-leave').text(response.calamity_leave ?? 0);
                        $('#adopt-leave').text(response.adopt_leave ?? 0);
                        $('#servcred-leave').text(response.servcred_leave ?? 0);
                    }
                }
            });
        }
        setInterval(updateLeaveInfo, 500);
    });
</script>
<script>
$(document).ready(function() {
    $('#pdfModalHistory').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var leaveId = button.data('id');
        $.ajax({
            url: "{{ route('getPdfPath') }}",
            type: 'POST',
            data: {
                id: leaveId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.path) {
                    var fullPath = "{{ url('/') }}" + response.path;
                    $('#pdfIframeHistory').attr('src', fullPath);
                } else {
                    console.error('PDF path not found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading PDF:', error);
                $('#pdfIframeHistory').attr('src', '');
            }
        });
    });

    $('#pdfModalHistory').on('hidden.bs.modal', function() {
        $('#pdfIframeHistory').attr('src', '');
    });
});

$(document).ready(function() {
    $('#pdfModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var leaveId = button.data('id');

        var baseUrl = "{{ route('previewLeave', ['id' => '__ID__']) }}";
        var previewUrl = baseUrl.replace('__ID__', leaveId);

        $('#pdfIframe').attr('src', previewUrl);
    });
    
    $('#pdfModal').on('hidden.bs.modal', function() {
        $('#pdfIframe').attr('src', '');
    });
});
</script>
    
@endif
