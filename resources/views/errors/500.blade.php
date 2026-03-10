<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        html, body { 
            height: 100%; width: 100%; margin: 0; overflow: hidden; 
            background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 100%); 
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

        /* Puing-puing Jatuh */
        .debris-container {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 7;
        }
        .debris { 
            position: absolute; background: #27272a; 
            border-radius: 4px; opacity: 0;
        }
        .db-1 { left: 40px; width: 40px; height: 20px; animation: debrisFall 1.5s infinite linear 0s; }
        .db-2 { right: 80px; width: 30px; height: 30px; background: #3f3f46; animation: debrisFall 2s infinite linear 0.5s; border-radius: 5px; }
        .db-3 { left: 160px; width: 10px; height: 60px; background: #fb7185; animation: debrisFall 1.8s infinite linear 0.8s; } /* Kabel */
        .db-4 { right: 30px; width: 50px; height: 15px; background: #52525b; animation: debrisFall 1.2s infinite linear 1.1s; }
        .db-5 { left: 100px; width: 25px; height: 25px; background: #fbbf24; animation: debrisFall 1.6s infinite linear 1.4s; border-radius: 50%; }

        /* Percikan Api (Sparks) */
        .spark {
            position: absolute; width: 4px; height: 15px; background: #fef08a;
            border-radius: 2px; opacity: 0; box-shadow: 0 0 10px #fde047;
        }
        .sp-1 { top: 150px; left: 120px; animation: sparkFlash 0.5s infinite alternate 0s; transform: rotate(45deg); }
        .sp-2 { top: 180px; left: 250px; animation: sparkFlash 0.3s infinite alternate 0.2s; transform: rotate(-30deg); }

        /* Pekerja Jongkok Ketakutan */
        .worker { 
            position: absolute; bottom: 30px; right: 100px; width: 200px; height: 200px; 
            animation: cowerShake 0.1s infinite alternate; 
        }

        /* Kaki Jongkok (Squat) */
        .legs { position: absolute; bottom: 0; left: 0; width: 100%; height: 80px; }
        .leg { position: absolute; width: 45px; height: 70px; background: #1e3a8a; border-radius: 20px; z-index: 1; }
        .leg-left { bottom: 15px; left: 40px; transform: rotate(60deg); }
        .leg-right { bottom: 15px; right: 40px; transform: rotate(-60deg); }
        
        .boot { position: absolute; bottom: 0; width: 45px; height: 30px; background: #3f2c22; z-index: 2; border-bottom: 5px solid #1c1917;}
        .boot-left { left: 20px; border-radius: 20px 10px 10px 10px; }
        .boot-right { right: 20px; border-radius: 10px 20px 10px 10px; }

        /* Badan (Torso) Merunduk */
        .torso { 
            position: absolute; bottom: 45px; left: 50px; width: 100px; height: 100px; 
            background: #f97316; border-radius: 40px 40px 20px 20px; z-index: 3; 
        }
        .vest-stripe { position: absolute; background: #cbd5e1; height: 100%; width: 20px; top: 0; }
        .vs-1 { left: 15px; } .vs-2 { right: 15px; } 
        .vest-h { position: absolute; bottom: 25px; height: 20px; width: 100%; background: #cbd5e1; }

        /* Kepala Tertunduk */
        .head-group { 
            position: absolute; top: -35px; left: 15px; width: 70px; height: 80px; 
            transform-origin: bottom center; z-index: 2; 
            transform: translateY(10px); /* Ditarik ke bawah/merunduk */
        }
        .neck { 
            position: absolute; bottom: -5px; left: 25px; width: 20px; height: 25px; 
            background: #e5c38b; border-radius: 10px; 
        }
        .face { 
            position: absolute; top: 0; left: 0; width: 70px; height: 75px; 
            background: #f1dca7; border-radius: 35px 35px 25px 25px; 
        }
        
        /* Ekspresi Panik */
        .eye { 
            position: absolute; top: 35px; width: 16px; height: 6px; 
            border-top: 3px solid #78350f; border-radius: 50%;
        }
        .eye-left { left: 12px; transform: rotate(15deg); }
        .eye-right { right: 12px; transform: rotate(-15deg); }
        .mouth {
            position: absolute; top: 52px; left: 28px; width: 14px; height: 10px;
            background: #450a0a; border-radius: 5px 5px 15px 15px;
        }
        .band-aid {
            position: absolute; top: 20px; left: 10px; width: 20px; height: 8px;
            background: #f87171; border-radius: 4px; transform: rotate(45deg); opacity: 0.8;
        }

        /* Helm */
        .helmet { 
            position: absolute; top: -15px; left: -10px; width: 90px; height: 45px; 
            background: #fbbf24; border-radius: 45px 45px 10px 10px; 
            border-bottom: 6px solid #d97706; z-index: 5;
            animation: helmetDangle 0.5s infinite alternate;
        }

        /* Lengan Bersiku Melindungi Kepala */
        .arm { 
            position: absolute; width: 28px; height: 70px; 
            background: #1e293b; border-radius: 14px; 
            transform-origin: 14px 14px; z-index: 6; 
        }
        .forearm {
            position: absolute; bottom: 0; left: 0; width: 28px; height: 75px; 
            background: #0f172a; border-radius: 14px;
            transform-origin: 14px 61px; /* Sumbu putar di siku */
        }
        .hand {
            position: absolute; top: -5px; left: 3px; width: 22px; height: 25px;
            background: #f1dca7; border-radius: 50%;
        }

        /* Lengan Kiri memutar ke atas-kanan lalu menekuk balik ke kepala */
        .arm-left { top: 15px; left: -10px; transform: rotate(-140deg); }
        .arm-left .forearm { transform: rotate(115deg); }

        /* Lengan Kanan memutar ke atas-kiri lalu menekuk balik ke kepala */
        .arm-right { top: 15px; right: -10px; transform: rotate(140deg); }
        .arm-right .forearm { transform: rotate(-115deg); }

        /* Debu / Asap */
        .dust {
            position: absolute; bottom: 0; background: #a1a1aa; border-radius: 50%;
            opacity: 0; filter: blur(5px);
        }
        .du-1 { width: 80px; height: 40px; left: -20px; animation: dustPoof 2s infinite 0s; }
        .du-2 { width: 60px; height: 30px; right: -10px; animation: dustPoof 2s infinite 1s; }

        /* KEYFRAMES ANIMASI */
        @keyframes debrisFall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            80% { opacity: 1; }
            100% { transform: translateY(600px) rotate(360deg); opacity: 0; }
        }
        @keyframes sparkFlash {
            0% { opacity: 0; transform: scale(1) translateY(0); }
            50% { opacity: 1; transform: scale(1.5) translateY(5px); }
            100% { opacity: 0; transform: scale(1) translateY(10px); }
        }
        @keyframes cowerShake {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-2px, 2px); }
        }
        @keyframes helmetDangle {
            0% { transform: rotate(-5deg) translate(0, 0); }
            100% { transform: rotate(5deg) translate(-1px, -1px); }
        }
        @keyframes dustPoof {
            0% { transform: scale(0.5) translateY(10px); opacity: 0; }
            50% { opacity: 0.5; }
            100% { transform: scale(2) translateY(-20px); opacity: 0; }
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
        <h1 class="error__title">500</h1>
        <div class="error__subtitle">Kecelakaan Server</div>
        <div class="error__description">Oops! Terjadi keruntuhan struktur pada server kami. Tim teknisi sedang berlindung dan berusaha mengevakuasi data.</div>
        <a href="/" class="error__button error__button--active">REFRESH HALAMAN</a>
        <button onclick="history.back()" class="error__button">KEMBALI</button>
        <a href="/" class="error__button">LAPORKAN INSIDEN</a>
    </div>

    <div class="scene">
        
        <!-- Puing-puing Jatuh dan Percikan -->
        <div class="debris-container">
            <div class="debris db-1"></div>
            <div class="debris db-2"></div>
            <div class="debris db-3"></div>
            <div class="debris db-4"></div>
            <div class="debris db-5"></div>
            
            <div class="spark sp-1"></div>
            <div class="spark sp-2"></div>
        </div>

        <div class="worker">
            <!-- Debu di lantai -->
            <div class="dust du-1"></div>
            <div class="dust du-2"></div>

            <div class="legs">
                <div class="leg leg-left"></div>
                <div class="leg leg-right"></div>
                <div class="boot boot-left"></div>
                <div class="boot boot-right"></div>
            </div>
            
            <div class="torso">
                <div class="vest-stripe vs-1"></div>
                <div class="vest-stripe vs-2"></div>
                <div class="vest-h"></div>
                
                <div class="head-group">
                    <div class="neck"></div>
                    <div class="face">
                        <div class="band-aid"></div>
                        <div class="eye eye-left"></div>
                        <div class="eye eye-right"></div>
                        <div class="mouth"></div>
                    </div>
                    <div class="helmet"></div>
                </div>
                
                <!-- Lengan Kiri (Bahu -> Siku -> Kepala) -->
                <div class="arm arm-left">
                    <div class="forearm">
                        <div class="hand"></div>
                    </div>
                </div>

                <!-- Lengan Kanan (Bahu -> Siku -> Kepala) -->
                <div class="arm arm-right">
                    <div class="forearm">
                        <div class="hand"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>