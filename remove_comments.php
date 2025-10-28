<?php
/**
 * Script para eliminar TODOS los comentarios de archivos PHP, JS, CSS y HTML
 * Uso: php remove_comments.php
 */

class CommentRemover {
    private $processedFiles = 0;
    private $errors = [];
    private $stats = [
        'php' => 0,
        'js' => 0,
        'css' => 0,
        'html' => 0
    ];

    /**
     * Elimina comentarios de archivos PHP
     */
    private function removePHPComments($source) {
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
                        // Mantener saltos de línea para preservar estructura
                        $output .= str_repeat("\n", substr_count($text, "\n"));
                        break;
                    default:
                        $output .= $text;
                        break;
                }
            }
        }

        // Eliminar comentarios HTML dentro de PHP
        $output = preg_replace('/<!--.*?-->/s', '', $output);

        return $output;
    }

    /**
     * Elimina comentarios de archivos JavaScript
     */
    private function removeJSComments($source) {
        // Proteger strings y regex
        $strings = [];
        $stringIndex = 0;

        // Guardar strings entre comillas
        $source = preg_replace_callback(
            '/(\'(?:\\\\.|[^\'])*\'|"(?:\\\\.|[^"])*")/s',
            function($matches) use (&$strings, &$stringIndex) {
                $placeholder = "___STRING_{$stringIndex}___";
                $strings[$placeholder] = $matches[0];
                $stringIndex++;
                return $placeholder;
            },
            $source
        );

        // Eliminar comentarios multi-línea /* */
        $source = preg_replace('~/\*.*?\*/~s', '', $source);

        // Eliminar comentarios de una línea //
        $source = preg_replace('~//[^\n]*~', '', $source);

        // Restaurar strings
        foreach ($strings as $placeholder => $value) {
            $source = str_replace($placeholder, $value, $source);
        }

        return $source;
    }

    /**
     * Elimina comentarios de archivos CSS
     */
    private function removeCSSComments($source) {
        // Eliminar comentarios /* */
        $source = preg_replace('~/\*.*?\*/~s', '', $source);
        return $source;
    }

    /**
     * Elimina comentarios de archivos HTML
     */
    private function removeHTMLComments($source) {
        // Eliminar comentarios HTML <!-- -->
        $source = preg_replace('/<!--.*?-->/s', '', $source);

        // Procesar <script> tags
        $source = preg_replace_callback(
            '/(<script[^>]*>)(.*?)(<\/script>)/si',
            function($matches) {
                $jsCode = $this->removeJSComments($matches[2]);
                return $matches[1] . $jsCode . $matches[3];
            },
            $source
        );

        // Procesar <style> tags
        $source = preg_replace_callback(
            '/(<style[^>]*>)(.*?)(<\/style>)/si',
            function($matches) {
                $cssCode = $this->removeCSSComments($matches[2]);
                return $matches[1] . $cssCode . $matches[3];
            },
            $source
        );

        return $source;
    }

    /**
     * Procesa un archivo según su extensión
     */
    private function processFile($filepath) {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $source = file_get_contents($filepath);

        if ($source === false) {
            throw new Exception("No se pudo leer el archivo");
        }

        $cleaned = '';

        switch ($extension) {
            case 'php':
                $cleaned = $this->removePHPComments($source);
                $this->stats['php']++;
                break;

            case 'js':
                $cleaned = $this->removeJSComments($source);
                $this->stats['js']++;
                break;

            case 'css':
                $cleaned = $this->removeCSSComments($source);
                $this->stats['css']++;
                break;

            case 'html':
            case 'htm':
                $cleaned = $this->removeHTMLComments($source);
                $this->stats['html']++;
                break;

            default:
                return false;
        }

        // Guardar el archivo limpio
        $result = file_put_contents($filepath, $cleaned);

        if ($result === false) {
            throw new Exception("No se pudo escribir el archivo");
        }

        return true;
    }

    /**
     * Procesa todos los archivos en un directorio
     */
    public function processDirectory($directory, $extensions = ['php', 'js', 'css', 'html', 'htm']) {
        if (!is_dir($directory)) {
            echo "ERROR: El directorio no existe: $directory\n";
            return;
        }

        echo "Procesando directorio: $directory\n";
        echo "Buscando archivos: " . implode(', ', $extensions) . "\n";
        echo str_repeat("=", 60) . "\n\n";

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = $file->getExtension();

                if (in_array($extension, $extensions)) {
                    $filepath = $file->getPathname();

                    try {
                        echo "Procesando: $filepath ... ";
                        $this->processFile($filepath);
                        $this->processedFiles++;
                        echo "✓ OK\n";
                    } catch (Exception $e) {
                        $this->errors[] = "$filepath: " . $e->getMessage();
                        echo "✗ ERROR: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }

    /**
     * Muestra el resumen de la ejecución
     */
    public function showSummary() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "RESUMEN DE EJECUCIÓN\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total de archivos procesados: {$this->processedFiles}\n";
        echo "  - PHP:  {$this->stats['php']} archivos\n";
        echo "  - JS:   {$this->stats['js']} archivos\n";
        echo "  - CSS:  {$this->stats['css']} archivos\n";
        echo "  - HTML: {$this->stats['html']} archivos\n";
        echo "\nErrores: " . count($this->errors) . "\n";

        if (!empty($this->errors)) {
            echo "\nDetalles de errores:\n";
            foreach ($this->errors as $error) {
                echo "  ✗ $error\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "¡Proceso completado! Todos los comentarios han sido eliminados.\n";
    }
}

// ============================================================================
// EJECUCIÓN DEL SCRIPT
// ============================================================================

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "  ELIMINADOR DE COMENTARIOS - Versión 2.0\n";
echo "  PHP | JavaScript | CSS | HTML\n";
echo str_repeat("=", 60) . "\n\n";

$remover = new CommentRemover();

// Directorios a procesar
$directories = [
    __DIR__ . '/php',
    __DIR__ . '/js',
    __DIR__ . '/css'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $remover->processDirectory($dir);
    }
}

// Mostrar resumen
$remover->showSummary();

echo "\n";
?>
