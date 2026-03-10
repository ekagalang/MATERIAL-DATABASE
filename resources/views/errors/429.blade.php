<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Too Many Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        html, body { 
            height: 100%; width: 100%; margin: 0; overflow: hidden; 
            background: linear-gradient(135deg, #450a0a 0%, #891313 100%); 
            font-family: 'Nunito', sans-serif; 
        }
        body::before { 
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), 
                              linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); 
            background-size: 50px 50px; pointer-events: none; 
        }
        
        .error { 
            position: absolute; left: 10%; top: 50%; transform: translateY(-50%); 
            color: #ffffff; z-index: 10; max-width: 500px; 
        }
        .error__title { 
            font-size: 8em; font-weight: 900; line-height: 1; 
            text-shadow: 4px 4px 0px #2a0404; color: #fca5a5; margin: 0; 
        }
        .error__subtitle { 
            font-size: 2.5em; font-weight: 700; margin-bottom: 20px; color: #fbbf24; 
        }
        .error__description { 
            opacity: 0.9; font-size: 1.2em; line-height: 1.5; color: #e2e8f0; margin-bottom: 40px; 
        }
        .error__button { 
            padding: 12px 30px; border: 2px solid #fbbf24; background-color: transparent; 
            border-radius: 8px; color: #fbbf24; cursor: pointer; transition: 0.2s; 
            font-size: 1em; font-weight: 700; text-decoration: none; 
            display: inline-block; margin-right: 15px; margin-bottom: 15px;
        }
        .error__button:hover { background-color: rgba(251, 191, 36, 0.1); }
        .error__button--active { background-color: #fbbf24; color: #450a0a; }

        /* SCENE & WORKER ANIMATION */
        .scene { 
            position: absolute; right: 10%; top: 50%; transform: translateY(-50%); 
            width: 400px; height: 500px; 
        }
        
        /* Pekerja Panik */
        .worker { 
            position: absolute; bottom: 20px; right: 80px; width: 200px; height: 250px; 
            animation: panicShake 0.3s infinite;
        }

        /* Kaki Menahan Beban */
        .legs { position: absolute; bottom: 0; left: 0; width: 100%; height: 60px; }
        .leg { position: absolute; width: 100px; height: 40px; background: #1e3a8a; border-radius: 20px; }
        .leg-left { bottom: 0; left: -25px; transform: rotate(-30deg); z-index: 2; }
        .leg-right { bottom: 7px; left: 99px; transform: rotate(20deg); z-index: 1; }
        .boot { 
            position: absolute; right: -25px; bottom: 2px; width: 45px; height: 35px; 
            background: #3f2c22; border-radius: 10px 20px 10px 10px; border-right: 5px solid #1c1917;
        }
        .leg-left .boot { right: auto; left: -20px; transform: scaleX(-1); border-right: none; border-left: 5px solid #1c1917; }

        /* Badan (Torso) Condong ke Belakang */
        .torso { 
            position: absolute; bottom: 35px; left: 35px; width: 100px; height: 120px; 
            background: #f97316; border-radius: 40px 40px 10px 10px; z-index: 3; 
            transform-origin: bottom center;
            transform: rotate(-15deg);
        }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 15px; } .vs-2 { right: 15px; } 
        .vest-h { position: absolute; bottom: 25px; height: 20px; width: 100%; background: #cbd5e1; }

        /* Lengan Panik ke Atas */
        .arm { position: absolute; width: 28px; height: 110px; background: #1e293b; border-radius: 15px; transform-origin: bottom center; }
        .arm-left { top: -80px; left: -5px; z-index: -1; transform: rotate(-30deg); animation: armWobbleLeft 0.5s infinite alternate; }
        .arm-right { top: -80px; right: -5px; z-index: 5; transform: rotate(30deg); animation: armWobbleRight 0.4s infinite alternate; }
        .hand {
            position: absolute; top: -10px; left: 4px; width: 20px; height: 25px;
            background: #f1dca7; border-radius: 50%; z-index: 2;
        }

        /* Kepala Melihat ke Atas */
        .head-group { 
            position: absolute; top: -75px; left: 5px; width: 70px; height: 80px; 
            transform-origin: bottom center; z-index: 4; 
            transform: rotate(25deg);
        }
        .neck { 
            position: absolute; bottom: -10px; left: 25px; width: 20px; height: 25px; 
            background: #e5c38b; border-radius: 10px; 
        }
        .face { 
            position: absolute; top: 0; left: 0; width: 70px; height: 75px; 
            background: #f1dca7; border-radius: 35px 35px 25px 25px; 
        }
        
        /* Ekspresi Panik */
        .eye { position: absolute; background: #fff; border-radius: 50%; }
        .eye::after { content: ''; position: absolute; width: 6px; height: 6px; background: #000; border-radius: 50%; top: 5px; left: 4px; }
        .eye-left { top: 30px; left: 10px; width: 18px; height: 18px; }
        .eye-right { top: 30px; right: 15px; width: 22px; height: 22px; } /* Mata kanan lebih besar karena kaget */
        
        .mouth {
            position: absolute; top: 55px; left: 25px; width: 14px; height: 22px;
            background: #450a0a; border-radius: 10px;
            animation: panting 0.3s infinite alternate;
        }

        .helmet { 
            position: absolute; top: -15px; left: -10px; width: 90px; height: 45px; 
            background: #fbbf24; border-radius: 45px 45px 10px 10px; 
            border-bottom: 6px solid #d97706; z-index: 5;
            animation: helmetRattle 0.2s infinite alternate;
        }

        /* Keringat Bercucuran */
        .sweat { 
            position: absolute; width: 8px; height: 12px; background: #bae6fd; 
            border-radius: 50% 50% 5px 5px; opacity: 0.8;
        }
        .sw1 { top: 20px; left: -5px; animation: sweatFly 0.6s infinite ease-in; }
        .sw2 { top: 10px; right: -5px; animation: sweatFly 0.8s infinite ease-in 0.3s; }

        /* Tumpukan Bata yang Goyah */
        .brick-stack {
            position: absolute; top: 110px; right: 50px; width: 140px; height: 150px; z-index: 6;
            animation: stackWobble 0.5s infinite alternate;
        }
        .brick { 
            position: absolute; width: 55px; height: 25px; background: #ef4444; 
            border: 2px solid #b91c1c; border-radius: 2px;
            box-shadow: 2px 2px 0 rgba(0,0,0,0.2);
        }
        /* Susunan bata tidak rapi */
        .bs1 { bottom: 0; left: 40px; transform: rotate(-2deg); }
        .bs2 { bottom: 25px; left: 30px; transform: rotate(4deg); }
        .bs3 { bottom: 25px; left: 80px; transform: rotate(-5deg); }
        .bs4 { bottom: 50px; left: 45px; transform: rotate(2deg); }
        .bs5 { bottom: 75px; left: 35px; transform: rotate(-4deg); }
        .bs6 { bottom: 100px; left: 50px; transform: rotate(5deg); }

        /* Hujan Bata dari Atas */
        .falling-bricks {
            position: absolute; top: -100px; right: 20px; width: 200px; height: 400px; z-index: 7;
        }
        .fb-1 { left: 80px; animation: rainBricks 1.2s infinite ease-in 0s; }
        .fb-2 { left: 40px; animation: rainBricks 1.5s infinite ease-in 0.5s; transform: rotate(15deg); }
        .fb-3 { left: 120px; animation: rainBricks 1.8s infinite ease-in 0.8s; transform: rotate(-25deg); }
        .fb-4 { left: 60px; animation: rainBricks 1.4s infinite ease-in 1.2s; transform: rotate(45deg); }

        /* KEYFRAMES ANIMASI */
        @keyframes panicShake {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(-2px, 1px); }
            50% { transform: translate(1px, -1px); }
            75% { transform: translate(-1px, 2px); }
        }
        @keyframes armWobbleLeft { 0% { transform: rotate(-35deg); } 100% { transform: rotate(-25deg); } }
        @keyframes armWobbleRight { 0% { transform: rotate(25deg); } 100% { transform: rotate(35deg); } }
        @keyframes stackWobble { 0% { transform: rotate(-3deg) translateX(-2px); } 100% { transform: rotate(3deg) translateX(2px); } }
        @keyframes helmetRattle { 0% { transform: rotate(-2deg) translateY(0); } 100% { transform: rotate(2deg) translateY(-2px); } }
        @keyframes panting { 0% { transform: scaleY(1); } 100% { transform: scaleY(1.3); } }
        
        @keyframes sweatFly {
            0% { transform: translate(0, 0) scale(1); opacity: 1; }
            100% { transform: translate(-20px, 30px) scale(0.5); opacity: 0; }
        }
        
        @keyframes rainBricks {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            70% { opacity: 1; }
            100% { transform: translateY(400px) rotate(70deg); opacity: 0; }
        }

        /* Penyesuaian Responsif untuk Layar Kecil */
        @media (max-width: 900px) {
            .error { left: 5%; top: 20%; max-width: 90%; text-align: center; transform: none; }
            .scene { right: 50%; top: auto; bottom: 5%; transform: translateX(50%) scale(0.8); }
        }
        @media (max-width: 500px) {
            .error__title { font-size: 6em; }
            .error__subtitle { font-size: 1.8em; }
            .scene { transform: translateX(50%) scale(0.65); bottom: -50px; }
        }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">429</h1>
        <div class="error__subtitle">Terlalu Banyak Permintaan</div>
        <div class="error__description">Waduh! Anda memberi tugas terlalu cepat. Pekerja kami kewalahan menahan tumpukan bata. Harap tunggu sebentar.</div>
        <a href="/" class="error__button error__button--active">TUNGGU SEBENTAR</a>
        <button onclick="history.back()" class="error__button">KEMBALI</button>
    </div>
    
    <div class="scene">
        
        <!-- Hujan Bata dari langit -->
        <div class="falling-bricks">
            <div class="brick fb-1"></div>
            <div class="brick fb-2"></div>
            <div class="brick fb-3"></div>
            <div class="brick fb-4"></div>
        </div>

        <!-- Tumpukan bata yang ditahan oleh pekerja -->
        <div class="brick-stack">
            <div class="brick bs1"></div>
            <div class="brick bs2"></div>
            <div class="brick bs3"></div>
            <div class="brick bs4"></div>
            <div class="brick bs5"></div>
            <div class="brick bs6"></div>
        </div>

        <div class="worker">
            <div class="legs">
                <div class="leg leg-left">
                    <div class="boot"></div>
                </div>
                <div class="leg leg-right">
                    <div class="boot"></div>
                </div>
            </div>
            
            <div class="torso">
                <div class="vest-stripe vs-1"></div>
                <div class="vest-stripe vs-2"></div>
                <div class="vest-h"></div>
                
                <!-- Tangan kiri berusaha menahan di atas -->
                <div class="arm arm-left">
                    <div class="hand"></div>
                </div>
                
                <div class="head-group">
                    <div class="neck"></div>
                    <div class="face">
                        <div class="eye eye-left"></div>
                        <div class="eye eye-right"></div>
                        <div class="mouth"></div>
                        <div class="sweat sw1"></div>
                        <div class="sweat sw2"></div>
                    </div>
                    <div class="helmet"></div>
                </div>
                
                <!-- Tangan kanan berusaha menahan di atas -->
                <div class="arm arm-right">
                    <div class="hand"></div>
                </div>
            </div>
        </div>
        
    </div>
</body>
</html>