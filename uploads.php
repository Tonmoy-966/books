<?php

$uploads_dir = __DIR__ . '/uploads';
$covers_dir = __DIR__ . '/uploads/covers';


if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
if (!is_dir($covers_dir)) mkdir($covers_dir, 0755, true);

echo "✓ Directories created successfully!<br>";
echo "✓ Uploads: $uploads_dir<br>";
echo "✓ Covers: $covers_dir<br>";
echo "<br><strong>DELETE this file now!</strong>";
?>