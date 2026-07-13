<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPSU EMPLOYEE LIST</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            background-color: #ffffff;
        }
        thead {
            background-color: #343a40;
            color: white;
        }
        th, td {
            padding: 6px;
            border: 1px solid #dee2e6;
        }
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tbody tr:hover {
            background-color: #e9ecef;
        }
        th {
            text-transform: uppercase;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>NAME</th>
                <th>OFFICE</th>
                <th>POSITION</th>
                <th>EMP. STATUS</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
            <tr>
                <td>{{ $emp->lname }} {{ $emp->fname }}</td>
                <td>{{ $emp->office_name }}</td>
                <td>{{ $emp->position }}</td>
                <td>{{ $emp->status_name }}</td>
                <td>{{ $emp->org_email }}</td>
            </tr>
            @endforeach 
        </tbody>
    </table>
</div>

</body>
</html>
