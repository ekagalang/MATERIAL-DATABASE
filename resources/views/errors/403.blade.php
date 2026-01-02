<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Forbidden</title>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* Base styles identical to 401/404 */
        html, body { height: 100%; width: 100%; margin: 0px; overflow: hidden; background: linear-gradient(135deg, #450a0a 0%, #891313 100%); font-family: 'League Spartan', sans-serif; }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); background-size: 50px 50px; pointer-events: none; }
        .error { position: absolute; left: 10%; top: 50%; transform: translateY(-50%); color: #ffffff; z-index: 10; max-width: 500px; }
        .error__title { font-size: 8em; font-weight: 900; line-height: 1; text-shadow: 4px 4px 0px #2a0404; color: #fca5a5; margin-bottom: 0px; }
        .error__subtitle { font-size: 2.5em; font-weight: 700; margin-bottom: 20px; color: #fbbf24; }
        .error__description { opacity: 0.9; font-size: 1.2em; line-height: 1.5; color: #e2e8f0; margin-bottom: 40px; }
        .error__button { padding: 12px 30px; border: 2px solid #fbbf24; background-color: transparent; border-radius: 8px; color: #fbbf24; cursor: pointer; transition: 0.2s; font-size: 1em; font-weight: 700; font-family: 'League Spartan', sans-serif; text-decoration: none; display: inline-block; margin-right: 15px; }
        .error__button:hover { background-color: rgba(251, 191, 36, 0.1); }
        .error__button--active { background-color: #fbbf24; border: 2px solid #fbbf24; color: #450a0a; }
        .error__button--active:hover { box-shadow: 0px 0px 15px 0px rgba(251, 191, 36, 0.5); background-color: #f59e0b; }
        
        .scene { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); width: 400px; height: 500px; }

        /* Caution Tape */
        .caution-tape {
            position: absolute;
            width: 120%;
            height: 40px;
            background: #fbbf24;
            color: #000;
            font-weight: 900;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-15deg);
            top: 200px;
            left: -10%;
            z-index: 20;
            box-shadow: 0 5px 10px rgba(0,0,0,0.3);
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            white-space: nowrap;
            overflow: hidden;
        }
        .caution-tape::after {
            content: "DANGER - DO NOT ENTER - DANGER - DO NOT ENTER";
            animation: slideText 2s linear infinite;
        }

        @keyframes slideText {
            0% { transform: translateX(0); }
            100% { transform: translateX(-20px); }
        }

        .worker { position: absolute; bottom: 0; right: 50px; width: 200px; height: 400px; }
        .leg { position: absolute; bottom: 0; width: 45px; height: 140px; background: #1e3a8a; border-radius: 5px; }
        .leg-left { left: 40px; } .leg-right { right: 40px; }
        .boot { position: absolute; bottom: 0; width: 55px; height: 25px; background: #3f2c22; border-radius: 10px 10px 0 0; }
        .boot-left { left: 35px; border-bottom: 5px solid #1c1917; } .boot-right { right: 35px; border-bottom: 5px solid #1c1917; }
        .torso { position: absolute; bottom: 130px; left: 35px; width: 130px; height: 140px; background: #f97316; border-radius: 20px 20px 0 0; z-index: 2; overflow: hidden; }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 20px; width: 100%; }
        .vs-v { width: 25px; height: 100%; top: 0; } .vs-v-1 { left: 25px; } .vs-v-2 { right: 25px; } .vs-h { bottom: 30px; }
        .shirt-collar { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 50px; height: 30px; background: #1e293b; border-radius: 0 0 25px 25px; }
        .head-group { position: absolute; top: 60px; left: 65px; width: 90px; height: 100px; transform-origin: bottom center; z-index: 5; }
        .neck { position: absolute; bottom: -10px; left: 25px; width: 40px; height: 30px; background: #f1dca7; border-radius: 0 0 10px 10px; }
        .face { position: absolute; top: 20px; width: 70px; height: 70px; left: 10px; background: #f1dca7; border-radius: 15px; }
        .helmet { position: absolute; top: 0; width: 90px; height: 45px; background: #fbbf24; border-radius: 50px 50px 10px 10px; border-bottom: 5px solid #d97706; z-index: 10; }
        .helmet::before { content: ''; position: absolute; top: 8px; left: 15px; width: 20px; height: 10px; background: rgba(255,255,255,0.4); border-radius: 20px; }
        .arm { position: absolute; width: 35px; height: 100px; background: #1e293b; border-radius: 20px; }

        /* Arms crossed (Defensive/Blocking) */
        .arm-right { top: 140px; right: 40px; height: 90px; transform: rotate(-45deg); z-index: 6; }
        .arm-left { top: 140px; left: 40px; height: 90px; transform: rotate(45deg); z-index: 6; }
        .hand { position: absolute; bottom: -10px; width: 35px; height: 35px; background: #f1dca7; border-radius: 50%; }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">403</h1>
        <div class="error__subtitle">Area Terbatas</div>
        <div class="error__description">
            BERBAHAYA! Anda tidak memiliki izin untuk memasuki zona konstruksi ini. Harap segera meninggalkan area.
        </div>
        <a href="/" class="error__button error__button--active">KELUAR DARI ZONA</a>
        <button onclick="history.back()" class="error__button">KEMBALI</button>
    </div>

    <div class="scene">
        <div class="caution-tape"></div>
        <div class="worker">
            <div class="head-group">
                <div class="helmet"></div>
                <div class="face"></div>
                <div class="neck"></div>
            </div>
            <div class="torso">
                <div class="shirt-collar"></div>
                <div class="vest-stripe vs-v vs-v-1"></div>
                <div class="vest-stripe vs-v vs-v-2"></div>
                <div class="vest-stripe vs-h"></div>
            </div>
            <div class="leg leg-left"></div>
            <div class="leg leg-right"></div>
            <div class="boot boot-left"></div>
            <div class="boot boot-right"></div>
            <div class="arm arm-right"><div class="hand"></div></div>
            <div class="arm arm-left"><div class="hand"></div></div>
        </div>
    </div>
</body>
</html>