@php $curr_route = request()->route()->getName(); @endphp

@if($curr_route == "dashboard")
<script>
$(function () {
    var pieChartCanvas = $('#pieChart').get(0).getContext('2d')
    var empReg = $('#pieChart').data('reg');
    var empJo = $('#pieChart').data('jo');
    var empPt = $('#pieChart').data('pt');
    var empPtPt = $('#pieChart').data('ptpt');
    var pieData = {
        labels: [
            'Regular',
            'Job Order',
            'Part-Time',
            'Part-Time/Part-Time',
        ],
        datasets: [
            {
                data: [empReg, empJo, empPt, empPtPt],
                backgroundColor: ['#04a45c', '#3c8cbc', '#f46c54', '#f49c14'],
            }
        ]
    }
    var pieOptions = {
        legend: {
            display: false
        }
    }
    var pieChart = new Chart(pieChartCanvas, {
        type: 'pie',
        data: pieData,
        options: pieOptions
    });

    @php
        // An LGU has no campuses: employees are broken down by office instead.
        // Ids 1 and 2 are the ALL OFFICES / ALL EMPLOYEES broadcast targets.
        $chartOffices = collect($offCount ?? [])->whereNotIn('id', [1, 2])->values();
    @endphp

    var barChartData = {
        labels: [@foreach($chartOffices as $off)'{{ $off->office_abbr }}',@endforeach],
        datasets: [
            {
                label: 'Permanent',
                backgroundColor: '#04a45c',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [@foreach($chartOffices as $off){{ $chartEmployee->where('emp_status', 1)->where('emp_dept', $off->id)->count() }},@endforeach],
            },
            {
                label: 'Casual',
                backgroundColor: '#3c8cbc',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [@foreach($chartOffices as $off){{ $chartEmployee->where('emp_status', 2)->where('emp_dept', $off->id)->count() }},@endforeach],
            },
            {
                label: 'Job Order',
                backgroundColor: '#f46c54',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [@foreach($chartOffices as $off){{ $chartEmployee->where('emp_status', 6)->where('emp_dept', $off->id)->count() }},@endforeach],
            },
            {
                label: 'Part-time/JO',
                backgroundColor: '#f49c14',
                borderColor: '#FFFF',
                borderWidth: 1,
                data: [@foreach($chartOffices as $off){{ $chartEmployee->where('emp_status', 7)->where('emp_dept', $off->id)->count() }},@endforeach],
            },
        ],
    };

    var stackedBarChartCanvas = $('#stackedBarChart').get(0).getContext('2d');
    var stackedBarChartData = $.extend(true, {}, barChartData);

    var stackedBarChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            xAxes: [{
                stacked: true,
            }],
            yAxes: [{
                stacked: true,
            }],
        },
    };

    new Chart(stackedBarChartCanvas, {
        type: 'bar',
        data: stackedBarChartData,
        options: stackedBarChartOptions,
    });
});
</script>
@endif