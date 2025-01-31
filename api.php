<?php
header('Content-Type: application/json');
$host = 'localhost';
$db = 'workout_tracker';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

if ($endpoint === 'workouts') {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM workouts ORDER BY date DESC');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO workouts (name, date, description) VALUES (:name, :date, :description)');
        $stmt->execute([
            'name' => $data['name'],
            'date' => $data['date'],
            'description' => $data['description'] ?? null,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM workouts WHERE id = :id');
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'ID is required']);
        }
    }
} elseif ($endpoint === 'exercises') {
    if ($method === 'GET') {
        $workout_id = $_GET['workout_id'] ?? null;
        if ($workout_id) {
            $stmt = $pdo->prepare('SELECT * FROM exercises WHERE workout_id = :workout_id');
            $stmt->execute(['workout_id' => $workout_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            echo json_encode(['error' => 'workout_id is required']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO exercises (name, workout_id, sets, reps, weight) VALUES (:name, :workout_id, :sets, :reps, :weight)');
        $stmt->execute([
            'name' => $data['name'],
            'workout_id' => $data['workout_id'],
            'sets' => $data['sets'],
            'reps' => $data['reps'],
            'weight' => $data['weight'] ?? null,
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM exercises WHERE id = :id');
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'ID is required']);
        }
    }
} else {
    echo json_encode(['error' => 'Unknown endpoint']);
}
if ($endpoint === 'progress' && $method === 'GET') {
    $exercise_id = $_GET['exercise_id'] ?? null;
    if ($exercise_id) {
        $stmt = $pdo->prepare('
            SELECT date, weight, sets, reps, duration 
            FROM progress 
            WHERE exercise_id = :exercise_id 
            ORDER BY date ASC
        ');
        $stmt->execute(['exercise_id' => $exercise_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo json_encode(['error' => 'Invalid exercise ID']);
    }
}
if ($endpoint === 'progress' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO progress (exercise_id, date, weight, sets, reps, duration, calories_burned) 
                           VALUES (:exercise_id, NOW(), :weight, :sets, :reps, :duration, :calories_burned)');
    $stmt->execute([
        'exercise_id' => $data['exercise_id'],
        'weight' => $data['weight'] ?? null,
        'sets' => $data['sets'] ?? null,
        'reps' => $data['reps'] ?? null,
        'duration' => $data['duration'],
        'calories_burned' => $data['calories_burned'] ?? null,
    ]);
    echo json_encode(['success' => true]);
}
?>
