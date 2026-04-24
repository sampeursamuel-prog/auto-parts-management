<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache vidé !<br>";
}
echo "Cache vidé. <a href='/auto-parts-management/public/index.php'>Retour à l'accueil</a>";