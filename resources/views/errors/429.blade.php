<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Too Many Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; width: 100%; margin: 0; overflow: hidden; background: linear-gradient(135deg, #450a0a 0%, #891313 100%); font-family: 'League Spartan', sans-serif; }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); background-size: 50px 50px; pointer-events: none; }
        .error { position: absolute; left: 10%; top: 50%; transform: translateY(-50%); color: #ffffff; z-index: 10; max-width: 500px; }
        .error__title { font-size: 8em; font-weight: 900; line-height: 1; text-shadow: 4px 4px 0px #2a0404; color: #fca5a5; margin: 0; }
        .error__subtitle { font-size: 2.5em; font-weight: 700; margin-bottom: 20px; color: #fbbf24; }
        .error__description { opacity: 0.9; font-size: 1.2em; line-height: 1.5; color: #e2e8f0; margin-bottom: 40px; }
        .error__button { padding: 12px 30px; border: 2px solid #fbbf24; background-color: transparent; border-radius: 8px; color: #fbbf24; cursor: pointer; transition: 0.2s; font-size: 1em; font-weight: 700; text-decoration: none; display: inline-block; margin-right: 15px; }
        .error__button:hover { background-color: rgba(251, 191, 36, 0.1); }
        .error__button--active { background-color: #fbbf24; border: 2px solid #fbbf24; color: #450a0a; }

        .scene { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); width: 400px; height: 500px; }
        
        .pile { position: absolute; bottom: 0; right: 50px; width: 300px; height: 200px; z-index: 20; }
        .brick-pile { position: absolute; width: 60px; height: 30px; background: #ef4444; border: 2px solid #b91c1c; }
        .bp1 { bottom: 0; left: 50px; } .bp2 { bottom: 0; left: 110px; } .bp3 { bottom: 0; left: 170px; }
        .bp4 { bottom: 30px; left: 80px; } .bp5 { bottom: 30px; left: 140px; }
        .bp6 { bottom: 60px; left: 110px; } 
        /* Falling bricks */
        .bp-fall { top: -100px; left: 110px; animation: fallStack 2s infinite ease-in; }

        .worker { position: absolute; bottom: 0; right: 100px; width: 200px; height: 300px; z-index: 5; }
        .head-group { position: absolute; bottom: 50px; left: 60px; width: 80px; height: 90px; transform: rotate(10deg); }
        .helmet { position: absolute; top: -20px; left: -5px; width: 90px; height: 45px; background: #fbbf24; border-radius: 50px 50px 10px 10px; border-bottom: 5px solid #d97706; }
        .face { position: absolute; top: 0; width: 70px; height: 75px; left: 5px; background: #f1dca7; border-radius: 20px; }
        
        /* Hands up trying to stop */
        .arm { position: absolute; width: 30px; height: 90px; background: #1e293b; border-radius: 15px; }
        .arm-left { bottom: 80px; left: 20px; transform: rotate(-150deg); }
        .arm-right { bottom: 80px; right: 60px; transform: rotate(150deg); }

        .sweat { position: absolute; top: -30px; right: 0; width: 10px; height: 15px; background: #fff; border-radius: 50%; opacity: 0.7; animation: sweatDrop 1s infinite; }

        @keyframes fallStack { 
            0% { top: -100px; opacity: 0; } 
            50% { opacity: 1; } 
            100% { top: 90px; opacity: 1; } 
        }
        @keyframes sweatDrop { 0% { top: -30px; opacity: 1; } 100% { top: 0px; opacity: 0; } }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">429</h1>
        <div class="error__subtitle">Terlalu Banyak Permintaan</div>
        <div class="error__description">Waduh! Anda memberi tugas terlalu cepat. Kuli kami kewalahan menumpuk bata. Harap tunggu sebentar.</div>
        <a href="/" class="error__button error__button--active">TUNGGU SEBENTAR</a>
    </div>
    <div class="scene">
        <div class="pile">
            <div class="brick-pile bp1"></div><div class="brick-pile bp2"></div><div class="brick-pile bp3"></div>
            <div class="brick-pile bp4"></div><div class="brick-pile bp5"></div>
            <div class="brick-pile bp6"></div>
            <div class="brick-pile bp-fall"></div>
        </div>
        <div class="worker">
            <div class="arm arm-left"></div><div class="arm arm-right"></div>
            <div class="head-group">
                <div class="face"></div><div class="helmet"></div>
                <div class="sweat"></div>
            </div>
        </div>
    </div>
</body>
</html>