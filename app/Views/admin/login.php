<?php
/**
 * Back-office sign-in.
 *
 * Rendered bare (`admin_view('login', $data, 1)`), so it carries its own head
 * and scripts. It uses the admin Bootstrap/AdminLTE bundle rather than the
 * public site theme: the front theme's `.input-text` fields are full-width
 * hero-sized controls, which is what made this screen look oversized.
 */
$sitename = isset($settings[0]->s_sitename) ? $settings[0]->s_sitename : 'Pick A Shift';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="<?php echo base_url('assets/images/favicon.png');?>">
    <title><?php echo $sitename;?> | Admin Login</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700&display=fallback">
    <link rel="stylesheet" href="<?php echo base_url('assets/admin/plugins/fontawesome-free/css/all.min.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/admin/dist/css/adminlte.min.css');?>">

    <style>
        :root {
            --brand: #c52424;
            --brand-dark: #a11d1d;
        }

        html,
        body {
            height: 100%;
        }

        body.login-page {
            /* Centre the card in the viewport instead of AdminLTE's fixed top
               offset, so the box stays put on short and tall screens alike. */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-color: #eef0f4;
            background-image:
                radial-gradient(circle at 12% 18%, rgba(197, 36, 36, .10), transparent 42%),
                radial-gradient(circle at 88% 82%, rgba(52, 58, 64, .12), transparent 45%);
            font-family: 'Source Sans Pro', -apple-system, "Segoe UI", sans-serif;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            margin: 0;
        }

        .login-card {
            border: 0;
            border-radius: .75rem;
            box-shadow: 0 .75rem 2rem rgba(20, 24, 33, .14);
            overflow: hidden;
        }

        .login-card__head {
            padding: 1.75rem 1.75rem 1.25rem;
            text-align: center;
            border-bottom: 1px solid #edf0f3;
        }

        .login-card__head img {
            max-height: 46px;
            width: auto;
        }

        .login-card__head h1 {
            margin: 1rem 0 .15rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #2f3542;
        }

        .login-card__head p {
            margin: 0;
            font-size: .875rem;
            color: #8a94a6;
        }

        .login-card__body {
            padding: 1.5rem 1.75rem 1.75rem;
        }

        .login-card__body label {
            font-size: .8125rem;
            font-weight: 600;
            color: #56606e;
            margin-bottom: .35rem;
        }

        .login-card .form-control {
            height: calc(2.5rem + 2px);
            border-color: #dfe3e9;
            font-size: .9375rem;
        }

        .login-card .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 .2rem rgba(197, 36, 36, .12);
        }

        .login-card .input-group-text {
            width: 2.75rem;
            justify-content: center;
            background-color: #f6f7f9;
            border-color: #dfe3e9;
            color: #98a2b3;
        }

        .login-card .input-group-text.toggle-password {
            cursor: pointer;
        }

        .captcha-row {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .captcha-row img {
            height: calc(2.5rem + 2px);
            width: auto;
            border: 1px solid #dfe3e9;
            border-radius: .25rem;
        }

        .btn-refresh-captcha {
            border-color: #dfe3e9;
            color: #8a94a6;
            height: calc(2.5rem + 2px);
            width: 2.5rem;
            padding: 0;
            flex: 0 0 auto;
        }

        .btn-brand {
            background-color: var(--brand);
            border-color: var(--brand);
            color: #fff;
            font-weight: 600;
            letter-spacing: .03em;
            height: calc(2.5rem + 2px);
        }

        .btn-brand:hover,
        .btn-brand:focus {
            background-color: var(--brand-dark);
            border-color: var(--brand-dark);
            color: #fff;
            box-shadow: 0 0 0 .2rem rgba(197, 36, 36, .18);
        }

        .login-foot {
            margin-top: 1.25rem;
            text-align: center;
            font-size: .8125rem;
            color: #98a2b3;
        }

        .invalid-feedback {
            font-size: .8125rem;
        }

        @media (max-width: 400px) {

            .login-card__head,
            .login-card__body {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }
        }
    </style>
</head>

<body class="login-page">

    <div class="login-box">
        <div class="card login-card">

            <div class="login-card__head">
                <img src="<?php echo base_url('assets/front/assets/img/logo.png');?>" alt="<?php echo $sitename;?>">
                <h1>Administrator Login</h1>
                <p>Sign in to manage <?php echo $sitename;?></p>
            </div>

            <div class="login-card__body">
                <?php if (session()->getFlashdata('error_msg')) { echo session()->getFlashdata('error_msg'); } ?>

                <form class="login" id="loginForm" action="<?php echo base_url('sadmin/login');?>" method="post">

                    <div class="form-group">
                        <label for="username">User Id</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" name="username" id="username" class="form-control"
                                placeholder="Enter your user id" autocomplete="username" autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input type="password" name="password" id="password" class="form-control"
                                placeholder="Enter your password" autocomplete="current-password">
                            <div class="input-group-append">
                                <span class="input-group-text toggle-password" id="togglePassword"
                                    title="Show password"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="captcha">Verification Code</label>
                        <div class="captcha-row mb-2">
                            <img id="captchaImage" src="<?php echo site_url('front/test_cap');?>" alt="Verification code">
                            <button type="button" class="btn btn-outline-secondary btn-refresh-captcha"
                                id="refreshCaptcha" title="Get a new code"><i class="fas fa-sync-alt"></i></button>
                        </div>
                        <input type="text" name="captcha" id="captcha" class="form-control" size="6" maxlength="6"
                            inputmode="numeric" placeholder="Type the 6 digits above" autocomplete="off">
                    </div>

                    <button type="submit" name="loginSubmit" value="loginSubmit"
                        class="btn btn-brand btn-block text-uppercase">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </button>
                </form>
            </div>

        </div>

        <p class="login-foot">&copy; <?php echo date('Y');?> <?php echo $sitename;?></p>
    </div>

    <!-- scripts -->
    <script src="<?php echo base_url('assets/admin/plugins/jquery/jquery.min.js');?>"></script>
    <script src="<?php echo base_url('assets/admin/plugins/bootstrap/js/bootstrap.bundle.min.js');?>"></script>
    <script src="<?php echo base_url('assets/admin/plugins/jquery-validation/jquery.validate.min.js');?>"></script>
    <script src="<?php echo base_url('assets/admin/plugins/jquery-validation/additional-methods.min.js');?>"></script>
    <script>
        $(function () {

            $('#loginForm').validate({
                rules: {
                    username: {
                        required: true,
                        minlength: 3
                    },
                    password: {
                        required: true,
                        minlength: 3
                    },
                    captcha: {
                        required: true
                    }
                },
                messages: {
                    username: {
                        required: "Please enter a user id",
                        minlength: "Your user id must be at least 3 characters long"
                    },
                    password: {
                        required: "Please provide a password",
                        minlength: "Your password must be at least 3 characters long"
                    },
                    captcha: {
                        required: "Please enter the verification code"
                    }
                },
                errorElement: 'span',
                errorPlacement: function (error, element) {
                    /* The inputs sit inside an .input-group, so Bootstrap's
                       `.is-invalid ~ .invalid-feedback` sibling rule never
                       matches - d-block shows the message regardless. */
                    error.addClass('invalid-feedback d-block');
                    element.closest('.form-group').append(error);
                },
                highlight: function (element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function (element) {
                    $(element).removeClass('is-invalid');
                }
            });

            // Fresh code without reposting the form; the query string defeats
            // the browser cache.
            $('#refreshCaptcha').on('click', function () {
                $('#captchaImage').attr('src', '<?php echo site_url('front/test_cap');?>?_=' + new Date().getTime());
                $('#captcha').val('').focus();
            });

            $('#togglePassword').on('click', function () {
                var $field = $('#password');
                var shown  = $field.attr('type') === 'text';

                $field.attr('type', shown ? 'password' : 'text');
                $(this).attr('title', shown ? 'Show password' : 'Hide password')
                    .find('i').toggleClass('fa-eye', shown).toggleClass('fa-eye-slash', !shown);
            });
        });
    </script>

</body>

</html>
