<?php
if (isset($_GET["error"])) {
  if ($_GET["error"] == "wrongpworemail") {
    $errormsg = "Hibás e-mail vagy jelszó!";
  }
}

if (isset($_GET["msg"])) {
  if ($_GET["msg"] == "success") {
    $successmsg = "Sikeres regisztráció, mostmár bejelentkezhetsz";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login - WikiClone</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <header>
    <h1>WikiClone</h1>
    <nav>
      <a href="#">Főoldal</a>
      <a href="#">Véletlenszerű szócikk</a>
      <a href="./../views/register_form.php">Regisztráció</a>
    </nav>
  </header>

  <main style="justify-content: center;">
    <div class="auth-container">
      <div class="form-box">
        <h2>Bejelentkezés</h2>
        <form method="post" action="./../actions/login.php">
          <label for="login-username">Felhasználónév</label>
          <input type="text" id="login-username" name="username" required>

          <label for="login-password">Jelszó</label>
          <input type="password" id="login-password" name="password" required>

          <button type="submit">Bejelentkezés</button>
          <?php if (isset($errormsg)) { ?>
            <div class="error-msg"><?php echo $errormsg; ?></div>
          <?php } ?>
          <?php if (isset($successmsg)) { ?>
            <div class="success-msg"><?php echo $successmsg; ?></div>
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