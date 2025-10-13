<style>
    .theme-toggle {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        background-color: var(--card-bg);
        border: 2px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        transition: all 0.1s ease;
        box-shadow: 0 2px 8px var(--shadow);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .theme-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px var(--shadow);
    }

    .theme-toggle i {
        font-size: 18px;
        margin: 0;
    }
</style>

<button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Cambiar tema">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>
