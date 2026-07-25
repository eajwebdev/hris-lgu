<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 Page Expired | Municipality of Mabinay HRIS</title>
    
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, rgba(13, 148, 136, 0.25) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 23, 42, 0.8) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(20, 184, 166, 0.15) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #f8fafc;
        }

        .error-card:nth-of-type(n+2) {
            display: none !important;
        }

        .error-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            max-width: 580px;
            width: 100%;
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 40px rgba(13, 148, 136, 0.15);
            animation: fadeInScale 0.4s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .lgu-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(13, 148, 136, 0.15);
            border: 1px solid rgba(45, 212, 191, 0.3);
            color: #2dd4bf;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 0.825rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        .error-code-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .error-code {
            font-size: 6.5rem;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 50%, #b45309 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.04em;
            text-shadow: 0 10px 30px rgba(217, 119, 6, 0.2);
        }

        .error-icon {
            font-size: 2.75rem;
            color: #fbbf24;
            margin-bottom: 1.25rem;
            display: inline-block;
        }

        .error-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .error-description {
            color: #94a3b8;
            font-size: 0.975rem;
            line-height: 1.6;
            margin-bottom: 2.25rem;
        }

        .action-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.925rem;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.5);
            color: #ffffff;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.07);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            transform: translateY(-2px);
        }

        .footer-note {
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
            color: #64748b;
        }

        @media (max-width: 480px) {
            .error-card {
                padding: 2rem 1.5rem;
            }
            .error-code {
                font-size: 5rem;
            }
            .error-title {
                font-size: 1.4rem;
            }
            .action-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="lgu-badge">
            <i class="fas fa-landmark"></i> Municipality of Mabinay
        </div>

        <div class="error-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>

        <div class="error-code-container">
            <div class="error-code">419</div>
        </div>

        <h1 class="error-title">Session Expired</h1>

        <p class="error-description">
            Your security token or active session has expired due to inactivity. Please refresh the page or log in again to continue.
        </p>

        <div class="action-group">
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="fas fa-rotate-right"></i> Refresh &amp; Try Again
            </button>
            <a href="{{ url('/') }}" class="btn btn-secondary">
                <i class="fas fa-house"></i> Return to Home
            </a>
        </div>

        <div class="footer-note">
            HRIS &bull; Human Resource Information System &bull; Municipality of Mabinay &copy; {{ date('Y') }}
        </div>
    </div>

</body>
</html>