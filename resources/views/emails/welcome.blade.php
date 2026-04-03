<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{$app_name}}!</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f8f9fa; line-height: 1.6;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8f9fa;">
        <tr>
            <td align="center" style="padding: 20px 0;">

                <!-- Main Email Container -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">

                    <!-- Header Section -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <!-- Logo Placeholder -->
                                        <div style="background-color: rgba(255,255,255,0.2); border-radius: 8px; padding: 15px; display: inline-block; margin-bottom: 20px;">
                                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="40" height="40" rx="8" fill="white"/>
                                                <path d="M20 10L25 15L20 20L15 15L20 10Z" fill="#667eea"/>
                                                <path d="M20 20L25 25L20 30L15 25L20 20Z" fill="#764ba2"/>
                                            </svg>
                                        </div>
                                        <h1 style="color: #ffffff; font-size: 28px; font-weight: 700; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                            Welcome to {{$app_name}}!
                                        </h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px 30px;">

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

                                <!-- Personal Greeting -->
                                <tr>
                                    <td style="padding-bottom: 25px;">
                                        <h2 style="color: #2d3748; font-size: 24px; font-weight: 600; margin: 0 0 10px 0;">
                                            Hi {{name}}! 👋
                                        </h2>
                                        <p style="color: #4a5568; font-size: 16px; margin: 0;">
                                            We're absolutely thrilled to have you join our community! Thank you for signing up and taking the first step towards [brief platform purpose].
                                        </p>
                                    </td>
                                </tr>

                                <!-- Platform Introduction -->
                                <tr>
                                    <td style="padding-bottom: 30px;">
                                        <p style="color: #4a5568; font-size: 16px; margin: 0 0 20px 0;">
                                            {{$app_name}} is designed to help you [main value proposition]. Whether you're looking to [benefit 1] or [benefit 2], we've got you covered with powerful tools and an intuitive interface.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Key Features -->
                                <tr>
                                    <td style="padding-bottom: 30px;">
                                        <h3 style="color: #2d3748; font-size: 18px; font-weight: 600; margin: 0 0 15px 0;">
                                            What you can do:
                                        </h3>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="padding-right: 12px; vertical-align: top;">
                                                                <span style="background-color: #e6fffa; color: #00a896; font-size: 16px; border-radius: 50%; width: 24px; height: 24px; display: inline-block; text-align: center; line-height: 24px; font-weight: bold;">✓</span>
                                                            </td>
                                                            <td style="vertical-align: top;">
                                                                <span style="color: #4a5568; font-size: 15px;">Access powerful analytics and insights to track your progress</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="padding-right: 12px; vertical-align: top;">
                                                                <span style="background-color: #e6fffa; color: #00a896; font-size: 16px; border-radius: 50%; width: 24px; height: 24px; display: inline-block; text-align: center; line-height: 24px; font-weight: bold;">✓</span>
                                                            </td>
                                                            <td style="vertical-align: top;">
                                                                <span style="color: #4a5568; font-size: 15px;">Collaborate seamlessly with your team members</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="padding-right: 12px; vertical-align: top;">
                                                                <span style="background-color: #e6fffa; color: #00a896; font-size: 16px; border-radius: 50%; width: 24px; height: 24px; display: inline-block; text-align: center; line-height: 24px; font-weight: bold;">✓</span>
                                                            </td>
                                                            <td style="vertical-align: top;">
                                                                <span style="color: #4a5568; font-size: 15px;">Customize your workspace to match your unique workflow</span>
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
                                    <td style="padding-bottom: 35px; text-align: center;">
                                        <a href="{{dashboard_url}}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; padding: 14px 32px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); transition: all 0.2s ease;">
                                            Get Started →
                                        </a>
                                    </td>
                                </tr>

                                <!-- Quick Tips -->
                                <tr>
                                    <td style="padding-bottom: 30px;">
                                        <div style="background-color: #f7fafc; border-left: 4px solid #667eea; padding: 20px; border-radius: 0 8px 8px 0;">
                                            <h4 style="color: #2d3748; font-size: 16px; font-weight: 600; margin: 0 0 10px 0;">
                                                💡 Quick Tips to Get Started
                                            </h4>
                                            <ul style="color: #4a5568; font-size: 14px; margin: 0; padding-left: 20px; line-height: 1.6;">
                                                <li>Complete your profile to personalize your experience</li>
                                                <li>Explore our tutorial videos in the help center</li>
                                                <li>Join our community forum to connect with other users</li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Friendly Closing -->
                                <tr>
                                    <td>
                                        <p style="color: #4a5568; font-size: 16px; margin: 0 0 20px 0;">
                                            We're here to help you succeed every step of the way. If you have any questions or need assistance, don't hesitate to reach out to our support team.
                                        </p>
                                        <p style="color: #2d3748; font-size: 16px; margin: 0;">
                                            Welcome aboard!<br>
                                            <strong style="color: #667eea;">The {{$app_name}} Team</strong>
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 15px;">
                                        <p style="color: #718096; font-size: 13px; margin: 0;">
                                            Need help? Contact us at <a href="mailto:support@{{$app_name_lower}}" style="color: #667eea; text-decoration: none;">support@{{$app_name_lower}}</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p style="color: #a0aec0; font-size: 12px; margin: 0;">
                                            © 2024 {{$app_name}}. All rights reserved.<br>
                                            <a href="{{unsubscribe_url}}" style="color: #a0aec0; text-decoration: underline;">Unsubscribe</a> |
                                            <a href="{{privacy_url}}" style="color: #a0aec0; text-decoration: underline;">Privacy Policy</a> |
                                            <a href="{{terms_url}}" style="color: #a0aec0; text-decoration: underline;">Terms of Service</a>
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
