<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoSmart - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>body, html {
    height: 100%;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Hero Section */
.front-page {
    background-image: url('images/background.jpg');
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    min-height: 100vh;
    position: relative;
    display: flex;
    flex-direction: column;
}

/* Dark overlay */
.front-page::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 1;
}

/* Navbar */
.navbar {
    z-index: 10;
    padding: 1rem 2rem;
    background: transparent !important;
}

.navbar-brand img {
    height: 38px;
}

.nav-link {
    color: #fff !important;
    font-weight: 500;
    transition: 0.3s;
}

.nav-link:hover {
    color: #7db6e0ff !important;
}

/* Login Button */
.login-btn {
    border: 1px solid #fff;
    border-radius: 20px;
    padding: 6px 18px;
}

/* Hero Content */
.content {
    z-index: 10;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #fff;
    padding: 2rem 1rem;
}

.content-inner {
    max-width: 700px;
    width: 100%;
}

.content h1 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 700;
    margin-bottom: 1rem;
}

.content p {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* Buttons */
.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

/* Mobile Navbar */
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: rgba(0, 0, 0, 0.51);
        padding: 1rem;
        border-radius: 10px;
        margin-top: 10px;
    }

    .login-btn {
        margin-top: 10px;
    }
}
</style>

</head>
<body>

    <div class="front-page">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="images/icon only.png" alt="Logo">
                    <span class="fw-bold">InventoSmart</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto text-center">
                        <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Solutions</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                    </ul>
                    <div class="d-flex justify-content-center">
                        <a href="login.php" class="nav-link login-btn">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign in
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="content">
    <div class="content-inner">
        <h1>Welcome to the Inventory System</h1>
        <p>Manage your stock efficiently and securely.</p>

        <div class="hero-buttons">
            <a href="#" class="btn btn-primary btn-lg px-4">Get Started</a>
            <a href="#" class="btn btn-outline-light btn-lg px-4">Learn More</a>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>