<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Page Expired</title>
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
        
        /* Sitting Worker */
        .worker { position: absolute; bottom: 0; right: 80px; width: 200px; height: 300px; }
        
        .torso { position: absolute; bottom: 80px; left: 45px; width: 110px; height: 120px; background: #f97316; border-radius: 25px 25px 10px 10px; z-index: 5; }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 20px; } .vs-2 { right: 20px; } .vest-h { position: absolute; bottom: 30px; height: 20px; width: 100%; background: #cbd5e1; }
        
        /* Legs Sitting */
        .leg { position: absolute; bottom: 10px; width: 120px; height: 45px; background: #1e3a8a; border-radius: 10px; z-index: 4; }
        .leg-left { left: 30px; bottom: 35px; transform: rotate(-10deg); }
        .leg-right { left: 30px; bottom: 35px; transform: rotate(-5deg); } /* Crossed/Stacked */

        .boot { position: absolute; left: 140px; bottom: 20px; width: 25px; height: 50px; background: #3f2c22; border-radius: 0 10px 10px 0; border-right: 5px solid #1c1917; z-index: 5; transform: rotate(10deg); }
        
        .head-group { position: absolute; top: 30px; left: 60px; width: 80px; height: 90px; transform-origin: bottom center; z-index: 10; animation: dozeOff 4s infinite ease-in-out; }
        .neck { position: absolute; bottom: -15px; left: 25px; width: 30px; height: 25px; background: #f1dca7; border-radius: 10px; }
        .face { position: absolute; top: 0; width: 70px; height: 75px; left: 5px; background: #f1dca7; border-radius: 20px; }
        /* No helmet on head */
        
        .helmet-floor { 
            position: absolute; bottom: 0; left: 0; width: 80px; height: 40px; 
            background: #fbbf24; border-radius: 40px 40px 10px 10px; border-bottom: 5px solid #d97706; 
            z-index: 2; transform: rotate(-10deg);
        }

        .arm { position: absolute; width: 30px; height: 90px; background: #1e293b; border-radius: 15px; transform-origin: 15px 15px; }
        .arm-right { top: 120px; right: 30px; transform: rotate(-40deg); z-index: 6; }
        .coffee { position: absolute; bottom: -10px; left: -5px; width: 30px; height: 40px; background: #fff; border-radius: 0 0 5px 5px; z-index: 10; }
        .coffee::before { content:''; position: absolute; top: -10px; width: 30px; height: 10px; background: #e2e8f0; border-radius: 50%; }
        .steam { position: absolute; top: -30px; left: 10px; width: 10px; height: 20px; background: rgba(255,255,255,0.5); filter: blur(4px); animation: steam 2s infinite; }

        @keyframes dozeOff { 0%, 100% { transform: rotate(5deg); } 50% { transform: rotate(15deg); } }
        @keyframes steam { 0% { transform: translateY(0); opacity: 0; } 50% { opacity: 1; } 100% { transform: translateY(-20px); opacity: 0; } }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">419</h1>
        <div class="error__subtitle">Sesi Habis</div>
        <div class="error__description">Waktu istirahat telah selesai. Halaman ini kadaluarsa karena terlalu lama ditinggal ngopi.</div>
        <button onclick="location.reload()" class="error__button error__button--active">REFRESH (KERJA LAGI)</button>
    </div>
    <div class="scene">
        <div class="worker">
            <div class="helmet-floor"></div>
            <div class="leg leg-left"></div><div class="leg leg-right"></div>
            <div class="boot"></div>
            <div class="torso">
                <div class="vest-stripe vs-1"></div><div class="vest-stripe vs-2"></div><div class="vest-h"></div>
            </div>
            <div class="head-group">
                <div class="neck"></div><div class="face"></div>
                <!-- Sleep Zzz -->
                <div style="position:absolute; top:-40px; right:-20px; color:#fff; font-weight:bold; font-size:24px; animation:steam 3s infinite;">Zzz</div>
            </div>
            <div class="arm arm-right">
                <div class="coffee"><div class="steam"></div></div>
            </div>
        </div>
    </div>
</body>
</html>