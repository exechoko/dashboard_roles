<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada</title>
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

        .particles {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) { width: 80px;  height: 80px;  left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 120px; height: 120px; left: 20%; animation-delay: 0.5s; }
        .particle:nth-child(3) { width: 60px;  height: 60px;  left: 60%; animation-delay: 1s; }
        .particle:nth-child(4) { width: 90px;  height: 90px;  left: 80%; animation-delay: 1.5s; }
        .particle:nth-child(5) { width: 110px; height: 110px; left: 50%; animation-delay: 2s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg);   opacity: 0.1; }
            50%       { transform: translateY(-100px) rotate(180deg); opacity: 0.3; }
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem 2rem;
            max-width: 560px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .code-display {
            font-size: 7rem;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.8; }
        }

        .icon-row {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: shake 4s ease-in-out infinite;
        }

        @keyframes shake {
            0%, 90%, 100% { transform: rotate(0deg); }
            92%            { transform: rotate(-10deg); }
            94%            { transform: rotate(10deg); }
            96%            { transform: rotate(-6deg); }
            98%            { transform: rotate(6deg); }
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }

        .subtitle {
            font-size: 0.95rem;
            color: #718096;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(229, 62, 62, 0.1);
            color: #c53030;
            padding: 0.4rem 1rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #fc8181;
            border-radius: 50%;
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.65rem 1.4rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 1.5rem 0;
        }

        .footer {
            font-size: 0.78rem;
            color: #a0aec0;
        }

        @media (max-width: 480px) {
            .code-display { font-size: 5rem; }
            h1 { font-size: 1.3rem; }
            .container { padding: 2rem 1.5rem; }
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
        <div class="code-display">404</div>
        <div class="icon-row">🔍</div>

        <h1>Página no encontrada</h1>
        <p class="subtitle">
            La página que buscás no existe, fue movida o la URL es incorrecta.
        </p>

        <div class="status-indicator">
            <span class="status-dot"></span>
            Recurso no disponible
        </div>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">
                &#8594; Ir al inicio
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                &#8592; Volver
            </a>
        </div>

        <hr class="divider">
        <p class="footer">
            Si creés que esto es un error, contactá al administrador del sistema.
        </p>
    </div>
</body>
</html>
