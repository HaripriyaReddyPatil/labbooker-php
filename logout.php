<?php
require 'config.php';

logoutUser();

flash('You have been signed out.');

redirect('login.php');