<!doctype html>
<html lang="en" class="fullscreen-bg">

<head>
    <title>Login | original</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="{{asset('assets/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/vendor/font-awesome/css/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/vendor/linearicons/style.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/main.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/demo.css')}}">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('upload/favicon.png') }}">
    <style>
        .mobile-logo { display: none; }

        @media (max-width: 767px) {
            html.fullscreen-bg,
            html.fullscreen-bg body,
            html.fullscreen-bg #wrapper {
                height: auto;
                min-height: 100dvh;
                background: #f5f5f5 !important;
            }

            #wrapper {
                display: flex;
                align-items: stretch;
                min-height: 100dvh;
                padding: 0;
            }

            .vertical-align-wrap,
            .vertical-align-middle {
                display: block;
                width: 100%;
            }

            .auth-box {
                width: 100% !important;
                height: auto !important;
                min-height: 100dvh;
                max-width: 100%;
                margin: 0;
                background: #fff !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                border: none;
                padding: 60px 28px 40px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .auth-box .right {
                display: none !important;
            }

            .auth-box .left {
                float: none !important;
                width: 100% !important;
                height: auto !important;
                padding: 0 !important;
                text-align: left !important;
            }

            .auth-box .left::before {
                display: none !important;
            }

            .auth-box .content {
                display: block !important;
                width: 100% !important;
            }

            /* Logo */
            .mobile-logo {
                display: block;
                text-align: center;
                margin-bottom: 32px;
            }
            .mobile-logo img {
                width: 64px;
                height: 64px;
                object-fit: cover;
                border-radius: 14px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            }
            .mobile-logo-name {
                display: block;
                margin-top: 12px;
                font-size: 20px;
                font-weight: 700;
                letter-spacing: 4px;
                color: #111;
                text-transform: uppercase;
            }

            .auth-box .header {
                margin-bottom: 24px;
            }

            .auth-box .lead {
                font-size: 14px !important;
                color: #999 !important;
                text-align: left !important;
                margin-top: 0 !important;
                font-weight: 400;
                letter-spacing: 0.3px;
            }

            /* Inputs */
            .auth-box .form-control {
                height: 50px;
                border-radius: 10px !important;
                background: #fafafa !important;
                border: 1.5px solid #e8e8e8 !important;
                color: #111 !important;
                font-size: 15px;
                padding: 0 14px;
                box-shadow: none !important;
                transition: border-color .2s;
            }
            .auth-box .form-control::placeholder {
                color: #bbb;
            }
            .auth-box .form-control:focus {
                background: #fff !important;
                border-color: #111 !important;
            }

            /* Remember me */
            .auth-box .fancy-checkbox span {
                color: #888;
                font-size: 13px;
            }

            /* Button */
            .auth-box .btn-success {
                height: 50px;
                border-radius: 10px !important;
                background: #111 !important;
                border: none !important;
                color: #fff !important;
                font-weight: 600;
                font-size: 14px;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                margin-top: 8px !important;
                box-shadow: none !important;
                transition: opacity .15s, transform .15s;
            }
            .auth-box .btn-success:active {
                opacity: 0.8;
                transform: scale(0.98);
            }

            /* Forgot password */
            .auth-box .form-auth-small .bottom {
                margin-top: 20px !important;
                text-align: center;
            }
            .auth-box .helper-text,
            .auth-box .helper-text a {
                color: #bbb !important;
                font-size: 13px;
                text-decoration: none;
            }

            /* Error */
            .auth-box .alert-danger {
                background: #fff5f5;
                border: 1px solid #fcc;
                color: #c00;
                border-radius: 8px;
                font-size: 13px;
                padding: 10px 14px;
            }
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <div class="vertical-align-wrap">
            <div class="vertical-align-middle">
                <div class="auth-box">
                    <div class="left">
                        <div class="content">

                            {{-- მობილურის ლოგო --}}
                            <div class="mobile-logo" id="mobileLogo">
                                <img src="{{ asset('upload/products/originalslogo.jpg') }}" alt="Original 100%">
                                <span class="mobile-logo-name">Original 100%</span>
                            </div>

                            <div class="header">
                                <p class="lead">Login to your account</p>
                            </div>
                            <form class="form-auth-small" method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="signin-email" class="control-label sr-only">Email</label>
                                    <input type="email" name="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" id="signin-email" value="{{ old('email') }}" required placeholder="Email">
                                    @if ($errors->has('email'))
                                    <br>
                                    <div class="alert alert-danger alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                        {{ $errors->first('email') }}
                                    </div>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="signin-password" class="control-label sr-only">Password</label>
                                    <input type="password" class="form-control" name="password" id="signin-password" placeholder="Password">
                                    @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <div class="form-group clearfix">
                                    <label class="fancy-checkbox element-left">
                                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <span>Remember me</span>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg btn-block">LOGIN</button>
                                <div class="bottom">
                                    @if (Route::has('password.request'))
                                    <span class="helper-text"><i class="fa fa-lock"></i> <a href="{{ route('password.request') }}">Forgot password?</a></span>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="right" style="position:relative; overflow:hidden; padding:0;">
                        <img
                            src="{{ asset('upload/products/originalslogo.jpg') }}"
                            alt="IMS"
                            style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;object-position:center;">
                        <div style="position:absolute;bottom:0;left:0;right:10px;padding:30px 25px 25px;">
                            <footer class="main-footer">
                                <div class="pull-right hidden-xs">© Developed by Ghvedashvili</div>
                            </footer>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function () {
            $('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%'
            });
        });
    </script>
</body>
</html>
