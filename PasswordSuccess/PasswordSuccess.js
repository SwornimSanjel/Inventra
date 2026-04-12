/**
 * Auto-redirection logic for the success page
 */
document.addEventListener('DOMContentLoaded', () => {
    // Redirect to login after 5 seconds
    const redirectTimer = setTimeout(() => {
        window.location.href = '../login/login.php';
    }, 5000);

    console.log("Success page loaded. Redirecting in 5 seconds...");
});