<script>
    function OfficialTime(empid) {
        $.ajax({
            url: '{{ route("OfficialTimeRead", ["empid" => ":empid"]) }}'.replace(':empid', empid),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    // Populate Monday fields
                    $('input[name="empid"]').val(empid);
                    $('input[name="mon_mornin"]').val(data.mon_mornin);
                    $('input[name="mon_mornout"]').val(data.mon_mornout);
                    $('input[name="mon_noonin"]').val(data.mon_noonin);
                    $('input[name="mon_noonout"]').val(data.mon_noonout);

                    // Populate Tuesday fields
                    $('input[name="tue_mornin"]').val(data.tue_mornin);
                    $('input[name="tue_mornout"]').val(data.tue_mornout);
                    $('input[name="tue_noonin"]').val(data.tue_noonin);
                    $('input[name="tue_noonout"]').val(data.tue_noonout);

                    // Populate Wednesday fields
                    $('input[name="wed_mornin"]').val(data.wed_mornin);
                    $('input[name="wed_mornout"]').val(data.wed_mornout);
                    $('input[name="wed_noonin"]').val(data.wed_noonin);
                    $('input[name="wed_noonout"]').val(data.wed_noonout);

                    // Populate Thursday fields
                    $('input[name="thu_mornin"]').val(data.thu_mornin);
                    $('input[name="thu_mornout"]').val(data.thu_mornout);
                    $('input[name="thu_noonin"]').val(data.thu_noonin);
                    $('input[name="thu_noonout"]').val(data.thu_noonout);

                    // Populate Friday fields
                    $('input[name="fri_mornin"]').val(data.fri_mornin);
                    $('input[name="fri_mornout"]').val(data.fri_mornout);
                    $('input[name="fri_noonin"]').val(data.fri_noonin);
                    $('input[name="fri_noonout"]').val(data.fri_noonout);
                } else {
                    alert(response.message || "Error fetching data.");
                }
            },
            error: function () {
                alert("An error occurred while fetching employee data.");
            }
        });
    }
</script>
<script>
    function toggleStat(value, empId){
        $.ajax({
            url: '{{ route("toggleAcctStat") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: empId,
                stat_1: value ? 1 : 2
            },
            success: function(response) {
                if (response.success) {
                    // alert('User role updated successfully.');
                } else {
                    // alert('Failed to update user role.');
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
            }
        });
    }
</script>
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