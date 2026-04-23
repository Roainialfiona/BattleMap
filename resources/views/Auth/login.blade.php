<!DOCTYPE html>
<html>
<head>
    <title>Login — Telkom Battle Map</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 40px 36px;
            width: 100%;
            max-width: 380px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-logo img {
            height: 48px;
        }
        .login-logo h1 {
            font-size: 18px;
            font-weight: 700;
            color: #212529;
            margin-top: 10px;
        }
        .login-logo p {
            font-size: 13px;
            color: #6c757d;
            margin-top: 4px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
            margin-top: 16px;
        }
        input[type=text], input[type=password] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            color: #212529;
            transition: border-color .2s;
            outline: none;
        }
        input:focus {
            border-color: #ed1c24;
        }
        .error-msg {
            background: #fff5f5;
            border: 1px solid #f5c6cb;
            color: #dc3545;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-top: 16px;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            font-size: 13px;
            color: #6c757d;
            cursor: pointer;
        }
        .btn-login {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            background: #ed1c24;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-login:hover { background: #c41119; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #6c757d;
            text-decoration: none;
        }
        .back-link:hover { color: #ed1c24; }
    </style>
</head>
<body>

<div class="login-card">

    <div class="login-logo">
        <img src="{{ asset('logo.png') }}" alt="Logo">
        <h1>Telkom Battle Map</h1>
        <p>Login untuk mengakses fitur edit</p>
    </div>

    @if($errors->any())
    <div class="error-msg">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <label>Username</label>
        <input type="text" name="username"
               value="{{ old('username') }}"
               placeholder="Masukkan username"
               autofocus required>

        <label>Password</label>
        <input type="password" name="password"
               placeholder="Masukkan password"
               required>

        <label class="remember">
            <input type="checkbox" name="remember">
            Ingat saya
        </label>

        <button type="submit" class="btn-login">Masuk</button>

    </form>

    <a href="/dashboard" class="back-link">← Kembali ke Map tanpa login</a>

</div>

</body>
</html>
