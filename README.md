# ĐỀ TÀI 29 - WEBSITE CÔNG THỨC NẤU ĂN

**Môn học:** Triển khai và Quản trị Hệ thống Phần mềm
**Sinh viên:** Hoàng Tiến Đạt
**MSSV:** DTC245180004
**Tên website:** Bếp Nhà Mình

## 1. Giới thiệu

Bếp Nhà Mình là website chia sẻ công thức nấu ăn, cho phép người dùng xem danh sách món ăn, tìm kiếm, lọc theo danh mục và xem nguyên liệu, cách chế biến.

Dự án triển khai hoàn toàn bằng Docker Compose, tích hợp quản lý cơ sở dữ liệu, reverse proxy, giám sát hiệu năng, log tập trung và các biện pháp bảo mật cơ bản.

## 2. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Website | PHP 8.3 + Apache |
| Cơ sở dữ liệu | MySQL 8.0 |
| Quản lý Database | phpMyAdmin |
| Reverse Proxy | Nginx |
| Container | Docker Compose |
| Giám sát | Prometheus, Grafana |
| Exporters | cAdvisor, Nginx Exporter, MySQL Exporter |
| Log tập trung | Loki, Promtail |
| Quản lý mã nguồn | GitHub |

## 3. Cấu trúc dự án

- `app/`: Mã nguồn website PHP.
- `mysql/init.sql`: Khởi tạo database và dữ liệu mẫu.
- `nginx/default.conf`: Cấu hình Nginx Reverse Proxy.
- `Dockerfile`: Xây dựng image website.
- `compose.yaml`: Website, MySQL, Nginx, phpMyAdmin.
- `compose.monitoring.yaml`: Prometheus, Grafana, Exporters.
- `compose.logging.yaml`: Loki và Promtail.
- `monitoring/`: Cấu hình giám sát và Grafana Dashboard.
- `logging/`: Cấu hình Loki và Promtail.
- `.env.example`: Mẫu biến môi trường, không chứa mật khẩu thật.

## 4. Yêu cầu môi trường

Máy chủ Ubuntu có Docker Engine, Docker Compose V2, Git và Python 3.

Kiểm tra:

```bash
docker --version
docker compose version
git --version
python3 --version
```

## 5. Hướng dẫn cài đặt từ đầu

### 5.1. Tải mã nguồn

```bash
git clone https://github.com/HoangTienDat-171/DTC245180004_De29Recipe.git
cd DTC245180004_De29Recipe
```

### 5.2. Tạo file môi trường

Chỉ thực hiện khi cài đặt mới. Không ghi đè `.env` của hệ thống đang chạy.

```bash
cp .env.example .env

python3 - <<'PY'
from pathlib import Path
import secrets

p = Path(".env")
data = p.read_text()

for key in (
    "MYSQL_ROOT_PASSWORD",
    "MYSQL_PASSWORD",
    "GRAFANA_ADMIN_PASSWORD",
    "MYSQL_EXPORTER_PASSWORD",
):
    data = data.replace(
        key + "=CHANGE_ME",
        key + "=" + secrets.token_hex(16)
    )

p.write_text(data)
print("PASS: Generated environment credentials")
PY

chmod 600 .env
```

Các mật khẩu được tạo ngẫu nhiên và lưu trong `.env`. Không đưa file này lên GitHub.

### 5.3. Khởi chạy website và Database

```bash
sudo docker compose up -d --build
sudo docker compose ps
```

Kiểm tra website:

```bash
curl -I http://127.0.0.1/
```

Kết quả mong đợi: HTTP 200.

MySQL tự khởi tạo các bảng `categories`, `recipes` và 6 công thức mẫu khi volume database được tạo lần đầu.

### 5.4. Tạo tài khoản MySQL Exporter

Chỉ thực hiện khi cài đặt mới, sau khi MySQL đã Healthy.

```bash
python3 - <<'PY' | sudo docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot'
from pathlib import Path
import re

env = dict(
    line.split("=", 1)
    for line in Path(".env").read_text().splitlines()
    if "=" in line and not line.startswith("#")
)

pw = env["MYSQL_EXPORTER_PASSWORD"].strip()

if not re.fullmatch(r"[0-9a-f]{32}", pw):
    raise SystemExit("Invalid generated exporter password")

print(
    "CREATE USER IF NOT EXISTS 'recipe_monitor'@'%' "
    "IDENTIFIED BY '" + pw + "';"
)
print(
    "ALTER USER 'recipe_monitor'@'%' "
    "IDENTIFIED BY '" + pw + "';"
)
print(
    "GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* "
    "TO 'recipe_monitor'@'%';"
)
PY
```

Tài khoản `recipe_monitor` được sử dụng để thu thập metrics MySQL, không dùng cho website.

### 5.5. Khởi chạy toàn bộ hệ thống

```bash
sudo docker compose \
  -f compose.yaml \
  -f compose.monitoring.yaml \
  -f compose.logging.yaml \
  up -d --build
```

Kiểm tra các container:

```bash
sudo docker compose \
  -f compose.yaml \
  -f compose.monitoring.yaml \
  -f compose.logging.yaml \
  ps
```

## 6. Hướng dẫn truy cập và sử dụng

### Website

Truy cập:

`http://IP_MAY_CHU/`

Ví dụ trong mạng thực hành: `http://192.168.52.131/`

Chức năng: xem công thức, tìm kiếm món ăn, lọc danh mục và xem chi tiết cách chế biến.

### phpMyAdmin

Địa chỉ trên máy Ubuntu:

`http://127.0.0.1:8081`

Đăng nhập bằng tài khoản `recipe_user` và mật khẩu `MYSQL_PASSWORD` trong `.env`.

Chọn database `recipe29` để xem bảng `categories` và `recipes`.

### Prometheus

Địa chỉ trên Ubuntu:

`http://127.0.0.1:9090/targets`

Kiểm tra các Targets: prometheus, cadvisor, nginx, mysql.

### Grafana

Địa chỉ trên Ubuntu:

`http://127.0.0.1:3001`

Username: `admin`

Password: Giá trị `GRAFANA_ADMIN_PASSWORD` trong `.env`.

Vào Dashboards, mở `Recipe 29 - System Monitoring`.

Dashboard gồm:
- Docker Container CPU
- Nginx Status
- MySQL Status

### Loki và LogQL

Trong Grafana chọn Explore, Data Source Loki, sau đó chạy:

**Truy vấn 1 - Log toàn hệ thống**

```logql
{compose_project="recipe29"}
```

**Truy vấn 2 - Lọc Nginx HTTP 404**

```logql
{compose_service="nginx"} |= "404"
```

**Truy vấn 3 - Lọc MySQL warning/error**

```logql
{compose_service="mysql"} |~ "(?i)warning|error"
```

Nếu chưa thấy log, mở rộng thời gian tìm kiếm sang Last 24 hours.

## 7. Truy cập từ Windows bằng SSH Tunnel

Các giao diện quản trị và giám sát chỉ mở cổng trên localhost Ubuntu.

Trên Windows PowerShell, chạy:

```powershell
ssh -N -L 18081:127.0.0.1:8081 -L 19090:127.0.0.1:9090 -L 13001:127.0.0.1:3001 dat17@192.168.52.131
```

Thay IP nếu địa chỉ máy Ubuntu thay đổi. Giữ cửa sổ SSH mở.

Sau đó truy cập trên Windows:

- phpMyAdmin: http://127.0.0.1:18081
- Prometheus: http://127.0.0.1:19090/targets
- Grafana: http://127.0.0.1:13001

## 8. Hardening hệ thống

Các biện pháp bảo mật đã triển khai:

1. Nginx chạy bằng tài khoản non-root.
2. Mạng Docker backend sử dụng `internal: true`.
3. Nginx có các Security Headers.
4. Mật khẩu lưu trong `.env`, được Git bỏ qua.
5. MySQL Exporter sử dụng tài khoản giám sát riêng.

Đây là cấu hình cho môi trường thực hành, chưa phải cấu hình production hoàn chỉnh.

## 9. Các lệnh quản trị

Xem container:

```bash
sudo docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml ps
```

Xem log dịch vụ:

```bash
sudo docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml logs --tail=50
```

Dừng hệ thống:

```bash
sudo docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml down
```

Khởi động lại:

```bash
sudo docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml up -d
```

Không sử dụng `down -v` nếu muốn giữ dữ liệu MySQL, Grafana, Prometheus và Loki.

## 10. Lịch sử triển khai

- Commit 1: Website PHP, MySQL, phpMyAdmin, Nginx.
- Commit 2: Prometheus, Grafana và các Exporters.
- Commit 3: Loki, Promtail và tài liệu Hardening.

## 11. Kết quả

Hệ thống đã triển khai website Bếp Nhà Mình, kết nối MySQL, truy cập thông qua Nginx, giám sát với Prometheus và Grafana, truy vấn log với Loki và Promtail.

Mã nguồn và các file cấu hình được quản lý trên GitHub để phục vụ kiểm tra, demo và triển khai lại.
