<?php
namespace jaz303\mild;

class PageContext {
    public function __construct(public $path, public $dir, public $page) {

    }

    public function findSource() {
        $sources = glob($this->dir . 'index.source.*');
        if (empty($sources)) {
            return [null, null];
        }

        $source = $sources[0];
        return [$source, pathinfo($source)['extension']];
    }
}
