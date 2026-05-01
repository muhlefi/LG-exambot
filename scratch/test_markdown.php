<?php
require __DIR__.'/../vendor/autoload.php';
use Illuminate\Support\Str;

$text = "| Title | Col |\n|---|---|\n| cell 1 | cell 2 |";
echo "--- TABLE TEST ---\n";
echo Str::markdown($text);

$latex = "Formula: \$x_1 + x_2 = y^2\$ and block:\n\n\$\$ E = mc^2 \$\$";
echo "\n--- LATEX TEST ---\n";
echo "Original: " . $latex . "\n";
echo "Markdown: " . Str::markdown($latex) . "\n";

