<?php
namespace jaz303\mild\render;

class HtmlRenderer extends BaseRenderer {
    public function __construct($cfg, $ctx, $sourceFile) {
        parent::__construct($cfg, $ctx, $sourceFile);
    }

    public function render() {
        ob_start();
        readfile($this->sourceFile);
        $html = ob_get_clean();

        echo "<h1>" . htmlspecialchars($this->ctx->page->title) . "</h1>";
        echo $html;
    }
}
