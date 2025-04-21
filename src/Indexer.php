<?php
namespace jaz303\mild;

class Indexer {
    private $db;

    public function __construct(private $root, private $databasePath) {
    }

    public function index() {
        $this->openDatabase();
        $this->createSchema();
        $this->db->exec("DELETE FROM page");
        $this->db->exec("DELETE FROM page_category");
        $this->db->exec("DELETE FROM page_tag");
        $this->scanDirectory($this->root, '/', 1);
    }

    private function openDatabase() {
        $dbDir = dirname($this->databasePath);
        if (!is_dir($dbDir)) {
            if (!mkdir($dbDir, 0777, true)) {
                throw new \Exception("Cannot create database directory $dbDir");
            }
        }
        $this->db = new \PDO("sqlite:" . $this->databasePath);
    }

    private function scanDirectory($dir, $pagePath, $depth) {
        $meta = $this->tryGetPageMeta($dir, $pagePath, $depth);
        if ($meta) {
            $this->insertPage($meta);
        }
        $this->scanChildren($dir, $pagePath, $depth);
    }

    private function scanChildren($dir, $pagePath, $depth) {
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subdir) {
            $basename = basename($subdir);
            $this->scanDirectory($subdir, $pagePath . $basename . '/', $depth + 1);
        }
    }

    private function createSchema() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS page (
                id integer primary key,
                path text not null,
                depth integer not null,
                title text not null,
                published_at integer not null,
                perma_id text null,
                series text null,
                collection text null,
                meta text not null
            )
        ");

        $this->db->exec("CREATE UNIQUE INDEX IF NOT EXISTS page_path ON page (path)");
        $this->db->exec("CREATE UNIQUE INDEX IF NOT EXISTS page_perma_id ON page (perma_id)");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS page_category (
                page_id integer,
                category text not null,
                primary key (page_id, category)
            )
        ");

        $this->db->exec("CREATE INDEX IF NOT EXISTS page_category_category ON page_category (category)");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS page_tag (
                page_id integer,
                tag text not null,
                primary key (page_id, tag)
            )
        ");

        $this->db->exec("CREATE INDEX IF NOT EXISTS page_tag_tag ON page_tag (tag)");
    }

    private function tryGetPageMeta($dir, $path, $depth) {
        $candidate = $dir . '/page.meta';
        $json = @file_get_contents($candidate);
        if ($json === false) {
            return false;
        }

        if (trim($json) === "") {
            $json = "{}";
        }

        $json = json_decode($json);
        if (!$json) {
            throw new \Exception("invalid JSON in $candidate");
        }

        $meta = new PageMeta();
        $meta->path = $path;
        $meta->depth = $depth;
        $meta->title = $json->title ?? "Untitled Page";
        $meta->publishedAt = new \DateTimeImmutable($json->publishedAt ?? "now");
        $meta->series = $json->series ?? null;
        $meta->collection = $json->collection ?? null;
        $meta->permaID = $json->permaID ?? null;
        $meta->categories = $json->categories ?? [];
        $meta->tags = $json->tags ?? [];

        unset($json->title);
        unset($json->publishedAt);
        unset($json->series);
        unset($json->collection);
        unset($json->permaID);
        unset($json->categories);
        unset($json->tags);

        $meta->meta = $json;

        return $meta;
    }

    private function insertPage(PageMeta $meta) {
        $stmt = $this->db->prepare("
            INSERT INTO page
                (path, depth, title, published_at, perma_id, series, collection, meta)
            VALUES
                (:path, :depth, :title, :published_at, :perma_id, :series, :collection, :meta)
            RETURNING id
        ");

        $stmt->execute([
            'path' => $meta->path,
            'depth' => $meta->depth,
            'title' => $meta->title,
            'published_at' => $meta->publishedAt->getTimestamp(),
            'perma_id' => $meta->permaID,
            'series' => $meta->series,
            'collection' => $meta->collection,
            'meta' => json_encode($meta->meta)
        ]);

        $id = $stmt->fetchColumn();
        
        $stmt = $this->db->prepare("INSERT INTO page_category (page_id, category) VALUES (:page_id, :category)");
        foreach ($meta->categories as $cat) {
            $stmt->execute(['page_id' => $id, 'category' => $cat]);
        }

        $stmt = $this->db->prepare("INSERT INTO page_tag (page_id, tag) VALUES (:page_id, :tag)");
        foreach ($meta->tags as $tag) {
            $stmt->execute(['page_id' => $id, 'tag' => $tag]);
        }
    }
}
