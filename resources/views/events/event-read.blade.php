@extends('layouts.master')

@section('body')
<div class="container-fluid">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
      <h4 class="font-weight-bold mb-0"><i class="fas fa-calendar-alt mr-1"></i> Events Calendar</h4>
      <small class="text-muted"><i class="fas fa-hand-pointer mr-1"></i> Click or drag on the calendar to add an event &bull; click an event to edit &bull; drag to reschedule</small>
    </div>
    <a href="{{ route('showReport') }}" class="btn btn-secondary"><i class="fas fa-file-pdf mr-1"></i> Reports</a>
  </div>

  <div class="card card-primary">
    <div class="card-body p-0">
      <div id="external-events">
            <div class="px-3 pt-2 text-muted small"><i class="fas fa-grip-vertical mr-1"></i> Drag a preset onto a date to create an event:</div>
            <div class="row m-2">
              <div class="col-md-2 col-4">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-primary"></i> Academic Council</div>
              </div>
              <div class="col-md-2 col-4">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-info"></i> Admin Council</div>
              </div>
              <div class="col-md-2 col-4">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-warning"></i> Convocation</div>
              </div>
              <div class="col-md-2 col-4">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-success"></i> Trainings &amp; Seminar</div>
              </div>
              <div class="col-md-2 col-4">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-danger"></i> Orientation</div>
              </div>
              <div class="col-md-2 col-4">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-secondary"></i> Meeting</div>
              </div>
            </div>
            <hr class="mt-1 mb-0">
          </div>
      <div id="calendar" class="p-2"></div>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div><!-- /.container-fluid -->

<!-- Create Event Modal (opened by clicking / dragging the calendar) -->
<div class="modal fade" id="createEventModal" tabindex="-1" role="dialog" aria-labelledby="createEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form class="modal-content" method="POST" action="{{ route('eventCreate') }}">
      @csrf
      <input type="hidden" name="bg_color" id="create_bg_color" value="bg-primary">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="createEventModalLabel"><i class="fas fa-plus mr-1"></i> New Event</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label>Event Title</label>
          <input type="text" class="form-control form-control-sm" name="title" id="create_title" required>
        </div>
        <div class="form-group">
          <label>Venue</label>
          <input type="text" class="form-control form-control-sm" name="venue" required>
        </div>
        <div class="form-group">
          <label>Organizing Department/s</label>
          <input type="text" class="form-control form-control-sm" name="org_dept" required>
        </div>
        <div class="form-row">
          <div class="form-group col-6">
          </div>
          <div class="form-group col-6">
            <label>Employee Status</label>
            <select class="form-control form-control-sm" name="emp_status" required>
              <option value="0">All</option>
              @foreach ($status as $st)
                <option value="{{ $st->id }}">{{ $st->status_name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-6">
            <label>Start Time</label>
            <input type="datetime-local" class="form-control form-control-sm" name="start" id="create_start" required>
          </div>
          <div class="form-group col-6">
            <label>End Time</label>
            <input type="datetime-local" class="form-control form-control-sm" name="end" id="create_end">
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="d-block">Color</label>
          <ul class="fc-color-picker mb-0 color-picker" id="createEventColor" data-target="create_bg_color">
            <li><a href="#" class="text-primary color-swatch selected" data-color="bg-primary"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-info color-swatch" data-color="bg-info"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-warning color-swatch" data-color="bg-warning"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-success color-swatch" data-color="bg-success"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-danger color-swatch" data-color="bg-danger"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-secondary color-swatch" data-color="bg-secondary"><i class="fas fa-square"></i></a></li>
          </ul>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light border btn-sm" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Save Event</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit / Delete Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" role="dialog" aria-labelledby="editEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form class="modal-content" method="POST" action="{{ route('eventUpdateSave') }}">
      @csrf
      <input type="hidden" name="id" id="edit_event_id">
      <input type="hidden" name="bg_color" id="edit_bg_color" value="bg-primary">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="editEventModalLabel"><i class="fas fa-edit mr-1"></i> Manage Event</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label>Event Title</label>
          <input type="text" class="form-control form-control-sm" name="title" id="edit_title" required>
        </div>
        <div class="form-group">
          <label>Venue</label>
          <input type="text" class="form-control form-control-sm" name="venue" id="edit_venue" required>
        </div>
        <div class="form-group">
          <label>Organizing Department/s</label>
          <input type="text" class="form-control form-control-sm" name="org_dept" id="edit_org_dept" required>
        </div>
        <div class="form-row">
          <div class="form-group col-6">
            <label>Start Time</label>
            <input type="datetime-local" class="form-control form-control-sm" name="start" id="edit_start" required>
          </div>
          <div class="form-group col-6">
            <label>End Time</label>
            <input type="datetime-local" class="form-control form-control-sm" name="end" id="edit_end">
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="d-block">Color</label>
          <ul class="fc-color-picker mb-0 color-picker" id="editEventColor" data-target="edit_bg_color">
            <li><a href="#" class="text-primary color-swatch" data-color="bg-primary"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-info color-swatch" data-color="bg-info"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-warning color-swatch" data-color="bg-warning"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-success color-swatch" data-color="bg-success"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-danger color-swatch" data-color="bg-danger"><i class="fas fa-square"></i></a></li>
            <li><a href="#" class="text-secondary color-swatch" data-color="bg-secondary"><i class="fas fa-square"></i></a></li>
          </ul>
        </div>
      </div>

      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-danger btn-sm" id="deleteEventBtn" data-id="">
          <i class="fas fa-trash"></i> Delete
        </button>
        <div>
          <button type="button" class="btn btn-light border btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
  .color-picker { display:flex; gap:6px; list-style:none; padding:0; }
  .color-picker .color-swatch { font-size:1.4rem; line-height:1; display:inline-block; opacity:.55; transition:.12s ease; }
  .color-picker .color-swatch:hover { opacity:.85; transform:translateY(-1px); }
  .color-picker .color-swatch.selected { opacity:1; transform:scale(1.15); }
</style>

<script>
  (function () {
    var deleteUrlBase = "{{ url('event/delete') }}";
    var csrf = document.querySelector('meta[name="csrf-token"]');
    csrf = csrf ? csrf.getAttribute('content') : '';

    document.addEventListener('click', function (e) {
      var swatch = e.target.closest('.color-picker .color-swatch');
      if (swatch) {
        e.preventDefault();
        var picker = swatch.closest('.color-picker');
        var targetId = picker && picker.getAttribute('data-target');
        if (targetId && document.getElementById(targetId)) {
          document.getElementById(targetId).value = swatch.getAttribute('data-color');
        }
        picker.querySelectorAll('.color-swatch').forEach(function (s) { s.classList.remove('selected'); });
        swatch.classList.add('selected');
      }
    });

    var deleteBtn = document.getElementById('deleteEventBtn');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        var id = this.getAttribute('data-id');
        if (!id) return;

        var doDelete = function () {
          fetch(deleteUrlBase + '/' + id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data.status === 200) {
                window.location.reload();
              } else if (window.toastr) {
                toastr.error(data.message || 'Failed to delete event.');
              }
            })
            .catch(function () { if (window.toastr) toastr.error('Failed to delete event.'); });
        };

        if (window.Swal) {
          Swal.fire({
            title: 'Delete this event?',
            text: 'This will remove the event and its attendance logs. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it'
          }).then(function (result) { if (result.isConfirmed) doDelete(); });
        } else if (window.confirm('Delete this event and its attendance logs?')) {
          doDelete();
        }
      });
    }
  })();
</script>

@endsection