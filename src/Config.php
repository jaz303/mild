<?php
namespace jaz303\mild;

class Config {
    public static function fromEnv() {
        $config = new self;
        
        $config->env = defined('MILD_ENV') ? MILD_ENV : "development";
        
        $config->offsiteDir = MILD_OFFSITE_ROOT;
        $config->htmlDir = realpath(MILD_OFFSITE_ROOT . "/../html");
        
        $config->cacheDisabled = $config->env === "development";
        if (defined('MILD_DISABLE_CACHE') && MILD_DISABLE_CACHE) {
            $config->cacheDisabled = true;
        }

        return $config;
    }

    public $env;
    private $offsiteDir;
    private $htmlDir;
    private $cacheDisabled;
    
    private $handlers = [
        'page' => 'jaz303\mild\handler\Page',
        'script' => 'jaz303\mild\handler\Script',
    ];
    
    private $renderers = [
        'html' => 'jaz303\mild\render\HtmlRenderer'
    ];

    private $index = null;

    public function __construct() {}

    public function databasePath() { return $this->offsiteDir . "/db/index.db"; }
    public function isCacheEnabled() { return !$this->cacheDisabled; }

    public function start() {
        if (defined('MILD_HANDLER')) {
            $hnd = $this->createHandler(MILD_HANDLER);
            $hnd->start();
            return $hnd;
        } else {
            return $this;
        }
    }
        
    //
    // Indexing

    public function createIndexer() {
        return new Indexer($this->htmlDir, $this->databasePath());
    }
    
    public function index() {
        if ($this->index === null) {
            $this->index = new Index($this->databasePath());
        }
        return $this->index;
    }

    //
    // Handlers

    public function createHandler($handler) {
        if (!isset($this->handlers[$handler])) {
            throw new \Exception("Handler not found: $handler");
        }
        return new ($this->handlers[$handler])($this);
    }

    //
    // Renderers

    public function registerPageRenderer($type, $class) {
        if (isset($this->renderers[$type])) {
            throw new \Exception("Renderer already registered: $type");
        }
        $this->renderers[$type] = $class;
    }
    
    public function createPageRenderer($type, $ctx, $sourceFile) {
        if (!isset($this->renderers[$type])) {
            throw new \Exception("Renderer not found: $type");
        }
        return new ($this->renderers[$type])($this, $ctx, $sourceFile);
    }
}
