<?php
// Copy to /data/config.php on the server (NOT into the repository) and fill in the hash.
// Generate it locally:  php -r "echo password_hash('your password', PASSWORD_DEFAULT);"
return [
    'password_hash' => '$2y$10$replace.with.your.own.hash..........................',
];
