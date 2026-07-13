
<script>   
$(document).on('click', '.employee_delete', function(e){
    var id = $(this).val();
    var url = "{{ route('empDelete', ['id' => ':id']) }}";
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
