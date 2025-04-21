<?php
namespace jaz303\mild\handler;

class Common {
    public static function initPageContext($cfg) {
        $url = parse_url($_SERVER['REQUEST_URI']);
        if (!str_ends_with($url['path'], '/')) {
            die("no trailing slash");
        }

        $path = $url['path'];
        if (strpos($path, '..') !== false) {
            die("no");
        }

        $page = null;
        try {
            $page = $cfg->index()->findByPath($path);
        } catch (\Exception $e) {}

        $dir = $_SERVER['DOCUMENT_ROOT'] . $path;

        return new \jaz303\mild\PageContext($path, $dir, $page);
    }
}
