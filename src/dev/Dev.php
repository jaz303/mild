<?php
namespace jaz303\mild\dev;

class Dev {
    public static function renderDockerConfig($rootDir) {
        $devDir = "$rootDir/.mild/dev";
        if (!is_dir($devDir)) {
            mkdir($devDir, 0755, true);
        }

        $manifest = [
            "supervisord.conf",
            "nginx.conf",
            "Dockerfile"
        ];

        foreach ($manifest as $f) {
            if (!file_exists("$devDir/$f")) {
                copy(__DIR__ . "/$f", "$devDir/$f");
            }
        }
    }
}
