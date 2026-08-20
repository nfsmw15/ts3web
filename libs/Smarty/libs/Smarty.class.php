<?php
/*
*Copyright (C) 2012-2013  Psychokiller
*Copyright (C) 2026 nfsmw15 - PHP 8.x port
*
*This program is free software; you can redistribute it and/or modify it under the terms of 
*the GNU General Public License as published by the Free Software Foundation; either 
*version 3 of the License, or any later version.
*
*This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
*without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
*See the GNU General Public License for more details.
*
*You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>. 
*/
if (!class_exists('Smarty')) {

class Smarty {
    public string $template_dir   = '';
    public string $compile_dir    = '';
    public bool   $debugging      = false;
    public bool   $caching        = false;
    public bool   $force_compile  = false;
    public bool   $compile_check  = true;
    public string $left_delimiter  = '{';
    public string $right_delimiter = '}';
    public bool   $security       = false;
    public array  $autoload_filters = [];
    public array  $registered_plugins = [];
    public mixed  $error_reporting = null;

    // Shared static store so sub-templates get all vars
    private static array $_vars = [];
    private static ?Smarty $_engine = null;

    public function assign(mixed $k, mixed $v = null): void {
        if (is_array($k)) {
            foreach ($k as $key => $val) if ($key !== '') self::$_vars[$key] = $val;
        } elseif ($k !== '') {
            self::$_vars[$k] = $v;
        }
    }

    public function get_template_vars(?string $n = null): mixed {
        return $n === null ? self::$_vars : (self::$_vars[$n] ?? null);
    }

    public function clear_assign(mixed $k): void {
        foreach ((array)$k as $v) unset(self::$_vars[$v]);
    }

    public function clear_all_assign(): void { self::$_vars = []; }

    public function display(string $tpl, mixed $c = null, mixed $ci = null): void {
        echo $this->fetch($tpl, $c, $ci);
    }

    public function fetch(string $tpl, mixed $c = null, mixed $ci = null): string {
        self::$_engine = $this;
        $path = $this->_resolve($tpl);
        if (!$path) {
            trigger_error("Smarty: template not found: $tpl (base: {$this->template_dir})", E_USER_WARNING);
            return '';
        }
        $compiled = $this->_getCompiled($path);
        return self::_runFile($compiled);
    }

    public function template_exists(string $t): bool { return (bool)$this->_resolve($t); }
    public function register_function(string $f, callable $i, bool $c = true, mixed $a = null): void {}
    public function register_block(string $b, callable $i): void {}
    public function register_modifier(string $m, callable $i): void {}
    public function register_resource(string $t, array $f): void {}
    public function clear_cache(mixed $t = null, mixed $c = null, mixed $ci = null, mixed $e = null): bool { return true; }
    public function clear_compiled_tpl(mixed $t = null, mixed $c = null, mixed $e = null): bool { return true; }
    public function is_cached(string $t, mixed $c = null, mixed $ci = null): bool { return false; }
    public function load_filter(string $t, string $n): void {}
    public function register_prefilter(callable $f): void {}
    public function register_postfilter(callable $f): void {}
    public function register_outputfilter(callable $f): void {}
    public function config_load(string $f, mixed $s = null, string $sc = 'global'): void {}
    public function get_config_vars(mixed $v = null): array { return []; }

    // Static fetch for use inside compiled templates
    public static function _fetchStatic(string $tpl): string {
        if (!self::$_engine) return '';
        return self::$_engine->fetch($tpl);
    }

    private function _resolve(string $tpl): string|false {
        if (file_exists($tpl)) return $tpl;
        $base = rtrim($this->template_dir, '/\\');
        $c = $base . DIRECTORY_SEPARATOR . $tpl;
        if (file_exists($c)) return $c;
        return false;
    }

    private function _getCompiled(string $tpl): string {
        $dir = rtrim($this->compile_dir, '/\\');
        if ($dir && !is_dir($dir)) @mkdir($dir, 0755, true);
        $out = ($dir ?: sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'tpl_' . md5($tpl) . '.php';
        if ($this->force_compile || !file_exists($out)
            || ($this->compile_check && @filemtime($tpl) > @filemtime($out))) {
            file_put_contents($out, $this->_compile(file_get_contents($tpl)));
        }
        return $out;
    }

    // Run compiled file with all shared vars extracted into local scope
    private static function _runFile(string $file): string {
        ob_start();
        try {
            // Extract all vars into local scope
            extract(self::$_vars, EXTR_SKIP);
            // Make $smarty superglobal available
            $smarty = [
                'post'    => $_POST    ?? [],
                'get'     => $_GET     ?? [],
                'session' => $_SESSION ?? [],
                'server'  => $_SERVER  ?? [],
                'env'     => $_ENV     ?? [],
                'cookies' => $_COOKIE  ?? [],
                'const'   => ['SORT_ASC' => SORT_ASC, 'SORT_DESC' => SORT_DESC],
                'now'     => time(),
            ];
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            return '<!-- Smarty error in ' . basename($file) . ': ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
        $output = ob_get_clean() ?: '';
        // CSRF: auto-inject the session token as a hidden field into every
        // <form method="post"> instead of requiring every template to do it.
        if (!empty(self::$_vars['csrf_token']) && stripos($output, '<form') !== false) {
            $token = htmlspecialchars((string)self::$_vars['csrf_token'], ENT_QUOTES, 'UTF-8');
            // Negative lookahead keeps this idempotent: nested {include}s already
            // run this same injection on their own output before the outer
            // template's buffer (which contains that already-injected HTML) runs
            // it again - without the lookahead every nesting level would add
            // another duplicate hidden field to the same form.
            $output = preg_replace(
                '/(<form\b[^>]*\bmethod\s*=\s*["\']post["\'][^>]*>)(?!<input type="hidden" name="csrf_token")/i',
                '$1<input type="hidden" name="csrf_token" value="' . $token . '">',
                $output
            );
        }
        return $output;
    }

    private function _compile(string $src): string {
        $ld = preg_quote($this->left_delimiter, '#');
        $rd = preg_quote($this->right_delimiter, '#');

        // 1. Strip comments
        $src = preg_replace('#' . $ld . '\*.*?\*' . $rd . '#s', '', $src);

        // 2. Literals
        $lits = [];
        $src = preg_replace_callback('#' . $ld . 'literal' . $rd . '(.*?)' . $ld . '/literal' . $rd . '#s',
            static function($m) use (&$lits) {
                $k = '%%L' . count($lits) . '%%';
                $lits[$k] = $m[1];
                return $k;
            }, $src);

        // 3. {php}...{/php}
        $src = preg_replace_callback('#' . $ld . 'php' . $rd . '(.*?)' . $ld . '/php' . $rd . '#s',
            static fn($m) => '<?php ' . $m[1] . ' ?>', $src);

        // 4. if/elseif/else/endif
        $src = preg_replace_callback('#' . $ld . 'if\s+((?:[^{}]|\{[^{}]*\})*)' . $rd . '#',
            fn($m) => '<?php if (' . $this->_expr($m[1]) . '): ?>', $src);
        $src = preg_replace_callback('#' . $ld . 'elseif\s+((?:[^{}]|\{[^{}]*\})*)' . $rd . '#',
            fn($m) => '<?php elseif (' . $this->_expr($m[1]) . '): ?>', $src);
        $src = preg_replace('#' . $ld . 'else' . $rd . '#', '<?php else: ?>', $src);
        $src = preg_replace('#' . $ld . '/if' . $rd . '#', '<?php endif; ?>', $src);

        // 5. foreach
        $src = preg_replace_callback('#' . $ld . 'foreach\s+(.*?)' . $rd . '#s',
            fn($m) => $this->_foreach($m[1]), $src);
        $src = preg_replace('#' . $ld . 'foreachelse' . $rd . '#', '<?php endforeach; if(false): ?>', $src);
        $src = preg_replace('#' . $ld . '/foreach' . $rd . '#', '<?php endforeach; ?>', $src);

        // {while ...} and {/while}
        $src = preg_replace_callback('#' . $ld . 'while\s+(.+?)' . $rd . '#s',
            fn($m) => '<?php while (' . $this->_expr($m[1]) . '): ?>', $src);
        $src = preg_replace('#' . $ld . '/while' . $rd . '#', '<?php endwhile; ?>', $src);

        // 6. section
        $src = preg_replace_callback('#' . $ld . 'section\s+name=(\w+)\s+loop=(\$[\w.]+)(?:\s[^}]*)?' . $rd . '#s',
            fn($m) => '<?php foreach ((' . $this->_var($m[2]) . ' ?: []) as $__s_' . $m[1] . '): ?>', $src);
        $src = preg_replace('#' . $ld . '/section' . $rd . '#', '<?php endforeach; ?>', $src);

        // 7. {include file="..."} — use static fetch, resolve vars at runtime
        $src = preg_replace_callback('#' . $ld . 'include\s+file=["\']([^"\']+)["\']' . $rd . '#s',
            function($m) {
                // Build runtime path: replace {$var} with PHP var lookup from shared store
                $path = $m[1];
                $phpPath = preg_replace_callback('/\{\$(\w+)\}/',
                    fn($pm) => '\' . (Smarty::$_vars[\'' . $pm[1] . '\'] ?? \'\') . \'',
                    $path);
                return '<?php echo Smarty::_fetchStatic(\'' . $phpPath . '\'); ?>';
            }, $src);

        // 8. assign
        $src = preg_replace_callback('#' . $ld . 'assign\s+var=["\']?(\w+)["\']?\s+value=(["\'][^"\']*["\']|\$[\w.`+]+|\w+)' . $rd . '#s',
            fn($m) => '<?php Smarty::$_vars[\'' . $m[1] . '\'] = $' . $m[1] . ' = ' . $this->_valOrVar($m[2]) . '; ?>', $src);

        // 9. {$smarty.X.Y} direct output
        $sup = ['post'=>'_POST','get'=>'_GET','session'=>'_SESSION','server'=>'_SERVER','env'=>'_ENV','cookies'=>'_COOKIE'];
        $src = preg_replace_callback('#' . $ld . '\$smarty\.(post|get|session|server|env|cookies)\.([\w.]+)' . $rd . '#s',
            fn($m) => '<?php echo htmlspecialchars((string)($' . $sup[$m[1]] . '[\'' . $m[2] . '\'] ?? \'\'), ENT_QUOTES, \'UTF-8\'); ?>', $src);
        $src = preg_replace_callback('#' . $ld . '\$smarty\.const\.(\w+)' . $rd . '#s',
            fn($m) => '<?php echo ' . $m[1] . '; ?>', $src);
        $src = preg_replace('#' . $ld . '\$smarty\.now' . $rd . '#', '<?php echo time(); ?>', $src);

        // 10. {$var|mod}
        $src = preg_replace_callback('#' . $ld . '(\$[\w.\[\]\'\"]+(?:\|[^}]*)?)' . $rd . '#s',
            fn($m) => '<?php echo ' . $this->_varMod($m[1]) . '; ?>', $src);

        // Restore literals
        foreach ($lits as $k => $v) $src = str_replace($k, $v, $src);

        return "<?php /* compiled */ ?>\n" . $src;
    }

    private function _foreach(string $p): string {
        $from = $item = $key = null;
        if (preg_match('/from=(\$[\w.]+)/', $p, $m)) $from = $m[1];
        if (preg_match('/item=["\']?(\w+)["\']?/', $p, $m)) $item = $m[1];
        if (preg_match('/key=["\']?(\w+)["\']?/', $p, $m)) $key = $m[1];
        if ($from && $item) {
            $kp = $key ? '$' . $key . ' => ' : '';
            return '<?php foreach ((' . $this->_var($from) . ' ?: []) as ' . $kp . '$' . $item . '): ?>';
        }
        if (preg_match('/(\$[\w.]+)\s+as\s+(\$\w+(?:\s*=>\s*\$\w+)?)/', $p, $m))
            return '<?php foreach ((' . $this->_var($m[1]) . ' ?: []) as ' . $m[2] . '): ?>';
        return '<?php if(false): /* bad foreach */ ?>';
    }

    private function _expr(string $e): string {
        $sup = ['post'=>'_POST','get'=>'_GET','session'=>'_SESSION','server'=>'_SERVER','env'=>'_ENV','cookies'=>'_COOKIE'];
        // isset($smarty.X.Y)
        $e = preg_replace_callback('/(!?)isset\(\s*\$smarty\.(post|get|session|server|env|cookies)\.([\w.]+)\s*\)/',
            fn($m) => $m[1] . 'isset($_' . ltrim($sup[$m[2]], '_') . "['" . $m[3] . "'])", $e);
        // $smarty.X.Y
        $e = preg_replace_callback('/\$smarty\.(post|get|session|server|env|cookies)\.([\w.]+)/',
            function($m) use ($sup) {
                $parts = explode('.', $m[2]);
                $out = '$_' . ltrim($sup[$m[1]], '_');
                foreach ($parts as $p) $out .= "['" . $p . "']";
                return '(' . $out . ' ?? null)';
            }, $e);
        $e = preg_replace_callback('/\$smarty\.const\.(\w+)/', fn($m) => $m[1], $e);
        $e = str_replace('$smarty.now', 'time()', $e);
        // $foo.bar
        $e = preg_replace_callback('/\$(\w+)\.(\w+)/', fn($m) => '$' . $m[1] . "['" . $m[2] . "']", $e);
        // operators
        foreach (['/\beq\b/'=>'==','/\bneq\b|\bne\b/'=>'!=','/\blt\b/'=>'<','/\bgt\b/'=>'>',
                  '/\blte\b|\ble\b/'=>'<=','/\bgte\b|\bge\b/'=>'>=',
                  '/\bnot\b/'=>'!','/\band\b/'=>'&&','/\bor\b/'=>'||','/\bmod\b/'=>'%'] as $p=>$r)
            $e = preg_replace($p, $r, $e);
        return $e;
    }

    private function _var(string $v): string {
        $v = trim($v);
        // If already contains quoted array access like $lang['error'], return as-is
        if (preg_match('/^\$\w+(\[[\'"]\w+[\'"]\])+$/', $v)) return $v;
        $sup = ['post'=>'_POST','get'=>'_GET','session'=>'_SESSION','server'=>'_SERVER','env'=>'_ENV','cookies'=>'_COOKIE'];
        if (preg_match('/^\$smarty\.(post|get|session|server|env|cookies)\.(.+)$/', $v, $m)) {
            $parts = explode('.', $m[2]);
            $out = '$_' . ltrim($sup[$m[1]], '_');
            foreach ($parts as $p) $out .= "['" . $p . "']";
            return '(' . $out . ' ?? null)';
        }
        if (preg_match('/^\$smarty\.const\.(\w+)$/', $v, $m)) return $m[1];
        if ($v === '$smarty.now') return 'time()';
        if (preg_match('/^\$(\w+)\.(.+)$/', $v, $m)) {
            $out = '$' . $m[1];
            foreach (explode('.', $m[2]) as $p) {
                if (is_numeric($p)) $out .= '[' . $p . ']';
                else $out .= "['" . $p . "']";
            }
            return $out;
        }
        return $v;
    }

    private function _valOrVar(string $v): string {
        $v = trim($v);
        if (str_starts_with($v, '$')) return $this->_var($v);
        if (str_starts_with($v, '`') && str_ends_with($v, '`')) {
            // backtick expression like `$change_col+1`
            $inner = substr($v, 1, -1);
            return '(int)(' . $this->_expr($inner) . ')';
        }
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'")) return $v;
        return '"' . $v . '"';
    }

    private function _varMod(string $expr): string {
        $parts = $this->_splitPipe($expr);
        $var = $this->_var(array_shift($parts));
        $result = $var;
        foreach ($parts as $mod) {
            $mod = trim($mod); if (!$mod) continue;
            $mp = explode(':', $mod); $mn = array_shift($mp);
            $args = array_map(fn($a) => $this->_valOrVar(trim($a)), $mp);
            switch ($mn) {
                case 'escape':
                    $t = trim($args[0] ?? '"html"', '"\'');
                    $result = match($t) {
                        'url' => 'urlencode((string)(' . $result . '))',
                        'javascript' => 'addslashes((string)(' . $result . '))',
                        default => 'htmlspecialchars((string)(' . $result . '), ENT_QUOTES, "UTF-8")'
                    };
                    break;
                case 'addslashes':    $result = 'addslashes((string)(' . $result . '))'; break;
                case 'nl2br':         $result = 'nl2br((string)(' . $result . '))'; break;
                case 'upper':         $result = 'strtoupper((string)(' . $result . '))'; break;
                case 'lower':         $result = 'strtolower((string)(' . $result . '))'; break;
                case 'capitalize':    $result = 'ucwords((string)(' . $result . '))'; break;
                case 'strip_tags':    $result = 'strip_tags((string)(' . $result . '))'; break;
                case 'trim':          $result = 'trim((string)(' . $result . '))'; break;
                case 'count_words':   $result = 'str_word_count((string)(' . $result . '))'; break;
                case 'count_characters': $result = 'strlen((string)(' . $result . '))'; break;
                case 'default':       $result = '(!empty(' . $var . ') ? (' . $result . ') : ' . ($args[0] ?? "''") . ')'; break;
                case 'truncate':
                    $result = '(mb_strlen((string)(' . $result . '))>' . ($args[0] ?? '80')
                            . '?mb_substr((string)(' . $result . '),0,' . ($args[0] ?? '80') . ').'
                            . ($args[1] ?? '"..."') . ':(' . $result . '))';
                    break;
                case 'replace':       $result = 'str_replace(' . ($args[0]??"''") . ',' . ($args[1]??"''") . ',(string)(' . $result . '))'; break;
                case 'date_format':
                    // Join all args back - format string may contain colons
                    $fmt_raw = implode(':', $mp);
                    // Strip surrounding quotes
                    $fmt_raw = trim($fmt_raw, '"\' ');
                    // Convert strftime format to date() format
                    $fmt_raw = str_replace(['%d','%m','%Y','%H','%M','%S','%y','%e','%b'], ['d','m','Y','H','i','s','y','j','M'], $fmt_raw);
                    if (empty($fmt_raw)) $fmt_raw = 'Y-m-d';
                    $result = 'date("' . $fmt_raw . '",is_numeric(' . $var . ')?(int)(' . $var . '):strtotime((string)(' . $var . ')))';
                    break;
                case 'number_format': $result = 'number_format((float)(' . $result . '),' . ($args[0]??'0') . ')'; break;
                case 'cat':           $result = '((string)(' . $result . ').' . ($args[0]??"''") . ')'; break;
                case 'string_format': $result = 'sprintf(' . ($args[0]??'"%s"') . ',' . $result . ')'; break;
                case 'wordwrap':      $result = 'wordwrap((string)(' . $result . '),' . ($args[0]??'80') . ')'; break;
                default:
                    $as = $args ? ',' . implode(',', $args) : '';
                    $result = '(function_exists("smarty_modifier_' . $mn . '")?smarty_modifier_' . $mn . '(' . $result . $as . '):(string)(' . $result . '))';
            }
        }
        return $result;
    }

    private function _splitPipe(string $s): array {
        $parts = []; $cur = ''; $in = false; $ch = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];
            if ($in) { $cur .= $c; if ($c === $ch && ($i === 0 || $s[$i-1] !== '\\')) $in = false; }
            elseif ($c === '"' || $c === "'") { $in = true; $ch = $c; $cur .= $c; }
            elseif ($c === '|') { $parts[] = $cur; $cur = ''; }
            else $cur .= $c;
        }
        if ($cur !== '') $parts[] = $cur;
        return $parts;
    }

    public function __set(string $n, mixed $v): void { $this->$n = $v; }
}

} // end if !class_exists
