<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$userRole = $user['role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOOVA Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 80rem;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
        }

        .logo img {
            height: 2.5rem;
            transition: transform 0.2s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            font-size: 0.9rem;
            font-weight: 500;
            color: #4b5563;
            transition: color 0.2s ease-in-out;
        }

        .nav-links a:hover {
            color: 669bbc;
        }

        .logout-btn {
            background: linear-gradient(to right, 669bbc, #003049);
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            color: #ffffff;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: linear-gradient(to right, #669bbc, 669bbc);
        }

        .mobile-menu {
            display: none;
            cursor: pointer;
        }

        main {
            max-width: 80rem;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 4rem;
                left: 0;
                right: 0;
                background: #ffffff;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                padding: 1.5rem;
                z-index: 50;
            }

            .nav-links.active {
                display: flex;
                gap: 1rem;
            }

            .mobile-menu {
                display: block;
            }

            .mobile-menu i {
                font-size: 1.5rem;
                color: #4b5563;
            }

            main {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <div class="logo">
                <a href="dashboard.php" title="MOOVA Ride Sharing">
                    <img src="assets/images/Car_logo.png" alt="MOOVA Logo" class="h-12">
                </a>
            </div>
            <nav class="nav-links" id="navLinks">

                <a href="dashboard.php" class="block px-4 py-2">Painel</a>
                <?php if($userRole === 'driver'): ?>
                    <a href="post_ride.php" class="font-medium">Publicar Viagem</a>
                <?php endif; ?>
                <a href="logs.php" class="font-medium">Logs</a>
                <a href="profile.php?user_id=<?= $_SESSION['user_id'] ?>" class="block px-4 py-2"><i class="fas fa-user-circle text-xl"></i> Perfil</a>

                <a href="public/logout.php" class="logout-btn">Logout</a>

            </nav>
            <button class="mobile-menu md:hidden" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    <main>
    </main>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', () => {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        });
    </script>
</body>
</html>