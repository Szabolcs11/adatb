<?php
if (isset($_GET["error"])) {
  if ($_GET["error"] == "fields_empty") {
    $errormsg = "Töltsd ki az összes mezőt!";
  } else if ($_GET["error"] == "password_mismatch") {
    $errormsg = "A jelszavak nem egyeznek!";
  } else if ($_GET["error"] == "username_or_email_exists") {
    $errormsg = "Ez a felhasználónév vagy email már foglalt!";
  } else if ($_GET["error"] == "unexpected_error") {
    $errormsg = "Váratlan hiba történt! Kérlek próbáld újra később.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Register - WikiClone</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <header>
    <h1>WikiClone</h1>
    <nav>
      <a href="#">Főoldal</a>
      <a href="#">Véletlenszerű szócikk</a>
      <a href="./../views/login_form.php">Bejelentkezés</a>
    </nav>
  </header>

  <main style="justify-content: center;">
    <div class="auth-container">
      <div class="form-box">
        <h2>Regisztráció</h2>
        <form method="post" action="./../actions/register.php">
          <label for="reg-username">Felhasználónév</label>
          <input type="text" id="reg-username" name="username" required>

          <label for="reg-email">Email cím</label>
          <input type="email" id="reg-email" name="email" required>

          <label for="reg-password">Jelszó</label>
          <input type="password" id="reg-password" name="password" required>

          <label for="reg-password-check">Jelszó újra</label>
          <input type="password" id="reg-password-check" name="password-check" required>

          <button type="submit">Regisztráció</button>
          <br />
          <?php if (isset($errormsg)) { ?>
            <div class="error-msg"><?php echo $errormsg; ?></div>
          <?php } ?>
        </form>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 WikiClone. All text is available under the CC BY-SA License.</p>
  </footer>
</body>

</html>