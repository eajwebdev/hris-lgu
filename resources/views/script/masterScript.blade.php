
<script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/chart.js/Chart.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/jquery-ui/jquery-ui.min.js') }}"></script>

<script defer src="{{ asset('template/plugins/moment/moment.min.js') }}"></script>
<script defer src="{{ asset('template/plugins/fullcalendar/main.js') }}"></script>

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

{{-- @include('script.dashboardChart') --}}
{{-- Notification --}}
<script>
  function showColor(element) {
    var color = element.getAttribute('data-color');

    document.getElementById('bg_color').value = color;

    document.getElementById('submit-bg').className = 'btn ' + color + ' btn-sm';
  }
</script>
@if(request()->is('event*') || request()->is('dashboard'))
<script>
  $(function () {
    function ini_events(ele) {
      ele.each(function () {
        var eventObject = {
          title: $.trim($(this).text())
        }

        $(this).data('eventObject', eventObject)

        $(this).draggable({
          zIndex: 1070,
          revert: true,
          revertDuration: 0
        })
      })
    }

    ini_events($('#external-events div.external-event'))

    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('calendar');

    new Draggable(containerEl, {
      itemSelector: '.external-event',
      eventData: function (eventEl) {
        return {
          title: eventEl.innerText,
          backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
          borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
          textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
        };
      }
    });

    // Editing (click to edit / drag to reschedule) is only enabled on the manage
    // page where the edit modal exists; the dashboard calendar stays read-only.
    var canManage = !!document.getElementById('editEventModal');

    function csrfToken() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }

    function pad2(n) { return String(n).padStart(2, '0'); }

    function toLocalInput(d) {
      if (!d) return '';
      var dt = new Date(d);
      return dt.getFullYear() + '-' + pad2(dt.getMonth() + 1) + '-' + pad2(dt.getDate()) +
        'T' + pad2(dt.getHours()) + ':' + pad2(dt.getMinutes());
    }

    function toServerDateTime(d) {
      if (!d) return '';
      var dt = new Date(d);
      return dt.getFullYear() + '-' + pad2(dt.getMonth() + 1) + '-' + pad2(dt.getDate()) +
        ' ' + pad2(dt.getHours()) + ':' + pad2(dt.getMinutes()) + ':00';
    }

    function eventColorHex(cls) {
      switch (cls) {
        case 'bg-primary':   return '#007bff';
        case 'bg-info':      return '#17a2b8';
        case 'bg-warning':   return '#ffc107';
        case 'bg-success':   return '#28a745';
        case 'bg-danger':    return '#dc3545';
        case 'bg-secondary': return '#6c757d';
        default:             return '#0073b7';
      }
    }

    // Populate and open the edit modal from a clicked calendar event.
    window.openEditEvent = function (ev) {
      var modal = document.getElementById('editEventModal');
      if (!modal || !window.jQuery) return;
      var p = ev.extendedProps || {};
      var color = p.bgColor || 'bg-primary';

      $('#edit_event_id').val(p.eventId || ev.id || '');
      $('#edit_title').val(ev.title || '');
      $('#edit_venue').val(p.venue || '');
      $('#edit_org_dept').val(p.orgDept || '');
      $('#edit_start').val(toLocalInput(ev.start));
      $('#edit_end').val(ev.end ? toLocalInput(ev.end) : '');
      $('#edit_bg_color').val(color);
      $('#editEventColor .color-swatch').removeClass('selected');
      $('#editEventColor .color-swatch[data-color="' + color + '"]').addClass('selected');
      $('#deleteEventBtn').attr('data-id', p.eventId || ev.id || '');
      $(modal).modal('show');
    };

    // Open the create modal pre-filled with the clicked/dragged date range
    // (and a title when a preset is dragged onto the calendar).
    window.openCreateEvent = function (start, end, title) {
      var modal = document.getElementById('createEventModal');
      if (!modal || !window.jQuery) return;
      $('#create_title').val(title || '');
      $('#create_start').val(toLocalInput(start));
      $('#create_end').val(end ? toLocalInput(end) : '');
      $(modal).modal('show');
    };

    // Persist a drag-to-reschedule / resize without leaving the calendar.
    function persistEventDates(ev) {
      var p = ev.extendedProps || {};
      var fd = new FormData();
      fd.append('id', p.eventId || ev.id);
      fd.append('title', ev.title || '');
      fd.append('venue', p.venue || '');
      fd.append('org_dept', p.orgDept || '');
      fd.append('bg_color', p.bgColor || 'bg-primary');
      fd.append('start', toServerDateTime(ev.start));
      fd.append('end', ev.end ? toServerDateTime(ev.end) : '');

      fetch("{{ route('eventUpdateSave') }}", {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
        body: fd
      })
        .then(function (r) { return r.json(); })
        .then(function () { if (window.toastr) toastr.success('Event rescheduled.'); })
        .catch(function () { if (window.toastr) toastr.error('Failed to reschedule event.'); });
    }

    var calendar = new Calendar(calendarEl, {
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
      },
      themeSystem: 'bootstrap',
      navLinks: true,
      nowIndicator: true,
      dayMaxEvents: true,
      // Fit the calendar within the viewport so the whole month is visible with
      // minimal page scrolling; rows compress and overflow scrolls internally.
      height: Math.max(480, window.innerHeight - 170),
      expandRows: true,

      events: function (fetchInfo, successCallback, failureCallback) {
        fetch("{{ route('eventJson') }}")
          .then(response => response.json())
          .then(data => {
            const events = data.map(event => {
              const startDate = new Date(event.start);
              const hours = startDate.getHours();
              const minutes = startDate.getMinutes().toString().padStart(2, '0');
              const ampm = hours >= 12 ? 'PM' : 'AM';
              const hour12 = hours % 12 || 12;
              const timeFormatted = `${hour12}:${minutes} ${ampm} `;

              const isSingle = !event.end;
              const color = eventColorHex(event.bg_color);

              return {
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end || null,
                allDay: false,
                backgroundColor: color,
                borderColor: color,
                extendedProps: {
                  eventId: event.id,
                  venue: event.venue,
                  orgDept: event.org_dept,
                  empStatus: event.emp_status,
                  bgColor: event.bg_color,
                  timeLabel: isSingle ? timeFormatted : null,
                }
              };
            });
            successCallback(events);
          })
          .catch(error => {
            console.error('Error loading events:', error);
            failureCallback(error);
          });
      },

      eventContent: function (arg) {
        const event = arg.event;
        const time = event.extendedProps.timeLabel;
        const title = event.title;
        const eventColor = event.backgroundColor || '#0073b7';  // Default to blue if no color is set

        let html = '';

        // Add the colored dot at the start of the title
        html += `<span class="fc-event-dot" style="width: 10px; height: 10px; background-color: ${eventColor}; border-radius: 50%; margin-right: 5px;"></span>`;

        if (time) {
          html += `<div class="fc-time-label">${time}</div>`;
        }
        html += `<div class="fc-event-title">${title}</div>`;

        return { html };
      },

      eventClick: function (info) {
        info.jsEvent.preventDefault();
        if (canManage) {
          window.openEditEvent(info.event);
        }
      },

      // Click a day / slot or drag across a range to create a new event.
      selectable: canManage,
      selectMirror: true,
      select: function (info) {
        if (!canManage) return;
        window._pendingDropEvent = null;
        var start = info.start;
        var end = info.end;

        // FullCalendar's all-day end is exclusive; make it inclusive for the form.
        if (info.allDay && end) {
          end = new Date(end.getTime() - 86400000);
          if (end <= start) end = null;
        }

        window.openCreateEvent(start, end);
        calendar.unselect();
      },

      editable: canManage,
      eventDrop: function (info) { persistEventDates(info.event); },
      eventResize: function (info) { persistEventDates(info.event); },

      droppable: true,
      // A dragged-in preset becomes a temporary event and immediately opens the
      // create modal pre-filled with its title + dropped date. If the user cancels,
      // the temporary event is removed from the calendar (see hidden.bs.modal below).
      eventReceive: function (info) {
        window._pendingDropEvent = info.event;
        window.openCreateEvent(info.event.start, null, info.event.title);
      }
    });

    calendar.render();

    // Keep the calendar fitted to the window as it resizes.
    window.addEventListener('resize', function () {
      calendar.setOption('height', Math.max(480, window.innerHeight - 170));
    });

    // Remove a dragged-in preset from the calendar if its create modal is cancelled;
    // keep it when the form is actually submitted (the page reloads and refetches).
    if (window.jQuery) {
      var $createModal = window.jQuery('#createEventModal');
      $createModal.on('hidden.bs.modal', function () {
        if (window._pendingDropEvent) {
          window._pendingDropEvent.remove();
          window._pendingDropEvent = null;
        }
      });
      $createModal.find('form').on('submit', function () {
        window._pendingDropEvent = null;
      });
    }

    var currColor = '#3c8dbc';

    $('#color-chooser > li > a').click(function (e) {
      e.preventDefault()
      currColor = $(this).css('color')
      $('#add-new-event').css({
        'background-color': currColor,
        'border-color': currColor
      })
    })

    $('#add-new-event').click(function (e) {
      e.preventDefault()
      var val = $('#new-event').val()
      if (val.length == 0) {
        return
      }

      var event = $('<div />')
      event.css({
        'background-color': currColor,
        'border-color': currColor,
        'color': '#fff'
      }).addClass('external-event')
      event.text(val)
      $('#external-events').prepend(event)

      ini_events(event)
      $('#new-event').val('')
    })
  })
</script>
@endif
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

        $("#leaveHistory").DataTable({
            "responsive": false,
            "lengthChange": false, 
            "autoWidth": true,
            order: [[1, 'desc']],
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('.col-md-6:eq(0)');

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
<script>
$(document).ready(function() {

    let offset = 10;   // first 10 already loaded in the blade
    let loading = false;
    let stopLoading = false;

    function loadMoreNotifications() {

        if (loading || stopLoading) return;

        loading = true;

        $.ajax({
            url: '{{ route("notificationload") }}',
            type: "GET",
            data: { offset: offset },
            beforeSend: function() {
                loading = true;
            }
        })
        .done(function(data) {

            // No more notifications
            if (data.stop === true || data.html === "") {
                stopLoading = true;
                loading = false;
                return;
            }

            // Append new notifications
            $('#notifications-container').append(data.html);

            // Increase offset by 10
            offset = data.nextOffset;

            loading = false;
        })
        .fail(function() {
            console.log("Error loading notifications");
            loading = false;
        });
    }

    // Infinite Scroll inside the dropdown
    $('.dropdown-menu').on('scroll', function() {

        let menu = $(this);

        if (menu.scrollTop() + menu.innerHeight() >= this.scrollHeight - 5) {
            loadMoreNotifications();
        }

    });

});
</script>
<script>
$(function() {
  $("input[data-bootstrap-switch]").each(function() {
    $(this).bootstrapSwitch('state', $(this).prop('checked'));
  });
});
</script>
<script>
$(function () {
    $('#dateRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(
            picker.startDate.format('YYYY-MM-DD') +
            ' to ' +
            picker.endDate.format('YYYY-MM-DD')
        );
    });

    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
});
</script>

