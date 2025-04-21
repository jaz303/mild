<?php
namespace jaz303\mild;

class Cacher_Null {
    public function __construct() {}
    public function start() {}
    public function end() {}
}

class Cacher_FS {
    public function __construct(private $targetDir) {}

    public function start() {
        ob_start();
    }

    public function end() {
        $html = ob_get_clean();
        file_put_contents($this->targetDir . '/index.html', $html);
        echo $html;
    }
}

class Cacher {
    public static function fromConfig($cfg, $targetDir) {
        if ($cfg->isCacheEnabled()) {
            return new Cacher_FS($targetDir);
        } else {
            return new Cacher_Null();
        }
    }
}
