<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee QR Cards | HRIS - LGU Mabinay</title>
    <link rel="shortcut icon" href="{{ asset('mabinay-logo.png') }}">
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            background: #F1F5F9;
            font-family: "Inter", Arial, Helvetica, sans-serif;
            color: #0F172A;
        }

        .sheet-header {
            max-width: 1000px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sheet-header img { width: 46px; height: 46px; object-fit: contain; }
        .sheet-header h1 { margin: 0; font-size: 17px; letter-spacing: -.01em; }
        .sheet-header p { margin: 2px 0 0; font-size: 12px; color: #64748B; }
        .sheet-header .print {
            margin-left: auto;
            padding: 8px 16px;
            border: 0;
            border-radius: 8px;
            background: #1E7A45;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .cards {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: flex-start;
        }

        /* The card matches the one shown in the employee's PDS */
        .employee-card {
            width: 300px;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #E5E7EB;
            box-shadow: 0 10px 24px -12px rgba(15, 23, 42, .25);
            text-align: center;
            page-break-inside: avoid;
        }

        .employee-card__header {
            background: linear-gradient(135deg, #1E7A45 0%, #10502C 100%);
            padding: 14px 12px 12px;
            color: #fff;
            position: relative;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .employee-card__header::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 4px;
            background: linear-gradient(90deg, #EF9017, #FBBF24, #EF9017);
        }
        .employee-card__seal {
            width: 54px; height: 54px;
            object-fit: contain;
            border-radius: 50%;
            background: #fff;
            padding: 3px;
        }
        .employee-card__org {
            margin: 7px 0 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }
        .employee-card__sub {
            margin: 2px 0 0;
            font-size: 8.5px;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: rgba(255,255,255,.78);
        }

        .employee-card__qr { padding: 16px 16px 10px; }
        .employee-card__qr .qr-code {
            display: inline-block;
            padding: 10px;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            line-height: 0;
        }
        .employee-card__qr .qr-code img,
        .employee-card__qr .qr-code canvas { display: block; }

        .employee-card__scan {
            margin: 8px 0 0;
            font-size: 8.5px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94A3B8;
        }

        .employee-card__body { padding: 4px 16px 16px; }
        .employee-card__name {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.25;
        }
        .employee-card__position {
            margin: 3px 0 10px;
            font-size: 11px;
            color: #64748B;
            min-height: 15px;
        }
        .employee-card__id {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            background: #FEF3E2;
            color: #B26205;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .employee-card__footer {
            padding: 7px 12px;
            background: #F1F5F9;
            border-top: 1px solid #E5E7EB;
            font-size: 8px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #94A3B8;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet-header { display: none; }
            .cards { gap: 10px; max-width: none; }
            .employee-card { box-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="sheet-header">
        <img src="{{ asset('mabinay-logo.png') }}" alt="Municipality of Mabinay Official Seal">
        <div>
            <h1>Employee QR Cards</h1>
            <p>{{ $employees->count() }} active {{ Str::plural('employee', $employees->count()) }} &middot; Municipality of Mabinay</p>
        </div>
        <button class="print" onclick="window.print()">Print</button>
    </div>

    <div class="cards">
        @foreach($employees as $employee)
            <div class="employee-card">
                <div class="employee-card__header">
                    <img src="{{ asset('mabinay-logo.png') }}" alt="Seal" class="employee-card__seal">
                    <p class="employee-card__org">Municipality of Mabinay</p>
                    <p class="employee-card__sub">Human Resource Information System</p>
                </div>

                <div class="employee-card__qr">
                    <div class="qr-code" id="qrcode-{{ $employee->emp_ID }}"></div>
                    <p class="employee-card__scan">Scan to log attendance</p>
                </div>

                <div class="employee-card__body">
                    <h5 class="employee-card__name">
                        {{ strtoupper($employee->fname) }} {{ strtoupper($employee->lname) }} {{ strtoupper($employee->suffix) }}
                    </h5>
                    <p class="employee-card__position">{{ $employee->position ?: 'Office Staff' }}</p>
                    <span class="employee-card__id">{{ $employee->emp_ID }}</span>
                </div>

                <div class="employee-card__footer">
                    Property of LGU Mabinay &middot; Return if found
                </div>
            </div>
        @endforeach
    </div>

    {{-- Served locally so the sheet still prints without an internet connection --}}
    <script src="{{ asset('template/dist/js/qrcode.min.js') }}"></script>
    <script>
        const employees = [
            @foreach($employees as $employee)
                { id: "{{ $employee->emp_ID }}", token: "{{ shortEncrypt($employee->emp_ID) }}" },
            @endforeach
        ];

        window.onload = function () {
            employees.forEach(function (emp) {
                const el = document.getElementById('qrcode-' + emp.id);
                if (!el) return;
                el.innerHTML = "";
                new QRCode(el, {
                    text: emp.token,
                    width: 196,
                    height: 196,
                    colorDark: "#10502C",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            });
        };
    </script>
</body>
</html>
