@extends('layouts.master')

@section('body')
<style>
    .bg-white {
        border-radius: 15px;
    }
    .icon{
        position: absolute;
        top: 37px !important;
        right: 5px;
    }
    .border-radius{
      border-radius: 8px !important;
      width: 40px !important;
      height: 40px !important;
    } 
</style>
@if($guard == 'web')
  @include('home.modal')
@endif
@if($guard == 'employee')
@php
    $profileUrl = asset('Profile/Employee/' . $employee->profile);
    $profilePath = public_path('Profile/Employee/' . $employee->profile);
    $profileImage = file_exists($profilePath) && $employee->profile ? $profileUrl : asset('Profile/Employee/default.png');
    $fullName = trim(ucwords(strtolower($employee->fname . ' ' . $employee->lname)));
    $todayLabel = now('Asia/Manila')->format('F j, Y');
@endphp
<style>
    .employee-dashboard {
        color: #20312b;
    }
    .employee-hero {
        background: linear-gradient(135deg, #f7fbf8 0%, #e7f3ec 48%, #fff7df 100%);
        border: 1px solid rgba(24, 119, 68, .12);
        border-radius: 8px;
        padding: 22px;
        margin-bottom: 18px;
    }
    .employee-avatar {
        width: 78px;
        height: 78px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,.08);
    }
    .metric-card,
    .action-card {
        background: #fff;
        border: 1px solid #e7ece9;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(31,49,43,.05);
        min-height: 112px;
    }
    .metric-card .icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eaf7f0;
        color: #187744;
    }
    .quick-action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-radius: 8px;
        color: #20312b;
        border: 1px solid #e7ece9;
        transition: all .15s ease;
    }
    .quick-action:hover {
        color: #187744;
        border-color: rgba(24, 119, 68, .35);
        background: #f7fbf8;
    }
    .quick-action i {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff4d3;
        color: #8b6b00;
    }
    .dashboard-table td {
        vertical-align: middle;
    }
    .punch-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .punch-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #f4f7f5;
        border: 1px solid #e2e9e5;
        font-size: 12px;
        white-space: nowrap;
    }
    .punch-pill strong {
        color: #187744;
    }
    .punch-pill.out strong {
        color: #9a5b00;
    }
    .punch-pill.ot strong {
        color: #6d4cc2;
    }
    .session-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(66px, 1fr));
        gap: 6px;
    }
    .session-cell {
        background: #f9fbfa;
        border: 1px solid #e2e9e5;
        border-radius: 8px;
        padding: 6px;
        text-align: center;
        min-height: 52px;
    }
    .session-cell span {
        display: block;
        color: #738078;
        font-size: 11px;
        text-transform: uppercase;
    }
    .session-cell strong {
        display: block;
        color: #20312b;
        font-size: 13px;
        margin-top: 2px;
    }
    .official-hours-note {
        color: #738078;
        font-size: 12px;
        line-height: 1.4;
    }
    .date-filter {
        background: #fff;
        border: 1px solid #e7ece9;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 18px;
        box-shadow: 0 8px 20px rgba(31,49,43,.04);
    }
    .date-filter .date-shell {
        position: relative;
    }
    .date-filter .date-shell i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #187744;
        z-index: 2;
    }
    .date-filter .date-input {
        height: 42px;
        border-radius: 8px;
        border-color: #dfe8e3;
        padding-left: 42px;
        background: #f9fbfa;
        cursor: pointer;
    }
</style>
<div class="container-fluid employee-dashboard">
    <div class="employee-hero">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex align-items-center mb-3 mb-md-0">
                <img src="{{ $profileImage }}" class="employee-avatar mr-3" alt="Profile Image">
                <div>
                    <div class="text-muted text-sm">{{ $todayLabel }}</div>
                    <h3 class="mb-1 font-weight-bold">Welcome, {{ $fullName }}</h3>
                    <div class="text-muted">
                        {{ $employee->position ?: 'Employee' }}
                        @if($employee->emp_ID)
                            <span class="mx-2">|</span>{{ $employee->emp_ID }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="date-filter">
        <div class="row align-items-end">
            <div class="col-12">
                <label class="text-muted mb-1" for="dashboard_date_range">Date Range</label>
                <div class="date-shell">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="text" id="dashboard_date_range" class="form-control date-input" value="{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}" readonly>
                </div>
                <input type="hidden" id="date_from" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" id="date_to" name="date_to" value="{{ $dateTo }}">
            </div>
        </div>
    </form>

    <section class="content">
        <div class="row">
            @if($isRegularEmployee)
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Leave Records</div>
                            <h4 class="mb-0">{{ number_format($leaveCount) }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-calendar-check"></i></span>
                    </div>
                    <small class="text-muted">Total applications filed</small>
                </div>
            </div>
            @endif
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Total Late</div>
                            <h4 class="mb-0">{{ $totalLate }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-business-time"></i></span>
                    </div>
                    <small class="text-muted">For selected range</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Total Undertime</div>
                            <h4 class="mb-0">{{ $totalUndertime }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-hourglass-half"></i></span>
                    </div>
                    <small class="text-muted">For selected range</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Service</div>
                            <h4 class="mb-0">{{ is_null($serviceYears) ? '--' : $serviceYears . ' yr' . ($serviceYears == 1 ? '' : 's') }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-id-badge"></i></span>
                    </div>
                    <small class="text-muted">{{ $employee->date_hired ? 'Since ' . \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : 'Date hired not set' }}</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="card-title font-weight-bold">Events</h3>
                    </div>
                    <div class="card-body" style="background-color: #f4f7f5;">
                        <div id="external-events"></div>
                        <div id="calendar" class="bg-white"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="card-title font-weight-bold">Recent DTR</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm dashboard-table mb-0">
                            <tbody>
                                @forelse($recentDtrs as $dtr)
                                    <tr>
                                        <td>
                                            <strong>{{ \Carbon\Carbon::parse($dtr->date)->format('M d') }}</strong>
                                            <div class="official-hours-note">
                                                AM {{ $dtr->official_schedule['am'] }}<br>
                                                PM {{ $dtr->official_schedule['pm'] }}
                                            </div>
                                        </td>
                                        <td colspan="2">
                                            <div class="session-grid">
                                                <div class="session-cell">
                                                    <span>AM In</span>
                                                    <strong>{{ $dtr->daily_punches['am_in'] ?: '--' }}</strong>
                                                </div>
                                                <div class="session-cell">
                                                    <span>AM Out</span>
                                                    <strong>{{ $dtr->daily_punches['am_out'] ?: '--' }}</strong>
                                                </div>
                                                <div class="session-cell">
                                                    <span>PM In</span>
                                                    <strong>{{ $dtr->daily_punches['pm_in'] ?: '--' }}</strong>
                                                </div>
                                                <div class="session-cell">
                                                    <span>PM Out</span>
                                                    <strong>{{ $dtr->daily_punches['pm_out'] ?: '--' }}</strong>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted p-3">No DTR records yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="action-card p-3 mb-3">
                    <h5 class="font-weight-bold mb-3">Quick Actions</h5>
                    <a class="quick-action mb-2" href="{{ route('empPDS') }}">
                        <i class="fas fa-clipboard"></i>
                        <span>Open PDS</span>
                    </a>
                    @if($isRegularEmployee)
                        <a class="quick-action mb-2" href="{{ route('leavesReadEmp') }}">
                            <i class="fas fa-calendar-plus"></i>
                            <span>File or Check Leave</span>
                        </a>
                    @endif
                    <a class="quick-action" href="{{ route('dtr-read') }}">
                        <i class="fas fa-clock"></i>
                        <span>View DTR</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
@else
<div class="container-fluid">
    <div class="wrapper">
        <section class="content">
            <div class="row">
                <div class="col-lg-8 col-sm-12">
                  <div class="row">
                    <div class="col-12">
                      <div class="row">
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-gray">Employee</h6>
                                      <h3 class="">{{ number_format($totalEmployees) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fa-solid fa-user-tie" style="color: #9E9E9E; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Present</h6>
                                      <h3>{{ number_format($dtrCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fa-solid fa-users-viewfinder" style="color: #607D8B; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Absent</h6>
                                      <h3>{{ number_format($totalEmployees - $dtrCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-users-viewfinder" style="color: #FF7043; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          
                          <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <a href="{{ route('readPending', 1) }}"> 
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Leave Application</h6>
                                      <h3>{{ number_format($leaveappCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-file-alt"  style="color: #9575CD;  font-size: 30px !important;"></i>
                                  </div>
                              </div>
                            </a>
                          </div>

                          <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <a href="{{ route('readPending', 2) }}">
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Eligibility</h6>
                                      <h3>{{ number_format($eliCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-award"  style="color: #FFEB3B;  font-size: 30px !important;"></i>
                                  </div>
                              </div>
                            </a>
                          </div>

                          <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <a href="{{ route('readPending', 3) }}">
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Working experience</h6>
                                      <h3>{{ number_format($workexpCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-tools"  style="color: #FF5722;  font-size: 30px !important;"></i>
                                  </div>
                              </div>
                            </a>
                          </div>

                          <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <a href="{{ route('readPending', 5) }}">
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Learning & Development</h6>
                                      <h3>{{ number_format($learDevCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-book"  style="color: #7986CB;  font-size: 30px !important;"></i>
                                  </div>
                              </div>
                            </a>
                          </div>

                          <div class="col-lg-3 col-6">
                            <!-- small box -->
                            <a href="{{ route('readPending', 4) }}">
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Voluntary works</h6>
                                      <h3>{{ number_format($volWorkCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-hands-helping"  style="color: #388E3C;  font-size: 30px !important;"></i>
                                  </div>
                              </div>
                            </a>
                          </div>
                      </div>
                    </div>
                    <div class="col-12">
                        <div class="card  p-0">
                            <div class="card-body" style="background-color: #e9ecef;">
                                <div id="external-events">
  
                                </div>
                                <div id="calendar" class="bg-white"></div>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>

                <div class="col-lg-4 col-sm-6">
                  {{-- <div class="row">
                    <div class="col-12">
                        <div class="row">
                          <div class="col-12">
                            <div class="card">
                              <div class="card-header">
                                <h3 class="card-title"><b>Male/Female</b></h3>
                              </div>
                              <!-- /.card-header -->
                              <div class="card-body p-0">
    
                              </div>
                            </div>
                          </div>
                        </div>
                    </div> --}}
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><b>Employee Status</b></h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body p-0">
                          <table class="table table-sm">
                            <tbody>
                              <tr>
                                  <td>Regular</td>
                                  <td width="100">
                                      <div class="progress progress-xs mt-2">
                                          <div class="progress-bar bg-danger" style="width: {{ number_format($empStatusPercentages->get(1)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-danger">
                                          {{ number_format($empStatusPercentages->get(1)['percentage'], 2) . '%' }} 
                                      </span>  
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(1)['count'] }}</span>
                                  </td>
                              </tr>
                              <tr>
                                  <td>Full-time / Part-time</td>
                                  <td width="100">
                                      <div class="progress progress-xs mt-2">
                                          <div class="progress-bar bg-warning" style="width: {{ number_format($empStatusPercentages->get(2)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-warning">
                                          {{ number_format($empStatusPercentages->get(2)['percentage'], 2) . '%' }}
                                      </span>  
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(2)['count'] }}</span>
                                  </td>
                              </tr>
                              <tr>
                                  <td>Part-time / Part-time</td>
                                  <td width="100">
                                      <div class="progress progress-xs mt-2 progress-striped active">
                                          <div class="progress-bar bg-primary" style="width: {{ number_format($empStatusPercentages->get(3)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-primary">
                                          {{ number_format($empStatusPercentages->get(3)['percentage'], 2) . '%' }}
                                      </span>
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(3)['count'] }}</span> 
                                  </td>
                              </tr>
                              <tr>
                                  <td>Job Order</td>
                                  <td width="100"> 
                                      <div class="progress progress-xs mt-2 progress-striped active">
                                          <div class="progress-bar bg-success" style="width: {{ number_format($empStatusPercentages->get(4)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-success">
                                          {{ number_format($empStatusPercentages->get(4)['percentage'], 2) . '%' }}  
                                      </span>  
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(4)['count'] }}</span>
                                  </td>
                              </tr>
                          </tbody>
                          
                          </table>
                        </div>
                        <!-- /.card-body -->
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><b>Birthday</b></h3>
                          <div class="card-tools">
                            {{-- <input type="month" class="form-control form-control-sm" style="width: auto; display: inline-block;" id="monthInput"> --}}
                          </div>
                        </div>
                        <div class="card-body p-0">
                          <ul class="products-list product-list-in-card pl-2 pr-2">
                            @foreach($upcomingBirthdays as $employee)
                              <li class="item">
                                <div class="product-img">
                                    @php
                                        $imageUrl = asset('Profile/Employee/' . $employee->profile);
                                        $imagePath = public_path('Profile/Employee/' . $employee->profile);
                                    @endphp
                                    <img class="border-radius" src="{{ file_exists($imagePath) ? $imageUrl : asset('Profile/Employee/default.png') }}" alt="Product Image">
                                </div>
                                <div class="product-info">
                                  <a href="#" class="product-title text-dark">{{ ucfirst(strtolower($employee->lname)) . ' ' . ucfirst(strtolower($employee->fname)) }}
                                    @php
                                        $birthday = Carbon\Carbon::parse($employee->bdate);
                                    @endphp
                                    
                                    <span class="float-right" style="margin-top: -2px;">
                                        @if ($employee->bdate->format('F j') == now('Asia/Manila')->format('F j'))
                                            <i class="fas fa-birthday-cake" style="color: #e71515;"></i>
                                        @endif
                                    </span>
                                  </a>
                                  <span class="product-description">
                                    {{ $employee->office_abbr }} <span class="float-right" style="margin-top: -2px;">{{ $employee->bdate->format('F j, Y') }}</span>
                                  </span>
                                </div>
                              </li>
                            @endforeach
                          </ul>
                        </div>
                      </div>
                    </div>                    
                  </div>
                </div>
                
            </div>
        </section>
    </div>
</div>
@endif
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
@if($guard == 'employee')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !jQuery.fn.daterangepicker) {
            return;
        }

        const rangeInput = $('#dashboard_date_range');
        const dateFrom = $('#date_from');
        const dateTo = $('#date_to');

        rangeInput.daterangepicker({
            startDate: moment(dateFrom.val(), 'YYYY-MM-DD'),
            endDate: moment(dateTo.val(), 'YYYY-MM-DD'),
            autoUpdateInput: true,
            locale: {
                format: 'MMM D, YYYY',
                separator: ' - '
            },
            ranges: {
                'This Week': [moment().startOf('week'), moment().endOf('week')],
                'Today': [moment(), moment()],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function (start, end) {
            dateFrom.val(start.format('YYYY-MM-DD'));
            dateTo.val(end.format('YYYY-MM-DD'));
            rangeInput.closest('form').trigger('submit');
        });
    });
</script>
@endif
{{-- <script>
  // Get the current date
  const currentDate = new Date();
  
  // Get the current month and year
  const currentMonth = currentDate.getMonth() + 1; // Months are zero-indexed
  const currentYear = currentDate.getFullYear();
  
  // Format the month to always have two digits
  const formattedMonth = currentMonth < 10 ? '0' + currentMonth : currentMonth;
  
  // Set the value of the input to the current month and year
  document.getElementById('monthInput').value = `${currentYear}-${formattedMonth}`;
  
  // Disable the year selection
  document.getElementById('monthInput').addEventListener('click', function() {
      this.showPicker = () => {};
  });
</script> --}}
@endsection
