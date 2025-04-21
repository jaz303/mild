<?php
namespace jaz303\mild;

class Index {
    private $db;
    
    public function __construct(private $dbFile) {}

    public function findRoot() { return $this->findByPath('/'); }
    public function findByPath($path) { return $this->findOneWithConditions(['path' => $path]); }
    public function findByPermaID($permaID) { return $this->findOneWithConditions(['permaID' => $permaID]); }

    public function findSeries($seriesOrPage) {
        $series = ($seriesOrPage instanceof PageMeta) ? $seriesOrPage->series : ((string)$seriesOrPage);
        return $this->findAllWithConditions(['series' => $series, 'order' => 'published_at ASC']);
    }

    public function findPrevAndNextInSeries($page) {
        $all = $this->findSeries($page);
        for ($i = 0; $i < count($all); $i++) {
            if ($all[$i]->id === $page->id) {
                $prev = ($i > 0) ? $all[$i-1] : null;
                $next = ($i < (count($all)-1)) ? $all[$i+1] : null;
            }
            return ['prev' => $prev, 'next' => $next];
        }
    }

    public function findChildren($pathOrPage) {
        $path = ($pathOrPage instanceof PageMeta) ? $pathOrPage->path : ((string)$pathOrPage);
        $depth = substr_count($path, '/');
        return $this->findAllWithConditions(['pathPrefix' => $path, 'depth' => $depth + 1]);
    }

    public function findByCollection($collection, $opts = []) {
        return $this->findAllWithConditions(['collection' => $collection] + $opts);
    }

    public function findByCategory($category) {
        return $this->findAllWithConditions(['category' => $category]);
    }

    public function findByTag($tag) {
        return $this->findAllWithConditions(['tag' => $tag]);
    }

    public function findAllWithConditions($conditions = []) {
        $conditions += ['order' => 'published_at DESC'];

        $conds = ['1=1'];
        $params = [];
        $joins = "";

        $simple = [
            'path' => 'path',
            'depth' => 'depth',
            'permaID' => 'perma_id',
            'collection' => 'collection',
            'series' => 'series'
        ];

        foreach ($simple as $field => $key) {
            if (isset($conditions[$key])) {
                $conds[] = "p.$field = :$key";
                $params[$key] = $conditions[$key];
            }
        }

        if (isset($conditions['pathPrefix'])) {
            $conds[] = 'p.path LIKE :pathPrefix';
            $params['pathPrefix'] = $conditions['pathPrefix'] . '%';
        }

        if (isset($conditions['category'])) {
            $joins .= " LEFT JOIN page_category pc ON p.id = pc.page_id";
            $conds[] = "pc.category = :category";
            $params['category'] = $conditions['category'];
        }

        if (isset($conditions['tag'])) {
            $joins .= " LEFT JOIN page_tag pt ON p.id = pt.page_id";
            $conds[] = "pt.tag = :tag";
            $params['tag'] = $conditions['tag'];
        }

        $sql = "SELECT * FROM page p $joins WHERE " . implode(" AND ", $conds) . " ORDER BY " . $conditions['order'];
        if (isset($conditions['limit'])) {
            $sql .= " LIMIT " . $conditions['limit'];
        }

        return $this->findBySQL($sql, $params);
    }

    public function findOneWithConditions($conditions = []) {
        $conditions['limit'] = 1;
        $all = $this->findAllWithConditions($conditions);
        return (count($all) > 0) ? reset($all) : null;
    }

    private function findBySQL($sql, $params) {
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $out = [];
        $ids = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $pm = new PageMeta();
            $pm->id = $row['id'];
            $pm->path = $row['path'];
            $pm->depth = $row['depth'];
            $pm->title = $row['title'];
            $pm->publishedAt = new \DateTimeImmutable('@' . $row['published_at']);
            $pm->series = $row['series'];
            $pm->collection = $row['collection'];
            $pm->permaID = $row['perma_id'];
            $pm->meta = json_decode($row['meta']);
            $pm->categories = [];
            $pm->tags = [];
            $out[$pm->id] = $pm;
            $ids[] = $pm->id;
        }

        $this->hydrateCategories($out, $ids);
        $this->hydrateTags($out, $ids);

        return $out;
    }

    private function hydrateCategories($collection, $ids) {
        if (count($ids) === 0) return;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db()->prepare("SELECT page_id, category FROM page_category WHERE page_id IN ($placeholders) ORDER BY page_id ASC, category ASC");
        $stmt->execute($ids);

        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $collection[$row['page_id']]->categories[] = $row['category'];
        }
    }

    private function hydrateTags($collection, $ids) {
        if (count($ids) === 0) return;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db()->prepare("SELECT page_id, tag FROM page_tag WHERE page_id IN ($placeholders) ORDER BY page_id ASC, tag ASC");
        $stmt->execute($ids);

        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $collection[$row['page_id']]->tags[] = $row['tag'];
        }
    }

    private function db() {
        if ($this->db === null) {
            $this->db = new \PDO("sqlite:" . $this->dbFile);
        }
        return $this->db;
    }
}
