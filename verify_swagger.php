<?php
$content = file_get_contents(__DIR__ . '/storage/api-docs/api-docs.json');
echo "Checking api-docs.json...\n";
echo "CRM Companies: " . (strpos($content, 'CRM Companies') !== false ? 'FOUND' : 'MISSING') . "\n";
echo "Media: " . (strpos($content, 'Media') !== false ? 'FOUND' : 'MISSING') . "\n";
echo "CRM Activities: " . (strpos($content, 'CRM Activities') !== false ? 'FOUND' : 'MISSING') . "\n";
