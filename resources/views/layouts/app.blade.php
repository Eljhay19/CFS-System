<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CFS')</title>
    <style>
        * { box-sizing: border-box; }
        :root {
            --navy: #07183a;
            --navy-2: #12345a;
            --blue: #244f84;
            --orange: #df740d;
            --orange-2: #f08b22;
            --teal: #17b6a0;
            --gold: #f5b942;
            --ink: #07183a;
            --line: #d8e1ee;
            --muted: #536f9a;
            --bg: #f7f4ef;
            --font-sans: "Aptos", "Segoe UI Variable", "Segoe UI", Arial, sans-serif;
            --font-display: "Aptos Display", "Segoe UI Variable Display", "Segoe UI", Arial, sans-serif;
            --cafe-photo: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1800&q=88');
        }
        html, body {
            overflow-x: hidden;
        }
        body {
            margin: 0;
            font-family: var(--font-sans);
            background:
                radial-gradient(circle at 0% 48%, rgba(119, 148, 94, .28), transparent 14rem),
                radial-gradient(circle at 100% 50%, rgba(222, 116, 13, .16), transparent 16rem),
                linear-gradient(180deg, #fff 0%, #f8f5ee 58%, #fff 100%);
            color: var(--ink);
            min-height: 100vh;
            text-rendering: optimizeLegibility;
        }
        a { color: inherit; text-decoration: none; }
        .topbar {
            background: rgba(255, 255, 255, .78);
            backdrop-filter: blur(18px);
            color: var(--ink);
            padding: 8px max(22px, calc((100vw - 1500px) / 2));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px solid rgba(216, 225, 238, .55);
            box-shadow: 0 10px 36px rgba(7, 24, 58, .06);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .brand {
            align-items: center;
            display: flex;
            font-size: clamp(26px, 2.4vw, 36px);
            font-weight: 800;
            gap: 7px;
            line-height: 1;
            letter-spacing: 0;
        }
        .brand-emblem {
            display: block;
            height: 42px;
            width: 80px;
            flex: 0 0 auto;
        }
        .footer .brand-emblem {
            height: 40px;
            width: 76px;
        }
        .footer .brand {
            font-size: 32px;
        }
        .nav {
            display: flex;
            align-items: center;
            gap: clamp(10px, 1.6vw, 22px);
            flex-wrap: wrap;
            margin-left: auto;
        }
        .nav a, .nav button {
            color: var(--ink);
            background: transparent;
            border: 0;
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            align-items: center;
            display: inline-flex;
            gap: 7px;
            min-height: 30px;
            padding: 3px 0;
            position: relative;
            transition: color .2s ease;
            white-space: nowrap;
        }
        .nav a:hover, .nav button:hover,
        .nav a.active {
            color: #000;
        }
        .nav a::after {
            background: var(--orange);
            border-radius: 999px;
            bottom: 0;
            content: "";
            height: 3px;
            left: 0;
            position: absolute;
            right: 0;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .2s ease;
        }
        .nav a.active::after,
        .nav a:hover::after {
            transform: scaleX(1);
        }
        .nav-icon {
            height: 20px;
            width: 20px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }
        .topbar-tools {
            align-items: center;
            display: flex;
            gap: 12px;
            margin-left: 14px;
        }
        .top-search {
            align-items: center;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            display: grid;
            grid-template-columns: 17px 1fr;
            gap: 8px;
            height: 32px;
            margin: 0;
            overflow: hidden;
            padding: 0 10px;
            width: 142px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .top-search:hover,
        .top-search:focus-within {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .1);
        }
        .top-search svg,
        .top-icon svg,
        .profile-caret {
            color: var(--ink);
            height: 17px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 17px;
            fill: none;
        }
        .top-search input {
            -webkit-appearance: none;
            appearance: none;
            background: #fff;
            border: 0;
            border-radius: 0;
            font-size: 12px;
            height: 30px;
            min-width: 0;
            padding: 0;
        }
        .top-search input:focus { outline: 0; }
        .top-search input::-webkit-search-cancel-button,
        .top-search input::-webkit-search-decoration {
            -webkit-appearance: none;
        }
        .top-search input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #fff inset;
            box-shadow: 0 0 0 30px #fff inset;
        }
        .top-icon {
            align-items: center;
            background: transparent;
            border: 0;
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            position: relative;
            width: 32px;
        }
        .top-icon:hover {
            color: #000;
        }
        .top-dot {
            background: var(--orange);
            border: 2px solid #fff;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 9px;
            font-weight: 800;
            height: 16px;
            justify-content: center;
            line-height: 1;
            min-width: 16px;
            padding: 0 4px;
            position: absolute;
            right: 1px;
            top: 0;
            align-items: center;
        }
        .top-dot.is-hidden {
            display: none;
        }
        .notification-menu {
            position: relative;
        }
        .notification-popover {
            background: rgba(255, 255, 255, .98);
            border: 1px solid rgba(216, 225, 238, .96);
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(7, 24, 58, .16);
            padding: 12px 12px 10px;
            position: absolute;
            right: -10px;
            top: calc(100% + 8px);
            width: 304px;
            z-index: 40;
        }
        .notification-popover[hidden] {
            display: none;
        }
        .notification-popover::before {
            background: #fff;
            border-left: 1px solid rgba(216, 225, 238, .9);
            border-top: 1px solid rgba(216, 225, 238, .9);
            content: "";
            height: 10px;
            position: absolute;
            right: 21px;
            top: -6px;
            transform: rotate(45deg);
            width: 10px;
        }
        .notification-popover-head {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 9px;
        }
        .notification-popover-title {
            align-items: center;
            display: inline-flex;
            font-family: var(--font-display);
            font-size: 14px;
            font-weight: 720;
            gap: 7px;
        }
        .notification-count-pill {
            align-items: center;
            background: #ef2f2f;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 10px;
            height: 20px;
            justify-content: center;
            min-width: 20px;
            padding: 0 6px;
        }
        .notification-mark-read {
            background: transparent;
            border: 0;
            color: #045eb7;
            cursor: pointer;
            font: inherit;
            font-size: 10.5px;
            font-weight: 620;
            padding: 0;
        }
        .notification-mark-read:hover {
            color: #000;
        }
        .notification-popover .notification-list {
            display: grid;
            gap: 0;
            max-height: 292px;
            overflow-y: auto;
            padding-right: 2px;
        }
        .notification-popup-row {
            align-items: flex-start;
            background: transparent;
            border: 0;
            border-bottom: 1px solid #e7edf5;
            border-radius: 0;
            display: grid;
            gap: 7px;
            grid-template-columns: 18px minmax(0, 1fr) 46px;
            min-height: 54px;
            padding: 8px 2px;
            transition: background .2s ease;
        }
        .notification-popup-row.unread {
            background: transparent;
        }
        .notification-popup-row:hover {
            background: #eaf2fc;
        }
        .notification-popup-row:focus-visible {
            background: #eaf2fc;
            outline: 2px solid #045eb7;
            outline-offset: -2px;
        }
        .notification-popup-row:last-child {
            border-bottom: 0;
        }
        .notification-popup-icon {
            color: #045eb7;
            height: 16px;
            margin-top: 3px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
            width: 16px;
            fill: none;
        }
        .notification-popup-icon.warning {
            color: #ef2f2f;
        }
        .notification-popup-icon.success {
            color: #0f9f6e;
        }
        .notification-popup-copy strong {
            display: block;
            font-size: 10.5px;
            font-weight: 620;
            line-height: 1.25;
            margin-bottom: 3px;
        }
        .notification-popup-copy p {
            color: var(--muted);
            font-size: 9.8px;
            line-height: 1.3;
            margin: 0;
        }
        .notification-popup-time {
            color: #536f9a;
            font-size: 9.5px;
            text-align: right;
            white-space: nowrap;
        }
        .notification-unread-dot {
            background: var(--orange);
            border-radius: 50%;
            height: 5px;
            left: 13px;
            position: absolute;
            top: 7px;
            width: 5px;
        }
        .notification-icon-wrap {
            position: relative;
        }
        .notification-empty,
        .notification-loading {
            color: var(--muted);
            font-size: 12px;
            padding: 14px 4px;
            text-align: center;
        }
        .notification-footer-link {
            align-items: center;
            color: #045eb7;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            justify-content: center;
            padding-top: 10px;
            width: 100%;
        }
        .notification-footer-link:hover {
            color: #000;
        }
        .top-profile {
            align-items: center;
            display: flex;
            gap: 9px;
            text-decoration: none;
            white-space: nowrap;
        }
        .top-profile:hover .profile-name,
        .top-profile:hover .profile-caret {
            color: #000;
        }
        .profile-avatar {
            align-items: center;
            background: var(--navy);
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            height: 32px;
            justify-content: center;
            width: 32px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .profile-avatar img { background: #fff; height: 100%; width: 100%; object-fit: contain; }
        .profile-name {
            font-size: 13px;
            font-weight: 700;
        }
        .container {
            width: min(1500px, calc(100% - 28px));
            margin: 22px auto 12px;
        }
        body.home-screen,
        body.login-screen,
        body.feedback-screen,
        body.admin-screen {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            isolation: isolate;
            position: relative;
        }
        body.admin-screen .container {
            flex: 1;
            margin: 16px auto 12px;
        }
        body.login-screen {
            height: 100vh;
            max-height: 100vh;
            overflow: hidden !important;
        }
        body.home-screen::before,
        body.login-screen::before,
        body.feedback-screen::before {
            background: var(--cafe-photo);
            background-position: center;
            background-size: cover;
            content: "";
            filter: blur(15px) brightness(1.14) saturate(.96);
            inset: -34px;
            opacity: .72;
            position: fixed;
            transform: scale(1.06);
            z-index: -2;
        }
        body.home-screen::after,
        body.login-screen::after,
        body.feedback-screen::after {
            background:
                radial-gradient(circle at 7% 48%, rgba(122, 147, 86, .18), transparent 13rem),
                radial-gradient(circle at 93% 54%, rgba(223, 116, 13, .1), transparent 14rem),
                linear-gradient(180deg, rgba(255,255,255,.66), rgba(255,255,255,.48) 50%, rgba(255,255,255,.68));
            content: "";
            inset: 0;
            position: fixed;
            z-index: -1;
        }
        body.home-screen .topbar,
        body.login-screen .topbar,
        body.feedback-screen .topbar {
            padding-bottom: 7px;
            padding-top: 7px;
        }
        body.home-screen .container,
        body.login-screen .container,
        body.feedback-screen .container {
            align-items: center;
            display: flex;
            flex: 1;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 6px;
            margin-top: 0;
            padding: clamp(24px, 4vh, 44px) 0;
            width: min(1280px, calc(100% - 32px));
        }
        body.login-screen .container {
            min-height: calc(100vh - 146px);
            max-height: calc(100vh - 146px);
            overflow: hidden;
            padding: 0;
        }
        body.feedback-screen .container {
            justify-content: center;
            margin-top: 0;
            padding: clamp(18px, 3vh, 34px) 0;
            width: min(1460px, calc(100% - 34px));
        }
        body.home-screen .container > .alert {
            width: min(1180px, 100%);
        }
        body.home-screen .footer,
        body.login-screen .footer,
        body.feedback-screen .footer,
        body.home-screen .container {
            position: relative;
            z-index: 1;
        }
        body.login-screen .container {
            position: relative;
            z-index: 1;
        }
        .panel {
            background: rgba(255, 255, 255, .92);
            border: 1px solid rgba(216, 225, 238, .9);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(12, 45, 79, .08);
        }
        .app-shell {
            display: grid;
            grid-template-columns: 230px 1fr;
            gap: 20px;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--navy), #0a2039);
            border-radius: 8px;
            color: #fff;
            padding: 20px;
            min-height: 620px;
        }
        .sidebar .brand {
            color: #fff;
            font-size: 30px;
            margin-bottom: 18px;
        }
        .sidebar .brand-emblem {
            height: 34px;
            width: 64px;
        }
        .logout-form {
            margin-top: 10px;
        }
        .sidebar a, .sidebar button {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            display: flex;
            font: inherit;
            gap: 10px;
            padding: 11px 12px;
            width: 100%;
        }
        .sidebar a.active,
        .sidebar a:hover,
        .sidebar button:hover { background: rgba(255, 255, 255, .12); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }
        input, textarea, select {
            width: 100%;
            border: 1px solid #101827;
            border-radius: 6px;
            padding: 11px 12px;
            font: inherit;
            background: #fff;
        }
        input[type="checkbox"], input[type="radio"] { width: auto; }
        textarea { min-height: 110px; resize: vertical; }
        .field { margin-bottom: 18px; }
        .button {
            background: linear-gradient(135deg, var(--orange-2), var(--orange));
            color: #fff;
            border: 0;
            border-radius: 12px;
            padding: 14px 28px;
            cursor: pointer;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 14px 28px rgba(223, 116, 13, .25);
            transition: background .22s ease, box-shadow .22s ease;
        }
        .button:hover {
            background: #000;
            box-shadow: 0 12px 24px rgba(0, 0, 0, .22);
        }
        .button.secondary { background: #374151; }
        .button.danger { background: #dc2626; }
        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 20px 0 28px;
        }
        .stat {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 18px;
            background: #fafafa;
        }
        .stat strong {
            display: block;
            margin-top: 8px;
            font-size: 30px;
        }
        .feedback-item {
            border-top: 1px solid #e5e7eb;
            padding: 16px 0;
        }
        .badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 10px;
        }
        .badge.info { background: #dbeafe; color: #1d4ed8; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        .badge.danger { background: #fee2e2; color: #991b1b; }
        .badge.success { background: #dcfce7; color: #166534; }
        .notification-list {
            display: grid;
            gap: 10px;
        }
        .notification-item {
            background: #f8fbfd;
            border: 1px solid var(--line);
            border-left: 5px solid var(--blue);
            border-radius: 8px;
            padding: 13px 14px;
        }
        .notification-item.warning { border-left-color: #f59e0b; }
        .admin-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
            gap: 20px;
        }
        .admin-workspace {
            display: grid;
            grid-template-columns: 214px minmax(0, 1fr);
            margin: 0 auto;
            min-height: auto;
            width: min(1440px, 100%);
        }
        .admin-sidebar {
            background:
                radial-gradient(circle at 20% 18%, rgba(255,255,255,.11), transparent 5rem),
                linear-gradient(165deg, #06152f 0%, #09213d 54%, #06152f 100%);
            border-radius: 8px 0 0 8px;
            color: #fff;
            padding: 26px 14px;
        }
        .admin-side-brand {
            align-items: center;
            color: #fff;
            display: flex;
            font-size: 25px;
            font-weight: 800;
            gap: 8px;
            line-height: 1;
            margin: 0 0 28px;
        }
        .admin-side-brand .brand-emblem {
            height: 34px;
            width: 64px;
        }
        .admin-side-nav {
            display: grid;
            gap: 8px;
        }
        .admin-side-nav a,
        .admin-side-nav button {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: rgba(255,255,255,.93);
            cursor: pointer;
            display: flex;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            gap: 11px;
            height: 44px;
            padding: 0 14px;
            text-align: left;
            transition: background .2s ease, color .2s ease;
            width: 100%;
        }
        .admin-side-nav a.active,
        .admin-side-nav a:hover,
        .admin-side-nav button:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
        }
        .admin-side-icon {
            color: currentColor;
            fill: none;
            flex: 0 0 auto;
            height: 20px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 20px;
        }
        .admin-main {
            background: rgba(255,255,255,.96);
            border: 1px solid rgba(216, 225, 238, .96);
            border-left: 0;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 14px 38px rgba(7, 24, 58, .09);
            min-width: 0;
            padding: 18px;
        }
        .dashboard-panel {
            display: grid;
            gap: 12px;
        }
        .dashboard-top {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }
        .dashboard-title h1 {
            font-family: var(--font-display);
            font-size: clamp(24px, 2vw, 30px);
            line-height: 1.08;
            margin: 0 0 4px;
        }
        .dashboard-title p {
            color: var(--muted);
            font-size: 13px;
            margin: 0;
        }
        .dashboard-tools {
            align-items: center;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .date-filter {
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: #365886;
            display: inline-flex;
            font-size: 12px;
            gap: 8px;
            height: 32px;
            padding: 0 12px;
            white-space: nowrap;
        }
        .date-filter svg,
        .dashboard-icon,
        .quick-card svg,
        .panel-title svg {
            color: var(--ink);
            fill: none;
            flex: 0 0 auto;
            height: 20px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 20px;
        }
        .dashboard-export {
            border: 1px solid #101827;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 32px;
            padding: 0 15px;
        }
        .dashboard-export:hover {
            background: #000;
            border-color: #000;
            box-shadow: none;
            color: #fff;
        }
        .dashboard-card .stat-feedback { color: #045eb7; }
        .dashboard-card .stat-rating { color: #f59e0b; }
        .dashboard-card .stat-today { color: #078b5a; }
        .dashboard-card .stat-unread { color: #dc2626; }
        .dashboard-card-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .dashboard-card,
        .quick-card {
            background: #fff;
            border: 1px solid rgba(216, 225, 238, .76);
            border-radius: 8px;
            box-shadow: none;
        }
        .dashboard-card {
            align-items: center;
            display: grid;
            gap: 10px;
            grid-template-columns: 30px 1fr;
            min-height: 82px;
            padding: 13px 15px;
            text-decoration: none;
            transition: background .2s ease, border-color .2s ease, box-shadow .2s ease, color .2s ease;
        }
        .dashboard-card-link {
            color: inherit;
            cursor: pointer;
        }
        .dashboard-card-link:hover,
        .dashboard-card-link:focus-visible,
        .dashboard-card-link.active {
            background: #f4f6f8;
            border-color: #c8d3e1;
            box-shadow: 0 0 0 2px rgba(7, 24, 58, .035);
            color: var(--ink);
            outline: 0;
        }
        .dashboard-card span {
            color: #365886;
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .dashboard-card strong {
            display: inline-flex;
            font-size: 25px;
            gap: 6px;
            line-height: 1;
        }
        .dashboard-change {
            align-items: center;
            color: #078b5a;
            display: flex;
            font-size: 11px;
            gap: 6px;
            margin-top: 6px;
        }
        .dashboard-change.warn {
            color: #dc2626;
        }
        .dashboard-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1.52fr) minmax(310px, .8fr);
        }
        .dashboard-bottom-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
        }
        .dashboard-box {
            background: transparent;
            border: 0;
            border-radius: 0;
            border-top: 1px solid rgba(216, 225, 238, .82);
            box-shadow: none;
            padding: 13px 0 0;
        }
        .metric-detail {
            border-top: 1px solid rgba(216, 225, 238, .82);
            display: grid;
            gap: 12px;
            padding-top: 14px;
        }
        .metric-detail-head {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }
        .metric-detail-head h2 {
            font-family: var(--font-display);
            font-size: 19px;
            line-height: 1.1;
            margin: 0 0 4px;
        }
        .metric-detail-head p {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
        }
        .metric-summary-strip {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .metric-chip {
            background: #fff;
            border: 1px solid rgba(216, 225, 238, .82);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .metric-chip > span {
            color: var(--muted);
            display: block;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .metric-chip strong {
            display: block;
            font-size: 22px;
            line-height: 1;
        }
        .metric-card-section {
            background: transparent;
            border-top: 1px solid rgba(216, 225, 238, .76);
            padding-top: 12px;
        }
        .metric-two-col {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(280px, .95fr) 1px minmax(320px, 1fr);
        }
        .metric-list {
            display: grid;
            gap: 8px;
        }
        .metric-list-row {
            align-items: center;
            border-bottom: 1px solid #e7edf5;
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr auto;
            padding: 9px 0;
        }
        .metric-list-row:last-child {
            border-bottom: 0;
        }
        .metric-list-row strong {
            display: block;
            font-size: 13px;
            margin-bottom: 3px;
        }
        .metric-list-row p,
        .metric-list-row small {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
        }
        .metric-empty {
            color: var(--muted);
            font-size: 12px;
            padding: 14px 0;
        }
        .metric-status {
            border: 0;
            color: #045eb7;
            display: inline-flex;
            font-size: 11px;
            font-weight: 700;
            min-width: 0;
            justify-content: center;
            padding: 0;
            white-space: nowrap;
        }
        .metric-status.status-resolved { color: #0f9f6e; }
        .metric-status.status-needs-action { color: var(--orange); }
        .metric-status.status-in-progress { color: #536f9a; }
        .metric-status.status-submitted { color: #045eb7; }
        .metric-status.status-new { color: #045eb7; }
        .panel-title {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .panel-title > span,
        .panel-title h2 {
            align-items: center;
            display: inline-flex;
            font-family: var(--font-display);
            font-size: 17px;
            gap: 10px;
            line-height: 1.1;
            margin: 0;
        }
        .panel-link {
            color: #045eb7;
            font-size: 12px;
            font-weight: 700;
        }
        .rating-layout {
            display: grid;
            gap: 15px;
            grid-template-columns: minmax(260px, .95fr) 1px minmax(300px, 1fr);
        }
        .rating-divider {
            background: var(--line);
            min-height: 150px;
        }
        .rating-summary {
            align-items: center;
            display: flex;
            gap: 14px;
            margin-bottom: 10px;
        }
        .rating-summary strong {
            font-size: 25px;
        }
        .rating-summary .stars {
            font-size: 18px;
            letter-spacing: 0;
        }
        .rating-summary p {
            color: var(--muted);
            font-size: 12px;
            margin: 2px 0 0;
        }
        .rating-bars {
            display: grid;
            gap: 8px;
        }
        .rating-row-line {
            align-items: center;
            display: grid;
            gap: 10px;
            grid-template-columns: 40px minmax(0, 1fr) 58px;
            font-size: 12px;
        }
        .rating-track {
            background: #e9eef5;
            border-radius: 99px;
            height: 14px;
            overflow: hidden;
        }
        .rating-fill {
            background: linear-gradient(90deg, #045eb7, #0874d1);
            height: 100%;
        }
        .trend-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .trend-head strong {
            font-size: 14px;
        }
        .trend-select {
            border: 1px solid var(--line);
            border-radius: 6px;
            color: #365886;
            font-size: 12px;
            height: 32px;
            padding: 0 8px;
            width: auto;
        }
        .mini-chart {
            height: 144px;
            width: 100%;
        }
        .mini-chart text {
            fill: #6b7fa3;
            font-size: 10px;
        }
        .notification-feed {
            display: grid;
            gap: 0;
        }
        .notification-row {
            border-bottom: 1px solid #e7edf5;
            display: grid;
            gap: 10px;
            grid-template-columns: 13px 1fr auto;
            padding: 10px 0;
        }
        .notification-row:last-child {
            border-bottom: 0;
        }
        .notification-dot {
            background: #a9b7ca;
            border-radius: 50%;
            height: 9px;
            margin-top: 4px;
            width: 9px;
        }
        .notification-row.warning .notification-dot,
        .notification-row.info .notification-dot {
            background: var(--orange);
        }
        .notification-row strong {
            display: block;
            font-size: 13px;
            margin-bottom: 3px;
        }
        .notification-row p,
        .notification-time {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
        }
        .recent-table th,
        .recent-table td {
            font-size: 12px;
            padding: 9px 12px;
            vertical-align: middle;
        }
        .recent-table th {
            background: #f4f7fb;
            color: var(--ink);
            text-transform: none;
        }
        .recent-table .stars {
            letter-spacing: 1px;
            white-space: nowrap;
        }
        .row-action {
            color: var(--ink);
            font-size: 18px;
            font-weight: 800;
        }
        .quick-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .quick-card {
            align-items: center;
            display: grid;
            gap: 10px;
            grid-template-columns: 32px 1fr 16px;
            min-height: 54px;
            padding: 9px 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .quick-card:hover {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223,116,13,.1);
        }
        .dashboard-export .dashboard-icon {
            color: #fff;
            height: 16px;
            width: 16px;
        }
        .quick-card strong {
            display: block;
            font-size: 12px;
            margin-bottom: 3px;
        }
        .quick-card p {
            color: var(--muted);
            font-size: 11px;
            margin: 0;
        }
        .quick-card.email-settings {
            border-color: var(--line);
        }
        .quick-email-input {
            border: 0;
            color: var(--muted);
            font-size: 11px;
            height: 18px;
            min-height: 18px;
            padding: 0;
        }
        .quick-email-input:focus {
            outline: 0;
        }
        .quick-save {
            background: transparent;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--ink);
            cursor: pointer;
            font-size: 11px;
            font-weight: 800;
            height: 32px;
            padding: 0 10px;
            transition: background .2s ease, border-color .2s ease, color .2s ease;
        }
        .quick-save:hover {
            background: #000;
            border-color: #000;
            color: #fff;
        }
        .questions-panel {
            background: rgba(255,255,255,.97);
            border: 1px solid rgba(216, 225, 238, .96);
            border-radius: 10px;
            box-shadow: 0 14px 38px rgba(7, 24, 58, .08);
            display: grid;
            gap: 20px;
            padding: 28px 32px 22px;
        }
        .questions-head {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
        }
        .questions-head h1 {
            font-family: var(--font-display);
            font-size: clamp(27px, 2.2vw, 34px);
            line-height: 1.08;
            margin: 6px 0 6px;
        }
        .questions-head p {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }
        .questions-add-button {
            background: var(--orange);
            border: 1px solid #101827;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 36px;
            margin-top: 16px;
            min-width: 0;
            padding: 0 12px;
            width: 136px;
        }
        .questions-add-button:hover {
            background: #000;
            border-color: #000;
            box-shadow: none;
            color: #fff;
        }
        .questions-filter-note {
            font-size: 12px;
            margin: -8px 0 0;
        }
        .questions-table-wrap {
            overflow-x: auto;
        }
        .questions-table {
            border-collapse: collapse;
            width: 100%;
        }
        .questions-table th {
            background: #f4f7fb;
            color: #365886;
            font-size: 11px;
            letter-spacing: 0;
            padding: 9px 14px;
            text-align: left;
            text-transform: uppercase;
        }
        .questions-table th:first-child {
            border-radius: 8px 0 0 8px;
            width: 56px;
        }
        .questions-table th:last-child {
            border-radius: 0 8px 8px 0;
            width: 220px;
        }
        .questions-table td {
            border-bottom: 1px solid #e3eaf3;
            color: var(--ink);
            font-size: 13px;
            padding: 9px 14px;
            vertical-align: middle;
        }
        .questions-actions {
            align-items: center;
            display: flex;
            gap: 9px;
        }
        .questions-actions form {
            margin: 0;
        }
        .questions-action {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 7px;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 11px;
            font-weight: 700;
            gap: 8px;
            height: 29px;
            justify-content: center;
            min-width: 88px;
            padding: 0 10px;
            transition: background .2s ease, border-color .2s ease, color .2s ease;
            width: 88px;
        }
        .questions-action svg {
            fill: none;
            height: 13px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 13px;
        }
        .questions-edit {
            background: #101827;
            color: #fff;
        }
        .questions-edit:hover {
            background: #000;
            color: #fff;
        }
        .questions-delete {
            background: #d7192f;
            color: #fff;
        }
        .questions-delete:hover {
            background: #a70f22;
            color: #fff;
        }
        body.question-modal-open {
            overflow: hidden;
        }
        .question-modal-backdrop {
            align-items: center;
            background: rgba(2, 10, 24, .55);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 90;
        }
        .question-modal-backdrop[hidden] {
            display: none;
        }
        .question-modal {
            background: rgba(255,255,255,.98);
            border: 1px solid rgba(216, 225, 238, .96);
            border-radius: 10px;
            box-shadow: 0 28px 70px rgba(7, 24, 58, .28);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            padding: 24px 28px 20px;
            position: relative;
            width: min(530px, calc(100vw - 40px));
        }
        .question-modal-close {
            align-items: center;
            background: transparent;
            border: 0;
            color: #536f9a;
            cursor: pointer;
            display: flex;
            height: 32px;
            justify-content: center;
            padding: 0;
            position: absolute;
            right: 18px;
            top: 18px;
            width: 32px;
        }
        .question-modal-close:hover {
            color: #000;
        }
        .question-modal-close svg {
            fill: none;
            height: 22px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 22px;
        }
        .question-modal-head h2 {
            font-family: var(--font-display);
            font-size: 24px;
            line-height: 1.1;
            margin: 0 34px 6px 0;
        }
        .question-modal-head p {
            color: var(--muted);
            font-size: 14px;
            margin: 0 0 20px;
        }
        .question-modal-form {
            display: grid;
            gap: 14px;
        }
        .question-field {
            display: grid;
            gap: 7px;
            position: relative;
        }
        .question-field label,
        .question-toggle-row > span,
        .question-choice-section h3 {
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            margin: 0;
        }
        .question-field label span {
            color: var(--muted);
            font-weight: 500;
        }
        .question-field input,
        .question-field textarea,
        .question-select-shell {
            background: #fff;
            border: 1px solid #ccd7e6;
            border-radius: 7px;
            color: var(--ink);
            font: inherit;
            font-size: 13px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .question-field input,
        .question-select-shell {
            height: 42px;
        }
        .question-field input {
            padding: 0 13px;
        }
        .question-field textarea {
            min-height: 66px;
            padding: 12px 13px 22px;
            resize: vertical;
        }
        .question-field input:focus,
        .question-field textarea:focus,
        .question-select-shell:focus-within,
        .question-field input:hover,
        .question-field textarea:hover,
        .question-select-shell:hover {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(239, 130, 10, .12);
            outline: 0;
        }
        .question-counter {
            bottom: 8px;
            color: var(--muted);
            font-size: 11px;
            position: absolute;
            right: 10px;
        }
        .question-select-shell {
            align-items: center;
            display: flex;
            padding: 0 12px;
        }
        .question-select-shell select {
            appearance: none;
            background: transparent;
            border: 0;
            color: #365886;
            font: inherit;
            height: 100%;
            padding: 0;
            width: 100%;
        }
        .question-select-shell select:focus {
            outline: 0;
        }
        .question-select-shell svg {
            fill: none;
            height: 17px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 17px;
        }
        .question-toggle-row {
            align-items: center;
            border-bottom: 1px solid #e3eaf3;
            display: flex;
            gap: 12px;
            padding: 2px 0 14px;
        }
        .question-toggle-row input {
            opacity: 0;
            position: absolute;
        }
        .question-toggle-row i {
            background: #cbd5e1;
            border-radius: 999px;
            cursor: pointer;
            height: 28px;
            position: relative;
            transition: background .2s ease;
            width: 48px;
        }
        .question-toggle-row i::after {
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(7,24,58,.16);
            content: "";
            height: 22px;
            left: 3px;
            position: absolute;
            top: 3px;
            transition: transform .2s ease;
            width: 22px;
        }
        .question-toggle-row input:checked + i {
            background: #0b74de;
        }
        .question-toggle-row input:checked + i::after {
            transform: translateX(20px);
        }
        .question-toggle-row em {
            color: #365886;
            font-style: normal;
            font-size: 13px;
        }
        .question-choice-section {
            display: grid;
            gap: 9px;
        }
        .question-choice-list {
            display: grid;
            gap: 8px;
        }
        .question-choice-row {
            align-items: center;
            display: grid;
            gap: 9px;
            grid-template-columns: 20px minmax(0, 1fr) 30px;
        }
        .question-choice-grip {
            color: #9aa8bc;
            font-size: 18px;
            line-height: 1;
            text-align: center;
        }
        .question-choice-row input {
            border: 1px solid #ccd7e6;
            border-radius: 7px;
            font: inherit;
            font-size: 13px;
            height: 38px;
            padding: 0 12px;
        }
        .question-choice-row input:focus,
        .question-choice-row input:hover {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(239, 130, 10, .12);
            outline: 0;
        }
        .question-choice-row button {
            align-items: center;
            background: #e8eef6;
            border: 0;
            border-radius: 50%;
            color: #6b7fa3;
            cursor: pointer;
            display: flex;
            height: 28px;
            justify-content: center;
            padding: 0;
            width: 28px;
        }
        .question-choice-row button:hover {
            background: #dce5f1;
            color: #000;
        }
        .question-choice-row button svg {
            fill: none;
            height: 15px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 15px;
        }
        .question-choice-add {
            align-items: center;
            background: #fff;
            border: 1px solid #b8d4fa;
            border-radius: 7px;
            color: #0b69d1;
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 8px;
            height: 32px;
            justify-content: center;
            padding: 0 14px;
            width: max-content;
        }
        .question-choice-add:hover {
            border-color: var(--orange);
            color: #000;
        }
        .question-modal-actions {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 2px;
        }
        .question-modal-backdrop.is-editing .question-modal {
            max-height: calc(100vh - 88px);
            padding: 16px 18px 14px;
            width: min(370px, calc(100vw - 48px));
        }
        .question-modal-backdrop.is-editing .question-modal-close {
            height: 26px;
            right: 14px;
            top: 13px;
            width: 26px;
        }
        .question-modal-backdrop.is-editing .question-modal-close svg {
            height: 17px;
            width: 17px;
        }
        .question-modal-backdrop.is-editing .question-modal-head h2 {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .question-modal-backdrop.is-editing .question-modal-head p {
            font-size: 12px;
            margin-bottom: 10px;
        }
        .question-modal-backdrop.is-editing .question-modal-form {
            gap: 8px;
        }
        .question-modal-backdrop.is-editing .question-field {
            gap: 4px;
        }
        .question-modal-backdrop.is-editing .question-field label,
        .question-modal-backdrop.is-editing .question-toggle-row > span,
        .question-modal-backdrop.is-editing .question-choice-section h3 {
            font-size: 11px;
        }
        .question-modal-backdrop.is-editing .question-field input,
        .question-modal-backdrop.is-editing .question-field textarea,
        .question-modal-backdrop.is-editing .question-select-shell,
        .question-modal-backdrop.is-editing .question-select-shell select,
        .question-modal-backdrop.is-editing .question-choice-row input {
            font-size: 12px;
        }
        .question-modal-backdrop.is-editing .question-field input,
        .question-modal-backdrop.is-editing .question-select-shell {
            height: 34px;
        }
        .question-modal-backdrop.is-editing .question-field input {
            padding: 0 11px;
        }
        .question-modal-backdrop.is-editing .question-field textarea {
            min-height: 48px;
            padding: 8px 11px 18px;
        }
        .question-modal-backdrop.is-editing .question-counter {
            bottom: 6px;
            font-size: 10px;
            right: 8px;
        }
        .question-modal-backdrop.is-editing .question-toggle-row {
            gap: 8px;
            padding: 0 0 8px;
        }
        .question-modal-backdrop.is-editing .question-toggle-row i {
            height: 20px;
            width: 36px;
        }
        .question-modal-backdrop.is-editing .question-toggle-row i::after {
            height: 14px;
            width: 14px;
        }
        .question-modal-backdrop.is-editing .question-toggle-row input:checked + i::after {
            transform: translateX(16px);
        }
        .question-modal-backdrop.is-editing .question-toggle-row em {
            font-size: 12px;
        }
        .question-modal-backdrop.is-editing .question-choice-section,
        .question-modal-backdrop.is-editing .question-choice-list {
            gap: 5px;
        }
        .question-modal-backdrop.is-editing .question-choice-row {
            gap: 6px;
            grid-template-columns: 16px minmax(0, 1fr) 24px;
        }
        .question-modal-backdrop.is-editing .question-choice-grip {
            font-size: 15px;
        }
        .question-modal-backdrop.is-editing .question-choice-row input {
            height: 30px;
            padding: 0 10px;
        }
        .question-modal-backdrop.is-editing .question-choice-row button {
            height: 22px;
            width: 22px;
        }
        .question-modal-backdrop.is-editing .question-choice-row button svg {
            height: 12px;
            width: 12px;
        }
        .question-modal-backdrop.is-editing .question-choice-add {
            height: 27px;
            padding: 0 10px;
        }
        .question-modal-backdrop.is-editing .question-cancel,
        .question-modal-backdrop.is-editing .question-save {
            height: 31px;
            min-width: 74px;
            padding: 0 14px;
        }
        .question-cancel,
        .question-save {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 38px;
            justify-content: center;
            min-width: 86px;
            padding: 0 18px;
        }
        .question-cancel {
            background: #f8fafc;
            color: var(--ink);
        }
        .question-cancel:hover,
        .question-save:hover {
            background: #000;
            border-color: #000;
            color: #fff;
        }
        .question-error {
            color: #dc2626;
            font-size: 11px;
        }
        .feedback-dashboard {
            display: grid;
            gap: 12px;
        }
        .feedback-dashboard-head {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }
        .feedback-dashboard-head h1 {
            font-family: var(--font-display);
            font-size: clamp(24px, 2vw, 30px);
            line-height: 1.08;
            margin: 0 0 5px;
        }
        .feedback-dashboard-head .muted,
        .feedback-dashboard-copy {
            color: var(--muted);
            font-size: 12px;
            margin: 0;
        }
        .feedback-kpi-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .feedback-kpi {
            align-items: center;
            background: #fff;
            border: 1px solid rgba(216, 225, 238, .82);
            border-radius: 8px;
            display: grid;
            gap: 2px 10px;
            grid-template-columns: 28px 1fr;
            min-height: 76px;
            padding: 11px 13px;
        }
        .feedback-kpi-icon {
            fill: none;
            grid-row: 1 / span 3;
            height: 23px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 23px;
        }
        .feedback-blue { color: #045eb7; }
        .feedback-gold { color: #f59e0b; }
        .feedback-green { color: #0f9f6e; }
        .feedback-orange { color: var(--orange); }
        .feedback-kpi span {
            color: #365886;
            font-size: 12px;
        }
        .feedback-kpi strong {
            font-size: 23px;
            line-height: 1;
        }
        .feedback-kpi small {
            color: var(--muted);
            font-size: 11px;
        }
        .dashboard-feedback-kpis .feedback-kpi {
            overflow: hidden;
            position: relative;
        }
        .feedback-kpi-link {
            color: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .feedback-kpi-link:hover,
        .feedback-kpi-link:focus-visible,
        .feedback-kpi-link.active {
            background: #f6f8fb;
            border-color: #c8d3e1;
            box-shadow: 0 0 0 2px rgba(7, 24, 58, .035);
            outline: 0;
        }
        .feedback-kpi-spark,
        .feedback-kpi-dots {
            align-items: end;
            bottom: 12px;
            display: inline-flex;
            gap: 4px;
            opacity: .65;
            position: absolute;
            right: 14px;
        }
        .feedback-kpi-spark b {
            background: rgba(4, 94, 183, .18);
            border-radius: 999px;
            display: block;
            width: 7px;
        }
        .feedback-kpi-spark b:nth-child(1) { height: 8px; }
        .feedback-kpi-spark b:nth-child(2) { height: 12px; }
        .feedback-kpi-spark b:nth-child(3) { height: 17px; }
        .feedback-kpi-spark b:nth-child(4) { height: 22px; }
        .feedback-kpi-spark.green b {
            background: rgba(15, 159, 110, .18);
        }
        .feedback-kpi-spark.orange b {
            background: rgba(239, 130, 10, .22);
        }
        .feedback-kpi-dots {
            align-items: center;
            bottom: 20px;
            gap: 11px;
        }
        .feedback-kpi-dots b {
            background: #0f9f6e;
            border-radius: 50%;
            display: block;
            height: 4px;
            width: 4px;
        }
        .feedback-chart-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(360px, 1.25fr) repeat(3, minmax(170px, .6fr));
        }
        .dashboard-feedback-charts {
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
        }
        .feedback-chart-card {
            background: #fff;
            border: 1px solid rgba(216, 225, 238, .82);
            border-radius: 8px;
            min-height: 150px;
            padding: 11px 13px;
        }
        .feedback-card-title {
            align-items: flex-start;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .feedback-card-title h2,
        .feedback-table-head h2 {
            font-family: var(--font-display);
            font-size: 16px;
            line-height: 1.12;
            margin: 0;
        }
        .feedback-card-title span,
        .feedback-table-head p {
            color: var(--muted);
            font-size: 11px;
            margin: 0;
        }
        .feedback-rating-bars {
            display: grid;
            gap: 7px;
        }
        .feedback-rating-row {
            align-items: center;
            display: grid;
            font-size: 11px;
            gap: 8px;
            grid-template-columns: 38px minmax(0, 1fr) 64px;
        }
        .feedback-rating-track {
            background: #e9eef5;
            border-radius: 99px;
            height: 11px;
            overflow: hidden;
        }
        .feedback-rating-track span {
            background: linear-gradient(90deg, #0f9f6e, #78b99b);
            display: block;
            height: 100%;
        }
        .feedback-donut-wrap {
            align-items: center;
            display: grid;
            gap: 10px;
            justify-items: center;
        }
        .feedback-donut {
            align-items: center;
            border-radius: 50%;
            display: flex;
            height: 78px;
            justify-content: center;
            position: relative;
            width: 78px;
        }
        .feedback-donut::after {
            background: #fff;
            border-radius: 50%;
            content: "";
            height: 50px;
            position: absolute;
            width: 50px;
        }
        .feedback-donut span {
            font-size: 19px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }
        .feedback-legend {
            display: grid;
            gap: 4px;
            width: 100%;
        }
        .feedback-legend span {
            align-items: center;
            color: #365886;
            display: flex;
            font-size: 10.5px;
            gap: 6px;
        }
        .feedback-legend i {
            border-radius: 50%;
            height: 7px;
            width: 7px;
        }
        .feedback-gauge {
            align-items: center;
            display: grid;
            justify-items: center;
            padding-top: 8px;
            text-align: center;
        }
        .feedback-gauge-arc {
            background: conic-gradient(from 270deg at 50% 100%, #0f9f6e 0 var(--score-deg), #e9eef5 var(--score-deg) 180deg, transparent 180deg 360deg);
            border-radius: 94px 94px 0 0;
            height: 54px;
            width: 108px;
        }
        .feedback-gauge strong {
            font-size: 24px;
            line-height: 1;
            margin-top: -20px;
            position: relative;
            z-index: 1;
        }
        .feedback-gauge span {
            color: var(--muted);
            font-size: 11px;
        }
        .feedback-gauge small {
            color: var(--muted);
            font-size: 10.5px;
            margin-top: 3px;
        }
        .feedback-mini-line {
            height: 96px;
            width: 100%;
        }
        .feedback-mini-line text {
            fill: #6b7fa3;
            font-size: 8.5px;
        }
        .feedback-table-head {
            align-items: flex-end;
            border-top: 1px solid rgba(216, 225, 238, .82);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding-top: 12px;
        }
        .dashboard-feedback-table-head {
            border-top: 0;
            margin-bottom: 8px;
            padding-top: 0;
        }
        .feedback-dashboard .button.secondary {
            background: #374151;
            border: 1px solid #101827;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 36px;
            padding: 0 18px;
        }
        .feedback-dashboard .button.secondary:hover {
            background: #000;
            border-color: #000;
        }
        .stars { color: var(--gold); letter-spacing: 2px; }
        .muted { color: var(--muted); }
        .rating-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .rating-choice {
            border: 1px solid #101827;
            border-radius: 10px;
            padding: 10px 13px;
            cursor: pointer;
            background: rgba(255, 255, 255, .82);
            transition: border-color .2s ease, box-shadow .2s ease, color .2s ease;
        }
        .rating-choice:hover {
            border-color: #000;
            color: #000;
            box-shadow: 0 10px 22px rgba(7, 24, 58, .08);
        }
        .rating-choice input { width: auto; margin-right: 6px; }
        .slider-field {
            background: #f8fbfd;
            border: 1px solid var(--line);
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 18px;
        }
        .slider-heading {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .slider-value {
            align-items: center;
            background: var(--navy);
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-weight: 800;
            height: 38px;
            justify-content: center;
            min-width: 54px;
            padding: 0 12px;
        }
        .range-control {
            accent-color: var(--blue);
            padding: 0;
        }
        .range-scale {
            color: var(--muted);
            display: flex;
            font-size: 12px;
            justify-content: space-between;
            margin-top: 6px;
        }
        .hero {
            background: rgba(255, 255, 255, .9);
            border: 1px solid rgba(216, 225, 238, .9);
            border-radius: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin: 0 auto;
            max-width: 1040px;
            overflow: hidden;
            box-shadow: 0 18px 46px rgba(7, 24, 58, .11);
            width: min(1040px, 100%);
        }
        .hero-copy {
            align-content: center;
            display: grid;
            padding: clamp(24px, 3.2vw, 40px) clamp(30px, 4vw, 54px);
        }
        .hero h1 {
            font-family: var(--font-display);
            font-size: clamp(34px, 3.45vw, 48px);
            line-height: .98;
            margin: 0 0 8px;
            max-width: 610px;
            letter-spacing: 0;
        }
        .hero h1 .accent { color: var(--orange); }
        .hero-visual {
            background:
                linear-gradient(90deg, rgba(255,255,255,.05), rgba(255,255,255,.1)),
                var(--cafe-photo);
            background-position: center;
            background-size: cover;
            min-height: clamp(330px, 43vh, 390px);
            position: relative;
        }
        .hero-feedback-card {
            backdrop-filter: blur(16px);
            background: linear-gradient(145deg, rgba(255, 255, 255, .82), rgba(255, 255, 255, .55));
            border: 1px solid rgba(255, 255, 255, .9);
            border-radius: 22px;
            box-shadow: 0 16px 34px rgba(7, 24, 58, .16);
            padding: clamp(15px, 1.8vw, 22px);
            position: absolute;
            right: 13%;
            top: 18%;
            width: min(300px, 62%);
            z-index: 2;
        }
        .face-row {
            display: flex;
            gap: 10px;
            margin: 14px 0;
        }
        .face {
            align-items: center;
            background: rgba(232, 228, 224, .82);
            border-radius: 50%;
            color: #777;
            display: inline-flex;
            height: 36px;
            justify-content: center;
            width: 36px;
        }
        .face svg {
            height: 21px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.2;
            width: 21px;
            fill: none;
        }
        .face.active {
            background: linear-gradient(135deg, var(--orange-2), var(--orange));
            color: #fff;
            box-shadow: 0 10px 22px rgba(223, 116, 13, .28);
        }
        .mock-line {
            background: rgba(7, 24, 58, .13);
            border-radius: 999px;
            height: 10px;
            margin-top: 10px;
        }
        .mock-line.orange {
            background: linear-gradient(90deg, var(--orange), #f3a44a);
            width: 36%;
        }
        .mock-line.tiny { width: 26%; }
        .mock-line.long { width: 78%; }
        .mock-line.short { width: 56%; }
        .eyebrow {
            color: var(--blue);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 7px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .hero-lead {
            color: var(--muted);
            font-size: clamp(15px, 1.45vw, 20px);
            font-weight: 400;
            line-height: 1.32;
            margin: 0;
            max-width: 500px;
        }
        .hero-form {
            margin-top: 12px;
        }
        .consent-option {
            align-items: center;
            display: flex;
            font-size: 14px;
            gap: 10px;
            min-height: 34px;
            margin-bottom: 6px;
            max-width: 390px;
            padding: 5px 10px;
        }
        .consent-option.last {
            margin-bottom: 8px;
        }
        .consent-option input {
            flex: 0 0 auto;
        }
        .inline-policy-link {
            color: #045eb7;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .hero-cta {
            border-radius: 10px;
            font-size: 14px;
            min-height: 36px;
            min-width: 128px;
            padding: 8px 18px;
            box-shadow: none;
        }
        .button:disabled,
        .hero-cta:disabled,
        .survey-button:disabled {
            background: #9aa8bb;
            border-color: #9aa8bb;
            box-shadow: none;
            color: #fff;
            cursor: not-allowed;
            opacity: .72;
        }
        .hero-cta:hover {
            box-shadow: none;
        }
        .hero-cta svg {
            height: 17px;
            width: 17px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.2;
        }
        .hero-card-title {
            font-size: clamp(20px, 1.9vw, 25px);
            font-weight: 800;
            line-height: 1.15;
            margin: 0;
            max-width: 220px;
        }
        .feature-text {
            margin: 7px 0 0;
        }
        .feature-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            justify-items: center;
            margin: 24px auto 0;
            max-width: 880px;
            width: min(880px, 100%);
        }
        .feature {
            align-items: center;
            background: transparent;
            border: 0;
            display: grid;
            gap: 12px;
            grid-template-columns: 34px minmax(0, 1fr);
            justify-content: center;
            max-width: 300px;
            padding: 0 20px;
            width: 100%;
        }
        .feature + .feature {
            border-left: 1px solid rgba(83, 111, 154, .45);
        }
        .feature-icon {
            color: var(--orange);
            height: 28px;
            width: 28px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }
        .dashboard-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
            gap: 18px;
            align-items: start;
        }
        .mini-form {
            background: #f8fbfd;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
        }
        .live-pill {
            align-items: center;
            color: #166534;
            display: inline-flex;
            font-size: 13px;
            gap: 8px;
            margin-top: 6px;
        }
        .live-dot {
            animation: pulse-dot 1.4s ease-in-out infinite;
            background: #22c55e;
            border-radius: 50%;
            height: 9px;
            width: 9px;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: .35; transform: scale(.85); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border-bottom: 1px solid #e6edf3;
            padding: 13px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-size: 13px;
            text-transform: uppercase;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .logo-preview {
            align-items: center;
            background: #f8fbfd;
            border: 1px dashed #b8c8d7;
            border-radius: 8px;
            display: flex;
            min-height: 150px;
            justify-content: center;
            overflow: hidden;
        }
        .logo-preview img { max-height: 140px; max-width: 100%; object-fit: contain; }
        .business-profile-panel {
            background: rgba(255,255,255,.98);
            border: 1px solid rgba(216, 225, 238, .96);
            border-radius: 10px;
            box-shadow: 0 14px 38px rgba(7, 24, 58, .055);
            padding: 30px 34px 24px;
        }
        .business-profile-head {
            border-bottom: 1px solid #dbe5f1;
            margin-bottom: 26px;
            padding-bottom: 18px;
        }
        .business-profile-head h1 {
            font-family: var(--font-display);
            font-size: clamp(28px, 2.5vw, 38px);
            line-height: 1.06;
            margin: 0 0 6px;
        }
        .business-profile-head p {
            color: var(--muted);
            font-size: 16px;
            margin: 0;
        }
        .business-profile-form {
            display: grid;
            gap: 22px;
        }
        .business-profile-grid {
            align-items: start;
            display: grid;
            gap: 32px;
            grid-template-columns: minmax(260px, 310px) minmax(0, 1fr);
        }
        .business-logo-field label,
        .business-field label {
            color: var(--ink);
            font-size: 14px;
            font-weight: 800;
            margin: 0 0 8px;
        }
        .business-logo-upload {
            align-items: center;
            background: rgba(255,255,255,.75);
            border: 1px dashed #a9bfd9;
            border-radius: 7px;
            cursor: pointer;
            display: flex;
            height: 212px;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
            width: 100%;
        }
        .business-logo-upload:hover,
        .business-logo-upload:focus-within,
        .business-logo-upload.is-dragging {
            background: #fff;
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(239, 130, 10, .12);
        }
        .business-logo-upload input {
            display: none;
        }
        .business-logo-preview {
            align-items: center;
            color: #5d6f90;
            display: grid;
            gap: 8px;
            justify-items: center;
            padding: 16px;
            text-align: center;
            width: 100%;
        }
        .business-logo-preview svg {
            color: #5d6f90;
            fill: none;
            height: 48px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
            width: 56px;
        }
        .business-logo-preview strong {
            color: var(--ink);
            font-size: 14px;
            line-height: 1.2;
        }
        .business-logo-preview em,
        .business-logo-note {
            color: var(--muted);
            font-size: 13px;
            font-style: normal;
            line-height: 1.4;
        }
        .business-logo-preview img {
            max-height: 180px;
            max-width: 90%;
            object-fit: contain;
        }
        .business-logo-note {
            margin: 12px 0 0;
        }
        .business-fields-grid {
            display: grid;
            gap: 16px 22px;
            grid-template-columns: repeat(2, minmax(205px, 320px));
            justify-content: space-between;
            max-width: 700px;
        }
        .business-field {
            margin: 0;
            min-width: 0;
        }
        .business-input-shell {
            align-items: center;
            background: #fff;
            border: 1px solid #aebcd1;
            border-radius: 7px;
            display: grid;
            gap: 10px;
            grid-template-columns: 22px minmax(0, 1fr);
            min-height: 40px;
            padding: 0 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .business-input-shell:hover,
        .business-input-shell:focus-within {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(239, 130, 10, .12);
        }
        .business-input-shell > svg {
            color: #52688d;
            fill: none;
            height: 19px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.9;
            width: 19px;
        }
        .business-input-shell input,
        .business-input-shell select,
        .business-input-shell textarea {
            background: transparent;
            border: 0;
            border-radius: 0;
            color: var(--ink);
            font: inherit;
            font-size: 14px;
            min-width: 0;
            outline: 0;
            padding: 0;
            width: 100%;
        }
        .business-input-shell input,
        .business-input-shell select {
            height: 38px;
        }
        .business-select-shell {
            grid-template-columns: 22px minmax(0, 1fr) 16px;
        }
        .business-select-shell select {
            appearance: none;
            color: var(--ink);
        }
        .business-caret {
            height: 16px !important;
            width: 16px !important;
        }
        .business-description-field {
            grid-column: 1 / -1;
            max-width: none;
        }
        .business-textarea-shell {
            align-items: start;
            grid-template-columns: 22px minmax(0, 1fr);
            min-height: 64px;
            padding: 10px 12px;
        }
        .business-textarea-shell textarea {
            min-height: 42px;
            resize: vertical;
        }
        .business-save-button {
            background: var(--orange);
            border: 1px solid #101827;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 36px;
            justify-content: center;
            justify-self: end;
            padding: 0 12px;
            width: 136px;
        }
        .business-save-button:hover {
            background: #000;
            border-color: #000;
            box-shadow: none;
            color: #fff;
        }
        .business-error {
            color: #dc2626;
            display: block;
            font-size: 11px;
            margin-top: 6px;
        }
        .login-stage {
            align-items: center;
            display: grid;
            gap: clamp(34px, 4vw, 72px);
            grid-template-columns: minmax(680px, 920px) minmax(306px, 332px);
            margin: 0 auto;
            width: min(1320px, 100%);
        }
        .login-showcase {
            display: grid;
            gap: clamp(14px, 1.8vw, 26px);
            grid-template-columns: minmax(285px, .8fr) minmax(330px, .95fr);
            align-items: center;
        }
        .login-showcase-copy {
            max-width: 385px;
        }
        .login-showcase-copy .eyebrow {
            font-size: 9px;
            letter-spacing: 5px;
            margin-bottom: 10px;
        }
        .login-showcase-copy h1 {
            font-family: var(--font-display);
            font-size: clamp(31px, 2.7vw, 44px);
            font-weight: 800;
            letter-spacing: 0;
            line-height: .98;
            margin: 0 0 9px;
        }
        .login-showcase-copy .accent {
            color: var(--orange);
        }
        .login-showcase-copy p {
            color: var(--blue);
            font-size: clamp(13px, 1vw, 16px);
            line-height: 1.32;
            margin: 0;
        }
        .login-showcase-art {
            background: transparent;
            min-height: clamp(270px, 39vh, 350px);
            position: relative;
        }
        .login-showcase-art::before {
            background:
                radial-gradient(ellipse at center, rgba(255,255,255,0) 0 46%, rgba(255,255,255,.22) 67%, rgba(255,255,255,.88) 100%),
                linear-gradient(90deg, rgba(255,255,255,.06), rgba(255,255,255,.02)),
                var(--cafe-photo);
            background-position: center;
            background-size: cover;
            content: "";
            filter: saturate(1.02) contrast(.98);
            inset: -46px -66px;
            opacity: .94;
            -webkit-mask-image: radial-gradient(ellipse at center, #000 0 38%, rgba(0,0,0,.8) 54%, rgba(0,0,0,.32) 72%, transparent 90%);
            mask-image: radial-gradient(ellipse at center, #000 0 38%, rgba(0,0,0,.8) 54%, rgba(0,0,0,.32) 72%, transparent 90%);
            -webkit-mask-size: 100% 100%;
            mask-size: 100% 100%;
            position: absolute;
            z-index: 0;
        }
        .login-mini-card {
            backdrop-filter: blur(16px);
            background: linear-gradient(145deg, rgba(255,255,255,.86), rgba(255,255,255,.58));
            border: 1px solid rgba(255,255,255,.92);
            border-radius: 22px;
            box-shadow: 0 18px 38px rgba(7, 24, 58, .16);
            left: 50%;
            padding: 16px;
            position: absolute;
            top: 24%;
            transform: translateX(-50%);
            width: min(265px, 74%);
            z-index: 1;
        }
        .login-mini-card h2 {
            font-size: 20px;
            line-height: 1.1;
            margin: 0;
        }
        .login-mini-card .face-row {
            margin: 14px 0;
        }
        .login-feature-row {
            display: grid;
            gap: 0;
            grid-template-columns: repeat(3, 1fr);
            grid-column: 1 / -1;
            margin-top: 12px;
            max-width: 650px;
            transform: translateY(8px);
        }
        .login-feature {
            align-items: center;
            display: grid;
            gap: 8px;
            grid-template-columns: 22px 1fr;
            padding: 0 14px;
        }
        .login-feature + .login-feature {
            border-left: 1px solid rgba(83, 111, 154, .42);
        }
        .login-feature strong {
            display: block;
            font-size: 11px;
            margin-bottom: 3px;
        }
        .login-feature .feature-icon {
            height: 18px;
            width: 18px;
        }
        .login-feature p {
            font-size: 10px;
            line-height: 1.28;
            margin: 0;
        }
        .login-grid {
            display: grid;
            grid-template-columns: 88px 1fr;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
        }
        .login-panel {
            background: transparent;
            border: 0;
            margin: 0 auto;
            max-width: 332px;
            width: min(332px, 100%);
            overflow: hidden;
            padding: 0;
            transform: translateY(-14px);
        }
        body.login-screen .login-splash {
            display: flex;
        }
        .login-splash {
            background:
                linear-gradient(138deg, transparent 0 18%, rgba(240,139,34,.96) 18.4%, rgba(240,139,34,.96) 19.6%, transparent 20.2%),
                radial-gradient(ellipse at -4% 105%, transparent 0 40%, rgba(240,139,34,.98) 41%, rgba(240,139,34,.98) 47%, transparent 48%),
                repeating-radial-gradient(ellipse at -8% 106%, transparent 0 9px, rgba(70, 126, 196, .27) 10px 11px, transparent 12px 19px),
                linear-gradient(145deg, #06162f, #06111f 58%, #0a2542);
            background-position: center;
            background-size: cover;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 284px;
            margin: -1px 0 -1px -1px;
            overflow: hidden;
            padding: 15px 12px;
            position: relative;
        }
        .login-splash::before {
            background: linear-gradient(32deg, transparent 0 43%, rgba(240,139,34,.9) 44%, rgba(240,139,34,.9) 49%, transparent 50%);
            bottom: -12px;
            content: "";
            height: 128px;
            left: -20px;
            position: absolute;
            width: 166px;
            z-index: 0;
        }
        .login-splash::after {
            background:
                radial-gradient(ellipse at 0% 100%, rgba(240,139,34,.95) 0 10%, transparent 11%),
                radial-gradient(ellipse at -12% 105%, rgba(7,24,58,.95) 0 27%, transparent 28%);
            content: "";
            inset: 0;
            position: absolute;
            z-index: 0;
        }
        .login-brand {
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }
        .login-brand .brand-emblem {
            height: 22px;
            width: 42px;
        }
        .login-splash h1 {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.05;
            margin: 0 0 7px;
            position: relative;
            z-index: 2;
        }
        .login-splash h1::before {
            background: var(--orange);
            border-radius: 999px;
            content: "";
            display: block;
            height: 3px;
            margin-bottom: 14px;
            width: 38px;
        }
        .login-splash p {
            font-size: 10.5px;
            font-weight: 400;
            line-height: 1.4;
            margin: 0;
            opacity: .9;
            position: relative;
            z-index: 2;
        }
        .login-form {
            align-content: center;
            display: grid;
            justify-items: center;
            min-height: 284px;
            padding: 20px 16px;
        }
        .login-form form {
            max-width: 190px;
            width: 100%;
        }
        .login-form h1 {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
            margin: 0 0 8px;
            width: 100%;
        }
        .login-form .muted {
            font-size: 11.5px;
            font-weight: 400;
            margin: 0 0 14px;
            width: 100%;
        }
        .login-form .field {
            margin-bottom: 11px;
        }
        .login-form label {
            color: var(--ink);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .input-shell {
            align-items: center;
            border: 1px solid #101827;
            border-radius: 8px;
            display: grid;
            grid-template-columns: 17px 1fr auto;
            gap: 7px;
            height: 32px;
            max-width: 190px;
            overflow: hidden;
            padding: 0 10px;
            transition: border-color .2s ease, box-shadow .2s ease;
            width: 100%;
        }
        .input-shell:hover {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .1);
        }
        .input-shell:focus-within {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .16);
        }
        .input-shell input {
            border: 0;
            border-radius: 0;
            font-size: 12px;
            font-weight: 400;
            min-width: 0;
            min-height: 30px;
            overflow: hidden;
            padding: 0;
            text-overflow: ellipsis;
        }
        .input-shell .password-mask-input {
            font-family: "Segoe UI Symbol", "Segoe UI", Arial, sans-serif;
            font-size: 7px;
            letter-spacing: 2px;
            line-height: 1;
        }
        .input-shell .password-mask-input::placeholder {
            font-size: 12px;
            letter-spacing: 0;
        }
        .input-shell .password-mask-input.is-visible {
            font-size: 12px;
            letter-spacing: 0;
        }
        .input-shell input:focus {
            outline: 0;
        }
        .input-icon {
            color: var(--ink);
            height: 16px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.1;
            width: 16px;
            fill: none;
        }
        .input-icon.fillable {
            fill: currentColor;
            stroke-width: 0;
        }
        .icon-button {
            align-items: center;
            background: transparent;
            border: 0;
            color: #6f86aa;
            cursor: pointer;
            display: inline-flex;
            padding: 0;
        }
        .icon-button .eye-closed {
            display: none;
        }
        .icon-button.is-visible .eye-open {
            display: none;
        }
        .icon-button.is-visible .eye-closed {
            display: block;
        }
        .login-options {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: flex-end;
            margin: -4px 0 16px;
            max-width: 190px;
            width: 100%;
        }
        .forgot-link {
            color: #315a8e;
            font-size: 12px;
            font-weight: 400;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .login-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            max-width: 190px;
            width: 100%;
        }
        .login-actions .button {
            border: 1px solid #101827;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 32px;
            justify-content: center;
            min-width: 70px;
            padding: 0 13px;
        }
        .login-actions .button.secondary {
            background: #fff;
            color: var(--ink);
        }
        .login-actions .button.secondary:hover {
            background: #fff;
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .12);
            color: var(--ink);
        }
        .login-actions .button:not(.secondary) {
            background: linear-gradient(135deg, var(--orange-2), var(--orange));
            color: #fff;
        }
        .login-actions .button:not(.secondary):hover {
            background: #000;
            border-color: #000;
            color: #fff;
        }
        .survey-shell {
            align-items: center;
            display: grid;
            gap: clamp(18px, 2.4vw, 36px);
            grid-template-columns: 196px minmax(0, 585px);
            justify-content: center;
            width: min(820px, 100%);
        }
        .survey-steps {
            display: grid;
            gap: 14px;
            position: relative;
        }
        .survey-steps::before {
            background: rgba(83, 111, 154, .2);
            bottom: 8px;
            content: "";
            left: 5px;
            position: absolute;
            top: 8px;
            width: 1px;
        }
        .survey-step {
            align-items: center;
            display: grid;
            gap: 12px;
            grid-template-columns: 16px 1fr;
            position: relative;
            z-index: 1;
        }
        .survey-step-number {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 0;
            color: var(--muted);
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            height: 20px;
            justify-content: center;
            line-height: 1;
            width: 20px;
            box-shadow: none;
        }
        .survey-step.active .survey-step-number {
            background: transparent;
            color: var(--orange);
        }
        .survey-step-label { display: none; }
        .survey-step strong {
            display: block;
            font-size: 12.5px;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .survey-step span:last-child {
            color: var(--muted);
            display: block;
            font-size: 12px;
            line-height: 1.35;
        }
        .survey-card {
            background: rgba(255,255,255,.94);
            border: 1px solid rgba(216, 225, 238, .9);
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(7, 24, 58, .13);
            display: grid;
            grid-template-columns: minmax(0, 1fr) 190px;
            min-height: 226px;
            overflow: hidden;
            position: relative;
            width: 100%;
        }
        .survey-card.no-visual {
            grid-template-columns: 1fr;
            max-width: 540px;
        }
        .survey-content {
            align-content: center;
            display: grid;
            padding: 22px 24px;
            position: relative;
            z-index: 2;
        }
        .survey-pill {
            align-items: center;
            background: transparent;
            border-radius: 0;
            color: var(--blue);
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            justify-content: center;
            margin-bottom: 10px;
            min-height: auto;
            padding: 0;
            width: max-content;
        }
        .survey-title-row {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .survey-title-row h1,
        .survey-content h1 {
            font-family: var(--font-display);
            font-size: clamp(20px, 1.45vw, 23px);
            line-height: 1.08;
            margin: 0 0 8px;
            white-space: nowrap;
        }
        .survey-title-icon {
            color: var(--ink);
            flex: 0 0 auto;
            height: 24px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.9;
            width: 24px;
            fill: none;
        }
        .survey-lead {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
            margin: 0 0 16px;
            max-width: 380px;
        }
        .survey-form {
            display: grid;
            gap: 12px;
        }
        .survey-form-row {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, 150px);
        }
        .visit-form {
            grid-template-columns: repeat(2, 150px);
        }
        .visit-form .survey-form-row {
            display: contents;
        }
        .survey-field {
            margin: 0;
        }
        .survey-field label {
            color: var(--ink);
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .required-dot {
            color: #d93612;
            margin-left: 6px;
        }
        .survey-input-shell {
            align-items: center;
            background: rgba(255,255,255,.92);
            border: 1px solid #101827;
            border-radius: 8px;
            display: grid;
            grid-template-columns: 1fr;
            height: 32px;
            max-width: 150px;
            padding: 0 10px;
            transition: border-color .2s ease, box-shadow .2s ease;
            width: 150px;
        }
        .survey-input-shell:focus-within,
        .survey-input-shell:hover {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .12);
        }
        .survey-input-shell input {
            background: transparent;
            border: 0;
            color: var(--ink);
            font-size: 12px;
            height: 30px;
            min-width: 0;
            padding: 0;
            text-align: center;
        }
        .survey-input-shell input:focus {
            outline: 0;
        }
        .survey-field-icon {
            color: var(--ink);
            height: 22px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 22px;
            fill: none;
        }
        .survey-visual {
            background: var(--cafe-photo);
            background-position: center;
            background-size: cover;
            min-height: 226px;
            position: relative;
            z-index: 0;
        }
        .survey-visual::before {
            background: linear-gradient(90deg, rgba(255,255,255,.18), rgba(255,255,255,0) 42%);
            content: "";
            inset: 0;
            position: absolute;
        }
        .survey-visual::after { display: none; }
        .survey-button {
            border: 1px solid #101827;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 32px;
            justify-content: center;
            margin-top: 2px;
            min-width: 88px;
            padding: 0 13px;
            width: max-content;
        }
        .visit-submit {
            grid-column: 2;
            justify-self: end;
            transform: translateX(34px);
        }
        .survey-button svg {
            height: 14px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.2;
            width: 14px;
        }
        .survey-choice-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .survey-choice {
            align-items: center;
            background: rgba(255,255,255,.92);
            border: 1px solid #c8d2d9;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 8px;
            min-height: 32px;
            padding: 0 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .survey-choice:hover,
        .survey-choice:focus-within {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .12);
        }
        .survey-choice input {
            accent-color: var(--orange);
            margin: 0;
        }
        .survey-textarea {
            background: rgba(255,255,255,.92);
            border: 1px solid #c8d2d9;
            border-radius: 8px;
            min-height: 74px;
            padding: 10px 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .survey-textarea:hover,
        .survey-textarea:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(223, 116, 13, .12);
            outline: 0;
        }
        .survey-rating-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .survey-rating-grid .slider-field {
            margin: 0;
            padding: 11px;
        }
        .survey-rating-grid .slider-heading {
            margin-bottom: 7px;
        }
        .survey-rating-grid label {
            font-size: 12px;
            line-height: 1.3;
            margin-bottom: 0;
        }
        .survey-rating-grid .slider-value {
            font-size: 12px;
            height: 28px;
            min-width: 38px;
        }
        .survey-rating-grid .range-scale {
            font-size: 10px;
        }
        .survey-card.no-visual.order-survey-card,
        .survey-card.no-visual.staff-survey-card,
        .survey-card.no-visual.food-survey-card,
        .survey-card.no-visual.final-survey-card {
            justify-self: center;
            max-width: 440px;
            width: min(440px, 100%);
        }
        .order-survey-shell,
        .staff-survey-shell,
        .food-survey-shell,
        .final-survey-shell {
            gap: 14px;
            grid-template-columns: 188px minmax(0, 440px);
            width: min(642px, 100%);
        }
        .order-survey-card {
            min-height: 318px;
        }
        .order-survey-steps {
            gap: 13px;
        }
        .order-survey-steps::before {
            background: rgba(83, 111, 154, .32);
            bottom: 16px;
            left: 17px;
            top: 16px;
        }
        .order-survey-steps .survey-step {
            grid-template-columns: 20px 1fr;
        }
        .order-survey-steps .survey-step-number {
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            color: var(--muted);
            height: 20px;
            width: 20px;
        }
        .order-survey-steps .survey-step.active .survey-step-number {
            background: transparent;
            border-color: transparent;
            color: var(--orange);
        }
        .order-survey-card .survey-content {
            padding: 24px 30px 22px;
        }
        .order-survey-card .survey-form {
            gap: 14px;
        }
        .survey-title-icon.soft-icon {
            background: #eef3f9;
            border-radius: 50%;
            height: 40px;
            padding: 9px;
            width: 40px;
        }
        .order-type-choice {
            flex-wrap: nowrap;
            gap: 7px;
            height: 34px;
            justify-content: flex-start;
            min-width: 128px;
            padding: 0 10px;
            white-space: nowrap;
            width: 128px;
        }
        .order-type-choice svg {
            fill: none;
            flex: 0 0 auto;
            height: 17px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.9;
            width: 17px;
        }
        .order-survey-card .survey-textarea {
            min-height: 70px;
            resize: vertical;
            width: 100%;
        }
        .order-survey-card .survey-title-row,
        .staff-survey-card .survey-title-row,
        .food-survey-card .survey-title-row,
        .final-survey-card .survey-title-row {
            align-items: flex-start;
        }
        .order-survey-card .survey-lead,
        .staff-survey-card .survey-lead,
        .food-survey-card .survey-lead,
        .final-survey-card .survey-lead {
            font-size: 12px;
            line-height: 1.42;
            margin-bottom: 10px;
            max-width: 405px;
        }
        .survey-actions {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            width: 100%;
        }
        .survey-error-message {
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, .34);
            border-radius: 8px;
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
            padding: 9px 10px;
        }
        .survey-error-message span {
            color: #536887;
            display: block;
            font-weight: 600;
            margin-top: 2px;
        }
        .survey-back-link {
            align-items: center;
            color: #3f5578;
            display: inline-flex;
            font-size: 13px;
            font-weight: 600;
            gap: 8px;
            min-height: 32px;
            text-decoration: none;
            transition: color .2s ease;
        }
        .survey-back-link:hover {
            color: #000;
        }
        .survey-back-link svg {
            fill: none;
            height: 16px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            width: 16px;
        }
        .staff-survey-card,
        .food-survey-card {
            height: 455px;
            min-height: 455px;
        }
        .staff-survey-card .survey-content {
            padding: 16px 24px 12px;
        }
        .staff-survey-card .survey-title-icon {
            height: 24px;
            width: 24px;
        }
        .food-survey-card .survey-content {
            padding: 16px 24px 12px;
        }
        .food-survey-card .survey-title-icon {
            height: 24px;
            width: 24px;
        }
        .food-survey-card .survey-form {
            gap: 8px;
        }
        .food-survey-card .service-rating-list {
            margin-top: 0;
        }
        .food-rating-list .service-rating-row {
            gap: 8px;
            grid-template-columns: 39px 1fr;
            padding: 2px 0 3px;
        }
        .food-rating-list .service-rating-icon {
            height: 20px;
            stroke-width: 1.7;
            width: 20px;
        }
        .food-rating-list .service-rating-main {
            gap: 1px;
        }
        .food-rating-list .slider-heading {
            gap: 8px;
            margin-bottom: 0;
        }
        .food-rating-list .slider-heading label {
            color: var(--ink);
            font-size: 9.2px;
            font-weight: 700;
            line-height: 1.08;
        }
        .food-rating-list .slider-value {
            font-size: 12px;
        }
        .food-rating-list .smooth-range {
            height: 10px;
        }
        .food-rating-list .range-scale {
            font-size: 8.8px;
            line-height: 1.1;
        }
        .food-survey-card .survey-actions {
            margin-top: 4px;
        }
        .final-survey-card {
            min-height: 384px;
        }
        .final-survey-card .survey-content {
            align-content: center;
            padding: 19px 24px 16px;
        }
        .final-survey-card .survey-title-icon {
            height: 24px;
            width: 24px;
        }
        .final-survey-card .survey-form {
            gap: 9px;
        }
        .final-question-list {
            display: grid;
            gap: 10px;
        }
        .final-question {
            display: grid;
            gap: 7px;
        }
        .final-question-label {
            display: block;
            line-height: 1.2;
            margin-bottom: 0;
        }
        .final-thumb-row {
            align-items: center;
            display: flex;
            gap: 40px;
            line-height: 1;
            min-height: 30px;
        }
        .final-thumb-choice {
            align-items: center;
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            justify-content: center;
            min-height: 30px;
            position: relative;
            transition: color .18s ease;
            width: 32px;
        }
        .final-thumb-choice:hover,
        .final-thumb-choice:focus-within {
            color: var(--ink);
        }
        .final-thumb-choice input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }
        .final-thumb-icon {
            align-items: center;
            color: inherit;
            display: inline-flex;
            height: 30px;
            justify-content: center;
            width: 30px;
        }
        .final-thumb-icon svg {
            display: block;
            fill: none;
            height: 25px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.6;
            width: 25px;
        }
        .final-thumb-icon .thumb-fill {
            fill: transparent;
            transition: fill .18s ease;
        }
        .final-thumb-choice input:checked + .final-thumb-icon {
            color: var(--orange);
        }
        .final-thumb-choice input:checked + .final-thumb-icon .thumb-fill {
            fill: currentColor;
        }
        .final-radio-row {
            align-items: center;
            display: flex;
            gap: 34px;
            min-height: 24px;
        }
        .final-radio-choice {
            align-items: center;
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 600;
            gap: 9px;
        }
        .final-radio-choice input {
            accent-color: var(--orange);
            height: 18px;
            margin: 0;
            width: 18px;
        }
        .final-survey-card .survey-textarea {
            min-height: 64px;
            width: 100%;
        }
        .final-survey-card .survey-actions {
            margin-top: 8px;
        }
        .final-submit-button {
            min-width: 146px;
        }
        .service-rating-list {
            border-top: 1px solid rgba(216, 225, 238, .88);
            display: grid;
        }
        .service-rating-row {
            border-bottom: 1px solid rgba(216, 225, 238, .88);
            display: grid;
            gap: 13px;
            grid-template-columns: 39px 1fr;
            padding: 10px 0 11px;
        }
        .staff-survey-card .service-rating-row {
            gap: 10px;
            padding: 6px 0 7px;
        }
        .service-rating-icon {
            align-self: center;
            color: var(--ink);
            fill: none;
            height: 26px;
            justify-self: center;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            overflow: visible;
            width: 26px;
        }
        .staff-survey-card .service-rating-icon {
            height: 23px;
            stroke-width: 1.85;
            width: 23px;
        }
        .service-rating-main {
            display: grid;
            gap: 5px;
        }
        .staff-survey-card .service-rating-main {
            gap: 3px;
        }
        .service-rating-list .slider-heading {
            align-items: start;
            gap: 12px;
            margin-bottom: 0;
        }
        .staff-survey-card .service-rating-list .slider-heading {
            gap: 10px;
        }
        .service-rating-list .slider-heading strong {
            color: var(--ink);
            display: block;
            font-size: 12.5px;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .staff-survey-card .service-rating-list .slider-heading strong {
            font-size: 12px;
            margin-bottom: 2px;
        }
        .service-rating-list .slider-heading label {
            color: var(--muted);
            display: block;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.28;
            margin: 0;
        }
        .staff-survey-card .service-rating-list .slider-heading label {
            font-size: 10.5px;
            line-height: 1.2;
        }
        .service-rating-list .slider-value {
            background: transparent;
            border-radius: 0;
            color: var(--ink);
            font-size: 14px;
            height: auto;
            min-width: auto;
            padding: 0;
        }
        .staff-survey-card .service-rating-list .range-scale {
            font-size: 10px;
            line-height: 1.15;
        }
        .service-rating-list .range-control {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            border: 0;
            border-radius: 999px;
            cursor: pointer;
            display: block;
            height: 18px;
            inset: 0;
            margin: 0;
            opacity: 0;
            padding: 0;
            position: absolute;
            width: 100%;
            z-index: 3;
        }
        .service-rating-list .range-control::-webkit-slider-runnable-track {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            border: 0;
            height: 2px;
        }
        .service-rating-list .range-control::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            background: #24588f;
            border: 0;
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(7, 24, 58, .2);
            height: 11px;
            margin-top: -4.5px;
            width: 11px;
        }
        .service-rating-list .range-control::-moz-range-track {
            background: transparent;
            border: 0;
            height: 2px;
        }
        .service-rating-list .range-control::-moz-range-thumb {
            background: #24588f;
            border: 0;
            border-radius: 50%;
            height: 11px;
            width: 11px;
        }
        .service-rating-list .range-control:focus {
            outline: 0;
        }
        .service-rating-list .range-control:focus::-webkit-slider-thumb {
            box-shadow: 0 0 0 3px rgba(36, 88, 143, .16);
        }
        .smooth-range {
            --range-progress: 44.44%;
            align-items: center;
            display: flex;
            height: 18px;
            position: relative;
            width: 100%;
        }
        .smooth-range-track,
        .smooth-range-fill {
            border-radius: 999px;
            height: 2px;
            left: 0;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        .smooth-range-track {
            background: #d8dde4;
            right: 0;
        }
        .smooth-range-fill {
            background: #24588f;
            transition: width .08s ease-out;
            width: var(--range-progress);
        }
        .smooth-range:not(.is-selected) .smooth-range-fill,
        .smooth-range:not(.is-selected) .smooth-range-thumb {
            background: #9fafc4;
        }
        .smooth-range.is-disabled {
            opacity: .5;
        }
        .smooth-range-thumb {
            background: #24588f;
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(7, 24, 58, .22);
            height: 10px;
            left: var(--range-progress);
            pointer-events: none;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            transition: left .08s ease-out;
            width: 10px;
            z-index: 2;
        }
        .service-rating-list .range-scale {
            font-size: 11px;
            margin-top: 0;
        }
        .rating-na-choice {
            align-items: center;
            color: #536887;
            cursor: pointer;
            display: inline-flex;
            font-size: 10.5px;
            font-weight: 600;
            gap: 7px;
            line-height: 1.2;
            margin-top: 3px;
        }
        .rating-na-choice input {
            accent-color: var(--orange);
            height: 14px;
            margin: 0;
            width: 14px;
        }
        .staff-survey-card .smooth-range {
            height: 14px;
        }
        .staff-survey-card .service-rating-list .range-scale {
            font-size: 10px;
            line-height: 1.15;
        }
        .staff-survey-card .survey-actions {
            margin-top: 8px;
        }
        .thank-you-card {
            align-items: center;
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(216, 225, 238, .92);
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(7, 24, 58, .16);
            display: grid;
            justify-items: center;
            max-width: 560px;
            min-height: 382px;
            overflow: hidden;
            padding: 24px 38px 24px;
            position: relative;
            text-align: center;
            width: min(560px, 100%);
        }
        .thank-confetti-canvas {
            inset: 0;
            pointer-events: none;
            position: absolute;
            z-index: 3;
        }
        .thank-you-burst,
        .thank-you-copy,
        .thank-you-points,
        .thank-home-button {
            position: relative;
            z-index: 2;
        }
        .thank-you-burst {
            height: 112px;
            margin-bottom: 0;
            position: relative;
            width: 330px;
            z-index: 4;
        }
        .thank-check {
            align-items: center;
            animation: thankCheckPop .65s cubic-bezier(.18, 1.35, .3, 1) both;
            background: linear-gradient(135deg, #22b78f, #0aa57f);
            border-radius: 50%;
            box-shadow: 0 12px 24px rgba(10, 165, 127, .22);
            color: #fff;
            display: inline-flex;
            height: 70px;
            justify-content: center;
            left: 50%;
            position: absolute;
            top: 50px;
            transform: translate(-50%, -50%);
            width: 70px;
            z-index: 2;
        }
        .thank-check svg {
            fill: none;
            height: 40px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.7;
            width: 40px;
        }
        .thank-you-copy h1 {
            color: var(--ink);
            font-family: var(--font-display);
            font-size: clamp(34px, 3.6vw, 48px);
            line-height: 1;
            margin: 0 0 10px;
        }
        .thank-you-main {
            color: var(--ink);
            font-size: 18px;
            line-height: 1.32;
            margin: 0 0 10px;
        }
        .thank-you-sub {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.35;
            margin: 0;
        }
        .thank-you-points {
            align-items: start;
            display: grid;
            gap: 0;
            grid-template-columns: repeat(3, 1fr);
            margin: 18px 0 18px;
            width: 100%;
        }
        .thank-you-point {
            align-items: center;
            color: var(--ink);
            display: grid;
            font-size: 14px;
            gap: 8px;
            justify-items: center;
            line-height: 1.2;
            min-height: 70px;
            padding: 0 14px;
        }
        .thank-you-point + .thank-you-point {
            border-left: 1px solid rgba(83, 111, 154, .2);
        }
        .thank-you-point svg {
            color: var(--ink);
            fill: none;
            height: 32px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.9;
            width: 32px;
        }
        .thank-you-point .thank-heart-fill {
            fill: rgba(255, 110, 31, .3);
            stroke-width: 0;
            transform: translate(2px, -1px) scale(.78);
            transform-origin: center;
        }
        .thank-home-button {
            border: 1px solid transparent;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 34px;
            justify-content: center;
            min-width: 132px;
            padding: 0 14px;
        }
        .thank-home-button svg {
            fill: none;
            height: 14px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.2;
            width: 14px;
        }
        @keyframes thankCheckPop {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(.72);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .thank-check {
                animation: none;
            }
        }
        @media (max-width: 640px) {
            .thank-you-card {
                border-radius: 16px;
                min-height: auto;
                padding: 28px 18px 24px;
            }
            .thank-you-burst {
                transform: scale(.78);
                transform-origin: center top;
                margin-bottom: -18px;
            }
            .thank-you-copy h1 {
                font-size: 34px;
            }
            .thank-you-main {
                font-size: 16px;
            }
            .thank-you-points {
                grid-template-columns: 1fr;
                margin: 20px 0;
            }
            .thank-you-point {
                min-height: auto;
                padding: 12px 0;
            }
            .thank-you-point + .thank-you-point {
                border-left: 0;
                border-top: 1px solid rgba(83, 111, 154, .2);
            }
        }
        .terms-card {
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(216, 225, 238, .92);
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(7, 24, 58, .16);
            display: grid;
            grid-template-columns: 140px minmax(0, 1fr);
            min-height: 360px;
            overflow: hidden;
            padding: 20px;
            position: relative;
            width: min(580px, 100%);
        }
        .terms-close {
            background: transparent;
            border: 0;
            color: #51637f;
            cursor: pointer;
            font: inherit;
            font-size: 16px;
            font-weight: 600;
            line-height: 1;
            padding: 0;
            position: absolute;
            right: 14px;
            top: 12px;
            transition: color .18s ease;
            z-index: 3;
        }
        .terms-close:hover,
        .terms-close:focus-visible {
            color: var(--ink);
            outline: 0;
        }
        .terms-nav {
            border-right: 1px solid rgba(83, 111, 154, .22);
            display: grid;
            gap: 8px;
            max-height: 320px;
            overflow-y: auto;
            padding: 4px 10px 4px 0;
        }
        .terms-card.is-info-only .terms-content {
            grid-template-rows: auto 1fr;
        }
        .terms-card.is-info-only .terms-panels {
            padding-bottom: 0;
        }
        .terms-tab {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 7px;
            color: #536887;
            cursor: pointer;
            display: grid;
            font: inherit;
            font-size: 11px;
            font-weight: 500;
            gap: 7px;
            grid-template-columns: 20px 1fr;
            min-height: 42px;
            padding: 0 8px;
            position: relative;
            text-align: left;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }
        .terms-tab::before {
            background: var(--orange);
            border-radius: 999px;
            bottom: 8px;
            content: "";
            left: 0;
            opacity: 0;
            position: absolute;
            top: 8px;
            transition: opacity .2s ease;
            width: 3px;
        }
        .terms-tab:hover,
        .terms-tab:focus-visible {
            background: rgba(255, 245, 233, .72);
            color: var(--ink);
            outline: 0;
        }
        .terms-tab.is-active {
            background: rgba(255, 245, 233, .9);
            color: var(--ink);
            font-weight: 700;
        }
        .terms-tab.is-active::before {
            opacity: 1;
        }
        .terms-tab svg {
            fill: none;
            height: 21px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
            width: 21px;
        }
        .terms-content {
            display: grid;
            grid-template-rows: auto 1fr auto;
            min-width: 0;
            padding: 3px 0 2px 22px;
        }
        .terms-heading {
            border-bottom: 1px solid rgba(83, 111, 154, .22);
            padding-bottom: 13px;
        }
        .terms-heading h1 {
            color: var(--ink);
            font-family: var(--font-display);
            font-size: clamp(30px, 3.4vw, 40px);
            line-height: 1;
            margin: 0 0 10px;
        }
        .terms-heading p {
            color: var(--muted);
            font-size: 13.5px;
            line-height: 1.35;
            margin: 0;
            max-width: 390px;
        }
        .terms-panels {
            min-height: 150px;
            overflow: hidden;
            padding: 18px 0 6px;
            position: relative;
        }
        .terms-panel {
            display: none;
        }
        .terms-panel.is-active {
            animation: termsPanelIn .22s ease both;
            display: block;
        }
        .terms-panel h2 {
            color: var(--ink);
            font-family: var(--font-display);
            font-size: 22px;
            line-height: 1.1;
            margin: 0 0 10px;
        }
        .terms-panel p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.42;
            margin: 0 0 10px;
            max-width: 390px;
        }
        .terms-actions {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding-top: 4px;
        }
        .terms-agree {
            align-items: center;
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 600;
            gap: 9px;
            line-height: 1.25;
        }
        .terms-agree input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }
        .terms-agree span {
            background: #fff;
            border: 2px solid #a7b4c8;
            border-radius: 5px;
            display: inline-block;
            flex: 0 0 auto;
            height: 19px;
            position: relative;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
            width: 19px;
        }
        .terms-agree span::after {
            border: solid #fff;
            border-width: 0 2px 2px 0;
            content: "";
            height: 8px;
            left: 6px;
            opacity: 0;
            position: absolute;
            top: 2px;
            transform: rotate(45deg);
            width: 4px;
        }
        .terms-agree input:checked + span {
            background: var(--orange);
            border-color: var(--orange);
        }
        .terms-agree input:checked + span::after {
            opacity: 1;
        }
        .terms-card.needs-agreement .terms-agree span {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .13);
        }
        .terms-next-button {
            border: 1px solid transparent;
            border-radius: 8px;
            box-shadow: none;
            font-size: 12px;
            height: 34px;
            justify-content: center;
            min-width: 150px;
            padding: 0 14px;
            white-space: nowrap;
        }
        .terms-next-button svg {
            fill: none;
            height: 14px;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.2;
            width: 14px;
        }
        @keyframes termsPanelIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 760px) {
            .terms-card {
                grid-template-columns: 1fr;
                min-height: auto;
                padding: 18px;
                width: min(100%, 500px);
            }
            .terms-nav {
                border-bottom: 1px solid rgba(83, 111, 154, .22);
                border-right: 0;
                gap: 8px;
                grid-template-columns: 1fr;
                padding: 0 0 16px;
            }
            .terms-tab {
                min-height: 44px;
            }
            .terms-content {
                padding: 22px 0 0;
            }
            .terms-heading h1 {
                font-size: 34px;
            }
            .terms-panels {
                min-height: 0;
                padding: 18px 0 8px;
            }
            .terms-actions {
                align-items: stretch;
                flex-direction: column;
            }
            .terms-next-button {
                width: 100%;
            }
        }
        .footer {
            background: linear-gradient(135deg, #08213d, #17395d);
            color: #fff;
            margin-top: 10px;
            padding: 12px max(24px, calc((100vw - 1500px) / 2));
        }
        .footer-inner {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .footer-brand {
            align-items: center;
            display: flex;
            gap: 14px;
        }
        .footer-tagline {
            opacity: .85;
        }
        .footer-links {
            display: flex;
            gap: clamp(14px, 2vw, 30px);
            flex-wrap: wrap;
        }
        .footer-links a {
            color: rgba(255,255,255,.9);
            font-size: 14px;
            padding: 4px 0;
            position: relative;
            transition: color .2s ease;
        }
        .footer-links a::after {
            background: var(--orange);
            border-radius: 999px;
            bottom: 0;
            content: "";
            height: 2px;
            left: 0;
            position: absolute;
            right: 0;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .2s ease;
        }
        .footer-links a:hover {
            color: #fff;
        }
        .footer-links a:hover::after,
        .footer-links a.active::after {
            transform: scaleX(1);
        }
        @media (max-width: 900px) {
            body.login-screen {
                height: auto;
                overflow: auto;
            }
            .hero { grid-template-columns: 1fr; }
            .hero-visual { min-height: 285px; }
            .login-stage {
                grid-template-columns: 1fr;
                justify-items: center;
            }
            .login-showcase {
                grid-template-columns: 1fr;
                max-width: 720px;
            }
            .login-showcase-art {
                min-height: 300px;
            }
            .login-feature-row {
                grid-template-columns: 1fr;
                gap: 14px;
                margin-top: 0;
                transform: none;
            }
            .login-feature {
                border-left: 0 !important;
                padding: 0;
            }
            .feature-row {
                grid-template-columns: 1fr;
                gap: 13px;
                max-width: 520px;
            }
            .feature {
                border-left: 0 !important;
                padding: 0 8px;
            }
            .admin-workspace {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .admin-sidebar {
                border-radius: 8px 8px 0 0;
                padding: 16px;
            }
            .admin-side-brand {
                margin-bottom: 14px;
            }
            .admin-side-nav {
                grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
            }
            .admin-main {
                border-left: 1px solid rgba(216, 225, 238, .96);
                border-radius: 0 0 8px 8px;
            }
            .business-profile-grid {
                grid-template-columns: 1fr;
            }
            .business-logo-field {
                max-width: 320px;
            }
            .business-fields-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
                max-width: none;
            }
            .dashboard-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .questions-head {
                align-items: flex-start;
                flex-direction: column;
            }
            .questions-add-button {
                margin-top: 0;
            }
            .feedback-kpi-grid,
            .feedback-chart-grid,
            .dashboard-feedback-charts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .feedback-chart-wide,
            .feedback-chart-line {
                grid-column: span 2;
            }
            .dashboard-grid,
            .dashboard-bottom-grid,
            .rating-layout {
                grid-template-columns: 1fr;
            }
            .rating-divider {
                display: none;
            }
            .survey-shell {
                grid-template-columns: 1fr;
                gap: 18px;
                width: min(720px, 100%);
            }
            .survey-steps {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 8px;
                width: 100%;
            }
            .survey-steps::before {
                display: none;
            }
            .survey-step {
                gap: 8px;
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
            }
            .order-survey-steps .survey-step {
                grid-template-columns: 1fr;
            }
            .survey-step-number { height: 20px; width: 20px; }
            .survey-step strong {
                font-size: 12px;
            }
            .survey-step span:last-child {
                display: none;
            }
            .survey-card {
                grid-template-columns: 1fr;
            }
            .survey-visual {
                display: none;
            }
        }
        @media (max-width: 640px) {
            .topbar {
                align-items: center;
                flex-direction: column;
                padding: 8px 14px;
            }
            .brand { font-size: 28px; }
            .brand-emblem {
                height: 34px;
                width: 64px;
            }
            .nav {
                justify-content: center;
                gap: 8px 14px;
            }
            .nav a, .nav button { font-size: 12px; min-height: 28px; }
            .nav-icon { height: 17px; width: 17px; }
            .topbar-tools {
                flex-wrap: wrap;
                justify-content: center;
                margin-left: 0;
                width: 100%;
            }
            .top-search {
                width: min(100%, 260px);
            }
            .notification-popover {
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                width: min(304px, calc(100vw - 24px));
            }
            .notification-popover::before {
                right: 50%;
                transform: translateX(50%) rotate(45deg);
            }
            .profile-name {
                display: none;
            }
            .container {
                margin: 14px auto;
                width: min(100% - 22px, 1160px);
            }
            .panel { padding: 18px; }
            .app-shell, .hero { grid-template-columns: 1fr; }
            .dashboard-head { grid-template-columns: 1fr; }
            .admin-grid { grid-template-columns: 1fr; }
            .login-grid { grid-template-columns: 1fr; }
            .login-panel { transform: none; }
            .login-showcase-copy h1 {
                font-size: clamp(34px, 12vw, 48px);
            }
            .login-showcase-art {
                min-height: 250px;
            }
            .login-mini-card {
                left: 50%;
                top: 20px;
                width: min(280px, calc(100% - 24px));
            }
            .sidebar { min-height: auto; }
            .admin-sidebar {
                padding: 14px;
            }
            .admin-side-nav {
                grid-template-columns: 1fr;
            }
            .admin-main {
                padding: 14px;
            }
            .questions-panel {
                padding: 20px 16px;
            }
            .business-profile-panel {
                padding: 22px 16px 20px;
            }
            .business-profile-head {
                margin-bottom: 20px;
            }
            .business-fields-grid {
                grid-template-columns: 1fr;
            }
            .business-logo-field {
                max-width: none;
            }
            .business-logo-upload {
                height: 190px;
            }
            .business-input-shell {
                min-height: 40px;
            }
            .business-input-shell input,
            .business-input-shell select {
                height: 38px;
            }
            .questions-actions {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }
            .questions-action {
                min-width: 76px;
            }
            .question-modal {
                padding: 22px 18px 18px;
            }
            .question-modal-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
            .dashboard-top {
                align-items: flex-start;
                flex-direction: column;
            }
            .dashboard-tools {
                justify-content: flex-start;
                width: 100%;
            }
            .date-filter,
            .dashboard-export {
                width: 100%;
            }
            .dashboard-card-grid,
            .metric-summary-strip,
            .quick-grid,
            .feedback-kpi-grid,
            .feedback-chart-grid,
            .dashboard-feedback-charts {
                grid-template-columns: 1fr;
            }
            .feedback-chart-wide,
            .feedback-chart-line {
                grid-column: auto;
            }
            .feedback-dashboard-head,
            .feedback-table-head {
                align-items: flex-start;
                flex-direction: column;
            }
            .dashboard-card {
                min-height: 94px;
                padding: 15px;
            }
            .metric-detail-head {
                flex-direction: column;
            }
            .metric-two-col {
                grid-template-columns: 1fr;
            }
            .metric-two-col .rating-divider {
                min-height: 1px;
            }
            .notification-row {
                grid-template-columns: 12px 1fr;
            }
            .notification-time {
                grid-column: 2;
            }
            .hero-copy { padding: 18px; }
            .hero h1 {
                font-size: clamp(32px, 10vw, 44px);
                letter-spacing: 0;
            }
            .eyebrow { letter-spacing: 3px; font-size: 10px; margin-bottom: 12px; }
            .hero-visual { min-height: 225px; }
            .hero-feedback-card {
                right: 14px;
                top: 24px;
                width: min(280px, calc(100% - 28px));
                padding: 16px;
            }
            .face-row { gap: 8px; }
            .face { height: 34px; width: 34px; font-size: 18px; }
            .feature-row { margin-top: 18px; }
            .login-splash, .login-form { padding: 28px; }
            .button {
                justify-content: center;
                width: 100%;
            }
            .hero-cta {
                width: auto;
                min-width: 124px;
            }
            .consent-option {
                max-width: none;
                width: 100%;
            }
            body.feedback-screen .container {
                padding: 16px 0;
                width: min(100% - 22px, 1160px);
            }
            .survey-steps {
                gap: 5px;
            }
            .survey-step-number { font-size: 13px; height: 20px; width: 20px; }
            .survey-step strong {
                font-size: 10px;
            }
            .survey-card,
            .survey-card.no-visual {
                max-width: none;
                min-height: auto;
                height: auto;
            }
            .survey-content {
                padding: 22px 18px;
            }
            .survey-title-row {
                align-items: flex-start;
            }
            .survey-title-row h1,
            .survey-content h1 {
                font-size: 24px;
            }
            .survey-title-icon {
                height: 32px;
                width: 32px;
            }
            .survey-lead {
                font-size: 14px;
                margin-bottom: 18px;
            }
            .survey-form-row,
            .survey-rating-grid {
                grid-template-columns: 1fr;
            }
            .order-survey-card .survey-content,
            .staff-survey-card .survey-content,
            .food-survey-card .survey-content,
            .final-survey-card .survey-content {
                padding: 24px 18px 20px;
            }
            .service-rating-row {
                gap: 12px;
                grid-template-columns: 36px 1fr;
                padding: 14px 0;
            }
            .service-rating-icon {
                height: 26px;
                width: 26px;
            }
            .survey-choice-row {
                display: grid;
                grid-template-columns: 1fr;
            }
            .order-type-choice {
                min-width: 0;
                width: 100%;
            }
            .final-thumb-row {
                gap: 28px;
            }
            .final-radio-row {
                gap: 24px;
            }
            .survey-actions {
                gap: 14px;
            }
            .survey-button {
                width: auto;
            }
            .footer-inner {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }
            .footer-links { gap: 10px 18px; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/workflow.css') }}?v={{ filemtime(public_path('css/workflow.css')) }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/compact-controls.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}">
    @auth<link rel="stylesheet" href="{{ asset('css/notifications.css') }}">@endauth
    <script src="{{ asset('js/keyboard-actions.js') }}" defer></script>
    <script src="{{ asset('js/page-lifecycle.js') }}" defer></script>
    <script src="{{ asset('js/responsive-navigation.js') }}" defer></script>
</head>
@php
    $adminRouteClass = auth()->check() && request()->routeIs(
        'dashboard',
        'dashboard.*',
        'questions.*',
        'business.*',
        'settings.*',
        'messages.*',
        'feedback.index',
        'feedback.detail'
    ) ? 'admin-screen' : '';

    $bodyClass = collect(preg_split('/\s+/', trim($__env->yieldContent('body_class') . ' ' . $adminRouteClass)))
        ->filter()
        ->unique()
        ->implode(' ');
@endphp
<body class="{{ $bodyClass }} {{ auth()->check() ? 'is-authenticated' : '' }}">
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            @include('partials.logo')
            CFS
        </a>
        <button class="navigation-toggle" type="button" aria-label="Open navigation" aria-controls="site-navigation" aria-expanded="false" data-site-menu-toggle><x-icon name="menu" /><x-icon name="x" /></button>
        @if (auth()->check())
            @php
                $adminUser = auth()->user();
                $profileName = optional($adminUser->business)->owner_name ?: ($adminUser->name ?: 'Admin');
                $nameParts = preg_split('/\s+/', trim($profileName));
                $profileInitials = strtoupper(substr($nameParts[0] ?? 'A', 0, 1) . substr($nameParts[1] ?? 'D', 0, 1));
                $profileBusiness = $adminUser->business;
                $profileLogo = optional($profileBusiness)->logo_data
                    ? route('business.logo', ['v' => optional($profileBusiness->updated_at)->timestamp])
                    : optional($profileBusiness)->logo;
                $unreadNotificationCount = $adminUser->notificationCenterItems()->where('is_read', false)->count();
                $searchingQuestions = request()->routeIs('questions.*');
                $searchingMessages = request()->routeIs('messages.*');
                $searchingDashboard = request()->routeIs('dashboard', 'dashboard.section');
                $topSearchRoute = $searchingQuestions ? route('questions.index') : ($searchingMessages ? route('messages.index') : ($searchingDashboard ? url()->current() : route('feedback.index')));
                $topSearchPlaceholder = $searchingQuestions ? 'Search questions...' : ($searchingMessages ? 'Search messages...' : 'Search feedback...');
                $topSearchValue = is_string(request('search')) ? request('search') : '';
            @endphp
            <nav class="nav" id="site-navigation" aria-label="Main navigation">
                <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h5v-6h4v6h5v-9.5"/></svg>
                    Home
                </a>
                <a class="{{ (request()->is('feedback*') || request()->routeIs('feedback.thank-you')) && ! request()->routeIs('feedback.index') && ! request()->routeIs('feedback.detail') ? 'active' : '' }}" href="{{ route('feedback.show') }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M6 4h12v16H6z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>
                    Feedback Form
                </a>
                @if (! $adminRouteClass && request()->routeIs('home', 'feedback.*'))
                <a href="{{ route('dashboard') }}" data-header-dashboard>
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M5 20V12M12 20V5M19 20v-9"></path></svg>
                    Dashboard
                </a>
                @endif
            </nav>
            <div class="topbar-tools">
                <form class="top-search" action="{{ $topSearchRoute }}" method="GET" role="search" aria-label="{{ $searchingQuestions ? 'Search questions' : ($searchingMessages ? 'Search messages' : 'Search submitted feedback') }}" @if ($searchingQuestions) data-suggestions-url="{{ route('questions.suggestions') }}" @endif>
                    @foreach (request()->only(['from', 'to', 'range', 'status', 'period', 'filter', 'unread', 'archived']) as $key => $value)
                        @if (is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <button class="top-search-submit" type="submit" aria-label="Run search" title="Search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg></button>
                    <input name="search" type="search" value="{{ $topSearchValue }}" maxlength="200" aria-label="{{ $topSearchPlaceholder }}" placeholder="{{ $topSearchPlaceholder }}">
                    <button class="top-search-clear" type="button" aria-label="Clear search" title="Clear search" {{ $topSearchValue === '' ? 'hidden' : '' }}><x-icon name="x" /></button>
                </form>
                <div class="notification-menu" data-notification-menu data-notification-icons="{{ asset('icons/lucide') }}" data-notification-url="{{ route('dashboard.notifications') }}" data-notification-read-url="{{ route('dashboard.notifications.read-all') }}" data-notification-csrf="{{ csrf_token() }}">
                    <button class="top-icon" type="button" aria-label="Open notifications" aria-expanded="false" data-notification-trigger>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
                        <span class="top-dot {{ $unreadNotificationCount ? '' : 'is-hidden' }}" data-notification-badge>{{ $unreadNotificationCount }}</span>
                    </button>
                    <div class="notification-popover" role="region" aria-label="Notifications" hidden data-notification-popover>
                        <div class="notification-popover-head">
                            <span class="notification-popover-title">
                                Notifications
                                <span class="notification-count-pill" data-notification-count>{{ $unreadNotificationCount }}</span>
                            </span>
                            <button class="notification-mark-read" type="button" data-notification-read-all>Mark all as read</button>
                            <button class="notification-close" type="button" aria-label="Close notifications" data-notification-close><x-icon name="x" /></button>
                        </div>
                        <div class="notification-list" data-notification-list>
                            <p class="notification-loading">Loading notifications...</p>
                        </div>
                        <a class="notification-footer-link" href="{{ route('dashboard.section', ['section' => 'inbox']) }}">
                            View all notifications
                            <span aria-hidden="true">›</span>
                        </a>
                    </div>
                </div>
                <a class="top-profile" href="{{ route('dashboard') }}" aria-label="Open admin dashboard">
                    <span class="profile-avatar" data-profile-fallback="{{ $profileInitials }}">@if ($profileLogo)<img src="{{ asset($profileLogo) }}" alt="{{ $profileName }} logo" onerror="this.parentElement.textContent=this.parentElement.dataset.profileFallback">@else{{ $profileInitials }}@endif</span>
                    <span class="profile-name">{{ $profileName }}</span>
                </a>
            </div>
        @else
            <nav class="nav" id="site-navigation" aria-label="Main navigation">
                <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h5v-6h4v6h5v-9.5"/></svg>
                    Home
                </a>
                <a class="{{ request()->is('feedback*') || request()->routeIs('feedback.thank-you') ? 'active' : '' }}" href="{{ route('feedback.show') }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M6 4h12v16H6z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>
                    Feedback Form
                </a>
                <a class="{{ request()->routeIs('login', 'password.*') ? 'active' : '' }}" href="{{ route('login') }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.6-4 4.3-6 8-6s6.4 2 8 6"/></svg>
                    Admin Login
                </a>
            </nav>
        @endif
    </header>

    <main class="container">
        @if (session('status') && ! request()->routeIs('password.*'))
            <div class="alert success" role="status" aria-live="polite" data-flash-toast>{{ session('status') }}</div>
        @elseif ($errors->any() && ! $errors->has('submission') && ! request()->routeIs('password.*'))
            <div class="alert error" role="alert" aria-live="assertive" data-flash-toast>
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a class="brand" href="{{ route('home') }}">
                    @include('partials.logo')
                    CFS
                </a>
                <span class="footer-tagline">Your Feedback. A Better Experience.</span>
            </div>
            <div class="footer-links">
                <a class="{{ request()->routeIs('terms') ? 'active' : '' }}" href="{{ route('terms') }}">Terms & Conditions</a>
                <a class="{{ request()->routeIs('privacy') ? 'active' : '' }}" href="{{ route('privacy') }}">Privacy Policy</a>
                <a class="{{ request()->routeIs('faqs') ? 'active' : '' }}" href="{{ route('faqs') }}">FAQs</a>
                <a href="{{ route('contact') }}">Contact Us</a>
            </div>
        </div>
    </footer>
    @auth
        <script src="{{ asset('js/admin-notifications.js') }}?v=20261106-notification-panel-alignment" defer></script>
    @endauth
    <script src="{{ asset('js/admin-live.js') }}?v={{ filemtime(public_path('js/admin-live.js')) }}" defer></script>
    <script src="{{ asset('js/flash-toast.js') }}?v={{ filemtime(public_path('js/flash-toast.js')) }}" defer></script>
    <script src="{{ asset('js/form-submit-feedback.js') }}?v={{ filemtime(public_path('js/form-submit-feedback.js')) }}" defer></script>
    <script src="{{ asset('js/page-motion.js') }}?v={{ filemtime(public_path('js/page-motion.js')) }}" defer></script>
    @auth<script src="{{ asset('js/header-search.js') }}?v={{ filemtime(public_path('js/header-search.js')) }}" defer></script>@endauth
    @auth<script src="{{ asset('js/chart-motion.js') }}?v={{ filemtime(public_path('js/chart-motion.js')) }}" defer></script>@endauth
    <script src="{{ asset('js/survey-form.js') }}" defer></script>
    <script src="{{ asset('js/survey-draft.js') }}" defer
        data-survey-token="{{ session('survey.token') }}"
        data-survey-reset="{{ ! session('survey.agreed') ? '1' : '0' }}"></script>
</body>
</html>
