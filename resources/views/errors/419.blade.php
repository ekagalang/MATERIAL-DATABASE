<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Page Expired</title>
    <!-- Memperbaiki link font untuk menggunakan Nunito sesuai dengan CSS -->
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
            width: 400px; height: 400px; 
        }
        .worker { 
            position: absolute; bottom: 20px; right: 80px; width: 200px; height: 250px; 
        }

        /* Kaki */
        .legs { position: absolute; bottom: 0; left: 0; width: 100%; height: 60px; }
        .leg { position: absolute; width: 120px; height: 40px; background: #1e3a8a; border-radius: 20px; }
        .leg-left { bottom: 0; left: 10px; transform: rotate(-15deg); z-index: 2; }
        .leg-right { bottom: 12px; left: 20px; transform: rotate(-195deg); z-index: 1; }
        .boot { 
            position: absolute; right: -25px; bottom: -5px; width: 45px; height: 35px; 
            background: #3f2c22; border-radius: 10px 20px 10px 10px; border-right: 5px solid #1c1917;
        }

        /* Helm Proyek di lantai */
        .helmet-floor { 
            position: absolute; bottom: 0; left: -40px; width: 70px; height: 35px; 
            background: #fbbf24; border-radius: 35px 35px 5px 5px; 
            z-index: 5; transform: rotate(-25deg);
        }
        .helmet-brim { 
            position: absolute; bottom: 0; left: -10%; width: 120%; height: 6px; 
            background: #d97706; border-radius: 3px;
        }

        /* Badan (Torso) */
        .torso { 
            position: absolute; bottom: 35px; left: 30px; width: 100px; height: 120px; 
            background: #f97316; border-radius: 40px 40px 10px 10px; z-index: 3; 
            transform-origin: bottom center;
            animation: breathe 4s infinite ease-in-out;
        }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 15px; } .vs-2 { right: 15px; } 
        .vest-h { position: absolute; bottom: 25px; height: 20px; width: 100%; background: #cbd5e1; }

        /* Lengan Kiri (Tersembunyi di belakang) */
        .arm-left {
            position: absolute; top: 14px; left: -23px; width: 30px; height: 90px;
            background: #0f172a; border-radius: 15px; z-index: -1; transform: rotate(20deg);
        }

        /* Lengan Kanan (Pegang Kopi) */
        .arm-right { 
            position: absolute; top: 20px; right: -9px; width: 30px; height: 95px; 
            background: #1e293b; border-radius: 15px; transform-origin: top center; 
            z-index: 5; animation: armDroop 4s infinite ease-in-out;
        }
        .hand {
            position: absolute; bottom: -5px; left: 2px; width: 20px; height: 20px;
            background: #f1dca7; border-radius: 50%; z-index: 2;
        }

        /* Kepala */
        .head-group { 
            position: absolute; top: -75px; left: 15px; width: 70px; height: 80px; 
            transform-origin: bottom center; z-index: 4; 
            animation: nod 4s infinite ease-in-out;
        }
        .neck { 
            position: absolute; bottom: -10px; left: 25px; width: 20px; height: 25px; 
            background: #e5c38b; border-radius: 10px; 
        }
        .face { 
            position: absolute; top: 0; left: 0; width: 70px; height: 75px; 
            background: #f1dca7; border-radius: 35px 35px 25px 25px; 
        }
        .ear {
            position: absolute; top: 35px; left: 11px; width: 12px; height: 16px;
            background: #e5c38b; border-radius: 6px;
        }
        .eye {
            position: absolute; top: 32px; right: 15px; width: 14px; height: 6px;
            border-bottom: 3px solid #b45309; border-radius: 50%;
        }
        .mouth {
            position: absolute; top: 52px; right: 4px; width: 10px; height: 10px;
            background: #b45309; border-radius: 5px;
            animation: snoreMouth 4s infinite ease-in-out;
        }

        /* Cangkir Kopi */
        .coffee { 
            position: absolute; bottom: -25px; right: -5px; width: 30px; height: 40px; 
            background: #f8fafc; border-radius: 2px 2px 5px 5px; z-index: 1; 
            animation: coffeeBalance 4s infinite ease-in-out;
        }
        .coffee::after {
            content: ''; position: absolute; top: -5px; left: 0; width: 100%; height: 10px;
            background: #cbd5e1; border-radius: 50%; z-index: -1;
        }
        .cup-handle {
            position: absolute; top: 5px; right: -10px; width: 15px; height: 20px;
            border: 4px solid #f8fafc; border-radius: 8px; z-index: -1;
        }
        .steam { 
            position: absolute; width: 8px; height: 20px; background: rgba(255,255,255,0.7); 
            border-radius: 10px; filter: blur(3px); opacity: 0; 
        }
        .s1 { top: -20px; left: 5px; animation: steamRise 2.5s infinite 0s; }
        .s2 { top: -15px; right: 5px; animation: steamRise 2.5s infinite 1.2s; }

        /* Huruf Zzz */
        .zzz {
            position: absolute; font-weight: 900; color: #fff; opacity: 0;
            text-shadow: 2px 2px 0px rgba(0,0,0,0.2);
        }
        .z1 { top: 20px; right: -10px; font-size: 20px; animation: snoreZ 4s infinite 0s; }
        .z2 { top: 0px; right: -30px; font-size: 26px; animation: snoreZ 4s infinite 0.5s; }
        .z3 { top: -25px; right: -55px; font-size: 34px; animation: snoreZ 4s infinite 1s; }

        /* KEYFRAMES ANIMASI */
        @keyframes breathe {
            0%, 100% { transform: rotate(4deg) translateY(0); }
            50% { transform: rotate(0deg) translateY(-2px); }
        }
        @keyframes nod {
            0%, 100% { transform: rotate(25deg); }
            50% { transform: rotate(10deg); }
        }
        @keyframes armDroop {
            0%, 100% { transform: rotate(-35deg); }
            50% { transform: rotate(-25deg); }
        }
        /* Mengimbangi rotasi lengan agar kopi tetap tegak tidak tumpah */
        @keyframes coffeeBalance {
            0%, 100% { transform: rotate(31deg); }
            50% { transform: rotate(25deg); }
        }
        @keyframes snoreMouth {
            0%, 100% { transform: scale(1); height: 6px; }
            50% { transform: scale(1.2); height: 12px; }
        }
        @keyframes steamRise {
            0% { transform: translateY(0) scale(1); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateY(-30px) scale(1.5); opacity: 0; }
        }
        @keyframes snoreZ {
            0% { transform: translate(0, 0) scale(0.5); opacity: 0; }
            20% { opacity: 1; }
            70% { opacity: 0.8; }
            100% { transform: translate(30px, -40px) scale(1.5); opacity: 0; }
        }

        /* Penyesuaian Responsif untuk Layar Kecil */
        @media (max-width: 900px) {
            .error { left: 5%; top: 25%; max-width: 90%; text-align: center; transform: none; }
            .scene { right: 50%; top: auto; bottom: 5%; transform: translateX(50%) scale(0.8); }
        }
        @media (max-width: 500px) {
            .error__title { font-size: 6em; }
            .error__subtitle { font-size: 1.8em; }
            .scene { transform: translateX(50%) scale(0.65); bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="error">
        <h1 class="error__title">419</h1>
        <div class="error__subtitle">Sesi Habis</div>
        <div class="error__description">Waktu istirahat telah selesai. Halaman ini kadaluarsa karena terlalu lama ditinggal ngopi.</div>
        <button onclick="location.reload()" class="error__button error__button--active">REFRESH (KERJA LAGI)</button>
        <button onclick="history.back()" class="error__button">KEMBALI</button>
    </div>
    <div class="scene">
        <div class="worker">
            <div class="helmet-floor">
                <div class="helmet-brim"></div>
            </div>
            
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
                
                <div class="arm-left"></div>
                
                <!-- Kelompok Kepala dan Zzz dimasukkan ke dalam Torso -->
                <div class="head-group">
                    <div class="neck"></div>
                    <div class="face">
                        <div class="ear"></div>
                        <div class="eye"></div>
                        <div class="mouth"></div>
                    </div>
                    <div class="zzz z1">Z</div>
                    <div class="zzz z2">z</div>
                    <div class="zzz z3">z</div>
                </div>
                
                <!-- Lengan Kanan dimasukkan ke dalam Torso -->
                <div class="arm-right">
                    <div class="hand"></div>
                    <div class="coffee">
                        <div class="cup-handle"></div>
                        <div class="steam s1"></div>
                        <div class="steam s2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>