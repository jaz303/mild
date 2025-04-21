<?php
namespace jaz303\mild\render;

class BaseRenderer {
    public function __construct(protected $cfg, protected $ctx, protected $sourceFile) {
    }
}
