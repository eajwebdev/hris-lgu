<script>
     //Delete
    $(document).on('click', '.users-delete', function(e){
        var id = $(this).val();
        
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
                    url: "{{ route('uDelete') }}",
                    data: { id: id },
                    success: function (response) {  
                        if (response.status === 200) {
                            $("#tr-"+response.id).fadeOut(2000, function () {
                                $(this).remove();
                            });
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Your file has been deleted.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1000
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'An error occurred.',
                                icon: 'error',
                                showConfirmButton: true
                            });
                        }
                    }
                });
            }
        })
    });
</script>