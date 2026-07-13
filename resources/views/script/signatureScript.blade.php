<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    $('#signature-preview').on('click', function () {
        $('#signature-file').click();
    });

    $('#signature-file').on('change', function () {
        const file = $('#signature-file')[0].files[0];

        if (!file || file.type !== 'image/png') {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Only PNG files are allowed.'
            });
            $('#signature-file').val('');
            return;
        }

        let formData = new FormData();
        formData.append('signature', file);

        $.ajax({
            url: "{{ route('uploadSignature', $employee->id) }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $('#signature-preview').attr('src', response.image_url);
                    Swal.fire({
                        icon: 'success',
                        title: 'Signature Updated',
                        text: 'Your signature was uploaded successfully.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: response.message
                    });
                }
            },
            error: function (xhr) {
                let response = xhr.responseJSON;

                if (xhr.status === 422 && response.errors) {
                    let allErrors = Object.values(response.errors).flat().join('\n');
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: allErrors
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: response?.message || 'An unexpected error occurred.'
                    });
                }
            }
        });
    });
});
</script>

