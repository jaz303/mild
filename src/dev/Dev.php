<?php
namespace jaz303\mild\dev;

class Dev {
    public static function renderDockerConfig($rootDir) {
        $devDir = "$rootDir/.mild/dev";
        if (!is_dir($devDir)) {
            mkdir($devDir, 0755, true);
        }
        
        if (!file_exists("$devDir/supervisord.conf")) {
            copy(__DIR__ . "/supervisord.conf", "$devDir/supervisord.conf");
        }

        if (!file_exists("$devDir/nginx.conf")) {
            copy(__DIR__ . "/nginx.conf", "$devDir/nginx.conf");
        }

        if (!file_exists("$devDir/Dockerfile")) {
            copy(__DIR__ . "/Dockerfile", "$devDir/Dockerfile");
        } 
    }
}
