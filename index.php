<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
  var_dump($input);
    if (isset($input['token'])) {
        $token = $input['token'];
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
        $response = file_get_contents($url);

        if ($response !== FALSE) {
            $data = json_decode($response, true);

            if (isset($data['email'])) {
                $name = $data['name'];
                $email = $data['email'];
                $picture = $data['picture'];

                $dsn = 'mysql:host=localhost;dbname=Hexagon';
                $username = 'root';
                $password = '';

                try {
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result) {
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, picture = ? WHERE email = ?");
                        $stmt->execute([$name, $picture, $email]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, picture) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $email, $picture]);
                    }

                    header('Content-Type: application/json');
                    echo json_encode(array(
                        'name' => $name,
                        'email' => $email,
                        'picture' => $picture
                    ));
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(array('error' => 'Database error: ' . $e->getMessage()));
                }

            } else {
                http_response_code(400);
                echo json_encode(array('error' => 'Invalid token data'));
            }
        } else {
            http_response_code(400);
            echo json_encode(array('error' => 'Token verification failed'));
        }
    } else {
        http_response_code(400);
        echo json_encode(array('error' => 'Token not provided'));
    }
} else {
    http_response_code(405);
    echo json_encode(array('error' => 'Invalid request method'));
}
?>