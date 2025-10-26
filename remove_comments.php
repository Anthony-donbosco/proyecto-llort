<?php
function removeCommentsFromFile($filename) {
    $source = file_get_contents($filename);
    $tokens = token_get_all($source);
    $output = '';

    foreach ($tokens as $token) {
        if (is_string($token)) {
            $output .= $token;
        } else {
            list($id, $text) = $token;
            switch ($id) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                    $output .= str_repeat("\n", substr_count($text, "\n"));
                    break;
                default:
                    $output .= $text;
                    break;
            }
        }
    }

    $output = preg_replace('/<!--.*?-->/s', '', $output);

    $output = preg_replace_callback(
        '/(<script[^>]*>)(.*?)(<\/script>)/si',
        function($matches) {
            $jsCode = $matches[2];
            $jsCode = preg_replace('~/\*.*?\*/~s', '', $jsCode);
            $jsCode = preg_replace('~(?://[^\n]*|/\*.*?\*/)~s', '', $jsCode);
            return $matches[1] . $jsCode . $matches[3];
        },
        $output
    );

    $output = preg_replace_callback(
        '/(<style[^>]*>)(.*?)(<\/style>)/si',
        function($matches) {
            $cssCode = $matches[2];
            $cssCode = preg_replace('~/\*.*?\*/~s', '', $cssCode);
            return $matches[1] . $cssCode . $matches[3];
        },
        $output
    );

    return $output;
}

$directory = __DIR__ . '/php';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$processedFiles = 0;
$errors = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filepath = $file->getPathname();

        try {
            echo "Processing: $filepath\n";
            $cleanedCode = removeCommentsFromFile($filepath);
            file_put_contents($filepath, $cleanedCode);
            $processedFiles++;
        } catch (Exception $e) {
            $errors[] = "$filepath: " . $e->getMessage();
            echo "ERROR: $filepath - " . $e->getMessage() . "\n";
        }
    }
}

echo "\n========== SUMMARY ==========\n";
echo "Total files processed: $processedFiles\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nError details:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nDone! All PHP comments have been removed.\n";
?>
