<?php
namespace jaz303\mild;

class Script {
    public static function invoke($cfg, $args) {
        switch ($args[0]) {
            case "index":
                self::index($cfg);
                break;
            case "build":
                self::build();
                break;
            case "serve":
                $port = isset($args[1]) ? ((int)$args[1]) : 8888;
                self::serve($port);
                break;
        }
    }

    //
    // Indexing

    public static function index($cfg) {
        $indexer = $cfg->createIndexer();
        $indexer->index();
    }
    
    //
    // Docker dev stuff

    public static function build() {
        \jaz303\mild\dev\Dev::renderDockerConfig(".");
        $dir = md5(getcwd());
        $tag = self::getDockerTag();
        passthru("docker build -t $tag -f .mild/dev/Dockerfile .");
    }

    public static function serve($port) {
        $tag = self::getDockerTag();
        $pwd = getcwd();
        passthru(implode(" ", [
            "docker run -it",
            "-p 127.0.0.1:$port:8888",
            "-v $pwd/html:/var/www/html",
            "-v $pwd/offsite:/var/www/offsite",
            $tag
        ]));
    }
    
    private static function getDockerTag() {
        $dir = md5(getcwd());
        return "mild/dev/$dir/latest";
    }
}
