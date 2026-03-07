# DramaBox Backend API

A comprehensive Laravel backend for a short drama/video streaming platform with coin-based monetization, built with **Laravel 12**, **PostgreSQL**, and **Sanctum API authentication**.

## Features

- **User Authentication** - Register, login, social login (Google/Facebook/Apple), Sanctum tokens
- **Drama Management** - Full CRUD with categories, tags, search, filtering, pagination
- **Episode Streaming** - HLS video support, episode unlocking, watch progress tracking
- **Coin System** - Coin packages, purchases, daily rewards, ad rewards, immutable ledger
- **Social Features** - Comments, likes, ratings, watchlist, watch history
- **Admin Panel API** - Dashboard analytics, content management, user management, reports
- **Notifications** - Database notifications for new episodes, rewards, comment replies
- **Role-Based Access** - Admin, Editor, Moderator, User roles via Spatie Permission

## Tech Stack

- **Framework**: Laravel 12
- **Database**: PostgreSQL
- **Auth**: Laravel Sanctum (token-based)
- **Roles/Permissions**: Spatie Laravel Permission
- **Media**: Spatie Laravel Media Library
- **Image Processing**: Intervention Image

## Setup

### Prerequisites
- PHP 8.2+
- Composer
- PostgreSQL

### Installation

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Update .env with your PostgreSQL credentials:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=dramabox
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# Run migrations and seed
php artisan migrate
php artisan db:seed

# Create storage link
php artisan storage:link

# Start server
php artisan serve
```

### Default Accounts

| Role  | Email              | Password |
|-------|--------------------|----------|
| Admin | admin@dramabox.com | password |
| User  | user@dramabox.com  | password |

## API Endpoints (93 routes)

Base URL: `http://localhost:8000/api`

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /auth/register | Register new user |
| POST | /auth/login | Login (returns token) |
| POST | /auth/social-login | Social login (Google/Facebook/Apple) |
| POST | /v1/auth/logout | Logout (revoke token) |
| GET | /v1/auth/me | Get current user |
| PUT | /v1/auth/fcm-token | Update push notification token |

### Dramas (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /v1/home | Home page data (banners, featured, trending, etc.) |
| GET | /v1/dramas | List dramas (search, filter, sort, paginate) |
| GET | /v1/dramas/featured | Featured dramas |
| GET | /v1/dramas/trending | Trending dramas |
| GET | /v1/dramas/new-releases | New releases |
| GET | /v1/dramas/{id} | Drama details with episodes |
| GET | /v1/categories | List categories |
| GET | /v1/categories/{id} | Category with dramas |

### Episodes
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /v1/episodes/{id} | Episode details (video URL if unlocked) |
| GET | /v1/episodes/{id}/next | Get next episode |
| POST | /v1/episodes/{id}/unlock | Unlock with coins (auth) |
| POST | /v1/episodes/{id}/progress | Update watch progress (auth) |

### Watchlist & History (Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /v1/watchlist | User's watchlist |
| POST | /v1/watchlist/{dramaId} | Add to watchlist |
| DELETE | /v1/watchlist/{dramaId} | Remove from watchlist |
| GET | /v1/watch-history | Watch history |
| GET | /v1/continue-watching | Continue watching list |

### Social (Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /v1/dramas/{id}/like | Toggle drama like |
| POST | /v1/episodes/{id}/like | Toggle episode like |
| POST | /v1/episodes/{id}/comments | Post comment |
| POST | /v1/comments/{id}/like | Toggle comment like |
| POST | /v1/dramas/{id}/rate | Rate drama (1-5) |
| GET | /v1/dramas/{id}/ratings | List ratings (public) |

### Coins & Rewards (Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /v1/coins/balance | Get coin balance |
| GET | /v1/coins/transactions | Transaction history |
| GET | /v1/coins/packages | Available coin packages |
| POST | /v1/coins/daily-reward | Claim daily reward |
| GET | /v1/coins/daily-reward/info | Streak info |
| POST | /v1/coins/ad-reward | Claim ad reward |

### Profile & Notifications (Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | /v1/profile | Update profile |
| PUT | /v1/profile/password | Change password |
| DELETE | /v1/profile | Delete account |
| GET | /v1/notifications | List notifications |
| PUT | /v1/notifications/read-all | Mark all read |
| POST | /v1/reports | Submit content report |

### Admin API (Admin Role Required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/dashboard | Dashboard statistics |
| CRUD | /admin/dramas | Drama management |
| CRUD | /admin/dramas/{id}/episodes | Episode management |
| GET/PUT | /admin/users | User management |
| POST | /admin/users/{id}/grant-coins | Grant coins |
| CRUD | /admin/categories | Category management |
| CRUD | /admin/tags | Tag management |
| CRUD | /admin/banners | Banner management |
| CRUD | /admin/coin-packages | Coin package management |
| GET/PUT | /admin/settings | App settings |
| GET/PUT | /admin/reports | Report management |

## Architecture

```
app/
├── Exceptions/
│   └── InsufficientCoinsException.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── ContentManagementController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DramaManagementController.php
│   │   │   ├── EpisodeManagementController.php
│   │   │   ├── ReportManagementController.php
│   │   │   └── UserManagementController.php
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── CategoryController.php
│   │       ├── CoinController.php
│   │       ├── CommentController.php
│   │       ├── DramaController.php
│   │       ├── EpisodeController.php
│   │       ├── LikeController.php
│   │       ├── NotificationController.php
│   │       ├── ProfileController.php
│   │       ├── RatingController.php
│   │       ├── ReportController.php
│   │       ├── WatchHistoryController.php
│   │       └── WatchlistController.php
│   ├── Middleware/
│   │   ├── EnsureUserIsActive.php
│   │   └── ForceJsonResponse.php
│   └── Requests/
│       ├── Admin/
│       │   ├── StoreDramaRequest.php
│       │   ├── StoreEpisodeRequest.php
│       │   └── UpdateDramaRequest.php
│       └── Auth/
│           ├── LoginRequest.php
│           ├── RegisterRequest.php
│           └── SocialLoginRequest.php
├── Models/
│   ├── AppSetting.php
│   ├── Banner.php
│   ├── Category.php
│   ├── CoinPackage.php
│   ├── CoinTransaction.php
│   ├── Comment.php
│   ├── DailyReward.php
│   ├── Drama.php
│   ├── Episode.php
│   ├── EpisodeUnlock.php
│   ├── Like.php
│   ├── Purchase.php
│   ├── Rating.php
│   ├── Report.php
│   ├── Tag.php
│   ├── User.php
│   └── WatchHistory.php
├── Notifications/
│   ├── CoinReceivedNotification.php
│   ├── CommentReplyNotification.php
│   └── NewEpisodeNotification.php
├── Services/
│   ├── CoinService.php
│   ├── DailyRewardService.php
│   └── EpisodeUnlockService.php
└── Traits/
    └── ApiResponse.php
```

## Security

- Token-based auth via Sanctum
- Rate limiting on auth and sensitive endpoints
- Input validation via Form Request classes
- Role-based access control (Spatie)
- Soft deletes for data retention
- Row-level locking for coin transactions (prevents race conditions)
- Immutable ledger for all coin movements
- CSRF protection for web routes
- Force JSON response middleware for API
