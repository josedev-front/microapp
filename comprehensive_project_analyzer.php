<?php
// comprehensive_project_analyzer.php
echo "🔍 ANALIZADOR COMPLETO DEL PROYECTO\n";
echo "===================================\n\n";

class ProjectAnalyzer {
    private $projectRoot;
    private $excludedDirs = ['vendor', 'node_modules', '.git', 'data/cache'];
    private $fileStats = [];
    private $issues = [];

    public function __construct($rootPath = null) {
        $this->projectRoot = $rootPath ?: $this->findProjectRoot();
    }

    public function fullAnalysis() {
        $this->findProjectRoot();
        $this->showFileStructure();
        $this->analyzeDependencies();
        $this->checkCommonIssues();
        $this->showIssues();
        $this->generateSummary();
    }

    private function findProjectRoot() {
        $possibleRoots = [
            dirname(__DIR__, 3), // 3 niveles arriba
            dirname(__DIR__, 2), // 2 niveles arriba  
            dirname(__DIR__),    // 1 nivel arriba
            __DIR__,             // Directorio actual
        ];

        foreach ($possibleRoots as $path) {
            if (file_exists($path . '/composer.json') || 
                file_exists($path . '/vendor/autoload.php') ||
                is_dir($path . '/app_core')) {
                $this->projectRoot = $path;
                echo "🎯 RAÍZ DEL PROYECTO ENCONTRADA: $path\n\n";
                return $path;
            }
        }

        $this->projectRoot = __DIR__;
        echo "⚠️  No se pudo determinar la raíz, usando directorio actual\n\n";
        return __DIR__;
    }

    public function showFileStructure($maxDepth = 4) {
        echo "📁 ESTRUCTURA COMPLETA DEL PROYECTO:\n";
        echo "====================================\n";
        
        $this->scanDirectory($this->projectRoot, 0, $maxDepth);
        
        echo "\n";
    }

    private function scanDirectory($path, $level = 0, $maxDepth = 4) {
        if (!is_dir($path) || $level > $maxDepth) return;

        $items = scandir($path);
        $files = [];
        $dirs = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            // Verificar si está excluido
            $relativePath = str_replace($this->projectRoot, '', $path . '/' . $item);
            if ($this->isExcluded($relativePath)) continue;

            $fullPath = $path . '/' . $item;
            
            if (is_dir($fullPath)) {
                $dirs[] = $item;
            } else {
                $files[] = $item;
                $this->collectFileStats($fullPath);
            }
        }

        // Mostrar directorios primero
        foreach ($dirs as $item) {
            $fullPath = $path . '/' . $item;
            $indent = str_repeat('  ', $level);
            echo $indent . "📁 " . $item . "/\n";
            $this->scanDirectory($fullPath, $level + 1, $maxDepth);
        }

        // Mostrar archivos después
        foreach ($files as $item) {
            $fullPath = $path . '/' . $item;
            $indent = str_repeat('  ', $level);
            $size = filesize($fullPath);
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            echo $indent . "📄 " . $item . " (" . $this->formatSize($size) . ")\n";
        }
    }

    private function isExcluded($path) {
        foreach ($this->excludedDirs as $excluded) {
            if (strpos($path, $excluded) !== false) {
                return true;
            }
        }
        return false;
    }

    private function collectFileStats($filePath) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $size = filesize($filePath);
        
        if (!isset($this->fileStats[$ext])) {
            $this->fileStats[$ext] = ['count' => 0, 'totalSize' => 0];
        }
        
        $this->fileStats[$ext]['count']++;
        $this->fileStats[$ext]['totalSize'] += $size;
    }

    private function analyzeDependencies() {
        echo "📦 ANÁLISIS DE DEPENDENCIAS:\n";
        echo "============================\n";

        // Composer
        $composerJson = $this->projectRoot . '/composer.json';
        if (file_exists($composerJson)) {
            $composer = json_decode(file_get_contents($composerJson), true);
            echo "✅ composer.json encontrado\n";
            
            if (isset($composer['require'])) {
                echo "   Dependencias: " . count($composer['require']) . " paquetes\n";
                foreach ($composer['require'] as $pkg => $version) {
                    echo "   - $pkg: $version\n";
                }
            }
        } else {
            echo "❌ composer.json no encontrado\n";
            $this->issues[] = "Falta composer.json";
        }

        // Autoload
        $autoload = $this->projectRoot . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            echo "✅ vendor/autoload.php encontrado\n";
        } else {
            echo "❌ vendor/autoload.php no encontrado\n";
            $this->issues[] = "Falta vendor/autoload.php - Ejecutar 'composer install'";
        }

        echo "\n";
    }

    private function checkCommonIssues() {
        echo "🔧 VERIFICACIÓN DE PROBLEMAS COMUNES:\n";
        echo "====================================\n";

        // Verificar archivos críticos
        $criticalFiles = [
            '.env' => 'Archivo de configuración de entorno',
            'routes.php' => 'Configuración de rutas',
        ];

        foreach ($criticalFiles as $file => $desc) {
            if (file_exists($this->projectRoot . '/' . $file)) {
                echo "✅ $file: $desc\n";
            } else {
                echo "❌ $file: NO ENCONTRADO ($desc)\n";
                $this->issues[] = "Falta archivo crítico: $file";
            }
        }

        // Verificar permisos de directorios de escritura
        $writableDirs = ['data/cache', 'data/uploads', 'data/rate_limits'];
        foreach ($writableDirs as $dir) {
            $fullPath = $this->projectRoot . '/' . $dir;
            if (is_dir($fullPath) && !is_writable($fullPath)) {
                echo "❌ $dir: Sin permisos de escritura\n";
                $this->issues[] = "Directorio sin permisos de escritura: $dir";
            }
        }

        // Buscar includes/requires problemáticos
        $this->checkPhpIssues();
    }

    private function checkPhpIssues() {
        $phpFiles = $this->getPhpFiles();
        
        foreach ($phpFiles as $phpFile) {
            $content = file_get_contents($phpFile);
            
            // Buscar includes con rutas absolutas problemáticas
            if (preg_match_all('/require.*[\'"](\\/|[a-zA-Z]:\\\\)/', $content, $matches)) {
                $this->issues[] = "Posible ruta absoluta en: " . basename($phpFile);
            }
            
            // Buscar funciones deprecated
            $deprecated = ['mysql_', 'ereg_', 'split\('];
            foreach ($deprecated as $deprecatedFunc) {
                if (strpos($content, $deprecatedFunc) !== false) {
                    $this->issues[] = "Funcióndeprecated en: " . basename($phpFile);
                }
            }
        }
    }

    private function getPhpFiles() {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        
        return $phpFiles;
    }

    private function showIssues() {
        if (empty($this->issues)) {
            echo "✅ No se encontraron problemas críticos\n\n";
            return;
        }

        echo "🚨 PROBLEMAS IDENTIFICADOS:\n";
        echo "==========================\n";
        foreach ($this->issues as $i => $issue) {
            echo ($i + 1) . ". $issue\n";
        }
        echo "\n";
    }

    private function generateSummary() {
        echo "📊 RESUMEN DEL PROYECTO:\n";
        echo "========================\n";
        
        echo "📍 Raíz del proyecto: " . $this->projectRoot . "\n";
        echo "📅 Análisis generado: " . date('Y-m-d H:i:s') . "\n\n";

        if (!empty($this->fileStats)) {
            echo "ESTADÍSTICAS DE ARCHIVOS:\n";
            foreach ($this->fileStats as $ext => $stats) {
                echo "  .$ext: {$stats['count']} archivos (" . $this->formatSize($stats['totalSize']) . ")\n";
            }
        }

        echo "\n¿Qué problema específico necesitas resolver? Puedo ayudarte con:\n";
        echo "• Configuración de rutas\n• Problemas de autoloading\n• Errores de inclusión de archivos\n• Configuración de middleware\n• Problemas con dependencias\n";
    }

    private function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}

// Ejecutar análisis
$analyzer = new ProjectAnalyzer();
$analyzer->fullAnalysis();