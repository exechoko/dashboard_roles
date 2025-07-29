<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sitio en Mantenimiento</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated background particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 120px; height: 120px; left: 20%; animation-delay: 0.5s; }
        .particle:nth-child(3) { width: 60px; height: 60px; left: 60%; animation-delay: 1s; }
        .particle:nth-child(4) { width: 90px; height: 90px; left: 80%; animation-delay: 1.5s; }
        .particle:nth-child(5) { width: 110px; height: 110px; left: 50%; animation-delay: 2s; }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.1;
            }
            50% {
                transform: translateY(-100px) rotate(180deg);
                opacity: 0.3;
            }
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            max-width: 900px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .maintenance-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }

        .maintenance-icon svg {
            width: 30px;
            height: 30px;
            fill: white;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 1rem;
            color: #4a5568;
            margin-bottom: 1rem;
            font-weight: 400;
            line-height: 1.6;
        }

        .message {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 16px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid #667eea;
        }

        .message p {
            color: #2d3748;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 1rem 0;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #ffc107;
            border-radius: 50%;
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Game Container */
        .game-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem;
            max-width: 900px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .game-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            text-align: center;
        }

        .game-instructions {
            text-align: center;
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        #gameCanvas {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            display: block;
            margin: 0 auto;
            background: #f8fafc;
        }

        .game-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 12px;
        }

        .score {
            font-weight: 600;
            color: #667eea;
        }

        .high-score {
            font-weight: 600;
            color: #38a169;
        }

        .footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            color: #718096;
            font-size: 0.8rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container, .game-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .subtitle {
                font-size: 0.9rem;
            }

            #gameCanvas {
                max-width: 100%;
                height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <div class="maintenance-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H11V21H5V19H11V17H5V15H11V13H5V11H11V9H21M15 15V13H17V11H15V9H17V7H15V9L9 15H15M15.41 16L12 19.41L10.59 18L14 14.59L15.41 16M18.42 12.8L17 14.22L15.58 12.8L17 11.39L18.42 12.8Z"/>
            </svg>
        </div>

        <h1>Mantenimiento</h1>
        <p class="subtitle">Estamos trabajando para mejorar tu experiencia</p>

        <div class="status-indicator">
            <span class="status-dot"></span>
            Sistema en mantenimiento
        </div>

        <div class="message">
                <p>Estamos realizando algunas mejoras. Mientras tanto, ¡juega al dinosaurio!</p>
        </div>
    </div>

    <div class="game-container">
        <h2 class="game-title">🦕 Dinosaurio Saltarín</h2>
        <p class="game-instructions">
            Presiona <strong>ESPACIO</strong> o <strong>CLICK</strong> para saltar y evitar los cactus
        </p>

        <canvas id="gameCanvas" width="800" height="200"></canvas>

        <div class="game-stats">
            <div class="score">Puntuación: <span id="score">0</span></div>
            <div class="high-score">Récord: <span id="highScore">0</span></div>
        </div>

        <div class="footer">
            <p>El sitio se actualizará automáticamente cuando esté listo</p>
        </div>
    </div>

    <script>
        // Dinosaur Game
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');

        // Game variables
        let gameSpeed = 3;
        let gravity = 0.5;
        let score = 0;
        let highScore = localStorage.getItem('dinoHighScore') || 0;
        let gameRunning = false;
        let gameOver = false;

        // Update high score display
        document.getElementById('highScore').textContent = highScore;

        // Dinosaur object
        const dino = {
            x: 50,
            y: 150,
            width: 40,
            height: 40,
            dy: 0,
            jumpPower: 12,
            grounded: false,
            color: '#667eea'
        };

        // Obstacles array
        const obstacles = [];

        // Ground
        const ground = {
            x: 0,
            y: canvas.height - 20,
            width: canvas.width,
            height: 20,
            color: '#e2e8f0'
        };

        // Clouds array
        const clouds = [];

        // Initialize clouds
        for (let i = 0; i < 3; i++) {
            clouds.push({
                x: Math.random() * canvas.width,
                y: Math.random() * 100 + 20,
                width: 60,
                height: 30,
                speed: 0.5
            });
        }

        class Obstacle {
            constructor() {
                this.x = canvas.width;
                this.y = ground.y - 40;
                this.width = 20;
                this.height = 40;
                this.color = '#38a169';
                this.passed = false;
            }

            update() {
                this.x -= gameSpeed;
            }

            draw() {
                // Draw cactus
                ctx.fillStyle = this.color;
                ctx.fillRect(this.x, this.y, this.width, this.height);

                // Cactus details
                ctx.fillRect(this.x + 5, this.y - 10, 10, 15);
                ctx.fillRect(this.x - 8, this.y + 10, 15, 8);
                ctx.fillRect(this.x + 13, this.y + 15, 15, 8);
            }

            collidesWith(dino) {
                return dino.x < this.x + this.width &&
                       dino.x + dino.width > this.x &&
                       dino.y < this.y + this.height &&
                       dino.y + dino.height > this.y;
            }
        }

        function drawDino() {
            ctx.fillStyle = dino.color;

            // Body
            ctx.fillRect(dino.x, dino.y, dino.width, dino.height);

            // Head
            ctx.fillRect(dino.x + 22, dino.y - 10, 25, 20);

            // Eye
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(dino.x + 35, dino.y - 5, 5, 5);
            ctx.fillStyle = '#000000';
            ctx.fillRect(dino.x + 37, dino.y - 3, 2, 2);

            // Legs
            ctx.fillStyle = dino.color;
            ctx.fillRect(dino.x + 5, dino.y + 35, 8, 15);
            ctx.fillRect(dino.x + 25, dino.y + 35, 8, 15);

            // Tail
            ctx.fillRect(dino.x - 15, dino.y + 10, 20, 8);
        }

        function drawGround() {
            ctx.fillStyle = ground.color;
            ctx.fillRect(ground.x, ground.y, ground.width, ground.height);

            // Ground pattern
            ctx.fillStyle = '#cbd5e0';
            for (let i = 0; i < canvas.width; i += 20) {
                ctx.fillRect(i, ground.y + 5, 10, 2);
            }
        }

        function drawClouds() {
            ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
            clouds.forEach(cloud => {
                // Cloud body
                ctx.fillRect(cloud.x, cloud.y, cloud.width, cloud.height);
                ctx.fillRect(cloud.x + 10, cloud.y - 10, 40, 20);
                ctx.fillRect(cloud.x + 20, cloud.y - 15, 30, 25);

                // Move cloud
                cloud.x -= cloud.speed;
                if (cloud.x + cloud.width < 0) {
                    cloud.x = canvas.width + Math.random() * 200;
                    cloud.y = Math.random() * 100 + 20;
                }
            });
        }

        function jump() {
            if (dino.grounded) {
                dino.dy = -dino.jumpPower;
                dino.grounded = false;
            }
        }

        function update() {
            if (!gameRunning) return;

            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw background elements
            drawClouds();
            drawGround();

            // Update dinosaur
            dino.dy += gravity;
            dino.y += dino.dy;

            // Ground collision
            if (dino.y >= ground.y - dino.height) {
                dino.y = ground.y - dino.height;
                dino.dy = 0;
                dino.grounded = true;
            }

            // Draw dinosaur
            drawDino();

            // Spawn obstacles
            if (Math.random() < 0.005) {
                obstacles.push(new Obstacle());
            }

            // Update and draw obstacles
            for (let i = obstacles.length - 1; i >= 0; i--) {
                const obstacle = obstacles[i];
                obstacle.update();
                obstacle.draw();

                // Check collision
                if (obstacle.collidesWith(dino)) {
                    gameOver = true;
                    gameRunning = false;

                    // Update high score
                    if (score > highScore) {
                        highScore = score;
                        localStorage.setItem('dinoHighScore', highScore);
                        document.getElementById('highScore').textContent = highScore;
                    }

                    showGameOver();
                    return;
                }

                // Increase score
                if (!obstacle.passed && obstacle.x + obstacle.width < dino.x) {
                    obstacle.passed = true;
                    score++;
                    document.getElementById('score').textContent = score;

                    // Increase game speed
                    if (score % 10 === 0) {
                        gameSpeed += 0.5;
                    }
                }

                // Remove off-screen obstacles
                if (obstacle.x + obstacle.width < 0) {
                    obstacles.splice(i, 1);
                }
            }

            requestAnimationFrame(update);
        }

        function showGameOver() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = '#ffffff';
            ctx.font = '36px Inter';
            ctx.textAlign = 'center';
            ctx.fillText('Game Over', canvas.width / 2, canvas.height / 2 - 30);

            ctx.font = '18px Inter';
            ctx.fillText('Presiona ESPACIO para jugar de nuevo', canvas.width / 2, canvas.height / 2 + 20);

            ctx.font = '16px Inter';
            ctx.fillText(`Puntuación: ${score}`, canvas.width / 2, canvas.height / 2 + 50);
        }

        function startGame() {
            gameRunning = true;
            gameOver = false;
            score = 0;
            gameSpeed = 3;
            obstacles.length = 0;
            dino.y = 150;
            dino.dy = 0;
            dino.grounded = true;
            document.getElementById('score').textContent = score;

            update();
        }

        function showStartScreen() {
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            drawGround();
            drawClouds();
            drawDino();

            ctx.fillStyle = '#2d3748';
            ctx.font = '24px Inter';
            ctx.textAlign = 'center';
            ctx.fillText('🦕 Dinosaurio Saltarín', canvas.width / 2, 60);

            ctx.font = '16px Inter';
            ctx.fillText('Presiona ESPACIO o CLICK para empezar', canvas.width / 2, 90);
        }

        // Event listeners
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                if (!gameRunning && !gameOver) {
                    startGame();
                } else if (!gameRunning && gameOver) {
                    startGame();
                } else {
                    jump();
                }
            }
        });

        canvas.addEventListener('click', () => {
            if (!gameRunning && !gameOver) {
                startGame();
            } else if (!gameRunning && gameOver) {
                startGame();
            } else {
                jump();
            }
        });

        // Initialize game
        showStartScreen();

        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes instead of 30 seconds to not interrupt gameplay
    </script>
</body>
</html>
