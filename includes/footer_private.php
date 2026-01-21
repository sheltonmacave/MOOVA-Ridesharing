</main>
<footer class="private-footer">
    <div class="container">
        <div class="footer-content">
            <p class="copyright">&copy; 2025 MOOVA Ride Sharing. Todos os direitos reservados.</p>
            <nav class="footer-nav">
                <ul>
                    <li><a href="#">Politicas de Privacidade</a></li>
                    <li><a href="#">Termos de Servico</a></li>
                    <li><a href="#">Contacte-nos</a></li>
                </ul>
            </nav>
        </div>
    </div>
</footer>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.private-footer {
    background-color: #003049;
    color: #669bbc;
    padding: 2rem 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 0.875rem;
    line-height: 1.5;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.copyright {
    color: #669bbc;
    font-weight: 400;
}

.footer-nav ul {
    list-style: none;
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.footer-nav a {
    color: #669bbc;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.footer-nav a:hover {
    color: #003049;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }

    .footer-nav ul {
        justify-content: center;
    }
}
</style>
</body>
</html>