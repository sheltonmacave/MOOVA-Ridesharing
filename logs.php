<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connection.php';
include 'includes/header_private.php';

$stmt = $pdo->prepare("
    SELECT l.id, l.action, l.created_at, u.name AS user_name, u.role
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
");
$stmt->execute();
$logs = $stmt->fetchAll();

$logsByDay = [];
foreach ($logs as $log) {
    $day = date("Y-m-d", strtotime($log['created_at']));
    $logsByDay[$day][] = $log;
}
?>

<div class="container mx-auto px-4 py-6 max-w-7xl">
    <h1 class="text-2xl font-bold mb-6">Logs do Sistema</h1>

    <?php if (empty($logsByDay)): ?>
        <p class="text-gray-600">Nenhum log registrado.</p>
    <?php else: ?>
        <?php foreach ($logsByDay as $day => $logsOfDay): ?>
            <div class="mb-4 border border-gray-300 rounded-lg overflow-hidden">

                <!-- Cabeçalho do Accordion -->
                <button 
                    onclick="toggleAccordion('acc_<?= $day ?>')" 
                    class="w-full flex justify-between items-center bg-gray-100 px-4 py-3 text-left font-semibold text-gray-700 hover:bg-gray-200 transition"
                >
                    <span><?= date("d/m/Y", strtotime($day)) ?></span>
                    <i id="icon_acc_<?= $day ?>" class="fas fa-chevron-down"></i>
                </button>

                <!-- Conteúdo -->
                <div id="acc_<?= $day ?>" class="hidden">
                    <table class="min-w-full table-auto bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">ID</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Usuário</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Ação</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logsOfDay as $log): ?>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($log['id']) ?></td>
                                    <td class="px-4 py-2 text-sm">
                                        <?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?>
                                        <?php if (!empty($log['role'])): ?>
                                            (<?= htmlspecialchars($log['role']) ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="px-4 py-2 text-sm"><?= date("H:i:s", strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleAccordion(id) {
    const all = document.querySelectorAll("[id^='acc_']");
    const allIcons = document.querySelectorAll("[id^='icon_acc_']");

    all.forEach(content => {
        if (content.id !== id) content.classList.add('hidden');
    });

    allIcons.forEach(icon => {
        if (icon.id !== "icon_" + id) {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    });

    const content = document.getElementById(id);
    const icon = document.getElementById("icon_" + id);

    content.classList.toggle('hidden');

    if (content.classList.contains('hidden')) {
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}
</script>

<?php include 'includes/footer_private.php'; ?>
