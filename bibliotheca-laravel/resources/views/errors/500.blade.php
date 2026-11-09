<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Server Error | Bibliotheca</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Momo+Trust+Display&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 + Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Error page styles -->
    <link href="error.css" rel="stylesheet">
</head>
<body class="error-500">

    <main class="error-wrapper d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center justify-content-center g-4 g-lg-5 text-center text-md-start">

                <div class="col-md-4 text-center">
                    <i class="bi bi-exclamation-circle-fill error-icon" aria-hidden="true"></i>
                </div>

                <div class="col-md-8 col-lg-7">
                    <h1 class="error-title">Server Error!</h1>
                    <p class="error-message">
                        We're having trouble processing your request right now. Please try again in a few moments, or return to the homepage.
                    </p>
                    <p class="error-code">Error code: 500</p>

                    <!-- Laravel: href="{{ url()->previous() }}" -->
                    <a href="javascript:history.back()" class="btn btn-back">Go back</a>
                </div>

            </div>
        </div>
    </main>

</body>
</html>