<div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Create Event</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="eventForm" action="{{ route('eventCreate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->guard($guard)->user()->id }}">
                    <div class="form-group">
                        <label for="eventTitle">Event Title</label>
                        <input type="text" class="form-control" id="eventTitle" name="title">
                    </div>
                    <div class="form-group">
                        <label for="eventStartTime">Start Time</label>
                        <input type="datetime-local" class="form-control" id="eventStartTime" name="start">
                    </div>
                    <div class="form-group">
                        <label for="eventEndTime">End Time</label>
                        <input type="datetime-local" class="form-control" id="eventEndTime" name="end">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-success">Save Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
{{-- <div class="modal fade" id="EditeventModal{{ $data->id }}" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="eventModalLabel">Edit Event</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <div class="modal-body" id="editEvent">
    <form id="eventForm" action="{{ route('eventUpdate') }}" method="POST">
                        @csrf
    <input type="hidden" name="id" value="{{ $data->id }}"> 
    <div class="form-group">
    <label for="eventTitle">Event Title</label>
    <input type="text" class="form-control" id="eventTitle" name="title"  value="{{ $data->title }}">
    </div>
    <div class="form-group">
    <label for="eventStartTime">Start Time</label>
    <input type="datetime-local" class="form-control" id="eventStartTime" name="start" value="{{ $data->start }}">
    </div>
    <div class="form-group">
    <label for="eventEndTime">End Time</label>
    <input type="datetime-local" class="form-control" id="eventEndTime" name="end" value="{{ $data->end }}">
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    <button type="submit" class="btn bg-success">Update</button>
    </div>
    </form>
    </div>
    </div>
    </div>
    </div> --}}
