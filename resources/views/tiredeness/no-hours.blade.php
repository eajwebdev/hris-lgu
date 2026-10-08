{{-- Shown in the preview frame on the Tardiness screen when a report is asked
     for an employee whose official working hours have not been set. It stands
     alone inside an <iframe>, so it carries its own few styles. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Official working hours not set</title>
    <style>
        html, body { height: 100%; margin: 0; }
        body {
            display: grid;
            place-items: center;
            padding: 0 1.5rem;
            font-family: "Instrument Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #14201A;
            background: #fff;
            text-align: center;
        }
        h1 { margin: 0; font-size: 1.15rem; }
        p { margin: .5rem auto 0; max-width: 30rem; line-height: 1.5; color: #5b655f; }
    </style>
</head>
<body>
    <div>
        <h1>This employee's official working hours are not set</h1>
        <p>
            Tardiness and undertime are measured against them. Set the hours for
            Monday to Friday from Employees, using the clock button on the
            employee's row, then generate this report again.
        </p>
    </div>
</body>
</html>
