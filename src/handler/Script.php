<?php
namespace jaz303\mild\handler;

class Script {
    public function __construct(public $cfg) {}
    
    public function start() {
       $ctx = Common::initPageContext($this->cfg);

       $cacher = jaz303\mild\Cacher::fromConfig($this->cfg, $this->dir);
       $cacher->start();
       register_shutdown_function(function() use ($cacher) {
           $cacher->end();
       }); 
    }
}
