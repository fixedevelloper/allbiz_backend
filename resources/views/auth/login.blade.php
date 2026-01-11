<html lang="en" class="dark"><head>
    <meta charset="utf-8">
    <title>Allbiz Dashboard</title>
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:title" content="">
    <meta property="og:type" content="">
    <meta property="og:url" content="">
    <meta property="og:image" content="">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('assets/imgs/theme/favicon.svg')}}" />
    <!-- Template CSS -->
    <script src="{{asset('assets/js/vendors/color-modes.js')}}"></script>
    <link href="{{asset('assets/css/main.css')}}?v=6.0" rel="stylesheet" type="text/css" />
</head>

<body>
<main>
    <header class="main-header style-2 navbar">
        <div class="col-brand">

        </div>

    </header>
    <section class="content-main mt-80 mb-10">
        <div class="card mx-auto card-login">
            <div class="card-body">
                <h4 class="card-title mb-4">Connexion</h4>
                <form method="POST" action="{{ route('login') }}" class="card p-4">
                    @csrf
                    <div class="mb-3">
                        <label class="mb-2">Telephone</label>
                        <input name="phone" class="form-control" placeholder="Phone" type="text" required>
                    </div>
                    <!-- form-group// -->
                    <div class="mb-3">
                        <label class="mb-2">Password</label>
                        <input name="password" class="form-control" placeholder="Password" type="password" required>
                    </div>
                    <!-- form-group// -->
                    <div class="mb-3">
                        <a href="#" class="float-end font-sm text-muted">Forgot password?</a>
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" checked="">
                            <span class="form-check-label">Remember</span>
                        </label>
                    </div>
                    <!-- form-group form-check .// -->
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </div>
                    <!-- form-group// -->
                </form>
            </div>
        </div>
    </section>
    <footer class="main-footer text-center">
        <p class="font-xs">
            <script>
                document.write(new Date().getFullYear());
            </script>2026
            © AllBiz - .
        </p>
        <p class="font-xs mb-10">All rights reserved</p>
    </footer>
</main>
<script src="{{asset('assets/js/vendors/jquery-3.6.0.min.js')}}"></script>
<script src="{{asset('assets/js/vendors/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/js/vendors/select2.min.js')}}"></script>
<!-- Main Script -->
<script src="{{asset('assets/js/main.js')}}?v=6.0" type="text/javascript"></script>


</body></html>
