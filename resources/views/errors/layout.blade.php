<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 36px;
                padding: 20px;
            }

            .btn-back {
                margin-top: 20px;
                cursor: pointer;
                padding: 10px 24px;
                background: transparent;
                border: 1.5px solid #636b6f;
                border-radius: 8px;
                color: #636b6f;
                font-weight: 600;
                text-decoration: none;
                display: inline-block;
                transition: all 0.2s;
                font-family: inherit;
            }
            .btn-back:hover {
                background-color: #f3f4f6;
                color: #1f2937;
                border-color: #1f2937;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title">
                    @yield('message')
                </div>
                <div>
                    <button onclick="history.back()" class="btn-back">
                        KEMBALI
                    </button>
                </div>
            </div>
        </div>
    </body>
</html>
