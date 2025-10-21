# 🚗 Laravel E-Commerce API - Car Dealership System

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel" alt="Laravel Version"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php" alt="PHP Version"></a>
  <a href="https://github.com/VanTruongNg/laravel-ecommerce-api"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License"></a>
</p>

## 📋 Mục lục

-   [Giới thiệu](#-giới-thiệu)
-   [Tính năng chính](#-tính-năng-chính)
-   [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
-   [Cấu trúc dự án](#-cấu-trúc-dự-án)
-   [Cài đặt](#-cài-đặt)
-   [Cấu hình môi trường](#-cấu-hình-môi-trường)
-   [API Endpoints](#-api-endpoints)
-   [Database Schema](#-database-schema)
-   [Authentication & Authorization](#-authentication--authorization)
-   [Payment Integration](#-payment-integration)
-   [Testing](#-testing)
-   [Deployment](#-deployment)
-   [Contributing](#-contributing)
-   [License](#-license)

## 🎯 Giới thiệu

**Laravel E-Commerce API** là một hệ thống backend RESTful API hoàn chỉnh cho ứng dụng bán xe hơi trực tuyến. Dự án được xây dựng trên Laravel 12, tích hợp JWT authentication, Redis caching, PayOS payment gateway, và AWS S3 storage.

### Điểm nổi bật

-   ✅ RESTful API architecture với response chuẩn
-   ✅ JWT-based authentication với refresh token rotation
-   ✅ Role-based access control (Admin/User)
-   ✅ Shopping cart với real-time inventory management
-   ✅ Order processing với multi-step workflow
-   ✅ Tích hợp PayOS payment gateway
-   ✅ Email verification & password reset
-   ✅ OAuth2 Google login
-   ✅ File upload với AWS S3
-   ✅ Redis caching & session management
-   ✅ Docker containerization

## 🚀 Tính năng chính

### 1. Authentication & User Management

-   Đăng ký tài khoản với email verification
-   Đăng nhập với JWT token (access + refresh)
-   OAuth2 Google authentication
-   Password reset qua email
-   Token refresh & revocation
-   Role-based permissions (Admin/User)

### 2. Car Management

-   CRUD operations cho xe (Admin only)
-   Danh sách xe với pagination, filtering
-   Chi tiết xe với thông tin brand
-   Quản lý inventory (stock, availability)
-   Upload hình ảnh xe lên AWS S3
-   Các loại nhiên liệu: Gasoline, Diesel, Electric, Hybrid

### 3. Brand Management

-   Quản lý thương hiệu xe (Admin only)
-   Danh sách brands
-   Chi tiết brand với danh sách xe

### 4. Shopping Cart

-   Thêm/xóa sản phẩm vào giỏ hàng
-   Cập nhật số lượng
-   Xem giỏ hàng của user
-   Clear cart
-   Real-time inventory validation

### 5. Order Management

-   Tạo đơn hàng từ cart hoặc direct purchase
-   Xem lịch sử đơn hàng
-   Chi tiết đơn hàng với order items
-   Hủy đơn hàng
-   Order status tracking: Pending, Completed, Cancelled
-   Admin dashboard để quản lý tất cả orders

### 6. Payment Processing

-   Tích hợp PayOS payment gateway
-   Tạo payment link cho đơn hàng
-   Kiểm tra trạng thái thanh toán
-   Webhook handling cho payment status
-   Payment status: Pending, Paid, Cancelled

## 🛠 Công nghệ sử dụng

### Backend Framework

-   **Laravel 12** - PHP framework hiện đại
-   **PHP 8.2+** - Latest PHP version với performance improvements

### Authentication & Security

-   **Firebase JWT** - JSON Web Token implementation
-   **Laravel Socialite** - OAuth2 authentication
-   **Redis** - Session & token blacklist management

### Database & Storage

-   **MySQL** - Primary database
-   **Redis** - Caching & session storage
-   **AWS S3** - Cloud file storage

### Payment

-   **PayOS** - Vietnamese payment gateway integration

### Development Tools

-   **Docker** - Containerization (MySQL, Redis, phpMyAdmin, Redis Commander)
-   **Pest PHP** - Modern testing framework
-   **Laravel Pint** - Code style fixer

### Email

-   **Laravel Mail** - Email sending với blade templates
-   **SMTP** - Email delivery configuration

## 📁 Cấu trúc dự án

```
laravel-ecommerce-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php       # Authentication & user management
│   │   │   ├── BrandController.php      # Brand CRUD operations
│   │   │   ├── CarController.php        # Car management
│   │   │   ├── CartController.php       # Shopping cart operations
│   │   │   ├── OrderController.php      # Order processing
│   │   │   └── PaymentController.php    # Payment integration
│   │   └── Middleware/
│   │       ├── JwtMiddleware.php        # JWT token validation
│   │       └── CheckRole.php            # Role-based access control
│   ├── Mail/
│   │   ├── VerificationEmail.php        # Email verification mail
│   │   └── ResetPasswordEmail.php       # Password reset mail
│   ├── Models/
│   │   ├── User.php                     # User model with roles
│   │   ├── Car.php                      # Car model with enums
│   │   ├── Brand.php                    # Brand model
│   │   ├── Cart.php                     # Shopping cart
│   │   ├── CartCar.php                  # Cart items pivot
│   │   ├── Order.php                    # Order model
│   │   ├── OrderDetail.php              # Order items
│   │   ├── Payment.php                  # Payment records
│   │   └── Token.php                    # Verification & reset tokens
│   ├── UploadService/
│   │   └── UploaderService.php          # AWS S3 file upload
│   └── utils/
│       └── Response.php                 # Standardized API responses
├── config/
│   ├── database.php                     # Database connections
│   ├── filesystems.php                  # S3 configuration
│   ├── mail.php                         # Email configuration
│   └── cors.php                         # CORS settings
├── database/
│   ├── migrations/                      # Database schema migrations
│   └── seeders/                         # Database seeders
├── routes/
│   └── api.php                          # API route definitions
├── docker-compose.yml                   # Docker services setup
└── README.md                            # This file
```

## 💻 Cài đặt

### Yêu cầu hệ thống

-   PHP >= 8.2
-   Composer
-   MySQL >= 8.0
-   Redis
-   Node.js & NPM (cho asset compilation)
-   Docker & Docker Compose (optional, recommended)

### Các bước cài đặt

#### 1. Clone repository

```bash
git clone https://github.com/VanTruongNg/laravel-ecommerce-api.git
cd laravel-ecommerce-api
```

#### 2. Cài đặt dependencies

```bash
composer install
npm install
```

#### 3. Tạo file .env

```bash
cp .env.example .env
```

#### 4. Generate application key

```bash
php artisan key:generate
```

#### 5. Khởi động Docker services

```bash
docker-compose up -d
```

Services sẽ chạy trên:

-   **MySQL**: `localhost:3306`
-   **phpMyAdmin**: `http://localhost:8080`
-   **Redis**: `localhost:6379`
-   **Redis Commander**: `http://localhost:8081`

#### 6. Chạy migrations

```bash
php artisan migrate
```

#### 7. (Optional) Chạy seeders

```bash
php artisan db:seed
```

#### 8. Khởi động development server

```bash
php artisan serve
```

API sẽ chạy trên `http://localhost:8000`

## ⚙️ Cấu hình môi trường

### Database Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_commerce
DB_USERNAME=root
DB_PASSWORD=root
```

### Redis Configuration

```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### JWT Configuration

```env
JWT_SECRET=your-secret-key-here
JWT_ACCESS_TOKEN_EXPIRY=900          # 15 minutes
JWT_REFRESH_TOKEN_EXPIRY=604800      # 7 days
```

### AWS S3 Configuration

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### PayOS Configuration

```env
PAYOS_CLIENT_ID=your-client-id
PAYOS_API_KEY=your-api-key
PAYOS_CHECKSUM_KEY=your-checksum-key
FRONTEND_RETURN_URL=http://localhost:3000/payment/success
FRONTEND_CANCEL_URL=http://localhost:3000/payment/cancel
```

### Google OAuth Configuration

```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

## 📡 API Endpoints

### Authentication Endpoints

| Method | Endpoint                              | Description                 | Auth Required |
| ------ | ------------------------------------- | --------------------------- | ------------- |
| POST   | `/api/auth/register`                  | Đăng ký tài khoản mới       | ❌            |
| POST   | `/api/auth/login`                     | Đăng nhập                   | ❌            |
| GET    | `/api/auth/google`                    | Google OAuth login          | ❌            |
| GET    | `/api/auth/google/callback`           | Google OAuth callback       | ❌            |
| POST   | `/api/auth/verify-email/{token}`      | Xác thực email              | ❌            |
| POST   | `/api/auth/resend-verification-email` | Gửi lại email xác thực      | ❌            |
| POST   | `/api/auth/send-reset-password-email` | Gửi email reset password    | ❌            |
| POST   | `/api/auth/reset-password/{token}`    | Reset password              | ❌            |
| POST   | `/api/auth/refresh`                   | Refresh access token        | ❌            |
| GET    | `/api/auth/user`                      | Lấy thông tin user hiện tại | ✅            |
| POST   | `/api/auth/logout`                    | Đăng xuất                   | ✅            |

### Car Endpoints

| Method | Endpoint           | Description                  | Auth Required | Role  |
| ------ | ------------------ | ---------------------------- | ------------- | ----- |
| GET    | `/api/cars`        | Lấy danh sách xe (paginated) | ❌            | -     |
| GET    | `/api/cars/newest` | Lấy xe mới nhất              | ❌            | -     |
| GET    | `/api/cars/{id}`   | Lấy chi tiết xe              | ❌            | -     |
| POST   | `/api/cars`        | Tạo xe mới                   | ✅            | Admin |
| POST   | `/api/cars/{id}`   | Cập nhật xe                  | ✅            | Admin |
| DELETE | `/api/cars/{id}`   | Xóa xe                       | ✅            | Admin |

### Brand Endpoints

| Method | Endpoint           | Description          | Auth Required | Role  |
| ------ | ------------------ | -------------------- | ------------- | ----- |
| GET    | `/api/brands`      | Lấy danh sách brands | ❌            | -     |
| GET    | `/api/brands/{id}` | Lấy chi tiết brand   | ❌            | -     |
| POST   | `/api/brands`      | Tạo brand mới        | ✅            | Admin |
| POST   | `/api/brands/{id}` | Cập nhật brand       | ✅            | Admin |
| DELETE | `/api/brands/{id}` | Xóa brand            | ✅            | Admin |

### Cart Endpoints

| Method | Endpoint           | Description           | Auth Required |
| ------ | ------------------ | --------------------- | ------------- |
| GET    | `/api/cart/me`     | Lấy giỏ hàng của user | ✅            |
| POST   | `/api/cart/add`    | Thêm sản phẩm vào giỏ | ✅            |
| DELETE | `/api/cart/remove` | Xóa sản phẩm khỏi giỏ | ✅            |
| DELETE | `/api/cart/clear`  | Xóa toàn bộ giỏ hàng  | ✅            |

### Order Endpoints

| Method | Endpoint                 | Description         | Auth Required | Role  |
| ------ | ------------------------ | ------------------- | ------------- | ----- |
| GET    | `/api/order`             | Lấy tất cả orders   | ✅            | Admin |
| GET    | `/api/order/me`          | Lấy orders của user | ✅            | User  |
| GET    | `/api/order/{id}`        | Lấy chi tiết order  | ✅            | User  |
| POST   | `/api/order/create`      | Tạo order mới       | ✅            | User  |
| DELETE | `/api/order/cancel/{id}` | Hủy order           | ✅            | User  |

### Payment Endpoints

| Method | Endpoint                                | Description                    | Auth Required |
| ------ | --------------------------------------- | ------------------------------ | ------------- |
| POST   | `/api/payment/create-link`              | Tạo payment link               | ✅            |
| GET    | `/api/payment/check-status/{orderCode}` | Kiểm tra trạng thái thanh toán | ✅            |

### Upload Endpoint

| Method | Endpoint           | Description        | Auth Required |
| ------ | ------------------ | ------------------ | ------------- |
| POST   | `/api/upload/file` | Upload file lên S3 | ✅            |

## 🗄 Database Schema

### Users Table

```sql
- id: uuid (PK)
- full_name: string
- email: string (unique)
- password: string (hashed)
- role: enum('user', 'admin')
- is_email_verified: boolean
- provider: string (nullable)
- provider_id: string (nullable)
- timestamps
```

### Cars Table

```sql
- id: uuid (PK)
- model: string
- year: integer
- color: string
- price: decimal
- brand_id: uuid (FK)
- image_url: string
- stock: integer
- fuel_type: enum('gasoline', 'diesel', 'electric', 'hybrid')
- availability: enum('in_stock', 'pre_order', 'out_of_stock')
- description: text (nullable)
- timestamps
```

### Brands Table

```sql
- id: uuid (PK)
- name: string (unique)
- description: text (nullable)
- logo_url: string (nullable)
- timestamps
```

### Carts Table

```sql
- id: uuid (PK)
- user_id: uuid (FK, unique)
- timestamps
```

### Cart_Car Pivot Table

```sql
- id: uuid (PK)
- cart_id: uuid (FK)
- car_id: uuid (FK)
- quantity: integer
- timestamps
```

### Orders Table

```sql
- id: uuid (PK)
- user_id: uuid (FK)
- total_price: decimal
- status: enum('pending', 'completed', 'cancelled')
- order_time: timestamp
- payment_code: integer (nullable)
- timestamps
```

### Order_Details Table

```sql
- id: uuid (PK)
- order_id: uuid (FK)
- car_id: uuid (FK)
- quantity: integer
- price: decimal
- subtotal_price: decimal
- timestamps
```

### Payments Table

```sql
- id: uuid (PK)
- order_id: uuid (FK)
- amount: decimal
- status: enum('pending', 'paid', 'cancelled')
- payment_method: string (nullable)
- transaction_id: string (nullable)
- timestamps
```

### Tokens Table

```sql
- id: uuid (PK)
- user_id: uuid (FK)
- token: string (unique)
- type: enum('email_verification', 'password_reset')
- expires_at: timestamp
- timestamps
```

## 🔐 Authentication & Authorization

### JWT Token Flow

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant Redis
    participant DB

    Client->>API: POST /auth/login
    API->>DB: Verify credentials
    DB-->>API: User data
    API->>Redis: Store session
    API-->>Client: Access Token + Refresh Token

    Client->>API: Request with Access Token
    API->>Redis: Validate token & check blacklist
    API-->>Client: Protected resource

    Client->>API: POST /auth/refresh
    API->>Redis: Validate refresh token
    API->>Redis: Blacklist old tokens
    API->>Redis: Store new session
    API-->>Client: New Access Token + Refresh Token
```

### Token Structure

**Access Token** (15 minutes expiry):

```json
{
    "sub": "user-uuid",
    "email": "user@example.com",
    "name": "User Name",
    "role": "user",
    "type": "access",
    "iat": 1234567890,
    "exp": 1234568790,
    "jti": "token-uuid"
}
```

**Refresh Token** (7 days expiry):

```json
{
    "sid": "session-uuid",
    "sub": "user-uuid",
    "type": "refresh",
    "role": "user",
    "iat": 1234567890,
    "exp": 1235172690
}
```

### Role-Based Access Control

-   **Admin**: Full access to all endpoints
-   **User**: Access to user-specific endpoints (cart, orders, profile)

Middleware: `JwtMiddleware` + `CheckRole`

## 💳 Payment Integration

### PayOS Payment Flow

```mermaid
sequenceDiagram
    participant User
    participant Frontend
    participant API
    participant PayOS
    participant DB

    User->>Frontend: Checkout
    Frontend->>API: POST /payment/create-link
    API->>DB: Create order & payment record
    API->>PayOS: Create payment request
    PayOS-->>API: Payment link
    API-->>Frontend: Payment link
    Frontend-->>User: Redirect to PayOS

    User->>PayOS: Complete payment
    PayOS-->>Frontend: Redirect to return URL
    Frontend->>API: GET /payment/check-status
    API->>PayOS: Check payment status
    PayOS-->>API: Payment status
    API->>DB: Update order & payment status
    API-->>Frontend: Order confirmed
```

### Payment Status

-   `PENDING` - Đang chờ thanh toán
-   `PAID` - Đã thanh toán thành công
-   `CANCELLED` - Đã hủy

## 🧪 Testing

### Chạy tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=AuthControllerTest

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

### Production Checklist

-   [ ] Set `APP_ENV=production` in `.env`
-   [ ] Set `APP_DEBUG=false`
-   [ ] Generate strong `APP_KEY` and `JWT_SECRET`
-   [ ] Configure production database
-   [ ] Configure Redis for caching
-   [ ] Set up proper CORS settings
-   [ ] Configure AWS S3 for file storage
-   [ ] Set up SSL certificate
-   [ ] Configure email service (SMTP)
-   [ ] Set up PayOS production credentials
-   [ ] Run `php artisan optimize`
-   [ ] Run `php artisan config:cache`
-   [ ] Run `php artisan route:cache`
-   [ ] Run `php artisan view:cache`
-   [ ] Set up queue worker with supervisor
-   [ ] Configure backup strategy

### Performance Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## 📚 Documentation

### API Response Format

**Success Response:**

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data
    }
}
```

**Error Response:**

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        // Validation errors or error details
    }
}
```

### Standard HTTP Status Codes

-   `200` - OK
-   `201` - Created
-   `400` - Bad Request
-   `401` - Unauthorized
-   `403` - Forbidden
-   `404` - Not Found
-   `422` - Validation Error
-   `500` - Internal Server Error

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

-   Follow PSR-12 coding standard
-   Use Laravel best practices
-   Write meaningful commit messages
-   Add tests for new features
-   Update documentation

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

-   **Van Truong Nguyen** - [@VanTruongNg](https://github.com/VanTruongNg)

## 🙏 Acknowledgments

-   [Laravel Framework](https://laravel.com)
-   [PayOS](https://payos.vn)
-   [Firebase JWT](https://github.com/firebase/php-jwt)
-   [AWS S3](https://aws.amazon.com/s3/)

## 📞 Support

Nếu bạn gặp vấn đề hoặc có câu hỏi, vui lòng:

-   Mở một issue trên GitHub
-   Liên hệ qua email: your-email@example.com

---

<p align="center">Made with ❤️ by Van Truong Nguyen</p>
