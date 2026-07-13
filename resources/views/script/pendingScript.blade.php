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
                        var fullPath = "{{ url('/') }}" + response.path;
                        $('#pdfIframe').attr('src', fullPath);
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format

        // Initialize the flatpickr instance
        const flatpickrInstance = flatpickr("#date_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: null, // Allow previous dates
            onChange: function(selectedDates) {
            calculateWeekdays(selectedDates);
            }
        });

    });

</script>
<script>
$(document).ready(function() {
    $('#pdfModalPending').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var leaveId = button.data('id');
        var urlTemplate = button.data('url-template');

        var previewUrl = urlTemplate.replace('__ID__', leaveId);

        $('#pdfIframe').attr('src', previewUrl);
    });

    $('#pdfModalPending').on('hidden.bs.modal', function() {
        $('#pdfIframe').attr('src', '');
    });
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

</script>