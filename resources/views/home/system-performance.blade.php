@extends('layouts.master')
@section('body')
<div class="container">
    <h3 class="mb-3">📈 System Performance Dashboard (Real-time)</h3>

    <div class="row g-2 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="from">From</label>
            <input id="from" type="datetime-local" name="from" value="{{ request('from') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="to">To</label>
            <input id="to" type="datetime-local" name="to" value="{{ request('to') }}" class="form-control">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <canvas id="requestChart"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="queryChart"></canvas>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let requestChart, queryChart;

    function fetchPerformanceData() {
        const from = document.getElementById('from').value;
        const to = document.getElementById('to').value;

        // Build URL with params
        let url = new URL("{{ route('systemPerformance') }}", window.location.origin);
        if (from) url.searchParams.append('from', from);
        if (to) url.searchParams.append('to', to);

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateCharts(data.requests, data.queries);
            })
            .catch(err => console.error(err));
    }

    function updateCharts(requests, queries) {
        const requestLabels = requests.map(r => r.url);
        const requestData = requests.map(r => r.time);

        const queryLabels = queries.map((_, i) => i + 1);
        const queryData = queries;

        // Create or update Request chart
        const ctx1 = document.getElementById('requestChart').getContext('2d');
        if (requestChart) {
            requestChart.data.labels = requestLabels;
            requestChart.data.datasets[0].data = requestData;
            requestChart.update();
        } else {
            requestChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: requestLabels,
                    datasets: [{
                        label: 'Request Duration (ms)',
                        data: requestData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)'
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        }

        // Create or update Query chart
        const ctx2 = document.getElementById('queryChart').getContext('2d');
        if (queryChart) {
            queryChart.data.labels = queryLabels;
            queryChart.data.datasets[0].data = queryData;
            queryChart.update();
        } else {
            queryChart = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: queryLabels,
                    datasets: [{
                        label: 'Slow Query Time (ms)',
                        data: queryData,
                        backgroundColor: 'rgba(255, 99, 132, 0.4)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        }
    }

    // Auto-fetch data every 15 seconds
    setInterval(fetchPerformanceData, 15000);

    // Fetch data on page load
    fetchPerformanceData();

    // Fetch data when filters change (debounced)
    let debounceTimeout;
    document.getElementById('from').addEventListener('change', () => {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(fetchPerformanceData, 1000);
    });
    document.getElementById('to').addEventListener('change', () => {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(fetchPerformanceData, 1000);
    });
</script>
@endsection
