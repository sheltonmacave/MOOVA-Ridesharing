<?php
include 'includes/header_public.php';
?>
<section class="landing-container" style="min-height:85vh;">
    <div class="landing-image">
        <img src="assets/images/Main.png" alt="Ilustração de Compartilhamento de Carro" />
    </div>
    <div class="welcome-text">
        <h1>Bem-vindo ao <span style="color:#0077cc;">MOOVA Ride Sharing</span></h1>
        <p class="main-description">
            Conectamos passageiros e motoristas para viagens seguras, econômicas e convenientes. 
            <strong>Ride Sharing</strong> é a prática de compartilhar viagens com outras pessoas, reduzindo custos e impactos ambientais, enquanto você se desloca de forma eficiente.
        </p>

        <div class="features">
            <div class="feature-item">
                <span class="feature-icon">🚗</span>
                <p>Viagens seguras</p>
            </div>
            <div class="feature-item">
                <span class="feature-icon">💰</span>
                <p>Economia para todos</p>
            </div>
            <div class="feature-item">
                <span class="feature-icon">🌱</span>
                <p>Redução do impacto ambiental</p>
            </div>
        </div>
    </div>
</section>

<style>
.landing-container {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    padding-top: 3rem;
}

.landing-image img {
    max-width: 370px;
    width: 96vw;
    height: auto;
    margin-right: 2.7rem;
    border-radius: 22px;
    box-shadow: 0 8px 42px rgba(176, 150, 221, 0.17);
    background: #f0f8ff;
}

.welcome-text h1 {
    font-size: 3rem;
    color: #023e8a;
    margin-bottom: 1rem;
    font-weight: 900;
}

.main-description {
    color: #003049;
    font-size: 1.35rem;
    font-weight: 500;
    margin-bottom: 2rem;
    line-height: 1.7;
    max-width: 550px;
}

.features {
    display: flex;
    gap: 0 1rem;
    flex-wrap: wrap;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
    color: #023e8a;
    font-weight: 600;
}

.feature-icon {
    font-size: 1.8rem;
}

.floating-driver-login-btn {
    position: fixed;
    right: 2.5rem;
    bottom: 2.5rem;
    background: #023e8a;
    color: #fff;
    font-size: 1.15rem;
    font-weight: 700;
    padding: 15px 34px;
    border-radius: 34px;
    border: none;
    outline: none;
    text-decoration: none;
    box-shadow: 0 6px 22px rgba(131, 74, 193, 0.11);
    z-index: 1001;
    transition: background .25s, box-shadow .22s;
}

.floating-driver-login-btn:hover,
.floating-driver-login-btn:focus {
    background: #1e5bacff;
    color: #fff;
    box-shadow: 0 8px 40px #7fd9b497;
}

@media(max-width:700px){
    .floating-driver-login-btn{
        padding: 13px 15vw;
        right: 0.8rem;
        bottom: 1.3rem;
        font-size: 1.1rem;
    }
    .landing-image img { margin: 0 auto 2.2rem auto;}
    .landing-container{flex-direction:column;}
    .features {justify-content: center;}
}
</style>

<?php
include 'includes/footer_public.php';
?>
