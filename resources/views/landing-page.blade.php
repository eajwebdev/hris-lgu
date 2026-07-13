<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CPSU | HRIS</title>
        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
        <!-- icheck bootstrap -->
        <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
        <!-- Theme style -->
        <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
        <!-- Logo  -->
        <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">

        <style>
            body {
                position: relative;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                font-family: 'Source Sans Pro', sans-serif;
                background: url('{{ asset('template/img/landing-page-bg.jpg') }}') no-repeat center center fixed;
                background-size: cover;
                overflow: hidden;
            }

            body::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(247, 206, 206, 0.5); /* Dark overlay with opacity */
                backdrop-filter: blur(5px); /* Blur effect */
                z-index: 1;
            }

            .container {
                position: relative;
                z-index: 2; /* Bring the content above the overlay */
            }

            .ribbon-wrapper {
                width: 100%;
                text-align: center;
            }
            
            .ribbon {
                width: 100%;
            }

            .position-relative {
                overflow: hidden;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
                border: 2px solid rgb(255, 255, 255);
            }


            .img-fluid {
                transition: transform 0.5s ease, box-shadow 0.5s ease; 
            }

            .position-relative {
                overflow: hidden;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            }

            .position-relative:hover .img-fluid {
                transform: scale(1.1);
            }

            @media (max-width: 767.98px) {
                .ribbon-wrapper {
                    position: absolute;
                    top: 10px;
                    left: 10px;
                }

                .ribbon {
                    font-size: 1.5rem;
                    padding: 0.5rem;
                }
            }
        </style>
    </head>
    <body class="hold-transition">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-md-4 col-sm-6 mb-4" id="hris">
                    <a href="{{ route('getLogin', Crypt::encrypt('012-324-567')) }}">
                        <div class="position-relative" style="min-height: 180px;">
                            <img src="{{ asset('template/img/login-bg.jpg') }}" alt="HRIS" class="img-fluid" style="border-radius: 10px;">
                            <div class="ribbon-wrapper ribbon-xl">
                                <div class="ribbon bg-success text-xl">
                                    HRIS
                                </div>
                            </div>
                        </div>
                    </a>                    
                </div>
                <div class="col-md-4 col-sm-6 mb-4" id="payroll">
                    <a href="http://localhost/cpsupms/public/">
                        <div class="position-relative" style="min-height: 180px;">
                            <img src="{{ asset('template/img/payroll.jpg') }}" alt="Payroll" class="img-fluid" style="border-radius: 10px;">
                            <div class="ribbon-wrapper ribbon-xl">
                                <div class="ribbon bg-success text-xl">
                                    PAYROLL
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <!-- /.login-box -->
        <!-- jQuery -->
        <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
        <!-- Bootstrap 4 -->
        <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <!-- AdminLTE App -->
        <script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>
        <!-- jquery-validation -->
        <script src="{{ asset('template/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('template/plugins/jquery-validation/additional-methods.min.js') }}"></script>

    </body>
</html>
