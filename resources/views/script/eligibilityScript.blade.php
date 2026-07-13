<script>   
    $(document).on('click', '.eligible_delete', function(e){
        var id = $(this).val();
        var url = "{{ route('eliDelete', ['id' => ':id']) }}";
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
                        $(".eligibility-row.row-" + id).fadeOut(2000);
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

    $(document).on('click', '.eligible_approve', function(e) {
        var id = $(this).val();
        var url = "{{ route('eliApprove', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to approve this eligibility!",
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
                            text: 'The eligibility has been approved.',
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