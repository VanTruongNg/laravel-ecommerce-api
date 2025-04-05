<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .welcome {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 30px;
        }
        .message {
            color: #4a5568;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .token-container {
            background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
        }
        .token {
            font-size: 42px;
            letter-spacing: 8px;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .expires {
            margin-top: 30px;
            color: #718096;
            font-size: 14px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            display: inline-block;
        }
        .warning {
            background-color: #fff5f5;
            border-left: 4px solid #fc8181;
            padding: 12px 15px;
            margin-top: 25px;
            text-align: left;
            font-size: 14px;
            color: #c53030;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome">
            Xin chào {{ $user->full_name }}
        </div>
        
        <div class="message">
            Mã đặt lại mật khẩu của bạn là:
        </div>

        <div class="token-container">
            <div class="token">{{ $token->token }}</div>
        </div>

        <div class="expires">
            Mã này sẽ hết hạn vào: {{ $expiresAt->format('H:i d/m/Y') }}
        </div>

        <div class="warning">
            ⚠️ Lưu ý: 
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li>Không chia sẻ mã này với bất kỳ ai</li>
                <li>E-commerce Car không bao giờ yêu cầu mã đặt lại mật khẩu qua điện thoại hoặc email khác</li>
                <li>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này và kiểm tra bảo mật tài khoản của bạn</li>
            </ul>
        </div>

        <div class="footer">
            Đây là email tự động. Vui lòng không trả lời email này.<br>
            &copy; {{ date('Y') }} E-commerce Car. All rights reserved.
        </div>
    </div>
</body>
</html>