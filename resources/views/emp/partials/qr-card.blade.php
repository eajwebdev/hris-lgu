{{--
    Employee QR card: a municipal ID card carrying the Mabinay seal, with the
    code the attendance scanner reads. Shown in a dialog on both shells
    (emp/submenu-side on layouts/master, emp/partials/pds-side on layouts/app)
    and downloaded from there as a PNG.

    It is rendered to that PNG by html2canvas, so everything here is plain CSS
    with same-origin images: no utility classes (html2canvas cannot read the
    colour functions Tailwind writes) and no remote assets. The code itself is
    drawn into #qrcode by the page that shows the card.
--}}
<style>
    .employee-card {
        width: 300px;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #E5E7EB;
        box-shadow: 0 18px 40px -12px rgba(15, 23, 42, .25);
        font-family: "Inter", "Instrument Sans", Arial, sans-serif;
        text-align: center;
        margin: 0 auto;
    }

    .employee-card__header {
        background: linear-gradient(135deg, #1E7A45 0%, #10502C 100%);
        padding: 14px 12px 12px;
        color: #fff;
        position: relative;
    }
    .employee-card__header::after {
        content: "";
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 4px;
        background: linear-gradient(90deg, #EF9017, #FBBF24, #EF9017);
    }
    .employee-card__seal {
        /* Named, because Tailwind's reset makes every image a block. */
        display: inline-block;
        width: 54px;
        height: 54px;
        object-fit: contain;
        border-radius: 50%;
        background: #fff;
        padding: 3px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .25);
    }
    .employee-card__org {
        margin: 7px 0 0;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        line-height: 1.3;
    }
    .employee-card__sub {
        margin: 2px 0 0;
        font-size: 8.5px;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .78);
    }

    .employee-card__qr {
        padding: 16px 16px 10px;
        background: #fff;
    }
    .employee-card__qr .qr-code {
        /* A block shrunk to the code, not an inline-block: html2canvas paints
           an inline-block's background after the blocks inside it, and the
           code is one, so the PNG came out with an empty white square. */
        display: block;
        width: fit-content;
        margin: 0 auto;
        padding: 10px;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        background: #fff;
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

    .employee-card__body {
        padding: 4px 16px 16px;
        background: #fff;
    }
    .employee-card__name {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0F172A;
        line-height: 1.25;
        letter-spacing: -.01em;
    }
    .employee-card__position {
        margin: 3px 0 10px;
        font-size: 11px;
        color: #64748B;
        line-height: 1.35;
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
</style>

<div class="employee-card-content">
    <div class="employee-card" id="employeeCard">

        <div class="employee-card__header">
            <img src="{{ asset('Uploads/logo.png') }}" alt="Municipality of Mabinay Official Seal" class="employee-card__seal">
            <p class="employee-card__org">Municipality of Mabinay</p>
            <p class="employee-card__sub">Human Resource Information System</p>
        </div>

        <div class="employee-card__qr">
            <div class="qr-code" id="qrcode">
                <!-- QR Code is rendered here -->
            </div>
            <p class="employee-card__scan">Scan to log attendance</p>
        </div>

        <div class="employee-card__body">
            <h5 class="employee-card__name">
                {{ strtoupper(str_replace('Ñ', 'ñ', $employee->fname)) }}
                {{ strtoupper(str_replace('Ñ', 'ñ', $employee->lname)) }}
                {{ strtoupper(str_replace('Ñ', 'ñ', $employee->suffix)) }}
            </h5>
            <p class="employee-card__position">
                {{ ($employee->emp_status == 1 && $employee->position) ? $employee->position : 'Office Staff' }}
            </p>
            <span class="employee-card__id">{{ $employee->emp_ID }}</span>
        </div>

        <div class="employee-card__footer">
            Property of LGU Mabinay &middot; Return if found
        </div>
    </div>
</div>
