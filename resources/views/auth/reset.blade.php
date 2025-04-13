<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Reset Password</h2>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ request()->route('token') }}">
        
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-control">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input id="password" type="password" name="password" required class="form-control">
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
</div>
</body>
</html>
