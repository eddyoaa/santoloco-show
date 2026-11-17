<?php
if (is_post()) {
    logout();
}
redirect('login');
