<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/log_function.php';

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitize($_POST['role'] ?? 'user');
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $license = sanitize($_POST['license'] ?? '');
    $languages = sanitize($_POST['languages'] ?? '');
    $car_model = sanitize($_POST['car_model'] ?? '');
    $car_capacity = intval($_POST['car_capacity'] ?? 0);
    $car_number = sanitize($_POST['car_number'] ?? '');
    $price_per_km = floatval($_POST['price_per_km'] ?? 0);

    $errors = [];

    if (empty($name)) $errors[] = "Nome é obrigatório.";
    if (empty($phone)) $errors[] = "Número de Telemóvel é obrigatório.";
    if (!in_array($gender, ['Male','Female','Other'])) $errors[] = "Seleção de gênero inválida.";
    if (empty($city)) $errors[] = "Cidade é obrigatória.";
    if ($year > 2013) $errors[] = "O ano de nascimento deve ser menor que 2014.";
    if (strlen($password) < 6) $errors[] = "A senha deve ter pelo menos 6 caracteres.";
    if ($password !== $password_confirm) $errors[] = "As senhas não coincidem.";

    if ($role === 'driver') {
        if (empty($license)) $errors[] = "Número da carta de condução é obrigatório para motoristas.";
        if (empty($car_model)) $errors[] = "Modelo do carro é obrigatório para motoristas.";
        if ($car_capacity <= 0) $errors[] = "Capacidade do carro deve ser maior que 0.";
        if (empty($car_number)) $errors[] = "Matrícula do carro é obrigatória.";
        if ($price_per_km <= 0) $errors[] = "Preço por km deve ser maior que 0.";
    }

    if (count($errors) > 0) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['signup_data'] = $_POST;
        header('Location: signup.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['signup_errors'] = ["Número de Telemóvel já registrado."];
            $_SESSION['signup_data'] = $_POST;
            header('Location: signup.php');
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users 
                (name, phone, gender, city, year, password_hash, role, license, languages, car_model, car_capacity, car_number, price_per_km)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            $phone,
            $gender,
            $city,
            $year,
            $password_hash,
            $role,
            $license,
            $languages,
            $car_model,
            $car_capacity,
            $car_number,
            $price_per_km
        ]);

        $new_user_id = $pdo->lastInsertId();

        // Log do novo cadastro
        log_action($pdo, $new_user_id, "Novo utilizador registado ($role)");

        $_SESSION['signup_success'] = "Cadastro realizado com sucesso! Por favor, faça login.";
        header('Location: login.php');
        exit;

    } catch (PDOException $e) {
        error_log("Erro no cadastro: " . $e->getMessage());
        $_SESSION['signup_errors'] = ["Ocorreu um erro inesperado. Tente novamente mais tarde."];
        header('Location: signup.php');
        exit;
    }

} else {
    header('Location: signup.php');
    exit;
}
?>
