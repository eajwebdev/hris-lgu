<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Employee QR</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .qr-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 30px;
            width: 100%;
            page-break-inside: avoid;
        }
        .emp-block {
            width: 25%; /* 100% / 4 */
            box-sizing: border-box;
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
        }
        .qr-code { margin-bottom: 10px; }
        .emp-lname { font-weight: bold; font-size: 12px; }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .qr-row {
                page-break-inside: avoid;
            }
            .emp-block {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @foreach($employees->chunk(4) as $row)
        <div class="qr-row">
            @foreach($row as $employee)
                <div class="emp-block">
                    <div class="qr-code" id="qrcode-{{ $employee->emp_ID }}"></div>
                    <div class="emp-lname">{{ $employee->lname }} {{ $employee->fname }}</div>
                </div>
            @endforeach
        </div>
    @endforeach

    <!-- Include QRCode.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        const employees = [
            @foreach($employees as $employee)
                {
                    id: "{{ $employee->emp_ID }}",
                    token: "{{ shortEncrypt($employee->emp_ID) }}"
                },
            @endforeach
        ];

        window.onload = function() {
            employees.forEach(emp => {
                const qrElement = document.getElementById('qrcode-' + emp.id);
                if (qrElement) {
                    qrElement.innerHTML = "";
                    new QRCode(qrElement, {
                        text: emp.token,
                        width: 150,
                        height: 150,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            });
        };
    </script>
</body>
</html>
