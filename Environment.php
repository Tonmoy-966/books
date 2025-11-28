<?php
namespace Twig;

class Environment {
    private $loader;

    public function __construct($loader){
        $this->loader = $loader;
    }


    private function isAssoc(array $arr) {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

 
    private function valueToString($value) {
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_array($value)) {
            $parts = [];
            $assoc = $this->isAssoc($value);
            foreach ($value as $k => $v) {
                $vStr = $this->valueToString($v);
                if ($assoc) {
                    $parts[] = $k . ': ' . $vStr;
                } else {
                    $parts[] = $vStr;
                }
            }
            return implode(', ', $parts);
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if ($value === null) return '';

        return (string) $value;
    }

    private function escapeForHtml($string) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    
    private function resolveVariable($name, $vars, $localScope = []) {
        $parts = explode('.', $name);
        if (count($parts) > 0 && array_key_exists($parts[0], $localScope)) {
            $value = $localScope[$parts[0]];
        } elseif (array_key_exists($parts[0], $vars)) {
            $value = $vars[$parts[0]];
        } else {
            return null;
        }

        for ($i = 1; $i < count($parts); $i++) {
            $p = $parts[$i];
            if (is_array($value) && array_key_exists($p, $value)) {
                $value = $value[$p];
            } elseif (is_object($value) && isset($value->$p)) {
                $value = $value->$p;
            } else {
                return null;
            }
        }
        return $value;
    }


    private function extractBlocks($source) {
        $blocks = [];
        $pattern = '/\{\%\s*block\s+([a-zA-Z0-9_]+)\s*\%\}(.*?)\{\%\s*endblock\s*\%\}/s';
        if (preg_match_all($pattern, $source, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $blocks[$match[1]] = $match[2];
            }
        }
        return $blocks;
    }

    private function applyBlocksToParent($parentSource, $childBlocks) {
        $pattern = '/\{\%\s*block\s+([a-zA-Z0-9_]+)\s*\%\}(.*?)\{\%\s*endblock\s*\%\}/s';
        return preg_replace_callback($pattern, function($m) use ($childBlocks) {
            $name = $m[1];
            if (array_key_exists($name, $childBlocks)) {
                return $childBlocks[$name];
            }
            return $m[2];
        }, $parentSource);
    }

    private function processIncludes($source) {
        $pattern = '/\{\%\s*include\s+[\'"](.+?)[\'"]\s*\%\}/';
        return preg_replace_callback($pattern, function($m) {
            $name = $m[1];
            try {
                return $this->loader->getSource($name);
            } catch (\Exception $e) {
                return '';
            }
        }, $source);
    }

    private function processIfs($content, $vars, $localScope = []) {
        $pattern = '/\{\%\s*if\s+([a-zA-Z0-9_\.]+)\s*\%\}(.*?)((\{\%\s*else\s*\%\}(.*?))?\{\%\s*endif\s*\%\})/s';
        while (preg_match($pattern, $content)) {
            $content = preg_replace_callback($pattern, function($m) use ($vars, $localScope) {
                $condName = $m[1];
                $body = $m[2];
                $elsePart = '';
                if (isset($m[5])) $elsePart = $m[5];

                $val = $this->resolveVariable($condName, $vars, $localScope);
                $truthy = $this->isTruthy($val);

                return $truthy ? $body : $elsePart;
            }, $content, 1);
        }
        return $content;
    }

    private function isTruthy($val) {
        if ($val === null) return false;
        if ($val === false) return false;
        if ($val === '') return false;
        if (is_array($val) && count($val) === 0) return false;
        return true;
    }

    private function processFors($content, $vars) {
        $pattern = '/\{\%\s*for\s+([a-zA-Z0-9_]+)\s+in\s+([a-zA-Z0-9_\.]+)\s*\%\}(.*?)\{\%\s*endfor\s*\%\}/s';
        while (preg_match($pattern, $content)) {
            $content = preg_replace_callback($pattern, function($m) use ($vars) {
                $itemName = $m[1];
                $listName = $m[2];
                $body = $m[3];

                $list = $this->resolveVariable($listName, $vars, []);
                if (!is_array($list)) {
                    return '';
                }

                $out = '';
                foreach ($list as $item) {
                    $localScope = [$itemName => $item];
                    $processed = $this->processIfs($body, $vars, $localScope);
                    $processed = $this->processFors($processed, $vars + $localScope);
                    $processed = $this->replaceVariablesInString($processed, $vars, $localScope);
                    $out .= $processed;
                }
                return $out;
            }, $content, 1);
        }
        return $content;
    }

    private function replaceVariablesInString($content, $vars, $localScope = []) {
        $pattern = '/\{\{\s*([a-zA-Z0-9_\.]+)(\s*\|\s*default\s*\(\s*(["\'])(.*?)\3\s*\)\s*)?\s*\}\}/';
        return preg_replace_callback($pattern, function($m) use ($vars, $localScope) {
            $name = $m[1];
            $hasDefault = isset($m[2]) && $m[2] !== '';
            $defaultVal = null;
            if ($hasDefault && isset($m[4])) {
                $defaultVal = $m[4];
            }

            $val = $this->resolveVariable($name, $vars, $localScope);

            if (($val === null || $val === '') && $hasDefault) {
                $out = $defaultVal;
            } else {
                $out = $this->valueToString($val);
            }

            return $this->escapeForHtml($out);
        }, $content);
    }

    
    public function render($template, $vars = []) {
        $source = $this->loader->getSource($template);

        
        $source = $this->processIncludes($source);

        if (preg_match('/\{\%\s*extends\s+[\'"](.+?)[\'"]\s*\%\}/', $source, $m)) {
            $parentName = $m[1];
            $parentSource = $this->loader->getSource($parentName);
            $parentSource = $this->processIncludes($parentSource);
            $childBlocks = $this->extractBlocks($source);
            $source = $this->applyBlocksToParent($parentSource, $childBlocks);
        }

        $source = $this->processIncludes($source);

        $source = $this->processIfs($source, $vars);

        $source = $this->processFors($source, $vars);

        $source = $this->replaceVariablesInString($source, $vars);

        return $source;
    }
}
