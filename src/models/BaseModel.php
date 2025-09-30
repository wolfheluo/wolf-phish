<?php

abstract class BaseModel {
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    
    public function __construct() {
        if (empty($this->table)) {
            $this->table = strtolower(get_class($this)) . 's';
        }
    }
    
    /**
     * 查找所有記錄
     */
    public function all($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "{$key} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $results = Database::fetchAll($sql, $params);
        return $this->hideFields($results);
    }
    
    /**
     * 根據ID查找記錄
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = Database::fetch($sql, [$id]);
        return $result ? $this->hideFields([$result])[0] : null;
    }
    
    /**
     * 根據條件查找單條記錄
     */
    public function findWhere($conditions) {
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $whereClause[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClause);
        $result = Database::fetch($sql, $params);
        return $result ? $this->hideFields([$result])[0] : null;
    }
    
    /**
     * 創建新記錄
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        $data = $this->addTimestamps($data, true);
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        Database::query($sql, array_values($data));
        $id = Database::lastInsertId();
        
        return $this->find($id);
    }
    
    /**
     * 更新記錄
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        $data = $this->addTimestamps($data, false);
        
        $setClause = [];
        foreach (array_keys($data) as $field) {
            $setClause[] = "{$field} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = ?";
        $params = array_values($data);
        $params[] = $id;
        
        Database::query($sql, $params);
        return $this->find($id);
    }
    
    /**
     * 刪除記錄
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        Database::query($sql, [$id]);
        return true;
    }
    
    /**
     * 統計記錄數
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "{$key} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $result = Database::fetch($sql, $params);
        return (int) $result['count'];
    }
    
    /**
     * 執行原生SQL查詢
     */
    public function query($sql, $params = []) {
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * 過濾可填充字段
     */
    private function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_filter($data, function($key) {
            return in_array($key, $this->fillable);
        }, ARRAY_FILTER_USE_KEY);
    }
    
    /**
     * 隱藏敏感字段
     */
    protected function hideFields($results) {
        if (empty($this->hidden) || empty($results)) {
            return $results;
        }
        
        foreach ($results as &$result) {
            foreach ($this->hidden as $field) {
                unset($result[$field]);
            }
        }
        
        return $results;
    }
    
    /**
     * 添加時間戳
     */
    private function addTimestamps($data, $isCreate = false) {
        $now = date('Y-m-d H:i:s');
        
        if ($isCreate && !isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }
        
        return $data;
    }
    
    /**
     * 分頁查詢
     */
    public function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = 'id DESC') {
        $offset = ($page - 1) * $perPage;
        
        // 查詢總數
        $total = $this->count($conditions);
        
        // 查詢數據
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "{$key} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $sql .= " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        
        $data = Database::fetchAll($sql, $params);
        $data = $this->hideFields($data);
        
        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
                'has_prev' => $page > 1,
                'has_next' => $page < ceil($total / $perPage)
            ]
        ];
    }
}