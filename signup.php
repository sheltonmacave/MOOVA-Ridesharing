<?php
session_start();
include 'includes/header_public.php';

$signup_errors = $_SESSION['signup_errors'] ?? [];
unset($_SESSION['signup_errors']);

$signup_data = $_SESSION['signup_data'] ?? [];
unset($_SESSION['signup_data']);
?>

<section class="form-container">
    <h2>Criar uma Conta</h2>

    <?php if (count($signup_errors) > 0): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($signup_errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="signup_process.php" method="POST" class="auth-form" novalidate>
        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
            <div style="flex:1 1 200px;">
                <label for="role">Tipo de Conta</label>
                <select id="role" name="role" required onchange="toggleDriverFields(this.value)">
                    <option value="user" <?= ($signup_data['role'] ?? '') === 'user' ? 'selected' : '' ?>>Usuário</option>
                    <option value="driver" <?= ($signup_data['role'] ?? '') === 'driver' ? 'selected' : '' ?>>Motorista</option>
                </select>
            </div>
            <div style="flex:2 1 300px;">
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" placeholder="Digite seu nome completo" required 
                       value="<?= htmlspecialchars($signup_data['name'] ?? '') ?>" />
            </div>
        </div>

        <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:0.5rem;">
            <div style="flex:1 1 150px;">
                <label for="phone">Número de Telemovel</label>
                <input type="text" id="phone" name="phone" placeholder="Número de Telemovel" required
                       value="<?= htmlspecialchars($signup_data['phone'] ?? '') ?>" />
            </div>
            <div style="flex:1 1 150px;">
                <label for="gender">Gênero</label>
                <select id="gender" name="gender" required>
                    <option value="" disabled <?= !isset($signup_data['gender']) ? 'selected' : '' ?>>Selecione</option>
                    <option value="Male" <?= (isset($signup_data['gender']) && $signup_data['gender'] === 'Male') ? 'selected' : '' ?>>Masculino</option>
                    <option value="Female" <?= (isset($signup_data['gender']) && $signup_data['gender'] === 'Female') ? 'selected' : '' ?>>Feminino</option>
                    <option value="Other" <?= (isset($signup_data['gender']) && $signup_data['gender'] === 'Other') ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
            <div style="flex:1 1 150px;">
                <label for="year">Ano de Nascimento</label>
                <input type="number" id="year" name="year" min="1900" max="2013" placeholder="Ano" required
                       value="<?= htmlspecialchars($signup_data['year'] ?? '') ?>" />
            </div>
        </div>

        <div style="margin-top:0.5rem;">
            <label for="city">Cidade</label>
            <input type="text" id="city" name="city" placeholder="Digite sua cidade" required
                   value="<?= htmlspecialchars($signup_data['city'] ?? '') ?>" />
        </div>

        <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:0.5rem;">
            <div style="flex:1 1 200px;">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" placeholder="Crie uma senha" required />
            </div>
            <div style="flex:1 1 200px;">
                <label for="password_confirm">Confirmar Senha</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmar senha" required />
            </div>
        </div>

        <!-- Campos adicionais para motorista -->
        <div id="driverFields" style="display: <?= ($signup_data['role'] ?? '') === 'driver' ? 'block' : 'none' ?>; margin-top:0.5rem;">
            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                <div style="flex:1 1 150px;">
                    <label for="license">Número da Carta</label>
                    <input type="text" id="license" name="license" placeholder="Carta de Condução" value="<?= htmlspecialchars($signup_data['license'] ?? '') ?>" />
                </div>
                <div style="flex:1 1 150px;">
                    <label for="languages">Línguas</label>
                    <input type="text" id="languages" name="languages" placeholder="Línguas que fala" value="<?= htmlspecialchars($signup_data['languages'] ?? '') ?>" />
                </div>
            </div>

            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:0.5rem;">
                <div style="flex:1 1 200px;">
                    <label for="car_model">Modelo do Carro</label>
                    <input type="text" id="car_model" name="car_model" placeholder="Modelo do carro" value="<?= htmlspecialchars($signup_data['car_model'] ?? '') ?>" />
                </div>
                <div style="flex:1 1 100px;">
                    <label for="car_capacity">Capacidade</label>
                    <input type="number" id="car_capacity" name="car_capacity" min="1" placeholder="Lugares" value="<?= htmlspecialchars($signup_data['car_capacity'] ?? '') ?>" />
                </div>
                <div style="flex:1 1 150px;">
                    <label for="price_per_km">Preço/Km</label>
                    <input type="number" step="0.01" id="price_per_km" name="price_per_km" placeholder="Preço por km" value="<?= htmlspecialchars($signup_data['price_per_km'] ?? '') ?>" />
                </div>
            </div>

            <div style="margin-top:0.5rem;">
                <label for="car_number">Matrícula do Carro</label>
                <input type="text" id="car_number" name="car_number" placeholder="Digite a matrícula" value="<?= htmlspecialchars($signup_data['car_number'] ?? '') ?>" />
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Registro</button>
    </form>

    <p class="register-link">
        Já tem uma conta? <a href="login.php">Faça login aqui</a>
    </p>
</section>

<script>
function toggleDriverFields(role) {
    document.getElementById('driverFields').style.display = role === 'driver' ? 'block' : 'none';
}
</script>

<?php
include 'includes/footer_public.php';
?>
