
<script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/chart.js/Chart.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/jquery-ui/jquery-ui.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/moment/moment.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/jszip/jszip.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<!-- daterangepicker -->
<link rel="stylesheet" href="{{ asset('template/plugins/daterangepicker/daterangepicker.css') }}">
<script src="{{ asset('template/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('template/plugins/daterangepicker/daterangepicker.js') }}"></script>

<script defer src="{{ asset('app.js') }}"></script>

<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });

    $(function () {
        @if(Session::has('error'))
        toastr.options = {
                "closeButton":true,
                "progressBar":true,
                'positionClass': 'toast-bottom-right'
            }
            toastr.error("{{ session('error') }}")
        @endif
        
        @if(Session::has('error1'))
            toastr.options = {
                "closeButton":true,
                "progressBar":true,
                'positionClass': 'toast-bottom-center'
            }
            toastr.error("{{ session('error1') }}")
        @endif

        @if(Session::has('success'))
            toastr.options = {
                "closeButton":true,
                "progressBar":true,
                'positionClass': 'toast-bottom-right'
            }
            toastr.success("{{ session('success') }}")
        @endif

        @if($errors->any())
                var errorMessage = "";
                @foreach($errors->all() as $error)
                    errorMessage += "{{ $error }}" + "<br>";
                @endforeach
                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                    "positionClass": "toast-bottom-right"
                };
                toastr.error(errorMessage);
        @endif

        $("#example1").DataTable({
            "responsive": false,
            "lengthChange": false, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        // $("#example1").DataTable({
        //     "responsive": false,
        //     "lengthChange": false, // Removes the "Show Entries" dropdown
        //     "autoWidth": true,
        //     "searching": true, // Hides the search input
        //     "paging": true, // Enables pagination
        //     "dom": '<"top">rt<"bottom"p><"clear">', // Pagination only at the bottom
        //     "pageLength": 10, // Sets the number of rows per page to 9
        //     //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        // }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example2").DataTable({
            "responsive": false,
            "lengthChange": true, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example3").DataTable({
            "responsive": false,
            "lengthChange": false, 
            "autoWidth": false,
            // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');

        $('.select2').select2()
    });
   
</script>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>

<script>
    $(document).ready(function() {
        let rowCount = 3;

        $('#addRow').click(function() {
            let newRow = `
                <div class="form-group col-md-8 row${rowCount}">
                    <input type="text" name="mfo[]" class="form-control form-control-sm" placeholder="Enter MFO" required>
                </div>
                <div class="form-group col-md-3 row${rowCount}">
                    <input type="number" name="percent[]" class="form-control form-control-sm" placeholder="Percent" required>
                </div>
                <div class="form-group col-md-1 row${rowCount}">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRow('row${rowCount}')"><i class="fas fa-times"></i></button>
                </div>
            `;
            $('#newrow').append(newRow);
            rowCount++;
        });
    });

    function deleteRow(rowClass) {
        $('.' + rowClass).remove();
    }
</script>
@if(request()->is('pds/work-experience*') || request()->is('pds/voluntary-work*') || request()->is('pds/learning-dev*') || request()->is('dtr/dtr-logs*'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const incDate1 = document.getElementById('inc_date1');
        const incDate2 = document.getElementById('inc_date2');

        function updateDate2MinDate() {
            const date1Value = incDate1.value;
            if (date1Value) {
                const minDate = new Date(date1Value).toISOString().split('T')[0];
                incDate2.setAttribute('min', minDate);
                incDate2.value = '';
                if (incDate2.value && new Date(incDate2.value) < new Date(minDate)) {
                    incDate2.value = '';
                } else {

                }
            } else {
                incDate2.removeAttribute('min');
            }
        }

        function validateDateRange() {
            const date1Value = incDate1.value;
            const date2Value = incDate2.value;

            if (date1Value && date2Value) {
                if (new Date(date1Value) > new Date(date2Value)) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }

        incDate1.addEventListener('change', function() {
            updateDate2MinDate();
            validateDateRange();
        });

        incDate2.addEventListener('change', validateDateRange);
    });
</script>
@endif
