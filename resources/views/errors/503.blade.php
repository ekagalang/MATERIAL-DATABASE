<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Service Unavailable</title>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; width: 100%; margin: 0; overflow: hidden; background: linear-gradient(135deg, #450a0a 0%, #891313 100%); font-family: 'Nunito', sans-serif; }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); background-size: 50px 50px; pointer-events: none; }
        .error { position: absolute; left: 10%; top: 50%; transform: translateY(-50%); color: #ffffff; z-index: 10; max-width: 500px; }
        .error__title { font-size: 8em; font-weight: 900; line-height: 1; text-shadow: 4px 4px 0px #2a0404; color: #fca5a5; margin: 0; }
        .error__subtitle { font-size: 2.5em; font-weight: 700; margin-bottom: 20px; color: #fbbf24; }
        .error__description { opacity: 0.9; font-size: 1.2em; line-height: 1.5; color: #e2e8f0; margin-bottom: 40px; }
        .error__button { padding: 12px 30px; border: 2px solid #fbbf24; background-color: transparent; border-radius: 8px; color: #fbbf24; cursor: pointer; transition: 0.2s; font-size: 1em; font-weight: 700; text-decoration: none; display: inline-block; margin-right: 15px; }
        .error__button:hover { background-color: rgba(251, 191, 36, 0.1); }
        .error__button--active { background-color: #fbbf24; border: 2px solid #fbbf24; color: #450a0a; }

        .scene { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); width: 400px; height: 500px; }

        /* Cone */
        .cone { position: absolute; bottom: 0; right: 280px; width: 0; height: 0; border-left: 30px solid transparent; border-right: 30px solid transparent; border-bottom: 80px solid #f97316; z-index: 20; }
        .cone::after { content: ''; position: absolute; top: 30px; left: -20px; width: 40px; height: 20px; background: white; }
        .cone-base { position: absolute; bottom: -10px; right: 270px; width: 80px; height: 10px; background: #f97316; z-index: 20; border-radius: 5px; }

        /* Worker Standing */
        .worker { position: absolute; bottom: 0; right: 50px; width: 220px; height: 420px; }

        .torso { position: absolute; bottom: 140px; left: 45px; width: 110px; height: 130px; background: #f97316; border-radius: 25px 25px 10px 10px; z-index: 5; box-shadow: inset 0 -5px 10px rgba(0,0,0,0.1); }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 20px; } .vs-2 { right: 20px; } .vest-h { position: absolute; bottom: 30px; height: 20px; width: 100%; background: #cbd5e1; }
        .shirt-collar { position: absolute; top: -5px; left: 35px; width: 40px; height: 20px; background: #1e293b; border-radius: 0 0 20px 20px; z-index: 6; }

        .leg { position: absolute; bottom: 0; width: 40px; height: 150px; background: #1e3a8a; border-radius: 10px; z-index: 4; }
        .leg-left { left: 55px; } .leg-right { right: 75px; }
        .boot { position: absolute; bottom: 0; width: 50px; height: 25px; background: #3f2c22; border-radius: 10px 10px 0 0; border-bottom: 5px solid #1c1917; z-index: 5; }
        .boot-left { left: 50px; } .boot-right { right: 70px; }

        .head-group { position: absolute; top: 60px; left: 60px; width: 80px; height: 90px; transform-origin: bottom center; z-index: 10; animation: headBob 1s infinite alternate; }
        .neck { position: absolute; bottom: 0px; left: 25px; width: 30px; height: 25px; background: #f1dca7; border-radius: 10px; }
        .face { position: absolute; top: 0; width: 70px; height: 75px; left: 5px; background: #f1dca7; border-radius: 20px; }
        .helmet { position: absolute; top: -20px; left: -5px; width: 90px; height: 45px; background: #fbbf24; border-radius: 50px 50px 10px 10px; border-bottom: 5px solid #d97706; }

        .arm { position: absolute; width: 30px; height: 100px; background: #1e293b; border-radius: 15px; transform-origin: 15px 15px; z-index: 6; }
        
        /* Hammering Arm */
        .arm-right { 
            top: 150px; right: 55px; 
            transform-origin: 15px 15px; 
            animation: hammer 1s infinite ease-in;
        }
        /* Hand gripping handle */
        .hand-right { position: absolute; bottom: -5px; left: -2px; width: 35px; height: 35px; background: #f1dca7; border-radius: 50%; z-index: 8; }
        
        /* Hammer Object */
        .hammer-handle { position: absolute; bottom: -40px; left: 10px; width: 10px; height: 80px; background: #78350f; transform: rotate(10deg); z-index: 7; }
        .hammer-head { position: absolute; bottom: -50px; left: -15px; width: 50px; height: 30px; background: #475569; border-radius: 5px; transform: rotate(10deg); z-index: 9; }

        /* Left Arm (Stabilizing) */
        .arm-left { top: 150px; left: 55px; transform: rotate(20deg); }
        .forearm-left { position: absolute; bottom: -30px; width: 30px; height: 50px; background: #f1dca7; border-radius: 15px; transform-origin: top center; transform: rotate(-45deg); }

        /* Spark Effect */
        .spark { position: absolute; width: 10px; height: 10px; background: #fbbf24; border-radius: 50%; opacity: 0; z-index: 30; }
        .s1 { bottom: 150px; right: -20px; animation: sparkFly 1s infinite 0.4s; }
        .s2 { bottom: 160px; right: -40px; animation: sparkFly 1s infinite 0.5s; }

        @keyframes hammer {
            0% { transform: rotate(-20deg); }
            50% { transform: rotate(-90deg); }
            100% { transform: rotate(-20deg); }
        }
        @keyframes headBob { 0% { transform: translateY(0); } 100% { transform: translateY(2px); } }
        @keyframes sparkFly { 
            0% { opacity: 1; transform: scale(1); } 
            100% { opacity: 0; transform: translate(20px, 20px) scale(0); } 
        }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">503</h1>
        <div class="error__subtitle">Sedang Renovasi</div>
        <div class="error__description">Situs ini sedang dalam perbaikan rutin untuk meningkatkan kekokohan struktur. Silakan kembali lagi nanti.</div>
        <a href="/" class="error__button error__button--active">COBA LAGI</a>
        <button onclick="history.back()" class="error__button">KEMBALI</button>
    </div>
    <div class="scene">
        <div class="cone"></div><div class="cone-base"></div>
        <div class="spark s1"></div><div class="spark s2"></div>
        <div class="worker">
            <div class="leg leg-left"></div><div class="leg leg-right"></div>
            <div class="boot boot-left"></div><div class="boot boot-right"></div>
            <div class="torso">
                <div class="vest-stripe vs-1"></div><div class="vest-stripe vs-2"></div><div class="vest-h"></div><div class="shirt-collar"></div>
            </div>
            <div class="head-group">
                <div class="neck"></div><div class="face"></div><div class="helmet"></div>
            </div>
            <div class="arm arm-left"><div class="forearm-left"></div></div>
            <div class="arm arm-right">
                <div class="hand-right"></div>
                <div class="hammer-handle"></div><div class="hammer-head"></div>
            </div>
        </div>
    </div>
</body>
</html>