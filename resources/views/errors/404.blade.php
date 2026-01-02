<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Blueprint Error</title>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* BASE STYLES */
        html, body { height: 100%; width: 100%; margin: 0px; overflow: hidden; background: linear-gradient(135deg, #450a0a 0%, #891313 100%); font-family: 'League Spartan', sans-serif; }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px); background-size: 50px 50px; pointer-events: none; }
        
        /* TEXT */
        .error { position: absolute; left: 10%; top: 50%; transform: translateY(-50%); color: #ffffff; z-index: 10; max-width: 500px; }
        .error__title { font-size: 8em; font-weight: 900; line-height: 1; text-shadow: 4px 4px 0px #2a0404; color: #fca5a5; margin: 0; }
        .error__subtitle { font-size: 2.5em; font-weight: 700; margin-bottom: 20px; color: #fbbf24; }
        .error__description { opacity: 0.9; font-size: 1.2em; line-height: 1.5; color: #e2e8f0; margin-bottom: 40px; }
        .error__button { padding: 12px 30px; border: 2px solid #fbbf24; background-color: transparent; border-radius: 8px; color: #fbbf24; cursor: pointer; transition: 0.2s; font-size: 1em; font-weight: 700; font-family: 'League Spartan', sans-serif; text-decoration: none; display: inline-block; margin-right: 15px; }
        .error__button:hover { background-color: rgba(251, 191, 36, 0.1); }
        .error__button--active { background-color: #fbbf24; border: 2px solid #fbbf24; color: #450a0a; }
        .error__button--active:hover { box-shadow: 0px 0px 15px 0px rgba(251, 191, 36, 0.5); background-color: #f59e0b; }

        /* SCENE & WORKER FIXED */
        .scene { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); width: 400px; height: 500px; }
        
        .worker { position: absolute; bottom: 0; right: 50px; width: 220px; height: 420px; }

        /* Joint Fix: Use rounded ends and negative margins/overlaps */
        .torso { 
            position: absolute; bottom: 140px; left: 45px; width: 110px; height: 130px; 
            background: #f97316; border-radius: 25px 25px 10px 10px; z-index: 5; 
            box-shadow: inset 0 -5px 10px rgba(0,0,0,0.1);
        }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 20px; } .vs-2 { right: 20px; }
        .vest-h { position: absolute; bottom: 30px; height: 20px; width: 100%; background: #cbd5e1; }

        .shirt-collar { position: absolute; top: -5px; left: 35px; width: 40px; height: 20px; background: #1e293b; border-radius: 0 0 20px 20px; z-index: 6; }

        /* Legs - tucked nicely under torso */
        .leg { position: absolute; bottom: 0; width: 40px; height: 150px; background: #1e3a8a; border-radius: 10px; z-index: 4; }
        .leg-left { left: 55px; transform: rotate(5deg); }
        .leg-right { right: 75px; transform: rotate(-5deg); }
        
        /* Boots */
        .boot { position: absolute; bottom: 0; width: 50px; height: 25px; background: #3f2c22; border-radius: 10px 10px 0 0; border-bottom: 5px solid #1c1917; }
        .boot-left { left: 50px; z-index: 5; }
        .boot-right { right: 70px; z-index: 5; }

        /* Head Group */
        .head-group { 
            position: absolute; top: 75px; left: 75px; width: 80px; height: 90px; 
            transform-origin: bottom center; animation: headTilt 4s ease-in-out infinite; z-index: 10; 
        }
        .neck { position: absolute; bottom: -15px; left: 25px; width: 30px; height: 25px; background: #f1dca7; border-radius: 10px; }
        .face { position: absolute; top: 0; width: 70px; height: 75px; left: 5px; background: #f1dca7; border-radius: 20px; }
        .helmet { 
            position: absolute; top: -20px; left: -5px; width: 90px; height: 45px; 
            background: #fbbf24; border-radius: 50px 50px 10px 10px; 
            border-bottom: 5px solid #d97706; 
        }

        /* Arms - Improved joints with circles */
        .arm { position: absolute; width: 30px; height: 100px; background: #1e293b; border-radius: 15px; }
        
        /* Right Arm (Scratching) */
        .arm-right { 
            top: 150px; right: 55px; height: 95px; 
            transform-origin: 15px 15px; /* Pivot at shoulder */
            transform: rotate(-140deg); z-index: 4;
            animation: scratch 2s infinite ease-in-out;
        }
        .hand-right { position: absolute; bottom: -5px; left: -2px; width: 35px; height: 35px; background: #f1dca7; border-radius: 50%; }

        /* Left Arm (Holding BP) */
        .arm-left { 
            top: 150px; left: 55px; height: 80px; 
            transform-origin: 15px 15px; /* Pivot at shoulder */
            transform: rotate(30deg); z-index: 6;
        }
        .forearm-left { 
            position: absolute; bottom: -35px; left: 0; width: 30px; height: 50px; 
            background: #f1dca7; border-radius: 15px;
            transform-origin: top center; transform: rotate(-90deg);
        }

        /* Blueprint */
        .blueprint {
            position: absolute; top: 180px; left: -100px; width: 200px; height: 140px;
            background: #1e40af; border: 3px solid #fff; z-index: 20;
            transform: rotate(-10deg); box-shadow: 10px 10px 20px rgba(0,0,0,0.3);
            animation: shake 4s ease-in-out infinite;
        }
        .bp-lines { position: absolute; width: 100%; height: 100%; background-image: linear-gradient(rgba(255,255,255,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.3) 1px, transparent 1px); background-size: 20px 20px; }
        .bp-mark { position: absolute; bottom: 10px; right: 10px; border: 1px solid white; color: white; padding: 2px 5px; font-size: 10px; font-weight: bold; }

        /* Question Marks */
        .q-mark { position: absolute; font-weight: 900; color: #fbbf24; font-size: 3em; animation: floatUp 2s infinite; opacity: 0; }
        .q1 { right: 100px; top: 40px; animation-delay: 0s; }
        .q2 { right: 60px; top: 20px; animation-delay: 0.5s; font-size: 2em; }

        @keyframes headTilt { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-5deg); } 75% { transform: rotate(5deg); } }
        @keyframes scratch { 0%, 100% { transform: rotate(-140deg); } 50% { transform: rotate(-125deg); } }
        @keyframes shake { 0%, 100% { transform: rotate(-10deg); } 50% { transform: rotate(-8deg) translateY(2px); } }
        @keyframes floatUp { 0% { transform: translateY(10px); opacity: 0; } 50% { opacity: 1; } 100% { transform: translateY(-30px); opacity: 0; } }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">404</h1>
        <div class="error__subtitle">Hmm... Gambar Tidak Sesuai</div>
        <div class="error__description">Halaman yang Anda cari tidak ditemukan dalam <i>blueprint</i> kami. Revisi arsitek mungkin belum sampai.</div>
        <a href="/" class="error__button error__button--active">KEMBALI KE HOME</a>
        <button onclick="history.back()" class="error__button">KEMBALI</button>
    </div>

    <div class="scene">
        <div class="q-mark q1">?</div>
        <div class="q-mark q2">?</div>

        <div class="worker">
            <div class="leg leg-left"></div>
            <div class="leg leg-right"></div>
            <div class="boot boot-left"></div>
            <div class="boot boot-right"></div>
            <div class="torso">
                <div class="vest-stripe vs-1"></div>
                <div class="vest-stripe vs-2"></div>
                <div class="vest-h"></div>
                <div class="shirt-collar"></div>
            </div>
            <div class="head-group">
                <div class="neck"></div>
                <div class="face"></div>
                <div class="helmet"></div>
            </div>
            <div class="arm arm-right"><div class="hand-right"></div></div>
            <div class="arm arm-left"><div class="forearm-left"></div></div>
            <div class="blueprint">
                <div class="bp-lines"></div>
                <div class="bp-mark">404</div>
            </div>
        </div>
    </div>
</body>
</html>