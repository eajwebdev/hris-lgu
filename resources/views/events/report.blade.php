@extends('layouts.master')

@section('body')
<div class="container-fluid">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <h4 class="font-weight-bold mb-0"><i class="fas fa-file-pdf mr-1"></i> Event Attendance Reports</h4>
    <a href="{{ route('eventIndex') }}" class="btn btn-secondary"><i class="fas fa-calendar mr-1"></i> Calendar</a>
  </div>

  <div class="card card-primary">
            <form class="form-horizontal add-form p-2" action="{{ route('searchReport') }}" method="POST">
                @csrf
                <div class="form-group row mtop">
                    {{-- Events --}}
                    <div class="col-md-3 col-sm-12">
                        <label class="badge badge-secondary lbel">Events</label><br>
                        <select class="form-control form-control-sm select2" name="eventid" required>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}"
                                    @if(isset($selectedEvent) && $selectedEvent && $event->id == $selectedEvent->id) selected @endif>
                                    {{ ucfirst($event->title) }}
                                </option>
                            @endforeach
                        </select>                                    
                    </div>
            
            
                    {{-- Employee Status --}}
                    <div class="col-md-3 col-sm-12">
                        <label class="badge badge-secondary lbel">Employee Status</label><br>
                        <select class="form-control form-control-sm select2 update-field" name="statusid" required>
                            <option value="0" @if(isset($statusid) && $statusid == 0) selected @endif>All</option>
                            @foreach ($status as $st)
                                <option value="{{ $st->id }}" @if(isset($statusid) && $statusid == $st->id) selected @endif>
                                    {{ $st->status_name }}
                                </option>
                            @endforeach
                        </select>                                    
                    </div>
            
                    {{-- Submit Button --}}
                    <div class="col-md-3 col-sm-12">
                        <button class="btn btn-success btn-sm btn-block">
                            <i class="fas fa-file-pdf"></i> Generate
                        </button>                              
                    </div>
                </div>
            </form>
            
            {{-- Iframe --}}
            <iframe class="m-2"
                src="{{ isset($eventid, $statusid) ? route('reportGenrate', ['eventid' => $eventid, 'statusid' => $statusid]) : '' }}"
                width="98.5%" height="800px">
            </iframe>

    </div>
</div><!-- /.container-fluid -->
            
@endsection