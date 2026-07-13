<script>
    function formatNumber(input) {
        let value = input.value.replace(/,/g, '');
        if (!isNaN(value) && value !== '') {
            input.value = Number(value).toLocaleString();
        }
    }

    function isNumberKey(evt) {
        let charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode != 46 && (charCode < 48 || charCode > 57)) {
            evt.preventDefault();
        }
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="table_search"]');
        const tableRows = document.querySelectorAll('.table tbody');
    
        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
    
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const found = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchTerm));
                row.style.display = found ? '' : 'none';
            });
        });
    });
</script>
<script>
    $(document).on('click', '.workexperience_delete', function(e){
        var id = $(this).val();
        var url = "{{ route('workDelete', ['id' => ':id']) }}";
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
                        $(".workexperience-row.row-" + id).fadeOut(2000);
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

    $(document).on('click', '.workexperience_approve', function(e) {
        var id = $(this).val();
        var url = "{{ route('expApprove', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to approve this work experience!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, approve!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: url,
                    success: function(response) {
                        Swal.fire({
                            title: 'Approved!',
                            text: 'The work experience has been approved.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        
                        $("#status-" + id)
                        .text("Reviewed") 
                        .removeClass("badge-warning")
                        .addClass("badge-success"); 
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while approving.',
                            icon: 'error',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                });
            }
        });
    });
</script>