<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edux API Docs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        :root {
            --bg: #0b1020;
            --panel: #11182d;
            --panel-2: #151f38;
            --text: #eef2ff;
            --muted: #94a3b8;
            --accent: #f97316;
            --accent-2: #38bdf8;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(56,189,248,0.12), transparent 32%),
                radial-gradient(circle at top right, rgba(249,115,22,0.14), transparent 28%),
                linear-gradient(180deg, #0b1020 0%, #0f172a 100%);
            color: var(--text);
        }
        .hero {
            padding: 40px 24px 18px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .hero-card {
            background: linear-gradient(135deg, rgba(17,24,45,0.92), rgba(21,31,56,0.95));
            border: 1px solid rgba(148,163,184,0.16);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.35);
        }
        .eyebrow {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--accent-2);
            margin-bottom: 14px;
        }
        .title {
            font-size: clamp(2rem, 5vw, 4rem);
            line-height: 1;
            margin: 0 0 12px;
            font-weight: 800;
        }
        .subtitle {
            max-width: 860px;
            margin: 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }
        .chip {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(148,163,184,0.14);
            color: var(--text);
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 600;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #fb7185);
            color: white;
            box-shadow: 0 12px 30px rgba(249,115,22,0.25);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(148,163,184,0.2);
            color: var(--text);
        }
        #swagger-ui {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px 48px;
        }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .scheme-container,
        .swagger-ui .opblock,
        .swagger-ui .information-container,
        .swagger-ui .models,
        .swagger-ui .auth-wrapper,
        .swagger-ui .responses-wrapper,
        .swagger-ui .opblock-summary {
            border-radius: 18px !important;
        }
        .swagger-ui .info .title,
        .swagger-ui .scheme-container,
        .swagger-ui .opblock-tag,
        .swagger-ui .opblock-summary-path,
        .swagger-ui .opblock-summary-method,
        .swagger-ui .opblock-summary-description,
        .swagger-ui .parameter__name,
        .swagger-ui .model-title,
        .swagger-ui .renderedMarkdown,
        .swagger-ui .response-col_status,
        .swagger-ui .response-col_description,
        .swagger-ui .response-control-media-type,
        .swagger-ui .download-contents,
        .swagger-ui .btn {
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-card">
            <div class="eyebrow">Swagger / OpenAPI 3.0</div>
            <h1 class="title">Edux Backend API Documentation</h1>
            <p class="subtitle">
                Student-first AI learning backend with authentication, profile completion, quizzes, roadmaps,
                daily challenges, badge tracking, and Sanctum token-based access.
            </p>
            <div class="chips">
                <span class="chip">Flat API responses</span>
                <span class="chip">Bearer auth</span>
                <span class="chip">Laravel 12</span>
                <span class="chip">Student-only platform</span>
            </div>
            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/docs/openapi.json') }}" target="_blank" rel="noreferrer">Open OpenAPI JSON</a>
                <a class="btn btn-secondary" href="{{ url('/') }}" target="_blank" rel="noreferrer">Back to app</a>
            </div>
        </div>
    </section>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = () => {
            SwaggerUIBundle({
                url: "{{ url('/docs/openapi.json') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                displayRequestDuration: true,
                filter: true,
                persistAuthorization: true,
                docExpansion: 'none',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: 'StandaloneLayout'
            });
        };
    </script>
</body>
</html>
