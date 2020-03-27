<?php
if (isset($_GET['token'])) {
  setcookie('n_token', $_GET['token']);
}
