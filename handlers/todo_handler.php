<?php
require_once dirname(__DIR__) . '/config/database.php';

class TodoHandler {
    private $pdo;
    private $last_error;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getLastError() {
        return $this->last_error;
    }

    public function createTodo($user_id, $title, $description) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO todos (user_id, title, description, status) VALUES (?, ?, ?, 'pending')");
            $result = $stmt->execute([$user_id, $title, $description]);
            if (!$result) {
                $this->last_error = "Execute failed: " . implode(", ", $stmt->errorInfo());
                return false;
            }
            return true;
        } catch(PDOException $e) {
            $this->last_error = "Database error: " . $e->getMessage();
            return false;
        }
    }

    public function getTodos($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getTodo($todo_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
            $stmt->execute([$todo_id, $user_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            return null;
        }
    }

    public function updateTodo($todo_id, $user_id, $title, $description) {
        try {
            $stmt = $this->pdo->prepare("UPDATE todos SET title = ?, description = ? WHERE id = ? AND user_id = ?");
            return $stmt->execute([$title, $description, $todo_id, $user_id]);
        } catch(PDOException $e) {
            $this->last_error = "Database error: " . $e->getMessage();
            return false;
        }
    }

    public function updateTodoStatus($todo_id, $user_id, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE todos SET status = ? WHERE id = ? AND user_id = ?");
            return $stmt->execute([$status, $todo_id, $user_id]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function deleteTodo($todo_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
            return $stmt->execute([$todo_id, $user_id]);
        } catch(PDOException $e) {
            return false;
        }
    }
}

$todo = new TodoHandler($pdo);
?> 