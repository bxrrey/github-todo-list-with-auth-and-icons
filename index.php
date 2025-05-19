<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/handlers/auth_handler.php';
require_once BASE_PATH . '/handlers/todo_handler.php';

$auth = new AuthHandler($pdo);
$todo = new TodoHandler($pdo);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Handle todo operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        $auth->logout();
        header('Location: login.php');
        exit;
    } elseif (isset($_POST['add_todo'])) {
        if ($todo->createTodo($_SESSION['user_id'], $_POST['title'], $_POST['description'])) {
            $success = "Todo added successfully!";
        } else {
            $error = "Failed to add todo: " . $todo->getLastError();
        }
    } elseif (isset($_POST['update_todo'])) {
        if ($todo->updateTodo($_POST['todo_id'], $_SESSION['user_id'], $_POST['title'], $_POST['description'])) {
            $success = "Todo updated successfully!";
        } else {
            $error = "Failed to update todo";
        }
    } elseif (isset($_POST['update_status'])) {
        $todo->updateTodoStatus($_POST['todo_id'], $_SESSION['user_id'], $_POST['status']);
    } elseif (isset($_POST['delete_todo'])) {
        $todo->deleteTodo($_POST['todo_id'], $_SESSION['user_id']);
    }
}

// Get todos
$todos = $todo->getTodos($_SESSION['user_id']);

// Get single todo for editing
$edit_todo = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_todo = $todo->getTodo($_GET['edit'], $_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    .todo-item {
    border-left: 5px solidrgb(244, 6, 30);
}
    .todo-item.completed {
    border-left-color:rgb(12, 221, 123);
    background: #f8f9fa;
    color: #6c757d;
    text-decoration: line-through;
}
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">To Do List</a>
            <form method="post" class="ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <button type="submit" name="logout" class="btn btn-outline-light">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $edit_todo ? 'Edit To Do' : 'Add New To Do'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <?php if ($edit_todo): ?>
                                <input type="hidden" name="todo_id" value="<?php echo $edit_todo['id']; ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required 
                                       value="<?php echo $edit_todo ? htmlspecialchars($edit_todo['title']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required><?php 
                                    echo $edit_todo ? htmlspecialchars($edit_todo['description']) : ''; 
                                ?></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="<?php echo $edit_todo ? 'update_todo' : 'add_todo'; ?>" 
                                        class="btn btn-primary">
                                    <?php echo $edit_todo ? 'Update To Do' : 'Add To Do'; ?>
                                </button>
                                <?php if ($edit_todo): ?>
                                    <a href="index.php" class="btn btn-secondary">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h3>Your To Do's</h3>
                <?php if (empty($todos)): ?>
                    <div class="alert alert-info">No to do's yet. Add your first to do!</div>
                <?php else: ?>
                    <?php foreach ($todos as $todo_item): ?>
                        <div class="card mb-3 shadow-sm todo-item <?php echo $todo_item['status'] === 'completed' ? 'completed' : ''; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title todo-title mb-0">
                                        <?php echo htmlspecialchars($todo_item['title']); ?>
                                    </h5>
                                    <span class="badge <?php echo $todo_item['status'] === 'completed' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($todo_item['status']); ?>
                                    </span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($todo_item['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="index.php?edit=<?php echo $todo_item['id']; ?>" 
                                           class="btn btn-sm btn-primary me-2">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo_item['id']; ?>">
                                            <button type="submit" name="delete_todo" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo_item['id']; ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" 
                                                onchange="this.form.submit()">
                                            <option value="pending" <?php echo $todo_item['status'] === 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>
                                            <option value="completed" <?php echo $todo_item['status'] === 'completed' ? 'selected' : ''; ?>>
                                                Completed
                                            </option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 