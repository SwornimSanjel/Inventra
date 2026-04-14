<?php
header('Location: ../index.php?url=login' . (!empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''));
exit;

$error_message = ""; 
$info_message = "";

if(isset($_GET['error'])) {
    if ($_GET['error'] === 'session_expired') {
        $error_message = "Your session has expired. Please log in again.";
    } elseif ($_GET['error'] === 'unauthorized') {
        $error_message = "You do not have permission to access that page.";
    } else {
        $error_message = "Invalid username or password. Please try again.";
    }
}

if(isset($_GET['logout'])) {
    $info_message = "You have been logged out successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventra | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        
        <div class="header">
            <div class="logo">
                <img src="../public/images/inventra-logo.png" alt="logo">
            </div> 
            <h1>Inventra</h1>
            <p class="tagline">Comprehensive stock tracking and inventory movement monitoring</p>
        </div>

        <div class="card">
            <?php if($info_message): ?>
                <div class="info-box"><?php echo $info_message; ?></div>
            <?php endif; ?>
            <?php if($error_message): ?>
                <div class="error-box"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="authenticate.php">
                <div class="field">
                    <label>USERNAME OR EMAIL</label>
                    <input type="text" name="username" placeholder="example@inventra.com" required>
                </div>

                <div class="field">
                    <label>PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <span class="toggle-password" onclick="togglePassword()">
                            <svg id="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn">Log In</button>
            </form>
            <p class="footer">
               <a href="" class="forgot"> Forgot your password?</a>
            </p>
        </div>
        
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eye-icon");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.style.color = "#1e293b"; 
            } else {
                passwordInput.type = "password";
                eyeIcon.style.color = "#64748b"; 
            }
        }
    </script>

</body>
</html>
