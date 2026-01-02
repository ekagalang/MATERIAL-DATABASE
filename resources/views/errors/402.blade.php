<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>402 - Payment Required</title>
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
        .worker { position: absolute; bottom: 0; right: 50px; width: 220px; height: 420px; }
        
        /* Reusing corrected worker geometry */
        .torso { position: absolute; bottom: 140px; left: 45px; width: 110px; height: 130px; background: #f97316; border-radius: 25px 25px 10px 10px; z-index: 5; box-shadow: inset 0 -5px 10px rgba(0,0,0,0.1); }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 20px; } .vs-2 { right: 20px; } .vest-h { position: absolute; bottom: 30px; height: 20px; width: 100%; background: #cbd5e1; }
        .shirt-collar { position: absolute; top: -5px; left: 35px; width: 40px; height: 20px; background: #1e293b; border-radius: 0 0 20px 20px; z-index: 6; }
        .leg { position: absolute; bottom: 0; width: 40px; height: 150px; background: #1e3a8a; border-radius: 10px; z-index: 4; }
        .leg-left { left: 55px; } .leg-right { right: 75px; }
        .boot { position: absolute; bottom: 0; width: 50px; height: 25px; background: #3f2c22; border-radius: 10px 10px 0 0; border-bottom: 5px solid #1c1917; z-index: 5; }
        .boot-left { left: 50px; } .boot-right { right: 70px; }
        .head-group { position: absolute; top: 75px; left: 75px; width: 80px; height: 90px; transform-origin: bottom center; z-index: 10; animation: headShake 5s infinite; }
        .neck { position: absolute; bottom: -15px; left: 25px; width: 30px; height: 25px; background: #f1dca7; border-radius: 10px; }
        .face { position: absolute; top: 0; width: 70px; height: 75px; left: 5px; background: #f1dca7; border-radius: 20px; }
        .helmet { position: absolute; top: -20px; left: -5px; width: 90px; height: 45px; background: #fbbf24; border-radius: 50px 50px 10px 10px; border-bottom: 5px solid #d97706; }
        .arm { position: absolute; width: 30px; height: 100px; background: #1e293b; border-radius: 15px; transform-origin: 15px 15px; }

        /* Pose: Holding a long bill */
        .arm-right { top: 150px; right: 55px; transform: rotate(-30deg); z-index: 6; }
        .arm-left { top: 150px; left: 55px; transform: rotate(30deg); z-index: 6; }
        
        .bill {
            position: absolute; top: 180px; left: 70px; width: 80px; height: 250px;
            background: #fff; border: 1px solid #ccc; z-index: 20;
            transform-origin: top center; animation: billWave 3s ease-in-out infinite;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .bill-text { width: 60%; height: 4px; background: #cbd5e1; margin: 8px auto; border-radius: 2px; }
        .bill-total { width: 80%; height: 8px; background: #ef4444; margin: 20px auto 0; border-radius: 2px; }

        /* Floating Coins */
        .coin {
            position: absolute; width: 30px; height: 30px; background: #fbbf24; border: 4px solid #f59e0b; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-weight: bold; color: #b45309;
            animation: floatCoin 3s infinite; opacity: 0;
        }
        .c1 { top: 50px; right: 80px; animation-delay: 0s; }
        .c2 { top: 100px; right: 40px; animation-delay: 1s; }
        
        @keyframes headShake { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-5deg); } 75% { transform: rotate(5deg); } }
        @keyframes billWave { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(2deg); } }
        @keyframes floatCoin { 0% { transform: translateY(0); opacity: 0; } 50% { opacity: 1; } 100% { transform: translateY(-50px); opacity: 0; } }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">402</h1>
        <div class="error__subtitle">Anggaran Habis</div>
        <div class="error__description">Pembayaran diperlukan untuk melanjutkan proyek ini. Mandor tidak bisa beli semen kalau kas kosong!</div>
        <a href="/pricing" class="error__button error__button--active">LUNASI TAGIHAN</a>
    </div>
    <div class="scene">
        <div class="coin c1">$</div>
        <div class="coin c2">$</div>
        <div class="worker">
            <div class="leg leg-left"></div><div class="leg leg-right"></div>
            <div class="boot boot-left"></div><div class="boot boot-right"></div>
            <div class="torso">
                <div class="vest-stripe vs-1"></div><div class="vest-stripe vs-2"></div><div class="vest-h"></div><div class="shirt-collar"></div>
            </div>
            <div class="head-group">
                <div class="neck"></div><div class="face"></div><div class="helmet"></div>
            </div>
            <div class="arm arm-right"></div><div class="arm arm-left"></div>
            <div class="bill">
                <div class="bill-text"></div><div class="bill-text"></div><div class="bill-text"></div>
                <div class="bill-text"></div><div class="bill-text"></div><div class="bill-total"></div>
            </div>
        </div>
    </div>
</body>
</html>