<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dataphyte Mailserver</title>
    <meta name="description" content="Internal newsletter management platform for Dataphyte.">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --dark:    #0a0f1e;
            --dark-2:  #111827;
            --dark-3:  #1f2937;
            --accent:  #6366f1;
            --accent-2:#818cf8;
            --green:   #10b981;
            --red:     #ef4444;
            --text:    #f9fafb;
            --muted:   #9ca3af;
            --border:  rgba(255,255,255,0.08);
        }

        html, body { height: 100%; background: var(--dark); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif; }

        /* Layout */
        .page { display: grid; grid-template-columns: 1fr 440px; min-height: 100vh; }
        @media (max-width: 900px) { .page { grid-template-columns: 1fr; } .hero { display: none; } }

        /* Hero panel */
        .hero {
            position: relative;
            background: linear-gradient(135deg, #0d1b2a 0%, #1a1040 50%, #0d1b2a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 64px 56px;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 40% at 20% 20%, rgba(99,102,241,0.15) 0%, transparent 70%),
                radial-gradient(ellipse 40% 50% at 80% 80%, rgba(16,185,129,0.10) 0%, transparent 70%);
        }

        /* Grid overlay */
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, transparent, black 20%, black 80%, transparent);
        }

        .hero-content { position: relative; z-index: 1; }

        .wordmark {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 56px;
        }

        .wordmark-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 24px rgba(99,102,241,0.4);
        }

        .wordmark-text { font-size: 15px; font-weight: 600; color: var(--text); letter-spacing: -0.3px; }
        .wordmark-sub  { font-size: 11px; color: var(--muted); letter-spacing: 1.5px; text-transform: uppercase; }

        .hero h1 {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 17px;
            color: var(--muted);
            line-height: 1.65;
            max-width: 420px;
            margin-bottom: 48px;
        }

        /* Feature grid */
        .features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .feature {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            transition: border-color .2s;
        }
        .feature:hover { border-color: rgba(99,102,241,0.3); }

        .feature-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .feature-icon.purple  { background: rgba(99,102,241,0.15); }
        .feature-icon.green   { background: rgba(16,185,129,0.15); }
        .feature-icon.blue    { background: rgba(59,130,246,0.15); }
        .feature-icon.orange  { background: rgba(245,158,11,0.15); }
        .feature-icon.pink    { background: rgba(236,72,153,0.15); }
        .feature-icon.teal    { background: rgba(20,184,166,0.15); }

        .feature h3 { font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .feature p  { font-size: 12px; color: var(--muted); line-height: 1.5; }

        /* Stats strip */
        .stats {
            display: flex;
            gap: 32px;
            margin-top: 48px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
        }
        .stat-val { font-size: 22px; font-weight: 700; color: var(--text); }
        .stat-lbl { font-size: 11px; color: var(--muted); margin-top: 2px; letter-spacing: 0.5px; }

        /* Login panel */
        .login-panel {
            background: var(--dark-2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 64px 48px;
            border-left: 1px solid var(--border);
        }

        .login-header { margin-bottom: 40px; }
        .login-header h2 { font-size: 24px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 6px; }
        .login-header p  { font-size: 14px; color: var(--muted); }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }
        .form-group input {
            width: 100%;
            background: var(--dark-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 15px;
            color: var(--text);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .form-group input::placeholder { color: #4b5563; }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: -0.2px;
            transition: opacity .15s, transform .1s;
            margin-top: 8px;
            box-shadow: 0 4px 20px rgba(99,102,241,0.3);
        }
        .btn-login:hover  { opacity: .9; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        /* Error */
        .login-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 13px;
            color: #fca5a5;
            margin-bottom: 18px;
        }

        .login-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: #4b5563;
            text-align: center;
        }

        /* Animated pulse dot */
        .pulse {
            display: inline-block;
            width: 8px; height: 8px;
            background: var(--green);
            border-radius: 50%;
            margin-right: 6px;
            box-shadow: 0 0 0 0 rgba(16,185,129,0.4);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(16,185,129,0.4); }
            70%  { box-shadow: 0 0 0 6px rgba(16,185,129,0); }
            100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Hero / Features ────────────────────────────────────── --}}
    <section class="hero">
        <div class="hero-content">

            {{-- Wordmark / Site Logo --}}
            <div class="wordmark">
                @if(!empty($siteLogo))
                    {{-- Site logo uploaded via CP → Globals → Newsletter Settings --}}
                    <img src="{{ $siteLogo }}"
                         alt="{{ config('app.name') }}"
                         style="height:40px;max-width:200px;object-fit:contain;display:block;">
                @else
                    {{-- Fallback icon + text wordmark --}}
                    <div class="wordmark-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="wordmark-text">{{ config('app.name') }}</div>
                        <div class="wordmark-sub">Mailserver</div>
                    </div>
                @endif
            </div>

            <h1>Newsletter&nbsp;delivery,<br>owned end&#8209;to&#8209;end.</h1>
            <p>
                An internal platform to manage, schedule and track
                newsletter campaigns across Dataphyte Insight and Foundation —
                with full analytics, subscriber control and GDPR compliance built in.
            </p>

            {{-- Features --}}
            <div class="features">
                <div class="feature">
                    <div class="feature-icon purple">📨</div>
                    <h3>Campaign Engine</h3>
                    <p>Draft, schedule or send immediately to any audience.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon green">📊</div>
                    <h3>Live Analytics</h3>
                    <p>Delivery, open and click rates per campaign in real time.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon blue">👥</div>
                    <h3>Subscriber Groups</h3>
                    <p>Insight &amp; Foundation groups with granular sub-groups.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon orange">🎨</div>
                    <h3>8 Email Templates</h3>
                    <p>Newsroom and programme templates for every use case.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon pink">🔔</div>
                    <h3>Webhook Tracking</h3>
                    <p>Real-time delivery events from Elastic Email.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon teal">🔐</div>
                    <h3>GDPR Compliant</h3>
                    <p>One-click data export and right-to-erasure built in.</p>
                </div>
            </div>

            {{-- Live stats --}}
            @php
                $stats = [
                    'subscribers' => \App\Models\Subscriber::where('status','active')->count(),
                    'campaigns'   => \App\Models\Campaign::whereIn('status',['sent','sending'])->count(),
                    'groups'      => \App\Models\SubscriberGroup::count(),
                ];
            @endphp
            <div class="stats">
                <div>
                    <div class="stat-val">{{ number_format($stats['subscribers']) }}</div>
                    <div class="stat-lbl">Active subscribers</div>
                </div>
                <div>
                    <div class="stat-val">{{ number_format($stats['campaigns']) }}</div>
                    <div class="stat-lbl">Campaigns sent</div>
                </div>
                <div>
                    <div class="stat-val">{{ number_format($stats['groups']) }}</div>
                    <div class="stat-lbl">Subscriber groups</div>
                </div>
            </div>

        </div>
    </section>

    {{-- ── Login Panel ─────────────────────────────────────────── --}}
    <aside class="login-panel">

        <div class="login-header">
            <div style="margin-bottom:20px;">
                <span class="pulse"></span>
                <span style="font-size:12px;color:#6b7280;">Internal platform · {{ config('app.name') }}</span>
            </div>
            <h2>Welcome back</h2>
            <p>Sign in to access the dashboard</p>
        </div>

        @if(session('error') || $errors->has('email'))
        <div class="login-error">
            {{ session('error') ?? $errors->first('email') }}
        </div>
        @endif

        <form method="POST" action="{{ route('statamic.login') }}">
            @csrf

            {{-- After login, go to CP --}}
            <input type="hidden" name="redirect" value="/cp">

            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="you@dataphyte.com"
                    autocomplete="email"
                    autofocus
                    required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="••••••••••••"
                    autocomplete="current-password"
                    required>
            </div>

            <button type="submit" class="btn-login">
                Sign in to Dashboard →
            </button>
        </form>

        <div style="margin-top:18px;text-align:right;">
            <a href="{{ cp_route('password.request') }}"
               style="font-size:13px;color:#6366f1;text-decoration:none;">
                Forgot password?
            </a>
        </div>

        <div class="login-footer">
            <p>Dataphyte internal use only &middot; &copy; {{ date('Y') }}</p>
            <p style="margin-top:6px;">
                <a href="/cp" style="color:#4b5563;text-decoration:none;">Go to CP directly →</a>
            </p>
        </div>

    </aside>

</div>
</body>
</html>
