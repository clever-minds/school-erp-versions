<?php
$c = file_get_contents("app/Http/Controllers/FeesController.php");
$startStr = "        \$sql = \App\Models\FeesPaid::with([";
$endStr = "    public function feesPaidReceiptPDF(\$feesPaidId)";

$start = strpos($c, $startStr);
$end = strpos($c, $endStr);

$draft = file_get_contents("combined_fees.php");
$draft = preg_replace('/^<\?php[\r\n]*/', '', $draft);

// Add closing brace for the function because we are replacing up to the start of the next function
$newC = substr($c, 0, $start) . $draft . "\r\n\r\n" . substr($c, $end);
file_put_contents("app/Http/Controllers/FeesController.php", $newC);
echo "Replaced successfully\n";
