<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow p-4" style="max-width: 500px; width: 100%;">
        <h4 class="mb-3">Email Verification Required</h4>

        @if (session('resent'))
            <div class="alert alert-success" role="alert">
                A new verification link has been sent to your email address.
            </div>
        @endif

        <p>Please check your email for a verification link before proceeding.</p>
        <p>If you did not receive the email, click the button below to request another.</p>

        <form class="mt-3" method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">Resend Verification Email</button>
        </form>
    </div>
</body>
</html>
