<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — SI-GORAY</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f3f6fa; font: 16px/1.5 system-ui, sans-serif; color: #172338; }
        .login-card { background: white; border: 1px solid #e0e6ee; border-radius: 15px; padding: 30px; width: min(420px, calc(100vw - 32px)); box-shadow: 0 8px 26px #182a4812; }
        h1 { margin: 0 0 4px; font-size: 24px; }
        p { margin: 0 0 24px; color: #64748b; }
        label { display: block; font-weight: 600; margin: 16px 0 6px; }
        input { display: block; width: 100%; padding: 11px 12px; border: 1px solid #bbc6d4; border-radius: 9px; font-size: 16px; }
        input:focus-visible { outline: 2px solid #2563eb; outline-offset: 1px; }
        button { margin-top: 22px; width: 100%; border: 0; border-radius: 9px; padding: 12px; font-weight: 700; font-size: 16px; background: #1249a0; color: #fff; cursor: pointer; }
        .error { margin: 12px 0; padding: 11px; background: #fff1f1; color: #a11c1c; border-radius: 8px; }
    </style>
</head>
<body>
    <main class="login-card">
        <h1>SI-GORAY</h1>
        <p>Masuk ke panel administrator</p>

        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <label for="username">Username Admin</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" maxlength="100" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit">Masuk sebagai Admin</button>
        </form>
    </main>
</body>
</html>
