<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
include '../includes/header_private.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo '<div class="max-w-4xl mx-auto p-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                Sessão inválida. Faça login novamente.
            </div>
          </div>';
    include '../includes/footer_private.php';
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, phone, gender, city, role,
            license, languages, car_model, car_capacity, car_number, price_per_km, created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo '<div class="max-w-4xl mx-auto p-4">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    Utilizador não encontrado.
                </div>
              </div>';
        include '../includes/footer_private.php';
        exit;
    }
} catch (Exception $ex) {
    echo '<div class="max-w-4xl mx-auto p-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                Não foi possível carregar os dados do utilizador.
            </div>
          </div>';
    include '../includes/footer_private.php';
    exit;
}

$photo_path = "../assets/images/driver.png";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Perfil do Motorista - MOOVA Carona Compartilhada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f7f7f7;
        }
        .osm-suggestions {
            position: absolute;
            z-index: 50;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .osm-suggestions li {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #003049;
        }
        .osm-suggestions li:hover {
            background: #669bbc;
            color: #003049;
        }
        .osm-suggestions .loc-meta {
            font-size: 0.875rem;
            color: #003049;
        }
        .btn-primary {
            background-color: #003049;
            color: #669bbc;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #003049;
            transform: translateY(-1px);
        }
        .btn-primary:disabled {
            background-color: #a3a3a3;
            cursor: not-allowed;
        }
        .profile-card {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .form-section {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .alert-box {
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body>
<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="profile-card p-6 md:flex gap-8">
        <div class="flex-shrink-0">
            <img src="<?= htmlspecialchars($photo_path) ?>" 
                 class="w-48 h-48 md:w-64 md:h-64 rounded-full object-cover border-4 border-[#669bbc] shadow-lg" />
        </div>
        <div class="flex-1 mt-6 md:mt-0">
            <h2 id="user-name" class="text-3xl font-bold text-[#003049] mb-6"><?= htmlspecialchars($user['name']) ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3 text-[#003049]">
                    <p><span class="font-semibold">Gênero:</span> <?= htmlspecialchars($user['gender']) ?></p>
                    <p><span class="font-semibold">Telefone:</span> <a href="tel:<?= htmlspecialchars($user['phone']) ?>"
                        class="text-[#003049] hover:underline"><?= htmlspecialchars($user['phone']) ?></a></p>
                    <p><span class="font-semibold">Cidade:</span> <?= htmlspecialchars($user['city']) ?></p>
                    <p><span class="font-semibold">Registado em:</span> <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
                </div>
                <div class="space-y-3 text-[#003049]">
                    <p><span class="font-semibold">Id:</span> <?= intval($user['id']) ?></p>
                    <p><span class="font-semibold">Tipo de Utilizador:</span> <?= htmlspecialchars($user['role'] ?? 'Cliente') ?></p>
                    
                    <?php if (($user['role'] ?? '') === 'driver'): ?>
                        <p><span class="font-semibold">Carta de Condução:</span> <?= htmlspecialchars($user['license'] ?? '-') ?></p>
                        <p><span class="font-semibold">Línguas:</span> <?= htmlspecialchars($user['languages'] ?? '-') ?></p>
                        <p><span class="font-semibold">Carro:</span> <?= htmlspecialchars($user['car_model'] ?? '-') ?> (<?= intval($user['car_capacity'] ?? 0) ?> lugares)</p>
                        <p><span class="font-semibold">Matrícula:</span> <?= htmlspecialchars($user['car_number'] ?? '-') ?></p>
                        <p><span class="font-semibold">Preço por Km:</span> <?= isset($user['price_per_km']) ? number_format($user['price_per_km'], 2) : '-' ?> MT</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de ação -->
    <div class="mt-8 flex gap-4 justify-center">
        <button id="editProfileBtn" class="btn-primary font-semibold py-2 px-6 rounded-md">
            <i class="fas fa-edit mr-2"></i> Editar Perfil
        </button>
        <button id="deleteAccountBtn" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-md">
            <i class="fas fa-trash mr-2"></i> Eliminar Conta
        </button>
    </div><br><br>

    <!-- Modal de edição de perfil -->
    <div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md relative">
            <h3 class="text-xl font-bold mb-4 text-[#003049]">Editar Perfil</h3>
            <form id="editProfileForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#003049]">Nome</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                               class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#003049]">Gênero</label>
                        <input type="text" name="gender" value="<?= htmlspecialchars($user['gender']) ?>" required
                               class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#003049]">Telefone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required
                               class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#003049]">Cidade</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($user['city']) ?>"
                               class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                    </div>
                </div>

                <?php if (($user['role'] ?? '') === 'driver'): ?>
                    <hr class="my-4 border-gray-300">
                    <h4 class="font-semibold text-[#003049] mb-2">Informações do Motorista</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[#003049]">Carta de Condução</label>
                            <input type="text" name="license" value="<?= htmlspecialchars($user['license'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#003049]">Línguas</label>
                            <input type="text" name="languages" value="<?= htmlspecialchars($user['languages'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#003049]">Modelo do Carro</label>
                            <input type="text" name="car_model" value="<?= htmlspecialchars($user['car_model'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#003049]">Capacidade</label>
                            <input type="number" name="car_capacity" value="<?= intval($user['car_capacity'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#003049]">Matrícula</label>
                            <input type="text" name="car_number" value="<?= htmlspecialchars($user['car_number'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#003049]">Preço por Km</label>
                            <input type="number" step="0.01" name="price_per_km" value="<?= htmlspecialchars($user['price_per_km'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-[#e5e7eb] shadow-sm focus:border-[#003049] focus:ring-[#003049] text-[#003049]">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" id="closeEditModal" class="px-4 py-2 rounded-md border border-gray-300">Cancelar</button>
                    <button type="submit" class="btn-primary px-4 py-2 rounded-md">Salvar</button>
                </div>
                <div id="editProfileAlert" class="mt-2 hidden p-2 rounded text-sm"></div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmação de eliminação -->
    <div id="deleteAccountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm relative">
            <h3 class="text-xl font-bold mb-4 text-red-600">Eliminar Conta</h3>
            <p class="mb-6 text-[#003049]">Tem certeza que deseja eliminar a sua conta? Esta ação não pode ser desfeita.</p>
            <div class="flex justify-end gap-2">
                <button id="closeDeleteModal" class="px-4 py-2 rounded-md border border-gray-300">Cancelar</button>
                <form method="POST" id="deleteAccountForm">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-md">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userNameEl = document.getElementById('user-name');
    if (userNameEl) console.log(`Perfil carregado: ${userNameEl.textContent}`);

    /* --- MODAIS --- */
    const modals = [
        { btnId: 'editProfileBtn', modalId: 'editProfileModal', closeId: 'closeEditModal' },
        { btnId: 'deleteAccountBtn', modalId: 'deleteAccountModal', closeId: 'closeDeleteModal' }
    ];

    modals.forEach(({ btnId, modalId, closeId }) => {
        const btn = document.getElementById(btnId);
        const modal = document.getElementById(modalId);
        const close = document.getElementById(closeId);
        if (btn && modal && close) {
            btn.addEventListener('click', () => modal.classList.remove('hidden'));
            close.addEventListener('click', () => modal.classList.add('hidden'));
        }
    });

    /* --- EDIT PROFILE --- */
    const editForm = document.getElementById('editProfileForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const alertBox = document.getElementById('editProfileAlert');
            alertBox && alertBox.classList.add('hidden');
            alertBox && (alertBox.textContent = '');

            const submitBtn = editForm.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'A guardar...'; }

            try {
                const formData = new FormData();
                formData.append('update_profile', '1');
                formData.append('confirm', 'yes');

                const res = await fetch('update_profile.php', { method: 'POST', body: formData });
                const text = await res.text();
                console.log(text);
                const data = JSON.parse(text);

                if (data.status === 'success') {
                    alert(data.message || 'Conta editada. ');
                    window.location.href = data.redirect || '/';
                } else {
                    alert(data.message || 'Falha ao editar conta.');
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Salvar'; }
                }
            } catch (err) {
                alert('Erro ao comunicar com o servidor.');
                console.error(err);
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Editar'; }
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Salvar'; }
            }
        });
    }

    /* --- DELETE ACCOUNT --- */
    const deleteForm = document.getElementById('deleteAccountForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!confirm('Tem a certeza que quer eliminar a sua conta? Esta ação não pode ser desfeita.')) return;

            const submitBtn = deleteForm.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'A eliminar...'; }

            try {
                const formData = new FormData();
                formData.append('delete_account', '1');
                formData.append('confirm', 'yes');

                const res = await fetch('delete_account.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.status === 'success') {
                    alert(data.message || 'Conta eliminada. A redireccionar...');
                    window.location.href = data.redirect || '/';
                } else {
                    alert(data.message || 'Falha ao eliminar conta.');
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Eliminar'; }
                }
            } catch (err) {
                alert('Erro ao comunicar com o servidor.');
                console.error(err);
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Eliminar'; }
            }
        });
    }
});
</script>

<?php include '../includes/footer_private.php'; ?>
</body>
</html>
