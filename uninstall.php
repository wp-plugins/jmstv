<?php
/* Remove settings */
delete_option('jmstv');

/* Clean DB */
$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->options. "`");