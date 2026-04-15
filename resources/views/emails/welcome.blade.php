<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>مرحبًا بك في {{$app_name}}!</title>
    <style>
        /* ============================
           COLOR SCHEME SUPPORT
           ============================ */
        :root {
            color-scheme: light dark;
        }

        /* ============================
           BASE RESETS
           ============================ */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
            line-height: 1.6;
            text-align: right;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        /* ============================
           LIGHT MODE (DEFAULT)
           ============================ */
        .email-body-bg       { background-color: #f0f4f8; }
        .email-container     { background-color: #ffffff; border: 1px solid #d1dde8; }
        .email-header        { background: #1a2d42; border-bottom: 1px solid #2a3f58; }
        .logo-wrap           { background-color: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15); }
        .header-title        { color: #ffffff; }
        .header-subtitle     { color: #b8cfe0; }
        .greeting-title      { color: #1a2d42; }
        .greeting-body       { color: #4a6070; }
        .brand-name          { color: #1a2d42; }
        .intro-highlight     { color: #1a2d42; }
        .features-label      { color: #185fa5; }
        .feature-icon        { background-color: rgba(24,95,165,0.12); color: #185fa5; border: 1px solid rgba(24,95,165,0.25); }
        .feature-text        { color: #4a6070; }
        .cta-btn             { background: #185fa5; color: #ffffff; border: 1px solid #185fa5; box-shadow: 0 4px 14px rgba(24,95,165,0.3); }
        .reminder-box        { background-color: rgba(24,95,165,0.05); border: 1px solid #d1dde8; border-right: 4px solid #185fa5; }
        .reminder-title      { color: #1a2d42; }
        .reminder-body       { color: #4a6070; }
        .quickstart-box      { background-color: rgba(24,95,165,0.05); border: 1px solid #d1dde8; border-right: 4px solid #185fa5; }
        .quickstart-title    { color: #1a2d42; }
        .quickstart-list     { color: #4a6070; }
        .support-text        { color: #1a2d42; }
        .closing-text        { color: #4a6070; }
        .closing-sign        { color: #1a2d42; }
        .closing-brand       { color: #185fa5; }
        .footer-bg           { background-color: #f0f4f8; border-top: 1px solid #d1dde8; }
        .footer-text         { color: #7a93a8; }
        .footer-link         { color: #185fa5; }
        .footer-meta         { color: #7a93a8; }
        .footer-meta-link    { color: #7a93a8; }

        /* ============================
           DARK MODE OVERRIDES
           ============================ */
        @media (prefers-color-scheme: dark) {
            .email-body-bg       { background-color: #0d1b2a !important; }
            .email-container     { background-color: #1a2d42 !important; border: 1px solid #2a3f58 !important; box-shadow: 0 20px 40px rgba(0,0,0,0.5) !important; }
            .email-header        { background: linear-gradient(135deg, #0d1b2a 0%, #1a2d42 100%) !important; border-bottom: 1px solid #2a3f58 !important; }
            .logo-wrap           { background-color: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.1) !important; }
            .header-title        { color: #ffffff !important; }
            .header-subtitle     { color: #9db5cc !important; }
            .greeting-title      { color: #ffffff !important; }
            .greeting-body       { color: #9db5cc !important; }
            .brand-name          { color: #ffffff !important; }
            .intro-highlight     { color: #ffffff !important; }
            .features-label      { color: #4b9be0 !important; }
            .feature-icon        { background-color: rgba(43,108,176,0.2) !important; color: #4b9be0 !important; border: 1px solid rgba(43,108,176,0.3) !important; }
            .feature-text        { color: #9db5cc !important; }
            .cta-btn             { background: #4882be !important; color: #ffffff !important; border: 1px solid #4b9be0 !important; box-shadow: 0 4px 15px rgba(72,130,190,0.4) !important; }
            .reminder-box        { background-color: rgba(43,108,176,0.05) !important; border: 1px solid #2a3f58 !important; border-right: 4px solid #2b6cb0 !important; }
            .reminder-title      { color: #ffffff !important; }
            .reminder-body       { color: #9db5cc !important; }
            .quickstart-box      { background-color: rgba(43,108,176,0.05) !important; border: 1px solid #2a3f58 !important; border-right: 4px solid #2b6cb0 !important; }
            .quickstart-title    { color: #ffffff !important; }
            .quickstart-list     { color: #9db5cc !important; }
            .support-text        { color: #ffffff !important; }
            .closing-text        { color: #9db5cc !important; }
            .closing-sign        { color: #ffffff !important; }
            .closing-brand       { color: #4b9be0 !important; }
            .footer-bg           { background-color: #0d1b2a !important; border-top: 1px solid #2a3f58 !important; }
            .footer-text         { color: #5c7a96 !important; }
            .footer-link         { color: #2b6cb0 !important; }
            .footer-meta         { color: #5c7a96 !important; }
            .footer-meta-link    { color: #5c7a96 !important; }
        }

        /* ============================
           OUTLOOK / MSO DARK MODE FIX
           ============================ */
        [data-ogsc] .email-body-bg       { background-color: #0d1b2a !important; }
        [data-ogsc] .email-container     { background-color: #1a2d42 !important; }
        [data-ogsc] .greeting-title      { color: #ffffff !important; }
        [data-ogsc] .greeting-body       { color: #9db5cc !important; }
        [data-ogsc] .features-label      { color: #4b9be0 !important; }
        [data-ogsc] .feature-text        { color: #9db5cc !important; }
        [data-ogsc] .support-text        { color: #ffffff !important; }
        [data-ogsc] .closing-text        { color: #9db5cc !important; }
        [data-ogsc] .footer-bg           { background-color: #0d1b2a !important; }
        [data-ogsc] .footer-meta         { color: #5c7a96 !important; }

        /* ============================
           FORCE DARK ON LOGO SVG
           ============================ */
        @media (prefers-color-scheme: dark) {
            .logo-diamond-top    { fill: #0d1b2a !important; }
            .logo-diamond-bottom { fill: #4b9be0 !important; }
        }
    </style>
</head>
<body class="email-body-bg" style="margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif; line-height: 1.6; text-align: right;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="email-body-bg" dir="rtl">
        <tr>
            <td align="center" style="padding: 40px 0;">

                <!-- Main Email Container -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="email-container" style="border-radius: 24px; overflow: hidden;">

                    <!-- Header Section -->
                    <tr>
                        <td class="email-header" style="padding: 60px 40px; text-align: center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <!-- Logo Container -->
                                        <div class="logo-wrap" style="border-radius: 12px; padding: 20px; display: inline-block; margin-bottom: 24px;">
                                            <svg width="48" height="48" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="40" height="40" rx="8" fill="white"/>
                                                <path class="logo-diamond-top" d="M20 10L25 15L20 20L15 15L20 10Z" fill="#0d1b2a"/>
                                                <path class="logo-diamond-bottom" d="M20 20L25 25L20 30L15 25L20 20Z" fill="#2b6cb0"/>
                                            </svg>
                                        </div>
                                        <h1 class="header-title" style="font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -0.03em; font-family: 'Montserrat', sans-serif;">
                                            مرحبًا بك في {{$app_name}}!
                                        </h1>
                                        <p class="header-subtitle" style="font-size: 16px; margin-top: 12px; font-weight: 400;">رحلتك نحو النجاح تبدأ من هنا.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 50px 40px;">

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

                                <!-- Personal Greeting -->
                                <tr>
                                    <td style="padding-bottom: 32px; text-align: right;">
                                        <h2 class="greeting-title" style="font-size: 26px; font-weight: 700; margin: 0 0 16px 0; letter-spacing: -0.01em;">
                                            مرحبًا بك، {{$name}}! 👋
                                        </h2>
                                        <p class="greeting-body" style="font-size: 16px; margin: 0; line-height: 1.8;">
                                            نرحّب بك في <strong class="brand-name">The Nova Digital Marketing Services FZC LLC</strong>. انضمامك إلينا ليس مجرد تسجيل… بل بداية خطوة حقيقية نحو التطور، الفهم، وبناء فرص أقوى.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Platform Introduction -->
                                <tr>
                                    <td style="padding-bottom: 40px; text-align: right;">
                                        <p class="intro-highlight" style="font-size: 18px; margin: 0; line-height: 1.8; font-weight: 500;">
                                            ✨ هنا، لن تتعلم فقط… بل ستفهم كيف تتحرك الأمور فعليًا، وكيف تبني طريقك بثقة.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Key Features -->
                                <tr>
                                    <td style="padding-bottom: 40px; text-align: right;">
                                        <h3 class="features-label" style="font-size: 18px; font-weight: 600; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 0.05em;">
                                            🎯 ما ينتظرك:
                                        </h3>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 16px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" dir="rtl">
                                                        <tr>
                                                            <td style="padding-left: 16px; vertical-align: top; width: 28px;">
                                                                <span class="feature-icon" style="font-size: 16px; border-radius: 8px; width: 28px; height: 28px; display: inline-block; text-align: center; line-height: 28px; font-weight: bold;">✓</span>
                                                            </td>
                                                            <td style="vertical-align: top;">
                                                                <span class="feature-text" style="font-size: 15px; font-weight: 500; text-align: right; display: block;">محتوى تعليمي احترافي مبني على خبرة</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 16px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" dir="rtl">
                                                        <tr>
                                                            <td style="padding-left: 16px; vertical-align: top; width: 28px;">
                                                                <span class="feature-icon" style="font-size: 16px; border-radius: 8px; width: 28px; height: 28px; display: inline-block; text-align: center; line-height: 28px; font-weight: bold;">✓</span>
                                                            </td>
                                                            <td style="vertical-align: top;">
                                                                <span class="feature-text" style="font-size: 15px; font-weight: 500; text-align: right; display: block;">تحليل واضح للسوق بطريقة عملية</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" dir="rtl">
                                                        <tr>
                                                            <td style="padding-left: 16px; vertical-align: top; width: 28px;">
                                                                <span class="feature-icon" style="font-size: 16px; border-radius: 8px; width: 28px; height: 28px; display: inline-block; text-align: center; line-height: 28px; font-weight: bold;">✓</span>
                                                            </td>
                                                            <td style="vertical-align: top;">
                                                                <span class="feature-text" style="font-size: 15px; font-weight: 500; text-align: right; display: block;">نظام متكامل يساعدك تتطور خطوة بخطوة</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- CTA Button -->
                                <tr>
                                    <td style="padding-bottom: 40px; text-align: center;">
                                        <a href="https://test.thenovagroupco.com/dashboard" class="cta-btn" style="text-decoration: none; font-size: 18px; font-weight: 700; padding: 16px 50px; border-radius: 12px; display: inline-block; letter-spacing: 0.05em;">
                                            ابدأ الآن
                                        </a>
                                    </td>
                                </tr>

                                <!-- Important Reminder -->
                                <tr>
                                    <td style="padding-bottom: 30px; text-align: right;">
                                        <div class="reminder-box" style="padding: 24px; border-radius: 12px;">
                                            <h4 class="reminder-title" style="font-size: 16px; font-weight: 700; margin: 0 0 10px 0;">
                                                ⚠️ تذكير مهم:
                                            </h4>
                                            <p class="reminder-body" style="font-size: 14px; margin: 0; line-height: 1.6;">
                                                كل ما نقدمه هو لأغراض تعليمية فقط، والنتائج تعتمد بشكل كامل على قراراتك والتزامك.
                                            </p>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Quick Start -->
                                <tr>
                                    <td style="padding-bottom: 40px; text-align: right;">
                                        <div class="quickstart-box" style="padding: 24px; border-radius: 12px;">
                                            <h4 class="quickstart-title" style="font-size: 16px; font-weight: 700; margin: 0 0 12px 0;">
                                                🚀 ابدأ الآن:
                                            </h4>
                                            <ul class="quickstart-list" style="font-size: 14px; margin: 0; padding-right: 20px; line-height: 1.8;">
                                                <li>ادخل إلى حسابك</li>
                                                <li>استكشف الباقة الخاصة بك</li>
                                                <li>وابدأ رحلتك بثقة</li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Support Section -->
                                <tr>
                                    <td style="padding-bottom: 40px; text-align: right;">
                                        <p class="support-text" style="font-size: 16px; margin: 0; line-height: 1.8;">
                                            💬 <strong>فريقنا دائمًا معك:</strong> أي وقت تحتاج دعم أو توجيه… ستجدنا بجانبك.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Final Closing -->
                                <tr>
                                    <td style="text-align: right;">
                                        <p class="closing-text" style="font-size: 18px; margin: 0 0 24px 0; line-height: 1.8; font-weight: 500;">
                                            أنت الآن في المكان الصحيح… ابدأها صح، وكملها للأعلى 🚀
                                        </p>
                                        <p class="closing-sign" style="font-size: 16px; margin: 0;">
                                            مع تحيات،<br>
                                            <strong class="closing-brand">فريق {{$app_name}}</strong>
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer-bg" style="padding: 40px; text-align: center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <p class="footer-text" style="font-size: 13px; margin: 0;">
                                            هل لديك أسئلة؟ تواصل معنا عبر <a href="mailto:support@thenovagroupco.com" class="footer-link" style="text-decoration: none; font-weight: 600;">support@thenovagroupco.com</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p class="footer-meta" style="font-size: 12px; margin: 0; line-height: 1.6;">
                                            &copy; {{ date('Y') }} {{$app_name}}. جميع الحقوق محفوظة.<br>
                                            <a href="#" class="footer-meta-link" style="text-decoration: underline;">إلغاء الاشتراك</a> &bull;
                                            <a href="https://thenovagroupco.com/privacy-policy" class="footer-meta-link" style="text-decoration: underline;">سياسة الخصوصية</a> &bull;
                                            <a href="https://thenovagroupco.com/terms-and-conditions" class="footer-meta-link" style="text-decoration: underline;">شروط الخدمة</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
