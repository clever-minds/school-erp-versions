<?php
$c = file_get_contents('public/assets/js/custom/bootstrap-table/formatter.js');
$c = str_replace("\0", '', $c);
file_put_contents('public/assets/js/custom/bootstrap-table/formatter.js', $c);
echo "Done";
