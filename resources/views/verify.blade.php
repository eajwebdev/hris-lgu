<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verify Your Email</title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">

    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

    <!-- AdminLTE -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">

    <!-- Custom Verify Style -->
    <link rel="stylesheet" href="{{ asset('css/verify.css') }}">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('template/img/CPSU_L.png') }}">

    <style>
        body {
            background-image: url('{{ asset('template/img/login-bg.jpg') }}');
            background-size: cover;
            background-position: center;
        }

        .otp-wrapper {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 25px 0;
        }

        .otp-input {
            width: 48px;
            height: 55px;
            font-size: 22px;
            text-align: center;
            border-radius: 10px;
            border: 2px solid #ffc107;
            background: transparent;
            color: #fff;
            outline: none;
            transition: all 0.2s ease;
        }

        .otp-input:focus {
            border-color: #ffdd57;
            box-shadow: 0 0 10px rgba(255,193,7,0.5);
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="card">
            <div class="card-body">

                <div class="login-logo mt-4 text-center">
                    <a href="./">
                        <img src="{{ asset('template/img/CPSU_L.png') }}" class="img-fluid" width="120">
                    </a>
                </div>

                <p class="login-box-msg mb-2">
                    <span class="text-light">Verify Your Email</span>
                </p>

                <p class="text-light text-center" style="font-size: 16px;">
                    A 6-digit verification code has been sent to your email.
                </p>

                <form action="{{ route('verify.code') }}" method="POST" id="otpForm">
                    @csrf

                    <input type="hidden" id="email" name="email" value="{{ session('email') }}">
                    <input type="hidden" name="verification_code" id="verification_code">

                    <div class="otp-wrapper">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" inputmode="numeric">
                    </div>

                    <!-- <div class="form-group mt-4">
                        <button type="submit" class="btn btn-warn btn-block w-100">
                            <i class="fas fa-check-circle"></i> Verify
                        </button>
                    </div> -->
                </form>

            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function () {

        const inputs = $(".otp-input");
        let submitted = false;

        inputs.first().focus();

        inputs.on("input", function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && $(this).next('.otp-input').length) {
                $(this).next('.otp-input').focus();
            }
            updateCode();
        });

        inputs.on("keydown", function (e) {
            if (e.key === "Backspace" && !this.value && $(this).prev('.otp-input').length) {
                $(this).prev('.otp-input').focus();
            }
        });

        inputs.on("paste", function (e) {
            e.preventDefault();
            const pasteData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            const digits = pasteData.replace(/\D/g, '').split('');
            inputs.each(function (index) {
                $(this).val(digits[index] || '');
            });
            updateCode();
        });

        function updateCode() {
            let code = "";
            inputs.each(function () {
                code += $(this).val();
            });

            $("#verification_code").val(code);

            if (code.length === 6 && !submitted) {
                submitted = true;
                inputs.prop('disabled', true);
                $("#otpForm").submit();
            }
        }

        if (!$('#email').val()) {
            window.location.href = "{{ route('getLogin') }}";
        }

    });
    </script>

</body>
</html>
