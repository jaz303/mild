<?php
namespace jaz303\mild\handler;

class Page {
    public function __construct(public $cfg) {}

    public function start() {
        $ctx = Common::initPageContext($this->cfg);
        if (!$ctx->page) {
            die("page not found");
        }

        list($sourceFile, $sourceExt) = $ctx->findSource();
        if (!$sourceFile) {
            die("page source not found");
        }

        $renderer = $this->cfg->createPageRenderer($sourceExt, $ctx, $sourceFile);

        $cacher = \jaz303\mild\Cacher::fromConfig($this->cfg, $ctx->dir);
        $cacher->start();
        $renderer->render();
        $cacher->end();
    }
}
