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
        }

        function disablePurposeFields() {
            $('.purpose-detail').find('input').prop('disabled', true);
            $('.purpose-detail').find('input[type="text"]').prop('readonly', true);
            $('.purpose-detail').find('input[type="radio"]').prop('checked', false);
            $('.purpose-detail').find('input[type="text"]').val('');
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
            minDate: today, // Default to today's date
            onChange: function(selectedDates) {
                calculateWeekdays(selectedDates);
            }
        });

        // Event listener for leave type selection
        document.querySelectorAll('input[name="leave_type"]').forEach(function(input) {
            input.addEventListener('change', function() {
                const selectedLeaveType = this.value;

                if (selectedLeaveType == '1' || selectedLeaveType == '8') {
                    flatpickrInstance.set('minDate', today); // Disable previous dates
                    flatpickrInstance.clear(); // Clear previous selection
                } else if (selectedLeaveType == '3') {
                    flatpickrInstance.set('minDate', null); // Allow previous dates
                    flatpickrInstance.clear(); // Clear previous selection
                } else {
                    flatpickrInstance.set('minDate', today); // Disable previous dates
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
                        $('#sl1').attr('min', 0).attr('max', 30).prop('readonly', true);
                        $('#vl1').attr('min', 0).attr('max', 30).prop('readonly', true);
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
            `,
            preConfirm: () => {
                var daysWithoutPay = document.getElementById('days-without-pay').value;
    
                if (!daysWithoutPay || daysWithoutPay < 0 || daysWithoutPay > max) {
                    Swal.showValidationMessage(`Please enter a valid number of days (0-${max})`);
                    return false;
                }
    
                return { daysWithoutPay };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('id', id);
                formData.append('day_wpay', result.value.daysWithoutPay);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                
                $.ajax({
                    type: "POST",
                    url: approveUrl,
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#days-wpay' + id).html(response.withpay);
                        $('#days-withoutpay' + id).html(response.withoutpay);
                        Swal.fire({
                            title: 'Approved!',
                            text: 'The request has been approved.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        $('#action-button0' + id).fadeOut(1000, function() {
                            $(this).remove();
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
    
<script>
    $('.approve-leave').on('click', function() {
        var id = $(this).data('id');
        var by = $(this).data('by');
        var approveUrl = "{{ route('leaveApprove') }}";
        var btnapp = (by == 0) ? 'Yes, Submit it!' : 'Yes, approve it!';

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to approve this request!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: btnapp,
            html: `
                <input type="file" id="pdf-file" class="swal2-input" accept=".pdf" style="width: calc(85% - 16px);">
            `,
            preConfirm: () => {
                var file = document.getElementById('pdf-file').files[0];

                if (!file) {
                    Swal.showValidationMessage('Please attach the signed application form.');
                    return false;
                }

                return { file };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('id', id);
                formData.append('by', by);
                formData.append('file', result.value.file);
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
                        if (by == 0) {
                            $('#action-button0' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                        }
                        if (by == 1) {
                            // Supervisor Approval
                            $('#action-button' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
                            $('.time-sup' + id).html(response.datetime);
                        } else if (by == 2) {
                            // HR Approval
                            $('#action-button1' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon1' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
                            $('.time-hr' + id).html(response.datetime);
                        } else if (by == 3) {
                            // Final Approval
                            $('#action-button2' + id).fadeOut(1000, function() {
                                $(this).remove();
                            });
                            $('#status-icon2' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
                            $('#status-icon3' + id).removeClass('fa-times bg-danger').removeClass('fa-times bg-secondary').addClass('fa-check bg-success');
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

    $('.disapprove-leave').on('click', function() {
        var id = $(this).data('id');
        var by = $(this).data('by');
        var disapproveUrl = "{{ route('leaveDisapprove') }}";

        Swal.fire({
            title: 'Disapprove Request',
            text: "Please provide your reason for disapproval:",
            input: 'textarea',
            inputPlaceholder: 'Enter your remarks...',
            showCancelButton: true,
            confirmButtonText: 'Submit',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            inputValidator: (value) => {
                if (!value) {
                    return 'Remarks are required!';
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
                                    if (remarksSupervisor.length) {
                                        remarksSupervisor.html(`
                                            <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                <p>${remarks}</p>
                                            </div>
                                        `);
                                    }
                                    break;
                                case 2:
                                    $('#action-button1' + id).fadeOut(1000, function() { $(this).remove(); });
                                    $('#status-icon1' + id).removeClass('fa-times bg-danger bg-secondary').addClass('fa-ban bg-danger');
                                    if (remarksHrmo.length) {
                                        remarksHrmo.html(`
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
<script>
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

</script>

@endif
@if(request()->is('leave/status') || request()->is('leaves/status/*') || request()->is('leave/history*') || request()->is('leave/status/*'))
<script>
    $(document).ready(function() {
        function updateLeaveInfo() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            });

            var url = "{{ request()->is('leave/status') || request()->is('leave/history') ? route('leaveLive') : (request()->is('leave/status/*') || request()->is('leave/history/*') ? route('leaveLive', $empid) : '') }}";
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response) {
                        $('#b-vl').text(response.vl);
                        $('#b-sl').text(response.sl);
                    }
                }
            });
        }
        setInterval(updateLeaveInfo, 500);
    });
</script>
<script>
    $(document).ready(function() {
        $('#pdfModal').on('show.bs.modal', function(event) {
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
                        $('#pdfIframe').attr('src', response.path);
                    } else {
                        console.error('PDF path not found');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading PDF:', error);
                    $('#pdfIframe').attr('src', '');
                }
            });
        });

        $('#pdfModal').on('hidden.bs.modal', function() {
            $('#pdfIframe').attr('src', '');
        });
    });
</script>
    
@endif
